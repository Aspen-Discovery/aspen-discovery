<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_ListsSearcher extends SearchObject_SolrSearcher
{
	public function __construct()
	{
		parent::__construct();

		global $configArray;
		global $timer;

		$this->resultsModule = 'Lists';

		$this->searchType = 'lists';
		$this->basicSearchType = 'lists';

		require_once ROOT_DIR . "/sys/SolrConnector/ListsSolrConnector.php";
		$this->indexEngine = new ListsSolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Lists');

		$this->allFacetSettings = getExtraConfigArray('listsFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('listsSearches');

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

		$timer->logTime('Setup Lists Search Object');
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
		parent::init('lists');

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

		//Validate we got good search terms
		foreach ($this->searchTerms as &$searchTerm) {
			if (isset($searchTerm['index'])){
				if ($searchTerm['index'] == 'Keyword') {
					$searchTerm['index'] = 'ListsKeyword';
				} elseif ($searchTerm['index'] == 'Title') {
					$searchTerm['index'] = 'ListsTitle';
				} elseif ($searchTerm['index'] == 'Author') {
					$searchTerm['index'] = 'ListsAuthor';
				}
			}else{
				foreach ($searchTerm['group'] as &$group){
					if ($group['field'] == 'Keyword') {
						$group['field'] = 'ListsKeyword';
					} elseif ($group['field'] == 'Title') {
						$group['field'] = 'ListsTitle';
					} elseif ($group['field'] == 'Author') {
						$group['field'] = 'ListsAuthor';
					}
				}
			}
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
			'ListsKeyword' => 'Keyword',
			'ListsTitle' => 'Title',
			'ListsAuthor' => 'Author',
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
		require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
		return new ListsRecordDriver($current);
	}

	public function getSearchesFile()
	{
		return 'listsSearches';
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
		if ($searchIndex == 'ListsTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'ListsAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	//TODO: Convert this to use definitions so they can be customized in admin
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$author = new LibraryFacetSetting();
			$author->id = 1;
			$author->multiSelect = true;
			$author->facetName = "author_display";
			$author->displayName = "Created By";
			$author->numEntriesToShowByDefault = 5;
			$author->translate = true;
			$author->collapseByDefault = false;
			$author->useMoreFacetPopup = true;
			$facetConfig["author_display"] = $author;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName(){
		return 'Lists';
	}

	public function getDefaultIndex()
	{
		return 'ListsKeyword';
	}
}