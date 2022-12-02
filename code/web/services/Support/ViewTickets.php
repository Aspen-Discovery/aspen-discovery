<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class ViewTickets extends Admin_Admin {

	function launch() {
		//Get a list of tickets from Request Tracker
		require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
		$supportConnections = new RequestTrackerConnection();
		global $interface;
		if ($supportConnections->find(true)) {
			$activeTickets = $supportConnections->getActiveTickets();
			$interface->assign('activeTickets', $activeTickets);
			$this->display('viewTickets.tpl', 'View Tickets');
		} else {
			//User shouldn't get here
			$module = 'Error';
			$action = 'Handle404';
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Support');
		$breadcrumbs[] = new Breadcrumb('/Support/ViewTickets', 'View Tickets');
		return $breadcrumbs;
	}

	function canView() {
		return UserAccount::userHasPermission('View Active Tickets');
	}

	function getActiveAdminSection(): string {
		return 'support';
	}
}