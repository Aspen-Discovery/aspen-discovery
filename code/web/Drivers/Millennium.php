<?php
require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class Millennium extends AbstractIlsDriver
{
	/** @var  Solr */
	public $db;

	/** @var CurlWrapper */
	var $curlWrapper;

	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		$this->curlWrapper = new CurlWrapper();
	}

	public function getMillenniumScope(){
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)){
			if ($searchLocation->useScope && $searchLocation->restrictSearchByLocation){
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)){
			return $branchScope;
		}else if (isset($searchLibrary) && $searchLibrary->useScope && $searchLibrary->restrictSearchByLibrary) {
			return $searchLibrary->scope;
		}else{
			return $this->getDefaultScope();
		}
	}

	public function getLibraryScope(){
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)){
			if (isset($searchLocation->scope) && $searchLocation->scope > 0){
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)){
			return $branchScope;
		}else if (isset($searchLibrary) && isset($searchLibrary->scope) && $searchLibrary->scope > 0) {
			return $searchLibrary->scope;
		}else{
			return $this->getDefaultScope();
		}
	}

	public function getDefaultScope(){
		global $configArray;
		return isset($configArray['OPAC']['defaultScope']) ? $configArray['OPAC']['defaultScope'] : '93';
	}

	public function getMillenniumRecordInfo($id){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCache.php';
		$scope = $this->getMillenniumScope();
		//Load the pages for holdings, order information, and items
		$millenniumCache = new MillenniumCache();
		$millenniumCache->recordId = $id;
		$millenniumCache->scope = $scope;
		global $timer;
		$host = $this->getVendorOpacUrl();

		//If we get an identifier type, strip that
		if (strpos($id, ':') > 0){
			$id = substr($id, strpos($id, ':') + 1);
		}
		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/holdings~" . $id_;
		$millenniumCache->holdingsInfo = file_get_contents($req);
		//$logger->log("Loaded holdings from url $req", Logger::LOG_DEBUG);
		$timer->logTime('got holdings from millennium');

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/frameset~" . $id_;
		$millenniumCache->framesetInfo = file_get_contents($req);
		$timer->logTime('got frameset info from millennium');

		$millenniumCache->cacheDate = time();

		return $millenniumCache;

	}

	static $scopingLocationCode = null;

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 * Interface defined in CatalogConnection.php
	 *
	 * @param   string  $username         The patron username
	 * @param   string  $password         The patron password
	 * @param   boolean $validatedViaSSO  True if the patron has already been validated via SSO.  If so we don't need to validation, just retrieve information
	 *
	 * @return  User|null           A string of the user's ID number
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	public function patronLogin($username, $password, $validatedViaSSO) {
		global $timer;

		//Get the barcode property
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			$barcode = $username;
		}else{
			$barcode = $password;
		}

		//Strip any non digit characters from the password
		//Can't do this any longer since some libraries do have characters in their barcode:
		//$password = preg_replace('/[a-or-zA-OR-Z\W]/', '', $password);
		//Remove any spaces from the barcode
		//ARL-153 remove spaces from the barcode
		$barcode = preg_replace('/[^a-zA-Z\d]/', '', trim($barcode));

		//Load the raw information about the patron
		$patronDump = $this->_getPatronDump($barcode);
		//$logger->log("Retrieved patron dump for $barcode\r\n" . print_r($patronDump, true), Logger::LOG_DEBUG);

		//Create a variety of possible name combinations for testing purposes.
		$userValid = false;
		//Break up the patron name into first name, last name and middle name based on the
		if ($validatedViaSSO) {
			$userValid = true;
		}else{
			if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
				$userValid = $this->_doPinTest($barcode, $password);
			}else{
				if (isset($patronDump['PATRN_NAME'])){
					$patronName = $patronDump['PATRN_NAME'];
					list(, , , $userValid) = $this->validatePatronName($username, $patronName);
				}
			}
		}

		if ($userValid) {
			if (!isset($patronName) || $patronName == null) {
				if (isset($patronDump['PATRN_NAME'])) {
					$patronName = $patronDump['PATRN_NAME'];
					$this->validatePatronName($username, $patronName);
				}
			}

			$user = $this->createPatronFromPatronDump($patronDump, $password);

			$timer->logTime("Patron logged in successfully");
			return $user;

		} else {
			$timer->logTime("Patron login failed");
			return null;
		}
	}

	public function loadContactInformation(User $user)
	{
		$barcode = $user->getBarcode();
		$patronDump = $this->_getPatronDump($barcode);
		$this->loadContactInformationFromPatronDump($user, $patronDump);
	}

	public function loadContactInformationFromPatronDump(User $user, $patronDump){
		//Setup home location
		$location = null;
		if (isset($patronDump['HOME_LIBR']) || isset($patronDump['HOLD_LIBR'])) {
			$homeBranchCode = isset($patronDump['HOME_LIBR']) ? $patronDump['HOME_LIBR'] : $patronDump['HOLD_LIBR'];
			$homeBranchCode = str_replace('+', '', $homeBranchCode); //Translate home branch to plain text
			$location = new Location();
			$location->code = $homeBranchCode;
			if (!$location->find(true)) {
				unset($location);
			}
		} else {
			global $logger;
			$logger->log('Millennium Driver: No Home Library Location or Hold location found in patron dump. User : ' . $user->id, Logger::LOG_ERROR);
			// The code below will attempt to find a location for the library anyway if the homeLocation is already set
		}

		if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
			if (empty($user->homeLocationId) && !isset($location)) {
				// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
				// try to find the main branch to assign to user
				// or the first location for the library
				global $library;

				$location = new Location();
				$location->libraryId = $library->libraryId;
				$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
				if (!$location->find(true)) {
					// Seriously no locations even?
					global $logger;
					$logger->log('Failed to find any location to assign to user as home location', Logger::LOG_ERROR);
					unset($location);
				}
			}
			if (isset($location)) {
				$user->homeLocationId = $location->locationId;
				if (empty($user->myLocation1Id)) {
					$user->myLocation1Id = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
					/** @var /Location $location */
					//Get display name for preferred location 1
					$myLocation1 = new Location();
					$myLocation1->locationId = $user->myLocation1Id;
					if ($myLocation1->find(true)) {
						$user->_myLocation1 = $myLocation1->displayName;
					}
				}

				if (empty($user->myLocation2Id)) {
					$user->myLocation2Id = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
					//Get display name for preferred location 2
					$myLocation2 = new Location();
					$myLocation2->locationId = $user->myLocation2Id;
					if ($myLocation2->find(true)) {
						$user->_myLocation2 = $myLocation2->displayName;
					}
				}
			}
		}

		if (isset($location)) {
			//Get display names that aren't stored
			$user->_homeLocationCode = $location->code;
			$user->_homeLocation = $location->displayName;
		}

		$user->_expired = 0; // default setting
		$user->_expireClose = 0;
		//See if expiration date is close
		if (trim($patronDump['EXP_DATE']) != '-  -') {
			$user->_expires = $patronDump['EXP_DATE'];
			list ($monthExp, $dayExp, $yearExp) = explode("-", $patronDump['EXP_DATE']);
			$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
			$timeNow = time();
			$timeToExpire = $timeExpire - $timeNow;
			if ($timeToExpire <= 30 * 24 * 60 * 60) {
				if ($timeToExpire <= 0) {
					$user->_expired = 1;
				}
				$user->_expireClose = 1;
			}
		}

		//Get additional information that doesn't necessarily get stored in the User Table
		if (isset($patronDump['ADDRESS'])) {
			$fullAddress = $patronDump['ADDRESS'];
			$addressParts = explode('$', $fullAddress);
			$user->_address1 = $addressParts[0];
			$user->_city = isset($addressParts[1]) ? $addressParts[1] : '';
			$user->_state = isset($addressParts[2]) ? $addressParts[2] : '';
			$user->_zip = isset($addressParts[3]) ? $addressParts[3] : '';

			if (preg_match('/(.*?),\\s+(.*)\\s+(\\d*(?:-\\d*)?)/', $user->_city, $matches)) {
				$user->_city = $matches[1];
				$user->_state = $matches[2];
				$user->_zip = $matches[3];
			} else if (preg_match('/(.*?)\\s+(\\w{2})\\s+(\\d*(?:-\\d*)?)/', $user->_city, $matches)) {
				$user->_city = $matches[1];
				$user->_state = $matches[2];
				$user->_zip = $matches[3];
			}
		} else {
			$user->_address1 = "";
			$user->_city = "";
			$user->_state = "";
			$user->_zip = "";
		}

		$user->_address2 = $user->_city . ', ' . $user->_state;
		$user->_workPhone = (isset($patronDump) && isset($patronDump['G/WK_PHONE'])) ? $patronDump['G/WK_PHONE'] : '';
		if (isset($patronDump) && isset($patronDump['MOBILE_NO'])) {
			$user->_mobileNumber = $patronDump['MOBILE_NO'];
		} else {
			if (isset($patronDump) && isset($patronDump['MOBILE_PH'])) {
				$user->_mobileNumber = $patronDump['MOBILE_PH'];
			} else {
				$user->_mobileNumber = '';
			}
		}

		$user->_finesVal = floatval(preg_replace('/[^\\d.]/', '', $patronDump['MONEY_OWED']));
		$user->_fines = $patronDump['MONEY_OWED'];

		$noticeLabels = array(
			//'-' => 'Mail',  // officially None in Sierra, as in No Preference Selected.
			'-' => '',  // notification will generally be based on what information is available so can't determine here. plb 12-02-2014
			'a' => 'Mail', // officially Print in Sierra
			'p' => 'Telephone',
			'z' => 'Email',
		);
		$user->_notices = isset($patronDump) ? $patronDump['NOTICE_PREF'] : '-';
		if (array_key_exists($user->_notices, $noticeLabels)) {
			$user->_noticePreferenceLabel = $noticeLabels[$user->_notices];
		} else {
			$user->_noticePreferenceLabel = 'Unknown';
		}
	}

	/**
	 * Get a dump of information from Millennium that can be used in other
	 * routines.
	 *
	 * @param string  $barcode the patron's barcode
	 * @param boolean $forceReload whether or not cached data can be used.
	 * @return array
	 */
	public function _getPatronDump(&$barcode, $forceReload = false)
	{
		global $configArray;
		global $memCache;
		global $library;
		global $timer;

		$patronDump = $memCache->get("patron_dump_$barcode");
		if (!$patronDump || $forceReload){
			$host = isset($this->accountProfile->patronApiUrl) ? $this->accountProfile->patronApiUrl : null; // avoid warning notices
			if ($host == null) {
				echo("Patron API URL must be defined in the account profile to work with the Millennium API");
				die();
			}
			$barcodesToTest = array();
			$barcodesToTest[] = $barcode;

			//Special processing to allow users to login with short barcodes
			if ($library){
				if ($library->barcodePrefix){
					if (strpos($barcode, $library->barcodePrefix) !== 0){
						//Add the barcode prefix to the barcode
						$barcodesToTest[] = $library->barcodePrefix . $barcode;
					}
				}
			}

			//Special processing to allow MCVSD Students to login
			//with their student id.
			if (strlen($barcode)== 5){
				$barcodesToTest[] = "41000000" . $barcode;
				$barcodesToTest[] = "mv" . $barcode;
			}elseif (strlen($barcode)== 6){
				$barcodesToTest[] = "4100000" . $barcode;
				$barcodesToTest[] = "mv" . $barcode;
			}

			foreach ($barcodesToTest as $i=>$barcode){
				$patronDump = $this->_parsePatronApiPage($host, $barcode);

				if (is_null($patronDump)){
					return $patronDump;
				}/** @noinspection PhpStatementHasEmptyBodyInspection */ elseif ((isset($patronDump['ERRNUM']) || count($patronDump) == 0) && $i != count($barcodesToTest) - 1){
					//check the next barcode
				}else{
					$timer->logTime('Finished loading patron dump from ILS.');
					$memCache->set("patron_dump_$barcode", $patronDump, $configArray['Caching']['patron_dump']);
					//Need to wait a little bit since getting the patron api locks the record in the DB
					usleep(250);
					break;
				}
			}

		} else {
			$timer->logTime('Loaded Patron Dump from memcache');
		}
		return $patronDump;
	}

	private function _parsePatronApiPage($host, $barcode){
		global $timer;
		// Load Record Page.  This page has a dump of all patron information
		//as a simple name value pair list within the body of the webpage.
		//Sample format of a row is as follows:
		//P TYPE[p47]=100<BR>
		$patronApiUrl =  $host . "/PATRONAPI/" . $barcode ."/dump" ;
		$result = $this->curlWrapper->curlGetPage($patronApiUrl);

		//Strip the actual contents out of the body of the page.
		//Periodically we get HTML like characters within the notes so strip tags breaks the page.
		//We really just need to remove the following tags:
		// <html>
		// <body>
		// <br>
		$cleanPatronData = preg_replace('/<(html|body|br)\s*\/?>/i', '', $result);
		//$cleanPatronData = strip_tags($result);

		//Add the key and value from each row into an associative array.
		$patronDump = array();
		preg_match_all('/(.*?)\\[.*?\\]=(.*)/', $cleanPatronData, $patronData, PREG_SET_ORDER);
		for ($curRow = 0; $curRow < count($patronData); $curRow++) {
			$patronDumpKey = str_replace(" ", "_", trim($patronData[$curRow][1]));
			switch ($patronDumpKey) {
				// multiple entries
				case 'HOLD' :
				case 'BOOKING' :
					$patronDump[$patronDumpKey][] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
					break;
				// single entries
				default :
					if (!array_key_exists($patronDumpKey, $patronDump)) {
						$patronDump[$patronDumpKey] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
					}
			}
		}

		$timer->logTime("Got patron information from Patron API for $barcode");
		return $patronDump;
	}

	public function _curl_login(User $patron) {
		global $logger;
		$loginResult = false;

		$curlUrl   = $this->getVendorOpacUrl() . "/patroninfo/";
		$post_data = $this->_getLoginFormValues($patron);

		$logger->log('Loading page ' . $curlUrl, Logger::LOG_NOTICE);

		$loginResponse = $this->curlWrapper->curlPostPage($curlUrl, $post_data);
		$curlInfo = curl_getinfo($this->curlWrapper->curl_connection);
		$redirectUrl = $curlInfo['url'];

		//When a library uses IPSSO, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResponse, $loginMatches)) {
			$lt = $loginMatches[1]; //Get the lt value
			//Login again
			$post_data['lt']       = $lt;
			$post_data['_eventId'] = 'submit';

			//Don't issue a post, just call the same page (with redirects as needed)
			$loginResponse = $this->curlWrapper->curlPostPage($redirectUrl, $post_data);
		}

		if ($loginResponse) {
			$loginResult = true;

			// Check for Login Error Responses
			$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/is', $loginResponse, $matches);
			if ($numMatches > 0) {
				$logger->log('Millennium Curl Login Attempt received an Error response : ' . $matches['error'], Logger::LOG_DEBUG);
				$loginResult = false;
			} else {

				// Pause briefly after logging in as some follow-up millennium operations (done via curl) will fail if done too quickly
				usleep(150000);
			}
		}

		return $loginResult;
	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 * @return Checkout[]        Array of the patron's transactions on success,
	 * AspenError otherwise.
	 * @access public
	 */
	public function getCheckouts(User $patron) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		return $millenniumCheckouts->getCheckouts($patron, $this->getIndexingProfile());
	}

	/**
	 * Return a page from classic with comments stripped
	 *
	 * @param $patron             User The unique identifier for the patron
	 * @param $page               string The page to be loaded
	 * @return string             The page from classic
	 */
	public function _fetchPatronInfoPage($patron, $page){
		//First we have to login to classic
		if ($this->_curl_login($patron)) {
			$scope = $this->getDefaultScope();

			//Now we can get the page
			$curlUrl      = $this->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username . "/$page";
			$curlResponse = $this->curlWrapper->curlGetPage($curlUrl);

			//Strip HTML comments
			$curlResponse = preg_replace("/<!--([^(-->)]*)-->/", " ", $curlResponse);
			return $curlResponse;
		}
		return false;
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumReadingHistory.php';
		$millenniumReadingHistory = new MillenniumReadingHistory($this);
		return $millenniumReadingHistory->getReadingHistory($patron, $page, $recordsPerPage, $sortOption);
	}

	public function performsReadingHistoryUpdatesOfILS(){
		return true;
	}
	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   User    $patron
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumReadingHistory.php';
		$millenniumReadingHistory = new MillenniumReadingHistory($this);
		$millenniumReadingHistory->doReadingHistoryAction($patron, $action, $selectedTitles);
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron    The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getHolds($patron) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->getHolds($patron, $this->getIndexingProfile());
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   User    $patron       The User to place a hold for
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @param   null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		$result = $this->placeItemHold($patron, $recordId, '', $pickupBranch, $cancelDate);
		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User    $patron     The User to place a hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @param   null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate);
	}

	/**
	 * Place Volume Hold
	 *
	 * This is responsible for both placing volume level holds.
	 *
	 * @param   User    $patron         The User to place a hold for
	 * @param   string  $recordId       The id of the bib record
	 * @param   string  $volumeId       The id of the volume to hold
	 * @param   string  $pickupBranch   The branch where the user wants to pickup the item when available
	 * @return  mixed                   True if successful, false if unsuccessful
	 *                                  If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch);
	}

	public function cancelHold($patron, $recordId, $cancelId = null, $isIll = false) : array{
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'cancel', null, $cancelId, $this->getIndexingProfile(), '', '');
	}

	function allowFreezingPendingHolds(){
		return false;
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', null, $itemToFreezeId, $this->getIndexingProfile(), '', 'on');
	}

	function thawHold($patron, $recordId, $itemToThawId) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', null, $itemToThawId, $this->getIndexingProfile(), '', 'off');
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', null, $itemToUpdateId, $this->getIndexingProfile(), $newPickupLocation, null); // freeze value of null gets us to change  pickup location
	}

	public function hasFastRenewAll() : bool{
		return true;
	}

	public function renewAll(User $patron){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		return $millenniumCheckouts->renewAll($patron);
	}

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		$result = $millenniumCheckouts->renewCheckout($patron, $itemId, $itemIndex);
		// If we get an account busy error let's try again a few times after a delay
		$numTries = 1;
		while (!$result['success'] && (strpos($result['message'], 'your account is in use by the system.') || stripos($result['message'], 'n use by system.')) && $numTries < 4) {
			usleep(400000);
			$numTries++;
			$result = $millenniumCheckouts->renewCheckout($patron, $itemId, $itemIndex);
			if (!$result['success'] && (strpos($result['message'], 'your account is in use by the system.') || stripos($result['message'], 'n use by system.'))) {
				global $logger;
				$logger->log("System still busy after $numTries attempts at renewal", Logger::LOG_ERROR);
			}
		}
		return $result;
	}

	/**
	 * @param User $patron                     The User Object to make updates to
	 * @param boolean $canUpdateContactInfo  Permission check that updating is allowed
	 * @param boolean $fromMasquerade
	 * @return array                         Array of error messages for errors that occurred
	 */
	public function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade) : array{
		$result = [
			'success' => false,
			'messages' => []
		];

		if ($canUpdateContactInfo){
			//Setup the call to Millennium
			$barcode = $this->_getBarcode($patron);
			$patronDump = $this->_getPatronDump($barcode);

			//Update profile information
			$extraPostInfo = array();
			if (isset($_REQUEST['address1'])){
				$extraPostInfo['addr1a'] = $_REQUEST['address1'];
				$extraPostInfo['addr1b'] = $_REQUEST['city'] . ', ' . $_REQUEST['state'] . ' ' . $_REQUEST['zip'];
				$extraPostInfo['addr1c'] = '';
				$extraPostInfo['addr1d'] = '';
			}
			if (isset($_REQUEST['phone'])) {
				$extraPostInfo['tele1'] = $_REQUEST['phone'];
			}
			if (isset($_REQUEST['workPhone'])){
				$extraPostInfo['tele2'] = $_REQUEST['workPhone'];
			}
			if (isset($_REQUEST['email'])) {
				$extraPostInfo['email'] = $_REQUEST['email'];
			}

			if (!empty($_REQUEST['pickupLocation'])){
				$pickupLocation = $_REQUEST['pickupLocation'];
				if (strlen($pickupLocation) < 5){
					$pickupLocation = $pickupLocation . str_repeat(' ', 5 - strlen($pickupLocation));
				}
				$extraPostInfo['locx00'] = $pickupLocation;
			}

			if (isset($_REQUEST['notices'])){
				$extraPostInfo['notices'] = $_REQUEST['notices'];
			}

			if (isset($_REQUEST['username'])){
				$extraPostInfo['user_name'] = $_REQUEST['username'];
			}

			if (isset($_REQUEST['mobileNumber'])){
				$extraPostInfo['mobile'] = preg_replace('/\D/', '', $_REQUEST['mobileNumber']);
				if (strlen($_REQUEST['mobileNumber']) > 0 && $_REQUEST['smsNotices'] == 'on'){
					$extraPostInfo['optin'] = 'on';
					global $library;
					if ($library->addSMSIndicatorToPhone){
						//If the user is using SMS notices append TEXT ONLY to the primary phone number
						if (strpos($extraPostInfo['tele1'], '### TEXT ONLY') !== 0) {
							if (strpos($extraPostInfo['tele1'], 'TEXT ONLY') !== 0){
								$extraPostInfo['tele1'] = str_replace('TEXT ONLY ', '', $extraPostInfo['tele1']);
							}
							$extraPostInfo['tele1'] = '### TEXT ONLY ' . $extraPostInfo['tele1'];
						}

					}
				}else{
					$extraPostInfo['optin'] = 'off';
					$extraPostInfo['mobile'] = "";
					global $library;
					if ($library->addSMSIndicatorToPhone){
						if (strpos($extraPostInfo['tele1'], '### TEXT ONLY') === 0){
							$extraPostInfo['tele1'] = str_replace('### TEXT ONLY ', '', $extraPostInfo['tele1']);
						}else if (strpos($extraPostInfo['tele1'], 'TEXT ONLY') === 0){
							$extraPostInfo['tele1'] = str_replace('TEXT ONLY ', '', $extraPostInfo['tele1']);
						}
					}
				}
			}

			//Validate we have required info for notices
			if (isset($extraPostInfo['notices'])){
				if ($extraPostInfo['notices'] == 'z' && strlen($extraPostInfo['email']) == 0){
					$result['messages'][] = 'To receive notices by email you must set an email address.';
				}elseif ($extraPostInfo['notices'] == 'p' && strlen($extraPostInfo['tele1']) == 0){
					$result['messages'][] = 'To receive notices by phone you must provide a telephone number.';
				}elseif (strlen($extraPostInfo['addr1a']) == 0 || strlen($extraPostInfo['addr1b']) == 0){
					$result['messages'][] = 'To receive notices by mail you must provide a complete mailing address.';
				}
				if (count($result['messages']) > 0){
					return $result;
				}
			}

			//Login to the patron's account
			$this->_curl_login($patron);

			//Issue a post request to update the patron information
			$scope = $this->getMillenniumScope();
			$curl_url = $this->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/modpinfo";
			$sresult = $this->curlWrapper->curlPostPage($curl_url, $extraPostInfo);

			// Update Patron Information on success
			if (isset($sresult) && strpos($sresult, 'Patron information updated') !== false){
				$patron->phone = $_REQUEST['phone'];
				$patron->email = $_REQUEST['email'];
				$patron->update();
				global $memCache;
				$memCache->delete("patron_dump_$barcode"); // because the update will affect the patron dump information also clear that cache as well
				$result['success'] = true;
				$result['messages'][] = 'Your account was updated successfully.';
			}else{
				// Doesn't look like the millennium (actually sierra) server ever provides error messages. plb 4-29-2015
				if (preg_match('/<h2 class="errormessage">(.*?)<\/h2>/i', $sresult, $errorMatches)){
					$errorMsg = $errorMatches[1]; // generic error message
				}else{
					$errorMsg = 'There were errors updating your information.'; // generic error message
				}

				$result['messages'][] = $errorMsg;
			}
		} else {
			$result['messages'][] = 'You can not update your information.';
		}
		return $result;
	}

	/**
	 * @param null|User $patron
	 * @return mixed
	 */
	public function _getBarcode($patron = null){
		if ($patron == null){
			$patron = UserAccount::getLoggedInUser();
		}
		if ($patron){
			return $patron->getBarcode();
		}else{
			return '';
		}
	}

	public function hasIssueSummaries(){
		return true;
	}

	/**
	 * Checks millennium to determine if there are issue summaries available.
	 * If there are issue summaries available, it will return them in an array.
	 * With holdings below them.
	 *
	 * If there are no issue summaries, null will be returned from the summary.
	 *
	 * @param string $id
	 * @return mixed - array or null
	 */
	public function getIssueSummaries($id){
		$millenniumInfo = $this->getMillenniumRecordInfo($id);
		//Issue summaries are loaded from the main record page.

		if (preg_match('/class\\s*=\\s*\\"bibHoldings\\"/s', $millenniumInfo->framesetInfo)){
			//There are issue summaries available
			//Extract the table with the holdings
			$issueSummaries = array();
			$matches = array();
			if (preg_match('/<table\\s.*?class=\\"bibHoldings\\">(.*?)<\/table>/s', $millenniumInfo->framesetInfo, $matches)) {
				$issueSummaryTable = trim($matches[1]);
				//Each holdingSummary begins with a holdingsDivider statement
				$summaryMatches = explode('<tr><td colspan="2"><hr  class="holdingsDivider" /></td></tr>', $issueSummaryTable);
				if (count($summaryMatches) > 1){
					//Process each match independently
					foreach ($summaryMatches as $summaryData){
						$summaryData = trim($summaryData);
						if (strlen($summaryData) > 0){
							//Get each line within the summary
							$issueSummary = array();
							$issueSummary['type'] = 'issueSummary';
							$summaryLines = array();
							preg_match_all('/<tr\\s*>(.*?)<\/tr>/s', $summaryData, $summaryLines, PREG_SET_ORDER);
							for ($matchi = 0; $matchi < count($summaryLines); $matchi++) {
								$summaryLine = trim(str_replace('&nbsp;', ' ', $summaryLines[$matchi][1]));
								$summaryCols = array();
								if (preg_match('/<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>/s', $summaryLine, $summaryCols)) {
									$label = trim($summaryCols[1]);
									$value = trim(strip_tags($summaryCols[2]));
									//Check to see if this has a link to a check-in grid.
									if (preg_match('/.*?<a href="(.*?)">.*/s', $label, $linkData)) {
										//Parse the check-in id
										$checkInLink = $linkData[1];
										if (preg_match('/\/search~S\\d+\\?\/.*?\/.*?\/.*?\/(.*?)&.*/', $checkInLink, $checkInGridInfo)) {
											$issueSummary['checkInGridId'] = $checkInGridInfo[1];
										}
										$issueSummary['checkInGridLink'] = 'http://www.millenium.marmot.org' . $checkInLink;
									}
									//Convert to camel case
									$label = (preg_replace('/[^\\w]/', '', strip_tags($label)));
									$label = strtolower(substr($label, 0, 1)) . substr($label, 1);
									if ($label == 'location'){
										//Try to trim the courier code if any
										if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $value, $locationParts)){
											$value = $locationParts[1];
										}
									}elseif ($label == 'holdings'){
										//Change the lable to avoid conflicts with actual holdings
										$label = 'holdingStatement';
									}
									$issueSummary[$label] = $value;
								}
							}
							$issueSummaries[$issueSummary['location'] . count($issueSummaries)] = $issueSummary;
						}
					}
				}
			}

			return $issueSummaries;
		}else{
			return null;
		}
	}

	function getCheckInGrid($id, $checkInGridId){
		//Issue summaries are loaded from the main record page.
		global $configArray;

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		// Load Record Page
		if (substr($configArray['Catalog']['url'], -1) == '/') {
			$host = substr($configArray['Catalog']['url'], 0, -1);
		} else {
			$host = $configArray['Catalog']['url'];
		}

		$branchScope = $this->getMillenniumScope();
		$req =  $host . "/search~S{$branchScope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/$checkInGridId&FF=1,0,";
		$result = file_get_contents($req);

		//Extract the actual table
		$checkInData = array();
		if (preg_match('/<table  class="checkinCardTable">(.*?)<\/table>/s', $result, $matches)) {
			$checkInTable = trim($matches[1]);

			//Extract each item from the grid.
			preg_match_all('/.*?<td valign="top" class="(.*?)">(.*?)<\/td>/s', $checkInTable, $checkInCellMatch, PREG_SET_ORDER);
			for ($matchi = 0; $matchi < count($checkInCellMatch); $matchi++) {
				$checkInCell = array();
				$checkInCell['class'] = $checkInCellMatch[$matchi][1];
				$cellData = trim($checkInCellMatch[$matchi][2]);
				//Load issue date, status, date received, issue number, copies received
				if (preg_match('/(.*?)<br\\s*\/?>.*?<span class="(?:.*?)">(.*?)<\/span>.*?on (\\d{1,2}-\\d{1,2}-\\d{1,2})<br\\s*\/?>(.*?)(?:<!-- copies --> \\((\\d+) copy\\))?<br\\s*\/?>/s', $cellData, $matches)) {
					$checkInCell['issueDate'] = trim($matches[1]);
					$checkInCell['status'] = trim($matches[2]);
					$checkInCell['statusDate'] = trim($matches[3]);
					$checkInCell['issueNumber'] = trim($matches[4]);
					if (isset($matches[5])){
						$checkInCell['copies'] = trim($matches[5]);
					}
				}
				$checkInData[] = $checkInCell;
			}
		}
		return $checkInData;
	}

	function combineCityStateZipInSelfRegistration(){
		return true;
	}
	function selfRegister(){
		global $logger;
		global $library;

		$firstName = trim($_REQUEST['firstName']);
		$middleName = trim($_REQUEST['middleName']);
		$lastName = trim($_REQUEST['lastName']);
		$address = trim($_REQUEST['address']);
		$city = trim($_REQUEST['city']);
		$state = trim($_REQUEST['state']);
		$zip = trim($_REQUEST['zip']);
		$email = trim($_REQUEST['email']);

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $this->getVendorOpacUrl() . "/selfreg~S" . $this->getLibraryScope();
		$logger->log('Loading page ' . $curl_url, Logger::LOG_NOTICE);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);

		$post_data['nfirst'] = $middleName ? $firstName.' '.$middleName : $firstName; // add middle name onto first name;
		$post_data['nlast'] = $lastName;
		$post_data['stre_aaddress'] = $address;
		if ($this->combineCityStateZipInSelfRegistration()){
			$post_data['city_aaddress'] = "$city, $state $zip";
		}else{
			$post_data['city_aaddress'] = "$city";
			$post_data['stat_aaddress'] = "$state";
			$post_data['post_aaddress'] = "$zip";
		}

		$post_data['zemailaddr'] = $email;
		if (isset($_REQUEST['phone'])){
			$phone = trim($_REQUEST['phone']);
			$post_data['tphone1'] = $phone;
		}
		if (isset($_REQUEST['birthDate'])){
			$post_data['F051birthdate'] = $_REQUEST['birthDate'];
		}
		if (isset($_REQUEST['universityID'])){
			$post_data['universityID'] = $_REQUEST['universityID'];
		}

		if ($library->selfRegistrationTemplate && $library->selfRegistrationTemplate != 'default'){
			$post_data['TemplateName'] = $library->selfRegistrationTemplate;
		}


//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);
		unlink($cookie);

		//Parse the library card number from the response
		if (preg_match('/Your barcode is:.*?(\\d+)<\/(b|strong)>/s', $sresult, $matches)) {
			$barcode = $matches[1];
			return array('success' => true, 'barcode' => $barcode);
		} else {
			return array('success' => false, 'barcode' => '');
		}

	}

	public function _getLoginFormValues(User $patron){
		$loginData = array();
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			$loginData['code'] = $patron->cat_username;
			$loginData['pin'] = $patron->cat_password;
		}else {
			$loginData['name'] = $patron->cat_username;
			$loginData['code'] = $patron->cat_password;
		}

		return $loginData;
	}

	/**
	 * @param $username
	 * @param $patronName
	 * @return array
	 */
	public function validatePatronName($username, $patronName) {
		$nameParts = explode(',', $patronName);
		$lastName = ucwords(strtolower(trim($nameParts[0])));

		if (isset($nameParts[1])){
			$firstName = ucwords(strtolower(trim($nameParts[1])));
		}else{
			$firstName = null;
		}

		$fullName = str_replace(",", " ", $patronName);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$allNameComponents = preg_split('/[\s-]/', strtolower($fullName));
		foreach ($allNameComponents as $name){
			$newName = str_replace('-', '', $name);
			if ($newName != $name){
				$allNameComponents[] = $newName;
			}
			$newName = str_replace("'", '', $name);
			if ($newName != $name){
				$allNameComponents[] = $newName;
			}
		}
		$fullName = ucwords(strtolower($patronName));

		//Get the first name that the user supplies.
		//This expects the user to enter one or two names and only
		//Validates the first name that was entered.
		$username = str_replace(",", " ", $username);
		$username = str_replace(";", " ", $username);
		$username = preg_replace("/\\s{2,}/", " ", $username);
		$enteredNames = preg_split('/[\s-]/', strtolower($username));
		$userValid = false;
		foreach ($enteredNames as $name) {
			if (in_array($name, $allNameComponents, false)) {
				$userValid = true;
				break;
			}
		}
		return array($fullName, $lastName, $firstName, $userValid);
	}

	/**
	 * @param User $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getFines($patron = null, $includeMessages = false) : array {
		//Load the information from millennium using CURL
		$pageContents = $this->_fetchPatronInfoPage($patron, 'overdues');

		//Get the fines table data
		$messages = array();
		if (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/si', $pageContents, $regs)) {
			$finesTable = $regs[1];
			//Get the title and, type, and fine detail from the page
			preg_match_all('/<tr class="(patFuncFinesEntryTitle|patFuncFinesEntryDetail|patFuncFinesDetailDate)">(.*?)<\/tr>/si', $finesTable, $rowDetails, PREG_SET_ORDER);
			$curFine = array();
			for ($match1 = 0; $match1 < count($rowDetails); $match1++) {
				$rowType = $rowDetails[$match1][1];
				$rowContents = $rowDetails[$match1][2];
				if ($rowType == 'patFuncFinesEntryTitle'){
					if ($curFine != null) $messages[] = $curFine;
					$curFine = array();
					if (preg_match('/<td.*?>(.*?)<\/td>/si', $rowContents, $colDetails)){
						$curFine['message'] = trim(strip_tags($colDetails[1]));
					}
				}else if ($rowType == 'patFuncFinesEntryDetail'){
					if (preg_match_all('/<td.*?>(.*?)<\/td>/si', $rowContents, $colDetails, PREG_SET_ORDER) > 0){
						$curFine['reason'] = trim(strip_tags($colDetails[1][1]));
						$curFine['amount'] = trim($colDetails[2][1]);
						$curFine['amountVal'] = (float)(str_replace('$', '', $curFine['amount']));
					}
				}else if ($rowType == 'patFuncFinesDetailDate'){
					if (preg_match_all('/<td.*?>(.*?)<\/td>/si', $rowContents, $colDetails, PREG_SET_ORDER) > 0){
						if (!array_key_exists('details', $curFine)) $curFine['details'] = array();
						$curFine['details'][] = array(
							'label' => trim(strip_tags($colDetails[1][1])),
							'value' => trim(strip_tags($colDetails[2][1])),
						);
					}
				}
			}
			if ($curFine != null) $messages[] = $curFine;
		}

		return $messages;
	}

	public function getEmailResetPinTemplate(){
	return 'requestPinReset.tpl';
}

	public function getEmailResetPinResultsTemplate(){
		return 'requestPinResetResults.tpl';
	}

	public function processEmailResetPinForm(){
		$barcode = strip_tags($_REQUEST['barcode']);

		//Go to the pinreset page
		$pinResetUrl = $this->getVendorOpacUrl() . '/pinreset';
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$curl_connection = curl_init();
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, is_null($cookieJar) ? true : false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);

		curl_setopt($curl_connection, CURLOPT_URL, $pinResetUrl);
		/*$pinResetPageHtml = */curl_exec($curl_connection);

		//Now submit the request
		$post_data['code'] = $barcode;
		$post_data['pat_submit'] = 'xxx';
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$pinResetResultPageHtml = curl_exec($curl_connection);

		//Parse the response
		$result = array(
			'success' => false,
			'error' => true,
			'message' => 'Unknown error resetting pin'
		);

		if (preg_match('/<div class="errormessage">(.*?)<\/div>/is', $pinResetResultPageHtml, $matches)){
			$result['error'] = false;
			$result['message'] = trim($matches[1]);
		}elseif (preg_match('/<div class="pageContent">.*?<strong>(.*?)<\/strong>/si', $pinResetResultPageHtml, $matches)){
			$result['error'] = false;
			$result['success'] = true;
			$result['message'] = trim($matches[1]);
		}
		return $result;
	}

	/**
	 * Import Lists from the ILS
	 *
	 * @param  User $patron
	 * @return array - an array of results including the names of the lists that were imported as well as number of titles.
	 */
	function importListsFromIls($patron){
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$user = UserAccount::getLoggedInUser();
		$results = array(
			'totalTitles' => 0,
			'totalLists' => 0
		);

		//Get the page which contains a table with all lists in them.
		$listsPage = $this->_fetchPatronInfoPage($patron, 'mylists');
		//Get the actual table
		if (preg_match('/<table[^>]*?class="patFunc"[^>]*?>(.*?)<\/table>/si', $listsPage, $listsPageMatches)) {
			$allListTable = $listsPageMatches[1];
			//Now that we have the table, get the actual list names and ids
			if (preg_match_all('/<tr[^>]*?class="patFuncEntry"[^>]*?>.*?<input type="checkbox" id ="(\\d+)".*?<a.*?>(.*?)<\/a>.*?<td[^>]*class="patFuncDetails">(.*?)<\/td>.*?<\/tr>/si', $allListTable, $listDetails, PREG_SET_ORDER)) {
				for ($listIndex = 0; $listIndex < count($listDetails); $listIndex++) {
					$listId = $listDetails[$listIndex][1];
					$title = $listDetails[$listIndex][2];
					$description = str_replace('&nbsp;', '', $listDetails[$listIndex][3]);

					//Create the list (or find one that already exists)
					$newList = new UserList();
					$newList->user_id = $user->id;
					$newList->title = $title;
					if (!$newList->find(true)) {
						$newList->description = strip_tags($description);
						$newList->insert();
					}

					$currentListTitles = $newList->getListTitles();
					$this->getListTitlesFromWebPAC($patron, $listId, $currentListTitles, $newList, $results, $title);

					$results['totalLists'] += 1;
				}
			}else if (preg_match_all('~<a.*?listNum=(\d+)">(.*?)<\/a>~si', $allListTable, $listDetails, PREG_SET_ORDER)) {
				for ($listIndex = 0; $listIndex < count($listDetails); $listIndex++) {
					$listId = $listDetails[$listIndex][1];
					$title = $listDetails[$listIndex][2];
					$newList = new UserList();
					$newList->user_id = $user->id;
					$newList->title = $title;
					if (!$newList->find(true)) {
						$newList->insert();
					}

					$currentListTitles = $newList->getListTitles();
					$this->getListTitlesFromWebPAC($patron, $listId, $currentListTitles, $newList, $results, $title);

					$results['totalLists'] += 1;
				}
			}
		}

		return $results;
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param String basedId String the base id without checksum
	 * @return String the check digit
	 */
	function getCheckDigit($baseId){
		$baseId = preg_replace('/\.?[bij]/', '', $baseId);
		$sumOfDigits = 0;
		for ($i = 0; $i < strlen($baseId); $i++){
			$curDigit = substr($baseId, $i, 1);
			$sumOfDigits += ((strlen($baseId) + 1) - $i) * $curDigit;
		}
		$modValue = $sumOfDigits % 11;
		if ($modValue == 10){
			return "x";
		}else{
			return $modValue;
		}
	}

	public function getSelfRegistrationFields(){
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => false);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'email', 'type'=>'email', 'label'=>'Email', 'description'=>'Email', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'phone', 'type'=>'text', 'label'=>'Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false);

		return $fields;
	}

	public function hasNativeReadingHistory() : bool {
		return true;
	}

	protected function _doPinTest($barcode, $pin) {
		$pin = urlencode(trim($pin));
		$barcode = trim($barcode);
		$pinTestUrl = $this->accountProfile->patronApiUrl . "/PATRONAPI/$barcode/$pin/pintest";
		$pinTestResultRaw = $this->curlWrapper->curlGetPage($pinTestUrl);
		//$logger->log('PATRONAPI pintest response : ' . $api_contents, Logger::LOG_DEBUG);
		if ($pinTestResultRaw){
			$pinTestResult = strip_tags($pinTestResultRaw);

			//Parse the page
			$pinTestData = array();
			preg_match_all('/(.*?)=(.*)/', $pinTestResult, $patronData, PREG_SET_ORDER);
			for ($curRow = 0; $curRow < count($patronData); $curRow++) {
				$patronDumpKey = str_replace(" ", "_", trim($patronData[$curRow][1]));
				$pinTestData[$patronDumpKey] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
			}
			if (!isset($pinTestData['RETCOD'])){
				$userValid = false;
			}else if ($pinTestData['RETCOD'] == 0){
				$userValid = true;
			}else{
				$userValid = false;
			}
		}else{
			$userValid = false;
		}

		return $userValid;
	}

	public function getAccountSummary(User $patron) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		$barcode = $patron->getBarcode();
		$patronDump = $this->_getPatronDump($barcode);
		$expirationTime = 0;
		//See if expiration date is close
		if (trim($patronDump['EXP_DATE']) != '-  -'){
			list ($monthExp, $dayExp, $yearExp) = explode("-",$patronDump['EXP_DATE']);
			$expirationTime = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
		}
		$numHoldsAvailable = 0;
		$numHoldsRequested = 0;
		global $configArray;
		$availableStatusRegex = isset($configArray['Catalog']['patronApiAvailableHoldsRegex']) ? $configArray['Catalog']['patronApiAvailableHoldsRegex'] : "/ST=(105|98|106),/";
		if (isset($patronDump) && isset($patronDump['HOLD']) && count($patronDump['HOLD']) > 0){
			foreach ($patronDump['HOLD'] as $hold){
				if (preg_match("$availableStatusRegex", $hold)){
					$numHoldsAvailable++;
				}else{
					$numHoldsRequested++;
				}
			}
		}
		$finesVal = floatval(preg_replace('/[^\\d.]/', '', $patronDump['MONEY_OWED']));

		$summary->numCheckedOut = $patronDump['CUR_CHKOUT'];
		$checkouts = $patron->getCheckouts(false);
		$numOverdue = 0;
		foreach ($checkouts as $checkout){
			if ($checkout->isOverdue()){
				$numOverdue++;
			}
		}
		$summary->numOverdue = $numOverdue;
		$summary->numAvailableHolds = $numHoldsAvailable;
		$summary->numUnavailableHolds = $numHoldsRequested;
		$summary->totalFines = $finesVal;
		$summary->expirationDate = $expirationTime;

		return $summary;
	}

	public function findNewUser($patronBarcode){
		$patronDump = $this->_getPatronDump($patronBarcode);
		if ($patronDump != null){
			if (count($patronDump) > 0){
				$user = $this->createPatronFromPatronDump($patronDump, '');
				return $user;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * @param array $patronDump
	 * @param $password
	 * @return User
	 */
	private function createPatronFromPatronDump(array $patronDump, $password): User
	{
		global $configArray;
		$userExistsInDB = false;

		$user = new User();
		//Get the unique user id from Millennium
		$user->source = $this->accountProfile->name;
		$user->username = $patronDump['RECORD_#'];
		if ($user->find(true)) {
			$userExistsInDB = true;
		}
		if (isset($patronDump['PATRN_NAME'])) {
			$patronName = $patronDump['PATRN_NAME'];
			$nameParts = explode(',', $patronName);
			$lastName = ucwords(strtolower(trim($nameParts[0])));

			if (isset($nameParts[1])){
				$firstName = ucwords(strtolower(trim($nameParts[1])));
			}else{
				$firstName = null;
			}

			$fullName = str_replace(",", " ", $patronName);
			$fullName = str_replace(";", " ", $fullName);
			$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
			$allNameComponents = preg_split('/[\s-]/', strtolower($fullName));
			foreach ($allNameComponents as $name){
				$newName = str_replace('-', '', $name);
				if ($newName != $name){
					$allNameComponents[] = $newName;
				}
				$newName = str_replace("'", '', $name);
				if ($newName != $name){
					$allNameComponents[] = $newName;
				}
			}
			$fullName = ucwords(strtolower($patronName));
		}
		$forceDisplayNameUpdate = false;
		$firstName = isset($firstName) ? $firstName : '';
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		$lastName = isset($lastName) ? $lastName : '';
		if ($user->lastname != $lastName) {
			$user->lastname = isset($lastName) ? $lastName : '';
			$forceDisplayNameUpdate = true;
		}
		$user->_fullname = isset($fullName) ? $fullName : '';
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
			if (isset($patronDump['P_BARCODE'])) {
				$user->cat_username = $patronDump['P_BARCODE']; //Make sure to get the barcode so if we are using usernames we can still get the barcode for use with overdrive, etc.
			} else {
				$user->cat_username = $patronDump['CARD_#']; //Make sure to get the barcode so if we are using usernames we can still get the barcode for use with overdrive, etc.
			}
			$user->cat_password = $password;
		} else {
			$user->cat_username = $patronDump['PATRN_NAME'];
			//When we get the patron dump, we may override the barcode so make sure that we update it here.
			//For self registered cards, the P_BARCODE is not set so we need to use the RECORD_# field
			if (strlen($patronDump['P_BARCODE']) > 0) {
				$user->cat_password = $patronDump['P_BARCODE'];
			} else {
				$user->cat_password = $patronDump['RECORD_#'];
			}

		}

		$user->phone = isset($patronDump['TELEPHONE']) ? $patronDump['TELEPHONE'] : (isset($patronDump['HOME_PHONE']) ? $patronDump['HOME_PHONE'] : '');
		$user->email = isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '';
		$user->patronType = $patronDump['P_TYPE'];

		// MDN: Ticket https://ticket.bywatersolutions.com/Ticket/Display.html?id=76676
		// in Sierra, there is not a
		/*if (isset($patronDump['MESSAGE'])) {
			$user->_web_note = $patronDump['MESSAGE'];
		}
		if (isset($patronDump['WEB_NOTE'])){
			if (!empty($user->_web_note)){
				$user->_web_note .= '<br/>';
			}
			$user->_web_note = $patronDump['WEB_NOTE'];
		}*/

		$this->loadContactInformationFromPatronDump($user, $patronDump);

		$numHoldsAvailable = 0;
		$numHoldsRequested = 0;
		$availableStatusRegex = isset($configArray['Catalog']['patronApiAvailableHoldsRegex']) ? $configArray['Catalog']['patronApiAvailableHoldsRegex'] : "/ST=(105|98|106),/";
		if (isset($patronDump) && isset($patronDump['HOLD']) && count($patronDump['HOLD']) > 0) {
			foreach ($patronDump['HOLD'] as $hold) {
				if (preg_match("$availableStatusRegex", $hold)) {
					$numHoldsAvailable++;
				} else {
					$numHoldsRequested++;
				}
			}
		}
		$user->_numCheckedOutIls = $patronDump['CUR_CHKOUT'];
		$user->_numHoldsIls = isset($patronDump) ? (isset($patronDump['HOLD']) ? count($patronDump['HOLD']) : 0) : '?';
		$user->_numHoldsAvailableIls = $numHoldsAvailable;
		$user->_numHoldsRequestedIls = $numHoldsRequested;

		if ($userExistsInDB) {
			$user->update();
		} else {
			$user->created = date('Y-m-d');
			$user->insert();
		}
		return $user;
	}

	/**
	 * @param $patron
	 * @param $listId
	 * @param array|null $currentListTitles
	 * @param UserList $newList
	 * @param array $results
	 * @param $title
	 */
	private function getListTitlesFromWebPAC($patron, $listId, ?array $currentListTitles, UserList $newList, &$results, $title)
	{
		//Get a list of all titles within the list to be imported
		//Increase the timeout for the page to load large lists
		$this->curlWrapper->setTimeout(120);
		$listDetailsPage = $this->_fetchPatronInfoPage($patron, 'mylists?listNum=' . $listId);
		//Get the table for the details
		$listsDetailsMatches = [];
		if (preg_match('/<table[^>]*?class="patFunc"[^>]*?>(.*?)<\/table>/si', $listDetailsPage, $listsDetailsMatches)) {
			$listTitlesTable = $listsDetailsMatches[1];
			//Get the bib numbers for the title
			preg_match_all('/<input type="checkbox" name=".*?(b\d{1,7})".*?<span[^>]*class="patFuncTitle(?:Main)?">(.*?)<\/span>/si', $listTitlesTable, $bibNumberMatches, PREG_SET_ORDER);
			for ($bibCtr = 0; $bibCtr < count($bibNumberMatches); $bibCtr++) {
				$bibNumber = $bibNumberMatches[$bibCtr][1];
				$bibTitle = strip_tags($bibNumberMatches[$bibCtr][2]);

				//Get the grouped work for the resource
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$primaryIdentifier->identifier = '.' . $bibNumber . $this->getCheckDigit($bibNumber);
				$primaryIdentifier->type = 'ils';
				if ($primaryIdentifier->find(true)) {
					$groupedWork = new GroupedWork();
					$groupedWork->id = $primaryIdentifier->grouped_work_id;
					if ($groupedWork->find(true)) {
						//Check to see if this title is already on the list.
						$resourceOnList = false;
						foreach ($currentListTitles as $currentTitle) {
							if (($currentTitle->source == 'GroupedWork') && ($currentTitle->sourceId == $groupedWork->permanent_id)) {
								$resourceOnList = true;
								break;
							}
						}

						if (!$resourceOnList) {
							$listEntry = new UserListEntry();
							$listEntry->source = 'GroupedWork';
							$listEntry->sourceId = $groupedWork->permanent_id;
							$listEntry->listId = $newList->id;
							$listEntry->notes = '';
							$listEntry->dateAdded = time();
							$listEntry->insert();
						}
					}
				} else {
					//The title is not in the resources, add an error to the results
					if (!isset($results['errors'])) {
						$results['errors'] = array();
					}
					$results['errors'][] = "\"$bibTitle\" on list $title could not be found in the catalog and was not imported.";
				}

				$results['totalTitles']++;
			}
		}
	}
}
