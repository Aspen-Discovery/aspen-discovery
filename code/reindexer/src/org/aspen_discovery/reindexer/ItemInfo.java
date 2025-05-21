package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.text.SimpleDateFormat;
import java.util.*;

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
	private ItemStatus itemStatus;
	private String detailedStatus;
	private String dueDate;
	private String collection;
	private Date lastCheckinDate;
	private String status;
	private String groupedStatus;
	private boolean available;
	private boolean holdable;
	private boolean bookable = false;
	private boolean inLibraryUseOnly;
	private boolean isVirtualChildRecord;
	private boolean isVirtualHoldingsRecord;

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
		return itemStatus.getOriginalValue();
	}

	public void setItemStatus(ItemStatus itemStatus) {
		this.itemStatus = itemStatus;
		this.detailedStatus = itemStatus.getStatus();
		this.groupedStatus = itemStatus.getGroupedStatus();
	}

	public ItemStatus getItemStatus() {
		return itemStatus;
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

	public String getItemIdentifier() {
		if (itemIdentifier == null) {
			itemIdentifier = recordInfo.getRecordIdentifier() + "-" + recordInfo.getRelatedItems().indexOf(this);
		}
		return itemIdentifier;
	}

	void setItemIdentifier(String itemIdentifier) {
		if (itemIdentifier == null || itemIdentifier.isEmpty()){
			//Don't use empty identifiers
			itemIdentifier = null;
		}else if (itemIdentifier.length() > 255){
			itemIdentifier = itemIdentifier.substring(0, 255).trim();
		}
		this.itemIdentifier = itemIdentifier;
	}

	public String getITypeCode() {
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

	private String primaryFormat;
	private String primaryFormatUppercase;
	public String getPrimaryFormat(){
		if (primaryFormat == null) {
			if (format != null) {
				primaryFormat = format;
			} else {
				primaryFormat = recordInfo.getPrimaryFormat();
			}
			if (primaryFormat != null) {
				primaryFormatUppercase = primaryFormat.toUpperCase();
			}
		}
		return primaryFormat;
	}

	public String getPrimaryFormatUppercase(){
		if (primaryFormat == null) {
			getPrimaryFormat();
		}
		return primaryFormatUppercase;
	}

	public void setFormat(String format) {
		this.format = format;
		this.trimmedFormat = AspenStringUtils.trimTrailingPunctuation(format);
		primaryFormat = null;
	}

	void setSubFormats(String subFormats){
		this.subFormat = subFormats;
	}

	int getNumCopies() {
		//Deal with Libby always available
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

	public boolean isEContent() {
		return isEContent;
	}

	void setIsEContent(boolean isEContent) {
		this.isEContent = isEContent;
	}

	private String baseDetails = null;
	String getDetails(BaseIndexingLogEntry logEntry){
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

	private String formatLastCheckInDate(Date lastCheckinDate, BaseIndexingLogEntry logEntry){
		String formattedLastCheckinDate;
		try {
			//We need to create this each time because the DateTimeFormatter is not ThreadSafe and just synchronizing
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

	String getTrimmedIType(){
		if (this.trimmedIType != null){
			return trimmedIType;
		}else {
			return trimmedFormat;
		}
	}

	void setIType(String IType) {
		this.IType = IType;
		this.trimmedIType = AspenStringUtils.trimTrailingPunctuation(IType);
	}

	String geteContentSource() {
		return eContentSource;
	}

	String getTrimmedEContentSource(){
		return trimmedEContentSource;
	}

	void seteContentSource(String eContentSource) {
		this.eContentSource = eContentSource;
		this.trimmedEContentSource = AspenStringUtils.trimTrailingPunctuation(eContentSource);
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
		String scopeName = scope.getScopeName();
		return scopingInfo.computeIfAbsent(scopeName, k -> new ScopingInfo(scope, this));
	}

	HashMap<String, ScopingInfo> getScopingInfo() {
		return scopingInfo;
	}

	public String getShelfLocationCode() {
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

	public String getSubLocationCode() {
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
		return this.marcField;
	}

	public String getSubfield(char audienceSubfield) {
		if (this.marcField != null) {
			Subfield subfield = this.marcField.getSubfield(audienceSubfield);
			if (subfield == null) {
				return null;
			} else {
				return subfield.getData();
			}
		} else {
			return null;
		}
	}

	public List<String> getSubfields(char subFieldSpec) {
		List<String> subfieldData = new ArrayList<>();
		if (this.marcField != null) {
			List<Subfield> subfields = this.marcField.getSubfields(subFieldSpec);
			for (Subfield subfield : subfields) {
				if (subfield.getData() != null) {
					subfieldData.add(subfield.getData());
				}
			}
		}
		return subfieldData;
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
		this.isVirtualChildRecord = itemInfo.isVirtualChildRecord;
		this.isVirtualHoldingsRecord = itemInfo.isVirtualHoldingsRecord;
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
	}

	private StringBuffer locationOwnedScopes = null;
	private StringBuffer libraryOwnedScopes = null;
	private StringBuffer recordsIncludedScopes = null;
	private HashSet<String> locationOwnedNames = null;
	private HashSet<String> libraryOwnedNames = null;
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
	public HashSet<String> getLocationOwnedNames() {
		if (this.locationOwnedNames == null){
			this.createScopingStrings();
		}
		return locationOwnedNames;
	}

	public HashSet<String> getLibraryOwnedNames() {
		if (this.libraryOwnedNames == null){
			this.createScopingStrings();
		}
		return libraryOwnedNames;
	}

	private void createScopingStrings() {
		locationOwnedScopes = new StringBuffer("~");
		libraryOwnedScopes = new StringBuffer("~");
		recordsIncludedScopes = new StringBuffer("~");
		locationOwnedNames = new HashSet<>();
		libraryOwnedNames = new HashSet<>();
		for (ScopingInfo scope : scopingInfo.values()){
			Scope curScope = scope.getScope();
			if (scope.isLocallyOwned()){
				locationOwnedScopes.append(curScope.getId()).append("~");
				locationOwnedNames.add(curScope.getFacetLabel());
			}else if (scope.isLibraryOwned()){
				libraryOwnedScopes.append(curScope.getId()).append("~");
				libraryOwnedNames.add(curScope.isLibraryScope() ? curScope.getFacetLabel() : curScope.getLibraryScope().getFacetLabel());
			}else {
				recordsIncludedScopes.append(curScope.getId()).append("~");
			}
		}
	}

	private HashSet<String> formatsForIndexing = null;
	public HashSet<String> getFormatsForIndexing() {
		if (formatsForIndexing == null){
			formatsForIndexing = new HashSet<>();
			if (format != null){
				formatsForIndexing.add(format);
			}else{
				formatsForIndexing.addAll(recordInfo.getFormats());
			}
		}
		return formatsForIndexing;
	}

	private HashSet<String> formatCategoriesForIndexing = null;
	public HashSet<String> getFormatCategoriesForIndexing() {
		if (formatCategoriesForIndexing == null){
			formatCategoriesForIndexing = new HashSet<>();
			if (formatCategory != null){
				formatCategoriesForIndexing.add(formatCategory);
			}else{
				formatCategoriesForIndexing.addAll(recordInfo.getFormatCategories());
			}
		}
		return formatCategoriesForIndexing;
	}

	public void setIsVirtualChildRecord(boolean isVirtualChildRecord) {
		this.isVirtualChildRecord = isVirtualChildRecord;
	}

	public boolean isVirtualChildRecord() {
		return isVirtualChildRecord;
	}

	public void setIsVirtualHoldingsRecord(boolean isVirtualHoldingsRecord) {
		this.isVirtualHoldingsRecord = isVirtualHoldingsRecord;
	}

	public boolean isVirtualHoldingsRecord() {
		return isVirtualHoldingsRecord;
	}

	public boolean isVirtual() {
		return isVirtualChildRecord || isVirtualHoldingsRecord;
	}
}
