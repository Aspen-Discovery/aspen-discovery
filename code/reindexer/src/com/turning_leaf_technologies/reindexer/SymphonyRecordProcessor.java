package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashSet;
import java.util.Locale;

class SymphonyRecordProcessor extends IlsRecordProcessor {
	SymphonyRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
		this.suppressRecordsWithNoCollection = false;
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes) {
		return super.isItemSuppressed(curItem, itemIdentifier, suppressionNotes, false);
	}

	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String statusFieldData = getItemSubfieldData(statusSubfieldIndicator, itemField);
		String shelfLocationData = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelfLocationData != null){
			shelfLocationData = shelfLocationData.toLowerCase();
		}else{
			shelfLocationData = "";
		}
		if (shelfLocationData.equalsIgnoreCase("Z-ON-ORDER") || shelfLocationData.equalsIgnoreCase("ON-ORDER") || shelfLocationData.equalsIgnoreCase("ONORDER")) {
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
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		boolean available = false;
		if (itemInfo.getStatusCode().equals("ONSHELF")) {
			available = true;
		}else {
			if (groupedStatus.equals("On Shelf") || (treatLibraryUseOnlyGroupedStatusesAsAvailable && groupedStatus.equals("Library Use Only"))){
				available = true;
			}
		}
		return available;
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		String location = translateValue("location", locationCode, identifier, true);

		String subLocationCode = getItemSubfieldData(subLocationSubfield, itemField);
		if (subLocationCode != null && subLocationCode.length() > 0){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && translatedSubLocation.length() > 0) {
				if (location.length() > 0) {
					location += " - ";
				}
				location += translateValue("sub_location", subLocationCode, identifier, true);
			}
		}

		String status = getItemSubfieldData(statusSubfieldIndicator, itemField);
		if (status == null || status.equals("CHECKEDOUT") || status.equals("HOLDS") || status.equals("INTRANSIT")) {
			String shelvingLocation = itemInfo.getShelfLocationCode();
			if (location == null) {
				location = translateValue("shelf_location", shelvingLocation, identifier, true);
			} else {
				location += " - " + translateValue("shelf_location", shelvingLocation, identifier, true);
			}
		}else {
			//In this case, the status is the current location of the item.
			if (location == null) {
				location = translateValue("shelf_location", status, identifier, true);
			} else {
				location += " - " + translateValue("shelf_location", status, identifier, true);
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

	protected void loadOnOrderItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		//On Order items for Symphony are currently handled with item records with On Order status
	}
}
