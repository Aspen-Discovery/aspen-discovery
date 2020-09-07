<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';

class Admin_UsageDashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;
		global $indexingProfiles;
		$profilesToGetStatsFor = [];
		foreach ($indexingProfiles as $indexingProfile){
			$profilesToGetStatsFor[$indexingProfile->id] = $indexingProfile->name;
		}
		$interface->assign('profiles', $profilesToGetStatsFor);

		$thisMonth = date('n');
		$thisYear = date('Y');
		$lastMonth = $thisMonth - 1;
		$lastMonthYear = $thisYear;
		if ($lastMonth == 0){
			$lastMonth = 12;
			$lastMonthYear--;
		}

		$usageThisMonth = $this->getStats($thisMonth, $thisYear);
		$interface->assign('usageThisMonth', $usageThisMonth);
		$usageLastMonth = $this->getStats($lastMonth, $lastMonthYear);
		$interface->assign('usageLastMonth', $usageLastMonth);
		$usageThisYear = $this->getStats(null, $thisYear);
		$interface->assign('usageThisYear', $usageThisYear);
		$usageAllTime = $this->getStats(null, null);
		$interface->assign('usageAllTime', $usageAllTime);

		$this->display('usage_dashboard.tpl', 'Aspen Usage Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int[]
	 */
	function getStats($month, $year): array
	{
		$usage = new AspenUsage();
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->selectAdd();
		$usage->selectAdd('SUM(pageViews) as totalViews');
		$usage->selectAdd('SUM(coverViews) as totalCovers');
		$usage->selectAdd('SUM(pagesWithErrors) as totalErrors');
		$usage->selectAdd('SUM(ajaxRequests) as totalAsyncRequests');
		$usage->selectAdd('SUM(genealogySearches) as totalGenealogySearches');
		$usage->selectAdd('SUM(groupedWorkSearches) as totalGroupedWorkSearches');
		$usage->selectAdd('SUM(islandoraSearches) as totalIslandoraSearches');
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
			'totalCovers' => $usage->totalCovers,
			'totalErrors' => $usage->totalErrors,
			'totalAsyncRequests' => $usage->totalAsyncRequests,
			'totalGenealogySearches' => $usage->totalGenealogySearches,
			'totalGroupedWorkSearches' => $usage->totalGroupedWorkSearches,
			'totalIslandoraSearches' => $usage->totalIslandoraSearches,
			'totalOpenArchivesSearches' => $usage->totalOpenArchivesSearches,
			'totalUserListSearches' => $usage->totalUserListSearches,
			'totalWebsiteSearches' => $usage->totalWebsiteSearches,
			'totalEventsSearches' => $usage->totalEventsSearches,
			'totalEbscoEdsSearches' => $usage->totalEbscoEdsSearches,
			'totalBlockedRequests' => $usage->totalBlockedRequests,
			'totalBlockedApiRequests' => $usage->totalBlockedApiRequests,
		];
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_reports';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View System Reports');
	}
}