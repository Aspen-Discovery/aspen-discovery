package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.GroupedWorkDisplaySettings;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.commons.lang3.StringUtils;
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
			HashSet<String> startOfTitle = new HashSet<>();
			startOfTitle.add(fullTitle);
			String sortableTitle = AspenStringUtils.makeValueSortable(fullTitle);
			startOfTitle.add(sortableTitle);
			startOfTitle.add(titleSort);
			doc.addField("title_left", startOfTitle);

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
			if (languages.size() > 1 || !groupedWorkIndexer.getTreatUnknownLanguageAs().isEmpty()) {
				languages.remove("Unknown");
			}
			if (languages.isEmpty()) {
				languages.add(groupedWorkIndexer.getTreatUnknownLanguageAs());
			}
			doc.addField("language", languages);
			doc.addField("translation", translations);
			doc.addField("language_boost", languageBoost);
			doc.addField("language_boost_es", languageBoostSpanish);
			//Publication related fields
			doc.addField("publisher", publishers);
			doc.addField("publishDate", publicationDates);
			doc.addField("placeOfPublication", placesOfPublication);
			//Sorting will use the earliest date published
			doc.addField("publishDateSort", earliestPublicationDate);

			//faceting and refined searching
			doc.addField("physical", physicals);
			doc.addField("edition", editions);
			doc.addField("dateSpan", dateSpans);
			//series.values().removeAll(GroupedWorkIndexer.hideSeries);
			doc.addField("series", series.values());
			//series2.values().removeAll(GroupedWorkIndexer.hideSeries);
			doc.addField("series2", series2.values());
			//seriesWithVolume.values().removeAll(GroupedWorkIndexer.hideSeries);
			doc.addField("series_with_volume", seriesWithVolume.values());

			doc.addField("topic", topics);
			topicFacets.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("topic_facet", topicFacets);
			subjects.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("subject_facet", subjects);
			doc.addField("lc_subject", lcSubjects);
			doc.addField("bisac_subject", bisacSubjects);
			doc.addField("genre", genres);
			genreFacets.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("genre_facet", genreFacets);
			doc.addField("geographic", geographic);
			geographicFacets.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("geographic_facet", geographicFacets);
			personalNameSubjects.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("personal_name_facet", personalNameSubjects);
			corporateNameSubjects.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("corporate_name_facet", corporateNameSubjects);
			eras.removeAll(groupedWorkIndexer.hideSubjects);
			doc.addField("era", eras);
			//Check default values and inconsistent forms
			checkDefaultValue(literaryFormFull, "Not Coded");
			checkDefaultValue(literaryFormFull, "Other");
			checkDefaultValue(literaryFormFull, "Unknown");
			if (this.isDebugEnabled()) {this.addDebugMessage("Total full literary form score: " + this.literaryFormFull, 2);}
			checkInconsistentLiteraryFormsFull();
			checkDefaultValue(literaryForm, "Not Coded");
			checkDefaultValue(literaryForm, "Other");
			checkDefaultValue(literaryForm, "Unknown");
			if (this.isDebugEnabled()) {this.addDebugMessage("Total fiction vs non fiction score: " + this.literaryForm, 2);}
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
			if (this.debugEnabled) {this.addDebugMessage("Literary form is " + literaryForm.keySet(), 4);}
			if (this.debugEnabled) {this.addDebugMessage("Full literary form is " + literaryFormFull.keySet(), 4);}
			doc.addField("literary_form_full", literaryFormFull.keySet());
			doc.addField("literary_form", literaryForm.keySet());
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
			doc.addField("target_audience_full", targetAudienceFull);
			if (targetAudience.size() > 1 || !groupedWorkIndexer.isTreatUnknownAudienceAsUnknown()) {
				targetAudience.remove("Unknown");
			}
			if (targetAudience.size() > 1) {
				targetAudience.remove("Other");
			}
			if (targetAudience.isEmpty()) {
				targetAudience.add(groupedWorkIndexer.getTreatUnknownAudienceAs());
			}
			if (this.isDebugEnabled()) {this.addDebugMessage("Final target audience is " + targetAudience, 4);}
			if (this.isDebugEnabled()) {this.addDebugMessage("Final full target audience is " + targetAudienceFull, 4);}
			doc.addField("target_audience", targetAudience);
			doc.addField("system_list", systemLists);
			//Date added to catalog
			Date dateAdded = getDateAdded();
			doc.addField("date_added", dateAdded);

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
			doc.addField("title_full", fullTitles);

			if (numItems == 0) {
				allItemsOnOrder = false;
			}
			if (allItemsOnOrder) {
				addKeywords("On Order");
				addKeywords("Coming Soon");
				doc.addField("days_since_added", -1);
				doc.addField("time_since_added", "On Order");
			} else if (allItemsInProcess) {
				addKeywords("In Processing");
				doc.addField("days_since_added", -2);
				doc.addField("time_since_added", "In Processing");
			} else if (allItemsUnderConsideration) {
				addKeywords("Under Consideration");
				doc.addField("days_since_added", Integer.MAX_VALUE);
				doc.addField("time_since_added", "Under Consideration");
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
			if (lexileScore.isEmpty()) {
				doc.addField("lexile_score", -1);
			} else {
				doc.addField("lexile_score", lexileScore);
			}
			if (!lexileCode.isEmpty()) {
				doc.addField("lexile_code", AspenStringUtils.trimTrailingPunctuation(lexileCode));
			}
			if (!fountasPinnell.isEmpty()) {
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
			doc.addField("ils_description", ilsDescription);

			for (Integer customFacetNumber : customFacetValues.keySet()) {
				doc.addField("custom_facet_" + customFacetNumber, customFacetValues.get(customFacetNumber));
			}
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
		boolean storeRecordDetailsInSolr = groupedWorkIndexer.isStoreRecordDetailsInSolr();
		try {
			if (storeRecordDetailsInSolr) {
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
		AvailabilityToggleInfo availabilityToggleForScope = new AvailabilityToggleInfo();
		AvailabilityToggleInfo availabilityToggleForItem = new AvailabilityToggleInfo();
		for (String scopeName : relatedScopes.keySet()){
			try{
				HashSet<String> scopingDetailsForScope = new HashSet<>();

				HashSet<String> localCallNumbersForScope = new HashSet<>();

				availabilityToggleForScope.reset();


				String sortableCallNumberForScope = null;
				Long daysSinceAddedForScope = null;
				long libBoost = 1;

				ArrayList<ScopingInfo> itemsWithScopingInfoForActiveScope = relatedScopes.get(scopeName);
				String scopePrefix = scopeName + "#";
				Scope curScope = null;
				String scopeFacetLabel = null;
				GroupedWorkDisplaySettings scopeDisplaySettings = null;
				if (!itemsWithScopingInfoForActiveScope.isEmpty()) {
					curScope = itemsWithScopingInfoForActiveScope.get(0).getScope();
					scopeFacetLabel = curScope.getFacetLabel();
					scopeDisplaySettings  = curScope.getGroupedWorkDisplaySettings();
				}

				//Process all items for the scope
				for (ScopingInfo scopingInfo : itemsWithScopingInfoForActiveScope) {
					if (storeRecordDetailsInSolr) {
						scopingDetailsForScope.add(scopingInfo.getScopingDetails());
					}

					HashSet<String> formatsForItem;
					HashSet<String> formatsCategoriesForItem;
					HashSet<String> availableAtForItem = new HashSet<>();
					availabilityToggleForItem.reset();

					String readerName = "Libby";

					//Loading reader name here isn't really needed since it gets set for the item based on scope
//					if ((scopingInfo.getScope().getOverDriveScope()) != null){
//						readerName = scopingInfo.getScope().getOverDriveScope().getReaderName();
//					}
					ItemInfo curItem = scopingInfo.getItem();
					try {
						formatsForItem = curItem.getFormatsForIndexing();
						formatsCategoriesForItem = curItem.getFormatCategoriesForIndexing();
						loadScopedFormatInfo(formats, formatCategories, scopePrefix, formatsForItem, formatsCategoriesForItem);

						boolean addAllOwningLocations = false;
						boolean addAllOwningLocationsToAvailableAt = false;
						boolean locallyOwned = scopingInfo.isLocallyOwned();
						boolean libraryOwned = scopingInfo.isLibraryOwned();
						boolean isAvailable = curItem.isAvailable();
						boolean isEContent = curItem.isEContent();
						if (isEContent) {
							String trimmedEContentSource = curItem.getTrimmedEContentSource();
							if (trimmedEContentSource.equals("overdrive")){
								trimmedEContentSource = readerName;
							}
							addAvailabilityToggle(locallyOwned || libraryOwned, scopeDisplaySettings.isIncludeOnlineMaterialsInAvailableToggle() && isAvailable, isAvailable, availabilityToggleForItem);
							owningLibraries.add(scopePrefix + trimmedEContentSource);
							if (isAvailable) {
								availableAtForItem.add(trimmedEContentSource);
							}
							eContentSources.add(scopePrefix + trimmedEContentSource);
						} else { //physical materials
							if (locallyOwned) {
								addAvailabilityToggle(locallyOwned, isAvailable, false, availabilityToggleForItem);
								if (isAvailable) {
									availableAtForItem.add(scopeFacetLabel);
								}
								//For physical materials, only locally owned means it is a location/branch scope and that branch owns it
								owningLocations.add(scopePrefix + scopeFacetLabel);
								//This can be a library scope if it is both a library and location scope
								owningLibraries.add(scopePrefix  + (curScope.isLibraryScope() ? scopeFacetLabel : curScope.getLibraryScope().getFacetLabel()));

								if (curScope.isIncludeAllLibraryBranchesInFacets()) {
									//Include other branches of this library that own the title within the owning locations
									//isIncludeAllLibraryBranchesInFacets is only a setting at the location level
									addAllOwningLocations = true;
								}
							}
							if (libraryOwned) {
								if (curScope.isLibraryScope() || (curScope.isLocationScope() && !scopeDisplaySettings.isBaseAvailabilityToggleOnLocalHoldingsOnly())) {
									addAvailabilityToggle(libraryOwned, isAvailable, false, availabilityToggleForItem);
								}
								if (isAvailable) {
									addAllOwningLocationsToAvailableAt = true;
								}
								owningLibraries.add(scopePrefix + scopeFacetLabel);
								//For owning locations, add all locations within the library that own it
								addAllOwningLocations = true;
							}
							//If it is not library or location owned, we might still add to the availability toggles
							if (!locallyOwned && !libraryOwned && !scopeDisplaySettings.isBaseAvailabilityToggleOnLocalHoldingsOnly()) {
								addAvailabilityToggle(false, isAvailable, false, availabilityToggleForItem);
								if (isAvailable) {
									addAllOwningLocationsToAvailableAt = true;
								}
							}
							if (isAvailable && curScope.getAdditionalLocationsToShowAvailabilityForPattern() != null && curItem.getLocationCode() != null) {
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
							availableAtForItem.addAll(curItem.getLocationOwnedNames());
						}

						for (String availableAtLocation : availableAtForItem) {
							availableAt.add(scopePrefix + availableAtLocation);
						}

						availabilityToggleForScope.local = availabilityToggleForScope.local || availabilityToggleForItem.local;
						availabilityToggleForScope.available = availabilityToggleForScope.available || availabilityToggleForItem.available;
						availabilityToggleForScope.availableOnline = availabilityToggleForScope.availableOnline || availabilityToggleForItem.availableOnline;

						loadScopedEditionInformation(editionInfo, scopePrefix, formatsForItem, formatsCategoriesForItem, availableAtForItem, availabilityToggleForItem);


						if (locallyOwned || libraryOwned || scopeDisplaySettings.isIncludeAllRecordsInShelvingFacets()) {
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
						if (isEContent || locallyOwned || libraryOwned || scopeDisplaySettings.isIncludeAllRecordsInDateAddedFacets()) {
							Long daysSinceAdded = loadScopedDaysAdded(curItem);
							if (daysSinceAddedForScope == null || daysSinceAdded > daysSinceAddedForScope) {
								daysSinceAddedForScope = daysSinceAdded;
							}
						}

						if (locallyOwned || libraryOwned) {
							if (isAvailable) {
								if (libBoost < groupedWorkIndexer.availableAtBoostValue) {
									libBoost = groupedWorkIndexer.availableAtBoostValue;
								}
							} else {
								if (libBoost < groupedWorkIndexer.ownedByBoostValue) {
									libBoost = groupedWorkIndexer.ownedByBoostValue;
								}
							}
						}

						String trimmedIType = curItem.getTrimmedIType();
						if (trimmedIType != null) {
							iTypes.add(scopePrefix + trimmedIType);
						}

						if (locallyOwned || libraryOwned || !scopingInfo.getScope().isRestrictOwningLibraryAndLocationFacets()) {
							localCallNumbersForScope.add(curItem.getCallNumber());
							if (sortableCallNumberForScope == null) {
								sortableCallNumberForScope = curItem.getSortableCallNumber();
							}
						}
					}catch (Exception e){
						logEntry.incErrors("Error setting up scope information for " + id + " scope " + scopeName + " item " + curItem.getItemIdentifier(), e);
					}
				} // end item loop

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
		} //End scope loop
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

		//logger.info("Work " + id + " processed " + relatedScopes.size() + " scopes");
	}

	private void loadScopedEditionInformation(HashSet<String> editionInfo, String scopePrefix, HashSet<String> formatsForItem, HashSet<String> formatsCategoriesForItem, HashSet<String> availableAtForItem, AvailabilityToggleInfo availabilityToggleForItem) {
		if (formatsCategoriesForItem.isEmpty()){
			formatsCategoriesForItem.add("");
		}
		if (availableAtForItem.isEmpty()) {
			availableAtForItem.add("none");
		}
		HashSet<String> availabilityToggleValues = availabilityToggleForItem.getValues();
		for (String formatCategory : formatsCategoriesForItem) {
			String scopeAndFormatCategory = scopePrefix + formatCategory;
			for (String format : formatsForItem) {
				String scopeFormatCategoryFormat = scopeAndFormatCategory  + "#" + format;
				for (String availabilityToggle : availabilityToggleValues) {
					StringBuilder baseEditionBuilder = new StringBuilder(scopeFormatCategoryFormat)
						.append("#")
						.append(availabilityToggle)
						.append("#");

					for (String availableAtLocation : availableAtForItem) {
						StringBuilder editionBuilder = new StringBuilder(baseEditionBuilder);
						String editionString = editionBuilder.append(availableAtLocation)
							.append("#")
							.toString() // Get the final String
							.replace(' ', '_');
						editionInfo.add(editionString);
					}
				}
			}
		}
	}

	private Long daysAddedSincePubDate = null;
	private Long loadScopedDaysAdded(ItemInfo curItem) {
		Long daysSinceAdded;
		if (curItem.isOrderItem() ||
			curItem.getStatusCode() != null &&
			(curItem.getGroupedStatus().equals("On Order") ||
			curItem.getGroupedStatus().equals("In Processing") ||
			curItem.getDetailedStatus().equals("Coming Soon") || // Falls under the "On Order" facet, so only consider the item's status to find it.
			curItem.getGroupedStatus().equals("Under Consideration")))
		{
			if (curItem.getGroupedStatus().equals("Under Consideration")) {
				daysSinceAdded = (long)Integer.MAX_VALUE;
			} else if (curItem.getGroupedStatus().equals("In Processing")) {
				daysSinceAdded = -2L;
			} else {
				daysSinceAdded = -1L;
			}
		} else {
			//Date Added To Catalog needs to be the earliest date added for the catalog.
			Date dateAdded = curItem.getDateAdded();
			//See if we need to override based on publication date if not provided.
			//Should be set by individual driver though.
			if (dateAdded == null) {
				if (earliestPublicationDate != null) {
					if (daysAddedSincePubDate == null) {
						//Return number of days since the given year
						Calendar publicationDate = GregorianCalendar.getInstance();
						//We don't know when in the year it is published, so assume January 1st which could be wrong
						publicationDate.set(earliestPublicationDate.intValue(), Calendar.JANUARY, 1);
						daysAddedSincePubDate = DateUtils.getDaysSinceAddedForDate(publicationDate.getTime());
					}
					daysSinceAdded = daysAddedSincePubDate;
				} else {
					daysSinceAdded = Long.MAX_VALUE;
				}
			} else {
				daysSinceAdded = DateUtils.getDaysSinceAddedForDate(dateAdded);
			}
		}
		return daysSinceAdded;
	}

	private void loadScopedFormatInfo(HashSet<String> scopedFormats, HashSet<String> scopedFormatCategories, String scopePrefix, HashSet<String> formatsForItem, HashSet<String> formatsCategoriesForItem) {
		for (String format : formatsForItem) {
			scopedFormats.add(scopePrefix + format);
		}
		for (String formatCategory : formatsCategoriesForItem) {
			scopedFormatCategories.add(scopePrefix + formatCategory);
		}
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
}
