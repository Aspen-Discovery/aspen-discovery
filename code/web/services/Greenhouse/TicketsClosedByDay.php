<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';

class Greenhouse_TicketsClosedByDay extends Admin_Admin {

	function launch() {
		global $interface;
		$title = 'Tickets Closed By Day (last 30 days)';

		$dataSeries = [];
		$columnLabels = [];
		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';
		require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
		require_once ROOT_DIR . '/sys/Support/Ticket.php';
		$ticketQueueFeeds = new TicketQueueFeed();
		$ticketQueueFeeds->find();
		while ($ticketQueueFeeds->fetch()) {
			$dataSeries[$ticketQueueFeeds->name] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		}
		$dataSeries['Total'] = GraphingUtils::getDataSeriesArray(count($dataSeries));

		$lastMonth = strtotime("now - 30days");

		$tickets = new Ticket();
		$tickets->groupBy('year, month, day, queue');
		$tickets->selectAdd();
		$tickets->selectAdd('YEAR(FROM_UNIXTIME(dateClosed)) as year');
		$tickets->selectAdd('MONTH(FROM_UNIXTIME(dateClosed)) as month');
		$tickets->selectAdd('DAY(FROM_UNIXTIME(dateClosed)) as day');
		$tickets->selectAdd('queue');
		$tickets->selectAdd('COUNT(*) as numTickets');
		$tickets->whereAdd("dateClosed >= $lastMonth");
		$tickets->whereAdd("status = 'Closed'");
		$tickets->orderBy('year, month, day');
		$tickets->find();
		while ($tickets->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$curPeriod = "{$tickets->month}-{$tickets->day}-{$tickets->year}";
			if (!in_array($curPeriod, $columnLabels)) {
				$columnLabels[] = $curPeriod;
			}

			if (!array_key_exists($tickets->queue, $dataSeries)) {
				//echo("Queue not set properly for ticket '" . $tickets->queue . "'");
			} else {
				//Populate all periods with 0's
				if (!array_key_exists($curPeriod, $dataSeries[$tickets->queue]['data'])) {
					foreach ($dataSeries as $queue => $data) {
						$dataSeries[$queue]['data'][$curPeriod] = 0;
					}
				}
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries[$tickets->queue]['data'][$curPeriod] = $tickets->numTickets;
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Total']['data'][$curPeriod] += $tickets->numTickets;
			}

		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);

		$interface->assign('graphTitle', $title);


		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketsClosedByDay', 'Tickets Closed By Day');
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