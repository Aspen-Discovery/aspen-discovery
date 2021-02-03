package com.turning_leaf_technologies.reindexer;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.indexing.Scope;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.*;

abstract class IIIRecordProcessor extends IlsRecordProcessor{
	private HashMap<String, ArrayList<OrderInfo>> orderInfoFromExport = new HashMap<>();
	private boolean loanRuleDataLoaded = false;
	private HashMap<Long, LoanRule> loanRules = new HashMap<>();
	private ArrayList<LoanRuleDeterminer> loanRuleDeterminers = new ArrayList<>();
	private String exportPath;
	// A list of status codes that are eligible to show items as checked out.
	HashSet<String> validCheckedOutStatusCodes = new HashSet<>();

	IIIRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, dbConn, indexingProfileRS, logger, fullReindex);
		try {
			exportPath = indexingProfileRS.getString("marcPath");
		}catch (Exception e){
			logger.error("Unable to load marc path from indexing profile");
		}
		loadLoanRuleInformation(dbConn, logger);
		validCheckedOutStatusCodes.add("-");
	}

	private void loadLoanRuleInformation(Connection dbConn, Logger logger) {
		if (!loanRuleDataLoaded){
			//Load loan rules
			try {
				PreparedStatement loanRuleStmt = dbConn.prepareStatement("SELECT * from loan_rules", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRulesRS = loanRuleStmt.executeQuery();
				while (loanRulesRS.next()) {
					LoanRule loanRule = new LoanRule();
					loanRule.setLoanRuleId(loanRulesRS.getLong("loanRuleId"));
					loanRule.setName(loanRulesRS.getString("name"));
					loanRule.setHoldable(loanRulesRS.getBoolean("holdable"));
					loanRule.setBookable(loanRulesRS.getBoolean("bookable"));

					loanRules.put(loanRule.getLoanRuleId(), loanRule);
				}
				logger.debug("Loaded " + loanRules.size() + " loan rules");

				PreparedStatement loanRuleDeterminersStmt = dbConn.prepareStatement("SELECT * from loan_rule_determiners where active = 1 order by rowNumber DESC", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRuleDeterminersRS = loanRuleDeterminersStmt.executeQuery();
				while (loanRuleDeterminersRS.next()) {
					LoanRuleDeterminer loanRuleDeterminer = new LoanRuleDeterminer();
					loanRuleDeterminer.setRowNumber(loanRuleDeterminersRS.getLong("rowNumber"));
					loanRuleDeterminer.setLocation(loanRuleDeterminersRS.getString("location"));
					loanRuleDeterminer.setPatronType(loanRuleDeterminersRS.getString("patronType"));
					loanRuleDeterminer.setItemType(loanRuleDeterminersRS.getString("itemType"));
					loanRuleDeterminer.setLoanRuleId(loanRuleDeterminersRS.getLong("loanRuleId"));
					loanRuleDeterminer.setActive(loanRuleDeterminersRS.getBoolean("active"));

					loanRuleDeterminers.add(loanRuleDeterminer);
				}

				logger.debug("Loaded " + loanRuleDeterminers.size() + " loan rule determiner");
			} catch (SQLException e) {
				logger.error("Unable to load loan rules", e);
			}
			loanRuleDataLoaded = true;
		}
	}

	private HashMap<String, HashMap<RelevantLoanRule, LoanRuleDeterminer>> cachedRelevantLoanRules = new HashMap<>();
	private HashMap<RelevantLoanRule, LoanRuleDeterminer> getRelevantLoanRules(String iType, String locationCode, HashSet<Long> pTypesToCheck){
		//Look for ac cached value
		String key = iType + locationCode + pTypesToCheck.toString();
		HashMap<RelevantLoanRule, LoanRuleDeterminer> relevantLoanRules = cachedRelevantLoanRules.get(key);
		if (relevantLoanRules == null){
			relevantLoanRules = new HashMap<>();
		}else{
			return relevantLoanRules;
		}

		HashSet<Long> pTypesNotAccountedFor = new HashSet<>(pTypesToCheck);
		long iTypeLong;
		if (iType == null){
			iTypeLong = 999L;
		}else{
			iTypeLong = Long.parseLong(iType);
		}

		boolean hasDefaultPType = pTypesToCheck.contains(-1L);
		for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers) {
			if (curDeterminer.isActive()) {
				//Make sure the location matches
				if (curDeterminer.matchesLocation(locationCode)) {
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
					if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)) {
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
						if (hasDefaultPType || curDeterminer.getPatronType().equals("999") || isPTypeValid(curDeterminer.getPatronTypes(), pTypesNotAccountedFor)) {
							//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
							LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
							relevantLoanRules.put(new RelevantLoanRule(loanRule, curDeterminer.getPatronTypes()), curDeterminer);

							//Stop once we have accounted for all pTypes
							if (curDeterminer.getPatronType().equals("999")) {
								//999 accounts for all pTypes
								break;
							} else {
								pTypesNotAccountedFor.removeAll(curDeterminer.getPatronTypes());
								if (pTypesNotAccountedFor.size() == 0) {
									break;
								}
							}

							//We want all relevant loan rules, do not break
							//break;
						}
					}
				}
			}
		}
		cachedRelevantLoanRules.put(key, relevantLoanRules);
		return relevantLoanRules;
	}

	private boolean isPTypeValid(HashSet<Long> determinerPatronTypes, HashSet<Long> pTypesToCheck) {
		//For our case,
		if (pTypesToCheck.size() == 0){
			return true;
		}
		for (Long determinerPType : determinerPatronTypes){
			for (Long pTypeToCheck : pTypesToCheck){
				if (pTypeToCheck.equals(determinerPType)) {
					return true;
				}
			}
		}
		return false;
	}

	private HashMap<String, HoldabilityInformation> holdabilityCache = new HashMap<>();
	@Override
	protected HoldabilityInformation isItemHoldable(ItemInfo itemInfo, Scope curScope, HoldabilityInformation isHoldableUnscoped) {
		//If no loan rules are provided, just use the default method.
		if (loanRuleDeterminers.size() == 0 || loanRules.size() == 0){
			return isHoldableUnscoped;
		}else {
			//Check to make sure this isn't an unscoped record
			if (curScope.isUnscoped()) {
				//This is an unscoped scope (everything should be holdable unless the location/itype/status is not holdable
				return isHoldableUnscoped;
			} else {
				//First check to see if the overall record is not holdable based on suppression rules
				if (isHoldableUnscoped.isHoldable()) {
					String locationCode;
					if (loanRulesAreBasedOnCheckoutLocation()) {
						//Loan rule determiner by lending location
						locationCode = curScope.getIlsCode();
					} else {
						//Loan rule determiner by owning location
						locationCode = itemInfo.getLocationCode();
					}

					String itemIType = itemInfo.getITypeCode();

					String key = curScope.getScopeName() + locationCode + itemIType;
					HoldabilityInformation cachedInfo = holdabilityCache.get(key);
					if (cachedInfo == null) {
						HashMap<RelevantLoanRule, LoanRuleDeterminer> relevantLoanRules = getRelevantLoanRules(itemIType, locationCode, curScope.getRelatedNumericPTypes());
						HashSet<Long> holdablePTypes = new HashSet<>();

						//Set back to false and then prove true
						boolean holdable = false;
						for (RelevantLoanRule loanRule : relevantLoanRules.keySet()) {
							if (loanRule.getLoanRule().getHoldable()) {
								holdablePTypes.addAll(loanRule.getPatronTypes());
								holdable = true;
							}
						}
						cachedInfo = new HoldabilityInformation(holdable, holdablePTypes);
						holdabilityCache.put(key, cachedInfo);
					}
					return cachedInfo;
				} else {
					return isHoldableUnscoped;
				}
			}
		}
	}

	private HashMap<String, BookabilityInformation> bookabilityCache = new HashMap<>();
	@Override
	protected BookabilityInformation isItemBookable(ItemInfo itemInfo, Scope curScope, BookabilityInformation isBookableUnscoped) {
		String locationCode;
		if (loanRulesAreBasedOnCheckoutLocation()) {
			//Loan rule determiner by lending location
			locationCode = curScope.getIlsCode();
		}else{
			//Loan rule determiner by owning location
			locationCode = itemInfo.getLocationCode();
		}

		String itemIType = itemInfo.getITypeCode();

		String key = curScope.getScopeName() + "-" + locationCode + "-" + itemIType;
		if (!bookabilityCache.containsKey(key)) {
			HashMap<RelevantLoanRule, LoanRuleDeterminer> relevantLoanRules = getRelevantLoanRules(itemIType, locationCode, curScope.getRelatedNumericPTypes());
			HashSet<Long> bookablePTypes = new HashSet<>();
			boolean isBookable = false;
			for (RelevantLoanRule loanRule : relevantLoanRules.keySet()) {
				if (loanRule.getLoanRule().getBookable()) {
					bookablePTypes.addAll(loanRule.getPatronTypes());
					isBookable = true;
				}
			}
			bookabilityCache.put(key, new BookabilityInformation(isBookable, bookablePTypes));
		}
		return bookabilityCache.get(key);
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

	protected abstract boolean loanRulesAreBasedOnCheckoutLocation();

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

	private SimpleDateFormat displayDateFormatter = new SimpleDateFormat("MMM d, yyyy");
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

	protected void loadOnOrderItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
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
					/*//Don't do this anymore, see D-1893
					HashSet<String> additionalOrderSubjects = new HashSet<>();
					additionalOrderSubjects.add("On Order");
					additionalOrderSubjects.add("Coming Soon");
					groupedWork.addTopic(additionalOrderSubjects);
					groupedWork.addTopicFacet(additionalOrderSubjects);*/
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
		itemInfo.setItemIdentifier(orderNumber);
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
}
