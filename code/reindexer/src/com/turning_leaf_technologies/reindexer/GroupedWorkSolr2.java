package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;

import java.util.*;

public class GroupedWorkSolr2 extends AbstractGroupedWorkSolr implements Cloneable {


	public GroupedWorkSolr2(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		super(groupedWorkIndexer, logger);
	}

	public GroupedWorkSolr2 clone() throws CloneNotSupportedException {
		GroupedWorkSolr2 clonedWork = (GroupedWorkSolr2) super.clone();
		super.cloneCollectionData(clonedWork);

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

	protected void addScopedFieldsToDocument(SolrInputDocument doc, BaseLogEntry logEntry) {
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

		HashSet<String> editionInfo = new HashSet<>();

		HashSet<String> formats = new HashSet<>();
		HashSet<String> formatCategories = new HashSet<>();
		HashSet<String> owningLibraries = new HashSet<>();
		HashSet<String> owningLocations = new HashSet<>();
		HashSet<String> collections = new HashSet<>();
		HashSet<String> detailedLocations = new HashSet<>();
		HashSet<String> shelfLocations = new HashSet<>();
		HashSet<String> iTypes = new HashSet<>();
		HashSet<String> eContentSources = new HashSet<>();
		HashSet<String> availableAt = new HashSet<>();
		HashSet<String> availabilityToggleValues = new HashSet<>();
		for (String scopeName : relatedScopes.keySet()){
			try{
				HashSet<String> scopingDetailsForScope = new HashSet<>();

				HashSet<String> localCallNumbersForScope = new HashSet<>();

				AvailabilityToggleInfo availabilityToggleForScope = new AvailabilityToggleInfo();


				String sortableCallNumberForScope = null;
				Long daysSinceAddedForScope = null;
				long libBoost = 1;

				ArrayList<ScopingInfo> itemsWithScopingInfoForActiveScope = relatedScopes.get(scopeName);
				String scopePrefix = scopeName + "#";
				for (ScopingInfo scopingInfo : itemsWithScopingInfoForActiveScope) {
					Scope curScope = scopingInfo.getScope();

					if (groupedWorkIndexer.isStoreRecordDetailsInSolr()) {
						scopingDetailsForScope.add(scopingInfo.getScopingDetails());
					}

					HashSet<String> formatsForItem = new HashSet<>();
					HashSet<String> formatsCategoriesForItem = new HashSet<>();
					HashSet<String> availableAtForItem = new HashSet<>();
					AvailabilityToggleInfo availabilityToggleForItem = new AvailabilityToggleInfo();

					ItemInfo curItem = scopingInfo.getItem();
					try {
						if (curItem.getFormat() != null) {
							formats.add(scopePrefix+ curItem.getFormat());
							formatsForItem.add(curItem.getFormat());
						} else {
							formatsForItem.addAll(curItem.getRecordInfo().getFormats());
							for (String format : curItem.getRecordInfo().getFormats()) {
								formats.add(scopePrefix + format);
							}
						}
						if (curItem.getFormatCategory() != null) {
							formatCategories.add(scopePrefix + curItem.getFormatCategory());
							formatsCategoriesForItem.add(curItem.getFormatCategory());
						} else {
							for (String formatCategory : curItem.getRecordInfo().getFormatCategories()) {
								formatCategories.add(scopePrefix + formatCategory);
							}
							formatsCategoriesForItem.addAll(curItem.getRecordInfo().getFormatCategories());
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

						boolean addAllOwningLocations = false;
						boolean addAllOwningLocationsToAvailableAt = false;
						if (curItem.isEContent()) {
							addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(), curScope.getGroupedWorkDisplaySettings().isIncludeOnlineMaterialsInAvailableToggle() && curItem.isAvailable(), curItem.isAvailable(), availabilityToggleForItem);
							owningLibraries.add(scopePrefix + curItem.getTrimmedEContentSource());
							if (curItem.isAvailable()) {
								availableAtForItem.add(curItem.getTrimmedEContentSource());
							}
						} else { //physical materials
							if (scopingInfo.isLocallyOwned()) {
								addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(), curItem.isAvailable(), false, availabilityToggleForItem);
								if (curItem.isAvailable()) {
									availableAtForItem.add(curScope.getFacetLabel());
								}
								//For physical materials, only locally owned means it is a location/branch scope and that branch owns it
								owningLocations.add(scopePrefix + curScope.getFacetLabel());
								//This can be a library scope if it is both a library and location scope
								owningLibraries.add(scopePrefix  + (curScope.isLibraryScope() ? curScope.getFacetLabel() : curScope.getLibraryScope().getFacetLabel()));

								if (curScope.isIncludeAllLibraryBranchesInFacets()) {
									//Include other branches of this library that own the title within the owning locations
									//isIncludeAllLibraryBranchesInFacets is only a setting at the location level
									addAllOwningLocations = true;
								}
							}
							if (scopingInfo.isLibraryOwned()) {
								addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(), curItem.isAvailable(), false, availabilityToggleForItem);
								if (curItem.isAvailable()) {
									addAllOwningLocationsToAvailableAt = true;
								}
								owningLibraries.add(scopePrefix + curScope.getFacetLabel());
								//For owning locations, add all locations within the library that own it
								addAllOwningLocations = true;
							}
							//If it is not library or location owned, we might still add to the availability toggles
							if (!scopingInfo.isLocallyOwned() && !scopingInfo.isLibraryOwned() && !curScope.getGroupedWorkDisplaySettings().isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
								addAvailabilityToggle(scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned(), curItem.isAvailable(), false, availabilityToggleForItem);
								if (curItem.isAvailable()) {
									addAllOwningLocationsToAvailableAt = true;
								}
							}
							if (curItem.isAvailable() && curScope.getAdditionalLocationsToShowAvailabilityForPattern() != null && curItem.getLocationCode() != null) {
								//We might include the item in the owning and availability facets if it matched the available locations
								if (curScope.getAdditionalLocationsToShowAvailabilityForPattern().matcher(curItem.getLocationCode()).matches()) {
									addAllOwningLocationsToAvailableAt = true;
								}
							}

							if (!curScope.isRestrictOwningLibraryAndLocationFacets() || curScope.isConsortialCatalog()) {
								for (String libraryOwnedName : curItem.getLibraryOwnedNames()) {
									owningLibraries.add(scopePrefix + libraryOwnedName);
								}
								addAllOwningLocations = true;
							}
						}

						if (addAllOwningLocations){
							addAllWithPrefix(owningLocations, scopePrefix, curItem.getLocationOwnedNames());
						}
						if (addAllOwningLocationsToAvailableAt){
							addAllWithPrefix(availableAtForItem, scopePrefix, curItem.getLocationOwnedNames());
						}

						for (String availableAtLocation : availableAtForItem) {
							availableAt.add(scopePrefix + availableAtLocation);
						}

						availabilityToggleForScope.local = availabilityToggleForScope.local || availabilityToggleForItem.local;
						availabilityToggleForScope.available = availabilityToggleForScope.available || availabilityToggleForItem.available;
						availabilityToggleForScope.availableOnline = availabilityToggleForScope.availableOnline || availabilityToggleForItem.availableOnline;

						if (formatsCategoriesForItem.size() == 0){
							formatsCategoriesForItem.add("");
						}
						for (String formatCategory : formatsCategoriesForItem) {
							for (String format : formatsForItem) {
								for (String availabilityToggle : availabilityToggleForItem.getValues()) {
									String baseEditionStmt = scopePrefix + formatCategory + "#" + format + "#" + availabilityToggle;
									for (String availableAtLocation : availableAtForItem) {
										String editionStmt = baseEditionStmt + "#" + availableAtLocation + "#";
										editionStmt = editionStmt.replace(' ', '_');
										editionInfo.add(editionStmt);
									}
									if (availableAtForItem.size() == 0) {
										String editionStmt = baseEditionStmt + "#none#";
										editionStmt = editionStmt.replace(' ', '_');
										editionInfo.add(editionStmt);
									}
								}
							}
						}


						if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || scopingInfo.getScope().getGroupedWorkDisplaySettings().isIncludeAllRecordsInShelvingFacets()) {
							if (curItem.getCollection() != null) {
								collections.add(scopePrefix + curItem.getCollection());
							}
							if (curItem.getDetailedLocation() != null) {
								detailedLocations.add(scopePrefix + curItem.getDetailedLocation());
							}
							if (curItem.getShelfLocation() != null) {
								shelfLocations.add(scopePrefix + curItem.getShelfLocation());
							}
						}
						if (curItem.isEContent() || scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || scopingInfo.getScope().getGroupedWorkDisplaySettings().isIncludeAllRecordsInDateAddedFacets()) {
							if (daysSinceAddedForScope == null || daysSinceAdded > daysSinceAddedForScope) {
								daysSinceAddedForScope = daysSinceAdded;
							}
						}

						if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned()) {
							if (curItem.isAvailable()) {
								if (libBoost < GroupedWorkIndexer.availableAtBoostValue) {
									libBoost = GroupedWorkIndexer.availableAtBoostValue;
								}
							} else {
								if (libBoost < GroupedWorkIndexer.ownedByBoostValue) {
									libBoost = GroupedWorkIndexer.ownedByBoostValue;
								}
							}
						}

						if (curItem.getTrimmedIType() != null) {
							iTypes.add(scopePrefix + curItem.getTrimmedIType());
						}

						if (curItem.isEContent()) {
							eContentSources.add(scopePrefix + curItem.getTrimmedEContentSource());
						}
						if (scopingInfo.isLocallyOwned() || scopingInfo.isLibraryOwned() || !scopingInfo.getScope().isRestrictOwningLibraryAndLocationFacets()) {
							localCallNumbersForScope.add(curItem.getCallNumber());
							if (sortableCallNumberForScope == null) {
								sortableCallNumberForScope = curItem.getSortableCallNumber();
							}
						}
					}catch (Exception e){
						logEntry.incErrors("Error setting up scope information for " + id + " scope " + scopeName + " item " + curItem.getItemIdentifier(), e);
					}
				}

				//eAudiobooks are considered both Audiobooks and eBooks by some people
				if (formats.contains(scopeName + "#eAudiobook")) {
					formatCategories.add(scopeName + "#eBook");
				}
				if (formats.contains(scopeName + "#CD + Book")) {
					formatCategories.add(scopeName + "#Books");
					formatCategories.add(scopeName + "#Audio Books");
				}
				if (formats.contains(scopeName + "#VOX Books")) {
					formatCategories.add(scopeName + "#Books");
					formatCategories.add(scopeName + "#Audio Books");
				}
				doc.addField("scoping_details_" + scopeName, scopingDetailsForScope);

				if (daysSinceAddedForScope != null){
					doc.addField("local_days_since_added_" + scopeName, daysSinceAddedForScope);
				}
				doc.addField("lib_boost_" + scopeName, libBoost);
				doc.addField("local_callnumber_" + scopeName, localCallNumbersForScope);
				doc.addField("callnumber_sort_" + scopeName, sortableCallNumberForScope);

				for (String availabilityToggleValue : availabilityToggleForScope.getValues()){
					availabilityToggleValues.add(scopePrefix + availabilityToggleValue);
				}

				SolrInputField field = doc.getField("local_days_since_added_" + scopeName);
				if (field != null) {
					Long daysSinceAdded = (Long) field.getFirstValue();
					doc.addField("local_time_since_added_" + scopeName, DateUtils.getTimeSinceAdded(daysSinceAdded));
				}
			} catch (Exception e){
				logEntry.incErrors("Error setting up scope information for " + id + " scope " + scopeName, e);
			}
		}
		doc.addField("edition_info", editionInfo);
		doc.addField("format", formats);
		doc.addField("format_category", formatCategories);
		doc.addField("owning_library", owningLibraries);
		doc.addField("owning_location", owningLocations);
		doc.addField("collection", collections);
		doc.addField("detailed_location", detailedLocations);
		doc.addField("shelf_location", shelfLocations);
		doc.addField("itype", iTypes);
		doc.addField("econtent_source", eContentSources);
		doc.addField("availability_toggle", availabilityToggleValues);
		doc.addField("available_at", availableAt);

		logger.info("Work " + id + " processed " + relatedScopes.size() + " scopes");
	}

	private void addAllWithPrefix(HashSet<String> fieldValues, String scopePrefix, HashSet<String> valuesToAdd) {
		for (String valueToAdd : valuesToAdd){
			fieldValues.add(scopePrefix + valueToAdd);
		}
	}

	protected void addAvailabilityToggle(boolean local, boolean available, boolean availableOnline, AvailabilityToggleInfo availabilityToggleForItem){
		availabilityToggleForItem.local = availabilityToggleForItem.local || local;
		availabilityToggleForItem.available = availabilityToggleForItem.available || available;
		availabilityToggleForItem.availableOnline = availabilityToggleForItem.availableOnline || availableOnline;
	}

	protected void addAvailableAt(String location, HashSet<String> availableAtForScope){
		availableAtForScope.add(location);
	}


}
