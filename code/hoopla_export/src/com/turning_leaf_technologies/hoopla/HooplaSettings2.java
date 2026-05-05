package com.turning_leaf_technologies.hoopla;

import java.sql.ResultSet;
import java.sql.SQLException;

class HooplaSettings2 {
	private final long settingsId;
	private final String apiUrl;
	private final String apiUsername;
	private final String apiPassword;

	// General Indexing Settings
	private final int recordExtractionBatchSize;
	private final int indexingTime;
	private final String countryCode;
	private final boolean runFullUpdate;
	private final long lastUpdateOfChangedRecords;
	private final long lastUpdateOfAllRecords;
	private final String lastRecordProcessed;
	private final int hooplaFlexBatchSize;

	// Token settings
	private final String accessToken;
	private final long tokenExpirationTime;

	private final boolean regroupAllRecords;

	public HooplaSettings2(ResultSet settingsRS) throws SQLException {
		settingsId = settingsRS.getLong("id");
		apiUrl = settingsRS.getString("apiUrl");
		apiUsername = settingsRS.getString("apiUsername");
		apiPassword = settingsRS.getString("apiPassword");
		countryCode = settingsRS.getString("countryCode");

		recordExtractionBatchSize = settingsRS.getInt("recordExtractionBatchSize");
		hooplaFlexBatchSize = settingsRS.getInt("hooplaFlexBatchSize");
		indexingTime = settingsRS.getInt("indexingTime");
		runFullUpdate = settingsRS.getBoolean("runFullUpdate");
		lastUpdateOfChangedRecords = settingsRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingsRS.getLong("lastUpdateOfAllRecords");
		lastRecordProcessed = settingsRS.getString("lastRecordProcessed");

		accessToken = settingsRS.getString("accessToken");
		tokenExpirationTime = settingsRS.getLong("tokenExpirationTime");

		regroupAllRecords = settingsRS.getBoolean("regroupAllRecords");
	}

	public long getSettingsId() {
		return settingsId;
	}

	public String getApiUrl() {
		return apiUrl;
	}

	public String getCountryCode() {
		return countryCode;
	}

	public String getApiUsername() {
		return apiUsername;
	}

	public String getApiPassword() {
		return apiPassword;
	}

	public boolean isRunFullUpdate() {
		return runFullUpdate;
	}

	public long getLastUpdateOfChangedRecords() {
		return lastUpdateOfChangedRecords;
	}

	public long getLastUpdateOfAllRecords() {
		return lastUpdateOfAllRecords;
	}

	public String getAccessToken() {
		return accessToken;
	}

	public long getTokenExpirationTime() {
		return tokenExpirationTime;
	}

	public boolean isRegroupAllRecords() {
		return regroupAllRecords;
	}

	public int getRecordExtractionBatchSize() {
		return recordExtractionBatchSize;
	}

	public int getIndexingTime() {
		return indexingTime;
	}

	public String getLastRecordProcessed() {
		return lastRecordProcessed;
	}

	public int getHooplaFlexBatchSize() {
		return hooplaFlexBatchSize;
	}

}
