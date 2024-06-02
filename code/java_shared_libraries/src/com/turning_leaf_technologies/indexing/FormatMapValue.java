package com.turning_leaf_technologies.indexing;

public class FormatMapValue {
	private String value;
	private String format;
	private String formatCategory;
	private int formatBoost;
	private boolean appliesToMatType;
	private boolean appliesToBibLevel;
	private boolean appliesToItemShelvingLocation;
	private boolean appliesToItemSublocation;
	private boolean appliesToItemCollection;
	private boolean appliesToItemType;
	private boolean appliesToItemFormat;
	private boolean appliesToFallbackFormat;

	public String getValue() {
		return value;
	}

	public void setValue(String value) {
		this.value = value;
	}

	public String getFormat() {
		return format;
	}

	public void setFormat(String format) {
		this.format = format;
	}

	public String getFormatCategory() {
		return formatCategory;
	}

	public void setFormatCategory(String formatCategory) {
		this.formatCategory = formatCategory;
	}

	public int getFormatBoost() {
		return formatBoost;
	}

	public void setFormatBoost(int formatBoost) {
		this.formatBoost = formatBoost;
	}

	public boolean isAppliesToMatType() {
		return appliesToMatType;
	}

	public void setAppliesToMatType(boolean appliesToMatType) {
		this.appliesToMatType = appliesToMatType;
	}

	public boolean isAppliesToBibLevel() {
		return appliesToBibLevel;
	}

	public void setAppliesToBibLevel(boolean appliesToBibLevel) {
		this.appliesToBibLevel = appliesToBibLevel;
	}

	public boolean isAppliesToItemShelvingLocation() {
		return appliesToItemShelvingLocation;
	}

	public void setAppliesToItemShelvingLocation(boolean appliesToItemShelvingLocation) {
		this.appliesToItemShelvingLocation = appliesToItemShelvingLocation;
	}

	public boolean isAppliesToItemSublocation() {
		return appliesToItemSublocation;
	}

	public void setAppliesToItemSublocation(boolean appliesToItemSublocation) {
		this.appliesToItemSublocation = appliesToItemSublocation;
	}

	public boolean isAppliesToItemCollection() {
		return appliesToItemCollection;
	}

	public void setAppliesToItemCollection(boolean appliesToItemCollection) {
		this.appliesToItemCollection = appliesToItemCollection;
	}

	public boolean isAppliesToItemType() {
		return appliesToItemType;
	}

	public void setAppliesToItemType(boolean appliesToItemType) {
		this.appliesToItemType = appliesToItemType;
	}

	public boolean isAppliesToItemFormat() {
		return appliesToItemFormat;
	}

	public void setAppliesToItemFormat(boolean appliesToItemFormat) {
		this.appliesToItemFormat = appliesToItemFormat;
	}

	public boolean isAppliesToFallbackFormat() {
		return appliesToFallbackFormat;
	}

	public void setAppliesToFallbackFormat(boolean appliesToFallbackFormat) {
		this.appliesToFallbackFormat = appliesToFallbackFormat;
	}
}
