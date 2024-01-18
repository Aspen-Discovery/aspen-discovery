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
		productsKey = settingRS.getString("productsKey");
		runFullUpdate = settingRS.getBoolean("runFullUpdate");
		allowLargeDeletes = settingRS.getBoolean("allowLargeDeletes");
		lastUpdateOfChangedRecords = settingRS.getLong("lastUpdateOfChangedRecords");
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
			if (!product.isEmpty()) {
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

	public HashSet<String> getProductsToUpdate(){
		return productsToUpdate;
	}

	public synchronized void addProductToUpdateNextTime(String overDriveId){
		productsToUpdateNextTime.add(overDriveId);
	}

	public String getProductsToUpdateNextTimeAsString(){
		return String.join("\n", productsToUpdateNextTime);
	}
}
