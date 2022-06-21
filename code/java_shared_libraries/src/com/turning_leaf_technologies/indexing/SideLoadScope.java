package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.marc.MarcUtil;
import org.marc4j.marc.Record;

import java.util.Set;
import java.util.regex.Pattern;

public class SideLoadScope {
	private long id;
	private String name;
	private long sideLoadId;
	private boolean restrictToChildrensMaterial;
	private String marcTagToMatch = "";
	private Pattern marcValueToMatchPattern;
	private boolean includeExcludeMatches;
	private String urlToMatch;
	private String urlReplacement;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public boolean isRestrictToChildrensMaterial() {
		return restrictToChildrensMaterial;
	}

	void setRestrictToChildrensMaterial(boolean restrictToChildrensMaterial) {
		this.restrictToChildrensMaterial = restrictToChildrensMaterial;
	}

	long getSideLoadId() {
		return sideLoadId;
	}

	void setSideLoadId(long sideLoadId) {
		this.sideLoadId = sideLoadId;
	}

	String getMarcTagToMatch() {
		return marcTagToMatch;
	}

	void setMarcTagToMatch(String marcTagToMatch) {
		if (marcTagToMatch == null){
			this.marcTagToMatch = "";
		}else{
			this.marcTagToMatch = marcTagToMatch;
		}
	}

	void setIncludeExcludeMatches(boolean includeExcludeMatches) {
		this.includeExcludeMatches = includeExcludeMatches;
	}

	void setUrlToMatch(String urlToMatch) {
		this.urlToMatch = urlToMatch;
	}

	void setUrlReplacement(String urlReplacement) {
		this.urlReplacement = urlReplacement;
	}

	void setMarcValueToMatch(String marcValueToMatch) {
		if (marcValueToMatch == null || marcValueToMatch.length() == 0){
			marcValueToMatch = ".*";
		}
		this.marcValueToMatchPattern = Pattern.compile(marcValueToMatch);
	}

	public boolean isItemPartOfScope(Record marcRecord){
		boolean isIncluded = true;
		//Make sure not to cache marc tag determination
		if (marcTagToMatch.length() > 0) {
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

	public String getLocalUrl(String url){
		if (urlToMatch == null || urlToMatch.length() == 0 || urlReplacement == null || urlReplacement.length() == 0){
			return url;
		}else{
			return url.replaceFirst(urlToMatch, urlReplacement);
		}
	}
}
