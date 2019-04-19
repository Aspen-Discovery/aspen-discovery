package com.turning_leaf_technologies.reindexer;

import java.util.regex.Pattern;

public class TimeToReshelve {
	private Pattern locationsPattern;
	private long numHoursToOverride;
	private String status;
	private String groupedStatus;

	void setLocations(String locations) {
		locationsPattern = Pattern.compile(locations);
	}

	Pattern getLocationsPattern() {
		return locationsPattern;
	}

	long getNumHoursToOverride() {
		return numHoursToOverride;
	}

	void setNumHoursToOverride(long numHoursToOverride) {
		this.numHoursToOverride = numHoursToOverride;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	String getGroupedStatus() {
		return groupedStatus;
	}

	void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
	}
}
