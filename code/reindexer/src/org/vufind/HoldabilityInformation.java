package org.vufind;

import java.util.HashSet;

/**
 * Information about holdability for a title, includes related pTypes when applicable
 *
 * Pika
 * User: Mark Noble
 * Date: 8/26/2015
 * Time: 2:56 PM
 */
public class HoldabilityInformation {
	private boolean isHoldable;
	private HashSet<Long> holdablePTypes;

	public HoldabilityInformation(boolean holdable, HashSet<Long> holdablePTypes) {
		this.isHoldable = holdable;
		this.holdablePTypes = holdablePTypes;
	}

	public boolean isHoldable() {
		return isHoldable;
	}

	String holdablePTypesString = null;
	public String getHoldablePTypes() {
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
