package com.turning_leaf_technologies.indexing;

import java.io.File;
import java.sql.ResultSet;
import java.sql.SQLException;

public class BaseIndexingSettings {
	protected Long id;
	protected String name;
	protected String marcPath;
	String marcEncoding;
	String individualMarcPath;
	int numCharsToCreateFolderFrom;
	boolean createFolderFromLeadingCharacters;
	String groupingClass;
	String recordNumberTag;
	char recordNumberSubfield;
	String recordNumberPrefix;
	String filenamesToInclude;
	String formatSource;
	String specifiedFormatCategory;
	long lastUpdateOfChangedRecords;
	long lastUpdateOfAllRecords;
	boolean runFullUpdate;
	boolean regroupAllRecords;

	static char getCharFromRecordSet(ResultSet indexingProfilesRS, String fieldName) throws SQLException {
		char result = ' ';
		String databaseValue = indexingProfilesRS.getString(fieldName);
		if (!indexingProfilesRS.wasNull() && databaseValue.length() > 0) {
			result = databaseValue.charAt(0);
		}
		return result;
	}

	public String getFilenamesToInclude() {
		return filenamesToInclude;
	}

	public File getFileForIlsRecord(String recordNumber) {
		StringBuilder shortId = new StringBuilder(recordNumber.replace(".", ""));
		while (shortId.length() < 9) {
			shortId.insert(0, "0");
		}

		String subFolderName;
		if (isCreateFolderFromLeadingCharacters()) {
			subFolderName = shortId.substring(0, getNumCharsToCreateFolderFrom());
		} else {
			subFolderName = shortId.substring(0, shortId.length() - getNumCharsToCreateFolderFrom());
		}

		String basePath = getIndividualMarcPath() + "/" + subFolderName;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		return new File(individualFilename);
	}

	public Long getId() {
		return id;
	}

	public String getName() {
		return name;
	}

	private String getIndividualMarcPath() {
		return individualMarcPath;
	}

	private int getNumCharsToCreateFolderFrom() {
		return numCharsToCreateFolderFrom;
	}

	private boolean isCreateFolderFromLeadingCharacters() {
		return createFolderFromLeadingCharacters;
	}

	public String getRecordNumberTag() {
		return recordNumberTag;
	}

	public String getMarcPath() {
		return marcPath;
	}

	public String getMarcEncoding() {
		return marcEncoding;
	}

	public String getRecordNumberPrefix() {
		return recordNumberPrefix;
	}

	public char getRecordNumberSubfield() {
		return recordNumberSubfield;
	}

	public long getLastUpdateOfChangedRecords() {
		return lastUpdateOfChangedRecords;
	}

	public long getLastUpdateOfAllRecords() {
		return lastUpdateOfAllRecords;
	}

	public boolean isRunFullUpdate() {
		return runFullUpdate;
	}

	public boolean isRegroupAllRecords() { return regroupAllRecords; }

	public String getFormatSource() {
		return formatSource;
	}

	public String getSpecifiedFormatCategory() {
		return specifiedFormatCategory;
	}

	public String getGroupingClass() {
		return groupingClass;
	}
}
