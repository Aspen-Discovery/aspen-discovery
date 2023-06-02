package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;

import java.util.*;

public class GroupedWorkSolr extends AbstractGroupedWorkSolr implements Cloneable {

	public GroupedWorkSolr(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		super(groupedWorkIndexer, logger);
	}

	public GroupedWorkSolr clone() throws CloneNotSupportedException {
		GroupedWorkSolr clonedWork = (GroupedWorkSolr) super.clone();
		super.cloneCollectionData(clonedWork);

		return clonedWork;
	}

	SolrInputDocument getSolrDocument(BaseIndexingLogEntry logEntry) {
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
				doc.addField("lexile_code", AspenStringUtils.trimTrailingPunctuation(lexileCode));
			}
			if (fountasPinnell.length() > 0) {
				doc.addField("fountas_pinnell", fountasPinnell);
			}
			doc.addField("accelerated_reader_interest_level", AspenStringUtils.trimTrailingPunctuation(acceleratedReaderInterestLevel));
			if (AspenStringUtils.isNumeric(acceleratedReaderReadingLevel)) {
				doc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
			}
			if (AspenStringUtils.isNumeric(acceleratedReaderPointValue)) {
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

	protected void addScopedFieldsToDocument(SolrInputDocument doc, BaseIndexingLogEntry logEntry) {
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
		AvailabilityToggleInfo availabilityToggleForScope = new AvailabilityToggleInfo();
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
				availabilityToggleForScope.reset();
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
						String trimmedEContentSource = curItem.getTrimmedEContentSource();
						owningLibrariesForScope.add(trimmedEContentSource);
						if (curItem.isAvailable()){
							addAvailableAt(trimmedEContentSource, availableAtForScope, availableAtByFormatForScope, formatsForItem);
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
							owningLibrariesForScope.add(curScope.isLibraryScope() ? curScope.getFacetLabel() : curScope.getLibraryScope().getFacetLabel());
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
			lowerCaseNoSpecialCharFormat = AspenStringUtils.toLowerCaseNoSpecialChars(format);
			lowerCaseNoSpecialCharFormats.put(format, lowerCaseNoSpecialCharFormat);
		}
		return lowerCaseNoSpecialCharFormat;
	}

	protected void addAvailabilityToggle(boolean local, boolean available, boolean availableOnline, AvailabilityToggleInfo availabilityToggleForScope, HashMap<String, AvailabilityToggleInfo> availabilityToggleByFormatForScope,  HashSet<String> formatsForItem){
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

	protected void addAvailableAt(String location, HashSet<String> availableAtForScope, HashMap<String, HashSet<String>> availableAtByFormatForScope,  HashSet<String> formatsForItem){
		availableAtForScope.add(location);
		for (String format : formatsForItem){
			availableAtByFormatForScope.get(format).add(location);
		}
	}

}
