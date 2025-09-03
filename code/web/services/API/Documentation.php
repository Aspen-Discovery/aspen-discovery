<?php

global $configArray;
require_once ROOT_DIR . '/Action.php';

class API_Documentation extends Action {
	function launch() {
		global $interface;

		$apiFile = "/openapi/aspen_openapi.json";
		
		$interface->assign('showBreadcrumbs', true);
		$interface->assign('showContentAsFullWidth', true);
		$interface->assign('apiFile', $apiFile);

		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', $this->getActiveAdminSection());
			$interface->assign('activeMenuOption', 'admin');
			$sidebar = 'Admin/admin-sidebar.tpl';
		} else {
			$sidebar = '';
		}
		$this->display('apiDocumentation.tpl', 'Aspen API Documentation', $sidebar);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Aspen Discovery Support');
		}
		$breadcrumbs[] = new Breadcrumb('', 'API Documentation');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'support';
	}

	function canView(): bool {
		return true;
	}
}