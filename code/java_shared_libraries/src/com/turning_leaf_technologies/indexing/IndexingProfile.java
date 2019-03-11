package com.turning_leaf_technologies.indexing;

import java.io.File;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;

import org.apache.logging.log4j.Logger;

public class IndexingProfile {
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volume;
	private char itemUrl;
	private char totalRenewalsSubfield;
	private char iCode2Subfield;
	private char lastYearCheckoutsSubfield;
	private char barcodeSubfield;
	private Long id;
	private String name;
	private String marcPath;
	private String marcEncoding;
	private String individualMarcPath;
	private int numCharsToCreateFolderFrom;
	private boolean createFolderFromLeadingCharacters;
	private String recordNumberTag;
	private String recordNumberPrefix;
	private String itemTag ;
	private char itemRecordNumberSubfield;
	private String lastCheckinFormat;
	private SimpleDateFormat lastCheckinFormatter;
	private String dateCreatedFormat;
	private SimpleDateFormat dateCreatedFormatter;
	private String dueDateFormat;
	private char lastCheckinDateSubfield;
	private char locationSubfield;
	private char itemStatusSubfield;
	private char iTypeSubfield;
	private char shelvingLocationSubfield;
	private char yearToDateCheckoutsSubfield;
	private char totalCheckoutsSubfield;
	private char callNumberSubfield;
	private char dateCreatedSubfield;
	private char dueDateSubfield;
	private SimpleDateFormat dueDateFormatter;
	private char eContentDescriptor = ' ';
	private boolean doAutomaticEcontentSuppression;


	public String getFilenamesToInclude() {
		return filenamesToInclude;
	}

	private void setFilenamesToInclude(String filenamesToInclude) {
		this.filenamesToInclude = filenamesToInclude;
	}

	public String getGroupingClass() {
		return groupingClass;
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

	public char getFormat() {
		return format;
	}

	public void setFormat(char format) {
		this.format = format;
	}

	public boolean isGroupUnchangedFiles() {
		return groupUnchangedFiles;
	}

	private void setGroupUnchangedFiles(boolean groupUnchangedFiles) {
		this.groupUnchangedFiles = groupUnchangedFiles;
	}

	private String filenamesToInclude;
	private String groupingClass;
	private String specifiedFormatCategory;
	private String formatSource;
	private char format;
	private boolean groupUnchangedFiles;

	public static IndexingProfile loadIndexingProfile(Connection dbConn, String profileToLoad, Logger logger) {
		//Get the Indexing Profile from the database
		IndexingProfile indexingProfile = new IndexingProfile();
		try {
			PreparedStatement getIndexingProfileStmt = dbConn.prepareStatement("SELECT * FROM indexing_profiles where name ='" + profileToLoad + "'");
			ResultSet indexingProfileRS = getIndexingProfileStmt.executeQuery();
			if (indexingProfileRS.next()) {

				indexingProfile.setId(indexingProfileRS.getLong("id"));
				indexingProfile.setName(indexingProfileRS.getString("name"));
				indexingProfile.setFilenamesToInclude(indexingProfileRS.getString("filenamesToInclude"));
				indexingProfile.setMarcPath(indexingProfileRS.getString("marcPath"));
				indexingProfile.setMarcEncoding(indexingProfileRS.getString("marcEncoding"));
				indexingProfile.setRecordNumberTag(indexingProfileRS.getString("recordNumberTag"));
				indexingProfile.setRecordNumberPrefix(indexingProfileRS.getString("recordNumberPrefix"));
				indexingProfile.setItemTag(indexingProfileRS.getString("itemTag"));
				indexingProfile.setItemRecordNumberSubfield(getCharFromRecordSet(indexingProfileRS,"itemRecordNumber"));
				indexingProfile.setLastCheckinDateSubfield(getCharFromRecordSet(indexingProfileRS,"lastCheckinDate"));
				indexingProfile.setLastCheckinFormat(indexingProfileRS.getString("lastCheckinFormat"));
				indexingProfile.setLocationSubfield(getCharFromRecordSet(indexingProfileRS,"location"));
				indexingProfile.setItemStatusSubfield(getCharFromRecordSet(indexingProfileRS,"status"));
				indexingProfile.setDueDateSubfield(getCharFromRecordSet(indexingProfileRS,"dueDate"));
				indexingProfile.setDueDateFormat(indexingProfileRS.getString("dueDateFormat"));
				indexingProfile.setDateCreatedSubfield(getCharFromRecordSet(indexingProfileRS,"dateCreated"));
				indexingProfile.setDateCreatedFormat(indexingProfileRS.getString("dateCreatedFormat"));
				indexingProfile.setCallNumberSubfield(getCharFromRecordSet(indexingProfileRS,"callNumber"));
				indexingProfile.setTotalCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"totalCheckouts"));
				indexingProfile.setYearToDateCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"yearToDateCheckouts"));

				indexingProfile.setIndividualMarcPath(indexingProfileRS.getString("individualMarcPath"));
				indexingProfile.setName(indexingProfileRS.getString("name"));
				indexingProfile.setNumCharsToCreateFolderFrom(indexingProfileRS.getInt("numCharsToCreateFolderFrom"));
				indexingProfile.setCreateFolderFromLeadingCharacters(indexingProfileRS.getBoolean("createFolderFromLeadingCharacters"));

				indexingProfile.setShelvingLocationSubfield(getCharFromRecordSet(indexingProfileRS,"shelvingLocation"));
				indexingProfile.setITypeSubfield(getCharFromRecordSet(indexingProfileRS,"iType"));

				indexingProfile.setGroupingClass(indexingProfileRS.getString("groupingClass"));
				indexingProfile.setFormatSource(indexingProfileRS.getString("formatSource"));
				indexingProfile.setSpecifiedFormatCategory(indexingProfileRS.getString("specifiedFormatCategory"));
				indexingProfile.setFormat(getCharFromRecordSet(indexingProfileRS, "format"));
				indexingProfile.setGroupUnchangedFiles(indexingProfileRS.getBoolean("groupUnchangedFiles"));

				indexingProfile.setDoAutomaticEcontentSuppression(indexingProfileRS.getBoolean("doAutomaticEcontentSuppression"));
				indexingProfile.setEContentDescriptor(getCharFromRecordSet(indexingProfileRS, "eContentDescriptor"));

				indexingProfile.setLastYearCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS, "lastYearCheckoutsSubfield"));
				indexingProfile.setBarcodeSubfield(getCharFromRecordSet(indexingProfileRS, "barcodeSubfield"));
				indexingProfile.setTotalRenewalsSubfield(getCharFromRecordSet(indexingProfileRS, "totalRenewalsSubfield"));
				indexingProfile.setICode2Subfield(getCharFromRecordSet(indexingProfileRS, "iCode2Subfield"));

				indexingProfile.setCallNumberCutterSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberCutterSubfield"));
				indexingProfile.setCallNumberPoststampSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberPoststampSubfield"));
				indexingProfile.setVolume(getCharFromRecordSet(indexingProfileRS, "volume"));
				indexingProfile.setItemUrl(getCharFromRecordSet(indexingProfileRS, "itemUrl"));
			} else {
				logger.error("Unable to find " + profileToLoad + " indexing profile, please create a profile with the name ils.");
			}

		}catch (Exception e){
			logger.error("Error reading index profile for CarlX", e);
		}
		return indexingProfile;
	}

	public File getFileForIlsRecord(String recordNumber) {
		StringBuilder shortId = new StringBuilder(recordNumber.replace(".", ""));
		while (shortId.length() < 9){
			shortId.insert(0, "0");
		}

		String subFolderName;
		if (isCreateFolderFromLeadingCharacters()){
			subFolderName        = shortId.substring(0, getNumCharsToCreateFolderFrom());
		}else{
			subFolderName        = shortId.substring(0, shortId.length() - getNumCharsToCreateFolderFrom());
		}

		String basePath           = getIndividualMarcPath() + "/" + subFolderName;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		return new File(individualFilename);
	}

	public String getItemTag() {
		return itemTag;
	}

	public void setItemTag(String itemTag) {
		this.itemTag = itemTag;
	}

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	private String getIndividualMarcPath() {
		return individualMarcPath;
	}

	private void setIndividualMarcPath(String individualMarcPath) {
		this.individualMarcPath = individualMarcPath;
	}

	private int getNumCharsToCreateFolderFrom() {
		return numCharsToCreateFolderFrom;
	}

	private void setNumCharsToCreateFolderFrom(int numCharsToCreateFolderFrom) {
		this.numCharsToCreateFolderFrom = numCharsToCreateFolderFrom;
	}

	private boolean isCreateFolderFromLeadingCharacters() {
		return createFolderFromLeadingCharacters;
	}

	private void setCreateFolderFromLeadingCharacters(boolean createFolderFromLeadingCharacters) {
		this.createFolderFromLeadingCharacters = createFolderFromLeadingCharacters;
	}

	public String getRecordNumberTag() {
		return recordNumberTag;
	}

	private void setRecordNumberTag(String recordNumberTag) {
		this.recordNumberTag = recordNumberTag;
	}

	public char getItemRecordNumberSubfield() {
		return itemRecordNumberSubfield;
	}

	private void setItemRecordNumberSubfield(char itemRecordNumberSubfield) {
		this.itemRecordNumberSubfield = itemRecordNumberSubfield;
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

	public char getItemStatusSubfield() {
		return itemStatusSubfield;
	}

	private void setItemStatusSubfield(char itemStatusSubfield) {
		this.itemStatusSubfield = itemStatusSubfield;
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

	public String getMarcPath() {
		return marcPath;
	}

	public void setMarcPath(String marcPath) {
		this.marcPath = marcPath;
	}

	public String getMarcEncoding() {
		return marcEncoding;
	}

	private void setMarcEncoding(String marcEncoding) {
		this.marcEncoding = marcEncoding;
	}

	public String getRecordNumberPrefix() {
		return recordNumberPrefix;
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

	private static char getCharFromRecordSet(ResultSet indexingProfilesRS, String fieldName) throws SQLException {
		char result = ' ';
		String databaseValue = indexingProfilesRS.getString(fieldName);
		if (!indexingProfilesRS.wasNull() && databaseValue.length() > 0){
			result = databaseValue.charAt(0);
		}
		return result;
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
}
