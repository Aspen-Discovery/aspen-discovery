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

abstract class Admin_Admin extends Action {
	protected $db;

	function __construct() {
		global $configArray;
		$user = UserAccount::getLoggedInUser();

		//If the user isn't logged in, take them to the login page
		if (!$user){
			header("Location: {$configArray['Site']['path']}/MyAccount/Login");
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