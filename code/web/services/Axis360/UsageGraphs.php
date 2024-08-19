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

}