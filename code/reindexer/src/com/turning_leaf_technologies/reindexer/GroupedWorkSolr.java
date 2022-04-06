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
	private long totalHolds;

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

	//Store a list of scopes for the work
	private HashMap<String, ArrayList<ScopingInfo>> relatedScopes = new HashMap<>();

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
		// noinspection unchecked
		clonedWork.relatedScopes = (HashMap<String, ArrayList<ScopingInfo>>) relatedScopes.clone();

		return clonedWork;
	}

	SolrInputDocument getSolrDocument(BaseLogEntry logEntry) {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("last_indexed", new Date());
		doc.addField("alternate_ids", alternateIds);
		doc.addField("recordtype", "grouped_work");
		try {
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
			if (languages.size() > 1 || groupedWorkIndexer.getTreatUnknownLanguageAs().length() != 0) {
				languages.remove("Unknown");
			}
			if (languages.size() == 0) {
				languages.add(groupedWorkIndexer.getTreatUnknownLanguageAs());
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
			checkDefaultValue(literaryFormFull, "Unknown");
			checkInconsistentLiteraryFormsFull();
			doc.addField("literary_form_full", literaryFormFull.keySet());
			checkDefaultValue(literaryForm, "Not Coded");
			checkDefaultValue(literaryForm, "Other");
			checkDefaultValue(literaryForm, "Unknown");
			checkInconsistentLiteraryForms();
			doc.addField("literary_form", literaryForm.keySet());
			if (targetAudienceFull.size() > 1 || !groupedWorkIndexer.isTreatUnknownAudienceAsUnknown()) {
				targetAudienceFull.remove("Unknown");
			}
			if (targetAudienceFull.size() > 1) {
				targetAudienceFull.remove("No Attempt To Code");
				targetAudienceFull.remove("Other");
			}
			if (targetAudienceFull.size() == 0) {
				targetAudienceFull.add(groupedWorkIndexer.getTreatUnknownAudienceAs());
			}
			doc.addField("target_audience_full", targetAudienceFull);
			if (targetAudience.size() > 1 || !groupedWorkIndexer.isTreatUnknownAudienceAsUnknown()) {
				targetAudience.remove("Unknown");
			}
			if (targetAudience.size() > 1) {
				targetAudience.remove("Other");
			}
			if (targetAudience.size() == 0) {
				targetAudience.add(groupedWorkIndexer.getTreatUnknownAudienceAs());
			}
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
			doc.addField("total_holds", Long.toString(totalHolds));
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
		}catch (Exception e){
			logEntry.incErrors("Error creating solr document for grouped work " + id, e);
		}
		try{
			//Save information from scopes
			addScopedFieldsToDocument(doc, logEntry);
		}catch (Exception e){
			logEntry.incErrors("Error adding scoped fields to grouped work " + id, e);
		}

		return doc;
	}

	public void addScopingInfo(String scopeName, ScopingInfo scopingInfo){
		ArrayList<ScopingInfo> scopingInfoForScope = relatedScopes.computeIfAbsent(scopeName, k -> new ArrayList<>());
		scopingInfoForScope.add(scopingInfo);
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
		try {
			if (groupedWorkIndexer.isStoreRecordDetailsInSolr()) {
				for (RecordInfo curRecord : relatedRecords.values()) {
					doc.addField("record_details", curRecord.getDetails());
					for (ItemInfo curItem : curRecord.getRelatedItems()) {
						doc.addField("item_details", curItem.getDetails(logEntry));
					}
				}
			}
		}catch (Exception e){
			logEntry.incErrors("Error setting up record details and item details for " + id, e);
		}


		doc.setField("scope_has_related_records", relatedScopes.keySet());
		for (String scopeName : relatedScopes.keySet()){
			try{
				HashSet<String> scopingDetailsForScope = new HashSet<>();
				HashSet<String> formatsForScope = new HashSet<>();
				HashSet<String> formatCategoriesForScope = new HashSet<>();
				HashSet<String> collectionsForScope = new HashSet<>();
				HashSet<String> detailedLocationsForScope = new HashSet<>();
				HashSet<String> shelfLocationsForScope = new HashSet<>();
				HashSet<String> iTypesForScope = new HashSet<>();
				HashSet<String> eContentSourcesForScope = new HashSet<>();
				HashSet<String> localCallNumbersForScope = new HashSet<>();
				HashSet<String> owningLibrariesForScope = new HashSet<>();
				HashSet<String> owningLocationsForScope = new HashSet<>();
				AvailabilityToggleInfo availabilityToggleForScope = new AvailabilityToggleInfo();
				HashMap<String, AvailabilityToggleInfo> availabilityToggleByFormatForScope = new HashMap<>();
				HashSet<String> availableAtForScope = new HashSet<>();
				HashMap<String, HashSet<String>> availableAtByFormatForScope = new HashMap<>();

				String sortableCallNumberForScope = null;
				Long daysSinceAddedForScope = null;
				long libBoost = 1;


				ArrayList<ScopingInfo> itemsWithScopingInfoForActiveScope = relatedScopes.get(scopeName);
				for (ScopingInfo scopingInfo : itemsWithScopingInfoForActiveScope) {
					Scope curScope = scopingInfo.getScope();
					if (groupedWorkIndexer.isStoreRecordDetailsInSolr()) {
						scopingDetailsForScope.add(scopingInfo.getScopingDetails());
					}

					HashSet<String> formatsForItem = new HashSet<>();

					ItemInfo curItem = scopingInfo.getItem();
					if (curItem.getFormat() != null) {
						formatsForScope.add(curItem.getFormat());
						formatsForItem.add(curItem.getFormat());
						if (!availabilityToggleByFormatForScope.containsKey(curItem.getFormat())){
							availabilityToggleByFormatForScope.put(curItem.getFormat(), new AvailabilityToggleInfo());
							availableAtByFormatForScope.put(curItem.getFormat(), new HashSet<>());
						}
					} else {
						formatsForItem.addAll(curItem.getRecordInfo().getFormats());
						for (String format : curItem.getRecordInfo().getFormats()){
							formatsForScope.add(format);
							if (!availabilityToggleByFormatForScope.containsKey(format)){
								availabilityToggleByFormatForScope.put(format, new AvailabilityToggleInfo());
								availableAtByFormatForScope.put(format, new HashSet<>());
							}
						}
					}
					if (curItem.getFormatCategory() != null) {
						formatCategoriesForScope.add(curItem.getFormatCategory());
						formatsForItem.add(curItem.getFormatCategory());
						if (!availabilityToggleByFormatForScope.containsKey(curItem.getFormatCategory())){
							availabilityToggleByFormatForScope.put(curItem.getFormatCategory(), new AvailabilityToggleInfo());
							availableAtByFormatForScope.put(curItem.getFormatCategory(), new HashSet<>());
						}
					} else {
						formatCategoriesForScope.addAll(curItem.getRecordInfo().getFormatCategories());
						formatsForItem.addAll(curItem.getRecordInfo().getFormatCategories());
						for (String format : curItem.getRecordInfo().getFormatCategories()){
							if (!availabilityToggleByFormatForScope.containsKey(format)){
								availabilityToggleByFormatForScope.put(format, new AvailabilityToggleInfo());
								availableAtByFormatForScope.put(format, new HashSet<>());
							}
						}
					}

					Long daysSinceAdded;
					if (curItem.isOrderItem() || (curItem.getStatusCode() != null && (curItem.getStatusCode().equals("On Order") || curItem.getStatusCode().equals("Coming Soon")))) {
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

					if (curItem.isEContent()){
						addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(),curScope.getGroupedWorkDisplaySettings().isIncludeOnlineMaterialsInAvailableToggle() && curItem.isAvailable(), curItem.isAvailable(), availabilityToggleForScope, availabilityToggleByFormatForScope, formatsForItem);
						owningLibrariesForScope.add(curItem.getTrimmedEContentSource());
						if (curItem.isAvailable()){
							addAvailableAt(curItem.getTrimmedEContentSource(), availableAtForScope, availableAtByFormatForScope, formatsForItem);
						}
					}else{ //physical materials
						if (scopingInfo.isLocallyOwned()) {
							addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(),curItem.isAvailable(), false, availabilityToggleForScope, availabilityToggleByFormatForScope, formatsForItem);
							if (curItem.isAvailable()){
								addAvailableAt(curScope.getFacetLabel(), availableAtForScope, availableAtByFormatForScope, formatsForItem);
							}
							//For physical materials, only locally owned means it is a location/branch scope and that branch owns it
							owningLocationsForScope.add(curScope.getFacetLabel());
							//This can be a library scope if it is both a library and location scope
							owningLibrariesForScope.add(curScope.isLibraryScope() ? curScope.getFacetLabel() : curScope.getLibraryScope().getFacetLabel());

							if (curScope.isIncludeAllLibraryBranchesInFacets()){
								//Include other branches of this library that own the title within the owning locations
								//isIncludeAllLibraryBranchesInFacets is only a setting at the location level
								owningLocationsForScope.addAll(curItem.getLocationOwnedNames());
							}
						}
						if (scopingInfo.isLibraryOwned()){
							addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(),curItem.isAvailable(), false, availabilityToggleForScope, availabilityToggleByFormatForScope, formatsForItem);
							if (curItem.isAvailable()){
								for (String owningName : curItem.getLocationOwnedNames()) {
									addAvailableAt(owningName, availableAtForScope, availableAtByFormatForScope, formatsForItem);
								}
							}
							owningLibrariesForScope.add(curScope.getFacetLabel());
							//For owning locations, add all locations within the library that own it
							owningLocationsForScope.addAll(curItem.getLocationOwnedNames());
						}
						//If it is not library or location owned, we might still add to the availability toggles
						if (!scopingInfo.isLocallyOwned() && !scopingInfo.isLibraryOwned() && !curScope.getGroupedWorkDisplaySettings().isBaseAvailabilityToggleOnLocalHoldingsOnly()){
							addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(), curItem.isAvailable(), false, availabilityToggleForScope, availabilityToggleByFormatForScope, formatsForItem);
							if (curItem.isAvailable()){
								for (String owningName : curItem.getLocationOwnedNames()) {
									addAvailableAt(owningName, availableAtForScope, availableAtByFormatForScope, formatsForItem);
								}
							}
						}
						if (curItem.isAvailable() && curScope.getAdditionalLocationsToShowAvailabilityForPattern() != null && curItem.getLocationCode() != null){
							//We might include the item in the owning and availability facets if it matched the available locations
							if (curScope.getAdditionalLocationsToShowAvailabilityForPattern().matcher(curItem.getLocationCode()).matches()){
								for (String owningName : curItem.getLocationOwnedNames()) {
									addAvailableAt(owningName, availableAtForScope, availableAtByFormatForScope, formatsForItem);
								}
							}
						}

						if (!curScope.isRestrictOwningLibraryAndLocationFacets() || curScope.isConsortialCatalog()){
							owningLibrariesForScope.addAll(curItem.getLibraryOwnedNames());
							owningLocationsForScope.addAll(curItem.getLocationOwnedNames());
						}
					}

					if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || scopingInfo.getScope().getGroupedWorkDisplaySettings().isIncludeAllRecordsInShelvingFacets()) {
						collectionsForScope.add(curItem.getCollection());
						detailedLocationsForScope.add(curItem.getDetailedLocation());
						shelfLocationsForScope.add(curItem.getShelfLocation());

					}
					if (curItem.isEContent() || scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || scopingInfo.getScope().getGroupedWorkDisplaySettings().isIncludeAllRecordsInDateAddedFacets()) {
						if (daysSinceAddedForScope == null || daysSinceAdded > daysSinceAddedForScope){
							daysSinceAddedForScope = daysSinceAdded;
						}
					}

					if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned()) {
						if (curItem.isAvailable()) {
							if (libBoost < GroupedWorkIndexer.availableAtBoostValue){
								libBoost = GroupedWorkIndexer.availableAtBoostValue;
							}
						} else {
							if (libBoost < GroupedWorkIndexer.ownedByBoostValue){
								libBoost = GroupedWorkIndexer.ownedByBoostValue;
							}
						}
					}

					iTypesForScope.add(curItem.getTrimmedIType());

					if (curItem.isEContent()) {
						eContentSourcesForScope.add(curItem.getTrimmedEContentSource());
					}
					if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || !scopingInfo.getScope().isRestrictOwningLibraryAndLocationFacets()) {
						localCallNumbersForScope.add(curItem.getCallNumber());
						if (sortableCallNumberForScope == null) {
							sortableCallNumberForScope = curItem.getSortableCallNumber();
						}
					}
				}

				//eAudiobooks are considered both Audiobooks and eBooks by some people
				if (formatsForScope.contains("eAudiobook")) {
					formatCategoriesForScope.add("eBook");
				}
				if (formatsForScope.contains("CD + Book")) {
					formatCategoriesForScope.add("Books");
					formatCategoriesForScope.add("Audio Books");
				}
				if (formatsForScope.contains("VOX Books")) {
					formatCategoriesForScope.add("Books");
					formatCategoriesForScope.add("Audio Books");
				}
				doc.addField("scoping_details_".concat(scopeName), scopingDetailsForScope);
				doc.addField("format_".concat(scopeName), formatsForScope);
				doc.addField("format_category_".concat(scopeName), formatCategoriesForScope);
				doc.addField("collection_".concat(scopeName), collectionsForScope);
				doc.addField("detailed_location_".concat(scopeName), detailedLocationsForScope);
				doc.addField("shelf_location_".concat(scopeName), shelfLocationsForScope);
				if (daysSinceAddedForScope != null){
					doc.addField("local_days_since_added_".concat(scopeName), daysSinceAddedForScope);
				}
				doc.addField("lib_boost_".concat(scopeName), libBoost);
				doc.addField("itype_".concat(scopeName), iTypesForScope);
				doc.addField("local_callnumber_".concat(scopeName), localCallNumbersForScope);
				doc.addField("callnumber_sort_".concat(scopeName), sortableCallNumberForScope);
				doc.addField("econtent_source_".concat(scopeName), eContentSourcesForScope);

				doc.addField("owning_library_".concat(scopeName), owningLibrariesForScope);
				doc.addField("owning_location_".concat(scopeName), owningLocationsForScope);
				doc.addField("availability_toggle_".concat(scopeName), availabilityToggleForScope.getValues());
				for (String format : availabilityToggleByFormatForScope.keySet()){
					doc.addField("availability_by_format_".concat(scopeName).concat("_").concat(toLowerCaseNoSpecialChars(format)), availabilityToggleByFormatForScope.get(format).getValues());
				}
				doc.addField("available_at_".concat(scopeName), availableAtForScope);
				for (String format : availableAtByFormatForScope.keySet()){
					if (availableAtByFormatForScope.get(format).size() != 0) {
						doc.addField("available_at_by_format_".concat(scopeName).concat("_").concat(toLowerCaseNoSpecialChars(format)), availableAtByFormatForScope.get(format));
					}
				}

				SolrInputField field = doc.getField("local_days_since_added_".concat(scopeName));
				if (field != null) {
					Long daysSinceAdded = (Long) field.getFirstValue();
					doc.addField("local_time_since_added_".concat(scopeName), DateUtils.getTimeSinceAdded(daysSinceAdded));
				}
			} catch (Exception e){
				logEntry.incErrors("Error setting up scope information for " + id + " scope " + scopeName);
			}
		}

		logger.info("Work " + id + " processed " + relatedScopes.size() + " scopes");
	}

	private final static HashMap<String, String> lowerCaseNoSpecialCharFormats = new HashMap<>();
	public String toLowerCaseNoSpecialChars(String format){
		String lowerCaseNoSpecialCharFormat = lowerCaseNoSpecialCharFormats.get(format);
		if (lowerCaseNoSpecialCharFormat == null){
			lowerCaseNoSpecialCharFormat = StringUtils.toLowerCaseNoSpecialChars(format);
			lowerCaseNoSpecialCharFormats.put(format, lowerCaseNoSpecialCharFormat);
		}
		return lowerCaseNoSpecialCharFormat;
	}

	private void addAvailabilityToggle(boolean local, boolean available, boolean availableOnline, AvailabilityToggleInfo availabilityToggleForScope, HashMap<String, AvailabilityToggleInfo> availabilityToggleByFormatForScope,  HashSet<String> formatsForItem){
		availabilityToggleForScope.local = availabilityToggleForScope.local || local;
		availabilityToggleForScope.available = availabilityToggleForScope.available || available;
		availabilityToggleForScope.availableOnline = availabilityToggleForScope.availableOnline || availableOnline;
		for (String format : formatsForItem){
			AvailabilityToggleInfo formatAvailability = availabilityToggleByFormatForScope.get(format);
			formatAvailability.local = true;
			formatAvailability.available = availabilityToggleForScope.available || available;
			formatAvailability.availableOnline = availabilityToggleForScope.availableOnline || availableOnline;
		}
	}

	private void addAvailableAt(String location, HashSet<String> availableAtForScope, HashMap<String, HashSet<String>> availableAtByFormatForScope,  HashSet<String> formatsForItem){
		availableAtForScope.add(location);
		for (String format : formatsForItem){
			availableAtByFormatForScope.get(format).add(location);
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
	@SuppressWarnings("RegExpUnnecessaryNonCapturingGroup")
	private final static Pattern commonSubtitlePattern = Pattern.compile("(?i)((?:[(])?(?:a )?graphic novel|audio cd|book club kit|large print(?:[)])?)$");
	private final static Pattern punctuationPattern = Pattern.compile("[.\\\\/()\\[\\]:;]");

	void setTitle(String shortTitle, String subTitle, String displayTitle, String sortableTitle, String recordFormat, String formatCategory) {
		this.setTitle(shortTitle, subTitle, displayTitle, sortableTitle, recordFormat, formatCategory, false);
	}

	void setTitle(String shortTitle, String subTitle, String displayTitle, String sortableTitle, String recordFormat, String formatCategory, boolean forceUpdate) {
		if (shortTitle != null) {
			shortTitle = StringUtils.trimTrailingPunctuation(shortTitle);

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
				this.titleFormat = formatCategory;
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

				//SubTitle only gets set based on the main title.
				if (subTitle == null){
					if (this.subTitle != null) {
						//clear the subtitle if it was set by a previous record.
						this.subTitle = null;
					}
				}else {
					setSubTitle(subTitle);
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
			subTitle = StringUtils.trimTrailingPunctuation(subTitle);
			//TODO: determine if the subtitle should be changed?
			//Strip out anything in brackets unless that would cause us to show nothing
			String tmpTitle = removeBracketsPattern.matcher(subTitle).replaceAll("").trim();
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
		series = series.replaceAll("\\s+\\(+.*?\\)+", "");
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
				if (targetAudience.size() == 0){
					targetAudience.add(target_audience);
					targetAudiencesAsString = null;
				}
				break;
			default:
				if (!targetAudience.contains(target_audience)) {
					if (targetAudience.contains("Unknown")) {
						targetAudience.remove("Unknown");
					} else //noinspection RedundantCollectionOperation
						if (targetAudience.contains("Other")) {
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
				if (targetAudienceFull.size() == 0){
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
		for (String barcode: barcodeList){
			if (barcode.length() > 0){
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

	void addDescription(String description, @NotNull String recordFormat, String formatCategory) {
		if (description == null || description.length() == 0) {
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
		this.lcSubjects.add(StringUtils.normalizeSubject(lcSubject));
	}

	void addBisacSubject(String bisacSubject) {
		this.bisacSubjects.add(StringUtils.normalizeSubject(bisacSubject));
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

	void addLanguage(@SuppressWarnings("SameParameterValue") String language) {
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
				} else if (relatedRecord.getSource().equals("overdrive") || relatedRecord.getSource().equals("axis36") || relatedRecord.getSource().equals("cloud_library")) {
					otherRecordsAsArray.add(relatedRecord);
				}
			}
			if (otherRecordsAsArray.size() == 0 || hooplaRecordsAsArray.size() == 0){
				return;
			}
			// record 1 is a hoopla record
			// record 2 is not a hoopla record.

			for (RecordInfo record1 : hooplaRecordsAsArray) {
				//This is a candidate for removal
				for (RecordInfo record2 : otherRecordsAsArray) {
					//Make sure we have the same format
					if (record1.getPrimaryFormat().equals(record2.getPrimaryFormat()) && record1.getPrimaryLanguage().equals(record2.getPrimaryLanguage())) {

						//Loop through all the scopes to see if we should remove the hoopla record from that scope.
						for (ItemInfo curItem1 : record1.getRelatedItems()){
							HashSet<String> scopesToRemove = new HashSet<>();
							for (ScopingInfo item1Scope : curItem1.getScopingInfo().values()) {
								String item1ScopeName = item1Scope.getScope().getScopeName();
								//Get information about the scope so we can determine how this scope should be processed.
								boolean removeScope = false;
								switch (item1Scope.getScope().getHooplaScope().getExcludeTitlesWithCopiesFromOtherVendors()) {
									case 0:
										//Don't remove items that have the same record someplace else
										break;
									case 1:
										//Remove if there is an available copy for the scope
										for (ItemInfo curItem2 : record2.getRelatedItems()){
											if (curItem2.getScopingInfo().containsKey(item1ScopeName)){
												if (curItem2.isAvailable()){
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
							}

							//Remove the item entirely if it is no longer valid for any scope
							if (curItem1.getScopingInfo().size() == 0){
								record1.getRelatedItems().remove(curItem1);
								break;
							}
						}
					}
				}

				//Remove the record entirely if it has no related items
				if (record1.getRelatedItems().size() == 0){
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
		groupedWorkIndexer.disableAutoCommit();
		//Get a list of all existing records for the grouped work
		HashMap<String, SavedRecordInfo> existingRecords = groupedWorkIndexer.getExistingRecordsForGroupedWork(groupedWorkId);
		HashMap<VariationInfo, Long> existingVariations = groupedWorkIndexer.getExistingVariationsForGroupedWork(groupedWorkId);
		HashSet<Long> foundVariations = new HashSet<>();
		//Save all the records
		for (RecordInfo recordInfo : relatedRecords.values()){
			String relatedRecordKey = groupedWorkIndexer.getSourceId(recordInfo.getSource(), recordInfo.getSubSource()) + ":" + recordInfo.getRecordIdentifier();
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
		groupedWorkIndexer.enableAutoCommit();
	}

	public void addHolds(int numHolds) {
		this.totalHolds += numHolds;
	}

	private String targetAudiencesAsString = null;
	public String getTargetAudiencesAsString() {
		if (targetAudiencesAsString == null) {
			if (targetAudience.size() == 0) {
				targetAudiencesAsString = "";
			} else if (targetAudience.size() == 1) {
				targetAudiencesAsString = targetAudience.first();
			} else {
				targetAudiencesAsString = targetAudience.toString();
			}
		}
		return targetAudiencesAsString;
	}
}
