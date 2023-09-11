package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.*;

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

	/*private static Date yesterday = null;
	private static Date lostDay = null;
	String getOverriddenStatus(ItemInfo itemInfo, boolean groupedStatus) {
		if (lostDay == null){
			Calendar lostDayCal = GregorianCalendar.getInstance();
			lostDayCal.roll(Calendar.DATE, -32);
			lostDay = lostDayCal.getTime();
		}
		if (yesterday == null){
			Calendar yesterdayCal = GregorianCalendar.getInstance();
			yesterdayCal.roll(Calendar.DATE, -1);
			yesterday = yesterdayCal.getTime();
		}
		String overriddenStatus = super.getOverriddenStatus(itemInfo, groupedStatus);
		String statusToTest = overriddenStatus == null ? itemInfo.getStatusCode() : overriddenStatus;
		if (statusToTest.equals("C")) {
			//Depending on due date this could be checked out, overdue or lost
			String dueDateStr = itemInfo.getDueDate();
			try {
				Date dueDate = dueDateFormatter.parse(dueDateStr);
				if (dueDate.before(lostDay)) {
					return "Lost";
				} else if (dueDate.before(yesterday)) {
					return "Overdue";
				}
			} catch (Exception e) {
				logger.warn("Error parsing due date", e);
			}
		}
		return overriddenStatus;
	}*/

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
				if (shelfLocation.equals("xord")) {
					item.itemInfo.setIsOrderItem();
				}
			}
		}
		return item;
	}
}
