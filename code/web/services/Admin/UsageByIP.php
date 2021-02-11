<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';

class Admin_UsageByIP extends Admin_Dashboard
{
	function launch()
	{
		global $interface;
		$instanceName = $this->loadInstanceInformation('UsageByIPAddress');

		$thisMonth = date('n');
		$thisYear = date('Y');

		//Load a list of IP addresses for the current month.
		$usageByIP = new UsageByIPAddress();
		$usageByIP->month = $thisMonth;
		$usageByIP->year = $thisYear;

		if (!empty($instanceName)){
			$usageByIP->instance = $instanceName;
		}

		$usageByIP->groupBy('ipAddress');
		$usageByIP->selectAdd();
		$usageByIP->selectAdd('ipAddress');
		$usageByIP->selectAdd('SUM(numRequests) AS numRequests');
		$usageByIP->selectAdd('SUM(numBlockedRequests) AS numBlockedRequests');
		$usageByIP->selectAdd('SUM(numBlockedApiRequests) AS numBlockedApiRequests');
		$usageByIP->selectAdd('SUM(numLoginAttempts) AS numLoginAttempts');
		$usageByIP->selectAdd('SUM(numFailedLoginAttempts) AS numFailedLoginAttempts');
		$usageByIP->selectAdd('MAX(lastRequest) AS lastRequest');

		$usageByIP->orderBy('lastRequest DESC');

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

		$this->display('usage_by_ip.tpl', 'Aspen Usage By IP');
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