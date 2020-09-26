<?php

require_once ROOT_DIR . '/sys/SIP2.php';
class CarlX extends SIP2Driver{
	public $patronWsdl;
	public $catalogWsdl;

	private $soapClient;

	public function __construct($accountProfile) {
	    parent::__construct($accountProfile);
		global $configArray;
		$this->patronWsdl  = $configArray['Catalog']['patronApiWsdl'];
		$this->catalogWsdl = $configArray['Catalog']['catalogApiWsdl'];
	}

	function initDatabaseConnection()
	{
		if (!isset($this->dbConnection)) {
			$port = empty($this->accountProfile->databasePort) ? '1521' : $this->accountProfile->databasePort;
			$ociConnection = $this->accountProfile->databaseHost . ':' . $port . '/' . $this->accountProfile->databaseName;
			$this->dbConnection = oci_connect($this->accountProfile->databaseUser, $this->accountProfile->databasePassword, $ociConnection);
			if (!$this->dbConnection || oci_error($this->dbConnection) != 0) {
				global $logger;
				$logger->log("Error connecting to CARL.X database " . oci_error($this->dbConnection), Logger::LOG_ERROR);
				$this->dbConnection = null;
			}
			global $timer;
			$timer->logTime("Initialized connection to CARL.X");
		}
	}

	public function patronLogin($username, $password, $validatedViaSSO){
		global $timer;

		//Remove any spaces from the barcode
		$username = preg_replace('/[^0-9a-zA-Z]/', '', trim($username));
		$password = trim($password);

		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID   = $username;
		$request->Modifiers  = '';

		$result = $this->doSoapRequest('getPatronInformation', $request);

		if ($result){
			if (isset($result->Patron)){
				//Check to see if the pin matches
				if ($result->Patron->PatronPIN == $password || $validatedViaSSO){
					$fullName = $result->Patron->FullName;
					$firstName = $result->Patron->FirstName;
					$lastName = $result->Patron->LastName;

					$userExistsInDB = false;
					$user = new User();
					$user->source   = $this->accountProfile->name;
					$user->username = $result->Patron->GeneralUserID;
					if ($user->find(true)){
						$userExistsInDB = true;
					}

					$forceDisplayNameUpdate = false;
					$firstName = isset($firstName) ? $firstName : '';
					if ($user->firstname != $firstName) {
						$user->firstname = $firstName;
						$forceDisplayNameUpdate = true;
					}
					$lastName = isset($lastName) ? $lastName : '';
					if ($user->lastname != $lastName){
						$user->lastname = isset($lastName) ? $lastName : '';
						$forceDisplayNameUpdate = true;
					}
					if ($forceDisplayNameUpdate){
						$user->displayName = '';
					}
					$user->_fullname     = isset($fullName) ? $fullName : '';
					$user->cat_username = $username;
					$user->cat_password = $result->Patron->PatronPIN;
					$user->email        = $result->Patron->Email;

					if ($userExistsInDB && $user->trackReadingHistory != $result->Patron->LoanHistoryOptInFlag) {
						$user->trackReadingHistory = $result->Patron->LoanHistoryOptInFlag;
					}

					$user->_emailReceiptFlag    = $result->Patron->EmailReceiptFlag;
					$user->_availableHoldNotice = $result->Patron->SendHoldAvailableFlag;
					$user->_comingDueNotice     = $result->Patron->SendComingDueFlag;
					$user->_phoneType           = $result->Patron->PhoneType;

					$homeBranchCode = strtolower($result->Patron->DefaultBranch);
					$location = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(1)){
						unset($location);
						$user->homeLocationId = 0;
						// Logging for Diagnosing PK-1846
						global $logger;
						$logger->log('CarlX Driver: No Location found, user\'s homeLocationId being set to 0. User : '.$user->id, Logger::LOG_WARNING);
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

					if (isset($result->Patron->Addresses)){
						//Find the primary address
						$primaryAddress = null;
						foreach ($result->Patron->Addresses->Address as $address){
							if ($address->Type == 'Primary'){
								$primaryAddress = $address;
								break;
							}
						}
						if ($primaryAddress != null){
							$user->_address1 = $primaryAddress->Street;
							$user->_address2 = $primaryAddress->City . ', ' . $primaryAddress->State;
							$user->_city     = $primaryAddress->City;
							$user->_state    = $primaryAddress->State;
							$user->_zip      = $primaryAddress->PostalCode;
						}
					}

					if (isset($result->Patron->EmailNotices)) {
						$user->_notices = $result->Patron->EmailNotices;
					}

					$user->patronType  = $result->Patron->PatronType; // Example: "ADULT"
					$user->_web_note    = '';
					$user->phone       = $result->Patron->Phone1;
					$user->_expires     = $this->extractDateFromCarlXDateField($result->Patron->ExpirationDate);
					$user->_expired     = 0; // default setting
					$user->_expireClose = 0;

					$timeExpire   = strtotime($user->_expires);
					$timeNow      = time();
					$timeToExpire = $timeExpire - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60) {
						if ($timeToExpire <= 0) {
							$user->_expired = 1;
						}
						$user->_expireClose = 1;
					}

					if ($userExistsInDB){
						$user->update();
					}else{
						$user->created = date('Y-m-d');
						$user->insert();
					}

					$timer->logTime("patron logged in successfully");
					return $user;
				}
			}
		}

		$timer->logTime("patron login failed");
		return null;
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll() {
		return true;
	}

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll($patron) {
		global $logger;

		//renew the item via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mysip = new sip2();
		$mysip->hostname = $this->accountProfile->sipHost;
		$mysip->port = $this->accountProfile->sipPort;

		$renew_result = array(
				'success' => false,
				'message' => array(),
				'Renewed' => 0,
				'NotRenewed' => $patron->_numCheckedOutIls,
				'Total' => $patron->_numCheckedOutIls
		);
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])){
					$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				}else{
					$mysip->AO = 'NASH'; /* set AO to value returned */
				}
				if (isset($result['variable']['AN'][0])) {
					$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}else{
					$mysip->AN = '';
				}

				$mysip->patron    = $patron->cat_username;
				$mysip->patronpwd = $patron->cat_password;

				$in = $mysip->msgRenewAll();
				//print_r($in . '<br/>');
				$msg_result = $mysip->get_message($in);
				//print_r($msg_result);

				if (preg_match("/^66/", $msg_result)) {
					$result = $mysip->parseRenewAllResponse($msg_result);
					$logger->log("Renew all response\r\n" . print_r($msg_result, true), Logger::LOG_ERROR);

					$renew_result['success'] = ($result['fixed']['Ok'] == 1);
					$renew_result['Renewed'] = ltrim($result['fixed']['Renewed'], '0');
					if (strlen($renew_result['Renewed']) == 0){
						$renew_result['Renewed'] = 0;
					}

					$renew_result['NotRenewed'] = ltrim($result['fixed']['NotRenewed'], '0');
					if (strlen($renew_result['NotRenewed']) == 0){
						$renew_result['NotRenewed'] = 0;
					}
					if (isset($result['variable']['AF'])){
						$renew_result['message'][] = $result['variable']['AF'][0];
					}

					if ($renew_result['NotRenewed'] > 0){
						$renew_result['message'] = array_merge($renew_result['message'], $result['variable']['BN']);
					}
				}else{
					$logger->log("Invalid message returned from SIP server '$msg_result''", Logger::LOG_ERROR);
					$renew_result['message'] = array("Invalid message returned from SIP server");
				}
			}else{
				$logger->log("Could not authenticate with the SIP server", Logger::LOG_ERROR);
				$renew_result['message'] = array("Could not authenticate with the SIP server");
			}
		}else{
			$logger->log("Could not connect to the SIP server", Logger::LOG_ERROR);
			$renew_result['message'] = array("Could not connect to circulation server, please try again later.");
		}

		return $renew_result;
	}

	private $genericResponseSOAPCallOptions = array(
		'features' => SOAP_WAIT_ONE_WAY_CALLS, // This setting overcomes the SOAP client's expectation that there is no response from our update request.
		'trace' => 1,                          // enable use of __getLastResponse, so that we can determine the response.
	);

	/**
	 * @param $requestName
	 * @param $request
	 * @param string $WSDL
	 * @param array $soapRequestOptions
	 * @return false|stdClass
	 */
	private function doSoapRequest($requestName, $request, $WSDL = '', $soapRequestOptions = array()) {
		if (empty($WSDL)) { // Let the patron WSDL be the assumed default WSDL when not specified.
			if (!empty($this->patronWsdl)) {
				$WSDL = $this->patronWsdl;
			} else {
				global $logger;
				$logger->log('No Default Patron WSDL defined for SOAP calls in CarlX Driver', Logger::LOG_ERROR);
				return false;
			}
		}

		// There are exceptions in the Soap Client that need to be caught for smooth functioning
		//MDN 6/24/2019 connection timeout is too long if we retry 3 times.
		//  Updating to a 1 second timeout and only trying twice which means a failed call will be no more than 2 seconds rather than 9
		$soapRequestOptions['connection_timeout'] = 1;
		$connectionPassed = false;
		$numTries = 0;
		$result = false;
		while (!$connectionPassed && $numTries < 2){
			try {
				$this->soapClient = new SoapClient($WSDL, $soapRequestOptions);
				$result = $this->soapClient->$requestName($request);
				$connectionPassed = true;
			} catch (SoapFault $e) {
				global $logger;
				$logger->log("Error connecting to SOAP " . $e, Logger::LOG_WARNING);
			}
			$numTries++;
		}
		if (!$connectionPassed){
			return false;
		}

		return $result;
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return mixed
	 */
	public function renewCheckout($patron, $recordId, $itemId=null, $itemIndex=null) {
		// Renew Via SIP
		return $result = $this->renewCheckoutViaSIP($patron, $itemId);
	}

	private $holdStatusCodes = array(
	                                  'H'  => 'Hold Shelf',
	                                  ''   => 'In Queue',
	                                  'IH' => 'In Transit',
	                                  // '?' => 'Suspended',
	                                  // '?' => 'filled',
	);
	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($user) {
		$holds = array(
			'available'   => array(),
			'unavailable' => array()
		);

		//Search for the patron in the database
		$result = $this->getPatronTransactions($user);

		if ($result && ($result->HoldItemsCount > 0 || $result->UnavailableHoldsCount > 0)) {

			// Available Holds
			if ($result->HoldItemsCount > 0) {
				//TODO: a single hold is not in an array; Need to verify that multiple holds are in an array
				if (!is_array($result->HoldItems->HoldItem)) $result->HoldItems->HoldItem = array($result->HoldItems->HoldItem); // For the case of a single hold
				foreach($result->HoldItems->HoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$carlID         = $this->fullCarlIDfromBID($bibId);
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch); //TODO: Use local DB; will require adding ILS branch numbers to DB or memcache (there is a getAllBranchInfo Call)

					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
					$curHold['cancelId']           = $hold->Identifier;
					$curHold['position']           = $hold->QueuePosition;
					$curHold['recordId']           = $carlID;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['locationUpdateable'] = false;
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold['expire']             = strtotime($expireDate); // give a time stamp  // use this for available holds
					$curHold['reactivate']         = null;
					$curHold['reactivateTime']     = null;
					$curHold['frozen']             = isset($hold->Suspended) && ($hold->Suspended == true);
					$curHold['cancelable']         = false; 
					$curHold['canFreeze']          = false;

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID);
					if ($recordDriver->isValid()){
						$curHold['groupedWorkId'] = $recordDriver->getPermanentId();
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl('medium', true);
						$curHold['link']            = $recordDriver->getLinkUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData(); //Load rating information

						if (empty($curHold['title'])){
							$curHold['title'] = $recordDriver->getTitle();
						}
						if (empty($curHold['author'])){
							$curHold['author'] = $recordDriver->getPrimaryAuthor();
						}
					}
					$holds['available'][]   = $curHold;

				}
			}

			// Unavailable Holds
			if ($result->UnavailableHoldsCount > 0) {
				if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) $result->UnavailableHoldItems->UnavailableHoldItem = array($result->UnavailableHoldItems->UnavailableHoldItem); // For the case of a single hold
				foreach($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$carlID         = $this->fullCarlIDfromBID($bibId);
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch);

					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
					$curHold['cancelId']           = $hold->Identifier; // James Staub declares cancelId is synonymous with holdId 20200613
					// CarlX API v1.9.6.3 does not accurately calculate hold queue position. See https://ww2.tlcdelivers.com/helpdesk/Default.asp?TicketID=500458
					$curHold['position']           = $hold->QueuePosition; 
					//$unavailableHoldViaSIP		= $this->getUnavailableHoldViaSIP($user, $hold->Identifier); // TO DO: should absolutely be refactored to merge API and SIP2 unavailable holds arrays outside of the API UnavailableHoldItem foreach loop - adds ~ 20 seconds to load James Staub's holds (30 items across 5 linked accounts)
					//$curHold['position']		= $unavailableHoldViaSIP['queuePosition'];
					$curHold['recordId']           = $carlID;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['frozen']             = $hold->Suspended;
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold['automaticCancellation'] = strtotime($expireDate); // use this for unavailable holds
					$curHold['cancelable']         = true;

					if ($curHold['frozen']){
						$curHold['reactivate']         = $this->extractDateFromCarlXDateField($hold->SuspendedUntilDate);
						$curHold['reactivateTime']     = strtotime($hold->SuspendedUntilDate);
						$curHold['status']             = 'Frozen';
					}
					// CarlX [9.6.4.3] will not allow update hold (suspend hold, change pickup location) on item level hold. UnavailableHoldItem ~ /^ITEM ID: / if the hold is an item level hold.
					if (strpos($curHold['cancelId'],'ITEM ID: ') === 0) {
						$curHold['canFreeze'] = false;
						$curHold['locationUpdateable'] = false;
					} elseif (strpos($curHold['cancelId'],'BID: ') === 0) {
						$curHold['canFreeze'] = true;
						$curHold['locationUpdateable'] = true;
					} else { // TO DO: Evaluate whether issue level holds are suspendable
						$curHold['canFreeze'] = false;
						$curHold['locationUpdateable'] = false;
					}

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID);
					if ($recordDriver->isValid()){
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl('medium', true);
						$curHold['link']            = $recordDriver->getLinkUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData(); //Load rating information

						if (empty($curHold['title'])){
							$curHold['title'] = $recordDriver->getTitle();
						}
						if (empty($curHold['author'])){
							$curHold['author'] = $recordDriver->getPrimaryAuthor();
						}
					}

					$holds['unavailable'][] = $curHold;

				}
			}

		} else {
			//TODO: Log Errors
		}

		return $holds;
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
     * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		return $this->placeHoldViaSIP($patron, $recordId, $pickupBranch, $cancelDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User $patron The User to place a hold for
	 * @param   string $recordId The id of the bib record
	 * @param   string $itemId The id of the item to hold
	 * @param   string $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null) {
		// TODO: Implement placeItemHold() method. // CarlX [9.6.4.3] does not allow item level holds via SIP2
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param   User $patron The User to cancel the hold for
	 * @param   string $recordId The id of the bib record
	 * @param   string $cancelId Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId = null) {
		return $this->placeHoldViaSIP($patron, $cancelId, null, null, 'cancel');

	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $recordId);
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$pickupLocation = $unavailableHoldViaSIP['pickupLocation']; // NB branchcode not branchnumber
		$freezeReactivationDate = $dateToReactivate . 'B';
		$result = $this->placeHoldViaSIP($patron, $recordId, $pickupLocation, null, 'update', $queuePosition, 'freeze', $freezeReactivationDate);
		return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $recordId);
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$pickupLocation = $unavailableHoldViaSIP['pickupLocation']; // NB branchcode not branchnumber
		$timeStamp = strtotime('+2 years'); // TO DO: read hold NNA or sync with default NNA (2 years?)
		$cancelDate = date('m/d/Y', $timeStamp);
		$result = $this->placeHoldViaSIP($patron, $recordId, $pickupLocation, $cancelDate, 'update', $queuePosition, 'thaw');
		return $result;
	}

	function changeHoldPickupLocation($patron, $recordId, $holdId, $newPickupLocation) {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $holdId);
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$freeze = null;
		$freezeReactivationDate = null;
		if (!empty($unavailableHoldViaSIP['freezeReactivationDate']) && substr($unavailableHoldViaSIP['freezeReactivationDate'],-1) == 'B') {
			$freeze = true;
			$freezeReactivationDate = $unavailableHoldViaSIP['freezeReactivationDate'];
		}
		$result = $this->placeHoldViaSIP($patron, $holdId, $newPickupLocation, null, 'update', $queuePosition, $freeze, $freezeReactivationDate);
		return $result;
	}

	/**
	 * Split a name into firstName, lastName, middleName.
	 *a
	 * Assumes the name is entered as LastName, FirstName MiddleName
	 * @param $fullName
	 * @return array
	 */
	public function splitFullName($fullName) {
		$fullName = str_replace(",", " ", $fullName);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$nameParts = explode(' ', $fullName);
		$lastName = strtolower($nameParts[0]);
		$middleName = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstName = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middleName;
		return array($fullName, $lastName, $firstName);
	}

	public function getCheckouts($user) {
		$checkedOutTitles = array();

		//Search for the patron in the database
		$result = $this->getPatronTransactions($user);
		//global $logger;
		//$logger->log("Patron Transactions\r\n" . print_r($result, true), Logger::LOG_ERROR );

		$itemsToLoad = array();
		if (!$result){
			global $logger;
			$logger->log('Failed to retrieve user Check outs from CarlX API call.', Logger::LOG_WARNING);
		}else{
			//TLC provides both ChargeItems and OverdueItems as separate elements, we can combine for loading
			if (!empty($result->ChargeItems->ChargeItem)) {
				if (!is_array($result->ChargeItems->ChargeItem)) {
					// Structure an single entry as an array of one.
					$itemsToLoad[] = $result->ChargeItems->ChargeItem;
				}else{
					$itemsToLoad = $result->ChargeItems->ChargeItem;
				}
			}
			if (!empty($result->OverdueItems->OverdueItem)) {
				if (!is_array($result->OverdueItems->OverdueItem)) {
					// Structure an single entry as an array of one.
					$itemsToLoad[] = $result->OverdueItems->OverdueItem;
				}else{
					$itemsToLoad = array_merge($itemsToLoad, $result->OverdueItems->OverdueItem);
				}
			}

			foreach ($itemsToLoad as $chargeItem) {
				$carlID = $this->fullCarlIDfromBID($chargeItem->BID);
				$dueDate = strstr($chargeItem->DueDate, 'T', true);
				$curTitle['checkoutSource']  = 'ILS';
				$curTitle['recordId']        = $carlID;
				$curTitle['shortId']         = $chargeItem->BID;
				$curTitle['id']              = $chargeItem->BID;
				$curTitle['barcode']         = $chargeItem->ItemNumber;   // Barcode & ItemNumber are the same for CarlX
				$curTitle['itemId']          = $chargeItem->ItemNumber;
				$curTitle['title']           = $chargeItem->Title;
				$curTitle['author']          = $chargeItem->Author;
				$curTitle['dueDate']         = strtotime($dueDate);
				$curTitle['checkoutDate']    = strstr($chargeItem->TransactionDate, 'T', true);
				$curTitle['renewCount']      = isset($chargeItem->RenewalCount) ? $chargeItem->RenewalCount : 0;
				$curTitle['canRenew']        = true;
				$curTitle['renewIndicator']  = $chargeItem->ItemNumber;

				$curTitle['format']          = 'Unknown';
				if (!empty($carlID)){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID); // This needs the $carlID
					if ($recordDriver->isValid()){
						$curTitle['groupedWorkId'] = $recordDriver->getPermanentId();
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
						$curTitle['ratingData']    = $recordDriver->getRatingData();
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['format']        = $recordDriver->getPrimaryFormat();
						$curTitle['title']         = $recordDriver->getTitle();
						$curTitle['title_sort']    = $recordDriver->getSortableTitle();
						$curTitle['author']        = $recordDriver->getPrimaryAuthor();
						$curTitle['link']          = $recordDriver->getLinkUrl();
					}else{
						$curTitle['coverUrl']     = "";
					}
				}
				if (empty($curTitle['title_sort'])){
					$curTitle['title_sort']  = preg_replace('/^The\s|^A\s/i', '',$curTitle['title']);
				}
				$checkedOutTitles[] = $curTitle;
			}
		}

		return $checkedOutTitles;
	}

	function updatePin($user, $oldPin, $newPin) {
		$request = $this->getSearchbyPatronIdRequest($user);
		$request->Patron->PatronPIN = $newPin;
		$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

		if (is_null($result)) {
			$result = $this->soapClient->__getLastResponse();
			if ($result) {
				$unxml   = new XML_Unserializer();
				$unxml->unserialize($result);
				$response = $unxml->getUnserializedData();

				if ($response) {
					$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
					if (!$success) {
						// TODO: might not want to include sending message back to user
						$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
						return ['success' => false, 'message' => 'Failed to update your pin'. ($errorMessage ? ' : ' .$errorMessage : '')];
					} else {
						$user->cat_password = $newPin;
						$user->update();
						return ['success' => true, 'message' => "Your pin number was updated successfully."];
					}

				} else {
					global $logger;
					$logger->log('Unable to read XML from CarlX response when attempting to update Patron PIN.', Logger::LOG_ERROR);
					return ['success' => false, 'message' => 'Unable to update your pin.'];
				}

			} else {
				global $logger;
				$logger->log('CarlX ILS gave no response when attempting to update Patron PIN.', Logger::LOG_ERROR);
				return ['success' => false, 'message' => 'Unable to update your pin.'];
			}
		} elseif (!$result) {
			return ['success' => false, 'message' => 'Failed to contact Circulation System.'];
		}
	}

	public function updatePatronInfo($user, $canUpdateContactInfo) {
		$result = [
			'success' => false,
			'messages' => []
		];
		if ($canUpdateContactInfo){

			$request = $this->getSearchbyPatronIdRequest($user);


			// Patron Info to update.
			$request->Patron->Email  = $_REQUEST['email'];
			$request->Patron->Phone1 = $_REQUEST['phone'];

			if (isset($_REQUEST['workPhone'])){
				$request->Patron->Phone2 = $_REQUEST['workPhone'];
			}

			$request->Patron->Addresses->Address->Type        = 'Primary';
			$request->Patron->Addresses->Address->Street      = $_REQUEST['address1'];
			$request->Patron->Addresses->Address->City        = $_REQUEST['city'];
			$request->Patron->Addresses->Address->State       = $_REQUEST['state'];
			$request->Patron->Addresses->Address->PostalCode  = $_REQUEST['zip'];


				if (isset($_REQUEST['emailReceiptFlag']) && ($_REQUEST['emailReceiptFlag'] == 'yes' || $_REQUEST['emailReceiptFlag'] == 'on')){
					// if set check & on check must be combined because checkboxes/radios don't report 'offs'
					$request->Patron->EmailReceiptFlag = 1;
				}else{
					$request->Patron->EmailReceiptFlag = 0;
				}
				if (isset($_REQUEST['availableHoldNotice']) && ($_REQUEST['availableHoldNotice'] == 'yes' || $_REQUEST['availableHoldNotice'] == 'on')){
					// if set check & on check must be combined because checkboxes/radios don't report 'offs'
					$request->Patron->SendHoldAvailableFlag = 1;
				}else{
					$request->Patron->SendHoldAvailableFlag = 0;
				}
				if (isset($_REQUEST['comingDueNotice']) && ($_REQUEST['comingDueNotice'] == 'yes' || $_REQUEST['comingDueNotice'] == 'on')){
					// if set check & on check must be combined because checkboxes/radios don't report 'offs'
					$request->Patron->SendComingDueFlag = 1;
				}else{
					$request->Patron->SendComingDueFlag = 0;
				}
				if (isset($_REQUEST['phoneType'])) {
					$request->Patron->PhoneType = $_REQUEST['phoneType'];
				}

			if (isset($_REQUEST['notices'])){
				$request->Patron->EmailNotices = $_REQUEST['notices'];
			}

			if (!empty($_REQUEST['pickupLocation'])) {
				$homeLocation = new Location();
				if ($homeLocation->get('code', $_REQUEST['pickupLocation'])) {
					$homeBranchCode = strtoupper($_REQUEST['pickupLocation']);
					$request->Patron->DefaultBranch = $homeBranchCode;
				}
			}

			$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

			if (is_null($result)) {
				$result = $this->soapClient->__getLastResponse();
				if ($result) {
					$unxml   = new XML_Unserializer();
					$unxml->unserialize($result);
					$response = $unxml->getUnserializedData();

					if ($response) {
						$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
						if (!$success) {
							$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
							$result['messages'][] = 'Failed to update your information'. ($errorMessage ? ' : ' .$errorMessage : '');
						}else{
							$result['success'] = true;
							$result['messages'][] = 'Your account was updated successfully.';
						}

					} else {
						$result['messages'][] = 'Unable to update your information.';
						global $logger;
						$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', Logger::LOG_ERROR);
					}

				} else {
					$result['messages'][] = 'Unable to update your information.';
					global $logger;
					$logger->log('CarlX ILS gave no response when attempting to update Patron Information.', Logger::LOG_ERROR);
				}
			}

		} else {
			$result['messages'][] = 'You can not update your information.';
		}

		if ($result['success'] == false && empty($result['messages'])){
			$result['messages'][] = 'Unknown error updating your account';
		}
		return $result;
	}

	public function getSelfRegistrationFields() {
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName',   'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName',  'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => false);
		$fields[] = array('property'=>'lastName',   'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$birthDateMin = date('Y-m-d', strtotime('-114 years'));
			$birthDateMax = date('Y-m-d', strtotime('-14 years'));
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'min'=>$birthDateMin, 'max'=>$birthDateMax, 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address',     'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city',        'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('default'=>'TN','property'=>'state',       'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 2, 'required' => true);
		$fields[] = array('property'=>'zip',         'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'phone',       'type'=>'text',  'label'=>'Primary Phone', 'description'=>'Primary Phone', 'maxLength'=>15, 'required'=>true);
		$fields[] = array('property'=>'email',       'type'=>'email', 'label'=>'Email', 'description'=>'Email', 'maxLength' => 128, 'required' => true);
//		$fields[] = array('property'=>'pin',         'type'=>'pin',   'label'=>'Pin', 'description'=>'Your desired 4-digit pin', 'maxLength' => 4, 'size' => 4, 'required' => true);
//		$fields[] = array('property'=>'pin1',        'type'=>'pin',   'label'=>'Confirm Pin', 'description'=>'Re-type your desired 4-digit pin', 'maxLength' => 4, 'size' => 4, 'required' => true);
		return $fields;
	}

	function selfRegister(){
		global $library,
		       $configArray,
		       $active_ip,
		       $interface;
		$success = false;

		$lastPatronID = new Variable();
		$lastPatronID->get('name', 'last_selfreg_patron_id');

		if (!empty($lastPatronID->value)) {
			$currentPatronIDNumber = rand(1,13) + $lastPatronID->value;

			$tempPatronID = $configArray['Catalog']['selfRegIDPrefix'] . str_pad($currentPatronIDNumber, $configArray['Catalog']['selfRegIDNumberLength'], '0', STR_PAD_LEFT);

			$firstName  = trim(strtoupper($_REQUEST['firstName']));
			$middleName = trim(strtoupper($_REQUEST['middleName']));
			$lastName   = trim(strtoupper($_REQUEST['lastName']));
			$address    = trim(strtoupper($_REQUEST['address']));
			$city       = trim(strtoupper($_REQUEST['city']));
			$state      = trim(strtoupper($_REQUEST['state']));
			$zip        = trim($_REQUEST['zip']);
			$email      = trim(strtoupper($_REQUEST['email']));
//			$pin        = trim($_REQUEST['pin']);
//			$pin1       = trim($_REQUEST['pin1']);
			$phone      = preg_replace('/^(\d{3})(\d{3})(\d{4})$/','$1-$2-$3',preg_replace('/\D/','',trim($_REQUEST['phone'])));

//			if (!empty($pin) && !empty($pin1) && $pin == $pin1) {


				// DENY REGISTRATION IF EMAIL MATCHES @LOAOA.COM
				if (substr(strtolower($email),-10,10) == '@loaoa.com' || substr(strtolower($email),-9,9) == 'zamte.com') {
					global $logger;
					$logger->log('Online Registration used forbidden email: ' . $email . ' IP: ' . $active_ip, Logger::LOG_ERROR);
					return array(
						'success' => false,
						'barcode' => $tempPatronID,
					);
				}

				// DENY REGISTRATION IF DUPLICATE EMAIL IS FOUND IN CARL.X
				// searchPatron on Email appears to be case-insensitive and
				// appears to eliminate spurious whitespace
				$request				= new stdClass();
				$request->Modifiers			= '';
				$request->AllSearchTermMatch		= 'true';
				$request->SearchTerms			= new stdClass();
				$request->SearchTerms->ApplicationType	= 'exact match';
				$request->SearchTerms->Attribute	= 'Email';
				$request->SearchTerms->Value		= $email;
				$request->PagingParameters		= new stdClass();
				$request->PagingParameters->StartPos	= 0;
				$request->PagingParameters->NoOfRecords	= 1;
				$request->Modifiers			= new stdClass();
				$request->Modifiers->InstitutionCode	= 'NASH';
				$result = $this->doSoapRequest('searchPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
				if ($result) {
					$noEmailMatch = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'No matching records found');
					if ($noEmailMatch === false) {
						global $logger;
						$logger->log('Online Registration Email already exists in Carl. Email: ' . $email . ' IP: ' . $active_ip, Logger::LOG_ERROR);
						return array(
							'success' => false,
							'barcode' => $tempPatronID,
						);
					}
				}

				// CREATE PATRON REQUEST
				$request                                         = new stdClass();
				$request->Modifiers                              = '';
				//$request->PatronFlags->PatronFlag                = 'DUPCHECK_ALTID'; // Duplicate check for alt id
				$request->PatronFlags->PatronFlag[0]                = 'DUPCHECK_NAME_DOB'; // Duplicate check for name/date of birth
				$request->PatronFlags->PatronFlag[1]                = 'VALIDATE_ZIPCODE'; // Validate ZIP against Carl.X Admin legal ZIPs
				$request->Patron				= new stdClass();
				$request->Patron->PatronID                       = $tempPatronID;
				$request->Patron->Email                          = $email;
				$request->Patron->FirstName                      = $firstName;
				$request->Patron->MiddleName                     = $middleName;
				$request->Patron->LastName                       = $lastName;
				$request->Patron->Addresses			= new stdClass();
				$request->Patron->Addresses->Address		= new stdClass();
				$request->Patron->Addresses->Address->Type       = 'Primary';
				$request->Patron->Addresses->Address->Street     = $address;
				$request->Patron->Addresses->Address->City       = $city;
				$request->Patron->Addresses->Address->State      = $state;
				$request->Patron->Addresses->Address->PostalCode = $zip;
				$request->Patron->PreferredAddress		= 'Primary';
//				$request->Patron->PatronPIN			= $pin;
				$request->Patron->Phone1			= $phone;
				$request->Patron->RegistrationDate		= date('c'); // Registration Date, format ISO 8601
				$request->Patron->LastActionDate		= date('c'); // Registration Date, format ISO 8601
				$request->Patron->LastEditDate			= date('c'); // Registration Date, format ISO 8601

				$request->Patron->EmailNotices			= $configArray['Catalog']['selfRegEmailNotices'];
				$request->Patron->DefaultBranch			= $configArray['Catalog']['selfRegDefaultBranch'];
				$request->Patron->PatronExpirationDate		= $configArray['Catalog']['selfRegPatronExpirationDate'];
				$request->Patron->PatronStatusCode		= $configArray['Catalog']['selfRegPatronStatusCode'];
				$request->Patron->PatronType			= $configArray['Catalog']['selfRegPatronType'];
				$request->Patron->RegBranch			= $configArray['Catalog']['selfRegRegBranch'];
				$request->Patron->RegisteredBy			= $configArray['Catalog']['selfRegRegisteredBy'];

				// VALIDATE BIRTH DATE.
				// DENY REGISTRATION IF REGISTRANT IS NOT 14 - 113 YEARS OLD
				if ($library && $library->promptForBirthDateInSelfReg) {
					$birthDate			= trim($_REQUEST['birthDate']);
					$date				= strtotime(str_replace('-','/',$birthDate));
					$birthDateMin			= strtotime('-113 years');
					$birthDateMax			= strtotime('-14 years');
					if ($date >= $birthDateMin && $date <= $birthDateMax) {
						$request->Patron->BirthDate = date('Y-m-d', $date);
					} else {
						global $logger;
						$logger->log('Online Registrant is too young : birth date : ' . date('Y-m-d', $date), Logger::LOG_WARNING);
						return array(
							'success' => false,
							'message' => 'You must be 14 years old to register.'
						);
					}
				}

				$result = $this->doSoapRequest('createPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
				if (is_null($result) && $this->soapClient) {
					$result = $this->soapClient->__getLastResponse();

					if ($result) {
						$unxml = new XML_Unserializer();
						$unxml->unserialize($result);
						$response = $unxml->getUnserializedData();

						if ($response) {
							$success = isset($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'])
								&& stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
							if (!$success) {
								$errorMessage = array();
								if (is_array($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus'])) {
									foreach($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus'] as $errorResponse) {
										$errorMessage[] = $errorResponse['ns2:LongMessage'];
									}
								} else {
									$errorMessage[] = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
								}
								if (in_array('A patron with that id already exists', $errorMessage)) {
									global $logger;
									$logger->log('While self-registering user for CarlX, temp id number was reported in use. Increasing internal counter', Logger::LOG_ERROR);
									// Increment the temp patron id number.
									$lastPatronID->value = $currentPatronIDNumber;
									if (!$lastPatronID->update()) {
										$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', Logger::LOG_ERROR);
									}
								}
							} else {
								$lastPatronID->value = $currentPatronIDNumber;
								if (!$lastPatronID->update()) {
									global $logger;
									$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', Logger::LOG_ERROR);
								}
								// Get Patron
								$request = new stdClass();
								$request->SearchType = 'Patron ID';
								$request->SearchID   = $tempPatronID;
								$request->Modifiers  = '';

								$result = $this->doSoapRequest('getPatronInformation', $request);

/*
// PATRON-CREATED PIN IS BEING OVERWRITTEN BY CARL.X LAST 4 DIGITS OF PHONE NUMBER
								// Check That the Pin was set  (the create Patron call does not seem to set the Pin)
								if ($result && isset($result->Patron) && $result->Patron->PatronPIN == '') {
									$request->Patron->PatronPIN = $pin;
									$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
									if (is_null($result)) {
										$result = $this->soapClient->__getLastResponse();
										if ($result) {
											$unxml = new XML_Unserializer();
											$unxml->unserialize($result);
											$response = $unxml->getUnserializedData();

											if ($response) {
												$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
												if (!$success) {
													global $logger;
													$logger->log('Unable to set pin for Self-Registered user on update call after initial creation call.', Logger::LOG_ERROR);
													// The Pin will be an empty.
													// Return Success Any way, because the account was created.
													return array(
														'success' => true,
														'barcode' => $tempPatronID,
													);
												}
											}
										}
									}
								}
*/

								// FOLLOWING SUCCESSFUL SELF REGISTRATION, INPUT PATRON IP ADDRESS INTO PATRON RECORD NOTE
								$request 			= new stdClass();
								$request->Modifiers		= '';
								$request->Note			= new stdClass();
								$request->Note->PatronID	= $tempPatronID;
								$request->Note->NoteType	= 2;
								$request->Note->NoteText	= "Online registration from IP " . $active_ip;
								$result = $this->doSoapRequest('addPatronNote', $request, $this->patronWsdl,  $this->genericResponseSOAPCallOptions);

								if (is_null($result)) {
									$result = $this->soapClient->__getLastResponse();
									if ($result) {
										$unxml = new XML_Unserializer();
										$unxml->unserialize($result);
										$response = $unxml->getUnserializedData();
											if ($response) {
											$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
											if (!$success) {
												global $logger;
												$logger->log('Unable to write IP address in Patron Note.', Logger::LOG_ERROR);
												// Return Success Any way, because the account was created.
												return array(
													'success' => true,
													'barcode' => $tempPatronID,
												);
											}
										}
									}
								}

								// FOLLOWING SUCCESSFUL SELF REGISTRATION, EMAIL PATRON THE LIBRARY CARD NUMBER
								$body = $interface->fetch('Emails/self-registration.tpl');
								$body = $firstName . " " . $lastName . "\n\nThank you for registering for an Online Library Card. Your library card number is:\n\n" . $tempPatronID . "\n\n" . $body;
								require_once ROOT_DIR . '/sys/Email/Mailer.php';
								$mail = new Mailer();
								$subject = 'Welcome to the Nashville Public Library';
								$emailResult = $mail->send($email, $subject, $body);
								if ($emailResult === true){
									$result = array(
										'result' => true,
										'message' => 'Your email was sent successfully.'
									);
								} elseif (($emailResult instanceof AspenError)){
									$interface->assign('error', "Your request could not be sent: {$emailResult->getMessage()}.");
								} else {
									$interface->assign('error', "Your request could not be sent due to an unknown error.");
									global $logger;
									$logger->log("Mail List Failure (unknown reason), parameters: $email, $subject, $body", Logger::LOG_ERROR);
								}
								return array(
									'success' => $success,
									'barcode' => $tempPatronID,
									'patronName' => $firstName . ' ' . $lastName,
								);
							}
						} else {
							global $logger;
							$logger->log('Unable to read XML from CarlX response when attempting to create Patron.', Logger::LOG_ERROR);
						}
					} else {
						global $logger;
						$logger->log('CarlX ILS gave no response when attempting to create Patron.', Logger::LOG_ERROR);
					}
				}
//			} else {
//				global $logger;
//				$logger->log('CarlX Self Registration Form was passed bad data for a user\'s pin.', Logger::LOG_WARNING);
//			}
		} else {
			global $logger;
			$logger->log('No value for "last_selfreg_patron_id" set in Variables table. Can not self-register patron in CarlX Driver.', Logger::LOG_ERROR);
		}
		return array(
			'success' => $success
		);

	}

	public function getReadingHistory($user, $page = 1, $recordsPerPage = -1, $sortOption = 'checkedOut') {
		$readHistoryEnabled = false;
		$request = $this->getSearchbyPatronIdRequest($user);
		$result = $this->doSoapRequest('getPatronInformation', $request, $this->patronWsdl);
		if ($result && $result->Patron) {
			$readHistoryEnabled = $result->Patron->LoanHistoryOptInFlag;
		}

		if ($readHistoryEnabled) { // Create Reading History Request
			$historyActive = true;
			$readingHistoryTitles = array();
			$numTitles = 0;

			$request->HistoryType = 'L'; //  From Documentation: The type of charge history to return, (O)utreach or (L)oan History opt-in
			$result = $this->doSoapRequest('getPatronChargeHistory', $request);

			if ($result) {
				// Process Reading History Response
				if (!empty($result->ChargeHistoryItems->ChargeItem)) {
					foreach ($result->ChargeHistoryItems->ChargeItem as $readingHistoryEntry) {
						// Process Reading History Entries
						$checkOutDate  = new DateTime($readingHistoryEntry->ChargeDateTime);
						$curTitle  = array();
						$curTitle['itemId']       = $readingHistoryEntry->ItemNumber;
						$curTitle['id']           = $readingHistoryEntry->BID;
						$curTitle['shortId']      = $readingHistoryEntry->BID;
						$curTitle['recordId']     = $this->fullCarlIDfromBID($readingHistoryEntry->BID);
						$curTitle['title']        = rtrim($readingHistoryEntry->Title, ' /');
						$curTitle['checkout']     = $checkOutDate->format('m-d-Y'); // this format is expected by Aspen Discovery's java cron program.
						$curTitle['borrower_num'] = $user->id;
						$curTitle['dueDate']      = null; // Not available in ChargeHistoryItems
						$curTitle['author']       = null; // Not available in ChargeHistoryItems

						$readingHistoryTitles[] = $curTitle;
					}

					$numTitles = count($readingHistoryTitles);

					//process pagination
					if ($recordsPerPage != -1){
						$startRecord = ($page - 1) * $recordsPerPage;
						$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
					}

					set_time_limit(20 * count($readingHistoryTitles)); // Taken from Aspencat Driver

					// Fetch Additional Information for each Item
					foreach ($readingHistoryTitles as $key => $historyEntry){
						//Get additional information from resources table
						$historyEntry['ratingData']  = null;
						$historyEntry['permanentId'] = null;
						$historyEntry['linkUrl']     = null;
						$historyEntry['coverUrl']    = null;
						$historyEntry['format']      = 'Unknown';
						if (!empty($historyEntry['recordId'])){
							require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
							$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource.':'.$historyEntry['recordId']);
							if ($recordDriver->isValid()){
								$historyEntry['ratingData']  = $recordDriver->getRatingData();
								$historyEntry['permanentId'] = $recordDriver->getPermanentId();
								$historyEntry['linkUrl']     = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
								$historyEntry['coverUrl']    = $recordDriver->getBookcoverUrl('medium', true);
								$historyEntry['format']      = $recordDriver->getFormats();
								$historyEntry['author']      = $recordDriver->getPrimaryAuthor();
								if (empty($curTitle['title'])){
									$curTitle['title']         = $recordDriver->getTitle();
								}
							}

							$recordDriver = null;
						}
						$historyEntry['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($historyEntry['title']));

						$readingHistoryTitles[$key] = $historyEntry;
					}


				}

				// Return Reading History
				return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);

			} else {
				global $logger;
				$logger->log('CarlX ILS gave no response when attempting to get Reading History.', Logger::LOG_ERROR);
			}
		}
		return array('historyActive' => false, 'titles' => array(), 'numTitles' => 0);
	}

    public function performsReadingHistoryUpdatesOfILS(){
        return true;
    }
	public function doReadingHistoryAction($user, $action, $selectedTitles){
		switch ($action) {
			case 'optIn' :
			case 'optOut' :
				$request = $this->getSearchbyPatronIdRequest($user);
				$request->Patron->LoanHistoryOptInFlag = ($action == 'optIn');
				$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

				$success = false;
				// code block below has been taken from updatePatronInfo()
				if (is_null($result)) {
					$result = $this->soapClient->__getLastResponse();
					if ($result) {
						$unxml   = new XML_Unserializer();
						$unxml->unserialize($result);
						$response = $unxml->getUnserializedData();

						if ($response) {
							$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
							if (!$success) {
								$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
//								$updateErrors[] = 'Failed to update your information'. ($errorMessage ? ' : ' .$errorMessage : '');
							}

						} else {
//							$updateErrors[] = 'Unable to update your information.';
							global $logger;
							$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', Logger::LOG_ERROR);
						}

					} else {
//						$updateErrors[] = 'Unable to update your information.';
						global $logger;
						$logger->log('CarlX ILS gave no response when attempting to update Patron Information.', Logger::LOG_ERROR);
					}
				}
				return $success;

				break;

		}

	}

	public function getFines($user, $includeMessages = false) {
		$myFines = array();

		$request = $this->getSearchbyPatronIdRequest($user);

		// Fines
		$request->TransactionType = 'Fine';
		$result = $this->doSoapRequest('getPatronTransactions', $request);
		//global $logger;
		//$logger->log("Result of getPatronTransactions (Fine)\r\n" . print_r($result, true), Logger::LOG_ERROR);
		if ($result && !empty($result->FineItems->FineItem)) {
			if (!is_array($result->FineItems->FineItem)) {
				$result->FineItems->FineItem = array($result->FineItems->FineItem);
			}
			foreach($result->FineItems->FineItem as $fine) {
				// hard coded Nashville school branch IDs
				if ($fine->Branch == 0) {
					$fine->Branch = $fine->TransactionBranch;
				}
				if ($fine->Branch >= 30 && $fine->Branch <= 178 && $fine->Branch != 42 && $fine->Branch != 171) {
					$fine->System = "MNPS";
				} else {
					$fine->System = "NPL";
				}

				if ($fine->FineAmountPaid > 0) {
					$fine->FineAmount -= $fine->FineAmountPaid;
				}
				$myFines[] = array(
					'reason'  => $fine->FeeNotes,
					'amount'  => $fine->FineAmount,
					'message' => $fine->Title,
					'date'    => date('M j, Y', strtotime($fine->FineAssessedDate)),
					'system'  => $fine->System,
				);
			}
		}

		// Lost Item Fees

		// TODO: Lost Items don't have the fine amount
		$request->TransactionType = 'Lost';
		$result = $this->doSoapRequest('getPatronTransactions', $request);
		//$logger->log("Result of getPatronTransactions (Lost)\r\n" . print_r($result, true), Logger::LOG_ERROR);

		if ($result && !empty($result->LostItems->LostItem)) {
			if (!is_array($result->LostItems->LostItem)) {
				$result->LostItems->LostItem = array($result->LostItems->LostItem);
			}
			foreach($result->LostItems->LostItem as $fine) {
				// hard coded Nashville school branch IDs
				if ($fine->Branch == 0) {
					$fine->Branch = $fine->TransactionBranch;
				}
				if ($fine->Branch >= 30 && $fine->Branch <= 178 && $fine->Branch != 42 && $fine->Branch != 171) {
					$fine->System = "MNPS";
				} else {
					$fine->System = "NPL";
				}

				$myFines[] = array(
					'reason'  => $fine->FeeNotes,
//					'amount'  => $fine->FineAmount, // TODO: There is no corresponding amount
					'amount'  => $fine->FeeAmount,
					'message' => $fine->Title,
					'date'    => date('M j, Y', strtotime($fine->TransactionDate)),
					'system'  => $fine->System,
				);
			}
		}

		return $myFines;
	}

//	public function getFines($user) {
//		$myFines = array();
//
//		$request = $this->getSearchbyPatronIdRequest($user);
////		$request->CirculationFilter = false; //TODO: not sure what this filters, might be needed in actual system
//		$request->CirculationFilter = true;
//		$result = $this->doSoapRequest('getPatronFiscalHistory', $request);
//		if ($result && !empty($result->FiscalHistoryItem)) {
//			if (!is_array($result->FiscalHistoryItem)) {
//				$result->FiscalHistoryItem = array($result->FiscalHistoryItem); // single entries are not presented as an array
//			}
//			foreach($result->FiscalHistoryItem as $fine) {
//				if ($fine->FiscalType == 'Credit') {
//					$amount = $fine->Amount > 0 ? '-$' . sprintf('%0.2f', $fine->Amount / 100) : ''; // amounts are in cents
//				} else {
//					$amount = $fine->Amount > 0 ? '$' . sprintf('%0.2f', $fine->Amount / 100) : ''; // amounts are in cents
//				}
//				$myFines[] = array(
//					'reason'  => $fine->Notes,
//					'amount'  => $amount,
//					'message' => $fine->Title,
////					'date'    => $this->extractDateFromCarlXDateField($fine->TransDate), //TODO: set as datetime?
//					'date'    => date('M j, Y', strtotime($fine->TransDate)), //TODO: set as datetime?
//				);
//			}
//
//			//TODO: Look At Page Result if additional Calls need to be made.
//		}
//
//		return $myFines;
//	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return false|stdClass        Array of the patron's transactions on success
	 * @access public
	 */
	private function getPatronTransactions($user)
	{
		$request = $this->getSearchbyPatronIdRequest($user);
		$result = $this->doSoapRequest('getPatronTransactions', $request, $this->patronWsdl);
		return $result;
	}

	public function getPhoneTypeList() {
		// TODO: Store in memcache

		$request             = new stdClass();
		$request->Modifiers  = '';

		$result = $this->doSoapRequest('getPhoneTypeList', $request);
		if ($result) {
			$phoneTypes = array();
			foreach ($result->PhoneTypes->PhoneType as $phoneType) {
				$phoneTypes[$phoneType->SortGroup][$phoneType->PhoneTypeId] = $phoneType->Description;
			}
			return $phoneTypes;
		}
		return false;
	}

	private function getBranchInformation($branchNumber = null, $branchCode = null) {
//		TODO: Store in Memcache instead
		/** @var Memcache $memCache */
		global $memCache;

		if (!empty($branchNumber)) {
			$branchInfo = $memCache->get('carlx_branchNumbers');
			if (!empty($branchInfo) and isset($branchInfo[$branchNumber])) {
				return $branchInfo[$branchNumber];
			} else {
				$request                    = new stdClass();
				$request->BranchSearchType  = 'Branch Number';
				$request->BranchSearchValue = $branchNumber;
				$request->Modifiers         = '';
			}
		} elseif (!empty($branchCode)) {
			$branchInfo = $memCache->get('carlx_branchCodes');
			if (!empty($branchInfo) and isset($branchInfo[$branchCode])) {
				return $branchInfo[$branchCode];
			} else {
				$request                    = new stdClass();
				$request->BranchSearchType  = 'Branch Code';
				$request->BranchSearchValue = $branchCode;
				$request->Modifiers         = '';
			}
		} else {
			return false;
		}

		$result = $this->doSoapRequest('GetBranchInformation', $request, $this->catalogWsdl);
		global $configArray;
		if ($result && $result->BranchInfo) {
			if (!empty($branchNumber)) {
				$branchInfo = $memCache->get('carlx_branchNumbers');
				if ($branchInfo) {
					$branchInfo[$branchNumber] = $result->BranchInfo;
				} else {
					$branchInfo = array(
						$branchNumber = $result->BranchInfo
					);
				}
				$memCache->set('carlx_branchNumbers', $branchInfo, $configArray['Caching']['carlx_branchNumbers']);
			} elseif (!empty($branchCode)) {
				$branchInfo = $memCache->get('carlx_branchCodes');
				if ($branchInfo) {
					$branchInfo[$branchCode] = $result->BranchInfo;
				} else {
					$branchInfo = array(
						$branchCode => $result->BranchInfo
					);
				}
				$memCache->set('carlx_branchCodes', $branchInfo, $configArray['Caching']['carlx_branchCodes']);
			}
			return $result->BranchInfo; // convert to array instead?
		}
		return false;
	}

	/**
	 * @param $dateField string
	 * @return string
	 */
	private function extractDateFromCarlXDateField($dateField)
	{
		return strstr($dateField, 'T', true);
	}

	/**
	 * @param $user
	 * @return stdClass
	 */
	private function getSearchbyPatronIdRequest($user)
	{
		$request             = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID   = $user->cat_username; // TODO: Question: barcode/pin check
		$request->Modifiers  = '';
		return $request;
	}

	private function getUnavailableHold($patron, $holdID) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->TransactionType = 'UnavailableHold';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		if ($result && !empty($result->UnavailableHoldItems->UnavailableHoldItem)) {
			if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) {
				$result->UnavailableHoldItems->UnavailableHoldItem = array($result->UnavailableHoldItems->UnavailableHoldItem);
			}
			foreach($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
				if ($hold->BID == $holdID) {
					return $hold;
				}
			}
		}
		return false;
	}

	private function getUnavailableHoldViaSIP($patron, $holdId) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->TransactionType = 'UnavailableHold';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		global $configArray;
		//Place the hold via SIP 2
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		$success = false;
		$message = '';
		if ($mySip->connect()) {
			//send self check status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])){
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				}else{
					$mySip->AO = 'NASH'; /* set AO to value returned */ // hardcoded Nashville
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}else{
					$mySip->AN = '';
				}

				$mySip->patron    = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				$in = $mySip->msgPatronInformation('unavail',1,110); // hardcoded Nashville - circulation policy allows 100 holds for many borrower types
				$result = $mySip->parsePatronInfoResponse( $mySip->get_message($in) );

				if ($result && !empty($result['variable']['CD'])) {
					if (!is_array($result['variable']['CD'])) {
						$result['variable']['CD'] = (array)$result['variable']['CD'];
					}
					foreach($result['variable']['CD'] as $unavailableHold) {
						$hold = [];
						$hold['Raw'] = explode("^",$unavailableHold);
				                foreach ($hold['Raw'] as $item) {
			                    	    $field = substr($item,0,1);
			                    	    $value = substr($item,1);
			                    	    $clean = trim($value, "\x00..\x1F");
			                    	    if (trim($clean) <> '') {
		                            		$hold[$field] = $clean;
                        			    }
                				}
						if ((!empty($hold['B']) && ($hold['B'] == $holdId || "BID: " . $hold['B'] == $holdId)) || (!empty($hold['I']) && "ITEM ID: " . $hold['I'] == $holdId)) { 
// TO DO: evaluate how/whether Issue level holds should be handled
							$success = true;
							$pickupLocation = $hold['U']; // NB branchcode, not branchnumber
							$queuePosition = $hold['O'];
							$freezeReactivationDate = $hold['2']; // CarlX custom field CS^2 and XI are MM/DD/YYYY with suffix 'B'
							if (!empty($hold['T'])) {
								$title = $hold['T'];
							}
							break;	
						} 
					}
				}
			}
		}
		return array(
			'holdId'			=> $holdId,
			'title'				=> $title,
			'pickupLocation'		=> $pickupLocation, // NB branchcode, not branchnumber
			'queuePosition'			=> $queuePosition,
			'freezeReactivationDate'	=> $freezeReactivationDate,
			'success'			=> $success,
			'message'			=> $message
		);

		return false;
	}

	public function placeHoldViaSIP($patron, $holdId, $pickupBranch = null, $cancelDate = null, $type = null, $queuePosition = null, $freeze = null, $freezeReactivationDate = null){
		global $configArray;
		//Place the hold via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		$success = false;
		$title = '';
		$message = 'Failed to connect to complete requested action.';
		if ($mySip->connect()) {
			//send selfcheck status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])){
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				}else{
					$mySip->AO = 'NASH'; /* set AO to value returned */ // hardcoded for Nashville
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}else{
					$mySip->AN = '';
				}

				$mySip->patron    = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				if (empty($pickupBranch)){
					//Get the code for the location
					$locationLookup = new Location();
					$locationLookup->locationId = $patron->homeLocationId;
					$locationLookup->find(1);
					if ($locationLookup->getNumResults() > 0){
						$pickupBranch = strtoupper($locationLookup->code);
					}
				}else{
					$pickupBranch = strtoupper($pickupBranch);
				}
				$pickupBranchInfo = $this->getBranchInformation(null, $pickupBranch);
				$pickupBranchNumber = $pickupBranchInfo->BranchNumber;

				//place the hold
				$holdType = '2'; // any copy of title
				$itemId = '';
				$recordId = '';
				if (strpos($holdId, 'ITEM ID: ') === 0){
					$holdType = 3; // specific copy
					$itemId = substr($holdId, 9);
				} elseif (strpos($holdId, 'BID: ') === 0){
					$holdType = 2; // any copy of title
					$recordId = substr($holdId, 5);
				} elseif (strpos($holdId, 'CARL') === 0) {
					$holdType = 2; // any copy of title
					$recordId = $this->BIDfromFullCarlID($holdId);
				} else { // assume a short BID
					$holdType = 2; // any copy of title
					$recordId = $holdId;
				}

				if ($type == 'cancel'){
					$mode = '-';
					// Get Title  (Title is not part of the cancel response)
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->fullCarlIDfromBID($recordId));
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
					}
				} elseif ($type == 'recall'){ // NASHVILLE DOES NOT ALLOW RECALL // TO DO: Evaluate what recall code should be
					$mode = '-';
					// Get Title  (Title is not part of the cancel response)
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->fullCarlIDfromBID($recordId));
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
					}

				} elseif ($type == 'update'){
					$mode = '*';
					//$holdId = $recordId;
				} else {
					$mode = '+';
					//$holdId = $this->BIDfromFullCarlID($recordId);
				}

				//TODO: Should change cancellation date when updating pick up locations
				if (!empty($cancelDate)) {
					$dateObject = date_create_from_format('m/d/Y', $cancelDate);
					$expirationTime = $dateObject->getTimestamp();
				} else {
					//expire the hold in 2 years by default
					$expirationTime = time() + 2 * 365 * 24 * 60 * 60;
				}

				$in = $mySip->msgHoldCarlX($mode, $expirationTime, $holdType, $itemId, $recordId, '', $pickupBranchNumber, $queuePosition, $freeze, $freezeReactivationDate);
				$msg_result = $mySip->get_message($in);

				if (preg_match("/^16/", $msg_result)) {
					$result = $mySip->parseHoldResponse($msg_result );
					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];
					if (!empty($result['variable']['AJ'][0])) {
						$title = $result['variable']['AJ'][0];
					}
				}
			}
		}
		return array(
				'title'   => $title,
				'bib'     => $recordId,
				'success' => $success,
				'message' => $message
		);
	}


	public function renewCheckoutViaSIP($patron, $itemId, $useAlternateSIP = false){
		//renew the item via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mysip = new sip2();
		$mysip->hostname = $this->accountProfile->sipHost;
		$mysip->port = $this->accountProfile->sipPort;

		$success = false;
		$message = 'Failed to connect to complete requested action.';
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])){
					$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				}else{
					$mysip->AO = 'NASH'; /* set AO to value returned */
				}
				if (isset($result['variable']['AN'][0])) {
					$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}else{
					$mysip->AN = '';
				}

				$mysip->patron    = $patron->cat_username;
				$mysip->patronpwd = $patron->cat_password;

				$in = $mysip->msgRenew($itemId, '', '', '', 'N', 'N', 'Y');
				//print_r($in . '<br/>');
				$msg_result = $mysip->get_message($in);
				//print_r($msg_result);

				if (preg_match("/^30/", $msg_result)) {
					$result = $mysip->parseRenewResponse($msg_result);

//					$title = $result['variable']['AJ'][0];

					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];

					if (!$success) {
						$title = $result['variable']['AJ'][0];

						$message = empty($title) ? $message : "<p style=\"font-style:italic\">$title</p><p>$message.</p>";
					}


				}
			}
		}else{
			$message = "Could not connect to circulation server, please try again later.";
		}

		return array(
			'itemId'  => $itemId,
			'success' => $success,
			'message' => $message
		);
	}

	/**
	 * @param $BID
	 * @return string CARL ID
	 */
	private function fullCarlIDfromBID($BID)
	{
		return 'CARL' . str_pad($BID, 10, '0', STR_PAD_LEFT);
	}

	private function BIDfromFullCarlID($CarlID) {
		if (strpos($CarlID, ':') > 0){
			list(,$CarlID) = explode(':', $CarlID);
		}
		$temp = str_replace('CARL', '', $CarlID);  // Remove CARL prefix
		$temp = ltrim($temp, '0'); // Remove preceding zeros
		return $temp;
	}


	public function findNewUser($patronBarcode) {
		// Use the validateViaSSO switch to bypass Pin check. If a user is found, patronLogin will return a new User object.
		$newUser = $this->patronLogin($patronBarcode, null, true);
		if (!empty($newUser) && !($newUser instanceof AspenError)) {
			return $newUser;
		}
		return false;
	}

	public function getAccountSummary(User $user)
	{
		$accountSummary = [
			'numCheckedOut' => 0,
			'numOverdue' => 0,
			'numAvailableHolds' => 0,
			'numUnavailableHolds' => 0,
			'totalFines' => 0
		];

		//Load summary information for number of holds, checkouts, etc
		$patronSummaryRequest = new stdClass();
		$patronSummaryRequest->SearchType = 'Patron ID';
		$patronSummaryRequest->SearchID  = $user->cat_username;
		$patronSummaryRequest->Modifiers = '';

		$patronSummaryResponse = $this->doSoapRequest('getPatronTransactions', $patronSummaryRequest, $this->patronWsdl);

		if (!empty($patronSummaryResponse) && is_object($patronSummaryResponse)) {
			$accountSummary['numCheckedOut'] += $patronSummaryResponse->ChargedItemsCount;
			$accountSummary['numCheckedOut'] += $patronSummaryResponse->OverdueItemsCount;
			$accountSummary['numOverdue'] = $patronSummaryResponse->OverdueItemsCount;
			$accountSummary['numAvailableHolds'] = $patronSummaryResponse->HoldItemsCount;
			$accountSummary['numUnavailableHolds'] = $patronSummaryResponse->UnavailableHoldsCount;

			$outstandingFines = $patronSummaryResponse->FineTotal + $patronSummaryResponse->LostItemFeeTotal;
			$accountSummary['totalFines'] = floatval($outstandingFines);
		}

		return $accountSummary;
	}

	public function showMessagingSettings()
	{
		return false;
	}

	public function getHoldsReportData($location) {
		$this->initDatabaseConnection();
		$sql = <<<EOT
			select 
				p.name as PATRON_NAME
				, p.sponsor as HOME_ROOM
				, bb.btyname as GRD_LVL
				, p.patronid as P_BARCODE
				, l.locname as SHELF_LOCATION
				, b.title as TITLE
				, i.cn as CALL_NUMBER
				, i.item as ITEM_ID
			from transbid_v t 
			join patron_v p on t.patronid=p.patronid
			join item_v i on t.bid=i.bid
			join bbibmap_v b on t.bid=b.bid
			join location_v l on i.location=l.locnumber
			join bty_v bb on p.bty=bb.btynumber
			join branch_v ib on i.branch = ib.branchnumber
			join branch_v pb on p.defaultbranch = pb.branchnumber
			where ib.branchcode = '$location'
			and i.status='S'
			and pb.branchcode = '$location'
			and t.transcode='R*'
			order by l.locname, i.cn
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		while (($row = oci_fetch_array ($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		return $data;
//
//		oci_free_statement($stid);
//		oci_close($this->dbConnection);
	}

	public function getStudentReportData($location,$showOverdueOnly,$date) {
			$this->initDatabaseConnection();
			$sql = <<<EOT
				select
				  patronbranch.branchcode AS Home_Lib_Code
				  , patronbranch.branchname AS Home_Lib
				  , bty_v.btynumber AS P_Type
				  , bty_v.btyname AS Grd_Lvl
				  , patron_v.sponsor AS Home_Room
				  , patron_v.name AS Patron_Name
				  , patron_v.patronid AS P_Barcode
				  , itembranch.branchgroup AS SYSTEM
				  , item_v.cn AS Call_Number
				  , bbibmap_v.title AS Title
				  , to_char(jts.todate(transitem_v.dueornotneededafterdate),'MM/DD/YYYY') AS Due_Date
				  , item_v.price AS Owed
				  , to_char(jts.todate(transitem_v.dueornotneededafterdate),'MM/DD/YYYY') AS Due_Date_Dup
				  , item_v.item AS Item
				from 
				  bbibmap_v
				  , branch_v patronbranch
				  , branch_v itembranch
				  , branchgroup_v patronbranchgroup
				  , branchgroup_v itembranchgroup
				  , bty_v
				  , item_v
				  , location_v
				  , patron_v
				  , transitem_v
				where
				  patron_v.patronid = transitem_v.patronid
				  and patron_v.bty = bty_v.btynumber
				  and transitem_v.item = item_v.item
				  and bbibmap_v.bid = item_v.bid
				  and patronbranch.branchnumber = patron_v.defaultbranch
				  and location_v.locnumber = item_v.location
				  and itembranch.branchnumber = transitem_v.holdingbranch
				  and itembranchgroup.branchgroup = itembranch.branchgroup
				  and (TRANSITEM_V.transcode = 'O' or transitem_v.transcode='L' or transitem_v.transcode='C')
				  and patronbranch.branchgroup = '2'
				  and patronbranchgroup.branchgroup = patronbranch.branchgroup
				  and bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42')
				  and patronbranch.branchcode = '$location'
				order by 
				  patronbranch.branchcode
				  , patron_v.bty
				  , patron_v.sponsor
				  , patron_v.name
				  , itembranch.branchgroup
				  , item_v.cn
				  , bbibmap_v.title
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		while (($row = oci_fetch_array ($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		return $data;
//
//		oci_free_statement($stid);
//		oci_close($this->dbConnection);
	}

}
