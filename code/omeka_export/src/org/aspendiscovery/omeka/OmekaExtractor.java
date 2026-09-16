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
}
