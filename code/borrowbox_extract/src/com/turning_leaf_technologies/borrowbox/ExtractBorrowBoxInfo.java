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

	int extractBorrowBoxInfo(Ini configIni, String serverName, Connection dbConn, BorrowBoxExtractLogEntry logEntry) {
		int numProcessed = 0;
		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logEntry = logEntry;

		long extractStartTime = new Date().getTime();

		try {
			initBorrowBoxExtract(dbConn);

			if (hasIncompleteApiConfiguration()) {
				logEntry.addNote("Did not find correct configuration in settings, not loading BorrowBox titles");
				return numProcessed;
			}

			doFullUpdate = settings.isRunFullUpdate() || settings.getLastUpdateOfChangedRecords() == 0;

			if (doFullUpdate) {
				logEntry.addNote("Performing full update of BorrowBox catalog");
			} else {
				logEntry.addNote("Performing incremental update since " + settings.getLastUpdateOfChangedRecords());
			}
			logEntry.saveResults();

			long toTimestamp = loadAvailabilities(doFullUpdate, extractStartTime);

			boolean loadIncrementalChanges = !doFullUpdate && !errorsWhileLoadingProducts;
			if (loadIncrementalChanges) {
				loadModifications();
			}

			for (String productToRetry : settings.getProductsToUpdate()) {
				getOrCreateRecordInfo(productToRetry).hasChanges = true;
			}

			int totalToProcess = 0;
			for (BorrowBoxRecordInfo recordInfo : allProductsInBorrowBox.values()) {
				boolean needsMetadata = recordInfo.hasChanges || recordInfo.isNew;
				if (needsMetadata) {
					totalToProcess++;
				}
			}
			logEntry.addNote("Found " + totalToProcess + " products to process");
			logEntry.setNumProducts(totalToProcess);
			logEntry.saveResults();

			List<BorrowBoxRecordInfo> recordsNeedingMetadata = new ArrayList<>();
			for (BorrowBoxRecordInfo recordInfo : allProductsInBorrowBox.values()) {
				boolean needsMetadata = recordInfo.hasChanges || recordInfo.isNew;
				if (needsMetadata) {
					recordsNeedingMetadata.add(recordInfo);
				}
			}

			for (int i = 0; i < recordsNeedingMetadata.size(); i += 25) {
				int end = Math.min(i + 25, recordsNeedingMetadata.size());
				List<BorrowBoxRecordInfo> batch = recordsNeedingMetadata.subList(i, end);
				fetchAndStoreMetadataBatch(batch);

				boolean atSaveCheckpoint = end % 100 == 0 || end == recordsNeedingMetadata.size();
				if (atSaveCheckpoint) {
					logEntry.saveResults();
				}
			}

			for (BorrowBoxRecordInfo recordInfo : recordsNeedingMetadata) {
				try {
					boolean stillInCollection = recordInfo.getDatabaseId() != -1 && !recordInfo.removedFromCollection;
					if (stillInCollection) {
						String groupedWorkId = getRecordGroupingProcessor().processBorrowBoxRecord(recordInfo.getBorrowboxId());
						if (groupedWorkId != null) {
							getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
						}
						if (recordInfo.isNew) {
							logEntry.incAdded();
						} else {
							logEntry.incUpdated();
						}
					}
				} catch (Exception e) {
					logEntry.incErrors("Error processing record " + recordInfo.getBorrowboxId(), e);
				}
				numProcessed++;
				if (numProcessed % 100 == 0) {
					logEntry.addNote("Processed " + numProcessed + " records");
					logEntry.saveResults();
				}
			}

			boolean fullUpdateSucceeded = doFullUpdate && !errorsWhileLoadingProducts;
			if (fullUpdateSucceeded) {
				handleDeletedRecords(extractStartTime);
			}

			processRecordsToReload(logEntry);

			PreparedStatement saveProductsToUpdateStmt = dbConn.prepareStatement("UPDATE borrowbox_settings set productsToUpdate = ? WHERE id = ?");
			saveProductsToUpdateStmt.setString(1, settings.getProductsToUpdateNextTimeAsString());
			saveProductsToUpdateStmt.setLong(2, settings.getId());
			saveProductsToUpdateStmt.executeUpdate();

			boolean extractSucceeded = !errorsWhileLoadingProducts && !logEntry.hasErrors();
			if (extractSucceeded) {
				long timestampToSave = (toTimestamp > 0) ? toTimestamp : (extractStartTime / 1000);
				PreparedStatement updateExtractTime;
				if (doFullUpdate) {
					updateExtractTime = dbConn.prepareStatement("UPDATE borrowbox_settings set runFullUpdate = 0, lastUpdateOfAllRecords = ?, lastUpdateOfChangedRecords = ? WHERE id = ?");
					updateExtractTime.setLong(1, timestampToSave);
					updateExtractTime.setLong(2, timestampToSave);
					updateExtractTime.setLong(3, settings.getId());
				} else {
					updateExtractTime = dbConn.prepareStatement("UPDATE borrowbox_settings set runFullUpdate = 0, lastUpdateOfChangedRecords = ? WHERE id = ?");
					updateExtractTime.setLong(1, timestampToSave);
					updateExtractTime.setLong(2, settings.getId());
				}
				updateExtractTime.executeUpdate();
				logger.debug("Setting last extract time to " + timestampToSave);
			} else {
				logEntry.addNote("Not setting last extract time since there were problems extracting products from the API");
			}

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}
			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error initializing BorrowBox extraction", e);
		}
		return numProcessed;
	}

	int processSingleWork(String singleWorkId, Ini configIni, String serverName, Connection dbConn, BorrowBoxExtractLogEntry logEntry) {
		int numChanges = 0;
		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logEntry = logEntry;

		try {
			initBorrowBoxExtract(dbConn);

			if (hasIncompleteApiConfiguration()) {
				logEntry.addNote("Did not find correct configuration in settings, not loading BorrowBox titles");
				return numChanges;
			}

			List<BorrowBoxRecordInfo> singleBatch = new ArrayList<>();
			BorrowBoxRecordInfo recordInfo = new BorrowBoxRecordInfo();
			recordInfo.setBorrowboxId(singleWorkId);
			recordInfo.hasChanges = true;

			getProductIdByBorrowBoxIdStmt.setString(1, singleWorkId);
			ResultSet existingRS = getProductIdByBorrowBoxIdStmt.executeQuery();
			if (existingRS.next()) {
				recordInfo.setDatabaseId(existingRS.getLong("id"));
			} else {
				recordInfo.isNew = true;
			}
			existingRS.close();

			singleBatch.add(recordInfo);
			fetchAndStoreMetadataBatch(singleBatch);
			updateAvailabilityForSingleProduct(recordInfo);

			if (recordInfo.getDatabaseId() != -1) {
				String groupedWorkId = getRecordGroupingProcessor().processBorrowBoxRecord(recordInfo.getBorrowboxId());
				if (groupedWorkId != null) {
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}
				numChanges++;
			}

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}
			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error initializing BorrowBox extraction", e);
		}
		return numChanges;
	}

	/**
	 * Load availabilities from the BorrowBox API.
	 * Full load: GET /v1/availabilities (no from param, excludes UNAVAILABLE)
	 * Incremental: GET /v1/availabilities?from={epoch}
	 *
	 * @param fullUpdate    true for full load, false for incremental
	 * @param extractStartTime time the extraction started
	 * @return the 'to' timestamp from the API response for use as next 'from'
	 */
	private long loadAvailabilities(boolean fullUpdate, long extractStartTime) {
		return walkCursorFeed("/v1/availabilities", !fullUpdate, "borrowboxExtract.loadAvailabilities", "availability records", true, (JSONObject item) -> {
			String productId = item.getString("productId");
			String siteId = item.getString("siteId");
			String availabilityStatus = item.optString("status", "UNKNOWN");

			BorrowBoxRecordInfo recordInfo = getOrCreateRecordInfo(productId);
			recordInfo.hasChanges = true;
			recordInfo.addPendingAvailability(siteId, availabilityStatus, item.isNull("nextAvailableDate") ? null : item.getLong("nextAvailableDate"));

			if (fullUpdate) {
				setLastSeenForProduct(extractStartTime, recordInfo);
			}
		});
	}

	/**
	 * Load product modifications from /v1/modifications endpoint.
	 * This returns product IDs whose metadata has changed.
	 */
	private void loadModifications() {
		if (settings.getLastUpdateOfChangedRecords() == 0) {
			return;
		}
		walkCursorFeed("/v1/modifications", true, "borrowboxExtract.loadModifications", "modification records", false, (JSONObject item) -> {
			getOrCreateRecordInfo(item.getString("productId")).hasChanges = true;
		});
	}

	private interface BorrowBoxFeedItemHandler {
		void handleItem(JSONObject item) throws Exception;
	}

	private long walkCursorFeed(String endpoint, boolean includeFromCursor, String operationName, String itemLabel, boolean flagProductLoadErrors, BorrowBoxFeedItemHandler itemHandler) {
		long toTimestamp = 0;
		String position = null;
		int totalLoaded = 0;

		try {
			do {
				StringBuilder urlBuilder = new StringBuilder(settings.getApiUrl()).append(endpoint);
				String separator = "?";
				if (includeFromCursor) {
					urlBuilder.append(separator).append("from=").append(settings.getLastUpdateOfChangedRecords());
					separator = "&";
				}
				urlBuilder.append(separator).append("size=1000");
				if (position != null) {
					urlBuilder.append("&position=").append(position);
				}

				WebServiceResponse response = callBorrowBoxURL(operationName, urlBuilder.toString());
				boolean requestFailed = response.getResponseCode() != 200 || response.getMessage() == null;
				if (requestFailed) {
					logEntry.incErrors("Error loading " + itemLabel + ", response code: " + response.getResponseCode());
					if (response.getMessage() != null) {
						logEntry.addNote(response.getMessage());
					}
					if (flagProductLoadErrors) {
						errorsWhileLoadingProducts = true;
					}
					break;
				}

				JSONObject responseObj = response.getJSONResponse();
				if (responseObj == null) {
					logEntry.incErrors("Null response loading " + itemLabel);
					if (flagProductLoadErrors) {
						errorsWhileLoadingProducts = true;
					}
					break;
				}

				if (responseObj.has("to")) {
					toTimestamp = responseObj.getLong("to");
				}

				JSONArray items = responseObj.optJSONArray("items");
				if (items != null) {
					for (int i = 0; i < items.length(); i++) {
						itemHandler.handleItem(items.getJSONObject(i));
						totalLoaded++;
					}
				}

				position = responseObj.isNull("nextPosition") ? null : responseObj.optString("nextPosition", null);
				boolean positionIsBlank = position != null && position.isEmpty();
				if (positionIsBlank) {
					position = null;
				}

				if (totalLoaded % 1000 == 0) {
					logEntry.addNote("Loaded " + totalLoaded + " " + itemLabel);
					logEntry.saveResults();
				}
			} while (position != null);

			logEntry.addNote("Loaded " + totalLoaded + " total " + itemLabel);
			logEntry.saveResults();

		} catch (Exception e) {
			logEntry.incErrors("Error loading " + itemLabel + " from BorrowBox API", e);
			if (flagProductLoadErrors) {
				errorsWhileLoadingProducts = true;
			}
		}

		return toTimestamp;
	}

	private BorrowBoxRecordInfo getOrCreateRecordInfo(String productId) throws SQLException {
		BorrowBoxRecordInfo recordInfo = allProductsInBorrowBox.get(productId);
		if (recordInfo != null) {
			return recordInfo;
		}

		recordInfo = new BorrowBoxRecordInfo();
		recordInfo.setBorrowboxId(productId);
		allProductsInBorrowBox.put(productId, recordInfo);

		getProductIdByBorrowBoxIdStmt.setString(1, productId);
		ResultSet existingRS = getProductIdByBorrowBoxIdStmt.executeQuery();
		if (existingRS.next()) {
			recordInfo.setDatabaseId(existingRS.getLong("id"));
		} else {
			recordInfo.isNew = true;
		}
		existingRS.close();
		return recordInfo;
	}

	/**
	 * Fetch metadata for a batch of products via GET /v2/products?productIds=...
	 * and store in the database.
	 */
	private void fetchAndStoreMetadataBatch(List<BorrowBoxRecordInfo> batch) {
		if (batch.isEmpty()) {
			return;
		}

		StringBuilder productIds = new StringBuilder();
		for (int i = 0; i < batch.size(); i++) {
			if (i > 0) {
				productIds.append(",");
			}
			productIds.append(batch.get(i).getBorrowboxId());
		}

		String url = settings.getApiUrl() + "/v2/products?productIds=" + productIds;
		int maxTries = Math.max(1, settings.getNumRetriesOnError() + 1);

		for (int tryNum = 0; tryNum < maxTries; tryNum++) {
			try {
				WebServiceResponse response = callBorrowBoxURL("borrowboxExtract.getProductMetadata", url, tryNum == maxTries - 1);
				boolean batchLoaded = response.getResponseCode() == 200 && response.getMessage() != null;
				if (batchLoaded) {
					JSONObject responseObj = response.getJSONResponse();
					boolean hasItems = responseObj != null && responseObj.has("items");
					if (hasItems) {
						JSONArray items = responseObj.getJSONArray("items");
						for (int i = 0; i < items.length(); i++) {
							JSONObject product = items.getJSONObject(i);
							saveProductMetadataToDatabase(product);
						}
					}
					break;
				} else if (response.getResponseCode() == 400) {
					logEntry.addNote("Got 400 response fetching metadata for batch. Some products may not be accessible.");
					for (BorrowBoxRecordInfo record : batch) {
						logEntry.incInvalidRecords(record.getBorrowboxId());
					}
					break;
				} else if (tryNum == maxTries - 1) {
					logEntry.incErrors("Could not load product metadata batch: response code " + response.getResponseCode());
				} else {
					try {
						Thread.sleep(5000);
					} catch (InterruptedException e) {
						logEntry.addNote("Sleeping after metadata fetch retry was interrupted");
					}
				}
			} catch (SocketTimeoutException e) {
				if (tryNum == maxTries - 1) {
					logEntry.incErrors("Timeout loading metadata for batch", e);
					for (BorrowBoxRecordInfo record : batch) {
						settings.addProductToUpdateNextTime(record.getBorrowboxId());
					}
				}
			}
		}
	}

	/**
	 * Parse a product JSON object from the /v2/products response and save to the database.
	 */
	private void saveProductMetadataToDatabase(JSONObject product) {
		try {
			String productId = product.getString("productId");
			String isbn13 = product.optString("isbn13", "");
			String format = product.getString("format");
			String title = product.getString("title");
			String subTitle = product.optString("subTitle", null);
			String seriesName = product.optString("seriesName", null);
			int seriesNumber = product.optInt("seriesNumber", 0);
			String publisher = product.optString("publisher", "");
			String coverUrl = product.optString("coverUrl", "");
			String summary = product.optString("summary", "");
			long releaseDate = product.optLong("releaseDate", 0);

			String primaryCreatorName = "";
			JSONArray authors = product.optJSONArray("authors");
			boolean hasAuthors = authors != null && authors.length() > 0;
			if (hasAuthors) {
				primaryCreatorName = authors.getJSONObject(0).optString("fullName", "");
			}

			BorrowBoxRecordInfo recordInfo = allProductsInBorrowBox.get(productId);
			if (recordInfo == null) {
				recordInfo = new BorrowBoxRecordInfo();
				recordInfo.setBorrowboxId(productId);
				recordInfo.isNew = true;
				allProductsInBorrowBox.put(productId, recordInfo);
			}
			recordInfo.setIsbn13(isbn13);
			recordInfo.setFormat(format);
			recordInfo.setTitle(title);
			recordInfo.setSubTitle(subTitle);
			recordInfo.setSeries(seriesName);
			recordInfo.setSeriesNumber(seriesNumber);
			recordInfo.setPrimaryCreatorName(primaryCreatorName);
			recordInfo.setPublisher(publisher);
			recordInfo.setCover(coverUrl);

			long curTime = new Date().getTime() / 1000;

			String mediaType = mapBorrowBoxFormat(format);

			if (recordInfo.getDatabaseId() == -1) {
				getProductIdByBorrowBoxIdStmt.setString(1, productId);
				ResultSet existingRS = getProductIdByBorrowBoxIdStmt.executeQuery();
				if (existingRS.next()) {
					recordInfo.setDatabaseId(existingRS.getLong("id"));
				}
				existingRS.close();
			}

			if (recordInfo.getDatabaseId() == -1) {
				int curCol = 0;
				addProductStmt.setString(++curCol, productId);
				addProductStmt.setString(++curCol, isbn13);
				addProductStmt.setString(++curCol, mediaType);
				addProductStmt.setString(++curCol, title);
				addProductStmt.setString(++curCol, subTitle != null ? subTitle : "");
				addProductStmt.setString(++curCol, seriesName != null ? seriesName : "");
				addProductStmt.setInt(++curCol, seriesNumber);
				addProductStmt.setString(++curCol, primaryCreatorName);
				addProductStmt.setString(++curCol, coverUrl);
				addProductStmt.setLong(++curCol, curTime);
				addProductStmt.setLong(++curCol, curTime);
				addProductStmt.setLong(++curCol, curTime);
				addProductStmt.executeUpdate();

				ResultSet newIdRS = addProductStmt.getGeneratedKeys();
				if (newIdRS.next()) {
					recordInfo.setDatabaseId(newIdRS.getLong(1));
				} else {
					getProductIdByBorrowBoxIdStmt.setString(1, productId);
					ResultSet existingIdRS = getProductIdByBorrowBoxIdStmt.executeQuery();
					if (existingIdRS.next()) {
						recordInfo.setDatabaseId(existingIdRS.getLong("id"));
					}
					existingIdRS.close();
				}
			} else {
				int curCol = 0;
				updateProductStmt.setString(++curCol, isbn13);
				updateProductStmt.setString(++curCol, mediaType);
				updateProductStmt.setString(++curCol, title);
				updateProductStmt.setString(++curCol, subTitle != null ? subTitle : "");
				updateProductStmt.setString(++curCol, seriesName != null ? seriesName : "");
				updateProductStmt.setInt(++curCol, seriesNumber);
				updateProductStmt.setString(++curCol, primaryCreatorName);
				updateProductStmt.setString(++curCol, coverUrl);
				updateProductStmt.setLong(++curCol, recordInfo.getDatabaseId());
				int numChanges = updateProductStmt.executeUpdate();
				if (numChanges > 0) {
					updateProductChangeTimeStmt.setLong(1, curTime);
					updateProductChangeTimeStmt.setString(2, productId);
					updateProductChangeTimeStmt.executeUpdate();
				}
			}

			if (recordInfo.getDatabaseId() != -1) {
				checksumCalculator.reset();
				checksumCalculator.update(product.toString().getBytes());
				long metadataChecksum = checksumCalculator.getValue();

				boolean metadataChanged = true;
				getExistingMetadataIdStmt.setLong(1, recordInfo.getDatabaseId());
				ResultSet existingMetadataRS = getExistingMetadataIdStmt.executeQuery();
				if (existingMetadataRS.next()) {
					long metadataId = existingMetadataRS.getLong("id");
					metadataChanged = existingMetadataRS.getLong("checksum") != metadataChecksum;
					if (metadataChanged) {
						int curCol = 0;
						updateMetaDataStmt.setLong(++curCol, metadataChecksum);
						updateMetaDataStmt.setString(++curCol, publisher);
						updateMetaDataStmt.setLong(++curCol, releaseDate);
						updateMetaDataStmt.setString(++curCol, summary);
						updateMetaDataStmt.setString(++curCol, coverUrl);
						updateMetaDataStmt.setString(++curCol, product.toString());
						updateMetaDataStmt.setLong(++curCol, metadataId);
						updateMetaDataStmt.executeUpdate();
					}
				} else {
					int curCol = 0;
					addMetadataStmt.setLong(++curCol, recordInfo.getDatabaseId());
					addMetadataStmt.setLong(++curCol, metadataChecksum);
					addMetadataStmt.setString(++curCol, publisher);
					addMetadataStmt.setLong(++curCol, releaseDate);
					addMetadataStmt.setString(++curCol, summary);
					addMetadataStmt.setString(++curCol, coverUrl);
					addMetadataStmt.setString(++curCol, product.toString());
					try {
						addMetadataStmt.executeUpdate();
					} catch (SQLIntegrityConstraintViolationException e) {
					}
				}
				existingMetadataRS.close();

				if (metadataChanged) {
					updateProductMetadataStmt.setLong(1, curTime);
					updateProductMetadataStmt.setLong(2, curTime);
					updateProductMetadataStmt.setLong(3, recordInfo.getDatabaseId());
					updateProductMetadataStmt.executeUpdate();
					logEntry.incMetadataChanges();
				} else {
					updateProductLastMetadataCheckStmt.setLong(1, curTime);
					updateProductLastMetadataCheckStmt.setLong(2, recordInfo.getDatabaseId());
					updateProductLastMetadataCheckStmt.executeUpdate();
					logEntry.incSkipped();
				}

				for (BorrowBoxRecordInfo.PendingAvailability pending : recordInfo.getPendingAvailabilities()) {
					storeAvailability(recordInfo, pending.siteId, pending.availabilityStatus, pending.nextAvailableDate);
				}
				boolean hasPendingAvailabilities = !recordInfo.getPendingAvailabilities().isEmpty();
				boolean replaceStaleAvailabilities = doFullUpdate && hasPendingAvailabilities;
				if (replaceStaleAvailabilities) {
					removeStaleAvailabilities(recordInfo);
				}
				boolean noRemainingAvailability = hasPendingAvailabilities && !hasRemainingAvailability(recordInfo);
				if (noRemainingAvailability) {
					deleteProduct(recordInfo.getBorrowboxId(), recordInfo.getDatabaseId());
					recordInfo.removedFromCollection = true;
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error saving product metadata to database", e);
		}
	}

	/**
	 * Store availability information for a product.
	 *
	 * Products with status UNAVAILABLE have left the site's collection per the
	 * BorrowBox API specification and their availability is removed instead.
	 */
	private void storeAvailability(BorrowBoxRecordInfo recordInfo, String siteId, String availabilityStatus, Long nextAvailableDate) {
		if (recordInfo.getDatabaseId() == -1) {
			return;
		}

		if ("UNAVAILABLE".equals(availabilityStatus)) {
			removeAvailability(recordInfo, siteId);
			return;
		}

		try {
			getExistingAvailabilityForProductStmt.setLong(1, recordInfo.getDatabaseId());
			getExistingAvailabilityForProductStmt.setLong(2, settings.getId());
			getExistingAvailabilityForProductStmt.setString(3, siteId);
			ResultSet existingRS = getExistingAvailabilityForProductStmt.executeQuery();

			if (existingRS.next()) {
				long existingId = existingRS.getLong("id");
				String existingStatus = existingRS.getString("availabilityStatus");
				if (!availabilityStatus.equals(existingStatus)) {
					updateAvailabilityStmt.setString(1, availabilityStatus);
					if (nextAvailableDate != null) {
						updateAvailabilityStmt.setLong(2, nextAvailableDate);
					} else {
						updateAvailabilityStmt.setNull(2, Types.BIGINT);
					}
					updateAvailabilityStmt.setLong(3, existingId);
					updateAvailabilityStmt.executeUpdate();
					logEntry.incAvailabilityChanges();
				}
			} else {
				int curCol = 0;
				addAvailabilityStmt.setLong(++curCol, recordInfo.getDatabaseId());
				addAvailabilityStmt.setLong(++curCol, settings.getId());
				addAvailabilityStmt.setString(++curCol, recordInfo.getBorrowboxId());
				addAvailabilityStmt.setString(++curCol, siteId);
				addAvailabilityStmt.setString(++curCol, availabilityStatus);
				if (nextAvailableDate != null) {
					addAvailabilityStmt.setLong(++curCol, nextAvailableDate);
				} else {
					addAvailabilityStmt.setNull(++curCol, Types.BIGINT);
				}
				addAvailabilityStmt.executeUpdate();
				logEntry.incAvailabilityChanges();
			}
			existingRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error storing availability for " + recordInfo.getBorrowboxId() + " site " + siteId, e);
		}
	}

	private void removeAvailability(BorrowBoxRecordInfo recordInfo, String siteId) {
		try {
			getExistingAvailabilityForProductStmt.setLong(1, recordInfo.getDatabaseId());
			getExistingAvailabilityForProductStmt.setLong(2, settings.getId());
			getExistingAvailabilityForProductStmt.setString(3, siteId);
			ResultSet existingRS = getExistingAvailabilityForProductStmt.executeQuery();
			if (existingRS.next()) {
				deleteAvailabilityStmt.setLong(1, existingRS.getLong("id"));
				deleteAvailabilityStmt.executeUpdate();
				logEntry.incAvailabilityChanges();
			}
			existingRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error removing availability for " + recordInfo.getBorrowboxId() + " site " + siteId, e);
		}
	}

	private boolean hasRemainingAvailability(BorrowBoxRecordInfo recordInfo) {
		try {
			countAvailabilityForProductStmt.setLong(1, recordInfo.getDatabaseId());
			ResultSet countRS = countAvailabilityForProductStmt.executeQuery();
			boolean hasRows = countRS.next() && countRS.getInt(1) > 0;
			countRS.close();
			return hasRows;
		} catch (SQLException e) {
			logEntry.incErrors("Error counting availability for " + recordInfo.getBorrowboxId(), e);
			return true;
		}
	}

	/**
	 * Remove availability rows for sites that no longer report this product.
	 * Only safe during a full update when the availability feed is authoritative.
	 */
	private void removeStaleAvailabilities(BorrowBoxRecordInfo recordInfo) {
		HashSet<String> seenSiteIds = new HashSet<>();
		for (BorrowBoxRecordInfo.PendingAvailability pending : recordInfo.getPendingAvailabilities()) {
			seenSiteIds.add(pending.siteId);
		}

		try {
			getExistingAvailabilitiesForProductStmt.setLong(1, recordInfo.getDatabaseId());
			getExistingAvailabilitiesForProductStmt.setLong(2, settings.getId());
			ResultSet existingRS = getExistingAvailabilitiesForProductStmt.executeQuery();
			ArrayList<Long> availabilityIdsToDelete = new ArrayList<>();
			while (existingRS.next()) {
				if (!seenSiteIds.contains(existingRS.getString("siteId"))) {
					availabilityIdsToDelete.add(existingRS.getLong("id"));
				}
			}
			existingRS.close();

			for (Long availabilityId : availabilityIdsToDelete) {
				deleteAvailabilityStmt.setLong(1, availabilityId);
				deleteAvailabilityStmt.executeUpdate();
				logEntry.incAvailabilityChanges();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error removing stale availability for " + recordInfo.getBorrowboxId(), e);
		}
	}

	/**
	 * Refresh availability for a single product by querying each site that has
	 * availability data for this setting.
	 */
	private void updateAvailabilityForSingleProduct(BorrowBoxRecordInfo recordInfo) {
		if (recordInfo.getDatabaseId() == -1) {
			return;
		}
		try {
			PreparedStatement getSiteIdsStmt = dbConn.prepareStatement("SELECT DISTINCT siteId from borrowbox_api_product_availability where settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getSiteIdsStmt.setLong(1, settings.getId());
			ResultSet siteIdsRS = getSiteIdsStmt.executeQuery();
			ArrayList<String> siteIds = new ArrayList<>();
			while (siteIdsRS.next()) {
				siteIds.add(siteIdsRS.getString("siteId"));
			}
			siteIdsRS.close();
			getSiteIdsStmt.close();

			for (String siteId : siteIds) {
				String url = settings.getApiUrl() + "/v1/sites/" + siteId + "/availabilities?productIds=" + recordInfo.getBorrowboxId();
				WebServiceResponse response = callBorrowBoxURL("borrowboxExtract.getProductAvailability", url);
				boolean invalidResponse = response.getResponseCode() != 200 || response.getMessage() == null;
				if (invalidResponse) {
					continue;
				}
				JSONObject responseObj = response.getJSONResponse();
				JSONArray items = responseObj == null ? null : responseObj.optJSONArray("items");
				if (items == null) {
					continue;
				}
				for (int i = 0; i < items.length(); i++) {
					JSONObject item = items.getJSONObject(i);
					String availabilityStatus = item.optString("status", "UNKNOWN");
					storeAvailability(recordInfo, siteId, availabilityStatus, item.isNull("nextAvailableDate") ? null : item.getLong("nextAvailableDate"));
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error updating availability for " + recordInfo.getBorrowboxId(), e);
		}
	}

	/**
	 * Handle deletion of records that were not seen during a full update.
	 */
	private void handleDeletedRecords(long extractStartTime) {
		try {
			int totalRecordsToDelete = 0;
			getNumDeletedProductsStmt.setLong(1, settings.getId());
			getNumDeletedProductsStmt.setLong(2, extractStartTime / 1000);
			ResultSet numDeletedRS = getNumDeletedProductsStmt.executeQuery();
			if (numDeletedRS.next()) {
				totalRecordsToDelete = numDeletedRS.getInt(1);
			}
			numDeletedRS.close();

			int totalProducts = 0;
			getTotalProductsStmt.setLong(1, settings.getId());
			ResultSet totalRS = getTotalProductsStmt.executeQuery();
			if (totalRS.next()) {
				totalProducts = totalRS.getInt(1);
			}
			totalRS.close();

			boolean allowLargeDeletes = settings.isAllowLargeDeletes();
			boolean tooManyToDelete = totalRecordsToDelete >= 500;
			boolean tooLargeAShareToDelete = totalProducts > 0 && ((float) totalRecordsToDelete / totalProducts) >= .05;
			boolean deletesWithinLimits = !tooManyToDelete && totalProducts > 0 && !tooLargeAShareToDelete;
			boolean deleteRecords = totalRecordsToDelete > 0 && (allowLargeDeletes || deletesWithinLimits);
			boolean blockedByCount = !allowLargeDeletes && tooManyToDelete;
			boolean blockedByShare = !allowLargeDeletes && tooLargeAShareToDelete;
			if (deleteRecords) {
				int numDeleted = 0;
				getDeletedProductsStmt.setLong(1, settings.getId());
				getDeletedProductsStmt.setLong(2, extractStartTime / 1000);
				ResultSet deletedRS = getDeletedProductsStmt.executeQuery();
				while (deletedRS.next()) {
					String borrowboxId = deletedRS.getString("borrowboxId");
					long aspenId = deletedRS.getLong("id");
					deleteProduct(borrowboxId, aspenId);
					numDeleted++;
					if (numDeleted % 100 == 0) {
						logEntry.saveResults();
					}
				}
				deletedRS.close();
				logger.info("Deleted " + numDeleted + " records that no longer exist");
			} else if (blockedByCount) {
				logEntry.incErrors("There were more than 500 records to delete (" + totalRecordsToDelete + "), not deleting records");
			} else if (blockedByShare) {
				logEntry.incErrors("More than 5% of the collection was marked for deletion (" + totalRecordsToDelete + " of " + totalProducts + "), not deleting records");
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error handling deleted records", e);
		}
	}

	private void deleteProduct(String borrowboxId, long aspenId) {
		try {
			long curTime = new Date().getTime() / 1000;
			deleteProductStmt.setLong(1, curTime);
			deleteProductStmt.setLong(2, aspenId);
			deleteProductStmt.executeUpdate();

			deleteAvailabilityForProductStmt.setLong(1, aspenId);
			deleteAvailabilityForProductStmt.setLong(2, settings.getId());
			deleteAvailabilityForProductStmt.executeUpdate();
			logEntry.incDeleted();

			RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("borrowbox", borrowboxId);
			if (result.reindexWork) {
				getGroupedWorkIndexer().processGroupedWork(result.permanentId);
			} else if (result.deleteWork) {
				getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error deleting BorrowBox product " + aspenId, e);
		}
	}

	private void processRecordsToReload(BorrowBoxExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='borrowbox'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");

			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String borrowboxId = getRecordsToReloadRS.getString("identifier");
				String groupedWorkId = getRecordGroupingProcessor().processBorrowBoxRecord(borrowboxId);
				if (groupedWorkId != null) {
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}

				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		} catch (Exception e) {
			logEntry.incErrors("Error processing records to reload", e);
		}
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

	private synchronized void setLastSeenForProduct(long startTime, BorrowBoxRecordInfo curRecord) {
		try {
			updateLastSeenStmt.setLong(1, startTime / 1000);
			updateLastSeenStmt.setString(2, curRecord.getBorrowboxId());
			updateLastSeenStmt.executeUpdate();
		} catch (SQLException e) {
			logEntry.incErrors("Error updating last seen for " + curRecord.getBorrowboxId());
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
