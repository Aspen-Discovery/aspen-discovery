package org.vufind;

import org.marc4j.marc.Record;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Pattern;

/**
 * Information to determine if a particular record/item should be included within a given scope
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 11:31 AM
 */
class InclusionRule {
	private String recordType;
	private Pattern locationCodePattern;
	private Pattern subLocationCodePattern;
	private Pattern iTypePattern;
	private boolean matchAllAudiences = false;
	private Pattern audiencePattern;
	private Pattern formatPattern;
	private boolean includeHoldableOnly;
	private boolean includeItemsOnOrder;
	private boolean includeEContent;
	private String marcTagToMatch;
	private Pattern marcValueToMatchPattern;
	private boolean includeExcludeMatches;
	private String urlToMatch;
	private String urlReplacement;

	InclusionRule(String recordType, String locationCode, String subLocationCode, String iType, String audience, String format, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches, String urlToMatch, String urlReplacement){
		this.recordType = recordType;
		this.includeHoldableOnly = includeHoldableOnly;
		this.includeItemsOnOrder = includeItemsOnOrder;
		this.includeEContent = includeEContent;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);

		if (subLocationCode.length() == 0){
			subLocationCode = ".*";
		}
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);

		if (iType == null || iType.length() == 0){
			iType = ".*";
		}
		this.iTypePattern = Pattern.compile(iType, Pattern.CASE_INSENSITIVE);

		if (audience == null || audience.length() == 0){
			audience = ".*";
			matchAllAudiences = true;
		}
		this.audiencePattern = Pattern.compile(audience, Pattern.CASE_INSENSITIVE);

		if (format == null || format.length() == 0){
			format = ".*";
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

	private HashMap<String, HashMap<String, HashMap<String, HashMap<String, HashMap<String, Boolean>>>>> locationCodeCache = new HashMap<>();
	boolean isItemIncluded(String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String format, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord){
		//Do the quick checks first
		if (!isEContent && (includeHoldableOnly && !isHoldable)){
			return false;
		}else if (!includeItemsOnOrder && isOnOrder){
			return  false;
		}else if (!includeEContent && isEContent){
			return  false;
		}else if (!this.recordType.equals(recordType)){
			return  false;
		}

		//Determine if we have already determined this already
		boolean hasCachedValue = true;
		HashMap<String, HashMap<String, HashMap<String, HashMap<String, Boolean>>>> subLocationCodeIncludeCache = locationCodeCache.get(locationCode);
		if (subLocationCodeIncludeCache == null){
			hasCachedValue = false;
			subLocationCodeIncludeCache = new HashMap<>();
			locationCodeCache.put(locationCode, subLocationCodeIncludeCache);
		}
		HashMap<String, HashMap<String, HashMap<String, Boolean>>> iTypeCache = subLocationCodeIncludeCache.get(subLocationCode);
		if (iTypeCache == null){
			hasCachedValue = false;
			iTypeCache = new HashMap<>();
			subLocationCodeIncludeCache.put(subLocationCode, iTypeCache);
		}
		HashMap<String, HashMap<String, Boolean>> audiencesCache = iTypeCache.get(iType);
		if (audiencesCache == null){
			hasCachedValue = false;
			audiencesCache = new HashMap<>();
			iTypeCache.put(iType, audiencesCache);
		}
		String audiencesKey = audiences.toString();
		HashMap<String, Boolean> formatCache = audiencesCache.get(audiencesKey);
		if (formatCache == null){
			hasCachedValue = false;
			formatCache = new HashMap<>();
			audiencesCache.put(audiencesKey, formatCache);
		}
		Boolean cachedInclusion = formatCache.get(format);
		if (cachedInclusion == null){
			hasCachedValue = false;
		}

		boolean isIncluded;

		if (!hasCachedValue){
			if ((locationCode == null || locationCodePattern.matcher(locationCode).lookingAt()) &&
					(subLocationCode == null || subLocationCodePattern.matcher(subLocationCode).lookingAt()) &&
					(format == null || formatPattern.matcher(format).lookingAt())
					){

				//We got a match based on location check formats iTypes etc
				if (iType != null && !iTypePattern.matcher(iType).lookingAt()){
					isIncluded =  false;
				}else{
					boolean audienceMatched = false;
					if (matchAllAudiences){
						audienceMatched = true;
					}else {
						for (String audience : audiences) {
							if (audiencePattern.matcher(audience).lookingAt()) {
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
			formatCache.put(format, isIncluded);
		}else{
			isIncluded = cachedInclusion;
		}
		//Make sure not to cache marc tag determination
		if (isIncluded && marcTagToMatch.length() > 0) {
			boolean hasMatch = false;
			Set<String> marcValuesToCheck = MarcUtil.getFieldList(marcRecord, marcTagToMatch);
			for (String marcValueToCheck : marcValuesToCheck) {
				if (marcValueToMatchPattern.matcher(marcValueToCheck).lookingAt()) {
					hasMatch = true;
					break;
				}
			}
			isIncluded = hasMatch && includeExcludeMatches;
		}
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
