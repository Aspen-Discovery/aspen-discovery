<?php

require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

class SearchObject_GroupedWorkSearcher extends SearchObject_SolrSearcher
{
	// Field List
	public static $fields_to_return = 'auth_author2,author2-role,id,mpaaRating,title_display,title_full,title_short,subtitle_display,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,num_titles,record_details,item_details,publisherStr,publishDate,publishDateSort,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,display_description,fountas_pinnell,last_indexed';

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
		if (isset($searchSettings['Sorting'])) {
			$this->sortOptions = $searchSettings['Sorting'];
		} else {
			$this->sortOptions = array(
				'relevance' => 'sort_relevance',
				'popularity' => 'sort_popularity',
				'year' => 'sort_year', 'year asc' => 'sort_year asc',
				'callnumber' => 'sort_callnumber', 'author' => 'sort_author',
				'title' => 'sort_title'
			);
		}

		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Solr Search Object');
	}

	public function disableScoping()
	{
		$this->indexEngine->disableScoping();
	}

	public function enableScoping()
	{
		$this->indexEngine->enableScoping();
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 *
	 * @param String|null $searchSource
	 * @param String|null $searchTerm
	 * @return  boolean
	 */
	public function init($searchSource = null, $searchTerm = null)
	{
		// Call the standard initialization routine in the parent:
		parent::init($searchSource);

		$this->indexEngine->setSearchSource($searchSource);

		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch(null, true, true);
		if ($restored === true) {
			return true;
		} else if (($restored instanceof AspenError)) {
			return false;
		}

		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initSort();
		$this->initFilters();

		if ($searchTerm == null) {
			$searchTerm = isset($_REQUEST['lookfor']) ? $_REQUEST['lookfor'] : null;
		}

		global $module;
		global $action;

		//********************
		// Basic Search logic
		if ($this->initBasicSearch($searchTerm)) {
			// If we found a basic search, we don't need to do anything further.
		} else {
			$this->initAdvancedSearch();
		}

		//********************
		// Author screens - handled slightly differently
		$author_ajax_call = (isset($_REQUEST['author']) && $action == 'AJAX' && $module == 'Search');
		if ($module == 'Author' || $author_ajax_call) {
			// Author module or ajax call from author results page
			// *** Things in common to both screens
			// Log a special type of search
			$this->searchType = 'author';
			// We don't spellcheck this screen
			//   it's not for free user input anyway
			$this->spellcheckEnabled = false;

			// *** Author/Home
			if ($action == 'Home' || $author_ajax_call) {
				$this->searchSubType = 'home';
				// Remove our empty basic search (default)
				$this->searchTerms = array();
				// Prepare the search as a normal author search
				if (isset($_REQUEST['author'])) {
					$author = $_REQUEST['author'];
					if (is_array($author)) {
						$author = array_pop($author);
					}
				} else {
					$author = 'Not Provided';
				}

				$this->searchTerms[] = array(
					'index' => 'Author',
					'lookfor' => trim(strip_tags($author))
				);
			}

			// *** Author/Search
			if ($action == 'Search') {
				$this->searchSubType = 'search';
				// We already have the 'lookfor', just set the index
				$this->searchTerms[0]['index'] = 'Author';
				// We really want author facet data
				$this->addFacet('authorStr');
				// Offset the facet list by the current page of results, and
				// allow up to ten total pages of results -- since we can't
				// get a total facet count, this at least allows the paging
				// mechanism to automatically add more pages to the end of the
				// list so that users can browse deeper and deeper as they go.
				// TODO: Make this better in the future if Solr offers a way
				//       to get a total facet count (currently not possible).
				$this->facetOffset = ($this->page - 1) * $this->limit;
				$this->facetLimit = $this->limit * 10;
				// Sorting - defaults to off with unlimited facets, so let's
				//           be explicit here for simplicity.
				if (isset($_REQUEST['sort']) && ($_REQUEST['sort'] == 'author')) {
					$this->setFacetSortOrder('index');
				} else {
					$this->setFacetSortOrder('count');
				}
			}
		} else if ($module == 'MyAccount') {
			// Users Lists
			$this->spellcheckEnabled = false;
			$this->searchType = ($action == 'Home') ? 'favorites' : 'list';
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = trim(strip_tags($_REQUEST['q']));
		}

		return true;
	} // End init()

	public function setDebugging($enableDebug, $enableSolrQueryDebugging)
	{
		$this->debug = $enableDebug;
		$this->debugSolrQuery = $enableDebug && $enableSolrQueryDebugging;
		$this->getIndexEngine()->setDebugging($enableDebug, $enableSolrQueryDebugging);
	}

	/**
	 * Initialise the object for retrieving advanced
	 *   search screen facet data from inside solr.
	 *
	 * @access  public
	 * @return  boolean
	 */
	public function initAdvancedFacets()
	{
		global $locationSingleton;
		// Call the standard initialization routine in the parent:
		parent::init();

		$searchLibrary = Library::getActiveLibrary();

		$searchLocation = $locationSingleton->getActiveLocation();
		if ($searchLocation != null) {
			$facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
		} else {
			$facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
		}

		foreach ($facets as &$facet) {
			//Adjust facet name for local scoping
			$facet->facetName = $this->getScopedFieldName($facet->facetName);
		}

		//********************

		$facetLimit = $this->getFacetSetting('Advanced_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Spellcheck is not needed for facet data!
		$this->spellcheckEnabled = false;

		//********************
		// Basic Search logic
		$this->searchTerms[] = array(
			'index' => $this->getDefaultIndex(),
			'lookfor' => ""
		);

		return true;
	}


	public function getDebugTiming()
	{
		if (!$this->debug) {
			return null;
		} else {
			if (!isset($this->indexResult['debug'])) {
				return null;
			} else {
				return json_encode($this->indexResult['debug']['timing']);
			}
		}
	}

	/**
	 * Return the field (index) searched by a basic search
	 *
	 * @access  public
	 * @return  string   The searched index
	 */
	public function getSearchIndex()
	{
		// Use normal parent method for non-advanced searches.
		if ($this->searchType == $this->basicSearchType || $this->searchType == 'author') {
			return parent::getSearchIndex();
		} else {
			if ($this->isAdvanced()) {
				return 'advanced';
			}else{
				return null;
			}
		}
	}

	/**
	 * @param array $orderedListOfIDs Use the index of the matched ID as the index of the resulting array of summary data (for later merging)
	 * @return array
	 */
	public function getTitleSummaryInformation($orderedListOfIDs = array())
	{
		$titleSummaries = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];
			/** @var GroupedWorkDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!($record instanceof AspenError)) {
				if (!empty($orderedListOfIDs)) {
					$position = array_search($current['id'], $orderedListOfIDs);
					if ($position !== false) {
						$titleSummaries[$position] = $record->getSummaryInformation();
					}
				} else {
					$titleSummaries[] = $record->getSummaryInformation();
				}
			} else {
				$titleSummaries[] = "Unable to find record";
			}
		}
		return $titleSummaries;
	}

	/*
	 * Get an array of citations for the records within the search results
	 */
	public function getCitations($citationFormat)
	{
		global $interface;
		$html = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];
			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			/** @var GroupedWorkDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			$html[] = $interface->fetch($record->getCitation($citationFormat));
		}
		return $html;
	}

	/*
	 *  Get the template to use to display the results returned from getRecordHTML()
	 *  based on the view mode
	 *
	 * @return string  Template file name
	 */
	public function getDisplayTemplate()
	{
		if ($this->view == 'covers') {
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles
		} else { // default
			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
		}
		return $displayTemplate;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML()
	{
		global $interface;
		global $memoryWatcher;
		global $timer;
		$html = array();
		if (isset($this->indexResult['response'])) {
			$allWorkIds = array();
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$allWorkIds[] = $this->indexResult['response']['docs'][$x]['id'];
			}
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			GroupedWorkDriver::loadArchiveLinksForWorks($allWorkIds);
			$timer->logTime('Loaded archive links');
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$memoryWatcher->logMemory("Started loading record information for index $x");
				$current = &$this->indexResult['response']['docs'][$x];
				if (!$this->debug) {
					unset($current['explain']);
					unset($current['score']);
				}
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				/** @var GroupedWorkDriver $record */
				$record = RecordDriverFactory::initRecordDriver($current);
				if (!($record instanceof AspenError)) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult($this->view));
				} else {
					$html[] = "Unable to find record";
				}
				//Free some memory
				$record = 0;
				unset($record);
				$memoryWatcher->logMemory("Finished loading record information for index $x");
				$timer->logTime('Loaded search result for ' . $current['id']);
			}
		}
		return $html;
	}

	/**
	 * Set an overriding array of record IDs.
	 *
	 * @access  public
	 * @param array $ids Record IDs to load
	 */
	public function setQueryIDs($ids)
	{
		$this->query = 'id:(' . implode(' OR ', $ids) . ')';
	}

	/**
	 * Set an overriding string.
	 *
	 * @access  public
	 * @param string $newQuery Query string
	 */
	public function setQueryString($newQuery)
	{
		$this->query = $newQuery;
	}

	/**
	 * Set an overriding facet sort order.
	 *
	 * @access  public
	 * @param string $newSort Sort string
	 */
	public function setFacetSortOrder($newSort)
	{
		// As of Solr 1.4 valid values are:
		// 'count' = relevancy ranked
		// 'index' = index order, most likely alphabetical
		// more info : http://wiki.apache.org/solr/SimpleFacetParameters#facet.sort
		if ($newSort == 'count' || $newSort == 'index') $this->facetSort = $newSort;
	}

	public function supportsSuggestions()
	{
		return true;
	}

	/**
	 * @param string $searchTerm
	 * @param string $searchIndex
	 * @return array
	 */
	public function getSearchSuggestions($searchTerm, $searchIndex)
	{
		$suggestionHandler = 'suggest';
		if ($searchIndex == 'Title' || $searchIndex == 'StartOfTitle') {
			$suggestionHandler = 'title_suggest';
		} elseif ($searchIndex == 'Author') {
			$suggestionHandler = 'author_suggest';
		} elseif ($searchIndex == 'Subject') {
			$suggestionHandler = 'subject_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	/**
	 * Return a list of valid sort options -- overrides the base class with
	 * custom behavior for Author/Search screen.
	 *
	 * @access  public
	 * @return  array    Sort value => description array.
	 */
	protected function getSortOptions()
	{
		// Author/Search screen
		if ($this->searchType == 'author' && $this->searchSubType == 'search') {
			// It's important to remember here we are talking about on-screen
			//   sort values, not what is sent to Solr, since this screen
			//   is really using facet sorting.
			return array('relevance' => 'sort_author_relevance',
				'author' => 'sort_author_author');
		}

		// Everywhere else -- use normal default behavior
		$sortOptions = parent::getSortOptions();
		$searchLibrary = Library::getSearchLibrary($this->searchSource);
		if ($searchLibrary == null) {
			unset($sortOptions['callnumber_sort']);
		}
		return $sortOptions;
	}

	/**
	 * Get the base URL for search results (including ? parameter prefix).
	 *
	 * @access  protected
	 * @return  string   Base URL
	 */
	protected function getBaseUrl()
	{
		// Base URL is different for author searches:
		if ($this->searchType == 'author') {
			if ($this->searchSubType == 'home') return "/Author/Home?";
			if ($this->searchSubType == 'search') return "/Author/Search?";
		} else if ($this->searchType == 'favorites') {
			return '/MyAccount/Home?';
		} else if ($this->searchType == 'list') {
			return '/MyAccount/MyList/' .
				urlencode($_GET['id']) . '?';
		}

		// If none of the special cases were met, use the default from the parent:
		return parent::getBaseUrl();
	}

	/**
	 * Get an array of strings to attach to a base URL in order to reproduce the
	 * current search.
	 *
	 * Note: Can't store this for future use since it gets rewritten by spelling suggestions etc.
	 *
	 * @access  protected
	 * @return  array    Array of URL parameters (key=url_encoded_value format)
	 */
	protected function getSearchParams()
	{
		$params = array();
		switch ($this->searchType) {
			// Author Home screen
			case "author":
				if ($this->searchSubType == 'home') $params[] = "author=" . urlencode($this->searchTerms[0]['lookfor']);
				if ($this->searchSubType == 'search') $params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
				$params[] = "basicSearchType=Author";
				break;
			// New Items or Reserves modules may have a few extra parameters to preserve:
			default:
				$params = parent::getSearchParams();
				break;
		}

		//Only use the request search index if we don't have a search index set alread
		$searchIndexSet = false;
		foreach ($params as $param) {
			if (strpos($param, 'searchIndex') == 0) {
				$searchIndexSet = true;
				break;
			}
		}
		if (!$searchIndexSet) {
			if (isset($_REQUEST['searchIndex'])) {
				if ($_REQUEST['searchIndex'] == 'AllFields') {
					$_REQUEST['searchIndex'] = 'Keyword';
				}
				if (is_array($_REQUEST['searchIndex'])) {
					$_REQUEST['searchIndex'] = reset($_REQUEST['searchIndex']);
				}
				$params[] = 'searchIndex=' . $_REQUEST['searchIndex'];
			}
		}

		return $params;
	}


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
		return parent::getRecommendationSettings();
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
		foreach ($this->filterList as $field => $filter) {
			if ($field === '') {
				unset($this->filterList[$field]);
			}
		}

		$availabilityToggleValue = null;
		$availabilityAtValues = [];
		$formatValues = [];
		$formatCategoryValues = [];
		$facetConfig = $this->getFacetConfig();
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
			foreach ($formatValues as $formatValue) {
				$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
				$filterQuery[] = $availabilityByFormatFieldName . ':"' . $availabilityToggleValue . '"';
				$availabilityByFormatFieldNames[] = $availabilityByFormatFieldName;
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
				if (strpos($facetField, 'availability_toggle') === 0) {
					if (!empty($availabilityByFormatFieldName)) {
						foreach ($availabilityByFormatFieldNames as $availabilityByFormatFieldName) {
							$facetSet['field'][$availabilityByFormatFieldName] = $facetInfo;
						}
					} else {
						$facetSet['field'][$facetField] = $facetInfo;
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

		// The first record to retrieve:
		//  (page - 1) * limit = start
		$recordStart = ($this->page - 1) * $this->limit;
		//Remove irrelevant fields based on scoping
		$fieldsToReturn = $this->getFieldsToReturn();

		$this->indexResult = $this->indexEngine->search(
			$this->query,      // Query string
			$this->index,      // DisMax Handler
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
		if (!isset($this->indexResult['response']['numFound'])) {
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

		global $locationSingleton;
		/** @var Library $currentLibrary */
		$currentLibrary = Library::getActiveLibrary();
		$activeLocationFacet = null;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!is_null($activeLocation)) {
			$activeLocationFacet = $activeLocation->facetLabel;
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
				$currentSettings['display'] = $translate ? translate($facet[0]) : $facet[0];
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
						} elseif ($facet[0] == 'Digital Collection' || $facet[0] == 'OverDrive' || $facet[0] == 'Online') {
							$valueKey = '5' . $valueKey;
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
			if (!$foundBranch && $doBranchProcessing && !is_null($activeLocationFacet)) {
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


	/**
	 * Turn our results into an RSS feed
	 *
	 * @access  public
	 * @param null|array $result Existing result set (null to do new search)
	 * @return  string                  XML document
	 */
	public function buildRSS($result = null)
	{
		global $configArray;
		// XML HTTP header
		header('Content-type: text/xml', true);

		// First, get the search results if none were provided
		// (we'll go for 50 at a time)
		if (is_null($result)) {
			$this->limit = 50;
			$result = $this->processSearch(false, false);
		}

		$baseUrl = $configArray['Site']['url'];
		for ($i = 0; $i < count($result['response']['docs']); $i++) {
			$id = $result['response']['docs'][$i]['id'];
			$result['response']['docs'][$i]['recordUrl'] = $baseUrl . '/GroupedWork/' . $id;
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$groupedWorkDriver = new GroupedWorkDriver($result['response']['docs'][$i]);
			if ($groupedWorkDriver->isValid) {
				$image = $groupedWorkDriver->getBookcoverUrl('medium', true);
				$description = "<img alt='Cover Image' src='$image'/> " . $groupedWorkDriver->getDescriptionFast();
				$result['response']['docs'][$i]['rss_description'] = $description;
			}
		}

		global $interface;

		// On-screen display value for our search
		$lookfor = $this->displayQuery();
		if (count($this->filterList) > 0) {
			// TODO : better display of filters
			$interface->assign('lookfor', $lookfor . " (" . translate('with filters') . ")");
		} else {
			$interface->assign('lookfor', $lookfor);
		}
		// The full url to recreate this search
		$interface->assign('searchUrl', $configArray['Site']['url'] . $this->renderSearchUrl());
		// Stub of a url for a records screen
		$interface->assign('baseUrl', $configArray['Site']['url'] . "/Record/");

		$interface->assign('result', $result);
		return $interface->fetch('Search/rss.tpl');
	}

	/**
	 * Turn our results into an Excel document
	 * @param null|array $result
	 */
	public function buildExcel($result = null)
	{
		try {
			// First, get the search results if none were provided
			// (we'll go for 50 at a time)
			if (is_null($result)) {
				$this->limit = 1000;
				$result = $this->processSearch(false, false);
			}

			// Prepare the spreadsheet
			ini_set('include_path', ini_get('include_path' . ';/PHPExcel/Classes'));
			include ROOT_DIR . '/PHPExcel.php';
			include ROOT_DIR . '/PHPExcel/Writer/Excel2007.php';
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setTitle("Search Results");

			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Results');

			//Add headers to the table
			$sheet = $objPHPExcel->getActiveSheet();
			$curRow = 1;
			$curCol = 0;
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Record #');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publisher');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Published');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Call Number');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Item Type');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Location');

			$maxColumn = $curCol - 1;

			global $solrScope;
			for ($i = 0; $i < count($result['response']['docs']); $i++) {
				//Output the row to excel
				$curDoc = $result['response']['docs'][$i];
				$curRow++;
				$curCol = 0;
				//Output the row to excel
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['id']) ? $curDoc['id'] : '');
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['title_display']) ? $curDoc['title_display'] : '');
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['author']) ? $curDoc['author'] : '');
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['publisherStr']) ? implode(', ', $curDoc['publisherStr']) : '');
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['publishDateSort']) ? implode(', ', $curDoc['publishDateSort']) : '');
				$callNumber = '';
				if (isset($curDoc['local_callnumber_' . $solrScope])) {
					$callNumber = is_array($curDoc['local_callnumber_' . $solrScope]) ? $curDoc['local_callnumber_' . $solrScope][0] : $curDoc['local_callnumber_' . $solrScope];
				}
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $callNumber);
				$iType = '';
				if (isset($curDoc['itype_' . $solrScope])) {
					$iType = is_array($curDoc['itype_' . $solrScope]) ? $curDoc['itype_' . $solrScope][0] : $curDoc['itype_' . $solrScope];
				}
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $iType);
				$location = '';
				if (isset($curDoc['detailed_location_' . $solrScope])) {
					$location = is_array($curDoc['detailed_location_' . $solrScope]) ? $curDoc['detailed_location_' . $solrScope][0] : $curDoc['detailed_location_' . $solrScope];
				}
				/** @noinspection PhpUnusedLocalVariableInspection */
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $location);
			}

			for ($i = 0; $i < $maxColumn; $i++) {
				$sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
			}

			//Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="Results.xlsx"');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output'); //THIS DOES NOT WORK WHY?
			$objPHPExcel->disconnectWorksheets();
			unset($objPHPExcel);
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create Excel File " . $e, Logger::LOG_ERROR);
		}
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @throws  AspenError
	 */
	function searchForRecordIds($ids)
	{
		$this->indexResult = $this->indexEngine->searchForRecordIds($ids);
	}


	/**
	 * Retrieves a document specified by the item barcode.
	 *
	 * @param string $barcode A barcode of an item in the document to retrieve from Solr
	 * @access  public
	 * @return  string              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByBarcode($barcode)
	{
		return $this->indexEngine->getRecordByBarcode($barcode);
	}

	/**
	 * Retrieves a document specified by an isbn.
	 *
	 * @param string[] $isbn An array of isbns to check
	 * @access  public
	 * @return  string              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByIsbn($isbn)
	{
		return $this->indexEngine->getRecordByIsbn($isbn, $this->getFieldsToReturn());
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

	public function setPrimarySearch($flag)
	{
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function __destruct()
	{
		if (isset($this->indexEngine)) {
			$this->indexEngine = null;
			unset($this->indexEngine);
		}
	}

	public function pingServer($failOnError = true)
	{
		return $this->indexEngine->pingServer($failOnError);
	}

	public function getSearchIndexes()
	{
		return $this->searchIndexes;
	}

	public function getRecordDriverForResult($record)
	{
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		return new GroupedWorkDriver($record);
	}

	public function getSearchesFile()
	{
		return 'groupedWorksSearches';
	}

	/**
	 * Get records similar to one record
	 * Uses MoreLikeThis Request Handler
	 *
	 * Uses SOLR MLT Query Handler
	 *
	 * @access    public
	 *
	 * @param array[] $ids
	 * @param int $page
	 * @param int $limit
	 * @return    array                            An array of query results
	 */
	function getMoreLikeThese($ids, $page = 1, $limit = 25)
	{
		return $this->indexEngine->getMoreLikeThese($ids, $this->getFieldsToReturn(), $page, $limit);
	}

	/**
	 * @return array
	 */
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$searchLibrary = Library::getActiveLibrary();
			global $locationSingleton;
			$searchLocation = $locationSingleton->getActiveLocation();
			if ($searchLocation != null) {
				$facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
			} else {
				$facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
			}
			global $solrScope;
			foreach ($facets as &$facet) {
				//Adjust facet name for local scoping
				$facet->facetName = $this->getScopedFieldName($facet->facetName);

				if ($this->isAdvanced()) {
					if ($facet->showInAdvancedSearch == 1) {
						$facetConfig[$facet->facetName] = $facet;
					}
				} else {
					if ($facet->showInResults == 1) {
						$facetConfig[$facet->facetName] = $facet;
					}
				}
			}
			$this->facetConfig = $facetConfig;
		}

		return $this->facetConfig;
	}

	function getMoreLikeThis($id, $availableOnly = false, $limitFormat = true, $limit = null)
	{
		return $this->indexEngine->getMoreLikeThis($id, $availableOnly, $limitFormat, $limit, $this->getFieldsToReturn());
	}

	public function getEngineName(){
		return 'GroupedWork';
	}

	public function getDefaultIndex()
	{
		return 'Keyword';
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
		} elseif (strpos($scopedFieldName, 'econtent_protection_type') === 0) {
			$scopedFieldName = 'econtent_protection_type';
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
		} elseif (strpos($scopedFieldName, 'collection') === 0 || strpos($scopedFieldName, 'collection_group') === 0) {
			$scopedFieldName = 'collection';
		} elseif (strpos($scopedFieldName, 'local_time_since_added') === 0) {
			$scopedFieldName = 'local_time_since_added';
		} elseif (strpos($scopedFieldName, 'itype') === 0) {
			$scopedFieldName = 'itype';
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
			} elseif ($field === 'econtent_protection_type') {
				$field = 'econtent_protection_type_' . $solrScope;
			} elseif (($field === 'collection') || ($field === 'collection_group')) {
				$field = 'collection_' . $solrScope;
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
			}
		}
		return $field;
	}
}