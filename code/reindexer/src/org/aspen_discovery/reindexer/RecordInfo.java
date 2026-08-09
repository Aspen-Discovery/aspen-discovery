package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.GroupedWorkDisplaySettings;
import com.turning_leaf_technologies.indexing.Scope;
import org.apache.solr.common.SolrInputDocument;

import java.util.*;
import java.util.regex.Pattern;

public class RecordInfo {
	private long databaseId;
	private String source;
	private String subSource;
	private String recordIdentifier;

	//Formats exist at both the item and record level because
	//Various systems define them in both ways.
	private HashSet<String> formats = new HashSet<>();
	// A record can have multiple format categories if the items within it have different formats when
	// format is defined based on properties for the item rather than being defined at the bib record
	// When this happens,
	private HashSet<String> formatCategories = new HashSet<>();
	private long formatBoost = 1;

	private String edition;
	private String audience;
	private String primaryLanguage;
	private final HashSet<String> languages = new HashSet<>();
	protected HashSet<String> translations = new HashSet<>();
	protected Long languageBoost = 1L;
	protected Long languageBoostSpanish = 1L;
	private String publisher;
	private String publicationDate;
	private String placeOfPublication;
	private String physicalDescription;
	private Integer duration;
	private boolean isClosedCaptioned;

	private boolean hasParentRecord;
	private boolean hasChildRecord;
	private boolean notForLoan;

	private final ArrayList<ItemInfo> relatedItems = new ArrayList<>();

	public RecordInfo(String source, String recordIdentifier){
		this.source = source;
		this.recordIdentifier = recordIdentifier;
	}

	public String getSource(){
		return this.source;
	}

	void setSubSource(String subSource) {
		this.subSource = subSource;
	}

	public String getSubSource(){
		return this.subSource;
	}

	public long getFormatBoost() {
		return formatBoost;
	}

	public void setFormatBoost(long formatBoost) {
		if (formatBoost > this.formatBoost) {
			this.formatBoost = formatBoost;
		}
	}

	void setEdition(String edition) {
		this.edition = edition;
	}

	public String getEdition(){
		return edition;
	}

	void setAudience(String audience) {
		this.audience = audience;
	}

	public String getAudience() {
		return audience;
	}

	void setPrimaryLanguage(String primaryLanguage) {
		this.primaryLanguage = primaryLanguage;
	}

	String getPrimaryLanguage(){
		return primaryLanguage;
	}

	void setLanguageBoost(Long languageBoost) {
		if (languageBoost > this.languageBoost) {
			this.languageBoost = languageBoost;
		}
	}

	void setLanguageBoostSpanish(Long languageBoostSpanish) {
		if (languageBoostSpanish > this.languageBoostSpanish) {
			this.languageBoostSpanish = languageBoostSpanish;
		}
	}

	public void addLanguage(String language) {
		this.languages.add(language);
		if (this.primaryLanguage == null) {
			this.setPrimaryLanguage(language);
		}
	}

	void setLanguages(HashSet<String> languages) {
		this.languages.addAll(languages);
		if (this.primaryLanguage == null) {
			setPrimaryLanguage(languages.iterator().next());
		}
	}

	void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	public String getPublisher() {
		return this.publisher;
	}

	void setPlaceOfPublication(String placeOfPublication) {
		this.placeOfPublication = placeOfPublication;
	}

	public String getPlaceOfPublication() {
		return this.placeOfPublication;
	}

	void setPublicationDate(String publicationDate) {
		this.publicationDate = publicationDate;
	}

	public String getPublicationDate() {
		return this.publicationDate;
	}

	void setPhysicalDescription(String physicalDescription) {
		this.physicalDescription = physicalDescription;
	}

	void setDuration(Integer duration) {
		this.duration = duration;
	}

	ArrayList<ItemInfo> getRelatedItems() {
		return relatedItems;
	}

	void setRecordIdentifier(String source, String recordIdentifier) {
		this.source = source;
		this.recordIdentifier = recordIdentifier;
	}

	public String getRecordIdentifier() {
		return recordIdentifier;
	}

	private String recordDetails = null;
	String getDetails() {
		if (recordDetails == null) {
			//None of this changes by scope, so we can just form it once and then return the previous value
			recordDetails = this.getFullIdentifier() + "|" +
					getPrimaryFormat() + "|" +
					getPrimaryFormatCategory() + "|" +
					Util.getCleanDetailValue(edition) + "|" +
					Util.getCleanDetailValue(audience) + "|" +
					Util.getCleanDetailValue(primaryLanguage) + "|" +
					Util.getCleanDetailValue(publisher) + "|" +
					Util.getCleanDetailValue(placeOfPublication) + "|" +
					Util.getCleanDetailValue(publicationDate) + "|" +
					Util.getCleanDetailValue(physicalDescription)
					;
		}
		return recordDetails;
	}

	private String primaryFormat = null;
	private String primaryFormatCategory = null;
	String getPrimaryFormat() {
		if (primaryFormat == null){
			HashMap<String, Integer> relatedFormats = new HashMap<>();
			HashMap<String, String> formatToFormatCategory = new HashMap<>();
			for (ItemInfo curItem : relatedItems){
				if (curItem.getFormat() != null && !curItem.getFormat().isEmpty()) {
					if (relatedFormats.containsKey(curItem.getFormat())){
						relatedFormats.merge(curItem.getFormat(), 1, Integer::sum);
					}else{
						relatedFormats.put(curItem.getFormat(), relatedFormats.getOrDefault(curItem.getFormat(), 1));
					}
					formatToFormatCategory.put(curItem.getFormat(), curItem.getFormatCategory());
				}
			}

			HashMap.Entry<String, Integer> FormatCounter = null; //need to sort through both string and integer to compare things properly
			String mostUsedFormat = null; //Set most used format to null before iterating through the hashmap

			//for each entry set in relatedFormats
			for (HashMap.Entry<String, Integer> curItem : relatedFormats.entrySet())
			{
				//if current item format count is greater than FormatCounter, set this as mostUsedFormat
				if (FormatCounter == null || curItem.getValue().compareTo(FormatCounter.getValue()) > 0)
				{
					FormatCounter = curItem;
					mostUsedFormat = curItem.getKey(); //get and set the most used format from entrySet with getKey()
				}
			}

			if (mostUsedFormat == null){
				//If we have formats for the record, use that. We only get here if we have no item formats.
				if (!formats.isEmpty()) {
					primaryFormat = formats.iterator().next();
				}

				//This might not be correct if we have multiple formats since the format category could be different
				//for each.
				if (!formatCategories.isEmpty()){
					primaryFormatCategory = formatCategories.iterator().next();
				}
			}else{
				primaryFormat = mostUsedFormat;
				primaryFormatCategory = formatToFormatCategory.get(mostUsedFormat);
			}
		}

		return primaryFormat;
	}

	public String getPrimaryFormatCategory() {
		if (primaryFormatCategory == null) {
			HashMap<String, Integer> relatedFormats = new HashMap<>();
			for (String format : formatCategories) {
				relatedFormats.put(format, 1);
			}
			for (ItemInfo curItem : relatedItems) {
				if (curItem.getFormatCategory() != null) {
					relatedFormats.put(curItem.getFormatCategory(), relatedFormats.getOrDefault(curItem.getFormatCategory(), 1));
				}
			}
			int timesUsed = 0;
			String mostUsedFormat = null;
			for (String curFormat : relatedFormats.keySet()) {
				if (relatedFormats.get(curFormat) > timesUsed) {
					mostUsedFormat = curFormat;
					timesUsed = relatedFormats.get(curFormat);
				}
			}
			primaryFormatCategory = Objects.requireNonNullElse(mostUsedFormat, "Unknown");
		}
		return primaryFormatCategory;
	}

	public void addItem(ItemInfo itemInfo) {
		relatedItems.add(itemInfo);
		itemInfo.setRecordInfo(this);
	}

	HashSet<String> getFormats() {
		return formats;
	}

	String getFirstFormat() {
		if (!formats.isEmpty()) {
			for (String format : formats) {
				return format;
			}
		}
		return null;
	}

	HashSet<String> getFormatCategories() {
		return formatCategories;
	}

	String getFirstFormatCategory() {
		if (!formatCategories.isEmpty()) {
			for (String formatCategory : formatCategories) {
				return formatCategory;
			}
		}
		return null;
	}

	int getNumCopiesOnOrder() {
		int numOrders = 0;
		for (ItemInfo curItem : relatedItems){
			if (curItem.isOrderItem()){
				numOrders += curItem.getNumCopies();
			}
		}
		return numOrders;
	}

	String getFullIdentifier() {
		String fullIdentifier;
		if (subSource != null && !subSource.isEmpty()){
			fullIdentifier = source + ":" + subSource + ":" + recordIdentifier;
		}else{
			fullIdentifier = source + ":" + recordIdentifier;
		}
		return fullIdentifier;
	}

	int getNumPrintCopies() {
		int numPrintCopies = 0;
		for (ItemInfo curItem : relatedItems){
			if (!curItem.isOrderItem() && !curItem.isEContent()){
				numPrintCopies += curItem.getNumCopies();
			}
		}
		return numPrintCopies;
	}

	int getNumEContentCopies() {
		int numEContentCopies = 0;
		for (ItemInfo curItem : relatedItems){
			if (curItem.isEContent()){
				numEContentCopies += curItem.getNumCopies();
			}
		}
		return numEContentCopies;
	}

	public int getNumVirtualItems() {
		int numVirtualItems = 0;
		for (ItemInfo curItem : relatedItems){
			if (curItem.isVirtual()){
				numVirtualItems++;
			}
		}
		return numVirtualItems;
	}

	HashSet<String> getAllEContentSources() {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.geteContentSource());
		}
		return values;
	}

	HashSet<String> getAllCallNumbers(){
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.getCallNumber());
		}
		return values;
	}

	void addFormats(HashSet<String> translatedFormats) {
		this.formats.addAll(translatedFormats);
	}

	void addFormat(String translatedFormat){
		this.formats.add(translatedFormat);
	}

	void addFormatCategories(HashSet<String> translatedFormatCategories) {
		this.formatCategories.addAll(translatedFormatCategories);
	}

	void addFormatCategory(String translatedFormatCategory){
		this.formatCategories.add(translatedFormatCategory);
	}

	boolean hasItemFormats() {
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormat() != null){
				return true;
			}
		}
		return false;
	}

	void getAutoReindexTimes(HashSet<Long> autoReindexTimes) {
		for (ItemInfo curItem : relatedItems){
			if (curItem.getAutoReindexTime() != null){
				autoReindexTimes.add(curItem.getAutoReindexTime());
			}
		}
	}

	void copyFrom(RecordInfo recordInfo){
		//noinspection unchecked
		this.formats = (HashSet<String>) recordInfo.formats.clone();
		//noinspection unchecked
		this.formatCategories = (HashSet<String>)recordInfo.formatCategories.clone();
		this.formatBoost = recordInfo.formatBoost;
		this.edition = recordInfo.edition;
		this.audience = recordInfo.audience;
		this.primaryLanguage = recordInfo.primaryLanguage;
		this.publisher = recordInfo.publisher;
		this.placeOfPublication = recordInfo.placeOfPublication;
		this.publicationDate = recordInfo.publicationDate;
		this.physicalDescription = recordInfo.physicalDescription;
		this.duration = recordInfo.duration;
		this.isClosedCaptioned = recordInfo.isClosedCaptioned;
		for (ItemInfo itemInfo : recordInfo.relatedItems) {
			ItemInfo clonedItem = new ItemInfo();
			addItem(clonedItem);
			clonedItem.copyFrom(itemInfo);
		}
	}

	public String getPhysicalDescription() {
		return physicalDescription;
	}

	public Integer getDuration() {
		if (duration != null) {
			return duration;
		}
		return 0;
	}

	public boolean isClosedCaptioned() {
		return isClosedCaptioned;
	}

	public void setClosedCaptioned(boolean closedCaptioned) {
		isClosedCaptioned = closedCaptioned;
	}

	public boolean allItemsHaveFormats() {
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormat() == null){
				return false;
			}
		}
		return true;
	}

	public HashSet<String> getUniqueItemFormats() {
		HashSet<String> uniqueItemFormats = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormat() != null){
				uniqueItemFormats.add(curItem.getFormat());
			}
		}
		return uniqueItemFormats;
	}

	public HashSet<String> getUniqueItemFormatsCategories() {
		HashSet<String> uniqueItemFormatCategories = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormat() != null){
				uniqueItemFormatCategories.add(curItem.getFormatCategory());
			}
		}
		return uniqueItemFormatCategories;
	}

	public boolean hasParentRecord() {
		return hasParentRecord;
	}

	public void setHasParentRecord(boolean hasParentRecord) {
		this.hasParentRecord = hasParentRecord;
	}

	public boolean hasChildRecord() {
		return hasChildRecord;
	}

	public void setHasChildRecord(boolean hasChildRecord) {
		this.hasChildRecord = hasChildRecord;
	}

	/**
	 * Checks if the record has a not-for-loan status; that is, it is not on-shelf or checked-out, where
	 * if it is checked-out, that means it is loanable.
	 *
	 * @return {@code true} if the item is on order, {@code false} otherwise.
	 */
	public boolean hasNotForLoanStatus() {
		return notForLoan;
	}

	/**
	 * Sets the on-order status of the record.
	 *
	 * @param notForLoan {@code true} to indicate the item is on order, {@code false} otherwise.
	 */
	public void setNotForLoan(boolean notForLoan) {
		this.notForLoan = notForLoan;
	}

	@SuppressWarnings("DuplicatedCode")
	public ArrayList<SolrInputDocument> getRecordScopeSolrDocuments(GroupedWorkIndexer groupedWorkIndexer, Long daysAddedSincePubDate) {
		if (databaseId == -1) {
			//This did not save for some reason
			return null;
		}

		HashSet<String> relatedScopes = getRelatedScopes();
		if (relatedScopes.isEmpty()) {
			return null;
		}

		ArrayList<SolrInputDocument> recordSolrScopeDocuments = new ArrayList<>();
		HashSet<String> formatsForRecord = getFormats();
		HashSet<String> formatCategoriesForRecord = getFormatCategories();
		if (formatsForRecord.contains("eAudiobook")) {
			formatCategoriesForRecord.add("eBook");
		}
		if (formatsForRecord.contains("CD + Book")) {
			formatCategoriesForRecord.add("Books");
			formatCategoriesForRecord.add("#udio Books");
		}
		if (formatsForRecord.contains("VOX Books")) {
			formatCategoriesForRecord.add("Books");
			formatCategoriesForRecord.add("Audio Books");
		}

		for (String scopeName : relatedScopes) {
			Scope curScope = groupedWorkIndexer.getScopes().get(scopeName);
			if (curScope == null) {
				continue;
			}
			SolrInputDocument recordDoc = new SolrInputDocument();
			recordDoc.setField("id", "record_" + databaseId + "_" + scopeName);
			recordDoc.setField("_nest_path_", "/record_scoping");
			recordDoc.setField("recordtype", "record_scoping");
			recordDoc.setField("scope", scopeName);
			recordDoc.setField("format", formatsForRecord);
			recordDoc.setField("format_category", formatCategoriesForRecord);

			AvailabilityToggleInfo availabilityToggleValuesForScope = new AvailabilityToggleInfo();
			int availableCopiesForScope = 0;
			Long daysSinceAddedForScope = null;
			String sortableCallNumberForScope = null;
			int libraryBoostForScope = 0;

			String scopeFacetLabel = curScope.getFacetLabel();
			GroupedWorkDisplaySettings scopeDisplaySettings = curScope.getGroupedWorkDisplaySettings();

			for (ItemInfo curItem : relatedItems) {
				ScopingInfo scopingInfo = curItem.getScopingInfo().get(scopeName);
				if (scopingInfo == null) {
					continue;
				}
				Long daysSinceAdded = curItem.getDaysSinceAdded(daysAddedSincePubDate);
				String trimmedIType = curItem.getTrimmedIType();

				if (curItem.getFormat() != null) {
					formatsForRecord.add(curItem.getFormat());
				}
				if (curItem.getFormatCategory() != null) {
					formatsForRecord.add(curItem.getFormatCategory());
				}

				boolean addAllOwningLocations = false;
				boolean addAllOwningLocationsToAvailableAt = false;
				boolean locallyOwned = scopingInfo.isLocallyOwned();
				boolean libraryOwned = scopingInfo.isLibraryOwned();
				boolean isAvailable = curItem.isAvailable();
				boolean isEContent = curItem.isEContent();

				if (isEContent) {
					String trimmedEContentSource = curItem.getTrimmedEContentSource();
					if (trimmedEContentSource == null) {
						trimmedEContentSource = "Unknown";
					}
					if (trimmedEContentSource.equals("overdrive")) {
						trimmedEContentSource = "Libby";
					}
					availabilityToggleValuesForScope.updateToggleValues(locallyOwned || libraryOwned, scopeDisplaySettings.isIncludeOnlineMaterialsInAvailableToggle() && isAvailable, isAvailable);
					recordDoc.addField("owning_library", trimmedEContentSource);
					if (isAvailable) {
						recordDoc.addField("available_at", trimmedEContentSource);
						availableCopiesForScope += curItem.getNumCopies();
					}
					recordDoc.addField("econtent_source", trimmedEContentSource);
				} else { //physical materials
					if (locallyOwned) {
						availabilityToggleValuesForScope.updateToggleValues(locallyOwned, isAvailable, false);
						if (isAvailable) {
							recordDoc.addField("available_at", scopeFacetLabel);
						}
						recordDoc.addField("owning_location", scopeFacetLabel);
						recordDoc.addField("owning_library", curScope.isLibraryScope() ? scopeFacetLabel : curScope.getLibraryScope().getFacetLabel());
						if (curScope.isIncludeAllLibraryBranchesInFacets()) {
							//Include other branches of this library that own the title within the owning locations
							//isIncludeAllLibraryBranchesInFacets is only a setting at the location level
							addAllOwningLocations = true;
						}
					}
					if (libraryOwned) {
						if (curScope.isLibraryScope() || (curScope.isLocationScope() && !scopeDisplaySettings.isBaseAvailabilityToggleOnLocalHoldingsOnly())) {
							availabilityToggleValuesForScope.updateToggleValues(libraryOwned, isAvailable, false);
						}
						if (isAvailable) {
							addAllOwningLocationsToAvailableAt = true;
						}
						recordDoc.addField("owning_library", scopeFacetLabel);
						addAllOwningLocations = true;
					}
					if (!locallyOwned && !libraryOwned && !scopeDisplaySettings.isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
						availabilityToggleValuesForScope.updateToggleValues(false, isAvailable, false);
						if (isAvailable) {
							addAllOwningLocationsToAvailableAt = true;
						}
					}
					if (isAvailable && curScope.getAdditionalLocationsToShowAvailabilityForPattern() != null && curItem.getLocationCode() != null) {
						//We might include the item in the owning and availability facets if it matched the available locations
						if (curScope.getAdditionalLocationsToShowAvailabilityForPattern().matcher(curItem.getLocationCode()).matches()) {
							addAllOwningLocationsToAvailableAt = true;
						}
					}

					if (!curScope.isRestrictOwningLibraryAndLocationFacets() || curScope.isConsortialCatalog()) {
						for (String libraryOwnedName : curItem.getLibraryOwnedNames()) {
							recordDoc.addField("owning_library", libraryOwnedName);
						}
						addAllOwningLocations = true;
					}
					if (isAvailable) {
						availableCopiesForScope += curItem.getNumCopies();
					}
				}

				if (addAllOwningLocations) {
					recordDoc.addField("owning_location", curItem.getLocationOwnedNames());
				}
				if (addAllOwningLocationsToAvailableAt) {
					recordDoc.addField("available_at", curItem.getLocationOwnedNames());
				}

				if (locallyOwned || libraryOwned || scopeDisplaySettings.isIncludeAllRecordsInShelvingFacets()) {
					String collection = curItem.getCollection();
					if (collection != null && !collection.isEmpty()) {
						recordDoc.addField("collection", curItem.getCollection());
					}
					String detailedLocation = curItem.getDetailedLocation();
					if (detailedLocation != null && !detailedLocation.isEmpty()) {
						recordDoc.addField("detailed_location", curItem.getDetailedLocation());
					}
					String shelfLocation = curItem.getShelfLocation();
					if (shelfLocation != null && !shelfLocation.isEmpty() && (scopeDisplaySettings.includeEContentInShelvingLocations() || !isEContent)) {
						recordDoc.addField("shelf_location", curItem.getShelfLocation());
					}
				}

				if (isEContent || locallyOwned || libraryOwned || scopeDisplaySettings.isIncludeAllRecordsInDateAddedFacets()) {
					if (daysSinceAddedForScope == null || daysSinceAdded > daysSinceAddedForScope) {
						daysSinceAddedForScope = daysSinceAdded;
					}
				}

				if (locallyOwned || libraryOwned) {
					if (isAvailable) {
						if (libraryBoostForScope < groupedWorkIndexer.availableAtBoostValue) {
							libraryBoostForScope = groupedWorkIndexer.availableAtBoostValue;
						}
					} else {
						if (libraryBoostForScope < groupedWorkIndexer.ownedByBoostValue) {
							libraryBoostForScope = groupedWorkIndexer.ownedByBoostValue;
						}
					}
				}

				if (trimmedIType != null) {
					recordDoc.addField("itype", trimmedIType);
				}

				if (locallyOwned || libraryOwned || !scopingInfo.getScope().isRestrictOwningLibraryAndLocationFacets()) {
					recordDoc.addField("local_callnumber", curItem.getCallNumber());
					if (sortableCallNumberForScope == null) {
						sortableCallNumberForScope = curItem.getSortableCallNumber();
					}
				}
			} // End looping through items

			recordDoc.addField("availability_toggle", availabilityToggleValuesForScope.getValues());
			if (curScope.getLocationsToExcludeAvailabilityForPattern() != null) {
				//Filter available At by locationsToExcludeAvailabilityFor
				if (recordDoc.getField("available_at") != null) {
					ArrayList<String> availableAtFiltered = filterCollection(recordDoc.getField("available_at").getValues(), curScope.getLocationsToExcludeAvailabilityForPattern());
					recordDoc.setField("available_at", availableAtFiltered);
				}
			}
			if (daysSinceAddedForScope != null) {
				recordDoc.addField("local_days_since_added", daysSinceAddedForScope);
				recordDoc.addField("local_time_since_added", DateUtils.getTimeSinceAdded(daysSinceAddedForScope));
			}
			recordDoc.addField("lib_boost", libraryBoostForScope);
			recordDoc.addField("available_copies", availableCopiesForScope);
			recordDoc.addField("callnumber_sort", sortableCallNumberForScope);

			recordSolrScopeDocuments.add(recordDoc);
		}

		return recordSolrScopeDocuments;
	}

	private ArrayList<String> filterCollection(Collection<Object> collectionToFilter, Pattern valuesToSkip) {
		ArrayList<String> filteredCollection = new ArrayList<>();
		for (Object valueToAdd : collectionToFilter){
			if (valueToAdd instanceof String) {
				String valueToAddStr = (String)valueToAdd;
				if (valuesToSkip == null || !valuesToSkip.matcher(valueToAddStr).matches()) {
					filteredCollection.add(valueToAddStr);
				}
			}
		}
		return filteredCollection;
	}


	public void setDatabaseId(long recordId) {
		this.databaseId = recordId;
	}

	public HashSet<String> getRelatedScopes() {
		HashSet<String> relatedScopes = new HashSet<>();
		for (ItemInfo itemInfo : relatedItems) {
			relatedScopes.addAll(itemInfo.getScopingInfo().keySet());
		}
		return relatedScopes;
	}
}
