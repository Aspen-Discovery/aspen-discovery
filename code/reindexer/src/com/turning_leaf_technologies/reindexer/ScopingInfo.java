package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;

class ScopingInfo{
	private final ItemInfo item;
	private final Scope scope;
	private boolean locallyOwned;
	private boolean libraryOwned;
	private String localUrl;

	ScopingInfo(Scope scope, ItemInfo item){
		this.item = item;
		this.scope = scope;
	}

	boolean isLocallyOwned() {
		return locallyOwned;
	}

	void setLocallyOwned(boolean locallyOwned) {
		if (locallyOwned) {
			this.locallyOwned = locallyOwned;
		}
	}

	public Scope getScope() {
		return scope;
	}


	boolean isLibraryOwned() {
		return libraryOwned;
	}

	void setLibraryOwned(boolean libraryOwned) {
		if (libraryOwned){
			this.libraryOwned = libraryOwned;
		}
	}

	String scopingDetails = null;
	String getScopingDetails(){
		if (scopingDetails == null) {
			String itemIdentifier = item.getItemIdentifier();
			if (itemIdentifier == null) itemIdentifier = "";
			scopingDetails = item.getFullRecordIdentifier() + "|" +
					itemIdentifier + "|" +
					item.getGroupedStatus() + "|" +
					item.getDetailedStatus() + "|" +
					locallyOwned + "|" +
					item.isAvailable() + "|" +
					item.isHoldable() + "|" +
					item.isBookable() + "|" +
					item.isInLibraryUseOnly() + "|" +
					libraryOwned + "|" +
					"|" + //holdable PTypes (removed)
					"|" + //bookable PTypes (removed)
					Util.getCleanDetailValue(localUrl) + "|"
					;
		}
		return scopingDetails;
	}

	void setLocalUrl(String localUrl) {
		this.localUrl = localUrl;
	}

	void copyFrom(ScopingInfo scopingInfo) {
		this.locallyOwned = scopingInfo.locallyOwned;
		this.libraryOwned = scopingInfo.libraryOwned;
		this.localUrl = scopingInfo.localUrl;
	}

	public String getLocalUrl() {
		return localUrl;
	}

	public ItemInfo getItem(){
		return item;
	}
}
