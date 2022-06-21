package com.turning_leaf_technologies.indexing;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SideLoadSettings extends BaseIndexingSettings {
	private HashSet<String> deletedIds = new HashSet<>();

	public SideLoadSettings(ResultSet settings) throws SQLException {
		this.id = settings.getLong("id");
		this.name = settings.getString("name");
		this.lastUpdateOfChangedRecords = settings.getLong("lastUpdateOfChangedRecords");
		this.lastUpdateOfAllRecords = settings.getLong("lastUpdateOfAllRecords");
		this.runFullUpdate = settings.getBoolean("runFullUpdate");
		this.groupingClass = settings.getString("groupingClass");
		this.marcPath = settings.getString("marcPath");
		this.individualMarcPath = settings.getString("individualMarcPath");
		this.filenamesToInclude = settings.getString("filenamesToInclude");
		this.marcEncoding = settings.getString("marcEncoding");
		this.numCharsToCreateFolderFrom = settings.getInt("numCharsToCreateFolderFrom");
		this.createFolderFromLeadingCharacters = settings.getBoolean("createFolderFromLeadingCharacters");
		this.recordNumberTag = settings.getString("recordNumberTag");
		this.recordNumberPrefix = settings.getString("recordNumberPrefix");
		this.recordNumberSubfield = getCharFromRecordSet(settings, "recordNumberSubfield");
		this.formatSource = settings.getString("formatSource");
		this.specifiedFormatCategory = settings.getString("specifiedFormatCategory");

		String deletedIdString = settings.getString("deletedRecordsIds");
		if (deletedIdString != null && deletedIdString.trim().length() > 0) {
			Pattern deletedIdsPattern = Pattern.compile("([^,\r\n\\s]*)[,\r\n\\s]*", Pattern.DOTALL);
			Matcher deletedIdMatcher = deletedIdsPattern.matcher(deletedIdString.trim());
			while (deletedIdMatcher.find()) {
				String deletedId = deletedIdMatcher.group(1);
				if (deletedId.length() > 0) {
					deletedIds.add(deletedId);
				}
			}
		}
	}

	public HashSet<String> getDeletedIds(){
		return deletedIds;
	}
}
