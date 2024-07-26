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
}