<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class Admin_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('Admin'); // could refactor to extract the section name ('Admin') from the URL within UsageGraphs_UsageGraphs::launch() itself
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/UsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;
		global $enabledModules;
		global $library;

		$dataSeries = [];
		$columnLabels = [];

		$groupByTimeframe = implode(',', $timeframes);

		// Default: AspenUsage
		$userUsage = new AspenUsage();
		$userUsage->selectAdd();
		if (!empty($instanceName)) {
			$userUsage->instance = $instanceName;
		}
	
		if (is_array($custom)) {
			$userUsage->buildCustomPeriodQuery($custom);
		} else {
			$userUsage->groupBy($groupByTimeframe);
			foreach ($timeframes as $timeframe) {
				$userUsage->selectAdd($timeframe);
			}
			$userUsage->orderBy($groupByTimeframe);
		}

		//General Usage Stats
		if ($stat == 'pageViews' || $stat == 'generalUsage') {
			$dataSeries['Page Views'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(pageViews) as sumPageViews');
		}
		if ($stat == 'authenticatedPageViews' || $stat == 'generalUsage') {
			$dataSeries['Authenticated Page Views'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(pageViewsByAuthenticatedUsers) as sumPageViewsByAuthenticatedUsers');
		}
		if ($stat == 'sessionsStarted' || $stat == 'generalUsage') {
			$dataSeries['Sessions Started'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(sessionsStarted) as sumSessionsStarted');
		}
		if ($stat == 'pageViewsByBots' || $stat == 'generalUsage') {
			$dataSeries['Page Views By Bots'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(pageViewsByBots) as sumPageViewsByBots');
		}
		if ($stat == 'asyncRequests' || $stat == 'generalUsage') {
			$dataSeries['Asynchronous Requests'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(ajaxRequests) as sumAjaxRequests');
		}
		if ($stat == 'coversRequested' || $stat == 'generalUsage') {
			$dataSeries['Covers Requested'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(coverViews) as sumCoverViews');
		}

		//Search Stats
		if ($stat == 'groupedWorksSearches' || $stat == 'searches') {
			$dataSeries['Grouped Work Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(groupedWorkSearches) as sumGroupedWorkSearches');
		}
		if ($stat == 'listSearches' || $stat == 'searches') {
			$dataSeries['List Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(userListSearches) as sumUserListSearches');
		}
		if (array_key_exists('EBSCO EDS', $enabledModules) && ($stat == 'edsSearches' || $stat == 'searches')) {
			$dataSeries['EDS Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(ebscoEdsSearches) as sumEbscoEdsSearches');
		}
		if (array_key_exists('EBSCOhost', $enabledModules) && ($stat == 'ebscohostSearches' || $stat == 'searches')) {
			$dataSeries['EBSCOhost Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(ebscohostSearches) as sumEbscohostSearches');
		}
		if (array_key_exists('Gale', $enabledModules) && ($stat == 'galeSearches' || $stat == 'searches')) {
			$dataSeries['Gale Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(galeSearches) as sumGaleSearches');
		}
		if (array_key_exists('Events', $enabledModules) && ($stat == 'eventSearches' || $stat == 'searches')) {
			$dataSeries['Events Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(eventsSearches) as sumEventsSearches');
		}
		if ((array_key_exists('Web Indexer', $enabledModules) || array_key_exists('Web Builder', $enabledModules)) && ($stat == 'websiteSearches' || $stat == 'searches')) {
			$dataSeries['Website Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(websiteSearches) as sumWebsiteSearches');
		}
		if (array_key_exists('Open Archives', $enabledModules) && ($stat == 'openArchivesSearches' || $stat == 'searches')) {
			$dataSeries['Open Archives Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(openArchivesSearches) as sumOpenArchivesSearches');
		}
		if ($library->enableGenealogy && ($stat == 'genealogySearches' || $stat == 'searches')) {
			$dataSeries['Genealogy Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(genealogySearches) as sumGenealogySearches');
		}

		//Exceptions
		if ($stat == 'blockedPages' || $stat == 'exceptionsReport') {
			$dataSeries['Blocked Pages'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(blockedRequests) as sumBlockedRequests');
		}
		if ($stat == 'blockedApiRequests' || $stat == 'exceptionsReport') {
			$dataSeries['Blocked API Requests'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(blockedApiRequests) as sumBlockedApiRequests');
		}
		if ($stat == 'errors' || $stat == 'exceptionsReport') {
			$dataSeries['Errors'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(pagesWithErrors) as sumPagesWithErrors');
		}
		if ($stat == 'searchesWithErrors' || $stat == 'exceptionsReport') {
			$dataSeries['Searches with Errors'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(searchesWithErrors) as sumSearchesWithErrors');
		}
		if ($stat == 'timedOutSearches' || $stat == 'exceptionsReport') {
			$dataSeries['Timed Out Searches'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(timedOutSearches) as sumTimedOutSearches');
		}
		if ($stat == 'timedOutSearchesWithHighLoad' || $stat == 'exceptionsReport') {
			$dataSeries['Timed Out Searches Under High Load'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(timedOutSearchesWithHighLoad) as sumTimedOutSearchesWithHighLoad');
		}
		if ($stat == 'emailsSent' || $stat == 'emailSending') {
			$dataSeries['Emails Sent'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(emailsSent) as sumEmailsSent');
		}
		if ($stat == 'failedEmails' || $stat == 'emailSending') {
			$dataSeries['Failed Emails'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('SUM(emailsFailed) as sumFailedEmails');
		}

		//Collect results
		$userUsage->find();
		while ($userUsage->fetch()) {
			$curPeriod = $custom ? $userUsage->getCustomPeriod() : $userUsage->getCurPeriod($timeframes);
			$columnLabels[] = $curPeriod;
			if ($stat == 'pageViews' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Page Views']['data'][$curPeriod] = $userUsage->sumPageViews;
			}
			if ($stat == 'authenticatedPageViews' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Authenticated Page Views']['data'][$curPeriod] = $userUsage->sumPageViewsByAuthenticatedUsers;
			}
			if ($stat == 'pageViewsByBots' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Page Views By Bots']['data'][$curPeriod] = $userUsage->sumPageViewsByBots;
			}
			if ($stat == 'sessionsStarted' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Sessions Started']['data'][$curPeriod] = $userUsage->sumSessionsStarted;
			}
			if ($stat == 'asyncRequests' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Asynchronous Requests']['data'][$curPeriod] = $userUsage->sumAjaxRequests;
			}
			if ($stat == 'coversRequested' || $stat == 'generalUsage') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Covers Requested']['data'][$curPeriod] = $userUsage->sumCoverViews;
			}
			if ($stat == 'groupedWorksSearches' || $stat == 'searches') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Grouped Work Searches']['data'][$curPeriod] = $userUsage->sumGroupedWorkSearches;
			}
			if ($stat == 'listSearches' || $stat == 'searches') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['List Searches']['data'][$curPeriod] = $userUsage->sumUserListSearches;
			}
			if (array_key_exists('EBSCO EDS', $enabledModules) && ($stat == 'edsSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['EDS Searches']['data'][$curPeriod] = $userUsage->sumEbscoEdsSearches;
			}
			if (array_key_exists('EBSCOhost', $enabledModules) && ($stat == 'ebscohostSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['EBSCOhost Searches']['data'][$curPeriod] = $userUsage->sumEbscohostSearches;
			}
			if (array_key_exists('Gale', $enabledModules) && ($stat == 'galeSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Gale Searches']['data'][$curPeriod] = $userUsage->sumGaleSearches;
			}
			if (array_key_exists('Events', $enabledModules) && ($stat == 'eventSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Events Searches']['data'][$curPeriod] = $userUsage->sumEventsSearches;
			}
			if ((array_key_exists('Web Indexer', $enabledModules) || array_key_exists('Web Builder', $enabledModules)) && ($stat == 'websiteSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Website Searches']['data'][$curPeriod] = $userUsage->sumWebsiteSearches;
			}
			if (array_key_exists('Open Archives', $enabledModules) && ($stat == 'openArchivesSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Open Archives Searches']['data'][$curPeriod] = $userUsage->sumOpenArchivesSearches;
			}
			if ($library->enableGenealogy && ($stat == 'genealogySearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Genealogy Searches']['data'][$curPeriod] = $userUsage->sumGenealogySearches;
			}
			if ($stat == 'blockedPages' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Blocked Pages']['data'][$curPeriod] = $userUsage->sumBlockedRequests;
			}
			if ($stat == 'blockedApiRequests' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Blocked API Requests']['data'][$curPeriod] = $userUsage->sumBlockedApiRequests;
			}
			if ($stat == 'errors' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Errors']['data'][$curPeriod] = $userUsage->sumPagesWithErrors;
			}
			if ($stat == 'timedOutSearches' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Timed Out Searches']['data'][$curPeriod] = $userUsage->sumTimedOutSearches;
			}
			if ($stat == 'timedOutSearchesWithHighLoad' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Timed Out Searches Under High Load']['data'][$curPeriod] = $userUsage->sumTimedOutSearchesWithHighLoad;
			}
			if ($stat == 'searchesWithErrors' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Searches with Errors']['data'][$curPeriod] = $userUsage->sumSearchesWithErrors;
			}
			if ($stat == 'emailsSent' || $stat == 'emailSending') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Emails Sent']['data'][$curPeriod] = $userUsage->sumEmailsSent;
			}
			if ($stat == 'failedEmails' || $stat == 'emailSending') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Failed Emails']['data'][$curPeriod] = $userUsage->sumFailedEmails;
			}
		}

		// Placard Usage Stats
		if ($stat == 'placardUsage') {
			require_once ROOT_DIR . '/sys/WebBuilder/PlacardUsage.php';
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			$placardId = $_REQUEST['placardId'] ?? null;
			// Get placard name for title
			$placard = new Placard();
			$placard->id = $placardId;
			$interface->assign('placardId', $placardId);
			if ($placard->find(true)) {
				$placardName = $placard->title;
			} else {
				$placardName = '';
			}
			$usage = new PlacardUsage();
			if (!empty($instanceName)) {
				$usage->instance = $instanceName;
			}
			$usage->placardName = $placardName;
			$usage->selectAdd();
			$usage->selectAdd('year');
			$usage->selectAdd('month');
			$usage->selectAdd('SUM(timesShown) as sumTimesShown');
			$usage->groupBy('year, month');
			$usage->orderBy('year, month');
			$usage->find();
			$dataSeries['Times Shown'] = GraphingUtils::getDataSeriesArray(0);
			$columnLabels = [];
			while ($usage->fetch()) {
				$curPeriod = "{$usage->month}-{$usage->year}";
				$columnLabels[] = $curPeriod;
				$dataSeries['Times Shown']['data'][$curPeriod] = (int)$usage->sumTimesShown;
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}

	protected function assignGraphSpecificTitle(string $stat): void {
		global $interface;
		$title = $interface->getVariable('graphTitle');
		switch ($stat) {
			case 'generalUsage':
				$title .= ' - General Usage';
				break;
			case 'pageViews':
				$title .= ' - Pages Viewed';
				break;
			case 'authenticatedPageViews':
				$title .= ' - Authenticated Page Views';
				break;
			case 'sessionsStarted':
				$title = ' - Sessions Started';
				break;
			case 'pageViewsByBots':
				$title .= ' - Pages Viewed By Bots';
				break;
			case 'asyncRequests':
				$title .= ' - Asynchronous Requests';
				break;
			case 'coversRequested':
				$title .= ' - Covers Requested';
				break;
			case 'searches':
				$title .= ' - Searches';
				break;
			case 'groupedWorksSearches':
				$title .= ' - Grouped Work Searches';
				break;
			case 'listSearches':
				$title .= ' - List Searches';
				break;
			case 'edsSearches':
				$title .= ' - EBSCO EDS Searches';
				break;
			case 'eventSearches':
				$title .= ' - Event Searches';
				break;
			case 'openArchivesSearches':
				$title .= ' - Open Archives Searches';
				break;
			case 'genealogySearches':
				$title .= ' - Genealogy Searches';
				break;
			case 'exceptionsReport':
				$title .= ' - Exceptions';
				break;
			case 'blockedPages':
				$title .= ' - Blocked Pages';
				break;
			case 'blockedApiRequests':
				$title .= ' - Blocked API Requests';
				break;
			case 'errors':
				$title .= ' - Errors';
				break;
			case 'emailSending':
				$title .= ' - Email Sending';
				break;
			case 'emailsSent':
				$title .= ' - Emails Sent';
				break;
			case 'failedEmails':
				$title .= ' - Failed Emails';
				break;
			case 'placardUsage':
				// Get placard name for title
				require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
				$placardId = $_REQUEST['placardId'] ?? null;
				$placardName = '';
				if (!empty($placardId)) {
					$placard = new Placard();
					$placard->id = $placardId;
					if ($placard->find(true)) {
						$placardName = $placard->title;
					}
				}
				$title = 'Admin Usage Graph - Placard: ' . $placardName;
				break;
		}
		$interface->assign('graphTitle', $title);
	}

}
