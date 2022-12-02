<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once(ROOT_DIR . "/PHPExcel.php");

class Admin_CronLog extends Admin_Admin {
	function launch() {
		global $interface;

		$logEntries = [];
		$cronLogEntry = new CronLogEntry();
		$total = $cronLogEntry->count();
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->orderBy('startTime DESC');
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);
		$cronLogEntry->limit(($page - 1) * 30, 30);
		$cronLogEntry->find();
		while ($cronLogEntry->fetch()) {
			$logEntries[] = clone($cronLogEntry);
		}
		$interface->assign('logEntries', $logEntries);

		$options = [
			'totalItems' => $total,
			'fileName' => '/Admin/CronLog?page=%d',
			'perPage' => 30,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('cronLog.tpl', 'Cron Log');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Cron Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View System Reports');
	}
}
