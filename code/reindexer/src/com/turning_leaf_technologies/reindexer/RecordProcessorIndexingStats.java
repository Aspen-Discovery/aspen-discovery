package com.turning_leaf_technologies.reindexer;

import java.util.ArrayList;

/**
 * Contains statistics for an individual record processor (within a scope)
 *
 * Pika
 * User: Mark Noble
 * Date: 7/25/2015
 * Time: 9:13 PM
 */
public class RecordProcessorIndexingStats {
	public int numRecordsOwned;
	public int numPhysicalItemsOwned;
	public int numOrderItemsOwned;
	public int numEContentOwned;
	public int numRecordsTotal;
	public int numPhysicalItemsTotal;
	public int numOrderItemsTotal;
	public int numEContentTotal;

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
