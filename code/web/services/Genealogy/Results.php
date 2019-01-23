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
require_once ROOT_DIR . '/services/MyResearch/lib/Search.php';

require_once ROOT_DIR . '/sys/Pager.php';

class Genealogy_Results extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $timer;
		$user = UserAccount::getLoggedInUser();
		global $analytics;

		//Check to see if a user is logged in with admin permissions
		if (UserAccount::isLoggedIn() && UserAccount::userHasRole('genealogyContributor')){
			$interface->assign('userIsAdmin', true);
		}else{
			$interface->assign('userIsAdmin', false);
		}

		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		//Check to see if the year has been set and if so, convert to a filter and resend.
		$dateFilters = array('birthYear', 'deathYear');
		foreach ($dateFilters as $dateFilter){
			if (isset($_REQUEST[$dateFilter . 'yearfrom']) || isset($_REQUEST[$dateFilter . 'yearto'])){
				$queryParams = $_GET;
				$yearFrom = preg_match('/\d{2,4}/', $_REQUEST[$dateFilter . 'yearfrom']) ? $_REQUEST[$dateFilter . 'yearfrom'] : '*';
				$yearTo = preg_match('/\d{2,4}/', $_REQUEST[$dateFilter . 'yearto']) ? $_REQUEST[$dateFilter . 'yearto'] : '*';
				if (strlen($yearFrom) == 2){
					$yearFrom = '19' . $yearFrom;
				}else if (strlen($yearFrom) == 3){
					$yearFrom = '0' . $yearFrom;
				}
				if (strlen($yearTo) == 2){
					$yearTo = '19' . $yearTo;
				}else if (strlen($yearFrom) == 3){
					$yearTo = '0' . $yearTo;
				}
				if ($yearTo != '*' && $yearFrom != '*' && $yearTo < $yearFrom){
					$tmpYear = $yearTo;
					$yearTo = $yearFrom;
					$yearFrom = $tmpYear;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$dateFilter . 'yearfrom']);
				unset($queryParams[$dateFilter . 'yearto']);
				$queryParamStrings = array();
				foreach($queryParams as $paramName => $queryValue){
					if (is_array($queryValue)){
						foreach ($queryValue as $arrayValue){
							if (strlen($arrayValue) > 0){
								$queryParamStrings[] = $paramName . '[]=' . $arrayValue;
							}
						}
					}else{
						if (strlen($queryValue)){
							$queryParamStrings[] = $paramName . '=' . $queryValue;
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*'){
					$queryParamStrings[] = "&filter[]=$dateFilter:[$yearFrom+TO+$yearTo]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: {$configArray['Site']['path']}/Genealogy/Results?$queryParamString");
				exit;
			}
		}

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/' . $configArray['Genealogy']['engine'] . '.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		/** @var SearchObject_Genealogy $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject($configArray['Genealogy']['searchObject']);
		$searchObject->init($searchSource);
		$searchObject->setPrimarySearch(true);

		// Build RSS Feed for Results (if requested)
		if ($searchObject->getView() == 'rss') {
			// Throw the XML to screen
			echo $searchObject->buildRSS();
			// And we're done
			exit();
		}else if ($searchObject->getView() == 'excel'){
			// Throw the Excel spreadsheet to screen for download
			echo $searchObject->buildExcel();
			// And we're done
			exit();
		}

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());
		$interface->assign('excelLink',  $searchObject->getExcelUrl());

		$displayQuery = $searchObject->displayQuery();
		$pageTitle = $displayQuery;
		if (strlen($pageTitle) > 20){
			$pageTitle = substr($pageTitle, 0, 20) . '...';
		}
		$pageTitle .= ' | Search Results';

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('qtime',               round($searchObject->getQuerySpeed(), 2));
		$interface->assign('spellingSuggestions', $searchObject->getSpellingSuggestions());
		$interface->assign('lookfor',             $searchObject->displayQuery());
		$interface->assign('searchType',          $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex',         $searchObject->getSearchIndex());

		// We'll need recommendations no matter how many results we found:
		$interface->assign('topRecommendations', $searchObject->getRecommendationsTemplates('top'));
		$interface->assign('sideRecommendations', $searchObject->getRecommendationsTemplates('side'));

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();
		$interface->assign('time', round($searchObject->getTotalSpeed(), 2));
		// Show the save/unsave code on screen
		// The ID won't exist until after the search has been put in the search history
		//    so this needs to occur after the close() on the searchObject
		$interface->assign('showSaved',   true);
		$interface->assign('savedSearch', $searchObject->isSavedSearch());
		$interface->assign('searchId',    $searchObject->getSearchId());
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $currentPage);

		$allSearchSources = SearchSources::getSearchSources();
		$translatedScope = $allSearchSources[$searchSource]['name'];
		$analytics->addSearch($translatedScope, $searchObject->displayQuery(), $searchObject->isAdvanced(), $searchObject->getFullSearchType(), $searchObject->hasAppliedFacets(), $searchObject->getResultTotal());
		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$interface->assign('sitepath', $configArray['Site']['path']);
			$interface->assign('subpage', 'Genealogy/list-none.tpl');
			$interface->setTemplate('Genealogy/list.tpl');
			$interface->assign('recordCount', 0);

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error['msg'], 'org.apache.lucene.queryParser.ParseException')
						|| preg_match('/^undefined field/', $error['msg'])
						|| stristr($error['msg'], 'org.apache.solr.search.SyntaxError')
						) {
					$interface->assign('parseError', $error['msg']);

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					PEAR_Singleton::raiseError(new PEAR_Error('Unable to process query<br>' . 'Solr Returned: ' . $error['msg']));
				}
			}

			$timer->logTime('no hits processing');

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$interface->assign('recordCount', $summary['resultTotal']);
			$interface->assign('recordStart', $summary['startRecord']);
			$interface->assign('recordEnd',   $summary['endRecord']);

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error['msg'], 'org.apache.lucene.queryParser.ParseException') || preg_match('/^undefined field/', $error['msg'])) {
					$interface->assign('parseError', $error['msg']);

					if (preg_match('/^undefined field/', $error['msg'])) {
						// Setup to try as a possible subtitle search
						$fieldName = trim(str_replace('undefined field', '', $error['msg'], $replaced)); // strip out the phrase 'undefined field' to get just the fieldname
						$original = urlencode("$fieldName:");
						if ($replaced === 1 && !empty($fieldName) && strpos($_SERVER['REQUEST_URI'], $original)) {
							// ensure only 1 replacement was done, that the fieldname isn't an empty string, and the label is in fact in the Search URL
							$new = urlencode("$fieldName :"); // include space in between the field name & colon to avoid the parse error
							$thisUrl = str_replace($original, $new, $_SERVER['REQUEST_URI'], $replaced);
							if ($replaced === 1) { // ensure only one modification was made
								header("Location: " . $thisUrl);
								exit();
							}
						}
					}

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					PEAR_Singleton::raiseError(new PEAR_Error('Unable to process query<br>' .
							'Solr Returned: ' . print_r($error, true)));
				}
			}

			$facetSet = $searchObject->getFacetList();
			$interface->assign('facetSet',       $facetSet);

			//Check to see if a format category is already set
			$categorySelected = false;
			if (isset($facetSet['top'])){
				foreach ($facetSet['top'] as $title=>$cluster){
					if ($cluster['label'] == 'Category'){
						foreach ($cluster['list'] as $thisFacet){
							if ($thisFacet['isApplied']){
								$categorySelected = true;
							}
						}
					}
				}
			}
			$interface->assign('categorySelected', $categorySelected);
			$timer->logTime('load selected category');

			// Big one - our results
			$recordSet = $searchObject->getResultRecordHTML();
			$interface->assign('recordSet', $recordSet);
			$timer->logTime('load result records');

			// Setup Display
			$interface->assign('sitepath', $configArray['Site']['path']);
			$interface->assign('subpage', 'Genealogy/list-list.tpl');
			$interface->setTemplate('Genealogy/list.tpl');

			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
			                 'fileName'   => $link,
			                 'perPage'    => $summary['perPage']);
			$pager = new VuFindPager($options);
			$interface->assign('pageLinks', $pager->getLinks());
			$timer->logTime('finish hits processing');
		}

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

		// Done, display the page
		$interface->assign('sectionLabel', 'Genealogy Database');
		$interface->assign('sidebar', 'Search/results-sidebar.tpl');
		$this->display($searchObject->getResultTotal() ? 'list.tpl' : 'list-none.tpl', $pageTitle, 'Search/results-sidebar.tpl');
	} // End launch()
}