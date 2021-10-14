<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_Home extends Admin_Admin
{
	function launch()
	{
		global $interface;

		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			$interface->assign('adminSections', $user->getAdminActions());
		}else{
			$interface->assign('error', 'You must be logged in to access the Administration Interface');
		}

		$this->display('home.tpl', 'Aspen Discovery Administration', '');
	}


	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Administration Home');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'home';
	}

	function canView() : bool
	{
		return !empty(UserAccount::getActiveRoles());
	}
}