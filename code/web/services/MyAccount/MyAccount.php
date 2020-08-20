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

		if ($this->requireLogin && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$myAccountAction = new MyAccount_Login();
			$myAccountAction->launch();
			exit();
		}

		// Setup Search Engine Connection
		$this->db = new GroupedWorksSolrConnector($configArray['Index']['url']);

		// Hide Covers when the user has set that setting on an Account Page
		$this->setShowCovers();
	}

	/**
	 * @param string $mainContentTemplate Name of the SMARTY template file for the main content of the Account Page
	 * @param string $pageTitle What to display is the html title tag, gets ran through the translator
	 * @param string|null $sidebar Sets the sidebar on the page to be displayed
	 * @param bool $translateTitle
	 */
	function display($mainContentTemplate, $pageTitle='My Account', $sidebar='Search/home-sidebar.tpl', $translateTitle = true) {
		global $interface;
		$interface->setPageTitle($pageTitle);
		parent::display($mainContentTemplate, $pageTitle, $sidebar, $translateTitle);
	}
}
