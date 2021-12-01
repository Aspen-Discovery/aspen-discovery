<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class UserAPI extends Action
{
	/**
	 * Processes method to determine return type and calls the correct method.
	 * Should not be called directly.
	 *
	 * @see Action::launch()
	 * @access private
	 */
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		//Make sure the user can access the API based on the IP address
		if (!IPAddress::allowAPIAccessForClientIP() && !in_array($method, array('isLoggedIn', 'logout', 'checkoutItem', 'placeHold', 'renewItem', 'renewAll', 'viewOnlineItem', 'changeHoldPickUpLocation', 'getPatronProfile', 'validateAccount', 'getPatronHolds', 'getPatronCheckedOutItems', 'cancelHold', 'activateHold', 'freezeHold', 'returnCheckout', 'updateOverDriveEmail', 'getValidPickupLocations' )) && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if ($method != 'getUserForApiCall' && method_exists($this, $method)) {
			$result = [
				'result' => $this->$method()
			];
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('UserAPI', $method);
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
	}

	/**
	 *
	 * Returns whether or not a user is currently logged in based on session information.
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
	function isLoggedIn(): bool
	{
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
	function login() : array
	{
		global $logger;
		$logger->log("Starting UserAPI/login session: " . session_id(), Logger::LOG_DEBUG);
		//Login the user.  Must be called via Post parameters.
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$user = UserAccount::getLoggedInUser();
			if ($user && !($user instanceof AspenError)) {
				$logger->log("User is already logged in",Logger::LOG_DEBUG);
				return array('success' => true, 'name' => ucwords($user->firstname . ' ' . $user->lastname));
			} else {
				try {
					$user = UserAccount::login();
					if ($user && !($user instanceof AspenError)) {
						$logger->log("User was logged in successfully session: " . session_id(),Logger::LOG_DEBUG);
						return array('success' => true, 'name' => ucwords($user->firstname . ' ' . $user->lastname));
					} else {
						$logger->log("Incorrect login parameters",Logger::LOG_DEBUG);
						return array('success' => false);
					}
				} catch (UnknownAuthenticationMethodException $e) {
					$logger->log("Error logging user in $e",Logger::LOG_DEBUG);
					return array('success' => false);
				}
			}
		} else {
			return array('success' => false, 'message' => 'This method must be called via POST.');
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
	function logout() : bool
	{
		global $logger;
		$logger->log("UserAPI/logout session: " . session_id(), Logger::LOG_DEBUG);
		UserAccount::logout();
		return true;
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
	function validateAccount() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

		$result = UserAccount::validateAccount($username, $password);
		if ($result != null) {
			//TODO This needs to be updated to just export public information
			//get rid of data object fields before returning the result
			unset($result->__table);
			unset($result->created);
			unset($result->_DB_DataObject_version);
			unset($result->_database_dsn);
			unset($result->_database_dsn_md5);
			unset($result->_database);
			unset($result->_query);
			unset($result->_DB_resultid);
			unset($result->_resultFields);
			unset($result->_link_loaded);
			unset($result->_join);
			unset($result->_lastError);

			return array('success' => $result);
		} else {
			return array('success' => false);
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
	function getPatronProfile() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

		$user = UserAccount::validateAccount($username, $password);
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

			$numCheckedOut = 0;
			$numHolds = 0;
			$numHoldsAvailable = 0;
			$accountSummary = $user->getAccountSummary();
			$userData->numCheckedOutIls = (int)$accountSummary->numCheckedOut;
			$userData->numHoldsIls =(int) $accountSummary->getNumHolds();
			$userData->numHoldsAvailableIls = (int) ($accountSummary->numAvailableHolds == null ? 0 : $accountSummary->numAvailableHolds);
			$userData->numHoldsRequestedIls = (int) ($accountSummary->numUnavailableHolds == null ? 0 :  $accountSummary->numUnavailableHolds);
			$userData->numOverdue = (int)$accountSummary->numOverdue;
			$userData->finesVal = (float)$accountSummary->totalFines;
			$numCheckedOut += $userData->numCheckedOutIls;
			$numHolds += $userData->numHoldsIls;
			$numHoldsAvailable += $userData->numHoldsAvailableIls;
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)){
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );
			$userData->fines = $currencyFormatter->formatCurrency($userData->finesVal, $currencyCode);

			//Add overdrive data
			if ($user->isValidForEContentSource('overdrive')) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$overDriveSummary = $driver->getAccountSummary($user);
				$userData->numCheckedOutOverDrive = (int)$overDriveSummary->numCheckedOut;
				$userData->numHoldsOverDrive = (int)$overDriveSummary->getNumHolds();
				$userData->numHoldsAvailableOverDrive = (int)$overDriveSummary->numAvailableHolds;
				$numCheckedOut += $userData->numCheckedOutOverDrive;
				$numHolds += $userData->numHoldsOverDrive;
				$numHoldsAvailable += $userData->numHoldsAvailableOverDrive;
			}

			//Add hoopla data
			if ($user->isValidForEContentSource('hoopla')) {
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$hooplaSummary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_Hoopla = (int)$hooplaSummary->numCheckedOut;
				$numCheckedOut += $userData->numCheckedOut_Hoopla;
			}

			//Add cloudLibrary data
			if ($user->isValidForEContentSource('cloud_library')) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				$cloudLibrarySummary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_cloudLibrary = (int)$cloudLibrarySummary->numCheckedOut;
				$userData->numHolds_cloudLibrary = (int)$cloudLibrarySummary->getNumHolds();
				$userData->numHoldsAvailable_cloudLibrary = (int)$cloudLibrarySummary->numAvailableHolds;
				$numCheckedOut += $userData->numCheckedOut_cloudLibrary;
				$numHolds += $userData->numHolds_cloudLibrary;
				$numHoldsAvailable += $userData->numHoldsAvailable_cloudLibrary;
			}

			//Add axis360 data
			if ($user->isValidForEContentSource('axis360')) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				$axis360Summary = $driver->getAccountSummary($user);
				$userData->numCheckedOut_axis360 = (int)$axis360Summary->numCheckedOut;
				$userData->numHolds_axis360 = (int)$axis360Summary->getNumHolds();
				$userData->numHoldsAvailable_axis360 = (int)$axis360Summary->numAvailableHolds;
				$numCheckedOut += $userData->numCheckedOut_axis360;
				$numHolds += $userData->numHolds_axis360;
				$numHoldsAvailable += $userData->numHoldsAvailable_axis360;
			}

			$userData->numCheckedOut = $numCheckedOut;
			$userData->numHolds = $numHolds;
			$userData->numHoldsAvailable = $numHoldsAvailable;

			return array('success' => true, 'profile' => $userData);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronHolds() : array
	{
		global $offlineMode;
		if ($offlineMode) {
			return array('success' => false, 'message' => 'Circulation system is offline');
		} else {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$source = $_REQUEST['source'] ?? 'all';
				$allHolds = $user->getHolds(false, 'sortTitle', 'expire', $source);
				$holdsToReturn = [
					'available' => [],
					'unavailable' => [],
				];
				/**
				 * @var string $key
				 * @var Hold $hold
				 */
				foreach ($allHolds['available'] as $key => $hold){
					$holdsToReturn['available'][$key] = $hold->getArrayForAPIs();
				}
				foreach ($allHolds['unavailable'] as $key => $hold){
					$holdsToReturn['unavailable'][$key] = $hold->getArrayForAPIs();
				}
				return array('success' => true, 'holds' => $holdsToReturn);
			} else {
				return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronHoldsOverDrive() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$eContentHolds = $driver->getHolds($user);
			return array('success' => true, 'holds' => $eContentHolds);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronCheckedOutItemsOverDrive() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$eContentCheckedOutItems = $driver->getCheckouts($user);
			$items = [];
			foreach ($eContentCheckedOutItems as $checkedOutItem){
				$items[] = $checkedOutItem->toArray();
			}
			return array('success' => true, 'items' => $items);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronOverDriveSummary() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$overDriveSummary = $driver->getAccountSummary($user);
			return array('success' => true, 'summary' => $overDriveSummary->toArray());
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronFines() : array
	{
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$fines = $user->getFines();
			$totalOwed = 0;
			foreach ($fines as &$fine) {
				if (isset($fine['amountOutstandingVal'])) {
					$totalOwed += $fine['amountOutstandingVal'];
				}elseif (isset($fine['amountVal'])) {
					$totalOwed += $fine['amountVal'];
				}elseif (isset($fine['amount'])) {
					$totalOwed += $fine['amount'];
				}
				if (array_key_exists('amount', $fine) && array_key_exists('amountOutstanding', $fine)){
					$fine['amountOriginal'] = $fine['amount'];
					$fine['amount'] = $fine['amountOutstanding'];
				}
				if (array_key_exists('amountVal', $fine) && array_key_exists('amountOutstandingVal', $fine)){
					$fine['amountOriginalVal'] = $fine['amountVal'];
					$fine['amountVal'] = $fine['amountOutstandingVal'];
				}
			}
			return array('success' => true, 'fines' => $fines, 'totalOwed' => $totalOwed);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/**
	 * Returns lending options for a patron from OverDrive.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	function getOverDriveLendingOptions() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$driver = new OverDriveDriver();
			$accountDetails = $driver->getOptions($user);
			return array('success' => true, 'lendingOptions' => $accountDetails['lendingPeriods']);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronCheckedOutItems() : array
	{
		global $offlineMode;
		if ($offlineMode) {
			return array('success' => false, 'message' => 'Circulation system is offline');
		} else {
			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$source = $_REQUEST['source'] ?? 'all';
				$allCheckedOut = $user->getCheckouts(false, $source);
				$checkoutsList = [];
				foreach ($allCheckedOut as $checkoutObj){
					$checkoutsList[] = $checkoutObj->getArrayForAPIs();
				}

				return array('success' => true, 'checkedOutItems' => $checkoutsList);
			} else {
				return array('success' => false, 'message' => 'Login unsuccessful');
			}
		}
	}

	function checkoutItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$source = $_REQUEST['itemSource'] ?? null;
		$patron = UserAccount::validateAccount($username, $password);

		if ($patron && !($patron instanceof AspenError)) {
			if ($source == 'overdrive') {
				return $this->checkoutOverDriveItem();
			} else if ($source == 'hoopla') {
				return $this->checkoutHooplaItem();
			} else if ($source == 'cloud_library') {
				return $this->checkoutCloudLibraryItem();
			} else if ($source == 'axis360') {
				return $this->checkoutAxis360Item();
			} else {
				return array('success' => false, 'message' => 'This source does not permit checkouts.');
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	function returnCheckout() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$source = $_REQUEST['itemSource'];
		$patron = UserAccount::validateAccount($username, $password);

		if ($patron && !($patron instanceof AspenError)) {
			if ($source == 'overdrive') {
				return $this->returnOverDriveCheckout();
			} else if ($source == 'hoopla') {
				return $this->returnHooplaItem();
			} else if ($source == 'cloud_library') {
				return $this->returnCloudLibraryItem();
			} else if ($source == 'axis360') {
				return $this->returnAxis360Item();
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	function viewOnlineItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$source = $_REQUEST['itemSource'];
		$patron = UserAccount::validateAccount($username, $password);

		if ($patron && !($patron instanceof AspenError)) {

			if ($source == 'overdrive') {
				if(isset($_REQUEST['isPreview']) && $_REQUEST['isPreview'] == true) {
					return $this->openOverDrivePreview();
				} else {
					return $this->openOverDriveItem();
				}
			} else if ($source == 'hoopla') {
				return $this->openHooplaItem();
			} else if ($source == 'cloud_library') {
				return $this->openCloudLibraryItem();
			} else if ($source == 'axis360') {
				return $this->openAxis360Item();
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful when trying to view online checkout');
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
	function renewCheckout() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$recordId = $_REQUEST['recordId'];
		$itemBarcode = $_REQUEST['itemBarcode'];
		$itemIndex = $_REQUEST['itemIndex'];

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			$renewalMessage = $user->renewCheckout($recordId, $itemBarcode, $itemIndex);
			if ($renewalMessage['success']) {
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('UserAPI', 'successfulRenewals');
			}
			return array('success' => true, 'renewalMessage' => $renewalMessage);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/** @noinspection PhpUnused */
	function renewItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$source = $_REQUEST['itemSource'] ?? null;
		$itemBarcode = $_REQUEST['itemBarcode'] ?? null;

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				$renewalMessage = $user->renewCheckout($user, $itemBarcode);
				if ($renewalMessage['success']) {
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('UserAPI', 'successfulRenewals');
					return array('success' => true, 'renewalMessage' => $renewalMessage);
				} else {
					return array('success' => false, 'renewalMessage' => $renewalMessage);
				}
			} else if ($source == 'overdrive') {
				return $this->renewOverDriveItem();
			} else if ($source == 'cloud_library') {
				return $this->renewCloudLibraryItem();
			} else if ($source == 'axis360') {
				return $this->renewAxis360Item();
			}

		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function renewAll() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$renewalMessage = $user->renewAll();
			$renewalMessage['message'] = array_merge([$renewalMessage['Renewed'] . ' of ' . $renewalMessage['Total'] . ' titles were renewed'],$renewalMessage['message']);
			for ($i = 0; $i < $renewalMessage['Renewed']; $i++){
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('UserAPI', 'successfulRenewals');
			}
			$renewalMessage['renewalMessage'] = $renewalMessage['message'];
			return $renewalMessage;
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function placeHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

		if(isset($_REQUEST['bibId'])){
			$bibId = $_REQUEST['bibId'];
		} else {
			$bibId = $_REQUEST['itemId'];
		}

		if(isset($_REQUEST['itemSource'])) {
			$source = $_REQUEST['itemSource'];
		} else {
			$source = null;
		}

		$patron = UserAccount::validateAccount($username, $password);
		if ($patron && !($patron instanceof AspenError)) {
			global $library;
			if ($library->showHoldButton) {
				if ($source == 'ils' || $source == null) {
					if (isset($_REQUEST['pickupBranch']) || isset($_REQUEST['campus'])) {
						if (isset($_REQUEST['pickupBranch'])) {
							$pickupBranch = trim($_REQUEST['pickupBranch']);
						} else {
							$pickupBranch = trim($_REQUEST['campus']);
						}
						$locationValid = $patron->validatePickupBranch($pickupBranch);
						if (!$locationValid) {
							return array('success' => false, 'message' => translate(['text' => 'This location is no longer available, please select a different pickup location', 'isPublicFacing' => true]));
						}
					} else {
						$pickupBranch = $patron->_homeLocationCode;
					}
					//Make sure that there are not volumes available
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($bibId);
					if ($recordDriver->isValid()) {
						require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
						$volumeDataDB = new IlsVolumeInfo();
						$volumeDataDB->recordId = $recordDriver->getIdWithSource();
						if ($volumeDataDB->find(true)) {
							return array('success' => false, 'message' => translate(['text' => 'You must place a volume hold on this title.']));
						}
					}
					$result = $patron->placeHold($bibId, $pickupBranch);
					$action = $result['api']['action'] ?? null;
					return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
				} else if ($source == 'overdrive') {
					return $this->placeOverDriveHold();
				} else if ($source == 'cloud_library') {
					return $this->placeCloudLibraryHold();
				} else if ($source == 'axis360') {
					return $this->placeAxis360Hold();
				}
			}else{
				return array('success' => false, 'message' => 'Sorry, holds are not currently allowed.');
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	function placeItemHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$bibId = $_REQUEST['bibId'];
		$itemId = $_REQUEST['itemId'];

		$patron = UserAccount::validateAccount($username, $password);
		if ($patron && !($patron instanceof AspenError)) {
			global $library;
			if ($library->showHoldButton) {
				if (isset($_REQUEST['pickupBranch'])) {
					$pickupBranch = trim($_REQUEST['pickupBranch']);
					$locationValid = $patron->validatePickupBranch($pickupBranch);
					if (!$locationValid){
						return array('success' => false, 'message' => translate(['text' => 'This location is no longer available, please select a different pickup location', 'isPublicFacing'=> true]));
					}
				} else {
					$pickupBranch = $patron->_homeLocationCode;
				}
				//Make sure that there are not volumes available
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($bibId);
				if ($recordDriver->isValid()) {
					require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
					$volumeDataDB = new IlsVolumeInfo();
					$volumeDataDB->recordId = $recordDriver->getIdWithSource();
					if ($volumeDataDB->find(true)){
						return array('success' => false, 'message' => translate(['text' => 'You must place a volume hold on this title.']));
					}
				}
				return $patron->placeItemHold($bibId, $itemId, $pickupBranch);
			} else {
				$pickupBranch = $patron->_homeLocationCode;
			}
			return $patron->placeHold($bibId, $pickupBranch);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/** @noinspection PhpUnused */
	function changeHoldPickUpLocation() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$holdId = $_REQUEST['holdId'];
		$newLocation = $_REQUEST['newLocation'];
		$patron = UserAccount::validateAccount($username, $password);
		if ($patron && !($patron instanceof AspenError)) {
			list ($locationId, $locationCode) = explode('_', $newLocation);
			$locationValid = $patron->validatePickupBranch($locationCode);
			if (!$locationValid){
				return array('success' => false, 'message' => translate(['text' => 'This location is no longer available, please select a different pickup location', 'isPublicFacing'=> true]));
			}
			$result = $patron->changeHoldPickUpLocation($holdId, $locationCode);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/** @noinspection PhpUnused */
	function getValidPickupLocations() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$patron = UserAccount::validateAccount($username, $password);
		if ($patron && !($patron instanceof AspenError)) {
			if ($patron->hasIlsConnection()){
				$tmpPickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
				$pickupLocations = [];
				foreach ($tmpPickupLocations as $pickupLocation){
					if (!is_string($pickupLocation)) {
						$pickupLocations[] = $pickupLocation->toArray();
					}
				}
				return array('success' => true, 'pickupLocations' => $pickupLocations);
			}else{
				return array('success' => false, 'message' => 'Patron is not connected to an ILS.');
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function placeOverDriveHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		if ((isset($_REQUEST['overDriveId'])) || (isset($_REQUEST['itemId']))) {
			if(isset($_REQUEST['overDriveId'])){
				$overDriveId = $_REQUEST['overDriveId'];
			} else {
				$overDriveId = $_REQUEST['itemId'];
			}

			$user = UserAccount::validateAccount($username, $password);
			if ($user && !($user instanceof AspenError)) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$result = $driver->placeHold($user, $overDriveId);
				$action = $result['api']['action'] ?? null;
				return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
			} else {
				return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
			}
		} else {
			return array('success' => false, 'message' => 'Please provide the overDriveId to be place the hold on');
		}

	}

	function freezeOverDriveHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		if ((isset($_REQUEST['overDriveId'])) || (isset($_REQUEST['recordId']))) {
			if(isset($_REQUEST['overDriveId'])){
				$overDriveId = $_REQUEST['overDriveId'];
			} else {
				$overDriveId = $_REQUEST['recordId'];
			}

			$reactivationDate = $_REQUEST['reactivationDate'] ?? null;

			$user = UserAccount::validateAccount($username, $password);
			if ($user && !($user instanceof AspenError)) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$result = $driver->freezeHold($user, $overDriveId, $reactivationDate);
				return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
			} else {
				return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
			}
		} else {
			return array('success' => false, 'message' => 'Please provide the overDriveId to be place the hold on');
		}

	}

	function activateOverDriveHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->thawHold($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
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
	function cancelOverDriveHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		if(isset($_REQUEST['overDriveId'])){
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['recordId'];
		}

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->cancelHold($user, $overDriveId);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function renewOverDriveItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		if(isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['recordId'];
		}

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->renewCheckout($user, $overDriveId);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function returnOverDriveCheckout() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

		if(isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['id'];
		}

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->returnCheckout($user, $overDriveId);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
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
	function checkoutOverDriveItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		if(isset($_REQUEST['overDriveId'])) {
			$overDriveId = $_REQUEST['overDriveId'];
		} else {
			$overDriveId = $_REQUEST['itemId'];
		}

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->checkOutTitle($user, $overDriveId);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'],'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function openOverDriveItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$accessLink = $driver->getDownloadLink($overDriveId, $formatId, $patron);
			return array('success' => true, 'title' => 'Download Url', 'url' => $accessLink['downloadUrl']);

		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function openOverDrivePreview() : array
	{
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];

		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
		$recordDriver = new OverDriveRecordDriver($overDriveId);
		if ($recordDriver->isValid()){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$format = new OverDriveAPIProductFormats();
			$format->id = $_REQUEST['formatId'];
			if ($format->find(true)){
				$result['success'] = true;
				if ($_REQUEST['sampleNumber'] == 2){
					$sampleUrl = $format->sampleUrl_2;
				}else{
					$sampleUrl = $format->sampleUrl_1;
				}

				$overDriveDriver = new OverDriveDriver();
				$overDriveDriver->incrementStat('numPreviews');

				$result['url'] = $sampleUrl;
			}else{
				$result['success'] = false;
				$result['title'] = "Error";
				$result['message'] = 'The specified Format was not valid';
			}
		}else{
			$result['success'] = false;
			$result['title'] = "Error";
			$result['message'] = 'The specified OverDrive Product was not valid';
		}

		return $result;
	}

	function updateOverDriveEmail() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

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
				return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
			}

		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function checkoutCloudLibraryItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->checkOutTitle($user, $id);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function renewCloudLibraryItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->renewCheckout($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function placeCloudLibraryHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->placeHold($user, $id);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function cancelCloudLibraryHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->cancelHold($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function returnCloudLibraryItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['id'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$this->recordDriver = new CloudLibraryRecordDriver($id);

			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->returnCheckout($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function openCloudLibraryItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);

			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$driver = new CloudLibraryRecordDriver($id);
			$accessUrl = $driver->getAccessOnlineLinkUrl($patron);

			return array('success' => true, 'title' => 'Download Url', 'url' => $accessUrl);

		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
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
	function checkoutHooplaItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$titleId = $_REQUEST['itemId'];

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$result = $driver->checkOutTitle($user, $titleId);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function returnHooplaItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$titleId = $_REQUEST['id'];

		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$result = $driver->returnCheckout($user, $titleId);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function openHooplaItem() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);

			require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
			$hooplaRecord = new HooplaRecordDriver($id);
			$accessLink = $hooplaRecord->getAccessLink();
			return array('success' => true, 'title' => "Download Url", 'url' => $accessLink['url']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function placeAxis360Hold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->placeHold($user, $id);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function freezeAxis360Hold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->freezeHold($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function activateAxis360Hold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->thawHold($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function cancelAxis360Hold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->cancelHold($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function checkoutAxis360Item() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->checkOutTitle($user, $id);
			$action = $result['api']['action'] ?? null;
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message'], 'action' => $action);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function returnAxis360Item() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['id'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->returnCheckout($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function renewAxis360Item() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$this->recordDriver = new Axis360RecordDriver($id);

			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->renewCheckout($user, $id);
			return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	function openAxis360Item() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$id = $_REQUEST['itemId'];
		$patronId = $_REQUEST['patronId'];

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$patron = $user->getUserReferredTo($patronId);
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$driver = new Axis360RecordDriver($id);
			$accessUrl = $driver->getAccessOnlineLinkUrl($patron);
			return array('success' => true, 'title' => 'Download Url', 'url' => $accessUrl);
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
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
	function cancelHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();

		// Cancel Hold requires one of these, which one depends on the ILS
		$recordId = $_REQUEST['recordId'] ?? null;
		$cancelId = $_REQUEST['cancelId'] ?? null;

		$source = $_REQUEST['itemSource'] ?? null;
		$patron = UserAccount::validateAccount($username, $password);

		if ($patron && !($patron instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				$result = $patron->cancelHold($recordId, $cancelId);
				return array('success' => $result['success'], 'title' => $result['api']['title'], 'message' => $result['api']['message']);
			} else if ($source == 'overdrive') {
				return $this->cancelOverDriveHold();
			} else if ($source == 'cloud_library') {
				return $this->cancelCloudLibraryHold();
			} else if ($source == 'axis360') {
				return $this->cancelAxis360Hold();
			}
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
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
	function freezeHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$source = $_REQUEST['itemSource'] ?? null;

		$user = UserAccount::validateAccount($username, $password);

		if ($user && !($user instanceof AspenError)) {
			$reactivationDate = $_REQUEST['reactivationDate'] ?? null;
			if ($source == 'ils' || $source == null) {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					return array('success' => false, 'message' => 'recordId and holdId must be provided');
				}
				$recordId = $_REQUEST['recordId'];
				$holdId = $_REQUEST['holdId'];
				return $user->freezeHold($recordId, $holdId, $reactivationDate);
			} else if ($source == 'overdrive') {
				return $this->freezeOverDriveHold();
			} else if ($source == 'axis360') {
				return $this->freezeAxis360Hold();
			}

		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	/** @noinspection PhpUnused */
	function freezeAllHolds() {
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			return $user->freezeAllHolds();
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/**
	 * Activates a hold that was previously suspended within the ILS.  Only unavailable holds can be activated.
	 * Note:  Horizon implements suspending and activating holds as a toggle.  If a hold is suspended, it will be activated
	 * and if a hold is active it will be suspended.  Care should be taken when calling the method with holds that are in the wrong state.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - </li>
	 * <li>holdId - </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the hold could be activated, false if the username or password were incorrect or the hold could not be activated.</li>
	 * <li>holdMessage - a reason why the method failed if success is false</li>
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
	function activateHold() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		$source = $_REQUEST['itemSource'] ?? null;

		if ($user && !($user instanceof AspenError)) {
			if ($source == 'ils' || $source == null) {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					return array('success' => false, 'title' => 'Error', 'message' => 'recordId and holdId must be provided');
				} else {
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					return $user->thawHold($recordId, $holdId);
				}
			} else if ($source == 'overdrive') {
				return $this->activateOverDriveHold();
			} else if ($source == 'axis360') {
				return $this->activateAxis360Hold();
			}
		} else {
			return array('success' => false, 'title' => 'Error', 'message' => 'Unable to validate user');
		}
	}

	/** @noinspection PhpUnused */
	function activateAllHolds() {
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			return $user->thawAllHolds();
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function getPatronReadingHistory() : array
	{
		global $offlineMode;
		if ($offlineMode) {
			return array('success' => false, 'message' => 'Circulation system is offline');
		} else {
			list($username, $password) = $this->loadUsernameAndPassword();
			$user = UserAccount::validateAccount($username, $password);
			if ($user && !($user instanceof AspenError)) {
				$readingHistory = $user->getReadingHistory();

				return array('success' => true, 'readingHistory' => $readingHistory['titles']);
			} else {
				return array('success' => false, 'message' => 'Login unsuccessful');
			}
		}
	}

	/** @noinspection PhpUnused */
	function updatePatronReadingHistory() : array
	{
		global $offlineMode;
		if ($offlineMode) {
			return array('success' => false, 'message' => 'Circulation system is offline');
		} else {
			list($username, $password) = $this->loadUsernameAndPassword();
			$user = UserAccount::validateAccount($username, $password);
			if ($user && !($user instanceof AspenError)) {
				$user->updateReadingHistoryBasedOnCurrentCheckouts();

				return array('success' => true);
			} else {
				return array('success' => false, 'message' => 'Login unsuccessful');
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
	function optIntoReadingHistory() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction( 'optIn', array());
			return array('success' => true);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function optOutOfReadingHistory() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction( 'optOut', array());
			return array('success' => true);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function deleteAllFromReadingHistory() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction( 'deleteAll', array());
			return array('success' => true);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function deleteSelectedFromReadingHistory() : array
	{
		list($username, $password) = $this->loadUsernameAndPassword();
		$selectedTitles = $_REQUEST['selected'];
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$user->doReadingHistoryAction( 'deleteMarked', $selectedTitles);
			return array('success' => true);
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	private function loadUsernameAndPassword() : array
	{
		if (isset($_REQUEST['username'])) {
			$username = $_REQUEST['username'];
		} else {
			$username = '';
		}
		if (isset($_REQUEST['password'])) {
			$password = $_REQUEST['password'];
		} else {
			$password = '';
		}
		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return array($username, $password);
	}

	/** @noinspection PhpUnused */
	function getBarcodeForPatron() : array
	{
		$results = array('success' => false, 'message' => 'Unknown error loading barcode');
		if (isset($_REQUEST['patronId'])){
			$user = new User();
			$user->username = $_REQUEST['patronId'];
			if ($user->find(true)){
				$results = array('success' => true, 'barcode' => $user->getBarcode());
			}else{
				$results['message'] = 'Invalid Patron';
			}
		}else if (isset($_REQUEST['id'])){
			$user = new User();
			$user->id = $_REQUEST['id'];
			if ($user->find(true)){
				$results = array('success' => true, 'barcode' => $user->getBarcode());
			}else{
				$results['message'] = 'Invalid Patron';
			}
		}else{
			$results['message'] = 'No patron id was provided';
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function dismissBrowseCategory() : array
	{
		$result = [
			'success' => false,
			'title' => translate(['text' => 'Error updating preferences', 'isPublicFacing' => true]),
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
		];

		list($username, $password) = $this->loadUsernameAndPassword();
		$patronId = $_REQUEST['patronId'];
		$browseCategoryId = $_REQUEST['browseCategoryId'];
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $browseCategoryId;
			if (!$browseCategory->find(true)){
				$result['message'] = translate(['text' => 'Invalid browse category provided, please try again', 'isPublicFacing' => true]);
			}else{
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
				$browseCategoryDismissal = new BrowseCategoryDismissal();
				$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
				$browseCategoryDismissal->userId = $patronId;
				if($browseCategoryDismissal->find(true)) {
					$result['message'] = translate(['text' => 'You already dismissed this browse category', 'isPublicFacing' => true]);
				} else {
					$browseCategoryDismissal->insert();
					$browseCategory->numTimesDismissed += 1;
					$browseCategory->update();
					$result = [
						'success' => true,
						'title' => translate(['text' => 'Preferences updated', 'isPublicFacing' => true]),
						'message' => translate(['text' => 'Browse category has been hidden', 'isPublicFacing' => true])
					];
				}
			}
		} else {
			$result['message'] = translate(['text'=>'Incorrect user information, please login again', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function showBrowseCategory() : array
	{
		$result = [
			'success' => false,
			'title' => translate(['text' => 'Error updating preferences', 'isPublicFacing' => true]),
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
		];

		list($username, $password) = $this->loadUsernameAndPassword();
		$patronId = $_REQUEST['patronId'];
		$browseCategoryId = $_REQUEST['browseCategoryId'];
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $browseCategoryId;
			if (!$browseCategory->find(true)){
				$result['message'] = translate(['text' => 'Invalid browse category provided, please try again', 'isPublicFacing' => true]);
			}else{
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
				$browseCategoryDismissal = new BrowseCategoryDismissal();
				$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
				$browseCategoryDismissal->userId = $patronId;
				if($browseCategoryDismissal->find(true)) {
					$browseCategoryDismissal->delete();
					$result = [
						'success' => true,
						'title' => translate(['text' => 'Preferences updated', 'isPublicFacing' => true]),
						'message' => translate(['text' => 'Browse category will be visible again', 'isPublicFacing' => true])
					];
				} else {
					$result['message'] = translate(['text' => 'You already have this browse category visible', 'isPublicFacing' => true]);
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getHiddenBrowseCategories() : array {
		$result = [
			'success' => false,
			'title' => translate(['text' => 'Error updating preferences', 'isPublicFacing' => true]),
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
		];

		list($username, $password) = $this->loadUsernameAndPassword();
		$patronId = $_REQUEST['patronId'];
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$hiddenCategories = [];
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissals = new BrowseCategoryDismissal();
			$browseCategoryDismissals->userId = $patronId;
			$browseCategoryDismissals->find();
			while($browseCategoryDismissals->fetch()) {
				$hiddenCategories[] = clone($browseCategoryDismissals);
			}

			if($browseCategoryDismissals->count() > 0) {
				$categories = [];
				foreach($hiddenCategories as $hiddenCategory) {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
					$browseCategory = new BrowseCategory();
					$browseCategory->textId = $hiddenCategory->browseCategoryId;
					if($browseCategory->find(true)){
						$category['id'] = $browseCategory->textId;
						$category['name'] = $browseCategory->label;
						$category['description'] = $browseCategory->description;
						$categories[] = $category;
					}
				}
				$result = [
					'success' => true,
					'title' => translate(['text' => 'Your hidden categories', 'isPublicFacing' => true]),
					'message' => translate(['text' => 'You currently have these categories hidden', 'isPublicFacing' => true]),
					'categories' => $categories,
				];
			} else {
				$result = [
					'message' => translate(['text' => 'You have no hidden browse categories', 'isPublicFacing' => true]),
				];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getUserByBarcode() : array
	{
		$results = array('success' => false, 'message' => 'Unknown error loading patronId');
		if (isset($_REQUEST['username'])){
			$user = UserAccount::getUserByBarcode($_REQUEST['username']);
			if ($user != false){
				$results = array('success' => true, 'id' => $user->id, 'patronId' => $user->username, 'displayName' => $user->displayName);
			}else{
				$results['message'] = 'Invalid Patron';
			}
		}else{
			$results['message'] = 'No barcode was provided';
		}
		return $results;
	}

	/**
	 * @return bool|User
	 */
	protected function getUserForApiCall()
	{
		if (isset($_REQUEST['patronId'])) {
			$user = new User();
			$user->username = $_REQUEST['patronId'];
			if (!$user->find(true)) {
				$user = false;
			}
		} else if (isset($_REQUEST['id'])) {
			$user = new User();
			$user->id = $_REQUEST['id'];
			if (!$user->find(true)) {
				$user = false;
			}
		} else {
			list($username, $password) = $this->loadUsernameAndPassword();
			$user = UserAccount::validateAccount($username, $password);
		}
		return $user;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}