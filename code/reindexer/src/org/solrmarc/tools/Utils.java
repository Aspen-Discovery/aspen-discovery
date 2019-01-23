package org.solrmarc.tools;

import org.apache.log4j.Logger;

import java.io.*;
import java.net.URL;
import java.util.*;
import java.util.regex.Pattern;

/**
 * General utility functions for org.solrmarc
 * 
 * @author Wayne Graham
 * @version $Id: Utils.java 1581 2011-12-19 21:21:52Z rh9ec@virginia.edu $
 */
public final class Utils {

	protected static Logger							logger																= Logger.getLogger(Utils.class.getName());

	/**
	 * Default Constructor It's private, so it can't be instantiated by other
	 * objects
	 * 
	 */
	private Utils() {
	}

	/**
	 * load a properties file into a Properties object
	 * 
	 * @param propertyPaths
	 *          the directories to search for the properties file
	 * @param propertyFileName
	 *          name of the sought properties file
	 * @return Properties object
	 */
	static Properties loadProperties(String propertyPaths[], String propertyFileName) {
		return (loadProperties(propertyPaths, propertyFileName, false, null));
	}

	/**
	 * load a properties file into a Properties object
	 * 
	 * @param propertyPaths
	 *          the directories to search for the properties file
	 * @param propertyFileName
	 *          name of the sought properties file
	 * @param showName
	 *          whether the name of the file/resource being read should be shown.
	 * @return Properties object
	 */
	private static Properties loadProperties(String propertyPaths[], String propertyFileName, boolean showName, String filenameProperty) {
		String inputStreamSource[] = new String[] { null };
		InputStream in = getPropertyFileInputStream(propertyPaths, propertyFileName, showName, inputStreamSource);
		String errmsg = "Fatal error: Unable to find specified properties file: " + propertyFileName;

		// load the properties
		Properties props = new Properties();
		try {
			if (propertyFileName.endsWith(".xml") || propertyFileName.endsWith(".XML")) {
				props.loadFromXML(in);
			} else {
				props.load(in);
			}
			in.close();
			if (filenameProperty != null && inputStreamSource[0] != null) {
				File tmpFile = new File(inputStreamSource[0]);

				props.setProperty(filenameProperty, tmpFile.getParent());
			}
		} catch (IOException e) {
			throw new IllegalArgumentException(errmsg);
		}
		return props;
	}

	@SuppressWarnings("resource")
	private static InputStream getPropertyFileInputStream(String[] propertyPaths, String propertyFileName, boolean showName, String inputSource[]) {
		InputStream in = null;
		// look for properties file in paths
		String verboseStr = System.getProperty("marc.test.verbose");
		boolean verbose = (verboseStr != null && verboseStr.equalsIgnoreCase("true"));
		String lookedIn = "";
		if (propertyPaths != null) {
			File propertyFile = new File(propertyFileName);
			int pathCnt = 0;
			do {
				if (propertyFile.exists() && propertyFile.isFile() && propertyFile.canRead()) {
					try {
						in = new FileInputStream(propertyFile);
						if (inputSource != null && inputSource.length >= 1) {
							inputSource[0] = propertyFile.getAbsolutePath();
						}
						if (showName){
							logger.info("Opening file: " + propertyFile.getAbsolutePath());
						}else{
							logger.debug("Opening file: " + propertyFile.getAbsolutePath());
						}
					} catch (FileNotFoundException e) {
						// simply eat this exception since we should only try to open the
						// file if we previously
						// determined that the file exists and is readable.
					}
					break; // we found it!
				}
				if (verbose) lookedIn = lookedIn + propertyFile.getAbsolutePath() + "\n";
				if (propertyPaths != null && pathCnt < propertyPaths.length) {
					propertyFile = new File(propertyPaths[pathCnt], propertyFileName);
				}
				pathCnt++;
			} while (propertyPaths != null && pathCnt <= propertyPaths.length);
		}
		// if we didn't find it as a file, look for it as a URL
		String errmsg = "Fatal error: Unable to find specified properties file: " + propertyFileName;
		if (verbose) errmsg = errmsg + "\n Looked in: " + lookedIn;
		if (in == null) {
			Utils utilObj = new Utils();
			URL url = utilObj.getClass().getClassLoader().getResource(propertyFileName);
			if (url == null) url = utilObj.getClass().getResource("/" + propertyFileName);
			if (url == null) {
				logger.error(errmsg);
				throw new IllegalArgumentException(errmsg);
			}
			if (showName) {
				logger.info("Opening resource via URL: " + url.toString());
			} else {
				logger.debug("Opening resource via URL: " + url.toString());
			}

			/*
			 * if (url == null) url =
			 * utilObj.getClass().getClassLoader().getResource(propertyPath + "/" +
			 * propertyFileName); if (url == null) url =
			 * utilObj.getClass().getResource("/" + propertyPath + "/" +
			 * propertyFileName);
			 */
			try {
				in = url.openStream();
			} catch (IOException e) {
				throw new IllegalArgumentException(errmsg);
			}
		}
		return (in);
	}

	private static Pattern cleanJrSrPattern = Pattern.compile(".*[JS]r\\.$");
	private static Pattern cleaner1Pattern = Pattern.compile(".*\\w\\w\\.$");
	private static Pattern cleaner2Pattern = Pattern.compile(".*\\p{L}\\p{L}\\.$");
	private static Pattern cleaner3Pattern = Pattern.compile(".*\\w\\p{InCombiningDiacriticalMarks}?\\w\\p{InCombiningDiacriticalMarks}?\\.$");
	private static Pattern cleaner4Pattern = Pattern.compile(".*\\p{Punct}\\.$");
	/**
	 * Removes trailing characters (space, comma, slash, semicolon, colon),
	 * trailing period if it is preceded by at least three letters, and single
	 * square bracket characters if they are the start and/or end chars of the
	 * cleaned string
	 * 
	 * @param origStr
	 *          String to clean
	 * @return cleaned string
	 */
	public static String cleanData(String origStr) {
		String currResult = origStr;
		String prevResult;
		do {
			prevResult = currResult;
			currResult = currResult.trim();

			currResult = currResult.replaceAll(" *([,/;:])$", "");

			// trailing period removed in certain circumstances
			if (currResult.endsWith(".")) {
				if (cleanJrSrPattern.matcher(currResult).matches()) {
					// dont strip period off of Jr. or Sr.
				} else if (cleaner1Pattern.matcher(currResult).matches()) {
					currResult = currResult.substring(0, currResult.length() - 1);
				} else if (cleaner2Pattern.matcher(currResult).matches()) {
					currResult = currResult.substring(0, currResult.length() - 1);
				} else if (cleaner3Pattern.matcher(currResult).matches()) {
					currResult = currResult.substring(0, currResult.length() - 1);
				} else if (cleaner4Pattern.matcher(currResult).matches()) {
					currResult = currResult.substring(0, currResult.length() - 1);
				}
			}

			currResult = removeOuterBrackets(currResult);

			if (currResult.length() == 0) return currResult;

		} while (!currResult.equals(prevResult));

		// if (!currResult.equals(origStr))
		// System.out.println(origStr + " -> "+ currResult);

		return currResult;
	}

	/**
	 * Remove single square bracket characters if they are the start and/or end
	 * chars (matched or unmatched) and are the only square bracket chars in the
	 * string.
	 */
	private static String removeOuterBrackets(String origStr) {
		if (origStr == null || origStr.length() == 0) return origStr;

		String result = origStr.trim();

		if (result.length() > 0) {
			boolean openBracketFirst = result.charAt(0) == '[';
			boolean closeBracketLast = result.endsWith("]");
			if (openBracketFirst && closeBracketLast && result.indexOf('[', 1) == -1 && result.lastIndexOf(']', result.length() - 2) == -1)
				// only square brackets are at beginning and end
				result = result.substring(1, result.length() - 1);
			else if (openBracketFirst && result.indexOf(']') == -1)
				// starts with '[' but no ']'; remove open bracket
				result = result.substring(1);
			else if (closeBracketLast && result.indexOf('[') == -1)
			// ends with ']' but no '['; remove close bracket
				result = result.substring(0, result.length() - 1);
		}

		return result.trim();
	}

	/**
	 * Remap a field value. If the field value is not present in the map, then: if
	 * "displayRawIfMissing" is a key in the map, then the raw field value is
	 * used. if "displayRawIfMissing" is not a key in the map, and the
	 * allowDefault param is set to true, then if the map contains "__DEFAULT" as
	 * a key, the value of "__DEFAULT" in the map is used; if allowDefault is true
	 * and there is neither "displayRawIfMissing" nor "__DEFAULT", as a key in the
	 * map, then if the map contains an empty key, the map value of the empty key
	 * is used. NOTE: If the spec for a field is supposed to contain all matching
	 * values, then the default lookup needs to be done here. If the spec for a
	 * field is only supposed to return the first matching mappable value, then
	 * the default mapping should be done in the calling method
	 *
	 * @param fieldVal
	 *          - the raw value to be mapped
	 * @param map
	 *          - the map to be used
	 * @param allowDefault
	 *          - if "displayRawIfMissing" is not a key in the map, and this is to
	 *          true, then if the map contains "__DEFAULT" as a key, the value of
	 *          "__DEFAULT" in the map is used.
	 * @return the new value, as determined by the mapping.
	 */
	static String remap(String fieldVal, Map<String, String> map, boolean allowDefault) {
		String result = null;

		if (map.keySet().contains("pattern_0")) {
			for (int i = 0; i < map.keySet().size(); i++) {
				String patternStr = map.get("pattern_" + i);
				String parts[] = patternStr.split("=>");
				if (containsMatch(fieldVal, parts[0])) {
					String newVal = parts[1];
					if (parts[1].contains("$")) {
						newVal = fieldVal.replaceAll(parts[0], parts[1]);
						fieldVal = newVal;
					}
					result = newVal;
				}
			}
		}
		if (map.containsKey(fieldVal)) {
			result = map.get(fieldVal);
		} else if (map.containsKey("displayRawIfMissing")) {
			result = fieldVal;
		} else if (allowDefault && map.containsKey("__DEFAULT")) {
			result = map.get("__DEFAULT");
		} else if (allowDefault && map.containsKey("")) {
			result = map.get("");
		}
		if (result == null || result.length() == 0) return null;
		return result;
	}

	private static boolean containsMatch(String val, String pattern) {
		String rep = val.replaceFirst(pattern, "###match###");

		if (!rep.equals(val)) {
			return true;
		}

		return false;
	}

}
