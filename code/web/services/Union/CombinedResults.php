<?php

class Union_CombinedResults extends Action{
	function launch() {
		global $library;
		global $locationSingleton;
		global $interface;
		if (array_key_exists('lookfor', $_REQUEST)){
			$lookfor = $_REQUEST['lookfor'];
		}else{
			$lookfor = '';
		}
		if (array_key_exists('searchIndex', $_REQUEST)){
			$basicType = $_REQUEST['searchIndex'];
		}else{
			$basicType = 'Keyword';
		}
		$interface->assign('lookfor', $lookfor);
		$interface->assign('basicSearchType', $basicType);

		$location = $locationSingleton->getActiveLocation();
		$combinedResultsName = 'Combined Results';
		if ($location && !$location->useLibraryCombinedResultsSettings){
			$combinedResultsName = $location->combinedResultsLabel;
			$combinedResultSections = $location->combinedResultSections;
		}else if ($library){
			$combinedResultsName = $library->combinedResultsLabel;
			$combinedResultSections = $library->combinedResultSections;
		}

		$interface->assign('combinedResultSections', $combinedResultSections);

		$this->display('combined-results.tpl', $combinedResultsName, '');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb(null, 'Combined Search Results');
		return $breadcrumbs;
	}
}