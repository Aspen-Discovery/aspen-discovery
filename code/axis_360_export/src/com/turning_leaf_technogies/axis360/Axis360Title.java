package com.turning_leaf_technogies.axis360;

import java.util.HashSet;

class Axis360Title {
	private long id;
	private String axis360Id;
	private long checksum;
	private boolean processed;
	private boolean deleted;

	private HashSet<Long> activeSettings = new HashSet<>(); //The settings (libraries) the title is active in

	Axis360Title(long id, String axis360Id, long checksum, boolean deleted) {
		this.id = id;
		this.axis360Id = axis360Id;
		this.checksum = checksum;
		this.deleted = deleted;
	}

	long getId() {
		return id;
	}

	String getAxis360Id() {
		return axis360Id;
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

	boolean isProcessed() {
		return processed;
	}

	void setProcessed(boolean processed) {
		this.processed = processed;
	}
}
