package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;

class CarlXRecordProcessor extends IlsRecordProcessor {
	CarlXRecordProcessor(GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
		//Add variations of the identifier
		String shortIdentifier = identifier.replace("CARL", "");
		groupedWork.addAlternateId(shortIdentifier);
		shortIdentifier = shortIdentifier.replaceFirst("^0+", "");
		groupedWork.addAlternateId(shortIdentifier);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return groupedStatus.equals("On Shelf") || (settings.getTreatLibraryUseOnlyGroupedStatusesAsAvailable() && groupedStatus.equals("Library Use Only"));
	}

	@Override
	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String statusCode = getItemSubfieldData(settings.getItemStatusSubfield(), itemField);
		if (statusCode.length() > 2){
			statusCode = translateValue("status_codes", statusCode, recordIdentifier);
		}
		return statusCode;
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String locationCode = getItemSubfieldData(settings.getLocationSubfield(), itemField);
		String location = translateValue("location", locationCode, identifier, true);
		String shelvingLocation = getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField);
		if (shelvingLocation != null && !shelvingLocation.equals(locationCode)){
			if (location == null){
				location = translateValue("shelf_location", shelvingLocation, identifier, true);
			}else {
				location += " - " + translateValue("shelf_location", shelvingLocation, identifier, true);
			}
		}
		return location;
	}

	ItemInfoWithNotes createPrintIlsItem(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField, StringBuilder suppressionNotes) {
		ItemInfoWithNotes item = super.createPrintIlsItem(groupedWork, recordInfo, record, itemField, suppressionNotes);
		if (item.itemInfo != null){
			Subfield shelfLocationField = itemField.getSubfield(settings.getShelvingLocationSubfield());
			if (shelfLocationField != null) {
				String shelfLocation = shelfLocationField.getData().toLowerCase();
				//noinspection SpellCheckingInspection
				if (shelfLocation.equals("xord")) {
					item.itemInfo.setIsOrderItem();
				}
			}
		}
		return item;
	}
}
