package com.turning_leaf_technologies.borrowbox;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import javax.net.ssl.HttpsURLConnection;

import org.aspen_discovery.grouping.BorrowBoxRecordGrouper;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.apache.commons.codec.binary.Base64;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

class ExtractBorrowBoxInfo {
	private static final Logger logger = LogManager.getLogger(ExtractBorrowBoxInfo.class);
	private BorrowBoxRecordGrouper recordGroupingProcessorSingleton;
	private String serverName;
	private Connection dbConn;
	private BorrowBoxExtractLogEntry logEntry;

	private final BorrowBoxSetting settings;
	private String borrowBoxAPIToken;
	private long borrowBoxAPIExpiration;

	private final HashMap<String, BorrowBoxRecordInfo> allProductsInBorrowBox = new HashMap<>();

	private PreparedStatement addProductStmt;
	private PreparedStatement getProductIdByBorrowBoxIdStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement updateProductChangeTimeStmt;
	private PreparedStatement deleteProductStmt;
	private PreparedStatement getExistingMetadataIdStmt;
	private PreparedStatement addMetadataStmt;
	private PreparedStatement updateMetaDataStmt;
	private PreparedStatement getExistingAvailabilityForProductStmt;
	private PreparedStatement getExistingAvailabilitiesForProductStmt;
	private PreparedStatement countAvailabilityForProductStmt;
	private PreparedStatement addAvailabilityStmt;
	private PreparedStatement updateAvailabilityStmt;
	private PreparedStatement deleteAvailabilityStmt;
	private PreparedStatement deleteAvailabilityForProductStmt;
	private PreparedStatement updateProductMetadataStmt;
	private PreparedStatement updateProductLastMetadataCheckStmt;
	private PreparedStatement getDeletedProductsStmt;
	private PreparedStatement getNumDeletedProductsStmt;
	private PreparedStatement getTotalProductsStmt;
	private PreparedStatement updateLastSeenStmt;
	private PreparedStatement logExternalRequestStmt;

	private final CRC32 checksumCalculator = new CRC32();
	private boolean errorsWhileLoadingProducts;
	private boolean doFullUpdate;
	private GroupedWorkIndexer groupedWorkIndexer;
	private Ini configIni;

	public ExtractBorrowBoxInfo(BorrowBoxSetting settings) {
		this.settings = settings;
	}


	private boolean hasIncompleteApiConfiguration() {
		boolean missingUrl = settings.getApiUrl() == null || settings.getApiUrl().isEmpty();
		boolean missingUsername = settings.getApiUsername() == null || settings.getApiUsername().isEmpty();
		boolean missingPassword = settings.getApiPassword() == null || settings.getApiPassword().isEmpty();
		return missingUrl || missingUsername || missingPassword;
	}

	private void initBorrowBoxExtract(Connection dbConn) throws SQLException {
		addProductStmt = dbConn.prepareStatement("INSERT INTO borrowbox_api_products set id = NULL, borrowboxId = ?, isbn13 = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, seriesNumber = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastSeen = ?, lastMetadataCheck = 0, lastMetadataChange = 0 ON DUPLICATE KEY UPDATE id=id", PreparedStatement.RETURN_GENERATED_KEYS);
		getProductIdByBorrowBoxIdStmt = dbConn.prepareStatement("SELECT id from borrowbox_api_products where borrowboxId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateProductStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products SET isbn13 = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, seriesNumber = ?, primaryCreatorName = ?, cover = ?, deleted = 0 where id = ?");
		updateProductChangeTimeStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products set dateUpdated = ? WHERE borrowboxId = ?");
		deleteProductStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products SET deleted = 1, dateDeleted = ? where id = ?");
		updateLastSeenStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products set lastSeen = ? where borrowboxId = ?");
		getNumDeletedProductsStmt = dbConn.prepareStatement("SELECT count(*) from borrowbox_api_products inner join borrowbox_api_product_availability on productId = borrowbox_api_products.id where deleted = 0 and settingId = ? and lastSeen < ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getTotalProductsStmt = dbConn.prepareStatement("SELECT count(*) from borrowbox_api_products inner join borrowbox_api_product_availability on productId = borrowbox_api_products.id where deleted = 0 and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getDeletedProductsStmt = dbConn.prepareStatement("SELECT borrowbox_api_products.id, borrowbox_api_products.borrowboxId from borrowbox_api_products inner join borrowbox_api_product_availability on productId = borrowbox_api_products.id where deleted = 0 and settingId = ? and lastSeen < ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

		getExistingMetadataIdStmt = dbConn.prepareStatement("SELECT id, checksum from borrowbox_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		addMetadataStmt = dbConn.prepareStatement("INSERT INTO borrowbox_api_product_metadata (productId, checksum, publisher, releaseDate, summary, cover, rawData) VALUES (?,?,?,?,?,?,COMPRESS(?))");
		updateMetaDataStmt = dbConn.prepareStatement("UPDATE borrowbox_api_product_metadata SET checksum = ?, publisher = ?, releaseDate = ?, summary = ?, cover = ?, rawData = COMPRESS(?) WHERE id = ?");
		updateProductMetadataStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
		updateProductLastMetadataCheckStmt = dbConn.prepareStatement("UPDATE borrowbox_api_products SET lastMetadataCheck = ? where id = ?");

		getExistingAvailabilityForProductStmt = dbConn.prepareStatement("SELECT * from borrowbox_api_product_availability where productId = ? and settingId = ? and siteId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getExistingAvailabilitiesForProductStmt = dbConn.prepareStatement("SELECT id, siteId from borrowbox_api_product_availability where productId = ? and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		countAvailabilityForProductStmt = dbConn.prepareStatement("SELECT COUNT(*) from borrowbox_api_product_availability where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		addAvailabilityStmt = dbConn.prepareStatement("INSERT INTO borrowbox_api_product_availability (productId, settingId, borrowboxId, siteId, availabilityStatus, nextAvailableDate) VALUES (?,?,?,?,?,?)");
		updateAvailabilityStmt = dbConn.prepareStatement("UPDATE borrowbox_api_product_availability SET availabilityStatus = ?, nextAvailableDate = ? WHERE id = ?");
		deleteAvailabilityStmt = dbConn.prepareStatement("DELETE FROM borrowbox_api_product_availability where id = ?");
		deleteAvailabilityForProductStmt = dbConn.prepareStatement("DELETE FROM borrowbox_api_product_availability where productId = ? and settingId = ?");
		logExternalRequestStmt = dbConn.prepareStatement("INSERT INTO external_request_log (requestType, requestMethod, requestUrl, requestHeaders, requestBody, responseCode, response, requestTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
	}

	/**
	 * Map BorrowBox format strings to Aspen format names.
	 */
	static String mapBorrowBoxFormat(String borrowBoxFormat) {
		if (borrowBoxFormat == null) {
			return "eBook";
		}
		switch (borrowBoxFormat.toUpperCase()) {
			case "EBOOK":
				return "eBook";
			case "EAUDIOBOOK":
				return "eAudiobook";
			case "EPRESS":
				return "eMagazine";
			default:
				return borrowBoxFormat;
		}
	}

	private WebServiceResponse callBorrowBoxURL(String requestType, String borrowboxUrl) throws SocketTimeoutException {
		return callBorrowBoxURL(requestType, borrowboxUrl, true);
	}

	private WebServiceResponse callBorrowBoxURL(String requestType, String borrowboxUrl, boolean logFailures) throws SocketTimeoutException {
		if (!connectToBorrowBoxAPI()) {
			logger.error("Unable to connect to BorrowBox API");
			return new WebServiceResponse(false, -1, "Failed to connect to BorrowBox API");
		}

		HashMap<String, String> headers = new HashMap<>();
		headers.put("Authorization", "Bearer " + borrowBoxAPIToken);
		headers.put("User-Agent", "Aspen Discovery");
		headers.put("Accept", "application/json");

		int numTries = 0;
		WebServiceResponse response = null;
		int maxTries = Math.max(1, settings.getNumRetriesOnError() + 1);
		while (numTries < maxTries) {
			numTries++;
			response = NetworkUtils.getURL(borrowboxUrl, logger, headers, 30000, logFailures);
			logExternalRequest(requestType, borrowboxUrl, headers, response.getResponseCode(), response.getMessage());
			boolean timedOut = response.isCallTimedOut();
			boolean outOfRetries = timedOut && numTries == maxTries;
			boolean gotUsableResponse = !timedOut && response.getResponseCode() != 500;
			if (outOfRetries) {
				errorsWhileLoadingProducts = true;
			} else if (gotUsableResponse) {
				break;
			}
		}
		boolean lastCallFailed = response != null && (response.isCallTimedOut() || response.getResponseCode() == 500);
		if (lastCallFailed) {
			errorsWhileLoadingProducts = true;
		}
		return response;
	}

	/**
	 * Authenticate with the BorrowBox API via POST /v1/token.
	 * Uses Basic auth with client_credentials grant type.
	 */
	private boolean connectToBorrowBoxAPI() throws SocketTimeoutException {
		if (borrowBoxAPIToken != null) {
			if (borrowBoxAPIExpiration - new Date().getTime() > 0) {
				return true;
			}
			logger.debug("BorrowBox token has expired");
		}

		HttpURLConnection conn;
		try {
			logger.debug("Authenticating with BorrowBox API at " + settings.getApiUrl() + "/v1/token");
			URL tokenUrl = new URL(settings.getApiUrl() + "/v1/token");
			conn = (HttpURLConnection) tokenUrl.openConnection();
			if (conn instanceof HttpsURLConnection) {
				HttpsURLConnection sslConn = (HttpsURLConnection) conn;
				sslConn.setHostnameVerifier((hostname, session) -> true);
			}
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			String encoded = Base64.encodeBase64String((settings.getApiUsername() + ":" + settings.getApiPassword()).getBytes());
			conn.setRequestProperty("Authorization", "Basic " + encoded);
			conn.setReadTimeout(30000);
			conn.setConnectTimeout(30000);
			conn.setDoOutput(true);

			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), StandardCharsets.UTF_8);
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				borrowBoxAPIToken = parser.getString("access_token");
				borrowBoxAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to BorrowBox authentication service");
				if (conn.getErrorStream() != null) {
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					rd.close();
					logger.error("  Error response body: " + response);
				} else {
					logger.error("  No error response body available");
				}
				return false;
			}
		} catch (SocketTimeoutException toe) {
			throw toe;
		} catch (Exception e) {
			logger.error("Error connecting to BorrowBox API", e);
			return false;
		}
		return true;
	}

	private BorrowBoxRecordGrouper getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new BorrowBoxRecordGrouper(dbConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	void close() {
		logger.info("Closing the BorrowBox extractor");
		if (recordGroupingProcessorSingleton != null) {
			recordGroupingProcessorSingleton.close();
			recordGroupingProcessorSingleton = null;
		}
		if (groupedWorkIndexer != null) {
			groupedWorkIndexer.close();
			groupedWorkIndexer = null;
		}

		allProductsInBorrowBox.clear();

		try {
			addProductStmt.close();
			getProductIdByBorrowBoxIdStmt.close();
			updateProductStmt.close();
			updateProductChangeTimeStmt.close();
			deleteProductStmt.close();
			getExistingMetadataIdStmt.close();
			addMetadataStmt.close();
			updateMetaDataStmt.close();
			getExistingAvailabilityForProductStmt.close();
			getExistingAvailabilitiesForProductStmt.close();
			countAvailabilityForProductStmt.close();
			addAvailabilityStmt.close();
			updateAvailabilityStmt.close();
			deleteAvailabilityStmt.close();
			deleteAvailabilityForProductStmt.close();
			updateProductMetadataStmt.close();
			updateProductLastMetadataCheckStmt.close();
			updateLastSeenStmt.close();
			logExternalRequestStmt.close();
		} catch (SQLException e) {
			logger.error("Error closing BorrowBox extractor", e);
		}
	}

	private void logExternalRequest(String requestType, String requestUrl, HashMap<String, String> requestHeaders, int responseCode, String response) {
		if (!settings.isEnableRequestLogging()) {
			return;
		}
		StringBuilder headers = new StringBuilder();
		for (String requestHeader : requestHeaders.keySet()) {
			headers.append(requestHeader).append(": ").append(requestHeaders.get(requestHeader)).append("\n");
		}
		try {
			logExternalRequestStmt.setString(1, requestType);
			logExternalRequestStmt.setString(2, "GET");
			logExternalRequestStmt.setString(3, requestUrl);
			logExternalRequestStmt.setString(4, headers.toString());
			logExternalRequestStmt.setString(5, "");
			logExternalRequestStmt.setInt(6, responseCode);
			logExternalRequestStmt.setString(7, response);
			logExternalRequestStmt.setLong(8, new Date().getTime() / 1000);
			logExternalRequestStmt.executeUpdate();
		} catch (Exception e) {
			logEntry.incErrors("Unable to log external request", e);
		}
	}
}
