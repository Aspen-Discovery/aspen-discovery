package com.turning_leaf_technologies.koha_export;

/**
 * Information about an item that has changed within Sierra
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/13/2014
 * Time: 9:47 AM
 */
class ItemChangeInfo {
	private String itemId;
	private String location;
	private int damaged;
	private String itemLost;
	private int withdrawn;
	private int suppress;
	private String restricted;
	private String onLoan;

	String getItemId() {
		return itemId;
	}

	void setItemId(String itemId) {
		this.itemId = itemId;
	}

	int getDamaged() {
		return damaged;
	}

	void setDamaged(int damaged) {
		this.damaged = damaged;
	}

	String getItemLost() {
		return itemLost;
	}

	void setItemLost(String itemLost) {
		this.itemLost = itemLost;
	}

	int getWithdrawn() {
		return withdrawn;
	}

	void setWithdrawn(int withdrawn) {
		this.withdrawn = withdrawn;
	}

	int getSuppress() {
		return suppress;
	}

	void setSuppress(int suppress) {
		this.suppress = suppress;
	}

	String getRestricted() {
		return restricted;
	}

	void setRestricted(String restricted) {
		this.restricted = restricted;
	}

	String getOnLoan() {
		return onLoan;
	}

	void setOnLoan(String onLoan) {
		this.onLoan = onLoan;
	}
}
