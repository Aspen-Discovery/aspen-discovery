package com.turning_leaf_technologies.overdrive;

class OverDriveDBInfo {
	private long dbId;
	private boolean deleted;

	boolean isDeleted() {
		return deleted;
	}
	void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	long getDbId() {
		return dbId;
	}
	void setDbId(long dbId) {
		this.dbId = dbId;
	}
}
