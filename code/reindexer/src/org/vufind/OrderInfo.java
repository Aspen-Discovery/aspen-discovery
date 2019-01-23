package org.vufind;

class OrderInfo {
	private String orderRecordId;
	private String status;
	private String locationCode;
	private int numCopies;
	String getOrderRecordId() {
		return orderRecordId;
	}
	void setOrderRecordId(String orderRecordId) {
		this.orderRecordId = orderRecordId;
	}
	
	public String getStatus() {
		return status;
	}
	public void setStatus(String status) {
		this.status = status;
	}
	public String getLocationCode() {
		return locationCode;
	}
	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}

	int getNumCopies() {
		return numCopies;
	}

	void setNumCopies(int numCopies) {
		this.numCopies = numCopies;
	}
}
