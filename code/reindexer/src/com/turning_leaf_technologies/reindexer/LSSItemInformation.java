package com.turning_leaf_technologies.reindexer;

import org.marc4j.marc.DataField;

import java.util.Date;

/**
 * Contains supplemental information about items from Library.Solutions for Schools that is not included in the
 * MARC export
 *
 * Pika
 * User: Mark Noble
 * Date: 7/24/2015
 * Time: 10:35 PM
 */
public class LSSItemInformation {
	private String resourceId;
	private String itemBarcode;
	private String holdingsCode;
	private String itemStatus;
	private String controlNumber;
	private int totalCirculations;
	private int checkoutsThisYear;
	private Date dateAddedToSystem;

	public String getResourceId() {
		return resourceId;
	}

	public void setResourceId(String resourceId) {
		this.resourceId = resourceId;
	}

	public String getItemBarcode() {
		return itemBarcode;
	}

	public void setItemBarcode(String itemBarcode) {
		this.itemBarcode = itemBarcode;
	}

	public String getHoldingsCode() {
		return holdingsCode;
	}

	public void setHoldingsCode(String holdingsCode) {
		this.holdingsCode = holdingsCode;
	}

	public String getItemStatus() {
		return itemStatus;
	}

	public void setItemStatus(String itemStatus) {
		this.itemStatus = itemStatus;
	}

	public String getControlNumber() {
		return controlNumber;
	}

	public void setControlNumber(String controlNumber) {
		this.controlNumber = controlNumber;
	}

	public int getTotalCirculations() {
		return totalCirculations;
	}

	public void setTotalCirculations(int totalCirculations) {
		this.totalCirculations = totalCirculations;
	}

	public int getCheckoutsThisYear() {
		return checkoutsThisYear;
	}

	public void setCheckoutsThisYear(int checkoutsThisYear) {
		this.checkoutsThisYear = checkoutsThisYear;
	}

	public Date getDateAddedToSystem() {
		return dateAddedToSystem;
	}

	public void setDateAddedToSystem(Date dateAddedToSystem) {
		this.dateAddedToSystem = dateAddedToSystem;
	}
}
