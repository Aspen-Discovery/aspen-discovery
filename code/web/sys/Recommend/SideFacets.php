<?php

require_once ROOT_DIR . '/sys/Recommend/Interface.php';

/**
 * SideFacets Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 */
class SideFacets implements RecommendationInterface {
	/** @var  SearchObject_SolrSearcher $searchObject */
	private SearchObject_BaseSearcher $searchObject;
	private array $facetSettings;
	private array $mainFacets;

	/* Constructor
	 *
	 * Establishes base settings for making recommendations.
	 *
	 * @access  public
	 * @param   SearchObject_BaseSearcher  $searchObject   The SearchObject requesting recommendations.
	 * @param   string  $params         Additional settings from the searches.ini.
	 */
	public function __construct(SearchObject_BaseSearcher $searchObject, $params) {
		// Save the passed-in SearchObject:
		$this->searchObject = $searchObject;

		$this->facetSettings = $searchObject->getFacetConfig();
		$this->mainFacets = [];
		if (!empty($this->facetSettings)) {
			foreach ($this->facetSettings as $facetName => $facet) {
				if (!$facet->showAboveResults) {
					$this->mainFacets[$facetName] = $facet->displayName;
				}
			}
		}
	}

	/* init
	 *
	 * Called before the SearchObject performs its main search.  This may be used
	 * to set SearchObject parameters in order to generate recommendations as part
	 * of the search.
	 *
	 * @access  public
	 */
	public function init() {
	}

	/* process
	 *
	 * Called after the SearchObject has performed its main search.  This may be
	 * used to extract necessary information from the SearchObject or to perform
	 * completely unrelated processing.
	 *
	 * @access  public
	 */
	public function process() : void {
		global $interface;
		global $library;

		$interface->assign('hasSearchableFacets', $this->searchObject->hasSearchableFacets());
		$interface->assign('removeAllFiltersUrl', $this->searchObject->getRemoveAllFiltersUrl());

		$lockSection = $this->searchObject->getSearchName();
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$lockedFacets = !empty($user->lockedFacets) ? json_decode($user->lockedFacets, true) : [];
		} else {
			$lockedFacets = $_SESSION['lockedFilters'] ?? [];
		}
		$lockedFacets = $lockedFacets[$lockSection] ?? [];
		$lockedValuesByUnscoped = [];
		foreach ($lockedFacets as $lockedFacetKey => $lockedValues) {
			$unscopedKey = $this->searchObject->getUnscopedFieldName($lockedFacetKey);
			// To make sure the scoped field (e.g. available_at_main) still shows as locked even when the field names differ.
			if (!isset($lockedValuesByUnscoped[$unscopedKey])) {
				$lockedValuesByUnscoped[$unscopedKey] = [];
			}
			if (is_array($lockedValues)) {
				$lockedValuesByUnscoped[$unscopedKey] = array_values(array_unique(array_merge($lockedValuesByUnscoped[$unscopedKey], $lockedValues)));
			}
		}

		//Get applied facets
		$filterList = $this->searchObject->getFilterList();
		foreach ($filterList as $facetKey => &$facet) {
			//Remove any top facets since the removal links are displayed above results
			if (str_starts_with($facet[0]['field'], 'availability_toggle')) {
				unset($filterList[$facetKey]);
				continue;
			}
			foreach ($facet as &$filter) {
				if (!empty($filter['field']) && array_key_exists('value', $filter)) {
					$unscopedField = $this->searchObject->getUnscopedFieldName($filter['field']);
					$filter['unscopedField'] = $unscopedField;
					$lockedValues = $lockedFacets[$filter['field']] ?? ($lockedValuesByUnscoped[$unscopedField] ?? []);
					if (!empty($lockedValues) && in_array($filter['value'], $lockedValues)) {
						$filter['isLocked'] = true;
					}
				}
			}
			unset($filter);
		}
		unset($facet);
		$interface->assign('filterList', $filterList);
		//Process the side facet set to handle the Added In Last facet which we only want to be
		//visible if there is not a value selected for the facet (makes it single select
		$sideFacets = $this->searchObject->getFacetList($this->mainFacets);

		//Figure out which counts to show.
		$searchSource = $_REQUEST['searchSource'];
		if ($searchSource == 'events') {
			/** @var LibraryEventsFacetSetting|null $facetSettings */
			$facetSettings = $library->getEventFacetSettings();
			if ($facetSettings) {
				$interface->assign('facetCountsToShow', $facetSettings->getFacetGroup()->eventFacetCountsToShow);

				//if there are multiple integrations being used for one library, the first setting found will be used
				if ($facetSettings->settingSource == 'communico') {
					require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
					$eventSettings = new CommunicoSetting;
					$eventSettings->id = $facetSettings->settingId;
					if ($eventSettings->find(true)) {
						$interface->assign('maxEventDate', strtotime("+" . $eventSettings->numberOfDaysToIndex . " days"));
					}
				} else if ($facetSettings->settingSource == 'springshare') {
					require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
					$eventSettings = new SpringshareLibCalSetting;
					$eventSettings->id = $facetSettings->settingId;
					if ($eventSettings->find(true)) {
						$interface->assign('maxEventDate', strtotime("+" . $eventSettings->numberOfDaysToIndex . " days"));
					}
				} else if ($facetSettings->settingSource == 'assabet') {
					require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
					$eventSettings = new AssabetSetting;
					$eventSettings->id = $facetSettings->settingId;
					if ($eventSettings->find(true)) {
						$interface->assign('maxEventDate', strtotime("+" . $eventSettings->numberOfDaysToIndex . " days"));
					}
				} else {
					require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
					$eventSettings = new LMLibraryCalendarSetting;
					$eventSettings->id = $facetSettings->settingId;
					if ($eventSettings->find(true)) {
						$interface->assign('maxEventDate', strtotime("+" . $eventSettings->numberOfDaysToIndex . " days"));
					}
				}
			}
		} else {
			$facetCountsToShow = $library->getGroupedWorkDisplaySettings()->facetCountsToShow;
			$interface->assign('facetCountsToShow', $facetCountsToShow);
		}

		//Do additional processing of facets
		if ($this->searchObject instanceof SearchObject_AbstractGroupedWorkSearcher) {
			foreach ($sideFacets as $facetKey => $facet) {
				/** @var FacetSetting $facetSetting */
				$facetSetting = $this->facetSettings[$facetKey];

				//Do special processing of facets
				if (preg_match('/time_since_added/i', $facetKey)) {
					$timeSinceAddedFacet = $this->updateTimeSinceAddedFacet($facet);
					$sideFacets[$facetKey] = $timeSinceAddedFacet;
				} elseif ($facetKey == 'rating_facet') {
					$userRatingFacet = $this->updateUserRatingsFacet($facet);
					$sideFacets[$facetKey] = $userRatingFacet;
				} else {
					$sideFacets = $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets);
				}
				//These are also done in apply Facet Settings, but are done here as well to cover other cases
				$sideFacets[$facetKey]['collapseByDefault'] = $facetSetting->collapseByDefault;
				$sideFacets[$facetKey]['locked'] = array_key_exists($facetKey, $lockedFacets);
				$sideFacets[$facetKey]['canLock'] = $facetSetting->canLock;
			}
		} elseif ($this->searchObject instanceof SearchObject_EventsSearcher) {
			//Process other searchers to add more facet popup
			foreach ($sideFacets as $facetKey => $facet) {
				/** @var FacetSetting $facetSetting */
				$facetSetting = $this->facetSettings[$facetKey];
				if ($facetKey == 'start_date') {
					$startDateFacet = $this->updateStartDateRatingsFacet($facet);
					$sideFacets[$facetKey] = $startDateFacet;
					$sideFacets[$facetKey]['hasApplied'] = isset($startDateFacet['start']) || isset($startDateFacet['end']);
				}else {
					$sideFacets = $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets);
				}
				$sideFacets[$facetKey]['collapseByDefault'] = $facetSetting->collapseByDefault;
				$sideFacets[$facetKey]['locked'] = array_key_exists($facetKey, $lockedFacets);
				$sideFacets[$facetKey]['canLock'] = $facetSetting->canLock;
			}
		} elseif ($this->searchObject instanceof SearchObject_ListsSearcher) {
			foreach ($sideFacets as $facetKey => $facet) {
				//Do special processing of facets
				if (preg_match('/local_time_since_(added|updated)/i', $facetKey)) {
					$timeSinceAddedFacet = $this->updateTimeSinceAddedFacet($facet);
					$sideFacets[$facetKey] = $timeSinceAddedFacet;
				}
			}
		} else {
			//Process other searchers to add more facet popup
			foreach ($sideFacets as $facetKey => $facet) {
				/** @var FacetSetting $facetSetting */
				$facetSetting = $this->facetSettings[$facetKey];
				$sideFacets = $this->applyFacetSettings($facetKey, $sideFacets, $facetSetting, $lockedFacets);
			}
		}
		// Add locked facets to the side facets
		$lockedFacetKeys = array_keys($lockedFacets);
		$missingLockedFacets = array_diff($lockedFacetKeys, array_keys($sideFacets));
		if (!empty($missingLockedFacets)) {
			foreach ($missingLockedFacets as $facetKey) {
				$facetSetting = $this->facetSettings[$facetKey] ?? null;
				$label = ($facetSetting instanceof FacetSetting) ? $facetSetting->displayName : $facetKey;
				$sideFacets[$facetKey] = [
					'label' => $label,
					'list' => [],
					'locked' => true,
					'collapseByDefault' => true,
					'canLock' => true,
					'valuesToShow' => 5,
				];
			}
		}


		$interface->assign('sideFacetSet', $sideFacets);
	}

	private function updateTimeSinceAddedFacet($timeSinceAddedFacet) {
		//See if there is a value selected
		$valueSelected = false;
		foreach ($timeSinceAddedFacet['list'] as $facetValue) {
			if (isset($facetValue['isApplied']) && $facetValue['isApplied']) {
				$valueSelected = true;
			}
		}
		if ($valueSelected) {
			//Get rid of all values except the selected value which will allow the value to be removed
			//We remove the other values because it is confusing to have results both longer and shorter than the current value.
			foreach ($timeSinceAddedFacet['list'] as $facetKey => $facetValue) {
				if (!isset($facetValue['isApplied']) || !$facetValue['isApplied']) {
					unset($timeSinceAddedFacet['list'][$facetKey]);
				}
			}
		} else {
			//Make sure to show all values
			$timeSinceAddedFacet['valuesToShow'] = count($timeSinceAddedFacet['list']);
			//We would like to show, On Order, time period values, and then under consideration
//			$onOrderOption = array_key_exists('On Order', $timeSinceAddedFacet['list']) ? $timeSinceAddedFacet['list']['On Order'] : null;
//			$underConsiderationOption = array_key_exists('Under Consideration', $timeSinceAddedFacet['list']) ? $timeSinceAddedFacet['list']['Under Consideration'] : null;
//			if ($onOrderOption != null) {
//				unset($timeSinceAddedFacet['list']['On Order']);
//			}
//			if ($underConsiderationOption != null) {
//				unset($timeSinceAddedFacet['list']['Under Consideration']);
//			}
			$sortOrder = [
				'On Order' => null,
				'In Processing' => null,
				'Day' => null,
				'Week' => null,
				'Month' => null,
				'2 Months' => null,
				'Quarter' => null,
				'Six Months' => null,
				'Year' => null,
				'Under Consideration' => null,
			];
			$sortedOptions = array_merge($sortOrder, $timeSinceAddedFacet['list']);
//			if ($onOrderOption != null) {
//				$sortedOptions = ['On Order' => $onOrderOption] + $sortedOptions;
//			}
//			if ($underConsiderationOption != null) {
//				$sortedOptions = $sortedOptions + ['Under Consideration' => $underConsiderationOption];
//			}
			foreach ($sortedOptions as $key => $value) {
				if (is_null($value)) {
					unset($sortedOptions[$key]);
				}
			}
			//Reverse the display of the list so Day is first and year is last
			$timeSinceAddedFacet['list'] = $sortedOptions;
		}
		return $timeSinceAddedFacet;
	}

	private function updateUserRatingsFacet($userRatingFacet) {
		global $interface;
		$ratingApplied = false;
		$ratingLabels = [];
		foreach ($userRatingFacet['list'] as $facetValue) {
			if ($facetValue['isApplied']) {
				$ratingApplied = true;
				$ratingLabels = [$facetValue['value']];
			}
		}
		if (!$ratingApplied) {
			$ratingLabels = [
				'fiveStar',
				'fourStar',
				'threeStar',
				'twoStar',
				'oneStar',
				'Unrated',
			];
		}
		$interface->assign('ratingLabels', $ratingLabels);
		return $userRatingFacet;
	}

	private function updateStartDateRatingsFacet($startDateFacet) {
		if (!isset($_REQUEST['filter'])) {
			return $startDateFacet;
		}
		$filters = $_REQUEST['filter'];
		if (!empty($filters) && is_array($filters)) {
			foreach ($filters as $filter) {
				if (str_starts_with($filter, 'start_date')) {
					$filterValue = substr($filter, strpos($filter, '[') + 1);
					$filterValue = substr($filterValue, 0, -2);
					$range = explode(' TO ', $filterValue);
					$utcTimeZone = new DateTimeZone('UTC');
					$defaultTimezone = new DateTimeZone(date_default_timezone_get());
					if ($range[0] != '*') {
						$dt = new DateTime($range[0], $utcTimeZone);
						$dt->setTimezone($defaultTimezone);
						$startDateFacet['start'] = $dt->format("Y-m-d");
					}
					if ($range[1] != '*') {
						$dt = new DateTime($range[1], $utcTimeZone);
						$dt->setTimezone($defaultTimezone);
						$startDateFacet['end'] = $dt->format("Y-m-d");
					}
					break;
				}
			}
		}
		return $startDateFacet;
	}

	/* getTemplate
	 *
	 * This method provides a template name so that recommendations can be displayed
	 * to the end user.  It is the responsibility of the process() method to
	 * populate all necessary template variables.
	 *
	 * @access  public
	 * @return  string      The template to use to display the recommendations.
	 */
	public function getTemplate() : string {
		return 'Search/Recommend/SideFacets.tpl';
	}

	private function applyFacetSettings(string $facetKey, array $sideFacets, FacetSetting $facetSetting, array $lockedFacets): array {
		//Do additional handling of the display
		if ($facetSetting->sortMode == 'alphabetically') {
			asort($sideFacets[$facetKey]['list']);
		}
		$lockedValues = $lockedFacets[$facetKey] ?? [];
		if (!empty($sideFacets[$facetKey]['list'])) {
			$sideFacets[$facetKey]['list'] = $this->reorderFacetValues($sideFacets[$facetKey]['list'], $lockedValues);
		}
		if ($facetSetting->numEntriesToShowByDefault > 0) {
			$sideFacets[$facetKey]['valuesToShow'] = $facetSetting->numEntriesToShowByDefault;
		}
		if ($facetSetting->showAsDropDown) {
			$sideFacets[$facetKey]['showAsDropDown'] = $facetSetting->showAsDropDown;
		}
		if ($facetSetting->multiSelect) {
			$sideFacets[$facetKey]['multiSelect'] = $facetSetting->multiSelect;
		}
		if ($facetSetting->useMoreFacetPopup && count($sideFacets[$facetKey]['list']) > 12) {
			$sideFacets[$facetKey]['showMoreFacetPopup'] = true;
			$facetsList = $sideFacets[$facetKey]['list'];
			if ($facetSetting->multiSelect) {
				$sideFacets[$facetKey]['list'] = array_slice($facetsList, 0, $facetSetting->numEntriesToShowByDefault);
				$sideFacets[$facetKey]['fullUnsortedList'] = $facetsList;
			} else {
				$sideFacets[$facetKey]['list'] = array_slice($facetsList, 0, $facetSetting->numEntriesToShowByDefault);
				$sideFacets[$facetKey]['fullUnsortedList'] = $facetsList;
			}

			$sortedList = [];
			foreach ($facetsList as $key => $value) {
				$sortedList[strtolower($key) . $key] = $value;
			}
			ksort($sortedList);
			$sideFacets[$facetKey]['sortedList'] = $sortedList;
		} else {
			$sideFacets[$facetKey]['showMoreFacetPopup'] = false;
		}
		$sideFacets[$facetKey]['collapseByDefault'] = $facetSetting->collapseByDefault;

		$sideFacets[$facetKey]['locked'] = array_key_exists($facetKey, $lockedFacets);
		$sideFacets[$facetKey]['canLock'] = $facetSetting->canLock;
		$sideFacets[$facetKey]['displayNamePlural'] = empty($facetSetting->displayNamePlural) ? $facetSetting->displayName : $facetSetting->displayNamePlural;
		return $sideFacets;
	}

	/**
	 * Reorder the facet values to alwasy show applied values, then the other values
	 * @param array $facetList
	 * @param array $lockedValues
	 * @return array
	 */
	private function reorderFacetValues(array $facetList, array $lockedValues): array {
		if (empty($facetList)) {
			return $facetList;
		}
		$applied = [];
		$other = [];
		foreach ($facetList as $key => $value) {
			if (!empty($lockedValues) && isset($value['value']) && in_array($value['value'], $lockedValues, true)) {
				$value['isLocked'] = true;
			}
			if (!empty($value['isApplied'])) {
				$applied[$key] = $value;
			} else {
				$other[$key] = $value;
			}
		}
		return $applied + $other;

	}
}
