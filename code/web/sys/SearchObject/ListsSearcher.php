<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/sys/UserLists/UserListFacet.php';

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
			'title_sort' => 'Title',
			'days_since_added desc, title_sort desc' => 'Date Added Desc',
			'days_since_added asc, title_sort asc' => 'Date Added Asc',
			'days_since_updated desc, title_sort desc' => 'Date Updated Desc',
			'days_since_updated asc, title_sort asc' => 'Date Updated Asc',
		];

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Lists Search Object');
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 */
	public function init(?string $searchSource = null) : bool {
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

	public function getSearchIndexes() : array {
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
	public function buildExcel($result = null) : void {
		try {
			global $configArray;

			if (is_null($result)) {
				$this->limit = 1000;
				$result = $this->processSearch();
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

	public function getUniqueField() : string {
		return 'id';
	}

	public function getRecordDriverForResult($record) : ListsRecordDriver {
		require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
		return new ListsRecordDriver($record);
	}

	public function getSearchesFile() : string {
		return 'listsSearches';
	}

	public function supportsSuggestions() : bool {
		return true;
	}

	/**
	 * @param string $searchTerm
	 * @param string $searchIndex
	 * @return array
	 */
	public function getSearchSuggestions($searchTerm, $searchIndex) : array {
		$suggestionHandler = 'suggest';
		if ($searchIndex == 'ListsTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'ListsAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	public function getFacetConfig() : array {
		if ($this->facetConfig == null) {
			global $library;
			global $solrScope;
			require_once ROOT_DIR . '/sys/UserLists/LibraryUserListFacetSetting.php';
			require_once ROOT_DIR . '/sys/UserLists/UserListFacetGroup.php';
			$libraryUserListFacetSetting = new LibraryUserListFacetSetting();
			$libraryUserListFacetSetting->libraryId = $library->libraryId;
			if ($libraryUserListFacetSetting->find(true)) {
				$userListFacetGroup = new UserListFacetGroup();
				$userListFacetGroup->id = $libraryUserListFacetSetting->userListFacetGroupId;
				if ($userListFacetGroup->find(true)) {
					$userListFacets = $userListFacetGroup->getFacets();
					if (!empty($userListFacets)) {
						$facetsToReturn = [];
						foreach ($userListFacets as $facetKey => $facetValue) {
							if ($facetValue->facetName == 'time_since_added'){
								$facetKey = "local_time_since_added_$solrScope";
								$facetValue->facetName = $facetKey;
							}elseif ($facetValue->facetName == 'time_since_updated'){
								$facetKey = "local_time_since_updated_$solrScope";
								$facetValue->facetName = $facetKey;
							}else{
								$facetKey = $facetValue->facetName;
							}
							$facetsToReturn[$facetKey] = $facetValue;
						}
						$this->facetConfig = $facetsToReturn;
					}
				}
			}

			if ($this->facetConfig == null) {
				$facetConfig = [];
				require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
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

				$daysSinceAdded = new LibraryFacetSetting();
				$daysSinceAdded->id = 2;
				$daysSinceAdded->multiSelect = false;
				$daysSinceAdded->facetName = "local_time_since_added_$solrScope";
				$daysSinceAdded->displayName = "Added In the Last";
				$daysSinceAdded->numEntriesToShowByDefault = 10;
				$daysSinceAdded->translate = true;
				$daysSinceAdded->collapseByDefault = false;
				$daysSinceAdded->useMoreFacetPopup = false;
				$facetConfig["local_time_since_added_$solrScope"] = $daysSinceAdded;

				$daysSinceUpdated = new LibraryFacetSetting();
				$daysSinceUpdated->id = 3;
				$daysSinceUpdated->multiSelect = false;
				$daysSinceUpdated->facetName = "local_time_since_updated_$solrScope";
				$daysSinceUpdated->displayName = "Updated In the Last";
				$daysSinceUpdated->numEntriesToShowByDefault = 10;
				$daysSinceUpdated->translate = true;
				$daysSinceUpdated->collapseByDefault = false;
				$daysSinceUpdated->useMoreFacetPopup = false;
				$facetConfig["local_time_since_updated_$solrScope"] = $daysSinceUpdated;

				$this->facetConfig = $facetConfig;
			}
		}
		return $this->facetConfig;
	}

	public function getEngineName() : string {
		return 'Lists';
	}

	public function getDefaultIndex() : string {
		return 'ListsKeyword';
	}

	/**
	 * @param string $scopedFieldName
	 * @return string
	 */
	public function getUnscopedFieldName(string $scopedFieldName): string {
		if (str_starts_with($scopedFieldName, 'local_time_since_added')) {
			$scopedFieldName = 'local_time_since_added';
		}else if (str_starts_with($scopedFieldName, 'local_time_since_updated')) {
			$scopedFieldName = 'local_time_since_updated';
		}
		return $scopedFieldName;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	protected function getScopedFieldName(string $field): string {
		global $solrScope;
		if ($solrScope) {
			if ($field === 'time_since_added') {
				$field = 'local_time_since_added_' . $solrScope;
			}elseif ($field === 'time_since_updated') {
				$field = 'local_time_since_updated_' . $solrScope;
			}
		}
		return $field;
	}
}
