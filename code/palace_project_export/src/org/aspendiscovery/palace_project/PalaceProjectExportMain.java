package org.aspendiscovery.palace_project;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;

import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.aspen_discovery.reindexer.PalaceProjectTitleAvailability;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.*;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;


public class PalaceProjectExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static PalaceProjectExportLogEntry logEntry;
	private static String palaceProjectBaseUrl;

	private static Connection aspenConn;
	private static PreparedStatement getExistingPalaceProjectTitleStmt;
	private static PreparedStatement addPalaceProjectTitleToDbStmt;
	private static PreparedStatement updatePalaceProjectTitleInDbStmt;
	private static PreparedStatement deletePalaceProjectTitleFromDbStmt;
	private static PreparedStatement addPalaceProjectAvailabilityStmt;
	private static PreparedStatement updatePalaceProjectAvailabilityStmt;
	private static PreparedStatement deletePalaceProjectAvailabilityStmt;
	private static PreparedStatement updateCollectionLastIndexedStmt;
	private static PreparedStatement getAvailabilityForTitleStmt;
	private static PreparedStatement getTitlesToRemoveFromCollectionStmt;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//For Checksums
	private static final CRC32 checksumCalculator = new CRC32();

	public static void main(String[] args){
		boolean extractSingleWork = false;
		String singleWorkId = null;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
			}
		} else {
			serverName = args[0];
			if (args.length > 1){
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")){
					extractSingleWork = true;
					if (args.length > 2) {
						singleWorkId = args[2];
					}
				}
			}
		}
		if (extractSingleWork && singleWorkId == null) {
			singleWorkId = AspenStringUtils.getInputFromCommandLine("Enter the id of the title to extract (will start with urn:)");
		}

		String processName = "palace_project_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			//Palace Project only needs to run once a day
			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime + ": Starting Palace Project Export");

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the Aspen database
			aspenConn = connectToDatabase();

			//Check to see if the jar has changes before processing records, and if so quit
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

			//Start a log entry
			createDbLogEntry(startTime, aspenConn);
			logEntry.addNote("Starting extract");
			logEntry.saveResults();

			//Do work here
			boolean updatesRun;
			if (singleWorkId == null) {
				updatesRun = exportPalaceProjectData();
			} else {
				//exportSinglePalaceProjectTitle(singleWorkId);
				System.out.println("Palace Project does not currently support extracting individual records.");
				updatesRun = true;
			}

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

			//Mark that indexing has finished
			logEntry.setFinished();

			if (!updatesRun) {
				//delete the log entry
				try {
					PreparedStatement deleteLogEntryStmt = aspenConn.prepareStatement("DELETE from palace_project_export_log WHERE id = " + logEntry.getLogEntryId());
					deleteLogEntryStmt.executeUpdate();
				} catch (SQLException e) {
					logger.error("Could not delete log export ", e);
				}

			}

			if (extractSingleWork) {
				disconnectDatabase(aspenConn);
				break;
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
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase(aspenConn);
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
				logger.info("Ending because we have low memory available");
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
					Thread.sleep(1000 * 60 * 15);
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}

		System.exit(0);
	}

	private static boolean exportPalaceProjectData() {
		boolean updatesRun = false;
		try{
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from palace_project_settings");
			PreparedStatement getCollectionsForSettingStmt = aspenConn.prepareStatement("SELECT * from palace_project_collections where settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement insertCollectionStmt = aspenConn.prepareStatement("INSERT INTO palace_project_collections (settingId, palaceProjectName, displayName, hasCirculation, includeInAspen) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement getTitlesForCollectionStmt = aspenConn.prepareStatement("SELECT * FROM palace_project_title_availability where collectionId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				long settingsId = getSettingsRS.getLong("id");
				numSettings++;

				//Setup times that we'll use later to determine if we need to index a collection
				long nowInSeconds = new Date().getTime() / 1000;
				long yesterdayInSeconds = nowInSeconds - 24 * 60 * 60;

				logEntry.addNote("Starting update from Palace Project");
				logEntry.saveResults();

				palaceProjectBaseUrl = getSettingsRS.getString("apiUrl");
				String palaceProjectLibraryId = getSettingsRS.getString("libraryId");
				boolean doFullReload = getSettingsRS.getBoolean("runFullUpdate");

				//Get a list of collections within Aspen
				HashMap<String, PalaceProjectCollection> palaceProjectCollections = getExistingCollectionsInAspenForSetting(getCollectionsForSettingStmt, settingsId);

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
					HashMap<String, String> validCollections = getValidCollectionsFromPalaceProject(initialCrawlableResponseJSON, palaceProjectCollections, insertCollectionStmt, settingsId);

					for (String collectionName : validCollections.keySet()) {
						//Index the collection if the collection has circulation or the collection has not been updated for 24 hours
						PalaceProjectCollection collection = palaceProjectCollections.get(collectionName);
						//Get a list of all titles for this collection
						HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection = getTitlesForCollection(getTitlesForCollectionStmt, collection);

						if (collection.includeInAspen) {
							if (collection.hasCirculation || collection.lastIndexed < yesterdayInSeconds) {
								extractRecordsForPalaceProjectCollection(collectionName, validCollections, headers, collection, titlesForCollection, doFullReload, nowInSeconds);
							}else{
								//Not time to index, leave things as is.
							}
						}else{
							//Remove all currently indexed products from solr
							for (PalaceProjectTitleAvailability titleAvailability : titlesForCollection.values()) {
								if (!titleAvailability.deleted) {
									removePalaceProjectTitleFromCollection(titleAvailability.id, titleAvailability.titleId, titleAvailability.collectionId);

								}
							}
						}
					}
				}

				updatesRun = true;

				//Set the extract time
				setLastUpdateTimeForSetting(doFullReload, settingsId);
			}
			if (numSettings == 0){
				logger.error("Unable to find settings for Palace Project, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting Palace Project data", e);
		}
		return updatesRun;
	}

	private static void removePalaceProjectTitleFromCollection(long availabilityId, long titleId, long collectionId) throws SQLException {
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
				getGroupedWorkIndexer().deleteRecord(result.permanentId);
			}
		}
	}

	private static HashMap<String, PalaceProjectCollection> getExistingCollectionsInAspenForSetting(PreparedStatement getCollectionsForSettingStmt, long settingsId) throws SQLException {
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

	private static void setLastUpdateTimeForSetting(boolean doFullReload, long settingsId) throws SQLException {
		PreparedStatement updateSettingsStmt = null;
		if (doFullReload){
			if (!logEntry.hasErrors()) {
				updateSettingsStmt = aspenConn.prepareStatement("UPDATE palace_project_settings set lastUpdateOfAllRecords = ? where id = ?");
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

	private static void extractRecordsForPalaceProjectCollection(String collectionName, HashMap<String, String> validCollections, HashMap<String, String> headers, PalaceProjectCollection collection, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, boolean doFullReload, long indexStartTime) {
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
					//This will just retry unless we are at max number of attempts.
					//logEntry.incErrors("Could not get titles from " + collectionUrl + " " + responseForCollection.getMessage());
				} else {
					try {
						JSONObject collectionResponseJSON = new JSONObject(responseForCollection.getMessage());
						callSucceeded = true;
						if (collectionResponseJSON.has("publications")) {
							JSONArray responseTitles = collectionResponseJSON.getJSONArray("publications");
							if (responseTitles != null && !responseTitles.isEmpty()) {
								updateTitlesInDB(collectionName, collection.id, responseTitles, titlesForCollection, doFullReload);
								logEntry.saveResults();
							}
						}
						collectionUrl = null;
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
					} catch (JSONException e) {
						//This will just retry unless we are at max number of attempts.
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

			//Remove availability for anything that we didn't see during this run
			try {
				getTitlesToRemoveFromCollectionStmt.setLong(1, collection.id);
				ResultSet titlesToRemoveFromCollectionRS = getTitlesToRemoveFromCollectionStmt.executeQuery();
				while (titlesToRemoveFromCollectionRS.next()) {
					removePalaceProjectTitleFromCollection(titlesToRemoveFromCollectionRS.getLong("id"), titlesToRemoveFromCollectionRS.getLong("titleId"), collection.id);
				}
			}catch (Exception e) {
				logEntry.incErrors("Unable to remove titles from collection after indexing", e);
			}
		}
	}

	private static HashMap<String, String> getValidCollectionsFromPalaceProject(JSONObject initialCrawlableResponseJSON, HashMap<String, PalaceProjectCollection> palaceProjectCollections, PreparedStatement insertCollectionStmt, long settingsId) throws SQLException {
		//Loop through facets to get a list of all collections for palace project
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

	private static HashMap<Long, PalaceProjectTitleAvailability> getTitlesForCollection(PreparedStatement getTitlesForCollectionStmt, PalaceProjectCollection collection) {
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
				titlesForCollection.put(title.titleId, title);
			}
		}catch (SQLException e) {
			logEntry.incErrors("Unable to load titles for collection", e);
		}
		return titlesForCollection;
	}

	private static void updateTitlesInDB(String collectionName, long collectionId, JSONArray responseTitles, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, boolean doFullReload) {
		long indexTime = new Date().getTime() / 1000;
		logEntry.incNumProducts(responseTitles.length());
		for (int i = 0; i < responseTitles.length(); i++){
			try {
				JSONObject curTitle = responseTitles.getJSONObject(i);
				JSONObject curTitleMetadata = curTitle.getJSONObject("metadata");

				String rawResponse = curTitle.toString();
				checksumCalculator.reset();
				checksumCalculator.update(rawResponse.getBytes());
				long rawChecksum = checksumCalculator.getValue();

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
					if ((existingTitle.getChecksum() != rawChecksum) || (existingTitle.getRawResponseLength() != rawResponse.length())){
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
					addPalaceProjectTitleToDbStmt.setString(4, rawResponse);
					addPalaceProjectTitleToDbStmt.setLong(5, startTimeForLogging);
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
						updatePalaceProjectTitleAvailability(collectionId, titlesForCollection, indexTime, palaceProjectAspenId);

						regroupAndIndexRecord = true;
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + palaceProjectId + " " + title + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error adding Palace Project title to database record " + palaceProjectId + " " + title + " " + collectionName, e);
					}
				}else if (recordUpdated || doFullReload){
					updatePalaceProjectTitleInDbStmt.setString(1, title);
					updatePalaceProjectTitleInDbStmt.setLong(2, rawChecksum);
					updatePalaceProjectTitleInDbStmt.setString(3, rawResponse);
					updatePalaceProjectTitleInDbStmt.setLong(4, existingTitle.getId());
					regroupAndIndexRecord = true;
					titleId = existingTitle.getId();
					try {
						updatePalaceProjectTitleInDbStmt.executeUpdate();
						updatePalaceProjectTitleAvailability(collectionId, titlesForCollection, indexTime, titleId);
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + palaceProjectId + " " + title + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error updating Palace Project data in database for record " + palaceProjectId + " " + title, e);
					}
				} else {
					//Update availability
					titleId = existingTitle.getId();
					regroupAndIndexRecord = updatePalaceProjectTitleAvailability(collectionId, titlesForCollection, indexTime, titleId);
				}

				if (regroupAndIndexRecord && titleId > 0) {
					String groupedWorkId =  getRecordGroupingProcessor().groupPalaceProjectRecord(curTitle, titleId);
					indexRecord(groupedWorkId);
				}
			}catch (Exception e){
				logEntry.incErrors("Error updating palace project data", e);
			}
		}
		getGroupedWorkIndexer().commitChanges();
	}

	private static boolean updatePalaceProjectTitleAvailability(long collectionId, HashMap<Long, PalaceProjectTitleAvailability> titlesForCollection, long indexTime, long titleId) throws SQLException {
		boolean availabilityChanged = false;
		//We might not have availability already if this title exists in another collection or settings and is new to this collection
		if (titlesForCollection.containsKey(titleId)){
			PalaceProjectTitleAvailability existingAvailability = titlesForCollection.get(titleId);
			//availability was deleted, need to restore and reindex
			if (existingAvailability.deleted) {
				availabilityChanged = true;
			}
			updatePalaceProjectAvailabilityStmt.setLong(1, indexTime);
			updatePalaceProjectAvailabilityStmt.setLong(2, titleId);
			updatePalaceProjectAvailabilityStmt.setLong(3, collectionId);
			updatePalaceProjectAvailabilityStmt.executeUpdate();
		}else{
			//Add availability for the title within the collection
			addPalaceProjectAvailabilityStmt.setLong(1, titleId);
			addPalaceProjectAvailabilityStmt.setLong(2, collectionId);
			addPalaceProjectAvailabilityStmt.setLong(3, indexTime);
			addPalaceProjectAvailabilityStmt.executeUpdate();
			availabilityChanged = true;
		}
		return availabilityChanged;
	}

	@SuppressWarnings("unused")
	private static void exportSinglePalaceProjectTitle(String singleWorkId) {
		try{
			logEntry.addNote("Doing extract of single work " + singleWorkId);
			logEntry.saveResults();

			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from palace_project_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				palaceProjectBaseUrl = getSettingsRS.getString("apiUrl");
				String palaceProjectLibraryId = getSettingsRS.getString("libraryId");

//				String url = palaceProjectBaseUrl + "/" + palaceProjectLibraryId + "/crawlable";
//				HashMap<String, String> headers = new HashMap<>();
//				headers.put("Accept", "application/opds+json");
//				headers.put("User-Agent", "Aspen Discovery");
//				WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
//				if (!response.isSuccess()){
//					logEntry.incErrors("Could not get titles from " + url + " " + response.getMessage());
//				}else {
//					JSONObject responseJSON = new JSONObject(response.getMessage());
//					if (responseJSON.has("publications")) {
//						JSONArray responseTitles = responseJSON.getJSONArray("publications");
//						if (responseTitles != null && !responseTitles.isEmpty()) {
//							//updateTitlesInDB(responseTitles, false);
//							logEntry.saveResults();
//						}
//					}
//				}
			}
			if (numSettings == 0){
				logger.error("Unable to find settings for Palace Project, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting Palace Project data", e);
		}
	}

	private static Connection connectToDatabase(){
		Connection aspenConn = null;
		try{
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);

				addPalaceProjectTitleToDbStmt = aspenConn.prepareStatement("INSERT INTO palace_project_title (palaceProjectId, title, rawChecksum, rawResponse, dateFirstDetected) VALUES (?, ?, ?, COMPRESS(?), ?)", PreparedStatement.RETURN_GENERATED_KEYS);
				getExistingPalaceProjectTitleStmt = aspenConn.prepareStatement("SELECT id, rawChecksum, UNCOMPRESSED_LENGTH(rawResponse) as rawResponseLength from palace_project_title where palaceProjectId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				updatePalaceProjectTitleInDbStmt = aspenConn.prepareStatement("UPDATE palace_project_title set title = ?, rawChecksum = ?, rawResponse = COMPRESS(?) WHERE id = ?");
				deletePalaceProjectTitleFromDbStmt = aspenConn.prepareStatement("DELETE FROM palace_project_title where id = ?");
				addPalaceProjectAvailabilityStmt = aspenConn.prepareStatement("INSERT INTO palace_project_title_availability (titleId, collectionId, lastSeen, deleted) VALUES (?, ?, ?, 0)");
				updatePalaceProjectAvailabilityStmt = aspenConn.prepareStatement("UPDATE palace_project_title_availability SET lastSeen = ?, deleted = 0 WHERE titleId = ? AND collectionId = ?");
				deletePalaceProjectAvailabilityStmt = aspenConn.prepareStatement("UPDATE palace_project_title_availability SET deleted = 1 WHERE id = ?");
				updateCollectionLastIndexedStmt = aspenConn.prepareStatement("UPDATE palace_project_collections SET lastIndexed = ? where id =?");
				getAvailabilityForTitleStmt = aspenConn.prepareStatement("SELECT COUNT(*) as availabilityCount from palace_project_title_availability WHERE titleId = ? and deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getTitlesToRemoveFromCollectionStmt = aspenConn.prepareStatement("SELECT palace_project_title_availability.id, titleId FROM palace_project_title_availability inner JOIN palace_project_collections on collectionId = palace_project_collections.id where collectionId = ? AND lastSeen < lastIndexed", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}else{
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}
		}catch (Exception e){
			logger.error("Error connecting to Aspen database " + e);
			System.exit(1);
		}
		return aspenConn;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try{
			addPalaceProjectTitleToDbStmt.close();
			getExistingPalaceProjectTitleStmt.close();
			updatePalaceProjectTitleInDbStmt.close();
			deletePalaceProjectTitleFromDbStmt.close();

			aspenConn.close();
			//noinspection UnusedAssignment
			aspenConn = null;
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from palace_project_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		logEntry = new PalaceProjectExportLogEntry(aspenConn, logger);
	}

	private static void processRecordsToReload(PalaceProjectExportLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='palace_project'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from palace_project_title where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getIdForPalaceProjectIdStmt = aspenConn.prepareStatement("SELECT id from palace_project_title where palaceProjectId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String rawPalaceProjectId = getRecordsToReloadRS.getString("identifier");
				long palaceProjectId;
				if (AspenStringUtils.isNumeric(rawPalaceProjectId)) {
					palaceProjectId = Long.parseLong(rawPalaceProjectId);
				}else{
					getIdForPalaceProjectIdStmt.setString(1, rawPalaceProjectId);
					ResultSet getIdForPalaceProjectIdRS = getIdForPalaceProjectIdStmt.executeQuery();
					if (getIdForPalaceProjectIdRS.next()) {
						palaceProjectId = getIdForPalaceProjectIdRS.getLong("id");
						getIdForPalaceProjectIdRS.close();
					}else{
						logEntry.addNote("Could not get details for record to reload " + rawPalaceProjectId + " it has been deleted");
						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
						getIdForPalaceProjectIdRS.close();
						continue;
					}
				}
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
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + palaceProjectId, e);
					}
				}else{
					//The record has likely been deleted
					logEntry.addNote("Could not get details for record to reload " + palaceProjectId + " it has been deleted");
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

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
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

	private static void indexRecord(String groupedWorkId) {
		getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
	}

	private static RecordGroupingProcessor getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}
}
