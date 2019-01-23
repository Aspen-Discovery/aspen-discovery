package org.vufind;

import org.apache.log4j.Logger;

import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Work Grouping based on original algorithm
 *
 * RecordGrouping
 * User: Mark Noble
 * Date: 11/15/13
 * Time: 9:02 AM
 */
public class GroupedWork1 extends GroupedWorkBase implements Cloneable {
	static Pattern authorExtract1 = Pattern.compile("^(.+?)\\spresents.*$");
	static Pattern authorExtract2 = Pattern.compile("^(?:(?:a|an)\\s)?(.+?)\\spresentation.*$");
	static Pattern distributedByRemoval = Pattern.compile("^distributed (?:in.*\\s)?by\\s(.+)$");
	static Pattern initialsFix = Pattern.compile("(?<=[A-Z])\\.(?=(\\s|[A-Z]|$))");
	static Pattern apostropheStrip = Pattern.compile("'s");
	static Pattern specialCharacterWhitespace = Pattern.compile("'");
	static Pattern specialCharacterStrip = Pattern.compile("[^\\p{L}\\d\\s]");
	static Pattern consecutiveCharacterStrip = Pattern.compile("\\s{2,}");
	static Pattern bracketedCharacterStrip = Pattern.compile("\\[(.*?)\\]");
	static Pattern commonAuthorSuffixPattern = Pattern.compile("^(.+?)\\s(?:general editor|editor|editor in chief|etc|inc|inc\\setc|co|corporation|llc|partners|company|home entertainment)$");
	static Pattern commonAuthorPrefixPattern = Pattern.compile("^(?:edited by|by the editors of|by|chosen by|translated by|prepared by|translated and edited by|completely rev by|pictures by|selected and adapted by|with a foreword by|with a new foreword by|introd by|introduction by|intro by|retold by)\\s(.+)$");

	static Logger logger = Logger.getLogger(GroupedWork1.class);
	private String normalizeAuthor(String author) {
		String groupingAuthor = initialsFix.matcher(author).replaceAll(" ");
		groupingAuthor = bracketedCharacterStrip.matcher(groupingAuthor).replaceAll("");

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
		Matcher editorMatcher1 = commonAuthorSuffixPattern.matcher(groupingAuthor);
		if (editorMatcher1.find()){
			groupingAuthor = editorMatcher1.group(1);
		}
		Matcher editorMatcher2 = commonAuthorPrefixPattern.matcher(groupingAuthor);
		if (editorMatcher2.find()){
			groupingAuthor = editorMatcher2.group(1);
		}
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

	static Pattern commonSubtitlesPattern = Pattern.compile("^(.*?)((a|una)\\s(.*)novel(a|la)?|a(.*)memoir|a(.*)mystery|a(.*)thriller|by\\s(.+)|a novel of .*|stories|an autobiography|a biography|a memoir in books|\\d+\\S*\\sed(ition)?|\\d+\\S*\\supdate|1st\\s+ed.*|an? .* story|a .*\\s?book|poems|the movie|[\\w\\s]+series book \\d+|[\\w\\s]+trilogy book \\d+|large print|graphic novel|magazine|audio cd|book club kit)$");
	static Pattern firstPattern = Pattern.compile("1st");
	static Pattern secondPattern = Pattern.compile("2nd");
	static Pattern thirdPattern = Pattern.compile("3rd");
	static Pattern fourthPattern = Pattern.compile("4th");
	static Pattern fifthPattern = Pattern.compile("5th");
	static Pattern sixthPattern = Pattern.compile("6th");
	static Pattern seventhPattern = Pattern.compile("7th");
	static Pattern eighthPattern = Pattern.compile("8th");
	static Pattern ninthPattern = Pattern.compile("9th");
	static Pattern tenthPattern = Pattern.compile("10th");
	private String normalizeSubtitle(String originalTitle) {
		if (originalTitle.length() > 0){
			String groupingSubtitle = originalTitle.replaceAll("&#8211;", "-");
			groupingSubtitle = groupingSubtitle.replaceAll("&", "and");

			//Remove any bracketed parts of the title
			groupingSubtitle = bracketedCharacterStrip.matcher(groupingSubtitle).replaceAll("");

			groupingSubtitle = apostropheStrip.matcher(groupingSubtitle).replaceAll("s");
			groupingSubtitle = specialCharacterStrip.matcher(groupingSubtitle).replaceAll(" ").toLowerCase().trim();
			groupingSubtitle = consecutiveCharacterStrip.matcher(groupingSubtitle).replaceAll(" ");

			//Remove some common subtitles that are meaningless
			Matcher commonSubtitleMatcher = commonSubtitlesPattern.matcher(groupingSubtitle);
			if (commonSubtitleMatcher.matches()){
				groupingSubtitle = commonSubtitleMatcher.group(1);
			}

			//Normalize numeric titles
			groupingSubtitle = firstPattern.matcher(groupingSubtitle).replaceAll("first");
			groupingSubtitle = secondPattern.matcher(groupingSubtitle).replaceAll("second");
			groupingSubtitle = thirdPattern.matcher(groupingSubtitle).replaceAll("third");
			groupingSubtitle = fourthPattern.matcher(groupingSubtitle).replaceAll("fourth");
			groupingSubtitle = fifthPattern.matcher(groupingSubtitle).replaceAll("fifth");
			groupingSubtitle = sixthPattern.matcher(groupingSubtitle).replaceAll("sixth");
			groupingSubtitle = seventhPattern.matcher(groupingSubtitle).replaceAll("seventh");
			groupingSubtitle = eighthPattern.matcher(groupingSubtitle).replaceAll("eighth");
			groupingSubtitle = ninthPattern.matcher(groupingSubtitle).replaceAll("ninth");
			groupingSubtitle = tenthPattern.matcher(groupingSubtitle).replaceAll("tenth");

			if (groupingSubtitle.length() > 175){
				groupingSubtitle = groupingSubtitle.substring(0, 175);
			}
			groupingSubtitle = groupingSubtitle.trim();
			return groupingSubtitle;
		}else{
			return originalTitle;
		}
	}

	static Pattern subtitleIndicator = Pattern.compile("[:;/=]") ;
	private String normalizeTitle(String fullTitle, int numNonFilingCharacters) {
		String groupingTitle;
		if (numNonFilingCharacters > 0 && numNonFilingCharacters < fullTitle.length()){
			groupingTitle = fullTitle.substring(numNonFilingCharacters);
		}else{
			groupingTitle = fullTitle;
		}

		groupingTitle = makeValueSortable(groupingTitle);

		//Remove any bracketed parts of the title
		String tmpTitle = bracketedCharacterStrip.matcher(groupingTitle).replaceAll("");
		//Make sure we don't strip the entire title
		if (tmpTitle.length() > 0){
			//And make sure we don't get just special characters
			tmpTitle = specialCharacterStrip.matcher(tmpTitle).replaceAll(" ").toLowerCase().trim();
			if (tmpTitle.length() > 0) {
				groupingTitle = tmpTitle;
			//}else{
			//	logger.warn("Just saved us from trimming " + groupingTitle + " to nothing");
			}
		}

		//If the title includes a : in it, take the first part as the title and the second as the subtitle
		Matcher subtitleMatcher = subtitleIndicator.matcher(groupingTitle);
		if (subtitleMatcher.find()){
			int startPos = subtitleMatcher.start();
			String subtitle = normalizeSubtitle(groupingTitle.substring(startPos + 1));
			groupingTitle = groupingTitle.substring(0, startPos);
			//Add the subtitle back
			if (subtitle != null && subtitle.length() > 0){
				groupingTitle += " " + subtitle;
			}
		}

		//Fix abbreviations
		groupingTitle = initialsFix.matcher(groupingTitle).replaceAll(" ");
		//Replace & with and for better matching
		groupingTitle = groupingTitle.replaceAll("&#8211;", "-");
		groupingTitle = groupingTitle.replaceAll("&", "and");

		//Remove some common subtitles that are meaningless (do again here in case they were part of the title).
		Matcher commonSubtitleMatcher = commonSubtitlesPattern.matcher(groupingTitle);
		if (commonSubtitleMatcher.matches() && commonSubtitleMatcher.group(1).length() != 0){
			groupingTitle = commonSubtitleMatcher.group(1);
		}

		groupingTitle = apostropheStrip.matcher(groupingTitle).replaceAll("s");
		groupingTitle = specialCharacterStrip.matcher(groupingTitle).replaceAll(" ").toLowerCase();
		//Replace consecutive spaces
		groupingTitle = consecutiveCharacterStrip.matcher(groupingTitle).replaceAll(" ");

		int titleEnd = 100;
		if (titleEnd < groupingTitle.length()) {
			groupingTitle = groupingTitle.substring(0, titleEnd);
		}
		groupingTitle = groupingTitle.trim();
		if (groupingTitle.length() == 0){
			if (!fullTitle.startsWith("By") && !fullTitle.startsWith("by")){
				logger.error("Title " + fullTitle + " was normalized to nothing");
			}
			groupingTitle = fullTitle;
		}
		return groupingTitle;
	}

	public GroupedWork1 clone() throws CloneNotSupportedException {

		try {
			return (GroupedWork1)super.clone();
		} catch (CloneNotSupportedException e) {
			e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
			return null;
		}
	}

	public String getTitle() {
		return fullTitle;
	}

	public void setTitle(String title, int numNonFilingCharacters, String subtitle) {
		title = normalizeTitle(title, numNonFilingCharacters);
		if (subtitle != null){
			subtitle = normalizeSubtitle(subtitle);
			if (subtitle.length() > 0){
				title += " " + subtitle;
			}
		}
		this.fullTitle = title.trim();
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		this.author = normalizeAuthor(author);
	}

	private static Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");
	private static String makeValueSortable(String curTitle) {
		if (curTitle == null) return "";
		String sortTitle = curTitle.toLowerCase();
		Matcher sortMatcher = sortTrimmingPattern.matcher(sortTitle);
		if (sortMatcher.matches()) {
			sortTitle = sortMatcher.group(1);
		}
		sortTitle = sortTitle.trim();
		return sortTitle;
	}

	public void overridePermanentId(String groupedWorkPermanentId) {
		this.permanentId = groupedWorkPermanentId;
	}

	private static Pattern validCategories = Pattern.compile("^(book|music|movie)$");
	public void setGroupingCategory(String groupingCategory) {
		groupingCategory = groupingCategory.toLowerCase();
		if (!validCategories.matcher(groupingCategory).matches()) {
			logger.error("Invalid grouping category " + groupingCategory);
		}else {
			this.groupingCategory = groupingCategory;
		}
	}

	public String getGroupingCategory(){
		return groupingCategory;
	}
}
