<?php

require_once ROOT_DIR . '/ResultsAction.php';
require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/Pager.php';

class Genealogy_Results extends ResultsAction {

	function launch() {
		global $interface;
		global $timer;
		global $aspenUsage;
		$aspenUsage->genealogySearches++;

		//Check to see if a user is logged in with admin permissions
		if (UserAccount::isLoggedIn() && UserAccount::userHasPermission('Administer Genealogy')) {
			$interface->assign('userIsAdmin', true);
		} else {
			$interface->assign('userIsAdmin', false);
		}

		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		//Check to see if the year has been set and if so, convert to a filter and resend.
		$dateFilters = [
			'birthYear',
			'deathYear',
		];
		foreach ($dateFilters as $dateFilter) {
			if (isset($_REQUEST[$dateFilter . 'yearfrom']) || isset($_REQUEST[$dateFilter . 'yearto'])) {
				$queryParams = $_GET;
				$yearFrom = preg_match('/\d{2,4}/', $_REQUEST[$dateFilter . 'yearfrom']) ? $_REQUEST[$dateFilter . 'yearfrom'] : '*';
				$yearTo = preg_match('/\d{2,4}/', $_REQUEST[$dateFilter . 'yearto']) ? $_REQUEST[$dateFilter . 'yearto'] : '*';
				if (strlen($yearFrom) == 2) {
					$yearFrom = '19' . $yearFrom;
				} elseif (strlen($yearFrom) == 3) {
					$yearFrom = '0' . $yearFrom;
				}
				if (strlen($yearTo) == 2) {
					$yearTo = '19' . $yearTo;
				} elseif (strlen($yearFrom) == 3) {
					$yearTo = '0' . $yearTo;
				}
				if ($yearTo != '*' && $yearFrom != '*' && $yearTo < $yearFrom) {
					$tmpYear = $yearTo;
					$yearTo = $yearFrom;
					$yearFrom = $tmpYear;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$dateFilter . 'yearfrom']);
				unset($queryParams[$dateFilter . 'yearto']);
				$queryParamStrings = [];
				foreach ($queryParams as $paramName => $queryValue) {
					if (is_array($queryValue)) {
						foreach ($queryValue as $arrayValue) {
							if (strlen($arrayValue) > 0) {
								$queryParamStrings[] = $paramName . '[]=' . $arrayValue;
							}
						}
					} else {
						if (strlen($queryValue)) {
							$queryParamStrings[] = $paramName . '=' . $queryValue;
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*') {
					$queryParamStrings[] = "&filter[]=$dateFilter:[$yearFrom+TO+$yearTo]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: /Genealogy/Results?$queryParamString");
				exit;
			}
		}

		// Hide Covers when the user has set that setting on the Search Results Page
		$this->setShowCovers();

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GenealogySolrConnector.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		/** @var SearchObject_GenealogySearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Genealogy');
		$searchObject->init($searchSource);
		$searchObject->setPrimarySearch(true);

		// Build RSS Feed for Results (if requested)
		if ($searchObject->getView() == 'rss') {
			// Throw the XML to screen
			echo $searchObject->buildRSS();
			// And we're done
			exit();
		} elseif ($searchObject->getView() == 'excel') {
			// Throw the Excel spreadsheet to screen for download
			$searchObject->buildExcel();
			// And we're done
			exit();
		}

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());
		$interface->assign('excelLink', $searchObject->getExcelUrl());

		$displayQuery = $searchObject->displayQuery();

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result == null) {
			$interface->assign('error', 'The Solr index is offline, please try your search again in a few minutes.');
			$this->display('searchError.tpl', 'Error in Search', '');
			return;
		} elseif ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('lookfor', $searchObject->displayQuery());
		$interface->assign('searchType', $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex', $searchObject->getSearchIndex());

		//Always get spelling suggestions to account for cases where something is misspelled, but still gets results
		$spellingSuggestions = $searchObject->getSpellingSuggestions();
		$interface->assign('spellingSuggestions', $spellingSuggestions['suggestions']);

		// We'll need recommendations no matter how many results we found:
		$interface->assign('topRecommendations', $searchObject->getRecommendationsTemplates('top'));
		$interface->assign('sideRecommendations', $searchObject->getRecommendationsTemplates('side'));

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();
		$interface->assign('time', round($searchObject->getTotalSpeed(), 2));
		$interface->assign('savedSearch', $searchObject->isSavedSearch());
		$interface->assign('searchId', $searchObject->getSearchId());
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $currentPage);

		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$interface->assign('subpage', 'Genealogy/list-none.tpl');
			$interface->setTemplate('Genealogy/list.tpl');
			$interface->assign('recordCount', 0);

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error['msg'], 'org.apache.lucene.queryParser.ParseException') || preg_match('/^undefined field/', $error['msg']) || stristr($error['msg'], 'org.apache.solr.search.SyntaxError')) {
					$interface->assign('parseError', $error['msg']);

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					AspenError::raiseError(new AspenError('Unable to process query<br>' . 'Solr Returned: ' . $error['msg']));
				}
			}

			$timer->logTime('no hits processing');

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$interface->assign('recordCount', $summary['resultTotal']);
			$interface->assign('recordStart', $summary['startRecord']);
			$interface->assign('recordEnd', $summary['endRecord']);

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
					AspenError::raiseError(new AspenError('Unable to process query<br>' . 'Solr Returned: ' . print_r($error, true)));
				}
			}

			$facetSet = $searchObject->getFacetList();
			$interface->assign('facetSet', $facetSet);

			//Check to see if a format category is already set
			$categorySelected = false;
			if (isset($facetSet['top'])) {
				foreach ($facetSet['top'] as $title => $cluster) {
					if ($cluster['label'] == 'Category') {
						foreach ($cluster['list'] as $thisFacet) {
							if ($thisFacet['isApplied']) {
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
			$interface->assign('subpage', 'Genealogy/list-list.tpl');
			$interface->setTemplate('Genealogy/list.tpl');

			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = [
				'totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage'],
			];
			$pager = new Pager($options);
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
		$sidebar = $searchObject->getResultTotal() > 0 ? 'Search/results-sidebar.tpl' : '';
		$this->display($searchObject->getResultTotal() ? 'list.tpl' : 'list-none.tpl', $displayQuery, $sidebar, false);
	}

	function getBreadcrumbs(): array {
		return parent::getResultsBreadcrumbs('Genealogy Search');
	}
}