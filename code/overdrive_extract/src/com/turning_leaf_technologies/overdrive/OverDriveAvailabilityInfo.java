package com.turning_leaf_technologies.overdrive;

class OverDriveAvailabilityInfo {
	//Information from the database
	private long id;
	private long libraryId;
	private long settingId;
	private boolean available;
	private int copiesOwned;
	private int copiesAvailable;
	private int numberOfHolds;
	private String availabilityType;

	//runtime
	private boolean newAvailabilityLoaded = false;

	long getId() {
		return id;
	}

	void setId(long id) {
		this.id = id;
	}

	long getLibraryId() {
		return libraryId;
	}

	void setLibraryId(long libraryId) {
		this.libraryId = libraryId;
	}

	boolean isAvailable() {
		return available;
	}

	void setAvailable(boolean available) {
		this.available = available;
	}

	int getCopiesOwned() {
		return copiesOwned;
	}

	void setCopiesOwned(int copiesOwned) {
		this.copiesOwned = copiesOwned;
	}

	int getCopiesAvailable() {
		return copiesAvailable;
	}

	void setCopiesAvailable(int copiesAvailable) {
		this.copiesAvailable = copiesAvailable;
	}

	int getNumberOfHolds() {
		return numberOfHolds;
	}

	void setNumberOfHolds(int numberOfHolds) {
		this.numberOfHolds = numberOfHolds;
	}

	String getAvailabilityType() {
		return availabilityType;
	}

	void setAvailabilityType(String availabilityType) {
		this.availabilityType = availabilityType;
	}

	boolean isNewAvailabilityLoaded() {
		return newAvailabilityLoaded;
	}

	void setNewAvailabilityLoaded() {
		this.newAvailabilityLoaded = true;
	}

	public long getSettingId() {
		return settingId;
	}

	void setSettingId(long settingId) {
		this.settingId = settingId;
	}
}
