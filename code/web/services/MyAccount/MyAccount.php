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

require_once ROOT_DIR . '/Action.php';

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';

abstract class MyAccount extends Action
{
	/** @var  SearchObject_Solr|SearchObject_Base */
	protected $db;
	/** @var  CatalogConnection $catalog */
	protected $catalog;
	protected $requireLogin = true;

	function __construct() {
		global $interface;
		global $configArray;

		$interface->assign('page_body_style', 'sidebar_left');

		if ($this->requireLogin && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$myAccountAction = new MyAccount_Login();
			$myAccountAction->launch();
			exit();
		}

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$this->db = new $class($configArray['Index']['url']);

		// Connect to Database
		//$user = UserAccount::getLoggedInUser();
		//$this->catalog = CatalogFactory::getCatalogConnectionInstance($user ? $user->source : null);
			// When loading MyList.php and the list is public, user does not need to be logged in to see list

		// Hide Covers when the user has set that setting on an Account Page
		$this->setShowCovers();
	}

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Account Page
	 * @param string $pageTitle            What to display is the html title tag, gets ran through the translator
	 * @param string|null $sidebar         Sets the sidebar on the page to be displayed
	 */
	function display($mainContentTemplate, $pageTitle='My Account', $sidebar='Search/home-sidebar.tpl') {
		parent::display($mainContentTemplate, translate($pageTitle), $sidebar);
	}
}
