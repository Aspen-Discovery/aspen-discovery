package com.turning_leaf_technologies.events;

import java.sql.ResultSet;
import java.sql.SQLException;

class LibraryCalendarEvent {
	private long id;
	private long settingsId;
	private String externalId;
	private String title;
	private long rawChecksum;
	private boolean deleted;

	LibraryCalendarEvent(ResultSet existingEventsRS) throws SQLException{
		this.id = existingEventsRS.getLong("id");
		this.settingsId = existingEventsRS.getLong("settingsId");
		this.externalId = existingEventsRS.getString("externalId");
		this.title = existingEventsRS.getString("title");
		this.rawChecksum = existingEventsRS.getLong("rawChecksum");
		this.deleted = existingEventsRS.getBoolean("deleted");
	}

	long getId() {
		return id;
	}

	long getSettingsId() {
		return settingsId;
	}

	String getExternalId() {
		return externalId;
	}

	String getTitle() {
		return title;
	}

	long getRawChecksum() {
		return rawChecksum;
	}

	boolean isDeleted() {
		return deleted;
	}
}
