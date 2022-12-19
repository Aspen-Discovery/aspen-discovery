<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class Greenhouse_PartnerTicketDashboard extends Admin_Admin{
	function launch() {
		global $interface;

		$aspenSite = new AspenSite();
		$aspenSite->siteType = 0;
		$aspenSite->orderBy('name');
		$allSites = [];
		$aspenSite->find();
		$selectedSite = '';
		while ($aspenSite->fetch()) {
			$allSites[$aspenSite->id] = $aspenSite->name;
			if ($selectedSite == '') {
				$selectedSite = $aspenSite->id;
			}
		}
		$interface->assign('allSites', $allSites);

		if (!empty($_REQUEST['site'])) {
			$selectedSite = $_REQUEST['site'];
		}
		$interface->assign('selectedSite', $selectedSite);

		$ticket = new Ticket();
		$ticket->status = 'Closed';
		$ticket->requestingPartner = $selectedSite;
		$ticket->whereAdd('partnerPriority > 0');
		$ticket->orderBy('dateClosed DESC');
		$ticket->find();
		$lastPriorityClosed = '';
		$lastPriority1Closed = '';
		$lastPriority2Closed = '';
		$lastPriority3Closed = '';
		$interface->assign('totalTicketsClosed', $ticket->count());
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
			if (!empty($lastPriority1Closed) && !empty($lastPriority2Closed) && !empty($lastPriority3Closed)) {
				break;
			}
		}
		$interface->assign('lastPriorityClosed', $lastPriorityClosed);
		$interface->assign('lastPriority1Closed', $lastPriority1Closed);
		$interface->assign('lastPriority2Closed', $lastPriority2Closed);
		$interface->assign('lastPriority3Closed', $lastPriority3Closed);


		$this->display('partnerTicketDashboard.tpl', 'Partner Ticket Dashboard', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/PartnerTicketDashboard', 'Partner Ticket Dashboard');
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