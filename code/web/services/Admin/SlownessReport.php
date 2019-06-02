<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/SlowPage.php';
require_once ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php';

class Admin_SlownessReport extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$thisMonth = date('n');
		$thisYear = date('Y');
		$lastMonth = $thisMonth - 1;
		$lastMonthYear = $thisYear;
		if ($lastMonth == 0){
			$lastMonth = 12;
			$lastMonthYear--;
		}

		$slowPages = [];
		$slowPages = $this->getSlowPageStats($thisMonth, $thisYear, 'this_month', $slowPages);
		$slowPages = $this->getSlowPageStats($lastMonth, $lastMonthYear, 'last_month', $slowPages);
		ksort($slowPages);
		$interface->assign('slowPages', $slowPages);

		$slowAsyncRequests = [];
		$slowAsyncRequests = $this->getSlowAsyncRequestStats($thisMonth, $thisYear, 'this_month', $slowAsyncRequests);
		$slowAsyncRequests = $this->getSlowAsyncRequestStats($lastMonth, $lastMonthYear, 'last_month', $slowAsyncRequests);
		ksort($slowAsyncRequests);
		$interface->assign('slowAsyncRequests', $slowAsyncRequests);

		$this->display('slowness_report.tpl', 'Slowness Report');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}

	private function getSlowPageStats(int $month, int $year, $setName, array $stats) : array
	{
		$usage = new SlowPage();
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->find();
		while($usage->fetch()){
			if (isset($stats[$usage->module . '_' . $usage->action])){
				$stats[$usage->module . '_' . $usage->action][$setName] = $usage->timesSlow;
			} else{
				$stats[$usage->module . '_' . $usage->action]=[
					'module' => $usage->module,
					'action' => $usage->action,
					$setName => $usage->timesSlow,
				];
			}
		}
		return $stats;
	}

	private function getSlowAsyncRequestStats(int $month, int $year, $setName, array $stats) : array
	{
		$usage = new SlowAjaxRequest();
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->find();
		while($usage->fetch()){
			if (isset($stats[$usage->module . '_' . $usage->action . '_' . $usage->method])){
				$stats[$usage->module . '_' . $usage->action . '_' . $usage->method][$setName] = $usage->timesSlow;
			} else{
				$stats[$usage->module . '_' . $usage->action . '_' . $usage->method]=[
					'module' => $usage->module,
					'action' => $usage->action,
					'method' => $usage->method,
					$setName => $usage->timesSlow,
				];
			}
		}
		return $stats;
	}
}