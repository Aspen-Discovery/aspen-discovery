<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';

class SearchObject_EventsSearcher extends SearchObject_SolrSearcher
{
	public function __construct()
	{
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
			'start_date_sort asc' => 'Event Date',
			'relevance' => 'Best Match',
			'title' => 'Title'
		);

		// Debugging
		$this->indexEngine->debug = $this->debug;
		$this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

		$now = new DateTime();
		$this->addHiddenFilter('end_date', "[{$now->format('Y-m-d')} TO *]");

		$timer->logTime('Setup Events Search Object');
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
		parent::init('events');

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
		if (!$this->initBasicSearch()) {
			$this->initAdvancedSearch();
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
		}

		//Validate we got good search terms
		foreach ($this->searchTerms as &$searchTerm) {
			if (isset($searchTerm['index'])){
				if ($searchTerm['index'] == 'Keyword') {
					$searchTerm['index'] = 'EventsKeyword';
				} elseif ($searchTerm['index'] == 'Title') {
					$searchTerm['index'] = 'EventsTitle';
				}
			}else{
				foreach ($searchTerm['group'] as &$group){
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

	public function getSearchIndexes()
	{
		return [
			'EventsKeyword' => translate(['text'=>'Keyword', 'isPublicFacing'=>true, 'inAttribute'=>true]),
			'EventsTitle' => translate(['text'=>'Title', 'isPublicFacing'=>true, 'inAttribute'=>true]),
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
        if (substr($current['type'],0,12) == 'event_libcal') {
            require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
            return new SpringshareLibCalEventRecordDriver($current);
        } else {
// TODO: rewrite Library Market Library Calendar type as event_lm or something similar. 2022 03 20 James.
            require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
            return new LibraryCalendarEventRecordDriver($current);
        }
	}

	public function getSearchesFile()
	{
		return 'eventsSearches';
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
		if ($searchIndex == 'EventsTitle') {
			$suggestionHandler = 'title_suggest';
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	//TODO: Convert this to use definitions so they can be customized in admin
	public function getFacetConfig()
	{
		if ($this->facetConfig == null) {
			$facetConfig = [];
//
//            $eventDate = new LibraryFacetSetting();
//            $eventDate->id = count($facetConfig) +1;
//            $eventDate->multiSelect = false;
//            $eventDate->facetName = "start_date";
//            $eventDate->displayName = "Event Date";
//            $eventDate->numEntriesToShowByDefault = 5;
//            $eventDate->translate = false;
//            $eventDate->collapseByDefault = false;
//            $eventDate->useMoreFacetPopup = false;
//            $facetConfig["start_date"] = $eventDate;

			$ageGroup = new LibraryFacetSetting();
			$ageGroup->id = count($facetConfig) +1;
			$ageGroup->multiSelect = true;
			$ageGroup->facetName = "age_group_facet";
			$ageGroup->displayName = "Age Group/Audience";
			$ageGroup->numEntriesToShowByDefault = 5;
			$ageGroup->translate = true;
			$ageGroup->collapseByDefault = false;
			$ageGroup->useMoreFacetPopup = true;
			$facetConfig["age_group_facet"] = $ageGroup;

			$programType = new LibraryFacetSetting();
			$programType->id = count($facetConfig) +1;
			$programType->multiSelect = true;
			$programType->facetName = "program_type_facet";
			$programType->displayName = "Program Type";
			$programType->numEntriesToShowByDefault = 5;
			$programType->translate = true;
			$programType->collapseByDefault = false;
			$programType->useMoreFacetPopup = true;
			$facetConfig["program_type_facet"] = $programType;

			$branch = new LibraryFacetSetting();
			$branch->id = count($facetConfig) +1;
			$branch->multiSelect = true;
			$branch->facetName = "branch";
			$branch->displayName = "Branch";
			$branch->numEntriesToShowByDefault = 5;
			$branch->translate = false;
			$branch->collapseByDefault = false;
			$branch->useMoreFacetPopup = true;
			$facetConfig["branch"] = $branch;

			$room = new LibraryFacetSetting();
			$room->id = count($facetConfig) +1;
			$room->multiSelect = true;
			$room->facetName = "room";
			$room->displayName = "Room";
			$room->numEntriesToShowByDefault = 5;
			$room->translate = false;
			$room->collapseByDefault = true;
			$room->useMoreFacetPopup = true;
			$facetConfig["room"] = $room;


			$internalCategory = new LibraryFacetSetting();
			$internalCategory->id = count($facetConfig) +1;
			$internalCategory->multiSelect = true;
			$internalCategory->facetName = "internal_category";
			$internalCategory->displayName = "Category";
			$internalCategory->numEntriesToShowByDefault = 5;
			$internalCategory->translate = false;
			$internalCategory->collapseByDefault = true;
			$internalCategory->useMoreFacetPopup = true;
			$facetConfig["internal_category"] = $internalCategory;

			$eventState = new LibraryFacetSetting();
			$eventState->id = count($facetConfig) +1;
			$eventState->multiSelect = true;
			$eventState->facetName = "event_state";
			$eventState->displayName = "State";
			$eventState->numEntriesToShowByDefault = 5;
			$eventState->translate = false;
			$eventState->collapseByDefault = true;
			$eventState->useMoreFacetPopup = true;
			$facetConfig["event_state"] = $eventState;

			$reservationState = new LibraryFacetSetting();
			$reservationState->id = count($facetConfig) +1;
			$reservationState->multiSelect = true;
			$reservationState->facetName = "reservation_state";
			$reservationState->displayName = "Reservation State";
			$reservationState->numEntriesToShowByDefault = 5;
			$reservationState->translate = false;
			$reservationState->collapseByDefault = true;
			$reservationState->useMoreFacetPopup = true;
			$facetConfig["reservation_state"] = $reservationState;

			$registrationRequired = new LibraryFacetSetting();
			$registrationRequired->id = count($facetConfig) +1;
			$registrationRequired->multiSelect = true;
			$registrationRequired->facetName = "registration_required";
			$registrationRequired->displayName = "Registration Required?";
			$registrationRequired->numEntriesToShowByDefault = 5;
			$registrationRequired->translate = false;
			$registrationRequired->collapseByDefault = true;
			$registrationRequired->useMoreFacetPopup = true;
			$facetConfig["registration_required"] = $registrationRequired;

			$eventType = new LibraryFacetSetting();
			$eventType->id = count($facetConfig) +1;
			$eventType->multiSelect = true;
			$eventType->facetName = "event_type";
			$eventType->displayName = "Event Type";
			$eventType->numEntriesToShowByDefault = 5;
			$eventType->translate = false;
			$eventType->collapseByDefault = true;
			$eventType->useMoreFacetPopup = true;
			$facetConfig["event_type"] = $eventType;

			$this->facetConfig = $facetConfig;
		}
		return $this->facetConfig;
	}

	public function getEngineName(){
		return 'Events';
	}

	public function getDefaultIndex()
	{
		return 'EventsKeyword';
	}
}