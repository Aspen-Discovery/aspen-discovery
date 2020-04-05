package com.turning_leaf_technologies.rbdigital;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
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
import java.util.HashSet;
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
	private static PreparedStatement getRBdigitalIssueStmt;
	private static PreparedStatement updateRBdigitalIssueStmt;
	private static PreparedStatement updateRBdigitalIssueAvailabilityStmt;
	private static PreparedStatement deleteRbdigitalItemStmt;
	private static PreparedStatement deleteRBdigitalAvailabilityStmt;
	private static PreparedStatement deleteRbdigitalMagazineStmt;
	private static PreparedStatement getAllExistingRbdigitalItemsStmt;
	private static PreparedStatement getAllExistingRbdigitalMagazinesStmt;
	private static PreparedStatement updateRbdigitalAvailabilityStmt;
	private static PreparedStatement getExistingRbdigitalAvailabilityStmt;
	private static PreparedStatement getRecordsToReloadStmt;
	private static PreparedStatement markRecordToReloadAsProcessedStmt;
	private static PreparedStatement getItemDetailsForRecordStmt;
	private static PreparedStatement getItemDetailsForMagazineStmt;

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

			HashSet<RBdigitalSetting> settings = loadSettings();

			int numChanges = 0;
			//Process each setting in order.  TODO: These could potentially run in parallel for reduced runtime.
			for(RBdigitalSetting setting : settings) {
				createDbLogEntry(startTime, setting.getId(), aspenConn);

				//Get a list of all existing records in the database
				loadExistingTitles(setting);
				loadExistingMagazines(setting);

				//Do the actual work here
				numChanges += extractRbdigitalData(setting);

				//Mark any records that no longer exist in search results as deleted
				numChanges += deleteItems(setting);

				processRecordsToReload(logEntry);

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
			}

			//Disconnect from the database
			disconnectDatabase(aspenConn);

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				break;
			}
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				break;
			}
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

	private static void processRecordsToReload(RbdigitalExtractLogEntry logEntry) {
		try {
			//First process books and eBooks
			getRecordsToReloadStmt.setString(1, "rbdigital");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String rbdigitalId = getRecordsToReloadRS.getString("identifier");
				//Regroup the record
				getItemDetailsForRecordStmt.setString(1, rbdigitalId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String primaryAuthor = getItemDetailsForRecordRS.getString("primaryAuthor");
						String groupedWorkId = groupRbdigitalRecord(itemDetails, rbdigitalId, primaryAuthor);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + rbdigitalId);
					}
				}else{
					logEntry.incErrors("Could not get details for record to reload " + rbdigitalId);
				}
				getItemDetailsForRecordRS.close();

			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " eBooks and audiobooks marked for reprocessing");
			}
			getRecordsToReloadRS.close();

			//First process books and eBooks
			getRecordsToReloadStmt.setString(1, "rbdigital_magazine");
			getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String magazineId = getRecordsToReloadRS.getString("identifier");
				//Regroup the record
				getItemDetailsForMagazineStmt.setString(1, magazineId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForMagazineStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String groupedWorkId = groupRbdigitalMagazine(itemDetails, magazineId);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + magazineId);
					}
				}else{
					logEntry.incErrors("Could not get details for record to reload " + magazineId);
				}

			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " magazines marked for reprocessing");
			}
			getRecordsToReloadRS.close();

			//Now process magazines
		}catch (SQLException e){
			logEntry.incErrors("Error processing records to reload", e);
		}
	}

	private static int deleteItems(RBdigitalSetting setting) {
		int numDeleted = 0;
		try {
			for (RbdigitalTitle rbdigitalTitle : existingRecords.values()) {
				if (!rbdigitalTitle.isDeleted()) {
					//Remove RBdigital availability
					deleteRBdigitalAvailabilityStmt.setString(1, rbdigitalTitle.getRbdigitalId());
					deleteRBdigitalAvailabilityStmt.setLong(2, setting.getId());

					rbdigitalTitle.removeSetting(setting.getId());

					if (rbdigitalTitle.getNumSettings() == 0) {
						deleteRbdigitalItemStmt.setLong(1, rbdigitalTitle.getId());
						deleteRbdigitalItemStmt.executeUpdate();
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("rbdigital", rbdigitalTitle.getRbdigitalId());
						if (result.reindexWork) {
							getGroupedWorkIndexer().processGroupedWork(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
						}
					}else{
						//Reindex the work
						String groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("rbdigital", rbdigitalTitle.getRbdigitalId());
						indexRbdigitalRecord(groupedWorkId);
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

	private static void loadExistingTitles(RBdigitalSetting setting) {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			getAllExistingRbdigitalItemsStmt.setLong(1, setting.getId());
			ResultSet allRecordsRS = getAllExistingRbdigitalItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				String rbdigitalId = allRecordsRS.getString("rbdigitalId");
				RbdigitalTitle newTitle = new RbdigitalTitle(
						allRecordsRS.getLong("id"),
						rbdigitalId,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);
				String allSettingIds = allRecordsRS.getString("all_settings");
				String[] settingIds = allSettingIds.split(",");
				for(String settingId : settingIds) {
					newTitle.addSetting(Long.parseLong(settingId));
				}
				existingRecords.put(rbdigitalId, newTitle);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing titles", e);
			logEntry.saveResults();
			System.exit(-1);
		}
	}

	private static void loadExistingMagazines(RBdigitalSetting setting) {
		try {
			if (existingMagazines == null) existingMagazines = new HashMap<>();
			getAllExistingRbdigitalMagazinesStmt.setLong(1, setting.getId());
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
			logEntry.incErrors("Error loading existing magazines ", e);
			logEntry.saveResults();
			System.exit(-1);
		}
	}

	private static HashSet<RBdigitalSetting> loadSettings(){
		HashSet<RBdigitalSetting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from rbdigital_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				RBdigitalSetting setting = new RBdigitalSetting(getSettingsRS);
				settings.add(setting);
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		if (settings.size() == 0) {
			logger.error("Unable to find settings for RBdigital, please add settings to the database");
		}
		return settings;
	}

	private static int extractRbdigitalData(RBdigitalSetting setting) {
		int numChanges = 0;
		try {
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", "basic " + setting.getApiToken());
			headers.put("Content-Type", "application/json");

			numChanges = extractMagazines(setting, numChanges, headers);
			numChanges = extractBooks(setting, numChanges, headers);

			if (setting.doFullReload()) {
				//Un mark that a full update needs to be done
				PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE rbdigital_settings set runFullUpdate = 0 where id = ?");
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
				updateExtractTime = aspenConn.prepareStatement("UPDATE rbdigital_settings set " + columnToUpdate + " = ? WHERE id = ?");
				updateExtractTime.setLong(1, startTimeForLogging);
				updateExtractTime.setLong(2, setting.getId());
				updateExtractTime.executeUpdate();
			} else {
				logEntry.addNote("Not setting last extract time since there were problems extracting products from the API");
			}
			logger.info("Updated or added " + numChanges + " records");
		} catch (SQLException e) {
			logEntry.incErrors("Error extracting RBdigital information ", e);
		}

		return numChanges;
	}

	private static int extractMagazines(RBdigitalSetting setting, int numChanges, HashMap<String, String> headers) {
		WebServiceResponse response;
		String bookUrl;

		// Get a list of magazines to process
		String eMagazineUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/search/emagazine?page-size=100";
		response = NetworkUtils.getURL(eMagazineUrl, logger, headers);
		if (!response.isSuccess()) {
			logEntry.incErrors(response.getMessage());
		} else {
			try {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				int numPages = responseJSON.getInt("pageCount");
				int numResults = responseJSON.getInt("resultSetCount");
				logEntry.addNote("Preparing to process " + numPages + " pages of emagazine results, " + numResults + " results");

				logEntry.incNumProducts(numResults);
				logEntry.saveResults();
				logger.debug("Processing page 0 of results");
				numChanges += processRbdigitalMagazines(responseJSON, setting, headers);
				for (int curPage = 1; curPage < numPages; curPage++) {
					logger.debug("Processing page " + curPage);
					bookUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/search/emagazine?page-size=100&page-index=" + curPage;
					response = NetworkUtils.getURL(bookUrl, logger, headers);
					responseJSON = new JSONObject(response.getMessage());
					numChanges += processRbdigitalMagazines(responseJSON, setting, headers);
				}

			} catch (JSONException e) {
				logger.error("Error parsing response", e);
				logEntry.addNote("Error parsing response: " + e.toString());
			}
		}
		getGroupedWorkIndexer().commitChanges();
		return numChanges;
	}

	private static int extractBooks(RBdigitalSetting setting, int numChanges, HashMap<String, String> headers) {
		//Get a list of eBooks and eAudiobooks to process (would ideally use book-holdings, but that is not currently working)
		//String audioBookUrl = baseUrl + "/v1/libraries/" + libraryId + "/book-holdings/";
		String bookUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/search?page-size=100";

		WebServiceResponse response = NetworkUtils.getURL(bookUrl, logger, headers, 120000);
		if (!response.isSuccess()) {
			logEntry.incErrors("Error calling " + bookUrl + ": " + response.getResponseCode() + " " + response.getMessage());
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
				numChanges += processRbdigitalTitles(setting, responseJSON, setting.doFullReload());

				//Process each page of the results
				for (int curPage = 1; curPage < numPages; curPage++) {
					logger.debug("Processing page " + curPage);
					bookUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/search?page-size=100&page-index=" + curPage;
					response = NetworkUtils.getURL(bookUrl, logger, headers);
					responseJSON = new JSONObject(response.getMessage());
					numChanges += processRbdigitalTitles(setting, responseJSON, setting.doFullReload());
				}
			} catch (JSONException e) {
				logger.error("Error parsing response", e);
				logEntry.addNote("Error parsing response: " + e.toString());
			}
		}
		groupedWorkIndexer.commitChanges();
		return numChanges;
	}

	private static int processRbdigitalMagazines(JSONObject responseJSON, RBdigitalSetting setting, HashMap<String, String> headers) {
		int numChanges = 0;
		try {
			int resultSetCount = responseJSON.getInt("resultSetCount");
			if (resultSetCount > 0) {
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
					if (metadataChanged || setting.doFullReload()) {
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
					String issuesUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/magazines/" + magazineId + "/issues?pageIndex=0&pageSize=100";
					WebServiceResponse issuesResponse = NetworkUtils.getURL(issuesUrl, logger, headers);
					JSONObject issuesObject = new JSONObject(issuesResponse.getMessage());
					int numPages = issuesObject.getInt("pageCount");
					int numResults = issuesObject.getInt("resultSetCount");

					logger.debug("Processing issues page 0 for magazine " + magazineId + ", " + numResults + " total issues");
					processMagazineIssues(magazineId, issuesObject, setting);
					for (int curPage = 1; curPage < numPages; curPage++) {
						logger.debug("Processing issues page " + curPage + " for magazine " + magazineId);
						issuesUrl = setting.getBaseUrl() + "/v1/libraries/" + setting.getLibraryId() + "/magazines/" + magazineId + "/issues?pageIndex=0&pageSize=100";
						issuesResponse = NetworkUtils.getURL(issuesUrl, logger, headers);
						issuesObject = new JSONObject(issuesResponse.getMessage());
						processMagazineIssues(magazineId, issuesObject, setting);
					}

					if (metadataChanged || setting.doFullReload()) {
						String groupedWorkId = groupRbdigitalMagazine(itemDetails, magazineIdString);

						logEntry.incUpdated();
						indexRbdigitalRecord(groupedWorkId);
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

	private static void processMagazineIssues(long magazineId, JSONObject issuesObject, RBdigitalSetting setting) {
		try {
			if (!issuesObject.has("resultSet")) {
				logEntry.incErrors("Items not found " + issuesObject.getString("message"));
			} else {
				JSONArray items = issuesObject.getJSONArray("resultSet");
				for (int i = 0; i < items.length(); i++) {
					JSONObject curItem = items.getJSONObject(i);
					JSONObject itemDetails = curItem.getJSONObject("item");
					String imageUrl = null;
					JSONArray images = itemDetails.getJSONArray("images");
					if (images.length() > 0) {
						JSONObject imageDetails = images.getJSONObject(0);
						imageUrl = imageDetails.getString("url");
					}

					updateRBdigitalIssueStmt.setLong(1, magazineId);
					updateRBdigitalIssueStmt.setLong(2, itemDetails.getLong("issueId"));
					updateRBdigitalIssueStmt.setString(3, imageUrl);
					updateRBdigitalIssueStmt.setString(4, itemDetails.getString("publishedOn"));
					updateRBdigitalIssueStmt.setString(5, itemDetails.getString("coverDate"));
					updateRBdigitalIssueStmt.executeUpdate();
					long issueId = -1;
					ResultSet generatedKeys = updateRBdigitalIssueStmt.getGeneratedKeys();
					if (generatedKeys != null && generatedKeys.next()) {
						issueId = generatedKeys.getLong(1);
					} else {
						getRBdigitalIssueStmt.setLong(1, magazineId);
						getRBdigitalIssueStmt.setLong(2, itemDetails.getLong("issueId"));
						ResultSet getRBdigitalIssueRS = getRBdigitalIssueStmt.executeQuery();
						if (getRBdigitalIssueRS.next()) {
							issueId = getRBdigitalIssueRS.getLong("id");
						}
					}

					JSONObject interestDetails = curItem.getJSONObject("interest");
					updateRBdigitalIssueAvailabilityStmt.setLong(1, issueId);
					updateRBdigitalIssueAvailabilityStmt.setLong(2, setting.getId());
					updateRBdigitalIssueAvailabilityStmt.setBoolean(3, interestDetails.getBoolean("isAvailable"));
					updateRBdigitalIssueAvailabilityStmt.setBoolean(4, interestDetails.getBoolean("isOwned"));
					updateRBdigitalIssueAvailabilityStmt.setInt(5, interestDetails.getInt("stateId"));
					updateRBdigitalIssueAvailabilityStmt.executeUpdate();

				}
			}
		} catch (Exception e) {
			logger.error("Error processing issues", e);
		}
		logEntry.saveResults();
	}

	private static int processRbdigitalTitles(RBdigitalSetting setting, JSONObject responseJSON, boolean doFullReload) {
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
					getExistingRbdigitalAvailabilityStmt.setLong(2, setting.getId());
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
						updateRbdigitalAvailabilityStmt.setLong(2, setting.getId());
						updateRbdigitalAvailabilityStmt.setBoolean(3, itemAvailability.getBoolean("isAvailable"));
						updateRbdigitalAvailabilityStmt.setBoolean(4, itemAvailability.getBoolean("isOwned"));
						updateRbdigitalAvailabilityStmt.setString(5, itemAvailability.getString("name"));
						updateRbdigitalAvailabilityStmt.setLong(6, availabilityChecksum);
						updateRbdigitalAvailabilityStmt.setString(7, itemAvailabilityAsString);
						updateRbdigitalAvailabilityStmt.setLong(8, startTimeForLogging);
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
				getAllExistingRbdigitalItemsStmt = aspenConn.prepareStatement("SELECT rbdigital_title.id, rbdigital_title.rbdigitalId, rbdigital_title.rawChecksum, deleted, GROUP_CONCAT(settingId) as all_settings from rbdigital_title INNER join rbdigital_availability on rbdigital_title.rbdigitalId = rbdigital_availability.rbdigitalId WHERE settingId = ?  group by rbdigital_title.id, rbdigital_title.rbdigitalId, rbdigital_title.rawChecksum, deleted", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateRbdigitalItemStmt = aspenConn.prepareStatement(
						"INSERT INTO rbdigital_title " +
								"(rbdigitalId, title, primaryAuthor, mediaType, isFiction, audience, language, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE title = VALUES(title), primaryAuthor = VALUES(primaryAuthor), mediaType = VALUES(mediaType), " +
								"isFiction = VALUES(isFiction), audience = VALUES(audience), language = VALUES(language), rawChecksum = VALUES(rawChecksum), " +
								"rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
				deleteRBdigitalAvailabilityStmt = aspenConn.prepareStatement("DELETE FROM rbdigital_availability where rbdigitalId = ? and settingId = ?");
				deleteRbdigitalItemStmt = aspenConn.prepareStatement("UPDATE rbdigital_title SET deleted = 1 where id = ?");
				getExistingRbdigitalAvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from rbdigital_availability WHERE rbdigitalId = ? and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateRbdigitalAvailabilityStmt = aspenConn.prepareStatement(
						"INSERT INTO rbdigital_availability " +
								"(rbdigitalId, settingId, isAvailable, isOwned, name, rawChecksum, rawResponse, lastChange) " +
								"VALUES (?, ?, ?, ?, ?, ?, ?, ?) " +
								"ON DUPLICATE KEY UPDATE isAvailable = VALUES(isAvailable), isOwned = VALUES(isOwned), " +
								"name = VALUES(name), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
				getAllExistingRbdigitalMagazinesStmt = aspenConn.prepareStatement("SELECT id, magazineId, rawChecksum, deleted from rbdigital_magazine where magazineId in (SELECT magazineId from rbdigital_magazine_issue inner join rbdigital_magazine_issue_availability ON rbdigital_magazine_issue.id = rbdigital_magazine_issue_availability.issueId where settingId = ?)", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateRbdigitalMagazineStmt = aspenConn.prepareStatement("INSERT INTO rbdigital_magazine " +
						"(magazineId, issueId, title, publisher, mediaType, language, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
						"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
						"ON DUPLICATE KEY UPDATE magazineId = VALUES(magazineId), issueId = VALUES(issueId), title = VALUES(title), publisher = VALUES(publisher), " +
						"mediaType = VALUES(mediaType), language = VALUES(language), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
				deleteRbdigitalMagazineStmt = aspenConn.prepareStatement("UPDATE rbdigital_magazine SET deleted = 1 where id = ?");
				getRBdigitalIssueStmt = aspenConn.prepareStatement("SELECT id from rbdigital_magazine_issue where magazineId = ? and issueId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updateRBdigitalIssueStmt = aspenConn.prepareStatement("INSERT INTO rbdigital_magazine_issue " +
						"(magazineId, issueId, imageUrl, publishedOn, coverDate) " +
						"VALUES (?, ?, ?, ?, ?) " +
						"ON DUPLICATE KEY UPDATE imageUrl = VALUES(imageUrl), publishedOn = VALUES(publishedOn), coverDate = VALUES(coverDate)", Statement.RETURN_GENERATED_KEYS);
				updateRBdigitalIssueAvailabilityStmt = aspenConn.prepareStatement("INSERT INTO rbdigital_magazine_issue_availability " +
						"(issueId, settingId, isAvailable, isOwned, stateId) " +
						"VALUES (?, ?, ?, ?, ?) " +
						"ON DUPLICATE KEY UPDATE isAvailable = VALUES(isAvailable), isOwned = VALUES(isOwned), stateId = VALUES(stateId)", Statement.RETURN_GENERATED_KEYS);
				getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type=?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
				getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT title, primaryAuthor, mediaType, rawResponse from rbdigital_title where rbdigitalId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getItemDetailsForMagazineStmt = aspenConn.prepareStatement("SELECT rawResponse from rbdigital_magazine where magazineId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
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
			int numDeletions = aspenConn.prepareStatement("DELETE from rbdigital_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		logEntry = new RbdigitalExtractLogEntry(settingId, aspenConn, logger);
	}
}
