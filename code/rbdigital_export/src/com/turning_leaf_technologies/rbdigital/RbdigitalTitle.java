package com.turning_leaf_technologies.rbdigital;

class RbdigitalTitle {
	private long id;
	private String rbdigitalId;
	private long checksum;
	private boolean deleted;

	RbdigitalTitle(long id, String rbdigitalId, long checksum, boolean deleted) {
		this.id = id;
		this.rbdigitalId = rbdigitalId;
		this.checksum = checksum;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	String getRbdigitalId() {
		return rbdigitalId;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}
}
