<?php
require_once ROOT_DIR . '/ResultsAction.php';
class Talpa_Results extends ResultsAction {
	function launch() {
		global $interface;
		global $timer;
		global $library;

		global $solrScope;
		if(!$solrScope) {
			$solrScope=$library->subdomain;
		}

		//Retrieve the Grouped Work Display settings to use in result.tpl
		foreach ($library->getGroupedWorkDisplaySettings()->showInSearchResultsMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}

		//Retrieve Talpa Display settings to use in result.tpl
		$defaultTalpaExplainerText = '<p>Talpa Search is a new way to search for books and other media using natural language to find items by plot details, genre, descriptions, and more. Talpa combines cutting-edge technology with data from libraries, publishers and readers to enable entirely new ways of searching&mdash;and find what you\'re looking for.</p>
		
		<p>Try searches like: "astronaut stranded on Mars", "novels about France during World War II", "recent cozy mysteries", or "books set in jacksonville florida".</p>
		<p><a href="https://www.talpasearch.com/about" target="_blank">Learn more about Talpa</a>.</p>';

		$defaultTalpaSearchSourceString = 'Talpa Search';

		$defaultTalpaOtherResultsExplainerText = 'Talpa found these other results.';

		require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
		if ($library->talpaSettingsId != -1) {
			$talpaSettings = new TalpaSettings();
			$talpaSettings->id = $library->talpaSettingsId;
			if (!$talpaSettings->find(true)) { //If no settings found, Use defaults
				$talpaSettings = null;
				$interface->assign('talpaExplainerText', $defaultTalpaExplainerText);
				$interface->assign('talpaSearchSourceString', $defaultTalpaSearchSourceString);
				$interface->assign('includeTalpaLogoSwitch',1);
				$interface->assign('talpaOtherResultsExplainerText',$defaultTalpaOtherResultsExplainerText);
				$interface->assign('includeTalpaOtherResultsSwitch',1);


			}else {
				if(trim(strip_tags($talpaSettings->talpaExplainerText)) == '') {
					$interface->assign('talpaExplainerText', $defaultTalpaExplainerText);
					} else {
					$interface->assign('talpaExplainerText', $talpaSettings->talpaExplainerText);
					}
				$interface->assign('talpaSearchSourceString', $talpaSettings->talpaSearchSourceString?:$defaultTalpaSearchSourceString);
				$interface->assign('includeTalpaLogoSwitch', $talpaSettings->includeTalpaLogoSwitch);
				$interface->assign('talpaOtherResultsExplainerText', $talpaSettings->talpaOtherResultsExplainerText?:$defaultTalpaOtherResultsExplainerText);
				$interface->assign('includeTalpaOtherResultsSwitch',$talpaSettings->includeTalpaOtherResultsSwitch?:1);
			}
		}
		if (!isset($_REQUEST['lookfor']) || empty($_REQUEST['lookfor'])) {
			$_REQUEST['lookfor'] = 'The Man with the Yellow Hat';
		}

		if (isset($_REQUEST['filter']) && isset($_REQUEST['filter'][0])) {
			preg_match('/availability_toggle:"(.*?)"/', $_REQUEST['filter'][0], $matches);
			$locationFilter = $matches[1];
			$interface->assign('availability_toggle', $locationFilter);
		}
		$interface->assign('showNotInterested', false);

		//Include Search Engine
		/** @var SearchObject_TalpaSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject("Talpa");
		$timer->logTime('Include search engine');

		// Hide Covers when the user has set that setting on the Search Results Page
		$this->setShowCovers();

		$searchObject->init();

		//If queryID matches the session data queryID (from Talpa's top facets), use those saved results to save load time.
		if (isset($_REQUEST['queryId']) && $_SESSION['last_recordData'] && ($_SESSION['last_query_id']== $_REQUEST['queryId']) ) {
			$result = unserialize($_SESSION['last_recordData']);
			$searchObject->processRepeatedSearch($result);

			if(isset($_SESSION['talpa_warning'])) {
				$interface->assign('talpa_warning', $_SESSION['talpa_warning']);
			}
		}
		elseif( isset($_REQUEST['queryId']) && ($_SESSION['last_query_id']!= $_REQUEST['queryId'])){ //two concurrent sessions, request new results
			$result = $searchObject->sendRequest($_REQUEST['queryId']);
		}
		else //performing a new search
		{
			$result = $searchObject->sendRequest();
		}



		//Assign vars for Talpa Summaries to be ajaxed in.
		$interface->assign('uniq_key_for_summary_retrieval', $result['response']['bib_info']['uniq_key_for_summary_retrieval']);
		$interface->assign('uniq_val_for_summary_retrieval', $result['response']['bib_info']['uniq_val_for_summary_retrieval']);

		// for reviewing api time taken in html results output
		$interface->assign('querySpeed', $searchObject->getQuerySpeed());
		$interface->assign('recordFetchSpeed', $searchObject->getRecordFetchSpeed());
		$interface->assign('preliminarySearchSpeed',  $searchObject->getPreliminarySearchSpeed());


		$rawIsbns =  explode(',', $result['response']['bib_info']['isbnS_for_summary_retrieval']);
		$summaryIsbnsJSON = json_encode($rawIsbns);
		$isbnS_for_summary_retrieval = htmlspecialchars($summaryIsbnsJSON, ENT_QUOTES, 'UTF-8');

		$interface->assign('isbnS_for_summary_retrieval', $isbnS_for_summary_retrieval );


		if ($result instanceof AspenError) {
			global $serverName;
			$logSearchError = true;
			if ($logSearchError) {
				try {
					require_once ROOT_DIR . '/sys/SystemVariables.php';
					$systemVariables = new SystemVariables();
					if ($systemVariables->find(true) && !empty($systemVariables->searchErrorEmail)) {
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mailer = new Mailer();
						$emailErrorDetails = $_SERVER['REQUEST_URI'] . "\n" . $result->getMessage();
						$mailer->send($systemVariables->searchErrorEmail, "$serverName Error processing Talpa search", $emailErrorDetails);
					}
				} catch (Exception $e) {

				}
			}

			$interface->assign('searchError', $result);
			$this->display('searchError.tpl', 'Error in Search');
			return;
		}

	//DISPLAY SEARCH TO USER
		$displayQuery = $searchObject->displayQuery();
		$pageTitle = $displayQuery;
		if (strlen($pageTitle) > 20) {
			$pageTitle = substr($pageTitle, 0, 20) . '...';
		}

		$interface->assign('lookfor', $displayQuery);
		$interface->assign('topRecommendations', $searchObject->getRecommendationsTemplates('top'));


		//SET INTERFACE VARS/SETTINGS
		$interface->assign('showLanguages', true);

		$summary = $searchObject->getResultSummary();
		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd', $summary['endRecord']);


		$appliedFacets = $searchObject->getFilterList();
		$interface->assign('filterList', $appliedFacets);

		$filterListApplied = $appliedFacets['Search Within'][0]['value'];
		$interface->assign('filterListApplied', $filterListApplied);

		$limitList = $searchObject->getLimitList();
		$interface->assign('limitList', $limitList);
		$facetSet = $searchObject->getFacetSet();
		$interface->assign('sideFacetSet', $facetSet);

		//Figure out which counts to show.
		$facetCountsToShow = $library->getGroupedWorkDisplaySettings()->facetCountsToShow;
		$interface->assign('facetCountsToShow', $facetCountsToShow);



		//Talpa Results //
		$recordSet = $searchObject->getResultRecordHTML();

		$interface->assign('recordSet', $recordSet);
		$timer->logTime('load result records');

		$interface->assign('searchIndex', $searchObject->getSearchIndex());

		if ($summary['resultTotal'] > 0) {
			$link = $searchObject->renderLinkPageTemplate();
			$options = [
				'totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage'],
			];
			$pager = new Pager($options);
			$interface->assign('pageLinks', $pager->getLinks());
		}

		$searchObject->close();

		$interface->assign('searchId', $searchObject->getSearchId());


		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily; used in Home.php to assign lastSearch interface variable for returning to results list from an individual item.
		$baseSearchUrl = $searchObject->renderSearchUrl();
		$currentQueryId = $_REQUEST['queryId'] ?? null;
		$lastQueryId = $_SESSION['last_query_id'];
		$lastSearchURL = $baseSearchUrl.'&queryId='.($currentQueryId?:$lastQueryId);
		$_SESSION['lastSearchURL'] = $lastSearchURL;


		$displayTemplate = 'Talpa/list-list.tpl'; // structure for regular results
		$interface->assign('subpage', $displayTemplate);
		$interface->assign('sectionLabel', 'Talpa');

		$interface->assign('hasSearchableFacets', $searchObject->hasSearchableFacets());

		$sidebar = $searchObject->getResultTotal() > 0 ? 'Talpa/results-sidebar.tpl' : '';
		$this->display($summary['resultTotal'] > 0 ? 'list.tpl' : 'list-none.tpl', $pageTitle, $sidebar, false);
	}

	function getBreadcrumbs(): array {
		return parent::getResultsBreadcrumbs($_SESSION['talpaBreadcrumb']);
	}
}
