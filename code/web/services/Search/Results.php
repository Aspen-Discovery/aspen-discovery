<?php

require_once ROOT_DIR . '/ResultsAction.php';
require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/InterLibraryLoan/InnReach.php';

require_once ROOT_DIR . '/sys/Pager.php';

class Search_Results extends ResultsAction {

	function launch() : void {
		global $interface;
		global $timer;
		global $memoryWatcher;
		global $library;
		global $aspenUsage;
		$aspenUsage->groupedWorkSearches++;

		$this->validateAndProcessSearchParameters();

		/** @var string $searchSource */
		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		//Load Placards (do this first so we can test both the original and the replacement term)
		$this->loadPlacards();

		if (isset($_REQUEST['replacementTerm'])) {
			$replacementTerm = $_REQUEST['replacementTerm'];
			$interface->assign('replacementTerm', $replacementTerm);
			if (isset($_REQUEST['lookfor'])) {
				$oldTerm = $_REQUEST['lookfor'];
				$interface->assign('oldTerm', $oldTerm);
			}

			$_REQUEST['lookfor'] = $replacementTerm;
			$_GET['lookfor'] = $replacementTerm;
			$oldSearchUrl = $_SERVER['REQUEST_URI'];
			$oldSearchUrl = str_replace('replacementTerm=' . urlencode($replacementTerm), 'disallowReplacements', $oldSearchUrl);
			$interface->assign('oldSearchUrl', $oldSearchUrl);
		}
		if (isset($_REQUEST['replacedIndex'])) {
			$replacedIndex = $_REQUEST['replacedIndex'];
			$interface->assign('replacedIndex', $replacedIndex);

			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchIndexes = $searchObject->getSearchIndexes();
			$interface->assign('replacedIndexLabel', $searchIndexes[$replacedIndex]);

			$oldSearchUrl = $_SERVER['REQUEST_URI'];
			$oldSearchUrl = preg_replace('/searchIndex=Keyword/', 'searchIndex=' . $replacedIndex, $oldSearchUrl);
			$_REQUEST['searchIndex'] = 'Keyword';
			$oldSearchUrl = preg_replace('/sort=.+?(&|$)/', '', $oldSearchUrl);
			unset($_REQUEST['sort']);
			$oldSearchUrl = preg_replace("/[?&]replacedIndex=$replacedIndex/", '', $oldSearchUrl);
			$interface->assign('oldSearchUrl', $oldSearchUrl);
		}
		if (isset($_REQUEST['replacedScope'])) {
			$replacedScope = $_REQUEST['replacedScope'];
			$interface->assign('replacedScope', $replacedScope);

			[
				$superScopeLabel,
				$localLabel,
				$availableLabel,
				$availableOnlineLabel,
			] = $this->getAvailabilityToggleLabels();
			switch ($replacedScope) {
				case 'local':
					$replacedScopeLabel = $localLabel;
					break;
				case 'available':
					$replacedScopeLabel = $availableLabel;
					break;
				case 'available_online':
					$replacedScopeLabel = $availableOnlineLabel;
					break;
				default:
					$replacedScopeLabel = 'Unknown';
			}
			$interface->assign('replacedScope', $replacedScope);
			$interface->assign('replacedScopeLabel', $replacedScopeLabel);
			$interface->assign('globalScopeLabel', $superScopeLabel);

			$oldSearchUrl = urldecode($_SERVER['REQUEST_URI']);
			$oldSearchUrl = preg_replace('/availability_toggle:"global"/', 'availability_toggle:"' . $replacedScope . '"', $oldSearchUrl);
			$oldSearchUrl = preg_replace("/[?&]replacedScope=$replacedScope/", '', $oldSearchUrl);
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
		} catch (Exception $e) {
			//This happens before the table is installed
		}

		// Set Show in Search Results Main Details Section options for template
		// (needs to be set before moreDetailsOptions)
		global $library;
		$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		foreach ($groupedWorkDisplaySettings->showInSearchResultsMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}
		$interface->assign('formatDisplayStyle', $groupedWorkDisplaySettings->formatDisplayStyle);


		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
		$timer->logTime('Include search engine');
		$memoryWatcher->logMemory('Include search engine');

		//Check to see if the year has been set and if so, convert to a filter and resend.
		$dateFilters = [
			'publishDate',
			'publishDateSort',
		];
		foreach ($dateFilters as $dateFilter) {
			if ((isset($_REQUEST[$dateFilter . 'yearfrom']) && !empty($_REQUEST[$dateFilter . 'yearfrom'])) || (isset($_REQUEST[$dateFilter . 'yearto']) && !empty($_REQUEST[$dateFilter . 'yearto']))) {
				$queryParams = $_GET;
				$yearFrom = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearfrom']) ? $_REQUEST[$dateFilter . 'yearfrom'] : '*';
				$yearTo = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearto']) ? $_REQUEST[$dateFilter . 'yearto'] : '*';
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
				if (!isset($queryParams['sort'])) {
					$queryParams['sort'] = 'year';
				}
				$queryParamStrings = [];
				foreach ($queryParams as $paramName => $queryValue) {
					if (is_array($queryValue)) {
						foreach ($queryValue as $arrayValue) {
							if (strlen($arrayValue) > 0) {
								$queryParamStrings[] = $paramName . '[]=' . urlencode($arrayValue);
							}
						}
					} else {
						if (strlen($queryValue)) {
							$queryParamStrings[] = $paramName . '=' . urlencode($queryValue);
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*') {
					$queryParamStrings[] = "&filter[]=$dateFilter:[$yearFrom+TO+$yearTo]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: /Search/Results?$queryParamString");
				exit;
			}
		}

		$rangeFilters = [
			'lexile_score',
			'accelerated_reader_reading_level',
			'accelerated_reader_point_value',
		];
		foreach ($rangeFilters as $filter) {
			if ((isset($_REQUEST[$filter . 'from']) && strlen($_REQUEST[$filter . 'from']) > 0) || (isset($_REQUEST[$filter . 'to']) && strlen($_REQUEST[$filter . 'to']) > 0)) {
				$queryParams = $_GET;
				$from = (isset($_REQUEST[$filter . 'from']) && preg_match('/^\d+(\.\d*)?$/', $_REQUEST[$filter . 'from'])) ? $_REQUEST[$filter . 'from'] : '*';
				$to = (isset($_REQUEST[$filter . 'to']) && preg_match('/^\d+(\.\d*)?$/', $_REQUEST[$filter . 'to'])) ? $_REQUEST[$filter . 'to'] : '*';

				if ($to != '*' && $from != '*' && $to < $from) {
					$tmpFilter = $to;
					$to = $from;
					$from = $tmpFilter;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$filter . 'from']);
				unset($queryParams[$filter . 'to']);
				$queryParamStrings = [];
				foreach ($queryParams as $paramName => $queryValue) {
					if (is_array($queryValue)) {
						foreach ($queryValue as $arrayValue) {
							if (strlen($arrayValue) > 0) {
								$queryParamStrings[] = $paramName . '[]=' . urlencode($arrayValue);
							}
						}
					} else {
						if (strlen($queryValue)) {
							$queryParamStrings[] = $paramName . '=' . urlencode($queryValue);
						}
					}
				}
				if ($from != '*' || $to != '*') {
					$queryParamStrings[] = "&filter[]=$filter:[$from+TO+$to]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: /Search/Results?$queryParamString");
				exit;
			}
		}

		//Set default sort by setting the request variable so the init grabs it
		if (!array_key_exists('sort', $_REQUEST) && UserAccount::isLoggedIn()) {
			$userId = UserAccount::getActiveUserId();
			require_once ROOT_DIR . '/sys/User/PageDefaults.php';
			$pageDefaults = PageDefaults::getPageDefaultsForUser($userId, 'Search', 'Results', null);
			if ($pageDefaults != null) {
				$_REQUEST['sort'] = $pageDefaults->pageSort;
			}
		}

		// Cannot use the current search globals since we may change the search term above
		// Display of query is not right when reusing the global search object
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
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
		} elseif ($searchObject->getView() == 'excel') {
			// Throw the Excel spreadsheet to screen for download
			$searchObject->buildExcel();
			// And we're done
			exit;
		} elseif ($searchObject->getView() == 'ris') {
			$searchObject->buildRisExport();
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
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());
		$interface->assign('excelLink', $searchObject->getExcelUrl());
		$interface->assign('risLink', $searchObject->getRisUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result == null) {
			$timeoutMessage = "Ooops, your search timed out. Try a simpler search if possible.";
			global $configArray;
			if ($configArray['System']['operatingSystem'] == 'linux') {
				//Get the number of CPUs available
				$numCPUs = (int)shell_exec("cat /proc/cpuinfo | grep processor | wc -l");

				//Check load (use the 5 minute load)
				$load = sys_getloadavg();
				$loadPerCpu = $load[1] / $numCPUs;
				if ($loadPerCpu > 1.5) {
					$timeoutMessage = "Ooops, your search timed out. Our servers are busy helping other people, please try your search again.";
					$aspenUsage->timedOutSearchesWithHighLoad++;
				} else {
					$aspenUsage->timedOutSearches++;
				}
			} else {
				$aspenUsage->timedOutSearches++;
			}
			$interface->assign('error', $timeoutMessage);
			$this->display('searchError.tpl', 'Error in Search', '');
			return;
		} elseif ($result instanceof AspenError || !empty($result['error'])) {
			$aspenUsage->searchesWithErrors++;
			//Don't record an error, but send it to issues just to be sure everything looks good
			global $serverName;
			$logSearchError = true;
			//Don't send error message for spammy searches
			foreach ($searchObject->getSearchTerms() as $term) {
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
					} elseif (strpos($term['lookfor'], 'nvOpzp') !== false) {
						$logSearchError = false;
						break;
					}
				}
			}

			if ($logSearchError) {
				try {
					require_once ROOT_DIR . '/sys/SystemVariables.php';
					$systemVariables = new SystemVariables();
					if ($systemVariables->find(true) && !empty($systemVariables->searchErrorEmail)) {
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mailer = new Mailer();
						$emailErrorDetails = $_SERVER['REQUEST_URI'] . "\nIP Address: " . IPAddress::getActiveIp() . "\n" . $result['error']['msg'];
						$mailer->send($systemVariables->searchErrorEmail, "$serverName Error processing catalog search", $emailErrorDetails);
					}
				} catch (Exception $e) {
					//This happens when the table has not been created
				}
			}

			$interface->assign('searchError', $result);
			if (!$this->getGlobalSearchResults($searchObject, $interface)) {
				$this->getKeywordSearchResults($searchObject, $interface);
			}
			$this->display('searchError.tpl', 'Error in Search', '');
			return;
		}
		$timer->logTime('Process Search');
		$memoryWatcher->logMemory('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('debugTiming', $searchObject->getDebugTiming());
		$interface->assign('lookfor', $displayQuery);
		$interface->assign('searchType', $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex', $searchObject->getSearchIndex());

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

		//Enable and disable functionality based on library settings
		//This must be done before we process each result
		$interface->assign('showNotInterested', false);

		$enableInnReachIntegration = ($library->enableInnReachIntegration == 1);
		if ($enableInnReachIntegration) {
			$interface->assign('showInnReachLink', true);
			$interface->assign('innReachSavedSearchId', $searchObject->getSearchId());
		} else {
			$interface->assign('showInnReachLink', false);
		}
		$enableShareItIntegration = ($library->ILLSystem == 3);
		if ($enableShareItIntegration) {
			$interface->assign('showShareItLink', true);
			$interface->assign('shareItSavedSearchId', $searchObject->getSearchId());
		} else {
			$interface->assign('showShareItLink', false);
		}
		global $enabledModules;
		if (array_key_exists('Talpa Search', $enabledModules)) {
			require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
			$_talpaSearchObject = SearchObjectFactory::initSearchObject("Talpa");
			$_talpaSearchObject->setSearchSource('talpa');
			$_talpaSearchObject->setBasicQuery($searchObject->getQuery(), 'title');
			$talpaSearchUrl = $_talpaSearchObject->renderSearchUrl();
			$talpaSearchUrl = str_replace('/Search/Results','/Union/Search', $talpaSearchUrl);
			$interface->assign('talpaSearchLink', $talpaSearchUrl);

			//Retrieve Talpa Display settings to use in result.tpl

			require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
			if ($library->talpaSettingsId != -1) {
				$talpaSettings = new TalpaSettings();
				$talpaSettings->id = $library->talpaSettingsId;
				if (!$talpaSettings->find(true)) {
					$talpaSettings = null;
				} else {
					$interface->assign('talpaTryItButton', $talpaSettings->talpaTryItButton);
					$interface->assign('tryThisSearchInTalpaText', $talpaSettings->tryThisSearchInTalpaText?:'Try this search in Talpa');
					$interface->assign('tryThisSearchInTalpaSidebarSwitch', $talpaSettings->tryThisSearchInTalpaSidebarSwitch);
					$interface->assign('tryThisSearchInTalpaNoResultsSwitch', $talpaSettings->tryThisSearchInTalpaNoResultsSwitch);
					$interface->assign('talpaExplainerText', $talpaSettings->talpaExplainerText);
				}
			}
		}

		// Save the ID of this search to the session so we can return to it easily.
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily.
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();
		$_SESSION['returnToModule'] = 'Search';
		$_SESSION['returnToAction'] = 'Results';

		//Always get spelling suggestions to account for cases where something is misspelled, but still gets results
		$spellingSuggestions = $searchObject->getSpellingSuggestions();
		$interface->assign('spellingSuggestions', $spellingSuggestions['suggestions']);

		//Look for suggestions for the search (but not if facets are applied)
		$facetSet = $searchObject->getFacetList();
		$hasAppliedFacets = $searchObject->hasAppliedFacets();
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

			//These are ok to do even if facets are applied
			if ($library->allowAutomaticSearchReplacements) {
				if (!$this->getGlobalSearchResults($searchObject, $interface)) {
					if ($this->getKeywordSearchResults($searchObject, $interface)) {
						if (!$disallowReplacements) {
							//We can automatically redirect to the keyword scope
							$newUrl = $interface->getVariable('keywordResultsLink');
							if (strpos($newUrl, '?') !== false) {
								$newUrl .= '&disallowReplacements&replacedIndex=' . $interface->getVariable('originalSearchIndex');
							} else {
								$newUrl .= '?disallowReplacements&replacedIndex=' . $interface->getVariable('originalSearchIndex');
							}
							header("Location: " . $newUrl);
							exit();
						}
					}
				} else {
					if (!$disallowReplacements) {
						//We can automatically redirect to the global results
						$newUrl = $interface->getVariable('globalResultsLink');
						if (strpos($newUrl, '?') !== false) {
							$newUrl .= '&disallowReplacements&replacedScope=' . $interface->getVariable('originalScope');
						} else {
							$newUrl .= '?disallowReplacements&replacedScope=' . $interface->getVariable('originalScope');
						}
						header("Location: " . $newUrl);
						exit();
					}
				}
			}

			//Spelling checks we will only do with no applied facets
			if (!$disallowReplacements && !$hasAppliedFacets) {
				//We can try to find a suggestion, but only if we are not doing a phrase search.
				if (strpos($searchObject->displayQuery(), '"') === false) {
					//If the search is not spelled properly, we can switch to the first spelling result
					if ($spellingSuggestions['correctlySpelled'] == false && $library->allowAutomaticSearchReplacements && count($spellingSuggestions['suggestions']) > 0) {
						$firstSuggestion = reset($spellingSuggestions['suggestions']);
						//first check to see if we will get results
						/** @var SearchObject_AbstractGroupedWorkSearcher $replacementSearchObject */
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
					AspenError::raiseError(new AspenError('Unable to process query<br>' . 'Solr Returned: ' . print_r($error, true)));
				}
			}

			$timer->logTime('no hits processing');

		} elseif ($searchObject->getResultTotal() == 1 && $searchObject->getSearchType() == 'id') {
			// Exactly One Result //
			//Redirect to the home page for the record
			$recordSet = $searchObject->getResultRecordSet();
			$record = reset($recordSet);
			$_SESSION['searchId'] = $searchObject->getSearchId();
			if ($record['recordtype'] == 'list') {
				$listId = substr($record['id'], 4);
				header("Location: " . "/MyAccount/MyList/{$listId}");
				exit();
			} else {
				header("Location: " . "/GroupedWork/{$record['id']}/Home");
				exit();
			}

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$interface->assign('recordCount', $summary['resultTotal']);
			$interface->assign('recordStart', $summary['startRecord']);
			$interface->assign('recordEnd', $summary['endRecord']);
			$memoryWatcher->logMemory('Get Result Summary');
		}

		// What Mode will search results be Displayed In //
		if ($displayMode == 'covers') {
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles
		} else { // default
			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
			$displayMode = 'list'; // In case the view is not explicitly set, do so now for display & clients-side functions

			// Process Paging (only in list mode)
			if ($searchObject->getResultTotal() > 1 && !empty($summary)) {
				$link = $searchObject->renderLinkPageTemplate();
				$options = [
					'totalItems' => $summary['resultTotal'],
					'fileName' => $link,
					'perPage' => $summary['perPage'],
				];
				$pager = new Pager($options);
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
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1) {
			$showExploreMoreBar = false;
		}
		$exploreMore = new ExploreMore();
		$exploreMoreSearchTerm = $exploreMore->getExploreMoreQuery();
		$interface->assign('exploreMoreSection', 'catalog');
		$interface->assign('showExploreMoreBar', $showExploreMoreBar);
		$interface->assign('exploreMoreSearchTerm', $exploreMoreSearchTerm);
		$interface->assign('sectionLabel', 'Library Catalog');

		if (array_key_exists('searchIndex', $_REQUEST)) {
			$searchIndex = $_REQUEST['searchIndex'];
		}else{
			$searchIndex = 'Keyword';
		}

		$ILLSystem = $library->ILLSystem;
		if (isset($ILLSystem) && $ILLSystem == 0) {
			$searchSource = new SearchSources();
			$interLibraryLoanURL = $searchSource->getExternalLink('innReach',$searchIndex,$_REQUEST['lookfor']);
		} elseif ($ILLSystem == 1) {
			$searchSource = new SearchSources();
			$interLibraryLoanURL = $searchSource->getExternalLink('worldcat',$searchIndex,$_REQUEST['lookfor']);
		} else {
			$interLibraryLoanURL = $library->interLibraryLoanUrl;
		}
		$interface->assign('interLibraryLoanURL',$interLibraryLoanURL);
		// Done, display the page
		$sidebar = ($searchObject->getResultTotal() > 0 || $hasAppliedFacets) ? 'Search/results-sidebar.tpl' : '';
		$this->display($searchObject->getResultTotal() ? 'list.tpl' : 'list-none.tpl', $pageTitle, $sidebar, false);
	} // End launch()

	/**
	 * @param SearchObject_AbstractGroupedWorkSearcher $searchObject
	 * @param UInterface $interface
	 *
	 * @return bool true if there are keyword results
	 */
	private function getKeywordSearchResults(SearchObject_AbstractGroupedWorkSearcher $searchObject, UInterface $interface): bool {
		//Check to see if we are not using a Keyword search and the Keyword search would provide results
		$interface->assign('hasKeywordResults', false);
		if (!$searchObject->isAdvanced()) {
			$searchTerms = $searchObject->getSearchTerms();
			if (count($searchTerms) == 1 && $searchTerms[0]['index'] != 'Keyword') {
				$searchIndexes = $searchObject->getSearchIndexes();
				$interface->assign('originalSearchIndex', $searchTerms[0]['index']);
				$interface->assign('originalSearchIndexLabel', $searchIndexes[$searchTerms[0]['index']]);
				$keywordSearchObject = clone $searchObject;
				$keywordSearchObject->setPrimarySearch(false);
				$keywordSearchObject->setSearchTerms([
					'index' => 'Keyword',
					'lookfor' => $searchTerms[0]['lookfor'],
				]);
				$keywordSearchObject->disableSpelling();
				$keywordSearchObject->clearFacets();
				$keywordSearchObject->processSearch(false, false, false);
				if ($keywordSearchObject->getResultTotal() > 0) {
					$interface->assign('hasKeywordResults', true);
					$interface->assign('keywordResultsLink', $keywordSearchObject->renderSearchUrl());
					$interface->assign('keywordResultsCount', $keywordSearchObject->getResultTotal());
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param SearchObject_AbstractGroupedWorkSearcher $searchObject
	 * @param UInterface $interface
	 *
	 * @return bool true if there are keyword results
	 */
	private function getGlobalSearchResults(SearchObject_AbstractGroupedWorkSearcher $searchObject, UInterface $interface): bool {
		//Check to see if we are not using a Global search and the Global search would provide results
		if (!$searchObject->isAdvanced()) {
			$searchTerms = $searchObject->getSearchTerms();
			if (count($searchTerms) == 1) {
				if ($searchObject->selectedAvailabilityToggleValue != 'global') {
					[
						$superScopeLabel,
						$localLabel,
						$availableLabel,
						$availableOnlineLabel,
					] = $this->getAvailabilityToggleLabels();
					switch ($searchObject->selectedAvailabilityToggleValue) {
						case 'local':
							$originalScopeLabel = $localLabel;
							break;
						case 'available':
							$originalScopeLabel = $availableLabel;
							break;
						case 'available_online':
							$originalScopeLabel = $availableOnlineLabel;
							break;
						default:
							$originalScopeLabel = 'Unknown';
					}

					$interface->assign('originalScope', $searchObject->selectedAvailabilityToggleValue);
					$interface->assign('originalScopeLabel', $originalScopeLabel);
					$globalSearchObject = clone $searchObject;
					$globalSearchObject->setPrimarySearch(false);
					$globalSearchObject->setSearchTerms([
						'index' => $searchTerms[0]['index'],
						'lookfor' => $searchTerms[0]['lookfor'],
					]);
					$globalSearchObject->removeFilter('availability_toggle');
					$globalSearchObject->addFilter('availability_toggle:global');
					$globalSearchObject->disableSpelling();
					$globalSearchObject->processSearch(false, false, false);
					if ($globalSearchObject->getResultTotal() > 0) {
						$interface->assign('hasGlobalResults', true);
						$interface->assign('globalResultsLink', $globalSearchObject->renderLinkWithFilter('availability_toggle', 'global'));
						$interface->assign('globalResultsCount', $globalSearchObject->getResultTotal());
						$interface->assign('globalScopeLabel', $superScopeLabel);
						return true;
					}
				}
			}
		}
		return false;
	}

	private function loadPlacards() {
		if (empty($_REQUEST['lookfor'])) {
			return;
		}
		try {
			$placardToDisplay = null;
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardTrigger.php';

			$placardToDisplay = Placard::getPlacardForTriggerWord($_REQUEST['lookfor']);

			if ($placardToDisplay == null && !empty($_REQUEST['replacementTerm'])) {
				$placardToDisplay = Placard::getPlacardForTriggerWord($_REQUEST['replacementTerm']);
			}

			if ($placardToDisplay != null) {
				global $interface;
				$interface->assign('placard', $placardToDisplay);
			}
		} catch (Exception $e) {
			//Placards are not defined yet
		}
	}

	function getBreadcrumbs(): array {
		return parent::getResultsBreadcrumbs('Catalog Search');
	}

	/**
	 * @return array
	 */
	private function getAvailabilityToggleLabels(): array {
		$searchLibrary = Library::getSearchLibrary(null);
		$searchLocation = Location::getSearchLocation(null);

		if ($searchLocation) {
			$groupedWorkDisplaySettings = $searchLocation->getGroupedWorkDisplaySettings();
		} else {
			$groupedWorkDisplaySettings = $searchLibrary->getGroupedWorkDisplaySettings();
		}
		$superScopeLabel = $groupedWorkDisplaySettings->availabilityToggleLabelSuperScope;
		$localLabel = $groupedWorkDisplaySettings->availabilityToggleLabelLocal;
		$localLabel = str_ireplace('{display name}', $searchLocation->displayName, $localLabel);
		$availableLabel = $groupedWorkDisplaySettings->availabilityToggleLabelAvailable;
		$availableLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableLabel);
		$availableOnlineLabel = $groupedWorkDisplaySettings->availabilityToggleLabelAvailableOnline;
		$availableOnlineLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableOnlineLabel);
		return [
			$superScopeLabel,
			$localLabel,
			$availableLabel,
			$availableOnlineLabel,
		];
	}

	/**
	 * Validates and processes search parameters, including handling field prefixes
	 * and validating searchIndex values against whitelist.
	 */
	private function validateAndProcessSearchParameters(): void {
		// Handle field prefixes in the 'lookfor' parameter (e.g., ISBN:97812345...).
		if (isset($_REQUEST['lookfor']) && !isset($_REQUEST['searchIndex'])) {
			$lookforRaw = $_REQUEST['lookfor'];
			if (preg_match('/^(\w+)\s*:(.+)$/', $lookforRaw, $matches)) {
				$rawPrefix = $matches[1];
				$restTerm = trim($matches[2]);

				require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
				$tmpSearchObj = SearchObjectFactory::initSearchObject();
				$validIndexes = $tmpSearchObj->getSearchIndexes();

				// Check if the prefix matches any valid search index (case-insensitive).
				foreach (array_keys($validIndexes) as $idxKey) {
					if (strcasecmp($idxKey, $rawPrefix) === 0) {
						$_REQUEST['searchIndex'] = $idxKey;
						$_GET['searchIndex'] = $idxKey;
						$_REQUEST['lookfor'] = $restTerm;
						$_GET['lookfor'] = $restTerm;
						break;
					}
				}
			}
		}

		// Validate 'searchIndex' parameter to prevent invalid or malicious index values.
		if (isset($_REQUEST['searchIndex'])) {
			if (!isset($tmpSearchObj)) {
				require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
				$tmpSearchObj = SearchObjectFactory::initSearchObject();
			}
			if (!isset($validIndexes)) {
				$validIndexes = $tmpSearchObj->getSearchIndexes();
			}

			if (!array_key_exists($_REQUEST['searchIndex'], $validIndexes)) {
				header("Location: /Error/Handle404");
				exit();
			}
		}
	}

}
