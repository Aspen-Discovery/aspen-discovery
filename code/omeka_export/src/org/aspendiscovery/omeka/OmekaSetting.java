package org.aspendiscovery.omeka;

import java.sql.ResultSet;
import java.sql.SQLException;

public class OmekaSetting {
	private final long id;
	private final String name;
	private final String baseUrl;
	private final boolean classic;
	private final String apiKeyIdentity;
	private final String apiKeyCredential;
	private final boolean doFullReload;
	private final long lastUpdateOfChangedRecords;
	private final long lastUpdateOfAllRecords;

	OmekaSetting(ResultSet settingsRS) throws SQLException {
		id = settingsRS.getLong("id");
		name = settingsRS.getString("name");
		baseUrl = trimTrailingSlash(settingsRS.getString("baseUrl"));
		classic = "classic".equals(settingsRS.getString("apiVersion"));
		apiKeyIdentity = settingsRS.getString("apiKeyIdentity");
		apiKeyCredential = settingsRS.getString("apiKeyCredential");
		doFullReload = settingsRS.getBoolean("runFullUpdate");
		lastUpdateOfChangedRecords = settingsRS.getLong("lastUpdateOfChangedRecords");
		lastUpdateOfAllRecords = settingsRS.getLong("lastUpdateOfAllRecords");
	}

	private static String trimTrailingSlash(String url) {
		if (url == null) {
			return null;
		}
		if (url.endsWith("/")) {
			return url.substring(0, url.length() - 1);
		}
		return url;
	}

	long getId() {
		return id;
	}

	String getName() {
		return name;
	}

	String getBaseUrl() {
		return baseUrl;
	}

	boolean doFullReload() {
		return doFullReload;
	}

	long getLastUpdateOfChangedRecords() {
		return Math.max(lastUpdateOfChangedRecords, lastUpdateOfAllRecords);
	}

	long getLastUpdateOfAllRecords() {
		return lastUpdateOfAllRecords;
	}

	boolean isClassic() {
		return classic;
	}

	boolean hasApiKey() {
		boolean hasCredential = apiKeyCredential != null && !apiKeyCredential.isEmpty();
		if (classic) {
			return hasCredential;
		}
		return apiKeyIdentity != null && !apiKeyIdentity.isEmpty() && hasCredential;
	}

	String getApiKeyIdentity() {
		return apiKeyIdentity;
	}

	String getApiKeyCredential() {
		return apiKeyCredential;
	}
}
