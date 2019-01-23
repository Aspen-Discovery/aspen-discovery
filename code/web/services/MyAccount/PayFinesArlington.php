<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/22/2016
 *
 */
require_once ROOT_DIR . '/Action.php';

// Quick Class to launch a Pop-up for arlington
class PayFinesArlington extends Action {

	function launch()
	{
		global $interface;

		$interface->display('MyAccount/PayFines.tpl');
	}

}