package com.turning_leaf_technologies.cloud_library;

class CloudLibraryTitle {
	private long id;
	private String cloudLibraryId;
	private long checksum;
	private boolean deleted;
	private Long availabilityId;

	CloudLibraryTitle(long id, String cloudLibraryId, long checksum, boolean deleted, Long availabilityId) {
		this.id = id;
		this.cloudLibraryId = cloudLibraryId;
		this.checksum = checksum;
		this.deleted = deleted;
		this.availabilityId = availabilityId;
	}

	long getId() {
		return id;
	}

	String getCloudLibraryId() {
		return cloudLibraryId;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}

	Long getAvailabilityId(){
		return availabilityId;
	}

	boolean wasPartOfSettings(){
		return availabilityId != null;
	}
}
