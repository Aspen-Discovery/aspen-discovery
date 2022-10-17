<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_OpenArchivesSearcher extends SearchObject_SolrSearcher
{
	public function __construct()
	{
		parent::__construct();

		global $configArray;
		global $timer;

		$this->resultsModule = 'OpenArchives';

		$this->searchType = 'open_archives';
		$this->basicSearchType = 'open_archives';

		require_once ROOT_DIR . "/sys/SolrConnector/OpenArchivesSolrConnector.php";
		$this->indexEngine = new OpenArchivesSolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Open Archives');

		$this->allFacetSettings = getExtraConfigArray('openArchivesFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('openArchivesSearches');
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

		$timer->logTime('Setup Open Archives Search Object');
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @return  boolean
	 */
	public function init($searchSource = null)
	{
		// Call the standard initialization routine in the parent:
		parent::init('open_archives');

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
			'OpenArchivesKeyword' => translate(['text' => 'Keyword', 'Keyword', 'isPublicFacing' => true, 'inAttribute' => true]),
			'OpenArchivesTitle' => translate(['text' => 'Title', 'Title', 'isPublicFacing' => true, 'inAttribute' => true]),
			'OpenArchivesSubject' => translate(['text' => 'Subject', 'Subject', 'isPublicFacing' => true, 'inAttribute' => true]),
		];
	}

	/**
	 * Turn our results into an Excel document
	 * @param null|array $result
	 */
	public function buildExcel($result = null)
	{
		// TODO: Implement buildExcel() method.
	}

	public function getUniqueField()
	{
		return 'identifier';
	}

	public function getRecordDriverForResult($current)
	{
		require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
		return new OpenArchivesRecordDriver($current);
	}

	public function getSearchesFile()
	{
		return 'openArchivesSearches';
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
		if ($searchIndex == 'OpenArchivesTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'OpenArchivesSubject') {
			$suggestionHandler = 'subject_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	//TODO: Convert this to use definitions
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$collection = new LibraryFacetSetting();
			$collection->id = 1;
			$collection->multiSelect = true;
			$collection->facetName = "collection_name";
			$collection->displayName = "Collection";
			$collection->numEntriesToShowByDefault = 5;
			$collection->translate = true;
			$collection->collapseByDefault = false;
			$collection->useMoreFacetPopup = true;
			$facetConfig["collection_name"] = $collection;

			$creator = new LibraryFacetSetting();
			$creator->id = 2;
			$creator->multiSelect = true;
			$creator->facetName = "creator_facet";
			$creator->displayName = "Creator";
			$creator->numEntriesToShowByDefault = 5;
			$creator->collapseByDefault = true;
			$creator->useMoreFacetPopup = true;
			$facetConfig["creator_facet"] = $creator;

			$contributor = new LibraryFacetSetting();
			$contributor->id = 3;
			$contributor->multiSelect = true;
			$contributor->facetName = "contributor_facet";
			$contributor->displayName = "Contributor";
			$contributor->numEntriesToShowByDefault = 5;
			$contributor->collapseByDefault = true;
			$contributor->useMoreFacetPopup = true;
			$facetConfig["contributor_facet"] = $contributor;

			$type = new LibraryFacetSetting();
			$type->id = 4;
			$type->multiSelect = true;
			$type->facetName = "type";
			$type->displayName = "Type";
			$type->numEntriesToShowByDefault = 5;
			$type->collapseByDefault = true;
			$type->useMoreFacetPopup = true;
			$type->translate;
			$facetConfig["type"] = $type;

			$subject = new LibraryFacetSetting();
			$subject->id = 5;
			$subject->multiSelect = true;
			$subject->facetName = "subject_facet";
			$subject->displayName = "Subject";
			$subject->numEntriesToShowByDefault = 5;
			$subject->collapseByDefault = true;
			$subject->useMoreFacetPopup = true;
			$subject->translate;
			$facetConfig["subject_facet"] = $subject;

			$publisher = new LibraryFacetSetting();
			$publisher->id = 6;
			$publisher->multiSelect = true;
			$publisher->facetName = "publisher_facet";
			$publisher->displayName = "Publisher";
			$publisher->numEntriesToShowByDefault = 5;
			$publisher->collapseByDefault = true;
			$publisher->useMoreFacetPopup = true;
			$facetConfig["publisher_facet"] = $publisher;

			$source = new LibraryFacetSetting();
			$source->id = 7;
			$source->multiSelect = true;
			$source->facetName = "source";
			$source->displayName = "Source";
			$source->numEntriesToShowByDefault = 5;
			$source->collapseByDefault = true;
			$source->useMoreFacetPopup = true;
			$facetConfig["source"] = $source;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName(){
		return 'OpenArchives';
	}

	public function getDefaultIndex()
	{
		return 'OpenArchivesKeyword';
	}
}