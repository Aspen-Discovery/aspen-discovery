package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SideLoadSettings extends BaseIndexingSettings {
	private HashSet<String> deletedIds = new HashSet<>();
	private boolean convertFormatToEContent;

	public SideLoadSettings(String serverName, ResultSet settings, BaseIndexingLogEntry logEntry) throws SQLException {
		super(serverName, logEntry);
		this.id = settings.getLong("id");
		this.name = settings.getString("name");
		this.lastUpdateOfChangedRecords = settings.getLong("lastUpdateOfChangedRecords");
		this.lastUpdateOfAllRecords = settings.getLong("lastUpdateOfAllRecords");
		this.runFullUpdate = settings.getBoolean("runFullUpdate");
		this.groupingClass = settings.getString("groupingClass");
		this.marcPath = settings.getString("marcPath");
		this.filenamesToInclude = settings.getString("filenamesToInclude");
		this.marcEncoding = settings.getString("marcEncoding");
		this.recordNumberTag = settings.getString("recordNumberTag");
		this.recordNumberTagInt = settings.getInt("recordNumberTag");
		this.recordNumberPrefix = settings.getString("recordNumberPrefix");
		this.recordNumberSubfield = getCharFromRecordSet(settings, "recordNumberSubfield");
		this.formatSource = settings.getString("formatSource");
		this.specifiedFormat = settings.getString("specifiedFormat");
		this.specifiedFormatCategory = settings.getString("specifiedFormatCategory");
		this.specifiedFormatBoost = settings.getInt("specifiedFormatBoost");
		this.treatUnknownLanguageAs = settings.getString("treatUnknownLanguageAs");
		this.includePersonalAndCorporateNamesInTopics = settings.getBoolean("includePersonalAndCorporateNamesInTopics");
		this.convertFormatToEContent = settings.getBoolean("convertFormatToEContent");

		String deletedIdString = settings.getString("deletedRecordsIds");
		if (deletedIdString != null && !deletedIdString.trim().isEmpty()) {
			Pattern deletedIdsPattern = Pattern.compile("([^,\r\n\\s]*)[,\r\n\\s]*", Pattern.DOTALL);
			Matcher deletedIdMatcher = deletedIdsPattern.matcher(deletedIdString.trim());
			while (deletedIdMatcher.find()) {
				String deletedId = deletedIdMatcher.group(1);
				if (!deletedId.isEmpty()) {
					deletedIds.add(deletedId);
				}
			}
		}
	}

	public HashSet<String> getDeletedIds(){
		return deletedIds;
	}

	public boolean isConvertFormatToEContent() {
		return convertFormatToEContent;
	}
}
