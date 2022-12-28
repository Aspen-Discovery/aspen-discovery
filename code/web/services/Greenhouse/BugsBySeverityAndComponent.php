<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Support/TicketComponentFeed.php';
require_once ROOT_DIR . '/sys/Development/ComponentTicketLink.php';

class Greenhouse_BugsBySeverityAndComponent extends Admin_Admin{
	function launch() {
		global $interface;
		$bugsBySeverityAndComponent = [];
		$components = new TicketComponentFeed();
		$components->orderBy('name');
		$components->find();
		while ($components->fetch()) {
			$bugsBySeverityAndComponent[$components->id] = [
				'componentId' => $components->id,
				'component' => $components->name,
				'low' => 0,
				'medium' => 0,
				'high' => 0,
				'critical' => 0,
				'Total' => 0,
			];

			$ticketComponentLink = new ComponentTicketLink();
			$ticketComponentLink->componentId = $components->id;

			$ticket = new Ticket();
			$ticket->whereAdd("status <> 'Closed'");
			$ticket->joinAdd($ticketComponentLink, 'INNER', 'component', 'id', 'ticketId');
			$ticket->queue = 'Bugs';
			$ticket->groupBy('lower(severity)');
			$ticket->selectAdd('');
			$ticket->selectAdd('lower(severity) as severity');
			$ticket->selectAdd('count(*) as numTickets');

			$ticket->find();
			while ($ticket->fetch()) {
				/** @noinspection PhpUndefinedFieldInspection */
				$bugsBySeverityAndComponent[$components->id][$ticket->severity] = $ticket->numTickets;
				/** @noinspection PhpUndefinedFieldInspection */
				$bugsBySeverityAndComponent[$components->id]['Total'] += $ticket->numTickets;
			}
		}

		$interface->assign('ticketsByComponent', $bugsBySeverityAndComponent);

		$this->display('bugsBySeverityAndComponent.tpl', 'Active Bugs By Severity', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketsByComponent', 'Tickets By Component');
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