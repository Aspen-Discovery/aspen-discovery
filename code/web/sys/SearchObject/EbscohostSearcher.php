<?php

require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_EbscohostSearcher extends SearchObject_BaseSearcher {
	static $instance;

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
	protected $sort = null;
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
			$this->ebscohostSettings = new EBSCOhostSetting();
			$this->ebscohostSettings->id = $library->ebscohostSettingId;
			if (!$this->ebscohostSettings->find(true)){
				$this->ebscohostSettings = null;
			}
		}
		return $this->ebscohostSettings;
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
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$record = new EbscoRecordDriver($current);
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
			$curlConnection = $this->getCurlConnection();
			$infoUrl = $this->ebscohostBaseUrl . "/Info?prof={$this->ebscohostSettings->profileId}&pwd={$this->ebscohostSettings->profilePwd}";
			curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
			$searchOptionsStr = curl_exec($curlConnection);

			SearchObject_EbscohostSearcher::$searchOptions = simplexml_load_string($searchOptionsStr);
			if (SearchObject_EbscohostSearcher::$searchOptions){
				return SearchObject_EbscohostSearcher::$searchOptions;
			}else{
				return null;
			}
		}else{
			return SearchObject_EbscohostSearcher::$searchOptions;
		}
	}

	public function getSearchIndexes(){
		global $memCache;

		if ($this->getSettings() == null){
			return [];
		}else {
			$searchIndexes = $memCache->get('ebscohost_search_indexes_' . $this->getSettings()->profileId);
			if ($searchIndexes === false) {
				$searchOptions = $this->getSearchOptions();
				$searchIndexes = array();
//				if ($searchOptions != null) {
//					foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields as $searchField) {
//						$searchIndexes[$searchField->FieldCode] = translate(['text'=>$searchField->Label, 'isPublicFacing'=>true, 'inAttribute'=>true]);
//					}
//				}
				global $configArray;
				$memCache->set('ebsco_eds_search_indexes_' . $this->getSettings()->profileId, $searchIndexes, $configArray['Caching']['ebsco_options']);
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
		global $memCache;
		$sortOptions = $memCache->get('ebscohost_sort_options_' . $this->getSettings()->profileId);
		if ($sortOptions === false) {
			$searchOptions = $this->getSearchOptions();
			$sortOptions = array();
//			if ($searchOptions != null) {
//				foreach ($searchOptions->AvailableSearchCriteria->AvailableSorts as $sortOption) {
//					$sort = $sortOption->Id;
//					$desc = $sortOption->Label;
//					$sortOptions[$sort] = $desc;
//				}
//			}
			global $configArray;
			$memCache->set('ebsco_eds_sort_options_' . $this->getSettings()->profileId, $sortOptions, $configArray['Caching']['ebsco_options']);
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
		return [];
	}

	public function getLimitList(){
		global $memCache;
		$limitOptions = $memCache->get('ebscohost_limit_options_' . $this->getSettings()->profileId);
		if ($limitOptions === false) {
			$searchOptions = $this->getSearchOptions();

			$limitOptions = array();
			if ($searchOptions != null) {
				//The only facet/limiter currently available is the database
				foreach ($searchOptions->dbInfo->db as $dbInfo) {
					$shortName = (string)$dbInfo->attributes()['shortName'];
					$longName = (string)$dbInfo->attributes()['longName'];
					$limitOptions[$shortName] = array(
						'type' => 'db',
						'value' => $shortName,
						'display' => $longName,
						'defaultOn' => false
					);
				}
			}
		}
		$limitList = [];
		foreach ($limitOptions as $limit => $limitOption){
			if (array_key_exists($limit, $this->limiters)){
				$limitIsApplied = ($this->limiters[$limit]) == 'y' ? 1 : 0;
			}else{
				$limitIsApplied = $limitOption['defaultOn'];
			}
			$limitList[$limit] = [
				'url' => $this->renderLinkWithLimiter($limit),
				'removalUrl' => $this->renderLinkWithoutLimiter($limit),
				'display' => $limitOption['display'],
				'value' => $limit,
				'isApplied' => $limitIsApplied,
			];
		}

		return $limitList;
	}

	public function retrieveRecord($dbId, $an) {
		$curlConnection = $this->getCurlConnection();
		curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
		curl_setopt($curlConnection, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-authenticationToken: ' . SearchObject_EbscohostSearcher::$authenticationToken,
			'x-sessionToken: ' . SearchObject_EbscohostSearcher::$sessionId,
		));
		$infoUrl = $this->ebscohostBaseUrl . "/Retrieve?an=$an&dbid=$dbId";
		curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
		$recordInfoStr = curl_exec($curlConnection);
		if ($recordInfoStr == false){
			return null;
		}else{
			$recordData = json_decode($recordInfoStr);
			if (isset($recordData->Record)) {
				return $recordData->Record;
			}else{
				return null;
			}
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
		$this->startQueryTimer();
		$hasSearchTerm = false;
		$searchUrl = $this->ebscohostBaseUrl . "/Search?prof={$this->ebscohostSettings->profileId}&pwd={$this->ebscohostSettings->profilePwd}&format=brief";
		if (is_array($this->searchTerms)){
			$searchUrl .= '&query=';
			$termIndex = 1;
			foreach ($this->searchTerms as $term){
				if (!empty($term)) {
					if ($termIndex > 1) $searchUrl .= ' AND ';
					$searchUrl .= urlencode($term['lookfor']);
					$termIndex++;
					$hasSearchTerm = true;
				}
			}
		}else{
			if (isset($_REQUEST['searchIndex'])) {
				$this->searchIndex = $_REQUEST['searchIndex'];
			}
			$searchUrl .= '&query=' . urlencode($this->searchTerms);
		}
		if (!$hasSearchTerm){
			return new AspenError('Please specify a search term');
		}

		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1){
			$this->page = $_REQUEST['page'];
			$searchUrl .= '&startrec=' . (($this->page -1) * $this->limit + 1);
		}else{
			$this->page = 1;
		}
		$searchUrl .= '&numrec=' . $this->limit;

		$limitList = $this->getLimitList();
		$hasAppliedLimiters = false;
		foreach ($limitList as $limiter => $limiterOptions) {
			if ($limiterOptions['isApplied']){
				$searchUrl .= "&db=$limiter";
				$hasAppliedLimiters = true;
			}
		}
		if (!$hasAppliedLimiters){
			//Apply all databases to the search (by default)
			$searchOptions = $this->getSearchOptions();
			/** @var SimpleXMLElement $dbInfo */
			foreach ($searchOptions->dbInfo->db as $dbInfo){
				$searchUrl .= '&db=' . $dbInfo->attributes()['shortName'];
			}
		}

		$searchUrl .= '&sort=' . $this->sort;

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
			if ($searchData->Message){
				return new AspenError("Error processing search in EBSCOhost: ". (string)$searchData->Message );
			}
			$this->stopQueryTimer();
			if ($searchData && empty($searchData->ErrorNumber)){
				$this->resultsTotal = (int)$searchData->Hits;
				$this->lastSearchResults = $searchData;

				return $searchData->SearchResults->records;
			}else{
				global $logger;
				if (IPAddress::showDebuggingInformation()) {
					$curlInfo = curl_getinfo($curlConnection);
					$logger->log(print_r($curlInfo(true)), Logger::LOG_WARNING);
				}
				$this->lastSearchResults = false;

			}
		}catch (Exception $e){
			global $logger;
			$logger->log("Error loading data from EBSCO $e", Logger::LOG_ERROR);
			return new AspenError("Error loading data from EBSCO $e");
		}
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

}