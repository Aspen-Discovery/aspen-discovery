package com.turning_leaf_technologies.events;

import java.sql.ResultSet;
import java.sql.SQLException;

class EventRegistrations {
	private final long id;
	private final long userId;
	private final String sourceId;

	EventRegistrations(ResultSet existingRegistrationsRS) throws SQLException{
		this.id = existingRegistrationsRS.getLong("id");
		this.userId = existingRegistrationsRS.getLong("userId");
		this.sourceId = existingRegistrationsRS.getString("sourceId");
	}

	long getId() {
		return id;
	}

	long getUserId() {
		return userId;
	}

	String getSourceId() {
		return sourceId;
	}
}
