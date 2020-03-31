package com.turning_leaf_technologies.rbdigital;

import java.sql.ResultSet;
import java.sql.SQLException;

class RBdigitalSetting {
	private String baseUrl;
	private String apiToken;
	private String libraryId;
	private boolean doFullReload;
	private long id;

	RBdigitalSetting(ResultSet settingsRS) throws SQLException {
		baseUrl = settingsRS.getString("apiUrl");
		apiToken = settingsRS.getString("apiToken");
		libraryId = settingsRS.getString("libraryId");
		doFullReload = settingsRS.getBoolean("runFullUpdate");
		id = settingsRS.getLong("id");
	}

	String getBaseUrl() {
		return baseUrl;
	}

	String getApiToken() {
		return apiToken;
	}

	String getLibraryId() {
		return libraryId;
	}

	boolean doFullReload() {
		return doFullReload;
	}

	long getId() {
		return id;
	}
}
