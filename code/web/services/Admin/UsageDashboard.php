<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';

class Admin_UsageDashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('AspenUsage');
		$this->loadDates();

		$usageThisMonth = $this->getStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('usageThisMonth', $usageThisMonth);
		$usageLastMonth = $this->getStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('usageLastMonth', $usageLastMonth);
		$usageThisYear = $this->getStats($instanceName, null, $this->thisYear);
		$interface->assign('usageThisYear', $usageThisYear);
		$usageAllTime = $this->getStats($instanceName, null, null);
		$interface->assign('usageAllTime', $usageAllTime);

		$this->display('usage_dashboard.tpl', 'Aspen Usage Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int[]
	 */
	function getStats($instanceName, $month, $year): array
	{
		$usage = new AspenUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->selectAdd();
		$usage->selectAdd('SUM(pageViews) as totalViews');
		$usage->selectAdd('SUM(pageViewsByBots) as totalPageViewsByBots');
		$usage->selectAdd('SUM(pageViewsByAuthenticatedUsers) as totalPageViewsByAuthenticatedUsers');
		$usage->selectAdd('SUM(sessionsStarted) as totalSessionsStarted');
		$usage->selectAdd('SUM(coverViews) as totalCovers');
		$usage->selectAdd('SUM(pagesWithErrors) as totalErrors');
		$usage->selectAdd('SUM(ajaxRequests) as totalAsyncRequests');
		$usage->selectAdd('SUM(genealogySearches) as totalGenealogySearches');
		$usage->selectAdd('SUM(groupedWorkSearches) as totalGroupedWorkSearches');
		$usage->selectAdd('SUM(openArchivesSearches) as totalOpenArchivesSearches');
		$usage->selectAdd('SUM(userListSearches) as totalUserListSearches');
		$usage->selectAdd('SUM(websiteSearches) as totalWebsiteSearches');
		$usage->selectAdd('SUM(eventsSearches) as totalEventsSearches');
		$usage->selectAdd('SUM(ebscoEdsSearches) as totalEbscoEdsSearches');
		$usage->selectAdd('SUM(blockedRequests) as totalBlockedRequests');
		$usage->selectAdd('SUM(blockedApiRequests) as totalBlockedApiRequests');

		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			'totalViews' => $usage->totalViews,
			'totalPageViewsByBots' => $usage->totalPageViewsByBots,
			'totalPageViewsByAuthenticatedUsers' => $usage->totalPageViewsByAuthenticatedUsers,
			'totalSessionsStarted' => $usage->totalSessionsStarted,
			'totalCovers' => $usage->totalCovers,
			'totalErrors' => $usage->totalErrors,
			'totalAsyncRequests' => $usage->totalAsyncRequests,
			'totalGenealogySearches' => $usage->totalGenealogySearches,
			'totalGroupedWorkSearches' => $usage->totalGroupedWorkSearches,
			'totalOpenArchivesSearches' => $usage->totalOpenArchivesSearches,
			'totalUserListSearches' => $usage->totalUserListSearches,
			'totalWebsiteSearches' => $usage->totalWebsiteSearches,
			'totalEventsSearches' => $usage->totalEventsSearches,
			'totalEbscoEdsSearches' => $usage->totalEbscoEdsSearches,
			'totalBlockedRequests' => $usage->totalBlockedRequests,
			'totalBlockedApiRequests' => $usage->totalBlockedApiRequests,
		];
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_reports';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View Dashboards', 'View System Reports']);
	}
}