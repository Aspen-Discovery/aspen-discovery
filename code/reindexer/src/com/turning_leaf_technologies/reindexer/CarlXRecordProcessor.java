package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
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
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
		//Add variations of the identifier
		String shortIdentifier = identifier.replace("CARL", "");
		groupedWork.addAlternateId(shortIdentifier);
		shortIdentifier = shortIdentifier.replaceFirst("^0+", "");
		groupedWork.addAlternateId(shortIdentifier);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		String groupedStatus = getDisplayGroupedStatus(itemInfo, itemInfo.getFullRecordIdentifier());
		return groupedStatus.equals("On Shelf") || groupedStatus.equals("Library Use Only");
	}

	@Override
	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String statusCode = getItemSubfieldData(statusSubfieldIndicator, itemField);
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
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		String location = translateValue("location", locationCode, identifier);
		String shelvingLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelvingLocation != null && !shelvingLocation.equals(locationCode)){
			if (location == null){
				location = translateValue("shelf_location", shelvingLocation, identifier);
			}else {
				location += " - " + translateValue("shelf_location", shelvingLocation, identifier);
			}
		}
		return location;
	}

	private static int numSampleRecordsWithMultiplePrintFormats = 0;
	@Override
	public void loadPrintFormatInformation(RecordInfo ilsRecord, Record record) {
		List<DataField> items = MarcUtil.getDataFields(record, itemTag);
		boolean allItemsAreOrderRecords = true;
		HashMap<String, Integer> printFormats = new HashMap<>();
		for (DataField curItem : items){
			Subfield shelfLocationField = curItem.getSubfield(shelvingLocationSubfield);
			boolean hasLocationBasedFormat = false;
			if (shelfLocationField != null){
				String shelfLocation = shelfLocationField.getData().toLowerCase();
				if (!shelfLocation.equals("xord")) {
					allItemsAreOrderRecords = false;
				}
				String printFormatLower = null;
				if (shelfLocation.endsWith("ltp")){
					printFormatLower = "largeprint";
					hasLocationBasedFormat = true;
				}else if (shelfLocation.endsWith("board")){
					printFormatLower = "board";
					hasLocationBasedFormat = true;
				}
				if (hasLocationBasedFormat) {
					if (!printFormats.containsKey(printFormatLower)) {
						printFormats.put(printFormatLower, 1);
					} else {
						printFormats.put(printFormatLower, printFormats.get(printFormatLower) + 1);
					}
				}
			}else{
				allItemsAreOrderRecords = false;
			}
			if (!hasLocationBasedFormat){
				Subfield formatField = curItem.getSubfield(formatSubfield);
				if (formatField != null) {
					String curFormat = formatField.getData();
					String printFormatLower = curFormat.toLowerCase();
					if (!printFormats.containsKey(printFormatLower)) {
						printFormats.put(printFormatLower, 1);
					} else {
						printFormats.put(printFormatLower, printFormats.get(printFormatLower) + 1);
					}
					if (!printFormatLower.equals("bk") && !printFormatLower.equals("oth") && !printFormatLower.equals("ord")){
						allItemsAreOrderRecords = false;
					}
				}else{
					allItemsAreOrderRecords = false;
				}
			}
		}

		if (allItemsAreOrderRecords){
			super.loadPrintFormatInformation(ilsRecord, record);
			return;
		}

		HashSet<String> selectedPrintFormats = new HashSet<>();
		if (printFormats.size() > 1 && numSampleRecordsWithMultiplePrintFormats < 100){
			logger.info("Record " + ilsRecord.getRecordIdentifier() + " had multiple formats based on the item information");
			numSampleRecordsWithMultiplePrintFormats++;
		}
		int maxPrintFormats = 0;
		String selectedFormat = "";
		if (printFormats.size() > 1) {
			for (String printFormat : printFormats.keySet()) {
				int numUsages = printFormats.get(printFormat);
				logger.info("  " + printFormat + " used " + numUsages + " times");
				if (numUsages > maxPrintFormats) {
					if (selectedFormat.length() > 0) {
						logger.info("Record " + ilsRecord.getRecordIdentifier() + " " + printFormat + " has more usages (" + numUsages + ") than " + selectedFormat + " (" + maxPrintFormats + ")");
					}
					selectedFormat = printFormat;
					maxPrintFormats = numUsages;
				}
			}
			logger.info("  Selected Format is " + selectedFormat);
		}else if (printFormats.size() == 1) {
			selectedFormat = printFormats.keySet().iterator().next();
		}else{
			//format not found based on item records.
			//TODO Fall back to default method?
			selectedFormat = "On Order";
		}
		selectedPrintFormats.add(selectedFormat);

		HashSet<String> translatedFormats = translateCollection("format", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		HashSet<String> translatedFormatCategories = translateCollection("format_category", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		ilsRecord.addFormats(translatedFormats);
		ilsRecord.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		for (String tmpFormatBoost : formatBoosts){
			if (StringUtils.isNumeric(tmpFormatBoost)) {
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			}
		}
		ilsRecord.setFormatBoost(formatBoost);
	}

	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//For Nashville CARL.X, load audiences based on location code rather than based on the 008 and 006 fields
		HashSet<String> targetAudiences = new HashSet<>();
		for (ItemInfo printItem : printItems){
			String location = printItem.getShelfLocationCode();
			if (location != null) {
				//Get the first character from the location
				if (location.length() > 0){
					targetAudiences.add(location.substring(0, 1));
				}
			}
		}

		HashSet<String> translatedAudiences = translateCollection("target_audience", targetAudiences, identifier);
		groupedWork.addTargetAudiences(translatedAudiences);
		groupedWork.addTargetAudiencesFull(translatedAudiences);
	}

	ItemInfo createPrintIlsItem(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField) {
		ItemInfo item = super.createPrintIlsItem(groupedWork, recordInfo, record, itemField);
		if (item != null){
			Subfield shelfLocationField = itemField.getSubfield(shelvingLocationSubfield);
			if (shelfLocationField != null) {
				String shelfLocation = shelfLocationField.getData().toLowerCase();
				if (shelfLocation.equals("xord")) {
					item.setIsOrderItem();
				}
			}
		}
		return item;
	}
}
