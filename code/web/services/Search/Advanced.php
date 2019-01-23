<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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

require_once ROOT_DIR . '/services/Search/AdvancedBase.php';
class Search_Advanced extends Search_AdvancedBase {

	function launch()
	{
		global $interface;
		global $searchObject;

		// Create our search object if the one initialized in index.php is not available
		if (!$searchObject) {
			$searchObject = SearchObjectFactory::initSearchObject();
		}
		$searchObject->initAdvancedFacets();
		// We don't want this search in the search history
		$searchObject->disableLogging();
		// Go get the facets
		$searchObject->processSearch();
		$facetList = $searchObject->getFacetList();
		// Shutdown the search object
		$searchObject->close();

		// Load a saved search, if any:
		$savedSearch = $this->loadSavedSearch();

		// Process the facets for appropriate display on the Advanced Search screen:
		$facets = $this->processFacets($facetList, $savedSearch);
		//check to see if we have a facet for format category since we want to show those
		//as icons
		if (array_key_exists('format_category', $facetList)){
			$label = $facetList['format_category']['label'];
			foreach ($facets[$label]['values'] as $key => $optionInfo){
				$optionInfo['imageName'] = str_replace(" ", "", strtolower($key)) . '.png';
				$facets[$label]['values'][$key] = $optionInfo;
			}
			$interface->assign('formatCategoryLimit', $facets[$label]['values']);
			unset($facets[$label]);
		}
		$interface->assign('facetList', $facets);

//		// Integer for % width of each column (be careful to avoid divide by zero!)
//		$columnWidth = (count($facets) > 1) ? round(100 / count($facets), 0) : 0;
//		$interface->assign('columnWidth', $columnWidth);

		// Process settings to control special-purpose facets not supported by the
		//     more generic configuration options.
		$specialFacets = $searchObject->getFacetSetting('Advanced_Settings', 'special_facets');
		if (stristr($specialFacets, 'illustrated')) {
			$interface->assign('illustratedLimit',
			$this->getIllustrationSettings($savedSearch));
		}


		// Send search type settings to the template
		$interface->assign('advSearchTypes', $searchObject->getAdvancedTypes());

		// If we found a saved search, let's assign some details to the interface:
		if ($savedSearch) {
			$interface->assign('searchDetails', $savedSearch->getSearchTerms());
			$interface->assign('searchFilters', $savedSearch->getFilterList());
		}

		$interface->setPageTitle('Advanced Search');
		$interface->setTemplate('advanced.tpl');
		$interface->assign('sidebar', 'Search/results-sidebar.tpl');
		$interface->display('layout.tpl');
	}

	/**
	 * Get the possible legal values for the illustration limit radio buttons.
	 *
	 * @access  private
	 * @param   object  $savedSearch    Saved search object (false if none)
	 * @return  array                   Legal options, with selected value flagged.
	 */
	private function getIllustrationSettings($savedSearch = false)
	{
		$illYes = array('text' => 'Has Illustrations', 'value' => 1, 'selected' => false);
		$illNo = array('text' => 'Not Illustrated', 'value' => 0, 'selected' => false);
		$illAny = array('text' => 'No Preference', 'value' => -1, 'selected' => false);

		// Find the selected value by analyzing facets -- if we find match, remove
		// the offending facet to avoid inappropriate items appearing in the
		// "applied filters" sidebar!
		if ($savedSearch && $savedSearch->hasFilter('illustrated:Illustrated')) {
			$illYes['selected'] = true;
			$savedSearch->removeFilter('illustrated:Illustrated');
		} else if ($savedSearch && $savedSearch->hasFilter('illustrated:"Not Illustrated"')) {
			$illNo['selected'] = true;
			$savedSearch->removeFilter('illustrated:"Not Illustrated"');
		} else {
			$illAny['selected'] = true;
		}
		return array($illYes, $illNo, $illAny);
	}

}
?>