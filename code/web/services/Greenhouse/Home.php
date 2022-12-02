<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_Home extends Admin_Admin {
	function launch() {
		global $interface;

		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
		} else {
			$interface->assign('error', 'You must be logged in to access the Administration Interface');
		}

		$this->display('home.tpl', 'Greenhouse Home', '');
	}


	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Greenhouse Home');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'home';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}
}