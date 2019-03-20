<?php

require_once ROOT_DIR . '/Action.php';

class Help_OverDriveError extends Action{
	function launch() {
		global $interface;

		$interface->display('Help/overdriveError.tpl');
	}
}