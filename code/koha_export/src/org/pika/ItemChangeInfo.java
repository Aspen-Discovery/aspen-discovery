package org.pika;

/**
 * Information about an item that has changed within Sierra
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/13/2014
 * Time: 9:47 AM
 */
public class ItemChangeInfo {
	private String itemId;
	private String location;
	private int damaged;
	private String itemLost;
	private int withdrawn;
	private int suppress;
	private String restricted;
	private String onLoan;

	public String getItemId() {
		return itemId;
	}

	public void setItemId(String itemId) {
		this.itemId = itemId;
	}

	public String getLocation() {
		return location;
	}

	public void setLocation(String location) {
		this.location = location;
	}

	public int getDamaged() {
		return damaged;
	}

	public void setDamaged(int damaged) {
		this.damaged = damaged;
	}

	public String getItemLost() {
		return itemLost;
	}

	public void setItemLost(String itemLost) {
		this.itemLost = itemLost;
	}

	public int getWithdrawn() {
		return withdrawn;
	}

	public void setWithdrawn(int withdrawn) {
		this.withdrawn = withdrawn;
	}

	public int getSuppress() {
		return suppress;
	}

	public void setSuppress(int suppress) {
		this.suppress = suppress;
	}

	public String getRestricted() {
		return restricted;
	}

	public void setRestricted(String restricted) {
		this.restricted = restricted;
	}

	public String getOnLoan() {
		return onLoan;
	}

	public void setOnLoan(String onLoan) {
		this.onLoan = onLoan;
	}
}
