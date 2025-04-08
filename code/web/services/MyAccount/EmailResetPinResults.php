<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class MyAccount_EmailResetPinResults extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;

		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if(isset($_REQUEST['success']) && $_REQUEST['success']) {
			$result['success'] = true;
			if(!empty($_REQUEST['message'])) {
				$result['message'] = $_REQUEST['message'];
			}
		} else {
			$result['success'] = false;
			$result['error'] = '';
			if(isset($_REQUEST['error']) && $_REQUEST['error']) {
				$result['error'] = $_REQUEST['error'];
			}
		}
		$interface->assign('result', $result);
		$this->display($catalog->getEmailResetPinResultsTemplate(), 'Email to Reset Pin', '');
	}

	function getBreadcrumbs(): array {
		return [];
	}
}