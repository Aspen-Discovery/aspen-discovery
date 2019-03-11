package com.turning_leaf_technologies.reindexer;

import java.util.ArrayList;
import java.util.TreeMap;

/**
 * Store stats about what has been indexed for each scope.
 *
 * Pika
 * User: Mark Noble
 * Date: 3/2/2015
 * Time: 7:14 PM
 */
public class ScopedIndexingStats {
	private String scopeName;
	public int numLocalWorks;
	public int numTotalWorks;
	public TreeMap<String, RecordProcessorIndexingStats> recordProcessorIndexingStats = new TreeMap<String, RecordProcessorIndexingStats>();

	public ScopedIndexingStats(String scopeName, ArrayList<String> recordProcessorNames) {
		this.scopeName = scopeName;
		for (String processorName : recordProcessorNames){
			recordProcessorIndexingStats.put(processorName, new RecordProcessorIndexingStats());
		}
	}

	public String getScopeName() {
		return scopeName;
	}

	public String[] getData() {
		ArrayList<String> dataFields = new ArrayList<>();
		dataFields.add(scopeName);
		dataFields.add(Integer.toString(numLocalWorks));
		dataFields.add(Integer.toString(numTotalWorks));
		for (RecordProcessorIndexingStats indexingStats : recordProcessorIndexingStats.values()){
			indexingStats.getData(dataFields);
		}
		return dataFields.toArray(new String[dataFields.size()]);
	}
}
