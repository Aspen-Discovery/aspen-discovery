package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

public class NashvilleCarlXRecordProcessor extends CarlXRecordProcessor{
	NashvilleCarlXRecordProcessor(GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	private static int numSampleRecordsWithMultiplePrintFormats = 0;
	@Override
	public void loadPrintFormatInformation(RecordInfo ilsRecord, Record record, boolean hasChildRecords) {
		List<DataField> items = MarcUtil.getDataFields(record, settings.getItemTagInt());
		boolean allItemsAreOrderRecords = true;
		HashMap<String, Integer> printFormats = new HashMap<>();
		for (DataField curItem : items){
			Subfield shelfLocationField = curItem.getSubfield(settings.getShelvingLocationSubfield());
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
				Subfield formatField = curItem.getSubfield(settings.getFormatSubfield());
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
			super.loadPrintFormatInformation(ilsRecord, record, hasChildRecords);
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
		long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		for (String tmpFormatBoost : formatBoosts){
			if (AspenStringUtils.isNumeric(tmpFormatBoost)) {
				long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			}
		}
		ilsRecord.setFormatBoost(formatBoost);
	}

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
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

		HashSet<String> translatedAudiences = translateCollection("target_audience", targetAudiences, identifier, true);
		groupedWork.addTargetAudiences(translatedAudiences);
		groupedWork.addTargetAudiencesFull(translatedAudiences);
	}
}
