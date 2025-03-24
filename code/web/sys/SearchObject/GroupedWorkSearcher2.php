<?php
require_once ROOT_DIR . '/sys/SearchObject/AbstractGroupedWorkSearcher.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacet.php';

class SearchObject_GroupedWorkSearcher2 extends SearchObject_AbstractGroupedWorkSearcher {
	// Field List
	public static $fields_to_return = 'auth_author2,author2-role,id,mpaaRating,title_display,title_full,title_short,subtitle_display,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,num_titles,record_details,item_details,publisherStr,publishDate,publishDateSort,placeOfPublication,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,display_description,fountas_pinnell,last_indexed,lc_subject,bisac_subject,format,format_category,language,ils_description, author_additional';

	// Display Modes //
	public $viewOptions = [
		'list',
		'covers',
	];

	private $fieldsToReturn = null;

	/**
	 * Constructor. Initialise some details about the server
	 *
	 * @access  public
	 */
	public function __construct() {
		// Call base class constructor
		parent::__construct(2);

		global $configArray;
		global $timer;
		// Initialise the index
		$this->indexEngine = new GroupedWorksSolrConnector2($configArray['Index']['url']);
		$timer->logTime('Created Index Engine');

		// Get default facet settings
		$this->allFacetSettings = getExtraConfigArray('groupedWorksFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('groupedWorksSearches');
		if (isset($searchSettings['General']['default_sort'])) {
			$this->defaultSort = $searchSettings['General']['default_sort'];
		}
		if (isset($searchSettings['General']['default_view'])) {
			$this->defaultView = $searchSettings['General']['default_view'];
		}
		if (isset($searchSettings['DefaultSortingByType']) && is_array($searchSettings['DefaultSortingByType'])) {
			$this->defaultSortByType = $searchSettings['DefaultSortingByType'];
		}
		if (isset($searchSettings['Basic_Searches'])) {
			$this->searchIndexes = $searchSettings['Basic_Searches'];
		}
		if (isset($searchSettings['Advanced_Searches'])) {
			$this->advancedTypes = $searchSettings['Advanced_Searches'];
		}

		// Load sort preferences (or defaults if none in .ini file):
		$this->sortOptions = [
			'relevance' => 'Best Match',
			'year desc,title asc' => "Publication Year Desc",
			'year asc,title asc' => "Publication Year Asc",
			'author asc,title asc' => "Author",
			'title' => 'Title',
			'days_since_added asc' => "Date Purchased Desc",
			'callnumber_sort' => 'sort_callnumber',
			'popularity desc' => 'sort_popularity',
			'rating desc' => 'sort_rating',
			'total_holds desc' => "Number of Holds",
		];

		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Solr Search Object');
	}

	/**
	 * Actually process and submit the search
	 *
	 * @access  public
	 * @param bool $returnIndexErrors Should we die inside the index code if
	 *                                     we encounter an error (false) or return
	 *                                     it for access via the getIndexError()
	 *                                     method (true)?
	 * @param bool $recommendations Should we process recommendations along
	 *                                     with the search itself?
	 * @param bool $preventQueryModification Should we allow the search engine
	 *                                             to modify the query or is it already
	 *                                             a well formatted query
	 * @return  array|AspenError
	 */
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) : AspenError|array|null {
		global $timer;
		global $solrScope;

		if ($this->searchSource == 'econtent') {
			$this->addHiddenFilter("econtent_source", "$solrScope#*");
		}

		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendation module appropriate to the current search:
		if ($recommendations) {
			$this->initRecommendations();
		}
		$timer->logTime("initRecommendations");

		// Build Query
		if ($preventQueryModification) {
			$query = $search;
		} else {
			$query = $this->indexEngine->buildQuery($search, false);
		}
		$timer->logTime("build query in grouped work searcher");
		if (($query instanceof AspenError)) {
			return $query;
		}

		// Only use the query we just built if there isn't an override in place.
		if ($this->query == null) {
			$this->query = $query;
		}

		// Define Filter Query
		$filterQuery = $this->hiddenFilters;
		//Remove any empty filters if we get them
		//(typically happens when a subdomain has a function disabled that is enabled in the main scope)
		//Also fix dynamic field names
		$validFields = $this->loadValidFields();
		$dynamicFields = $this->loadDynamicFields();
		foreach ($this->filterList as $field => $filter) {
			if ($field === '') {
				unset($this->filterList[$field]);
			}
			if (strpos($field, 'custom_facet_') === 0) {
				$this->filterList[$field] = $filter;
			}else if (strpos($field, '_') !== false) {
				$lastUnderscore = strrpos($field, '_');
				$shortFieldName = substr($field, 0, $lastUnderscore + 1);
				$oldScope = substr($field, $lastUnderscore + 1);
				if ($oldScope != $solrScope) {
					//Correct any dynamic fields
					foreach ($dynamicFields as $dynamicField) {
						if ($shortFieldName == $dynamicField) {
							//This is a dynamic field with the wrong scope
							if ($field != ($dynamicField . $solrScope)) {
								unset($this->filterList[$field]);
								$this->filterList[$dynamicField . $solrScope] = $filter;
							}
							break;
						}
					}
				}
			}
		}

		$this->selectedAvailabilityToggleValue = null;
		$selectedAvailableAtValues = [];
		$selectedFormatValues = [];
		$selectedFormatCategoryValues = [];
		$facetConfig = $this->getFacetConfig();
		$availabilityToggleId = null;
		foreach ($this->filterList as $field => $filter) {
			$multiSelect = false;
			$fieldPrefix = '';
			if (isset($facetConfig[$field])) {
				/** @var FacetSetting $facetInfo */
				$facetInfo = $facetConfig[$field];
				$facetName = $facetInfo->getFacetName(2);
				$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
				$multiSelect = $facetInfo->multiSelect || $facetName == 'availability_toggle';
				$fieldPrefix = "{!tag=$facetKey}";
			} else {
				//This is either a field we need to convert from the old schema to new schema or valid field from advanced search we aren't seeing here
				$tmpFieldName = substr($field, 0, strrpos($field, '_'));
				if (isset($facetConfig[$tmpFieldName])) {
					$facetInfo = $facetConfig[$tmpFieldName];
					$facetName = $facetInfo->getFacetName(2);
					$field = $tmpFieldName;
					$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
					$multiSelect = $facetInfo->multiSelect || $facetName == 'availability_toggle';
					$fieldPrefix = "{!tag=$facetKey}";
				} else {
					if (in_array($field, $validFields)) {
						$facetName = $field;
						$multiSelect = false;
					} else {
						//Unknown field
						continue;
					}
				}
			}
			$fieldValue = "";
			foreach ($filter as $value) {
				if ($facetName == 'availability_toggle' || $facetName == "availability_toggle_$solrScope") {
					$this->selectedAvailabilityToggleValue = $value;
					$availabilityToggleId = $facetInfo->id;
				} elseif ($facetName == 'available_at' || $facetName == "available_at_$solrScope") {
					$selectedAvailableAtValues[] = $value;
				} elseif ($facetName == 'format_category') {
					$selectedFormatCategoryValues[] = $value;
				} elseif ($facetName == 'format') {
					$selectedFormatValues[] = $value;
				}
				if ($this->isScopedField($facetName)) {
					$value = "$solrScope#$value";
				}

				// Special case -- allow trailing wildcards:
				$okToAdd = false;
				if (substr($value, -1) == '*') {
					$okToAdd = true;
				} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)) {
					$okToAdd = true;
				} elseif (preg_match('/^\\(.*?\\)$/', $value)) {
					$okToAdd = true;
				} else {
					if (!empty($value)) {
						//The value is already specified as field:value
						if (is_numeric($field)) {
							$filterQuery[] = $value;
						} else {
							$okToAdd = true;
							$value = "\"$value\"";
						}
					}
				}
				if ($okToAdd) {
					if ($multiSelect) {
						if (!empty($fieldValue)) {
							$fieldValue .= ' OR ';
						}
						$fieldValue .= $value;
					} else {
						$filterQuery[] = "$fieldPrefix$field:$value";
					}
				}
			}
			if ($multiSelect) {
				$filterQuery[] = "$fieldPrefix$field:($fieldValue)";
			}
		}

		//Check to see if we should apply a default filter
		if ($this->selectedAvailabilityToggleValue == null) {
			global $library;
			$location = Location::getSearchLocation(null);
			if ($location != null) {
				$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
			} else {
				$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			}
			$availabilityToggleValue = $groupedWorkDisplaySettings->defaultAvailabilityToggle;
			$this->selectedAvailabilityToggleValue = $availabilityToggleValue;

			if ($availabilityToggleId == null) {
				foreach ($facetConfig as $facetInfo) {
					$facetName = $facetInfo->getFacetName(2);
					if ($facetName == 'availability_toggle' || $facetName == "availability_toggle_$solrScope") {
						$availabilityToggleId = $facetInfo->id;
					}
				}
			}

			$filterQuery[] = "{!tag=$availabilityToggleId}availability_toggle:\"$solrScope#$availabilityToggleValue\"";
		}

		$facetSet = [];

		if (empty($this->selectedAvailabilityToggleValue)) {
			$this->selectedAvailabilityToggleValue = 'global';
		}
		if (empty($selectedAvailableAtValues)) {
			$selectedAvailableAtValues[] = '*';
		}
		if (empty($selectedFormatCategoryValues)) {
			$selectedFormatCategoryValues[] = '*';
		}
		if (empty($selectedFormatValues)) {
			$selectedFormatValues[] = '*';
		}
		$allEditionFilters = [];
//		$editionFiltersFormat = [];
//		$editionFiltersFormatCategory = [];
//		$editionFiltersFormatAvailability = [];
//		$editionFiltersFormatAvailableAt = [];
		foreach ($selectedAvailableAtValues as $selectedAvailableAtValue) {
			$selectedAvailableAtValue = str_replace('(', '\(', $selectedAvailableAtValue);
			$selectedAvailableAtValue = str_replace(')', '\)', $selectedAvailableAtValue);
			foreach ($selectedFormatCategoryValues as $selectedFormatCategoryValue) {
				foreach ($selectedFormatValues as $selectedFormatValue) {
//					if ($selectedFormatValue != '*'){
//						$editionFiltersFormat[] = str_replace(' ', '_', "edition_info:$solrScope#$selectedFormatCategoryValue#*#$this->selectedAvailabilityToggleValue#$selectedAvailableAtValue#");
//					}
//					if ($selectedFormatCategoryValue != '*'){
//						$editionFiltersFormatCategory[] = str_replace(' ', '_', "edition_info:$solrScope#*#$selectedFormatValue#$this->selectedAvailabilityToggleValue#$selectedAvailableAtValue#");
//					}
//					if ($this->selectedAvailabilityToggleValue != 'global'){
//						$editionFiltersFormatAvailability[] = str_replace(' ', '_', "edition_info:$solrScope#$selectedFormatCategoryValue#$selectedFormatValue#*#$selectedAvailableAtValue#");
//					}
//					if ($selectedAvailableAtValue != '*'){
//						$editionFiltersFormatAvailableAt[] = str_replace(' ', '_', "edition_info:$solrScope#$selectedFormatCategoryValue#$selectedFormatValue#$this->selectedAvailabilityToggleValue#*#");
//					}
					$allEditionFilters[] = str_replace(' ', '_', "edition_info:$solrScope#$selectedFormatCategoryValue#$selectedFormatValue#$this->selectedAvailabilityToggleValue#$selectedAvailableAtValue#");
				}
			}
		}
		if (count($allEditionFilters) > 0) {
			$allEditions = '(' . implode(' OR ', $allEditionFilters) . ')';
			$filterQuery[] = "{!tag=edition_info}$allEditions";
		}
//		if (count($editionFiltersFormat) > 0) {
//			$allFormatEditions = '(' . implode(' OR ', $editionFiltersFormat) . ')';
//			$filterQuery[] = "{!tag=edition_info_format}$allFormatEditions";
//		}
//		if (count($editionFiltersFormatCategory) > 0) {
//			$allFormatCategoryEditions = '(' . implode(' OR ', $editionFiltersFormatCategory) . ')';
//			$filterQuery[] = "{!tag=edition_info_format_category}$allFormatCategoryEditions";
//		}
//		if (count($editionFiltersFormatAvailability) > 0) {
//			$allAvailabilityEditions = '(' . implode(' OR ', $editionFiltersFormatAvailability) . ')';
//			$filterQuery[] = "{!tag=edition_info_availability}$allAvailabilityEditions";
//		}
//		if (count($editionFiltersFormatAvailableAt) > 0) {
//			$allAvailableAtEditions = '(' . implode(' OR ', $editionFiltersFormatAvailableAt) . ')';
//			$filterQuery[] = "{!tag=edition_info_available_at}$allAvailableAtEditions";
//		}

		// If we are only searching one field use the DisMax handler
		//    for that field. If left at null let solr take care of it
		if (count($search) == 1 && isset($search[0]['index'])) {
			$this->index = $search[0]['index'];
		}

		// Build a list of facets we want from the index
		$facetConfig = $this->getFacetConfig();
		if ($recommendations && !empty($facetConfig)) {
			$facetSet['limit'] = $this->facetLimit;
			foreach ($facetConfig as $facetField => $facetInfo) {
				if ($facetInfo instanceof FacetSetting) {
					$isMultiSelect = $facetInfo->multiSelect;
					$additionalTags = '';
					$facetName = $facetInfo->getFacetName(2);
					if ($facetName == 'availability_toggle' || $facetName == "availability_toggle_$solrScope") {
						//$isEditionField = true;
						$isMultiSelect = true;
						$additionalTags = 'edition_info,edition_info_available_at,edition_info_format_category,edition_info_format';
					} elseif ($facetName == 'available_at' || $facetName == "available_at_$solrScope") {
						$additionalTags = 'edition_info,edition_info_availability,edition_info_format_category,edition_info_format';
					} elseif ($facetName == 'format_category') {
						$isMultiSelect = true;
						$additionalTags = 'edition_info,edition_info_availability,edition_info_available_at,edition_info_format';
					} elseif ($facetName == 'format') {
						$additionalTags = 'edition_info,edition_info_availability,edition_info_available_at,edition_info_format_category';
					}
					if ($isMultiSelect && !empty($additionalTags)) {
						$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
						$facetSet['field'][$facetField] = "{!ex=$facetKey,$additionalTags}" . $facetField;
					} elseif ($isMultiSelect) {
						$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
						$facetSet['field'][$facetField] = "{!ex=$facetKey}" . $facetField;
					} elseif (!empty($additionalTags)) {
						$facetSet['field'][$facetField] = "{!ex=$additionalTags}" . $facetField;
					} else {
						$facetSet['field'][$facetField] = $facetField;
					}
				} else {
					$facetSet['field'][$facetField] = $facetInfo;
				}
			}
			if ($this->facetOffset != null) {
				$facetSet['offset'] = $this->facetOffset;
			}
			if ($this->facetLimit != null) {
				$facetSet['limit'] = $this->facetLimit;
			}
			if ($this->facetPrefix != null) {
				$facetSet['prefix'] = $this->facetPrefix;
			}
			if ($this->facetSort != null) {
				$facetSet['sort'] = $this->facetSort;
			}

			$this->facetOptions["f.series_facet.facet.mincount"] = 2;
			$this->facetOptions["f.target_audience_full.facet.method"] = 'enum';
			$this->facetOptions["f.target_audience.facet.method"] = 'enum';
			$this->facetOptions["f.literary_form_full.facet.method"] = 'enum';
			$this->facetOptions["f.literary_form.facet.method"] = 'enum';
			$this->facetOptions["f.lexile_code.facet.method"] = 'enum';
			$this->facetOptions["f.mpaa_rating.facet.method"] = 'enum';
			$this->facetOptions["f.rating_facet.facet.method"] = 'enum';
			$this->facetOptions["f.format_category.facet.method"] = 'enum';
			$this->facetOptions["f.format.facet.method"] = 'enum';
			$this->facetOptions["f.availability_toggle.facet.method"] = 'enum';
			$this->facetOptions["f.local_time_since_added_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.owning_library.facet.method"] = 'enum';
			$this->facetOptions["f.owning_location.facet.method"] = 'enum';
			foreach (SearchObject_GroupedWorkSearcher2::$scopedFields as $facetName) {
				$this->facetOptions["f.$facetName.facet.prefix"] = "$solrScope#";
			}
		}
		if (!empty($this->facetSearchTerm) && !empty($this->facetSearchField)) {
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains"] = $this->facetSearchTerm;
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains.ignoreCase"] = 'true';
		}
		if (!empty($this->facetOptions)) {
			$facetSet['additionalOptions'] = $this->facetOptions;
		}
		$timer->logTime("create facets");

		// Build our spellcheckQuery query
		if ($this->spellcheckEnabled) {
			$spellcheckQuery = $this->buildSpellingQuery();

			// If the spellcheckQuery query is purely numeric, skip it if
			// the appropriate setting is turned on.
			if (is_numeric($spellcheckQuery)) {
				$spellcheckQuery = "";
			}
		} else {
			$spellcheckQuery = "";
		}
		$timer->logTime("create spell check");

		// Get time before the query
		$this->startQueryTimer();

		// The "relevance" sort option is a VuFind reserved word; we need to make
		// this null in order to achieve the desired effect with Solr:
		$finalSort = ($this->sort == 'relevance') ? null : $this->sort;
		if ($finalSort == 'days_since_added asc') {
			$finalSort = 'local_days_since_added_' . $solrScope . ' asc';
		}

		// The first record to retrieve:
		//  (page - 1) * limit = start
		$recordStart = ($this->page - 1) * $this->limit;
		//Remove irrelevant fields based on scoping
		$fieldsToReturn = $this->getFieldsToReturn();

		$handler = $this->index;
		if (preg_match('/^\\"[^\\"]+?\\"$/', $this->query)) {
			if ($handler == 'Keyword') {
				$handler = 'KeywordProper';
			} elseif ($handler == 'Author') {
				$handler = 'AuthorProper';
			} elseif ($handler == 'Subject') {
				$handler = 'SubjectProper';
			} elseif ($handler == 'AllFields') {
				$handler = 'KeywordProper';
			} elseif ($handler == 'Title') {
				$handler = 'TitleProper';
			} elseif ($handler == 'Title') {
				$handler = 'TitleProper';
			} elseif ($handler == 'Series') {
				$handler = 'SeriesProper';
			}
		}

		//Check the filters to make sure they are for the correct scope
		$validFields = $this->loadValidFields();
		$dynamicFields = $this->loadDynamicFields();
		global $solrScope;
		if (!empty($filterQuery)) {
			if (!is_array($filterQuery)) {
				$filterQuery = [$filterQuery];
			}

			$validFilters = [];
			foreach ($filterQuery as $id => $filterTerm) {
				[
					$fieldName,
					$term,
				] = explode(":", $filterTerm, 2);
				$tagging = '';
				if (preg_match("/({!tag=.*?})\(?(.*)/", $fieldName, $matches)) {
					$tagging = $matches[1];
					$fieldName = $matches[2];
				}
				if (!in_array($fieldName, $validFields)) {
					//Field doesn't exist, check to see if it is a dynamic field
					//Where we can replace the scope with the current scope
					foreach ($dynamicFields as $dynamicField) {
						if (preg_match("/^{$dynamicField}[^_]+$/", $fieldName)) {
							//This is a dynamic field with the wrong scope
							$validFilters[$id] = $tagging . $dynamicField . $solrScope . ":" . $term;
							break;
						}
					}
				} else {
					$validFilters[$id] = $filterTerm;
				}
			}
			$filterQuery = $validFilters;
		}

		$this->indexResult = $this->indexEngine->search($this->query,      // Query string
			$handler,      // DisMax Handler
			$filterQuery,      // Filter query
			$recordStart,      // Starting record
			$this->limit,      // Records per page
			$facetSet,         // Fields to facet on
			$spellcheckQuery,       // Spellcheck query
			$this->dictionary, // Spellcheck dictionary
			$finalSort,        // Field to sort on
			$fieldsToReturn,   // Fields to return
			'POST',     // HTTP Request method
			$returnIndexErrors // Include errors in response?
		);
		$timer->logTime("run solr search");

		// Get time after the query
		$this->stopQueryTimer();

		// How many results were there?
		if (is_null($this->indexResult)) {
			//This happens with a timeout
			$this->resultsTotal = 0;
		} elseif (!isset($this->indexResult['response']['numFound'])) {
			//An error occurred
			$this->resultsTotal = 0;
		} else {
			$this->resultsTotal = $this->indexResult['response']['numFound'];
		}

		// If extra processing is needed for recommendations, do it now:
		if ($recommendations && is_array($this->recommend)) {
			foreach ($this->recommend as $currentSet) {
				/** @var RecommendationInterface $current */
				foreach ($currentSet as $current) {
					$current->process();
				}
			}
		}

		//Add debug information to the results if available
		if ($this->debug && isset($this->indexResult['debug'])) {
			$explainInfo = $this->indexResult['debug']['explain'];
			foreach ($this->indexResult['response']['docs'] as $key => $result) {
				if (array_key_exists($result['id'], $explainInfo)) {
					$result['explain'] = $explainInfo[$result['id']];
					$this->indexResult['response']['docs'][$key] = $result;
				}
			}
		}

		// Return the result set
		return $this->indexResult;
	}

	function setAppliedFilters($filterList) {
		$updatedFilterList = [];
		$facetConfig = $this->getFacetConfig();
		$validFields = $this->loadValidFields();
		foreach ($filterList as $field => $fieldValue) {
			if (isset($facetConfig[$field])) {
				$updatedFilterList[$field] = $fieldValue;
			} elseif (in_array($field, $validFields)) {
				$updatedFilterList[$field] = $fieldValue;
			} else {
				//This is likely a field we need to convert from the old schema to new schema
				$tmpFieldName = substr($field, 0, strrpos($field, '_'));
				if (isset($facetConfig[$tmpFieldName])) {
					$updatedFilterList[$tmpFieldName] = $fieldValue;
				} else {
					//Unknown field
					/** @noinspection PhpUnnecessaryStopStatementInspection */
					continue;
				}
			}
		}

		parent::setAppliedFilters($updatedFilterList); // TODO: Change the autogenerated stub
	}

	/**
	 * @param String $fields - a list of comma separated fields to return
	 */
	function setFieldsToReturn($fields) {
		$this->fieldsToReturn = $fields;
	}

	protected function getFieldsToReturn() {
		if (isset($_REQUEST['allFields'])) {
			$fieldsToReturn = '*,score';
		} elseif ($this->fieldsToReturn != null) {
			$fieldsToReturn = $this->fieldsToReturn;
		} else {
			$fieldsToReturn = SearchObject_GroupedWorkSearcher2::$fields_to_return;
			global $solrScope;
			if ($solrScope != false) {
				$fieldsToReturn .= ',local_days_since_added_' . $solrScope;
				$fieldsToReturn .= ',local_time_since_added_' . $solrScope;
				$fieldsToReturn .= ',local_callnumber_' . $solrScope;
				$fieldsToReturn .= ',scoping_details_' . $solrScope;
			} else {
				$fieldsToReturn .= ',days_since_added';
				$fieldsToReturn .= ',local_callnumber';
			}
			$fieldsToReturn .= ',collection';
			$fieldsToReturn .= ',detailed_location';
			$fieldsToReturn .= ',owning_location';
			$fieldsToReturn .= ',owning_library';
			$fieldsToReturn .= ',available_at';
			$fieldsToReturn .= ',itype';
			$fieldsToReturn .= ',score';
		}
		return $fieldsToReturn;
	}

	/**
	 * @param string $scopedFieldName
	 * @return string
	 */
	protected function getUnscopedFieldName(string $scopedFieldName): string {
		if (strpos($scopedFieldName, 'availability_toggle_') === 0) {
			$scopedFieldName = 'availability_toggle';
		} elseif (strpos($scopedFieldName, 'available_at') === 0) {
			$scopedFieldName = 'available_at';
		} elseif (strpos($scopedFieldName, 'local_time_since_added') === 0) {
			$scopedFieldName = 'local_time_since_added';
		}
		return $scopedFieldName;
	}

	/**
	 * @param $field
	 * @return string
	 */
	protected function getScopedFieldName(string $field): string {
		global $solrScope;
		if ($solrScope) {
			if ($field === 'time_since_added') {
				$field = 'local_time_since_added_' . $solrScope;
			}
			$validFields = $this->getIndexEngine()->loadValidFields();
			if (!in_array($field, $validFields)) {
				//Check to see if we need to trim off the scope
				$tmpFieldName = substr($field, 0, strrpos($field, '_'));
				if (in_array($tmpFieldName, $validFields)) {
					$field = $tmpFieldName;
				}
			}
		}
		return $field;
	}

	/**
	 * Process facets from the results object
	 *
	 * @access  public
	 * @param array $filter Array of field => on-screen description
	 *                                  listing all of the desired facet fields;
	 *                                  set to null to get all configured values.
	 * @return  array   Facets data arrays
	 */
	public function getFacetList($filter = null) {
		global $solrScope;
		global $timer;
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->getFacetConfig();
		}

		$selectedAvailableAtValues = [];
		$selectedFormatValues = [];
		$selectedFormatCategoryValues = [];
		foreach ($this->filterList as $field => $selectedValues) {
			foreach ($selectedValues as $value) {
				if ($field == 'available_at') {
					$selectedAvailableAtValues[] = $value;
				} elseif ($field == 'format_category') {
					$selectedFormatCategoryValues[] = $value;
				} elseif ($field == 'format') {
					$selectedFormatValues[] = $value;
				}
			}
		}

		// Start building the facet list:
		$list = [];

		// If we have no facets to process, give up now
		if (!isset($this->indexResult['facet_counts'])) {
			return $list;
		} elseif (!is_array($this->indexResult['facet_counts']['facet_fields'])) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		global $locationSingleton;
		/** @var Library $currentLibrary */
		$currentLibrary = Library::getActiveLibrary();
		$activeLocationFacet = null;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!is_null($activeLocation)) {
			if (empty($activeLocation->facetLabel)) {
				$activeLocationFacet = $activeLocation->displayName;
			} else {
				$activeLocationFacet = $activeLocation->facetLabel;
			}
		} else {
			//Use the main branch for the library if we have one
			$locationsForLibrary = $currentLibrary->getLocations();
			foreach ($locationsForLibrary as $tmpLocation) {
				if ($tmpLocation->isMainBranch) {
					if (empty($tmpLocation->facetLabel)) {
						$activeLocationFacet = $tmpLocation->displayName;
					} else {
						$activeLocationFacet = $tmpLocation->facetLabel;
					}
					break;
				}
			}
		}
		$relatedLocationFacets = null;
		$relatedHomeLocationFacets = null;
		$additionalAvailableAtLocations = null;
		if (!is_null($currentLibrary)) {
			if ($currentLibrary->facetLabel == '') {
				$currentLibrary->facetLabel = $currentLibrary->displayName;
			}
			$relatedLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($currentLibrary->libraryId);
			if (strlen($currentLibrary->additionalLocationsToShowAvailabilityFor) > 0) {
				$additionalAvailableAtLocations = [];
				$location = new Location();
				if ($currentLibrary->additionalLocationsToShowAvailabilityFor != ".*"){
					$locationsToLookfor = explode('|', $currentLibrary->additionalLocationsToShowAvailabilityFor);
					$location->whereAddIn('code', $locationsToLookfor, true);
				}
				$location->find();
				while ($location->fetch()) {
					if ($location->facetLabel == null){
						$location->facetLabel = $location->displayName;
					}
					$additionalAvailableAtLocations[] = $location->facetLabel;
				}
			}
		}
		$homeLibrary = Library::getPatronHomeLibrary();
		if (!is_null($homeLibrary)) {
			$relatedHomeLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($homeLibrary->libraryId);
		}

		$allFacets = $this->indexResult['facet_counts']['facet_fields'];
		/** @var FacetSetting $facetConfig */
		$facetConfig = $this->getFacetConfig();
		foreach ($allFacets as $field => $data) {
			// Skip filtered fields and empty arrays:
			if (!in_array($field, $validFields) || count($data) < 1) {
				$isValid = false;
				if (!$isValid) {
					continue;
				}
			}
			// Initialize the settings for the current field
			$list[$field] = [];
			$list[$field]['field_name'] = $field;
			// Add the on-screen label
			if (is_object($filter[$field])) {
				$list[$field]['label'] = $filter[$field]->displayName;
			} else {
				$list[$field]['label'] = $filter[$field];
			}

			// Build our array of values for this field
			$list[$field]['list'] = [];
			$list[$field]['hasApplied'] = false;
			$list[$field]['multiSelect'] = $facetConfig[$field]->multiSelect;

			$foundInstitution = false;
			$doInstitutionProcessing = false;
			$foundBranch = false;
			$doBranchProcessing = false;

			//Marmot specific processing to do custom resorting of facets.
			if (strpos($field, 'owning_library') === 0 && isset($currentLibrary) && !is_null($currentLibrary)) {
				$doInstitutionProcessing = true;
			}
			if (strpos($field, 'owning_location') === 0 || strpos($field, 'available_at') === 0) {
				$doBranchProcessing = true;
			}
			// Should we translate values for the current facet?
			$translate = $facetConfig[$field]->translate;
			$numValidRelatedLocations = 0;
			$numValidLibraries = 0;
			// Loop through values:
			$isScopedField = $this->isScopedField($field);

			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = [];
				$facetValue = $facet[0];

				if ($isScopedField && strpos($facetValue, '#') !== false) {
					$facetValue = substr($facetValue, strpos($facetValue, '#') + 1);
				}
				$currentSettings['value'] = $facetValue;
				$currentSettings['display'] = $translate ? translate([
					'text' => $facetValue,
					'isPublicFacing' => true,
					'isMetadata' => true,
					'escape' => true,
				]) : htmlentities($facetValue);
				$currentSettings['count'] = $facet[1];
				$currentSettings['isApplied'] = false;
				$currentSettings['url'] = $this->renderLinkWithFilter($field, $facetValue);

				// Is this field a current filter?
				if (in_array($field, array_keys($this->filterList))) {
					// and is this value a selected filter?
					if (in_array($facetValue, $this->filterList[$field])) {
						$currentSettings['isApplied'] = true;
						$list[$field]['hasApplied'] = true;
						$currentSettings['removalUrl'] = $this->renderLinkWithoutFilter("$field:{$facetValue}");
					}
				}

				if ($field == 'availability_toggle') {
					$currentSettings['countIsApproximate'] = (count($selectedAvailableAtValues) > 0 || count($selectedFormatCategoryValues) > 0 || count($selectedFormatValues) > 0) && $facetValue != 'global';
				} elseif ($field == 'available_at') {
					$currentSettings['countIsApproximate'] = $this->selectedAvailabilityToggleValue != 'global' || count($selectedFormatCategoryValues) > 0 || count($selectedFormatValues) > 0;
				} elseif ($field == 'format_category') {
					$currentSettings['countIsApproximate'] = $this->selectedAvailabilityToggleValue != 'global' || count($selectedAvailableAtValues) > 0 || count($selectedFormatValues) > 0;
				} elseif ($field == 'format') {
					$currentSettings['countIsApproximate'] = $this->selectedAvailabilityToggleValue != 'global' || count($selectedAvailableAtValues) > 0 || count($selectedFormatCategoryValues) > 0;
				} else {
					$currentSettings['countIsApproximate'] = false;
				}

				//Setup the key to allow sorting alphabetically if needed.
				$valueKey = $facetValue;
				$okToAdd = true;
				//Don't include empty settings since they don't work properly with Solr
				if (strlen(trim($facetValue)) == 0) {
					$okToAdd = false;
				}
				if ($doInstitutionProcessing) {
					if ($facetValue == $currentLibrary->facetLabel) {
						$valueKey = '1' . $valueKey;
						$numValidLibraries++;
						$foundInstitution = true;
					} elseif ($facetValue == $currentLibrary->facetLabel . ' On Order') {
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					} elseif ($facetValue == 'Digital Collection') {
						$valueKey = '2' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}
				} elseif ($doBranchProcessing) {
					if (strlen($facetValue) > 0) {
						if ($activeLocationFacet != null && $facetValue == $activeLocationFacet) {
							$valueKey = '1' . $valueKey;
							$foundBranch = true;
							$numValidRelatedLocations++;
						} elseif (isset($currentLibrary) && ($facetValue == $currentLibrary->facetLabel . ' On Order')) {
							$valueKey = '1' . $valueKey;
							$numValidRelatedLocations++;
						} elseif (!is_null($relatedLocationFacets) && in_array($facetValue, $relatedLocationFacets)) {
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						} elseif (!is_null($relatedHomeLocationFacets) && in_array($facetValue, $relatedHomeLocationFacets)) {
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						} elseif (!is_null($additionalAvailableAtLocations) && in_array($facetValue, $additionalAvailableAtLocations)) {
							$valueKey = '3' . $valueKey;
							$numValidRelatedLocations++;
						} else {
							$valueKey = '4' . $valueKey;
							$numValidRelatedLocations++;
						}
					}
				}


				// Store the collected values:
				if ($okToAdd) {
					$list[$field]['list'][$valueKey] = $currentSettings;
				}
			}

			if (!$foundInstitution && $doInstitutionProcessing) {
				$list[$field]['list']['1' . $currentLibrary->facetLabel] = [
					'value' => $translate ? translate([
						'text' => $currentLibrary->facetLabel,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($currentLibrary->facetLabel),
					'display' => $translate ? translate([
						'text' => $currentLibrary->facetLabel,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($currentLibrary->facetLabel),
					'count' => 0,
					'isApplied' => false,
					'url' => null,
				];
			}
			if (!$foundBranch && $doBranchProcessing && !empty($activeLocationFacet)) {
				$list[$field]['list']['1' . $activeLocationFacet] = [
					'value' => $translate ? translate([
						'text' => $activeLocationFacet,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($activeLocationFacet),
					'display' => $translate ? translate([
						'text' => $activeLocationFacet,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($activeLocationFacet),
					'count' => 0,
					'isApplied' => false,
					'url' => null,
				];
				$numValidRelatedLocations++;
			}

			if ($doBranchProcessing || $doInstitutionProcessing) {
				ksort($list[$field]['list']);
			}

			//How many facets should be shown by default
			//Only show one system unless we are in the global scope
			if ($field == 'owning_library_' . $solrScope && isset($currentLibrary)) {
				$list[$field]['valuesToShow'] = $numValidLibraries;
			} elseif ($field == 'owning_location_' . $solrScope && isset($relatedLocationFacets) && $numValidRelatedLocations > 0) {
				$list[$field]['valuesToShow'] = $numValidRelatedLocations;
			} elseif ($field == 'available_at_' . $solrScope) {
				$list[$field]['valuesToShow'] = count($list[$field]['list']);
			} else {
				$list[$field]['valuesToShow'] = 5;
			}

			//Sort the facet alphabetically?
			//Sort the system and location alphabetically unless we are in the global scope
			global $solrScope;
			if (in_array($field, [
					'owning_library_' . $solrScope,
					'owning_location_' . $solrScope,
					'available_at_' . $solrScope,
				]) && isset($currentLibrary)) {
				$list[$field]['showAlphabetically'] = true;
			} else {
				$list[$field]['showAlphabetically'] = false;
			}
			if ($list[$field]['showAlphabetically']) {
				ksort($list[$field]['list']);
			}
			$timer->logTime("Processed facet $field Translated? $translate Num values: " . count($data));
		}
		return $list;
	}

	private static $scopedFields = [
		'format_category',
		'format',
		'collection',
		'detailed_location',
		'shelf_location',
		'itype',
		'econtent_source',
		'available_at',
		'availability_toggle',
		'owning_location',
		'owning_library',
	];

	public function isScopedField($fieldName) {
		return in_array($fieldName, SearchObject_GroupedWorkSearcher2::$scopedFields);
	}

	public function getResultRecordSet() {
		$recordSet = parent::getResultRecordSet();
		foreach ($recordSet as $index => $record) {
			$recordSet[$index] = $this->cleanScopedFieldsForRecord($record);
		}
		return $recordSet;
	}

	private function cleanScopedFieldsForRecord(array $record): array {
		global $solrScope;
		$solrScopeWithSeparator = $solrScope . '#';
		$solrScopeLength = strlen($solrScopeWithSeparator);
		foreach ($record as $fieldName => &$fieldData) {
			if (in_array($fieldName, SearchObject_GroupedWorkSearcher2::$scopedFields)) {
				$scopedField = $fieldName . '_' . $solrScope;
				if (is_array($fieldData)) {
					$unscopedFieldValues = [];
					foreach ($fieldData as $valueIndex => $fieldValue) {
						if (strpos($fieldValue, $solrScopeWithSeparator) === 0) {
							$unscopedFieldValues[] = substr($fieldValue, $solrScopeLength);
						} else {
							unset($fieldData[$valueIndex]);
						}
					}
					$record[$scopedField] = $unscopedFieldValues;
					$record[$fieldName] = $unscopedFieldValues;
				} else {
					$record[$scopedField] = substr($fieldData, $solrScopeLength);
					$record[$fieldName] = substr($fieldData, $solrScopeLength);
				}
			}
		}
		return $record;
	}

	/**
	 * Retrieves a document specified by the item barcode.
	 *
	 * @param string $barcode A barcode of an item in the document to retrieve from Solr
	 * @access  public
	 * @return  string              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByBarcode($barcode) {
		$recordData = $this->indexEngine->getRecordByBarcode($barcode);
		if ($recordData != null) {
			$recordData = $this->cleanScopedFieldsForRecord($recordData);
		}
		return $recordData;
	}

	/**
	 * Retrieves a document specified by an isbn.
	 *
	 * @param string[] $isbn An array of isbns to check
	 * @access  public
	 * @return  string              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByIsbn($isbn) {
		$recordData = $this->indexEngine->getRecordByIsbn($isbn, $this->getFieldsToReturn());
		if ($recordData != null) {
			$recordData = $this->cleanScopedFieldsForRecord($recordData);
		}
		return $recordData;
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string $id The document to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resource
	 * @throws  AspenError
	 */
	function getRecord($id) {
		$recordData = $this->indexEngine->getRecord($id, $this->getFieldsToReturn());
		if ($recordData != null) {
			$recordData = $this->cleanScopedFieldsForRecord($recordData);
		}
		return $recordData;
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resources
	 * @throws  AspenError
	 */
	function getRecords($ids) {
		$recordsRaw = $this->indexEngine->getRecords($ids, $this->getFieldsToReturn());
		foreach ($recordsRaw as $index => $recordRaw) {
			$recordsRaw[$index] = $this->getRecordDriverForResult($recordRaw);
		}
		return $recordsRaw;
	}

	public function getRecordDriverForResult($record) {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$record = $this->cleanScopedFieldsForRecord($record);
		return new GroupedWorkDriver($record);
	}
}
