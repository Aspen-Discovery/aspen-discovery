package com.turning_leaf_technologies.carlx;

public class ItemChangeInfo {
	private String itemId;
	private String BID;
	private String location;
	private String status;
	private String dueDate;
	private String lastCheckinDate;
	private String dateCreated;
	private String callNumber;
	private String totalCheckouts;
	private String yearToDateCheckouts;
	private String shelvingLocation;
	private String iType;
	private boolean suppress;
	private String notes;


	String getItemId() {
		return itemId;
	}

	void setItemId(String itemId) {
		this.itemId = itemId;
	}

	String getBID() {
		return BID;
	}

	void setBID(String BID) {
		this.BID = BID;
	}

	String getLocation() {
		return location;
	}

	void setLocation(String location) {
		this.location = location;
	}

	String getStatus() {
		return status;
	}

	void setStatus(String status) {
		this.status = status;
	}

	String getDueDate() {
		return dueDate;
	}

	void setDueDate(String dueDate) {
		this.dueDate = dueDate;
	}

	String getLastCheckinDate() {
		return lastCheckinDate;
	}

	void setLastCheckinDate(String lastCheckinDate) {
		this.lastCheckinDate = lastCheckinDate;
	}

	String getCallNumber() {
		return callNumber;
	}

	void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}

	String getTotalCheckouts() {
		return totalCheckouts;
	}

	void setTotalCheckouts(String totalCheckouts) {
		this.totalCheckouts = totalCheckouts;
	}

	String getDateCreated() {
		return dateCreated;
	}

	void setDateCreated(String dateCreated) {
		this.dateCreated = dateCreated;
	}

	String getYearToDateCheckouts() {
		return yearToDateCheckouts;
	}

	void setYearToDateCheckouts(String yearToDateCheckouts) {
		this.yearToDateCheckouts = yearToDateCheckouts;
	}

	String getShelvingLocation() {
		return shelvingLocation;
	}

	void setShelvingLocation(String shelvingLocation) {
		this.shelvingLocation = shelvingLocation;
	}

	String getiType() {
		return iType;
	}

	void setiType(String iType) {
		this.iType = iType;
	}

	void setSuppress(String suppress) {
		this.suppress = suppress.equalsIgnoreCase("true");
	}

	boolean isSuppressed(){
		return this.suppress;
	}

	public String toString(){
		return "Item ID: " + itemId + "\r\n" +
				"BID: " + BID + "\r\n" +
				"location: " + location + "\r\n" +
				"status: " + status + "\r\n" +
				"dueDate: " + dueDate + "\r\n" +
				"lastCheckinDate: " + lastCheckinDate + "\r\n" +
				"dateCreated: " + dateCreated + "\r\n" +
				"callNumber: " + callNumber + "\r\n" +
				"totalCheckouts: " + totalCheckouts + "\r\n" +
				"yearToDateCheckouts: " + yearToDateCheckouts + "\r\n" +
				"shelvingLocation: " + shelvingLocation + "\r\n" +
				"iType: " + iType + "\r\n" +
				"suppress: " + suppress + "\r\n";
	}

	public void setNotes(String notes) {
		this.notes = notes;
	}

	public String getNotes() {
		return notes;
	}
}
