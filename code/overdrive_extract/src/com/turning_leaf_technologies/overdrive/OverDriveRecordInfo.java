package com.turning_leaf_technologies.overdrive;

import java.util.HashSet;

/**
 * Full data from the API
 */
class OverDriveRecordInfo {
	boolean hasChanges = false;
	boolean isNew = false;

	//Data from base title call
	private String id;
	private long databaseId = -1;
	private HashSet<AdvantageCollectionInfo> collections = new HashSet<>();
	private boolean deleted;

	String getId() {
		return id;
	}
	void setId(String id) {
		this.id = id.toLowerCase();
	}

	HashSet<AdvantageCollectionInfo> getCollections() {
		return collections;
	}
	void addCollection(AdvantageCollectionInfo collectionInfo) {
		this.collections.add(collectionInfo);
	}

	long getDatabaseId() {
		return databaseId;
	}

	void setDatabaseId(long databaseId) {
		this.databaseId = databaseId;
	}

	public void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	public boolean isDeleted() {
		return deleted;
	}
}
