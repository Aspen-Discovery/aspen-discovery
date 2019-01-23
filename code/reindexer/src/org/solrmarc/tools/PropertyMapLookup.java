package org.solrmarc.tools;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.*;

public class PropertyMapLookup {
	/**
	 * map of translation maps. keys are names of translation maps; values are the
	 * translation maps (hence, it's a map of maps)
	 */
	private static Map<String, Map<String, String>>	transMapMap	= null;

	protected static Map<String, String> findMap(String mapName) {
		if (mapName.startsWith("pattern_map:")) mapName = mapName.substring("pattern_map:".length());

		if (transMapMap.containsKey(mapName)) return (transMapMap.get(mapName));

		return null;
	}

	protected static String loadTranslationMap(String translationMapSpec) {
		if (translationMapSpec.length() == 0) return null;

		String mapName = null;
		String mapKeyPrefix = null;
		// translation map is a separate file
		String transMapFname = null;
		if (translationMapSpec.contains("(") && translationMapSpec.endsWith(")")) {
			String mapSpec[] = translationMapSpec.split("(//s|[()])+");
			transMapFname = mapSpec[0];
			mapName = mapSpec[1];
			mapKeyPrefix = mapName;
		} else {
			transMapFname = translationMapSpec;
			mapName = translationMapSpec.replaceAll(".properties", "");
			mapKeyPrefix = "";
		}

		if (findMap(mapName) == null) loadTranslationMapValues(transMapFname, mapName, mapKeyPrefix);

		return mapName;
	}

	/**
	 * Load translation map into transMapMap. Look for translation map in site
	 * specific directory first; if not found, look in org.solrmarc top directory
	 * 
	 * @param transMapName
	 *          name of translation map file to load
	 * @param mapName
	 *          - the name of the Map to go in transMapMap (the key in
	 *          transMapMap)
	 * @param mapKeyPrefix
	 *          - any prefix on individual Map keys (entries in the value in
	 *          transMapMap)
	 */
	private static void loadTranslationMapValues(String transMapName, String mapName, String mapKeyPrefix) {
		Properties props = null;
		props = Utils.loadProperties(new String[0], transMapName);
		// logger.debug("Loading Custom Map: " + transMapName);
		loadTranslationMapValues(props, mapName, mapKeyPrefix);
	}

	/**
	 * populate transMapMap
	 * 
	 * @param transProps
	 *          - the translation map as a Properties object
	 * @param mapName
	 *          - the name of the Map to go in transMapMap (the key in
	 *          transMapMap)
	 * @param mapKeyPrefix
	 *          - any prefix on individual Map keys (entries in the value in
	 *          transMapMap)
	 */
	private static void loadTranslationMapValues(Properties transProps, String mapName, String mapKeyPrefix) {
		Enumeration<?> en = transProps.propertyNames();
		while (en.hasMoreElements()) {
			String property = (String) en.nextElement();
			if (mapKeyPrefix.length() == 0 || property.startsWith(mapKeyPrefix)) {
				String mapKey = property.substring(mapKeyPrefix.length());
				if (mapKey.startsWith(".")) mapKey = mapKey.substring(1);
				String value = transProps.getProperty(property);
				value = value.trim();
				if (value.equals("null")) value = null;

				Map<String, String> valueMap;
				if (transMapMap.containsKey(mapName))
					valueMap = transMapMap.get(mapName);
				else {
					valueMap = new LinkedHashMap<String, String>();
					transMapMap.put(mapName, valueMap);
				}

				valueMap.put(mapKey, value);
			}
		}
	}

	static String	locMapName	= null;
	static String	visMapName	= null;
	static String	libMapName	= null;

	public static String getCustomLocation(String curLoc, String homeLoc, String library) {
		String result = null;
		if (locMapName == null) locMapName = loadTranslationMap("location_map.properties");
		if (visMapName == null) visMapName = loadTranslationMap("shadowed_location_map.properties");
		if (libMapName == null) libMapName = loadTranslationMap("library_map.properties");
		String mappedHomeVis = Utils.remap(homeLoc, findMap(visMapName), true);
		String mappedHomeLoc = Utils.remap(homeLoc, findMap(locMapName), true);
		if (mappedHomeVis.equals("VISIBLE") && mappedHomeLoc == null) {
			String combinedLocMapped = Utils.remap(homeLoc + "__" + "ALDERMAN", findMap(locMapName), true);
			if (combinedLocMapped != null) mappedHomeLoc = combinedLocMapped;
		}
		String mappedLib = library;
		if (curLoc != null) {
			String mappedCurLoc = Utils.remap(curLoc, findMap(locMapName), true);
			String mappedCurVis = Utils.remap(curLoc, findMap(visMapName), true);
			if (mappedCurVis.equals("HIDDEN")) return (result); // this copy of the
																													// item is Hidden, go
																													// no further
			if (mappedCurLoc != null) {
				if (mappedCurLoc.contains("$m")) {
					// mappedCurLoc.replaceAll("$l", mappedHomeLoc);
					mappedCurLoc = mappedCurLoc.replaceAll("[$]m", mappedLib);
				}
				result = mappedCurLoc;
				return (result); // Used
			}
		}
		if (mappedHomeVis.equals("HIDDEN")) return (result); // this copy of the
																													// item is Hidden, go
																													// no further
		if (mappedHomeVis != null && mappedHomeLoc.contains("$")) {
			mappedHomeLoc.replaceAll("$m", mappedLib);
		}
		result = mappedHomeLoc;
		return result;
	}

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		transMapMap = new HashMap<String, Map<String, String>>();
		String mapName;
		String mapSpec = "location_map.properties";
		try {
			mapName = loadTranslationMap(mapSpec);
		} catch (IllegalArgumentException e) {
			// logger.error("Unable to find file containing specified translation map ("
			// + fieldDef[3] + ")");
			throw new IllegalArgumentException("Error: Problems reading specified translation map (" + mapSpec + ")");
		}
		String line;
		BufferedReader in = new BufferedReader(new InputStreamReader(System.in));
		try {
			Map<String, String> map = findMap(mapName);
			while ((line = in.readLine()) != null) {
				String parts[] = line.split(" +");
				String part1Mapped = Utils.remap(parts[1], map, true);
				String part3Mapped = Utils.remap(parts[3], map, true);
				String count = parts.length > 4 ? parts[4] : null;
				String mapValue;
				if ((part1Mapped == null || part1Mapped.equals("null")) && (part3Mapped == null || part3Mapped.equals("null")))
					mapValue = "";
				else if (part1Mapped == null || part1Mapped.equals("null"))
					mapValue = part3Mapped;
				else if (part3Mapped == null || part3Mapped.equals("null"))
					mapValue = part1Mapped;
				else
					mapValue = part1Mapped + " ; " + part3Mapped;
				String newMapValue = getCustomLocation(parts[1], parts[3], "[LibraryName]");
				System.out.println(parts[1] + "\t" + parts[3] + "\t" + mapValue + "\t" + newMapValue + "\t" + count);
			}
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}

}
