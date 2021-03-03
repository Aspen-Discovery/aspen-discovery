package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;

class ScopingInfo{
	private ItemInfo item;
	private final Scope scope;
	private String status;
	private String groupedStatus;
	private boolean available;
	private boolean holdable;
	private boolean locallyOwned;
	private boolean bookable;
	private boolean inLibraryUseOnly;
	private boolean libraryOwned;
	private String holdablePTypes;
	private String bookablePTypes;
	private String localUrl;

	ScopingInfo(Scope scope, ItemInfo item){
		this.item = item;
		this.scope = scope;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	void setHoldablePTypes(String holdablePTypes) {
		this.holdablePTypes = holdablePTypes;
	}

	void setBookablePTypes(String bookablePTypes) {
		this.bookablePTypes = bookablePTypes;
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

	void setBookable(boolean bookable) {
		this.bookable = bookable;
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

	String getScopingDetails(){
		String itemIdentifier = item.getItemIdentifier();
		if (itemIdentifier == null) itemIdentifier = "";
		return item.getFullRecordIdentifier() + "|" +
				itemIdentifier + "|" +
				groupedStatus + "|" +
				status + "|" +
				locallyOwned + "|" +
				available + "|" +
				holdable + "|" +
				bookable + "|" +
				inLibraryUseOnly + "|" +
				libraryOwned + "|" +
				Util.getCleanDetailValue(holdablePTypes) + "|" +
				Util.getCleanDetailValue(bookablePTypes) + "|" +
				Util.getCleanDetailValue(localUrl) + "|"
				;
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
		this.holdablePTypes = scopingInfo.holdablePTypes;
		this.bookablePTypes = scopingInfo.bookablePTypes;
		this.localUrl = scopingInfo.localUrl;
	}
}
