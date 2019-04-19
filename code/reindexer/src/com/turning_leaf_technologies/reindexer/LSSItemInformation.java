package com.turning_leaf_technologies.reindexer;

import java.util.Date;

class LSSItemInformation {
	private String resourceId;
	private String itemBarcode;
	private String holdingsCode;
	private String itemStatus;
	private String controlNumber;
	private int totalCirculations;
	private int checkoutsThisYear;
	private Date dateAddedToSystem;

	@SuppressWarnings("unused")
	String getResourceId() {
		return resourceId;
	}

	void setResourceId(String resourceId) {
		this.resourceId = resourceId;
	}

	String getItemBarcode() {
		return itemBarcode;
	}

	void setItemBarcode(String itemBarcode) {
		this.itemBarcode = itemBarcode;
	}

	@SuppressWarnings("unused")
	String getHoldingsCode() {
		return holdingsCode;
	}

	void setHoldingsCode(String holdingsCode) {
		this.holdingsCode = holdingsCode;
	}

	String getItemStatus() {
		return itemStatus;
	}

	void setItemStatus(String itemStatus) {
		this.itemStatus = itemStatus;
	}

	@SuppressWarnings("unused")
	String getControlNumber() {
		return controlNumber;
	}

	void setControlNumber(String controlNumber) {
		this.controlNumber = controlNumber;
	}

	int getTotalCirculations() {
		return totalCirculations;
	}

	void setTotalCirculations(int totalCirculations) {
		this.totalCirculations = totalCirculations;
	}

	int getCheckoutsThisYear() {
		return checkoutsThisYear;
	}

	void setCheckoutsThisYear(int checkoutsThisYear) {
		this.checkoutsThisYear = checkoutsThisYear;
	}

	Date getDateAddedToSystem() {
		return dateAddedToSystem;
	}

	void setDateAddedToSystem(Date dateAddedToSystem) {
		this.dateAddedToSystem = dateAddedToSystem;
	}
}
