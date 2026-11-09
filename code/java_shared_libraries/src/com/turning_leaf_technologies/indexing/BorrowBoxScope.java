package com.turning_leaf_technologies.indexing;

public class BorrowBoxScope {
	private long id;
	private long settingId;
	private String settingName;
	private String scopeName;
	private boolean includeAdult;
	private boolean includeTeen;
	private boolean includeKids;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public long getSettingId() {
		return settingId;
	}

	void setSettingId(long settingId) {
		this.settingId = settingId;
	}

	public String getSettingName() {
		return settingName;
	}

	public void setSettingName(String settingName) {
		this.settingName = settingName;
	}

	public String getScopeName() {
		return scopeName;
	}

	public void setScopeName(String scopeName) {
		this.scopeName = scopeName;
	}

	public boolean isIncludeAdult() {
		return includeAdult;
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
}
