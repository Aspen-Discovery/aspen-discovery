<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 10/19/2016
 *
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Masquerade extends MyAccount
{
	// When username & password are passed as POST parameters, index.php will automatically attempt to login the user
	// When the parameters aren't passed and there is no user logged in, MyAccount::__construct will prompt user to login,
	// with a followup action back to this class


	function launch()
	{
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

	static function initiateMasquerade()
	{
		global $library;
		if (!empty($library) && $library->allowMasqueradeMode) {
			if (!empty($_REQUEST['cardNumber'])) {
				//$logger->log("Masquerading as " . $_REQUEST['cardNumber'], PEAR_LOG_ERR);
				$libraryCard = $_REQUEST['cardNumber'];
				global $guidingUser;
				if (empty($guidingUser)) {
					$user = UserAccount::getLoggedInUser();
					if ($user && $user->canMasquerade()) {
						$masqueradedUser = new User();
						//TODO: below, when $masquerade User account is in another ILS and the other ILS has a different $authenticationMethod (ie barcode/pin)
						if ($user->getAccountProfile()->loginConfiguration == 'barcode_pin') {
							$masqueradedUser->cat_username = $libraryCard;
						} else {
							$masqueradedUser->cat_password = $libraryCard;
						}
						if ($masqueradedUser->find(true)) {
							if ($masqueradedUser->id == $user->id) {
								return array(
									'success' => false,
									'error' => 'No need to masquerade as yourself.'
								);
							}
							//$logger->log("Found masqueraded user with card " . $libraryCard, PEAR_LOG_ERR);
						} else {
							//$logger->log("Testing a different login configuration", PEAR_LOG_ERR);
							// Check for another ILS with a different login configuration
							$accountProfile = new AccountProfile();
							$accountProfile->groupBy('loginConfiguration');
							$numConfigurations = $accountProfile->count('loginConfiguration');
							if ($numConfigurations > 1) {
								// Now that we know there is more than loginConfiguration type, check the opposite column
								$masqueradedUser = new User();
								if ($user->getAccountProfile()->loginConfiguration == 'barcode_pin') {
									$masqueradedUser->cat_password = $libraryCard;
								} else {
									$masqueradedUser->cat_username = $libraryCard;
								}
								$masqueradedUser->find(true);
							}

							if ($masqueradedUser->N == 0) {
								// Test for a user that hasn't logged into Pika before
								$masqueradedUser = UserAccount::findNewUser($libraryCard);
								if (!$masqueradedUser) {
									return array(
										'success' => false,
										'error' => 'Invalid User'
									);
								}
							}
						}

						// Now that we have found the masqueraded User, check Masquerade Levels
						if ($masqueradedUser) {
							//Check for errors
							switch ($user->getMasqueradeLevel()) {
							case 'location' :
								if (empty($user->homeLocationId)) {
									return array(
										'success' => false,
										'error'   => 'Could not determine your home library branch.'
									);
								}
								if (empty($masqueradedUser->homeLocationId)) {
									return array(
										'success' => false,
										'error'   => 'Could not determine the patron\'s home library branch.'
									);
								}
								if ($user->homeLocationId != $masqueradedUser->homeLocationId) {
									return array(
										'success' => false,
										'error'   => 'You do not have the same home library branch as the patron.'
									);
								}
								break;
							case 'library' :
								$guidingUserLibrary = $user->getHomeLibrary();
								if (!$guidingUserLibrary) {
									return array(
										'success' => false,
										'error'   => 'Could not determine your home library.'
									);
								}
								$masqueradedUserLibrary = $masqueradedUser->getHomeLibrary();
								if (!$masqueradedUserLibrary) {
									return array(
										'success' => false,
										'error' => 'Could not determine the patron\'s home library.'
									);
								}
								if ($guidingUserLibrary->libraryId != $masqueradedUserLibrary->libraryId) {
									return array(
										'success' => false,
										'error'   => 'You do not have the same home library as the patron.'
									);
								}
								break;
							case 'any' :

							}

							//Setup the guiding user and masqueraded user
							global $guidingUser;
							//$logger->log("Logging in with masqueraded user information", PEAR_LOG_ERR);
							//$logger->log("Guiding User " . (empty($guidingUser) ? 'none' : $guidingUser->id), PEAR_LOG_ERR);
							//$logger->log("User " . (empty($user) ? 'none' : $user->id), PEAR_LOG_ERR);
							$guidingUser = $user;
							//$logger->log("New Guiding User " . (empty($guidingUser) ? 'none' : $guidingUser->id), PEAR_LOG_ERR);
							// NOW login in as masquerade user
							//$logger->log("Masqueraded User " . (empty($masqueradedUser) ? 'none' : $masqueradedUser->id), PEAR_LOG_ERR);
							$_REQUEST['username'] = $masqueradedUser->cat_username;
							$_REQUEST['password'] = $masqueradedUser->cat_password;
							//$logger->log("Masquerade Login " . $_REQUEST['username'] . " " . $_REQUEST['password'], PEAR_LOG_ERR);
							$user                 = UserAccount::login();
							//$logger->log("New User " . (empty($user) ? 'none' : $user->id), PEAR_LOG_ERR);
							if (!empty($user) && !PEAR_Singleton::isError($user)){
								@session_start(); // (suppress notice if the session is already started)
								$_SESSION['guidingUserId'] = $guidingUser->id;
								$_SESSION['activeUserId'] = $user->id;
								return array('success' => true);
							} else {
								unset($_SESSION['guidingUserId']);
								$user = $guidingUser;
								return array(
										'success' => false,
										'error'   => 'Failed to initiate masquerade as specified user.'
								);
							}
						} else {

						}
					} else {
						return array(
							'success' => false,
							'error'   => $user ? 'You are not allowed to Masquerade.' : 'Not logged in. Please Log in.'
						);
					}
				} else {
					return array(
						'success' => false,
						'error'   => 'Already Masquerading.'
					);
				}
			} else {
				return array(
					'success' => false,
					'error'   => 'Please enter a valid Library Card Number.'
				);
			}
		} else {
			return array(
				'success' => false,
				'error'   => 'Masquerade Mode is not allowed.'
			);
		}
	}

	static function endMasquerade() {
		if (UserAccount::isLoggedIn()) {
			global $guidingUser,
			       $masqueradeMode;
			@session_start();  // (suppress notice if the session is already started)
			unset($_SESSION['guidingUserId']);
			$masqueradeMode = false;
			if ($guidingUser) {
				$_REQUEST['username'] = $guidingUser->cat_username;
				$_REQUEST['password'] = $guidingUser->cat_password;
				$user = UserAccount::login();
				if ($user && !PEAR_Singleton::isError($user)) {
					return array('success' => true);
				}
			}
		}
		return array('success' => false);
	}

}