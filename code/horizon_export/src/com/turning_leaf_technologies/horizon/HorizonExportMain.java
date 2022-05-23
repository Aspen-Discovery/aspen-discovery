package com.turning_leaf_technologies.horizon;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
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

public class HorizonExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;

	private static Connection dbConn;
	private static PreparedStatement markGroupedWorkForBibAsChangedStmt;

	public static void main(String[] args) {
		//Aspen Discovery instance name
		String serverName = args[0];

		Date startTime = new Date();
		logger = LoggingUtil.setupLogging(serverName, "horizon_export");
		logger.info(startTime.toString() + ": Starting Horizon Export");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Connect to the database
		dbConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(ini.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo == null){
				logger.error("Please provide database_aspen_jdbc within config.pwd.ini");
				System.exit(1);
			}
			dbConn = DriverManager.getConnection(databaseConnectionInfo);

			markGroupedWorkForBibAsChangedStmt = dbConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
		} catch (Exception e) {
			logger.error("Error connecting to database ", e);
			System.exit(1);
		}

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

		//Look for any exports from Horizon that have not been processed
		processChangesFromHorizon(ini);

		//TODO: Get a list of records with holds on them?

		//Cleanup
		if (dbConn != null){
			try{
				//Close the connection
				dbConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}

		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished Horizon Export");
	}

	/**
	 * Processes the exports from Horizon.  If a record appears in multiple extracts,
	 * we just process the last extract.
	 *
	 * Expects extracts to already be copied to the server and to be in the
	 * /data/aspen-discovery/{sitename}/marc_changes directory
	 *
	 * @param ini the configuration INI file for Aspen
	 */
	private static void processChangesFromHorizon(Ini ini) {
		String exportPath = ini.get("Reindex", "marcChangesPath");
		File exportFile = new File(exportPath);
		if(!exportFile.exists()){
			logger.error("Export path " + exportPath + " does not exist");
			return;
		}
		File[] files = exportFile.listFiles((dir, name) -> name.matches(".*\\.mrc"));
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
		String[] filenamesArray = filenames.toArray(new String[0]);
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
			dbConn.setAutoCommit(false);
			long updateTime = new Date().getTime() / 1000;
			for (String recordId : recordsToUpdate.keySet()) {
				Record recordToUpdate = recordsToUpdate.get(recordId);
				if (!updateMarc(recordId, recordToUpdate, updateTime)){
					logger.error("Error updating marc record " + recordId);
					errorUpdatingDatabase = true;
				}
				numUpdates++;
				if (numUpdates % 50 == 0){
					dbConn.commit();
				}
			}
		}catch (Exception e){
			logger.error("Error updating marc records");
			errorUpdatingDatabase = true;
		} finally{
			try {
				//Turn auto commit back on
				dbConn.commit();
				dbConn.setAutoCommit(true);
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
			MarcStreamWriter updateWriter = new MarcStreamWriter(marcOutputStream, "UTF-8",true);
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
		List<DataField> recordIdField = getDataFields(marcRecord, indexingProfile.getRecordNumberTagInt());
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

	private static List<DataField> getDataFields(Record marcRecord, int tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}
}
