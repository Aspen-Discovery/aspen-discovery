<?php

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
				//$logger->log("Masquerading as " . $_REQUEST['cardNumber'], Logger::LOG_ERROR);
				$libraryCard = trim($_REQUEST['cardNumber']);
				global $guidingUser;
				if (empty($guidingUser)) {
					$user = UserAccount::getLoggedInUser();
					if ($user && $user->canMasquerade()) {
						//Check to see if the user already exists in the database
						$foundExistingUser = false;
						$accountProfile = new AccountProfile();
						$accountProfile->find();
						$masqueradedUser = null;
						while ($accountProfile->fetch()){
							$masqueradedUser = new User();
							$masqueradedUser->source = $accountProfile->name;
							if ($accountProfile->loginConfiguration == 'barcode_pin') {
								$masqueradedUser->cat_username = $libraryCard;
							} else {
								$masqueradedUser->cat_password = $libraryCard;
							}
							if ($masqueradedUser->find(true)) {
								if ($masqueradedUser->id == $user->id) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'No need to masquerade as yourself.', 'isAdminFacing'=>true])
									);
								}
								$foundExistingUser = true;
								break;
							}else{
								$masqueradedUser = null;
							}
						}

						if (!$foundExistingUser) {
							// Test for a user that hasn't logged into Aspen Discovery before
							$masqueradedUser = UserAccount::findNewUser($libraryCard);
							if (!$masqueradedUser) {
								return array(
									'success' => false,
									'error' => translate(['text'=>'Invalid User', 'isAdminFacing'=>true])
								);
							}
						}

						// Now that we have found the masqueraded User, check Masquerade Levels
						if ($masqueradedUser) {
							//Check for errors
							$masqueradedUserPType = new PType();
							$masqueradedUserPType->pType = $masqueradedUser->patronType;
							$isRestrictedUser = true;
							if ($masqueradedUserPType->find(true)) {
								if ($masqueradedUserPType->restrictMasquerade == 0) {
									$isRestrictedUser = false;
								}
							}
							/** @noinspection PhpStatementHasEmptyBodyInspection */
							if (UserAccount::userHasPermission('Masquerade as any user')) {
								//The user can masquerade as anyone, no additional checks needed
							}elseif (UserAccount::userHasPermission('Masquerade as unrestricted patron types')) {
								if ($isRestrictedUser) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'Cannot masquerade as patrons of this type.', 'isAdminFacing'=>true])
									);
								}
							}elseif (UserAccount::userHasPermission('Masquerade as patrons with same home library') || UserAccount::userHasPermission('Masquerade as unrestricted patrons with same home library')) {
								$guidingUserLibrary = $user->getHomeLibrary();
								if (!$guidingUserLibrary) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'Could not determine your home library.', 'isAdminFacing'=>true])
									);
								}
								$masqueradedUserLibrary = $masqueradedUser->getHomeLibrary();
								if (!$masqueradedUserLibrary) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'Could not determine the patron\'s home library.', 'isAdminFacing'=>true])
									);
								}
								if ($guidingUserLibrary->libraryId != $masqueradedUserLibrary->libraryId) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'You do not have the same home library as the patron.', 'isAdminFacing'=>true])
									);
								}
								if ($isRestrictedUser && !UserAccount::userHasPermission('Masquerade as patrons with same home library')) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'Cannot masquerade as patrons of this type.', 'isAdminFacing'=>true])
									);
								}
							}elseif (UserAccount::userHasPermission('Masquerade as patrons with same home location') || UserAccount::userHasPermission('Masquerade as unrestricted patrons with same home location')) {
								if (empty($user->homeLocationId)) {
									return array(
										'success' => false,
										'error'   => translate(['text'=>'Could not determine your home library branch.', 'isAdminFacing'=>true])
									);
								}
								if (empty($masqueradedUser->homeLocationId)) {
									return array(
										'success' => false,
										'error'   => translate(['text'=>'Could not determine the patron\'s home library branch.', 'isAdminFacing'=>true])
									);
								}
								if ($user->homeLocationId != $masqueradedUser->homeLocationId) {
									return array(
										'success' => false,
										'error'   => translate(['text'=>'You do not have the same home library branch as the patron.', 'isAdminFacing'=>true])
									);
								}
								if ($isRestrictedUser && !UserAccount::userHasPermission('Masquerade as patrons with same home location')) {
									return array(
										'success' => false,
										'error' => translate(['text'=>'Cannot masquerade as patrons of this type.', 'isAdminFacing'=>true])
									);
								}
							}

							//Setup the guiding user and masqueraded user
							global $guidingUser;
							$guidingUser = $user;
							$user = $masqueradedUser;
							if (!empty($user) && !($user instanceof AspenError)){
								if ($user->lastLoginValidation < (time() - 15 * 60)) {
									$user->loadContactInformation();
									$user->validateUniqueId();
								}

								@session_start(); // (suppress notice if the session is already started)
								$_SESSION['guidingUserId'] = $guidingUser->id;
								$_SESSION['activeUserId'] = $user->id;
								@session_write_close();
								return array('success' => true);
							} else {
								unset($_SESSION['guidingUserId']);
								return array(
									'success' => false,
									'error'   => translate(['text'=>'Failed to initiate masquerade as specified user.', 'isAdminFacing'=>true])
								);
							}
						} else {
							return array(
								'success' => false,
								'error'   => translate(['text'=>'Could not load user to masquerade as.', 'isAdminFacing'=>true])
							);
						}
					} else {
						return array(
							'success' => false,
							'error'   => $user ? translate(['text'=>'You are not allowed to Masquerade.', 'isAdminFacing'=>true]) : translate(['text'=>'Your session has expired, please sign in again.', 'isAdminFacing'=>true])
						);
					}
				} else {
					return array(
						'success' => false,
						'error'   => translate(['text'=>'Already Masquerading.', 'isAdminFacing'=>true])
					);
				}
			} else {
				return array(
					'success' => false,
					'error'   => translate(['text'=>'Please enter a valid Library Card Number.', 'isAdminFacing'=>true])
				);
			}
		} else {
			return array(
				'success' => false,
				'error'   => translate(['text'=>'Masquerade Mode is not allowed.', 'isAdminFacing'=>true])
			);
		}
	}

	static function endMasquerade() {
		if (UserAccount::isLoggedIn()) {
			/** @var User $guidingUser */
			global $guidingUser;
			global $masqueradeMode;
			@session_start();  // (suppress notice if the session is already started)
			unset($_SESSION['guidingUserId']);
			$masqueradeMode = false;
			if ($guidingUser) {
				$_REQUEST['username'] = $guidingUser->getBarcode();
				$_REQUEST['password'] = $guidingUser->getPasswordOrPin();
				$user = UserAccount::login();
				if ($user && !($user instanceof AspenError)) {
					return array('success' => true);
				}else{
					UserAccount::softLogout();
				}
			}
		}
		return array('success' => false);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Masquerade as another user');
		return $breadcrumbs;
	}
}