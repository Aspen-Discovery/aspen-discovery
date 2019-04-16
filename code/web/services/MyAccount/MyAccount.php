<?php

require_once ROOT_DIR . '/Action.php';

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';

abstract class MyAccount extends Action
{
	/** @var  GroupedWorksSolrConnector */
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
		$this->db = new GroupedWorksSolrConnector($configArray['Index']['url']);

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
