<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class SubmitTicketResults extends Admin_Admin {
	function launch($msg = null) {
		global $interface;
		$error = false;
		if(!isset($_REQUEST['success']) || !$_REQUEST['success']) {
			$error = true;
		}

		$interface->assign('error', $error);
		$this->display('submitTicketSuccess.tpl', 'Submit Ticket');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Aspen Discovery Support');
		$breadcrumbs[] = new Breadcrumb('/Admin/SubmitTicket', 'Submit Support Ticket');
		$breadcrumbs[] = new Breadcrumb('', 'Results');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'support';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Submit Ticket');
	}
}