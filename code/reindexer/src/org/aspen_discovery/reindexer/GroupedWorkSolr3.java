package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.commons.lang3.StringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;

import java.util.*;

/**
 * Represents a grouped work withing Solr
 *
 * Compatible with the grouped_work_v3 schema
 *
 * Utilizes nested documents for better faceting
 */
public class GroupedWorkSolr3 extends AbstractGroupedWorkSolr implements Cloneable {
	public GroupedWorkSolr3(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		super(groupedWorkIndexer, logger);
	}

	public GroupedWorkSolr3 clone() throws CloneNotSupportedException {
		GroupedWorkSolr3 clonedWork = (GroupedWorkSolr3) super.clone();
		super.cloneCollectionData(clonedWork);

		return clonedWork;
	}

	/**
	 * Create a solr document to store the majority of the grouped work data.
	 * There is also a child document that contains information about the scoped records which allows filtering by
	 *
	 *
	 * @param logEntry
	 * @return
	 */
	@SuppressWarnings("DuplicatedCode")
	SolrInputDocument getSolrDocument(BaseIndexingLogEntry logEntry) {
		SolrInputDocument groupedWorkDoc = new SolrInputDocument();
		//Main identification

		groupedWorkDoc.addField("id", id);
		groupedWorkDoc.addField("last_indexed", new Date());
		groupedWorkDoc.addField("alternate_ids", alternateIds);
		groupedWorkDoc.addField("recordtype", "grouped_work");
		try {
			//Title and variations
			String fullTitle = title;
			if (subTitle != null) {
				fullTitle += " " + subTitle;
			}
			groupedWorkDoc.addField("title", fullTitle);
			groupedWorkDoc.addField("title_display", displayTitle);
			//This is set lower now with additional titles added with formats
			//doc.addField("title_full", fullTitles);
			HashSet<String> startOfTitle = new HashSet<>();
			startOfTitle.add(fullTitle);
			String sortableTitle = AspenStringUtils.makeValueSortable(fullTitle);
			startOfTitle.add(sortableTitle);
			startOfTitle.add(titleSort);
			groupedWorkDoc.addField("title_left", startOfTitle);

			groupedWorkDoc.addField("subtitle_display", subTitle);
			groupedWorkDoc.addField("title_short", title);
			groupedWorkDoc.addField("title_sort", titleSort);
			groupedWorkDoc.addField("title_alt", titleAlt);
			groupedWorkDoc.addField("title_new", titleNew);

			//author and variations
			String primaryAuthor = getPrimaryAuthor();
			groupedWorkDoc.addField("auth_author", authAuthor);
			groupedWorkDoc.addField("author", primaryAuthor);
			if (primaryAuthor != null && !primaryAuthor.isEmpty()){ //skip if empty so titles with no author are sorted last
				primaryAuthor = primaryAuthor.toLowerCase();
				groupedWorkDoc.addField("author_sort", primaryAuthor);
			}

			groupedWorkDoc.addField("auth_author2", authAuthor2);
			groupedWorkDoc.addField("author2", author2);
			groupedWorkDoc.addField("author2-role", author2Role);
			groupedWorkDoc.addField("author_additional", authorAdditional);
			groupedWorkDoc.addField("author_display", authorDisplay);

			//title auth
			HashSet<String> titleAuthors = new HashSet<>();
			titleAuthors.add(fullTitle + " " + getPrimaryAuthor());
			groupedWorkDoc.addField("title_author", titleAuthors);

			//format related items at grouped work level
			groupedWorkDoc.addField("grouping_category", groupingCategory);
			groupedWorkDoc.addField("format_boost", getTotalFormatBoost());

			//language related fields
			//Check to see if we have Unknown plus a valid value
			if (languages.size() > 1 || !groupedWorkIndexer.getTreatUnknownLanguageAs().isEmpty()) {
				languages.remove("Unknown");
			}
			if (languages.isEmpty()) {
				languages.add(groupedWorkIndexer.getTreatUnknownLanguageAs());
			}
			groupedWorkDoc.addField("all_languages", languages);
			groupedWorkDoc.addField("language", primaryLanguage);
			groupedWorkDoc.addField("translation", translations);
			groupedWorkDoc.addField("language_boost", languageBoost);
			groupedWorkDoc.addField("language_boost_es", languageBoostSpanish);
			//Publication related fields
			groupedWorkDoc.addField("publisher", publishers);
			groupedWorkDoc.addField("publishDate", publicationDates);
			groupedWorkDoc.addField("placeOfPublication", placesOfPublication);
			//Sorting will use the earliest date published
			groupedWorkDoc.addField("publishDateSort", earliestPublicationDate);

			//faceting and refined searching
			groupedWorkDoc.addField("physical", physicals);
			groupedWorkDoc.addField("duration", durations);
			groupedWorkDoc.addField("edition", editions);

			//Series fields
			SeriesInfo[] sortedSeriesWithVolume = series.values().stream()
				.sorted(Comparator.comparingInt(SeriesInfo::getPriorityScore).reversed())
				.toArray(SeriesInfo[]::new);
			if (isDebugEnabled() && !series.isEmpty()) {
				addDebugMessage("Series Priority Values", 1);
			}
			boolean isFirstSeries = true;
			for (SeriesInfo seriesInfo : sortedSeriesWithVolume) {
				if (isDebugEnabled()) {
					addDebugMessage(seriesInfo.getSeriesName() + " priority: " + seriesInfo.getVolumes(), 2);
				}
				groupedWorkDoc.addField("series", seriesInfo.getSeriesName());

				for (String volume : seriesInfo.getVolumes()) {
					groupedWorkDoc.addField("series_with_volume", seriesInfo.getSeriesName() + "|" + volume);
				}
				if (isFirstSeries) {
					groupedWorkDoc.addField("series_author", seriesInfo.getSeriesName() + " " + getPrimaryAuthor());
					isFirstSeries = false;
				}
			}

			//Subject related fields
			groupedWorkDoc.addField("topic", topics);
			topicFacets.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("topic_facet", topicFacets);
			subjects.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("subject_facet", subjects);
			groupedWorkDoc.addField("lc_subject", lcSubjects);
			groupedWorkDoc.addField("bisac_subject", bisacSubjects);
			groupedWorkDoc.addField("genre", genres);
			genreFacets.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("genre_facet", genreFacets);
			groupedWorkDoc.addField("geographic", geographic);
			geographicFacets.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("geographic_facet", geographicFacets);
			personalNameSubjects.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("personal_name_facet", personalNameSubjects);
			corporateNameSubjects.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("corporate_name_facet", corporateNameSubjects);
			eras.removeAll(groupedWorkIndexer.hideSubjects);
			groupedWorkDoc.addField("era", eras);

			//Literary Forms
			checkDefaultValue(literaryFormFull, "Not Coded");
			checkDefaultValue(literaryFormFull, "Other");
			checkDefaultValue(literaryFormFull, "Unknown");
			if (this.isDebugEnabled()) {this.addDebugMessage("Total full literary form score: " + this.literaryFormFull, 1);}
			checkInconsistentLiteraryFormsFull();
			checkDefaultValue(literaryForm, "Not Coded");
			checkDefaultValue(literaryForm, "Other");
			checkDefaultValue(literaryForm, "Unknown");
			if (this.isDebugEnabled()) {this.addDebugMessage("Total fiction vs non fiction score: " + this.literaryForm, 1);}
			checkInconsistentLiteraryForms();
			//Check if .isHide
			if (groupedWorkIndexer.isHideUnknownLiteraryForm()) {
				literaryForm.remove("Unknown");
				literaryFormFull.remove("Unknown");
			}
			if (groupedWorkIndexer.isHideNotCodedLiteraryForm()) {
				literaryForm.remove("Not Coded");
				literaryFormFull.remove("Not Coded");
			}
			//Add field
			if (this.debugEnabled) {this.addDebugMessage("Literary form is " + literaryForm.keySet(), 2);}
			if (this.debugEnabled) {this.addDebugMessage("Full literary form is " + literaryFormFull.keySet(), 2);}
			groupedWorkDoc.addField("literary_form_full", literaryFormFull.keySet());
			groupedWorkDoc.addField("literary_form", literaryForm.keySet());

			//Target Audiences
			if (targetAudienceFull.size() > 1 || !groupedWorkIndexer.isTreatUnknownAudienceAsUnknown()) {
				targetAudienceFull.remove("Unknown");
			}
			if (targetAudienceFull.size() > 1) {
				targetAudienceFull.remove("No Attempt To Code");
				targetAudienceFull.remove("Other");
			}
			if (targetAudienceFull.isEmpty()) {
				targetAudienceFull.add(groupedWorkIndexer.getTreatUnknownAudienceAs());
			}
			groupedWorkDoc.addField("target_audience_full", targetAudienceFull);
			if (targetAudience.size() > 1 || !groupedWorkIndexer.isTreatUnknownAudienceAsUnknown()) {
				targetAudience.remove("Unknown");
			}
			if (targetAudience.size() > 1) {
				targetAudience.remove("Other");
			}
			if (targetAudience.isEmpty()) {
				targetAudience.add(groupedWorkIndexer.getTreatUnknownAudienceAs());
			}
			if (this.isDebugEnabled()) {this.addDebugMessage("Final target audience is " + targetAudience, 1);}
			if (this.isDebugEnabled()) {this.addDebugMessage("Final full target audience is " + targetAudienceFull, 1);}
			groupedWorkDoc.addField("target_audience", targetAudience);

			//Date added to catalog
			Date dateAdded = getDateAdded();
			groupedWorkDoc.addField("date_added", dateAdded);

			//Check to see if all items are on order.  If so, add on order keywords
			boolean allItemsOnOrder = true;
			boolean allItemsUnderConsideration = true;
			boolean allItemsInProcess = true;
			int numItems = 0;
			HashSet<String> uniqueFormatCategories = new HashSet<>();
			HashSet<String> uniqueFormats = new HashSet<>();
			for (RecordInfo record : relatedRecords.values()) {
				for (ItemInfo item : record.getRelatedItems()) {
					numItems++;
					if (!item.isOrderItem()) {
						if (item.getDetailedStatus() != null) {
							if (!(item.getGroupedStatus().equals("On Order") || item.getDetailedStatus().equals("On Order") || item.getDetailedStatus().equals("Coming Soon"))) {
								allItemsOnOrder = false;
							}
							if (!(item.getGroupedStatus().equals("In Processing") || item.getDetailedStatus().equals("In-Process"))) {
								allItemsInProcess = false;
							}
							if (!(item.getGroupedStatus().equals("Under Consideration") || item.getDetailedStatus().equals("Under Consideration"))) {
								allItemsUnderConsideration = false;
							}
						}else{
							allItemsOnOrder = false;
							allItemsUnderConsideration = false;
						}
					}else{
						allItemsUnderConsideration = false;
					}
				}
				if (!record.getFormatCategories().isEmpty()) {
					uniqueFormatCategories.addAll(record.getFormatCategories());
				}
				if (record.hasItemFormats()) {
					uniqueFormats.addAll(record.getUniqueItemFormats());
					uniqueFormats.addAll(record.getUniqueItemFormatsCategories());
				}
				if (!record.getFormats().isEmpty()) {
					uniqueFormats.addAll(record.getFormats());
				}
			}
			if (!uniqueFormatCategories.isEmpty()) {
				fullTitles.add(fullTitle + " " + StringUtils.join(uniqueFormatCategories, ", "));
				addKeywords(uniqueFormatCategories);
			}
			if (!uniqueFormats.isEmpty()) {
				fullTitles.add(fullTitle + " " + StringUtils.join(uniqueFormats, ", "));
				addKeywords(uniqueFormats);
			}
			groupedWorkDoc.addField("title_full", fullTitles);

			if (numItems == 0) {
				allItemsOnOrder = false;
			}
			if (allItemsOnOrder) {
				addKeywords("On Order");
				addKeywords("Coming Soon");
				groupedWorkDoc.addField("days_since_added", -1);
				groupedWorkDoc.addField("time_since_added", "On Order");
			} else if (allItemsInProcess) {
				addKeywords("In Processing");
				groupedWorkDoc.addField("days_since_added", -2);
				groupedWorkDoc.addField("time_since_added", "In Processing");
			} else if (allItemsUnderConsideration) {
				addKeywords("Under Consideration");
				groupedWorkDoc.addField("days_since_added", Integer.MAX_VALUE);
				groupedWorkDoc.addField("time_since_added", "Under Consideration");
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
						groupedWorkDoc.addField("days_since_added", Long.toString(bibDaysSinceAdded));
						groupedWorkDoc.addField("time_since_added", DateUtils.getTimeSinceAddedForDate(publicationDate.getTime()));
					} else {
						groupedWorkDoc.addField("days_since_added", Long.toString(Integer.MAX_VALUE));
					}
				} else {
					groupedWorkDoc.addField("days_since_added", DateUtils.getDaysSinceAddedForDate(dateAdded));
					groupedWorkDoc.addField("time_since_added", DateUtils.getTimeSinceAddedForDate(dateAdded));
				}
			}

			//Awards and ratings
			groupedWorkDoc.addField("content_rating", contentRatings);
			groupedWorkDoc.addField("awards_facet", awards);
			if (lexileScore.isEmpty()) {
				groupedWorkDoc.addField("lexile_score", -1);
			} else {
				groupedWorkDoc.addField("lexile_score", lexileScore);
			}
			if (!lexileCode.isEmpty()) {
				groupedWorkDoc.addField("lexile_code", AspenStringUtils.trimTrailingPunctuation(lexileCode));
			}
			if (!fountasPinnell.isEmpty()) {
				groupedWorkDoc.addField("fountas_pinnell", fountasPinnell);
			}
			groupedWorkDoc.addField("accelerated_reader_interest_level", AspenStringUtils.trimTrailingPunctuation(acceleratedReaderInterestLevel));
			if (AspenStringUtils.isNumeric(acceleratedReaderReadingLevel)) {
				groupedWorkDoc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
			}
			if (AspenStringUtils.isNumeric(acceleratedReaderPointValue)) {
				groupedWorkDoc.addField("accelerated_reader_point_value", acceleratedReaderPointValue);
			}

			// Add some special values to keywords
			HashSet<String> eContentSources = getAllEContentSources();
			keywords.addAll(eContentSources);

			keywords.addAll(isbns.keySet());
			keywords.addAll(oclcs);
			keywords.addAll(issns);
			keywords.addAll(lccns);
			keywords.addAll(upcs.keySet());

			HashSet<String> callNumbers = getAllCallNumbers();
			keywords.addAll(callNumbers);
			groupedWorkDoc.addField("keywords", Util.getCRSeparatedStringFromSet(keywords));

			groupedWorkDoc.addField("table_of_contents", contents);
			//broad search terms
			//identifiers
			groupedWorkDoc.addField("lccn", lccns);
			groupedWorkDoc.addField("oclc", oclcs);
			//Get the primary isbn
			groupedWorkDoc.addField("primary_isbn", primaryIsbn);
			groupedWorkDoc.addField("isbn", isbns.keySet());
			groupedWorkDoc.addField("issn", issns);
			groupedWorkDoc.addField("primary_upc", getPrimaryUpc());
			groupedWorkDoc.addField("upc", upcs.keySet());

			//call numbers
			groupedWorkDoc.addField("callnumber-first", callNumberFirst);
			groupedWorkDoc.addField("callnumber-subject", callNumberSubject);
			//relevance determiners
			groupedWorkDoc.addField("popularity", Long.toString((long) popularity));
			groupedWorkDoc.addField("total_holds", Long.toString(totalHolds));
			groupedWorkDoc.addField("num_holdings", numHoldings);
			//aspen-discovery enrichment
			groupedWorkDoc.addField("rating", rating == -1f ? 2.5 : rating);
			groupedWorkDoc.addField("rating_facet", getRatingFacet(rating));

			//Links to users
			groupedWorkDoc.addField("user_rating_link", userRatingLink);
			groupedWorkDoc.addField("user_not_interested_link", userNotInterestedLink);
			groupedWorkDoc.addField("user_reading_history_link", userReadingHistoryLink);
			groupedWorkDoc.addField("list_link", listLink);
			for (Long listId : listEntryWeights.keySet()) {
				groupedWorkDoc.addField("list_entry_weight_" + listId, listEntryWeights.get(listId));
			}
			for (Long listId : listEntryDatesAdded.keySet()) {
				groupedWorkDoc.addField("list_entry_date_added_" + listId, listEntryDatesAdded.get(listId));
			}

			groupedWorkDoc.addField("description", Util.getCRSeparatedString(description));
			groupedWorkDoc.addField("display_description", displayDescription);
			//This possibly could move to the record, but that would also make the index significantly larger
			groupedWorkDoc.addField("ils_description", ilsDescription);

			for (Integer customFacetNumber : customFacetValues.keySet()) {
				groupedWorkDoc.addField("custom_facet_" + customFacetNumber, customFacetValues.get(customFacetNumber));
			}
		}catch (Exception e){
			logEntry.incErrors("Error creating solr document for grouped work " + id, e);
		}
		try{
			//Save information from scopes
			addScopedFieldsToDocument(groupedWorkDoc, logEntry);
		}catch (Exception e){
			logEntry.incErrors("Error adding scoped fields to grouped work " + id, e);
		}

		Long daysAddedSincePubDate = null;
		if (earliestPublicationDate != null) {
			//Return number of days since the given year
			Calendar publicationDate = GregorianCalendar.getInstance();
			//We don't know when in the year it is published, so assume January 1st which could be wrong
			publicationDate.set(earliestPublicationDate.intValue(), Calendar.JANUARY, 1);
			daysAddedSincePubDate = DateUtils.getDaysSinceAddedForDate(publicationDate.getTime());
		}

		for (RecordInfo recordInfo : relatedRecords.values()) {
			ArrayList<SolrInputDocument> recordDocuments = recordInfo.getRecordScopeSolrDocuments(groupedWorkIndexer, daysAddedSincePubDate);
			if (recordDocuments != null) {
				groupedWorkDoc.addChildDocuments(recordDocuments);
			}
		}

		return groupedWorkDoc;
	}

	@Override
	protected void addScopedFieldsToDocument(SolrInputDocument doc, BaseIndexingLogEntry logEntry) {
		//This is a no-op for GroupedWork Solr 3, instead it adds child documents
	}
}
