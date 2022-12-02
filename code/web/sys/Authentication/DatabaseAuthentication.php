<?php
require_once 'Authentication.php';

class DatabaseAuthentication implements Authentication {
	public function __construct($additionalInfo) {}

	public function authenticate($validatedViaSSO) {
		if (!isset($_POST['username']) || !isset($_POST['password'])) {
			return new AspenError('Login information cannot be blank.');
		}
		$username = $_POST['username'];
		$password = $_POST['password'];
		return $this->login($username, $password);
	}

	public function validateAccount($username, $password, $parentAccount = null, $validatedViaSSO = false) {
		return $this->login($username, $password);
	}

	private function login($username, $password) {
		if (($username == '') || ($password == '')) {
			$user = new AspenError('Login information cannot be blank.');
		} else {
			if ($username == 'nyt_user') {
				$user = new AspenError('Cannot login as the New York Times User');
			} else {
				$user = new User();
				$user->username = $username;
				if (!$user->find(true)) {
					$user = null;
				} else {
					if ($user->password != $password) {
						$user = new AspenError('Sorry that login information was not recognized, please try again.');
					} else {
						$user->lastLoginValidation = time();
						$user->update();
					}
				}
			}
		}
		return $user;
	}
}