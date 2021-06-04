<?php

require_once ROOT_DIR . '/Action.php';

class Help_OverDriveError extends Action{
	function launch() {
		$this->display('Help/overdriveError.tpl', 'Error in OverDrive');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'OverDrive Error');
		return $breadcrumbs;
	}
}