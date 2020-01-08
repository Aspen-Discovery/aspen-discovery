<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class MyAccount_EmailResetPin extends Action{
	function launch($msg = null)
	{
		global $interface;
		global $library;

		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['submit'])){
			$emailResult = $catalog->processEmailResetPinForm();

			$interface->assign('emailResult', $emailResult);
			$this->display('emailResetPinResults.tpl', 'Email to Reset Pin');
		}else{
			if (isset($_REQUEST['email'])){
				$interface->assign('email', $_REQUEST['email']);
			}
			if (isset($_REQUEST['barcode'])){
				$interface->assign('barcode', $_REQUEST['barcode']);
			}
			if (isset($_REQUEST['username'])){
				$interface->assign('username', $_REQUEST['username']);
			}
			if (isset($_REQUEST['resendEmail'])){
				$interface->assign('resendEmail', $_REQUEST['resendEmail']);
			}

			$this->display($catalog->getEmailResetPinTemplate(), 'Reset ' . $interface->getVariable('passwordLabel'));
		}
	}
}
