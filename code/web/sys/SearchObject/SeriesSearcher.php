<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_SeriesSearcher extends SearchObject_SolrSearcher {

	public $viewOptions = [
		'list',
		'covers',
	];

	public function __construct() {
		parent::__construct();

		global $configArray;
		global $timer;

		$this->resultsModule = 'Series';

		$this->searchType = 'series';
		$this->basicSearchType = 'series';

		require_once ROOT_DIR . "/sys/SolrConnector/SeriesSolrConnector.php";
		$this->indexEngine = new SeriesSolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Lists');

		$this->allFacetSettings = getExtraConfigArray('seriesFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('seriesSearches');

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
			'days_since_added, title_sort desc' => 'Date Added Desc',
			'days_since_added, title_sort asc' => 'Date Added Asc',
			'days_since_updated, title_sort desc' => 'Date Updated Desc',
			'days_since_updated, title_sort asc' => 'Date Updated Asc',
		];

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Series Search Object');
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = null) : bool {
		// Call the standard initialization routine in the parent:
		parent::init('series');

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
			'SeriesKeyword' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'SeriesTitle' => translate([
				'text' => 'Series Title',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'SeriesAuthor' => translate([
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

			$fields = array('Link', 'Series Title', 'Author', 'Number of Titles', 'Description');
			fputcsv($fp, $fields);

			$docs = $result['response']['docs'];

			for ($i = 0; $i < count($docs); $i++) {
				//Output the row to csv
				$curDoc = $docs[$i];
				//Output the row to csv
				$link = '';
				if ($curDoc['id']) {
					$link = $configArray['Site']['url'] . '/Series/' . $curDoc['id'];
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

	public function getRecordDriverForResult($record) : SeriesRecordDriver {
		require_once ROOT_DIR . '/RecordDrivers/SeriesRecordDriver.php';
		return new SeriesRecordDriver($record);
	}

	public function getSearchesFile() : string {
		return 'seriesSearches';
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
		if ($searchIndex == 'SeriesTitle') {
			$suggestionHandler = 'title_suggest';
		}
		if ($searchIndex == 'SeriesAuthor') {
			$suggestionHandler = 'author_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	//TODO: Convert this to use definitions so they can be customized in admin
	public function getFacetConfig() : array {
		if ($this->facetConfig == null) {
			$facetConfig = [];
			global $solrScope;

			$author = new LibraryFacetSetting();
			$author->id = 1;
			$author->multiSelect = false;
			$author->facetName = "author_display";
			$author->displayName = "Author";
			$author->numEntriesToShowByDefault = 5;
			$author->translate = true;
			$author->collapseByDefault = true;
			$author->useMoreFacetPopup = true;
			$facetConfig["author_display"] = $author;

			$audience = new LibraryFacetSetting();
			$audience->id = 2;
			$audience->multiSelect = true;
			$audience->facetName = "audience_facet";
			$audience->displayName = "Reading Level";
			$audience->numEntriesToShowByDefault = 10;
			$audience->translate = true;
			$audience->collapseByDefault = true;
			$audience->useMoreFacetPopup = false;
			$facetConfig["audience_facet"] = $audience;

			$language = new LibraryFacetSetting();
			$language->id = 3;
			$language->multiSelect = true;
			$language->facetName = "language";
			$language->displayName = "Language";
			$language->numEntriesToShowByDefault = 10;
			$language->translate = false;
			$language->collapseByDefault = true;
			$language->useMoreFacetPopup = false;
			$facetConfig["language"] = $language;

			$literaryForm = new LibraryFacetSetting();
			$literaryForm->id = 4;
			$literaryForm->multiSelect = false;
			$literaryForm->facetName = "literary_form";
			$literaryForm->displayName = "Literary Form";
			$literaryForm->numEntriesToShowByDefault = 10;
			$literaryForm->translate = true;
			$literaryForm->collapseByDefault = true;
			$literaryForm->useMoreFacetPopup = false;
			$facetConfig["literary_form"] = $literaryForm;

			$subject = new LibraryFacetSetting();
			$subject->id = 5;
			$subject->multiSelect = true;
			$subject->facetName = "subject";
			$subject->displayName = "Subject";
			$subject->numEntriesToShowByDefault = 10;
			$subject->translate = true;
			$subject->collapseByDefault = true;
			$subject->useMoreFacetPopup = true;
			$facetConfig["subject"] = $subject;

			$eContentSource = new LibraryFacetSetting();
			$eContentSource->id = 6;
			$eContentSource->multiSelect = true;
			$eContentSource->facetName = "econtent_source_$solrScope";
			$eContentSource->displayName = "eContent Source";
			$eContentSource->numEntriesToShowByDefault = 10;
			$eContentSource->translate = true;
			$eContentSource->collapseByDefault = true;
			$eContentSource->useMoreFacetPopup = false;
			$facetConfig["econtent_source_$solrScope"] = $eContentSource;

			$format = new LibraryFacetSetting();
			$format->id = 7;
			$format->multiSelect = true;
			$format->facetName = "format_$solrScope";
			$format->displayName = "Format";
			$format->numEntriesToShowByDefault = 10;
			$format->translate = true;
			$format->collapseByDefault = true;
			$format->useMoreFacetPopup = false;
			$facetConfig["format_$solrScope"] = $format;

			$formatCategory = new LibraryFacetSetting();
			$formatCategory->id = 8;
			$formatCategory->multiSelect = true;
			$formatCategory->facetName = "format_category_$solrScope";
			$formatCategory->displayName = "Format Category";
			$formatCategory->numEntriesToShowByDefault = 10;
			$formatCategory->translate = true;
			$formatCategory->collapseByDefault = true;
			$formatCategory->useMoreFacetPopup = false;
			$facetConfig["format_category_$solrScope"] = $formatCategory;

			$daysSinceAdded = new LibraryFacetSetting();
			$daysSinceAdded->id = 9;
			$daysSinceAdded->multiSelect = false;
			$daysSinceAdded->facetName = "local_time_since_added_$solrScope";
			$daysSinceAdded->displayName = "Added In the Last";
			$daysSinceAdded->numEntriesToShowByDefault = 10;
			$daysSinceAdded->translate = true;
			$daysSinceAdded->collapseByDefault = true;
			$daysSinceAdded->useMoreFacetPopup = false;
			$facetConfig["local_time_since_added_$solrScope"] = $daysSinceAdded;

			$daysSinceUpdated = new LibraryFacetSetting();
			$daysSinceUpdated->id = 10;
			$daysSinceUpdated->multiSelect = false;
			$daysSinceUpdated->facetName = "local_time_since_updated_$solrScope";
			$daysSinceUpdated->displayName = "Updated In the Last";
			$daysSinceUpdated->numEntriesToShowByDefault = 10;
			$daysSinceUpdated->translate = true;
			$daysSinceUpdated->collapseByDefault = true;
			$daysSinceUpdated->useMoreFacetPopup = false;
			$facetConfig["local_time_since_updated_$solrScope"] = $daysSinceUpdated;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName() : string {
		return 'Series';
	}

	public function getDefaultIndex() : string {
		return 'SeriesKeyword';
	}

	/**
	 * @param string $scopedFieldName
	 * @return string
	 */
	protected function getUnscopedFieldName(string $scopedFieldName): string {
		if (str_starts_with($scopedFieldName, 'local_time_since_added')) {
			$scopedFieldName = 'local_time_since_added';
		} else if (str_starts_with($scopedFieldName, 'local_time_since_updated')) {
			$scopedFieldName = 'local_time_since_updated';
		} else if (str_starts_with($scopedFieldName, 'format')) {
			$scopedFieldName = 'format';
		} else if (str_starts_with($scopedFieldName, 'format_category')) {
			$scopedFieldName = 'format_category';
		} else if (str_starts_with($scopedFieldName, 'econtent_source')) {
			$scopedFieldName = 'econtent_source';
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
			} elseif ($field === 'time_since_updated') {
				$field = 'local_time_since_updated_' . $solrScope;
			} elseif ($field === 'format') {
				$field = 'format_' . $solrScope;
			} elseif ($field === 'format_category') {
				$field = 'format_category_' . $solrScope;
			} elseif ($field === 'econtent_source') {
				$field = 'econtent_source_' . $solrScope;
			}
		}
		return $field;
	}
}
