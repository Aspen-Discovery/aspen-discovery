package com.turning_leaf_technologies.hoopla;

import java.sql.ResultSet;
import java.sql.SQLException;

class HooplaSettings {
	private final long settingsId;
	private final String apiUrl;
	private final int libraryId;
	private final String apiUsername;
	private final String apiPassword;
	private final int recordExtractionBatchSize;
	private final int indexingTime;
	private final int hooplaFlexBatchSize;

	// Instant settings
	private final boolean hooplaInstantEnabled;
	private final boolean runFullUpdateInstant;
	private final long lastUpdateOfChangedRecordsInstant;
	private final long lastUpdateOfAllRecordsInstant;

	// Flex settings
	private final boolean hooplaFlexEnabled;
	private final boolean runFullUpdateFlex;
	private final long lastUpdateOfChangedRecordsFlex;
	private final long lastUpdateOfAllRecordsFlex;

	// Token settings
	private final String accessToken;
	private final long tokenExpirationTime;

	private final boolean regroupAllRecords;

	public HooplaSettings(ResultSet settingsRS) throws SQLException {
		settingsId = settingsRS.getLong("id");
		apiUrl = settingsRS.getString("apiUrl");
		libraryId = settingsRS.getInt("libraryId");
		apiUsername = settingsRS.getString("apiUsername");
		apiPassword = settingsRS.getString("apiPassword");

		recordExtractionBatchSize = settingsRS.getInt("recordExtractionBatchSize");
		hooplaFlexBatchSize = settingsRS.getInt("hooplaFlexBatchSize");
		indexingTime = settingsRS.getInt("indexingTime");
		hooplaInstantEnabled = settingsRS.getBoolean("hooplaInstantEnabled");
		runFullUpdateInstant = settingsRS.getBoolean("runFullUpdateInstant");
		lastUpdateOfChangedRecordsInstant = settingsRS.getLong("lastUpdateOfChangedRecordsInstant");
		lastUpdateOfAllRecordsInstant = settingsRS.getLong("lastUpdateOfAllRecordsInstant");

		hooplaFlexEnabled = settingsRS.getBoolean("hooplaFlexEnabled");
		runFullUpdateFlex = settingsRS.getBoolean("runFullUpdateFlex");
		lastUpdateOfChangedRecordsFlex = settingsRS.getLong("lastUpdateOfChangedRecordsFlex");
		lastUpdateOfAllRecordsFlex = settingsRS.getLong("lastUpdateOfAllRecordsFlex");

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

	public int getLibraryId() {
		return libraryId;
	}

	public String getApiUsername() {
		return apiUsername;
	}

	public String getApiPassword() {
		return apiPassword;
	}

	public boolean isHooplaEnabled(String hooplaType) {
		if (hooplaType.equals("Flex")) {
			return hooplaFlexEnabled;
		} else if (hooplaType.equals("Instant")) {
			return hooplaInstantEnabled;
		}
		return false;
	}

	public boolean isRunFullUpdate(String hooplaType) {
		if (hooplaType.equals("Flex")) {
			return runFullUpdateFlex;
		} else if (hooplaType.equals("Instant")) {
			return runFullUpdateInstant;
		}
		return false;
	}

	public long getLastUpdateOfChangedRecords(String hooplaType) {
		if (hooplaType.equals("Flex")) {
			return lastUpdateOfChangedRecordsFlex;
		} else if (hooplaType.equals("Instant")) {
			return lastUpdateOfChangedRecordsInstant;
		}
		return 0;
	}

	public long getLastUpdateOfAllRecords(String hooplaType) {
		if (hooplaType.equals("Flex")) {
			return lastUpdateOfAllRecordsFlex;
		} else if (hooplaType.equals("Instant")) {
		return lastUpdateOfAllRecordsInstant;
		}
		return 0;
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

	public int getHooplaFlexBatchSize() {
		return hooplaFlexBatchSize;
	}
}
