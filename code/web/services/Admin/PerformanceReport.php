<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/SlowPage.php';
require_once ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php';

class Admin_PerformanceReport extends Admin_Admin {
	function launch() {
		global $interface;

		$thisMonth = date('n');
		$thisYear = date('Y');

		$slowPages = [];
		$slowPages = $this->getSlowPageStats($thisMonth, $thisYear, 'this_month', $slowPages);
		foreach ($slowPages as $key => $slowPage) {
			$totalCount = $slowPage['this_month_fast'] + $slowPage['this_month_acceptable'] + $slowPage['this_month_slow'] + $slowPage['this_month_slower'] + $slowPage['this_month_very_slow'];
			$weightedCount = $slowPage['this_month_fast'] + $slowPage['this_month_acceptable'] * 2 + $slowPage['this_month_slow'] * 3 + $slowPage['this_month_slower'] * 4 + $slowPage['this_month_very_slow'] * 5;
			$averageSlowness = round($weightedCount / $totalCount);
			$slowPages[$key]['average'] = $averageSlowness;
			$slowPages[$key]['total'] = $totalCount;
		}
		ksort($slowPages);
		$interface->assign('slowPages', $slowPages);

		$slowAsyncRequests = [];
		$slowAsyncRequests = $this->getSlowAsyncRequestStats($thisMonth, $thisYear, 'this_month', $slowAsyncRequests);
		foreach ($slowAsyncRequests as $key => $slowRequest) {
			$totalCount = $slowRequest['this_month_fast'] + $slowRequest['this_month_acceptable'] + $slowRequest['this_month_slow'] + $slowRequest['this_month_slower'] + $slowRequest['this_month_very_slow'];
			$weightedCount = $slowRequest['this_month_fast'] + $slowRequest['this_month_acceptable'] * 2 + $slowRequest['this_month_slow'] * 3 + $slowRequest['this_month_slower'] * 4 + $slowRequest['this_month_very_slow'] * 5;
			$averageSlowness = round($weightedCount / $totalCount);
			$slowAsyncRequests[$key]['average'] = $averageSlowness;
			$slowAsyncRequests[$key]['total'] = $totalCount;
		}
		//$slowAsyncRequests = $this->getSlowAsyncRequestStats($lastMonth, $lastMonthYear, 'last_month', $slowAsyncRequests);
		ksort($slowAsyncRequests);
		$interface->assign('slowAsyncRequests', $slowAsyncRequests);

		$this->display('performance_report.tpl', 'Performance Report');
	}

	private function getSlowPageStats(int $month, int $year, $setName, array $stats): array {
		$usage = new SlowPage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->find();
		while ($usage->fetch()) {
			if (isset($stats[$usage->module . '_' . $usage->action])) {
				$stats[$usage->module . '_' . $usage->action][$setName . '_fast'] = ($usage->timesFast == null ? 0 : $usage->timesFast);
				$stats[$usage->module . '_' . $usage->action][$setName . '_acceptable'] = ($usage->timesAcceptable == null ? 0 : $usage->timesAcceptable);
				$stats[$usage->module . '_' . $usage->action][$setName . '_slow'] = ($usage->timesSlow == null ? 0 : $usage->timesSlow);
				$stats[$usage->module . '_' . $usage->action][$setName . '_slower'] = ($usage->timesSlower == null ? 0 : $usage->timesSlower);
				$stats[$usage->module . '_' . $usage->action][$setName . '_very_slow'] = ($usage->timesVerySlow == null ? 0 : $usage->timesVerySlow);
			} else {
				$stats[$usage->module . '_' . $usage->action] = [
					'module' => $usage->module,
					'action' => $usage->action,
					$setName . '_fast' => $usage->timesFast == null ? 0 : $usage->timesFast,
					$setName . '_acceptable' => $usage->timesAcceptable == null ? 0 : $usage->timesAcceptable,
					$setName . '_slow' => $usage->timesSlow == null ? 0 : $usage->timesSlow,
					$setName . '_slower' => $usage->timesSlower == null ? 0 : $usage->timesSlower,
					$setName . '_very_slow' => $usage->timesVerySlow == null ? 0 : $usage->timesVerySlow,
				];
			}
		}
		return $stats;
	}

	private function getSlowAsyncRequestStats(int $month, int $year, $setName, array $stats): array {
		$usage = new SlowAjaxRequest();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->find();
		while ($usage->fetch()) {
			if (isset($stats[$usage->module . '_' . $usage->action . '_' . $usage->method])) {
				$stats[$usage->module . '_' . $usage->action . '_' . $usage->method][$setName] = $usage->timesSlow;
			} else {
				$stats[$usage->module . '_' . $usage->action . '_' . $usage->method] = [
					'module' => $usage->module,
					'action' => $usage->action,
					'method' => $usage->method,
					$setName . '_fast' => $usage->timesFast,
					$setName . '_acceptable' => $usage->timesAcceptable,
					$setName . '_slow' => $usage->timesSlow,
					$setName . '_slower' => $usage->timesSlower,
					$setName . '_very_slow' => $usage->timesVerySlow,
				];
			}
		}
		return $stats;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Performance Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View System Reports');
	}
}