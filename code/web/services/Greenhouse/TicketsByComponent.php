<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Support/TicketComponentFeed.php';
require_once ROOT_DIR . '/sys/Development/ComponentTicketLink.php';

class Greenhouse_TicketsByComponent extends Admin_Admin{
	function launch() {
		global $interface;
		$ticketsByComponent = [];
		$components = new TicketComponentFeed();
		$components->orderBy('name');
		$components->find();
		while ($components->fetch()) {
			$ticketsByComponent[$components->id] = [
				'componentId' => $components->id,
				'component' => $components->name,
				'Implementation' => 0,
				'Support' => 0,
				'Bugs' => 0,
				'Development' => 0,
				'PriorityTickets' => 0,
				'PriorityScore' => 0,
				'Total' => 0,
			];

			$ticketComponentLink = new ComponentTicketLink();
			$ticketComponentLink->componentId = $components->id;

			$ticket = new Ticket();
			$ticket->whereAdd("status <> 'Closed'");
			$ticket->joinAdd($ticketComponentLink, 'INNER', 'component', 'id', 'ticketId');
			$ticket->groupBy('queue');
			$ticket->selectAdd('');
			$ticket->selectAdd('queue');
			$ticket->selectAdd('count(*) as numTickets');

			$ticket->find();
			while ($ticket->fetch()) {
				/** @noinspection PhpUndefinedFieldInspection */
				$ticketsByComponent[$components->id][$ticket->queue] = $ticket->numTickets;
				/** @noinspection PhpUndefinedFieldInspection */
				$ticketsByComponent[$components->id]['Total'] += $ticket->numTickets;
			}

			//Also get the number of priority tickets
			$ticket = new Ticket();
			$ticket->whereAdd("status <> 'Closed'");
			$ticket->whereAdd('partnerPriority > 0');
			$ticket->joinAdd($ticketComponentLink, 'INNER', 'component', 'id', 'ticketId');
			$ticket->find();
			while ($ticket->fetch()) {
				$priority = $ticket->partnerPriority;
				$ticketsByComponent[$components->id]['PriorityTickets']++;
				$ticketsByComponent[$components->id]['PriorityScore'] += (4 - $priority);
			}
		}

		$interface->assign('ticketsByComponent', $ticketsByComponent);

		$this->display('ticketsByComponent.tpl', 'Active Tickets By Component', 'Development/development-sidebar.tpl');
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