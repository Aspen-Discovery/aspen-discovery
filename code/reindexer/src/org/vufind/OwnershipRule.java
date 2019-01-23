package org.vufind;

import com.sun.istack.internal.NotNull;

import java.util.HashMap;
import java.util.regex.Pattern;

/**
 * Required information to determine what records are owned directly by a library or location
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 10:49 AM
 */
public class OwnershipRule {
	private String recordType;

	private Pattern locationCodePattern;
	private Pattern subLocationCodePattern;

	OwnershipRule(String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		this.recordType = recordType;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
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

			isOwned = locationCodePattern.matcher(locationCode).lookingAt() && (subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt());
			ownershipResults.put(key, isOwned);
		}
		return  isOwned;
	}
}
