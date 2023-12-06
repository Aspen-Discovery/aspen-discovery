package com.turning_leaf_technologies.indexing;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;

public class IndexingProfile extends BaseIndexingSettings {
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volume;
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
	private Pattern statusesToSuppressPattern;
	private Pattern nonHoldableStatuses;
	private boolean treatLibraryUseOnlyGroupedStatusesAsAvailable;
	private char iTypeSubfield;
	private char collectionSubfield;
	private char shelvingLocationSubfield;
	private char yearToDateCheckoutsSubfield;
	private char totalCheckoutsSubfield;
	private char callNumberSubfield;
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
	private long lastUpdateOfAuthorities;
	private long lastChangeProcessed;
	private Pattern suppressRecordsWithUrlsMatching;
	private String fallbackFormatField;
	private String customMarcFieldsToIndexAsKeyword;
	private boolean processRecordLinking;
	private int evergreenOrgUnitSchema;
	private String orderRecordsStatusesToInclude;
	private boolean hideOrderRecordsForBibsWithPhysicalItems;
	private int orderRecordsToSuppressByDate;
	private boolean checkSierraMatTypeForFormat;

	//Custom Facets
	private String customFacet1SourceField;
	private String customFacet1ValuesToInclude;
	private Pattern customFacet1ValuesToIncludePattern;
	private String customFacet1ValuesToExclude;
	private Pattern customFacet1ValuesToExcludePattern;
	private String customFacet2SourceField;
	private String customFacet2ValuesToInclude;
	private Pattern customFacet2ValuesToIncludePattern;
	private String customFacet2ValuesToExclude;
	private Pattern customFacet2ValuesToExcludePattern;
	private String customFacet3SourceField;
	private String customFacet3ValuesToInclude;
	private Pattern customFacet3ValuesToIncludePattern;
	private String customFacet3ValuesToExclude;
	private Pattern customFacet3ValuesToExcludePattern;

	//Evergreen settings
	private int numRetriesForBibLookups;
	private int numMillisecondsToPauseAfterBibLookups;
	private int numExtractionThreads;

	public IndexingProfile(ResultSet indexingProfileRS, BaseIndexingLogEntry logEntry)  throws SQLException {
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
			if (pattern != null && pattern.length() > 0) {
				nonHoldableLocations = Pattern.compile("^(" + pattern + ")$");
			}
		}catch (Exception e){
			logEntry.incErrors("Could not load non holdable locations", e);
		}
		String locationsToSuppress = indexingProfileRS.getString("locationsToSuppress");
		if (locationsToSuppress != null && locationsToSuppress.length() > 0){
			locationsToSuppressPattern = Pattern.compile(locationsToSuppress);
		}

		String collectionsToSuppress = indexingProfileRS.getString("collectionsToSuppress");
		if (collectionsToSuppress != null && collectionsToSuppress.length() > 0){
			collectionsToSuppressPattern = Pattern.compile(collectionsToSuppress);
		}
		this.setItemStatusSubfield(getCharFromRecordSet(indexingProfileRS,"status"));
		String statusesToSuppress = indexingProfileRS.getString("statusesToSuppress");
		if (statusesToSuppress != null && statusesToSuppress.length() > 0){
			this.statusesToSuppressPattern = Pattern.compile(statusesToSuppress);
		}
		try {
			String pattern = indexingProfileRS.getString("nonHoldableStatuses");
			if (pattern != null && pattern.length() > 0) {
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
		this.setCallNumberSubfield(getCharFromRecordSet(indexingProfileRS,"callNumber"));
		this.setTotalCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"totalCheckouts"));
		this.setYearToDateCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"yearToDateCheckouts"));

		this.setIndividualMarcPath(indexingProfileRS.getString("individualMarcPath"));
		this.setName(indexingProfileRS.getString("name"));
		this.setNumCharsToCreateFolderFrom(indexingProfileRS.getInt("numCharsToCreateFolderFrom"));
		this.setCreateFolderFromLeadingCharacters(indexingProfileRS.getBoolean("createFolderFromLeadingCharacters"));

		this.setShelvingLocationSubfield(getCharFromRecordSet(indexingProfileRS,"shelvingLocation"));
		this.setITypeSubfield(getCharFromRecordSet(indexingProfileRS,"iType"));
		this.setCollectionSubfield(getCharFromRecordSet(indexingProfileRS,"collection"));
		this.setSubLocationSubfield(getCharFromRecordSet(indexingProfileRS,"subLocation"));

		this.setGroupingClass(indexingProfileRS.getString("groupingClass"));
		this.setFormatSource(indexingProfileRS.getString("formatSource"));
		this.setFallbackFormatField(indexingProfileRS.getString("fallbackFormatField"));
		this.setSpecifiedFormatCategory(indexingProfileRS.getString("specifiedFormatCategory"));
		this.setFormatSubfield(getCharFromRecordSet(indexingProfileRS, "format"));
		this.setCheckRecordForLargePrint(indexingProfileRS.getBoolean("checkRecordForLargePrint"));

		this.setDoAutomaticEcontentSuppression(indexingProfileRS.getBoolean("doAutomaticEcontentSuppression"));
		this.setSuppressRecordsWithUrlsMatching(indexingProfileRS.getString("suppressRecordsWithUrlsMatching"));
		this.setEContentDescriptor(getCharFromRecordSet(indexingProfileRS, "eContentDescriptor"));

		this.setLastYearCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS, "lastYearCheckouts"));
		this.setBarcodeSubfield(getCharFromRecordSet(indexingProfileRS, "barcode"));
		if (this.getItemRecordNumberSubfield() == ' '){
			this.setItemRecordNumberSubfield(this.getBarcodeSubfield());
		}
		this.setTotalRenewalsSubfield(getCharFromRecordSet(indexingProfileRS, "totalRenewals"));
		this.setICode2Subfield(getCharFromRecordSet(indexingProfileRS, "iCode2"));

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

		this.setNoteSubfield(getCharFromRecordSet(indexingProfileRS, "noteSubfield"));

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
		this.customMarcFieldsToIndexAsKeyword = indexingProfileRS.getString("customMarcFieldsToIndexAsKeyword");
		this.processRecordLinking = indexingProfileRS.getBoolean("processRecordLinking");

		this.evergreenOrgUnitSchema = indexingProfileRS.getInt("evergreenOrgUnitSchema");

		this.orderRecordsStatusesToInclude = indexingProfileRS.getString("orderRecordsStatusesToInclude");
		this.hideOrderRecordsForBibsWithPhysicalItems = indexingProfileRS.getBoolean("hideOrderRecordsForBibsWithPhysicalItems");
		this.orderRecordsToSuppressByDate = indexingProfileRS.getInt("orderRecordsToSuppressByDate");

		this.checkSierraMatTypeForFormat = indexingProfileRS.getBoolean("checkSierraMatTypeForFormat");

		//Custom Facet 1
		this.customFacet1SourceField = indexingProfileRS.getString("customFacet1SourceField");
		this.customFacet1ValuesToInclude = indexingProfileRS.getString("customFacet1ValuesToInclude");
		if (this.customFacet1ValuesToInclude != null && this.customFacet1ValuesToInclude.length() > 0 && !this.customFacet1ValuesToInclude.equals(".*")) {
			try {
				customFacet1ValuesToIncludePattern = Pattern.compile(customFacet1ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet1ValuesToIncludePattern", e);
			}
		}
		this.customFacet1ValuesToExclude = indexingProfileRS.getString("customFacet1ValuesToExclude");
		if (this.customFacet1ValuesToExclude != null && this.customFacet1ValuesToExclude.length() > 0) {
			try {
				customFacet1ValuesToExcludePattern = Pattern.compile(customFacet1ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet1ValuesToExcludePattern", e);
			}
		}

		//Custom Facet 2
		this.customFacet2SourceField = indexingProfileRS.getString("customFacet2SourceField");
		this.customFacet2ValuesToInclude = indexingProfileRS.getString("customFacet2ValuesToInclude");
		if (this.customFacet2ValuesToInclude != null && this.customFacet2ValuesToInclude.length() > 0 && !this.customFacet2ValuesToInclude.equals(".*")) {
			try {
				customFacet2ValuesToIncludePattern = Pattern.compile(customFacet2ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet2ValuesToIncludePattern", e);
			}
		}
		this.customFacet2ValuesToExclude = indexingProfileRS.getString("customFacet2ValuesToExclude");
		if (this.customFacet2ValuesToExclude != null && this.customFacet2ValuesToExclude.length() > 0) {
			try {
				customFacet2ValuesToExcludePattern = Pattern.compile(customFacet2ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet2ValuesToExcludePattern", e);
			}
		}

		//Custom Facet 3
		this.customFacet3SourceField = indexingProfileRS.getString("customFacet3SourceField");
		this.customFacet3ValuesToInclude = indexingProfileRS.getString("customFacet3ValuesToInclude");
		if (this.customFacet3ValuesToInclude != null && this.customFacet3ValuesToInclude.length() > 0 && !this.customFacet3ValuesToInclude.equals(".*")) {
			try {
				customFacet3ValuesToIncludePattern = Pattern.compile(customFacet3ValuesToInclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet3ValuesToIncludePattern", e);
			}
		}
		this.customFacet3ValuesToExclude = indexingProfileRS.getString("customFacet3ValuesToExclude");
		if (this.customFacet3ValuesToExclude != null && this.customFacet3ValuesToExclude.length() > 0) {
			try {
				customFacet3ValuesToExcludePattern = Pattern.compile(customFacet3ValuesToExclude, Pattern.CASE_INSENSITIVE);
			} catch (PatternSyntaxException e) {
				logEntry.incErrors("Unable to compile pattern for customFacet3ValuesToExcludePattern", e);
			}
		}

		this.numRetriesForBibLookups = indexingProfileRS.getInt("numRetriesForBibLookups");
		this.numMillisecondsToPauseAfterBibLookups = indexingProfileRS.getInt("numMillisecondsToPauseAfterBibLookups");
		this.numExtractionThreads = indexingProfileRS.getInt("numExtractionThreads");
	}

	private void setFilenamesToInclude(String filenamesToInclude) {
		this.filenamesToInclude = filenamesToInclude;
	}

	private void setGroupingClass(String groupingClass) {
		this.groupingClass = groupingClass;
	}

	public String getSpecifiedFormatCategory() {
		return specifiedFormatCategory;
	}

	private void setSpecifiedFormatCategory(String specifiedFormatCategory) {
		this.specifiedFormatCategory = specifiedFormatCategory;
	}

	public String getFormatSource() {
		return formatSource;
	}

	private void setFormatSource(String formatSource) {
		this.formatSource = formatSource;
	}

	public char getFormatSubfield() {
		return format;
	}

	public void setFormatSubfield(char format) {
		this.format = format;
	}

	public static IndexingProfile loadIndexingProfile(Connection dbConn, String profileToLoad, Logger logger, BaseIndexingLogEntry logEntry) {
		//Get the Indexing Profile from the database
		IndexingProfile indexingProfile = null;
		try {
			PreparedStatement getIndexingProfileStmt = dbConn.prepareStatement("SELECT * FROM indexing_profiles where name ='" + profileToLoad + "'");
			ResultSet indexingProfileRS = getIndexingProfileStmt.executeQuery();
			if (indexingProfileRS.next()) {
				indexingProfile = new IndexingProfile(indexingProfileRS, logEntry);

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

	private void setIndividualMarcPath(String individualMarcPath) {
		this.individualMarcPath = individualMarcPath;
	}

	private void setNumCharsToCreateFolderFrom(int numCharsToCreateFolderFrom) {
		this.numCharsToCreateFolderFrom = numCharsToCreateFolderFrom;
	}

	private void setCreateFolderFromLeadingCharacters(boolean createFolderFromLeadingCharacters) {
		this.createFolderFromLeadingCharacters = createFolderFromLeadingCharacters;
	}

	private void setRecordNumberTag(String recordNumberTag) {
		this.recordNumberTag = recordNumberTag;
		this.recordNumberTagInt = Integer.parseInt(recordNumberTag);
	}

	public char getItemRecordNumberSubfield() {
		return itemRecordNumberSubfield;
	}

	private void setItemRecordNumberSubfield(char itemRecordNumberSubfield) {
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

	private void setLocationSubfield(char locationSubfield) {
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

	public Pattern getStatusesToSuppressPattern() {
		return statusesToSuppressPattern;
	}

	public Pattern getNonHoldableStatuses() {
		return nonHoldableStatuses;
	}

	public char getITypeSubfield() {
		return iTypeSubfield;
	}

	private void setITypeSubfield(char iTypeSubfield) {
		this.iTypeSubfield = iTypeSubfield;
	}

	public char getShelvingLocationSubfield() {
		return shelvingLocationSubfield;
	}

	private void setShelvingLocationSubfield(char shelvingLocationSubfield) {
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

	private void setMarcEncoding(String marcEncoding) {
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

	private void setBarcodeSubfield(char barcodeSubfield) {
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

	private void setRecordNumberSubfield(char recordNumberSubfield) {
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

	private void setCollectionSubfield(char collectionSubfield) {
		this.collectionSubfield = collectionSubfield;
	}

	public char getCollectionSubfield() {
		return collectionSubfield;
	}

	private void setSubLocationSubfield(char sublocationSubfield) {
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
		if (suppressRecordsWithUrlsMatching.length() == 0){
			this.suppressRecordsWithUrlsMatching = null;
		}else {
			this.suppressRecordsWithUrlsMatching = Pattern.compile(suppressRecordsWithUrlsMatching, Pattern.CASE_INSENSITIVE);
		}
	}

	public Pattern getSuppressRecordsWithUrlsMatching() {
		return suppressRecordsWithUrlsMatching;
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
}
