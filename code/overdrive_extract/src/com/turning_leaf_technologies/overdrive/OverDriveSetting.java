package com.turning_leaf_technologies.overdrive;

import com.turning_leaf_technologies.encryption.EncryptionUtils;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;

public class OverDriveSetting {
	private final long id;
	private String clientSecret;
	private final String clientKey;
	private final String accountId;
	private final String productsKey;
	private final boolean runFullUpdate;
	private final long lastUpdateOfChangedRecords;
	private final boolean allowLargeDeletes;
	private final boolean enableRequestLogging;
	private final int numRetriesOnError;
	private final int deletionCheckHour;

	OverDriveSetting(ResultSet settingRS, String serverName) throws SQLException {
		id = settingRS.getLong("id");
		try {
			clientSecret = EncryptionUtils.decryptString(settingRS.getString("clientSecret"), serverName, null);
		}catch (Exception e){
			System.err.println("Error loading client secret for " + serverName);
			clientSecret = settingRS.getString("clientSecret");
		}
		clientKey = settingRS.getString("clientKey");
		accountId = settingRS.getString("accountId");
		productsKey = settingRS.getString("productsKey");
		runFullUpdate = settingRS.getBoolean("runFullUpdate");
		allowLargeDeletes = settingRS.getBoolean("allowLargeDeletes");
		lastUpdateOfChangedRecords = settingRS.getLong("lastUpdateOfChangedRecords");
		enableRequestLogging = settingRS.getBoolean("enableRequestLogging");
		numRetriesOnError = Math.max(1, settingRS.getInt("numRetriesOnError"));
		deletionCheckHour = settingRS.getInt("deletionCheckHour");
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

	public String getProductsKey() {
		return productsKey;
	}

	public boolean isRunFullUpdate() {
		return runFullUpdate;
	}

	public long getLastUpdateOfChangedRecords() {
		return lastUpdateOfChangedRecords;
	}

	public boolean isAllowLargeDeletes() {
		return allowLargeDeletes;
	}

	public boolean isEnableRequestLogging() {
		return enableRequestLogging;
	}

	public int getNumRetriesOnError() {
		return numRetriesOnError;
	}

	public int getDeletionCheckHour() {
		return deletionCheckHour;
	}

}
