<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

/**
 * Class RequestPinReset
 *
 * This is the same as MyAccount_EmailResetPin.
 * Both exist for historical compatibility
 */
class RequestPinReset extends Action{
	function launch($msg = null)
	{
		global $interface;

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['submit'])){

			$result = $catalog->processEmailResetPinForm();

			$interface->assign('result', $result);
			$template = $catalog->getEmailResetPinResultsTemplate();
		}else{
			$template = $catalog->getEmailResetPinTemplate();
		}

		$this->display($template, 'Pin Reset', null);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}
