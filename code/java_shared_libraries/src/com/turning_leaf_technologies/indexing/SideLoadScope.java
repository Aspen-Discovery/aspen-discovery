package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.marc.MarcUtil;
import org.marc4j.marc.Record;

import java.util.Objects;
import java.util.Set;
import java.util.regex.Pattern;

public class SideLoadScope {
	private long id;
	private String name;
	private long sideLoadId;
	private boolean includeAdult;
	private boolean includeTeen;
	private boolean includeKids;
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

	void setIncludeAdult(boolean includeAdult) {
		this.includeAdult = includeAdult;
	}

	public boolean isIncludeTeen() {
		return includeTeen;
	}

	void setIncludeTeen(boolean includeTeen) {
		this.includeTeen = includeTeen;
	}

	public boolean isIncludeKids() {
		return includeKids;
	}

	void setIncludeKids(boolean includeKids) {
		this.includeKids = includeKids;
	}

	public boolean isIncludeAdult() {
		return includeAdult;
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
		this.marcTagToMatch = Objects.requireNonNullElse(marcTagToMatch, "");
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
		if (marcValueToMatch == null || marcValueToMatch.isEmpty()){
			marcValueToMatch = ".*";
		}
		this.marcValueToMatchPattern = Pattern.compile(marcValueToMatch);
	}

	public boolean isItemPartOfScope(Record marcRecord, boolean isAdult, boolean isTeen, boolean isKids){
		boolean isIncluded = false;
		//Check audience
		//noinspection RedundantIfStatement
		if (isAdult && includeAdult) {
			isIncluded = true;
		}
		if (isTeen && includeTeen) {
			isIncluded = true;
		}
		if (isKids && includeKids) {
			isIncluded = true;
		}
		if (isIncluded) {
			//Make sure not to cache marc tag determination
			if (!marcTagToMatch.isEmpty()) {
				boolean hasMatch = false;
				Set<String> marcValuesToCheck = MarcUtil.getFieldList(marcRecord, marcTagToMatch);
				for (String marcValueToCheck : marcValuesToCheck) {
					if (marcValueToMatchPattern.matcher(marcValueToCheck).lookingAt()) {
						hasMatch = true;
						break;
					}
				}
				if (includeExcludeMatches) {
					isIncluded = hasMatch;
				} else {
					isIncluded = !hasMatch;
				}
			}
		}
		return isIncluded;
	}

	public String getLocalUrl(String url){
		if (urlToMatch == null || urlToMatch.isEmpty() || urlReplacement == null || urlReplacement.isEmpty()){
			return url;
		}else{
			return url.replaceFirst(urlToMatch, urlReplacement);
		}
	}
}
