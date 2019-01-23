<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/sys/Solr.php';
require_once ROOT_DIR . '/sys/SearchObject/Base.php';
require_once ROOT_DIR . '/RecordDrivers/Factory.php';

/**
 * Search Object class
 *
 * This is the default implementation of the SearchObjectBase class, providing the
 * Solr-driven functionality used by VuFind's standard Search module.
 */
class SearchObject_Islandora extends SearchObject_Base
{
	// Publicly viewable version
	private $publicQuery = null;
	// Facets
	private $facetLimit = 30;
	private $facetOffset = null;
	private $facetPrefix = null;
	private $facetSort = null;

	// Index
	private $index = null;
	// Field List
	//private $fields = '*,score';
	private $fields = 'PID,fgs_label_s,dc.title,mods_abstract_s,mods_genre_s,RELS_EXT_hasModel_uri_s,dateCreated,score,fgs_createdDate_dt,fgs_lastModifiedDate_dt';
	// HTTP Method
	//    private $method = HTTP_REQUEST_METHOD_GET;
	private $method = HTTP_REQUEST_METHOD_POST;
	// Result
	private $indexResult;

	//Whether or not filters should be applied
	private $applyStandardFilters = true;

	// OTHER VARIABLES
	// Index
	/** @var Solr */
	private $indexEngine = null;
	// Facets information
	private $allFacetSettings = array();    // loaded from facets.ini

	// Spelling
	private $spellingLimit = 3;
	private $spellQuery    = array();
	private $dictionary    = 'default';
	private $spellSimple   = false;
	private $spellSkipNumeric = true;

	// Display Modes //
	public $viewOptions = array('list', 'covers');

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
		require_once ROOT_DIR . "/sys/Solr.php";
		$this->searchType = 'islandora';
		$this->basicSearchType = 'islandora';
		// Initialise the index
		$this->indexEngine = new Solr($configArray['Islandora']['solrUrl'], isset($configArray['Islandora']['solrCore']) ? $configArray['Islandora']['solrCore'] : 'islandora');
		$timer->logTime('Created Index Engine for Islandora');

		//Make sure to turn off sharding for islandora
		$this->indexEngine->setShards(array());

		// Get default facet settings
		$this->allFacetSettings = getExtraConfigArray('islandoraFacets');
		$this->facetConfig = array();
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}
		$translatedFacets = $this->getFacetSetting('Advanced_Settings', 'translated_facets');
		if (is_array($translatedFacets)) {
			$this->translatedFacets = $translatedFacets;
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
			$this->basicTypes = $searchSettings['Basic_Searches'];
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

		// Load Spelling preferences
		$this->spellcheck    = $configArray['Spelling']['enabled'];
		$this->spellingLimit = $configArray['Spelling']['limit'];
		$this->spellSimple   = $configArray['Spelling']['simple'];
		$this->spellSkipNumeric = isset($configArray['Spelling']['skip_numeric']) ?
		$configArray['Spelling']['skip_numeric'] : true;

		// Debugging
		$this->indexEngine->debug = $this->debug;

		$this->recommendIni = 'islandoraSearches';

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
	 * @return  boolean
	 */
	public function init()
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
		} else if (PEAR_Singleton::isError($restored)) {
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
		$this->facetConfig = isset($this->allFacetSettings['Advanced']) ? $this->allFacetSettings['Advanced'] : array();
		$facetLimit = $this->getFacetSetting('Advanced_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Spellcheck is not needed for facet data!
		$this->spellcheck = false;

		//********************
		// Basic Search logic
		$this->searchTerms[] = array(
            'index'   => $this->defaultIndex,
            'lookfor' => ""
            );

            return true;
	}

	/**
	 * Return the specified setting from the facets.ini file.
	 *
	 * @access  public
	 * @param   string $section   The section of the facets.ini file to look at.
	 * @param   string $setting   The setting within the specified file to return.
	 * @return  string    The value of the setting (blank if none).
	 */
	public function getFacetSetting($section, $setting)
	{
		return isset($this->allFacetSettings[$section][$setting]) ?
		$this->allFacetSettings[$section][$setting] : '';
	}

	public function getFullSearchUrl() {
		return isset($this->indexEngine->fullSearchUrl) ? $this->indexEngine->fullSearchUrl : 'Unknown';
	}

	/**
	 * Used during repeated deminification (such as search history).
	 *   To scrub fields populated above.
	 *
	 * @access  private
	 */
	protected function purge()
	{
		// Call standard purge:
		parent::purge();

		// Make some Solr-specific adjustments:
		$this->query        = null;
		$this->publicQuery  = null;
	}

	/**
	 * Switch the spelling dictionary to basic
	 *
	 * @access  public
	 */
	public function useBasicDictionary() {
		$this->dictionary = 'basicSpell';
	}

	public function getQuery()          {return $this->query;}
	public function getIndexEngine()    {return $this->indexEngine;}

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
	 * @param   object $user User object owning tag/note metadata.
	 * @param   int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool $allowEdit Should we display edit controls?
	 * @param   array   $IDList     optional list of IDs to re-order the archive Objects by (ie User List sorts)
	 * @param   bool $isMixedUserList Used to correctly number items in a list of mixed content (eg catalog & archive content)
	 * @return array Array of HTML chunks for individual records.
	 */
	public function getResultListHTML($user, $listId = null, $allowEdit = true, $IDList = null, $isMixedUserList = false)
	{
		global $interface;
		$html = array();

		if ($IDList){
			//Reorder the documents based on the list of id's
			//TODO: taken from Solr.php (May need to adjust for Islandora
			$x = 0;
			foreach ($IDList as $listPosition => $currentId){
				// use $IDList as the order guide for the html
				$current = null; // empty out in case we don't find the matching record
				foreach ($this->indexResult['response']['docs'] as $index => $doc) {
					if ($doc['PID'] == $currentId) {
						$current = & $this->indexResult['response']['docs'][$index];
						break;
					}
				}
				if (empty($current)) {
					continue; // In the case the record wasn't found, move on to the next record
				}else {
					if ($isMixedUserList) {
						$interface->assign('recordIndex', $listPosition + 1);
						$interface->assign('resultIndex', $listPosition + 1 + (($this->page - 1) * $this->limit));
					} else {
						$interface->assign('recordIndex', $x + 1);
						$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
					}
					if (!$this->debug){
						unset($current['explain']);
						unset($current['score']);
					}
					/** @var IslandoraDriver $record */
					$record = RecordDriverFactory::initRecordDriver($current);
					if ($isMixedUserList) {
						$html[$listPosition] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
					} else {
						$html[] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
						$x++;
					}
				}
			}
		}else{
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			$current = &$this->indexResult['response']['docs'][$x];
			$record  = RecordDriverFactory::initRecordDriver($current);
			$html[]  = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
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
		foreach ($recordSet as $key => $record){
			// Additional Information for Emailing a list of Archive Objects
			$recordDriver = RecordDriverFactory::initRecordDriver($record);
			$record['url']    = $recordDriver->getLinkUrl();
			$record['format'] = $recordDriver->getFormat();

			$recordSet[$key] = $record;
		}
		return $recordSet;
	}

	/**
	 * @param array $orderedListOfIDs  Use the index of the matched ID as the index of the resulting array of ListWidget data (for later merging)
	 * @return array
	 */
	public function getListWidgetTitles($orderedListOfIDs = array()){
		$widgetTitles = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = & $this->indexResult['response']['docs'][$x];
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!PEAR_Singleton::isError($record)){
				if (method_exists($record, 'getListWidgetTitle')){
					if (!empty($orderedListOfIDs)) {
						$position = array_search($current['PID'], $orderedListOfIDs);
						if ($position !== false) {
							$widgetTitles[$position] = $record->getListWidgetTitle();
						}
					} else {
						$widgetTitles[] = $record->getListWidgetTitle();
					}
				}else{
					$widgetTitles[] = 'List Widget Item not available';
				}
			}else{
				$widgetTitles[] = "Unable to find record";
			}
		}
		return $widgetTitles;
	}


	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML()
	{
		global $interface;

		$html = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = & $this->indexResult['response']['docs'][$x];

			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!PEAR_Singleton::isError($record)) {
				$interface->assign('recordDriver', $record);
				$html[] = $interface->fetch($record->getSearchResult($this->view));
			} else {
				$html[] = "Unable to find record";
			}
		}
		return $html;
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
			$current = & $this->indexResult['response']['docs'][$x];

			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			/** @var IslandoraDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!PEAR_Singleton::isError($record)) {
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
	 * @param   array   $ids        archive PIDs to load
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
	 * @param   string  $newQuery   Query string
	 */
	public function setQueryString($newQuery)
	{
		$this->query = $newQuery;
	}

	/**
	 * Set an overriding facet sort order.
	 *
	 * @access  public
	 * @param   string  $newSort   Sort string
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
	 * @param   int $newLimit   Number of facet values to return
	 */
	public function setFacetLimit($newLimit)
	{
		$this->facetLimit = $newLimit;
	}

	/**
	 * Set an overriding number of facet values to return
	 *
	 * @access  public
	 * @param   int $newOffset  Offset to return
	 */
	public function setFacetOffset($newOffset)
	{
		$this->facetLimit = $newOffset;
	}

	/**
	 * Add a prefix to facet requirements. Serves to
	 *    limits facet sets to smaller subsets.
	 *
	 *  eg. all facet data starting with 'R'
	 *
	 * @access  public
	 * @param   string  $prefix   Data for prefix
	 */
	public function addFacetPrefix($prefix)
	{
		$this->facetPrefix = $prefix;
	}

	/**
	 * Turn the list of spelling suggestions into an array of urls
	 *   for on-screen use to implement the suggestions.
	 *
	 * @access  public
	 * @return  array     Spelling suggestion data arrays
	 */
	public function getSpellingSuggestions()
	{
		global $configArray;

		$returnArray = array();
		if (count($this->suggestions) == 0) return $returnArray;
		$tokens = $this->spellingTokens($this->buildSpellingQuery());

		foreach ($this->suggestions as $term => $details) {
			// Find out if our suggestion is part of a token
			$inToken = false;
			$targetTerm = "";
			foreach ($tokens as $token) {
				// TODO - Do we need stricter matching here?
				//   Similar to that in replaceSearchTerm()?
				if (stripos($token, $term) !== false) {
					$inToken = true;
					// We need to replace the whole token
					$targetTerm = $token;
					// Go and replace this token
					$returnArray = $this->doSpellingReplace($term,
					$targetTerm, $inToken, $details, $returnArray);
				}
			}
			// If no tokens we found, just look
			//    for the suggestion 'as is'
			if ($targetTerm == "") {
				$targetTerm = $term;
				$returnArray = $this->doSpellingReplace($term,
				$targetTerm, $inToken, $details, $returnArray);
			}
		}
		return $returnArray;
	}

	/**
	 * Process one instance of a spelling replacement and modify the return
	 *   data structure with the details of what was done.
	 *
	 * @access  public
	 * @param   string   $term        The actually term we're replacing
	 * @param   string   $targetTerm  The term above, or the token it is inside
	 * @param   boolean  $inToken     Flag for whether the token or term is used
	 * @param   array    $details     The spelling suggestions
	 * @param   array    $returnArray Return data structure so far
	 * @return  array    $returnArray modified
	 */
	private function doSpellingReplace($term, $targetTerm, $inToken, $details, $returnArray)
	{
		global $configArray;

		$returnArray[$targetTerm]['freq'] = $details['freq'];
		foreach ($details['suggestions'] as $word => $freq) {
			// If the suggested word is part of a token
			if ($inToken) {
				// We need to make sure we replace the whole token
				$replacement = str_replace($term, $word, $targetTerm);
			} else {
				$replacement = $word;
			}
			//  Do we need to show the whole, modified query?
			if ($configArray['Spelling']['phrase']) {
				$label = $this->getDisplayQueryWithReplacedTerm($targetTerm, $replacement);
			} else {
				$label = $replacement;
			}
			// Basic spelling suggestion data
			$returnArray[$targetTerm]['suggestions'][$label] = array(
                'freq'        => $freq,
                'replace_url' => $this->renderLinkWithReplacedTerm($targetTerm, $replacement)
			);
			// Only generate expansions if enabled in config
			if ($configArray['Spelling']['expand']) {
				// Parentheses differ for shingles
				if (strstr($targetTerm, " ") !== false) {
					$replacement = "(($targetTerm) OR ($replacement))";
				} else {
					$replacement = "($targetTerm OR $replacement)";
				}
				$returnArray[$targetTerm]['suggestions'][$label]['expand_url'] =
				$this->renderLinkWithReplacedTerm($targetTerm, $replacement);
			}
		}

		return $returnArray;
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
	 * Build a string for onscreen display showing the
	 *   query used in the search (not the filters).
	 *
	 * @access  public
	 * @return  string   user friendly version of 'query'
	 */
	public function displayQuery()
	{
		// Maybe this is a restored object...
		if ($this->query == null) {
			$this->query = $this->indexEngine->buildQuery($this->searchTerms);
		}

		// Do we need the complex answer? Advanced searches
		if ($this->searchType == $this->advancedSearchType) {
			$output = $this->buildAdvancedDisplayQuery();
			// If there is a hardcoded public query (like tags) return that
		} else if ($this->publicQuery != null) {
			$output = $this->publicQuery;
			// If we don't already have a public query, and this is a basic search
			// with case-insensitive booleans, we need to do some extra work to ensure
			// that we display the user's query back to them unmodified (i.e. without
			// capitalized Boolean operators)!
		} else if (!$this->indexEngine->hasCaseSensitiveBooleans()) {
			$output = $this->publicQuery =
			$this->indexEngine->buildQuery($this->searchTerms, true);
			// Simple answer
		} else {
			$output = $this->query;
		}

		// Empty searches will look odd to users
		if ($output == '*:*') {
			$output = "";
		}

		return $output;
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
			return $this->serverUrl . '/MyAccount/MyList/' .
			urlencode($_GET['id']) . '?';
		}
		// Base URL is different for author searches:
		return $this->serverUrl . '/Archive/Results?';
	}

	/**
	 * Get error message from index response, if any.  This will only work if
	 * processSearch was called with $returnIndexErrors set to true!
	 *
	 * @access  public
	 * @return  mixed       false if no error, error string otherwise.
	 */
	public function getIndexError()
	{
		return isset($this->indexResult['error']) ?
		$this->indexResult['error'] : false;
	}

	/**
	 * Load all recommendation settings from the relevant ini file.  Returns an
	 * associative array where the key is the location of the recommendations (top
	 * or side) and the value is the settings found in the file (which may be either
	 * a single string or an array of strings).
	 *
	 * @access  protected
	 * @return  array           associative: location (top/side) => search settings
	 */
	protected function getRecommendationSettings()
	{
		// Special hard-coded case for author module.  We should make this more
		// flexible in the future!
		// Marmot hard-coded case and use searches.ini and facets.ini instead.
		/*if ($this->searchType == 'author') {
		 return array('side' => array('SideFacets:Author'));
		 }*/

		// Use default case from parent class the rest of the time:
		return parent::getRecommendationSettings();
	}

	/**
	 * Actually process and submit the search
	 *
	 * @access  public
	 * @param   bool   $returnIndexErrors  Should we die inside the index code if
	 *                                     we encounter an error (false) or return
	 *                                     it for access via the getIndexError()
	 *                                     method (true)?
	 * @param   bool   $recommendations    Should we process recommendations along
	 *                                     with the search itself?
	 * @param   bool   $preventQueryModification   Should we allow the search engine
	 *                                             to modify the query or is it already
	 *                                             a well formatted query
	 * @return  object solr result structure (for now)
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
		if ($preventQueryModification){
			$query = $search[0]['lookfor'];
		}else{
			$query = $this->indexEngine->buildQuery($search, false);
		}

		if (PEAR_Singleton::isError($query)) {
			return $query;
		}

		// Only use the query we just built if there isn't an override in place.
		if ($this->query == null) {
			$this->query = $query;
		}

		// Define Filter Query
		$filterQuery = $this->hiddenFilters;

		if ($this->applyStandardFilters){
			$filterQuery = array_merge($filterQuery, $this->getStandardFilters());
		}

		//Remove any empty filters if we get them
		//(typically happens when a subdomain has a function disabled that is enabled in the main scope)
		foreach ($this->filterList as $field => $filter) {
			if (empty ($field)){
				unset($this->filterList[$field]);
			}
		}
		foreach ($this->filterList as $field => $filter) {
			if (is_numeric($field)){
				//This is a complex filter with ANDs and/or ORs
				$filterQuery[] = $filter[0];
			}else{
				foreach ($filter as $value) {
					// Special case -- allow trailing wildcards:
					if (substr($value, -1) == '*') {
						$filterQuery[] = "$field:$value";
					} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)){
						$filterQuery[] = "$field:$value";
					} else {
						if (!empty($value)){
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
		if (!empty($this->facetConfig)) {
			$facetSet['limit'] = $this->facetLimit;
			foreach ($this->facetConfig as $facetField => $facetName) {
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

		if (!empty($this->facetOptions)){
			$facetSet['additionalOptions'] = $this->facetOptions;
		}

		// Build our spellcheck query
		if ($this->spellcheck) {
			if ($this->spellSimple) {
				$this->useBasicDictionary();
			}
			$spellcheck = $this->buildSpellingQuery();

			// If the spellcheck query is purely numeric, skip it if
			// the appropriate setting is turned on.
			if ($this->spellSkipNumeric && is_numeric($spellcheck)) {
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
		if ($pingResult == "false" || $pingResult == false){
			PEAR_Singleton::raiseError('The archive server is currently unavailable.  Please try your search again in a few minutes.');
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
		if (isset($this->indexResult['response']['numFound'])){
			$this->resultsTotal = $this->indexResult['response']['numFound'];
		}else{
			$this->resultsTotal = 0;
		}

		// Process spelling suggestions if no index error resulted from the query
		if ($this->spellcheck && !isset($this->indexResult['error'])) {
			// Shingle dictionary
			$this->processSpelling();
			// Make sure we don't endlessly loop
			if ($this->dictionary == 'default') {
				// Expand against the basic dictionary
				$this->basicSpelling();
			}
		}

		// If extra processing is needed for recommendations, do it now:
		if ($recommendations && is_array($this->recommend)) {
			foreach($this->recommend as $currentSet) {
				foreach($currentSet as $current) {
					$current->process();
				}
			}
		}

		// Return the result set
		return $this->indexResult;
	}

	/**
	 * Adapt the search query to a spelling query
	 *
	 * @access  private
	 * @return  string    Spelling query
	 */
	private function buildSpellingQuery()
	{
		$this->spellQuery = array();
		// Basic search
		if ($this->searchType == $this->basicSearchType) {
			// Just the search query is fine
			return $this->query;

			// Advanced search
		} else {
			foreach ($this->searchTerms as $search) {
				foreach ($search['group'] as $field) {
					// Add just the search terms to the list
					$this->spellQuery[] = $field['lookfor'];
				}
			}
			// Return the list put together as a string
			return join(" ", $this->spellQuery);
		}
	}

	/**
	 * Process spelling suggestions from the results object
	 *
	 * @access  private
	 */
	private function processSpelling()
	{
		global $configArray;

		// Do nothing if spelling is disabled
		if (!$configArray['Spelling']['enabled']) {
			return;
		}

		// Do nothing if there are no suggestions
		$suggestions = isset($this->indexResult['spellcheck']['suggestions']) ?
		$this->indexResult['spellcheck']['suggestions'] : array();
		if (count($suggestions) == 0) {
			return;
		}

		// Loop through the array of search terms we have suggestions for
		$suggestionList = array();
		foreach ($suggestions as $suggestion) {
			$ourTerm = $suggestion[0];

			// Skip numeric terms if numeric suggestions are disabled
			if ($this->spellSkipNumeric && is_numeric($ourTerm)) {
				continue;
			}

			$ourHit  = $suggestion[1]['origFreq'];
			$count   = $suggestion[1]['numFound'];
			$newList = $suggestion[1]['suggestion'];

			$validTerm = true;

			// Make sure the suggestion is for a valid search term.
			// Sometimes shingling will have bridged two search fields (in
			// an advanced search) or skipped over a stopword.
			if (!$this->findSearchTerm($ourTerm)) {
				$validTerm = false;
			}

			// Unless this term had no hits
			if ($ourHit != 0) {
				// Filter out suggestions we are already using
				$newList = $this->filterSpellingTerms($newList);
			}

			// Make sure it has suggestions and is valid
			if (count($newList) > 0 && $validTerm) {
				// Did we get more suggestions then our limit?
				if ($count > $this->spellingLimit) {
					// Cut the list at the limit
					array_splice($newList, $this->spellingLimit);
				}
				$suggestionList[$ourTerm]['freq'] = $ourHit;
				// Format the list nicely
				foreach ($newList as $item) {
					if (is_array($item)) {
						$suggestionList[$ourTerm]['suggestions'][$item['word']] = $item['freq'];
					} else {
						$suggestionList[$ourTerm]['suggestions'][$item] = 0;
					}
				}
			}
		}
		$this->suggestions = $suggestionList;
	}

	/**
	 * Filter a list of spelling suggestions to remove suggestions
	 *   we are already searching for
	 *
	 * @access  private
	 * @param   array    $termList List of suggestions
	 * @return  array    Filtered list
	 */
	private function filterSpellingTerms($termList) {
		$newList = array();
		if (count($termList) == 0) return $newList;

		foreach ($termList as $term) {
			if (!$this->findSearchTerm($term['word'])) {
				$newList[] = $term;
			}
		}
		return $newList;
	}

	/**
	 * Try running spelling against the basic dictionary.
	 *   This function should ensure it doesn't return
	 *   single word suggestions that have been accounted
	 *   for in the shingle suggestions above.
	 *
	 * @access  private
	 * @return  array     Suggestions array
	 */
	private function basicSpelling()
	{
		// TODO: There might be a way to run the
		//   search against both dictionaries from
		//   inside solr. Investigate. Currently
		//   submitting a second search for this.

		// Create a new search object
		$newSearch = SearchObjectFactory::initSearchObject('Archive');
		$newSearch->deminify($this->minify());

		// Activate the basic dictionary
		$newSearch->useBasicDictionary();
		// We don't want it in the search history
		$newSearch->disableLogging();

		// Run the search
		$newSearch->processSearch();
		// Get the spelling results
		$newList = $newSearch->getRawSuggestions();

		// If there were no shingle suggestions
		if (count($this->suggestions) == 0) {
			// Just use the basic ones as provided
			$this->suggestions = $newList;

			// Otherwise
		} else {
			// For all the new suggestions
			foreach ($newList as $word => $data) {
				// Check the old suggestions
				$found = false;
				foreach ($this->suggestions as $k => $v) {
					// Make sure it wasn't part of a shingle
					//   which has been suggested at a higher
					//   level.
					$found = preg_match("/\b$word\b/", $k) ? true : $found;
				}
				if (!$found) {
					$this->suggestions[$word] = $data;
				}
			}
		}
	}

	/**
	 * Return an array structure containing all current filters
	 *    and urls to remove them.
	 *
	 * @access  public
	 * @param   bool   $excludeCheckboxFilters  Should we exclude checkbox filters
	 *                                          from the list (to be used as a
	 *                                          complement to getCheckboxFacets()).
	 * @return  array    Field, values and removal urls
	 */
	public function getFilterList($excludeCheckboxFilters = false)
	{
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		global $library;
		$fedoraUtils = FedoraUtils::getInstance();

		// Get a list of checkbox filters to skip if necessary:
		$skipList = $excludeCheckboxFilters ? array_keys($this->checkboxFacets) : array();

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
					if ($namespaceLookup){
						$tmpLibrary = new Library();
						$tmpLibrary->archiveNamespace = $value;
						if ($tmpLibrary->find(true)){
							$display = $tmpLibrary->displayName;
						}
					}elseif ($lookupPid) {
						$pid = str_replace('info:fedora/', '', $value);
						if ($field == 'RELS_EXT_isMemberOfCollection_uri_ms'){
							$okToShow = $this->showCollectionAsFacet($pid);
						}else{
							$okToShow = true;
						}
						if ($okToShow){
							$display = $fedoraUtils->getObjectLabel($pid);
							if ($display == 'Invalid Object'){
								continue;
							}
						}else{
							continue;
						}
					}elseif ($translate){
						$display = translate($value);
					}else{
						$display = $value;
					}

					$list[$facetLabel][] = array(
							'value'      => $value,     // raw value for use with Solr
							'display'    => $display,   // version to display to user
							'field'      => $field,
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
	 * @param   array   $filter         Array of field => on-screen description
	 *                                  listing all of the desired facet fields;
	 *                                  set to null to get all configured values.
	 * @param   bool    $expandingLinks If true, we will include expanding URLs
	 *                                  (i.e. get all matches for a facet, not
	 *                                  just a limit to the current search) in
	 *                                  the return array.
	 * @return  array   Facets data arrays
	 */
	public function getFacetList($filter = null, $expandingLinks = false)
	{
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->facetConfig;
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
			$list[$field]['list']  = array();

			// Should we translate values for the current facet?
			$translate = in_array($field, $this->translatedFacets);
			$lookupPid = in_array($field, $this->pidFacets);
			$namespaceLookup = $field == 'namespace_s';

			// Loop through values:
			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = array();
				$currentSettings['value'] = $facet[0];
				if ($namespaceLookup){
					$tmpLibrary = new Library();
					$tmpLibrary->archiveNamespace = $facet[0];
					if ($tmpLibrary->find(true)){
						$currentSettings['display'] = $tmpLibrary->displayName;
					}
				}elseif ($lookupPid) {
					$pid = str_replace('info:fedora/', '', $facet[0]);
					if ($field == 'RELS_EXT_isMemberOfCollection_uri_ms'){
						$okToShow = $this->showCollectionAsFacet($pid);
					}else{
						$okToShow = true;
					}

					if ($okToShow) {
						$currentSettings['display'] = $fedoraUtils->getObjectLabel($pid);
						if ($currentSettings['display'] == 'Invalid Object') {
							continue;
						}
					}else{
						continue;
					}

				}elseif ($translate){
					$currentSettings['display'] = translate($facet[0]);
				}else{
					$currentSettings['display'] = $facet[0];
				}
				$currentSettings['count'] = $facet[1];
				$currentSettings['isApplied'] = false;
				$currentSettings['url'] = $this->renderLinkWithFilter("$field:".$facet[0]);
				// If we want to have expanding links (all values matching the facet)
				// in addition to limiting links (filter current search with facet),
				// do some extra work:
				if ($expandingLinks) {
					$currentSettings['expandUrl'] = $this->getExpandingFacetLink($field, $facet[0]);
				}

				// Is this field a current filter?
				if (in_array($field, array_keys($this->filterList))) {
					// and is this value a selected filter?
					if (in_array($facet[0], $this->filterList[$field])) {
						$currentSettings['isApplied'] = true;
						$currentSettings['removalUrl'] =  $this->renderLinkWithoutFilter("$field:{$facet[0]}");
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
			if ($list[$field]['showAlphabetically']){
				ksort($list[$field]['list']);
			}
		}
		return $list;
	}

	/**
	 * Load all available facet settings.  This is mainly useful for showing
	 * appropriate labels when an existing search has multiple filters associated
	 * with it.
	 *
	 * @access  public
	 * @param   string      $preferredSection       Section to favor when loading
	 *                                              settings; if multiple sections
	 *                                              contain the same facet, this
	 *                                              section's description will be
	 *                                              favored.
	 */
	public function activateAllFacets($preferredSection = false)
	{
		foreach($this->allFacetSettings as $section => $values) {
			foreach($values as $key => $value) {
				$this->addFacet($key, $value);
			}
		}

		if ($preferredSection &&
		is_array($this->allFacetSettings[$preferredSection])) {
			foreach($this->allFacetSettings[$preferredSection] as $key => $value) {
				$this->addFacet($key, $value);
			}
		}
	}

	/**
	 * Turn our results into an RSS feed
	 *
	 * @access  public
	 * @public  array      $result      Existing result set (null to do new search)
	 * @return  string                  XML document
	 */
	public function buildRSS($result = null)
	{
		global $configArray;
		// XML HTTP header
		header('Content-type: text/xml', true);

		// First, get the search results if none were provided
		// (we'll go for 50 at a time)
		if (is_null($result)) {
			$this->limit = 50;
			$result = $this->processSearch(false, false);
		}

		for ($i = 0; $i < count($result['response']['docs']); $i++) {
			$current = & $this->indexResult['response']['docs'][$i];

			$record = RecordDriverFactory::initRecordDriver($current);
			if (!PEAR_Singleton::isError($record)) {
				$result['response']['docs'][$i]['recordUrl'] = $record->getLinkUrl();
				$result['response']['docs'][$i]['title_display'] = $record->getTitle();
				$image = $record->getBookcoverUrl('medium');
				$description = "<img src='$image'/> " . $record->getDescription();
				$result['response']['docs'][$i]['rss_description'] = $description;
			} else {
				$html[] = "Unable to find record";
			}
		}

		global $interface;

		// On-screen display value for our search
		$lookfor = $this->displayQuery();
		if (count($this->filterList) > 0) {
			// TODO : better display of filters
			$interface->assign('lookfor', $lookfor . " (" . translate('with filters') . ")");
		} else {
			$interface->assign('lookfor', $lookfor);
		}
		// The full url to recreate this search
		$interface->assign('searchUrl', $configArray['Site']['url']. $this->renderSearchUrl());
		// Stub of a url for a records screen
		$interface->assign('baseUrl',    $configArray['Site']['url']);

		$interface->assign('result', $result);
		return $interface->fetch('Search/rss.tpl');
	}

	/**
	 * Turn our results into an Excel document
	 *
	 * @access  public
	 * @public  array      $result      Existing result set (null to do new search)
	 * @return  string                  Excel document
	 */
	public function buildExcel($result = null)
	{
		// First, get the search results if none were provided
		// (we'll go for 50 at a time)
		if (is_null($result)) {
			$this->limit = 2000;
			$result = $this->processSearch(false, false);
		}

		// Prepare the spreadsheet
		ini_set('include_path', ini_get('include_path'.';/PHPExcel/Classes'));
		include 'PHPExcel.php';
		include 'PHPExcel/Writer/Excel2007.php';
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("Search Results");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Results');

		//Add headers to the table
		$sheet = $objPHPExcel->getActiveSheet();
		$curRow = 1;
		$curCol = 0;
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'First Name');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Last Name');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Birth Date');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Death Date');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Veteran Of');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Cemetery');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Addition');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Block');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Lot');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Grave');
		$maxColumn = $curCol -1;

		for ($i = 0; $i < count($result['response']['docs']); $i++) {
			$curDoc = $result['response']['docs'][$i];
			$curRow++;
			$curCol = 0;
			//TODO: Need to export information to Excel
		}

		for ($i = 0; $i < $maxColumn; $i++){
			$sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}

		//Output to the browser
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Results.xlsx"');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output'); //THIS DOES NOT WORK WHY?
		$objPHPExcel->disconnectWorksheets();
		unset($objPHPExcel);
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param   string  $id         The document to retrieve from Solr
	 * @access  public
	 * @throws  object              PEAR Error
	 * @return  string              The requested resource
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
		if (is_null($this->params)) {
			$params = array();
			switch ($this->searchType) {
				case 'islandora' :
				default :
					$params = parent::getSearchParams();
					if (isset($_REQUEST['islandoraType'])) {
						$params[] = 'islandoraType=' . $_REQUEST['islandoraType'];
					} else {
						$params[] = 'islandoraType=' . $this->defaultIndex;
					}
					break;
				case 'list' :
				case "favorites":
				case "list":
					$preserveParams = array(
						// for favorites/list:
						'tag', 'pagesize'
					);
					foreach ($preserveParams as $current) {
						if (isset($_GET[$current])) {
							if (is_array($_GET[$current])) {
								foreach ($_GET[$current] as $value) {
									$params[] = $current . '[]=' . urlencode($value);
								}
							} else {
								$params[] = $current . '=' . urlencode($_GET[$current]);
							}
						}
					}

			}
			$this->params = $params;
		}
		return $this->params;
	}

	/**
	 * Get an array of filters that should always be applied based on the library
	 *
	 * @return array
	 */
	private function getStandardFilters() {
		$filters = array();
		global $library;
		//Make sure we have MODS data
		$filters[] = "fedora_datastreams_ms:MODS";
		if ($library->hideAllCollectionsFromOtherLibraries && $library->archiveNamespace){
			$filters[] = "RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/{$library->archiveNamespace}\\:*
			  OR RELS_EXT_isMemberOf_uri_ms:info\\:fedora/{$library->archiveNamespace}\\:*
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:events
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:organizations
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:people
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:places
			  OR RELS_EXT_isMemberOfCollection_uri_ms:info\\:fedora/marmot\\:families";
		}
		if ($library->collectionsToHide){
			$collectionsToHide = explode("\r\n", $library->collectionsToHide);
			$filter = '';
			foreach ($collectionsToHide as $collection){
				if (strlen($filter) > 0){
					$filter .= ' AND ';
				}
				$filter .= "!ancestors_ms:\"{$collection}\"";
			}
			$filters[] = $filter;
		}
		require_once ROOT_DIR . '/sys/ArchivePrivateCollection.php';
		$privateCollectionsObj = new ArchivePrivateCollection();
		if ($privateCollectionsObj->find(true)){
			$filter = '';
			$privateCollections = explode("\r\n", $privateCollectionsObj->privateCollections);
			foreach ($privateCollections as $privateCollection){
				$privateCollection = trim($privateCollection);
				if (strlen($privateCollection) > 0){
					if (strlen($library->archiveNamespace) == 0 || strpos($privateCollection, $library->archiveNamespace) !== 0){
						if (strlen($filter) > 0){
							$filter .= ' AND ';
						}
						$filter .= "!ancestors_ms:\"{$privateCollection}\"";
					}
				}

			}
			if (strlen($filter) > 0){
				$filters[] = $filter;
			}
		}

		if ($library->objectsToHide){
			$objectsToHide = explode("\r\n", $library->objectsToHide);
			$filter = '';
			foreach ($objectsToHide as $objectPID){
				if (strlen($filter) > 0){
					$filter .= ' AND ';
				}
				$filter .= "!PID:\"$objectPID\"";
			}
			$filters[] = $filter;
		}
		if ($library->archiveNamespace != 'islandora'){
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
		}else{
			$filters[] = "!mods_extension_marmotLocal_pikaOptions_includeInPika_ms:no";
		}
		return $filters;
	}

	public function setPrimarySearch($flag){
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function addFieldsToReturn($fields){
		$this->fields .= ',' . implode(',', $fields);
	}

	public function setApplyStandardFilters($flag){
		$this->applyStandardFilters = $flag;
	}

	/**
	 * @return array
	 */
	public function getFacetConfig()
	{
		return $this->facetConfig;
	}

// Original Version
//	public function getNextPrevLinks(){
//		global $interface;
//		global $timer;
//		//Setup next and previous links based on the search results.
//		if (isset($_REQUEST['searchId']) && isset($_REQUEST['recordIndex']) && ctype_digit($_REQUEST['searchId']) && ctype_digit($_REQUEST['recordIndex'])){
//
//			//rerun the search
//			$interface->assign('searchId', $_REQUEST['searchId']);
//			$currentPage = isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ? $_REQUEST['page'] : 1;
//			$interface->assign('page', $currentPage);
//
//			$s = new SearchEntry();
//			if ($s->get($_REQUEST['searchId'])){
//				$minSO = unserialize($s->search_object);
//				$searchObject = SearchObjectFactory::deminify($minSO);
//				$searchObject->setPage($currentPage);
//				//Run the search
//				$result = $searchObject->processSearch(true, false, false);
//
//				//Check to see if we need to run a search for the next or previous page
//				$currentResultIndex = $_REQUEST['recordIndex'] - 1;
//				$recordsPerPage = $searchObject->getLimit();
//				$adjustedResultIndex = $currentResultIndex - ($recordsPerPage * ($currentPage -1));
//
//				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0){
//					//Need to run a search for the previous page
//					$interface->assign('previousPage', $currentPage - 1);
//					$previousSearchObject = clone $searchObject;
//					$previousSearchObject->setPage($currentPage - 1);
//					$previousSearchObject->processSearch(true, false, false);
//					$previousResults = $previousSearchObject->getResultRecordSet();
//				}else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()){
//					//Need to run a search for the next page
//					$nextSearchObject = clone $searchObject;
//					$interface->assign('nextPage', $currentPage + 1);
//					$nextSearchObject->setPage($currentPage + 1);
//					$nextSearchObject->processSearch(true, false, false);
//					$nextResults = $nextSearchObject->getResultRecordSet();
//				}
//
//				if (PEAR_Singleton::isError($result)) {
//					//If we get an error excuting the search, just eat it for now.
//				}else{
//					if ($searchObject->getResultTotal() < 1) {
//						//No results found
//					}else{
//						$recordSet = $searchObject->getResultRecordSet();
//						//Record set is 0 based, but we are passed a 1 based index
//						if ($currentResultIndex > 0){
//							if (isset($previousResults)){
//								$previousRecord = $previousResults[count($previousResults) -1];
//							}else{
//								$previousId = $adjustedResultIndex - 1;
//								if (isset($recordSet[$previousId])){
//									$previousRecord = $recordSet[$previousId];
//								}
//							}
//
//							//Convert back to 1 based index
//							if (isset($previousRecord)) {
//								$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
//								if (key_exists('PID', $previousRecord)) {
//									$interface->assign('previousType', 'Archive');
//									$interface->assign('previousUrl', $previousRecord['url']);
//									$interface->assign('previousTitle', $previousRecord['fgs_label_s']);
//								}
//							}
//						}
//						if ($currentResultIndex + 1 < $searchObject->getResultTotal()){
//
//							if (isset($nextResults)){
//								$nextRecord = $nextResults[0];
//							}else{
//								$nextRecordIndex = $adjustedResultIndex + 1;
//								if (isset($recordSet[$nextRecordIndex])){
//									$nextRecord = $recordSet[$nextRecordIndex];
//								}
//							}
//							//Convert back to 1 based index
//							$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
//							if (isset($nextRecord)) {
//								if (key_exists('PID', $nextRecord)) {
//									$interface->assign('nextType', 'Archive');
//									$interface->assign('nextUrl', $nextRecord['url']);
//									$interface->assign('nextTitle', $nextRecord['fgs_label_s']);
//								}
//							}
//						}
//
//					}
//				}
//			}
//			$timer->logTime('Got next/previous links');
//		}
//	}

	// Second Attempt to handle Exhibit Navigation
	public function getNextPrevLinks($searchId=null, $recordIndex=null, $page=null, $preventQueryModification = false){
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
			$interface->assign('searchId',$searchId);
			if (is_null($page)) {
				$page = isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			}
			$interface->assign('page', $page);

			$s = new SearchEntry();
			if ($s->get($searchId)){
				$minSO        = unserialize($s->search_object);
				/** @var SearchObject_Islandora $searchObject */
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($page);
				$searchObject->setLimit(24); // Assume 24 for Archive Searches; or // TODO: Add pagelimit to saved search?
				//Run the search
				$result = $searchObject->processSearch(true, false, $preventQueryModification); // prevent query modification needed for Map Exhibits

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $recordIndex - 1;
				$recordsPerPage = $searchObject->getLimit();
				$adjustedResultIndex = $currentResultIndex - ($recordsPerPage * ($page -1));

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0){
					//Need to run a search for the previous page
					$interface->assign('previousPage', $page - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($page - 1);
					$previousSearchObject->processSearch(true, false, $preventQueryModification);
					$previousResults = $previousSearchObject->getResultRecordSet();
				}else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()){
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $page + 1);
					$nextSearchObject->setPage($page + 1);
					$nextSearchObject->processSearch(true, false, $preventQueryModification);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				if (PEAR_Singleton::isError($result)) {
					//If we get an error excuting the search, just eat it for now.
				}else{
					if ($searchObject->getResultTotal() < 1) {
						//No results found
					}else{
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0){
							if (isset($previousResults)){
								$previousRecord = $previousResults[count($previousResults) -1];
							}else{
								$previousId = $adjustedResultIndex - 1;
								if (isset($recordSet[$previousId])){
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
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()){

							if (isset($nextResults)){
								$nextRecord = $nextResults[0];
							}else{
								$nextRecordIndex = $adjustedResultIndex + 1;
								if (isset($recordSet[$nextRecordIndex])){
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
		$this->searchId     = $minified->id;
		$this->initTime     = $minified->i;
		$this->queryTime    = $minified->s;
		$this->resultsTotal = $minified->r;
		$this->filterList   = $minified->f;
		$this->searchType   = $minified->ty;
		$this->sort         = $minified->sr;
		$this->hiddenFilters= $minified->hf;
		$this->facetConfig  = $minified->fc;

		// Search terms, we need to expand keys
		$tempTerms = $minified->t;
		foreach ($tempTerms as $term) {
			$newTerm = array();
			foreach ($term as $k => $v) {
				switch ($k) {
					case 'j' :  $newTerm['join']    = $v; break;
					case 'i' :  $newTerm['index']   = $v; break;
					case 'l' :  $newTerm['lookfor'] = $v; break;
					case 'g' :
						$newTerm['group'] = array();
						foreach ($v as $line) {
							$search = array();
							foreach ($line as $k2 => $v2) {
								switch ($k2) {
									case 'b' :  $search['bool']    = $v2; break;
									case 'f' :  $search['field']   = $v2; break;
									case 'l' :  $search['lookfor'] = $v2; break;
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

	private function showCollectionAsFacet($pid){
		global $library;
		global $fedoraUtils;
		$namespace = substr($pid, 0, strpos($pid, ':'));
		if ($namespace == 'marmot'){
			$okToShow = true;
			return $okToShow;
		}elseif ($library->hideAllCollectionsFromOtherLibraries && $library->archiveNamespace) {
			$okToShow = ($namespace == $library->archiveNamespace);
		}elseif (strlen($library->collectionsToHide) > 0){
			$okToShow = strpos($library->collectionsToHide, $pid) === false;
		}else{
			$okToShow = true;
		}
		if ($okToShow){
			$fedoraUtils = FedoraUtils::getInstance();
			$archiveObject = $fedoraUtils->getObject($pid);
			if ($archiveObject == null){
				$okToShow = true; //These are things like People, Places, Events, Large Image Collection, etc
			}else if (!$fedoraUtils->isObjectValidForPika($archiveObject)){
				$okToShow = false;
			}
		}
		return $okToShow;
	}

	public function pingServer($failOnError = true){
		return $this->indexEngine->pingServer($failOnError);
	}
}