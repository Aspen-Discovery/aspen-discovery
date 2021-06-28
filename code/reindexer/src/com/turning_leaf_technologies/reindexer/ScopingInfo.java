package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;

class ScopingInfo{
	private final ItemInfo item;
	private final Scope scope;
	private String status;
	private String groupedStatus;
	private boolean available;
	private boolean holdable;
	private boolean locallyOwned;
	private boolean bookable = false;
	private boolean inLibraryUseOnly;
	private boolean libraryOwned;
	private String localUrl;

	ScopingInfo(Scope scope, ItemInfo item){
		this.item = item;
		this.scope = scope;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
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

	boolean isLocallyOwned() {
		return locallyOwned;
	}

	void setLocallyOwned(boolean locallyOwned) {
		this.locallyOwned = locallyOwned;
	}

	public Scope getScope() {
		return scope;
	}

	void setInLibraryUseOnly(boolean inLibraryUseOnly) {
		this.inLibraryUseOnly = inLibraryUseOnly;
	}

	boolean isLibraryOwned() {
		return libraryOwned;
	}

	void setLibraryOwned(boolean libraryOwned) {
		this.libraryOwned = libraryOwned;
	}

	String scopingDetails = null;
	String getScopingDetails(){
		if (scopingDetails == null) {
			String itemIdentifier = item.getItemIdentifier();
			if (itemIdentifier == null) itemIdentifier = "";
			scopingDetails = item.getFullRecordIdentifier() + "|" +
					itemIdentifier + "|" +
					groupedStatus + "|" +
					status + "|" +
					locallyOwned + "|" +
					available + "|" +
					holdable + "|" +
					bookable + "|" +
					inLibraryUseOnly + "|" +
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
		this.status = scopingInfo.status;
		this.groupedStatus = scopingInfo.groupedStatus;
		this.available = scopingInfo.available;
		this.holdable = scopingInfo.holdable;
		this.locallyOwned = scopingInfo.locallyOwned;
		this.bookable = scopingInfo.bookable;
		this.inLibraryUseOnly = scopingInfo.inLibraryUseOnly;
		this.libraryOwned = scopingInfo.libraryOwned;
		this.localUrl = scopingInfo.localUrl;
	}

	public String getStatus() {
		return status;
	}

	public String getGroupedStatus() {
		return groupedStatus;
	}

	public boolean isHoldable() {
		return holdable;
	}

	public boolean isInLibraryUseOnly() {
		return inLibraryUseOnly;
	}

	public String getLocalUrl() {
		return localUrl;
	}
}
