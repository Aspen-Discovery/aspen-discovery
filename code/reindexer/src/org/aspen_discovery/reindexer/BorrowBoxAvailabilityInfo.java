package org.aspen_discovery.reindexer;

public class BorrowBoxAvailabilityInfo {
	public String siteId;
	public String availabilityStatus;
	public Long nextAvailableDate;
	public long settingId;

	public BorrowBoxAvailabilityInfo(String siteId, String availabilityStatus, Long nextAvailableDate, long settingId) {
		this.siteId = siteId;
		this.availabilityStatus = availabilityStatus;
		this.nextAvailableDate = nextAvailableDate;
		this.settingId = settingId;
	}

	public boolean isAvailable() {
		return "AVAILABLE".equalsIgnoreCase(availabilityStatus);
	}
}
