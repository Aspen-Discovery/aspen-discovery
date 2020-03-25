package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.indexing.TranslationMap;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Pattern;

abstract class IlsRecordProcessor extends MarcRecordProcessor {
	protected boolean fullReindex;
	String marcPath;
	String profileType;

	private String recordNumberTag;
	String itemTag;
	char formatSubfield;
	boolean checkRecordForLargePrint;
	char barcodeSubfield;
	char statusSubfieldIndicator;
	Pattern statusesToSuppressPattern = null;
	private Pattern nonHoldableStatuses;
	char shelvingLocationSubfield;
	char collectionSubfield;
	private char dueDateSubfield;
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

	private HashMap<String, Integer> numberOfHoldsByIdentifier = new HashMap<>();

	private HashMap<String, TranslationMap> translationMaps = new HashMap<>();
	private ArrayList<TimeToReshelve> timesToReshelve = new ArrayList<>();
	private HashSet<String> formatsToSuppress = new HashSet<>();
	private HashSet<String> statusesToSuppress = new HashSet<>();
	private HashSet<String> inLibraryUseOnlyFormats = new HashSet<>();
	private HashSet<String> inLibraryUseOnlyStatuses = new HashSet<>();

	IlsRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, logger);
		this.fullReindex = fullReindex;
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
			checkRecordForLargePrint = indexingProfileRS.getBoolean("checkRecordForLargePrint");
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

			treatUnknownLanguageAs = indexingProfileRS.getString("treatUnknownLanguageAs");
			treatUndeterminedLanguageAs = indexingProfileRS.getString("treatUndeterminedLanguageAs");

			//loadAvailableItemBarcodes(marcRecordPath, logger);
			loadHoldsByIdentifier(dbConn, logger);

			loadTranslationMapsForProfile(dbConn, indexingProfileRS.getLong("id"));

			loadTimeToReshelve(dbConn, indexingProfileRS.getLong("id"));
		}catch (Exception e){
			logger.error("Error loading indexing profile information from database", e);
		}
	}

	private void loadTimeToReshelve(Connection dbConn, long id) throws SQLException{
		PreparedStatement getTimesToReshelveStmt = dbConn.prepareStatement("SELECT * from time_to_reshelve WHERE indexingProfileId = ? ORDER by weight");
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
	private void loadTranslationMapsForProfile(Connection dbConn, long id) throws SQLException{
		//TODO: Load default system values which will be overwritten below


		PreparedStatement getTranslationMapsStmt = dbConn.prepareStatement("SELECT * from translation_maps WHERE indexingProfileId = ?");
		PreparedStatement getTranslationMapValuesStmt = dbConn.prepareStatement("SELECT * from translation_map_values WHERE translationMapId = ?");
		getTranslationMapsStmt.setLong(1, id);
		ResultSet translationsMapRS = getTranslationMapsStmt.executeQuery();
		while (translationsMapRS.next()){
			TranslationMap map = new TranslationMap(profileType, translationsMapRS.getString("name"), translationsMapRS.getBoolean("usesRegularExpressions"), logger);
			long translationMapId = translationsMapRS.getLong("id");
			getTranslationMapValuesStmt.setLong(1, translationMapId);
			ResultSet translationMapValuesRS = getTranslationMapValuesStmt.executeQuery();
			while (translationMapValuesRS.next()){
				map.addValue(translationMapValuesRS.getString("value"), translationMapValuesRS.getString("translation"));
			}
			translationMaps.put(map.getMapName(), map);
		}

		//Status and Format maps are special.  We store them as part of the indexing profile.
		PreparedStatement getFormatMapStmt = dbConn.prepareStatement("SELECT * from format_map_values WHERE indexingProfileId = ?");
		getFormatMapStmt.setLong(1, id);
		ResultSet formatMapRS = getFormatMapStmt.executeQuery();
		TranslationMap formatMap = new TranslationMap(profileType, "format", false, logger);
		translationMaps.put(formatMap.getMapName(), formatMap);
		TranslationMap formatCategoryMap = new TranslationMap(profileType, "format_category", false, logger);
		translationMaps.put(formatCategoryMap.getMapName(), formatCategoryMap);
		TranslationMap formatBoostMap = new TranslationMap(profileType, "format_boost", false, logger);
		translationMaps.put(formatBoostMap.getMapName(), formatBoostMap);
		while (formatMapRS.next()){
			String format = formatMapRS.getString("value");
			if (formatMapRS.getBoolean("suppress")){
				formatsToSuppress.add(format);
			}
			if (formatMapRS.getBoolean("inLibraryUseOnly")){
				inLibraryUseOnlyFormats.add(format);
			}
			formatMap.addValue(format, formatMapRS.getString("format"));
			formatCategoryMap.addValue(format, formatMapRS.getString("formatCategory"));
			formatBoostMap.addValue(format, formatMapRS.getString("formatBoost"));
		}
		formatMapRS.close();

		PreparedStatement getStatusMapStmt = dbConn.prepareStatement("SELECT * from status_map_values WHERE indexingProfileId = ?");
		getStatusMapStmt.setLong(1, id);
		ResultSet statusMapRS = getStatusMapStmt.executeQuery();
		TranslationMap itemStatusMap = new TranslationMap(profileType, "item_status", false, logger);
		translationMaps.put(itemStatusMap.getMapName(), itemStatusMap);
		TranslationMap itemGroupedStatusMap = new TranslationMap(profileType, "item_grouped_status", false, logger);
		translationMaps.put(itemGroupedStatusMap.getMapName(), itemGroupedStatusMap);
		while (statusMapRS.next()){
			String status = statusMapRS.getString("value");
			if (statusMapRS.getBoolean("suppress")){
				statusesToSuppress.add(status);
			}
			if (statusMapRS.getBoolean("inLibraryUseOnly")){
				inLibraryUseOnlyStatuses.add(status);
			}
			itemStatusMap.addValue(status, statusMapRS.getString("status"));
			itemGroupedStatusMap.addValue(status, statusMapRS.getString("groupedStatus"));
		}
		statusMapRS.close();
	}

	private void loadHoldsByIdentifier(Connection dbConn, Logger logger) {
		try{
			PreparedStatement loadHoldsStmt = dbConn.prepareStatement("SELECT ilsId, numHolds from ils_hold_summary", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet holdsRS = loadHoldsStmt.executeQuery();
			while (holdsRS.next()) {
				numberOfHoldsByIdentifier.put(holdsRS.getString("ilsId"), holdsRS.getInt("numHolds"));
			}

		} catch (Exception e){
			logger.error("Unable to load hold data", e);
		}
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
			groupedWork.addHoldings(numPrintItems + recordInfo.getNumCopiesOnOrder() + recordInfo.getNumEContentCopies());

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
		itemInfo.setIsOrderItem();
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
				createPrintIlsItem(groupedWork, recordInfo, record, itemField);
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
		RecordInfo relatedRecord;

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

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (StringUtils.isNumeric(eContentFields[3].trim())){
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
			itemInfo.seteContentSource(getSourceType(record, itemField));
		}

		//Set record type
		relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
		relatedRecord.setSubSource(profileType);
		relatedRecord.addItem(itemInfo);

		loadEContentFormatInformation(record, relatedRecord, itemInfo);

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(itemUrlSubfieldIndicator);
		if (urlSubfield != null){
			itemInfo.seteContentUrl(urlSubfield.getData().trim());
		}else{
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

		//If we don't get a URL, return null since it isn't valid
		if (itemInfo.geteContentUrl() == null || itemInfo.geteContentUrl().length() == 0){
			return null;
		}else{
			//System.out.println(identifier + "\t" + itemInfo.getItemIdentifier() + "\t" + itemInfo.geteContentUrl());
			return relatedRecord;
		}
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
	private HashSet<String> unhandledFormatBoosts = new HashSet<>();
	void createPrintIlsItem(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField) {
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
		if (statusesToSuppress.contains(itemStatus)){
			return;
		}

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
		if (isItemInvalid(itemStatus, itemLocation)) return;

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
					if (!unhandledFormatBoosts.contains(format)){
						unhandledFormatBoosts.add(format);
						logger.warn("Could not get boost for format " + format);
					}
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

	private void scopeItems(RecordInfo recordInfo, GroupedWorkSolr groupedWork, Record record){
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
		if (inLibraryUseOnlyStatuses.contains(itemInfo.getStatusCode())){
			return true;
		}
		if (inLibraryUseOnlyFormats.contains(itemInfo.getFormat())){
			return true;
		}
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
						itemInfo.setAutoReindexTime((itemInfo.getLastCheckinDate().getTime() / 1000) + (timeToReshelve.getNumHoursToOverride() * 60 * 60) + 1);
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

	protected boolean isItemInvalid(String itemStatus, String itemLocation) {
		return itemStatus == null && itemLocation == null;
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
				if (fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
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
			StringBuilder callNumber = null;
			if (use099forBibLevelCallNumbers()) {
				DataField localCallNumberField = record.getDataField("099");
				if (localCallNumberField != null) {
					callNumber = new StringBuilder();
					for (Subfield curSubfield : localCallNumberField.getSubfields()) {
						callNumber.append(" ").append(curSubfield.getData().trim());
					}
				}
			}
			//MDN #ARL-217 do not use 099 as a call number
			if (callNumber == null) {
				DataField deweyCallNumberField = record.getDataField("092");
				if (deweyCallNumberField != null) {
					callNumber = new StringBuilder();
					for (Subfield curSubfield : deweyCallNumberField.getSubfields()) {
						callNumber.append(" ").append(curSubfield.getData().trim());
					}
				}
			}
			if (callNumber != null) {

				if (volume != null && volume.length() > 0 && !callNumber.toString().endsWith(volume)){
					if (callNumber.length() > 0 && callNumber.charAt(callNumber.length() - 1) != ' '){
						callNumber.append(" ");
					}
					callNumber.append(volume);
				}
				itemInfo.setCallNumber(callNumber.toString().trim());
				itemInfo.setSortableCallNumber(callNumber.toString().trim());
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
				return new HoldabilityInformation(false, new HashSet<>());
			}
		}
		String itemLocationCode =  itemInfo.getLocationCode();
		if (nonHoldableLocations != null && itemLocationCode != null && itemLocationCode.length() > 0){
			if (!locationsThatHaveHoldabilityChecked.containsKey(itemLocationCode)){
				locationsThatHaveHoldabilityChecked.put(itemLocationCode, !nonHoldableLocations.matcher(itemLocationCode).matches());
			}
			if (!locationsThatHaveHoldabilityChecked.get(itemLocationCode)){
				return new HoldabilityInformation(false, new HashSet<>());
			}
		}
		String itemStatusCode = itemInfo.getStatusCode();
		if (nonHoldableStatuses != null && itemStatusCode != null && itemStatusCode.length() > 0){
			if (!statusesThatHaveHoldabilityChecked.containsKey(itemStatusCode)){
				statusesThatHaveHoldabilityChecked.put(itemStatusCode, !nonHoldableStatuses.matcher(itemStatusCode).matches());
			}
			if (!statusesThatHaveHoldabilityChecked.get(itemStatusCode)){


				return new HoldabilityInformation(false, new HashSet<>());
			}
		}
		return new HoldabilityInformation(true, new HashSet<>());
	}

	protected HoldabilityInformation isItemHoldable(ItemInfo itemInfo, Scope curScope, HoldabilityInformation isHoldableUnscoped){
		return isHoldableUnscoped;
	}

	private BookabilityInformation isItemBookableUnscoped(){
		return new BookabilityInformation(false, new HashSet<>());
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

	private void loadPopularity(GroupedWorkSolr groupedWork, String recordIdentifier) {
		//Add popularity based on the number of holds (we have already done popularity for prior checkouts)
		//Active holds indicate that a title is more interesting so we will count each hold at double value
		double popularity = 2 * getIlsHoldsForTitle(recordIdentifier);
		groupedWork.addPopularity(popularity);
	}

	private int getIlsHoldsForTitle(String recordIdentifier) {
		return numberOfHoldsByIdentifier.getOrDefault(recordIdentifier, 0);
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			if (statusSubfield == null) {
				return true;
			} else {
				String statusValue = statusSubfield.getData();
				if (statusesToSuppressPattern != null && statusesToSuppressPattern.matcher(statusValue).matches()) {
					return true;
				}else if (statusesToSuppress.contains(statusValue)){
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
		if (formatSubfield != ' '){
			Subfield formatSubfieldValue = curItem.getSubfield(formatSubfield);
			if (formatSubfieldValue != null){
				String formatValue = formatSubfieldValue.getData();
				//noinspection RedundantIfStatement
				if (formatsToSuppress.contains(formatValue)){
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
				if (!unableToTranslateWarnings.contains("no_format_boost_" + tmpFormatBoost)){
					logger.error("Could not load format boost for format " + tmpFormatBoost + " profile " + profileType);
					unableToTranslateWarnings.add("no_format_boost_" + tmpFormatBoost);
				}
			}
		}
		recordInfo.setFormatBoost(formatBoost);
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
		return StringUtils.convertStringToChar(subfieldString);
	}

	public String translateValue(String mapName, String value, String identifier){
		return translateValue(mapName, value, identifier, true);
	}

	boolean hasTranslation(String mapName, String value) {
		TranslationMap translationMap = translationMaps.get(mapName);
		if (translationMap != null){
			return translationMap.hasTranslation(value);
		}else{
			return false;
		}

	}

	HashSet<String> unableToTranslateWarnings = new HashSet<>();
	public String translateValue(String mapName, String value, String identifier, boolean reportErrors){
		if (value == null){
			return null;
		}

		String translatedValue;
		String lowerCaseValue = value.toLowerCase();
		if (hasTranslation(mapName, lowerCaseValue)) {
			TranslationMap translationMap = translationMaps.get(mapName);
			translatedValue = translationMap.translateValue(lowerCaseValue, identifier, reportErrors);
		} else if (indexer.hasSystemTranslation(mapName, lowerCaseValue)){
			translatedValue = indexer.translateSystemValue(mapName, lowerCaseValue, identifier);
		} else {
			translatedValue = value;
			//Error handling
			if (!translationMaps.containsKey(mapName)) {
				if (!unableToTranslateWarnings.contains("unable_to_find_" + mapName)) {
					logger.error("Unable to find translation map for " + mapName);
					unableToTranslateWarnings.add("unable_to_find_" + mapName);
				}
			} else {
				//Check to see if this is translated based off of regular expression
				String concatenatedValue = mapName + ":" + value;
				if (!unableToTranslateWarnings.contains(concatenatedValue)) {
					if (reportErrors) {
						logger.warn("Could not translate '" + concatenatedValue + "' in profile " + profileType + " sample record " + identifier);
					}
					unableToTranslateWarnings.add(concatenatedValue);
				}
			}
		}
		return translatedValue;
	}

	HashSet<String> translateCollection(String mapName, Set<String> values, String identifier) {
		HashSet<String> translatedValues = new HashSet<>();
		for (String value : values){
			String translatedValue = translateValue(mapName, value, identifier);
			if (translatedValue != null){
				translatedValues.add(translatedValue);
			}
		}

		return translatedValues;

	}
}
