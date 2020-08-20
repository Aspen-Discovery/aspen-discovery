<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/Parsedown.php';

class Admin_HelpManual extends Admin_Admin
{
	function launch()
	{
		global $interface;

		//Get a list of all available release notes
		$helpManualPath = ROOT_DIR . '/manual';
		$page = $_REQUEST['page'];

		if (file_exists($helpManualPath . '/'. $page . '.MD')){
			$parsedown = Parsedown::instance();
			$formattedPage = $parsedown->parse(file_get_contents($helpManualPath . '/'. $page . '.MD'));
			$interface->assign('formattedPage', $formattedPage);
			$this->display('manual.tpl', 'Help Manual');
		}else{
			$this->display('unknownPage.tpl', 'Help Manual');
		}
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_help', 'Aspen Discovery Help');
		$breadcrumbs[] = new Breadcrumb('/Admin/HelpManual?page=table_of_contents', 'Table of Contents');
		return $breadcrumbs;
	}
}