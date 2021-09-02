<?php

require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

class SearchObject_GenealogySearcher extends SearchObject_SolrSearcher
{
	// Facets information
	protected $allFacetSettings = array();

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
		// Include our solr index
		$this->searchType = 'genealogy';
		$this->basicSearchType = 'genealogy';
		// Initialise the index
		require_once ROOT_DIR . "/sys/SolrConnector/GenealogySolrConnector.php";
		$this->indexEngine = new GenealogySolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Genealogy');

		$this->allFacetSettings = getExtraConfigArray('genealogyFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('genealogySearches');
		if (isset($searchSettings['General']['default_sort'])) {
			$this->defaultSort = $searchSettings['General']['default_sort'];
		}
		if (isset($searchSettings['DefaultSortingByType']) &&
			is_array($searchSettings['DefaultSortingByType'])) {
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
			'lastName' => 'Last Name',
			'firstName' => 'First Name',
			'deathYear desc' => 'Year of Death',
			'deathYear asc' => "Year of Death Asc",
			'birthYear desc' => 'Year of Birth',
			'birthYear asc' => "Year of Birth Asc",
		);

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Genealogy Search Object');
	}

	public function setDebugging($enableDebug, $enableSolrQueryDebugging)
	{
		$this->debug = $enableDebug;
		$this->debugSolrQuery = $enableDebug && $enableSolrQueryDebugging;
		$this->getIndexEngine()->setDebugging($enableDebug, $enableSolrQueryDebugging);
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = 'genealogy')
	{
		// Call the standard initialization routine in the parent:
		parent::init('genealogy');

		$this->searchType = 'genealogy';
		$this->basicSearchType = 'genealogy';

		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch();
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

		//********************
		// Basic Search logic
		if ($this->initBasicSearch()) {
			// If we found a basic search, we don't need to do anything further.
		} else {
			$this->initAdvancedSearch();
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
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
		// Call the standard initialization routine in the parent:
		parent::init();

		//********************
		// Adjust facet options to use advanced settings
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

	/**
	 * Return the field (index) searched by a basic search
	 *
	 * @access  public
	 * @return  string   The searched index
	 */
	public function getSearchIndex()
	{
		// Use normal parent method for non-advanced searches.
		if ($this->searchType == $this->basicSearchType) {
			return parent::getSearchIndex();
		} else {
			return null;
		}
	}

	/**
	 * Return the record set from the search results.
	 *
	 * @access  public
	 * @return  array   recordSet
	 */
	public function getResultRecordSet()
	{
		//Marmot add shortIds without dot for use in display.
		$recordSet = $this->indexResult['response']['docs'];
		foreach ($recordSet as $key => $record) {
			$recordSet[$key] = $record;
		}
		return $recordSet;
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

	/**
	 * Return a list of valid sort options -- overrides the base class with
	 * custom behavior for Author/Search screen.
	 *
	 * @access  public
	 * @return  array    Sort value => description array.
	 */
	protected function getSortOptions()
	{
		// Everywhere else -- use normal default behavior
		return parent::getSortOptions();
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
	 * Return a url of the current search as an RSS feed.
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
	 * Get the base URL for search results (including ? parameter prefix).
	 *
	 * @access  protected
	 * @return  string   Base URL
	 */
	protected function getBaseUrl()
	{
		// Base URL is different for author searches:
//		return '/Genealogy/Results?';
		return '/Union/Search?';
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
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->getFacetConfig();
		}

		// Start building the facet list:
		$list = array();

		// If we have no facets to process, give up now
		if (!isset($this->indexResult['facet_counts'])) {
			return $list;
		} elseif (empty($this->indexResult['facet_counts']['facet_fields']) && empty($this->indexResult['facet_counts']['facet_dates'])) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		if (isset($this->indexResult['facet_counts']['facet_dates'])) {
			$allFacets = array_merge($this->indexResult['facet_counts']['facet_fields'], $this->indexResult['facet_counts']['facet_dates']);
		} else {
			$allFacets = $this->indexResult['facet_counts']['facet_fields'];
		}

		$facetConfig = $this->getFacetConfig();
		foreach ($allFacets as $field => $data) {
			// Skip filtered fields and empty arrays:
			if (!in_array($field, $validFields) || count($data) < 1) {
				continue;
			}

			// Initialize the settings for the current field
			$list[$field] = array();
			// Add the on-screen label
			$list[$field]['label'] = $filter[$field];
			// Build our array of values for this field
			$list[$field]['list'] = array();

			// Should we translate values for the current facet?
			$translate = $facetConfig[$field]->translate;

			$list[$field]['hasApplied'] = false;
			// Loop through values:
			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = array();
				$currentSettings['value'] = $facet[0];
				$currentSettings['display'] = $translate ? translate(['text'=>$facet[0],'isPublicFacing'=>true,'isMetadata'=>true]) : $facet[0];
				$currentSettings['count'] = $facet[1];
				$currentSettings['isApplied'] = false;
				$currentSettings['url'] = $this->renderLinkWithFilter($field, $facet[0]);


				// Is this field a current filter?
				if (in_array($field, array_keys($this->filterList))) {
					// and is this value a selected filter?
					if (in_array($facet[0], $this->filterList[$field])) {
						$currentSettings['isApplied'] = true;
						$currentSettings['removalUrl'] = $this->renderLinkWithoutFilter("$field:{$facet[0]}");
					}
				}

				//Setup the key to allow sorting alphabetically if needed.
				$valueKey = $facet[0];

				// Store the collected values:
				$list[$field]['list'][$valueKey] = $currentSettings;
			}

			//How many facets should be shown by default
			$list[$field]['valuesToShow'] = 5;

			//Sort the facet alphabetically?
			//Sort the system and location alphabetically unless we are in the global scope
			$list[$field]['showAlphabetically'] = false;
			if ($list[$field]['showAlphabetically']) {
				ksort($list[$field]['list']);
			}
		}
		return $list;
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
				$this->limit = 2000;
				$result = $this->processSearch(false, false);
			}

			// Prepare the spreadsheet
			ini_set('include_path', ini_get('include_path' . ';/PHPExcel/Classes'));
			include 'PHPExcel.php';
			include 'PHPExcel/Writer/Excel2007.php';
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setTitle("Search Results");

			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Results');

			//Add headers to the table
			$sheet = $objPHPExcel->getActiveSheet();
			$curRow = 1;
			$curCol = 0;
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'First Name');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Last Name');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Birth Date');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Death Date');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Veteran Of');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Cemetery');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Addition');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Block');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Lot');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Grave');
			$maxColumn = $curCol - 1;

			for ($i = 0; $i < count($result['response']['docs']); $i++) {
				$curDoc = $result['response']['docs'][$i];
				$curRow++;
				$curCol = 0;
				//Get supplemental information from the database
				require_once ROOT_DIR . '/sys/Genealogy/Person.php';
				$person = new Person();
				$id = str_replace('person', '', $curDoc['id']);
				$person->personId = $id;
				if ($person->find(true)) {
					//Output the row to excel
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['firstName']) ? $curDoc['firstName'] : '');
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['lastName']) ? $curDoc['lastName'] : '');
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear));
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear));
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['veteranOf']) ? implode(', ', $curDoc['veteranOf']) : '');
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['cemeteryName']) ? $curDoc['cemeteryName'] : '');
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->addition);
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->block);
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->lot);
					/** @noinspection PhpUnusedLocalVariableInspection */
					$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $person->grave);
				}
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
	 * @param string $id The document to retrieve from Solr
	 * @access  public
	 * @return  array               The requested resource
	 * @throws  AspenError
	 */
	function getRecord($id)
	{
		return $this->indexEngine->getRecord($id, $this->getFieldsToReturn());
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
		$params = parent::getSearchParams();

		if (isset($_REQUEST['searchIndex'])) {
			$params[] = 'searchIndex=' . $_REQUEST['searchIndex'];
		}
		if (isset($_REQUEST['searchSource'])) {
			$params[] = 'searchSource=' . $_REQUEST['searchSource'];
		}

		return $params;
	}

	public function setPrimarySearch($flag)
	{
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function getSearchIndexes()
	{
		return [
			"GenealogyKeyword" => "Keyword",
			"GenealogyName" => "Name"
		];
	}

	/** @return PersonRecord */
	public function getRecordDriverForResult($current)
	{
		require_once ROOT_DIR . '/RecordDrivers/PersonRecord.php';
		return new PersonRecord($current);
	}

	public function getSearchesFile()
	{
		return 'genealogySearches';
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
		if ($searchIndex == 'GenealogyName') {
			$suggestionHandler = 'name_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	protected function getFieldsToReturn()
	{
		return 'id,recordtype,title,comments,firstName,lastName,middleName,maidenName,otherName,nickName,fullName,veteranOf,birthDate,birthYear,deathYear,ageAtDeath,cemeteryName,mortuaryName,sex,race,causeOfDeath,obituaryDate,obituarySource,obituaryText,spouseName,marriageDate,marriageComments';
	}

	//TODO: Convert this to use definitions
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$birthYear = new LibraryFacetSetting();
			$birthYear->id = 1;
			$birthYear->facetName = "birthYear";
			$birthYear->displayName = "Date of Birth";
			$birthYear->collapseByDefault = true;
			$facetConfig["birthYear"] = $birthYear;

			$deathYear = new LibraryFacetSetting();
			$deathYear->id = 2;
			$deathYear->multiSelect = true;
			$deathYear->facetName = "deathYear";
			$deathYear->displayName = "Date of Death";
			$deathYear->collapseByDefault = true;
			$facetConfig["deathYear"] = $deathYear;

			$veteranOf = new LibraryFacetSetting();
			$veteranOf->id = 3;
			$veteranOf->multiSelect = true;
			$veteranOf->facetName = "veteranOf";
			$veteranOf->displayName = "Veteran Of";
			$veteranOf->numEntriesToShowByDefault = 5;
			$veteranOf->collapseByDefault = true;
			$veteranOf->useMoreFacetPopup = true;
			$facetConfig["veteranOf"] = $veteranOf;

			$cemeteryName = new LibraryFacetSetting();
			$cemeteryName->id = 4;
			$cemeteryName->multiSelect = true;
			$cemeteryName->facetName = "cemeteryName";
			$cemeteryName->displayName = "Cemetery Name";
			$cemeteryName->numEntriesToShowByDefault = 5;
			$cemeteryName->collapseByDefault = true;
			$cemeteryName->useMoreFacetPopup = true;
			$facetConfig["cemeteryName"] = $cemeteryName;

			$cemeteryLocation = new LibraryFacetSetting();
			$cemeteryLocation->id = 5;
			$cemeteryLocation->multiSelect = true;
			$cemeteryLocation->facetName = "cemeteryLocation";
			$cemeteryLocation->displayName = "Cemetery Location";
			$cemeteryLocation->numEntriesToShowByDefault = 5;
			$cemeteryLocation->collapseByDefault = true;
			$cemeteryLocation->useMoreFacetPopup = true;
			$facetConfig["cemeteryLocation"] = $cemeteryLocation;

			$mortuaryName = new LibraryFacetSetting();
			$mortuaryName->id = 6;
			$mortuaryName->multiSelect = true;
			$mortuaryName->facetName = "mortuaryName";
			$mortuaryName->displayName = "Mortuary Name";
			$mortuaryName->numEntriesToShowByDefault = 5;
			$mortuaryName->collapseByDefault = true;
			$mortuaryName->useMoreFacetPopup = true;
			$facetConfig["mortuaryName"] = $mortuaryName;

			$obituarySource = new LibraryFacetSetting();
			$obituarySource->id = 7;
			$obituarySource->multiSelect = true;
			$obituarySource->facetName = "obituarySource";
			$obituarySource->displayName = "Obituary Source";
			$obituarySource->numEntriesToShowByDefault = 5;
			$obituarySource->collapseByDefault = true;
			$obituarySource->useMoreFacetPopup = true;
			$facetConfig["obituarySource"] = $obituarySource;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName(){
		return 'Genealogy';
	}

	public function getDefaultIndex()
	{
		return 'GenealogyKeyword';
	}
}