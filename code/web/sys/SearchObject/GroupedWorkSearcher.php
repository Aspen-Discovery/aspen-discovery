<?php

require_once ROOT_DIR . '/sys/SearchObject/AbstractGroupedWorkSearcher.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';

class SearchObject_GroupedWorkSearcher extends SearchObject_AbstractGroupedWorkSearcher
{
	// Field List
	public static $fields_to_return = 'auth_author2,author2-role,id,mpaaRating,title_display,title_full,title_short,subtitle_display,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,num_titles,record_details,item_details,publisherStr,publishDate,publishDateSort,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,display_description,fountas_pinnell,last_indexed,lc_subject,bisac_subject';

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

	/**
	 * @param String $fields - a list of comma separated fields to return
	 */
	function setFieldsToReturn($fields){
		$this->fieldsToReturn = $fields;
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
				$fieldsToReturn .= ',collection_group';
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

	/**
	 * @param string $scopedFieldName
	 * @return string
	 */
	protected function getUnscopedFieldName(string $scopedFieldName): string
	{
		if (strpos($scopedFieldName, 'availability_toggle_') === 0) {
			$scopedFieldName = 'availability_toggle';
		} elseif (strpos($scopedFieldName, 'format') === 0) {
			$scopedFieldName = 'format';
		} elseif (strpos($scopedFieldName, 'format_category') === 0) {
			$scopedFieldName = 'format_category';
		} elseif (strpos($scopedFieldName, 'econtent_source') === 0) {
			$scopedFieldName = 'econtent_source';
		} elseif (strpos($scopedFieldName, 'shelf_location') === 0) {
			$scopedFieldName = 'shelf_location';
		} elseif (strpos($scopedFieldName, 'detailed_location') === 0) {
			$scopedFieldName = 'detailed_location';
		} elseif (strpos($scopedFieldName, 'owning_location') === 0) {
			$scopedFieldName = 'owning_location';
		} elseif (strpos($scopedFieldName, 'owning_library') === 0) {
			$scopedFieldName = 'owning_library';
		} elseif (strpos($scopedFieldName, 'available_at') === 0) {
			$scopedFieldName = 'available_at';
		} elseif (strpos($scopedFieldName, 'local_time_since_added') === 0) {
			$scopedFieldName = 'local_time_since_added';
		} elseif (strpos($scopedFieldName, 'itype') === 0) {
			$scopedFieldName = 'itype';
		} elseif (strpos($scopedFieldName, 'collection') === 0) {
			$scopedFieldName = 'collection_group';
		}
		return $scopedFieldName;
	}

	/**
	 * @param $field
	 * @return string
	 */
	protected function getScopedFieldName($field): string
	{
		global $solrScope;
		if ($solrScope) {
			if ($field === 'availability_toggle') {
				$field = 'availability_toggle_' . $solrScope;
			} elseif ($field === 'format') {
				$field = 'format_' . $solrScope;
			} elseif ($field === 'format_category') {
				$field = 'format_category_' . $solrScope;
			} elseif ($field === 'econtent_source') {
				$field = 'econtent_source_' . $solrScope;
			} elseif ($field === 'shelf_location') {
				$field = 'shelf_location_' . $solrScope;
			} elseif ($field === 'detailed_location') {
				$field = 'detailed_location_' . $solrScope;
			} elseif ($field === 'owning_location') {
				$field = 'owning_location_' . $solrScope;
			} elseif ($field === 'owning_library') {
				$field = 'owning_library_' . $solrScope;
			} elseif ($field === 'available_at') {
				$field = 'available_at_' . $solrScope;
			} elseif ($field === 'time_since_added') {
				$field = 'local_time_since_added_' . $solrScope;
			} elseif ($field === 'itype') {
				$field = 'itype_' . $solrScope;
			} elseif ($field === 'shelf_location') {
				$field = 'shelf_location_' . $solrScope;
			} elseif ($field === 'detailed_location') {
				$field = 'detailed_location_' . $solrScope;
			} elseif ($field === 'collection_group' || $field === 'collection') {
				$field = 'collection_' . $solrScope;
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
	public function getFacetList($filter = null)
	{
		global $solrScope;
		global $timer;
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->getFacetConfig();
		}

		// Start building the facet list:
		$list = array();

		// If we have no facets to process, give up now
		if (!isset($this->indexResult['facet_counts'])) {
			return $list;
		} elseif (!is_array($this->indexResult['facet_counts']['facet_fields'])) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		/** @var Location $locationSingleton */
		global $locationSingleton;
		/** @var Library $currentLibrary */
		$currentLibrary = Library::getActiveLibrary();
		$activeLocationFacet = null;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!is_null($activeLocation)) {
			if (empty($activeLocation->facetLabel)){
				$activeLocationFacet = $activeLocation->displayName;
			}else{
				$activeLocationFacet = $activeLocation->facetLabel;
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
				$locationsToLookfor = explode('|', $currentLibrary->additionalLocationsToShowAvailabilityFor);
				$location = new Location();
				$location->whereAddIn('code', $locationsToLookfor, true);
				$location->find();
				$additionalAvailableAtLocations = array();
				while ($location->fetch()) {
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
				//Check to see if we are overriding availability toggle
				if (strpos($field, 'availability_by_format') === 0) {
					foreach ($validFields as $validFieldName) {
						if (strpos($validFieldName, 'availability_toggle') === 0) {
							$field = $validFieldName;
							$isValid = true;
							break;
						}
					}
				}
				if (!$isValid) {
					continue;
				}
			}
			// Initialize the settings for the current field
			$list[$field] = array();
			$list[$field]['field_name'] = $field;
			// Add the on-screen label
			$list[$field]['label'] = $filter[$field];
			// Build our array of values for this field
			$list[$field]['list'] = array();
			$list[$field]['hasApplied'] = false;
			$foundInstitution = false;
			$doInstitutionProcessing = false;
			$foundBranch = false;
			$doBranchProcessing = false;

			//Marmot specific processing to do custom resorting of facets.
			if (strpos($field, 'owning_library') === 0 && isset($currentLibrary) && !is_null($currentLibrary)) {
				$doInstitutionProcessing = true;
			}
			if (strpos($field, 'owning_location') === 0 && (!is_null($relatedLocationFacets) || !is_null($activeLocationFacet))) {
				$doBranchProcessing = true;
			} elseif (strpos($field, 'available_at') === 0) {
				$doBranchProcessing = true;
			}
			// Should we translate values for the current facet?
			$translate = $facetConfig[$field]->translate;
			$numValidRelatedLocations = 0;
			$numValidLibraries = 0;
			// Loop through values:
			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = array();
				$currentSettings['value'] = $facet[0];
				$currentSettings['display'] = $translate ? translate(['text'=>$facet[0],'isPublicFacing'=>true]) : $facet[0];
				$currentSettings['count'] = $facet[1];
				$currentSettings['isApplied'] = false;
				$currentSettings['url'] = $this->renderLinkWithFilter($field, $facet[0]);

				// Is this field a current filter?
				if (in_array($field, array_keys($this->filterList))) {
					// and is this value a selected filter?
					if (in_array($facet[0], $this->filterList[$field])) {
						$currentSettings['isApplied'] = true;
						$list[$field]['hasApplied'] = true;
						$currentSettings['removalUrl'] = $this->renderLinkWithoutFilter("$field:{$facet[0]}");
					}
				}

				//Setup the key to allow sorting alphabetically if needed.
				$valueKey = $facet[0];
				$okToAdd = true;
				//Don't include empty settings since they don't work properly with Solr
				if (strlen(trim($facet[0])) == 0){
					$okToAdd = false;
				}
				if ($doInstitutionProcessing) {
					if ($facet[0] == $currentLibrary->facetLabel) {
						$valueKey = '1' . $valueKey;
						$numValidLibraries++;
						$foundInstitution = true;
					} elseif ($facet[0] == $currentLibrary->facetLabel . ' Online') {
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					} elseif ($facet[0] == $currentLibrary->facetLabel . ' On Order' || $facet[0] == $currentLibrary->facetLabel . ' Under Consideration') {
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					} elseif ($facet[0] == 'Digital Collection') {
						$valueKey = '2' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}
				} else if ($doBranchProcessing) {
					if (strlen($facet[0]) > 0) {
						if ($activeLocationFacet != null && $facet[0] == $activeLocationFacet) {
							$valueKey = '1' . $valueKey;
							$foundBranch = true;
							$numValidRelatedLocations++;
						} elseif (isset($currentLibrary) && $facet[0] == $currentLibrary->facetLabel . ' Online') {
							$valueKey = '1' . $valueKey;
							$numValidRelatedLocations++;
						} elseif (isset($currentLibrary) && ($facet[0] == $currentLibrary->facetLabel . ' On Order' || $facet[0] == $currentLibrary->facetLabel . ' Under Consideration')) {
							$valueKey = '1' . $valueKey;
							$numValidRelatedLocations++;
						} else if (!is_null($relatedLocationFacets) && in_array($facet[0], $relatedLocationFacets)) {
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						} else if (!is_null($relatedLocationFacets) && in_array($facet[0], $relatedLocationFacets)) {
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						} else if (!is_null($relatedHomeLocationFacets) && in_array($facet[0], $relatedHomeLocationFacets)) {
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						} elseif (!is_null($currentLibrary) && $facet[0] == $currentLibrary->facetLabel . ' Online') {
							$valueKey = '3' . $valueKey;
							$numValidRelatedLocations++;
						} else if ($field == 'available_at' && !is_null($additionalAvailableAtLocations) && in_array($facet[0], $additionalAvailableAtLocations)) {
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
				$list[$field]['list']['1' . $currentLibrary->facetLabel] =
					array(
						'value' => $currentLibrary->facetLabel,
						'display' => $currentLibrary->facetLabel,
						'count' => 0,
						'isApplied' => false,
						'url' => null,
					);
			}
			if (!$foundBranch && $doBranchProcessing && !empty($activeLocationFacet)) {
				$list[$field]['list']['1' . $activeLocationFacet] =
					array(
						'value' => $activeLocationFacet,
						'display' => $activeLocationFacet,
						'count' => 0,
						'isApplied' => false,
						'url' => null,
					);
				$numValidRelatedLocations++;
			}

			//How many facets should be shown by default
			//Only show one system unless we are in the global scope
			if ($field == 'owning_library_' . $solrScope && isset($currentLibrary)) {
				$list[$field]['valuesToShow'] = $numValidLibraries;
			} else if ($field == 'owning_location_' . $solrScope && isset($relatedLocationFacets) && $numValidRelatedLocations > 0) {
				$list[$field]['valuesToShow'] = $numValidRelatedLocations;
			} else if ($field == 'available_at_' . $solrScope) {
				$list[$field]['valuesToShow'] = count($list[$field]['list']);
			} else {
				$list[$field]['valuesToShow'] = 5;
			}

			//Sort the facet alphabetically?
			//Sort the system and location alphabetically unless we are in the global scope
			global $solrScope;
			if (in_array($field, array('owning_library_' . $solrScope, 'owning_location_' . $solrScope, 'available_at_' . $solrScope)) && isset($currentLibrary)) {
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
}