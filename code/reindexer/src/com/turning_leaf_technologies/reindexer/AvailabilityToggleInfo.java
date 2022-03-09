package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

public class AvailabilityToggleInfo {
	public boolean local;
	public boolean available;
	public boolean availableOnline;

	public HashSet<String> getValues(){
		HashSet<String> values = new HashSet<>();
		values.add(globalStr);
		if (local) {
			values.add(localStr);
		}
		if (available){
			values.add(availableStr);
		}
		if (availableOnline){
			values.add(availableOnlineStr);
		}
		return values;
	}

	private final static String globalStr = "global";
	private final static String localStr = "local";
	private final static String availableStr = "available";
	private final static String availableOnlineStr = "available_online";
}
