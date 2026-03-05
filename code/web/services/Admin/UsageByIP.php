<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';
require_once ROOT_DIR . '/sys/Pager.php';

class Admin_UsageByIP extends Admin_Dashboard {
	function launch() {
		global $interface;
		$instanceName = $this->loadInstanceInformation('UsageByIPAddress');

		$thisMonth = date('n');
		$thisYear = date('Y');

		// Pagination setup
		$page = max(1, (int)($_REQUEST['page'] ?? 1));
		$pageSize = min(100,(int)($_REQUEST['pageSize'] ?? 30));
		$interface->assign('page', $page);
		$interface->assign('recordsPerPage', $pageSize);

		$sort = $this->sortableSetUp();

		//Load a list of IP addresses for the current month.
		$usageByIP = new UsageByIPAddress();
		$usageByIP->month = $thisMonth;
		$usageByIP->year = $thisYear;

		if (!empty($instanceName)) {
			$usageByIP->instance = $instanceName;
		}
		$usageByIP->groupBy('ipAddress');
		$total = $usageByIP->count();
		$usageByIP->selectAdd();
		$usageByIP->selectAdd('ipAddress');
		$usageByIP->selectAdd('SUM(numRequests) AS numRequests');
		$usageByIP->selectAdd('SUM(numBlockedRequests) AS numBlockedRequests');
		$usageByIP->selectAdd('SUM(numBlockedApiRequests) AS numBlockedApiRequests');
		$usageByIP->selectAdd('SUM(numLoginAttempts) AS numLoginAttempts');
		$usageByIP->selectAdd('SUM(numFailedLoginAttempts) AS numFailedLoginAttempts');
		$usageByIP->selectAdd('SUM(numSpammyRequests) AS numSpammyRequests');
		$usageByIP->selectAdd('MAX(lastRequest) AS lastRequest');
		// For IPv4 and IPv6 address sorting to work, use numeric conversion.
		$orderBy = $sort;
		if (preg_match('/^ipAddress\s+(asc|desc)$/i', $sort, $m)) {
			$dir     = strtoupper($m[1]);
			$orderBy = "INET6_ATON(ipAddress) $dir";
		}
		$usageByIP->orderBy($orderBy);
		$usageByIP->limit(($page - 1) * $pageSize, $pageSize);

		//TODO: Apply filters

		$allIpStats = [];
		$usageByIP->find();
		while ($usageByIP->fetch()) {
			$allIpStats[] = clone $usageByIP;
		}
		$interface->assign('allIpStats', $allIpStats);

		$options = [
			'totalItems' => $total,
			'perPage' => $pageSize,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('usage_by_ip.tpl', 'Aspen Usage By IP');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage By IP Address');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View System Reports');
	}

	private function sortableSetUp(): string {
		global $interface;
		$sort = $_REQUEST['sort'] ?? 'lastRequest desc';
		$sortableFields = [
			['property'=>'ipAddress', 'label'=>'IP Address'],
			['property'=>'numRequests', 'label'=>'Total Requests'],
			['property'=>'numBlockedRequests', 'label'=>'Blocked Requests'],
			['property'=>'numBlockedApiRequests', 'label'=>'Blocked API Requests'],
			['property'=>'numLoginAttempts', 'label'=>'Login Attempts'],
			['property'=>'numFailedLoginAttempts', 'label'=>'Failed Logins'],
			['property'=>'numSpammyRequests', 'label'=>'Spammy Requests'],
			['property'=>'lastRequest', 'label'=>'Last Request'],
		];
		$allowedSorts = [];
		foreach ($sortableFields as $field) {
			$allowedSorts[] = $field['property'] . ' asc';
			$allowedSorts[] = $field['property'] . ' desc';
		}
		if (!in_array($sort, $allowedSorts)) {
			$sort = 'lastRequest desc';
		}
		$interface->assign('sortableFields', $sortableFields);
		$interface->assign('sort', $sort);
		$interface->assign('canSort', true);
		return $sort;
	}
}