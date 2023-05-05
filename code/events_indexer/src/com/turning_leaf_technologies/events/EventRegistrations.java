package com.turning_leaf_technologies.events;

import java.sql.ResultSet;
import java.sql.SQLException;

class EventRegistrations {
	private long id;
	private long userId;
	private String userBarcode;
	private String sourceId;
	private long waitlist;

	EventRegistrations(ResultSet existingRegistrationsRS) throws SQLException{
		this.id = existingRegistrationsRS.getLong("id");
		this.userId = existingRegistrationsRS.getLong("userId");
		this.userBarcode = existingRegistrationsRS.getString("userBarcode");
		this.sourceId = existingRegistrationsRS.getString("sourceId");
		this.waitlist = existingRegistrationsRS.getLong("waitlist");
	}

	long getId() {
		return id;
	}

	long getUserId() {
		return userId;
	}

	String getUserBarcode() {
		return userBarcode;
	}

	String getSourceId() {
		return sourceId;
	}

	long getWaitlist() {
		return waitlist;
	}
}
