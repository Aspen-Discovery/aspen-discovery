<?php

require_once ROOT_DIR . '/Action.php';

abstract class Admin_Admin extends Action {
	protected $db;

	function __construct() {
		global $configArray;
		$user = UserAccount::getLoggedInUser();

		//If the user isn't logged in, take them to the login page
		if (!$user){
			header("Location: /MyAccount/Login");
			die();
		}

		//Make sure the user has permission to access the page
		$allowableRoles = $this->getAllowableRoles();
		$userCanAccess = false;
		foreach($allowableRoles as $roleId => $roleName){
			if (UserAccount::userHasRole($roleName)){
				$userCanAccess = true;
				break;
			}
		}

		if (!$userCanAccess){
			$this->display('../Admin/noPermission.tpl', 'Access Error');
			exit();
		}
	}

	abstract function getAllowableRoles();
}