package com.turning_leaf_technologies.grouping;

import java.text.Normalizer;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

class AuthorNormalizer {
	private static Pattern authorExtract1 = Pattern.compile("^(.+?)\\spresents.*$");
	private static Pattern authorExtract2 = Pattern.compile("^(?:(?:a|an)\\s)?(.+?)\\spresentation.*$");
	private static Pattern distributedByRemoval = Pattern.compile("^distributed (?:in.*\\s)?by\\s(.+)$");
	private static Pattern initialsFix = Pattern.compile("(?<=[A-Z])\\.(?=(\\s|[A-Z]|$))");
	private static Pattern apostropheStrip = Pattern.compile("'s");
	private static Pattern specialCharacterWhitespace = Pattern.compile("'");
	private static Pattern specialCharacterStrip = Pattern.compile("[^\\p{L}\\d\\s]");
	private static Pattern consecutiveCharacterStrip = Pattern.compile("\\s{2,}");

	static String getNormalizedName(String rawName) {
		String groupingAuthor = normalizeDiacritics(rawName);
		groupingAuthor = removeParentheticalInformation(groupingAuthor);
		groupingAuthor = removeDates(groupingAuthor);
		groupingAuthor = initialsFix.matcher(groupingAuthor).replaceAll(" ");
		//For authors we just want to strip the brackets, not the text within them
		groupingAuthor = groupingAuthor.replace("[", "").replace("]", "");
		groupingAuthor = groupingAuthor.replace("<", "").replace(">", "");

		//Remove special characters that should be replaced with nothing
		groupingAuthor = apostropheStrip.matcher(groupingAuthor).replaceAll("");
		groupingAuthor = specialCharacterWhitespace.matcher(groupingAuthor).replaceAll("");
		groupingAuthor = specialCharacterStrip.matcher(groupingAuthor).replaceAll(" ").trim().toLowerCase();
		groupingAuthor = consecutiveCharacterStrip.matcher(groupingAuthor).replaceAll(" ");
		//extract common additional info (especially for movie studios)
		Matcher authorExtract1Matcher = authorExtract1.matcher(groupingAuthor);
		if (authorExtract1Matcher.find()){
			groupingAuthor = authorExtract1Matcher.group(1);
		}
		Matcher authorExtract2Matcher = authorExtract2.matcher(groupingAuthor);
		if (authorExtract2Matcher.find()){
			groupingAuthor = authorExtract2Matcher.group(1);
		}

		groupingAuthor = removeCommonPrefixesAndSuffixes(groupingAuthor);

		//Remove home entertainment
		Matcher distributedByRemovalMatcher = distributedByRemoval.matcher(groupingAuthor);
		if (distributedByRemovalMatcher.find()){
			groupingAuthor = distributedByRemovalMatcher.group(1);
		}
		//Remove md if the author ends with md
		if (groupingAuthor.endsWith(" md")){
			groupingAuthor = groupingAuthor.substring(0, groupingAuthor.length() - 3);
		}

		if (groupingAuthor.length() > 50){
			groupingAuthor = groupingAuthor.substring(0, 50);
		}
		groupingAuthor = groupingAuthor.trim();

		return groupingAuthor;
	}

	private static String normalizeDiacritics(String textToNormalize){
		return Normalizer.normalize(textToNormalize, Normalizer.Form.NFKC);
	}

	private static Pattern companyIdentifierPattern = Pattern.compile("(?i)[\\s,](pty ltd|llc|co|company|inc|ltd|lp)$");
	private static Pattern multipleNamePattern = Pattern.compile("(?i)[\\s,](and|or|with)\\s");
	/**
	 * Try to swap First Name and Last Name for titles that are entered as last name, first name
	 * Will return null if the name should not be reversed.
	 *
	 * @param authorName The Name to be converted
	 * @return String
	 */
	static String getDisplayName(String authorName) {
		//Don't bother with long names
		if (authorName.length() > 50){
			return null;
		}

		authorName = authorName.trim();
		authorName = removeParentheticalInformation(authorName);
		authorName = authorName.replace("[", "").replace("]", "");
		authorName = authorName.replace("<", "").replace(">", "");
		authorName = removeDates(authorName);
		authorName = removeTrailingPunctuation(authorName);
		authorName = removeCommonPrefixesAndSuffixes(authorName);

		int firstCommaIndex = authorName.indexOf(',');
		if (firstCommaIndex > 0){
			//Do not process if there is more than one comma (too complex)
			if (authorName.indexOf(',', firstCommaIndex + 1) > 0){
				return null;
			}

			//Do not process anything that looks like a corporate name
			if (companyIdentifierPattern.matcher(authorName).find()) {
				//Company name, ignore this
				return null;

			//Do not process anything that looks like there are multiple authors
			}else if (multipleNamePattern.matcher(authorName).find()){
				return null;

			}

			String lastName = authorName.substring(0, firstCommaIndex);
			String firstName = removeTrailingPunctuation(authorName.substring(firstCommaIndex + 1));

			return firstName.trim() + " " + lastName.trim();
		}

		return null;
	}



	private static Pattern trailingPunctuationPattern = Pattern.compile("[\\\\,./:-]+$");
	/**
	 * Remove commonPunctuation ,./\:
	 * @param stringToUpdate The string to update
	 * @return the string without trailing punctuation
	 */
	private static String removeTrailingPunctuation(String stringToUpdate) {
		return trailingPunctuationPattern.matcher(stringToUpdate).replaceAll("").trim();
	}

	private static Pattern parenRemoval = Pattern.compile("\\(.*?\\)");

	/**
	 * Remove information contained within parenthesis
	 *
 	 * @param authorName the author name to modify
	 * @return the author name without information in the parenthesis if any
	 */
	private static String removeParentheticalInformation(String authorName) {
		return parenRemoval.matcher(authorName).replaceAll("");
	}

	private static Pattern commonAuthorPrefixPattern = Pattern.compile("(?i)^(consultant|publisher & editor-in-chief|edited by|by the editors of|editor in chief|editor-in-chief|general editor|editors|editor|by|chosen by|translated by|prepared by|translated and edited by|completely rev by|pictures by|selected and adapted by|with a foreword by|with a new foreword by|introd by|introduction by|intro by|retold by|concept),?\\s");
	private static Pattern commonAuthorSuffixPattern = Pattern.compile("(?i),?\\s(presents|general editor|editor in chief|editor-in-chief|editors|editor|inc\\setc|etc|inc|co|corporation|llc|partners|company|home entertainment|musical group|et al|concept|consultant|\\.\\.\\.\\set al)\\.?$");
	private static String removeCommonPrefixesAndSuffixes(String authorName) {
		boolean changeMade = true;
		while (changeMade){
			changeMade = false;
			Matcher commonAuthorPrefixMatcher = commonAuthorPrefixPattern.matcher(authorName);
			if (commonAuthorPrefixMatcher.find()){
				authorName = commonAuthorPrefixMatcher.replaceAll("");
				changeMade = true;
			}
		}
		changeMade = true;
		while (changeMade){
			changeMade = false;
			Matcher commonAuthorSuffixMatcher = commonAuthorSuffixPattern.matcher(authorName);
			if (commonAuthorSuffixMatcher.find()){
				authorName = commonAuthorSuffixMatcher.replaceAll("");
				changeMade = true;
			}
		}
		return authorName;
	}
	private static Pattern datePattern = Pattern.compile("(,\\s)?(\\d{4}\\sor\\s)?(\\d{4}\\??-?|\\d{4}\\??-\\d{4}|-\\d{4}|\\d{4} (Jan|January|Feb|February|Mar|March|Apr|April|May|Jun|June|Jul|July|Aug|August|Sep|September|Oct|October|Nov|November|Dec|December)(\\s\\d+-?))\\??(\\sor\\s)?(\\d{4})?(\\.+)?$");
	/**
	 * Remove dates from the end of an author name. Currently handles the following formats.
	 *   Gordon, Maxwell, 1910-1983
	 *   David, Robert E. (Robert Edmund), 1922-
	 *   Pyke, Bernice (Bernice S.), 1879 or 1880-1964
	 *   Alder, Jamie, 1951-2010
	 *   Jacobs, David, 1978 December 11-
	 *   Ferrici, Petrus, -1478?
	 *   Burtini, 1901?-1969
	 *   Ḥamzah Mīrzā, Prince of the Qajars, 1819 or 1820-1879 or 1880
	 *   Paz Manzano, Carlos Roberto, 1964-....
	 *
	 * @param authorName The author name to strip dates from
	 * @return the name without dates.
	 */
	private static String removeDates(String authorName) {
		return datePattern.matcher(authorName).replaceAll("");
	}

	private static Pattern hasParentheticalPattern = Pattern.compile(",.*?\\([^,]*?\\)");
	private static Pattern parentheticalPattern = Pattern.compile(".*?\\((.*?)\\)", Pattern.CANON_EQ);
	/**
	 * Replace information outside of a parenthesis with the information in the parenthesis.
	 * I.e. Hogarth, D. G. (David George) becomes Hogarth, David George
	 *
	 * @param originalAuthorName  the name to be converted
	 * @return null if the name can't be converted, or the new name with parenthetical information converted
	 */
	static String getParentheticalName(String originalAuthorName) {
		//Don't bother with long names
		if (originalAuthorName.length() > 50){
			return null;
		}

		originalAuthorName = originalAuthorName.trim();
		originalAuthorName = removeCommonParentheticals(originalAuthorName);
		if (hasParentheticalPattern.matcher(originalAuthorName).find()){
			String[] nameParts = originalAuthorName.split(",");
			StringBuilder parentheticalName = new StringBuilder();
			int numReplacements = 0;
			for (int i = 0; i < nameParts.length; i++){
				Matcher parentheticalMatcher = parentheticalPattern.matcher(nameParts[i]);
				if (parentheticalMatcher.find()){
					nameParts[i] = parentheticalMatcher.group(1);
					numReplacements++;
				}
				if (i != 0){
					parentheticalName.append(", ");
				}
				parentheticalName.append(nameParts[i]);
			}
			if (numReplacements > 0) {
				return parentheticalName.toString().trim();
			}
		}
		return null;
	}

	private static Pattern commonParentheticalsPattern = Pattern.compile("(?i)\\((cor|edt|inc|eds\\.?|ed\\.?|editor|comedy troupe|comedy group|rap group|firm|musical group.*?|muiscal group|rock group.*?|vocal quartet|translated from.*?|monographs|[\\d-]+|u\\.s\\.|canada)\\)");
	private static String removeCommonParentheticals(String authorName){
		return commonParentheticalsPattern.matcher(authorName).replaceAll("");
	}
}
