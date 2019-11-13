<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Websites_Dashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;

		echo "Implement ME!";
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'cataloging');
	}
}