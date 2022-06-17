<?php

require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_EbscohostSearcher extends SearchObject_BaseSearcher {
	static $instance;

	private $ebscohostSearchSettings;
	/** @var EBSCOhostSetting */
	private $ebscohostSettings;
	private $ebscohostBaseUrl = 'https://eit.ebscohost.com/Services/SearchService.asmx';
	private $curl_connection;
	private static $searchOptions;

	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

	// Page number
	protected $page = 1;
	// Result limit
	protected $limit = 20;

	// Sorting
	protected $sort = 'relevance';
	protected $defaultSort = 'relevance';

	// STATS
	protected $resultsTotal = 0;

	protected $searchTerms;

	protected $lastSearchResults;
	/**
	 * @var string mixed
	 */
	private $searchIndex = 'TX';

	public function __construct()
	{
		parent::__construct();
		$this->searchSource = 'ebscohost';
		$this->searchType = 'ebscohost';
		$this->resultsModule = 'EBSCOhost';
		$this->resultsAction = 'Results';

		global $library;
		if ($library->ebscohostSearchSettingId > 0){
			$searchSettings = new EBSCOhostSearchSetting();
			$searchSettings->id = $library->ebscohostSearchSettingId;
			if (!$searchSettings->find(true)){
				$searchSettings = null;
			}
			$this->ebscohostSearchSettings = $searchSettings;
		}
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = null)
	{
		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch();
		if ($restored === true) {
			return true;
		} else if ($restored instanceof AspenError) {
			return false;
		}

		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initFilters();
		$this->initLimiters();
		//Sorting needs to be initialized after filters since they depend on the selected database
		$this->initSort();

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
	 * @return SearchObject_EbscohostSearcher
	 */
	public static function getInstance(){
		if (SearchObject_EbscohostSearcher::$instance == null){
			SearchObject_EbscohostSearcher::$instance = new SearchObject_EbscohostSearcher();
		}
		return SearchObject_EbscohostSearcher::$instance;
	}

	/**
	 * @return EBSCOhostSetting|null
	 */
	private function getSettings() : ?EBSCOhostSetting{
		global $library;
		if ($this->ebscohostSettings == null){
			require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSearchSetting.php';
			$searchSettings = new EBSCOhostSearchSetting();
			$searchSettings->id = $library->ebscohostSearchSettingId;
			if ($searchSettings->find(true)) {
				$this->ebscohostSettings = new EBSCOhostSetting();
				$this->ebscohostSettings->id = $searchSettings->settingId;
				if (!$this->ebscohostSettings->find(true)) {
					$this->ebscohostSettings = null;
				}
			}
		}
		return $this->ebscohostSettings;
	}

	public function setSettings($ebscohostSettings){
		$this->ebscohostSettings = $ebscohostSettings;
	}

	public function getCurlConnection(){
		if ($this->curl_connection == null){
			$this->curl_connection = curl_init();
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
		}
		return $this->curl_connection;
	}

	public function __destruct(){
		if ($this->curl_connection) {
			curl_close($this->curl_connection);
		}
	}

	public function getQuerySpeed() {
		return $this->queryTime;
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
	 * Return an array of data summarising the results of a search.
	 *
	 * @access  public
	 * @return  array   summary of results
	 */
	public function getResultSummary() {
		$summary = array();

		$summary['page']        = $this->page;
		$summary['perPage']     = $this->limit;
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

	public function getNumResults() : int{
		return $this->resultsTotal;
	}

	/**
	 * Return a url for use by pagination template
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
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML()
	{
		global $interface;
		$html = array();
		//global $logger;
		//$logger->log(print_r($this->lastSearchResults, true), Logger::LOG_WARNING);
		if (isset($this->lastSearchResults->SearchResults->records)) {
			for ($x = 0; $x < count($this->lastSearchResults->SearchResults->records->rec); $x++) {
				$current = &$this->lastSearchResults->SearchResults->records->rec[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
				$record = new EbscohostRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}

		//Save to history
		$this->addToHistory();

		return $html;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getCombinedResultHTML()
	{
		global $interface;
		$html = array();
		//global $logger;
		//$logger->log(print_r($this->lastSearchResults, true), Logger::LOG_WARNING);
		if (isset($this->lastSearchResults->SearchResults->records)) {
			for ($x = 0; $x < count($this->lastSearchResults->SearchResults->records->rec); $x++) {
				$current = &$this->lastSearchResults->SearchResults->records->rec[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
				$record = new EbscohostRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getCombinedResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}
		return $html;
	}

	public function getSearchOptions() : ?SimpleXMLElement{
		if (SearchObject_EbscohostSearcher::$searchOptions == null){
			$settings = $this->getSettings();
			if ($settings != null) {
				$curlConnection = $this->getCurlConnection();
				$infoUrl = $this->ebscohostBaseUrl . "/Info?prof={$settings->profileId}&pwd={$settings->profilePwd}";
				curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
				$searchOptionsStr = curl_exec($curlConnection);

				SearchObject_EbscohostSearcher::$searchOptions = simplexml_load_string($searchOptionsStr);
				if (SearchObject_EbscohostSearcher::$searchOptions) {
					return SearchObject_EbscohostSearcher::$searchOptions;
				} else {
					return null;
				}
			}else{
				return null;
			}
		}else{
			return SearchObject_EbscohostSearcher::$searchOptions;
		}
	}

	public function getSearchIndexes(){
		global $memCache;
		$settings = $this->getSettings();
		if ($settings == null){
			return [];
		}else {
			$searchIndexes = $memCache->get('ebscohost_search_indexes_' . $settings->profileId);
			if ($searchIndexes === false) {
				$searchOptions = $this->getSearchOptions();
				$searchIndexes = array();
//				if ($searchOptions != null) {
//					foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields as $searchField) {
//						$searchIndexes[$searchField->FieldCode] = translate(['text'=>$searchField->Label, 'isPublicFacing'=>true, 'inAttribute'=>true]);
//					}
//				}
				global $configArray;
				$memCache->set('ebsco_eds_search_indexes_' . $settings->profileId, $searchIndexes, $configArray['Caching']['ebsco_options']);
			}

			return $searchIndexes;
		}
	}

	public function getSortList() {
		$sortOptions = $this->getSortOptions();
		$list = array();
		if ($sortOptions != null){
			foreach ($sortOptions as $sort => $label){
				$list[$sort] = array(
					'sortUrl' => $this->renderLinkWithSort($sort),
					'desc' => $label,
					'selected' => ($sort == $this->sort)
				);

			}
		}

		return $list;
	}

	public function getSortOptions() {
		$appliedDatabases = $this->getAppliedDatabases();
		$searchOptions = $this->getSearchOptions();
		if ($searchOptions == null){
			return [];
		}
		$sortOptions = array();
		if (empty($appliedDatabases)){
			$isFirstDb = true;
			//Get the sort options that apply to all default databases
			if (!empty($this->ebscohostSearchSettings)){
				$defaultDatabases = $this->ebscohostSearchSettings->getDefaultSearchDatabases();
			}else{
				$defaultDatabases = [];
			}

			foreach ($searchOptions->dbInfo->db as $db){
				$shortName = (string)$db->attributes()['shortName'];
				if (empty($defaultDatabases) || in_array($shortName, $defaultDatabases)) {
					if ($isFirstDb) {
						//For the first DB add all options.
						foreach ($db->sortOptions->sort as $sortOption) {
							$id = (string)$sortOption->attributes()['id'];
							$name = (string)$sortOption->attributes()['name'];
							$sortOptions[$id] = $name;
						}
						$isFirstDb = false;
					} else {
						//For the rest, remove any sort options that are not found.
						$sortOptionsForThisDB = [];
						foreach ($db->sortOptions->sort as $sortOption) {
							$id = (string)$sortOption->attributes()['id'];
							$sortOptionsForThisDB[$id] = $id;
						}
						foreach ($sortOptions as $id => $name) {
							if (!in_array($id, $sortOptionsForThisDB)) {
								unset($sortOptions[$id]);
							}
						}
					}
				}
			}
		}else{
			foreach ($searchOptions->dbInfo->db as $db){
				if (in_array($db->attributes()['shortName'], $appliedDatabases)){
					foreach ($db->sortOptions->sort as $sortOption){
						$id = (string)$sortOption->attributes()['id'];
						$name = (string)$sortOption->attributes()['name'];
						$sortOptions[$id] = $name;
					}
				}
			}
		}

		return $sortOptions;
	}

	/**
	 * Return a url for the current search with a new sort
	 *
	 * @access  public
	 * @param   string   $newSort   A field to sort by
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

	public function getFacetSet() {
		$searchOptions = $this->getSearchOptions();

		$selectedDatabases = [];
		foreach ($this->filterList as $field=>$value){
			if ($field == 'db'){
				if (is_array($value)){
					$selectedDatabases = $value;
				}else{
					$selectedDatabases = [$value];
				}
			}
		}
		$availableFacets = array();
		$availableFacets['db'] = [
			'multiSelect' => true,
			'showAsDropDown' => false,
			'valuesToShow' => 5,
			'showMoreFacetPopup' => true,
			'label' => 'Source',
			'defaultValue' => translate(['text'=>'All Databases', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'hasSelectedOption' => empty($selectedDatabases),
			'displayNamePlural' => translate(['text'=>'Sources', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'list' => []
		];
		if ($searchOptions != null) {
			//Handle databases a little differently
			foreach ($searchOptions->dbInfo->db as $dbInfo) {
				$shortName = (string)$dbInfo->attributes()['shortName'];
				$longName = (string)$dbInfo->attributes()['longName'];
				$isApplied = array_key_exists('db', $this->filterList) && in_array($shortName, $this->filterList['db']);
				$availableFacets['db']['list'][$shortName] = array(
					'type' => 'db',
					'value' => $shortName,
					'display' => $longName,
					'url' => $this->renderLinkWithFilter('db', $shortName),
					'removalUrl' => $this->renderLinkWithoutFilter("db:$shortName"),
					'isApplied' => $isApplied,
				);
			}
			if (!empty($this->lastSearchResults->Statistics)) {
				foreach ($this->lastSearchResults->Statistics->Statistic as $statistic) {
					$availableFacets['db']['list'][(string)$statistic->Database]['count'] = (int)$statistic->Hits;
					if ($statistic->Hits == 0) {
						unset($availableFacets['db']['list'][(string)$statistic->Database]);
					}
				}
			}
			if (!empty($this->ebscohostSearchSettings)){
				foreach ($this->ebscohostSearchSettings->getDatabases() as $database){
					if (!$database->allowSearching){
						unset($availableFacets['db']['list'][$database->shortName]);
					}
				}
			}
			$sorter = function($a, $b) {
				if (isset($a['count']) && isset($b['count'])) {
					$countA = $a['count'];
					$countB = $b['count'];
					if ($countA > $countB) {
						return -1;
					} elseif ($countA < $countB) {
						return 1;
					} else {
						return strcasecmp($a['display'], $b['display']);
					}
				}elseif (isset($a['count'])) {
					return -1;
				}elseif (isset($b['count'])) {
					return 1;
				}else{
					return strcasecmp($a['display'], $b['display']);
				}
			};
			uasort($availableFacets['db']['list'], $sorter);

			$sortedList = array();
			foreach ($availableFacets['db']['list'] as $key => $value) {
				$sortedList[strtolower($value['display'])] = $value;
			}
			ksort($sortedList);
			$availableFacets['db']['sortedList'] = $sortedList;

			$availableFacets['db']['list'] = array_slice($availableFacets['db']['list'], 0, 6);

			if (!empty($this->lastSearchResults->Facets)) {
				if (!empty($this->lastSearchResults->Facets->Clusters)) {
					foreach ($this->lastSearchResults->Facets->Clusters->ClusterCategory as $facetCluster) {
						$id = (string)$facetCluster->attributes()['ID'];
						$tag = (string)$facetCluster->attributes()['Tag'];
						$availableFacets[$tag] = [
							'multiSelect' => false,
							'valuesToShow' => 5,
							'collapseByDefault' => false,
							'label' => $id,
							'list' => []
						];
						foreach ($facetCluster->Cluster as $clusterData){
							$facetValue = (string)$clusterData;
							$isApplied = array_key_exists($tag, $this->filterList) && in_array($facetValue, $this->filterList[$tag]);
							$availableFacets[$tag]['list'][$facetValue] = array(
								'type' => $tag,
								'value' => $facetValue,
								'display' => $facetValue,
								'url' => $this->renderLinkWithFilter($tag, $facetValue),
								'removalUrl' => $this->renderLinkWithoutFilter("$tag:$facetValue"),
								'isApplied' => $isApplied,
							);
						}
					}
				}
			}
		}

		return $availableFacets;
	}

	public function getLimitList(){
		return [];
	}

	public function retrieveRecord($dbId, $an) {
		$settings = $this->getSettings();
		if ($settings != null) {
			$curlConnection = $this->getCurlConnection();
			curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
			$infoUrl = $this->ebscohostBaseUrl . "/Search?prof={$settings->profileId}&pwd={$settings->profilePwd}&format=full";
			$infoUrl .= "&query=$an&db=$dbId";
			curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
			$recordInfoStr = curl_exec($curlConnection);
			if ($recordInfoStr == false) {
				return null;
			} else {
				$recordData = simplexml_load_string($recordInfoStr);
				if ($recordData->Hits > 0) {
					return reset($recordData->SearchResults->records);
				} else {
					return null;
				}
			}
		}else{
			return null;
		}
	}

	public function getEngineName()
	{
		return 'Ebscohost';
	}

	function getSearchesFile()
	{
		// EBSCOhost does not have a searches file, we load dynamically
		return false;
	}

	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
	{
		$settings = $this->getSettings();
		if ($settings == null){
			return new AspenError("EBSCOhost searching is not configured for this library.");
		}else {
			$this->startQueryTimer();
			$hasSearchTerm = false;
			$searchUrl = $this->ebscohostBaseUrl . "/Search?prof={$this->getSettings()->profileId}&pwd={$this->getSettings()->profilePwd}&format=detailed";
			if (is_array($this->searchTerms)) {
				$searchUrl .= '&query=';
				$termIndex = 1;
				foreach ($this->searchTerms as $term) {
					if (!empty($term)) {
						if ($termIndex > 1) $searchUrl .= ' AND ';
						$searchUrl .= urlencode($term['lookfor']);
						$termIndex++;
						$hasSearchTerm = true;
					}
				}
			} else {
				if (isset($_REQUEST['searchIndex'])) {
					$this->searchIndex = $_REQUEST['searchIndex'];
				}
				$searchUrl .= '&query=' . urlencode($this->searchTerms);
			}
			foreach ($this->filterList as $field => $filter) {
				if ($field != 'db') {
					if (is_array($filter)){
						foreach ($filter as $filterValue){
							$searchUrl .= "%20AND%20$field%20" . urlencode($filterValue);
						}
					}else{
						$searchUrl .= "%20AND%20$field%20" . urlencode($filter);
					}
				}
			}
			if (!$hasSearchTerm) {
				return new AspenError('Please specify a search term');
			}

			if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1) {
				$this->page = $_REQUEST['page'];
				$searchUrl .= '&startrec=' . (($this->page - 1) * $this->limit + 1);
			} else {
				$this->page = 1;
			}
			$searchUrl .= '&numrec=' . $this->limit;

			$hasAppliedDatabase = false;
			foreach ($this->filterList as $field => $filter) {
				if ($field == 'db') {
					$hasAppliedDatabase = true;
					if (is_array($filter)) {
						foreach ($filter as $fieldIndex => $fieldValue) {
							$searchUrl .= "&$field=" . urlencode($fieldValue);
						}
					} else {
						$searchUrl .= "&$field=" . urlencode($filter);
					}
				}
			}

			if (!$hasAppliedDatabase) {
				//Apply all databases to the search (by default)
				if ($this->ebscohostSearchSettings != null){
					$defaultSearchDatabases = $this->ebscohostSearchSettings->getDefaultSearchDatabases();
					foreach ($defaultSearchDatabases as $defaultDB) {
						$searchUrl .= '&db=' . $defaultDB;
					}
				}else {
					$searchOptions = $this->getSearchOptions();
					/** @var SimpleXMLElement $dbInfo */
					foreach ($searchOptions->dbInfo->db as $dbInfo) {
						$searchUrl .= '&db=' . $dbInfo->attributes()['shortName'];
					}
				}
			}

			$searchUrl .= '&sort=' . $this->sort;
			$searchUrl .= '&clusters=true';

			$curlConnection = $this->getCurlConnection();
			curl_setopt($curlConnection, CURLOPT_HTTPGET, true);

			curl_setopt($curlConnection, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Accept: application/json',
			));
			curl_setopt($curlConnection, CURLOPT_URL, $searchUrl);
			$result = curl_exec($curlConnection);
			try {
				$searchData = simplexml_load_string($result);
				if ($searchData->Message) {
					return new AspenError("Error processing search in EBSCOhost: " . (string)$searchData->Message);
				}
				$this->stopQueryTimer();
				if ($searchData && empty($searchData->ErrorNumber)) {
					$this->resultsTotal = (int)$searchData->Hits;
					$this->lastSearchResults = $searchData;

					return $searchData->SearchResults->records;
				} else {
					global $logger;
					if (IPAddress::showDebuggingInformation()) {
						$curlInfo = curl_getinfo($curlConnection);
						$logger->log(print_r($curlInfo(true)), Logger::LOG_WARNING);
					}
					$this->lastSearchResults = false;

				}
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error loading data from EBSCO $e", Logger::LOG_ERROR);
				return new AspenError("Error loading data from EBSCO $e");
			}
		}
	}

	public function getDatabases(){
		$databases = [];
		$searchOptions = $this->getSearchOptions();
		/** @var SimpleXMLElement $dbInfo */
		foreach ($searchOptions->dbInfo->db as $dbInfo){
			$shortName = (string)$dbInfo->attributes()['shortName'];
			$databases[$shortName] = [];
			foreach ($dbInfo->attributes() as $attributeName => $attributeValue){
				$databases[$shortName][(string)$attributeName] = (string)$attributeValue;
			}
			$hasRelevancySort = false;
			$hasDateSort = false;
			foreach ($dbInfo->sortOptions->sort as $sortOptions){
				if ($sortOptions->attributes()['id'] == 'relevance'){
					$hasRelevancySort = true;
				}elseif ($sortOptions->attributes()['id'] == 'date'){
					$hasDateSort = true;
				}
			}
			$databases[$shortName]['hasRelevancySort'] = $hasRelevancySort;
			$databases[$shortName]['hasDateSort'] = $hasDateSort;
		}
		return $databases;
	}

	public function getIndexError()
	{
		// TODO: Implement getIndexError() method.
	}

	public function buildRSS($result = null)
	{
		// TODO: Implement buildRSS() method.
	}

	public function buildExcel($result = null)
	{
		// TODO: Implement buildExcel() method.
	}

	public function getResultRecordSet()
	{
		// TODO: Implement getResultRecordSet() method.
	}

	function getSearchName()
	{
		return $this->searchSource;
	}

	function loadValidFields()
	{
		// TODO: Implement loadValidFields() method.
	}

	function loadDynamicFields()
	{
		// TODO: Implement loadDynamicFields() method.
	}

	public function getRecordDriverForResult($current) : EbscohostRecordDriver
	{
		require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
		return new EbscohostRecordDriver($current);
	}

	function getBrowseRecordHTML(){
		global $interface;
		$html = array();
		//global $logger;
		//$logger->log(print_r($this->lastSearchResults, true), Logger::LOG_WARNING);
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$record = new EbscoRecordDriver($current);
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

	public function getSpotlightResults(CollectionSpotlight $spotlight){
		$spotlightResults = [];
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$record = new EbscoRecordDriver($current);
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

	public function setSearchTerm($searchTerm)
	{
		if (strpos($searchTerm, ':') !== false){
			list($searchIndex, $term) = explode(':', $searchTerm, 2);
			$this->setSearchTerms([
				'lookfor' =>$term,
				'index' => $searchIndex
			]);
		}else {
			$this->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => $this->getDefaultIndex()
			]);
		}
	}

	public function disableSpelling(){
		//Do nothing for now
	}

	public function getDefaultIndex()
	{
		return 'TX';
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resources
	 */
	public function getRecords($ids){
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
		foreach ($ids as $index => $id){
			$records[$index] = new EbscoRecordDriver($id);
		}
		return $records;
	}

	public function getAppliedDatabases(){
		$appliedDatabase = null;
		if (isset($this->filterList['db'])) {
			$filter = $this->filterList['db'];
			if (is_array($filter)) {
				$appliedDatabase = $filter;
			}else{
				$appliedDatabase = [$filter];
			}
		}
		return $appliedDatabase;
	}
}