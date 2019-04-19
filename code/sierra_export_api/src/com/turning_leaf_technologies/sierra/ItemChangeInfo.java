package com.turning_leaf_technologies.sierra;

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
