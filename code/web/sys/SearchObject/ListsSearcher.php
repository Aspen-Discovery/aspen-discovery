<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_ListsSearcher extends SearchObject_SolrSearcher {
	public function __construct() {
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
			'title' => 'Title',
		];

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
	public function init($searchSource = null) {
		// Call the standard initialization routine in the parent:
		parent::init('lists');

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

		//Validate we got good search terms
		foreach ($this->searchTerms as &$searchTerm) {
			if (isset($searchTerm['index'])) {
				if ($searchTerm['index'] == 'Keyword') {
					$searchTerm['index'] = 'ListsKeyword';
				} elseif ($searchTerm['index'] == 'Title') {
					$searchTerm['index'] = 'ListsTitle';
				} elseif ($searchTerm['index'] == 'Author') {
					$searchTerm['index'] = 'ListsAuthor';
				}
			} else {
				foreach ($searchTerm['group'] as &$group) {
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

	public function getSearchIndexes() {
		return [
			'ListsKeyword' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'ListsTitle' => translate([
				'text' => 'Title',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'ListsAuthor' => translate([
				'text' => 'Author',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	/**
	 * Turn our results into a csv document
	 * @param array $result
	 */
	public function buildExcel($result = null) {
		try {
			global $configArray;

			if (is_null($result)) {
				$this->limit = 1000;
				$result = $this->processSearch(false, false);
			}

			//Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="SearchResults.csv"');
			$fp = fopen('php://output', 'w');

			$fields = array('Link', 'List Title', 'Created By', 'Number of Titles', 'Description');
			fputcsv($fp, $fields);

			$docs = $result['response']['docs'];

			for ($i = 0; $i < count($docs); $i++) {
				//Output the row to csv
				$curDoc = $docs[$i];
				//Output the row to csv
				$link = '';
				if ($curDoc['id']) {
					$link = $configArray['Site']['url'] . '/MyAccount/MyList/' . $curDoc['id'];
				}

				$title = $curDoc['title_display'];

				$author = $curDoc['author_display'];

				$numTitles = $curDoc['num_titles'];

				$description = $curDoc['description'];

				$row = array ($link, $title, $author, $numTitles, $description);
				fputcsv($fp, $row);
			}

			exit();
		}
		catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create csv file " . $e, Logger::LOG_ERROR);
		}
	}

	public function getUniqueField() {
		return 'id';
	}

	public function getRecordDriverForResult($current) {
		require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
		return new ListsRecordDriver($current);
	}

	public function getSearchesFile() {
		return 'listsSearches';
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
		if ($searchIndex == 'ListsTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'ListsAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	//TODO: Convert this to use definitions so they can be customized in admin
	public function getFacetConfig() {
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

	public function getEngineName() {
		return 'Lists';
	}

	public function getDefaultIndex() {
		return 'ListsKeyword';
	}
}