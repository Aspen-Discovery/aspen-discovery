<?php
/**
 * Service to show an Advanced Popup form to streamline the advanced search.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/26/13
 * Time: 9:50 AM
 */

require_once ROOT_DIR . '/services/Search/AdvancedBase.php';
class AdvancedPopup extends Search_AdvancedBase {
	function launch() {
		global $interface;

		// Create our search object
		/** @var SearchObject_Solr|SearchObject_Base $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->initAdvancedFacets();
		// We don't want this search in the search history
		$searchObject->disableLogging();
		// Go get the facets
		$searchObject->processSearch();
		$facetList = $searchObject->getFacetList();
		//print_r($facetList);
		// Shutdown the search object
		$searchObject->close();

		// Load a saved search, if any:
		$savedSearch = $this->loadSavedSearch();
		if ($savedSearch){
			$searchTerms = $savedSearch->getSearchTerms();

			$searchGroups = array();
			$numGroups = 0;
			foreach ($searchTerms as $search){
				$groupStart = true;
				$numItemsInGroup = count($search['group']);
				$curItem = 0;
				foreach ($search['group'] as $group) {
					$searchGroups[$numGroups] = array(
						'groupStart' => $groupStart ? 1 : 0,
						'searchType' => $group['field'],
						'lookfor' => $group['lookfor'],
						'join' => $group['bool'],
						'groupEnd' => ++$curItem === $numItemsInGroup ? 1 : 0
					);

					$groupStart = false;
					$numGroups++;
				}
			}
			$interface->assign('searchGroups', $searchGroups);
		}

		//Get basic search types
		$basicSearchTypes = $searchObject->getBasicTypes();
		$interface->assign('basicSearchTypes', $basicSearchTypes);
		// Send search type settings to the template
		$advSearchTypes = $searchObject->getAdvancedTypes();
		//Remove any basic search types
		foreach ($basicSearchTypes as $basicTypeKey => $basicType){
			unset($advSearchTypes[$basicTypeKey]);
		}
		foreach ($advSearchTypes as $advSearchKey => $label){
			$advSearchTypes[$advSearchKey] = translate($label);
		}
		natcasesort($advSearchTypes);
		$interface->assign('advSearchTypes', $advSearchTypes);

		foreach ($facetList as $facetKey => $facetData){
			$facetList[$facetKey] = translate($facetData['label']);
		}
		natcasesort($facetList);
		$interface->assign('facetList', $facetList);

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$results = array(
				'title' => 'Advanced Search',
				'modalBody' => $interface->fetch("Search/advancedPopup.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Searches.submitAdvancedSearch(); return false;'>Find</span>"
		);
		echo json_encode($results);
	}
}
