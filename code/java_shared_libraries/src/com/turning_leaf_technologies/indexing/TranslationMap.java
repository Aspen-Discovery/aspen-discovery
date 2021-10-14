package com.turning_leaf_technologies.indexing;

import org.apache.logging.log4j.Logger;

import java.util.HashMap;
import java.util.HashSet;
import java.util.regex.Pattern;

public class TranslationMap {
	private Logger logger;
	private String profileName;
	private String mapName;
	private boolean usesRegularExpressions;

	private HashMap<String, String> translationValues = new HashMap<>();
	private HashMap<Pattern, String> translationValuePatterns = new HashMap<>();

	public TranslationMap(String profileName, String mapName, boolean usesRegularExpressions, Logger logger){
		this.profileName = profileName;
		this.mapName = mapName;
		this.usesRegularExpressions = usesRegularExpressions;
		this. logger = logger;
	}

	private HashSet<String> unableToTranslateWarnings = new HashSet<>();
	private HashMap<String, String> cachedTranslations = new HashMap<>();

	public String translateValue(String value, String identifier){
		return this.translateValue(value, identifier, false);
	}

	public String translateValue(String value, String identifier, boolean reportErrors){
		String translatedValue = null;
		String lowerCaseValue = value.toLowerCase();
		if (cachedTranslations.containsKey(value)){
			return cachedTranslations.get(value);
		}
		if (usesRegularExpressions){
			boolean matchFound = false;
			for (Pattern pattern : translationValuePatterns.keySet()){
				if (pattern.matcher(value).matches()){
					matchFound = true;
					translatedValue = translationValuePatterns.get(pattern);
					break;
				}
			}
			if (!matchFound) {
				String concatenatedValue = mapName + ":" + value;
				if (!unableToTranslateWarnings.contains(concatenatedValue)) {
					if (reportErrors) {
						logger.warn("Could not translate '" + concatenatedValue + "' in profile " + profileName + " sample record " + identifier);
					}
					unableToTranslateWarnings.add(concatenatedValue);
				}
			}
		} else {
			if (translationValues.containsKey(lowerCaseValue)) {
				translatedValue = translationValues.get(lowerCaseValue);
			} else {
				if (translationValues.containsKey("*")) {
					translatedValue = translationValues.get("*");
				} else {
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)) {
						if (reportErrors) {
							logger.warn("Could not translate '" + concatenatedValue + "' in profile " + profileName + " sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
			if (translatedValue != null){
				if (translatedValue.equals("nomap")){
					translatedValue = value;
				}else {
					translatedValue = translatedValue.trim();
					if (translatedValue.length() == 0) {
						translatedValue = null;
					}
				}
			}
		}
		cachedTranslations.put(value, translatedValue);
		return translatedValue;
	}

	public String getMapName() {
		return mapName;
	}

	public void addValue(String value, String translation) {
		if (usesRegularExpressions){
			translationValuePatterns.put(Pattern.compile(value.trim(), Pattern.CASE_INSENSITIVE), translation);
		}else{
			translationValues.put(value.trim().toLowerCase(), translation);
		}
	}

	public boolean hasTranslation(String value) {
		return translationValues.containsKey(value) || usesRegularExpressions;
	}
}
