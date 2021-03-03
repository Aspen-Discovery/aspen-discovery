package com.turning_leaf_technologies.reindexer;

import java.util.HashMap;
import java.util.HashSet;
import java.util.TreeMap;
import java.util.regex.Pattern;

public class RecordInfo {
	private String source;
	private String subSource;
	private String recordIdentifier;

	//Formats exist at both the item and record level because
	//Various systems define them in both ways.
	private HashSet<String> formats = new HashSet<>();
	private HashSet<String> formatCategories = new HashSet<>();
	private long formatBoost = 1;

	private String edition;
	private String primaryLanguage;
	private String publisher;
	private String publicationDate;
	private String physicalDescription;

	private final HashSet<ItemInfo> relatedItems = new HashSet<>();
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

	void setPrimaryLanguage(String primaryLanguage) {
		this.primaryLanguage = primaryLanguage;
	}

	String getPrimaryLanguage(){
		return primaryLanguage;
	}

	void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	void setPublicationDate(String publicationDate) {
		this.publicationDate = publicationDate;
	}

	void setPhysicalDescription(String physicalDescription) {
		this.physicalDescription = physicalDescription;
	}

	HashSet<ItemInfo> getRelatedItems() {
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
			//None of this changes by scope so we can just form it once and then return the previous value
			recordDetails = this.getFullIdentifier() + "|" +
					getPrimaryFormat() + "|" +
					getPrimaryFormatCategory() + "|" +
					Util.getCleanDetailValue(edition) + "|" +
					Util.getCleanDetailValue(primaryLanguage) + "|" +
					Util.getCleanDetailValue(publisher) + "|" +
					Util.getCleanDetailValue(publicationDate) + "|" +
					Util.getCleanDetailValue(physicalDescription)
					;
		}
		return recordDetails;
	}

	private String primaryFormat = null;
	String getPrimaryFormat() {
		if (primaryFormat == null){
			HashMap<String, Integer> relatedFormats = new HashMap<>();
			for (String format : formats){
				relatedFormats.put(format, 1);
			}
			for (ItemInfo curItem : relatedItems){
				if (curItem.getFormat() != null) {
					relatedFormats.put(curItem.getFormat(), relatedFormats.getOrDefault(curItem.getFormat(), 1));
				}
			}
			int timesUsed = 0;
			String mostUsedFormat = null;
			for (String curFormat : relatedFormats.keySet()){
				if (relatedFormats.get(curFormat) > timesUsed){
					mostUsedFormat = curFormat;
					timesUsed = relatedFormats.get(curFormat);
				}
			}
			if (mostUsedFormat == null){
				return "Unknown";
			}else{
				primaryFormat = mostUsedFormat;
			}
		}

		return primaryFormat;
	}

	private String getPrimaryFormatCategory() {
		HashMap<String, Integer> relatedFormats = new HashMap<>();
		for (String format : formatCategories){
			relatedFormats.put(format, 1);
		}
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormatCategory() != null) {
				relatedFormats.put(curItem.getFormatCategory(), relatedFormats.getOrDefault(curItem.getFormatCategory(), 1));
			}
		}
		int timesUsed = 0;
		String mostUsedFormat = null;
		for (String curFormat : relatedFormats.keySet()){
			if (relatedFormats.get(curFormat) > timesUsed){
				mostUsedFormat = curFormat;
				timesUsed = relatedFormats.get(curFormat);
			}
		}
		if (mostUsedFormat == null){
			return "Unknown";
		}
		return mostUsedFormat;
	}

	public void addItem(ItemInfo itemInfo) {
		relatedItems.add(itemInfo);
		itemInfo.setRecordInfo(this);
	}

	private HashSet<String> allFormats = null;
	private static final Pattern nonWordPattern = Pattern.compile("\\W");
	HashSet<String> getAllSolrFieldEscapedFormats() {
		if (allFormats == null){
			allFormats = new HashSet<>();
			for (String curFormat : formats){
				allFormats.add(nonWordPattern.matcher(curFormat).replaceAll("_").toLowerCase());
			}
			for (ItemInfo curItem : relatedItems){
				if (curItem.getFormat() != null) {
					allFormats.add(nonWordPattern.matcher(curItem.getFormat()).replaceAll("_").toLowerCase());
				}
			}
		}
		return allFormats;
	}

	HashSet<String> getFormats() {
		return formats;
	}

	private HashSet<String> allFormatCategories = null;
	HashSet<String> getAllSolrFieldEscapedFormatCategories() {
		if (allFormatCategories == null) {
			allFormatCategories = new HashSet<>();
			for (String curFormat : formatCategories){
				allFormatCategories.add(nonWordPattern.matcher(curFormat).replaceAll("_").toLowerCase());
			}
			for (ItemInfo curItem : relatedItems) {
				if (curItem.getFormatCategory() != null) {
					allFormatCategories.add(nonWordPattern.matcher(curItem.getFormatCategory()).replaceAll("_").toLowerCase());
				}
			}
		}
		return allFormatCategories;
	}

	HashSet<String> getFormatCategories() {
		return formatCategories;
	}

	private HashSet<ItemInfo> getRelatedItemsForScope(String scopeName) {
		HashSet<ItemInfo> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			if (curItem.isValidForScope(scopeName)){
				values.add(curItem);
			}
		}
		return values;
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
		if (subSource != null && subSource.length() > 0){
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

	void clearFormats(){
		this.formats.clear();;
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

	void updateIndexingStats(TreeMap<String, ScopedIndexingStats> indexingStats) {
		for (ScopedIndexingStats scopedStats : indexingStats.values()){
			String recordProcessor = this.subSource == null ? this.source : this.subSource;
			RecordProcessorIndexingStats stats = scopedStats.recordProcessorIndexingStats.get(recordProcessor.toLowerCase());
			if (stats == null) {
				continue;
			}
			HashSet<ItemInfo> itemsForScope = getRelatedItemsForScope(scopedStats.getScopeName());
			if (itemsForScope.size() > 0) {
				stats.numRecordsTotal++;
				boolean recordLocallyOwned = false;
				for (ItemInfo curItem : itemsForScope){
					//Check the type (physical, eContent, on order)
					boolean locallyOwned = curItem.isLocallyOwned(scopedStats.getScopeName())
							|| curItem.isLibraryOwned(scopedStats.getScopeName());
					if (locallyOwned){
						recordLocallyOwned = true;
					}
					if (curItem.isEContent()){
						stats.numEContentTotal += curItem.getNumCopies();
						if (locallyOwned){
							stats.numEContentOwned += curItem.getNumCopies();
						}
					}else if (curItem.isOrderItem()){
						stats.numOrderItemsTotal += curItem.getNumCopies();
						if (locallyOwned){
							stats.numOrderItemsOwned += curItem.getNumCopies();
						}
					}else{
						stats.numPhysicalItemsTotal += curItem.getNumCopies();
						if (locallyOwned){
							stats.numPhysicalItemsOwned += curItem.getNumCopies();
						}
					}
				}
				if (recordLocallyOwned){
					stats.numRecordsOwned++;
				}
			}
		}
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
		this.primaryLanguage = recordInfo.primaryLanguage;
		this.publisher = recordInfo.publisher;
		this.publicationDate = recordInfo.publicationDate;
		this.physicalDescription = recordInfo.physicalDescription;
		//noinspection unchecked
		for (ItemInfo itemInfo : recordInfo.relatedItems) {
			ItemInfo clonedItem = new ItemInfo();
			addItem(clonedItem);
			clonedItem.copyFrom(itemInfo);
		}
	}
}
