<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php';

class Admin_UsageDashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('AspenUsage');
		//$instanceWebResourceUsage = $this->loadInstanceInformation('WebResourceUsage');
		$this->loadDates();

		$aspenUsageThisMonth = $this->getStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('aspenUsageThisMonth', $aspenUsageThisMonth);
		$aspenUsageLastMonth = $this->getStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('aspenUsageLastMonth', $aspenUsageLastMonth);
		$aspenUsageThisYear = $this->getStats($instanceName, null, $this->thisYear);
		$interface->assign('aspenUsageThisYear', $aspenUsageThisYear);
		$aspenUsageAllTime = $this->getStats($instanceName, null, null);
		$interface->assign('aspenUsageAllTime', $aspenUsageAllTime);

		$webResources = $this->getWebResources();
		$webResourceUsage = [];
		foreach ($webResources as $webResource) {
			if (!isset($webResourceUsage)) {
				$webResourceUsage[] = array(
					'name' => $webResource,
					'thisMonth' => $this->getWebResourceStats($instanceName, $webResource, $this->thisMonth, $this->thisYear),
					'lastMonth' => $this->getWebResourceStats($instanceName, $webResource,  $this->lastMonth, $this->lastMonthYear),
					'thisYear' => $this->getWebResourceStats($instanceName, $webResource,  null, $this->thisYear),
					'allTime' => $this->getWebResourceStats($instanceName, $webResource,  null, null)
				);
			} elseif (!in_array( $webResource, array_column($webResourceUsage, 'name'))) {
				$webResourceUsage[] = array(
					'name' =>  $webResource,
					'thisMonth' => $this->getWebResourceStats($instanceName, $webResource, $this->thisMonth, $this->thisYear),
					'lastMonth' => $this->getWebResourceStats($instanceName, $webResource,  $this->lastMonth, $this->lastMonthYear),
					'thisYear' => $this->getWebResourceStats($instanceName, $webResource,  null, $this->thisYear),
					'allTime' => $this->getWebResourceStats($instanceName, $webResource,  null, null)
				);
			}
		}
		$interface->assign('webResourceUsage', $webResourceUsage);
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

	function getWebResources(): array
	{
		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResources = [];
		$object = new WebResource();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()) {
			$webResources[$object->name] = $object->name;
		}
		return $webResources;
	}
	/**
	 * @param string|null $instanceName
	 * @param string $resourceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int[]
	 */
	function getWebResourceStats($instanceName, $resourceName, $month, $year): array
	{
		$usage = new WebResourceUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		if (!empty($resourceName)){
			$usage->resourceName = $resourceName;
		}

		$usage->selectAdd();
		$usage->selectAdd('SUM(pageViews) as totalViews');
		$usage->selectAdd('SUM(pageViewsByAuthenticatedUsers) as totalPageViewsByAuthenticatedUsers');
		$usage->selectAdd('SUM(pageViewsInLibrary) as totalPageViewsInLibrary');

		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			'name' => $usage->resourceName,
			'totalViews' => $usage->totalViews,
			'totalPageViewsByAuthenticatedUsers' => $usage->totalPageViewsByAuthenticatedUsers,
			'totalPageViewsInLibrary' => $usage->totalPageViewsInLibrary,
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