package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.net.URLEncoder;
import java.sql.*;
import java.util.*;

class KohaRecordProcessor extends IlsRecordProcessor {
	private final HashSet<String> inTransitItems = new HashSet<>();
	private final HashSet<String> onHoldShelfItems = new HashSet<>();
	private final HashMap<String, String> lostStatuses = new HashMap<>();
	private final HashMap<String, String> damagedStatuses = new HashMap<>();
	private final HashMap<String, String> notForLoanStatuses = new HashMap<>();

	KohaRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		this (indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex, null);
		suppressRecordsWithNoCollection = false;
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
					logger.warn("Error connecting to koha database ", e);
					//System.exit(1);
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

	private KohaRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex, Connection kohaConnection) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
		boolean valid = false;
		int tries = 0;
		while (tries < 3 && !valid) {
			tries ++;
			valid = true;
			if (kohaConnection == null) {
				kohaConnection = connectToKohaDB(dbConn, logger);
			}

			if (kohaConnection == null) {
				valid = false;
			}else{
				try {
					//Get a list of lost statuses
					PreparedStatement lostStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'LOST'");
					ResultSet lostStatusRS = lostStatusStmt.executeQuery();
					while (lostStatusRS.next()) {
						lostStatuses.put(lostStatusRS.getString("authorised_value"), lostStatusRS.getString("lib"));
					}
					lostStatusRS.close();

					PreparedStatement damagedStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'DAMAGED'");
					ResultSet damagedStatusRS = damagedStatusStmt.executeQuery();
					while (damagedStatusRS.next()) {
						damagedStatuses.put(damagedStatusRS.getString("authorised_value"), damagedStatusRS.getString("lib"));
					}
					damagedStatusRS.close();


					PreparedStatement notForLoanStatusStmt = kohaConnection.prepareStatement("SELECT * FROM authorised_values where category = 'NOT_LOAN'");
					ResultSet notForLoanStatusesRS = notForLoanStatusStmt.executeQuery();
					while (notForLoanStatusesRS.next()) {
						notForLoanStatuses.put(notForLoanStatusesRS.getString("authorised_value"), notForLoanStatusesRS.getString("lib"));
					}
					notForLoanStatusesRS.close();

					//Get a list of all items that are in transit
					//PreparedStatement getInTransitItemsStmt = kohaConn.prepareStatement("SELECT itemnumber from reserves WHERE found = 'T'");
					PreparedStatement getInTransitItemsStmt;
					if (getKohaVersion(kohaConnection) >= 21.05){
						getInTransitItemsStmt = kohaConnection.prepareStatement("SELECT itemnumber from branchtransfers WHERE datearrived IS NULL AND datecancelled IS NULL");
					}else{
						getInTransitItemsStmt = kohaConnection.prepareStatement("SELECT itemnumber from branchtransfers WHERE datearrived IS NULL");
					}

					ResultSet inTransitItemsRS = getInTransitItemsStmt.executeQuery();
					while (inTransitItemsRS.next()) {
						inTransitItems.add(inTransitItemsRS.getString("itemnumber"));
					}
					inTransitItemsRS.close();
					getInTransitItemsStmt.close();

					PreparedStatement onHoldShelfItemsStmt = kohaConnection.prepareStatement("SELECT itemnumber from reserves WHERE found = 'W'");
					ResultSet onHoldShelfItemsRS = onHoldShelfItemsStmt.executeQuery();
					while (onHoldShelfItemsRS.next()) {
						onHoldShelfItems.add(onHoldShelfItemsRS.getString("itemnumber"));
					}
					onHoldShelfItemsRS.close();
					onHoldShelfItemsStmt.close();
				} catch (Exception e) {
					logger.error("Error setting up koha statements ", e);
					kohaConnection = null;
					valid = false;
				}
			}
			if (!valid) {
				try {
					Thread.sleep(60000);
				} catch (InterruptedException e) {
					logger.error("Pausing to wait for koha database to be reestablished");
				}
			}
		}
		if (!valid) {
			logger.error("Could not connect to Koha database, watch out for dragons");
		}
	}

	private float kohaVersion = -1;
	private float getKohaVersion(Connection kohaConn){
		if (kohaVersion == -1) {
			try {
				PreparedStatement getKohaVersionStmt = kohaConn.prepareStatement("SELECT value FROM systempreferences WHERE variable='Version'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet kohaVersionRS = getKohaVersionStmt.executeQuery();
				while (kohaVersionRS.next()){
					kohaVersion = kohaVersionRS.getFloat("value");
					break;
				}
			} catch (SQLException e) {
				logger.error("Error loading koha version", e);
			}
		}
		return kohaVersion;
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return !inTransitItems.contains(itemInfo.getItemIdentifier()) && displayStatus.equals("On Shelf") || (treatLibraryUseOnlyGroupedStatusesAsAvailable && groupedStatus.equals("Library Use Only"));
	}

	@Override
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record) {
		HashMap<String, Integer> itemCountsByItype = new HashMap<>();
		HashMap<String, String> itemTypeToFormat = new HashMap<>();
		int mostUsedCount = 0;
		String mostPopularIType = ""; //Get a list of all the formats based on the items
		for(ItemInfo item : recordInfo.getRelatedItems()){
			if (item.isEContent()) {continue;}
			boolean foundFormatFromShelfLocation = false;
			String shelfLocationCode = item.getShelfLocationCode();
			if (shelfLocationCode != null){
				String shelfLocation = shelfLocationCode.toLowerCase().trim();
				if (hasTranslation("format", shelfLocation)){
					String translatedLocation = translateValue("format", shelfLocation, recordInfo.getRecordIdentifier());
					if (translatedLocation != null && translatedLocation.length() > 0) {
						if (itemCountsByItype.containsKey(shelfLocation)) {
							itemCountsByItype.put(shelfLocation, itemCountsByItype.get(shelfLocation) + 1);
						} else {
							itemCountsByItype.put(shelfLocation, 1);
						}
						foundFormatFromShelfLocation = true;
						itemTypeToFormat.put(shelfLocation, translatedLocation);
						if (itemCountsByItype.get(shelfLocation) > mostUsedCount) {
							mostPopularIType = shelfLocation;
							mostUsedCount = itemCountsByItype.get(shelfLocation);
						}
					}
				}
			}

			boolean foundFormatFromSublocation = false;
			String subLocationCode = item.getSubLocationCode();
			if (!foundFormatFromShelfLocation && subLocationCode != null){
				String subLocation = subLocationCode.toLowerCase().trim();
				if (hasTranslation("format", subLocation)){
					String translatedLocation = translateValue("format", subLocation, recordInfo.getRecordIdentifier());
					if (translatedLocation != null && translatedLocation.length() > 0) {
						if (itemCountsByItype.containsKey(subLocation)) {
							itemCountsByItype.put(subLocation, itemCountsByItype.get(subLocation) + 1);
						} else {
							itemCountsByItype.put(subLocation, 1);
						}
						foundFormatFromSublocation = true;
						itemTypeToFormat.put(subLocation, translatedLocation);
						if (itemCountsByItype.get(subLocation) > mostUsedCount) {
							mostPopularIType = subLocation;
							mostUsedCount = itemCountsByItype.get(subLocation);
						}
					}
				}
			}

			boolean foundFormatFromCollection = false;
			String collectionCode = item.getCollection();
			if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && collectionCode != null){
				collectionCode = collectionCode.toLowerCase().trim();
				if (hasTranslation("format", collectionCode)){
					String translatedLocation = translateValue("format", collectionCode, recordInfo.getRecordIdentifier());
					if (translatedLocation != null && translatedLocation.length() > 0) {
						if (itemCountsByItype.containsKey(collectionCode)) {
							itemCountsByItype.put(collectionCode, itemCountsByItype.get(collectionCode) + 1);
						} else {
							itemCountsByItype.put(collectionCode, 1);
						}
						foundFormatFromCollection = true;
						itemTypeToFormat.put(collectionCode, translatedLocation);
						if (itemCountsByItype.get(collectionCode) > mostUsedCount) {
							mostPopularIType = collectionCode;
							mostUsedCount = itemCountsByItype.get(collectionCode);
						}
					}
				}
			}

			if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && !foundFormatFromCollection) {
				String iTypeCode = item.getITypeCode();
				if (iTypeCode != null) {
					String iType = iTypeCode.toLowerCase().trim();
					if (itemCountsByItype.containsKey(iType)) {
						itemCountsByItype.put(iType, itemCountsByItype.get(iType) + 1);
					} else {
						itemCountsByItype.put(iType, 1);
						//Translate the iType to see what formats we get.  Some item types do not have a format by default and use the default translation
						//We still will want to record those counts.
						String translatedFormat = translateValue("format", iType, recordInfo.getRecordIdentifier());
						if (translatedFormat == null) {
							translatedFormat = "";
						}
						itemTypeToFormat.put(iType, translatedFormat);
					}

					if (itemCountsByItype.get(iType) > mostUsedCount) {
						mostPopularIType = iType;
						mostUsedCount = itemCountsByItype.get(iType);
					}
				}
			}
		}

		try {
			if (checkRecordForLargePrint && (itemTypeToFormat.size() == 1) && itemTypeToFormat.values().iterator().next().equalsIgnoreCase("Book")) {
				LinkedHashSet<String> printFormats = getFormatsFromBib(record, recordInfo);
				if (printFormats.size() == 1 && printFormats.iterator().next().equalsIgnoreCase("LargePrint")) {
					String translatedFormat = translateValue("format", "LargePrint", recordInfo.getRecordIdentifier());
					//noinspection Java8MapApi
					for (String itemType : itemTypeToFormat.keySet()) {
						itemTypeToFormat.put(itemType, translatedFormat);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error checking record for large print");
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
			if (AspenStringUtils.isNumeric(formatBoostStr)) {
				formatBoost = Long.parseLong(formatBoostStr);
			}
			recordInfo.setFormatBoost(formatBoost);
		}
	}

	private final HashSet<String> additionalStatuses = new HashSet<>();
	protected String getItemStatus(DataField itemField, String recordIdentifier){
		String itemIdentifier = getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField);
		if (inTransitItems.contains(itemIdentifier)){
			return "In Transit";
		}
		if (onHoldShelfItems.contains(itemIdentifier)){
			return "On Hold Shelf";
		}

		String subLocationData = getItemSubfieldData(subLocationSubfield, itemField);
		if (subLocationData != null && subLocationData.equalsIgnoreCase("ON-ORDER")){
			return "On Order";
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
					if (subfield == '7' && notForLoanStatuses.containsKey(fieldData)){
						return notForLoanStatuses.get(fieldData);
					}
					return defaultStatus;
				}else{
					if (subfield == 'q'){
						if (fieldData.matches("\\d{4}-\\d{2}-\\d{2}")){
							return "Checked Out";
						}
					}else if (subfield == '1'){
						try {
							return lostStatuses.get(fieldData);
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
							return damagedStatuses.get(fieldData);
						}catch (NumberFormatException nfe) {
							//Didn't get a valid status
							return null;
						}

					}else if (subfield == '7') {
						if ("-1".equals(fieldData)) {
							return "On Order";
						}
						try {
							return notForLoanStatuses.get(fieldData);
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
					}else if (subfield == '0'){
						//Everything should be treated as withdrawn if this field is set
						return "Withdrawn";
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

	protected StringBuilder loadUnsuppressedPrintItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record, StringBuilder suppressionNotes){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTagInt);
		for (DataField itemField : itemRecords){
			String itemIdentifier = getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField);
			ResultWithNotes isSuppressed = isItemSuppressed(itemField, itemIdentifier, suppressionNotes);
			suppressionNotes = isSuppressed.notes;
			if (!isSuppressed.result){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				if (itemField.getSubfield(iTypeSubfield) != null){
					String iType = itemField.getSubfield(iTypeSubfield).getData().toLowerCase().trim();
					if (iType.equals("ebook") || iType.equals("ebk") || iType.equals("eaudio") || iType.equals("evideo") || iType.equals("online") || iType.equals("oneclick") || iType.equals("eaudiobook") || iType.equals("download")){
						isEContent = true;
					}
				}
				if (!isEContent){
					ItemInfoWithNotes itemInfoWithNotes = createPrintIlsItem(groupedWork, recordInfo, record, itemField, suppressionNotes);
					suppressionNotes = itemInfoWithNotes.notes;
				}
			}
		}
		return suppressionNotes;
	}

	protected List<RecordInfo> loadUnsuppressedEContentItems(AbstractGroupedWorkSolr groupedWork, String identifier, Record record, StringBuilder suppressionNotes){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, itemTagInt);
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();

		for (DataField itemField : itemRecords){
			String itemIdentifier = getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField);
			ResultWithNotes isSuppressed = isItemSuppressed(itemField, itemIdentifier, suppressionNotes);
			suppressionNotes = isSuppressed.notes;
			if (!isSuppressed.result){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				boolean isCloudLibrary = false;
				boolean isOneClickDigital = false;
				if (itemField.getSubfield(iTypeSubfield) != null){
					String iType = itemField.getSubfield(iTypeSubfield).getData().toLowerCase().trim();
					if (iType.equals("ebook") || iType.equals("ebk") || iType.equals("eaudio") || iType.equals("evideo") || iType.equals("online") || iType.equals("oneclick") || iType.equals("eaudiobook") || iType.equals("download")){
						isEContent = true;
						String sourceType = getSourceType(record, itemField);
						if (sourceType != null){
							sourceType = sourceType.toLowerCase().trim();
							if (sourceType.contains("overdrive")) {
								isOverDrive = true;
							} else if (sourceType.contains("hoopla")) {
								isHoopla = true;
							} else if (sourceType.contains("cloudlibrary") || sourceType.contains("3m")) {
								isCloudLibrary = true;
							} else if (sourceType.contains("oneclickdigital")) {
								isOneClickDigital = true;
							} else {
								logger.debug("Found eContent Source " + sourceType);
							}
						}else {
							//Need to figure out how to load a source
							logger.warn("Did not find an econtent source for " + identifier);
						}
					}
				}
				if (!isOverDrive && !isHoopla && !isOneClickDigital && !isCloudLibrary && isEContent){
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
			if (sourceType.equalsIgnoreCase("marcexpress")){
				sourceType = "OverDrive";
			}
		}else{
			List<DataField> urlFields = record.getDataFields(856);
			for (DataField urlDataField : urlFields) {
				Subfield subfieldU = urlDataField.getSubfield('u');
				if (subfieldU != null) {
					String urlSubfield = subfieldU.getData();
					if (urlSubfield.contains("overdrive.com")) {
						sourceType = "OverDrive";
						break;
					} else if (urlSubfield.contains("ebrary.com")) {
						sourceType = "Ebook Central";
						break;
					} else if (urlSubfield.contains("oneclickdigital")) {
						sourceType = "oneclickdigital";
						break;
					} else if (urlSubfield.contains("yourcloudlibrary") || urlSubfield.contains("3m.com")) {
						sourceType = "cloudlibrary";
						break;
					} else if (urlSubfield.contains("hoopla")) {
						sourceType = "hoopla";
						break;
					} else if (urlSubfield.contains("freading.com")) {
						sourceType = "Freading";
						break;
					} else if (urlSubfield.contains("galegroup.com")){
						sourceType = "Gale Group";
						break;
					} else if (urlSubfield.contains("gpo.gov")){
						sourceType = "Government Document";
						break;
					} else {
						logger.debug("URL is not overdrive");
					}
				}
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
				DataField field037 = record.getDataField(37);
				DataField field949 = record.getDataField(949);
				if (field037 != null && field037.getSubfield('b') != null) {
					sourceType = field037.getSubfield('b').getData();
				}else if (field949 != null && field949.getSubfield('a') != null){
					sourceType = field949.getSubfield('a').getData();
				}else{
					//Try the location for the item
					if (itemField.getSubfield('a') != null){
						sourceType = itemField.getSubfield('a').getData();
					}
				}
			}
		}
		return sourceType;
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes) {
		boolean suppressed = false;
		if (curItem.getSubfield('i') != null) {
			suppressed = curItem.getSubfield('i').getData().equals("1");
			if (suppressed) suppressionNotes.append("Item ").append(itemIdentifier).append(" subfield i set to 1<br/>");
		}
		if (!suppressed && curItem.getSubfield(iTypeSubfield) != null) {
			suppressed = curItem.getSubfield(iTypeSubfield).getData().equalsIgnoreCase("ill");
			if (suppressed) suppressionNotes.append("Item ").append(itemIdentifier).append(" iType is ILL<br/>");
		}
		if (curItem.getSubfield('0') != null) {
			if (curItem.getSubfield('0').getData().equals("1")) {
				suppressed = true;
				suppressionNotes.append("Item ").append(itemIdentifier).append(" subfield 0 (withdrawn) set to 1<br/>");
			}
		}
		if (curItem.getSubfield('1') != null) {
			String fieldData = curItem.getSubfield('1').getData().toLowerCase();
			if (fieldData.equals("lost") || fieldData.equals("missing") || fieldData.equals("longoverdue") || fieldData.equals("trace")) {
				suppressed = true;
				suppressionNotes.append("Item ").append(itemIdentifier).append(" subfield 1 (itemlost) set to ").append(fieldData).append("<br/>");
			}
		}
		//Suppression based on format
		String shelfLocationCode = getSubfieldData(curItem, shelvingLocationSubfield);
		String subLocation = getSubfieldData(curItem, subLocationSubfield);
		String collectionCode = getSubfieldData(curItem, collectionSubfield);
		String itemType = getSubfieldData(curItem, iTypeSubfield);
		if (shelfLocationCode != null && formatsToSuppress.contains(shelfLocationCode.toUpperCase())){
			suppressed = true;
			suppressionNotes.append("Item ").append(itemIdentifier).append(" shelf location suppressed in formats table<br/>");
		}else if (subLocation != null && formatsToSuppress.contains(subLocation.toUpperCase())){
			suppressed = true;
			suppressionNotes.append("Item ").append(itemIdentifier).append(" sub location suppressed in formats table<br/>");
		}else if (collectionCode != null && formatsToSuppress.contains(collectionCode.toUpperCase())){
			suppressed = true;
			suppressionNotes.append("Item ").append(itemIdentifier).append(" collection code suppressed in formats table<br/>");
		}else if (itemType != null && formatsToSuppress.contains(itemType.toUpperCase())){
			suppressed = true;
			suppressionNotes.append("Item ").append(itemIdentifier).append(" item type suppressed in formats table<br/>");
		}
		if (suppressed){
			return new ResultWithNotes(true, suppressionNotes);
		}else{
			return super.isItemSuppressed(curItem, itemIdentifier, suppressionNotes);
		}
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location;
		String subLocationCode = getItemSubfieldData(subLocationSubfield, itemField);
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		if (includeLocationNameInDetailedLocation) {
			location = translateValue("location", locationCode, identifier);
		}else{
			location = "";
		}
		if (subLocationCode != null && subLocationCode.length() > 0){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && translatedSubLocation.length() > 0) {
				if (location.length() > 0) {
					location += " - ";
				}
				location += translateValue("sub_location", subLocationCode, identifier, true);
			}
		}
		String shelvingLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelvingLocation != null && shelvingLocation.length() > 0){
			if (location.length() > 0){
				location += " - ";
			}
			location += translateValue("shelf_location", shelvingLocation, identifier, true);
		}
		return location;
	}

	protected boolean isBibSuppressed(Record record, String identifier) {
		DataField field942 = record.getDataField(942);
		if (field942 != null){
			Subfield subfieldN = field942.getSubfield('n');
			if (subfieldN != null && subfieldN.getData().equals("1")){
				updateRecordSuppression(true, new StringBuilder().append("942n is set to 1"), identifier);
				return true;
			}
		}

		return super.isBibSuppressed(record, identifier);
	}

	protected boolean isItemHoldableUnscoped(ItemInfo itemInfo){
		//Koha uses subfield 7 to determine if a record is holdable or not.
		Subfield subfield7 = itemInfo.getMarcField().getSubfield('7');
		if (subfield7 != null) {
			int notForLoan = Integer.parseInt(subfield7.getData());
			if (notForLoan >= 1) {
				return false;
			}
		}
		return super.isItemHoldableUnscoped(itemInfo);
	}
}
