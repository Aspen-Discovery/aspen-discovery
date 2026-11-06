package com.turning_leaf_technologies.borrowbox;

import com.turning_leaf_technologies.encryption.EncryptionUtils;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;

public class BorrowBoxSetting {
	private final long id;
	private final String name;
	private final String apiUrl;
	private final String apiUsername;
	private String apiPassword;
	private final boolean runFullUpdate;
	private final long lastUpdateOfChangedRecords;
	private final long lastUpdateOfAllRecords;
	private final boolean allowLargeDeletes;
	private final boolean enableRequestLogging;
	private final int numRetriesOnError;
	private final HashSet<String> productsToUpdate = new HashSet<>();
	private final HashSet<String> productsToUpdateNextTime = new HashSet<>();

	BorrowBoxSetting(ResultSet settingRS, String serverName) throws SQLException {
		id = settingRS.getLong("id");
		name = settingRS.getString("name");
		apiUrl = settingRS.getString("apiUrl");
		apiUsername = settingRS.getString("apiUsername");
		try {
			apiPassword = EncryptionUtils.decryptString(settingRS.getString("apiPassword"), serverName, null);
		} catch (Exception e) {
			System.err.println("Error loading API password for " + serverName);
			apiPassword = settingRS.getString("apiPassword");
		}
		runFullUpdate = settingRS.getBoolean("runFullUpdate");
		lastUpdateOfChangedRecords = settingRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingRS.getLong("lastUpdateOfAllRecords");
		allowLargeDeletes = settingRS.getBoolean("allowLargeDeletes");
		enableRequestLogging = settingRS.getBoolean("enableRequestLogging");
		numRetriesOnError = Math.max(1, settingRS.getInt("numRetriesOnError"));
		String productsToUpdateStr = settingRS.getString("productsToUpdate");
		if (productsToUpdateStr == null) {
			productsToUpdateStr = "";
		} else {
			productsToUpdateStr = productsToUpdateStr.trim();
		}
		String[] products = productsToUpdateStr.split("\r\n|\r|\n");
		for (String product : products) {
			product = product.trim();
			if (product.isEmpty()) {
				continue;
			}
			productsToUpdate.add(product);
		}
	}

	public long getId() {
		return id;
	}

	public String getName() {
		return name;
	}

	public String getApiUrl() {
		return apiUrl;
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

	public boolean isAllowLargeDeletes() {
		return allowLargeDeletes;
	}

	public boolean isEnableRequestLogging() {
		return enableRequestLogging;
	}

	public int getNumRetriesOnError() {
		return numRetriesOnError;
	}

	public HashSet<String> getProductsToUpdate() {
		return productsToUpdate;
	}

	public synchronized void addProductToUpdateNextTime(String borrowBoxId) {
		productsToUpdateNextTime.add(borrowBoxId);
	}

	public String getProductsToUpdateNextTimeAsString() {
		return String.join("\n", productsToUpdateNextTime);
	}
}
