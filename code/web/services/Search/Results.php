<?php

require_once ROOT_DIR . '/ResultsAction.php';
require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';

require_once ROOT_DIR . '/sys/Pager.php';

class Search_Results extends ResultsAction {

	function launch() {
		global $interface;
		global $timer;
		global $memoryWatcher;
		global $library;
		global $aspenUsage;
		$aspenUsage->groupedWorkSearches++;

		/** @var string $searchSource */
		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		//Load Placards (do this first so we can test both the original and the replacement term)
		$this->loadPlacards();

		if (isset($_REQUEST['replacementTerm'])){
			$replacementTerm = $_REQUEST['replacementTerm'];
			$interface->assign('replacementTerm', $replacementTerm);
			if (isset($_REQUEST['lookfor'])){
				$oldTerm = $_REQUEST['lookfor'];
				$interface->assign('oldTerm', $oldTerm);
			}

			$_REQUEST['lookfor'] = $replacementTerm;
			$_GET['lookfor'] = $replacementTerm;
			$oldSearchUrl = $_SERVER['REQUEST_URI'];
			$oldSearchUrl = str_replace('replacementTerm=' . urlencode($replacementTerm), 'disallowReplacements', $oldSearchUrl);
			$interface->assign('oldSearchUrl', $oldSearchUrl);
		}

		$interface->assign('showDplaLink', false);
		try {
			require_once ROOT_DIR . '/sys/Enrichment/DPLASetting.php';
			$dplaSetting = new DPLASetting();
			if ($dplaSetting->find(true)) {
				if ($library->includeDplaResults) {
					$interface->assign('showDplaLink', true);
				}
			}
		}catch (Exception $e){
			//This happens before the table is installed
		}

		// Set Show in Search Results Main Details Section options for template
		// (needs to be set before moreDetailsOptions)
		global $library;
		foreach ($library->getGroupedWorkDisplaySettings()->showInSearchResultsMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}


		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		$timer->logTime('Include search engine');
		$memoryWatcher->logMemory('Include search engine');

		//Check to see if the year has been set and if so, convert to a filter and resend.
		$dateFilters = array('publishDate', 'publishDateSort');
		foreach ($dateFilters as $dateFilter){
			if ((isset($_REQUEST[$dateFilter . 'yearfrom']) && !empty($_REQUEST[$dateFilter . 'yearfrom'])) || (isset($_REQUEST[$dateFilter . 'yearto']) && !empty($_REQUEST[$dateFilter . 'yearto']))){
				$queryParams = $_GET;
				$yearFrom = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearfrom']) ? $_REQUEST[$dateFilter . 'yearfrom'] : '*';
				$yearTo = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearto']) ? $_REQUEST[$dateFilter . 'yearto'] : '*';
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
				if (!isset($queryParams['sort'])){
					$queryParams['sort'] = 'year';
				}
				$queryParamStrings = array();
				foreach($queryParams as $paramName => $queryValue){
					if (is_array($queryValue)){
						foreach ($queryValue as $arrayValue){
							if (strlen($arrayValue) > 0){
								$queryParamStrings[] = $paramName . '[]=' . urlencode($arrayValue);
							}
						}
					}else{
						if (strlen($queryValue)){
							$queryParamStrings[] = $paramName . '=' . urlencode($queryValue);
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*'){
					$queryParamStrings[] = "&filter[]=$dateFilter:[$yearFrom+TO+$yearTo]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: /Search/Results?$queryParamString");
				exit;
			}
		}

		$rangeFilters = array('lexile_score', 'accelerated_reader_reading_level', 'accelerated_reader_point_value');
		foreach ($rangeFilters as $filter){
			if ((isset($_REQUEST[$filter . 'from']) && strlen($_REQUEST[$filter . 'from']) > 0) || (isset($_REQUEST[$filter . 'to']) && strlen($_REQUEST[$filter . 'to']) > 0)){
				$queryParams = $_GET;
				$from = (isset($_REQUEST[$filter . 'from']) && preg_match('/^\d+(\.\d*)?$/', $_REQUEST[$filter . 'from'])) ? $_REQUEST[$filter . 'from'] : '*';
				$to = (isset($_REQUEST[$filter . 'to']) && preg_match('/^\d+(\.\d*)?$/', $_REQUEST[$filter . 'to'])) ? $_REQUEST[$filter . 'to'] : '*';

				if ($to != '*' && $from != '*' && $to < $from){
					$tmpFilter = $to;
					$to = $from;
					$from = $tmpFilter;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$filter . 'from']);
				unset($queryParams[$filter . 'to']);
				$queryParamStrings = array();
				foreach($queryParams as $paramName => $queryValue){
					if (is_array($queryValue)){
						foreach ($queryValue as $arrayValue){
							if (strlen($arrayValue) > 0){
								$queryParamStrings[] = $paramName . '[]=' . urlencode($arrayValue);
							}
						}
					}else{
						if (strlen($queryValue)){
							$queryParamStrings[] = $paramName . '=' . urlencode($queryValue);
						}
					}
				}
				if ($from != '*' || $to != '*'){
					$queryParamStrings[] = "&filter[]=$filter:[$from+TO+$to]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: /Search/Results?$queryParamString");
				exit;
			}
		}

		// Cannot use the current search globals since we may change the search term above
		// Display of query is not right when reusing the global search object
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->setPrimarySearch(true);
		$timer->logTime("Init Search Object");
		$memoryWatcher->logMemory("Init Search Object");

		// Build RSS Feed for Results (if requested)
		if ($searchObject->getView() == 'rss') {
			// Throw the XML to screen
			echo $searchObject->buildRSS();
			// And we're done
			exit;
		}else if ($searchObject->getView() == 'excel'){
			// Throw the Excel spreadsheet to screen for download
			$searchObject->buildExcel();
			// And we're done
			exit;
		}
		$displayMode = $searchObject->getView();
		if ($displayMode == 'covers') {
			$searchObject->setLimit(24); // a set of 24 covers looks better in display
		}

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed

		// Hide Covers when the user has set that setting on the Search Results Page
		$this->setShowCovers();

		$displayQuery = $searchObject->displayQuery();
		$pageTitle = 'Search Results';
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());
		$interface->assign('excelLink',  $searchObject->getExcelUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result == null) {
			$timeoutMessage = "Ooops, your search timed out. Try a simpler search if possible.";
			global $configArray;
			if ($configArray['System']['operatingSystem'] == 'linux'){
				//Get the number of CPUs available
				$numCPUs = (int)shell_exec("cat /proc/cpuinfo | grep processor | wc -l");

				//Check load (use the 5 minute load)
				$load = sys_getloadavg();
				$loadPerCpu = $load[1] / $numCPUs;
				if ($loadPerCpu > 1.5){
					$timeoutMessage = "Ooops, your search timed out. Our servers are busy helping other people, please try your search again.";
					$aspenUsage->timedOutSearchesWithHighLoad++;
				}else{
					$aspenUsage->timedOutSearches++;
				}
			}else{
				$aspenUsage->timedOutSearches++;
			}
			$interface->assign('error', $timeoutMessage);
			$this->display('searchError.tpl', 'Error in Search', '');
			return;
		}elseif ($result instanceof AspenError || !empty($result['error'])) {
			$aspenUsage->searchesWithErrors++;
			//Don't record an error, but send it to issues just to be sure everything looks good
			global $serverName;
			$logSearchError = true;
			//Don't send error message for spammy searches
			foreach ($searchObject->getSearchTerms() as $term){
				if (isset($term['lookfor'])) {
					if (strpos($term['lookfor'], 'DBMS_PIPE.RECEIVE_MESSAGE') !== false) {
						$logSearchError = false;
						break;
					} elseif (strpos($term['lookfor'], 'PG_SLEEP') !== false) {
						$logSearchError = false;
						break;
					} elseif (strpos($term['lookfor'], 'SELECT') !== false) {
						$logSearchError = false;
						break;
					} elseif (strpos($term['lookfor'], 'SLEEP') !== false) {
						$logSearchError = false;
						break;
					} elseif (strpos($term['lookfor'], 'ORDER BY') !== false) {
						$logSearchError = false;
						break;
					} elseif (strpos($term['lookfor'], 'WAITFOR') !== false) {
						$logSearchError = false;
						break;
					}
				}
			}

			if ($logSearchError) {
				try{
					require_once ROOT_DIR . '/sys/SystemVariables.php';
					$systemVariables = new SystemVariables();
					if ($systemVariables->find(true) && !empty($systemVariables->searchErrorEmail)) {
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mailer = new Mailer();
						$emailErrorDetails = $_SERVER['REQUEST_URI'] . "\n" . $result['error']['msg'];
						$mailer->send($systemVariables->searchErrorEmail, "$serverName Error processing catalog search", $emailErrorDetails);
					}
				}catch (Exception $e){
					//This happens when the table has not been created
				}
			}

			$interface->assign('searchError', $result);
			$this->getKeywordSearchResults($searchObject, $interface);
			$this->display('searchError.tpl', 'Error in Search', '');
			return;
		}
		$timer->logTime('Process Search');
		$memoryWatcher->logMemory('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('debugTiming',         $searchObject->getDebugTiming());
		$interface->assign('lookfor',             $displayQuery);
		$interface->assign('searchType',          $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex',         $searchObject->getSearchIndex());

		// We'll need recommendations no matter how many results we found:
		$interface->assign('topRecommendations', $searchObject->getRecommendationsTemplates('top'));
		$interface->assign('sideRecommendations', $searchObject->getRecommendationsTemplates('side'));

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();
		$interface->assign('time', round($searchObject->getTotalSpeed(), 2));
		$interface->assign('savedSearch', $searchObject->isSavedSearch());
		$interface->assign('searchId',    $searchObject->getSearchId());
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $currentPage);

		//Enable and disable functionality based on library settings
		//This must be done before we process each result
		$interface->assign('showNotInterested', false);

		$enableProspectorIntegration = ($library->enableProspectorIntegration == 1);
		if ($enableProspectorIntegration){
			$interface->assign('showProspectorLink', true);
			$interface->assign('prospectorSavedSearchId', $searchObject->getSearchId());
		}else{
			$interface->assign('showProspectorLink', false);
		}

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

		//Always get spelling suggestions to account for cases where something is misspelled, but still gets results
        $spellingSuggestions = $searchObject->getSpellingSuggestions();
        $interface->assign('spellingSuggestions', $spellingSuggestions['suggestions']);

        //Look for suggestions for the search (but not if facets are applied)
		$facetSet = $searchObject->getFacetList();
		$hasAppliedFacets = false;
		if (isset($facetSet)){
			foreach ($facetSet as $facet){
				if ($facet['hasApplied']){
					$hasAppliedFacets = true;
				}
			}
		}
		if (!$hasAppliedFacets && $searchObject->getResultTotal() <= 5) {
			require_once ROOT_DIR . '/sys/SearchSuggestions.php';
			$searchSuggestions = new SearchSuggestions();
			$allSuggestions = $searchSuggestions->getAllSuggestions($searchObject->displayQuery(), $searchObject->getSearchIndex(), 'grouped_works');
			$interface->assign('searchSuggestions', $allSuggestions);
		}

		// No Results Actions //
		if ($searchObject->getResultTotal() == 0) {
			//Check to see if we can automatically replace the search with a spelling result
			$disallowReplacements = isset($_REQUEST['disallowReplacements']) || isset($_REQUEST['replacementTerm']);

			if (!$disallowReplacements && !$hasAppliedFacets){
				//We can try to find a suggestion, but only if we are not doing a phrase search.
				if (strpos($searchObject->displayQuery(), '"') === false){
					//If the search is not spelled properly, we can switch to the first spelling result
					if ($spellingSuggestions['correctlySpelled'] == false && $library->allowAutomaticSearchReplacements && count($spellingSuggestions['suggestions']) > 0) {
						$firstSuggestion = reset($spellingSuggestions['suggestions']);
						//first check to see if we will get results
						/** @var SearchObject_GroupedWorkSearcher $replacementSearchObject */
						$replacementSearchObject = SearchObjectFactory::initSearchObject();
						$replacementSearchObject->init($searchSource, $firstSuggestion['phrase']);
						$replacementSearchObject->setPrimarySearch(false);
						$replacementSearchObject->processSearch(true, false);
						if ($replacementSearchObject->getResultTotal() > 0) {
							//Get search results for the new search
							// The above assignments probably do nothing when there is a redirect below
							$thisUrl = $_SERVER['REQUEST_URI'] . "&replacementTerm=" . urlencode($firstSuggestion['phrase']);
							header("Location: " . $thisUrl);
							exit();
						}
					}
					if ($library->allowAutomaticSearchReplacements && !empty($allSuggestions)) {
						$firstSuggestion = reset($allSuggestions);
						$thisUrl = $_SERVER['REQUEST_URI'] . "&replacementTerm=" . urlencode($firstSuggestion['nonHighlightedTerm']);
						header("Location: " . $thisUrl);
						exit();
					}
				}
			}

			$this->getKeywordSearchResults($searchObject, $interface);

			// No record found
			$interface->assign('recordCount', 0);

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
					AspenError::raiseError(new AspenError('Unable to process query<br>' .
                        'Solr Returned: ' . print_r($error, true)));
				}
			}

			$timer->logTime('no hits processing');

		} elseif ($searchObject->getResultTotal() == 1 && (strpos($searchObject->displayQuery(), 'id') === 0 || $searchObject->getSearchType() == 'id')){
			// Exactly One Result //
			//Redirect to the home page for the record
			$recordSet = $searchObject->getResultRecordSet();
			$record = reset($recordSet);
			$_SESSION['searchId'] = $searchObject->getSearchId();
			if ($record['recordtype'] == 'list'){
				$listId = substr($record['id'], 4);
				header("Location: " . "/MyAccount/MyList/{$listId}");
				exit();
			}else{
				header("Location: " . "/Record/{$record['id']}/Home");
				exit();
			}

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$interface->assign('recordCount', $summary['resultTotal']);
			$interface->assign('recordStart', $summary['startRecord']);
			$interface->assign('recordEnd',   $summary['endRecord']);
			$memoryWatcher->logMemory('Get Result Summary');
		}

		// What Mode will search results be Displayed In //
		if ($displayMode == 'covers'){
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles
		} else { // default
			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
			$displayMode = 'list'; // In case the view is not explicitly set, do so now for display & clients-side functions

			// Process Paging (only in list mode)
			if ($searchObject->getResultTotal() > 1 && !empty($summary)) {
				$link    = $searchObject->renderLinkPageTemplate();
				$options = array('totalItems' => $summary['resultTotal'],
				                 'fileName' => $link,
				                 'perPage' => $summary['perPage']);
				$pager   = new Pager($options);
				$interface->assign('pageLinks', $pager->getLinks());
			}
		}
		$timer->logTime('finish hits processing');

		$interface->assign('subpage', $displayTemplate);
		$interface->assign('displayMode', $displayMode); // For user toggle switches

		// Big one - our results //
		$recordSet = $searchObject->getResultRecordHTML();
		$interface->assign('recordSet', $recordSet);
		$timer->logTime('load result records');
		$memoryWatcher->logMemory('load result records');

		//Setup explore more
		$showExploreMoreBar = true;
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1){
			$showExploreMoreBar = false;
		}
		$exploreMore = new ExploreMore();
		$exploreMoreSearchTerm = $exploreMore->getExploreMoreQuery();
		$interface->assign('exploreMoreSection', 'catalog');
		$interface->assign('showExploreMoreBar', $showExploreMoreBar);
		$interface->assign('exploreMoreSearchTerm', $exploreMoreSearchTerm);

		$interface->assign('sectionLabel', 'Library Catalog');
		// Done, display the page
		$sidebar = $searchObject->getResultTotal() > 0 ? 'Search/results-sidebar.tpl' : '';
		$this->display($searchObject->getResultTotal() ? 'list.tpl' : 'list-none.tpl', $pageTitle, $sidebar, false);
	} // End launch()

	/**
	 * @param SearchObject_GroupedWorkSearcher $searchObject
	 * @param UInterface $interface
	 */
	private function getKeywordSearchResults(SearchObject_GroupedWorkSearcher $searchObject, UInterface $interface): void
	{
		//Check to see if we are not using a Keyword search and the Keyword search would provide results
		if (!$searchObject->isAdvanced()) {
			$searchTerms = $searchObject->getSearchTerms();
			if (count($searchTerms) == 1 && $searchTerms[0]['index'] != 'Keyword') {
				$keywordSearchObject = clone $searchObject;
				$keywordSearchObject->setPrimarySearch(false);
				$keywordSearchObject->setSearchTerms(['index' => 'Keyword', 'lookfor' => $searchTerms[0]['lookfor']]);
				$keywordSearchObject->disableSpelling();
				$keywordSearchObject->clearFacets();
				$keywordSearchObject->processSearch(false, false, false);
				if ($keywordSearchObject->getResultTotal() > 0) {
					$interface->assign('keywordResultsLink', $keywordSearchObject->renderSearchUrl());
					$interface->assign('keywordResultsCount', $keywordSearchObject->getResultTotal());
				}
			}
		}
	}

	private function loadPlacards()
	{
		if (empty($_REQUEST['lookfor'])){
			return;
		}
		try {
			$placardToDisplay = null;
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardTrigger.php';

			$trigger = new PlacardTrigger();
			$trigger->whereAdd($trigger->escape($_REQUEST['lookfor'] ) . " like concat('%', triggerWord, '%')");
			$trigger->find();
			while ($trigger->fetch()) {
				if ($trigger->exactMatch == 0 || (strcasecmp($trigger->triggerWord, $_REQUEST['lookfor']) === 0)){
					$placardToDisplay = new Placard();
					$placardToDisplay->id = $trigger->placardId;
					if ($placardToDisplay->find(true)){
						if (!$placardToDisplay->isValidForDisplay()){
							$placardToDisplay = null;
						}
					}else{
						$placardToDisplay = null;
					}
					if ($placardToDisplay != null) {
						break;
					}
				}
			}
			if ($placardToDisplay == null && !empty($_REQUEST['replacementTerm'])) {
				$trigger = new PlacardTrigger();
				$trigger->whereAdd($trigger->escape($_REQUEST['replacementTerm']). " like concat('%', triggerWord, '%')");
				//$trigger->triggerWord = $_REQUEST['replacementTerm'];
				$trigger->find();
				while ($trigger->fetch()) {
					if ($trigger->exactMatch == 0 || (strcasecmp($trigger->triggerWord, $_REQUEST['replacementTerm']) === 0)) {
						$placardToDisplay = new Placard();
						$placardToDisplay->id = $trigger->placardId;
						$placardToDisplay->find(true);
						if (!$placardToDisplay->isValidForDisplay()){
							$placardToDisplay = null;
						}
						if ($placardToDisplay != null) {
							break;
						}
					}
				}
			}
			//TODO: Additional fuzzy matches of the search terms

			if ($placardToDisplay != null) {
				global $interface;
				$interface->assign('placard', $placardToDisplay);
			}
		}catch (Exception $e){
			//Placards are not defined yet
		}
	}

	function getBreadcrumbs() : array
	{
		return parent::getResultsBreadcrumbs('Catalog Search');
	}

}