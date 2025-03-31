package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;

import java.util.*;
import java.util.regex.Pattern;

public abstract class AbstractGroupedWorkSolr {
	protected String id;

	protected HashMap<String, RecordInfo> relatedRecords = new HashMap<>();

	protected String acceleratedReaderInterestLevel;
	protected String acceleratedReaderReadingLevel;
	protected String acceleratedReaderPointValue;
	protected HashSet<String> alternateIds = new HashSet<>();
	protected String authAuthor;
	protected HashMap<String, Long> primaryAuthors = new HashMap<>();
	protected HashSet<String> authorAdditional = new HashSet<>();
	protected String authorDisplay;
	protected String authorFormat;
	protected HashSet<String> author2 = new HashSet<>();
	protected HashSet<String> authAuthor2 = new HashSet<>();
	protected HashSet<String> author2Role = new HashSet<>();
	protected HashSet<String> awards = new HashSet<>();
	protected HashSet<String> barcodes = new HashSet<>();
	protected final HashSet<String> bisacSubjects = new HashSet<>();
	protected String callNumberA;
	protected String callNumberFirst;
	protected String callNumberSubject;
	protected HashSet<String> contents = new HashSet<>();
	protected HashSet<String> dateSpans = new HashSet<>();
	protected HashSet<String> description = new HashSet<>();
	protected String displayDescription = "";
	protected String displayDescriptionFormat = "";
	protected String ilsDescription = "";
	protected String ilsDescriptionFormat = "";
	protected String displayTitle;
	protected Long earliestPublicationDate = null;
	protected HashSet<String> editions = new HashSet<>();
	protected HashSet<String> eras = new HashSet<>();
	protected HashSet<String> fullTitles = new HashSet<>();
	protected HashSet<String> genres = new HashSet<>();
	protected HashSet<String> genreFacets = new HashSet<>();
	protected HashSet<String> geographic = new HashSet<>();
	protected HashSet<String> geographicFacets = new HashSet<>();
	protected HashSet<String> personalNameSubjects = new HashSet<>();
	protected HashSet<String> corporateNameSubjects = new HashSet<>();
	protected String groupingCategory;
	protected String primaryIsbn;
	protected boolean primaryIsbnIsBook;
	protected Long primaryIsbnUsageCount;
	protected HashMap<String, Long> isbns = new HashMap<>();
	protected HashSet<String> issns = new HashSet<>();
	protected HashSet<String> keywords = new HashSet<>();
	protected HashSet<String> languages = new HashSet<>();
	protected HashSet<String> translations = new HashSet<>();
	protected Long languageBoost = 1L;
	protected Long languageBoostSpanish = 1L;
	protected HashSet<String> lccns = new HashSet<>();
	protected HashSet<String> lcSubjects = new HashSet<>();
	protected String lexileScore = "-1";
	protected String lexileCode = "";
	protected String fountasPinnell = "";
	protected HashMap<String, Integer> literaryFormFull = new HashMap<>();
	protected HashMap<String, Integer> literaryForm = new HashMap<>();
	protected HashSet<String> mpaaRatings = new HashSet<>();
	protected Long numHoldings = 0L;
	protected HashSet<String> oclcs = new HashSet<>();
	protected HashSet<String> physicals = new HashSet<>();
	protected double popularity;
	protected long totalHolds;

	protected HashSet<String> publishers = new HashSet<>();
	protected HashSet<String> publicationDates = new HashSet<>();
	protected HashSet<String> placesOfPublication = new HashSet<>();
	protected float rating = -1f;
	protected HashMap<String, String> series = new HashMap<>();
	protected HashMap<String, String> series2 = new HashMap<>();
	protected HashMap<String, String> seriesWithVolume = new HashMap<>();
	protected String subTitle;
	protected HashSet<String> targetAudienceFull = new HashSet<>();
	protected TreeSet<String> targetAudience = new TreeSet<>();
	protected String title;
	protected HashSet<String> titleAlt = new HashSet<>();
	protected HashSet<String> titleOld = new HashSet<>();
	protected HashSet<String> titleNew = new HashSet<>();
	protected String titleSort;
	protected String titleFormat = "";
	protected HashSet<String> topics = new HashSet<>();
	protected HashSet<String> topicFacets = new HashSet<>();
	protected HashSet<String> subjects = new HashSet<>();
	protected HashMap<String, Long> upcs = new HashMap<>();

	protected final Logger logger;
	protected final GroupedWorkIndexer groupedWorkIndexer;
	protected HashSet<String> systemLists = new HashSet<>();
	protected final HashSet<Long> userReadingHistoryLink = new HashSet<>();
	protected final HashSet<Long> userRatingLink = new HashSet<>();
	protected final HashSet<Long> userNotInterestedLink = new HashSet<>();

	protected final HashSet<String> parentRecords = new HashSet<>();

	protected final HashMap<Integer, Set<String>> customFacetValues = new HashMap<>();

	//Store a list of scopes for the work
	protected HashMap<String, ArrayList<ScopingInfo>> relatedScopes = new HashMap<>();

	protected boolean debugEnabled = false;
	protected long debugId = -1L;
	protected ArrayList<String> debugMessages = new ArrayList<>();

	public AbstractGroupedWorkSolr(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		this.logger = logger;
		this.groupedWorkIndexer = groupedWorkIndexer;
	}

	public AbstractGroupedWorkSolr clone() throws CloneNotSupportedException {
		return (AbstractGroupedWorkSolr) super.clone();
	}

	protected void cloneCollectionData(AbstractGroupedWorkSolr clonedWork){
		//Clone collections as well
		// noinspection unchecked
		clonedWork.relatedRecords = (HashMap<String, RecordInfo>) relatedRecords.clone();
		// noinspection unchecked
		clonedWork.alternateIds = (HashSet<String>) alternateIds.clone();
		// noinspection unchecked
		clonedWork.primaryAuthors = (HashMap<String, Long>) primaryAuthors.clone();
		// noinspection unchecked
		clonedWork.authorAdditional = (HashSet<String>) authorAdditional.clone();
		// noinspection unchecked
		clonedWork.author2 = (HashSet<String>) author2.clone();
		// noinspection unchecked
		clonedWork.authAuthor2 = (HashSet<String>) authAuthor2.clone();
		// noinspection unchecked
		clonedWork.author2Role = (HashSet<String>) author2Role.clone();
		// noinspection unchecked
		clonedWork.awards = (HashSet<String>) awards.clone();
		// noinspection unchecked
		clonedWork.barcodes = (HashSet<String>) barcodes.clone();
		// noinspection unchecked
		clonedWork.contents = (HashSet<String>) contents.clone();
		// noinspection unchecked
		clonedWork.dateSpans = (HashSet<String>) dateSpans.clone();
		// noinspection unchecked
		clonedWork.description = (HashSet<String>) description.clone();
		// noinspection unchecked
		clonedWork.editions = (HashSet<String>) editions.clone();
		// noinspection unchecked
		clonedWork.eras = (HashSet<String>) eras.clone();
		// noinspection unchecked
		clonedWork.fullTitles = (HashSet<String>) fullTitles.clone();
		// noinspection unchecked
		clonedWork.genres = (HashSet<String>) genres.clone();
		// noinspection unchecked
		clonedWork.genreFacets = (HashSet<String>) genreFacets.clone();
		// noinspection unchecked
		clonedWork.geographic = (HashSet<String>) geographic.clone();
		// noinspection unchecked
		clonedWork.geographicFacets = (HashSet<String>) geographicFacets.clone();
		// noinspection unchecked
		clonedWork.personalNameSubjects = (HashSet<String>) personalNameSubjects.clone();
		// noinspection unchecked
		clonedWork.corporateNameSubjects = (HashSet<String>) corporateNameSubjects.clone();
		// noinspection unchecked
		clonedWork.isbns = (HashMap<String, Long>) isbns.clone();
		// noinspection unchecked
		clonedWork.issns = (HashSet<String>) issns.clone();
		// noinspection unchecked
		clonedWork.keywords = (HashSet<String>) keywords.clone();
		// noinspection unchecked
		clonedWork.languages = (HashSet<String>) languages.clone();
		// noinspection unchecked
		clonedWork.translations = (HashSet<String>) translations.clone();
		// noinspection unchecked
		clonedWork.lccns = (HashSet<String>) lccns.clone();
		// noinspection unchecked
		clonedWork.lcSubjects = (HashSet<String>) lcSubjects.clone();
		// noinspection unchecked
		clonedWork.literaryFormFull = (HashMap<String, Integer>) literaryFormFull.clone();
		// noinspection unchecked
		clonedWork.literaryForm = (HashMap<String, Integer>) literaryForm.clone();
		// noinspection unchecked
		clonedWork.mpaaRatings = (HashSet<String>) mpaaRatings.clone();
		// noinspection unchecked
		clonedWork.oclcs = (HashSet<String>) oclcs.clone();
		// noinspection unchecked
		clonedWork.physicals = (HashSet<String>) physicals.clone();
		// noinspection unchecked
		clonedWork.publishers = (HashSet<String>) publishers.clone();
		// noinspection unchecked
		clonedWork.publicationDates = (HashSet<String>) publicationDates.clone();
		// noinspection unchecked
		clonedWork.placesOfPublication = (HashSet<String>)placesOfPublication.clone();
		// noinspection unchecked
		clonedWork.series = (HashMap<String, String>) series.clone();
		// noinspection unchecked
		clonedWork.series2 = (HashMap<String, String>) series2.clone();
		// noinspection unchecked
		clonedWork.seriesWithVolume = (HashMap<String, String>) seriesWithVolume.clone();
		// noinspection unchecked
		clonedWork.targetAudienceFull = (HashSet<String>) targetAudienceFull.clone();
		// noinspection unchecked
		clonedWork.targetAudience = (TreeSet<String>) targetAudience.clone();
		// noinspection unchecked
		clonedWork.titleAlt = (HashSet<String>) titleAlt.clone();
		// noinspection unchecked
		clonedWork.titleOld = (HashSet<String>) titleOld.clone();
		// noinspection unchecked
		clonedWork.titleNew = (HashSet<String>) titleNew.clone();
		// noinspection unchecked
		clonedWork.topics = (HashSet<String>) topics.clone();
		// noinspection unchecked
		clonedWork.topicFacets = (HashSet<String>) topicFacets.clone();
		// noinspection unchecked
		clonedWork.subjects = (HashSet<String>) subjects.clone();
		// noinspection unchecked
		clonedWork.upcs = (HashMap<String, Long>) upcs.clone();
		// noinspection unchecked
		clonedWork.systemLists = (HashSet<String>) systemLists.clone();
		// noinspection unchecked
		clonedWork.relatedScopes = (HashMap<String, ArrayList<ScopingInfo>>) relatedScopes.clone();
	}

	abstract SolrInputDocument getSolrDocument(BaseIndexingLogEntry logEntry);

	public void addScopingInfo(String scopeName, ScopingInfo scopingInfo){
		ArrayList<ScopingInfo> scopingInfoForScope = relatedScopes.computeIfAbsent(scopeName, k -> new ArrayList<>());
		scopingInfoForScope.add(scopingInfo);
	}

	protected String getPrimaryUpc() {
		String primaryUpc = null;
		long maxUsage = 0;
		for (String upc : upcs.keySet()) {
			long usage = upcs.get(upc);
			if (primaryUpc == null || usage > maxUsage) {
				primaryUpc = upc;
				maxUsage = usage;
			}
		}
		return primaryUpc;
	}

	protected Long getTotalFormatBoost() {
		long formatBoost = 0;
		for (RecordInfo curRecord : relatedRecords.values()) {
			formatBoost += curRecord.getFormatBoost();
		}
		if (formatBoost == 0) {
			formatBoost = 1;
		}
		return formatBoost;
	}

	protected HashSet<String> getAllEContentSources() {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()) {
			values.addAll(curRecord.getAllEContentSources());
		}
		return values;
	}

	protected HashSet<String> getAllCallNumbers() {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()) {
			values.addAll(curRecord.getAllCallNumbers());
		}
		return values;
	}

	protected Date getDateAdded() {
		Date earliestDate = null;
		for (RecordInfo curRecord : relatedRecords.values()) {
			for (ItemInfo curItem : curRecord.getRelatedItems()) {
				if (curItem.getDateAdded() != null) {
					if (earliestDate == null || curItem.getDateAdded().before(earliestDate)) {
						earliestDate = curItem.getDateAdded();
					}
				}
			}
		}
		return earliestDate;
	}

	abstract protected void addScopedFieldsToDocument(SolrInputDocument doc, BaseIndexingLogEntry logEntry);

	protected void checkInconsistentLiteraryForms() {
		if (literaryForm.size() > 1) {
			//We got unknown and something else, remove the unknown
			literaryForm.remove("Unknown");
			if (literaryForm.size() >= 2) {
				//Hmm, we got both fiction and non-fiction
				Integer numFictionIndicators = literaryForm.get("Fiction");
				if (numFictionIndicators == null) {
					numFictionIndicators = 0;
				}
				Integer numNonFictionIndicators = literaryForm.get("Non Fiction");
				if (numNonFictionIndicators == null) {
					numNonFictionIndicators = 0;
				}
				if (numFictionIndicators.equals(numNonFictionIndicators)) {
					//Houston we have a problem.
					//logger.warn("Found inconsistent literary forms for grouped work " + id + " both fiction and non fiction had the same amount of usage.  Defaulting to neither.");
					literaryForm.clear();
					literaryForm.put("Unknown", 1);
					if (this.debugEnabled) {this.addDebugMessage("Fiction and non fiction score are the same - literary form is unknown ", 2);}
				} else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0) {
					logger.debug("Popularity dictates that Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Non Fiction");
					if (this.debugEnabled) {this.addDebugMessage("Fiction has the highest literary form score", 2);}
				} else if (numFictionIndicators.compareTo(numNonFictionIndicators) < 0) {
					logger.debug("Popularity dictates that Non Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Fiction");
					if (this.debugEnabled) {this.addDebugMessage("Non fiction has the highest literary form score", 2);}
				}
			}
		}
	}

	protected void checkInconsistentLiteraryFormsFull() {
		if (literaryFormFull.size() > 1) {
			//We got unknown and something else, remove the unknown
			literaryFormFull.remove("Unknown");
			if (literaryFormFull.size() >= 2) {
				//Hmm, we got multiple forms.  Check to see if there are inconsistent forms
				// i.e. Fiction and Non-Fiction are incompatible, but Novels and Fiction could be mixed
				int maxUsage = 0;
				HashSet<String> highestUsageLiteraryForms = new HashSet<>();
				for (String literaryForm : literaryFormFull.keySet()) {
					int curUsage = literaryFormFull.get(literaryForm);
					if (curUsage > maxUsage) {
						highestUsageLiteraryForms.clear();
						highestUsageLiteraryForms.add(literaryForm);
						maxUsage = curUsage;
					} else if (curUsage == maxUsage) {
						highestUsageLiteraryForms.add(literaryForm);
					}
				}
				if (highestUsageLiteraryForms.size() > 1) {
					//Check to see if the highest usage literary forms are inconsistent
					if (hasInconsistentLiteraryForms(highestUsageLiteraryForms)) {
						//Ugh, we have inconsistent literary forms and can't make an educated guess as to which is correct.
						if (this.debugEnabled) {this.addDebugMessage("Literary forms (" + literaryFormFull  + ") don't match - full form is Unknown", 2);}
						literaryFormFull.clear();
						literaryFormFull.put("Unknown", 1);
					}
				} else {
					removeInconsistentFullLiteraryForms(literaryFormFull, highestUsageLiteraryForms);
				}
			}
		}
	}

	private void removeInconsistentFullLiteraryForms(HashMap<String, Integer> literaryFormFull, HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(highestUsageLiteraryForms.iterator().next());
		boolean changeMade = true;
		while (changeMade) {
			changeMade = false;
			for (String curLiteraryForm : literaryFormFull.keySet()) {
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)) {
					logger.debug(curLiteraryForm + " got voted off the island for grouped work " + id + " because it was inconsistent with other full literary forms.");
					if (this.debugEnabled) {this.addDebugMessage(curLiteraryForm + " got voted off the island for grouped work " + id + " because it was inconsistent with other full literary forms.", 2);}
					literaryFormFull.remove(curLiteraryForm);
					changeMade = true;
					break;
				}
			}
		}
	}

	private static final ArrayList<String> nonFictionFullLiteraryForms = new ArrayList<>();

	static {
		nonFictionFullLiteraryForms.add("Non Fiction");
		nonFictionFullLiteraryForms.add("Essays");
		nonFictionFullLiteraryForms.add("Letters");
		nonFictionFullLiteraryForms.add("Speeches");
	}

	private boolean hasInconsistentLiteraryForms(HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = false;
		int numFormsChecked = 0;
		for (String curLiteraryForm : highestUsageLiteraryForms) {
			if (numFormsChecked == 0) {
				firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(curLiteraryForm);
			} else {
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)) {
					return true;
				}
			}
			numFormsChecked++;
		}
		return false;
	}

	protected void checkDefaultValue(Map<String, Integer> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.containsKey(defaultValue) && valuesCollection.size() > 1) {
			valuesCollection.remove(defaultValue);
		} else if (valuesCollection.isEmpty()) {
			valuesCollection.put(defaultValue, 1);
		}
	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id.toLowerCase(Locale.ROOT);
	}

	private final static Pattern removeBracketsPattern = Pattern.compile("\\[.*?]");
	private final static Pattern commonSubtitlePattern = Pattern.compile("(?i)([(]?(?:\\s?a\\s?|\\s?the\\s?)?graphic novel|audio cd|book club kit|large print[)]?)$");
	private final static Pattern punctuationPattern = Pattern.compile("[.\\\\/()\\[\\]:;]");

	void setTitle(String shortTitle, String subTitle, String displayTitle, String sortableTitle, String recordFormat, String formatCategory) {
		this.setTitle(shortTitle, subTitle, displayTitle, sortableTitle, formatCategory, false);
	}

	void setTitle(String shortTitle, String subTitle, String displayTitle, String sortableTitle, String formatCategory, boolean isDisplayInfo) {
		if (shortTitle != null) {
			shortTitle = AspenStringUtils.trimTrailingPunctuation(shortTitle);

			//Figure out if we want to use this title or if the one we have is better.
			boolean updateTitle = false;
			if (this.title == null) {
				updateTitle = true;
			} else {
				//Only overwrite if we get a better format
				if (formatCategory.equals("Books")) {
					//We have a book, update if we didn't have a book before
					if (!formatCategory.equals(titleFormat)) {
						updateTitle = true;
						//Or update if we had a book before and this title is longer
					} else if (shortTitle.length() > this.title.length()) {
						updateTitle = true;
					}
				} else if (formatCategory.equals("eBook")) {
					//Update if the format we had before is not a book
					if (!titleFormat.equals("Books")) {
						//And the new format was not an eBook or the new title is longer than what we had before
						if (!formatCategory.equals(titleFormat)) {
							updateTitle = true;
							//or update if we had a book before and this title is longer
						} else if (shortTitle.length() > this.title.length()) {
							updateTitle = true;
						}
					}
				} else if (!titleFormat.equals("Books") && !titleFormat.equals("eBook")) {
					//If we don't have a Book or an eBook then we can update the title if we get a longer title
					if (shortTitle.length() > this.title.length()) {
						updateTitle = true;
					}
				}
			}

			if (updateTitle || isDisplayInfo) {
				//Strip out anything in brackets unless that would cause us to show nothing
//				String tmpTitle = removeBracketsPattern.matcher(shortTitle).replaceAll("").trim();
//				if (!tmpTitle.isEmpty()) {
//					shortTitle = tmpTitle;
//				}
				//Do not remove common subtitle from display info
				//if (!isDisplayInfo) {
					String tmpTitle = commonSubtitlePattern.matcher(shortTitle).replaceAll("").trim();
					if (!tmpTitle.isEmpty()) {
						shortTitle = tmpTitle;
					}
				//}
				this.title = shortTitle;
				this.titleFormat = formatCategory;
				//Strip out anything in brackets unless that would cause us to show nothing
//				tmpTitle = removeBracketsPattern.matcher(sortableTitle).replaceAll("").trim();
//				if (!tmpTitle.isEmpty()) {
//					sortableTitle = tmpTitle;
//				}
				//Remove common formats
				tmpTitle = commonSubtitlePattern.matcher(sortableTitle).replaceAll("").trim();
				if (!tmpTitle.isEmpty()) {
					sortableTitle = tmpTitle;
				}
				//remove punctuation from the sortable title
				sortableTitle = punctuationPattern.matcher(sortableTitle).replaceAll("");
				this.titleSort = sortableTitle.trim();

				//SubTitle only gets set based on the main title.
				if (subTitle == null || subTitle.isEmpty()){
					this.displayTitle = shortTitle;
					if (this.subTitle != null) {
						//clear the subtitle if it was set by a previous record.
						this.subTitle = null;
					}
				}else {
					setSubTitle(subTitle);
					subTitle = AspenStringUtils.trimTrailingPunctuation(subTitle);
					this.displayTitle = shortTitle.concat(": ").concat(subTitle);
				}
			}

			//Create an alternate title for searching by replacing ampersands with the word and.
			String tmpTitle = shortTitle.replace("&", " and ").replace("  ", " ");
			if (!tmpTitle.equals(shortTitle)) {
				this.titleAlt.add(shortTitle);
				// alt title has multiple values
			}
			keywords.add(shortTitle);
		}
	}


	private void setSubTitle(String subTitle) {
		if (subTitle != null) {
			subTitle = AspenStringUtils.trimTrailingPunctuation(subTitle);
			//TODO: determine if the subtitle should be changed?
			//Strip out anything in brackets unless that would cause us to show nothing
//			String tmpTitle = removeBracketsPattern.matcher(subTitle).replaceAll("").trim();
//			if (!tmpTitle.isEmpty()) {
//				subTitle = tmpTitle;
//			}
			this.subTitle = subTitle;
			keywords.add(subTitle);
		}
	}

	void clearSubTitle(){
		this.subTitle = "";
	}

	void addFullTitles(Set<String> fullTitles) {
		this.fullTitles.addAll(fullTitles);
	}

	void addFullTitle(String title) {
		this.fullTitles.add(title);
	}

	void addAlternateTitles(Set<String> altTitles) {
		this.titleAlt.addAll(altTitles);
	}

	void addOldTitles(Set<String> oldTitles) {
		this.titleOld.addAll(oldTitles);
	}

	void addNewTitles(Set<String> newTitles) {
		this.titleNew.addAll(newTitles);
	}

	void setAuthor(String author) {
		if (author != null) {
			author = AspenStringUtils.trimTrailingPunctuation(author);
			if (primaryAuthors.containsKey(author)) {
				primaryAuthors.put(author, primaryAuthors.get(author) + 1);
			} else {
				primaryAuthors.put(author, 1L);
			}
		}
	}

	protected String getPrimaryAuthor() {
		String mostUsedAuthor = null;
		long numUses = -1;
		for (String curAuthor : primaryAuthors.keySet()) {
			if (primaryAuthors.get(curAuthor) > numUses) {
				mostUsedAuthor = curAuthor;
			}
		}
		return mostUsedAuthor;
	}

	void setAuthorDisplay(String newAuthor, String formatCategory) {
		boolean updateAuthor = false;
		if (this.authorDisplay == null) {
			updateAuthor = true;
		} else {
			if (formatCategory.equals("Books")) {
				//We have a book, update if we didn't have a book before
				if (!formatCategory.equals(authorFormat)) {
					updateAuthor = true;
				}
			} else if (formatCategory.equals("eBook")) {
				//Update if the format we had before is not a book
				if (!authorFormat.equals("Books")) {
					//And the new format was not an eBook or the new title is longer than what we had before
					if (!formatCategory.equals(authorFormat)) {
						updateAuthor = true;
					}
				}
			}
		}
		if (updateAuthor) {
			this.authorDisplay = AspenStringUtils.trimTrailingPunctuation(newAuthor);
			authorFormat = formatCategory;
		}
	}

	void setAuthAuthor(String author) {
		this.authAuthor = AspenStringUtils.trimTrailingPunctuation(author);
		keywords.add(this.authAuthor);
	}

	void addOclcNumbers(Set<String> oclcs) {
		this.oclcs.addAll(oclcs);
	}

	void addIsbns(Set<String> isbns, String format) {
		for (String isbn : isbns) {
			addIsbn(isbn, format);
		}
	}

	void addIsbn(String isbn, String format) {
		isbn = isbn.replaceAll("\\D", "");
		if (isbn.length() == 10) {
			isbn = Util.convertISBN10to13(isbn);
		}
		if (isbns.containsKey(isbn)) {
			isbns.put(isbn, isbns.get(isbn) + 1);
		} else {
			isbns.put(isbn, 1L);
		}
		//Determine if we should set the primary isbn
		boolean updatePrimaryIsbn = false;
		boolean newIsbnIsBook = format.equalsIgnoreCase("book");
		if (primaryIsbn == null) {
			updatePrimaryIsbn = true;
		} else if (!primaryIsbn.equals(isbn)) {
			if (!primaryIsbnIsBook && newIsbnIsBook) {
				updatePrimaryIsbn = true;
			} else if (primaryIsbnIsBook == newIsbnIsBook) {
				//Both are books or both are not books
				if (isbns.get(isbn) > primaryIsbnUsageCount) {
					updatePrimaryIsbn = true;
				}
			}
		}

		if (updatePrimaryIsbn) {
			primaryIsbn = isbn;
			primaryIsbnIsBook = format.equalsIgnoreCase("book");
			primaryIsbnUsageCount = isbns.get(isbn);
		}
	}

	Set<String> getIsbns() {
		return isbns.keySet();
	}

	void addIssns(Set<String> issns) {
		this.issns.addAll(issns);
	}

	void addUpc(String upc) {
		if (upcs.containsKey(upc)) {
			upcs.put(upc, upcs.get(upc) + 1);
		} else {
			upcs.put(upc, 1L);
		}
	}

	void addAlternateId(String alternateId) {
		this.alternateIds.add(alternateId);
	}

	void setGroupingCategory(String groupingCategory) {
		this.groupingCategory = groupingCategory;
	}

	void addAuthAuthor2(Set<String> fieldList) {
		this.authAuthor2.addAll(AspenStringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthor2(Set<String> fieldList) {
		this.author2.addAll(AspenStringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthor2Role(Set<String> fieldList) {
		this.author2Role.addAll(AspenStringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthorAdditional(Set<String> fieldList) {
		this.authorAdditional.addAll(AspenStringUtils.trimTrailingPunctuation(fieldList));
	}

	void addHoldings(int recordHoldings) {
		if (recordHoldings > 1000) {
			//This is an unlimited access title, just count it as 1
			recordHoldings = 1;
		}
		this.numHoldings += recordHoldings;
	}

	void addPopularity(double itemPopularity) {
		this.popularity += itemPopularity;
	}

	void addTopic(Set<String> fieldList) {
		this.topics.addAll(AspenStringUtils.normalizeSubjects(fieldList));
	}

	void addTopic(String fieldValue) {
		this.topics.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addTopicFacet(Set<String> fieldList) {
		this.topicFacets.addAll(AspenStringUtils.normalizeSubjects(fieldList));
	}

	void addTopicFacet(String fieldValue) {
		this.topicFacets.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addSubjects(Set<String> fieldList) {
		this.subjects.addAll(AspenStringUtils.normalizeSubjects(fieldList));
	}

	void addSeries(Set<String> fieldList) {
		for (String curField : fieldList) {
			this.addSeries(curField);
		}
	}

	void addSeries(String series) {
		addSeriesInfoToField(series, this.series);
	}

	void clearSeries(){
		this.seriesWithVolume.clear();
		this.series2.putAll(this.series);
		this.series.clear();
	}

	void addSeriesWithVolume(String seriesName, String volume) {
		if (seriesName != null && !seriesName.isEmpty()) {
			String seriesInfo = getNormalizedSeries(seriesName);
			String seriesInfoLower = seriesInfo.toLowerCase();
			if (GroupedWorkIndexer.hideSeries.contains(seriesInfoLower)) {
				return;
			}
			if (!volume.isEmpty()) {
				volume = getNormalizedSeriesVolume(volume);
			}
			String volumeLower = volume.toLowerCase();
			String seriesInfoWithVolume = seriesInfo + "|" + (!volume.isEmpty() ? volume : "");
			String normalizedSeriesInfoWithVolume = seriesInfoWithVolume.toLowerCase();

			if (!this.seriesWithVolume.containsKey(normalizedSeriesInfoWithVolume)) {
				boolean okToAdd = true;
				for (String existingSeries2 : this.seriesWithVolume.keySet()) {
					String[] existingSeriesInfo = existingSeries2.split("\\|", 2);
					String existingSeriesName = existingSeriesInfo[0];
					String existingVolume = "";
					if (existingSeriesInfo.length > 1) {
						existingVolume = existingSeriesInfo[1];
					}
					//Get the longer series name
					if (existingSeriesName.contains(seriesInfoLower)) {
						//Use the old one unless it doesn't have a volume
						if (existingVolume.isEmpty()) {
							this.seriesWithVolume.remove(existingSeries2);
							break;
						} else {
							if (volumeLower.equals(existingVolume)) {
								okToAdd = false;
								break;
							} else if (volumeLower.isEmpty()) {
								okToAdd = false;
								break;
							}
						}
					} else if (seriesInfoLower.contains(existingSeriesName)) {
						//Before removing the old series, make sure the new one has a volume
						if (!existingVolume.isEmpty() && existingVolume.equals(volumeLower)) {
							this.seriesWithVolume.remove(existingSeries2);
							break;
						} else if (volume.isEmpty() && !existingVolume.isEmpty()) {
							okToAdd = false;
							break;
						} else if (volume.isEmpty()) {
							this.seriesWithVolume.remove(existingSeries2);
							break;
						}
					}
				}
				if (okToAdd) {
					this.seriesWithVolume.put(normalizedSeriesInfoWithVolume, seriesInfoWithVolume);
				}
			}
		}
	}

	void addSeries2(Set<String> fieldList) {
		for (String curField : fieldList) {
			this.addSeries2(curField);
		}
	}

	private void addSeries2(String series2) {
		if (series != null) {
			addSeriesInfoToField(series2, this.series2);
		}
	}

	private void addSeriesInfoToField(String seriesInfo, HashMap<String, String> seriesField) {
		if (seriesInfo != null && !seriesInfo.equalsIgnoreCase("none")) {
			seriesInfo = getNormalizedSeries(seriesInfo);
			String normalizedSeriesLower = seriesInfo.toLowerCase();
			if (GroupedWorkIndexer.hideSeries.contains(normalizedSeriesLower)) {
				return;
			}
			if (!seriesField.containsKey(normalizedSeriesLower)) {
				boolean okToAdd = true;
				for (String existingSeries2 : seriesField.keySet()) {
					if (existingSeries2.contains(normalizedSeriesLower)) {
						okToAdd = false;
						break;
					} else if (normalizedSeriesLower.contains(existingSeries2)) {
						seriesField.remove(existingSeries2);
						break;
					}
				}
				if (okToAdd) {
					seriesField.put(normalizedSeriesLower, seriesInfo);
				}
			}
		}
	}

	private String getNormalizedSeriesVolume(String volume) {
		volume = AspenStringUtils.trimTrailingPunctuation(volume);
		volume = volume.replaceAll("(bk\\.?|book)", "");
		volume = volume.replaceAll("(volume|vol\\.|v\\.)", "");
		volume = volume.replaceAll("libro", "");
		volume = volume.replaceAll("one", "1");
		volume = volume.replaceAll("two", "2");
		volume = volume.replaceAll("three", "3");
		volume = volume.replaceAll("four", "4");
		volume = volume.replaceAll("five", "5");
		volume = volume.replaceAll("six", "6");
		volume = volume.replaceAll("seven", "7");
		volume = volume.replaceAll("eight", "8");
		volume = volume.replaceAll("nine", "9");
		volume = volume.replaceAll("[\\[\\]#]", "");
		volume = AspenStringUtils.trimTrailingPunctuation(volume.trim());
		return volume;
	}

	private String getNormalizedSeries(String series) {
		series = AspenStringUtils.trimTrailingPunctuation(series);
		series = series.replaceAll("[#|]\\s*\\d+$", "");

		//Remove anything in parentheses since it's normally just the format
		// series = series.replaceAll("\\s+\\(+.*?\\)+", "");
		series = series.replaceAll(" & ", " and ");
		series = series.replaceAll("--", " ");
		series = series.replaceAll(",\\s+(the|an)$", "");
		series = series.replaceAll("[:,]\\s", " ");
		//Remove the word series at the end since this gets cataloged inconsistently
		series = series.replaceAll("(?i)\\s+series$", "");

		return AspenStringUtils.trimTrailingPunctuation(series).trim();
	}


	void addPhysical(Set<String> fieldList) {
		this.physicals.addAll(fieldList);
	}

	void addPhysical(String field) {
		this.physicals.add(field);
	}

	void addDateSpan(Set<String> fieldList) {
		this.dateSpans.addAll(fieldList);
	}

	void addEditions(Set<String> fieldList) {
		this.editions.addAll(fieldList);
	}

	void addContents(Set<String> fieldList) {
		this.contents.addAll(fieldList);
	}

	void addContents(String contents) {
		this.contents.add(contents);
	}

	void addGenre(Set<String> fieldList) {
		this.genres.addAll(AspenStringUtils.normalizeSubjects(fieldList));
	}

	void addGenre(String fieldValue) {
		this.genres.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addGenreFacet(Set<String> fieldList) {
		this.genreFacets.addAll(AspenStringUtils.normalizeSubjects(fieldList));
	}

	void addGenreFacet(String fieldValue) {
		this.genreFacets.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addGeographic(String fieldValue) {
		this.geographic.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addGeographicFacet(String fieldValue) {
		this.geographicFacets.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addPersonalNameSubject(String fieldValue) {
		this.personalNameSubjects.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addCorporateNameSubject(String fieldValue) {
		this.corporateNameSubjects.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void addEra(String fieldValue) {
		this.eras.add(AspenStringUtils.normalizeSubject(fieldValue));
	}

	void setLanguageBoost(Long languageBoost) {
		if (languageBoost > this.languageBoost) {
			this.languageBoost = languageBoost;
		}
	}

	void setLanguageBoostSpanish(Long languageBoostSpanish) {
		if (languageBoostSpanish > this.languageBoostSpanish) {
			this.languageBoostSpanish = languageBoostSpanish;
		}
	}

	void setLanguages(HashSet<String> languages) {
		this.languages.addAll(languages);
	}

	void setTranslations(HashSet<String> translations) {
		this.translations.addAll(translations);
	}

	void addPublishers(Set<String> publishers) {
		for(String publisher : publishers) {
			addPublisher(publisher);
		}
	}

	void addPublisher(String publisher) {
		publisher = publisher.trim();
		if (publisher.endsWith(",") || publisher.endsWith(";")){
			publisher = publisher.substring(0, publisher.length() - 1).trim();
		}
		if (!publisher.isEmpty()){
			this.publishers.add(publisher);
		}
	}

	void addPublicationDates(Set<String> publicationDate) {
		for (String pubDate : publicationDate) {
			addPublicationDate(pubDate);
		}
	}

	void addPublicationDate(String publicationDate) {
		String cleanDate = DateUtils.cleanDate(publicationDate);
		if (cleanDate != null) {
			this.publicationDates.add(cleanDate);
			//Convert the date to a long and see if it is before the current date
			long pubDateLong = Long.parseLong(cleanDate);
			if (earliestPublicationDate == null || pubDateLong < earliestPublicationDate) {
				earliestPublicationDate = pubDateLong;
			}
		}
	}

	void addPlacesOfPublication(Set<String> placesOfPublication) {
		for (String placeOfPublication: placesOfPublication) {
			addPlaceOfPublication(placeOfPublication);
		}
	}

	void addPlaceOfPublication(String placeOfPublication) {
		placeOfPublication	= AspenStringUtils.trimTrailingPunctuation(placeOfPublication);
		if (!placeOfPublication.isEmpty()) {
			this.placesOfPublication.add(placeOfPublication);
		}
	}

	void addLiteraryForms(HashMap<String, Integer> literaryForms) {
		for (String curLiteraryForm : literaryForms.keySet()) {
			this.addLiteraryForm(curLiteraryForm, literaryForms.get(curLiteraryForm));
		}
	}

	private void addLiteraryForm(String literaryForm, int count) {
		if (literaryForm.equals("Not Coded")){
			if (this.groupedWorkIndexer.isHideNotCodedLiteraryForm()){
				return;
			}
		}
		if (literaryForm.equals("Unknown")){
			if (this.groupedWorkIndexer.isHideUnknownLiteraryForm()){
				return;
			}
		}
		literaryForm = literaryForm.trim();
		if (this.literaryForm.containsKey(literaryForm)) {
			Integer numMatches = this.literaryForm.get(literaryForm);
			this.literaryForm.put(literaryForm, numMatches + count);
		} else {
			this.literaryForm.put(literaryForm, count);
		}
	}

	void addLiteraryForm(String literaryForm) {
		if (literaryForm.equals("Not Coded")){
			if (this.groupedWorkIndexer.isHideNotCodedLiteraryForm()){
				return;
			}
		}
		if (literaryForm.equals("Unknown")){
			if (this.groupedWorkIndexer.isHideUnknownLiteraryForm()){
				return;
			}
		}
		addLiteraryForm(literaryForm, 1);
	}

	void addLiteraryFormsFull(HashMap<String, Integer> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull.keySet()) {
			this.addLiteraryFormFull(curLiteraryForm, literaryFormsFull.get(curLiteraryForm));
		}
	}

	private void addLiteraryFormFull(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (literaryForm.equals("Not Coded")){
			if (this.groupedWorkIndexer.isHideNotCodedLiteraryForm()){
				return;
			}
		}
		if (literaryForm.equals("Unknown")){
			if (this.groupedWorkIndexer.isHideUnknownLiteraryForm()){
				return;
			}
		}
		if (this.literaryFormFull.containsKey(literaryForm)) {
			Integer numMatches = this.literaryFormFull.get(literaryForm);
			this.literaryFormFull.put(literaryForm, numMatches + count);
		} else {
			this.literaryFormFull.put(literaryForm, count);
		}
	}

	void addLiteraryFormFull(String literaryForm) {
		if (literaryForm.equals("Not Coded")){
			if (this.groupedWorkIndexer.isHideNotCodedLiteraryForm()){
				return;
			}
		}
		if (literaryForm.equals("Unknown")){
			if (this.groupedWorkIndexer.isHideUnknownLiteraryForm()){
				return;
			}
		}
		this.addLiteraryFormFull(literaryForm, 1);
	}

	void addTargetAudiences(HashSet<String> target_audiences) {
		for (String target_audience : target_audiences) {
			this.addTargetAudience(target_audience);
		}
	}

	void addTargetAudience(String target_audience) {
		switch (target_audience){
			case "Unknown":
			case "Other":
				if (targetAudience.isEmpty()){
					targetAudience.add(target_audience);
					targetAudiencesAsString = null;
				}
				break;
			default:
				if (!targetAudience.contains(target_audience)) {
					if (targetAudience.contains("Unknown")) {
						targetAudience.remove("Unknown");
					} else {
						targetAudience.remove("Other");
					}
					targetAudience.add(target_audience);
					targetAudiencesAsString = null;
				}
				break;
		}
	}

	void addTargetAudiencesFull(HashSet<String> target_audiences_full) {
		for (String target_audience : target_audiences_full) {
			this.addTargetAudienceFull(target_audience);
		}
	}

	void addTargetAudienceFull(String target_audience) {
		targetAudienceFull.add(target_audience);
		switch (target_audience){
			case "Unknown":
			case "Other":
			case "No Attempt To Code":
				//noinspection ConstantConditions
				if (targetAudienceFull.isEmpty()){
					targetAudienceFull.add(target_audience);
				}
				break;
			default:
				if (targetAudienceFull.contains("Unknown")){
					targetAudienceFull.remove("Unknown");
				}else if (targetAudienceFull.contains("Other")){
					targetAudienceFull.remove("Other");
				}else //noinspection RedundantCollectionOperation
					if (targetAudienceFull.contains("No Attempt To Code")){
						targetAudienceFull.remove("No Attempt To Code");
					}
				targetAudienceFull.add(target_audience);
				break;
		}
	}

	protected Set<String> getRatingFacet(Float rating) {
		Set<String> ratingFacet = new HashSet<>();
		if (rating >= 4.9) {
			ratingFacet.add("fiveStar");
		} else if (rating >= 4) {
			ratingFacet.add("fourStar");
		} else if (rating >= 3) {
			ratingFacet.add("threeStar");
		} else if (rating >= 2) {
			ratingFacet.add("twoStar");
		} else if (rating >= 0.0001) {
			ratingFacet.add("oneStar");
		} else {
			ratingFacet.add("Unrated");
		}
		return ratingFacet;
	}

	void addMpaaRating(String mpaaRating) {
		this.mpaaRatings.add(mpaaRating);
	}

	void addBarcodes(Set<String> barcodeList) {
		for (String barcode: barcodeList){
			if (!barcode.isEmpty()){
				this.barcodes.add(barcode);
			}
		}
	}

	void setRating(float rating) {
		this.rating = rating;
	}

	void setLexileScore(String lexileScore) {
		this.lexileScore = lexileScore;
	}

	void setLexileCode(String lexileCode) {
		this.lexileCode = lexileCode;
	}

	void setFountasPinnell(String fountasPinnell) {
		if (this.fountasPinnell.isEmpty()) {
			this.fountasPinnell = fountasPinnell;
		}
	}

	void addAwards(Set<String> awards) {
		this.awards.addAll(AspenStringUtils.trimTrailingPunctuation(awards));
	}

	void setAcceleratedReaderInterestLevel(String acceleratedReaderInterestLevel) {
		if (acceleratedReaderInterestLevel != null) {
			this.acceleratedReaderInterestLevel = acceleratedReaderInterestLevel;
		}
	}

	void setAcceleratedReaderReadingLevel(String acceleratedReaderReadingLevel) {
		if (acceleratedReaderReadingLevel != null) {
			this.acceleratedReaderReadingLevel = acceleratedReaderReadingLevel;
		}
	}

	void setAcceleratedReaderPointValue(String acceleratedReaderPointValue) {
		if (acceleratedReaderPointValue != null) {
			this.acceleratedReaderPointValue = acceleratedReaderPointValue;
		}
	}

	void setCallNumberA(String callNumber) {
		if (callNumber != null && callNumberA == null) {
			this.callNumberA = callNumber;
		}
	}

	void setCallNumberFirst(String callNumber) {
		if (callNumber != null && callNumberFirst == null) {
			this.callNumberFirst = callNumber;
		}
	}

	void setCallNumberSubject(String callNumber) {
		if (callNumber != null && callNumberSubject == null) {
			this.callNumberSubject = callNumber;
		}
	}

	void addKeywords(String keywords) {
		this.keywords.add(keywords);
	}

	void addKeywords(HashSet<String> keywords) {
		this.keywords.addAll(keywords);
	}

	void addIlsDescription(String description, String formatCategory) {
		if (description == null || description.isEmpty()) {
			return;
		}
		this.description.add(description);
		boolean updateDescription = false;
		if (this.ilsDescription == null) {
			updateDescription = true;
		} else {
			//Only overwrite if we get a better format
			if (formatCategory.equals("Books")) {
				//We have a book, update if we didn't have a book before
				if (!formatCategory.equals(ilsDescriptionFormat)) {
					updateDescription = true;
					//or update if we had a book before and this Description is longer
				} else if (description.length() > this.ilsDescription.length()) {
					updateDescription = true;
				}
			} else if (formatCategory.equals("eBook")) {
				//Update if the format we had before is not a book
				if (!ilsDescriptionFormat.equals("Books")) {
					//And the new format was not an eBook or the new Description is longer than what we had before
					if (!formatCategory.equals(ilsDescriptionFormat)) {
						updateDescription = true;
						//or update if we had a book before and this Description is longer
					} else if (description.length() > this.ilsDescription.length()) {
						updateDescription = true;
					}
				}
			} else if (!ilsDescriptionFormat.equals("Books") && !ilsDescriptionFormat.equals("eBook")) {
				//If we don't have a Book or an eBook then we can update the Description if we get a longer Description
				if (description.length() > this.ilsDescription.length()) {
					updateDescription = true;
				}
			}
		}
		if (updateDescription) {
			this.ilsDescription = description;
			this.ilsDescriptionFormat = formatCategory;
		}
	}

	void addDescription(String description, String formatCategory) {
		if (description == null || description.isEmpty()) {
			return;
		}
		this.description.add(description);
		boolean updateDescription = false;
		if (this.displayDescription == null) {
			updateDescription = true;
		} else {
			//Only overwrite if we get a better format
			if (formatCategory.equals("Books")) {
				//We have a book, update if we didn't have a book before
				if (!formatCategory.equals(displayDescriptionFormat)) {
					updateDescription = true;
					//or update if we had a book before and this Description is longer
				} else if (description.length() > this.displayDescription.length()) {
					updateDescription = true;
				}
			} else if (formatCategory.equals("eBook")) {
				//Update if the format we had before is not a book
				if (!displayDescriptionFormat.equals("Books")) {
					//And the new format was not an eBook or the new Description is longer than what we had before
					if (!formatCategory.equals(displayDescriptionFormat)) {
						updateDescription = true;
						//or update if we had a book before and this Description is longer
					} else if (description.length() > this.displayDescription.length()) {
						updateDescription = true;
					}
				}
			} else if (!displayDescriptionFormat.equals("Books") && !displayDescriptionFormat.equals("eBook")) {
				//If we don't have a Book or an eBook then we can update the Description if we get a longer Description
				if (description.length() > this.displayDescription.length()) {
					updateDescription = true;
				}
			}
		}
		if (updateDescription) {
			this.displayDescription = description;
			this.displayDescriptionFormat = formatCategory;
		}
	}

	RecordInfo addRelatedRecord(String source, String recordIdentifier) {
		String recordIdentifierWithType = source + ":" + recordIdentifier;
		if (relatedRecords.containsKey(recordIdentifierWithType)) {
			return relatedRecords.get(recordIdentifierWithType);
		} else {
			RecordInfo newRecord = new RecordInfo(source, recordIdentifier);
			relatedRecords.put(recordIdentifierWithType, newRecord);
			return newRecord;
		}
	}

	@SuppressWarnings("SameParameterValue")
	RecordInfo addRelatedRecord(String source, String subSource, String recordIdentifier) {
		String recordIdentifierWithType = source + ":" + subSource + ":" + recordIdentifier;
		if (relatedRecords.containsKey(recordIdentifierWithType)) {
			return relatedRecords.get(recordIdentifierWithType);
		} else {
			RecordInfo newRecord = new RecordInfo(source, recordIdentifier);
			newRecord.setSubSource(subSource);
			relatedRecords.put(recordIdentifierWithType, newRecord);
			return newRecord;
		}
	}

	void addLCSubject(String lcSubject) {
		this.lcSubjects.add(AspenStringUtils.normalizeSubject(lcSubject));
	}

	void addBisacSubject(String bisacSubject) {
		this.bisacSubjects.add(AspenStringUtils.normalizeSubject(bisacSubject));
	}

	void removeRelatedRecord(RecordInfo recordInfo) {
		this.relatedRecords.remove(recordInfo.getFullIdentifier());
	}

	int getNumRecords() {
		return this.relatedRecords.size();
	}

	TreeSet<String> getTargetAudiences() {

		return targetAudience;
	}

	void addLanguage(String language) {
		this.languages.add(language);
	}

	/**
	 * Removes any hoopla records where the equivalent format exists in another eContent format with APIs
	 *
	 * 0 = do not remove settings
	 * 1 = remove only if the other record is available
	 * 2 = remove regardless of if the other record is available
	 */
	void removeRedundantHooplaRecords() {
		if (relatedRecords.size() > 1) {
			ArrayList<RecordInfo> hooplaRecordsAsArray = new ArrayList<>();
			ArrayList<RecordInfo> otherRecordsAsArray = new ArrayList<>();
			for (RecordInfo relatedRecord : relatedRecords.values()) {
				if (relatedRecord.getSource().equals("hoopla")) {
					hooplaRecordsAsArray.add(relatedRecord);
				} else if (relatedRecord.getNumEContentCopies() > 0) {
					otherRecordsAsArray.add(relatedRecord);
				}
			}
			if (otherRecordsAsArray.isEmpty() || hooplaRecordsAsArray.isEmpty()){
				return;
			}
			// record 1 is a hoopla record
			// record 2 is not a hoopla record.

			for (RecordInfo record1 : hooplaRecordsAsArray) {
				String record1PrimaryFormat = record1.getPrimaryFormat();
				//This is a candidate for removal
				for (RecordInfo record2 : otherRecordsAsArray) {
					String record2PrimaryFormat = record2.getPrimaryFormat();
					//Make sure we have the same format
					if (record1PrimaryFormat.equals(record2PrimaryFormat)) {

						//Loop through all the scopes to see if we should remove the hoopla record from that scope.
						for (ItemInfo curItem1 : record1.getRelatedItems()){
							HashSet<String> scopesToRemove = new HashSet<>();
							for (ScopingInfo item1Scope : curItem1.getScopingInfo().values()) {
								String item1ScopeName = item1Scope.getScope().getScopeName();
								//Get information about the scope to determine how this scope should be processed.
								switch (item1Scope.getScope().getHooplaScope().getExcludeTitlesWithCopiesFromOtherVendors()) {
									case 0:
										//Don't remove items that have the same record someplace else
										break;
									case 1:
										//Remove if there is an available copy for the scope
										for (ItemInfo curItem2 : record2.getRelatedItems()){
											if (curItem2.isAvailable()){
												if (curItem2.getScopingInfo().containsKey(item1ScopeName)){
													scopesToRemove.add(item1ScopeName);
													break;
												}
											}
										}
										break;
									case 2:
										//Remove if there is another copy in the scope (does not have to be available)
										for (ItemInfo curItem2 : record2.getRelatedItems()){
											if (curItem2.getScopingInfo().containsKey(item1ScopeName)){
												scopesToRemove.add(item1ScopeName);
												break;
											}
										}
										break;
								}
							}
							for (String scopeToRemove : scopesToRemove){
								curItem1.getScopingInfo().remove(scopeToRemove);
								//Remove from related scopes as well
								ArrayList<ScopingInfo> scopingInfo = relatedScopes.get(scopeToRemove);
								if (scopingInfo != null) {
									ArrayList<ScopingInfo> scopingInfoClone;
									//noinspection unchecked
									scopingInfoClone = (ArrayList<ScopingInfo>) scopingInfo.clone();
									for (ScopingInfo relatedScopeInfo : scopingInfoClone) {
										if (relatedScopeInfo.getItem().equals(curItem1)) {
											scopingInfo.remove(relatedScopeInfo);
										}
									}
									if (scopingInfo.isEmpty()) {
										relatedScopes.remove(scopeToRemove);
									}
								}
							}

							//Remove the item entirely if it is no longer valid for any scope
							if (curItem1.getScopingInfo().isEmpty()){
								record1.getRelatedItems().remove(curItem1);
								break;
							}


						}
					}
				}

				//Remove the record entirely if it has no related items
				if (record1.getRelatedItems().isEmpty()){
					relatedRecords.remove(record1.getFullIdentifier());
				}
			}
		}
	}

	HashSet<Long> getAutoReindexTimes() {
		HashSet<Long> autoReindexTimes = new HashSet<>();
		for (RecordInfo relatedRecord : relatedRecords.values()) {
			relatedRecord.getAutoReindexTimes(autoReindexTimes);
		}
		return autoReindexTimes;
	}

	public void addReadingHistoryLink(long userId) {
		this.userReadingHistoryLink.add(userId);
	}

	public void addRatingLink(long userId){
		this.userRatingLink.add(userId);
	}

	public void addNotInterestedLink(long userId){
		this.userNotInterestedLink.add(userId);
	}

	public synchronized void saveRecordsToDatabase(long groupedWorkId) {
		//Get a list of all existing records for the grouped work
		HashMap<String, SavedRecordInfo> existingRecords = groupedWorkIndexer.getExistingRecordsForGroupedWork(groupedWorkId);
		HashMap<VariationInfo, Long> existingVariations = groupedWorkIndexer.getExistingVariationsForGroupedWork(groupedWorkId);
		HashSet<Long> foundVariations = new HashSet<>();
		//Save all the records
		for (RecordInfo recordInfo : relatedRecords.values()){
			//Don't look at format since that is causing records to be deleted incorrectly
			//long formatId = groupedWorkIndexer.getFormatId(recordInfo.getPrimaryFormat());
			String relatedRecordKey = groupedWorkIndexer.getSourceId(recordInfo.getSource(), recordInfo.getSubSource(), 1) + ":" + recordInfo.getRecordIdentifier(); // + ":" + formatId;
			SavedRecordInfo savedRecord = null;
			if (existingRecords.containsKey(relatedRecordKey)){
				savedRecord = existingRecords.get(relatedRecordKey);
				existingRecords.remove(relatedRecordKey);
			}
			long recordId = groupedWorkIndexer.saveGroupedWorkRecord(groupedWorkId, recordInfo, savedRecord);

			if (recordId != -1) {
				//Get existing items for the record
				HashMap<String, SavedItemInfo> existingItems = groupedWorkIndexer.getExistingItemsForRecord(recordId);

				//Save all the items
				HashSet<Long> foundItems = new HashSet<>();
				for (ItemInfo itemInfo : recordInfo.getRelatedItems()) {
					//Get the variation for the item
					long variationId = groupedWorkIndexer.saveGroupedWorkVariation(existingVariations, groupedWorkId, recordInfo, itemInfo);
					foundVariations.add(variationId);

					long itemId = groupedWorkIndexer.saveItemForRecord(recordId, variationId, itemInfo, existingItems);
					if (itemId != -1) {
						foundItems.add(itemId);
					}
				}

				//Remove remaining items that no longer exist
				for (SavedItemInfo existingItem : existingItems.values()) {
					if (!foundItems.contains(existingItem.id)) {
						groupedWorkIndexer.removeRecordItem(existingItem.id);
					}
				}
			}
		}
		//Anything left over should be removed
		//Remove remaining records
		for (SavedRecordInfo existingRecord : existingRecords.values()){
			groupedWorkIndexer.removeGroupedWorkRecord(existingRecord.id);
		}
		//Remove remaining variations
		for (Long existingVariationId : existingVariations.values()) {
			if (!foundVariations.contains(existingVariationId)) {
				groupedWorkIndexer.removeGroupedWorkVariation(existingVariationId);
			}
		}
	}

	public void addHolds(int numHolds) {
		this.totalHolds += numHolds;
	}

	private String targetAudiencesAsString = null;
	public String getTargetAudiencesAsString() {
		if (targetAudiencesAsString == null) {
			if (targetAudience.isEmpty()) {
				targetAudiencesAsString = "";
			} else if (targetAudience.size() == 1) {
				targetAudiencesAsString = targetAudience.first();
			} else {
				targetAudiencesAsString = targetAudience.toString();
			}
		}
		return targetAudiencesAsString;
	}

	public void addParentRecord(String workId){
		this.parentRecords.add(workId);
	}

	public void addCustomFacetValues(int customFacetNumber, Set<String> fieldData) {
		if (!customFacetValues.containsKey(customFacetNumber)) {
			customFacetValues.put(customFacetNumber, new LinkedHashSet<>());
		}
		customFacetValues.get(customFacetNumber).addAll(fieldData);
	}

	public boolean isDebugEnabled() {
		return debugEnabled;
	}

	public void setDebugEnabled(boolean debugEnabled) {
		this.debugEnabled = debugEnabled;
	}

	public long getDebugId() {
		return debugId;
	}

	public void setDebugId(long debugId) {
		this.debugId = debugId;
	}

	public void addDebugMessage(String message) {
		debugMessages.add(message);
	}

	public void addDebugMessage(String message, int indentLevel) {
		StringBuilder messageBuilder = new StringBuilder(message);
		for (int i = 0; i < indentLevel; i++) {
			messageBuilder.insert(0, "&nbsp;&nbsp;");
		}
		debugMessages.add(messageBuilder.toString());
	}

	public String getDebuggingInfo() {
		StringBuilder debugInfo = new StringBuilder();
		for (String debugMessage : debugMessages) {
			if (debugInfo.length() != 0) {
				debugInfo.append("<br/>");
			}
			debugInfo.append(debugMessage);
		}
		return debugInfo.toString();
	}
}
