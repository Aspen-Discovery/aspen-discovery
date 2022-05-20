package com.turning_leaf_technologies.reindexer;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.indexing.SierraExportFieldMapping;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.*;

class IIIRecordProcessor extends IlsRecordProcessor{
	private final HashMap<String, ArrayList<OrderInfo>> orderInfoFromExport = new HashMap<>();
	private String exportPath;
	private SierraExportFieldMapping exportFieldMapping = null;
	// A list of status codes that are eligible to show items as checked out.
	HashSet<String> validCheckedOutStatusCodes = new HashSet<>();

	IIIRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
		try {
			exportPath = indexingProfileRS.getString("marcPath");
		}catch (Exception e){
			logger.error("Unable to load marc path from indexing profile");
		}
		validCheckedOutStatusCodes.add("-");
		loadOrderInformationFromExport();
		try {
			exportFieldMapping = SierraExportFieldMapping.loadSierraFieldMappings(dbConn, indexingProfileRS.getLong("id"), logger);
		}catch (Exception e){
			logger.error("Unable to load Sierra Export Mappings", e);
		}
	}

	protected String getDisplayGroupedStatus(ItemInfo itemInfo, String identifier) {
		String overriddenStatus = getOverriddenStatus(itemInfo, true);
		if (overriddenStatus != null) {
			return overriddenStatus;
		}else {
			String statusCode = itemInfo.getStatusCode();
			if (validCheckedOutStatusCodes.contains(statusCode)) {
				//We need to override based on due date
				String dueDate = itemInfo.getDueDate();
				if (dueDate == null || dueDate.length() == 0 || dueDate.equals("-  -")) {
					return translateValue("item_grouped_status", statusCode, identifier);
				} else {
					return "Checked Out";
				}
			} else {
				return translateValue("item_grouped_status", statusCode, identifier);
			}
		}
	}

	protected String getDisplayStatus(ItemInfo itemInfo, String identifier) {
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			return overriddenStatus;
		}else {
			String statusCode = itemInfo.getStatusCode();
			if (validCheckedOutStatusCodes.contains(statusCode)) {
				//We need to override based on due date
				String dueDate = itemInfo.getDueDate();
				if (dueDate == null || dueDate.length() == 0 || dueDate.equals("-  -")) {
					return translateValue("item_status", statusCode, identifier);
				} else {
					return "Checked Out";
				}
			} else {
				return translateValue("item_status", statusCode, identifier);
			}
		}
	}

	protected void setDetailedStatus(ItemInfo itemInfo, DataField itemField, String itemStatus, String identifier) {
		//See if we need to override based on the last check in date
		String overriddenStatus = getOverriddenStatus(itemInfo, false);
		if (overriddenStatus != null) {
			itemInfo.setDetailedStatus(overriddenStatus);
		}else {
			if (validCheckedOutStatusCodes.contains(itemStatus)) {
				String dueDate = itemInfo.getDueDate();
				if (dueDate == null || dueDate.length() == 0 || dueDate.equals("-  -")) {
					itemInfo.setDetailedStatus(translateValue("item_status", itemStatus, identifier));
				}else{
					itemInfo.setDetailedStatus("Due " + getDisplayDueDate(dueDate, itemInfo.getItemIdentifier()));
				}
			} else {
				itemInfo.setDetailedStatus(translateValue("item_status", itemStatus, identifier));
			}
		}
	}

	private final SimpleDateFormat displayDateFormatter = new SimpleDateFormat("MMM d, yyyy");
	private String getDisplayDueDate(String dueDateStr, String identifier){
		try {
			Date dueDate = dueDateFormatter.parse(dueDateStr);
			return displayDateFormatter.format(dueDate);
		}catch (Exception e){
			logger.warn("Could not load display due date for dueDate " + dueDateStr + " for identifier " + identifier, e);
		}
		return "Unknown";
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	private static String getCheckDigit(String basedId) {
		int sumOfDigits = 0;
		for (int i = 0; i < basedId.length(); i++){
			int multiplier = ((basedId.length() +1 ) - i);
			sumOfDigits += multiplier * Integer.parseInt(basedId.substring(i, i+1));
		}
		int modValue = sumOfDigits % 11;
		if (modValue == 10){
			return "x";
		}else{
			return Integer.toString(modValue);
		}
	}

	void loadOrderInformationFromExport() {
		File activeOrders = new File(this.exportPath + "/active_orders.csv");
		if (activeOrders.exists()){
			try{
				CSVReader reader = new CSVReader(new FileReader(activeOrders));
				//First line is headers
				reader.readNext();
				String[] orderData;
				while ((orderData = reader.readNext()) != null){
					OrderInfo orderRecord = new OrderInfo();
					String recordId = ".b" + orderData[0] + getCheckDigit(orderData[0]);
					String orderRecordId = ".o" + orderData[1] + getCheckDigit(orderData[1]);
					orderRecord.setOrderRecordId(orderRecordId);
					orderRecord.setStatus(orderData[3]);
					orderRecord.setNumCopies(Integer.parseInt(orderData[4]));
					//Get the order record based on the accounting unit
					orderRecord.setLocationCode(orderData[5]);
					if (orderInfoFromExport.containsKey(recordId)){
						orderInfoFromExport.get(recordId).add(orderRecord);
					}else{
						ArrayList<OrderInfo> orderRecordColl = new ArrayList<>();
						orderRecordColl.add(orderRecord);
						orderInfoFromExport.put(recordId, orderRecordColl);
					}
				}
			}catch(Exception e){
				logger.error("Error loading order records from active orders", e);
			}
		}
	}

	protected void loadOnOrderItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		if (orderInfoFromExport.size() > 0){
			ArrayList<OrderInfo> orderItems = orderInfoFromExport.get(recordInfo.getRecordIdentifier());
			if (orderItems != null) {
				for (OrderInfo orderItem : orderItems) {
					createAndAddOrderItem(recordInfo, orderItem);
					//For On Order Items, increment popularity based on number of copies that are being purchased.
					groupedWork.addPopularity(orderItem.getNumCopies());
				}
				if (recordInfo.getNumCopiesOnOrder() > 0 && !hasTangibleItems) {
					groupedWork.addKeywords("On Order");
					groupedWork.addKeywords("Coming Soon");
				}
			}
		}else{
			super.loadOnOrderItems(groupedWork, recordInfo, record, hasTangibleItems);
		}
	}

	private void createAndAddOrderItem(RecordInfo recordInfo, OrderInfo orderItem) {
		ItemInfo itemInfo = new ItemInfo();
		String orderNumber = orderItem.getOrderRecordId();
		String location = orderItem.getLocationCode();
		if (location == null){
			logger.warn("No location set for order " + orderNumber + " skipping");
			return;
		}
		itemInfo.setLocationCode(location);
		itemInfo.setItemIdentifier(orderNumber + "-" + location);
		itemInfo.setNumCopies(orderItem.getNumCopies());
		itemInfo.setIsEContent(false);
		itemInfo.setIsOrderItem();
		itemInfo.setCallNumber("ON ORDER");
		itemInfo.setSortableCallNumber("ON ORDER");
		itemInfo.setDetailedStatus("On Order");
		itemInfo.setCollection("On Order");
		//Since we don't know when the item will arrive, assume it will come tomorrow.
		Date tomorrow = new Date();
		tomorrow.setTime(tomorrow.getTime() + 1000 * 60 * 60 * 24);
		itemInfo.setDateAdded(tomorrow);

		//Format and Format Category should be set at the record level, so we don't need to set them here.

		//Add the library this is on order for
		itemInfo.setShelfLocation("On Order");
		itemInfo.setDetailedLocation("On Order");

		String status = orderItem.getStatus();

		if (isOrderItemValid(status, null)){
			recordInfo.addItem(itemInfo);
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-o";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		if (!available && (groupedStatus.equals("On Shelf") || (treatLibraryUseOnlyGroupedStatusesAsAvailable && groupedStatus.equals("Library Use Only")))){
			available = true;
		}
		return available;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (iCode2Subfield != ' '){
			Subfield iCode2SubfieldValue = curItem.getSubfield(iCode2Subfield);
			if (iCode2SubfieldValue != null){
				String iCode2Value = iCode2SubfieldValue.getData();
				if (iCode2sToSuppress != null && iCode2sToSuppress.matcher(iCode2Value).matches()){
					return true;
				}
			}
		}
		return super.isItemSuppressed(curItem);
	}

	protected boolean isBibSuppressed(Record record) {
		if (exportFieldMapping != null){
			DataField sierraFixedField = record.getDataField(exportFieldMapping.getFixedFieldDestinationField());
			if (sierraFixedField != null){
				Subfield bCode3Subfield = sierraFixedField.getSubfield(exportFieldMapping.getBcode3DestinationSubfield());
				if (bCode3Subfield != null){
					String bCode3 = bCode3Subfield.getData().toLowerCase().trim();
					if (bCode3sToSuppress != null && bCode3sToSuppress.matcher(bCode3).matches()){
						if (logger.isDebugEnabled()) {
							logger.debug("Bib record is suppressed due to BCode3 " + bCode3);
						}
						return true;
					}
				}
			}
		}
		return super.isBibSuppressed(record);
	}
}
