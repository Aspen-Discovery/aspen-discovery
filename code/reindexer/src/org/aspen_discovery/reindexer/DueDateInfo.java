package org.aspen_discovery.reindexer;

import java.util.Date;

class DueDateInfo {

	private String itemId;
	private Date dueDate;

	void setItemId(String itemId) {
		this.itemId = itemId;
	}

	String getItemId() {
		return itemId;
	}

	void setDueDate(Date dueDate) {
		this.dueDate = dueDate;
	}

	Date getDueDate() {
		return dueDate;
	}
}
