package org.aspendiscovery.palace_project;

class PalaceProjectTitle {
	private long id;
	private String palaceProjectId;
	private long checksum;
	private long rawResponseLength;
	private boolean foundInExport;

	PalaceProjectTitle(long id, String palaceProjectId, long checksum, long rawResponseLength) {
		this.id = id;
		this.palaceProjectId = palaceProjectId;
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
