<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/Parsedown.php';

class Admin_HelpManual extends Action
{
	function launch()
	{
		global $interface;

		//Get a list of all available release notes
		$helpManualPath = ROOT_DIR . '/manual';
		if (isset($_REQUEST['page'])){
			$page = $_REQUEST['page'];
		}else{
			$page = 'table_of_contents';
		}

		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$sidebar = 'Search/home-sidebar.tpl';
		}else{
			$sidebar = '';
		}
		if (file_exists($helpManualPath . '/'. $page . '.MD')){
			$parsedown = Parsedown::instance();
			$formattedPage = $parsedown->parse(file_get_contents($helpManualPath . '/'. $page . '.MD'));
			$interface->assign('formattedPage', $formattedPage);
			$this->display('manual.tpl', 'Help Manual', $sidebar);
		}else{
			$this->display('unknownPage.tpl', 'Help Manual', $sidebar);
		}
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_help', 'Aspen Discovery Help');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/HelpManual?page=table_of_contents', 'Table of Contents');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'aspen_help';
	}

	function canView()
	{
		return true;
	}
}