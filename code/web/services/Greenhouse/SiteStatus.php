<?php

require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
class SiteStatus extends Action
{

	function launch()
	{
		$sites = new AspenSite();
		$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		$sites->orderBy('siteType ASC, implementationStatus DESC, name ASC');
		$sites->find();
		$siteStatuses = [];
		$allChecks = [];
		while ($sites->fetch()){
			$siteStatus = $sites->getStatus();
			$siteStatuses[] = $siteStatus;
			foreach ($siteStatus['checks'] as $key => $check){
				$allChecks[$key] = $check['name'];
			}
		}
		asort($allChecks);
		global $interface;
		$interface->assign('allChecks', $allChecks);
		$interface->assign('siteStatuses', $siteStatuses);
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
}