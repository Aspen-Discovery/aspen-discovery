package org.vufind;

import java.util.regex.Pattern;

/**
 * Stores information about time to reshelve to override status for items
 * Pika
 * User: Mark Noble
 * Date: 1/28/2016
 * Time: 9:33 PM
 */
public class TimeToReshelve {
	private String locations;
	private Pattern locationsPattern;
	private long numHoursToOverride;
	private String status;
	private String groupedStatus;

	public void setLocations(String locations) {
		this.locations = locations;
		locationsPattern = Pattern.compile(locations);
	}

	public Pattern getLocationsPattern() {
		return locationsPattern;
	}

	public long getNumHoursToOverride() {
		return numHoursToOverride;
	}

	public void setNumHoursToOverride(long numHoursToOverride) {
		this.numHoursToOverride = numHoursToOverride;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getGroupedStatus() {
		return groupedStatus;
	}

	public void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
	}
}
