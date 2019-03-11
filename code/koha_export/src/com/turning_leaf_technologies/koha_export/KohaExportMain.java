package com.turning_leaf_technologies.koha_export;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.SubfieldImpl;

import java.io.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;

public class KohaExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;

	public static void main(String[] args) {
		String serverName = args[0];

		Date startTime = new Date();
		logger = LoggingUtil.setupLogging(serverName, "koha_export");
		logger.info(startTime.toString() + ": Starting Koha Extract");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Connect to the database
		Connection dbConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(ini.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo == null){
				logger.error("Please provide database_aspen_jdbc within config.ini (or better config.pwd.ini) ");
				System.exit(1);
			}
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (Exception e) {
			logger.error("Error connecting to database ", e);
			System.exit(1);
		}

		Connection kohaConn = null;
		try {
			String kohaConnectionJDBC = "jdbc:mysql://" +
					ConfigUtil.cleanIniValue(ini.get("Catalog", "db_host")) +
					"/" + ConfigUtil.cleanIniValue(ini.get("Catalog", "db_name") +
					"?user=" + ConfigUtil.cleanIniValue(ini.get("Catalog", "db_user")) +
					"&password=" + ConfigUtil.cleanIniValue(ini.get("Catalog", "db_pwd")) +
					"&useUnicode=yes&characterEncoding=UTF-8");
			kohaConn = DriverManager.getConnection(kohaConnectionJDBC);
		} catch (Exception e) {
			logger.error("Error connecting to koha database ", e);
			System.exit(1);
		}

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

		//Get a list of works that have changed since the last index
		getChangedRecordsFromDatabase(ini, dbConn, kohaConn);
		exportHolds(dbConn, kohaConn);

		if (dbConn != null){
			try{
				//Close the connection
				dbConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
		if (kohaConn != null){
			try{
				//Close the connection
				kohaConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished Koha Extract");
	}

	private static void exportHolds(Connection dbConn, Connection kohaConn) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = dbConn.setSavepoint();
			dbConn.setAutoCommit(false);
			dbConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeQuery();

			PreparedStatement addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = kohaConn.prepareStatement("select count(reservenumber) as numHolds, biblionumber from reserves group by biblionumber", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()){
				String bibId = bibHoldsRS.getString("biblionumber");
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			try {
				dbConn.commit();
				dbConn.setAutoCommit(true);
			}catch (Exception e){
				logger.warn("error committing hold updates rolling back", e);
				dbConn.rollback(startOfHolds);
			}

		} catch (Exception e) {
			logger.error("Unable to export holds from Koha", e);
			if (startOfHolds != null) {
				try {
					dbConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");
	}

	private static void getChangedRecordsFromDatabase(Ini ini, Connection dbConn, Connection kohaConn) {
		//Get the time the last extract was done
		try{
			logger.info("Starting to load changed records from Koha using the Database connection");
			long lastKohaExtractTime;
			Long lastKohaExtractTimeVariableId = null;

			long updateTime = new Date().getTime() / 1000;

			PreparedStatement markGroupedWorkForBibAsChangedStmt = dbConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
			PreparedStatement loadLastKohaExtractTimeStmt = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_koha_extract_time'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet lastKohaExtractTimeRS = loadLastKohaExtractTimeStmt.executeQuery();
			if (lastKohaExtractTimeRS.next()){
				lastKohaExtractTime = lastKohaExtractTimeRS.getLong("value");
				lastKohaExtractTimeVariableId = lastKohaExtractTimeRS.getLong("id");
			}else{
				//Get the last 5 minutes for the initial setup
				lastKohaExtractTime = new Date().getTime() / 1000 - 5 * 60 * 60;
			}

			//Since we are on a replica of the database, go back 20 minutes to make sure that we cover changes that haven't been replicated
			lastKohaExtractTime -= 20 * 60;

			String maxRecordsToUpdateDuringExtractStr = ini.get("Catalog", "maxRecordsToUpdateDuringExtract");
			int maxRecordsToUpdateDuringExtract = 100000;
			if (maxRecordsToUpdateDuringExtractStr != null){
				maxRecordsToUpdateDuringExtract = Integer.parseInt(maxRecordsToUpdateDuringExtractStr);
			}

			//Only mark records as changed
			boolean errorUpdatingDatabase = false;

			PreparedStatement getChangedItemsFromKohaStmt = kohaConn.prepareStatement("select itemnumber, biblionumber, barcode, damaged, itemlost, wthdrawn, suppress, restricted, onloan from items where timestamp >= ? LIMIT 0, ?");
			getChangedItemsFromKohaStmt.setTimestamp(1, new Timestamp(lastKohaExtractTime * 1000));
			getChangedItemsFromKohaStmt.setLong(2, maxRecordsToUpdateDuringExtract);

			ResultSet itemChangeRS = getChangedItemsFromKohaStmt.executeQuery();
			HashMap<String, ArrayList<ItemChangeInfo>> changedBibs = new HashMap<>();
			while (itemChangeRS.next()){
				String bibNumber = itemChangeRS.getString("biblionumber");
				String itemNumber = itemChangeRS.getString("itemnumber");
				int damaged = itemChangeRS.getInt("damaged");
				String itemlost = itemChangeRS.getString("itemlost");
				int wthdrawn = itemChangeRS.getInt("wthdrawn");
				int suppress = itemChangeRS.getInt("suppress");
				String restricted = itemChangeRS.getString("restricted");
				String onloan = "";
				try {
					onloan = itemChangeRS.getString("onloan");
				}catch (SQLException e){
					logger.info("Invalid onloan value for bib " + bibNumber + " item " + itemNumber);
				}

				ItemChangeInfo changeInfo = new ItemChangeInfo();
				changeInfo.setItemId(itemNumber);
				changeInfo.setDamaged(damaged);
				changeInfo.setItemLost(itemlost);
				changeInfo.setWithdrawn(wthdrawn);
				changeInfo.setSuppress(suppress);
				changeInfo.setRestricted(restricted);
				changeInfo.setOnLoan(onloan);

				ArrayList<ItemChangeInfo> itemChanges;
				if (changedBibs.containsKey(bibNumber)) {
					itemChanges = changedBibs.get(bibNumber);
				}else{
					itemChanges = new ArrayList<>();
					changedBibs.put(bibNumber, itemChanges);
				}
				itemChanges.add(changeInfo);
			}

			dbConn.setAutoCommit(false);
			logger.info("A total of " + changedBibs.size() + " bibs were updated");
			int numUpdates = 0;
			for (String curBibId : changedBibs.keySet()){
				//Update the marc record
				updateMarc(curBibId, changedBibs.get(curBibId));
				//Update the database
				try {
					markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
					markGroupedWorkForBibAsChangedStmt.setString(2, curBibId);
					markGroupedWorkForBibAsChangedStmt.executeUpdate();

					numUpdates++;
					if (numUpdates % 50 == 0){
						dbConn.commit();
					}
				}catch (SQLException e){
					logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
					errorUpdatingDatabase = true;
				}
			}
			//Turn auto commit back on
			dbConn.commit();
			dbConn.setAutoCommit(true);

			if (!errorUpdatingDatabase) {
				//Update the last extract time
				long finishTime = new Date().getTime() / 1000;
				if (lastKohaExtractTimeVariableId != null) {
					PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
					updateVariableStmt.setLong(1, finishTime);
					updateVariableStmt.setLong(2, lastKohaExtractTimeVariableId);
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				} else {
					PreparedStatement insertVariableStmt = dbConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_koha_extract_time', ?)");
					insertVariableStmt.setString(1, Long.toString(finishTime));
					insertVariableStmt.executeUpdate();
					insertVariableStmt.close();
				}
			}else{
				logger.error("There was an error updating the database, not setting last extract time.");
			}

		} catch (Exception e){
			logger.error("Error loading changed records from Koha database", e);
			System.exit(1);
		}
		logger.info("Finished loading changed records from Koha database");
	}

	private static void updateMarc(String curBibId, ArrayList<ItemChangeInfo> itemChangeInfo) {
		//Load the existing marc record from file
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(indexingProfile.getItemTag());
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(indexingProfile.getItemRecordNumberSubfield()) != null) {
							String itemRecordNumber = itemField.getSubfield(indexingProfile.getItemRecordNumberSubfield()).getData();
							//Update the items
							for (ItemChangeInfo curItem : itemChangeInfo) {
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									//Do not update location since we get the permanent location which shouldn't change
									//itemField.getSubfield(locationSubfield).setData(curItem.getLocation());
									setBooleanSubfield(itemField, curItem.getWithdrawn(), '0');
									setSubfieldValue(itemField, '1', curItem.getItemLost());
									setBooleanSubfield(itemField, curItem.getDamaged(), '4');
									setBooleanSubfield(itemField, curItem.getSuppress(), 'i');
									char subfield = '7';
									String newValue = curItem.getRestricted();
									setSubfieldValue(itemField, subfield, newValue);
									setSubfieldValue(itemField, 'q', curItem.getOnLoan());
									setSubfieldValue(itemField, '0', curItem.getOnLoan() == null ? "1" : "0");

								}
							}
						}
					}

					//Write the new marc record
					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
					writer.write(marcRecord);
					writer.close();
				} else {
					logger.info("Could not read marc record for " + curBibId + " the bib was empty");
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
	}

	private static void setSubfieldValue(DataField itemField, char subfield, String newValue) {
		if (newValue == null){
			if (itemField.getSubfield(subfield) != null) itemField.removeSubfield(itemField.getSubfield(subfield));
		}else{

			if (itemField.getSubfield(subfield) != null) {
				itemField.getSubfield(subfield).setData(newValue);
			}else{
				itemField.addSubfield(new SubfieldImpl(subfield, newValue));
			}
		}
	}

	private static void setBooleanSubfield(DataField itemField, int flagValue, char withdrawnSubfieldChar) {
		if (flagValue == 0){
			Subfield withDrawnSubfield = itemField.getSubfield(withdrawnSubfieldChar);
			if (withDrawnSubfield != null){
				itemField.removeSubfield(withDrawnSubfield);
			}
		}else{
			Subfield withDrawnSubfield = itemField.getSubfield(withdrawnSubfieldChar);
			if (withDrawnSubfield == null){
				itemField.addSubfield(new SubfieldImpl(withdrawnSubfieldChar, "1"));
			}else{
				withDrawnSubfield.setData("1");
			}
		}
	}
}
