package org.vufind;

import java.util.Date;

/**
 * Created by mnoble on 8/8/2017.
 */
class DueDateInfo {

	private String itemId;
	private Date dueDate;

	public void setItemId(String itemId) {
		this.itemId = itemId;
	}

	public String getItemId() {
		return itemId;
	}

	public void setDueDate(Date dueDate) {
		this.dueDate = dueDate;
	}

	public Date getDueDate() {
		return dueDate;
	}
}
