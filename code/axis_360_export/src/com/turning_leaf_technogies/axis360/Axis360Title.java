package com.turning_leaf_technogies.axis360;

import java.util.HashSet;

class Axis360Title {
	private long id;
	private String axis360Id;
	private long checksum;
	private boolean processed;
	private boolean deleted;

	Axis360Title(long id, String axis360Id, long checksum, boolean deleted) {
		this.id = id;
		this.axis360Id = axis360Id;
		this.checksum = checksum;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	String getAxis360Id() {
		return axis360Id;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}

	boolean isProcessed() {
		return processed;
	}

	void setProcessed(boolean processed) {
		this.processed = processed;
	}
}
