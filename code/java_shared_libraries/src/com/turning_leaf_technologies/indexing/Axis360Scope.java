package com.turning_leaf_technologies.indexing;

public class Axis360Scope {
	private long id;
	private String name;
	private long settingId;
	private boolean includeAdult;
	private boolean includeTeen;
	private boolean includeKids;

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

	public long getSettingId() {
		return settingId;
	}

	public void setSettingId(long settingId) {
		this.settingId = settingId;
	}

	void setIncludeAdult(boolean includeAdult) {
		this.includeAdult = includeAdult;
	}

	public boolean isIncludeTeen() {
		return includeTeen;
	}

	void setIncludeTeen(boolean includeTeen) {
		this.includeTeen = includeTeen;
	}

	public boolean isIncludeKids() {
		return includeKids;
	}

	void setIncludeKids(boolean includeKids) {
		this.includeKids = includeKids;
	}

	public boolean isIncludeAdult() {
		return includeAdult;
	}
}
