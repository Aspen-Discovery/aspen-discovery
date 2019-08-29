package com.turning_leaf_technologies.indexing;

import java.sql.ResultSet;
import java.sql.SQLException;

public class SideLoadSettings extends BaseIndexingSettings {
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
    }
}
