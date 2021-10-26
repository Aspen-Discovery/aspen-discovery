package com.turning_leaf_technologies.strings;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.util.Collection;
import java.util.HashSet;
import java.util.Locale;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class StringUtils {
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
				//noinspection StatementWithEmptyBody
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

			if (currResult.length() == 0) return currResult;

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
		if (subfieldString != null && subfieldString.length() > 0) {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

	public static String stripNonValidXMLCharacters(String in) {
		StringBuilder out = new StringBuilder(); // Used to hold the output.
		char current; // Used to reference the current character.

		if (in == null || ("".equals(in))) return ""; // vacancy test.
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

	private static Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");

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

	private static Pattern trimPunctuationPattern = Pattern.compile("^(.*?)[-\\s/,.;|]+$");

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

	private static Pattern replacePipePattern = Pattern.compile("\\|");

	public static Collection<String> normalizeSubjects(Set<String> fieldList) {
		HashSet<String> trimmedCollection = new HashSet<>();
		for (String field : fieldList) {
			String trimmedField = trimTrailingPunctuation(field);
			trimmedField = replacePipePattern.matcher(trimmedField).replaceAll(" -- ");
			trimmedCollection.add(trimmedField);
		}
		return trimmedCollection;
	}

	public static String normalizeSubject(String field) {
		String trimmedField = trimTrailingPunctuation(field);
		trimmedField = replacePipePattern.matcher(trimmedField).replaceAll(" -- ");
		return trimmedField;
	}

	/**
	 * Remove single square bracket characters if they are the start and/or end
	 * chars (matched or unmatched) and are the only square bracket chars in the
	 * string.
	 */
	public static String removeOuterBrackets(String origStr) {
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
		if (stringToTest.length() == 0) {
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

	private static Pattern nonAlphaNumerics = Pattern.compile("[^a-z0-9_]");
	public static String toLowerCaseNoSpecialChars(String originalValue){
		originalValue = originalValue.toLowerCase();
		return nonAlphaNumerics.matcher(originalValue).replaceAll("_");
	}
}
