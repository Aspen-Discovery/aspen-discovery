package org.aspendiscovery.palace_project;

class PalaceProjectTitle {
	private long id;
	private String palaceProjectId;
	private long checksum;
	private long rawResponseLength;
	private boolean foundInExport;

	private String collectionName;

	PalaceProjectTitle(long id, String palaceProjectId, String collectionName, long checksum, long rawResponseLength) {
		this.id = id;
		this.palaceProjectId = palaceProjectId;
		this.collectionName = collectionName;
		this.checksum = checksum;
		this.rawResponseLength = rawResponseLength;
	}

	long getId() {
		return id;
	}

	String getPalaceProjectId() {
		return palaceProjectId;
	}

	long getChecksum() {
		return checksum;
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
