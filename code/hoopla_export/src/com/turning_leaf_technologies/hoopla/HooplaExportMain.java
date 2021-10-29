package com.turning_leaf_technologies.hoopla;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.marc4j.marc.Record;

import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.Date;
import java.util.HashMap;
import java.util.zip.CRC32;

public class HooplaExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static HooplaExtractLogEntry logEntry;
	private static String hooplaAPIBaseURL;

	private static Connection aspenConn;
	private static PreparedStatement getAllExistingHooplaItemsStmt;
	private static PreparedStatement addHooplaTitleToDB = null;
	private static PreparedStatement updateHooplaTitleInDB = null;
	private static PreparedStatement deleteHooplaItemStmt;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//Existing records
	private static HashMap<Long, HooplaTitle> existingRecords = new HashMap<>();

	//For Checksums
	private static CRC32 checksumCalculator = new CRC32();

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

		String processName = "hoopla_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");

		while (true) {
			//Hoopla only needs to run once a day so just run it in cron
			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime.toString() + ": Starting Hoopla Export");

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the Aspen database
			aspenConn = connectToDatabase();

			//Start a log entry
			createDbLogEntry(startTime, aspenConn);
			logEntry.addNote("Starting extract");
			logEntry.saveResults();

			//Get a list of all existing records in the database
			loadExistingTitles();

			//Do work here
			exportHooplaData();
			int numChanges = logEntry.getNumChanges();

			processRecordsToReload(logEntry);

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
				existingRecords = null;
			}

			if (logEntry.hasErrors()) {
				logger.error("There were errors during the export!");
			}

			logger.info("Finished exporting data " + new Date().toString());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			//Mark that indexing has finished
			logEntry.setFinished();

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}

			disconnectDatabase(aspenConn);

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
		}

	}

	private static void processRecordsToReload(HooplaExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='hoopla'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordId = getRecordsToReloadRS.getString("identifier");
				long hooplaId = Long.parseLong(recordId.replace("MWT", ""));
				//Regroup the record
				getItemDetailsForRecordStmt.setLong(1, hooplaId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String groupedWorkId = groupRecord(itemDetails, hooplaId);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + hooplaId, e);
					}
				}else{
					//The record has likely been deleted
					logEntry.addNote("Could not get details for record to reload " + hooplaId + " it has been deleted");
					markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
					markRecordToReloadAsProcessedStmt.executeUpdate();
					numRecordsToReloadProcessed++;
				}
				getItemDetailsForRecordRS.close();
			}
			if (numRecordsToReloadProcessed > 0){
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static void deleteItems() {
		int numDeleted = 0;
		try {
			for (HooplaTitle hooplaTitle : existingRecords.values()) {
				if (!hooplaTitle.isFoundInExport() && hooplaTitle.isActive()) {
					deleteHooplaItemStmt.setLong(1, hooplaTitle.getId());
					deleteHooplaItemStmt.executeUpdate();
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", Long.toString(hooplaTitle.getHooplaId()));
					if (result.reindexWork){
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					}else if (result.deleteWork){
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old titles");
			}
		}catch (SQLException e) {
			logger.error("Error deleting items", e);
			logEntry.addNote("Error deleting items " + e.toString());
		}
	}

	private static void loadExistingTitles() {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			ResultSet allRecordsRS = getAllExistingHooplaItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				long hooplaId = allRecordsRS.getLong("hooplaId");
				HooplaTitle newTitle = new HooplaTitle(
						allRecordsRS.getLong("id"),
						hooplaId,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("active"),
						allRecordsRS.getLong("rawResponseLength")
				);
				existingRecords.put(hooplaId, newTitle);
			}
			allRecordsRS.close();
			//noinspection UnusedAssignment
			allRecordsRS = null;
			getAllExistingHooplaItemsStmt.close();
			getAllExistingHooplaItemsStmt = null;
		} catch (SQLException e) {
			logger.error("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e.toString());
			System.exit(-1);
		}
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from hoopla_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		logEntry = new HooplaExtractLogEntry(aspenConn, logger);
	}

	private static void exportHooplaData() {
		try{
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from hoopla_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				hooplaAPIBaseURL = getSettingsRS.getString("apiUrl");
				String apiUsername = getSettingsRS.getString("apiUsername");
				String apiPassword = getSettingsRS.getString("apiPassword");
				String hooplaLibraryId = getSettingsRS.getString("libraryId");
				long lastUpdateOfChangedRecords = getSettingsRS.getLong("lastUpdateOfChangedRecords");
				long lastUpdateOfAllRecords = getSettingsRS.getLong("lastUpdateOfAllRecords");
				long lastUpdate = Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
				boolean isRegroupAllRecords = getSettingsRS.getBoolean("regroupAllRecords");
				boolean doFullReload = getSettingsRS.getBoolean("runFullUpdate");
				long settingsId = getSettingsRS.getLong("id");
				if (doFullReload){
					//Unset that a full update needs to be done
					PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set runFullUpdate = 0 where id = ?");
					updateSettingsStmt.setLong(1, settingsId);
					updateSettingsStmt.executeUpdate();
				}

				if (isRegroupAllRecords) {
					regroupAllRecords(aspenConn, settingsId, getGroupedWorkIndexer(), logEntry);
				}

				String accessToken = getAccessToken(apiUsername, apiPassword);
				if (accessToken == null) {
					logEntry.incErrors("Could not load access token");
					return;
				}

				//Formulate the first call depending on if we are doing a full reload or not
				String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content";
				if (!doFullReload && lastUpdate > 0) {
					logEntry.addNote("Extracting records since " + new Date(lastUpdate * 1000).toString());
					url += "?startTime=" + lastUpdate;
				}

				HashMap<String, String> headers = new HashMap<>();
				headers.put("Authorization", "Bearer " + accessToken);
				headers.put("Content-Type", "application/json");
				headers.put("Accept", "application/json");
				WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
				if (!response.isSuccess()){
					logEntry.incErrors("Could not get titles from " + url + " " + response.getMessage());
				}else {
					JSONObject responseJSON = new JSONObject(response.getMessage());
					if (responseJSON.has("titles")) {
						JSONArray responseTitles = responseJSON.getJSONArray("titles");
						if (responseTitles != null && responseTitles.length() > 0) {
							updateTitlesInDB(responseTitles, doFullReload);
							logEntry.saveResults();
						}

						String startToken = null;
						if (responseJSON.has("nextStartToken")) {
							startToken = responseJSON.get("nextStartToken").toString();
						}

						int numTries = 0;
						while (startToken != null) {
							url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content?startToken=" + startToken;
							if (!doFullReload && lastUpdate > 0) {
								url += "&startTime=" + lastUpdate;
							}
							response = NetworkUtils.getURL(url, logger, headers);
							if (response.isSuccess()){
								responseJSON = new JSONObject(response.getMessage());
								if (responseJSON.has("titles")) {
									responseTitles = responseJSON.getJSONArray("titles");
									if (responseTitles != null && responseTitles.length() > 0) {
										updateTitlesInDB(responseTitles, doFullReload);
									}
								}
								if (responseJSON.has("nextStartToken")) {
									startToken = responseJSON.get("nextStartToken").toString();
								} else {
									startToken = null;
								}
							}else{
								if (response.getResponseCode() == 401 || response.getResponseCode() == 504){
									numTries++;
									if (numTries >= 3){
										logEntry.incErrors("Error loading data from " + url + " " + response.getResponseCode() + " " + response.getMessage());
										startToken = null;
									}else{
										accessToken = getAccessToken(apiUsername, apiPassword);
										headers.put("Authorization", "Bearer " + accessToken);
									}
								}else {
									logEntry.incErrors("Error loading data from " + url + " " + response.getResponseCode() + " " + response.getMessage());
									startToken = null;
								}
							}

							logEntry.saveResults();
						}
					}
				}

				if (doFullReload){
					deleteItems();
				}

				//Set the extract time
				if (doFullReload){
					PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfAllRecords = ? where id = ?");
					updateSettingsStmt.setLong(1, startTimeForLogging);
					updateSettingsStmt.setLong(2, settingsId);
					updateSettingsStmt.executeUpdate();
				}else{
					PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfChangedRecords = ? where id = ?");
					updateSettingsStmt.setLong(1, startTimeForLogging);
					updateSettingsStmt.setLong(2, settingsId);
					updateSettingsStmt.executeUpdate();
				}
			}
			if (numSettings == 0){
				logger.error("Unable to find settings for Hoopla, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting hoopla data", e);
		}
	}


	private static void updateTitlesInDB(JSONArray responseTitles, boolean doFullReload) {
		logEntry.incNumProducts(responseTitles.length());
		for (int i = 0; i < responseTitles.length(); i++){
			try {
				JSONObject curTitle = responseTitles.getJSONObject(i);

				String rawResponse = curTitle.toString();
				checksumCalculator.reset();
				checksumCalculator.update(rawResponse.getBytes());
				long rawChecksum = checksumCalculator.getValue();
				boolean curTitleActive = curTitle.getBoolean("active");

				long hooplaId = curTitle.getLong("titleId");

				HooplaTitle existingTitle = existingRecords.get(hooplaId);
				boolean recordUpdated = false;
				if (existingTitle != null) {
					//Record exists
					if ((existingTitle.getChecksum() != rawChecksum) || (existingTitle.getRawResponseLength() != rawResponse.length())){
						recordUpdated = true;
						logEntry.incUpdated();
						if (existingTitle.isActive() != curTitleActive) {
							if (curTitleActive) {
								logEntry.incAdded();
							} else {
								logEntry.incDeleted();
							}
						}else{
							logEntry.incUpdated();
						}
					}
					existingTitle.setFoundInExport(true);
				}else{
					if (!curTitleActive){
						logEntry.incSkipped();
						continue;
					}
					recordUpdated = true;
					logEntry.incAdded();
				}

				if (!curTitleActive){
					//Title is currently active (and if we got this far exists, delete it)
					//Delete the record if it exists
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", Long.toString(hooplaId));
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId);
					}
					logEntry.incDeleted();
					deleteHooplaItemStmt.setLong(1, existingTitle.getId());
					deleteHooplaItemStmt.executeUpdate();
				}else {
					if (existingTitle == null){
						addHooplaTitleToDB.setLong(1, hooplaId);
						addHooplaTitleToDB.setBoolean(2, true);
						addHooplaTitleToDB.setString(3, curTitle.getString("title"));
						addHooplaTitleToDB.setString(4, curTitle.getString("kind"));
						addHooplaTitleToDB.setBoolean(5, curTitle.getBoolean("pa"));
						addHooplaTitleToDB.setBoolean(6, curTitle.getBoolean("demo"));
						addHooplaTitleToDB.setBoolean(7, curTitle.getBoolean("profanity"));
						addHooplaTitleToDB.setString(8, curTitle.has("rating") ? curTitle.getString("rating") : "");
						addHooplaTitleToDB.setBoolean(9, curTitle.getBoolean("abridged"));
						addHooplaTitleToDB.setBoolean(10, curTitle.getBoolean("children"));
						addHooplaTitleToDB.setDouble(11, curTitle.getDouble("price"));
						addHooplaTitleToDB.setLong(12, rawChecksum);
						addHooplaTitleToDB.setString(13, rawResponse);
						addHooplaTitleToDB.setLong(14, startTimeForLogging);
						try {
							addHooplaTitleToDB.executeUpdate();

							String groupedWorkId = groupRecord(curTitle, hooplaId);
							indexRecord(groupedWorkId);
						}catch (DataTruncation e) {
							logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e.toString());
						}catch (SQLException e){
							logEntry.incErrors("Error adding hoopla title to database record " + hooplaId + " " + curTitle.getString("title"), e);
						}
					}else if (recordUpdated || doFullReload){
						updateHooplaTitleInDB.setBoolean(1, true);
						updateHooplaTitleInDB.setString(2, curTitle.getString("title"));
						updateHooplaTitleInDB.setString(3, curTitle.getString("kind"));
						updateHooplaTitleInDB.setBoolean(4, curTitle.getBoolean("pa"));
						updateHooplaTitleInDB.setBoolean(5, curTitle.getBoolean("demo"));
						updateHooplaTitleInDB.setBoolean(6, curTitle.getBoolean("profanity"));
						updateHooplaTitleInDB.setString(7, curTitle.has("rating") ? curTitle.getString("rating") : "");
						updateHooplaTitleInDB.setBoolean(8, curTitle.getBoolean("abridged"));
						updateHooplaTitleInDB.setBoolean(9, curTitle.getBoolean("children"));
						updateHooplaTitleInDB.setDouble(10, curTitle.getDouble("price"));
						updateHooplaTitleInDB.setLong(11, rawChecksum);
						updateHooplaTitleInDB.setString(12, rawResponse);
						updateHooplaTitleInDB.setLong(13, existingTitle.getId());
						try {
							updateHooplaTitleInDB.executeUpdate();

							String groupedWorkId = groupRecord(curTitle, hooplaId);
							indexRecord(groupedWorkId);
						}catch (DataTruncation e) {
							logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e.toString());
						}catch (SQLException e){
							logEntry.incErrors("Error updating hoopla data in database for record " + hooplaId + " " + curTitle.getString("title"), e);
						}
					}
				}
			}catch (Exception e){
				logEntry.incErrors("Error updating hoopla data", e);
			}
		}

	}

	private static void indexRecord(String groupedWorkId) {
		getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
	}

	private static String groupRecord(JSONObject itemDetails, long hooplaId) throws JSONException {
		//Perform record grouping on the record
		String title;
		String subTitle;
		if (itemDetails.has("titleTitle")){
			title = itemDetails.getString("titleTitle");
			subTitle = itemDetails.getString("title");
		}else {
			title = itemDetails.getString("title");
			if (itemDetails.has("subtitle")){
				subTitle = itemDetails.getString("subtitle");
			}else{
				subTitle = "";
			}
		}
		String mediaType = itemDetails.getString("kind");
		String primaryFormat;
		switch (mediaType) {
			case "MOVIE":
			case "TELEVISION":
				primaryFormat = "eVideo";
				break;
			case "AUDIOBOOK":
				primaryFormat = "eAudiobook";
				break;
			case "EBOOK":
				primaryFormat = "eBook";
				break;
			case "COMIC":
				primaryFormat = "eComic";
				break;
			case "MUSIC":
				primaryFormat = "eMusic";
				break;
			default:
				logger.error("Unhandled hoopla mediaType " + mediaType);
				primaryFormat = mediaType;
				break;
		}
		String author = "";
		if (itemDetails.has("artist")) {
			author = itemDetails.getString("artist");
			author = StringUtils.swapFirstLastNames(author);
		} else if (itemDetails.has("publisher")) {
			author = itemDetails.getString("publisher");
		}

		RecordIdentifier primaryIdentifier = new RecordIdentifier("hoopla", Long.toString(hooplaId));

		return getRecordGroupingProcessor().processRecord(primaryIdentifier, title, subTitle, author, primaryFormat, true);
	}

	private static String getAccessToken(String username, String password) {
		if (username == null || password == null){
			logger.error("Please set HooplaAPIUser and HooplaAPIPassword in settings");
			logEntry.addNote("Please set HooplaAPIUser and HooplaAPIPassword in settings");
			return null;
		}
		String getTokenUrl = hooplaAPIBaseURL + "/v2/token";
		WebServiceResponse response = NetworkUtils.postToURL(getTokenUrl, null, "application/json", null, logger, username + ":" + password);
		if (response.isSuccess()){
			try {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				return responseJSON.getString("access_token");
			} catch (JSONException e) {
				logEntry.addNote("Could not parse JSON for token " + response.getMessage());
				logger.error("Could not parse JSON for token " + response.getMessage(), e);
				return null;
			}
		}else{
			logEntry.addNote("Please set HooplaAPIUser and HooplaAPIPassword in settings");
			return null;
		}
	}

	private static Connection connectToDatabase(){
		Connection aspenConn = null;
		try{
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);
				getAllExistingHooplaItemsStmt = aspenConn.prepareStatement("SELECT id, hooplaId, rawChecksum, active, UNCOMPRESSED_LENGTH(rawResponse) as rawResponseLength from hoopla_export");
				addHooplaTitleToDB = aspenConn.prepareStatement("INSERT INTO hoopla_export (hooplaId, active, title, kind, pa, demo, profanity, rating, abridged, children, price, rawChecksum, rawResponse, dateFirstDetected) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,COMPRESS(?),?) ");
				updateHooplaTitleInDB = aspenConn.prepareStatement("UPDATE hoopla_export set active = ?, title = ?, kind = ?, pa = ?, demo = ?, profanity = ?, " +
						"rating = ?, abridged = ?, children = ?, price = ?, rawChecksum = ?, rawResponse = COMPRESS(?) where id = ?");
				deleteHooplaItemStmt = aspenConn.prepareStatement("DELETE FROM hoopla_export where id = ?");
			}else{
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}
		}catch (Exception e){
			logger.error("Error connecting to Aspen database " + e.toString());
			System.exit(1);
		}
		return aspenConn;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try{
			addHooplaTitleToDB.close();
			addHooplaTitleToDB = null;
			updateHooplaTitleInDB.close();
			updateHooplaTitleInDB = null;
			deleteHooplaItemStmt.close();
			deleteHooplaItemStmt = null;
			aspenConn.close();
			//noinspection UnusedAssignment
			aspenConn = null;
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static RecordGroupingProcessor getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static void regroupAllRecords(Connection dbConn, long settingsId, GroupedWorkIndexer indexer, HooplaExtractLogEntry logEntry)  throws SQLException {
		logEntry.addNote("Starting to regroup all records");
		PreparedStatement getAllRecordsToRegroupStmt = dbConn.prepareStatement("SELECT hooplaId, UNCOMPRESS(rawResponse) as rawResponse from hoopla_export where active = 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		//It turns out to be quite slow to look this up repeatedly, just grab the existing values for all and store in memory
		PreparedStatement getOriginalPermanentIdForRecordStmt = dbConn.prepareStatement("SELECT identifier, permanent_id from grouped_work_primary_identifiers join grouped_work on grouped_work_id = grouped_work.id WHERE type = 'hoopla'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		HashMap<Long, String> allPermanentIdsForHoopla = new HashMap<>();
		ResultSet getOriginalPermanentIdForRecordRS = getOriginalPermanentIdForRecordStmt.executeQuery();
		while (getOriginalPermanentIdForRecordRS.next()){
			allPermanentIdsForHoopla.put(getOriginalPermanentIdForRecordRS.getLong("identifier"), getOriginalPermanentIdForRecordRS.getString("permanent_id"));
		}
		getOriginalPermanentIdForRecordRS.close();
		getOriginalPermanentIdForRecordStmt.close();
		ResultSet allRecordsToRegroupRS = getAllRecordsToRegroupStmt.executeQuery();
		while (allRecordsToRegroupRS.next()) {
			logEntry.incRecordsRegrouped();
			long recordIdentifier = allRecordsToRegroupRS.getLong("hooplaId");
			String originalGroupedWorkId;
			originalGroupedWorkId = allPermanentIdsForHoopla.get(recordIdentifier);
			if (originalGroupedWorkId == null){
				originalGroupedWorkId = "false";
			}
			String rawResponseString = new String(allRecordsToRegroupRS.getBytes("rawResponse"), StandardCharsets.UTF_8);
			JSONObject rawResponse = new JSONObject(rawResponseString);
			//Pass null to processMarcRecord.  It will do the lookup to see if there is an existing id there.
			String groupedWorkId = groupRecord(rawResponse, recordIdentifier);
			if (!originalGroupedWorkId.equals(groupedWorkId)) {
				logEntry.incChangedAfterGrouping();
			}
		}

		//Process all the records to reload which will handle reindexing anything that just changed
		if (logEntry.getNumChangedAfterGrouping() > 0){
			indexer.processScheduledWorks(logEntry);
		}

		try {
			PreparedStatement clearRegroupAllRecordsStmt = dbConn.prepareStatement("UPDATE hoopla_settings set regroupAllRecords = 0 where id =?");
			clearRegroupAllRecordsStmt.setLong(1, settingsId);
			clearRegroupAllRecordsStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not clear regroup all records", e);
		}
		logEntry.addNote("Finished regrouping all records");
		logEntry.saveResults();
	}
}
