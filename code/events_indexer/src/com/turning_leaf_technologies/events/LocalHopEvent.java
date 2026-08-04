package com.turning_leaf_technologies.events;

import java.sql.ResultSet;
import java.sql.SQLException;

class LocalHopEvent {
	private final long id;
	private final String externalId;

	LocalHopEvent(ResultSet existingEventsRS) throws SQLException{
		this.id = existingEventsRS.getLong("id");
		this.externalId = existingEventsRS.getString("externalId");
	}

	long getId() {
		return id;
	}

	String getExternalId() {
		return externalId;
	}
}
