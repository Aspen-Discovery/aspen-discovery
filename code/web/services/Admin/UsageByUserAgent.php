<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/UserAgent.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByUserAgent.php';

class Admin_UsageByUserAgent extends Admin_Dashboard {
	protected $thisMonth;
	protected $thisYear;
	protected $lastMonth;
	protected $lastMonthYear;
	protected $lastYear;

	function launch() {
		global $interface;

		//Get a list of instances that we have stats for.
		$instanceName = $this->loadInstanceInformation('UsageByUserAgent');

		$this->loadDates();

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : 30; // to adjust number of items listed on a page
		$interface->assign('recordsPerPage', $pageSize);
		$interface->assign('page', $page);

		$userAgent = new UserAgent();
		$userAgent->orderBy('userAgent');
		$total = $userAgent->count();
		$userAgent->limit(($page - 1) * $pageSize, $pageSize);
		$userAgent->find();
		while ($userAgent->fetch()) {
			$allUserAgents[$userAgent->id] = $userAgent->userAgent;
		}
		$interface->assign('allUserAgents', $allUserAgents);

		$activeUsersThisMonth = $this->getUsageStats($instanceName, $this->thisMonth, $this->thisYear, $allUserAgents);
		$interface->assign('usageThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUsageStats($instanceName, $this->lastMonth, $this->lastMonthYear, $allUserAgents);
		$interface->assign('usageLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUsageStats($instanceName, null, $this->thisYear, $allUserAgents);
		$interface->assign('usageThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUsageStats($instanceName, null, $this->lastYear, $allUserAgents);
		$interface->assign('usageLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUsageStats($instanceName, null, null, $allUserAgents);
		$interface->assign('usageAllTime', $activeUsersAllTime);

		$options = [
			'totalItems' => $total,
			'perPage' => $pageSize,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('usage_by_user_agent.tpl', 'Aspen Usage By User Agent');
	}

	public function getUsageStats($instanceName, $month, $year, $allUserAgents): array {
		$usageByUserAgent = new UsageByUserAgent();
		$usageByUserAgent->month = $month;
		$usageByUserAgent->year = $year;

		if (!empty($instanceName)) {
			$usageByUserAgent->instance = $instanceName;
		}
		if ($month != null) {
			$usageByUserAgent->month = $month;
		}
		if ($year != null) {
			$usageByUserAgent->year = $year;
		}

		$usageByUserAgent->groupBy('userAgentId');
		$usageByUserAgent->selectAdd();
		$usageByUserAgent->selectAdd('userAgentId');
		$usageByUserAgent->selectAdd('SUM(numRequests) AS numRequests');
		$usageByUserAgent->selectAdd('SUM(numBlockedRequests) AS numBlockedRequests');

		$allUserAgentStats = [];
		$usageByUserAgent->find();
		while ($usageByUserAgent->fetch()) {
			if (array_key_exists($usageByUserAgent->userAgentId, $allUserAgents)) {
				$userAgent = $allUserAgents[$usageByUserAgent->userAgentId];
				$allUserAgentStats[$usageByUserAgent->userAgentId] = [
					'userAgent' => $userAgent,
					'numRequests' => $usageByUserAgent->numRequests,
					'numBlockedRequests' => $usageByUserAgent->numBlockedRequests,
				];
			}
		}

		return $allUserAgentStats;
	}

	function loadDates() {
		$this->thisMonth = date('n');
		$this->thisYear = date('Y');
		$this->lastMonth = $this->thisMonth - 1;
		$this->lastMonthYear = $this->thisYear;
		if ($this->lastMonth == 0) {
			$this->lastMonth = 12;
			$this->lastMonthYear--;
		}
		$this->lastYear = $this->thisYear - 1;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage By User Agent');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View System Reports');
	}
}