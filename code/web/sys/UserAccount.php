<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/sys/Authentication/AuthenticationFactory.php';

class UserAccount {
	private static $isLoggedIn = null;
	private static $primaryUserData = null;
	/** @var User|false  */
	private static $primaryUserObjectFromDB = null;
	/** @var User|false $guidingUserObjectFromDB */
	private static $guidingUserObjectFromDB = null;
	private static $userRoles = null;
	/**
     * 
	 * Checks whether the user is logged in.
	 *
	 * When logged in we store information the id of the active user within the session.
	 * The actual user is stored within memcache
	 *
	 * @return bool|User
	 */
	public static function isLoggedIn() {
		if (UserAccount::$isLoggedIn == null) {
			if (isset($_SESSION['activeUserId'])) {
				UserAccount::$isLoggedIn = true;
			} else {
				UserAccount::$isLoggedIn = false;
				//Need to check cas just in case the user logged in from another site
				//if ($action != 'AJAX' && $action != 'DjatokaResolver' && $action != 'Logout' && $module != 'MyAccount' && $module != 'API' && !isset($_REQUEST['username'])){
				//If the library uses CAS/SSO we may already be logged in even though they never logged in within Pika
				global $library;
				if ($library && strlen($library->casHost) > 0) {
					$checkCAS = false;
					$curTime = time();
					if (!isset($_SESSION['lastCASCheck'])){
						$checkCAS = true;
					}elseif ($curTime - $_SESSION['lastCASCheck'] > 10){
						$checkCAS = true;
					}
					global $action;
					global $module;
					if ($checkCAS && $action != 'AJAX' && $action != 'DjatokaResolver' && $action != 'Logout' && $module != 'MyAccount' && $module != 'API' && !isset($_REQUEST['username'])) {
						//Check CAS first
						require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
						global $logger;
						$casAuthentication = new CASAuthentication(null);
						$casUsername = $casAuthentication->validateAccount(null, null, null, false);
						$_SESSION['lastCASCheck'] = time();
						$logger->log("Checked CAS Authentication from UserAccount::isLoggedIn result was $casUsername", PEAR_LOG_DEBUG);
						if ($casUsername == false || PEAR_Singleton::isError($casUsername)) {
							//The user could not be authenticated in CAS
							UserAccount::$isLoggedIn = false;

						} else {
							$logger->log("We got a valid user from CAS, getting the user from the database", PEAR_LOG_DEBUG);
							//We have a valid user via CAS, need to do a login to Pika
							$_REQUEST['casLogin'] = true;
							UserAccount::$isLoggedIn = true;
							//Set the active user id for the user
							$user = new User();
							//TODO this may need to change if anyone but Fort Lewis ever does CAS authentication
							$user->cat_password = $casUsername;
							if ($user->find(true)){
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

	public static function getActiveUserId() {
		if (isset($_SESSION['activeUserId'])) {
			return $_SESSION['activeUserId'];
		}else{
			return false;
		}
	}

	public static function userHasRole($roleName) {
		$userRoles = UserAccount::getActiveRoles();
		return array_key_exists($roleName, $userRoles);
	}

	public static function getActiveRoles(){
		if (UserAccount::$userRoles == null){
			if (UserAccount::isLoggedIn()){
				UserAccount::$userRoles = array();

				//Roles for the user
				require_once ROOT_DIR . '/sys/Administration/Role.php';
				$role = new Role();
				$canUseTestRoles = false;
				$role->query("SELECT * FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . UserAccount::getActiveUserId() . " ORDER BY name");
				while ($role->fetch()){
					UserAccount::$userRoles[$role->name] = $role->name;
					if ($role->name == 'userAdmin'){
						$canUseTestRoles = true;
					}
				}

				//Test roles if we are doing overrides
				$testRole = '';
				if (isset($_REQUEST['test_role'])){
					$testRole = $_REQUEST['test_role'];
				}elseif (isset($_COOKIE['test_role'])){
					$testRole = $_COOKIE['test_role'];
				}
				if ($canUseTestRoles && $testRole != ''){
					if (is_array($testRole)){
						$testRoles = $testRole;
					}else{
						$testRoles = array($testRole);
					}
					//Ignore the standard roles for the user
					UserAccount::$userRoles = array();
					foreach ($testRoles as $tmpRole){
						$role = new Role();
						if (is_numeric($tmpRole)){
							$role->roleId = $tmpRole;
						}else{
							$role->name = $tmpRole;
						}
						$found = $role->find(true);
						if ($found == true){
							UserAccount::$userRoles[$role->name] = $role->name;
						}
					}
				}

				//TODO: Figure out roles for masquerade mode see User.php line 251

			}else{
				UserAccount::$userRoles = array();
			}
		}
		return UserAccount::$userRoles;
	}

	private static function loadUserObjectFromDatabase(){
		if (UserAccount::$primaryUserObjectFromDB == null){
			$activeUserId = UserAccount::getActiveUserId();
			if ($activeUserId){
				$user = new User();
				$user->id = $activeUserId;
				if ($user->find(true)){
					UserAccount::$primaryUserObjectFromDB = $user;
				}else{
					UserAccount::$primaryUserObjectFromDB = false;
				}
			}else{
				UserAccount::$primaryUserObjectFromDB = false;
			}
		}
	}

	public static function getActiveUserObj(){
		UserAccount::loadUserObjectFromDatabase();
		return UserAccount::$primaryUserObjectFromDB;
	}

	public static function getUserDisplayName(){
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false){
			if (strlen(UserAccount::$primaryUserObjectFromDB->displayName)){
				return UserAccount::$primaryUserObjectFromDB->displayName;
			}else{
				return UserAccount::$primaryUserObjectFromDB->firstname . ' ' . UserAccount::$primaryUserObjectFromDB->lastname;;
			}

		}
		return '';
	}

	public static function getUserPType(){
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false){
			return UserAccount::$primaryUserObjectFromDB->patronType;
		}
		return 'logged out';
	}

	public static function getDisableCoverArt(){
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false){
			return UserAccount::$primaryUserObjectFromDB->disableCoverArt;
		}
		return 'logged out';
	}

	public static function hasLinkedUsers(){
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false){
			return count(UserAccount::$primaryUserObjectFromDB->getLinkedUserObjects()) > 0;
		}
		return 'false';
	}

	public static function getUserHomeLocationId(){
		UserAccount::loadUserObjectFromDatabase();
		if (UserAccount::$primaryUserObjectFromDB != false){
			return UserAccount::$primaryUserObjectFromDB->homeLocationId;
		}
		return -1;
	}

	public static function isUserMasquerading(){
		return !empty($_SESSION['guidingUserId']);
	}

	public static function getGuidingUserObject(){
		if (UserAccount::$guidingUserObjectFromDB == null){
			if (UserAccount::isUserMasquerading()){
				$activeUserId = $_SESSION['guidingUserId'];
				if ($activeUserId){
					$user = new User();
					$user->id = $activeUserId;
					if ($user->find(true)){
						UserAccount::$guidingUserObjectFromDB = $user;
					}else{
						UserAccount::$guidingUserObjectFromDB = false;
					}
				}else{
					UserAccount::$guidingUserObjectFromDB = false;
				}
			}else{
				UserAccount::$guidingUserObjectFromDB = false;
			}
		}
		return UserAccount::$guidingUserObjectFromDB;
	}

	/**
	 * @return bool|null|User
	 */
	public static function getLoggedInUser(){
		if (UserAccount::$isLoggedIn != null){
			if (UserAccount::$isLoggedIn){
				if (!is_null(UserAccount::$primaryUserData)) {
					return UserAccount::$primaryUserData;
				}
			}else{
				return false;
			}
		}
		global $action;
		global $module;
		global $logger;

		$userData = false;
		if (isset($_SESSION['activeUserId'])) {
			$activeUserId = $_SESSION['activeUserId'];
			/** @var Memcache $memCache */
			global $memCache;
			global $serverName;

			/** @var User $userData */
			$userData = $memCache->get("user_{$serverName}_{$activeUserId}");
			if ($userData === false || isset($_REQUEST['reload'])){

				//Load the user from the database
				$userData = new User();

				$userData->id = $activeUserId;
				if ($userData->find(true)){
					$logger->log("Loading user {$userData->cat_username}, {$userData->cat_password} because we didn't have data in memcache", PEAR_LOG_DEBUG);
					$userData = UserAccount::validateAccount($userData->cat_username, $userData->cat_password, $userData->source);
					if ($userData == false) {
					    echo("Could not validate your account.  The underlying ILS may be inaccessible");
					    die();
                    }
					self::updateSession($userData);
				}
			}else{
				$logger->log("Found cached user {$userData->id}", PEAR_LOG_DEBUG);
			}
			UserAccount::$isLoggedIn = true;

			$masqueradeMode = UserAccount::isUserMasquerading();
			if ($masqueradeMode) {
				global $guidingUser;
				$guidingUser = $memCache->get("user_{$serverName}_{$_SESSION['guidingUserId']}"); //TODO: check if this ever works
				if ($guidingUser === false || isset($_REQUEST['reload'])){
					$guidingUser = new User();
					$guidingUser->get($_SESSION['guidingUserId']);
					if (!$guidingUser) {
						global $logger;
						$logger->log('Invalid Guiding User ID in session variable: '. $_SESSION['guidingUserId'], PEAR_LOG_ERR);
						$masqueradeMode = false;
						unset($_SESSION['guidingUserId']); // session_start(); session_commit(); probably needed for this to take effect, but might have other side effects
					}
				}
			}

			//Check to see if the patron is already logged in within CAS as long as we aren't on a page that is likely to be a login page
		}elseif ($action != 'AJAX' && $action != 'DjatokaResolver' && $action != 'Logout' && $module != 'MyAccount' && $module != 'API' && !isset($_REQUEST['username'])){
			//If the library uses CAS/SSO we may already be logged in even though they never logged in within Pika
			global $library;
			if (strlen($library->casHost) > 0){
				//Check CAS first
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				global $logger;
				$logger->log("Checking CAS Authentication from UserAccount::getLoggedInUser", PEAR_LOG_DEBUG);
				$casUsername = $casAuthentication->validateAccount(null, null, null, false);
				if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
					//The user could not be authenticated in CAS
					UserAccount::$isLoggedIn = false;
					return false;
				}else{
					//We have a valid user via CAS, need to do a login to Pika
					$_REQUEST['casLogin'] = true;
					$userData = UserAccount::login();
					UserAccount::$isLoggedIn = true;
				}
			}
		}
		if (UserAccount::$isLoggedIn){
			UserAccount::$primaryUserData = $userData;
			global $interface;
			if ($interface){
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


		if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] === "true" || $_REQUEST['rememberMe'] === "on")){
			$_SESSION['rememberMe'] = true;
		}else{
			$_SESSION['rememberMe'] = false;
		}

		// If the user browser has the showCovers settings stored, set the Session variable
		// Used for showing or hiding covers on MyAccount Pages
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			$_SESSION['showCovers'] = $showCovers;
		}

		session_commit();
	}

	/**
	 * Try to log in the user using current query parameters
	 * return User object on success, PEAR error on failure.
	 *
	 * @return PEAR_Error|User
	 * @throws UnknownAuthenticationMethodException
	 */
	public static function login() {
		global $logger;

		$validUsers = array();

		$validatedViaSSO = false;
		if (isset($_REQUEST['casLogin'])){
			$logger->log("Logging the user in via CAS", PEAR_LOG_INFO);
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$casUsername = $casAuthentication->authenticate(false);
			if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
				//The user could not be authenticated in CAS
				$logger->log("The user could not be logged in", PEAR_LOG_INFO);
				return new PEAR_Error('Could not authenticate in sign on service');
			}else{
				$logger->log("User logged in OK CAS Username $casUsername", PEAR_LOG_INFO);
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$_REQUEST['username'] = $casUsername;
				$_REQUEST['password'] = $casUsername;
				$validatedViaSSO = true;
			}
		}

		/** @var User $primaryUser */
		$primaryUser = null;
		$lastError = null;
		$driversToTest = self::loadAccountProfiles();

		//Test each driver in turn.  We do test all of them in case an account is valid in
		//more than one system
		foreach ($driversToTest as $driverName => $driverData){
			// Perform authentication:
			$authN = AuthenticationFactory::initAuthentication($driverData['authenticationMethod'], $driverData);
			$tempUser = $authN->authenticate($validatedViaSSO);

			// If we authenticated, store the user in the session:
			if (!PEAR_Singleton::isError($tempUser)) {
				if ($validatedViaSSO){
					$_SESSION['loggedInViaCAS'] = true;
				}
				global $library;
				if (isset($library) && $library->preventExpiredCardLogin && $tempUser->expired) {
					// Create error
					$cardExpired = new PEAR_Error('expired_library_card');
					return $cardExpired;
				}

				/** @var Memcache $memCache */
				global $memCache;
				global $serverName;
				global $configArray;
				$memCache->set("user_{$serverName}_{$tempUser->id}", $tempUser, 0, $configArray['Caching']['user']);
				$logger->log("Cached user {$tempUser->id}", PEAR_LOG_DEBUG);

				$validUsers[] = $tempUser;
				if ($primaryUser == null){
					$primaryUser = $tempUser;
					self::updateSession($primaryUser);
				}else{
					//We have more than one account with these credentials, automatically link them
					$primaryUser->addLinkedUser($tempUser);
				}
			}else{
				$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : 'No username provided';
				$logger->log("Error authenticating patron $username for driver {$driverName}\r\n", PEAR_LOG_ERR);
				$lastError = $tempUser;
				$logger->log($lastError->toString(), PEAR_LOG_ERR);
			}
		}

		// Send back the user object (which may be a PEAR error):
		if ($primaryUser){
			UserAccount::$isLoggedIn = true;
			UserAccount::$primaryUserData = $primaryUser;
			return $primaryUser;
		}else{
			return $lastError;
		}
	}

	private static $validatedAccounts = array();

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
	public static function validateAccount($username, $password, $accountSource = null, $parentAccount = null){
		if (array_key_exists($username . $password, UserAccount::$validatedAccounts)){
			return UserAccount::$validatedAccounts[$username . $password];
		}
		// Perform authentication:
		//Test all valid authentication methods and see which (if any) result in a valid login.
		$driversToTest = self::loadAccountProfiles();

		global $library;
		global $logger;
		$validatedViaSSO = false;
		if (strlen($library->casHost) > 0 && $username == null && $password == null){
			//Check CAS first
			require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
			$casAuthentication = new CASAuthentication(null);
			$logger->log("Checking CAS Authentication from UserAccount::validateAccount", PEAR_LOG_DEBUG);
			$casUsername = $casAuthentication->validateAccount(null, null, $parentAccount, false);
			if ($casUsername == false || PEAR_Singleton::isError($casUsername)){
				//The user could not be authenticated in CAS
				$logger->log("User could not be authenticated in CAS", PEAR_LOG_DEBUG);
				UserAccount::$validatedAccounts[$username . $password] = false;
				return false;
			}else{
				$logger->log("User was authenticated in CAS", PEAR_LOG_DEBUG);
				//Set both username and password since authentication methods could use either.
				//Each authentication method will need to deal with the possibility that it gets a barcode for both user and password
				$username = $casUsername;
				$password = $casUsername;
				$validatedViaSSO = true;
			}
		}

		foreach ($driversToTest as $driverName => $additionalInfo){
			if ($accountSource == null || $accountSource == $additionalInfo['accountProfile']->name) {
				$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
				$validatedUser = $authN->validateAccount($username, $password, $parentAccount, $validatedViaSSO);
				if ($validatedUser && !PEAR_Singleton::isError($validatedUser)) {
					/** @var Memcache $memCache */
					global $memCache;
					global $serverName;
					global $configArray;
					$memCache->set("user_{$serverName}_{$validatedUser->id}", $validatedUser, 0, $configArray['Caching']['user']);
					$logger->log("Cached user {$validatedUser->id}", PEAR_LOG_DEBUG);
					if ($validatedViaSSO){
						$_SESSION['loggedInViaCAS'] = true;
					}
					UserAccount::$validatedAccounts[$username . $password] = $validatedUser;
					return $validatedUser;
				}
			}
		}

		UserAccount::$validatedAccounts[$username . $password] = false;
		return false;
	}

	/**
	 * Completely logout the user annihilating their entire session.
	 */
	public static function logout()
	{
		//global $logger;
		//$logger->log("Logging user out", PEAR_LOG_DEBUG);
		UserAccount::softLogout();
		session_regenerate_id(true);
		//$logger->log("New session id is $newId", PEAR_LOG_DEBUG);
	}

	/**
	 * Remove user info from the session so the user is not logged in, but
	 * preserve hold message and search information
	 */
	public static function softLogout(){
		if (isset($_SESSION['activeUserId'])){
			//global $logger;
			//$logger->log("Logging user {$_SESSION['activeUserId']} out", PEAR_LOG_DEBUG);
			if (isset($_SESSION['guidingUserId'])){
				// Shouldn't end up here while in Masquerade Mode, but if does happen end masquerading as well
				unset($_SESSION['guidingUserId']);
			}
			if (isset($_SESSION['loggedInViaCAS']) && $_SESSION['loggedInViaCAS']){
				require_once ROOT_DIR . '/sys/Authentication/CASAuthentication.php';
				$casAuthentication = new CASAuthentication(null);
				$casAuthentication->logout();
			}
			unset($_SESSION['activeUserId']);
			if (isset($_SESSION['lastCASCheck'])){
				unset($_SESSION['lastCASCheck']);
			}
			UserAccount::$isLoggedIn = false;
			UserAccount::$primaryUserData = null;
			UserAccount::$primaryUserObjectFromDB = null;
			UserAccount::$guidingUserObjectFromDB = null;
		}
	}

	/**
	 * @return array
	 */
	protected static function loadAccountProfiles() {
		/** @var Memcache $memCache */
		global $memCache;
		global $instanceName;
		global $configArray;
		$accountProfiles = $memCache->get('account_profiles_' . $instanceName);

		if ($accountProfiles == false || isset($_REQUEST['reload'])){
			$accountProfiles = array();

			//Load a list of authentication methods to test and see which (if any) result in a valid login.
			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->orderBy('weight', 'name');
			$accountProfile->find();
			while ($accountProfile->fetch()) {
				$additionalInfo = array(
					'driver' => $accountProfile->driver,
					'authenticationMethod' => $accountProfile->authenticationMethod,
					'accountProfile' => clone($accountProfile)
				);
				$accountProfiles[$accountProfile->name] = $additionalInfo;
			}
			if (count($accountProfiles) == 0) {
				global $configArray;
				//Create default information for historic login.  This will eventually be obsolete
				$accountProfile = new AccountProfile();
				$accountProfile->orderBy('weight', 'name');
				$accountProfile->driver = $configArray['Catalog']['driver'];
				if (isset($configArray['Catalog']['url'])){
					$accountProfile->vendorOpacUrl = $configArray['Catalog']['url'];
				}
				$accountProfile->authenticationMethod = 'ils';
				if ($configArray['Catalog']['barcodeProperty'] == 'cat_password'){
					$accountProfile->loginConfiguration = 'username_barcode';
				}else{
					$accountProfile->loginConfiguration = 'barcode_pin';
				}
				if (isset($configArray['OPAC']['patron_host'])){
					$accountProfile->patronApiUrl = $configArray['OPAC']['patron_host'];
				}
				$accountProfile->recordSource = 'ils';
				$accountProfile->name = 'ils';

				$additionalInfo = array(
					'driver' => $configArray['Catalog']['driver'],
					'authenticationMethod' => $configArray['Authentication']['method'],
					'accountProfile' => $accountProfile
				);
				$accountProfiles['ils'] = $additionalInfo;
			}
			$memCache->set('account_profiles_' . $instanceName, $accountProfiles, 0, $configArray['Caching']['account_profiles']);
			global $timer;
			$timer->logTime("Loaded Account Profiles");
		}
		return $accountProfiles;
	}


	/**
	 * Look up in ILS for a user that has never logged into Pika before, based on the patron's barcode.
	 *
	 * @param $patronBarcode
	 */
	public static function findNewUser($patronBarcode){
		$driversToTest = self::loadAccountProfiles();
		foreach ($driversToTest as $driverName => $driverData){
			$catalogConnectionInstance = CatalogFactory::getCatalogConnectionInstance($driverData['driver'], $driverData['accountProfile']);
			if (method_exists($catalogConnectionInstance->driver, 'findNewUser')) {
				$tmpUser = $catalogConnectionInstance->driver->findNewUser($patronBarcode);
				if (!empty($tmpUser) && !PEAR_Singleton::isError($tmpUser)) {
					return $tmpUser;
				}
			}
		}
		return false;
	}
}