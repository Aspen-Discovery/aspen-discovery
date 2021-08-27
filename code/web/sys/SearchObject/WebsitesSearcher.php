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
			'title' => 'Title'
		);

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
		if ($current['recordtype'] == 'WebPage') {
			require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';
			return new WebsitePageRecordDriver($current);
		}elseif ($current['recordtype'] == 'WebResource'){
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
			return new WebResourceRecordDriver($current);
		}elseif ($current['recordtype'] == 'BasicPage'){
			require_once ROOT_DIR . '/RecordDrivers/BasicPageRecordDriver.php';
			return new BasicPageRecordDriver($current);
		}else{
			AspenError::raiseError("Unknown type of Website result {$current['recordtype']}");
		}
		return null;
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

	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$audienceFacet = new LibraryFacetSetting();
			$audienceFacet->id = count($facetConfig) +1;
			$audienceFacet->multiSelect = true;
			$audienceFacet->facetName = "audience_facet";
			$audienceFacet->displayName = "Audience";
			$audienceFacet->numEntriesToShowByDefault = 5;
			$audienceFacet->translate = true;
			$audienceFacet->collapseByDefault = true;
			$audienceFacet->useMoreFacetPopup = true;
			$facetConfig["audience_facet"] = $audienceFacet;

			$categoryFacet = new LibraryFacetSetting();
			$categoryFacet->id = count($facetConfig) +1;
			$categoryFacet->multiSelect = true;
			$categoryFacet->facetName = "category_facet";
			$categoryFacet->displayName = "Category";
			$categoryFacet->numEntriesToShowByDefault = 5;
			$categoryFacet->translate = true;
			$categoryFacet->collapseByDefault = true;
			$categoryFacet->useMoreFacetPopup = true;
			$facetConfig["category_facet"] = $categoryFacet;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName(){
		return 'Websites';
	}

	public function getDefaultIndex()
	{
		return 'WebsiteKeyword';
	}
}