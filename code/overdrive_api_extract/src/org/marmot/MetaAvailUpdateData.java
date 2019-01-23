package org.marmot;

/**
 * Stores information about a record that needs to be updated
 *
 * Created by mnoble on 10/31/2017.
 */
class MetaAvailUpdateData {
	public long databaseId;
	public long crossRefId;
	public long lastMetadataCheck;
	public long lastMetadataChange;
	public long lastAvailabilityChange;
	public String overDriveId;

	public boolean metadataUpdated = false;

	public boolean hadAvailabilityErrors = false;
	public boolean hadMetadataErrors = false;

}
