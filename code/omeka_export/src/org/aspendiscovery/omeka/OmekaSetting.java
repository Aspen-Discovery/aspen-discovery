package org.aspendiscovery.omeka;

import java.sql.ResultSet;
import java.sql.SQLException;

public class OmekaSetting {
	private final long id;
	private final String name;
	private final String baseUrl;
	private final String apiKeyIdentity;
	private final String apiKeyCredential;
	private final boolean doFullReload;

	OmekaSetting(ResultSet settingsRS) throws SQLException {
		id = settingsRS.getLong("id");
		name = settingsRS.getString("name");
		baseUrl = trimTrailingSlash(settingsRS.getString("baseUrl"));
		apiKeyIdentity = settingsRS.getString("apiKeyIdentity");
		apiKeyCredential = settingsRS.getString("apiKeyCredential");
		doFullReload = settingsRS.getBoolean("runFullUpdate");
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

	boolean hasApiKey() {
		return apiKeyIdentity != null && !apiKeyIdentity.isEmpty() && apiKeyCredential != null && !apiKeyCredential.isEmpty();
	}

	String getApiKeyIdentity() {
		return apiKeyIdentity;
	}

	String getApiKeyCredential() {
		return apiKeyCredential;
	}
}
