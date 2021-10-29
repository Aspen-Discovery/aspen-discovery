<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_HelpManual extends Action
{
	function launch()
	{
		global $interface;

		//Get a list of all available release notes
		
		/* Kware Fix */
		$language = strip_tags((isset($_SESSION['language'])) ? $_SESSION['language'] : 'en');		
		$langManualPath = ROOT_DIR . '/manual_'.$language;
		$defaultManualPath = ROOT_DIR . '/manual';		
		$helpManualPath = file_exists($langManualPath) ? $langManualPath : $defaultManualPath;
		/* End of Kware Fix */
		
		if (isset($_REQUEST['page'])){
			$page = $_REQUEST['page'];
		}else{
			$page = 'table_of_contents';
		}

		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', $this->getActiveAdminSection());
			$interface->assign('activeMenuOption', 'admin');
			$sidebar = 'Admin/admin-sidebar.tpl';
		}else{
			$sidebar = '';
		}
		if (file_exists($helpManualPath . '/'. $page . '.MD')){
			$parsedown = AspenParsedown::instance();
			$formattedPage = $parsedown->parse(file_get_contents($helpManualPath . '/'. $page . '.MD'));
			$interface->assign('formattedPage', $formattedPage);
			$this->display('manual.tpl', 'Help Manual', $sidebar);
		}else{
			$this->display('unknownPage.tpl', 'Help Manual', $sidebar);
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_help', 'Aspen Discovery Help');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/HelpManual?page=table_of_contents', 'Table of Contents');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'aspen_help';
	}

	function canView() : bool
	{
		return true;
	}
}