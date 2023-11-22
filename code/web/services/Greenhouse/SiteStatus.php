<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_SiteStatus extends Admin_Admin {

	function launch() {
		global $interface;
		$showErrorsOnly = false;
		if (isset($_REQUEST['showErrorsOnly'])) {
			$showErrorsOnly = true;
		}
		$interface->assign('showErrorsOnly', $showErrorsOnly);

		$serversToShow = 1;
		if (isset($_REQUEST['serversToShow'])) {
			$serversToShow = $_REQUEST['serversToShow'];
		}
		$interface->assign('serversToShow', $serversToShow);

		$serversToShowOptions = [
			1 => 'All Servers',
			2 => 'Production & Soft Launch Servers Only',
			3 => 'Test Servers Only',
			4 => 'Implementation Servers Only',
			5 => 'Test and Implementation Servers',
		];
		$interface->assign('serversToShowOptions', $serversToShowOptions);

		$versionToShow = '';
		if (isset($_REQUEST['versionToShow'])) {
			$versionToShow = $_REQUEST['versionToShow'];
		}
		$interface->assign('versionToShow', $versionToShow);
		$sites = new AspenSite();
		$interface->assign('numTotalResults', $sites->count());

		if ($serversToShow == 1) {
			$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		}elseif ($serversToShow == 2) {
			$sites->whereAdd('implementationStatus = 2 OR implementationStatus = 3');
			$sites->whereAdd('siteType = 0 OR siteType = 2');
		}elseif ($serversToShow == 3) {
			$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
			$sites->whereAdd('siteType = 1 OR siteType = 3');
		}elseif ($serversToShow == 4) {
			$sites->whereAdd('implementationStatus = 1');
		}elseif ($serversToShow == 5) {
			//First bit gets implementation servers and second bit gets test servers
			$sites->whereAdd('implementationStatus = 1 OR ((implementationStatus = 2 OR implementationStatus = 3) AND (siteType = 1 OR siteType = 3))');
		}
		if (!empty($versionToShow)) {
			$sites->whereAdd("version LIKE '$versionToShow%'");
		}
		$sites->monitored = 1;

		$sites->orderBy('name ASC');
		$sites->find();
		$interface->assign('numFilteredResults', $sites->getNumResults());
		$siteStatuses = [];
		$allChecks = [];
		$checksWithErrors = [];
		$sitesWithErrors = [];
		while ($sites->fetch()) {
			$siteStatus = $sites->getCachedStatus();
			$siteStatuses[] = $siteStatus;
			foreach ($siteStatus['checks'] as $key => $check) {
				$allChecks[$key] = $check['name'];
				if ($check['status'] != 'okay') {
					$checksWithErrors[$key] = $key;
					$sitesWithErrors[$sites->name] = $sites->name;
				}
			}
			if (!$sites->isOnline){
				$sitesWithErrors[$sites->name] = $sites->name;
			}
		}
		asort($allChecks);
		if ($showErrorsOnly) {
			$interface->assign('numFilteredResults', count($sitesWithErrors));
		}

		$interface->assign('allChecks', $allChecks);
		$interface->assign('siteStatuses', $siteStatuses);
		$interface->assign('checksWithErrors', $checksWithErrors);
		$interface->assign('sitesWithErrors', $sitesWithErrors);
		$this->display('siteStatus.tpl', 'Aspen Site Status', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Status');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}
}