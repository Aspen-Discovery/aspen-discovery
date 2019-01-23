package org.pika;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.util.*;


/**
 * Export data from Horizon and update Pika copy MARC records and the database
 * Pika
 * User: Mark Noble
 * Date: 10/18/2015
 * Time: 10:18 PM
 */
public class HorizonExportMain {
	private static Logger logger = Logger.getLogger(HorizonExportMain.class);
	private static String serverName; //Pika instance name

	private static IndexingProfile indexingProfile;

	private static Connection vufindConn;
	private static PreparedStatement markGroupedWorkForBibAsChangedStmt;

	public static void main(String[] args) {
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.horizon_export.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Horizon Export");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = loadConfigFile("config.ini");

		//Connect to the vufind database
		vufindConn = null;
		try {
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			if (databaseConnectionInfo == null){
				logger.error("Please provide database_vufind_jdbc within config.ini (or better config.pwd.ini) ");
				System.exit(1);
			}
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);

			markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
		} catch (Exception e) {
			logger.error("Error connecting to vufind database ", e);
			System.exit(1);
		}

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(vufindConn, profileToLoad, logger);

		//Look for any exports from Horizon that have not been processed
		processChangesFromHorizon(ini);

		//TODO: Get a list of records with holds on them?

		//Cleanup
		if (vufindConn != null){
			try{
				//Close the connection
				vufindConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}

		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished Horizon Export");
	}

	/**
	 * Processes the exports from Horizon.  If a record appears in mulitple extracts,
	 * we just process the last extract.
	 *
	 * Expects extracts to already be copied to the server and to be in the
	 * /data/vufind-plus/{sitename}/marc_changes directory
	 *
	 * @param ini the configuration INI file for Pika
	 */
	private static void processChangesFromHorizon(Ini ini) {
		String exportPath = ini.get("Reindex", "marcChangesPath");
		File exportFile = new File(exportPath);
		if(!exportFile.exists()){
			logger.error("Export path " + exportPath + " does not exist");
			return;
		}
		File[] files = exportFile.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.matches(".*\\.mrc");
			}
		});
		if (files == null){
			//Nothing to process
			return;
		}
		TreeMap<String, File> filesToProcess = new TreeMap<>();
		//Make sure files are sorted in order.  We can do a simple sort since they have the timestamp on them
		for (File file : files){
			filesToProcess.put(file.getName(), file);
		}
		//A list of records to be updated.
		HashMap<String, Record> recordsToUpdate = new HashMap<>();
		Set<String> filenames = filesToProcess.keySet();
		String[] filenamesArray = filenames.toArray(new String[filenames.size()]);
		for (String fileName: filenamesArray){
			File file = filesToProcess.get(fileName);
			logger.debug("Processing " + file.getName());
			try {
				FileInputStream marcFileStream = new FileInputStream(file);
				//Record Grouping always writes individual MARC records as UTF8
				MarcReader updatesReader = new MarcPermissiveStreamReader(marcFileStream, true, true, "UTF8");
				while (updatesReader.hasNext()) {
					try {
						Record curBib = updatesReader.next();
						String recordId = getRecordIdFromMarcRecord(curBib);
						recordsToUpdate.put(recordId, curBib);
					}catch (MarcException me){
						logger.info("File " + file + " has not been fully written", me);
						filesToProcess.remove(fileName);
						break;
					}
				}
				marcFileStream.close();
			} catch (EOFException e){
				logger.info("File " + file + " has not been fully written", e);
				filesToProcess.remove(fileName);
			} catch (Exception e){
				logger.error("Unable to read file " + file + " not processing", e);
				filesToProcess.remove(fileName);
			}
		}
		//Now that we have all the records, merge them and update the database.
		boolean errorUpdatingDatabase = false;
		int numUpdates = 0;
		try {
			vufindConn.setAutoCommit(false);
			long updateTime = new Date().getTime() / 1000;
			for (String recordId : recordsToUpdate.keySet()) {
				Record recordToUpdate = recordsToUpdate.get(recordId);
				if (!updateMarc(recordId, recordToUpdate, updateTime)){
					logger.error("Error updating marc record " + recordId);
					errorUpdatingDatabase = true;
				}
				numUpdates++;
				if (numUpdates % 50 == 0){
					vufindConn.commit();
				}
			}
		}catch (Exception e){
			logger.error("Error updating marc records");
			errorUpdatingDatabase = true;
		} finally{
			try {
				//Turn auto commit back on
				vufindConn.commit();
				vufindConn.setAutoCommit(true);
			}catch (Exception e){
				logger.error("Error committing changes");
			}
		}

		logger.info("Updated a total of " + numUpdates + " from " + filesToProcess.size() + " files");

		if (!errorUpdatingDatabase){
			//Finally, move all files we have processed to another folder (or delete) so we don't process them again
			for (File file : filesToProcess.values()){
				logger.debug("Deleting " + file.getName() + " since it has been processed");
				if (!file.delete()){
					logger.warn("Could not delete " + file.getName());
				}
			}
			logger.info("Deleted " + filesToProcess.size() + " files that were processed successfully.");
		}else{
			logger.error("There were errors updating the database, not clearing the files so they will be processed next time");
		}
	}

	private static boolean updateMarc(String recordId, Record recordToUpdate, long updateTime) {
		//Replace the MARC record in the individual marc records
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(recordId);
			if (!marcFile.exists()){
				//This is a new record, we can just skip it for now.
				return true;
			}

			FileOutputStream marcOutputStream = new FileOutputStream(marcFile);
			MarcStreamWriter updateWriter = new MarcStreamWriter(marcOutputStream);
			updateWriter.setAllowOversizeEntry(true);
			updateWriter.write(recordToUpdate);
			updateWriter.close();
			marcOutputStream.close();
		}catch (Exception e){
			logger.error("Error saving changed MARC record");
		}

		//Update the database to indicate it has changed
		try {
			markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
			markGroupedWorkForBibAsChangedStmt.setString(2, recordId);
			markGroupedWorkForBibAsChangedStmt.executeUpdate();
		}catch (SQLException e){
			logger.error("Could not mark that " + recordId + " was changed due to error ", e);
			return false;
		}
		return true;
	}

	private static String getRecordIdFromMarcRecord(Record marcRecord) {
		List<DataField> recordIdField = getDataFields(marcRecord, indexingProfile.recordNumberTag);
		//Make sure we only get one ils identifier
		for (DataField curRecordField : recordIdField) {
			Subfield subfieldA = curRecordField.getSubfield('a');
			if (subfieldA != null) {
				return curRecordField.getSubfield('a').getData();
			}
		}
		return null;
	}

	private static List<DataField> getDataFields(Record marcRecord, String tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
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
