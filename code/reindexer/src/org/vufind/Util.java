package org.vufind;

import org.apache.log4j.Logger;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.ByteBuffer;
import java.nio.channels.FileChannel;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class Util {
	static byte[] readFileBytes(String filename) throws IOException {
		FileInputStream f = new FileInputStream( filename );
		FileChannel fileChannel = f.getChannel();
		long fileSize = fileChannel.size();
		byte[] fileBytes = new byte[(int)fileSize];
		ByteBuffer buffer = ByteBuffer.wrap(fileBytes);
		fileChannel.read(buffer);
		fileChannel.close();
		f.close();
		return fileBytes;
	}

	static String getCRSeparatedString(Object values) {
		StringBuilder crSeparatedString = new StringBuilder();
		if (values instanceof String){
			crSeparatedString.append((String)values);
		}else if (values instanceof Iterable){
			@SuppressWarnings("unchecked")
			Iterable<String> valuesIterable = (Iterable<String>)values;
			for (String curValue : valuesIterable) {
				if (crSeparatedString.length() > 0) {
					crSeparatedString.append("\r\n");
				}
				crSeparatedString.append(curValue);
			}
		}
		return crSeparatedString.toString();
	}
	
	static String getCRSeparatedStringFromSet(Set<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCRSeparatedString(HashSet<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCsvSeparatedString(Set<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append(",");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCsvSeparatedStringFromLongs(Set<Long> values) {
		StringBuilder crSeparatedString = new StringBuilder();
		for (Long curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append(",");
			}
			crSeparatedString.append(curValue.toString());
		}
		return crSeparatedString.toString();
	}

	static boolean copyFile(File sourceFile, File destFile) throws IOException {
		if (!sourceFile.exists()){
			return false;
		}
		if (!destFile.exists()) {
			if (!destFile.createNewFile()){
				return false;
			}
		}

		FileChannel source = null;
		FileChannel destination = null;

		try {
			source = new FileInputStream(sourceFile).getChannel();
			destination = new FileOutputStream(destFile).getChannel();
			destination.transferFrom(source, 0, source.size());
		}catch (Exception e){
			return false;
		} finally {
			if (source != null) {
				source.close();
			}
			if (destination != null) {
				destination.close();
			}
		}
		return true;
	}

	static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.length() > 0 && value.charAt(0) == '"') {
			value = value.substring(1);
		}
		if (value.length() > 0 && value.charAt(value.length() -1) == '"') {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	static String trimTo(int maxCharacters, String stringToTrim) {
		if (stringToTrim == null) {
			return null;
		}
		if (stringToTrim.length() > maxCharacters) {
			stringToTrim = stringToTrim.substring(0, maxCharacters);
		}
		return stringToTrim.trim();
	}

	static URLPostResponse getURL(String url, Logger logger) {
		URLPostResponse retVal;
		HttpURLConnection conn;
		try {
			logger.debug("Getting URL " + url);
			URL emptyIndexURL = new URL(url);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {
					
					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setConnectTimeout(3000);
			conn.setReadTimeout(450000);
			//logger.debug("  Opened connection");
			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				//logger.debug("  Got successful response");
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				//logger.debug("  Finished reading response");
				rd.close();
				retVal = new URLPostResponse(true, 200, response.toString());
			} else {
				logger.error("Received error " + conn.getResponseCode() + " getting " + url);
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response");

				rd.close();
				retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}
		logger.debug("  Finished calling url");
		return retVal;
	}

	private static Pattern trimPunctuationPattern = Pattern.compile("^(.*?)[\\s/,.;|]+$");
	static String trimTrailingPunctuation(String format) {
		if (format == null){
			return "";
		}
		Matcher trimPunctuationMatcher = trimPunctuationPattern.matcher(format);
		if (trimPunctuationMatcher.matches()){
			return trimPunctuationMatcher.group(1);
		}else{
			return format;
		}
	}

	static StringBuilder trimTrailingPunctuation(StringBuilder format) {
		if (format == null){
			return new StringBuilder();
		}
		Matcher trimPunctuationMatcher = trimPunctuationPattern.matcher(format);
		if (trimPunctuationMatcher.matches()){
			return new StringBuilder(trimPunctuationMatcher.group(1));
		}else{
			return format;
		}
	}

	static Collection<String> trimTrailingPunctuation(Set<String> fieldList) {
		HashSet<String> trimmedCollection = new HashSet<>();
		for (String field : fieldList){
			trimmedCollection.add(trimTrailingPunctuation(field));
		}
		return trimmedCollection;
	}

	private static Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");
	static String makeValueSortable(String curTitle) {
		if (curTitle == null) return "";
		String sortTitle = curTitle.toLowerCase();
		Matcher sortMatcher = sortTrimmingPattern.matcher(sortTitle);
		if (sortMatcher.matches()) {
			sortTitle = sortMatcher.group(1);
		}
		sortTitle = sortTitle.replaceAll("\\W", " "); //get rid of non alpha numeric characters
		sortTitle = sortTitle.replaceAll("\\s{2,}", " "); //get rid of duplicate spaces 
		sortTitle = sortTitle.trim();
		return sortTitle;
	}
	
	static Long getDaysSinceAddedForDate(Date curDate){
		if (curDate == null){
			return null;
		}
		return (indexDate.getTime() - curDate.getTime()) / (1000 * 60 * 60 * 24);
	}
	private static Date indexDate = new Date();
	static Date getIndexDate(){
		return indexDate;
	}
	static LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
		if (curDate == null) {
			return null;
		}
		long timeDifferenceDays = (indexDate.getTime() - curDate.getTime())
				/ (1000 * 60 * 60 * 24);
		return getTimeSinceAdded(timeDifferenceDays);
	}
	static LinkedHashSet<String> getTimeSinceAdded(long timeDifferenceDays){
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		LinkedHashSet<String> result = new LinkedHashSet<>();
		if (timeDifferenceDays < 0) {
			result.add("On Order");
		}
		if (timeDifferenceDays <= 1) {
			result.add("Day");
		}
		if (timeDifferenceDays <= 7) {
			result.add("Week");
		}
		if (timeDifferenceDays <= 30) {
			result.add("Month");
		}
		if (timeDifferenceDays <= 60) {
			result.add("2 Months");
		}
		if (timeDifferenceDays <= 90) {
			result.add("Quarter");
		}
		if (timeDifferenceDays <= 180) {
			result.add("Six Months");
		}
		if (timeDifferenceDays <= 365) {
			result.add("Year");
		}
		return result;
	}


	static boolean isNumeric(String stringToTest) {
		if (stringToTest == null){
			return false;
		}
		if (stringToTest.length() == 0){
			return false;
		}
		int numDecimals = 0;
		for (char curChar : stringToTest.toCharArray()){
			if (!Character.isDigit(curChar) && curChar != '.'){
				return false;
			}if (curChar == '.'){
				numDecimals++;
			}
		}
		return numDecimals <= 1;
	}

	static boolean compareFiles(File file1, File file2, Logger logger){
		try {
			BufferedReader reader1 = new BufferedReader(new FileReader(file1));
			BufferedReader reader2 = new BufferedReader(new FileReader(file2));
			String curLine1 = reader1.readLine();
			String curLine2 = reader2.readLine();
			boolean filesMatch = Util.compareStrings(curLine1, curLine2);
			while (curLine1 != null && curLine2 != null && filesMatch){
				curLine1 = reader1.readLine();
				curLine2 = reader2.readLine();
				filesMatch = Util.compareStrings(curLine1, curLine2);
			}
			return filesMatch;
		}catch (IOException e){
			logger.error("Error comparing files", e);
			return false;
		}
	}

	private static boolean compareStrings(String curLine1, String curLine2) {
		return curLine1 == null && curLine2 == null || !(curLine1 == null || curLine2 == null) && curLine1.equals(curLine2);
	}

	private final static Pattern FOUR_DIGIT_PATTERN_BRACES							= Pattern.compile("\\[[12]\\d{3}\\]");
	private final static Pattern				FOUR_DIGIT_PATTERN_ONE_BRACE					= Pattern.compile("\\[[12]\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_STARTING_WITH_1_2	= Pattern.compile("(20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_1						= Pattern.compile("l\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_2						= Pattern.compile("\\[19\\]\\d{2}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_3						= Pattern.compile("(20|19|18|17|16|15)[0-9][-?0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_4						= Pattern.compile("i.e. (20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				BC_DATE_PATTERN												= Pattern.compile("[0-9]+ [Bb][.]?[Cc][.]?");

	/**
	 * Cleans non-digits from a String
	 *
	 * @param date
	 *          String to parse
	 * @return Numeric part of date String (or null)
	 */
	static String cleanDate(final String date) {
		if (date == null || date.length() == 0){
			return null;
		}
		Matcher matcher_braces = FOUR_DIGIT_PATTERN_BRACES.matcher(date);

		String cleanDate = null; // raises DD-anomaly

		if (matcher_braces.find()) {
			cleanDate = matcher_braces.group();
			cleanDate = removeOuterBrackets(cleanDate);
		} else{
			Matcher matcher_ie_date = FOUR_DIGIT_PATTERN_OTHER_4.matcher(date);
			if (matcher_ie_date.find()) {
				cleanDate = matcher_ie_date.group().replaceAll("i.e. ", "");
			} else {
				Matcher matcher_one_brace = FOUR_DIGIT_PATTERN_ONE_BRACE.matcher(date);
				if (matcher_one_brace.find()) {
					cleanDate = matcher_one_brace.group();
					cleanDate = removeOuterBrackets(cleanDate);
				} else {
					Matcher matcher_bc_date = BC_DATE_PATTERN.matcher(date);
					if (matcher_bc_date.find()) {
						cleanDate = null;
					} else {
						Matcher matcher_start_with_1_2 = FOUR_DIGIT_PATTERN_STARTING_WITH_1_2.matcher(date);
						if (matcher_start_with_1_2.find()) {
							cleanDate = matcher_start_with_1_2.group();
						} else {
							Matcher matcher_l_plus_three_digits = FOUR_DIGIT_PATTERN_OTHER_1.matcher(date);
							if (matcher_l_plus_three_digits.find()) {
								cleanDate = matcher_l_plus_three_digits.group().replaceAll("l", "1");
							} else {
								Matcher matcher_bracket_19_plus_two_digits = FOUR_DIGIT_PATTERN_OTHER_2.matcher(date);
								if (matcher_bracket_19_plus_two_digits.find()) {
									cleanDate = matcher_bracket_19_plus_two_digits.group().replaceAll("\\[", "").replaceAll("\\]", "");
								} else{
									Matcher matcher_three_digits_plus_unk = FOUR_DIGIT_PATTERN_OTHER_3.matcher(date);
									if (matcher_three_digits_plus_unk.find()) {
										cleanDate = matcher_three_digits_plus_unk.group().replaceAll("[-?]", "0");
									}
								}
							}
						}
					}
				}
			}
		}
		if (cleanDate != null) {
			Calendar calendar = Calendar.getInstance();
			SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy");
			String thisYear = dateFormat.format(calendar.getTime());
			try {
				if (Integer.parseInt(cleanDate) > Integer.parseInt(thisYear) + 1) cleanDate = null;
			} catch (NumberFormatException nfe) {
				cleanDate = null;
			}
		}
		return cleanDate;
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

	static String getCleanDetailValue(String value) {
		return value == null ? "" : value;
	}

	static String convertISBN10to13(String isbn10) {
		if (isbn10.length() != 10){
			return null;
		}
		String isbn = "978" + isbn10.substring(0, 9);
		//Calculate the 13 digit checksum
		int sumOfDigits = 0;
		for (int i = 0; i < 12; i++){
			int multiplier = 1;
			if (i % 2 == 1){
				multiplier = 3;
			}
			int curDigit = Integer.parseInt(Character.toString(isbn.charAt(i)));
			sumOfDigits += multiplier * curDigit;
		}
		int modValue = sumOfDigits % 10;
		int checksumDigit;
		if (modValue == 0){
			checksumDigit = 0;
		}else{
			checksumDigit = 10 - modValue;
		}
		return  isbn + Integer.toString(checksumDigit);
	}
}
