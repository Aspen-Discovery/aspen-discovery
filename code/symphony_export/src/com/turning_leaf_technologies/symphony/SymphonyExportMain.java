package com.turning_leaf_technologies.symphony;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.BaseMarcRecordGrouper;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.*;
import org.marc4j.marc.*;

import java.io.*;
import java.sql.*;
import java.util.*;
import java.util.Date;

public class SymphonyExportMain {
	private static Logger logger;
	private static IndexingProfile indexingProfile;

	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;

	private static IlsExtractLogEntry logEntry;

	private static Date reindexStartTime;

	private static boolean hadErrors = false;

	public static void main(String[] args){
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		String profileToLoad = "ils";

		String processName = "symphony_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

		while (true) {
			reindexStartTime = new Date();
			logger.info(reindexStartTime.toString() + ": Starting Symphony Extract");

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			int numChanges;

			//Connect to the aspen database
			try {
				String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
				if (databaseConnectionInfo == null) {
					logger.error("Please provide database_aspen_jdbc within config.pwd.ini");
					System.exit(1);
				}
				dbConn = DriverManager.getConnection(databaseConnectionInfo);
				if (dbConn == null) {
					logger.error("Could not establish connection to database at " + databaseConnectionInfo);
					System.exit(1);
				}

				logEntry = new IlsExtractLogEntry(dbConn, profileToLoad, logger);
				//Remove log entries older than 45 days
				long earliestLogToKeep = (reindexStartTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try {
					int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + profileToLoad + "'").executeUpdate();
					logger.info("Deleted " + numDeletions + " old log entries");
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}
			} catch (Exception e) {
				System.out.println("Error connecting to aspen database " + e.toString());
				System.exit(1);
			}

			//TODO: Load the account profile with additional information about Symphony connection if needed.

			indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

			//Check for new marc out
			numChanges = updateRecords(dbConn);

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				if (recordGroupingProcessorSingleton != null) {
					recordGroupingProcessorSingleton.close();
					recordGroupingProcessorSingleton = null;
				}
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}

			//Check for a new holds file
			processNewHoldsFile(dbConn);

			//Check for new orders file(lastExportTime, dbConn);
			processOrdersFile();

			logEntry.setFinished();

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}

			disconnectDatabase();

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				while (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
					try {
						System.gc();
						//noinspection BusyWait
						Thread.sleep(1000 * 60 * 5);
					} catch (InterruptedException e) {
						logger.info("Thread was interrupted");
					}
				}
			}else {
				//Pause before running the next export (longer if we didn't get any actual changes)
				try {
					if (numChanges == 0 || logEntry.hasErrors()) {
						//noinspection BusyWait
						Thread.sleep(1000 * 60 * 5);
					} else {
						//noinspection BusyWait
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}
	}

	private static int updateRecords(Connection dbConn){
		//Get the last export from MARC time
		long lastUpdateFromMarc = indexingProfile.getLastUpdateFromMarcExport();

		//These are all of the full exports, we only want one full export to be processed
		File marcExportPath = new File(indexingProfile.getMarcPath());
		File[] exportedMarcFiles = marcExportPath.listFiles((dir, name) -> name.endsWith("mrc") || name.endsWith("marc"));
		ArrayList<File> filesToProcess = new ArrayList<>();
		File latestFile = null;
		long latestMarcFile = 0;
		boolean hasFullExportFile = false;
		if (exportedMarcFiles != null && exportedMarcFiles.length > 0){
			for (File exportedMarcFile : exportedMarcFiles) {
				//Remove any files that are older than the last time we processed files.
				if (exportedMarcFile.lastModified() / 1000 < lastUpdateFromMarc){
					if (exportedMarcFile.delete()){
						logEntry.addNote("Removed old file " + exportedMarcFile.getAbsolutePath());
					}
				}else{
					if (exportedMarcFile.lastModified() / 1000 > latestMarcFile){
						latestMarcFile = exportedMarcFile.lastModified();
						latestFile = exportedMarcFile;
					}
				}
			}
		}

		if (latestFile != null) {
			filesToProcess.add(latestFile);
			hasFullExportFile = true;
		}

		//Get a list of marc deltas since the last marc record
		File marcDeltaPath = new File(marcExportPath.getParentFile() + "/marc_delta");
		File[] exportedMarcDeltaFiles = marcDeltaPath.listFiles((dir, name) -> name.endsWith("mrc") || name.endsWith("marc"));
		if (exportedMarcDeltaFiles != null && exportedMarcDeltaFiles.length > 0){
			for (File exportedMarcDeltaFile : exportedMarcDeltaFiles) {
				if (exportedMarcDeltaFile.lastModified() / 1000 < lastUpdateFromMarc){
					if (exportedMarcDeltaFile.delete()){
						logEntry.addNote("Removed old delta file " + exportedMarcDeltaFile.getAbsolutePath());
					}
				}else{
					if (exportedMarcDeltaFile.lastModified() > latestMarcFile){
						filesToProcess.add(exportedMarcDeltaFile);
					}
				}
			}
		}

		if (filesToProcess.size() > 0){
			//Update all records based on the MARC export
			logEntry.addNote("Updating based on MARC extract");
			return updateRecordsUsingMarcExtract(filesToProcess, hasFullExportFile, dbConn);
		}else{
			//TODO: See if we can get more runtime info from SirsiDynix APIs;
			return 0;
		}
	}

	/**
	 * Updates Aspen using the MARC export or exports provided.
	 * To see which records are deleted it needs to get a list of all records that are already in the database
	 * so it can detect what has been deleted.
	 *
	 * @param exportedMarcFiles - An array of files to process
	 * @param hasFullExportFile - Whether or not we are including a full export.  We will only delete records if we have a full export.
	 * @param dbConn            - Connection to the Aspen database
	 * @return - total number of changes that were found
	 */
	private static int updateRecordsUsingMarcExtract(ArrayList<File> exportedMarcFiles, boolean hasFullExportFile, Connection dbConn) {
		int totalChanges = 0;
		MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor(dbConn);
		if (!recordGroupingProcessor.isValid()){
			logEntry.incErrors("Record Grouping Processor was not valid");
			return totalChanges;
		}else if (!recordGroupingProcessor.loadExistingTitles(logEntry)){
			return totalChanges;
		}

		for (File curBibFile : exportedMarcFiles) {
			//Make sure the file is not currently changing.
			boolean isFileChanging = true;
			long lastSizeCheck = curBibFile.length();
			while (isFileChanging) {
				try {
					Thread.sleep(5000); //Wait 5 seconds
				} catch (InterruptedException e) {
					logEntry.incErrors("Error checking if a file is still changing", e);
				}
				if (lastSizeCheck == curBibFile.length()){
					isFileChanging = false;
				}else{
					lastSizeCheck = curBibFile.length();
				}
			}
			logEntry.addNote("Processing file " + curBibFile.getAbsolutePath());

			int numRecordsRead = 0;
			String lastRecordProcessed = "";
			try {
				FileInputStream marcFileStream = new FileInputStream(curBibFile);
				MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, indexingProfile.getMarcEncoding());
				while (catalogReader.hasNext()) {
					logEntry.incProducts();
					try{
						Record curBib = catalogReader.next();
						RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile.getName(), indexingProfile.isDoAutomaticEcontentSuppression());
						boolean deleteRecord = false;
						if (recordIdentifier == null) {
							//logger.debug("Record with control number " + curBib.getControlNumber() + " was suppressed or is eContent");
							String controlNumber = curBib.getControlNumber();
							if (controlNumber == null) {
								logger.warn("Bib did not have control number or identifier");
							}
						}else if (!recordIdentifier.isSuppressed()) {
							String recordNumber = recordIdentifier.getIdentifier();

							BaseMarcRecordGrouper.MarcStatus marcStatus = recordGroupingProcessor.writeIndividualMarc(indexingProfile, curBib, recordNumber, logger);
							if (marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED || indexingProfile.isRunFullUpdate()) {
								String permanentId = recordGroupingProcessor.processMarcRecord(curBib, marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED);
								if (permanentId == null){
									//Delete the record since it is suppressed
									deleteRecord = true;
								}else {
									if (marcStatus == BaseMarcRecordGrouper.MarcStatus.NEW){
										logEntry.incAdded();
									}else {
										logEntry.incUpdated();
									}
									getGroupedWorkIndexer(dbConn).processGroupedWork(permanentId);
									totalChanges++;
								}
							}else{
								logEntry.incSkipped();
							}
							if (totalChanges % 5000 == 0) {
								getGroupedWorkIndexer(dbConn).commitChanges();
							}
							//Mark that the record was processed
							recordGroupingProcessor.removeExistingRecord(recordIdentifier.getIdentifier());
							lastRecordProcessed = recordNumber;
						}else{
							//Delete the record since it is suppressed
							deleteRecord = true;
						}
						if (deleteRecord){
							RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), recordIdentifier.getIdentifier());
							if (result.reindexWork){
								getGroupedWorkIndexer(dbConn).processGroupedWork(result.permanentId);
							}else if (result.deleteWork){
								//Delete the work from solr and the database
								getGroupedWorkIndexer(dbConn).deleteRecord(result.permanentId, result.groupedWorkId);
							}
							logEntry.incDeleted();
							totalChanges++;
						}
					}catch (MarcException me){
						logEntry.incErrors("Error processing individual record  on record " + numRecordsRead + " of " + curBibFile.getAbsolutePath() + " the last record processed was " + lastRecordProcessed + " trying to continue", me);
					}
					numRecordsRead++;
					if (numRecordsRead % 250 == 0) {
						logEntry.saveResults();
					}
				}
				marcFileStream.close();
			} catch (Exception e) {
				logEntry.incErrors("Error loading Symphony bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + curBibFile.getAbsolutePath(), e);
			}
		}

		//Loop through remaining records and delete them
		if (hasFullExportFile) {
			for (String identifier : recordGroupingProcessor.getExistingRecords().keySet()) {
				RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), identifier);
				if (result.reindexWork){
					getGroupedWorkIndexer(dbConn).processGroupedWork(result.permanentId);
				}else if (result.deleteWork){
					//Delete the work from solr and the database
					getGroupedWorkIndexer(dbConn).deleteRecord(result.permanentId, result.groupedWorkId);
				}
				logEntry.incDeleted();
				totalChanges++;
			}
		}


		try {
			PreparedStatement updateMarcExportStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateFromMarcExport = ? where id = ?");
			updateMarcExportStmt.setLong(1, reindexStartTime.getTime() / 1000);
			updateMarcExportStmt.setLong(2, indexingProfile.getId());
			updateMarcExportStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Error updating lastUpdateFromMarcExport", e);
		}

		return totalChanges;
	}

	private static MarcRecordGrouper getRecordGroupingProcessor(Connection dbConn){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer(Connection dbConn) {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static void disconnectDatabase() {
		try {
			//Close the connection
			if (dbConn != null) {
				dbConn.close();
				dbConn = null;
			}
		} catch (Exception e) {
			System.out.println("Error closing aspen connection: " + e.toString());
			e.printStackTrace();
		}
	}

	private static void processOrdersFile() {
		File mainFile = new File(indexingProfile.getMarcPath() + "/fullexport.mrc");
		HashSet<String> idsInMainFile = new HashSet<>();
		if (mainFile.exists()){
			try {
				MarcReader reader = new MarcPermissiveStreamReader(new FileInputStream(mainFile), true, true);
				int numRecordsRead = 0;
				while (reader.hasNext()) {
					try {
						Record marcRecord = reader.next();
						numRecordsRead++;
						String id = getPrimaryIdentifierFromMarcRecord(marcRecord);
						idsInMainFile.add(id);
					}catch (MarcException me){
						logger.warn("Error processing individual record  on record " + numRecordsRead + " of " + mainFile.getAbsolutePath(), me);
					}
				}
			}catch (Exception e){
				logger.error("Error loading existing marc ids", e);
			}
		}

		//We have gotten 2 different exports a single export as CSV and a second daily version as XLSX.  If the XLSX exists, we will
		//process that and ignore the CSV version.
		File ordersFileMarc = new File(indexingProfile.getMarcPath() + "/orders.mrc");
		File ordersFile = new File(indexingProfile.getMarcPath() + "/onorderfile.txt");
		convertOrdersFileToMarc(ordersFile, ordersFileMarc, idsInMainFile);

	}

	private static void convertOrdersFileToMarc(File ordersFile, File ordersFileMarc, HashSet<String> idsInMainFile) {
		if (ordersFile.exists()){
			long now = new Date().getTime();
			long ordersFileLastModified = ordersFile.lastModified();
			if (now - ordersFileLastModified > 7 * 24 * 60 * 60 * 1000){
				logger.warn("Orders File was last written more than 7 days ago");
			}
			//Always process since we only received one export and we are gradually removing records as they appear in the full export.
			try{
				MarcWriter writer = new MarcStreamWriter(new FileOutputStream(ordersFileMarc, false), "UTF-8", true);
				BufferedReader ordersReader = new BufferedReader(new InputStreamReader(new FileInputStream(ordersFile)));
				String line = ordersReader.readLine();
				int numOrderRecordsWritten = 0;
				int numOrderRecordsSkipped = 0;
				while (line != null){
					int firstPipePos = line.indexOf('|');
					if (firstPipePos != -1){
						String recordNumber = line.substring(0, firstPipePos);
						line = line.substring(firstPipePos + 1);
						if (recordNumber.matches("^\\d+$")) {
							if (!idsInMainFile.contains("a" + recordNumber)){
								if (line.endsWith("|")){
									line = line.substring(0, line.length() - 1);
								}
								int lastPipePosition = line.lastIndexOf('|');
								String title = line.substring(lastPipePosition + 1);
								line = line.substring(0, lastPipePosition);
								lastPipePosition = line.lastIndexOf('|');
								String author = line.substring(lastPipePosition + 1);
								line = line.substring(0, lastPipePosition);
								String ohohseven = line.replace("|", " ");
								//The marc record does not exist, create a temporary bib in the orders file which will get processed by record grouping
								MarcFactory factory = MarcFactory.newInstance();
								Record marcRecord = factory.newRecord();
								marcRecord.addVariableField(factory.newControlField("001", "a" + recordNumber));
								if (!ohohseven.equals("-")) {
									marcRecord.addVariableField(factory.newControlField("007", ohohseven));
								}
								if (!author.equals("-")){
									marcRecord.addVariableField(factory.newDataField("100", '0', '0', "a", author));
								}
								marcRecord.addVariableField(factory.newDataField("245", '0', '0', "a", title));
								writer.write(marcRecord);
								numOrderRecordsWritten++;
							}else{
								logger.info("Marc record already exists for a" + recordNumber);
								numOrderRecordsSkipped++;
							}
						}
					}
					line = ordersReader.readLine();
				}
				writer.close();
				logger.info("Finished writing Orders to MARC record");
				logger.info("Wrote " + numOrderRecordsWritten);
				logger.info("Skipped " + numOrderRecordsSkipped + " because they are in the main export");
			}catch (Exception e){
				logger.error("Error reading orders file ", e);
			}
		}else{
			logger.warn("Could not find orders file at " + ordersFile.getAbsolutePath());
		}
	}

	/**
	 * Check the marc folder to see if the holds files have been updated since the last export time.
	 *
	 * If so, load a count of holds per bib and then update the database.
	 *
	 * @param aspenConn       the connection to the database
	 */
	private static void processNewHoldsFile(Connection aspenConn) {
		HashMap<String, Integer> holdsByBib = new HashMap<>();
		boolean writeHolds = false;
		File holdFile = new File(indexingProfile.getMarcPath() + "/Holds.csv");
		if (holdFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = holdFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Holds File was last written more than 2 days ago");
			}else{
				writeHolds = true;
				String lastCatalogIdRead = "";
				try {
					BufferedReader reader = new BufferedReader(new FileReader(holdFile));
					String line = reader.readLine();
					while (line != null){
						int firstComma = line.indexOf(',');
						if (firstComma > 0){
							String catalogId = line.substring(0, firstComma);
							catalogId = catalogId.replaceAll("\\D", "");
							lastCatalogIdRead = catalogId;
							//Make sure the catalog is numeric
							if (catalogId.length() > 0 && catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						line = reader.readLine();
					}
				}catch (Exception e){
					logger.error("Error reading holds file ", e);
					hadErrors = true;
				}
				logger.info("Read " + holdsByBib.size() + " bibs with holds, lastCatalogIdRead = " + lastCatalogIdRead);
			}
		}else{
			logger.warn("No holds file found at " + indexingProfile.getMarcPath() + "/Holds.csv");
			hadErrors = true;
		}

		File periodicalsHoldFile = new File(indexingProfile.getMarcPath() + "/Hold_Periodicals.csv");
		if (periodicalsHoldFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = periodicalsHoldFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Periodicals Holds File was last written more than 2 days ago");
			}else {
				writeHolds = true;
				try {
					BufferedReader reader = new BufferedReader(new FileReader(periodicalsHoldFile));
					String line = reader.readLine();
					String lastCatalogIdRead = "";
					while (line != null){
						int firstComma = line.indexOf(',');
						if (firstComma > 0){
							String catalogId = line.substring(0, firstComma);
							catalogId = catalogId.replaceAll("\\D", "");
							lastCatalogIdRead = catalogId;
							//Make sure the catalog is numeric
							if (catalogId.length() > 0 && catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						line = reader.readLine();
					}
					logger.info(holdsByBib.size() + " bibs with holds (including periodicals) lastCatalogIdRead for periodicals = " + lastCatalogIdRead);
				}catch (Exception e){
					logger.error("Error reading periodicals holds file ", e);
					hadErrors = true;
				}
			}
		}else{
			logger.warn("No periodicals holds file found at " + indexingProfile.getMarcPath() + "/Hold_Periodicals.csv" );
			hadErrors = true;
		}

		//Now that we've counted all the holds, update the database
		if (!hadErrors && writeHolds){
			try {
				aspenConn.setAutoCommit(false);
				aspenConn.prepareCall("DELETE FROM ils_hold_summary").executeUpdate();
				logger.info("Removed existing holds");
				PreparedStatement updateHoldsStmt = aspenConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");
				for (String ilsId : holdsByBib.keySet()){
					updateHoldsStmt.setString(1, "a" + ilsId);
					updateHoldsStmt.setInt(2, holdsByBib.get(ilsId));
					int numUpdates = updateHoldsStmt.executeUpdate();
					if (numUpdates != 1){
						logger.info("Hold was not inserted " + "a" + ilsId + " " + holdsByBib.get(ilsId));
					}
				}
				aspenConn.commit();
				aspenConn.setAutoCommit(true);
				logger.info("Finished adding new holds to the database");
			}catch (Exception e){
				logger.error("Error updating holds database", e);
				hadErrors = true;
			}
		}
	}


	private static String getPrimaryIdentifierFromMarcRecord(Record marcRecord) {
		List<VariableField> recordNumberFields = marcRecord.getVariableFields(indexingProfile.getRecordNumberTag());
		String recordNumber = null;
		//Make sure we only get one ils identifier
		for (VariableField curVariableField : recordNumberFields) {
			if (curVariableField instanceof DataField) {
				DataField curRecordNumberField = (DataField) curVariableField;
				Subfield subfieldA = curRecordNumberField.getSubfield('a');
				if (subfieldA != null && (indexingProfile.getRecordNumberPrefix().length() == 0 || subfieldA.getData().length() > indexingProfile.getRecordNumberPrefix().length())) {
					if (curRecordNumberField.getSubfield('a').getData().startsWith(indexingProfile.getRecordNumberPrefix())) {
						recordNumber = curRecordNumberField.getSubfield('a').getData().trim();
						break;
					}
				}
			} else {
				//It's a control field
				ControlField curRecordNumberField = (ControlField) curVariableField;
				recordNumber = curRecordNumberField.getData().trim();
				break;
			}
		}
		return recordNumber;
	}
}
