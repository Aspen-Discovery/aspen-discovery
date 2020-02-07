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
	private static Logger logger = LogManager.getLogger(ExtractOverDriveInfo.class);
	private OverDriveRecordGrouper recordGroupingProcessorSingleton;
	private String serverName;
	private Connection dbConn;
	private OverDriveExtractLogEntry results;

	private String lastUpdateTimeParam = "";

	//Overdrive API information
	private String clientSecret;
	private String clientKey;
	private String accountId; //This is not the website id which is used for circulation.
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private TreeMap<Long, String> libToOverDriveAPIKeyMap = new TreeMap<>();
	private HashMap<String, Long> overDriveFormatMap = new HashMap<>();
	
	private HashMap<String, OverDriveRecordInfo> allProductsInOverDrive = new HashMap<>();
	private ArrayList<AdvantageCollectionInfo> allAdvantageCollections = new ArrayList<>();
	private HashMap<String, OverDriveDBInfo> existingProductsInAspen = new HashMap<>();

	private PreparedStatement addProductStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement updateProductChangeTimeStmt;
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
	private PreparedStatement deleteAllAvailabilityStmt;
	private PreparedStatement updateProductAvailabilityStmt;

	private CRC32 checksumCalculator = new CRC32();
	private boolean errorsWhileLoadingProducts;
	private boolean hadTimeoutsFromOverDrive;
	private GroupedWorkIndexer groupedWorkIndexer;
	private Ini configIni;

	int extractOverDriveInfo(Ini configIni, String serverName, Connection dbConn, OverDriveExtractLogEntry logEntry) {
		int numChanges = 0;
		this.configIni = configIni;
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.results = logEntry;

		long extractStartTime = new Date().getTime();

		try {
			boolean runFullUpdate = initOverDriveExtract(dbConn, logEntry);

			try {
				if (clientSecret == null || clientKey == null || accountId == null || clientSecret.length() == 0 || clientKey.length() == 0 || accountId.length() == 0) {
					logEntry.addNote("Did not find correct configuration in config.ini, not loading overdrive titles");
				} else {
					//Load products from database this lets us know what is new, what has been deleted, and what has been updated
					if (!loadProductsFromDatabase()) {
						return 0;
					}

					//Load all products from API to figure out what is actually new, what is deleted, and what needs an update
					//This just gets minimal data, we will load more complete information when we have truly determined
					//What has changed
					if (!loadProductsFromAPI(LOAD_ALL_PRODUCTS)) {
						return 0;
					}
					logger.info("There are a total of " + allProductsInOverDrive.size() + " products in the combined overdrive collections");

					//Remove any records that no longer exist
					int numRecordsDeleted = 0;
					if (!this.hadTimeoutsFromOverDrive) {
						for (String overDriveId : existingProductsInAspen.keySet()) {
							OverDriveDBInfo dbInfo = existingProductsInAspen.get(overDriveId);
							//If the record is already deleted, don't bother re-deleting it.
							if (!dbInfo.isDeleted()) {
								RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("overdrive", overDriveId);
								deleteProductInDB(dbInfo);
								if (result.reindexWork){
									getGroupedWorkIndexer().processGroupedWork(result.permanentId);
								}else if (result.deleteWork){
									//Delete the work from solr and the database
									getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
								}
								numRecordsDeleted++;
							}
						}
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
						if (runFullUpdate || curRecord.isNew || curRecord.hasMetadataChanges){
							//Load Metadata for the record
							updateOverDriveMetaData(curRecord);
						}
						if (runFullUpdate || curRecord.hasAvailabilityChanges) {
							//Load availability for the record
							updateOverDriveAvailability(curRecord, curRecord.getDatabaseId());
						}

						String groupedWorkId = null;
						if (runFullUpdate || curRecord.isNew || curRecord.hasMetadataChanges){
							//Regroup the record
							groupedWorkId = getRecordGroupingProcessor().processOverDriveRecord(curRecord.getId());
						}
						if (runFullUpdate || curRecord.isNew || curRecord.hasMetadataChanges || curRecord.hasAvailabilityChanges){
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

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				recordGroupingProcessorSingleton = null;
				groupedWorkIndexer = null;
			}

			//Mark the new last update time if we did not get errors loading products from the database
			if (errorsWhileLoadingProducts || results.hasErrors()) {
				logger.warn("Not setting last extract time since there were problems extracting products from the API");
			} else {
				PreparedStatement updateExtractTime;
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (runFullUpdate){
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				updateExtractTime = dbConn.prepareStatement("UPDATE overdrive_settings set " + columnToUpdate + " = ?");
				updateExtractTime.setLong(1, extractStartTime / 1000);
				updateExtractTime.executeUpdate();
				logger.debug("Setting last extract time to " + extractStartTime + " " + new Date(extractStartTime).toString());
			}
		} catch (SQLException e) {
		// handle any errors
			logger.error("Error initializing overdrive extraction", e);
			results.addNote("Error initializing overdrive extraction " + e.toString());
			results.incErrors();
			results.saveResults();
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
			logEntry.incErrors();
			logEntry.addNote("Error processing records to reload " + e.toString());
		}
	}

	private boolean initOverDriveExtract(Connection dbConn, OverDriveExtractLogEntry logEntry) throws SQLException {
		addProductStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_products set overdriveid = ?, crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastMetadataCheck = 0, lastMetadataChange = 0, lastAvailabilityCheck = 0, lastAvailabilityChange = 0", PreparedStatement.RETURN_GENERATED_KEYS);
		updateProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, deleted = 0 where id = ?");
		updateProductChangeTimeStmt = dbConn.prepareStatement("UPDATE overdrive_api_products set dateUpdated = ? WHERE overdriveId = ?");
		deleteProductStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET deleted = 1, dateDeleted = ? where id = ?");
		updateProductMetadataStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
		updateMetaDataStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, isOwnedByCollections=?, rawData=? " +
				"ON DUPLICATE KEY UPDATE " +
				"checksum = VALUES(checksum), sortTitle = VALUES(sortTitle), publisher = VALUES(publisher), publishDate = VALUES(publishDate), isPublicDomain = VALUES(isPublicDomain), isPublicPerformanceAllowed = VALUES(isPublicPerformanceAllowed), shortDescription = VALUES(shortDescription), fullDescription = VALUES(fullDescription), starRating = VALUES(starRating), popularity = VALUES(popularity), thumbnail=VALUES(thumbnail), cover=VALUES(cover), isOwnedByCollections=VALUES(isOwnedByCollections), rawData=VALUES(rawData)");
		clearFormatsStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_formats where productId = ?");
		addFormatStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_formats set productId = ?, textId = ?, numericId = ?, name = ?, fileName = ?, fileSize = ?, partCount = ?, sampleSource_1 = ?, sampleUrl_1 = ?, sampleSource_2 = ?, sampleUrl_2 = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		clearIdentifiersStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_identifiers where productId = ?");
		addIdentifierStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_identifiers set productId = ?, type = ?, value = ?");
		getExistingAvailabilityForProductStmt = dbConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ?");
		updateAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_product_availability set available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared =? WHERE id = ?");
		addAvailabilityStmt = dbConn.prepareStatement("INSERT INTO overdrive_api_product_availability set productId = ?, libraryId = ?, available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?, shared = ?");
		deleteAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where id = ?");
		deleteAllAvailabilityStmt = dbConn.prepareStatement("DELETE FROM overdrive_api_product_availability where productId = ?");
		updateProductAvailabilityStmt = dbConn.prepareStatement("UPDATE overdrive_api_products SET lastAvailabilityCheck = ?, lastAvailabilityChange = ? where id = ?");

		//Load settings
		PreparedStatement overDriveSettingsStmt = dbConn.prepareStatement("SELECT * from overdrive_settings");
		ResultSet overDriveSettingsRS = overDriveSettingsStmt.executeQuery();

		boolean runFullUpdate = false;
		if (overDriveSettingsRS.next()){
			clientSecret = overDriveSettingsRS.getString("clientSecret");
			clientKey = overDriveSettingsRS.getString("clientKey");
			accountId = overDriveSettingsRS.getString("accountId");

			String overDriveProductsKey = overDriveSettingsRS.getString("productsKey");
			if (overDriveProductsKey == null){
				logger.error("No products key provided for OverDrive");
				System.exit(1);
			}
			libToOverDriveAPIKeyMap.put(-1L, overDriveProductsKey);
			runFullUpdate = overDriveSettingsRS.getBoolean("runFullUpdate");

			//Load last extract time regardless of if we are doing full index or partial index
			long lastExtractTime = overDriveSettingsRS.getLong("lastUpdateOfChangedRecords");
			if (!runFullUpdate) {
				Date lastExtractDate = new Date(lastExtractTime * 1000);
				SimpleDateFormat lastUpdateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
				logger.info("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
				logEntry.addNote("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
				lastUpdateTimeParam = lastUpdateFormat.format(lastExtractDate);
				//Simple Date Format doesn't give us quite the right timezone format so adjust
				lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 2) + ":" + lastUpdateTimeParam.substring(lastUpdateTimeParam.length() - 2);
			}else{
				//Update the settings to mark the full update as not needed
				dbConn.prepareStatement("UPDATE overdrive_settings set runFullUpdate = 0").executeUpdate();
			}
		}else{
			logger.error("No configuration found in the database for OverDrive");
			System.exit(1);
		}

		PreparedStatement advantageCollectionMapStmt = dbConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library INNER JOIN overdrive_scopes on library.overDriveScopeId = overdrive_scopes.id where overdriveAdvantageName != ''");
		ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
		while (advantageCollectionMapRS.next()){
			libToOverDriveAPIKeyMap.put(advantageCollectionMapRS.getLong(1), advantageCollectionMapRS.getString(3));
		}

		setupOverDriveFormatMap();

		return runFullUpdate;
	}

	private void setupOverDriveFormatMap() {
		overDriveFormatMap.put("ebook-epub-adobe", 410L);
		overDriveFormatMap.put("ebook-kindle", 420L);
		overDriveFormatMap.put("Microsoft eBook", 1L);
		overDriveFormatMap.put("audiobook-wma", 25L);
		overDriveFormatMap.put("audiobook-mp3", 425L);
		overDriveFormatMap.put("audiobook-overdrive", 625L);
		overDriveFormatMap.put("music-wma", 30L);
		overDriveFormatMap.put("video-wmv", 35L);
		overDriveFormatMap.put("ebook-pdf-adobe", 50L);
		overDriveFormatMap.put("Palm", 150L);
		overDriveFormatMap.put("Mobipocket eBook", 90L);
		overDriveFormatMap.put("Disney Online Book", 302L);
		overDriveFormatMap.put("ebook-pdf-open", 450L);
		overDriveFormatMap.put("ebook-epub-open", 810L);
		overDriveFormatMap.put("ebook-overdrive", 610L);
		overDriveFormatMap.put("video-streaming", 635L);
		overDriveFormatMap.put("periodicals-nook", 304L);
		overDriveFormatMap.put("ebook-mediado", 303L);
	}

	private void deleteProductInDB(OverDriveDBInfo overDriveDBInfo) {
		try {
			long curTime = new Date().getTime() / 1000;
			deleteProductStmt.setLong(1, curTime);
			deleteProductStmt.setLong(2, overDriveDBInfo.getDbId());
			deleteProductStmt.executeUpdate();
			results.incDeleted();
		} catch (SQLException e) {
			logger.info("Error deleting overdrive product " + overDriveDBInfo.getDbId(), e);
			results.addNote("Error deleting overdrive product " + overDriveDBInfo.getDbId() + e.toString());
			results.incErrors();
			results.saveResults();
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

				results.incUpdated();
			} else {
				results.incSkipped();
			}
		} catch (SQLException e) {
			logger.info("Error updating overdrive product " + overDriveId, e);
			results.addNote("Error updating overdrive product " + overDriveId + e.toString());
			results.incErrors();
			results.saveResults();
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
			newIdRS.next();
			databaseId = newIdRS.getLong(1);

			results.incAdded();

			OverDriveDBInfo dbInfo = new OverDriveDBInfo();
			dbInfo.setDbId(databaseId);
			dbInfo.setDeleted(false);
			existingProductsInAspen.put(overDriveId, dbInfo);

		} catch (SQLException e) {
			logger.warn("Error saving product " + overDriveId + " to the database", e);
			results.addNote("Error saving product " + overDriveId + " to the database " + e.toString());
			results.incErrors();
			results.saveResults();
		}
		return databaseId;
	}

	private boolean loadProductsFromDatabase() {
		try {
			PreparedStatement loadProductsStmt = dbConn.prepareStatement("Select * from overdrive_api_products");
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
			logger.warn("Error loading products from database", e);
			results.addNote("Error loading products from database " + e.toString());
			results.incErrors();
			results.saveResults();
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
		WebServiceResponse libraryInfoResponse = callOverDriveURL("https://api.overdrive.com/v1/libraries/" + accountId);
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
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getJSONResponse();
						if (advantageInfo.has("advantageAccounts")) {
							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);

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
									allAdvantageCollections.add(collectionInfo);
								}else{
									int collectionId = curAdvantageAccount.getInt("id");
									for (AdvantageCollectionInfo curCollectionInfo : allAdvantageCollections){
										if (curCollectionInfo.getAdvantageId() == collectionId){
											collectionInfo = curCollectionInfo;
											break;
										}
									}
								}

								if (collectionInfo == null){
									logger.error("Did not get collection information");
									continue;
								}
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
									results.addNote("Unable to load advantage information for " + advantageSelfUrl);
									if (advantageWebServiceResponse.getMessage() != null) {
										results.addNote(advantageWebServiceResponse.getMessage());
									}
								}
							}
						}
					} else {
						results.addNote("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
						if (webServiceResponse.getMessage() != null) {
							results.addNote(webServiceResponse.getMessage());
						}
						results.incErrors();
					}
				}
				results.setNumProducts(allProductsInOverDrive.size());
				return true;
			} catch (SocketTimeoutException toe){
				throw toe;
			} catch (Exception e) {
				results.addNote("error loading information from OverDrive API " + e.toString());
				results.incErrors();
				logger.info("Error loading overdrive titles", e);
				return false;
			}
		}else{
			results.addNote("Unable to load library information for library " + accountId);
			if (libraryInfoResponse.getMessage() != null){
				results.addNote(libraryInfoResponse.getMessage());
			}
			results.incErrors();
			logger.info("Error loading overdrive titles " + libraryInfoResponse.getMessage());
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
				results.addNote(collectionInfo.getName() + " collection has " + numProducts + " products, the libraryId for the collection is " + collectionInfo.getAspenLibraryId());
			}
			results.saveResults();
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

				WebServiceResponse productBatchInfoResponse = callOverDriveURL(batchUrl);
				if (productBatchInfoResponse.getResponseCode() == 200){
					JSONObject productBatchInfo = productBatchInfoResponse.getJSONResponse();
					if (productBatchInfo != null && productBatchInfo.has("products")) {
						numProducts = productBatchInfo.getLong("totalItems");
						JSONArray products = productBatchInfo.getJSONArray("products");
						logger.debug(" Found " + products.length() + " products");
						for (int j = 0; j < products.length(); j++) {
							JSONObject curProduct = products.getJSONObject(j);
							//Update the main data in the database and
							OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(collectionInfo, curProduct);
							if (curRecord != null) {
								OverDriveRecordInfo previouslyLoadedProduct = allProductsInOverDrive.get(curRecord.getId());
								if (loadType == LOAD_ALL_PRODUCTS){
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
									}else {
										if (loadType == LOAD_PRODUCTS_WITH_METADATA_CHANGES) {
											previouslyLoadedProduct.hasMetadataChanges = true;
										} else if (loadType == LOAD_PRODUCTS_WITH_ANY_CHANGES) {
											previouslyLoadedProduct.hasAvailabilityChanges = true;
										}
									}
								}
							}else{
								//Could not parse the record make sure we log that there was an error
								errorsWhileLoadingProducts = true;
								results.incErrors();
							}
						}
					}
				}else{
					logger.info("Could not load product batch " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getMessage());
					results.addNote("Could not load product batch " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getMessage());
					errorsWhileLoadingProducts = true;
					results.incErrors();
				}

			}
		}else{
			logger.error("Unable to load products from " + collectionInfo.getName() + " " + mainProductUrl);
			results.addNote("Unable to load products from " + collectionInfo.getName() + " " + mainProductUrl);
			results.incErrors();
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
			logger.info("Could not load metadata from " + url );
			logger.info(metaDataResponse.getResponseCode() + ":" + metaDataResponse.getMessage());
			results.addNote("Could not load metadata from " + url );
			results.incErrors();
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
						Long numericFormat = overDriveFormatMap.get(textFormat);
						if (numericFormat == null){
							logger.warn("Could not find numeric format for format " + textFormat);
							results.addNote("Could not find numeric format for format " + textFormat);
//							results.incErrors();
//							System.out.println("Warning: new format for OverDrive found " + textFormat);
//							continue;
							addFormatStmt.setLong(3, 0L);
						}else {
							addFormatStmt.setLong(3, numericFormat);
						}
						addFormatStmt.setString(4, format.getString("name"));
						addFormatStmt.setString(5, format.has("filename") ? format.getString("fileName") : "");
						addFormatStmt.setLong(6, format.has("fileSize") ? format.getLong("fileSize") : 0L);
						addFormatStmt.setLong(7, format.has("partCount") ? format.getLong("partCount") : 0L);

						if (format.has("identifiers")){
							JSONArray identifiers = format.getJSONArray("identifiers");
							for (int j = 0; j < identifiers.length(); j++){
								JSONObject identifier = identifiers.getJSONObject(j);
								uniqueIdentifiers.add(identifier.getString("type") + ":" + identifier.getString("value"));
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
				results.incMetadataChanges();
			} catch (Exception e) {
				logger.info("Error loading meta data for title " + overDriveInfo.getId() , e);
				results.addNote("Error loading meta data for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}

			try {
				updateProductMetadataStmt.setLong(1, curTime);
				updateProductMetadataStmt.setLong(2, curTime);
				updateProductMetadataStmt.setLong(3, overDriveInfo.getDatabaseId());
				updateProductMetadataStmt.executeUpdate();
			} catch (SQLException e) {
				logger.warn("Error updating product metadata summary ", e);
				results.addNote("Error updating product metadata summary " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
	}

	private void updateOverDriveAvailability(OverDriveRecordInfo overDriveInfo, long databaseId) throws SocketTimeoutException {
		//Don't need to load availability if we already have availability and the availability was checked within the last hour
		long curTime = new Date().getTime() / 1000;

		//OverDrive now returns availability for all records in one API call so we minimize the number of API calls we need to make
		//by only calling once and then processing all the accounts within the response
		AdvantageCollectionInfo firstCollection = overDriveInfo.getCollections().iterator().next();
		String apiKey = firstCollection.getCollectionToken();

		String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
		WebServiceResponse availabilityResponse = callOverDriveURL(url);
		//404 is a message that availability has been deleted.
		if (availabilityResponse.getResponseCode() != 200 && availabilityResponse.getResponseCode() != 404){
			//We got an error calling the OverDrive API, do nothing.
			logger.info("Error loading availability for product " + overDriveInfo.getId());
			logger.info(availabilityResponse.getResponseCode() + ":" + availabilityResponse.getMessage());
			results.addNote("Error availability API for product " + overDriveInfo.getId());
			results.incErrors();
		}else if (availabilityResponse.getMessage() == null){
			//Delete all availability for this record
			try{
				deleteAllAvailabilityStmt.setLong(1, overDriveInfo.getDatabaseId());
				deleteAllAvailabilityStmt.executeUpdate();
			} catch (SQLException e) {
				logger.info("SQL Error deleting all availability for title " + overDriveInfo.getId() , e);
				results.addNote("SQL Error deleting all availability for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}else {
			try {
				JSONObject availability = availabilityResponse.getJSONResponse();
				boolean available = availability.has("available") && availability.getString("available").equals("true");

				//Get existing availability
				HashMap<Long, OverDriveAvailabilityInfo> existingAvailabilities = new HashMap<>();
				try {
					getExistingAvailabilityForProductStmt.setLong(1, databaseId);

					ResultSet existingAvailabilityRS = getExistingAvailabilityForProductStmt.executeQuery();
					while (existingAvailabilityRS.next()){
						OverDriveAvailabilityInfo existingAvailability = new OverDriveAvailabilityInfo();
						existingAvailability.setId(existingAvailabilityRS.getLong("id"));
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

				if (!availability.has("errorCode")){
					//Check to see if we have a default account.  There is a case where a library can own a title, but the
					//consortium doesn't.  If the title is shared with the consortium, we need to add availability for the
					//consortium even though OverDrive doesn't provide it.
					JSONArray allAccounts = availability.getJSONArray("accounts");
					boolean hasDefaultAccount = false;
					boolean hasSharedTitle = false;
					for (int i = 0; i < allAccounts.length(); i++){
						JSONObject accountData = allAccounts.getJSONObject(i);
						long libraryId = accountData.getLong("id");
						if (libraryId == -1){
							hasDefaultAccount = true;
						}
						if (accountData.has("shared")){
							if (accountData.getBoolean("shared")){
								hasSharedTitle = true;
							}
						}
					}
					//Force updating the shared account as needed.
					if (!hasDefaultAccount && hasSharedTitle){
						JSONObject defaultAccount = new JSONObject();
						defaultAccount.put("id", -1L);
						defaultAccount.put("copiesAvailable", availability.getInt("copiesAvailable"));
						defaultAccount.put("copiesOwned", availability.getInt("copiesOwned"));
						allAccounts.put(defaultAccount);
					}

					for (int i = 0; i < allAccounts.length(); i++){
						//Update data for this account
						JSONObject accountData = allAccounts.getJSONObject(i);
						long libraryId = accountData.getLong("id");
						//Get the library this relates to
						AdvantageCollectionInfo activeCollection = null;
						for (AdvantageCollectionInfo curCollection: allAdvantageCollections) {
							if (curCollection.getAdvantageId() == libraryId) {
								activeCollection = curCollection;
								break;
							}
						}

						if (activeCollection == null) {
							logger.warn("Did not find a collection for id " + libraryId);
						}else {
							if (activeCollection.getAspenLibraryId() == 0){
								//This is an overdrive collection that we don't have in Aspen, just skip it.
								continue;
							}
							//Update availability for this library/collection
							try {
								int copiesOwned = accountData.getInt("copiesOwned");
								int copiesAvailable;
								if (accountData.has("copiesAvailable")) {
									copiesAvailable = accountData.getInt("copiesAvailable");
								} else {
									logger.info("copiesAvailable was not provided for collection " + apiKey + " title " + overDriveInfo.getId());
									copiesAvailable = 0;
								}

								boolean shared = false;
								if (accountData.has("shared")){
									shared = accountData.getBoolean("shared");
								}
								int numberOfHolds;
								if (activeCollection.getAdvantageId() == -1) {
									numberOfHolds = availability.getInt("numberOfHolds");
									//Since the shared collection can have additional copies owned from overdrive advantage sharing
									//we get the total count from the combined data and then we can back it out advantage copies later for
									//clarity of the display.
									copiesOwned = availability.getInt("copiesOwned");
									copiesAvailable = availability.getInt("copiesAvailable");
								}else{
									numberOfHolds = 0;
								}
								String availabilityType = availability.getString("availabilityType");

								OverDriveAvailabilityInfo existingAvailability = existingAvailabilities.get(activeCollection.getAspenLibraryId());
								if (existingAvailability != null) {
									//Check to see if the availability has changed
									if (available != existingAvailability.isAvailable() ||
											copiesOwned != existingAvailability.getCopiesOwned() ||
											copiesAvailable != existingAvailability.getCopiesAvailable() ||
											numberOfHolds != existingAvailability.getNumberOfHolds() ||
											!availabilityType.equals(existingAvailability.getAvailabilityType())
									) {
										updateAvailabilityStmt.setBoolean(1, available);
										updateAvailabilityStmt.setInt(2, copiesOwned);
										updateAvailabilityStmt.setInt(3, copiesAvailable);
										updateAvailabilityStmt.setInt(4, numberOfHolds);
										updateAvailabilityStmt.setString(5, availabilityType);
										updateAvailabilityStmt.setBoolean(6, shared);
										long existingId = existingAvailability.getId();
										updateAvailabilityStmt.setLong(7, existingId);
										updateAvailabilityStmt.executeUpdate();
									}
									existingAvailability.setNewAvailabilityLoaded();
								} else {
									addAvailabilityStmt.setLong(1, databaseId);
									addAvailabilityStmt.setLong(2, activeCollection.getAspenLibraryId());
									addAvailabilityStmt.setBoolean(3, available);
									addAvailabilityStmt.setInt(4, copiesOwned);
									addAvailabilityStmt.setInt(5, copiesAvailable);
									addAvailabilityStmt.setInt(6, numberOfHolds);
									addAvailabilityStmt.setString(7, availabilityType);
									addAvailabilityStmt.setBoolean(8, shared);
									addAvailabilityStmt.executeUpdate();
								}
							} catch (SQLException e) {
								logger.info("SQL Error loading availability for title ", e);
								results.addNote("SQL Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
								results.incErrors();
							}
						}
					}
				}else{
					//We get NotFound when an advantage library owns the title, but they don't share it.
					if (!availability.get("errorCode").equals("NotFound")){
						logger.info("Error loading availability " + availability.get("errorCode") + " " + availability.get("message"));
					}
				}

				//Delete availability for any collections that did not exist
				for (OverDriveAvailabilityInfo existingAvailability: existingAvailabilities.values()){
					if (!existingAvailability.isNewAvailabilityLoaded()){
						try{
							long existingId = existingAvailability.getId();
							deleteAvailabilityStmt.setLong(1, existingId);
							deleteAvailabilityStmt.executeUpdate();
						} catch (SQLException e) {
							logger.info("SQL Error loading availability for title ", e);
							results.addNote("SQL Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
							results.incErrors();
						}
					}
				}
			} catch (JSONException e) {
				logger.info("JSON Error loading availability for title ", e);
				results.addNote("JSON Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
		//Update the product to indicate that we checked availability
		try {
			updateProductAvailabilityStmt.setLong(1, curTime);
			updateProductAvailabilityStmt.setLong(2, curTime);
			results.incAvailabilityChanges();
			results.saveResults();
			updateProductAvailabilityStmt.setLong(3, databaseId);
			updateProductAvailabilityStmt.executeUpdate();
		} catch (SQLException e) {
			logger.warn("Error updating product availability status ", e);
			results.addNote("Error updating product availability status " + overDriveInfo.getId() + " " + e.toString());
			results.incErrors();
		}
	}

	private WebServiceResponse callOverDriveURL(String overdriveUrl) throws SocketTimeoutException {
		if (connectToOverDriveAPI()) {
			HashMap<String, String> headers = new HashMap<>();
			headers.put("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
			WebServiceResponse response = NetworkUtils.getURL(overdriveUrl, logger, headers);
			if (response.isCallTimedOut()) {
				this.hadTimeoutsFromOverDrive = true;
			}
			return response;
		}else{
			logger.error("Unable to connect to API");
			return new WebServiceResponse(false, -1, "Failed to connect to OverDrive API");
		}
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
			String encoded = Base64.encodeBase64String((clientKey + ":" + clientSecret).getBytes());
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
			recordGroupingProcessorSingleton = new OverDriveRecordGrouper(dbConn, serverName, logger, false);
		}
		return recordGroupingProcessorSingleton;
	}

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, false, logger);
		}
		return groupedWorkIndexer;
	}

	void close(){
		//TODO: Should these have an explicit close to cleanup resources?
		recordGroupingProcessorSingleton = null;
		groupedWorkIndexer = null;

		libToOverDriveAPIKeyMap.clear();
		overDriveFormatMap.clear();

		allProductsInOverDrive.clear();
		allAdvantageCollections.clear();
		existingProductsInAspen.clear();

		try {
			addProductStmt.close();
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

			dbConn.close();
		} catch (SQLException e) {
			logger.error("Error closing overdrive extractor", e);
		}
	}
}
