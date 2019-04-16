<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class RequestPinReset extends Action{
	protected $catalog;

	function launch($msg = null)
	{
		global $interface;

		if (isset($_REQUEST['submit'])){
			$this->catalog = CatalogFactory::getCatalogConnectionInstance();
			$driver = $this->catalog->driver;
			if ($this->catalog->checkFunction('requestPinReset')){
				$barcode = strip_tags($_REQUEST['barcode']);
				$requestPinResetResult = $this->catalog->requestPinReset($barcode);
			}else{
				$requestPinResetResult = array(
					'error' => 'This functionality is not available in the ILS.',
				);
			}
			$interface->assign('requestPinResetResult', $requestPinResetResult);
			$template = 'requestPinResetResults.tpl';
		}else{
			$template = ('requestPinReset.tpl');
		}

		$this->display($template, 'Pin Reset');
	}
}
