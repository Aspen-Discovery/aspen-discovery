<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/14/2016
 * Time: 5:42 PM
 */
require_once ROOT_DIR . '/sys/Pager.php';

class EDS_API {
	static $instance;

	private $edsBaseApi = 'https://eds-api.ebscohost.com/edsapi/rest';
	private $curl_connection;
	private $sessionId;
	private $authenticationToken;

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

	protected $searchTerm;

	protected $lastSearchResults;

	/**
	 * @return EDS_API
	 */
	public static function getInstance(){
		if (EDS_API::$instance == null){
			EDS_API::$instance = new EDS_API();
		}
		return EDS_API::$instance;
	}

	public function authenticate(){
		/*if (isset($this->sessionId)){
			return true;
		}*/
		global $library;
		if ($library->edsApiProfile){
			$this->curl_connection = curl_init("https://eds-api.ebscohost.com/authservice/rest/uidauth");
			$params =<<<BODY
<UIDAuthRequestMessage xmlns="http://www.ebscohost.com/services/public/AuthService/Response/2012/06/01" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
    <UserId>{$library->edsApiUsername}</UserId>
    <Password>{$library->edsApiPassword}</Password>
    <InterfaceId>{$library->edsApiProfile}</InterfaceId>
</UIDAuthRequestMessage>
BODY;
			$headers = array(
				'Content-Type: application/xml',
				'Content-Length: ' . strlen($params)
			);

			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($this->curl_connection, CURLOPT_POST, true);
			curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $params);

			$return = curl_exec($this->curl_connection);
			$authenticationResponse = new SimpleXMLElement($return);
			if ($authenticationResponse && isset($authenticationResponse->AuthToken)){
				$this->authenticationToken = (string)$authenticationResponse->AuthToken;

				curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, $headers);

				$params =<<<BODY
<CreateSessionRequestMessage xmlns="http://epnet.com/webservices/EbscoApi/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
  <Profile>{$library->edsApiProfile}</Profile>
  <Guest>n</Guest>
  <Org>{$library->displayName}</Org>
</CreateSessionRequestMessage>
BODY;
;
				$headers = array(
						'Content-Type: application/xml',
						'Content-Length: ' . strlen($params),
						'x-authenticationToken: ' . $this->authenticationToken
				);
				curl_setopt($this->curl_connection, CURLOPT_POST, true);
				curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($this->curl_connection, CURLOPT_URL, $this->edsBaseApi . '/createsession');
				curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $params);
				$result = curl_exec($this->curl_connection);
				if ($result == false){
					echo("Error getting session token");
					echo(curl_error($this->curl_connection));
				}else {
					$createSessionResponse = new SimpleXMLElement($result);
					if ($createSessionResponse->SessionToken) {
						$this->sessionId = (string)$createSessionResponse->SessionToken;
						//echo("Authenticated in EDS!");
						return true;
					} elseif ($createSessionResponse->ErrorDescription) {
						echo("create session failed, " . print_r($createSessionResponse));
						return false;
					}
				}
			}else{
				echo("Authentication failed!, $return");
				return false;
			}
		}else{
			return false;
		}
	}

	public function getSearchResults($searchTerms, $sort = null, $filters = array()){
		if (!$this->authenticate()){
			return null;
		}

		$this->startQueryTimer();
		$this->searchTerm = $searchTerms;
		if (is_array($searchTerms)){
			$searchUrl = $this->edsBaseApi . '/search?';
			$termIndex = 1;
			foreach ($searchTerms as $term){
				if ($termIndex > 1) $searchUrl .= '&';
				$term = str_replace(',', '', $term);
				$searchUrl .= "query-{$termIndex}=OR," . urlencode($term);
				$termIndex++;
			}
		}else{
			$searchTerms = str_replace(',', '', $searchTerms);
			$searchUrl = $this->edsBaseApi . '/search?query-1=AND,' . urlencode($searchTerms);
		}

		if (isset($sort)) {
			$this->sort = $sort;
		}else {
			$this->sort = $this->defaultSort;
		}
		$searchUrl .= '&sort=' . $this->sort;

		$facetIndex = 1;
		foreach ($filters as $filter) {
			$searchUrl .= "&facetfilter=$facetIndex," . urlencode($filter);
			$facetIndex++;
		}

		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
			'x-authenticationToken: ' . $this->authenticationToken,
			'x-sessionToken: ' . $this->sessionId,
		));
		curl_setopt($this->curl_connection, CURLOPT_URL, $searchUrl);
		$result = curl_exec($this->curl_connection);
		try {
			$searchData = new SimpleXMLElement($result);
			$this->stopQueryTimer();
			if ($searchData && !$searchData->ErrorNumber){
				$this->resultsTotal = $searchData->SearchResult->Statistics->TotalHits;
				$this->lastSearchResults = $searchData->SearchResult;
				return $searchData->SearchResult;
			}else{
				$curlInfo = curl_getinfo($this->curl_connection);
				$this->lastSearchResults = null;
				return null;
			}
		}catch (Exception $e){
			global $logger;
			$logger->log("Error loading data from EBSCO $e", PEAR_LOG_ERR);
		}
	}

	public function endSession(){
		curl_setopt($this->curl_connection, CURLOPT_URL, $this->edsBaseApi . '/endsession?sessiontoken=' . $this->sessionId);
		$result = curl_exec($this->curl_connection);
	}

	public function __destruct(){
		$this->endSession();
		curl_close($this->curl_connection);
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
	 * Build a url for the current search
	 *
	 * @access  public
	 * @return  string   URL of a search
	 */
	public function renderSearchUrl() {
		$searchUrl = '/EBSCO/Results?lookfor=' . $this->searchTerm;
		if ($this->page != 1){
			$searchUrl .= '&page=' . $this->page;
		}
		if ($this->sort){
			$searchUrl .= '&sort=' . $this->sort;
		}
		return $searchUrl;
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
		//$logger->log(print_r($this->lastSearchResults, true), PEAR_LOG_WARNING);
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records->Record); $x++) {
				$current = &$this->lastSearchResults->Data->Records->Record[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$record = new EbscoRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}
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
		//$logger->log(print_r($this->lastSearchResults, true), PEAR_LOG_WARNING);
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records->Record); $x++) {
				$current = &$this->lastSearchResults->Data->Records->Record[$x];
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

	public function getSearchOptions() {
		if (!$this->authenticate()){
			return null;
		}
		/** @var Memcache $memCache */
		global $memCache;
		global $library;
		global $configArray;
		$searchOptionsStr = $memCache->get('ebsco_search_options_' . $library->subdomain);
		if ($searchOptionsStr == false || isset ($_REQUEST['reload'])){
			curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
			curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
					'x-authenticationToken: ' . $this->authenticationToken,
					'x-sessionToken: ' . $this->sessionId,
			));
			$infoUrl = $this->edsBaseApi . '/info';
			curl_setopt($this->curl_connection, CURLOPT_URL, $infoUrl);
			$searchOptionsStr = curl_exec($this->curl_connection);
			$memCache->set('ebsco_search_options_' . $library->subdomain, $searchOptionsStr, 0, $configArray['Caching']['ebsco_options']);
		}

		$infoData = new SimpleXMLElement($searchOptionsStr);
		if ($infoData){
			return $infoData;
		}else{
			return null;
		}

	}

	public function getSearchTypes(){
		$searchOptions = $this->getSearchOptions();
		$searchTypes = array();
		if ($searchOptions != null){
			foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields->AvailableSearchField as $searchField){
				$searchTypes[(string)$searchField->FieldCode] = (string)$searchField->Label;
			}
		}
		return $searchTypes;
	}

	public function getSortList() {
		$searchOptions = $this->getSearchOptions();
		$list = array();
		if ($searchOptions != null){
			foreach ($searchOptions->AvailableSearchCriteria->AvailableSorts->AvailableSort as $sortOption){
				$sort = (string)$sortOption->Id;
				$desc = (string)$sortOption->Label;
				$list[$sort] = array(
						'sortUrl' => $this->renderLinkWithSort($sort),
						'desc' => $desc,
						'selected' => ($sort == $this->sort)
				);

			}
		}

		return $list;
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

	public function getAppliedFilters() {
		$appliedFilters = array();
		return $appliedFilters;
	}

	public function getFacetSet() {
		$availableFacets = array();
		if (isset($this->lastSearchResults) && isset($this->lastSearchResults->AvailableFacets)){
			foreach ($this->lastSearchResults->AvailableFacets->AvailableFacet as $facet){
				$facetId = (string)$facet->Id;
				$availableFacets[$facetId] = array(
						'collapseByDefault' => true,
						'label' => (string)$facet->Label,
						'valuesToShow' => 5,
				);
				$list = array();
				foreach ($facet->AvailableFacetValues->AvailableFacetValue as $value){
					$facetValue = (string)$value->Value;
					$urlWithFacet = $this->renderSearchUrl() . '&filter[]=' . $facetId . ':' . urlencode($facetValue);
					$list[] = array(
							'display' => $facetValue,
							'count' => (string)$value->Count,
							'url' => $urlWithFacet
					);
				}
				$availableFacets[$facetId]['list'] = $list;
			}
		}
		return $availableFacets;
	}

	public function retrieveRecord($dbId, $an) {
		if (!$this->authenticate()){
			return null;
		}else{
			curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
			curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
					'x-authenticationToken: ' . $this->authenticationToken,
					'x-sessionToken: ' . $this->sessionId,
			));
			$infoUrl = $this->edsBaseApi . "/Retrieve?an=$an&dbid=$dbId";
			curl_setopt($this->curl_connection, CURLOPT_URL, $infoUrl);
			$recordInfoStr = curl_exec($this->curl_connection);
			if ($recordInfoStr == false){
				return null;
			}else{
				$recordData = new SimpleXMLElement($recordInfoStr);
				return $recordData->Record;
			}
		}
	}

	public function displayQuery(){
		return $this->searchTerm;
	}
}