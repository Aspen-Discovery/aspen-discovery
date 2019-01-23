package org.marmot;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

import com.mysql.jdbc.exceptions.MySQLIntegrityConstraintViolationException;
import org.apache.commons.codec.binary.Base64;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

class ExtractOverDriveInfo {
	private static Logger logger = Logger.getLogger(ExtractOverDriveInfo.class);
	private Connection vufindConn;
	private Connection econtentConn;
	private OverDriveExtractLogEntry results;

	private Long lastExtractTime;
	private Long extractStartTime;
	private String lastUpdateTimeParam = "";

	private boolean partialExtractRunning;
	private Long partialExtractRunningVariableId;
	
	//Overdrive API information
	private String clientSecret;
	private String clientKey;
	private String accountId;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private String overDriveProductsKey;
	private boolean forceMetaDataUpdate;
	private TreeMap<Long, String> libToOverDriveAPIKeyMap = new TreeMap<>();
	private HashMap<String, Long> overDriveFormatMap = new HashMap<>();
	
	private HashMap<String, OverDriveRecordInfo> overDriveTitles = new HashMap<>();
	private HashMap<String, Long> advantageCollectionToLibMap = new HashMap<>();
	private HashMap<String, OverDriveDBInfo> databaseProducts = new HashMap<>();
	private HashMap<String, Long> existingLanguageIds = new HashMap<>();
	private HashMap<String, Long> existingSubjectIds = new HashMap<>();
	
	private PreparedStatement addProductStmt;
	private PreparedStatement setNeedsUpdateStmt;
	private PreparedStatement getNumProductsNeedingUpdatesStmt;
	private PreparedStatement getIndividualProductStmt;
	private PreparedStatement getProductsNeedingUpdatesStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement deleteProductStmt;
	private PreparedStatement updateProductMetadataStmt;
	private PreparedStatement loadMetaDataStmt;
	private PreparedStatement addMetaDataStmt;
	private PreparedStatement updateMetaDataStmt;
	private PreparedStatement clearCreatorsStmt;
	private PreparedStatement addCreatorStmt;
	private PreparedStatement addLanguageStmt;
	private PreparedStatement clearLanguageRefStmt;
	private PreparedStatement addLanguageRefStmt;
	private PreparedStatement addSubjectStmt;
	private PreparedStatement clearSubjectRefStmt;
	private PreparedStatement addSubjectRefStmt;
	private PreparedStatement clearFormatsStmt;
	private PreparedStatement addFormatStmt;
	private PreparedStatement clearIdentifiersStmt;
	private PreparedStatement addIdentifierStmt;
	private PreparedStatement checkForExistingAvailabilityStmt;
	private PreparedStatement updateAvailabilityStmt;
	private PreparedStatement addAvailabilityStmt;
	private PreparedStatement deleteAvailabilityStmt;
	private PreparedStatement updateProductAvailabilityStmt;
	private PreparedStatement markGroupedWorkForBibAsChangedStmt;
	private boolean hadTimeoutsFromOverDrive;
	
	private CRC32 checksumCalculator = new CRC32();
	private boolean errorsWhileLoadingProducts;

	void extractOverDriveInfo(Ini configIni, Connection vufindConn, Connection econtentConn, OverDriveExtractLogEntry logEntry, boolean doFullReload, String individualIdToProcess) {
		this.vufindConn = vufindConn;
		this.econtentConn = econtentConn;
		this.results = logEntry;

		extractStartTime = new Date().getTime() / 1000;

		try {
			addProductStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_products set overdriveid = ?, crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastMetadataCheck = 0, lastMetadataChange = 0, lastAvailabilityCheck = 0, lastAvailabilityChange = 0, rawData=?", PreparedStatement.RETURN_GENERATED_KEYS);
			setNeedsUpdateStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products set needsUpdate = ? where overdriveid = ?");
			PreparedStatement markAllAsNeedingUpdatesStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products set needsUpdate = 1");
			long maxProductsToUpdate = 1500;
			getNumProductsNeedingUpdatesStmt = econtentConn.prepareCall("SELECT count(overdrive_api_products.id) from overdrive_api_products where needsUpdate = 1 and deleted = 0 LIMIT " + maxProductsToUpdate);
			getProductsNeedingUpdatesStmt = econtentConn.prepareCall("SELECT overdrive_api_products.id, overdriveId, crossRefId, lastMetadataCheck, lastMetadataChange, lastAvailabilityCheck, lastAvailabilityChange from overdrive_api_products where needsUpdate = 1 and deleted = 0 LIMIT " + maxProductsToUpdate);
			getIndividualProductStmt = econtentConn.prepareCall("SELECT overdrive_api_products.id, overdriveId, crossRefId, lastMetadataCheck, lastMetadataChange, lastAvailabilityCheck, lastAvailabilityChange from overdrive_api_products WHERE overdriveId = ?");
			updateProductStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET crossRefId = ?, mediaType = ?, title = ?, subtitle = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateUpdated = ?, deleted = 0, rawData=? where id = ?");
			deleteProductStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET deleted = 1, dateDeleted = ? where id = ?");
			updateProductMetadataStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
			loadMetaDataStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_metadata WHERE productId = ?");
			updateMetaDataStmt = econtentConn.prepareStatement("UPDATE overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, isOwnedByCollections=?, rawData=? where id = ?");
			addMetaDataStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, isOwnedByCollections=?, rawData=?");
			clearCreatorsStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_creators WHERE productId = ?");
			addCreatorStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_creators SET productId = ?, role = ?, name = ?, fileAs = ?");
			PreparedStatement loadLanguagesStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_languages");
			addLanguageStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_languages set code =?, name = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearLanguageRefStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_languages_ref where productId = ?");
			addLanguageRefStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_languages_ref set productId = ?, languageId = ?");
			PreparedStatement loadSubjectsStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_subjects");
			addSubjectStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_subjects set name = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearSubjectRefStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_subjects_ref where productId = ?");
			addSubjectRefStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_subjects_ref set productId = ?, subjectId = ?");
			clearFormatsStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_formats where productId = ?");
			addFormatStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_formats set productId = ?, textId = ?, numericId = ?, name = ?, fileName = ?, fileSize = ?, partCount = ?, sampleSource_1 = ?, sampleUrl_1 = ?, sampleSource_2 = ?, sampleUrl_2 = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearIdentifiersStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_identifiers where productId = ?");
			addIdentifierStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_identifiers set productId = ?, type = ?, value = ?");
			checkForExistingAvailabilityStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ? and libraryId = ?");
			updateAvailabilityStmt = econtentConn.prepareStatement("UPDATE overdrive_api_product_availability set available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ? WHERE id = ?");
			addAvailabilityStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_availability set productId = ?, libraryId = ?, available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?, availabilityType = ?");
			deleteAvailabilityStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_availability where id = ?");
			updateProductAvailabilityStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET lastAvailabilityCheck = ?, lastAvailabilityChange = ? where id = ?");
			markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'overdrive' and identifier = ?)") ;

			//Get the last time we extracted data from OverDrive
			if (individualIdToProcess != null) {
				logger.info("Updating a single record " + individualIdToProcess);
			}else if (!doFullReload){
				//Check to see if a partial extract is running
				try{
					PreparedStatement loadPartialExtractRunning = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'partial_overdrive_extract_running'");
					ResultSet loadPartialExtractRunningRS = loadPartialExtractRunning.executeQuery();
					if (loadPartialExtractRunningRS.next()){
						partialExtractRunning = loadPartialExtractRunningRS.getBoolean("value");
						partialExtractRunningVariableId = loadPartialExtractRunningRS.getLong("id");
					}
					loadPartialExtractRunningRS.close();
					loadPartialExtractRunning.close();

					if (partialExtractRunning){
						//Oops, a reindex is already running.
						logger.info("A partial overdrive extract is already running, verify that multiple extracts are not running for best performance.");
						//return;
					}else{
						updatePartialExtractRunning(true);
					}
				} catch (Exception e){
					logger.error("Could not load last index time from variables table ", e);
				}
			}else{
				logger.info("Doing a full reload of all records.");
				markAllAsNeedingUpdatesStmt.executeUpdate();
			}

			if (individualIdToProcess == null) {
				//Load last extract time regardless of if we are doing full index or partial index
				PreparedStatement getVariableStatement = vufindConn.prepareStatement("SELECT * FROM variables where name = 'last_overdrive_extract_time'");
				ResultSet lastExtractTimeRS = getVariableStatement.executeQuery();
				if (lastExtractTimeRS.next()) {
					lastExtractTime = lastExtractTimeRS.getLong("value");
					Date lastExtractDate = new Date(lastExtractTime);
					if (!doFullReload) {
						SimpleDateFormat lastUpdateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
						logger.info("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
						logEntry.addNote("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
						lastUpdateTimeParam = "lastupdatetime=" + lastUpdateFormat.format(lastExtractDate);
						//Simple Date Format doesn't give us quite the right timezone format so adjust
						lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 2) + ":" + lastUpdateTimeParam.substring(lastUpdateTimeParam.length() - 2);
					}
				}
			}

			//Update the last extract time
			Long extractStartTime = new Date().getTime();
			
			ResultSet loadLanguagesRS = loadLanguagesStmt.executeQuery();
			while (loadLanguagesRS.next()){
				existingLanguageIds.put(loadLanguagesRS.getString("code"), loadLanguagesRS.getLong("id"));
			}
			
			ResultSet loadSubjectsRS = loadSubjectsStmt.executeQuery();
			while (loadSubjectsRS.next()){
				existingSubjectIds.put(loadSubjectsRS.getString("name").toLowerCase(), loadSubjectsRS.getLong("id"));
			}
			
			PreparedStatement advantageCollectionMapStmt = vufindConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library where overdriveAdvantageName > ''");
			ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
			while (advantageCollectionMapRS.next()){
				advantageCollectionToLibMap.put(advantageCollectionMapRS.getString(2), advantageCollectionMapRS.getLong(1));
				libToOverDriveAPIKeyMap.put(advantageCollectionMapRS.getLong(1), advantageCollectionMapRS.getString(3));
			}
			
			//Load products from API 
			clientSecret = Util.cleanIniValue(configIni.get("OverDrive", "clientSecret"));
			clientKey = Util.cleanIniValue(configIni.get("OverDrive", "clientKey"));
			accountId = Util.cleanIniValue(configIni.get("OverDrive", "accountId"));
			
			overDriveProductsKey = configIni.get("OverDrive", "productsKey");
			if (overDriveProductsKey == null){
				logger.warn("Warning no products key provided for OverDrive");
			}
			String forceMetaDataUpdateStr = configIni.get("OverDrive", "forceMetaDataUpdate");
			forceMetaDataUpdate = forceMetaDataUpdateStr != null && Boolean.parseBoolean(forceMetaDataUpdateStr);
			
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

			try {
				if (clientSecret == null || clientKey == null || accountId == null || clientSecret.length() == 0 || clientKey.length() == 0 || accountId.length() == 0) {
					logEntry.addNote("Did not find correct configuration in config.ini, not loading overdrive titles");
				} else {
					if (individualIdToProcess == null) {
						//Load products from database this lets us know what is new, what has been deleted, and what has been updated
						if (!loadProductsFromDatabase()) {
							return;
						}

						//Load products from API to figure out what is actually new, what is deleted, and what needs an update
						if (!loadProductsFromAPI()) {
							return;
						}

						//Update products in database
						updateDatabase();
					}
					//Get a list of records to get full details for.  We don't want this to take forever so only do a few thousand
					//records at the most
					updateMetadataAndAvailability(individualIdToProcess);
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

			//Mark the new last update time if we did not get errors loading products from the database
			if (individualIdToProcess == null) {
				if (errorsWhileLoadingProducts || results.hasErrors()) {
					logger.debug("Not setting last extract time since there were problems extracting products from the API");
				} else {
					PreparedStatement updateExtractTime;
					if (lastExtractTime == null) {
						updateExtractTime = vufindConn.prepareStatement("INSERT INTO variables set value = ?, name = 'last_overdrive_extract_time'");
					} else {
						updateExtractTime = vufindConn.prepareStatement("UPDATE variables set value = ? where name = 'last_overdrive_extract_time'");
					}
					updateExtractTime.setLong(1, extractStartTime);
					updateExtractTime.executeUpdate();
					logger.debug("Setting last extract time to " + extractStartTime + " " + new Date(extractStartTime).toString());
				}
				if (!doFullReload) {
					updatePartialExtractRunning(false);
				}
			}
		} catch (SQLException e) {
		// handle any errors
			logger.error("Error initializing overdrive extraction", e);
			results.addNote("Error initializing overdrive extraction " + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}

	private void updateMetadataAndAvailability(String individualIdToProcess) {
		try {
			logger.debug("Starting to update metadata and availability for products");
			ResultSet productsNeedingUpdatesRS;
			if (individualIdToProcess == null){
				ResultSet numProductsNeedingUpdatesRS = getNumProductsNeedingUpdatesStmt.executeQuery();
				numProductsNeedingUpdatesRS.next();
				logger.info("There are " + numProductsNeedingUpdatesRS.getInt(1) + " products that currently need updates.");
				productsNeedingUpdatesRS = getProductsNeedingUpdatesStmt.executeQuery();
			}else{
				getIndividualProductStmt.setString(1, individualIdToProcess);
				productsNeedingUpdatesRS = getIndividualProductStmt.executeQuery();
			}


			//Add the main collection to make iteration easier later
			libToOverDriveAPIKeyMap.put(-1L, overDriveProductsKey);

			ArrayList<MetaAvailUpdateData> productsToUpdate = new ArrayList<>();
			while (productsNeedingUpdatesRS.next()) {
				MetaAvailUpdateData productToUpdate = new MetaAvailUpdateData();
				productToUpdate.databaseId = productsNeedingUpdatesRS.getLong("id");
				productToUpdate.crossRefId = productsNeedingUpdatesRS.getLong("crossRefId");
				productToUpdate.lastMetadataCheck = productsNeedingUpdatesRS.getLong("lastMetadataCheck");
				productToUpdate.lastMetadataChange = productsNeedingUpdatesRS.getLong("lastMetadataChange");
				productToUpdate.lastAvailabilityChange = productsNeedingUpdatesRS.getLong("lastAvailabilityChange");
				productToUpdate.overDriveId = productsNeedingUpdatesRS.getString("overdriveId");
				productsToUpdate.add(productToUpdate);
			}

			int batchSize = 25;
			int batchNum = 1;
			while (productsToUpdate.size() > 0){
				HashMap<String, SharedStats> sharedStatsHashMap = new HashMap<>();


				int maxIndex = productsToUpdate.size() > batchSize ? batchSize : productsToUpdate.size();
				ArrayList<MetaAvailUpdateData> productsToUpdateBatch = new ArrayList<>();
				for (int i = 0; i < maxIndex; i++){
					productsToUpdateBatch.add(productsToUpdate.get(i));
					sharedStatsHashMap.put(productsToUpdate.get(i).overDriveId, new SharedStats());
				}
				productsToUpdate.removeAll(productsToUpdateBatch);
				//Loop through the libraries first and then the products so we can get data as a batch.
				for (Long libraryId : libToOverDriveAPIKeyMap.keySet()){
					updateOverDriveMetaDataBatch(libraryId, productsToUpdateBatch);
					//TODO: Switch to V2 as soon as loading holds works properly
					updateOverDriveAvailabilityBatchV1(libraryId, productsToUpdateBatch, sharedStatsHashMap);
				}
				//Do a final update to mark that they don't need to be updated again.
				for (MetaAvailUpdateData productToUpdate : productsToUpdateBatch){
					if (!productToUpdate.hadAvailabilityErrors && !productToUpdate.hadMetadataErrors){

						setNeedsUpdateStmt.setInt(1, 0);
						setNeedsUpdateStmt.setString(2, productToUpdate.overDriveId);
						int numChanges = setNeedsUpdateStmt.executeUpdate();
						if (numChanges == 0){
							logger.warn("Did not update that " + productToUpdate.overDriveId + " no longer needs update");
						}
					}else{
						logger.info("Had errors updating metadata (" + productToUpdate.hadMetadataErrors + ") and/or availability (" + productToUpdate.hadAvailabilityErrors + ") for " + productToUpdate.overDriveId + " crossRefId " + productToUpdate.crossRefId);
					}
				}
				logger.debug("Processed availability and metadata batch " + batchNum + " records " + ((batchNum - 1) * batchSize) + " to " + (batchNum * batchSize));
				batchNum++;
			}
		}catch (Exception e){
			logger.error("Error updating metadata and availability", e);
		}
	}

	private void updateDatabase() throws SocketTimeoutException {
		int numProcessed = 0;
		for (String overDriveId : overDriveTitles.keySet()){
			OverDriveRecordInfo overDriveInfo = overDriveTitles.get(overDriveId);
			//Check to see if the title already exists within the database.
			try {
				econtentConn.setAutoCommit(false);
				if (databaseProducts.containsKey(overDriveId)) {
					updateProductInDB(overDriveInfo, databaseProducts.get(overDriveId));
					databaseProducts.remove(overDriveId);
				} else {
					addProductToDB(overDriveInfo);
				}
				econtentConn.commit();
				econtentConn.setAutoCommit(true);
			}catch (SQLException e){
				logger.info("Error saving/updating product ", e);
				results.addNote("Error saving/updating product " + e.toString());
				results.incErrors();
			}

			results.saveResults();
			numProcessed++;
			if (numProcessed % 100 == 0){
				logger.debug("Updated database for  " + numProcessed + " products from the API");
			}
		}
		
		//Delete any products that no longer exist, but only if we aren't only loading changes and also
		//should not update if we had any timeouts loading products since those products would have been skipped.
		if (lastUpdateTimeParam.length() == 0 && !hadTimeoutsFromOverDrive){
			for (String overDriveId : databaseProducts.keySet()){
				OverDriveDBInfo overDriveDBInfo = databaseProducts.get(overDriveId);
				if (!overDriveDBInfo.isDeleted()){
					deleteProductInDB(databaseProducts.get(overDriveId));
				}
			}
		}
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

	private void updateProductInDB(OverDriveRecordInfo overDriveInfo,
			OverDriveDBInfo overDriveDBInfo) throws SocketTimeoutException {
		try {
			boolean updateMade = false;
			//Check to see if anything has changed.  If so, perform necessary updates. 
			if (!Util.compareStrings(overDriveInfo.getMediaType(), overDriveDBInfo.getMediaType()) || 
					!Util.compareStrings(overDriveInfo.getTitle(), overDriveDBInfo.getTitle()) ||
					!Util.compareStrings(overDriveInfo.getSubtitle(), overDriveDBInfo.getSubtitle()) ||
					!Util.compareStrings(overDriveInfo.getSeries(), overDriveDBInfo.getSeries()) ||
					!Util.compareStrings(overDriveInfo.getPrimaryCreatorRole(), overDriveDBInfo.getPrimaryCreatorRole()) ||
					!Util.compareStrings(overDriveInfo.getPrimaryCreatorName(), overDriveDBInfo.getPrimaryCreatorName()) ||
					!Util.compareStrings(overDriveInfo.getCoverImage(), overDriveDBInfo.getCover()) ||
					overDriveInfo.getCrossRefId() != overDriveDBInfo.getCrossRefId() ||
					overDriveDBInfo.isDeleted() ||
					!overDriveDBInfo.hasRawData()
					){
				//Update the product in the database
				long curTime = new Date().getTime() / 1000;
				int curCol = 0;
				updateProductStmt.setLong(++curCol, overDriveInfo.getCrossRefId());
				updateProductStmt.setString(++curCol, overDriveInfo.getMediaType());
				updateProductStmt.setString(++curCol, overDriveInfo.getTitle());
				updateProductStmt.setString(++curCol, overDriveInfo.getSubtitle());
				updateProductStmt.setString(++curCol, overDriveInfo.getSeries());
				updateProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorRole());
				updateProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorName());
				updateProductStmt.setString(++curCol, overDriveInfo.getCoverImage());
				updateProductStmt.setLong(++curCol, curTime);
				updateProductStmt.setString(++curCol, overDriveInfo.getRawData());
				updateProductStmt.setLong(++curCol, overDriveDBInfo.getDbId());

				updateProductStmt.executeUpdate();

				updateMade = true;
			}

			setNeedsUpdateStmt.setBoolean(1, true);
			setNeedsUpdateStmt.setString(2, overDriveInfo.getId());
			setNeedsUpdateStmt.executeUpdate();
			
			if (updateMade){
				//Mark that the grouped work needs to be updated
				markGroupedWorkForBibAsChangedStmt.setLong(1, extractStartTime);
				markGroupedWorkForBibAsChangedStmt.setString(2, overDriveInfo.getId());
				markGroupedWorkForBibAsChangedStmt.executeUpdate();
				results.incUpdated();
			}else{
				results.incSkipped();
			}
			
		} catch (SQLException e) {
			logger.info("Error updating overdrive product " + overDriveInfo.getId(), e);
			results.addNote("Error updating overdrive product " + overDriveInfo.getId() + e.toString());
			results.incErrors();
			results.saveResults();
		}
		
	}

	private void addProductToDB(OverDriveRecordInfo overDriveInfo) throws SocketTimeoutException {
		int curCol = 0;
		try {
			long curTime = new Date().getTime() / 1000;
			addProductStmt.setString(++curCol, overDriveInfo.getId());
			addProductStmt.setLong(++curCol, overDriveInfo.getCrossRefId());
			addProductStmt.setString(++curCol, overDriveInfo.getMediaType());
			addProductStmt.setString(++curCol, overDriveInfo.getTitle());
			addProductStmt.setString(++curCol, overDriveInfo.getSubtitle());
			addProductStmt.setString(++curCol, overDriveInfo.getSeries());
			addProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorRole());
			addProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorName());
			addProductStmt.setString(++curCol, overDriveInfo.getCoverImage());
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.setString(++curCol, overDriveInfo.getRawData());
			addProductStmt.executeUpdate();

			ResultSet newIdRS = addProductStmt.getGeneratedKeys();
			newIdRS.next();
			long databaseId = newIdRS.getLong(1);

			results.incAdded();

			//Update metadata based information
			//Do this the first time we detect it to be certain that all the data exists on the first extract.
			updateOverDriveMetaData(overDriveInfo, databaseId, null);
			updateOverDriveAvailability(overDriveInfo, databaseId, null);
		} catch (MySQLIntegrityConstraintViolationException e1){
			logger.warn("Error saving product " + overDriveInfo.getId() + " to the database, it was already added by another process");
			results.addNote("Error saving product " + overDriveInfo.getId() + " to the database, it was already added by another process");
			results.incErrors();
			results.saveResults();
		} catch (SQLException e) {
			logger.warn("Error saving product " + overDriveInfo.getId() + " to the database", e);
			results.addNote("Error saving product " + overDriveInfo.getId() + " to the database " + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}

	private boolean loadProductsFromDatabase() {
		try {
			PreparedStatement loadProductsStmt = econtentConn.prepareStatement("Select * from overdrive_api_products");
			ResultSet loadProductsRS = loadProductsStmt.executeQuery();
			while (loadProductsRS.next()){
				String overdriveId = loadProductsRS.getString("overdriveId").toLowerCase();
				OverDriveDBInfo curProduct = new OverDriveDBInfo();
				curProduct.setDbId(loadProductsRS.getLong("id"));
				curProduct.setCrossRefId(loadProductsRS.getLong("crossRefId"));
				curProduct.setMediaType(loadProductsRS.getString("mediaType"));
				curProduct.setSeries(loadProductsRS.getString("series"));
				curProduct.setTitle(loadProductsRS.getString("title"));
				curProduct.setPrimaryCreatorRole(loadProductsRS.getString("primaryCreatorRole"));
				curProduct.setPrimaryCreatorName(loadProductsRS.getString("primaryCreatorName"));
				curProduct.setCover(loadProductsRS.getString("cover"));
				curProduct.setLastAvailabilityCheck(loadProductsRS.getLong("lastAvailabilityCheck"));
				curProduct.setLastAvailabilityChange(loadProductsRS.getLong("lastAvailabilityChange"));
				curProduct.setLastMetadataCheck(loadProductsRS.getLong("lastMetadataCheck"));
				curProduct.setLastMetadataChange(loadProductsRS.getLong("lastMetadataChange"));
				curProduct.setDeleted(loadProductsRS.getLong("deleted") == 1);
				String rawData = loadProductsRS.getString("rawData");
				curProduct.setHasRawData(rawData != null && rawData.length() > 0);
				databaseProducts.put(overdriveId, curProduct);
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
	private boolean loadProductsFromAPI() throws SocketTimeoutException {
		WebServiceResponse libraryInfoResponse = callOverDriveURL("https://api.overdrive.com/v1/libraries/" + accountId);
		if (libraryInfoResponse.getResponseCode() == 200 && libraryInfoResponse.getResponse() != null){
			JSONObject libraryInfo = libraryInfoResponse.getResponse();
			try {
				String mainLibraryName = libraryInfo.getString("name");
				String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
				if (lastUpdateTimeParam.length() > 0) {
					if (mainProductUrl.contains("?")) {
						mainProductUrl += "&" + lastUpdateTimeParam;
					} else {
						mainProductUrl += "?" + lastUpdateTimeParam;
					}
				}
				loadProductsFromUrl(mainLibraryName, mainProductUrl);
				logger.info("loaded " + overDriveTitles.size() + " overdrive titles in shared collection");
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")) {
					WebServiceResponse webServiceResponse = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					if (webServiceResponse.getResponseCode() == 200) {
						JSONObject advantageInfo = webServiceResponse.getResponse();
						if (advantageInfo.has("advantageAccounts")) {
							JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
							for (int i = 0; i < advantageAccounts.length(); i++) {
								JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
								String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
								WebServiceResponse advantageWebServiceResponse = callOverDriveURL(advantageSelfUrl);
								if (advantageWebServiceResponse.getResponseCode() == 200) {
									JSONObject advantageSelfInfo = advantageWebServiceResponse.getResponse();
									if (advantageSelfInfo != null) {
										String advantageName = curAdvantageAccount.getString("name");
										String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
										if (lastUpdateTimeParam.length() > 0) {
											if (productUrl.contains("?")) {
												productUrl += "&" + lastUpdateTimeParam;
											} else {
												productUrl += "?" + lastUpdateTimeParam;
											}
										}

										Long advantageLibraryId = getLibraryIdForOverDriveAccount(advantageName);
										if (advantageLibraryId != -1L){
											loadProductsFromUrl(advantageName, productUrl);
										}else{
											logger.info("Skipping advantage account " + advantageName + " because it does not have a Pika library");
										}

									}
								} else {
									results.addNote("Unable to load advantage information for " + advantageSelfUrl);
									if (advantageWebServiceResponse.getError() != null) {
										results.addNote(advantageWebServiceResponse.getError());
									}
								}
							}
						}
					} else {
						results.addNote("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
						if (webServiceResponse.getError() != null) {
							results.addNote(webServiceResponse.getError());
						}
						results.incErrors();
					}
					logger.info("loaded " + overDriveTitles.size() + " overdrive titles in shared collection and advantage collections");
				}
				results.setNumProducts(overDriveTitles.size());
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
			if (libraryInfoResponse.getError() != null){
				results.addNote(libraryInfoResponse.getError());
			}
			results.incErrors();
			logger.info("Error loading overdrive titles " + libraryInfoResponse.getError());
			return false;
		}

	}

	private void loadProductsFromUrl(String libraryName, String mainProductUrl) throws JSONException, SocketTimeoutException {
		WebServiceResponse productsResponse = callOverDriveURL(mainProductUrl);
		if (productsResponse.getResponseCode() == 200) {
			JSONObject productInfo = productsResponse.getResponse();
			if (productInfo == null) {
				return;
			}
			long numProducts = productInfo.getLong("totalItems");
			Long libraryId = getLibraryIdForOverDriveAccount(libraryName);
			//if (numProducts > 50) numProducts = 50;
			logger.info(libraryName + " collection has " + numProducts + " products in it.  The libraryId for the collection is " + libraryId);
			results.addNote("Loading OverDrive information for " + libraryName);
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
				logger.debug("Processing " + libraryName + " batch from " + i + " to " + (i + batchSize));
				batchUrl += "offset=" + i + "&limit=" + batchSize;

				WebServiceResponse productBatchInfoResponse = callOverDriveURL(batchUrl);
				if (productBatchInfoResponse.getResponseCode() == 200){
					JSONObject productBatchInfo = productBatchInfoResponse.getResponse();
					if (productBatchInfo != null && productBatchInfo.has("products")) {
						numProducts = productBatchInfo.getLong("totalItems");
						JSONArray products = productBatchInfo.getJSONArray("products");
						logger.debug(" Found " + products.length() + " products");
						for (int j = 0; j < products.length(); j++) {
							JSONObject curProduct = products.getJSONObject(j);
							//Mark the product as updated and needing metadata and availability checks
							try {
								setNeedsUpdateStmt.setBoolean(1, true);
								setNeedsUpdateStmt.setString(2, curProduct.getString("id"));
								setNeedsUpdateStmt.executeUpdate();
							}catch (SQLException e){
								logger.error("Unable to update needs update ", e);
							}

							OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(libraryId, curProduct);
							if (curRecord != null) {
								if (overDriveTitles.containsKey(curRecord.getId().toLowerCase())) {
									OverDriveRecordInfo oldRecord = overDriveTitles.get(curRecord.getId().toLowerCase());
									oldRecord.getCollections().add(libraryId);
								} else {
									//logger.debug("Loading record " + curRecord.getId());
									overDriveTitles.put(curRecord.getId().toLowerCase(), curRecord);
								}
							}else{
								//Could not parse the record make sure we log that there was an error
								errorsWhileLoadingProducts = true;
								results.incErrors();
							}
						}
					}
				}else{
					logger.info("Could not load product batch " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getError());
					results.addNote("Could not load product batch " + productBatchInfoResponse.getResponseCode() + " - " + productBatchInfoResponse.getError());
					errorsWhileLoadingProducts = true;
					results.incErrors();
				}

			}
		}else{
			errorsWhileLoadingProducts = true;
		}
	}

	private OverDriveRecordInfo loadOverDriveRecordFromJSON(Long libraryId, JSONObject curProduct) throws JSONException {
		OverDriveRecordInfo curRecord = new OverDriveRecordInfo();
		curRecord.setId(curProduct.getString("id"));
		//logger.debug("Processing overdrive title " + curRecord.getId());
		if (!curProduct.has("title")){
			logger.debug("Product " + curProduct.getString("id") + " did not have a title, skipping");
			results.addNote("Product " + curProduct.getString("id") + " did not have a title, skipping");
			return null;
		}
		curRecord.setTitle(curProduct.getString("title"));
		curRecord.setCrossRefId(curProduct.getLong("crossRefId"));
		if (curProduct.has("subtitle")){
			curRecord.setSubtitle(curProduct.getString("subtitle"));
		}
		curRecord.setMediaType(curProduct.getString("mediaType"));
		if (curProduct.has("series")){
			curRecord.setSeries(curProduct.getString("series"));
		}
		if (curProduct.has("primaryCreator")){
			curRecord.setPrimaryCreatorName(curProduct.getJSONObject("primaryCreator").getString("name"));
			curRecord.setPrimaryCreatorRole(curProduct.getJSONObject("primaryCreator").getString("role"));
		}
		if (curProduct.has("formats")){
			for (int k = 0; k < curProduct.getJSONArray("formats").length(); k++){
				curRecord.getFormats().add(curProduct.getJSONArray("formats").getJSONObject(k).getString("id"));
			}
		}
		if (curProduct.has("images") && curProduct.getJSONObject("images").has("thumbnail")){
			String thumbnailUrl = curProduct.getJSONObject("images").getJSONObject("thumbnail").getString("href");
			curRecord.setCoverImage(thumbnailUrl);
		}
		curRecord.getCollections().add(libraryId);
		curRecord.setRawData(curProduct.toString(2));
		return curRecord;
	}

	private Long getLibraryIdForOverDriveAccount(String libraryName) {
		if (advantageCollectionToLibMap.containsKey(libraryName)){
			return advantageCollectionToLibMap.get(libraryName);
		}
		return -1L;
	}

	private boolean updateOverDriveMetaData(OverDriveRecordInfo overDriveInfo, long databaseId, OverDriveDBInfo dbInfo) throws SocketTimeoutException {
		//Check to see if we need to load metadata
		long curTime = new Date().getTime() / 1000;
		//Don't need to load metadata if we already have metadata and the metadata was checked within the last 24 hours
		if ((dbInfo != null && dbInfo.getLastMetadataCheck() >= curTime - 24 * 60 * 60) && !forceMetaDataUpdate){
			return false;
		}

		//load metadata information for the product from the database
		OverDriveDBMetaData databaseMetaData = loadMetadataFromDatabase(databaseId);

		//Get the url to call for meta data information (based on the first owning collection)
		long firstCollection = overDriveInfo.getCollections().iterator().next();
		String apiKey;
		if (firstCollection == -1L){
			apiKey = overDriveProductsKey;
		}else{
			apiKey = libToOverDriveAPIKeyMap.get(firstCollection);
		}
		if (apiKey == null){
			logger.error("Unable to get api key for collection " + firstCollection);
			results.incErrors();
		}
		String url = "https://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/metadata";
		WebServiceResponse metaDataResponse = callOverDriveURL(url);
		if (metaDataResponse.getResponseCode() != 200){
			logger.info("Could not load metadata from " + url );
			logger.info(metaDataResponse.getResponseCode() + ":" + metaDataResponse.getError());
			results.addNote("Could not load metadata from " + url );
			results.incErrors();
			return false;
		}else{
			JSONObject metaData = metaDataResponse.getResponse();
			checksumCalculator.reset();
			checksumCalculator.update(metaData.toString().getBytes());
			long metadataChecksum = checksumCalculator.getValue();
			boolean updateMetaData = false;
			if (dbInfo == null || forceMetaDataUpdate){
				updateMetaData = true;
			}else{
				if (!databaseMetaData.hasRawData()){
					updateMetaData = true;
				}else if (metadataChecksum != databaseMetaData.getChecksum()){
					//The metadata has definitely changed.
					updateMetaData = true;
				}else if (dbInfo.getLastMetadataCheck() <= curTime - 14 * 24 * 60 * 60){
					//If it's been two weeks since we last updated, give a 20% chance of updating
					//Don't update everything at once to spread out the number of calls and reduce time.
					double randomNumber = Math.random() * 100;
					if (randomNumber <= 20.0){
						updateMetaData = true;
					}
				}
			}
			if (updateMetaData){
				try {
					int curCol = 0;
					PreparedStatement metaDataStatement = addMetaDataStmt;
					if (databaseMetaData.getId() != -1){
						metaDataStatement = updateMetaDataStmt;
					}
					metaDataStatement.setLong(++curCol, databaseId);
					metaDataStatement.setLong(++curCol, metadataChecksum);
					metaDataStatement.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
					metaDataStatement.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
					//Grab the textual version of publish date rather than the actual date
					if (metaData.has("publishDateText")){
						String publishDateText = metaData.getString("publishDateText");
						if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")){
							publishDateText = publishDateText.substring(6, 10);
							metaDataStatement.setLong(++curCol, Long.parseLong(publishDateText));
						}else{
							metaDataStatement.setNull(++curCol, Types.INTEGER);
						}
					}else{
						metaDataStatement.setNull(++curCol, Types.INTEGER);
					}

					metaDataStatement.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
					metaDataStatement.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
					metaDataStatement.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
					metaDataStatement.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
					metaDataStatement.setDouble(++curCol, metaData.has("starRating") ? metaData.getDouble("starRating") : 0);
					metaDataStatement.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
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
					metaDataStatement.setString(++curCol, thumbnail);
					metaDataStatement.setString(++curCol, cover);
					metaDataStatement.setBoolean(++curCol, metaData.has("isOwnedByCollections") && metaData.getBoolean("isOwnedByCollections"));
					metaDataStatement.setString(++curCol, metaData.toString(2));

					if (databaseMetaData.getId() != -1){
						metaDataStatement.setLong(++curCol, databaseMetaData.getId());
					}
					metaDataStatement.executeUpdate();

					clearCreatorsStmt.setLong(1, databaseId);
					clearCreatorsStmt.executeUpdate();
					if (metaData.has("creators")){
						JSONArray contributors = metaData.getJSONArray("creators");
						for (int i = 0; i < contributors.length(); i++){
							JSONObject contributor = contributors.getJSONObject(i);
							addCreatorStmt.setLong(1, databaseId);
							addCreatorStmt.setString(2, contributor.getString("role"));
							addCreatorStmt.setString(3, contributor.getString("name"));
							addCreatorStmt.setString(4, contributor.getString("fileAs"));
							addCreatorStmt.executeUpdate();
						}
					}

					clearLanguageRefStmt.setLong(1, databaseId);
					clearLanguageRefStmt.executeUpdate();
					if (metaData.has("languages")){
						JSONArray languages = metaData.getJSONArray("languages");
						for (int i = 0; i < languages.length(); i++){
							JSONObject language = languages.getJSONObject(i);
							String code = language.getString("code");
							long languageId;
							if (existingLanguageIds.containsKey(code)){
								languageId = existingLanguageIds.get(code);
							}else{
								addLanguageStmt.setString(1, code);
								addLanguageStmt.setString(2, language.getString("name"));
								addLanguageStmt.executeUpdate();
								ResultSet keys = addLanguageStmt.getGeneratedKeys();
								keys.next();
								languageId = keys.getLong(1);
								existingLanguageIds.put(code, languageId);
							}
							addLanguageRefStmt.setLong(1, databaseId);
							addLanguageRefStmt.setLong(2, languageId);
							addLanguageRefStmt.executeUpdate();
						}
					}

					clearSubjectRefStmt.setLong(1, databaseId);
					clearSubjectRefStmt.executeUpdate();
					if (metaData.has("subjects")){
						HashSet<String> subjectsProcessed = new HashSet<>();
						JSONArray subjects = metaData.getJSONArray("subjects");
						for (int i = 0; i < subjects.length(); i++){
							JSONObject subject = subjects.getJSONObject(i);
							String curSubject = subject.getString("value").trim();
							String lcaseSubject = curSubject.toLowerCase();
							//First make sure we haven't processed this, htere are a few records where the same subject occurs twice
							if (subjectsProcessed.contains(lcaseSubject)){
								continue;
							}
							long subjectId;
							if (existingSubjectIds.containsKey(lcaseSubject)){
								subjectId = existingSubjectIds.get(lcaseSubject);
							}else{
								addSubjectStmt.setString(1, curSubject);
								addSubjectStmt.executeUpdate();
								ResultSet keys = addSubjectStmt.getGeneratedKeys();
								keys.next();
								subjectId = keys.getLong(1);
								existingSubjectIds.put(lcaseSubject, subjectId);
							}
							addSubjectRefStmt.setLong(1, databaseId);
							addSubjectRefStmt.setLong(2, subjectId);
							addSubjectRefStmt.executeUpdate();
							subjectsProcessed.add(lcaseSubject);
						}
					}

					clearFormatsStmt.setLong(1, databaseId);
					clearFormatsStmt.executeUpdate();
					clearIdentifiersStmt.setLong(1, databaseId);
					clearIdentifiersStmt.executeUpdate();
					if (metaData.has("formats")){
						JSONArray formats = metaData.getJSONArray("formats");
						HashSet<String> uniqueIdentifiers = new HashSet<>();
						for (int i = 0; i < formats.length(); i++){
							JSONObject format = formats.getJSONObject(i);
							addFormatStmt.setLong(1, databaseId);
							String textFormat = format.getString("id");
							addFormatStmt.setString(2, textFormat);
							Long numericFormat = overDriveFormatMap.get(textFormat);
							if (numericFormat == null){
								logger.warn("Could not find numeric format for format " + textFormat);
								results.addNote("Could not find numeric format for format " + textFormat);
								results.incErrors();
								System.out.println("Warning: new format for OverDrive found " + textFormat);
								continue;
							}
							addFormatStmt.setLong(3, numericFormat);
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
							addIdentifierStmt.setLong(1, databaseId);
							String[] identifierInfo = curIdentifier.split(":");
							addIdentifierStmt.setString(2, identifierInfo[0]);
							addIdentifierStmt.setString(3, identifierInfo[1]);
							addIdentifierStmt.executeUpdate();
						}
					}
					results.incMetadataChanges();
				} catch (Exception e) {
					logger.info("Error loading meta data for title ", e);
					results.addNote("Error loading meta data for title " + overDriveInfo.getId() + " " + e.toString());
					results.incErrors();
				}
			}
			try {
				updateProductMetadataStmt.setLong(1, curTime);
				if (updateMetaData){
					updateProductMetadataStmt.setLong(2, curTime);
				}else{
					Long lastMetaDataChange = dbInfo.getLastMetadataChange();
					updateProductMetadataStmt.setLong(2, lastMetaDataChange);
				}
				updateProductMetadataStmt.setLong(3, databaseId);
				updateProductMetadataStmt.executeUpdate();
			} catch (SQLException e) {
				logger.warn("Error updating product metadata summary ", e);
				results.addNote("Error updating product metadata summary " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
			return updateMetaData;
		}
	}

	private void updateOverDriveMetaDataBatch(long libraryId, List<MetaAvailUpdateData> productsToUpdateBatch) throws SocketTimeoutException {
		if (productsToUpdateBatch.size() == 0){
			return;
		}
		//Check to see if we need to load metadata
		long curTime = new Date().getTime() / 1000;

		String apiKey = libToOverDriveAPIKeyMap.get(libraryId);
		String url = "https://api.overdrive.com/v1/collections/" + apiKey + "/bulkmetadata?reserveIds=";
		ArrayList<MetaAvailUpdateData> productsToUpdateMetadata = new ArrayList<>();
		for (MetaAvailUpdateData curProduct : productsToUpdateBatch) {
			if (!curProduct.metadataUpdated) {
				if (productsToUpdateMetadata.size() >= 1) {
					url += ",";
				}
				url += curProduct.overDriveId;
				productsToUpdateMetadata.add(curProduct);
			}
		}

		if (productsToUpdateMetadata.size() == 0){
			return;
		}

		WebServiceResponse metaDataResponse = callOverDriveURL(url);
		if (metaDataResponse.getResponseCode() != 200){
			//Doesn't exist in this collection, skip to the next.
			logger.error("Error " + metaDataResponse.getResponseCode() + " retrieving batch metadata for batch " + url + " " + metaDataResponse.getError());
		}else{
			JSONObject bulkResponse = metaDataResponse.getResponse();
			if (bulkResponse.has("metadata")){
				try {
					JSONArray metaDataArray = bulkResponse.getJSONArray("metadata");
					for (int i = 0; i < metaDataArray.length(); i++) {
						JSONObject metadata = metaDataArray.getJSONObject(i);
						//Get the product to update
						for (MetaAvailUpdateData curProduct : productsToUpdateMetadata){
							if (metadata.getString("id").equalsIgnoreCase(curProduct.overDriveId)){
								if (metadata.getBoolean("isOwnedByCollections")){
									updateDBMetadataForProduct(curProduct, metadata, curTime);
								}else{
									boolean ownedByAdvantage = false;
									logger.debug("Product " + curProduct.overDriveId + " is not owned by the shared collection, checking advantage collections.");
									//Sometimes a product is owned by just advantage accounts so we need to check those accounts too
									for (String advantageKey : libToOverDriveAPIKeyMap.values()){
										url = "https://api.overdrive.com/v1/collections/" +advantageKey+ "/products/" + curProduct.overDriveId + "/metadata";
										WebServiceResponse advantageMetaDataResponse = callOverDriveURL(url);
										if (advantageMetaDataResponse.getResponseCode() != 200){
											//Doesn't exist in this collection, skip to the next.
											logger.error("Error " + advantageMetaDataResponse.getResponseCode() + " retrieving metadata for advantage account " + url + " " + metaDataResponse.getError());
										}else {
											JSONObject advantageMetadata = advantageMetaDataResponse.getResponse();
											if (advantageMetadata.getBoolean("isOwnedByCollections")){
												updateDBMetadataForProduct(curProduct, advantageMetadata, curTime);
												ownedByAdvantage = true;
												break;
											}
										}
									}
									if (!ownedByAdvantage){
										//Not owned by any collections, make sure we set that it isn't owned.
										logger.debug("Product " + curProduct.overDriveId + " is not owned by any collections.");
										updateDBMetadataForProduct(curProduct, metadata, curTime);
									}
								}

								curProduct.metadataUpdated = true;
								productsToUpdateMetadata.remove(curProduct);
								break;
							}
						}

					}
				}catch (Exception e){
					logger.error("Error loading metadata within batch", e);
				}
			}
		}
	}

	private void updateDBMetadataForProduct(MetaAvailUpdateData updateData, JSONObject metaData, long curTime){
		OverDriveDBMetaData databaseMetaData = loadMetadataFromDatabase(updateData.databaseId);
		checksumCalculator.reset();
		checksumCalculator.update(metaData.toString().getBytes());
		long metadataChecksum = checksumCalculator.getValue();
		boolean updateMetaData = false;
		if (databaseMetaData.getId() == -1 || forceMetaDataUpdate){
			updateMetaData = true;
		}else{
			if (!databaseMetaData.hasRawData()){
				updateMetaData = true;
			}else if (metadataChecksum != databaseMetaData.getChecksum()){
				//The metadata has definitely changed.
				updateMetaData = true;
			}else if (updateData.lastMetadataCheck <= curTime - 14 * 24 * 60 * 60){
				//If it's been two weeks since we last updated, give a 20% chance of updating
				//Don't update everything at once to spread out the number of calls and reduce time.
				double randomNumber = Math.random() * 100;
				if (randomNumber <= 20.0){
					updateMetaData = true;
				}
			}
		}
		if (updateMetaData){
			try {
				int curCol = 0;
				PreparedStatement metaDataStatement = addMetaDataStmt;
				if (databaseMetaData.getId() != -1){
					metaDataStatement = updateMetaDataStmt;
				}
				metaDataStatement.setLong(++curCol, updateData.databaseId);
				metaDataStatement.setLong(++curCol, metadataChecksum);
				metaDataStatement.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
				metaDataStatement.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
				//Grab the textual version of publish date rather than the actual date
				if (metaData.has("publishDateText")){
					String publishDateText = metaData.getString("publishDateText");
					if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")){
						publishDateText = publishDateText.substring(6, 10);
						metaDataStatement.setLong(++curCol, Long.parseLong(publishDateText));
					}else{
						metaDataStatement.setNull(++curCol, Types.INTEGER);
					}
				}else{
					metaDataStatement.setNull(++curCol, Types.INTEGER);
				}

				metaDataStatement.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
				metaDataStatement.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
				metaDataStatement.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
				metaDataStatement.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
				metaDataStatement.setDouble(++curCol, metaData.has("starRating") ? metaData.getDouble("starRating") : 0);
				metaDataStatement.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
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
				metaDataStatement.setString(++curCol, thumbnail);
				metaDataStatement.setString(++curCol, cover);
				metaDataStatement.setBoolean(++curCol, metaData.has("isOwnedByCollections") && metaData.getBoolean("isOwnedByCollections"));
				metaDataStatement.setString(++curCol, metaData.toString(2));

				if (databaseMetaData.getId() != -1){
					metaDataStatement.setLong(++curCol, databaseMetaData.getId());
				}
				metaDataStatement.executeUpdate();

				clearCreatorsStmt.setLong(1, updateData.databaseId);
				clearCreatorsStmt.executeUpdate();
				if (metaData.has("creators")){
					JSONArray contributors = metaData.getJSONArray("creators");
					for (int i = 0; i < contributors.length(); i++){
						JSONObject contributor = contributors.getJSONObject(i);
						addCreatorStmt.setLong(1, updateData.databaseId);
						addCreatorStmt.setString(2, contributor.getString("role"));
						addCreatorStmt.setString(3, contributor.getString("name"));
						addCreatorStmt.setString(4, contributor.getString("fileAs"));
						addCreatorStmt.executeUpdate();
					}
				}

				clearLanguageRefStmt.setLong(1, updateData.databaseId);
				clearLanguageRefStmt.executeUpdate();
				if (metaData.has("languages")){
					JSONArray languages = metaData.getJSONArray("languages");
					for (int i = 0; i < languages.length(); i++){
						JSONObject language = languages.getJSONObject(i);
						String code = language.getString("code");
						long languageId;
						if (existingLanguageIds.containsKey(code)){
							languageId = existingLanguageIds.get(code);
						}else{
							addLanguageStmt.setString(1, code);
							addLanguageStmt.setString(2, language.getString("name"));
							addLanguageStmt.executeUpdate();
							ResultSet keys = addLanguageStmt.getGeneratedKeys();
							keys.next();
							languageId = keys.getLong(1);
							existingLanguageIds.put(code, languageId);
						}
						addLanguageRefStmt.setLong(1, updateData.databaseId);
						addLanguageRefStmt.setLong(2, languageId);
						addLanguageRefStmt.executeUpdate();
					}
				}

				clearSubjectRefStmt.setLong(1, updateData.databaseId);
				clearSubjectRefStmt.executeUpdate();
				if (metaData.has("subjects")){
					HashSet<String> subjectsProcessed = new HashSet<>();
					JSONArray subjects = metaData.getJSONArray("subjects");
					for (int i = 0; i < subjects.length(); i++){
						JSONObject subject = subjects.getJSONObject(i);
						String curSubject = subject.getString("value").trim();
						String lcaseSubject = curSubject.toLowerCase();
						//First make sure we haven't processed this, htere are a few records where the same subject occurs twice
						if (subjectsProcessed.contains(lcaseSubject)){
							continue;
						}
						long subjectId;
						if (existingSubjectIds.containsKey(lcaseSubject)){
							subjectId = existingSubjectIds.get(lcaseSubject);
						}else{
							addSubjectStmt.setString(1, curSubject);
							addSubjectStmt.executeUpdate();
							ResultSet keys = addSubjectStmt.getGeneratedKeys();
							keys.next();
							subjectId = keys.getLong(1);
							existingSubjectIds.put(lcaseSubject, subjectId);
						}
						addSubjectRefStmt.setLong(1, updateData.databaseId);
						addSubjectRefStmt.setLong(2, subjectId);
						addSubjectRefStmt.executeUpdate();
						subjectsProcessed.add(lcaseSubject);
					}
				}

				clearFormatsStmt.setLong(1, updateData.databaseId);
				clearFormatsStmt.executeUpdate();
				clearIdentifiersStmt.setLong(1, updateData.databaseId);
				clearIdentifiersStmt.executeUpdate();
				if (metaData.has("formats")){
					JSONArray formats = metaData.getJSONArray("formats");
					HashSet<String> uniqueIdentifiers = new HashSet<>();
					for (int i = 0; i < formats.length(); i++){
						JSONObject format = formats.getJSONObject(i);
						addFormatStmt.setLong(1, updateData.databaseId);
						String textFormat = format.getString("id");
						addFormatStmt.setString(2, textFormat);
						Long numericFormat = overDriveFormatMap.get(textFormat);
						if (numericFormat == null){
							logger.warn("Could not find numeric format for format " + textFormat);
							results.addNote("Could not find numeric format for format " + textFormat);
							updateData.hadMetadataErrors = true;
							System.out.println("Warning: new format for OverDrive found " + textFormat);
							continue;
						}
						addFormatStmt.setLong(3, numericFormat);
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
						addIdentifierStmt.setLong(1, updateData.databaseId);
						String[] identifierInfo = curIdentifier.split(":");
						addIdentifierStmt.setString(2, identifierInfo[0]);
						addIdentifierStmt.setString(3, identifierInfo[1]);
						addIdentifierStmt.executeUpdate();
					}
				}
				results.incMetadataChanges();
			} catch (Exception e) {
				logger.info("Error loading meta data for title ", e);
				results.addNote("Error loading meta data for title " + updateData.overDriveId + " " + e.toString());
				updateData.hadMetadataErrors = true;
			}
		}
		try {
			updateProductMetadataStmt.setLong(1, curTime);
			if (updateMetaData){
				updateProductMetadataStmt.setLong(2, curTime);
			}else{
				updateProductMetadataStmt.setLong(2, updateData.lastMetadataChange);
			}
			updateProductMetadataStmt.setLong(3, updateData.databaseId);
			updateProductMetadataStmt.executeUpdate();
		} catch (SQLException e) {
			logger.warn("Error updating product metadata summary ", e);
			results.addNote("Error updating product metadata summary " + updateData.overDriveId + " " + e.toString());
			updateData.hadMetadataErrors = true;
		}
	}

	private OverDriveDBMetaData loadMetadataFromDatabase(long databaseId) {
		OverDriveDBMetaData metaData = new OverDriveDBMetaData();
		try {
			loadMetaDataStmt.setLong(1, databaseId);
			ResultSet metaDataRS = loadMetaDataStmt.executeQuery();
			if (metaDataRS.next()){
				metaData.setId(metaDataRS.getLong("id"));
				metaData.setChecksum(metaDataRS.getLong("checksum"));
				String rawData = metaDataRS.getString("rawData");
				metaData.setHasRawData(rawData != null && rawData.length() > 0);
			}
		} catch (SQLException e) {
			logger.warn("Error loading product metadata ", e);
			results.addNote("Error loading product metadata for " + databaseId + " " + e.toString());
			results.incErrors();
		}
		return metaData;
	}

	private boolean updateOverDriveAvailability(OverDriveRecordInfo overDriveInfo, long databaseId, OverDriveDBInfo dbInfo) throws SocketTimeoutException {
		//Don't need to load availability if we already have availability and the availability was checked within the last hour
		long curTime = new Date().getTime() / 1000;
		if (dbInfo != null && dbInfo.getLastAvailabilityCheck() >= curTime - 60 * 60){
			return false;
		}

		//logger.debug("Loading availability, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");
		boolean availabilityChanged = false;
		for (Long curCollection : overDriveInfo.getCollections()){
			try {
				//Get existing availability
				checkForExistingAvailabilityStmt.setLong(1, databaseId);
				checkForExistingAvailabilityStmt.setLong(2, curCollection);

				ResultSet existingAvailabilityRS = checkForExistingAvailabilityStmt.executeQuery();
				boolean hasExistingAvailability = existingAvailabilityRS.next();

				String apiKey;
				if (curCollection == -1L){
					apiKey = overDriveProductsKey;
				}else{
					apiKey = libToOverDriveAPIKeyMap.get(curCollection);
				}
				if (apiKey == null){
					logger.error("Unable to get api key for collection " + curCollection);
					results.addNote("Unable to get api key for collection " + curCollection);
					results.incErrors();
					continue;
				}
				String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
				WebServiceResponse availabilityResponse = callOverDriveURL(url);
				//404 is a message that availability has been deleted.
				if (availabilityResponse.getResponseCode() != 200 && availabilityResponse.getResponseCode() != 404){
					//We got an error calling the OverDrive API, do nothing.
					logger.info("Error loading API for product " + overDriveInfo.getId());
					logger.info(availabilityResponse.getResponseCode() + ":" + availabilityResponse.getError());
					results.addNote("Error loading API for product " + overDriveInfo.getId());
					results.incErrors();
				}else if (availabilityResponse.getResponse() == null){
					if (hasExistingAvailability){
						deleteAvailabilityStmt.setLong(1, existingAvailabilityRS.getLong("id"));
						deleteAvailabilityStmt.executeUpdate();
						availabilityChanged = true;
					}
				}else{
					JSONObject availability = availabilityResponse.getResponse();
					//If availability is null, it isn't available for this collection
					try {
						boolean available = availability.has("available") && availability.getString("available").equals("true");
						JSONArray allAccounts = availability.getJSONArray("accounts");
						JSONObject accountData = null;
						for (int i = 0; i < allAccounts.length(); i++){
							accountData = allAccounts.getJSONObject(i);
							long accountId = accountData.getLong("id");
							if (curCollection == -1L && accountId == -1L){
								break;
							}else if (curCollection != -1L && accountId != -1L){
								//These don't match because overdrive has it's own number scheme.  There is only one that is not -1 though
								break;
							}else{
								accountData = null;
							}
						}

						if (accountData != null){
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
							if (curCollection == -1) {
								numberOfHolds = availability.getInt("numberOfHolds");
							}else{
								numberOfHolds = 0;
							}
							String availabilityType = availability.getString("availabilityType");
							if (hasExistingAvailability) {
								//Check to see if the availability has changed
								if (available != existingAvailabilityRS.getBoolean("available") ||
												copiesOwned != existingAvailabilityRS.getInt("copiesOwned") ||
												copiesAvailable != existingAvailabilityRS.getInt("copiesAvailable") ||
												numberOfHolds != existingAvailabilityRS.getInt("numberOfHolds") ||
												!availabilityType.equals(existingAvailabilityRS.getString("availabilityType"))
												) {
									updateAvailabilityStmt.setBoolean(1, available);
									updateAvailabilityStmt.setInt(2, copiesOwned);
									updateAvailabilityStmt.setInt(3, copiesAvailable);
									updateAvailabilityStmt.setInt(4, numberOfHolds);
									updateAvailabilityStmt.setString(5, availabilityType);
									long existingId = existingAvailabilityRS.getLong("id");
									updateAvailabilityStmt.setLong(6, existingId);
									updateAvailabilityStmt.executeUpdate();
									availabilityChanged = true;
								}
							} else {
								addAvailabilityStmt.setLong(1, databaseId);
								addAvailabilityStmt.setLong(2, curCollection);
								addAvailabilityStmt.setBoolean(3, available);
								addAvailabilityStmt.setInt(4, copiesOwned);
								addAvailabilityStmt.setInt(5, copiesAvailable);
								addAvailabilityStmt.setInt(6, numberOfHolds);
								addAvailabilityStmt.setString(7, availabilityType);
								addAvailabilityStmt.executeUpdate();
								availabilityChanged = true;
							}
						}else{
							if (hasExistingAvailability){
								//Delete availability from the database if it used to exist since there is none now

								long existingId = existingAvailabilityRS.getLong("id");
								deleteAvailabilityStmt.setLong(1, existingId);
								deleteAvailabilityStmt.executeUpdate();
								availabilityChanged = true;
							}
						}

					} catch (JSONException e) {
						logger.info("JSON Error loading availability for title ", e);
						results.addNote("JSON Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
						results.incErrors();
					}
				}
			} catch (SQLException e) {
				logger.info("SQL Error loading availability for title ", e);
				results.addNote("SQL Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
		//Update the product to indicate that we checked availability
		try {
			updateProductAvailabilityStmt.setLong(1, curTime);
			if (dbInfo == null || availabilityChanged){
				updateProductAvailabilityStmt.setLong(2, curTime);
				results.incAvailabilityChanges();
				results.saveResults();
			}else{
				updateProductAvailabilityStmt.setLong(2, dbInfo.getLastAvailabilityChange());
			}
			updateProductAvailabilityStmt.setLong(3, databaseId);
			updateProductAvailabilityStmt.executeUpdate();
		} catch (SQLException e) {
			logger.warn("Error updating product availability status ", e);
			results.addNote("Error updating product availability status " + overDriveInfo.getId() + " " + e.toString());
			results.incErrors();
		}
		return availabilityChanged;
	}

	private void updateOverDriveAvailabilityBatchV1(long libraryId, List<MetaAvailUpdateData> productsToUpdateBatch, HashMap<String, SharedStats> sharedStats) throws SocketTimeoutException {
		//logger.debug("Loading availability, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");
		long curTime = new Date().getTime() / 1000;
		String apiKey;
		apiKey = libToOverDriveAPIKeyMap.get(libraryId);
		for (MetaAvailUpdateData curProduct : productsToUpdateBatch){
			//If we have an error already don't bother
			if (!curProduct.hadAvailabilityErrors) {
				String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/products/" + curProduct.overDriveId + "/availability";
				int numTries = 0;
				WebServiceResponse availabilityResponse = null;
				while (numTries < 3) {
					try {
						availabilityResponse = callOverDriveURL(url);
						break;
					} catch (SocketTimeoutException e) {
						numTries++;
					}
				}

				if (availabilityResponse == null || availabilityResponse.getResponseCode() != 200) {
					//Doesn't exist in this collection, skip to the next.
					if (availabilityResponse != null) {
						if (availabilityResponse.getResponseCode() == 404 || availabilityResponse.getResponseCode() == 500) {
							//No availability for this product
							deleteOverDriveAvailability(curProduct, libraryId);
						} else {
							logger.error("Did not get availability (" + availabilityResponse.getResponseCode() + ") for batch " + url);
							curProduct.hadAvailabilityErrors = true;
						}
					} else {
						logger.error("Did not get availability null response for batch " + url);
						curProduct.hadAvailabilityErrors = true;
					}
				} else {
					JSONObject availability = availabilityResponse.getResponse();
					updateDBAvailabilityForProductV1(libraryId, curProduct, availability, curTime, sharedStats.get(curProduct.overDriveId));
				}
			}else{
				logger.debug("Not checking availability because we got an error earlier");
			}
		}
	}

	private void deleteOverDriveAvailability(MetaAvailUpdateData curProduct, long libraryId) {
		try {
			//No availability for this product
			checkForExistingAvailabilityStmt.setLong(1, curProduct.databaseId);
			checkForExistingAvailabilityStmt.setLong(2, libraryId);
			ResultSet existingAvailabilityRS = checkForExistingAvailabilityStmt.executeQuery();
			boolean hasExistingAvailability = existingAvailabilityRS.next();
			if (hasExistingAvailability){
				deleteAvailabilityStmt.setLong(1, existingAvailabilityRS.getLong("id"));
				deleteAvailabilityStmt.executeUpdate();
			}
		}catch (Exception e){
			logger.error("Error loading availability within batch", e);
		}
	}

	private void updateOverDriveAvailabilityBatchV2(long libraryId, List<MetaAvailUpdateData> productsToUpdateBatch, HashMap<String, Integer> copiesOwnedByShared, HashMap<String, Integer> copiesAvailableInShared) throws SocketTimeoutException {
		//logger.debug("Loading availability, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");
		long curTime = new Date().getTime() / 1000;
		String apiKey;
		apiKey = libToOverDriveAPIKeyMap.get(libraryId);
		String url = "https://api.overdrive.com/v2/collections/" + apiKey + "/availability?products=";
		int numAdded = 0;
		ArrayList<MetaAvailUpdateData> productsToUpdateClone = new ArrayList<>();
		productsToUpdateClone.addAll(productsToUpdateBatch);
		for (MetaAvailUpdateData curProduct : productsToUpdateBatch){
			if (numAdded > 0){
				url += ",";
			}
			url += curProduct.overDriveId;
			numAdded++;
		}

		int numTries = 0;
		WebServiceResponse availabilityResponse = null;
		while (numTries < 3){
			try{
				availabilityResponse = callOverDriveURL(url);
				break;
			}catch (SocketTimeoutException e){
				numTries++;
			}
		}

		if (availabilityResponse == null || availabilityResponse.getResponseCode() != 200){
			//Doesn't exist in this collection, skip to the next.
			if (availabilityResponse != null){
				logger.error("Did not get availability (" + availabilityResponse.getResponseCode() + ") for batch " + url);
			}else{
				logger.error("Did not get availability null response for batch " + url);
			}

			for (MetaAvailUpdateData curProduct : productsToUpdateClone){
				curProduct.hadAvailabilityErrors = true;
			}
		}else{
			JSONObject bulkResponse = availabilityResponse.getResponse();
			if (bulkResponse.has("availability")){
				try {
					JSONArray availabilityArray = bulkResponse.getJSONArray("availability");
					for (int i = 0; i < availabilityArray.length(); i++) {
						JSONObject availability = availabilityArray.getJSONObject(i);
						//Get the product to update
						for (MetaAvailUpdateData curProduct : productsToUpdateClone){
							if (availability.has("titleId") && availability.getLong("titleId") == curProduct.crossRefId){
								updateDBAvailabilityForProductV2(libraryId, curProduct, availability, curTime);
								productsToUpdateClone.remove(curProduct);
								break;
							}else if (availability.has("reserveId") && availability.getString("reserveId").equals(curProduct.overDriveId)) {
								updateDBAvailabilityForProductV2(libraryId, curProduct, availability, curTime);
								productsToUpdateClone.remove(curProduct);
								break;
							}
						}
					}

					//Anything that is still left should have availability removed from the database
					for (MetaAvailUpdateData curProduct : productsToUpdateClone){
						deleteOverDriveAvailability(curProduct, libraryId);
					}
				}catch (Exception e){
					logger.error("Error loading availability within batch", e);
				}
			}
		}
	}

	private void updateDBAvailabilityForProductV1(long libraryId, MetaAvailUpdateData curProduct, JSONObject availability, long curTime, SharedStats sharedStats){
		boolean availabilityChanged = false;
		try {
			//Get existing availability
			checkForExistingAvailabilityStmt.setLong(1, curProduct.databaseId);
			checkForExistingAvailabilityStmt.setLong(2, libraryId);

			ResultSet existingAvailabilityRS = checkForExistingAvailabilityStmt.executeQuery();
			boolean hasExistingAvailability = existingAvailabilityRS.next();

			//If availability is null, it isn't available for this collection
			try {
				boolean available = availability.has("available") && availability.getString("available").equals("true");

				int copiesOwned = availability.getInt("copiesOwned");
				int copiesAvailable;
				if (availability.has("copiesAvailable")) {
					copiesAvailable = availability.getInt("copiesAvailable");
					if (copiesAvailable < 0){
						copiesAvailable = 0;
					}
				} else {
					logger.warn("copiesAvailable was not provided for library " + libraryId + " title " + curProduct.overDriveId);
					copiesAvailable = 0;
				}
				if (libraryId == -1){
					sharedStats.copiesOwnedByShared = copiesOwned;
					sharedStats.copiesAvailableInShared = copiesAvailable;
				}else{
					if (copiesOwned < sharedStats.copiesOwnedByShared){
						logger.warn("Copies owned " + copiesOwned + " was less than copies owned by the shared collection " + sharedStats.copiesOwnedByShared + " for libraryId " + libraryId + " product " + curProduct.overDriveId);
						copiesOwned = 0;
						curProduct.hadAvailabilityErrors = true;
					}else{
						copiesOwned -= sharedStats.copiesOwnedByShared;
					}
					if (copiesAvailable < sharedStats.copiesAvailableInShared){
						logger.warn("Copies available " + copiesAvailable + " was less than copies available in shared collection " + sharedStats.copiesAvailableInShared + " for libraryId " + libraryId + " product " + curProduct.overDriveId);
						copiesAvailable = 0;
						curProduct.hadAvailabilityErrors = true;
					}else{
						copiesAvailable -= sharedStats.copiesAvailableInShared;
					}
				}

				boolean shared = false;
				if (availability.has("shared")) {
					shared = availability.getBoolean("shared");
				}
				//Don't restrict this to only the library since it could be owned by an advantage library only.
				int numberOfHolds;
				if (libraryId == -1 || sharedStats.copiesOwnedByShared > 0) {
					numberOfHolds = availability.getInt("numberOfHolds");
				}else{
					numberOfHolds = 0;
				}
				String availabilityType = availability.getString("availabilityType");
				if (hasExistingAvailability) {
					//Check to see if the availability has changed
					if (available != existingAvailabilityRS.getBoolean("available") ||
									copiesOwned != existingAvailabilityRS.getInt("copiesOwned") ||
									copiesAvailable != existingAvailabilityRS.getInt("copiesAvailable") ||
									numberOfHolds != existingAvailabilityRS.getInt("numberOfHolds") ||
									!availabilityType.equals(existingAvailabilityRS.getString("availabilityType"))
									) {
						updateAvailabilityStmt.setBoolean(1, available);
						updateAvailabilityStmt.setInt(2, copiesOwned);
						updateAvailabilityStmt.setInt(3, copiesAvailable);
						updateAvailabilityStmt.setInt(4, numberOfHolds);
						updateAvailabilityStmt.setString(5, availabilityType);
						long existingId = existingAvailabilityRS.getLong("id");
						updateAvailabilityStmt.setLong(6, existingId);
						updateAvailabilityStmt.executeUpdate();
						availabilityChanged = true;
					}
				} else {
					addAvailabilityStmt.setLong(1, curProduct.databaseId);
					addAvailabilityStmt.setLong(2, libraryId);
					addAvailabilityStmt.setBoolean(3, available);
					addAvailabilityStmt.setInt(4, copiesOwned);
					addAvailabilityStmt.setInt(5, copiesAvailable);
					addAvailabilityStmt.setInt(6, numberOfHolds);
					addAvailabilityStmt.setString(7, availabilityType);
					addAvailabilityStmt.executeUpdate();
					availabilityChanged = true;
				}

			} catch (JSONException e) {
				logger.info("Error loading availability for title ", e);
				results.addNote("Error loading availability for title " + curProduct.overDriveId + " " + e.toString());
				results.incErrors();
				curProduct.hadAvailabilityErrors = true;
			}
		} catch (SQLException e) {
			logger.info("Error loading availability for title ", e);
			results.addNote("Error loading availability for title " + curProduct.overDriveId + " " + e.toString());
			results.incErrors();
			curProduct.hadAvailabilityErrors = true;
		}

		//Update the product to indicate that we checked availability
		try {
			updateProductAvailabilityStmt.setLong(1, curTime);
			if (availabilityChanged){
				updateProductAvailabilityStmt.setLong(2, curTime);
				results.incAvailabilityChanges();
				results.saveResults();
			}else{
				updateProductAvailabilityStmt.setLong(2, curProduct.lastAvailabilityChange);
			}
			updateProductAvailabilityStmt.setLong(3, curProduct.databaseId);
			updateProductAvailabilityStmt.executeUpdate();
		} catch (SQLException e) {
			logger.warn("Error updating product availability status ", e);
			results.addNote("Error updating product availability status " + curProduct.overDriveId + " " + e.toString());
			results.incErrors();
			curProduct.hadAvailabilityErrors = true;
		}
	}

	private void updateDBAvailabilityForProductV2(long libraryId, MetaAvailUpdateData curProduct, JSONObject availability, long curTime){
		boolean availabilityChanged = false;
		try {
			//Get existing availability
			checkForExistingAvailabilityStmt.setLong(1, curProduct.databaseId);
			checkForExistingAvailabilityStmt.setLong(2, libraryId);

			ResultSet existingAvailabilityRS = checkForExistingAvailabilityStmt.executeQuery();
			boolean hasExistingAvailability = existingAvailabilityRS.next();

			//If availability is null, it isn't available for this collection
			try {
				boolean available = availability.has("available") && availability.getString("available").equals("true");
				JSONArray allAccounts = availability.getJSONArray("accounts");
				JSONObject accountData = null;
				for (int i = 0; i < allAccounts.length(); i++) {
					accountData = allAccounts.getJSONObject(i);
					long accountId = accountData.getLong("id");
					if (libraryId == -1L && accountId == -1L) {
						break;
					} else if (libraryId != -1L && accountId != -1L) {
						//These don't match because overdrive has it's own number scheme.  There is only one that is not -1 though
						break;
					} else {
						accountData = null;
					}
				}

				if (accountData != null) {
					int copiesOwned = accountData.getInt("copiesOwned");
					int copiesAvailable;
					if (accountData.has("copiesAvailable")) {
						copiesAvailable = accountData.getInt("copiesAvailable");
					} else {
						logger.info("copiesAvailable was not provided for library " + libraryId + " title " + curProduct.overDriveId);
						copiesAvailable = 0;
					}
					boolean shared = false;
					if (accountData.has("shared")) {
						shared = accountData.getBoolean("shared");
					}
					int numberOfHolds;
					if (libraryId == -1) {
						numberOfHolds = availability.getInt("numberOfHolds");
					} else {
						numberOfHolds = 0;
					}
					String availabilityType = availability.getString("availabilityType");
					if (hasExistingAvailability) {
						//Check to see if the availability has changed
						if (available != existingAvailabilityRS.getBoolean("available") ||
										copiesOwned != existingAvailabilityRS.getInt("copiesOwned") ||
										copiesAvailable != existingAvailabilityRS.getInt("copiesAvailable") ||
										numberOfHolds != existingAvailabilityRS.getInt("numberOfHolds") ||
										!availabilityType.equals(existingAvailabilityRS.getString("availabilityType"))
										) {
							updateAvailabilityStmt.setBoolean(1, available);
							updateAvailabilityStmt.setInt(2, copiesOwned);
							updateAvailabilityStmt.setInt(3, copiesAvailable);
							updateAvailabilityStmt.setInt(4, numberOfHolds);
							updateAvailabilityStmt.setString(5, availabilityType);
							long existingId = existingAvailabilityRS.getLong("id");
							updateAvailabilityStmt.setLong(6, existingId);
							updateAvailabilityStmt.executeUpdate();
							availabilityChanged = true;
						}
					} else {
						addAvailabilityStmt.setLong(1, curProduct.databaseId);
						addAvailabilityStmt.setLong(2, libraryId);
						addAvailabilityStmt.setBoolean(3, available);
						addAvailabilityStmt.setInt(4, copiesOwned);
						addAvailabilityStmt.setInt(5, copiesAvailable);
						addAvailabilityStmt.setInt(6, numberOfHolds);
						addAvailabilityStmt.setString(7, availabilityType);
						addAvailabilityStmt.executeUpdate();
						availabilityChanged = true;
					}
				} else {
					if (hasExistingAvailability) {
						//Delete availability from the database if it used to exist since there is none now

						long existingId = existingAvailabilityRS.getLong("id");
						deleteAvailabilityStmt.setLong(1, existingId);
						deleteAvailabilityStmt.executeUpdate();
						availabilityChanged = true;
					}
				}

			} catch (JSONException e) {
				logger.info("Error loading availability for title ", e);
				results.addNote("Error loading availability for title " + curProduct.overDriveId + " " + e.toString());
				results.incErrors();
				curProduct.hadAvailabilityErrors = true;
			}
		} catch (SQLException e) {
			logger.info("Error loading availability for title ", e);
			results.addNote("Error loading availability for title " + curProduct.overDriveId + " " + e.toString());
			results.incErrors();
			curProduct.hadAvailabilityErrors = true;
		}

		//Update the product to indicate that we checked availability
		try {
			updateProductAvailabilityStmt.setLong(1, curTime);
			if (availabilityChanged){
				updateProductAvailabilityStmt.setLong(2, curTime);
				results.incAvailabilityChanges();
				results.saveResults();
			}else{
				updateProductAvailabilityStmt.setLong(2, curProduct.lastAvailabilityChange);
			}
			updateProductAvailabilityStmt.setLong(3, curProduct.databaseId);
			updateProductAvailabilityStmt.executeUpdate();
		} catch (SQLException e) {
			logger.warn("Error updating product availability status ", e);
			results.addNote("Error updating product availability status " + curProduct.overDriveId + " " + e.toString());
			results.incErrors();
			curProduct.hadAvailabilityErrors = true;
		}
	}

	private WebServiceResponse callOverDriveURL(String overdriveUrl) throws SocketTimeoutException {
		WebServiceResponse webServiceResponse = new WebServiceResponse();
		if (connectToOverDriveAPI(false)) {
			//Connect to the API to get our token
			HttpURLConnection conn;
			StringBuilder response = new StringBuilder();
			try {
				URL emptyIndexURL = new URL(overdriveUrl);
				conn = (HttpURLConnection) emptyIndexURL.openConnection();
				if (conn instanceof HttpsURLConnection) {
					HttpsURLConnection sslConn = (HttpsURLConnection) conn;
					sslConn.setHostnameVerifier(new HostnameVerifier() {

						@Override
						public boolean verify(String hostname, SSLSession session) {
							//Do not verify host names
							return true;
						}
					});
				}
				conn.setRequestMethod("GET");
				conn.setRequestProperty("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
				conn.setReadTimeout(30000);
				conn.setConnectTimeout(30000);
				webServiceResponse.setResponseCode(conn.getResponseCode());

				if (conn.getResponseCode() == 200) {
					// Get the response
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.debug("  Finished reading response");
					rd.close();
					String responseString = response.toString();
					if (responseString.equals("null")) {
						webServiceResponse.setResponse(null);
					} else {
						webServiceResponse.setResponse(new JSONObject(response.toString()));
					}
				} else {
					// Get any errors
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.info("Received error " + conn.getResponseCode() + " connecting to overdrive API " + response.toString());
					//logger.debug("  Finished reading response");
					//logger.debug(response.toString());
					webServiceResponse.setError(response.toString());

					rd.close();
					hadTimeoutsFromOverDrive = true;
				}
			} catch (SocketTimeoutException toe){
				throw toe;
			} catch (Exception e) {
				logger.debug("Error loading data from overdrive API ", e);
				hadTimeoutsFromOverDrive = true;
			}
		}else{
			logger.error("Unable to connect to API");
		}

		return webServiceResponse;
	}

	private boolean connectToOverDriveAPI(boolean getNewToken) throws SocketTimeoutException {
		//Check to see if we already have a valid token
		if (overDriveAPIToken != null && !getNewToken){
			if (overDriveAPIExpiration - new Date().getTime() > 0){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has exipred");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection) {
				HttpsURLConnection sslConn = (HttpsURLConnection) conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {

					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
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

			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
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

	private void updatePartialExtractRunning(boolean running) {
		//Update the last grouping time in the variables table
		try {
			if (partialExtractRunningVariableId != null) {
				PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setString(1, Boolean.toString(running));
				updateVariableStmt.setLong(2, partialExtractRunningVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else {
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('partial_overdrive_extract_running', ?)", Statement.RETURN_GENERATED_KEYS);
				insertVariableStmt.setString(1, Boolean.toString(running));
				insertVariableStmt.executeUpdate();
				ResultSet generatedKeys = insertVariableStmt.getGeneratedKeys();
				if (generatedKeys.next()){
					partialExtractRunningVariableId = generatedKeys.getLong(1);
				}
				insertVariableStmt.close();
			}
		} catch (Exception e) {
			logger.error("Error setting partial extract running", e);
		}
	}
}
