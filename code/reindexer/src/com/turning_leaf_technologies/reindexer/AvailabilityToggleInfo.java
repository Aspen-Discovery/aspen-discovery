package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

public class AvailabilityToggleInfo {
	public boolean local;
	public boolean available;
	public boolean availableOnline;

	public HashSet<String> getValues(){
		if (local) {
			if (available) {
				if (availableOnline) {
					return globalLocalAvailableOnline;
				} else {
					return globalLocalAvailable;
				}
			} else {
				if (availableOnline) {
					return globalLocalOnline;
				} else {
					return globalLocal;
				}
			}
		}else {
			if (available) {
				if (availableOnline) {
					return globalAvailableOnline;
				} else {
					return globalAvailable;
				}
			} else {
				if (availableOnline) {
					return globalOnline;
				} else {
					return globalOnly;
				}
			}
		}
	}

	private final static String globalStr = "global";
	private final static String localStr = "local";
	private final static String availableStr = "available";
	private final static String availableOnlineStr = "available_online";

	private final static HashSet<String> globalOnly = new HashSet<>();
	private final static HashSet<String> globalLocal = new HashSet<>();
	private final static HashSet<String> globalLocalAvailable = new HashSet<>();
	private final static HashSet<String> globalLocalAvailableOnline = new HashSet<>();
	private final static HashSet<String> globalLocalOnline = new HashSet<>();
	private final static HashSet<String> globalAvailable = new HashSet<>();
	private final static HashSet<String> globalAvailableOnline = new HashSet<>();
	private final static HashSet<String> globalOnline = new HashSet<>();

	static {
		globalOnly.add(globalStr);

		globalLocalAvailableOnline.add(globalStr);
		globalLocalAvailableOnline.add(localStr);
		globalLocalAvailableOnline.add(availableStr);
		globalLocalAvailableOnline.add(availableOnlineStr);

		globalLocalAvailable.add(globalStr);
		globalLocalAvailable.add(localStr);
		globalLocalAvailable.add(availableStr);

		globalLocalOnline.add(globalStr);
		globalLocalOnline.add(localStr);
		globalLocalOnline.add(availableOnlineStr);

		globalLocal.add(globalStr);
		globalLocal.add(localStr);

		globalAvailableOnline.add(globalStr);
		globalAvailableOnline.add(availableStr);
		globalAvailableOnline.add(availableOnlineStr);

		globalAvailable.add(globalStr);
		globalAvailable.add(availableStr);

		globalOnline.add(globalStr);
		globalOnline.add(availableOnlineStr);
	}

	public void reset() {
		local = false;
		available = false;
		availableOnline = false;
	}
}
