package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.MarcStreamReader;
import org.marc4j.marc.*;

import java.io.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Pattern;

/**
 * Processes data that was exported from the ILS.
 *
 * Pika
 * User: Mark Noble
 * Date: 11/26/13
 * Time: 9:30 AM
 */
abstract class IlsRecordProcessor extends MarcRecordProcessor {
	protected boolean fullReindex;
	private String individualMarcPath;
	String marcPath;
	String profileType;

	private String recordNumberTag;
	String itemTag;
	String formatSource;
	String specifiedFormat;
	String specifiedFormatCategory;
	int specifiedFormatBoost;
	char formatSubfield;
	char barcodeSubfield;
	char statusSubfieldIndicator;
	Pattern statusesToSuppressPattern = null;
	private Pattern nonHoldableStatuses;
	char shelvingLocationSubfield;
	char collectionSubfield;
	char dueDateSubfield;
	SimpleDateFormat dueDateFormatter;
	private char lastCheckInSubfield;
	private String lastCheckInFormat;
	private char dateCreatedSubfield;
	private String dateAddedFormat;
	char locationSubfieldIndicator;
	private Pattern nonHoldableLocations;
	Pattern locationsToSuppressPattern = null;
	Pattern collectionsToSuppressPattern = null;
	char subLocationSubfield;
	char iTypeSubfield;
	private Pattern nonHoldableITypes;
	boolean useEContentSubfield = false;
	char eContentSubfieldIndicator;
	private char lastYearCheckoutSubfield;
	private char ytdCheckoutSubfield;
	private char totalCheckoutSubfield;
	boolean useICode2Suppression;
	char iCode2Subfield;
	private boolean useItemBasedCallNumbers;
	private char callNumberPrestampSubfield;
	private char callNumberSubfield;
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volumeSubfield;
	char itemRecordNumberSubfieldIndicator;
	private char itemUrlSubfieldIndicator;
	boolean suppressItemlessBibs;

	//Fields for loading order information
	private String orderTag;
	private char orderLocationSubfield;
	private char singleOrderLocationSubfield;
	private char orderCopiesSubfield;
	private char orderStatusSubfield;
	private char orderCode3Subfield;

	private int numCharsToCreateFolderFrom;
	private boolean createFolderFromLeadingCharacters;

	private HashMap<String, Integer> numberOfHoldsByIdentifier = new HashMap<>();

	HashMap<String, TranslationMap> translationMaps = new HashMap<>();
	private ArrayList<TimeToReshelve> timesToReshelve = new ArrayList<>();

	IlsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, logger);
		this.fullReindex = fullReindex;
		//String marcRecordPath = configIni.get("Reindex", "marcPath");
		try {
			profileType = indexingProfileRS.getString("name");
			individualMarcPath = indexingProfileRS.getString("individualMarcPath");
			marcPath = indexingProfileRS.getString("marcPath");
			numCharsToCreateFolderFrom         = indexingProfileRS.getInt("numCharsToCreateFolderFrom");
			createFolderFromLeadingCharacters  = indexingProfileRS.getBoolean("createFolderFromLeadingCharacters");

			recordNumberTag = indexingProfileRS.getString("recordNumberTag");
			suppressItemlessBibs = indexingProfileRS.getBoolean("suppressItemlessBibs");

			itemTag = indexingProfileRS.getString("itemTag");
			itemRecordNumberSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemRecordNumber");

			callNumberPrestampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPrestamp");
			callNumberSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumber");
			callNumberCutterSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberCutter");
			callNumberPoststampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPoststamp");

			locationSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "location");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableLocations");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableLocations = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable locations", e);
			}
			subLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "subLocation");
			shelvingLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "shelvingLocation");
			collectionSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "collection");
			String locationsToSuppress = indexingProfileRS.getString("locationsToSuppress");
			if (locationsToSuppress.length() > 0){
				locationsToSuppressPattern = Pattern.compile(locationsToSuppress);
			}

			String collectionsToSuppress = indexingProfileRS.getString("collectionsToSuppress");
			if (collectionsToSuppress.length() > 0){
				collectionsToSuppressPattern = Pattern.compile(collectionsToSuppress);
			}

			itemUrlSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemUrl");

			formatSource = indexingProfileRS.getString("formatSource");
			specifiedFormat = indexingProfileRS.getString("specifiedFormat");
			specifiedFormatCategory = indexingProfileRS.getString("specifiedFormatCategory");
			specifiedFormatBoost = indexingProfileRS.getInt("specifiedFormatBoost");
			formatSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "format");
			barcodeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "barcode");
			statusSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "status");
			String statusesToSuppress = indexingProfileRS.getString("statusesToSuppress");
			if (statusesToSuppress.length() > 0){
				statusesToSuppressPattern = Pattern.compile(statusesToSuppress);
			}

			try {
				String pattern = indexingProfileRS.getString("nonHoldableStatuses");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableStatuses = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable statuses", e);
			}

			dueDateSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dueDate");
			String dueDateFormat = indexingProfileRS.getString("dueDateFormat");
			if (dueDateFormat.length() > 0) {
				dueDateFormatter = new SimpleDateFormat(dueDateFormat);
			}

			ytdCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "yearToDateCheckouts");
			lastYearCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "lastYearCheckouts");
			totalCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "totalCheckouts");

			iTypeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "iType");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableITypes");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableITypes = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable iTypes", e);
			}

			dateCreatedSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dateCreated");
			dateAddedFormat = indexingProfileRS.getString("dateCreatedFormat");

			lastCheckInSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "lastCheckinDate");
			lastCheckInFormat = indexingProfileRS.getString("lastCheckinFormat");

			iCode2Subfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "iCode2");
			useICode2Suppression = indexingProfileRS.getBoolean("useICode2Suppression");

			eContentSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "eContentDescriptor");
			useEContentSubfield = eContentSubfieldIndicator != ' ';

			useItemBasedCallNumbers = indexingProfileRS.getBoolean("useItemBasedCallNumbers");
			volumeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "volume");


			orderTag = indexingProfileRS.getString("orderTag");
			orderLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderLocation");
			singleOrderLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderLocationSingle");
			orderCopiesSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderCopies");
			orderStatusSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderStatus");
			orderCode3Subfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderCode3");

			//loadAvailableItemBarcodes(marcRecordPath, logger);
			loadHoldsByIdentifier(vufindConn, logger);

			loadTranslationMapsForProfile(vufindConn, indexingProfileRS.getLong("id"));

			loadTimeToReshelve(vufindConn, indexingProfileRS.getLong("id"));
		}catch (Exception e){
			logger.error("Error loading indexing profile information from database", e);
		}
	}

	private void loadTimeToReshelve(Connection vufindConn, long id) throws SQLException{
		PreparedStatement getTimesToReshelveStmt = vufindConn.prepareStatement("SELECT * from time_to_reshelve WHERE indexingProfileId = ? ORDER by weight");
		getTimesToReshelveStmt.setLong(1, id);
		ResultSet timesToReshelveRS = getTimesToReshelveStmt.executeQuery();
		while (timesToReshelveRS.next()){
			TimeToReshelve timeToReshelve = new TimeToReshelve();
			timeToReshelve.setLocations(timesToReshelveRS.getString("locations"));
			timeToReshelve.setNumHoursToOverride(timesToReshelveRS.getLong("numHoursToOverride"));
			timeToReshelve.setStatus(timesToReshelveRS.getString("status"));
			timeToReshelve.setGroupedStatus(timesToReshelveRS.getString("groupedStatus"));
			timesToReshelve.add(timeToReshelve);
		}
	}
	private void loadTranslationMapsForProfile(Connection vufindConn, long id) throws SQLException{
		PreparedStatement getTranslationMapsStmt = vufindConn.prepareStatement("SELECT * from translation_maps WHERE indexingProfileId = ?");
		PreparedStatement getTranslationMapValuesStmt = vufindConn.prepareStatement("SELECT * from translation_map_values WHERE translationMapId = ?");
		getTranslationMapsStmt.setLong(1, id);
		ResultSet translationsMapRS = getTranslationMapsStmt.executeQuery();
		while (translationsMapRS.next()){
			TranslationMap map = new TranslationMap(profileType, translationsMapRS.getString("name"), fullReindex, translationsMapRS.getBoolean("usesRegularExpressions"), logger);
			Long translationMapId = translationsMapRS.getLong("id");
			getTranslationMapValuesStmt.setLong(1, translationMapId);
			ResultSet translationMapValuesRS = getTranslationMapValuesStmt.executeQuery();
			while (translationMapValuesRS.next()){
				map.addValue(translationMapValuesRS.getString("value"), translationMapValuesRS.getString("translation"));
			}
			translationMaps.put(map.getMapName(), map);
		}
	}

	private void loadHoldsByIdentifier(Connection vufindConn, Logger logger) {
		try{
			PreparedStatement loadHoldsStmt = vufindConn.prepareStatement("SELECT ilsId, numHolds from ils_hold_summary", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet holdsRS = loadHoldsStmt.executeQuery();
			while (holdsRS.next()) {
				numberOfHoldsByIdentifier.put(holdsRS.getString("ilsId"), holdsRS.getInt("numHolds"));
			}

		} catch (Exception e){
			logger.error("Unable to load hold data", e);
		}
	}

	@Override
	public void processRecord(GroupedWorkSolr groupedWork, String identifier){
		Record record = loadMarcRecordFromDisk(identifier);

		if (record != null){
			try{
				updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
			}catch (Exception e) {
				logger.error("Error updating solr based on marc record", e);
			}
		//No need to warn here, we already have a warning when getting it
		//}else{
			//logger.info("Could not load marc record from disk for " + identifier);
		}
	}

	private Record loadMarcRecordFromDisk(String identifier) {
		Record record = null;
		String shortId = identifier.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		String individualFilename = getFileForIlsRecord(identifier);
		try {
			byte[] fileContents = Util.readFileBytes(individualFilename);
			//FileInputStream inputStream = new FileInputStream(individualFile);
			InputStream inputStream = new ByteArrayInputStream(fileContents);
			//Don't need to use a permissive reader here since we've written good individual MARCs as part of record grouping
			//Actually we do need to since we can still get MARC records over the max length.
			MarcReader marcReader = new MarcPermissiveStreamReader(inputStream, true, false, "UTF-8");
			if (marcReader.hasNext()) {
				record = marcReader.next();
			}
			inputStream.close();
		}catch (FileNotFoundException fe){
			logger.warn("Could not find MARC record at " + individualFilename + " for " + identifier);
		} catch (Exception e) {
			logger.error("Error reading data from ils file " + individualFilename, e);
		}
		return record;
	}

	private String getFileForIlsRecord(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}

		String subFolderName;
		if (createFolderFromLeadingCharacters){
			subFolderName        = shortId.substring(0, numCharsToCreateFolderFrom);
		}else{
			subFolderName        = shortId.substring(0, shortId.length() - numCharsToCreateFolderFrom);
		}

		String basePath           = individualMarcPath + "/" + subFolderName;
		return basePath + "/" + shortId + ".mrc";
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//For ILS Records, we can create multiple different records, one for print and order items,
		//and one or more for eContent items.
		HashSet<RecordInfo> allRelatedRecords = new HashSet<>();

		try{
			//If the entire bib is suppressed, update stats and bail out now.
			if (isBibSuppressed(record)){
				logger.debug("Bib record " + identifier + " is suppressed skipping");
				return;
			}

			// Let's first look for the print/order record
			RecordInfo recordInfo = groupedWork.addRelatedRecord(profileType, identifier);
			logger.debug("Added record for " + identifier + " work now has " + groupedWork.getNumRecords() + " records");
			loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record);
			loadOnOrderItems(groupedWork, recordInfo, record, recordInfo.getNumPrintCopies() > 0);
			//If we don't get anything remove the record we just added
			if (checkIfBibShouldBeRemovedAsItemless(recordInfo)) {
				groupedWork.removeRelatedRecord(recordInfo);
				logger.debug("Removing related print record for " + identifier + " because there are no print copies, no on order copies and suppress itemless bibs is on");
			}else{
				allRelatedRecords.add(recordInfo);
			}

			//Since print formats are loaded at the record level, do it after we have loaded items
			loadPrintFormatInformation(recordInfo, record);

			//Now look for any eContent that is defined within the ils
			List<RecordInfo> econtentRecords = loadUnsuppressedEContentItems(groupedWork, identifier, record);
			allRelatedRecords.addAll(econtentRecords);

			//Do updates based on the overall bib (shared regardless of scoping)
			String primaryFormat = null;
			for (RecordInfo ilsRecord : allRelatedRecords) {
				primaryFormat = ilsRecord.getPrimaryFormat();
				if (primaryFormat != null){
					break;
				}
			}
			if (primaryFormat == null/* || primaryFormat.equals("Unknown")*/) {
				primaryFormat = "Unknown";
				//logger.info("No primary format for " + recordInfo.getRecordIdentifier() + " found setting to unknown to load standard marc data");
			}
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, recordInfo.getRelatedItems(), identifier, primaryFormat);

			//Special processing for ILS Records
			String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(record, "520a"));
			for (RecordInfo ilsRecord : allRelatedRecords) {
				String primaryFormatForRecord = ilsRecord.getPrimaryFormat();
				if (primaryFormatForRecord == null){
					primaryFormatForRecord = "Unknown";
				}
				groupedWork.addDescription(fullDescription, primaryFormatForRecord);
			}
			loadEditions(groupedWork, record, allRelatedRecords);
			loadPhysicalDescription(groupedWork, record, allRelatedRecords);
			loadLanguageDetails(groupedWork, record, allRelatedRecords, identifier);
			loadPublicationDetails(groupedWork, record, allRelatedRecords);
			loadSystemLists(groupedWork, record);

			if (record.getControlNumber() != null){
				groupedWork.addKeywords(record.getControlNumber());
			}

			//Do updates based on items
			loadPopularity(groupedWork, identifier);
			groupedWork.addBarcodes(MarcUtil.getFieldList(record, itemTag + barcodeSubfield));

			loadOrderIds(groupedWork, record);

			int numPrintItems = recordInfo.getNumPrintCopies();

			numPrintItems = checkForNonSuppressedItemlessBib(numPrintItems);
			groupedWork.addHoldings(numPrintItems + recordInfo.getNumCopiesOnOrder());

			for (ItemInfo curItem : recordInfo.getRelatedItems()){
				String itemIdentifier = curItem.getItemIdentifier();
				if (itemIdentifier != null && itemIdentifier.length() > 0) {
					groupedWork.addAlternateId(itemIdentifier);
				}
			}

			for (RecordInfo recordInfoTmp: allRelatedRecords) {
				scopeItems(recordInfoTmp, groupedWork, record);
			}
		}catch (Exception e){
			logger.error("Error updating grouped work " + groupedWork.getId() + " for MARC record with identifier " + identifier, e);
		}
	}

	boolean checkIfBibShouldBeRemovedAsItemless(RecordInfo recordInfo) {
		return recordInfo.getNumPrintCopies() == 0 && recordInfo.getNumCopiesOnOrder() == 0 && suppressItemlessBibs;
	}

	/**
	 * Check to see if we should increment the number of print items by one.   For bibs without items that should not be
	 * suppressed.
	 *
	 * @param numPrintItems the number of print titles on the record
	 * @return number of items that should be counted
	 */
	private int checkForNonSuppressedItemlessBib(int numPrintItems) {
		if (!suppressItemlessBibs && numPrintItems == 0){
			numPrintItems = 1;
		}
		return numPrintItems;
	}

	protected boolean isBibSuppressed(Record record) {
		return false;
	}

	protected void loadSystemLists(GroupedWorkSolr groupedWork, Record record) {
		//By default, do nothing
	}

	protected void loadOnOrderItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		List<DataField> orderFields = MarcUtil.getDataFields(record, orderTag);
		for (DataField curOrderField : orderFields){
			//Check here to make sure the order item is valid before doing further processing.
			String status = "";
			if (curOrderField.getSubfield(orderStatusSubfield) != null) {
				status = curOrderField.getSubfield(orderStatusSubfield).getData();
			}
			String code3 = null;
			if (orderCode3Subfield != ' ' && curOrderField.getSubfield(orderCode3Subfield) != null){
				code3 = curOrderField.getSubfield(orderCode3Subfield).getData();
			}

			if (isOrderItemValid(status, code3)){
				int copies = 0;
				//If the location is multi, we actually have several records that should be processed separately
				List<Subfield> detailedLocationSubfield = curOrderField.getSubfields(orderLocationSubfield);
				if (detailedLocationSubfield.size() == 0){
					//Didn't get detailed locations
					if (curOrderField.getSubfield(orderCopiesSubfield) != null){
						copies = Integer.parseInt(curOrderField.getSubfield(orderCopiesSubfield).getData());
					}
					String locationCode = "multi";
					if (curOrderField.getSubfield(singleOrderLocationSubfield) != null){
						locationCode = curOrderField.getSubfield(singleOrderLocationSubfield).getData().trim();
					}
					createAndAddOrderItem(recordInfo, curOrderField, locationCode, copies);
				} else {
					for (Subfield curLocationSubfield : detailedLocationSubfield) {
						String curLocation = curLocationSubfield.getData();
						if (curLocation.startsWith("(")) {
							//There are multiple copies for this location
							String tmpLocation = curLocation;
							try {
								copies = Integer.parseInt(tmpLocation.substring(1, tmpLocation.indexOf(")")));
								curLocation = tmpLocation.substring(tmpLocation.indexOf(")") + 1).trim();
							} catch (StringIndexOutOfBoundsException e) {
								logger.error("Error parsing copies and location for order item " + tmpLocation);
							}
						} else {
							//If we only get one location in the detailed copies, we need to read the copies subfield rather than
							//hard coding to 1
							copies = 1;
							if (orderCopiesSubfield != ' ') {
								if (detailedLocationSubfield.size() == 1 && curOrderField.getSubfield(orderCopiesSubfield) != null) {
									String copiesData = curOrderField.getSubfield(orderCopiesSubfield).getData().trim();
									try {
										copies = Integer.parseInt(copiesData);
									} catch (StringIndexOutOfBoundsException e) {
										logger.error("StringIndexOutOfBoundsException loading number of copies " + copiesData, e);
									} catch (Exception e) {
										logger.error("Exception loading number of copies " + copiesData, e);
									} catch (Error e) {
										logger.error("Error loading number of copies " + copiesData, e);
									}
								}
							}
						}
						if (createAndAddOrderItem(recordInfo, curOrderField, curLocation, copies)) {
							//For On Order Items, increment popularity based on number of copies that are being purchased.
							groupedWork.addPopularity(copies);
						}
					}
				}
			}
		}
		if (recordInfo.getNumCopiesOnOrder() > 0 && !hasTangibleItems){
			groupedWork.addKeywords("On Order");
			groupedWork.addKeywords("Coming Soon");
			/*//Don't do this anymore, see D-1893
			HashSet<String> additionalOrderSubjects = new HashSet<>();
			additionalOrderSubjects.add("On Order");
			additionalOrderSubjects.add("Coming Soon");
			groupedWork.addTopic(additionalOrderSubjects);
			groupedWork.addTopicFacet(additionalOrderSubjects);*/
		}
	}

	private boolean createAndAddOrderItem(RecordInfo recordInfo, DataField curOrderField, String location, int copies) {
		ItemInfo itemInfo = new ItemInfo();
		if (curOrderField.getSubfield('a') == null){
			//Skip if we have no identifier
			return false;
		}
		String orderNumber = curOrderField.getSubfield('a').getData();
		itemInfo.setLocationCode(location);
		itemInfo.setItemIdentifier(orderNumber);
		itemInfo.setNumCopies(copies);
		itemInfo.setIsEContent(false);
		itemInfo.setIsOrderItem(true);
		itemInfo.setCallNumber("ON ORDER");
		itemInfo.setSortableCallNumber("ON ORDER");
		itemInfo.setDetailedStatus("On Order");
		Date tomorrow = new Date();
		tomorrow.setTime(tomorrow.getTime() + 1000 * 60 * 60 * 24);
		itemInfo.setDateAdded(tomorrow);
		//Format and Format Category should be set at the record level, so we don't need to set them here.

		//Add the library this is on order for
		itemInfo.setShelfLocation("On Order");

		recordInfo.addItem(itemInfo);

		return true;
	}

	private void loadScopeInfoForOrderItem(String location, String format, TreeSet<String> audiences, ItemInfo itemInfo, Record record) {
		//Shelf Location also include the name of the ordering branch if possible
		boolean hasLocationBasedShelfLocation = false;
		boolean hasSystemBasedShelfLocation = false;
		String originalUrl = itemInfo.geteContentUrl();
		for (Scope scope: indexer.getScopes()){
			Scope.InclusionResult result = scope.isItemPartOfScope(profileType, location, "", null, audiences, format, true, true, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(scope);
				if (scopingInfo == null){
					logger.error("Could not add scoping information for " + scope.getScopeName() + " for item " + itemInfo.getFullRecordIdentifier());
					continue;
				}
				if (scope.isLocationScope()) {
					scopingInfo.setLocallyOwned(scope.isItemOwnedByScope(profileType, location, ""));
					if (scope.getLibraryScope() != null) {
						boolean libraryOwned = scope.getLibraryScope().isItemOwnedByScope(profileType, location, "");
						scopingInfo.setLibraryOwned(libraryOwned);
					}else{
						//Check to see if the scope is both a library and location scope
						if (!scope.isLibraryScope()){
							logger.warn("Location scope " + scope.getScopeName() + " does not have an associated library getting scope for order item " + itemInfo.getItemIdentifier() + " - " + itemInfo.getFullRecordIdentifier());
							continue;
						}
					}
				}
				if (scope.isLibraryScope()) {
					boolean libraryOwned = scope.isItemOwnedByScope(profileType, location, "");
					scopingInfo.setLibraryOwned(libraryOwned);
					//TODO: Should this be here or should this only happen for consortia?
					if (libraryOwned && itemInfo.getShelfLocation().equals("On Order")){
						itemInfo.setShelfLocation(scopingInfo.getScope().getFacetLabel() + " On Order");
					}
				}
				if (scopingInfo.isLocallyOwned()){
					if (scope.isLibraryScope() && !hasLocationBasedShelfLocation && !hasSystemBasedShelfLocation){
						hasSystemBasedShelfLocation = true;
					}
					if (scope.isLocationScope() && !hasLocationBasedShelfLocation){
						hasLocationBasedShelfLocation = true;
						//TODO: Decide if this code should be activated
						/*if (itemInfo.getShelfLocation().equals("On Order")) {
							itemInfo.setShelfLocation(scopingInfo.getScope().getFacetLabel() + "On Order");
						}*/
					}
				}
				scopingInfo.setAvailable(false);
				scopingInfo.setHoldable(true);
				scopingInfo.setStatus("On Order");
				scopingInfo.setGroupedStatus("On Order");
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1");
	}

	private void loadOrderIds(GroupedWorkSolr groupedWork, Record record) {
		//Load order ids from recordNumberTag
		Set<String> recordIds = MarcUtil.getFieldList(record, recordNumberTag + "a");
		for(String recordId : recordIds){
			if (recordId.startsWith(".o")){
				groupedWork.addAlternateId(recordId);
			}
		}
	}

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTag);
		logger.debug("Found " + itemRecords.size() + " items for record " + identifier);
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				//Can return null if the record does not have status and location
				//This happens with secondary call numbers sometimes.
			}else{
				logger.debug("item was suppressed");
			}
		}
	}

	RecordInfo getEContentIlsRecord(GroupedWorkSolr groupedWork, Record record, String identifier, DataField itemField){
		ItemInfo itemInfo = new ItemInfo();
		itemInfo.setIsEContent(true);
		RecordInfo relatedRecord = null;

		loadDateAdded(identifier, itemField, itemInfo);
		String itemLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = getItemSubfieldData(subLocationSubfield, itemField);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		if (itemSublocation.length() > 0){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation, identifier));
		}
		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField), identifier));
		loadItemCallNumber(record, itemField, itemInfo);
		itemInfo.setItemIdentifier(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		itemInfo.setShelfLocation(getShelfLocationForItem(itemInfo, itemField, identifier));

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField), identifier));

		Subfield eContentSubfield = itemField.getSubfield(eContentSubfieldIndicator);
		if (eContentSubfield != null){
			String eContentData = eContentSubfield.getData().trim();
			if (eContentData.indexOf(':') > 0) {
				String[] eContentFields = eContentData.split(":");
				//First element is the source, and we will always have at least the source and protection type
				itemInfo.seteContentSource(eContentFields[0].trim());
				itemInfo.seteContentProtectionType(eContentFields[1].trim().toLowerCase());

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (Util.isNumeric(eContentFields[3].trim())){
						//ilsEContentItem.setNumberOfCopies(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							itemInfo.seteContentFilename(eContentFields[4].trim());
						}else{
							logger.warn("Filename for local econtent not specified " + eContentData + " " + identifier);
						}
					}else{
						//Field 4 is the filename
						itemInfo.seteContentFilename(eContentFields[3].trim());
					}
				}
			}
		}else{
			//This is for a "less advanced" catalog, set some basic info
			itemInfo.seteContentProtectionType("external");
			itemInfo.seteContentSource(getSourceType(record, itemField));
		}

		//Set record type
		String protectionType = itemInfo.geteContentProtectionType();
		switch (protectionType) {
			case "external":
				relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				break;
			case "acs":
			case "drm":
			case "public domain":
			case "free":
				//Remove restricted (ACS) eContent from Pika #PK-1199
				//Remove free public domain, but stored locally eContent from Pika #PK-1199
				//relatedRecord = groupedWork.addRelatedRecord("public_domain_econtent", identifier);
				//relatedRecord.setSubSource(profileType);
				//relatedRecord.addItem(itemInfo);
				return null;
			default:
				logger.warn("Unknown protection type " + protectionType + " found in record " + identifier);
				break;
		}

		loadEContentFormatInformation(record, relatedRecord, itemInfo);

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(itemUrlSubfieldIndicator);
		if (urlSubfield != null){
			itemInfo.seteContentUrl(urlSubfield.getData().trim());
		}else if (protectionType.equals("external")){
			//Check the 856 tag to see if there is a link there
			List<DataField> urlFields = MarcUtil.getDataFields(record, "856");
			for (DataField urlField : urlFields){
				//load url into the item
				if (urlField.getSubfield('u') != null){
					//Try to determine if this is a resource or not.
					if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0' || urlField.getIndicator1() == '7'){
						if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '8') {
							itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
							break;
						}
					}

				}
			}

		}

		itemInfo.setDetailedStatus("Available Online");

		return relatedRecord;
	}

	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		String dateAddedStr = getItemSubfieldData(dateCreatedSubfield, itemField);
		if (dateAddedStr != null && dateAddedStr.length() > 0) {
			try {
				if (dateAddedFormatter == null){
					dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
				}
				Date dateAdded = dateAddedFormatter.parse(dateAddedStr);
				itemInfo.setDateAdded(dateAdded);
			} catch (ParseException e) {
				logger.error("Error processing date added for record identifier " + recordIdentifier + " profile " + profileType + " using format " + dateAddedFormat, e);
			}
		}
	}

	protected String getSourceType(Record record, DataField itemField) {
		return "Unknown Source";
	}

	private SimpleDateFormat dateAddedFormatter = null;
	private SimpleDateFormat lastCheckInFormatter = null;
	ItemInfo getPrintIlsItem(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField) {
		if (dateAddedFormatter == null){
			dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
		}
		if (lastCheckInFormatter == null && lastCheckInFormat != null && lastCheckInFormat.length() > 0){
			lastCheckInFormatter = new SimpleDateFormat(lastCheckInFormat);
		}
		ItemInfo itemInfo = new ItemInfo();
		//Load base information from the Marc Record
		itemInfo.setItemIdentifier(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));

		String itemStatus = getItemStatus(itemField, recordInfo.getRecordIdentifier());

		String itemLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = getItemSubfieldData(subLocationSubfield, itemField);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		itemInfo.setSubLocationCode(itemSublocation);
		if (itemSublocation.length() > 0){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation, recordInfo.getRecordIdentifier()));
		}else{
			itemInfo.setSubLocation("");
		}

		//if the status and location are null, we can assume this is not a valid item
		if (!isItemValid(itemStatus, itemLocation)) return null;

		setShelfLocationCode(itemField, itemInfo, recordInfo.getRecordIdentifier());
		itemInfo.setShelfLocation(getShelfLocationForItem(itemInfo, itemField, recordInfo.getRecordIdentifier()));

		loadDateAdded(recordInfo.getRecordIdentifier(), itemField, itemInfo);
		getDueDate(itemField, itemInfo);

		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField), recordInfo.getRecordIdentifier()));

		double itemPopularity = getItemPopularity(itemField);
		groupedWork.addPopularity(itemPopularity);

		loadItemCallNumber(record, itemField, itemInfo);

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField), recordInfo.getRecordIdentifier()));

		if (lastCheckInFormatter != null) {
			String lastCheckInDate = getItemSubfieldData(lastCheckInSubfield, itemField);
			Date lastCheckIn = null;
			if (lastCheckInDate != null && lastCheckInDate.length() > 0)
				try {
					lastCheckIn = lastCheckInFormatter.parse(lastCheckInDate);
				} catch (ParseException e) {
					logger.debug("Could not parse check in date " + lastCheckInDate, e);
				}
			itemInfo.setLastCheckinDate(lastCheckIn);
		}

		//set status towards the end so we can access date added and other things that may need to
		itemInfo.setStatusCode(itemStatus);
		if (itemStatus != null) {
			setDetailedStatus(itemInfo, itemField, itemStatus, recordInfo.getRecordIdentifier());
		}

		if (formatSource.equals("item") && formatSubfield != ' '){
			String format = getItemSubfieldData(formatSubfield, itemField);
			if (format != null) {
				itemInfo.setFormat(translateValue("format", format, recordInfo.getRecordIdentifier()));
				itemInfo.setFormatCategory(translateValue("format_category", format, recordInfo.getRecordIdentifier()));
				String formatBoost = translateValue("format_boost", format, recordInfo.getRecordIdentifier());
				try {
					if (formatBoost != null && formatBoost.length() > 0) {
						recordInfo.setFormatBoost(Integer.parseInt(formatBoost));
					}
				} catch (Exception e) {
					logger.warn("Could not get boost for format " + format);
				}
			}
		}

		//This is done later so we don't need to do it here.
		//loadScopeInfoForPrintIlsItem(recordInfo, groupedWork.getTargetAudiences(), itemInfo, record);

		groupedWork.addKeywords(itemLocation);
		if (itemSublocation.length() > 0){
			groupedWork.addKeywords(itemSublocation);
		}

		recordInfo.addItem(itemInfo);
		return itemInfo;
	}

	protected void getDueDate(DataField itemField, ItemInfo itemInfo) {
		String dueDateStr = getItemSubfieldData(dueDateSubfield, itemField);
		itemInfo.setDueDate(dueDateStr);
	}

	protected void setShelfLocationCode(DataField itemField, ItemInfo itemInfo, String recordIdentifier) {
		if (shelvingLocationSubfield != ' '){
			itemInfo.setShelfLocationCode(getItemSubfieldData(shelvingLocationSubfield, itemField));
		}else {
			itemInfo.setShelfLocationCode(getItemSubfieldData(locationSubfieldIndicator, itemField));
		}
	}

	void scopeItems(RecordInfo recordInfo, GroupedWorkSolr groupedWork, Record record){
		for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
			if (itemInfo.isOrderItem()){
				loadScopeInfoForOrderItem(itemInfo.getLocationCode(), recordInfo.getPrimaryFormat(), groupedWork.getTargetAudiences(), itemInfo, record);
			}else if (itemInfo.isEContent()){
				loadScopeInfoForEContentItem(groupedWork, itemInfo, record);
			}else{
				loadScopeInfoForPrintIlsItem(recordInfo, groupedWork.getTargetAudiences(), itemInfo, record);
			}
		}
	}

	private void loadScopeInfoForEContentItem(GroupedWorkSolr groupedWork, ItemInfo itemInfo, Record record) {
		String itemLocation = itemInfo.getLocationCode();
		String originalUrl = itemInfo.geteContentUrl();
		for (Scope curScope : indexer.getScopes()){
			String format = itemInfo.getFormat();
			if (format == null){
				format = itemInfo.getRecordInfo().getPrimaryFormat();
			}
			Scope.InclusionResult result = curScope.isItemPartOfScope(profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), format, false, false, true, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(true);
				scopingInfo.setStatus("Available Online");
				scopingInfo.setGroupedStatus("Available Online");
				scopingInfo.setHoldable(false);
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, itemLocation, ""));
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope(profileType, itemLocation, ""));
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, itemLocation, ""));
				}
				//Check to see if we need to do url rewriting
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	private void loadScopeInfoForPrintIlsItem(RecordInfo recordInfo, TreeSet<String> audiences, ItemInfo itemInfo, Record record) {
		//Determine Availability
		boolean available = isItemAvailable(itemInfo);

		//Determine which scopes have access to this record
		String displayStatus = getDisplayStatus(itemInfo, recordInfo.getRecordIdentifier());
		String groupedDisplayStatus = getDisplayGroupedStatus(itemInfo, recordInfo.getRecordIdentifier());
		String overiddenStatus = getOverriddenStatus(itemInfo, true);
		if (overiddenStatus != null && !overiddenStatus.equals("On Shelf") && !overiddenStatus.equals("Library Use Only") && !overiddenStatus.equals("Available Online")){
			available = false;
		}

		String itemLocation = itemInfo.getLocationCode();
		String itemSublocation = itemInfo.getSubLocationCode();

		HoldabilityInformation isHoldableUnscoped = isItemHoldableUnscoped(itemInfo);
		BookabilityInformation isBookableUnscoped = isItemBookableUnscoped();
		String originalUrl = itemInfo.geteContentUrl();
		String primaryFormat = recordInfo.getPrimaryFormat();
		for (Scope curScope : indexer.getScopes()) {
			//Check to see if the record is holdable for this scope
			HoldabilityInformation isHoldable = isItemHoldable(itemInfo, curScope, isHoldableUnscoped);

			Scope.InclusionResult result = curScope.isItemPartOfScope(profileType, itemLocation, itemSublocation, itemInfo.getITypeCode(), audiences, primaryFormat, isHoldable.isHoldable(), false, false, record, originalUrl);
			if (result.isIncluded){
				BookabilityInformation isBookable = isItemBookable(itemInfo, curScope, isBookableUnscoped);
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(available);
				scopingInfo.setHoldable(isHoldable.isHoldable());
				scopingInfo.setHoldablePTypes(isHoldable.getHoldablePTypes());
				scopingInfo.setBookable(isBookable.isBookable());
				scopingInfo.setBookablePTypes(isBookable.getBookablePTypes());

				scopingInfo.setInLibraryUseOnly(determineLibraryUseOnly(itemInfo, curScope));

				scopingInfo.setStatus(displayStatus);
				scopingInfo.setGroupedStatus(groupedDisplayStatus);
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope(profileType, itemLocation, itemSublocation));
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
				}
			}
		}
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return false;
	}

	protected void setDetailedStatus(ItemInfo itemInfo, DataField itemField, String itemStatus, String identifier) {
		//See if we need to override based on the last check in date
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			itemInfo.setDetailedStatus(overriddenStatus);
		}else {
			itemInfo.setDetailedStatus(translateValue("item_status", itemStatus, identifier));
		}
	}

	String getOverriddenStatus(ItemInfo itemInfo, boolean groupedStatus) {
		String overriddenStatus = null;
		if (itemInfo.getLastCheckinDate() != null) {
			for (TimeToReshelve timeToReshelve : timesToReshelve) {
				if (timeToReshelve.getLocationsPattern().matcher(itemInfo.getLocationCode()).matches()) {
					long now = new Date().getTime();
					if (now - itemInfo.getLastCheckinDate().getTime() <= timeToReshelve.getNumHoursToOverride() * 60 * 60 * 1000) {
						if (groupedStatus){
							overriddenStatus = timeToReshelve.getGroupedStatus();
						} else{
							overriddenStatus = timeToReshelve.getStatus();
						}
						break;
					}
				}
			}
		}
		return overriddenStatus;
	}

	protected String getDisplayGroupedStatus(ItemInfo itemInfo, String identifier) {
		String overriddenStatus = getOverriddenStatus(itemInfo, true);
		if (overriddenStatus != null) {
			return overriddenStatus;
		}else {
			return translateValue("item_grouped_status", itemInfo.getStatusCode(), identifier);
		}
	}

	protected String getDisplayStatus(ItemInfo itemInfo, String identifier) {
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			return overriddenStatus;
		}else {
			return translateValue("item_status", itemInfo.getStatusCode(), identifier);
		}
	}

	protected double getItemPopularity(DataField itemField) {
		String totalCheckoutsField = getItemSubfieldData(totalCheckoutSubfield, itemField);
		int totalCheckouts = 0;
		if (totalCheckoutsField != null){
			try{
				totalCheckouts = Integer.parseInt(totalCheckoutsField);
			}catch (NumberFormatException e){
				logger.warn("Did not get a number for total checkouts. Got " + totalCheckoutsField);
			}

		}
		String ytdCheckoutsField = getItemSubfieldData(ytdCheckoutSubfield, itemField);
		int ytdCheckouts = 0;
		if (ytdCheckoutsField != null){
			ytdCheckouts = Integer.parseInt(ytdCheckoutsField);
		}
		String lastYearCheckoutsField = getItemSubfieldData(lastYearCheckoutSubfield, itemField);
		int lastYearCheckouts = 0;
		if (lastYearCheckoutsField != null){
			lastYearCheckouts = Integer.parseInt(lastYearCheckoutsField);
		}
		double itemPopularity = ytdCheckouts + .5 * (lastYearCheckouts) + .1 * (totalCheckouts - lastYearCheckouts - ytdCheckouts);
		if (itemPopularity == 0){
			itemPopularity = 1;
		}
		return itemPopularity;
	}

	protected boolean isItemValid(String itemStatus, String itemLocation) {
		return !(itemStatus == null && itemLocation == null);
	}

	void loadItemCallNumber(Record record, DataField itemField, ItemInfo itemInfo) {
		boolean hasCallNumber = false;
		String volume = null;
		if (itemField != null){
			volume = getItemSubfieldData(volumeSubfield, itemField);
		}
		if (useItemBasedCallNumbers && itemField != null) {
			String callNumberPreStamp = getItemSubfieldDataWithoutTrimming(callNumberPrestampSubfield, itemField);
			String callNumber = getItemSubfieldDataWithoutTrimming(callNumberSubfield, itemField);
			String callNumberCutter = getItemSubfieldDataWithoutTrimming(callNumberCutterSubfield, itemField);
			String callNumberPostStamp = getItemSubfieldData(callNumberPoststampSubfield, itemField);

			StringBuilder fullCallNumber = new StringBuilder();
			StringBuilder sortableCallNumber = new StringBuilder();
			if (callNumberPreStamp != null) {
				fullCallNumber.append(callNumberPreStamp);
			}
			if (callNumber != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumber);
				sortableCallNumber.append(callNumber);
			}
			if (callNumberCutter != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumberCutter);
				if (sortableCallNumber.length() > 0 && sortableCallNumber.charAt(sortableCallNumber.length() - 1) != ' '){
					sortableCallNumber.append(' ');
				}
				sortableCallNumber.append(callNumberCutter);
			}
			if (callNumberPostStamp != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumberPostStamp);
				if (sortableCallNumber.length() > 0 && sortableCallNumber.charAt(sortableCallNumber.length() - 1) != ' '){
					sortableCallNumber.append(' ');
				}
				sortableCallNumber.append(callNumberPostStamp);
			}
			//ARL-203 do not create an item level call number that is just a volume
			if (volume != null && fullCallNumber.length() > 0){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(volume);
			}
			if (fullCallNumber.length() > 0){
				hasCallNumber = true;
				itemInfo.setCallNumber(fullCallNumber.toString().trim());
				itemInfo.setSortableCallNumber(sortableCallNumber.toString().trim());
			}
		}
		if (!hasCallNumber){
			String callNumber = null;
			if (use099forBibLevelCallNumbers()) {
				DataField localCallNumberField = record.getDataField("099");
				if (localCallNumberField != null) {
					callNumber = "";
					for (Subfield curSubfield : localCallNumberField.getSubfields()) {
						callNumber += " " + curSubfield.getData().trim();
					}
				}
			}
			//MDN #ARL-217 do not use 099 as a call number
			if (callNumber == null) {
				DataField deweyCallNumberField = record.getDataField("092");
				if (deweyCallNumberField != null) {
					callNumber = "";
					for (Subfield curSubfield : deweyCallNumberField.getSubfields()) {
						callNumber += " " + curSubfield.getData().trim();
					}
				}
			}
			if (callNumber != null) {

				if (volume != null && volume.length() > 0 && !callNumber.endsWith(volume)){
					if (callNumber.length() > 0 && callNumber.charAt(callNumber.length() - 1) != ' '){
						callNumber += " ";
					}
					callNumber += volume;
				}
				itemInfo.setCallNumber(callNumber.trim());
				itemInfo.setSortableCallNumber(callNumber.trim());
			}
		}
	}

	protected boolean use099forBibLevelCallNumbers() {
		return true;
	}

	private HashMap<String, Boolean> iTypesThatHaveHoldabilityChecked = new HashMap<>();
	private HashMap<String, Boolean> locationsThatHaveHoldabilityChecked = new HashMap<>();
	private HashMap<String, Boolean> statusesThatHaveHoldabilityChecked = new HashMap<>();

	private HoldabilityInformation isItemHoldableUnscoped(ItemInfo itemInfo){
		String itemItypeCode =  itemInfo.getITypeCode();
		if (nonHoldableITypes != null && itemItypeCode != null && itemItypeCode.length() > 0){
			if (!iTypesThatHaveHoldabilityChecked.containsKey(itemItypeCode)){
				iTypesThatHaveHoldabilityChecked.put(itemItypeCode, !nonHoldableITypes.matcher(itemItypeCode).matches());
			}
			if (!iTypesThatHaveHoldabilityChecked.get(itemItypeCode)){
				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		String itemLocationCode =  itemInfo.getLocationCode();
		if (nonHoldableLocations != null && itemLocationCode != null && itemLocationCode.length() > 0){
			if (!locationsThatHaveHoldabilityChecked.containsKey(itemLocationCode)){
				locationsThatHaveHoldabilityChecked.put(itemLocationCode, !nonHoldableLocations.matcher(itemLocationCode).matches());
			}
			if (!locationsThatHaveHoldabilityChecked.get(itemLocationCode)){
				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		String itemStatusCode = itemInfo.getStatusCode();
		if (nonHoldableStatuses != null && itemStatusCode != null && itemStatusCode.length() > 0){
			if (!statusesThatHaveHoldabilityChecked.containsKey(itemStatusCode)){
				statusesThatHaveHoldabilityChecked.put(itemStatusCode, !nonHoldableStatuses.matcher(itemStatusCode).matches());
			}
			if (!statusesThatHaveHoldabilityChecked.get(itemStatusCode)){


				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		return new HoldabilityInformation(true, new HashSet<Long>());
	}

	protected HoldabilityInformation isItemHoldable(ItemInfo itemInfo, Scope curScope, HoldabilityInformation isHoldableUnscoped){
		return isHoldableUnscoped;
	}

	private BookabilityInformation isItemBookableUnscoped(){
		return new BookabilityInformation(false, new HashSet<Long>());
	}

	protected BookabilityInformation isItemBookable(ItemInfo itemInfo, Scope curScope, BookabilityInformation isBookableUnscoped) {
		return isBookableUnscoped;
	}

	protected String getShelfLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String shelfLocation = null;
		if (itemField != null) {
			shelfLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		}
		if (shelfLocation == null || shelfLocation.length() == 0 || shelfLocation.equals("none")){
			return "";
		}else {
			return translateValue("shelf_location", shelfLocation, identifier);
		}
	}

	protected String getItemStatus(DataField itemField, String recordIdentifier){
		return getItemSubfieldData(statusSubfieldIndicator, itemField);
	}

	protected abstract boolean isItemAvailable(ItemInfo itemInfo);

	String getItemSubfieldData(char subfieldIndicator, DataField itemField) {
		if (subfieldIndicator == ' '){
			return null;
		}else {
//			return itemField.getSubfield(subfieldIndicator) != null ? itemField.getSubfield(subfieldIndicator).getData().trim() : null;

			List<Subfield> subfields = itemField.getSubfields(subfieldIndicator);
			if (subfields.size() == 1) {
				return subfields.get(0).getData().trim();
			} else if (subfields.size() == 0) {
				return null;
			} else {
				StringBuilder subfieldData = new StringBuilder();
				for (Subfield subfield:subfields) {
					String trimmedValue = subfield.getData().trim();
					boolean okToAdd = false;
					if (trimmedValue.length() == 0){
						continue;
					}
					try {
						if (subfieldData.length() == 0) {
							okToAdd = true;
						} else if (subfieldData.length() < trimmedValue.length()) {
							okToAdd = true;
						} else if (!subfieldData.substring(subfieldData.length() - trimmedValue.length()).equals(trimmedValue)) {
							okToAdd = true;
						}
					}catch (Exception e){
						logger.error("Error determining if the new value is already part of the string", e);
					}
					if (okToAdd) {
						if (subfieldData.length() > 0 && subfieldData.charAt(subfieldData.length() - 1) != ' ') {
							subfieldData.append(' ');
						}
						subfieldData.append(trimmedValue);
					}else{
						logger.debug("Not appending subfield because the value looks redundant");
					}
				}
				return subfieldData.toString().trim();
			}

		}
	}

	private String getItemSubfieldDataWithoutTrimming(char subfieldIndicator, DataField itemField) {
		if (subfieldIndicator == ' '){
			return null;
		}else {
//			return itemField.getSubfield(subfieldIndicator) != null ? itemField.getSubfield(subfieldIndicator).getData() : null;

			List<Subfield> subfields = itemField.getSubfields(subfieldIndicator);
			if (subfields.size() == 1) {
				return subfields.get(0).getData();
			} else if (subfields.size() == 0) {
				return null;
			} else {
				StringBuilder subfieldData = new StringBuilder();
				for (Subfield subfield:subfields) {
					if (subfieldData.length() > 0 && subfieldData.charAt(subfieldData.length() - 1) != ' '){
						subfieldData.append(' ');
					}
					subfieldData.append(subfield.getData());
				}
				return subfieldData.toString();
			}
		}
	}

	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		return new ArrayList<>();
	}

	void loadPopularity(GroupedWorkSolr groupedWork, String recordIdentifier) {
		//Add popularity based on the number of holds (we have already done popularity for prior checkouts)
		//Active holds indicate that a title is more interesting so we will count each hold at double value
		double popularity = 2 * getIlsHoldsForTitle(recordIdentifier);
		groupedWork.addPopularity(popularity);
	}

	private int getIlsHoldsForTitle(String recordIdentifier) {
		if (numberOfHoldsByIdentifier.containsKey(recordIdentifier)){
			return numberOfHoldsByIdentifier.get(recordIdentifier);
		}else {
			return 0;
		}
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			if (statusSubfield == null) {
				return true;
			} else {
				if (statusesToSuppressPattern != null && statusesToSuppressPattern.matcher(statusSubfield.getData()).matches()) {

					return true;
				}
			}
		}
		Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationSubfield == null){
			return true;
		}else{
			if (locationsToSuppressPattern != null && locationsToSuppressPattern.matcher(locationSubfield.getData().trim()).matches()){
				return true;
			}
		}
		if (collectionSubfield != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(collectionSubfield);
			if (collectionSubfieldValue == null){
				return true;
			}else{
				if (collectionsToSuppressPattern != null && collectionsToSuppressPattern.matcher(collectionSubfieldValue.getData().trim()).matches()){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Determine Record Format(s)
	 */
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record){
		//We should already have formats based on the items
		if (formatSource.equals("item") && formatSubfield != ' ' && recordInfo.hasItemFormats()){
			return;
		}

		if (formatSource.equals("specified")){
			HashSet<String> translatedFormats = new HashSet<>();
			translatedFormats.add(specifiedFormat);
			HashSet<String> translatedFormatCategories = new HashSet<>();
			translatedFormatCategories.add(specifiedFormatCategory);
			recordInfo.addFormats(translatedFormats);
			recordInfo.addFormatCategories(translatedFormatCategories);
			recordInfo.setFormatBoost(specifiedFormatBoost);
		} else {
			loadPrintFormatFromBib(recordInfo, record);
		}
	}

	void loadPrintFormatFromBib(RecordInfo recordInfo, Record record) {
		LinkedHashSet<String> printFormats = getFormatsFromBib(record, recordInfo);

		/*for(String format: printFormats){
			logger.debug("Print formats from bib:");
			logger.debug("    " + format);
		}*/
		HashSet<String> translatedFormats = translateCollection("format", printFormats, recordInfo.getRecordIdentifier());
		if (translatedFormats.size() == 0){
			logger.warn("Did not find a format for " + recordInfo.getRecordIdentifier() + " using standard format method " + printFormats.toString());
		}
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats, recordInfo.getRecordIdentifier());
		recordInfo.addFormats(translatedFormats);
		recordInfo.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats, recordInfo.getRecordIdentifier());
		for (String tmpFormatBoost : formatBoosts) {
			try {
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			} catch (NumberFormatException e) {
				logger.warn("Could not load format boost for format " + tmpFormatBoost + " profile " + profileType);
			}
		}
		recordInfo.setFormatBoost(formatBoost);
	}

	LinkedHashSet<String> getFormatsFromBib(Record record, RecordInfo recordInfo){
		LinkedHashSet<String> printFormats = new LinkedHashSet<>();
		String leader = record.getLeader().toString();
		char leaderBit;
		ControlField fixedField = (ControlField) record.getVariableField("008");

		// check for music recordings quickly so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc is difficult).
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'J':
					printFormats.add("MusicRecording");
					break;
			}
		}
		getFormatFromPublicationInfo(record, printFormats);
		getFormatFromNotes(record, printFormats);
		getFormatFromEdition(record, printFormats);
		getFormatFromPhysicalDescription(record, printFormats);
		getFormatFromSubjects(record, printFormats);
		getFormatFromTitle(record, printFormats);
		getFormatFromDigitalFileCharacteristics(record, printFormats);
		if (printFormats.size() == 0) {
			//Only get from fixed field information if we don't have anything yet since the catalogging of
			//fixed fields is not kept up to date reliably.  #D-87
			getFormatFrom007(record, printFormats);
			if (printFormats.size() > 1){
				logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " looking at just 007");
			}
			if (printFormats.size() == 0) {
				getFormatFromLeader(printFormats, leader, fixedField);
				if (printFormats.size() > 1){
					logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " looking at just the leader");
				}
			}
		}

		if (printFormats.size() == 0){
			logger.debug("Did not get any formats for print record " + recordInfo.getFullIdentifier() + ", assuming it is a book ");
			printFormats.add("Book");
		}else{
			for(String format: printFormats){
				logger.debug("    found format " + format);
			}
		}

		filterPrintFormats(printFormats);

		if (printFormats.size() > 1){
			String formatsString = Util.getCsvSeparatedString(printFormats);
			if (!formatsToFilter.contains(formatsString)){
				formatsToFilter.add(formatsString);
				logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " - " + formatsString);
			}
		}
		return printFormats;
	}
	private HashSet<String> formatsToFilter = new HashSet<>();

	private void getFormatFromDigitalFileCharacteristics(Record record, LinkedHashSet<String> printFormats) {
		Set<String> fields = MarcUtil.getFieldList(record, "347b");
		for (String curField : fields){
			if (curField.equalsIgnoreCase("Blu-Ray")){
				printFormats.add("Blu-ray");
			}else if (curField.equalsIgnoreCase("DVD video")){
				printFormats.add("DVD");
			}
		}
	}

	private void filterPrintFormats(Set<String> printFormats) {
		if (printFormats.contains("Archival Materials")){
			printFormats.clear();
			printFormats.add("Archival Materials");
			return;
		}
		if (printFormats.contains("SoundCassette") && printFormats.contains("MusicRecording")){
			printFormats.clear();
			printFormats.add("MusicCassette");
		}
		if (printFormats.contains("Thesis")){
			printFormats.clear();
			printFormats.add("Thesis");
		}
		if (printFormats.contains("Phonograph")){
			printFormats.clear();
			printFormats.add("Phonograph");
			return;
		}
		if (printFormats.contains("MusicRecording") && (printFormats.contains("CD") || printFormats.contains("CompactDisc") || printFormats.contains("SoundDisc"))){
			printFormats.clear();
			printFormats.add("MusicCD");
			return;
		}
		if (printFormats.contains("PlayawayView")){
			printFormats.clear();
			printFormats.add("PlayawayView");
			return;
		}
		if (printFormats.contains("Playaway")){
			printFormats.clear();
			printFormats.add("Playaway");
			return;
		}
		if (printFormats.contains("GoReader")){
			printFormats.clear();
			printFormats.add("GoReader");
			return;
		}
		if (printFormats.contains("Video") && printFormats.contains("DVD")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("VideoDisc") && printFormats.contains("DVD")){
			printFormats.remove("VideoDisc");
		}
		if (printFormats.contains("Video") && printFormats.contains("VideoDisc")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("Video") && printFormats.contains("VideoCassette")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("DVD") && printFormats.contains("VideoCassette")){
			printFormats.remove("VideoCassette");
		}
		if (printFormats.contains("Blu-ray") && printFormats.contains("VideoDisc")){
			printFormats.remove("VideoDisc");
		}
		if (printFormats.contains("SoundDisc") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}
		if (printFormats.contains("SoundDisc") && printFormats.contains("CDROM")){
			printFormats.remove("CDROM");
		}
		if (printFormats.contains("SoundCassette") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}
		if (printFormats.contains("SoundCassette") && printFormats.contains("CompactDisc")){
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("SoundRecording") && printFormats.contains("CDROM")){
			printFormats.clear();
			printFormats.add("SoundDisc");
		}

		if (printFormats.contains("Book") && printFormats.contains("LargePrint")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("Manuscript")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("GraphicNovel")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("MusicalScore")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("BookClubKit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("Kit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("AudioCD") && printFormats.contains("CD")){
			printFormats.remove("AudioCD");
		}

		if (printFormats.contains("CD") && printFormats.contains("SoundDisc")){
			printFormats.remove("CD");
		}
		if (printFormats.contains("CompactDisc") && printFormats.contains("SoundDisc")){
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("CompactDisc") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}
		if (printFormats.contains("GraphicNovel") && printFormats.contains("Serial")){
			printFormats.remove("Serial");
		}
		if (printFormats.contains("Atlas") && printFormats.contains("Map")){
			printFormats.remove("Atlas");
		}
		if (printFormats.contains("LargePrint") && printFormats.contains("Manuscript")){
			printFormats.remove("Manuscript");
		}
		if (printFormats.contains("Kinect") || printFormats.contains("XBox360")  || printFormats.contains("Xbox360")
				|| printFormats.contains("XBoxOne") || printFormats.contains("PlayStation")
				|| printFormats.contains("PlayStation3") || printFormats.contains("PlayStation4")
				|| printFormats.contains("Wii") || printFormats.contains("WiiU")
				|| printFormats.contains("3DS") || printFormats.contains("WindowsGame")){
			printFormats.remove("Software");
			printFormats.remove("Electronic");
			printFormats.remove("CDROM");
			printFormats.remove("Blu-ray");
		}
	}

	private void getFormatFromTitle(Record record, Set<String> printFormats) {
		String titleMedium = MarcUtil.getFirstFieldVal(record, "245h");
		if (titleMedium != null){
			titleMedium = titleMedium.toLowerCase();
			if (titleMedium.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleMedium.contains("large print")){
				printFormats.add("LargePrint");
			}else if (titleMedium.contains("book club kit")){
				printFormats.add("BookClubKit");
			}else if (titleMedium.contains("ebook")){
				printFormats.add("eBook");
			}else if (titleMedium.contains("eaudio")){
				printFormats.add("eAudio");
			}else if (titleMedium.contains("emusic")){
				printFormats.add("eMusic");
			}else if (titleMedium.contains("evideo")){
				printFormats.add("eVideo");
			}else if (titleMedium.contains("ejournal")){
				printFormats.add("eJournal");
			}else if (titleMedium.contains("playaway")){
				printFormats.add("Playaway");
			}else if (titleMedium.contains("periodical")){
				printFormats.add("Serial");
			}else if (titleMedium.contains("vhs")){
				printFormats.add("VideoCassette");
			}else if (titleMedium.contains("blu-ray")){
				printFormats.add("Blu-ray");
			}else if (titleMedium.contains("dvd")){
				printFormats.add("DVD");
			}

		}
		String titleForm = MarcUtil.getFirstFieldVal(record, "245k");
		if (titleForm != null){
			titleForm = titleForm.toLowerCase();
			if (titleForm.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleForm.contains("large print")){
				printFormats.add("LargePrint");
			}else if (titleForm.contains("book club kit")){
				printFormats.add("BookClubKit");
			}
		}
		String titlePart = MarcUtil.getFirstFieldVal(record, "245p");
		if (titlePart != null){
			titlePart = titlePart.toLowerCase();
			if (titlePart.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titlePart.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
		String title = MarcUtil.getFirstFieldVal(record, "245a");
		if (title != null){
			title = title.toLowerCase();
			if (title.contains("book club kit")){
				printFormats.add("BookClubKit");
			}
		}
	}

	private void getFormatFromPublicationInfo(Record record, Set<String> result) {
		// check for playaway in 260|b
		DataField sysDetailsNote = record.getDataField("260");
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData()
						.toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				}else if (sysDetailsValue.contains("go reader")) {
					result.add("GoReader");
				}
			}
		}
	}

	private void getFormatFromEdition(Record record, Set<String> result) {
		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = record.getDataField("250");
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				String editionData = edition.getSubfield('a').getData().toLowerCase();
				if (editionData.contains("large type") || editionData.contains("large print")) {
					result.add("LargePrint");
				}else if (editionData.contains("go reader")) {
						result.add("GoReader");
				}else {
					String gameFormat = getGameFormatFromValue(editionData);
					if (gameFormat != null) {
						result.add(gameFormat);
					}
				}
			}
		}
	}

	private void getFormatFromPhysicalDescription(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> physicalDescription = MarcUtil.getDataFields(record, "300");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = physicalDescription.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subFields = field.getSubfields();
				for (Subfield subfield : subFields) {
					if (subfield.getCode() != 'e') {
						String physicalDescriptionData = subfield.getData().toLowerCase();
						if (physicalDescriptionData.contains("large type") || physicalDescriptionData.contains("large print")) {
							result.add("LargePrint");
						} else if (physicalDescriptionData.contains("bluray") || physicalDescriptionData.contains("blu-ray")) {
							result.add("Blu-ray");
						} else if (physicalDescriptionData.contains("computer optical disc")) {
							result.add("Software");
						} else if (physicalDescriptionData.contains("sound cassettes")) {
							result.add("SoundCassette");
						} else if (physicalDescriptionData.contains("sound discs") || physicalDescriptionData.contains("audio discs") || physicalDescriptionData.contains("compact disc")) {
							result.add("SoundDisc");
						}
						//Since this is fairly generic, only use it if we have no other formats yet
						if (result.size() == 0 && subfield.getCode() == 'f' && physicalDescriptionData.matches("^.*?\\d+\\s+(p\\.|pages).*$")) {
							result.add("Book");
						}
					}
				}
			}
		}
	}

	private void getFormatFromNotes(Record record, Set<String> result) {
		// Check for formats in the 538 field
		DataField sysDetailsNote2 = record.getDataField("538");
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				String gameFormat = getGameFormatFromValue(sysDetailsValue);
				if (gameFormat != null){
					result.add(gameFormat);
				}else{
					if (sysDetailsValue.contains("playaway")) {
						result.add("Playaway");
					} else if (sysDetailsValue.contains("bluray") || sysDetailsValue.contains("blu-ray")) {
						result.add("Blu-ray");
					} else if (sysDetailsValue.contains("dvd")) {
						result.add("DVD");
					} else if (sysDetailsValue.contains("vertical file")) {
						result.add("VerticalFile");
					}
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = record.getDataField("500");
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					result.add("VerticalFile");
				}else if (noteValue.contains("vox books")) {
					result.add("VoxBooks");
				}
			}
		}

		// Check for formats in the 502 tag
		DataField dissertaionNoteField = record.getDataField("502");
		if (dissertaionNoteField != null) {
			if (dissertaionNoteField.getSubfield('a') != null) {
				String noteValue = dissertaionNoteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("thesis (m.a.)")) {
					result.add("Thesis");
				}
			}
		}

		// Check for formats in the 590 tag
		DataField localNoteField = record.getDataField("590");
		if (localNoteField != null) {
			if (localNoteField.getSubfield('a') != null) {
				String noteValue = localNoteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("archival materials")) {
					result.add("Archival Materials");
				}
			}
		}
	}

	private String getGameFormatFromValue(String value) {
		if (value.contains("kinect sensor")) {
			return "Kinect";
		} else if (value.contains("xbox one") && !value.contains("compatible")) {
			return "XboxOne";
		} else if (value.contains("xbox") && !value.contains("compatible")) {
			return "Xbox360";
		} else if (value.contains("playstation 4") && !value.contains("compatible")) {
			return "PlayStation4";
		} else if (value.contains("playstation 3") && !value.contains("compatible")) {
			return "PlayStation3";
		} else if (value.contains("playstation") && !value.contains("compatible")) {
			return "PlayStation";
		} else if (value.contains("wii u")) {
			return "WiiU";
		} else if (value.contains("nintendo wii")) {
			return "Wii";
		} else if (value.contains("nintendo 3ds")) {
			return "3DS";
		} else if (value.contains("directx")) {
			return "WindowsGame";
		}else{
			return null;
		}
	}

	private void getFormatFromSubjects(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> topicalTerm = MarcUtil.getDataFields(record, "650");
		if (topicalTerm != null) {
			Iterator<DataField> fieldsIter = topicalTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type") || subfieldData.contains("large print")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("television adaptation")){
									okToAdd = true;
								//}else{
									//System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		List<DataField> genreFormTerm = MarcUtil.getDataFields(record, "655");
		if (genreFormTerm != null) {
			Iterator<DataField> fieldsIter = genreFormTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("Television adaptation")){
									okToAdd = true;
								//}else{
									//System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> localTopicalTerm = MarcUtil.getDataFields(record, "690");
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						result.add("SeedPacket");
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> addedEntryFields = MarcUtil.getDataFields(record, "710");
		if (localTopicalTerm != null) {
			Iterator<DataField> addedEntryFieldIterator = addedEntryFields.iterator();
			DataField field;
			while (addedEntryFieldIterator.hasNext()) {
				field = addedEntryFieldIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null && subfieldA.getData() != null) {
					String fieldData = subfieldA.getData().toLowerCase();
					if (fieldData.contains("playaway view")) {
						result.add("PlayawayView");
					}else if (fieldData.contains("playaway digital audio") || fieldData.contains("findaway world")) {
						result.add("Playaway");
					}
				}
			}
		}
	}

	private void getFormatFrom007(Record record, Set<String> result) {
		char formatCode;// check the 007 - this is a repeating field
		@SuppressWarnings("unchecked")
		ControlField formatField = MarcUtil.getControlField(record, "007");
		if (formatField != null){
			if (formatField.getData() == null || formatField.getData().length() < 2) {
				return;
			}
			// Check for blu-ray (s in position 4)
			// This logic does not appear correct.
			/*
			 * if (formatField.getData() != null && formatField.getData().length()
			 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
			 * result.add("Blu-ray"); break; } }
			 */
			formatCode = formatField.getData().toUpperCase().charAt(0);
			switch (formatCode) {
				case 'A':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'D':
							result.add("Atlas");
							break;
						default:
							result.add("Map");
							break;
					}
					break;
				case 'C':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'A':
							result.add("TapeCartridge");
							break;
						case 'B':
							result.add("ChipCartridge");
							break;
						case 'C':
							result.add("DiscCartridge");
							break;
						case 'F':
							result.add("TapeCassette");
							break;
						case 'H':
							result.add("TapeReel");
							break;
						case 'J':
							result.add("FloppyDisk");
							break;
						case 'M':
						case 'O':
							result.add("CDROM");
							break;
						case 'R':
							// Do not return - this will cause anything with an
							// 856 field to be labeled as "Electronic"
							break;
						default:
							result.add("Software");
							break;
					}
					break;
				case 'D':
					result.add("Globe");
					break;
				case 'F':
					result.add("Braille");
					break;
				case 'G':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'C':
						case 'D':
							result.add("Filmstrip");
							break;
						case 'T':
							result.add("Transparency");
							break;
						default:
							result.add("Slide");
							break;
					}
					break;
				case 'H':
					result.add("Microfilm");
					break;
				case 'K':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'C':
							result.add("Collage");
							break;
						case 'D':
							result.add("Drawing");
							break;
						case 'E':
							result.add("Painting");
							break;
						case 'F':
							result.add("Print");
							break;
						case 'G':
							result.add("Photonegative");
							break;
						case 'J':
							result.add("Print");
							break;
						case 'L':
							result.add("Drawing");
							break;
						case 'O':
							result.add("FlashCard");
							break;
						case 'N':
							result.add("Chart");
							break;
						default:
							result.add("Photo");
							break;
					}
					break;
				case 'M':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'F':
							result.add("VideoCassette");
							break;
						case 'R':
							result.add("Filmstrip");
							break;
						default:
							result.add("MotionPicture");
							break;
					}
					break;
				case 'O':
					result.add("Kit");
					break;
				case 'Q':
					result.add("MusicalScore");
					break;
				case 'R':
					result.add("SensorImage");
					break;
				case 'S':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'D':
							if (formatField.getData().length() >= 4) {
								char speed = formatField.getData().toUpperCase().charAt(3);
								if (speed >= 'A' && speed <= 'E') {
									result.add("Phonograph");
								} else if (speed == 'F') {
									result.add("CompactDisc");
								} else if (speed >= 'K' && speed <= 'R') {
									result.add("TapeRecording");
								} else {
									result.add("SoundDisc");
								}
							} else {
								result.add("SoundDisc");
							}
							break;
						case 'S':
							result.add("SoundCassette");
							break;
						default:
							result.add("SoundRecording");
							break;
					}
					break;
				case 'T':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'A':
							result.add("Book");
							break;
						case 'B':
							result.add("LargePrint");
							break;
					}
					break;
				case 'V':
					switch (formatField.getData().toUpperCase().charAt(1)) {
						case 'C':
							result.add("VideoCartridge");
							break;
						case 'D':
							result.add("VideoDisc");
							break;
						case 'F':
							result.add("VideoCassette");
							break;
						case 'R':
							result.add("VideoReel");
							break;
						default:
							result.add("Video");
							break;
					}
					break;
			}
		}
	}


	private void getFormatFromLeader(Set<String> result, String leader, ControlField fixedField) {
		char leaderBit;
		char formatCode;// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					result.add("MusicalScore");
					break;
				case 'E':
				case 'F':
					result.add("Map");
					break;
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// result.add("Slide");
					result.add("Video");
					break;
				case 'I':
					result.add("SoundRecording");
					break;
				case 'J':
					result.add("MusicRecording");
					break;
				case 'K':
					result.add("Photo");
					break;
				case 'M':
					result.add("Electronic");
					break;
				case 'O':
				case 'P':
					result.add("Kit");
					break;
				case 'R':
					result.add("PhysicalObject");
					break;
				case 'T':
					result.add("Manuscript");
					break;
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					if (result.isEmpty()) {
						result.add("Book");
					}
					break;
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					if (fixedField != null && fixedField.getData().length() >= 22) {
						formatCode = fixedField.getData().toUpperCase().charAt(21);
						switch (formatCode) {
							case 'N':
								result.add("Newspaper");
								break;
							case 'P':
								result.add("Journal");
								break;
							default:
								result.add("Serial");
								break;
						}
					}
			}
		}
	}

	/**
	 * Load information about eContent formats.
	 *
	 * @param record         The MARC record information
	 * @param econtentRecord The record to load format information for
	 * @param econtentItem   The item to load format information for
	 */
	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {

	}

	private char getSubfieldIndicatorFromConfig(ResultSet indexingProfileRS, String subfieldName) throws SQLException{
		String subfieldString = indexingProfileRS.getString(subfieldName);
		char subfield = ' ';
		if (!indexingProfileRS.wasNull() && subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

	public String translateValue(String mapName, String value, String identifier){
		return translateValue(mapName, value, identifier, true);
	}
	public String translateValue(String mapName, String value, String identifier, boolean reportErrors){
		if (value == null){
			return null;
		}
		TranslationMap translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName + " in profile " + profileType);
			translatedValue = value;
		}else{
			translatedValue = translationMap.translateValue(value, identifier, reportErrors);
		}
		return translatedValue;
	}

	HashSet<String> translateCollection(String mapName, Set<String> values, String identifier) {
		TranslationMap translationMap = translationMaps.get(mapName);
		HashSet<String> translatedValues;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName + " in profile " + profileType);
			if (values instanceof HashSet){
				translatedValues = (HashSet<String>)values;
			}else{
				translatedValues = new HashSet<>();
				translatedValues.addAll(values);
			}

		}else{
			translatedValues = translationMap.translateCollection(values, identifier);
		}
		return translatedValues;

	}
}
