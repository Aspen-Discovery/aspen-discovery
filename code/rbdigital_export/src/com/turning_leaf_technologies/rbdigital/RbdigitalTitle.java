package com.turning_leaf_technologies.rbdigital;

import java.util.HashSet;

class RbdigitalTitle {
	private long id;
	private String rbdigitalId;
	private long checksum;
	private boolean deleted;

	private HashSet<Long> activeSettings = new HashSet<>(); //The settings (libraries) the title is active in

	RbdigitalTitle(long id, String rbdigitalId, long checksum, boolean deleted) {
		this.id = id;
		this.rbdigitalId = rbdigitalId;
		this.checksum = checksum;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	String getRbdigitalId() {
		return rbdigitalId;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}

	void addSetting(long settingId) {
		this.activeSettings.add(settingId);
	}

	void removeSetting(long id) {
		this.activeSettings.remove(id);
	}

	int getNumSettings(){
		return this.activeSettings.size();
	}
}
