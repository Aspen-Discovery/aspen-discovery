package com.turning_leaf_technologies.hoopla;

class HooplaTitle {
	private long id;
	private long hooplaId;
	private long checksum;
	private boolean active;
	private long rawResponseLength;
	private boolean foundInExport;

	HooplaTitle(long id, long hooplaId, long checksum, boolean active, long rawResponseLength) {
		this.id = id;
		this.hooplaId = hooplaId;
		this.checksum = checksum;
		this.active = active;
		this.rawResponseLength = rawResponseLength;
	}

	long getId() {
		return id;
	}

	long getHooplaId() {
		return hooplaId;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isActive() {
		return active;
	}

	long getRawResponseLength() {
	    return rawResponseLength;
    }

	public boolean isFoundInExport() {
		return foundInExport;
	}

	public void setFoundInExport(boolean foundInExport) {
		this.foundInExport = foundInExport;
	}
}
