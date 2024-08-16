<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';

class SideLoads_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$title = 'Side Loading Usage Graph';

		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$title = $interface->getVariable('graphTitle');
		$this->display('usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/UsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	private function assignGraphSpecificTitle($stat) {
		global $interface;
		$title = $interface->getVariable('graphTitle');
		if ($stat == 'activeUsers') {
			$title .= ' - Active Users';
		}
		if ($stat == 'recordsAccessedOnline') {
			$title .= ' - Records Accessed Online';
		}
		$interface->assign('graphTitle', $title);
	}
}
