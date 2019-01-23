<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'PEAR.php';

// Abstract Base Class for Actions
abstract class Action extends PEAR
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