<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class EmailPin extends Action{
	protected $catalog;
	
	function __construct()
	{
	}

	function launch($msg = null)
	{
		global $interface;

		if (isset($_REQUEST['submit'])){
			$this->catalog = CatalogFactory::getCatalogConnectionInstance();
			$driver = $this->catalog->driver;
			if ($this->catalog->checkFunction('emailPin')){
				$barcode = strip_tags($_REQUEST['barcode']);
				$emailResult = $driver->emailPin($barcode);
			}else{
				$emailResult = array(
					'error' => 'This functionality is not available in the ILS.',
				);
			}
			$interface->assign('emailResult', $emailResult);
			$this->display('emailPinResults.tpl', 'Email Pin');
		}else{
			$this->display('emailPin.tpl', 'Email Pin');
		}
	}
}
