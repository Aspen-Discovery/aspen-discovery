package org.marmot;

public class OverDriveDBMetaData {
	private long id = -1;
	private long checksum;
	private boolean hasRawData;

	public boolean hasRawData() {
		return hasRawData;
	}

	public void setHasRawData(boolean hasRawData) {
		this.hasRawData = hasRawData;
	}

	public long getId() {
		return id;
	}
	public void setId(long id) {
		this.id = id;
	}
	public long getChecksum() {
		return checksum;
	}
	public void setChecksum(long checksum) {
		this.checksum = checksum;
	}


}
