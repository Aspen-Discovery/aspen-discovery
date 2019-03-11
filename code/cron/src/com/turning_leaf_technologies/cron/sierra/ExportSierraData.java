package com.turning_leaf_technologies.cron.sierra;

import com.turning_leaf_technologies.cron.CronLogEntry;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;

import java.sql.*;

@SuppressWarnings("unused")
public class ExportSierraData implements IProcessHandler {
	private CronProcessLogEntry processLog;
	private Logger logger;

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		this.logger = logger;
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Export Sierra Data");
		processLog.saveToDatabase(dbConn, logger);

		String ils = configIni.get("Catalog", "ils");
		if (!ils.equalsIgnoreCase("Sierra")){
			processLog.addNote("ILS is not Sierra, quiting");
		}else{
			//Connect to the sierra database
			String url = configIni.get("Catalog", "sierra_db");
			if (url.startsWith("\"")){
				url = url.substring(1, url.length() - 1);
			}
			Connection conn;
			try{
				//Open the connection to the database
				conn = DriverManager.getConnection(url);

				exportVolumes(conn, dbConn);

				conn.close();
			}catch(Exception e){
				System.out.println("Error: " + e.toString());
				e.printStackTrace();
			}
		}

		processLog.setFinished();
		processLog.saveToDatabase(dbConn, logger);
	}

	private void exportVolumes(Connection conn, Connection dbConn){
		try {
			logger.info("Starting export of volume information");
			PreparedStatement getVolumeInfoStmt = conn.prepareStatement("select volume_view.id, volume_view.record_num as volume_num, sort_order from sierra_view.volume_view " +
					"inner join sierra_view.bib_record_volume_record_link on bib_record_volume_record_link.volume_record_id = volume_view.id " +
					"where volume_view.is_suppressed = 'f'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getBibForVolumeStmt = conn.prepareStatement("select record_num from sierra_view.bib_record_volume_record_link " +
					"inner join sierra_view.bib_view on bib_record_volume_record_link.bib_record_id = bib_view.id " +
					"where volume_record_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getItemsForVolumeStmt = conn.prepareStatement("select record_num from sierra_view.item_view " +
					"inner join sierra_view.volume_record_item_record_link on volume_record_item_record_link.item_record_id = item_view.id " +
					"where volume_record_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getVolumeNameStmt = conn.prepareStatement("SELECT * FROM sierra_view.subfield where record_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			PreparedStatement removeOldVolumes = dbConn.prepareStatement("DELETE FROM ils_volume_info WHERE recordId LIKE 'ils%'");
			PreparedStatement addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems) VALUES (?,?,?,?)");

			ResultSet volumeInfoRS = null;
			boolean loadError = false;
			boolean updateError = false;
			Savepoint transactionStart = dbConn.setSavepoint("load_volumes");
			try {
				volumeInfoRS = getVolumeInfoStmt.executeQuery();
			} catch (SQLException e1) {
				logger.error("Error loading volume information", e1);
				loadError = true;
			}
			if (!loadError) {
				try {
					removeOldVolumes.executeUpdate();
				}catch (SQLException sqle){
					logger.error("Error removing old volume information", sqle);
					updateError = true;
				}

				while (volumeInfoRS.next()) {
					long recordId = volumeInfoRS.getLong("id");

					String volumeId = volumeInfoRS.getString("volume_num");
					volumeId = ".j" + volumeId + getCheckDigit(volumeId);

					getBibForVolumeStmt.setLong(1, recordId);
					ResultSet bibForVolumeRS = getBibForVolumeStmt.executeQuery();
					String bibId = "";
					if (bibForVolumeRS.next()) {
						bibId = bibForVolumeRS.getString("record_num");
						bibId = ".b" + bibId + getCheckDigit(bibId);
					}

					getItemsForVolumeStmt.setLong(1, recordId);
					ResultSet itemsForVolumeRS = getItemsForVolumeStmt.executeQuery();
					StringBuilder itemsForVolume = new StringBuilder();
					while (itemsForVolumeRS.next()) {
						String itemId = itemsForVolumeRS.getString("record_num");
						if (itemId != null) {
							itemId = ".i" + itemId + getCheckDigit(itemId);
							if (itemsForVolume.length() > 0) itemsForVolume.append("|");
							itemsForVolume.append(itemId);
						}
					}

					getVolumeNameStmt.setLong(1, recordId);
					ResultSet getVolumeNameRS = getVolumeNameStmt.executeQuery();
					String volumeName = "Unknown";
					if (getVolumeNameRS.next()) {
						volumeName = getVolumeNameRS.getString("content");
					}

					try {
						addVolumeStmt.setString(1, "ils:" + bibId);
						addVolumeStmt.setString(2, volumeId);
						addVolumeStmt.setString(3, volumeName);
						addVolumeStmt.setString(4, itemsForVolume.toString());
						addVolumeStmt.executeUpdate();
						processLog.incUpdated();
					}catch (SQLException sqle){
						logger.error("Error adding volume", sqle);
						processLog.incErrors();
						updateError = true;
					}
				}
				volumeInfoRS.close();
			}
			if (updateError){
				dbConn.rollback(transactionStart);
			}
			dbConn.setAutoCommit(true);
			logger.info("Finished export of volume information");
		}catch (Exception e){
			logger.error("Error exporting volume information", e);
			processLog.incErrors();
			processLog.addNote("Error exporting volume information " + e.toString());

		}
		processLog.setFinished();
		processLog.saveToDatabase(dbConn, logger);
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	private static String getCheckDigit(String basedId) {
		int sumOfDigits = 0;
		for (int i = 0; i < basedId.length(); i++){
			int multiplier = ((basedId.length() +1 ) - i);
			sumOfDigits += multiplier * Integer.parseInt(basedId.substring(i, i+1));
		}
		int modValue = sumOfDigits % 11;
		if (modValue == 10){
			return "x";
		}else{
			return Integer.toString(modValue);
		}
	}

}
