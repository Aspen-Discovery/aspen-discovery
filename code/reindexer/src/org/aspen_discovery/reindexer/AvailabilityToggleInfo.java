package org.aspen_discovery.reindexer;

import java.util.ArrayList;

public class AvailabilityToggleInfo {
	public boolean local;
	public boolean available;
	public boolean availableOnline;

	public ArrayList<String> getValues(){
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

	private final static ArrayList<String> globalOnly = new ArrayList<>();
	private final static ArrayList<String> globalLocal = new ArrayList<>();
	private final static ArrayList<String> globalLocalAvailable = new ArrayList<>();
	private final static ArrayList<String> globalLocalAvailableOnline = new ArrayList<>();
	private final static ArrayList<String> globalLocalOnline = new ArrayList<>();
	private final static ArrayList<String> globalAvailable = new ArrayList<>();
	private final static ArrayList<String> globalAvailableOnline = new ArrayList<>();
	private final static ArrayList<String> globalOnline = new ArrayList<>();

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

	public void updateToggleValues(boolean local, boolean available, boolean availableOnline){
		this.local = this.local || local;
		this.available = this.available || available;
		this.availableOnline = this.availableOnline || availableOnline;
	}
}
