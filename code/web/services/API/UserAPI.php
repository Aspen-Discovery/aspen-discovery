<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class UserAPI extends Action {
	/**
	 * Processes method to determine return type and calls the correct method.
	 * Should not be called directly.
	 *
	 * @see Action::launch()
	 * @access private
	 */
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$output = '';

		//Set Headers
		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'isLoggedIn',
					'logout',
					'login',
					'loginToLiDA',
					'resetExpiredPin',
					'checkoutItem',
					'placeHold',
					'renewItem',
					'renewAll',
					'viewOnlineItem',
					'changeHoldPickUpLocation',
					'getPatronProfile',
					'validateAccount',
					'getPatronHolds',
					'getPatronCheckedOutItems',
					'cancelHold',
					'activateHold',
					'freezeHold',
					'returnCheckout',
					'updateOverDriveEmail',
					'getValidPickupLocations',
					'getHiddenBrowseCategories',
					'getILSMessages',
					'dismissBrowseCategory',
					'showBrowseCategory',
					'getLinkedAccounts',
					'getViewers',
					'addAccountLink',
					'removeAccountLink',
					'saveLanguage',
					'initMasquerade',
					'endMasquerade',
					'saveNotificationPushToken',
					'deleteNotificationPushToken',
					'getNotificationPushToken',
					'submitVdxRequest',
					'cancelVdxRequest',
					'getNotificationPreference',
					'setNotificationPreference',
					'getNotificationPreferences',
					'updateBrowseCategoryStatus',
					'removeViewerLink',
					'getPatronReadingHistory',
					'updatePatronReadingHistory',
					'optIntoReadingHistory',
					'optOutOfReadingHistory',
					'deleteAllFromReadingHistory',
					'deleteSelectedFromReadingHistory',
					'getReadingHistorySortOptions',
					'confirmHold',
					'updateNotificationOnboardingStatus'
				])) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('UserAPI', $method);
					$output = json_encode(['result' => $this->$method()]);
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('UserAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			if ($method != 'getUserForApiCall' && method_exists($this, $method)) {
				$result = [
					'result' => $this->$method(),
				];
				$output = json_encode($result);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('UserAPI', $method);
			} else {
				$output = json_encode(['error' => 'invalid_method']);
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	/**
	 *
	 * Returns whether a user is currently logged in based on session information.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Returns:
	 * <code>
	 * {result:[true|false]}
	 * </code>
	 *
	 * Sample call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=isLoggedIn
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 */
	function isLoggedIn(): bool {
		global $logger;
		$logger->log("UserAPI/isLoggedIn session: " . session_id(), Logger::LOG_DEBUG);
		return UserAccount::isLoggedIn();
	}

	/**
	 * Logs in the user and sets a cookie indicating that the user is logged in.
	 * Must be called by POSTing data to the API.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Sample call:
	 * <code>
	 * https://aspenurl/API/UserAPI
	 * Post variables:
	 *   method=login
	 *   username=23025003575917
	 *   password=7604
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 */
	function login(): array {
		global $logger;
		$logger->log("Starting UserAPI/login session: " . session_id(), Logger::LOG_DEBUG);
		//Login the user.  Must be called via Post parameters.
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$user = UserAccount::getLoggedInUser();
			if ($user && !($user instanceof AspenError)) {
				$logger->log("User is already logged in", Logger::LOG_DEBUG);
				return [
					'success' => true,
					'name' => ucwords($user->firstname . ' ' . $user->lastname),
					'session' => session_id(),
				];
			} else {
				try {
					$user = UserAccount::login();
					if ($user && !($user instanceof AspenError)) {
						$logger->log("User was logged in successfully session: " . session_id(), Logger::LOG_DEBUG);
						return [
							'success' => true,
							'name' => ucwords($user->firstname . ' ' . $user->lastname),
							'session' => session_id(),
						];
					} else {
						$logger->log("Incorrect login parameters", Logger::LOG_DEBUG);
						return ['success' => false];
					}
				} catch (UnknownAuthenticationMethodException $e) {
					$logger->log("Error logging user in $e", Logger::LOG_DEBUG);
					return ['success' => false];
				}
			}
		} else {
			return [
				'success' => false,
				'message' => 'This method must be called via POST.',
			];
		}
	}

	/**
	 * Logs the user out of the system and clears cookies indicating that the user is logged in.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Sample call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=logout
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 */
	function logout(): bool {
		global $logger;
		$logger->log("UserAPI/logout session: " . session_id(), Logger::LOG_DEBUG);
		UserAccount::logout();
		return true;
	}

	/**
	 * Validates an account based on the username and PIN provided, while returning errors such as expired PINs.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user.
	 * </ul>
	 *
	 * @noinspection PhpUnused
	 **/
	function loginToLiDA(): array {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();
		$accountSource = null;
		$parentAccount = null;
		$validatedViaSSO = false;

		require_once ROOT_DIR . '/CatalogFactory.php';
		$driversToTest = UserAccount::getAccountProfiles();

		foreach ($driversToTest as $driverName => $additionalInfo) {
			if ($accountSource == null || $accountSource == $additionalInfo['accountProfile']->name) {
				try {
					$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
				} catch (UnknownAuthenticationMethodException $e) {
					return [
						'success' => false,
						'message' => 'Unknown authentication method',
						'session' => false,
					];
				}
				$validatedUser = $authN->validateAccount($username, $password, $parentAccount, $validatedViaSSO);
				if ($validatedUser && !($validatedUser instanceof AspenError)) {
					return [
						'success' => true,
						'message' => 'User is valid',
						'session' => session_id(),
					];
				} else {
					$invalidUser = (array) $validatedUser;
					if(isset($invalidUser['message'])) {
						return [
							'success' => false,
							'id' => $invalidUser['id'] ?? null,
							'message' => $invalidUser['message'],
							'resetToken' => $invalidUser['resetToken'] ?? null,
							'userId' => $invalidUser['userId'] ?? null,
							'session' => false,
						];
					}
				}
			}
		}
		return [
			'success' => false,
			'message' => 'Unknown error logging in',
		];
	}

	/**
	 * Allows a user to reset an expired PIN.
	 *
	 * Parameters (POST):
	 * <ul>
	 * <li>token - The reset token provided at authentication to validate the request.</li>
	 * <li>pin1 - The PIN for the user.
	 * <li>pin2 - The PIN for the user (used to validate that they are the same).
	 * </ul>
	 *
	 * @noinspection PhpUnused
	 **/
	function resetExpiredPin() {
		$tokenValid = false;
		$result = [
			'success' => false,
			'message' => ''
		];
		if(isset($_POST['token'])) {
			require_once ROOT_DIR . '/sys/Account/PinResetToken.php';
			$pinResetToken = new PinResetToken();
			$pinResetToken->token = $_POST['token'];
			if ($pinResetToken->find(true)) {
				//Token should only be valid for 1 hour.
				if ((time() - $pinResetToken->dateIssued) < 60 * 60) {
					$tokenValid = true;
				} else {
					$result['message'] = translate([
						'text' => 'Token has expired.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'Token not found.',
					'isPublicFacing' => true,
				]);
			}

			$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
			if ((isset($_POST['pin1']) && isset($_POST['pin2'])) && $tokenValid) {
				$userToResetPinFor = new User();
				$userToResetPinFor->id = $pinResetToken->userId;
				if ($userToResetPinFor->find(true)) {
					$pin1 = $_POST['pin1'];
					$pin2 = $_POST['pin2'];
					if ($pin1 != $pin2) {
						$result['message'] = translate([
							'text' => 'The provided PINs do not match.',
							'isPublicFacing' => true,
						]);
					} else {
						$resetResults = $catalog->driver->updatePin($userToResetPinFor, $userToResetPinFor->getPasswordOrPin(), $pin1);
						if (!$resetResults['success']) {
							$result['message'] = $resetResults['message'];
						} else {
							$result['success'] = true;
							$result['message'] = translate([
								'text' => 'PIN reset successfully.',
								'isPublicFacing' => true,
							]);
						}
					}
				}
			}
		} else {
			$result['message'] = translate([
				'text' => 'No PIN reset token provided.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/**
	 * Validate whether or not an account is valid based on the barcode and pin number provided.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user.
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - false if the username or password could not be found, or the following user information if the account is valid.</li>
	 * <li>id - The id of the user within VuFind</li>
	 * <li>username, cat_username - The patron's library card number</li>
	 * <li>password, cat_password - The patron's PIN number</li>
	 * <li>firstname - The first name of the patron in the ILS</li>
	 * <li>lastname - The last name of the patron in the ILS</li>
	 * <li>email - The patron's email address if set within Horizon.</li>
	 * <li>homeLocationId - the id of the patron's home library within Aspen.</li>
	 * <li>MyLocation1Id, myLocation2Id - not currently used</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=validateAccount&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":{
	 *     "id":"5",
	 *     "username":"23025003575917",
	 *     "password":"7604",
	 *     "firstname":"OS test 1",
	 *     "lastname":"",
	 *     "email":"email",
	 *     "cat_username":"23025003575917",
	 *     "cat_password":"7604",
	 *     "homeLocationId":null,
	 *     "myLocation1Id":null,
	 *     "myLocation2Id":null
	 *     }
	 *   }
	 * }
	 * </code>
	 *
	 * Sample Response failed login:
	 * <code>
	 * {"result":{"success":false}}
	 * </code>
	 *
	 */
	function validateAccount(): array {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();

		$user = UserAccount::validateAccount($username, $password);
		if ($user != null) {
			//TODO This needs to be updated to just export public information
			//get rid of data object fields before returning the result
			unset($user->__table);
			unset($user->created);
			unset($user->_DB_DataObject_version);
			unset($user->_database_dsn);
			unset($user->_database_dsn_md5);
			unset($user->_database);
			unset($user->_query);
			unset($user->_DB_resultid);
			unset($user->_resultFields);
			unset($user->_link_loaded);
			unset($user->_join);
			unset($user->_lastError);

			$result = new stdClass();
			$properties = get_object_vars($user);
			foreach ($properties as $name => $value) {
				if ($name[0] != '_') {
					$result->$name = $value;
				} elseif ($name[0] == '_' && strlen($name) > 1 && $name[1] != '_') {
					if ($name != '_data') {
						$result->$name = $value;
					}
				}
			}
			$result->homeLocationCode = $user->getHomeLocationCode();

			return ['success' => $result];
		} else {
			return ['success' => false];
		}
	}

	/**
	 * Load patron profile information for a user based on username and password.
	 * Includes information about print titles and eContent titles that the user has checked out.
	 * Does not include information about OverDrive titles since tat
	 *
	 * Usage:
	 * <code>
	 * {siteUrl}/API/UserAPI?method=getPatronProfile&username=patronBarcode&password=pin
	 * </code>
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid, false if the username or password were incorrect</li>
	 * <li>message - a reason why the method failed if success is false</li>
	 * <li>profile - profile information including name, address, email, number of holds, number of checked out items, fines.</li>
	 * <li>firstname - The first name of the patron in the ILS</li>
	 * <li>lastname - The last name of the patron in the ILS</li>
	 * <li>fullname - The combined first and last name for the patron in the ILS</li>
	 * <li>address1 - The street information for the patron</li>
	 * <li>city - The city where the patron lives</li>
	 * <li>state - The state where the patron lives</li>
	 * <li>zip - The zip code for the patron</li>
	 * <li>phone - The phone number for the patron</li>
	 * <li>email - The email for the patron</li>
	 * <li>homeLocationId - The id of the patron's home branch within VuFind</li>
	 * <li>homeLocationName - The full name of the patron's home branch</li>
	 * <li>expires - The expiration date of the patron's library card</li>
	 * <li>fines - the amount of fines on the patron's account formatted for display</li>
	 * <li>finesVal - the amount of  fines on the patron's account without formatting</li>
	 * <li>numHolds - The number of holds the patron currently has</li>
	 * <li>numHoldsAvailable - The number of holds the patron currently has that are available</li>
	 * <li>numHoldsRequested - The number of holds the patron currently has that are not available</li>
	 * <li>numCheckedOut - The number of items the patron currently has checked out.</li>
	 * <li>bypassAutoLogout - 1 if the user has chosen to bypass te automatic logout script or 0 if they have not.</li>
	 * <li>numEContentCheckedOut - The number of eContent items that the user currently has checked out. </li>
	 * <li>numEContentAvailableHolds - The number of available eContent holds for the user that can be checked out. </li>
	 * <li>numEContentUnavailableHolds - The number of unavailable eContent holds for the user.</li>
	 * <li>numEContentWishList - The number of eContent titles the user has added to their wishlist.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronProfile&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response failed login:
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "message":"Login unsuccessful"
	 * }}
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * { "result" : { "profile" : {
	 *   "address1" : "P O BOX 283",
	 *   "bypassAutoLogout" : "0",
	 *   "city" : "LOUVIERS",
	 *   "displayName" : "",
	 *   "email" : "test@comcast.net",
	 *   "expires" : "02/03/2039",
	 *   "fines" : 0,
	 *   "finesval" : "",
	 *   "firstname" : "",
	 *   "fullname" : "POS test 1",
	 *   "homeLocationId" : "3",
	 *   "homeLocationName" : "Philip S. Miller",
	 *   "lastname" : "POS test 1",
	 *    "numCheckedOut" : 0,
	 *   "numEContentAvailableHolds" : 0,
	 *   "numEContentCheckedOut" : 0,
	 *   "numEContentUnavailableHolds" : 0,
	 *   "numEContentWishList" : 0,
	 *   "numHolds" : 0,
	 *   "numHoldsAvailable" : 0,
	 *   "numHoldsRequested" : 0,
	 *   "phone" : "303-555-5555",
	 *   "state" : "CO",
	 *   "zip" : "80131"
	 * },
	 * "success" : true
	 * } }
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronProfile(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			//Remove a bunch of junk from the user data
			unset($user->query);
			$userData = new stdClass();
			foreach ($user as $key => $value) {
				if ($key[0] == '_') {
					if ($key[1] == '_') {
						unset($user->$key);
					} else {
						if (!is_object($value) && !is_array($value)) {
							$shortKey = substr($key, 1);
							$userData->$shortKey = $value;
						}
					}
				} else {
					$userData->$key = $value;
				}
			}

			$linkedUsers = $_REQUEST['linkedUsers'] ?? false;
			$reload = $_REQUEST['reload'] ?? false;

			$numCheckedOut = 0;
			$numOverdue = 0;
			$numHolds = 0;
			$numHoldsAvailable = 0;
			if(!$reload) {
				// set reload parameter to get ILS account summary if it's not already set
				$_REQUEST['reload'] = true;
			}

			$accountSummary = $user->getAccountSummary();
			$userData->numCheckedOutIls = (int)$accountSummary->numCheckedOut;
			$userData->numHoldsIls = (int)$accountSummary->getNumHolds();
			$userData->numHoldsAvailableIls = (int)($accountSummary->numAvailableHolds == null ? 0 : $accountSummary->numAvailableHolds);
			$userData->numHoldsRequestedIls = (int)($accountSummary->numUnavailableHolds == null ? 0 : $accountSummary->numUnavailableHolds);
			$userData->numOverdue = (int)$accountSummary->numOverdue;
			$userData->finesVal = (float)$accountSummary->totalFines;
			$numCheckedOut += $userData->numCheckedOutIls;
			$numHolds += $userData->numHoldsIls;
			$numHoldsAvailable += $userData->numHoldsAvailableIls;
			$numOverdue += $userData->numOverdue;

			$userData->expires = $accountSummary->expiresOn();
			$userData->expireClose = $accountSummary->isExpirationClose();
			$userData->expired = $accountSummary->isExpired();

			$userData->readingHistoryEnabled = (int)$user->isReadingHistoryEnabled();
			$accountSummary->setReadingHistory($user->getReadingHistorySize());
			$userData->numReadingHistory = $accountSummary->getReadingHistory();

			$userData->numLinkedAccounts = 0;
			$userData->numLinkedUsers = 0;
			$userData->numLinkedViewers = 0;

			if ($linkedUsers && $user->getLinkedUsers() != null) {
				$linkedAccounts = $user->getLinkedUsers();
				foreach ($linkedAccounts as $linkedUser) {
					$linkedUserSummary = $linkedUser->getCatalogDriver()->getAccountSummary($linkedUser);
					$userData->finesVal += (int)$linkedUserSummary->totalFines;
					$userData->numHoldsIls = (int)$linkedUserSummary->getNumHolds();
					$userData->numCheckedOutIls += (int)$linkedUserSummary->numCheckedOut;
					$userData->numOverdue += (int)$linkedUserSummary->numOverdue;
					$userData->numHoldsAvailableIls += (int)($linkedUserSummary->numAvailableHolds == null ? 0 : $linkedUserSummary->numAvailableHolds);
					$userData->numHoldsRequestedIls += (int)($linkedUserSummary->numUnavailableHolds == null ? 0 : $linkedUserSummary->numUnavailableHolds);
					$numCheckedOut += (int)$linkedUserSummary->numCheckedOut;
					$numHolds += (int)$linkedUserSummary->getNumHolds();
					$numHoldsAvailable += ($linkedUserSummary->numAvailableHolds == null ? 0 : $linkedUserSummary->numAvailableHolds);
					$numOverdue += (int)$linkedUserSummary->numOverdue;
				}
				$userData->numLinkedUsers = count($linkedAccounts);

				$linkedViewers = $user->getViewers();
				$userData->numLinkedViewers = count($linkedViewers);
			}

			$userData->numLinkedAccounts = $userData->numLinkedUsers + $userData->numLinkedViewers;

			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);
			$userData->fines = $currencyFormatter->formatCurrency($userData->finesVal, $currencyCode);

			if(!$reload) {
				// clear forced reload parameter
				$_REQUEST['reload'] = false;
			}

			//Add overdrive data
			$userData->isValidForOverdrive = false;
			if ($user->isValidForEContentSource('overdrive')) {
				$userData->isValidForOverdrive = true;
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$overDriveSummary = $driver->getAccountSummary($user);
				$userData->numCheckedOutOverDrive = (int)$overDriveSummary->numCheckedOut;
				$userData->numHoldsOverDrive = (int)$overDriveSummary->getNumHolds();
				$userData->numHoldsAvailableOverDrive = (int)$overDriveSummary->numAvailableHolds;
				$numCheckedOut += (int)$overDriveSummary->numCheckedOut;
				$numHolds += (int)$overDriveSummary->getNumHolds();
				$numHoldsAvailable += (int)$overDriveSummary->numAvailableHolds;

				if ($linkedUsers && $user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary_OverDrive = $driver->getAccountSummary($linkedUser);
						$userData->numCheckedOutOverDrive += (int)$linkedUserSummary_OverDrive->numCheckedOut;
						$userData->numHoldsOverDrive += (int)$linkedUserSummary_OverDrive->getNumHolds();
						$userData->numHoldsAvailableOverDrive += (int)$linkedUserSummary_OverDrive->numAvailableHolds;
						$numCheckedOut += (int)$linkedUserSummary_OverDrive->numCheckedOut;
						$numHolds += (int)$linkedUserSummary_OverDrive->getNumHolds();
						$numHoldsAvailable += (int)$linkedUserSummary_OverDrive->numAvailableHolds;
					}
				}

			}

			//Add hoopla data
			$userData->isValidForHoopla = false;
			if ($user->isValidForEContentSource('hoopla')) {
				$userData->isValidForHoopla = true;
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$hooplaSummary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_Hoopla = (int)$hooplaSummary->numCheckedOut;
				$numCheckedOut += (int)$hooplaSummary->numCheckedOut;

				if ($linkedUsers && $user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary_Hoopla = $driver->getAccountSummary($linkedUser);
						$userData->numCheckedOut_Hoopla += (int)$linkedUserSummary_Hoopla->numCheckedOut;
						$numCheckedOut += (int)$linkedUserSummary_Hoopla->numCheckedOut;
					}
				}
			}

			//Add cloudLibrary data
			$userData->isValidForCloudLibrary = false;
			if ($user->isValidForEContentSource('cloud_library')) {
				$userData->isValidForCloudLibrary = true;
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				$cloudLibrarySummary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_cloudLibrary = (int)$cloudLibrarySummary->numCheckedOut;
				$userData->numHolds_cloudLibrary = (int)$cloudLibrarySummary->getNumHolds();
				$userData->numHoldsAvailable_cloudLibrary = (int)$cloudLibrarySummary->numAvailableHolds;
				$numCheckedOut += (int)$cloudLibrarySummary->numCheckedOut;
				$numHolds += (int)$cloudLibrarySummary->getNumHolds();
				$numHoldsAvailable += (int)$cloudLibrarySummary->numAvailableHolds;

				if ($linkedUsers && $user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary_cloudLibrary = $driver->getAccountSummary($linkedUser);
						$userData->numCheckedOut_cloudLibrary += (int)$linkedUserSummary_cloudLibrary->numCheckedOut;
						$userData->numHolds_cloudLibrary += (int)$linkedUserSummary_cloudLibrary->getNumHolds();
						$userData->numHoldsAvailable_cloudLibrary += (int)$linkedUserSummary_cloudLibrary->numAvailableHolds;
						$numCheckedOut += (int)$linkedUserSummary_cloudLibrary->numCheckedOut;
						$numHolds += (int)$linkedUserSummary_cloudLibrary->getNumHolds();
						$numHoldsAvailable += (int)$linkedUserSummary_cloudLibrary->numAvailableHolds;
					}
				}
			}

			//Add axis360 data
			$userData->isValidForAxis360 = false;
			if ($user->isValidForEContentSource('axis360')) {
				$userData->isValidForAxis360 = true;
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				$axis360Summary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_axis360 = (int)$axis360Summary->numCheckedOut;
				$userData->numHolds_axis360 = (int)$axis360Summary->getNumHolds();
				$userData->numHoldsAvailable_axis360 = (int)$axis360Summary->numAvailableHolds;
				$numCheckedOut += (int)$axis360Summary->numCheckedOut;
				$numHolds += (int)$axis360Summary->getNumHolds();
				$numHoldsAvailable += (int)$axis360Summary->numAvailableHolds;

				if ($linkedUsers && $user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary_axis360 = $driver->getAccountSummary($linkedUser);
						$userData->numCheckedOut_axis360 += (int)$linkedUserSummary_axis360->numCheckedOut;
						$userData->numHolds_axis360 += (int)$linkedUserSummary_axis360->getNumHolds();
						$userData->numHoldsAvailable_axis360 += (int)$linkedUserSummary_axis360->numAvailableHolds;
						$numCheckedOut += (int)$linkedUserSummary_axis360->numCheckedOut;
						$numHolds += (int)$linkedUserSummary_axis360->getNumHolds();
						$numHoldsAvailable += (int)$linkedUserSummary_axis360->numAvailableHolds;
					}
				}
			}

			//Add Interlibrary Loan
			$userData->hasInterlibraryLoan = false;
			if ($user->hasInterlibraryLoan()) {
				$userData->hasInterlibraryLoan = true;
				require_once ROOT_DIR . '/Drivers/VdxDriver.php';
				$driver = new VdxDriver();
				$vdxSummary = $driver->getAccountSummary($user);
				$numHolds += (int)$vdxSummary->numUnavailableHolds;
			}


			$userData->numCheckedOut = $numCheckedOut;
			$userData->numHolds = $numHolds;
			$userData->numHoldsAvailable = $numHoldsAvailable;

			require_once ROOT_DIR . '/services/API/ListAPI.php';

			$numLists = 0;
			$numSavedSearches = 0;
			$numSavedSearchesNew = 0;

			// get list count
			$userLists = new ListAPI();
			$lists = $userLists->getUserLists();
			if ($lists['count']) {
				$numLists = $lists['count'];
			}

			// get saved search count
			$savedSearches = new ListAPI(true);
			$searches = $savedSearches->getSavedSearches($user->id);
			if ($searches['count']) {
				$numSavedSearches = $searches['count'];
			}

			if ($searches['countNewResults']) {
				$numSavedSearchesNew = $searches['countNewResults'];
			}

			$userData->numLists = $numLists;
			$userData->numSavedSearches = $numSavedSearches;
			$userData->numSavedSearchesNew = $numSavedSearchesNew;

			$userData->notification_preferences = $user->getNotificationPreferencesByUser();

			$promptForHoldNotifications = $user->getCatalogDriver()->isPromptForHoldNotifications();
			$userData->promptForHoldNotifications = $promptForHoldNotifications;
			if($promptForHoldNotifications) {
				$userData->holdNotificationInfo = $user->getCatalogDriver()->loadHoldNotificationInfo($user);
			}

			$userData->summaryFines = translate([
				'text' => 'Your accounts have %1% in fines',
				1 => $userData->fines,
				'isPublicFacing' => true,
			]);

			return [
				'success' => true,
				'profile' => $userData,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Returns messages for a patron from the ILS.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	function getILSMessages(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$messages = $user->getILSMessages();
			return [
				'success' => true,
				'messages' => $messages,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Get eContent and ILS holds for a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid, false if the username or password were incorrect</li>
	 * <li>message - a reason why the method failed if success is false</li>
	 * <li>holds - information about each hold including when it was placed, when it expires, and whether or not it is available for pickup.  Holds are broken into two sections: available and unavailable.  Available holds are ready for pickup.</li>
	 * <li>Id - the record/bib id of the title being held</li>
	 * <li>location - The location where the title will be picked up</li>
	 * <li>expire - the timestamp the hold will expire if it is unavailable or the date that it must be picked up if the hold is available</li>
	 * <li>create - the date the hold was originally placed</li>
	 * <li>createTime - the create information in number of days since January 1, 1970</li>
	 * <li>reactivate - The date the hold will be reactivated if the hold is suspended</li>
	 * <li>reactivateTime - the reactivate information in number of days since January 1, 1970</li>
	 * <li>available - whether or not the hold is available for pickup</li>
	 * <li>position - the patron's position in the hold queue</li>
	 * <li>frozen - whether or not the hold is frozen</li>
	 * <li>itemId - the barcode of the item that filled the hold if the hold has been filled.</li>
	 * <li>Status - a textual status of the item (Available, Suspended, Active, In Transit)</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronHolds&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * { "result" :
	 *   { "holds" :
	 *     { "unavailable" : [
	 *       { "author" : "Bernhardt, Gale, 1958-",
	 *            "available" : false,
	 *            "availableTime" : null,
	 *            "barcode" : "33025016545293",
	 *            "create" : "2011-12-20 00:00:00",
	 *            "createTime" : 15328,
	 *            "expire" : 15429,
	 *            "format" : "Book",
	 *            "format_category" : [ "Books" ],
	 *            "frozen" : false,
	 *            "id" : 868679,
	 *            "isbn" : [ "1931382921 (paper)",
	 *                "9781931382922"
	 *              ],
	 *            "itemId" : 1559061,
	 *            "location" : "Parker",
	 *            "position" : 1,
	 *            "reactivate" : "",
	 *            "reactivateTime" : null,
	 *            "sortTitle" : "training plans for multisport athletes",
	 *            "status" : "In Transit",
	 *            "title" : "Training plans for multisport athletes /",
	 *            "upc" : ""
	 *       } ]
	 *     },
	 *     { "available" : [
	 *       { "author" : "Hunter, Erin.",
	 *            "available" : true,
	 *            "availableTime" : null,
	 *            "barcode" : "33025025084185",
	 *            "create" : "2011-09-27 00:00:00",
	 *            "createTime" : 15244,
	 *            "expire" : 15429,
	 *            "format" : "Book",
	 *            "format_category" : [ "Books" ],
	 *            "frozen" : false,
	 *            "id" : 1012238,
	 *            "isbn" : [ "9780061555220",
	 *                "0061555223"
	 *              ],
	 *            "itemId" : 2216202,
	 *            "location" : "Parker",
	 *            "position" : 2,
	 *            "reactivate" : "",
	 *            "reactivateTime" : 15308,
	 *            "sortTitle" : "forgotten warrior",
	 *            "status" : "Available",
	 *            "title" : "The forgotten warrior /",
	 *            "upc" : ""
	 *          } ]
	 *     },
	 *     "success" : true
	 *  }
	 * }
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronHolds(): array {
		global $offlineMode;
		if ($offlineMode) {
			return [
				'success' => false,
				'message' => 'Circulation system is offline',
			];
		} else {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$unavailableSort = $_REQUEST['unavailableSort'] ?? 'sortTitle';
				$availableSort = $_REQUEST['availableSort'] ?? 'expire';
				$source = $_REQUEST['source'] ?? 'all';
				$linkedUsers = $_REQUEST['linkedUsers'] ?? false;
				$allHolds = $user->getHolds($linkedUsers, $unavailableSort, $availableSort, $source);
				$holdsToReturn = [
					'available' => [],
					'unavailable' => [],
				];
				/**
				 * @var string $key
				 * @var Hold $hold
				 */
				foreach ($allHolds['available'] as $key => $hold) {
					$holdsToReturn['available'][$key] = $hold->getArrayForAPIs();
					$holdsToReturn['available'][$key]['statusMessage'] = $holdsToReturn['available'][$key]['status'];
				}
				foreach ($allHolds['unavailable'] as $key => $hold) {
					$holdsToReturn['unavailable'][$key] = $hold->getArrayForAPIs();
					$holdsToReturn['unavailable'][$key]['statusMessage'] = $holdsToReturn['unavailable'][$key]['status'];
					if($holdsToReturn['unavailable'][$key]['frozen'] && $holdsToReturn['unavailable'][$key]['reactivateDate']) {
						$reactivateDate = gmdate('M d, Y', $holdsToReturn['unavailable'][$key]['reactivateDate']);
						$status = $holdsToReturn['unavailable'][$key]['status'];
						$holdsToReturn['unavailable'][$key]['statusMessage'] = translate(['text' => "$status until %1%", 1 => $reactivateDate, 'isPublicFacing' => true]);
					}
				}
				return [
					'success' => true,
					'sortMethods' => [
						'unavailableSort' => $unavailableSort,
						'availableSort' => $availableSort
					],
					'holds' => $holdsToReturn
				];
			} else {
				return [
					'success' => false,
					'message' => 'Login unsuccessful',
				];
			}
		}
	}

	/**
	 * Get a list of holds with details from OverDrive.
	 * Note: OverDrive can be very slow at times.  Proper precautions should be taken to ensure the calling application
	 * remains responsive.  VuFind does handle caching of OverDrive details so additional caching should not be needed.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronHoldsOverDrive&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holds":{
	 *     "available":[{
	 *       "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *       "imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/1523-1\/%7B2C32E00B-8838-4F2A-BED7-EAEF2E9249C8%7DImg200.jpg",
	 *       "title":"Danger in a Red Dress",
	 *       "subTitle":"The Fortune Hunter Series, Book 4",
	 *       "author":"Christina Dodd",
	 *       "recordId":"9604",
	 *       "notificationDate":1325921790,
	 *       "expirationDate":1326180990,
	 *       "formats":[
	 *         {"name":"Kindle Book",
	 *          "overDriveId":
	 *          "2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"420"
	 *         },
	 *         {"name":"Adobe EPUB eBook",
	 *          "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"410"
	 *         },
	 *         {"name":"Adobe PDF eBook",
	 *          "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"50"
	 *         }
	 *       ]
	 *     }],
	 *     "unavailable":[{
	 *       "overDriveId":"E750E1B6-2B11-42B5-89CB-153A286FB4A0",
	 *       "imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/0293-1\/%7BE750E1B6-2B11-42B5-89CB-153A286FB4A0%7DImg200.jpg",
	 *       "title":"Sunrise",
	 *       "subTitle":"Warriors: Power of Three Series, Book 6",
	 *       "author":"Erin Hunter",
	 *       "recordId":"7356",
	 *       "formatId":"410",
	 *       "holdQueuePosition":"1",
	 *       "holdQueueLength":"1"
	 *     }]
	 *   }
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronHoldsOverDrive(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$eContentHolds = $driver->getHolds($user);
			return [
				'success' => true,
				'holds' => $eContentHolds,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Get a list of items that are currently checked out to the user within OverDrive.
	 * Note: Aspen takes care of caching the checked out items page appropriately.  The calling application should not
	 * do additional caching.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronCheckedOutItemsOverDrive&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "items":[
	 *     {"imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/1138-1\/%7BA10890DB-DDEF-4BA5-BAEC-39AAF8A67D69%7DImg200.jpg",
	 *      "title":"An Object of Beauty",
	 *      "overDriveId":"A10890DB-DDEF-4BA5-BAEC-39AAF8A67D69",
	 *      "subTitle":"",
	 *      "format":"OverDrive WMA Audiobook ",
	 *      "downloadSize":"106251 kb",
	 *      "downloadLink":"http:\/\/ofs.contentreserve.com\/bin\/OFSGatewayModule.dll\/AnObjectOfBeauty9781607889410.odm?...",
	 *      "checkedOutOn":"Jan 05, 2012",
	 *      "expiresOn":"Jan 12, 2012",
	 *      "recordId":"14955"
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronCheckedOutItemsOverDrive(): array {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$eContentCheckedOutItems = $driver->getCheckouts($user);
			$items = [];
			foreach ($eContentCheckedOutItems as $checkedOutItem) {
				$items[] = $checkedOutItem->toArray();
			}
			return [
				'success' => true,
				'items' => $items,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Get a count of items in various lists within overdrive (holds, cart, wishlist, checked out).
	 *
	 * Usage:
	 * <code>
	 * {siteUrl}/API/UserAPI?method=getPatronOverDriveSummary&username=patronBarcode&password=pin
	 * </code>
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronOverDriveSummary&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "summary":{
	 *     "numAvailableHolds":1,
	 *     "numUnavailableHolds":4,
	 *     "numCheckedOut":7,
	 *     "numWishlistItems":9
	 *   }
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronOverDriveSummary(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$overDriveSummary = $driver->getAccountSummary($user);
			return [
				'success' => true,
				'summary' => $overDriveSummary->toArray(),
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Get fines from the ILS for a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronFines&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "fines":[
	 *     {"reason":"Privacy - Family permission",
	 *      "amount":"$0.00",
	 *      "message":"",
	 *      "date":"09\/27\/2005"
	 *     },
	 *     {"reason":"Charges Misc. Fees",
	 *      "amount":"$5.00",
	 *      "message":"",
	 *      "date":"04\/14\/2011"
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronFines(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$includeLinkedUsers = $_REQUEST['includeLinkedUsers'] ?? false;
			$fines = $user->getFines($includeLinkedUsers, true);
			$totalOwed = 0;
			foreach ($fines as &$fine) {
				if (isset($fine['amountOutstandingVal'])) {
					$totalOwed += $fine['amountOutstandingVal'];
				} elseif (isset($fine['amountVal'])) {
					$totalOwed += $fine['amountVal'];
				} elseif (isset($fine['amount'])) {
					$totalOwed += $fine['amount'];
				}
				if (array_key_exists('amount', $fine) && array_key_exists('amountOutstanding', $fine)) {
					$fine['amountOriginal'] = $fine['amount'];
					$fine['amount'] = $fine['amountOutstanding'];
				}
				if (array_key_exists('amountVal', $fine) && array_key_exists('amountOutstandingVal', $fine)) {
					$fine['amountOriginalVal'] = $fine['amountVal'];
					$fine['amountVal'] = $fine['amountOutstandingVal'];
				}
			}
			return [
				'success' => true,
				'fines' => $fines,
				'totalOwed' => $totalOwed,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Returns lending options for a patron from OverDrive.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	function getOverDriveLendingOptions(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$driver = new OverDriveDriver();
			$accountDetails = $driver->getOptions($user);
			return [
				'success' => true,
				'lendingOptions' => $accountDetails['lendingPeriods'],
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Get eContent and ILS records that are checked out to a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>includeEContent - Optional flag for whether or not to include checked out eContent. Set to false to only include print titles.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronCheckedOutItems&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "checkedOutItems":{
	 *     "33025021368319":{
	 *       "id":"966379",
	 *       "itemId":"33025021368319",
	 *       "dueDate":"01\/24\/2012",
	 *       "checkoutDate":"2011-12-27 00:00:00",
	 *       "barcode":"33025021368319",
	 *       "renewCount":"1",
	 *       "request":null,
	 *       "overdue":false,
	 *       "daysUntilDue":16,
	 *       "title":"Be iron fit : time-efficient training secrets for ultimate fitness \/",
	 *       "sortTitle":"be iron fit : time-efficient training secrets for ultimate fitness \/ time-efficient training secrets for ultimate fitness \/",
	 *       "author":"Fink, Don.",
	 *       "format":"Book",
	 *       "isbn":"9781599218571"
	 *       ,"upc":"",
	 *       "format_category":"Books",
	 *       "holdQueueLength":3
	 *     }
	 *   }
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronCheckedOutItems(): array {
		global $offlineMode;
		if ($offlineMode) {
			return [
				'success' => false,
				'message' => 'Circulation system is offline',
			];
		} else {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$source = $_REQUEST['source'] ?? 'all';
				$linkedUsers = $_REQUEST['linkedUsers'] ?? false;
				$allCheckedOut = $user->getCheckouts($linkedUsers, $source);
				$checkoutsList = [];
				foreach ($allCheckedOut as $checkoutObj) {
					$checkoutsList[] = $checkoutObj->getArrayForAPIs();
				}

				return [
					'success' => true,
					'checkedOutItems' => $checkoutsList,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Login unsuccessful',
				];
			}
		}
	}

	function checkoutItem(): array {
		$source = $_REQUEST['itemSource'] ?? null;
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			if ($source == 'overdrive') {
				return $this->checkoutOverDriveItem();
			} elseif ($source == 'hoopla') {
				return $this->checkoutHooplaItem();
			} elseif ($source == 'cloud_library') {
				return $this->checkoutCloudLibraryItem();
			} elseif ($source == 'axis360') {
				return $this->checkoutAxis360Item();
			} else {
				return [
					'success' => false,
					'message' => 'This source does not permit checkouts.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout(): array {
		$source = $_REQUEST['itemSource'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			if ($source == 'overdrive') {
				return $this->returnOverDriveCheckout();
			} elseif ($source == 'hoopla') {
				return $this->returnHooplaItem();
			} elseif ($source == 'cloud_library') {
				return $this->returnCloudLibraryItem();
			} elseif ($source == 'axis360') {
				return $this->returnAxis360Item();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function viewOnlineItem(): array {
		$source = $_REQUEST['itemSource'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {

			if ($source == 'overdrive') {
				if (isset($_REQUEST['isPreview']) && $_REQUEST['isPreview'] == true) {
					return $this->openOverDrivePreview();
				} else {
					return $this->openOverDriveItem();
				}
			} elseif ($source == 'hoopla') {
				return $this->openHooplaItem();
			} elseif ($source == 'cloud_library') {
				return $this->openCloudLibraryItem();
			} elseif ($source == 'axis360') {
				return $this->openAxis360Item();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful when trying to view online checkout',
			];
		}
	}

	/**
	 * Renews an item that has been checked out within the ILS.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>itemBarcode - The barcode of the item to be renewed.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=renewCheckout&username=userbarcode&password=userpin&itemBarcode=33025021368319
	 * </code>
	 *
	 * Sample Response (failed renewal):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":{
	 *     "itemId":"33025021368319",
	 *     "result":false,
	 *     "message":"This item may not be renewed - Item has been requested."
	 *   }
	 * }}
	 * </code>
	 *
	 * Sample Response (successful renewal):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":{
	 *     "itemId":"33025021723869",
	 *     "result":true,
	 *     "message":"#Renewal successful."
	 *   }
	 * }}
	 * </code>
	 *
	 */
	function renewCheckout(): array {
		$recordId = $_REQUEST['recordId'];
		$itemBarcode = $_REQUEST['itemBarcode'];
		$itemIndex = $_REQUEST['itemIndex'];

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			$renewalMessage = $user->renewCheckout($recordId, $itemBarcode, $itemIndex);
			if ($renewalMessage['success']) {
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('UserAPI', 'successfulRenewals');
			}
			return [
				'success' => true,
				'renewalMessage' => $renewalMessage,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function renewItem(): array {
		$source = $_REQUEST['itemSource'] ?? null;
		$itemBarcode = $_REQUEST['itemBarcode'] ?? null;

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				$result = $user->renewCheckout($user, $itemBarcode);
				if ($result['success']) {
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('UserAPI', 'successfulRenewals');
					return [
						'success' => true,
						'title' => $result['api']['title'],
						'message' => $result['api']['message'],
					];
				} else {
					return [
						'success' => false,
						'title' => $result['api']['title'],
						'message' => $result['api']['message'],
					];
				}
			} elseif ($source == 'overdrive') {
				return $this->renewOverDriveItem();
			} elseif ($source == 'cloud_library') {
				return $this->renewCloudLibraryItem();
			} elseif ($source == 'axis360') {
				return $this->renewAxis360Item();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}

		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Renews all items that have been checked out to the user from the ILS.
	 * Returns a count of the number of items that could be renewed.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=renewAll&username=userbarcode&password=userpin
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":"0006 of 8 items were renewed successfully."
	 * }}
	 * </code>
	 *
	 */
	function renewAll(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$result = $user->renewAll(true);
			$message = array_merge([$result['Renewed'] . ' of ' . $result['Total'] . ' titles were renewed'], $result['message']);
			for ($i = 0; $i < $result['Renewed']; $i++) {
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('UserAPI', 'successfulRenewals');
			}
			if ($result['Renewed'] == 0) {
				$result['success'] = false;
			}
			return [
				'success' => $result['success'],
				'title' => $result['title'],
				'renewalMessage' => $message,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}


	/**
	 * Places a hold on an item that is available within the ILS. The location where the user would like to pickup
	 * the title must be specified as well als the record the user would like a hold placed on.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>bibId    - The id of the record within the ILS.</li>
	 * <li>pickupBranch   - the location where the patron would like to pickup the title (optional). If not provided, the patron's home location will be used.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be placed, false if the username or password were incorrect or the hold could not be placed.</li>
	 * <li>holdMessage - a reason why the method failed if success is false, or information about hold queue position if successful.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=renewAll&username=userbarcode&password=userpin&bibId=1004012&pickupBranch=pa
	 * </code>
	 *
	 * Sample Response (successful hold):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Placement of hold request successful. You are number 1 in the queue."
	 * }}
	 * </code>
	 *
	 * Sample Response (failed hold):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "holdMessage":"Unable to place a hold request. You have already requested this."
	 * }}
	 * </code>
	 *
	 */
	function placeHold(): array {

		if (isset($_REQUEST['bibId'])) {
			$bibId = $_REQUEST['bibId'];
		} elseif(isset($_REQUEST['recordId'])) {
			$bibId = $_REQUEST['recordId'];
		} else {
			$bibId = $_REQUEST['itemId'];
		}

		if (isset($_REQUEST['itemSource'])) {
			$source = $_REQUEST['itemSource'];
		} else {
			$source = null;
		}

		$shortId = null;
		if($bibId) {
			if (strpos($bibId, ':') > 0) {
				[
					,
					$bibId,
				] = explode(':', $bibId, 2);
			}
			$shortId = $bibId;
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			global $library;
			if ($library->showHoldButton) {
				if ($source == 'ils' || $source == null) {
					if (isset($_REQUEST['pickupBranch']) || isset($_REQUEST['campus'])) {
						if (isset($_REQUEST['pickupBranch'])) {
							if (is_null($_REQUEST['pickupBranch'])) {
								$location = new Location();
								$userPickupLocations = $location->getPickupBranches($user);
								foreach ($userPickupLocations as $tmpLocation) {
									if ($tmpLocation->code == $user->getPickupLocationCode()) {
										$pickupBranch = $tmpLocation->code;
										break;
									}
								}
							} else {
								$pickupBranch = trim($_REQUEST['pickupBranch']);
							}
						} else {
							$pickupBranch = trim($_REQUEST['campus']);
						}
						$locationValid = $user->validatePickupBranch($pickupBranch);
						if (!$locationValid) {
							return [
								'success' => false,
								'message' => translate([
									'text' => 'This location is no longer available, please select a different pickup location',
									'isPublicFacing' => true,
								]),
							];
						}
					} else {
						$pickupBranch = $user->_homeLocationCode;
					}

					$homeLibrary = $user->getHomeLibrary();

					if (!empty($_REQUEST['cancelDate'])) {
						$cancelDate = $_REQUEST['cancelDate'];
					} elseif ($homeLibrary->defaultNotNeededAfterDays <= 0) {
						$cancelDate = null;
					} else {
						//Default to a date based on the default not needed after days in the library configuration.
						$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
						$cancelDate = date('Y-m-d', $nnaDate);
					}

					$holdType = $_REQUEST['holdType'];
					if ($holdType == 'item' && isset($_REQUEST['itemId'])) {
						$result = $user->placeItemHold($shortId, $_REQUEST['itemId'], $pickupBranch, $cancelDate);
						$action = $result['api']['action'] ?? null;
						$responseMessage = strip_tags($result['api']['message']);
						$responseMessage = trim($responseMessage);
						return [
							'success' => $result['success'],
							'title' => $result['api']['title'],
							'message' => $responseMessage,
							'action' => $action,
							'confirmationNeeded' => $result['api']['confirmationNeeded'] ?? false,
							'confirmationId' => $result['api']['confirmationId'] ?? null,
							];
					} elseif ($holdType == 'volume' && isset($_REQUEST['volumeId'])) {
						$result = $user->placeVolumeHold($shortId, $_REQUEST['volumeId'], $pickupBranch);
						$action = $result['api']['action'] ?? null;
						$responseMessage = strip_tags($result['api']['message']);
						$responseMessage = trim($responseMessage);
						return [
							'success' => $result['success'],
							'title' => $result['api']['title'],
							'message' => $responseMessage,
							'action' => $action,
							'confirmationNeeded' => $result['api']['confirmationNeeded'] ?? false,
							'confirmationId' => $result['api']['confirmationId'] ?? null,
						];
					} else {
							//Make sure that there are not volumes available
							require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
							$recordDriver = new MarcRecordDriver($bibId);
							if ($recordDriver->isValid()) {
								require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
								$volumeDataDB = new IlsVolumeInfo();
								$volumeDataDB->recordId = $recordDriver->getIdWithSource();
								if ($volumeDataDB->find(true)) {
									return [
										'success' => false,
										'message' => translate(['text' => 'You must place a volume hold on this title.']),
									];
								}
							}
							$result = $user->placeHold($bibId, $pickupBranch, $cancelDate);
							$action = $result['api']['action'] ?? null;
							$responseMessage = strip_tags($result['api']['message']);
							$responseMessage = trim($responseMessage);
							return [
								'success' => $result['success'],
								'title' => $result['api']['title'],
								'message' => $responseMessage,
								'action' => $action,
								'confirmationNeeded' => $result['api']['confirmationNeeded'] ?? false,
								'confirmationId' => $result['api']['confirmationId'] ?? null,
								'shouldBeItemHold' => (bool)$result['items'],
								'items' => $result['items'] ?? null,
							];
						}
				} elseif ($source == 'overdrive') {
					return $this->placeOverDriveHold();
				} elseif ($source == 'cloud_library') {
					return $this->placeCloudLibraryHold();
				} elseif ($source == 'axis360') {
					return $this->placeAxis360Hold();
				} else {
					return [
						'success' => false,
						'message' => 'Invalid source',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Sorry, holds are not currently allowed.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function placeItemHold(): array {
		$bibId = $_REQUEST['bibId'];
		$itemId = $_REQUEST['itemId'];

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			global $library;
			if ($library->showHoldButton) {
				if (isset($_REQUEST['pickupBranch'])) {
					$pickupBranch = trim($_REQUEST['pickupBranch']);
					$locationValid = $user->validatePickupBranch($pickupBranch);
					if (!$locationValid) {
						return [
							'success' => false,
							'message' => translate([
								'text' => 'This location is no longer available, please select a different pickup location',
								'isPublicFacing' => true,
							]),
						];
					}
				} else {
					$pickupBranch = $user->_homeLocationCode;
				}
				//Make sure that there are not volumes available
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($bibId);
				if ($recordDriver->isValid()) {
					require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
					$volumeDataDB = new IlsVolumeInfo();
					$volumeDataDB->recordId = $recordDriver->getIdWithSource();
					if ($volumeDataDB->find(true)) {
						return [
							'success' => false,
							'message' => translate(['text' => 'You must place a volume hold on this title.']),
						];
					}
				}
				return $user->placeItemHold($bibId, $itemId, $pickupBranch);
			} else {
				$pickupBranch = $user->_homeLocationCode;
			}
			return $user->placeHold($bibId, $pickupBranch);
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}


	function changeHoldPickUpLocation(): array {
		$holdId = $_REQUEST['holdId'];
		$newLocation = $_REQUEST['newLocation'];
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			[
				$locationId,
				$locationCode,
			] = explode('_', $newLocation);
			$locationValid = $user->validatePickupBranch($locationCode);
			if (!$locationValid) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'This location is no longer available, please select a different pickup location',
						'isPublicFacing' => true,
					]),
				];
			}
			$result = $user->changeHoldPickUpLocation($holdId, $locationCode);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getValidPickupLocations(): array {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();
		$patron = UserAccount::validateAccount($username, $password);
		if ($patron && !($patron instanceof AspenError)) {
			if ($patron->hasIlsConnection()) {
				$tmpPickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
				$pickupLocations = [];
				foreach ($tmpPickupLocations as $pickupLocation) {
					if (!is_string($pickupLocation)) {
						$pickupLocations[] = $pickupLocation->toArray();
					}
				}
				return [
					'success' => true,
					'pickupLocations' => $pickupLocations,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Patron is not connected to an ILS.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function confirmHold(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$confirmationId = $_REQUEST['confirmationId'] ?? null;
			$recordId = $_REQUEST['id'] ?? null;
			if($confirmationId && $recordId) {
				$result = $user->confirmHold($recordId, $confirmationId);
				return [
					'success' => $result['success'],
					'title' => $result['api']['title'],
					'message' => $result['api']['message'],
				];
			} else {
				return [
					'success' => false,
					'message' => 'You must provide a record and confirmation id to confirm this hold.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Place a hold within OverDrive.
	 * You should specify either the recordId of the title within VuFind or the overdrive id.
	 * The format is also required however when the user checks out the title they can override the format to checkout the version they want.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * <li>format - The format of the item to place a hold on within OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be placed, false if the username or password were incorrect or the hold could not be placed.</li>
	 * <li>message - information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=placeOverDriveHold&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was placed successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function placeOverDriveHold(): array {
		if ((isset($_REQUEST['overDriveId'])) || (isset($_REQUEST['itemId']))) {
			if (isset($_REQUEST['overDriveId'])) {
				$overDriveId = $_REQUEST['overDriveId'];
			} else {
				$overDriveId = $_REQUEST['itemId'];
			}

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$result = $driver->placeHold($user, $overDriveId);
				$action = $result['api']['action'] ?? null;
				return [
					'success' => $result['success'],
					'title' => $result['api']['title'],
					'message' => $result['api']['message'],
					'action' => $action,
				];
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => 'Unable to validate user',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide the overDriveId to be place the hold on',
			];
		}

	}

	function freezeOverDriveHold(): array {
		if ((isset($_REQUEST['overDriveId'])) || (isset($_REQUEST['recordId']))) {
			if (isset($_REQUEST['overDriveId'])) {
				$overDriveId = $_REQUEST['overDriveId'];
			} else {
				$overDriveId = $_REQUEST['recordId'];
			}

			$reactivationDate = $_REQUEST['reactivationDate'] ?? null;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$result = $driver->freezeHold($user, $overDriveId, $reactivationDate);
				return [
					'success' => $result['success'],
					'title' => $result['api']['title'],
					'message' => $result['api']['message'],
				];
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => 'Unable to validate user',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide the overDriveId to be place the hold on',
			];
		}

	}

	/**
	 * Activates a hold that was previously suspended within OverDrive.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin/password for the user. </li>
	 * <li>recordId - The recordId for the item. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be activated, false if the username or password were incorrect or the hold could not be activated.</li>
	 * <li>title - a brief title of failure or success</li>
	 * <li>message - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=activateOverDriveHold&username=23025003575917&password=1234&recordId=1004012
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "title":"Hold thawed successfully",
	 *   "message":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function activateOverDriveHold(): array {
		$id = $_REQUEST['recordId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->thawHold($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/**
	 * Cancel a hold within OverDrive
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>overdriveId - The id of the record in OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be cancelled, false if the username or password were incorrect or the hold could not be cancelled.</li>
	 * <li>message - information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=cancelOverDriveHold&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was cancelled successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function cancelOverDriveHold(): array {
		if (isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['recordId'];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->cancelHold($user, $overDriveId);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function renewOverDriveItem(): array {
		if (isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['recordId'];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->renewCheckout($user, $overDriveId);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/** @noinspection PhpUnused */
	function returnOverDriveCheckout(): array {
		if (isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} elseif (isset($_REQUEST['itemId'])) {
			$overDriveId = $_REQUEST['itemId'];
		} else {
			$overDriveId = $_REQUEST['id'];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->returnCheckout($user, $overDriveId);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}

	}

	/**
	 * Checkout an item in OverDrive by first adding to the cart and then processing the cart.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>overdriveId - The id of the record in OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid and the title could be checked out, false if the username or password were incorrect or the hold could not be checked out.</li>
	 * <li>message - information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=checkoutOverDriveItem&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your titles were checked out successfully. You may now download the titles from your Account."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function checkoutOverDriveItem(): array {
		if (isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['itemId'];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->checkOutTitle($user, $overDriveId);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function openOverDriveItem(): array {
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($user->id);
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$accessLink = $driver->getDownloadLink($overDriveId, $formatId, $patron);
			return [
				'success' => true,
				'title' => 'Download Url',
				'url' => $accessLink['downloadUrl'],
			];

		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function openOverDrivePreview(): array {
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];

		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
		$recordDriver = new OverDriveRecordDriver($overDriveId);
		if ($recordDriver->isValid()) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$format = new OverDriveAPIProductFormats();
			$format->id = $_REQUEST['formatId'];
			if ($format->find(true)) {
				$result['success'] = true;
				if ($_REQUEST['sampleNumber'] == 2) {
					$sampleUrl = $format->sampleUrl_2;
				} else {
					$sampleUrl = $format->sampleUrl_1;
				}

				$overDriveDriver = new OverDriveDriver();
				$overDriveDriver->incrementStat('numPreviews');

				$result['url'] = $sampleUrl;
			} else {
				$result['success'] = false;
				$result['title'] = "Error";
				$result['message'] = 'The specified Format was not valid';
			}
		} else {
			$result['success'] = false;
			$result['title'] = "Error";
			$result['message'] = 'The specified OverDrive Product was not valid';
		}

		return $result;
	}

	function updateOverDriveEmail(): array {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				if (isset($_REQUEST['overdriveEmail'])) {
					if ($_REQUEST['overdriveEmail'] != $patron->overdriveEmail) {
						$patron->overdriveEmail = $_REQUEST['overdriveEmail'];
						$patron->update();
					}
				}
				if (isset($_REQUEST['promptForOverdriveEmail'])) {
					if ($_REQUEST['promptForOverdriveEmail'] == 1 || $_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on') {
						$patron->promptForOverdriveEmail = 1;
					} else {
						$patron->promptForOverdriveEmail = 0;
					}
					$patron->update();
				}

				return $this->placeOverDriveHold();
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => 'Unable to validate user',
				];
			}

		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function checkoutCloudLibraryItem(): array {
		$id = $_REQUEST['itemId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->checkOutTitle($user, $id);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function renewCloudLibraryItem(): array {
		$id = $_REQUEST['recordId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->renewCheckout($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function placeCloudLibraryHold(): array {
		$id = $_REQUEST['itemId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->placeHold($user, $id);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function cancelCloudLibraryHold(): array {
		$id = $_REQUEST['recordId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->cancelHold($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function openCloudLibraryItem(): array {
		$id = $_REQUEST['itemId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($user->id);

			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryRecordDriver($id);
			$cloudLibrary = new CloudLibraryDriver();
			$accessUrl = $cloudLibrary->getCloudLibraryUrl($patron, $driver);

			return [
				'success' => true,
				'title' => 'Download Url',
				'url' => $accessUrl,
			];

		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function returnCloudLibraryItem(): array {
		$cloudLibraryId = $_REQUEST['itemId'] ?? $_REQUEST['id'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($cloudLibraryId);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->returnCheckout($user, $cloudLibraryId);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/**
	 * Checkout an item in Hoopla by first adding to the cart and then processing the cart.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>hooplaId - The id of the record in Hoopla.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - true if the account is valid and the title could be checked out, false if the username or password were incorrect or the hold could not be checked out.</li>
	 * <li>message - information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=checkoutHooplaItem&username=23025003575917&password=1234&id=13567811
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your titles were checked out successfully. You may now download the titles from your Account."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function checkoutHooplaItem(): array {
		$titleId = $_REQUEST['itemId'];

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$result = $driver->checkOutTitle($user, $titleId);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function returnHooplaItem(): array {

		$titleId = $_REQUEST['itemId'] ?? $_REQUEST['id'];

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$result = $driver->returnCheckout($user, $titleId);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function openHooplaItem(): array {
		$id = $_REQUEST['itemId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {

			require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
			$hooplaRecord = new HooplaRecordDriver($id);
			$accessLink = $hooplaRecord->getAccessLink();
			return [
				'success' => true,
				'title' => "Download Url",
				'url' => $accessLink['url'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function placeAxis360Hold(): array {
		$id = $_REQUEST['itemId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->placeHold($user, $id);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function freezeAxis360Hold(): array {
		$id = $_REQUEST['recordId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($user->id);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->freezeHold($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/**
	 * Activates a hold that was previously suspended within Axis360.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin/password for the user. </li>
	 * <li>recordId - The recordId for the item. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be activated, false if the username or password were incorrect or the hold could not be activated.</li>
	 * <li>title - a brief title of failure or success</li>
	 * <li>message - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=activateAxis360Hold&username=23025003575917&password=1234&recordId=1004012
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "title":"Hold thawed successfully",
	 *   "message":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function activateAxis360Hold(): array {
		$id = $_REQUEST['recordId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->thawHold($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function cancelAxis360Hold(): array {
		$id = $_REQUEST['recordId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->cancelHold($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function checkoutAxis360Item(): array {
		$id = $_REQUEST['itemId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($user->id);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->checkOutTitle($user, $id);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
				'action' => $action,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function returnAxis360Item(): array {
		$id = $_REQUEST['itemId'] ?? $_REQUEST['id'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->returnCheckout($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function renewAxis360Item(): array {
		$id = $_REQUEST['recordId'];

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->renewCheckout($user, $id);
			return [
				'success' => $result['success'],
				'title' => $result['api']['title'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	function openAxis360Item(): array {
		$id = $_REQUEST['itemId'];
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($user->id);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$driver = new Axis360RecordDriver($id);
			$accessUrl = $driver->getAccessOnlineLinkUrl($patron);
			return [
				'success' => true,
				'title' => 'Download Url',
				'url' => $accessUrl,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/**
	 * Cancel a hold that was placed within the ILS.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>cancelId[] - an array of holds that should be canceled.  Each item should be specified as <bibId>:<itemId>. BibId and itemId can be retrieved as part of the getPatronHolds API</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be canceled, false if the username or password were incorrect or the hold could not be canceled.</li>
	 * <li>holdMessage - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=cancelHold&username=23025003575917&password=1234&cancelId[]=1003198
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * </code>
	 *
	 * Sample Response (failed):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "holdMessage":"Your hold could not be cancelled. Please try again later or see your librarian."
	 * }}
	 * </code>
	 *
	 * Sample Response (succeeded):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was cancelled successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function cancelHold(): array {

		// Cancel Hold requires one of these, which one depends on the ILS
		$recordId = $_REQUEST['recordId'] ?? null;
		$cancelId = $_REQUEST['cancelId'] ?? null;

		$source = $_REQUEST['itemSource'] ?? null;
		$isIll = $_REQUEST['isIll'] ?? false;

		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				$result = $user->cancelHold($recordId, $cancelId, $isIll);
				return [
					'success' => $result['success'],
					'title' => $result['api']['title'],
					'message' => $result['api']['message'],
				];
			} elseif ($source == 'overdrive') {
				return $this->cancelOverDriveHold();
			} elseif ($source == 'cloud_library') {
				return $this->cancelCloudLibraryHold();
			} elseif ($source == 'axis360') {
				return $this->cancelAxis360Hold();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/**
	 * Freezes a hold that has been placed on a title within the ILS.  Only unavailable holds can be frozen.
	 * Note:  Horizon implements suspending and activating holds as a toggle.  If a hold is suspended, it will be activated
	 * and if a hold is active it will be suspended.  Care should be taken when calling the method with holds that are in the wrong state.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - </li>
	 * <li>holdId - </li>
	 * <li>suspendDate - The date that the hold should be automatically reactivated.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be frozen, false if the username or password were incorrect or the hold could not be frozen.</li>
	 * <li>holdMessage - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=freezeHold&username=23025003575917&password=1234&cancelId[]=1004012:0&suspendDate=1/25/2012
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function freezeHold(): array {
		$source = $_REQUEST['itemSource'] ?? null;
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$reactivationDate = $_REQUEST['reactivationDate'] ?? null;
			if ($source == 'ils' || $source == null) {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					return [
						'success' => false,
						'message' => 'recordId and holdId must be provided',
					];
				}
				$recordId = $_REQUEST['recordId'];
				$holdId = $_REQUEST['holdId'];
				return $user->freezeHold($recordId, $holdId, $reactivationDate);
			} elseif ($source == 'overdrive') {
				return $this->freezeOverDriveHold();
			} elseif ($source == 'axis360') {
				return $this->freezeAxis360Hold();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}

		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/** @noinspection PhpUnused */
	function freezeAllHolds() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			return $user->freezeAllHolds();
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Activates a hold that was previously suspended. For ILS, only unavailable holds can be activated.
	 * Note:  Horizon implements suspending and activating holds as a toggle.  If a hold is suspended, it will be activated
	 * and if a hold is active it will be suspended.  Care should be taken when calling the method with holds that are in the wrong state.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - </li>
	 * <li>holdId - Required for ILS holds</li>
	 * <li>itemSource - The source of the item, i.e. overdrive, ils, axis360. If not provided, hold will be assumed as ils. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be activated, false if the username or password were incorrect or the hold could not be activated.</li>
	 * <li>title - </li>
	 * <li>message - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=activateHold&username=23025003575917&password=1234&cancelId[]=1004012:0
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function activateHold(): array {
		$user = $this->getUserForApiCall();
		$source = $_REQUEST['itemSource'] ?? null;

		if ($user && !($user instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => 'recordId and holdId must be provided',
					];
				} else {
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					return $user->thawHold($recordId, $holdId);
				}
			} elseif ($source == 'overdrive') {
				return $this->activateOverDriveHold();
			} elseif ($source == 'axis360') {
				return $this->activateAxis360Hold();
			} else {
				return [
					'success' => false,
					'message' => 'Invalid source',
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/** @noinspection PhpUnused */
	function activateAllHolds() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			return $user->thawAllHolds();
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/** @noinspection PhpUnused */
	function submitVdxRequest() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/VdxDriver.php';
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)) {
				$vdxDriver = new VdxDriver();
				return $vdxDriver->submitRequest($vdxSettings, $user, $_REQUEST, false);
			} else {
				return [
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => "VDX Settings do not exist, please contact the library to make a request.",
						'isPublicFacing' => true,
					]),
					'success' => false,
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/** @noinspection PhpUnused */
	function cancelVdxRequest() {
		$user = $this->getUserForApiCall();
		$title = translate([
			'text' => 'Error',
			'isPublicFacing' => true,
		]);
		if ($user && !($user instanceof AspenError)) {
			$sourceId = $_REQUEST['sourceId'] ?? null;
			$cancelId = $_REQUEST['cancelId'] ?? null;
			$result = $user->cancelVdxRequest($sourceId, $cancelId);
			if ($result['success'] == true || $result['success'] == "true") {
				$title = translate([
					'text' => 'Success',
					'isPublicFacing' => true,
				]);
			}
			return [
				'success' => $result['success'],
				'title' => $title,
				'message' => $result['message'],
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Loads the reading history for the user.  Includes print, eContent, and OverDrive titles.
	 * Note: The return of this method can be quite lengthy if the patron has a large number of items in their reading history.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be canceled, false if the username or password were incorrect or the hold could not be canceled.</li>
	 * <li>holdMessage - a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=getPatronReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "totalResults":46,
	 *   "page_current":1,
	 *   "page_total":2,
	 *   "readingHistory":[
	 *     {"recordId":"597608",
	 *      "checkout":"2011-03-18",
	 *      "checkoutTime":1300428000,
	 *      "lastCheckout":"2011-03-22",
	 *      "lastCheckoutTime":1300773600,
	 *      "title":"The wanderer",
	 *      "title_sort":"wanderer",
	 *      "author":"O.A.R. (Musical group)",
	 *      "format":"Music CD",
	 *      "format_category":"Music",
	 *      "isbn":"",
	 *      "upc":"803494030726"
	 *     },
	 *     {"recordId":"808990",
	 *      "checkout":"2011-03-18",
	 *      "checkoutTime":1300428000,
	 *      "lastCheckout":"2011-03-22",
	 *      "lastCheckoutTime":1300773600,
	 *      "title":"Seals \/",
	 *      "title_sort":"seals \/",
	 *      "author":"Sexton, Colleen A.,",
	 *      "format":"Book",
	 *      "format_category":"Books",
	 *      "isbn":"9781600140563",
	 *      "upc":""
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function getPatronReadingHistory(): array {
		global $offlineMode;
		if ($offlineMode) {
			return [
				'success' => false,
				'message' => 'Circulation system is offline',
			];
		} else {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$page = $_REQUEST['page'] ?? 1;
				$pageSize = $_REQUEST['pageSize'] ?? 25;
				$sort = $_REQUEST['sort_by'] ?? 'checkedOut';
				$readingHistory = $user->getReadingHistory($page, $pageSize, $sort);

				$options = [
					'totalItems' => $readingHistory['numTitles'],
					'perPage' => $pageSize,
					'append' => false,
					'sort' => $sort,
				];
				$pager = new Pager($options);

				return [
					'success' => true,
					'totalResults' => $pager->getTotalItems(),
					'page_current' => (int)$pager->getCurrentPage(),
					'page_total' => (int)$pager->getTotalPages(),
					'sort' => $sort,
					'readingHistory' => $readingHistory['titles'],
				];
			} else {
				return [
					'success' => false,
					'message' => 'Login unsuccessful',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function updatePatronReadingHistory(): array {
		global $offlineMode;
		if ($offlineMode) {
			return [
				'success' => true,
				'message' => 'Circulation system is offline',
			];
		} else {
			$username = $_REQUEST['username'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)) {
				$user->updateReadingHistoryBasedOnCurrentCheckouts();

				return ['success' => true];
			} else {
				return [
					'success' => false,
					'message' => 'Could not find a user with that user name',
				];
			}
		}
	}

	/**
	 * Allows reading history to be collected for the patron.  If this option is not selected,
	 * no reading history for the patron wil be stored.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the reading history could be turned on, false if the username or password were incorrect or the reading history could not be turned on.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=optIntoReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function optIntoReadingHistory(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction('optIn', []);
			return ['success' => true];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Stops collecting reading history for the patron and removes any reading history entries that have been collected already.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the reading history could be turned off, false if the username or password were incorrect or the reading history could not be turned off.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=optOutOfReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function optOutOfReadingHistory(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction('optOut', []);
			return ['success' => true];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Clears the user's reading history, but does not stop the collection of new data.  If items are currently checked out
	 * to the user they will be added to the reading history the next time cron runs.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the reading history could cleared, false if the username or password were incorrect or the reading history could not be cleared.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=deleteAllFromReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function deleteAllFromReadingHistory(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction('deleteAll', []);
			return ['success' => true];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Removes one or more titles from the user's reading history.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>selected - A list of record ids to be deleted from the reading history.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the items could be removed from the reading history, false if the username or password were incorrect or the items could not be removed from the reading history.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/UserAPI?method=deleteSelectedFromReadingHistory&username=23025003575917&password=1234&selected[]=25855
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @noinspection PhpUnused
	 */
	function deleteSelectedFromReadingHistory(): array {
		if(isset($_REQUEST['selected'])) {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$selectedTitles = [$_REQUEST['selected'] => $_REQUEST['selected']];
				$user->doReadingHistoryAction('deleteMarked', $selectedTitles);
				return ['success' => true];
			} else {
				return [
					'success' => false,
					'message' => 'Login unsuccessful',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'No item provided to delete',
			];
		}
	}

	function getReadingHistorySortOptions() {
		return [
			0 => [
				'label' => translate([
					'text' => 'Title',
					'isPublicFacing' => true,
				]),
				'sort' => 'title',
				'default' => false,
			],
			1 => [
				'label' => translate([
					'text' => 'Author',
					'isPublicFacing' => true,
				]),
				'sort' => 'author',
				'default' => false,
			],
			2 => [
				'label' => translate([
					'text' => 'Last Used',
					'isPublicFacing' => true,
				]),
				'sort' => 'checkedOut',
				'default' => true,
			],
			3 => [
				'label' => translate([
					'text' => 'Format',
					'isPublicFacing' => true,
				]),
				'sort' => 'format',
				'default' => false,
			],
		];
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	private function loadUsernameAndPassword(): array {
		$username = $_REQUEST['username'] ?? '';
		$password = $_REQUEST['password'] ?? '';

		// check for post request data
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}

		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return [
			$username,
			$password,
		];
	}

	/** @noinspection PhpUnused */
	function getBarcodeForPatron(): array {
		$results = [
			'success' => false,
			'message' => 'Unknown error loading barcode',
		];
		if (isset($_REQUEST['patronId'])) {
			$user = new User();
			$user->username = $_REQUEST['patronId'];
			if ($user->find(true)) {
				$results = [
					'success' => true,
					'barcode' => $user->getBarcode(),
				];
			} else {
				$results['message'] = 'Invalid Patron';
			}
		} elseif (isset($_REQUEST['id'])) {
			$user = new User();
			$user->id = $_REQUEST['id'];
			if ($user->find(true)) {
				$results = [
					'success' => true,
					'barcode' => $user->getBarcode(),
				];
			} else {
				$results['message'] = 'Invalid Patron';
			}
		} else {
			$results['message'] = 'No patron id was provided';
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function updateBrowseCategoryStatus() {
		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error updating preferences',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if (isset($_REQUEST['browseCategoryId'])) {
			$user = $this->getUserForApiCall();
			$givenId = $_REQUEST['browseCategoryId'];
			$label = explode('_', $givenId);
			$id = $label[3];
			if ($user && !($user instanceof AspenError)) {
				if (strpos($givenId, 'system_saved_searches') !== false) {
					$searchEntry = new SearchEntry();
					$searchEntry->id = $id;
					if (!$searchEntry->find(true)) {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error updating preferences',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Unable to find saved search',
								'isPublicFacing' => true,
							]),
						];
					}
				} elseif (strpos($givenId, 'system_user_lists') !== false) {
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$userList = new UserList();
					$userList->id = $id;
					if (!$userList->find(true)) {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error updating preferences',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Unable to find user list',
								'isPublicFacing' => true,
							]),
						];
					}
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
					$browseCategory = new BrowseCategory();
					$browseCategory->textId = $givenId;
					if (!$browseCategory->find(true)) {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error updating preferences',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Unable to find browse category',
								'isPublicFacing' => true,
							]),
						];
					}

					$isDismissed = new BrowseCategoryDismissal();
					$isDismissed->browseCategoryId = $givenId;
					$isDismissed->userId = $user->id;
					if (!$isDismissed->find(true)) {
						$browseCategory->numTimesDismissed += 1;
						$browseCategory->update();
					}
				}

				$browseCategoryDismissal = new BrowseCategoryDismissal();
				$browseCategoryDismissal->browseCategoryId = $_REQUEST['browseCategoryId'];
				$browseCategoryDismissal->userId = $user->id;
				if ($browseCategoryDismissal->find(true)) {
					$browseCategoryDismissal->delete();
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Preferences updated',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Browse category has been unhidden',
							'isPublicFacing' => true,
						]),
					];
				} else {
					$browseCategoryDismissal->insert();
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Preferences updated',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Browse category has been hidden',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				$result['message'] = translate([
					'text' => 'Incorrect user information, please login again',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'Please provide a browse category',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function dismissBrowseCategory(): array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error updating preferences',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$browseCategoryId = $_REQUEST['browseCategoryId'];
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (strpos($browseCategoryId, "system_saved_searches") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				$searchEntry = new SearchEntry();
				$searchEntry->id = $id;
				if (!$searchEntry->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = translate([
							'text' => 'You already dismissed this browse category',
							'isPublicFacing' => true,
						]);
					} else {
						$browseCategoryDismissal->insert();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category has been hidden',
								'isPublicFacing' => true,
							]),
						];
					}
				}
			} elseif (strpos($browseCategoryId, "system_user_lists") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$userList = new UserList();
				$userList->id = $id;
				if (!$userList->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = translate([
							'text' => 'You already dismissed this browse category',
							'isPublicFacing' => true,
						]);
					} else {
						$browseCategoryDismissal->insert();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category has been hidden',
								'isPublicFacing' => true,
							]),
						];
					}
				}
			} else {
				require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
				$browseCategory = new BrowseCategory();
				$browseCategory->textId = $browseCategoryId;
				if (!$browseCategory->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = translate([
							'text' => 'You already dismissed this browse category',
							'isPublicFacing' => true,
						]);
					} else {
						$browseCategoryDismissal->insert();
						$browseCategory->numTimesDismissed += 1;
						$browseCategory->update();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category has been hidden',
								'isPublicFacing' => true,
							]),
						];
					}
				}
			}
		} else {
			$result['message'] = translate([
				'text' => 'Incorrect user information, please login again',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function showBrowseCategory(): array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error updating preferences',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$browseCategoryId = $_REQUEST['browseCategoryId'];
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (strpos($browseCategoryId, "system_saved_searches") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				$searchEntry = new SearchEntry();
				$searchEntry->id = $id;
				if (!$searchEntry->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$browseCategoryDismissal->delete();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category will be visible again',
								'isPublicFacing' => true,
							]),
						];
					} else {
						$result['message'] = translate([
							'text' => 'You already have this browse category visible',
							'isPublicFacing' => true,
						]);
					}
				}
			} elseif (strpos($browseCategoryId, "system_user_lists") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$userList = new UserList();
				$userList->id = $id;
				if (!$userList->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$browseCategoryDismissal->delete();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category will be visible again',
								'isPublicFacing' => true,
							]),
						];
					} else {
						$result['message'] = translate([
							'text' => 'You already have this browse category visible',
							'isPublicFacing' => true,
						]);
					}
				}
			} else {
				require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
				$browseCategory = new BrowseCategory();
				$browseCategory->textId = $browseCategoryId;
				if (!$browseCategory->find(true)) {
					$result['message'] = translate([
						'text' => 'Invalid browse category provided, please try again',
						'isPublicFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $user->id;
					if ($browseCategoryDismissal->find(true)) {
						$browseCategoryDismissal->delete();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category will be visible again',
								'isPublicFacing' => true,
							]),
						];
					} else {
						$result['message'] = translate([
							'text' => 'You already have this browse category visible',
							'isPublicFacing' => true,
						]);
					}
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getHiddenBrowseCategories(): array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error updating preferences',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$hiddenCategories = [];
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissals = new BrowseCategoryDismissal();
			$browseCategoryDismissals->userId = $user->id;
			$browseCategoryDismissals->find();
			while ($browseCategoryDismissals->fetch()) {
				$hiddenCategories[] = clone($browseCategoryDismissals);
			}

			if ($hiddenCategories > 0) {
				$categories = [];
				foreach ($hiddenCategories as $hiddenCategory) {
					if (strpos($hiddenCategory->browseCategoryId, "system_saved_searches") !== false) {
						$parentLabel = "";
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$savedSearchesBrowseCategory = new BrowseCategory();
						$savedSearchesBrowseCategory->textId = "system_saved_searches";
						if ($savedSearchesBrowseCategory->find(true)) {
							$parentLabel = $savedSearchesBrowseCategory->label . ": ";
						}

						$label = explode('_', $hiddenCategory->browseCategoryId);
						$id = $label[3];
						$searchEntry = new SearchEntry();
						$searchEntry->id = $id;
						if ($searchEntry->find(true)) {
							$category['id'] = $hiddenCategory->browseCategoryId;
							$category['name'] = $parentLabel;
							if ($searchEntry->title) {
								$category['name'] = $parentLabel . $searchEntry->title;
							}
							$categories[] = $category;
						}
					} elseif (strpos($hiddenCategory->browseCategoryId, "system_user_lists") !== false) {
						$parentLabel = "";
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$userListsBrowseCategory = new BrowseCategory();
						$userListsBrowseCategory->textId = "system_user_lists";
						if ($userListsBrowseCategory->find(true)) {
							$parentLabel = $userListsBrowseCategory->label . ": ";
						}

						$label = explode('_', $hiddenCategory->browseCategoryId);
						$id = $label[3];
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$sourceList = new UserList();
						$sourceList->id = $id;
						if ($sourceList->find(true)) {
							$category['id'] = $hiddenCategory->browseCategoryId;
							$category['name'] = $parentLabel;
							if ($sourceList->title) {
								$category['name'] = $parentLabel . $sourceList->title;
							}
							$categories[] = $category;
						}
					} else {
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$browseCategory = new BrowseCategory();
						$browseCategory->textId = $hiddenCategory->browseCategoryId;
						if ($browseCategory->find(true)) {
							$categoryResponse = [
								'id' => $browseCategory->textId,
								'name' => $browseCategory->label,
							];

							$subCategories = $browseCategory->getSubCategories();
							$categoryResponse['subCategories'] = [];
							if (count($subCategories) > 0) {
								foreach ($subCategories as $subCategory) {
									$tempA = new BrowseCategory();
									$tempA->id = $subCategory->subCategoryId;
									if ($tempA->find(true)) {
										$tempB = new BrowseCategoryDismissal();
										$tempB->userId = $user->id;
										$tempB->browseCategoryId = $tempA->textId;
										if ($tempB->find(true)) {
											$categoryResponse['subCategories'][] = [
												'id' => $tempA->textId,
												'name' => $browseCategory->label . ': ' . $tempA->label,
											];
										}
									}
								}
							}

							$categories[] = $categoryResponse;
						}
					}
				}
				$result = [
					'success' => true,
					'title' => translate([
						'text' => 'Your hidden categories',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'You currently have these categories hidden',
						'isPublicFacing' => true,
					]),
					'categories' => $categories,
				];
			} else {
				$result = [
					'message' => translate([
						'text' => 'You have no hidden browse categories',
						'isPublicFacing' => true,
					]),
				];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateNotificationOnboardingStatus(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$newStatus = $_REQUEST['status'] ?? null;
			$userToken = $_REQUEST['token'] ?? null;
			if($newStatus && $userToken) {
				require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
				$token = new UserNotificationToken();
				$token->pushToken = $userToken;
				if($token->find(true)) {
					if ($newStatus == 'false' || !$newStatus) {
						$token->onboardAppNotifications = 0;
					}
					$token->update();
					return [
						'success' => true,
						'title' => 'Success',
						'message' => 'Updated user notification onboarding status'
					];
				} else {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => 'Push token not valid.',
					];
				}
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => 'New status or user token not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to validate user',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getUserByBarcode(): array {
		$results = [
			'success' => false,
			'message' => 'Unknown error loading patronId',
		];
		if (isset($_REQUEST['username'])) {
			$user = UserAccount::getUserByBarcode($_REQUEST['username']);
			if ($user != false) {
				$results = [
					'success' => true,
					'id' => $user->id,
					'patronId' => $user->username,
					'displayName' => $user->displayName,
				];
			} else {
				$results['message'] = 'Invalid Patron';
			}
		} else {
			$results['message'] = 'No barcode was provided';
		}
		return $results;
	}

	/**
	 * @return bool|User
	 */
	protected function getUserForApiCall() {
		$user = false;
		if ($this->getLiDAVersion() === "v22.04.00") {
			[
				$username,
				$password,
			] = $this->loadUsernameAndPassword();
			return UserAccount::validateAccount($username, $password);
		}

		if (isset($_REQUEST['patronId'])) {
			$user = new User();
			$user->username = $_REQUEST['patronId'];
			if (!$user->find(true)) {
				$user = false;
			}
		} elseif (isset($_REQUEST['userId'])) {
			$user = new User();
			$user->id = $_REQUEST['userId'];
			if (!$user->find(true)) {
				$user = false;
			}
		} elseif (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && $_REQUEST['id'] != 0) {
			$user = new User();
			$user->id = $_REQUEST['id'];
			if (!$user->find(true)) {
				$user = false;
			}
		}
		if ($user === false) {
			[
				$username,
				$password,
			] = $this->loadUsernameAndPassword();
			$user = UserAccount::validateAccount($username, $password);
		}
		if ($user !== false && $user->source == 'admin') {
			return false;
		}
		return $user;
	}

	function getLiDAVersion() {
		global $logger;
		//$logger->log(print_r(getallheaders(), true), Logger::LOG_WARNING);
		foreach (getallheaders() as $name => $value) {
			if ($name == 'version' || $name == 'Version') {
				$version = explode(' ', $value);
				return $version[0];
			}
		}
		return 0;
	}

	function getLinkedAccounts() {
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$linkedAccounts = $user->getLinkedUsers();
			$account = [];
			if (count($linkedAccounts) > 0) {
				foreach ($linkedAccounts as $linkedAccount) {
					$account[$linkedAccount->id]['displayName'] = $linkedAccount->displayName;
					$account[$linkedAccount->id]['homeLocation'] = $linkedAccount->getHomeLocation()->displayName;
					$account[$linkedAccount->id]['barcode'] = $linkedAccount->cat_username;
					$account[$linkedAccount->id]['barcodeStyle'] = $linkedAccount->getHomeLibrary()->libraryCardBarcodeStyle;
					$account[$linkedAccount->id]['id'] = $linkedAccount->id;
					$account[$linkedAccount->id]['expired'] = $linkedAccount->_expired;
					$account[$linkedAccount->id]['expires'] = $linkedAccount->_expires;
					$account[$linkedAccount->id]['cat_username'] = $linkedAccount->cat_username;
				}
				return [
					'success' => true,
					'linkedAccounts' => $account,
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'You have no linked accounts',
						'isPublicFacing' => true,
					]),
					'linkedAccounts' => $account,
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function getViewers() {
		$user = $this->getUserForApiCall();

		if ($user && !($user instanceof AspenError)) {
			$viewers = [];
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->linkedAccountId = $user->id;
			$userLink->find();
			while ($userLink->fetch()) {
				$linkedUser = new User();
				$linkedUser->id = $userLink->primaryAccountId;
				if ($linkedUser->find(true)) {
					if (!$linkedUser->isBlockedAccount($user->id)) {
						$viewers[$linkedUser->id]['displayName'] = $linkedUser->displayName;
						$viewers[$linkedUser->id]['homeLocation'] = $linkedUser->getHomeLocation()->displayName;
						$viewers[$linkedUser->id]['barcode'] = $linkedUser->cat_username;
						$viewers[$linkedUser->id]['id'] = $linkedUser->id;
					}
				}
			}

			return [
				'success' => true,
				'viewers' => $viewers,
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function addAccountLink() {
		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();

		$accountToLinkUsername = $_POST['accountToLinkUsername'] ?? '';
		$accountToLinkPassword = $_POST['accountToLinkPassword'] ?? '';

		$accountToLink = UserAccount::validateAccount($accountToLinkUsername, $accountToLinkPassword);
		$patron = UserAccount::validateAccount($username, $password);

		if ($patron && !($patron instanceof AspenError)) {
			if ($accountToLink) {
				if ($accountToLink->id != $patron->id) {
					$addResult = $patron->addLinkedUser($accountToLink);
					if ($addResult === true) {
						return [
							'success' => true,
							'title' => translate([
								'text' => 'Accounts linked',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Successfully linked accounts.',
								'isPublicFacing' => true,
							]),
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Unable to link accounts',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.',
								'isPublicFacing' => true,
							]),
						];
					}
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Unable to link accounts',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'You cannot link to yourself.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to link accounts',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Sorry, we could not find a user with that information to link to.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function removeAccountLink() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$accountToRemove = $_REQUEST['idToRemove'];
			if ($user->removeLinkedUser($accountToRemove)) {
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Accounts no longer linked',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Successfully removed linked account.',
						'isPublicFacing' => true,
					]),
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to unlink accounts',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Sorry, we could remove that account.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function removeViewerLink() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$accountToRemove = $_REQUEST['idToRemove'];
			if ($user->removeManagingAccount($accountToRemove)) {
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Accounts no longer linked',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Successfully removed linked account.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function saveLanguage() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (isset($_REQUEST['languageCode'])) {
				$user->interfaceLanguage = $_REQUEST['languageCode'];
				$user->update();
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Language updated',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Your language preference was updated.',
						'isPublicFacing' => true,
					]),
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to update language',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'A language code was no provided',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to validate user',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/**
	 * Stores the push token for notifications.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	function saveNotificationPushToken(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (isset($_POST['pushToken'])) {
				$device = $_POST['deviceModel'] ?? "Unknown";
				$result = $user->saveNotificationPushToken($_POST['pushToken'], $device);
				if ($result === true) {
					return [
						'success' => true,
						'title' => translate([
							'text' => 'Success',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Successfully updated notification preferences',
							'isPublicFacing' => true,
						]),
					];
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Sorry, we could save your notification preferences at this time.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'A push token was not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function deleteNotificationPushToken(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (isset($_POST['pushToken'])) {
				$device = $_POST['deviceModel'] ?? "Unknown";
				$result = $user->deleteNotificationPushToken($_POST['pushToken']);
				if ($result === true) {
					return [
						'success' => true,
						'title' => translate([
							'text' => 'Success',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Successfully updated notification preferences',
							'isPublicFacing' => true,
						]),
					];
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Sorry, we could save your notification preferences at this time.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'A push token was not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getNotificationPushToken(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$result = $user->getNotificationPushToken();
			if (!empty($result)) {
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Success',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Notification push tokens found',
						'isPublicFacing' => true,
					]),
					'tokens' => $result,
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'No notification push tokens were found for this user',
						'isPublicFacing' => true,
					]),
					'tokens' => $result,
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getNotificationPreferences(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (!empty($_POST['pushToken'])) {
				$preferences = $user->getNotificationPreferencesByToken($_POST['pushToken']);
				return [
					'success' => true,
					'savedPreferences' => $preferences,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Push token not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getNotificationPreference(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (!empty($_REQUEST['type']) && !empty($_POST['pushToken'])) {
				$allowNotificationType = $user->getNotificationPreference($_REQUEST['type'], $_POST['pushToken']);
				return [
					'success' => true,
					'type' => $_REQUEST['type'],
					'allow' => $allowNotificationType,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Preference type or push token not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function setNotificationPreference(): array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if (!empty($_REQUEST['type']) && !empty($_REQUEST['pushToken']) && !empty($_REQUEST['value'])) {
				if ($_REQUEST['value'] === "false") {
					$newValue = 0;
				} else {
					$newValue = 1;
				}
				$result = $user->setNotificationPreference($_REQUEST['type'], $newValue, $_REQUEST['pushToken']);
				if ($result) {
					return [
						'success' => true,
						'title' => translate([
							'text' => 'Success',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Successfully updated notification preferences',
							'isPublicFacing' => true,
						]),
					];
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Sorry, we could save your notification preferences at this time.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Preference type, value, or push token not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}

	function initMasquerade() {
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
						while ($accountProfile->fetch()) {
							$masqueradedUser = new User();
							$masqueradedUser->source = $accountProfile->name;
							if ($accountProfile->loginConfiguration == 'barcode_pin') {
								$masqueradedUser->cat_username = $libraryCard;
							} else {
								$masqueradedUser->cat_password = $libraryCard;
							}
							if ($masqueradedUser->find(true)) {
								if ($masqueradedUser->id == $user->id) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'No need to masquerade as yourself.',
											'isAdminFacing' => true,
										]),
									];
								}
								$foundExistingUser = true;
								break;
							} else {
								$masqueradedUser = null;
							}
						}

						if (!$foundExistingUser) {
							// Test for a user that hasn't logged into Aspen Discovery before
							$masqueradedUser = UserAccount::findNewUser($libraryCard);
							if (!$masqueradedUser) {
								return [
									'success' => false,
									'error' => translate([
										'text' => 'Invalid User',
										'isAdminFacing' => true,
									]),
								];
							}
						} else {
							//Call find new user just to be sure that all patron information is up to date.
							$tmpUser = UserAccount::findNewUser($libraryCard);
							if (!$tmpUser) {
								//This user no longer exists? return an error?
								return [
									'success' => false,
									'error' => translate([
										'text' => 'User no longer exists in the ILS',
										'isAdminFacing' => true,
									]),
								];
							} else {
								$masqueradedUser = $tmpUser;
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
							if (UserAccount::userHasPermission('Masquerade as any user')) {
								//The user can masquerade as anyone, no additional checks needed
							} elseif (UserAccount::userHasPermission('Masquerade as unrestricted patron types')) {
								if ($isRestrictedUser) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Cannot masquerade as patrons of this type.',
											'isAdminFacing' => true,
										]),
									];
								}
							} elseif (UserAccount::userHasPermission('Masquerade as patrons with same home library') || UserAccount::userHasPermission('Masquerade as unrestricted patrons with same home library')) {
								$guidingUserLibrary = $user->getHomeLibrary();
								if (!$guidingUserLibrary) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Could not determine your home library.',
											'isAdminFacing' => true,
										]),
									];
								}
								$masqueradedUserLibrary = $masqueradedUser->getHomeLibrary();
								if (!$masqueradedUserLibrary) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Could not determine the patron\'s home library.',
											'isAdminFacing' => true,
										]),
									];
								}
								if ($guidingUserLibrary->libraryId != $masqueradedUserLibrary->libraryId) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'You do not have the same home library as the patron.',
											'isAdminFacing' => true,
										]),
									];
								}
								if ($isRestrictedUser && !UserAccount::userHasPermission('Masquerade as patrons with same home library')) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Cannot masquerade as patrons of this type.',
											'isAdminFacing' => true,
										]),
									];
								}
							} elseif (UserAccount::userHasPermission('Masquerade as patrons with same home location') || UserAccount::userHasPermission('Masquerade as unrestricted patrons with same home location')) {
								if (empty($user->homeLocationId)) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Could not determine your home library branch.',
											'isAdminFacing' => true,
										]),
									];
								}
								if (empty($masqueradedUser->homeLocationId)) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Could not determine the patron\'s home library branch.',
											'isAdminFacing' => true,
										]),
									];
								}
								if ($user->homeLocationId != $masqueradedUser->homeLocationId) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'You do not have the same home library branch as the patron.',
											'isAdminFacing' => true,
										]),
									];
								}
								if ($isRestrictedUser && !UserAccount::userHasPermission('Masquerade as patrons with same home location')) {
									return [
										'success' => false,
										'error' => translate([
											'text' => 'Cannot masquerade as patrons of this type.',
											'isAdminFacing' => true,
										]),
									];
								}
							}

							//Setup the guiding user and masqueraded user
							global $guidingUser;
							$guidingUser = $user;
							$user = $masqueradedUser;
							if (!empty($user) && !($user instanceof AspenError)) {
								if ($user->lastLoginValidation < (time() - 15 * 60)) {
									$user->loadContactInformation();
									$user->validateUniqueId();
								}

								@session_start(); // (suppress notice if the session is already started)
								$_SESSION['guidingUserId'] = $guidingUser->id;
								$_SESSION['activeUserId'] = $user->id;
								@session_write_close();
								//TODO: For calls from LiDA we would need the entire patron profile
								return [
									'success' => true,
									'activeUserId' => $user->id,
								];
							} else {
								unset($_SESSION['guidingUserId']);
								return [
									'success' => false,
									'error' => translate([
										'text' => 'Failed to initiate masquerade as specified user.',
										'isAdminFacing' => true,
									]),
								];
							}
						} else {
							return [
								'success' => false,
								'error' => translate([
									'text' => 'Could not load user to masquerade as.',
									'isAdminFacing' => true,
								]),
							];
						}
					} else {
						return [
							'success' => false,
							'error' => $user ? translate([
								'text' => 'You are not allowed to Masquerade.',
								'isAdminFacing' => true,
							]) : translate([
								'text' => 'Your session has expired, please sign in again.',
								'isAdminFacing' => true,
							]),
						];
					}
				} else {
					return [
						'success' => false,
						'error' => translate([
							'text' => 'Already Masquerading.',
							'isAdminFacing' => true,
						]),
					];
				}
			} else {
				return [
					'success' => false,
					'error' => translate([
						'text' => 'Please enter a valid Library Card Number.',
						'isAdminFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'error' => translate([
					'text' => 'Masquerade Mode is not allowed.',
					'isAdminFacing' => true,
				]),
			];
		}
	}

	function endMasquerade() {
		if (UserAccount::isLoggedIn()) {
			global $guidingUser;
			global $masqueradeMode;
			@session_start();  // (suppress notice if the session is already started)
			unset($_SESSION['guidingUserId']);
			$masqueradeMode = false;
			if ($guidingUser) {
				if($guidingUser->isLoggedInViaSSO) {
					$user = UserAccount::loginWithAspen($guidingUser);
				} else {
					$_REQUEST['username'] = $guidingUser->getBarcode();
					$_REQUEST['password'] = $guidingUser->getPasswordOrPin();
					$user = UserAccount::login();
				}

				if ($user && !($user instanceof AspenError)) {
					return ['success' => true];
				} else {
					UserAccount::softLogout();
				}
			}
		}
		return ['success' => false];
	}
}