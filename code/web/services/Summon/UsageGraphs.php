<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
require_once ROOT_DIR . '/sys/Summon/SummonRecordUsage.php';

class Summon_UsageGraphs extends Admin_Admin {

	function getActiveAdminSection(): string {
		return 'summon';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#summon', 'Summon');
		$breadcrumbs[] = new Breadcrumb('/Summon/SummonDashboard', 'Summon Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}
}