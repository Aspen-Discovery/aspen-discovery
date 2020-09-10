<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';

class ILS_Dashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$thisMonth = date('n');
		$thisYear = date('Y');
		$lastMonth = $thisMonth - 1;
		$lastMonthYear = $thisYear;
		if ($lastMonth == 0) {
			$lastMonth = 12;
			$lastMonthYear--;
		}
		$lastYear = $thisYear - 1;
		//Generate stats

		global $indexingProfiles;
		$profilesToGetStatsFor = [];
		foreach ($indexingProfiles as $indexingProfile) {
			$profilesToGetStatsFor[$indexingProfile->id] = $indexingProfile->name;
		}
		$interface->assign('profiles', $profilesToGetStatsFor);

		$activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats(null, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats(null, $lastYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats(null, null, $profilesToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getRecordStats($thisMonth, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getRecordStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getRecordStats(null, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getRecordStats(null, $lastYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getRecordStats(null, null, $profilesToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		$this->display('dashboard.tpl', 'ILS Usage Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($month, $year, $profilesToGetStatsFor): array
	{
		$userUsage = new UserILSUsage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->groupBy('indexingProfileId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('indexingProfileId');
		$userUsage->selectAdd('COUNT(id) as numUsers');
		$userUsage->selectAdd('SUM(IF(usageCount>0,1,0)) as usersWithHolds');
		$userUsage->selectAdd('SUM(selfRegistrationCount) AS numSelfRegistrations');
		$userUsage->selectAdd('SUM(IF(pdfDownloadCount>0,1,0)) as usersWithPdfDownloads');
		$userUsage->selectAdd('SUM(IF(supplementalFileDownloadCount>0,1,0)) as usersWithSupplementalFileDownloads');
		$userUsage->selectAdd('SUM(IF(pdfViewCount>0,1,0)) as usersWithPdfViews');

		$userUsage->find();
		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = [
				'totalUsers' => 0,
				'usersWithHolds' => 0,
				'usersWithPdfDownloads' => 0,
				'usersWithPdfViews' => 0,
				'numSelfRegistrations' => 0,
				'usersWithSupplementalFileDownloads' => 0
			];
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['totalUsers'] = $userUsage->numUsers;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['usersWithHolds'] = $userUsage->usersWithHolds;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['usersWithPdfDownloads'] = $userUsage->usersWithPdfDownloads;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['usersWithPdfViews'] = $userUsage->usersWithPdfViews;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['numSelfRegistrations'] = $userUsage->numSelfRegistrations;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId]['usersWithSupplementalFileDownloads'] = $userUsage->usersWithSupplementalFileDownloads;
		}
		return $usageStats;
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getRecordStats($month, $year, $profilesToGetStatsFor): array
	{
		$usage = new ILSRecordUsage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('indexingProfileId');
		$usage->selectAdd(null);
		$usage->selectAdd('indexingProfileId');

		$usage->selectAdd('COUNT(*) as numRecordViewed');
		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->selectAdd('SUM(pdfDownloadCount) as numPDFsDownloaded');
		$usage->selectAdd('SUM(pdfViewCount) as numPDFsViewed');
		$usage->selectAdd('SUM(supplementalFileDownloadCount) as numSupplementalFileDownloadCount');

		$usage->find();

		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = [
				'numRecordViewed' => 0,
				'numRecordsUsed' => 0,
				'numPDFsDownloaded' => 0,
				'numPDFsViewed' => 0,
				'numSupplementalFileDownloadCount' => 0
			];
		}
		while ($usage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$usage->indexingProfileId] = [
				'numRecordViewed' => $usage->numRecordViewed,
				'numRecordsUsed' => $usage->numRecordsUsed,
				'numPDFsDownloaded' => $usage->numPDFsDownloaded,
				'numPDFsViewed' => $usage->numPDFsViewed,
				'numSupplementalFileDownloadCount' => $usage->numSupplementalFileDownloadCount,
			];
		}
		return $usageStats;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ils_integration';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}