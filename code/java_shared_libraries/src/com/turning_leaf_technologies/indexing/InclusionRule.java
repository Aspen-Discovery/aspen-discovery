package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.marc.MarcUtil;

import java.util.HashMap;
import java.util.Objects;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Pattern;

class InclusionRule {
	//Checks boolean inclusion options
	private InclusionRuleBasics inclusionRuleBasics;
	//Checks inclusion by location, this is where most things are weeded out
	private InclusionRuleLocation inclusionRuleByLocation;
	//Checks detailed inclusion options, can be null if no inclusion rules apply
	private InclusionRuleDetails detailedInclusionRule;

	private final String urlToMatch;
	private final String urlReplacement;

	private static final HashMap<String, InclusionRuleBasics> basicInclusionRules = new HashMap<>();
	private static final HashMap<String, InclusionRuleLocation> locationInclusionRules = new HashMap<>();
	private static final HashMap<String, InclusionRuleDetails> detailedInclusionRules = new HashMap<>();

	//TODO Switch this back to the previous constructor and create local static caches for all the options
	InclusionRule(String recordType, String locationCode, String subLocationCode, String locationsToExclude, String subLocationsToExclude, String iType, String iTypesToExclude, String audience, String audiencesToExclude,String format, String formatsToExclude, String shelfLocation, String shelfLocationsToExclude, String collectionCode, String collectionCodesToExclude, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches, String urlToMatch, String urlReplacement){
		String basicInclusionRuleKey =
			Boolean.toString(includeHoldableOnly) + "~" +
			Boolean.toString(includeItemsOnOrder) + "~" +
			Boolean.toString(includeEContent);
		if (basicInclusionRules.containsKey(basicInclusionRuleKey)){
			this.inclusionRuleBasics = basicInclusionRules.get(basicInclusionRuleKey);
		}else{
			InclusionRuleBasics inclusionRuleBasics = new InclusionRuleBasics(includeHoldableOnly, includeItemsOnOrder, includeEContent);
			basicInclusionRules.put(basicInclusionRuleKey, inclusionRuleBasics);
			this.inclusionRuleBasics = inclusionRuleBasics;
		}

		String locationInclusionRuleKey = recordType + "~" + locationCode + "~" + locationsToExclude;
		if (locationInclusionRules.containsKey(locationInclusionRuleKey)){
			this.inclusionRuleByLocation = locationInclusionRules.get(locationInclusionRuleKey);
		}else{
			InclusionRuleLocation inclusionRuleByLocation = new InclusionRuleLocation(recordType, locationCode, locationsToExclude);
			locationInclusionRules.put(locationInclusionRuleKey, inclusionRuleByLocation);
			this.inclusionRuleByLocation = inclusionRuleByLocation;
		}

		//Check to see if detailed inclusion will result in no restrictions.
		boolean detailedInclusionRuleHasNoRestrictions = false;
		if (subLocationCode == null || subLocationCode.isEmpty() || subLocationCode.equals(".*")){
			if (subLocationsToExclude == null || subLocationsToExclude.isEmpty()){
				if (iType == null || iType.isEmpty() || iType.equals(".*")) {
					if (iTypesToExclude == null || iTypesToExclude.isEmpty()) {
						if (audience == null || audience.isEmpty() || audience.equals(".*")) {
							if (audiencesToExclude == null || audiencesToExclude.isEmpty()) {
								if (format == null || format.isEmpty() || format.equals(".*")) {
									if (formatsToExclude == null || formatsToExclude.isEmpty()) {
										if (shelfLocation == null || shelfLocation.isEmpty() || shelfLocation.equals(".*")) {
											if (shelfLocationsToExclude == null || shelfLocationsToExclude.isEmpty()) {
												if (collectionCode == null || collectionCode.isEmpty() || collectionCode.equals(".*")) {
													if (collectionCodesToExclude == null || collectionCodesToExclude.isEmpty()) {
														if (marcTagToMatch == null || marcTagToMatch.isEmpty() || marcTagToMatch.equals(".*")) {
															if (marcValueToMatch == null || marcValueToMatch.isEmpty() || marcValueToMatch.equals(".*")) {
																detailedInclusionRuleHasNoRestrictions = true;
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if (detailedInclusionRuleHasNoRestrictions) {
			this.detailedInclusionRule = null;
		}else {
			String detailedInclusionRuleKey = subLocationCode + "~" +
				subLocationsToExclude + "~" +
				iType + "~" +
				iTypesToExclude + "~" +
				audience + "~" +
				audiencesToExclude + "~" +
				format + "~" +
				formatsToExclude + "~" +
				shelfLocation + "~" +
				shelfLocationsToExclude + "~" +
				collectionCode + "~" +
				collectionCodesToExclude + "~" +
				marcTagToMatch + "~" +
				marcValueToMatch + "~" +
				Boolean.toString(includeExcludeMatches);
			if (detailedInclusionRules.containsKey(detailedInclusionRuleKey)) {
				this.detailedInclusionRule = detailedInclusionRules.get(detailedInclusionRuleKey);
			} else {
				InclusionRuleDetails detailedInclusionRule = new InclusionRuleDetails(subLocationCode, subLocationsToExclude, iType, iTypesToExclude, audience, audiencesToExclude, format, formatsToExclude, shelfLocation, shelfLocationsToExclude, collectionCode, collectionCodesToExclude, marcTagToMatch, marcValueToMatch, includeExcludeMatches);
				detailedInclusionRules.put(detailedInclusionRuleKey, detailedInclusionRule);
				this.detailedInclusionRule = detailedInclusionRule;
			}
		}

		this.urlToMatch = urlToMatch;
		this.urlReplacement = urlReplacement;
	}

	private String lastIdentifier = null;
	private boolean lastIdentifierResult = false;

	HashMap<String, Boolean> inclusionCache = new HashMap<>();

	//TODO: We can potentially just pass in the ItemInfo object instead of all or most of these parameters
	//		This would likely require creating an interface for ItemInfo under java_shared_libraries.
	boolean isItemIncluded(String itemIdentifier, String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, String shelfLocation, String collectionCode, boolean isHoldable, boolean isOnOrder, boolean isEContent, org.marc4j.marc.Record marcRecord, DebugLogger debugLogger){
		if (lastIdentifier != null && lastIdentifier.equals(itemIdentifier)){
			return lastIdentifierResult;
		}
		lastIdentifier = itemIdentifier;
		if (!inclusionRuleBasics.isItemIncluded(itemIdentifier, isHoldable, isOnOrder, isEContent, debugLogger)){
			lastIdentifierResult = false;
			return false;
		}
		if (!inclusionRuleByLocation.isItemIncluded(itemIdentifier, recordType, locationCode, debugLogger)){
			lastIdentifierResult = false;
			return false;
		}
		if (detailedInclusionRule != null && !detailedInclusionRule.isItemIncluded(itemIdentifier, subLocationCode, iType, audiences, audiencesAsString, format, shelfLocation, collectionCode, marcRecord, debugLogger)){
			lastIdentifierResult = false;
			return false;
		}

		if (debugLogger != null && debugLogger.isDebugEnabled()) {
			if (lastIdentifierResult) {
				debugLogger.addDebugMessage("Item " + itemIdentifier + " included in scope (location='" + locationCode + "', format='" + format + "', holdable=" + isHoldable + ")", 3);
			}
		}
		lastIdentifierResult = true;
		return true;
	}

	String getLocalUrl(String url){
		if (urlToMatch == null || urlToMatch.isEmpty() || urlReplacement == null || urlReplacement.isEmpty()){
			return url;
		}else{
			return url.replaceFirst(urlToMatch, urlReplacement);
		}
	}
}
