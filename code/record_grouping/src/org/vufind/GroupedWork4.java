package org.vufind;

import org.apache.log4j.Logger;

import java.text.Normalizer;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Work Grouping with updated algorithm with the following changes from the original:
 * 1) Normalize diacritics using NFKC to all diacritics are handled consistently regardless of input
 * 2) Add trimming of "with illustrations" to title normalization
 * 3) Add trimming dates and parenthetical information to authors
 * 4) Group title and sub title at the same time
 *
 * RecordGrouping
 * User: Mark Noble
 * Date: 11/15/13
 * Time: 9:02 AM
 */
class GroupedWork4 extends GroupedWorkBase implements Cloneable {


	private static Pattern initialsFix = Pattern.compile("(?<=[A-Z])\\.(?=(\\s|[A-Z]|$))");
	private static Pattern apostropheStrip = Pattern.compile("'s");
	private static Pattern specialCharacterStrip = Pattern.compile("[^\\p{L}\\d\\s]");
	private static Pattern consecutiveSpaceStrip = Pattern.compile("\\s{2,}");
	private static Pattern bracketedCharacterStrip = Pattern.compile("\\[(.*?)\\]");

	static Logger logger = Logger.getLogger(GroupedWork4.class);

	private String normalizeAuthor(String author) {
		return AuthorNormalizer.getNormalizedName(author);
	}

	private static Pattern editionRemovalPattern = Pattern.compile("(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|revised|\\d+\\S*)\\s+(edition|ed|ed\\.|update)");

	private String normalizeTitle(String fullTitle, int numNonFilingCharacters) {
		String groupingTitle;
		if (numNonFilingCharacters > 0 && numNonFilingCharacters < fullTitle.length()){
			groupingTitle = fullTitle.substring(numNonFilingCharacters);
		}else{
			groupingTitle = fullTitle;
		}

		groupingTitle = normalizeDiacritics(groupingTitle);
		groupingTitle = makeValueSortable(groupingTitle);
		groupingTitle = removeBracketedPartOfTitle(groupingTitle);

		//Remove any bracketed parts of the title
		groupingTitle = cleanTitleCharacters(groupingTitle);

		//Remove some common subtitles that are meaningless (do again here in case they were part of the title).
		String titleBeforeRemovingSubtitles = groupingTitle.trim();
		groupingTitle = removeCommonSubtitles(groupingTitle);

		groupingTitle = normalizeNumericTitleText(groupingTitle);

		//Remove editions
		groupingTitle = removeEditionInformation(groupingTitle);

		int titleEnd = 100;
		if (titleEnd < groupingTitle.length()) {
			groupingTitle = groupingTitle.substring(0, titleEnd);
		}
		groupingTitle = groupingTitle.trim();
		if (groupingTitle.length() == 0 && titleBeforeRemovingSubtitles.length() > 0){
			logger.info("Title " + fullTitle + " was normalized to nothing, reverting to " + titleBeforeRemovingSubtitles);
			groupingTitle = titleBeforeRemovingSubtitles.trim();
		}
		return groupingTitle;
	}

	private static Pattern dashPattern = Pattern.compile("&#8211");
	private static Pattern ampersandPattern = Pattern.compile("&");
	private String cleanTitleCharacters(String groupingTitle) {
		//Fix abbreviations
		groupingTitle = initialsFix.matcher(groupingTitle).replaceAll(" ");
		//Replace & with and for better matching
		groupingTitle = dashPattern.matcher(groupingTitle).replaceAll("-");
		groupingTitle = ampersandPattern.matcher(groupingTitle).replaceAll("and");

		groupingTitle = apostropheStrip.matcher(groupingTitle).replaceAll("s");
		groupingTitle = specialCharacterStrip.matcher(groupingTitle).replaceAll(" ").toLowerCase();

		//Replace consecutive spaces
		groupingTitle = consecutiveSpaceStrip.matcher(groupingTitle).replaceAll(" ");
		return groupingTitle;
	}

	private String removeEditionInformation(String groupingTitle) {
		groupingTitle = editionRemovalPattern.matcher(groupingTitle).replaceAll("");
		return groupingTitle;
	}

	private static Pattern firstPattern = Pattern.compile("1st");
	private static Pattern secondPattern = Pattern.compile("2nd");
	private static Pattern thirdPattern = Pattern.compile("3rd");
	private static Pattern fourthPattern = Pattern.compile("4th");
	private static Pattern fifthPattern = Pattern.compile("5th");
	private static Pattern sixthPattern = Pattern.compile("6th");
	private static Pattern seventhPattern = Pattern.compile("7th");
	private static Pattern eighthPattern = Pattern.compile("8th");
	private static Pattern ninthPattern = Pattern.compile("9th");
	private static Pattern tenthPattern = Pattern.compile("10th");
	private String normalizeNumericTitleText(String groupingTitle) {
		//Normalize numeric titles
		groupingTitle = firstPattern.matcher(groupingTitle).replaceAll("first");
		groupingTitle = secondPattern.matcher(groupingTitle).replaceAll("second");
		groupingTitle = thirdPattern.matcher(groupingTitle).replaceAll("third");
		groupingTitle = fourthPattern.matcher(groupingTitle).replaceAll("fourth");
		groupingTitle = fifthPattern.matcher(groupingTitle).replaceAll("fifth");
		groupingTitle = sixthPattern.matcher(groupingTitle).replaceAll("sixth");
		groupingTitle = seventhPattern.matcher(groupingTitle).replaceAll("seventh");
		groupingTitle = eighthPattern.matcher(groupingTitle).replaceAll("eighth");
		groupingTitle = ninthPattern.matcher(groupingTitle).replaceAll("ninth");
		groupingTitle = tenthPattern.matcher(groupingTitle).replaceAll("tenth");
		return groupingTitle;
	}

	private static Pattern commonSubtitlesSimplePattern = Pattern.compile("(by\\s\\w+\\s\\w+|a novel of .*|stories|an autobiography|a biography|a memoir in books|poems|the movie|large print|graphic novel|magazine|audio cd|book club kit|with illustrations|book \\d+|the original classic edition|classic edition|a novel)$");
	private static Pattern commonSubtitlesComplexPattern = Pattern.compile("((a|una)\\s(.*)novel(a|la)?|a(.*)memoir|a(.*)mystery|a(.*)thriller|by\\s\\w+\\s\\w+|an? .* story|a .*\\s?book|[\\w\\s]+series book \\d+|the[\\w\\s]+chronicles book \\d+|[\\w\\s]+trilogy book \\d+)$");
	private String removeCommonSubtitles(String groupingTitle) {
		boolean changeMade = true;
		while (changeMade){
			changeMade = false;
			Matcher commonSubtitleMatcher = commonSubtitlesSimplePattern.matcher(groupingTitle);
			if (commonSubtitleMatcher.find()) {
				groupingTitle = commonSubtitleMatcher.replaceAll("").trim();
				changeMade = true;
			}
		}
		return groupingTitle;
	}

	private String removeBracketedPartOfTitle(String groupingTitle) {
		//Remove any bracketed parts of the title
		String tmpTitle = bracketedCharacterStrip.matcher(groupingTitle).replaceAll("");
		//Make sure we don't strip the entire title
		if (tmpTitle.length() > 0){
			//And make sure we don't have just special characters
			tmpTitle = specialCharacterStrip.matcher(tmpTitle).replaceAll(" ").toLowerCase().trim();
			if (tmpTitle.length() > 0) {
				groupingTitle = tmpTitle;
			//}else{
			//	logger.warn("Just saved us from trimming " + groupingTitle + " to nothing");
			}
		}else{
			//The entire title is in brackets, just remove the brackets
			groupingTitle = groupingTitle.replace("[", "").replace("]","");
		}
		return groupingTitle;
	}

	private static String normalizeDiacritics(String textToNormalize){
		return Normalizer.normalize(textToNormalize, Normalizer.Form.NFKC);
	}

	public GroupedWorkBase clone() throws CloneNotSupportedException {

		try {
			return (GroupedWorkBase)super.clone();
		} catch (CloneNotSupportedException e) {
			e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
			return null;
		}
	}

	@Override
	public String getTitle() {
		return fullTitle;
	}

	@Override
	public void setTitle(String title, int numNonFilingCharacters, String subtitle) {
		//this.fullTitle = title;
		//if (subtitle != null) title += " " + subtitle;
		if (subtitle != null && subtitle.length() > 0){
			title = normalizePassedInSubtitle(title, subtitle);
		}else{
			//Check for a subtitle within the main title
			title = normalizeSubtitleWithinMainTitle(title);
		}
		title = normalizeTitle(title, numNonFilingCharacters);
		this.fullTitle = title.trim();
	}

	private String normalizePassedInSubtitle(String title, String subtitle) {
		if (!title.endsWith(subtitle)){
			//Remove any complex subtitles since we know the beginning of the string
			String newSubtitle = cleanTitleCharacters(subtitle);
			if (newSubtitle.length() > 0) {
				newSubtitle = removeComplexSubtitles(newSubtitle);
				if (newSubtitle.length() > 0) {
					title += " " + newSubtitle;
				//} else {
				//	logger.debug("Removed subtitle " + subtitle);
				}
			}
		}else{
			logger.debug("Not appending subtitle because it was already part of the title.");
		}
		return title;
	}

	private String removeComplexSubtitles(String newSubtitle) {
		newSubtitle = commonSubtitlesComplexPattern.matcher(newSubtitle).replaceAll("");
		return newSubtitle;
	}

	private String normalizeSubtitleWithinMainTitle(String title) {
		if (title.endsWith(":")){
			title = title.substring(0, title.length() -1);
		}
		int colonIndex = title.lastIndexOf(':');
		if (colonIndex > 0){
			String subtitleFromTitle = title.substring(colonIndex + 1).trim();
			String newSubtitle = cleanTitleCharacters(subtitleFromTitle);
			String mainTitle = title.substring(0, colonIndex).trim();
			newSubtitle = removeComplexSubtitles(newSubtitle);
			if (newSubtitle.length() > 0) {
				title =  mainTitle + " " + newSubtitle;
			//} else{
			//	logger.debug("Removed subtitle " + subtitleFromTitle);
			}
		}
		return title;
	}

	@Override
	public String getAuthor() {
		return author;
	}

	@Override
	public void setAuthor(String author) {
		originalAuthorName = author;
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

	@Override
	public void overridePermanentId(String groupedWorkPermanentId) {
		this.permanentId = groupedWorkPermanentId;
	}

	private static Pattern validCategories = Pattern.compile("^(book|music|movie)$");
	@Override
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
