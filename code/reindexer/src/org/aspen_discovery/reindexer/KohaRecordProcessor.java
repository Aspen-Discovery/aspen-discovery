package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.FormatMapValue;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.*;
import java.util.regex.Pattern;
class KohaRecordProcessor extends IlsRecordProcessor {
	private final HashSet<String> inTransitItems = new HashSet<>();
	private final HashSet<String> onHoldShelfItems = new HashSet<>();
	private final HashMap<String, String> lostStatuses = new HashMap<>();
	private final HashMap<String, String> damagedStatuses = new HashMap<>();
	private final HashMap<String, String> notForLoanStatuses = new HashMap<>();

	KohaRecordProcessor(String serverName, GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		this (serverName, indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex, null);
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
					if (port == null || port.isEmpty()) {
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
					if (timezone != null && !timezone.isEmpty()){
						kohaConnectionJDBC += "&serverTimezone=" + URLEncoder.encode(timezone, StandardCharsets.UTF_8);

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

	private KohaRecordProcessor(String serverName, GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex, Connection kohaConnection) {
		super(serverName, indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
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
					Thread.sleep(5000);
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
				if (kohaVersionRS.next()){
					kohaVersion = kohaVersionRS.getFloat("value");
				}
			} catch (SQLException e) {
				logger.error("Error loading koha version", e);
			}
		}
		return kohaVersion;
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return !inTransitItems.contains(itemInfo.getItemIdentifier()) && groupedStatus.equals("On Shelf") || (settings.getTreatLibraryUseOnlyGroupedStatusesAsAvailable() && groupedStatus.equals("Library Use Only"));
	}

	private final HashSet<String> additionalStatuses = new HashSet<>();
	protected ItemStatus getItemStatus(DataField itemField, String recordIdentifier){
		String itemIdentifier = MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger);
		if (inTransitItems.contains(itemIdentifier)){
			return new ItemStatus("In Transit", ItemStatus.FROM_OTHER, this, recordIdentifier);
		}
		if (onHoldShelfItems.contains(itemIdentifier)){
			return new ItemStatus("On Hold Shelf", ItemStatus.FROM_OTHER, this, recordIdentifier);
		}

		String subLocationData = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (subLocationData != null && subLocationData.equalsIgnoreCase("ON-ORDER")){
			return new ItemStatus("On Order", ItemStatus.FROM_OTHER, this, recordIdentifier);
		}

		//Determining status for Koha relies on a number of different fields
		String status = getStatusFromSubfield(itemField, '0', "Withdrawn");
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		status = getStatusFromSubfield(itemField, '1', "Lost");
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		status = getStatusFromSubfield(itemField, '4', "Damaged");
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		status = getStatusFromSubfield(itemField, 'q', "Checked Out");
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		status = getStatusFromSubfield(itemField, '7', "Library Use Only"); //not for loan
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		status = getStatusFromSubfield(itemField, 'k', null);
		if (status != null) return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);

		return new ItemStatus("On Shelf", ItemStatus.FROM_OTHER, this, recordIdentifier);
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
		List<DataField> itemRecords = MarcUtil.getDataFields(record, settings.getItemTagInt());
		//Retrieve the eContent type Regex from the indexing profile
		Pattern eContentTypeRegex = settings.getTreatItemsAsEcontent();
		for (DataField itemField : itemRecords){
			String itemIdentifier = MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger);
			ResultWithNotes isSuppressed = isItemSuppressed(itemField, itemIdentifier, suppressionNotes);
			suppressionNotes = isSuppressed.notes;
			if (!isSuppressed.result){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				if (itemField.getSubfield(settings.getITypeSubfield()) != null){
					String iType = itemField.getSubfield(settings.getITypeSubfield()).getData().toLowerCase().trim();
					if (eContentTypeRegex.matcher(iType).matches()){
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

	protected List<RecordInfo> loadUnsuppressedEContentItems(AbstractGroupedWorkSolr groupedWork, String identifier, Record record, StringBuilder suppressionNotes, RecordInfo mainRecordInfo, boolean hasParentRecord, boolean hasChildRecords){
		List<DataField> itemRecords = MarcUtil.getDataFields(record, settings.getItemTagInt());
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();

		//Retrieve the Regex for eContent types from the indexing profile
		Pattern eContentTypeRegex = settings.getTreatItemsAsEcontent();

		for (DataField itemField : itemRecords){
			String itemIdentifier = MarcUtil.getItemSubfieldData(settings.getItemRecordNumberSubfield(), itemField, indexer.getLogEntry(), logger);
			ResultWithNotes isSuppressed = isItemSuppressed(itemField, itemIdentifier, suppressionNotes);
			suppressionNotes = isSuppressed.notes;
			if (!isSuppressed.result){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				boolean isCloudLibrary = false;
				boolean isOneClickDigital = false;
				if (itemField.getSubfield(settings.getITypeSubfield()) != null){
					String iType = itemField.getSubfield(settings.getITypeSubfield()).getData().toLowerCase().trim();
					if (eContentTypeRegex.matcher(iType).matches()){
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
					getIlsEContentItems(record, mainRecordInfo, identifier, itemField);
				}
			}
		}
		List<RecordInfo> parentEContentRecords = super.loadUnsuppressedEContentItems(groupedWork, identifier, record, suppressionNotes, mainRecordInfo, hasParentRecord, hasChildRecords);
		if (!parentEContentRecords.isEmpty()) {
			unsuppressedEcontentRecords.addAll(parentEContentRecords);
		}
		return unsuppressedEcontentRecords;
	}

	@Override
	protected void loadIlsEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		if (econtentItem.getITypeCode() != null) {
			String iType = econtentItem.getITypeCode().toLowerCase();
			FormatMapValue formatMapValue = settings.getFormatMapValue(iType, BaseIndexingSettings.FORMAT_TYPE_ITEM_TYPE);
			if (formatMapValue != null) {
				econtentItem.setFormat(formatMapValue.getFormat());
				econtentItem.setFormatCategory(formatMapValue.getFormatCategory());
				econtentRecord.setFormatBoost(formatMapValue.getFormatBoost());
			}
		}
	}

	protected String getSourceType(Record record, DataField itemField) {
		//Try to figure out the source
		//Try |e
		String sourceType = null;
		if (itemField.getSubfield('e') != null){
			sourceType = itemField.getSubfield('e').getData();
			//noinspection SpellCheckingInspection
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
				sourceType = "Online Content";
			}
		}
		return sourceType;
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes) {
		boolean suppressed = false;
		if (itemIdentifier == null) {
			suppressed = true;
			suppressionNotes.append("Item had no identifier, suppressing<br/>");
		}
		if (curItem.getSubfield('i') != null) {
			suppressed = curItem.getSubfield('i').getData().equals("1");
			if (suppressed) suppressionNotes.append("Item ").append(itemIdentifier).append(" subfield i set to 1<br/>");
		}
		if (!suppressed && curItem.getSubfield(settings.getITypeSubfield()) != null) {
			suppressed = curItem.getSubfield(settings.getITypeSubfield()).getData().equalsIgnoreCase("ill");
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
		String shelfLocationCode = getSubfieldData(curItem, settings.getShelvingLocationSubfield());
		String subLocation = getSubfieldData(curItem, settings.getSubLocationSubfield());
		String collectionCode = getSubfieldData(curItem, settings.getCollectionSubfield());
		String itemType = getSubfieldData(curItem, settings.getITypeSubfield());
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
		String subLocationCode = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		String locationCode = MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (settings.isIncludeLocationNameInDetailedLocation()) {
			location = translateValue("location", locationCode, identifier);
		}else{
			location = "";
		}
		if (subLocationCode != null && !subLocationCode.isEmpty()){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && !translatedSubLocation.isEmpty()) {
				if (!location.isEmpty()) {
					location += " - ";
				}
				location += translateValue("sub_location", subLocationCode, identifier, true);
			}
		}
		String shelvingLocation = MarcUtil.getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (shelvingLocation != null && !shelvingLocation.isEmpty()){
			if (!location.isEmpty()){
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
