<?php

require_once ROOT_DIR . '/Action.php';

class MyAccount_Logout extends Action {

	public function launch() {
		UserAccount::logout();
		session_write_close();

		header('Location: /');
		die();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
