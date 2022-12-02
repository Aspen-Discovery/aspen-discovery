<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Masquerade extends MyAccount {
	// When username & password are passed as POST parameters, index.php will automatically attempt to login the user
	// When the parameters aren't passed and there is no user logged in, MyAccount::__construct will prompt user to login,
	// with a followup action back to this class


	function launch() {
		$result = $this->initiateMasquerade();
		if ($result['success']) {
			header('Location: /MyAccount/Home');
			session_commit();
			exit();
		} else {
			// Display error and embedded Masquerade As Form
			global $interface;
			$interface->assign('error', $result['error']);
			$this->display('masqueradeAs.tpl', 'Masquerade');
		}
	}

	static function initiateMasquerade() {
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI();
		return $api->initMasquerade();
	}

	static function endMasquerade() {
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI();
		return $api->endMasquerade();

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Masquerade as another user');
		return $breadcrumbs;
	}
}