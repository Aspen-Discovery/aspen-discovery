package org.aspendiscovery.omeka;

import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.Date;
import java.util.HashMap;
import java.util.zip.CRC32;

public class OmekaExtractor {
	private static final int ITEMS_PER_PAGE = 100;
	private static final String PRIMARY_MEDIA_KEY = "aspen:primaryMedia";

	private final String serverName;
	private final OmekaSetting setting;
	private final OmekaExportLogEntry logEntry;
	private final Logger logger;
	private final Connection aspenConn;
	private final Ini configIni;

	private final Long startTimeForLogging;
	private final CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addTitleStmt;
	private PreparedStatement updateTitleStmt;
	private PreparedStatement updateLastSeenStmt;
	private PreparedStatement markTitleDeletedStmt;

	private GroupedWorkIndexer groupedWorkIndexer;
	private RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	public OmekaExtractor(String serverName, Connection aspenConn, OmekaSetting setting, Ini configIni, OmekaExportLogEntry logEntry, Logger logger) {
		this.serverName = serverName;
		this.aspenConn = aspenConn;
		this.setting = setting;
		this.configIni = configIni;
		this.logEntry = logEntry;
		this.logger = logger;

		Date startTime = new Date();
		startTimeForLogging = startTime.getTime() / 1000;
	}

	public boolean exportOmekaData() {
		try {
			addTitleStmt = aspenConn.prepareStatement("INSERT INTO omeka_title (settingId, omekaId, title, mediaType, thumbnailUrl, itemSetIds, rawChecksum, rawResponseLength, rawResponse, dateFirstDetected, lastSeen, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, COMPRESS(?), ?, ?, 0)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateTitleStmt = aspenConn.prepareStatement("UPDATE omeka_title SET title = ?, mediaType = ?, thumbnailUrl = ?, itemSetIds = ?, rawChecksum = ?, rawResponseLength = ?, rawResponse = COMPRESS(?), lastSeen = ?, deleted = 0 WHERE id = ?");
			updateLastSeenStmt = aspenConn.prepareStatement("UPDATE omeka_title SET lastSeen = ? WHERE id = ?");
			markTitleDeletedStmt = aspenConn.prepareStatement("UPDATE omeka_title SET deleted = 1 WHERE id = ?");

			logEntry.addNote("Starting update from Omeka for setting " + setting.getName());
			logEntry.saveResults();

			HashMap<Long, OmekaTitle> existingTitles = loadExistingTitles();

			boolean hadErrorsExtracting = extractItems(existingTitles);

			if (!hadErrorsExtracting) {
				removeTitlesNotSeenInExport(existingTitles);
			}

			logEntry.addNote("Processing records to reload");
			logEntry.saveResults();
			processRecordsToReload();

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}

			setLastUpdateTimeForSetting();

			addTitleStmt.close();
			updateTitleStmt.close();
			updateLastSeenStmt.close();
			markTitleDeletedStmt.close();
		} catch (Exception e) {
			logEntry.incErrors("Error exporting Omeka data", e);
		}
		return logEntry.getNumChanges() > 0;
	}

	private HashMap<Long, OmekaTitle> loadExistingTitles() throws SQLException {
		HashMap<Long, OmekaTitle> existingTitles = new HashMap<>();
		PreparedStatement getExistingTitlesStmt = aspenConn.prepareStatement("SELECT id, omekaId, rawChecksum, rawResponseLength, deleted FROM omeka_title WHERE settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getExistingTitlesStmt.setLong(1, setting.getId());
		ResultSet existingTitlesRS = getExistingTitlesStmt.executeQuery();
		while (existingTitlesRS.next()) {
			OmekaTitle existingTitle = new OmekaTitle(
				existingTitlesRS.getLong("id"),
				existingTitlesRS.getLong("omekaId"),
				existingTitlesRS.getLong("rawChecksum"),
				existingTitlesRS.getLong("rawResponseLength"),
				existingTitlesRS.getBoolean("deleted")
			);
			existingTitles.put(existingTitle.getOmekaId(), existingTitle);
		}
		existingTitlesRS.close();
		getExistingTitlesStmt.close();
		return existingTitles;
	}

	private boolean extractItems(HashMap<Long, OmekaTitle> existingTitles) {
		boolean hadErrors = false;
		int page = 1;
		while (true) {
			String url = buildApiUrl("/api/items", "page=" + page + "&per_page=" + ITEMS_PER_PAGE + "&sort_by=id&sort_order=asc");
			WebServiceResponse response = callOmekaWithRetries(url);
			if (response == null) {
				logEntry.incErrors("Did not get a successful API response from " + url);
				hadErrors = true;
				break;
			}
			try {
				JSONArray items = new JSONArray(response.getMessage());
				if (items.isEmpty()) {
					break;
				}
				logEntry.incNumProducts(items.length());
				for (int i = 0; i < items.length(); i++) {
					processItem(items.getJSONObject(i), existingTitles);
				}
				logEntry.saveResults();
				if (items.length() < ITEMS_PER_PAGE) {
					break;
				}
			} catch (JSONException e) {
				logEntry.incErrors("Could not parse response from " + url + " as JSON", e);
				hadErrors = true;
				break;
			}
			page++;
		}
		return hadErrors;
	}

	private void processItem(JSONObject item, HashMap<Long, OmekaTitle> existingTitles) {
		try {
			long omekaId = item.getLong("o:id");
			OmekaTitle existingTitle = existingTitles.get(omekaId);

			boolean isPublic = item.optBoolean("o:is_public", true);
			if (!isPublic) {
				if (existingTitle != null && !existingTitle.isDeleted()) {
					removeTitle(existingTitle.getId());
					logEntry.incDeleted();
				}
				if (existingTitle != null) {
					existingTitle.setFoundInExport(true);
				}
				logEntry.incSkipped();
				return;
			}

			String rawResponse = item.toString();
			checksumCalculator.reset();
			checksumCalculator.update(rawResponse.getBytes(StandardCharsets.UTF_8));
			long rawChecksum = checksumCalculator.getValue();
			long rawResponseLength = rawResponse.length();

			boolean isNewTitle = existingTitle == null;
			boolean titleWasDeleted = !isNewTitle && existingTitle.isDeleted();
			boolean titleChanged = !isNewTitle && (existingTitle.getChecksum() != rawChecksum || existingTitle.getRawResponseLength() != rawResponseLength);
			if (!isNewTitle) {
				existingTitle.setFoundInExport(true);
			}

			boolean saveNeeded = isNewTitle || titleChanged || titleWasDeleted;
			if (!saveNeeded) {
				updateLastSeenStmt.setLong(1, startTimeForLogging);
				updateLastSeenStmt.setLong(2, existingTitle.getId());
				updateLastSeenStmt.executeUpdate();
				if (setting.doFullReload()) {
					regroupAndIndexTitle(loadStoredResponse(existingTitle.getId()), existingTitle.getId());
				} else {
					logEntry.incSkipped();
				}
				return;
			}

			String title = getTitleForItem(item);
			String itemSetIds = getItemSetIdsForItem(item);
			JSONObject primaryMedia = fetchPrimaryMedia(item);
			String mediaType = null;
			String thumbnailUrl = null;
			if (primaryMedia != null) {
				item.put(PRIMARY_MEDIA_KEY, primaryMedia);
				mediaType = primaryMedia.optString("o:media_type", null);
				JSONObject thumbnailUrls = primaryMedia.optJSONObject("o:thumbnail_urls");
				if (thumbnailUrls != null) {
					thumbnailUrl = thumbnailUrls.optString("large", null);
				}
			}
			String storedResponse = item.toString();

			long titleId;
			if (isNewTitle) {
				addTitleStmt.setLong(1, setting.getId());
				addTitleStmt.setLong(2, omekaId);
				addTitleStmt.setString(3, title);
				addTitleStmt.setString(4, mediaType);
				addTitleStmt.setString(5, thumbnailUrl);
				addTitleStmt.setString(6, itemSetIds);
				addTitleStmt.setLong(7, rawChecksum);
				addTitleStmt.setLong(8, rawResponseLength);
				addTitleStmt.setString(9, storedResponse);
				addTitleStmt.setLong(10, startTimeForLogging);
				addTitleStmt.setLong(11, startTimeForLogging);
				addTitleStmt.executeUpdate();
				ResultSet generatedKeys = addTitleStmt.getGeneratedKeys();
				if (!generatedKeys.next()) {
					logEntry.incErrors("Could not add Omeka item " + omekaId + " to the database, did not get the Aspen ID back");
					return;
				}
				titleId = generatedKeys.getLong(1);
				logEntry.incAdded();
			} else {
				titleId = existingTitle.getId();
				updateTitleStmt.setString(1, title);
				updateTitleStmt.setString(2, mediaType);
				updateTitleStmt.setString(3, thumbnailUrl);
				updateTitleStmt.setString(4, itemSetIds);
				updateTitleStmt.setLong(5, rawChecksum);
				updateTitleStmt.setLong(6, rawResponseLength);
				updateTitleStmt.setString(7, storedResponse);
				updateTitleStmt.setLong(8, startTimeForLogging);
				updateTitleStmt.setLong(9, titleId);
				updateTitleStmt.executeUpdate();
				logEntry.incUpdated();
			}

			regroupAndIndexTitle(item, titleId);
		} catch (DataTruncation e) {
			logEntry.addNote("Omeka item contained invalid data " + e);
		} catch (Exception e) {
			logEntry.incErrors("Error processing Omeka item", e);
		}
	}

	private String getTitleForItem(JSONObject item) {
		String title = item.optString("o:title", null);
		boolean titleIsMissing = title == null || title.isEmpty() || title.equals("null");
		if (titleIsMissing) {
			title = "Untitled";
		}
		if (title.length() > 750) {
			title = title.substring(0, 750);
		}
		return title;
	}

	private String getItemSetIdsForItem(JSONObject item) {
		JSONArray itemSets = item.optJSONArray("o:item_set");
		if (itemSets == null || itemSets.isEmpty()) {
			return "";
		}
		StringBuilder itemSetIds = new StringBuilder();
		for (int i = 0; i < itemSets.length(); i++) {
			JSONObject itemSet = itemSets.optJSONObject(i);
			if (itemSet == null || !itemSet.has("o:id")) {
				continue;
			}
			if (itemSetIds.length() > 0) {
				itemSetIds.append(",");
			}
			itemSetIds.append(itemSet.getLong("o:id"));
		}
		return itemSetIds.toString();
	}

	private JSONObject fetchPrimaryMedia(JSONObject item) {
		long mediaId = getPrimaryMediaId(item);
		if (mediaId == -1) {
			return null;
		}
		String url = buildApiUrl("/api/media/" + mediaId, null);
		WebServiceResponse response = callOmekaWithRetries(url);
		if (response == null) {
			logEntry.incErrors("Could not load media " + mediaId + " from " + url);
			return null;
		}
		try {
			return new JSONObject(response.getMessage());
		} catch (JSONException e) {
			logEntry.incErrors("Could not parse media response from " + url + " as JSON", e);
			return null;
		}
	}

	private long getPrimaryMediaId(JSONObject item) {
		JSONObject primaryMediaReference = item.optJSONObject("o:primary_media");
		if (primaryMediaReference != null && primaryMediaReference.has("o:id")) {
			return primaryMediaReference.getLong("o:id");
		}
		JSONArray mediaReferences = item.optJSONArray("o:media");
		if (mediaReferences == null || mediaReferences.isEmpty()) {
			return -1;
		}
		JSONObject firstMediaReference = mediaReferences.optJSONObject(0);
		if (firstMediaReference == null || !firstMediaReference.has("o:id")) {
			return -1;
		}
		return firstMediaReference.getLong("o:id");
	}

	private String buildApiUrl(String path, String queryString) {
		StringBuilder url = new StringBuilder(setting.getBaseUrl()).append(path);
		String separator = "?";
		if (queryString != null) {
			url.append(separator).append(queryString);
			separator = "&";
		}
		if (setting.hasApiKey()) {
			url.append(separator).append("key_identity=").append(URLEncoder.encode(setting.getApiKeyIdentity(), StandardCharsets.UTF_8));
			url.append("&key_credential=").append(URLEncoder.encode(setting.getApiKeyCredential(), StandardCharsets.UTF_8));
		}
		return url.toString();
	}

	private WebServiceResponse callOmekaWithRetries(String url) {
		HashMap<String, String> headers = new HashMap<>();
		headers.put("Accept", "application/json");
		headers.put("User-Agent", "Aspen Discovery");
		int numTries = 0;
		while (numTries < 3) {
			if (numTries > 0) {
				try {
					Thread.sleep(60000);
				} catch (InterruptedException ignored) {
				}
			}
			WebServiceResponse response = NetworkUtils.getURL(url, logger, headers);
			if (response.isSuccess()) {
				return response;
			}
			boolean isClientError = response.getResponseCode() >= 400 && response.getResponseCode() < 500;
			if (isClientError) {
				return null;
			}
			numTries++;
		}
		return null;
	}

	private void removeTitlesNotSeenInExport(HashMap<Long, OmekaTitle> existingTitles) {
		for (OmekaTitle existingTitle : existingTitles.values()) {
			if (existingTitle.isFoundInExport() || existingTitle.isDeleted()) {
				continue;
			}
			try {
				removeTitle(existingTitle.getId());
				logEntry.incDeleted();
			} catch (Exception e) {
				logEntry.incErrors("Error removing Omeka title " + existingTitle.getId(), e);
			}
		}
	}

	private void removeTitle(long titleId) throws SQLException {
		markTitleDeletedStmt.setLong(1, titleId);
		markTitleDeletedStmt.executeUpdate();
		removeRecordFromWork(Long.toString(titleId));
	}

	private void removeRecordFromWork(String recordIdentifier) {
		RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("omeka", recordIdentifier);
		if (result.reindexWork) {
			getGroupedWorkIndexer().processGroupedWork(result.permanentId);
			return;
		}
		if (result.deleteWork) {
			getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
		}
	}

	private JSONObject loadStoredResponse(long titleId) {
		try {
			PreparedStatement getStoredResponseStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from omeka_title where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getStoredResponseStmt.setLong(1, titleId);
			ResultSet storedResponseRS = getStoredResponseStmt.executeQuery();
			JSONObject storedResponse = null;
			if (storedResponseRS.next()) {
				byte[] rawResponseBytes = storedResponseRS.getBytes("rawResponse");
				if (rawResponseBytes != null) {
					storedResponse = new JSONObject(new String(rawResponseBytes, StandardCharsets.UTF_8));
				}
			}
			storedResponseRS.close();
			getStoredResponseStmt.close();
			return storedResponse;
		} catch (Exception e) {
			logEntry.incErrors("Error loading stored response for Omeka title " + titleId, e);
			return null;
		}
	}

	private void regroupAndIndexTitle(JSONObject itemDetails, long titleId) {
		if (itemDetails == null) {
			return;
		}
		String groupedWorkId = getRecordGroupingProcessor().groupOmekaRecord(itemDetails, titleId);
		if (groupedWorkId != null) {
			getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
		}
	}

	private void processRecordsToReload() {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='omeka'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String rawOmekaId = getRecordsToReloadRS.getString("identifier");
				if (AspenStringUtils.isNumeric(rawOmekaId)) {
					long titleId = Long.parseLong(rawOmekaId);
					JSONObject storedResponse = loadStoredResponse(titleId);
					if (storedResponse != null) {
						regroupAndIndexTitle(storedResponse, titleId);
					} else {
						logEntry.addNote("Could not get details for Omeka record to reload " + titleId + " it has been deleted");
						logEntry.incDeleted();
					}
				} else {
					removeRecordFromWork(rawOmekaId);
					logEntry.incDeleted();
				}
				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
				if (numRecordsToReloadProcessed % 250 == 0) {
					logEntry.saveResults();
				}
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		} catch (Exception e) {
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private void setLastUpdateTimeForSetting() throws SQLException {
		PreparedStatement updateSettingsStmt;
		if (setting.doFullReload()) {
			if (logEntry.hasErrors()) {
				PreparedStatement reactivateFullUpdateStmt = aspenConn.prepareStatement("UPDATE omeka_settings set runFullUpdate = 1 where id = ?");
				reactivateFullUpdateStmt.setLong(1, setting.getId());
				reactivateFullUpdateStmt.executeUpdate();
				return;
			}
			updateSettingsStmt = aspenConn.prepareStatement("UPDATE omeka_settings SET lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
			logEntry.addNote("Disabling Run Full Update option after a successful full update.");
		} else {
			updateSettingsStmt = aspenConn.prepareStatement("UPDATE omeka_settings set lastUpdateOfChangedRecords = ? where id = ?");
		}
		updateSettingsStmt.setLong(1, startTimeForLogging);
		updateSettingsStmt.setLong(2, setting.getId());
		updateSettingsStmt.executeUpdate();
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

	private RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}
}
