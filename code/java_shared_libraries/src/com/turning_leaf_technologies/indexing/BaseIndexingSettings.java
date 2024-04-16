package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.strings.AspenStringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;

public class BaseIndexingSettings {
	protected Long id;
	protected String name;
	protected String marcPath;
	String marcEncoding;
	String groupingClass;
	String recordNumberTag;
	int recordNumberTagInt;
	char recordNumberSubfield;
	String recordNumberPrefix;
	String filenamesToInclude;
	String formatSource;
	String specifiedFormat;
	String specifiedFormatCategory;
	int specifiedFormatBoost;
	long lastUpdateOfChangedRecords;
	long lastUpdateOfAllRecords;
	boolean runFullUpdate;
	boolean regroupAllRecords;
	String treatUnknownLanguageAs;
	String treatUndeterminedLanguageAs;
	String customMarcFieldsToIndexAsKeyword;

	static char getCharFromRecordSet(ResultSet indexingProfilesRS, String fieldName) throws SQLException {
		String subfieldString = indexingProfilesRS.getString(fieldName);
		return AspenStringUtils.convertStringToChar(subfieldString);
	}

	public String getFilenamesToInclude() {
		return filenamesToInclude;
	}

	public Long getId() {
		return id;
	}

	public String getName() {
		return name;
	}

	public String getRecordNumberTag() {
		return recordNumberTag;
	}

	public int getRecordNumberTagInt() {
		return recordNumberTagInt;
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

	public String getTreatUnknownLanguageAs() {
		return treatUnknownLanguageAs;
	}

	public String getCustomMarcFieldsToIndexAsKeyword() { return customMarcFieldsToIndexAsKeyword; }

	public String getSpecifiedFormat() {
		return specifiedFormat;
	}

	public void setSpecifiedFormat(String specifiedFormat) {
		this.specifiedFormat = specifiedFormat;
	}

	public int getSpecifiedFormatBoost() {
		return specifiedFormatBoost;
	}

	public void setSpecifiedFormatBoost(int specifiedFormatBoost) {
		this.specifiedFormatBoost = specifiedFormatBoost;
	}

	public String getTreatUndeterminedLanguageAs() {
		return treatUndeterminedLanguageAs;
	}

	public void setTreatUndeterminedLanguageAs(String treatUndeterminedLanguageAs) {
		this.treatUndeterminedLanguageAs = treatUndeterminedLanguageAs;
	}
}
