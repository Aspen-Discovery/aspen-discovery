<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';

class ILS_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('UserILSUsage');
		$this->loadDates();

		//Generate stats

		global $indexingProfiles;
		$profilesToGetStatsFor = [];
		foreach ($indexingProfiles as $indexingProfile) {
			$profilesToGetStatsFor[$indexingProfile->id] = $indexingProfile->name;
		}
		$interface->assign('profiles', $profilesToGetStatsFor);

		$activeUsersThisMonth = $this->getUserStats($instanceName, $this->thisMonth, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($instanceName, $this->lastMonth, $this->lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats($instanceName, null, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats($instanceName, null, $this->lastYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats($instanceName, null, null, $profilesToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getRecordStats($instanceName, null, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getRecordStats($instanceName, null, $this->lastYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getRecordStats($instanceName, null, null, $profilesToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		$this->display('dashboard.tpl', 'ILS Usage Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($instanceName, $month, $year, $profilesToGetStatsFor): array
	{
		$userUsage = new UserILSUsage();
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
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
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getRecordStats($instanceName, $month, $year, $profilesToGetStatsFor): array
	{
		$usage = new ILSRecordUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
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

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ils_integration';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}