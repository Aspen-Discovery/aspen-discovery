<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class ImportSettings extends Admin_Admin
{
	function launch()
	{
		if (isset($_REQUEST['submit'])){
			//Import account profiles
			//Import admin users
			//Import libraries
			//Import locations
			//Import browse categories
			//Import collection spotlights
			//
		}
		$this->display('importSettings.tpl', 'Import Settings');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}