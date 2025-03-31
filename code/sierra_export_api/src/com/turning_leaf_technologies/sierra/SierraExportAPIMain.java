package com.turning_leaf_technologies.sierra;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

import com.opencsv.CSVReader;
import com.opencsv.CSVWriter;
import com.turning_leaf_technologies.file.JarUtil;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.*;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.config.ConfigUtil;
import org.aspen_discovery.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import javax.net.ssl.HttpsURLConnection;
import org.apache.commons.codec.binary.Base64;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

public class SierraExportAPIMain {
	private static Logger logger;

	private static final SimpleDateFormat dateTimeFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
	private static final SimpleDateFormat dateFormatter = new SimpleDateFormat("yyyy-MM-dd");

	private static IndexingProfile indexingProfile;
	private static SierraExportFieldMapping sierraExportFieldMapping;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;

	private static String apiBaseUrl = null;

	private static final TreeSet<String> allBibsToUpdate = new TreeSet<>();
	private static final TreeSet<String> allDeletedIds = new TreeSet<>();
	/**
	 * Bibs that have MARC holdings with short id mapped to full id
	 */
	private static final HashMap<String, String> bibsWithHoldings = new HashMap<>();

	//Reporting information
	private static IlsExtractLogEntry logEntry;

	public static void main(String[] args){
		boolean extractSingleRecord = false;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleRecord = true;
			}
		} else {
			serverName = args[0];
			if (args.length > 1){
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equals("singleRecord")){
					extractSingleRecord = true;
				}
			}
		}

		if (extractSingleRecord){
			String recordToExtract = AspenStringUtils.getInputFromCommandLine("Enter the id of the record to extract, can optionally include the .b or the check digit for Sierra/Millennium systems");
			if (recordToExtract.startsWith(".b")){
				recordToExtract = recordToExtract.substring(2, recordToExtract.length() -1);
			}
			allBibsToUpdate.add(recordToExtract);
		}

		String profileToLoad = "ils";
		String processName = "sierra_export_api";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started to stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			Date startTime = new Date();
			long startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime + ": Starting Sierra Extract");

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			int numChanges = 0;
			//Connect to the aspen database
			dbConn = null;
			try{
				//Connect to the Aspen Database
				String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
				if (databaseConnectionInfo == null) {
					logger.error("Please provide database_aspen_jdbc within config.pwd.ini");
					System.exit(1);
				}
				dbConn = DriverManager.getConnection(databaseConnectionInfo);

				//Check to see if the jar has changes before processing records, and if so quit
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

				logEntry = new IlsExtractLogEntry(dbConn, profileToLoad, logger);
				//Remove log entries older than 45 days
				long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try {
					int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + profileToLoad + "'").executeUpdate();
					logger.info("Deleted " + numDeletions + " old log entries");
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}
				SystemUtils.quitIfOffline(dbConn, logger, logEntry);

				//Connect to the Sierra database
				Connection sierraConn = null;
				SierraInstanceInformation sierraInstanceInformation = initializeSierraConnection(dbConn);
				indexingProfile = IndexingProfile.loadIndexingProfile(serverName, dbConn, sierraInstanceInformation.indexingProfileName, logger, logEntry);
				logEntry.setIsFullUpdate(indexingProfile.isRunFullUpdate());

				if (sierraInstanceInformation.sierraConnection == null) {
					logEntry.incErrors("Could not connect to the Sierra database");
				}else{
					//Open the connection to the database
					sierraConn = sierraInstanceInformation.sierraConnection;
					if (!extractSingleRecord) {
						//exportValidPatronIds(indexingProfile.getMarcPath(), sierraConn);
						exportActiveOrders(indexingProfile.getMarcPath(), sierraConn);
						exportHolds(sierraConn, dbConn);
						exportVolumes(sierraConn, dbConn);
					}
					getBibsWithHoldings(sierraConn);
				}

				sierraExportFieldMapping = SierraExportFieldMapping.loadSierraFieldMappings(dbConn, indexingProfile.getId(), logEntry);

				String apiVersion = sierraInstanceInformation.apiVersion;
				if (apiVersion == null || apiVersion.isEmpty()){
					logger.error("No API Version was provided");
					return;
				}
				apiBaseUrl = sierraInstanceInformation.apiBaseUrl + "/iii/sierra-api/v" + apiVersion;


				if (!extractSingleRecord) {
					//Check to see if we should regroup all existing records
					try {
						if (indexingProfile.isRegroupAllRecords()) {
							MarcRecordGrouper recordGrouper = getRecordGroupingProcessor();
							recordGrouper.regroupAllRecords(dbConn, indexingProfile, getGroupedWorkIndexer(), logEntry);
						}
					}catch (Exception e){
						logEntry.incErrors("Error regrouping all records", e);
					}

					//Get a list of all active bibs, so we can see what needs to be deleted.
					if (sierraInstanceInformation.sierraConnection != null) {
						checkForDeletedBibsInSierra(sierraConn);
					}

					//Load MARC record changes
					getBibsAndItemUpdatesFromSierra(sierraInstanceInformation, sierraConn);
				}

				loadRecordsToReload(indexingProfile, logEntry);

				logEntry.setNumProducts(allBibsToUpdate.size());
				logEntry.saveResults();

				numChanges = updateBibs(sierraInstanceInformation);



				if (sierraConn != null){
					try{
						//Close the connection
						sierraConn.close();
					}catch(Exception e){
						System.out.println("Error closing connection: " + e);
					}
				}

				if (recordGroupingProcessorSingleton != null) {
					recordGroupingProcessorSingleton.close();
					recordGroupingProcessorSingleton = null;
				}

				if (groupedWorkIndexer != null) {
					groupedWorkIndexer.finishIndexingFromExtract(logEntry);
					groupedWorkIndexer.close();
					groupedWorkIndexer = null;
				}

				//Update the last extract time for the indexing profile
				if (!extractSingleRecord) {
					if (indexingProfile.isRunFullUpdate()) {
						PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
						updateVariableStmt.setLong(1, startTimeForLogging);
						updateVariableStmt.setLong(2, indexingProfile.getId());
						updateVariableStmt.executeUpdate();
						updateVariableStmt.close();
					} else {
						if (!logEntry.hasErrors()) {
							PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfChangedRecords = ? WHERE id = ?");
							updateVariableStmt.setLong(1, startTimeForLogging);
							updateVariableStmt.setLong(2, indexingProfile.getId());
							updateVariableStmt.executeUpdate();
							updateVariableStmt.close();
						}
					}
				}

				logEntry.addNote("Finished exporting sierra data " + new Date());
				logEntry.setFinished();

				Date currentTime = new Date();
				logger.info(currentTime + ": Finished Sierra Extract");
			}catch (Exception e){
				System.out.println("Error extracting data from Sierra " + e);
				System.exit(1);
			}

			if (extractSingleRecord){
				disconnectDatabase();
				break;
			}
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
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase();
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
				logger.info("Ending because we have low memory available");
				disconnectDatabase();
				break;
			}

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else {
				//Pause before running the next export (longer if we didn't get any actual changes)
				try {
					System.gc();
					if (numChanges == 0) {
						Thread.sleep(1000 * 60 * 5);
					} else {
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		} //Infinite loop

		System.exit(0);
	}

	private static void checkForDeletedBibsInSierra(Connection sierraConn) {
		try {
			HashSet<String> allBibsInSierra = new HashSet<>();
			PreparedStatement getAllBibsStmt = sierraConn.prepareStatement("SELECT record_type_code, record_num FROM sierra_view.bib_record LEFT JOIN sierra_view.record_metadata ON sierra_view.bib_record.record_id = sierra_view.record_metadata.id WHERE record_type_code = 'b' AND is_suppressed = FALSE;", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getAllBibsRS = getAllBibsStmt.executeQuery();
			while (getAllBibsRS.next()) {
				String shortId = getAllBibsRS.getString("record_num");
				String longId = ".b" + shortId + getCheckDigit(shortId);
				allBibsInSierra.add(longId);
			}
			getAllBibsRS.close();
			getAllBibsStmt.close();

			HashSet<String> bibsToDelete = new HashSet<>();
			HashSet<String> allBibsInAspen = getRecordGroupingProcessor().loadExistingActiveIds(logEntry);
			if (allBibsInAspen != null) {
				for (String bibId : allBibsInAspen) {
					if (!allBibsInSierra.contains(bibId)) {
						//This bib has been deleted
						bibsToDelete.add(bibId);
					} else {
						//Remove it faster to lookup information in the future
						allBibsInSierra.remove(bibId);
					}
				}

				logEntry.addNote("Deleting " + bibsToDelete.size() + " bibs that no longer exist in Sierra.");
				for (String bibToDelete : bibsToDelete) {
					getGroupedWorkIndexer().markIlsRecordAsDeleted(indexingProfile.getName(), bibToDelete);
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibToDelete);
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					logEntry.incDeleted();
				}
			}
		}catch (Exception e) {
			logEntry.incErrors("Error checking Sierra for deleted bibs");
		}
		logEntry.saveResults();
	}

	private static void disconnectDatabase() {
		try{
			//Close the connection
			dbConn.close();
			bibsWithHoldings.clear();
			allDeletedIds.clear();
			allBibsToUpdate.clear();
		}catch(Exception e){
			System.out.println("Error closing connection: " + e);
		}
	}

	private static void loadRecordsToReload(IndexingProfile indexingProfile, IlsExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='" + indexingProfile.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				if (recordIdentifier.startsWith(".b")){
					recordIdentifier = recordIdentifier.substring(2, recordIdentifier.length() -1);
				}
				allBibsToUpdate.add(recordIdentifier);

				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Loaded " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static void getBibsAndItemUpdatesFromSierra(SierraInstanceInformation sierraInstanceInformation, Connection sierraConn) {
		long lastSierraExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
		if (indexingProfile.getLastUpdateOfAllRecords() > lastSierraExtractTime){
			lastSierraExtractTime = indexingProfile.getLastUpdateOfAllRecords();
		}

		//Last Update in UTC
		if (lastSierraExtractTime == 0 || indexingProfile.isRunFullUpdate()){
			//Export all records
			logEntry.addNote("Loading all records");
			//Make a call to the database to get a list of all bibs that are not suppressed
			try {
				PreparedStatement getAllBibsStmt = sierraConn.prepareStatement("SELECT record_type_code, record_num FROM sierra_view.bib_record LEFT JOIN sierra_view.record_metadata ON sierra_view.bib_record.record_id = sierra_view.record_metadata.id WHERE record_type_code = 'b' AND is_suppressed = FALSE;", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet getAllBibsRS = getAllBibsStmt.executeQuery();
				while (getAllBibsRS.next()){
					allBibsToUpdate.add(getAllBibsRS.getString("record_num"));
				}
				getAllBibsRS.close();
				getAllBibsStmt.close();
			}catch (SQLException e){
				logEntry.incErrors("Error loading all records: " , e);
			}
		}else{
			//Add a 120-second buffer to the extract
			Date lastExtractDate = new Date((lastSierraExtractTime - 120) * 1000);

			dateTimeFormatter.setTimeZone(TimeZone.getTimeZone("UTC"));
			String lastExtractDateTimeFormatted = dateTimeFormatter.format(lastExtractDate);
			dateFormatter.setTimeZone(TimeZone.getTimeZone("UTC"));
			String lastExtractDateFormatted = dateFormatter.format(lastExtractDate);
			logger.info("Loading records changed since " + lastExtractDateTimeFormatted);

			processDeletedBibs(sierraInstanceInformation, lastExtractDateFormatted);
			getNewRecordsFromAPI(sierraInstanceInformation, lastExtractDateTimeFormatted);
			getChangedRecordsFromAPI(sierraInstanceInformation, lastExtractDateTimeFormatted);
			getNewItemsFromAPI(sierraInstanceInformation, lastExtractDateTimeFormatted);
			getChangedItemsFromAPI(sierraInstanceInformation, lastExtractDateTimeFormatted);
			getDeletedItemsFromAPI(sierraInstanceInformation, lastExtractDateFormatted);
			getMarcHoldingsToReindex();
		}
	}

	private static int updateBibs(SierraInstanceInformation sierraInstanceInformation) {
		//This section uses the batch method which doesn't work in Sierra because we are limited to 100 exports per hour
		if (allBibsToUpdate.isEmpty()){
			return 0;
		}
		logEntry.addNote("Found " + allBibsToUpdate.size() + " bib records that need to be updated with data from Sierra.");
		int batchSize = 25;
		int numProcessed = 0;

		boolean hasMoreIdsToProcess = true;
		while (hasMoreIdsToProcess) {
			hasMoreIdsToProcess = false;
			int maxIndex = Math.min(allBibsToUpdate.size(), batchSize);
			ArrayList<String> ids = new ArrayList<>();
			for (int i = 0; i < maxIndex; i++) {
				String lastId = allBibsToUpdate.last();
				ids.add(lastId);
				allBibsToUpdate.remove(lastId);
			}
			updateMarcAndRegroupRecordIds(sierraInstanceInformation, ids);

			numProcessed += maxIndex;
			if (numProcessed % 250 == 0 || allBibsToUpdate.isEmpty()){
				logEntry.saveResults();
			}
			if (!allBibsToUpdate.isEmpty()) {
				hasMoreIdsToProcess = true;
			}
		}

		return numProcessed;
	}

	private static void getBibsWithHoldings(Connection sierraConn) {
		bibsWithHoldings.clear();
		try {
			PreparedStatement bibHoldingsStmt = sierraConn.prepareStatement("select distinct(record_num) as record_num from sierra_view.bib_record_holding_record_link INNER JOIN sierra_view.record_metadata ON bib_record_id = record_metadata.id where record_type_code = 'b'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldingsRS = bibHoldingsStmt.executeQuery();
			while (bibHoldingsRS.next()){
				String bibId = bibHoldingsRS.getString("record_num");
				//Don't need the .b and checksum for this
				bibsWithHoldings.put(bibId, ".b" + bibId + getCheckDigit(bibId));
			}
			bibHoldingsRS.close();
		} catch (Exception e) {
			logger.error("Unable to get bibs with holdings from Sierra", e);
		}

		logEntry.addNote("Finished getting bibs with holdings " + dateTimeFormatter.format(new Date()));
	}

	private static void exportHolds(Connection sierraConn, Connection dbConn) {
		Savepoint startOfHolds = null;
		try {
			logEntry.addNote("Starting export of holds " + dateTimeFormatter.format(new Date()));

			//Start a transaction so we can rebuild an entire table
			startOfHolds = dbConn.setSavepoint();
			dbConn.setAutoCommit(false);
			dbConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeUpdate();

			PreparedStatement addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
			HashMap<String, Long> numHoldsByVolume = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, record_type_code, record_num from sierra_view.hold left join sierra_view.record_metadata on hold.record_id = record_metadata.id where record_type_code = 'b' and (status = '0' OR status = 't') GROUP BY record_type_code, record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()){
				String bibId = bibHoldsRS.getString("record_num");
				bibId = ".b" + bibId + getCheckDigit(bibId);
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

			//Export item level holds
			PreparedStatement itemHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, record_num\n" +
					"from sierra_view.hold \n" +
					"inner join sierra_view.bib_record_item_record_link ON hold.record_id = item_record_id \n" +
					"inner join sierra_view.record_metadata on bib_record_item_record_link.bib_record_id = record_metadata.id \n" +
					"WHERE status = '0' OR status = 't' " +
					"group by record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet itemHoldsRS = itemHoldsStmt.executeQuery();
			while (itemHoldsRS.next()) {
				String bibId = itemHoldsRS.getString("record_num");
				bibId = ".b" + bibId + getCheckDigit(bibId);
				Long numHolds = itemHoldsRS.getLong("numHolds");
				if (numHoldsByBib.containsKey(bibId)) {
					numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
				} else {
					numHoldsByBib.put(bibId, numHolds);
				}
			}
			itemHoldsRS.close();

			//Export volume level holds
			PreparedStatement volumeHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, bib_metadata.record_num as bib_num, volume_metadata.record_num as volume_num\n" +
					"from sierra_view.hold \n" +
					"inner join sierra_view.bib_record_volume_record_link ON hold.record_id = volume_record_id \n" +
					"inner join sierra_view.record_metadata as volume_metadata on bib_record_volume_record_link.volume_record_id = volume_metadata.id \n" +
					"inner join sierra_view.record_metadata as bib_metadata on bib_record_volume_record_link.bib_record_id = bib_metadata.id \n" +
					"WHERE status = '0' OR status = 't'\n" +
					"GROUP BY bib_metadata.record_num, volume_metadata.record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet volumeHoldsRS = volumeHoldsStmt.executeQuery();
			while (volumeHoldsRS.next()) {
				String bibId = volumeHoldsRS.getString("bib_num");
				bibId = ".b" + bibId + getCheckDigit(bibId);
				String volumeId = volumeHoldsRS.getString("volume_num");
				volumeId = ".j" + volumeId + getCheckDigit(volumeId);
				Long numHolds = volumeHoldsRS.getLong("numHolds");
				//Do not count these in
				if (numHoldsByBib.containsKey(bibId)) {
					numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
				} else {
					numHoldsByBib.put(bibId, numHolds);
				}
				if (numHoldsByVolume.containsKey(volumeId)) {
					numHoldsByVolume.put(volumeId, numHolds + numHoldsByVolume.get(bibId));
				} else {
					numHoldsByVolume.put(volumeId, numHolds);
				}
			}
			volumeHoldsRS.close();


			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			for (String volumeId : numHoldsByVolume.keySet()){
				addIlsHoldSummary.setString(1, volumeId);
				addIlsHoldSummary.setLong(2, numHoldsByVolume.get(volumeId));
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
			logger.error("Unable to export holds from Sierra", e);
			if (startOfHolds != null) {
				try {
					dbConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logEntry.addNote("Finished exporting holds " + dateTimeFormatter.format(new Date()));
	}



	private static void processDeletedBibs(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process deleted records since " + lastExtractDateFormatted);

		int bufferSize = 250;
		boolean hasMoreRecords = true;
		long offset = 0;
		while (hasMoreRecords){
			hasMoreRecords = false;
			String url = apiBaseUrl + "/bibs/?deletedDate=[" + lastExtractDateFormatted + ",]&fields=id&deleted=true&limit=" + bufferSize;
			if (offset > 0){
				url += "&offset=" + offset;
			}
			JSONObject deletedRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);

			if (deletedRecords != null) {
				try {
					JSONArray entries = deletedRecords.getJSONArray("entries");
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curBib = entries.getJSONObject(i);
						String id = curBib.getString("id");
						id = ".b" + id + getCheckDigit(id);
						if (allDeletedIds.add(id)){
							removeRecordFromGroupedWork(indexingProfile.getName(), id);
						}
					}
					//If nothing has been deleted, iii provides entries, but not a total
					if (deletedRecords.has("total") && deletedRecords.getLong("total") >= bufferSize){
						offset += deletedRecords.getLong("total");
						hasMoreRecords = true;
					}
				}catch (Exception e){
					logger.error("Error processing deleted bibs", e);
				}
			}
			logEntry.saveResults();
		}

		if (!allDeletedIds.isEmpty()){
			logEntry.addNote("Finished processing deleted records, deleted " + logEntry.getNumDeleted());
		}else{
			logEntry.addNote("No deleted records found");
		}
	}

	//TODO: Move to record grouping or another shared location
	/**
	 * Removes a record from a grouped work and returns if the grouped work no longer has
	 * any records attached to it (in which case it should be removed from the index after calling this)
	 *
	 * @param source - The source of the record being removed
	 * @param id - The id of the record being removed
	 */
	private static void removeRecordFromGroupedWork(String source, String id) {
		try {
			//Check to see if the identifier is in the grouped work primary identifiers table
			RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(source, id);
			if (result.reindexWork) {
				getGroupedWorkIndexer().processGroupedWork(result.permanentId);
			} else if (result.deleteWork) {
				//Delete the work from solr and the database
				getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
			}
			logEntry.incDeleted();
		} catch (Exception e) {
			logger.error("Error removing record from grouped work", e);
		}
	}

	private static void getChangedRecordsFromAPI(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process records changed since " + lastExtractDateFormatted);
		int bufferSize = 1000;
		boolean hasMoreRecords = true;
		int numChangedRecords = 0;
		int numSuppressedRecords = 0;
		int recordOffset = 50000;
		long firstRecordIdToLoad = 1;
		while (hasMoreRecords) {
			hasMoreRecords = false;
			String url = apiBaseUrl + "/bibs/?updatedDate=[" + lastExtractDateFormatted + ",]&deleted=false&fields=id,suppressed&limit=" + bufferSize;
			if (firstRecordIdToLoad > 1){
				url += "&id=[" + firstRecordIdToLoad + ",]";
			}
			JSONObject createdRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);
			if (createdRecords != null){
				try {
					JSONArray entries = createdRecords.getJSONArray("entries");
					int lastId = 0;
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curBib = entries.getJSONObject(i);
						boolean isSuppressed = false;
						if (curBib.has("suppressed")){
							isSuppressed = curBib.getBoolean("suppressed");
						}
						lastId = curBib.getInt("id");
						if (isSuppressed){
							String id = curBib.getString("id");
							allDeletedIds.add(id);
							id = ".b" + id + getCheckDigit(id);
							removeRecordFromGroupedWork(indexingProfile.getName(), id);
							numSuppressedRecords++;
						}else {
							allBibsToUpdate.add(curBib.getString("id"));
							numChangedRecords++;
						}
					}
					if (createdRecords.getLong("total") >= bufferSize){
						hasMoreRecords = true;
					}
					if (entries.length() >= bufferSize){
						firstRecordIdToLoad = lastId + 1;
					}else{
						firstRecordIdToLoad += recordOffset;
					}
					//Get the grouped work id for the new bib
				}catch (Exception e){
					logger.error("Error processing changed bibs", e);
				}
			}else{
				logEntry.addNote("No changed records found");
			}
		}
		logEntry.addNote("Finished processing changed records, there were " + numChangedRecords + " changed records and " + numSuppressedRecords + " suppressed records");
	}

	private static void getNewRecordsFromAPI(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process records created since " + lastExtractDateFormatted);
		int bufferSize = 1000;
		boolean hasMoreRecords = true;
		long offset = 0;
		int numNewRecords = 0;
		int numSuppressedRecords = 0;

		while (hasMoreRecords) {
			hasMoreRecords = false;
			String url = apiBaseUrl + "/bibs/?createdDate=[" + lastExtractDateFormatted + ",]&deleted=false&fields=id,suppressed&limit=" + bufferSize;
			if (offset > 0){
				url += "&offset=" + offset;
			}
			JSONObject createdRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);
			if (createdRecords != null){
				try {
					JSONArray entries = createdRecords.getJSONArray("entries");
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curBib = entries.getJSONObject(i);
						boolean isSuppressed = false;
						if (curBib.has("suppressed")){
							isSuppressed = curBib.getBoolean("suppressed");
						}
						if (isSuppressed){
							String id = curBib.getString("id");
							allDeletedIds.add(id);
							id = ".b" + id + getCheckDigit(id);
							removeRecordFromGroupedWork(indexingProfile.getName(), id);
							numSuppressedRecords++;
						}else {
							allBibsToUpdate.add(curBib.getString("id"));
							numNewRecords++;
						}
					}
					if (createdRecords.getLong("total") >= bufferSize){
						offset += createdRecords.getLong("total");
						hasMoreRecords = true;
					}
					//Get the grouped work id for the new bib
				}catch (Exception e){
					logger.error("Error processing newly created bibs", e);
				}
			}else{
				logEntry.addNote("No newly created records found");
			}
		}
		logEntry.addNote("Finished processing newly created records " + numNewRecords + " were new and " + numSuppressedRecords + " were suppressed");
	}

	private static void getNewItemsFromAPI(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process items created since " + lastExtractDateFormatted);
		int bufferSize = 1000;
		boolean hasMoreRecords = true;
		long offset = 0;
		int numNewRecords = 0;
		while (hasMoreRecords) {
			hasMoreRecords = false;
			String url = apiBaseUrl + "/items/?createdDate=[" + lastExtractDateFormatted + ",]&deleted=false&fields=id,bibIds&limit=" + bufferSize;
			if (offset > 0){
				url += "&offset=" + offset;
			}
			JSONObject createdRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);
			if (createdRecords != null){
				try {
					JSONArray entries = createdRecords.getJSONArray("entries");
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curBib = entries.getJSONObject(i);
						JSONArray bibIds = curBib.getJSONArray("bibIds");
						for (int j = 0; j < bibIds.length(); j++){
							String id = bibIds.getString(j);
							if (!allDeletedIds.contains(id)) {
								allBibsToUpdate.add(id);
							}
							numNewRecords++;
						}
					}
					if (createdRecords.getLong("total") >= bufferSize){
						offset += createdRecords.getLong("total");
						hasMoreRecords = true;
					}
					//Get the grouped work id for the new bib
				}catch (Exception e){
					logger.error("Error processing newly created items", e);
				}
			}else{
				logEntry.addNote("No newly created items found");
			}
		}
		logEntry.addNote("Finished processing newly created items " + numNewRecords);
	}

	private static void getMarcHoldingsToReindex() {
		//Because MARC holdings do not have dates updated, we will just make sure that they get reindexed at least once a day, but try to do that after hours.
		Calendar rightNow = Calendar.getInstance();
		int hour = rightNow.get(Calendar.HOUR_OF_DAY);
		Calendar yesterday = Calendar.getInstance();
		yesterday.add(Calendar.DAY_OF_MONTH, -1);
		long yesterdayTime = yesterday.getTimeInMillis() / 1000;
		if (hour == 21){
			try {
				PreparedStatement getLastModifiedForBibStmt = dbConn.prepareStatement("SELECT lastModified from ils_records where source = ? and ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				for (String bibWithHoldingsShort : bibsWithHoldings.keySet()) {
					String bibWithHoldings = bibsWithHoldings.get(bibWithHoldingsShort);
					getLastModifiedForBibStmt.setString(1, indexingProfile.getName());
					getLastModifiedForBibStmt.setString(2, bibWithHoldings);
					ResultSet getLastModifiedForBibRS = getLastModifiedForBibStmt.executeQuery();
					if (getLastModifiedForBibRS.next()) {
						long lastModifiedDate = getLastModifiedForBibRS.getLong("lastModified");
						if (lastModifiedDate < yesterdayTime) {
							allBibsToUpdate.add(bibWithHoldingsShort);
						}
					}else{
						//We haven't seen this before make sure it updates
						allBibsToUpdate.add(bibWithHoldingsShort);
					}
					getLastModifiedForBibRS.close();
				}
			} catch (SQLException e) {
				throw new RuntimeException(e);
			}
		}
	}

	private static void getChangedItemsFromAPI(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process items updated since " + lastExtractDateFormatted);
		int bufferSize = 1000;
		boolean hasMoreRecords = true;
		int numChangedItems = 0;
		int numNewBibs = 0;
		long firstRecordIdToLoad = 1;
		int recordOffset = 50000;
		while (hasMoreRecords) {
			hasMoreRecords = false;
			String url = apiBaseUrl + "/items/?updatedDate=[" + lastExtractDateFormatted + ",]&deleted=false&fields=id,bibIds&limit=" + bufferSize;
			if (firstRecordIdToLoad > 1){
				url += "&id=[" + firstRecordIdToLoad + ",]";
			}
			JSONObject createdRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);
			if (createdRecords != null){
				try {
					JSONArray entries = createdRecords.getJSONArray("entries");
					int lastId = 0;
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curItem = entries.getJSONObject(i);
						lastId = curItem.getInt("id");
						if (curItem.has("bibIds")) {
							JSONArray bibIds = curItem.getJSONArray("bibIds");
							for (int j = 0; j < bibIds.length(); j++) {
								String id = bibIds.getString(j);
								if (!allDeletedIds.contains(id) && !allBibsToUpdate.contains(id)) {
									allBibsToUpdate.add(id);
									numNewBibs++;
								}
								numChangedItems++;
							}
						}
					}
					if (createdRecords.getLong("total") >= bufferSize){
						hasMoreRecords = true;
					}
					if (entries.length() >= bufferSize){
						firstRecordIdToLoad = lastId + 1;
					}else{
						firstRecordIdToLoad += recordOffset;
					}
					//Get the grouped work id for the new bib
				}catch (Exception e){
					logger.error("Error processing updated items", e);
				}
			}else{
				logEntry.addNote("No updated items found");
			}
		}
		logEntry.addNote("Finished processing updated items " + numChangedItems + " this added " + numNewBibs + " bibs to process");
	}

	private static void getDeletedItemsFromAPI(SierraInstanceInformation sierraInstanceInformation, String lastExtractDateFormatted) {
		//Get a list of deleted bibs
		logEntry.addNote("Starting to process items deleted since " + lastExtractDateFormatted);
		int bufferSize = 1000;
		boolean hasMoreRecords = true;
		long offset = 0;
		int numDeletedItems = 0;
		while (hasMoreRecords) {
			hasMoreRecords = false;
			String url = apiBaseUrl + "/items/?deletedDate=[" + lastExtractDateFormatted + ",]&deleted=true&fields=id,bibIds&limit=" + bufferSize;
			if (offset > 0){
				url += "&offset=" + offset;
			}
			JSONObject deletedRecords = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, url, false, false);
			if (deletedRecords != null){
				try {
					JSONArray entries = deletedRecords.getJSONArray("entries");
					for (int i = 0; i < entries.length(); i++) {
						JSONObject curBib = entries.getJSONObject(i);
						JSONArray bibIds = curBib.getJSONArray("bibIds");
						for (int j = 0; j < bibIds.length(); j++){
							String id = bibIds.getString(j);
							if (!allDeletedIds.contains(id)) {
								allBibsToUpdate.add(id);
							}
						}
					}
					if (deletedRecords.getLong("total") >= bufferSize){
						offset += deletedRecords.getLong("total");
						hasMoreRecords = true;
					}
					//Get the grouped work id for the new bib
				}catch (Exception e){
					logger.error("Error processing deleted items", e);
				}
			}else{
				logEntry.addNote("No deleted items found");
			}
		}
		logEntry.addNote("Finished processing deleted items found " + numDeletedItems);
	}

	private static final MarcFactory marcFactory = MarcFactory.newInstance();
	private static boolean updateMarcAndRegroupRecordId(SierraInstanceInformation sierraInstanceInformation, String id) {
		final JSONObject[] bibsResults = {null};
		//noinspection CodeBlock2Expr
		Thread getBibsResultsThread = new Thread(() -> {
			bibsResults[0] = getMarcJSONFromSierraApiURL(sierraInstanceInformation, apiBaseUrl, apiBaseUrl + "/bibs?id=" + id + "&fields=marc,fixedFields,locations", id);
		});
		final JSONObject[] itemIds = {null};
		//noinspection CodeBlock2Expr
		Thread itemUpdateThread = new Thread(() -> {
			itemIds[0] = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, apiBaseUrl + "/items?limit=1000&deleted=false&suppressed=false&fields=default,itemType,fixedFields,varFields&bibIds=" + id, false, true);
		});
		final JSONObject[] holdingIds = {null};
		boolean hasHoldings = bibsWithHoldings.containsKey(id);
		Thread holdingsUpdateThread = null;
		if (hasHoldings) {
			//noinspection CodeBlock2Expr
			holdingsUpdateThread = new Thread(() -> {
				holdingIds[0] = callSierraApiURL(sierraInstanceInformation, apiBaseUrl, apiBaseUrl + "/holdings?limit=1000&deleted=false&suppressed=false&fields=id,fixedFields,varFields&bibIds=" + id, true, false);
			});
		}
		getBibsResultsThread.start();
		itemUpdateThread.start();
		if (hasHoldings) {
			holdingsUpdateThread.start();
		}
		try {
			getBibsResultsThread.join();
			itemUpdateThread.join();
			if (hasHoldings) {
				holdingsUpdateThread.join();
			}
		}catch (InterruptedException e){
			logEntry.incErrors("Loading data from Sierra was interrupted", e);
		}
		try {
			if (bibsResults[0] != null){
				if (bibsResults[0].has("httpStatus")){
					if (bibsResults[0].getInt("code") == 107) {
						//This record was deleted
						String longId = ".b" + id + getCheckDigit(id);
						logger.debug("id " + longId + " was deleted");
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), longId);
						getGroupedWorkIndexer().markIlsRecordAsDeleted(indexingProfile.getName(), longId);
						if (result.reindexWork) {
							getGroupedWorkIndexer().processGroupedWork(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
						}
						logEntry.incDeleted();
						return true;
					} else if (bibsResults[0].getInt("httpStatus") == 500){
						//This record can't be loaded
						logEntry.incRecordsWithInvalidMarc("Record " + id + " could not be fetched from the API");
						return true;
					} else {
						logEntry.incErrors("Unknown error " + bibsResults[0]);
						return false;
					}
				}

				JSONObject bibsData = bibsResults[0].getJSONArray("entries").getJSONObject(0);
				
				//Get Marc data from bibs response
				if (!bibsData.has("marc")) {
					logEntry.incInvalidRecords("Record " + id + " has no MARC data");
					return true;
				}
				JSONObject marcData = bibsData.getJSONObject("marc");
				String leader = "";
				if (marcData.has("leader")) {
					leader = marcData.getString("leader");
					if (leader.isEmpty()){
						logEntry.incRecordsWithInvalidMarc("Record " + id + " has empty MARC leader");
						return true;
					}
				} else {
					logEntry.incRecordsWithInvalidMarc("Record " + id + " has no valid MARC leader");
					return true;
				}
				Record marcRecord = marcFactory.newRecord(leader);

				if (marcData.has("fields")) {
					JSONArray fields = marcData.getJSONArray("fields");
					if (fields.length() == 0) {
						logEntry.incRecordsWithInvalidMarc("Record " + id + " has empty MARC fields");
						return true;
					}
					for (int i = 0; i < fields.length(); i++){
						JSONObject fieldData = fields.getJSONObject(i);
						Iterator<String> tags = fieldData.keys();
						while (tags.hasNext()){
							String tag = tags.next();
							if (!tag.equals(indexingProfile.getItemTag())) {
								if (fieldData.get(tag) instanceof JSONObject) {
									JSONObject fieldDataDetails = fieldData.getJSONObject(tag);
									char ind1 = fieldDataDetails.getString("ind1").charAt(0);
									char ind2 = fieldDataDetails.getString("ind2").charAt(0);
									DataField dataField = marcFactory.newDataField(tag, ind1, ind2);
									JSONArray subfields = fieldDataDetails.getJSONArray("subfields");
									for (int j = 0; j < subfields.length(); j++) {
										JSONObject subfieldData = subfields.getJSONObject(j);
										String subfieldIndicatorStr = subfieldData.keys().next();
										char subfieldIndicator = subfieldIndicatorStr.charAt(0);
										String subfieldValue = subfieldData.getString(subfieldIndicatorStr);
										dataField.addSubfield(marcFactory.newSubfield(subfieldIndicator, subfieldValue));
									}
									if (tag.equals(indexingProfile.getRecordNumberTag())) {
										Subfield recordNumberSubfield = dataField.getSubfield(indexingProfile.getRecordNumberSubfield());
										if (recordNumberSubfield == null) {
											continue;
										} else {
											if (!recordNumberSubfield.getData().startsWith(".b")) {
												continue;
											} else if (recordNumberSubfield.getData().equals(".b")) {
												continue;
											}
										}
									} else if (tag.equals(sierraExportFieldMapping.getFixedFieldDestinationField())) {
										continue;
									}
									marcRecord.addVariableField(dataField);
								} else {
									String fieldValue = fieldData.getString(tag);
									marcRecord.addVariableField(marcFactory.newControlField(tag, fieldValue));
								}
							}
						}
					}
				} else {
					logEntry.incRecordsWithInvalidMarc("Record " + id + " has no valid MARC fields");
					return true;
				}
				logger.debug("Converted JSON to MARC for Bib");

				//Add the identifier
				marcRecord.addVariableField(marcFactory.newDataField(indexingProfile.getRecordNumberTag(), ' ', ' ',  "a", ".b" + id + getCheckDigit(id)));

				//Get Fixed Fields data from bibs response
				if (bibsData.has("fixedFields")) {
					JSONObject bibsFixedFields = bibsData.getJSONObject("fixedFields");
					if (bibsFixedFields.isEmpty()) {
						logEntry.incRecordsWithInvalidMarc("Record " + id + " has empty fixed fields");
						return true;
					}
					if (!sierraExportFieldMapping.getFixedFieldDestinationField().isEmpty()) {
						DataField fixedDataField = marcFactory.newDataField(sierraExportFieldMapping.getFixedFieldDestinationField(), ' ', ' ');
						if (sierraExportFieldMapping.getBcode3DestinationSubfield() != ' ') {
							String bCode3 = bibsFixedFields.getJSONObject("31").getString("value");
							fixedDataField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getBcode3DestinationSubfield(), bCode3));
						}
						if (sierraExportFieldMapping.getMaterialTypeSubfield() != ' ') {
							String matType = bibsFixedFields.getJSONObject("30").getString("value");
							fixedDataField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getMaterialTypeSubfield(), matType));
						}
						if (sierraExportFieldMapping.getBibLevelLocationsSubfield() != ' ') {
							if (bibsFixedFields.has("26")) {
								String location = bibsFixedFields.getJSONObject("26").getString("value");
								if (location.equalsIgnoreCase("multi")) {
									if (bibsData.has("locations")) {
										JSONArray locationsJSON = bibsData.getJSONArray("locations");
										for (int k = 0; k < locationsJSON.length(); k++) {
											location = locationsJSON.getJSONObject(k).getString("code");
											fixedDataField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getBibLevelLocationsSubfield(), location));
										}
									}	
								} else {
									fixedDataField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getBibLevelLocationsSubfield(), location));
								}
							}
						}
						marcRecord.addVariableField(fixedDataField);
					}
				} else {
					logEntry.incRecordsWithInvalidMarc("Record " + id + " has no valid fixed fields");
					return true;
				}

				//Get Holdings for the bib record
				if (hasHoldings && holdingIds[0] != null) {
					JSONObject holdingsData = holdingIds[0];
					if (holdingsData.getInt("total") > 0) {
						JSONArray holdings = holdingsData.getJSONArray("entries");
						for (int i = 0; i < holdings.length(); i++) {
							JSONObject curHolding = holdings.getJSONObject(i);
							int holdingId = curHolding.getInt("id");
							//We need the label to show as well as the location
							//Label is in the varFields
							JSONArray varFields = curHolding.getJSONArray("varFields");
							for (int j = 0; j < varFields.length(); j++) {
								JSONObject curVarField = varFields.getJSONObject(j);
								if (curVarField.has("marcTag")) {
									if (curVarField.has("subfields")) {
										DataField holdingField = marcFactory.newDataField(curVarField.getString("marcTag"), curVarField.getString("ind1").charAt(0), curVarField.getString("ind2").charAt(0));
										marcRecord.addVariableField(holdingField);
										JSONArray subfields = curVarField.getJSONArray("subfields");
										for (int k = 0; k < subfields.length(); k++) {
											JSONObject subfield = subfields.getJSONObject(k);
											holdingField.addSubfield(marcFactory.newSubfield(subfield.getString("tag").charAt(0), subfield.getString("content")));
										}
										holdingField.addSubfield(marcFactory.newSubfield('6', Integer.toString(holdingId)));
									}
								}else if (curVarField.has("fieldTag") && curVarField.get("fieldTag").equals("h")) {
									DataField holdingField = marcFactory.newDataField("866", ' ', ' ');
									marcRecord.addVariableField(holdingField);
									if (curVarField.has("content")) {
										holdingField.addSubfield(marcFactory.newSubfield('a', curVarField.getString("content")));
									}else if (curVarField.has("subfields")) {
										JSONArray subfields = curVarField.getJSONArray("subfields");
										for (int k = 0; k < subfields.length(); k++) {
											JSONObject subfield = subfields.getJSONObject(k);
											holdingField.addSubfield(marcFactory.newSubfield(subfield.getString("tag").charAt(0), subfield.getString("content")));
										}
									}
									holdingField.addSubfield(marcFactory.newSubfield('6', Integer.toString(holdingId)));
								}
							}
							//Location is in the fixed fields
							JSONObject fixedFields = curHolding.getJSONObject("fixedFields");
							if (fixedFields.has("40")) {
								DataField holdingField = marcFactory.newDataField("852", ' ', ' ');
								marcRecord.addVariableField(holdingField);
								holdingField.addSubfield(marcFactory.newSubfield('b', fixedFields.getJSONObject("40").getString("value")));
								holdingField.addSubfield(marcFactory.newSubfield('c', fixedFields.getJSONObject("40").getString("value")));
								holdingField.addSubfield(marcFactory.newSubfield('6', Integer.toString(holdingId)));
							}

						}
					}
					//getItemsForBib(id, marcRecord, itemIds[0]);
					logger.debug("Processed holdings for Bib");
				}

				//Get Items for the bib record
				if (itemIds[0] != null) {
					getItemsForBib(id, marcRecord, itemIds[0]);
					logger.debug("Processed items for Bib");
				}

				RecordIdentifier identifier = getRecordGroupingProcessor().getPrimaryIdentifierFromMarcRecord(marcRecord, indexingProfile);
				//noinspection unused
				GroupedWorkIndexer.MarcStatus marcStatus = getGroupedWorkIndexer().saveMarcRecordToDatabase(indexingProfile, identifier.getIdentifier(), marcRecord);

				//Set up the grouped work for the record.  This will take care of either adding it to the proper grouped work
				//or creating a new grouped work
				String groupedWorkId = groupSierraRecord(marcRecord);
				if (groupSierraRecord(marcRecord) == null) {
					logger.warn(identifier.getIdentifier() + " was suppressed");
				}else{
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}
			}else{
				//Log this as an invalid record, so we can continue, but return true, so we don't log an error.
				logEntry.incRecordsWithInvalidMarc("Record " + id + " could not be fetched from the API, could not connect to API");
				return true;
			}
		}catch (Exception e){
			logEntry.incErrors("Error in updateMarcAndRegroupRecordId processing bib " + id + " from Sierra API", e);
			return false;
		}
		return true;
	}


	private static final SimpleDateFormat sierraAPIDateFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
	private static void getItemsForBib(String id, Record marcRecord, JSONObject itemIds) {
		//Get a list of all items
		long startTime = new Date().getTime();
		//This will return a 404 error if all items are suppressed or if the record has not items

		try {
			if (itemIds.has("code")){
				if (itemIds.getInt("code") != 404){
					logger.error("Error getting information about items " + itemIds);
				}
			}else{
				JSONArray entries = itemIds.getJSONArray("entries");
				logger.debug("finished getting items for " + id + " elapsed time " + (new Date().getTime() - startTime) + "ms found " + entries.length());
				for (int i = 0; i < entries.length(); i++) {
					JSONObject curItem = entries.getJSONObject(i);
					JSONObject fixedFields = curItem.getJSONObject("fixedFields");
					JSONArray varFields = curItem.getJSONArray("varFields");
					String itemId = curItem.getString("id");
					DataField itemField = marcFactory.newDataField(indexingProfile.getItemTag(), ' ', ' ');
					//Record Number
					if (indexingProfile.getItemRecordNumberSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemRecordNumberSubfield(), ".i" + itemId + getCheckDigit(itemId)));
					}
					//barcode
					if (curItem.has("barcode")){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getBarcodeSubfield(), curItem.getString("barcode")));
					}
					//location
					if (curItem.has("location") && indexingProfile.getLocationSubfield() != ' '){
						String locationCode = curItem.getJSONObject("location").getString("code");
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getLocationSubfield(), locationCode));
					}
					//status
					if (curItem.has("status")){
						String statusCode = curItem.getJSONObject("status").getString("code");
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemStatusSubfield(), statusCode));
						if (curItem.getJSONObject("status").has("duedate")){
							Date createdDate = sierraAPIDateFormatter.parse(curItem.getJSONObject("status").getString("duedate"));
							itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDueDateSubfield(), indexingProfile.getDueDateFormatter().format(createdDate)));
						}else{
							itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDueDateSubfield(), ""));
						}
					}else{
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDueDateSubfield(), ""));
					}
					//total checkouts
					if (fixedFields.has("76") && indexingProfile.getTotalCheckoutsSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getTotalCheckoutsSubfield(), fixedFields.getJSONObject("76").getString("value")));
					}
					//last year checkouts
					if (fixedFields.has("110") && indexingProfile.getLastYearCheckoutsSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getLastYearCheckoutsSubfield(), fixedFields.getJSONObject("110").getString("value")));
					}
					//year to date checkouts
					if (fixedFields.has("109") && indexingProfile.getYearToDateCheckoutsSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getYearToDateCheckoutsSubfield(), fixedFields.getJSONObject("109").getString("value")));
					}
					//total renewals
					if (fixedFields.has("77") && indexingProfile.getTotalRenewalsSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getTotalRenewalsSubfield(), fixedFields.getJSONObject("77").getString("value")));
					}
					//iType
					if (fixedFields.has("61") && indexingProfile.getITypeSubfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getITypeSubfield(), fixedFields.getJSONObject("61").getString("value")));
					}
					//date created
					if (curItem.has("createdDate") && indexingProfile.getDateCreatedSubfield() != ' '){
						Date createdDate = sierraAPIDateFormatter.parse(curItem.getString("createdDate"));
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDateCreatedSubfield(), indexingProfile.getDateCreatedFormatter().format(createdDate)));
					}
					//last check in date
					if (fixedFields.has("68") && indexingProfile.getLastCheckinDateSubfield() != ' '){
						String lastCheckInDate = fixedFields.getJSONObject("68").getString("value");
						Date lastCheckin = sierraAPIDateFormatter.parse(lastCheckInDate);
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getLastCheckinDateSubfield(), indexingProfile.getLastCheckinFormatter().format(lastCheckin)));
					}
					//icode2
					if (fixedFields.has("60") && indexingProfile.getICode2Subfield() != ' '){
						itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getICode2Subfield(), fixedFields.getJSONObject("60").getString("value")));
					}
					//OPAC note
					if (fixedFields.has("108") && indexingProfile.getNoteSubfield() != ' '){
						String noteValue = fixedFields.getJSONObject("108").getString("value").trim();
						if (!noteValue.isEmpty() && !noteValue.equals("-")) {
							itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getNoteSubfield(), noteValue));
						}
					}
					//Replacement Cost
					if (fixedFields.has("62") && indexingProfile.getReplacementCostSubfield() != ' '){
						String replacementCost = fixedFields.getJSONObject("62").getString("value").trim();
						if (!replacementCost.isEmpty() && !replacementCost.equals("-")) {
							try{
								Double replacementCostValue = Double.parseDouble(replacementCost) / 100;
								itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getReplacementCostSubfield(), replacementCostValue.toString()));
							}catch (NumberFormatException e) {
								//Just ignore this
							}
						}
					}

					//Process variable fields
					for (int j = 0; j < varFields.length(); j++){
						JSONObject curVarField = varFields.getJSONObject(j);
						String fieldTag = curVarField.getString("fieldTag");
						StringBuilder allFieldContent = new StringBuilder();
						JSONArray subfields = null;
						String marcTag = "";
						if (curVarField.has("marcTag")) {
							marcTag = curVarField.getString("marcTag");
						}
						if (marcTag.equals("856")) {
							if (curVarField.has("subfields")) {
								subfields = curVarField.getJSONArray("subfields");
								for (int k = 0; k < subfields.length(); k++) {
									JSONObject subfield = subfields.getJSONObject(k);
									char tag = AspenStringUtils.convertStringToChar(subfield.getString("tag"));
									String content = subfield.getString("content");
									if (tag == 'u') {
										itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemUrl(), content));
									} else if (tag == 'z') {
										itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemUrlDescription(), content));
									}
								}
							}
						} else {
							if (curVarField.has("subfields")) {
								subfields = curVarField.getJSONArray("subfields");
								for (int k = 0; k < subfields.length(); k++) {
									JSONObject subfield = subfields.getJSONObject(k);
									allFieldContent.append(subfield.getString("content"));
								}
							} else {
								allFieldContent.append(curVarField.getString("content"));
							}

							if (fieldTag.equals(sierraExportFieldMapping.getCallNumberExportFieldTag())) {
								if (subfields != null) {
									for (int k = 0; k < subfields.length(); k++) {
										JSONObject subfield = subfields.getJSONObject(k);
										char tag = AspenStringUtils.convertStringToChar(subfield.getString("tag"));
										String content = subfield.getString("content");
										if (tag == sierraExportFieldMapping.getCallNumberPrestampExportSubfield()) {
											itemField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getCallNumberPrestampExportSubfield(), content));
										} else if (tag == sierraExportFieldMapping.getCallNumberPrestamp2ExportSubfield()) {
											itemField.addSubfield(marcFactory.newSubfield(sierraExportFieldMapping.getCallNumberPrestamp2ExportSubfield(), content));
										} else if (tag == sierraExportFieldMapping.getCallNumberExportSubfield()) {
											itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberSubfield(), content));
										} else if (tag == sierraExportFieldMapping.getCallNumberCutterExportSubfield()) {
											itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberCutterSubfield(), content));
										} else if (tag == sierraExportFieldMapping.getCallNumberPoststampExportSubfield()) {
											itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberPoststampSubfield(), content));
											//}else{
											//logger.debug("Unhandled call number subfield " + tag);
										}
									}
								} else {
									String content = curVarField.getString("content");
									itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberSubfield(), content));
								}
							} else if (fieldTag.equals(sierraExportFieldMapping.getVolumeExportFieldTag())) {
								itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getVolume(), allFieldContent.toString()));
							} else if (fieldTag.equals(sierraExportFieldMapping.getEContentExportFieldTag())) {
								itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getEContentDescriptor(), allFieldContent.toString()));
							} else if (fieldTag.equals(sierraExportFieldMapping.getItemPublicNoteExportSubfield())) {
								itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getNoteSubfield(), allFieldContent.toString()));
								//}else{
								//logger.debug("Unhandled item variable field " + fieldTag);
							}
						}
					}
					marcRecord.addVariableField(itemField);
				}
			}
		}catch (Exception e){
			logger.error("Error getting information about items", e);
		}
	}

	private static void updateMarcAndRegroupRecordIds(SierraInstanceInformation sierraInstanceInformation, ArrayList<String> idArray) {
		try {
			for (String id : idArray) {
				logger.debug("starting to process " + id);
				if (!updateMarcAndRegroupRecordId(sierraInstanceInformation, id)){
					//Don't fail the entire process.  We will just reprocess next time the export runs
					logEntry.incErrors("Processing " + id + " failed");
					//allPass = false;
				}else{
					logEntry.incUpdated();
				}
			}
			logger.debug("finished processing " + idArray.size() + " records with the slow method");
		}catch (Exception e){
			logger.error("Error processing newly created bibs", e);
		}
	}

	private static void exportActiveOrders(String exportPath, Connection conn) throws SQLException, IOException {
		logEntry.addNote("Starting export of active orders " + dateTimeFormatter.format(new Date()));
		//Load the orders we had last time
		File orderRecordFile = new File(exportPath + "/active_orders.csv");
		HashMap<String, Integer> existingBibsWithOrders = new HashMap<>();
		readOrdersFile(orderRecordFile, existingBibsWithOrders);

		String[] orderStatusesToExportValues = indexingProfile.getOrderRecordsStatusesToInclude().split("\\|");
		StringBuilder orderStatusCodesSQL = new StringBuilder();
		for (String orderStatusesToExportVal : orderStatusesToExportValues){
			if (orderStatusCodesSQL.length() > 0){
				orderStatusCodesSQL.append(" or ");
			}
			orderStatusCodesSQL.append(" order_status_code = '").append(orderStatusesToExportVal).append("'");
		}
		String activeOrderSQL = "select bib_view.record_num as bib_record_num, order_view.record_num as order_record_num, accounting_unit_code_num, order_status_code, copies, location_code, catalog_date_gmt, received_date_gmt " +
				"from sierra_view.order_view " +
				"inner join sierra_view.bib_record_order_record_link on bib_record_order_record_link.order_record_id = order_view.record_id " +
				"inner join sierra_view.bib_view on sierra_view.bib_view.id = bib_record_order_record_link.bib_record_id " +
				"inner join sierra_view.order_record_cmf on order_record_cmf.order_record_id = order_view.id " +
				"where (" + orderStatusCodesSQL + ") and order_view.is_suppressed = 'f' and location_code != 'multi' and ocode4 != 'n'";
		if (indexingProfile.getOrderRecordsToSuppressByDate() == 4){
			activeOrderSQL += " and (catalog_date_gmt IS NULL and received_date_gmt IS NULL) ";
		}else if (indexingProfile.getOrderRecordsToSuppressByDate() == 2){
			activeOrderSQL += " and (catalog_date_gmt IS NULL) ";
		}else if (indexingProfile.getOrderRecordsToSuppressByDate() == 3){
			activeOrderSQL += " and (received_date_gmt IS NULL) ";
		}
		PreparedStatement getActiveOrdersStmt = conn.prepareStatement(activeOrderSQL, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet activeOrdersRS = null;
		boolean loadError = false;
		try{
			activeOrdersRS = getActiveOrdersStmt.executeQuery();
		} catch (SQLException e1){
			logger.error("Error loading active orders", e1);
			loadError = true;
		}
		if (!loadError){
			CSVWriter orderRecordWriter = new CSVWriter(new FileWriter(orderRecordFile));
			orderRecordWriter.writeAll(activeOrdersRS, true);
			orderRecordWriter.close();
			activeOrdersRS.close();

			HashMap<String, Integer> updatedBibsWithOrders = new HashMap<>();
			readOrdersFile(orderRecordFile, updatedBibsWithOrders);

			//Check to see which bibs either have new or deleted orders
			for (String bibId : updatedBibsWithOrders.keySet()){
				if (!existingBibsWithOrders.containsKey(bibId)){
					//We didn't have a bib with an order before, update it
					allBibsToUpdate.add(bibId);
				}else{
					if (!updatedBibsWithOrders.get(bibId).equals(existingBibsWithOrders.get(bibId))){
						//Number of orders has changed, we should reindex.
						allBibsToUpdate.add(bibId);
					}
					existingBibsWithOrders.remove(bibId);
				}
			}
			//Now that all updated bibs are processed, look for any that we used to have that no longer exist
			allBibsToUpdate.addAll(existingBibsWithOrders.keySet());
		}
		logEntry.addNote("Finished exporting active orders " + dateTimeFormatter.format(new Date()));
	}

	private static void readOrdersFile(File orderRecordFile, HashMap<String, Integer> bibsWithOrders) throws IOException {
		if (orderRecordFile.exists()){
			CSVReader orderReader = new CSVReader(new FileReader(orderRecordFile));
			//Skip the header
			orderReader.readNext();
			String[] recordData = orderReader.readNext();
			while (recordData != null){
				if (bibsWithOrders.containsKey(recordData[0])){
					bibsWithOrders.put(recordData[0], bibsWithOrders.get(recordData[0]) + 1);
				}else{
					bibsWithOrders.put(recordData[0], 1);
				}

				recordData = orderReader.readNext();
			}
			orderReader.close();
		}
	}

	private static String sierraAPIToken;
	private static String sierraAPITokenType;
	private static long sierraAPIExpiration;
	private static boolean connectToSierraAPI(SierraInstanceInformation sierraInstanceInformation, String baseUrl){
		//Check to see if we already have a valid token
		if (sierraAPIToken != null){
			//Give this a buffer of 60 seconds to be sure the next call completes in time
			if (sierraAPIExpiration - new Date().getTime() > 60000){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has expired");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		String getTokenUrl = baseUrl + "/token";
		try {
			URL emptyIndexURL = new URL(getTokenUrl);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier((hostname, session) -> {
					//Do not verify host names
					return true;
				});
			}
			conn.setReadTimeout(30000);
			conn.setConnectTimeout(30000);
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			String clientKey = sierraInstanceInformation.clientKey;
			String clientSecret = sierraInstanceInformation.clientSecret;
			String encoded = Base64.encodeBase64String((clientKey + ":" + clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), StandardCharsets.UTF_8);
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				try {
					JSONObject parser = new JSONObject(response.toString());
					sierraAPIToken = parser.getString("access_token");
					sierraAPITokenType = parser.getString("token_type");
					//logger.debug("Token expires in " + parser.getLong("expires_in") + " seconds");
					sierraAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
					//logger.debug("Sierra token is " + sierraAPIToken);
				}catch (JSONException jse){
					logger.error("Error parsing response to json " + response, jse);
					return false;
				}

			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to sierra authentication service" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response\r\n" + response);

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to sierra API - " + getTokenUrl, e );
			return false;
		}
		return true;
	}

	private static JSONObject callSierraApiURL(SierraInstanceInformation sierraInstanceInformation, String baseUrl, String sierraUrl, boolean logErrors, boolean ignore404errors) {
		if (connectToSierraAPI(sierraInstanceInformation, baseUrl)){
			//Connect to the API to get our token
			HttpURLConnection conn;
			try {
				URL emptyIndexURL = new URL(sierraUrl);
				conn = (HttpURLConnection) emptyIndexURL.openConnection();
				if (conn instanceof HttpsURLConnection){
					HttpsURLConnection sslConn = (HttpsURLConnection)conn;
					sslConn.setHostnameVerifier((hostname, session) -> {
						//Do not verify host names
						return true;
					});
				}
				conn.setRequestMethod("GET");
				conn.setRequestProperty("Accept-Charset", "UTF-8");
				conn.setRequestProperty("Authorization", sierraAPITokenType + " " + sierraAPIToken);
				conn.setReadTimeout(30000);
				conn.setConnectTimeout(5000);

				StringBuilder response = new StringBuilder();
				if (conn.getResponseCode() == 200) {
					// Get the response
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.debug("  Finished reading response");
					rd.close();
					try{
						return new JSONObject(response.toString());
					}catch (JSONException jse){
						logger.error("Error parsing response \n" + response, jse);
						return null;
					}

				} else {
					if (conn.getResponseCode() == 404 && ignore404errors) {
						return null;
					}
					//Check to see if we failed due to the grant being invalid.
					logger.error("Received error " + conn.getResponseCode() + " calling sierra API " + sierraUrl);
					// Get any errors
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream(), StandardCharsets.UTF_8));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					try{
						JSONObject errorResponse = new JSONObject(response.toString());
						if (errorResponse.has("httpStatus") && errorResponse.has("description") && errorResponse.getInt("httpStatus") == 401 && errorResponse.getString("description").equals("invalid_grant")){
							sierraAPIToken = null;
							sierraAPITokenType = null;
							//pause for a minute to let the api come back
							logEntry.addNote("Reconnecting to the Sierra API");
							try {
								Thread.sleep(60000);
							} catch (InterruptedException e) {
								logger.error("Sleep was interrupted", e);
							}
							return callSierraApiURL(sierraInstanceInformation, baseUrl, sierraUrl, logErrors, ignore404errors);
						}
					}catch (JSONException jse){
						logger.error("Error parsing response \n" + response, jse);
					}
					if (logErrors) {
						logger.error("  Finished reading response");
						logger.error(response.toString());
					}
					rd.close();
				}

			} catch (java.net.SocketTimeoutException e) {
				logEntry.incErrors("Socket timeout talking to to sierra API (callSierraApiURL) " + sierraUrl, e);
			} catch (java.net.ConnectException e) {
				logEntry.incErrors("Timeout connecting to sierra API (callSierraApiURL) " + sierraUrl, e );
			} catch (Exception e) {
				logEntry.incErrors("Error loading data from sierra API (callSierraApiURL) " + sierraUrl , e );
			}
		}
		return null;
	}

	private static JSONObject getMarcJSONFromSierraApiURL(SierraInstanceInformation sierraInstanceInformation, String baseUrl, String sierraUrl, String id) {
		if (connectToSierraAPI(sierraInstanceInformation, baseUrl)){
			//Connect to the API to get our token
			HttpURLConnection conn;
			try {
				URL emptyIndexURL = new URL(sierraUrl);
				conn = (HttpURLConnection) emptyIndexURL.openConnection();
				if (conn instanceof HttpsURLConnection){
					HttpsURLConnection sslConn = (HttpsURLConnection)conn;
					sslConn.setHostnameVerifier((hostname, session) -> {
						//Do not verify host names
						return true;
					});
				}
				conn.setRequestMethod("GET");
				conn.setRequestProperty("Accept-Charset", "UTF-8");
				conn.setRequestProperty("Authorization", sierraAPITokenType + " " + sierraAPIToken);
				conn.setRequestProperty("Accept", "application/marc-in-json");
				conn.setReadTimeout(20000);
				conn.setConnectTimeout(5000);

				StringBuilder response = new StringBuilder();
				if (conn.getResponseCode() == 200) {
					// Get the response
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.debug("  Finished reading response");
					rd.close();
					try {
						return new JSONObject(response.toString());
					} catch (Exception e) {
						logEntry.incRecordsWithInvalidMarc("Title had invalid JSON " + id);
					}
				} else {
					// Get any errors
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream(), StandardCharsets.UTF_8));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}

					rd.close();

					try{
						return new JSONObject(response.toString());
					}catch (JSONException jse){
						logger.error("Received error " + conn.getResponseCode() + " calling sierra API " + sierraUrl);
						logger.error(response.toString());
					}
				}

			} catch (java.net.SocketTimeoutException e) {
				logger.error("Socket timeout talking to to sierra API (getMarcJSONFromSierraApiURL) " + e );
			} catch (java.net.ConnectException e) {
				logger.error("Timeout connecting to sierra API (getMarcJSONFromSierraApiURL) " + e );
			} catch (Exception e) {
				logger.error("Error loading data from sierra API (getMarcJSONFromSierraApiURL) ", e );
			}
		}else {
			logger.error("Could not connect to api when fetching " + sierraUrl);
		}
		return null;
	}

	private static void exportVolumes(Connection sierraConn, Connection aspenConn){
		try {
			logEntry.addNote("Starting export of volume information " + dateTimeFormatter.format(new Date()));

			//Get the existing volumes
			PreparedStatement getExistingVolumes = aspenConn.prepareStatement("SELECT volumeId from ils_volume_info", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			HashSet<String> existingVolumes = new HashSet<>();
			ResultSet existingVolumesRS = getExistingVolumes.executeQuery();
			while (existingVolumesRS.next()){
				existingVolumes.add(existingVolumesRS.getString("volumeId"));
			}
			existingVolumesRS.close();

			//This is a little inefficient since we have to convert short ids to long ids.
			//Get a list of all the values and store them in memory to minimize the number of times we need to call Sierra
			PreparedStatement getVolumeInfoStmt = sierraConn.prepareStatement("select volume_view.id, volume_view.record_num as volume_num, sort_order from sierra_view.volume_view where volume_view.is_suppressed = 'f'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getBibForVolumeStmt = sierraConn.prepareStatement("select record_num, volume_record_id from sierra_view.bib_record_volume_record_link " +
					"inner join sierra_view.bib_view on bib_record_volume_record_link.bib_record_id = bib_view.id " +
					"where volume_record_id IN (select id from sierra_view.volume_view where volume_view.is_suppressed = 'f')", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getItemsForVolumeStmt = sierraConn.prepareStatement("select record_num, volume_record_id from sierra_view.item_view " +
					"inner join sierra_view.volume_record_item_record_link on volume_record_item_record_link.item_record_id = item_view.id " +
					"where volume_record_id IN (select id from sierra_view.volume_view where volume_view.is_suppressed = 'f')", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getVolumeNameStmt = sierraConn.prepareStatement("SELECT content, record_id FROM sierra_view.subfield where field_type_code = 'v' and record_id in (select id from sierra_view.volume_view where sierra_view.volume_view.is_suppressed = 'f');", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			PreparedStatement addVolumeStmt = aspenConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems) VALUES (?,?,?,?) ON DUPLICATE KEY update recordId = VALUES(recordId), displayLabel = VALUES(displayLabel), relatedItems = VALUES(relatedItems)");
			PreparedStatement deleteVolumeStmt = aspenConn.prepareStatement("DELETE from ils_volume_info where volumeId = ?");

			ResultSet volumeInfoRS = null;
			boolean loadError = false;

			HashMap<Long, String> bibsForVolume = new HashMap<>(); //Volume ID to Bib
			HashMap<Long, String> itemsForVolume = new HashMap<>(); //Volume ID, list of item
			HashMap<Long, String> labelsForVolume = new HashMap<>();

			try {
				volumeInfoRS = getVolumeInfoStmt.executeQuery();

				ResultSet getBibForVolumeRS = getBibForVolumeStmt.executeQuery();
				while (getBibForVolumeRS.next()){
					String bibRecordNum = getBibForVolumeRS.getString("record_num");
					bibRecordNum = ".b" + bibRecordNum + getCheckDigit(bibRecordNum);
					bibsForVolume.put(getBibForVolumeRS.getLong("volume_record_id"), bibRecordNum);
				}
				getBibForVolumeRS.close();

				ResultSet getItemsForVolumeRS = getItemsForVolumeStmt.executeQuery();
				while (getItemsForVolumeRS.next()){
					String itemRecordNum = getItemsForVolumeRS.getString("record_num");

					Long volumeId = getItemsForVolumeRS.getLong("volume_record_id");
					String existingItems = itemsForVolume.get(volumeId);
					if (existingItems == null){
						itemsForVolume.put(volumeId, ".i" + itemRecordNum + getCheckDigit(itemRecordNum));
					}else{
						itemsForVolume.put(volumeId, existingItems + "|" + itemRecordNum + getCheckDigit(itemRecordNum));
					}
				}
				getItemsForVolumeRS.close();

				ResultSet getVolumeLabelsRS = getVolumeNameStmt.executeQuery();
				while (getVolumeLabelsRS.next()) {
					labelsForVolume.put(getVolumeLabelsRS.getLong("record_id"), getVolumeLabelsRS.getString("content"));
				}
				getVolumeLabelsRS.close();
			} catch (SQLException e1) {
				logEntry.incErrors("Error loading volume information", e1);
				loadError = true;
			}
			if (!loadError) {
				int numVolumesUpdated = 0;
				while (volumeInfoRS.next()) {
					long recordId = volumeInfoRS.getLong("id");

					String volumeId = volumeInfoRS.getString("volume_num");
					volumeId = ".j" + volumeId + getCheckDigit(volumeId);

					existingVolumes.remove(volumeId);

					try {
						String relatedItems = itemsForVolume.get(recordId);
						if (relatedItems == null){
							relatedItems = "";
						}
						addVolumeStmt.setString(1, "ils:" + bibsForVolume.get(recordId));
						addVolumeStmt.setString(2, volumeId);
						addVolumeStmt.setString(3, labelsForVolume.get(recordId));
						addVolumeStmt.setString(4, relatedItems);
						int numUpdates = addVolumeStmt.executeUpdate();
						if (numUpdates > 0) {
							numVolumesUpdated++;
						}
					}catch (SQLException sqlException){
						logEntry.incErrors("Error adding volume", sqlException);
					}
				}
				volumeInfoRS.close();

				//Remove anything that no longer exists
				long numVolumesDeleted = 0;
				for (String existingVolume : existingVolumes){
					logEntry.addNote("Deleted volume " + existingVolume);
					deleteVolumeStmt.setString(1, existingVolume);
					deleteVolumeStmt.executeUpdate();
					numVolumesDeleted++;
				}
				logEntry.addNote("Updated " + numVolumesUpdated + " volumes and deleted " + numVolumesDeleted + " volumes");
			}

			logEntry.addNote("Finished export of volume information " + dateTimeFormatter.format(new Date()));
		}catch (Exception e){
			logEntry.incErrors("Error exporting volume information", e);
		}
		logEntry.saveResults();
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

	private static SierraInstanceInformation initializeSierraConnection(Connection dbConn) throws SQLException {
		//Get information about the account profile for koha
		PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = 'sierra'");
		ResultSet accountProfileRS = accountProfileStmt.executeQuery();
		SierraInstanceInformation sierraInstanceInformation = null;
		if (accountProfileRS.next()) {
			try {
				String host = accountProfileRS.getString("databaseHost");
				String port = accountProfileRS.getString("databasePort");
				if (port == null || port.isEmpty()) {
					port = "1032";
				}
				String databaseName = accountProfileRS.getString("databaseName");
				String user = accountProfileRS.getString("databaseUser");
				String password = accountProfileRS.getString("databasePassword");

				String sierraConnectionJDBC = "jdbc:postgresql://" +
						host + ":" + port +
						"/" + databaseName +
						"?user=" + user +
						"&password=" + password +
						"&ssl=true&sslfactory=org.postgresql.ssl.NonValidatingFactory";

				sierraInstanceInformation = new SierraInstanceInformation();
				sierraInstanceInformation.indexingProfileName = accountProfileRS.getString("recordSource");
				Connection sierraConn = connectToSierraDatabase(sierraConnectionJDBC);
				if (sierraConn != null) {
					sierraInstanceInformation.sierraConnection = sierraConn;
				}
				sierraInstanceInformation.clientKey = accountProfileRS.getString("oAuthClientId");
				sierraInstanceInformation.clientSecret = accountProfileRS.getString("oAuthClientSecret");
				sierraInstanceInformation.apiVersion = accountProfileRS.getString("apiVersion");
				sierraInstanceInformation.apiBaseUrl = accountProfileRS.getString("vendorOpacUrl");
			} catch (Exception e) {
				logger.error("Error connecting to sierra database ", e);
			}
		} else {
			logger.error("Could not find an account profile for Sierra stopping");
			System.exit(1);
		}
		return sierraInstanceInformation;
	}

	private static Connection connectToSierraDatabase(String sierraConnectionJDBC) {
		int tries = 0;
		while (tries < 3) {
			try {
				return DriverManager.getConnection(sierraConnectionJDBC);
			} catch (Exception e) {
				tries++;
				logger.error("Could not connect to the sierra database, try " + tries);
				try {
					Thread.sleep(15000);
				} catch (InterruptedException ex) {
					logger.debug("Thread was interrupted");
				}
				if (tries == 3){
					logEntry.incErrors("Could not connect to the sierra database",e);
				}
			}

		}
		return null;
	}

	private static String groupSierraRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null, getGroupedWorkIndexer());
	}

	private static MarcRecordGrouper getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}
}