<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyEvents extends MyAccount {
	public function launch() {
		global $interface;

		global $offlineMode;
		if (!$offlineMode) {
			$interface->assign('offline', false);
		}
		$user = UserAccount::getLoggedInUser();

		// Get My Transactions
		if ($user) {
			if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
				$interface->assign('page', $_REQUEST['page']);
			} else {
				$interface->assign('page', 1);
			}
			if (isset($_REQUEST['eventsFilter'])) {
				$interface->assign('eventsFilter', strip_tags($_REQUEST['eventsFilter']));
			} else {
				$interface->assign('eventsFilter', 'upcoming');
			}
		}

		$this->display('myEvents.tpl', 'Your Events');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Events');
		return $breadcrumbs;
	}
}