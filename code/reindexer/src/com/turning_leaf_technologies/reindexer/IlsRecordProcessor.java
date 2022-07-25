package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.IndexingProfile;
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

	private String recordNumberTag;
	String itemTag;
	int itemTagInt;
	char formatSubfield;
	boolean checkRecordForLargePrint;
	char barcodeSubfield;
	char statusSubfieldIndicator;
	Pattern statusesToSuppressPattern = null;
	private Pattern nonHoldableStatuses;
	protected boolean treatLibraryUseOnlyGroupedStatusesAsAvailable;
	char shelvingLocationSubfield;
	char collectionSubfield;
	private char dueDateSubfield;
	SimpleDateFormat dueDateFormatter;
	private char lastCheckInSubfield;
	private String lastCheckInFormat;
	private char dateCreatedSubfield;
	private String dateAddedFormat;
	char locationSubfieldIndicator;
	boolean includeLocationNameInDetailedLocation;
	private Pattern nonHoldableLocations;
	Pattern locationsToSuppressPattern = null;
	Pattern collectionsToSuppressPattern = null;
	char subLocationSubfield;
	char iTypeSubfield;
	private Pattern nonHoldableITypes;
	protected Pattern iTypesToSuppress;
	boolean useEContentSubfield = false;
	char eContentSubfieldIndicator;
	private Pattern suppressRecordsWithUrlsMatching;
	private char lastYearCheckoutSubfield;
	private char ytdCheckoutSubfield;
	private char totalCheckoutSubfield;
	boolean useICode2Suppression;
	char iCode2Subfield;
	protected Pattern iCode2sToSuppress;
	protected Pattern bCode3sToSuppress;
	private boolean useItemBasedCallNumbers;
	private char callNumberPrestampSubfield;
	private char callNumberSubfield;
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volumeSubfield;
	char itemRecordNumberSubfieldIndicator;
	private char itemUrlSubfieldIndicator;
	boolean suppressItemlessBibs;

	private int determineAudienceBy;
	private char audienceSubfield;
	private String treatUnknownAudienceAs;

	private int determineLiteraryFormBy;
	private char literaryFormSubfield;
	private boolean hideUnknownLiteraryForm;
	private boolean hideNotCodedLiteraryForm;

	//Fields for loading order information
	private String orderTag;
	private char orderLocationSubfield;
	private char singleOrderLocationSubfield;
	private char orderCopiesSubfield;
	private char orderStatusSubfield;
	private char orderCode3Subfield;

	private final HashMap<String, TranslationMap> translationMaps = new HashMap<>();
	private final ArrayList<TimeToReshelve> timesToReshelve = new ArrayList<>();
	protected final HashSet<String> formatsToSuppress = new HashSet<>();
	protected final HashSet<String> statusesToSuppress = new HashSet<>();
	private final HashSet<String> inLibraryUseOnlyFormats = new HashSet<>();
	private final HashSet<String> inLibraryUseOnlyStatuses = new HashSet<>();
	private final HashSet<String> nonHoldableFormats = new HashSet<>();
	protected boolean suppressRecordsWithNoCollection = true;

	private PreparedStatement loadHoldsStmt;
	private PreparedStatement addTranslationMapValueStmt;
	private PreparedStatement updateRecordSuppressionReasonStmt;

	IlsRecordProcessor(GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, logger);
		this.fullReindex = fullReindex;
		try {
			settings = new IndexingProfile(indexingProfileRS);
			profileType = indexingProfileRS.getString("name");
			individualMarcPath = indexingProfileRS.getString("individualMarcPath");
			marcPath = indexingProfileRS.getString("marcPath");
			numCharsToCreateFolderFrom         = indexingProfileRS.getInt("numCharsToCreateFolderFrom");
			createFolderFromLeadingCharacters  = indexingProfileRS.getBoolean("createFolderFromLeadingCharacters");

			recordNumberTag = indexingProfileRS.getString("recordNumberTag");
			suppressItemlessBibs = indexingProfileRS.getBoolean("suppressItemlessBibs");

			itemTag = indexingProfileRS.getString("itemTag");
			itemTagInt = indexingProfileRS.getInt("itemTag");
			itemRecordNumberSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemRecordNumber");

			callNumberPrestampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPrestamp");
			callNumberSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumber");
			callNumberCutterSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberCutter");
			callNumberPoststampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPoststamp");

			locationSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "location");
			includeLocationNameInDetailedLocation = indexingProfileRS.getBoolean("includeLocationNameInDetailedLocation");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableLocations");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableLocations = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load non holdable locations", e);
			}
			subLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "subLocation");
			shelvingLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "shelvingLocation");
			collectionSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "collection");
			String locationsToSuppress = indexingProfileRS.getString("locationsToSuppress");
			if (locationsToSuppress != null && locationsToSuppress.length() > 0){
				locationsToSuppressPattern = Pattern.compile(locationsToSuppress);
			}

			String collectionsToSuppress = indexingProfileRS.getString("collectionsToSuppress");
			if (collectionsToSuppress != null && collectionsToSuppress.length() > 0){
				collectionsToSuppressPattern = Pattern.compile(collectionsToSuppress);
			}

			itemUrlSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemUrl");

			formatSource = indexingProfileRS.getString("formatSource");
			fallbackFormatField = indexingProfileRS.getString("fallbackFormatField");
			specifiedFormat = indexingProfileRS.getString("specifiedFormat");
			specifiedFormatCategory = indexingProfileRS.getString("specifiedFormatCategory");
			specifiedFormatBoost = indexingProfileRS.getInt("specifiedFormatBoost");
			formatSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "format");
			checkRecordForLargePrint = indexingProfileRS.getBoolean("checkRecordForLargePrint");
			barcodeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "barcode");
			if (itemRecordNumberSubfieldIndicator == ' '){
				itemRecordNumberSubfieldIndicator = barcodeSubfield;
			}
			statusSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "status");
			String statusesToSuppress = indexingProfileRS.getString("statusesToSuppress");
			if (statusesToSuppress != null && statusesToSuppress.length() > 0){
				statusesToSuppressPattern = Pattern.compile(statusesToSuppress);
			}

			try {
				String pattern = indexingProfileRS.getString("nonHoldableStatuses");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableStatuses = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load non holdable statuses", e);
			}

			treatLibraryUseOnlyGroupedStatusesAsAvailable = indexingProfileRS.getBoolean("treatLibraryUseOnlyGroupedStatusesAsAvailable");

			dueDateSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dueDate");
			String dueDateFormat = indexingProfileRS.getString("dueDateFormat");
			if (dueDateFormat != null && dueDateFormat.length() > 0) {
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
				indexer.getLogEntry().incErrors("Could not load non holdable iTypes", e);
			}
			try {
				String pattern = indexingProfileRS.getString("iTypesToSuppress");
				if (pattern != null && pattern.length() > 0) {
					iTypesToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load iTypes to Suppress", e);
			}

			dateCreatedSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dateCreated");
			dateAddedFormat = indexingProfileRS.getString("dateCreatedFormat");

			lastCheckInSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "lastCheckinDate");
			lastCheckInFormat = indexingProfileRS.getString("lastCheckinFormat");

			iCode2Subfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "iCode2");
			useICode2Suppression = indexingProfileRS.getBoolean("useICode2Suppression");

			try {
				String pattern = indexingProfileRS.getString("iCode2sToSuppress");
				if (pattern != null && pattern.length() > 0) {
					iCode2sToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load iCode2s to Suppress", e);
			}

			try {
				String pattern = indexingProfileRS.getString("bCode3sToSuppress");
				if (pattern != null && pattern.length() > 0) {
					bCode3sToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load bCode3s to Suppress", e);
			}

			eContentSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "eContentDescriptor");
			useEContentSubfield = eContentSubfieldIndicator != ' ';

			String suppressRecordsWithUrlsMatching = indexingProfileRS.getString("suppressRecordsWithUrlsMatching");
			if (suppressRecordsWithUrlsMatching != null && suppressRecordsWithUrlsMatching.length() == 0){
				this.suppressRecordsWithUrlsMatching = null;
			}else {
				this.suppressRecordsWithUrlsMatching = Pattern.compile(suppressRecordsWithUrlsMatching, Pattern.CASE_INSENSITIVE);
			}

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
			customMarcFieldsToIndexAsKeyword = indexingProfileRS.getString("customMarcFieldsToIndexAsKeyword");

			determineAudienceBy = indexingProfileRS.getInt("determineAudienceBy");
			audienceSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "audienceSubfield");
			treatUnknownAudienceAs = indexingProfileRS.getString("treatUnknownAudienceAs");

			determineLiteraryFormBy = indexingProfileRS.getInt("determineLiteraryFormBy");
			literaryFormSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "literaryFormSubfield");
			hideUnknownLiteraryForm = indexingProfileRS.getBoolean("hideUnknownLiteraryForm");
			hideNotCodedLiteraryForm = indexingProfileRS.getBoolean("hideNotCodedLiteraryForm");

			loadHoldsStmt = dbConn.prepareStatement("SELECT ilsId, numHolds from ils_hold_summary where ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addTranslationMapValueStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");
			updateRecordSuppressionReasonStmt = dbConn.prepareStatement("UPDATE ils_records set suppressed=?, suppressionNotes=? where source=? and ilsId=?");

			loadTranslationMapsForProfile(dbConn, indexingProfileRS.getLong("id"));

			loadTimeToReshelve(dbConn, indexingProfileRS.getLong("id"));
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error loading indexing profile information from database", e);
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
			TranslationMap map = new TranslationMap(profileType, translationsMapRS.getLong("id"), translationsMapRS.getString("name"), translationsMapRS.getBoolean("usesRegularExpressions"), logger);
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
				formatsToSuppress.add(format.toUpperCase());
			}
			if (formatMapRS.getBoolean("inLibraryUseOnly")){
				inLibraryUseOnlyFormats.add(format.toUpperCase());
			}
			if (formatMapRS.getString("holdType").equals("none")){
				nonHoldableFormats.add(formatMapRS.getString("format").toUpperCase());
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


	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, Record record, String identifier) {
		//For ILS Records, we can create multiple different records, one for print and order items,
		//and one or more for eContent items.
		HashSet<RecordInfo> allRelatedRecords = new HashSet<>();

		try{
			//If the entire bib is suppressed, update stats and bail out now.
			if (isBibSuppressed(record, identifier)){
				return;
			}

			// Let's first look for the print/order record
			RecordInfo recordInfo = groupedWork.addRelatedRecord(profileType, identifier);
			logger.debug("Added record for " + identifier + " work now has " + groupedWork.getNumRecords() + " records");
			StringBuilder suppressionNotes = new StringBuilder();
			suppressionNotes = loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record, suppressionNotes);
			loadOnOrderItems(groupedWork, recordInfo, record, recordInfo.getNumPrintCopies() > 0);
			//If we don't get anything remove the record we just added
			if (checkIfBibShouldBeRemovedAsItemless(recordInfo)) {
				groupedWork.removeRelatedRecord(recordInfo);
				logger.debug("Removing related print record for " + identifier + " because there are no print copies, no on order copies and suppress itemless bibs is on");
				suppressionNotes.append("Record had no items<br/>");
				updateRecordSuppression(true, suppressionNotes, identifier);
			}else{
				allRelatedRecords.add(recordInfo);
				updateRecordSuppression(false, suppressionNotes, identifier);
			}

			//Since print formats are loaded at the record level, do it after we have loaded items
			loadPrintFormatInformation(recordInfo, record);

			//Now look for any eContent that is defined within the ils
			List<RecordInfo> econtentRecords = loadUnsuppressedEContentItems(groupedWork, identifier, record, suppressionNotes);
			allRelatedRecords.addAll(econtentRecords);

			//Updates based on the overall bib (shared regardless of scoping)
			String primaryFormat = null;
			String primaryFormatCategory = null;
			for (RecordInfo ilsRecord : allRelatedRecords) {
				primaryFormat = ilsRecord.getPrimaryFormat();
				primaryFormatCategory = ilsRecord.getPrimaryFormatCategory();
				if (primaryFormatCategory != null && primaryFormat != null){
					break;
				}
			}
			if (primaryFormat == null/* || primaryFormat.equals("Unknown")*/) {
				primaryFormat = "Unknown";
				//logger.info("No primary format for " + recordInfo.getRecordIdentifier() + " found setting to unknown to load standard marc data");
			}
			if (primaryFormatCategory == null/* || primaryFormat.equals("Unknown")*/) {
				primaryFormatCategory = "Unknown";
				//logger.info("No primary format for " + recordInfo.getRecordIdentifier() + " found setting to unknown to load standard marc data");
			}
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, recordInfo.getRelatedItems(), identifier, primaryFormat, primaryFormatCategory);

			//Special processing for ILS Records
			String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(record, "520a"));
			for (RecordInfo ilsRecord : allRelatedRecords) {
				String primaryFormatForRecord = ilsRecord.getPrimaryFormat();
				if (primaryFormatForRecord == null){
					primaryFormatForRecord = "Unknown";
				}
				String primaryFormatCategoryForRecord = ilsRecord.getPrimaryFormatCategory();
				if (primaryFormatCategoryForRecord == null){
					primaryFormatCategoryForRecord = "Unknown";
				}
				groupedWork.addDescription(fullDescription, primaryFormatForRecord, primaryFormatCategoryForRecord);
			}
			loadEditions(groupedWork, record, allRelatedRecords);
			loadPhysicalDescription(groupedWork, record, allRelatedRecords);
			loadLanguageDetails(groupedWork, record, allRelatedRecords, identifier);
			loadPublicationDetails(groupedWork, record, allRelatedRecords);
			loadClosedCaptioning(groupedWork, record, allRelatedRecords);

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
			indexer.getLogEntry().incErrors("Error updating grouped work " + groupedWork.getId() + " for MARC record with identifier " + identifier, e);
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

	//static Pattern eContentUrlPattern = Pattern.compile("overdrive\\.com|contentreserve\\.com|hoopla|yourcloudlibrary|axis360\\.baker-taylor\\.com", Pattern.CASE_INSENSITIVE);
	//Suppress all marc records for eContent that can be loaded via API
	protected boolean isBibSuppressed(Record record, String identifier) {
		if (suppressRecordsWithUrlsMatching != null) {
			Set<String> urls = MarcUtil.getFieldList(record, "856u");
			for (String url : urls) {
				//Suppress if the url is an overdrive or hoopla url
				if (suppressRecordsWithUrlsMatching.matcher(url).find()) {
					updateRecordSuppression(true, new StringBuilder().append("Suppressed due to 856u"), identifier);
					return true;
				}
			}
		}
		return false;
	}

	protected void updateRecordSuppression(boolean suppressed, StringBuilder suppressionNotes, String identifier){
		try{
			String notes;
			if (suppressionNotes.length() > 65000){
				notes = suppressionNotes.substring(0, 65000);
			}else{
				notes = suppressionNotes.toString();
			}
			updateRecordSuppressionReasonStmt.setInt(1, suppressed ? 1 : 0);
			updateRecordSuppressionReasonStmt.setString(2, notes);
			updateRecordSuppressionReasonStmt.setString(3, profileType);
			updateRecordSuppressionReasonStmt.setString(4, identifier);
			int numUpdated = updateRecordSuppressionReasonStmt.executeUpdate();
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error updating record suppression", e);
		}
	}

	protected String getSubfieldData(DataField dataField, char subfield){
		if (subfield == ' '){
			return null;
		}else {
			Subfield subfieldObject = dataField.getSubfield(subfield);
			if (subfieldObject != null) {
				return subfieldObject.getData();
			} else {
				return null;
			}
		}
	}

	protected void loadOnOrderItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		if (orderTag.length() == 0){
			return;
		}
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
								indexer.getLogEntry().incErrors("Error parsing copies and location for order item " + tmpLocation);
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
										indexer.getLogEntry().incErrors("StringIndexOutOfBoundsException loading number of copies " + copiesData, e);
									} catch (Exception e) {
										indexer.getLogEntry().incErrors("Exception loading number of copies " + copiesData, e);
									} catch (Error e) {
										indexer.getLogEntry().incErrors("Error loading number of copies " + copiesData + " " + e);
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
		itemInfo.setDetailedLocation("On Order");

		recordInfo.addItem(itemInfo);

		return true;
	}

	private void loadScopeInfoForOrderItem(AbstractGroupedWorkSolr groupedWork, String location, String format, TreeSet<String> audiences, String audiencesAsString, ItemInfo itemInfo, Record record) {
		//Shelf Location also include the name of the ordering branch if possible
		boolean hasLocationBasedShelfLocation = false;
		boolean hasSystemBasedShelfLocation = false;
		String originalUrl = itemInfo.geteContentUrl();
		String fullKey = profileType + location;

		String itemIdentifier = itemInfo.getItemIdentifier();
		for (Scope scope: indexer.getScopes()){
			Scope.InclusionResult result = scope.isItemPartOfScope(itemIdentifier, fullKey, profileType, location, "", null, audiences, audiencesAsString, format, true, true, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(scope);
				if (scopingInfo == null){
					indexer.getLogEntry().incErrors("Could not add scoping information for " + scope.getScopeName() + " for item " + itemInfo.getFullRecordIdentifier());
					continue;
				}
				groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
				if (scope.isLocationScope()) { //Either a location scope or both library and location scope
					boolean itemIsOwned = scope.isItemOwnedByScope(itemInfo.getItemIdentifier(), fullKey, profileType, location, "");
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (scope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
						if (itemIsOwned && itemInfo.getShelfLocation().equals("On Order")){
							itemInfo.setShelfLocation("On Order");
							itemInfo.setDetailedLocation(scopingInfo.getScope().getFacetLabel() + " On Order");
						}
					}
				}else if (scope.isLibraryScope()) {
					boolean libraryOwned = scope.isItemOwnedByScope(itemInfo.getItemIdentifier(), fullKey, profileType, location, "");
					scopingInfo.setLibraryOwned(libraryOwned);
					//TODO: Should this be here or should this only happen for consortia?
					if (libraryOwned && itemInfo.getShelfLocation().equals("On Order")){
						itemInfo.setShelfLocation("On Order");
						itemInfo.setDetailedLocation(scopingInfo.getScope().getFacetLabel() + " On Order");
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

				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1");
	}

	private void loadOrderIds(AbstractGroupedWorkSolr groupedWork, Record record) {
		//Load order ids from recordNumberTag
		Set<String> recordIds = MarcUtil.getFieldList(record, recordNumberTag + "a");
		for(String recordId : recordIds){
			if (recordId.startsWith(".o")){
				groupedWork.addAlternateId(recordId);
			}
		}
	}

	protected StringBuilder loadUnsuppressedPrintItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record, StringBuilder suppressionNotes){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTagInt);
		logger.debug("Found " + itemRecords.size() + " items for record " + identifier);
		for (DataField itemField : itemRecords){
			String itemIdentifier = getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField);
			ResultWithNotes isSuppressed = isItemSuppressed(itemField, itemIdentifier, suppressionNotes);
			suppressionNotes = isSuppressed.notes;
			if (!isSuppressed.result){
				ItemInfoWithNotes itemInfoWithNotes = createPrintIlsItem(groupedWork, recordInfo, record, itemField, suppressionNotes);
				suppressionNotes = itemInfoWithNotes.notes;
				//Can return null if the record does not have status and location
				//This happens with secondary call numbers sometimes.
			}else{
				logger.debug("item was suppressed");
			}
		}
		return suppressionNotes;
	}

	RecordInfo getEContentIlsRecord(AbstractGroupedWorkSolr groupedWork, Record record, String identifier, DataField itemField){
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
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation, identifier, true));
		}
		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField), identifier, true));
		loadItemCallNumber(record, itemField, itemInfo);
		itemInfo.setItemIdentifier(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		itemInfo.setShelfLocation(getShelfLocationForItem(itemField, identifier));
		itemInfo.setDetailedLocation(getDetailedLocationForItem(itemInfo, itemField, identifier));

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField), identifier, true));

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
			List<DataField> urlFields = MarcUtil.getDataFields(record, 856);
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
		itemInfo.setGroupedStatus("Available Online");

		//If we don't get a URL, return null since it isn't valid
		if (itemInfo.geteContentUrl() == null || itemInfo.geteContentUrl().length() == 0){
			return null;
		}else{
			return relatedRecord;
		}
	}

	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		String dateAddedStr = getItemSubfieldData(dateCreatedSubfield, itemField);
		if (dateAddedStr != null && dateAddedStr.length() > 0) {
			if (dateAddedStr.equals("NEVER")) {
				logger.info("Date Added was never");
			}else {
				try {
					if (dateAddedFormatter == null) {
						dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
					}

					Date dateAdded = dateAddedFormatter.parse(dateAddedStr);
					itemInfo.setDateAdded(dateAdded);
				} catch (ParseException e) {
					if (dateAddedStr.length() == 6) {
						if (dateAddedFormatter2 == null) {
							dateAddedFormatter2 = new SimpleDateFormat("yyMMdd");
						}
						try {
							Date dateAdded = dateAddedFormatter2.parse(dateAddedStr);
							itemInfo.setDateAdded(dateAdded);
						}catch (ParseException e2){
							indexer.getLogEntry().addNote("Error processing date added for record identifier " + recordIdentifier + " profile " + profileType + " using format " + dateAddedFormat + " and yyMMdd " + e2);
						}
					}else {
						indexer.getLogEntry().addNote("Error processing date added for record identifier " + recordIdentifier + " profile " + profileType + " using format " + dateAddedFormat + " " + e);
					}
				}
			}
		}
	}

	protected String getSourceType(Record record, DataField itemField) {
		return "Unknown Source";
	}

	private SimpleDateFormat dateAddedFormatter = null;
	private SimpleDateFormat dateAddedFormatter2 = null;
	private SimpleDateFormat lastCheckInFormatter = null;
	private final HashSet<String> unhandledFormatBoosts = new HashSet<>();
	ItemInfoWithNotes createPrintIlsItem(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField, StringBuilder suppressionNotes) {
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
			suppressionNotes.append(itemInfo.getItemIdentifier()).append(" status matched suppression table<br/>");
			return new ItemInfoWithNotes(null, suppressionNotes);
		}

		String itemLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = getItemSubfieldData(subLocationSubfield, itemField);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		itemInfo.setSubLocationCode(itemSublocation);
		if (itemSublocation.length() > 0){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation, recordInfo.getRecordIdentifier(), true));
		}else{
			itemInfo.setSubLocation("");
		}

		//if the status and location are null, we can assume this is not a valid item
		if (isItemInvalid(itemStatus, itemLocation)) return null;

		setShelfLocationCode(itemField, itemInfo, recordInfo.getRecordIdentifier());
		itemInfo.setShelfLocation(getShelfLocationForItem(itemField, recordInfo.getRecordIdentifier()));
		itemInfo.setDetailedLocation(getDetailedLocationForItem(itemInfo, itemField, recordInfo.getRecordIdentifier()));

		loadDateAdded(recordInfo.getRecordIdentifier(), itemField, itemInfo);
		getDueDate(itemField, itemInfo);

		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField), recordInfo.getRecordIdentifier(), true));

		itemInfo.setVolumeField(getItemSubfieldData(volumeSubfield, itemField));

		double itemPopularity = getItemPopularity(itemField);
		groupedWork.addPopularity(itemPopularity);

		loadItemCallNumber(record, itemField, itemInfo);

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField), recordInfo.getRecordIdentifier(), true));

		if (lastCheckInFormatter != null) {
			String lastCheckInDate = getItemSubfieldData(lastCheckInSubfield, itemField);
			Date lastCheckIn = null;
			if (lastCheckInDate != null && lastCheckInDate.length() > 0 && !lastCheckInDate.equals("-  -")) {
				try {
					lastCheckIn = lastCheckInFormatter.parse(lastCheckInDate);
				} catch (ParseException e) {
					logger.debug("Could not parse check in date " + lastCheckInDate, e);
				}
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
				if (hasTranslation("format", format)) {
					itemInfo.setFormat(translateValue("format", format, recordInfo.getRecordIdentifier()));
				}
				if (hasTranslation("format_category", format)) {
					itemInfo.setFormatCategory(translateValue("format_category", format, recordInfo.getRecordIdentifier()));
				}
				String formatBoost = null;
				if (hasTranslation("format_boost", format)) {
					formatBoost = translateValue("format_boost", format, recordInfo.getRecordIdentifier());
				}
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

		groupedWork.addKeywords(itemLocation);
		if (itemSublocation.length() > 0){
			groupedWork.addKeywords(itemSublocation);
		}

		itemInfo.setMarcField(itemField);

		recordInfo.addItem(itemInfo);

		return new ItemInfoWithNotes(itemInfo, suppressionNotes);
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

	private void scopeItems(RecordInfo recordInfo, AbstractGroupedWorkSolr groupedWork, Record record){
		for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
			if (itemInfo.isOrderItem()){
				itemInfo.setAvailable(false);
				itemInfo.setHoldable(true);
				itemInfo.setDetailedStatus("On Order");
				itemInfo.setGroupedStatus("On Order");
				loadScopeInfoForOrderItem(groupedWork, itemInfo.getLocationCode(), recordInfo.getPrimaryFormat(), groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), itemInfo, record);
			}else if (itemInfo.isEContent()){
				itemInfo.setAvailable(true);
				itemInfo.setDetailedStatus("Available Online");
				itemInfo.setGroupedStatus("Available Online");
				itemInfo.setHoldable(false);
				loadScopeInfoForEContentItem(groupedWork, itemInfo, record);
			}else{
				loadScopeInfoForPrintIlsItem(groupedWork, recordInfo, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), itemInfo, record);
			}
		}
	}

	private void loadScopeInfoForEContentItem(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, Record record) {
		String itemLocation = itemInfo.getLocationCode();
		String originalUrl = itemInfo.geteContentUrl();
		String fullKey = profileType + itemLocation;
		for (Scope curScope : indexer.getScopes()){
			String format = itemInfo.getFormat();
			if (format == null){
				format = itemInfo.getRecordInfo().getPrimaryFormat();
			}
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemInfo.getItemIdentifier(), fullKey, profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, false, false, true, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);
				if (curScope.isLocationScope()) {  //Either a location scope or both library and location scope
					boolean itemIsOwned = curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), fullKey, profileType, itemLocation, "");
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (curScope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
					}
				}else if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), fullKey, profileType, itemLocation, ""));
				}
				//Check to see if we need to do url rewriting
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	private void loadScopeInfoForPrintIlsItem(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, TreeSet<String> audiences, String audiencesAsString, ItemInfo itemInfo, Record record) {
		//Determine status, need to do this before determining if it is available since that is part of the check.
		String recordIdentifier = recordInfo.getRecordIdentifier();
		String displayStatus = getDisplayStatus(itemInfo, recordIdentifier);
		String groupedDisplayStatus = getDisplayGroupedStatus(itemInfo, recordIdentifier);

		//Determine Availability
		boolean available = isItemAvailable(itemInfo, displayStatus, groupedDisplayStatus);

		//Determine which scopes have access to this record
		String overiddenStatus = getOverriddenStatus(itemInfo, true);
		if (overiddenStatus != null && !overiddenStatus.equals("On Shelf") && !overiddenStatus.equals("Library Use Only") && !overiddenStatus.equals("Available Online")){
			available = false;
		}

		itemInfo.setAvailable(available);

		itemInfo.setDetailedStatus(displayStatus);
		itemInfo.setGroupedStatus(groupedDisplayStatus);

		itemInfo.setInLibraryUseOnly(determineLibraryUseOnly(itemInfo));

		String itemLocation = itemInfo.getLocationCode();
		String itemSublocation = itemInfo.getSubLocationCode();

		boolean isHoldableUnscoped = isItemHoldableUnscoped(itemInfo);
		itemInfo.setHoldable(isHoldableUnscoped);
		String originalUrl = itemInfo.geteContentUrl();
		String primaryFormat = recordInfo.getPrimaryFormat();
		String fullKey = profileType + itemLocation + itemSublocation;
		String itemIdentifier = itemInfo.getItemIdentifier();
		for (Scope curScope : indexer.getScopes()) {
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemIdentifier, fullKey, profileType, itemLocation, itemSublocation, itemInfo.getITypeCode(), audiences, audiencesAsString, primaryFormat, isHoldableUnscoped, false, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);

				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(result.isOwned);
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope(itemInfo.getItemIdentifier(), fullKey, profileType, itemLocation, itemSublocation));
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(result.isOwned);
				}
			}
		}
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo) {
		if (inLibraryUseOnlyStatuses.contains(itemInfo.getStatusCode()) || itemInfo.getGroupedStatus().equals("Library Use Only")){
			return true;
		}
		String format = itemInfo.getFormat();
		if (format == null){
			format = itemInfo.getRecordInfo().getPrimaryFormat();
		}
		return inLibraryUseOnlyFormats.contains(format.toUpperCase());
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
			if (fullCallNumber.length() > 0){
				hasCallNumber = true;
				itemInfo.setCallNumber(fullCallNumber.toString().trim());
				itemInfo.setSortableCallNumber(sortableCallNumber.toString().trim());
			}
		}
		if (!hasCallNumber){
			StringBuilder callNumber = null;
			if (use099forBibLevelCallNumbers()) {
				DataField localCallNumberField = record.getDataField(99);
				if (localCallNumberField != null) {
					callNumber = new StringBuilder();
					for (Subfield curSubfield : localCallNumberField.getSubfields()) {
						callNumber.append(" ").append(curSubfield.getData().trim());
					}
				}
			}
			//MDN #ARL-217 do not use 099 as a call number
			if (callNumber == null) {
				DataField deweyCallNumberField = record.getDataField(92);
				if (deweyCallNumberField != null) {
					callNumber = new StringBuilder();
					for (Subfield curSubfield : deweyCallNumberField.getSubfields()) {
						callNumber.append(" ").append(curSubfield.getData().trim());
					}
				}
			}
			if (callNumber == null) {
				DataField deweyCallNumberField = record.getDataField(82);
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
			}else if (volume != null){
				itemInfo.setCallNumber(volume.trim());
				itemInfo.setSortableCallNumber(volume.trim());
			}
		}else{
			String callNumber = itemInfo.getCallNumber();
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

	protected boolean use099forBibLevelCallNumbers() {
		return true;
	}

	private final HashMap<String, Boolean> iTypesThatHaveHoldabilityChecked = new HashMap<>();
	private final HashMap<String, Boolean> locationsThatHaveHoldabilityChecked = new HashMap<>();
	private final HashMap<String, Boolean> statusesThatHaveHoldabilityChecked = new HashMap<>();

	protected boolean isItemHoldableUnscoped(ItemInfo itemInfo){
		String itemItypeCode =  itemInfo.getITypeCode();
		if (nonHoldableITypes != null && itemItypeCode != null && itemItypeCode.length() > 0){
			Boolean cachedValue = iTypesThatHaveHoldabilityChecked.get(itemItypeCode);
			if (cachedValue == null){
				cachedValue = !nonHoldableITypes.matcher(itemItypeCode).matches();
				iTypesThatHaveHoldabilityChecked.put(itemItypeCode, cachedValue);
			}
			if (!cachedValue){
				return false;
			}
		}
		String itemLocationCode =  itemInfo.getLocationCode();
		if (nonHoldableLocations != null && itemLocationCode != null && itemLocationCode.length() > 0){
			Boolean cachedValue = locationsThatHaveHoldabilityChecked.get(itemLocationCode);
			if (cachedValue == null){
				cachedValue = !nonHoldableLocations.matcher(itemLocationCode).matches();
				locationsThatHaveHoldabilityChecked.put(itemLocationCode, cachedValue);
			}
			if (!cachedValue){
				return false;
			}
		}
		String itemStatusCode = itemInfo.getStatusCode();
		if (nonHoldableStatuses != null && itemStatusCode != null && itemStatusCode.length() > 0){
			Boolean cachedValue = statusesThatHaveHoldabilityChecked.get(itemStatusCode);
			if (cachedValue == null){
				cachedValue = !nonHoldableStatuses.matcher(itemStatusCode).matches();
				statusesThatHaveHoldabilityChecked.put(itemStatusCode, cachedValue);
			}
			if (!cachedValue){
				return false;
			}
		}
		String format = itemInfo.getPrimaryFormatUppercase();
		return !nonHoldableFormats.contains(format.toUpperCase());
	}

	String getShelfLocationForItem(DataField itemField, String identifier) {
		String shelfLocation = null;
		if (itemField != null) {
			shelfLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		}
		if (shelfLocation == null || shelfLocation.length() == 0 || shelfLocation.equals("none")){
			return "";
		}else {
			return translateValue("shelf_location", shelfLocation, identifier, true);
		}
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location;
		String subLocationCode = getItemSubfieldData(subLocationSubfield, itemField);
		if (includeLocationNameInDetailedLocation) {
			String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
			location = translateValue("location", locationCode, identifier, true);
			if (location == null){
				location = "";
			}
		}else{
			location = "";
		}
		if (subLocationCode != null && subLocationCode.length() > 0){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && translatedSubLocation.length() > 0) {
				if (location.length() > 0) {
					location += " - ";
				}
				location += translateValue("sub_location", subLocationCode, identifier, true);
			}
		}
		String shelfLocation = null;
		if (itemField != null) {
			shelfLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		}
		if (shelfLocation != null && shelfLocation.length() > 0 && !shelfLocation.equals("none")){
			if (location.length() > 0) {
				location += " - ";
			}
			location += translateValue("shelf_location", shelfLocation, identifier, true);
		}
		return location;
	}

	protected String getItemStatus(DataField itemField, String recordIdentifier){
		return getItemSubfieldData(statusSubfieldIndicator, itemField);
	}

	protected abstract boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus);

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
						indexer.getLogEntry().incErrors("Error determining if the new value is already part of the string", e);
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

	protected List<RecordInfo> loadUnsuppressedEContentItems(AbstractGroupedWorkSolr groupedWork, String identifier, Record record, StringBuilder suppressionNotes){
		return new ArrayList<>();
	}

	private void loadPopularity(AbstractGroupedWorkSolr groupedWork, String recordIdentifier) {
		//Add popularity based on the number of holds (we have already done popularity for prior checkouts)
		//Active holds indicate that a title is more interesting so we will count each hold at double value
		int numHolds = getIlsHoldsForTitle(recordIdentifier);
		groupedWork.addHolds(numHolds);
		double popularity = 2 * numHolds;
		groupedWork.addPopularity(popularity);
	}

	private int getIlsHoldsForTitle(String recordIdentifier) {
		int numHolds = 0;
		try{
			loadHoldsStmt.setString(1, recordIdentifier);
			ResultSet holdsRS = loadHoldsStmt.executeQuery();
			if (holdsRS.next()) {
				numHolds = holdsRS.getInt("numHolds");
			}

		} catch (Exception e){
			logger.error("Unable to load hold data", e);
		}
		return numHolds;
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			if (statusSubfield == null) {
				suppressionNotes.append("Item ").append(itemIdentifier).append(" - no status<br>");
				return new ResultWithNotes(true, suppressionNotes);
			} else {
				String statusValue = statusSubfield.getData();
				if (statusesToSuppressPattern != null && statusesToSuppressPattern.matcher(statusValue).matches()) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - matched status suppression pattern<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}else if (statusesToSuppress.contains(statusValue)){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - status suppressed in Indexing Profile<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}

			}
		}
		Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationSubfield == null){
			suppressionNotes.append("Item ").append(itemIdentifier).append(" no location<br/>");
			return new ResultWithNotes(true, suppressionNotes);
		}else{
			if (locationsToSuppressPattern != null && locationsToSuppressPattern.matcher(locationSubfield.getData().trim()).matches()){
				suppressionNotes.append("Item ").append(itemIdentifier).append(" location matched suppression pattern<br/>");
				return new ResultWithNotes(true, suppressionNotes);
			}
		}
		if (collectionSubfield != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(collectionSubfield);
			if (collectionSubfieldValue == null){
				if (this.suppressRecordsWithNoCollection) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" no collection<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}else{
				if (collectionsToSuppressPattern != null && collectionsToSuppressPattern.matcher(collectionSubfieldValue.getData().trim()).matches()){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" collection matched suppression pattern<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (formatSubfield != ' '){
			Subfield formatSubfieldValue = curItem.getSubfield(formatSubfield);
			if (formatSubfieldValue != null){
				String formatValue = formatSubfieldValue.getData();
				if (formatsToSuppress.contains(formatValue.toUpperCase())){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" format suppressed in formats table<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (iTypeSubfield != ' '){
			Subfield iTypeSubfieldValue = curItem.getSubfield(iTypeSubfield);
			if (iTypeSubfieldValue != null){
				String iTypeValue = iTypeSubfieldValue.getData();
				if (iTypesToSuppress != null && iTypesToSuppress.matcher(iTypeValue).matches()){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" iType matched suppression pattern<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}

		return new ResultWithNotes(false, suppressionNotes);
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
			logger.warn("Did not find a format for " + recordInfo.getRecordIdentifier() + " using standard format method " + printFormats);
		}
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats, recordInfo.getRecordIdentifier());
		recordInfo.addFormats(translatedFormats);
		recordInfo.addFormatCategories(translatedFormatCategories);
		long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats, recordInfo.getRecordIdentifier());
		for (String tmpFormatBoost : formatBoosts) {
			try {
				long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			} catch (NumberFormatException e) {
				if (!unableToTranslateWarnings.contains("no_format_boost_" + tmpFormatBoost)){
					indexer.getLogEntry().addNote("Could not load format boost for format " + tmpFormatBoost + " profile " + profileType);
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
		return translateValue(mapName, value, identifier, true, false);
	}

	public String translateValue(String mapName, String value, String identifier, boolean addMissingValues){
		return translateValue(mapName, value, identifier, true, addMissingValues);
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
	public String translateValue(String mapName, String value, String identifier, boolean reportErrors, boolean addMissingValues){
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
					indexer.getLogEntry().addNote("Unable to find translation map for " + mapName);
					unableToTranslateWarnings.add("unable_to_find_" + mapName);
				}
			} else {
				if (addMissingValues){
					TranslationMap translationMap = translationMaps.get(mapName);
					translationMap.addValue(value, value);
					try {
						addTranslationMapValueStmt.setLong(1, translationMap.getId());
						addTranslationMapValueStmt.setString(2, lowerCaseValue);
						addTranslationMapValueStmt.setString(3, value);
						addTranslationMapValueStmt.executeUpdate();
					}catch (SQLException e){
						indexer.getLogEntry().incErrors("Unable to add missing translation map value " + value + " to " + mapName);
					}
					return value;
				}else {
					//Check to see if this is translated based off of regular expression
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)) {
						if (reportErrors) {
							indexer.getLogEntry().addNote("Could not translate '" + concatenatedValue + "' in profile " + profileType + " sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
				}
			}
		}
		return translatedValue;
	}

	HashSet<String> translateCollection(String mapName, Set<String> values, String identifier) {
		return translateCollection(mapName, values, identifier, false);
	}

	HashSet<String> translateCollection(String mapName, Set<String> values, String identifier, boolean addMissingValues) {
		HashSet<String> translatedValues = new HashSet<>();
		for (String value : values){
			String translatedValue = translateValue(mapName, value, identifier, addMissingValues);
			if (translatedValue != null){
				translatedValues.add(translatedValue);
			}
		}

		return translatedValues;
	}

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier) {
		if (determineAudienceBy == 0) {
			super.loadTargetAudiences(groupedWork, record, printItems, identifier, treatUnknownAudienceAs);
		}else{
			HashSet<String> targetAudiences = new HashSet<>();
			if (determineAudienceBy == 1) {
				//Load based on collection
				for (ItemInfo printItem : printItems){
					String collection = printItem.getCollection();
					if (collection != null) {
						targetAudiences.add(collection.toLowerCase());
					}
				}
			}else if (determineAudienceBy == 2) {
				//Load based on shelf location
				for (ItemInfo printItem : printItems){
					String shelfLocationCode = printItem.getShelfLocationCode();
					if (shelfLocationCode != null) {
						targetAudiences.add(shelfLocationCode.toLowerCase());
					}
				}
			}else if (determineAudienceBy == 3){
				//Load based on a specified subfield
				for (ItemInfo printItem : printItems){
					List<String> audienceCodes = printItem.getSubfields(audienceSubfield);
					for (String audienceCode : audienceCodes) {
						String audienceCodeLower = audienceCode.toLowerCase();
						if (hasTranslation("audience", audienceCodeLower)) {
							targetAudiences.add(audienceCodeLower);
						}
					}
				}
			}
			HashSet<String> translatedAudiences = translateCollection("audience", targetAudiences, identifier, true);

			if (!treatUnknownAudienceAs.equals("Unknown") && translatedAudiences.contains("Unknown")) {
				translatedAudiences.remove("Unknown");
				translatedAudiences.add(treatUnknownAudienceAs);
			}
			if (translatedAudiences.size() == 0){
				//We didn't get anything from the items (including Unknown), check the bib record
				super.loadTargetAudiences(groupedWork, record, printItems, identifier, treatUnknownAudienceAs);
			}else {
				groupedWork.addTargetAudiences(translatedAudiences);
				groupedWork.addTargetAudiencesFull(translatedAudiences);
			}
		}
	}

	protected void loadLiteraryForms(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier) {
		if (determineLiteraryFormBy == 0){
			super.loadLiteraryForms(groupedWork, record, printItems, identifier);
		}else{
			//Load based on a subfield of the items
			for (ItemInfo printItem : printItems) {
				Subfield subfield = printItem.getMarcField().getSubfield(literaryFormSubfield);
				if (subfield != null){
					if (subfield.getData() != null){
						String translatedValue = translateValue("literary_form", subfield.getData(), identifier, true);
						if (translatedValue != null) {
							groupedWork.addLiteraryForm(translatedValue);
							groupedWork.addLiteraryFormFull(translatedValue);
						}
					}
				}
			}
		}
	}

	public boolean isHideUnknownLiteraryForm() {
		return hideUnknownLiteraryForm;
	}

	public boolean isHideNotCodedLiteraryForm() {
		return hideNotCodedLiteraryForm;
	}

	@Override
	protected void getFormatFromFallbackField(Record record, LinkedHashSet<String> printFormats) {
		Set<String> fields = MarcUtil.getFieldList(record, fallbackFormatField);
		for (String curField : fields) {
			if (hasTranslation("format", curField.toLowerCase())){
				printFormats.add(curField);
			}
		}
	}
}
