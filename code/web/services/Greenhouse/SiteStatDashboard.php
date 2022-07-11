<?php
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteStat.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_SiteStatDashboard extends Admin_Admin
{
	protected $todayDay;
	protected $thisMonth;
	protected $thisYear;
	protected $lastMonth;
	protected $lastMonthYear;
	protected $lastYear;

	function launch()
	{
		global $interface;

		$aspenSite = new AspenSite();
		$aspenSite->orderBy('name');
		$allSites = [];
		$aspenSite->find();
		$selectedSite = '';
		while ($aspenSite->fetch()){
			$allSites[$aspenSite->id] = $aspenSite->name;
			if ($selectedSite == ''){
				$selectedSite = $aspenSite->id;
			}
		}
		$interface->assign('allSites', $allSites);

		if (!empty($_REQUEST['site'])){
			$selectedSite = $_REQUEST['site'];
		}
		$interface->assign('selectedSite', $selectedSite);

		$this->loadDates();

		$statsToday = $this->getStats($selectedSite, $this->todayDay, $this->thisMonth, $this->thisYear);
		$interface->assign('siteStatsToday', $statsToday);
		$aspenUsageThisMonth = $this->getStats($selectedSite, null, $this->thisMonth, $this->thisYear);
		$interface->assign('siteStatsThisMonth', $aspenUsageThisMonth);
		$aspenUsageLastMonth = $this->getStats($selectedSite, null, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('siteStatsLastMonth', $aspenUsageLastMonth);
		$aspenUsageThisYear = $this->getStats($selectedSite, null, null, $this->thisYear);
		$interface->assign('siteStatsThisYear', $aspenUsageThisYear);
		$aspenUsageAllTime = $this->getStats($selectedSite, null, null, null);
		$interface->assign('siteStatsAllTime', $aspenUsageAllTime);
		
		$this->display('siteStatsDashboard.tpl', 'Aspen Site Stats Dashboard', '');
	}

	/**
	 * @param string $selectedSite
	 * @param string|null $day
	 * @param string|null $month
	 * @param string|null $year
	 * @return int[]
	 */
	function getStats($selectedSite, $day, $month, $year): array
	{
		$siteStat = new AspenSiteStat();
		$siteStat->aspenSiteId = $selectedSite;
		if ($day != null){
			$siteStat->day = $day;
		}
		if ($month != null){
			$siteStat->month = $month;
		}
		if ($year != null){
			$siteStat->year = $year;
		}

		$siteStat->selectAdd();
		$siteStat->selectAdd('MIN(minDataDiskSpace) as minDataDiskSpace');
		$siteStat->selectAdd('MIN(minUsrDiskSpace) as minUsrDiskSpace');
		$siteStat->selectAdd('MIN(minAvailableMemory) as minAvailableMemory');
		$siteStat->selectAdd('MAX(maxAvailableMemory) as maxAvailableMemory');
		$siteStat->selectAdd('MIN(minLoadPerCPU) as minLoadPerCPU');
		$siteStat->selectAdd('MAX(maxLoadPerCPU) as maxLoadPerCPU');
		$siteStat->selectAdd('MAX(maxWaitTime) as maxWaitTime');

		$siteStat->find(true);

		return [
			'minDataDiskSpace' => $siteStat->minDataDiskSpace,
			'minUsrDiskSpace' => $siteStat->minUsrDiskSpace,
			'minAvailableMemory' => $siteStat->minAvailableMemory,
			'maxAvailableMemory' => $siteStat->maxAvailableMemory,
			'minLoadPerCPU' => $siteStat->minLoadPerCPU,
			'maxLoadPerCPU' => $siteStat->maxLoadPerCPU,
			'maxWaitTime' => $siteStat->maxWaitTime,
		];
	}

	function loadDates(){
		$now = new DateTime();
		$today = $now->setTime(0, 0);
		$this->todayDay = date('j', $today->getTimestamp());
		$this->thisMonth = date('n');
		$this->thisYear = date('Y');
		$this->lastMonth = $this->thisMonth - 1;
		$this->lastMonthYear = $this->thisYear;
		if ($this->lastMonth == 0) {
			$this->lastMonth = 12;
			$this->lastMonthYear--;
		}
		$this->lastYear = $this->thisYear - 1;
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Stats Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}
}