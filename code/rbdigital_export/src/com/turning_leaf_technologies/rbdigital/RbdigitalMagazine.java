package com.turning_leaf_technologies.rbdigital;

class RbdigitalMagazine {
	private long id;
	private String magazineId;
	private long checksum;
	private boolean deleted;

	RbdigitalMagazine(long id, String magazineId, long checksum, boolean deleted) {
		this.id = id;
		this.magazineId = magazineId;
		this.checksum = checksum;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	String getMagazineId() {
		return magazineId;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}
}
