package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.marc.MarcUtil;

import java.util.*;
import java.util.regex.Pattern;

/**
 * Inclusion rule based on everything except the location code.
 * These generally don't change so we can optimize checking.
 */
class InclusionRuleDetails {
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

	private final String marcTagToMatch;
	private final Pattern marcValueToMatchPattern;
	private final boolean includeExcludeMatches;

	private final String formatPatternString;

	private static final Pattern isRegexPattern = Pattern.compile("[.*?{}\\\\^\\[\\]|$]");
	InclusionRuleDetails(String subLocationCode, String subLocationsToExclude, String iType, String iTypesToExclude, String audience, String audiencesToExclude, String format, String formatsToExclude, String shelfLocation, String shelfLocationsToExclude, String collectionCode, String collectionCodesToExclude, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches){

		//Location & Sublocation Code Inclusion/Exclusion Check
		if (subLocationCode.isEmpty()){
			subLocationCode = ".*";
		}
		matchAllSubLocations = subLocationCode.equals(".*");
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);

		if (subLocationsToExclude != null && !subLocationsToExclude.isEmpty()){
			this.subLocationsToExcludePattern = Pattern.compile(subLocationsToExclude, Pattern.CASE_INSENSITIVE);
		}

		//iType Inclusion/Exclusion Check
		if (iType == null || iType.isEmpty()){
			iType = ".*";
		}
		if (iTypesToExclude == null) {
			iTypesToExclude = "";
		}
		if (iType.equals(".*") && (iTypesToExclude.isEmpty())){
			matchAlliTypes = true;
		}
		this.iTypePattern = Pattern.compile(iType, Pattern.CASE_INSENSITIVE);
		if (!iTypesToExclude.isEmpty()){
			this.iTypesToExcludePattern = Pattern.compile(iTypesToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Audience Inclusion/Exclusion Check
		if (audience == null || audience.isEmpty()) {
			audience = ".*";
		}
		if (audiencesToExclude == null) {
			audiencesToExclude = "";
		}
		if (audience.equals(".*") && audiencesToExclude.isEmpty()){
			matchAllAudiences = true;
		}
		this.audiencePattern = Pattern.compile(audience, Pattern.CASE_INSENSITIVE);
		if (audiencesToExclude != null && !audiencesToExclude.isEmpty()){
			this.audiencesToExcludePattern = Pattern.compile(audiencesToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Format Inclusion/Exclusion Check
		if (format == null || format.isEmpty()){
			format = ".*";
		}
		this.formatPatternString = format;
		if (formatsToExclude == null) {
			formatsToExclude = "";
		}
		if (format.equals(".*") && formatsToExclude.isEmpty()){
			matchAllFormats = true;
		}
		this.formatPattern = Pattern.compile(format, Pattern.CASE_INSENSITIVE);
		if (!formatsToExclude.isEmpty()){
			this.formatsToExcludePattern = Pattern.compile(formatsToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Shelf Location Inclusion/Exclusion Check
		if (shelfLocation == null || shelfLocation.isEmpty()) {
			shelfLocation = ".*";
		}
		if (shelfLocation.equals(".*") && (shelfLocationsToExclude == null || shelfLocationsToExclude.isEmpty())){
			matchAllShelfLocations = true;
		}
		this.shelfLocationPattern = Pattern.compile(shelfLocation, Pattern.CASE_INSENSITIVE);
		if (shelfLocationsToExclude != null && !shelfLocationsToExclude.isEmpty()){
			this.shelfLocationsToExcludePattern = Pattern.compile(shelfLocationsToExclude, Pattern.CASE_INSENSITIVE);
		}

		//Collection Code Inclusion/Exclusion Check
		if (collectionCode == null || collectionCode.isEmpty()) {
			collectionCode = ".*";
		}
		if (collectionCode.equals(".*") && collectionCodesToExclude.isEmpty()){
			matchAllCollectionCodes = true;
		}
		this.collectionCodePattern = Pattern.compile(collectionCode, Pattern.CASE_INSENSITIVE);
		if (!collectionCodesToExclude.isEmpty()){
			this.collectionCodesToExcludePattern = Pattern.compile(collectionCodesToExclude, Pattern.CASE_INSENSITIVE);
		}
		this.marcTagToMatch = Objects.requireNonNullElse(marcTagToMatch, "");

		if (marcValueToMatch == null || marcValueToMatch.isEmpty()){
			marcValueToMatch = ".*";
		}
		this.marcValueToMatchPattern = Pattern.compile(marcValueToMatch);

		this.includeExcludeMatches = includeExcludeMatches;
	}

	private String lastIdentifier = null;
	private boolean lastIdentifierResult = false;

	HashMap<String, Boolean> inclusionCache = new HashMap<>();

	//TODO: We can potentially just pass in the ItemInfo object instead of all or most of these parameters
	//		This would likely require creating an interface for ItemInfo under java_shared_libraries.
	boolean isItemIncluded(String itemIdentifier, String subLocationCode, String iType, HashSet<String> audiences, String audiencesAsString, String format, String shelfLocation, String collectionCode, org.marc4j.marc.Record marcRecord, DebugLogger debugLogger){
		if (lastIdentifier != null && lastIdentifier.equals(itemIdentifier)){
			return lastIdentifierResult;
		}

		lastIdentifier = itemIdentifier;
		//Determine if we have already determined this already
		boolean hasCachedValue = true;

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
		String inclusionCacheKey = subLocationCode + iType + audienceKey + format + shelfLocation + collectionCode;
		Boolean cachedInclusion = inclusionCache.get(inclusionCacheKey);
		if (cachedInclusion == null){
			hasCachedValue = false;
		}

		boolean isIncluded;

		if (!hasCachedValue){
			isIncluded = true;

			if (!subLocationCode.isEmpty()){
				if (!matchAllSubLocations) {
					if (!subLocationCodePattern.matcher(subLocationCode).matches()) {
						isIncluded = false;
					}
					if (isIncluded && subLocationsToExcludePattern != null) {
						if (subLocationsToExcludePattern.matcher(subLocationCode).matches()){
							isIncluded = false;
						}
					}
				}
			}

			//Check Formats to include & exclude
			if (isIncluded && format != null && !format.isEmpty()){
				if (!matchAllFormats) {
					if (!formatPattern.matcher(format).matches()) {
						if (debugLogger != null && debugLogger.isDebugEnabled()) {
							debugLogger.addDebugMessage("Item " + itemIdentifier + " excluded from scope because format '" + format + "' does not match pattern '" + formatPatternString + "'", 3);
						}
						isIncluded = false;
					}
					if (isIncluded && formatsToExcludePattern != null) {
						if(formatsToExcludePattern.matcher(format).matches()){
							if (debugLogger != null && debugLogger.isDebugEnabled()) {
								debugLogger.addDebugMessage("Item " + itemIdentifier + " excluded from scope because format '" + format + "' matches exclusion pattern", 3);
							}
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
					if (isIncluded && iTypesToExcludePattern != null) {
						if(iTypesToExcludePattern.matcher(iType).matches()) {
							isIncluded = false;
						}
					}
				}
			}
			//Check Shelf Location to include & exclude
			if (isIncluded && !matchAllShelfLocations){ //still want to process empty shelf locations, don't check for length > 0
				if (shelfLocation != null && !shelfLocation.isEmpty()){
					if (!shelfLocationPattern.matcher(shelfLocation).matches()) {
						isIncluded = false;
					}
					if (isIncluded && shelfLocationsToExcludePattern != null) {
						if(shelfLocationsToExcludePattern.matcher(shelfLocation).matches()) {
							isIncluded = false;
						}
					}
				}
				else {
					if (!shelfLocationPattern.pattern().equals(".*")) {
						isIncluded = false;
					}
				}

			}
			//Check Collection Code to include & exclude
			if (isIncluded && !matchAllCollectionCodes){
				if (collectionCode != null && !collectionCode.isEmpty()) {
					if (!collectionCodePattern.matcher(collectionCode).matches()) {
						isIncluded = false;
					}
					if (isIncluded && collectionCodesToExcludePattern != null) {
						if(collectionCodesToExcludePattern.matcher(collectionCode).matches()) {
							isIncluded = false;
						}
					}
				}
				else {
					if (!collectionCodePattern.pattern().equals(".*")) {
						isIncluded = false;
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
				}
				if (audienceMatched){
					for (String audience : audiences) {
						//As soon as something is either matched or excluded we can stop checking.
						if (audiencesToExcludePattern != null && audiencesToExcludePattern.matcher(audience).matches()) {
							audienceMatched = false;
							break;
						}
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
		if (isIncluded && !marcTagToMatch.isEmpty()) {
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
}
