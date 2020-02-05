package com.turning_leaf_technologies.rbdigital;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.StringUtils;

import org.apache.logging.log4j.Logger;

import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;

import java.sql.*;
import java.util.Date;
import java.util.HashMap;
import java.util.zip.CRC32;

public class RbdigitalExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static RbdigitalExtractLogEntry logEntry;

	//SQL Statements
	private static PreparedStatement updateRbdigitalItemStmt;
	private static PreparedStatement updateRbdigitalMagazineStmt;
	private static PreparedStatement deleteRbdigitalItemStmt;
	private static PreparedStatement deleteRbdigitalMagazineStmt;
	private static PreparedStatement getAllExistingRbdigitalItemsStmt;
	private static PreparedStatement getAllExistingRbdigitalMagazinesStmt;
	private static PreparedStatement updateRbdigitalAvailabilityStmt;
	private static PreparedStatement getExistingRbdigitalAvailabilityStmt;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//Existing records
	private static HashMap<String, RbdigitalTitle> existingRecords = new HashMap<>();
	private static HashMap<String, RbdigitalMagazine> existingMagazines = new HashMap<>();

	//For Checksums
	private static CRC32 checksumCalculator = new CRC32();
	private static Connection aspenConn;

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

		String processName = "rbdigital_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//noinspection InfiniteLoopStatement
		while (true) {

			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info("Starting " + processName + ": " + startTime.toString());

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			aspenConn = connectToDatabase();

			createDbLogEntry(startTime, aspenConn);

			//Get a list of all existing records in the database
			loadExistingTitles();
			loadExistingMagazines();

			//Do the actual work here
			int numChanges = extractRbdigitalData();

			//Mark any records that no longer exist in search results as deleted
			numChanges += deleteItems();

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				recordGroupingProcessorSingleton = null;
				groupedWorkIndexer = null;
				existingRecords = null;
				existingMagazines = null;
			}

			if (logEntry.hasErrors()) {
				logger.error("There were errors during the export!");
			}

			logger.info("Finished " + new Date().toString());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			logEntry.setFinished();

			//Disconnect from the database
			disconnectDatabase(aspenConn);

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

	private static int deleteItems() {
		int numDeleted = 0;
		try {
			for (RbdigitalTitle rbdigitalTitle : existingRecords.values()) {
				if (!rbdigitalTitle.isDeleted()) {
					deleteRbdigitalItemStmt.setLong(1, rbdigitalTitle.getId());
					deleteRbdigitalItemStmt.executeUpdate();
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("rbdigital", rbdigitalTitle.getRbdigitalId());
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old titles");
			}

			for (RbdigitalMagazine rbdigitalMagazine : existingMagazines.values()) {
				if (!rbdigitalMagazine.isDeleted()) {
					deleteRbdigitalMagazineStmt.setLong(1, rbdigitalMagazine.getId());
					deleteRbdigitalMagazineStmt.executeUpdate();
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("rbdigital_magazine", rbdigitalMagazine.getMagazineId());
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old magazines");
			}
		} catch (SQLException e) {
			logger.error("Error deleting items", e);
			logEntry.addNote("Error deleting items " + e.toString());
		}
		return numDeleted;
	}

	private static void loadExistingTitles() {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			ResultSet allRecordsRS = getAllExistingRbdigitalItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				String rbdigitalId = allRecordsRS.getString("rbdigitalId");
				RbdigitalTitle newTitle = new RbdigitalTitle(
						allRecordsRS.getLong("id"),
						rbdigitalId,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);
				existingRecords.put(rbdigitalId, newTitle);
			}
		} catch (SQLException e) {
			logger.error("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e.toString());
			System.exit(-1);
		}
	}

	private static void loadExistingMagazines() {
		try {
			if (existingMagazines == null) existingMagazines = new HashMap<>();
			ResultSet allRecordsRS = getAllExistingRbdigitalMagazinesStmt.executeQuery();
			while (allRecordsRS.next()) {
				String magazineId = allRecordsRS.getString("magazineId");
				RbdigitalMagazine newTitle = new RbdigitalMagazine(
						allRecordsRS.getLong("id"),
						magazineId,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);
				existingMagazines.put(magazineId, newTitle);
			}
		} catch (SQLException e) {
			logger.error("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e.toString());
			System.exit(-1);
		}
	}

	private static int extractRbdigitalData() {
		int numChanges = 0;

		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from rbdigital_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				String baseUrl = getSettingsRS.getString("apiUrl");
				String apiToken = getSettingsRS.getString("apiToken");
				String libraryId = getSettingsRS.getString("libraryId");
				boolean doFullReload = getSettingsRS.getBoolean("runFullUpdate");
				long settingsId = getSettingsRS.getLong("id");
				if (doFullReload) {
					//Un mark that a full update needs to be done
					PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE rbdigital_settings set runFullUpdate = 0 where id = ?");
					updateSettingsStmt.setLong(1, settingsId);
					updateSettingsStmt.executeUpdate();
				}

				//Get a list of eBooks and eAudiobooks to process (would ideally use book-holdings, but that is not currently working)
				//String audioBookUrl = baseUrl + "/v1/libraries/" + libraryId + "/book-holdings/";

				String bookUrl = baseUrl + "/v1/libraries/" + libraryId + "/search?page-size=100";
				HashMap<String, String> headers = new HashMap<>();
				headers.put("Authorization", "basic " + apiToken);
				headers.put("Content-Type", "application/json");
				WebServiceResponse response = NetworkUtils.getURL(bookUrl, logger, headers);
				if (!response.isSuccess()) {
					logEntry.incErrors();
					logEntry.addNote("Error calling " + bookUrl + ": " + response.getResponseCode() + " " + response.getMessage());
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
						numChanges += processRbdigitalTitles(responseJSON, doFullReload);

						//Process each page of the results
						for (int curPage = 1; curPage < numPages; curPage++) {
							logger.debug("Processing page " + curPage);
							bookUrl = baseUrl + "/v1/libraries/" + libraryId + "/search?page-size=100&page-index=" + curPage;
							response = NetworkUtils.getURL(bookUrl, logger, headers);
							responseJSON = new JSONObject(response.getMessage());
							numChanges += processRbdigitalTitles(responseJSON, doFullReload);
						}
					} catch (JSONException e) {
						logger.error("Error parsing response", e);
						logEntry.addNote("Error parsing response: " + e.toString());
					}
				}

				// Get a list of magazines to process
				String eMagazineUrl = baseUrl + "/v1/libraries/" + libraryId + "/search/emagazine?page-size=100";
				response = NetworkUtils.getURL(eMagazineUrl, logger, headers);
				if (!response.isSuccess()) {
					logEntry.incErrors();
					logEntry.addNote(response.getMessage());
				} else {
					try {
						JSONObject responseJSON = new JSONObject(response.getMessage());
						int numPages = responseJSON.getInt("pageCount");
						int numResults = responseJSON.getInt("resultSetCount");
						logEntry.addNote("Preparing to process " + numPages + " pages of emagazine results, " + numResults + " results");

						logEntry.incNumProducts(numResults);
						logEntry.saveResults();
						logger.debug("Processing page 0 of results");
						numChanges += processRbdigitalMagazines(responseJSON, doFullReload, baseUrl, libraryId, headers);
						for (int curPage = 1; curPage < numPages; curPage++) {
							logger.debug("Processing page " + curPage);
							bookUrl = baseUrl + "/v1/libraries/" + libraryId + "/search/emagazine?page-size=100&page-index=" + curPage;
							response = NetworkUtils.getURL(bookUrl, logger, headers);
							responseJSON = new JSONObject(response.getMessage());
							numChanges += processRbdigitalMagazines(responseJSON, doFullReload, baseUrl, libraryId, headers);
						}

					} catch (JSONException e) {
						logger.error("Error parsing response", e);
						logEntry.addNote("Error parsing response: " + e.toString());
					}
				}

				if (!logEntry.hasErrors()) {
					//Update the last time we ran the update in settings
					PreparedStatement updateExtractTime;
					String columnToUpdate = "lastUpdateOfChangedRecords";
					if (doFullReload) {
						columnToUpdate = "lastUpdateOfAllRecords";
					}
					updateExtractTime = aspenConn.prepareStatement("UPDATE rbdigital_settings set " + columnToUpdate + " = ? WHERE id = ?");
					updateExtractTime.setLong(1, startTimeForLogging);
					updateExtractTime.setLong(2, settingsId);
					updateExtractTime.executeUpdate();
				} else {
					logger.warn("Not setting last extract time since there were problems extracting products from the API");
				}
				logger.info("Updated or added " + numChanges + " records");
			}
			if (numSettings == 0) {
				logger.error("Unable to find settings for Rbdigital, please add settings to the database");
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		return numChanges;
	}

	private static int processRbdigitalMagazines(JSONObject responseJSON, boolean doFullReload, String baseUrl, String libraryId, HashMap<String, String> headers) {
		int numChanges = 0;
		try {
			JSONArray items = responseJSON.getJSONArray("items");
			for (int i = 0; i < items.length(); i++) {
				JSONObject curItem = items.getJSONObject(i);
				JSONObject itemDetails = curItem.getJSONObject("item");
				checksumCalculator.reset();
				String itemDetailsAsString = itemDetails.toString();
				checksumCalculator.update(itemDetailsAsString.getBytes());
				long itemChecksum = checksumCalculator.getValue();

				long magazineId = itemDetails.getLong("magazineId");
				String magazineIdString = Long.toString(magazineId);
				logger.debug("Processing magazine " + magazineId);

				RbdigitalMagazine existingMagazine = existingMagazines.get(magazineIdString);
				boolean metadataChanged = false;
				if (existingMagazine != null) {
					logger.debug("Magazine already exists");
					if (existingMagazine.getChecksum() != itemChecksum || existingMagazine.isDeleted()) {
						logger.debug("Updating magazine details");
						metadataChanged = true;
					}
					existingMagazines.remove(magazineIdString);
				} else {
					logger.debug("Adding magazine " + magazineId);
					metadataChanged = true;
				}
				if (metadataChanged || doFullReload) {
					logEntry.incMetadataChanges();
					//Update the database
					updateRbdigitalMagazineStmt.setLong(1, magazineId);
					updateRbdigitalMagazineStmt.setLong(2, itemDetails.getLong("issueId"));
					updateRbdigitalMagazineStmt.setString(3, itemDetails.getString("title"));
					updateRbdigitalMagazineStmt.setString(4, itemDetails.getString("publisher"));
					updateRbdigitalMagazineStmt.setString(5, itemDetails.getString("mediaType"));
					updateRbdigitalMagazineStmt.setString(6, itemDetails.getString("language"));
					updateRbdigitalMagazineStmt.setLong(7, itemChecksum);
					updateRbdigitalMagazineStmt.setString(8, itemDetailsAsString);
					updateRbdigitalMagazineStmt.setLong(9, startTimeForLogging);
					updateRbdigitalMagazineStmt.setLong(10, startTimeForLogging);
					int result = updateRbdigitalMagazineStmt.executeUpdate();
					if (result == 1) {
						//A result of 1 indicates a new row was inserted
						logEntry.incAdded();
					}
				}

				//Load issue information
				String issuesUrl = baseUrl + "/v1/libraries/" + libraryId + "/magazines/" + magazineId + "/issues?pageIndex=0&pageSize=100";
				WebServiceResponse response = NetworkUtils.getURL(issuesUrl, logger, headers);
				JSONObject issuesObject = new JSONObject(response.getMessage());

				if (metadataChanged || doFullReload) {
					String groupedWorkId = groupRbdigitalMagazine(itemDetails, magazineIdString);

					logEntry.incUpdated();
					indexRbdigitalRecord(groupedWorkId);
					numChanges++;
				}
			}
		} catch (Exception e) {
			logger.error("Error processing titles", e);
		}
		logEntry.saveResults();
		return numChanges;
	}

	private static int processRbdigitalTitles(JSONObject responseJSON, boolean doFullReload) {
		int numChanges = 0;
		try {
			JSONArray items = responseJSON.getJSONArray("items");
			for (int i = 0; i < items.length(); i++) {
				JSONObject curItem = items.getJSONObject(i);
				JSONObject itemDetails = curItem.getJSONObject("item");
				checksumCalculator.reset();
				String itemDetailsAsString = itemDetails.toString();
				checksumCalculator.update(itemDetailsAsString.getBytes());
				long itemChecksum = checksumCalculator.getValue();

				//MDN 4/11/2019 Although rbdigital provides an id field, they actually use ISBN as the unique identifier
				//for audiobooks and eBooks.  Switch to that.
				String rbdigitalId = itemDetails.getString("isbn");
				logger.debug("processing " + rbdigitalId);

				//Check to see if the title metadata has changed
				RbdigitalTitle existingTitle = existingRecords.get(rbdigitalId);
				boolean metadataChanged = false;
				if (existingTitle != null) {
					logger.debug("Record already exists");
					if (existingTitle.getChecksum() != itemChecksum || existingTitle.isDeleted()) {
						logger.debug("Updating item details");
						metadataChanged = true;
					}
					existingRecords.remove(rbdigitalId);
				} else {
					logger.debug("Adding record " + rbdigitalId);
					metadataChanged = true;
				}

				//Check if availability changed
				JSONObject itemAvailability = curItem.getJSONObject("interest");
				checksumCalculator.reset();
				String itemAvailabilityAsString = itemAvailability.toString();
				checksumCalculator.update(itemAvailabilityAsString.getBytes());
				long availabilityChecksum = checksumCalculator.getValue();
				boolean availabilityChanged = false;
				getExistingRbdigitalAvailabilityStmt.setString(1, rbdigitalId);
				ResultSet getExistingAvailabilityRS = getExistingRbdigitalAvailabilityStmt.executeQuery();
				if (getExistingAvailabilityRS.next()) {
					long existingChecksum = getExistingAvailabilityRS.getLong("rawChecksum");
					logger.debug("Availability already exists");
					if (existingChecksum != availabilityChecksum) {
						logger.debug("Updating availability details");
						availabilityChanged = true;
					}
				} else {
					logger.debug("Adding availability for " + rbdigitalId);
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
					updateRbdigitalItemStmt.setString(1, rbdigitalId);
					updateRbdigitalItemStmt.setString(2, itemDetails.getString("title"));
					updateRbdigitalItemStmt.setString(3, primaryAuthor);

					updateRbdigitalItemStmt.setString(4, itemDetails.getString("mediaType"));
					updateRbdigitalItemStmt.setBoolean(5, itemDetails.getBoolean("isFiction"));
					updateRbdigitalItemStmt.setString(6, itemDetails.getString("audience"));
					updateRbdigitalItemStmt.setString(7, itemDetails.getString("language"));
					updateRbdigitalItemStmt.setLong(8, itemChecksum);
					updateRbdigitalItemStmt.setString(9, itemDetailsAsString);
					updateRbdigitalItemStmt.setLong(10, startTimeForLogging);
					updateRbdigitalItemStmt.setLong(11, startTimeForLogging);
					int result = updateRbdigitalItemStmt.executeUpdate();
					if (result == 1) {
						//A result of 1 indicates a new row was inserted
						logEntry.incAdded();
					}
				}

				if (availabilityChanged || doFullReload) {
					logEntry.incAvailabilityChanges();
					updateRbdigitalAvailabilityStmt.setString(1, rbdigitalId);
					updateRbdigitalAvailabilityStmt.setBoolean(2, itemAvailability.getBoolean("isAvailable"));
					updateRbdigitalAvailabilityStmt.setBoolean(3, itemAvailability.getBoolean("isOwned"));
					updateRbdigitalAvailabilityStmt.setString(4, itemAvailability.getString("name"));
					updateRbdigitalAvailabilityStmt.setLong(5, availabilityChecksum);
					updateRbdigitalAvailabilityStmt.setString(6, itemAvailabilityAsString);
					updateRbdigitalAvailabilityStmt.setLong(7, startTimeForLogging);
					updateRbdigitalAvailabilityStmt.executeUpdate();
				}

				String groupedWorkId = null;
				if (metadataChanged || doFullReload) {
					groupedWorkId = groupRbdigitalRecord(itemDetails, rbdigitalId, primaryAuthor);
				}
				if (metadataChanged || availabilityChanged || doFullReload) {
					logEntry.incUpdated();
					if (groupedWorkId == null) {
						groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("rbdigital", rbdigitalId);
					}
					indexRbdigitalRecord(groupedWorkId);
					numChanges++;
				}
			}
		} catch (Exception e) {
			logger.error("Error processing titles", e);
		}
		logEntry.saveResults();
		return numChanges;
	}

	private static void indexRbdigitalRecord(String permanentId) {
		getGroupedWorkIndexer().processGroupedWork(permanentId);
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, false, logger);
		}
		return groupedWorkIndexer;
	}

	private static String groupRbdigitalRecord(JSONObject itemDetails, String rbdigitalId, String primaryAuthor) throws JSONException {
		//Perform record grouping on the record
		String title = itemDetails.getString("title");
		String author = primaryAuthor;
		author = StringUtils.swapFirstLastNames(author);
		String mediaType = itemDetails.getString("mediaType");

		RecordIdentifier primaryIdentifier = new RecordIdentifier("rbdigital", rbdigitalId);

		String subtitle = "";
		if (itemDetails.getBoolean("hasSubtitle")) {
			subtitle = itemDetails.getString("subtitle");
		}
		return getRecordGroupingProcessor().processRecord(primaryIdentifier, title, subtitle, author, mediaType, true);
	}

	private static String groupRbdigitalMagazine(JSONObject itemDetails, String magazineId) throws JSONException {
		String title = itemDetails.getString("title");
		String author = itemDetails.getString("publisher");
		String mediaType = itemDetails.getString("mediaType");

		RecordIdentifier primaryIdentifier = new RecordIdentifier("rbdigital_magazine", magazineId);
		return getRecordGroupingProcessor().processRecord(primaryIdentifier, title, "", author, mediaType, true);
	}

	private static RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logger);
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
				getAllExistingRbdigitalItemsStmt = aspenConn.prepareStatement("SELECT id, rbdigitalId, rawChecksum, deleted from rbdigital_title");
				updateRbdigitalItemStmt = aspenConn.prepareStatement(
						"INSERT INTO rbdigital_title " +
								"(rbdigitalId, title, primaryAuthor, mediaType, isFiction, audience, language, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE title = VALUES(title), primaryAuthor = VALUES(primaryAuthor), mediaType = VALUES(mediaType), " +
								"isFiction = VALUES(isFiction), audience = VALUES(audience), language = VALUES(language), rawChecksum = VALUES(rawChecksum), " +
								"rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
				deleteRbdigitalItemStmt = aspenConn.prepareStatement("UPDATE rbdigital_title SET deleted = 1 where id = ?");
				getExistingRbdigitalAvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from rbdigital_availability WHERE rbdigitalId = ?");
				updateRbdigitalAvailabilityStmt = aspenConn.prepareStatement(
						"INSERT INTO rbdigital_availability " +
								"(rbdigitalId, isAvailable, isOwned, name, rawChecksum, rawResponse, lastChange) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE isAvailable = VALUES(isAvailable), isOwned = VALUES(isOwned), " +
								"name = VALUES(name), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
				getAllExistingRbdigitalMagazinesStmt = aspenConn.prepareStatement("SELECT id, magazineId, rawChecksum, deleted from rbdigital_magazine");
				updateRbdigitalMagazineStmt = aspenConn.prepareStatement("INSERT INTO rbdigital_magazine (magazineId, issueId, title, publisher, mediaType, language, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
						"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
						"ON DUPLICATE KEY UPDATE magazineId = VALUES(magazineId), issueId = VALUES(issueId), title = VALUES(title), publisher = VALUES(publisher), " +
						"mediaType = VALUES(mediaType), language = VALUES(language), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
				deleteRbdigitalMagazineStmt = aspenConn.prepareStatement("UPDATE rbdigital_magazine SET deleted = 1 where id = ?");
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

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from rbdigital_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		logEntry = new RbdigitalExtractLogEntry(aspenConn, logger);
	}
}
