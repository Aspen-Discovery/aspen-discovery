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

@SuppressWarnings("SqlResolve")
public class HooplaExporter2 {
	private final Logger logger;
	private final String serverName;

	private final Ini configIni;

	private final Long startTimeForLogging;
	private final HooplaExtractLogEntry2 logEntry;

	private final Connection aspenConn;
	private PreparedStatement getAllExistingHooplaItemsStmt;
	private PreparedStatement addHooplaTitleToDB = null;
	private PreparedStatement updateHooplaTitleInDB = null;
	private PreparedStatement deleteHooplaItemStmt;
	private PreparedStatement getLibraryHooplaSettingsStmt;
	private PreparedStatement updateFullUpdateForLibraryStmt;
	private PreparedStatement getHooplaEntitlementIdStmt;
	private PreparedStatement addHooplaEntitlementStmt;
	private PreparedStatement getHooplaEntitlementScopeStmt;
	private PreparedStatement addHooplaEntitlementScopeStmt;
	private PreparedStatement deleteHooplaEntitlementScopeStmt;
	private PreparedStatement entitlementHasScopesStmt;
	private PreparedStatement deleteHooplaEntitlementByIdStmt;
	private PreparedStatement getExistingEntitlementsForLibraryStmt;
	private PreparedStatement getFlexEntitlementsForLibraryStmt;
	private PreparedStatement upsertFlexAvailabilityStmt;
	private PreparedStatement getExistingFlexAvailabilityStmt;
	private PreparedStatement deleteFlexAvailabilityForLibraryStmt;

	//Record grouper
	private GroupedWorkIndexer groupedWorkIndexer;
	private RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//Existing records
	private HashMap<Long, HooplaTitle2> existingRecords = new HashMap<>();
	private final HashSet<Long> titlesNeedingReindex = new HashSet<>();

	private final String HOOPLA_TYPE_INSTANT = "Instant";
	private static final String HOOPLA_TYPE_FLEX = "Flex";

	//For Checksums
	private final CRC32 checksumCalculator = new CRC32();

	//For 32 hours catch up
	private int numRetries32HoursAfter = 0;

	public HooplaExporter2(String serverName, Connection aspenConn, Ini configIni, HooplaExtractLogEntry2 logEntry, Logger logger) throws SQLException {
		this.serverName = serverName;
		this.aspenConn = aspenConn;
		this.configIni = configIni;
		this.logEntry = logEntry;
		this.logger = logger;

		Date startTime = new Date();
		startTimeForLogging = startTime.getTime() / 1000;

		try {
			getAllExistingHooplaItemsStmt = aspenConn.prepareStatement("SELECT id, hooplaId, rawChecksum, UNCOMPRESSED_LENGTH(rawResponse) as rawResponseLength from hoopla_export");
			addHooplaTitleToDB = aspenConn.prepareStatement("INSERT INTO hoopla_export (hooplaId, title, format, pa, demo, profanity, rating, abridged, children, ppuPrice, rawChecksum, rawResponse, dateFirstDetected) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,COMPRESS(?),?) ");
			updateHooplaTitleInDB = aspenConn.prepareStatement("UPDATE hoopla_export set title = ?, format = ?, pa = ?, demo = ?, profanity = ?, rating = ?, abridged = ?, children = ?, ppuPrice = ?, rawChecksum = ?, rawResponse = COMPRESS(?) where id = ?");
			deleteHooplaItemStmt = aspenConn.prepareStatement("DELETE FROM hoopla_export where id = ?");
			getLibraryHooplaSettingsStmt = aspenConn.prepareStatement("SELECT lhs.*, l.displayName FROM library_hoopla_settings lhs INNER JOIN library l ON lhs.libraryId = l.libraryId WHERE settingId = ?");
			updateFullUpdateForLibraryStmt = aspenConn.prepareStatement("UPDATE library_hoopla_settings SET fullUpdateForLibrary = 0 WHERE id = ?");
			getHooplaEntitlementIdStmt = aspenConn.prepareStatement("SELECT id FROM hoopla_entitlements WHERE hooplaId = ? AND hooplaType = ?");
			addHooplaEntitlementStmt = aspenConn.prepareStatement("INSERT INTO hoopla_entitlements (hooplaId, hooplaType) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			getHooplaEntitlementScopeStmt = aspenConn.prepareStatement("SELECT * FROM hoopla_entitlement_scopes WHERE entitlementId = ? AND scopeLibraryId = ?");
			addHooplaEntitlementScopeStmt = aspenConn.prepareStatement("INSERT INTO hoopla_entitlement_scopes (entitlementId, scopeLibraryId) VALUES (?, ?)");
			deleteHooplaEntitlementScopeStmt = aspenConn.prepareStatement("DELETE FROM hoopla_entitlement_scopes WHERE entitlementId = ? AND scopeLibraryId = ?");
			entitlementHasScopesStmt = aspenConn.prepareStatement("SELECT count(*) FROM hoopla_entitlement_scopes WHERE entitlementId = ?");
			deleteHooplaEntitlementByIdStmt = aspenConn.prepareStatement("DELETE FROM hoopla_entitlements WHERE id = ?");
			getExistingEntitlementsForLibraryStmt = aspenConn.prepareStatement("SELECT he.id AS entitlementId, he.hooplaId FROM hoopla_entitlements he INNER JOIN hoopla_entitlement_scopes hes ON hes.entitlementId = he.id WHERE hes.scopeLibraryId = ? AND he.hooplaType = ?");
			getFlexEntitlementsForLibraryStmt = aspenConn.prepareStatement("SELECT he.hooplaId FROM hoopla_entitlements he INNER JOIN hoopla_entitlement_scopes hes ON hes.entitlementId = he.id WHERE hes.scopeLibraryId = ? AND he.hooplaType = ?");
			upsertFlexAvailabilityStmt = aspenConn.prepareStatement("INSERT INTO hoopla_flex_availability (hooplaId, scopeLibraryId, holdsQueueSize, availableCopies, totalCopies, status) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE scopeLibraryId = VALUES(scopeLibraryId), holdsQueueSize = VALUES(holdsQueueSize), availableCopies = VALUES(availableCopies), totalCopies = VALUES(totalCopies), status = VALUES(status)");
			getExistingFlexAvailabilityStmt = aspenConn.prepareStatement("SELECT holdsQueueSize, availableCopies, totalCopies, status FROM hoopla_flex_availability WHERE hooplaId = ? AND scopeLibraryId = ?");
			deleteFlexAvailabilityForLibraryStmt = aspenConn.prepareStatement("DELETE FROM hoopla_flex_availability WHERE hooplaId = ? AND scopeLibraryId = ?");
		} catch (SQLException e) {
			logEntry.incErrors("Error preparing Hoopla exporter2 statements", e);
			logger.error("Error preparing Hoopla exporter2 statements", e);
		}
		//Get a list of all existing records in the database
		loadExistingTitles();

		processRecordsToReload(logEntry);
	}

	private void processRecordsToReload(HooplaExtractLogEntry2 logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='hoopla'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse FROM hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordId = getRecordsToReloadRS.getString("identifier");
				long hooplaId = Long.parseLong(StringUtils.replace(recordId,"MWT", ""));
				//Regroup the record
				getItemDetailsForRecordStmt.setLong(1, hooplaId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String groupedWorkId =  getRecordGroupingProcessor().groupHooplaRecord(itemDetails, hooplaId);
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
					logEntry.addNote("Could not get details for Hoopla record to reload " + hooplaId + " it has been deleted");
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

	private boolean cleanOrphanRecords() {
		int numDeleted = 0;
		logEntry.addNote("Starting to clean orphan records");
		logEntry.saveResults();
		PreparedStatement getOrphanEntitlementsStmt = null;
		ResultSet orphanEntitlementsRS = null;
		//noinspection TryFinallyCanBeTryWithResources
		try {
			getOrphanEntitlementsStmt = aspenConn.prepareStatement("SELECT id, hooplaId from hoopla_entitlements where id not in (SELECT entitlementId from hoopla_entitlement_scopes)");
			orphanEntitlementsRS = getOrphanEntitlementsStmt.executeQuery();
			while (orphanEntitlementsRS.next()) {
				long orphanEntitlementId = orphanEntitlementsRS.getLong("id");
				long orphanHooplaId = orphanEntitlementsRS.getLong("hooplaId");
				deleteHooplaEntitlementByIdStmt.setLong(1, orphanEntitlementId);
				deleteHooplaEntitlementByIdStmt.executeUpdate();

				// Remove the record from the grouped work
				RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("hoopla", Long.toString(orphanHooplaId));
				if (result.reindexWork) {
					getGroupedWorkIndexer().processGroupedWork(result.permanentId);
				} else if (result.deleteWork) {
					getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
				}
				numDeleted++;
				logEntry.incNumProducts(1);
				titlesNeedingReindex.remove(orphanHooplaId);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error cleaning orphan records", e);
			logger.error("Error cleaning orphan records", e);
		} finally {
			try {
				if (orphanEntitlementsRS != null) {
					orphanEntitlementsRS.close();
				}
				if (getOrphanEntitlementsStmt != null) {
					getOrphanEntitlementsStmt.close();
				}
			} catch (Exception e) {
				logEntry.incErrors("Error cleaning orphan records", e);
			}
		}
		if (numDeleted > 0) {
			logEntry.addNote("Deleted " + numDeleted + " orphan entitlements");
			logEntry.saveResults();
		}
		logEntry.addNote("Finished cleaning orphan records");
		logEntry.saveResults();
		return numDeleted > 0;
	}

	private boolean cleanupLibraryEntitlements(HooplaLibrarySettings librarySetting) {
		boolean cleanUpRan = false;
		if (librarySetting.isCleanUpInstant()) {
			logEntry.addNote("Cleaning up Instant entitlements for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")");
			logEntry.saveResults();
			HashMap<Long, Long> existingEntitlements = loadExistingEntitlementsForLibrary(librarySetting.getLibraryId(), HOOPLA_TYPE_INSTANT);
			for (Map.Entry<Long, Long> entry : existingEntitlements.entrySet()) {
				Long hooplaId = entry.getKey();
				Long entitlementId = entry.getValue();
				try {
					deleteHooplaEntitlementScopeStmt.setLong(1, entitlementId);
					deleteHooplaEntitlementScopeStmt.setLong(2, librarySetting.getLibraryId());
					deleteHooplaEntitlementScopeStmt.executeUpdate();
					titlesNeedingReindex.add(hooplaId);
					logEntry.incEntitlementsDeleted();
				} catch (SQLException e) {
					logEntry.incErrors("Error deleting Instant entitlement scope for title " + hooplaId + " (library " + librarySetting.getLibraryId() + ")", e);
				}
			}
			// Reset the clean up Instant flag
			try {
				PreparedStatement updateLibrarySettingsStmt = aspenConn.prepareStatement("UPDATE library_hoopla_settings set cleanUpInstant = 0 where id = ?");
				updateLibrarySettingsStmt.setLong(1, librarySetting.getId());
				updateLibrarySettingsStmt.executeUpdate();
				updateLibrarySettingsStmt.close();
			} catch (SQLException e) {
				logEntry.incErrors("Error resetting clean up Instant flag for library " + librarySetting.getLibraryId(), e);
			}
			logEntry.addNote("Cleaned up " + existingEntitlements.size() + " Instant entitlements for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")");
			logEntry.saveResults();
			cleanUpRan = true;
		}
		if (librarySetting.isCleanUpFlex()) {
			logEntry.addNote("Cleaning up Flex entitlements for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")");
			logEntry.saveResults();
			HashMap<Long, Long> existingEntitlements = loadExistingEntitlementsForLibrary(librarySetting.getLibraryId(), HOOPLA_TYPE_FLEX);
			for (Map.Entry<Long, Long> entry : existingEntitlements.entrySet()) {
				Long hooplaId = entry.getKey();
				Long entitlementId = entry.getValue();
				try {
					deleteHooplaEntitlementScopeStmt.setLong(1, entitlementId);
					deleteHooplaEntitlementScopeStmt.setLong(2, librarySetting.getLibraryId());
					deleteHooplaEntitlementScopeStmt.executeUpdate();
					deleteFlexAvailabilityForLibraryStmt.setLong(1, hooplaId);
					deleteFlexAvailabilityForLibraryStmt.setLong(2, librarySetting.getLibraryId());
					deleteFlexAvailabilityForLibraryStmt.executeUpdate();
					logEntry.incEntitlementsDeleted();
					titlesNeedingReindex.add(hooplaId);
				} catch (SQLException e) {
					logEntry.incErrors("Error deleting Flex entitlement scope and availability for title " + hooplaId + " (library " + librarySetting.getLibraryId() + ")", e);
				}
			}
			// Reset the clean up Flex flag
			try {
				PreparedStatement updateLibrarySettingsStmt = aspenConn.prepareStatement("UPDATE library_hoopla_settings set cleanUpFlex = 0 where id = ?");
				updateLibrarySettingsStmt.setLong(1, librarySetting.getId());
				updateLibrarySettingsStmt.executeUpdate();
				updateLibrarySettingsStmt.close();
			} catch (SQLException e) {
				logEntry.incErrors("Error resetting clean up Flex flag for library " + librarySetting.getLibraryId(), e);
			}
			logEntry.addNote("Cleaned up " + existingEntitlements.size() + " Flex entitlements and availability for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")");
			logEntry.saveResults();
			cleanUpRan = true;
		}
		return cleanUpRan;
	}

	private boolean flushRecordsToReindex() {
		logEntry.addNote("Starting to flush records to reindex");
		logEntry.saveResults();
		if (titlesNeedingReindex.isEmpty()) {
			return false;
		}
		logger.info("Flushing " + titlesNeedingReindex.size() + " Hoopla titles for reindex");

		List<Long> idsToProcess = new ArrayList<>(titlesNeedingReindex);
		int batchSize = 1000;
		int numProcessed = 0;
		int numNoMetadata = 0;

		for (int i = 0; i < idsToProcess.size(); i += batchSize) {
			List<Long> idsToProcessBatch = idsToProcess.subList(i, Math.min(i + batchSize, idsToProcess.size()));

			if (idsToProcessBatch.isEmpty()) {
				continue;
			}
			PreparedStatement getRawResponseForRecordsStmt = null;
			ResultSet getRawResponseForRecordsRS = null;
			int numHasMetadata = 0;

			try {
				StringBuilder idsToProcessString = new StringBuilder();
				for (int j = 0; j < idsToProcessBatch.size(); j++) {
					if (j > 0) idsToProcessString.append(",");
					idsToProcessString.append(idsToProcessBatch.get(j));
				}

				// Query get rawResponse for the records that are entitled
				getRawResponseForRecordsStmt =
				aspenConn.prepareStatement("SELECT DISTINCT he.hooplaId, UNCOMPRESS(hex.rawResponse) as rawResponse " +
				"FROM hoopla_export hex " +
				"INNER JOIN hoopla_entitlements he ON hex.hooplaId = he.hooplaId " +
				"INNER JOIN hoopla_entitlement_scopes hes ON he.id = hes.entitlementId " +
				"WHERE he.hooplaId IN (" + idsToProcessString + ") ");
				getRawResponseForRecordsRS = getRawResponseForRecordsStmt.executeQuery();
				while (getRawResponseForRecordsRS.next()) {
					numHasMetadata++;
					logEntry.incNumProducts(1);
					long hooplaId = getRawResponseForRecordsRS.getLong("hooplaId");
					String rawResponse = getRawResponseForRecordsRS.getString("rawResponse");
					JSONObject curTitleDetails = new JSONObject(rawResponse);
					String groupedWorkId =  getRecordGroupingProcessor().groupHooplaRecord(curTitleDetails, hooplaId);
					if (groupedWorkId != null) {
						indexRecord(groupedWorkId);
						numProcessed++;
					}
				}
				numNoMetadata += idsToProcessBatch.size() - numHasMetadata;
				logEntry.saveResults();
			} catch (SQLException e) {
				logEntry.incErrors("Error getting raw response for records", e);
			} finally {
				try {
					if (getRawResponseForRecordsRS != null) {
						getRawResponseForRecordsRS.close();
					}
					if (getRawResponseForRecordsStmt != null) {
						getRawResponseForRecordsStmt.close();
					}
				} catch (Exception e) {
					logEntry.incErrors("Error getting raw response for records", e);
				}
			}
		}
		logEntry.addNote("Flushed " + titlesNeedingReindex.size() + " Hoopla titles for reindex" + ", processed " + numProcessed + " titles, " + numNoMetadata + " titles without metadata");
		logEntry.saveResults();
		titlesNeedingReindex.clear();
		return true;
	}

	private void loadExistingTitles() {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			ResultSet allRecordsRS = getAllExistingHooplaItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				long hooplaId = allRecordsRS.getLong("hooplaId");
				HooplaTitle2 newTitle = new HooplaTitle2(
						allRecordsRS.getLong("id"),
						hooplaId,
						allRecordsRS.getLong("rawChecksum"),
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
			boolean globalContentUpdated = false;
			while (getSettingsRS.next()) {
				HooplaSettings2 settings = new HooplaSettings2(getSettingsRS);
				ArrayList<HooplaLibrarySettings> librarySettings = loadLibraryHooplaSettings(settings.getSettingsId());
				numSettings++;
				boolean hasEnabledLibrary = false;
				boolean hasCleanUpLibrary = false;

				for (HooplaLibrarySettings librarySetting : librarySettings) {
					if (librarySetting.isInstantEnabled() || librarySetting.isFlexEnabled()) {
						hasEnabledLibrary = true;
					} else if (librarySetting.isCleanUpInstant() || librarySetting.isCleanUpFlex()) {
						hasCleanUpLibrary = true;
					}
				}

				// Nothing enabled or needed for cleanup, skip
				if (!hasEnabledLibrary && !hasCleanUpLibrary) {
					logEntry.addNote("No enabled or needed for cleanup libraries, skipping this run");
					logEntry.saveResults();
					continue;
				}

				// Extract Global Content
				if (!globalContentUpdated) {
					globalContentUpdated = exportHooplaContent(settings);
					updatesRun |= globalContentUpdated;
				}

				updatesRun |= exportLibraryEntitlements(settings, globalContentUpdated, librarySettings);

				// Clean Orphan records
				updatesRun |= cleanOrphanRecords();

				// Process Flex Availability
				updatesRun |= getFlexAvailability(settings, librarySettings);

				// Flush the records to reindex
				updatesRun |= flushRecordsToReindex();

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


	private boolean exportHooplaContent(HooplaSettings2 settings) {
		boolean updatedContent = false;
		boolean doFullReload = settings.isRunFullUpdate();
		long settingsId = settings.getSettingsId();
		String hooplaAPIBaseURL = settings.getApiUrl();
		long lastUpdateOfChangedRecords = settings.getLastUpdateOfChangedRecords();
		long lastUpdateOfAllRecords = settings.getLastUpdateOfAllRecords();
		long lastUpdate = Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
		String countryCode = settings.getCountryCode();
		String lastRecordProcessed = settings.getLastRecordProcessed() != null ? settings.getLastRecordProcessed() : "0";
		int recordExtractionBatchSize = settings.getRecordExtractionBatchSize();
		int numRecordsToExtract = 0;

		String accessToken = settings.getAccessToken();
		long tokenExpirationTime = settings.getTokenExpirationTime();
		int indexingTime = settings.getIndexingTime();

		if (accessToken == null || tokenExpirationTime < (System.currentTimeMillis() / 1000)) {
			accessToken = getAccessToken(settings);
		}

		if (accessToken == null) {
			logEntry.incErrors("Could not load access token");
			return false;
		}

		logEntry.addNote("Starting global content extraction using a batch size of " + recordExtractionBatchSize + " at hour " + indexingTime);
		logEntry.saveResults();

		try {
			if (doFullReload){
				//Unset that a full update needs to be done
				PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set runFullUpdate = 0 where id = ?");
				updateSettingsStmt.setLong(1, settingsId);
				updateSettingsStmt.executeUpdate();
				logEntry.addNote("Processing full update for global content");
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
						logger.warn("Already completed today's global content extraction at " + indexingTime + ". Skipping until tomorrow.");
						logEntry.addNote("Already completed today's global content extraction at " + indexingTime + ". Skipping until tomorrow.");
						logEntry.saveResults();
						return false;
					}
					//Set the last update time to 32 hours ago (go bigger to get more updates)
					if (thirtyTwoHoursAgo < lastUpdate){
						lastUpdate = thirtyTwoHoursAgo;
					}
					numRetries32HoursAfter = 0;
					logEntry.addNote("Starting daily global content extraction");
				}else{
					//It's not configured indexing time, skip for now.
					//Figure out when we last indexed this collection.
					if (lastUpdate >= thirtyTwoHoursAgo) {
						//Do not index unless it has been 32 hours
						return false;
					}
					// If we don't have updates for 32 hours, we will try 3 times
					// If we exceed 3 times and fail, we will wait until configured indexing time
					if (numRetries32HoursAfter >= 3){
						logger.warn("Exceeded 3 retries for 32 hours catch up, waiting until next indexing time at " + indexingTime);
						return false;
					}
					numRetries32HoursAfter++;
					logEntry.addNote("Retrying global content extraction after 32 hours " + numRetries32HoursAfter + " of 3");
				}
			}

			updatedContent = false;

			//Formulate the first call depending on if we are doing a full reload or not
			String startToken = lastRecordProcessed;
			String url = hooplaAPIBaseURL + "/api/v1/global-contents?countryCodes=" + countryCode;

			if (!doFullReload && lastUpdate > 0) {
				//Give a 2-minute buffer for the extract
				lastUpdate -= 120;
				logEntry.addNote("Extracting records since " + new Date(lastUpdate * 1000));
				url += "&startTime=" + lastUpdate + "&limit=" + recordExtractionBatchSize;
			} else {
				url += "&limit=" + recordExtractionBatchSize;
			}

			@SuppressWarnings("DuplicatedCode")
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", "Bearer " + accessToken);
			headers.put("Content-Type", "application/json");
			headers.put("Accept", "application/json");

			PreparedStatement updateLastRecordProcessedStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastRecordProcessed = ? where id = ?");
			int numTries = 0;
			WebServiceResponse response;

			while (startToken != null) {
				String paginationUrl = url + "&startToken=" + startToken;
				response = NetworkUtils.getURL(paginationUrl, logger, headers);
				if (response.isSuccess()) {
					JSONObject responseJSON = new JSONObject(response.getMessage());
					if (responseJSON.has("contents")) {
						JSONArray responseTitles = responseJSON.getJSONArray("contents");
						if (responseTitles != null && !responseTitles.isEmpty()) {
							updateTitlesInDB(responseTitles, false, doFullReload);
							numRecordsToExtract += responseTitles.length();
							updatedContent = true;
							logEntry.saveResults();
						}

						JSONObject metadataJSON = responseJSON.optJSONObject("metadata");
						if (metadataJSON != null && metadataJSON.has("nextStartToken")) {
							startToken = metadataJSON.get("nextStartToken").toString();
							try {
								updateLastRecordProcessedStmt.setString(1, startToken);
								updateLastRecordProcessedStmt.setLong(2, settingsId);
								updateLastRecordProcessedStmt.executeUpdate();
							} catch (SQLException e) {
								logEntry.incErrors("Error updating lastRecordProcessed ", e);
							}
						} else {
							// No more records to extact from global content
							startToken = null;
							try {
								updateLastRecordProcessedStmt.setString(1, "0");
								updateLastRecordProcessedStmt.setLong(2, settingsId);
								updateLastRecordProcessedStmt.executeUpdate();
							} catch (SQLException e) {
								logEntry.incErrors("Error updating lastRecordProcessed ", e);
							}
						}

					}
				} else {
					if (response.getResponseCode() == 401 || response.getResponseCode() == 504 || response.getResponseCode() == 503){
						numTries++;
						if (numTries >= 3){
							logEntry.incErrors("Error loading data after 3 attempts from" + url + " " + response.getResponseCode() + " " + response.getMessage());
						}else{
							try {
								Thread.sleep(1000 * 60 * 2); //Wait for 2 minutes before trying again
							} catch (InterruptedException e) {
								logEntry.incErrors("Error sleeping for 2 minutes for global contents", e);
							}
							accessToken = getAccessToken(settings);
							headers.put("Authorization", "Bearer " + accessToken);
						}
					}else {
						logEntry.incErrors("Error loading global content from " + url + " " + response.getResponseCode() + " " + response.getMessage());
					}
					try {
						updateLastRecordProcessedStmt.setString(1, startToken);
						updateLastRecordProcessedStmt.setLong(2, settingsId);
						updateLastRecordProcessedStmt.executeUpdate();
					} catch (SQLException e) {
						logEntry.incErrors("Error updating lastRecordProcessed ", e);
					}
					startToken = null;
				}
			}
			updateLastRecordProcessedStmt.close();
			logEntry.saveResults();
			logEntry.addNote("Completed " + numRecordsToExtract + " global content updates");
			logEntry.saveResults();
		} catch (SQLException e) {
			logEntry.incErrors("Error updating settings", e);
		}
		return updatedContent;
	}

	private boolean exportLibraryEntitlements(HooplaSettings2 settings, boolean globalContentUpdated, ArrayList<HooplaLibrarySettings> librarySettings) {
		logEntry.addNote("Starting library entitlements extraction");
		logEntry.saveResults();

		if (librarySettings.isEmpty()) {
			return false;
		}

		boolean hasUpdates = false;
		for (HooplaLibrarySettings librarySetting : librarySettings) {
			boolean libraryUpdates = false;
			boolean cleanUpRan = false;
			// Check if we need to do the clean-up of entitlements
			if (librarySetting.isCleanUpInstant() || librarySetting.isCleanUpFlex()) {
				cleanUpRan = cleanupLibraryEntitlements(librarySetting);
			}
			// Skip if the library doesn't have a Hoopla library ID
			if (!librarySetting.hasHooplaLibraryId()) {
				continue;
			}
			// Skip if the library doesn't have instant or flex enabled
			if (!librarySetting.isInstantEnabled() && !librarySetting.isFlexEnabled()) {
				continue;
			}

			boolean runFullUpdateForLibrary = settings.isRunFullUpdate() || librarySetting.isfullUpdateForLibrary();

			// Export Instant Entitlements only when running full update for library,
			// or when global contents get extracted (Full Update or Incremental Updates)
			if (librarySetting.isInstantEnabled() && (runFullUpdateForLibrary || globalContentUpdated)) {
				libraryUpdates |= exportLibraryEntitlementsForType(settings, librarySetting, HOOPLA_TYPE_INSTANT, runFullUpdateForLibrary);
			}

			// Export Flex Entitlements only when running full update for library,
			// or when global contents get extracted (Full Update or Incremental Updates)
			if (librarySetting.isFlexEnabled() && (runFullUpdateForLibrary || globalContentUpdated)) {
				libraryUpdates |= exportLibraryEntitlementsForType(settings, librarySetting, HOOPLA_TYPE_FLEX, runFullUpdateForLibrary);
			}

			if (libraryUpdates && librarySetting.isfullUpdateForLibrary()) {
				try {
					updateFullUpdateForLibraryStmt.setLong(1, librarySetting.getId());
					updateFullUpdateForLibraryStmt.executeUpdate();
				} catch (SQLException e) {
					logEntry.incErrors("Unable to update fullUpdateForLibrary for library setting " + librarySetting.getLibraryId(), e);
				}
			}

			if (libraryUpdates || cleanUpRan) {
				hasUpdates = true;
			}
		}

		// Update the timestamp for the settings after exporting all the library entitlements
		try{
			PreparedStatement updateSettingsStmt = null;
			if (settings.isRunFullUpdate()){
				if (!logEntry.hasErrors()) {
					updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfAllRecords = ?, lastRecordProcessed = 0 where id = ?");
				} else {
					//force another full update
					PreparedStatement reactiveFullUpdateStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set runFullUpdate = 1 where id = ?");
					reactiveFullUpdateStmt.setLong(1, settings.getSettingsId());
					reactiveFullUpdateStmt.executeUpdate();
				}
			}else{
				// Update the lastUpdateOfChangedRecords only if global content was updated
				if (globalContentUpdated){
					updateSettingsStmt = aspenConn.prepareStatement("UPDATE hoopla_settings set lastUpdateOfChangedRecords = ? where id = ?");
				}
			}
			if (updateSettingsStmt != null) {
				updateSettingsStmt.setLong(1, startTimeForLogging);
				updateSettingsStmt.setLong(2, settings.getSettingsId());
				updateSettingsStmt.executeUpdate();
				updateSettingsStmt.close();
				numRetries32HoursAfter = 0;
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error updating settings timestamp", e);
		}
		if (!hasUpdates) {
			logEntry.addNote("No updates found in library entitlements extraction");
			logEntry.saveResults();
		}
		return hasUpdates;
	}

	private boolean exportLibraryEntitlementsForType(HooplaSettings2 settings, HooplaLibrarySettings librarySetting, String hooplaType, boolean runFullUpdateForLibrary) {
		boolean updateEntitlements = false;
		String hooplaLibraryId = librarySetting.getHooplaLibraryId();
		if (hooplaLibraryId == null || hooplaLibraryId.isEmpty()) {
			return false;
		}
		String accessToken = settings.getAccessToken();
		if (accessToken == null || settings.getTokenExpirationTime() < (System.currentTimeMillis() / 1000)) {
			accessToken = getAccessToken(settings);
		}
		if (accessToken == null) {
			return false;
		}
		if (runFullUpdateForLibrary) {
			logEntry.addNote("Running entitlements full update for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ") (" + hooplaType + ")");
			logEntry.saveResults();
		}
		int recordExtractionBatchSize = settings.getRecordExtractionBatchSize();
		long lastUpdateOfChangedRecords = settings.getLastUpdateOfChangedRecords();
		long lastUpdateOfAllRecords = settings.getLastUpdateOfAllRecords();
		long lastUpdate = Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
		String hooplaAPIBaseURL = settings.getApiUrl();

		String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/entitlements?purchaseModel=" + hooplaType + "&limit=" + recordExtractionBatchSize;

		if (!runFullUpdateForLibrary && lastUpdate > 0) {
			url += "&startTime=" + lastUpdate;
		} else {
			// Full update for the library, only get active entitlements
			url += "&status=active";
		}

		// Load existing entitlements for the library when running a full update
		HashMap<Long, Long> existingEntitlements = runFullUpdateForLibrary ? loadExistingEntitlementsForLibrary(librarySetting.getLibraryId(), hooplaType) : null;

		HashMap<String, String> headers = new HashMap<>();
		headers.put("Authorization", "Bearer " + accessToken);
		headers.put("Content-Type", "application/json");
		headers.put("Accept", "application/json");

		String startToken = "0";
		int numEntitlements = 0;
		int numTries = 0;

		while (startToken != null) {
			String paginationUrl = url + "&startToken=" + startToken;
			WebServiceResponse response = NetworkUtils.getURL(paginationUrl, logger, headers);

			if (response.isSuccess()) {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				JSONArray entitlements = responseJSON.getJSONArray("entitlements");
				if (entitlements != null && !entitlements.isEmpty()) {
					numEntitlements += entitlements.length();
					updateEntitlementsInDB(entitlements, existingEntitlements, runFullUpdateForLibrary, hooplaType, librarySetting.getLibraryId());
					updateEntitlements = true;
				}

				JSONObject metadataJSON = responseJSON.optJSONObject("metadata");
				if (metadataJSON != null && metadataJSON.has("nextStartToken")) {
					startToken = metadataJSON.get("nextStartToken").toString();
				} else {
					startToken = null;
				}

			} else {
				if (response.getResponseCode() == 401 || response.getResponseCode() == 504 || response.getResponseCode() == 503){
					numTries++;
					if (numTries >= 3){
						logEntry.incErrors("Error loading entitlements after 3 attempts from" + url + " " + response.getResponseCode() + " " + response.getMessage());
					}else{
						try {
							Thread.sleep(1000 * 60 * 2); //Wait for 2 minutes before trying again
						} catch (InterruptedException e) {
							logEntry.incErrors("Error sleeping for 2 minutes for entitlements", e);
						}
						accessToken = getAccessToken(settings);
						headers.put("Authorization", "Bearer " + accessToken);
					}
				}else {
					logEntry.incErrors("Error loading entitlements from" + url + " " + response.getResponseCode() + " " + response.getMessage());
				}
				startToken = null;
			}

		}
		// Clean up the entitlements that are no longer in the API response
		// If Flex, also clean up the availability
		if (runFullUpdateForLibrary && !existingEntitlements.isEmpty()) {
			int numRemainingEntitlements = 0;
			for (Map.Entry<Long, Long> remainingEntitlement : existingEntitlements.entrySet()) {
				Long hooplaId = remainingEntitlement.getKey();
				Long entitlementId = remainingEntitlement.getValue();
				try {
					deleteHooplaEntitlementScopeStmt.setLong(1, entitlementId);
					deleteHooplaEntitlementScopeStmt.setLong(2, librarySetting.getLibraryId());
					deleteHooplaEntitlementScopeStmt.executeUpdate();
					logEntry.incEntitlementsDeleted();
				} catch (SQLException e) {
					logEntry.incErrors("Error deleting Hoopla entitlement scope for stale title " + hooplaId + " (library " + librarySetting.getLibraryId() + ")", e);
				}
				if (hooplaType.equals(HOOPLA_TYPE_FLEX)) {
					try {
						deleteFlexAvailabilityForLibraryStmt.setLong(1, hooplaId);
						deleteFlexAvailabilityForLibraryStmt.setLong(2, librarySetting.getLibraryId());
						deleteFlexAvailabilityForLibraryStmt.executeUpdate();
					} catch (SQLException e) {
						logEntry.incErrors("Error deleting Flex availability for stale title " + hooplaId + " (library " + librarySetting.getLibraryId() + ")", e);
					}
				}
				numRemainingEntitlements++;
				titlesNeedingReindex.add(hooplaId);
			}
			logEntry.addNote("Cleaned up " + numRemainingEntitlements + " remaining " + hooplaType + " entitlements for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")" );
			logEntry.saveResults();
		}

		logEntry.addNote("Exported " + numEntitlements + " " + hooplaType + " entitlements for library " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + ")");
		logEntry.saveResults();
		return updateEntitlements;
	}

	private ArrayList<HooplaLibrarySettings> loadLibraryHooplaSettings(long settingsId) {
		ArrayList<HooplaLibrarySettings> librarySettings = new ArrayList<>();
		try {
			getLibraryHooplaSettingsStmt.setLong(1, settingsId);
			ResultSet librarySettingsRS = getLibraryHooplaSettingsStmt.executeQuery();
			while (librarySettingsRS.next()) {
				librarySettings.add(new HooplaLibrarySettings(librarySettingsRS));
			}
			librarySettingsRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error loading Hoopla library settings for settingsId " + settingsId, e);
		}
		return librarySettings;
	}

	private HashMap<Long, Long> loadExistingEntitlementsForLibrary(long scopeLibraryId, String hooplaType) {
		HashMap<Long, Long> existingEntitlements = new HashMap<>();
		try {
			getExistingEntitlementsForLibraryStmt.setLong(1, scopeLibraryId);
			getExistingEntitlementsForLibraryStmt.setString(2, hooplaType);
			ResultSet existingEntitlementsRS = getExistingEntitlementsForLibraryStmt.executeQuery();
			while (existingEntitlementsRS.next()) {
				existingEntitlements.put(
					existingEntitlementsRS.getLong("hooplaId"),
					existingEntitlementsRS.getLong("entitlementId")
				);
			}
			existingEntitlementsRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing Hoopla entitlements for library " + scopeLibraryId + " (" + hooplaType + ")", e);
		}
		return existingEntitlements;
	}

	private ArrayList<Long> loadFlexEntitlementsForLibrary(long scopeLibraryId) {
		ArrayList<Long> flexEntitlements = new ArrayList<>();
		try {
			getFlexEntitlementsForLibraryStmt.setLong(1, scopeLibraryId);
			getFlexEntitlementsForLibraryStmt.setString(2, HOOPLA_TYPE_FLEX);
			ResultSet flexEntitlementsRS = getFlexEntitlementsForLibraryStmt.executeQuery();
			while (flexEntitlementsRS.next()) {
				flexEntitlements.add(flexEntitlementsRS.getLong("hooplaId"));
			}
			flexEntitlementsRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error loading Flex entitlements for library " + scopeLibraryId, e);
		}
		return flexEntitlements;
	}

	private void updateFlexAvailabilityInDB(JSONArray availabilityArray, long scopeLibraryId) {
		for (int i = 0; i < availabilityArray.length(); i++) {
			try {
				JSONObject availabilityInfo = availabilityArray.getJSONObject(i);
				if (!availabilityInfo.has("contentId")) {
					logEntry.incErrors("Flex availability response missing contentId for library " + scopeLibraryId);
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
						getExistingFlexAvailabilityStmt.setLong(1, hooplaId);
						getExistingFlexAvailabilityStmt.setLong(2, scopeLibraryId);
						ResultSet existingAvailabilityRS = getExistingFlexAvailabilityStmt.executeQuery();
						if (existingAvailabilityRS.next()) {
							int existingholdsQueueSize = existingAvailabilityRS.getInt("holdsQueueSize");
							int existingAvailableCopies = existingAvailabilityRS.getInt("availableCopies");
							int existingTotalCopies = existingAvailabilityRS.getInt("totalCopies");
							String existingStatus = existingAvailabilityRS.getString("status");
							if (existingholdsQueueSize != holdsQueueSize || existingAvailableCopies != availableCopies || existingTotalCopies != totalCopies || !Objects.equals(existingStatus, status)) {
								availabilityChanged = true;
							}
						} else {
							availabilityChanged = true;
						}
						existingAvailabilityRS.close();

						if (availabilityChanged) {
							upsertFlexAvailabilityStmt.setLong(1, hooplaId);
							upsertFlexAvailabilityStmt.setLong(2, scopeLibraryId);
							upsertFlexAvailabilityStmt.setInt(3, holdsQueueSize);
							upsertFlexAvailabilityStmt.setInt(4, availableCopies);
							upsertFlexAvailabilityStmt.setInt(5, totalCopies);
							upsertFlexAvailabilityStmt.setString(6, status);
							upsertFlexAvailabilityStmt.executeUpdate();
							titlesNeedingReindex.add(hooplaId);
							logEntry.incAvailabilityChanges();
						}
					} catch (SQLException e) {
						logEntry.incErrors("Error updating Flex availability for title " + hooplaId + " (library " + scopeLibraryId + ")", e);
					}
				}
			} catch (JSONException e) {
				logEntry.incErrors("Error parsing Flex availability JSON for library " + scopeLibraryId, e);
			}
		}
	}

	private void updateEntitlementsInDB(JSONArray entitlements, HashMap<Long, Long> existingEntitlements, boolean runFullUpdateForLibrary, String hooplaType, long scopeLibraryId) {
		for (int i = 0; i < entitlements.length(); i++) {
			try {
				JSONObject entitlement = entitlements.getJSONObject(i);
				long hooplaId = entitlement.getLong("contentId");
				Long entitlementId = null;
				try {
					getHooplaEntitlementIdStmt.setLong(1, hooplaId);
					getHooplaEntitlementIdStmt.setString(2, hooplaType);
					ResultSet entitlementRS = getHooplaEntitlementIdStmt.executeQuery();
					if (!entitlementRS.next()) {
						addHooplaEntitlementStmt.setLong(1, hooplaId);
						addHooplaEntitlementStmt.setString(2, hooplaType);
						addHooplaEntitlementStmt.executeUpdate();
						ResultSet generatedKeys = addHooplaEntitlementStmt.getGeneratedKeys();
						if (generatedKeys.next()) {
							entitlementId = generatedKeys.getLong(1);
						}
					} else {
						entitlementId = entitlementRS.getLong("id");
					}
					entitlementRS.close();
				} catch (SQLException e) {
					logEntry.incErrors("Error inserting Hoopla entitlement for title " + hooplaId + " (" + hooplaType + ")", e);
				}

				if (runFullUpdateForLibrary) {
					try {
						getHooplaEntitlementScopeStmt.setLong(1, entitlementId);
						getHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
						ResultSet scopeRS = getHooplaEntitlementScopeStmt.executeQuery();
						if (!scopeRS.next()) {
							try {
								addHooplaEntitlementScopeStmt.setLong(1, entitlementId);
								addHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
								addHooplaEntitlementScopeStmt.executeUpdate();
								logEntry.incEntitlementsUpdated();
							} catch (SQLException e) {
								logEntry.incErrors("Error inserting Hoopla entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")", e);
							}
						}
					} catch (SQLException e) {
						logEntry.incErrors("Error checking existing entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")" + " (" + hooplaType + ")", e);
					}

					if (existingEntitlements != null) {
						existingEntitlements.remove(hooplaId);
					}
					titlesNeedingReindex.add(hooplaId);
				} else {
					if (entitlement.getBoolean("active")) {
						try {
							getHooplaEntitlementScopeStmt.setLong(1, entitlementId);
							getHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
							ResultSet scopeRS = getHooplaEntitlementScopeStmt.executeQuery();
							if (!scopeRS.next()) {
								try {
									addHooplaEntitlementScopeStmt.setLong(1, entitlementId);
									addHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
									addHooplaEntitlementScopeStmt.executeUpdate();
									logEntry.incEntitlementsUpdated();
								} catch (SQLException e) {
									logEntry.incErrors("Error inserting Hoopla entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")" + " (" + hooplaType + ")", e);
								}
							}
						} catch (SQLException e) {
							logEntry.incErrors("Error checking existing entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")" + " (" + hooplaType + ")", e);
						}
						titlesNeedingReindex.add(hooplaId);
					} else {
						try {
							getHooplaEntitlementScopeStmt.setLong(1, entitlementId);
							getHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
							ResultSet scopeRS = getHooplaEntitlementScopeStmt.executeQuery();
							if (scopeRS.next()) {
								try {
									deleteHooplaEntitlementScopeStmt.setLong(1, entitlementId);
									deleteHooplaEntitlementScopeStmt.setLong(2, scopeLibraryId);
									deleteHooplaEntitlementScopeStmt.executeUpdate();
									logEntry.incEntitlementsDeleted();
								} catch (SQLException e) {
									logEntry.incErrors("Error deleting Hoopla entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")" + " (" + hooplaType + ")", e);
								}
							}
							scopeRS.close();
							try {
								deleteFlexAvailabilityForLibraryStmt.setLong(1, hooplaId);
								deleteFlexAvailabilityForLibraryStmt.setLong(2, scopeLibraryId);
								deleteFlexAvailabilityForLibraryStmt.executeUpdate();
							} catch (SQLException e) {
								logEntry.incErrors("Error deleting Flex availability for entitlement ID " + entitlementId + " (library " + scopeLibraryId + ")", e);
							}
						} catch (SQLException e) {
							logEntry.incErrors("Error checking existing entitlement scope for title " + hooplaId + " (library " + scopeLibraryId + ")", e);
						}
						titlesNeedingReindex.add(hooplaId);
					}
				}
			} catch (JSONException e) {
				logEntry.incErrors("Error parsing entitlement JSON ", e);
			}
		}
	}

	private boolean getFlexAvailability(HooplaSettings2 settings, ArrayList<HooplaLibrarySettings> librarySettings) {
		logEntry.addNote("Starting Flex availability update");
		logEntry.saveResults();

		if (librarySettings.isEmpty()) {
			return false;
		}

		String hooplaAPIBaseURL = settings.getApiUrl();
		String accessToken = settings.getAccessToken();
		long tokenExpirationTime = settings.getTokenExpirationTime();
		if (accessToken == null || tokenExpirationTime < (System.currentTimeMillis() / 1000)) {
			accessToken = getAccessToken(settings);
		}
		if (accessToken == null) {
			logEntry.incErrors("Could not load access token for Flex availability");
			return false;
		}

		boolean hasUpdates = false;
		for (HooplaLibrarySettings librarySetting : librarySettings) {
			// Skip if the library doesn't have flex enabled
			if (!librarySetting.isFlexEnabled() || !librarySetting.hasHooplaLibraryId()) {
				continue;
			}
			ArrayList<Long> flexTitleIds = loadFlexEntitlementsForLibrary(librarySetting.getLibraryId());
			if (flexTitleIds.isEmpty()) {
				logEntry.incErrors("No Flex entitlements found for library " + librarySetting.getLibraryId() + ", please run full update for this library");
				logEntry.saveResults();
				continue;
			}

			int numFlexTitlesProcessed = 0;
			// Hardcoded batch size of 1 for now
			int flexBatchSize = 1;
			while (numFlexTitlesProcessed < flexTitleIds.size()) {
				List<Long> flexBatch = flexTitleIds.subList(numFlexTitlesProcessed, numFlexTitlesProcessed + flexBatchSize);
				StringBuilder contentIdsString = new StringBuilder();
				for (Long hooplaId : flexBatch) {
					if (contentIdsString.length() > 0) {
						contentIdsString.append(',');
					}
					contentIdsString.append(hooplaId);
				}

				@SuppressWarnings("DuplicatedCode")
				HashMap<String, String> headers = new HashMap<>();
				headers.put("Authorization", "Bearer " + accessToken);
				headers.put("Content-Type", "application/json");
				headers.put("Accept", "application/json");

				String url = hooplaAPIBaseURL + "/api/v1/libraries/" + librarySetting.getHooplaLibraryId() + "/content/info?contentIds=" + contentIdsString;
				WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
				int numTries = 0;
				if (response.isSuccess()) {
					try {
						JSONArray availabilityArray = new JSONArray(response.getMessage());
						updateFlexAvailabilityInDB(availabilityArray, librarySetting.getLibraryId());
						numFlexTitlesProcessed += availabilityArray.length();
					} catch (JSONException e) {
						logEntry.incErrors("Error parsing Flex availability response for library " + librarySetting.getLibraryId(), e);
					}
				} else {
					if (response.getResponseCode() == 401 || response.getResponseCode() == 503 || response.getResponseCode() == 504) {
						numTries++;
						if (numTries >= 3){
							logEntry.incErrors("Error getting Flex availability after 3 attempts from " + url + " " + response.getResponseCode() + " " + response.getMessage());
						} else {
							try {
								Thread.sleep(1000 * 60); //Wait for 1 minute before trying again
							} catch (InterruptedException e) {
								logEntry.incErrors("Error sleeping for 1 minutes for Flex availability", e);
							}
						}
					} else {
						logEntry.incErrors("Error getting Flex availability from " + url + " " + response.getResponseCode() + " " + response.getMessage());
					}
				}
			}

			if (numFlexTitlesProcessed > 0) {
				hasUpdates = true;
				logEntry.addNote("Updated Flex availability for " + librarySetting.getLibraryDisplayName() + " (Hoopla Library ID: " + librarySetting.getHooplaLibraryId() + "), processed " + numFlexTitlesProcessed + " titles");
				logEntry.saveResults();
			}
			if (hasUpdates) {
				logEntry.addNote("Completed Flex availability updates.");
				logEntry.saveResults();
			}
		}
		return hasUpdates;
	}

	public boolean exportSingleHooplaTitle(String singleWorkId) {
		boolean updatesRun = false;
		try{
			logEntry.addNote("Doing extract of single work " + singleWorkId);
			logEntry.saveResults();
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from hoopla_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				HooplaSettings2 settings = new HooplaSettings2(getSettingsRS);
				String hooplaAPIBaseURL = settings.getApiUrl();
				ArrayList<HooplaLibrarySettings> librarySettings = loadLibraryHooplaSettings(settings.getSettingsId());
				String accessToken = getAccessToken(settings);
				if (accessToken == null) {
					logEntry.incErrors("Could not load access token");
					return false;
				}
				String countryCode = settings.getCountryCode();

				// Get the global content for the given hoopla id
				String globalContentUrl = hooplaAPIBaseURL + "/api/v1/global-contents?countryCodes=" + countryCode + "&limit=1";
				long numericSingleWorkId = Long.parseLong(singleWorkId);
				globalContentUrl += "&startToken=" + (numericSingleWorkId - 1);
				HashMap<String, String> headers = new HashMap<>();
				headers.put("Authorization", "Bearer " + accessToken);
				headers.put("Content-Type", "application/json");
				headers.put("Accept", "application/json");
				WebServiceResponse globalContentResponse = NetworkUtils.getURL(globalContentUrl, logger, headers);
				if (!globalContentResponse.isSuccess()){
					logEntry.incErrors("Could not get global content from " + globalContentUrl + " " + globalContentResponse.getMessage());
					logEntry.saveResults();
					logger.error("Could not get global content from " + globalContentUrl + " " + globalContentResponse.getMessage());
				}else {
					JSONObject responseJSON = new JSONObject(globalContentResponse.getMessage());
					if (responseJSON.has("contents")) {
						JSONArray responseTitles = responseJSON.getJSONArray("contents");
						if (responseTitles != null && !responseTitles.isEmpty()) {
							updateTitlesInDB(responseTitles, true, false);
							updatesRun = true;
							logger.warn("Updated global content for " + numericSingleWorkId);
						}
					}
				}

				// Loop through each library and get the entitlement for the given hoopla id
				for (HooplaLibrarySettings librarySetting : librarySettings) {
					// Skip if the library doesn't have instant or flex enabled
					if (!librarySetting.isInstantEnabled() && !librarySetting.isFlexEnabled()) {
						continue;
					}
					if (librarySetting.getHooplaLibraryId() == null) {
						logEntry.incErrors("Hoopla Library ID for library setting " + librarySetting.getLibraryDisplayName() + " is null, skipping");
						continue;
					}
					// We don't know if this title is instant or flex for the current library, so we try instant first
					if (librarySetting.isInstantEnabled()) {
						String entitlementUrl = hooplaAPIBaseURL + "/api/v1/libraries/" + librarySetting.getHooplaLibraryId() + "/entitlements?purchaseModel=Instant&limit=1&status=active&startToken=" + (numericSingleWorkId - 1);
						WebServiceResponse entitlementResponse = NetworkUtils.getURL(entitlementUrl, logger, headers);
						if (!entitlementResponse.isSuccess()) {
							logEntry.incErrors("Could not get entitlements from " + entitlementUrl + " " + entitlementResponse.getMessage());
						} else {
							JSONObject responseJSON = new JSONObject(entitlementResponse.getMessage());
							if (responseJSON.has("entitlements")) {
								JSONArray responseEntitlements = responseJSON.getJSONArray("entitlements");
								if (responseEntitlements != null && !responseEntitlements.isEmpty()) {
									JSONObject entitlement = responseEntitlements.getJSONObject(0);

									// Verify contentId is the same as the given hoopla Id
									if (entitlement.has("contentId") && entitlement.getLong("contentId") == numericSingleWorkId) {
										if (entitlement.has("active") && entitlement.getBoolean("active")) {
											// Only update the entitlement if it is active
											updateEntitlementsInDB(responseEntitlements, null, false, HOOPLA_TYPE_INSTANT, librarySetting.getLibraryId());
											logger.warn("Updated entitlement for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Instant.");
											updatesRun = true;
											continue;
										} else {
											logger.warn("Entitlement is not active for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Instant.");
										}
									} else {
										logger.warn("Content ID for entitlement does not match the given hoopla ID, this record might not be active for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Instant.");
									}
								}
							}
						}
					}

					if (librarySetting.isFlexEnabled()) {
						String entitlementUrl = hooplaAPIBaseURL + "/api/v1/libraries/" + librarySetting.getHooplaLibraryId() + "/entitlements?purchaseModel=Flex&limit=1&status=active&startToken=" + (numericSingleWorkId - 1);
						WebServiceResponse entitlementResponse = NetworkUtils.getURL(entitlementUrl, logger, headers);
						if (!entitlementResponse.isSuccess()) {
							logEntry.incErrors("Could not get entitlements from " + entitlementUrl + " " + entitlementResponse.getMessage());
						} else {
							JSONObject responseJSON = new JSONObject(entitlementResponse.getMessage());
							if (responseJSON.has("entitlements")) {
								JSONArray responseEntitlements = responseJSON.getJSONArray("entitlements");
								if (responseEntitlements != null && !responseEntitlements.isEmpty()) {
									JSONObject entitlement = responseEntitlements.getJSONObject(0);

									// Verify contentId is the same as the given hoopla ID
									if (entitlement.has("contentId") && entitlement.getLong("contentId") == numericSingleWorkId) {
										if (entitlement.has("active") && entitlement.getBoolean("active")) {
											// Only update the entitlement if it is active
											updateEntitlementsInDB(responseEntitlements, null, false, HOOPLA_TYPE_FLEX, librarySetting.getLibraryId());
											logger.warn("Updated entitlement for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Flex.");

											// This is an active fle entitlement, so we need to update the availability
											String availabilityUrl = hooplaAPIBaseURL + "/api/v1/libraries/" + librarySetting.getHooplaLibraryId() + "/content/info?contentIds=" + numericSingleWorkId;
											WebServiceResponse availabilityResponse = NetworkUtils.getURL(availabilityUrl, logger, headers);
											if (!availabilityResponse.isSuccess()) {
												logEntry.incErrors("Could not get availability from " + availabilityUrl + " " + availabilityResponse.getMessage());
											} else {
												JSONArray availabilityArray = new JSONArray(availabilityResponse.getMessage());
												updateFlexAvailabilityInDB(availabilityArray, librarySetting.getLibraryId());
												logger.warn("Updated availability for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Flex.");
												updatesRun = true;
												continue;
											}
										} else {
											logger.warn("Entitlement is not active for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Flex.");
										}
									} else {
										logger.warn("Content ID for entitlement " + entitlement.getLong("contentId") + " does not match the given hoopla ID, this record might not be active for Hoopla Library ID " + librarySetting.getHooplaLibraryId() + " for Flex.");
									}
								}
							}
						}
					}
				}
				// Flush the records to reindex
				if (updatesRun) {
					// Add the title to the list regardless
					titlesNeedingReindex.add(numericSingleWorkId);
					flushRecordsToReindex();
				}
				logEntry.addNote("Completed extract of single work " + singleWorkId + " for setting " + settings.getSettingsId());
				logEntry.saveResults();
				logger.warn("Completed extract of single work " + singleWorkId + " for setting " + settings.getSettingsId());
			}
			if (numSettings == 0){
				logEntry.incErrors("Unable to find settings for Hoopla when processing single title, please add settings to the database");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting single hoopla title", e);
		}
		return updatesRun;
	}

	private void updateTitlesInDB(JSONArray responseTitles, boolean forceRegrouping, boolean doFullReload) {
		for (int i = 0; i < responseTitles.length(); i++){
			try {
				JSONObject curTitle = responseTitles.getJSONObject(i);

				String rawResponse = curTitle.toString();
				checksumCalculator.reset();
				checksumCalculator.update(rawResponse.getBytes());
				long rawChecksum = checksumCalculator.getValue();

				long hooplaId = curTitle.getLong("id"); //formerly titleId was used, but this is not unique for TV series

				HooplaTitle2 existingTitle = existingRecords.get(hooplaId);
				boolean recordUpdated = false;
				if (existingTitle != null) {
					//Record exists
					if ((existingTitle.getChecksum() != rawChecksum) || (existingTitle.getRawResponseLength() != rawResponse.length())){
						recordUpdated = true;
						logEntry.incUpdated();
					}
					existingTitle.setFoundInExport(true);
				}else{
					logEntry.incAdded();
				}

				if (existingTitle == null){
					addHooplaTitleToDB.setLong(1, hooplaId);
					addHooplaTitleToDB.setString(2, curTitle.getString("title"));
					addHooplaTitleToDB.setString(3, curTitle.getString("format"));
					addHooplaTitleToDB.setBoolean(4, curTitle.getBoolean("isParentalAdvisory"));
					addHooplaTitleToDB.setBoolean(5, curTitle.getBoolean("isDemo"));
					addHooplaTitleToDB.setBoolean(6, curTitle.getBoolean("containsProfanity"));
					if (curTitle.has("ratings")) {
						JSONArray ratingsArray = curTitle.getJSONArray("ratings");
						if (!ratingsArray.isEmpty()) {
							addHooplaTitleToDB.setString(7, ratingsArray.getJSONObject(0).getString("ratingValue"));
						} else {
							addHooplaTitleToDB.setString(7, "");
						}
					} else {
						addHooplaTitleToDB.setString(7, "");
					}
					addHooplaTitleToDB.setBoolean(8, curTitle.getBoolean("isAbridged"));
					addHooplaTitleToDB.setBoolean(9, curTitle.getBoolean("isForChildren"));
					if (curTitle.has("ppuPrices")) {
						JSONArray ppuPricesArray = curTitle.getJSONArray("ppuPrices");
						if (!ppuPricesArray.isEmpty()) {
							addHooplaTitleToDB.setDouble(10, ppuPricesArray.getJSONObject(0).getDouble("ppuPrice"));
						} else {
							addHooplaTitleToDB.setDouble(10, 0.0);
						}
					} else {
						addHooplaTitleToDB.setDouble(10, 0.0);
					}
					addHooplaTitleToDB.setLong(11, rawChecksum);
					addHooplaTitleToDB.setString(12, rawResponse);
					addHooplaTitleToDB.setLong(13, startTimeForLogging);
					try {
						addHooplaTitleToDB.executeUpdate();
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error adding hoopla title to database record " + hooplaId + " " + curTitle.getString("title"), e);
					}
				}else if (recordUpdated || doFullReload || forceRegrouping){
					updateHooplaTitleInDB.setString(1, curTitle.getString("title"));
					updateHooplaTitleInDB.setString(2, curTitle.getString("format"));
					updateHooplaTitleInDB.setBoolean(3, curTitle.getBoolean("isParentalAdvisory"));
					updateHooplaTitleInDB.setBoolean(4, curTitle.getBoolean("isDemo"));
					updateHooplaTitleInDB.setBoolean(5, curTitle.getBoolean("containsProfanity"));
					if (curTitle.has("ratings")) {
						JSONArray ratingsArray = curTitle.getJSONArray("ratings");
						if (!ratingsArray.isEmpty()) {
							updateHooplaTitleInDB.setString(6, ratingsArray.getJSONObject(0).getString("ratingValue"));
						} else {
							updateHooplaTitleInDB.setString(6, "");
						}
					} else {
						updateHooplaTitleInDB.setString(6, "");
					}
					updateHooplaTitleInDB.setBoolean(7, curTitle.getBoolean("isAbridged"));
					updateHooplaTitleInDB.setBoolean(8, curTitle.getBoolean("isForChildren"));
					if (curTitle.has("ppuPrices")) {
						JSONArray ppuPricesArray = curTitle.getJSONArray("ppuPrices");
						if (!ppuPricesArray.isEmpty()) {
							updateHooplaTitleInDB.setDouble(9, ppuPricesArray.getJSONObject(0).getDouble("ppuPrice"));
						} else {
							updateHooplaTitleInDB.setDouble(9, 0.0);
						}
					} else {
						updateHooplaTitleInDB.setDouble(9, 0.0);
					}
					updateHooplaTitleInDB.setLong(10, rawChecksum);
					updateHooplaTitleInDB.setString(11, rawResponse);
					updateHooplaTitleInDB.setLong(12, existingTitle.getId());

					try {
						updateHooplaTitleInDB.executeUpdate();
					}catch (DataTruncation e) {
						logEntry.addNote("Record " + hooplaId + " " + curTitle.getString("title") + " contained invalid data " + e);
					}catch (SQLException e){
						logEntry.incErrors("Error updating hoopla data in database for record " + hooplaId + " " + curTitle.getString("title"), e);
					}
				}
			}catch (Exception e){
				logEntry.incErrors("Error updating hoopla data in db", e);
			}
		}
	}

	private void indexRecord(String groupedWorkId) {
		getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
	}

	private String getAccessToken(HooplaSettings2 settings) {
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

	public void exporter2CleanUp() {
		try{
			addHooplaTitleToDB.close();
			addHooplaTitleToDB = null;
			updateHooplaTitleInDB.close();
			updateHooplaTitleInDB = null;
			deleteHooplaItemStmt.close();
			deleteHooplaItemStmt = null;
			getLibraryHooplaSettingsStmt.close();
			getLibraryHooplaSettingsStmt = null;
			updateFullUpdateForLibraryStmt.close();
			updateFullUpdateForLibraryStmt = null;
			getHooplaEntitlementIdStmt.close();
			getHooplaEntitlementIdStmt = null;
			addHooplaEntitlementStmt.close();
			addHooplaEntitlementStmt = null;
			getHooplaEntitlementScopeStmt.close();
			getHooplaEntitlementScopeStmt = null;
			addHooplaEntitlementScopeStmt.close();
			addHooplaEntitlementScopeStmt = null;
			deleteHooplaEntitlementScopeStmt.close();
			deleteHooplaEntitlementScopeStmt = null;
			entitlementHasScopesStmt.close();
			entitlementHasScopesStmt = null;
			deleteHooplaEntitlementByIdStmt.close();
			deleteHooplaEntitlementByIdStmt = null;
			getExistingEntitlementsForLibraryStmt.close();
			getExistingEntitlementsForLibraryStmt = null;
			getFlexEntitlementsForLibraryStmt.close();
			getFlexEntitlementsForLibraryStmt = null;
			upsertFlexAvailabilityStmt.close();
			upsertFlexAvailabilityStmt = null;
			getExistingFlexAvailabilityStmt.close();
			getExistingFlexAvailabilityStmt = null;
			deleteFlexAvailabilityForLibraryStmt.close();
			deleteFlexAvailabilityForLibraryStmt = null;
		}catch (Exception e){
			logger.error("Error closing Hoopla exporter2 statements", e);
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

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private RecordGroupingProcessor getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private void regroupAllRecords(Connection dbConn, long settingsId, GroupedWorkIndexer indexer, HooplaExtractLogEntry2 logEntry)  throws SQLException {
		try {
			logEntry.addNote("Starting to regroup all records");
			PreparedStatement getAllRecordsToRegroupStmt = dbConn.prepareStatement("SELECT hooplaId, permanent_id, UNCOMPRESS(rawResponse) as rawResponse from hoopla_export left join grouped_work_primary_identifiers on type = 'hoopla' AND grouped_work_primary_identifiers.identifier = hoopla_export.hooplaId inner join grouped_work on grouped_work_id = grouped_work.id", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet allRecordsToRegroupRS = getAllRecordsToRegroupStmt.executeQuery();
			while (allRecordsToRegroupRS.next()) {
				logEntry.incRecordsRegrouped();
				long recordIdentifier = allRecordsToRegroupRS.getLong("hooplaId");
				String originalGroupedWorkId;
				originalGroupedWorkId = allRecordsToRegroupRS.getString("permanent_id");
				if (originalGroupedWorkId == null) {
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
				if (logEntry.getNumChangedAfterGrouping() % 1000 == 0) {
					indexer.processScheduledWorks(logEntry, false, -1);
				}
			}

			//Finish reindexing anything that just changed
			if (logEntry.getNumChangedAfterGrouping() > 0) {
				indexer.processScheduledWorks(logEntry, false, -1);
			}
		}catch (Exception e){
			logEntry.incErrors("Error regrouping records", e);
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
