package com.turning_leaf_technologies.overdrive;

class MetaAvailUpdateData {
	long databaseId;
	long crossRefId;
	long lastMetadataCheck;
	long lastMetadataChange;
	long lastAvailabilityChange;
	String overDriveId;

	boolean metadataUpdated = false;

	boolean hadAvailabilityErrors = false;
	boolean hadMetadataErrors = false;

}
