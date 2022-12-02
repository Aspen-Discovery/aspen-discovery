<?php

require_once ROOT_DIR . '/Action.php';

abstract class Search_AdvancedBase extends Action {
	/**
	 * Load a saved search, if appropriate and legal; assign an error to the
	 * interface if necessary.
	 *
	 * @access  protected
	 * @return  SearchObject_BaseSearcher|boolean mixed           Search Object on successful load, false otherwise
	 */
	protected function loadSavedSearch() {
		global $interface;

		// Are we editing an existing search?
		if (isset($_REQUEST['edit']) || isset($_SESSION['lastSearchId'])) {
			// Go find it
			$search = new SearchEntry();
			$search->id = isset($_REQUEST['edit']) ? $_REQUEST['edit'] : $_SESSION['lastSearchId'];
			if ($search->find(true)) {
				// Check permissions
				if ($search->session_id == session_id() || $search->user_id == UserAccount::getActiveUserId()) {
					// Retrieve the search details
					$minSO = unserialize($search->search_object);
					$savedSearch = SearchObjectFactory::deminify($minSO);
					// Make sure it's an advanced search or convert it to advanced
					if ($savedSearch->getSearchType() == 'basic') {
						$savedSearch->convertBasicToAdvancedSearch();
					}

					// Activate facets so we get appropriate descriptions
					// in the filter list:
					$savedSearch->activateAllFacets('Advanced');
					return $savedSearch;

				} else {
					// No permissions
					$interface->assign('editErr', 'noRights');
				}
				// Not found
			} else {
				$interface->assign('editErr', 'notFound');
			}
		}

		return false;
	}

	/**
	 * Process the facets to be used as limits on the Advanced Search screen.
	 *
	 * @access  protected
	 * @param array $facetList The advanced facet values
	 * @param object|boolean $searchObject Saved search object (false if none)
	 * @return  array                   Sorted facets, with selected values flagged.
	 */
	protected function processFacets($facetList, $searchObject = false) {
		// Process the facets, assuming they came back
		$hasSelectedFacet = false;
		$facets = [];
		foreach ($facetList as $facetField => $list) {
			if ($list['label'] instanceof FacetSetting) {
				$facetLabel = $list['label']->displayName;
			} else {
				$facetLabel = $list['label'];
			}
			$facets[$facetField] = $list;
			$currentList = [];
			$valueSelected = false;
			foreach ($list['list'] as $value) {
				// Build the filter string for the URL:
				$fullFilter = $facetField . ':"' . $value['value'] . '"';

				// If we haven't already found a selected facet and the current
				// facet has been applied to the search, we should store it as
				// the selected facet for the current control.
				if ($searchObject && $searchObject->hasFilter($facetField, $value['value'])) {
					$selected = true;
					// Remove the filter from the search object -- we don't want
					// it to show up in the "applied filters" sidebar since it
					// will already be accounted for by being selected in the
					// filter select list!
					$searchObject->removeFilter($fullFilter);
					$valueSelected = true;
					$hasSelectedFacet = true;
				} else {
					$selected = false;
				}
				$currentList[$value['value']] = [
					'filter' => $fullFilter,
					'selected' => $selected,
					'display' => $value['display'],
					'value' => $value['value'],
				];
			}

			$keys = array_keys($currentList);

			//Add a value for not selected which will be the first item
			if (strpos($facetField, 'availability_toggle') === false) {
				// Perform a natural case sort on the array of facet values:
				natcasesort($keys);
				if ($list['label'] instanceof FacetSetting) {
					$facets[$facetField]['values']['Any ' . $list['label']->displayName] = [
						'filter' => '',
						'selected' => !$valueSelected,
						'display' => ''
						/*'Any ' . $list['label']->displayName*/
					];
				} else {
					$facets[$facetField]['values']['Any ' . $list['label']] = [
						'filter' => '',
						'selected' => !$valueSelected,
						'display' => ''
						/*'Any ' . $list['label']*/
					];
				}

			} else {
				//Don't sort Available Now facet and make sure the global (Entire Collection) facet is selected if no value is selected
				global $library;
				$location = Location::getSearchLocation(null);
				if ($location) {
					$superScopeLabel = $location->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $location->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $location->displayName, $localLabel);
					$availableLabel = $location->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $location->displayName, $availableLabel);
					$availableOnlineLabel = $location->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $location->displayName, $availableOnlineLabel);
					$availabilityToggleValue = $location->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				} else {
					$superScopeLabel = $library->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
					$localLabel = $library->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
					$localLabel = str_ireplace('{display name}', $library->displayName, $localLabel);
					$availableLabel = $library->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
					$availableLabel = str_ireplace('{display name}', $library->displayName, $availableLabel);
					$availableOnlineLabel = $library->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
					$availableOnlineLabel = str_ireplace('{display name}', $library->displayName, $availableOnlineLabel);
					$availabilityToggleValue = $library->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				}
				foreach ($currentList as $facetKey => &$facet) {
					if ($facetKey == 'local' || $facetKey == 'Entire Collection') {
						$facet['display'] = $localLabel;
						if (trim($localLabel) == '') {
							unset($currentList[$facetKey]);
						}
					} elseif ($facetKey == 'global' || $facetKey == '') {
						$facet['display'] = $superScopeLabel;
					} elseif ($facetKey == 'available' || $facet['value'] == 'Available Now') {
						$facet['display'] = $availableLabel;
					} elseif ($facet['value'] == 'available_online' || $facet['value'] == 'Available Online') {
						if (strlen($availableOnlineLabel) > 0) {
							$facet['display'] = $availableOnlineLabel;
						} else {
							unset($currentList[$facetKey]);
						}
					}
				}
				if (!$valueSelected) {
					$currentList[$availabilityToggleValue]['selected'] = true;
				}
			}

			$facets[$facetField]['facetName'] = $facetField;
			$facets[$facetField]['facetLabel'] = $facetLabel;
			foreach ($keys as $key) {
				if (isset($currentList[$key])) {
					$facets[$facetField]['values'][$key] = $currentList[$key];
				}
			}
		}
		global $interface;
		$interface->assign('hasSelectedFacet', $hasSelectedFacet);
		return $facets;
	}
}