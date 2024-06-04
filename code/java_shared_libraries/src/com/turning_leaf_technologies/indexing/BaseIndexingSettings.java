package com.turning_leaf_technologies.indexing;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;

public class BaseIndexingSettings {
	protected Long id;
	protected String name;
	protected String marcPath;
	String marcEncoding;
	String groupingClass;
	String recordNumberTag;
	int recordNumberTagInt;
	char recordNumberSubfield;
	String recordNumberPrefix;
	String filenamesToInclude;
	String formatSource;
	String specifiedFormat;
	String specifiedFormatCategory;
	int specifiedFormatBoost;
	long lastUpdateOfChangedRecords;
	long lastUpdateOfAllRecords;
	boolean runFullUpdate;
	boolean regroupAllRecords;
	String treatUnknownLanguageAs;
	String treatUndeterminedLanguageAs;
	String customMarcFieldsToIndexAsKeyword;
	boolean includePersonalAndCorporateNamesInTopics;

	HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();
	HashMap<String, FormatMapValue> formatMapping = new HashMap<>();

	static char getCharFromRecordSet(ResultSet indexingProfilesRS, String fieldName) throws SQLException {
		String subfieldString = indexingProfilesRS.getString(fieldName);
		return AspenStringUtils.convertStringToChar(subfieldString);
	}

	public String getFilenamesToInclude() {
		return filenamesToInclude;
	}

	public Long getId() {
		return id;
	}

	public String getName() {
		return name;
	}

	public String getRecordNumberTag() {
		return recordNumberTag;
	}

	public int getRecordNumberTagInt() {
		return recordNumberTagInt;
	}

	public String getMarcPath() {
		return marcPath;
	}

	public String getMarcEncoding() {
		return marcEncoding;
	}

	public String getRecordNumberPrefix() {
		return recordNumberPrefix;
	}

	public char getRecordNumberSubfield() {
		return recordNumberSubfield;
	}

	public long getLastUpdateOfChangedRecords() {
		return lastUpdateOfChangedRecords;
	}

	public long getLastUpdateOfAllRecords() {
		return lastUpdateOfAllRecords;
	}

	public boolean isRunFullUpdate() {
		return runFullUpdate;
	}

	public boolean isRegroupAllRecords() { return regroupAllRecords; }

	public String getFormatSource() {
		return formatSource;
	}

	public String getSpecifiedFormatCategory() {
		return specifiedFormatCategory;
	}

	public String getGroupingClass() {
		return groupingClass;
	}

	public String getTreatUnknownLanguageAs() {
		return treatUnknownLanguageAs;
	}

	public String getCustomMarcFieldsToIndexAsKeyword() { return customMarcFieldsToIndexAsKeyword; }

	public String getSpecifiedFormat() {
		return specifiedFormat;
	}

	public void setSpecifiedFormat(String specifiedFormat) {
		this.specifiedFormat = specifiedFormat;
	}

	public int getSpecifiedFormatBoost() {
		return specifiedFormatBoost;
	}

	public void setSpecifiedFormatBoost(int specifiedFormatBoost) {
		this.specifiedFormatBoost = specifiedFormatBoost;
	}

	public String getTreatUndeterminedLanguageAs() {
		return treatUndeterminedLanguageAs;
	}

	public void setTreatUndeterminedLanguageAs(String treatUndeterminedLanguageAs) {
		this.treatUndeterminedLanguageAs = treatUndeterminedLanguageAs;
	}

	public boolean isIncludePersonalAndCorporateNamesInTopics() {
		return includePersonalAndCorporateNamesInTopics;
	}

	public void setIncludePersonalAndCorporateNamesInTopics(boolean includePersonalAndCorporateNamesInTopics) {
		this.includePersonalAndCorporateNamesInTopics = includePersonalAndCorporateNamesInTopics;
	}

	public BaseIndexingSettings(String serverName, BaseIndexingLogEntry logEntry) {
		loadSystemTranslationMaps(serverName, logEntry);
	}
	/**
	 * System translation maps are used for things that are not customizable (or that shouldn't be customized)
	 * by library.  For example, translations of language codes, or things where MARC standards define the values.
	 * We can also load translation maps that are specific to an indexing profile.  That is done within
	 * the record processor itself.
	 */
	private void loadSystemTranslationMaps(String serverName, BaseIndexingLogEntry logEntry){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		if (defaultTranslationMapFiles != null) {
			for (File curFile : defaultTranslationMapFiles) {
				String mapName = curFile.getName().replace(".properties", "");
				mapName = mapName.replace("_map", "");
				translationMaps.put(mapName, loadSystemTranslationMap(curFile, logEntry));
			}
			if (serverTranslationMapFiles != null) {
				for (File curFile : serverTranslationMapFiles) {
					String mapName = curFile.getName().replace(".properties", "");
					mapName = mapName.replace("_map", "");
					translationMaps.put(mapName, loadSystemTranslationMap(curFile, logEntry));
				}
			}
		}

		File serverFormatFile = new File("../../sites/" + serverName + "/translation_maps/format_map.csv");
		if (serverFormatFile.exists()) {
			loadSystemFormatMap(serverFormatFile, logEntry);
		}
		File formatFile = new File("../../sites/default/translation_maps/format_map.csv");
		if (formatFile.exists()) {
			loadSystemFormatMap(formatFile, logEntry);
		}

	}

	private HashMap<String, String> loadSystemTranslationMap(File translationMapFile, BaseIndexingLogEntry logEntry) {
		Properties props = new Properties();
		try {
			FileReader translationMapReader = new FileReader(translationMapFile);
			props.load(translationMapReader);
			translationMapReader.close();
		} catch (IOException e) {
			if (logEntry != null) {
				logEntry.incErrors("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
			}
		}
		HashMap<String, String> translationMap = new HashMap<>();
		for (Object keyObj : props.keySet()){
			String key = (String)keyObj;
			translationMap.put(key.toLowerCase(), props.getProperty(key));
		}
		return translationMap;
	}

	private void loadSystemFormatMap(File translationMapFile, BaseIndexingLogEntry logEntry) {
		try {
			CSVReader translationMapReader = new CSVReader(new FileReader(translationMapFile));
			//Skip the first line
			translationMapReader.readNext();

			String[] nextLine;
			while ((nextLine = translationMapReader.readNext()) != null) {
				String value = nextLine[0].trim();
				FormatMapValue formatMapValue;
				if (formatMapping.containsKey(value.toLowerCase())) {
					formatMapValue = formatMapping.get(value.toLowerCase());
				}else{
					formatMapValue = new FormatMapValue();
					formatMapping.put(value.toLowerCase(), formatMapValue);
				}
				formatMapValue.setValue(value);
				formatMapValue.setFormat(nextLine[1].trim());
				formatMapValue.setFormatCategory(nextLine[2].trim());
				formatMapValue.setFormatBoost(Integer.parseInt(nextLine[3].trim()));
				formatMapValue.setAppliesToBibLevel(true);
			}
			translationMapReader.close();
		} catch (IOException e) {
			if (logEntry != null) {
				logEntry.incErrors("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
			}
		}
	}

	boolean hasSystemTranslation(String mapName, String value) {
		return translationMaps.containsKey(mapName) && translationMaps.get(mapName).containsKey(value);
	}

	private final HashSet<String> unableToTranslateWarnings = new HashSet<>();
	private final HashSet<String> missingTranslationMaps = new HashSet<>();

	public String translateValue(String mapName, String value) {
		return translateValue(mapName, value, null, null, null, false);
	}

	public boolean hasTranslation(String mapName, String value) {
		if (value == null) {
			return false;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		if (translationMap != null){
			return translationMap.containsKey(value.toLowerCase());
		}else{
			return false;
		}
	}

	String translateValue(String mapName, String value, String identifier, BaseIndexingLogEntry logEntry, Logger logger, boolean logUnableToTranslateWarnings){
		if (value == null){
			return null;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			if (!missingTranslationMaps.contains(mapName)) {
				missingTranslationMaps.add(mapName);
				if (logEntry != null) {
					logEntry.incErrors("Unable to find system translation map for " + mapName);
				}
			}
			translatedValue = value;
		}else{
			String lowerCaseValue = value.toLowerCase();
			if (translationMap.containsKey(lowerCaseValue)){
				translatedValue = translationMap.get(lowerCaseValue);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						if (logUnableToTranslateWarnings && logger != null && identifier != null) {
							logger.warn("Could not translate '" + concatenatedValue + "' sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null){
			translatedValue = translatedValue.trim();
			if (translatedValue.isEmpty()){
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	public LinkedHashSet<String> translateCollection(String mapName, Set<String> values, String identifier, BaseIndexingLogEntry logEntry, Logger logger, boolean logUnableToTranslateWarnings) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<>();
		for (String value : values){
			String translatedValue = translateValue(mapName, value, identifier, logEntry, logger, logUnableToTranslateWarnings);
			if (translatedValue != null) {
				translatedCollection.add(translatedValue);
			}
		}
		return  translatedCollection;
	}

	public static final int FORMAT_TYPE_MAT_TYPE = 1;
	public static final int FORMAT_TYPE_BIB_LEVEL = 2;
	public static final int FORMAT_TYPE_ITEM_SHELVING_LOCATION = 3;
	public static final int FORMAT_TYPE_ITEM_SUBLOCATION = 4;
	public static final int FORMAT_TYPE_ITEM_COLLECTION = 5;
	public static final int FORMAT_TYPE_ITEM_TYPE = 6;
	public static final int FORMAT_TYPE_ITEM_FORMAT = 7;
	public static final int FORMAT_TYPE_FALLBACK_FORMAT = 8;

	public boolean hasFormat(String originalValue, int formatType){
		if (originalValue == null) {
			return false;
		}
		FormatMapValue formatMapValue = formatMapping.get(originalValue.toLowerCase());
		if (formatMapValue == null) {
			return false;
		}else{
			if (formatType == FORMAT_TYPE_MAT_TYPE) {
				return formatMapValue.isAppliesToMatType() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_BIB_LEVEL) {
				return formatMapValue.isAppliesToBibLevel() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_ITEM_SHELVING_LOCATION) {
				return formatMapValue.isAppliesToItemShelvingLocation() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_ITEM_SUBLOCATION) {
				return formatMapValue.isAppliesToItemSublocation() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_ITEM_COLLECTION) {
				return formatMapValue.isAppliesToItemCollection() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_ITEM_TYPE) {
				return formatMapValue.isAppliesToItemType() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_ITEM_FORMAT) {
				return formatMapValue.isAppliesToItemFormat() && !formatMapValue.getFormat().isEmpty();
			}else if (formatType == FORMAT_TYPE_FALLBACK_FORMAT) {
				return formatMapValue.isAppliesToFallbackFormat() && !formatMapValue.getFormat().isEmpty();
			}else{
				//Invalid format type
				return false;
			}
		}
	}

	public FormatMapValue getFormatMapValue(String originalValue, int formatType){
		if (originalValue == null) {
			return null;
		}
		FormatMapValue formatMapValue = formatMapping.get(originalValue.toLowerCase());
		if (formatMapValue == null) {
			return null;
		}else{
			if (formatType == FORMAT_TYPE_MAT_TYPE) {
				if (formatMapValue.isAppliesToMatType() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_BIB_LEVEL) {
				if (formatMapValue.isAppliesToBibLevel() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_ITEM_SHELVING_LOCATION) {
				if (formatMapValue.isAppliesToItemShelvingLocation() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_ITEM_SUBLOCATION) {
				if (formatMapValue.isAppliesToItemSublocation() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_ITEM_COLLECTION) {
				if (formatMapValue.isAppliesToItemCollection() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_ITEM_TYPE) {
				if (formatMapValue.isAppliesToItemType() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_ITEM_FORMAT) {
				if (formatMapValue.isAppliesToItemFormat() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}else if (formatType == FORMAT_TYPE_FALLBACK_FORMAT) {
				if (formatMapValue.isAppliesToFallbackFormat() && !formatMapValue.getFormat().isEmpty()){
					return formatMapValue;
				}
			}
			//Invalid format type or format type did not apply or format translation was blank
			return null;
		}
	}

	public void addFormatMapValue(FormatMapValue formatMapValue) {
		formatMapping.put(formatMapValue.getValue().toLowerCase(), formatMapValue);
	}
}
