package org.vufind;

/**
 * Information that applies to specific scopes for the item.
 *
 * Pika
 * User: Mark Noble
 * Date: 7/14/2015
 * Time: 9:51 PM
 */
class ScopingInfo {
	private ItemInfo item;
	private Scope scope;
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
}
