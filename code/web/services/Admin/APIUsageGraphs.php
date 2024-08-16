<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';

class Admin_APIUsageGraphs extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$title = 'Aspen Discovery API Usage Graph';
		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$title = $interface->getVariable('graphTitle');
		$this->display('usage-graph.tpl', $title);
	}
	
	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/APIUsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string
	{
		return 'system_reports';
	}

	function canView(): bool
	{
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	private function assignGraphSpecificTitle()
	{
		global $interface;
		$title = 'Aspen Discovery API Usage Graph';
		$title .= ' - runPendingDatabaseUpdates';
		$interface->assign('graphTitle', $title);
	}
}
