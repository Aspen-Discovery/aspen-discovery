<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class Greenhouse_PartnerPriorities extends Admin_Admin{

	function launch() {
		global $interface;

		$aspenSite = new AspenSite();
		$aspenSite->siteType = 0;
		$aspenSite->orderBy('name');
		$partnerPriorities = [];
		$aspenSite->find();
		while ($aspenSite->fetch()) {
			$partnerPriorities[$aspenSite->id] = [
				'siteId' => $aspenSite->id,
				'siteName' => $aspenSite->name,
				'Priority1' => '',
				'Priority2' => '',
				'Priority3' => '',
				'lastPriorityClosed' => '',
				'lastPriority1Closed' => 0,
				'lastPriority2Closed' => 0,
				'lastPriority3Closed' => 0,
				'closedPriorityTickets' => 0,
				'previouslyRankedClosed' => 0,
				'closedBugs' => 0,
				'closedDevelopments' => 0,
			];
			//Closed Priority Tickets
			$ticket = new Ticket();
			$ticket->status = 'Closed';
			$ticket->requestingPartner = $aspenSite->id;
			$ticket->whereAdd('partnerPriority > 0');
			$ticket->orderBy('dateClosed DESC');
			$ticket->find();
			$lastPriorityClosed = '';
			$lastPriority1Closed = '';
			$lastPriority2Closed = '';
			$lastPriority3Closed = '';
			$closedPriorityTickets = 0;
			while ($ticket->fetch()) {
				if (empty($lastPriorityClosed)) {
					$lastPriorityClosed = clone $ticket;
				}
				if ($ticket->partnerPriority == 1 && empty($lastPriority1Closed)) {
					$lastPriority1Closed = clone $ticket;
				}
				if ($ticket->partnerPriority == 2 && empty($lastPriority2Closed)) {
					$lastPriority2Closed = clone $ticket;
				}
				if ($ticket->partnerPriority == 3 && empty($lastPriority3Closed)) {
					$lastPriority3Closed = clone $ticket;
				}
				$closedPriorityTickets++;
			}
			$partnerPriorities[$aspenSite->id]['lastPriorityClosed'] = $lastPriorityClosed;
			$partnerPriorities[$aspenSite->id]['lastPriority1Closed'] = $lastPriority1Closed;
			$partnerPriorities[$aspenSite->id]['lastPriority2Closed'] = $lastPriority2Closed;
			$partnerPriorities[$aspenSite->id]['lastPriority3Closed'] = $lastPriority3Closed;
			$partnerPriorities[$aspenSite->id]['closedPriorityTickets'] = $closedPriorityTickets;

			//Priorities for the library.
			$ticket = new Ticket();
			$ticket->whereAdd("status <> 'Closed'");
			$ticket->requestingPartner = $aspenSite->id;
			$ticket->whereAdd('partnerPriority > 0');
			$ticket->orderBy('dateCreated DESC');
			$ticket->find();
			$priority1Ticket = null;
			$priority2Ticket = null;
			$priority3Ticket = null;
			while ($ticket->fetch()) {
				if ($ticket->partnerPriority == 1) {
					$priority1Ticket = clone $ticket;
				} elseif ($ticket->partnerPriority == 2) {
					$priority2Ticket = clone $ticket;
				} elseif ($ticket->partnerPriority == 3) {
					$priority3Ticket = clone $ticket;
				}
			}
			$partnerPriorities[$aspenSite->id]['priority1Ticket'] = $priority1Ticket;
			$partnerPriorities[$aspenSite->id]['priority2Ticket'] = $priority2Ticket;
			$partnerPriorities[$aspenSite->id]['priority3Ticket'] = $priority3Ticket;

//			//Closed Previously Ranked Tickets
//			$ticket = new Ticket();
//			$ticket->status = 'Closed';
//			$ticket->requestingPartner = $aspenSite->id;
//			$ticket->partnerPriority = -1;
//			$ticket->whereAdd('partnerPriorityChangeDate-dateCreated > 480000');
//			$ticket->orderBy('dateClosed DESC');
//			$partnerPriorities[$aspenSite->id]['previouslyRankedClosed'] = $ticket->count();

			//Total closed tickets
			$ticket = new Ticket();
			$ticket->status = 'Closed';
			$ticket->queue = 'Bugs';
			$ticket->requestingPartner = $aspenSite->id;
			$ticket->orderBy('dateClosed DESC');
			$partnerPriorities[$aspenSite->id]['closedBugs'] = $ticket->count();

			$ticket = new Ticket();
			$ticket->status = 'Closed';
			$ticket->queue = 'Development';
			$ticket->requestingPartner = $aspenSite->id;
			$ticket->orderBy('dateClosed DESC');
			$partnerPriorities[$aspenSite->id]['closedDevelopments'] = $ticket->count();
		}

		$interface->assign('partnerPriorities', $partnerPriorities);

		$this->display('partnerPriorities.tpl', 'Partner Priorities', 'Development/development-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/PartnerPriorities', 'Partner Priorities');
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