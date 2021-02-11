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
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.zip.CRC32;

import javax.net.ssl.HttpsURLConnection;

import com.turning_leaf_technologies.grouping.OverDriveRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import org.apache.commons.codec.binary.Base64;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

class ExtractOverDriveInfo {
	private static final Logger logger = LogManager.getLogger(ExtractOverDriveInfo.class);
	private OverDriveRecordGrouper recordGroupingProcessorSingleton;
	private String serverName;
	private Connection dbConn;
	private OverDriveExtractLogEntry logEntry;

	private String lastUpdateTimeParam = "";

	//Overdrive API information
	private final OverDriveSetting settings;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private final TreeMap<Long, String> libToOverDriveAPIKeyMap = new TreeMap<>();

	private final HashMap<String, OverDriveRecordInfo> allProductsInOverDrive = new HashMap<>();
	private final ArrayList<AdvantageCollectionInfo> allAdvantageCollections = new ArrayList<>();
	private final HashMap<String, OverDriveDBInfo> existingProductsInAspen = new HashMap<>();

	private PreparedStatement addProductStmt;
	private PreparedStatement getProductIdByOverDriveIdStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement updateProductChangeTimeStmt;
	private PreparedStatement isProductAvailableInOtherSettingsStmt;
	private PreparedStatement deleteProductStmt;
	private PreparedStatement updateProductMetadataStmt;
	private PreparedStatement updateMetaDataStmt;
	private PreparedStatement clearFormatsStmt;
	private PreparedStatement addFormatStmt;
	private PreparedStatement clearIdentifiersStmt;
	private PreparedStatement addIdentifierStmt;
	private PreparedStatement getExistingAvailabilityForProductStmt;
	private PreparedStatement updateAvailabilityStmt;
	private PreparedStatement addAvailabilityStmt;
	private PreparedStatement deleteAvailabilityStmt;
	private PreparedStatement deleteAvailabilityForSettingStmt;
	private PreparedStatement deleteAllAvailabilityStmt;
	private PreparedStatement updateProductAvailabilityStmt;

	private final CRC32 checksumCalculator = new CRC32();
	private boolean errorsWhileLoadingProducts;
	private boolean hadTimeoutsFromOverDrive;
	private GroupedWorkIndexer groupedWorkIndexer;
	private Ini configIni;

	public ExtractOverDriveInfo(OverDriveSetting settings) {
		this.settings = settings;
	}

	int extractOverDriveInfo(Ini configIni, String serverName, Connection dbConn, OverDriveExtractLogEntry logEntry) {
		int numChanges = 0;
		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logEntry = logEntry;

		long extractStartTime = new Date().getTime();

		try {
			initOverDriveExtract(dbConn, logEntry);

			try {
				if (settings.getClientSecret() == null || settings.getClientKey() == null || settings.getAccountId() == null || settings.getClientSecret().length() == 0 || settings.getClientKey().length() == 0 || settings.getAccountId().length() == 0) {
					logEntry.addNote("Did not find correct configuration in settings, not loading overdrive titles");
				} else {
					//Load products from database this lets us know what is new, what has been deleted, and what has been updated
					if (!loadProductsFromDatabase()) {
						return 0;
					}
					logEntry.addNote("The collection currently has " + existingProductsInAspen.size() + " products in it.");

					//Load all products from API to figure out what is actually new, what is deleted, and what needs an update
					//This just gets minimal data, we will load more complete information when we have truly determined
					//What has changed
					if (!loadProductsFromAPI(LOAD_ALL_PRODUCTS)) {
						return 0;
					}
					logger.info("There are a total of " + allProductsInOverDrive.size() + " products in the combined overdrive collections");

					//Remove any records that no longer exist
					//There is currently an issue with OverDrive Search APIs that cause all records to not be returned
					//so we will avoid deleting records if we are deleting more than 500 records or 5% of the collection
					int totalRecordsToDelete = 0;
					for (String overDriveId : existingProductsInAspen.keySet()) {
						OverDriveDBInfo dbInfo = existingProductsInAspen.get(overDriveId);
						if (!dbInfo.isDeleted()) {
							totalRecordsToDelete++;
						}
					}
					int numRecordsDeleted = 0;
					if (!this.hadTimeoutsFromOverDrive && totalRecordsToDelete > 0 && (settings.isAllowLargeDeletes() || (totalRecordsToDelete < 500 && allProductsInOverDrive.size() > 0 && (((float)totalRecordsToDelete / allProductsInOverDrive.size()) < .05)))) {
						for (String overDriveId : existingProductsInAspen.keySet()) {
							OverDriveDBInfo dbInfo = existingProductsInAspen.get(overDriveId);

							//If the record is already deleted, don't bother re-deleting it.
							if (!dbInfo.isDeleted()) {
								deleteProduct(overDriveId, dbInfo);
								numRecordsDeleted++;
							}
							if (numRecordsDeleted % 100 == 0){
								logEntry.saveResults();
							}
						}
					}else if (!settings.isAllowLargeDeletes() && totalRecordsToDelete >= 500) {
						logEntry.incErrors("There were more than 500 records to delete, detected " + totalRecordsToDelete + ", not deleting records");
					}else if (!settings.isAllowLargeDeletes() && (((float)totalRecordsToDelete / allProductsInOverDrive.size()) >= .05)) {
						logEntry.incErrors("More than 5% of the collection was marked as being deleted. Detected " + totalRecordsToDelete + ", not deleting records");
					}
					logger.info("Deleted " + numRecordsDeleted + " records that no longer exist");

					//We now have a list of all products in all collections, but we need to know what needs availability
					//and metadata updated for it.  So we need to call 2 more times to figure out which records have
					//availability and metadata updated
					logger.info("Loading products with metadata changes");
					loadProductsFromAPI(LOAD_PRODUCTS_WITH_METADATA_CHANGES);
					logger.info("Loading products with any changes (to get availability)");
					loadProductsFromAPI(LOAD_PRODUCTS_WITH_ANY_CHANGES);

					//Do some counts of numbers of records that will be updated for logging purposes
					int numRecordsToUpdateMetadata = 0;
					int numRecordsToUpdateAvailability = 0;
					for (OverDriveRecordInfo curRecord : allProductsInOverDrive.values()) {
						//Extract data from overdrive and update the database
						if (curRecord.isNew || curRecord.hasMetadataChanges) {
							numRecordsToUpdateMetadata++;
						}
						if (curRecord.hasAvailabilityChanges) {
							//Load availability for the record
							numRecordsToUpdateAvailability++;
						}
					}
					logger.info("Preparing to update records.  There are " + allProductsInOverDrive.size() + " total records, " + numRecordsToUpdateMetadata + " need metadata updates and " + numRecordsToUpdateAvailability + " need availability updates.");

					//Update, regroup, and reindex records
					for (OverDriveRecordInfo curRecord : allProductsInOverDrive.values()) {
						//Extract data from overdrive and update the database
						if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasMetadataChanges){
							//Load Metadata for the record
							updateOverDriveMetaData(curRecord);
						}
						if (settings.isRunFullUpdate() || curRecord.hasAvailabilityChanges) {
							//Load availability for the record
							updateOverDriveAvailability(curRecord, curRecord.getDatabaseId(), false);
						}

						String groupedWorkId = null;
						if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasMetadataChanges){
							//Regroup the record
							groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(curRecord.getId());
						}
						if (settings.isRunFullUpdate() || curRecord.isNew || curRecord.hasMetadataChanges || curRecord.hasAvailabilityChanges){
							//Metadata didn't change so we need to load from the database
							if (groupedWorkId == null) {
								groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("overdrive", curRecord.getId());
							}
							//Reindex the record
							getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
							numChanges++;
							if (numChanges % 250 == 0) {
								logger.info("Processed " + numChanges);
							}
						}
					}

					//For any records that have been marked to reload, regroup and reindex the records
					processRecordsToReload(logEntry);

					//Finally process any records that seem to be unlinked
					processUnlinkedProducts();

				}
			}catch (SocketTimeoutException toe){
				logger.info("Timeout while loading information from OverDrive, aborting");
				logEntry.addNote("Timeout while loading information from OverDrive, aborting");
				errorsWhileLoadingProducts = true;
			}catch (Exception e){
				logger.error("Error while loading information from OverDrive, aborting");
				logEntry.addNote("Error while loading information from OverDrive, aborting");
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

			//Mark the new last update time if we did not get errors loading products from the database
			if (errorsWhileLoadingProducts || this.logEntry.hasErrors()) {
				logger.warn("Not setting last extract time since there were problems extracting products from the API");
			} else {
				PreparedStatement updateExtractTime;
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (settings.isRunFullUpdate()){
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				updateExtractTime = dbConn.prepareStatement("UPDATE overdrive_settings set " + columnToUpdate + " = ?");
				updateExtractTime.setLong(1, extractStartTime / 1000);
				updateExtractTime.executeUpdate();
				logger.debug("Setting last extract time to " + extractStartTime + " " + new Date(extractStartTime).toString());
			}
		} catch (SQLException e) {
			// handle any errors
			this.logEntry.incErrors("Error initializing overdrive extraction ", e);
		}
		return numChanges;
	}

	private void processUnlinkedProducts() {
		try {
			PreparedStatement getUnlinkedProductsStmt = dbConn.prepareStatement("select id, overdriveId from overdrive_api_products where deleted = 0 and overdriveId in (select identifier from grouped_work_primary_identifiers where type='overdrive' and grouped_work_id not in (select id from grouped_work));", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getUnlinkedProductsRS = getUnlinkedProductsStmt.executeQuery();
			int numUnlinkedProductsProcessed = 0;
			while (getUnlinkedProductsRS.next()) {
				String overDriveId = getUnlinkedProductsRS.getString("overDriveId");
				long aspenId = getUnlinkedProductsRS.getLong("id");
				try {
					overDriveId = overDriveId.toLowerCase();
					OverDriveRecordInfo recordInfo = new OverDriveRecordInfo();
					recordInfo.setId(overDriveId);
					recordInfo.setDatabaseId(aspenId);

					//Call API for the product to figure out what collections the record belongs to
					for (AdvantageCollectionInfo collectionInfo : allAdvantageCollections) {
						//TODO: Do we need to validate this before updating metadata and availability?
						recordInfo.addCollection(collectionInfo);
					}

					//Update the product in the database
					updateOverDriveMetaData(recordInfo);
					updateOverDriveAvailability(recordInfo, recordInfo.getDatabaseId(), false);

					//Reindex
					String groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(recordInfo.getId());
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

					numUnlinkedProductsProcessed++;
				}catch (Exception e) {
					logEntry.incErrors("Error processing unlinked record " + overDriveId, e);
				}
			}
			if (numUnlinkedProductsProcessed > 0) {
				logEntry.addNote("Processed " + numUnlinkedProductsProcessed + " records that were not linked to a grouped work and that were not deleted");
			}
		} catch (SQLException e) {
			logEntry.incErrors("Could not load unlinked products", e);
		}
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
				if (settings.getClientSecret() == null || settings.getClientKey() == null || settings.getAccountId() == null || settings.getClientSecret().length() == 0 || settings.getClientKey().length() == 0 || settings.getAccountId().length() == 0) {
					logEntry.addNote("Did not find correct configuration in settings, not loading overdrive titles");
				} else {
					//Load products from database this lets us know what is new, what has been deleted, and what has been updated
					if (!loadProductsFromDatabase()) {
						return 0;
					}

					singleWorkId = singleWorkId.toLowerCase();
					OverDriveRecordInfo recordInfo = new OverDriveRecordInfo();
					recordInfo.setId(singleWorkId);

					OverDriveDBInfo dbInfo = existingProductsInAspen.get(singleWorkId);
					if (dbInfo != null) {
						recordInfo.setDatabaseId(dbInfo.getDbId());

						if (dbInfo.isDeleted()){
							logger.error("Record " + singleWorkId + " has been marked as deleted in the database");
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
				logger.info("Timeout while loading information from OverDrive, aborting");
				logEntry.addNote("Timeout while loading information from OverDrive, aborting");
				errorsWhileLoadingProducts = true;
			}catch (Exception e){
				logger.error("Error while loading information from OverDrive, aborting");
				logEntry.addNote("Error while loading information from OverDrive, aborting");
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
			this.logEntry.incErrors("Error initializing overdrive extraction ", e);
		}
		return numChanges;
	}

	private void processRecordsToReload(OverDriveExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='overdrive'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");

			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
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
			if (numRecordsToReloadProcessed > 0){
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private void initOverDriveExtract(Connection dbConn, OverDriveExtractLogEntry logEntry) throws SQLException {
		addProductStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_products set id = NULL, overdriveid = ?, crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastMetadataCheck = 0, lastMetadataChange = 0, lastAvailabilityCheck = 0, lastAvailabilityChange = 0 ON DUPLICATE KEY UPDATE id=id", PreparedStatement.RETURN_GENERATED_KEYS);
		getProductIdByOverDriveIdStmt = dbConn.prepareStatement("SELECT id from overdrive_api_products where overdriveid = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, deleted = 0 where id = ?");
		updateProductChangeTimeStmt = dbConn.prepareStatement("UPDATE overdrive_api_products set dateUpdated = ? WHERE overdriveId = ?");
		deleteProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET deleted = 1, dateDeleted = ? where id = ?");
		isProductAvailableInOtherSettingsStmt = dbConn.prepareStatement("SELECT count(*) as availabilityCount from overdrive_api_product_availability where productId = ? and settingId <> ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		updateProductMetadataStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
		updateMetaDataStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, isOwnedByCollections=?, rawData=? " +
				"ON DUPLICATE KEY UPDATE " +
				"checksum = VALUES(checksum), sortTitle = VALUES(sortTitle), publisher = VALUES(publisher), publishDate = VALUES(publishDate), isPublicDomain = VALUES(isPublicDomain), isPublicPerformanceAllowed = VALUES(isPublicPerformanceAllowed), shortDescription = VALUES(shortDescription), fullDescription = VALUES(fullDescription), starRating = VALUES(starRating), popularity = VALUES(popularity), thumbnail=VALUES(thumbnail), cover=VALUES(cover), isOwnedByCollections=VALUES(isOwnedByCollections), rawData=VALUES(rawData)");
		clearFormatsStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_formats where productId = ?");
		addFormatStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_formats set id = NULL, productId = ?, textId = ?, numericId = ?, name = ?, fileName = ?, fileSize = ?, partCount = ?, sampleSource_1 = ?, sampleUrl_1 = ?, sampleSource_2 = ?, sampleUrl_2 = ? ON DUPLICATE KEY update id = id", PreparedStatement.RETURN_GENERATED_KEYS);
		clearIdentifiersStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_identifiers where productId = ?");
		addIdentifierStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_identifiers set productId = ?, type = ?, value = ?");
		getExistingAvailabilityForProductStmt = dbConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ? and settingId = ?");
		updateAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_product_availability set available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared =? WHERE id = ?");
		addAvailabilityStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_availability set productId = ?, settingId = ?, libraryId = ?, available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared = ?");
		deleteAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where id = ?");
		deleteAvailabilityForSettingStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability WHERE productId = ? and settingId = ?");
		deleteAllAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where productId = ? and libraryId = ? and settingId = ?");
		updateProductAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastAvailabilityCheck = ?, lastAvailabilityChange = ? where id = ?");

		if (settings.getProductsKey() == null){
			logEntry.incErrors("No products key was provided for settings " + settings.getId());
		}
		libToOverDriveAPIKeyMap.put(-1L, settings.getProductsKey());

		//Load last extract time regardless of if we are doing full index or partial index
		if (!settings.isRunFullUpdate()) {
			Date lastExtractDate = new Date(settings.getLastUpdateOfChangedRecords() * 1000);
			SimpleDateFormat lastUpdateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
			logger.info("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
			logEntry.addNote("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
			lastUpdateTimeParam = lastUpdateFormat.format(lastExtractDate);
			//Simple Date Format doesn't give us quite the right timezone format so adjust
			lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 2) + ":" + lastUpdateTimeParam.substring(lastUpdateTimeParam.length() - 2);
		}else{
			//Update the settings to mark the full update as not needed
			dbConn.prepareStatement("UPDATE overdrive_settings set runFullUpdate = 0 where id = " + settings.getId()).executeUpdate();
		}

		PreparedStatement advantageCollectionMapStmt = dbConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library INNER JOIN overdrive_scopes on library.overDriveScopeId = overdrive_scopes.id where overdriveAdvantageName != '' and settingId = ?");
		advantageCollectionMapStmt.setLong(1, settings.getId());
		ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
		while (advantageCollectionMapRS.next()){
			libToOverDriveAPIKeyMap.put(advantageCollectionMapRS.getLong(1), advantageCollectionMapRS.getString(3));
		}
	}

	private void deleteProduct(String overDriveId, OverDriveDBInfo overDriveDBInfo) {
		try {
			//Check to be sure the product isn't active from other settings
			isProductAvailableInOtherSettingsStmt.setLong(1, overDriveDBInfo.getDbId());
			isProductAvailableInOtherSettingsStmt.setLong(2, settings.getId());
			boolean isAvailableElsewhere = false;
			ResultSet isProductAvailableInOtherSettingsRS = isProductAvailableInOtherSettingsStmt.executeQuery();
			if (isProductAvailableInOtherSettingsRS.next()){
				int availabilityCount = isProductAvailableInOtherSettingsRS.getInt("availabilityCount");
				if (availabilityCount > 0){
					isAvailableElsewhere = true;
				}
			}

			if (isAvailableElsewhere) {
				//Remove availability within this collection and reindex
				deleteAvailabilityForSettingStmt.setLong(1, overDriveDBInfo.getDbId());
				deleteAvailabilityForSettingStmt.setLong(2, settings.getId());
				deleteAvailabilityForSettingStmt.executeUpdate();
				logEntry.incDeleted();

				String permanentId = getRecordGroupingProcessor().getPermanentIdForRecord("overdrive", overDriveId);
				getGroupedWorkIndexer().processGroupedWork(permanentId);
			}else{
				long curTime = new Date().getTime() / 1000;
				deleteProductStmt.setLong(1, curTime);
				deleteProductStmt.setLong(2, overDriveDBInfo.getDbId());
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
			logEntry.incErrors("Error deleting overdrive product " + overDriveDBInfo.getDbId(), e);
		}
	}

	private void updateProductInDB(long databaseId, String overDriveId, Long crossRefId, String mediaType, String title, String subtitle, String series, String primaryCreatorRole, String primaryCreatorName, String coverUrl)  {
		try {
			//Update the product in the database
			long curTime = new Date().getTime() / 1000;
			int curCol = 0;
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

				logEntry.incUpdated();
			} else {
				logEntry.incSkipped();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error updating overdrive product " + overDriveId, e);
		}
		
	}

	private long addProductToDB(String overDriveId, Long crossRefId, String mediaType, String title, String subtitle, String series, String primaryCreatorRole, String primaryCreatorName, String coverUrl) {
		int curCol = 0;
		long databaseId = -1;
		try {
			long curTime = new Date().getTime() / 1000;
			addProductStmt.setString(++curCol, overDriveId);
			addProductStmt.setLong(++curCol, crossRefId);
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

			ResultSet newIdRS = addProductStmt.getGeneratedKeys();
			if (newIdRS.next()) {
				databaseId = newIdRS.getLong(1);
			}else{
				//get the id of the title in overdrive. This happens when we are adding titles in multiple threads.
				getProductIdByOverDriveIdStmt.setString(1, overDriveId);
				ResultSet getProductIdByOverDriveIdRS = getProductIdByOverDriveIdStmt.executeQuery();
				if (getProductIdByOverDriveIdRS.next()){
					databaseId = getProductIdByOverDriveIdRS.getLong(1);
				}
				getProductIdByOverDriveIdRS.close();
			}

			logEntry.incAdded();

			if (databaseId == -1){
				logEntry.incErrors("A title was not added to the database properly");
			}else {
				OverDriveDBInfo dbInfo = new OverDriveDBInfo();
				dbInfo.setDbId(databaseId);
				dbInfo.setDeleted(false);
				existingProductsInAspen.put(overDriveId, dbInfo);
			}

		} catch (SQLException e) {
			logEntry.incErrors("Error saving product " + overDriveId + " to the database " , e);
		}
		return databaseId;
	}

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	private boolean loadProductsFromDatabase() {
		try {
			PreparedStatement loadProductsStmt = dbConn.prepareStatement("Select overdrive_api_products.* from overdrive_api_products INNER JOIN overdrive_api_product_availability ON overdrive_api_products.id = overdrive_api_product_availability.productId where settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadProductsStmt.setLong(1, settings.getId());
			ResultSet loadProductsRS = loadProductsStmt.executeQuery();
			while (loadProductsRS.next()){
				String overdriveId = loadProductsRS.getString("overdriveId").toLowerCase();
				OverDriveDBInfo curProduct = new OverDriveDBInfo();
				curProduct.setDbId(loadProductsRS.getLong("id"));
				curProduct.setDeleted(loadProductsRS.getLong("deleted") == 1);
				existingProductsInAspen.put(overdriveId, curProduct);
			}
			return true;
		} catch (SQLException e) {
			logEntry.incErrors("Error loading products from database " , e);
			return false;
		}
		
	}

	private final int LOAD_ALL_PRODUCTS = 0;
	private final int LOAD_PRODUCTS_WITH_METADATA_CHANGES = 1;
	private final int LOAD_PRODUCTS_WITH_ANY_CHANGES = 2;

	/**
	 * Get all of the products that are currently in OverDrive so we can determine what needs to be deleted.
	 * We just get minimal information to start, the id and the list of collections that the product is valid for.
	 *
	 * @return boolean whether or not errors occurred
	 * @throws SocketTimeoutException Error if we timeout getting data
	 */
	private boolean loadProductsFromAPI(int loadType) throws SocketTimeoutException {
		WebServiceResponse libraryInfoResponse = callOverDriveURL("https://api.overdrive.com/v1/libraries/" + settings.getAccountId());
		if (libraryInfoResponse.getResponseCode() == 200 && libraryInfoResponse.getMessage() != null){
			JSONObject libraryInfo = libraryInfoResponse.getJSONResponse();
			try {
				String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
				if (mainProductUrl.contains("?")) {
					mainProductUrl += "&minimum=true";
				}else{
					mainProductUrl += "?minimum=true";
				}
				if (loadType == LOAD_PRODUCTS_WITH_METADATA_CHANGES){
					mainProductUrl += "&lastTitleUpdateTime=" + lastUpdateTimeParam;
				}else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES){
					mainProductUrl += "&lastUpdateTime=" + lastUpdateTimeParam;
				}
				AdvantageCollectionInfo mainCollectionInfo = null;
				if (loadType == LOAD_ALL_PRODUCTS) {
					mainCollectionInfo = new AdvantageCollectionInfo();
					mainCollectionInfo.setAdvantageId(-1);
					mainCollectionInfo.setName("Shared OverDrive Collection");
					mainCollectionInfo.setCollectionToken(libraryInfo.getString("collectionToken"));
					mainCollectionInfo.setAspenLibraryId(-1);
					allAdvantageCollections.add(mainCollectionInfo);
				}else{
					for (AdvantageCollectionInfo curCollection : allAdvantageCollections){
						if (curCollection.getAspenLibraryId() == -1){
							mainCollectionInfo = curCollection;
							break;
						}
					}
				}
				loadProductsFromUrl(mainCollectionInfo, mainProductUrl, loadType);
				logEntry.setNumProducts(allProductsInOverDrive.size());
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getJSONResponse();
						if (advantageInfo.has("advantageAccounts")) {
							//Thread loading advantage accounts to improve the speed of loading
							ExecutorService es = Executors.newCachedThreadPool();

							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
								es.execute(() -> {
									try {
										extractProductsForAdvantageAccount(loadType, curAdvantageAccount);
									} catch (SocketTimeoutException e) {
										logEntry.incErrors("Socket timeout loading information from OverDrive API ", e);
										hadTimeoutsFromOverDrive = true;
									}
								});
							}
							es.shutdown();
							while (true) {
								try {
									boolean terminated = es.awaitTermination(15, TimeUnit.SECONDS);
									if (terminated){
										break;
									}
								} catch (InterruptedException e) {
									logger.error("Error waiting for all extracts to finish");
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
				logEntry.incErrors("error loading information from OverDrive API ", e);
				return false;
			}
		}else{
			logEntry.incErrors("Unable to load library information for library " + settings.getAccountId());
			if (libraryInfoResponse.getMessage() != null){
				logEntry.addNote(libraryInfoResponse.getMessage());
			}
			logger.info("Error loading overdrive titles " + libraryInfoResponse.getMessage());
			return false;
		}
	}

	private void extractProductsForAdvantageAccount(int loadType, JSONObject curAdvantageAccount) throws SocketTimeoutException {
		AdvantageCollectionInfo collectionInfo = null;
		if (loadType == LOAD_ALL_PRODUCTS) {
			collectionInfo = new AdvantageCollectionInfo();
			collectionInfo.setAdvantageId(curAdvantageAccount.getInt("id"));
			collectionInfo.setName(curAdvantageAccount.getString("name"));
			collectionInfo.setCollectionToken(curAdvantageAccount.getString("collectionToken"));
			for (Long curLibraryId : libToOverDriveAPIKeyMap.keySet()) {
				String collectionToken = libToOverDriveAPIKeyMap.get(curLibraryId);
				if (collectionToken.equals(collectionInfo.getCollectionToken())) {
					collectionInfo.setAspenLibraryId(curLibraryId);
					break;
				}
			}
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
			//This happens when we are processing individual advantage accounts. It should only happen for collections that OverDrive has designated as Inactive
			if (!curAdvantageAccount.getString("name").contains("Inactive")) {
				logger.error("Did not get collection information for " + curAdvantageAccount.getString("name"));
			}
			processCollection = false;
		}
		if (processCollection) {
			//Need to load products for all advantage libraries since they can be shared with the entire consortium.
			String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
			WebServiceResponse advantageWebServiceResponse = callOverDriveURL(advantageSelfUrl);
			if (advantageWebServiceResponse.getResponseCode() == 200) {
				JSONObject advantageSelfInfo = advantageWebServiceResponse.getJSONResponse();
				if (advantageSelfInfo != null) {
					String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
					if (productUrl.contains("?")) {
						productUrl += "&minimum=true";
					} else {
						productUrl += "?minimum=true";
					}
					if (loadType == LOAD_PRODUCTS_WITH_METADATA_CHANGES) {
						productUrl += "&lastTitleUpdateTime=" + lastUpdateTimeParam;
					} else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES) {
						productUrl += "&lastUpdateTime=" + lastUpdateTimeParam;
					}

					loadProductsFromUrl(collectionInfo, productUrl, loadType);
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
	 * Get all of the products that are currently in OverDrive so we can determine what needs to be deleted.
	 * We just get minimal information to start, the id and the list of collections that the product is valid for.
	 *
	 * @return boolean whether or not errors occurred
	 * @throws SocketTimeoutException Error if we timeout getting data
	 */
	private boolean loadAccountInformationFromAPI() throws SocketTimeoutException {
		WebServiceResponse libraryInfoResponse = callOverDriveURL("https://api.overdrive.com/v1/libraries/" + settings.getAccountId());
		if (libraryInfoResponse.getResponseCode() == 200 && libraryInfoResponse.getMessage() != null){
			JSONObject libraryInfo = libraryInfoResponse.getJSONResponse();
			try {
				AdvantageCollectionInfo mainCollectionInfo = new AdvantageCollectionInfo();
				mainCollectionInfo.setAdvantageId(-1);
				mainCollectionInfo.setName("Shared OverDrive Collection");
				mainCollectionInfo.setCollectionToken(libraryInfo.getString("collectionToken"));
				mainCollectionInfo.setAspenLibraryId(-1);
				allAdvantageCollections.add(mainCollectionInfo);

				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getJSONResponse();
						if (advantageInfo.has("advantageAccounts")) {
							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);

								AdvantageCollectionInfo collectionInfo = new AdvantageCollectionInfo();
								collectionInfo.setAdvantageId(curAdvantageAccount.getInt("id"));
								collectionInfo.setName(curAdvantageAccount.getString("name"));
								collectionInfo.setCollectionToken(curAdvantageAccount.getString("collectionToken"));
								for (Long curLibraryId : libToOverDriveAPIKeyMap.keySet()) {
									String collectionToken = libToOverDriveAPIKeyMap.get(curLibraryId);
									if (collectionToken.equals(collectionInfo.getCollectionToken())) {
										collectionInfo.setAspenLibraryId(curLibraryId);
										break;
									}
								}
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
				logEntry.incErrors("error loading information from OverDrive API ", e);
				return false;
			}
		}else{
			logEntry.incErrors("Unable to load library information for library " + settings.getAccountId());
			if (libraryInfoResponse.getMessage() != null){
				logEntry.addNote(libraryInfoResponse.getMessage());
			}
			logger.info("Error loading overdrive accounts " + libraryInfoResponse.getMessage());
			return false;
		}
	}

	private void loadProductsFromUrl(AdvantageCollectionInfo collectionInfo, String mainProductUrl, int loadType) throws JSONException, SocketTimeoutException {
		WebServiceResponse productsResponse = callOverDriveURL(mainProductUrl);
		if (productsResponse.getResponseCode() == 200) {
			JSONObject productInfo = productsResponse.getJSONResponse();
			if (productInfo == null) {
				return;
			}
			long numProducts = productInfo.getLong("totalItems");
			//if (numProducts > 50) numProducts = 50;
			logger.info(collectionInfo.getName() + " collection has " + numProducts + " products, the libraryId for the collection is " + collectionInfo.getAspenLibraryId());
			if (loadType == LOAD_ALL_PRODUCTS) {
				logEntry.addNote(collectionInfo.getName() + " collection has " + numProducts + " products, the libraryId for the collection is " + collectionInfo.getAspenLibraryId());
			}
			logEntry.saveResults();
			long batchSize = 300;
			for (int i = 0; i < numProducts; i += batchSize) {
				//Just search for the specific product
				String batchUrl = mainProductUrl;
				if (mainProductUrl.contains("?")) {
					batchUrl += "&";
				} else {
					batchUrl += "?";
				}
				logger.debug("Processing " + collectionInfo.getName() + " batch from " + i + " to " + (i + batchSize));
				batchUrl += "offset=" + i + "&limit=" + batchSize;

				for (int tries = 0; tries < 3; tries++){
					WebServiceResponse productBatchInfoResponse = callOverDriveURL(batchUrl);
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
									if (previouslyLoadedProduct == null) {
										//Add to the list of all titles we have found
										allProductsInOverDrive.put(curRecord.getId(), curRecord);
										OverDriveDBInfo existingProductInAspen = existingProductsInAspen.get(curRecord.getId());
										if (existingProductInAspen != null) {
											curRecord.setDatabaseId(existingProductInAspen.getDbId());
											//remove the record now that we have found it
											existingProductsInAspen.remove(curRecord.getId());
										} else {
											curRecord.isNew = true;
										}
									} else {
										previouslyLoadedProduct.addCollection(collectionInfo);
									}
								} else {
									if (previouslyLoadedProduct == null) {
										logger.warn("Found new product loading metadata and availability " + curRecord.getId());
									} else {
										if (loadType == LOAD_PRODUCTS_WITH_METADATA_CHANGES) {
											previouslyLoadedProduct.hasMetadataChanges = true;
										} else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES) {
											previouslyLoadedProduct.hasAvailabilityChanges = true;
										}
									}
								}
							}
							//Get out of the number of tries
							break;
						}else{
							logEntry.incErrors("Batch " + i + " did not have any products in it, but we got back a 200 code");
						}
					} else {
						if (tries == 2) {
							logEntry.incErrors("Could not load product batch: response code " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getMessage());
							logEntry.addNote(batchUrl);
							errorsWhileLoadingProducts = true;
						}else{
							//Give OverDrive a few seconds to sort itself out.
							try {
								Thread.sleep(30000);
							} catch (InterruptedException e) {
								e.printStackTrace();
							}
						}
					}
				}
			}
		}else{
			logEntry.incErrors("Unable to load products from " + collectionInfo.getName() + " " + mainProductUrl);
			logger.error(productsResponse.getResponseCode() + " " + productsResponse.getMessage());
			errorsWhileLoadingProducts = true;
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

		//Get the url to call for meta data information (based on the first owning collection)
		AdvantageCollectionInfo collectionInfo = overDriveInfo.getCollections().iterator().next();
		String apiKey = collectionInfo.getCollectionToken();
		String url = "https://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/metadata";
		WebServiceResponse metaDataResponse = callOverDriveURL(url);
		if (metaDataResponse.getResponseCode() != 200){
			logEntry.incErrors("Could not load metadata (code " + metaDataResponse.getResponseCode() + ") from " + url );
			logger.info(metaDataResponse.getResponseCode() + ":" + metaDataResponse.getMessage());
		}else{
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
					if (creators.length() > 0) {
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
				updateMetaDataStmt.setLong(++curCol, overDriveInfo.getDatabaseId());
				updateMetaDataStmt.setLong(++curCol, metadataChecksum);
				updateMetaDataStmt.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
				updateMetaDataStmt.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
				//Grab the textual version of publish date rather than the actual date
				if (metaData.has("publishDateText")){
					String publishDateText = metaData.getString("publishDateText");
					if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")){
						publishDateText = publishDateText.substring(6, 10);
						updateMetaDataStmt.setLong(++curCol, Long.parseLong(publishDateText));
					}else{
						updateMetaDataStmt.setNull(++curCol, Types.INTEGER);
					}
				}else{
					updateMetaDataStmt.setNull(++curCol, Types.INTEGER);
				}

				updateMetaDataStmt.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
				updateMetaDataStmt.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
				updateMetaDataStmt.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
				updateMetaDataStmt.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
				updateMetaDataStmt.setDouble(++curCol, metaData.has("starRating") ? metaData.getDouble("starRating") : 0);
				updateMetaDataStmt.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
				String thumbnail = "";
				String cover = "";
				if (metaData.has("images")){
					JSONObject imagesData = metaData.getJSONObject("images");
					if (imagesData.has("thumbnail")){
						thumbnail = imagesData.getJSONObject("thumbnail").getString("href");
					}
					if (imagesData.has("cover")){
						cover = imagesData.getJSONObject("cover").getString("href");
					}
				}
				updateMetaDataStmt.setString(++curCol, thumbnail);
				updateMetaDataStmt.setString(++curCol, cover);
				updateMetaDataStmt.setBoolean(++curCol, metaData.has("isOwnedByCollections") && metaData.getBoolean("isOwnedByCollections"));
				updateMetaDataStmt.setString(++curCol, metaData.toString(2));

				updateMetaDataStmt.executeUpdate();

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
						//Numeric ids are no longer important in our integration with OverDrive
						addFormatStmt.setLong(3, 0L);
						addFormatStmt.setString(4, format.getString("name"));
						addFormatStmt.setString(5, format.has("filename") ? format.getString("fileName") : "");
						addFormatStmt.setLong(6, format.has("fileSize") ? format.getLong("fileSize") : 0L);
						addFormatStmt.setLong(7, format.has("partCount") ? format.getLong("partCount") : 0L);

						if (format.has("identifiers")){
							JSONArray identifiers = format.getJSONArray("identifiers");
							for (int j = 0; j < identifiers.length(); j++){
								JSONObject identifier = identifiers.getJSONObject(j);
								if (identifier.getString("value").length() > 0) {
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
	}

	private void updateOverDriveAvailability(OverDriveRecordInfo overDriveInfo, long databaseId, boolean singleWork) throws SocketTimeoutException {
		//Don't need to load availability if we already have availability and the availability was checked within the last hour
		long curTime = new Date().getTime() / 1000;

		boolean changesMade = false;

		//Get existing availability
		HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities = new HashMap<>();
		try {
			getExistingAvailabilityForProductStmt.setLong(1, databaseId);
			getExistingAvailabilityForProductStmt.setLong(2, settings.getId());

			ResultSet existingAvailabilityRS = getExistingAvailabilityForProductStmt.executeQuery();
			while (existingAvailabilityRS.next()){
				OverDriveAvailabilityInfo existingAvailability = new OverDriveAvailabilityInfo();
				existingAvailability.setId(existingAvailabilityRS.getLong("id"));
				existingAvailability.setSettingId(existingAvailabilityRS.getLong("settingId"));
				existingAvailability.setLibraryId(existingAvailabilityRS.getLong("libraryId"));
				existingAvailability.setAvailable(existingAvailabilityRS.getBoolean("available"));
				existingAvailability.setCopiesOwned(existingAvailabilityRS.getInt("copiesOwned"));
				existingAvailability.setCopiesAvailable(existingAvailabilityRS.getInt("copiesAvailable"));
				existingAvailability.setNumberOfHolds(existingAvailabilityRS.getInt("numberOfHolds"));
				existingAvailability.setAvailabilityType(existingAvailabilityRS.getString("availabilityType"));

				existingAvailabilities.put(existingAvailability.getLibraryId(), existingAvailability);
			}
			existingAvailabilityRS.close();
		}catch (SQLException e){
			logger.warn("Could not load existing availability for overdrive product " + databaseId);
		}

		//We need to load availability for every collection because sharing can vary, but we only need to do the shared collection
		//and any of our libraries that have Advantage collections
		for (AdvantageCollectionInfo collectionInfo : overDriveInfo.getCollections()){
			if (collectionInfo.getAspenLibraryId() == 0){
				continue;
			}
			String apiKey = collectionInfo.getCollectionToken();

			String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
			WebServiceResponse availabilityResponse = callOverDriveURL(url, false);

			//404 is a message that availability has been deleted.
			if (availabilityResponse.getResponseCode() != 200 && availabilityResponse.getResponseCode() != 404){
				//We got an error calling the OverDrive API, do nothing.
				if (singleWork) {
					logEntry.addNote("Found availability for api key " + apiKey);
				}
				logEntry.incErrors("Error availability API for product " + overDriveInfo.getId() + " response code " + availabilityResponse.getResponseCode());
				logger.info(availabilityResponse.getResponseCode() + ":" + availabilityResponse.getMessage());
			}else if (availabilityResponse.getMessage() == null){
				//Delete all availability for this record
				if (singleWork) {
					logEntry.addNote("Availability response had no message " + apiKey + " response code " + availabilityResponse.getResponseCode());
				}
				if (existingAvailabilities.containsKey(collectionInfo.getAspenLibraryId())) {
					try {
						deleteAllAvailabilityStmt.setLong(1, overDriveInfo.getDatabaseId());
						deleteAllAvailabilityStmt.setLong(2, collectionInfo.getAspenLibraryId());
						deleteAllAvailabilityStmt.setLong(3, settings.getId());
						deleteAllAvailabilityStmt.executeUpdate();
						changesMade = true;
						existingAvailabilities.remove(collectionInfo.getAspenLibraryId());
					} catch (SQLException e) {
						logEntry.incErrors("SQL Error deleting all availability for title " + overDriveInfo.getId(), e);
					}
				}
			}else {
				if (singleWork) {
					logEntry.addNote("Got availability response for " + collectionInfo.getAspenLibraryId() + " code was " + availabilityResponse.getResponseCode());
					logEntry.addNote(availabilityResponse.getMessage());
				}
				try {
					JSONObject availability = availabilityResponse.getJSONResponse();

					if (!availability.has("errorCode")){
						boolean available = false;
						if (availability.has("available")){
							Object availableObj = availability.get("available");
							if (availableObj instanceof Boolean){
								available = (Boolean)availableObj;
							}else if (availableObj instanceof String){
								available = availability.getString("available").equals("true");
							}
						}

						//Check to see if we have a default account.  There is a case where a library can own a title, but the
						//consortium doesn't.  If the title is shared with the consortium, we need to add availability for the
						//consortium even though OverDrive doesn't provide it.
						JSONArray allAccounts = availability.getJSONArray("accounts");
						int numCopiesOwned = 0;
						int numConsortiumCopies = 0;
						int numSharedCopies = 0;
						int numCopiesAvailable = 0;
						int numConsortiumCopiesAvailable = 0;
						int numSharedCopiesAvailable = 0;
						for (int i = 0; i < allAccounts.length(); i++){
							JSONObject accountData = allAccounts.getJSONObject(i);
							long libraryId = accountData.getLong("id");
							if (libraryId == -1){
								numConsortiumCopies += accountData.getInt("copiesOwned");
								numConsortiumCopiesAvailable += accountData.getInt("copiesAvailable");
							}else if (libraryId == collectionInfo.getAdvantageId()){
								numCopiesOwned += accountData.getInt("copiesOwned");
								numCopiesAvailable += accountData.getInt("copiesAvailable");
							}else{
								if (accountData.has("shared")){
									if (accountData.getBoolean("shared")){
										numSharedCopies += accountData.getInt("copiesOwned");
										numSharedCopiesAvailable += accountData.getInt("copiesAvailable");
									}
								}
							}
						}

						if (singleWork) {
							logEntry.addNote("Updating availability for library " + collectionInfo.getAspenLibraryId());
						}
						//Update availability for this library/collection
						try {
							int numberOfHolds = availability.getInt("numberOfHolds");
							String availabilityType = availability.getString("availabilityType");

							int totalCopiesOwned;
							int totalAvailableCopies;
							if (collectionInfo.getAspenLibraryId() == -1){
								totalCopiesOwned = numConsortiumCopies;
								totalAvailableCopies = numConsortiumCopiesAvailable;
							}else{
								totalCopiesOwned = numCopiesOwned - numConsortiumCopies + numSharedCopies;
								totalAvailableCopies = numCopiesAvailable - numConsortiumCopiesAvailable + numSharedCopiesAvailable;
							}

							OverDriveAvailabilityInfo existingAvailability = existingAvailabilities.get(collectionInfo.getAspenLibraryId());
							if (existingAvailability != null) {
								if (singleWork) {
									logEntry.addNote("Updating existing availability");
								}
								//Check to see if the availability has changed
								if (available != existingAvailability.isAvailable() ||
										totalCopiesOwned != existingAvailability.getCopiesOwned() ||
										totalAvailableCopies != existingAvailability.getCopiesAvailable() ||
										numberOfHolds != existingAvailability.getNumberOfHolds() ||
										!availabilityType.equals(existingAvailability.getAvailabilityType())
								) {
									updateAvailabilityStmt.setBoolean(1, available);
									updateAvailabilityStmt.setInt(2, totalCopiesOwned);
									updateAvailabilityStmt.setInt(3, totalAvailableCopies);
									updateAvailabilityStmt.setInt(4, numberOfHolds);
									updateAvailabilityStmt.setString(5, availabilityType);
									updateAvailabilityStmt.setBoolean(6, false);
									long existingId = existingAvailability.getId();
									updateAvailabilityStmt.setLong(7, existingId);
									updateAvailabilityStmt.executeUpdate();
									changesMade = true;
								}else if (singleWork) {
									logEntry.addNote("Availability did not change, did not update the database");
								}
								existingAvailability.setNewAvailabilityLoaded();
							} else {
								if (singleWork) {
									logEntry.addNote("Adding availability to the database");
								}
								addAvailabilityStmt.setLong(1, databaseId);
								addAvailabilityStmt.setLong(2, settings.getId());
								addAvailabilityStmt.setLong(3, collectionInfo.getAspenLibraryId());
								addAvailabilityStmt.setBoolean(4, available);
								addAvailabilityStmt.setInt(5, totalCopiesOwned);
								addAvailabilityStmt.setInt(6, totalAvailableCopies);
								addAvailabilityStmt.setInt(7, numberOfHolds);
								addAvailabilityStmt.setString(8, availabilityType);
								addAvailabilityStmt.setBoolean(9, false);
								addAvailabilityStmt.executeUpdate();
								changesMade = true;
							}
						} catch (SQLException e) {
							logEntry.incErrors("SQL Error adding availability for title " + overDriveInfo.getId(), e);
						}
					}else{
						if (singleWork) {
							logEntry.addNote("Availability has an error code " + availability.get("errorCode"));
						}
						//We get NotFound when an advantage library owns the title, but they don't share it.
						if (!availability.get("errorCode").equals("NotFound")){
							logger.info("Error loading availability " + availability.get("errorCode") + " " + availability.get("message"));
						}
					}
				} catch (JSONException e) {
					logEntry.incErrors("JSON Error loading availability for title " + overDriveInfo.getId(), e);
				}
			}
		}

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
				logEntry.saveResults();
				updateProductAvailabilityStmt.setLong(3, databaseId);
				updateProductAvailabilityStmt.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error updating product availability status " + overDriveInfo.getId(), e);
			}
		}
	}

	private WebServiceResponse callOverDriveURL(String overdriveUrl, boolean logFailures) throws SocketTimeoutException {
		if (connectToOverDriveAPI()) {
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
			int numTries = 0;
			WebServiceResponse response = null;
			while (numTries < 3) {
				numTries++;
				response = NetworkUtils.getURL(overdriveUrl, logger, headers, 300000, logFailures);
				if (response.isCallTimedOut() && numTries == 3) {
					this.hadTimeoutsFromOverDrive = true;
					try {
						Thread.sleep(30000);
					} catch (InterruptedException e) {
						logger.warn("Timeout waiting to retry call to OverDrive", e);
					}
				}else{
					if (!response.isCallTimedOut() && response.getResponseCode() != 500) {
						break;
					}
				}
			}
			return response;
		}else{
			logger.error("Unable to connect to API");
			return new WebServiceResponse(false, -1, "Failed to connect to OverDrive API");
		}
	}

	private WebServiceResponse callOverDriveURL(String overdriveUrl) throws SocketTimeoutException {
		return callOverDriveURL(overdriveUrl, true);
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
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
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
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				overDriveAPIToken = parser.getString("access_token");
				overDriveAPITokenType = parser.getString("token_type");
				//logger.debug("Token expires in " + parser.getLong("expires_in") + " seconds");
				overDriveAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
				//logger.debug("OverDrive token is " + overDriveAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive authentication service");
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response\r\n" + response);

				rd.close();
				return false;
			}
		} catch (SocketTimeoutException toe){
			throw toe;
		} catch (Exception e) {
			logger.error("Error connecting to overdrive API", e );
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

	void close(){
		logger.info("Closing the overdrive extractor");
		if (recordGroupingProcessorSingleton != null) {
			recordGroupingProcessorSingleton.close();
			recordGroupingProcessorSingleton = null;
		}
		if (groupedWorkIndexer != null) {
			groupedWorkIndexer.close();
			groupedWorkIndexer = null;
		}

		libToOverDriveAPIKeyMap.clear();

		allProductsInOverDrive.clear();
		allAdvantageCollections.clear();
		existingProductsInAspen.clear();

		try {
			addProductStmt.close();
			getProductIdByOverDriveIdStmt.close();
			updateProductStmt.close();
			updateProductChangeTimeStmt.close();
			deleteProductStmt.close();
			updateProductMetadataStmt.close();
			updateMetaDataStmt.close();
			clearFormatsStmt.close();
			addFormatStmt.close();
			clearIdentifiersStmt.close();
			addIdentifierStmt.close();
			getExistingAvailabilityForProductStmt.close();
			updateAvailabilityStmt.close();
			addAvailabilityStmt.close();
			deleteAvailabilityStmt.close();
			deleteAllAvailabilityStmt.close();
			updateProductAvailabilityStmt.close();
		} catch (SQLException e) {
			logger.error("Error closing overdrive extractor", e);
		}
	}
}
