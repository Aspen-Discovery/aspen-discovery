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
	private boolean isLocationExactMatch;
	private String locationCodeToMatch;
	private Pattern locationCodePattern;
	private Pattern locationsToExcludePattern = null;

	private final boolean matchAllSubLocations;
	private final Pattern subLocationCodePattern;
	private Pattern subLocationsToExcludePattern = null;

	private boolean matchAlliTypes = false;
	private final Pattern iTypePattern;
	private Pattern iTypesToExcludePattern = null;

	private boolean matchAllAudiences = false;
	private final Pattern audiencePattern;
	private Pattern audiencesToExcludePattern = null;

	private boolean matchAllFormats = false;
	private final Pattern formatPattern;
	private Pattern formatsToExcludePattern = null;

	private boolean matchAllShelfLocations = false;
	private final Pattern shelfLocationPattern;
	private Pattern shelfLocationsToExcludePattern = null;

	private boolean matchAllCollectionCodes = false;
	private final Pattern collectionCodePattern;
	private Pattern collectionCodesToExcludePattern = null;

	private final boolean includeHoldableOnly;
	private final boolean includeItemsOnOrder;
	private final boolean includeEContent;
	private final String marcTagToMatch;
	private final Pattern marcValueToMatchPattern;
	private final boolean includeExcludeMatches;
	private final String urlToMatch;
	private final String urlReplacement;

	private static Pattern isRegexPattern = Pattern.compile("[.*?{}\\\\^\\[\\]|$]");
	InclusionRule(String recordType, String locationCode, String subLocationCode, @NotNull String locationsToExclude, @NotNull String subLocationsToExclude, String iType, String iTypesToExclude, String audience, String audiencesToExclude,String format, String formatsToExclude, String shelfLocation, String shelfLocationsToExclude, String collectionCode, String collectionCodesToExclude, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches, String urlToMatch, String urlReplacement){
		this.recordType = recordType;
		this.includeHoldableOnly = includeHoldableOnly;
		this.includeItemsOnOrder = includeItemsOnOrder;
		this.includeEContent = includeEContent;

		//Location & Sublocation Code Inclusion/Exclusion Check
		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		matchAllLocations = locationCode.equals(".*");
		if (!matchAllLocations){
			if (isRegexPattern.matcher(locationCode).find()) {
				this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);
			}else{
				this.locationCodeToMatch = locationCode;
				isLocationExactMatch = true;
			}
		}
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

		//iType Inclusion/Exclusion Check
		if (iType == null || iType.length() == 0 || iType.equals(".*")){
			iType = ".*";
			matchAlliTypes = true;
		}
		this.iTypePattern = Pattern.compile(iType, Pattern.CASE_INSENSITIVE);
		if (iTypesToExclude.length() > 0){
			this.iTypesToExcludePattern = Pattern.compile(iTypesToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Audience Inclusion/Exclusion Check
		if (audience == null || audience.length() == 0) {
			audience = ".*";
		}
		if (audience.equals(".*") && audiencesToExclude.length() == 0){
			matchAllAudiences = true;
		}
		this.audiencePattern = Pattern.compile(audience, Pattern.CASE_INSENSITIVE);
		if (audiencesToExclude.length() > 0){
			this.audiencesToExcludePattern = Pattern.compile(audiencesToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Format Inclusion/Exclusion Check
		if (format == null || format.length() == 0){
			format = ".*";
		}
		if (format.equals(".*") && formatsToExclude.length() == 0){
			matchAllFormats = true;
		}
		this.formatPattern = Pattern.compile(format, Pattern.CASE_INSENSITIVE);
		if (formatsToExclude.length() > 0){
			this.formatsToExcludePattern = Pattern.compile(formatsToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Shelf Location Inclusion/Exclusion Check
		if (shelfLocation == null || shelfLocation.length() == 0) {
			shelfLocation = ".*";
		}
		if (shelfLocation.equals(".*") && shelfLocationsToExclude.length() == 0){
			matchAllShelfLocations = true;
		}
		this.shelfLocationPattern = Pattern.compile(shelfLocation, Pattern.CASE_INSENSITIVE);
		if (shelfLocationsToExclude.length() > 0){
			this.shelfLocationsToExcludePattern = Pattern.compile(shelfLocationsToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Collection Code Inclusion/Exclusion Check
		if (collectionCode == null || collectionCode.length() == 0 || collectionCode.equals(".*")) {
			collectionCode = ".*";
		}
		if (collectionCode.equals(".*") && collectionCodesToExclude.length() == 0){
			matchAllCollectionCodes = true;
		}
		this.collectionCodePattern = Pattern.compile(collectionCode, Pattern.CASE_INSENSITIVE);
		if (collectionCodesToExclude.length() > 0){
			this.collectionCodesToExcludePattern = Pattern.compile(collectionCodesToExclude, Pattern.CASE_INSENSITIVE);
		}
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

	private String lastIdentifier = null;
	private boolean lastIdentifierResult = false;

	HashMap<String, Boolean> inclusionCache = new HashMap<>();

	//TODO: We can potentially just pass in the ItemInfo object instead of all or most of these parameters
	boolean isItemIncluded(String itemIdentifier, String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, String shelfLocation, String collectionCode, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord){
		if (lastIdentifier != null && lastIdentifier.equals(itemIdentifier)){
			return lastIdentifierResult;
		}

		lastIdentifier = itemIdentifier;
		//Do the quick checks first
		if (!isEContent && (includeHoldableOnly && !isHoldable)){
			lastIdentifierResult = false;
			return false;
		}else if (!includeItemsOnOrder && isOnOrder){
			lastIdentifierResult = false;
			return  false;
		}else if (!includeEContent && isEContent){
			lastIdentifierResult = false;
			return  false;
		}else if (!this.recordType.equals(recordType)){
			lastIdentifierResult = false;
			return  false;
		}

		//Determine if we have already determined this already
		boolean hasCachedValue = true;
		if (locationCode == null){
			if (matchAllLocations){
				locationCode = "null";
			}else {
				lastIdentifierResult = false;
				return false;
			}
		}

		if (matchAlliTypes){
			iType = "any";
		}
		String audienceKey = audiencesAsString;
		if (matchAllAudiences){
			audienceKey = "all";
		}
		if (matchAllFormats){
			format = "any";
		}
		if(matchAllShelfLocations){
			shelfLocation = "all";
		}
		if(matchAllCollectionCodes){
			collectionCode = "any";
		}
		String inclusionCacheKey = locationCode + subLocationCode + iType + audienceKey + format + shelfLocation + collectionCode;
		Boolean cachedInclusion = inclusionCache.get(inclusionCacheKey);
		if (cachedInclusion == null){
			hasCachedValue = false;
		}

		boolean isIncluded;

		if (!hasCachedValue){
			isIncluded = true;
			if (!matchAllLocations) {
				if (isLocationExactMatch){
					if (!locationCodeToMatch.equalsIgnoreCase(locationCode)){
						isIncluded = false;
					}
				}else{
					if (!locationCodePattern.matcher(locationCode).matches()){
						isIncluded = false;
					}
				}
				if (isIncluded && locationCode != null && locationCode.length() > 0 && locationsToExcludePattern != null) {
					if (locationsToExcludePattern.matcher(locationCode).matches()) {
						isIncluded = false;
					}
				}
			}

			if (isIncluded && subLocationCode.length() > 0){
				if (!matchAllSubLocations) {
					if (!subLocationCodePattern.matcher(subLocationCode).matches()) {
						isIncluded = false;
					}
					if (isIncluded && subLocationCode != null && subLocationCode.length() > 0 && subLocationsToExcludePattern != null) {
						if (subLocationsToExcludePattern.matcher(subLocationCode).matches()){
							isIncluded = false;
						}
					}
				}
			}

			//Check Formats to include & exclude
			if (isIncluded && format.length() > 0){
				if (!matchAllFormats) {
					if (!formatPattern.matcher(format).matches()) {
						isIncluded = false;
					}
					if (isIncluded && format != null && formatsToExcludePattern != null) {
						if(formatsToExcludePattern.matcher(format).matches()){
							isIncluded = false;
						}
					}
				}
			}

			//Check iTypes to include & exclude
			if (isIncluded && iType != null){
				if (!matchAlliTypes) {
					if (!iTypePattern.matcher(iType).matches()) {
						isIncluded = false;
					}
					if (isIncluded && iType != null && iTypesToExcludePattern != null) {
						if(iTypesToExcludePattern.matcher(iType).matches()) {
							isIncluded = false;
						}
					}
				}
			}
			//Check Shelf Location to include & exclude
			if (isIncluded && shelfLocation.length() > 0){
				if (!matchAllShelfLocations) {
					if (!shelfLocationPattern.matcher(shelfLocation).matches()) {
						isIncluded = false;
					}
					if (isIncluded && shelfLocation != null && shelfLocationsToExcludePattern != null) {
						if(shelfLocationsToExcludePattern.matcher(shelfLocation).matches()) {
							isIncluded = false;
						}
					}
				}

			}
			//Check Collection Code to include & exclude
			if (isIncluded && collectionCode != null && collectionCode.length() > 0){
				if (!matchAllCollectionCodes) {
					if (!collectionCodePattern.matcher(collectionCode).matches()) {
						isIncluded = false;
					}
					if (isIncluded && collectionCodesToExcludePattern != null) {
						if(collectionCodesToExcludePattern.matcher(collectionCode).matches()) {
							isIncluded = false;
						}
					}
				}
			}
			//Check audiences to include & exclude
			if (isIncluded && !matchAllAudiences){
				boolean audienceMatched = false;
				for (String audience : audiences) {
					//As soon as something is either matched or excluded we can stop checking.
					if (audiencePattern.matcher(audience).matches()) {
						audienceMatched = true;
						break;
					}
					if (audiencesToExcludePattern != null && audiencesToExcludePattern.matcher(audience).matches()) {
						audienceMatched = false;
						break;
					}
				}
				isIncluded = audienceMatched;
			}

			//Make sure not to cache marc tag determination
			inclusionCache.put(inclusionCacheKey, isIncluded);
		}else{
			isIncluded = cachedInclusion;
		}
		//Make sure not to cache marc tag determination
		//TODO: *Someday* if the marc tag to match is the item tag, only get the marc tag for the item we are on.
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
		lastIdentifierResult = isIncluded;
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
