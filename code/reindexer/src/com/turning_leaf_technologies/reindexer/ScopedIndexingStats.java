package com.turning_leaf_technologies.reindexer;

import java.util.ArrayList;
import java.util.TreeMap;

public class ScopedIndexingStats {
	private String scopeName;
	int numLocalWorks;
	int numTotalWorks;
	TreeMap<String, RecordProcessorIndexingStats> recordProcessorIndexingStats = new TreeMap<>();

	ScopedIndexingStats(String scopeName, ArrayList<String> recordProcessorNames) {
		this.scopeName = scopeName;
		for (String processorName : recordProcessorNames){
			recordProcessorIndexingStats.put(processorName, new RecordProcessorIndexingStats());
		}
	}

	String getScopeName() {
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
		return dataFields.toArray(new String[0]);
	}
}
