package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashSet;
import java.util.Locale;

class SymphonyRecordProcessor extends IlsRecordProcessor {
	private HashSet<String> bibsWithOrders = new HashSet<>();
	SymphonyRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			//For Symphony, the status is blank if the item is on shelf
			if (statusSubfield != null) {
				if (statusesToSuppressPattern != null && statusesToSuppressPattern.matcher(statusSubfield.getData()).matches()) {
					return true;
				}
			}
		}
		Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationSubfield == null){
			return true;
		}else{
			if (locationsToSuppressPattern != null && locationsToSuppressPattern.matcher(locationSubfield.getData().trim()).matches()){
				return true;
			}
		}
		if (collectionSubfield != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(collectionSubfield);
			if (collectionSubfieldValue != null){
				return collectionsToSuppressPattern != null && collectionsToSuppressPattern.matcher(collectionSubfieldValue.getData().trim()).matches();
			}
		}
		return false;
	}

	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String statusFieldData = getItemSubfieldData(statusSubfieldIndicator, itemField);
		String shelfLocationData = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelfLocationData != null){
			shelfLocationData = shelfLocationData.toLowerCase();
		}else{
			shelfLocationData = "";
		}
		if (shelfLocationData.equalsIgnoreCase("Z-ON-ORDER") || shelfLocationData.equalsIgnoreCase("ON-ORDER")) {
			statusFieldData = "On Order";
		}else {
			if (statusFieldData == null) {
				if (hasTranslation("item_status", shelfLocationData)){
					//We are treating the shelf location as a status i.e. DISPLAY
					statusFieldData = shelfLocationData;
				}else{
					statusFieldData = "ONSHELF";
				}
			}else{
				statusFieldData = statusFieldData.toLowerCase();
				if (hasTranslation("item_status", statusFieldData)){
					//The status is provided and is in the translation table so we use the status
					statusFieldData = statusFieldData;
				}else {
					if (!shelfLocationData.equalsIgnoreCase(statusFieldData)) {
						statusFieldData = "Checked Out";
					}else{
						statusFieldData = "ONSHELF";
					}
				}
			}
		}
		return statusFieldData;
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		if (itemInfo.getStatusCode().equals("ONSHELF")) {
			available = true;
		}else if (this.getDisplayGroupedStatus(itemInfo, itemInfo.getFullRecordIdentifier()).equals("On Shelf")){
			available = true;
		}
		return available;
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		String location = translateValue("location", locationCode, identifier);

		String status = getItemSubfieldData(statusSubfieldIndicator, itemField);
		if (status == null || status.equals("CHECKEDOUT") || status.equals("HOLDS") || status.equals("INTRANSIT")) {
			String shelvingLocation = itemInfo.getShelfLocationCode();
			if (location == null) {
				location = translateValue("shelf_location", shelvingLocation, identifier);
			} else {
				location += " - " + translateValue("shelf_location", shelvingLocation, identifier);
			}
		}else {
			//In this case, the status is the current location of the item.
			if (location == null) {
				location = translateValue("shelf_location", status, identifier);
			} else {
				location += " - " + translateValue("shelf_location", status, identifier);
			}
		}
		return location;
	}

	protected void setShelfLocationCode(DataField itemField, ItemInfo itemInfo, String recordIdentifier) {
		//For Symphony the status field holds the location code unless it is currently checked out, on display, etc.
		//In that case the location code holds the permanent location
		String subfieldData = getItemSubfieldData(statusSubfieldIndicator, itemField);
		boolean loadFromPermanentLocation = false;
		if (subfieldData == null){
			loadFromPermanentLocation = true;
		}else if (translateValue("item_status", subfieldData, recordIdentifier, false) != null){
			loadFromPermanentLocation = true;
		}
		if (loadFromPermanentLocation){
			subfieldData = getItemSubfieldData(shelvingLocationSubfield, itemField);
		}
		itemInfo.setShelfLocationCode(subfieldData);
	}

	protected void loadOnOrderItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		if (bibsWithOrders.contains(recordInfo.getRecordIdentifier())){
			if (recordInfo.getNumPrintCopies() == 0 && recordInfo.getNumCopiesOnOrder() == 0) {
				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setLocationCode("aacpl");
				itemInfo.setItemIdentifier(recordInfo.getRecordIdentifier());
				itemInfo.setNumCopies(1);
				itemInfo.setIsEContent(false);
				itemInfo.setIsOrderItem();
				itemInfo.setCallNumber("ON ORDER");
				itemInfo.setSortableCallNumber("ON ORDER");
				itemInfo.setDetailedStatus("On Order");
				Date tomorrow = new Date();
				tomorrow.setTime(tomorrow.getTime() + 1000 * 60 * 60 * 24);
				itemInfo.setDateAdded(tomorrow);
				//Format and Format Category should be set at the record level, so we don't need to set them here.

				//String formatByShelfLocation = translateValue("shelf_location_to_format", bibsWithOrders.get(recordInfo.getRecordIdentifier()), recordInfo.getRecordIdentifier());
				//itemInfo.setFormat(translateValue("format", formatByShelfLocation, recordInfo.getRecordIdentifier()));
				//itemInfo.setFormatCategory(translateValue("format_category", formatByShelfLocation, recordInfo.getRecordIdentifier()));
				itemInfo.setFormat("On Order");
				itemInfo.setFormatCategory("");

				//Add the library this is on order for
				itemInfo.setShelfLocation("On Order");
				itemInfo.setDetailedLocation("On Order");

				recordInfo.addItem(itemInfo);
			}else{
				logger.debug("Skipping order item because there are print or order records available");
			}
		}
	}
}
