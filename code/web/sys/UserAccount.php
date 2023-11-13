<?php

require_once ROOT_DIR . '/sys/Authentication/AuthenticationFactory.php';
require_once ROOT_DIR . '/sys/Account/TwoFactorAuthenticationError.php';

class UserAccount {
	public static $isLoggedIn = null;
	public static $isAuthenticated = false;
	public static $primaryUserData = null;
	/** @var User|false */
	private static $primaryUserObjectFromDB = null;
	/** @var User|false $guidingUserObjectFromDB */
	private static $guidingUserObjectFromDB = null;
	private static $userRoles = null;
	private static $userPermissions = null;
	private static $ssoAuthOnly = false;

	/**
	 * Check to see if the user is enrolled in 2 factor authentication and has been sent a verification code, but has not verified it.
	 *
	 * @return bool
	 */
	public static function needsToComplete2FA(): bool {
		try {
			require_once ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
			$twoFactorSetting = new TwoFactorAuthSetting();
			$twoFactorSetting->whereAdd("isEnabled = 'optional' OR isEnabled = 'mandatory'");
			if ($twoFactorSetting->find()) {

				if (!UserAccount::isUserMasquerading()) {
					//Two factor might be required
					if (UserAccount::has2FAEnabled()) {
						//Two factor is required, check to see if it's complete.
						//Check the session to see if it is complete
						require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
						$authCodeForSession = new TwoFactorAuthCode();
						$authCodeForSession->sessionId = session_id();
						$authCodeForSession->userId = $_SESSION['activeUserId'];
						if ($authCodeForSession->find(true)) {
							$needsToComplete2FA = $authCodeForSession->status != 'used';
						} else {
							$needsToComplete2FA = true;
						}
					} elseif (UserAccount::isRequired2FA() && !UserAccount::has2FAEnabled()) {
						$needsToComplete2FA = true;
					} else {
						$needsToComplete2FA = false;
					}
				} else {
					$needsToComplete2FA = false;
				}
			} else {
				$needsToComplete2FA = false;
			}
		} catch (PDOException $e) {
			//This happens if the table has not been created.
			$needsToComplete2FA = false;
		}
		return $needsToComplete2FA;
	}

	/**
	 *
	 * Checks whether the user is logged in.
	 *
	 * When logged in we store information the id of the active user within the session.
	 * The actual user is stored within memcache
	 *
	 * @return bool
	 */
	public static function isLoggedIn() {
		if (UserAccount::$isLoggedIn == null) {
			if (isset($_SESSION['activeUserId'])) {
				if(isset($_SESSION['loggedInViaSSO'])) {
					UserAccount::$isLoggedIn = true;
				} else {
					UserAccount::$isLoggedIn = !UserAccount::needsToComplete2FA();
				}
			} else {
				UserAccount::$isLoggedIn = false;
				//Need to check cas just in case the user logged in from another site
				//If the library uses CAS/SSO we may already be logged in even though they never logged in within Aspen
				global $library;
				if ($library && strlen($library->casHost) > 0) {
					$checkCAS = false;
					$curTime = time();
					if (!isset($_SESSION['lastCASCheck'])) {
						$checkCAS = true;
					} elseif ($curTime - $_SESSION['lastCASCheck'] > 10) {
						$checkCAS = true;
					}
					global $action;
					global $module;
					if ($checkCAS && $action != 'AJAX' && $action != 'DjatokaResolver' && $action != 'Logout' && $module != 'MyAccount' && $module != 'API' && !isset($_REQUEST['username'])) {
						//Check CAS first
						require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
						global $logger;
						$casAuthentication = new CASAuthentication(null);
						$casUsername = $casAuthentication->validateAccount(null, null, null, null, false);
						$_SESSION['lastCASCheck'] = time();
						$logger->log("Checked CAS Authentication from UserAccount::isLoggedIn result was $casUsername", Logger::LOG_DEBUG);
						if ($casUsername == false || $casUsername instanceof AspenError) {
							//The user could not be authenticated in CAS
							UserAccount::$isLoggedIn = false;

						} else {
							$logger->log("We got a valid user from CAS, getting the user from the database", Logger::LOG_DEBUG);
							//We have a valid user via CAS, need to do a login to Aspen
							$_REQUEST['casLogin'] = true;
							UserAccount::$isLoggedIn = true;
							//Set the active user id for the user
							$user = new User();
							//TODO this may need to change if anyone but Fort Lewis ever does CAS authentication
							$user->cat_password = $casUsername;
							if ($user->find(true)) {
								$_SESSION['activeUserId'] = $user->id;
								UserAccount::$primaryUserObjectFromDB = $user;
							}
						}
					}
				}
			}
		}
		return UserAccount::$isLoggedIn;
	}

	/**
	 * @return bool|integer
	 */
	public static function getActiveUserId() {
		if (isset($_SESSION['activeUserId'])) {
			return $_SESSION['activeUserId'];
		} else {
			return false;
		}
	}

	/**
	 * @param string[]|string $permission
	 * @return bool
	 */
	public static function userHasPermission($permission) {
		$userPermissions = UserAccount::getActivePermissions();
		if (is_array($permission)) {
			foreach ($permission as $tmpPermission) {
				if (in_array($tmpPermission, $userPermissions)) {
					return true;
				}
			}
			return false;
		} else {
			return in_array($permission, $userPermissions);
		}
	}

	public static function getActiveRoles() {
		if (UserAccount::$userRoles == null) {
			if (UserAccount::isLoggedIn()) {
				UserAccount::$userRoles = UserAccount::getActiveUserObj()->getRoles();
			} else {
				UserAccount::$userRoles = [];
			}
		}
		return UserAccount::$userRoles;
	}

	public static function getActivePermissions() {
		if (UserAccount::$userPermissions == null) {
			if (UserAccount::isLoggedIn()) {
				UserAccount::$userPermissions = [];

				$loadDefaultPermissions = false;
				try {
					$activeUser = UserAccount::getActiveUserObj();
					UserAccount::$userPermissions = $activeUser->getPermissions();
				} catch (Exception $e) {
					$loadDefaultPermissions = true;
				}
				if ($loadDefaultPermissions) {
					//Permission system has not been setup, load default permissions
					$roles = UserAccount::getActiveRoles();
					foreach ($roles as $roleId => $roleName) {
						$role = new Role();
						$role->roleId = $roleId;
						if ($role->find(true)) {
							$defaultPermissions = $role->getDefaultPermissions();
							foreach ($defaultPermissions as $permissionName) {
								if (!in_array($permissionName, UserAccount::$userPermissions)) {
									UserAccount::$userPermissions[] = $permissionName;
								}
							}
						}
					}
				}
			} else {
				UserAccount::$userPermissions = [];
			}
		}
		return UserAccount::$userPermissions;
	}

	private static function loadUserObjectFromDatabase() {
		if (UserAccount::$primaryUserObjectFromDB == null) {
			$activeUserId = UserAccount::getActiveUserId();
			if ($activeUserId) {
				$user = new User();
				$user->id = $activeUserId;
				if ($user->find(true)) {
					UserAccount::$primaryUserObjectFromDB = $user;
				} else {
					UserAccount::$primaryUserObjectFromDB = false;
				}
			} else {
				UserAccount::$primaryUserObjectFromDB = false;
			}
		}
	}

	/**
	 * @return User|bool
	 */
	public static function getActiveUserObj() {
		UserAccount::loadUserObjectFromDatabase();
		return UserAccount::$primaryUserObjectFromDB;
	}

	public static function getUserDisplayName() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->getDisplayName();
		}
		return '';
	}

	public static function getUserInterfaceLanguage() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->interfaceLanguage;
		}
		return '';
	}

	public static function getUserHasInterLibraryLoan() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->hasInterlibraryLoan();
		}
		return false;
	}

	public static function getUserHasCatalogConnection() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::getActiveUserObj()->hasIlsConnection();
		}
		return false;
	}

	public static function getUserPType() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->patronType;
		}
		return 'logged out';
	}

	public static function getDisableCoverArt() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->disableCoverArt;
		}
		return 'logged out';
	}

	public static function hasLinkedUsers() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return count(UserAccount::$primaryUserObjectFromDB->getLinkedUserObjects()) > 0;
		}
		return 'false';
	}

	public static function getUserHomeLocationId() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false) {
			return UserAccount::$primaryUserObjectFromDB->homeLocationId;
		}
		return -1;
	}

	public static function isUserMasquerading() {
		return !empty($_SESSION['guidingUserId']);
	}

	public static function getGuidingUserId() {
		if (empty($_SESSION['guidingUserId'])) {
			return false;
		} else {
			return $_SESSION['guidingUserId'];
		}
	}

	public static function getGuidingUserObject(): ?User {
		if (UserAccount::$guidingUserObjectFromDB == null) {
			if (UserAccount::isUserMasquerading()) {
				$activeUserId = $_SESSION['guidingUserId'];
				if ($activeUserId) {
					$user = new User();
					$user->id = $activeUserId;
					if ($user->find(true)) {
						UserAccount::$guidingUserObjectFromDB = $user;
					} else {
						UserAccount::$guidingUserObjectFromDB = false;
					}
				} else {
					UserAccount::$guidingUserObjectFromDB = false;
				}
			} else {
				UserAccount::$guidingUserObjectFromDB = false;
			}
		}
		return UserAccount::$guidingUserObjectFromDB;
	}

	/**
	 * @return User|false
	 */
	public static function getLoggedInUser() {
		if (UserAccount::$isLoggedIn != null) {
			if (UserAccount::$isLoggedIn) {
				if (!is_null(UserAccount::$primaryUserData) && !isset($_REQUEST['reload'])) {
					return UserAccount::$primaryUserData;
				}
			} else {
				return false;
			}
		}
		global $action;
		global $module;
		//global $logger;

		$userData = false;
		if (isset($_SESSION['activeUserId'])) {
			$activeUserId = $_SESSION['activeUserId'];
			//global $memCache;
			//global $serverName;

			///** @var User $userData */
			//$userData = $memCache->get("user_{$serverName}_{$activeUserId}");
			//if ($userData === false || isset($_REQUEST['reload'])) {

				//Load the user from the database
				$userData = new User();

				$userData->id = $activeUserId;
				if ($userData->find(true)) {
					if (UserAccount::isUserMasquerading() || !empty($_SESSION['loggedInViaSSO'])) {
						return $userData;
					} else {
						//Get the account profile for the user
						$driversToTest = UserAccount::getAccountProfiles();
						if (!array_key_exists($userData->source, $driversToTest)) {
							AspenError::raiseError("We could not validate your account, please logout and login again. If this error persists, please contact the library. Error ($activeUserId)");
						}
						$accountProfile = $driversToTest[$userData->source]['accountProfile'];
						if ($accountProfile->authenticationMethod == 'db') {
							$userData = UserAccount::validateAccount($userData->username, $userData->password, $userData->source);
						} else {
							$userData = UserAccount::validateAccount($userData->ils_barcode, $userData->ils_password, $userData->source);
						}

						if ($userData == false) {
							//This happens when the PIN has been reset in the ILS, redirect to the login page
							global $isAJAX;
							if (!$isAJAX) {
								UserAccount::softLogout();

								require_once ROOT_DIR . '/services/MyAccount/Login.php';
								$launchAction = new MyAccount_Login();
								$launchAction->launch();
								exit();
							}
							AspenError::raiseError("We could not validate your account, please logout and login again. If this error persists, please contact the library. Error ($activeUserId)");
						}
						self::updateSession($userData);
					}
				} else {
					AspenError::raiseError("Error validating saved session for user $activeUserId, the user was not found in the database.");
				}
			//} else {
				//$logger->log("Found cached user {$userData->id}", Logger::LOG_DEBUG);
			//}
			UserAccount::$isLoggedIn = true;

			$masqueradeMode = UserAccount::isUserMasquerading();
			if ($masqueradeMode) {
				global $guidingUser;
				//$guidingUser = $memCache->get("user_{$serverName}_{$_SESSION['guidingUserId']}"); //TODO: check if this ever works
				//if ($guidingUser === false || isset($_REQUEST['reload'])) {
					$guidingUser = new User();
					$guidingUser->get($_SESSION['guidingUserId']);
					if (!$guidingUser) {
						global $logger;
						$logger->log('Invalid Guiding User ID in session variable: ' . $_SESSION['guidingUserId'], Logger::LOG_ERROR);
						unset($_SESSION['guidingUserId']); // session_start(); session_commit(); probably needed for this to take effect, but might have other side effects
					}
				//}
			}

			//Check to see if the patron is already logged in within CAS as long as we aren't on a page that is likely to be a login page
		} elseif ($action != 'AJAX' && $action != 'DjatokaResolver' && $action != 'Logout' && $module != 'MyAccount' && $module != 'API' && !isset($_REQUEST['username'])) {
			//If the library uses CAS/SSO we may already be logged in even though they never logged in within Aspen
			global $library;
			if (strlen($library->casHost) > 0) {
				//Check CAS first
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				global $logger;
				$logger->log("Checking CAS Authentication from UserAccount::getLoggedInUser", Logger::LOG_DEBUG);
				$casUsername = $casAuthentication->validateAccount(null, null, null, null, false);
				if ($casUsername == false || $casUsername instanceof AspenError) {
					//The user could not be authenticated in CAS
					UserAccount::$isLoggedIn = false;
					return false;
				} else {
					//We have a valid user via CAS, need to do a login to Aspen
					$_REQUEST['casLogin'] = true;
					try {
						$userData = UserAccount::login();
					} catch (UnknownAuthenticationMethodException $e) {
						echo("Unknown validation method $e");
						die();
					}
					UserAccount::$isLoggedIn = true;
				}
			}
		}
		if (UserAccount::$isLoggedIn) {
			UserAccount::$primaryUserData = $userData;
			global $interface;
			if ($interface) {
				$interface->assign('user', $userData);
			}
		}
		return UserAccount::$primaryUserData;
	}

	/**
	 * Updates the user information in the session and in memcache
	 *
	 * @param User $user
	 */
	public static function updateSession($user) {
		if ($user != false) {
			$_SESSION['activeUserId'] = $user->id;
		} else {
			unset($_SESSION['activeUserId']);
		}

		if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] === "true" || $_REQUEST['rememberMe'] === "on")) {
			$_SESSION['rememberMe'] = true;
		} else {
			$_SESSION['rememberMe'] = false;
		}

		// If the user browser has the showCovers settings stored, set the Session variable
		// Used for showing or hiding covers on MyAccount Pages
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			$_SESSION['showCovers'] = $showCovers;
		}
	}

	/**
	 * Try to log in the user using current query parameters
	 * return User object on success, AspenError on failure.
	 *
	 * @return AspenError|2FA|User
	 * @throws UnknownAuthenticationMethodException
	 */
	public static function login($validatedViaSSO = false) {
		global $logger;
		global $usageByIPAddress;
		$usageByIPAddress->numLoginAttempts++;
		UserAccount::$ssoAuthOnly = UserAccount::isPrimaryAccountAuthenticationSSO();
		$localAuthOnly = false;

		if (isset($_REQUEST['casLogin'])) {
			$logger->log("Logging the user in via CAS", Logger::LOG_NOTICE);
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$casUsername = $casAuthentication->authenticate(false);
			if ($casUsername == false || $casUsername instanceof AspenError) {
				//The user could not be authenticated in CAS
				$logger->log("The user could not be logged in", Logger::LOG_NOTICE);
				$usageByIPAddress->numFailedLoginAttempts++;
				require_once ROOT_DIR . '/sys/SystemLogging/FailedLoginsByIPAddress.php';
				FailedLoginsByIPAddress::addFailedLogin();
				return new AspenError('Could not authenticate in sign on service');
			} else {
				$logger->log("User logged in OK CAS Username $casUsername", Logger::LOG_NOTICE);
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$_REQUEST['username'] = $casUsername;
				$_REQUEST['password'] = $casUsername;
				$validatedViaSSO = true;
			}
		}

		if(isset($_REQUEST['ldapLogin']) && !$validatedViaSSO) {
			$logger->log('Logging the user in via LDAP', Logger::LOG_NOTICE);
			require_once ROOT_DIR . '/sys/Authentication/LDAPAuthentication.php';
			$ldapAuthentication = new LDAPAuthentication();
			$isAuthenticated = $ldapAuthentication->validateAccount($_POST['username'], $_POST['password'], null);
			if($isAuthenticated instanceof AspenError) {
				$logger->log('The user could not be logged in', Logger::LOG_NOTICE);
				$usageByIPAddress->numFailedLoginAttempts++;
				require_once ROOT_DIR . '/sys/SystemLogging/FailedLoginsByIPAddress.php';
				FailedLoginsByIPAddress::addFailedLogin();
				return new AspenError('Could not authenticate in sign on service');
			} else {
				$logger->log("LDAP user logged in OK", Logger::LOG_NOTICE);
				$validatedViaSSO = true;
				$localAuthOnly = UserAccount::isSSOAuthenticationOnly();
			}
		}

		/** @var User $primaryUser */
		$primaryUser = null;
		$lastError = null;
		$driversToTest = self::getAccountProfiles();

		//Test each driver in turn.  We do test all of them in case an account is valid in
		//more than one system
		foreach ($driversToTest as $driverName => $driverData) {
			// Perform authentication:
			$authN = AuthenticationFactory::initAuthentication($driverData['authenticationMethod'], $driverData);
			// We get back 1 of 3 states from the authenticate call:
			//  1) A user which means we authenticated correctly
			//  2) Null which means the authentication method couldn't handle the user
			//  3) AspenError which means the authentication method handled the user, but didn't find the user

			if(!$localAuthOnly) {
				$tempUser = $authN->authenticate($validatedViaSSO, $driverData['accountProfile']);
			} else {
				$tempUser = UserAccount::findNewAspenUser('username', $_POST['username']);
			}

			// If we authenticated, store the user in the session:
			if ((!($tempUser instanceof AspenError) && $tempUser != null)) {
				if ($validatedViaSSO) {
					if (isset($_REQUEST['casLogin'])) {
						$_SESSION['loggedInViaCAS'] = true;
					}

					if (isset($_REQUEST['ldapLogin'])) {
						$_SESSION['loggedInViaLDAP'] = true;
					}
				}

				if(!UserAccount::$ssoAuthOnly && !$localAuthOnly) {
					global $library;
					if ($library->preventExpiredCardLogin && $tempUser->_expired) {
						// Create error
						$cardExpired = new AspenError('Your library card has expired. Please contact your local library to have your library card renewed.');
						$usageByIPAddress->numFailedLoginAttempts++;
						//DON'T mark this as a failed login in the IP table since they were actually valid and we're blocking them
						return $cardExpired;
					} elseif ($library->allowLoginToPatronsOfThisLibraryOnly && ($tempUser->getHomeLibrary() != null && ($tempUser->getHomeLibrary()->libraryId != $library->libraryId))) {
						$disallowedMessage = empty(trim(strip_tags($library->messageForPatronsOfOtherLibraries))) ? 'Sorry, this catalog can only be accessed by patrons of ' . $library->displayName : $library->messageForPatronsOfOtherLibraries;
						return new AspenError($disallowedMessage);
					} elseif ($tempUser->getHomeLibrary() != null && ($tempUser->getHomeLibrary()->preventLogin)) {
						$disallowedMessage = empty(trim(strip_tags($tempUser->getHomeLibrary()->preventLoginMessage))) ? 'Sorry, patrons of ' . $library->displayName . ' cannot login at this time.' : $tempUser->getHomeLibrary()->preventLoginMessage;
						return new AspenError($disallowedMessage);
					}
				}

				//global $memCache;
				//global $serverName;
				//global $configArray;
				//$memCache->set("user_{$serverName}_{$tempUser->id}", $tempUser, $configArray['Caching']['user']);
				//$logger->log("Cached user {$tempUser->id}", Logger::LOG_DEBUG);

				if ($primaryUser == null) {
					$primaryUser = $tempUser;
					self::updateSession($primaryUser);
				} else {
					//We have more than one account with these credentials, automatically link them
					$primaryUser->addLinkedUser($tempUser);
				}
			} elseif ($tempUser != null) {
				$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : 'No username provided';
				$logger->log("Error authenticating patron $username for driver {$driverName}", Logger::LOG_ERROR);
				$lastError = $tempUser;
				$logger->log($lastError->toString(), Logger::LOG_ERROR);
			}
		}

		// Send back the user object (which may be an AspenError):
		if ($primaryUser) {
			if (!$validatedViaSSO && UserAccount::isRequired2FA() && !UserAccount::has2FAEnabled() && UserAccount::$isAuthenticated === false) {
				UserAccount::$isLoggedIn = false;
				$logger->log("User needs to enroll in two-factor authentication", Logger::LOG_DEBUG);
				$_SESSION['enroll2FA'] = true;
				$_SESSION['has2FA'] = false;
				$_SESSION['codeSent'] = false;
				return new TwoFactorAuthenticationError(UserAccount::getActiveUserId(), TwoFactorAuthenticationError:: MUST_ENROLL, "User needs to enroll in two-factor authentication");
			} elseif (!$validatedViaSSO && UserAccount::has2FAEnabled() && UserAccount::$isAuthenticated === false) {
				UserAccount::$isLoggedIn = false;
				$logger->log("User needs to two-factor authenticate", Logger::LOG_DEBUG);
				require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
				$twoFactorAuth = new TwoFactorAuthCode();
				$codeSent = $twoFactorAuth->createCode();
				$_SESSION['enroll2FA'] = false;
				$_SESSION['codeSent'] = $codeSent;
				$_SESSION['has2FA'] = $codeSent;
				return new TwoFactorAuthenticationError(UserAccount::getActiveUserId(), TwoFactorAuthenticationError::MUST_COMPLETE_AUTHENTICATION, 'You must authenticate before logging in. Please provide the 6-digit code that was emailed to you.');
			} else {
				$_SESSION['enroll2FA'] = false;
				$_SESSION['has2FA'] = false;
				$_SESSION['codeSent'] = false;
				UserAccount::$isLoggedIn = true;
				UserAccount::$primaryUserData = $primaryUser;
				if (isset($_COOKIE['searchPreferenceLanguage']) && $primaryUser->searchPreferenceLanguage == -1) {
					$primaryUser->searchPreferenceLanguage = $_COOKIE['searchPreferenceLanguage'];
					$primaryUser->update();
				}
				return $primaryUser;
			}
		} else {
			$usageByIPAddress->numFailedLoginAttempts++;
			require_once ROOT_DIR . '/sys/SystemLogging/FailedLoginsByIPAddress.php';
			FailedLoginsByIPAddress::addFailedLogin();
			return $lastError;
		}
	}

	public static function loginWithAspen(User $user) {
		self::updateSession($user);
		UserAccount::$isLoggedIn = true;
		UserAccount::$primaryUserData = $user;
		if (isset($_COOKIE['searchPreferenceLanguage']) && $user->searchPreferenceLanguage == -1) {
			$user->searchPreferenceLanguage = $_COOKIE['searchPreferenceLanguage'];
			$user->update();
		}
		return $user;
	}

	private static $validatedAccounts = [];

	/**
	 * Validate the account information (username and password are correct).
	 * Returns the account, but does not set the global user variable.
	 *
	 * @param $username       string
	 * @param $password       string
	 * @param $accountSource  string The source of the user account if known or null to test all sources
	 * @param $parentAccount  User   The parent user if any
	 *
	 * @return User|false
	 */
	public static function validateAccount($username, $password, $accountSource = null, $parentAccount = null) {
		if (array_key_exists($username . $password, UserAccount::$validatedAccounts)) {
			return UserAccount::$validatedAccounts[$username . $password];
		}
		require_once ROOT_DIR . '/CatalogFactory.php';
		// Perform authentication:
		//Test all valid authentication methods and see which (if any) result in a valid login.
		$driversToTest = self::getAccountProfiles();

		global $library;
		global $logger;
		$validatedViaSSO = false;
		if (strlen($library->casHost) > 0 && $username == null && $password == null) {
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$logger->log("Checking CAS Authentication from UserAccount::validateAccount", Logger::LOG_DEBUG);
			$authenticationProfile = null;
			if (!array_key_exists($accountSource, $driversToTest)) {
				$logger->log("Could not find account profile for $accountSource", Logger::LOG_DEBUG);
				UserAccount::$validatedAccounts[$username . $password . $accountSource] = false;
				return false;
			}
			$userDriver = $driversToTest[$accountSource];
			$authenticationProfile = $userDriver['accountProfile'];
			$casUsername = $casAuthentication->validateAccount(null, null, $authenticationProfile, $parentAccount, false);
			if ($casUsername == false || $casUsername instanceof AspenError) {
				//The user could not be authenticated in CAS
				$logger->log("User could not be validated", Logger::LOG_DEBUG);
				UserAccount::$validatedAccounts[$username . $password . $accountSource] = false;
				return false;
			} else {
				$logger->log("User was validated", Logger::LOG_DEBUG);
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$username = $casUsername;
				$password = $casUsername;
				$validatedViaSSO = true;
			}
		}

		foreach ($driversToTest as $driverName => $additionalInfo) {
			if ($accountSource == null || $accountSource == $additionalInfo['accountProfile']->name) {
				try {
					$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
				} catch (UnknownAuthenticationMethodException $e) {
					echo("Unknown validation method $e");
					die();
				}
				$validatedUser = $authN->validateAccount($username, $password, $additionalInfo['accountProfile'], $parentAccount, $validatedViaSSO);
				if ($validatedUser && !($validatedUser instanceof AspenError)) {
					//global $memCache;
					//global $serverName;
					//global $configArray;
					//$memCache->set("user_{$serverName}_{$validatedUser->id}", $validatedUser, $configArray['Caching']['user']);
					//$logger->log("Cached user {$validatedUser->id}", Logger::LOG_DEBUG);
					if ($validatedViaSSO) {
						if (isset($_REQUEST['casLogin'])) {
							$_SESSION['loggedInViaCAS'] = true;
						}

						if (isset($_REQUEST['ldapLogin'])) {
							$_SESSION['loggedInViaLDAP'] = true;
						}
					}
					UserAccount::$validatedAccounts[$username . $password. $accountSource] = $validatedUser;
					return $validatedUser;
				}
			}
		}

		UserAccount::$validatedAccounts[$username . $password] = false;
		return false;
	}

	public static function getUserByBarcode($username) {
		require_once ROOT_DIR . '/CatalogFactory.php';
		// Perform authentication:
		//Test all valid authentication methods and see which (if any) result in a valid login.
		$driversToTest = self::getAccountProfiles();
		foreach ($driversToTest as $driverName => $additionalInfo) {
			$accountProfile = $additionalInfo['accountProfile'];
			$user = new User();
			$user->source = $accountProfile->name;
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				return $user;
			}
		}
		return false;
	}

	/**
	 * Completely logout the user annihilating their entire session.
	 */
	public static function logout() {
		//global $logger;
		//$logger->log("Logging user out", Logger::LOG_DEBUG);
		UserAccount::softLogout();
		$_SESSION = [];

		if (isset($_COOKIE['searchPreferenceLanguage'])) {
			setcookie('searchPreferenceLanguage', $_COOKIE['searchPreferenceLanguage'], time() - 1000, '/');
			unset($_COOKIE['searchPreferenceLanguage]']);
		}
		session_regenerate_id(true);
		//$logger->log("New session id is $newId", Logger::LOG_DEBUG);
	}

	/**
	 * Remove user info from the session so the user is not logged in, but
	 * preserve hold message and search information
	 */
	public static function softLogout() {
		if (isset($_SESSION['activeUserId'])) {
			$user = UserAccount::getActiveUserObj();
			if ($user != false) {
				$user->logout();
			}
			//global $logger;
			//$logger->log("Logging user {$_SESSION['activeUserId']} out", Logger::LOG_DEBUG);
			if (isset($_SESSION['guidingUserId'])) {
				// Shouldn't end up here while in Masquerade Mode, but if does happen end masquerading as well
				unset($_SESSION['guidingUserId']);
			}
			if (isset($_SESSION['loggedInViaCAS']) && $_SESSION['loggedInViaCAS']) {
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				$casAuthentication->logout();
			}
			unset($_SESSION['activeUserId']);
			if (isset($_SESSION['lastCASCheck'])) {
				unset($_SESSION['lastCASCheck']);
			}
			unset($_SESSION['has2FA]']);
			unset($_SESSION['enroll2FA]']);
			unset($_SESSION['codeSent]']);

			UserAccount::$isLoggedIn = false;
			UserAccount::$primaryUserData = null;
			UserAccount::$primaryUserObjectFromDB = null;
			UserAccount::$guidingUserObjectFromDB = null;

			global $interface;
			$interface->assign('loggedIn', false);

			//global $logger;
			//$logger->log('Finished updating session as part of softLogout, will write on shutdown', Logger::LOG_DEBUG);
			//$logger->log(print_r($_SESSION, true), Logger::LOG_DEBUG);
		}
	}

	private static $_accountProfiles = null;

	/**
	 * @return AccountProfile[]
	 */
	static function getAccountProfiles() {
		if (UserAccount::$_accountProfiles == null) {
			UserAccount::$_accountProfiles = [];

			//Load a list of authentication methods to test and see which (if any) result in a valid login.
			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->orderBy([
				'weight',
				'name',
			]);
			$accountProfile->find();
			while ($accountProfile->fetch()) {
				$additionalInfo = [
					'driver' => $accountProfile->driver,
					'authenticationMethod' => $accountProfile->authenticationMethod,
					'accountProfile' => clone($accountProfile),
				];
				UserAccount::$_accountProfiles[$accountProfile->name] = $additionalInfo;
			}
			if (count(UserAccount::$_accountProfiles) == 0) {
				echo("Account profiles must be defined in the database for proper setup.");
				die();
			}
			global $timer;
			$timer->logTime("Loaded Account Profiles");
		}
		return UserAccount::$_accountProfiles;
	}

	public static function getAccountProfile($name) : ?AccountProfile {
		$accountProfiles = UserAccount::getAccountProfiles();
		if (array_key_exists($name, $accountProfiles)) {
			return $accountProfiles[$name]['accountProfile'];
		}else{
			return null;
		}
	}

	public static function getAccountProfileByRecordSource($name) : ?AccountProfile {
		$accountProfiles = UserAccount::getAccountProfiles();
		foreach ($accountProfiles as $accountProfile) {
			if ($accountProfile['accountProfile']->recordSource == $name) {
				return $accountProfile['accountProfile'];
			}
		}
		return null;
	}

	public static function getLibraryAccountProfile() : ?AccountProfile {
		global $library;
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->id = $library->accountProfileId;
		if($accountProfile->find(true)) {
			return $accountProfile;
		}
		return null;
	}

	public static function isPrimaryAccountAuthenticationSSO(): bool {
		global $library;
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->id = $library->accountProfileId;
		if($accountProfile->find(true)) {
			if($accountProfile->authenticationMethod === 'sso') {
				return true;
			}
		}
		return false;
	}

	public static function isSSOAuthenticationOnly(): bool {
		global $library;
		require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
		$ssoSetting = new SSOSetting();
		$ssoSetting->id = $library->ssoSettingId;
		if($ssoSetting->find(true)) {
			if($ssoSetting->ssoAuthOnly) {
				return true;
			}
		}
		return false;
	}

	static function has2FAEnabledForPType() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false  && UserAccount::$ssoAuthOnly === false) {
			return UserAccount::$primaryUserObjectFromDB->get2FAStatusForPType();
		}
		return false;
	}

	static function isRequired2FA() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false && UserAccount::$ssoAuthOnly === false) {
			return UserAccount::$primaryUserObjectFromDB->is2FARequired();
		}
		return false;
	}

	static function has2FAEnabled() {
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false && UserAccount::$ssoAuthOnly === false) {
			return UserAccount::$primaryUserObjectFromDB->get2FAStatus();
		}
		return false;
	}


	/**
	 * Look up in ILS for a user that has never logged into Aspen before, based on the patron's barcode.
	 *
	 * @param $patronBarcode
	 * @param $patronUsername
	 *
	 * @return false|User
	 */
	public static function findNewUser($patronBarcode, $patronUsername) {
		require_once ROOT_DIR . '/CatalogFactory.php';
		$driversToTest = self::getAccountProfiles();
		foreach ($driversToTest as $driverData) {
			$catalogConnectionInstance = CatalogFactory::getCatalogConnectionInstance($driverData['driver'], $driverData['accountProfile']);
			if ($catalogConnectionInstance != null && !is_null($catalogConnectionInstance->driver) && method_exists($catalogConnectionInstance->driver, 'findNewUser')) {
				$tmpUser = $catalogConnectionInstance->driver->findNewUser($patronBarcode, $patronUsername);
				if (!empty($tmpUser) && !($tmpUser instanceof AspenError)) {
					return $tmpUser;
				}
			}
		}
		return false;
	}

	/**
	 * Look up in Aspen for a user based on the given key and value.
	 *
	 * @param string $key The column in the User table to search
	 * @param string $value Value needed to match the given key to find the user
	 *
	 * @return false|User
	 */
	public static function findNewAspenUser(string $key, string $value) {
		$newUser = new User();
		$newUser->$key = $value;
		if($newUser->find(true)) {
			return $newUser;
		}
		return false;
	}

	/**
	 * Used to determine if the active user is a staff member (has Aspen privileges or is marked as staff in the PType).
	 * @return bool
	 */
	public static function isStaff() {
		if (UserAccount::isLoggedIn()) {
			if (count(UserAccount::getActiveRoles()) > 0) {
				return true;
			} else {
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$pType = new PType();
				$pType->pType = UserAccount::getUserPType();
				if ($pType->find(true)) {
					return $pType->isStaff;
				}
			}
		}
		return false;
	}

	/**
	 * Used to determine if the active user is logged in via single sign-on.
	 * @return bool
	 */
	public static function isLoggedInViaSSO() {
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if($user->isLoggedInViaSSO) {
				return true;
			}
		}
		return false;
	}
}