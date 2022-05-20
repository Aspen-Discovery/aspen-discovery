<?php

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
		$searchObject->processSearch(false, true);
		$facetList = $searchObject->getFacetList();
		// Shutdown the search object
		$searchObject->close();

		$searchIndex = 'advanced';
		if (isset($_REQUEST['searchIndex'])){
			$searchIndex = $_REQUEST['searchIndex'];
		}

		if ($searchIndex == 'editAdvanced'){
			// Load a saved search, if any:
			$savedSearch = $this->loadSavedSearch();
		}else{
			if (isset($_REQUEST['lookfor'])){
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
				$savedSearch = SearchObjectFactory::initSearchObject();
				$interface->assign('lookfor', $_REQUEST['lookfor']);
				$savedSearch->setSearchTerms([
					'index' => 'Keyword',
					'lookfor' => $_REQUEST['lookfor']
				]);
				$savedSearch->convertBasicToAdvancedSearch();
			}else{
				$savedSearch = false;
			}
		}


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

		// Send search type settings to the template
		$interface->assign('advSearchTypes', $searchObject->getAdvancedTypes());

		// If we found a saved search, let's assign some details to the interface:
		if ($savedSearch) {
			$interface->assign('searchDetails', $savedSearch->getSearchTerms());
			$interface->assign('searchFilters', $savedSearch->getFilterList());
		}

		$this->display('advanced.tpl', 'Advanced Search', '');
	}
	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Catalog Advanced Search');
		return $breadcrumbs;
	}
}