<?php

require_once ROOT_DIR . '/sys/SearchObject/AbstractGroupedWorkSearcher.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';

class SearchObject_GroupedWorkSearcher extends SearchObject_AbstractGroupedWorkSearcher
{
	// Field List
	public static $fields_to_return = 'auth_author2,author2-role,id,mpaaRating,title_display,title_full,title_short,subtitle_display,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,num_titles,record_details,item_details,publisherStr,publishDate,publishDateSort,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,display_description,fountas_pinnell,last_indexed,lc_subject,bisac_subject';

	// Optional, used on author screen for example
	private $searchSubType = '';

	// Display Modes //
	public $viewOptions = array('list', 'covers');

	private $fieldsToReturn = null;

	/**
	 * Constructor. Initialise some details about the server
	 *
	 * @access  public
	 */
	public function __construct()
	{
		// Call base class constructor
		parent::__construct();

		global $configArray;
		global $timer;
		require_once ROOT_DIR . "/sys/SolrConnector/GroupedWorksSolrConnector.php";
		// Initialise the index
		$this->indexEngine = new GroupedWorksSolrConnector($configArray['Index']['url']);
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
		$this->sortOptions = array(
			'relevance' => 'Best Match',
			'year desc,title asc' => "Publication Year Desc",
			'year asc,title asc' => "Publication Year Asc",
			'author asc,title asc' => "Author",
			'title' => 'Title',
			'days_since_added asc' => "Date Purchased Desc",
			'callnumber_sort' => 'sort_callnumber',
			'popularity desc' => 'sort_popularity',
			'rating desc' => 'sort_rating',
			'total_holds desc' => "Number of Holds"
		);

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
	 * @return  array
	 */
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
	{
		global $timer;
		global $solrScope;

		if ($this->searchSource == 'econtent') {
			$this->addHiddenFilter("econtent_source_{$solrScope}", '*');
		}

		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendations module appropriate to the current search:
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
		$dynamicFields = $this->loadDynamicFields();
		foreach ($this->filterList as $field => $filter) {
			if ($field === '') {
				unset($this->filterList[$field]);
			}
			if (strpos($field, '_') !== false) {
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

		$availabilityToggleValue = null;
		$availabilityAtValues = [];
		$formatValues = [];
		$formatCategoryValues = [];
		$facetConfig = $this->getFacetConfig();
		$formatsAreMultiSelect = false;
		foreach ($this->filterList as $field => $filter) {
			$fieldPrefix = "";
			$multiSelect = false;
			if (isset($facetConfig[$field])) {
				/** @var FacetSetting $facetInfo */
				$facetInfo = $facetConfig[$field];
				if ($facetInfo->multiSelect) {
					$facetKey = empty($facetInfo->id) ? $facetInfo->facetName : $facetInfo->id;
					$fieldPrefix = "{!tag={$facetKey}}";
					$multiSelect = true;
				}
			}
			$fieldValue = "";
			foreach ($filter as $value) {
				$isAvailabilityToggle = false;
				$isAvailableAt = false;
				if (strpos($field, 'availability_toggle') === 0) {
					$availabilityToggleValue = $value;
					$isAvailabilityToggle = true;
				} elseif (strpos($field, 'available_at') === 0) {
					$availabilityAtValues[] = $value;
					$isAvailableAt = true;
				} elseif (strpos($field, 'format_category') === 0) {
					$formatCategoryValues[] = $value;
				} elseif (strpos($field, 'format') === 0) {
					$formatValues[] = $value;
					$formatsAreMultiSelect = $multiSelect;
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

						if ($isAvailabilityToggle || $isAvailableAt) {
							$okToAdd = true;
							$value = "\"$value\"";
						} else {
							//The value is already specified as field:value
							if (is_numeric($field)) {
								$filterQuery[] = $value;
							} else {
								$okToAdd = true;
								$value = "\"$value\"";
							}
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
						if ($isAvailabilityToggle) {
							$filterQuery['availability_toggle_' . $solrScope] = "$fieldPrefix$field:$value";
						} elseif ($isAvailableAt) {
							$filterQuery['available_at_' . $solrScope] = "$fieldPrefix$field:$value";
						} else {
							$filterQuery[] = "$fieldPrefix$field:$value";
						}
					}
				}
			}
			if ($multiSelect) {
				$filterQuery[] = "$fieldPrefix$field:($fieldValue)";
			}
		}

		//Check to see if we should apply a default filter
		if ($availabilityToggleValue == null){
			global $library;
			$location = Location::getSearchLocation(null);
			if ($location != null){
				$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
			}else{
				$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			}
			$availabilityToggleValue = $groupedWorkDisplaySettings->defaultAvailabilityToggle;

			$filterQuery['availability_toggle_'. $solrScope] = "availability_toggle_{$solrScope}:\"{$availabilityToggleValue}\"";
		}

		//Check to see if we have both a format and availability facet applied.
		$availabilityByFormatFieldNames = [];
		if ($availabilityToggleValue != null && (!empty($formatCategoryValues) || !empty($formatValues))) {
			global $solrScope;
			//Make sure to process the more specific format first
			if ($formatsAreMultiSelect) {
				$formatFilters = [];
				foreach ($formatValues as $formatValue) {
					$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
					$formatFilters[] = $availabilityByFormatFieldName . ':"' . $availabilityToggleValue . '"';
					$availabilityByFormatFieldNames[] = $availabilityByFormatFieldName;
				}
				$filterQuery[] = '(' . implode(' OR ', $formatFilters) . ')';
			}else{
				foreach ($formatValues as $formatValue) {
					$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
					$filterQuery[] = $availabilityByFormatFieldName . ':"' . $availabilityToggleValue . '"';
					$availabilityByFormatFieldNames[] = $availabilityByFormatFieldName;
				}
			}
			foreach ($formatCategoryValues as $formatCategoryValue) {
				$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatCategoryValue));
				$filterQuery[] = $availabilityByFormatFieldName . ':"' . $availabilityToggleValue . '"';
				$availabilityByFormatFieldNames[] = $availabilityByFormatFieldName;
			}
			unset($filterQuery['availability_toggle_'. $solrScope]);
		}

		//Check to see if we have both a format and available at facet applied
		$availableAtByFormatFieldName = null;
		if (!empty($availabilityAtValues) && (!empty($formatCategoryValues) || !empty($formatValues))) {
			global $solrScope;
			$availabilityByFormatFilter = "";
			if (!empty($formatValues)) {
				$availabilityByFormatFilter .= '(';
				foreach ($formatValues as $formatValue) {
					$availabilityByFormatFieldName = 'available_at_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
					foreach ($availabilityAtValues as $index => $availabilityAtValue) {
						if ($index > 0) {
							$availabilityByFormatFilter .= ' OR ';
						}
						$availabilityByFormatFilter .= $availabilityByFormatFieldName . ':"' . $availabilityAtValue . '"';
					}
				}
				$availabilityByFormatFilter .= ')';
			}
			if (!empty($formatCategoryValues)) {
				if (strlen($availabilityByFormatFilter) > 0) {
					$availabilityByFormatFilter .= ' OR ';
				}
				$availabilityByFormatFilter .= '(';
				foreach ($formatCategoryValues as $formatCategoryValue) {
					$availabilityByFormatFieldName = 'available_at_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatCategoryValue));
					foreach ($availabilityAtValues as $index => $availabilityAtValue) {
						if ($index > 0) {
							$availabilityByFormatFilter .= ' OR ';
						}
						$availabilityByFormatFilter .= $availabilityByFormatFieldName . ':"' . $availabilityAtValue . '"';
					}
				}
				$availabilityByFormatFilter .= ')';
			}
			$filterQuery[] = $availabilityByFormatFilter;
			unset($filterQuery['available_at']);
		}


		// If we are only searching one field use the DisMax handler
		//    for that field. If left at null let solr take care of it
		if (count($search) == 1 && isset($search[0]['index'])) {
			$this->index = $search[0]['index'];
		}

		// Build a list of facets we want from the index
		$facetSet = array();
		$facetConfig = $this->getFacetConfig();
		if ($recommendations && !empty($facetConfig)) {
			$facetSet['limit'] = $this->facetLimit;
			foreach ($facetConfig as $facetField => $facetInfo) {
				if ($facetInfo instanceof FacetSetting) {
					$facetName = $facetInfo->facetName;
					if (strpos($facetName, 'availability_toggle') === 0) {
						if (!empty($availabilityByFormatFieldName)) {
							foreach ($availabilityByFormatFieldNames as $availabilityByFormatFieldName) {
								$facetSet['field'][$facetField] = $facetField;
							}
						} else {
							$facetSet['field'][$facetField] = $facetField;
						}
					}else {
						if ($facetInfo->multiSelect) {
							$facetKey = empty($facetInfo->id) ? $facetInfo->facetName : $facetInfo->id;
							$facetSet['field'][$facetField] = "{!ex={$facetKey}}" . $facetField;
						} elseif (strpos($facetName, 'availability_toggle') === 0 || strpos($facetName, 'availability_by_format') === 0) {
							$facetSet['field'][$facetField] = '{!ex=avail}' . $facetField;
						} else {
							$facetSet['field'][$facetField] = $facetField;
						}
					}
				} else {
					$options['facet.field'][] = $facetInfo;
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
			$this->facetOptions["f.format_category_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.format_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.availability_toggle_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.local_time_since_added_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.owning_library_$solrScope.facet.method"] = 'enum';
			$this->facetOptions["f.owning_location_$solrScope.facet.method"] = 'enum';

			if (isset($searchLibrary) && $searchLibrary->showAvailableAtAnyLocation) {
				$this->facetOptions['f.available_at.facet.missing'] = 'true';
			}
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
		if ($finalSort == 'days_since_added asc'){
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
			} else if ($handler == 'Author') {
				$handler = 'AuthorProper';
			} else if ($handler == 'Subject') {
				$handler = 'SubjectProper';
			} else if ($handler == 'AllFields') {
				$handler = 'KeywordProper';
			} else if ($handler == 'Title') {
				$handler = 'TitleProper';
			} else if ($handler == 'Title') {
				$handler = 'TitleProper';
			} else if ($handler == 'Series') {
				$handler = 'SeriesProper';
			}
		}

		//Check the filters to make sure they are for the correct scope
		$validFields = $this->loadValidFields();
		$dynamicFields = $this->loadDynamicFields();
		global $solrScope;
		if (!empty($filterQuery)) {
			if (!is_array($filterQuery)) {
				$filterQuery = array($filterQuery);
			}

			$validFilters = array();
			foreach ($filterQuery as $id => $filterTerm) {
				list($fieldName, $term) = explode(":", $filterTerm, 2);
				$tagging = '';
				if (preg_match("/({!tag=\d+})\(?(.*)/", $fieldName, $matches)) {
					$tagging = $matches[1];
					$fieldName = $matches[2];
				}
				if (!in_array($fieldName, $validFields)) {
					//Special handling for availability_by_format
					if (preg_match("/availability_by_format_([^_]+)_[\\w_]+$/", $fieldName)) {
						//This is a valid field
						$validFilters[$id] = $filterTerm;
					} elseif (preg_match("/available_at_by_format_([^_]+)_[\\w_]+$/", $fieldName)) {
						//This is a valid field
						$validFilters[$id] = $filterTerm;
					} else {
						//Field doesn't exist, check to see if it is a dynamic field
						//Where we can replace the scope with the current scope
						foreach ($dynamicFields as $dynamicField) {
							if (preg_match("/^{$dynamicField}[^_]+$/", $fieldName)) {
								//This is a dynamic field with the wrong scope
								$validFilters[$id] = $tagging . $dynamicField . $solrScope . ":" . $term;
								break;
							}
						}
					}
				} else {
					$validFilters[$id] = $filterTerm;
				}
			}
			$filterQuery = $validFilters;
		}

		$this->indexResult = $this->indexEngine->search(
			$this->query,      // Query string
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
		} else if (!isset($this->indexResult['response']['numFound'])) {
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

	protected function getFieldsToReturn()
	{
		if (isset($_REQUEST['allFields'])) {
			$fieldsToReturn = '*,score';
		}elseif ($this->fieldsToReturn != null) {
			$fieldsToReturn = $this->fieldsToReturn;
		} else {
			$fieldsToReturn = SearchObject_GroupedWorkSearcher::$fields_to_return;
			global $solrScope;
			if ($solrScope != false) {
				//$fieldsToReturn .= ',related_record_ids_' . $solrScope;
				//$fieldsToReturn .= ',related_items_' . $solrScope;
				$fieldsToReturn .= ',format_' . $solrScope;
				$fieldsToReturn .= ',format_category_' . $solrScope;
				$fieldsToReturn .= ',collection_' . $solrScope;
				$fieldsToReturn .= ',local_days_since_added_' . $solrScope;
				$fieldsToReturn .= ',local_time_since_added_' . $solrScope;
				$fieldsToReturn .= ',local_callnumber_' . $solrScope;
				$fieldsToReturn .= ',detailed_location_' . $solrScope;
				$fieldsToReturn .= ',scoping_details_' . $solrScope;
				$fieldsToReturn .= ',owning_location_' . $solrScope;
				$fieldsToReturn .= ',owning_library_' . $solrScope;
				$fieldsToReturn .= ',available_at_' . $solrScope;
				$fieldsToReturn .= ',itype_' . $solrScope;

			} else {
				//$fieldsToReturn .= ',related_record_ids';
				//$fieldsToReturn .= ',related_record_items';
				//$fieldsToReturn .= ',related_items_related_record_ids';
				$fieldsToReturn .= ',format';
				$fieldsToReturn .= ',format_category';
				$fieldsToReturn .= ',days_since_added';
				$fieldsToReturn .= ',local_callnumber';
				$fieldsToReturn .= ',detailed_location';
				$fieldsToReturn .= ',owning_location';
				$fieldsToReturn .= ',owning_library';
				$fieldsToReturn .= ',available_at';
				$fieldsToReturn .= ',itype';
			}
			$fieldsToReturn .= ',score';
		}
		return $fieldsToReturn;
	}
}