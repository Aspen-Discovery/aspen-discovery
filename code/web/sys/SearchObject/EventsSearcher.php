<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/sys/Events/EventsFacet.php';

class SearchObject_EventsSearcher extends SearchObject_SolrSearcher {
	public function __construct() {
		parent::__construct();

		global $configArray;
		global $timer;

		$this->resultsModule = 'Events';

		$this->searchType = 'events';
		$this->basicSearchType = 'events';

		require_once ROOT_DIR . "/sys/SolrConnector/EventsSolrConnector.php";
		$this->indexEngine = new EventsSolrConnector($configArray['Index']['url']);
		$timer->logTime('Created Index Engine for Events');

		$this->allFacetSettings = getExtraConfigArray('eventsFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('eventsSearches');

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
			'start_date_sort asc' => 'Event Date',
			'title_sort' => 'Title',
		];

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$now = new DateTime();
		$this->addHiddenFilter('end_date', "[{$now->format("Y-m-d\TH:i:s\Z")} TO *]");

		// Check permissions before showing private events
		if (!UserAccount::userHasPermission('View Private Events for All Locations')) {
			if (!UserAccount::userHasPermission([
					'View Private Events for Home Library Locations',
					'View Private Events for Home Location'
				])) {
				$this->addHiddenFilter('-private', "private");
			} else {
				if (!UserAccount::userHasPermission('View Private Events for Home Library Locations')) {
					$user = UserAccount::getLoggedInUser();
					$locations = array_values($user->getAdditionalAdministrationLocations());
					$locations[] = $user->getHomeLocationName();
					$this->addHiddenFilter('private', '("' . implode('" OR "private_', $locations) . '" OR "public")');
				} else {
					$locations = array_values(Location::getLocationList(true));
					$this->addHiddenFilter('private', '("private_' . implode('" OR "private_', $locations) . '" OR "public")');
				}
			}
		}

		$timer->logTime('Setup Events Search Object');
	}

	/**
	 * Actually process and submit the search
	 *
	 * @access  public
	 * @param bool $returnIndexErrors Should we die inside the index code if
	 *                                     we encounter an error (false) or return
	 *                                     it for access via the getIndexError()
	 *                                     method (true)?
	 * @param bool $recommendations Should we process recommendations along
	 *                                     with the search itself?
	 * @param bool $preventQueryModification Should we allow the search engine
	 *                                             to modify the query or is it already
	 *                                             a well formatted query
	 * @return  array solr result structure (for now)
	 */
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) : AspenError|array|null {
		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendations module appropriate to the current search:
		if ($recommendations) {
			$this->initRecommendations();
		}

		// Build Query
		if ($preventQueryModification) {
			$query = $search;
		} else {
			$query = $this->indexEngine->buildQuery($search, false);
		}
		if (($query instanceof AspenError)) {
			return $query;
		}

		// Only use the query we just built if there isn't an override in place.
		if ($this->query == null) {
			$this->query = $query;
		}

		// Define Filter Query
		$filterQuery = $this->hiddenFilters;
		//Remove any empty filters if we get them
		//(typically happens when a subdomain has a function disabled that is enabled in the main scope)
		foreach ($this->filterList as $field => $filter) {
			if (empty ($field)) {
				unset($this->filterList[$field]);
			}
		}
		$facetConfig = $this->getFacetConfig();
		foreach ($this->filterList as $field => $filter) {
			/** @var FacetSetting $facetInfo */
			$facetInfo = $facetConfig[$field];
			$fieldPrefix = "";
			if ($facetInfo->multiSelect) {
				$facetKey = empty($facetInfo->id) ? $facetInfo->facetName : $facetInfo->id;
				$fieldPrefix = "{!tag={$facetKey}}";
			}
			$fieldValue = "";
			$okToAdd = false;
			foreach ($filter as $value) {
				// Special case -- allow trailing wildcards:
				if (substr($value, -1) == '*') {
					$okToAdd = true;
				} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)) {
					$okToAdd = true;
				} else {
					if (!empty($value)) {
						$okToAdd = true;
						$value = "\"$value\"";
					}
				}
				if ($okToAdd) {
					if ($facetInfo->multiSelect) {
						if (!empty($fieldValue)) {
							$fieldValue .= ' OR ';
						}
						$fieldValue .= $value;
					} else {
						$filterQuery[] = "$fieldPrefix$field:$value";
					}
				}
			}
			if ($facetInfo->multiSelect) {
				$filterQuery[] = "$fieldPrefix$field:($fieldValue)";
			}
		}

		// If we are only searching one field use the DisMax handler
		//    for that field. If left at null let solr take care of it
		if (count($search) == 1 && isset($search[0]['index'])) {
			$this->index = $search[0]['index'];
		}

		// Build a list of facets we want from the index
		$facetSet = [];
		$facetConfig = $this->getFacetConfig();
		if (!empty($facetConfig)) {
			$facetSet['limit'] = $this->facetLimit;
			foreach ($facetConfig as $facetField => $facetInfo) {
				if ($facetField == 'start_date') {
					//special processing for start_date
					$facetName = $facetInfo->facetName;
					$facetSet['field'][$facetField] = $facetName;
					$this->facetOptions["facet.range"] = $facetName;
					$this->facetOptions["f.{$facetName}.facet.range.start"] = "NOW/DAY";
					$this->facetOptions["f.{$facetName}.facet.range.end"] = "NOW/DAY+180DAYS";
					$this->facetOptions["f.{$facetName}.facet.range.gap"] = "+1DAY";
				} else {
					if ($facetInfo instanceof EventsFacet) {
						$facetName = $facetInfo->facetName;
						$isMultiSelect = $facetInfo->multiSelect;
						if ($isMultiSelect) {
							$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
							$facetSet['field'][$facetField] = "{!ex=$facetKey}" . $facetField;
						}else{
							$facetSet['field'][$facetField] = $facetName;
						}
					} else {
						$facetSet['field'][$facetField] = $facetInfo;
					}
				}
			}
			if ($this->facetOffset != null) {
				$facetSet['offset'] = $this->facetOffset;
			}
			if ($this->facetPrefix != null) {
				$facetSet['prefix'] = $this->facetPrefix;
			}
			if ($this->facetSort != null) {
				$facetSet['sort'] = $this->facetSort;
			}
		}

		if (!empty($this->facetSearchTerm) && !empty($this->facetSearchField)) {
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains"] = $this->facetSearchTerm;
			$this->facetOptions["f.{$this->facetSearchField}.facet.contains.ignoreCase"] = 'true';
		}

		if (!empty($this->facetOptions)) {
			$facetSet['additionalOptions'] = $this->facetOptions;
		}

		// Build our spellcheck query
		if ($this->spellcheckEnabled) {
			$spellcheck = $this->buildSpellingQuery();

			// If the spellcheck query is purely numeric, skip it if
			// the appropriate setting is turned on.
			if (is_numeric($spellcheck)) {
				$spellcheck = "";
			}
		} else {
			$spellcheck = "";
		}

		// Get time before the query
		$this->startQueryTimer();

		// The "relevance" sort option is a VuFind reserved word; we need to make
		// this null in order to achieve the desired effect with Solr:
		$finalSort = ($this->sort == 'relevance') ? null : $this->sort;

		// The first record to retrieve:
		//  (page - 1) * limit = start
		$recordStart = ($this->page - 1) * $this->limit;
		$this->indexResult = $this->indexEngine->search($this->query,      // Query string
			$this->index,      // DisMax Handler
			$filterQuery,      // Filter query
			$recordStart,      // Starting record
			$this->limit,      // Records per page
			$facetSet,         // Fields to facet on
			$spellcheck,       // Spellcheck query
			$this->dictionary, // Spellcheck dictionary
			$finalSort,        // Field to sort on
			$this->fields,     // Fields to return
			'POST',     // HTTP Request method
			$returnIndexErrors // Include errors in response?
		);

		// Get time after the query
		$this->stopQueryTimer();

		// How many results were there?
		if (isset($this->indexResult['response']['numFound'])) {
			$this->resultsTotal = $this->indexResult['response']['numFound'];
		} else {
			$this->resultsTotal = 0;
		}

		// If extra processing is needed for recommendations, do it now:
		if ($recommendations && is_array($this->recommend)) {
			foreach ($this->recommend as $currentSet) {
				foreach ($currentSet as $current) {
					/** @var RecommendationInterface $current */
					$current->process();
				}
			}
		}

		//Add debug information to the results if available
		if ($this->debug && isset($this->indexResult['debug'])) {
			$explainInfo = $this->indexResult['debug']['explain'];
			foreach ($this->indexResult['response']['docs'] as $key => $result) {
				if (array_key_exists($result['identifier'], $explainInfo)) {
					$result['explain'] = $explainInfo[$result['identifier']];
					$this->indexResult['response']['docs'][$key] = $result;
				}
			}
		}

		// Return the result set
		return $this->indexResult;
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
		parent::init('events');

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
		if (!$this->initBasicSearch()) {
			$this->initAdvancedSearch();
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
		}

		//Validate we got good search terms
		foreach ($this->searchTerms as &$searchTerm) {
			if (isset($searchTerm['index'])) {
				if ($searchTerm['index'] == 'Keyword') {
					$searchTerm['index'] = 'EventsKeyword';
				} elseif ($searchTerm['index'] == 'Title') {
					$searchTerm['index'] = 'EventsTitle';
				}
			} else {
				foreach ($searchTerm['group'] as &$group) {
					if ($group['field'] == 'Keyword') {
						$group['field'] = 'EventsKeyword';
					} elseif ($group['field'] == 'Title') {
						$group['field'] = 'EventsTitle';
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
			'EventsKeyword' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'EventsTitle' => translate([
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
		if (substr($record['type'], 0, 12) == 'event_libcal') {
			require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
			return new SpringshareLibCalEventRecordDriver($record);
		} else if (substr($record['type'], 0, 15) == 'event_communico') {
			require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
			return new CommunicoEventRecordDriver($record);
		} else if (substr($record['type'], 0, 13) == 'event_assabet') {
			require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
			return new AssabetEventRecordDriver($record);
		} else if (substr($record['type'], 0, 17) == 'event_aspenEvent') {
			require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
			return new AspenEventRecordDriver($record);
		} else {
			// TODO: rewrite Library Market Library Calendar type as event_lm or something similar. 2022 03 20 James.
			require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
			return new LibraryCalendarEventRecordDriver($record);
		}
	}

	public function getSearchesFile() {
		return 'eventsSearches';
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
		if ($searchIndex == 'EventsTitle') {
			$suggestionHandler = 'title_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	public function getFacetConfig() {
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$searchLibrary = Library::getActiveLibrary();

			if ($searchLibrary->getEventFacetSettings() != null){
				$facets = $searchLibrary->getEventFacetSettings()->getFacets();

				foreach ($facets as &$facet) {
                    $facetConfig[$facet->facetName] = $facet;
                }
				$this->facetConfig = $facetConfig;
			}
		}

		return $this->facetConfig;
	}

	public function getEngineName() {
		return 'Events';
	}

	public function getDefaultIndex() {
		return 'EventsKeyword';
	}
}
