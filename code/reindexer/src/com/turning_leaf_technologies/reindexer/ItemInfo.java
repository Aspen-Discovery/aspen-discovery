package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;

public class ItemInfo{
	private String itemIdentifier;
	private String locationCode;
	private String subLocation;
	private String subLocationCode;
	private String format;
	private String trimmedFormat;
	private String subFormat;
	private String formatCategory;
	private int numCopies = 1;
	private boolean isOrderItem;
	private boolean isEContent;
	private String shelfLocation;
	private String detailedLocation;
	private String callNumber;
	private String sortableCallNumber;
	private Date dateAdded;
	private String IType;
	private String trimmedIType;
	private String ITypeCode;
	private String eContentSource;
	private String trimmedEContentSource;
	private String eContentFilename;
	private String eContentUrl;
	private String statusCode;
	private String detailedStatus;
	private String dueDate;
	private String collection;
	private Date lastCheckinDate;
	private String volumeField;
	private String status;
	private String groupedStatus;
	private boolean available;
	private boolean holdable;
	private boolean bookable = false;
	private boolean inLibraryUseOnly;

	private RecordInfo recordInfo;

	private final HashMap<String, ScopingInfo> scopingInfo = new HashMap<>();
	private String shelfLocationCode;
	private Long autoReindexTime = null;
	private DataField marcField;

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

	@SuppressWarnings("SpellCheckingInspection")
	String geteContentUrl() {
		return eContentUrl;
	}

	@SuppressWarnings("SpellCheckingInspection")
	void seteContentUrl(String eContentUrl) {
		this.eContentUrl = eContentUrl;
	}

	@SuppressWarnings("SpellCheckingInspection")
	void seteContentFilename(String eContentFilename) {
		this.eContentFilename = eContentFilename;
	}

	String getItemIdentifier() {
		if (itemIdentifier == null || itemIdentifier.length() == 0) {
			return recordInfo.getRecordIdentifier() + "-" + recordInfo.getRelatedItems().indexOf(this);
		}else {
			return itemIdentifier;
		}
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

	String getDetailedLocation(){
		return detailedLocation;
	}

	public String getFormat() {
		return format;
	}

	public void setFormat(String format) {
		this.format = format;
		this.trimmedFormat = StringUtils.trimTrailingPunctuation(format);
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

	void setIsOrderItem() {
		this.isOrderItem = true;
	}

	boolean isEContent() {
		return isEContent;
	}

	void setIsEContent(boolean isEContent) {
		this.isEContent = isEContent;
	}

	private String baseDetails = null;
	String getDetails(BaseLogEntry logEntry){
		if (baseDetails == null){
			String formattedLastCheckinDate = "";
			if (lastCheckinDate != null){
				formattedLastCheckinDate = formatLastCheckInDate(lastCheckinDate, logEntry);
			}
			//Cache the part that doesn't change depending on the scope
			baseDetails = recordInfo.getFullIdentifier() + "|" +
					Util.getCleanDetailValue(getItemIdentifier()) + "|" +
					Util.getCleanDetailValue(detailedLocation) + "|" +
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

	private String formatLastCheckInDate(Date lastCheckinDate, BaseLogEntry logEntry){
		String formattedLastCheckinDate;
		try {
			//We need to create this each time because the DateTimeFomatter is not ThreadSafe and just synchronizing
			// this method is not working. Eventually, we can convert everything that uses Date to Java 8's new Date classes
			SimpleDateFormat lastCheckinDateFormatter = new SimpleDateFormat("MMM dd, yyyy");
			formattedLastCheckinDate = lastCheckinDateFormatter.format(lastCheckinDate);
		}catch (Exception e){
			logEntry.incErrors("Error formatting check in date for " + lastCheckinDate, e);
			formattedLastCheckinDate = "";
		}
		return formattedLastCheckinDate;
	}

	Date getDateAdded() {
		return dateAdded;
	}

	public void setDateAdded(Date dateAdded) {
		this.dateAdded = dateAdded;
	}

	String getIType() {
		if (this.IType != null){
			return IType;
		}else {
			return format;
		}
	}

	String getTrimmedIType(){
		if (this.trimmedIType != null){
			return trimmedIType;
		}else {
			return trimmedFormat;
		}
	}

	void setIType(String IType) {
		this.IType = IType;
		this.trimmedIType = StringUtils.trimTrailingPunctuation(IType);
	}

	@SuppressWarnings("SpellCheckingInspection")
	String geteContentSource() {
		return eContentSource;
	}

	String getTrimmedEContentSource(){
		return trimmedEContentSource;
	}

	@SuppressWarnings("SpellCheckingInspection")
	void seteContentSource(String eContentSource) {
		this.eContentSource = eContentSource;
		this.trimmedEContentSource = StringUtils.trimTrailingPunctuation(eContentSource);
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

	void setDetailedLocation(String detailedLocation) {
		this.detailedLocation = detailedLocation;
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

	boolean isValidForScope(String scopeName){
		return scopingInfo.containsKey(scopeName);
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

	@SuppressWarnings("unused")
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

	void setAutoReindexTime(Long reindexTime) {
		this.autoReindexTime = reindexTime;
	}

	Long getAutoReindexTime(){
		return autoReindexTime;
	}

	void setMarcField(DataField itemField) {
		this.marcField = itemField;
	}

	DataField getMarcField() {
		return  this.marcField;
	}

	public String getSubfield(char audienceSubfield) {
		Subfield subfield = this.marcField.getSubfield(audienceSubfield);
		if (subfield == null){
			return null;
		}else{
			return subfield.getData();
		}
	}

	void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
	}

	public String getGroupedStatus() {
		return this.groupedStatus;
	}

	public boolean isAvailable() {
		return available;
	}

	public void setAvailable(boolean available) {
		this.available = available;
	}

	void setHoldable(boolean holdable) {
		this.holdable = holdable;
	}

	public boolean isHoldable() {
		return holdable;
	}

	public boolean isBookable() {
		return false;
	}

	void setInLibraryUseOnly(boolean inLibraryUseOnly) {
		this.inLibraryUseOnly = inLibraryUseOnly;
	}

	public boolean isInLibraryUseOnly() {
		return inLibraryUseOnly;
	}

	public void copyFrom(ItemInfo itemInfo) {
		this.itemIdentifier = itemInfo.itemIdentifier;
		this.locationCode = itemInfo.locationCode;
		this.subLocation = itemInfo.subLocation;
		this.subLocationCode = itemInfo.subLocationCode;
		this.format = itemInfo.format;
		this.formatCategory = itemInfo.formatCategory;
		this.numCopies = itemInfo.numCopies;
		this.isOrderItem = itemInfo.isOrderItem;
		this.isEContent = itemInfo.isEContent;
		this.shelfLocation = itemInfo.shelfLocation;
		this.detailedLocation = itemInfo.detailedLocation;
		this.callNumber = itemInfo.callNumber;
		this.sortableCallNumber = itemInfo.sortableCallNumber;
		this.dateAdded = itemInfo.dateAdded;
		this.IType = itemInfo.IType;
		this.ITypeCode = itemInfo.ITypeCode;
		this.eContentSource = itemInfo.eContentSource;
		this.eContentFilename = itemInfo.eContentFilename;
		this.eContentUrl = itemInfo.eContentUrl;
		this.statusCode = itemInfo.statusCode;
		this.detailedStatus = itemInfo.detailedStatus;
		this.dueDate = itemInfo.dueDate;
		this.collection = itemInfo.collection;
		this.lastCheckinDate = itemInfo.lastCheckinDate;
		this.shelfLocationCode = itemInfo.shelfLocationCode;
		this.autoReindexTime = itemInfo.autoReindexTime;
		this.marcField = itemInfo.marcField;
		this.status = itemInfo.status;
		this.groupedStatus = itemInfo.groupedStatus;
		this.available = itemInfo.available;
		this.holdable = itemInfo.holdable;
		this.bookable = itemInfo.bookable;
		this.inLibraryUseOnly = itemInfo.inLibraryUseOnly;
		for (String scope : itemInfo.scopingInfo.keySet()){
			ScopingInfo curScopingInfo = itemInfo.scopingInfo.get(scope);
			ScopingInfo clonedScope = addScope(curScopingInfo.getScope());
			clonedScope.copyFrom(curScopingInfo);
		}
	}

	public String getDetailedStatus() {
		return detailedStatus;
	}

	public void setVolumeField(String volumeField) {
		this.volumeField = volumeField;
	}

	public String getVolumeField() {
		return this.volumeField;
	}

	private StringBuffer locationOwnedScopes = null;
	private StringBuffer libraryOwnedScopes = null;
	private StringBuffer recordsIncludedScopes = null;
	public String getLocationOwnedScopes() {
		if (this.locationOwnedScopes == null){
			this.createScopingStrings();
		}
		return locationOwnedScopes.toString();
	}

	public String getLibraryOwnedScopes() {
		if (this.libraryOwnedScopes == null){
			this.createScopingStrings();
		}
		return libraryOwnedScopes.toString();
	}

	public String getRecordsIncludedScopes() {
		if (this.recordsIncludedScopes == null){
			this.createScopingStrings();
		}
		return recordsIncludedScopes.toString();
	}

	private void createScopingStrings() {
		locationOwnedScopes = new StringBuffer("~");
		libraryOwnedScopes = new StringBuffer("~");
		recordsIncludedScopes = new StringBuffer("~");
		for (ScopingInfo scope : scopingInfo.values()){
			if (scope.isLocallyOwned()){
				locationOwnedScopes.append(scope.getScope().getId()).append("~");
			}else if (scope.isLibraryOwned()){
				libraryOwnedScopes.append(scope.getScope().getId()).append("~");
			}else {
				recordsIncludedScopes.append(scope.getScope().getId()).append("~");
			}
		}
	}



}
