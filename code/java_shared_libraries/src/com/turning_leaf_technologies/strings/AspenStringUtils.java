package com.turning_leaf_technologies.strings;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.util.Collection;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class AspenStringUtils {
	private static final Pattern cleanJrSrPattern = Pattern.compile(".*[JS]r\\.$");
	private static final Pattern cleaner1Pattern = Pattern.compile(".*\\w\\w\\.$");
	private static final Pattern cleaner2Pattern = Pattern.compile(".*\\p{L}\\p{L}\\.$");
	private static final Pattern cleaner3Pattern = Pattern.compile(".*\\w\\p{InCombiningDiacriticalMarks}?\\w\\p{InCombiningDiacriticalMarks}?\\.$");
	private static final Pattern cleaner4Pattern = Pattern.compile(".*\\p{Punct}\\.$");

	/**
	 * Removes trailing characters (space, comma, slash, semicolon, colon),
	 * trailing period if it is preceded by at least three letters, and single
	 * square bracket characters if they are the start and/or end chars of the
	 * cleaned string
	 *
	 * @param origStr String to clean
	 * @return cleaned string
	 */
	public static String cleanDataForSolr(String origStr) {
		String currResult = origStr;
		String prevResult;
		do {
			prevResult = currResult;
			currResult = currResult.trim();

			currResult = currResult.replaceAll(" *([,/;:])$", "");

			// trailing period removed in certain circumstances
			if (currResult.endsWith(".")) {
				if (cleanJrSrPattern.matcher(currResult).matches()) {
					// don't strip period off of Jr. or Sr.
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

			if (currResult.isEmpty()) return currResult;

		} while (!currResult.equals(prevResult));

		// if (!currResult.equals(origStr))
		// System.out.println(origStr + " -> "+ currResult);

		return currResult;
	}

	public static String trimTo(int maxCharacters, String stringToTrim) {
		if (stringToTrim == null) {
			return null;
		}
		if (stringToTrim.length() > maxCharacters) {
			stringToTrim = stringToTrim.substring(0, maxCharacters);
		}
		return stringToTrim.trim();
	}

	public static boolean compareStrings(String curLine1, String curLine2) {
		return curLine1 == null && curLine2 == null || !(curLine1 == null || curLine2 == null) && curLine1.equals(curLine2);
	}

	public static String convertStreamToString(InputStream is) throws IOException {
		/*
		 * To convert the InputStream to String we use the Reader.read(char[]
		 * buffer) method. We iterate until the Reader return -1 which means there's
		 * no more data to read. We use the StringWriter class to produce the
		 * string.
		 */
		if (is != null) {
			Writer writer = new StringWriter();

			char[] buffer = new char[1024];
			try {
				Reader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
				int n;
				while ((n = reader.read(buffer)) != -1) {
					writer.write(buffer, 0, n);
				}
			} finally {
				is.close();
			}
			buffer = null;
			return writer.toString();
		} else {
			return "";
		}
	}

	public static char convertStringToChar(String subfieldString) {
		char subfield = ' ';
		if (subfieldString != null && !subfieldString.isEmpty()) {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

	static final Pattern discPattern = Pattern.compile("(\\d+)\\s+(?:\\w+\\s+)?discs?");
	static final Pattern timeColonPattern = Pattern.compile("(\\d+):(\\d{2}):(\\d{2})");
	static final Pattern hrPattern = Pattern.compile(
		"(?:ca\\.\\s*)?(\\d+(?:\\.\\d+)?(?:\\s+\\d+/\\d+)?)\\s*(?:hours?|hrs?\\.|h\\b)"
	);
	static final Pattern minPattern = Pattern.compile(
		"(?:ca\\.\\s*)?(\\d+)\\s*(?:minutes?|min\\.|m(?!s))"
	);

	public static int extractTotalMinutes(String input) {
		// Check for "N disc/discs" at the start, used when "each" appears later to calculate total time
		// Allow a word in-between the number and disc/discs for matching
		Matcher discMatcher = discPattern.matcher(input);
		int discCount = discMatcher.find() ? Integer.parseInt(discMatcher.group(1)) : 1;
		boolean hasEach = input.contains("each");

		// Handle HH:mm:ss format (e.g., "06:02:00")
		Matcher timeColonMatcher = timeColonPattern.matcher(input);
		if (timeColonMatcher.find()) {
			int hours = Integer.parseInt(timeColonMatcher.group(1));
			int minutes = Integer.parseInt(timeColonMatcher.group(2));
			// group(3) is seconds — ignored, but matched to confirm HH:mm:ss format
			return hours * 60 + minutes;
		}

		// Handle hours/minutes in word or abbreviated form, ignore comma separation, include decimal and fraction values
		// e.g., "16 hours, 10 minutes" / "6 hr. 2 min." / "7.5 hr." / "11 1/2 hr." / "6h 2m"
		Matcher hrMatcher = hrPattern.matcher(input);
		Matcher minMatcher = minPattern.matcher(input);

		// Hours: convert decimal/fraction to total minutes (e.g. 7.5 hr -> 450 min)
		int hours   = hrMatcher.find()  ? (int) Math.round(parseMixedNumber(hrMatcher.group(1).trim()) * 60) : 0;
		// Minutes: round to nearest minute
		int minutes = minMatcher.find() ? (int) Math.round(Double.parseDouble(minMatcher.group(1))) : 0;

		if (hours > 0 || minutes > 0) {
			int total = hours + minutes;
			return hasEach ? total * discCount : total;
		}

		return 0;
	}

	static final Pattern mixedPattern = Pattern.compile("(\\d+)\\s+(\\d+)/(\\d+)");
	private static double parseMixedNumber(String s) {
		Matcher m = mixedPattern.matcher(s);
		if (m.find()) {
			double whole = Double.parseDouble(m.group(1));
			double num   = Double.parseDouble(m.group(2));
			double denom = Double.parseDouble(m.group(3));
			return whole + num / denom;
		}
		return Double.parseDouble(s);
	}

	public static String stripNonValidXMLCharacters(String in) {
		StringBuilder out = new StringBuilder(); // Used to hold the output.
		char current; // Used to reference the current character.

		if (in == null || (in.isEmpty())) return ""; // vacancy test.
		for (int i = 0; i < in.length(); i++) {
			current = in.charAt(i); // NOTE: No IndexOutOfBoundsException caught here; it should not happen.
			if ((current == 0x9) ||
					(current == 0xA) ||
					(current == 0xD) ||
					((current >= 0x20) && (current <= 0xD7FF)) ||
					((current >= 0xE000) && (current <= 0xFFFD))) {
				out.append(current);
			}
		}
		return out.toString();
	}

	private static final Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");

	public static String makeValueSortable(String curTitle) {
		if (curTitle == null) return "";
		String sortTitle = curTitle.toLowerCase();
		Matcher sortMatcher = sortTrimmingPattern.matcher(sortTitle);
		if (sortMatcher.matches()) {
			sortTitle = sortMatcher.group(1);
		}
		sortTitle = sortTitle.trim();
		return sortTitle;
	}

	private static final Pattern trimPunctuationPattern = Pattern.compile("^(.*?)[\\s/,.;:|]+$");

	public static String trimTrailingPunctuation(String format) {
		if (format == null) {
			return "";
		}
		Matcher trimPunctuationMatcher = trimPunctuationPattern.matcher(format);
		if (trimPunctuationMatcher.matches()) {
			return trimPunctuationMatcher.group(1);
		} else {
			return format;
		}
	}

	public static StringBuilder trimTrailingPunctuation(StringBuilder format) {
		if (format == null) {
			return new StringBuilder();
		}
		Matcher trimPunctuationMatcher = trimPunctuationPattern.matcher(format);
		if (trimPunctuationMatcher.matches()) {
			return new StringBuilder(trimPunctuationMatcher.group(1));
		} else {
			return format;
		}
	}

	public static Collection<String> trimTrailingPunctuation(Set<String> fieldList) {
		HashSet<String> trimmedCollection = new HashSet<>();
		for (String field : fieldList) {
			trimmedCollection.add(trimTrailingPunctuation(field));
		}
		return trimmedCollection;
	}

	private static final Pattern replacePipePattern = Pattern.compile("\\|");

	public static Collection<String> normalizeSubjects(Set<String> fieldList) {
		HashSet<String> trimmedCollection = new HashSet<>();
		for (String field : fieldList) {
			String trimmedField = trimTrailingPunctuation(field);
			trimmedField = replacePipePattern.matcher(trimmedField).replaceAll(" -- ").trim();
			trimmedCollection.add(trimmedField);
		}
		return trimmedCollection;
	}

	public static String normalizeSubject(String field) {
		String trimmedField = trimTrailingPunctuation(field);
		trimmedField = replacePipePattern.matcher(trimmedField).replaceAll(" -- ").trim();
		return trimmedField;
	}

	/**
	 * Remove single square bracket characters if they are the start and/or end
	 * chars (matched or unmatched) and are the only square bracket chars in the
	 * string.
	 */
	public static String removeOuterBrackets(String origStr) {
		if (origStr == null || origStr.isEmpty()) return origStr;

		String result = origStr.trim();

		if (!result.isEmpty()) {
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

	public static String swapFirstLastNames(String author) {
		//Need to swap the first and last names
		if (author.contains(" ")) {
			String[] authorParts = author.split("\\s+");
			StringBuilder tmpAuthor = new StringBuilder();
			for (int i = 0; i < authorParts.length - 1; i++) {
				tmpAuthor.append(authorParts[i]).append(" ");
			}
			author = authorParts[authorParts.length - 1] + ", " + tmpAuthor.toString();
		}
		return author;
	}

	public static boolean isNumeric(String stringToTest) {
		if (stringToTest == null) {
			return false;
		}
		if (stringToTest.isEmpty()) {
			return false;
		}
		int numDecimals = 0;
		for (char curChar : stringToTest.toCharArray()) {
			if (!Character.isDigit(curChar) && curChar != '.') {
				return false;
			}
			if (curChar == '.') {
				numDecimals++;
			}
		}
		return numDecimals <= 1;
	}

	public static boolean isInteger(String stringToTest) {
		if (stringToTest == null) {
			return false;
		}
		if (stringToTest.isEmpty()) {
			return false;
		}
		for (char curChar : stringToTest.toCharArray()) {
			if (!Character.isDigit(curChar)) {
				return false;
			}
		}
		return true;
	}

	public static String getInputFromCommandLine(String prompt) {
		//Prompt for the work to process
		System.out.print(prompt + ": ");

		//  open up standard input
		BufferedReader br = new BufferedReader(new InputStreamReader(System.in));

		//  read the work from the command-line; need to use try/catch with the
		//  readLine() method
		String value = null;
		try {
			value = br.readLine().trim();
		} catch (IOException ioe) {
			System.out.println("IO error trying to read " + prompt);
			System.exit(1);
		}
		return value;
	}

	private static final Pattern nonAlphaNumerics = Pattern.compile("[^a-z0-9_]");
	public static String toLowerCaseNoSpecialChars(String originalValue){
		originalValue = originalValue.toLowerCase();
		return nonAlphaNumerics.matcher(originalValue).replaceAll("_");
	}

	public static String formatBytes(long bytes) {
		return AspenStringUtils.formatBytes(bytes, 2);
	}

	public static String formatBytes(long bytes, int precision) {
		String[] units = {"B", "KB", "MB", "GB", "TB"};

		bytes = Math.max(bytes, 0);
		int pow = (int) Math.floor(Math.log(bytes) / Math.log(1024));
		pow = Math.min(pow, units.length - 1);

		double result = bytes / Math.pow(1024, pow);  // Using Math.pow

		//noinspection MalformedFormatString
		return String.format("%." + precision + "f %s", result, units[pow]);
	}
}
