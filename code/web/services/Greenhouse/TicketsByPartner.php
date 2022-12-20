<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class Greenhouse_TicketsByPartner extends Admin_Admin{

	function launch() {
		global $interface;

		$aspenSite = new AspenSite();
		$aspenSite->siteType = 0;
		$aspenSite->orderBy('name');
		$ticketsByPartner = [];
		$aspenSite->find();
		while ($aspenSite->fetch()) {
			$ticketsByPartner[$aspenSite->id] = [
				'siteId' => $aspenSite->id,
				'siteName' => $aspenSite->name,
				'Implementation' => 0,
				'Support' => 0,
				'Bugs' => 0,
				'Development' => 0,
				'Total' => 0,
			];
			$ticket = new Ticket();
			$ticket->requestingPartner = $aspenSite->id;
			$ticket->groupBy('queue');
			$ticket->whereAdd("status <> 'Closed'");
			$ticket->selectAdd('');
			$ticket->selectAdd('queue');
			$ticket->selectAdd('count(*) as numTickets');
			$ticket->find();
			while ($ticket->fetch()) {
				/** @noinspection PhpUndefinedFieldInspection */
				$ticketsByPartner[$aspenSite->id][$ticket->queue] = $ticket->numTickets;
				/** @noinspection PhpUndefinedFieldInspection */
				$ticketsByPartner[$aspenSite->id]['Total'] += $ticket->numTickets;
			}
		}

		$interface->assign('ticketsByPartner', $ticketsByPartner);

		$this->display('ticketsByPartner.tpl', 'Active Tickets By Partner', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketsByPartner', 'Tickets By Partner');
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