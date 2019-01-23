<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 8/16/2016
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class EmailResetPin extends Action{
	protected $catalog;

	function __construct()
	{
	}

	function launch($msg = null)
	{
		global $interface;

		if (isset($_REQUEST['submit'])){

			$this->catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
			$driver = $this->catalog->driver;
			if ($this->catalog->checkFunction('emailResetPin')){
				$barcode = strip_tags($_REQUEST['barcode']);
				$emailResult = $driver->emailResetPin($barcode);
			}else{
				$emailResult = array(
					'error' => 'This functionality is not available in the ILS.',
				);
			}
			$interface->assign('emailResult', $emailResult);
			$this->display('emailResetPinResults.tpl', 'Email to Reset Pin');
		}else{
			$this->display('emailResetPin.tpl', 'Email to Reset Pin');
		}
	}
}
