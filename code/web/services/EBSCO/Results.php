<?php
require_once ROOT_DIR . '/ResultsAction.php';
class EBSCO_Results extends ResultsAction {
	function launch() {
		global $interface;
		global $timer;
		global $aspenUsage;

		if (!isset($_REQUEST['lookfor']) || empty($_REQUEST['lookfor'])){
			$_REQUEST['lookfor'] = '*';
		}

		$aspenUsage->ebscoEdsSearches++;

		//Include Search Engine
		/** @var SearchObject_EbscoEdsSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject("EbscoEds");
		$timer->logTime('Include search engine');

		// Hide Covers when the user has set that setting on the Search Results Page
		$this->setShowCovers();

		$searchObject->init();
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError){
			global $serverName;
			$logSearchError = true;
			if ($logSearchError) {
				try{
					require_once ROOT_DIR . '/sys/SystemVariables.php';
					$systemVariables = new SystemVariables();
					if ($systemVariables->find(true) && !empty($systemVariables->searchErrorEmail)) {
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mailer = new Mailer();
						$emailErrorDetails = $_SERVER['REQUEST_URI'] . "\n" . $result['error']['msg'];
						$mailer->send($systemVariables->searchErrorEmail, "$serverName Error processing EBSCO EDS search", $emailErrorDetails);
					}
				}catch (Exception $e){
					//This happens when the table has not been created
				}
			}

			$interface->assign('searchError', $result);
			$this->display('searchError.tpl', 'Error in Search');
			return;
		}

		$displayQuery = $searchObject->displayQuery();
		$pageTitle = $displayQuery;
		if (strlen($pageTitle) > 20){
			$pageTitle = substr($pageTitle, 0, 20) . '...';
		}

		$interface->assign('lookfor', $displayQuery);

		// Big one - our results //
		$recordSet = $searchObject->getResultRecordHTML();
		$interface->assign('recordSet', $recordSet);
		$timer->logTime('load result records');

		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('searchIndex', $searchObject->getSearchIndex());

		$summary = $searchObject->getResultSummary();
		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);

		$appliedFacets = $searchObject->getFilterList();
		$interface->assign('filterList', $appliedFacets);
		$limitList = $searchObject->getLimitList();
		$interface->assign('limitList', $limitList);
		$facetSet = $searchObject->getFacetSet();
		$interface->assign('sideFacetSet', $facetSet);

		if ($summary['resultTotal'] > 0){
			$link    = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
					'fileName' => $link,
					'perPage' => $summary['perPage']);
			$pager   = new Pager($options);
			$interface->assign('pageLinks', $pager->getLinks());
		}

		$interface->assign('savedSearch', $searchObject->isSavedSearch());
		$interface->assign('searchId',    $searchObject->getSearchId());

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

		//Setup explore more
		$showExploreMoreBar = true;
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1){
			$showExploreMoreBar = false;
		}
		$exploreMore = new ExploreMore();
		$exploreMoreSearchTerm = $exploreMore->getExploreMoreQuery();
		$interface->assign('exploreMoreSection', 'ebsco_eds');
		$interface->assign('showExploreMoreBar', $showExploreMoreBar);
		$interface->assign('exploreMoreSearchTerm', $exploreMoreSearchTerm);

		//Check for research starters
		if (!empty($_REQUEST['lookfor'])) {
			$researchStarters = $searchObject->getResearchStarters($_REQUEST['lookfor']);
			$researchStarterHtml = '';
			foreach ($researchStarters as $researchStarter) {
				$researchStarterHtml .= $researchStarter->getDisplayHtml();
			}
			$interface->assign('researchStarters', $researchStarterHtml);
		}

		$displayTemplate = 'EBSCO/list-list.tpl'; // structure for regular results
		$interface->assign('subpage', $displayTemplate);
		$interface->assign('sectionLabel', 'EBSCO Research Databases');
		$sidebar = $searchObject->getResultTotal() > 0 ? 'EBSCO/results-sidebar.tpl' : '';
		$this->display($summary['resultTotal'] > 0 ? 'list.tpl' : 'list-none.tpl', $pageTitle, $sidebar, false);
	}

	function getBreadcrumbs() : array
	{
		return parent::getResultsBreadcrumbs('Articles & Databases');
	}
}