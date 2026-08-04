package com.turning_leaf_technologies.overdrive;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.concurrent.*;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.zip.CRC32;

import javax.net.ssl.HttpsURLConnection;

import org.aspen_discovery.grouping.OverDriveRecordGrouper;
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

class ExtractOverDriveInfo implements AutoCloseable {
	private static final Logger logger = LogManager.getLogger(ExtractOverDriveInfo.class);
	private OverDriveRecordGrouper recordGroupingProcessorSingleton;
	private String serverName;
	private Connection dbConn;
	private OverDriveExtractLogEntry logEntry;

	private Date lastExtractDate;
	private String lastUpdateTimeParam = "";

	//Overdrive API information
	private final OverDriveSetting settings;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private final TreeMap<Long, LibraryAdvantageSetting> libraryAdvantageSettings = new TreeMap<>();

	private final ConcurrentHashMap<String, OverDriveRecordInfo> allProductsInOverDrive = new ConcurrentHashMap<>();
	private final List<AdvantageCollectionInfo> allAdvantageCollections = Collections.synchronizedList(new ArrayList<>());

	private PreparedStatement addProductStmt;
	private PreparedStatement getProductIdByOverDriveIdStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement updateProductChangeTimeStmt;
	private PreparedStatement isProductAvailableInOtherSettingsStmt;
	private PreparedStatement deleteProductStmt;
	private PreparedStatement updateProductMetadataStmt;
	private PreparedStatement getExistingMetadataIdStmt;
	private PreparedStatement addMetadataStmt;
	private PreparedStatement updateMetaDataStmt;
	private PreparedStatement clearFormatsStmt;
	private PreparedStatement addFormatStmt;
	private PreparedStatement clearIdentifiersStmt;
	private PreparedStatement addIdentifierStmt;
	private PreparedStatement getExistingAvailabilityForProductStmt;
	private PreparedStatement deleteAvailabilityStmt;
	private PreparedStatement deleteAvailabilityForSettingStmt;
	private PreparedStatement updateProductAvailabilityStmt;
	private PreparedStatement updateLastSeenStmt;
	private PreparedStatement getDeletedProductsStmt;
	private PreparedStatement getNumDeletedProductsStmt;
	private PreparedStatement getTotalProductsStmt;
	private PreparedStatement logExternalRequestStmt;

	private final CRC32 checksumCalculator = new CRC32();
	private boolean errorsWhileLoadingProducts;
	private boolean hadTimeoutsFromOverDrive;
	private GroupedWorkIndexer groupedWorkIndexer;
	private Ini configIni;

	private static class LibraryAdvantageSetting {
		private long advantageId;
		private String advantageProductsKey;
		private long additionalAdvantageId;
		private String additionalAdvantageProductsKey;

		private LibraryAdvantageSetting(long advantageId, String advantageProductsKey, long additionalAdvantageId, String additionalAdvantageProductsKey) {
			this.advantageId = advantageId;
			this.advantageProductsKey = advantageProductsKey;
			this.additionalAdvantageId = additionalAdvantageId;
			this.additionalAdvantageProductsKey = additionalAdvantageProductsKey;
		}
	}

	private static class AvailabilityProcessingResult {
		private boolean changed;
		private boolean hadErrors;
	}

	private int totalProductsInCollection;

	public ExtractOverDriveInfo(OverDriveSetting settings) {
		this.settings = settings;
	}

	int extractOverDriveInfo(Ini configIni, String serverName, Connection dbConn, OverDriveExtractLogEntry logEntry) {
		AtomicInteger numProcessed = new AtomicInteger(0);
		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logEntry = logEntry;

		long extractStartTime = new Date().getTime();

		boolean checkForDeletedRecords = false;
		Calendar rightNow = Calendar.getInstance();
		int hour = rightNow.get(Calendar.HOUR_OF_DAY);
		int configuredDeletionHour = settings.getDeletionCheckHour();
		if (configuredDeletionHour >= 0 && hour == configuredDeletionHour){
			checkForDeletedRecords = true;
		}

		try {
			initOverDriveExtract(dbConn, logEntry);

			//Initialize these so we don't have to synchronize later
			getGroupedWorkIndexer();
			getRecordGroupingProcessor();

			try {
				if (settings.getClientSecret() == null || settings.getClientKey() == null || settings.getAccountId() == null || settings.getClientSecret().isEmpty() || settings.getClientKey().isEmpty() || settings.getAccountId().isEmpty()) {
					logEntry.addNote("Did not find correct configuration in settings, not loading Libby titles");
				} else {
					if (checkForDeletedRecords) {
						//Load all products from API to figure out what is actually new, what is deleted, and what needs an update
						//This just gets minimal data, we will load more complete information when we have truly determined
						//What has changed
						if (!loadProductsFromAPI(LOAD_ALL_PRODUCTS, extractStartTime)) {
							return 0;
						}
						logger.info("There are a total of " + totalProductsInCollection + " products in the combined Libby collections");
					}

					//We now have a list of all products in all collections, but we need to know what needs availability
					//and metadata updated for it.  So we need to call again to figure out which records have
					//availability and/or metadata updated
					logger.info("Loading products with any changes (to get availability)");
					logEntry.addNote("Loading products with any changes (to get availability)");
					loadProductsFromAPI(LOAD_PRODUCTS_WITH_ANY_CHANGES, extractStartTime);

					//Look for any records that are new
					if (!settings.isRunFullUpdate()) {
						logEntry.addNote("Loading new products");
						loadNewProducts(extractStartTime);
					}

					//Remove any products owned only by libraries that are not connected to Aspen
					HashSet <String> idsToRemove = new HashSet<>();
					int numProductsToUpdate = 0;
					for (OverDriveRecordInfo recordInfo : allProductsInOverDrive.values()) {
						boolean libraryConnectedToAspen = false;
						for (AdvantageCollectionInfo collectionInfo : recordInfo.getCollections()) {
							if (!collectionInfo.getAspenLibraryIds().isEmpty() || !collectionInfo.getAdditionalAspenLibraryIds().isEmpty()) {
								libraryConnectedToAspen = true;
								break;
							}
						}
						if (!libraryConnectedToAspen) {
							idsToRemove.add(recordInfo.getId());
							logger.info("Removing " + recordInfo.getId() + " because there are no records connected to Libby");
						}else{
							if (recordInfo.hasChanges || recordInfo.isNew){
								numProductsToUpdate++;
							}
						}
					}
					for(String idToRemove : idsToRemove){
						allProductsInOverDrive.remove(idToRemove);
					}
					logEntry.addNote("Did not process " + idsToRemove.size() + " products only owned by advantage collections of Non-Aspen libraries");
					logEntry.addNote("There are " + numProductsToUpdate + " products that need to be checked for updates");
					logEntry.saveResults();

					//Do some counts of the records that will be updated for logging purposes
					int numRecordsToUpdate = 0;
					int numNewRecords = 0;
					int totalRecordsWithChanges = 0;
					for (OverDriveRecordInfo curRecord : allProductsInOverDrive.values()) {
						if (settings.getProductsToUpdate().contains(curRecord.getId().toLowerCase())){
							curRecord.hasChanges = true;
						}
						//Extract data from Libby and update the database
						if (curRecord.isNew){
							numNewRecords++;
						}else if (curRecord.hasChanges) {
							numRecordsToUpdate++;
						}
						if (curRecord.isNew || curRecord.hasChanges){
							totalRecordsWithChanges++;
						}else{
							allProductsInOverDrive.remove(curRecord.getId());
							logger.info("Removing " + curRecord + " because it is not new and does not have changes");
						}
					}
					logEntry.addNote("Preparing to update records.  There are " + allProductsInOverDrive.size() + " total records, " + numNewRecords + " are new, " + numRecordsToUpdate + " need metadata updates.");
					logEntry.setNumProducts(totalRecordsWithChanges);
					logEntry.saveResults();

					for (OverDriveRecordInfo curRecord : allProductsInOverDrive.values()) {
						numProcessed.incrementAndGet();
						try {
							//Extract data from Libby and update the database
							final boolean[] errorsEncountered = {false};
							if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasChanges) {
								//Load Metadata for the record
								Thread metadataThread = new Thread(() -> {
									try {
										updateOverDriveMetaData(curRecord);
									} catch (SocketTimeoutException e) {
										settings.addProductToUpdateNextTime(curRecord.getId());
										logEntry.addNote("Error loading metadata for " + curRecord.getId() + " " + e.getMessage());
										errorsEncountered[0] = true;
									}
								});
								//Load availability for all collections since we will currently only have collections where the record changed.
								for (AdvantageCollectionInfo collectionInfo: allAdvantageCollections) {
									curRecord.addCollection(collectionInfo);
								}
								//Load availability for the record
								Thread availabilityThread = new Thread(() -> {
									if (updateOverDriveAvailability(curRecord, curRecord.getDatabaseId(), false)){
										errorsEncountered[0] = true;
									}
								});
								metadataThread.start();
								availabilityThread.start();
								metadataThread.join();
								availabilityThread.join();

								if (!errorsEncountered[0]){
									String groupedWorkId = null;
									if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasChanges) {
										//Regroup the record
										groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(curRecord.getId());
									}
									if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasChanges) {
										//Metadata didn't change, so we need to load from the database
										if (groupedWorkId == null) {
											groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("overdrive", curRecord.getId());
										}
										//Reindex the record
										getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
										logEntry.incUpdated();
									}else{
										logEntry.incSkipped();
									}
								}
							}
						}catch (Exception e){
							logEntry.incErrors("Error processing record " + curRecord.getId(), e);
						}
						if (numProcessed.get() % 100 == 0) {
							logEntry.addNote("Processed " + numProcessed.get());
							logEntry.saveResults();
						}
					}

					if (checkForDeletedRecords) {
						//Remove any records that no longer exist.
						//There is currently an issue with Libby Search APIs that cause all records to not be returned,
						//so we will avoid deleting records if we are deleting more than 500 records or 5% of the collection
						int totalRecordsToDelete = 0;
						getNumDeletedProductsStmt.setLong(1, settings.getId());
						getNumDeletedProductsStmt.setLong(2, extractStartTime / 1000);
						try (ResultSet getNumDeletedProductsRS = getNumDeletedProductsStmt.executeQuery()) {
							if (getNumDeletedProductsRS.next()) {
								totalRecordsToDelete = getNumDeletedProductsRS.getInt(1);
							}
						}
						int totalOverDriveRecords = 0;
						getTotalProductsStmt.setLong(1, settings.getId());
						try (ResultSet getTotalProductsRS = getTotalProductsStmt.executeQuery()) {
							if (getTotalProductsRS.next()) {
								totalOverDriveRecords = getTotalProductsRS.getInt(1);
							}
						}
						if (!this.errorsWhileLoadingProducts && !this.hadTimeoutsFromOverDrive) {
							if (totalRecordsToDelete > 0 && (settings.isAllowLargeDeletes() || (totalRecordsToDelete < 500 && totalOverDriveRecords > 0 && (((float) totalRecordsToDelete / totalOverDriveRecords) < .05)))) {
								int numRecordsDeleted = 0;
								getDeletedProductsStmt.setLong(1, settings.getId());
								getDeletedProductsStmt.setLong(2, extractStartTime / 1000);
								try (ResultSet getDeletedProductsRS = getDeletedProductsStmt.executeQuery()) {
									while (getDeletedProductsRS.next()) {
										String overDriveId = getDeletedProductsRS.getString("overdriveId");
										long aspenOverDriveId = getDeletedProductsRS.getLong("id");
										deleteProduct(overDriveId, aspenOverDriveId);
										numRecordsDeleted++;
										if (numRecordsDeleted % 100 == 0) {
											logEntry.saveResults();
										}
									}
								}
								logger.info("Deleted " + numRecordsDeleted + " records that no longer exist");
							} else if (!settings.isAllowLargeDeletes() && totalRecordsToDelete >= 500) {
								logEntry.incErrors("There were more than 500 records to delete, detected " + totalRecordsToDelete + ", not deleting records");
							} else if (!settings.isAllowLargeDeletes() && (((float) totalRecordsToDelete / totalOverDriveRecords) >= .05)) {
								logEntry.incErrors("More than 5% of the collection was marked as being deleted. Detected " + totalRecordsToDelete + " of " + totalOverDriveRecords + " to delete, not deleting records");
							}
						} else {
							logger.info("Did not delete " + totalRecordsToDelete + " records that no longer exist because we received errors from Libby.");
						}
					}

					try (PreparedStatement saveProductsToUpdateStmt = dbConn.prepareStatement("UPDATE overdrive_settings set productsToUpdate = ? WHERE id = ?")) {
						saveProductsToUpdateStmt.setString(1, settings.getProductsToUpdateNextTimeAsString());
						saveProductsToUpdateStmt.setLong(2, settings.getId());
						saveProductsToUpdateStmt.executeUpdate();
					}

					//For any records that have been marked to reload, regroup and reindex the records
					processRecordsToReload(logEntry);

					//Finally, process any records that seem to be unlinked
					//MDN 3/6/24 - This is no longer needed.
					//processUnlinkedProducts();

				}
			}catch (SocketTimeoutException toe){
				logger.info("Timeout while loading product information from Libby, aborting");
				logEntry.addNote("Timeout while loading information from Libby, aborting");
				errorsWhileLoadingProducts = true;
			}catch (Exception e){
				logger.error("Error while loading product information from Libby, aborting");
				logEntry.addNote("Error while loading information from Libby, aborting");
				errorsWhileLoadingProducts = true;
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

			//Mark the new last update time if we did not get errors loading products from the database
			if (errorsWhileLoadingProducts || this.logEntry.hasErrors()) {
				this.logEntry.addNote("Not setting last extract time since there were problems extracting products from the API");
			} else {
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (settings.isRunFullUpdate()){
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				try (PreparedStatement updateExtractTime = dbConn.prepareStatement("UPDATE overdrive_settings set " + columnToUpdate + " = ? WHERE id = ?")) {
					updateExtractTime.setLong(1, extractStartTime / 1000);
					updateExtractTime.setLong(2, settings.getId());
					updateExtractTime.executeUpdate();
				}
				logger.debug("Setting last extract time to " + extractStartTime + " " + new Date(extractStartTime));
			}
		} catch (SQLException e) {
			// handle any errors
			this.logEntry.incErrors("Error initializing Libby extraction ", e);
		}
		return numProcessed.get();
	}

	int processSingleWork(String singleWorkId, Ini configIni, String serverName, Connection dbConn, OverDriveExtractLogEntry logEntry) {
		int numChanges = 0;

		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logEntry = logEntry;

		try {
			initOverDriveExtract(dbConn, logEntry);

			try {
				if (settings.getClientSecret() == null || settings.getClientKey() == null || settings.getAccountId() == null || settings.getClientSecret().isEmpty() || settings.getClientKey().isEmpty() || settings.getAccountId().isEmpty()) {
					logEntry.addNote("Did not find correct configuration in settings, not loading Libby titles");
				} else {
					//Load products from the database this lets us know what is new, what has been deleted, and what has been updated
					singleWorkId = singleWorkId.toLowerCase();
					OverDriveRecordInfo recordInfo = new OverDriveRecordInfo();
					recordInfo.setId(singleWorkId);

					getProductIdByOverDriveIdStmt.setString(1, singleWorkId);
					try (ResultSet getProductIdByOverDriveIdRS = getProductIdByOverDriveIdStmt.executeQuery()) {
						if (getProductIdByOverDriveIdRS.next()) {
							recordInfo.setDatabaseId(getProductIdByOverDriveIdRS.getLong(1));
							if (getProductIdByOverDriveIdRS.getBoolean("deleted")) {
								logger.error("Record " + singleWorkId + " has been marked as deleted in the database");
							}
						}
					}

					//Get a list of all the advantage collections for the account
					if (loadAccountInformationFromAPI()) {

						//Call API for the product to figure out what collections the record belongs to
						for (AdvantageCollectionInfo collectionInfo: allAdvantageCollections) {
							//TODO: Do we need to validate this before updating metadata and availability?
							recordInfo.addCollection(collectionInfo);
						}

						//Update the product in the database
						updateOverDriveMetaData(recordInfo);
						updateOverDriveAvailability(recordInfo, recordInfo.getDatabaseId(), true);

						//Reindex
						String groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(recordInfo.getId());
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						numChanges++;
					}else {
						logger.error("Unable to load account information");
					}
				}

				logger.info("Processed " + numChanges);
			}catch (SocketTimeoutException toe){
				logger.info("Timeout while loading availability information from Libby, aborting");
				logEntry.addNote("Timeout while loading availability information from Libby, aborting");
				errorsWhileLoadingProducts = true;
			}catch (Exception e){
				logger.error("Error while loading availability information from Libby, aborting");
				logEntry.addNote("Error while loading availability information from Libby, aborting");
				errorsWhileLoadingProducts = true;
			}

			logger.info("Processed " + numChanges);

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
			// handle any errors
			this.logEntry.incErrors("Error initializing Libby extraction ", e);
		}
		return numChanges;
	}

	private void processRecordsToReload(OverDriveExtractLogEntry logEntry) {
		try (PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='overdrive'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY)) {
			try (PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?")) {
				try (ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery()) {
					int numRecordsToReloadProcessed = 0;
					while (getRecordsToReloadRS.next()) {
						long recordToReloadId = getRecordsToReloadRS.getLong("id");
						String overDriveId = getRecordsToReloadRS.getString("identifier");
						//Regroup the record
						String groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(overDriveId);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}
					if (numRecordsToReloadProcessed > 0) {
						logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
					}
				}
			}
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private void initOverDriveExtract(Connection dbConn, OverDriveExtractLogEntry logEntry) throws SQLException {
		addProductStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_products set id = NULL, overdriveid = ?, crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastMetadataCheck = 0, lastMetadataChange = 0, lastAvailabilityCheck = 0, lastAvailabilityChange = 0 ON DUPLICATE KEY UPDATE id=id", PreparedStatement.RETURN_GENERATED_KEYS);
		getProductIdByOverDriveIdStmt = dbConn.prepareStatement("SELECT id, deleted from overdrive_api_products where overdriveid = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateLastSeenStmt = dbConn.prepareStatement("UPDATE overdrive_api_products set lastSeen = ? where overdriveid = ?");
		getNumDeletedProductsStmt = dbConn.prepareStatement("SELECT count(*) from overdrive_api_products inner join overdrive_api_product_availability on productId = overdrive_api_products.id where deleted = 0 and settingId = ? and lastSeen < ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getTotalProductsStmt = dbConn.prepareStatement("SELECT count(*) from overdrive_api_products inner join overdrive_api_product_availability on productId = overdrive_api_products.id where deleted = 0 and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getDeletedProductsStmt = dbConn.prepareStatement("SELECT overdrive_api_products.id, overdriveId from overdrive_api_products inner join overdrive_api_product_availability on productId = overdrive_api_products.id where deleted = 0 and settingId = ? and lastSeen < ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, deleted = 0 where id = ?");
		updateProductChangeTimeStmt = dbConn.prepareStatement("UPDATE overdrive_api_products set dateUpdated = ? WHERE overdriveId = ?");
		deleteProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET deleted = 1, dateDeleted = ? where id = ?");
		isProductAvailableInOtherSettingsStmt = dbConn.prepareStatement("SELECT count(*) as availabilityCount from overdrive_api_product_availability where productId = ? and settingId <> ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateProductMetadataStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
		getExistingMetadataIdStmt = dbConn.prepareStatement("SELECT id, UNCOMPRESSED_LENGTH(rawData) as rawDataLength from overdrive_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		addMetadataStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_metadata (productId, checksum, sortTitle, publisher, publishDate, isPublicDomain, isPublicPerformanceAllowed, shortDescription, fullDescription, popularity, thumbnail, cover, isOwnedByCollections, rawData) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,COMPRESS(?))");
		updateMetaDataStmt = dbConn.prepareStatement("UPDATE overdrive_api_product_metadata SET checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, popularity = ?, thumbnail=?, cover=?, isOwnedByCollections=?, rawData=COMPRESS(?) WHERE id = ?");
		clearFormatsStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_formats where productId = ?");
		addFormatStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_formats set id = NULL, productId = ?, textId = ?, numericId = ?, name = ?, fileName = ?, fileSize = ?, partCount = ?, sampleSource_1 = ?, sampleUrl_1 = ?, sampleSource_2 = ?, sampleUrl_2 = ? ON DUPLICATE KEY update id = id", PreparedStatement.RETURN_GENERATED_KEYS);
		clearIdentifiersStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_identifiers where productId = ?");
		addIdentifierStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_identifiers set productId = ?, type = ?, value = ?");
		getExistingAvailabilityForProductStmt = dbConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ? and settingId = ?");
		deleteAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where id = ?");
		deleteAvailabilityForSettingStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability WHERE productId = ? and settingId = ?");
		updateProductAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastAvailabilityCheck = ?, lastAvailabilityChange = ? where id = ?");
		logExternalRequestStmt = dbConn.prepareStatement("INSERT INTO external_request_log (requestType, requestMethod, requestUrl, requestHeaders, requestBody, responseCode, response, requestTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

		//Load last extract time regardless of if we are doing full index or partial index
		if (!settings.isRunFullUpdate()) {
			lastExtractDate = new Date(settings.getLastUpdateOfChangedRecords() * 1000);
			SimpleDateFormat lastUpdateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
			//lastUpdateFormat.setTimeZone(TimeZone.getTimeZone("UTC"));
			logEntry.addNote("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
			lastUpdateTimeParam = lastUpdateFormat.format(lastExtractDate);
			//Simple Date Format doesn't give us quite the right timezone format, so adjust
			lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 2) + ":" + lastUpdateTimeParam.substring(lastUpdateTimeParam.length() - 2);
			//lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 5) + "Z";
		}else{
			//Update the settings to mark the full update as not needed
			try (PreparedStatement updateRunFullUpdateStmt = dbConn.prepareStatement("UPDATE overdrive_settings set runFullUpdate = 0 where id = " + settings.getId())) {
				updateRunFullUpdateStmt.executeUpdate();
			}
		}

		try (PreparedStatement advantageCollectionMapStmt = dbConn.prepareStatement("SELECT library.libraryId, library_overdrive_settings.overdriveAdvantageProductsKey, library_overdrive_settings.overdriveAdvantageId, library_overdrive_settings.additionalAdvantageId, library_overdrive_settings.additionalAdvantageProductsKey FROM library INNER JOIN library_overdrive_settings on library.libraryId = library_overdrive_settings.libraryId where library_overdrive_settings.overdriveAdvantageName != '' and settingId = ?")) {
			advantageCollectionMapStmt.setLong(1, settings.getId());
			try (ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery()) {
				while (advantageCollectionMapRS.next()) {
					long libraryId = advantageCollectionMapRS.getLong(1);
					String advantageProductsKey = advantageCollectionMapRS.getString(2);
					long advantageId = advantageCollectionMapRS.getLong(3);
					long additionalAdvantageId = advantageCollectionMapRS.getLong(4);
					String additionalAdvantageProductsKey = advantageCollectionMapRS.getString(5);
					libraryAdvantageSettings.put(libraryId, new LibraryAdvantageSetting(advantageId, advantageProductsKey, additionalAdvantageId, additionalAdvantageProductsKey));
				}
			}
		}
	}

	private void deleteProduct(String overDriveId, long aspenOverDriveId) {
		try {
			//Check to be sure the product isn't active from other settings
			isProductAvailableInOtherSettingsStmt.setLong(1, aspenOverDriveId);
			isProductAvailableInOtherSettingsStmt.setLong(2, settings.getId());
			boolean isAvailableElsewhere = false;
			try (ResultSet isProductAvailableInOtherSettingsRS = isProductAvailableInOtherSettingsStmt.executeQuery()) {
				if (isProductAvailableInOtherSettingsRS.next()) {
					int availabilityCount = isProductAvailableInOtherSettingsRS.getInt("availabilityCount");
					if (availabilityCount > 0) {
						isAvailableElsewhere = true;
					}
				}
			}

			if (isAvailableElsewhere) {
				//Remove availability within this collection and reindex
				deleteAvailabilityForSettingStmt.setLong(1, aspenOverDriveId);
				deleteAvailabilityForSettingStmt.setLong(2, settings.getId());
				deleteAvailabilityForSettingStmt.executeUpdate();
				logEntry.incDeleted();

				String permanentId = getRecordGroupingProcessor().getPermanentIdForRecord("overdrive", overDriveId);
				getGroupedWorkIndexer().processGroupedWork(permanentId);
			}else{
				long curTime = new Date().getTime() / 1000;
				deleteProductStmt.setLong(1, curTime);
				deleteProductStmt.setLong(2, aspenOverDriveId);
				deleteProductStmt.executeUpdate();
				logEntry.incDeleted();

				//If there is no availability in other collections, we can just delete the product.
				RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("overdrive", overDriveId);

				if (result.reindexWork) {
					getGroupedWorkIndexer().processGroupedWork(result.permanentId);
				} else if (result.deleteWork) {
					//Delete the work from solr and the database
					getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error deleting Libby product " + aspenOverDriveId, e);
		}
	}

	private void updateProductInDB(long databaseId, String overDriveId, Long crossRefId, String mediaType, String title, String subtitle, String series, String primaryCreatorRole, String primaryCreatorName, String coverUrl)  {
		try {
			//Update the product in the database
			long curTime = new Date().getTime() / 1000;
			int curCol = 0;
			//noinspection DuplicatedCode
			updateProductStmt.setLong(++curCol, crossRefId);
			updateProductStmt.setString(++curCol, mediaType);
			updateProductStmt.setString(++curCol, title);
			updateProductStmt.setString(++curCol, subtitle);
			updateProductStmt.setString(++curCol, series);
			updateProductStmt.setString(++curCol, primaryCreatorRole);
			updateProductStmt.setString(++curCol, primaryCreatorName);
			updateProductStmt.setString(++curCol, coverUrl);
			updateProductStmt.setLong(++curCol, databaseId);

			//If we have made changes, update that the bib has changed
			int numChanges = updateProductStmt.executeUpdate();
			if (numChanges > 0) {
				updateProductChangeTimeStmt.setLong(1, curTime);
				updateProductChangeTimeStmt.setString(2, overDriveId);

				updateProductChangeTimeStmt.executeUpdate();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error updating Libby product " + overDriveId, e);
		}

	}

	private synchronized long addProductToDB(String overDriveId, Long crossRefId, String mediaType, String title, String subtitle, String series, String primaryCreatorRole, String primaryCreatorName, String coverUrl) {
		int curCol = 0;
		long databaseId = -1;
		try {
			long curTime = new Date().getTime() / 1000;
			addProductStmt.setString(++curCol, overDriveId);
			addProductStmt.setLong(++curCol, crossRefId);
			//noinspection DuplicatedCode
			addProductStmt.setString(++curCol, mediaType);
			addProductStmt.setString(++curCol, title);
			addProductStmt.setString(++curCol, subtitle);
			addProductStmt.setString(++curCol, series);
			addProductStmt.setString(++curCol, primaryCreatorRole);
			addProductStmt.setString(++curCol, primaryCreatorName);
			addProductStmt.setString(++curCol, coverUrl);
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.executeUpdate();

			try (ResultSet newIdRS = addProductStmt.getGeneratedKeys()) {
				if (newIdRS.next()) {
					databaseId = newIdRS.getLong(1);
				} else {
					//Get the id of the title in Libby. This happens when we are adding titles in multiple threads,
					//or when the title was not previously available in a setting, but did exist in another setting
					getProductIdByOverDriveIdStmt.setString(1, overDriveId);
					try (ResultSet getProductIdByOverDriveIdRS = getProductIdByOverDriveIdStmt.executeQuery()) {
						if (getProductIdByOverDriveIdRS.next()) {
							databaseId = getProductIdByOverDriveIdRS.getLong(1);
						}
					}
				}
			}

			logEntry.incAdded();

			if (databaseId == -1){
				logEntry.incErrors("A title was not added to the database properly");
			}

		} catch (SQLException e) {
			logEntry.incErrors("Error saving product " + overDriveId + " to the database " , e);
		}
		return databaseId;
	}

	private void loadNewProducts(long startTime) throws SocketTimeoutException {
		int daysToLoad = (int)Math.ceil((double)(new Date().getTime() - lastExtractDate.getTime()) / (double)(24 * 60 * 60 * 1000));

		//Only use a maximum of 90 days since that is all that Libby supports.
		if (daysToLoad > 90){
			daysToLoad = 90;
		}

		for (AdvantageCollectionInfo collectionInfo : allAdvantageCollections){
			String newProductsUrl = "https://api.overdrive.com/v1/collections/" + collectionInfo.getCollectionToken() + "/products/?daysSinceAdded=" + daysToLoad;
			loadProductsFromUrl(collectionInfo, newProductsUrl, LOAD_NEW_PRODUCTS, startTime);
		}
	}

	private final int LOAD_ALL_PRODUCTS = 0;
	private final int LOAD_PRODUCTS_WITH_ANY_CHANGES = 2;
	private final int LOAD_NEW_PRODUCTS = 3;

	/**
	 * Get all products that are currently in Libby to determine what needs to be deleted.
	 * We just get minimal information to start, the id and the list of collections that the product is valid for.
	 *
	 * @return boolean whether errors occurred
	 * @throws SocketTimeoutException Error if we have a timeout getting data
	 */
	private boolean loadProductsFromAPI(int loadType, long startTime) throws SocketTimeoutException {
		WebServiceResponse libraryInfoResponse = callOverDriveURL("overDriveExtract.loadLibraries", "https://api.overdrive.com/v1/libraries/" + settings.getAccountId());
		if (libraryInfoResponse.getResponseCode() == 200 && libraryInfoResponse.getMessage() != null){
			JSONObject libraryInfo = libraryInfoResponse.getJSONResponse();

			// Update shared Products Key
			String sharedCollectionToken = libraryInfo.getString("collectionToken");
			if (!sharedCollectionToken.isEmpty() && !sharedCollectionToken.equals(settings.getProductsKey())) {
				try (PreparedStatement updateProductsKeyStmt = dbConn.prepareStatement("UPDATE overdrive_settings SET productsKey = ? WHERE id = ? ")) {
					updateProductsKeyStmt.setString(1, sharedCollectionToken);
					updateProductsKeyStmt.setLong(2, settings.getId());
					updateProductsKeyStmt.executeUpdate();
				} catch (Exception e) {
					logger.error("Error updating OverDrive productsKey", e);
				}
			}

			//noinspection CommentedOutCode
			try {
				//noinspection DuplicatedCode
				String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
				if (mainProductUrl.contains("?")) {
					mainProductUrl += "&minimum=true";
				}else{
					mainProductUrl += "?minimum=true";
				}
				if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES){
					mainProductUrl += "&lastUpdateTime=" + lastUpdateTimeParam;
				}
				AdvantageCollectionInfo mainCollectionInfo = null;
				boolean loadCollectionInfo = false;
				if (loadType == LOAD_ALL_PRODUCTS || allAdvantageCollections.isEmpty()) {
					mainCollectionInfo = new AdvantageCollectionInfo();
					mainCollectionInfo.setAdvantageId(-1);
					mainCollectionInfo.setName("Shared Libby Collection");
					mainCollectionInfo.setCollectionToken(sharedCollectionToken);
					mainCollectionInfo.addAspenLibraryId(-1);
					allAdvantageCollections.add(mainCollectionInfo);
					loadCollectionInfo = true;
				}else{
					for (AdvantageCollectionInfo curCollection : allAdvantageCollections){
						if (curCollection.getAspenLibraryIds().contains(-1L)){
							mainCollectionInfo = curCollection;
							break;
						}
					}
				}
				loadProductsFromUrl(mainCollectionInfo, mainProductUrl, loadType, startTime);

				//TODO: Use Digital inventory collection to figure out which titles are deleted and which titles each collection owns
				// digital inventory only updates once a day though.
//				if (mainCollectionInfo != null) {
//					WebServiceResponse digitalInventoryRequest = callOverDriveURL("overDriveExtract.digitalInventory", "https://api.overdrive.com/v1/collections/" + mainCollectionInfo.getCollectionToken() + "/digitalInventory");
//					JSONObject digitalInventoryInfo = digitalInventoryRequest.getJSONResponse();
//					if (digitalInventoryInfo.getInt("totalItems") >= 0) {
//						JSONArray files = digitalInventoryInfo.getJSONArray("files");
//						for (int i = 0; i < files.length(); i++) {
//							JSONObject file = files.getJSONObject(i);
//							String fileUrl = file.getString("fileUrl");
//							String fileExpires = file.getString("expires");
//						}
//					}
//				}

				logEntry.setNumProducts(allProductsInOverDrive.size());
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL("overdriveExtract.loadAdvantageAccounts", libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getJSONResponse();
						if (advantageInfo.has("advantageAccounts")) {
							//Thread loading advantage accounts to improve the speed of loading
							ExecutorService es = Executors.newCachedThreadPool();
							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							boolean finalLoadCollectionInfo = loadCollectionInfo;
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
								es.execute(() -> {
									try {
										loadProductsForAdvantageAccount(loadType, curAdvantageAccount, startTime, finalLoadCollectionInfo);
									} catch (SocketTimeoutException e) {
										logEntry.incErrors("Socket timeout loading information from Libby API ", e);
										hadTimeoutsFromOverDrive = true;
									}
								});
							}
							es.shutdown();
							while (true) {
								try {
									boolean terminated = es.awaitTermination(15, TimeUnit.SECONDS);
									if (terminated) {
										break;
									}
								} catch (InterruptedException e) {
									logger.error("Error waiting for all extracts to finish", e);
								}
							}
						}
					} else {
						logEntry.incErrors("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
						if (webServiceResponse.getMessage() != null) {
							logEntry.addNote(webServiceResponse.getMessage());
						}
					}
				}else{
					logger.debug("No Advantage accounts exist for the library.");
				}

				logEntry.setNumProducts(allProductsInOverDrive.size());
				return true;
			} catch (SocketTimeoutException toe){
				throw toe;
			} catch (Exception e) {
				logEntry.incErrors("error loading information from Libby API ", e);
				return false;
			}
		}else{
			logEntry.incErrors("Unable to load library product information for library " + settings.getAccountId());
			if (libraryInfoResponse.getMessage() != null){
				logEntry.addNote(libraryInfoResponse.getMessage());
			}
			logger.info("Error loading Libby titles " + libraryInfoResponse.getMessage());
			return false;
		}
	}

	private void loadProductsForAdvantageAccount(int loadType, JSONObject curAdvantageAccount, long startTime, boolean loadCollectionInfo) throws SocketTimeoutException {
		AdvantageCollectionInfo collectionInfo = null;
		if (loadType == LOAD_ALL_PRODUCTS || loadCollectionInfo) {
			collectionInfo = buildAdvantageCollectionInfo(curAdvantageAccount);
			if (!collectionInfo.getName().contains("Inactive")) {
				allAdvantageCollections.add(collectionInfo);
			}
		}else{
			int collectionId = curAdvantageAccount.getInt("id");
			for (AdvantageCollectionInfo curCollectionInfo : allAdvantageCollections){
				if (curCollectionInfo.getAdvantageId() == collectionId){
					collectionInfo = curCollectionInfo;
					break;
				}
			}
		}

		boolean processCollection = true;
		if (collectionInfo == null){
			//This happens when we are processing individual advantage accounts. It should only happen for collections that Libby has designated as Inactive
			if (!curAdvantageAccount.getString("name").contains("Inactive")) {
				logger.error("Did not get collection information for " + curAdvantageAccount.getString("name"));
			}
			processCollection = false;
		}else{
			if (collectionInfo.getAspenLibraryIds().isEmpty() && collectionInfo.getAdditionalAspenLibraryIds().isEmpty()){
				processCollection = false;
			}
		}
		if (processCollection) {
			//Need to load products for all advantage libraries since they can be shared with the entire consortium.
			//Get the product URL for just the advantage account
			String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
			WebServiceResponse advantageWebServiceResponse = callOverDriveURL("overdriveExtract.loadAdvantageProducts", advantageSelfUrl);
			if (advantageWebServiceResponse.getResponseCode() == 200) {
				JSONObject advantageSelfInfo = advantageWebServiceResponse.getJSONResponse();
				if (advantageSelfInfo != null) {
					//noinspection DuplicatedCode
					String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
					if (productUrl.contains("?")) {
						productUrl += "&minimum=true";
					} else {
						productUrl += "?minimum=true";
					}
					if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES) {
						productUrl += "&lastUpdateTime=" + lastUpdateTimeParam;
					}

					loadProductsFromUrl(collectionInfo, productUrl, loadType, startTime);
				}
			} else {
				logEntry.addNote("Unable to load advantage information for " + advantageSelfUrl);
				if (advantageWebServiceResponse.getMessage() != null) {
					logEntry.addNote(advantageWebServiceResponse.getMessage());
				}
			}
		}
		logEntry.setNumProducts(allProductsInOverDrive.size());
	}

	/**
	 * Get all products that are currently in Libby, so we can determine what needs to be deleted.
	 * We just get minimal information to start, the id and the list of collections that the product is valid for.
	 *
	 * @return boolean whether errors occurred
	 * @throws SocketTimeoutException Error if we get a timeout retrieving data
	 */
	private boolean loadAccountInformationFromAPI() throws SocketTimeoutException {
		WebServiceResponse libraryInfoResponse = callOverDriveURL("overdriveExtract.loadLibraryAccount", "https://api.overdrive.com/v1/libraries/" + settings.getAccountId());
		if (libraryInfoResponse.getResponseCode() == 200 && libraryInfoResponse.getMessage() != null){
			JSONObject libraryInfo = libraryInfoResponse.getJSONResponse();

			// Update shared Products Key
			String sharedCollectionToken = libraryInfo.getString("collectionToken");
			if (!sharedCollectionToken.isEmpty() && !sharedCollectionToken.equals(settings.getProductsKey())) {
				try (PreparedStatement updateProductsKeyStmt = dbConn.prepareStatement("UPDATE overdrive_settings SET productsKey = ? WHERE id = ?")) {
					updateProductsKeyStmt.setString(1, sharedCollectionToken);
					updateProductsKeyStmt.setLong(2, settings.getId());
					updateProductsKeyStmt.executeUpdate();
				} catch (SQLException e) {
					logEntry.incErrors("Error updating shared OverDrive products key", e);
				}
			}

			AdvantageCollectionInfo mainCollectionInfo = new AdvantageCollectionInfo();
			mainCollectionInfo.setAdvantageId(-1);
			mainCollectionInfo.setName("Shared Libby Collection");
			mainCollectionInfo.setCollectionToken(sharedCollectionToken);
			mainCollectionInfo.addAspenLibraryId(-1);
			allAdvantageCollections.add(mainCollectionInfo);
			try {
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL("overdriveExtract.loadAdvantageAccounts", libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getJSONResponse();
						if (advantageInfo.has("advantageAccounts")) {
							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
								AdvantageCollectionInfo collectionInfo = buildAdvantageCollectionInfo(curAdvantageAccount);
								if (!collectionInfo.getName().contains("Inactive")) {
									allAdvantageCollections.add(collectionInfo);
								}
							}
						}
					} else {
						logEntry.incErrors("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
						if (webServiceResponse.getMessage() != null) {
							logEntry.addNote(webServiceResponse.getMessage());
						}
					}
				}
				logEntry.setNumProducts(allProductsInOverDrive.size());
				return true;
			} catch (SocketTimeoutException toe){
				throw toe;
			} catch (Exception e) {
				logEntry.incErrors("error loading information from Libby API ", e);
				return false;
			}
		}else{
			logEntry.incErrors("Unable to load library account information for library " + settings.getAccountId());
			if (libraryInfoResponse.getMessage() != null){
				logEntry.addNote(libraryInfoResponse.getMessage());
			}
			logger.info("Error loading Libby accounts " + libraryInfoResponse.getMessage());
			return false;
		}
	}

	private AdvantageCollectionInfo buildAdvantageCollectionInfo(JSONObject curAdvantageAccount) throws JSONException {
		int apiAdvantageId = curAdvantageAccount.getInt("id");
		String apiCollectionToken = curAdvantageAccount.getString("collectionToken");

		AdvantageCollectionInfo collectionInfo = new AdvantageCollectionInfo();
		collectionInfo.setAdvantageId(apiAdvantageId);
		collectionInfo.setName(curAdvantageAccount.getString("name"));
		collectionInfo.setCollectionToken(apiCollectionToken);

		for (Map.Entry<Long, LibraryAdvantageSetting> entry : libraryAdvantageSettings.entrySet()) {
			long libraryId = entry.getKey();
			LibraryAdvantageSetting storedLibrarySetting = entry.getValue();
			long storedAdvantageId = storedLibrarySetting.advantageId;
			String storedAdvantageKey = storedLibrarySetting.advantageProductsKey;
			long storedAddiontalAdvantageId = storedLibrarySetting.additionalAdvantageId;
			String storedAdditionalAdvantageProductsKey = storedLibrarySetting.additionalAdvantageProductsKey;

			boolean needUpdate = false;
			boolean matchedPrimary = false;
			boolean needAdditionalUpdate = false;
			if (storedAdvantageId > 0) {
				if (storedAdvantageId == apiAdvantageId) {
					collectionInfo.addAspenLibraryId(libraryId);
					needUpdate = !Objects.equals(storedAdvantageKey, apiCollectionToken);
					matchedPrimary = true;
				}
			} else {
				if (Objects.equals(storedAdvantageKey, apiCollectionToken)) {
					collectionInfo.addAspenLibraryId(libraryId);
					needUpdate = true;
					matchedPrimary = true;
				}
			}
			if (needUpdate) {
				try (PreparedStatement updateStmt = dbConn.prepareStatement("UPDATE library_overdrive_settings SET overdriveAdvantageId = ?, overdriveAdvantageProductsKey = ? WHERE settingId = ? AND libraryId = ?")) {
					updateStmt.setLong(1, apiAdvantageId);
					updateStmt.setString(2, apiCollectionToken);
					updateStmt.setLong(3, settings.getId());
					updateStmt.setLong(4, libraryId);
					updateStmt.executeUpdate();
				} catch (Exception e) {
					logEntry.incErrors("Error updating Advantage setting for library " + libraryId, e);
				}
			}

			if (matchedPrimary) {
				continue;
			}

			// Additonal slot
			if (storedAddiontalAdvantageId > 0 && storedAddiontalAdvantageId == apiAdvantageId) {
				collectionInfo.addAdditionalAspenLibraryId(libraryId);
				needAdditionalUpdate = !Objects.equals(storedAdditionalAdvantageProductsKey, apiCollectionToken);
			} else if (storedAddiontalAdvantageId == 0 && storedAdditionalAdvantageProductsKey.equals(apiCollectionToken)) {
				collectionInfo.addAdditionalAspenLibraryId(libraryId);
				needAdditionalUpdate = true;
			}

			if (needAdditionalUpdate) {
				try (PreparedStatement updateStmt = dbConn.prepareStatement("UPDATE library_overdrive_settings SET additionalAdvantageId = ?, additionalAdvantageProductsKey = ? WHERE settingId = ? AND libraryId = ?")) {
					updateStmt.setLong(1, apiAdvantageId);
					updateStmt.setString(2, apiCollectionToken);
					updateStmt.setLong(3, settings.getId());
					updateStmt.setLong(4, libraryId);
					updateStmt.executeUpdate();
				} catch (Exception e) {
					logEntry.incErrors("Error updating additional Advantage setting for library " + libraryId, e);
				}
			}
		}
		return collectionInfo;
	}

	private void loadProductsFromUrl(AdvantageCollectionInfo collectionInfo, String mainProductUrl, int loadType, long startTime) throws JSONException, SocketTimeoutException {
		if  (loadType == LOAD_ALL_PRODUCTS && collectionInfo.getAspenLibraryIds().isEmpty() && collectionInfo.getAdditionalAspenLibraryIds().isEmpty()) {
			logger.info("Not loading products for " + collectionInfo.getName() + " since it is not part of Aspen");
		}
		int numProductsLoaded = 0;
		int numProductsPreviouslyLoaded = 0;
		WebServiceResponse productsResponse = callOverDriveURL("overdriveExtract.loadProducts", mainProductUrl);
		if (productsResponse.getResponseCode() == 200) {
			JSONObject productInfo = productsResponse.getJSONResponse();
			if (productInfo == null) {
				return;
			}
			long numProducts = productInfo.getLong("totalItems");
			//if (numProducts > 50) numProducts = 50;
			logger.info(collectionInfo.getName() + " collection has " + numProducts + " products, the libraryIds for the collection are " + collectionInfo.getAspenLibraryIds());
			if (loadType == LOAD_ALL_PRODUCTS) {
				logEntry.addNote(collectionInfo.getName() + " collection has " + numProducts + " products, the libraryIds for the collection are " + collectionInfo.getAspenLibraryIds());
				logEntry.saveResults();
			}
			int batchSize = 300;
			for (int i = 0; i < numProducts; i += batchSize) {
				//Search for the specific product
				String batchUrl = mainProductUrl;
				if (mainProductUrl.contains("?")) {
					batchUrl += "&";
				} else {
					batchUrl += "?";
				}
				logger.debug("Processing " + collectionInfo.getName() + " batch from " + i + " to " + (i + batchSize));
				batchUrl += "offset=" + i + "&limit=" + batchSize;

				int maxTries = Math.max(1, settings.getNumRetriesOnError() + 1);
				for (int tries = 0; tries < maxTries; tries++){
					WebServiceResponse productBatchInfoResponse = callOverDriveURL("overdriveExtract.getProductsBatch", batchUrl, tries == maxTries -1);
					if (productBatchInfoResponse.getResponseCode() == 200) {
						JSONObject productBatchInfo = productBatchInfoResponse.getJSONResponse();
						if (productBatchInfo != null && productBatchInfo.has("products")) {
							numProducts = productBatchInfo.getLong("totalItems");
							JSONArray products = productBatchInfo.getJSONArray("products");
							logger.debug(" Found " + products.length() + " products");
							for (int j = 0; j < products.length(); j++) {
								JSONObject curProduct = products.getJSONObject(j);
								//Update the main data in the database and
								OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(collectionInfo, curProduct);
								OverDriveRecordInfo previouslyLoadedProduct = allProductsInOverDrive.get(curRecord.getId());
								if (loadType == LOAD_ALL_PRODUCTS) {
									setLastSeenForProduct(startTime, curRecord);
									totalProductsInCollection++;
								} else {
									//By definition, the record has changes if we are loading just changes
									curRecord.hasChanges = true;
									if (previouslyLoadedProduct == null) {
										allProductsInOverDrive.put(curRecord.getId(), curRecord);
										getExistingRecordInformationForProduct(curRecord);
										previouslyLoadedProduct = curRecord;
										logger.debug("    No previously loaded product for " + curRecord.getId());
									} else {
										numProductsPreviouslyLoaded++;
										previouslyLoadedProduct.hasChanges = true;
										logger.debug("    Found previously loaded product for " + curRecord.getId());
									}
									previouslyLoadedProduct.addCollection(collectionInfo);
								}
								numProductsLoaded++;
							}
							//Get out of the number of tries
							if (loadType == LOAD_ALL_PRODUCTS) {
								logEntry.setNumProducts(totalProductsInCollection);
								logEntry.saveResults();
							}else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES || loadType == LOAD_NEW_PRODUCTS) {
								logEntry.setNumProducts(allProductsInOverDrive.size());
								logEntry.saveResults();
							}
							break;
						}else{
							//This seems to be a normal thing if a batch has no titles in it. Log the condition and move on.
							if (tries == maxTries - 1) {
								logEntry.addNote("Batch " + i + " did not have any products in it, but we got back a 200 code");
							}
						}
					} else {
						if (tries == maxTries - 1) {
							logEntry.incErrors("Could not load product batch: response code " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getMessage());
							logEntry.addNote(batchUrl);
							errorsWhileLoadingProducts = true;
						}else{
							//Give Libby a few seconds to sort itself out.
							try {
								Thread.sleep(30000);
							} catch (InterruptedException e) {
								logEntry.addNote("Sleeping after loading product batch was interrupted");
							}
						}
					}
				}
			}
			if (loadType == LOAD_ALL_PRODUCTS) {
				logEntry.addNote(collectionInfo.getName() + " has " + numProductsLoaded + " products in it, " + numProductsPreviouslyLoaded + " were loaded previously.");
			}else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES) {
				logEntry.addNote(collectionInfo.getName() + " has " + numProductsLoaded + " changed products in it, " + numProductsPreviouslyLoaded + " were loaded previously.");
			}else if (loadType == LOAD_NEW_PRODUCTS) {
				logEntry.addNote(collectionInfo.getName() + " has " + numProductsLoaded + " new products in it, " + numProductsPreviouslyLoaded + " were loaded previously.");
			}
			logEntry.saveResults();
		}else{
			logEntry.incErrors("Unable to load products from " + collectionInfo.getName() + " " + mainProductUrl);
			logger.error(productsResponse.getResponseCode() + " " + productsResponse.getMessage());
			errorsWhileLoadingProducts = true;
		}
	}

	private synchronized void setLastSeenForProduct(long startTime, OverDriveRecordInfo curRecord) {
		try {
			updateLastSeenStmt.setLong(1, startTime / 1000);
			updateLastSeenStmt.setString(2, curRecord.getId());
			updateLastSeenStmt.executeUpdate();
		} catch (SQLException e) {
			logEntry.incErrors("Error updating last seen for " + curRecord.getId());
		}
	}

	private synchronized void getExistingRecordInformationForProduct(OverDriveRecordInfo curRecord) {
		try {
			getProductIdByOverDriveIdStmt.setString(1, curRecord.getId());
			try (ResultSet getProductIdByOverDriveIdRS = getProductIdByOverDriveIdStmt.executeQuery()) {
				if (getProductIdByOverDriveIdRS.next()) {
					curRecord.setDatabaseId(getProductIdByOverDriveIdRS.getLong("id"));
				} else {
					curRecord.isNew = true;
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error getting existing DB id for " + curRecord.getId());
		}
	}

	private OverDriveRecordInfo loadOverDriveRecordFromJSON(AdvantageCollectionInfo collectionInfo, JSONObject curProduct) throws JSONException {
		OverDriveRecordInfo curRecord = new OverDriveRecordInfo();
		curRecord.setId(curProduct.getString("id"));
		curRecord.addCollection(collectionInfo);
		return curRecord;
	}

	private void updateOverDriveMetaData(OverDriveRecordInfo overDriveInfo) throws SocketTimeoutException {
		//Check to see if we need to load metadata
		long curTime = new Date().getTime() / 1000;

		//Get the url to call for metadata information (based on the first owning collection)
		AdvantageCollectionInfo collectionInfo = overDriveInfo.getCollections().iterator().next();
		String apiKey = collectionInfo.getCollectionToken();
		String url = "https://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/metadata";
		WebServiceResponse metaDataResponse = callOverDriveURL("overdriveExtract.getProductMetadata", url);
		if (metaDataResponse.getResponseCode() != 200){
			settings.addProductToUpdateNextTime(overDriveInfo.getId());
			logEntry.addNote("Could not load metadata (code " + metaDataResponse.getResponseCode() + ") from " + url );
			logger.info(metaDataResponse.getResponseCode() + ":" + metaDataResponse.getMessage());
		}else{
			saveMetadataToDatabase(overDriveInfo, curTime, metaDataResponse);
		}
	}

	private void saveMetadataToDatabase(OverDriveRecordInfo overDriveInfo, long curTime, WebServiceResponse metaDataResponse) {
		JSONObject metaData = metaDataResponse.getJSONResponse();

		checksumCalculator.reset();
		checksumCalculator.update(metaData.toString().getBytes());
		long metadataChecksum = checksumCalculator.getValue();

		try {
			//Add the product to the database as needed
			String series = "";
			if (metaData.has("series")) {
				series = metaData.getString("series");
			}
			String subtitle = "";
			if (metaData.has("subtitle")) {
				subtitle = metaData.getString("subtitle");
			}
			String primaryCreatorRole = "";
			String primaryCreatorName = "";
			if (metaData.has("creators")){
				JSONArray creators = metaData.getJSONArray("creators");
				if (!creators.isEmpty()) {
					JSONObject primaryCreator = creators.getJSONObject(0);
					primaryCreatorRole = primaryCreator.getString("role");
					if (primaryCreator.has("fileAs")) {
						primaryCreatorName = primaryCreator.getString("fileAs");
					} else {
						primaryCreatorName = primaryCreator.getString("name");
					}
				}
			}
			JSONObject images = metaData.getJSONObject("images");
			String coverUrl = "";
			if (images.has("cover300Wide")){
				coverUrl = images.getJSONObject("cover300Wide").getString("href");
			}else if (images.has("cover150Wide")){
				coverUrl = images.getJSONObject("cover150Wide").getString("href");
			}else if (images.has("cover")){
				coverUrl = images.getJSONObject("cover").getString("href");
			}else if (images.has("thumbnail")){
				coverUrl = images.getJSONObject("thumbnail").getString("href");
			}else {
				logger.debug(overDriveInfo.getId() + " did not have a cover");
			}

			if (overDriveInfo.getDatabaseId() == -1){
				//Add the product to the database
				long databaseId = addProductToDB(
						overDriveInfo.getId(),
						metaData.getLong("crossRefId"),
						metaData.getString("mediaType"),
						metaData.getString("title"),
						subtitle,
						series,
						primaryCreatorRole,
						primaryCreatorName,
						coverUrl
						);
				overDriveInfo.setDatabaseId(databaseId);
			} else {
				//Update raw data for the main title
				updateProductInDB(overDriveInfo.getDatabaseId(),
						overDriveInfo.getId(),
						metaData.getLong("crossRefId"),
						metaData.getString("mediaType"),
						metaData.getString("title"),
						subtitle,
						series,
						primaryCreatorRole,
						primaryCreatorName,
						coverUrl);
			}

			int curCol = 0;
			//Check to see if we have metadata saved already
			getExistingMetadataIdStmt.setLong(1, overDriveInfo.getDatabaseId());
			try (ResultSet getExistingMetadataIdRS = getExistingMetadataIdStmt.executeQuery()) {
				if (getExistingMetadataIdRS.next()) {
					long metadataId = getExistingMetadataIdRS.getLong("id");

					//noinspection DuplicatedCode
					updateMetaDataStmt.setLong(++curCol, metadataChecksum);
					updateMetaDataStmt.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
					updateMetaDataStmt.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
					//Grab the textual version of publishDate rather than the actual date
					if (metaData.has("publishDateText")) {
						String publishDateText = metaData.getString("publishDateText");
						if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")) {
							publishDateText = publishDateText.substring(6, 10);
							updateMetaDataStmt.setLong(++curCol, Long.parseLong(publishDateText));
						} else {
							updateMetaDataStmt.setNull(++curCol, Types.INTEGER);
						}
					} else {
						updateMetaDataStmt.setNull(++curCol, Types.INTEGER);
					}

					updateMetaDataStmt.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
					updateMetaDataStmt.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
					updateMetaDataStmt.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
					updateMetaDataStmt.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
					updateMetaDataStmt.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
					String thumbnail = "";
					String cover = "";
					if (metaData.has("images")) {
						JSONObject imagesData = metaData.getJSONObject("images");
						if (imagesData.has("thumbnail")) {
							thumbnail = imagesData.getJSONObject("thumbnail").getString("href");
						}
						if (imagesData.has("cover")) {
							cover = imagesData.getJSONObject("cover").getString("href");
						}
					}
					updateMetaDataStmt.setString(++curCol, thumbnail);
					updateMetaDataStmt.setString(++curCol, cover);
					updateMetaDataStmt.setBoolean(++curCol, metaData.has("isOwnedByCollections") && metaData.getBoolean("isOwnedByCollections"));
					updateMetaDataStmt.setString(++curCol, metaData.toString(2));
					updateMetaDataStmt.setLong(++curCol, metadataId);

					updateMetaDataStmt.executeUpdate();
				} else {
					addMetadataStmt.setLong(++curCol, overDriveInfo.getDatabaseId());
					//noinspection DuplicatedCode
					addMetadataStmt.setLong(++curCol, metadataChecksum);
					addMetadataStmt.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
					addMetadataStmt.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
					//Grab the textual version of publishDate rather than the actual date
					if (metaData.has("publishDateText")) {
						String publishDateText = metaData.getString("publishDateText");
						if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")) {
							publishDateText = publishDateText.substring(6, 10);
							addMetadataStmt.setLong(++curCol, Long.parseLong(publishDateText));
						} else {
							addMetadataStmt.setNull(++curCol, Types.INTEGER);
						}
					} else {
						addMetadataStmt.setNull(++curCol, Types.INTEGER);
					}

					addMetadataStmt.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
					addMetadataStmt.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
					addMetadataStmt.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
					addMetadataStmt.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
					addMetadataStmt.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
					String thumbnail = "";
					String cover = "";
					if (metaData.has("images")) {
						JSONObject imagesData = metaData.getJSONObject("images");
						if (imagesData.has("thumbnail")) {
							thumbnail = imagesData.getJSONObject("thumbnail").getString("href");
						}
						if (imagesData.has("cover")) {
							cover = imagesData.getJSONObject("cover").getString("href");
						}
					}
					addMetadataStmt.setString(++curCol, thumbnail);
					addMetadataStmt.setString(++curCol, cover);
					addMetadataStmt.setBoolean(++curCol, metaData.has("isOwnedByCollections") && metaData.getBoolean("isOwnedByCollections"));
					addMetadataStmt.setString(++curCol, metaData.toString(2));

					try {
						addMetadataStmt.executeUpdate();
					} catch (SQLIntegrityConstraintViolationException e) {
						//Another thread already created it, since we don't need the ID for additional work,
						// and since the metadata doesn't normally change between collections, we can just ignore this
					}
				}
			}

			clearFormatsStmt.setLong(1, overDriveInfo.getDatabaseId());
			clearFormatsStmt.executeUpdate();
			clearIdentifiersStmt.setLong(1, overDriveInfo.getDatabaseId());
			clearIdentifiersStmt.executeUpdate();
			if (metaData.has("formats")){
				JSONArray formats = metaData.getJSONArray("formats");
				HashSet<String> uniqueIdentifiers = new HashSet<>();
				for (int i = 0; i < formats.length(); i++){
					JSONObject format = formats.getJSONObject(i);
					addFormatStmt.setLong(1, overDriveInfo.getDatabaseId());
					String textFormat = format.getString("id");
					addFormatStmt.setString(2, textFormat);
					//Numeric ids are no longer important in our integration with Libby
					addFormatStmt.setLong(3, 0L);
					addFormatStmt.setString(4, format.getString("name"));
					addFormatStmt.setString(5, format.has("filename") ? format.getString("fileName") : "");
					addFormatStmt.setLong(6, format.has("fileSize") ? format.getLong("fileSize") : 0L);
					addFormatStmt.setLong(7, format.has("partCount") ? format.getLong("partCount") : 0L);

					if (format.has("identifiers")){
						JSONArray identifiers = format.getJSONArray("identifiers");
						for (int j = 0; j < identifiers.length(); j++){
							JSONObject identifier = identifiers.getJSONObject(j);
							if (!identifier.getString("value").isEmpty()) {
								uniqueIdentifiers.add(identifier.getString("type") + ":" + identifier.getString("value"));
							}
						}
					}
					//Default samples to null
					addFormatStmt.setString(8, null);
					addFormatStmt.setString(9, null);
					addFormatStmt.setString(10, null);
					addFormatStmt.setString(11, null);

					if (format.has("samples")){
						JSONArray samples = format.getJSONArray("samples");
						for (int j = 0; j < samples.length(); j++){
							JSONObject sample = samples.getJSONObject(j);
							if (j == 0){
								addFormatStmt.setString(8, sample.getString("source"));
								addFormatStmt.setString(9, sample.getString("url"));
							}else if (j == 1){
								addFormatStmt.setString(10, sample.getString("source"));
								addFormatStmt.setString(11, sample.getString("url"));
							}
						}
					}
					addFormatStmt.executeUpdate();
				}

				for (String curIdentifier : uniqueIdentifiers){
					addIdentifierStmt.setLong(1, overDriveInfo.getDatabaseId());
					String[] identifierInfo = curIdentifier.split(":");
					addIdentifierStmt.setString(2, identifierInfo[0]);
					addIdentifierStmt.setString(3, identifierInfo[1]);
					addIdentifierStmt.executeUpdate();
				}
			}
			logEntry.incMetadataChanges();
		} catch (Exception e) {
			logEntry.incErrors("Error loading meta data for title " + overDriveInfo.getId(), e);
		}

		try {
			updateProductMetadataStmt.setLong(1, curTime);
			updateProductMetadataStmt.setLong(2, curTime);
			updateProductMetadataStmt.setLong(3, overDriveInfo.getDatabaseId());
			updateProductMetadataStmt.executeUpdate();
		} catch (SQLException e) {
			logEntry.incErrors("Error updating product metadata summary " + overDriveInfo.getId(), e);
		}
	}

	private synchronized boolean updateOverDriveAvailability(OverDriveRecordInfo overDriveInfo, long databaseId, boolean singleWork) {
		//Don't need to load availability if we already have availability, and the availability was checked within the last hour
		long curTime = new Date().getTime() / 1000;

		boolean changesMade = false;
		boolean errorsEncountered = false;

		//Get existing availability
		HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities = new HashMap<>();
		try {
			getExistingAvailabilityForProductStmt.setLong(1, databaseId);
			getExistingAvailabilityForProductStmt.setLong(2, settings.getId());

			try (ResultSet existingAvailabilityRS = getExistingAvailabilityForProductStmt.executeQuery()) {
				while (existingAvailabilityRS.next()) {
					OverDriveAvailabilityInfo existingAvailability = new OverDriveAvailabilityInfo();
					existingAvailability.setId(existingAvailabilityRS.getLong("id"));
					existingAvailability.setSettingId(settings.getId());
					existingAvailability.setLibraryId(existingAvailabilityRS.getLong("libraryId"));
					existingAvailability.setAvailable(existingAvailabilityRS.getBoolean("available"));
					existingAvailability.setCopiesOwned(existingAvailabilityRS.getInt("copiesOwned"));
					existingAvailability.setCopiesAvailable(existingAvailabilityRS.getInt("copiesAvailable"));
					existingAvailability.setNumberOfHolds(existingAvailabilityRS.getInt("numberOfHolds"));
					existingAvailability.setAvailabilityType(existingAvailabilityRS.getString("availabilityType"));

					existingAvailabilities.put(existingAvailability.getLibraryId(), existingAvailability);
				}
			}
		}catch (SQLException e){
			logger.warn("Could not load existing availability for Libby product " + databaseId);
		}

		Set<Long> librariesSatisfiedByPrimaryAvailability = ConcurrentHashMap.newKeySet();

		AvailabilityProcessingResult primaryResult = processAvailabilityCollections(overDriveInfo, singleWork, existingAvailabilities, librariesSatisfiedByPrimaryAvailability, true);

		AvailabilityProcessingResult additionalResult = processAvailabilityCollections(overDriveInfo, singleWork, existingAvailabilities, librariesSatisfiedByPrimaryAvailability, false);

		changesMade = primaryResult.changed || additionalResult.changed;
		errorsEncountered = primaryResult.hadErrors || additionalResult.hadErrors;

		//Delete availability for any collections that did not exist
		for (OverDriveAvailabilityInfo existingAvailability: existingAvailabilities.values()){
			if (!existingAvailability.isNewAvailabilityLoaded()){
				try{
					long existingId = existingAvailability.getId();
					deleteAvailabilityStmt.setLong(1, existingId);
					deleteAvailabilityStmt.executeUpdate();
					changesMade = true;
					if (singleWork) {
						logEntry.addNote("Deleting availability for library " + existingAvailability.getLibraryId());
					}
				} catch (SQLException e) {
					errorsEncountered = true;
					logEntry.incErrors("SQL Error deleting availability for title " + overDriveInfo.getId(), e);
				}
			}
		}

		//Update the product to indicate that we checked availability
		if (changesMade){
			try {
				updateProductAvailabilityStmt.setLong(1, curTime);
				updateProductAvailabilityStmt.setLong(2, curTime);
				logEntry.incAvailabilityChanges();
				updateProductAvailabilityStmt.setLong(3, databaseId);
				updateProductAvailabilityStmt.executeUpdate();
			} catch (SQLException e) {
				errorsEncountered = true;
				logEntry.incErrors("Error updating product availability status " + overDriveInfo.getId(), e);
			}
		}
		//If we got here, everything is good
		return errorsEncountered;
	}

	private AvailabilityProcessingResult processAvailabilityCollections(OverDriveRecordInfo overDriveInfo, boolean singleWork, HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities, Set<Long> librariesSatisfiedByPrimaryAvailability, boolean primaryPhase) {
		AvailabilityProcessingResult result = new AvailabilityProcessingResult();

		BlockingQueue<Runnable> blockingQueue = new ArrayBlockingQueue<>(overDriveInfo.getCollections().size());
		ThreadPoolExecutor es = new ThreadPoolExecutor(Math.max(1, overDriveInfo.getCollections().size() / 2), overDriveInfo.getCollections().size(), 5000, TimeUnit.MILLISECONDS, blockingQueue);
		List<Future<AvailabilityProcessingResult>> futures = new ArrayList<>();

		for (AdvantageCollectionInfo collectionInfo : overDriveInfo.getCollections()) {
			Collection<Long> targetLibraries = primaryPhase ? collectionInfo.getAspenLibraryIds() : collectionInfo.getAdditionalAspenLibraryIds();

			if (targetLibraries.isEmpty()) {
				continue;
			}
			futures.add(es.submit(() -> processAvailabilityForCollection(collectionInfo, overDriveInfo, singleWork, existingAvailabilities, librariesSatisfiedByPrimaryAvailability, primaryPhase)));
		}

		es.shutdown();
		while (true) {
			try {
				boolean terminated = es.awaitTermination(15, TimeUnit.SECONDS);
				if (terminated) {
					break;
				}
			} catch (InterruptedException e) {
				logger.error("Error waiting for availability threads to finish", e);
			}
		}

		for (Future<AvailabilityProcessingResult> future : futures) {
			try {
				AvailabilityProcessingResult collectionResult = future.get();
				result.changed = result.changed || collectionResult.changed;
				result.hadErrors = result.hadErrors || collectionResult.hadErrors;
			} catch (Exception e) {
				logEntry.incErrors("Error waiting for availability processing", e);
				result.hadErrors = true;
			}
		}
		return result;
	}

	private AvailabilityProcessingResult processAvailabilityForCollection(AdvantageCollectionInfo collectionInfo, OverDriveRecordInfo overDriveInfo, boolean singleWork, HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities, Set<Long> librariesSatisfiedByPrimaryAvailability,boolean primaryPhase) {
		AvailabilityProcessingResult result = new AvailabilityProcessingResult();
		Collection<Long> targetLibraries = primaryPhase ? collectionInfo.getAspenLibraryIds() : collectionInfo.getAdditionalAspenLibraryIds();
		String apiKey = collectionInfo.getCollectionToken();
		String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
		WebServiceResponse availabilityResponse;
		try {
			availabilityResponse = callOverDriveURL("overdriveExtract.getProductAvailability", url, false);
		} catch (SocketTimeoutException e) {
			settings.addProductToUpdateNextTime(overDriveInfo.getId());
			logEntry.addNote("Error loading availability for " + overDriveInfo.getId() + " " + e.getMessage());
			result.hadErrors = true;
			return result;
		}

		if (availabilityResponse.getResponseCode() == 404) {
			return result;
		} else if (availabilityResponse.getResponseCode() != 200) {
			if (singleWork) {
				logEntry.addNote("Found availability for api key " + apiKey);
			}
			settings.addProductToUpdateNextTime(overDriveInfo.getId());
			logEntry.addNote("Error availability API for product " + overDriveInfo.getId() + " collection " + collectionInfo.getName() + " response code " + availabilityResponse.getResponseCode());
			logger.info(availabilityResponse.getResponseCode() + ":" + availabilityResponse.getMessage());
			result.hadErrors = true;
			return result;
		} else if (availabilityResponse.getMessage() == null) {
			//Delete all availability for this record
			if (singleWork) {
				logEntry.addNote("Availability response had no message " + apiKey + " response code " + availabilityResponse.getResponseCode());
			}
			for (Long aspenLibraryId : targetLibraries) {
				if (!primaryPhase && librariesSatisfiedByPrimaryAvailability.contains(aspenLibraryId)) {
					continue;
				}
				if (existingAvailabilities.containsKey(aspenLibraryId)) {
					try (PreparedStatement deleteAllAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where productId = ? and libraryId = ? and settingId = ?")) {
						deleteAllAvailabilityStmt.setLong(1, overDriveInfo.getDatabaseId());
						deleteAllAvailabilityStmt.setLong(2, aspenLibraryId);
						deleteAllAvailabilityStmt.setLong(3, settings.getId());
						deleteAllAvailabilityStmt.executeUpdate();
						result.changed = true;
						existingAvailabilities.remove(aspenLibraryId);
					} catch (SQLException e) {
						result.hadErrors = true;
						logEntry.incErrors("SQL Error deleting all availability for title " + overDriveInfo.getId(), e);
					}
				}
			}
			return result;
		}
		if (singleWork) {
			logEntry.addNote("Got availability response for collection " + collectionInfo.getName() + " code was " + availabilityResponse.getResponseCode());
			logEntry.addNote(availabilityResponse.getMessage());
		}

		try {
			JSONObject availability = availabilityResponse.getJSONResponse();
			if (!availability.has("errorCode")) {
				for (Long aspenLibraryId : targetLibraries) {
					if (!primaryPhase && librariesSatisfiedByPrimaryAvailability.contains(aspenLibraryId)) {
						continue;
					}
					try {
						boolean libraryChanged = updateAvailabilityForLibrary(aspenLibraryId, existingAvailabilities, availability, singleWork, overDriveInfo.getDatabaseId());
						result.changed = result.changed || libraryChanged;

						if (primaryPhase) {
							librariesSatisfiedByPrimaryAvailability.add(aspenLibraryId);
						}
					} catch (SQLException e) {
						result.hadErrors = true;
						logEntry.incErrors("SQL Error adding availability for title " + overDriveInfo.getId(), e);
					}
				}
			}
		} catch (Exception e) {
			result.hadErrors = true;
			logEntry.incErrors("Error processing availability for title " + overDriveInfo.getId(), e);
		}
		return result;
	}


	private boolean updateAvailabilityForLibrary(long aspenLibraryId, HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities, JSONObject availability, boolean singleWork, long databaseId) throws SQLException {
		boolean changed = false;

		boolean available = false;
		if (availability.has("available")) {
			Object availableObj = availability.get("available");
			if (availableObj instanceof Boolean) {
				available = (Boolean) availableObj;
			} else if (availableObj instanceof String) {
				available = availability.getString("available").equals("true");
			}
		}
		int numCopiesOwned = availability.getInt("copiesOwned");
		int numCopiesAvailable = availability.getInt("copiesAvailable");
		int numberOfHolds = availability.getInt("numberOfHolds");
		String availabilityType = availability.getString("availabilityType");

		OverDriveAvailabilityInfo existingAvailability = existingAvailabilities.get(aspenLibraryId);
		if (existingAvailability != null) {
			if (singleWork) {
				logEntry.addNote("Updating existing availability");
			}
			//Check to see if the availability has changed
			if (available != existingAvailability.isAvailable() ||
				numCopiesOwned != existingAvailability.getCopiesOwned() ||
				numCopiesAvailable != existingAvailability.getCopiesAvailable() ||
				numberOfHolds != existingAvailability.getNumberOfHolds() ||
				!availabilityType.equals(existingAvailability.getAvailabilityType())
			) {
				try (PreparedStatement updateAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_product_availability set available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared =? WHERE id = ?")) {
					updateAvailabilityStmt.setBoolean(1, available);
					updateAvailabilityStmt.setInt(2, numCopiesOwned);
					updateAvailabilityStmt.setInt(3, numCopiesAvailable);
					updateAvailabilityStmt.setInt(4, numberOfHolds);
					updateAvailabilityStmt.setString(5, availabilityType);
					updateAvailabilityStmt.setBoolean(6, false);
					long existingId = existingAvailability.getId();
					updateAvailabilityStmt.setLong(7, existingId);
					updateAvailabilityStmt.executeUpdate();
				}
				changed = true;
			} else if (singleWork) {
				logEntry.addNote("Availability did not change, did not update the database");
			}
			existingAvailability.setNewAvailabilityLoaded();
		} else {
			if (singleWork) {
				logEntry.addNote("Adding availability to the database");
			}
			try (PreparedStatement addAvailabilityStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_availability set productId = ?, settingId = ?, libraryId = ?, available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared = ?")) {
				addAvailabilityStmt.setLong(1, databaseId);
				addAvailabilityStmt.setLong(2, settings.getId());
				addAvailabilityStmt.setLong(3, aspenLibraryId);
				addAvailabilityStmt.setBoolean(4, available);
				addAvailabilityStmt.setInt(5, numCopiesOwned);
				addAvailabilityStmt.setInt(6, numCopiesAvailable);
				addAvailabilityStmt.setInt(7, numberOfHolds);
				addAvailabilityStmt.setString(8, availabilityType);
				addAvailabilityStmt.setBoolean(9, false);
				addAvailabilityStmt.executeUpdate();
			}
			changed = true;
		}
		return changed;
	}

	private WebServiceResponse callOverDriveURL(String requestType, String overdriveUrl, boolean logFailures) throws SocketTimeoutException {
		if (connectToOverDriveAPI()) {
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
			headers.put("User-Agent", "Aspen Discovery");
			int numTries = 0;
			WebServiceResponse response = null;
			int maxTries = Math.max(1, settings.getNumRetriesOnError() + 1);
			while (numTries < maxTries) {
				numTries++;
				//logger.error(numTries + " - " + overdriveUrl);
				response = NetworkUtils.getURL(overdriveUrl, logger, headers, 10000, logFailures);
				logExternalRequest(requestType, overdriveUrl, headers, response.getResponseCode(), response.getMessage());
				if (response.isCallTimedOut() && numTries == maxTries) {
					this.hadTimeoutsFromOverDrive = true;
					try {
						Thread.sleep(30000);
					} catch (InterruptedException e) {
						logger.warn("Timeout waiting to retry call to Libby", e);
					}
				}else{
					//Retry on 404 errors because Libby occasionally returns a 404 for a record that is really there
					// they suggested retrying.
					if (!response.isCallTimedOut() && response.getResponseCode() != 500 && response.getResponseCode() != 404) {
						break;
					}
				}
			}
			if (response.isCallTimedOut() || response.getResponseCode() == 500){
				//If we get a 500 on any call (3 times in a row due to repetition), make sure that we don't process deletes
				// do this because the 500 seems to indicate that the server is down/having issues
				this.errorsWhileLoadingProducts = true;
			}
			return response;
		}else{
			logger.error("Unable to connect to API");
			return new WebServiceResponse(false, -1, "Failed to connect to Libby API");
		}
	}

	private WebServiceResponse callOverDriveURL(String requestType, String overdriveUrl) throws SocketTimeoutException {
		return callOverDriveURL(requestType, overdriveUrl, true);
	}

	private boolean connectToOverDriveAPI() throws SocketTimeoutException {
		//Check to see if we already have a valid token
		if (overDriveAPIToken != null){
			if (overDriveAPIExpiration - new Date().getTime() > 0){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has expired");
			}
		}
		//Connect to the API to get our token
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
			HttpURLConnection conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection) {
				HttpsURLConnection sslConn = (HttpsURLConnection) conn;
				sslConn.setHostnameVerifier((hostname, session) -> {
					//Do not verify host names
					return true;
				});
			}
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			//logger.debug("Client Key is " + clientSecret);
			String encoded = Base64.encodeBase64String((settings.getClientKey() + ":" + settings.getClientSecret()).getBytes());
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
				// Get the response
				try (BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()))) {
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
				}
				JSONObject parser = new JSONObject(response.toString());
				overDriveAPIToken = parser.getString("access_token");
				overDriveAPITokenType = parser.getString("token_type");
				overDriveAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
				conn.disconnect();
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to Libby authentication service. Encoded auth header: " + encoded);
				// Get any errors
				try (BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()))) {
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					logger.debug("  Finished reading response\r\n" + response);
				}
				conn.disconnect();
				return false;
			}
		} catch (SocketTimeoutException toe){
			throw toe;
		} catch (Exception e) {
			logger.error("Error connecting to Libby API", e );
			return false;
		}
		return true;
	}

	private OverDriveRecordGrouper getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new OverDriveRecordGrouper(dbConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	public void close(){
		logger.info("Closing the Libby extractor for setting {}", settings.getId());
		if (recordGroupingProcessorSingleton != null) {
			recordGroupingProcessorSingleton.close();
			recordGroupingProcessorSingleton = null;
		}
		if (groupedWorkIndexer != null) {
			logger.info("Closing Grouped Work Indexer for setting {}", settings.getId());
			groupedWorkIndexer.close();
			groupedWorkIndexer = null;
		}

		libraryAdvantageSettings.clear();

		allProductsInOverDrive.clear();
		allAdvantageCollections.clear();

		closeStatement(addProductStmt);
		closeStatement(getProductIdByOverDriveIdStmt);
		closeStatement(updateProductStmt);
		closeStatement(updateProductChangeTimeStmt);
		closeStatement(deleteProductStmt);
		closeStatement(updateProductMetadataStmt);
		closeStatement(updateMetaDataStmt);
		closeStatement(clearFormatsStmt);
		closeStatement(addFormatStmt);
		closeStatement(clearIdentifiersStmt);
		closeStatement(addIdentifierStmt);
		closeStatement(getExistingAvailabilityForProductStmt);
		closeStatement(deleteAvailabilityStmt);
		closeStatement(updateProductAvailabilityStmt);
		closeStatement(logExternalRequestStmt);
	}

	private void closeStatement(PreparedStatement statement) {
		if (statement != null) {
			try {
				if (!statement.isClosed()) {
					statement.close();
				}
			} catch (SQLException e) {
				logger.error("Error closing statement", e);
			}
		}
	}
	void logExternalRequest(String requestType, String requestUrl, HashMap<String, String> requestHeaders, int responseCode, String response){
		if (settings.isEnableRequestLogging()) {
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
}
