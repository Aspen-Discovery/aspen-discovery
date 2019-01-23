package org.marmot.pika;

/**
 * Information about an item that has changed within Sierra
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/13/2014
 * Time: 9:47 AM
 */
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


	public String getItemId() {
		return itemId;
	}

	public void setItemId(String itemId) {
		this.itemId = itemId;
	}

	public String getBID() {
		return BID;
	}

	public void setBID(String BID) {
		this.BID = BID;
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

	private String value; // Delete This; for debugging
	public void setMe(String value) {
		this.value = value;
	}

	public String getCallNumber() {
		return callNumber;
	}

	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}

	public String getTotalCheckouts() {
		return totalCheckouts;
	}

	public void setTotalCheckouts(String totalCheckouts) {
		this.totalCheckouts = totalCheckouts;
	}

	public String getDateCreated() {
		return dateCreated;
	}

	public void setDateCreated(String dateCreated) {
		this.dateCreated = dateCreated;
	}

	public String getYearToDateCheckouts() {
		return yearToDateCheckouts;
	}

	public void setYearToDateCheckouts(String yearToDateCheckouts) {
		this.yearToDateCheckouts = yearToDateCheckouts;
	}

	public String getShelvingLocation() {
		return shelvingLocation;
	}

	public void setShelvingLocation(String shelvingLocation) {
		this.shelvingLocation = shelvingLocation;
	}

	public String getiType() {
		return iType;
	}

	public void setiType(String iType) {
		this.iType = iType;
	}

	public void setSuppress(String suppress) {
		this.suppress = suppress.equalsIgnoreCase("true");
	}

	public boolean isSuppressed(){
		return this.suppress;
	}

	public String toString(){
		StringBuilder aboutMe = new StringBuilder();
		aboutMe.append("Item ID: ").append(itemId).append("\r\n");
		aboutMe.append("BID: ").append(BID).append("\r\n");
		aboutMe.append("location: ").append(location).append("\r\n");
		aboutMe.append("status: ").append(status).append("\r\n");
		aboutMe.append("dueDate: ").append(dueDate).append("\r\n");
		aboutMe.append("lastCheckinDate: ").append(lastCheckinDate).append("\r\n");
		aboutMe.append("dateCreated: ").append(dateCreated).append("\r\n");
		aboutMe.append("callNumber: ").append(callNumber).append("\r\n");
		aboutMe.append("totalCheckouts: ").append(totalCheckouts).append("\r\n");
		aboutMe.append("yearToDateCheckouts: ").append(yearToDateCheckouts).append("\r\n");
		aboutMe.append("shelvingLocation: ").append(shelvingLocation).append("\r\n");
		aboutMe.append("iType: ").append(iType).append("\r\n");
		aboutMe.append("suppress: ").append(suppress).append("\r\n");
		return aboutMe.toString();
	}
}
