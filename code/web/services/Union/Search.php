<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009
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

require_once ROOT_DIR . '/Action.php';

/**
 * Union Results
 * Provides a way of unifying searching disparate sources either by
 * providing joined results between the sources or by including results from
 * a single source
 *
 * @author Mark Noble
 *
 */
class Union_Search extends Action {
	function launch(){
		global $module;
		global $action;
		global $interface;
		//Get the search source and determine what to show.
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
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
			$type = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : $_REQUEST['type'];
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
			$results = new Genealogy_Results();
			$results->launch();
		}else if ($searchSource == 'islandora'){
			require_once (ROOT_DIR . '/services/Archive/Results.php');
			$module = 'Archive';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$results = new Archive_Results();
			$results->launch();
		}else if ($searchSource == 'ebsco'){
			require_once (ROOT_DIR . '/services/EBSCO/Results.php');
			$module = 'EBSCO';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$results = new EBSCO_Results();
			$results->launch();
		}else if ($searchSource == 'combinedResults'){
			require_once (ROOT_DIR . '/services/Union/CombinedResults.php');
			$module = 'Union';
			$interface->assign('module', $module);
			$action = 'CombinedResults';
			$interface->assign('action', $action);
			$results = new Union_CombinedResults();
			$results->launch();
		}else{
			require_once (ROOT_DIR . '/services/Search/Results.php');
			$module = 'Search';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$results = new Search_Results();
			$results->launch();
		}
	}
}