<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_WebsitesSearcher extends SearchObject_SolrSearcher
{
	public function __construct()
	{
		parent::__construct();

		global $configArray;
		global $timer;

		$this->resultsModule = 'Websites';

		$this->searchType = 'websites';
		$this->basicSearchType = 'websites';

		require_once ROOT_DIR . "/sys/SolrConnector/WebsiteSolrConnector.php";
		$this->indexEngine = new WebsiteSolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Websites');

		$this->allFacetSettings = getExtraConfigArray('websiteFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('websiteSearches');
		$this->defaultIndex = 'WebsiteKeyword';
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
		if (isset($searchSettings['Sorting'])) {
			$this->sortOptions = $searchSettings['Sorting'];
		} else {
			$this->sortOptions = array('relevance' => 'sort_relevance',
				'title' => 'sort_title');
		}

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Website Search Object');
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
		// Call the standard initialization routine in the parent:
		parent::init('website_pages');

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

	public function getSearchIndexes()
	{
		return [
			'WebsiteKeyword' => 'Keyword',
			'WebsiteTitle' => 'Title',
		];
	}

	/**
	 * Turn our results into an Excel document
	 * @param array $result
	 */
	public function buildExcel($result = null)
	{
		// TODO: Implement buildExcel() method.
	}

	public function getUniqueField()
	{
		return 'id';
	}

	public function getRecordDriverForResult($current)
	{
		require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';
		return new WebsitePageRecordDriver($current);
	}

	public function getSearchesFile()
	{
		return 'websiteSearches';
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
		if ($searchIndex == 'WebsiteTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'WebsiteAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	public function getEngineName(){
		return 'Websites';
	}

	public function getDefaultSearchIndex()
	{
		return 'WebsiteKeyword';
	}
}