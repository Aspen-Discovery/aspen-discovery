<?php

require_once ROOT_DIR . '/Action.php';

class MyAccount_Logout extends Action {

	public function launch() {
		global $configArray;

		UserAccount::logout();

		header('Location: /');
	}
}
