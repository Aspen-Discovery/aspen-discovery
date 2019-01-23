package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.*;

/**
 * ILS Indexing with customizations specific to Flatirons Library Consortium
 *
 * Pika
 * User: Mark Noble
 * Date: 12/29/2014
 * Time: 10:25 AM
 */
class FlatironsRecordProcessor extends IIIRecordProcessor{
	FlatironsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, indexingProfileRS, logger, fullReindex);
		loadOrderInformationFromExport();
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-oyj";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0 || dueDate.equals("-  -")) {
				available = true;
			}
		}
		return available;
	}

	@Override
	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		IsRecordEContent isRecordEContent = new IsRecordEContent(record).invoke();
		boolean isEContent = isRecordEContent.isEContent();
		List<DataField> itemRecords = isRecordEContent.getItemRecords();
		if (!isEContent){
			//The record is print
			for (DataField itemField : itemRecords){
				if (!isItemSuppressed(itemField)){
					getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				}
			}
		}
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		IsRecordEContent isRecordEContent = new IsRecordEContent(record).invoke();
		boolean isEContent = isRecordEContent.isEContent();
		List<DataField> itemRecords = isRecordEContent.getItemRecords();
		List<RecordInfo> unsuppressedEcontentRecords = isRecordEContent.getUnsuppressedEcontentRecords();
		String url = isRecordEContent.getUrl();
		if (isEContent){
			for (DataField itemField : itemRecords) {
				if (!isItemSuppressed(itemField)) {
					//Check to see if the item has an eContent indicator
					RecordInfo eContentRecord = getEContentIlsRecord(groupedWork, record, identifier, itemField);
					if (eContentRecord != null) {
						unsuppressedEcontentRecords.add(eContentRecord);

						//Set the target audience based on the location code for the record based on the item locations
						this.loadTargetAudiences(groupedWork, record, eContentRecord.getRelatedItems(), identifier);
					}
				}
			}
			if (itemRecords.size() == 0){
				//Much of the econtent for flatirons has no items.  Need to determine the location based on the 907b field
				String eContentLocation = MarcUtil.getFirstFieldVal(record, "907b");
				if (eContentLocation != null) {
					ItemInfo itemInfo = new ItemInfo();
					itemInfo.setIsEContent(true);
					itemInfo.setLocationCode(eContentLocation);

					//Set the target audience based on the location code for the record based on the bib level location
					String lastCharacter = eContentLocation.substring(eContentLocation.length() - 1);
					groupedWork.addTargetAudience(translateValue("target_audience", lastCharacter, identifier));
					groupedWork.addTargetAudienceFull(translateValue("target_audience", lastCharacter, identifier));

					itemInfo.seteContentSource("External eContent");
					itemInfo.seteContentProtectionType("external");
					if (url.contains("ebrary.com")) {
						itemInfo.seteContentSource("ebrary");
					}else{
						itemInfo.seteContentSource("Unknown");
					}
					itemInfo.setCallNumber("Online");
					itemInfo.setShelfLocation(itemInfo.geteContentSource());
					RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
					relatedRecord.setSubSource(profileType);
					relatedRecord.addItem(itemInfo);
					//Check the 856 tag to see if there is a link there
					loadEContentUrl(record, itemInfo);
					if (itemInfo.geteContentUrl() == null){
						itemInfo.seteContentUrl(url);
					}

					loadEContentFormatInformation(record, relatedRecord, itemInfo);

					itemInfo.setDetailedStatus("Available Online");

					unsuppressedEcontentRecords.add(relatedRecord);
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	protected boolean isBibSuppressed(Record record) {
		DataField field998 = record.getDataField("998");
		if (field998 != null){
			Subfield bcode3Subfield = field998.getSubfield('f');
			if (bcode3Subfield != null){
				String bCode3 = bcode3Subfield.getData().toLowerCase().trim();
				if (bCode3.matches("^(c|d|s|a|m|r|n)$")){
					return true;
				}
			}
		}

		String bibFormat = MarcUtil.getFirstFieldVal(record, "998e");
		if (bibFormat != null){
			bibFormat = bibFormat.trim();
		}else{
			return false;
		}
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = MarcUtil.getFirstFieldVal(record, "856u");
		boolean has856 = url != null;
		if (isEContentBibFormat && has856){
			//Suppress if the url is an overdrive or hoopla url
			if (url.contains("lib.overdrive") || url.contains("hoopla")){
				return true;
			}
		}

		return false;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null) {
			String icode2 = icode2Subfield.getData().toLowerCase().trim();

			//Suppress icode2 of wmsrn
			//         status = l
			//         bcode 3 = cdsamrn
			if (icode2.matches("^(w|m|s|r|n)$")) {
				return true;
			}
		}
		//Check status
		Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
		if (statusSubfield != null){
			String status = statusSubfield.getData();
			if (status.equals("l")){
				return true;
			}
		}
		return super.isItemSuppressed(curItem);
	}

	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		//Load the eContent Format from the mat type
		String bibFormat = MarcUtil.getFirstFieldVal(record, "998e");
		if (bibFormat != null){
			bibFormat = bibFormat.trim();
		}else{
			bibFormat = "";
		}
		String format;
		switch (bibFormat){
			case "3":
				format = "eBook";
				break;
			case "v":
				format = "eVideo";
				break;
			case "u":
				format = "eAudiobook";
				break;
			case "y":
				format = "eMusic";
				break;
			case "t":
				//Check to see if this is a serial resource
				String leader = record.getLeader().toString();
				boolean isSerial = false;
				if (leader.length() >= 7) {
					// check the Leader at position 7
					char leaderBit = leader.charAt(7);
					if (leaderBit == 's' || leaderBit == 'S') {
						isSerial = true;
					}
				}
				if (isSerial){
					format = "eJournal";
				}else {
					format = "online_resource";
				}
				break;
			default:
				//Check based off of other information
				if (econtentItem == null || econtentItem.getCallNumber() == null){
					format = "Unknown";
				}else {
					if (econtentItem.getCallNumber().contains("PHOTO")) {
						format = "Photo";
					} else if (econtentItem.getCallNumber().contains("OH")) {
						format = "Oral History";
					} else {
						format = "Unknown";
					}
				}
		}

		String translatedFormat = translateValue("format", format, econtentRecord.getRecordIdentifier());
		String translatedFormatCategory = translateValue("format_category", format, econtentRecord.getRecordIdentifier());
		String translatedFormatBoost = translateValue("format_boost", format, econtentRecord.getRecordIdentifier());
		econtentItem.setFormat(translatedFormat);
		econtentItem.setFormatCategory(translatedFormatCategory);
		try {
			econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
		}catch (NumberFormatException e){
			logger.warn("Could not get format boost for format " + format);
			econtentRecord.setFormatBoost(1);
		}
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return false;
	}

	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//For Flatirons, load audiences based on the final character of the location codes
		HashSet<String> targetAudiences = new HashSet<>();
		for (ItemInfo printItem : printItems){
			String locationCode = printItem.getLocationCode();
			if (locationCode.length() > 0) {
				String lastCharacter = locationCode.substring(locationCode.length() - 1);
				targetAudiences.add(lastCharacter);
			}
		}

		groupedWork.addTargetAudiences(translateCollection("target_audience", targetAudiences, identifier));
		groupedWork.addTargetAudiencesFull(translateCollection("target_audience", targetAudiences, identifier));
	}

	private class IsRecordEContent {
		private Record record;
		private String url;
		private List<DataField> itemRecords;
		private List<RecordInfo> unsuppressedEcontentRecords;
		private boolean isEContent;

		IsRecordEContent(Record record) {
			this.record = record;
		}

		public String getUrl() {
			return url;
		}

		List<DataField> getItemRecords() {
			return itemRecords;
		}

		List<RecordInfo> getUnsuppressedEcontentRecords() {
			return unsuppressedEcontentRecords;
		}

		boolean isEContent() {
			return isEContent;
		}

		IsRecordEContent invoke() {
			String bibFormat = MarcUtil.getFirstFieldVal(record, "998e");
			if (bibFormat != null){
				bibFormat = bibFormat.trim();
			}else{
				bibFormat = "";
			}
			boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
			url = MarcUtil.getFirstFieldVal(record, "856u");
			boolean has856 = url != null;

			itemRecords = MarcUtil.getDataFields(record, itemTag);
			unsuppressedEcontentRecords = new ArrayList<>();

			isEContent = false;

			if (isEContentBibFormat && has856) {
				isEContent = true;
			}else{
				//Check to see if this is Carnegie eContent
				for (DataField itemField : itemRecords) {
					if (itemField.getSubfield(locationSubfieldIndicator) != null && itemField.getSubfield(locationSubfieldIndicator).getData().startsWith("bc")){
						//Check to see if we have related links
						if (has856){
							isEContent = true;
							break;
						}else{
							//Check the 962
							List<DataField> additionalLinks = MarcUtil.getDataFields(record, "962");
							for (DataField additionalLink : additionalLinks){
								if (additionalLink.getSubfield('u') != null){
									url = additionalLink.getSubfield('u').getData();
									isEContent = true;
									break;
								}
							}
							if (isEContent){
								break;
							}
						}
					}
				}
			}
			return this;
		}
	}

	@Override
	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o");
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1") || status.equals("a") || status.equals("q") || status.equals("f") || status.equals("d");
	}
}
