package com.turning_leaf_technologies.overdrive;

import com.turning_leaf_technologies.encryption.EncryptionUtils;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Collections;
import java.util.HashSet;

public class OverDriveSetting {
	private final long id;
	private String clientSecret;
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
	private final int numRetriesOnError;
	private final HashSet<String> productsToUpdate = new HashSet<>();
	private final HashSet<String> productsToUpdateNextTime = new HashSet<>();

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
		websiteId = settingRS.getString("websiteId");
		productsKey = settingRS.getString("productsKey");
		runFullUpdate = settingRS.getBoolean("runFullUpdate");
		allowLargeDeletes = settingRS.getBoolean("allowLargeDeletes");
		lastUpdateOfChangedRecords = settingRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingRS.getLong("lastUpdateOfAllRecords");
		numExtractionThreads = settingRS.getInt("numExtractionThreads");
		enableRequestLogging = settingRS.getBoolean("enableRequestLogging");
		numRetriesOnError = Math.max(1, settingRS.getInt("numRetriesOnError"));
		String productsToUpdateStr = settingRS.getString("productsToUpdate");
		if (productsToUpdateStr == null){
			productsToUpdateStr = "";
		}else{
			productsToUpdateStr = productsToUpdateStr.trim();
		}
		String[] products = productsToUpdateStr.split("\r\n|\r|\n");
		for (String product : products){
			product = product.trim().toLowerCase();
			if (product.length() > 0) {
				productsToUpdate.add(product);
			}
		}
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

	public int getNumRetriesOnError() {
		return numRetriesOnError;
	}

	public HashSet<String> getProductsToUpdate(){
		return productsToUpdate;
	}

	public void addProductToUpdateNextTime(String overDriveId){
		productsToUpdateNextTime.add(overDriveId);
	}

	public String getProductsToUpdateNextTimeAsString(){
		return String.join("\n", productsToUpdateNextTime);
	}
}
