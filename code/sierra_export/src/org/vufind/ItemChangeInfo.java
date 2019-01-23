package org.vufind;

/**
 * Information about an item that has changed within Sierra
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/13/2014
 * Time: 9:47 AM
 */
public class ItemChangeInfo {
	private String itemId;
	private String location;
	private String status;
	private String dueDate;
	private String lastCheckinDate;

	public String getItemId() {
		return itemId;
	}

	public void setItemId(String itemId) {
		this.itemId = itemId;
	}

	public String getLocation() {
		return location;
	}

	public void setLocation(String location) {
		this.location = location;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getDueDate() {
		return dueDate;
	}

	public void setDueDate(String dueDate) {
		this.dueDate = dueDate;
	}

	public String getLastCheckinDate() {
		return lastCheckinDate;
	}

	public void setLastCheckinDate(String lastCheckinDate) {
		this.lastCheckinDate = lastCheckinDate;
	}
}
