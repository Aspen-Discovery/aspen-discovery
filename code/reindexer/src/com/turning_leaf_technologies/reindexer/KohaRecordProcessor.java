package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

class KohaRecordProcessor extends IlsRecordProcessor {
	private HashSet<String> inTransitItems = new HashSet<>();
	private HashSet<String> onHoldShelfItems = new HashSet<>();
	private HashMap<Long, String> lostStatuses = new HashMap<>();
	private HashMap<Long, String> damagedStatuses = new HashMap<>();
	private HashMap<Long, String> notForLoanStatuses = new HashMap<>();

	KohaRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		this (indexer, dbConn, indexingProfileRS, logger, fullReindex, null);
	}

	private Connection connectToKohaDB(Connection dbConn, Logger logger) {
		Connection kohaConnection = null;
		try {
			//Get information about the account profile for koha
			PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE recordSource = ?");
			accountProfileStmt.setString(1, profileType);
			ResultSet accountProfileRS = accountProfileStmt.executeQuery();
			if (accountProfileRS.next()) {
				try {
					String host = accountProfileRS.getString("databaseHost");
					String port = accountProfileRS.getString("databasePort");
					if (port == null || port.length() == 0) {
						port = "3306";
					}
					String databaseName = accountProfileRS.getString("databaseName");
					String user = accountProfileRS.getString("databaseUser");
					String password = accountProfileRS.getString("databasePassword");
					String timezone = accountProfileRS.getString("databaseTimezone");

					String kohaConnectionJDBC = "jdbc:mysql://" +
							host + ":" + port +
							"/" + databaseName +
							"?user=" + user +
							"&password=" + password +
							"&useUnicode=yes&characterEncoding=UTF-8";
					if (timezone != null && timezone.length() > 0){
						kohaConnectionJDBC += "&serverTimezone=" + URLEncoder.encode(timezone, "UTF8");

					}
					kohaConnection = DriverManager.getConnection(kohaConnectionJDBC);
				} catch (Exception e) {
					logger.error("Error connecting to koha database ", e);
					System.exit(1);
				}
			} else {
				logger.error("Could not find an account profile for Koha stopping");
				System.exit(1);
			}
		} catch (Exception e) {
			logger.error("Error connecting to database ", e);
			System.exit(1);
		}
		return kohaConnection;
	}

	private KohaRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex, Connection kohaConnection) {
		super(indexer, dbConn, indexingProfileRS, logger, fullReindex);
		if (kohaConnection == null){
			kohaConnection = connectToKohaDB(dbConn, logger);
		}

		try {
			//Get a list of lost statuses
			PreparedStatement lostStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'LOST'");
			ResultSet lostStatusRS = lostStatusStmt.executeQuery();
			while (lostStatusRS.next()) {
				lostStatuses.put(lostStatusRS.getLong("authorised_value"), lostStatusRS.getString("lib"));
			}
			lostStatusRS.close();

			PreparedStatement damagedStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'DAMAGED'");
			ResultSet damagedStatusRS = damagedStatusStmt.executeQuery();
			while (damagedStatusRS.next()) {
				damagedStatuses.put(damagedStatusRS.getLong("authorised_value"), damagedStatusRS.getString("lib"));
			}
			damagedStatusRS.close();


			PreparedStatement notForLoanStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'NOT_LOAN'");
			ResultSet notForLoanStatusesRS = notForLoanStatusStmt.executeQuery();
			while (notForLoanStatusesRS.next()) {
				notForLoanStatuses.put(notForLoanStatusesRS.getLong("authorised_value"), notForLoanStatusesRS.getString("lib"));
			}
			notForLoanStatusesRS.close();

			//Get a list of all items that are in transit
			//PreparedStatement getInTransitItemsStmt = kohaConn.prepareStatement("SELECT itemnumber from reserves WHERE found = 'T'");
			PreparedStatement getInTransitItemsStmt = kohaConnection.prepareStatement("SELECT itemnumber from branchtransfers WHERE datearrived IS NULL");
			ResultSet inTransitItemsRS = getInTransitItemsStmt.executeQuery();
			while (inTransitItemsRS.next()){
				inTransitItems.add(inTransitItemsRS.getString("itemnumber"));
			}
			inTransitItemsRS.close();
			getInTransitItemsStmt.close();

			PreparedStatement onHoldShelfItemsStmt = kohaConnection.prepareStatement("SELECT itemnumber from reserves WHERE found = 'W'");
			ResultSet onHoldShelfItemsRS = onHoldShelfItemsStmt.executeQuery();
			while (onHoldShelfItemsRS.next()){
				onHoldShelfItems.add(onHoldShelfItemsRS.getString("itemnumber"));
			}
			onHoldShelfItemsRS.close();
			onHoldShelfItemsStmt.close();
		} catch (Exception e) {
			logger.error("Error setting up koha statements ", e);
			System.exit(1);
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		return !inTransitItems.contains(itemInfo.getItemIdentifier()) && (itemInfo.getStatusCode().equals("On Shelf") || itemInfo.getStatusCode().equals("Library Use Only"));
	}

	@Override
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record) {
		HashMap<String, Integer> itemCountsByItype = new HashMap<>();
		HashMap<String, String> itemTypeToFormat = new HashMap<>();
		int mostUsedCount = 0;
		String mostPopularIType = "";		//Get a list of all the formats based on the items
		List<DataField> items = MarcUtil.getDataFields(record, itemTag);
		for(DataField item : items){
			Subfield iTypeSubField = item.getSubfield(iTypeSubfield);
			if (iTypeSubField != null){
				String iType = iTypeSubField.getData().toLowerCase();
				if (itemCountsByItype.containsKey(iType)){
					itemCountsByItype.put(iType, itemCountsByItype.get(iType) + 1);
				}else{
					itemCountsByItype.put(iType, 1);
					//Translate the iType to see what formats we get.  Some item types do not have a format by default and use the default translation
					//We still will want to record those counts.
					String translatedFormat = translateValue("format", iType, recordInfo.getRecordIdentifier());
					//If the format is book, ignore it for now.  We will use the default method later.
					if (translatedFormat == null || translatedFormat.equalsIgnoreCase("book")){
						translatedFormat = "";
					}
					itemTypeToFormat.put(iType, translatedFormat);
				}

				if (itemCountsByItype.get(iType) > mostUsedCount){
					mostPopularIType = iType;
					mostUsedCount = itemCountsByItype.get(iType);
				}
			}
		}

		if (itemTypeToFormat.size() == 0 || itemTypeToFormat.get(mostPopularIType) == null || itemTypeToFormat.get(mostPopularIType).length() == 0){
			//We didn't get any formats from the collections, get formats from the base method (007, 008, etc).
			//logger.debug("All formats are books or there were no formats found, loading format information from the bib");
			super.loadPrintFormatFromBib(recordInfo, record);
		} else{
			//logger.debug("Using default method of loading formats from iType");
			recordInfo.addFormat(itemTypeToFormat.get(mostPopularIType));
			String translatedFormatCategory = translateValue("format_category", mostPopularIType, recordInfo.getRecordIdentifier());
			if (translatedFormatCategory == null){
				translatedFormatCategory = translateValue("format_category", itemTypeToFormat.get(mostPopularIType), recordInfo.getRecordIdentifier());
				if (translatedFormatCategory == null){
					translatedFormatCategory = mostPopularIType;
				}
			}
			recordInfo.addFormatCategory(translatedFormatCategory);
			long formatBoost = 1L;
			String formatBoostStr = translateValue("format_boost", mostPopularIType, recordInfo.getRecordIdentifier());
			if (formatBoostStr == null){
				formatBoostStr = translateValue("format_boost", itemTypeToFormat.get(mostPopularIType), recordInfo.getRecordIdentifier());
			}
			if (Util.isNumeric(formatBoostStr)) {
				formatBoost = Long.parseLong(formatBoostStr);
			}
			recordInfo.setFormatBoost(formatBoost);
		}
	}

	private HashSet<String> additionalStatuses = new HashSet<>();
	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String itemIdentifier = getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField);
		if (inTransitItems.contains(itemIdentifier)){
			return "In Transit";
		}
		if (onHoldShelfItems.contains(itemIdentifier)){
			return "On Hold Shelf";
		}

		//Determining status for Koha relies on a number of different fields
		String status = getStatusFromSubfield(itemField, '0', "Withdrawn");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '1', "Lost");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '4', "Damaged");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, 'q', "Checked Out");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '7', "Library Use Only");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, 'k', null);
		if (status != null) return status;

		return "On Shelf";
	}

	private String getStatusFromSubfield(DataField itemField, char subfield, String defaultStatus) {
		if (itemField.getSubfield(subfield) != null){
			String fieldData = itemField.getSubfield(subfield).getData();
			if (!fieldData.equals("0")) {
				if (fieldData.equals("1")) {
					return defaultStatus;
				}else{
					if (subfield == 'q'){
						if (fieldData.matches("\\d{4}-\\d{2}-\\d{2}")){
							return "Checked Out";
						}
					}else if (subfield == '1'){
						try {
							Long subfieldDataNumeric = Long.parseLong(fieldData);
							return lostStatuses.get(subfieldDataNumeric);
						}catch (NumberFormatException nfe) {
							switch (fieldData) {
								case "lost":
									return "Lost";
								case "missing":
									return "Missing";
								case "longoverdue":
									return "Long Overdue";
								case "trace":
									return "Trace";
							}
						}

					}else if (subfield == '4'){
						try {
							Long subfieldDataNumeric = Long.parseLong(fieldData);
							return damagedStatuses.get(subfieldDataNumeric);
						}catch (NumberFormatException nfe) {
							//Didn't get a valid status
							return null;
						}

					}else if (subfield == '7') {
						if ("-1".equals(fieldData)) {
							return "On Order";
						}
						try {
							Long subfieldDataNumeric = Long.parseLong(fieldData);
							return notForLoanStatuses.get(subfieldDataNumeric);
						}catch (NumberFormatException nfe) {
							//Didn't get a valid status
							return null;
						}
					}else if (subfield == 'k') {
						switch (fieldData) {
							case "CATALOGED":
							case "READY":
								return null;
							case "BINDERY":
								return "Bindery";
							case "IN REPAIRS":
								return "Repair";
							case "trace":
								return "Trace";
							default:
								//There are several reserve statuses that we don't care about, just ignore silently.
								return null;
						}
					}
					String status = "|" + subfield + "-" + fieldData;
					if (!additionalStatuses.contains(status)){
						logger.warn("Found new status " + status);
						additionalStatuses.add(status);
					}
				}
			}
		}
		return null;
	}

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTag);
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				if (itemField.getSubfield(iTypeSubfield) != null){
					String iType = itemField.getSubfield(iTypeSubfield).getData().toLowerCase();
					if (iType.equals("ebook") || iType.equals("eaudio") || iType.equals("online") || iType.equals("oneclick")){
						isEContent = true;
					}
				}
				if (!isEContent){
					createPrintIlsItem(groupedWork, recordInfo, record, itemField);
				}
			}
		}
	}

	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTag);
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();

		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				if (itemField.getSubfield(iTypeSubfield) != null){
					String iType = itemField.getSubfield(iTypeSubfield).getData().toLowerCase();
					if (iType.equals("ebook") || iType.equals("eaudio") || iType.equals("online") || iType.equals("oneclick")){
						isEContent = true;
						String sourceType = getSourceType(record, itemField);
						if (sourceType != null){
							sourceType = sourceType.toLowerCase().trim();
							if (sourceType.contains("overdrive")) {
								isOverDrive = true;
							} else if (sourceType.contains("hoopla")) {
								isHoopla = true;
							} else {
								logger.debug("Found eContent Source " + sourceType);
							}
						}else {
							//Need to figure out how to load a source
							logger.warn("Did not find an econtent source for " + identifier);
						}
					}
				}
				if (!isOverDrive && !isHoopla && isEContent){
					RecordInfo eContentRecord = getEContentIlsRecord(groupedWork, record, identifier, itemField);
					if (eContentRecord != null) {
						unsuppressedEcontentRecords.add(eContentRecord);
					}
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	@Override
	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		if (econtentItem.getITypeCode() != null) {
			String iType = econtentItem.getITypeCode().toLowerCase();
			String translatedFormat = translateValue("format", iType, econtentRecord.getRecordIdentifier());
			String translatedFormatCategory = translateValue("format_category", iType, econtentRecord.getRecordIdentifier());
			String translatedFormatBoost = translateValue("format_boost", iType, econtentRecord.getRecordIdentifier());
			econtentItem.setFormat(translatedFormat);
			econtentItem.setFormatCategory(translatedFormatCategory);
			econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
		}
	}

	protected String getSourceType(Record record, DataField itemField) {
		//Try to figure out the source
		//Try |e
		String sourceType = null;
		if (itemField.getSubfield('e') != null){
			sourceType = itemField.getSubfield('e').getData();
		}else{
			//Try 949a
			DataField field949 = record.getDataField("949");
			if (field949 != null && field949.getSubfield('a') != null){
				sourceType = field949.getSubfield('a').getData();
			}else{
				DataField field037 = record.getDataField("037");
				if (field037 != null && field037.getSubfield('b') != null){
					sourceType = field037.getSubfield('b').getData();
				}else{
					List<DataField> urlFields = record.getDataFields("856");
					for (DataField urlDataField : urlFields){
						if (urlDataField.getSubfield('3') != null) {
							if (urlDataField.getIndicator1() == '4' || urlDataField.getIndicator1() == ' ') {
								//Technically, should not include indicator 2 of 2, but AspenCat has lots of records with an indicator 2 of 2 that are valid.
								if (urlDataField.getIndicator2() == ' ' || urlDataField.getIndicator2() == '0' || urlDataField.getIndicator2() == '1' || urlDataField.getIndicator2() == '2') {
									sourceType = urlDataField.getSubfield('3').getData().trim();
									break;
								}
							}
						}
					}

					//If the source type is still null, try the location of the item
					if (sourceType == null){
						//Try the location for the item
						if (itemField.getSubfield('a') != null){
							sourceType = itemField.getSubfield('a').getData();
						}
					}
				}
			}
		}
		return sourceType;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		boolean suppressed = false;
		if (curItem.getSubfield('i') != null) {
			suppressed = curItem.getSubfield('i').getData().equals("1");
		}
		if (!suppressed && curItem.getSubfield(iTypeSubfield) != null) {
			suppressed = curItem.getSubfield(iTypeSubfield).getData().equalsIgnoreCase("ill");
		}
		if (curItem.getSubfield('0') != null) {
			if (curItem.getSubfield('0').getData().equals("1")) {
				suppressed = true;
			}
		}
		if (curItem.getSubfield('1') != null) {
			String fieldData = curItem.getSubfield('1').getData().toLowerCase();
			if (fieldData.equals("lost") || fieldData.equals("missing") || fieldData.equals("longoverdue") || fieldData.equals("trace")) {
				suppressed = true;
			}
		}
		return suppressed || super.isItemSuppressed(curItem);
	}

	protected String getShelfLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location = "";
		String subLocationCode = getItemSubfieldData(subLocationSubfield, itemField);
		if (subLocationCode != null && subLocationCode.length() > 0){
			location += translateValue("sub_location", subLocationCode, identifier);
		}else{
			String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
			location = translateValue("location", locationCode, identifier);
		}
		String shelvingLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelvingLocation != null && shelvingLocation.length() > 0){
			if (location.length() > 0){
				location += " - ";
			}
			location += translateValue("shelf_location", shelvingLocation, identifier);
		}
		return location;
	}

}
