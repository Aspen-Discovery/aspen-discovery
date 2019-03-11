package com.turning_leaf_technologies.overdrive;

class OverDriveDBMetaData {
	private long id = -1;
	private long checksum;
	private boolean hasRawData;

	boolean hasRawData() {
		return hasRawData;
	}

	void setHasRawData(boolean hasRawData) {
		this.hasRawData = hasRawData;
	}

	long getId() {
		return id;
	}
	void setId(long id) {
		this.id = id;
	}
	long getChecksum() {
		return checksum;
	}
	void setChecksum(long checksum) {
		this.checksum = checksum;
	}
}
