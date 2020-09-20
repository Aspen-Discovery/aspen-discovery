<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';

class Admin_UsageByIP extends Admin_Admin
{
	function launch()
	{
		global $interface;
		global $instanceName;

		$thisMonth = date('n');
		$thisYear = date('Y');

		//TODO: Get a list of instance names for the current month.
		$showStatsForAllInstances = true;

		//Load a list of IP addresses for the current month.
		$usageByIP = new UsageByIPAddress();
		$usageByIP->month = $thisMonth;
		$usageByIP->year = $thisYear;
		if ($showStatsForAllInstances){
			$usageByIP->groupBy('ipAddress');
			$usageByIP->selectAdd();
			$usageByIP->selectAdd('ipAddress');
			$usageByIP->selectAdd('SUM(numRequests) AS numRequests');
			$usageByIP->selectAdd('SUM(numBlockedRequests) AS numBlockedRequests');
			$usageByIP->selectAdd('SUM(numBlockedApiRequests) AS numBlockedApiRequests');
			$usageByIP->selectAdd('MAX(lastRequest) AS lastRequest');
		}else{
			//TODO: Filter by the instance name
		}
		$usageByIP->orderBy('ipAddress');

		//TODO: Apply filters

		$allIpStats = [];
		$usageByIP->find();
		while ($usageByIP->fetch()){
			$ipAddress = ip2long($usageByIP->ipAddress);
			if ($ipAddress !== false){
				$allIpStats[$ipAddress] = clone $usageByIP;
			}else{
				$allIpStats[] = clone $usageByIP;
			}
		}
		ksort($allIpStats);
		$interface->assign('allIpStats', $allIpStats);

		$this->display('usage_by_ip.tpl', 'Aspen Usage Dashboard');
	}


	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage By IP Address');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_reports';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View System Reports');
	}
}