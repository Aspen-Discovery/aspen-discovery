package org.aspendiscovery.palace_project;

import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.aspen_discovery.reindexer.PalaceProjectTitleAvailability;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

public class PalaceProjectExtractor {
	private final String serverName;
	private final PalaceProjectSetting setting;
	private final PalaceProjectExportLogEntry logEntry;
	private final Logger logger;
	private final Connection aspenConn;
	private final Ini configIni;

	private final Long startTimeForLogging;
	private final CRC32 checksumCalculator = new CRC32();

	private PreparedStatement getExistingPalaceProjectTitleStmt;
	private PreparedStatement addPalaceProjectTitleToDbStmt;
	private PreparedStatement updatePalaceProjectTitleInDbStmt;
	private PreparedStatement addPalaceProjectAvailabilityStmt;
	private PreparedStatement updatePalaceProjectAvailabilityStmt;
	private PreparedStatement deletePalaceProjectAvailabilityStmt;
	private PreparedStatement updateCollectionLastIndexedStmt;
	private PreparedStatement getAvailabilityForTitleStmt;
	private PreparedStatement getTitlesToRemoveFromCollectionStmt;
	private PreparedStatement deleteCollectionStmt;

	//Record grouper
	private GroupedWorkIndexer groupedWorkIndexer;
	private RecordGroupingProcessor recordGroupingProcessorSingleton = null;


	public PalaceProjectExtractor(String serverName, Connection aspenConn, PalaceProjectSetting setting, Ini configIni, PalaceProjectExportLogEntry logEntry, Logger logger) {
		this.serverName = serverName;
		this.aspenConn = aspenConn;
		this.setting = setting;
		this.configIni = configIni;
		this.logEntry = logEntry;
		this.logger = logger;

		Date startTime = new Date();
		startTimeForLogging = startTime.getTime() / 1000;
	}

	/**
	 * Exports all titles in all collections from Palace Project for the active settings.
	 * @return boolean true if any collections had updates.
	 */
	public boolean exportPalaceProjectData() {
		boolean updatesRun = false;
		try{
			addPalaceProjectTitleToDbStmt = aspenConn.prepareStatement("INSERT INTO palace_project_title (palaceProjectId, title, rawChecksum, rawResponseLength, rawResponse, dateFirstDetected) VALUES (?, ?, ?, ?, COMPRESS(?), ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			getExistingPalaceProjectTitleStmt = aspenConn.prepareStatement("SELECT id, rawChecksum, rawResponseLength from palace_project_title where palaceProjectId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			updatePalaceProjectTitleInDbStmt = aspenConn.prepareStatement("UPDATE palace_project_title set title = ?, rawChecksum = ?, rawResponseLength = ?, rawResponse = COMPRESS(?) WHERE id = ?");
			addPalaceProjectAvailabilityStmt = aspenConn.prepareStatement("INSERT INTO palace_project_title_availability (titleId, collectionId, lastSeen, borrowLink, needsHold, previewLink, deleted) VALUES (?, ?, ?, ?, ?, ?, 0)");
			updatePalaceProjectAvailabilityStmt = aspenConn.prepareStatement("UPDATE palace_project_title_availability SET lastSeen = ?, borrowLink = ?, needsHold = ?, previewLink = ?, deleted = 0 WHERE titleId = ? AND collectionId = ?");
			deletePalaceProjectAvailabilityStmt = aspenConn.prepareStatement("UPDATE palace_project_title_availability SET deleted = 1 WHERE id = ?");
			updateCollectionLastIndexedStmt = aspenConn.prepareStatement("UPDATE palace_project_collections SET lastIndexed = ? where id =?");
			getAvailabilityForTitleStmt = aspenConn.prepareStatement("SELECT COUNT(*) as availabilityCount from palace_project_title_availability WHERE titleId = ? and deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getTitlesToRemoveFromCollectionStmt = aspenConn.prepareStatement("SELECT palace_project_title_availability.id, titleId FROM palace_project_title_availability inner JOIN palace_project_collections on collectionId = palace_project_collections.id where collectionId = ? AND lastSeen < lastIndexed", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			deleteCollectionStmt = aspenConn.prepareStatement("DELETE FROM palace_project_collections WHERE id = ?");

			PreparedStatement getCollectionsForSettingStmt = aspenConn.prepareStatement("SELECT * from palace_project_collections where settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement insertCollectionStmt = aspenConn.prepareStatement("INSERT INTO palace_project_collections (settingId, palaceProjectName, displayName, hasCirculation, includeInAspen) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement getTitlesForCollectionStmt = aspenConn.prepareStatement("SELECT * FROM palace_project_title_availability where collectionId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			//Setup times that we'll use later to determine if we need to index a collection
			Date startTime = new Date();
			long nowInSeconds = startTime.getTime() / 1000;
			long yesterdayInSeconds = nowInSeconds - 24 * 60 * 60;

			logEntry.addNote("Starting update from Palace Project");
			logEntry.saveResults();

			String palaceProjectBaseUrl = setting.getApiUrl();
			String palaceProjectLibraryId = setting.getLibraryId();
			boolean doFullReload = setting.doFullReload();

			//Get a list of collections within Aspen
			HashMap<String, PalaceProjectCollection> palaceProjectCollections = getExistingCollectionsInAspenForSetting(getCollectionsForSettingStmt, setting.getId());

			//Setup default headers
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Accept", "application/opds+json");
			headers.put("User-Agent", "Aspen Discovery");

			String url = palaceProjectBaseUrl + "/" + palaceProjectLibraryId + "/crawlable";

			//Load a list of collections within Palace Project
			WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
			if (!response.isSuccess()) {
				logEntry.incErrors("Could not get titles from " + url + " " + response.getMessage());
			} else {
				JSONObject initialCrawlableResponseJSON = new JSONObject(response.getMessage());
				HashMap<String, String> validCollections = getValidCollectionsFromPalaceProject(initialCrawlableResponseJSON, palaceProjectCollections, insertCollectionStmt, setting.getId());

				// Process deleted collections.
				processDeletedCollections(palaceProjectCollections, validCollections, getTitlesForCollectionStmt);

				// Track if any collections were processed.
				boolean anyCollectionsProcessed = false;
				int collectionsSkippedDueToSettings = 0;

				for (String collectionName : validCollections.keySet()) {
					//Index the collection if the collection has circulation or the collection has not been updated for 24 hours
					PalaceProjectCollection collection = palaceProjectCollections.get(collectionName);

					if (collection.includeInAspen) {
						if (collection.hasCirculation || collection.lastIndexed < yesterdayInSeconds) {
							//Get a list of all titles for this collection
							HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection = getTitlesForCollection(getTitlesForCollectionStmt, collection);

							if (collection.hasCirculation) {
								logEntry.addNote("Collection " + collectionName + " needs to be processed because it has circulation enabled.");
							}else{
								logEntry.addNote("Collection " + collectionName + " needs to be processed because it has not been updated for 24 hours.");
							}
							int numTitlesProcessed = extractRecordsForPalaceProjectCollection(collectionName, validCollections, headers, collection, titlesForCollection, doFullReload, nowInSeconds);
							anyCollectionsProcessed = true;
							if (numTitlesProcessed > 0) {
								updatesRun = true;
							}
						} else {
							// Not time to index, leave things as is.
							logEntry.addNote("Collection " + collectionName + " does not currently need to be processed.");
							collectionsSkippedDueToSettings++;
						}
					} else {
						logEntry.addNote("Collection " + collectionName + " is set to not be included in Aspen, so remove its currently indexed records from Solr.");

						//Get a list of all titles for this collection
						HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection = getTitlesForCollection(getTitlesForCollectionStmt, collection);

						// Remove all currently indexed products from solr.
						for (PalaceProjectTitleAvailability titleAvailability : titlesForCollection.values()) {
							if (!titleAvailability.deleted) {
								removePalaceProjectTitleFromCollection(titleAvailability.id, titleAvailability.titleId);
							}
						}
						collectionsSkippedDueToSettings++;
					}
				}
				// Log if no collections were processed due to settings.
				if (!anyCollectionsProcessed && !validCollections.isEmpty()) {
					String collectionMessage = collectionsSkippedDueToSettings == 1
						? "1 collection was"
						: collectionsSkippedDueToSettings + " collections were";

					logEntry.addNote("WARNING: No collections were processed because " + collectionMessage +
						" not marked for circulation or set to be neither included in Aspen nor marked for circulation. " +
						"Check your Palace Project collection settings in the Aspen Administration interface.");
				} else if (validCollections.isEmpty()) {
					logEntry.addNote("WARNING: No collections were found in the Palace Project API response. " +
						"Check your API configuration and ensure your Palace Project account has active collections.");
				}

			}

			logEntry.addNote("Processing records to reload");
			logEntry.saveResults();
			processRecordsToReload(logEntry);

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}

			if (logEntry.hasErrors()) {
				logger.error("There were errors during the export!");
			}

			logger.info("Finished exporting data " + new Date());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			//Set the extract time
			setLastUpdateTimeForSetting(doFullReload, setting.getId());

			//noinspection DuplicatedCode
			addPalaceProjectTitleToDbStmt.close();
			getExistingPalaceProjectTitleStmt.close();
			updatePalaceProjectTitleInDbStmt.close();
			addPalaceProjectAvailabilityStmt.close();
			updatePalaceProjectAvailabilityStmt.close();
			deletePalaceProjectAvailabilityStmt.close();
			updateCollectionLastIndexedStmt.close();
			getAvailabilityForTitleStmt.close();
			getTitlesToRemoveFromCollectionStmt.close();
			deleteCollectionStmt.close();
		}catch (Exception e){
			logEntry.incErrors("Error exporting Palace Project data", e);
		}
		return updatesRun;
	}

	private void processRecordsToReload(PalaceProjectExportLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='palace_project'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from palace_project_title where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String rawPalaceProjectId = getRecordsToReloadRS.getString("identifier");
				long palaceProjectId;
				logEntry.incRecordsRegrouped();
				if (AspenStringUtils.isNumeric(rawPalaceProjectId)) {
					palaceProjectId = Long.parseLong(rawPalaceProjectId);

					//Regroup the record
					getItemDetailsForRecordStmt.setLong(1, palaceProjectId);
					ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
					if (getItemDetailsForRecordRS.next()){
						String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
						try {
							JSONObject itemDetails = new JSONObject(rawResponse);
							String groupedWorkId =  getRecordGroupingProcessor().groupPalaceProjectRecord(itemDetails, palaceProjectId);
							//Reindex the record
							getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

							markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
							markRecordToReloadAsProcessedStmt.executeUpdate();
							numRecordsToReloadProcessed++;
							logEntry.incChangedAfterGrouping();
						}catch (JSONException e){
							logEntry.incErrors("Could not parse item details for record to reload " + palaceProjectId, e);
						}
					}else{
						//The record has likely been deleted
						logEntry.addNote("Could not get details for palace project record to reload " + palaceProjectId + " it has been deleted");
						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						logEntry.incDeleted();
						numRecordsToReloadProcessed++;
					}
					getItemDetailsForRecordRS.close();

				}else{
					//Delete this record
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("palace_project", rawPalaceProjectId);
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
					markRecordToReloadAsProcessedStmt.executeUpdate();
					numRecordsToReloadProcessed++;
					logEntry.incDeleted();
				}
				if (numRecordsToReloadProcessed % 250 == 0) {
					getGroupedWorkIndexer().commitChanges();
					logEntry.saveResults();
				}
			}
			if (numRecordsToReloadProcessed > 0){
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
				getGroupedWorkIndexer().commitChanges();
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private void removePalaceProjectTitleFromCollection(long availabilityId, long titleId) throws SQLException {
		//Mark the title availability deleted
		deletePalaceProjectAvailabilityStmt.setLong(1, availabilityId);
		deletePalaceProjectAvailabilityStmt.executeUpdate();
		//check to see if the title has any availability
		getAvailabilityForTitleStmt.setLong(1, availabilityId);
		ResultSet availabilityForTitleRS = getAvailabilityForTitleStmt.executeQuery();
		boolean hasAvailability = false;
		if (availabilityForTitleRS.next()) {
			hasAvailability = availabilityForTitleRS.getLong("availabilityCount") > 0;
		}
		availabilityForTitleRS.close();

		if (hasAvailability) {
			//The title still has availability, mark it for reindex
			getGroupedWorkIndexer().forceRecordReindex("palace_project", Long.toString(titleId));

		}else{
			//The title no longer exists, remove it from the work
			RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("palace_project", Long.toString(titleId));
			if (result.reindexWork) {
				getGroupedWorkIndexer().processGroupedWork(result.permanentId);
			} else if (result.deleteWork) {
				//Delete the work from solr and the database
				getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
			}
		}
	}

	private HashMap<String, PalaceProjectCollection> getExistingCollectionsInAspenForSetting(PreparedStatement getCollectionsForSettingStmt, long settingsId) throws SQLException {
		getCollectionsForSettingStmt.setLong(1, settingsId);
		ResultSet collectionsForSettingsRS =  getCollectionsForSettingStmt.executeQuery();
		HashMap <String, PalaceProjectCollection> palaceProjectCollections = new HashMap<>();
		while (collectionsForSettingsRS.next()) {
			PalaceProjectCollection collection = new PalaceProjectCollection();
			collection.id = collectionsForSettingsRS.getLong("id");
			collection.settingId = collectionsForSettingsRS.getLong("settingId");
			collection.palaceProjectName = collectionsForSettingsRS.getString("palaceProjectName");
			collection.displayName = collectionsForSettingsRS.getString("displayName");
			collection.hasCirculation = collectionsForSettingsRS.getBoolean("hasCirculation");
			collection.includeInAspen = collectionsForSettingsRS.getBoolean("includeInAspen");
			collection.lastIndexed = collectionsForSettingsRS.getLong("lastIndexed");
			palaceProjectCollections.put(collection.palaceProjectName, collection);
		}
		return palaceProjectCollections;
	}

	private void setLastUpdateTimeForSetting(boolean doFullReload, long settingsId) throws SQLException {
		PreparedStatement updateSettingsStmt = null;
		if (doFullReload){
			if (!logEntry.hasErrors()) {
				// Update lastUpdateOfAllRecords and reset runFullUpdate flag.
				updateSettingsStmt = aspenConn.prepareStatement("UPDATE palace_project_settings SET lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
				logEntry.addNote("Disabling Run Full Update option after a successful full update.");
			} else {
				//force another full update
				PreparedStatement reactiveFullUpdateStmt = aspenConn.prepareStatement("UPDATE palace_project_settings set runFullUpdate = 1 where id = ?");
				reactiveFullUpdateStmt.setLong(1, settingsId);
				reactiveFullUpdateStmt.executeUpdate();
			}
		}else{
			updateSettingsStmt = aspenConn.prepareStatement("UPDATE palace_project_settings set lastUpdateOfChangedRecords = ? where id = ?");
		}
		if (updateSettingsStmt != null) {
			updateSettingsStmt.setLong(1, startTimeForLogging);
			updateSettingsStmt.setLong(2, settingsId);
			updateSettingsStmt.executeUpdate();
		}
	}

	private final SimpleDateFormat dateModifiedFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX");
	private int extractRecordsForPalaceProjectCollection(String collectionName, HashMap<String, String> validCollections, HashMap<String, String> headers, PalaceProjectCollection collection, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, boolean doFullReload, long indexStartTime) {
		int numTitlesProcessed = 0;
		logEntry.addNote("Extracting Records for " + collectionName + " in setting " + collection.settingId);
		//Index all records in the collection
		String collectionUrl = validCollections.get(collectionName);
		boolean hadErrorsIndexing = false;
		while (collectionUrl != null) {
			int numTries = 0;
			boolean callSucceeded = false;
			while (!callSucceeded && numTries < 3) {
				if (numTries > 0) {
					try {
						//Sleep a little bit to allow the server to calm down.
						Thread.sleep(60000);
					} catch (InterruptedException e) {
						//Not a big deal if this gets interrupted
					}
				}

				WebServiceResponse responseForCollection = NetworkUtils.getURL(collectionUrl, logger, headers);
				if (!responseForCollection.isSuccess()) {
					//This will just retry unless we are at the max number of attempts.
					//logEntry.incErrors("Could not get titles from " + collectionUrl + " " + responseForCollection.getMessage());
				} else {
					try {
						boolean stopProcessingDueToLastUpdateTime = false;
						JSONObject collectionResponseJSON = new JSONObject(responseForCollection.getMessage());
						callSucceeded = true;
						if (collectionResponseJSON.has("publications")) {
							JSONArray responseTitles = collectionResponseJSON.getJSONArray("publications");
							if (responseTitles != null && !responseTitles.isEmpty()) {
								//Get a list of titles to process. If the collection does not have circulation, we only
								//need to index records that were modified since we last indexed. If the collection does have
								//circulation, we need to index all records because the modification date does not change
								//when something checks in or out
								ArrayList<JSONObject> titlesToProcess = new ArrayList<>();

								for (int i = 0; i < responseTitles.length(); i++) {
									JSONObject curTitle = responseTitles.getJSONObject(i);
									String lastModified = curTitle.getJSONObject("metadata").getString("modified");
									try {
										Date lastModifiedDate = dateModifiedFormatter.parse(lastModified);
										//Give a 10-minute buffer
										if (doFullReload || (lastModifiedDate.getTime() / 1000 > (collection.lastIndexed - 60 * 10))) {
											titlesToProcess.add(curTitle);
										}else{
											//Titles are sorted by update date so we can jump out
											break;
										}
									} catch (ParseException e) {
										logEntry.incErrors("Could not parse date modified " + lastModified, e);
									}
								}
								numTitlesProcessed = titlesToProcess.size();
								updateTitlesInDB(collectionName, collection.id, titlesToProcess, titlesForCollection, doFullReload);
								if (!doFullReload) {
									//We only need to index records changed since the last time
									// the feed from Palace Project is sorted based on the time metadata or circulation changed
									try {
										JSONObject lastTitle = responseTitles.getJSONObject(responseTitles.length() - 1);
										String lastTitleModified = lastTitle.getJSONObject("metadata").getString("modified");
										try {
											Date lastTitleModifiedDate = dateModifiedFormatter.parse(lastTitleModified);
											//Give a 10-minute buffer for processing
											if (lastTitleModifiedDate.getTime() / 1000 < (collection.lastIndexed - 60 * 10)) {
												stopProcessingDueToLastUpdateTime = true;
											}
										} catch (ParseException e) {
											logEntry.incErrors("Could not parse date modified " + lastTitleModified, e);
										}
									}catch (Exception e) {
										logEntry.incErrors("Error determining if indexing should halt", e);
									}
								}
								logEntry.saveResults();
							}
						}
						collectionUrl = null;
						if (!stopProcessingDueToLastUpdateTime) {
							//Get the next URL
							if (collectionResponseJSON.has("links")) {
								JSONArray links = collectionResponseJSON.getJSONArray("links");
								for (int i = 0; i < links.length(); i++) {
									JSONObject curLink = links.getJSONObject(i);
									if (curLink.getString("rel").equals("next")) {
										collectionUrl = curLink.getString("href");
										break;
									}
								}
							}
						}
					} catch (JSONException e) {
						//This will just retry unless we are at the max number of attempts.
						//logEntry.incErrors("Unable to load titles from " + collectionUrl + ", response could not be parsed as JSON", e);
					}
				}
				numTries++;
			}
			if (numTries == 3 && !callSucceeded) {
				hadErrorsIndexing = true;
				logEntry.incErrors("Did not get a successful API response after 3 tries for " + collectionUrl);
				break;
			}
		}

		//Set last indexed for the collection
		if (!hadErrorsIndexing) {
			try {
				updateCollectionLastIndexedStmt.setLong(1, indexStartTime);
				updateCollectionLastIndexedStmt.setLong(2, collection.id);
				updateCollectionLastIndexedStmt.executeUpdate();
			} catch (Exception e) {
				logEntry.incErrors("Error updating collection last indexed time", e);
			}

			if (doFullReload) {
				//Remove availability for anything that we didn't see during this run
				try {
					getTitlesToRemoveFromCollectionStmt.setLong(1, collection.id);
					ResultSet titlesToRemoveFromCollectionRS = getTitlesToRemoveFromCollectionStmt.executeQuery();
					while (titlesToRemoveFromCollectionRS.next()) {
						removePalaceProjectTitleFromCollection(titlesToRemoveFromCollectionRS.getLong("id"), titlesToRemoveFromCollectionRS.getLong("titleId"));
					}
				} catch (Exception e) {
					logEntry.incErrors("Unable to remove titles from collection after indexing", e);
				}
			}
		}
		return numTitlesProcessed;
	}

	private HashMap<String, String> getValidCollectionsFromPalaceProject(JSONObject initialCrawlableResponseJSON, HashMap<String, PalaceProjectCollection> palaceProjectCollections, PreparedStatement insertCollectionStmt, long settingsId) throws SQLException {
		//Loop through facets to get a list of all collections for Palace Project
		HashMap<String, String> validCollections = new HashMap<>();
		if (initialCrawlableResponseJSON.has("facets")) {
			JSONArray facetList = initialCrawlableResponseJSON.getJSONArray("facets");
			for (int i = 0; i < facetList.length(); i++) {
				JSONObject curFacet = facetList.getJSONObject(i);
				if (curFacet.has("metadata")) {
					JSONObject facetMetadata = curFacet.getJSONObject("metadata");
					if (facetMetadata.getString("title").equals("Collection Name")) {
						JSONArray links = curFacet.getJSONArray("links");
						for (int j = 0; j < links.length(); j++) {
							JSONObject link = links.getJSONObject(j);
							String linkTitle = link.getString("title");
							if (linkTitle.equals("All") || linkTitle.contains("OverDrive") || linkTitle.contains("Axis 360") || linkTitle.contains("Boundless") || linkTitle.contains("Bibliotheca")) {
								continue;
							}
							validCollections.put(linkTitle, link.getString("href"));
							if (!palaceProjectCollections.containsKey(linkTitle)) {
								//Add the collection to the database
								insertCollectionStmt.setLong(1, settingsId);
								insertCollectionStmt.setString(2, linkTitle);
								insertCollectionStmt.setString(3, linkTitle);
								insertCollectionStmt.setBoolean(4, linkTitle.toLowerCase().contains("marketplace"));
								insertCollectionStmt.setBoolean(5, true);
								insertCollectionStmt.executeUpdate();
								ResultSet generatedKeys = insertCollectionStmt.getGeneratedKeys();
								if (generatedKeys.next()){
									long collectionId = generatedKeys.getLong(1);
									PalaceProjectCollection collection = new PalaceProjectCollection();
									collection.id = collectionId;
									collection.palaceProjectName = linkTitle;
									collection.displayName = linkTitle;
									collection.hasCirculation = linkTitle.toLowerCase().contains("marketplace");
									collection.includeInAspen = true;
									collection.settingId = settingsId;
									palaceProjectCollections.put(collection.palaceProjectName, collection);
								}
							}
						}
					}
				}
			}
		}
		return validCollections;
	}

	/**
	 * Processes collections that exist in Aspen but are no longer present in Palace Project API response.
	 * Removes titles from these collections and deletes the collection from the database.
	 *
	 * @param palaceProjectCollections Collections that exist in Aspen
	 * @param validCollections Collections that were found in the Palace Project API
	 * @param getTitlesForCollectionStmt Prepared statement to get titles for a collection
	 * @throws SQLException If a database error occurs
	 */
	private void processDeletedCollections(
		HashMap<String, PalaceProjectCollection> palaceProjectCollections,
		HashMap<String, String> validCollections,
		PreparedStatement getTitlesForCollectionStmt) throws SQLException {

		// Create defensive copies of the key sets to avoid modifying the original collections.
		// Directly modifying the keySet() of a HashMap, which is backed by the map itself,
		// can lead to unpredictable behavior in Java.
		List<String> allAspenCollections = new ArrayList<>(palaceProjectCollections.keySet());
		List<String> validSetCopy = new ArrayList<>(validCollections.keySet());
		allAspenCollections.removeAll(validSetCopy);

		for (String deletedCollectionName : allAspenCollections) {
			PalaceProjectCollection deletedColl = palaceProjectCollections.get(deletedCollectionName);
			logEntry.addNote("Deleting titles from deleted collection " + deletedCollectionName +
				", and removing the collection because it is no longer in the Palace Project.");
			HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection =
				getTitlesForCollection(getTitlesForCollectionStmt, deletedColl);
			for (PalaceProjectTitleAvailability titleAvailability : titlesForCollection.values()) {
				removePalaceProjectTitleFromCollection(titleAvailability.id, titleAvailability.titleId);
			}
			deleteCollectionStmt.setLong(1, deletedColl.id);
			deleteCollectionStmt.executeUpdate();
		}
	}

	private HashMap<Long, PalaceProjectTitleAvailability> getTitlesForCollection(PreparedStatement getTitlesForCollectionStmt, PalaceProjectCollection collection) {
		HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection = new HashMap<>();
		try {
			getTitlesForCollectionStmt.setLong(1, collection.id);
			ResultSet titlesForCollectionRS = getTitlesForCollectionStmt.executeQuery();
			while (titlesForCollectionRS.next()) {
				PalaceProjectTitleAvailability title = new PalaceProjectTitleAvailability();
				title.id = titlesForCollectionRS.getLong("id");
				title.titleId = titlesForCollectionRS.getLong("titleId");
				title.collectionId = titlesForCollectionRS.getLong("collectionId");
				title.lastSeen = titlesForCollectionRS.getLong("lastSeen");
				title.deleted = titlesForCollectionRS.getBoolean("deleted");
				title.borrowLink = titlesForCollectionRS.getString("borrowLink");
				title.needsHold = titlesForCollectionRS.getBoolean("needsHold");
				title.previewLink = titlesForCollectionRS.getString("previewLink");
				titlesForCollection.put(title.titleId, title);
			}
		}catch (SQLException e) {
			logEntry.incErrors("Unable to load titles for collection", e);
		}
		return titlesForCollection;
	}

	private void updateTitlesInDB(String collectionName, long collectionId, ArrayList<JSONObject> responseTitles, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, boolean doFullReload) {
		long indexTime = new Date().getTime() / 1000;
		logEntry.incNumProducts(responseTitles.size());
		for (JSONObject curTitle : responseTitles) {
			try {
				JSONObject curTitleMetadata = curTitle.getJSONObject("metadata");

				//The Metadata includes the library ID, we should replace the library ID with <<LibraryID>> when calculating length and checksum
				// to make the metadata library agnostic to not generate unnecessary updates.
				String rawResponse = curTitle.toString();
				String libraryAgnosticResponse = rawResponse.replaceAll(setting.getLibraryId(), "<<LibraryID>>");
				checksumCalculator.reset();
				checksumCalculator.update(libraryAgnosticResponse.getBytes());
				long rawChecksum = checksumCalculator.getValue();
				long rawResponseLength = libraryAgnosticResponse.length();

				String palaceProjectId = curTitleMetadata.getString("identifier");
				String title = curTitleMetadata.getString("title");

				getExistingPalaceProjectTitleStmt.setString(1, palaceProjectId);
				ResultSet getExistingPalaceProjectTitleRS = getExistingPalaceProjectTitleStmt.executeQuery();
				PalaceProjectTitle existingTitle = null;
				if (getExistingPalaceProjectTitleRS.next()) {
					existingTitle = new PalaceProjectTitle(
						getExistingPalaceProjectTitleRS.getLong("id"),
						palaceProjectId,
						getExistingPalaceProjectTitleRS.getLong("rawChecksum"),
						getExistingPalaceProjectTitleRS.getLong("rawResponseLength")
					);
				}
				boolean recordUpdated = false;
				if (existingTitle != null) {
					//Record exists
					if ((existingTitle.getChecksum() != rawChecksum) || (existingTitle.getRawResponseLength() != rawResponseLength)){
						recordUpdated = true;
						logEntry.incUpdated();
					}
					existingTitle.setFoundInExport(true);
				}else{
					recordUpdated = true;
					logEntry.incAdded();
				}

				if (title.length() > 750) {
					title = title.substring(0, 750);
				}

				boolean regroupAndIndexRecord = false;
				long titleId = -1;
				if (existingTitle == null){
					addPalaceProjectTitleToDbStmt.setString(1, palaceProjectId);
					addPalaceProjectTitleToDbStmt.setString(2, title);
					addPalaceProjectTitleToDbStmt.setLong(3, rawChecksum);
					addPalaceProjectTitleToDbStmt.setLong(4, rawResponseLength);
					addPalaceProjectTitleToDbStmt.setString(5, rawResponse);
					addPalaceProjectTitleToDbStmt.setLong(6, startTimeForLogging);
					try {
						addPalaceProjectTitleToDbStmt.executeUpdate();

						ResultSet generatedKeys = addPalaceProjectTitleToDbStmt.getGeneratedKeys();
						long palaceProjectAspenId = -1;
						if (generatedKeys.next()){
							palaceProjectAspenId = generatedKeys.getLong(1);
						}else{
							logEntry.incErrors("Could not add " + palaceProjectId + " to the database, did not get the Aspen ID back");
						}

						//Update availability
						titleId = palaceProjectAspenId;
						updatePalaceProjectTitleAvailability(curTitle, collectionId, titlesForCollection, indexTime, palaceProjectAspenId);

						regroupAndIndexRecord = true;
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + palaceProjectId + " " + title + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error adding Palace Project title to database record " + palaceProjectId + " " + title + " " + collectionName, e);
					}
				}else if (recordUpdated || doFullReload){
					updatePalaceProjectTitleInDbStmt.setString(1, title);
					updatePalaceProjectTitleInDbStmt.setLong(2, rawChecksum);
					updatePalaceProjectTitleInDbStmt.setLong(3, rawResponseLength);
					updatePalaceProjectTitleInDbStmt.setString(4, rawResponse);
					updatePalaceProjectTitleInDbStmt.setLong(5, existingTitle.getId());
					regroupAndIndexRecord = true;
					titleId = existingTitle.getId();
					try {
						updatePalaceProjectTitleInDbStmt.executeUpdate();
						updatePalaceProjectTitleAvailability(curTitle, collectionId, titlesForCollection, indexTime, titleId);
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + palaceProjectId + " " + title + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error updating Palace Project data in database for record " + palaceProjectId + " " + title, e);
					}
				} else {
					//Update availability
					titleId = existingTitle.getId();
					regroupAndIndexRecord = updatePalaceProjectTitleAvailability(curTitle, collectionId, titlesForCollection, indexTime, titleId);
				}

				if (titleId > 0) {
					//The title saved properly
					if (regroupAndIndexRecord) {
						//We need to reindex the title because the actual title updated or availability changed.
						String groupedWorkId =  getRecordGroupingProcessor().groupPalaceProjectRecord(curTitle, titleId);
						indexRecord(groupedWorkId);
					}else{
						logEntry.incSkipped();
					}
				}

			}catch (Exception e){
				logEntry.incErrors("Error updating palace project data", e);
			}
		}
		getGroupedWorkIndexer().commitChanges();
	}

	private boolean updatePalaceProjectTitleAvailability(JSONObject curTitle, long collectionId, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, long indexTime, long titleId) throws SQLException {
		boolean availabilityChanged = false;
		//We might not have availability yet if this title exists in another collection or settings and is new to this collection
		String borrowLink = "";
		String previewLink = "";
		boolean needsHold = false;

		JSONArray links = curTitle.getJSONArray("links");
		for (int i = 0; i < links.length(); i++) {
			JSONObject curLink = links.getJSONObject(i);
			String rel = curLink.getString("rel");
			if (rel.equals("http://opds-spec.org/acquisition/borrow")){
				borrowLink = curLink.getString("href");
				if (curLink.has("properties")) {
					JSONObject properties = curLink.getJSONObject("properties");
					if (properties.has("availability")) {
						String state = properties.getJSONObject("availability").getString("state");
						if (!state.equals("available")) {
							needsHold = true;
						}
					}
				}
			}else if (rel.equals("preview") && curLink.getString("type").equals("text/html")) {
				previewLink = curLink.getString("href");
			}
		}

		if (titlesForCollection.containsKey(titleId)){
			PalaceProjectTitleAvailability existingAvailability = titlesForCollection.get(titleId);
			//availability was deleted, need to restore and reindex
			if (existingAvailability.deleted || needsHold != existingAvailability.needsHold) {
				availabilityChanged = true;
			}
			updatePalaceProjectAvailabilityStmt.setLong(1, indexTime);
			updatePalaceProjectAvailabilityStmt.setString(2, borrowLink);
			updatePalaceProjectAvailabilityStmt.setBoolean(3, needsHold);
			updatePalaceProjectAvailabilityStmt.setString(4, previewLink);
			updatePalaceProjectAvailabilityStmt.setLong(5, titleId);
			updatePalaceProjectAvailabilityStmt.setLong(6, collectionId);
			updatePalaceProjectAvailabilityStmt.executeUpdate();
		}else{
			//Add availability for the title within the collection
			addPalaceProjectAvailabilityStmt.setLong(1, titleId);
			addPalaceProjectAvailabilityStmt.setLong(2, collectionId);
			addPalaceProjectAvailabilityStmt.setLong(3, indexTime);
			addPalaceProjectAvailabilityStmt.setString(4, borrowLink);
			addPalaceProjectAvailabilityStmt.setBoolean(5, needsHold);
			addPalaceProjectAvailabilityStmt.setString(6, previewLink);
			addPalaceProjectAvailabilityStmt.executeUpdate();
			availabilityChanged = true;
		}
		return availabilityChanged;
	}

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
			if (!groupedWorkIndexer.isOkToIndex()) {
				logEntry.incErrors("Indexer could not be initialized properly");
				logEntry.saveResults();
				System.exit(1);
			}
		}
		return groupedWorkIndexer;
	}

	private void indexRecord(String groupedWorkId) {
		getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
	}

	private RecordGroupingProcessor getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}
}
