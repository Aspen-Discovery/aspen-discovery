<?php

require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/Recommend/RecommendationFactory.php';

/**
 * Search Object abstract base class.
 *
 * Generic base class for abstracting search URL generation and other standard
 * functionality.  This should be extended to implement functionality for specific
 * modules (Grouped Works, Lists, OAI, Archives, etc)
 */
abstract class SearchObject_BaseSearcher
{
	// Parsed query
	protected $query = null;

	// SEARCH PARAMETERS
	// RSS feed?
	protected $view = null;
	protected $defaultView = 'list';
	// Search terms
	protected $searchTerms = array();
	// Sorting
	protected $sort = null;
	protected $defaultSort = 'relevance';
	protected $defaultSortByType = array();
	/** @var string */
	protected $searchSource = 'local';

	// Filters
	protected $filterList = array();
	// Facets information
	protected $allFacetSettings = array();
	// Page number
	protected $page = 1;
	// Result limit
	protected $limit = 20;

	// Used to pass hidden filter queries to Solr
	protected $hiddenFilters = array();

	//Limiters applied to the search
	protected $limiters = array();

	// STATS
	protected $resultsTotal = 0;

	// OTHER VARIABLES
	// Module and Action for building search results URLs
	protected $resultsModule = 'Search';
	protected $resultsAction = 'Results';
	// Facets information
	protected $facetConfig;    // Array of valid facet fields=>labels
	protected $facetOptions = array();
	// Available sort options
	protected $sortOptions = array();
	// An ID number for saving/retrieving search
	protected $searchId = null;
	protected $savedSearch = false;
	protected $searchType = 'basic';
	// Possible values of $searchType:
	protected $isAdvanced = false;
	protected $basicSearchType = 'basic';
	protected $advancedSearchType = 'advanced';
	// Flag for logging/search history
	protected $disableLogging = false;

	protected $isPrimarySearch = false;
	// Search options for the user
	protected $advancedTypes = array();
	protected $searchIndexes = array();

	// Recommendation modules associated with the search:
	/** @var bool|array $recommend */
	protected $recommend = false;

	// STATS
	protected $initTime = null;
	protected $endTime = null;
	protected $totalTime = null;

	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

	/**
	 * Constructor. Initialise some details about the server
	 *
	 * @access  public
	 */
	public function __construct()
	{
		global $timer;

		$timer->logTime('Setup Base Search Object');
	}

	function ping()
	{
		return true;
	}

	/* Parse apart the field and value from a URL filter string.
	 *
	 * @access  protected
	 * @param   string  $filter     A filter string from url : "field:value"
	 * @return  array               Array with elements 0 = field, 1 = value.
	 */
	protected function parseFilter($filter)
	{
		if ((strpos($filter, ' AND ') !== FALSE) || (strpos($filter, ' OR ') !== FALSE)) {
			//This is a complex filter that does not need parsing
			return array('', $filter);
		}
		// Split the string
		$temp = explode(':', $filter);
		// $field is the first value
		$field = array_shift($temp);
		// join them in case the value contained colons as well.
		$value = join(":", $temp);

		// Remove quotes from the value if there are any
		if (substr($value, 0, 1) == '"') $value = substr($value, 1);
		if (substr($value, -1, 1) == '"') $value = substr($value, 0, -1);
		// One last little clean on whitespace
		$value = trim($value);

		// Send back the results:
		return array($field, $value);
	}

	/**
	 * @return string
	 */
	public function getSearchSource()
	{
		return $this->searchSource;
	}

	public abstract function getEngineName();

	/**
	 * @return array
	 */
	public function getHiddenFilters()
	{
		return $this->hiddenFilters;
	}

	/**
	 * Does the object already contain the specified filter?
	 *
	 * @access  public
	 * @param string $filterName The name of the filter
	 * @param string $value The value of the filter
	 * @return  bool
	 */
	public function hasFilter($filterName, $value)
	{
		if (isset($this->filterList[$filterName]) && in_array($value, $this->filterList[$filterName])) {
			return true;
		}
		return false;
	}

	public function clearFilters()
	{
		$this->filterList = array();
	}


	/**
	 * Take a filter string and add it into the protected
	 *   array checking for duplicates.
	 *
	 * @access  public
	 * @param string|array $newFilter A filter string from url : "field:value"
	 */
	public function addFilter($newFilter)
	{
		if (is_array($newFilter)) {
			$field = $newFilter[0];
			$value = $newFilter[1];
		} else {
			if (strlen($newFilter) == 0) {
				return;
			}
			// Extract field and value from URL string:
			list($field, $value) = $this->parseFilter($newFilter);
		}

		if ($field == '') {
			$field = count($this->filterList) + 1;
		}

		// Check for duplicates -- if it's not in the array, we can add it
		if (!$this->hasFilter($field, $value)) {
			if (!is_numeric($field)) {
				if ($field === 'literary-form') {
					$field = 'literary_form';
				} else if ($field === 'literary-form-full') {
					$field = 'literary_form_full';
				} else if ($field === 'target-audience') {
					$field = 'target_audience';
				} else if ($field === 'target-audience-full') {
					$field = 'target_audience_full';
				}

				$field = $this->getScopedFieldName($field);
			}

			$this->filterList[$field][] = $value;
		}
	}

	/**
	 * Remove a filter from the list.
	 *
	 * @access  public
	 * @param string $oldFilter A filter string from url : "field:value"
	 */
	public function removeFilter($oldFilter)
	{
		// Extract field and value from URL string:
		list($field, $value) = $this->parseFilter($oldFilter);

		// Make sure the field exists
		if (isset($this->filterList[$field])) {
			// Loop through all filters on the field
			foreach ($this->filterList[$field] as $key => $existingValue) {
				// Does it contain the value we don't want?
				if ($existingValue == $value) {
					// If so remove it.
					unset($this->filterList[$field][$key]);
				}
			}
		}
	}

	public function clearHiddenFilters()
	{
		$this->hiddenFilters = array();
	}

	public function addHiddenFilter($field, $value)
	{
		$this->hiddenFilters[] = $field . ':' . $value;
	}

	/**
	 * Get a user-friendly string to describe the provided facet field.
	 *
	 * @access  protected
	 * @param string $field Facet field name.
	 * @return  string                          Human-readable description of field.
	 */
	protected function getFacetLabel($field)
	{
		$shortField = $field;
		$shortField = $this->getUnscopedFieldName($shortField);
		$facetConfig = $this->getFacetConfig();
		if (isset($facetConfig[$field])) {
			$facetConfig = $facetConfig[$field];
			if ($facetConfig instanceof FacetSetting) {
				return $facetConfig->displayName;
			} else {
				return $facetConfig;
			}
		} elseif (isset($facetConfig[$shortField])) {
			$facetConfig = $facetConfig[$shortField];
			if ($facetConfig instanceof FacetSetting) {
				return $facetConfig->displayName;
			} else {
				return $facetConfig;
			}
		} else {
			return ucwords(str_replace("_", " ", translate(['text'=>$shortField,'isPublicFacing'=>true])));
		}
	}

	/**
	 * Clear all facets which will speed up searching if we won't be using the facets.
	 */
	public function clearFacets()
	{
		$this->facetConfig = array();
	}

	public function hasAppliedFacets()
	{
		return count($this->filterList) > 0;
	}

	/**
	 * Return an array structure containing all current filters
	 *    and urls to remove them.
	 *
	 * @access  public
	 * @return  array    Field, values and removal urls
	 */
	public function getFilterList()
	{
		$list = array();
		$facetConfig = $this->getFacetConfig();
		// Loop through all the current filter fields
		foreach ($this->filterList as $field => $values) {
			// and each value currently used for that field
			$translate = false;
			if (isset($facetConfig[$field])){
				if (is_object($facetConfig[$field])){
					$translate = $facetConfig[$field]->translate;
				}else{
					$translate = in_array($field, $facetConfig['translated_facets']);
				}
			}

			foreach ($values as $value) {
				$facetLabel = $this->getFacetLabel($field);
				if ($field == 'available_at' && $value == '*') {
					$anyLocationLabel = $this->getFacetSetting("Availability", "anyLocationLabel");
					$display = $anyLocationLabel == '' ? "Any Marmot Location" : $anyLocationLabel;
				} elseif ($field == 'available_at' && $value == '*') {
					$anyLocationLabel = $this->getFacetSetting("Availability", "anyLocationLabel");
					$display = $anyLocationLabel == '' ? "Any Marmot Location" : $anyLocationLabel;
				} else {
					$display = $translate ? translate(['text'=>$value,'isPublicFacing'=>true]) : $value;
				}

				$list[$facetLabel][] = array(
					'value' => $value,     // raw value for use with Solr
					'display' => $display,   // version to display to user
					'field' => $field,
					'removalUrl' => $this->renderLinkWithoutFilter("$field:$value")
				);
			}
		}
		return $list;
	}

	/**
	 * Return the specified setting from the configuration file.
	 *
	 * @access  public
	 * @param string $section The section of the configuration file to look at.
	 * @param string $setting The setting within the specified file to return.
	 * @return  string    The value of the setting (blank if none).
	 */
	public function getFacetSetting($section, $setting)
	{
		return isset($this->allFacetSettings[$section][$setting]) ?
			$this->allFacetSettings[$section][$setting] : '';
	}


	/**
	 * Return a url for the current search with an additional filter
	 *
	 * @access  public
	 * @param string $field The name of the field to filter on
	 * @param string $filterValue The value to filter on
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithFilter($field, $filterValue)
	{
		// Stash our old data for a minute
		$oldFilterList = $this->filterList;
		$oldPage = $this->page;
		// Availability facet can have only one item selected at a time
		if (strpos($field, 'availability_toggle') === 0) {
			foreach ($this->filterList as $name => $value) {
				if (strpos($name, 'availability_toggle') === 0) {
					unset ($this->filterList[$name]);
				}
			}
		}
		// Add the new filter
		if ($filterValue != null) {
			$this->addFilter([$field, $filterValue]);
		}
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->filterList = $oldFilterList;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Return a url for the current search without one of the current filters
	 *
	 * @access  public
	 * @param string $oldFilter A filter to remove from the search url
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithoutFilter($oldFilter)
	{
		return $this->renderLinkWithoutFilters(array($oldFilter));
	}

	/**
	 * Return a url for the current search without several of the current filters
	 *
	 * @access  public
	 * @param array $filters The filters to remove from the search url
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithoutFilters($filters)
	{
		// Stash our old data for a minute
		$oldFilterList = $this->filterList;
		$oldPage = $this->page;
		// Remove the old filter
		foreach ($filters as $oldFilter) {
			$this->removeFilter($oldFilter);
		}
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->filterList = $oldFilterList;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Get the base URL for search results (including ? parameter prefix).
	 *
	 * @access  protected
	 * @return  string   Base URL
	 */
	protected function getBaseUrl()
	{
		return "/{$this->resultsModule}/{$this->resultsAction}?";
	}

	/**
	 * Get the URL to load a saved search from the current module.
	 *
	 * @access  public
	 * @param string $id The Id of the saved search
	 * @return  string   Saved search URL.
	 */
	public function getSavedUrl($id)
	{
		return $this->getBaseUrl() . 'saved=' . urlencode($id);
	}

	/**
	 * Get an array of strings to attach to a base URL in order to reproduce the
	 * current search.
	 *
	 * @access  protected
	 * @return  array    Array of URL parameters (key=url_encoded_value format)
	 */
	protected function getSearchParams()
	{
		$params = array();
		switch ($this->searchType) {
			// Advanced search
			case $this->advancedSearchType:
				if (false) {
					// Advanced Search Pop-up (probably)
					// structure lookfor[]
					$paramIndex = 0;
					for ($i = 0; $i < count($this->searchTerms); $i++) {
						for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
							$paramIndex++;
							$params[] = "lookfor[$paramIndex]=" . urlencode($this->searchTerms[$i]['group'][$j]['lookfor']);
							$params[] = "searchType[$paramIndex]=" . urlencode($this->searchTerms[$i]['group'][$j]['field']);
							$params[] = "join[$paramIndex]=" . urlencode($this->searchTerms[$i]['group'][$j]['bool']);
						}
						if ($i > 0) {
							$params[] = "groupEnd[$paramIndex]=1";
						}
					}
				} else {
					// Advanced Search Page
					//structure lookfor0[], lookfor1[],
					$params[] = "join=" . urlencode($this->searchTerms[0]['join']);
					for ($i = 0; $i < count($this->searchTerms); $i++) {
						$params[] = "bool" . $i . "[]=" . urlencode($this->searchTerms[$i]['group'][0]['bool']);
						for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
							$params[] = "lookfor" . $i . "[]=" . urlencode($this->searchTerms[$i]['group'][$j]['lookfor']);
							$params[] = "type" . $i . "[]=" . urlencode($this->searchTerms[$i]['group'][$j]['field']);
						}
					}
				}
				break;
			// Basic search
			default:
				if (isset($this->searchTerms[0]['lookfor'])) {
					$params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
				}
				if (isset($this->searchTerms[0]['index'])) {
					if ($this->searchType == 'basic') {
						$params[] = "searchIndex=" . urlencode($this->searchTerms[0]['index']);
					} else {
						$params[] = "type=" . urlencode($this->searchTerms[0]['index']);
					}

				}
				break;
		}
		return $params;
	}

	/**
	 * Initialize the object's search settings for a basic search found in the
	 * $_REQUEST super global.
	 *
	 * @access  public
	 * @param String|String[] $searchTerm
	 * @return  boolean  True if search settings were found, false if not.
	 */
	public function initBasicSearch($searchTerm = null)
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		if ($searchTerm == null) {
			// If no lookfor parameter was found, we have no search terms to
			// add to our array!
			if (!isset($_REQUEST['lookfor'])) {
				return false;
			} else {
				$searchTerm = StringUtils::removeTrailingPunctuation(trim($_REQUEST['lookfor']));
			}
		}else{
			$searchTerm = StringUtils::removeTrailingPunctuation(trim($searchTerm));
		}

		// If no type defined use default
		if ((isset($_REQUEST['searchIndex'])) && ($_REQUEST['searchIndex'] != '')) {
			$type = $_REQUEST['searchIndex'];

			// Flatten type arrays for backward compatibility:
			if (is_array($type)) {
				$type = strip_tags($type[0]);
			} else {
				$type = strip_tags($type);
			}

			//The type should never have punctuation in it (quotes, colons, etc)
			$type = preg_replace('/[:"\']/', '', $type);

			if (!array_key_exists($type, $this->getSearchIndexes()) && !array_key_exists($type, $this->advancedTypes)){
				$type = $this->getDefaultIndex();
			}
		} else {
			$type = $this->getDefaultIndex();
		}

		if (strpos($searchTerm, ':') > 0) {
			$tempSearchInfo = explode(':', $searchTerm);
			if (count($tempSearchInfo) == 2){
				//Check for leading and trailing parentheses
				if (strlen($tempSearchInfo[0]) > 0 && $tempSearchInfo[0][0] == '('){
					$tempSearchInfo[0] = substr($tempSearchInfo[0], 1);
				}
				if (strlen($tempSearchInfo[1]) > 0 && $tempSearchInfo[1][-1] == ')'){
					$tempSearchInfo[1] = substr($tempSearchInfo[1], 0, -1);
				}

				if (array_key_exists($tempSearchInfo[0], $this->searchIndexes)) {
					$type = $tempSearchInfo[0];
					$searchTerm = $tempSearchInfo[1];
				}else{
					$validFields = $this->loadValidFields();
					$dynamicFields = $this->loadDynamicFields();
					if (!in_array($tempSearchInfo[0], $validFields) && !in_array($tempSearchInfo[0], $dynamicFields) || array_key_exists($tempSearchInfo[0], $this->advancedTypes)) {
						$searchTerm = str_replace(':', ' ', $searchTerm);
					}else{
						return false;
					}
				}
			}else{
				//This is an advanced search
				return false;
			}
		}

		$this->searchTerms[] = array(
			'index' => $type,
			'lookfor' => $searchTerm
		);
		return true;
	}

	/**
	 * Initialize the object's search settings for a basic search found in the
	 * $_REQUEST super global.
	 *
	 * @access  public
	 * @param String $searchIndex
	 * @param String $searchTerm
	 * @return  boolean  True if search settings were found, false if not.
	 */
	public function initBasicSearchWithIndex($searchIndex, $searchTerm)
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$searchTerm = StringUtils::removeTrailingPunctuation(trim($searchTerm));

		$type = $searchIndex;

		// Flatten type arrays for backward compatibility:
		if (is_array($type)) {
			$type = strip_tags($type[0]);
		} else {
			$type = strip_tags($type);
		}

		//The type should never have punctuation in it (quotes, colons, etc)
		$type = preg_replace('/[:"\']/', '', $type);

		if (!array_key_exists($type, $this->getSearchIndexes()) && !array_key_exists($type, $this->advancedTypes)){
			$type = $this->getDefaultIndex();
		}

		if (strpos($searchTerm, ':') > 0) {
			$tempSearchInfo = explode(':', $searchTerm);
			if (count($tempSearchInfo) == 2){
				//Check for leading and trailing parentheses
				if (strlen($tempSearchInfo[0]) > 0 && $tempSearchInfo[0][0] == '('){
					$tempSearchInfo[0] = substr($tempSearchInfo[0], 1);
				}
				if (strlen($tempSearchInfo[1]) > 0 && $tempSearchInfo[1][-1] == ')'){
					$tempSearchInfo[1] = substr($tempSearchInfo[1], 0, -1);
				}

				if (array_key_exists($tempSearchInfo[0], $this->searchIndexes)) {
					$type = $tempSearchInfo[0];
					$searchTerm = $tempSearchInfo[1];
				}else{
					$validFields = $this->loadValidFields();
					$dynamicFields = $this->loadDynamicFields();
					if (!in_array($tempSearchInfo[0], $validFields) && !in_array($tempSearchInfo[0], $dynamicFields) || array_key_exists($tempSearchInfo[0], $this->advancedTypes)) {
						$searchTerm = str_replace(':', ' ', $searchTerm);
					}else{
						return false;
					}
				}
			}else{
				//This is an advanced search
				return false;
			}
		}

		$this->searchTerms[] = array(
			'index' => $type,
			'lookfor' => $searchTerm
		);
		return true;
	}

	public function setSearchTerms($searchTerms)
	{
		$this->searchTerms = array();
		$this->searchTerms[] = $searchTerms;
	}

	public function isAdvanced()
	{
		return $this->isAdvanced;
	}

	/**
	 * Initialize the object's search settings for an advanced search found in the
	 * $_REQUEST super global.  Advanced searches have numeric subscripts on the
	 * lookfor and type parameters -- this is how they are distinguished from basic
	 * searches.
	 *
	 * @access  protected
	 */
	protected function initAdvancedSearch()
	{
		$this->isAdvanced = true;
		$this->searchType = $this->advancedSearchType;
		if (isset($_REQUEST['lookfor'])) {
			if (is_array($_REQUEST['lookfor'])) {
				//Advanced search from popup form
				$this->searchType = $this->advancedSearchType;
				$group = array();
				foreach ($_REQUEST['lookfor'] as $index => $lookfor) {
					$group[] = array(
						'field' => $_REQUEST['searchType'][$index],
						'lookfor' => $lookfor,
						'bool' => $_REQUEST['join'][$index]
					);

					if (isset($_REQUEST['groupEnd'])) {
						if (isset($_REQUEST['groupEnd'][$index]) && $_REQUEST['groupEnd'][$index] == 1) {
							// Add the completed group to the list
							$this->searchTerms[] = array(
								'group' => $group,
								'join' => $_REQUEST['join'][$index]
							);
							$group = array();
						}
					}
				}
				if (count($group) > 0 && !empty($index)) {
					// Add the completed group to the list
					$this->searchTerms[] = array(
						'group' => $group,
						'join' => $_REQUEST['join'][$index]
					);
				}
			}else{
				if (strpos($_REQUEST['lookfor'], ':') > 0) {
					$tempSearchInfo = explode(':', $_REQUEST['lookfor']);
					if (count($tempSearchInfo) == 2){
						//Check for leading and trailing parentheses
						if ($tempSearchInfo[0][0] == '('){
							$tempSearchInfo[0] = substr($tempSearchInfo[0], 1);
						}
						if ($tempSearchInfo[1][-1] == ')'){
							$tempSearchInfo[1] = substr($tempSearchInfo[1], 0, -1);
						}
						$validFields = $this->loadValidFields();
						$dynamicFields = $this->loadDynamicFields();
						if (in_array($tempSearchInfo[0], $validFields) || in_array($tempSearchInfo[0], $dynamicFields) || array_key_exists($tempSearchInfo[0], $this->advancedTypes)) {
							$group[] = array(
								'field' => $tempSearchInfo[0],
								'lookfor' => $tempSearchInfo[1],
								'bool' => 'AND'
							);
						}else {
							$group[] = array(
								'field' => $this->getDefaultIndex(),
								'lookfor' => str_replace(':', ' ', $_REQUEST['lookfor']),
								'bool' => 'AND'
							);
						}
						$this->searchTerms[] = array(
							'group' => $group,
							'join' => 'AND'
						);
					}else{
						//TODO: This needs to create multiple groups for the search.
						preg_match_all('~((\w+?):("?.+?"?)(AND|OR|\)|$))~', $_REQUEST['lookfor'], $matches, PREG_SET_ORDER);
						foreach ($matches as $match){
							$group[] = array(
								'field' => $match[2],
								'lookfor' => str_replace(':', ' ', $match[3]),
								'bool' => ($match[4] == ')') ? 'AND' : $match[4]
							);
						}
						$this->searchTerms[] = array(
							'group' => $group,
							'join' => 'AND'
						);
					}
				}
			}

		} else {
			//********************
			// Advanced Search logic
			//  'lookfor0[]' 'type0[]'
			//  'lookfor1[]' 'type1[]' ...
			$this->searchType = $this->advancedSearchType;
			$groupCount = 0;
			// Loop through each search group
			while (isset($_REQUEST['lookfor' . $groupCount])) {
				$group = array();
				// Loop through each term inside the group
				for ($i = 0, $l = count($_REQUEST['lookfor' . $groupCount]); $i < $l; $i++) {
					// Ignore advanced search fields with no lookup
					if ($_REQUEST['lookfor' . $groupCount][$i] != '') {
						// Use default fields if not set
						if (!empty($_REQUEST['type' . $groupCount][$i])) {
							$type = strip_tags($_REQUEST['type' . $groupCount][$i]);
						} else {
							$type = $this->getDefaultIndex();
						}

						//Marmot - search both ISBN-10 and ISBN-13
						//Check to see if the search term looks like an ISBN10 or ISBN13
						$lookfor = strip_tags($_REQUEST['lookfor' . $groupCount][$i]);

						// Add term to this group
						$group[] = array(
							'field' => $type,
							'lookfor' => $lookfor,
							'bool' => isset($_REQUEST['bool' . $groupCount]) ? strip_tags($_REQUEST['bool' . $groupCount][0]) : 'AND'
						);
					}
				}

				// Make sure we aren't adding groups that had no terms
				if (count($group) > 0) {
					// Add the completed group to the list
					$this->searchTerms[] = array(
						'group' => $group,
						'join' => isset($_REQUEST['join']) ? (is_array($_REQUEST['join']) ? strip_tags(reset($_REQUEST['join'])) : strip_tags($_REQUEST['join'])) : 'AND'
					);
				}

				// Increment
				$groupCount++;
			}

			// Finally, if every advanced row was empty
			if (empty($this->searchTerms)) {
				// Treat it as an empty basic search
				$this->searchType = $this->basicSearchType;
				$this->searchTerms[] = array(
					'index' => $this->getDefaultIndex(),
					'lookfor' => ''
				);
			}
		}
	}

	/**
	 * Add view mode to the object based on the $_REQUEST super global.
	 *
	 * @access  protected
	 */
	protected function initView()
	{
		if (!empty($this->view)) { //return view if it has already been set.
			return;
		}
		// Check for a view parameter in the url.
		if (isset($_REQUEST['view'])) {
			if ($_REQUEST['view'] == 'rss') {
				// we don't want to store rss in the Session variable
				$this->view = 'rss';
			} elseif ($_REQUEST['view'] == 'excel') {
				// we don't want to store excel in the Session variable
				$this->view = 'excel';
			} else {
				// store non-rss views in Session for persistence
				$validViews = $this->getViewOptions();
				// make sure the url parameter is a valid view
//				if (in_array($_REQUEST['view'], array_keys($validViews))) {
				if (in_array($_REQUEST['view'], $validViews)) { // currently using a simple array listing the views (not listed in the keys)
					$this->view = $_REQUEST['view'];
					$_SESSION['lastView'] = $this->view;
				} else {
					$this->view = $this->defaultView;
				}
			}
		} elseif (isset($_SESSION['lastView']) && !empty($_SESSION['lastView'])) {
			// if there is nothing in the URL, check the Session variable
			$this->view = $_SESSION['lastView'];
		} else {
			// otherwise load the default
			$this->view = $this->defaultView;
		}
	}

	/**
	 * Add page number to the object based on the $_REQUEST super global.
	 *
	 * @access  protected
	 */
	protected function initPage()
	{
		if (isset($_REQUEST['page'])) {
			$page = $_REQUEST['page'];
			if (is_array($page)) {
				$page = array_pop($page);
			}
			$this->page = strip_tags($page);
		}
		$this->page = intval($this->page);
		if ($this->page < 1) {
			$this->page = 1;
		}
	}

	function setPage($page)
	{
		$this->page = intval($page);
		if ($this->page < 1) {
			$this->page = 1;
		}
	}

	/**
	 * Add sort value to the object based on the $_REQUEST super global.
	 *
	 * @access  protected
	 */
	protected function initSort()
	{
		$defaultSort = '';
		if (isset($_REQUEST['sort'])) {
			if (is_array($_REQUEST['sort'])) {
				$sort = array_pop($_REQUEST['sort']);
			} else {
				$sort = $_REQUEST['sort'];
			}
			$this->sort = $sort;
		} else if ($defaultSort != '') {
			$this->sort = $defaultSort;
		} else {
			// Is there a search-specific sort type set?
			$type = isset($_REQUEST['searchIndex']) ? $_REQUEST['searchIndex'] : false;
			if ($type && isset($this->defaultSortByType[$type])) {
				$this->sort = $this->defaultSortByType[$type];
				// If no search-specific sort type was found, use the overall default:
			} else {
				$this->sort = $this->defaultSort;
			}
		}
		//Validate the sort to make sure it is correct.
		if (!array_key_exists($this->sort, $this->getSortOptions())) {
			$this->sort = $this->defaultSort;
		}
	}

	public function setSort($sort)
	{
		$this->sort = $sort;
	}

	protected function initLimiters(){
		if (isset($_REQUEST['limiters'])){
			if (is_array($_REQUEST['limiters'])) {
				foreach ($_REQUEST['limiters'] as $limiter) {
					if (!is_array($limiter)) {
						list($limiterName, $limiterValue) = explode(':', strip_tags($limiter));
						$this->limiters[$limiterName] = $limiterValue;
					}
				}
			} else {
				list($limiterName, $limiterValue) = explode(':', strip_tags($_REQUEST['limiters']));
				$this->limiters[$limiterName] = $limiterValue;
			}
		}
	}

	/**
	 * Add filters to the object based on values found in the $_REQUEST super global.
	 *
	 * @access  protected
	 */
	protected function initFilters()
	{
		if (isset($_REQUEST['filter'])) {
			if (is_array($_REQUEST['filter'])) {
				foreach ($_REQUEST['filter'] as $filter) {
					if (!is_array($filter)) {
						$this->addFilter(strip_tags($filter));
					}
				}
			} else {
				$this->addFilter(strip_tags($_REQUEST['filter']));
			}
		} else {
			//Check for locked filters
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$lockedFacets = !empty($user->lockedFacets) ? json_decode($user->lockedFacets, true) : [];
			} else {
				$lockedFacets = isset($_SESSION['lockedFilters']) ? $_SESSION['lockedFilters'] : [];
			}
			if (!empty($lockedFacets) && !empty($lockedFacets[$this->getSearchName()])) {
				$lockedFilters = $lockedFacets[$this->getSearchName()];
				foreach ($lockedFilters as $fieldName => $values) {
					foreach ($values as $value) {
						$this->addFilter($fieldName . ':' . $value);
					}
				}
			}
		}
	}

	/**
	 * Build a url for the current search
	 *
	 * @access  public
	 * @return  string   URL of a search
	 */
	public function renderSearchUrl()
	{
		// Get the base URL and initialize the parameters attached to it:
		$url = $this->getBaseUrl();
		$params = $this->getSearchParams();

		// Add any filters
		if (count($this->filterList) > 0) {
			foreach ($this->filterList as $field => $filter) {
				foreach ($filter as $value) {
					if (empty($value) || $value == '""'){
						$params[] = "filter[]=$field:(\"\")";
					} else if (preg_match('/\\[.*?\\sTO\\s.*?\\]/', $value)) {
						$params[] = "filter[]=$field:$value";
					} elseif (preg_match('/^\\(.*?\\)$/', $value)) {
						$params[] = "filter[]=$field:$value";
					} else {
						if (is_numeric($field)) {
							$params[] = "filter[]=" . urlencode($value);
						} else {
							$params[] = "filter[]=" . urlencode("$field:\"$value\"");
						}
					}
				}
			}
		}

		if (!empty($this->limiters)){
			foreach ($this->limiters as $limit => $value){
				$params[] = "limiters[]=$limit%3A$value";
			}
		}

		// Sorting
		if ($this->sort != null) {
			$params[] = "sort=" . urlencode($this->sort);
		}

		// Page number
		if ($this->page != 1) {
			// Don't url encode if it's the paging template
			if ($this->page == '%d') {
				$params[] = "page=" . $this->page;
				// Otherwise... encode to prevent XSS.
			} else {
				$params[] = "page=" . urlencode($this->page);
			}
		}

		// View
		if ($this->view != null) {
			$params[] = "view=" . urlencode($this->view);
		} else if (isset($_REQUEST['view'])) {
			$view = $_REQUEST['view'];
			if (is_array($view)) {
				$view = array_pop($view);
			}
			$params[] = "view=" . urlencode($view);
		}

		if ($this->searchSource) {
			$params[] = "searchSource=" . $this->searchSource;
		}

		// Join all parameters with an escaped ampersand,
		//   add to the base url and return
		return $url . join("&", $params);
	}

	/**
	 * Return a url for use by pagination template
	 *
	 * @access  public
	 * @return  string   URL of a new search
	 */
	public function renderLinkPageTemplate()
	{
		// Stash our old data for a minute
		$oldPage = $this->page;
		// Add the page template
		$this->page = '%d';
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Return a url for the current search with a new sort
	 *
	 * @access  public
	 * @param string $newSort A field to sort by
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithSort($newSort)
	{
		// Stash our old data for a minute
		$oldSort = $this->sort;
		// Add the new sort
		$this->sort = $newSort;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->sort = $oldSort;
		// Return the URL
		return $url;
	}

	/**
	 * Return a list of urls for sorting, along with which option
	 *    should be currently selected.
	 *
	 * @access  public
	 * @return  array    Sort urls, descriptions and selected flags
	 */
	public function getSortList()
	{
		// Loop through all the current filter fields
		$valid = $this->getSortOptions();
		$list = array();
		foreach ($valid as $sort => $desc) {
			$list[$sort] = array(
				'sortUrl' => $this->renderLinkWithSort($sort),
				'desc' => $desc,
				'selected' => ($sort == $this->sort)
			);
		}
		return $list;
	}

	/**
	 * Return a url for the current search with a new view
	 *
	 * @param string $newView The new view
	 *
	 * @return string         URL of a new search
	 * @access public
	 */
	public function renderLinkWithView($newView)
	{
		// Stash our old data for a minute
		$oldView = $this->view;
		// Add the new view
		$this->view = $newView;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->view = $oldView;
		// Return the URL
		return $url;
	}

	/**
	 * Return a list of urls for possible views, along with which option
	 *    should be currently selected.
	 *
	 * @return array View urls, descriptions and selected flags
	 * @access public
	 */
	public function getViewList()
	{
		// Loop through all the current views
		$valid = $this->getViewOptions();
		$list = array();
		foreach ($valid as $view => $desc) {
			$list[$view] = array(
				'viewType' => $view,
				'viewUrl' => $this->renderLinkWithView($view),
				'desc' => $desc,
				'selected' => ($view == $this->view)
			);
		}
		return $list;
	}

	/**
	 * Return a url for the current search with a new limit (number of results)
	 *
	 * @param string $newLimit The new limit
	 *
	 * @return string         URL of a new search
	 * @access public
	 */
	public function renderLinkWithLimit($newLimit)
	{
		// Stash our old data for a minute
		$oldLimit = $this->limit;
		$oldPage = $this->page;
		// Add the new limit
		$this->limit = $newLimit;
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->limit = $oldLimit;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Return a url for the current search with a new limiter (checkbox)
	 *
	 * @param string $newLimit The new limit
	 *
	 * @return string         URL of a new search
	 * @access public
	 */
	public function renderLinkWithLimiter($newLimit)
	{
		// Stash our old data for a minute
		$oldLimiters = $this->limiters;
		$oldPage = $this->page;
		// Add the new limit
		$this->limiters[$newLimit] = 'y';
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->limiters = $oldLimiters;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Return a url for the current search with a new limiter (checkbox)
	 *
	 * @param string $newLimit The new limit
	 *
	 * @return string         URL of a new search
	 * @access public
	 */
	public function renderLinkWithoutLimiter($newLimit)
	{
		// Stash our old data for a minute
		$oldLimiters = $this->limiters;
		$oldPage = $this->page;
		// Add the new limit
		$this->limiters[$newLimit] = 'n';

		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->limiters = $oldLimiters;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Return a list of urls for possible limits, along with which option
	 *    should be currently selected.
	 *
	 * @return array Limit urls, descriptions and selected flags
	 * @access public
	 */
	public function getLimitList()
	{
		// Loop through all the current limits
		$valid = $this->getLimitOptions();
		$list = array();
		if (is_array($valid) && count($valid) > 0) {
			foreach ($valid as $limit) {
				$list[$limit] = array(
					'url' => $this->renderLinkWithLimit($limit),
					'display' => $limit,
					'value' => $limit,
					'isApplied' => ($limit == $this->limit)
				);
			}
		}
		return $list;
	}

	/**
	 * Basic 'getters'
	 *
	 * @access  public
	 * @return  mixed    various internal variables
	 */
	public function getAdvancedTypes()
	{
		return $this->advancedTypes;
	}

	public abstract function getSearchIndexes();

	public function getFilters()
	{
		return $this->filterList;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function getLimit()
	{
		return $this->limit;
	}

	public function getQuerySpeed()
	{
		return $this->queryTime;
	}

	public function getResultTotal()
	{
		return $this->resultsTotal;
	}

	public function getSearchId()
	{
		return $this->searchId;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function getSearchTerms()
	{
		return $this->searchTerms;
	}

	public function getSearchType()
	{
		return $this->searchType;
	}

	public function getSort()
	{
		return $this->sort;
	}

	public function getFullSearchType()
	{
		if ($this->isAdvanced) {
			return $this->searchType;
		} else {
			return $this->searchType . ' - ' . $this->getSearchIndex();
		}
	}

	public function getStartTime()
	{
		return $this->initTime;
	}

	public function getTotalSpeed()
	{
		return $this->totalTime;
	}

	public function getView()
	{
		return $this->view;
	}

	public function isSavedSearch()
	{
		return $this->savedSearch;
	}

	/**
	 * Protected 'getters' for values not intended for use outside the class.
	 *
	 * @access  protected
	 * @return  mixed    various internal variables
	 */
	protected function getSortOptions()
	{
		return $this->sortOptions;
	}

	/**
	 * Get an array of view options; protected since this should not be used
	 * outside of the class.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getViewOptions()
	{
		if (isset($this->viewOptions) && is_array($this->viewOptions)) {
			return $this->viewOptions;
		} else {
			return array();
		}
	}

	/**
	 * Get an array of limit options; protected since this should not be used
	 * outside of the class.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getLimitOptions()
	{
		return isset($this->limitOptions) ? $this->limitOptions : array();
	}

	/**
	 * Reset a simple query against the default index.
	 *
	 * @access  public
	 * @param string $query Query string
	 * @param string $index Index to search (exclude to use default)
	 */
	public function setBasicQuery($query, $index = null)
	{
		if (is_null($index)) {
			$index = $this->getDefaultIndex();
		}
		$this->searchTerms = array();
		$this->searchTerms[] = array(
			'index' => $index,
			'lookfor' => $query
		);
	}

	/**
	 * Set the number of search results returned per page.
	 *
	 * @access  public
	 * @param int $limit New page limit value
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}

	public function setSearchSource($searchSource)
	{
		$this->searchSource = $searchSource;
	}

	/**
	 * Add a field to facet on.
	 *
	 * @access  public
	 * @param string $newField Field name
	 * @param string|FacetSetting $newAlias Optional on-screen display label
	 */
	public function addFacet($newField, $newAlias = null)
	{
		if ($newAlias == null) {
			$newAlias = $newField;
		}
		$this->facetConfig[$newField] = $newAlias;
	}

	public function addFacetOptions($options)
	{
		$this->facetOptions = $options;
	}

	/**
	 * Return an array of data summarising the results of a search.
	 *
	 * @access  public
	 * @return  array   summary of results
	 */
	public function getResultSummary()
	{
		$summary = array();

		$summary['page'] = $this->page;
		$summary['perPage'] = $this->limit;
		$summary['resultTotal'] = $this->resultsTotal;
		// 1st record is easy, work out the start of this page
		$summary['startRecord'] = (($this->page - 1) * $this->limit) + 1;
		// Last record needs more care
		if ($this->resultsTotal < $this->limit) {
			// There are less records returned than one page, then use total results
			$summary['endRecord'] = $this->resultsTotal;
		} elseif (($this->page * $this->limit) > $this->resultsTotal) {
			// The end of the current page runs past the last record, use total results
			$summary['endRecord'] = $this->resultsTotal;
		} else {
			// Otherwise use the last record on this page
			$summary['endRecord'] = $this->page * $this->limit;
		}

		return $summary;
	}

	/**
	 * Returns the stored list of facets for the last search
	 *
	 * @access  public
	 * @param array $filter Array of field => on-screen description
	 *                                  listing all of the desired facet fields;
	 *                                  set to null to get all configured values.
	 * @return  array   Facets data arrays
	 */
	public function getFacetList(/** @noinspection PhpUnusedParameterInspection */ $filter = null)
	{
		// Assume no facets by default -- child classes can override this to extract
		// the necessary details from the results saved by processSearch().
		return array();
	}

	/**
	 * Disable logging. Used to stop administrative searches
	 *    appearing in search histories
	 *
	 * @access  public
	 */
	public function disableLogging()
	{
		$this->disableLogging = true;
	}

	/**
	 * Used during repeated deminification (such as search history).
	 *   To scrub fields populated above.
	 *
	 * @access  protected
	 */
	protected function purge()
	{
		$this->searchType = $this->basicSearchType;
		$this->searchId = null;
		$this->resultsTotal = null;
		$this->filterList = null;
		$this->initTime = null;
		$this->queryTime = null;
		// An array so we don't have to initialise
		//   the empty array during population.
		$this->searchTerms = array();
	}

	/**
	 * Create a minified copy of this object for storage in the database.
	 *
	 * @access  protected
	 * @return  object     A SearchObject instance
	 */
	protected function minify()
	{
		// Clone this object as a minified object
		return new minSO($this);
	}

	/**
	 * Initialise the object from a minified one stored in a cookie or database.
	 *  Needs to be kept up-to-date with the minSO object at the end of this file.
	 *
	 * @access  public
	 * @param object $minified A minSO object
	 */
	public function deminify($minified)
	{
		// Clean the object
		$this->purge();

		// Most values will transfer without changes
		if (isset($minified->q)) {
			$this->query = $minified->q;
		}
		$this->searchId = $minified->id;
		$this->initTime = $minified->i;
		$this->queryTime = $minified->s;
		$this->resultsTotal = $minified->r;
		$this->filterList = $minified->f;
		$this->searchType = $minified->ty;
		$this->searchSource = $minified->ss;
		$this->sort = $minified->sr;

		// Search terms, we need to expand keys
		$tempTerms = $minified->t;
		foreach ($tempTerms as $term) {
			$newTerm = array();
			foreach ($term as $k => $v) {
				switch ($k) {
					case 'j' :
						$newTerm['join'] = $v;
						break;
					case 'i' :
						$newTerm['index'] = $v;
						break;
					case 'l' :
						$newTerm['lookfor'] = $v;
						break;
					case 'g' :
						$newTerm['group'] = array();
						foreach ($v as $line) {
							$search = array();
							foreach ($line as $k2 => $v2) {
								switch ($k2) {
									case 'b' :
										$search['bool'] = $v2;
										break;
									case 'f' :
										$search['field'] = $v2;
										break;
									case 'l' :
										$search['lookfor'] = $v2;
										break;
								}
							}
							$newTerm['group'][] = $search;
						}
						break;
				}
			}
			$this->searchTerms[] = $newTerm;
		}
	}

	/**
	 * Add into the search table (history)
	 *
	 * @access  protected
	 */
	protected function addToHistory()
	{
		$thisSearchUrl = $this->renderSearchUrl();
		$s = new SearchEntry();
		//Get the active search within the history, looking at the URL for speed instead of deminifying everything
		$previouslySavedSearch = $s->getSavedSearchByUrl($thisSearchUrl, session_id(), UserAccount::isLoggedIn() ? UserAccount::getActiveUserId() : null);
		if ($previouslySavedSearch != null) {
			$this->searchId = $previouslySavedSearch->id;
			if ($previouslySavedSearch->saved) {
				$this->savedSearch = true;
			} else {
				//Update creation date if needed
				if ($previouslySavedSearch->created != date('Y-m-d')) {
					$previouslySavedSearch->created = date('Y-m-d');
					$previouslySavedSearch->update();
				}
			}
		} else {
			// Get the list of all old searches for this session and/or user
			/** @var SearchEntry[] $searchHistory */
			$searchHistory = $s->getSearchesWithNullUrl($this->searchSource, session_id(), UserAccount::isLoggedIn() ? UserAccount::getActiveUserId() : null);

			// Duplicate elimination
			$dupSaved = false;
			foreach ($searchHistory as $oldSearch) {
				// Deminify the old search
				$minSO = unserialize($oldSearch->search_object);
				$dupSearch = SearchObjectFactory::deminify($minSO);
				// See if the classes and urls match
				if ($oldSearch->searchUrl == null) {
					$oldSearch->searchUrl = $dupSearch->renderSearchUrl();
					$oldSearch->update();
				}
				if ((get_class($dupSearch) == get_class($this)) && ($oldSearch->searchUrl == $thisSearchUrl)) {
					// Is the older search saved?
					if ($oldSearch->saved) {
						// Flag for later
						$dupSaved = true;
						// Record the details
						$this->searchId = $oldSearch->id;
						$this->savedSearch = true;
					} else {
						// Delete the old search
						$oldSearch->delete();
					}
				}
			}

			// Save this search unless we found a 'saved' duplicate
			if (!$dupSaved) {
				$search = new SearchEntry();
				$search->session_id = session_id();
				$search->created = date('Y-m-d');
				$search->searchSource = $this->searchSource;
				$search->search_object = serialize($this->minify());
				$search->searchUrl = $this->renderSearchUrl();

				//MDN 10/10/2019 had cases where user was passing in invalid sources which
				//caused an error on insert.  Just ignore for now, but should eventually do more validation.
				if (strlen($search->searchSource) <= 30) {
					$search->insert();
					// Record the details
					$this->searchId = $search->id;
					$this->savedSearch = false;

					// Chicken and egg... We didn't know the id before insert
					$search->search_object = serialize($this->minify());
					$search->update();
				}
			}
		}
	}

	public function loadLastSearch()
	{
		if (isset($_SESSION['lastSearchId']) && is_numeric($_SESSION['lastSearchId'])) {
			$lastSearchId = $_SESSION['lastSearchId'];
		} else {
			//No search to load, get out
			return null;
		}
		// Yes, retrieve it
		$search = new SearchEntry();
		$search->id = $lastSearchId;
		if ($search->find(true)) {
			// Found, make sure the user has the
			//   rights to view this search
			$currentSessionId = session_id();
			if ($search->session_id == $currentSessionId || $search->user_id == UserAccount::getActiveUserId()) {
				// They do, deminify it to a new object.
				$minSO = unserialize($search->search_object);
				return SearchObjectFactory::deminify($minSO);
			} else {
				// Just get out, we don't need to show an error
				return null;
			}
		}
		return null;
	}

	/**
	 * If there is a saved search being loaded through $_REQUEST, redirect to the
	 * URL for that search.  If no saved search was requested, return false.  If
	 * unable to load a requested saved search, return a AspenError object.
	 *
	 * @access  protected
	 *
	 * @var     string $searchId
	 * @var     boolean $redirect
	 * @var     boolean $forceReload
	 *
	 * @return  mixed               Does not return on successful load, returns
	 *                              false if no search to restore, returns
	 *                              AspenError object in case of trouble.
	 */
	public function restoreSavedSearch($searchId = null, $redirect = true, $forceReload = false)
	{
		// Is this is a saved search?
		if (isset($_REQUEST['saved']) || $searchId != null) {
			// Yes, retrieve it
			$search = new SearchEntry();
			$search->id = strip_tags(isset($_REQUEST['saved']) ? $_REQUEST['saved'] : $searchId);
			if ($search->find(true)) {
				// Found, make sure the user has the
				//   rights to view this search
				if ($forceReload || $search->session_id == session_id() || (UserAccount::isLoggedIn() && $search->user_id == UserAccount::getActiveUserId())) {
					// They do, deminify it to a new object.
					$minSO = unserialize($search->search_object);
					$savedSearch = SearchObjectFactory::deminify($minSO);

					// Now redirect to the URL associated with the saved search;
					// this simplifies problems caused by mixing different classes
					// of search object, and it also prevents the user from ever
					// landing on a "?saved=xxxx" URL, which may not persist beyond
					// the current session.  (We want all searches to be
					// persistent and able to be bookmarked).
					if ($redirect) {
						header('Location: ' . $savedSearch->renderSearchUrl());
						die();
					} else {
						return $savedSearch;
					}
				} else {
					// They don't
					// TODO : Error handling -
					//    User is trying to view a saved search from
					//    another session (deliberate or expired) or
					//    associated with another user.
					return new AspenError("Attempt to access invalid search ID");
				}
			}
		}

		// Report no saved search to restore.
		return false;
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @return  boolean
	 * @var string $searchSource
	 */
	public function init($searchSource = null)
	{
		// Start the timer
		$mtime = explode(' ', microtime());
		$this->initTime = $mtime[1] + $mtime[0];

		$this->searchSource = $searchSource;
		return true;
	}

	/**
	 * An optional de-initialise function.
	 *
	 *   At this stage it's used for finish of timing calculations
	 *   and logged search history.
	 *
	 *   Possible future uses will including closing child resources if
	 *     required (database connections) or finish database writes
	 *     (audit tables). Remember the destructor() can also be used
	 *     for mandatory stuff.
	 *   Might need parameters for logging level and whether to keep
	 *     the search in history etc.
	 *
	 * @access  public
	 */
	public function close()
	{
		// Finish timing
		$mtime = explode(" ", microtime());
		$this->endTime = $mtime[1] + $mtime[0];
		$this->totalTime = $this->endTime - $this->initTime;

		if (!$this->disableLogging) {
			// Add to search history
			$this->addToHistory();
		}
	}

	/**
	 * DEBUGGING. Support function for debugOutput().
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function debugOutputSearchTerms()
	{
		// Advanced search
		if (isset($this->searchTerms[0]['group'])) {
			$output = "GROUP JOIN : " . $this->searchTerms[0]['join'] . "<br/>\n";
			for ($i = 0; $i < count($this->searchTerms); $i++) {
				$output .= "BOOL ($i) : " . $this->searchTerms[$i]['group'][0]['bool'] . "<br/>\n";
				for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
					$output .= "TERMS ($i)($j) : " . $this->searchTerms[$i]['group'][$j]['lookfor'] . "<br/>\n";
					$output .= "INDEX ($i)($j) : " . $this->searchTerms[$i]['group'][$j]['field'] . "<br/>\n";
				}
			}
			// Basic search
		} else {
			$output = "TERMS : " . $this->searchTerms[0]['lookfor'] . "<br/>\n";
			$output .= "INDEX : " . $this->searchTerms[0]['index'] . "<br/>\n";
		}

		return $output;
	}

	/**
	 * DEBUGGING. Use this to print out your search.
	 *
	 * @access  public
	 * @return  string
	 */
	public function debugOutput()
	{
		$output = "VIEW : " . $this->view . "<br/>\n";
		$output .= $this->debugOutputSearchTerms();

		foreach ($this->filterList as $field => $filter) {
			foreach ($filter as $value) {
				$output .= "FILTER : $field => $value<br/>\n";
			}
		}
		$output .= "PAGE : " . $this->page . "<br/>\n";
		$output .= "SORT : " . $this->sort . "<br/>\n";
		$output .= "TIMING : START : " . $this->initTime . "<br/>\n";
		$output .= "TIMING : QUERY.S : " . $this->queryStartTime . "<br/>\n";
		$output .= "TIMING : QUERY.E : " . $this->queryEndTime . "<br/>\n";
		$output .= "TIMING : FINISH : " . $this->endTime . "<br/>\n";

		return $output;
	}

	/**
	 * Start the timer to figure out how long a query takes.  Complements
	 * stopQueryTimer().
	 *
	 * @access protected
	 */
	protected function startQueryTimer()
	{
		// Get time before the query
		$time = explode(" ", microtime());
		$this->queryStartTime = $time[1] + $time[0];
	}

	/**
	 * End the timer to figure out how long a query takes.  Complements
	 * startQueryTimer().
	 *
	 * @access protected
	 */
	protected function stopQueryTimer()
	{
		$time = explode(" ", microtime());
		$this->queryEndTime = $time[1] + $time[0];
		$this->queryTime = $this->queryEndTime - $this->queryStartTime;
	}

	/**
	 * Return the field (index) searched by a basic search
	 *
	 * @access  public
	 * @return  string   The searched index
	 */
	public function getSearchIndex()
	{
		// Single search index does not apply to advanced search:
		if ($this->searchType == $this->advancedSearchType) {
			return null;
		} elseif (isset($this->searchTerms[0]['index'])) {
			return $this->searchTerms[0]['index'];
		} else {
			return 'Keyword';
		}
	}

	/**
	 * Find a word amongst the current search terms
	 *
	 * @access  protected
	 * @param string $needle Search term to find
	 * @return  bool     True/False if the word was found
	 */
	protected function findSearchTerm($needle)
	{
		// Advanced search
		if (isset($this->searchTerms[0]['group'])) {
			foreach ($this->searchTerms as $group) {
				foreach ($group['group'] as $search) {
					if (preg_match("/\b$needle\b/", $search['lookfor'])) {
						return true;
					}
				}
			}

			// Basic search
		} else {
			foreach ($this->searchTerms as $haystack) {
				if (preg_match("/\b$needle\b/", $haystack['lookfor'])) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Replace a search term in the query
	 *
	 * @access  protected
	 * @param string $from Search term to find
	 * @param string $to Search term to insert
	 */
	protected function replaceSearchTerm($from, $to)
	{
		// Escape $from so it is regular expression safe (just in case it
		// includes any weird punctuation -- unlikely but possible):
		$from = addcslashes($from, '\^$.[]|()?*+{}/');

		// If we are looking for a quoted phrase
		// we can't use word boundaries
		if (strpos($from, '"') === false) {
			$pattern = "/\b$from\b/i";
		} else {
			$pattern = "/$from/i";
		}

		// Advanced search
		if (isset($this->searchTerms[0]['group'])) {
			for ($i = 0; $i < count($this->searchTerms); $i++) {
				for ($j = 0; $j < count($this->searchTerms[$i]['group']); $j++) {
					$this->searchTerms[$i]['group'][$j]['lookfor'] =
						preg_replace($pattern, $to,
							$this->searchTerms[$i]['group'][$j]['lookfor']);
				}
			}
			// Basic search
		} else {
			for ($i = 0; $i < count($this->searchTerms); $i++) {
				// Perform the replacement:
				$this->searchTerms[$i]['lookfor'] = preg_replace($pattern,
					$to, $this->searchTerms[$i]['lookfor']);
			}
		}
	}

	/**
	 * Return a query string for the current search with
	 *   a search term replaced
	 *
	 * @access  public
	 * @param string $oldTerm The old term to replace
	 * @param string $newTerm The new term to search
	 * @return  string   query string
	 */
	public function getDisplayQueryWithReplacedTerm($oldTerm, $newTerm)
	{
		// Stash our old data for a minute
		$oldTerms = $this->searchTerms;
		// Replace the search term
		$this->replaceSearchTerm($oldTerm, $newTerm);

		// Get the new query string
		$query = $this->displayQuery(true);
		// Restore the old data
		$this->searchTerms = $oldTerms;
		// Return the query string
		return $query;
	}

	/**
	 * Input Tokenizer - Specifically for spelling purposes
	 *
	 * Because of its focus on spelling, these tokens are unsuitable
	 * for actual searching. They are stripping important search data
	 * such as joins and groups, simply because they don't need
	 * spellchecking.
	 *
	 * @param string $input
	 * @return  array               Tokenized array
	 * @access  public
	 */
	public function spellingTokens($input)
	{
		$joins = array("AND", "OR", "NOT");
		$paren = array("(" => "", ")" => "");

		// Base of this algorithm comes straight from
		// PHP doc examples & benighted at gmail dot com
		// http://php.net/manual/en/function.strtok.php
		$tokens = array();
		$token = strtok($input, ' ');
		while ($token) {
			// find bracketed tokens
			if ($token{0} == '(') {
				$token .= ' ' . strtok(')') . ')';
			}
			// find double quoted tokens
			if ($token{0} == '"') {
				$token .= ' ' . strtok('"') . '"';
			}
			// find single quoted tokens
			if ($token{0} == "'") {
				$token .= ' ' . strtok("'") . "'";
			}
			$tokens[] = $token;
			$token = strtok(' ');
		}
		// Some cleaning of tokens that are just boolean joins
		//  and removal of brackets
		$return = array();
		foreach ($tokens as $token) {
			// Ignore join
			if (!in_array($token, $joins)) {
				// And strip parentheses
				$final = trim(strtr($token, $paren));
				if ($final != "") $return[] = $final;
			}
		}
		return $return;
	}

	/**
	 * Return a url for the current search with a search term replaced
	 *
	 * @access  public
	 * @param string|array $oldTerm The old term to replace
	 * @param string|array $newTerm The new term to search
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithReplacedTerm($oldTerm, $newTerm)
	{
		// Stash our old data for a minute
		$oldTerms = $this->searchTerms;
		$oldPage = $this->page;
		// Switch to page 1 -- it doesn't make sense to maintain the current page
		// when changing the contents of the search
		$this->page = 1;
		// Replace the term(s)
		if (is_array($oldTerm)) {
			for ($i = 0; $i < count($oldTerm); $i++) {
				$this->replaceSearchTerm($oldTerm[$i], $newTerm[$i]);
			}
		} else {
			$this->replaceSearchTerm($oldTerm, $newTerm);
		}

		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->searchTerms = $oldTerms;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Get the templates used to display recommendations for the current search.
	 *
	 * @access  public
	 * @param string $location 'top' or 'side'
	 * @return  array       Array of templates to display at the specified location.
	 */
	public function getRecommendationsTemplates($location = 'top')
	{
		$returnValue = array();
		if (isset($this->recommend[$location]) && !empty($this->recommend[$location])) {
			foreach ($this->recommend[$location] as $current) {
				$returnValue[] = $current->getTemplate();
			}
		}
		return $returnValue;
	}

	/**
	 * @return string
	 */
	abstract function getSearchesFile();

	/**
	 * Load all recommendation settings from the relevant ini file.  Returns an
	 * associative array where the key is the location of the recommendations (top
	 * or side) and the value is the settings found in the file (which may be either
	 * a single string or an array of strings).
	 *
	 * @access  protected
	 * @return  array           associative: location (top/side) => search settings
	 */
	protected function getRecommendationSettings()
	{
		// Load the necessary settings to determine the appropriate recommendations
		// module:
		$search = $this->searchTerms;
		$searchSettings = getExtraConfigArray($this->getSearchesFile());

		// If we have just one search type, save it so we can try to load a
		// type-specific recommendations module:
		if (count($search) == 1 && isset($search[0]['index'])) {
			$searchType = $search[0]['index'];
		} else {
			$searchType = false;
		}

		// Load a type-specific recommendations setting if possible, or the default
		// otherwise:
		$recommend = array();
		if ($searchType &&
			isset($searchSettings['TopRecommendations'][$searchType])) {
			$recommend['top'] = $searchSettings['TopRecommendations'][$searchType];
		} else {
			$recommend['top'] = isset($searchSettings['General']['default_top_recommend']) ? $searchSettings['General']['default_top_recommend'] : false;
		}
		if ($searchType && isset($searchSettings['SideRecommendations'][$searchType])) {
			$recommend['side'] = $searchSettings['SideRecommendations'][$searchType];
		} else {
			$recommend['side'] = isset($searchSettings['General']['default_side_recommend']) ? $searchSettings['General']['default_side_recommend'] : false;
		}

		return $recommend;
	}

	/**
	 * Initialize the recommendations module based on current searchTerms.
	 *
	 * @access  protected
	 */
	protected function initRecommendations()
	{
		// If no settings were found, quit now:
		$settings = $this->getRecommendationSettings();
		if (empty($settings)) {
			$this->recommend = false;
			return;
		}

		// Process recommendations for each location:
		$this->recommend = array('top' => array(), 'side' => array());
		foreach ($settings as $location => $currentSet) {
			// If the current location is disabled, skip processing!
			if (empty($currentSet)) {
				continue;
			}
			// Make sure the current location's set of recommendations is an array;
			// if it's a single string, this normalization will simplify processing.
			if (!is_array($currentSet)) {
				$currentSet = array($currentSet);
			}
			// Now loop through all recommendation settings for the location.
			foreach ($currentSet as $current) {
				// Break apart the setting into module name and extra parameters:
				$current = explode(':', $current);
				$module = array_shift($current);
				$params = implode(':', $current);

				// Can we build a recommendation module with the provided settings?
				// If the factory throws an error, we'll assume for now it means we
				// tried to load a non-existent module, and we'll ignore it.
				$obj = RecommendationFactory::initRecommendation($module, $this, $params);
				if ($obj && !($obj instanceof AspenError)) {
					$obj->init();
					$this->recommend[$location][] = $obj;
				}
			}
		}
	}

	/**
	 * Load all available facet settings.  This is mainly useful for showing
	 * appropriate labels when an existing search has multiple filters associated
	 * with it.
	 *
	 * @access  public
	 * @param false|string $preferredSection Section to favor when loading
	 *                                              settings; if multiple sections
	 *                                              contain the same facet, this
	 *                                              section's description will be
	 *                                              favored.
	 */
	public function activateAllFacets($preferredSection = false)
	{
		// By default, there is only set of facet settings, so this function isn't
		// really necessary.  However, in the Search History screen, we need to
		// use this for Solr-based Search Objects, so we need this dummy method to
		// allow other types of Search Objects to co-exist with Solr-based ones.
		// See the Solr Search Object for details of how this works if you need to
		// implement context-sensitive facet settings in another module.
	}

	/**
	 * Translate a field name to a displayable string for rendering a query in
	 * human-readable format:
	 *
	 * @access  protected
	 * @param string $field Field name to display.
	 * @return  string                      Human-readable version of field name.
	 */
	protected function getHumanReadableFieldName($field)
	{
		if (isset($this->searchIndexes[$field])) {
			return translate(['text'=>$this->searchIndexes[$field],'isPublicFacing'=>true]);
		} else if (isset($this->advancedTypes[$field])) {
			return translate(['text'=>$this->advancedTypes[$field],'isPublicFacing'=>true]);
		} else {
			return $field;
		}
	}

	/**
	 * Get a human-readable presentation version of the advanced search query
	 * stored in the object.  This will not work if $this->searchType is not
	 * 'advanced.'
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function buildAdvancedDisplayQuery()
	{
		// Groups and exclusions. This mirrors some logic in Solr.php
		$groups = array();
		$excludes = array();

		foreach ($this->searchTerms as $search) {
			$thisGroup = array();
			// Process each search group
			foreach ($search['group'] as $group) {
				// Build this group individually as a basic search
				$thisGroup[] = $group['field'] .
					":{$group['lookfor']}";
			}
			// Is this an exclusion (NOT) group or a normal group?
			if ($search['group'][0]['bool'] == 'NOT') {
				$excludes[] = join(" OR ", $thisGroup);
			} else {
				$groups[] = join(" " . $search['group'][0]['bool'] . " ", $thisGroup);
			}
		}

		// Base 'advanced' query
		$output = "(" . join(") " . $this->searchTerms[0]['join'] . " (", $groups) . ")";
		// Concatenate exclusion after that
		if (count($excludes) > 0) {
			$output .= " NOT ((" . join(") OR (", $excludes) . "))";
		}

		return $output;
	}

	/**
	 * Build a string for onscreen display showing the
	 *   query used in the search (not the filters).
	 *
	 * @access  public
	 * @param bool $forceRebuild
	 * @return  string   user friendly version of 'query'
	 */
	public function displayQuery(/** @noinspection PhpUnusedParameterInspection */ $forceRebuild = false)
	{
		// Advanced search?
		if ($this->searchType == $this->advancedSearchType) {
			return $this->buildAdvancedDisplayQuery();
		}
		// Default -- Basic search:
		return $this->searchTerms[0]['lookfor'];
	}

	/**
	 * Actually process and submit the search; in addition to returning results,
	 * this method is responsible for populating various class properties that
	 * are returned by other get methods (i.e. getFacetList).
	 *
	 * @access  public
	 * @param bool $returnIndexErrors Should we die inside the index code if
	 *                                     we encounter an error (false) or return
	 *                                     it for access via the getIndexError()
	 *                                     method (true)?
	 * @param bool $recommendations Should we process recommendations along
	 *                                     with the search itself?
	 * @param bool $preventQueryModification Should we make sure the query doesn't change
	 * @return  object   Search results (format may vary from class to class).
	 */
	abstract public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false);

	/**
	 * Get error message from index response, if any.  This will only work if
	 * processSearch was called with $returnIndexErrors set to true!
	 *
	 * @access  public
	 * @return  mixed       false if no error, error string otherwise.
	 */
	abstract public function getIndexError();

	public function getNextPrevLinks()
	{
		global $interface;
		global $timer;
		//Setup next and previous links based on the search results.
		if (isset($_REQUEST['searchId']) && isset($_REQUEST['recordIndex']) && ctype_digit($_REQUEST['searchId']) && ctype_digit($_REQUEST['recordIndex'])) {
			//rerun the search
			$s = new SearchEntry();
			$s->id = $_REQUEST['searchId'];
			$interface->assign('searchId', $_REQUEST['searchId']);
			$currentPage = isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $currentPage);

			$s->find();
			if ($s->getNumResults() > 0) {
				$s->fetch();
				$minSO = unserialize($s->search_object);
				/** @var SearchObject_BaseSearcher $searchObject */
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($currentPage);
				//Run the search
				$result = $searchObject->processSearch(true, false, false);

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $_REQUEST['recordIndex'] - 1;
				$recordsPerPage = $searchObject->getLimit();
				$adjustedResultIndex = $currentResultIndex - ($recordsPerPage * ($currentPage - 1));

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0) {
					//Need to run a search for the previous page
					$interface->assign('previousPage', $currentPage - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($currentPage - 1);
					$previousSearchObject->processSearch(true, false, false);
					$previousResults = $previousSearchObject->getResultRecordSet();
				} else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()) {
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $currentPage + 1);
					$nextSearchObject->setPage($currentPage + 1);
					$nextSearchObject->processSearch(true, false, false);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				if ($result instanceof AspenError) {
					//If we get an error executing the search, just eat it for now.
				} else {
					if ($searchObject->getResultTotal() < 1) {
						//No results found
					} else {
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0) {
							if (isset($previousResults)) {
								$previousRecord = $previousResults[count($previousResults) - 1];
							} else {
								$previousId = $adjustedResultIndex - 1;
								if (isset($recordSet[$previousId])) {
									$previousRecord = $recordSet[$previousId];
								}
							}

							//Convert back to 1 based index
							if (isset($previousRecord)) {
								$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
								if (isset($previousRecord['title_display'])) {
									$interface->assign('previousTitle', $previousRecord['title_display']);
								}else{
									$interface->assign('previousTitle', 'Unknown Title');
								}
								if ($previousRecord['recordtype'] == 'grouped_work') {
									require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
									$groupedWork = New GroupedWorkDriver($previousRecord);
									$relatedRecords = $groupedWork->getRelatedRecords(true);
									global $timer;
									$timer->logTime('Loaded related records for previous result');
									if (count($relatedRecords) == 1) {
										$previousRecord = reset($relatedRecords);
										list($previousType, $previousId) = explode('/', trim($previousRecord->getUrl(), '/'));
										$interface->assign('previousId', $previousId);
										$interface->assign('previousType', $previousType);
									} else {
										$interface->assign('previousType', 'GroupedWork');
										$interface->assign('previousId', $previousRecord['id']);
									}
								} elseif (strpos($previousRecord['id'], 'list') === 0) {
									$interface->assign('previousType', 'MyAccount/MyList');
									$interface->assign('previousId', str_replace('list', '', $previousRecord['id']));
								} else {
									$interface->assign('previousType', 'Record');
									$interface->assign('previousId', $previousRecord['id']);
								}
							}
						}
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()) {

							if (isset($nextResults)) {
								$nextRecord = $nextResults[0];
							} else {
								$nextRecordIndex = $adjustedResultIndex + 1;
								if (isset($recordSet[$nextRecordIndex])) {
									$nextRecord = $recordSet[$nextRecordIndex];
								}
							}
							//Convert back to 1 based index
							$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
							if (isset($nextRecord)) {
								if (isset($nextRecord['title_display'])) {
									$interface->assign('nextTitle', $nextRecord['title_display']);
								}else{
									$interface->assign('nextTitle', 'Unknown Title');
								}
								if ($nextRecord['recordtype'] == 'grouped_work') {
									require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
									$groupedWork = New GroupedWorkDriver($nextRecord);
									$relatedRecords = $groupedWork->getRelatedRecords(true);
									global $timer;
									$timer->logTime('Loaded related records for next result');
									if (count($relatedRecords) == 1) {
										$nextRelatedRecord = reset($relatedRecords);
										list($nextType, $nextId) = explode('/', trim($nextRelatedRecord->getUrl(), '/'));
										$interface->assign('nextId', $nextId);
										$interface->assign('nextType', $nextType);
									} else {
										$interface->assign('nextType', 'GroupedWork');
										$interface->assign('nextId', $nextRecord['id']);
									}
								} elseif (strpos($nextRecord['id'], 'list') === 0) {
									$interface->assign('nextType', 'MyAccount/MyList');
									$interface->assign('nextId', str_replace('list', '', $nextRecord['id']));
								} else {
									$interface->assign('nextType', 'Record');
									$interface->assign('nextId', $nextRecord['id']);
								}
							}
						}

					}
				}
			}
			$timer->logTime('Got next/previous links');
		}
	}

	/**
	 * Set whether or not this is a primary search.  If it is, we will show links to it in search result debugging
	 * @param boolean $flag
	 */
	public function setPrimarySearch($flag)
	{
		$this->isPrimarySearch = $flag;
	}

	public function convertBasicToAdvancedSearch()
	{

		$searchTerms = $this->searchTerms;

		$searchString = isset($searchTerms[0]['lookfor']) ? $searchTerms[0]['lookfor'] : '';
		$searchIndex =  isset($searchTerms[0]['index']) ? $searchTerms[0]['index'] : $this->getDefaultIndex();

		$this->searchTerms = array(
			array(
				'group' => array(
					0 => array(
						'field' => $searchIndex,
						'lookfor' => $searchString,
						'bool' => 'AND'
					)
				),
				'join' => 'AND'
			)
		);

		$this->searchType = 'advanced';
	}

	/**
	 * Return a url of the current search as an RSS feed.
	 *
	 * @access  public
	 * @return  string    URL
	 */
	public function getRSSUrl()
	{
		// Stash our old data for a minute
		$oldView = $this->view;
		$oldPage = $this->page;
		// Add the new view
		$this->view = 'rss';
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->view = $oldView;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Turn our results into an RSS feed
	 *
	 * @access  public
	 * @param array|null $result Existing result set (null to do new search)
	 * @return  string                  XML document
	 */
	public abstract function buildRSS($result = null);

	/**
	 * Return a url of the current search as an Excel Spreadsheet.
	 *
	 * @access  public
	 * @return  string    URL
	 */
	public function getExcelUrl()
	{
		// Stash our old data for a minute
		$oldView = $this->view;
		$oldPage = $this->page;
		// Add the new view
		$this->view = 'excel';
		// Remove page number
		$this->page = 1;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->view = $oldView;
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Turn our results into an Excel document
	 * @param array|null $result
	 */
	public abstract function buildExcel($result = null);

	public abstract function getResultRecordSet();

	/**
	 * @return bool
	 */
	public function supportsSuggestions()
	{
		return false;
	}

	/**
	 * @param string $searchTerm
	 * @param string $searchIndex
	 * @return array
	 */
	public function getSearchSuggestions(/** @noinspection PhpUnusedParameterInspection */ $searchTerm, $searchIndex)
	{
		return [];
	}

	/**
	 * @return array
	 */
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$this->facetConfig = [];
		}
		return $this->facetConfig;
	}

	abstract function getSearchName();
	public abstract function getDefaultIndex();

	abstract function loadValidFields();
	abstract function loadDynamicFields();

	/**
	 * @param string $scopedFieldName
	 * @return string
	 */
	protected function getUnscopedFieldName(string $scopedFieldName): string
	{
		return $scopedFieldName;
	}

	/**
	 * @param $field
	 * @return string
	 */
	protected function getScopedFieldName($field): string
	{
		return $field;
	}
}//End of SearchObject_Base

/**
 * ****************************************************
 *
 * A minified search object used exclusively for trimming
 *  a search object down to it's barest minimum size
 *  before storage in a cookie or database.
 *
 * It's still contains enough data granularity to
 *  programmatically recreate search urls.
 *
 * This class isn't intended for general use, but simply
 *  a way of storing/retrieving data from a search object:
 *
 * eg. Store
 * $searchHistory[] = serialize($this->minify());
 *
 * eg. Retrieve
 * $searchObject  = SearchObjectFactory::initSearchObject();
 * $searchObject->deminify(unserialize($search));
 *
 */
class minSO
{
	public $t = array();
	public $f = array();
	public $hf = array();
	public $fc = array();
	public $id, $i, $s, $r, $ty, $sr, $q, $ss;

	/**
	 * Constructor. Building minified object from the
	 *    searchObject passed in. Needs to be kept
	 *    up-to-date with the deminify() function on
	 *    searchObject.
	 * @param SearchObject_BaseSearcher $searchObject
	 * @access  public
	 */
	public function __construct($searchObject)
	{
		// Most values will transfer without changes
		$this->id = $searchObject->getSearchId();
		$this->i = $searchObject->getStartTime();
		$this->s = $searchObject->getQuerySpeed();
		$this->ss = $searchObject->getSearchSource();
		$this->r = $searchObject->getResultTotal();
		$this->ty = $searchObject->getSearchType();
		$this->sr = $searchObject->getSort();
		$this->q = $searchObject->getQuery();

		// Search terms, we'll shorten keys
		$tempTerms = $searchObject->getSearchTerms();
		foreach ($tempTerms as $term) {
			$newTerm = array();
			foreach ($term as $k => $v) {
				switch ($k) {
					case 'join'    :
						$newTerm['j'] = $v;
						break;
					case 'index'   :
						$newTerm['i'] = $v;
						break;
					case 'lookfor' :
						$newTerm['l'] = $v;
						break;
					case 'group' :
						$newTerm['g'] = array();
						foreach ($v as $line) {
							$search = array();
							foreach ($line as $k2 => $v2) {
								switch ($k2) {
									case 'bool'    :
										$search['b'] = $v2;
										break;
									case 'field'   :
										$search['f'] = $v2;
										break;
									case 'lookfor' :
										$search['l'] = $v2;
										break;
								}
							}
							$newTerm['g'][] = $search;
						}
						break;
				}
			}
			$this->t[] = $newTerm;
		}

		// It would be nice to shorten filter fields too, but
		//      it would be a nightmare to maintain.
		$this->f = $searchObject->getFilters();


		// Add Hidden Filters if Present
		if (method_exists($searchObject, 'getHiddenFilters')) {
			$this->hf = $searchObject->getHiddenFilters();
		}

		// Add Facet Configurations if Present
		if (method_exists($searchObject, 'getFacetConfig')) {
			$this->fc = $searchObject->getFacetConfig();
		}
	}
} //End of minSO object (not SearchObject_Base)