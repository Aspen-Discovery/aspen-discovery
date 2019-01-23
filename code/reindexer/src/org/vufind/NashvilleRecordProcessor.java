package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * ILS Indexing with customizations specific to Nashville
 * Pika
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
class NashvilleRecordProcessor extends IIIRecordProcessor {
	NashvilleRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	public void loadPrintFormatInformation(RecordInfo ilsRecord, Record record) {
		Set<String> printFormatsRaw = MarcUtil.getFieldList(record, "998d");
		HashSet<String> printFormats = new HashSet<>();
		for (String curFormat : printFormatsRaw){
			printFormats.add(curFormat.toLowerCase());
		}

		HashSet<String> translatedFormats = translateCollection("format", printFormats, ilsRecord.getRecordIdentifier());
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats, ilsRecord.getRecordIdentifier());
		ilsRecord.addFormats(translatedFormats);
		ilsRecord.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats, ilsRecord.getRecordIdentifier());
		for (String tmpFormatBoost : formatBoosts){
			if (Util.isNumeric(tmpFormatBoost)) {
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			}
		}
		ilsRecord.setFormatBoost(formatBoost);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-do";
		if (availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0 || dueDate.trim().equals("-  -")) {
				available = true;
			}
		}
		return available;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationCodeSubfield == null) {
			return false;
		}
		String locationCode = locationCodeSubfield.getData().trim();

		return locationCode.matches(".*sup") || super.isItemSuppressed(curItem);
	}

	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();

		//Check to see if we should add a supplemental record:
		String url = MarcUtil.getFirstFieldVal(record, "856u");
		if (url == null){
			return  unsuppressedEcontentRecords;
		}
		if (url.contains("digital.library.nashville.org") ||
				url.contains("www.library.nashville.org/localhistory/findingaids") ||
				url.contains("nashville.contentdm.oclc.org") ||
				url.contains("purl.fdlp.gov") ||
				url.contains("purl.access.gpo.gov")
				){
			//Much of the econtent for flatirons has no items.  Need to determine the location based on the 907b field
			String eContentLocation = MarcUtil.getFirstFieldVal(record, "945l");
			if (eContentLocation == null && (url.contains("purl.fdlp.gov") || url.contains("purl.access.gpo.gov"))){
				eContentLocation = "mndoc";
			}
			if (eContentLocation != null) {
				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setIsEContent(true);
				itemInfo.setLocationCode(eContentLocation);
				itemInfo.seteContentProtectionType("external");
				itemInfo.setCallNumber("Online");
				itemInfo.setShelfLocation(itemInfo.geteContentSource());
				RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				itemInfo.seteContentUrl(url);

				loadEContentFormatInformation(record, relatedRecord, itemInfo);
				if (url.contains("purl.fdlp.gov") || url.contains("purl.access.gpo.gov")){
					itemInfo.setFormat("Online Version");
					itemInfo.seteContentSource("Government Documents");
				} else {
					if (url.contains("findingaid")){
						itemInfo.setFormat("Finding Aid");
						itemInfo.seteContentSource("Special Collections");
					}else{
						itemInfo.setFormat("Digitized Content");
						itemInfo.seteContentSource("Nashville Archives");
					}

				}
				itemInfo.setFormatCategory("Other");
				relatedRecord.setFormatBoost(1);

				itemInfo.setDetailedStatus("Available Online");

				unsuppressedEcontentRecords.add(relatedRecord);
			}else{
				logger.warn("Record " + identifier + " looks like eContent, but we didn't get a location for it");
			}
		}

		return unsuppressedEcontentRecords;
	}

	@Override
	protected boolean isOrderItemValid(String status, String code3) {
		return (code3 == null || !code3.equals("s")) && (status.equals("o") || status.equals("1") || status.equals("a") || status.equals("q"));
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return true;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o");
	}
}
