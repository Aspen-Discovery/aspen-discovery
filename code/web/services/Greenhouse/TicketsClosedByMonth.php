<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
class Greenhouse_TicketsClosedByMonth extends Admin_Admin
{
	//TODO: Only show tickets closed since Feb/March 2022 since we aren't loading all closed tickets.
	function launch()
	{
		global $interface;
		$title = 'Tickets Closed By Month';

		$dataSeries = [];
		$columnLabels = [];
		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';
		require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
		require_once ROOT_DIR . '/sys/Support/Ticket.php';
		$ticketQueueFeeds = new TicketQueueFeed();
		$ticketQueueFeeds->find();
		while ($ticketQueueFeeds->fetch()){
			$dataSeries[$ticketQueueFeeds->name] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		}
		$dataSeries['Total']  = GraphingUtils::getDataSeriesArray(count($dataSeries));

		$tickets = new Ticket();
		$tickets->groupBy('year, month, queue');
		$tickets->selectAdd();
		$tickets->selectAdd('YEAR(FROM_UNIXTIME(dateClosed)) as year');
		$tickets->selectAdd('MONTH(FROM_UNIXTIME(dateClosed)) as month');
		$tickets->selectAdd('queue');
		$tickets->selectAdd('COUNT(*) as numTickets');
		$tickets->whereAdd("status = 'Closed'");
		$tickets->orderBy('year, month');
		$tickets->find();
		while ($tickets->fetch()){
			/** @noinspection PhpUndefinedFieldInspection */
			$curPeriod = "{$tickets->month}-{$tickets->year}";
			if (!in_array($curPeriod, $columnLabels)) {
				$columnLabels[] = $curPeriod;
			}

			if (!array_key_exists($tickets->queue, $dataSeries)){
				//echo("Queue not set properly for ticket '" . $tickets->queue . "'");
			}else{
				//Populate all periods with 0's
				if (!array_key_exists($curPeriod, $dataSeries[$tickets->queue]['data'])){
					foreach ($dataSeries as $queue => $data){
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

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketsClosedByMonth', 'Tickets Closed By Month');
		return $breadcrumbs;
	}

	function canView()
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string
	{
		return 'greenhouse';
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true)
	{
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}