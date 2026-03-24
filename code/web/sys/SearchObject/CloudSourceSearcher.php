<?php

require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_CloudSourceSearcher extends SearchObject_BaseSearcher{
	static $instance;
	/** @var CloudSourceSetting */

	/**Build URL */
	private $sessionId;
	private $version = '2.0.0';
	private $service = 'search';
	private $responseType = "json";

	private static $searchOptions;
	private $curl_connection;

	/**Track query time info */
	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

	// STATS
	protected $resultsTotal = 0;
	protected $searchTerms;
	protected $lastSearchResults;

	/** @var string */
	protected $searchSource = 'local';
	protected $searchType = 'basic';

	/** Values for the options array*/
	protected $holdings = true;
	protected $didYouMean = false;
	protected $language = 'en';

	protected $expand = true;
	protected $sortOptions = array();
	/**
	 * @var string
	 */
	protected $defaultSort = 'relevance';
	protected $query;
	protected $filters = array();

	/**
	 * @var int
	 */
	protected $limit= 20;
	/**
	 * @var int
	 */
	protected $page = 1;

	protected $debug = false;
	protected $journalTitle = false;
	protected $lightWeightRes = true;
	protected $sort = null;
	/**
	 * @var string mixed
	 */
	private $searchIndex = '';
	protected $facetFields;

	public function __construct() {
		parent::__construct();
		//Initialize properties with default values
		$this->searchSource = 'cloudsource';
		$this->searchType = 'cloudsource';
		$this->resultsModule = 'CloudSource';
		$this->resultsAction = 'Results';
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 */
	public function init(?string $searchSource = null) : bool {
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
	 * Create an instance of the CloudSource Searcher
	 * @return SearchObject_CloudSourceSearcher
	 */
	public static function getInstance() {
		if (SearchObject_CloudSourceSearcher::$instance == null) {
			SearchObject_CloudSourceSearcher::$instance = new SearchObject_CloudSourceSearcher();
		}
		return SearchObject_CloudSourceSearcher::$instance;
	}

	/**
	 * Retreive settings for institution's CloudSource connector
	 */
	private function getSettings() {
		global $library;
		require_once ROOT_DIR . '/sys/CloudSource/LibraryCloudSourceSetting.php';
		$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
		$libraryCloudSourceSetting->libraryId = $library->libraryId;
		if ($libraryCloudSourceSetting->find(true)){
			require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
			$cloudSourceSetting = new CloudSourceSetting();
			$cloudSourceSetting->id = $libraryCloudSourceSetting->cloudsourceSettingId;
			if ($cloudSourceSetting->find(true)){
				return $cloudSourceSetting;
			}
		}
		AspenError::raiseError(new AspenError('There are no CloudSource OA Settings set for this library system.'));
	}

	public function getCurlConnection() {
		if ($this->curl_connection == null) {
			$this->curl_connection = curl_init();
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
		}
		return $this->curl_connection;
	}

	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) : AspenError|stdClass|array|null {
		$settings = $this->getSettings();
		$curlConnection = $this->getCurlConnection();
		$url = $settings->baseUrl . '/api/cloudsourcesearch/search';
		if (!empty($this->searchTerms) && is_array($this->searchTerms)) {
			$searchTerm = $this->searchTerms[0]['lookfor'];
		} else {
			$searchTerm = '';
		}

		$start = 0;
		if ($this->page > 1) {
			$start = 20 * ($this->page - 1);
		}

		//CloudSource uses access tokens for authorization, no other auth process
		$headers = [
			'Content-Type: application/vnd.sirsidynix.roa.roaobject.v2+json',
			'BCWS-Access-Token: ' . $settings->accessToken,
			'SD-Stats-Session-ID: ' . $_COOKIE['aspen_session']
		];
		$facets=[];
		if (!empty($this->filterList)) {
			foreach ($this->filterList as $facetField => $facetValue) {
				$facets[] = [
					'@ROAObject' => 'searchrequest/facetselection',
					'field' => $facetField,
					'navigators' => $facetValue
				];
			}
		}
		$params = [
			'@ROAObject' => 'searchrequest',
			'term' => $searchTerm,
			'start' => $start,
			'pageSize' => 20,
			'includeFields' => 'title,author{name},format{name},webUrl,publishDate,abstrakt,index',
			'includeFacets' => true,
			'profileKey' => $settings->profileKey,
			'facetSelections' => $facets
		];

		$params = json_encode($params);

		curl_setopt($curlConnection, CURLOPT_POST, true);
		curl_setopt($curlConnection, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curlConnection, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curlConnection, CURLOPT_URL, $url);

		$results = curl_exec($curlConnection);

		try {
			$searchData = json_decode($results);
			$this->stopQueryTimer();

			$searchHasError = false;
			if (!empty($searchData->messageList) && str_contains($searchData->messageList[0]->code, "Error")) {
				$searchHasError = true;
			}

			if ($searchData && !$searchHasError) {
				$this->resultsTotal = $searchData->total;
				$this->lastSearchResults = $searchData->searchResults;
				$this->facetFields = $searchData->facets;

				return $searchData->searchResults;
			} else {
				global $logger;
				if (IPAddress::showDebuggingInformation()) {
					$curlInfo = curl_getinfo($curlConnection);
					$logger->log(print_r($curlInfo, true), Logger::LOG_WARNING);
				}
				$this->lastSearchResults = false;
				if (!empty($searchData->messageList[0]->code)) {
					return new AspenError($searchData->messageList[0]->code . ': ' . $searchData->messageList[0]->message);
				}
				return new AspenError("Could not process search in CloudSource OA");
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error loading data from CloudSource OA: $e", Logger::LOG_ERROR);
			return new AspenError("Could not load data from CloudSource OA");
		}
	}

	//Retreive a specific record - used to retreive bookcovers
	public function retrieveRecord($id, $index = null) {
		$settings = $this->getSettings();
		//CloudSource uses access tokens for authorization, no other auth process
		$headers = [
			'Content-Type: application/vnd.sirsidynix.roa.roaobject.v2+json',
			'BCWS-Access-Token: ' . $settings->accessToken,
			'SD-Stats-Session-ID: ' . $_COOKIE['aspen_session']
		];

		$baseUrl = $settings->baseUrl . '/api/cloudsourcesearch/search/id/' . $index . '/' . $id;


		$recordData = $this->httpRequest($baseUrl, $headers);

		return json_decode($recordData);

	}

	/**
	 * Send HTTP request with headers modified to meet Summon API requirements
	 */
	protected function httpRequest($baseUrl, $headers) {
		$curlConnection = $this->getCurlConnection();
		$curlOptions = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => "{$baseUrl}",
			CURLOPT_HTTPHEADER => $headers
		);
		curl_setopt_array($curlConnection, $curlOptions);
		$result = curl_exec($curlConnection);
		if ($result === false) {
			throw new Exception("Error in HTTP Request.");
		}
		// curl_close($curlConnection);
		return $result;
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
			for ($x = 0; $x < count($this->lastSearchResults); $x++) {
				$current = &$this->lastSearchResults[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
				$record = new CloudSourceRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		} $this->addToHistory();
		return $html;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getCombinedResultHTML() {
		global $interface;
		$html = [];
		if (isset($this->lastSearchResults)) {
			foreach($this->lastSearchResults as $key=>$value){
				$interface->assign('recordIndex', $key + 1);
				$interface->assign('resultIndex', $key + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
				$record = new CloudSourceRecordDriver($value);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getCombinedResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		} else {
			$html[] = "Unable to find record";
		}
		return $html;
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
				$facetId = $facetField->name;
				//replace period with underscore so side facet accordion functions properly
				$facetIdForSideFacets = str_replace(".", "_", $facetField->name);
				$displayName = $facetField->label;

				$availableFacets[$facetIdForSideFacets] = [
					'collapseByDefault' => true,
					'multiSelect' =>true,
					'label' =>$displayName,
					'valuesToShow' =>5,
				];
				if ($facetId == 'fieldOfStudy') {
					$availableFacets[$facetIdForSideFacets]['collapseByDefault'] = false;
				}

				if ($facetId == 'peerReviewed') {
					$availableFacets[$facetIdForSideFacets]['multiSelect'] = false;
				}

				$list = [];
				foreach ($facetField->navigators as $value) {
					$facetValue = $value->name;
					$facetDisplay = $value->label;
					//Ensures selected facet stays checked when selected - interacts with .tpl
					$isApplied = array_key_exists($facetIdForSideFacets, $this->filterList) && in_array($facetValue, $this->filterList[$facetId]);
					$facetSettings = [
						'value' => $facetValue,
						'display' =>$facetDisplay,
						'count' =>$value->count,
						'isApplied' => $isApplied,
					];
					if ($isApplied) {
						$facetSettings['removalUrl'] = $this->renderLinkWithoutFilter($facetId . ':' . $facetValue);
					} else {
						$facetSettings['url'] = $this->renderSearchUrl() . '&filter[]=' . $facetId . ':' . urlencode($facetValue) . '&page=1';
					}
					$list[] = $facetSettings;
				}
				$availableFacets[$facetIdForSideFacets]['list'] = $list;
			}
		}
		return $availableFacets;
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
	 * Work out how long the query took
	 */
	public function getQuerySpeed() {
		return $this->queryTime;
	}

	/**
	 * Search indexes
	 */
	public function getSearchIndexes() {
		return [
			'' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}
	//Default search index
	public function getDefaultIndex() {
		return $this->searchIndex;
	}

	public function setSearchTerm($searchTerm = null) {
		if (is_array($this->searchTerms) && count($this->searchTerms) > 0) {
			if (strpos($this->searchTerms[0], ':') !== false) {
				[$searchIndex, $term] = explode(':', $this->searchTerms[0], 2);
				$this->setSearchTerms([
					'lookfor' => $term,
					'index' => $searchIndex,
				]);
			} else {
				$this->setSearchTerms([
					'lookfor' => $this->searchTerms[0],
					'index' => $this->getDefaultIndex(),
				]);
			}
		} elseif (!empty($searchTerm)) {
			$this->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => $this->getDefaultIndex(),
			]);
		} else {
				$this->setSearchTerms([
					'lookfor' => '',
					'index' => $this->getDefaultIndex(),
				]);
		}
	}
	function getBrowseRecordHTML() {
		global $interface;
		$html = [];
		global $logger;

		if (isset($this->lastSearchResults)) {
			for ($x = 0; $x < count($this->lastSearchResults); $x++) {
				$current = &$this->lastSearchResults[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
				$record = new CloudSourceRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getBrowseResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}

		return $html;
	}

	public function getSpotlightResults(CollectionSpotlight $spotlight) {
		$spotlightResults = [];
		if (isset($this->lastSearchResults)) {
			for ($x = 0; $x < count($this->lastSearchResults); $x++) {
				$current = &$this->lastSearchResults[$x];
				require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
				$record = new CloudSourceRecordDriver($current);
				if ($record->isValid()) {
					if (!empty($orderedListOfIDs)) {
						$position = array_search($current['id'], $orderedListOfIDs);
						if ($position !== false) {
							$spotlightResults[$position] = $record->getSpotlightResult($spotlight, $position);
						}
					} else {
						$spotlightResults[] = $record->getSpotlightResult($spotlight, $x);
					}
				} else {
					$spotlightResults[] = "Unable to find record";
				}
			}
		}
		return $spotlightResults;
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
		return 'CloudSource';
	}
	public function disableSpelling() {
		//Do nothing for now
	}

	function getSearchesFile() {
		return false;
	}

	public function getSessionId() {
		return $this->sessionId;
	}

	public function getresultsTotal(){
		return $this->resultsTotal;
	}

	public function __destruct() {
		if ($this->curl_connection) {
			curl_close($this->curl_connection);
		}
	}

	public function getRecords($ids) {
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
		foreach ($ids as $index => $id) {
			$records[$index] = new CloudSourceRecordDriver($id);
		}
		return $records;
	}


}