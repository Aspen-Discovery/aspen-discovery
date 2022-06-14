<?php

require_once ROOT_DIR . '/ResultsAction.php';

/**
 * Union Results
 * Provides a way of unifying searching disparate sources either by
 * providing joined results between the sources or by including results from
 * a single source
 */
class Union_Search extends ResultsAction {
	/** @var Action */
	private $searchResultsAction;
	function launch(){
		global $module;
		global $action;
		global $interface;
		//Get the search source and determine what to show.
		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$searchSources = new SearchSources();
		$searches = $searchSources->getSearchSources();
		if (!isset($searches[$searchSource]) && $searchSource == 'marmot'){
			$searchSource = 'local';
		}
		$searchInfo = $searches[$searchSource];
		if (isset($searchInfo['external']) && $searchInfo['external'] == true){
			//Reset to a local search source so the external search isn't remembered
			$_SESSION['searchSource'] = 'local';
			//Need to redirect to the appropriate search location with the new value for look for
			$type = isset($_REQUEST['searchIndex']) ? $_REQUEST['searchIndex'] : 'Keyword';
			$lookfor = isset($_REQUEST['lookfor']) ? $_REQUEST['lookfor'] : '';
			$link = $searchSources->getExternalLink($searchSource, $type, $lookfor);
			header('Location: ' . $link);
			die();
		}else if ($searchSource == 'genealogy'){
			require_once (ROOT_DIR . '/services/Genealogy/Results.php');
			$module = 'Genealogy';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new Genealogy_Results();
			$this->searchResultsAction->launch();
		} else if ($searchSource == 'open_archives') {
			require_once(ROOT_DIR . '/services/OpenArchives/Results.php');
			$module = 'OpenArchives';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new OpenArchives_Results();
			$this->searchResultsAction->launch();
		} else if ($searchSource == 'lists') {
			require_once(ROOT_DIR . '/services/Lists/Results.php');
			$module = 'Lists';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new Lists_Results();
			$this->searchResultsAction->launch();
		} else if ($searchSource == 'course_reserves') {
			require_once(ROOT_DIR . '/services/CourseReserves/Results.php');
			$module = 'CourseReserves';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new CourseReserves_Results();
			$this->searchResultsAction->launch();
		}else if ($searchSource == 'websites'){
			require_once(ROOT_DIR . '/services/Websites/Results.php');
			$module = 'Websites';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new Websites_Results();
			$this->searchResultsAction->launch();
		}else if ($searchSource == 'events'){
			require_once(ROOT_DIR . '/services/Events/Results.php');
			$module = 'Events';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new Events_Results();
			$this->searchResultsAction->launch();
		}else if ($searchSource == 'ebsco_eds'){
			require_once (ROOT_DIR . '/services/EBSCO/Results.php');
			$module = 'EBSCO';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new EBSCO_Results();
			$this->searchResultsAction->launch();
		}else if ($searchSource == 'ebscohost'){
			require_once (ROOT_DIR . '/services/EBSCOhost/Results.php');
			$module = 'EBSCOhost';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$this->searchResultsAction = new EBSCOhost_Results();
			$this->searchResultsAction->launch();
		}else if ($searchSource == 'combined'){
			require_once (ROOT_DIR . '/services/Union/CombinedResults.php');
			$module = 'Union';
			$interface->assign('module', $module);
			$action = 'CombinedResults';
			$interface->assign('action', $action);
			$this->searchResultsAction = new Union_CombinedResults();
			$this->searchResultsAction->launch();
		}else{
			if (isset($_REQUEST['searchIndex'])) {
				$searchIndex = $_REQUEST['searchIndex'];
			}else{
				$searchIndex = 'Keyword';
			}
			if ($searchIndex == 'advanced' || $searchIndex == 'editAdvanced'){
				require_once (ROOT_DIR . '/services/Search/Advanced.php');
				$module = 'Search';
				$interface->assign('module', $module);
				$action = 'Advanced';
				$interface->assign('action', $action);
				$this->searchResultsAction = new Search_Advanced();
				$this->searchResultsAction->launch();
			}else{
				require_once (ROOT_DIR . '/services/Search/Results.php');
				$module = 'Search';
				$interface->assign('module', $module);
				$action = 'Results';
				$interface->assign('action', $action);
				$this->searchResultsAction = new Search_Results();
				$this->searchResultsAction->launch();
			}
		}
	}

	function getBreadcrumbs() : array
	{
		return $this->searchResultsAction->getBreadcrumbs();
	}
}