package org.aspendiscovery.omeka;

class OmekaTitle {
	private final long id;
	private final long omekaId;
	private final long checksum;
	private final long rawResponseLength;
	private final boolean deleted;
	private boolean foundInExport;

	OmekaTitle(long id, long omekaId, long checksum, long rawResponseLength, boolean deleted) {
		this.id = id;
		this.omekaId = omekaId;
		this.checksum = checksum;
		this.rawResponseLength = rawResponseLength;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	long getOmekaId() {
		return omekaId;
	}

	long getChecksum() {
		return checksum;
	}

	long getRawResponseLength() {
		return rawResponseLength;
	}

	boolean isDeleted() {
		return deleted;
	}

	boolean isFoundInExport() {
		return foundInExport;
	}

	void setFoundInExport(boolean foundInExport) {
		this.foundInExport = foundInExport;
	}
}
