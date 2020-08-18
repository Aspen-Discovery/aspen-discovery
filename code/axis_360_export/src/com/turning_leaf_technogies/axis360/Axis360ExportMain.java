package com.turning_leaf_technogies.axis360;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
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

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Base64;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.zip.CRC32;

public class Axis360ExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static Axis360ExtractLogEntry logEntry;

	//SQL Statements
	private static PreparedStatement updateAxis360ItemStmt;
	private static PreparedStatement deleteAxis360ItemStmt;
	private static PreparedStatement deleteAxis360AvailabilityStmt;
	private static PreparedStatement getAllExistingAxis360ItemsStmt;
	private static PreparedStatement updateAxis360AvailabilityStmt;
	private static PreparedStatement getExistingAxis360AvailabilityStmt;
	private static PreparedStatement getRecordsToReloadStmt;
	private static PreparedStatement markRecordToReloadAsProcessedStmt;
	private static PreparedStatement getItemDetailsForRecordStmt;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//Existing records
	private static HashMap<String, Axis360Title> existingRecords = new HashMap<>();

	//For Checksums
	private static final CRC32 checksumCalculator = new CRC32();
	private static Connection aspenConn;

	private static String accessToken;
	private static long accessTokenExpiration;

	public static void main(String[] args) {
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		String processName = "axis_360_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

		while (true) {

			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info("Starting " + processName + ": " + startTime.toString());

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			aspenConn = connectToDatabase();

			HashSet<Axis360Setting> settings = loadSettings();

			int numChanges = 0;
			//Process each setting in order.  TODO: These could potentially run in parallel for reduced runtime.
			for(Axis360Setting setting : settings) {
				createDbLogEntry(startTime, setting.getId(), aspenConn);

				//Get a list of all existing records in the database
				loadExistingTitles(setting);

				//Do the actual work here
				numChanges += extractAxis360Data(setting);

				//Mark any records that no longer exist in search results as deleted
				numChanges += deleteItems(setting);

				processRecordsToReload(logEntry);

				if (groupedWorkIndexer != null) {
					groupedWorkIndexer.finishIndexingFromExtract(logEntry);
					recordGroupingProcessorSingleton = null;
					groupedWorkIndexer = null;
					existingRecords = null;
				}

				if (logEntry.hasErrors()) {
					logger.error("There were errors during the export!");
				}

				logger.info("Finished " + new Date().toString());
				long endTime = new Date().getTime();
				long elapsedTime = endTime - startTime.getTime();
				logger.info("Elapsed Minutes " + (elapsedTime / 60000));

				logEntry.setFinished();
			}

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
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}

			//Disconnect from the database
			disconnectDatabase(aspenConn);

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				while (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
					try {
						System.gc();
						Thread.sleep(1000 * 60 * 5);
					} catch (InterruptedException e) {
						logger.info("Thread was interrupted");
					}
				}
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

	private static void processRecordsToReload(Axis360ExtractLogEntry logEntry) {
		try {
			//First process books and eBooks
			getRecordsToReloadStmt.setString(1, "axis360");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String axis360Id = getRecordsToReloadRS.getString("identifier");
				//Regroup the record
				getItemDetailsForRecordStmt.setString(1, axis360Id);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String primaryAuthor = getItemDetailsForRecordRS.getString("primaryAuthor");
						String groupedWorkId = groupAxis360Record(itemDetails, axis360Id, primaryAuthor);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + axis360Id);
					}
				}else{
					logEntry.incErrors("Could not get details for record to reload " + axis360Id);
				}
				getItemDetailsForRecordRS.close();

			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " eBooks and audiobooks marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (SQLException e){
			logEntry.incErrors("Error processing records to reload", e);
		}
	}

	private static int deleteItems(Axis360Setting setting) {
		int numDeleted = 0;
		try {
			for (Axis360Title axis360Title : existingRecords.values()) {
				if (!axis360Title.isDeleted()) {
					//Remove Axis360 availability
					deleteAxis360AvailabilityStmt.setString(1, axis360Title.getAxis360Id());
					deleteAxis360AvailabilityStmt.setLong(2, setting.getId());

					axis360Title.removeSetting(setting.getId());

					if (axis360Title.getNumSettings() == 0) {
						deleteAxis360ItemStmt.setLong(1, axis360Title.getId());
						deleteAxis360ItemStmt.executeUpdate();
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("axis360", axis360Title.getAxis360Id());
						if (result.reindexWork) {
							getGroupedWorkIndexer().processGroupedWork(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
						}
					}else{
						//Reindex the work
						String groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("axis360", axis360Title.getAxis360Id());
						indexAxis360Record(groupedWorkId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old titles");
			}
		} catch (SQLException e) {
			logger.error("Error deleting items", e);
			logEntry.addNote("Error deleting items " + e.toString());
		}
		return numDeleted;
	}

	private static void loadExistingTitles(Axis360Setting setting) {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			getAllExistingAxis360ItemsStmt.setLong(1, setting.getId());
			ResultSet allRecordsRS = getAllExistingAxis360ItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				String axis360Id = allRecordsRS.getString("axis360Id");
				Axis360Title newTitle = new Axis360Title(
						allRecordsRS.getLong("id"),
						axis360Id,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);
				String allSettingIds = allRecordsRS.getString("all_settings");
				String[] settingIds = allSettingIds.split(",");
				for(String settingId : settingIds) {
					newTitle.addSetting(Long.parseLong(settingId));
				}
				existingRecords.put(axis360Id, newTitle);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing titles", e);
			logEntry.saveResults();
			System.exit(-1);
		}
	}

	private static HashSet<Axis360Setting> loadSettings(){
		HashSet<Axis360Setting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from axis360_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				Axis360Setting setting = new Axis360Setting(getSettingsRS);
				settings.add(setting);
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		if (settings.size() == 0) {
			logger.error("Unable to find settings for Axis 360, please add settings to the database");
		}
		return settings;
	}

	private static String getAxis360AccessToken(Axis360Setting setting) {
		long curTime = new Date().getTime();
		if (accessToken == null || accessTokenExpiration <= curTime){
			String authentication = setting.getVendorUsername() + ":" + setting.getVendorPassword() + ":" + setting.getLibraryPrefix();

			String authorizationUrl = setting.getBaseUrl() + "/Services/VendorAPI/accesstoken";
			WebServiceResponse response = NetworkUtils.postToURL(authorizationUrl, "", "application/json", null, logger, authentication, 10000, 300000, StandardCharsets.UTF_16LE);
			if (!response.isSuccess()) {
				logEntry.incErrors("Error calling " + authorizationUrl + ": " + response.getResponseCode() + " " + response.getMessage());
			}else{
				JSONObject accessTokenResponse = response.getJSONResponse();
				accessToken = accessTokenResponse.getString("access_token");
				accessTokenExpiration = new Date().getTime() + (accessTokenResponse.getLong("expires_in")  - 5) * 1000;
			}
		}
		return accessToken;
	}

	private static int extractAxis360Data(Axis360Setting setting) {
		int numChanges = 0;
		try {
			numChanges = extractBooks(setting, numChanges);

			if (setting.doFullReload()) {
				//Un mark that a full update needs to be done
				PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE axis360_settings set runFullUpdate = 0 where id = ?");
				updateSettingsStmt.setLong(1, setting.getId());
				updateSettingsStmt.executeUpdate();
			}

			if (!logEntry.hasErrors()) {
				//Update the last time we ran the update in settings
				PreparedStatement updateExtractTime;
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (setting.doFullReload()) {
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				updateExtractTime = aspenConn.prepareStatement("UPDATE axis360_settings set " + columnToUpdate + " = ? WHERE id = ?");
				updateExtractTime.setLong(1, startTimeForLogging);
				updateExtractTime.setLong(2, setting.getId());
				updateExtractTime.executeUpdate();
			} else {
				logEntry.addNote("Not setting last extract time since there were problems extracting products from the API");
			}
			logger.info("Updated or added " + numChanges + " records");
		} catch (SQLException e) {
			logEntry.incErrors("Error extracting Axis360 information ", e);
		}

		return numChanges;
	}

	private static int extractBooks(Axis360Setting setting, int numChanges) {
		HashMap<String, String> headers = new HashMap<>();
		String accessToken = getAxis360AccessToken(setting);
		if (accessToken == null){
			logEntry.incErrors("Did not get access token");
			return 0;
		}
		headers.put("Authorization", getAxis360AccessToken(setting));
		headers.put("Library", setting.getLibraryPrefix());
		headers.put("Content-Type", "text/xml");
		headers.put("Accept", "text/xml");
		//Get a list of titles to process
		String itemDetailsUrl = setting.getBaseUrl() + "/Services/VendorAPI/getItemDetails/v2";
		if (!setting.doFullReload() && (setting.getLastUpdateOfChangedRecords() != 0)){
			itemDetailsUrl += "?startDateTime=" + new SimpleDateFormat("MM-dd-YYYY HH:mm:ss").format(new Date(setting.getLastUpdateOfChangedRecords() * 1000));
		}else{
			itemDetailsUrl += "?startDateTime=" + URLEncoder.encode(new SimpleDateFormat("MM-dd-YYYY HH:mm:ss").format(new Date(946684800000L))); //January 1st 2000
		}

		WebServiceResponse response = NetworkUtils.getURL(itemDetailsUrl, logger, headers, 120000);
		if (!response.isSuccess()) {
			logEntry.incErrors("Error calling " + itemDetailsUrl + ": " + response.getResponseCode() + " " + response.getMessage());
		} else {
			try {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				int numPages = responseJSON.getInt("pageCount");
				int numResults = responseJSON.getInt("resultSetCount");
				logEntry.addNote("Preparing to process " + numPages + " pages of audiobook and ebook results, " + numResults + " results");
				logEntry.setNumProducts(numResults);
				logEntry.saveResults();
				//Process the first page of results
				logger.debug("Processing page 0 of results");
				numChanges += processAxis360Titles(setting, responseJSON, setting.doFullReload());

				//Process each page of the results
				for (int curPage = 1; curPage < numPages; curPage++) {
					logger.debug("Processing page " + curPage);
					itemDetailsUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryPrefix() + "/search?page-size=100&page-index=" + curPage;
					response = NetworkUtils.getURL(itemDetailsUrl, logger, headers);
					responseJSON = new JSONObject(response.getMessage());
					numChanges += processAxis360Titles(setting, responseJSON, setting.doFullReload());
				}
			} catch (JSONException e) {
				logger.error("Error parsing response", e);
				logEntry.addNote("Error parsing response: " + e.toString());
			}
		}
		groupedWorkIndexer.commitChanges();
		return numChanges;
	}

	private static int processAxis360Titles(Axis360Setting setting, JSONObject responseJSON, boolean doFullReload) {
		int numChanges = 0;
		try {
			if (!responseJSON.has("items")){
				logEntry.incErrors("Items not found " + responseJSON.getString("message"));
			}else {
				JSONArray items = responseJSON.getJSONArray("items");
				for (int i = 0; i < items.length(); i++) {
					JSONObject curItem = items.getJSONObject(i);
					JSONObject itemDetails = curItem.getJSONObject("item");
					checksumCalculator.reset();
					String itemDetailsAsString = itemDetails.toString();
					checksumCalculator.update(itemDetailsAsString.getBytes());
					long itemChecksum = checksumCalculator.getValue();

					//MDN 4/11/2019 Although axis360 provides an id field, they actually use ISBN as the unique identifier
					//for audiobooks and eBooks.  Switch to that.
					String axis360Id = itemDetails.getString("isbn");
					logger.debug("processing " + axis360Id);

					//Check to see if the title metadata has changed
					Axis360Title existingTitle = existingRecords.get(axis360Id);
					boolean metadataChanged = false;
					if (existingTitle != null) {
						logger.debug("Record already exists");
						if (existingTitle.getChecksum() != itemChecksum || existingTitle.isDeleted()) {
							logger.debug("Updating item details");
							metadataChanged = true;
						}
						existingRecords.remove(axis360Id);
					} else {
						logger.debug("Adding record " + axis360Id);
						metadataChanged = true;
					}

					//Check if availability changed
					JSONObject itemAvailability = curItem.getJSONObject("interest");
					checksumCalculator.reset();
					String itemAvailabilityAsString = itemAvailability.toString();
					checksumCalculator.update(itemAvailabilityAsString.getBytes());
					long availabilityChecksum = checksumCalculator.getValue();
					boolean availabilityChanged = false;
					getExistingAxis360AvailabilityStmt.setString(1, axis360Id);
					getExistingAxis360AvailabilityStmt.setLong(2, setting.getId());
					ResultSet getExistingAvailabilityRS = getExistingAxis360AvailabilityStmt.executeQuery();
					if (getExistingAvailabilityRS.next()) {
						long existingChecksum = getExistingAvailabilityRS.getLong("rawChecksum");
						logger.debug("Availability already exists");
						if (existingChecksum != availabilityChecksum) {
							logger.debug("Updating availability details");
							availabilityChanged = true;
						}
					} else {
						logger.debug("Adding availability for " + axis360Id);
						availabilityChanged = true;
					}

					String primaryAuthor = null;
					JSONArray authors = itemDetails.getJSONArray("authors");
					if (authors.length() > 0) {
						primaryAuthor = authors.getJSONObject(0).getString("text");
					}
					if (metadataChanged || doFullReload) {
						logEntry.incMetadataChanges();
						//Update the database
						updateAxis360ItemStmt.setString(1, axis360Id);
						updateAxis360ItemStmt.setString(2, itemDetails.getString("title"));
						updateAxis360ItemStmt.setString(3, primaryAuthor);

						updateAxis360ItemStmt.setString(4, itemDetails.getString("mediaType"));
						updateAxis360ItemStmt.setBoolean(5, itemDetails.getBoolean("isFiction"));
						updateAxis360ItemStmt.setString(6, itemDetails.getString("audience"));
						updateAxis360ItemStmt.setString(7, itemDetails.getString("language"));
						updateAxis360ItemStmt.setLong(8, itemChecksum);
						updateAxis360ItemStmt.setString(9, itemDetailsAsString);
						updateAxis360ItemStmt.setLong(10, startTimeForLogging);
						updateAxis360ItemStmt.setLong(11, startTimeForLogging);
						int result = updateAxis360ItemStmt.executeUpdate();
						if (result == 1) {
							//A result of 1 indicates a new row was inserted
							logEntry.incAdded();
						}
					}

					if (availabilityChanged || doFullReload) {
						logEntry.incAvailabilityChanges();
						updateAxis360AvailabilityStmt.setString(1, axis360Id);
						updateAxis360AvailabilityStmt.setLong(2, setting.getId());
						updateAxis360AvailabilityStmt.setBoolean(3, itemAvailability.getBoolean("isAvailable"));
						updateAxis360AvailabilityStmt.setBoolean(4, itemAvailability.getBoolean("isOwned"));
						updateAxis360AvailabilityStmt.setString(5, itemAvailability.getString("name"));
						updateAxis360AvailabilityStmt.setLong(6, availabilityChecksum);
						updateAxis360AvailabilityStmt.setString(7, itemAvailabilityAsString);
						updateAxis360AvailabilityStmt.setLong(8, startTimeForLogging);
						updateAxis360AvailabilityStmt.executeUpdate();
					}

					String groupedWorkId = null;
					if (metadataChanged || doFullReload) {
						groupedWorkId = groupAxis360Record(itemDetails, axis360Id, primaryAuthor);
					}
					if (metadataChanged || availabilityChanged || doFullReload) {
						logEntry.incUpdated();
						if (groupedWorkId == null) {
							groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("axis360", axis360Id);
						}
						indexAxis360Record(groupedWorkId);
						numChanges++;
					}
				}
			}
		} catch (Exception e) {
			logger.error("Error processing titles", e);
		}
		logEntry.saveResults();
		return numChanges;
	}

	private static void indexAxis360Record(String permanentId) {
		getGroupedWorkIndexer().processGroupedWork(permanentId);
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static String groupAxis360Record(JSONObject itemDetails, String axis360Id, String primaryAuthor) throws JSONException {
		//Perform record grouping on the record
		String title = itemDetails.getString("title");
		String author = primaryAuthor;
		author = StringUtils.swapFirstLastNames(author);
		String mediaType = itemDetails.getString("mediaType");

		RecordIdentifier primaryIdentifier = new RecordIdentifier("axis360", axis360Id);

		String subtitle = "";
		if (itemDetails.getBoolean("hasSubtitle")) {
			subtitle = itemDetails.getString("subtitle");
		}
		return getRecordGroupingProcessor().processRecord(primaryIdentifier, title, subtitle, author, mediaType, true);
	}

	private static RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static Connection connectToDatabase() {
		Connection aspenConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);
				getAllExistingAxis360ItemsStmt = aspenConn.prepareStatement("SELECT axis360_title.id, axis360_title.axis360Id, axis360_title.rawChecksum, deleted, GROUP_CONCAT(settingId) as all_settings from axis360_title INNER join axis360_title_availability on axis360_title.id = axis360_title_availability.titleId WHERE settingId = ?  group by axis360_title.id, axis360_title.axis360Id, axis360_title.rawChecksum, deleted", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateAxis360ItemStmt = aspenConn.prepareStatement(
						"INSERT INTO axis360_title " +
								"(axis360Id, title, subtitle, primaryAuthor, formatType, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), primaryAuthor = VALUES(primaryAuthor), formatType = VALUES(formatType), " +
								"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
				deleteAxis360AvailabilityStmt = aspenConn.prepareStatement("DELETE FROM axis360_title_availability where titleId = ? and settingId = ?");
				deleteAxis360ItemStmt = aspenConn.prepareStatement("UPDATE axis360_title SET deleted = 1 where id = ?");
				getExistingAxis360AvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from axis360_title_availability WHERE axis360Id = ? and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateAxis360AvailabilityStmt = aspenConn.prepareStatement(
						"INSERT INTO axis360_title_availability " +
								"(axis360Id, settingId, libraryPrefix, ownedQty, availableQty, copiesAvailable, totalHolds, totalCheckouts, rawChecksum, rawResponse, lastChange) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE ownedQty = VALUES(ownedQty), availableQty = VALUES(availableQty), " +
								"copiesAvailable = VALUES(copiesAvailable), totalHolds = VALUES(totalHolds), totalCheckouts = VALUES(totalCheckouts), " +
								"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
				getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type=?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
				getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT title, subtitle, primaryAuthor, formatType, rawResponse from axis360_title where axis360Id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			} else {
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}

		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
		return aspenConn;
	}

	private static void createDbLogEntry(Date startTime, Long settingId, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from axis360_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		logEntry = new Axis360ExtractLogEntry(settingId, aspenConn, logger);
	}
}
