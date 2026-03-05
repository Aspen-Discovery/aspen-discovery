package com.turning_leaf_technologies.cloud_library;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Calendar;
import java.util.GregorianCalendar;

public class CloudLibrarySettings {
	private final long settingsId;
	private final String baseUrl;
	private final String accountId;
	private final String accountKey;
	private final String libraryId;
	private final boolean doFullReload;
	private final long lastExtractTime;
	private final long lastExtractTimeAll;
	private final boolean reindexOnSunday;
	private final boolean shouldRunSundayReindex;


	public CloudLibrarySettings(ResultSet getSettingsRS) throws SQLException {
		settingsId = getSettingsRS.getLong("id");

		baseUrl = getSettingsRS.getString("apiUrl");
		accountId = getSettingsRS.getString("accountId");
		accountKey = getSettingsRS.getString("accountKey");
		libraryId = getSettingsRS.getString("libraryId");

		doFullReload = getSettingsRS.getBoolean("runFullUpdate");
		lastExtractTime = getSettingsRS.getLong("lastUpdateOfChangedRecords");
		lastExtractTimeAll = getSettingsRS.getLong("lastUpdateOfAllRecords");
		reindexOnSunday = getSettingsRS.getBoolean("reindexOnSunday");

		// get the current day of the week and time to determine if we should reindex on Sunday at 8PM
		Calendar calendar = new GregorianCalendar();
		int dayOfWeek = calendar.get(Calendar.DAY_OF_WEEK);
		int hourOfDay = calendar.get(Calendar.HOUR_OF_DAY);
		boolean isSunday = dayOfWeek == Calendar.SUNDAY && hourOfDay >= 20;

		// we only want to reindex on Sunday if lastExtractTimeAll was more than 24 hours ago
		shouldRunSundayReindex = reindexOnSunday && isSunday && (System.currentTimeMillis() - lastExtractTimeAll > 24 * 60 * 60 * 1000);

	}

	public long getSettingsId() {
		return settingsId;
	}

	public String getBaseUrl() {
		return baseUrl;
	}

	public String getAccountId() {
		return accountId;
	}

	public String getAccountKey() {
		return accountKey;
	}

	public String getLibraryId() {
		return libraryId;
	}

	public boolean isDoFullReload() {
		return doFullReload;
	}

	public long getLastExtractTime() {
		return lastExtractTime;
	}

	public long getLastExtractTimeAll() {
		return lastExtractTimeAll;
	}

	public boolean isReindexOnSunday() {
		return reindexOnSunday;
	}

	public boolean shouldRunSundayReindex() {
		return shouldRunSundayReindex;
	}
}
