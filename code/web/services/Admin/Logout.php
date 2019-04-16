<?php

require_once ROOT_DIR . '/Action.php';

class Logout extends Action {

	public function launch() {
		global $configArray;

		if(!isset($_SESSION)){
			session_start();
		}

		unset($_SESSION['admininfo']);

		header('Location: ' . $configArray['Site']['path'] . '/Admin/Home');
	}
}
