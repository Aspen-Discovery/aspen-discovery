<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_HelpManual extends Action {
	function launch() {
		global $interface;
		global $activeLanguage;

		//Get a list of all available release notes
		$helpManualPath = ROOT_DIR . '/manual';
		if (file_exists($helpManualPath . '_' . $activeLanguage->code)) {
			$helpManualPath = $helpManualPath . '_' . $activeLanguage->code;
		}
		if (isset($_REQUEST['page'])) {
			$page = $_REQUEST['page'];
		} else {
			$page = 'table_of_contents';
		}

		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', $this->getActiveAdminSection());
			$interface->assign('activeMenuOption', 'admin');
			$sidebar = 'Admin/admin-sidebar.tpl';
		} else {
			$sidebar = '';
		}
		if (file_exists($helpManualPath . '/' . $page . '.MD')) {
			$parsedown = AspenParsedown::instance();
			$formattedPage = $parsedown->parse(file_get_contents($helpManualPath . '/' . $page . '.MD'));
			$interface->assign('formattedPage', $formattedPage);
			$this->display('https://help.aspendiscovery.org', 'Help Center', $sidebar);
		} else {
			$this->display('unknownPage.tpl', 'Help Manual', $sidebar);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Aspen Discovery Support');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/HelpManual?page=table_of_contents', 'Table of Contents');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'support';
	}

	function canView(): bool {
		return true;
	}
}