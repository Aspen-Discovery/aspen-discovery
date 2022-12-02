<?php

require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Ebsco/EDSSettings.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_EbscoEdsSearcher extends SearchObject_BaseSearcher {
	static $instance;

	/** @var EDSSettings */
	private $edsSettings;
	private $edsBaseApi = 'https://eds-api.ebscohost.com/edsapi/rest';
	private $curl_connection;
	private static $sessionId;
	private static $authenticationToken;
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

	public function __construct() {
		parent::__construct();
		$this->searchSource = 'ebsco_eds';
		$this->searchType = 'ebsco_eds';
		$this->resultsModule = 'EBSCO';
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
	public function init($searchSource = null) {
		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch();
		if ($restored === true) {
			return true;
		} elseif ($restored instanceof AspenError) {
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
	 * @return SearchObject_EbscoEdsSearcher
	 */
	public static function getInstance() {
		if (SearchObject_EbscoEdsSearcher::$instance == null) {
			SearchObject_EbscoEdsSearcher::$instance = new SearchObject_EbscoEdsSearcher();
		}
		return SearchObject_EbscoEdsSearcher::$instance;
	}

	private function getSettings() {
		global $library;
		if ($this->edsSettings == null) {
			$this->edsSettings = new EDSSettings();
			$this->edsSettings->id = $library->edsSettingsId;
			if (!$this->edsSettings->find(true)) {
				$this->edsSettings = null;
			}
		}
		return $this->edsSettings;
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

	public function authenticate() {
		if (SearchObject_EbscoEdsSearcher::$authenticationToken == null) {
			global $library;
			$settings = $this->getSettings();
			if ($settings != null && $settings->edsApiProfile) {
				$curlConnection = $this->getCurlConnection();
				curl_setopt($curlConnection, CURLOPT_URL, "https://eds-api.ebscohost.com/authservice/rest/uidauth");
				$isGuest = UserAccount::isLoggedIn() ? 'N' : 'Y';
				/** @noinspection XmlUnusedNamespaceDeclaration */
				$params = "<UIDAuthRequestMessage xmlns=\"http://www.ebscohost.com/services/public/AuthService/Response/2012/06/01\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\">
				    <UserId>{$settings->edsApiUsername}</UserId>
				    <Password>{$settings->edsApiPassword}</Password>
				    <InterfaceId>{$settings->edsApiProfile}</InterfaceId>
				    <Guest>$isGuest</Guest>
				</UIDAuthRequestMessage>";
				$headers = [
					'Content-Type: application/xml',
					'Content-Length: ' . strlen($params),
				];

				curl_setopt($curlConnection, CURLOPT_POST, true);
				curl_setopt($curlConnection, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curlConnection, CURLOPT_POSTFIELDS, $params);

				$return = curl_exec($curlConnection);
				$authenticationResponse = new SimpleXMLElement($return);
				if ($authenticationResponse && isset($authenticationResponse->AuthToken)) {
					SearchObject_EbscoEdsSearcher::$authenticationToken = (string)$authenticationResponse->AuthToken;

					/** @noinspection XmlUnusedNamespaceDeclaration */
					$params = <<<BODY
<CreateSessionRequestMessage xmlns="http://epnet.com/webservices/EbscoApi/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
  <Profile>{$settings->edsApiProfile}</Profile>
  <Guest>$isGuest</Guest>
  <Org>{$library->displayName}</Org>
</CreateSessionRequestMessage>
BODY;

					$headers = [
						'Content-Type: application/xml',
						'Content-Length: ' . strlen($params),
						'x-authenticationToken: ' . SearchObject_EbscoEdsSearcher::$authenticationToken,
					];
					curl_setopt($curlConnection, CURLOPT_POST, true);
					curl_setopt($curlConnection, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($curlConnection, CURLOPT_URL, $this->edsBaseApi . '/createsession');
					curl_setopt($curlConnection, CURLOPT_POSTFIELDS, $params);
					$result = curl_exec($curlConnection);
					if ($result == false) {
						echo("Error getting session token");
						echo(curl_error($curlConnection));
						return false;
					} else {
						/** @var stdClass $createSessionResponse */
						$createSessionResponse = new SimpleXMLElement($result);
						if ($createSessionResponse->SessionToken) {
							SearchObject_EbscoEdsSearcher::$sessionId = (string)$createSessionResponse->SessionToken;
							//echo("Authenticated in EDS!");
							return true;
						} elseif ($createSessionResponse->ErrorDescription) {
							echo("create session failed, " . print_r($createSessionResponse));
							return false;
						}
					}
				} else {
					echo("Authentication failed!, $return");
					return false;
				}
			} else {
				return false;
			}
			return false;
		} else {
			return true;
		}
	}

	public function endSession() {
		if ($this->curl_connection) {
			curl_setopt($this->curl_connection, CURLOPT_URL, $this->edsBaseApi . '/endsession?sessiontoken=' . SearchObject_EbscoEdsSearcher::$sessionId);
			curl_exec($this->curl_connection);
		}
	}

	public function __destruct() {
		$this->endSession();
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
	protected function startQueryTimer() {
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
	protected function stopQueryTimer() {
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
	public function getResultRecordHTML() {
		global $interface;
		$html = [];
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
	public function getCombinedResultHTML() {
		global $interface;
		$html = [];
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

	public function getSearchOptions() {
		if (SearchObject_EbscoEdsSearcher::$searchOptions == null) {
			if (!$this->authenticate()) {
				return null;
			}

			$curlConnection = $this->getCurlConnection();
			curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
			curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Accept: application/json',
				'x-authenticationToken: ' . SearchObject_EbscoEdsSearcher::$authenticationToken,
				'x-sessionToken: ' . SearchObject_EbscoEdsSearcher::$sessionId,
			]);
			$infoUrl = $this->edsBaseApi . '/info';
			curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
			$searchOptionsStr = curl_exec($curlConnection);

			SearchObject_EbscoEdsSearcher::$searchOptions = json_decode($searchOptionsStr);
			if (SearchObject_EbscoEdsSearcher::$searchOptions) {
				return SearchObject_EbscoEdsSearcher::$searchOptions;
			} else {
				return null;
			}
		} else {
			return SearchObject_EbscoEdsSearcher::$searchOptions;
		}
	}

	public function getSearchIndexes() {
		global $memCache;

		if ($this->getSettings() == null) {
			return [];
		} else {
			$searchIndexes = $memCache->get('ebsco_eds_search_indexes_' . $this->getSettings()->edsApiProfile);
			if ($searchIndexes === false) {
				$searchOptions = $this->getSearchOptions();
				$searchIndexes = [];
				if ($searchOptions != null) {
					foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields as $searchField) {
						$searchIndexes[$searchField->FieldCode] = translate([
							'text' => $searchField->Label,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]);
					}
				}
				global $configArray;
				$memCache->set('ebsco_eds_search_indexes_' . $this->getSettings()->edsApiProfile, $searchIndexes, $configArray['Caching']['ebsco_options']);
			}

			return $searchIndexes;
		}
	}

	public function getSortList() {
		$sortOptions = $this->getSortOptions();
		$list = [];
		if ($sortOptions != null) {
			foreach ($sortOptions as $sort => $label) {
				$list[$sort] = [
					'sortUrl' => $this->renderLinkWithSort($sort),
					'desc' => $label,
					'selected' => ($sort == $this->sort),
				];

			}
		}

		return $list;
	}

	public function getSortOptions() {
		global $memCache;
		$sortOptions = $memCache->get('ebsco_eds_sort_options_' . $this->getSettings()->edsApiProfile);
		if ($sortOptions === false) {
			$searchOptions = $this->getSearchOptions();
			$sortOptions = [];
			if ($searchOptions != null) {
				foreach ($searchOptions->AvailableSearchCriteria->AvailableSorts as $sortOption) {
					$sort = $sortOption->Id;
					$desc = $sortOption->Label;
					$sortOptions[$sort] = $desc;
				}
			}
			global $configArray;
			$memCache->set('ebsco_eds_sort_options_' . $this->getSettings()->edsApiProfile, $sortOptions, $configArray['Caching']['ebsco_options']);
		}

		return $sortOptions;
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

	public function getFacetSet() {
		$availableFacets = [];
		if (isset($this->lastSearchResults) && isset($this->lastSearchResults->AvailableFacets)) {
			foreach ($this->lastSearchResults->AvailableFacets as $facet) {
				$facetId = (string)$facet->Id;
				$availableFacets[$facetId] = [
					'collapseByDefault' => true,
					'multiSelect' => true,
					'label' => (string)$facet->Label,
					'valuesToShow' => 5,
				];
				if ($facetId == 'SourceType') {
					$availableFacets[$facetId]['collapseByDefault'] = false;
				}
				$list = [];
				foreach ($facet->AvailableFacetValues as $value) {
					$facetValue = (string)$value->Value;
					//Check to see if the facet has been applied
					$isApplied = array_key_exists($facetId, $this->filterList) && in_array($facetValue, $this->filterList[$facetId]);

					$facetSettings = [
						'value' => $facetValue,
						'display' => $facetValue,
						'count' => (string)$value->Count,
						'isApplied' => $isApplied,
						'countIsApproximate' => false,
					];
					if ($isApplied) {
						$facetSettings['removalUrl'] = $this->renderLinkWithoutFilter($facetId . ':' . $facetValue);
					} else {
						$facetSettings['url'] = $this->renderSearchUrl() . '&filter[]=' . $facetId . ':' . urlencode($facetValue);
					}
					$list[] = $facetSettings;
				}
				$availableFacets[$facetId]['list'] = $list;
			}
		}
		return $availableFacets;
	}

	public function retrieveRecord($dbId, $an) {
		if (!$this->authenticate()) {
			return null;
		} else {
			$curlConnection = $this->getCurlConnection();
			curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
			curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Accept: application/json',
				'x-authenticationToken: ' . SearchObject_EbscoEdsSearcher::$authenticationToken,
				'x-sessionToken: ' . SearchObject_EbscoEdsSearcher::$sessionId,
			]);
			$infoUrl = $this->edsBaseApi . "/Retrieve?an=$an&dbid=$dbId";
			curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
			$recordInfoStr = curl_exec($curlConnection);
			if ($recordInfoStr == false) {
				return null;
			} else {
				$recordData = json_decode($recordInfoStr);
				if (isset($recordData->Record)) {
					return $recordData->Record;
				} else {
					return null;
				}
			}
		}
	}

	public function getEngineName() {
		return 'EbscoEds';
	}

	function getSearchesFile() {
		// EBSCO does not have a searches file, we load dynamically
		return false;
	}

	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) {
		if (!$this->authenticate()) {
			return null;
		}

		$this->startQueryTimer();
		$hasSearchTerm = false;
		if (is_array($this->searchTerms)) {
			$searchUrl = $this->edsBaseApi . '/search?';
			$termIndex = 1;
			foreach ($this->searchTerms as $term) {
				if (!empty($term)) {
					if ($termIndex > 1) {
						$searchUrl .= '&';
					}
					$term = str_replace(',', '', $term);
					$searchIndex = $term['index'];
					$searchUrl .= "query-{$termIndex}=AND," . urlencode($searchIndex . ":" . $term['lookfor']);
					$termIndex++;
					$hasSearchTerm = true;
				}
			}
		} else {
			if (isset($_REQUEST['searchIndex'])) {
				$this->searchIndex = $_REQUEST['searchIndex'];
			}
			$searchTerms = str_replace(',', '', $this->searchTerms);
			if (!empty($searchTerms)) {
				$searchTerms = $this->searchIndex . ':' . $searchTerms;
				$searchUrl = $this->edsBaseApi . '/Search?query=' . urlencode($searchTerms);
				$hasSearchTerm = true;
			}
		}
		if (!$hasSearchTerm) {
			return new AspenError('Please specify a search term');
		}
		$searchUrl .= '&searchmode=all';

		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1) {
			$this->page = $_REQUEST['page'];
			$searchUrl .= '&pagenumber=' . $this->page;
		} else {
			$this->page = 1;
			$searchUrl .= '&relatedcontent=rs';
		}

		$searchUrl .= '&sort=' . $this->sort;

		$searchUrl .= "&highlight=n&view=detailed&autosuggest=n&autocorrect=n";

		$facetIndex = 1;
		foreach ($this->filterList as $field => $filter) {
			$appliedFilters = '';
			//Facets are applied differently in EDS than Solr. Format is filter, Field
			if (is_array($filter)) {
				$appliedFilters .= "$facetIndex,";
				foreach ($filter as $fieldIndex => $fieldValue) {
					if ($fieldIndex > 0) {
						$appliedFilters .= ',';
					}
					$appliedFilters .= "$field:" . urlencode($fieldValue);
				}
			} else {
				$appliedFilters .= "$facetIndex,$field:" . urlencode($filter);
			}
			$searchUrl .= '&facetfilter=' . $appliedFilters;

			$facetIndex++;
		}

		$limitList = $this->getLimitList();
		foreach ($limitList as $limiter => $limiterOptions) {
			if ($limiterOptions['isApplied']) {
				$searchUrl .= '&limiter=' . $limiter . ':y';
			}
		}

		$curlConnection = $this->getCurlConnection();
		curl_setopt($curlConnection, CURLOPT_HTTPGET, true);

		curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
			'x-authenticationToken: ' . SearchObject_EbscoEdsSearcher::$authenticationToken,
			'x-sessionToken: ' . SearchObject_EbscoEdsSearcher::$sessionId,
		]);
		curl_setopt($curlConnection, CURLOPT_URL, $searchUrl);
		$result = curl_exec($curlConnection);
		try {
			$searchData = json_decode($result);
			$this->stopQueryTimer();
			if ($searchData && empty($searchData->ErrorNumber)) {
				$this->resultsTotal = $searchData->SearchResult->Statistics->TotalHits;
				$this->lastSearchResults = $searchData->SearchResult;

				return $searchData->SearchResult;
			} else {
				global $configArray;
				global $logger;
				if (IPAddress::showDebuggingInformation()) {
					$curlInfo = curl_getinfo($curlConnection);
					$logger->log(print_r($curlInfo(true)), Logger::LOG_WARNING);
				}
				$this->lastSearchResults = false;
				return new AspenError("Error processing search in EBSCO EDS");
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error loading data from EBSCO $e", Logger::LOG_ERROR);
			return new AspenError("Error loading data from EBSCO $e");
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

	function getBrowseRecordHTML() {
		global $interface;
		$html = [];
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

	public function getSpotlightResults(CollectionSpotlight $spotlight) {
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

	public function setSearchTerm($searchTerm) {
		if (strpos($searchTerm, ':') !== false) {
			[
				$searchIndex,
				$term,
			] = explode(':', $searchTerm, 2);
			$this->setSearchTerms([
				'lookfor' => $term,
				'index' => $searchIndex,
			]);
		} else {
			$this->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => $this->getDefaultIndex(),
			]);
		}
	}

	public function disableSpelling() {
		//Do nothing for now
	}

	public function getDefaultIndex() {
		return 'TX';
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resources
	 */
	public function getRecords($ids) {
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
		foreach ($ids as $index => $id) {
			$records[$index] = new EbscoRecordDriver($id);
		}
		return $records;
	}

	public function getLimitList() {
		global $memCache;
		$limitOptions = $memCache->get('ebsco_eds_limit_options_' . $this->getSettings()->edsApiProfile);
		if ($limitOptions === false) {
			$searchOptions = $this->getSearchOptions();
			$limitOptions = [];
			if ($searchOptions != null) {
				foreach ($searchOptions->AvailableSearchCriteria->AvailableLimiters as $limitOption) {
					if ($limitOption->Type == 'select') {
						$limit = $limitOption->Id;
						$desc = $limitOption->Label;
						$limitOptions[$limit] = [
							'display' => $desc,
							'value' => $limit,
							'defaultOn' => false,
						];
						if ($limit == 'FT') {
							$limitOptions[$limit]['defaultOn'] = true;
						}
					}
				}
				global $configArray;
				$memCache->set('ebsco_eds_limit_options_' . $this->getSettings()->edsApiProfile, $limitOptions, $configArray['Caching']['ebsco_options']);
			}
		}
		$limitList = [];
		foreach ($limitOptions as $limit => $limitOption) {
			if (array_key_exists($limit, $this->limiters)) {
				$limitIsApplied = ($this->limiters[$limit]) == 'y' ? 1 : 0;
			} else {
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

	public function getResearchStarters($searchTerm = null) {
		$researchStarters = [];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->hideResearchStarters == 1) {
				return $researchStarters;
			}
		}
		if (empty($this->lastSearchResults) || $this->searchIndex == 'TX') {
			$searchResults = null;
			if (!empty($searchTerm)) {
				if ($this->authenticate()) {

					$searchUrl = $this->edsBaseApi . '/Search?query=' . urlencode($searchTerm);

					$searchUrl .= '&includefacets=n&searchmode=all&relatedcontent=rs&highlight=n&view=title&autosuggest=n&autocorrect=n';

					$curlConnection = $this->getCurlConnection();
					curl_setopt($curlConnection, CURLOPT_HTTPGET, true);

					curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
						'Content-Type: application/json',
						'Accept: application/json',
						'x-authenticationToken: ' . SearchObject_EbscoEdsSearcher::$authenticationToken,
						'x-sessionToken: ' . SearchObject_EbscoEdsSearcher::$sessionId,
					]);
					curl_setopt($curlConnection, CURLOPT_URL, $searchUrl);
					$result = curl_exec($curlConnection);
					try {
						$searchData = json_decode($result);
						if ($searchData && empty($searchData->ErrorNumber)) {
							$searchResults = $searchData->SearchResult;
						} else {
							global $logger;
							if (IPAddress::showDebuggingInformation()) {
								$curlInfo = curl_getinfo($curlConnection);
								$logger->log(print_r($curlInfo(true)), Logger::LOG_WARNING);
							}
						}
					} catch (Exception $e) {
						global $logger;
						$logger->log("Error loading data from EBSCO $e", Logger::LOG_ERROR);
					}
				}
			}
		} else {
			$searchResults = $this->lastSearchResults;
		}
		if ($searchResults != null) {
			if (!empty($searchResults->RelatedContent)) {
				foreach ($searchResults->RelatedContent->RelatedRecords as $relatedRecord) {
					if ($relatedRecord->Type == 'rs') {
						foreach ($relatedRecord->Records as $record) {
							require_once ROOT_DIR . '/sys/Ebsco/ResearchStarter.php';
							$researchStarter = new ResearchStarter();
							$researchStarter->setRecordDriver(new EbscoRecordDriver($record));
							if (!$researchStarter->isHidden()) {
								$researchStarters[] = $researchStarter;
							}
						}
					}
				}
			}
		}

		return $researchStarters;
	}
}