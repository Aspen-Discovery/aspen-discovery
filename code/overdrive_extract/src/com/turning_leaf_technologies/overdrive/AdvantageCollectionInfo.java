package com.turning_leaf_technologies.overdrive;

import java.util.HashSet;

class AdvantageCollectionInfo {
	private int advantageId;
	private String collectionToken;
	private HashSet<Long> aspenLibraryIds = new HashSet<>();
	private String name;

	int getAdvantageId() {
		return advantageId;
	}

	void setAdvantageId(int advantageId) {
		this.advantageId = advantageId;
	}

	String getCollectionToken() {
		return collectionToken;
	}

	void setCollectionToken(String collectionToken) {
		this.collectionToken = collectionToken;
	}

	HashSet<Long> getAspenLibraryIds() {
		return aspenLibraryIds;
	}

	void addAspenLibraryId(long aspenLibraryId) {
		this.aspenLibraryIds.add(aspenLibraryId);
	}

	String getName() {
		return name;
	}

	void setName(String name) {
		this.name = name;
	}
}
