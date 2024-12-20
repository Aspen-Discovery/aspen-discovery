<?php
require_once 'Authentication.php';

class DatabaseAuthentication implements Authentication {
	public function __construct($additionalInfo) {}

	public function authenticate($validatedViaSSO, $accountProfile) {
		if (!isset($_POST['username']) || !isset($_POST['password'])) {
			return new AspenError('Login information cannot be blank authenticating user with Database Authentication.');
		}
		$username = $_POST['username'];
		$password = $_POST['password'];
		return $this->login($username, $password, $accountProfile);
	}

	public function validateAccount($username, $password, $accountProfile, $parentAccount = null, $validatedViaSSO = false) {
		return $this->login($username, $password, $accountProfile);
	}

	private function login($username, $password, AccountProfile $accountProfile) {
		if (($username == '') || ($password == '')) {
			$user = new AspenError('Login information cannot be blank when logging in user with Database Authentication.');
		} else {
			if ($username == 'nyt_user') {
				$user = new AspenError('Cannot login as the New York Times User');
			} else {
				$user = new User();
				$user->source = $accountProfile->name;
				//Only check username since this is not going to be connected to the ILS so ILS username is not set
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
