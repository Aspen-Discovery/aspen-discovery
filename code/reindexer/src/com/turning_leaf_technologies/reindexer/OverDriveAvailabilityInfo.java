package com.turning_leaf_technologies.reindexer;

public class OverDriveAvailabilityInfo {
	public int numberOfHolds;
	public long libraryId;
	public boolean available;
	public int copiedOwned;

	public OverDriveAvailabilityInfo(int numberOfHolds, long libraryId, boolean available, int copiesOwned) {
		this.numberOfHolds = numberOfHolds;
		this.libraryId = libraryId;
		this.available = available;
		this.copiedOwned = copiesOwned;
	}
}
