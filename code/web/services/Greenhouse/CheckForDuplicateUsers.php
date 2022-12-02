<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class CheckForDuplicateUsers extends Admin_Admin {
	function launch() {
		global $interface;

		$duplicateUsers = $this->getDuplicateUsers();
		$interface->assign('duplicateUsers', $duplicateUsers);

		$this->display('checkForDuplicateUsers.tpl', 'Users With Duplicate Barcodes', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Check For Duplicate Users');

		return $breadcrumbs;
	}

	function getDuplicateUsers() {
		//Get a list of all barcodes that have more than one user for them
		global $aspen_db;
		$result = $aspen_db->query('select cat_username, count(*) as numUsers from user where cat_username != "" group by cat_username having numUsers > 1;');
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
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