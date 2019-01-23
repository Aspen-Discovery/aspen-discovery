package org.nashville;

/**
 * Stores information about how patrons are converted between millennium/lss and CARL.X for use during conversion.
 *
 * Created by mnoble on 6/21/2017.
 */
class CarlXPatronMap {
	private String legacyId;
	private String source;
	private String patronId;
	private String patronGuid;

	CarlXPatronMap(String legacyId, String patronId, String patronGuid, String source) {
		this.legacyId = legacyId;
		this.patronId = patronId;
		this.patronGuid = patronGuid;
		this.source = source;
	}

	public String getKey() {
		if (source.equalsIgnoreCase("millennium")){
			//For millennium trim off the .p and the check digit so we get a match and change source to match Pika
			source = "ils";
			legacyId = legacyId.replace(".p", "");
			legacyId = legacyId.substring(0, legacyId.length() -1);
		}else if (source.equalsIgnoreCase("ls")){
			source = "lss";
		}
		return source + "-" + legacyId;
	}

	String getPatronGuid() {
		return patronGuid;
	}

	String getPatronId() {
		return patronId;
	}
}
