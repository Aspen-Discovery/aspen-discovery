<?php

require_once ROOT_DIR . '/Action.php';

// Quick Class to launch a Pop-up for arlington
class PayFinesArlington extends Action {

	function launch()
	{
		global $interface;

		$interface->display('MyAccount/PayFines.tpl');
	}

}