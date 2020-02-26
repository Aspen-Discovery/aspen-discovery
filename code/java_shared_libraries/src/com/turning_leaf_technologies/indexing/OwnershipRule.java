package com.turning_leaf_technologies.indexing;

import com.sun.istack.internal.NotNull;

import java.util.HashMap;
import java.util.regex.Pattern;

class OwnershipRule {
	private String recordType;

	private boolean matchAllLocations;
	private Pattern locationCodePattern;
	private Pattern subLocationCodePattern;

	OwnershipRule(String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		this.recordType = recordType;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		this.matchAllLocations = locationCode.equals(".*");
		this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);
		if (subLocationCode.length() == 0){
			subLocationCode = ".*";
		}
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);
	}

	private HashMap<String, Boolean> ownershipResults = new HashMap<>();
	boolean isItemOwned(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		boolean isOwned = false;
		if (this.recordType.equals(recordType)){
			String key = locationCode + "-" + subLocationCode;
			if (ownershipResults.containsKey(key)){
				return ownershipResults.get(key);
			}

			if (locationCode == null ){
				if (matchAllLocations) {
					isOwned = (subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt());
				}else{
					isOwned = false;
				}
			}else{
				isOwned = locationCodePattern.matcher(locationCode).lookingAt() && (subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt());
			}
			ownershipResults.put(key, isOwned);
		}
		return  isOwned;
	}
}
