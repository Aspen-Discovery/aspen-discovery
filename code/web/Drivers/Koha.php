<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
class Koha extends AbstractIlsDriver {
	private $dbConnection = null;

	/** @var CurlWrapper */
	private $curlWrapper;
	/** @var CurlWrapper */
	private $opacCurlWrapper;

	/**
	 * @return array
	 */
	private static $holdingSortingData = null;
	protected static function getSortingDataForHoldings() {
		if (self::$holdingSortingData == null){
		    /** @var User $user */
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
			while ($location->fetch()) {
				if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???') {
					if ($library && $library->libraryId == $location->libraryId) {
						$cleanLabel = str_replace('/', '\/', $location->holdingBranchLabel);
						$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
					}

					$locationLabels[$location->holdingBranchLabel] = $location->displayName;
					$locationCodes[$location->code] = $location->holdingBranchLabel;
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
		if (!$canUpdateContactInfo) {
			$updateErrors[] = "Profile Information can not be updated.";
		}else{
			$catalogUrl = $this->accountProfile->vendorOpacUrl;

			$this->loginToKohaOpac($patron);

			$updatePage = $this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl');
			//Get the csr token
			$csr_token = '';
			if (preg_match('%<input type="hidden" name="csrf_token" value="(.*?)" />%s', $updatePage, $matches)) {
				$csr_token = $matches[1];
			}

			$postVariables = [
				'borrower_branchcode' => $_REQUEST['borrower_branchcode'],
				'borrower_title' => $_REQUEST['borrower_title'],
				'borrower_surname' => $_REQUEST['borrower_surname'],
				'borrower_firstname' => $_REQUEST['borrower_firstname'],
				'borrower_dateofbirth' => $this->aspenDateToKohaDate($_REQUEST['borrower_dateofbirth']),
				'borrower_initials' => $_REQUEST['borrower_initials'],
				'borrower_othernames' => $_REQUEST['borrower_othernames'],
				'borrower_sex' => $_REQUEST['borrower_sex'],
				'borrower_address' => $_REQUEST['borrower_address'],
				'borrower_address2' => $_REQUEST['borrower_address2'],
				'borrower_city' => $_REQUEST['borrower_city'],
				'borrower_state' => $_REQUEST['borrower_state'],
				'borrower_zipcode' => $_REQUEST['borrower_zipcode'],
				'borrower_country' => $_REQUEST['borrower_country'],
				'borrower_phone' => $_REQUEST['borrower_phone'],
				'borrower_email' => $_REQUEST['borrower_email'],
				'borrower_phonepro' => $_REQUEST['borrower_phonepro'],
				'borrower_mobile' => $_REQUEST['borrower_mobile'],
				'borrower_emailpro' => $_REQUEST['borrower_emailpro'],
				'borrower_fax' => $_REQUEST['borrower_fax'],
				'borrower_B_address' => $_REQUEST['borrower_B_address'],
				'borrower_B_address2' => $_REQUEST['borrower_B_address2'],
				'borrower_B_city' => $_REQUEST['borrower_B_city'],
				'borrower_B_state' => $_REQUEST['borrower_B_state'],
				'borrower_B_zipcode' => $_REQUEST['borrower_B_zipcode'],
				'borrower_B_country' => $_REQUEST['borrower_B_country'],
				'borrower_B_phone' => $_REQUEST['borrower_B_phone'],
				'borrower_B_email' => $_REQUEST['borrower_B_email'],
				'borrower_contactnote' => $_REQUEST['borrower_contactnote'],
				'borrower_altcontactsurname' => $_REQUEST['borrower_altcontactsurname'],
				'borrower_altcontactfirstname' => $_REQUEST['borrower_altcontactfirstname'],
				'borrower_altcontactaddress1' => $_REQUEST['borrower_altcontactaddress1'],
				'borrower_altcontactaddress2' => $_REQUEST['borrower_altcontactaddress2'],
				'borrower_altcontactaddress3' => $_REQUEST['borrower_altcontactaddress3'],
				'borrower_altcontactstate' => $_REQUEST['borrower_altcontactstate'],
				'borrower_altcontactzipcode' => $_REQUEST['borrower_altcontactzipcode'],
				'borrower_altcontactcountry' => $_REQUEST['borrower_altcontactcountry'],
				'borrower_altcontactphone' => $_REQUEST['borrower_altcontactphone'],

				'csrf_token' => $csr_token,
				'action' => 'update'
			];
			if (isset($_REQUEST['resendEmail'])){
				$postVariables['resendEmail'] = strip_tags($_REQUEST['resendEmail']);
			}

			$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl', $postVariables);

			$messageInformation = [];
			if (preg_match('%<div class="alert alert-warning">(.*?)</div>%s', $postResults, $messageInformation)){
				$error = $messageInformation[1];
				$error = str_replace('<h3>', '<h4>', $error);
				$error = str_replace('</h3>', '</h4>', $error);
				$updateErrors[] = trim($error);
			}elseif (preg_match('%<div class="alert alert-success">(.*?)</div>%s', $postResults, $messageInformation)) {
				$error = $messageInformation[1];
				$error = str_replace('<h3>', '<h4>', $error);
				$error = str_replace('</h3>', '</h4>', $error);
				$updateErrors[] = trim($error);
			}elseif (preg_match('%<div class="alert">(.*?)</div>%s', $postResults, $messageInformation)) {
				$error = $messageInformation[1];
				$updateErrors[] = trim($error);
			}
		}

		return $updateErrors;
	}

	private $checkouts = array();

	/**
	 * @param User $patron
	 * @return array
	 */
	public function getCheckouts($patron) {
		if (isset($this->checkouts[$patron->id])){
			return $this->checkouts[$patron->id];
		}

		//Get checkouts by screen scraping
		$checkouts = array();

		$this->initDatabaseConnection();

        /** @noinspection SqlResolve */
        $sql = "SELECT issues.*, items.biblionumber, title, author from issues left join items on items.itemnumber = issues.itemnumber left join biblio ON items.biblionumber = biblio.biblionumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()){
			$checkout = array();
			$checkout['checkoutSource'] = 'ILS';

			$checkout['id'] = $curRow['issue_id'];
			$checkout['recordId'] = $curRow['biblionumber'];
			$checkout['shortId'] = $curRow['biblionumber'];
			$checkout['title'] = $curRow['title'];
			$checkout['author'] = $curRow['author'];

			$dateDue = DateTime::createFromFormat('Y-m-d H:i:s', $curRow['date_due']);
			if ($dateDue){
				$dueTime = $dateDue->getTimestamp();
			}else{
				$dueTime = null;
			}
			$checkout['dueDate'] = $dueTime;
			$checkout['itemId'] = $curRow['itemnumber'];
			$checkout['renewIndicator'] = $curRow['itemnumber'];
			$checkout['renewCount'] = $curRow['renewals'];

			if ($checkout['id'] && strlen($checkout['id']) > 0){
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($checkout['recordId']);
				if ($recordDriver->isValid()){
					$checkout['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
					$checkout['groupedWorkId'] = $recordDriver->getGroupedWorkId();
					$checkout['ratingData']    = $recordDriver->getRatingData();
					$checkout['format']        = $recordDriver->getPrimaryFormat();
					$checkout['author']        = $recordDriver->getPrimaryAuthor();
					$checkout['title']         = $recordDriver->getTitle();
					$curTitle['title_sort']       = $recordDriver->getSortableTitle();
					$checkout['link']          = $recordDriver->getLinkUrl();
				}else{
					$checkout['coverUrl'] = "";
					$checkout['groupedWorkId'] = "";
					$checkout['format'] = "Unknown";
				}
			}

			$checkout['user'] = $patron->getNameAndLibraryLabel();

			$checkouts[] = $checkout;
		}

		$this->checkouts[$patron->id] = $checkouts;

		return $checkouts;
	}

    public function getXMLWebServiceResponse($url){
        $xml = $this->curlWrapper->curlGetPage($url);
        if ($xml !== false && $xml !== 'false'){
            if (strpos($xml, '<') !== false){
                //Strip any non-UTF-8 characters
                $xml = preg_replace('/[^(\x20-\x7F)]*/', '', $xml);
                libxml_use_internal_errors(true);
                $parsedXml = simplexml_load_string($xml);
                if ($parsedXml === false){
                    //Failed to load xml
                    global $logger;
                    $logger->log("Error parsing xml", Logger::LOG_ERROR);
                    $logger->log($xml, Logger::LOG_DEBUG);
                    foreach (libxml_get_errors() as $error){
                        $logger->log("\t {$error->message}", Logger::LOG_ERROR);
                    }
                    return false;
                }else{
                    return $parsedXml;
                }
            }else{
                return $xml;
            }
        }else{
            global $logger;
            $logger->log('Curl problem in getWebServiceResponse', Logger::LOG_WARNING);
            return false;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param boolean $validatedViaSSO
     * @return AspenError|User|null
     */
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

		$userExistsInDB = false;
		foreach ($barcodesToTest as $i=>$barcode) {
		    //Authenticate the user using KOHA ILSDI
            $authenticationURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?service=AuthenticatePatron&username=' . urlencode($barcode) . '&password=' . urlencode($password);
            $authenticationResponse = $this->getXMLWebServiceResponse($authenticationURL);
            if (isset($authenticationResponse->id)){
                $patronId = $authenticationResponse->id;
                /** @noinspection SqlResolve */
                $sql = "SELECT borrowernumber, cardnumber, surname, firstname, streetnumber, streettype, address, address2, city, state, zipcode, country, email, phone, mobile, categorycode, dateexpiry, password, userid, branchcode from borrowers where borrowernumber = $patronId";

                $lookupUserResult = mysqli_query($this->dbConnection, $sql, MYSQLI_USE_RESULT);
                if ($lookupUserResult) {
                    $userFromDb = $lookupUserResult->fetch_assoc();
                    $lookupUserResult->close();

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
                    $user->_fullname     = $userFromDb['firstname'] . ' ' . $userFromDb['surname'];
                    $user->cat_username = $barcode;
                    $user->cat_password = $password;
                    $user->email        = $userFromDb['email'];
                    $user->patronType   = $userFromDb['categorycode'];
                    $user->_web_note     = '';

                    $user->_address1 = trim($userFromDb['streetnumber'] . ' ' . $userFromDb['address']);
                    $user->_address2 = $userFromDb['address2'];
                    $user->_city     = $userFromDb['city'];
                    $user->_state    = $userFromDb['state'];
                    $user->_zip      = $userFromDb['zipcode'];
                    $user->phone    = $userFromDb['phone'];

                    //Get fines
                    //Load fines from database
                    $outstandingFines = $this->getOutstandingFineTotal($user);
                    $user->_fines    = sprintf('$%0.2f', $outstandingFines);
                    $user->_finesVal = floatval($outstandingFines);

                    //Get number of items checked out
                    /** @noinspection SqlResolve */
                    $checkedOutItemsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numCheckouts FROM issues WHERE borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
                    $numCheckouts = 0;
                    if ($checkedOutItemsRS){
                        $checkedOutItems = $checkedOutItemsRS->fetch_assoc();
                        $numCheckouts = $checkedOutItems['numCheckouts'];
                        $checkedOutItemsRS->close();
                    }
                    $user->_numCheckedOutIls = $numCheckouts;

                    //Get number of available holds
                    /** @noinspection SqlResolve */
                    $availableHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE found = "W" and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
                    $numAvailableHolds = 0;
                    if ($availableHoldsRS){
                        $availableHolds = $availableHoldsRS->fetch_assoc();
                        $numAvailableHolds = $availableHolds['numHolds'];
                        $availableHoldsRS->close();
                    }
                    $user->_numHoldsAvailableIls = $numAvailableHolds;

                    //Get number of unavailable
                    /** @noinspection SqlResolve */
                    $waitingHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE (found <> "W" or found is null) and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
                    $numWaitingHolds = 0;
                    if ($waitingHoldsRS){
                        $waitingHolds = $waitingHoldsRS->fetch_assoc();
                        $numWaitingHolds = $waitingHolds['numHolds'];
                        $waitingHoldsRS->close();
                    }
                    $user->_numHoldsRequestedIls = $numWaitingHolds;
                    $user->_numHoldsIls = $user->_numHoldsAvailableIls + $user->_numHoldsRequestedIls;

                    $homeBranchCode = strtolower($userFromDb['branchcode']);
                    $location = new Location();
                    $location->code = $homeBranchCode;
                    if (!$location->find(1)){
                        unset($location);
                        $user->homeLocationId = 0;
                        // Logging for Diagnosing PK-1846
                        global $logger;
                        $logger->log('Koha Driver: No Location found, user\'s homeLocationId being set to 0. User : '.$user->id, Logger::LOG_WARNING);
                    }

                    if ((empty($user->homeLocationId) || $user->homeLocationId == -1) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
                        if ((empty($user->homeLocationId) || $user->homeLocationId == -1) && !isset($location)) {
                            // homeBranch Code not found in location table and the user doesn't have an assigned home location,
                            // try to find the main branch to assign to user
                            // or the first location for the library
                            global $library;

                            $location            = new Location();
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
                                $user->myLocation1Id  = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
                                /** @var /Location $location */
                                //Get display name for preferred location 1
                                $myLocation1             = new Location();
                                $myLocation1->locationId = $user->myLocation1Id;
                                if ($myLocation1->find(true)) {
                                    $user->_myLocation1 = $myLocation1->displayName;
                                }
                            }

                            if (empty($user->myLocation2Id)){
                                $user->myLocation2Id  = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
                                //Get display name for preferred location 2
                                $myLocation2             = new Location();
                                $myLocation2->locationId = $user->myLocation2Id;
                                if ($myLocation2->find(true)) {
                                    $user->_myLocation2 = $myLocation2->displayName;
                                }
                            }
                        }
                    }

                    if (isset($location)){
                        //Get display names that aren't stored
                        $user->_homeLocationCode = $location->code;
                        $user->_homeLocation     = $location->displayName;
                    }

                    $user->_expires = $userFromDb['dateexpiry']; //TODO: format is year-month-day; millennium is month-day-year; needs converting??

                    $user->_expired     = 0; // default setting
                    $user->_expireClose = 0;

                    if (!empty($userFromDb['dateexpiry'])) { // TODO: probably need a better check of this field
                        list ($yearExp, $monthExp, $dayExp) = explode('-', $userFromDb['dateexpiry']);
                        $timeExpire   = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
                        $timeNow      = time();
                        $timeToExpire = $timeExpire - $timeNow;
                        if ($timeToExpire <= 30 * 24 * 60 * 60) {
                            if ($timeToExpire <= 0) {
                                $user->_expired = 1;
                            }
                            $user->_expireClose = 1;
                        }
                    }

                    $user->_noticePreferenceLabel = 'Unknown';

                    if ($userExistsInDB){
                        $user->update();
                    }else{
                        $user->created = date('Y-m-d');
                        $user->insert();
                    }

                    $timer->logTime("patron logged in successfully");

                    return $user;
                }else{
                    $logger->log("MySQL did not return a result for getUserInfoStmt", Logger::LOG_ERROR);
                    if ($i == count($barcodesToTest) -1){
                        return new AspenError('authentication_error_technical');
                    }
                }
            }else{
            	//User is not valid, check to see if they have a valid account in Koha so we can return a different error
	            /** @noinspection SqlResolve */
	            $sql = "SELECT borrowernumber, cardnumber, userId from borrowers where cardnumber = '$barcode' OR userId = '$barcode'";

	            $lookupUserResult = mysqli_query($this->dbConnection, $sql);
	            if ($lookupUserResult->num_rows > 0) {
		            $userExistsInDB = true;
	            }
            }
		}
		if ($userExistsInDB){
			return new AspenError('authentication_error_denied');
		}else{
			return null;
		}
	}

	function initDatabaseConnection(){
		if ($this->dbConnection == null){
		    $port = empty($this->accountProfile->databasePort) ? '3306' : $this->accountProfile->databasePort;
			$this->dbConnection = mysqli_connect($this->accountProfile->databaseHost, $this->accountProfile->databaseUser, $this->accountProfile->databasePassword, $this->accountProfile->databaseName, $port);

			if (!$this->dbConnection || mysqli_errno($this->dbConnection) != 0){
				global $logger;
				$logger->log("Error connecting to Koha database " . mysqli_error($this->dbConnection), Logger::LOG_ERROR);
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
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Koha Driver");
		$this->curlWrapper = new CurlWrapper();

	}

	function __destruct(){
	    $this->curlWrapper = null;

		//Cleanup any connections we have to other systems
		if ($this->dbConnection != null){
			if ($this->getNumHoldsStmt != null){
				$this->getNumHoldsStmt->close();
			}
			mysqli_close($this->dbConnection);
		}
	}

	public function hasNativeReadingHistory() {
		return true;
	}

    public function hasReadingHistoryUpdatesOfILS(){
        return false;
    }

    /**
     * @param User $patron
     * @param int $page
     * @param int $recordsPerPage
     * @param string $sortOption
     * @return array
     * @throws Exception
     */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		// TODO implement sorting, currently only done in catalogConnection for koha reading history
		//TODO prepend indexProfileType
		$this->initDatabaseConnection();

		//Figure out if the user is opted in to reading history
        /** @noinspection SqlResolve */
		$sql = "select disable_reading_history from borrowers where borrowernumber = {$patron->username}";
		$historyEnabledRS = mysqli_query($this->dbConnection, $sql);
		if ($historyEnabledRS){
			$historyEnabledRow = $historyEnabledRS->fetch_assoc();
			$historyEnabled = !$historyEnabledRow['disable_reading_history'];

			// Update patron's setting in Aspen if the setting has changed in Koha
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
                /** @noinspection SqlResolve */
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
						$curTitle['checkout'] = $checkOutDate->format('m-d-Y'); // this format is expected by Aspen's java cron program.

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
//					if (is_int($historyEntry['recordId'])) $historyEntry['recordId'] = (string) $historyEntry['recordId']; // Marc Record Constructor expects the recordId as a string.
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource.':'.$historyEntry['recordId']);
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

	/**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param   User $patron The User to place a hold for
     * @param   string $recordId The id of the bib record
     * @param   string $pickupBranch The branch where the user wants to pickup the item when available
     * @param   null|string $cancelDate  The date the hold should be automatically cancelled
     * @return  mixed                 True if successful, false if unsuccessful
     *                                If an error occurs, return a AspenError
     * @access  public
     */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null){
		$hold_result = array();
		$hold_result['success'] = false;

		//Set pickup location
		$campus = strtoupper($pickupBranch);

		//Get a specific item number to place a hold on even though we are placing a title level hold.
		//because.... Koha
		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$recordDriver = new MarcRecordDriver($recordId);
		if (!$recordDriver->isValid()){
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		$marcRecord = $recordDriver->getMarcRecord();

		//TODO: Use get services method to determine if title or item holds are available

		//Check to see if the title requires item level holds
		/** @var File_MARC_Data_Field[] $holdTypeFields */
		$itemLevelHoldAllowed = false;
		$itemLevelHoldOnly = false;
		$indexingProfile = $this->getIndexingProfile();
		$holdTypeFields = $marcRecord->getFields($indexingProfile->itemTag);
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
		if ($itemLevelHoldAllowed){
		    //TODO: Handle item level holds
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

            global $active_ip;
			$holdParams = [
			    'service' => 'HoldTitle',
                'patron_id' => $patron->username,
                'bib_id' => $recordDriver->getId(),
                'request_location' => $active_ip,
                'pickup_location' => $campus
                //TODO: Handle these options
                //needed_before_date (Optional)
                //pickup_expiry_date (Optional)
            ];

            $placeHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
            $placeHoldResponse = $this->getXMLWebServiceResponse($placeHoldURL);

			//If the hold is successful we go back to the account page and can see

			$hold_result['id'] = $recordId;
            /** @noinspection HtmlUnknownAnchorTarget */
            if ($placeHoldResponse->title) {
				//We redirected to the holds page, everything seems to be good
				$holds = $this->getHolds($patron, 1, -1, 'title');
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
				$hold_result['message'] = 'Your hold could not be placed. ' . $placeHoldResponse->code ;
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
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		$hold_result = array();
		$hold_result['success'] = false;

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$recordDriver = new MarcRecordDriver($recordId);
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

        global $active_ip;
        $holdParams = [
            'service' => 'HoldItem',
            'patron_id' => $patron->username,
            'bib_id' => $recordDriver->getId(),
            'item_id' => $itemId,
            'request_location' => $active_ip,
            'pickup_location' => $campus
            //TODO: Handle these options
            //needed_before_date (Optional)
            //pickup_expiry_date (Optional)
        ];

        $placeHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
        $placeHoldResponse = $this->getXMLWebServiceResponse($placeHoldURL);

		if ($placeHoldResponse->title) {
            //We redirected to the holds page, everything seems to be good
            $holds = $this->getHolds($patron, 1, -1, 'title');
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
            $hold_result['message'] = 'Your hold could not be placed. ' . $placeHoldResponse->code ;
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
	 * @return mixed        Array of the patron's holds on success, AspenError
	 * otherwise.
	 * @access public
	 */
    /** @noinspection PhpUnusedParameterInspection */
	public function getHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$this->initDatabaseConnection();

        /** @noinspection SqlResolve */
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
			$curHold['create'] = date_parse_from_format('Y-m-d H:i:s', $curRow['reservedate']);
			if (!empty($curRow['expirationdate'])){
                $dateTime = date_create_from_format('Y-m-d', $curRow['expirationdate']);
                $curHold['expire'] = $dateTime->getTimestamp();
            }

			$curHold['location'] = $curRow['branchcode'];
			$curHold['locationUpdateable'] = false;
			$curHold['currentPickupName'] = $curHold['location'];
			$curHold['position'] = $curRow['priority'];
			$curHold['frozen'] = false;
			$curHold['canFreeze'] = false;
			$curHold['cancelable'] = true;
			if ($curRow['suspend'] == '1'){
				$curHold['frozen'] = true;
				$curHold['status'] = "Suspended";
				if ($curRow['suspend_until'] != null){
					$curHold['status'] .= ' until ' . $curRow['suspend_until'];
				}
			}elseif ($curRow['found'] == 'W'){
				$curHold['status'] = "Ready to Pickup";
			}elseif ($curRow['found'] == 'T'){
				$curHold['status'] = "In Transit";
			}else{
				$curHold['status'] = "Pending";
				$curHold['canFreeze'] = true;
			}
			$curHold['cancelId'] = $curRow['reserve_id'];

			if ($bibId){
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($bibId);
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
		$xNum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $xNum, $cancelId, $locationId, $freezeValue);
	}

    /**
     * Update a hold that was previously placed in the system.
     * Can cancel the hold or update pickup locations.
     * @param User $patron
     * @param string $type
     * @param string $xNum
     * @param string $cancelId
     * @param integer $locationId
     * @param string $freezeValue
     * @return array
     */
    public function updateHoldDetailed($patron, $type, $xNum, $cancelId, $locationId, /** @noinspection PhpUnusedParameterInspection */ $freezeValue='off'){
		$titles = array();

		if (!isset($xNum) || empty($xNum)){
            if (is_array($cancelId)){
                $holdKeys = $cancelId;
            }else{
                $holdKeys = array($cancelId);
            }
		}else{
			$holdKeys = $xNum;
		}

		if ($type == 'cancel'){
			$allCancelsSucceed = true;

			//Post a request to koha
			foreach ($holdKeys as $holdKey){
				$holdParams = [
                    'service' => 'CancelHold',
                    'patron_id' => $patron->username,
                    'item_id' => $holdKey,
                ];

                $cancelHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
                $cancelHoldResponse = $this->getXMLWebServiceResponse($cancelHoldURL);

				//Parse the result
				if (isset($cancelHoldResponse->code) && ($cancelHoldResponse->code == 'Cancelled' || $cancelHoldResponse->code == 'Canceled')){
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
                return array(
                    'title' => $titles,
                    'success' => false,
                    'message' => 'Freezing and thawing holds is not supported.');
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

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null){
        $params = [
            'service' => 'RenewLoan',
            'patron_id' => $patron->username,
            'item_id' => $itemId,
        ];

        $renewURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($params);
        $renewResponse = $this->getXMLWebServiceResponse($renewURL);

        //Parse the result
        if (isset($renewResponse->success) && ($renewResponse->success == 1)){
            //We renewed the hold
            $success = true;
            $message = 'Your item was successfully renewed';
        }else{
            $success = false;
            $message = 'The item could not be renewed';
        }

		return array(
			'itemId' => $itemId,
			'success'  => $success,
			'message' => $message);
	}

	/**
	 * Get a list of fines for the user.
	 * Code taken from C4::Account getcharges method
	 *
	 * @param User $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getMyFines($patron, $includeMessages = false)
    {
        require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

        $this->initDatabaseConnection();

        //Get a list of outstanding fees
        /** @noinspection SqlResolve */
        $query = "SELECT * FROM accountlines WHERE borrowernumber = {$patron->username} and amountoutstanding > 0 ORDER BY date DESC";

        $allFeesRS = mysqli_query($this->dbConnection, $query);

        $fines = [];
        if ($allFeesRS->num_rows > 0) {
            while ($allFeesRow = $allFeesRS->fetch_assoc()) {
                $curFine = [
                    'date' => $allFeesRow['date'],
                    'reason' => $allFeesRow['accounttype'],
                    'message' => $allFeesRow['description'],
                    'amount' => StringUtils::money_format('%.2n', $allFeesRow['amount']),
                    'amountOutstanding' => StringUtils::money_format('%.2n', $allFeesRow['amountoutstanding']),
                ];
                $fines[] = $curFine;
            }
        }
		$allFeesRS->close();

		return $fines;
	}

	/** @var mysqli_stmt  */
	private $getNumHoldsStmt = null;

    /**
     * Get Total Outstanding fines for a user.  Lifted from Koha:
     * C4::Accounts.pm gettotalowed method
     *
     * @param User $patron
     * @return mixed
     */
	private function getOutstandingFineTotal($patron) {
		//Since borrowerNumber is stored in fees and payments, not fee_transactions,
		//this is done with two queries: the first gets all outstanding charges, the second
		//picks up any unallocated credits.
		$amountOutstanding = 0;
		$this->initDatabaseConnection();
        /** @noinspection SqlResolve */
		$amountOutstandingRS = mysqli_query($this->dbConnection, "SELECT SUM(amountoutstanding) FROM accountlines where borrowernumber = {$patron->username}");
		if ($amountOutstandingRS){
			$amountOutstanding = $amountOutstandingRS->fetch_array();
			$amountOutstanding = $amountOutstanding[0];
			$amountOutstandingRS->close();
		}

		return $amountOutstanding ;
	}

	private $oauthToken = null;
	function getOAuthToken(){
		if ($this->oauthToken == null){
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/oauth/token";
			$postParams = [
				'grant_type' => 'client_credentials',
				'client_id' => $this->accountProfile->oAuthClientId,
				'client_secret' => $this->accountProfile->oAuthClientSecret,
			];

			$this->curlWrapper->addCustomHeaders([
				'Accept: application/json',
				'Content-Type: application/x-www-form-urlencoded',
			], false);
			$response = $this->curlWrapper->curlPostPage($apiUrl, $postParams);
			$json_response = json_decode($response);
			if (!empty($json_response->access_token)){
				$this->oauthToken = $json_response->access_token;
			}else{
				$this->oauthToken = false;
			}
		}
		return $this->oauthToken;
	}
	function cancelHold($patron, $recordId, $cancelId = null) {
		return $this->updateHoldDetailed($patron, 'cancel', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
        $result = [
            'success' => false,
            'message' => 'Unable to freeze your hold.'
        ];

        $this->loginToKohaOpac($patron);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-user.pl#opac-user-holds');

		$reactivateDate = $this->aspenDateToKohaDate($dateToReactivate);
		$postVariables = [
			'reserve_id' => $itemToFreezeId,
			'suspend_until' => $reactivateDate,
			'submit' => '',
		];
		//This doesn't actually return status so we have to assume it works
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
			'Referer: ' . $catalogUrl . '/cgi-bin/koha/opac-user.pl'
		];
		$this->opacCurlWrapper->addCustomHeaders($headers, false);
		$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-modrequest-suspend.pl', $postVariables);
		if ($postResults != 'Internal Server Error'){
			$result = [
				'success' => true,
				'message' => 'The hold has been frozen.'
			];
		}else{
			$result['message'] .= ' ' . $postResults;
		}


//        $oauthToken = $this->getOAuthToken();
//        if ($oauthToken == false){
//	        $result['message'] = 'Unable to authenticate with the ILS.  Please try again later or contact the library.';
//        }else{
//          $apiUrl = $this->getWebServiceUrl() . "/api/v1/holds/{$itemToFreezeId}/suspension";
//          if (strlen($dateToReactivate) > 0){
//	            $postParams = [
//		            'end_date' => $dateToReactivate
//	            ];
//	            $postParams = json_encode($postParams);
//          }else{
//        	    $postParams = '';
//          }
//	        $this->curlWrapper->addCustomHeaders([
//		        'Accept: application/json',
//		        'Content-Type: application/x-www-form-urlencoded',
//		        'Authorization: Bearer ' . $oauthToken,
//		        //'Authorization: Basic ' . base64_encode($this->accountProfile->oAuthClientId . ':' . $this->accountProfile->oAuthClientSecret),
//	        ], true);
//	        $response = $this->curlWrapper->curlPostPage($apiUrl, $postParams);
//	        if(!$response) {
//		        return $result;
//	        }else{
//		        $hold_response = json_decode($response, false);
//		        if (isset($hold_response->error)){
//			        $result['message'] = $hold_response->error;
//			        $result['success'] = true;
//		        }else{
//			        print_r($hold_response);
//			        if ($hold_response->suspended && $hold_response->suspended == true) {
//				        $result['message'] = 'Your hold was ' . translate('frozen') .' successfully.';
//				        $result['success'] = true;
//			        }
//		        }
//	        }
//        }

        return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId) {
        $result = [
			'success' => false,
			'message' => 'Unable to thaw your hold.'
		];

		$this->loginToKohaOpac($patron);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-user.pl#opac-user-holds');

		$postVariables = [
			'reserve_id' => $itemToThawId,
			'submit' => '',
		];
		$headers = [
			'Content-Type: application/x-www-form-urlencoded'
		];
		$this->opacCurlWrapper->addCustomHeaders($headers, false);
		//This doesn't actually return status so we have to assume it works
		$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-modrequest-suspend.pl', $postVariables);
		if ($postResults != 'Internal Server Error'){
			$result = [
				'success' => true,
				'message' => 'The hold has been thawed.'
			];
		}else{
			$result['message'] .= ' ' . $postResults;
		}
        return $result;
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToUpdateId, $newPickupLocation, 'off');
	}

    public function showOutstandingFines()
    {
        return true;
    }

	private function loginToKohaOpac($user) {
		$catalogUrl = $this->accountProfile->vendorOpacUrl;
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
	 * @param $kohaUrl
	 * @param $postParams
	 * @return mixed
	 */
	protected function postToKohaPage($kohaUrl, $postParams) {
		if ($this->opacCurlWrapper == null){
			$this->opacCurlWrapper = new CurlWrapper();
		}
		return $this->opacCurlWrapper->curlPostPage($kohaUrl, $postParams);
	}

	protected function getKohaPage($kohaUrl){
		if ($this->opacCurlWrapper == null){
			$this->opacCurlWrapper = new CurlWrapper();
		}
		return $this->opacCurlWrapper->curlGetPage($kohaUrl);
	}

	function processEmailResetPinForm()
	{
		$result = array(
			'success' => false,
			'error' => "Unknown error sending password reset."
		);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$username = strip_tags($_REQUEST['username']);
		$email = strip_tags($_REQUEST['email']);
		$postVariables = [
			'koha_login_context' => 'opac',
			'username' => $username,
			'email' => $email,
			'sendEmail' => 'Submit'
		];
		if (isset($_REQUEST['resendEmail'])){
			$postVariables['resendEmail'] = strip_tags($_REQUEST['resendEmail']);
		}

		$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-password-recovery.pl', $postVariables);

		$messageInformation = [];
		if (preg_match('%<div class="alert alert-warning">(.*?)</div>%s', $postResults, $messageInformation)){
			$error = $messageInformation[1];
			$error = str_replace('<h3>', '<h4>', $error);
			$error = str_replace('</h3>', '</h4>', $error);
			$error = str_replace('/cgi-bin/koha/opac-password-recovery.pl', '/MyAccount/EmailResetPin', $error);
			$result['error'] = trim($error);
		}elseif (preg_match('%<div id="password-recovery">\s+<div class="alert alert-info">(.*?)<a href="/cgi-bin/koha/opac-main.pl"">Return to the main page</a>\s+</div>\s+</div>%s', $postResults, $messageInformation)) {
			$message = $messageInformation[1];
			$result['success'] = true;
			$result['message'] = trim($message);
		}

		return $result;
	}

	/**
	 * Returns one of three values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * @return string
	 */
	function getForgotPasswordType()
	{
		return 'emailResetLink';
	}

	function getEmailResetPinTemplate()
	{
		return 'kohaEmailResetPinLink.tpl';
	}

	function getSelfRegistrationFields(){
		//TODO: Load these from the Koha database
		//global $library;
		$fields = array();
		$location = new Location();
		//$location->libraryId = $library->libraryId;
		$location->validHoldPickupBranch = 1;

		$pickupLocations = array();
		if ($location->find()) {
			while($location->fetch()) {
				$pickupLocations[$location->code] = $location->displayName;
			}
			asort($pickupLocations);
		}

		//Library
		$fields['librarySection'] = array('property' => 'librarySection', 'type' => 'section', 'label' => 'Library', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_branchcode' => array('property' => 'borrower_branchcode', 'type' => 'enum', 'label' => 'Home Library', 'description' => 'Please choose the Library location you would prefer to use', 'values' => $pickupLocations, 'required' => true)
		]);

		//Identity
		$fields['identitySection'] = array('property' => 'identitySection', 'type' => 'section', 'label' => 'Identity', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_title' => array('property'=>'borrower_title', 'type'=>'enum', 'label'=>'Salutation', 'values'=>[''=>'', 'Mr'=>'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms', 'Miss' => 'Miss','Dr.'=>'Dr.'], 'description'=>'Your first name', 'required' => false),
			'borrower_surname' => array('property'=>'borrower_surname', 'type'=>'text', 'label'=>'Surname', 'description'=>'Your last name', 'maxLength' => 60, 'required' => true),
			'borrower_firstname' => array('property'=>'borrower_firstname', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 25, 'required' => true),
			'borrower_dateofbirth' => array('property'=>'borrower_dateofbirth', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true),
			'borrower_initials' => array('property'=>'borrower_initials', 'type'=>'text', 'label'=>'Initials', 'description'=>'Initials', 'maxLength' => 25, 'required' => false),
			'borrower_othernames' => array('property'=>'borrower_othernames', 'type'=>'text', 'label'=>'Other names', 'description'=>'Other names you go by', 'maxLength' => 128, 'required' => false),
			'borrower_sex' => array('property'=>'borrower_sex', 'type'=>'enum', 'label'=>'Gender', 'values'=>[''=>'None Specified','F'=>'Female', 'M'=>'Male'], 'description'=>'Gender', 'required' => false),

		]);
		//Main Address
		$fields['mainAddressSection'] = array('property' => 'mainAddressSection', 'type' => 'section', 'label' => 'Main Address', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_address' => array('property'=>'borrower_address', 'type'=>'text', 'label'=>'Address', 'description'=>'Address', 'maxLength' => 128, 'required' => true),
			'borrower_address2' => array('property'=>'borrower_address2', 'type'=>'text', 'label'=>'Address 2', 'description'=>'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_city' => array('property'=>'borrower_city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true),
			'borrower_state' => array('property'=>'borrower_state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true),
			'borrower_zipcode' => array('property'=>'borrower_zipcode', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true),
			'borrower_country' => array('property'=>'borrower_country', 'type'=>'text', 'label'=>'Country', 'description'=>'Country', 'maxLength' => 32, 'required' => false),
		]);
		//Contact information
		$fields['contactInformationSection'] = array('property' => 'contactInformationSection', 'type' => 'section', 'label' => 'Contact Information', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_phone' => array('property'=>'borrower_phone', 'type'=>'text', 'label'=>'Primary Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_email' => array('property'=>'borrower_email', 'type'=>'email', 'label'=>'Primary Email', 'description'=>'Email', 'maxLength' => 128, 'required' => false),
		]);
		//Contact information
		$fields['additionalContactInformationSection'] = array('property' => 'additionalContactInformationSection', 'type' => 'section', 'label' => 'Additional Contact Information', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_phonepro' => array('property'=>'borrower_phonepro', 'type'=>'text', 'label'=>'Secondary Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_mobile' => array('property'=>'borrower_mobile', 'type'=>'text', 'label'=>'Other Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_emailpro' => array('property'=>'borrower_emailpro', 'type'=>'email', 'label'=>'Secondary Email', 'description'=>'Email', 'maxLength' => 128, 'required' => false),
			'borrower_fax' => array('property'=>'borrower_fax', 'type'=>'text', 'label'=>'Fax (xxx-xxx-xxxx)', 'description'=>'Fax', 'maxLength' => 128, 'required' => false),
		]);
		//Alternate address
		$fields['alternateAddressSection'] = array('property' => 'alternateAddressSection', 'type' => 'section', 'label' => 'Alternate address', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_B_address' => array('property'=>'borrower_B_address', 'type'=>'text', 'label'=>'Alternate Address', 'description'=>'Address', 'maxLength' => 128, 'required' => false),
			'borrower_B_address2' => array('property'=>'borrower_B_address2', 'type'=>'text', 'label'=>'Address 2', 'description'=>'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_B_city' => array('property'=>'borrower_B_city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => false),
			'borrower_B_state' => array('property'=>'borrower_B_state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => false),
			'borrower_B_zipcode' => array('property'=>'borrower_B_zipcode', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => false),
			'borrower_B_country' => array('property'=>'borrower_B_country', 'type'=>'text', 'label'=>'Country', 'description'=>'Country', 'maxLength' => 32, 'required' => false),
			'borrower_B_phone' => array('property'=>'borrower_B_phone', 'type'=>'text', 'label'=>'Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_B_email' => array('property'=>'borrower_B_email', 'type'=>'email', 'label'=>'Email', 'description'=>'Email', 'maxLength' => 128, 'required' => false),
			'borrower_contactnote' => array('property'=>'borrower_contactnote', 'type'=>'textarea', 'label'=>'Contact  Notes', 'description'=>'Additional information for the alternate contact', 'maxLength' => 128, 'required' => false),
		]);
		//Alternate contact
		$fields['alternateContactSection'] = array('property' => 'alternateContactSection', 'type' => 'section', 'label' => 'Alternate contact', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_altcontactsurname' => array('property'=>'borrower_altcontactsurname', 'type'=>'text', 'label'=>'Surname', 'description'=>'Your last name', 'maxLength' => 60, 'required' => false),
			'borrower_altcontactfirstname' => array('property'=>'borrower_altcontactfirstname', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 25, 'required' => false),
			'borrower_altcontactaddress1' => array('property'=>'borrower_altcontactaddress1', 'type'=>'text', 'label'=>'Address', 'description'=>'Address', 'maxLength' => 128, 'required' => false),
			'borrower_altcontactaddress2' => array('property'=>'borrower_altcontactaddress2', 'type'=>'text', 'label'=>'Address 2', 'description'=>'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_altcontactaddress3' => array('property'=>'borrower_altcontactaddress3', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => false),
			'borrower_altcontactstate' => array('property'=>'borrower_altcontactstate', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactzipcode' => array('property'=>'borrower_altcontactzipcode', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactcountry' => array('property'=>'borrower_altcontactcountry', 'type'=>'text', 'label'=>'Country', 'description'=>'Country', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactphone' => array('property'=>'borrower_altcontactphone', 'type'=>'text', 'label'=>'Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false),
		]);

		return $fields;
	}

	function selfRegister()
	{
		$result = [
			'success' => false,
		];

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$selfRegPage = $this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl');
		$captcha = '';
		$captchaDigest = '';
		$captchaInfo = [];
		if (preg_match('%<span class="hint">(?:.*)<strong>(.*?)</strong></span>%s', $selfRegPage, $captchaInfo)){
			$captcha = $captchaInfo[1];
		}
		$captchaInfo = [];
		if (preg_match('%<input type="hidden" name="captcha_digest" value="(.*?)" />%s', $selfRegPage, $captchaInfo)){
			$captchaDigest = $captchaInfo[1];
		}

		$postFields = [];
		$postFields['borrower_branchcode'] = $_REQUEST['borrower_branchcode'];
		$postFields['borrower_title'] = $_REQUEST['borrower_title'];
		$postFields['borrower_surname'] = $_REQUEST['borrower_surname'];
		$postFields['borrower_firstname'] = $_REQUEST['borrower_firstname'];
		$postFields['borrower_dateofbirth'] = str_replace('-', '/', $_REQUEST['borrower_dateofbirth']);
		$postFields['borrower_initials'] = $_REQUEST['borrower_initials'];
		$postFields['borrower_othernames'] = $_REQUEST['borrower_othernames'];
		$postFields['borrower_sex'] = $_REQUEST['borrower_sex'];
		$postFields['borrower_address'] = $_REQUEST['borrower_address'];
		$postFields['borrower_address2'] = $_REQUEST['borrower_address2'];
		$postFields['borrower_city'] = $_REQUEST['borrower_city'];
		$postFields['borrower_state'] = $_REQUEST['borrower_state'];
		$postFields['borrower_zipcode'] = $_REQUEST['borrower_zipcode'];
		$postFields['borrower_country'] = $_REQUEST['borrower_country'];
		$postFields['borrower_phone'] = $_REQUEST['borrower_phone'];
		$postFields['borrower_email'] = $_REQUEST['borrower_email'];
		$postFields['borrower_phonepro'] = $_REQUEST['borrower_phonepro'];
		$postFields['borrower_mobile'] = $_REQUEST['borrower_mobile'];
		$postFields['borrower_emailpro'] = $_REQUEST['borrower_emailpro'];
		$postFields['borrower_fax'] = $_REQUEST['borrower_fax'];
		$postFields['borrower_B_address'] = $_REQUEST['borrower_B_address'];
		$postFields['borrower_B_address2'] = $_REQUEST['borrower_B_address2'];
		$postFields['borrower_B_city'] = $_REQUEST['borrower_B_city'];
		$postFields['borrower_B_state'] = $_REQUEST['borrower_B_state'];
		$postFields['borrower_B_zipcode'] = $_REQUEST['borrower_B_zipcode'];
		$postFields['borrower_B_country'] = $_REQUEST['borrower_B_country'];
		$postFields['borrower_B_phone'] = $_REQUEST['borrower_B_phone'];
		$postFields['borrower_B_email'] = $_REQUEST['borrower_B_email'];
		$postFields['borrower_contactnote'] = $_REQUEST['borrower_contactnote'];
		$postFields['borrower_altcontactsurname'] = $_REQUEST['borrower_altcontactsurname'];
		$postFields['borrower_altcontactfirstname'] = $_REQUEST['borrower_altcontactfirstname'];
		$postFields['borrower_altcontactaddress1'] = $_REQUEST['borrower_altcontactaddress1'];
		$postFields['borrower_altcontactaddress2'] = $_REQUEST['borrower_altcontactaddress2'];
		$postFields['borrower_altcontactaddress3'] = $_REQUEST['borrower_altcontactaddress3'];
		$postFields['borrower_altcontactstate'] = $_REQUEST['borrower_altcontactstate'];
		$postFields['borrower_altcontactzipcode'] = $_REQUEST['borrower_altcontactzipcode'];
		$postFields['borrower_altcontactcountry'] = $_REQUEST['borrower_altcontactcountry'];
		$postFields['borrower_altcontactphone'] = $_REQUEST['borrower_altcontactphone'];
		$postFields['captcha'] = $captcha;
		$postFields['captcha_digest'] = $captchaDigest;
		$postFields['action'] = 'create';
		$headers = [
			'Content-Type: application/x-www-form-urlencoded'
		];
		$this->opacCurlWrapper->addCustomHeaders($headers, false);
		$selfRegPageResponse = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl', $postFields);

		$matches = [];
		if (preg_match('%<h1>Registration Complete!</h1>.*?<span id="patron-userid">(.*?)</span>.*?<span id="patron-password">(.*?)</span>%s', $selfRegPageResponse, $matches)){
			$username = $matches[1];
			$password = $matches[2];
			$result['success'] = true;
			$result['username'] = $username;
			$result['password'] = $password;
		}
		return $result;
	}

	function updatePin(User $user, string $oldPin, string $newPin)
	{
		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$this->loginToKohaOpac($user);

		$postFields = [
			'Oldkey' => $oldPin,
			'Newkey' => $newPin,
			'Confirm' => $newPin
		];

		$pinFormResponse = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-passwd.pl', $postFields);
		if (preg_match('%<div class="alert">(.*?)</div>%s', $pinFormResponse, $matches)) {
			$error = $matches[1];
			$error = str_replace('<h3>', '<h4>', $error);
			$error = str_replace('</h3>', '</h4>', $error);
			return ['success' => false, 'errors' => trim($error)];
		}else if (preg_match('/Password updated/s', $pinFormResponse)) {
			return ['success' => true, 'message' => 'Your password was updated successfully.'];
		}
		return ['success' => false, 'errors' => "Unknown error updating password."];
	}

	function hasMaterialsRequestSupport(){
		return true;
	}
	function getNewMaterialsRequestForm()
	{
		global $interface;
		require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
		$itypeMap = new TranslationMap();
		$itypeMap->name = 'itype';
		$itypeMap->indexingProfileId = $this->getIndexingProfile()->id;
		$iTypes = [];
		if ($itypeMap->find(true)){
			/** @var TranslationMapValue $value */
			/** @noinspection PhpUndefinedFieldInspection */
			foreach ($itypeMap->translationMapValues as $value){
				$iTypes[$value->value] = $value->translation;
			}
		}
		$pickupLocations = [];
		$locations = new Location();
		$locations->orderBy('displayName');
		$locations->find();
		while ($locations->fetch()) {
			$pickupLocations[$locations->code] = $locations->displayName;
		}
		$interface->assign('pickupLocations', $pickupLocations);

		$fields = [
			array('property'=>'title', 'type'=>'text', 'label'=>'Title', 'description'=>'The title of the item to be purchased', 'maxLength'=>255, 'required' => true),
			array('property'=>'author', 'type'=>'text', 'label'=>'Author', 'description'=>'The author of the item to be purchased', 'maxLength'=>80, 'required' => false),
			array('property'=>'copyrightdate', 'type'=>'text', 'label'=>'Copyright Date', 'description'=>'Copyright or publication year, for example: 2016', 'maxLength'=>4, 'required' => false),
			array('property'=>'isbn', 'type'=>'text', 'label'=>'Standard number (ISBN, ISSN or other)', 'description'=>'', 'maxLength'=>80, 'required' => false),
			array('property'=>'publishercode', 'type'=>'text', 'label'=>'Publisher', 'description'=>'', 'maxLength'=>80, 'required' => false),
			array('property'=>'collectiontitle', 'type'=>'text', 'label'=>'Collection', 'description'=>'', 'maxLength'=>80, 'required' => false),
			array('property'=>'place', 'type'=>'text', 'label'=>'Publication place', 'description'=>'', 'maxLength'=>80, 'required' => false),
			array('property'=>'quantity', 'type'=>'text', 'label'=>'Quantity', 'description'=>'', 'maxLength'=>4, 'required' => false),
			array('property'=>'itemtype', 'type'=>'enum', 'values'=>$iTypes, 'label'=>'Item type', 'description'=>'', 'required' => false),
			array('property'=>'branchcode', 'type'=>'enum', 'values'=>$pickupLocations, 'label'=>'Library', 'description'=>'', 'required' => false),
			array('property'=>'note', 'type'=>'textarea', 'label'=>'Note', 'description'=>'', 'required' => false),
		];


		$interface->assign('submitUrl', '/MaterialsRequest/NewRequestIls');
		$interface->assign('structure', $fields);
		$interface->assign('saveButtonText', 'Submit your suggestion');

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('materialsRequestForm', $fieldsForm);

		return 'new-koha-request.tpl';
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	function processMaterialsRequestForm($user)
	{
		$this->loginToKohaOpac($user);
		$postFields = [
			'title' => $_REQUEST['title'],
			'author' => $_REQUEST['author'],
			'copyrightdate' => $_REQUEST['copyrightdate'],
			'isbn' => $_REQUEST['isbn'],
			'publishercode' => $_REQUEST['publishercode'],
			'collectiontitle' => $_REQUEST['collectiontitle'],
			'place' => $_REQUEST['place'],
			'quantity' => $_REQUEST['quantity'],
			'itemtype' => $_REQUEST['itemtype'],
			'branchcode' => $_REQUEST['branchcode'],
			'note' => $_REQUEST['note'],
			'negcap' => '',
			'suggested_by_anyone' => 0,
			'op' => 'add_confirm'
		];
		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$submitSuggestionResponse = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl', $postFields);
		if (preg_match('%<div class="alert alert-error">(.*?)</div>%s', $submitSuggestionResponse, $matches)) {
			return ['success' => false, 'message' => $matches[1]];
		}elseif (preg_match('/Your purchase suggestions/', $submitSuggestionResponse)){
			return ['success' => true, 'message' => 'Successfully submitted your request'];
		}else{
			return ['success' => false, 'message' => 'Unknown error submitting request'];
		}
	}

	function getMaterialsRequests(User $user)
	{
		$this->loginToKohaOpac($user);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$requestsPage = $this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl');

		$matches = [];
		$allRequests = [];
		/** @noinspection HtmlUnknownAttribute */
		if (preg_match('%<table id="suggestt" .*?<tbody>(.*)</tbody>%s', $requestsPage, $matches)) {
			$tableBody = $matches[1];
			preg_match_all('%<tr>.*?<input type="checkbox" class="cb" name="delete_field" value="(.*?)" />.*?<td>(.*?)</td>\s+<td>(.*?)</td>\s+<td>\s+<span class="tdlabel">Note: </span>(.*?)</td>\s+<td>(.*?)</td>\s+<td>\s+<span class="tdlabel">Status:</span>(.*?)</td>%s', $tableBody, $tableRows, PREG_SET_ORDER);
			foreach ($tableRows as $tableRow){
				$request = [];
				$request['id'] = $tableRow[1];
				$request['summary'] = trim($tableRow[2]);
				$request['suggestedOn'] = trim($tableRow[3]);
				$request['note'] = trim($tableRow[4]);
				$request['managedBy'] = trim($tableRow[5]);
				$request['status'] = trim($tableRow[6]);
				$allRequests[] = $request;
			}
		}

		return $allRequests;
	}

	function getMaterialsRequestsPage(User $user)
	{
		$allRequests = $this->getMaterialsRequests($user);

		global $interface;
		$interface->assign('allRequests', $allRequests);

		return 'koha-requests.tpl';
	}

	function deleteMaterialsRequests(/** @noinspection PhpUnusedParameterInspection */User $user)
	{
		$this->loginToKohaOpac($user);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl');

		$postFields = [
			'op' => 'delete_confirm',
			'delete_field' => $_REQUEST['delete_field']
		];
		$this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl', $postFields);

		return [
			'success' => true,
			'message' => 'deleted your requests'
		];
	}

	/**
	 * Gets a form to update contact information within the ILS.
	 *
	 * @param User $user
	 * @return string|null
	 */
	function getPatronUpdateForm($user)
	{
		//This is very similar to a patron self so we are going to get those fields and then modify
		$patronUpdateFields = $this->getSelfRegistrationFields();
		//Display sections as headings
		$patronUpdateFields['librarySection']['renderAsHeading'] = true;
		$patronUpdateFields['identitySection']['renderAsHeading'] = true;
		$patronUpdateFields['mainAddressSection']['renderAsHeading'] = true;
		$patronUpdateFields['contactInformationSection']['renderAsHeading'] = true;
		$patronUpdateFields['additionalContactInformationSection']['renderAsHeading'] = true;
		$patronUpdateFields['alternateAddressSection']['renderAsHeading'] = true;
		$patronUpdateFields['alternateContactSection']['renderAsHeading'] = true;

		//Set default values
		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM borrowers where borrowernumber = " . mysqli_escape_string($this->dbConnection, $user->username);
		$results = mysqli_query($this->dbConnection, $sql);
		if ($curRow = $results->fetch_assoc()){
			foreach ($curRow as $property => $value){
				$objectProperty = 'borrower_' . $property;
				if ($property == 'dateofbirth'){
					$user->$objectProperty = $this->kohaDateToAspenDate($value);
				}else{
					$user->$objectProperty = $value;
				}
			}
		}

		global $interface;
		$patronUpdateFields[] = array('property'=>'updateScope', 'type'=>'hidden', 'label'=>'Update Scope', 'description'=>'', 'value' => 'contact');
		/** @noinspection PhpUndefinedFieldInspection */
		$user->updateScope = 'contact';
		$interface->assign('submitUrl', '/MyAccount/Profile');
		$interface->assign('structure', $patronUpdateFields);
		$interface->assign('object', $user);
		$interface->assign('saveButtonText', 'Update Contact Information');

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		return $fieldsForm;
	}

	function kohaDateToAspenDate($date){
		if (strlen($date) == 0){
			return $date;
		}else{
			list($year, $month, $day) = explode('-', $date);
			return "$day-$month-$year";
		}
	}

	/**
	 * Converts the string for submission to the web form which is different than the
	 * format within the database.
	 * @param string $date
	 * @return string
	 */
	function aspenDateToKohaDate($date){
		if (strlen($date) == 0){
			return $date;
		}else{
			list($day, $month, $year) = explode('-', $date);
			return "$day/$month/$year";
		}
	}
}