package com.turning_leaf_technologies.cron.sierra;

class OfflineCirculationResult {
	private boolean success;
	private String note;

	boolean isSuccess() {
		return success;
	}

	void setSuccess(boolean success) {
		this.success = success;
	}

	String getNote() {
		return note;
	}

	void setNote(String note) {
		this.note = note;
	}
}
