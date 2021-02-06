package com.turning_leaf_technologies.reindexer;

import com.sun.istack.internal.NotNull;
import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;

import java.util.*;
import java.util.regex.Pattern;

public class GroupedWorkSolr implements Cloneable {
	private String id;

	private HashMap<String, RecordInfo> relatedRecords = new HashMap<>();

	private String acceleratedReaderInterestLevel;
	private String acceleratedReaderReadingLevel;
	private String acceleratedReaderPointValue;
	private HashSet<String> alternateIds = new HashSet<>();
	private String authAuthor;
	private HashMap<String, Long> primaryAuthors = new HashMap<>();
	private HashSet<String> authorAdditional = new HashSet<>();
	private String authorDisplay;
	private HashSet<String> author2 = new HashSet<>();
	private HashSet<String> authAuthor2 = new HashSet<>();
	private HashSet<String> author2Role = new HashSet<>();
	private HashSet<String> awards = new HashSet<>();
	private HashSet<String> barcodes = new HashSet<>();
	private final HashSet<String> bisacSubjects = new HashSet<>();
	private String callNumberA;
	private String callNumberFirst;
	private String callNumberSubject;
	private HashSet<String> contents = new HashSet<>();
	private HashSet<String> dateSpans = new HashSet<>();
	private HashSet<String> description = new HashSet<>();
	private String displayDescription = "";
	private String displayDescriptionFormat = "";
	private String displayTitle;
	private Long earliestPublicationDate = null;
	private HashSet<String> editions = new HashSet<>();
	private HashSet<String> eras = new HashSet<>();
	private HashSet<String> fullTitles = new HashSet<>();
	private HashSet<String> genres = new HashSet<>();
	private HashSet<String> genreFacets = new HashSet<>();
	private HashSet<String> geographic = new HashSet<>();
	private HashSet<String> geographicFacets = new HashSet<>();
	private String groupingCategory;
	private String primaryIsbn;
	private boolean primaryIsbnIsBook;
	private Long primaryIsbnUsageCount;
	private HashMap<String, Long> isbns = new HashMap<>();
	private HashSet<String> issns = new HashSet<>();
	private HashSet<String> keywords = new HashSet<>();
	private HashSet<String> languages = new HashSet<>();
	private HashSet<String> translations = new HashSet<>();
	private Long languageBoost = 1L;
	private Long languageBoostSpanish = 1L;
	private HashSet<String> lccns = new HashSet<>();
	private HashSet<String> lcSubjects = new HashSet<>();
	private String lexileScore = "-1";
	private String lexileCode = "";
	private String fountasPinnell = "";
	private HashMap<String, Integer> literaryFormFull = new HashMap<>();
	private HashMap<String, Integer> literaryForm = new HashMap<>();
	private HashSet<String> mpaaRatings = new HashSet<>();
	private Long numHoldings = 0L;
	private HashSet<String> oclcs = new HashSet<>();
	private HashSet<String> physicals = new HashSet<>();
	private double popularity;
	private HashSet<String> publishers = new HashSet<>();
	private HashSet<String> publicationDates = new HashSet<>();
	private float rating = -1f;
	private HashMap<String, String> series = new HashMap<>();
	private HashMap<String, String> series2 = new HashMap<>();
	private HashMap<String, String> seriesWithVolume = new HashMap<>();
	private String subTitle;
	private HashSet<String> targetAudienceFull = new HashSet<>();
	private TreeSet<String> targetAudience = new TreeSet<>();
	private String title;
	private HashSet<String> titleAlt = new HashSet<>();
	private HashSet<String> titleOld = new HashSet<>();
	private HashSet<String> titleNew = new HashSet<>();
	private String titleSort;
	private String titleFormat = "";
	private HashSet<String> topics = new HashSet<>();
	private HashSet<String> topicFacets = new HashSet<>();
	private HashSet<String> subjects = new HashSet<>();
	private HashMap<String, Long> upcs = new HashMap<>();

	private final Logger logger;
	private final GroupedWorkIndexer groupedWorkIndexer;
	private HashSet<String> systemLists = new HashSet<>();
	private final HashSet<Long> userReadingHistoryLink = new HashSet<>();
	private final HashSet<Long> userRatingLink = new HashSet<>();
	private final HashSet<Long> userNotInterestedLink = new HashSet<>();

	public GroupedWorkSolr(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		this.logger = logger;
		this.groupedWorkIndexer = groupedWorkIndexer;
	}

	protected GroupedWorkSolr clone() throws CloneNotSupportedException {
		GroupedWorkSolr clonedWork = (GroupedWorkSolr) super.clone();
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

		return clonedWork;
	}

	SolrInputDocument getSolrDocument(BaseLogEntry logEntry) {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("last_indexed", new Date());
		doc.addField("alternate_ids", alternateIds);
		doc.addField("recordtype", "grouped_work");

		//Title and variations
		String fullTitle = title;
		if (subTitle != null) {
			fullTitle += " " + subTitle;
		}
		doc.addField("title", fullTitle);
		doc.addField("title_display", displayTitle);
		//This is set lower now with additional titles added with formats
		//doc.addField("title_full", fullTitles);

		doc.addField("subtitle_display", subTitle);
		doc.addField("title_short", title);
		doc.addField("title_sort", titleSort);
		doc.addField("title_alt", titleAlt);
		doc.addField("title_old", titleOld);
		doc.addField("title_new", titleNew);

		//author and variations
		doc.addField("auth_author", authAuthor);
		doc.addField("author", getPrimaryAuthor());

		doc.addField("auth_author2", authAuthor2);
		doc.addField("author2", author2);
		doc.addField("author2-role", author2Role);
		doc.addField("author_additional", authorAdditional);
		doc.addField("author_display", authorDisplay);
		//format
		doc.addField("grouping_category", groupingCategory);

		doc.addField("format_boost", getTotalFormatBoost());

		//language related fields
		//Check to see if we have Unknown plus a valid value
		if (languages.size() > 1) {
			languages.remove("Unknown");
		}
		doc.addField("language", languages);
		doc.addField("translation", translations);
		doc.addField("language_boost", languageBoost);
		doc.addField("language_boost_es", languageBoostSpanish);
		//Publication related fields
		doc.addField("publisher", publishers);
		doc.addField("publishDate", publicationDates);
		//Sorting will use the earliest date published
		doc.addField("publishDateSort", earliestPublicationDate);

		//faceting and refined searching
		doc.addField("physical", physicals);
		doc.addField("edition", editions);
		doc.addField("dateSpan", dateSpans);
		doc.addField("series", series.values());
		doc.addField("series2", series2.values());
		doc.addField("series_with_volume", seriesWithVolume.values());
		doc.addField("topic", topics);
		doc.addField("topic_facet", topicFacets);
		doc.addField("subject_facet", subjects);
		doc.addField("lc_subject", lcSubjects);
		doc.addField("bisac_subject", bisacSubjects);
		doc.addField("genre", genres);
		doc.addField("genre_facet", genreFacets);
		doc.addField("geographic", geographic);
		doc.addField("geographic_facet", geographicFacets);
		doc.addField("era", eras);
		checkDefaultValue(literaryFormFull, "Not Coded");
		checkDefaultValue(literaryFormFull, "Other");
		checkInconsistentLiteraryFormsFull();
		doc.addField("literary_form_full", literaryFormFull.keySet());
		checkDefaultValue(literaryForm, "Not Coded");
		checkDefaultValue(literaryForm, "Other");
		checkInconsistentLiteraryForms();
		doc.addField("literary_form", literaryForm.keySet());
		checkDefaultValue(targetAudienceFull, "Unknown");
		checkDefaultValue(targetAudienceFull, "Other");
		checkDefaultValue(targetAudienceFull, "No Attempt To Code");
		doc.addField("target_audience_full", targetAudienceFull);
		checkDefaultValue(targetAudience, "Unknown");
		checkDefaultValue(targetAudience, "Other");
		doc.addField("target_audience", targetAudience);
		doc.addField("system_list", systemLists);
		//Date added to catalog
		Date dateAdded = getDateAdded();
		doc.addField("date_added", dateAdded);

		//Check to see if all items are on order.  If so, add on order keywords
		boolean allItemsOnOrder = true;
		int numItems = 0;
		for (RecordInfo record : relatedRecords.values()) {
			for (ItemInfo item : record.getRelatedItems()) {
				numItems++;
				if (!(item.isOrderItem() || (item.getStatusCode() != null && item.getStatusCode().equals("On Order")))) {
					allItemsOnOrder = false;
				}
			}
			if (record.getFormatCategories().size() > 0) {
				fullTitles.add(fullTitle + " " + record.getFormatCategories().toString());
			}
		}
		doc.addField("title_full", fullTitles);

		if (numItems == 0) {
			allItemsOnOrder = false;
		}
		if (allItemsOnOrder) {
			addKeywords("On Order");
			addKeywords("Coming Soon");
			doc.addField("days_since_added", -1);
			doc.addField("time_since_added", "On Order");
		} else {
			//Check to see if all items are either on order or
			if (dateAdded == null) {
				//Determine date added based on publication date
				if (earliestPublicationDate != null) {
					//Return number of days since the given year
					Calendar publicationDate = GregorianCalendar.getInstance();
					publicationDate.set(earliestPublicationDate.intValue(), Calendar.JANUARY, 1);

					long indexTime = new Date().getTime();
					long publicationTime = publicationDate.getTime().getTime();
					long bibDaysSinceAdded = (indexTime - publicationTime) / (long) (1000 * 60 * 60 * 24);
					doc.addField("days_since_added", Long.toString(bibDaysSinceAdded));
					doc.addField("time_since_added", DateUtils.getTimeSinceAddedForDate(publicationDate.getTime()));
				} else {
					doc.addField("days_since_added", Long.toString(Integer.MAX_VALUE));
				}
			} else {
				doc.addField("days_since_added", DateUtils.getDaysSinceAddedForDate(dateAdded));
				doc.addField("time_since_added", DateUtils.getTimeSinceAddedForDate(dateAdded));
			}
		}

		doc.addField("barcode", barcodes);
		//Awards and ratings
		doc.addField("mpaa_rating", mpaaRatings);
		doc.addField("awards_facet", awards);
		if (lexileScore.length() == 0) {
			doc.addField("lexile_score", -1);
		} else {
			doc.addField("lexile_score", lexileScore);
		}
		if (lexileCode.length() > 0) {
			doc.addField("lexile_code", StringUtils.trimTrailingPunctuation(lexileCode));
		}
		if (fountasPinnell.length() > 0) {
			doc.addField("fountas_pinnell", fountasPinnell);
		}
		doc.addField("accelerated_reader_interest_level", StringUtils.trimTrailingPunctuation(acceleratedReaderInterestLevel));
		if (StringUtils.isNumeric(acceleratedReaderReadingLevel)) {
			doc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
		}
		if (StringUtils.isNumeric(acceleratedReaderPointValue)) {
			doc.addField("accelerated_reader_point_value", acceleratedReaderPointValue);
		}
		HashSet<String> eContentSources = getAllEContentSources();
		keywords.addAll(eContentSources);

		keywords.addAll(isbns.keySet());
		keywords.addAll(oclcs);
		keywords.addAll(barcodes);
		keywords.addAll(issns);
		keywords.addAll(lccns);
		keywords.addAll(upcs.keySet());

		HashSet<String> callNumbers = getAllCallNumbers();
		keywords.addAll(callNumbers);
		doc.addField("keywords", Util.getCRSeparatedStringFromSet(keywords));

		doc.addField("table_of_contents", contents);
		//broad search terms
		//identifiers
		doc.addField("lccn", lccns);
		doc.addField("oclc", oclcs);
		//Get the primary isbn
		doc.addField("primary_isbn", primaryIsbn);
		doc.addField("isbn", isbns.keySet());
		doc.addField("issn", issns);
		doc.addField("primary_upc", getPrimaryUpc());
		doc.addField("upc", upcs.keySet());

		//call numbers
		doc.addField("callnumber-first", callNumberFirst);
		doc.addField("callnumber-subject", callNumberSubject);
		//relevance determiners
		doc.addField("popularity", Long.toString((long) popularity));
		doc.addField("num_holdings", numHoldings);
		//aspen-discovery enrichment
		doc.addField("rating", rating == -1f ? 2.5 : rating);
		doc.addField("rating_facet", getRatingFacet(rating));

		//Links to users
		doc.addField("user_rating_link", userRatingLink);
		doc.addField("user_not_interested_link", userNotInterestedLink);
		doc.addField("user_reading_history_link", userReadingHistoryLink);

		doc.addField("description", Util.getCRSeparatedString(description));
		doc.addField("display_description", displayDescription);

		//Save information from scopes
		addScopedFieldsToDocument(doc, logEntry);

		return doc;
	}

	private String getPrimaryUpc() {
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

	private Long getTotalFormatBoost() {
		long formatBoost = 0;
		for (RecordInfo curRecord : relatedRecords.values()) {
			formatBoost += curRecord.getFormatBoost();
		}
		if (formatBoost == 0) {
			formatBoost = 1;
		}
		return formatBoost;
	}

	private HashSet<String> getAllEContentSources() {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()) {
			values.addAll(curRecord.getAllEContentSources());
		}
		return values;
	}

	private HashSet<String> getAllCallNumbers() {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()) {
			values.addAll(curRecord.getAllCallNumbers());
		}
		return values;
	}

	private Date getDateAdded() {
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

	private void addScopedFieldsToDocument(SolrInputDocument doc, BaseLogEntry logEntry) {
		//Load information based on scopes.  This has some pretty severe performance implications since we potentially
		//have a lot of scopes and a lot of items & records.
		int numScopesForWork = 0;
		for (RecordInfo curRecord : relatedRecords.values()) {
			doc.addField("record_details", curRecord.getDetails());
			for (ItemInfo curItem : curRecord.getRelatedItems()) {
				doc.addField("item_details", curItem.getDetails(logEntry));
				HashMap<String, ScopingInfo> curScopingInfo = curItem.getScopingInfo();
				Set<String> scopingNames = curScopingInfo.keySet();
				for (String curScopeName : scopingNames) {
					numScopesForWork++;
					ScopingInfo curScope = curScopingInfo.get(curScopeName);
					doc.addField("scoping_details_" + curScopeName, curScope.getScopingDetails());
					//if we do that, we don't need to filter within PHP
					addUniqueFieldValue(doc, "scope_has_related_records", curScopeName);
					HashSet<String> formats = new HashSet<>();
					if (curItem.getFormat() != null) {
						formats.add(curItem.getFormat());
					} else {
						formats = curRecord.getFormats();
					}
					addUniqueFieldValues(doc, "format_" + curScopeName, formats);
					HashSet<String> formatCategories = new HashSet<>();
					if (curItem.getFormatCategory() != null) {
						formatCategories.add(curItem.getFormatCategory());
					} else {
						formatCategories = curRecord.getFormatCategories();
					}
					//eAudiobooks are considered both Audiobooks and eBooks by some people
					if (formats.contains("eAudiobook")) {
						formatCategories.add("eBook");
					}
					if (formats.contains("VOX Books")) {
						formatCategories.add("Books");
						formatCategories.add("Audio Books");
					}
					addUniqueFieldValues(doc, "format_category_" + curScopeName, formatCategories);

					//Setup ownership & availability toggle values
					setupAvailabilityToggleAndOwnershipForItemWithinScope(doc, curRecord, curItem, curScopeName, curScope);

					Scope curScopeDetails = curScope.getScope();
					if (curScope.isLocallyOwned() || curScope.isLibraryOwned() || curScopeDetails.getGroupedWorkDisplaySettings().isIncludeAllRecordsInShelvingFacets()) {
						addUniqueFieldValue(doc, "collection_" + curScopeName, curItem.getCollection());
						addUniqueFieldValue(doc, "detailed_location_" + curScopeName, curItem.getDetailedLocation());
						addUniqueFieldValue(doc, "shelf_location_" + curScopeName, curItem.getShelfLocation());
					}
					if (curItem.isEContent() || curScope.isLocallyOwned() || curScope.isLibraryOwned() || curScopeDetails.getGroupedWorkDisplaySettings().isIncludeAllRecordsInDateAddedFacets()) {
						Long daysSinceAdded;
						if (curItem.isOrderItem() || (curItem.getStatusCode() != null && curItem.getStatusCode().equals("On Order"))) {
							daysSinceAdded = -1L;
						} else {
							//Date Added To Catalog needs to be the earliest date added for the catalog.
							Date dateAdded = curItem.getDateAdded();
							//See if we need to override based on publication date if not provided.
							//Should be set by individual driver though.
							if (dateAdded == null) {
								if (earliestPublicationDate != null) {
									//Return number of days since the given year
									Calendar publicationDate = GregorianCalendar.getInstance();
									//We don't know when in the year it is published, so assume January 1st which could be wrong
									publicationDate.set(earliestPublicationDate.intValue(), Calendar.JANUARY, 1);

									daysSinceAdded = DateUtils.getDaysSinceAddedForDate(publicationDate.getTime());
								} else {
									daysSinceAdded = Long.MAX_VALUE;
								}
							} else {
								daysSinceAdded = DateUtils.getDaysSinceAddedForDate(dateAdded);
							}
						}

						updateMaxValueField(doc, "local_days_since_added_" + curScopeName, daysSinceAdded);
					}

					if (curScope.isLocallyOwned() || curScope.isLibraryOwned()) {
						if (curScope.isAvailable()) {
							updateMaxValueField(doc, "lib_boost_" + curScopeName, GroupedWorkIndexer.availableAtBoostValue);
						} else {
							updateMaxValueField(doc, "lib_boost_" + curScopeName, GroupedWorkIndexer.ownedByBoostValue);
						}
					} else {
						//Make sure we have a minimum value so we don't multiply relevance by 0
						updateMaxValueField(doc, "lib_boost_" + curScopeName, 1);
					}

					addUniqueFieldValue(doc, "itype_" + curScopeName, StringUtils.trimTrailingPunctuation(curItem.getIType()));
					if (curItem.isEContent()) {
						addUniqueFieldValue(doc, "econtent_source_" + curScopeName, StringUtils.trimTrailingPunctuation(curItem.geteContentSource()));
					}
					if (curScope.isLocallyOwned() || curScope.isLibraryOwned() || !curScopeDetails.isRestrictOwningLibraryAndLocationFacets()) {
						addUniqueFieldValue(doc, "local_callnumber_" + curScopeName, curItem.getCallNumber());
						setSingleValuedFieldValue(doc, "callnumber_sort_" + curScopeName, curItem.getSortableCallNumber());
					}
				}
			}
		}
		logger.info("Work " + id + " processed " + numScopesForWork + " scopes");

		//Now that we know the latest number of days added for each scope, we can set the time since added facet
		for (Scope scope : groupedWorkIndexer.getScopes()) {
			SolrInputField field = doc.getField("local_days_since_added_" + scope.getScopeName());
			if (field != null) {
				Long daysSinceAdded = (Long) field.getFirstValue();
				doc.addField("local_time_since_added_" + scope.getScopeName(), DateUtils.getTimeSinceAdded(daysSinceAdded));
			}
		}
	}

	private void setupAvailabilityToggleAndOwnershipForItemWithinScope(SolrInputDocument doc, RecordInfo curRecord, ItemInfo curItem, String curScopeName, ScopingInfo curScope) {
		boolean addLocationOwnership = false;
		boolean addLibraryOwnership = false;
		HashSet<String> availabilityToggleValues = new HashSet<>();
		Scope curScopeDetails = curScope.getScope();availabilityToggleValues.add("global");

		if (curItem.isEContent()){
			//If the item is eContent, we will count it as part of the collection since it will be available.
			availabilityToggleValues.add("local");
			if (curScope.isAvailable()){
				if (curScopeDetails.getGroupedWorkDisplaySettings().isIncludeOnlineMaterialsInAvailableToggle()) {
					availabilityToggleValues.add("available");
				}
				availabilityToggleValues.add("available_online");
			}
			addLibraryOwnership = true;
			if (curScope.isLocallyOwned()) {
				addLocationOwnership = true;
			}
		}else{
			//Physical materials
			if (curScope.isLocallyOwned() && curScopeDetails.isLocationScope()) {
				addLocationOwnership = true;
				addLibraryOwnership = true;
				availabilityToggleValues.add("local");
				if (curScope.isAvailable()){
					availabilityToggleValues.add("available");
				}
			}
			if (curScope.isLibraryOwned()) {
				if (curScopeDetails.isLocationScope()) {
					if (!curScopeDetails.getGroupedWorkDisplaySettings().isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
						addLibraryOwnership = true;
						availabilityToggleValues.add("local");
						if (curScope.isAvailable()) {
							availabilityToggleValues.add("available");
						}
					}
				} else {
					addLibraryOwnership = true;
					availabilityToggleValues.add("local");
					if (curScope.isLibraryOwned() && curScope.isAvailable()) {
						availabilityToggleValues.add("available");
					}
				}
			}
		}

		HashMap<String, ScopingInfo> curScopingInfo = curItem.getScopingInfo();

		//Apply ownership and availability toggles
		if (addLocationOwnership) {

			//We do different ownership display depending on if this is eContent or not
			String owningLocationValue = curScopeDetails.getFacetLabel();
			if (curItem.isEContent()) {
				owningLocationValue = curItem.getShelfLocation();
			}

			//Save values for this scope
			addUniqueFieldValue(doc, "owning_location_" + curScopeName, owningLocationValue);

			if (curScope.isAvailable()) {
				addAvailableAtValues(doc, curRecord, curScopeName, owningLocationValue);
			}

			if (curScopeDetails.isLocationScope()) {
				//Also add the location to the system
				if (curScopeDetails.getLibraryScope() != null && !curScopeDetails.getLibraryScope().getScopeName().equals(curScopeName)) {
					addUniqueFieldValue(doc, "owning_location_" + curScopeDetails.getLibraryScope().getScopeName(), owningLocationValue);
					addAvailabilityToggleValues(doc, curRecord, curScopeDetails.getLibraryScope().getScopeName(), availabilityToggleValues);
					if (curScope.isAvailable()) {
						addAvailableAtValues(doc, curRecord, curScopeDetails.getLibraryScope().getScopeName(), owningLocationValue);
					}
				}

				//Add to other locations within the library if desired
				if (curScopeDetails.isIncludeAllLibraryBranchesInFacets()) {
					//Add to other locations in this library
					if (curScopeDetails.getLibraryScope() != null) {
						for (String otherScopeName : curScopingInfo.keySet()) {
							ScopingInfo otherScope = curScopingInfo.get(otherScopeName);
							if (!otherScope.equals(curScope)) {
								Scope otherScopeDetails = otherScope.getScope();
								if (otherScopeDetails.isLocationScope() && otherScopeDetails.getLibraryScope() != null && curScopeDetails.getLibraryScope().equals(otherScopeDetails.getLibraryScope())) {
									if (!otherScopeDetails.getGroupedWorkDisplaySettings().isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
										addAvailabilityToggleValues(doc, curRecord, otherScopeName, availabilityToggleValues);
									}
									addUniqueFieldValue(doc, "owning_location_" + otherScopeName, owningLocationValue);
									if (curScope.isAvailable()) {
										addAvailableAtValues(doc, curRecord, otherScopeName, owningLocationValue);
									}
								}
							}
						}
					}
				}

				//Add to other locations as desired
				for (String otherScopeName : curScopingInfo.keySet()) {
					ScopingInfo otherScope = curScopingInfo.get(otherScopeName);
					if (!otherScope.equals(curScope)) {
						Scope otherScopeDetails = otherScope.getScope();
						if (otherScopeDetails.getAdditionalLocationsToShowAvailabilityFor().length() > 0) {
							if (otherScopeDetails.getAdditionalLocationsToShowAvailabilityForPattern().matcher(curScopeName).matches()) {
								addAvailabilityToggleValues(doc, curRecord, otherScopeName, availabilityToggleValues);
								addUniqueFieldValue(doc, "owning_location_" + otherScopeName, owningLocationValue);
								if (curScope.isAvailable()) {
									addAvailableAtValues(doc, curRecord, otherScopeName, owningLocationValue);
								}
							}
						}
					}
				}

			}

			//finally add to any scopes where we show all owning locations
			for (String scopeToShowAllName : curScopingInfo.keySet()) {
				ScopingInfo scopeToShowAll = curScopingInfo.get(scopeToShowAllName);
				if (!scopeToShowAll.getScope().isRestrictOwningLibraryAndLocationFacets()) {
					if (!scopeToShowAll.getScope().getGroupedWorkDisplaySettings().isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
						addAvailabilityToggleValues(doc, curRecord, scopeToShowAll.getScope().getScopeName(), availabilityToggleValues);
					}
					addUniqueFieldValue(doc, "owning_location_" + scopeToShowAll.getScope().getScopeName(), owningLocationValue);
					if (curScope.isAvailable()) {
						addAvailableAtValues(doc, curRecord, scopeToShowAll.getScope().getScopeName(), owningLocationValue);
					}
				}
			}
		}
		if (addLibraryOwnership) {
			//We do different ownership display depending on if this is eContent or not
			String owningLibraryValue;
			if (curItem.isEContent()) {
				owningLibraryValue = curItem.getShelfLocation();
			}else{
				if (curScope.getScope().isLibraryScope()) {
					owningLibraryValue = curScopeDetails.getFacetLabel();
				} else {
					owningLibraryValue = curScopeDetails.getLibraryScope().getFacetLabel();
				}
			}
			addUniqueFieldValue(doc, "owning_library_" + curScopeName, owningLibraryValue);
			for (Scope locationScope : curScopeDetails.getLocationScopes()) {
				addUniqueFieldValue(doc, "owning_library_" + locationScope.getScopeName(), owningLibraryValue);
			}
			//finally add to any scopes where we show all owning libraries
			for (String scopeToShowAllName : curScopingInfo.keySet()) {
				ScopingInfo scopeToShowAll = curScopingInfo.get(scopeToShowAllName);
				if (!scopeToShowAll.getScope().isRestrictOwningLibraryAndLocationFacets()) {
					addUniqueFieldValue(doc, "owning_library_" + scopeToShowAll.getScope().getScopeName(), owningLibraryValue);
				}
			}
		}
		//Make sure we always add availability toggles to this scope even if they are blank
		addAvailabilityToggleValues(doc, curRecord, curScopeName, availabilityToggleValues);
	}

	private void addAvailableAtValues(SolrInputDocument doc, RecordInfo curRecord, String curScopeName, String owningLocationValue) {
		addUniqueFieldValue(doc, "available_at_" + curScopeName, owningLocationValue);
		for (String format : curRecord.getAllSolrFieldEscapedFormats()) {
			addUniqueFieldValue(doc, "available_at_by_format_" + curScopeName + "_" + format, owningLocationValue);
		}
		for (String formatCategory : curRecord.getAllSolrFieldEscapedFormatCategories()) {
			addUniqueFieldValue(doc, "available_at_by_format_" + curScopeName + "_" + formatCategory, owningLocationValue);
		}
	}

	private void addAvailabilityToggleValues(SolrInputDocument doc, RecordInfo curRecord, String curScopeName, HashSet<String> availabilityToggleValues) {
		addUniqueFieldValues(doc, "availability_toggle_" + curScopeName, availabilityToggleValues);
		HashSet<String> allFormats = curRecord.getAllSolrFieldEscapedFormats();
		for (String format : allFormats) {
			addUniqueFieldValues(doc, "availability_by_format_" + curScopeName + "_" + format, availabilityToggleValues);
		}
		HashSet<String> allFormatCategories = curRecord.getAllSolrFieldEscapedFormatCategories();
		if (allFormats.contains("eaudiobook")) {
			allFormatCategories.add("ebook");
		}
		if (allFormats.contains("vox_books")) {
			allFormatCategories.add("books");
			allFormatCategories.add("audio_books");
		}
		for (String formatCategory : allFormatCategories) {
			addUniqueFieldValues(doc, "availability_by_format_" + curScopeName + "_" + formatCategory, availabilityToggleValues);
		}
	}

	/**
	 * Update a field that can only contain a single value.  Ignores any subsequent after the first.
	 *
	 * @param doc       The document to be updated
	 * @param fieldName The field name to update
	 * @param value     The value to set if no value already exists
	 */
	private void setSingleValuedFieldValue(SolrInputDocument doc, String fieldName, String value) {
		Object curValue = doc.getFieldValue(fieldName);
		if (curValue == null) {
			doc.addField(fieldName, value);
		}
	}

	private void updateMaxValueField(SolrInputDocument doc, String fieldName, long value) {
		Object curValue = doc.getFieldValue(fieldName);
		if (curValue == null) {
			doc.addField(fieldName, value);
		} else {
			if ((Long) curValue < value) {
				doc.setField(fieldName, value);
			}
		}
	}

	private void updateMaxValueField(SolrInputDocument doc, String fieldName, int value) {
		Object curValue = doc.getFieldValue(fieldName);
		if (curValue == null) {
			doc.addField(fieldName, value);
		} else {
			if ((Integer) curValue < value) {
				doc.setField(fieldName, value);
			}
		}
	}

	private void addUniqueFieldValue(SolrInputDocument doc, String fieldName, String value) {
		if (value == null || value.length() == 0) return;
		Collection<Object> fieldValues = doc.getFieldValues(fieldName);
		if (fieldValues == null) {
			doc.addField(fieldName, value);
		} else if (!fieldValues.contains(value)) {
			fieldValues.add(value);
			doc.setField(fieldName, fieldValues);
		}
	}

	private void addUniqueFieldValues(SolrInputDocument doc, String fieldName, Collection<String> values) {
		if (values.size() == 0) return;
		for (String value : values) {
			addUniqueFieldValue(doc, fieldName, value);
		}
	}

	private boolean isLocallyOwned(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems) {
			if (curItem.isLocallyOwned(scope)) {
				return true;
			}
		}
		return false;
	}

	private boolean isLibraryOwned(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems) {
			if (curItem.isLibraryOwned(scope)) {
				return true;
			}
		}
		return false;
	}

	private void loadRelatedRecordsAndItemsForScope(Scope curScope, HashSet<RecordInfo> scopedRecords, HashSet<ItemInfo> scopedItems) {
		for (RecordInfo curRecord : relatedRecords.values()) {
			boolean recordIsValid = false;
			for (ItemInfo curItem : curRecord.getRelatedItems()) {
				if (curItem.isValidForScope(curScope)) {
					scopedItems.add(curItem);
					recordIsValid = true;
				}
			}
			if (recordIsValid) {
				scopedRecords.add(curRecord);
			}
		}
	}

	private void checkInconsistentLiteraryForms() {
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
				} else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0) {
					logger.debug("Popularity dictates that Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Non Fiction");
				} else if (numFictionIndicators.compareTo(numNonFictionIndicators) < 0) {
					logger.debug("Popularity dictates that Non Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Fiction");
				}
			}
		}
	}

	private void checkInconsistentLiteraryFormsFull() {
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

	private void checkDefaultValue(Set<String> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.contains(defaultValue) && valuesCollection.size() > 1) {
			valuesCollection.remove(defaultValue);
		} else if (valuesCollection.size() == 0) {
			valuesCollection.add(defaultValue);
		}
	}

	private void checkDefaultValue(Map<String, Integer> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.containsKey(defaultValue) && valuesCollection.size() > 1) {
			valuesCollection.remove(defaultValue);
		} else if (valuesCollection.size() == 0) {
			valuesCollection.put(defaultValue, 1);
		}
	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	private final static Pattern removeBracketsPattern = Pattern.compile("\\[.*?]");
	private final static Pattern commonSubtitlePattern = Pattern.compile("(?i)((?:[(])?(?:a )?graphic novel|audio cd|book club kit|large print(?:[)])?)$");
	private final static Pattern punctuationPattern = Pattern.compile("[.\\\\/()\\[\\]:;]");

	void setTitle(String shortTitle, String displayTitle, String sortableTitle, String recordFormat) {
		this.setTitle(shortTitle, displayTitle, sortableTitle, recordFormat, false);
	}

	void setTitle(String shortTitle, String displayTitle, String sortableTitle, String recordFormat, boolean forceUpdate) {
		if (shortTitle != null) {
			shortTitle = StringUtils.trimTrailingPunctuation(shortTitle);

			//Figure out if we want to use this title or if the one we have is better.
			boolean updateTitle = false;
			if (this.title == null) {
				updateTitle = true;
			} else {
				//Only overwrite if we get a better format
				if (recordFormat.equals("Book")) {
					//We have a book, update if we didn't have a book before
					if (!recordFormat.equals(titleFormat)) {
						updateTitle = true;
						//Or update if we had a book before and this title is longer
					} else if (shortTitle.length() > this.title.length()) {
						updateTitle = true;
					}
				} else if (recordFormat.equals("eBook")) {
					//Update if the format we had before is not a book
					if (!titleFormat.equals("Book")) {
						//And the new format was not an eBook or the new title is longer than what we had before
						if (!recordFormat.equals(titleFormat)) {
							updateTitle = true;
							//or update if we had a book before and this title is longer
						} else if (shortTitle.length() > this.title.length()) {
							updateTitle = true;
						}
					}
				} else if (!titleFormat.equals("Book") && !titleFormat.equals("eBook")) {
					//If we don't have a Book or an eBook then we can update the title if we get a longer title
					if (shortTitle.length() > this.title.length()) {
						updateTitle = true;
					}
				}
			}

			if (updateTitle || forceUpdate) {
				//Strip out anything in brackets unless that would cause us to show nothing
				String tmpTitle = removeBracketsPattern.matcher(shortTitle).replaceAll("").trim();
				if (shortTitle.length() > 0) {
					shortTitle = tmpTitle;
				}
				//Remove common formats
				tmpTitle = commonSubtitlePattern.matcher(shortTitle).replaceAll("").trim();
				if (tmpTitle.length() > 0) {
					shortTitle = tmpTitle;
				}
				this.title = shortTitle;
				this.titleFormat = recordFormat;
				//Strip out anything in brackets unless that would cause us to show nothing
				tmpTitle = removeBracketsPattern.matcher(sortableTitle).replaceAll("").trim();
				if (tmpTitle.length() > 0) {
					sortableTitle = tmpTitle;
				}
				//Remove common formats
				tmpTitle = commonSubtitlePattern.matcher(sortableTitle).replaceAll("").trim();
				if (tmpTitle.length() > 0) {
					sortableTitle = tmpTitle;
				}
				//remove punctuation from the sortable title
				sortableTitle = punctuationPattern.matcher(sortableTitle).replaceAll("");
				this.titleSort = sortableTitle.trim();
				displayTitle = StringUtils.trimTrailingPunctuation(displayTitle);
				//Strip out anything in brackets unless that would cause us to show nothing
				tmpTitle = removeBracketsPattern.matcher(displayTitle).replaceAll("").trim();
				if (tmpTitle.length() > 0) {
					displayTitle = tmpTitle;
				}
				//Remove common formats
				tmpTitle = commonSubtitlePattern.matcher(displayTitle).replaceAll("").trim();
				if (tmpTitle.length() > 0) {
					displayTitle = tmpTitle;
				}
				this.displayTitle = displayTitle.trim();
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


	void setSubTitle(String subTitle) {
		if (subTitle != null) {
			subTitle = StringUtils.trimTrailingPunctuation(subTitle);
			//TODO: determine if the subtitle should be changed?
			//Strip out anything in brackets unless that would cause us to show nothing
			String tmpTitle = removeBracketsPattern.matcher(subTitle).replaceAll("").trim();
			if (tmpTitle.length() > 0) {
				subTitle = tmpTitle;
			}
			//Remove common formats
			tmpTitle = commonSubtitlePattern.matcher(subTitle).replaceAll("").trim();
			if (tmpTitle.length() > 0) {
				subTitle = tmpTitle;
			}
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
			author = StringUtils.trimTrailingPunctuation(author);
			if (primaryAuthors.containsKey(author)) {
				primaryAuthors.put(author, primaryAuthors.get(author) + 1);
			} else {
				primaryAuthors.put(author, 1L);
			}
		}
	}

	private String getPrimaryAuthor() {
		String mostUsedAuthor = null;
		long numUses = -1;
		for (String curAuthor : primaryAuthors.keySet()) {
			if (primaryAuthors.get(curAuthor) > numUses) {
				mostUsedAuthor = curAuthor;
			}
		}
		return mostUsedAuthor;
	}

	void setAuthorDisplay(String newAuthor) {
		this.authorDisplay = StringUtils.trimTrailingPunctuation(newAuthor);
	}

	void setAuthAuthor(String author) {
		this.authAuthor = StringUtils.trimTrailingPunctuation(author);
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
		this.authAuthor2.addAll(StringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthor2(Set<String> fieldList) {
		this.author2.addAll(StringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthor2Role(Set<String> fieldList) {
		this.author2Role.addAll(StringUtils.trimTrailingPunctuation(fieldList));
	}

	void addAuthorAdditional(Set<String> fieldList) {
		this.authorAdditional.addAll(StringUtils.trimTrailingPunctuation(fieldList));
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
		this.topics.addAll(StringUtils.normalizeSubjects(fieldList));
	}

	void addTopic(String fieldValue) {
		this.topics.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addTopicFacet(Set<String> fieldList) {
		this.topicFacets.addAll(StringUtils.normalizeSubjects(fieldList));
	}

	void addTopicFacet(String fieldValue) {
		this.topicFacets.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addSubjects(Set<String> fieldList) {
		this.subjects.addAll(StringUtils.normalizeSubjects(fieldList));
	}

	void addSeries(Set<String> fieldList) {
		for (String curField : fieldList) {
			this.addSeries(curField);
		}
	}

//	void clearSeriesData(){
//		this.series.clear();
//		this.seriesWithVolume.clear();
//	}

	void addSeries(String series) {
		addSeriesInfoToField(series, this.series);
	}

	void clearSeries(){
		this.seriesWithVolume.clear();
		this.series2.putAll(this.series);
		this.series.clear();
	}

	void addSeriesWithVolume(String seriesName, String volume) {
		if (series != null) {
			String seriesInfo = getNormalizedSeries(seriesName);
			if (volume.length() > 0) {
				volume = getNormalizedSeriesVolume(volume);
			}
			String seriesInfoLower = seriesInfo.toLowerCase();
			String volumeLower = volume.toLowerCase();
			String seriesInfoWithVolume = seriesInfo + "|" + (volume.length() > 0 ? volume : "");
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
						if (existingVolume.length() == 0) {
							this.seriesWithVolume.remove(existingSeries2);
							break;
						} else {
							if (volumeLower.equals(existingVolume)) {
								okToAdd = false;
								break;
							} else if (volumeLower.length() == 0) {
								okToAdd = false;
								break;
							}
						}
					} else if (seriesInfoLower.contains(existingSeriesName)) {
						//Before removing the old series, make sure the new one has a volume
						if (existingVolume.length() > 0 && existingVolume.equals(volumeLower)) {
							this.seriesWithVolume.remove(existingSeries2);
							break;
						} else if (volume.length() == 0 && existingVolume.length() > 0) {
							okToAdd = false;
							break;
						} else if (volume.length() == 0) {
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
			String normalizedSeries = seriesInfo.toLowerCase();
			if (!seriesField.containsKey(normalizedSeries)) {
				boolean okToAdd = true;
				for (String existingSeries2 : seriesField.keySet()) {
					if (existingSeries2.contains(normalizedSeries)) {
						okToAdd = false;
						break;
					} else if (normalizedSeries.contains(existingSeries2)) {
						seriesField.remove(existingSeries2);
						break;
					}
				}
				if (okToAdd) {
					seriesField.put(normalizedSeries, seriesInfo);
				}
			}
		}
	}

	private String getNormalizedSeriesVolume(String volume) {
		volume = StringUtils.trimTrailingPunctuation(volume);
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
		volume = StringUtils.trimTrailingPunctuation(volume.trim());
		return volume;
	}

	private String getNormalizedSeries(String series) {
		series = StringUtils.trimTrailingPunctuation(series);
		series = series.replaceAll("[#|]\\s*\\d+$", "");

		//Remove anything in parentheses since it's normally just the format
		series = series.replaceAll("\\s+\\(.*?\\)", "");
		series = series.replaceAll(" & ", " and ");
		series = series.replaceAll("--", " ");
		series = series.replaceAll(",\\s+(the|an)$", "");
		series = series.replaceAll("[:,]\\s", " ");
		//Remove the word series at the end since this gets cataloged inconsistently
		series = series.replaceAll("(?i)\\s+series$", "");

		return StringUtils.trimTrailingPunctuation(series).trim();
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

	void addGenre(Set<String> fieldList) {
		this.genres.addAll(StringUtils.normalizeSubjects(fieldList));
	}

	void addGenre(String fieldValue) {
		this.genres.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addGenreFacet(Set<String> fieldList) {
		this.genreFacets.addAll(StringUtils.normalizeSubjects(fieldList));
	}

	void addGenreFacet(String fieldValue) {
		this.genreFacets.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addGeographic(String fieldValue) {
		this.geographic.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addGeographicFacet(String fieldValue) {
		this.geographicFacets.add(StringUtils.normalizeSubject(fieldValue));
	}

	void addEra(String fieldValue) {
		this.eras.add(StringUtils.normalizeSubject(fieldValue));
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
		if (publisher.length() > 0){
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

	void addLiteraryForms(HashSet<String> literaryForms) {
		for (String curLiteraryForm : literaryForms) {
			this.addLiteraryForm(curLiteraryForm);
		}
	}

	void addLiteraryForms(HashMap<String, Integer> literaryForms) {
		for (String curLiteraryForm : literaryForms.keySet()) {
			this.addLiteraryForm(curLiteraryForm, literaryForms.get(curLiteraryForm));
		}
	}

	private void addLiteraryForm(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryForm.containsKey(literaryForm)) {
			Integer numMatches = this.literaryForm.get(literaryForm);
			this.literaryForm.put(literaryForm, numMatches + count);
		} else {
			this.literaryForm.put(literaryForm, count);
		}
	}

	void addLiteraryForm(String literaryForm) {
		addLiteraryForm(literaryForm, 1);
	}

	void addLiteraryFormsFull(HashMap<String, Integer> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull.keySet()) {
			this.addLiteraryFormFull(curLiteraryForm, literaryFormsFull.get(curLiteraryForm));
		}
	}

	void addLiteraryFormsFull(HashSet<String> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull) {
			this.addLiteraryFormFull(curLiteraryForm);
		}
	}

	private void addLiteraryFormFull(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryFormFull.containsKey(literaryForm)) {
			Integer numMatches = this.literaryFormFull.get(literaryForm);
			this.literaryFormFull.put(literaryForm, numMatches + count);
		} else {
			this.literaryFormFull.put(literaryForm, count);
		}
	}

	void addLiteraryFormFull(String literaryForm) {
		this.addLiteraryFormFull(literaryForm, 1);
	}

	void addTargetAudiences(HashSet<String> target_audience) {
		targetAudience.addAll(target_audience);
	}

	void addTargetAudience(String target_audience) {
		targetAudience.add(target_audience);
	}

	void addTargetAudiencesFull(HashSet<String> target_audience_full) {
		targetAudienceFull.addAll(target_audience_full);
	}

	void addTargetAudienceFull(String target_audience) {
		targetAudienceFull.add(target_audience);
	}

	private Set<String> getRatingFacet(Float rating) {
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
		this.barcodes.addAll(barcodeList);
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
		if (this.fountasPinnell.length() == 0) {
			this.fountasPinnell = fountasPinnell;
		}
	}

	void addAwards(Set<String> awards) {
		this.awards.addAll(StringUtils.trimTrailingPunctuation(awards));
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

	void addDescription(String description, @NotNull String recordFormat) {
		if (description == null || description.length() == 0) {
			return;
		}
		this.description.add(description);
		boolean updateDescription = false;
		if (this.displayDescription == null) {
			updateDescription = true;
		} else {
			//Only overwrite if we get a better format
			if (recordFormat.equals("Book")) {
				//We have a book, update if we didn't have a book before
				if (!recordFormat.equals(displayDescriptionFormat)) {
					updateDescription = true;
					//or update if we had a book before and this Description is longer
				} else if (description.length() > this.displayDescription.length()) {
					updateDescription = true;
				}
			} else if (recordFormat.equals("eBook")) {
				//Update if the format we had before is not a book
				if (!displayDescriptionFormat.equals("Book")) {
					//And the new format was not an eBook or the new Description is longer than what we had before
					if (!recordFormat.equals(displayDescriptionFormat)) {
						updateDescription = true;
						//or update if we had a book before and this Description is longer
					} else if (description.length() > this.displayDescription.length()) {
						updateDescription = true;
					}
				}
			} else if (!displayDescriptionFormat.equals("Book") && !displayDescriptionFormat.equals("eBook")) {
				//If we don't have a Book or an eBook then we can update the Description if we get a longer Description
				if (description.length() > this.displayDescription.length()) {
					updateDescription = true;
				}
			}
		}
		if (updateDescription) {
			this.displayDescription = description;
			this.displayDescriptionFormat = recordFormat;
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
		this.lcSubjects.add(StringUtils.normalizeSubject(lcSubject));
	}

	void addBisacSubject(String bisacSubject) {
		this.bisacSubjects.add(StringUtils.normalizeSubject(bisacSubject));
	}

	void addSystemLists(Set<String> systemLists) {
		this.systemLists.addAll(systemLists);
	}

	void removeRelatedRecord(RecordInfo recordInfo) {
		this.relatedRecords.remove(recordInfo.getFullIdentifier());
	}

	void updateIndexingStats(TreeMap<String, ScopedIndexingStats> indexingStats) {
		//Update total works
		for (Scope scope : groupedWorkIndexer.getScopes()) {
			HashSet<RecordInfo> relatedRecordsForScope = new HashSet<>();
			HashSet<ItemInfo> relatedItems = new HashSet<>();
			loadRelatedRecordsAndItemsForScope(scope, relatedRecordsForScope, relatedItems);
			if (relatedRecordsForScope.size() > 0) {
				ScopedIndexingStats stats = indexingStats.get(scope.getScopeName());
				stats.numTotalWorks++;
				if (isLocallyOwned(relatedItems, scope) || isLibraryOwned(relatedItems, scope)) {
					stats.numLocalWorks++;
				}
			}
		}
		//Update stats based on individual record processor
		for (RecordInfo curRecord : relatedRecords.values()) {
			curRecord.updateIndexingStats(indexingStats);
		}
	}

	int getNumRecords() {
		return this.relatedRecords.size();
	}

	TreeSet<String> getTargetAudiences() {
		return targetAudience;
	}

	void addLanguage(@SuppressWarnings("SameParameterValue") String language) {
		this.languages.add(language);
	}

	/**
	 * Removes any hoopla records where the equivalent format exists in OverDrive or Rbdigital
	 */
	void removeRedundantHooplaRecords() {
		if (relatedRecords.size() > 1) {
			ArrayList<RecordInfo> hooplaRecordsAsArray = new ArrayList<>();
			ArrayList<RecordInfo> otherRecordsAsArray = new ArrayList<>();
			for (RecordInfo relatedRecord : relatedRecords.values()) {
				if (relatedRecord.getSource().equals("hoopla")) {
					hooplaRecordsAsArray.add(relatedRecord);
				} else if (relatedRecord.getSource().equals("overdrive") || relatedRecord.getSource().equals("rbdigital") || relatedRecord.getSource().equals("cloud_library")) {
					otherRecordsAsArray.add(relatedRecord);
				}
			}
			if (otherRecordsAsArray.size() == 0 || hooplaRecordsAsArray.size() == 0){
				return;
			}
			for (RecordInfo record1 : hooplaRecordsAsArray) {
				//This is a candidate for removal
				for (RecordInfo record2 : otherRecordsAsArray) {
					//Make sure we have the same format
					if (record1.getPrimaryFormat().equals(record2.getPrimaryFormat()) && record1.getPrimaryLanguage().equals(record2.getPrimaryLanguage())) {
						//Remove the hoopla record from any scope where there is an available replacement
						for (ItemInfo curItem2 : record2.getRelatedItems()){
							for (String curScopeName2 : curItem2.getScopingInfo().keySet()){
								ScopingInfo curScope2 = curItem2.getScopingInfo().get(curScopeName2);
								if (curScope2.isAvailable()){
									for (ItemInfo curItem1 : record1.getRelatedItems()){
										curItem1.getScopingInfo().remove(curScope2.getScope().getScopeName());
									}
									boolean changeMade = true;
									while (changeMade){
										changeMade = false;
										for (ItemInfo curItem1 : record1.getRelatedItems()){
											if (curItem1.getScopingInfo().size() == 0){
												record1.getRelatedItems().remove(curItem1);
												changeMade = true;
												break;
											}
										}
									}
									if (record1.getRelatedItems().size() == 0){
										relatedRecords.remove(record1.getFullIdentifier());
									}
								}
							}
						}
					}
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

}
