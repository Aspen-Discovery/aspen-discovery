<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';

class Admin_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		global $enabledModules;
		global $library;
		$title = 'Aspen Usage Graph';
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$dataSeries = [];
		$columnLabels = [];
		$userUsage = new AspenUsage();
		$userUsage->groupBy('year, month');
		if (!empty($instanceName)) {
			$userUsage->instance = $instanceName;
		}
		$userUsage->selectAdd();
		$userUsage->selectAdd('year');
		$userUsage->selectAdd('month');
		$userUsage->orderBy('year, month');

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
		}

		//General Usage Stats
		if ($stat == 'pageViews' || $stat == 'generalUsage') {
			$dataSeries['Page Views'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(pageViews) as sumPageViews');
		}
		if ($stat == 'authenticatedPageViews' || $stat == 'generalUsage') {
			$dataSeries['Authenticated Page Views'] = [
				'borderColor' => 'rgba(255, 159, 64, 1)',
				'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(pageViewsByAuthenticatedUsers) as sumPageViewsByAuthenticatedUsers');
		}
		if ($stat == 'sessionsStarted' || $stat == 'generalUsage') {
			$dataSeries['Sessions Started'] = [
				'borderColor' => 'rgba(0, 255, 55, 1)',
				'backgroundColor' => 'rgba(0, 255, 55, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(sessionsStarted) as sumSessionsStarted');
		}
		if ($stat == 'pageViewsByBots' || $stat == 'generalUsage') {
			$dataSeries['Page Views By Bots'] = [
				'borderColor' => 'rgba(154, 75, 244, 1)',
				'backgroundColor' => 'rgba(154, 75, 244, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(pageViewsByBots) as sumPageViewsByBots');
		}
		if ($stat == 'asyncRequests' || $stat == 'generalUsage') {
			$dataSeries['Asynchronous Requests'] = [
				'borderColor' => 'rgba(54, 162, 235, 1)',
				'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(ajaxRequests) as sumAjaxRequests');
		}
		if ($stat == 'coversRequested' || $stat == 'generalUsage') {
			$dataSeries['Covers Requested'] = [
				'borderColor' => 'rgba(255, 206, 86, 1)',
				'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(coverViews) as sumCoverViews');
		}

		//Search Stats
		if ($stat == 'groupedWorksSearches' || $stat == 'searches') {
			$dataSeries['Grouped Work Searches'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(groupedWorkSearches) as sumGroupedWorkSearches');
		}
		if ($stat == 'listSearches' || $stat == 'searches') {
			$dataSeries['List Searches'] = [
				'borderColor' => 'rgba(54, 162, 235, 1)',
				'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(userListSearches) as sumUserListSearches');
		}
		if (array_key_exists('EBSCO EDS', $enabledModules) && ($stat == 'edsSearches' || $stat == 'searches')) {
			$dataSeries['EDS Searches'] = [
				'borderColor' => 'rgba(255, 206, 86, 1)',
				'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(ebscoEdsSearches) as sumEbscoEdsSearches');
		}
		if (array_key_exists('EBSCOhost', $enabledModules) && ($stat == 'ebscohostSearches' || $stat == 'searches')) {
			$dataSeries['EBSCOhost Searches'] = [
				'borderColor' => 'rgba(255, 206, 86, 1)',
				'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(ebscohostSearches) as sumEbscohostSearches');
		}
		if (array_key_exists('Events', $enabledModules) && ($stat == 'eventSearches' || $stat == 'searches')) {
			$dataSeries['Events Searches'] = [
				'borderColor' => 'rgba(75, 192, 192, 1)',
				'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(eventsSearches) as sumEventsSearches');
		}
		if ((array_key_exists('Web Indexer', $enabledModules) || array_key_exists('Web Builder', $enabledModules)) && ($stat == 'websiteSearches' || $stat == 'searches')) {
			$dataSeries['Website Searches'] = [
				'borderColor' => 'rgba(153, 102, 255, 1)',
				'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(websiteSearches) as sumWebsiteSearches');
		}
		if (array_key_exists('Open Archives', $enabledModules) && ($stat == 'openArchivesSearches' || $stat == 'searches')) {
			$dataSeries['Open Archives Searches'] = [
				'borderColor' => 'rgba(255, 159, 64, 1)',
				'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(openArchivesSearches) as sumOpenArchivesSearches');
		}
		if ($library->enableGenealogy && ($stat == 'genealogySearches' || $stat == 'searches')) {
			$dataSeries['Genealogy Searches'] = [
				'borderColor' => 'rgba(154, 75, 244, 1)',
				'backgroundColor' => 'rgba(2154, 75, 244, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(genealogySearches) as sumGenealogySearches');
		}

		//Exceptions
		if ($stat == 'blockedPages' || $stat == 'exceptionsReport') {
			$dataSeries['Blocked Pages'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(blockedRequests) as sumBlockedRequests');
		}
		if ($stat == 'blockedApiRequests' || $stat == 'exceptionsReport') {
			$dataSeries['Blocked API Requests'] = [
				'borderColor' => 'rgba(255, 159, 64, 1)',
				'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(blockedApiRequests) as sumBlockedApiRequests');
		}
		if ($stat == 'errors' || $stat == 'exceptionsReport') {
			$dataSeries['Errors'] = [
				'borderColor' => 'rgba(154, 75, 244, 1)',
				'backgroundColor' => 'rgba(154, 75, 244, 0.2)',
				'data' => [],
			];
			$userUsage->selectAdd('SUM(pagesWithErrors) as sumPagesWithErrors');
		}


		//Collect results
		$userUsage->find();

		while ($userUsage->fetch()) {
			$curPeriod = "{$userUsage->month}-{$userUsage->year}";
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
			if (array_key_exists('EBSCO EDS', $enabledModules) && ($stat == 'EbscoEdsSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['EDS Searches']['data'][$curPeriod] = $userUsage->sumEbscoEdsSearches;
			}
			if (array_key_exists('EBSCOhost', $enabledModules) && ($stat == 'ebscohostSearches' || $stat == 'searches')) {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['EBSCOhost Searches']['data'][$curPeriod] = $userUsage->sumEbscohostSearches;
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
			if ($stat == 'blockedPages' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Blocked API Requests']['data'][$curPeriod] = $userUsage->sumBlockedApiRequests;
			}
			if ($stat == 'errors' || $stat == 'exceptionsReport') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Errors']['data'][$curPeriod] = $userUsage->sumPagesWithErrors;
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);

		$interface->assign('graphTitle', $title);
		$this->display('usage-graph.tpl', $title);
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

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}
}