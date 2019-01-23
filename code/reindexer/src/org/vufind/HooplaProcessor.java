package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.Record;

import java.io.ByteArrayInputStream;
import java.io.FileNotFoundException;
import java.io.InputStream;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashSet;
import java.util.Set;

/**
 * Extracts data from Hoopla Marc records to fill out information within the work to be indexed.
 *
 * Pika
 * User: Mark Noble
 * Date: 12/17/2014
 * Time: 10:30 AM
 */
class HooplaProcessor extends MarcRecordProcessor {
	private String individualMarcPath;
	private int numCharsToCreateFolderFrom;
	private boolean createFolderFromLeadingCharacters;

	HooplaProcessor(GroupedWorkIndexer indexer, ResultSet indexingProfileRS, Logger logger) {
		super(indexer, logger);

		try {
			individualMarcPath = indexingProfileRS.getString("individualMarcPath");
			numCharsToCreateFolderFrom         = indexingProfileRS.getInt("numCharsToCreateFolderFrom");
			createFolderFromLeadingCharacters  = indexingProfileRS.getBoolean("createFolderFromLeadingCharacters");

		}catch (Exception e){
			logger.error("Error loading indexing profile information from database", e);
		}
	}

	@Override
	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		Record record = loadMarcRecordFromDisk(identifier);

		if (record != null) {
			try {
				updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
			} catch (Exception e) {
				logger.error("Error updating solr based on hoopla marc record", e);
			}
		}
	}

	private Record loadMarcRecordFromDisk(String identifier){
		Record record = null;
		//Load the marc record from disc
		String individualFilename = getFileForIlsRecord(identifier);
		try {
			byte[] fileContents = Util.readFileBytes(individualFilename);
			InputStream inputStream = new ByteArrayInputStream(fileContents);
			//FileInputStream inputStream = new FileInputStream(individualFile);
			MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
			if (marcReader.hasNext()) {
				record = marcReader.next();
			}
			inputStream.close();
		} catch (FileNotFoundException fnfe){
			logger.error("Hoopla file " + individualFilename + " did not exist");
		} catch (Exception e) {
			logger.error("Error reading data from hoopla file " + individualFilename, e);
		}
		return record;
	}

	private String getFileForIlsRecord(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}

		String subFolderName;
		if (createFolderFromLeadingCharacters){
			subFolderName        = shortId.substring(0, numCharsToCreateFolderFrom);
		}else{
			subFolderName        = shortId.substring(0, shortId.length() - numCharsToCreateFolderFrom);
		}

		String basePath           = individualMarcPath + "/" + subFolderName;
		return basePath + "/" + shortId + ".mrc";
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//First get format
		String format = MarcUtil.getFirstFieldVal(record, "099a");
		if (format != null) {
			format = format.replace(" hoopla", "");
		}

		//Do updates based on the overall bib (shared regardless of scoping)
		updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, null, identifier, format);

		//Do special processing for Hoopla which does not have individual items within the record
		//Instead, each record has essentially unlimited items that can be used at one time.
		//There are also not multiple formats within a record that we would need to split out.

		String formatCategory = indexer.translateSystemValue("format_category_hoopla", format, identifier);
		String formatBoostStr = indexer.translateSystemValue("format_boost_hoopla", format, identifier);
		Long formatBoost = Long.parseLong(formatBoostStr);

		String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(record, "520a"));
		groupedWork.addDescription(fullDescription, format);

		//Load editions
		Set<String> editions = MarcUtil.getFieldList(record, "250a");
		String primaryEdition = null;
		if (editions.size() > 0) {
			primaryEdition = editions.iterator().next();
		}
		groupedWork.addEditions(editions);

		//Load publication details
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		String publisher = null;
		if (publishers.size() > 0){
			publisher = publishers.iterator().next();
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		String publicationDate = null;
		if (publicationDates.size() > 0){
			publicationDate = publicationDates.iterator().next();
		}

		//Load physical description
		Set<String> physicalDescriptions = MarcUtil.getFieldList(record, "300abcefg:530abcd");
		String physicalDescription = null;
		if (physicalDescriptions.size() > 0){
			physicalDescription = physicalDescriptions.iterator().next();
		}
		groupedWork.addPhysical(physicalDescriptions);

		//Setup the per Record information
		RecordInfo recordInfo = groupedWork.addRelatedRecord("hoopla", identifier);
		recordInfo.setFormatBoost(formatBoost);
		recordInfo.setEdition(primaryEdition);
		recordInfo.setPhysicalDescription(physicalDescription);
		recordInfo.setPublicationDate(publicationDate);
		recordInfo.setPublisher(publisher);

		//Load Languages
		HashSet<RecordInfo> records = new HashSet<>();
		records.add(recordInfo);
		loadLanguageDetails(groupedWork, record, records, identifier);

		//For Hoopla, we just have a single item always
		ItemInfo itemInfo = new ItemInfo();
		itemInfo.setIsEContent(true);
		itemInfo.setNumCopies(1);
		itemInfo.setFormat(format);
		itemInfo.setFormatCategory(formatCategory);
		itemInfo.seteContentSource("Hoopla");
		itemInfo.seteContentProtectionType("Always Available");
		itemInfo.setShelfLocation("Online Hoopla Collection");
		itemInfo.setCallNumber("Online Hoopla");
		itemInfo.setSortableCallNumber("Online Hoopla");
		itemInfo.seteContentSource("Hoopla");
		itemInfo.seteContentProtectionType("Always Available");
		itemInfo.setDetailedStatus("Available Online");
		loadEContentUrl(record, itemInfo);
		Date dateAdded = indexer.getDateFirstDetected("hoopla", identifier);
		itemInfo.setDateAdded(dateAdded);

		recordInfo.addItem(itemInfo);
		loadScopeInfoForEContentItem(groupedWork, recordInfo, itemInfo, record);


		//TODO: Determine how to find popularity for Hoopla titles.
		//Right now the information is not exported from Hoopla.  We could load based on clicks
		//From Pika to Hoopla, but that wouldn't count plays directly within the app
		//(which may be ok).
		groupedWork.addPopularity(1);

		//Related Record
		groupedWork.addRelatedRecord("hoopla", identifier);
	}

	private void loadScopeInfoForEContentItem(GroupedWorkSolr groupedWork, RecordInfo recordInfo, ItemInfo itemInfo, Record record) {
		//Figure out ownership information
		for (Scope curScope: indexer.getScopes()){
			String originalUrl = itemInfo.geteContentUrl();
			Scope.InclusionResult result = curScope.isItemPartOfScope("hoopla", "", "", null, groupedWork.getTargetAudiences(), recordInfo.getPrimaryFormat(), false, false, true, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(true);
				scopingInfo.setStatus("Available Online");
				scopingInfo.setGroupedStatus("Available Online");
				scopingInfo.setHoldable(false);
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope("hoopla", "", ""));
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope("hoopla", "", ""));
					}
				}
				if (curScope.isLibraryScope()) {
					 scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope("hoopla", "", ""));
				}
				//Check to see if we need to do url rewriting
				if (originalUrl != null && !originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}
}
