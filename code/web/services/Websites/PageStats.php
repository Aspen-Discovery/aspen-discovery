<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsitePage.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php';

class Websites_PageStats extends Admin_Admin {
	function launch() {
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
		$websiteId = $_REQUEST['siteId'];
		$website = new WebsiteIndexSetting();
		$website->id = $websiteId;
		if (!$website->find(true)) {
			$interface->assign('error', 'Unable to find the specified website');
		} else {
			$interface->assign('websiteName', $website->name);
			$websitePage = new WebsitePage();
			$pagesToLoadStatsFor = [];
			$websitePage->websiteId = $websiteId;
			$websitePage->deleted = "0";
			$websitePage->orderBy('url');
			$websitePage->find();
			while ($websitePage->fetch()) {
				$pagesToLoadStatsFor[$websitePage->id] = $websitePage->url;
			}
			$interface->assign('pages', $pagesToLoadStatsFor);

			$activeRecordsThisMonth = $this->getPageStats($thisMonth, $thisYear, $pagesToLoadStatsFor);
			$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
			$activeRecordsLastMonth = $this->getPageStats($lastMonth, $lastMonthYear, $pagesToLoadStatsFor);
			$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
			$activeRecordsThisYear = $this->getPageStats(null, $thisYear, $pagesToLoadStatsFor);
			$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
			$activeRecordsLastYear = $this->getPageStats(null, $lastYear, $pagesToLoadStatsFor);
			$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
			$activeRecordsAllTime = $this->getPageStats(null, null, $pagesToLoadStatsFor);
			$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		}

		$this->display('pageStats.tpl', 'Page Stats');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $pagesToLoadStatsFor
	 * @return int[]
	 */
	public function getPageStats($month, $year, $pagesToLoadStatsFor): array {
		$usage = new WebPageUsage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('webPageId');

		$usage->selectAdd();
		$usage->selectAdd('webPageId');
		$usage->selectAdd('SUM(timesViewedInSearch) as numRecordViewed');
		$usage->selectAdd('SUM(timesUsed) as numRecordsUsed');
		$usage->find();

		$usageStats = [];
		foreach ($pagesToLoadStatsFor as $pageId => $url) {
			$usageStats[$pageId] = [
				'numRecordsViewed' => 0,
				'numRecordsUsed' => 0,
			];
		}
		while ($usage->fetch()) {
			//Ignore anything that is deleted
			if (array_key_exists($usage->webPageId, $usageStats)) {
				/** @noinspection PhpUndefinedFieldInspection */
				$usageStats[$usage->webPageId] = [
					'numRecordsViewed' => $usage->numRecordViewed,
					'numRecordsUsed' => $usage->numRecordsUsed,
				];
			}
		}
		return $usageStats;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
		$breadcrumbs[] = new Breadcrumb('/Websites/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Page Stats');
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