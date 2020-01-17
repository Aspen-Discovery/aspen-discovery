<?php

require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

class SearchObject_IslandoraSearcher extends SearchObject_SolrSearcher
{
	// Field List
	//private $fields = '*,score';
	private $fields = 'PID,fgs_label_s,dc.title,mods_abstract_s,mods_genre_s,RELS_EXT_hasModel_uri_s,dateCreated,score,fgs_createdDate_dt,fgs_lastModifiedDate_dt';

	//Whether or not filters should be applied
	private $applyStandardFilters = true;

	// Facets information
	private $allFacetSettings = array();

	// Display Modes //
	public $viewOptions = array('list', 'covers');
	protected $pidFacets = array();

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
		require_once ROOT_DIR . "/sys/SolrConnector/Solr.php";
		$this->searchType = 'islandora';
		$this->basicSearchType = 'islandora';
		// Initialise the index
		$this->indexEngine = new IslandoraSolrConnector($configArray['Islandora']['solrUrl'], isset($configArray['Islandora']['solrCore']) ? $configArray['Islandora']['solrCore'] : 'islandora');
		$timer->logTime('Created Index Engine for Islandora');

		// Get default facet settings
		$this->allFacetSettings = getExtraConfigArray('islandoraFacets');
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		$pidFacets = $this->getFacetSetting('Advanced_Settings', 'pid_facets');
		if (is_array($pidFacets)) {
			$this->pidFacets = $pidFacets;
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('islandoraSearches');
		$this->defaultIndex = 'IslandoraKeyword';
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
				'year' => 'sort_year', 'year asc' => 'sort_year asc',
				'title' => 'sort_title');
		}

		// Debugging
		$this->indexEngine->debug = $this->debug;

		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;
		$this->indexEngine->isPrimarySearch = $this->isPrimarySearch;

		$this->resultsModule = 'Archive';
		$this->resultsAction = 'Results';
		$this->searchSource = 'islandora';

		$timer->logTime('Setup Solr Search Object');
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = 'islandora')
	{
		// Call the standard initialization routine in the parent:
		parent::init('islandora');

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

		global $module, $action;
		if ($module == 'MyAccount') {
			// Users Lists
//			$this->spellcheck = false;
			$this->searchType = ($action == 'Home') ? 'favorites' : 'list';
			// This is to set the sorting URLs for a User List of Archive Items. pascal 8-25-2016
		}


		return true;
	} // End init()

	public function setDebugging($enableDebug, $enableSolrQueryDebugging)
	{
		$this->debug = $enableDebug;
		$this->debugSolrQuery = $enableDebug && $enableSolrQueryDebugging;
		$this->getIndexEngine()->setDebugging($enableDebug, $enableSolrQueryDebugging);
	}

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
		$this->searchTerms[] = [
			'index' => $this->defaultIndex,
			'lookfor' => ""
		];

		return true;
	}

	public function getFullSearchUrl()
	{
		return isset($this->indexEngine->fullSearchUrl) ? $this->indexEngine->fullSearchUrl : 'Unknown';
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
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use on a user's "favorites" page.
	 *
	 * @access  public
	 * @param object $user User object owning tag/note metadata.
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @param array $IDList optional list of IDs to re-order the archive Objects by (ie User List sorts)
	 * @param bool $isMixedUserList Used to correctly number items in a list of mixed content (eg catalog & archive content)
	 * @return array Array of HTML chunks for individual records.
	 */
	public function getResultListHTML($user, $listId = null, $allowEdit = true, $IDList = null, $isMixedUserList = false)
	{
		global $interface;
		$html = array();

		if ($IDList) {
			//Reorder the documents based on the list of id's
			//TODO: taken from Solr.php (May need to adjust for Islandora
			$x = 0;
			foreach ($IDList as $listPosition => $currentId) {
				// use $IDList as the order guide for the html
				$current = null; // empty out in case we don't find the matching record
				foreach ($this->indexResult['response']['docs'] as $docIndex => $doc) {
					if ($doc['PID'] == $currentId) {
						$current = &$this->indexResult['response']['docs'][$docIndex];
						break;
					}
				}
				if (empty($current)) {
					continue; // In the case the record wasn't found, move on to the next record
				} else {
					if ($isMixedUserList) {
						$interface->assign('recordIndex', $listPosition + 1);
						$interface->assign('resultIndex', $listPosition + 1 + (($this->page - 1) * $this->limit));
					} else {
						$interface->assign('recordIndex', $x + 1);
						$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
					}
					if (!$this->debug) {
						unset($current['explain']);
						unset($current['score']);
					}
					/** @var IslandoraRecordDriver $record */
					$record = RecordDriverFactory::initRecordDriver($current);
					if ($isMixedUserList) {
						$html[$listPosition] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
					} else {
						$html[] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
						$x++;
					}
				}
			}
		} else {
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				$current = &$this->indexResult['response']['docs'][$x];
				/** @var IslandoraRecordDriver $record */
				$record = RecordDriverFactory::initRecordDriver($current);
				$html[] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
			}
		}
		return $html;
	}

	/**
	 * Return the record set from the search results.
	 *
	 * @access  public
	 * @return  array   recordSet
	 */
	public function getResultRecordSet()
	{
		$recordSet = $this->indexResult['response']['docs'];
		foreach ($recordSet as $key => $record) {
			// Additional Information for Emailing a list of Archive Objects
			/** @var IslandoraRecordDriver $recordDriver */
			$recordDriver = RecordDriverFactory::initRecordDriver($record);
			$record['url'] = $recordDriver->getLinkUrl();
			$record['format'] = $recordDriver->getFormat();

			$recordSet[$key] = $record;
		}
		return $recordSet;
	}

	/**
	 * @param array $orderedListOfIDs Use the index of the matched ID as the index of the resulting array of summary data (for later merging)
	 * @return array
	 */
	public function getTitleSummaryInformation($orderedListOfIDs = array())
	{
		$titleSummaries = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!($record instanceof AspenError)) {
				if (!empty($orderedListOfIDs)) {
					$position = array_search($current['PID'], $orderedListOfIDs);
					if ($position !== false) {
						$titleSummaries[$position] = $record->getSummaryInformation();
					}
				} else {
					$titleSummaries[] = $record->getSummaryInformation();
				}
			} else {
				$titleSummaries[] = "Unable to find record";
			}
		}
		return $titleSummaries;
	}


	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getCombinedResultHTML()
	{
		global $interface;

		$html = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];

			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			/** @var IslandoraRecordDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!($record instanceof AspenError)) {
				$interface->assign('recordDriver', $record);
				$html[] = $interface->fetch($record->getCombinedResult($this->view));
			} else {
				$html[] = "Unable to find record";
			}
		}
		return $html;
	}

	/**
	 * Set an overriding array of archive PIDs.
	 *
	 * @access  public
	 * @param array $ids archive PIDs to load
	 */
	public function setQueryIDs($ids)
	{
		$quoteIDs = function ($id) {
			return "\"$id\"";
		};
		$ids = array_map($quoteIDs, $ids);
		$this->query = 'PID:(' . implode(' OR ', $ids) . ')';
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
	 * Set an overriding number of facet values to return
	 *
	 * @access  public
	 * @param int $newLimit Number of facet values to return
	 */
	public function setFacetLimit($newLimit)
	{
		$this->facetLimit = $newLimit;
	}

	/**
	 * Set an overriding number of facet values to return
	 *
	 * @access  public
	 * @param int $newOffset Offset to return
	 */
	public function setFacetOffset($newOffset)
	{
		$this->facetLimit = $newOffset;
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
		if ($this->searchType == 'list') {
			return '/MyAccount/MyList/' .
				urlencode($_GET['id']) . '?';
		}
		// Base URL is different for author searches:
		return '/Archive/Results?';
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
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
	{
		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendations module appropriate to the current search:
		if ($recommendations) {
			$this->initRecommendations();
		}

		// Build Query
		if ($preventQueryModification) {
			$query = $search[0]['lookfor'];
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

		if ($this->applyStandardFilters) {
			$filterQuery = array_merge($filterQuery, $this->getStandardFilters());
		}

		//Remove any empty filters if we get them
		//(typically happens when a subdomain has a function disabled that is enabled in the main scope)
		foreach ($this->filterList as $field => $filter) {
			if (empty ($field)) {
				unset($this->filterList[$field]);
			}
		}
		foreach ($this->filterList as $field => $filter) {
			if (is_numeric($field)) {
				//This is a complex filter with ANDs and/or ORs
				$filterQuery[] = $filter[0];
			} else {
				foreach ($filter as $value) {
					// Special case -- allow trailing wildcards:
					if (substr($value, -1) == '*') {
						$filterQuery[] = "$field:$value";
					} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)) {
						$filterQuery[] = "$field:$value";
					} else {
						if (!empty($value)) {
							$filterQuery[] = "$field:\"$value\"";
						}
					}
				}
			}
		}

		// If we are only searching one field use the DisMax handler
		//    for that field. If left at null let solr take care of it
		if (count($search) == 1 && isset($search[0]['index'])) {
			$this->index = $search[0]['index'];
		}

		// Build a list of facets we want from the index
		$facetSet = array();
		$facetConfig = $this->getFacetConfig();
		if (!empty($facetConfig)) {
			$facetSet['limit'] = $this->facetLimit;
			foreach ($facetConfig as $facetField => $facetName) {
				$facetSet['field'][] = $facetField;
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
		$pingResult = $this->indexEngine->pingServer(false);
		if ($pingResult == "false" || $pingResult == false) {
			AspenError::raiseError('The archive server is currently unavailable.  Please try your search again in a few minutes.');
		}
		$this->indexResult = $this->indexEngine->search(
			$this->query,      // Query string
			$this->index,      // DisMax Handler
			$filterQuery,      // Filter query
			$recordStart,      // Starting record
			$this->limit,      // Records per page
			$facetSet,         // Fields to facet on
			$spellcheck,       // Spellcheck query
			$this->dictionary, // Spellcheck dictionary
			$finalSort,        // Field to sort on
			$this->fields,     // Fields to return
			$this->method,     // HTTP Request method
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
				/** @var RecommendationInterface $current */
				foreach ($currentSet as $current) {
					$current->process();
				}
			}
		}

		// Return the result set
		return $this->indexResult;
	}

	/**
	 * Return an array structure containing all current filters
	 *    and urls to remove them.
	 *
	 * @access  public
	 * @return  array    Field, values and removal urls
	 */
	public function getFilterList()
	{
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$list = array();
		// Loop through all the current filter fields
		foreach ($this->filterList as $field => $values) {
			// and each value currently used for that field
			$translate = in_array($field, $this->translatedFacets);
			$lookupPid = in_array($field, $this->pidFacets);
			$namespaceLookup = $field == 'namespace_s';
			foreach ($values as $value) {
				// Add to the list unless it's in the list of fields to skip:
				if (!in_array($field, $skipList)) {
					$facetLabel = $this->getFacetLabel($field);
					if ($namespaceLookup) {
						$tmpLibrary = new Library();
						$tmpLibrary->archiveNamespace = $value;
						if ($tmpLibrary->find(true)) {
							$display = $tmpLibrary->displayName;
						}
					} elseif ($lookupPid) {
						$pid = str_replace('info:fedora/', '', $value);
						if ($field == 'RELS_EXT_isMemberOfCollection_uri_ms') {
							$okToShow = $this->showCollectionAsFacet($pid);
						} else {
							$okToShow = true;
						}
						if ($okToShow) {
							$display = $fedoraUtils->getObjectLabel($pid);
							if ($display == 'Invalid Object') {
								continue;
							}
						} else {
							continue;
						}
					} elseif ($translate) {
						$display = translate($value);
					} else {
						$display = $value;
					}

					$list[$facetLabel][] = array(
						'value' => $value,     // raw value for use with Solr
						'display' => $display,   // version to display to user
						'field' => $field,
						'removalUrl' => $this->renderLinkWithoutFilter("$field:$value")
					);
				}
			}
		}
		return $list;
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
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->getFacetConfig();
		}

		// Start building the facet list:
		$list = array();

		// If we have no facets to process, give up now
		if (!isset($this->indexResult['facet_counts']) || (!is_array($this->indexResult['facet_counts']['facet_fields']) && !is_array($this->indexResult['facet_counts']['facet_dates']))) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		$allFacets = array_merge($this->indexResult['facet_counts']['facet_fields'], $this->indexResult['facet_counts']['facet_dates']);
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
			$translate = in_array($field, $this->translatedFacets);
			$lookupPid = in_array($field, $this->pidFacets);
			$namespaceLookup = $field == 'namespace_s';

			// Loop through values:
			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = array();
				$currentSettings['value'] = $facet[0];
				if ($namespaceLookup) {
					$tmpLibrary = new Library();
					$tmpLibrary->archiveNamespace = $facet[0];
					if ($tmpLibrary->find(true)) {
						$currentSettings['display'] = $tmpLibrary->displayName;
					}
				} elseif ($lookupPid) {
					$pid = str_replace('info:fedora/', '', $facet[0]);
					if ($field == 'RELS_EXT_isMemberOfCollection_uri_ms') {
						$okToShow = $this->showCollectionAsFacet($pid);
					} else {
						$okToShow = true;
					}

					if ($okToShow) {
						$currentSettings['display'] = $fedoraUtils->getObjectLabel($pid);
						if ($currentSettings['display'] == 'Invalid Object') {
							continue;
						}
					} else {
						continue;
					}

				} elseif ($translate) {
					$currentSettings['display'] = translate($facet[0]);
				} else {
					$currentSettings['display'] = $facet[0];
				}
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
	 */
	public function buildExcel($result = null)
	{
		//TODO: Create an Excel sheet with the results
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string $id The document to retrieve from Solr
	 * @access  public
	 * @return  array               The requested resource
	 * @throws  object              PEAR Error
	 */
	function getRecord($id)
	{
		return $this->indexEngine->getRecord($id);
	}

	protected $params;

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
		if (isset($_REQUEST['islandoraType'])) {
			$params[] = 'islandoraType=' . $_REQUEST['islandoraType'];
		} else {
			$params[] = 'islandoraType=' . $this->defaultIndex;
		}
		return $params;
	}

	/**
	 * Get an array of filters that should always be applied based on the library
	 *
	 * @return array
	 */
	private function getStandardFilters()
	{
		$filters = array();
		global $library;
		//Make sure we have MODS data
		$filters[] = "fedora_datastreams_ms:MODS";
		if ($library->hideAllCollectionsFromOtherLibraries && $library->archiveNamespace) {
			$filters[] = "RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/{$library->archiveNamespace}\\:*
			  OR RELS_EXT_isMemberOf_uri_ms:info\\:fedora/{$library->archiveNamespace}\\:*
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:events
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:organizations
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:people
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:places
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:families";
		}
		if ($library->collectionsToHide) {
			$collectionsToHide = explode("\r\n", $library->collectionsToHide);
			$filter = '';
			foreach ($collectionsToHide as $collection) {
				if (strlen($filter) > 0) {
					$filter .= ' AND ';
				}
				$filter .= "!ancestors_ms:\"{$collection}\"";
			}
			$filters[] = $filter;
		}
		require_once ROOT_DIR . '/sys/ArchivePrivateCollection.php';
		$privateCollectionsObj = new ArchivePrivateCollection();
		if ($privateCollectionsObj->find(true)) {
			$filter = '';
			$privateCollections = explode("\r\n", $privateCollectionsObj->privateCollections);
			foreach ($privateCollections as $privateCollection) {
				$privateCollection = trim($privateCollection);
				if (strlen($privateCollection) > 0) {
					if (strlen($library->archiveNamespace) == 0 || strpos($privateCollection, $library->archiveNamespace) !== 0) {
						if (strlen($filter) > 0) {
							$filter .= ' AND ';
						}
						$filter .= "!ancestors_ms:\"{$privateCollection}\"";
					}
				}

			}
			if (strlen($filter) > 0) {
				$filters[] = $filter;
			}
		}

		if ($library->objectsToHide) {
			$objectsToHide = explode("\r\n", $library->objectsToHide);
			$filter = '';
			foreach ($objectsToHide as $objectPID) {
				if (strlen($filter) > 0) {
					$filter .= ' AND ';
				}
				$filter .= "!PID:\"$objectPID\"";
			}
			$filters[] = $filter;
		}
		if ($library->archiveNamespace != 'islandora') {
			$filters[] = "!PID:islandora\\:*";
		}
		$filters[] = "!PID:demo\\:*";
		$filters[] = "!PID:testCollection\\:*";
		$filters[] = "!PID:testcollection\\:*";
		$filters[] = "!PID:marmot\\:*";
		$filters[] = "!PID:ssb\\:*";
		$filters[] = "!PID:mandala\\:*";
		$filters[] = "!RELS_EXT_hasModel_uri_s:info\\:fedora/islandora\\:newspaperIssueCModel";
		$filters[] = "!RELS_EXT_hasModel_uri_s:info\\:fedora/ir\\:thesisCModel";
		$filters[] = "!RELS_EXT_hasModel_uri_s:info\\:fedora/ir\\:citationCModel";

		global $configArray;
		if ($configArray['Site']['isProduction']) {
			$filters[] = "!mods_extension_marmotLocal_pikaOptions_includeInPika_ms:(no OR testOnly)";
		} else {
			$filters[] = "!mods_extension_marmotLocal_pikaOptions_includeInPika_ms:no";
		}
		return $filters;
	}

	public function setPrimarySearch($flag)
	{
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function addFieldsToReturn($fields)
	{
		$this->fields .= ',' . implode(',', $fields);
	}

	public function setApplyStandardFilters($flag)
	{
		$this->applyStandardFilters = $flag;
	}

	// Second Attempt to handle Exhibit Navigation
	public function getNextPrevLinks($searchId = null, $recordIndex = null, $page = null, $preventQueryModification = false)
	{
		global $interface;
		global $timer;
		//Setup next and previous links based on the search results.
		if (is_null($searchId)) {
			if (isset($_REQUEST['searchId']) && ctype_digit($_REQUEST['searchId'])) {
				$searchId = $_REQUEST['searchId'];
			}
		}
		if (is_null($recordIndex)) {
			if (isset($_REQUEST['recordIndex']) && ctype_digit($_REQUEST['recordIndex'])) {
				$recordIndex = $_REQUEST['recordIndex'];
			} else {
				$recordIndex = 0; // TODO: what is a good default value
			}
		}
		if ($searchId) {
			//rerun the search
			$interface->assign('searchId', $searchId);
			if (is_null($page)) {
				$page = isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			}
			$interface->assign('page', $page);

			$s = new SearchEntry();
			if ($s->get($searchId)) {
				$minSO = unserialize($s->search_object);
				/** @var SearchObject_IslandoraSearcher $searchObject */
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($page);
				$searchObject->setLimit(24); // Assume 24 for Archive Searches; or // TODO: Add pagelimit to saved search?
				//Run the search
				$result = $searchObject->processSearch(true, false, $preventQueryModification); // prevent query modification needed for Map Exhibits

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $recordIndex - 1;
				$recordsPerPage = $searchObject->getLimit();
				$adjustedResultIndex = $currentResultIndex - ($recordsPerPage * ($page - 1));

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0) {
					//Need to run a search for the previous page
					$interface->assign('previousPage', $page - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($page - 1);
					$previousSearchObject->processSearch(true, false, $preventQueryModification);
					$previousResults = $previousSearchObject->getResultRecordSet();
				} else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()) {
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $page + 1);
					$nextSearchObject->setPage($page + 1);
					$nextSearchObject->processSearch(true, false, $preventQueryModification);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				if ($result instanceof AspenError) {
					//If we get an error excuting the search, just eat it for now.
				} else {
					if ($searchObject->getResultTotal() < 1) {
						//No results found
					} else {
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0) {
							if (isset($previousResults)) {
								$previousRecord = $previousResults[count($previousResults) - 1];
							} else {
								$previousId = $adjustedResultIndex - 1;
								if (isset($recordSet[$previousId])) {
									$previousRecord = $recordSet[$previousId];
								}
							}

							//Convert back to 1 based index
							if (isset($previousRecord)) {
								$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
								if (key_exists('PID', $previousRecord)) {
									$interface->assign('previousType', 'Archive');
									$interface->assign('previousUrl', $previousRecord['url']);
									$interface->assign('previousTitle', $previousRecord['fgs_label_s']);
								}
							}
						}
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()) {

							if (isset($nextResults)) {
								$nextRecord = $nextResults[0];
							} else {
								$nextRecordIndex = $adjustedResultIndex + 1;
								if (isset($recordSet[$nextRecordIndex])) {
									$nextRecord = $recordSet[$nextRecordIndex];
								}
							}
							//Convert back to 1 based index
							$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
							if (isset($nextRecord)) {
								if (key_exists('PID', $nextRecord)) {
									$interface->assign('nextType', 'Archive');
									$interface->assign('nextUrl', $nextRecord['url']);
									$interface->assign('nextTitle', $nextRecord['fgs_label_s']);
								}
							}
						}

					}
				}
			}
			$timer->logTime('Got next/previous links');
		}
	}


	public function deminify($minified)
	{
		// Clean the object
		$this->purge();

		// Most values will transfer without changes
		$this->searchId = $minified->id;
		$this->initTime = $minified->i;
		$this->queryTime = $minified->s;
		$this->resultsTotal = $minified->r;
		$this->filterList = $minified->f;
		$this->searchType = $minified->ty;
		$this->sort = $minified->sr;
		$this->hiddenFilters = $minified->hf;
		$this->facetConfig = $minified->fc;

		// Search terms, we need to expand keys
		$tempTerms = $minified->t;
		foreach ($tempTerms as $term) {
			$newTerm = array();
			foreach ($term as $k => $v) {
				switch ($k) {
					case 'j' :
						$newTerm['join'] = $v;
						break;
					case 'i' :
						$newTerm['index'] = $v;
						break;
					case 'l' :
						$newTerm['lookfor'] = $v;
						break;
					case 'g' :
						$newTerm['group'] = array();
						foreach ($v as $line) {
							$search = array();
							foreach ($line as $k2 => $v2) {
								switch ($k2) {
									case 'b' :
										$search['bool'] = $v2;
										break;
									case 'f' :
										$search['field'] = $v2;
										break;
									case 'l' :
										$search['lookfor'] = $v2;
										break;
								}
							}
							$newTerm['group'][] = $search;
						}
						break;
				}
			}
			$this->searchTerms[] = $newTerm;
		}
	}

	private function showCollectionAsFacet($pid)
	{
		global $library;
		global $fedoraUtils;
		$namespace = substr($pid, 0, strpos($pid, ':'));
		if ($namespace == 'marmot') {
			$okToShow = true;
			return $okToShow;
		} elseif ($library->hideAllCollectionsFromOtherLibraries && $library->archiveNamespace) {
			$okToShow = ($namespace == $library->archiveNamespace);
		} elseif (strlen($library->collectionsToHide) > 0) {
			$okToShow = strpos($library->collectionsToHide, $pid) === false;
		} else {
			$okToShow = true;
		}
		if ($okToShow) {
			$fedoraUtils = FedoraUtils::getInstance();
			$archiveObject = $fedoraUtils->getObject($pid);
			if ($archiveObject == null) {
				$okToShow = true; //These are things like People, Places, Events, Large Image Collection, etc
			} else if (!$fedoraUtils->isObjectValidForPika($archiveObject)) {
				$okToShow = false;
			}
		}
		return $okToShow;
	}

	public function pingServer($failOnError = true)
	{
		return $this->indexEngine->pingServer($failOnError);
	}

	public function getSearchIndexes()
	{
		return [
			'IslandoraKeyword' => 'Keyword',
			'IslandoraTitle' => 'Title'
		];
	}

	public function getRecordDriverForResult($record)
	{
		return RecordDriverFactory::initRecordDriver($record);
	}

	public function getSearchesFile()
	{
		return 'islandoraSearches';
	}
}