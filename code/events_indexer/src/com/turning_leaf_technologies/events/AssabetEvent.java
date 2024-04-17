package com.turning_leaf_technologies.events;

import java.sql.ResultSet;
import java.sql.SQLException;

class AssabetEvent {
	private final long id;
	private final String externalId;

	AssabetEvent(ResultSet existingEventsRS) throws SQLException{
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
