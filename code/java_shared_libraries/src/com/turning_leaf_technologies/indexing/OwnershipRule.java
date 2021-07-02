package com.turning_leaf_technologies.indexing;

import com.sun.istack.internal.NotNull;

import java.util.HashMap;
import java.util.regex.Pattern;

class OwnershipRule {
	private final String recordType;

	private final boolean matchAllLocations;
	private final Pattern locationCodePattern;
	private Pattern locationsToExcludePattern = null;
	private final boolean matchAllSubLocations;
	private final Pattern subLocationCodePattern;
	private Pattern subLocationsToExcludePattern = null;

	OwnershipRule(String recordType, @NotNull String locationCode, @NotNull String subLocationCode, @NotNull String locationsToExclude, @NotNull String subLocationsToExclude){
		this.recordType = recordType;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		this.matchAllLocations = locationCode.equals(".*");
		this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);
		if (subLocationCode.length() == 0){
			subLocationCode = ".*";
		}
		this.matchAllSubLocations = subLocationCode.equals(".*");
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);

		if (locationsToExclude.length() > 0){
			this.locationsToExcludePattern = Pattern.compile(locationsToExclude, Pattern.CASE_INSENSITIVE);
		}
		if (subLocationsToExclude.length() > 0){
			this.subLocationsToExcludePattern = Pattern.compile(subLocationsToExclude, Pattern.CASE_INSENSITIVE);
		}
	}

	private final HashMap<String, Boolean> ownershipResults = new HashMap<>();
	boolean isItemOwned(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		Boolean isOwned = false;
		if (this.recordType.equals(recordType)){
			String key = locationCode + "-" + subLocationCode;
			isOwned = ownershipResults.get(key);
			if (isOwned != null){
				return isOwned;
			}

			if (locationCode == null ){
				if (matchAllLocations) {
					isOwned =  (matchAllSubLocations || subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt());
				}else{
					isOwned = false;
				}
			}else{
				isOwned = locationCodePattern.matcher(locationCode).lookingAt() && (matchAllSubLocations || subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt());
			}
			//Make sure that we are not excluding the result
			if (isOwned && locationCode != null && locationsToExcludePattern != null) {
				isOwned = !locationsToExcludePattern.matcher(locationCode).lookingAt();
			}
			if (isOwned && subLocationCode != null && subLocationsToExcludePattern != null) {
				isOwned = !subLocationsToExcludePattern.matcher(subLocationCode).lookingAt();
			}
			ownershipResults.put(key, isOwned);
		}
		return  isOwned;
	}
}
