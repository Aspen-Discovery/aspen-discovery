package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

public class LoanRuleDeterminer {
	private String location;
	private String trimmedLocation;
	private String patronType;
	private HashSet<Long>	patronTypes;
	private String itemType;
	private HashSet<Long> itemTypes;
	private Long loanRuleId;
	private boolean active;
	private Long rowNumber;

	@SuppressWarnings("unused")
	Long getRowNumber() {
		return rowNumber;
	}

	void setRowNumber(Long rowNumber) {
		this.rowNumber = rowNumber;
	}

	public String getLocation() {
		return location;
	}
	public void setLocation(String location) {
		location = location.trim();
		this.location = location;
		if (location.endsWith("*")){
			trimmedLocation = location.substring(0, location.length() -1).toLowerCase();
		}else{
			trimmedLocation = location.toLowerCase();
		}
	}
	
	Long getLoanRuleId() {
		return loanRuleId;
	}
	void setLoanRuleId(Long loanRuleId) {
		this.loanRuleId = loanRuleId;
	}
	boolean isActive() {
		return active;
	}
	void setActive(boolean active) {
		this.active = active;
	}
	String getPatronType() {
		return patronType;
	}
	void setPatronType(String patronType) {
		this.patronType = patronType;
		patronTypes = splitNumberRangeString(patronType);
	}
	String getItemType() {
		return itemType;
	}
	void setItemType(String itemType) {
		this.itemType = itemType;
		itemTypes = splitNumberRangeString(itemType);
	}
	private HashSet<Long> splitNumberRangeString(String numberRangeString) {
		HashSet<Long> result = new HashSet<>();
		String[] iTypeValues = numberRangeString.split(",");

		for (String iTypeValue : iTypeValues) {
			if (iTypeValue.indexOf('-') > 0) {
				String[] iTypeRange = iTypeValue.split("-");
				Long iTypeRangeStart = Long.parseLong(iTypeRange[0]);
				Long iTypeRangeEnd = Long.parseLong(iTypeRange[1]);
				for (Long j = iTypeRangeStart; j <= iTypeRangeEnd; j++) {
					result.add(j);
				}
			} else {
				result.add(Long.parseLong(iTypeValue));
			}
		}
		return result;
	}


	boolean matchesLocation(String locationCode) {
		return location.equals("*") || location.equals("?????") || locationCode.toLowerCase().startsWith(this.trimmedLocation);
	}
	HashSet<Long> getPatronTypes() {
		return patronTypes;
	}
	HashSet<Long> getItemTypes() {
		return itemTypes;
	}

}
