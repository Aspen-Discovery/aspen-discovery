package com.turning_leaf_technologies.overdrive;

import java.sql.ResultSet;
import java.sql.SQLException;

public class OverDriveSetting {
	private final long id;
	private final String clientSecret;
	private final String clientKey;
	private final String accountId;
	private final String websiteId;
	private final String productsKey;
	private final boolean runFullUpdate;
	private final long lastUpdateOfChangedRecords;
	private final long lastUpdateOfAllRecords;
	private final boolean allowLargeDeletes;
	private final int numExtractionThreads;
	private final boolean enableRequestLogging;

	OverDriveSetting(ResultSet settingRS) throws SQLException {
		id = settingRS.getLong("id");
		clientSecret = settingRS.getString("clientSecret");
		clientKey = settingRS.getString("clientKey");
		accountId = settingRS.getString("accountId");
		websiteId = settingRS.getString("websiteId");
		productsKey = settingRS.getString("productsKey");
		runFullUpdate = settingRS.getBoolean("runFullUpdate");
		allowLargeDeletes = settingRS.getBoolean("allowLargeDeletes");
		lastUpdateOfChangedRecords = settingRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingRS.getLong("lastUpdateOfAllRecords");
		numExtractionThreads = settingRS.getInt("numExtractionThreads");
		enableRequestLogging = settingRS.getBoolean("enableRequestLogging");
	}

	public long getId() {
		return id;
	}

	public String getClientSecret() {
		return clientSecret;
	}

	public String getClientKey() {
		return clientKey;
	}

	public String getAccountId() {
		return accountId;
	}

	public String getWebsiteId() {
		return websiteId;
	}

	public String getProductsKey() {
		return productsKey;
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

	public boolean isAllowLargeDeletes() {
		return allowLargeDeletes;
	}

	public int getNumExtractionThreads() {
		return numExtractionThreads;
	}

	public boolean isEnableRequestLogging() {
		return enableRequestLogging;
	}
}
