<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_SiteStatus extends Admin_Admin
{

	function launch()
	{
		global $interface;
		if (isset($_REQUEST['showErrorsOnly'])){
			$interface->assign('showErrorsOnly', true);
		}
		$sites = new AspenSite();
		$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		$sites->orderBy('name ASC');
		$sites->find();
		$siteStatuses = [];
		$allChecks = [];
		$checksWithErrors = [];
		$sitesWithErrors = [];
		while ($sites->fetch()){
			$siteStatus = $sites->getCachedStatus();
			$siteStatuses[] = $siteStatus;
			foreach ($siteStatus['checks'] as $key => $check){
				$allChecks[$key] = $check['name'];
				if ($check['status'] != 'okay'){
					$checksWithErrors[$key] = $key;
					$sitesWithErrors[$sites->name] = $sites->name;
				}
			}
		}
		asort($allChecks);

		$interface->assign('allChecks', $allChecks);
		$interface->assign('siteStatuses', $siteStatuses);
		$interface->assign('checksWithErrors', $checksWithErrors);
		$interface->assign('sitesWithErrors', $sitesWithErrors);
		$this->display('siteStatus.tpl', 'Aspen Site Status',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Status');
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