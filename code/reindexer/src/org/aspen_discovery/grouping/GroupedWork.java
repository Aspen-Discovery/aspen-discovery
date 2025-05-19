package org.aspen_discovery.grouping;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.text.Normalizer;
import java.util.Locale;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Work Grouping with updated algorithm with the following changes from the original:
 * 1) Normalize diacritics using NFKC to all diacritics are handled consistently regardless of input
 * 2) Add trimming of "with illustrations" to title normalization
 * 3) Add trimming dates and parenthetical information to authors
 * 4) Group title and subtitle at the same time
 */
public class GroupedWork implements Cloneable {
	private final static Logger logger = LogManager.getLogger(GroupedWork.class);
	protected BaseIndexingLogEntry logEntry;

	//The id of the work within the database.
	String permanentId;

	String fullTitle = "";              //Up to 200 chars
	String originalAuthorName = "";
	protected String author = "";             //Up to 50  chars
	String groupingCategory = "";   //Up to 25  chars
	String language = "und"; //Default to undetermined

	private String uniqueIdentifier = null;
	private final RecordGroupingProcessor processor;

	private static final Pattern initialsFix = Pattern.compile("(?<=[A-Z])\\.(?=(\\s|[A-Z]|$))");
	private static final Pattern apostropheStrip = Pattern.compile("'s");
	private static final Pattern specialCharacterStrip = Pattern.compile("[^\\p{L}\\d\\s]");
	private static final Pattern consecutiveSpaceStrip = Pattern.compile("\\s{2,}");
	private static final Pattern bracketedCharacterStrip = Pattern.compile("\\[(.*?)]");

	GroupedWork(RecordGroupingProcessor processor) {
		this.processor = processor;
		this.logEntry = processor.getLogEntry();
	}

	String getPermanentId() {
		if (this.permanentId == null){
			StringBuilder permanentId;
			try {
				MessageDigest idGenerator = MessageDigest.getInstance("MD5");
				String fullTitle = getAuthoritativeTitle();
				if (fullTitle.isEmpty()){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(fullTitle.getBytes());
				}

				if (author.isEmpty()){
					idGenerator.update("--null--".getBytes());
				}else{
					String authoritativeAuthor = getAuthoritativeAuthor();
					idGenerator.update(authoritativeAuthor.getBytes());
				}
				if (groupingCategory.isEmpty()){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(groupingCategory.getBytes());
				}
				if (uniqueIdentifier != null){
					idGenerator.update(uniqueIdentifier.getBytes());
				}
				permanentId = new StringBuilder(new BigInteger(1, idGenerator.digest()).toString(16));
				while (permanentId.length() < 32){
					permanentId.insert(0, "0");
				}
				//Insert -'s for formatting
				this.permanentId = permanentId.substring(0, 8) + "-" + permanentId.substring(8, 12) + "-" + permanentId.substring(12, 16) + "-" + permanentId.substring(16, 20) + "-" + permanentId.substring(20) + "-" + language;
			} catch (NoSuchAlgorithmException e) {
				System.out.println("Error generating permanent id" + e);
			}
		}
		return this.permanentId;
	}

	public void overridePermanentId(String newId){
		this.permanentId = newId;
	}

	private String authoritativeTitle;
	String getAuthoritativeTitle() {
		if (authoritativeTitle == null) {
			authoritativeTitle = processor.getAuthoritativeTitle(fullTitle);
		}
		return authoritativeTitle;
	}

	private String authoritativeAuthor = null;
	String getAuthoritativeAuthor() {
		if (authoritativeAuthor == null) {
			authoritativeAuthor = processor.getAuthoritativeAuthor(author);
		}
		return authoritativeAuthor;
	}

	void makeUnique(String primaryIdentifier) {
		uniqueIdentifier = primaryIdentifier;
		this.permanentId = null;
	}

	private String normalizeAuthor(String author) {
		if (author.indexOf(';' )> 0){
			author = author.substring(0, author.indexOf(';'));
		}
		if (author.indexOf('/' )> 0){
			author = author.substring(0, author.indexOf('/'));
		}
		return AuthorNormalizer.getNormalizedName(author);
	}

	private static final Pattern editionRemovalPattern = Pattern.compile("(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|revised|\\d+\\S*)\\s+(edition|ed|ed\\.|update)");

	private String normalizeTitle(String fullTitle, int numNonFilingCharacters) {
		String groupingTitle;
		if (numNonFilingCharacters > 0 && numNonFilingCharacters < fullTitle.length()){
			groupingTitle = fullTitle.substring(numNonFilingCharacters);
		}else{
			groupingTitle = fullTitle;
		}

		groupingTitle = normalizeDiacritics(groupingTitle);
		groupingTitle = AspenStringUtils.makeValueSortable(groupingTitle);

		//Remove any bracketed parts of the title
		groupingTitle = removeBracketedPartOfTitle(groupingTitle);

		groupingTitle = cleanTitleCharacters(groupingTitle);

		//Remove some common subtitles that are meaningless (do again here in case they were part of the title).
		String titleBeforeRemovingSubtitles = groupingTitle.trim();
		groupingTitle = removeCommonSubtitles(groupingTitle);

		groupingTitle = normalizeNumericTitleText(groupingTitle);

		//Remove editions
		groupingTitle = removeEditionInformation(groupingTitle);

		int titleEnd = 200;
		if (titleEnd < groupingTitle.length()) {
			groupingTitle = groupingTitle.substring(0, titleEnd);
		}
		groupingTitle = groupingTitle.trim();
		if (groupingTitle.isEmpty() && !titleBeforeRemovingSubtitles.isEmpty()){
			logger.info("Title " + fullTitle + " was normalized to nothing, reverting to " + titleBeforeRemovingSubtitles);
			groupingTitle = titleBeforeRemovingSubtitles.trim();
		}
		return groupingTitle;
	}

	private static final Pattern dashPattern = Pattern.compile("&#8211");
	private static final Pattern ampersandPattern = Pattern.compile("&");
	private String cleanTitleCharacters(String groupingTitle) {
		//Fix abbreviations
		groupingTitle = initialsFix.matcher(groupingTitle).replaceAll(" ");
		//Replace & with and for better matching
		groupingTitle = dashPattern.matcher(groupingTitle).replaceAll("-");
		groupingTitle = ampersandPattern.matcher(groupingTitle).replaceAll("and");

		groupingTitle = apostropheStrip.matcher(groupingTitle).replaceAll("s");
		//replace apostrophes in contractions
		groupingTitle = groupingTitle.replaceAll("(\\w)'(\\w)", "$1$2");
		groupingTitle = specialCharacterStrip.matcher(groupingTitle).replaceAll(" ").toLowerCase();

		//Replace consecutive spaces
		groupingTitle = consecutiveSpaceStrip.matcher(groupingTitle).replaceAll(" ");
		return groupingTitle.trim();
	}

	private String removeEditionInformation(String groupingTitle) {
		groupingTitle = editionRemovalPattern.matcher(groupingTitle).replaceAll("");
		return groupingTitle;
	}

	private static final Pattern firstPattern = Pattern.compile("1st");
	private static final Pattern secondPattern = Pattern.compile("2nd");
	private static final Pattern thirdPattern = Pattern.compile("3rd");
	private static final Pattern fourthPattern = Pattern.compile("4th");
	private static final Pattern fifthPattern = Pattern.compile("5th");
	private static final Pattern sixthPattern = Pattern.compile("6th");
	private static final Pattern seventhPattern = Pattern.compile("7th");
	private static final Pattern eighthPattern = Pattern.compile("8th");
	private static final Pattern ninthPattern = Pattern.compile("9th");
	private static final Pattern tenthPattern = Pattern.compile("10th");
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

	private static final Pattern commonSubtitlesSimplePattern = Pattern.compile("\\b(a novel of .*|stories|an autobiography|a biography|a memoir in books|poems|the movie|large print|the graphic novel|graphic novel|magazine|audio cd|book club kit|with illustrations|book \\d+|the original classic edition|classic edition|a novel|large type edition|novel)$");
	private static final Pattern commonSubtitlesComplexPattern = Pattern.compile("\\b((a|una|an)\\s(.*)novel(a|la)?|a(.*)memoir|a(.*)mystery|a(.*)thriller|an? .* story|a .*\\s?book|[\\w\\s]+series book \\d+|[\\w\\s]+serie libro \\d+|the[\\w\\s]+chronicles book \\d+|[\\w\\s]+trilogy book \\d+|^novel|[\\w\\s]+series|.+\\sbook\\s\\d+)$");
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
		if (!groupingTitle.contains("[")) {
			return groupingTitle;
		}else{
			groupingTitle = groupingTitle.replace("[", "").replace("]","");
		}
		//Remove any bracketed parts of the title
//		String tmpTitle = bracketedCharacterStrip.matcher(groupingTitle).replaceAll("");
//		//Make sure we don't strip the entire title
//		if (!tmpTitle.isEmpty()){
//			//And make sure we don't have just special characters
//			tmpTitle = specialCharacterStrip.matcher(tmpTitle).replaceAll(" ").toLowerCase().trim();
//			if (!tmpTitle.isEmpty()) {
//				groupingTitle = tmpTitle;
//			//}else{
//			//	logger.warn("Just saved us from trimming " + groupingTitle + " to nothing");
//			}
//		}else{
//			//The entire title is in brackets, just remove the brackets
//			groupingTitle = groupingTitle.replace("[", "").replace("]","");
//		}
		return groupingTitle;
	}

	private static String normalizeDiacritics(String textToNormalize){
		return Normalizer.normalize(textToNormalize, Normalizer.Form.NFKC);
	}

	public GroupedWork clone() {
		try {
			return (GroupedWork)super.clone();
		} catch (CloneNotSupportedException e) {
			logEntry.incErrors("Could not clone grouped work", e);
			return null;
		}
	}

	public String getTitle() {
		return fullTitle;
	}

	public void setTitle(String title, int numNonFilingCharacters, String subtitle, String partInformation) {
		if (subtitle != null && !subtitle.isEmpty()){
			title = normalizePassedInSubtitle(title, subtitle);
		}else{
			//Check for a subtitle within the main title
			title = normalizeSubtitleWithinMainTitle(title);
		}
		title = normalizeTitle(title, numNonFilingCharacters);
		if (partInformation != null && !partInformation.isEmpty()){
			title += " " + normalizePartInformation(partInformation);
		}
		this.fullTitle = title.trim();
	}

	private String normalizePartInformation(String partInformation){
		String normalizedPartInformation = partInformation.toLowerCase();
		normalizedPartInformation = normalizeDiacritics(normalizedPartInformation);
		normalizedPartInformation = cleanTitleCharacters(normalizedPartInformation);
		normalizedPartInformation = normalizeNumericTitleText(normalizedPartInformation);
		return normalizedPartInformation;
	}

	private String normalizePassedInSubtitle(String title, String subtitle) {
		if (!title.endsWith(subtitle)){
			//Remove any complex subtitles since we know the beginning of the string
			String newSubtitle = cleanTitleCharacters(subtitle);
			if (!newSubtitle.isEmpty()) {
				newSubtitle = removeComplexSubtitles(newSubtitle);
				if (!newSubtitle.isEmpty()) {
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
			if (!newSubtitle.isEmpty()) {
				title =  mainTitle + " " + newSubtitle;
			//} else{
			//	logger.debug("Removed subtitle " + subtitleFromTitle);
			}
		}
		return title;
	}

	private static final Pattern authorStatementPattern = Pattern.compile("\\b(by\\s\\w+\\s\\w+)");
	private void removeTitleAuthorStatement() {
		if (!this.fullTitle.isEmpty() && !this.author.isEmpty()) {
			Matcher authorStatementMatcher = authorStatementPattern.matcher(this.fullTitle);
			if (authorStatementMatcher.find() && authorStatementMatcher.hitEnd()) {
				String[] authorNames = this.author.split("(,\\s|\\s)");
				String match = authorStatementMatcher.group();
				String newTitle = this.fullTitle;
				for (String authorName : authorNames) {
					if (match.contains(authorName)) {
						newTitle = authorStatementMatcher.replaceFirst("");
						break;
					}
				}
				this.fullTitle = newTitle.trim();
			}
		}
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		originalAuthorName = author;
		this.author = normalizeAuthor(author);
		removeTitleAuthorStatement();
	}

	private static final Pattern validCategories = Pattern.compile("^(book|music|movie|other|comic)$");

	public void setGroupingCategory(String groupingCategory) {
		groupingCategory = groupingCategory.toLowerCase();
		if (!validCategories.matcher(groupingCategory).matches()) {
			logEntry.incErrors("Invalid grouping category " + groupingCategory);
			this.groupingCategory = "other";
		}else {
			this.groupingCategory = groupingCategory;
		}
	}

	public String getGroupingCategory(){
		return groupingCategory;
	}

	public void setLanguage(String language){
		this.language = language.toLowerCase(Locale.ROOT);
	}

	public String getLanguage(){
		return this.language;
	}

}
