package org.vufind;

import org.apache.log4j.Logger;

import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.Set;
import java.util.regex.Pattern;

/**
 * A translation map to translate values
 *
 * Pika
 * User: Mark Noble
 * Date: 7/9/2015
 * Time: 10:43 PM
 */
public class TranslationMap {
	private Logger logger;
	private String profileName;
	private String mapName;
	private boolean fullReindex;
	private boolean usesRegularExpressions;

	private HashMap<String, String> translationValues = new HashMap<>();
	private HashMap<Pattern, String> translationValuePatterns = new HashMap<>();

	public TranslationMap(String profileName, String mapName, boolean fullReindex, boolean usesRegularExpressions, Logger logger){
		this.profileName = profileName;
		this.mapName = mapName;
		this.fullReindex = fullReindex;
		this.usesRegularExpressions = usesRegularExpressions;
		this. logger = logger;
	}

	HashSet<String> unableToTranslateWarnings = new HashSet<>();
	public HashMap<String, String> cachedTranslations = new HashMap<>();
	public String translateValue(String value, String identifier){
		return translateValue(value, identifier, true);
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
					if (fullReindex && reportErrors) {
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
						if (fullReindex && reportErrors) {
							logger.warn("Could not translate '" + concatenatedValue + "' in profile " + profileName + " sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					if (!reportErrors){
						translatedValue = null;
					}else{
						translatedValue = value;
					}
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

	public LinkedHashSet<String> translateCollection(Set<String> values, String identifier) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<>();
		for (String value : values){
			String translatedValue = translateValue(value, identifier);
			if (translatedValue != null) {
				translatedCollection.add(translatedValue);
			}
		}
		return  translatedCollection;
	}

	public String getMapName() {
		return mapName;
	}

	public void addValue(String value, String translation) {
		if (usesRegularExpressions){
			translationValuePatterns.put(Pattern.compile(value, Pattern.CASE_INSENSITIVE), translation);
		}else{
			translationValues.put(value.toLowerCase(), translation);
		}
	}
}
