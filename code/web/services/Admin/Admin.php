<?php

require_once ROOT_DIR . '/Action.php';

abstract class Admin_Admin extends Action {
	protected $db;

	function __construct() {
		parent::__construct(false);

		$user = UserAccount::getLoggedInUser();

		//If the user isn't logged in, take them to the login page
		if (!$user){
			header("Location: /MyAccount/Login");
			die();
		}

		//Make sure the user has permission to access the page
		$userCanAccess = $this->canView();

		if (!$userCanAccess){
			$this->display('../Admin/noPermission.tpl', 'Access Error');
			exit();
		}

		global $interface;
		$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
		$interface->assign('adminActions', $adminActions);
		$interface->assign('activeAdminSection', $this->getActiveAdminSection());
		$interface->assign('activeMenuOption', 'admin');
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Admin/admin-sidebar.tpl', $translateTitle = true)
	{
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

	abstract function canView();

	abstract function getActiveAdminSection();
}