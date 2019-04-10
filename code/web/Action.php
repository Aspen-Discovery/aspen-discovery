<?php

//require_once 'PEAR.php';

// Abstract Base Class for Actions
abstract class Action
{
    abstract function launch();

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 * @param bool|string $sidebarTemplate      Sets the sidebar template, set to false or empty string for no sidebar
	 */
	function display($mainContentTemplate, $pageTitle, $sidebarTemplate='Search/home-sidebar.tpl') {
		global $interface;
		if (!empty($sidebarTemplate)) $interface->assign('sidebar', $sidebarTemplate);
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle);
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->display('layout.tpl');
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) $_SESSION['showCovers'] = $showCovers;
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
	}
}