<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacet.php';

class SearchObject_WebsitesSearcher extends SearchObject_SolrSearcher {
	public function __construct() {
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
		if (isset($searchSettings['DefaultSortingByType']) && is_array($searchSettings['DefaultSortingByType'])) {
			$this->defaultSortByType = $searchSettings['DefaultSortingByType'];
		}
		if (isset($searchSettings['Basic_Searches'])) {
			$this->searchIndexes = $searchSettings['Basic_Searches'];
		}
		if (isset($searchSettings['Advanced_Searches'])) {
			$this->advancedTypes = $searchSettings['Advanced_Searches'];
		}

		// Load sort preferences (or defaults if none in .ini file):
		$this->sortOptions = [
			'relevance' => 'Best Match',
			'title_sort' => 'Title',
		];

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
	public function init($searchSource = null) {
		// Call the standard initialization routine in the parent:
		parent::init('website_pages');

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

	public function getSearchIndexes() {
		return [
			'WebsiteKeyword' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'WebsiteTitle' => translate([
				'text' => 'Title',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	/**
	 * Turn our results into an Excel document
	 * @param array $result
	 */
	public function buildExcel($result = null) {
		// TODO: Implement buildExcel() method.
	}

	public function getUniqueField() {
		return 'id';
	}

	public function getRecordDriverForResult($record) {
		if ($record['recordtype'] == 'WebPage') {
			require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';
			return new WebsitePageRecordDriver($record);
		} elseif ($record['recordtype'] == 'WebResource') {
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
			return new WebResourceRecordDriver($record);
		} elseif ($record['recordtype'] == 'BasicPage') {
			require_once ROOT_DIR . '/RecordDrivers/BasicPageRecordDriver.php';
			return new BasicPageRecordDriver($record);
		} elseif ($record['recordtype'] == 'PortalPage') {
			require_once ROOT_DIR . '/RecordDrivers/PortalPageRecordDriver.php';
			return new PortalPageRecordDriver($record);
		} elseif ($record['recordtype'] == 'GrapesPage') {
			require_once ROOT_DIR . '/RecordDrivers/GrapesPageRecordDriver.php';
			return new GrapesPageRecordDriver($record);
		} elseif ($record['recordtype'] == 'ResourceAudiencePage' || $record['recordtype'] == 'ResourceCategoryPage' || $record['recordtype'] == 'CustomResourcePage' || $record['recordtype'] == 'WebResourcesAtoZ') {
			require_once ROOT_DIR . '/RecordDrivers/CustomResourcePagesRecordDriver.php';
			return new CustomResourcePagesRecordDriver($record);
		} else {
			AspenError::raiseError("Unknown type of Website result {$record['recordtype']}");
		}
		return null;
	}

	public function getSearchesFile() {
		return 'websiteSearches';
	}

	public function supportsSuggestions() {
		return true;
	}

	/**
	 * @param string $searchTerm
	 * @param string $searchIndex
	 * @return array
	 */
	public function getSearchSuggestions($searchTerm, $searchIndex) {
		$suggestionHandler = 'suggest';
		if ($searchIndex == 'WebsiteTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'WebsiteAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

    public function getFacetConfig() {
        if ($this->facetConfig == null) {
            $facetConfig = [];
            $facets = [];
            $searchLibrary = Library::getActiveLibrary();
            global $locationSingleton;
            $searchLocation = $locationSingleton->getActiveLocation();
            if ($searchLocation != null) {
                if ($searchLocation->getWebsiteFacetSettings() != null){
                    $facets = $searchLocation->getWebsiteFacetSettings()->getFacets();
                }
            } else if ($searchLibrary->getWebsiteFacetSettings() != null){
                $facets = $searchLibrary->getWebsiteFacetSettings()->getFacets();
            }
            if ($facets != null){
                foreach ($facets as &$facet) {
                    $facetConfig[$facet->facetName] = $facet;
                }
                $this->facetConfig = $facetConfig;
            }
        }

        return $this->facetConfig;
    }

	public function getEngineName() {
		return 'Websites';
	}

	public function getDefaultIndex() {
		return 'WebsiteKeyword';
	}
}
