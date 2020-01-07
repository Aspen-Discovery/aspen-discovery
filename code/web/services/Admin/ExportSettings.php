<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

/** @noinspection PhpUnused */
class ExportSettings extends Admin_Admin
{
	function launch()
	{
		if (isset($_REQUEST['submit'])){
			//Export account profiles
			//Export admin users
			//Export themes
				//Export images that have been uploaded
			//Export configuration templates
			//Export libraries
			//Export locations
			//Export Indexing Profiles
				//Export Scopes
			//Export Side loads
			//Export browse categories
			//Export widgets
			//Export sideload data
		}
		$this->display('exportSettings.tpl', 'Export Settings');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}