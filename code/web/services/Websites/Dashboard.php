<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsitePage.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';

class Websites_Dashboard extends Admin_Dashboard {
	function launch() {
		global $interface;

		$instanceName = $this->loadInstanceInformation('WebPageUsage');
		$this->loadDates();

		//Generate stats
		$website = new WebsiteIndexSetting();
		$websitesToGetStatsFor = [];
		$website->orderBy('name');
		$website->find();
		while ($website->fetch()) {
			$websitesToGetStatsFor[$website->id] = $website->name;
		}

		$interface->assign('websites', $websitesToGetStatsFor);

		$activeUsersThisMonth = $this->getUserStats($instanceName, $this->thisMonth, $this->thisYear, $websitesToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($instanceName, $this->lastMonth, $this->lastMonthYear, $websitesToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats($instanceName, null, $this->thisYear, $websitesToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats($instanceName, null, $this->lastYear, $websitesToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats($instanceName, null, null, $websitesToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getSiteStats($instanceName, $this->thisMonth, $this->thisYear, $websitesToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getSiteStats($instanceName, $this->lastMonth, $this->lastMonthYear, $websitesToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getSiteStats($instanceName, null, $this->thisYear, $websitesToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getSiteStats($instanceName, null, $this->lastYear, $websitesToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getSiteStats($instanceName, null, null, $websitesToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		$this->display('dashboard.tpl', 'Website Search Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $websitesToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($instanceName, $month, $year, $websitesToGetStatsFor): array {
		$userUsage = new UserWebsiteUsage();
		if (!empty($instanceName)) {
			$userUsage->instance = $instanceName;
		}
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->groupBy('websiteId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('websiteId');
		$userUsage->selectAdd('COUNT(id) as numUsers');
		$userUsage->find();
		$usageStats = [];
		foreach ($websitesToGetStatsFor as $websiteId => $name) {
			$usageStats[$websiteId] = 0;
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->websiteId] = $userUsage->numUsers;
		}
		return $usageStats;
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $websitesToGetStatsFor
	 * @return int[]
	 */
	public function getSiteStats($instanceName, $month, $year, $websitesToGetStatsFor): array {
		$usage = new WebPageUsage();
		$recordInfo = new WebsitePage();
		$usage->joinAdd($recordInfo, 'INNER', 'record', 'webPageId', 'id');
		if (!empty($instanceName)) {
			$usage->instance = $instanceName;
		}
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('websiteId');
		$usage->selectAdd(null);
		$usage->selectAdd('record.websiteId');

		$usage->selectAdd('SUM(IF(timesViewedInSearch>0,1,0)) as numRecordViewed');
		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->find();

		$usageStats = [];
		foreach ($websitesToGetStatsFor as $websiteId => $siteName) {
			$usageStats[$websiteId] = [
				'numRecordViewed' => 0,
				'numRecordsUsed' => 0,
			];
		}
		while ($usage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$usage->websiteId] = [
				'numRecordViewed' => $usage->numRecordViewed,
				'numRecordsUsed' => $usage->numRecordsUsed,
			];
		}
		return $usageStats;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
		$breadcrumbs[] = new Breadcrumb('/Websites/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'web_indexer';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Dashboards',
		]);
	}
}
