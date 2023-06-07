<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketTrendByQueue.php';

class TicketsTrend extends Admin_Admin {
	function launch() {
		global $interface;
		$title = 'Ticket Trend';

		$dataSeries = [];
		$columnLabels = [];
		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

		$ticketQueue = new TicketQueueFeed();
		$ticketQueue->find();
		while ($ticketQueue->fetch()) {
			$dataSeries[$ticketQueue->name] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		}
		$dataSeries['Total'] = GraphingUtils::getDataSeriesArray(count($dataSeries));

		$ticketTrend = new TicketTrendByQueue();
		$ticketTrend->orderBy('year, month, day');
		$ticketTrend->find();
		while ($ticketTrend->fetch()) {
			$curPeriod = "{$ticketTrend->month}-{$ticketTrend->day}-{$ticketTrend->year}";
			if (!in_array($curPeriod, $columnLabels)) {
				$columnLabels[] = $curPeriod;
			}

			if (!array_key_exists($ticketTrend->queue, $dataSeries)) {
				//echo("Queue not set properly for ticket '" . $tickets->queue . "'");
			} else {
				//Populate all periods with 0's
				if (!array_key_exists($curPeriod, $dataSeries[$ticketTrend->queue]['data'])) {
					foreach ($dataSeries as $queue => $data) {
						$dataSeries[$queue]['data'][$curPeriod] = 0;
					}
				}
				$dataSeries[$ticketTrend->queue]['data'][$curPeriod] = $ticketTrend->count;
				$dataSeries['Total']['data'][$curPeriod] += $ticketTrend->count;
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);

		$interface->assign('graphTitle', $title);

		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketTrends', 'Ticket Trends');
		return $breadcrumbs;
	}

	function canView() {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}