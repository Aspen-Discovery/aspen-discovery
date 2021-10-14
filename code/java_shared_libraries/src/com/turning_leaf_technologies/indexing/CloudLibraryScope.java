package com.turning_leaf_technologies.indexing;

public class CloudLibraryScope {
	private long id;
	private long settingId;
	private String name;
	private boolean includeEBooks;
	private boolean includeEAudiobook;
	private boolean restrictToChildrensMaterial;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public boolean isIncludeEBooks() {
		return includeEBooks;
	}

	void setIncludeEBooks(boolean includeEBooks) {
		this.includeEBooks = includeEBooks;
	}

	public boolean isIncludeEAudiobook() {
		return includeEAudiobook;
	}

	void setIncludeEAudiobook(boolean includeEAudiobook) {
		this.includeEAudiobook = includeEAudiobook;
	}

	public boolean isRestrictToChildrensMaterial() {
		return restrictToChildrensMaterial;
	}

	void setRestrictToChildrensMaterial(boolean restrictToChildrensMaterial) {
		this.restrictToChildrensMaterial = restrictToChildrensMaterial;
	}

	public long getSettingId() {
		return settingId;
	}

	public void setSettingId(long settingId) {
		this.settingId = settingId;
	}

}
