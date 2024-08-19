<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class Axis360_UsageGraphs extends Admin_Admin {
	function launch() {
		$title = 'Boundless Usage Graph';
		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Boundless');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'boundless';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Dashboards',
		]);
	}

	private function assignGraphSpecificTitle($stat) {
		global $interface;
		$title = $interface->getVariable('graphTitle');
		switch ($stat) {
			case 'activeUsers':
				$title .= ' - Active Users';
				break;
			case 'recordsWithUsage':
				$title .= ' - Records With Usage';
				break;
			case 'loans':
				$title .= ' - Total Checkouts';
				break;
			case 'holds':
				$title .= ' - Total Holds';
				break;
			case 'renewals':
				$title .= ' - Total Renewals';
				break;
			case 'earlyReturns':
				$title .= ' - Total Early Returns';
				break;
			case 'holdsCancelled':
				$title .= ' - Total Holds Cancelled';
				break;
			case 'holdsFrozen':
				$title .= ' - Total Holds Frozen';
				break;
			case 'holdsThawed':
				$title .= ' - Total Holds Thawed';
				break;
			case 'apiErrors':
				$title .= ' - Total API Errors';
				break;
			case 'connectionFailures':
				$title .= ' - Total Connection Failures';
				break;
			case 'general':
				$title .= ' - General';
				break;
		}
		$interface->assign('graphTitle', $title);
	}
}