package com.turning_leaf_technologies.reindexer;

import org.marc4j.marc.Record;

public class AppendItemsToRecordResult {
	private GroupedWorkIndexer.MarcStatus marcRecordStatus;
	private Record mergedRecord;
	public AppendItemsToRecordResult(GroupedWorkIndexer.MarcStatus marcRecordStatus, Record mergedRecord) {
		this.marcRecordStatus = marcRecordStatus;
		this.mergedRecord = mergedRecord;
	}

	public GroupedWorkIndexer.MarcStatus getMarcStatus() {
		return marcRecordStatus;
	}

	public Record getMergedRecord(){
		return mergedRecord;
	}
}
