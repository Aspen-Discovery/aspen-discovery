<?php

require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_TalpaSearcher extends SearchObject_BaseSearcher{

	static $instance;
	/** @var TalpaSettings */
//	private $talpaBaseApi ='https://www.librarything.com/api/talpa.php';
	private $talpaBaseApi ='https://www.librarything.com/api_talpa.php';

	/**Build URL */
//	private $sessionId;
//	private $version = '2.0.0';
//	private $service = 'search';
	private $responseType = "json";

	private $search;
	private $query_id;

	private $token;

//	private $page;
//	private $limit;
//	private nocaching;
//
	private static $searchOptions;
	private $curl_connection;

	/**Track query time info */
	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

	/**Track record fetch time info */
	protected $recordFetchStartTime = null;
	protected $recordFetchEndTime = null;
	protected $recordFetchTime = null;

	/**Track preliminary search time info */
	protected $preliminarySearchStartTime = null;
	protected $preliminarySearchEndTime = null;
	protected $preliminarySearchTime = null;

	// STATS
	protected $resultsTotal = 0;

	protected $searchTerms;

	protected $preliminarySearchResults = null;
	protected $lastSearchResults;

	// Module and Action for building search results
	protected $resultsModule = 'Search';
	protected $resultsAction = 'Results';

		/** @var string */
	protected $searchSource = 'local';
	protected $searchType = 'basic';

/** Values for the options array*/
	protected $holdings = true;
	protected $didYouMean = false;
	protected $language = 'en';
	protected $idsToFetch = array();
	/**@var int */
	protected $maxTopics = 1;
	protected $groupFilters = array();
	protected $openAccessFilter = false;
	protected $expand = false;
	protected $sortOptions = array();
	/**
	 * @var string
	 */
	protected $defaultSort = 'relevance';
	protected $query;
	protected $filters = array();
	protected $rangeFilters = array();

	/**
	 * @var int
	 * Number of results to return from Talpa
	 */
	protected $limit= 100;
	/**
	 * @var int
	 */
	protected $page = 1;
	/**
	 * @var int
	 */
	protected $maxRecDb = 2;
	protected $bookMark;
	protected $debug = false;

	// Facets
	protected $facetLimit = 30;
	protected $facetOffset = null;
	protected $facetPrefix = null;
	protected $facetSort = null;

	protected $lightWeightRes = false;
	protected $sort = null;
	  /**
	 * @var string mixed
	 */
	private $searchIndex = 'Title';
	/**Facets, filters and limiters */
	//Values for the main facets - each has an array of available values
	protected $facets = [
//		'Author,or',
//		'ContentType,or,1,30',
//		'SubjectTerms,or,1,30',
//		'Discipline,or,1,30',
//		'Language,or,1,30',
//		'DatabaseName,or,1,30',
//		'SourceType,or,1,30',
	];

	protected $limits = [
//		'IsPeerReviewed,or,1,30',
//		'IsScholarly,or,1,30',
	];

	protected $rangeFacets = [
	];

	protected $limitList = [];
	protected $limitFields;


	protected $facetFields;

	public function __construct() {
		//Initialize properties with default values
		$this->searchSource = 'talpa';
		$this->searchType = 'talpa';
		$this->resultsModule = 'Talpa';
		$this->resultsAction = 'Results';


	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = null) {
		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.


		$restored = $this->restoreSavedSearch();
		if ($restored === true) {
			//there is a saved search that can be reused
			return true;
		} elseif ($restored instanceof Exception) {
			//there is an error with hte restored search
			return false;
		}
		//Carry out a new search
		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initSort();
		$this->initFilters();
		$this->initLimiters();

		//********************
		// Basic Search logic
		if (!$this->initBasicSearch()) {
			$this->initAdvancedSearch();
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
		}
		return true;
	}

	/**
	 * Create an instance of the Talpa Searcher
	 * @return SearchObject_TalpaSearcher
	 */
	 public static function getInstance() {
	if (SearchObject_TalpaSearcher::$instance == null) {
		SearchObject_TalpaSearcher::$instance = new SearchObject_TalpaSearcher();
		}
		return SearchObject_TalpaSearcher::$instance;
	}

	/**
	 * Retreive settings for institution's talpa connector
	*/
	private function getSettings() {
		global $library;
		if ($library->talpaSettingsId != -1) {
			$talpaSettings = new TalpaSettings();
			$talpaSettings->id = $library->talpaSettingsId;
			if (!$talpaSettings->find(true)) {
				$talpaSettings = null;
			}
			return $talpaSettings;
		}
		AspenError::raiseError(new AspenError('There are no Talpa Settings set for this library system.'));
	}

	public function getCurlConnection() {
		if ($this->curl_connection == null) {
			$this->curl_connection = curl_init();
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
		}
		return $this->curl_connection;
	}

	public function getHeaders() {
		$headers = array(
			'Accept' => 'application/'.$this->responseType,
			'x-talpa-date' => date('D, d M Y H:i:s T'),
		);
		return $headers;
	}


	public function  authenticate($settings) {
		$headers = $this->getHeaders();
		$headers['token'] = $settings->talpaApiToken;
		return $headers;
	}

	public function getSort() {
		$this->sortOptions = array(
			'Relevance',
		);
	}

//	//Build an array of options that will be passed into the final query string that will be sent to the Talpa API
//	public function getOptions () {
//		//Search terms in an array with the index of your search and your search terms. We must add the index to the query and then add the 'look for' terms.
//		$searchQuery = $this->searchTerms[0]['index'].':('.implode('&', array_slice($this->searchTerms[0],1)).')';
//		$options = array(
//			's.q' => $searchQuery,
//			//Results per page
//			's.ps' => $this->limit,
//			//Page number
//			's.pn' => $this->page,
//			//In library collection - can be implemented for libraries as required
//			's.ho' => $this->holdings ? 'true' : 'false',
//			//Query suggestions - can be implemented for libraries as required
//			's.dym' => $this->didYouMean ? 'true' : 'false',
//			//Default English
//			's.l' => $this->language,
//			//Fetch specific records
//			's.fids' =>$this->idsToFetch,
//			//Side facets to filter by
//			's.ff' =>array_merge($this->facets, $this->limits),
//			//Filters that are active - from side facets
//			's.fvf' => $this->getTalpaFilters(),
//			//Default 1
//			's.rec.topic.max' => $this->maxTopics,
//			//Filters
//			's.fvgf' => $this->groupFilters,
//			//Range Facets
//			's.rff' => $this->rangeFacets,
//			//Filters
//			's.rf' => $this->rangeFilters,
//			//Order results
//			's.sort' => $this->getSort(),
//			//False by default
//			's.exp' => $this->expand ? 'true' : 'false',
//			//False by default
//			's.oaf' => $this->openAccessFilter ? 'true' : 'false',
//			//To bookmark an item so you can retreive it later
//			's.bookMark' => $this->bookMark,
//			//False by default
//			's.debug' => $this->debug ? 'true' : 'false',
//			//False by default - recommend journals
//			's.rec.jt' => $this->journalTitle ? 'true' : 'false',
//			//False by default
//			's.light' => $this->lightWeightRes ? 'true' : 'false',
//			//2 by default - max database reccomendations
//			's.rec.db.max' => $this->maxRecDb,
//			//allows access to records
//			's.role' =>  'authenticated',
//		);
//		return $options;
//	}

	/**
	 * Use the data that is returned when from the API and process it to assign it to variables
	 */
	public function processData($recordData, $textQuery = null)
	{
		global $configArray;
		require_once ROOT_DIR.'/sys/SolrConnector/GroupedWorksSolrConnector2.php';
		$GroupedWorksSolrConnector2 = new GroupedWorksSolrConnector2($configArray['Index']['url']);

		$recordData = $this->process($recordData, $textQuery);

		if (is_array($recordData)) {
			$this->lastSearchResults = $recordData;
			$this->lastSearchResults['response']['talpa_result_count'] = 0;
			$this->lastSearchResults['response']['global_count'] = 0;
			$resultsList = $recordData['response']['resultlist'];
//var_dump($resultsList);
			$this->startRecordFetchTimer();
			$inLibraryResults = array();
			$allGroupedWorks = explode(',',$recordData['response']['aspen']['all_grouped_workidA'] );
			$allGroupedWorks_chunked = array_chunk($allGroupedWorks,20, true);
			foreach ($allGroupedWorks_chunked as $chunk) {
				$foundGroupedWorks = $GroupedWorksSolrConnector2->searchForRecordIds($chunk);

				foreach ($foundGroupedWorks['response']['docs'] as $recordItem) {
					extract($recordItem);
					$inLibraryResults[$id] = $recordItem;
				}
			}

			for ($x = 0; $x < count($resultsList); $x++) {
				$current = &$resultsList[$x];

				require_once ROOT_DIR . '/RecordDrivers/TalpaRecordDriver.php';
				$record = new TalpaRecordDriver($current);

				$groupedWorkIds = $current['groupedworkidA'];
				$foundLibraryResult = false;
				foreach ($groupedWorkIds  as $groupedWorkId) {
					if ($inLibraryResults[$groupedWorkId]) {
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
						if ($groupedWorkDriver->isValid()) {
							$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
							if(!empty($relatedManifestations)) {
								//add the groupedWork data into the recordData
								$this->lastSearchResults['response']['resultlist'][$x]['groupedWork'] = $inLibraryResults[$groupedWorkId];
								$this->lastSearchResults['response']['resultlist'][$x]['inLibraryB'] = 1;
								$this->lastSearchResults['response']['resultlist'][$x]['groupedWorkID'] = $groupedWorkId;
								//add solr data into recordData
								$_recordData = $GroupedWorksSolrConnector2->getRecord($groupedWorkId, $this->getFieldsToReturn());
								$this->lastSearchResults['response']['resultlist'][$x]['solrRecord'] = $_recordData;

								//get count of in-library records
								$this->lastSearchResults['response']['global_count']++;
								$this->resultsTotal++;
								$foundLibraryResult = true;
								break;
							}
						}
					}
				}
				if(!$foundLibraryResult) {

					$bibInfo = $record->getRecord();
					if(!empty($bibInfo['isbns'])) {
						$this->lastSearchResults['response']['resultlist'][$x]['inLibraryB'] = 0;
						$this->lastSearchResults['response']['resultlist'][$x]['hasIsbnB'] = 1;
						$this->lastSearchResults['response']['resultlist'][$x]['hasIsbnB'] = 1;
						$this->lastSearchResults['response']['resultlist'][$x]['author'] = $bibInfo['author'];
						$this->lastSearchResults['response']['resultlist'][$x]['pubYear'] = $bibInfo['date'];
						$this->lastSearchResults['response']['talpa_result_count']++;
						$this->resultsTotal++;
					}

				}

//				$this->resultsTotal = count($resultsList);
			}
			$this->stopRecordFetchTimer();

			return $recordData;
		}
		return false;
	}
	public function splitFacets($combinedFacets) {
		$splitFacets = [];
		foreach($combinedFacets as $facet) {
			foreach ($this->facets as $facetName) {
				if (strpos($facetName, $facet['displayName']) !== false) {
					$splitFacets['facetFields'][] = $facet;
				}
			}
			foreach ($this->limits as $limitName) {
				if (strpos($limitName, $facet['displayName']) !== false) {
					$splitFacets['limitFields'][] = $facet;
				}
			}
		}
		return $splitFacets;
	}

	/**
	 * Return an array of data summarising the results of a search.
	 *
	 * @access  public
	 * @return  array   summary of results
	 */
	public function getResultSummary() {
		$summary = [];
		$summary['page'] = $this->page;
		$summary['perPage'] = $this->limit;

		if (!empty($_REQUEST['filter']) && !empty($_REQUEST['filter'][0])) {
			preg_match('/availability_toggle:"(.*?)"/', $_REQUEST['filter'][0], $matches);
			$locationFilter = $matches[1];
		} else{
			$locationFilter = 'global';
		}
		if(($locationFilter == 'global' || !$locationFilter) && (int)$this->lastSearchResults['response']['global_count']>=1) {
			$this->resultsTotal = (int)$this->lastSearchResults['response']['global_count'];

		}else{
			$this->resultsTotal = (int)$this->lastSearchResults['response']['talpa_result_count'];
			$talpaSettings = $this->getSettings();

			if(!$talpaSettings->includeTalpaOtherResultsSwitch){
				$this->resultsTotal = 0;
			}
		}

		$summary['resultTotal'] = (int)$this->resultsTotal;
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

	 /** Return a url for use by pagination template
	 *
	 * @access  public
	 * @return  string   URL of a new search
	 */
	public function renderLinkPageTemplate() {
		// Stash our old data for a minute
		$oldPage = $this->page;
		// Add the page template
		$this->page = '%d';
		$this->page = '%d';
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results. Called by results.php.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML() {
		global $interface;
		global $timer;
		$html = [];
		$timer->logTime("Starting to load record html");

		if (isset($this->lastSearchResults)) {
			if (!empty($_REQUEST['filter']) && !empty($_REQUEST['filter'][0])) {
				preg_match('/availability_toggle:"(.*?)"/', $_REQUEST['filter'][0], $matches);
				$locationFilter = $matches[1];
			}else{
				$locationFilter = null;
			}

			$_resultList = $this->lastSearchResults['response']['resultlist'];

			//used in getSearchResult() to generate item url to return to talpa search results page
			$interface->assign('searchSource', 'talpa');

			$talpaSettings = $this-> getSettings();

			$inLibraryResults = array();
			$talpaResults = array();
			foreach ($_resultList as $record) {
				if(!empty($record['inLibraryB'])){
					$inLibraryResults[] = $record;
				}elseif(!empty($record['hasIsbnB']) && $talpaSettings->includeTalpaOtherResultsSwitch){
					$talpaResults[] = $record;
				}
			}

			$inLibraryB = false;
			$talpaSettings = $this->getSettings();
			$searchString = $talpaSettings->talpaSearchSourceString?:'Talpa Search';
			$_SESSION['talpaBreadcrumb'] = $searchString.': Other Results';
			if (($locationFilter=='global' || !$locationFilter) && $this->lastSearchResults['response']['global_count']>=1){
				$resultlist = $inLibraryResults;
				$_SESSION['talpaBreadcrumb'] = $searchString.': Library Results';
				$inLibraryB=true;
			} elseif ($locationFilter=='talpa_result') {
				$resultlist = $talpaResults;

			} elseif (!$locationFilter && count($inLibraryResults)==0) {
				$resultlist = $talpaResults;
			} else{
				$resultlist = [];
			}
			$this->lastSearchResults['response']['global_count']++;

			for ($x = 0; $x < count($resultlist); $x++) {
				$current = &$resultlist[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				require_once ROOT_DIR . '/RecordDrivers/TalpaRecordDriver.php';
				$record = new TalpaRecordDriver($current);
				$interface->assign('recordDriver', $record);
				$html[] = $interface->fetch($record->getSearchResult($inLibraryB));
			}
		} $this->addToHistory();

		return $html;
	}


	/**
	 * Return a url for the current search with a new sort
	 *
	 * @access  public
	 * @param string $newSort A field to sort by
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithSort($newSort) {
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
	 * Called in Results.php
	 * Controls side facets
	 */
	public function getFacetSet() {
		$availableFacets = [];
		$this->filters = [];
		if (isset($this->facetFields)) {
			foreach ($this->facetFields as $facetField) {
				$facetId = $facetField['displayName'];
				//results array does not return human readable option
				$parts = preg_split('/(?=[A-Z])/', $facetId, -1, PREG_SPLIT_NO_EMPTY);
				$displayName = implode(' ', $parts);
				$availableFacets[$facetId] = [
					'collapseByDefault' => true,
					'multiSelect' =>true,
					'label' =>$displayName,
					'valuesToShow' =>5,
				];
				if ($facetId == 'ContentType') {
					$availableFacets[$facetId]['collapseByDefault'] = false;
				}

				if ($facetId == 'IsScholarly' || $facetId == 'IsPeerReviewed') {
					$availableFacets[$facetId]['multiSelect'] = false;
				}

				$list = [];
				foreach ($facetField['counts'] as $value) {
					$facetValue = $value['value'];
					//Ensures selected facet stays checked when selected - interacts with .tpl
//					var_dump($this->filterList);
					$filtersA = array('global', 'talpa_result');
//					var_dump($facetId);
					$isApplied = array_key_exists($facetId, $this->filterList) && in_array($facetValue, $this->filterList[$facetId]);
					$facetSettings = [
						'value' => $facetValue,
						'display' =>$facetValue,
						'count' =>$value['count'],
						'isApplied' => $value['isApplied'],
					];
					$queryId = $this->lastSearchResults['response']['query_id'];


					if ($isApplied) {
						$facetSettings['removalUrl'] = $this->renderLinkWithoutFilter($facetId . ':' . $facetValue);
					} else {
						$facetSettings['url'] = $this->renderSearchUrl() . '&filter[]=' . $facetId . ':' . urlencode($facetValue) . '&page=1';
					}
					$list[] = $facetSettings;
				}
				$availableFacets[$facetId]['list'] = $list;
			}
		}
		return $availableFacets;
	}


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



		$currentResults = $this->lastSearchResults['response']['resultlist'];

		$allFacets=array();
		$facetCounts = array(
			'global' => [
				'count' => 0
			],
			'talpa_result' => [
				'count' => 0
			],
		);
		$talpaSettings = $this ->getSettings();

		foreach ($currentResults as $resultKey => $result){
			$inLibraryB = !empty($result['inLibraryB']) ? $result['inLibraryB'] : 0;
			$hasIsbnB = !empty($result['hasIsbnB']) ? $result['hasIsbnB'] : 0;
			$talpaResultB = $hasIsbnB && $talpaSettings->includeTalpaOtherResultsSwitch; //if library allows Other Results

			if($inLibraryB)
			{
				$solrRecord = $result['solrRecord'];
				$availableAt = $solrRecord['available_at'];
				if($availableAt)
				{
					$facetCounts['global']['count']++;
					$allFacets['availability_toggle'][]= array('global', 1);
				}

			} elseif ($talpaResultB) {
//				$allFacets['availability_toggle']['talpa_result']['count']++;
				$facetCounts['talpa_result']['count']++;
				$allFacets['availability_toggle'][]= array('talpa_result', 1);
			}

		}

		if(empty($this->filterList )) {
			if ($facetCounts['global']['count'] >=1) {
				$this->filterList['availability_toggle'][0] = 'global';
			} else {
				$this->filterList['availability_toggle'][0] = 'talpa_result';
			}
		}


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


			// Should we translate values for the current facet?
			$translate = $facetConfig[$field]->translate;
			$numValidRelatedLocations = 0;
			$numValidLibraries = 0;
			// Loop through values:
//			$isScopedField = $this->isScopedField($field);

			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = [];
				$facetValue = $facet[0];
//				if ($isScopedField && strpos($facetValue, '#') !== false) {
//					$facetValue = substr($facetValue, strpos($facetValue, '#') + 1);
//				}
				$currentSettings['value'] = $facetValue;
				$currentSettings['display'] = $translate ? translate([
					'text' => $facetValue,
					'isPublicFacing' => true,
					'isMetadata' => true,
					'escape' => true,
				]) : htmlentities($facetValue);
				$currentSettings['count'] = $facetCounts[$facetValue]['count'];
				$currentSettings['isApplied'] = $this->filterList['availability_toggle'][0]==$facetValue;

				$baseUrl = $this->renderLinkWithFilter($field, $facetValue);
				$queryID = $this->lastSearchResults['response']['query_id'];
				$currentSettings['url'] = $baseUrl.'&queryId='.$queryID;

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



				// Store the collected values:
				if ($okToAdd) {
					$list[$field]['list'][$valueKey] = $currentSettings;
				}
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



	public function getLimitList() {

		$availableLimits=[];
		if (isset($this->limitFields)){
			foreach($this->limitFields as $limitOption){
				$limitId = $limitOption['displayName'];
				$parts = preg_split('/(?=[A-Z])/', $limitId, -1, PREG_SPLIT_NO_EMPTY);
				$displayName = implode(' ', $parts);

				foreach($limitOption['counts'] as $value){
					if ($value['value'] == 'true') {
						$isApplied = isset($this->limiters[$limitId]) && $this->limiters[$limitId] == 'y' ? 1 : 0;

						$availableLimits[$limitId] = [
							'display' => $displayName,
							'value' => $limitId,
							'isApplied' => $isApplied,
							'url' => $this->renderLinkWithLimiter($limitId),
							'removalUrl' => $this->renderLinkWithoutLimiter($limitId),
						];
					}
				}

			}
		}
		return $availableLimits;
	}

	public function createSearchLimits() {
		foreach ($this->limiters as $limiter => $limiterOptions) {
			if ($this->limiters[$limiter] == 'y') {
				$this->limitList[$limiter] = $limiterOptions;
			}
		}
		return $this->limitList;
	}


	//Compile filter options chosen in side facets and add to filter array to be passed in via options array
	public function getTalpaFilters() {
		$this->filters = array();
		$this->createSearchLimits();
		if (isset($this->limitList) && isset($this->filterList)) {
			$this->filterList = array_merge($this->limitList, $this->filterList);
		}
		foreach ($this->filterList as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $val) {
					$encodedValue = urlencode($val);
					$this->filters[] = urlencode($key) . ',' . $encodedValue . ',';
				}
			} else {
				$encodedValue = urlencode($value);
				$this->filters[] = urlencode($key) . ',' . $encodedValue . ',';
			}
		}
		return $this->filters;
	}


	/**
	 * Generate an HMAC hash for authentication
	 *
	 * @param string $key  Hash key
	 * @param string $data Data to hash
	 *
	 * @return string	  Generated hash
 	*/
	protected function hmacsha1($key, $data) {
		$blocksize=64;
		$hashfunc='sha1';
		if (strlen($key)>$blocksize) {
			$key=pack('H*', $hashfunc($key));
		}
		$key=str_pad($key, $blocksize, chr(0x00));
		$ipad=str_repeat(chr(0x36), $blocksize);
		$opad=str_repeat(chr(0x5c), $blocksize);
		$hmac = pack(
			'H*', $hashfunc(
				($key^$opad).pack(
					'H*', $hashfunc(
						($key^$ipad).$data
					)
				)
			)
		);
		return base64_encode($hmac);
	}

	/**
	 * Send a fully built query string to the API with user authentication - called in Results.php
	 * @throws Exception
	 * @return object API response
	 */
	public function sendRequest($queryId=null) {
		$baseUrl = $this->talpaBaseApi;
		$settings = $this->getSettings();

		$queryString = $this->searchTerms[0]['lookfor']?:'The man with the yellow hat';
		$this->query = $queryString;
		// Perform preliminary search of the library catalog
		$preliminaryResults = $this->performPreliminarySearch($queryString);
		$this->preliminarySearchResults = $preliminaryResults;


		if(!$settings->talpaApiToken) {
			$msg = $settings->talpaSearchSourceString.' settings are not configured by your library: missing API token.';
			AspenError::raiseError(new AspenError($msg));
		}

		$headers = $this->authenticate($settings);
		$this->startQueryTimer();
		$recordData = $this->httpRequest($baseUrl, $queryString, $headers, $settings, $queryId);
		$json_response = json_decode($recordData, true);
		if( $json_response['error'])
		{
			$code = $json_response['error']['code'];
			$wording = $json_response['error']['wording'];
			$msg = $wording . ' ('.$code.')';
			AspenError::raiseError(new AspenError($msg));
		}

		if (!empty($recordData)){
			$this->processData($recordData);
			$recordData = $this->lastSearchResults;
			$this->stopQueryTimer();
		}
		$this->processSearch(); //Add in facets for recommendations to use

		$this->initRecommendations();
		if ( is_array($this->recommend)) {
			foreach ($this->recommend as $currentSet) {
				/** @var RecommendationInterface $current */
				foreach ($currentSet as $current) {
					$current->process();
				}
			}
		}


		$_SESSION['last_query_id'] = $this->lastSearchResults['response']['query_id'];

		$_SESSION['last_recordData'] = serialize($recordData);

		return $recordData;
	}

	public function processRepeatedSearch($result) {

	$this->resultsTotal = count($result['response']['resultlist']);
	$this->lastSearchResults = $result;
		$this->processSearch(); //Add in facets for recommendations to use
		if (1) {
			$this->initRecommendations();
			if ( is_array($this->recommend)) {
//				print_r($this->recommend);
				foreach ($this->recommend as $currentSet) {
					/** @var RecommendationInterface $current */
					foreach ($currentSet as $current) {
//						print_r($current);
						$current->process();
					}
				}
			}
		}
	}

	public function getRecommendationsTemplates($location = 'top') {
		$returnValue = [];
//		if (isset($this->recommend[$location]) && !empty($this->recommend[$location])) {
			foreach ($this->recommend[$location] as $current) {
				$returnValue[] = $current->getTemplate();
			}
//		}
		return $returnValue;
	}


	public function process($input, $textQuery = null) {
		//handles error reporting
		if (SearchObject_TalpaSearcher::$searchOptions == null ||
			SearchObject_TalpaSearcher::$searchOptions['textQuery'] != $textQuery ) {
			if ($this->responseType != 'json') {
				return $input;
			}
			SearchObject_TalpaSearcher::$searchOptions = json_decode($input, true);
			$resultsList = SearchObject_TalpaSearcher::$searchOptions ['response']['resultlist'];
			$warnings = SearchObject_TalpaSearcher::$searchOptions ['response']['warnings'];

			if (!SearchObject_TalpaSearcher::$searchOptions) {
				SearchObject_TalpaSearcher::$searchOptions = array(
					'recordCount' => 0,
					'documents' => array(),
					'errors' => array(
						array(
							'code' => 'PHP-Internal',
							'message' => 'Cannot decode JSON response.'
						)
					)
				);
			} elseif( !is_array(SearchObject_TalpaSearcher::$searchOptions))
			{
				SearchObject_TalpaSearcher::$searchOptions = array(
					'recordCount' => 0,
					'documents' => array(),
					'errors' => array(
						array(
							'code' => 'API Error 1',
							'message' => 'Talpa Results not in readable format.'
						)
					)
				);
			} elseif( empty($resultsList) && !$warnings)
			{
				SearchObject_TalpaSearcher::$searchOptions = array(
					'recordCount' => 0,
					'documents' => array(),
					'errors' => array(
						array(
							'code' => 'API Error 2',
							'message' => 'Talpa returned 0 results.'
						)
					)
				);
			}

			$talpaSettings = $this -> getSettings();
			$searchSourceString = $talpaSettings->talpaSearchSourceString?:'Talpa Search';
			require_once ROOT_DIR . '/services/Talpa/TalpaWarning.php';

			// Detect errors
			if (isset(SearchObject_TalpaSearcher::$searchOptions['errors']) && is_array(SearchObject_TalpaSearcher::$searchOptions['errors'])) {
				foreach (SearchObject_TalpaSearcher::$searchOptions['errors'] as $current) {
					$errors[] = "{$current['code']}: {$current['message']}";

					$msg = $searchSourceString.' encountered an error while processing your request: '. $current['message'];
				}


				$retryLink = '';
				if (!empty($this->searchTerms[0]['lookfor'])) {
					$retryLink = $this->renderSearchUrl();
					$retryLink = str_replace('/Search/Results','/Union/Search', $retryLink);
				}



				$TalpaWarning = new TalpaWarning();
				$TalpaWarning->launchError($msg, $retryLink);
				exit();
			}
			elseif (isset($warnings) && is_array($warnings))
			{
				foreach ($warnings as $current) {
					$header = 'Unable to display results.';
					$msg = $current['wording'];

					if($current['reason'] =='selfharm') {
						$header = 'Help is available.';
						$msg = preg_replace('/Help is available\./', '', $msg);
					}elseif ($header == $msg)
					{
						$msg = '';
					}

					global $interface;
					$interface->assign('header', $header);
					$interface->assign('msg', $msg);


				if($current['stop'] || empty($resultsList)) {
				SearchObject_TalpaSearcher::$searchOptions = array(
						'recordCount' => 0,
						'documents' => array(),
						'warnings' => array(
							array(
								'code' => 'API Offensive Warning',
								'message' => $current['wording'],
								'stop'	=> $current['stop'],
								'reason' => $current['reason']
							)
						)
					);

					$TalpaWarning = new TalpaWarning();
					$TalpaWarning->launch();
					exit();
				} else {
					$interface->assign('talpa_warning', $current['wording']);
					$_SESSION['talpa_warning'] = $current['wording'];
					}
				}
			}
			if (SearchObject_TalpaSearcher::$searchOptions) {
				return SearchObject_TalpaSearcher::$searchOptions;
			} else {
				return null;
			}
		} else {
			return SearchObject_TalpaSearcher::$searchOptions;
		}
	}

	/**
	 * Send HTTP request with headers modified to meet Talpa API requirements
	 */
	protected function httpRequest($baseUrl, $queryString, $headers, $settings, $queryId=null) {
		foreach ($headers as $key =>$value) {
			$modified_headers[] = $key.": ".$value;
		}
		global $activeLanguage;;

		$params = array(
			'search' => $queryString,
			'token' => $headers['token'],
			'limit' => $this ->getLimit(),
			'aspen'=> 1,
			'lang_code' => $activeLanguage->code,
		);
		if($queryId) {
			$params['query_id'] = $queryId;
		}

		$requestUrl = $baseUrl.'?'.http_build_query($params);


		if ($this->preliminarySearchResults) {
			$isbns = $this->preliminarySearchResults['isbns'];
			$groupedWorkIds = $this->preliminarySearchResults['groupedWorkIds'];

//			if (!empty($isbns)) {
//				$isbnParam = '&isbns=' . urlencode(implode(',', $isbns));
//				$requestUrl .= $isbnParam;
//			}

			if (!empty($groupedWorkIds)) {
				$groupedWorkParam = '&grouped_work_ids=' . urlencode(implode(',', $groupedWorkIds));
				$requestUrl .= $groupedWorkParam;
			}
		}
		$curlConnection = $this->getCurlConnection();
		$curlOptions = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $requestUrl,
			CURLOPT_HTTPHEADER => $modified_headers
		);


		curl_setopt_array($curlConnection, $curlOptions);
		$result = curl_exec($curlConnection);
		if ($result === false) {
			$msg = $settings->talpaSearchSourceString.' encountered an error while processing your request.';
			AspenError::raiseError(new AspenError($msg));
		}

		 curl_close($curlConnection);
		return $result;
	}

	/**
	 * Start the timer to work out how long a query takes.  Complements
	 * stopQueryTimer().
	 *
	 * @access protected
	 */
	protected function startQueryTimer() {
		// Get time before the query
		$time = explode(" ", microtime());
		$this->queryStartTime = $time[1] + $time[0];
	}

	/**
	 * End the timer to work out how long a query takes.  Complements
	 * startQueryTimer().
	 *
	 * @access protected
	 */
	protected function stopQueryTimer() {
		$time = explode(" ", microtime());
		$this->queryEndTime = $time[1] + $time[0];
		$this->queryTime = $this->queryEndTime - $this->queryStartTime;
	}

	/**
	 * Start the timer to work out how long it takes to fetch record data.
	 * stopRecordFetchTimer().
	 *
	 * @access protected
	 */
	protected function startRecordFetchTimer() {
		// Get time before the query
		$time = explode(" ", microtime());
		$this->recordFetchStartTime = $time[1] + $time[0];
	}

	/**
	 * End the timer to work out how long a query takes.  Complements
	 * startRecordFetchTimer().
	 *
	 * @access protected
	 */
	protected function stopRecordFetchTimer() {
		$time = explode(" ", microtime());
		$this->recordFetchEndTime = $time[1] + $time[0];
		$this->recordFetchTime = $this->recordFetchEndTime - $this->recordFetchStartTime;
	}

	/**
	 * Start the timer to work out how long it takes to do a preliminary search of the catalog for results that Talpa can review.
	 *
	 * @access protected
	 */
	protected function startPreliminarySearchTimer() {
		$time = explode(" ", microtime());
		$this->preliminarySearchStartTime = $time[1] + $time[0];
	}

	/**
	 * End the timer to work out how long a preliminary query takes.  Complements
	 * startPreliminarySearchTimer().
	 *
	 * @access protected
	 */
	protected function stopPreliminarySearchTimer() {
		$time = explode(" ", microtime());
		$this->preliminarySearchEndTime = $time[1] + $time[0];
		$this->preliminarySearchTime = $this->preliminarySearchEndTime - $this->preliminarySearchStartTime;

	}


	/**
	 * Work out how long the query took
	 */
	public function getQuerySpeed() {
		return $this->queryTime;
	}

	/**
	 * Work out how long the record fetch
	 */
	public function getRecordFetchSpeed() {
		return $this->recordFetchTime;
	}

	/**
	 * Work out how long the record fetch
	 */
	public function getPreliminarySearchSpeed() {
		return $this->preliminarySearchTime;
	}

	 /**
	  * Search indexes
	  */
	 public function getSearchIndexes() {
		return [
			"Title" => translate([
				'text' => "Ask me anything",
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	//Default search index
	public function getDefaultIndex() {
		return $this->searchIndex;
	}

	public function setSearchTerm() {
		if (strpos($this->searchTerms, ':') !== false) {
			[
				$searchIndex,
				$term,
			] = explode(':', $this->searchTerms, 2);
			$this->setSearchTerms([
				'lookfor' => $term,
				'index' => $searchIndex,
			]);
		} else {
			$this->setSearchTerms([
				'lookfor' => $this->searchTerms,
				'index' => $this->getDefaultIndex(),
			]);
		}
	}

	public function getIndexError() {
		// TODO: Implement getIndexError() method.
	}

	public function buildRSS($result = null) {
		// TODO: Implement buildRSS() method.
	}

	public function buildExcel($result = null) {
		// TODO: Implement buildExcel() method.
	}

	public function getResultRecordSet() {
		// TODO: Implement getResultRecordSet() method.
	}

	function getSearchName() {
		return $this->searchSource;
	}

	function loadValidFields() {
		// TODO: Implement loadValidFields() method.
	}

	function loadDynamicFields() {
		// TODO: Implement loadDynamicFields() method.
	}

	public function getEngineName() {
		return 'talpa';
	}

	function getSearchesFile() {
		return 'talpaSearches';

	}

	public function getSessionId() {
		return $this->sessionId;
	}

	public function getresultsTotal(){
		return $this->resultsTotal;
	}

	/**
	 * Perform a preliminary keyword search of the library catalog
	 * @param string $queryString The search query
	 * @return array Array containing ISBNs and groupedWorkIDs found
	 */
	protected function performPreliminarySearch($queryString) {
		global $configArray;

		$this->startPreliminarySearchTimer();

		require_once ROOT_DIR.'/sys/SolrConnector/GroupedWorksSolrConnector2.php';
		$solrConnector = new GroupedWorksSolrConnector2($configArray['Index']['url']);


		// Perform a basic keyword search
		$searchResults = $solrConnector->search($queryString, 'Keyword', [], 0, 50, [], '', '', null, 'id,isbn,primary_isbn', 'POST', false);
		$this->stopPreliminarySearchTimer();

		$isbns = [];
		$groupedWorkIds = [];

		if ($searchResults && isset($searchResults['response']['docs'])) {
			foreach ($searchResults['response']['docs'] as $doc) {

				if (isset($doc['id'])) {
					$groupedWorkIds[] = $doc['id'];
				}

				if (isset($doc['isbn'])) {
					if (is_array($doc['isbn'])) {
						$isbns = array_merge($isbns, $doc['isbn']);
					} else {
						$isbns[] = $doc['isbn'];
					}
				}

				if (isset($doc['primary_isbn'])) {
					if (is_array($doc['primary_isbn'])) {
						$isbns = array_merge($isbns, $doc['primary_isbn']);
					} else {
						$isbns[] = $doc['primary_isbn'];
					}
				}
			}
		}

		// Remove duplicates
		$isbns = array_unique($isbns);
		$groupedWorkIds = array_unique($groupedWorkIds);

		return [
			'isbns' => $isbns,
			'groupedWorkIds' => $groupedWorkIds,
			'searchTime' => $this->preliminarySearchTime
		];
	}


	/**
	 * Get the preliminary search results
	 * @return array|null
	 */
	public function getPreliminarySearchResults() {
		return $this->preliminarySearchResults;
	}

	public function processSearch(bool $returnIndexErrors = false, bool $recommendations = true, bool $preventQueryModification = false): SimpleXMLElement|array|AspenError|stdClass|null
	{
		 global $solrScope;

		global $library;
		$location = Location::getSearchLocation(null);
		if ($location != null) {
			$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
		} else {
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		}

		$facetSet = [];

		if (empty($this->selectedAvailabilityToggleValue)) {
			$this->selectedAvailabilityToggleValue = 'global';
		}
		if (empty($selectedAvailableAtValues)) {
			$selectedAvailableAtValues[] = '*';
		}

		foreach ($selectedAvailableAtValues as $selectedAvailableAtValue) {
			$selectedAvailableAtValue = str_replace('(', '\(', $selectedAvailableAtValue);
			$selectedAvailableAtValue = str_replace(')', '\)', $selectedAvailableAtValue);
		}


		// Build a list of facets we want from the index
		$facetConfig = $this->getFacetConfig();

		if ($recommendations && !empty($facetConfig)) {

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

		}
		if (!empty($this->facetSearchTerm) && !empty($this->facetSearchField)) {
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains"] = $this->facetSearchTerm;
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains.ignoreCase"] = 'true';
		}
		if (!empty($this->facetOptions)) {
			$facetSet['additionalOptions'] = $this->facetOptions;
		}

		return null;
	}

	public function getFacetConfig() {
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
			$searchVersion = SystemVariables::getSystemVariables()->searchVersion;
			foreach ($facets as &$facet) {
				//Adjust facet name for local scoping

				$facet->facetName = $this->getScopedFieldName($facet->getFacetName($searchVersion));

				global $action;
				if ($action == 'Advanced') {
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

	protected function getFieldsToReturn() {
		if (isset($_REQUEST['allFields'])) {
			$fieldsToReturn = '*,score';
		} else {
			$fieldsToReturn = 'collection';
			$fieldsToReturn .= ',detailed_location';
			$fieldsToReturn .= ',owning_location';
			$fieldsToReturn .= ',owning_library';
			$fieldsToReturn .= ',available_at';
			$fieldsToReturn .= ',itype';
			$fieldsToReturn .= ',score';
		}
		return $fieldsToReturn;
	}



	public function __destruct() {
		if ($this->curl_connection) {
			curl_close($this->curl_connection);
		}
	}

	public function getRecords($ids) {
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/TalpaRecordDriver.php';
		foreach ($ids as $index => $id) {
			$records[$index] = new TalpaRecordDriver($id);
		}
		return $records;
	}


}
