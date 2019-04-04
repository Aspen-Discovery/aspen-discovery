package com.turning_leaf_technologies.overdrive;

/**
 * Stores minimal information from the database to check if the record is deleted and get existing id
 */
class OverDriveDBInfo {
	private long dbId;
	private boolean deleted;
	private boolean updated;

	boolean isDeleted() {
		return deleted;
	}
	void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	boolean isUpdated() {
		return updated;
	}

	void setUpdated(boolean updated) {
		this.updated = updated;
	}

	long getDbId() {
		return dbId;
	}
	void setDbId(long dbId) {
		this.dbId = dbId;
	}
}
