package org.aspendiscovery.omeka;

import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.aspen_discovery.reindexer.OmekaProcessor;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.TimeZone;
import java.util.function.Consumer;
import java.util.zip.CRC32;

public class OmekaExtractor {
	private static final int ITEMS_PER_PAGE = 100;
	private static final String PRIMARY_MEDIA_KEY = "aspen:primaryMedia";
	private static final long FULL_UPDATE_INTERVAL_SECONDS = 24 * 60 * 60;
	private static final long MODIFIED_AFTER_BUFFER_SECONDS = 10 * 60;
	private static final String[] MEDIA_KEYS_TO_KEEP = {"o:id", "o:media_type", "o:thumbnail_urls", "id", "order", "mime_type", "file_urls"};

	private final String serverName;
	private final OmekaSetting setting;
	private final OmekaExportLogEntry logEntry;
	private final Logger logger;
	private final Connection aspenConn;
	private final Ini configIni;

	private final Long startTimeForLogging;
	private final CRC32 checksumCalculator = new CRC32();
	private final HashSet<Long> processedOmekaIds = new HashSet<>();
	private final HashMap<Long, JSONObject> mediaCache = new HashMap<>();

	private PreparedStatement addTitleStmt;
	private PreparedStatement updateTitleStmt;
	private PreparedStatement updateLastSeenStmt;
	private PreparedStatement markTitleDeletedStmt;
	private PreparedStatement getStoredResponseStmt;

	private GroupedWorkIndexer groupedWorkIndexer;
	private RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	private boolean doFullReload;

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
			getStoredResponseStmt = aspenConn.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from omeka_title where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			boolean fullUpdateIsDue = setting.getLastUpdateOfAllRecords() < (startTimeForLogging - FULL_UPDATE_INTERVAL_SECONDS);
			doFullReload = setting.doFullReload() || fullUpdateIsDue;

			if (doFullReload) {
				logEntry.addNote("Starting full update from Omeka for setting " + setting.getName());
			} else {
				logEntry.addNote("Starting update of changed records from Omeka for setting " + setting.getName());
			}
			logEntry.saveResults();

			HashMap<Long, OmekaTitle> existingTitles = loadExistingTitles();
			HashSet<String> scopedSetIds = getScopedSetIds();
			boolean allItemsAreScoped = scopedSetIds == null;
			if (doFullReload && allItemsAreScoped) {
				loadMediaCache();
			}

			boolean hadErrorsExtracting = extractItems(scopedSetIds, existingTitles);

			if (!hadErrorsExtracting && doFullReload) {
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
			getStoredResponseStmt.close();
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

	private String getModifiedAfterQueryValue() {
		long extractChangesSince = setting.getLastUpdateOfChangedRecords() - MODIFIED_AFTER_BUFFER_SECONDS;
		SimpleDateFormat modifiedAfterFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
		modifiedAfterFormatter.setTimeZone(TimeZone.getTimeZone("UTC"));
		return modifiedAfterFormatter.format(new Date(extractChangesSince * 1000));
	}

	private boolean extractItems(HashSet<String> scopedSetIds, HashMap<Long, OmekaTitle> existingTitles) {
		if (scopedSetIds == null) {
			return extractItemsForSet(null, existingTitles);
		}
		if (scopedSetIds.isEmpty()) {
			logEntry.addNote("No item sets or collections are scoped, skipping extraction for setting " + setting.getName());
			return false;
		}
		boolean hadErrors = false;
		for (String setId : scopedSetIds) {
			hadErrors |= extractItemsForSet(setId, existingTitles);
		}
		return hadErrors;
	}

	private HashSet<String> getScopedSetIds() throws SQLException {
		HashSet<String> scopedSetIds = new HashSet<>();
		PreparedStatement getScopesStmt = aspenConn.prepareStatement("SELECT includeAllItemSets, itemSetIds FROM omeka_scopes WHERE settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getScopesStmt.setLong(1, setting.getId());
		ResultSet scopesRS = getScopesStmt.executeQuery();
		boolean anyScopeIncludesAllItems = false;
		while (scopesRS.next()) {
			if (scopesRS.getBoolean("includeAllItemSets")) {
				anyScopeIncludesAllItems = true;
				break;
			}
			String itemSetIds = scopesRS.getString("itemSetIds");
			if (itemSetIds == null || itemSetIds.isBlank()) {
				continue;
			}
			for (String itemSetId : itemSetIds.split(",")) {
				String trimmedId = itemSetId.trim();
				if (!trimmedId.isEmpty()) {
					scopedSetIds.add(trimmedId);
				}
			}
		}
		scopesRS.close();
		getScopesStmt.close();
		if (anyScopeIncludesAllItems) {
			return null;
		}
		return scopedSetIds;
	}

	private boolean extractItemsForSet(String setId, HashMap<Long, OmekaTitle> existingTitles) {
		String baseQueryString = "per_page=" + ITEMS_PER_PAGE + "&" + getSortQueryString();
		if (setId != null) {
			String setFilterName = setting.isClassic() ? "collection" : "item_set_id";
			baseQueryString += "&" + setFilterName + "=" + URLEncoder.encode(setId, StandardCharsets.UTF_8);
		}
		if (!doFullReload) {
			String modifiedParameterName = setting.isClassic() ? "modified_since" : "modified_after";
			baseQueryString += "&" + modifiedParameterName + "=" + URLEncoder.encode(getModifiedAfterQueryValue(), StandardCharsets.UTF_8);
		}
		return readAllPages("/api/items", baseQueryString, items -> processItems(items, existingTitles));
	}

	private boolean readAllPages(String path, String baseQueryString, Consumer<JSONArray> pageHandler) {
		int page = 1;
		while (true) {
			String url = buildApiUrl(path, "page=" + page + "&" + baseQueryString);
			WebServiceResponse response = callOmekaWithRetries(url);
			if (response == null) {
				logEntry.incErrors("Did not get a successful API response from " + getRedactedUrl(url));
				return true;
			}
			JSONArray results;
			try {
				results = new JSONArray(response.getMessage());
			} catch (JSONException e) {
				logEntry.incErrors("Could not parse response from " + getRedactedUrl(url) + " as JSON", e);
				return true;
			}
			if (results.isEmpty()) {
				return false;
			}
			pageHandler.accept(results);
			logEntry.saveResults();
			boolean serverMayCapPageSize = setting.isClassic();
			boolean lastPageReached = !serverMayCapPageSize && results.length() < ITEMS_PER_PAGE;
			if (lastPageReached) {
				return false;
			}
			page++;
		}
	}

	private void processItems(JSONArray items, HashMap<Long, OmekaTitle> existingTitles) {
		for (int i = 0; i < items.length(); i++) {
			JSONObject item = items.optJSONObject(i);
			if (item == null) {
				continue;
			}
			processItem(item, existingTitles);
		}
	}

	private void processItem(JSONObject item, HashMap<Long, OmekaTitle> existingTitles) {
		try {
			long omekaId = getItemId(item);
			boolean alreadyProcessedThisRun = !processedOmekaIds.add(omekaId);
			if (alreadyProcessedThisRun) {
				return;
			}
			logEntry.incNumProducts(1);
			OmekaTitle existingTitle = existingTitles.get(omekaId);
			boolean isNewTitle = existingTitle == null;
			if (!isNewTitle) {
				existingTitle.setFoundInExport(true);
			}

			String publicFlagKey = OmekaProcessor.isClassicItem(item) ? "public" : "o:is_public";
			boolean isPublic = item.optBoolean(publicFlagKey, true);
			if (!isPublic) {
				boolean activeTitleBecamePrivate = !isNewTitle && !existingTitle.isDeleted();
				if (activeTitleBecamePrivate) {
					removeTitle(existingTitle.getId());
					logEntry.incDeleted();
				}
				logEntry.incSkipped();
				return;
			}

			String rawItem = item.toString();
			checksumCalculator.reset();
			checksumCalculator.update(rawItem.getBytes(StandardCharsets.UTF_8));
			long rawChecksum = checksumCalculator.getValue();
			long rawResponseLength = rawItem.length();

			boolean titleWasDeleted = !isNewTitle && existingTitle.isDeleted();
			boolean titleChanged = !isNewTitle && (existingTitle.getChecksum() != rawChecksum || existingTitle.getRawResponseLength() != rawResponseLength);

			boolean saveNeeded = isNewTitle || titleChanged || titleWasDeleted;
			if (!saveNeeded) {
				updateLastSeenStmt.setLong(1, startTimeForLogging);
				updateLastSeenStmt.setLong(2, existingTitle.getId());
				updateLastSeenStmt.executeUpdate();
				if (doFullReload) {
					regroupAndIndexTitle(loadStoredResponse(existingTitle.getId()), existingTitle.getId());
				} else {
					logEntry.incSkipped();
				}
				return;
			}

			boolean mediaAttached = attachPrimaryMedia(item);
			if (!mediaAttached) {
				return;
			}
			JSONObject primaryMedia = item.optJSONObject(PRIMARY_MEDIA_KEY);
			String mediaType = OmekaProcessor.getPrimaryMediaType(primaryMedia);
			String thumbnailUrl = getThumbnailUrl(primaryMedia);
			String rawResponse = item.toString();

			String title = getTitleForItem(item);
			String itemSetIds = getItemSetIdsForItem(item);

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
				addTitleStmt.setString(9, rawResponse);
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
				updateTitleStmt.setString(7, rawResponse);
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

	private long getItemId(JSONObject item) {
		if (OmekaProcessor.isClassicItem(item)) {
			return item.getLong("id");
		}
		return item.getLong("o:id");
	}

	private String getSortQueryString() {
		if (setting.isClassic()) {
			return "sort_field=added&sort_dir=a";
		}
		return "sort_by=id&sort_order=asc";
	}

	private String getThumbnailUrl(JSONObject primaryMedia) {
		if (primaryMedia == null) {
			return null;
		}
		JSONObject thumbnailUrls = primaryMedia.optJSONObject("o:thumbnail_urls");
		if (thumbnailUrls != null) {
			return AspenStringUtils.trimTo(750, thumbnailUrls.optString("large", null));
		}
		JSONObject fileUrls = primaryMedia.optJSONObject("file_urls");
		if (fileUrls == null) {
			return null;
		}
		return AspenStringUtils.trimTo(750, fileUrls.optString("fullsize", null));
	}

	private String getTitleForItem(JSONObject item) {
		String title = OmekaProcessor.getFirstLiteralValue(item, "dcterms:title");
		if (title == null) {
			title = item.optString("o:title", null);
		}
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
		if (OmekaProcessor.isClassicItem(item)) {
			JSONObject collection = item.optJSONObject("collection");
			if (collection == null || !collection.has("id")) {
				return "";
			}
			return Long.toString(collection.getLong("id"));
		}
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
			String itemSetId = Long.toString(itemSet.getLong("o:id"));
			int separatorLength = itemSetIds.length() > 0 ? 1 : 0;
			boolean wouldExceedColumn = itemSetIds.length() + separatorLength + itemSetId.length() > 500;
			if (wouldExceedColumn) {
				break;
			}
			if (itemSetIds.length() > 0) {
				itemSetIds.append(",");
			}
			itemSetIds.append(itemSetId);
		}
		return itemSetIds.toString();
	}

	private boolean attachPrimaryMedia(JSONObject item) {
		if (!hasPrimaryMedia(item)) {
			return true;
		}
		JSONObject primaryMedia = fetchPrimaryMedia(item);
		boolean mediaFetchFailed = primaryMedia == null;
		if (mediaFetchFailed) {
			return false;
		}
		item.put(PRIMARY_MEDIA_KEY, primaryMedia);
		return true;
	}

	private boolean hasPrimaryMedia(JSONObject item) {
		if (OmekaProcessor.isClassicItem(item)) {
			JSONObject filesInfo = item.optJSONObject("files");
			return filesInfo != null && filesInfo.optInt("count", 0) > 0;
		}
		return getPrimaryMediaId(item) != -1;
	}

	private JSONObject fetchPrimaryMedia(JSONObject item) {
		JSONObject cachedMedia = mediaCache.get(getMediaCacheKey(item));
		if (cachedMedia != null) {
			return cachedMedia;
		}
		if (OmekaProcessor.isClassicItem(item)) {
			return fetchPrimaryFile(item);
		}
		long mediaId = getPrimaryMediaId(item);
		String url = buildApiUrl("/api/media/" + mediaId, null);
		WebServiceResponse response = callOmekaWithRetries(url);
		if (response == null) {
			logEntry.incErrors("Could not load media " + mediaId + " from " + getRedactedUrl(url));
			return null;
		}
		try {
			return trimMedia(new JSONObject(response.getMessage()));
		} catch (JSONException e) {
			logEntry.incErrors("Could not parse media response from " + getRedactedUrl(url) + " as JSON", e);
			return null;
		}
	}

	private JSONObject fetchPrimaryFile(JSONObject item) {
		long itemId = getItemId(item);
		String url = buildApiUrl("/api/files", "item=" + itemId + "&per_page=1");
		WebServiceResponse response = callOmekaWithRetries(url);
		if (response == null) {
			logEntry.incErrors("Could not load files for item " + itemId + " from " + getRedactedUrl(url));
			return null;
		}
		try {
			JSONArray files = new JSONArray(response.getMessage());
			if (files.isEmpty()) {
				return new JSONObject();
			}
			return trimMedia(files.getJSONObject(0));
		} catch (JSONException e) {
			logEntry.incErrors("Could not parse files response from " + getRedactedUrl(url) + " as JSON", e);
			return null;
		}
	}

	private long getMediaCacheKey(JSONObject item) {
		if (OmekaProcessor.isClassicItem(item)) {
			return getItemId(item);
		}
		return getPrimaryMediaId(item);
	}

	private void loadMediaCache() {
		String path = setting.isClassic() ? "/api/files" : "/api/media";
		String baseQueryString = "per_page=" + ITEMS_PER_PAGE;
		if (!setting.isClassic()) {
			baseQueryString += "&sort_by=id&sort_order=asc";
		}
		boolean hadErrors = readAllPages(path, baseQueryString, this::cacheMediaList);
		if (hadErrors) {
			logEntry.addNote("Loading media per item because the bulk media load did not complete");
			return;
		}
		logEntry.addNote("Loaded " + mediaCache.size() + " media records in bulk");
	}

	private void cacheMediaList(JSONArray mediaList) {
		for (int i = 0; i < mediaList.length(); i++) {
			JSONObject media = mediaList.optJSONObject(i);
			if (media == null) {
				continue;
			}
			cacheMedia(media);
		}
	}

	private void cacheMedia(JSONObject media) {
		if (setting.isClassic()) {
			cacheClassicFile(media);
			return;
		}
		if (!media.has("o:id")) {
			return;
		}
		mediaCache.put(media.getLong("o:id"), trimMedia(media));
	}

	private void cacheClassicFile(JSONObject file) {
		JSONObject itemReference = file.optJSONObject("item");
		boolean fileHasItem = itemReference != null && itemReference.has("id");
		if (!fileHasItem) {
			return;
		}
		long itemId = itemReference.getLong("id");
		JSONObject cachedFile = mediaCache.get(itemId);
		boolean fileComesFirst = cachedFile == null || compareClassicFileOrder(file, cachedFile) < 0;
		if (fileComesFirst) {
			mediaCache.put(itemId, trimMedia(file));
		}
	}

	private int compareClassicFileOrder(JSONObject file, JSONObject otherFile) {
		long order = file.optLong("order", Long.MAX_VALUE);
		long otherOrder = otherFile.optLong("order", Long.MAX_VALUE);
		if (order != otherOrder) {
			return Long.compare(order, otherOrder);
		}
		return Long.compare(file.optLong("id", Long.MAX_VALUE), otherFile.optLong("id", Long.MAX_VALUE));
	}

	private JSONObject trimMedia(JSONObject media) {
		JSONObject trimmedMedia = new JSONObject();
		for (String key : MEDIA_KEYS_TO_KEEP) {
			if (media.has(key)) {
				trimmedMedia.put(key, media.get(key));
			}
		}
		return trimmedMedia;
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
			url.append(separator).append(getAuthQueryString());
		}
		return url.toString();
	}

	private String getAuthQueryString() {
		String encodedCredential = URLEncoder.encode(setting.getApiKeyCredential(), StandardCharsets.UTF_8);
		if (setting.isClassic()) {
			return "key=" + encodedCredential;
		}
		return "key_identity=" + URLEncoder.encode(setting.getApiKeyIdentity(), StandardCharsets.UTF_8) + "&key_credential=" + encodedCredential;
	}

	private String getRedactedUrl(String url) {
		return url.replaceAll("(key|key_identity|key_credential)=[^&]*", "$1=REDACTED");
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
			WebServiceResponse response = NetworkUtils.getURL(url, logger, headers, 300000, false);
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
		boolean fullReloadFailed = doFullReload && logEntry.hasErrors();
		if (fullReloadFailed) {
			PreparedStatement reactivateFullUpdateStmt = aspenConn.prepareStatement("UPDATE omeka_settings set runFullUpdate = 1 where id = ?");
			reactivateFullUpdateStmt.setLong(1, setting.getId());
			reactivateFullUpdateStmt.executeUpdate();
			return;
		}
		if (logEntry.hasErrors()) {
			logEntry.addNote("Keeping the last update time because the extract had errors.");
			return;
		}
		PreparedStatement updateSettingsStmt;
		if (doFullReload) {
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
