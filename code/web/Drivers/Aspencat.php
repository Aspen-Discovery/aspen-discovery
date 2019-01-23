<?php
/**
 * Catalog Driver for Aspencat libraries based on Koha
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/3/14
 * Time: 5:51 PM
 */
class Aspencat implements DriverInterface{
	/** @var string $cookieFile A temporary file to store cookies  */
	private $cookieFile = null;
	/** @var resource connection to AspenCat  */
	private $curl_connection = null;

	private $dbConnection = null;
	public $accountProfile;

	/**
	 * @return array
	 */
	private static $holdingSortingData = null;
	protected static function getSortingDataForHoldings() {
		if (self::$holdingSortingData == null){
			$user = UserAccount::getLoggedInUser();
			global $library;
			global $locationSingleton; /** @var $locationSingleton Location */

			$holdingSortingData = array();

			//Get location information so we can put things into sections
			$physicalLocation = $locationSingleton->getPhysicalLocation();
			if ($physicalLocation != null) {
				$holdingSortingData['physicalBranch'] = $physicalLocation->holdingBranchLabel;
			} else {
				$holdingSortingData['physicalBranch'] = '';
			}
			$holdingSortingData['homeBranch'] = '';
			$homeBranchId = 0;
			$holdingSortingData['nearbyBranch1'] = '';
			$nearbyBranch1Id = 0;
			$holdingSortingData['nearbyBranch2'] = '';
			$nearbyBranch2Id = 0;

			//Set location information based on the user login.  This will override information based
			if (isset($user) && $user != false) {
				$homeBranchId = $user->homeLocationId;
				$nearbyBranch1Id = $user->myLocation1Id;
				$nearbyBranch2Id = $user->myLocation2Id;
			}
			//Load the holding label for the user's home location.
			$userLocation = new Location();
			$userLocation->whereAdd("locationId = '$homeBranchId'");
			$userLocation->find();
			if ($userLocation->N == 1) {
				$userLocation->fetch();
				$holdingSortingData['homeBranch'] = $userLocation->holdingBranchLabel;
			}
			//Load nearby branch 1
			$nearbyLocation1 = new Location();
			$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
			$nearbyLocation1->find();
			if ($nearbyLocation1->N == 1) {
				$nearbyLocation1->fetch();
				$holdingSortingData['nearbyBranch1'] = $nearbyLocation1->holdingBranchLabel;
			}
			//Load nearby branch 2
			$nearbyLocation2 = new Location();
			$nearbyLocation2->whereAdd();
			$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
			$nearbyLocation2->find();
			if ($nearbyLocation2->N == 1) {
				$nearbyLocation2->fetch();
				$holdingSortingData['nearbyBranch2'] = $nearbyLocation2->holdingBranchLabel;
			}

			//Get a list of the display names for all locations based on holding label.
			$locationLabels = array();
			$location = new Location();
			$location->find();
			$holdingSortingData['libraryLocationLabels'] = array();
			$locationCodes = array();
			$suppressedLocationCodes = array();
			while ($location->fetch()) {
				if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???') {
					if ($library && $library->libraryId == $location->libraryId) {
						$cleanLabel = str_replace('/', '\/', $location->holdingBranchLabel);
						$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
					}

					$locationLabels[$location->holdingBranchLabel] = $location->displayName;
					$locationCodes[$location->code] = $location->holdingBranchLabel;
					if ($location->suppressHoldings == 1) {
						$suppressedLocationCodes[$location->code] = $location->code;
					}
				}
			}
			if (count($holdingSortingData['libraryLocationLabels']) > 0) {
				$holdingSortingData['libraryLocationLabels'] = '/^(' . join('|', $holdingSortingData['libraryLocationLabels']) . ').*/i';
			} else {
				$holdingSortingData['libraryLocationLabels'] = '';
			}
			self::$holdingSortingData = $holdingSortingData;
			global $timer;
			$timer->logTime("Finished loading sorting information for holdings");
		}
		return self::$holdingSortingData;
	}

	/**
	 * @param User $patron                    The User Object to make updates to
	 * @param boolean $canUpdateContactInfo   Permission check that updating is allowed
	 * @return array                  Array of error messages for errors that occurred
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo){
		$updateErrors = array();
		if ($canUpdateContactInfo) {
			$updateErrors[] = "Profile Information can not be updated.";
		}
		return $updateErrors;
	}

	private $transactions = array();
	public function getMyCheckouts($user) {
		if (true){
			return $this->getMyCheckoutsFromOpac($user);
		}else{
			return $this->getMyCheckoutsFromDB($user);
		}
	}

	public function getMyCheckoutsFromOpac($user) {
		global $logger;
		if (isset($this->transactions[$user->id])){
			return $this->transactions[$user->id];
		}
		//Get transactions by screen scraping
		$transactions = array();
		//Login to Koha classic interface
		$result = $this->loginToKoha($user);
		if (!$result['success']){
			return $transactions;
		}
		//Get the checked out titles page
		$transactionPage = $result['summaryPage'];
		//Parse the checked out titles page
		if (preg_match_all('/<table id="checkoutst">(.*?)<\/table>/si', $transactionPage, $transactionTableData, PREG_SET_ORDER)){
			$transactionTable = $transactionTableData[0][0];
			//Get the header row labels in case the columns are ever rearranged?
			$headerLabels = array();
			preg_match_all('/<th>([^<]*?)<\/th>/si', $transactionTable, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strtolower($tableHeader));
			}
			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $transactionTable, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr(?:.*?)>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
			foreach ($tableData[1] as $tableRow){
				//Each row represents a transaction
				$transaction = array();
				$transaction['checkoutSource'] = 'ILS';
				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				foreach ($tableCells[1] as $col => $tableCell){
					//Based off which column we are in, fill out the transaction
					if ($headerLabels[$col] == 'title'){
						//Title column contains title, author, and id link
						if (preg_match('/biblionumber=(\\d+)">\\s*([^<]*)\\s*<\/a>.*?>\\s*(.*?)\\s*<\/span>/si', $tableCell, $cellDetails)) {
							$transaction['id']      = $cellDetails[1];
							$transaction['shortId'] = $cellDetails[1];
							$transaction['title']   = $cellDetails[2];
							$transaction['author']  = $cellDetails[3];
						}else{
							$logger->log("Could not parse title for checkout", PEAR_LOG_WARNING);
							$transaction['title'] = strip_tags($tableCell);
						}
					}elseif ($headerLabels[$col] == 'call no.'){
						//Ignore this column for now
					}elseif ($headerLabels[$col] == 'due'){
						if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $tableCell, $dueMatches)){
							$dateDue = DateTime::createFromFormat('m/d/Y', $dueMatches[1]);
							if ($dateDue){
								$dueTime = $dateDue->getTimestamp();
							}else{
								$dueTime = null;
							}
						}else{
							$dueTime = strtotime($tableCell);
						}
						if ($dueTime != null){
							$transaction['dueDate'] = $dueTime;
						}
					}elseif ($headerLabels[$col] == 'renew'){
						if (preg_match('/item=(\\d+).*?\\((\\d+) of (\\d+) renewals/si', $tableCell, $renewalData)) {
							$transaction['itemid'] = $renewalData[1];
							$transaction['renewIndicator'] = $renewalData[1];
							$numRenewalsRemaining = $renewalData[2];
							$numRenewalsAllowed = $renewalData[3];
							$transaction['renewCount'] = $numRenewalsAllowed - $numRenewalsRemaining;
						}elseif(preg_match('/not renewable.*?\\((\\d+) of (\\d+) renewals/si', $tableCell, $renewalData)){
							$transaction['canrenew'] = false;
							$numRenewalsRemaining = $renewalData[1];
							$numRenewalsAllowed = $renewalData[2];
							$transaction['renewCount'] = $numRenewalsAllowed - $numRenewalsRemaining;
						}elseif(preg_match('/not renewable/si', $tableCell, $renewalData)){
							$transaction['canrenew'] = false;
						}
					}
					//TODO: Add display of fines on a title?
				}
				if ($transaction['id'] && strlen($transaction['id']) > 0){
					$transaction['recordId'] = $transaction['id'];
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($transaction['recordId']);
					if ($recordDriver->isValid()){
						$transaction['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
						$transaction['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$transaction['ratingData']    = $recordDriver->getRatingData();
						$transaction['format']        = $recordDriver->getPrimaryFormat();
						$transaction['author']        = $recordDriver->getPrimaryAuthor();
						$transaction['title']         = $recordDriver->getTitle();
						$curTitle['title_sort']       = $recordDriver->getSortableTitle();
						$transaction['link']          = $recordDriver->getLinkUrl();

					}else{
						$transaction['coverUrl'] = "";
						$transaction['groupedWorkId'] = "";
						$transaction['format'] = "Unknown";
					}
				}
				$transactions[] = $transaction;
			}
		}
		$this->transactions[$user->id] = $transactions;
		return $transactions;
	}

	/**
	 * @param User $patron
	 * @return array
	 */
	public function getMyCheckoutsFromDB($patron) {
		if (isset($this->transactions[$patron->id])){
			return $this->transactions[$patron->id];
		}

		//Get transactions by screen scraping
		$transactions = array();

		$this->initDatabaseConnection();

		$sql = "SELECT issues.*, items.biblionumber, title, author from issues left join items on items.itemnumber = issues.itemnumber left join biblio ON items.biblionumber = biblio.biblionumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()){
			$transaction = array();
			$transaction['checkoutSource'] = 'ILS';

			$transaction['id'] = $curRow['biblionumber'];
			$transaction['recordId'] = $curRow['biblionumber'];
			$transaction['shortId'] = $curRow['biblionumber'];
			$transaction['title'] = $curRow['title'];
			$transaction['author'] = $curRow['author'];

			$dateDue = DateTime::createFromFormat('Y-m-d', $curRow['date_due']);
			if ($dateDue){
				$dueTime = $dateDue->getTimestamp();
			}else{
				$dueTime = null;
			}
			$transaction['dueDate'] = $dueTime;
			$transaction['itemid'] = $curRow['id'];
			$transaction['renewIndicator'] = $curRow['id'];
			$transaction['renewCount'] = $curRow['renewals'];

			if ($transaction['id'] && strlen($transaction['id']) > 0){
				$transaction['recordId'] = $transaction['id'];
				require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
				$recordDriver = new MarcRecord($transaction['recordId']);
				if ($recordDriver->isValid()){
					$transaction['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
					$transaction['groupedWorkId'] = $recordDriver->getGroupedWorkId();
					$transaction['ratingData']    = $recordDriver->getRatingData();
					$transaction['format']        = $recordDriver->getPrimaryFormat();
					$transaction['author']        = $recordDriver->getPrimaryAuthor();
					$transaction['title']         = $recordDriver->getTitle();
					$curTitle['title_sort']       = $recordDriver->getSortableTitle();
					$transaction['link']          = $recordDriver->getLinkUrl();
				}else{
					$transaction['coverUrl'] = "";
					$transaction['groupedWorkId'] = "";
					$transaction['format'] = "Unknown";
				}
			}

			$transaction['user'] = $patron->getNameAndLibraryLabel();

			$transactions[] = $transaction;
		}

		$this->transactions[$patron->id] = $transactions;

		return $transactions;
	}


	protected function getKohaPage($kohaUrl){
		if ($this->cookieFile == null) {
			$this->cookieFile = tempnam("/tmp", "KOHACURL");
		}

		//Setup the connection to the url
		if ($this->curl_connection == null){
			$this->curl_connection = curl_init($kohaUrl);
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile);
			curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($this->cookieFile) ? true : false);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 5);
		}else{
			curl_setopt($this->curl_connection, CURLOPT_URL, $kohaUrl);
		}

		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);

		//Get the response from the page
		$sResult = curl_exec($this->curl_connection);
		return $sResult;
	}

	/**
	 * @param $kohaUrl
	 * @param $postParams
	 * @return mixed
	 */
	protected function postToKohaPage($kohaUrl, $postParams) {
		//Post parameters to the login url using curl
		//If we haven't created a file to store cookies, create one
		if ($this->cookieFile == null) {
			$this->cookieFile = tempnam("/tmp", "KOHACURL");
		}

		//Setup the connection to the url
		if ($this->curl_connection == null){
			$this->curl_connection = curl_init($kohaUrl);
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile);
			curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($this->cookieFile) ? true : false);
		}else{
			curl_setopt($this->curl_connection, CURLOPT_URL, $kohaUrl);
		}

		//Set post parameters
		curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, http_build_query($postParams));
		curl_setopt($this->curl_connection, CURLOPT_POST, true);

		//Get the response from the page
		$sResult = curl_exec($this->curl_connection);
		return $sResult;
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		global $logger;
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		global $timer;

		//Use MySQL connection to load data
		$this->initDatabaseConnection();

		$barcodesToTest = array();
		$barcodesToTest[] = $username;
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library){
			if ($library->barcodePrefix){
				if (strpos($username, $library->barcodePrefix) !== 0){
					//Add the barcode prefix to the barcode
					$barcodesToTest[] = $library->barcodePrefix . $username;
				}
			}
		}

		foreach ($barcodesToTest as $i=>$barcode) {
			$sql = "SELECT borrowernumber, cardnumber, surname, firstname, streetnumber, streettype, address, address2, city, zipcode, country, email, phone, mobile, categorycode, dateexpiry, password, userid, branchcode from borrowers where cardnumber = '" . mysqli_escape_string($this->dbConnection, $barcode). "' OR userid = '" . mysqli_escape_string($this->dbConnection, $barcode). "'";
			$encodedPassword = rtrim(base64_encode(pack('H*', md5($password))), '=');

			$lookupUserResult = mysqli_query($this->dbConnection, $sql, MYSQLI_USE_RESULT);
			if ($lookupUserResult) {
				if ($userFromDbResultSet = $lookupUserResult) {
					$userFromDb = $userFromDbResultSet->fetch_assoc();
					$userFromDbResultSet->close();
					if ($userFromDb == null){
						if ($i == count($barcodesToTest) -1){
							return new PEAR_Error('authentication_error_invalid');
						}
					}elseif (($userFromDb['password'] == $encodedPassword) || $validatedViaSSO) {
						$userExistsInDB = false;
						$user = new User();
						//Get the unique user id from Millennium
						$user->source = $this->accountProfile->name;
						$user->username = $userFromDb['borrowernumber'];
						if ($user->find(true)){
							$userExistsInDB = true;
						}

						$forceDisplayNameUpdate = false;
						$firstName = $userFromDb['firstname'];
						if ($user->firstname != $firstName) {
							$user->firstname = $firstName;
							$forceDisplayNameUpdate = true;
						}
						$lastName = $userFromDb['surname'];
						if ($user->lastname != $lastName){
							$user->lastname = isset($lastName) ? $lastName : '';
							$forceDisplayNameUpdate = true;
						}
						if ($forceDisplayNameUpdate){
							$user->displayName = '';
						}
						$user->fullname     = $userFromDb['firstname'] . ' ' . $userFromDb['surname'];
						$user->cat_username = $barcode;
						$user->cat_password = $password;
						$user->email        = $userFromDb['email'];
						$user->patronType   = $userFromDb['categorycode'];
						$user->web_note     = '';

						$city = strtok($userFromDb['city'], ',');
						$state = strtok(',');
						$city = trim($city);
						$state = trim($state);

						$user->address1 = trim($userFromDb['streetnumber'] . ' ' . $userFromDb['address'] . ' ' . $userFromDb['address2']);
						$user->city     = $city;
						$user->state    = $state;
						$user->zip      = $userFromDb['zipcode'];
						$user->phone    = $userFromDb['phone'];

						//Get fines
						//Load fines from database
						$outstandingFines = $this->getOutstandingFineTotal($user);
						$user->fines    = sprintf('$%0.2f', $outstandingFines);
						$user->finesVal = floatval($outstandingFines);

						//Get number of items checked out
						$checkedOutItemsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numCheckouts FROM issues WHERE borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
						$numCheckouts = 0;
						if ($checkedOutItemsRS){
							$checkedOutItems = $checkedOutItemsRS->fetch_assoc();
							$numCheckouts = $checkedOutItems['numCheckouts'];
							$checkedOutItemsRS->close();
						}
						$user->numCheckedOutIls = $numCheckouts;

						//Get number of available holds
						$availableHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE found = "W" and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
						$numAvailableHolds = 0;
						if ($availableHoldsRS){
							$availableHolds = $availableHoldsRS->fetch_assoc();
							$numAvailableHolds = $availableHolds['numHolds'];
							$availableHoldsRS->close();
						}
						$user->numHoldsAvailableIls = $numAvailableHolds;

						//Get number of unavailable
						$waitingHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE (found <> "W" or found is null) and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
						$numWaitingHolds = 0;
						if ($waitingHoldsRS){
							$waitingHolds = $waitingHoldsRS->fetch_assoc();
							$numWaitingHolds = $waitingHolds['numHolds'];
							$waitingHoldsRS->close();
						}
						$user->numHoldsRequestedIls = $numWaitingHolds;
						$user->numHoldsIls = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

						$homeBranchCode = strtolower($userFromDb['branchcode']);
						$location = new Location();
						$location->code = $homeBranchCode;
						if (!$location->find(1)){
							unset($location);
							$user->homeLocationId = 0;
							// Logging for Diagnosing PK-1846
							global $logger;
							$logger->log('Aspencat Driver: No Location found, user\'s homeLocationId being set to 0. User : '.$user->id, PEAR_LOG_WARNING);
						}

						if ((empty($user->homeLocationId) || $user->homeLocationId == -1) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
							if ((empty($user->homeLocationId) || $user->homeLocationId == -1) && !isset($location)) {
								// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
								// try to find the main branch to assign to user
								// or the first location for the library
								global $library;

								$location            = new Location();
								$location->libraryId = $library->libraryId;
								$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
								if (!$location->find(true)) {
									// Seriously no locations even?
									global $logger;
									$logger->log('Failed to find any location to assign to user as home location', PEAR_LOG_ERR);
									unset($location);
								}
							}
							if (isset($location)) {
								$user->homeLocationId = $location->locationId;
								if (empty($user->myLocation1Id)) {
									$user->myLocation1Id  = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
									/** @var /Location $location */
									//Get display name for preferred location 1
									$myLocation1             = new Location();
									$myLocation1->locationId = $user->myLocation1Id;
									if ($myLocation1->find(true)) {
										$user->myLocation1 = $myLocation1->displayName;
									}
								}

								if (empty($user->myLocation2Id)){
									$user->myLocation2Id  = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
									//Get display name for preferred location 2
									$myLocation2             = new Location();
									$myLocation2->locationId = $user->myLocation2Id;
									if ($myLocation2->find(true)) {
										$user->myLocation2 = $myLocation2->displayName;
									}
								}
							}
						}

						if (isset($location)){
							//Get display names that aren't stored
							$user->homeLocationCode = $location->code;
							$user->homeLocation     = $location->displayName;
						}

						$user->expires = $userFromDb['dateexpiry']; //TODO: format is year-month-day; millennium is month-day-year; needs converting??

						$user->expired     = 0; // default setting
						$user->expireClose = 0;

						if (!empty($userFromDb['dateexpiry'])) { // TODO: probably need a better check of this field
							list ($yearExp, $monthExp, $dayExp) = explode('-', $userFromDb['dateexpiry']);
							$timeExpire   = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
							$timeNow      = time();
							$timeToExpire = $timeExpire - $timeNow;
							if ($timeToExpire <= 30 * 24 * 60 * 60) {
								if ($timeToExpire <= 0) {
									$user->expired = 1;
								}
								$user->expireClose = 1;
							}
						}

						$user->noticePreferenceLabel = 'Unknown';

						if ($userExistsInDB){
							$user->update();
						}else{
							$user->created = date('Y-m-d');
							$user->insert();
						}

						$timer->logTime("patron logged in successfully");

						return $user;
					}else{
						if ($i == count($barcodesToTest) -1){
							return new PEAR_Error('authentication_error_denied');
						}
					}
				}else{
					$logger->log("MySQL did not return a result for getUserInfoStmt", PEAR_LOG_ERR);
					if ($i == count($barcodesToTest) -1){
						return new PEAR_Error('authentication_error_technical');
					}
				}
			}else{
				$logger->log("Unable to execute getUserInfoStmt " .  mysqli_error($this->dbConnection), PEAR_LOG_ERR);
				if ($i == count($barcodesToTest) -1) {
					return new PEAR_Error('authentication_error_technical');
				}
			}
		}
		return null;
	}

	function initDatabaseConnection(){
		global $configArray;
		if ($this->dbConnection == null){
			$this->dbConnection = mysqli_connect($configArray['Catalog']['db_host'], $configArray['Catalog']['db_user'], $configArray['Catalog']['db_pwd'], $configArray['Catalog']['db_name']);

			if (!$this->dbConnection || mysqli_errno($this->dbConnection) != 0){
				global $logger;
				$logger->log("Error connecting to Koha database " . mysqli_error($this->dbConnection), PEAR_LOG_ERR);
				$this->dbConnection = null;
			}
			global $timer;
			$timer->logTime("Initialized connection to Koha");
		}
	}

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		$this->accountProfile = $accountProfile;
		global $timer;
		$timer->logTime("Crea11ted Aspencat Driver");
	}

	function __destruct(){
		//Cleanup any connections we have to other systems
		if ($this->curl_connection != null){
			curl_close($this->curl_connection);
		}
		if ($this->dbConnection != null){
			if ($this->getNumHoldsStmt != null){
				$this->getNumHoldsStmt->close();
			}
			mysqli_close($this->dbConnection);
		}

		if ($this->cookieFile != null){
			unlink($this->cookieFile);
		}
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	/**
	 * Get Reading History
	 *
	 * This is responsible for retrieving a history of checked out items for the patron.
	 *
	 * @param   User   $patron     The patron account
	 * @param   int     $page
	 * @param   int     $recordsPerPage
	 * @param   string  $sortOption
	 *
	 * @return  array               Array of the patron's reading list
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		// TODO implement sorting, currently only done in catalogConnection for aspencat reading history
		//TODO prepend indexProfileType
		$this->initDatabaseConnection();

		//Figure out if the user is opted in to reading history

		$sql = "select disable_reading_history from borrowers where borrowernumber = {$patron->username}";
		$historyEnabledRS = mysqli_query($this->dbConnection, $sql);
		if ($historyEnabledRS){
			$historyEnabledRow = $historyEnabledRS->fetch_assoc();
			$historyEnabled = !$historyEnabledRow['disable_reading_history'];

			// Update patron's setting in Pika if the setting has changed in Koha
			if ($historyEnabled != $patron->trackReadingHistory) {
				$patron->trackReadingHistory = (boolean) $historyEnabled;
				$patron->update();
			}

			if (!$historyEnabled){
				return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
			}else{
				$historyActive = true;
				$readingHistoryTitles = array();

				//Borrowed from C4:Members.pm
				$readingHistoryTitleSql = "SELECT *,issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp
					FROM issues
					LEFT JOIN items on items.itemnumber=issues.itemnumber
					LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
					LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
					WHERE borrowernumber={$patron->username}
					UNION ALL
					SELECT *,old_issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp
					FROM old_issues
					LEFT JOIN items on items.itemnumber=old_issues.itemnumber
					LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
					LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
					WHERE borrowernumber={$patron->username}";
				$readingHistoryTitleRS = mysqli_query($this->dbConnection, $readingHistoryTitleSql);
				if ($readingHistoryTitleRS){
					while ($readingHistoryTitleRow = $readingHistoryTitleRS->fetch_assoc()){
						$checkOutDate = new DateTime($readingHistoryTitleRow['itemstimestamp']);
						$curTitle = array();
						$curTitle['id']       = $readingHistoryTitleRow['biblionumber'];
						$curTitle['shortId']  = $readingHistoryTitleRow['biblionumber'];
						$curTitle['recordId'] = $readingHistoryTitleRow['biblionumber'];
						$curTitle['title']    = $readingHistoryTitleRow['title'];
						$curTitle['checkout'] = $checkOutDate->format('m-d-Y'); // this format is expected by Pika's java cron program.

						$readingHistoryTitles[] = $curTitle;
					}
				}
			}

			$numTitles = count($readingHistoryTitles);

			//process pagination
			if ($recordsPerPage != -1){
				$startRecord = ($page - 1) * $recordsPerPage;
				$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
			}

			set_time_limit(20 * count($readingHistoryTitles));
			foreach ($readingHistoryTitles as $key => $historyEntry){
				//Get additional information from resources table
				$historyEntry['ratingData']  = null;
				$historyEntry['permanentId'] = null;
				$historyEntry['linkUrl']     = null;
				$historyEntry['coverUrl']    = null;
				$historyEntry['format']      = "Unknown";
				;
				if (!empty($historyEntry['recordId'])){
//					if (is_int($historyEntry['recordId'])) $historyEntry['recordId'] = (string) $historyEntry['recordId']; // Marc Record Contructor expects the recordId as a string.
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($this->accountProfile->recordSource.':'.$historyEntry['recordId']);
					if ($recordDriver->isValid()){
						$historyEntry['ratingData']  = $recordDriver->getRatingData();
						$historyEntry['permanentId'] = $recordDriver->getPermanentId();
						$historyEntry['linkUrl']     = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
						$historyEntry['coverUrl']    = $recordDriver->getBookcoverUrl('medium');
						$historyEntry['format']      = $recordDriver->getFormats();
						$historyEntry['author']      = $recordDriver->getPrimaryAuthor();
					}
					$recordDriver = null;
				}
				$readingHistoryTitles[$key] = $historyEntry;
			}

			return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
		}
		return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
	}

	private function loginToKoha($user) {
		global $configArray;
		$catalogUrl = $configArray['Catalog']['url'];

		//Construct the login url
		$loginUrl = "$catalogUrl/cgi-bin/koha/opac-user.pl";

		//Setup post parameters to the login url
		$postParams = array(
			'koha_login_context' => 'opac',
			'password' => $user->cat_password,
			'userid'=> $user->cat_username
		);
		$sResult = $this->postToKohaPage($loginUrl, $postParams);

		//Parse the response to make sure the login went ok
		//If we can see the logout link, it means that we logged in successfully.
		if (preg_match('/<a\\s+class="logout"\\s+id="logout"[^>]*?>/si', $sResult)){
			$result =array(
				'success' => true,
				'summaryPage' => $sResult
			);
		}else{
			$result =array(
				'success' => false,
			);
		}
		return $result;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   User    $patron       The User to place a hold for
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null){
		$hold_result = array();
		$hold_result['success'] = false;

		//Set pickup location
		$campus = strtoupper($pickupBranch);

		//Get a specific item number to place a hold on even though we are placing a title level hold.
		//because.... Koha
		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$recordDriver = new MarcRecord($recordId);
		if (!$recordDriver->isValid()){
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		global $configArray;
		$marcRecord = $recordDriver->getMarcRecord();

		//Check to see if the title requires item level holds
		/** @var File_MARC_Data_Field[] $holdTypeFields */
		$itemLevelHoldAllowed = false;
		$itemLevelHoldOnly = false;
		$holdTypeFields = $marcRecord->getFields('942');
		foreach ($holdTypeFields as $holdTypeField){
			if ($holdTypeField->getSubfield('r') != null){
				if ($holdTypeField->getSubfield('r')->getData() == 'itemtitle'){
					$itemLevelHoldAllowed = true;
				}else if ($holdTypeField->getSubfield('r')->getData() == 'item'){
					$itemLevelHoldAllowed = true;
					$itemLevelHoldOnly = true;
				}
			}
		}

		//Get the items the user can place a hold on
		$this->loginToKoha($patron);
		$placeHoldPage = $this->getKohaPage($configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl?biblionumber=' . $recordId);
		preg_match_all('/<div class="dialog alert">(.*?)<\/div>/s', $placeHoldPage, $matches);
		if (count($matches) > 0 && count($matches[1]) > 0){
			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['success'] = false;
			$hold_result['message'] = '';
			foreach ($matches[1] as $errorMsg){
				$errorMsg = trim($errorMsg);
				$errorMsg = str_replace(array("\r","\n"), '', $errorMsg);
				$errorMsg = translate($errorMsg);
				$hold_result['message'] .= $errorMsg . '<br/>';
			}
			return $hold_result;
		}

		if ($itemLevelHoldAllowed){
			//Need to prompt for an item level hold
			$items = array();
			if (!$itemLevelHoldOnly){
				//Add a first title returned
				$items[-1] = array(
					'itemNumber' => -1,
					'location' => 'Next available copy',
					'callNumber' => '',
					'status' => '',
				);
			}

			//Get the item table from the page
			if (preg_match('/<table>\\s+<caption>Select a specific copy:<\/caption>\\s+(.*?)<\/table>/s', $placeHoldPage, $matches)) {
				$itemTable = $matches[1];
				//Get the header row labels
				$headerLabels = array();
				preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $itemTable, $tableHeaders, PREG_PATTERN_ORDER);
				foreach ($tableHeaders[1] as $col => $tableHeader){
					$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
				}

				//Grab each row within the table
				preg_match_all('/<tr[^>]*>\\s+(<td.*?)<\/tr>/si', $itemTable, $tableData, PREG_PATTERN_ORDER);
				foreach ($tableData[1] as $tableRow){
					//Each row in the table represents a hold

					$curItem = array();
					$validItem = false;
					//Go through each cell in the row
					preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
					foreach ($tableCells[1] as $col => $tableCell){
						if ($headerLabels[$col] == 'copy'){
							if (strpos($tableCell, 'disabled') === false){
								$validItem = true;
								if (preg_match('/value="(\d+)"/', $tableCell, $valueMatches)){
									$curItem['itemNumber'] = $valueMatches[1];
								}
							}
						}else if ($headerLabels[$col] == 'item type'){
							$curItem['itemType'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'barcode'){
							$curItem['barcode'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'home library'){
							$curItem['location'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'call number'){
							$curItem['callNumber'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'vol info'){
							$curItem['volInfo'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'information'){
							$curItem['status'] = trim($tableCell);
						}
					}
					if ($validItem){
						$items[$curItem['itemNumber']] = $curItem;
					}
				}
			}elseif (preg_match('/<div class="dialog alert">(.*?)<\/div>/s', $placeHoldPage, $matches)){
				$items = array();
				$message = trim($matches[1]);
			}

			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['items'] = $items;
			if (count($items) > 0){
				$message = 'This title allows item level holds, please select an item to place a hold on.';
			}else{
				if (!isset($message)){
					$message = 'There are no holdable items for this title.';
				}
			}
			$hold_result['success'] = false;
			$hold_result['message'] = $message;
			return $hold_result;
		}else{
			//Just a regular bib level hold
			$hold_result['title'] = $recordDriver->getTitle();

			//Post the hold to koha
			$placeHoldPage = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl';
			$holdParams = array(
				'biblionumbers' => $recordId . '/',
				'branch' => $campus,
				'place_reserve' => 1,
				"reqtype_$recordId" => 'Any',
				'reserve_mode' => 'multi',
				'selecteditems' => "$recordId//$campus/",
				'single_bib' => $recordId,
			);
			$kohaHoldResult = $this->postToKohaPage($placeHoldPage, $holdParams);

			//If the hold is successful we go back to the account page and can see

			$hold_result['id'] = $recordId;
			if (preg_match('/<a href="#opac-user-holds">Holds<\/a>/si', $kohaHoldResult)) {
				//We redirected to the holds page, everything seems to be good
				$holds = $this->getMyHolds($patron, 1, -1, 'title', $kohaHoldResult);
				$hold_result['success'] = true;
				$hold_result['message'] = "Your hold was placed successfully.";
				//Find the correct hold (will be unavailable)
				foreach ($holds['unavailable'] as $holdInfo){
					if ($holdInfo['id'] == $recordId){
						if (isset($holdInfo['position'])){
							$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
						}
						break;
					}
				}
			}else{
				$hold_result['success'] = false;
				//Look for an alert message
				if (preg_match('/<div class="dialog alert">(.*?)<\/div>/', $kohaHoldResult, $matches)){
					$hold_result['message'] = 'Your hold could not be placed. ' . $matches[1] ;
				}else{
					$hold_result['message'] = 'Your hold could not be placed. ' ;
				}

			}
			return $hold_result;
		}
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
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		global $configArray;

		$hold_result = array();
		$hold_result['success'] = false;

		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$recordDriver = new MarcRecord($recordId);
		if (!$recordDriver->isValid()){
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		$hold_result['title'] = $recordDriver->getTitle();

		//Set pickup location
		if (isset($_REQUEST['campus'])){
			$campus=trim($_REQUEST['campus']);
		}else{
			$campus = $patron->homeLocationId;
			//Get the code for the location
			$locationLookup = new Location();
			$locationLookup->locationId = $campus;
			$locationLookup->find();
			if ($locationLookup->N > 0){
				$locationLookup->fetch();
				$campus = $locationLookup->code;
			}
		}
		$campus = strtoupper($campus);

		//Login before placing the hold
		$this->loginToKoha($patron);

		//Post the hold to koha
		$placeHoldPage = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl';
		$holdParams = array(
			'biblionumbers' => $recordId . '/',
			'branch' => $campus,
			"checkitem_$recordId" => $itemId,
			'place_reserve' => 1,
			"reqtype_$recordId" => 'Specific',
			'reserve_mode' => 'multi',
			'selecteditems' => "$recordId/$itemId/$campus/",
			'single_bib' => $recordId,
		);
		$kohaHoldResult = $this->postToKohaPage($placeHoldPage, $holdParams);

		$hold_result['id'] = $recordId;
		if (preg_match('/<a href="#opac-user-holds">Holds<\/a>/si', $kohaHoldResult)) {
			//We redirected to the holds page, everything seems to be good
			$holds = $this->getMyHolds($patron, 1, -1, 'title', $kohaHoldResult);
			$hold_result['success'] = true;
			$hold_result['message'] = "Your hold was placed successfully.";
			//Find the correct hold (will be unavailable)
			foreach ($holds['unavailable'] as $holdInfo){
				if ($holdInfo['id'] == $recordId){
					if (isset($holdInfo['position'])){
						$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
					}
					break;
				}
			}
		}else{
			$hold_result['success'] = false;
			//Look for an alert message
			if (preg_match('/<div class="dialog alert">(.*?)<\/div>/', $kohaHoldResult, $matches)){
				$hold_result['message'] = 'Your hold could not be placed. ' . $matches[1] ;
			}else{
				$hold_result['message'] = 'Your hold could not be placed. ' ;
			}

		}
		return $hold_result;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron      The patron array from patronLogin
	 * @param integer $page           The current page of holds
	 * @param integer $recordsPerPage The number of records to show per page
	 * @param string $sortOption      How the records should be sorted
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHolds(/** @noinspection PhpUnusedParameterInspection */
		$patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		if (true){
			return $this->getMyHoldsFromOpac($patron);
		}else{
			return $this->getMyHoldsFromDB($patron);
		}
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron      The patron array from patronLogin
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHoldsFromOpac($patron){
		global $logger;
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);
		//Get transactions by screen scraping
		//Login to Koha classic interface
		$result = $this->loginToKoha($patron);
		if (!$result['success']){
			return $holds;
		}
		//Get the summary page that contains both checked out titles and holds
		$summaryPage = $result['summaryPage'];

		//Get the holds table
		if (preg_match_all('/<table id="aholdst">(.*?)<\/table>/si', $summaryPage, $holdsTableData, PREG_SET_ORDER)){
			$holdsTable = $holdsTableData[0][0];
			//Get the header row labels
			$headerLabels = array();
			preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $holdsTable, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
			}
			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $holdsTable, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
			foreach ($tableData[1] as $tableRow){
				//Each row in the table represents a hold
				$curHold= array();
				$curHold['holdSource'] = 'ILS';
				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				$bibId = "";
				foreach ($tableCells[1] as $col => $tableCell){
					//Based off which column we are in, fill out the transaction
					if ($headerLabels[$col] == 'title'){
						//Title column contains title, author, and id link
						if (preg_match('/biblionumber=(\\d+)".*?>(.*?)<\/a>/si', $tableCell, $cellDetails)) {
							$bibId = $cellDetails[1];
							$curHold['id']       = $cellDetails[1];
							$curHold['shortId']  = $cellDetails[1];
							$curHold['recordId'] = $cellDetails[1];
							$curHold['title']    = $cellDetails[2];
						}else{
							$logger->log("Could not parse title for checkout", PEAR_LOG_WARNING);
							$curHold['title'] = strip_tags($tableCell);
						}
					}elseif ($headerLabels[$col] == 'placed on'){
						$tempDate = DateTime::createFromFormat('m/d/Y', $tableCell);
						$curHold['create'] = $tempDate->getTimestamp();
					}elseif ($headerLabels[$col] == 'expires on'){
						if (strlen($tableCell) != 0){
							$tempDate = DateTime::createFromFormat('m/d/Y', $tableCell);
							$curHold['expire'] = $tempDate->getTimestamp();
						}
					}elseif ($headerLabels[$col] == 'pick up location'){
						if (strlen($tableCell) != 0){
							$curHold['location']           = trim($tableCell);
							$curHold['locationUpdateable'] = false;
							$curHold['currentPickupName']  = $curHold['location'];
						}
					}elseif ($headerLabels[$col] == 'priority'){
						$curHold['position'] = trim($tableCell);
					}elseif ($headerLabels[$col] == 'status'){
						$curHold['status'] = trim($tableCell);
					}elseif ($headerLabels[$col] == 'cancel'){
						$curHold['cancelable'] = strlen($tableCell) > 0;
						if (preg_match('/<input type="hidden" name="reservenumber" value="(.*?)" \/>/', $tableCell, $matches)) {
							$curHold['cancelId'] = $matches[1];
						}
					}elseif ($headerLabels[$col] == 'suspend'){
						if (preg_match('/cannot be suspended/i', $tableCell)){
							$curHold['freezeable'] = false;
						}else{
							$curHold['freezeable'] = true;
						}

					}
				}
				if ($bibId){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($bibId);
					if ($recordDriver->isValid()){
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl('medium');
						$curHold['link']            = $recordDriver->getRecordUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData();
					}
				}
				if (!isset($curHold['status']) || !preg_match('/^Item waiting.*/i', $curHold['status'])){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		//Get the suspended holds table
		if (preg_match_all('/<table id="sholdst">(.*?)<\/table>/si', $summaryPage, $holdsTableData, PREG_SET_ORDER)){
			$holdsTable = $holdsTableData[0][0];
			//Get the header row labels
			$headerLabels = array();
			preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $holdsTable, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
			}
			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $holdsTable, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
			foreach ($tableData[1] as $tableRow){
				//Each row in the table represents a hold
				$curHold = array();
				$curHold['holdSource'] = 'ILS';
				$curHold['frozen']    = true;
				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				$bibId = "";
				foreach ($tableCells[1] as $col => $tableCell){
					//Based off which column we are in, fill out the transaction
					if ($headerLabels[$col] == 'title'){
						//Title column contains title, author, and id link
						if (preg_match('/biblionumber=(\\d+)".*?>(.*?)<\/a>/si', $tableCell, $cellDetails)) {
							$bibId = $cellDetails[1];
							$curHold['id']       = $cellDetails[1];
							$curHold['shortId']  = $cellDetails[1];
							$curHold['recordId'] = $cellDetails[1];
							$curHold['title']    = $cellDetails[2];
						}else{
							$logger->log("Could not parse title for checkout", PEAR_LOG_WARNING);
							$curHold['title'] = strip_tags($tableCell);
						}
					}elseif ($headerLabels[$col] == 'placed on'){
						$tempDate = DateTime::createFromFormat('m/d/Y', $tableCell);
						$curHold['create'] = $tempDate->getTimestamp();
					}elseif ($headerLabels[$col] == 'expires on'){
						if (strlen($tableCell) != 0){
							$tempDate = DateTime::createFromFormat('m/d/Y', $tableCell);
							$curHold['expire'] = $tempDate->getTimestamp();
						}
					}elseif ($headerLabels[$col] == 'pick up location'){
						if (strlen($tableCell) != 0){
							$curHold['location']           = $tableCell;
							$curHold['locationUpdateable'] = false;
							$curHold['currentPickupName']  = $curHold['location'];
						}
					}elseif ($headerLabels[$col] == 'resume now'){
						$curHold['cancelable'] = false;
						$curHold['freezeable'] = false;
						if (preg_match('/<input type="hidden" name="reservenumber" value="(.*?)" \/>/', $tableCell, $matches)) {
							$curHold['cancelId'] = $matches[1];
						}
					}elseif ($headerLabels[$col] == 'resume on'){
						$tableCell = trim($tableCell);
						if (strlen($tableCell) != 0){
							$tempDate = DateTime::createFromFormat('m/d/Y', $tableCell);
							$curHold['reactivate']         = $tableCell;
							$curHold['reactivateTime']     = $tempDate->getTimestamp();
							$curHold['status']             = 'Frozen';
						}
					}
				}
				if ($bibId){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($bibId);
					if ($recordDriver->isValid()){
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl();
						$curHold['link']            = $recordDriver->getRecordUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData();
						if (empty($curHold['title'])) {
							$curHold['title'] = $recordDriver->getTitle();
						}
						if (empty($curHold['author'])) {
							$curHold['author'] = $recordDriver->getPrimaryAuthor();
						}
					}
				}
				$curHold['user'] = $patron->getNameAndLibraryLabel();
				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "filled") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		return $holds;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron      The patron array from patronLogin
	 * @param integer $page           The current page of holds
	 * @param integer $recordsPerPage The number of records to show per page
	 * @param string $sortOption      How the records should be sorted
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHoldsFromDB(/** @noinspection PhpUnusedParameterInspection */
		$patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$this->initDatabaseConnection();

		$sql = "SELECT *, title, author FROM reserves inner join biblio on biblio.biblionumber = reserves.biblionumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()){
			//Each row in the table represents a hold
			$curHold= array();
			$curHold['holdSource'] = 'ILS';
			$bibId = $curRow['biblionumber'];
			$curHold['id'] = $curRow['biblionumber'];
			$curHold['shortId'] = $curRow['biblionumber'];
			$curHold['recordId'] = $curRow['biblionumber'];
			$curHold['title'] = $curRow['title'];
			$curHold['create'] = date_parse_from_format('Y-M-d H:m:s', $curRow['reservedate']);
			$dateTime = date_create_from_format('Y-M-d', $curRow['expirationdate']);
			$curHold['expire'] = $dateTime->getTimestamp();

			$curHold['location'] = $curRow['branchcode'];
			$curHold['locationUpdateable'] = false;
			$curHold['currentPickupName'] = $curHold['location'];
			$curHold['position'] = $curRow['priority'];
			$curHold['frozen'] = false;
			$curHold['freezeable'] = false;
			$curHold['cancelable'] = true;
			if ($curRow['found'] == 'S'){
				$curHold['frozen'] = true;
				$curHold['status'] = "Suspended";
				$curHold['cancelable'] = false;
			}elseif ($curRow['found'] == 'W'){
				$curHold['status'] = "Ready to Pickup";
			}elseif ($curRow['found'] == 'T'){
				$curHold['status'] = "In Transit";
			}else{
				$curHold['status'] = "Pending";
				$curHold['freezeable'] = true;
			}
			$curHold['freezeable'] = true;
			$curHold['cancelId'] = $curRow['reservenumber'];

			if ($bibId){
				require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
				$recordDriver = new MarcRecord($bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle'] = $recordDriver->getSortableTitle();
					$curHold['format'] = $recordDriver->getFormat();
					$curHold['isbn'] = $recordDriver->getCleanISBN();
					$curHold['upc'] = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl'] = $recordDriver->getBookcoverUrl();
					$curHold['link'] = $recordDriver->getRecordUrl();

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}
			}
			$curHold['user'] = $patron->getNameAndLibraryLabel();

			if (!isset($curHold['status']) || !preg_match('/^Ready to Pickup.*/i', $curHold['status'])){
				$holds['unavailable'][] = $curHold;
			}else{
				$holds['available'][] = $curHold;
			}
		}

		return $holds;
	}

	public function updateHold($requestId, $patronId, $type){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patron, $type, /*$title,*/ $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		// TODO: get actual titles for hold items
		$titles = array();

		if (!isset($xNum) || empty($xNum)){
			if (isset($_REQUEST['waitingholdselected']) || isset($_REQUEST['availableholdselected'])){
				$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
				$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
				$holdKeys = array_merge($waitingHolds, $availableHolds);
			}else{
				if (is_array($cancelId)){
					$holdKeys = $cancelId;
				}else{
					$holdKeys = array($cancelId);
				}
			}
		}else{
			$holdKeys = $xNum;
		}

		//In all cases, we need to login
		$result = $this->loginToKoha($patron);
		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			$originalHolds = $this->getMyHolds($patron, 1, -1, 'title', $result['summaryPage']);

			//Post a request to koha
			foreach ($holdKeys as $holdKey){
				//Get the record Id for the hold
				if (isset($_REQUEST['recordId'][$holdKey])){
					$recordId = $_REQUEST['recordId'][$holdKey];
				}else{
					$recordId = "";
				}

				$postParams = array(
					'biblionumber' => $recordId,
					'reservenumber' => $holdKey,
					'submit' => 'Cancel'
				);
				$catalogUrl = $configArray['Catalog']['url'];
				$cancelUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
				$kohaHoldResult = $this->postToKohaPage($cancelUrl, $postParams);

				//Parse the result
				$updatedHolds = $this->getMyHolds($patron, 1, -1, 'title', $kohaHoldResult);
				if ((count($updatedHolds['available']) + count($updatedHolds['unavailable'])) < (count($originalHolds['available']) + count($originalHolds['unavailable']))){
					//We cancelled the hold
				}else{
					$allCancelsSucceed = false;
				}
			}
			if ($allCancelsSucceed){
				return array(
					'title' => $titles,
					'success' => true,
					'message' => count($holdKeys) == 1 ? 'Cancelled 1 hold successfully.' : 'Cancelled ' . count($holdKeys) . ' hold(s) successfully.');
			}else{
				return array(
					'title' => $titles,
					'success' => false,
					'message' => 'Some holds could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			if ($locationId){
				return array(
					'title' => $titles,
					'success' => false,
					'message' => 'Changing location for a hold is not supported.');
			}else{
				//Freeze/Thaw the hold
				if ($freezeValue == 'on'){
					//Suspend the hold

					$allLocationChangesSucceed = true;

					foreach ($holdKeys as $holdKey){
						$postParams = array(
							'suspend' => 1,
							'reservenumber' => $holdKey,
							'submit' => 'Suspend'
						);
						if (isset($_REQUEST['reactivationDate'])){
							$reactivationDate = strtotime($_REQUEST['reactivationDate']);
							$reactivationDate = date('m-d-Y', $reactivationDate);
							$postParams['resumedate_' . $holdKey] = $reactivationDate;
						}else{
							$postParams['resumedate_' . $holdKey] = '';
						}
						$catalogUrl = $configArray['Catalog']['url'];
						$updateUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
						$kohaUpdateResults = $this->postToKohaPage($updateUrl, $postParams);

						//Check the result of the update
					}
					if ($allLocationChangesSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => 'Your hold(s) were frozen successfully.');
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => 'Some holds could not be frozen.  Please try again later or see your librarian.');
					}
				}else{
					//Reactivate the hold
					$allUnsuspendsSucceed = true;

					foreach ($holdKeys as $holdKey){
						$postParams = array(
							'resume' => 1,
							'reservenumber' => $holdKey,
							'submit' => 'Resume'
						);
						$catalogUrl = $configArray['Catalog']['url'];
						$updateUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
						$this->postToKohaPage($updateUrl, $postParams);
					}
					if ($allUnsuspendsSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => 'Your hold(s) were thawed successfully.');
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => 'Some holds could not be thawed.  Please try again later or see your librarian.');
					}
				}
			}
		}
	}

	public function hasFastRenewAll(){
		return false;
	}

	public function renewAll($patron){
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		global $analytics;
		global $configArray;

		//Get the session token for the user
		$loginResult = $this->loginToKoha($patron);
		if ($loginResult['success']){
			global $analytics;
			$postParams = array(
				'from' => 'opac_user',
				'item' => $itemId,
				'borrowernumber' => $patron->username,
			);
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-renew.pl";
			$kohaUrl .= "?" . http_build_query($postParams);

			$kohaResponse = $this->getKohaPage($kohaUrl);

			//TODO: Renewal Failure Messages needed
			if (true) {
				$success = true;
				$message = 'Your item was successfully renewed.';
				//Clear the patron profile
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Renew Successful');
				}
			}else{
				$success = false;
				$message = 'Invalid Response from SIP 2';
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Renew Failed', $message);
				}
			}
		}else{
			$success = false;
			$message = 'Unable to login2';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $message);
			}
		}

		return array(
			'itemId' => $itemId,
			'success'  => $success,
			'message' => $message);
	}

	/**
	 * Get a list of fines for the user.
	 * Code take from C4::Account getcharges method
	 *
	 * @param null $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getMyFines($patron, $includeMessages = false){


		$this->initDatabaseConnection();

		//Get a list of outstanding fees
		$query = "SELECT * FROM fees JOIN fee_transactions AS ft on(id = fee_id) WHERE borrowernumber = {$patron->username} and accounttype in (select accounttype from accounttypes where class='fee' or class='invoice') ";

		$allFeesRS = mysqli_query($this->dbConnection, $query);

		$fines = array();
		while ($allFeesRow = $allFeesRS->fetch_assoc()){
			$feeId = $allFeesRow['id'];
			$query2 = "SELECT sum(amount) as amountOutstanding from fees LEFT JOIN fee_transactions on (fees.id=fee_transactions.fee_id) where fees.id = $feeId";

			$outstandingFeesRS = mysqli_query($this->dbConnection, $query2);
			$outstandingFeesRow = $outstandingFeesRS->fetch_assoc();
			$amountOutstanding = $outstandingFeesRow['amountOutstanding'];
			if ($amountOutstanding > 0){
				$curFine = array(
					'date' => $allFeesRow['timestamp'],
					'reason' => $allFeesRow['accounttype'],
					'message' => $allFeesRow['description'],
					'amount' => $allFeesRow['amount'],
					'amountOutstanding' => $amountOutstanding,
				);
				$fines[] = $curFine;
			}
			$outstandingFeesRS->close();
		}
		$allFeesRS->close();

		return $fines;
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, /** @noinspection PhpUnusedParameterInspection */
	                                $selectedTitles){
		global $configArray;
		global $analytics;
		if (!$this->loginToKoha($patron)){
			return;
		}else{
			if ($action == 'deleteMarked'){

				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Delete Marked Reading History Titles');
				}
			}elseif ($action == 'deleteAll'){

				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Delete All Reading History Titles');
				}
			}elseif ($action == 'exportList'){
				//Leave this unimplemented for now.
			}elseif ($action == 'optOut'){
				$kohaUrl = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-update_reading_history.pl';
				$postParams = array(
					'disable_reading_history' => 1
				);
				$this->postToKohaPage($kohaUrl, $postParams);
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Opt Out of Reading History');
				}
			}elseif ($action == 'optIn'){
				$kohaUrl = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-update_reading_history.pl';
				$postParams = array(
					'disable_reading_history' => 0
				);
				$this->postToKohaPage($kohaUrl, $postParams);
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Opt in to Reading History');
				}
			}
		}
	}

	private $holdsByBib = array();
	/** @var mysqli_stmt  */
	private $getNumHoldsStmt = null;
	public function getNumHolds($id) {
		if (isset($this->holdsByBib[$id])){
			return $this->holdsByBib[$id];
		}
		$numHolds = 0;

		$this->initDatabaseConnection();
		$sql = "SELECT count(*) from reserves where biblionumber = $id";
		$results = mysqli_query($this->dbConnection, $sql);
		if (!$results){
			global $logger;
			$logger->log("Unable to load hold count from Koha (" . mysqli_errno($this->dbConnection) . ") " . mysqli_error($this->dbConnection), PEAR_LOG_ERR);
		}else{
			$curRow = $results->fetch_row();
			$numHolds = $curRow[0];
			$results->close();
		}

		$this->holdsByBib[$id] = $numHolds;

		global $timer;
		$timer->logTime("Finished loading num holds for record ");

		return $numHolds;
	}

	private function getInTransitHoldsForBibFromKohaDB($recordId){
		$holdsForBib = array();

		if (strpos($recordId, ':') > 0){
			list($type, $recordId) = explode(':', $recordId);
		}

		$this->initDatabaseConnection();

		$sql = "SELECT count(*) from reserves where biblionumber = $recordId AND found = 'T'";
		$results = mysqli_query($this->dbConnection, $sql);

		if (!$results){
			global $logger;
			$logger->log("Unable to load in transit hold count from Koha (" . mysqli_errno($this->dbConnection) . ") " . mysqli_error($this->dbConnection), PEAR_LOG_ERR);
		}else{
			//Read the information
			while ($curRow = $results->fetch_assoc()){
				$holdsForBib[$curRow['itemnumber']] = $curRow['itemnumber'];
			}
			$results->close();
		}

		return $holdsForBib;
	}

	/**
	 * Get Total Outstanding fines for a user.  Lifted from Koha:
	 * C4::Accounts.pm gettotalowed method
	 *
	 * @return mixed
	 */
	private function getOutstandingFineTotal($patron) {
		//Since borrowernumber is stored in fees and payments, not fee_transactions,
		//this is done with two queries: the first gets all outstanding charges, the second
		//picks up any unallocated credits.
		$amountOutstanding = 0;
		$this->initDatabaseConnection();
		$amountOutstandingRS = mysqli_query($this->dbConnection, "SELECT SUM(amount) FROM fees LEFT JOIN fee_transactions on(fees.id = fee_transactions.fee_id) where fees.borrowernumber = {$patron->username}");
		if ($amountOutstandingRS){
			$amountOutstanding = $amountOutstandingRS->fetch_array();
			$amountOutstanding = $amountOutstanding[0];
			$amountOutstandingRS->close();
		}

		$creditRS = mysqli_query($this->dbConnection, "SELECT SUM(amount) FROM payments LEFT JOIN fee_transactions on(payments.id = fee_transactions.payment_id) where payments.borrowernumber = ? and fee_id is null" );
		if ($creditRS){
			$credit = $creditRS->fetch_array();
			$credit = $credit[0];
			if ($credit != null){
				$amountOutstanding += $credit;
			}
			$creditRS->close();
		}

		return $amountOutstanding ;
	}

	function cancelHold($patron, $recordId, $cancelId) {
		return $this->updateHoldDetailed($patron, 'cancel', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToFreezeId, '', 'on');
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToThawId, '', 'off');
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToUpdateId, $newPickupLocation, 'off');
	}
}