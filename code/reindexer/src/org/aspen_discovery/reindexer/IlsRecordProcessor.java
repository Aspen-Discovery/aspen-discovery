package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.format_classification.IIIRecordFormatClassifier;
import org.aspen_discovery.format_classification.IlsRecordFormatClassifier;
import org.aspen_discovery.format_classification.KohaRecordFormatClassifier;
import org.aspen_discovery.format_classification.NashvilleRecordFormatClassifier;
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

	protected IndexingProfile settings;

	private Pattern nonHoldableITypes;
	protected Pattern iTypesToSuppress;
	private Pattern suppressRecordsWithUrlsMatching;

	protected Pattern iCode2sToSuppress;
	protected Pattern bCode3sToSuppress;

	private final HashMap<String, TranslationMap> translationMaps = new HashMap<>();
	private final ArrayList<TimeToReshelve> timesToReshelve = new ArrayList<>();
	protected final HashSet<String> formatsToSuppress = new HashSet<>();
	protected final HashSet<String> statusesToSuppress = new HashSet<>();
	private final HashSet<String> inLibraryUseOnlyFormats = new HashSet<>();
	private final HashSet<String> inLibraryUseOnlyStatuses = new HashSet<>();
	private final HashSet<String> nonHoldableFormats = new HashSet<>();

	//This gets overridden by ILS Processors
	protected boolean suppressRecordsWithNoCollection = true;

	private PreparedStatement loadHoldsStmt;
	private PreparedStatement addTranslationMapValueStmt;
	private PreparedStatement updateRecordSuppressionReasonStmt;
	private PreparedStatement loadChildRecordsStmt;
	private PreparedStatement loadParentRecordsStmt;

	IlsRecordProcessor(String serverName, GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, logger);
		this.fullReindex = fullReindex;
		try {
			settings = new IndexingProfile(serverName, indexingProfileRS, dbConn, indexer.getLogEntry());
			super.settings = this.settings;
			profileType = indexingProfileRS.getString("name");

			//Setup format classifier
			switch (settings.getIndexingClass()) {
				case "III":
					formatClassifier = new IIIRecordFormatClassifier(logger);
					break;
				case "Koha":
					formatClassifier = new KohaRecordFormatClassifier(logger);
					break;
				case "NashvilleCarlX":
					formatClassifier = new NashvilleRecordFormatClassifier(logger);
					break;
				default:
					formatClassifier = new IlsRecordFormatClassifier(logger);
					break;
			}

			try {
				String pattern = indexingProfileRS.getString("nonHoldableITypes");
				if (pattern != null && !pattern.isEmpty()) {
					nonHoldableITypes = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load non holdable iTypes", e);
			}
			try {
				String pattern = indexingProfileRS.getString("iTypesToSuppress");
				if (pattern != null && !pattern.isEmpty()) {
					iTypesToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load iTypes to Suppress", e);
			}

			try {
				String pattern = indexingProfileRS.getString("iCode2sToSuppress");
				if (pattern != null && !pattern.isEmpty()) {
					iCode2sToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load iCode2s to Suppress", e);
			}

			try {
				String pattern = indexingProfileRS.getString("bCode3sToSuppress");
				if (pattern != null && !pattern.isEmpty()) {
					bCode3sToSuppress = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
				}
			}catch (Exception e){
				indexer.getLogEntry().incErrors("Could not load bCode3s to Suppress", e);
			}

			String suppressRecordsWithUrlsMatching = indexingProfileRS.getString("suppressRecordsWithUrlsMatching");
			if (suppressRecordsWithUrlsMatching == null || suppressRecordsWithUrlsMatching.isEmpty()){
				this.suppressRecordsWithUrlsMatching = null;
			}else {
				this.suppressRecordsWithUrlsMatching = Pattern.compile(suppressRecordsWithUrlsMatching, Pattern.CASE_INSENSITIVE);
			}

			loadHoldsStmt = dbConn.prepareStatement("SELECT ilsId, numHolds from ils_hold_summary where ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addTranslationMapValueStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");
			updateRecordSuppressionReasonStmt = dbConn.prepareStatement("UPDATE ils_records set suppressed=?, suppressionNotes=? where source=? and ilsId=?");
			loadChildRecordsStmt = dbConn.prepareStatement("SELECT * from record_parents where parentRecordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadParentRecordsStmt = dbConn.prepareStatement("SELECT * from record_parents where childRecordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

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
				map.addValue(translationMapValuesRS.getString("value"), translationMapValuesRS.getString("translation"), indexer.getLogEntry());
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
			formatMap.addValue(format, formatMapRS.getString("format"), indexer.getLogEntry());
			formatCategoryMap.addValue(format, formatMapRS.getString("formatCategory"), indexer.getLogEntry());
			formatBoostMap.addValue(format, formatMapRS.getString("formatBoost"), indexer.getLogEntry());
		}
		formatMapRS.close();

		PreparedStatement getStatusMapStmt = dbConn.prepareStatement("SELECT * from status_map_values WHERE indexingProfileId = ?");
		getStatusMapStmt.setLong(1, id);
		ResultSet statusMapRS = getStatusMapStmt.executeQuery();
		TranslationMap itemStatusMap = new TranslationMap(profileType, "item_status", false, logger);
		translationMaps.put(itemStatusMap.getMapName(), itemStatusMap);
		TranslationMap itemGroupedStatusMap = new TranslationMap(profileType, "item_grouped_status", false, logger);
		translationMaps.put(itemGroupedStatusMap.getMapName(), itemGroupedStatusMap);
		TranslationMap itemStatusAltMap = new TranslationMap(profileType, "item_status_alt", false, logger);
		translationMaps.put(itemStatusAltMap.getMapName(), itemStatusAltMap);
		TranslationMap itemGroupedStatusAltMap = new TranslationMap(profileType, "item_grouped_status_alt", false, logger);
		translationMaps.put(itemGroupedStatusAltMap.getMapName(), itemGroupedStatusAltMap);
		while (statusMapRS.next()){
			String status = statusMapRS.getString("value");
			if (statusMapRS.getBoolean("suppress")){
				statusesToSuppress.add(status);
			}
			if (statusMapRS.getBoolean("inLibraryUseOnly")){
				inLibraryUseOnlyStatuses.add(status);
			}
			if (statusMapRS.getBoolean("appliesToStatusSubfield")) {
				itemStatusMap.addValue(status, statusMapRS.getString("status"), indexer.getLogEntry());
				itemGroupedStatusMap.addValue(status, statusMapRS.getString("groupedStatus"), indexer.getLogEntry());
			}
			if (statusMapRS.getBoolean("appliesToStatusAltSubfield")) {
				itemStatusAltMap.addValue(status, statusMapRS.getString("status"), indexer.getLogEntry());
				itemGroupedStatusAltMap.addValue(status, statusMapRS.getString("groupedStatus"), indexer.getLogEntry());
			}
		}
		statusMapRS.close();
	}


	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier) {
		//For ILS Records, we can create multiple different records, one for print and order items,
		//and one or more for eContent items.
		HashSet<RecordInfo> allRelatedRecords = new HashSet<>();

		try{

			//If the entire bib is suppressed, update stats and bail out now.
			if (isBibSuppressed(record, identifier)){
				return;
			}

			//Check to see if we have child records to load
			boolean hasChildRecords = false;
			String firstParentId = null;
			if (settings.isProcessRecordLinking()){
				hasChildRecords = loadChildRecords(groupedWork, identifier);
				firstParentId = loadParentRecords(groupedWork, identifier);
			}

			// Let's first look for the print/order record
			RecordInfo recordInfo = groupedWork.addRelatedRecord(profileType, identifier);

			if (hasChildRecords) {
				recordInfo.setHasChildRecord(true);
			}
			if (firstParentId != null) {
				recordInfo.setHasParentRecord(true);
			}
			loadMarcHoldingItems(recordInfo, record);

			logger.debug("Added record for " + identifier + " work now has " + groupedWork.getNumRecords() + " records");
			StringBuilder suppressionNotes = new StringBuilder();
			suppressionNotes = loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record, suppressionNotes);
			loadOnOrderItems(groupedWork, recordInfo, record, recordInfo.getNumPrintCopies() > 0);

			//Now look for any eContent that is defined within the ils
			List<RecordInfo> econtentRecords = loadUnsuppressedEContentItems(groupedWork, identifier, record, suppressionNotes, recordInfo, firstParentId != null, hasChildRecords);
			if (!econtentRecords.isEmpty()) {
				allRelatedRecords.addAll(econtentRecords);
			}

			//check for cases where we need a virtual record
			if (hasChildRecords) {
				//If we have child records, it's very likely that we don't have real items, so we need to create a virtual one for scoping.
				ItemInfo virtualItem = new ItemInfo();
				virtualItem.setIsVirtualChildRecord(true);
				recordInfo.addItem(virtualItem);
			}

			//If we don't get anything remove the record we just added
			if (checkIfBibShouldBeRemovedAsItemless(recordInfo)) {
				groupedWork.removeRelatedRecord(recordInfo);
				logger.debug("Removing related print record for " + identifier + " because there are no print copies, no on order copies and suppress itemless bibs is on");
				suppressionNotes.append("Record had no items<br/>");
				updateRecordSuppression(true, suppressionNotes, identifier);
			} else {
				allRelatedRecords.add(recordInfo);
				updateRecordSuppression(false, suppressionNotes, identifier);
			}

			//Since print formats are loaded at the record level, do it after we have loaded items
			//TODO: Is this necessary? Or can we switch to using the Format Classifier?
			loadPrintFormatInformation(groupedWork, recordInfo, record, hasChildRecords);

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
			if (primaryFormat == null || primaryFormat.equals("Unknown")) {
				primaryFormat = "Unknown";
				primaryFormatCategory = "Unknown";
				//logger.info("No primary format for " + recordInfo.getRecordIdentifier() + " found setting to unknown to load standard marc data");
			}
			if (primaryFormatCategory == null/* || primaryFormat.equals("Unknown")*/) {
				primaryFormatCategory = "Unknown";
				//logger.info("No primary format for " + recordInfo.getRecordIdentifier() + " found setting to unknown to load standard marc data");
			}
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, recordInfo.getRelatedItems(), identifier, primaryFormat, primaryFormatCategory, firstParentId != null);

			//Special processing for ILS Records
			String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(record, "520ac"));
			for (RecordInfo ilsRecord : allRelatedRecords) {
				String primaryFormatCategoryForRecord = ilsRecord.getPrimaryFormatCategory();
				if (primaryFormatCategoryForRecord == null){
					primaryFormatCategoryForRecord = "Unknown";
				}
				groupedWork.addDescription(fullDescription, primaryFormatCategoryForRecord);
				groupedWork.addIlsDescription(fullDescription, primaryFormatCategoryForRecord);
			}
			loadEditions(groupedWork, record, allRelatedRecords);
			loadAudiences(groupedWork, record, allRelatedRecords);
			loadPhysicalDescription(groupedWork, record, allRelatedRecords);
			loadLanguageDetails(groupedWork, record, allRelatedRecords, identifier);
			loadPublicationDetails(groupedWork, record, allRelatedRecords);
			loadClosedCaptioning(record, allRelatedRecords);

			if (record.getControlNumber() != null){
				groupedWork.addKeywords(record.getControlNumber());
			}

			//Perform updates based on items
			loadPopularity(groupedWork, identifier);
			groupedWork.addBarcodes(MarcUtil.getFieldList(record, settings.getItemTag() + settings.getBarcodeSubfield()));

			loadOrderIds(groupedWork, record);

			int numPrintItems = recordInfo.getNumPrintCopies();

			groupedWork.addHoldings(numPrintItems + recordInfo.getNumCopiesOnOrder() + recordInfo.getNumEContentCopies());

			for (ItemInfo curItem : recordInfo.getRelatedItems()){
				String itemIdentifier = curItem.getItemIdentifier();
				if (itemIdentifier != null && !itemIdentifier.isEmpty()) {
					groupedWork.addAlternateId(itemIdentifier);
				}
			}

			for (RecordInfo recordInfoTmp: allRelatedRecords) {
				scopeItems(recordInfoTmp, groupedWork, record);
			}

			//Load Custom Facets
			loadCustomFacets(groupedWork, record, recordInfo);
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error updating grouped work " + groupedWork.getId() + " for MARC record with identifier " + identifier, e);
		}
	}

	private void loadCustomFacets(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, RecordInfo recordInfo) {
		loadCustomFacet(1, settings.getCustomFacet1SourceField(), settings.getCustomFacet1ValuesToIncludePattern(), settings.getCustomFacet1ValuesToExcludePattern(), groupedWork, record, recordInfo);
		loadCustomFacet(2, settings.getCustomFacet2SourceField(), settings.getCustomFacet2ValuesToIncludePattern(), settings.getCustomFacet2ValuesToExcludePattern(), groupedWork, record, recordInfo);
		loadCustomFacet(3, settings.getCustomFacet3SourceField(), settings.getCustomFacet3ValuesToIncludePattern(), settings.getCustomFacet3ValuesToExcludePattern(), groupedWork, record, recordInfo);
	}

	private void loadCustomFacet(int customFacetNumber, String sourceField, Pattern valuesToIncludePattern, Pattern valuesToExcludePattern, AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, RecordInfo recordInfo) {
		if (!sourceField.isEmpty()) {
			Set<String> fieldData = MarcUtil.getFieldList(record, sourceField);
			if (!fieldData.isEmpty()) {
				if (valuesToIncludePattern != null) {
					Set<String> includedValues = new LinkedHashSet<>();
					for (String curValue : fieldData) {
						if (valuesToIncludePattern.matcher(curValue).matches()) {
							includedValues.add(curValue);
						}
					}
					fieldData = includedValues;
				}
				if (valuesToExcludePattern != null && !fieldData.isEmpty()) {
					Set<String> includedValues = new LinkedHashSet<>();
					for (String curValue : fieldData) {
						if (!valuesToExcludePattern.matcher(curValue).matches()) {
							includedValues.add(curValue);
						}
					}
					fieldData = includedValues;
				}
				//Map the values
				TranslationMap customFacetMap = translationMaps.get("customFacet" + customFacetNumber);
				if (translationMaps.containsKey("customFacet" + customFacetNumber)) {
					LinkedHashSet<String> mappedValues = new LinkedHashSet<>();
					for (String curValue : fieldData) {
						String translatedValue = customFacetMap.translateValue(curValue, recordInfo.getRecordIdentifier(), false);
						if (translatedValue == null) {
							mappedValues.add(curValue);
						} else {
							mappedValues.add(translatedValue);
						}
					}
					fieldData = mappedValues;
				}
			}
			groupedWork.addCustomFacetValues(customFacetNumber, fieldData);
		}
	}

	private boolean loadChildRecords(AbstractGroupedWorkSolr groupedWork, String identifier) {
		boolean hasChildRecords = false;
		try {
			loadChildRecordsStmt.setString(1, identifier);
			ResultSet childRecordsRS = loadChildRecordsStmt.executeQuery();
			while (childRecordsRS.next()){
				String childRecordId = childRecordsRS.getString("childRecordId");
				org.marc4j.marc.Record marcRecord = indexer.loadMarcRecordFromDatabase(profileType, childRecordId, indexer.getLogEntry());
				if (marcRecord != null){
					DataField titleField = marcRecord.getDataField(245);
					if (titleField != null) {
						//noinspection SpellCheckingInspection
						String childTitle = titleField.getSubfieldsAsString("abfgnp", " ");
						groupedWork.addContents(childTitle);
						hasChildRecords = true;
					}
				}
			}
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error loading child records for MARC record with identifier " + identifier, e);
		}
		return hasChildRecords;
	}

	private String loadParentRecords(AbstractGroupedWorkSolr groupedWork, String identifier){
		String firstParentId = null;
		try {
			loadParentRecordsStmt.setString(1, identifier);
			ResultSet parentRecordsRS = loadParentRecordsStmt.executeQuery();
			while (parentRecordsRS.next()){
				String parentRecordId = parentRecordsRS.getString("parentRecordId");
				groupedWork.addParentRecord(parentRecordId);
				if (firstParentId == null) {
					firstParentId = parentRecordId;
				}
			}
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error loading parent records for MARC record with identifier " + identifier, e);
		}
		return firstParentId;
	}

	private void loadMarcHoldingItems(RecordInfo recordInfo, org.marc4j.marc.Record marcRecord) {
		//We have marc holdings if we have one or more 852 and 866 fields with subfield 6.
		boolean hasValid852 = false;
		boolean hasValid866 = false;
		List<DataField> fields852 = marcRecord.getDataFields(852);
		HashSet<String> uniqueLocationCodes = new HashSet<>();
		for (DataField field852 : fields852) {
			if (field852.getSubfield('6') != null) {
				if (field852.getSubfield('b') != null) {
					String locationCode = field852.getSubfield('b').getData();
					if (locationCode != null) {
						uniqueLocationCodes.add(locationCode);
						hasValid852 = true;
					}
				}
			}
		}
		List<DataField> fields866 = marcRecord.getDataFields(866);
		for (DataField field866 : fields866) {
			if (field866.getSubfield('6') != null) {
				hasValid866 = true;
				break;
			}
		}
		if (hasValid852 && hasValid866) {
			for(String locationCode : uniqueLocationCodes) {
				//Create virtual items with one for each location code
				ItemInfo virtualItem = new ItemInfo();
				virtualItem.setLocationCode(locationCode.trim());
				virtualItem.setShelfLocation(translateValue("shelf_location", locationCode.trim(), recordInfo.getRecordIdentifier(), true));
				virtualItem.setIsVirtualHoldingsRecord(true);
				recordInfo.addItem(virtualItem);
			}
		}
	}

	boolean checkIfBibShouldBeRemovedAsItemless(RecordInfo recordInfo) {
		if (recordInfo.hasChildRecord()) {
			return false;
		}
		return recordInfo.getNumPrintCopies() == 0 && recordInfo.getNumCopiesOnOrder() == 0  && recordInfo.getNumEContentCopies() == 0 && recordInfo.getNumVirtualItems() ==0;
	}

	//Suppress all marc records for eContent that can be loaded via API
	protected boolean isBibSuppressed(org.marc4j.marc.Record record, String identifier) {
		//Check to see if the bib is an authority
		if (record.getLeader().getTypeOfRecord() == 'z'){
			updateRecordSuppression(true, new StringBuilder().append("Suppressed because leader indicates it's an authority"), identifier);
			return true;
		}
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
			@SuppressWarnings("unused")
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

	protected void loadOnOrderItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record, boolean hasTangibleItems){
		if (settings.getOrderTag() == null || settings.getOrderTag().isEmpty()){
			return;
		}
		List<DataField> orderFields = MarcUtil.getDataFields(record, settings.getOrderTag());
		for (DataField curOrderField : orderFields){
			//Check here to make sure the order item is valid before doing further processing.
			int copies = 0;
			//If the location is multi, we actually have several records that should be processed separately
			List<Subfield> detailedLocationSubfield = curOrderField.getSubfields(settings.getOrderLocationSubfield());
			if (detailedLocationSubfield.isEmpty()){
				//Didn't get detailed locations
				if (curOrderField.getSubfield(settings.getOrderCopiesSubfield()) != null){
					copies = Integer.parseInt(curOrderField.getSubfield(settings.getOrderCopiesSubfield()).getData());
				}
				String locationCode = "multi";
				if (curOrderField.getSubfield(settings.getSingleOrderLocationSubfield()) != null){
					locationCode = curOrderField.getSubfield(settings.getSingleOrderLocationSubfield()).getData().trim();
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
						//If we only get one location in the detailed copies, we need to read the "copies" subfield rather than
						//hard coding to 1
						copies = 1;
						if (settings.getOrderCopiesSubfield() != ' ') {
							if (detailedLocationSubfield.size() == 1 && curOrderField.getSubfield(settings.getOrderCopiesSubfield()) != null) {
								String copiesData = curOrderField.getSubfield(settings.getOrderCopiesSubfield()).getData().trim();
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
		if (recordInfo.getNumCopiesOnOrder() > 0 && !hasTangibleItems){
			groupedWork.addKeywords("On Order");
			groupedWork.addKeywords("Coming Soon");
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

	private void loadScopeInfoForOrderItem(AbstractGroupedWorkSolr groupedWork, String location, String format, TreeSet<String> audiences, String audiencesAsString, ItemInfo itemInfo, org.marc4j.marc.Record record) {
		//Shelf Location also include the name of the ordering branch if possible
		boolean hasLocationBasedShelfLocation = false;
		boolean hasSystemBasedShelfLocation = false;
		String originalUrl = itemInfo.geteContentUrl();

		String itemIdentifier = itemInfo.getItemIdentifier();
		String shelfLocation = itemInfo.getShelfLocation();
		String collectionCode = itemInfo.getCollection();
		for (Scope scope: indexer.getScopes()){
			Scope.InclusionResult result = scope.isItemPartOfScope(itemIdentifier, profileType, location, "", null, audiences, audiencesAsString, format, shelfLocation, collectionCode, true, true, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(scope);
				if (scopingInfo == null){
					indexer.getLogEntry().incErrors("Could not add scoping information for " + scope.getScopeName() + " for item " + itemInfo.getFullRecordIdentifier());
					continue;
				}
				groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
				if (scope.isLocationScope()) { //Either a location scope or both library and location scope
					boolean itemIsOwned = scope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, location, "", null, audiences, audiencesAsString, format, shelfLocation, collectionCode, true, true, false, record);
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (scope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
						if (itemIsOwned && itemInfo.getShelfLocation().equals("On Order")){
							itemInfo.setShelfLocation("On Order");
							itemInfo.setDetailedLocation(scopingInfo.getScope().getFacetLabel() + " On Order");
						}
					}
				}else if (scope.isLibraryScope()) {
					boolean libraryOwned = scope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, location, "", null, audiences, audiencesAsString, format, shelfLocation, collectionCode, true, true, false, record);
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
					}
				}

				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	private void loadOrderIds(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record) {
		//Load order ids from recordNumberTag
		Set<String> recordIds = MarcUtil.getFieldList(record, settings.getRecordNumberTag() + "a");
		for(String recordId : recordIds){
			if (recordId.startsWith(".o")){
				groupedWork.addAlternateId(recordId);
			}
		}
	}

	protected StringBuilder loadUnsuppressedPrintItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, org.marc4j.marc.Record record, StringBuilder suppressionNotes){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, settings.getItemTagInt());
		logger.debug("Found " + itemRecords.size() + " items for record " + identifier);
		for (DataField itemField : itemRecords){
			String itemIdentifier = MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger);
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

	void getIlsEContentItems(org.marc4j.marc.Record record, RecordInfo mainRecordInfo, String identifier, DataField itemField){
		ItemInfo itemInfo = new ItemInfo();
		itemInfo.setIsEContent(true);

		loadDateAdded(identifier, itemField, itemInfo);
		String itemLocation = MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		if (!itemSublocation.isEmpty()){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation, identifier, true));
		}
		itemInfo.setITypeCode(MarcUtil.getItemSubfieldData(settings.getITypeSubfield(), itemField, indexer.getLogEntry(), logger));
		itemInfo.setIType(translateValue("itype", MarcUtil.getItemSubfieldData(settings.getITypeSubfield(), itemField, indexer.getLogEntry(), logger), identifier, true));
		loadItemCallNumber(record, itemField, itemInfo);
		itemInfo.setItemIdentifier(MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger));
		itemInfo.setShelfLocation(getShelfLocationForItem(itemField, identifier));
		itemInfo.setDetailedLocation(getDetailedLocationForItem(itemInfo, itemField, identifier));

		itemInfo.setCollection(translateValue("collection", MarcUtil.getItemSubfieldData(settings.getCollectionSubfield(), itemField, indexer.getLogEntry(), logger), identifier, true));

		Subfield eContentSubfield = itemField.getSubfield(settings.getEContentDescriptor());
		if (eContentSubfield != null){
			String eContentData = eContentSubfield.getData().trim();
			if (eContentData.indexOf(':') > 0) {
				String[] eContentFields = eContentData.split(":");
				//First element is the source, and we will always have at least the source and protection type
				itemInfo.seteContentSource(eContentFields[0].trim());

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (AspenStringUtils.isNumeric(eContentFields[3].trim())){
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
		mainRecordInfo.addItem(itemInfo);

		loadIlsEContentFormatInformation(record, mainRecordInfo, itemInfo);

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(settings.getItemUrl());
		if (urlSubfield != null){
			itemInfo.seteContentUrl(urlSubfield.getData().trim());
		}else{
			//Check the 856 tag to see if there is a link there
			List<DataField> urlFields = MarcUtil.getDataFields(record, 856);
			for (DataField urlField : urlFields){
				//load url into the item
				if (urlField.getSubfield('u') != null){
					urlSubfield = urlField.getSubfield('u');
					String linkText = urlSubfield.getData().trim();
					if (!linkText.isEmpty()) {
						//Try to determine if this is a resource or not.
						if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0' || urlField.getIndicator1() == '7') {
							if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '8') {
								itemInfo.seteContentUrl(urlSubfield.getData().trim());
								break;
							}
						}
					}

				}
			}

		}

		itemInfo.setDetailedStatus("Available Online");
		itemInfo.setGroupedStatus("Available Online");
	}

	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		String dateAddedStr = MarcUtil.getItemSubfieldData(settings.getDateCreatedSubfield(), itemField, indexer.getLogEntry(), logger);
		if (dateAddedStr != null && !dateAddedStr.isEmpty()) {
			if (dateAddedStr.equals("NEVER")) {
				logger.info("Date Added was never");
			}else {
				dateAddedStr = dateAddedStr.trim();
				try {
					Date dateAdded = settings.getDateAddedFormatter().parse(dateAddedStr);
					itemInfo.setDateAdded(dateAdded);
				} catch (ParseException e) {
					if (dateAddedStr.length() == 6) {
						try {
							Date dateAdded = settings.getDateAddedFormatter2().parse(dateAddedStr);
							itemInfo.setDateAdded(dateAdded);
						}catch (ParseException e2){
							indexer.getLogEntry().addNote("Error processing date added (" + dateAddedStr + ") for record identifier " + recordIdentifier + " profile " + profileType + " using both format " + settings.getDateAddedFormat() + " and yyMMdd " + e2);
						}
					}else {
						indexer.getLogEntry().addNote("Error processing date (" + dateAddedStr + ") added for record identifier " + recordIdentifier + " profile " + profileType + " using format " + settings.getDateAddedFormat() + " " + e);
					}
				}
			}
		}
	}

	protected String getSourceType(org.marc4j.marc.Record record, DataField itemField) {
		return "Unknown Source";
	}

	private SimpleDateFormat lastCheckInFormatter = null;
	ItemInfoWithNotes createPrintIlsItem(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record, DataField itemField, StringBuilder suppressionNotes) {
		if (lastCheckInFormatter == null && settings.getLastCheckinFormat() != null && !settings.getLastCheckinFormat().isEmpty()){
			lastCheckInFormatter = new SimpleDateFormat(settings.getLastCheckinFormat());
		}
		ItemInfo itemInfo = new ItemInfo();

		//Load base information from the Marc Record
		String itemIdentifier = MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger);
		if (itemIdentifier == null) {
			suppressionNotes.append("Invalid item with no item number was suppressed.</br>");
			return new ItemInfoWithNotes(null, suppressionNotes);
		}
		itemInfo.setItemIdentifier(itemIdentifier);

		ItemStatus itemStatus = getItemStatus(itemField, recordInfo.getRecordIdentifier());
		if (statusesToSuppress.contains(itemStatus.getOriginalValue())){
			suppressionNotes.append(itemInfo.getItemIdentifier()).append(" status matched suppression table<br/>");
			return new ItemInfoWithNotes(null, suppressionNotes);
		}

		String itemLocation = MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		itemInfo.setSubLocationCode(itemSublocation);
		if (!itemSublocation.isEmpty()){
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

		itemInfo.setITypeCode(MarcUtil.getItemSubfieldData(settings.getITypeSubfield(), itemField, indexer.getLogEntry(), logger));
		itemInfo.setIType(translateValue("itype", MarcUtil.getItemSubfieldData(settings.getITypeSubfield(), itemField, indexer.getLogEntry(), logger), recordInfo.getRecordIdentifier(), true));

		itemInfo.setVolumeField(MarcUtil.getItemSubfieldData(settings.getVolume(), itemField, indexer.getLogEntry(), logger));

		double itemPopularity = getItemPopularity(itemField);
		groupedWork.addPopularity(itemPopularity);

		loadItemCallNumber(record, itemField, itemInfo);

		itemInfo.setCollection(translateValue("collection", MarcUtil.getItemSubfieldData(settings.getCollectionSubfield(), itemField, indexer.getLogEntry(), logger), recordInfo.getRecordIdentifier(), true));

		if (lastCheckInFormatter != null) {
			String lastCheckInDate = MarcUtil.getItemSubfieldData(settings.getLastCheckinDateSubfield(), itemField, indexer.getLogEntry(), logger);
			Date lastCheckIn = null;
			if (lastCheckInDate != null && !lastCheckInDate.isEmpty() && !lastCheckInDate.equals("-  -")) {
				try {
					lastCheckIn = lastCheckInFormatter.parse(lastCheckInDate);
				} catch (ParseException e) {
					logger.debug("Could not parse check in date " + lastCheckInDate, e);
				}
			}
			itemInfo.setLastCheckinDate(lastCheckIn);
		}

		//set status towards the end - so we can access date added and other things that may need to
		itemInfo.setItemStatus(itemStatus);
		if (itemStatus.getOriginalValue() != null) {
			setDetailedStatus(itemInfo, itemField, itemStatus, recordInfo.getRecordIdentifier());
		}

		if (settings.getFormatSource().equals("item")){
			formatClassifier.loadItemFormat(groupedWork, recordInfo, itemField, itemInfo, settings, indexer.getLogEntry(), logger);
		}

		groupedWork.addKeywords(itemLocation);
		if (!itemSublocation.isEmpty()){
			groupedWork.addKeywords(itemSublocation);
		}

		itemInfo.setMarcField(itemField);

		recordInfo.addItem(itemInfo);

		return new ItemInfoWithNotes(itemInfo, suppressionNotes);
	}

	protected void getDueDate(DataField itemField, ItemInfo itemInfo) {
		String dueDateStr = MarcUtil.getItemSubfieldData(settings.getDueDateSubfield(), itemField, indexer.getLogEntry(), logger);
		itemInfo.setDueDate(dueDateStr);
	}

	protected void setShelfLocationCode(DataField itemField, ItemInfo itemInfo, String recordIdentifier) {
		if (settings.getShelvingLocationSubfield() != ' '){
			itemInfo.setShelfLocationCode(MarcUtil.getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField, indexer.getLogEntry(), logger));
		}else {
			itemInfo.setShelfLocationCode(MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger));
		}
	}

	private void scopeItems(RecordInfo recordInfo, AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record){
		for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
			if (itemInfo.isVirtualChildRecord()) {
				itemInfo.setAvailable(false);
				itemInfo.setHoldable(false);
				itemInfo.setDetailedStatus("See individual issues");
				itemInfo.setGroupedStatus("See individual issues");
				loadScopeInfoForVirtualChildItem(groupedWork, itemInfo, record);
			}else if (itemInfo.isVirtualHoldingsRecord()) {
				itemInfo.setAvailable(false);
				itemInfo.setHoldable(false);
				itemInfo.setDetailedStatus("See holdings");
				itemInfo.setGroupedStatus("See holdings");
				loadScopeInfoForVirtualHoldingsItem(groupedWork, itemInfo, record);
			}else if (itemInfo.isOrderItem()){
				itemInfo.setAvailable(false);
				itemInfo.setHoldable(true);
				if (itemInfo.getDetailedStatus() == null || itemInfo.getDetailedStatus().isEmpty()) {
					itemInfo.setDetailedStatus("On Order");
				}
				if (itemInfo.getGroupedStatus() == null || itemInfo.getGroupedStatus().isEmpty()) {
					itemInfo.setGroupedStatus("On Order");
				}
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

	protected void loadScopeInfoForVirtualHoldingsItem(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, org.marc4j.marc.Record record) {
		String itemLocation = itemInfo.getLocationCode();
		String format = itemInfo.getFormat();
		if (format == null){
			format = itemInfo.getRecordInfo().getPrimaryFormat();
		}
		for (Scope curScope : indexer.getScopes()){
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record, "");
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);
				if (curScope.isLocationScope()) {  //Either a location scope or both library and location scope
					boolean itemIsOwned = curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record);
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (curScope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record));
				}
			}
		}
	}

	private void loadScopeInfoForEContentItem(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, org.marc4j.marc.Record record) {
		String itemLocation = itemInfo.getLocationCode();
		String shelfLocation = itemInfo.getShelfLocation();
		String collectionCode = itemInfo.getCollection();
		String originalUrl = itemInfo.geteContentUrl();
		String format = itemInfo.getFormat();
		if (format == null){
			format = itemInfo.getRecordInfo().getPrimaryFormat();
		}
		for (Scope curScope : indexer.getScopes()){
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, shelfLocation, collectionCode, false, false, true, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);
				if (curScope.isLocationScope()) {  //Either a location scope or both library and location scope
					boolean itemIsOwned = curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, shelfLocation, collectionCode, false, false, true, record);
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (curScope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, itemLocation, "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, shelfLocation, collectionCode, false, false, true, record));
				}
				//Check to see if we need to do url rewriting
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	private void loadScopeInfoForVirtualChildItem(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, org.marc4j.marc.Record record) {
		for (Scope curScope : indexer.getScopes()){
			String format = itemInfo.getFormat();
			if (format == null){
				format = itemInfo.getRecordInfo().getPrimaryFormat();
			}
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemInfo.getItemIdentifier(), profileType, "", "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record, "");
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);
				if (curScope.isLocationScope()) {  //Either a location scope or both library and location scope
					boolean itemIsOwned = curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, "", "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record);
					scopingInfo.setLocallyOwned(itemIsOwned);
					if (curScope.isLibraryScope()){
						scopingInfo.setLibraryOwned(itemIsOwned);
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, "", "", null, groupedWork.getTargetAudiences(), groupedWork.getTargetAudiencesAsString(), format, "", "", false, false, false, record));
				}
			}
		}
	}

	private void loadScopeInfoForPrintIlsItem(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, TreeSet<String> audiences, String audiencesAsString, ItemInfo itemInfo, org.marc4j.marc.Record record) {
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
		String shelfLocation = itemInfo.getShelfLocation();
		String collectionCode = itemInfo.getCollection();
		String originalUrl = itemInfo.geteContentUrl();
		String primaryFormat = recordInfo.getPrimaryFormat();
		String itemIdentifier = itemInfo.getItemIdentifier();
		for (Scope curScope : indexer.getScopes()) {
			Scope.InclusionResult result = curScope.isItemPartOfScope(itemIdentifier, profileType, itemLocation, itemSublocation, itemInfo.getITypeCode(), audiences, audiencesAsString, primaryFormat, shelfLocation, collectionCode, isHoldableUnscoped, false, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);

				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(result.isOwned);
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope(itemInfo.getItemIdentifier(), profileType, itemLocation, itemSublocation, itemInfo.getITypeCode(), audiences, audiencesAsString, primaryFormat, shelfLocation, collectionCode, isHoldableUnscoped, false, false, record));
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
		if (format == null){
			return false;
		}else {
			return inLibraryUseOnlyFormats.contains(format.toUpperCase());
		}
	}

	@SuppressWarnings("unused")
	protected void setDetailedStatus(ItemInfo itemInfo, DataField itemField, ItemStatus itemStatus, String identifier) {
		//See if we need to override based on the last check in date
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			itemInfo.setDetailedStatus(overriddenStatus);
		}else {
			itemInfo.setDetailedStatus(itemStatus.getStatus());
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
			return itemInfo.getItemStatus().getGroupedStatus();
		}
	}

	protected String getDisplayStatus(ItemInfo itemInfo, String identifier) {
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			return overriddenStatus;
		}else {
			return translateValue("item_status", itemInfo.getItemStatus().getStatus(), identifier);
		}
	}

	protected double getItemPopularity(DataField itemField) {
		String totalCheckoutsField = MarcUtil.getItemSubfieldData(settings.getTotalCheckoutsSubfield(), itemField, indexer.getLogEntry(), logger);
		int totalCheckouts = 0;
		if (totalCheckoutsField != null){
			try{
				totalCheckouts = Integer.parseInt(totalCheckoutsField);
			}catch (NumberFormatException e){
				logger.warn("Did not get a number for total checkouts. Got " + totalCheckoutsField);
			}

		}
		String ytdCheckoutsField = MarcUtil.getItemSubfieldData(settings.getYearToDateCheckoutsSubfield(), itemField, indexer.getLogEntry(), logger);
		int ytdCheckouts = 0;
		if (ytdCheckoutsField != null){
			ytdCheckouts = Integer.parseInt(ytdCheckoutsField);
		}
		String lastYearCheckoutsField = MarcUtil.getItemSubfieldData(settings.getLastYearCheckoutsSubfield(), itemField, indexer.getLogEntry(), logger);
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

	protected boolean isItemInvalid(ItemStatus itemStatus, String itemLocation) {
		return itemStatus == null && itemLocation == null;
	}

	void loadItemCallNumber(org.marc4j.marc.Record record, DataField itemField, ItemInfo itemInfo) {
		boolean hasCallNumber = false;
		String volume = null;
		if (itemField != null){
			volume = MarcUtil.getItemSubfieldData(settings.getVolume(), itemField, indexer.getLogEntry(), logger);
		}
		if (settings.isUseItemBasedCallNumbers() && itemField != null) {
			String callNumberPreStamp = MarcUtil.getItemSubfieldDataWithoutTrimming(settings.getCallNumberPrestampSubfield(), itemField);
			String callNumberPreStamp2 = MarcUtil.getItemSubfieldDataWithoutTrimming(settings.getCallNumberPrestamp2Subfield(), itemField);
			String callNumber = MarcUtil.getItemSubfieldDataWithoutTrimming(settings.getCallNumberSubfield(), itemField);
			String callNumberCutter = MarcUtil.getItemSubfieldDataWithoutTrimming(settings.getCallNumberCutterSubfield(), itemField);
			String callNumberPostStamp = MarcUtil.getItemSubfieldData(settings.getCallNumberPoststampSubfield(), itemField, indexer.getLogEntry(), logger);

			StringBuilder fullCallNumber = new StringBuilder();
			StringBuilder sortableCallNumber = new StringBuilder();
			if (callNumberPreStamp != null) {
				fullCallNumber.append(callNumberPreStamp);
			}
			if (callNumberPreStamp2 != null) {
				fullCallNumber.append(callNumberPreStamp2);
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
			String[] bibCallNumberFields = settings.getBibCallNumberFields().split(":");
			StringBuilder callNumber = null;
			for (String field : bibCallNumberFields) {
				DataField bibCallNumberField = record.getDataField(field);
				if (bibCallNumberField != null) {
					callNumber = new StringBuilder();
					for (Subfield curSubfield : bibCallNumberField.getSubfields()) {
						callNumber.append(" ").append(curSubfield.getData().trim());
					}
					break;
				}
			}
			if (callNumber != null) {
				if (volume != null && !volume.isEmpty() && !callNumber.toString().endsWith(volume)){
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
			if (volume != null && !volume.isEmpty() && !callNumber.endsWith(volume)){
				if (!callNumber.isEmpty() && callNumber.charAt(callNumber.length() - 1) != ' '){
					callNumber += " ";
				}
				callNumber += volume;
			}
			itemInfo.setCallNumber(callNumber.trim());
			itemInfo.setSortableCallNumber(callNumber.trim());
		}
	}

	private final HashMap<String, Boolean> iTypesThatHaveHoldabilityChecked = new HashMap<>();
	private final HashMap<String, Boolean> locationsThatHaveHoldabilityChecked = new HashMap<>();
	private final HashMap<String, Boolean> statusesThatHaveHoldabilityChecked = new HashMap<>();

	protected boolean isItemHoldableUnscoped(ItemInfo itemInfo){
		String itemItypeCode =  itemInfo.getITypeCode();
		if (nonHoldableITypes != null && itemItypeCode != null && !itemItypeCode.isEmpty()){
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
		if (settings.getNonHoldableLocations() != null && itemLocationCode != null && !itemLocationCode.isEmpty()){
			Boolean cachedValue = locationsThatHaveHoldabilityChecked.get(itemLocationCode);
			if (cachedValue == null){
				cachedValue = !settings.getNonHoldableLocations().matcher(itemLocationCode).matches();
				locationsThatHaveHoldabilityChecked.put(itemLocationCode, cachedValue);
			}
			if (!cachedValue){
				return false;
			}
		}
		String itemStatusCode = itemInfo.getStatusCode();
		if (settings.getNonHoldableStatuses() != null && itemStatusCode != null && !itemStatusCode.isEmpty()){
			Boolean cachedValue = statusesThatHaveHoldabilityChecked.get(itemStatusCode);
			if (cachedValue == null){
				cachedValue = !settings.getNonHoldableStatuses().matcher(itemStatusCode).matches();
				statusesThatHaveHoldabilityChecked.put(itemStatusCode, cachedValue);
			}
			if (!cachedValue){
				return false;
			}
		}
		String format = itemInfo.getPrimaryFormatUppercase();
		if (format != null) {
			return !nonHoldableFormats.contains(format.toUpperCase());
		}else{
			return true;
		}
	}

	String getShelfLocationForItem(DataField itemField, String identifier) {
		String shelfLocation = null;
		if (itemField != null) {
			shelfLocation = MarcUtil.getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		}
		if (shelfLocation == null || shelfLocation.isEmpty() || shelfLocation.equals("none")){
			return "";
		}else {
			return translateValue("shelf_location", shelfLocation, identifier, true);
		}
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location;
		String subLocationCode = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (settings.isIncludeLocationNameInDetailedLocation()) {
			String locationCode = MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger);
			location = translateValue("location", locationCode, identifier, true);
			if (location == null){
				location = "";
			}
		}else{
			location = "";
		}
		if (subLocationCode != null && !subLocationCode.isEmpty()){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && !translatedSubLocation.isEmpty()) {
				if (!location.isEmpty()) {
					location += " - ";
				}
				location += translateValue("sub_location", subLocationCode, identifier, true);
			}
		}
		String shelfLocation = null;
		if (itemField != null) {
			shelfLocation = MarcUtil.getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		}
		if (shelfLocation != null && !shelfLocation.isEmpty() && !shelfLocation.equals("none")){
			if (!location.isEmpty()) {
				location += " - ";
			}
			location += translateValue("shelf_location", shelfLocation, identifier, true);
		}
		return location;
	}

	protected ItemStatus getItemStatus(DataField itemField, String recordIdentifier){
		String statusValue = MarcUtil.getItemSubfieldData(settings.getItemStatusSubfield(), itemField, indexer.getLogEntry(), logger);
		return new ItemStatus(statusValue, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);
	}

	protected abstract boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus);

	protected List<RecordInfo> loadUnsuppressedEContentItems(AbstractGroupedWorkSolr groupedWork, String identifier, org.marc4j.marc.Record record, StringBuilder suppressionNotes, RecordInfo mainRecordInfo, boolean hasParentRecord, boolean hasChildRecords){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		if (settings.getIndex856Links() == 1 || settings.getIndex856Links() == 2) {
			boolean hasEContentItems = !mainRecordInfo.getRelatedItems().isEmpty() && mainRecordInfo.getRelatedItems().stream().anyMatch(ItemInfo::isEContent);
			if (settings.getIndex856Links() == 2 && hasEContentItems) {
				return unsuppressedEcontentRecords;
			}
			List<DataField> recordUrls = MarcUtil.getDataFields(record, 856);
			if (recordUrls.isEmpty()) {
				return unsuppressedEcontentRecords;
			} else {
				int i = 0;
				for (DataField recordUrl : recordUrls) {
					Subfield urlSubfield = recordUrl.getSubfield('u');
					if (urlSubfield == null) {
						continue;
					}
					String url = urlSubfield.getData();
					if (url != null && !url.isEmpty()) {
						if (suppressRecordsWithUrlsMatching != null) {
							if (suppressRecordsWithUrlsMatching.matcher(url).matches()) {
								continue;
							}
						}
						//Include first indicator of 4
						if (recordUrl.getIndicator1() != '4') {
							continue;
						}
						//Include second indicators of 0 or 1
						if (recordUrl.getIndicator2() != '0' && recordUrl.getIndicator2() != '1') {
							continue;
						}
						if (recordUrl.getIndicator2() == '1') {
							if (recordUrl.getSubfield('3') != null) {
								continue;
							}
						}
						//Do not index 856 links with subfield 6 set since those go with library holdings.
						if (recordUrl.getSubfield('6') != null) {
							continue;
						}
						//Get the econtent source
						String urlLower = url.toLowerCase();
						String econtentSource;
						Subfield publicNoteSubfield = recordUrl.getSubfield('z');
						if (publicNoteSubfield != null) {
							String publicNoteText = publicNoteSubfield.getData();
							String publicNoteTextLower = publicNoteText.toLowerCase();
							if (publicNoteTextLower.contains("gale virtual reference library")) {
								econtentSource = "Gale Virtual Reference Library";
							} else if (publicNoteTextLower.contains("gale directory library")) {
								econtentSource = "Gale Directory Library";
							} else if (publicNoteTextLower.contains("national geographic virtual library")) {
								econtentSource = "National Geographic Virtual Library";
							} else if ((publicNoteTextLower.contains("ebscohost") || urlLower.contains("netlibrary") || urlLower.contains("ebsco"))) {
								econtentSource = "EbscoHost";
							} else {
								econtentSource = "Web Content";
							}
						} else {
							econtentSource = "Web Content";
						}

						ItemInfo itemInfo = new ItemInfo();
						itemInfo.setItemIdentifier(identifier + ":856link:" + i);
						itemInfo.setIsEContent(true);
						itemInfo.setLocationCode("Online");
						itemInfo.setCallNumber("Online");
						itemInfo.seteContentSource(econtentSource);
						itemInfo.setShelfLocation("Online");
						Subfield linkTextSubfield = recordUrl.getSubfield('y');
						if (linkTextSubfield != null) {
							itemInfo.setDetailedLocation(linkTextSubfield.getData());
						} else {
							linkTextSubfield = recordUrl.getSubfield('z');
							if (linkTextSubfield != null) {
								itemInfo.setDetailedLocation(linkTextSubfield.getData());
							} else {
								itemInfo.setDetailedLocation(econtentSource);
							}
						}
						itemInfo.setIType("eCollection");
						mainRecordInfo.addItem(itemInfo);
						mainRecordInfo.setHasChildRecord(hasChildRecords);
						mainRecordInfo.setHasParentRecord(hasParentRecord);
						mainRecordInfo.setFormatBoost(6);
						itemInfo.seteContentUrl(url);

						//Set the format based on the material type
						itemInfo.setFormat("Online Content");
						itemInfo.setFormatCategory("Other");

						itemInfo.setDetailedStatus("Available Online");

						if (unsuppressedEcontentRecords.isEmpty()) {
							unsuppressedEcontentRecords.add(mainRecordInfo);
						}

						logger.debug("Found eContent item from " + econtentSource);
						i++;
					}
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	private void loadPopularity(AbstractGroupedWorkSolr groupedWork, String recordIdentifier) {
		//Add popularity based on the number of holds (we have already done popularity for prior checkouts)
		//Active holds indicate that a title is more interesting - so we will count each hold at double value
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
		return isItemSuppressed(curItem, itemIdentifier, suppressionNotes, true);
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes, boolean suppressBlankStatuses) {
		if (settings.getItemStatusAltSubfield() != ' ') {
			Subfield statusAltSubfield = curItem.getSubfield(settings.getItemStatusAltSubfield());
			if (statusAltSubfield != null) {
				String statusAltValue = statusAltSubfield.getData();
				if (statusesToSuppress.contains(statusAltValue)){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - status alt suppressed in Indexing Profile<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (settings.getItemStatusSubfield() != ' ') {
			Subfield statusSubfield = curItem.getSubfield(settings.getItemStatusSubfield());
			if (statusSubfield == null) {
				if (suppressBlankStatuses) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - no status<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			} else {
				String statusValue = statusSubfield.getData();
				if (settings.getStatusesToSuppressPattern() != null && settings.getStatusesToSuppressPattern().matcher(statusValue).matches()) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - matched status suppression pattern<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}else if (statusesToSuppress.contains(statusValue)){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - status suppressed in Indexing Profile<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}

			}
		}
		Subfield locationSubfield = curItem.getSubfield(settings.getLocationSubfield());
		if (locationSubfield == null){
			suppressionNotes.append("Item ").append(itemIdentifier).append(" no location<br/>");
			return new ResultWithNotes(true, suppressionNotes);
		}else{
			if (settings.getLocationsToSuppressPattern() != null && settings.getLocationsToSuppressPattern().matcher(locationSubfield.getData().trim()).matches()){
				suppressionNotes.append("Item ").append(itemIdentifier).append(" location matched suppression pattern<br/>");
				return new ResultWithNotes(true, suppressionNotes);
			}
		}
		if (settings.getCollectionSubfield() != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(settings.getCollectionSubfield());
			if (collectionSubfieldValue == null){
				if (this.suppressRecordsWithNoCollection) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" no collection<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}else{
				if (settings.getCollectionsToSuppressPattern() != null && settings.getCollectionsToSuppressPattern().matcher(collectionSubfieldValue.getData().trim()).matches()){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" collection matched suppression pattern<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (settings.getFormatSubfield() != ' '){
			Subfield formatSubfieldValue = curItem.getSubfield(settings.getFormatSubfield());
			if (formatSubfieldValue != null){
				String formatValue = formatSubfieldValue.getData();
				if (formatsToSuppress.contains(formatValue.toUpperCase())){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" format suppressed in formats table<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (settings.getITypeSubfield() != ' '){
			Subfield iTypeSubfieldValue = curItem.getSubfield(settings.getITypeSubfield());
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
	 * Determine Record Format(s). This can handle loading data from bibs and/or items.
	 *
	 * recordInfo - Information about the record within Aspen.
	 * record - The MARC record to load data from
	 * hasChildRecords - whether the title has child records based on record linking.
	 */
	public void loadPrintFormatInformation(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record, boolean hasChildRecords) {
		//Check to see if we have child records, if so format will be Serials
		if (hasChildRecords) {
			if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Title has child records, loading format from bib.", 2);}
			//A record with children will not generally have items, so we will load from the bib.
			loadPrintFormatFromBib(groupedWork, recordInfo, record);
			if (recordInfo.getFormats().isEmpty()) {
				if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Set Format to Serial because there were no formats for the bib and child records exist.", 2);}
				recordInfo.addFormat("Serial");
				recordInfo.addFormatCategory("Books");
				recordInfo.setFormatBoost(8);
			}
		} else {
			//We should already have formats based on the items
			if (settings.getFormatSource().equals("item") && settings.getFormatSubfield() != ' ' && recordInfo.hasItemFormats()) {
				//Check to see if all items have formats.
				//noinspection IfStatementWithIdenticalBranches
				if (!recordInfo.allItemsHaveFormats()) {
					loadPrintFormatFromBib(groupedWork, recordInfo, record);
					String firstFormat = recordInfo.getFirstFormat();
					String firstFormatCategory = recordInfo.getFirstFormatCategory();
					if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("All items did not have formats, setting format to " + firstFormat + " for all items that did not have a format based on bib.", 2);}
					for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
						if (itemInfo.getFormat() == null || itemInfo.getFormat().isEmpty()) {
							itemInfo.setFormat(firstFormat);
							itemInfo.setFormatCategory(firstFormatCategory);
						}
					}
					return;
				} else {
					largePrintCheck(groupedWork, recordInfo, record);
					return;
				}
			}
			if (recordInfo.hasItemFormats() && !recordInfo.allItemsHaveFormats()){
				//We're doing bib level formats, but we got some item level formats (probably eContent or something)
				loadPrintFormatFromBib(groupedWork, recordInfo, record);
				String firstFormat = recordInfo.getFirstFormat();
				String firstFormatCategory = recordInfo.getFirstFormatCategory();
				if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("All items did not have formats, setting format to " + firstFormat + " for all items that did not have a format based on bib.", 2);}
				for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
					if (itemInfo.getFormat() == null || itemInfo.getFormat().isEmpty()) {
						itemInfo.setFormat(firstFormat);
						itemInfo.setFormatCategory(firstFormatCategory);
					}
				}
				return;
			}

			//If not, we will assign format based on bib level data
			//ILS records do not support specified format categories, etc
			loadPrintFormatFromBib(groupedWork, recordInfo, record);
		}
	}

	void largePrintCheck(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record){
		HashSet<String> uniqueItemFormats = recordInfo.getUniqueItemFormats();
		try {
			if (settings.getCheckRecordForLargePrint()){
				boolean doLargePrintCheck = false;
				if ((uniqueItemFormats.size() == 1) && uniqueItemFormats.iterator().next().equalsIgnoreCase("Book")){
					if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("All items are Book, checking record for Large Print.",2);}

					doLargePrintCheck = true;
				}else if ((uniqueItemFormats.size() == 2) && uniqueItemFormats.contains("Book") && uniqueItemFormats.contains("Large Print")){
					if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("All items are Book or Large Print, checking record for Large Print.",2);}
					doLargePrintCheck = true;
				}else {
					if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record has items that are not Book/Large Print, skipping Large Print check.",2);}
				}
				if (doLargePrintCheck) {
					LinkedHashSet<String> printFormats = formatClassifier.getUntranslatedFormatsFromBib(groupedWork, record, settings);
					if (printFormats.size() == 1 && printFormats.iterator().next().contains("LargePrint")) {
						FormatMapValue formatMapValue = settings.getFormatMapValue("LargePrint", BaseIndexingSettings.FORMAT_TYPE_BIB_LEVEL);
						if (formatMapValue != null) {
							for (ItemInfo item : recordInfo.getRelatedItems()) {
								item.setFormat(null);
								item.setFormatCategory(null);
							}
							recordInfo.addFormat(formatMapValue.getFormat());
							recordInfo.addFormatCategory(formatMapValue.getFormatCategory());
							recordInfo.setFormatBoost(formatMapValue.getFormatBoost());
						}
					}
				}
			}
		} catch (Exception e) {
			logger.error("Error checking record for large print");
		}
	}

	/**
	 * Loads information from the MARC record to populate format, format category, and format boost for the record.
	 *
	 * @param recordInfo Information about the record within Aspen
	 * @param record The MARC record to load data from
	 */
	void loadPrintFormatFromBib(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record) {
		LinkedHashSet<String> printFormats = formatClassifier.getUntranslatedFormatsFromBib(groupedWork, record, settings);
		for (String printFormat : printFormats) {
			FormatMapValue formatMapValue = settings.getFormatMapValue(printFormat, BaseIndexingSettings.FORMAT_TYPE_BIB_LEVEL);
			if (formatMapValue != null) {
				recordInfo.addFormat(formatMapValue.getFormat());
				recordInfo.addFormatCategory(formatMapValue.getFormatCategory());
				recordInfo.setFormatBoost(formatMapValue.getFormatBoost());
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
	protected void loadIlsEContentFormatInformation(org.marc4j.marc.Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {

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
					translationMap.addValue(value, value, indexer.getLogEntry());
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

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier) {
		if (settings.getDetermineAudienceBy() == 0) {
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining target audience by bib record data", 1);}
			super.loadTargetAudiences(groupedWork, record, printItems, identifier, settings.getTreatUnknownAudienceAs());
		}else{
			HashSet<String> targetAudiences = new HashSet<>();
			if (settings.getDetermineAudienceBy() == 1) {
				//Load based on collection
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining audience by collection", 1);}
				for (ItemInfo printItem : printItems){
					String collection = printItem.getCollection();
					if (collection != null) {
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience code includes " + collection.toLowerCase() + " based on collection for item " + printItem.getItemIdentifier(), 1);}
						targetAudiences.add(collection.toLowerCase());
					}
				}
			}else if (settings.getDetermineAudienceBy() == 2) {
				//Load based on shelf location
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining audience by location", 1);}
				for (ItemInfo printItem : printItems){
					String shelfLocationCode = printItem.getShelfLocationCode();
					if (shelfLocationCode != null) {
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience includes code " + shelfLocationCode.toLowerCase() + " based on shelf location for item " + printItem.getItemIdentifier(), 1);}
						targetAudiences.add(shelfLocationCode.toLowerCase());
					}
				}
			}else if (settings.getDetermineAudienceBy() == 3){
				//Load based on a specified subfield
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining audience by subfield " + settings.getAudienceSubfield(), 1);}
				for (ItemInfo printItem : printItems){
					List<String> audienceCodes = printItem.getSubfields(settings.getAudienceSubfield());
					for (String audienceCode : audienceCodes) {
						String audienceCodeLower = audienceCode.toLowerCase();
						if (hasTranslation("audience", audienceCodeLower)) {
							if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience includes code " + audienceCodeLower + " based on subfield for item " + printItem.getItemIdentifier(), 1);}
							targetAudiences.add(audienceCodeLower);
						}
					}
				}
			}
			HashSet<String> translatedAudiences = translateCollection("audience", targetAudiences, identifier, true);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience code(s) " + targetAudiences + " translate(s) to " + translatedAudiences, 1);}

			if (! settings.getTreatUnknownAudienceAs().equals("Unknown") && translatedAudiences.contains("Unknown")) {
				translatedAudiences.remove("Unknown");
				translatedAudiences.add( settings.getTreatUnknownAudienceAs());
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Replacing unknown target audience with " + settings.getTreatUnknownAudienceAs(), 1);}
			}
			if (translatedAudiences.isEmpty()){
				//We didn't get anything from the items (including Unknown), check the bib record
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Could not find target audience based on item - checking the bib record", 1);}
				super.loadTargetAudiences(groupedWork, record, printItems, identifier,  settings.getTreatUnknownAudienceAs());
			}else {
				if (groupedWork != null) {
					groupedWork.addTargetAudiences(translatedAudiences);
					groupedWork.addTargetAudiencesFull(translatedAudiences);
				}
			}
		}
	}

	protected void loadLiteraryForms(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier) {
		if (settings.getDetermineLiteraryFormBy() == 0){
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining literary form by bib record data", 1);}
			super.loadLiteraryForms(groupedWork, record, printItems, identifier);
		}else{
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Determining literary form by item subfield with literary_form map", 1);}
			//Load based on a subfield of the items
			for (ItemInfo printItem : printItems) {
				if (printItem.getMarcField() != null) {
					Subfield subfield = printItem.getMarcField().getSubfield(settings.getLiteraryFormSubfield());
					if (subfield != null) {
						if (subfield.getData() != null) {
							String translatedValue = translateValue("literary_form", subfield.getData(), identifier, true);
							if (translatedValue != null) {
								if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Subfield " + settings.getLiteraryFormSubfield() + " for item " + printItem.getItemIdentifier() + " is " + subfield.getData() + " which maps to " + translatedValue, 2);}
								if (groupedWork != null) {
									groupedWork.addLiteraryForm(translatedValue);
									groupedWork.addLiteraryFormFull(translatedValue);
								}
							}
						}
					}
				}
			}
		}
	}

	public boolean isHideUnknownLiteraryForm() {
		return settings.isHideUnknownLiteraryForm();
	}

	public boolean isHideNotCodedLiteraryForm() {
		return settings.isHideNotCodedLiteraryForm();
	}

}
