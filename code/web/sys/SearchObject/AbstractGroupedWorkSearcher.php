<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

abstract class SearchObject_AbstractGroupedWorkSearcher extends SearchObject_SolrSearcher
{
	protected $searchSubType;
	protected $searchVersion;

	public $selectedAvailabilityToggleValue;

	public function __construct($searchVersion)
	{
		parent::__construct();
		$this->searchVersion = $searchVersion;
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
		global $solrScope;
		$titleSummaries = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];
			/** @var GroupedWorkDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!($record instanceof AspenError)) {
				$isNew = false;
				if (!empty($this->searchId) && $this->savedSearch) {
					if (isset($current["local_time_since_added_$solrScope"])) {
						$isNew = in_array('Week', $current["local_time_since_added_$solrScope"]);
					}
				}
				if (!empty($orderedListOfIDs)) {
					$position = array_search($current['id'], $orderedListOfIDs);
					if ($position !== false) {
						$summary = $record->getSummaryInformation();
						$summary['isNew'] = $isNew;
						$titleSummaries[$position] = $summary;
					}
				} else {
					$summary = $record->getSummaryInformation();
					$summary['isNew'] = $isNew;
					$titleSummaries[] = $summary;
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
		global $solrScope;

		$searchEntry = new SearchEntry();
		$searchEntry = $searchEntry->getSavedSearchByUrl($this->renderSearchUrl(false), session_id(), UserAccount::getActiveUserId());
		$isSaved = false;
		if ($searchEntry != null){
			$isSaved = $searchEntry->saved;
		}
		global $library;
		$location = Location::getSearchLocation(null);
		if ($location != null){
			$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
		}else{
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		}
		$alwaysFlagNewTitles = $groupedWorkDisplaySettings->alwaysFlagNewTitles;
		$html = array();
		if (isset($this->indexResult['response'])) {
			$allWorkIds = array();
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$allWorkIds[] = $this->indexResult['response']['docs'][$x]['id'];
			}
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
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
				if ($isSaved || $alwaysFlagNewTitles) {
					if (isset($current["local_time_since_added_$solrScope"])) {
						$interface->assign('isNew', in_array('Week', $current["local_time_since_added_$solrScope"]));
					} else {
						$interface->assign('isNew', false);
					}
				} else {
					$interface->assign('isNew', false);
				}
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
			return array(
				'relevance' => 'sort_author_relevance',
				'author' => 'sort_author_author'
			);
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
			$interface->assign('lookfor', $lookfor . " (" . translate(['text' => 'with filters', 'isPublicFacing'=>true]) . ")");
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

	public function getSearchIndexes()
	{
		return [
			'Keyword' => translate(['text'=>'Keyword', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'Title' => translate(['text'=>'Title', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'StartOfTitle' => translate(['text'=>'Start of Title', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'Series' => translate(['text'=>'Series', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'Author' => translate(['text'=>'Author', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'Subject' => translate(['text'=>'Subject', 'isPublicFacing'=>true, 'inAttribute'=>true]),
		];
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
	 * @param string[] $notInterestedTitles
	 * @return    array                            An array of query results
	 */
	function getMoreLikeThese($ids, $page = 1, $limit = 25, array $notInterestedTitles = [])
	{
		return $this->indexEngine->getMoreLikeThese($ids, $this->getFieldsToReturn(), $page, $limit, $notInterestedTitles);
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
			foreach ($facets as &$facet) {
				//Adjust facet name for local scoping
				$facet->facetName = $this->getScopedFieldName($facet->getFacetName($this->searchVersion));

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
}