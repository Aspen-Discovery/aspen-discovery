<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
class Koha extends AbstractIlsDriver {
	private $dbConnection = null;

	/** @var CurlWrapper */
	private $curlWrapper;

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
		if ($canUpdateContactInfo) {
			$updateErrors[] = "Profile Information can not be updated.";
		}
		return $updateErrors;
	}

	private $transactions = array();

	/**
	 * @param User $patron
	 * @return array
	 */
	public function getCheckouts($patron) {
		if (isset($this->transactions[$patron->id])){
			return $this->transactions[$patron->id];
		}

		//Get transactions by screen scraping
		$transactions = array();

		$this->initDatabaseConnection();

        /** @noinspection SqlResolve */
        $sql = "SELECT issues.*, items.biblionumber, title, author from issues left join items on items.itemnumber = issues.itemnumber left join biblio ON items.biblionumber = biblio.biblionumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()){
			$transaction = array();
			$transaction['checkoutSource'] = 'ILS';

			$transaction['id'] = $curRow['issue_id'];
			$transaction['recordId'] = $curRow['biblionumber'];
			$transaction['shortId'] = $curRow['biblionumber'];
			$transaction['title'] = $curRow['title'];
			$transaction['author'] = $curRow['author'];

			$dateDue = DateTime::createFromFormat('Y-m-d H:i:s', $curRow['date_due']);
			if ($dateDue){
				$dueTime = $dateDue->getTimestamp();
			}else{
				$dueTime = null;
			}
			$transaction['dueDate'] = $dueTime;
			$transaction['itemId'] = $curRow['itemnumber'];
			$transaction['renewIndicator'] = $curRow['itemnumber'];
			$transaction['renewCount'] = $curRow['renewals'];

			if ($transaction['id'] && strlen($transaction['id']) > 0){
				$transaction['recordId'] = $transaction['id'];
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($transaction['recordId']);
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
     * @return PEAR_Error|User|null
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

		foreach ($barcodesToTest as $i=>$barcode) {
		    //Authenticate the user using KOHA ILSDI
            $authenticationURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?service=AuthenticatePatron&username=' . $barcode . '&password=' . $password;
            $authenticationResponse = $this->getXMLWebServiceResponse($authenticationURL);
            if (isset($authenticationResponse->id)){
                $patronId = $authenticationResponse->id;
                /** @noinspection SqlResolve */
                $sql = "SELECT borrowernumber, cardnumber, surname, firstname, streetnumber, streettype, address, address2, city, zipcode, country, email, phone, mobile, categorycode, dateexpiry, password, userid, branchcode from borrowers where borrowernumber = $patronId";

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

                    $city = strtok($userFromDb['city'], ',');
                    $state = strtok(',');
                    $city = trim($city);
                    $state = trim($state);

                    $user->_address1 = trim($userFromDb['streetnumber'] . ' ' . $userFromDb['address'] . ' ' . $userFromDb['address2']);
                    $user->_city     = $city;
                    $user->_state    = $state;
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
            }
		}
		return null;
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
     *                                If an error occurs, return a PEAR_Error
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
	 *                              If an error occurs, return a PEAR_Error
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
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
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
				$curHold['canFreeze'] = true;
			}
			$curHold['canFreeze'] = false;
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

	public function renewCheckout($patron, $recordId, $itemId, $itemIndex){
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
	public function getMyFines($patron, $includeMessages = false){


		$this->initDatabaseConnection();

		//Get a list of outstanding fees
        /** @noinspection SqlResolve */
		$query = "SELECT * FROM fees JOIN fee_transactions AS ft on(id = fee_id) WHERE borrowernumber = {$patron->username} and accounttype in (select accounttype from accounttypes where class='fee' or class='invoice') ";

		$allFeesRS = mysqli_query($this->dbConnection, $query);

		$fines = array();
		while ($allFeesRow = $allFeesRS->fetch_assoc()){
			$feeId = $allFeesRow['id'];
            /** @noinspection SqlResolve */
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
		$amountOutstandingRS = mysqli_query($this->dbConnection, "SELECT SUM(amount) FROM fees LEFT JOIN fee_transactions on(fees.id = fee_transactions.fee_id) where fees.borrowernumber = {$patron->username}");
		if ($amountOutstandingRS){
			$amountOutstanding = $amountOutstandingRS->fetch_array();
			$amountOutstanding = $amountOutstanding[0];
			$amountOutstandingRS->close();
		}

        /** @noinspection SqlResolve */
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