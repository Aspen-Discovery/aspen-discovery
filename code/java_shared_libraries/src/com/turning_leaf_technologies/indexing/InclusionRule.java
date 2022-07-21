package com.turning_leaf_technologies.indexing;

import com.sun.istack.internal.NotNull;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.util.MaxSizeHashMap;
import org.marc4j.marc.Record;

import java.util.HashMap;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Pattern;

class InclusionRule {
	private final String recordType;
	private final boolean matchAllLocations;
	private final Pattern locationCodePattern;
	private Pattern locationsToExcludePattern = null;
	private final boolean matchAllSubLocations;
	private final Pattern subLocationCodePattern;
	private Pattern subLocationsToExcludePattern = null;
	private final Pattern iTypePattern;
	private boolean matchAlliTypes = false;
	private final Pattern audiencePattern;
	private boolean matchAllAudiences = false;
	private final Pattern formatPattern;
	private boolean matchAllFormats = false;
	private final boolean includeHoldableOnly;
	private final boolean includeItemsOnOrder;
	private final boolean includeEContent;
	private final String marcTagToMatch;
	private final Pattern marcValueToMatchPattern;
	private final boolean includeExcludeMatches;
	private final String urlToMatch;
	private final String urlReplacement;

	InclusionRule(String recordType, String locationCode, String subLocationCode, @NotNull String locationsToExclude, @NotNull String subLocationsToExclude, String iType, String audience, String format, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches, String urlToMatch, String urlReplacement){
		this.recordType = recordType;
		this.includeHoldableOnly = includeHoldableOnly;
		this.includeItemsOnOrder = includeItemsOnOrder;
		this.includeEContent = includeEContent;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		matchAllLocations = locationCode.equals(".*");
		this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);

		if (subLocationCode.length() == 0){
			subLocationCode = ".*";
		}
		matchAllSubLocations = subLocationCode.equals(".*");
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);

		if (locationsToExclude.length() > 0){
			this.locationsToExcludePattern = Pattern.compile(locationsToExclude, Pattern.CASE_INSENSITIVE);
		}
		if (subLocationsToExclude.length() > 0){
			this.subLocationsToExcludePattern = Pattern.compile(subLocationsToExclude, Pattern.CASE_INSENSITIVE);
		}

		if (iType == null || iType.length() == 0){
			iType = ".*";
			matchAlliTypes = true;
		}
		this.iTypePattern = Pattern.compile(iType, Pattern.CASE_INSENSITIVE);

		if (audience == null || audience.length() == 0){
			audience = ".*";
			matchAllAudiences = true;
		}
		this.audiencePattern = Pattern.compile(audience, Pattern.CASE_INSENSITIVE);

		if (format == null || format.length() == 0){
			format = ".*";
			matchAllFormats = true;
		}
		this.formatPattern = Pattern.compile(format, Pattern.CASE_INSENSITIVE);

		if (marcTagToMatch == null){
			this.marcTagToMatch = "";
		}else{
			this.marcTagToMatch = marcTagToMatch;
		}

		if (marcValueToMatch == null || marcValueToMatch.length() == 0){
			marcValueToMatch = ".*";
		}
		this.marcValueToMatchPattern = Pattern.compile(marcValueToMatch);

		this.includeExcludeMatches = includeExcludeMatches;

		this.urlToMatch = urlToMatch;
		this.urlReplacement = urlReplacement;
	}

	MaxSizeHashMap<String, Boolean> includedItemResults = new MaxSizeHashMap<>(500);
	//private final HashMap<String, HashMap<String, HashMap<String, HashMap<String, HashMap<String, Boolean>>>>> locationCodeCache = new HashMap<>();
	HashMap<String, Boolean> inclusionCache = new HashMap<>();

	boolean isItemIncluded(String itemIdentifier, String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord){
		Boolean includedItemResult = includedItemResults.get(itemIdentifier);
		if (includedItemResult != null){
			return includedItemResult;
		}

		//Do the quick checks first
		if (!isEContent && (includeHoldableOnly && !isHoldable)){
			includedItemResults.put(itemIdentifier, Boolean.FALSE);
			return false;
		}else if (!includeItemsOnOrder && isOnOrder){
			includedItemResults.put(itemIdentifier, Boolean.FALSE);
			return  false;
		}else if (!includeEContent && isEContent){
			includedItemResults.put(itemIdentifier, Boolean.FALSE);
			return  false;
		}else if (!this.recordType.equals(recordType)){
			includedItemResults.put(itemIdentifier, Boolean.FALSE);
			return  false;
		}

		//Determine if we have already determined this already
		boolean hasCachedValue = true;
		if (locationCode == null){
			if (matchAllLocations){
				locationCode = "null";
			}else {
				includedItemResults.put(itemIdentifier, Boolean.FALSE);
				return false;
			}
		}
//		HashMap<String, HashMap<String, HashMap<String, HashMap<String, Boolean>>>> subLocationCodeIncludeCache = locationCodeCache.get(locationCode);
//		if (subLocationCodeIncludeCache == null) {
//			hasCachedValue = false;
//			subLocationCodeIncludeCache = new HashMap<>();
//			locationCodeCache.put(locationCode, subLocationCodeIncludeCache);
//		}

//		HashMap<String, HashMap<String, HashMap<String, Boolean>>> iTypeCache = subLocationCodeIncludeCache.get(subLocationCode);
//		if (iTypeCache == null){
//			hasCachedValue = false;
//			iTypeCache = new HashMap<>();
//			subLocationCodeIncludeCache.put(subLocationCode, iTypeCache);
//		}

		if (matchAlliTypes){
			iType = "any";
		}
//		HashMap<String, HashMap<String, Boolean>> audiencesCache = iTypeCache.get(iType);
//		if (audiencesCache == null){
//			hasCachedValue = false;
//			audiencesCache = new HashMap<>();
//			iTypeCache.put(iType, audiencesCache);
//		}
		String audienceKey = audiencesAsString;
		if (matchAllAudiences){
			audienceKey = "all";
		}
//		HashMap<String, Boolean> formatCache = audiencesCache.get(audienceKey);
//		if (formatCache == null){
//			hasCachedValue = false;
//			formatCache = new HashMap<>();
//			audiencesCache.put(audienceKey, formatCache);
//		}
		if (matchAllFormats){
			format = "any";
		}
		String inclusionCacheKey = locationCode + subLocationCode + iType + audienceKey + format;
		Boolean cachedInclusion = inclusionCache.get(inclusionCacheKey);
		if (cachedInclusion == null){
			hasCachedValue = false;
		}

		boolean isIncluded;

		if (!hasCachedValue){
			if (locationCodePattern.matcher(locationCode).matches() &&
					(subLocationCode == "" || matchAllSubLocations || subLocationCodePattern.matcher(subLocationCode).matches()) &&
					(matchAllFormats || format == "" || formatPattern.matcher(format).matches())
					){

				//We got a match based on location check formats iTypes etc
				if (!matchAlliTypes && iType != null && !iTypePattern.matcher(iType).matches()){
					isIncluded =  false;
				}else{
					boolean audienceMatched = false;
					if (matchAllAudiences){
						audienceMatched = true;
					}else {
						for (String audience : audiences) {
							if (audiencePattern.matcher(audience).matches()) {
								audienceMatched = true;
								break;
							}
						}
					}
					isIncluded = audienceMatched;
				}
			}else{
				isIncluded = false;
			}
			//Make sure not to cache marc tag determination
			inclusionCache.put(inclusionCacheKey, isIncluded);
		}else{
			isIncluded = cachedInclusion;
		}
		//Make sure not to cache marc tag determination
		if (isIncluded && marcTagToMatch.length() > 0) {
			boolean hasMatch = false;
			Set<String> marcValuesToCheck = MarcUtil.getFieldList(marcRecord, marcTagToMatch);
			for (String marcValueToCheck : marcValuesToCheck) {
				if (marcValueToMatchPattern.matcher(marcValueToCheck).matches()) {
					hasMatch = true;
					break;
				}
			}
			isIncluded = hasMatch && includeExcludeMatches;
		}
		//Make sure that we are not excluding the result
		if (isIncluded && locationCode.length() > 0 && locationsToExcludePattern != null) {
			isIncluded = !locationsToExcludePattern.matcher(locationCode).matches();
		}
		if (isIncluded && subLocationCode != null && subLocationsToExcludePattern != null) {
			isIncluded = !subLocationsToExcludePattern.matcher(subLocationCode).matches();
		}
		includedItemResults.put(itemIdentifier, isIncluded);
		return isIncluded;
	}

	String getLocalUrl(String url){
		if (urlToMatch == null || urlToMatch.length() == 0 || urlReplacement == null || urlReplacement.length() == 0){
			return url;
		}else{
			return url.replaceFirst(urlToMatch, urlReplacement);
		}
	}
}
