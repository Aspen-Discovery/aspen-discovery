package org.vufind;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;

/**
 * Information about an Item that is being inserted into the index
 * Pika
 * User: Mark Noble
 * Date: 7/11/2015
 * Time: 12:07 AM
 */
public class ItemInfo {
	private String itemIdentifier;
	private String locationCode;
	private String subLocation;
	private String subLocationCode;
	private String format;
	private String subFormat;
	private String formatCategory;
	private int numCopies = 1;
	private boolean isOrderItem;
	private boolean isEContent;
	private String shelfLocation;
	private String callNumber;
	private String sortableCallNumber;
	private Date dateAdded;
	private String IType;
	private String ITypeCode;
	private String eContentSource;
	private String eContentProtectionType;
	private String eContentFilename;
	private String eContentUrl;
	private String statusCode;
	private String detailedStatus;
	private String dueDate;
	private String collection;
	private Date lastCheckinDate;
	private RecordInfo recordInfo;

	private HashMap<String, ScopingInfo> scopingInfo = new HashMap<>();
	private String shelfLocationCode;

	public void setRecordInfo(RecordInfo recordInfo) {
		this.recordInfo = recordInfo;
	}

	public RecordInfo getRecordInfo(){
		return recordInfo;
	}

	public String getCollection() {
		return collection;
	}

	public void setCollection(String collection) {
		this.collection = collection;
	}

	public String getStatusCode() {
		return statusCode;
	}

	void setStatusCode(String statusCode) {
		this.statusCode = statusCode;
	}

	void setDetailedStatus(String detailedStatus) {
		this.detailedStatus = detailedStatus;
	}

	public String getLocationCode() {
		return locationCode;
	}

	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}

	String geteContentUrl() {
		return eContentUrl;
	}

	void seteContentUrl(String eContentUrl) {
		this.eContentUrl = eContentUrl;
	}

	void seteContentFilename(String eContentFilename) {
		this.eContentFilename = eContentFilename;
	}

	String getItemIdentifier() {
		return itemIdentifier;
	}

	void setItemIdentifier(String itemIdentifier) {
		this.itemIdentifier = itemIdentifier;
	}

	String getITypeCode() {
		return ITypeCode;
	}

	void setITypeCode(String ITypeCode) {
		this.ITypeCode = ITypeCode;
	}

	String getDueDate() {
		if (dueDate == null){
			dueDate = "";
		}
		return dueDate;
	}

	void setDueDate(String dueDate) {
		this.dueDate = dueDate;
	}

	String getShelfLocation() {
		return shelfLocation;
	}

	public String getFormat() {
		return format;
	}

	public void setFormat(String format) {
		this.format = format;
	}

	void setSubFormats(String subFormats){
		this.subFormat = subFormats;
	}

	int getNumCopies() {
		//Deal with OverDrive always available
		if (numCopies > 1000){
			return 1;
		}else {
			return numCopies;
		}
	}

	void setNumCopies(int numCopies) {
		this.numCopies = numCopies;
	}

	boolean isOrderItem() {
		return isOrderItem;
	}

	void setIsOrderItem(boolean isOrderItem) {
		this.isOrderItem = isOrderItem;
	}

	boolean isEContent() {
		return isEContent;
	}

	void setIsEContent(boolean isEContent) {
		this.isEContent = isEContent;
	}

	private SimpleDateFormat lastCheckinDateFormatter = new SimpleDateFormat("MMM dd, yyyy");
	private String baseDetails = null;
	String getDetails(){
		if (baseDetails == null){
			String formattedLastCheckinDate = "";
			if (lastCheckinDate != null){
				formattedLastCheckinDate = lastCheckinDateFormatter.format(lastCheckinDate);
			}
			//Cache the part that doesn't change depending on the scope
			baseDetails = recordInfo.getFullIdentifier() + "|" +
					Util.getCleanDetailValue(itemIdentifier) + "|" +
					Util.getCleanDetailValue(shelfLocation) + "|" +
					Util.getCleanDetailValue(callNumber) + "|" +
					Util.getCleanDetailValue(format) + "|" +
					Util.getCleanDetailValue(formatCategory) + "|" +
					numCopies + "|" +
					isOrderItem + "|" +
					isEContent + "|" +
					Util.getCleanDetailValue(eContentSource) + "|" +
					Util.getCleanDetailValue(eContentFilename) + "|" +
					Util.getCleanDetailValue(eContentUrl) + "|" +
					Util.getCleanDetailValue(subFormat) + "|" +
					Util.getCleanDetailValue(detailedStatus) + "|" +
					Util.getCleanDetailValue(formattedLastCheckinDate) + "|" +
					Util.getCleanDetailValue(locationCode) + "|" +
					Util.getCleanDetailValue(subLocation) + "|";
		}
		return baseDetails;
	}

	Date getDateAdded() {
		return dateAdded;
	}

	public void setDateAdded(Date dateAdded) {
		this.dateAdded = dateAdded;
	}

	String getIType() {
		return IType;
	}

	void setIType(String IType) {
		this.IType = IType;
	}

	String geteContentSource() {
		return eContentSource;
	}

	void seteContentSource(String eContentSource) {
		this.eContentSource = eContentSource;
	}

	String geteContentProtectionType() {
		return eContentProtectionType;
	}

	void seteContentProtectionType(String eContentProtectionType) {
		this.eContentProtectionType = eContentProtectionType;
	}

	String getCallNumber() {
		return callNumber;
	}

	void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}


	String getSortableCallNumber() {
		return sortableCallNumber;
	}

	void setSortableCallNumber(String sortableCallNumber) {
		this.sortableCallNumber = sortableCallNumber;
	}

	String getFormatCategory() {
		return formatCategory;
	}

	public void setFormatCategory(String formatCategory) {
		this.formatCategory = formatCategory;
	}

	void setShelfLocation(String shelfLocation) {
		this.shelfLocation = shelfLocation;
	}

	ScopingInfo addScope(Scope scope) {
		ScopingInfo scopeInfo;
		if (scopingInfo.containsKey(scope.getScopeName())){
			scopeInfo = scopingInfo.get(scope.getScopeName());
		}else{
			scopeInfo = new ScopingInfo(scope, this);
			scopingInfo.put(scope.getScopeName(), scopeInfo);
		}
		return scopeInfo;
	}

	HashMap<String, ScopingInfo> getScopingInfo() {
		return scopingInfo;
	}

	boolean isValidForScope(Scope scope){
		return scopingInfo.containsKey(scope.getScopeName());
	}

	boolean isValidForScope(String scopeName){
		return scopingInfo.containsKey(scopeName);
	}

	boolean isLocallyOwned(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isLocallyOwned()){
				return true;
			}
		}
		return false;
	}

	boolean isLibraryOwned(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isLibraryOwned()){
				return true;
			}
		}
		return false;
	}

	boolean isLocallyOwned(String scopeName) {
		ScopingInfo scopeData = scopingInfo.get(scopeName);
		if (scopeData != null){
			if (scopeData.isLocallyOwned()){
				return true;
			}
		}
		return false;
	}

	boolean isLibraryOwned(String scopeName) {
		ScopingInfo scopeData = scopingInfo.get(scopeName);
		if (scopeData != null){
			if (scopeData.isLibraryOwned()){
				return true;
			}
		}
		return false;
	}

	String getShelfLocationCode() {
		return shelfLocationCode;
	}

	void setShelfLocationCode(String shelfLocationCode) {
		this.shelfLocationCode = shelfLocationCode;
	}

	String getFullRecordIdentifier() {
		return recordInfo.getFullIdentifier();
	}

	String getSubLocation() {
		return subLocation;
	}

	void setSubLocation(String subLocation) {
		this.subLocation = subLocation;
	}

	String getSubLocationCode() {
		return subLocationCode;
	}

	void setSubLocationCode(String subLocationCode) {
		this.subLocationCode = subLocationCode;
	}

	Date getLastCheckinDate() {
		return lastCheckinDate;
	}

	void setLastCheckinDate(Date lastCheckinDate) {
		this.lastCheckinDate = lastCheckinDate;
	}
}
