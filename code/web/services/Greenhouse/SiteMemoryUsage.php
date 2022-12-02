<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteMemoryUsage.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class SiteMemoryUsage extends Admin_Admin {
	function launch() {
		global $interface;
		$aspenSite = new AspenSite();
		$aspenSite->orderBy('name');
		$allSites = [];
		$aspenSite->find();
		$selectedSite = '';
		while ($aspenSite->fetch()) {
			$allSites[$aspenSite->id] = $aspenSite->name;
			if ($selectedSite == '') {
				$selectedSite = $aspenSite->id;
			}
		}
		$interface->assign('allSites', $allSites);

		if (!empty($_REQUEST['site'])) {
			$selectedSite = $_REQUEST['site'];
		}
		$interface->assign('selectedSite', $selectedSite);

		//Get stats
		if (!empty($selectedSite)) {
			$dataSeries = [];
			$columnLabels = [];

			$dataSeries['Total Memory'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$dataSeries['Used Memory'] = [
				'borderColor' => 'rgba(255, 159, 64, 1)',
				'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
				'data' => [],
			];

			$aspenSiteMemoryStats = new AspenSiteMemoryUsage();
			$aspenSiteMemoryStats->aspenSiteId = $selectedSite;
			$aspenSiteMemoryStats->orderBy('timestamp');

			$aspenSiteMemoryStats->find();
			while ($aspenSiteMemoryStats->fetch()) {
				$columnLabel = date('m/d/y h:i', $aspenSiteMemoryStats->timestamp);
				$columnLabels[] = $columnLabel;
				$dataSeries['Total Memory']['data'][$aspenSiteMemoryStats->timestamp] = $aspenSiteMemoryStats->totalMemory;
				$dataSeries['Used Memory']['data'][$aspenSiteMemoryStats->timestamp] = $aspenSiteMemoryStats->totalMemory - $aspenSiteMemoryStats->availableMemory;
			}

			$interface->assign('columnLabels', $columnLabels);
			$interface->assign('dataSeries', $dataSeries);
		}


		$this->display('siteMemory.tpl', 'Aspen Site Memory Dashboard', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Memory Usage');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}
}