package org.aspen_discovery.reindexer;

public class SeriesMemberVolume {
	private String volume;
	private boolean deleted;
	private boolean userAdded;
	private boolean foundInIndex;

	public SeriesMemberVolume() {

	}

	public SeriesMemberVolume(String volume, boolean deleted, boolean userAdded) {
		this.volume = volume;
		this.deleted = deleted;
		this.userAdded = userAdded;
	}

	public String getVolume() {
		return volume;
	}

	public void setVolume(String volume) {
		this.volume = volume;
	}

	public boolean isDeleted() {
		return deleted;
	}

	public void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	public boolean isUserAdded() {
		return userAdded;
	}

	public void setUserAdded(boolean userAdded) {
		this.userAdded = userAdded;
	}

	public boolean isFoundInIndex() {
		return foundInIndex;
	}

	public void setFoundInIndex(boolean foundInIndex) {
		this.foundInIndex = foundInIndex;
	}
}
