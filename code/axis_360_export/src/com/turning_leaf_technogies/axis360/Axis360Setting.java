package com.turning_leaf_technogies.axis360;

import java.sql.ResultSet;
import java.sql.SQLException;

class Axis360Setting {
	private String baseUrl;
	private String vendorUsername;
	private String vendorPassword;
	private String libraryPrefix;
	private boolean doFullReload;
	private long lastUpdateOfChangedRecords;
	private long lastUpdateOfAllRecords;
	private long id;

	Axis360Setting(ResultSet settingsRS) throws SQLException {
		baseUrl = settingsRS.getString("apiUrl");
		vendorUsername = settingsRS.getString("vendorUsername");
		vendorPassword = settingsRS.getString("vendorPassword");
		libraryPrefix = settingsRS.getString("libraryPrefix");
		doFullReload = settingsRS.getBoolean("runFullUpdate");
		lastUpdateOfChangedRecords = settingsRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingsRS.getLong("lastUpdateOfAllRecords");
		id = settingsRS.getLong("id");
	}

	String getBaseUrl() {
		return baseUrl;
	}

	String getVendorUsername() {
		return vendorUsername;
	}

	String getVendorPassword() {
		return vendorPassword;
	}

	String getLibraryPrefix() {
		return libraryPrefix;
	}

	boolean doFullReload() {
		return doFullReload;
	}

	long getId() {
		return id;
	}

	public long getLastUpdateOfChangedRecords() {
		return Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
	}
}
