package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

class HoldabilityInformation {
	private boolean isHoldable;
	private HashSet<Long> holdablePTypes;

	HoldabilityInformation(boolean holdable, HashSet<Long> holdablePTypes) {
		this.isHoldable = holdable;
		this.holdablePTypes = holdablePTypes;
	}

	boolean isHoldable() {
		return isHoldable;
	}

	private String holdablePTypesString = null;
	String getHoldablePTypes() {
		if (holdablePTypesString == null){
			if (holdablePTypes.contains(999L)){
				holdablePTypesString = "999";
			}else{
				holdablePTypesString = Util.getCsvSeparatedStringFromLongs(holdablePTypes);
			}
		}
		return holdablePTypesString;
	}
}
