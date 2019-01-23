<?php
/**
 * Base class for Advanced Searches
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/27/13
 * Time: 10:10 AM
 */

require_once ROOT_DIR . '/Action.php';

abstract class Search_AdvancedBase extends Action{
	/**
	 * Load a saved search, if appropriate and legal; assign an error to the
	 * interface if necessary.
	 *
	 * @access  protected
	 * @return  SearchObject_Base|boolean mixed           Search Object on successful load, false otherwise
	 */
	protected function loadSavedSearch()
	{
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
	 * @param   array   $facetList      The advanced facet values
	 * @param   object|boolean  $searchObject   Saved search object (false if none)
	 * @return  array                   Sorted facets, with selected values flagged.
	 */
	protected function processFacets($facetList, $searchObject = false)
	{
		// Process the facets, assuming they came back
		$facets = array();
		foreach ($facetList as $facet => $list) {
			$currentList = array();
			$valueSelected = false;
			foreach ($list['list'] as $value) {
				// Build the filter string for the URL:
				$fullFilter = $facet.':"'.$value['value'].'"';

				// If we haven't already found a selected facet and the current
				// facet has been applied to the search, we should store it as
				// the selected facet for the current control.
				if ($searchObject && $searchObject->hasFilter($fullFilter)) {
					$selected = true;
					// Remove the filter from the search object -- we don't want
					// it to show up in the "applied filters" sidebar since it
					// will already be accounted for by being selected in the
					// filter select list!
					$searchObject->removeFilter($fullFilter);
					$valueSelected = true;
				} else {
					$selected = false;
				}
				$currentList[$value['value']] = array('filter' => $fullFilter, 'selected' => $selected);
			}

			$keys = array_keys($currentList);

			//Add a value for not selected which will be the first item
			if (strpos($facet, 'availability_toggle') === false){
				// Perform a natural case sort on the array of facet values:
				natcasesort($keys);

				$facets[$list['label']]['values']['Any ' . $list['label']] = array('filter' => '','selected' => !$valueSelected );
			}else{
				//Don't sort Available Now facet and make sure the Entire Collection is selected if no value is selected
				if (!$valueSelected){
					foreach ($currentList as $key => $value){
						if ($key == 'Entire Collection'){
							$currentList[$key]['selected'] = true;
						}
					}
				}
			}

			$facets[$list['label']]['facetName'] = $facet;
			foreach($keys as $key) {
				$facets[$list['label']]['values'][$key] = $currentList[$key];
			}
		}
		return $facets;
	}
}