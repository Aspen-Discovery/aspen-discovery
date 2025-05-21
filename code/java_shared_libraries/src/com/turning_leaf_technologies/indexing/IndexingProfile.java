package com.turning_leaf_technologies.indexing;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.HashMap;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;

public class IndexingProfile extends BaseIndexingSettings {
	private char itemUrl;
	private char itemUrlDescription;
	private char totalRenewalsSubfield;
	private char iCode2Subfield;
	private char lastYearCheckoutsSubfield;
	private char barcodeSubfield;
	private String itemTag;
	private int itemTagInt;
	private char itemRecordNumberSubfield;
	private String dateAddedFormat;
	private SimpleDateFormat dateAddedFormatter;
	private SimpleDateFormat dateAddedFormatter2;
	private String lastCheckinFormat;
	private SimpleDateFormat lastCheckinFormatter;
	private String dateCreatedFormat;
	private SimpleDateFormat dateCreatedFormatter;
	private String dueDateFormat;
	private char lastCheckinDateSubfield;
	private char locationSubfield;
	private Pattern nonHoldableLocations;
	Pattern locationsToSuppressPattern = null;
	Pattern collectionsToSuppressPattern = null;
	boolean includeLocationNameInDetailedLocation;
	private char itemStatusSubfield;
	private char itemStatusAltSubfield;
	private Pattern statusesToSuppressPattern;
	private Pattern nonHoldableStatuses;
	private boolean treatLibraryUseOnlyGroupedStatusesAsAvailable;
	private char iTypeSubfield;
	private char collectionSubfield;
	private char shelvingLocationSubfield;
	private char yearToDateCheckoutsSubfield;
	private char totalCheckoutsSubfield;
	private boolean useItemBasedCallNumbers;
	private String bibCallNumberFields;
	private char callNumberPrestampSubfield;
	private char callNumberPrestamp2Subfield;
	private char callNumberSubfield;
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volume;
	private char dateCreatedSubfield;
	private char dueDateSubfield;
	private SimpleDateFormat dueDateFormatter;
	private char eContentDescriptor = ' ';
	private boolean doAutomaticEcontentSuppression;
	private char format;
	private long lastUpdateFromMarcExport;
	private long fullMarcExportRecordIdThreshold;
	private long lastVolumeExportTimestamp;
	private boolean checkRecordForLargePrint;
	private char subLocationSubfield;
	private int determineAudienceBy;
	private char audienceSubfield;
	private int determineLiteraryFormBy;
	private char literaryFormSubfield;
	private boolean hideUnknownLiteraryForm;
	private boolean hideNotCodedLiteraryForm;
	private char noteSubfield;
	private char replacementCostSubfield;
	private long lastUpdateOfAuthorities;
	private long lastChangeProcessed;
	private Pattern suppressRecordsWithUrlsMatching;
	private Pattern treatItemsAsEcontent;
	private String fallbackFormatField;
	private boolean processRecordLinking;
	private int evergreenOrgUnitSchema;
	private String orderRecordsStatusesToInclude;
	private String orderRecordStatusToTreatAsUnderConsideration;
	private boolean hideOrderRecordsForBibsWithPhysicalItems;
	private int orderRecordsToSuppressByDate;
	private boolean checkSierraMatTypeForFormat;
	private int index856Links;
	private String treatUnknownAudienceAs;

	//Fields for loading order information
	private String orderTag;
	private char orderLocationSubfield;
	private char singleOrderLocationSubfield;
	private char orderCopiesSubfield;
	private char orderStatusSubfield;

	//Custom Facets
	private String customFacet1SourceField;
	private Pattern customFacet1ValuesToIncludePattern;
	private Pattern customFacet1ValuesToExcludePattern;
	private String customFacet2SourceField;
	private Pattern customFacet2ValuesToIncludePattern;
	private Pattern customFacet2ValuesToExcludePattern;
	private String customFacet3SourceField;
	private Pattern customFacet3ValuesToIncludePattern;
	private Pattern customFacet3ValuesToExcludePattern;

	//Evergreen settings
	private final int numRetriesForBibLookups;
	private final int numMillisecondsToPauseAfterBibLookups;
	private final int numExtractionThreads;
	private String indexingClass;

	private SierraExportFieldMapping sierraExportFieldMappings = null;

	public IndexingProfile(String serverName, BaseIndexingLogEntry logEntry){
		//This is only intended to be used for unit testing
		super(serverName, logEntry);
		numRetriesForBibLookups = 0;
		numMillisecondsToPauseAfterBibLookups = 1000;
		numExtractionThreads = 1;
	}

	public IndexingProfile(String serverName, ResultSet indexingProfileRS, Connection dbConn, BaseIndexingLogEntry logEntry)  throws SQLException {
		super(serverName, logEntry);
		this.setId(indexingProfileRS.getLong("id"));
		this.setName(indexingProfileRS.getString("name"));
		this.setFilenamesToInclude(indexingProfileRS.getString("filenamesToInclude"));
		this.setMarcPath(indexingProfileRS.getString("marcPath"));
		this.setMarcEncoding(indexingProfileRS.getString("marcEncoding"));
		this.setRecordNumberTag(indexingProfileRS.getString("recordNumberTag"));
		this.setRecordNumberSubfield(getCharFromRecordSet(indexingProfileRS, "recordNumberSubfield"));
		this.setRecordNumberPrefix(indexingProfileRS.getString("recordNumberPrefix"));
		this.setItemTag(indexingProfileRS.getString("itemTag"));
		this.setItemRecordNumberSubfield(getCharFromRecordSet(indexingProfileRS,"itemRecordNumber"));
		this.setLastCheckinDateSubfield(getCharFromRecordSet(indexingProfileRS,"lastCheckinDate"));
		this.setDateAddedFormat(indexingProfileRS.getString("dateCreatedFormat"));
		this.setLastCheckinFormat(indexingProfileRS.getString("lastCheckinFormat"));
		this.setLocationSubfield(getCharFromRecordSet(indexingProfileRS,"location"));
		this.includeLocationNameInDetailedLocation = indexingProfileRS.getBoolean("includeLocationNameInDetailedLocation");
		try {
			String pattern = indexingProfileRS.getString("nonHoldableLocations");
			if (pattern != null && !pattern.isEmpty()) {
				nonHoldableLocations = Pattern.compile("^(" + pattern + ")$");
			}
		}catch (Exception e){
			logEntry.incErrors("Could not load non holdable locations", e);
		}
		String locationsToSuppress = indexingProfileRS.getString("locationsToSuppress");
		if (locationsToSuppress != null && !locationsToSuppress.isEmpty()){
			locationsToSuppressPattern = Pattern.compile(locationsToSuppress);
		}

		String collectionsToSuppress = indexingProfileRS.getString("collectionsToSuppress");
		if (collectionsToSuppress != null && !collectionsToSuppress.isEmpty()){
			collectionsToSuppressPattern = Pattern.compile(collectionsToSuppress);
		}
		this.setItemStatusSubfield(getCharFromRecordSet(indexingProfileRS,"status"));
		this.setItemStatusAltSubfield(getCharFromRecordSet(indexingProfileRS,"statusAlt"));
		String statusesToSuppress = indexingProfileRS.getString("statusesToSuppress");
		if (statusesToSuppress != null && !statusesToSuppress.isEmpty()){
			this.statusesToSuppressPattern = Pattern.compile(statusesToSuppress);
		}
		try {
			String pattern = indexingProfileRS.getString("nonHoldableStatuses");
			if (pattern != null && !pattern.isEmpty()) {
				this.nonHoldableStatuses = Pattern.compile("^(" + pattern + ")$", Pattern.CASE_INSENSITIVE);
			}
		}catch (Exception e){
			logEntry.incErrors("Could not load non holdable statuses", e);
		}
		this.setTreatLibraryUseOnlyGroupedStatusesAsAvailable(indexingProfileRS.getBoolean("treatLibraryUseOnlyGroupedStatusesAsAvailable"));
		this.setDueDateSubfield(getCharFromRecordSet(indexingProfileRS,"dueDate"));
		this.setDueDateFormat(indexingProfileRS.getString("dueDateFormat"));
		this.setDateCreatedSubfield(getCharFromRecordSet(indexingProfileRS,"dateCreated"));
		this.setDateCreatedFormat(indexingProfileRS.getString("dateCreatedFormat"));
		this.setTotalCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"totalCheckouts"));
		this.setYearToDateCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"yearToDateCheckouts"));

		this.setName(indexingProfileRS.getString("name"));

		this.setShelvingLocationSubfield(getCharFromRecordSet(indexingProfileRS,"shelvingLocation"));
		this.setITypeSubfield(getCharFromRecordSet(indexingProfileRS,"iType"));
		this.setCollectionSubfield(getCharFromRecordSet(indexingProfileRS,"collection"));
		this.setSubLocationSubfield(getCharFromRecordSet(indexingProfileRS,"subLocation"));

		this.setIndexingClass(indexingProfileRS.getString("indexingClass"));
		this.setFormatSource(indexingProfileRS.getString("formatSource"));
		this.setFallbackFormatField(indexingProfileRS.getString("fallbackFormatField"));

		//Indexing Profiles do not support specified format, specified format category, and specified format boost

		this.setFormatSubfield(getCharFromRecordSet(indexingProfileRS, "format"));
		this.setCheckRecordForLargePrint(indexingProfileRS.getBoolean("checkRecordForLargePrint"));

		this.setDoAutomaticEcontentSuppression(indexingProfileRS.getBoolean("doAutomaticEcontentSuppression"));
		this.setSuppressRecordsWithUrlsMatching(indexingProfileRS.getString("suppressRecordsWithUrlsMatching"));
		this.setTreatItemsAsEcontent(indexingProfileRS.getString("treatItemsAsEcontent"));
		this.setEContentDescriptor(getCharFromRecordSet(indexingProfileRS, "eContentDescriptor"));

		this.setLastYearCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS, "lastYearCheckouts"));
		this.setBarcodeSubfield(getCharFromRecordSet(indexingProfileRS, "barcode"));
		if (this.getItemRecordNumberSubfield() == ' '){
			this.setItemRecordNumberSubfield(this.getBarcodeSubfield());
		}
		this.setTotalRenewalsSubfield(getCharFromRecordSet(indexingProfileRS, "totalRenewals"));
		this.setICode2Subfield(getCharFromRecordSet(indexingProfileRS, "iCode2"));

		this.useItemBasedCallNumbers = indexingProfileRS.getBoolean("useItemBasedCallNumbers");
		this.bibCallNumberFields = (indexingProfileRS.getString("bibCallNumberFields"));
		this.setCallNumberPrestampSubfield(getCharFromRecordSet(indexingProfileRS,"callNumberPrestamp"));
		callNumberPrestamp2Subfield = getCharFromRecordSet(indexingProfileRS, "callNumberPrestamp2");
		this.setCallNumberSubfield(getCharFromRecordSet(indexingProfileRS,"callNumber"));
		this.setCallNumberCutterSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberCutter"));
		this.setCallNumberPoststampSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberPoststamp"));
		this.setVolume(getCharFromRecordSet(indexingProfileRS, "volume"));
		this.setItemUrl(getCharFromRecordSet(indexingProfileRS, "itemUrl"));
		this.setItemUrlDescription(getCharFromRecordSet(indexingProfileRS, "itemUrlDescription"));

		this.setDetermineAudienceBy(indexingProfileRS.getInt("determineAudienceBy"));
		this.setAudienceSubfield(getCharFromRecordSet(indexingProfileRS, "audienceSubfield"));

		this.determineLiteraryFormBy = indexingProfileRS.getInt("determineLiteraryFormBy");
		this.literaryFormSubfield = getCharFromRecordSet(indexingProfileRS, "literaryFormSubfield");
		this.hideUnknownLiteraryForm = indexingProfileRS.getBoolean("hideUnknownLiteraryForm");
		this.hideNotCodedLiteraryForm = indexingProfileRS.getBoolean("hideNotCodedLiteraryForm");

		this.includePersonalAndCorporateNamesInTopics = indexingProfileRS.getBoolean("includePersonalAndCorporateNamesInTopics");

		this.setNoteSubfield(getCharFromRecordSet(indexingProfileRS, "noteSubfield"));
		this.setReplacementCostSubfield(getCharFromRecordSet(indexingProfileRS, "replacementCostSubfield"));

		this.setLastUpdateOfChangedRecords(indexingProfileRS.getLong("lastUpdateOfChangedRecords"));
		this.setLastUpdateOfAllRecords(indexingProfileRS.getLong("lastUpdateOfAllRecords"));
		this.setLastUpdateFromMarcExport(indexingProfileRS.getLong("lastUpdateFromMarcExport"));
		this.setFullMarcExportRecordIdThreshold(indexingProfileRS.getLong("fullMarcExportRecordIdThreshold"));
		this.setLastVolumeExportTimestamp(indexingProfileRS.getLong("lastVolumeExportTimestamp"));
		this.setLastUpdateOfAuthorities(indexingProfileRS.getLong("lastUpdateOfAuthorities"));
		this.setLastChangeProcessed(indexingProfileRS.getLong("lastChangeProcessed"));

		this.setRunFullUpdate(indexingProfileRS.getBoolean("runFullUpdate"));
		this.setRegroupAllRecords(indexingProfileRS.getBoolean("regroupAllRecords"));

		this.treatUnknownLanguageAs = indexingProfileRS.getString("treatUnknownLanguageAs");
		treatUndeterminedLanguageAs = indexingProfileRS.getString("treatUndeterminedLanguageAs");
		this.customMarcFieldsToIndexAsKeyword = indexingProfileRS.getString("customMarcFieldsToIndexAsKeyword");
		this.processRecordLinking = indexingProfileRS.getBoolean("processRecordLinking");

		this.evergreenOrgUnitSchema = indexingProfileRS.getInt("evergreenOrgUnitSchema");

		orderTag = indexingProfileRS.getString("orderTag");
		orderLocationSubfield = getCharFromRecordSet(indexingProfileRS, "orderLocation");
		singleOrderLocationSubfield = getCharFromRecordSet(indexingProfileRS, "orderLocationSingle");
		orderCopiesSubfield = getCharFromRecordSet(indexingProfileRS, "orderCopies");
		orderStatusSubfield = getCharFromRecordSet(indexingProfileRS, "orderStatus");

		this.orderRecordsStatusesToInclude = indexingProfileRS.getString("orderRecordsStatusesToInclude");
		this.orderRecordStatusToTreatAsUnderConsideration = indexingProfileRS.getString("orderRecordStatusToTreatAsUnderConsideration");
		this.hideOrderRecordsForBibsWithPhysicalItems = indexingProfileRS.getBoolean("hideOrderRecordsForBibsWithPhysicalItems");
		this.orderRecordsToSuppressByDate = indexingProfileRS.getInt("orderRecordsToSuppressByDate");

		this.checkSierraMatTypeForFormat = indexingProfileRS.getBoolean("checkSierraMatTypeForFormat");

		index856Links = indexingProfileRS.getInt("index856Links");
		treatUnknownAudienceAs = indexingProfileRS.getString("treatUnknownAudienceAs");

		//Custom Facet 1
		this.customFacet1SourceField = indexingProfileRS.getString("customFacet1SourceField");
		String customFacet1ValuesToInclude = indexingProfileRS.getString("customFacet1ValuesToInclude");
		if (customFacet1ValuesToInclude != null && !customFacet1ValuesToInclude.isEmpty() && !customFacet1ValuesToInclude.equals(".*")) {
			try {
				customFacet1ValuesToIncludePattern = Pattern.compile(customFacet1ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet1ValuesToIncludePattern", e);
			}
		}
		String customFacet1ValuesToExclude = indexingProfileRS.getString("customFacet1ValuesToExclude");
		if (customFacet1ValuesToExclude != null && !customFacet1ValuesToExclude.isEmpty()) {
			try {
				customFacet1ValuesToExcludePattern = Pattern.compile(customFacet1ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet1ValuesToExcludePattern", e);
			}
		}

		//Custom Facet 2
		this.customFacet2SourceField = indexingProfileRS.getString("customFacet2SourceField");
		String customFacet2ValuesToInclude = indexingProfileRS.getString("customFacet2ValuesToInclude");
		if (customFacet2ValuesToInclude != null && !customFacet2ValuesToInclude.isEmpty() && !customFacet2ValuesToInclude.equals(".*")) {
			try {
				customFacet2ValuesToIncludePattern = Pattern.compile(customFacet2ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet2ValuesToIncludePattern", e);
			}
		}
		String customFacet2ValuesToExclude = indexingProfileRS.getString("customFacet2ValuesToExclude");
		if (customFacet2ValuesToExclude != null && !customFacet2ValuesToExclude.isEmpty()) {
			try {
				customFacet2ValuesToExcludePattern = Pattern.compile(customFacet2ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet2ValuesToExcludePattern", e);
			}
		}

		//Custom Facet 3
		this.customFacet3SourceField = indexingProfileRS.getString("customFacet3SourceField");
		String customFacet3ValuesToInclude = indexingProfileRS.getString("customFacet3ValuesToInclude");
		if (customFacet3ValuesToInclude != null && !customFacet3ValuesToInclude.isEmpty() && !customFacet3ValuesToInclude.equals(".*")) {
			try {
				customFacet3ValuesToIncludePattern = Pattern.compile(customFacet3ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet3ValuesToIncludePattern", e);
			}
		}
		String customFacet3ValuesToExclude = indexingProfileRS.getString("customFacet3ValuesToExclude");
		if (customFacet3ValuesToExclude != null && !customFacet3ValuesToExclude.isEmpty()) {
			try {
				customFacet3ValuesToExcludePattern = Pattern.compile(customFacet3ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet3ValuesToExcludePattern", e);
			}
		}

		this.numRetriesForBibLookups = indexingProfileRS.getInt("numRetriesForBibLookups");
		this.numMillisecondsToPauseAfterBibLookups = indexingProfileRS.getInt("numMillisecondsToPauseAfterBibLookups");
		this.numExtractionThreads = indexingProfileRS.getInt("numExtractionThreads");

		if (this.indexingClass.equals("III")) {
			try {
				sierraExportFieldMappings = SierraExportFieldMapping.loadSierraFieldMappings(dbConn, indexingProfileRS.getLong("id"), logEntry);
			} catch (Exception e) {
				logEntry.incErrors("Unable to load Sierra Export Mappings", e);
			}
		}

		loadTranslationMaps(dbConn, logEntry);
	}

	private void loadTranslationMaps(Connection dbConn, BaseIndexingLogEntry logEntry) {
		try {
			PreparedStatement loadMapsStmt = dbConn.prepareStatement("SELECT * FROM translation_maps where indexingProfileId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement loadMapValuesStmt = dbConn.prepareStatement("SELECT * FROM translation_map_values where translationMapId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadMapsStmt.setLong(1, id);
			ResultSet translationMapsRS = loadMapsStmt.executeQuery();
			while (translationMapsRS.next()){
				HashMap<String, String> translationMap = new HashMap<>();
				String mapName = translationMapsRS.getString("name");
				long translationMapId = translationMapsRS.getLong("id");

				loadMapValuesStmt.setLong(1, translationMapId);
				ResultSet mapValuesRS = loadMapValuesStmt.executeQuery();
				while (mapValuesRS.next()){
					String value = mapValuesRS.getString("value");
					String translation = mapValuesRS.getString("translation");

					translationMap.put(value.toLowerCase(), translation);
				}
				mapValuesRS.close();
				if (translationMaps.containsKey(mapName)) {
					translationMaps.get(mapName).putAll(translationMap);
				}else{
					translationMaps.put(mapName, translationMap);
				}
			}
			translationMapsRS.close();

			PreparedStatement getFormatMapStmt = dbConn.prepareStatement("SELECT * from format_map_values WHERE indexingProfileId = ?");
			getFormatMapStmt.setLong(1, id);

			ResultSet formatMapRS = getFormatMapStmt.executeQuery();

			while (formatMapRS.next()){
				String value = formatMapRS.getString("value");
				FormatMapValue formatMapValue;
				if (formatMapping.containsKey(value.toLowerCase())) {
					formatMapValue = formatMapping.get(value.toLowerCase());
				}else{
					formatMapValue = new FormatMapValue();
					formatMapping.put(value.toLowerCase(), formatMapValue);
				}
				formatMapValue.setValue(value);
				formatMapValue.setFormat(formatMapRS.getString("format"));
				formatMapValue.setFormatCategory(formatMapRS.getString("formatCategory"));
				formatMapValue.setFormatBoost(formatMapRS.getInt("formatBoost"));
				formatMapValue.setAppliesToMatType(formatMapRS.getBoolean("appliesToMatType"));
				formatMapValue.setAppliesToBibLevel(formatMapRS.getBoolean("appliesToBibLevel"));
				formatMapValue.setAppliesToItemShelvingLocation(formatMapRS.getBoolean("appliesToItemShelvingLocation"));
				formatMapValue.setAppliesToItemSublocation(formatMapRS.getBoolean("appliesToItemSublocation"));
				formatMapValue.setAppliesToItemCollection(formatMapRS.getBoolean("appliesToItemCollection"));
				formatMapValue.setAppliesToItemType(formatMapRS.getBoolean("appliesToItemType"));
				formatMapValue.setAppliesToItemFormat(formatMapRS.getBoolean("appliesToItemFormat"));
				formatMapValue.setAppliesToFallbackFormat(formatMapRS.getBoolean("appliesToFallbackFormat"));
				formatMapping.put(value.toLowerCase(), formatMapValue);
			}
			formatMapRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error loading translation maps", e);
		}

	}

	public void addTranslationMapValue(String mapName, String value, String translation) {
		if (!this.translationMaps.containsKey(mapName)) {
			this.translationMaps.put(mapName, new HashMap<>());
		}
		this.translationMaps.get(mapName).put(value.toLowerCase(), translation);
	}

	private void setFilenamesToInclude(String filenamesToInclude) {
		this.filenamesToInclude = filenamesToInclude;
	}

	public void setFormatSource(String formatSource) {
		this.formatSource = formatSource;
	}

	public char getFormatSubfield() {
		return format;
	}

	public void setFormatSubfield(char format) {
		this.format = format;
	}

	public static IndexingProfile loadIndexingProfile(String serverName, Connection dbConn, String profileToLoad, Logger logger, BaseIndexingLogEntry logEntry) {
		//Get the Indexing Profile from the database
		IndexingProfile indexingProfile = null;
		try {
			PreparedStatement getIndexingProfileStmt = dbConn.prepareStatement("SELECT * FROM indexing_profiles where name ='" + profileToLoad + "'");
			ResultSet indexingProfileRS = getIndexingProfileStmt.executeQuery();
			if (indexingProfileRS.next()) {
				indexingProfile = new IndexingProfile(serverName, indexingProfileRS, dbConn, logEntry);

			} else {
				logger.error("Unable to find " + profileToLoad + " indexing profile, please create a profile with the name ils.");
			}

		}catch (Exception e){
			logger.error("Error reading index profile " + profileToLoad, e);
		}
		return indexingProfile;
	}

	private void setAudienceSubfield(char audienceSubfield) {
		this.audienceSubfield = audienceSubfield;
	}

	public char getAudienceSubfield(){
		return this.audienceSubfield;
	}

	public String getItemTag() {
		return itemTag;
	}

	public int getItemTagInt() {
		return itemTagInt;
	}

	public void setItemTag(String itemTag) {
		this.itemTag = itemTag;
		this.itemTagInt = Integer.parseInt(itemTag);
	}

	public void setId(Long id) {
		this.id = id;
	}

	public void setName(String name) {
		this.name = name;
	}

	public void setRecordNumberTag(String recordNumberTag) {
		this.recordNumberTag = recordNumberTag;
		this.recordNumberTagInt = Integer.parseInt(recordNumberTag);
	}

	public char getItemRecordNumberSubfield() {
		return itemRecordNumberSubfield;
	}

	public void setItemRecordNumberSubfield(char itemRecordNumberSubfield) {
		this.itemRecordNumberSubfield = itemRecordNumberSubfield;
	}

	public void setDateAddedFormat(String dateAddedFormat) {
		this.dateAddedFormat = dateAddedFormat;
		this.dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
		this.dateAddedFormatter2 = new SimpleDateFormat("yyMMdd");
	}

	public String getDateAddedFormat(){
		return dateAddedFormat;
	}
	public SimpleDateFormat getDateAddedFormatter() {
		return dateAddedFormatter;
	}

	public SimpleDateFormat getDateAddedFormatter2() {
		return dateAddedFormatter2;
	}

	public String getLastCheckinFormat() {
		return lastCheckinFormat;
	}

	private void setLastCheckinFormat(String lastCheckinFormat) {
		this.lastCheckinFormat = lastCheckinFormat;
		this.lastCheckinFormatter = new SimpleDateFormat(lastCheckinFormat);
	}

	public String getDateCreatedFormat() {
		return dateCreatedFormat;
	}

	private void setDateCreatedFormat(String dateCreatedFormat) {
		this.dateCreatedFormat = dateCreatedFormat;
		dateCreatedFormatter = new SimpleDateFormat(dateCreatedFormat);
	}

	public String getDueDateFormat() {
		return dueDateFormat;
	}

	private void setDueDateFormat(String dueDateFormat) {
		this.dueDateFormat = dueDateFormat;
		this.dueDateFormatter = new SimpleDateFormat(dueDateFormat);
	}

	public char getLastCheckinDateSubfield() {
		return lastCheckinDateSubfield;
	}

	private void setLastCheckinDateSubfield(char lastCheckinDateSubfield) {
		this.lastCheckinDateSubfield = lastCheckinDateSubfield;
	}

	public char getLocationSubfield() {
		return locationSubfield;
	}

	public void setLocationSubfield(char locationSubfield) {
		this.locationSubfield = locationSubfield;
	}

	public Pattern getNonHoldableLocations() {
		return nonHoldableLocations;
	}

	public Pattern getLocationsToSuppressPattern() {
		return locationsToSuppressPattern;
	}

	public Pattern getCollectionsToSuppressPattern() {
		return collectionsToSuppressPattern;
	}

	public boolean isIncludeLocationNameInDetailedLocation() {
		return  includeLocationNameInDetailedLocation;
	}

	public char getItemStatusSubfield() {
		return itemStatusSubfield;
	}

	private void setItemStatusSubfield(char itemStatusSubfield) {
		this.itemStatusSubfield = itemStatusSubfield;
	}

	public char getItemStatusAltSubfield() {
		return itemStatusAltSubfield;
	}

	private void setItemStatusAltSubfield(char itemStatusAltSubfield) {
		this.itemStatusAltSubfield = itemStatusAltSubfield;
	}

	public Pattern getStatusesToSuppressPattern() {
		return statusesToSuppressPattern;
	}

	public Pattern getNonHoldableStatuses() {
		return nonHoldableStatuses;
	}

	public char getITypeSubfield() {
		return iTypeSubfield;
	}

	public void setITypeSubfield(char iTypeSubfield) {
		this.iTypeSubfield = iTypeSubfield;
	}

	public char getShelvingLocationSubfield() {
		return shelvingLocationSubfield;
	}

	public void setShelvingLocationSubfield(char shelvingLocationSubfield) {
		this.shelvingLocationSubfield = shelvingLocationSubfield;
	}

	public char getYearToDateCheckoutsSubfield() {
		return yearToDateCheckoutsSubfield;
	}

	private void setYearToDateCheckoutsSubfield(char yearToDateCheckoutsSubfield) {
		this.yearToDateCheckoutsSubfield = yearToDateCheckoutsSubfield;
	}

	public char getTotalCheckoutsSubfield() {
		return totalCheckoutsSubfield;
	}

	private void setTotalCheckoutsSubfield(char totalCheckoutsSubfield) {
		this.totalCheckoutsSubfield = totalCheckoutsSubfield;
	}

	public char getCallNumberPrestampSubfield() {
		return callNumberPrestampSubfield;
	}

	private void setCallNumberPrestampSubfield(char callNumberPrestampSubfield) {
		this.callNumberPrestampSubfield = callNumberPrestampSubfield;
	}

	public char getCallNumberSubfield() {
		return callNumberSubfield;
	}

	private void setCallNumberSubfield(char callNumberSubfield) {
		this.callNumberSubfield = callNumberSubfield;
	}

	public char getDateCreatedSubfield() {
		return dateCreatedSubfield;
	}

	private void setDateCreatedSubfield(char dateCreatedSubfield) {
		this.dateCreatedSubfield = dateCreatedSubfield;
	}

	public char getDueDateSubfield() {
		return dueDateSubfield;
	}

	private void setDueDateSubfield(char dueDateSubfield) {
		this.dueDateSubfield = dueDateSubfield;
	}

	public void setMarcPath(String marcPath) {
		this.marcPath = marcPath;
	}

	public void setMarcEncoding(String marcEncoding) {
		this.marcEncoding = marcEncoding;
	}

	private void setRecordNumberPrefix(String recordNumberPrefix) {
		this.recordNumberPrefix = recordNumberPrefix;
	}

	public boolean isDoAutomaticEcontentSuppression() {
		return doAutomaticEcontentSuppression;
	}

	private void setDoAutomaticEcontentSuppression(boolean doAutomaticEcontentSuppression) {
		this.doAutomaticEcontentSuppression = doAutomaticEcontentSuppression;
	}

	public char getEContentDescriptor() {
		return eContentDescriptor;
	}

	private void setEContentDescriptor(char eContentDescriptor) {
		this.eContentDescriptor = eContentDescriptor;
	}

	public boolean useEContentSubfield() {
		return this.eContentDescriptor != ' ';
	}

	public SimpleDateFormat getDueDateFormatter() {
		return dueDateFormatter;
	}

	public SimpleDateFormat getDateCreatedFormatter() {
		return dateCreatedFormatter;
	}

	public SimpleDateFormat getLastCheckinFormatter() {
		return lastCheckinFormatter;
	}

	public char getLastYearCheckoutsSubfield() {
		return lastYearCheckoutsSubfield;
	}

	private void setLastYearCheckoutsSubfield(char lastYearCheckoutsSubfield) {
		this.lastYearCheckoutsSubfield = lastYearCheckoutsSubfield;
	}

	public char getBarcodeSubfield() {
		return barcodeSubfield;
	}

	public void setBarcodeSubfield(char barcodeSubfield) {
		this.barcodeSubfield = barcodeSubfield;
	}

	public char getTotalRenewalsSubfield() {
		return totalRenewalsSubfield;
	}

	private void setTotalRenewalsSubfield(char totalRenewalsSubfield) {
		this.totalRenewalsSubfield = totalRenewalsSubfield;
	}

	public char getICode2Subfield() {
		return iCode2Subfield;
	}

	private void setICode2Subfield(char iCode2Subfield) {
		this.iCode2Subfield = iCode2Subfield;
	}

	public char getNoteSubfield() {
		return noteSubfield;
	}

	private void setNoteSubfield(char noteSubfield){
		this.noteSubfield = noteSubfield;
	}

	public char getReplacementCostSubfield() {
		return replacementCostSubfield;
	}

	public void setReplacementCostSubfield(char replacementCostSubfield) {
		this.replacementCostSubfield = replacementCostSubfield;
	}

	public char getCallNumberCutterSubfield() {
		return callNumberCutterSubfield;
	}

	private void setCallNumberCutterSubfield(char callNumberCutterSubfield) {
		this.callNumberCutterSubfield = callNumberCutterSubfield;
	}

	public char getCallNumberPoststampSubfield() {
		return callNumberPoststampSubfield;
	}

	private void setCallNumberPoststampSubfield(char callNumberPoststampSubfield) {
		this.callNumberPoststampSubfield = callNumberPoststampSubfield;
	}

	public char getVolume() {
		return volume;
	}

	private void setVolume(char volume) {
		this.volume = volume;
	}

	public char getItemUrl() {
		return itemUrl;
	}

	private void setItemUrl(char itemUrl) {
		this.itemUrl = itemUrl;
	}

	public char getItemUrlDescription() {
		return itemUrlDescription;
	}

	private void setItemUrlDescription(char itemUrlDescription) {
		this.itemUrlDescription = itemUrlDescription;
	}

	public void setRecordNumberSubfield(char recordNumberSubfield) {
		this.recordNumberSubfield = recordNumberSubfield;
	}

	private void setLastUpdateOfChangedRecords(long lastUpdateOfChangedRecords) {
		this.lastUpdateOfChangedRecords = lastUpdateOfChangedRecords;
	}

	private void setLastUpdateOfAllRecords(long lastUpdateOfAllRecords) {
		this.lastUpdateOfAllRecords = lastUpdateOfAllRecords;
	}

	private void setRunFullUpdate(boolean runFullUpdate) {
		this.runFullUpdate = runFullUpdate;
	}

	private void setRegroupAllRecords(boolean regroupAllRecords) {
		this.regroupAllRecords = regroupAllRecords;
	}

	private void setLastUpdateFromMarcExport(long lastUpdateFromMarcExport) {
		this.lastUpdateFromMarcExport = lastUpdateFromMarcExport;
	}

	public long getLastUpdateFromMarcExport() {
		return lastUpdateFromMarcExport;
	}

	private void setCheckRecordForLargePrint(boolean checkRecordForLargePrint) {
		this.checkRecordForLargePrint = checkRecordForLargePrint;
	}

	public boolean getCheckRecordForLargePrint() {
		return checkRecordForLargePrint;
	}

	public void setCollectionSubfield(char collectionSubfield) {
		this.collectionSubfield = collectionSubfield;
	}

	public char getCollectionSubfield() {
		return collectionSubfield;
	}

	public void setSubLocationSubfield(char sublocationSubfield) {
		this.subLocationSubfield = sublocationSubfield;
	}

	public char getSubLocationSubfield() {
		return subLocationSubfield;
	}

	public int getDetermineAudienceBy() {
		return determineAudienceBy;
	}

	private void setDetermineAudienceBy(int determineAudienceBy) {
		this.determineAudienceBy = determineAudienceBy;
	}

	public long getLastVolumeExportTimestamp() {
		return lastVolumeExportTimestamp;
	}

	public void setLastVolumeExportTimestamp(long lastVolumeExportTimestamp) {
		this.lastVolumeExportTimestamp = lastVolumeExportTimestamp;
	}

	public long getLastUpdateOfAuthorities() {
		return lastUpdateOfAuthorities;
	}

	private void setLastUpdateOfAuthorities(long lastUpdateOfAuthorities) {
		this.lastUpdateOfAuthorities = lastUpdateOfAuthorities;
	}

	public void clearRegroupAllRecords(Connection dbConn, BaseIndexingLogEntry logEntry) {
		try {
			PreparedStatement clearRegroupAllRecordsStmt = dbConn.prepareStatement("UPDATE indexing_profiles set regroupAllRecords = 0 where id =?");
			clearRegroupAllRecordsStmt.setLong(1, id);
			clearRegroupAllRecordsStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not clear regroup all records", e);
		}
	}
	public long getFullMarcExportRecordIdThreshold() {
		return fullMarcExportRecordIdThreshold;
	}

	public void setFullMarcExportRecordIdThreshold(long fullMarcExportRecordIdThreshold) {
		this.fullMarcExportRecordIdThreshold = fullMarcExportRecordIdThreshold;
	}

	public long getLastChangeProcessed() {
		return lastChangeProcessed;
	}

	public void setLastChangeProcessed(long lastChangeProcessed) {
		this.lastChangeProcessed = lastChangeProcessed;
	}
	public void updateLastChangeProcessed(Connection dbConn, BaseIndexingLogEntry logEntry) {
		try {
			PreparedStatement updateLastChangeProcessedId = dbConn.prepareStatement("UPDATE indexing_profiles set lastChangeProcessed = ? where id =?");
			updateLastChangeProcessedId.setLong(1, lastChangeProcessed);
			updateLastChangeProcessedId.setLong(2, id);
			updateLastChangeProcessedId.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not set last record processed", e);
		}
	}

	public void setSuppressRecordsWithUrlsMatching(String suppressRecordsWithUrlsMatching) {
		if (suppressRecordsWithUrlsMatching.isEmpty()){
			this.suppressRecordsWithUrlsMatching = null;
		}else {
			this.suppressRecordsWithUrlsMatching = Pattern.compile(suppressRecordsWithUrlsMatching, Pattern.CASE_INSENSITIVE);
		}
	}

	public void setTreatItemsAsEcontent(String treatItemsAsEcontent) {
		if (treatItemsAsEcontent.isEmpty()){
			this.treatItemsAsEcontent = null;
		} else {
			this.treatItemsAsEcontent = Pattern.compile(treatItemsAsEcontent, Pattern.CASE_INSENSITIVE);
		}
	}

	public Pattern getSuppressRecordsWithUrlsMatching() {
		return suppressRecordsWithUrlsMatching;
	}

	public Pattern getTreatItemsAsEcontent() {
		return treatItemsAsEcontent;
	}

	public void setFallbackFormatField(String fallbackFormatField) {
		this.fallbackFormatField = fallbackFormatField;
	}

	public String getFallbackFormatField() {
		return fallbackFormatField;
	}

	public void setTreatLibraryUseOnlyGroupedStatusesAsAvailable(boolean treatLibraryUseOnlyGroupedStatusesAsAvailable) {
		this.treatLibraryUseOnlyGroupedStatusesAsAvailable = treatLibraryUseOnlyGroupedStatusesAsAvailable;
	}

	public boolean getTreatLibraryUseOnlyGroupedStatusesAsAvailable() {
		return treatLibraryUseOnlyGroupedStatusesAsAvailable;
	}

	public boolean isProcessRecordLinking() {
		return processRecordLinking;
	}

	public int getEvergreenOrgUnitSchema() {
		return evergreenOrgUnitSchema;
	}

	public String getOrderRecordsStatusesToInclude() {
		return orderRecordsStatusesToInclude;
	}

	public boolean isHideOrderRecordsForBibsWithPhysicalItems() {
		return hideOrderRecordsForBibsWithPhysicalItems;
	}

	public int getOrderRecordsToSuppressByDate() {
		return orderRecordsToSuppressByDate;
	}

	public String getCustomFacet1SourceField() {
		return customFacet1SourceField;
	}

	public Pattern getCustomFacet1ValuesToIncludePattern() {
		return customFacet1ValuesToIncludePattern;
	}

	public Pattern getCustomFacet1ValuesToExcludePattern() {
		return customFacet1ValuesToExcludePattern;
	}

	public String getCustomFacet2SourceField() {
		return customFacet2SourceField;
	}

	public Pattern getCustomFacet2ValuesToIncludePattern() {
		return customFacet2ValuesToIncludePattern;
	}

	public Pattern getCustomFacet2ValuesToExcludePattern() {
		return customFacet2ValuesToExcludePattern;
	}

	public String getCustomFacet3SourceField() {
		return customFacet3SourceField;
	}

	public Pattern getCustomFacet3ValuesToIncludePattern() {
		return customFacet3ValuesToIncludePattern;
	}

	public Pattern getCustomFacet3ValuesToExcludePattern() {
		return customFacet3ValuesToExcludePattern;
	}

	public int getNumRetriesForBibLookups() {
		return numRetriesForBibLookups;
	}

	public int getNumMillisecondsToPauseAfterBibLookups() {
		return numMillisecondsToPauseAfterBibLookups;
	}

	public int getNumExtractionThreads() {
		return numExtractionThreads;
	}

	public void setIndexingClass(String indexingClass) {
		this.indexingClass = indexingClass;
	}

	public String getIndexingClass() {
		return indexingClass;
	}

	public boolean isUseItemBasedCallNumbers() {
		return useItemBasedCallNumbers;
	}

	public void setUseItemBasedCallNumbers(boolean useItemBasedCallNumbers) {
		this.useItemBasedCallNumbers = useItemBasedCallNumbers;
	}

	public String getBibCallNumberFields() {
		return this.bibCallNumberFields;
	}

	public void setBibCallNumberFields(String bibCallNumberFields) {
		this.bibCallNumberFields = bibCallNumberFields;
	}

	public char getCallNumberPrestamp2Subfield() {
		return callNumberPrestamp2Subfield;
	}

	public void setCallNumberPrestamp2Subfield(char callNumberPrestamp2Subfield) {
		this.callNumberPrestamp2Subfield = callNumberPrestamp2Subfield;
	}

	public int getDetermineLiteraryFormBy() {
		return determineLiteraryFormBy;
	}

	public void setDetermineLiteraryFormBy(int determineLiteraryFormBy) {
		this.determineLiteraryFormBy = determineLiteraryFormBy;
	}

	public char getLiteraryFormSubfield() {
		return literaryFormSubfield;
	}

	public void setLiteraryFormSubfield(char literaryFormSubfield) {
		this.literaryFormSubfield = literaryFormSubfield;
	}

	public boolean isHideUnknownLiteraryForm() {
		return hideUnknownLiteraryForm;
	}

	public boolean isHideNotCodedLiteraryForm() {
		return hideNotCodedLiteraryForm;
	}

	public void setHideNotCodedLiteraryForm(boolean hideNotCodedLiteraryForm) {
		this.hideNotCodedLiteraryForm = hideNotCodedLiteraryForm;
	}

	public boolean isCheckSierraMatTypeForFormat() {
		return checkSierraMatTypeForFormat;
	}

	public void setCheckSierraMatTypeForFormat(boolean checkSierraMatTypeForFormat) {
		this.checkSierraMatTypeForFormat = checkSierraMatTypeForFormat;
	}

	public String getOrderTag() {
		return orderTag;
	}

	public void setOrderTag(String orderTag) {
		this.orderTag = orderTag;
	}

	public char getOrderLocationSubfield() {
		return orderLocationSubfield;
	}

	public void setOrderLocationSubfield(char orderLocationSubfield) {
		this.orderLocationSubfield = orderLocationSubfield;
	}

	public char getSingleOrderLocationSubfield() {
		return singleOrderLocationSubfield;
	}

	public char getOrderCopiesSubfield() {
		return orderCopiesSubfield;
	}

	public void setOrderCopiesSubfield(char orderCopiesSubfield) {
		this.orderCopiesSubfield = orderCopiesSubfield;
	}

	public char getOrderStatusSubfield() {
		return orderStatusSubfield;
	}

	public void setOrderStatusSubfield(char orderStatusSubfield) {
		this.orderStatusSubfield = orderStatusSubfield;
	}

	public int getIndex856Links() {
		return index856Links;
	}

	public void setIndex856Links(int index856Links) {
		this.index856Links = index856Links;
	}

	public String getTreatUnknownAudienceAs() {
		return treatUnknownAudienceAs;
	}

	public void setTreatUnknownAudienceAs(String treatUnknownAudienceAs) {
		this.treatUnknownAudienceAs = treatUnknownAudienceAs;
	}

	public SierraExportFieldMapping getSierraExportFieldMappings() {
		return sierraExportFieldMappings;
	}

	public String translateValue(String mapName, String value) {
		value = value.toLowerCase();
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null) {
			translatedValue = value;
		} else {
			if (translationMap.containsKey(value)) {
				translatedValue = translationMap.get(value);
			} else {
				translatedValue = translationMap.getOrDefault("*", value);
			}
		}
		if (translatedValue != null) {
			translatedValue = translatedValue.trim();
			if (translatedValue.isEmpty()) {
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	public void setSierraExportFieldMappings(SierraExportFieldMapping fieldMapping) {
		sierraExportFieldMappings = fieldMapping;
	}

	public String getOrderRecordStatusToTreatAsUnderConsideration() {
		return orderRecordStatusToTreatAsUnderConsideration;
	}

	public void setOrderRecordStatusToTreatAsUnderConsideration(String orderRecordStatusToTreatAsUnderConsideration) {
		this.orderRecordStatusToTreatAsUnderConsideration = orderRecordStatusToTreatAsUnderConsideration;
	}
}
