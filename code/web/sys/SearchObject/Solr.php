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
require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';

/**
 * Search Object class
 *
 * This is the default implementation of the SearchObjectBase class, providing the
 * Solr-driven functionality used by VuFind's standard Search module.
 */
class SearchObject_Solr extends SearchObject_Base
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
	public static $fields = 'auth_author2,author2-role,id,mpaaRating,title_display,title_full,title_short,title_sub,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,num_titles,record_details,item_details,publisherStr,publishDate,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,display_description,fountas_pinnell,last_indexed';
	private $fieldsFull = '*,score';
	// HTTP Method
	//    private $method = HTTP_REQUEST_METHOD_GET;
	private $method = HTTP_REQUEST_METHOD_POST;
	// Result
	private $indexResult;

	// OTHER VARIABLES
	// Index
	/** @var Solr $indexEngine */
	private $indexEngine = null;
	// Facets information
	private $allFacetSettings = array();    // loaded from facets.ini
	// Optional, used on author screen for example
	private $searchSubType  = '';

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
		global $library;
		global $solrScope;
		// Include our solr index
		$class = $configArray['Index']['engine'];
		$classWithExtension = $class . '.php';
		require_once ROOT_DIR . "/sys/" . $classWithExtension;
		// Initialise the index
		$this->indexEngine = new $class($configArray['Index']['url']);
		$timer->logTime('Created Index Engine');

		// Get default facet settings
		$this->allFacetSettings = getExtraConfigArray('facets');
		$this->facetConfig = array();
		$facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}
		$translatedFacets = $this->getFacetSetting('Advanced_Settings', 'translated_facets');
		if (is_array($translatedFacets)) {
			$this->translatedFacets = $translatedFacets;
			foreach ($translatedFacets as $translatedFacet){
				$this->translatedFacets[] = $translatedFacet . '_'. $solrScope;
			}
		}

		// Load search preferences:
		$searchSettings = getExtraConfigArray('searches');
		if (isset($library)){
			if ($library->showTagging == 0){
				unset($searchSettings['Basic_Searches']['tag']);
			}
		}
		if (isset($searchSettings['General']['default_handler'])) {
			$this->defaultIndex = $searchSettings['General']['default_handler'];
		}
		if (isset($searchSettings['General']['default_sort'])) {
			$this->defaultSort = $searchSettings['General']['default_sort'];
		}
		if (isset($searchSettings['General']['default_view'])) {
			$this->defaultView = $searchSettings['General']['default_view'];
		}
		if (isset($searchSettings['General']['default_limit'])) {
			$this->defaultLimit = $searchSettings['General']['default_limit'];
		}
		if (isset($searchSettings['General']['retain_filters_by_default'])) {
			$this->retainFiltersByDefault
			= $searchSettings['General']['retain_filters_by_default'];
		}
		if (isset($searchSettings['DefaultSortingByType']) && is_array($searchSettings['DefaultSortingByType'])) {
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
								'popularity' => 'sort_popularity',
                'year' => 'sort_year', 'year asc' => 'sort_year asc',
                'callnumber' => 'sort_callnumber', 'author' => 'sort_author',
                'title' => 'sort_title');
		}

		// Load Spelling preferences
		$this->spellcheck    = $configArray['Spelling']['enabled'];
		$this->spellingLimit = $configArray['Spelling']['limit'];
		$this->spellSimple   = $configArray['Spelling']['simple'];
		$this->spellSkipNumeric = isset($configArray['Spelling']['skip_numeric']) ?
		$configArray['Spelling']['skip_numeric'] : true;

		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$timer->logTime('Setup Solr Search Object');
	}

	public function disableScoping(){
		$this->indexEngine->disableScoping();
	}

	public function enableScoping(){
		$this->indexEngine->enableScoping();
	}

	public function disableSpelling(){
		$this->spellcheck = false;
	}

	public function enableSpelling(){
		$this->spellcheck = true;
	}

	/**
	 * Add filters to the object based on values found in the $_REQUEST superglobal.
	 *
	 * @access  protected
	 */
	protected function initFilters()
	{
		// Use the default behavior of the parent class, but add support for the
		// special illustrations filter.
		parent::initFilters();
		if (isset($_REQUEST['illustration'])) {
			if ($_REQUEST['illustration'] == 1) {
				$this->addFilter('illustrated:Illustrated');
			} else if ($_REQUEST['illustration'] == 0) {
				$this->addFilter('illustrated:"Not Illustrated"');
			}
		}
	}

	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @var string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = null, $searchTerm = null)
	{
		// Call the standard initialization routine in the parent:
		parent::init($searchSource);

		$this->indexEngine->setSearchSource($searchSource);

		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch(null, true, true);
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

		if ($searchTerm == null){
			$searchTerm = isset($_REQUEST['lookfor']) ? $_REQUEST['lookfor'] : null;
		}

		global $module;
		global $action;

		//********************
		// Basic Search logic
		if ($this->initBasicSearch($searchTerm)) {
			// If we found a basic search, we don't need to do anything further.
		} elseif (isset($_REQUEST['tag']) && $module != 'MyAccount') {
			// Tags, just treat them as normal searches for now.
			// The search processor knows what to do with them.
			if ($_REQUEST['tag'] != '') {
				$this->searchTerms[] = array(
                    'index'   => 'tag',
                    'lookfor' => strip_tags($_REQUEST['tag'])
				);
			}
		} else {
			$this->initAdvancedSearch();
		}

		//********************
		// Author screens - handled slightly differently
		$author_ajax_call = (isset($_REQUEST['author']) && $action == 'AJAX' && $module == 'Search');
		if ($module == 'Author' || $author_ajax_call) {
			// Author module or ajax call from author results page
			// *** Things in common to both screens
			// Log a special type of search
			$this->searchType = 'author';
			// We don't spellcheck this screen
			//   it's not for free user input anyway
			$this->spellcheck  = false;

			// *** Author/Home
			if ($action == 'Home' || $author_ajax_call) {
				$this->searchSubType = 'home';
				// Remove our empty basic search (default)
				$this->searchTerms = array();
				// Prepare the search as a normal author search
				$author = $_REQUEST['author'];
				if (is_array($author)){
					$author = array_pop($author);
				}
				$this->searchTerms[] = array(
                    'index'   => 'Author',
                    'lookfor' => trim(strip_tags($author))
				);
			}

			// *** Author/Search
			if ($action == 'Search') {
				$this->searchSubType = 'search';
				// We already have the 'lookfor', just set the index
				$this->searchTerms[0]['index'] = 'Author';
				// We really want author facet data
				$this->facetConfig = array();
				$this->addFacet('authorStr');
				// Offset the facet list by the current page of results, and
				// allow up to ten total pages of results -- since we can't
				// get a total facet count, this at least allows the paging
				// mechanism to automatically add more pages to the end of the
				// list so that users can browse deeper and deeper as they go.
				// TODO: Make this better in the future if Solr offers a way
				//       to get a total facet count (currently not possible).
				$this->facetOffset = ($this->page - 1) * $this->limit;
				$this->facetLimit = $this->limit * 10;
				// Sorting - defaults to off with unlimited facets, so let's
				//           be explicit here for simplicity.
				if (isset($_REQUEST['sort']) && ($_REQUEST['sort'] == 'author')) {
					$this->setFacetSortOrder('index');
				} else {
					$this->setFacetSortOrder('count');
				}
			}
		} else if ($module == 'Search' && ($action == 'NewItem' || $action == 'Reserves')) {
			// We don't need spell checking
			$this->spellcheck = false;
			$this->searchType = strtolower($action);
		} else if ($module == 'MyAccount') {
			// Users Lists
			$this->spellcheck = false;
			$this->searchType = ($action == 'Home') ? 'favorites' : 'list';
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = strip_tags($_REQUEST['q']);
		}

		return true;
	} // End init()

	public function setSearchTerm($searchTerm){
		$this->initBasicSearch($searchTerm);
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
		global $locationSingleton;
		// Call the standard initialization routine in the parent:
		parent::init();

		$searchLibrary = Library::getActiveLibrary();

		$searchLocation = $locationSingleton->getActiveLocation();
		/** @var Location $userLocation */
//		$userLocation = Location::getUserHomeLocation();
		$hasSearchLibraryFacets = ($searchLibrary != null && (count($searchLibrary->facets) > 0));
		$hasSearchLocationFacets = ($searchLocation != null && (count($searchLocation->facets) > 0));
		if ($hasSearchLocationFacets){
			$facets = $searchLocation->facets;
		}elseif ($hasSearchLibraryFacets){
			$facets = $searchLibrary->facets;
		}else{
			$facets = Library::getDefaultFacets();
		}

		$this->facetConfig = array();
		global $solrScope;
		foreach ($facets as $facet){
			$facetName = $facet->facetName;
			//Adjust facet name for local scoping
			if ($solrScope){
				if ($facet->facetName == 'availability_toggle'){
					$facetName = 'availability_toggle_' . $solrScope;
				}elseif ($facet->facetName == 'format'){
					$facetName = 'format_' . $solrScope;
				}elseif ($facet->facetName == 'format_category'){
					$facetName = 'format_category_' . $solrScope;
				}elseif ($facet->facetName == 'econtent_source'){
					$facetName = 'econtent_source_' . $solrScope;
				}elseif ($facet->facetName == 'econtent_protection_type'){
					$facetName = 'econtent_protection_type_' . $solrScope;
				}elseif ($facet->facetName == 'detailed_location'){
					$facetName = 'detailed_location_' . $solrScope;
				}elseif ($facet->facetName == 'owning_location'){
					$facetName = 'owning_location_' . $solrScope;
				}elseif ($facet->facetName == 'owning_library'){
					$facetName = 'owning_library_' . $solrScope;
				}elseif ($facet->facetName == 'available_at'){
					$facetName = 'available_at_' . $solrScope;
				}elseif ($facet->facetName == 'collection' || $facet->facetName == 'collection_group'){
					$facetName = 'collection_' . $solrScope;
				}
			}
			if (isset($searchLibrary)){
				if ($facet->facetName == 'time_since_added'){
					$facetName = 'local_time_since_added_' . $searchLibrary->subdomain;
				}elseif ($facet->facetName == 'itype'){
					$facetName = 'itype_' . $searchLibrary->subdomain;
				}
			}
			//TODO: check if needed anymore
//			if (isset($userLocation)){
//				if ($facet->facetName == 'availability_toggle'){
//					$facetName = 'availability_toggle_' . $userLocation->code;
//				}
//			}
			if (isset($searchLocation)) {
				if ($facet->facetName == 'time_since_added' && $searchLocation->restrictSearchByLocation) {
					$facetName = 'local_time_since_added_' . $searchLocation->code;
				}
			}

				if ($facet->showInAdvancedSearch){
				$this->facetConfig[$facetName] = $facet->displayName;
			}
		}

		//********************

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
	 * Initialise the object for retrieving dynamic data
	 *    for the browse screen to function.
	 *
	 * We don't know much at this stage, the browse AJAX
	 *   calls need to supply the queries and facets.
	 *
	 * @access  public
	 * @return  boolean
	 */
	public function initBrowseScreen()
	{
		global $configArray;

		// Call the standard initialization routine in the parent:
		parent::init();

		$this->facetConfig = array();
		// Use the facet limit specified in config.ini (or default to 100):
		$this->facetLimit = isset($configArray['Browse']['result_limit']) ?
		$configArray['Browse']['result_limit'] : 100;
		// Sorting defaults to off with unlimited facets
		$this->setFacetSortOrder('count');

		// We don't need spell checking
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

	public function getDebugTiming() {
		if (!$this->debug){
			return null;
		}else{
			if (!isset($this->indexResult['debug'])){
				return null;
			}else{
				return json_encode($this->indexResult['debug']['timing']);
			}
		}
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
		if ($this->searchType == $this->basicSearchType || $this->searchType == 'author') {
			return parent::getSearchIndex();
		} else {
			return null;
		}
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param   object $user User object owning tag/note metadata.
	 * @param   int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool $allowEdit Should we display edit controls?
	 * @param   array $IDList optional list of IDs to re-order the records by (ie User List sorts)
	 * @param    bool $isMixedUserList Used to correctly number items in a list of mixed content (eg catalog & archive content)
	 * @return array Array of HTML chunks for individual records.
	 */
	public function getResultListHTML($user, $listId = null, $allowEdit = true, $IDList = null, $isMixedUserList = false)
	{
		global $interface;
		$html = array();
		if ($IDList){
			//Reorder the documents based on the list of id's
			$x = 0;
			$nullHolder = null;
			foreach ($IDList as $listPosition => $currentId){
				// use $IDList as the order guide for the html
				$current = &$nullHolder; // empty out in case we don't find the matching record
				reset($this->indexResult['response']['docs']);
				foreach ($this->indexResult['response']['docs'] as $index => $doc) {
					if ($doc['id'] == $currentId) {
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
					/** @var GroupedWorkDriver $record */
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
			//The order we get from solr is just fine
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$current = & $this->indexResult['response']['docs'][$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				if (!$this->debug){
					unset($current['explain']);
					unset($current['score']);
				}
				/** @var GroupedWorkDriver $record */
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				$record = RecordDriverFactory::initRecordDriver($current);
				$html[] = $interface->fetch($record->getListEntry($user, $listId, $allowEdit));
			}
		}
		return $html;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use on a user's "favorites" page.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getSuggestionListHTML()
	{
		global $interface;

		$html = array();
		if (isset($this->indexResult['response']) && isset($this->indexResult['response']['docs'])){
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$current = & $this->indexResult['response']['docs'][$x];
				if (!$this->debug){
					unset($current['explain']);
					unset($current['score']);
				}
				$record = RecordDriverFactory::initRecordDriver($current);
				$html[] = $interface->fetch($record->getSuggestionEntry());
			}
		}
		return $html;
	}
	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use on a user's "favorites" page.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getBrowseRecordHTML()
	{
		global $interface;
		$html = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = & $this->indexResult['response']['docs'][$x];
			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!PEAR_Singleton::isError($record)){
				if (method_exists($record, 'getBrowseResult')){
					$html[] = $interface->fetch($record->getBrowseResult());
				}else{
					$html[] = 'Browse Result not available';
				}

			}else{
				$html[] = "Unable to find record";
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
		//Marmot add shortIds without dot for use in display.
		if (isset($this->indexResult['response'])){
			$recordSet = $this->indexResult['response']['docs'];
			if (is_array($recordSet)){
				foreach ($recordSet as $key => $record){
					//Trim off the dot from the start
					$record['shortId'] = substr($record['id'], 1);
					if (!$this->debug){
						unset($record['explain']);
						unset($record['score']);
					}
					$recordSet[$key] = $record;
				}
			}
		}else{
			return array();
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
					if (!empty($orderedListOfIDs)){
						$position = array_search($current['id'], $orderedListOfIDs);
						if ($position !== false){
							$widgetTitles[$position] = $record->getListWidgetTitle();
						}
					} else {
						$widgetTitles[] = $record->getListWidgetTitle();
					}
				}else{
					$widgetTitles[] = 'List Widget Title not available';
				}
			}else{
				$widgetTitles[] = "Unable to find record";
			}
		}
		return $widgetTitles;
	}

	/*
	 * Get an array of citations for the records within the search results
	 */
	public function getCitations($citationFormat){
		global $interface;
		$html = array();
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = & $this->indexResult['response']['docs'][$x];
			$interface->assign('recordIndex', $x + 1);
			$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
			$record = RecordDriverFactory::initRecordDriver($current);
			$html[] = $interface->fetch($record->getCitation($citationFormat));
		}
		return $html;
	}
/*
 *  Get the template to use to display the results returned from getRecordHTML()
 *  based on the view mode
 *
 * @return string  Template file name
 */
	public function getDisplayTemplate() {
		if ($this->view == 'covers'){
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles
		} else { // default
			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
		}
		return $displayTemplate;
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
		global $memoryWatcher;
		$html = array();
		if (isset($this->indexResult['response'])) {
			$allWorkIds = array();
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$allWorkIds[] = $this->indexResult['response']['docs'][$x]['id'];
			}
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			GroupedWorkDriver::loadArchiveLinksForWorks($allWorkIds);
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$memoryWatcher->logMemory("Started loading record information for index $x");
				$current = &$this->indexResult['response']['docs'][$x];
				if (!$this->debug) {
					unset($current['explain']);
					unset($current['score']);
				}
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				$record = RecordDriverFactory::initRecordDriver($current);
				$record->setScopingEnabled($this->indexEngine->isScopingEnabled());
				if (!PEAR_Singleton::isError($record)) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult($this->view));
				} else {
					$html[] = "Unable to find record";
				}
				//Free some memory
				$record = 0;
				unset($record);
				$memoryWatcher->logMemory("Finished loading record information for index $x");
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
	public function getCombinedResultsHTML()
	{
		global $interface;
		global $memoryWatcher;
		$html = array();
		if (isset($this->indexResult['response'])) {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$memoryWatcher->logMemory("Started loading record information for index $x");
				$current = &$this->indexResult['response']['docs'][$x];
				if (!$this->debug) {
					unset($current['explain']);
					unset($current['score']);
				}
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				/** @var GroupedWorkDriver|ListRecord $record */
				$record = RecordDriverFactory::initRecordDriver($current);
				$record->setScopingEnabled($this->indexEngine->isScopingEnabled());
				if (!PEAR_Singleton::isError($record)) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getCombinedResult($this->view));
				} else {
					$html[] = "Unable to find record";
				}
				//Free some memory
				$record = 0;
				unset($record);
				$memoryWatcher->logMemory("Finished loading record information for index $x");
			}
		}
		return $html;
	}

	/**
	 * Set an overriding array of record IDs.
	 *
	 * @access  public
	 * @param   array   $ids        Record IDs to load
	 */
	public function setQueryIDs($ids)
	{
		$this->query = 'id:(' . implode(' OR ', $ids) . ')';
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
					$returnArray = $this->doSpellingReplace($term, $targetTerm, $inToken, $details, $returnArray);
				}
			}
			// If no tokens we found, just look
			//    for the suggestion 'as is'
			if ($targetTerm == "") {
				$targetTerm = $term;
				$returnArray = $this->doSpellingReplace($term, $targetTerm, $inToken, $details, $returnArray);
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
		// Author/Search screen
		if ($this->searchType == 'author' && $this->searchSubType == 'search') {
			// It's important to remember here we are talking about on-screen
			//   sort values, not what is sent to Solr, since this screen
			//   is really using facet sorting.
			return array('relevance' => 'sort_author_relevance',
                'author' => 'sort_author_author');
		}

		// Everywhere else -- use normal default behavior
		$sortOptions = parent::getSortOptions();
		$searchLibrary = Library::getSearchLibrary($this->searchSource);
		if ($searchLibrary == null){
			unset($sortOptions['callnumber_sort'] );
		}
		return $sortOptions;
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
	 * Return a url of the current search as an Excel Spreadsheet.
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
			$fullQuery = $this->indexEngine->buildQuery($this->searchTerms, false);
			$displayQuery = $this->indexEngine->buildQuery($this->searchTerms, true);
			$this->query = $fullQuery;
			if ($fullQuery != $displayQuery){
				$this->publicQuery = $displayQuery;
			}
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
			$output = $this->publicQuery = $this->indexEngine->buildQuery($this->searchTerms, true);
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
		// Base URL is different for author searches:
		if ($this->searchType == 'author') {
			if ($this->searchSubType == 'home')   return $this->serverUrl."/Author/Home?";
			if ($this->searchSubType == 'search') return $this->serverUrl."/Author/Search?";
		} else if ($this->searchType == 'newitem') {
			return $this->serverUrl . '/Search/NewItem?';
		} else if ($this->searchType == 'reserves') {
			return $this->serverUrl . '/Search/Reserves?';
		} else if ($this->searchType == 'favorites') {
			return $this->serverUrl . '/MyAccount/Home?';
		} else if ($this->searchType == 'list') {
			return $this->serverUrl . '/MyAccount/MyList/' .
			urlencode($_GET['id']) . '?';
		}

		// If none of the special cases were met, use the default from the parent:
		return parent::getBaseUrl();
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
				// Author Home screen
				case "author":
					if ($this->searchSubType == 'home') $params[] = "author=" . urlencode($this->searchTerms[0]['lookfor']);
					if ($this->searchSubType == 'search') $params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
					$params[] = "basicSearchType=Author";
					break;
				// New Items or Reserves modules may have a few extra parameters to preserve:
				case "newitem":
				case "reserves":
				case "favorites":
				case "list":
					$preserveParams = array(
						// for newitem:
						'range', 'department',
						// for reserves:
						'course', 'inst', 'dept',
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
					break;
				// Basic search -- use default from parent class.
				default:
					$params = parent::getSearchParams();
					break;
			}

			if (isset($_REQUEST['basicType'])) {
				if ($_REQUEST['basicType'] == 'AllFields'){
					$_REQUEST['basicType'] = 'Keyword';
				}
				if (is_array($_REQUEST['basicType'])){
					$_REQUEST['basicType'] = reset($_REQUEST['basicType']);
				}
				$params[] = 'basicType=' . $_REQUEST['basicType'];
			} else if (isset($_REQUEST['type'])) {
				if ($_REQUEST['type'] == 'AllFields'){
					$_REQUEST['type'] = 'Keyword';
				}
				$params[] = 'type=' . $_REQUEST['type'];
			}
			$this->params = $params;
		}
		return $this->params;
	}

	/**
	 * Process a search for a particular tag.
	 *
	 * @access  private
	 * @param   string  $lookfor    The tag to search for
	 * @return  array   A revised searchTerms array to get matching Solr records
	 *                  (empty if no tag matches found).
	 */
	private function processTagSearch($lookfor)
	{
		// Include the app database objects
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		// Find our tag in the database
		$tag = new UserTag();
		$tag->tag = $lookfor;
		$tag->selectAdd(null);
		$tag->selectAdd('DISTINCT(groupedRecordPermanentId) as groupedRecordPermanentId');
		$newSearch = array();
		$newSearch[0] = array('join' => 'OR', 'group' => array());
		$tag->find();
		while ($tag->fetch()) {
			// Grab the list of records tagged with this tag
			$id = $tag->groupedRecordPermanentId;
			$newSearch[0]['group'][] = array(
                    'field' => 'id',
                    'lookfor' => $id,
                    'bool' => 'OR'
                    );
		}

		return $newSearch;
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
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) {
		global $timer;
		global $analytics;

		if ($this->searchSource == 'econtent'){
			global $solrScope;
			$this->addHiddenFilter("econtent_source_{$solrScope}", '*');
		}

		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendations module appropriate to the current search:
		if ($recommendations) {
			$this->initRecommendations();
		}
		$timer->logTime("initRecommendations");


		// Tag searches need to be handled differently
		if (count($search) == 1 && isset($search[0]['index']) && $search[0]['index'] == 'tag') {
			// If we managed to find some tag matches, let's override the search
			// array.  If we didn't find any tag matches, we should return an
			// empty record set.
			$newSearch = $this->processTagSearch($search[0]['lookfor']);
			$timer->logTime("process Tag search");
			// Save search so it displays correctly on the "no hits" page:
			$this->publicQuery = $search[0]['lookfor'];
			if (empty($newSearch)) {
				return array('response' => array('numFound' => 0, 'docs' => array()));
			} else {
				$search = $newSearch;
			}
		}

		// Build Query
		if ($preventQueryModification){
			$query = $search;
		}else{
			$query = $this->indexEngine->buildQuery($search, false);
		}
		$timer->logTime("build query");
		if (PEAR_Singleton::isError($query)) {
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
			if ($field === ''){
				unset($this->filterList[$field]);
			}
		}

		$availabilityToggleValue = null;
		$availabilityAtValue = null;
		$formatValue = null;
		$formatCategoryValue = null;
		foreach ($this->filterList as $field => $filter) {
			foreach ($filter as $value) {
				$analytics->addEvent('Apply Facet', $field, $value);
				$isAvailabilityToggle = false;
				$isAvailableAt = false;
				if (substr($field, 0, strlen('availability_toggle')) == 'availability_toggle') {
					$availabilityToggleValue = $value;
					$isAvailabilityToggle = true;
				}elseif (substr($field, 0, strlen('available_at')) == 'available_at'){
					$availabilityAtValue = $value;
					$isAvailableAt = true;
				}elseif (substr($field, 0, strlen('format_category')) == 'format_category'){
					$formatCategoryValue = $value;
				}elseif (substr($field, 0, strlen('format')) == 'format'){
					$formatValue = $value;
				}
				// Special case -- allow trailing wildcards:
				if (substr($value, -1) == '*') {
					$filterQuery[] = "$field:$value";
				} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)){
					$filterQuery[] = "$field:$value";
				} elseif (preg_match('/^\\(.*?\\)$/', $value)){
					$filterQuery[] = "$field:$value";
				} else {
					if (!empty($value)){
						if ($isAvailabilityToggle) {
							$filterQuery['availability_toggle'] = "$field:\"$value\"";
						}elseif ($isAvailableAt){
							$filterQuery['available_at'] = "$field:\"$value\"";
						}else{
							if (is_numeric($field)){
								$filterQuery[] = $value;
							}else {
								$filterQuery[] = "$field:\"$value\"";
							}
						}
					}
				}
			}
		}

		//Check to see if we have both a format and availability facet applied.
		$availabilityByFormatFieldName = null;
		if ($availabilityToggleValue != null && ($formatCategoryValue != null || $formatValue != null)){
			global $solrScope;
			//Make sure to process the more specific format first
			if ($formatValue != null){
				$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
			}else{
				$availabilityByFormatFieldName = 'availability_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatCategoryValue));
			}
			$filterQuery['availability_toggle'] = $availabilityByFormatFieldName . ':"' . $availabilityToggleValue . '"';
		}

		//Check to see if we have both a format and available at facet applied
		$availableAtByFormatFieldName = null;
		if ($availabilityAtValue != null && ($formatCategoryValue != null || $formatValue != null)){
			global $solrScope;
			//Make sure to process the more specific format first
			if ($formatValue != null){
				$availableAtByFormatFieldName = 'available_at_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatValue));
			}else{
				$availableAtByFormatFieldName = 'available_at_by_format_' . $solrScope . '_' . strtolower(preg_replace('/\W/', '_', $formatCategoryValue));
			}
			$filterQuery['available_at'] = $availableAtByFormatFieldName . ':"' . $availabilityAtValue . '"';
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
				if (strpos($facetField, 'availability_toggle') === 0){
					if ($availabilityByFormatFieldName){
						$facetSet['field'][] = $availabilityByFormatFieldName;
					}else{
						$facetSet['field'][] = $facetField;
					}
				}else{
					$facetSet['field'][] = $facetField;
				}
			}
			if ($this->facetOffset != null) {
				$facetSet['offset'] = $this->facetOffset;
			}
			if ($this->facetLimit != null) {
				$facetSet['limit'] = $this->facetLimit;
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
		$timer->logTime("create facets");

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
		$timer->logTime("create spell check");

		// Get time before the query
		$this->startQueryTimer();

		// The "relevance" sort option is a VuFind reserved word; we need to make
		// this null in order to achieve the desired effect with Solr:
		$finalSort = ($this->sort == 'relevance') ? null : $this->sort;

		// The first record to retrieve:
		//  (page - 1) * limit = start
		$recordStart = ($this->page - 1) * $this->limit;
		//Remove irrelevant fields based on scoping
		$fieldsToReturn = $this->getFieldsToReturn();

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
			$fieldsToReturn,   // Fields to return
			$this->method,     // HTTP Request method
			$returnIndexErrors // Include errors in response?
		);
		$timer->logTime("run solr search");

		// Get time after the query
		$this->stopQueryTimer();

		// How many results were there?
		if (!isset($this->indexResult['response']['numFound'])){
			//An error occurred
			$this->resultsTotal = 0;
		}else{
			$this->resultsTotal = $this->indexResult['response']['numFound'];
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

		//Add debug information to the results if available
		if ($this->debug && isset($this->indexResult['debug'])){
			$explainInfo = $this->indexResult['debug']['explain'];
			foreach ($this->indexResult['response']['docs'] as $key => $result){
				if (array_key_exists($result['id'], $explainInfo)){
					$result['explain'] = $explainInfo[$result['id']];
					$this->indexResult['response']['docs'][$key] = $result;
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
		$newSearch = SearchObjectFactory::initSearchObject('Solr');
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
		global $solrScope;
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->facetConfig;
		}

		// Start building the facet list:
		$list = array();

		// If we have no facets to process, give up now
		if (!isset($this->indexResult['facet_counts'])){
			return $list;
		}elseif (!is_array($this->indexResult['facet_counts']['facet_fields']) && !is_array($this->indexResult['facet_counts']['facet_dates'])) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		global $locationSingleton;
		/** @var Library $currentLibrary */
		$currentLibrary = Library::getActiveLibrary();
		$activeLocationFacet = null;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!is_null($activeLocation)){
			$activeLocationFacet = $activeLocation->facetLabel;
		}
		$relatedLocationFacets = null;
		$relatedHomeLocationFacets = null;
		$additionalAvailableAtLocations = null;
		if (!is_null($currentLibrary)){
			if ($currentLibrary->facetLabel == ''){
				$currentLibrary->facetLabel = $currentLibrary->displayName;
			}
			$relatedLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($currentLibrary->libraryId);
			if (strlen($currentLibrary->additionalLocationsToShowAvailabilityFor) > 0){
				$locationsToLookfor = explode('|', $currentLibrary->additionalLocationsToShowAvailabilityFor);
				$location = new Location();
				$location->whereAddIn('code', $locationsToLookfor, 'string');
				$location->find();
				$additionalAvailableAtLocations = array();
				while ($location->fetch()){
					$additionalAvailableAtLocations[] = $location->facetLabel;
				}
			}
		}
		$homeLibrary = Library::getPatronHomeLibrary();
		if (!is_null($homeLibrary)){
			$relatedHomeLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($homeLibrary->libraryId);
		}

		$allFacets = array_merge($this->indexResult['facet_counts']['facet_fields'], $this->indexResult['facet_counts']['facet_dates']);
		foreach ($allFacets as $field => $data) {
			// Skip filtered fields and empty arrays:
			if (!in_array($field, $validFields) || count($data) < 1) {
				$isValid = false;
				//Check to see if we are overriding availability toggle
				if (strpos($field, 'availability_by_format') === 0){
					foreach ($validFields as $validFieldName){
						if (strpos($validFieldName, 'availability_toggle') === 0){
							$field = $validFieldName;
							$isValid  = true;
							break;
						}
					}
				}
				if (!$isValid){
					continue;
				}
			}
			// Initialize the settings for the current field
			$list[$field] = array();
			// Add the on-screen label
			$list[$field]['label'] = $filter[$field];
			// Build our array of values for this field
			$list[$field]['list']  = array();
			$foundInstitution = false;
			$doInstitutionProcessing = false;
			$foundBranch = false;
			$doBranchProcessing = false;

			//Marmot specific processing to do custom resorting of facets.
			if (strpos($field, 'owning_library') === 0 && isset($currentLibrary) && !is_null($currentLibrary)){
				$doInstitutionProcessing = true;
			}
			if (strpos($field, 'owning_location') === 0 && (!is_null($relatedLocationFacets) || !is_null($activeLocationFacet))){
				$doBranchProcessing = true;
			}elseif(strpos($field, 'available_at') === 0){
				$doBranchProcessing = true;
			}
			// Should we translate values for the current facet?
			$translate = in_array($field, $this->translatedFacets);
			$numValidRelatedLocations = 0;
			$numValidLibraries = 0;
			// Loop through values:
			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = array();
				$currentSettings['value'] = $facet[0];
				$currentSettings['display'] = $translate ? translate($facet[0]) : $facet[0];
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
				$okToAdd = true;
				if ($doInstitutionProcessing){
					//Special processing for Marmot digital library
					if ($facet[0] == $currentLibrary->facetLabel){
						$valueKey = '1' . $valueKey;
						$numValidLibraries++;
						$foundInstitution = true;
					}elseif ($facet[0] == $currentLibrary->facetLabel . ' Online'){
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}elseif ($facet[0] == $currentLibrary->facetLabel . ' On Order' || $facet[0] == $currentLibrary->facetLabel . ' Under Consideration'){
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}elseif ($facet[0] == 'Digital Collection' || $facet[0] == 'Marmot Digital Library'){
						$valueKey = '2' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}else if (!is_null($currentLibrary) && $currentLibrary->restrictOwningBranchesAndSystems == 1){
						//$okToAdd = false;
					}
				}else if ($doBranchProcessing){
					if (strlen($facet[0]) > 0){
						if ($activeLocationFacet != null && $facet[0] == $activeLocationFacet){
							$valueKey = '1' . $valueKey;
							$foundBranch = true;
							$numValidRelatedLocations++;
						}elseif (isset($currentLibrary) && $facet[0] == $currentLibrary->facetLabel . ' Online'){
							$valueKey = '1' . $valueKey;
							$numValidRelatedLocations++;
						}elseif (isset($currentLibrary) && ($facet[0] == $currentLibrary->facetLabel . ' On Order' || $facet[0] == $currentLibrary->facetLabel . ' Under Consideration')){
							$valueKey = '1' . $valueKey;
							$numValidRelatedLocations++;
						}else if (!is_null($relatedLocationFacets) && in_array($facet[0], $relatedLocationFacets)){
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						}else if (!is_null($relatedLocationFacets) && in_array($facet[0], $relatedLocationFacets)){
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						}else if (!is_null($relatedHomeLocationFacets) && in_array($facet[0], $relatedHomeLocationFacets)){
							$valueKey = '2' . $valueKey;
							$numValidRelatedLocations++;
						}elseif (!is_null($currentLibrary) && $facet[0] == $currentLibrary->facetLabel . ' Online'){
							$valueKey = '3' . $valueKey;
							$numValidRelatedLocations++;
						}else if ($field == 'available_at' && !is_null($additionalAvailableAtLocations) && in_array($facet[0], $additionalAvailableAtLocations)){
							$valueKey = '4' . $valueKey;
							$numValidRelatedLocations++;
						}elseif ($facet[0] == 'Marmot Digital Library' || $facet[0] == 'Digital Collection' || $facet[0] == 'OverDrive' || $facet[0] == 'Online'){
							$valueKey = '5' . $valueKey;
							$numValidRelatedLocations++;
						}else if (!is_null($currentLibrary) && $currentLibrary->restrictOwningBranchesAndSystems == 1){
							//$okToAdd = false;
						}
					}
				}


				// Store the collected values:
				if ($okToAdd){
					$list[$field]['list'][$valueKey] = $currentSettings;
				}
			}

			if (!$foundInstitution && $doInstitutionProcessing){
				$list[$field]['list']['1'.$currentLibrary->facetLabel] =
				array(
                        'value' => $currentLibrary->facetLabel,
                        'display' => $currentLibrary->facetLabel,
                        'count' => 0,
                        'isApplied' => false,
                        'url' => null,
                        'expandUrl' => null,
				);
			}
			if (!$foundBranch && $doBranchProcessing && !is_null($activeLocationFacet)){
				$list[$field]['list']['1'.$activeLocationFacet] =
				array(
                        'value' => $activeLocationFacet,
                        'display' => $activeLocationFacet,
                        'count' => 0,
                        'isApplied' => false,
                        'url' => null,
                        'expandUrl' => null,
				);
				$numValidRelatedLocations++;
			}

			//How many facets should be shown by default
			//Only show one system unless we are in the global scope
			if ($field == 'owning_library_' . $solrScope && isset($currentLibrary)){
				$list[$field]['valuesToShow'] = $numValidLibraries;
			}else if ($field == 'owning_location_' . $solrScope && isset($relatedLocationFacets) && $numValidRelatedLocations > 0){
				$list[$field]['valuesToShow'] = $numValidRelatedLocations;
			}else if ($field == 'available_at_' . $solrScope){
				$list[$field]['valuesToShow'] = count($list[$field]['list']);
			}else{
				$list[$field]['valuesToShow'] = 5;
			}

			//Sort the facet alphabetically?
			//Sort the system and location alphabetically unless we are in the global scope
			global $solrScope;
			if (in_array($field, array('owning_library_' . $solrScope, 'owning_location_' . $solrScope, 'available_at_' . $solrScope))  && isset($currentLibrary) ){
				$list[$field]['showAlphabetically'] = true;
			}else{
				$list[$field]['showAlphabetically'] = false;
			}
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

		$baseUrl = $configArray['Site']['url'];
		for ($i = 0; $i < count($result['response']['docs']); $i++) {

			//Since the base URL can be different depending on the record type, add the url to the response
			if (strcasecmp($result['response']['docs'][$i]['recordtype'], 'grouped_work') == 0){
				$id = $result['response']['docs'][$i]['id'];
				$result['response']['docs'][$i]['recordUrl'] = $baseUrl . '/GroupedWork/' . $id;
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
				$groupedWorkDriver = new GroupedWorkDriver($result['response']['docs'][$i]);
				if ($groupedWorkDriver->isValid){
					$image = $groupedWorkDriver->getBookcoverUrl('medium');
					$description = "<img src='$image'/> " . $groupedWorkDriver->getDescriptionFast();
					$result['response']['docs'][$i]['rss_description'] = $description;
				}
			}else{
				$id = $result['response']['docs'][$i]['id'];
				$result['response']['docs'][$i]['recordUrl'] = $baseUrl . '/Record/' . $id;
			}

		}

		global $interface;

		// On-screen display value for our search
		if ($this->searchType == 'newitem') {
			$lookfor = translate('New Items');
		} else if ($this->searchType == 'reserves') {
			$lookfor = translate('Course Reserves');
		} else {
			$lookfor = $this->displayQuery();
		}
		if (count($this->filterList) > 0) {
			// TODO : better display of filters
			$interface->assign('lookfor', $lookfor . " (" . translate('with filters') . ")");
		} else {
			$interface->assign('lookfor', $lookfor);
		}
		// The full url to recreate this search
		$interface->assign('searchUrl', $configArray['Site']['url']. $this->renderSearchUrl());
		// Stub of a url for a records screen
		$interface->assign('baseUrl',    $configArray['Site']['url']."/Record/");

		$interface->assign('result', $result);
		return $interface->fetch('Search/rss.tpl');
	}

	/**
	 * Turn our results into an Excel document
	 *
	 * @access  public
	 * @var  array      $result      Existing result set (null to do new search)
	 * @return  string                  Excel document
	 */
	public function buildExcel($result = null)
	{
		// First, get the search results if none were provided
		// (we'll go for 50 at a time)
		if (is_null($result)) {
			$this->limit = 1000;
			$result = $this->processSearch(false, false);
		}

		// Prepare the spreadsheet
		ini_set('include_path', ini_get('include_path'.';/PHPExcel/Classes'));
		include ROOT_DIR . '/PHPExcel.php';
		include ROOT_DIR . '/PHPExcel/Writer/Excel2007.php';
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("Search Results");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Results');

		//Add headers to the table
		$sheet = $objPHPExcel->getActiveSheet();
		$curRow = 1;
		$curCol = 0;
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Record #');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publisher');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Published');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Call Number');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Item Type');
		$sheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Location');

		$maxColumn = $curCol -1;

		global $solrScope;
		for ($i = 0; $i < count($result['response']['docs']); $i++) {
			//Output the row to excel
			$curDoc = $result['response']['docs'][$i];
			$curRow++;
			$curCol = 0;
			//Output the row to excel
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['id']) ? $curDoc['id'] : '');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['title_display']) ? $curDoc['title_display'] : '');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['author']) ? $curDoc['author'] : '');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['publisherStr']) ? implode(', ', $curDoc['publisherStr']) : '');
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, isset($curDoc['publishDate']) ? implode(', ', $curDoc['publishDate']) : '');
			$callNumber = '';
			if (isset($curDoc['local_callnumber_' . $solrScope])){
				$callNumber = is_array($curDoc['local_callnumber_' . $solrScope]) ? $curDoc['local_callnumber_' . $solrScope][0] : $curDoc['local_callnumber_' . $solrScope];
			}
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $callNumber);
			$iType = '';
			if (isset($curDoc['itype_' . $solrScope])){
				$iType = is_array($curDoc['itype_' . $solrScope]) ? $curDoc['itype_' . $solrScope][0] : $curDoc['itype_' . $solrScope];
			}
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $iType);
			$location = '';
			if (isset($curDoc['detailed_location_' . $solrScope])){
				$location = is_array($curDoc['detailed_location_' . $solrScope]) ? $curDoc['detailed_location_' . $solrScope][0] : $curDoc['detailed_location_' . $solrScope];
			}
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $location);
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
		return $this->indexEngine->getRecord($id, $this->getFieldsToReturn());
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param   string[]  $ids        An array of documents to retrieve from Solr
	 * @access  public
	 * @throws  object              PEAR Error
	 * @return  array              The requested resources
	 */
	function getRecords($ids)
	{
		return $this->indexEngine->getRecords($ids, $this->getFieldsToReturn());
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param   string[]  $ids        An array of documents to retrieve from Solr
	 * @access  public
	 * @throws  object              PEAR Error
	 * @return  string              The requested resource
	 */
	function searchForRecordIds($ids)
	{
		$this->indexResult = $this->indexEngine->searchForRecordIds($ids);
	}


	/**
	 * Retrieves a document specified by the item barcode.
	 *
	 * @param   string  $barcode    A barcode of an item in the document to retrieve from Solr
	 * @access  public
	 * @throws  object              PEAR Error
	 * @return  string              The requested resource
	 */
	function getRecordByBarcode($barcode){
		return $this->indexEngine->getRecordByBarcode($barcode);
	}

	/**
	 * Retrieves a document specified by an isbn.
	 *
	 * @param   string[]  $isbn     An array of isbns to check
	 * @access  public
	 * @throws  object              PEAR Error
	 * @return  string              The requested resource
	 */
	function getRecordByIsbn($isbn){
		return $this->indexEngine->getRecordByIsbn($isbn, $this->getFieldsToReturn());
	}

	private function getFieldsToReturn() {
		if (isset($_REQUEST['allFields'])){
			$fieldsToReturn = '*,score';
		}else{
			$fieldsToReturn = SearchObject_Solr::$fields;
			global $solrScope;
			if ($solrScope != false){
				//$fieldsToReturn .= ',related_record_ids_' . $solrScope;
				//$fieldsToReturn .= ',related_items_' . $solrScope;
				$fieldsToReturn .= ',format_' . $solrScope;
				$fieldsToReturn .= ',format_category_' . $solrScope;
				$fieldsToReturn .= ',collection_' . $solrScope;
				$fieldsToReturn .= ',local_time_since_added_' . $solrScope;
				$fieldsToReturn .= ',local_callnumber_' . $solrScope;
				$fieldsToReturn .= ',detailed_location_' . $solrScope;
				$fieldsToReturn .= ',scoping_details_' . $solrScope;
				$fieldsToReturn .= ',owning_location_' . $solrScope;
				$fieldsToReturn .= ',owning_library_' . $solrScope;
				$fieldsToReturn .= ',available_at_' . $solrScope;
				$fieldsToReturn .= ',itype_' . $solrScope;

			}else{
				//$fieldsToReturn .= ',related_record_ids';
				//$fieldsToReturn .= ',related_record_items';
				//$fieldsToReturn .= ',related_items_related_record_ids';
				$fieldsToReturn .= ',format';
				$fieldsToReturn .= ',format_category';
				$fieldsToReturn .= ',days_since_added';
				$fieldsToReturn .= ',local_callnumber';
				$fieldsToReturn .= ',detailed_location';
				$fieldsToReturn .= ',owning_location';
				$fieldsToReturn .= ',owning_library';
				$fieldsToReturn .= ',available_at';
				$fieldsToReturn .= ',itype';
			}
			$fieldsToReturn .= ',score';
		}
		return $fieldsToReturn;
	}

	public function setPrimarySearch($flag){
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function __destruct(){
		if (isset($this->indexEngine)){
			$this->indexEngine = null;
			unset($this->indexEngine);
		}
	}

	public function pingServer($failOnError = true){
		return $this->indexEngine->pingServer($failOnError);
	}
}