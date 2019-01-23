package org.pika;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
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

/**
 * Export data from Koha
 *
 * Pika
 * User: Mark Noble
 * Date: 3/22/2015
 * Time: 9:23 PM
 */
public class KohaExportMain {
	private static Logger logger = Logger.getLogger(KohaExportMain.class);
	private static String serverName; //Pika instance name

	private static IndexingProfile indexingProfile;

	public static void main(String[] args) {
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.koha_export.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Koha Extract");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = loadConfigFile("config.ini");

		//Connect to the vufind database
		Connection vufindConn = null;
		try {
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			if (databaseConnectionInfo == null){
				logger.error("Please provide database_vufind_jdbc within config.ini (or better config.pwd.ini) ");
				System.exit(1);
			}
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (Exception e) {
			logger.error("Error connecting to vufind database ", e);
			System.exit(1);
		}

		Connection kohaConn = null;
		try {
			String kohaConnectionJDBC = "jdbc:mysql://" +
					cleanIniValue(ini.get("Catalog", "db_host")) +
					"/" + cleanIniValue(ini.get("Catalog", "db_name") +
					"?user=" + cleanIniValue(ini.get("Catalog", "db_user")) +
					"&password=" + cleanIniValue(ini.get("Catalog", "db_pwd")) +
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
		indexingProfile = IndexingProfile.loadIndexingProfile(vufindConn, profileToLoad, logger);

		//Get a list of works that have changed since the last index
		getChangedRecordsFromDatabase(ini, vufindConn, kohaConn);
		exportHolds(vufindConn, kohaConn);

		if (vufindConn != null){
			try{
				//Close the connection
				vufindConn.close();
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

	private static void exportHolds(Connection vufindConn, Connection kohaConn) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = vufindConn.setSavepoint();
			vufindConn.setAutoCommit(false);
			vufindConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeQuery();

			PreparedStatement addIlsHoldSummary = vufindConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

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
				vufindConn.commit();
				vufindConn.setAutoCommit(true);
			}catch (Exception e){
				logger.warn("error committing hold updates rolling back", e);
				vufindConn.rollback(startOfHolds);
			}

		} catch (Exception e) {
			logger.error("Unable to export holds from Koha", e);
			if (startOfHolds != null) {
				try {
					vufindConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");
	}

	private static void getChangedRecordsFromDatabase(Ini ini, Connection vufindConn, Connection kohaConn) {
		//Get the time the last extract was done
		try{
			logger.info("Starting to load changed records from Koha using the Database connection");
			Long lastKohaExtractTime;
			Long lastKohaExtractTimeVariableId = null;

			long updateTime = new Date().getTime() / 1000;

			PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
			PreparedStatement loadLastKohaExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_koha_extract_time'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
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

			vufindConn.setAutoCommit(false);
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
						vufindConn.commit();
					}
				}catch (SQLException e){
					logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
					errorUpdatingDatabase = true;
				}
			}
			//Turn auto commit back on
			vufindConn.commit();
			vufindConn.setAutoCommit(true);

			if (!errorUpdatingDatabase) {
				//Update the last extract time
				Long finishTime = new Date().getTime() / 1000;
				if (lastKohaExtractTimeVariableId != null) {
					PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
					updateVariableStmt.setLong(1, finishTime);
					updateVariableStmt.setLong(2, lastKohaExtractTimeVariableId);
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				} else {
					PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_koha_extract_time', ?)");
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
					List<VariableField> itemFields = marcRecord.getVariableFields(indexingProfile.itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(indexingProfile.itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(indexingProfile.itemRecordNumberSubfield).getData();
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

	private static Ini loadConfigFile(String filename){
		//First load the default config file
		String configName = "../../sites/default/conf/" + filename;
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		Ini ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}

		//Now override with the site specific configuration
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificFile = new File(siteSpecificFilename);
		if (!siteSpecificFile.exists()) {
			logger.error("Could not find server specific config file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificFile));
			for (Profile.Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
			//Also load password files if they exist
			String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
			logger.info("Loading password config from " + siteSpecificPassword);
			File siteSpecificPasswordFile = new File(siteSpecificPassword);
			if (siteSpecificPasswordFile.exists()) {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Profile.Section curSection : siteSpecificPwdIni.values()){
					for (String curKey : curSection.keySet()){
						ini.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}

		return ini;
	}

	private static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}
}
