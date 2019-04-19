package com.turning_leaf_technologies.reindexer;

import java.util.ArrayList;

public class RecordProcessorIndexingStats {
	int numRecordsOwned;
	int numPhysicalItemsOwned;
	int numOrderItemsOwned;
	int numEContentOwned;
	int numRecordsTotal;
	int numPhysicalItemsTotal;
	int numOrderItemsTotal;
	int numEContentTotal;

	public void getData(ArrayList<String> dataFields) {
		dataFields.add(Integer.toString(numRecordsOwned));
		dataFields.add(Integer.toString(numPhysicalItemsOwned));
		dataFields.add(Integer.toString(numOrderItemsOwned));
		dataFields.add(Integer.toString(numEContentOwned));
		dataFields.add(Integer.toString(numRecordsTotal));
		dataFields.add(Integer.toString(numPhysicalItemsTotal));
		dataFields.add(Integer.toString(numOrderItemsTotal));
		dataFields.add(Integer.toString(numEContentTotal));
	}
}
