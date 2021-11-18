<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_UpdateCenter extends Admin_Admin
{

	function launch()
	{
		$sites = new AspenSite();
		$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		$sites->orderBy('implementationStatus ASC, name ASC');
		$sites->find();
		$allSites = [];
		while ($sites->fetch()){
			$allSites[] = clone $sites;
		}
		global $interface;
		$interface->assign('allSites', $allSites);
		$this->display('updateCenter.tpl', 'Aspen Upgrade Center',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		$breadcrumbs[] = new Breadcrumb('', 'Upgrade Center');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source = 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}
}