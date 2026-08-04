package com.turning_leaf_technologies.hoopla;

import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.apache.commons.lang3.StringUtils;

import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.time.ZonedDateTime;
import java.time.temporal.ChronoUnit;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

public class HooplaExporter {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static HooplaExtractLogEntry logEntry;

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
	private static final CRC32 checksumCalculator = new CRC32();

	//For 32 hours catch up
	private static int numRetries32HoursAfter = 0;


	public HooplaExporter(String serverName, Connection aspenConn, Ini configIni, HooplaExtractLogEntry logEntry, Logger logger) throws SQLException {
		this.serverName = serverName;
		this.aspenConn = aspenConn;
		this.configIni = configIni;
		this.logEntry = logEntry;
		this.logger = logger;

		Date startTime = new Date();
		startTimeForLogging = startTime.getTime() / 1000;

		try {
			getAllExistingHooplaItemsStmt = aspenConn.prepareStatement("SELECT id, hooplaId, rawChecksum, active, UNCOMPRESSED_LENGTH(rawResponse) as rawResponseLength, hooplaType from hoopla_export");
			addHooplaTitleToDB = aspenConn.prepareStatement("INSERT INTO hoopla_export (hooplaId, active, title, kind, pa, demo, profanity, rating, abridged, children, price, rawChecksum, rawResponse, dateFirstDetected, hooplaType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,COMPRESS(?),?, ?) ");
			updateHooplaTitleInDB = aspenConn.prepareStatement("UPDATE hoopla_export set active = ?, title = ?, kind = ?, pa = ?, demo = ?, profanity = ?, rating = ?, abridged = ?, children = ?, price = ?, rawChecksum = ?, rawResponse = COMPRESS(?), hooplaType = ? where id = ?");
			deleteHooplaItemStmt = aspenConn.prepareStatement("DELETE FROM hoopla_export where id = ?");
		} catch (SQLException e) {
			logger.error("Error preparing Hoopla exporter statements", e);
			logEntry.addNote("Error preparing Hoopla exporter statements" + e);
		}
		//Get a list of all existing records in the database
		loadExistingTitles();

		processRecordsToReload(logEntry);

	}

	private static void processRecordsToReload(HooplaExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='hoopla'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT hooplaType FROM hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			int numInstantRecords = 0;
			int numFlexRecords = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordId = getRecordsToReloadRS.getString("identifier");
				long hooplaId = Long.parseLong(StringUtils.replace(recordId,"MWT", ""));
				//Regroup the record
				getItemDetailsForRecordStmt.setLong(1, hooplaId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String hooplaType = getItemDetailsForRecordRS.getString("hooplaType");
					try {
						//Refetch the record from Hoopla
						exportSingleHooplaTitle(recordId, hooplaType);

						if (hooplaType != null && hooplaType.equalsIgnoreCase("Flex")){
							numFlexRecords++;
						} else {
							numInstantRecords++;
						}

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + hooplaId, e);
					}
				}else{
					//The record has likely been deleted
					logEntry.addNote("Could not get details for Hoopla record to reload " + hooplaId + " it has been deleted");
					markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
					markRecordToReloadAsProcessedStmt.executeUpdate();
					numRecordsToReloadProcessed++;
				}
				getItemDetailsForRecordRS.close();
			}
			if (numRecordsToReloadProcessed > 0){
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
				logEntry.addNote("Regrouped " + numInstantRecords + " Instant records");
				logEntry.addNote("Regrouped " + numFlexRecords + " Flex records");
			}else{
				logEntry.addNote("No records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static void deleteItems(String hooplaType) {
		int numDeleted = 0;
		try {
			for (HooplaTitle hooplaTitle : existingRecords.values()) {
				if (hooplaTitle.getHooplaType().equalsIgnoreCase(hooplaType) && !hooplaTitle.isFoundInExport() && hooplaTitle.isActive()) {
					deleteHooplaItemStmt.setLong(1, hooplaTitle.getId());
					deleteHooplaItemStmt.executeUpdate();
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", Long.toString(hooplaTitle.getHooplaId()));

					if (hooplaType.equalsIgnoreCase("Flex")){
						PreparedStatement deleteFlexAvailabilityStmt = aspenConn.prepareStatement("DELETE from hoopla_flex_availability where hooplaId = ?");
						deleteFlexAvailabilityStmt.setLong(1, hooplaTitle.getHooplaId());
						deleteFlexAvailabilityStmt.executeUpdate();
					}

					if (result.reindexWork){
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					}else if (result.deleteWork){
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					existingRecords.remove(hooplaTitle.getHooplaId());
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old " + hooplaType + " titles");
			}
		}catch (SQLException e) {
			logger.error("Error deleting " + hooplaType + " items", e);
			logEntry.addNote("Error deleting " + hooplaType + " items " + e);
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
						allRecordsRS.getLong("rawResponseLength"),
						allRecordsRS.getString("hooplaType")
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
			logEntry.addNote("Error loading existing titles" + e);
			System.exit(-1);
		}
	}


	public boolean exportHooplaData() {
		boolean updatesRun = false;
		try{
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from hoopla_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				HooplaSettings settings = new HooplaSettings(getSettingsRS);
				numSettings++;

				// Process Instant Content
				boolean hooplaInstantEnabled = settings.isHooplaEnabled("Instant");
				if (hooplaInstantEnabled) {
					boolean instantUpdated = exportHooplaContent(settings, "Instant");
					updatesRun |= instantUpdated;
				}

				// Process Flex Content
				boolean hooplaFlexEnabled = settings.isHooplaEnabled("Flex");
				if (hooplaFlexEnabled) {
					boolean flexUpdated = exportHooplaContent(settings, "Flex");
					boolean availabilityUpdated = getFlexAvailability(settings);
					updatesRun |= flexUpdated || availabilityUpdated;
				}
				if (settings.isRegroupAllRecords()) {
					regroupAllRecords(aspenConn, settings.getSettingsId(), getGroupedWorkIndexer(), logEntry);
				}

			}
			if (numSettings == 0){
				logger.error("Unable to find settings for Hoopla, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting hoopla data", e);
		}
		return updatesRun;
	}


	private static boolean exportHooplaContent(HooplaSettings settings, String hooplaType) {
		boolean updatedContent = false;
		boolean doFullReload = settings.isRunFullUpdate(hooplaType);
		long settingsId = settings.getSettingsId();
		String hooplaAPIBaseURL = settings.getApiUrl();
		int hooplaLibraryId = settings.getLibraryId();
		long lastUpdateOfChangedRecords = settings.getLastUpdateOfChangedRecords(hooplaType);
		long lastUpdateOfAllRecords = settings.getLastUpdateOfAllRecords(hooplaType);
		long lastUpdate = Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
		String purchaseModel = hooplaType.equals("Instant") ? "PPU" : "EST";
		int numRecordsToExtract = 0;

		String accessToken = settings.getAccessToken();
		long tokenExpirationTime = settings.getTokenExpirationTime();
		int indexingTime = settings.getIndexingTime();

		if (accessToken == null || tokenExpirationTime < (System.currentTimeMillis() / 1000)) {
			accessToken = getAccessToken(settings);
		}

		if (accessToken == null) {
			logEntry.incErrors("Could not load access token");
			return true;
		}

		logEntry.addNote("Starting " + hooplaType + " content extraction using a batch size of " + settings.getRecordExtractionBatchSize() + " at " + indexingTime);
		logEntry.saveResults();

		try {
			if (doFullReload){
				//Unset that a full update needs to be done
				PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set runFullUpdate" + hooplaType + " = 0 where id = ?");
				updateSettingsStmt.setLong(1, settingsId);
				updateSettingsStmt.executeUpdate();
				logEntry.addNote("Processing full update for " + hooplaType);
			} else {
				//We only want to index once a day at 1 am Local Time
				ZonedDateTime nowLocalTime = ZonedDateTime.now();
				int curHour = nowLocalTime.getHour();
				ZonedDateTime startOfToday = nowLocalTime.truncatedTo(ChronoUnit.DAYS);
				long startOfTodaySeconds = startOfToday.toEpochSecond();
				ZonedDateTime thirtyTwoHoursAgoTime = nowLocalTime.minusHours(32);
				long thirtyTwoHoursAgo = thirtyTwoHoursAgoTime.toInstant().getEpochSecond();

				if (curHour == indexingTime){
					if (lastUpdateOfChangedRecords >= startOfTodaySeconds) {
						logger.warn("Already completed today's " + hooplaType + " extraction at " + indexingTime + ". Skipping until tomorrow.");
						return false;
					}
					//Set the last update time to 32 hours ago (go bigger to get more updates)
					if (thirtyTwoHoursAgo < lastUpdate){
						lastUpdate = thirtyTwoHoursAgo;
					}
					numRetries32HoursAfter = 0;
					logEntry.addNote("Starting daily " + hooplaType + " content extraction");
				}else{
					//It's not 1 am Local time, skip for now.
					//Figure out when we last indexed this collection.
					if (lastUpdate >= thirtyTwoHoursAgo) {
						//Do not index unless it has been 32 hours
						return false;
					}
					// If we don't have updates for 32 hours, we will try 3 times
					// If we exceed 3 times and fail, we will wait until 1 AM
					if (numRetries32HoursAfter >= 3){
						logger.warn("Exceeded 3 retries for 32 hours catch up, waiting until next indexing time at " + indexingTime);
						return false;
					}
					numRetries32HoursAfter++;
					logEntry.addNote("Retrying " + hooplaType + " extraction after 32 hours " + numRetries32HoursAfter + " of 3");
				}
			}

			updatedContent = true;

			//Formulate the first call depending on if we are doing a full reload or not
			String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content";
			if (!doFullReload && lastUpdate > 0) {
				//Give a 2-minute buffer for the extract
				lastUpdate -= 120;
				logEntry.addNote("Extracting records since " + new Date(lastUpdate * 1000));
				url += "?startTime=" + lastUpdate + "&limit=" + settings.getRecordExtractionBatchSize() + "&purchaseModel=" + purchaseModel;
			} else {
				url += "?limit=" + settings.getRecordExtractionBatchSize() + "&purchaseModel=" + purchaseModel;
			}

			@SuppressWarnings("DuplicatedCode")
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", "Bearer " + accessToken);
			headers.put("Content-Type", "application/json");
			headers.put("Accept", "application/json");
			WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
			if (!response.isSuccess()){
				logEntry.incErrors("Could not get titles from " + url + " " + response.getMessage() + " " + response.getResponseCode());
				return false;
			}else {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				if (responseJSON.has("titles")) {
					JSONArray responseTitles = responseJSON.getJSONArray("titles");
					if (responseTitles != null && !responseTitles.isEmpty()) {
						updateTitlesInDB(responseTitles, false, doFullReload, hooplaType);
						numRecordsToExtract += responseTitles.length();
						logEntry.saveResults();
					}

					String startToken = null;
					if (responseJSON.has("nextStartToken")) {
						startToken = responseJSON.get("nextStartToken").toString();
					}

					int numTries = 0;
					while (startToken != null) {
						if (!doFullReload && lastUpdate > 0) {
							url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content?startTime=" + lastUpdate + "&startToken=" + startToken + "&limit=" + settings.getRecordExtractionBatchSize() + "&purchaseModel=" + purchaseModel;
						}else {
							url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content?startToken=" + startToken + "&limit=" + settings.getRecordExtractionBatchSize() + "&purchaseModel=" + purchaseModel;
						}
						response = NetworkUtils.getURL(url, logger, headers);
						if (response.isSuccess()){
							responseJSON = new JSONObject(response.getMessage());
							if (responseJSON.has("titles")) {
								responseTitles = responseJSON.getJSONArray("titles");
								if (responseTitles != null && !responseTitles.isEmpty()) {
									updateTitlesInDB(responseTitles, false, doFullReload, hooplaType);
									numRecordsToExtract += responseTitles.length();
								}
							}
							if (responseJSON.has("nextStartToken")) {
								startToken = responseJSON.get("nextStartToken").toString();
							} else {
								startToken = null;
							}
						}else{
							if (response.getResponseCode() == 401 || response.getResponseCode() == 504 || response.getResponseCode() == 503){
								numTries++;
								if (numTries >= 3){
									logEntry.incErrors("Error loading data after 3 attempts for " + hooplaType + " from" + url + " " + response.getResponseCode() + " " + response.getMessage());
									startToken = null;
								}else{
									try {
										Thread.sleep(1000 * 60 * 2); //Wait for 2 minutes before trying again
									} catch (InterruptedException e) {
										logEntry.incErrors("Error sleeping for 2 minutes", e);
									}
									accessToken = getAccessToken(settings);
									headers.put("Authorization", "Bearer " + accessToken);
								}
							}else {
								logEntry.incErrors("Error loading data for " + hooplaType + " from " + url + " " + response.getResponseCode() + " " + response.getMessage());
								startToken = null;
							}
						}

						logEntry.saveResults();
					}
					logEntry.addNote("Completed " + numRecordsToExtract + " " + hooplaType + " updates");
					logEntry.saveResults();
				}
			}

			//Delete records from Hoopla, but not if we have errors
			if (doFullReload && !logEntry.hasErrors()){
				deleteItems(hooplaType);
			}

			try{
				//Set the extract time
				PreparedStatement updateSettingsStmt = null;
				if (doFullReload){
					if (!logEntry.hasErrors()) {
						updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfAllRecords" + hooplaType + " = ? where id = ?");
					} else {
						//force another full update
						PreparedStatement reactiveFullUpdateStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set runFullUpdate" + hooplaType + " = 1 where id = ?");
						reactiveFullUpdateStmt.setLong(1, settingsId);
						reactiveFullUpdateStmt.executeUpdate();
					}
				}else{
					// Update the lastUpdateOfChangedRecords if we have a successful response
					if (response.isSuccess()){
						updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfChangedRecords" + hooplaType + " = ? where id = ?");
					}
				}
				if (updateSettingsStmt != null) {
					updateSettingsStmt.setLong(1, startTimeForLogging);
					updateSettingsStmt.setLong(2, settingsId);
					updateSettingsStmt.executeUpdate();
					numRetries32HoursAfter = 0;
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error updating settings for" + hooplaType, e);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error updating settings for" + hooplaType, e);
		}
		return updatedContent;
	}

	private static boolean getFlexAvailability(HooplaSettings settings) {
		// Update all the flex titles availability
		int batchSize = Math.max(1, settings.getHooplaFlexBatchSize());
		logEntry.addNote("Starting Flex availability update using a batch size of " + batchSize);
		logEntry.saveResults();
		int numProcessed = 0;

		int hooplaLibraryId = settings.getLibraryId();
		ArrayList<Long> flexTitleIds = loadFlexTitles();
		if (flexTitleIds.isEmpty()) {
			logEntry.incErrors("No Flex titles found, please run full update for Flex.");
			return false;
		}

		int numFlexTitles = flexTitleIds.size();
		int start = 0;
		while (start < numFlexTitles) {
			int end = Math.min(start + batchSize, numFlexTitles);
			List<Long> batchIds = flexTitleIds.subList(start, end);

			Set<Long> processedIds = new HashSet<>(getFlexAvailabilityFromAPI(settings, batchIds));
			Set<Long> missingIds = new HashSet<>(batchIds);
			missingIds.removeAll(processedIds);
			if (!missingIds.isEmpty()){
				Set<Long> retried = getFlexAvailabilityFromAPI(settings, new ArrayList<>(missingIds));
				missingIds.removeAll(retried);
				processedIds.addAll(retried);
			}
			numProcessed += processedIds.size();
			start = end;
		}
		logEntry.addNote("Procesed Flex availability for " + numFlexTitles + " titles, missed " + (numFlexTitles - numProcessed) + " titles.");
		logEntry.addNote("Completed Flex availability updates.");
		logEntry.saveResults();
		return true;
	}

	private static ArrayList<Long> loadFlexTitles () {
		ArrayList<Long> flexTitleIds = new ArrayList<>();
		try {
			PreparedStatement getFlexTitlesStmt = aspenConn.prepareStatement("SELECT hooplaId FROM hoopla_export WHERE hooplaType = 'Flex' AND active = 1");
			ResultSet flexTitlesRS = getFlexTitlesStmt.executeQuery();
			while (flexTitlesRS.next()) {
				flexTitleIds.add(flexTitlesRS.getLong("hooplaId"));
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading Flex titles.", e);
		}
		return flexTitleIds;

	}

	private static Set<Long> getFlexAvailabilityFromAPI (HooplaSettings settings, List<Long> contentIds) {
		if (contentIds == null || contentIds.isEmpty()){
			return Collections.emptySet();
		}

		String hooplaAPIBaseURL = settings.getApiUrl();
		String accessToken = settings.getAccessToken();
		long tokenExpirationTime = settings.getTokenExpirationTime();
		int hooplaLibraryId = settings.getLibraryId();
		boolean doFullReloadFlex = settings.isRunFullUpdate("Flex");
		if (accessToken == null || tokenExpirationTime < (System.currentTimeMillis() / 1000)) {
			accessToken = getAccessToken(settings);
		}
		if (accessToken == null) {
			logEntry.incErrors("Could not load access token for Flex availability");
			return Collections.emptySet();
		}

		StringBuilder contentIdsString = new StringBuilder();
		for (Long hooplaId : contentIds) {
			if (contentIdsString.length() > 0) {
				contentIdsString.append(',');
			}
			contentIdsString.append(hooplaId);
		}

		HashMap<String, String> headers = new HashMap<>();
		headers.put("Authorization", "Bearer " + accessToken);
		headers.put("Content-Type", "application/json");
		headers.put("Accept", "application/json");

		String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content/info?contentIds=" + contentIdsString;
		for (int numTries = 1; numTries <= 3; numTries++){
			WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
			if (response.isSuccess()) {
				try {
					JSONArray availabilityArray = new JSONArray(response.getMessage());
					return updateFlexAvailabilityInDB(availabilityArray, doFullReloadFlex);
				} catch (JSONException e) {
					logEntry.incErrors("Error parsing Flex availability response.", e);
					return Collections.emptySet();
				}
			}

			int responseCode = response.getResponseCode();
			if (responseCode != 401 && responseCode != 503 && responseCode != 504) {
				logEntry.incErrors("Error getting Flex availability " + responseCode + " " + response.getMessage());
				logger.error("Error getting Flex availability from " + url + " + responseCode + " + response.getMessage());
				return Collections.emptySet();
			}
			if (responseCode == 401) {
				accessToken = getAccessToken(settings);
				if (accessToken == null) {
					logEntry.incErrors("Could not refresh access token for Flex availability");
					return Collections.emptySet();
				}
				headers.put("Authorization", "Bearer " + accessToken);
			}

			if (numTries < 3){
				try {
					Thread.sleep(1000 * 60); //Wait for 1 minute before trying again
				} catch (InterruptedException e) {
					logEntry.incErrors("Error sleeping for 1 minutes for Flex availability", e);
				}
			} else {
				logEntry.incErrors("Error getting Flex availability " + responseCode + " " + response.getMessage());
				logger.error("Error getting Flex availability from " + url + " + responseCode + " + response.getMessage());
			}
		}
		return Collections.emptySet();
	}

	private static Set<Long> updateFlexAvailabilityInDB(JSONArray availabilityArray, boolean doFullReloadFlex) {
		Set<Long> processedIds =  new HashSet<>();
		PreparedStatement getFlexTitlesStmt = null;
		PreparedStatement upsertFlexAvailabilityStmt = null;
		try {
			getFlexTitlesStmt = aspenConn.prepareStatement("SELECT t.id, t.hooplaId, UNCOMPRESS(t.rawResponse) as rawResponse, fa.holdsQueueSize, fa.availableCopies, fa.totalCopies, fa.status, fa.hooplaId FROM hoopla_export t LEFT JOIN hoopla_flex_availability fa ON t.hooplaId = fa.hooplaId WHERE t.hooplaType = 'Flex' AND t.active = 1 AND t.hooplaId = ?");
			upsertFlexAvailabilityStmt = aspenConn.prepareStatement("INSERT INTO hoopla_flex_availability (hooplaId, holdsQueueSize, availableCopies, totalCopies, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE holdsQueueSize = VALUES(holdsQueueSize), availableCopies = VALUES(availableCopies), totalCopies = VALUES(totalCopies), status = VALUES(status)");

			for (int i = 0; i < availabilityArray.length(); i++) {
				try {
					JSONObject availabilityInfo = availabilityArray.getJSONObject(i);
					if (!availabilityInfo.has("contentId")) {
						logEntry.incErrors("Flex availability response missing contentId");
						continue;
					}
					long hooplaId = availabilityInfo.getLong("contentId");
					if (!availabilityInfo.has("availability")) {
						continue;
					}
					JSONObject availability = availabilityInfo.getJSONObject("availability");
					if (!availability.isEmpty()) {
						String status = availability.getString("status");
						int holdsQueueSize = status.equals("BORROW") ? 0 : (availability.has("holdsQueueSize")  ? availability.getInt("holdsQueueSize") : 0);
						int availableCopies = availability.has("availableCopies")  ? availability.getInt("availableCopies") : 0;
						int totalCopies = availability.has("totalCopies")  ? availability.getInt("totalCopies") : 0;

						boolean availabilityChanged = false;
						try {
							getFlexTitlesStmt.setLong(1, hooplaId);
							ResultSet flexTitlesRS = getFlexTitlesStmt.executeQuery();
							if (flexTitlesRS.next()) {
								boolean existingInDB = flexTitlesRS.getString("status") != null;
								int existingholdsQueueSize = flexTitlesRS.getInt("holdsQueueSize");
								int existingAvailableCopies = flexTitlesRS.getInt("availableCopies");
								int existingTotalCopies = flexTitlesRS.getInt("totalCopies");
								String existingStatus = flexTitlesRS.getString("status");
								if (existingholdsQueueSize != holdsQueueSize || existingAvailableCopies != availableCopies || existingTotalCopies != totalCopies || !Objects.equals(existingStatus, status)) {
									availabilityChanged = true;
								}
							} else {
								availabilityChanged = true;
							}

							if (availabilityChanged) {
								upsertFlexAvailabilityStmt.setLong(1, hooplaId);
								upsertFlexAvailabilityStmt.setInt(2, holdsQueueSize);
								upsertFlexAvailabilityStmt.setInt(3, availableCopies);
								upsertFlexAvailabilityStmt.setInt(4, totalCopies);
								upsertFlexAvailabilityStmt.setString(5, status);
								upsertFlexAvailabilityStmt.executeUpdate();
								String rawResponse = flexTitlesRS.getString("rawResponse");
								JSONObject curTitle = new JSONObject(rawResponse);
								String groupedWorkId =  getRecordGroupingProcessor().groupHooplaRecord(curTitle, hooplaId);
								indexRecord(groupedWorkId);
								logEntry.incAvailabilityChanges();
								if (!doFullReloadFlex) {
									logEntry.incNumProducts(1);
								}
							}
							flexTitlesRS.close();
							processedIds.add(hooplaId);
						} catch (SQLException e) {
							logEntry.incErrors("Error updating Flex availability for title " + hooplaId, e);
						}
					}
				} catch (JSONException e) {
					logEntry.incErrors("Error parsing Flex availability JSON", e);
				}
			}
		}catch (SQLException e) {
			logEntry.incErrors("Error preparing Flex availability statements", e);
		} finally {
			try {
				if (getFlexTitlesStmt != null) getFlexTitlesStmt.close();
				if (upsertFlexAvailabilityStmt != null) upsertFlexAvailabilityStmt.close();
			} catch (SQLException e) {
				logEntry.incErrors("Error closing Flex availability statements", e);
			}
		}
		return processedIds;
	}

	public static boolean exportSingleHooplaTitle(String singleWorkId, String singleWorkType) {
		boolean updatesRun = false;
		try{
			logEntry.addNote("Doing extract of single work " + singleWorkId);
			logEntry.saveResults();
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from hoopla_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				HooplaSettings settings = new HooplaSettings(getSettingsRS);
				String hooplaAPIBaseURL = settings.getApiUrl();
				int hooplaLibraryId = settings.getLibraryId();

				String accessToken = getAccessToken(settings);
				if (accessToken == null) {
					logEntry.incErrors("Could not load access token");
					return false;
				}
				String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content";
				long numericSingleWorkId = Long.parseLong(singleWorkId);
				if (singleWorkType.equalsIgnoreCase("Flex")) {
					url += "?limit=1&startToken=" + (numericSingleWorkId - 1) + "&purchaseModel=EST";
				} else {
					url += "?limit=1&startToken=" + (numericSingleWorkId - 1) + "&purchaseModel=PPU";
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
						if (responseTitles != null && !responseTitles.isEmpty()) {
							updateTitlesInDB(responseTitles, true, false, singleWorkType);
							logEntry.saveResults();

							if (singleWorkType.equalsIgnoreCase("Flex")) {
								if (!responseTitles.isEmpty()) {
									JSONObject titleObj = responseTitles.getJSONObject(0);
									boolean isActive = titleObj.getBoolean("active");
									if (!isActive) {
										logEntry.addNote("Skipping availability check for inactive Flex title: " + numericSingleWorkId);
									} else {
										String availUrl = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content/info?contentIds=" + numericSingleWorkId;
										WebServiceResponse availResponse = NetworkUtils.getURL(availUrl, logger, headers);
										if (!availResponse.isSuccess()) {
											logEntry.incErrors("Could not get availability for Flex title " + numericSingleWorkId + " from " + availUrl + " " + availResponse.getMessage());
										} else {
											try {
												JSONArray availabilityArray = new JSONArray(availResponse.getMessage());
												if (!availabilityArray.isEmpty()) {
													JSONObject titleInfo = availabilityArray.getJSONObject(0);
													JSONObject availability = titleInfo.getJSONObject("availability");

													// Direct update without comparing old values
													PreparedStatement updateFlexAvailabilityStmt = aspenConn.prepareStatement(
														"INSERT INTO hoopla_flex_availability (hooplaId, holdsQueueSize, " +
														"availableCopies, totalCopies, status) " +
														"VALUES (?, ?, ?, ?, ?) " +
														"ON DUPLICATE KEY UPDATE " +
														"holdsQueueSize = VALUES(holdsQueueSize), " +
														"availableCopies = VALUES(availableCopies), " +
														"totalCopies = VALUES(totalCopies), " +
														"status = VALUES(status)"
													);
													int holdsQueueSize = availability.has("holdsQueueSize") ? availability.getInt("holdsQueueSize") : 0;

													updateFlexAvailabilityStmt.setLong(1, numericSingleWorkId);
													updateFlexAvailabilityStmt.setInt(2, holdsQueueSize);
													updateFlexAvailabilityStmt.setInt(3, availability.getInt("availableCopies"));
													updateFlexAvailabilityStmt.setInt(4, availability.getInt("totalCopies"));
													updateFlexAvailabilityStmt.setString(5, availability.getString("status"));
													updateFlexAvailabilityStmt.executeUpdate();

													logEntry.addNote("Updated availability for Flex title " + numericSingleWorkId);
													logEntry.incAvailabilityChanges();
												}
											} catch (Exception e) {
												logEntry.incErrors("Error updating Flex availability for title " +
													numericSingleWorkId, e);
											}
										}
									}
									updatesRun = true;
								}
							}
						}else{
							//The title has been deleted
							RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", singleWorkId);
							if (result.reindexWork) {
								getGroupedWorkIndexer().processGroupedWork(result.permanentId);
							} else if (result.deleteWork) {
								//Delete the work from solr and the database
								getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
							}
							logEntry.incDeleted();
							deleteHooplaItemStmt.setLong(1, numericSingleWorkId);
							deleteHooplaItemStmt.executeUpdate();
						}
					}
				}
			}
			if (numSettings == 0){
				logger.error("Unable to find settings for Hoopla when processing single title, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting single hoopla title", e);
		}
		return updatesRun;
	}

	private static void updateTitlesInDB(JSONArray responseTitles, boolean forceRegrouping, boolean doFullReload, String hooplaType) {
		logEntry.incNumProducts(responseTitles.length());
		for (int i = 0; i < responseTitles.length(); i++){
			try {
				JSONObject curTitle = responseTitles.getJSONObject(i);

				String rawResponse = curTitle.toString();
				checksumCalculator.reset();
				checksumCalculator.update(rawResponse.getBytes());
				long rawChecksum = checksumCalculator.getValue();
				boolean curTitleActive = curTitle.getBoolean("active");

				long hooplaId = curTitle.getLong("id"); //formerly titleId was used, but this is not unique for TV series

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

					//Delete the Flex availability if it's a Flex title
					if (hooplaType.equalsIgnoreCase("Flex")) {
						try {
							PreparedStatement deleteFlexAvailabilityStmt = aspenConn.prepareStatement(
								"DELETE from hoopla_flex_availability where hooplaId = ?"
							);
							deleteFlexAvailabilityStmt.setLong(1, hooplaId);
							deleteFlexAvailabilityStmt.executeUpdate();
						} catch (SQLException e) {
							logEntry.incErrors("Error deleting Flex availability for inactive title " + hooplaId, e);
						}
					}

					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", Long.toString(hooplaId));
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					logEntry.incDeleted();
					deleteHooplaItemStmt.setLong(1, existingTitle.getId());
					deleteHooplaItemStmt.executeUpdate();
					existingRecords.remove(hooplaId);
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
						// Flex titles don't have a price, so set it to 0.0 or set to 0 if the record has no price
						if (hooplaType.equalsIgnoreCase("Flex") || !curTitle.has("price")) {
							addHooplaTitleToDB.setDouble(11, 0.0);
						} else {
							addHooplaTitleToDB.setDouble(11, curTitle.getDouble("price"));
						}
						addHooplaTitleToDB.setLong(12, rawChecksum);
						addHooplaTitleToDB.setString(13, rawResponse);
						addHooplaTitleToDB.setLong(14, startTimeForLogging);
						addHooplaTitleToDB.setString(15, hooplaType);
						try {
							addHooplaTitleToDB.executeUpdate();

							String groupedWorkId =  getRecordGroupingProcessor().groupHooplaRecord(curTitle, hooplaId);
							indexRecord(groupedWorkId);
						}catch (DataTruncation e) {
							logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e);
						}catch (SQLException e){
							logEntry.incErrors("Error adding hoopla title to database record " + hooplaId + " " + curTitle.getString("title"), e);
						}
					}else if (recordUpdated || doFullReload || forceRegrouping){
						updateHooplaTitleInDB.setBoolean(1, true);
						updateHooplaTitleInDB.setString(2, curTitle.getString("title"));
						updateHooplaTitleInDB.setString(3, curTitle.getString("kind"));
						updateHooplaTitleInDB.setBoolean(4, curTitle.getBoolean("pa"));
						updateHooplaTitleInDB.setBoolean(5, curTitle.getBoolean("demo"));
						updateHooplaTitleInDB.setBoolean(6, curTitle.getBoolean("profanity"));
						updateHooplaTitleInDB.setString(7, curTitle.has("rating") ? curTitle.getString("rating") : "");
						updateHooplaTitleInDB.setBoolean(8, curTitle.getBoolean("abridged"));
						updateHooplaTitleInDB.setBoolean(9, curTitle.getBoolean("children"));
						// Flex titles don't have a price, so set it to 0.0 or set to 0 if the record has no price
						if (hooplaType.equalsIgnoreCase("Flex") || !curTitle.has("price")) {
							updateHooplaTitleInDB.setDouble(10, 0.0);
						} else {
							updateHooplaTitleInDB.setDouble(10, curTitle.getDouble("price"));
						}
						updateHooplaTitleInDB.setLong(11, rawChecksum);
						updateHooplaTitleInDB.setString(12, rawResponse);
						updateHooplaTitleInDB.setString(13, hooplaType);
						updateHooplaTitleInDB.setLong(14, existingTitle.getId());

						try {
							updateHooplaTitleInDB.executeUpdate();

							String groupedWorkId =  getRecordGroupingProcessor().groupHooplaRecord(curTitle, hooplaId);
							indexRecord(groupedWorkId);
						}catch (DataTruncation e) {
							logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e);
						}catch (SQLException e){
							logEntry.incErrors("Error updating hoopla data in database for record " + hooplaId + " " + curTitle.getString("title"), e);
						}
					}
				}
			}catch (Exception e){
				logEntry.incErrors("Error updating hoopla " + hooplaType + " data", e);
			}
		}
		getGroupedWorkIndexer().commitChanges();
	}

	private static void indexRecord(String groupedWorkId) {
		getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
	}

	private static String getAccessToken(HooplaSettings settings) {
		String username = settings.getApiUsername();
		String password = settings.getApiPassword();
		if (username == null || password == null){
			logger.error("Please set HooplaAPIUser and HooplaAPIPassword in settings");
			logEntry.addNote("Please set HooplaAPIUser and HooplaAPIPassword in settings");
			return null;
		}
		int numTries = 0;
		while (numTries <= 3) {
			numTries++;
			String getTokenUrl = settings.getApiUrl() + "/v2/token";
			WebServiceResponse response = NetworkUtils.postToURL(getTokenUrl, null, "application/json", null, logger, username + ":" + password);

			if (response.isSuccess()) {
				try {
					JSONObject responseJSON = new JSONObject(response.getMessage());
					String  accessToken =  responseJSON.getString("access_token");
					long tokenExpirationTime = (System.currentTimeMillis() / 1000) + responseJSON.getLong("expires_in");

					try {
						PreparedStatement updateTokenStmt = aspenConn.prepareStatement(
							"UPDATE hoopla_settings SET accessToken = ?, tokenExpirationTime = ? WHERE id = ?"
						);
						updateTokenStmt.setString(1, accessToken);
						updateTokenStmt.setLong(2, tokenExpirationTime);
						updateTokenStmt.setLong(3, settings.getSettingsId());
						updateTokenStmt.executeUpdate();
						return accessToken;
					} catch (SQLException e) {
						logEntry.incErrors("Error storing token", e);
					}
				} catch (JSONException e) {
					if (numTries == 3) {
						logEntry.addNote("Could not parse JSON for token " + response.getMessage());
						logger.error("Could not parse JSON for token " + response.getMessage(), e);
						return null;
					} else {
						try {
							Thread.sleep(1000 * 60 * 2);
						} catch (InterruptedException ex) {
							logEntry.incErrors("Thread was interrupted while sleeping");
						}
					}
				}
			}
		}
		logEntry.addNote("Could not get access token in 3 tries");
		return null;
	}

	public void exporterCleanUp() {
		try{
			addHooplaTitleToDB.close();
			addHooplaTitleToDB = null;
			updateHooplaTitleInDB.close();
			updateHooplaTitleInDB = null;
			deleteHooplaItemStmt.close();
			deleteHooplaItemStmt = null;
		}catch (Exception e){
			logger.error("Error closing Hoopla exporter statements", e);
		}
		if (groupedWorkIndexer != null) {
			groupedWorkIndexer.finishIndexingFromExtract(logEntry);
			groupedWorkIndexer.close();
			groupedWorkIndexer = null;
		}
		if (recordGroupingProcessorSingleton != null) {
			recordGroupingProcessorSingleton.close();
			recordGroupingProcessorSingleton = null;
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
		//It turns out to be quite slow to look this up repeatedly, grab the existing values for all and store in memory
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
			String groupedWorkId = getRecordGroupingProcessor().groupHooplaRecord(rawResponse, recordIdentifier);
			if (!originalGroupedWorkId.equals(groupedWorkId)) {
				logEntry.incChangedAfterGrouping();
			}
			//process records to regroup after every 1000 changes, so we keep up with the changes.
			if (logEntry.getNumChangedAfterGrouping() % 1000 == 0){
				indexer.processScheduledWorks(logEntry, false, -1);
			}
		}

		//Finish reindexing anything that just changed
		if (logEntry.getNumChangedAfterGrouping() > 0){
			indexer.processScheduledWorks(logEntry, false, -1);
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
