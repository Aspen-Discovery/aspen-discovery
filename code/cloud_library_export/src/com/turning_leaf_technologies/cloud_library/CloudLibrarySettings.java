package com.turning_leaf_technologies.cloud_library;

import org.ini4j.Ini;

import java.sql.ResultSet;
import java.sql.SQLException;

public class CloudLibrarySettings {
	private final long settingsId;
	private final String baseUrl;
	private final String accountId;
	private final String accountKey;
	private final String libraryId;
	private final boolean doFullReload;
	private long lastExtractTime;
	private final long lastExtractTimeAll;

	public CloudLibrarySettings(ResultSet getSettingsRS) throws SQLException {
		settingsId = getSettingsRS.getLong("id");

		baseUrl = getSettingsRS.getString("apiUrl");
		accountId = getSettingsRS.getString("accountId");
		accountKey = getSettingsRS.getString("accountKey");
		libraryId = getSettingsRS.getString("libraryId");

		doFullReload = getSettingsRS.getBoolean("runFullUpdate");
		lastExtractTime = getSettingsRS.getLong("lastUpdateOfChangedRecords");
		lastExtractTimeAll = getSettingsRS.getLong("lastUpdateOfAllRecords");
	}

	public long getSettingsId() {
		return settingsId;
	}

	public String getBaseUrl() {
		return baseUrl;
	}

	public String getAccountId() {
		return accountId;
	}

	public String getAccountKey() {
		return accountKey;
	}

	public String getLibraryId() {
		return libraryId;
	}

	public boolean isDoFullReload() {
		return doFullReload;
	}

	public long getLastExtractTime() {
		return lastExtractTime;
	}

	public long getLastExtractTimeAll() {
		return lastExtractTimeAll;
	}
}
