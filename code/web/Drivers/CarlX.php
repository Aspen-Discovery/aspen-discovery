<?php

require_once ROOT_DIR . '/sys/SIP2.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class CarlX extends AbstractIlsDriver {
	public $patronWsdl;
	public $catalogWsdl;

	private $soapClient;
	protected $dbConnection;

	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
		$this->patronWsdl = $this->accountProfile->patronApiUrl . '/CarlXAPI/PatronAPI.wsdl';
		$this->catalogWsdl = $this->accountProfile->patronApiUrl . '/CarlXAPI/CatalogAPI.wsdl';
	}

	public function __destruct() {
		if (isset($this->dbConnection)) {
			oci_close($this->dbConnection);
			$this->dbConnection = null;
		}
	}

	function initDatabaseConnection() {
		if (!isset($this->dbConnection)) {
			$port = empty($this->accountProfile->databasePort) ? '1521' : $this->accountProfile->databasePort;
			$ociConnection = $this->accountProfile->databaseHost . ':' . $port . '/' . $this->accountProfile->databaseName;
			$this->dbConnection = oci_connect($this->accountProfile->databaseUser, $this->accountProfile->databasePassword, $ociConnection, 'AL32UTF8');
			if (!$this->dbConnection || oci_error($this->dbConnection) != 0) {
				global $logger;
				$logger->log("Error connecting to CARL.X database " . oci_error($this->dbConnection), Logger::LOG_ERROR);
				$this->dbConnection = null;
			}
			global $timer;
			$timer->logTime("Initialized connection to CARL.X database");
		}
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		global $timer;

		//CARL.X supports 3 different login methods:
		//  Patron Barcode & PIN
		//  Last Name & Patron Barcode
		//  EZ Username & EZ Password
		if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
			//Remove any spaces from the barcode
			$username = preg_replace('/[^0-9a-zA-Z]/', '', trim($username));
			$password = trim($password);
		} else {
			//Remove any spaces from the barcode
			$username = trim($username);
			$password = preg_replace('/[^0-9a-zA-Z]/', '', trim($password));
		}
		//Remove any spaces from the barcode
		$username = preg_replace('/[^0-9a-zA-Z]/', '', trim($username));
		$password = trim($password);

		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID = $username;
		$request->Modifiers = '';

		$result = $this->doSoapRequest('getPatronInformation', $request, '', [], ['password' => $password]);

		if ($result) {
			if (isset($result->Patron)) {
				$loginValid = false;
				if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
					//Check to see if the pin matches
					if (($result->Patron->PatronID == $username) && ($result->Patron->PatronPIN == $password || $validatedViaSSO)) {
						$loginValid = true;
					}
				} else {
					if (($result->Patron->PatronID == $username) && ((strcasecmp($result->Patron->LastName, $password) === 0) || $validatedViaSSO)) {
						$loginValid = true;
					}
				}
				if ($loginValid) {
					//$fullName = $result->Patron->FullName;
					$firstName = $result->Patron->FirstName;
					$lastName = $result->Patron->LastName;

					$userExistsInDB = false;
					$user = new User();
					$user->source = $this->accountProfile->name;
					$user->username = $result->Patron->GeneralUserID;
					if ($user->find(true)) {
						$userExistsInDB = true;
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
					if ($forceDisplayNameUpdate) {
						$user->displayName = '';
					}
					$user->cat_username = $username;
					if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
						$user->cat_password = $result->Patron->PatronPIN;
					}else {
						$user->cat_password = $result->Patron->LastName;
					}
					$user->email = $result->Patron->Email;

					if ($userExistsInDB && $user->trackReadingHistory != $result->Patron->LoanHistoryOptInFlag) {
						$user->trackReadingHistory = $result->Patron->LoanHistoryOptInFlag;
					}

					$homeBranchCode = strtolower($result->Patron->DefaultBranch);
					$location = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(1)) {
						unset($location);
						$user->homeLocationId = 0;
						// Logging for Diagnosing PK-1846
						global $logger;
						$logger->log('CarlX Driver: No Location found, user\'s homeLocationId being set to 0. User : ' . $user->id, Logger::LOG_WARNING);
					}

					if ((empty($user->homeLocationId) || $user->homeLocationId == -1) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
						if ((empty($user->homeLocationId) || $user->homeLocationId == -1) && !isset($location)) {
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
							$homeLocationChanged = false;
							if ($user->homeLocationId != $location->locationId) {
								$homeLocationChanged = true;
							}
							$user->homeLocationId = $location->locationId;
							if (empty($user->myLocation1Id)) {
								$user->myLocation1Id = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
							}

							if (empty($user->myLocation2Id)) {
								$user->myLocation2Id = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
							}
							if ($homeLocationChanged) {
								//reset the patrons preferred pickup location to their new home library
								$user->pickupLocationId = $user->homeLocationId;
								$user->rememberHoldPickupLocation = 0;
							}
						}
					}

					$user->patronType = $result->Patron->PatronType; // Example: "ADULT"
					$user->phone = $result->Patron->Phone1;

					$this->loadContactInformationFromSoapResult($user, $result);

					if ($userExistsInDB) {
						$user->update();
					} else {
						$user->created = date('Y-m-d');
						if (!$user->insert()) {
							return null;
						}
					}

					$timer->logTime("patron logged in successfully");
					return $user;
				}
			}
		}

		$timer->logTime("patron login failed");
		return null;
	}

	public function hasNativeReadingHistory(): bool {
		return true;
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll(): bool {
		return true;
	}

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll(User $patron) {
		global $logger;

		//renew the item via SIP 2
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		$renew_result = [
			'success' => false,
			'message' => [],
			'Renewed' => 0,
			'NotRenewed' => $patron->_numCheckedOutIls,
			'Total' => $patron->_numCheckedOutIls,
		];
		if ($mySip->connect()) {
			//send selfcheck status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			ExternalRequestLogEntry::logRequest('carlx.selfCheckStatus', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			// Make sure the response is 98 as expected
			if (str_starts_with($msg_result, "98")) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])) {
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				} else {
					$mySip->AO = 'NASH'; /* set AO to value returned */
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				} else {
					$mySip->AN = '';
				}

				$mySip->patron = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				$in = $mySip->msgRenewAll();
				//print_r($in . '<br/>');
				$msg_result = $mySip->get_message($in);
				ExternalRequestLogEntry::logRequest('carlx.renewAll', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, ['patronPwd' => $patron->cat_password]);
				//print_r($msg_result);

				if (str_starts_with($msg_result, "66")) {
					$result = $mySip->parseRenewAllResponse($msg_result);
					//$logger->log("Renew all response\r\n" . print_r($msg_result, true), Logger::LOG_ERROR);

					$renew_result['success'] = ($result['fixed']['Ok'] == 1);
					$renew_result['Renewed'] = ltrim($result['fixed']['Renewed'], '0');
					if (strlen($renew_result['Renewed']) == 0) {
						$renew_result['Renewed'] = 0;
					}

					$renew_result['NotRenewed'] = ltrim($result['fixed']['NotRenewed'], '0');
					if (strlen($renew_result['NotRenewed']) == 0) {
						$renew_result['NotRenewed'] = 0;
					}
					if (isset($result['variable']['AF'])) {
						$renew_result['message'][] = $result['variable']['AF'][0];
					}

					if ($renew_result['NotRenewed'] > 0) {
						$renew_result['message'] = array_merge($renew_result['message'], $result['variable']['BN']);
					}

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				} else {
					$logger->log("Invalid message returned from SIP server '$msg_result''", Logger::LOG_ERROR);
					$renew_result['message'] = ["Invalid message returned from SIP server"];
				}
			} else {
				$logger->log("Could not authenticate with the SIP server", Logger::LOG_ERROR);
				$renew_result['message'] = ["Could not authenticate with the SIP server"];
			}
		} else {
			$logger->log("Could not connect to the SIP server", Logger::LOG_ERROR);
			$renew_result['message'] = ["Could not connect to circulation server, please try again later."];
		}

		return $renew_result;
	}

	protected $genericResponseSOAPCallOptions = [
		'connection_timeout' => 1,
		'features' => SOAP_SINGLE_ELEMENT_ARRAYS | SOAP_WAIT_ONE_WAY_CALLS,
		'trace' => 1,
	];

	/**
	 * @param $requestName
	 * @param $request
	 * @param string $WSDL
	 * @param array $soapRequestOptions
	 * @return false|stdClass
	 */
	protected function doSoapRequest($requestName, $request, string $WSDL = '', array $soapRequestOptions = [], $dataToSanitize = []) {
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
		$connectionPassed = false;
		$numTries = 0;
		$result = false;
		if (IPAddress::showDebuggingInformation()) {
			$soapRequestOptions['trace'] = true;
		}
		while (!$connectionPassed && $numTries < 2) {
			try {
				$this->soapClient = new SoapClient($WSDL, $soapRequestOptions);
				$result = $this->soapClient->$requestName($request);
				$connectionPassed = true;
				if (IPAddress::showDebuggingInformation()) {
					ExternalRequestLogEntry::logRequest('carlx.' . $requestName, 'GET', $WSDL, $this->soapClient->__getLastRequestHeaders(), $this->soapClient->__getLastRequest(), 0, $this->soapClient->__getLastResponse(), $dataToSanitize);
				}
				if (is_null($result)) {
					$lastResponse = $this->soapClient->__getLastResponse();
					$lastResponse = simplexml_load_string($lastResponse, NULL, NULL, 'http://schemas.xmlsoap.org/soap/envelope/');
					$lastResponse->registerXPathNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
					$lastResponse->registerXPathNamespace('ns3', 'http://tlcdelivers.com/cx/schemas/patronAPI');
					$lastResponse->registerXPathNamespace('ns2', 'http://tlcdelivers.com/cx/schemas/response');
					$result = new stdClass();
					$result->ResponseStatuses = new stdClass();
					$result->ResponseStatuses->ResponseStatus = new stdClass();
					$shortMessages = $lastResponse->xpath('//ns2:ShortMessage');
					$result->ResponseStatuses->ResponseStatus->ShortMessage = implode('; ', $shortMessages);
					$longMessages = $lastResponse->xpath('//ns2:LongMessage');
					// TODO make empty
					$result->ResponseStatuses->ResponseStatus->LongMessage = implode('; ', array_filter($longMessages));
				}
			} catch (SoapFault $e) {
				if ($numTries == 2) {
					global $logger;
					$logger->log("Error connecting to SOAP " . $e, Logger::LOG_WARNING);
					$result->error = "EXCEPTION: " . $e->getMessage();
				}
			}
			$numTries++;
		}
		if (!$connectionPassed) {
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
	public function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null) {
		// Renew Via SIP
		return $result = $this->renewCheckoutViaSIP($patron, $itemId);
	}

	private $holdStatusCodes = [
		'H' => 'Hold Shelf',
		'' => 'In Queue',
		'IH' => 'In Transit',
		// '?' => 'Suspended',
		// '?' => 'filled',
	];

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$holds = [
			'available' => [],
			'unavailable' => [],
		];

		//Search for the patron in the database
		$result = $this->getPatronTransactions($patron);

		if ($result && ($result->HoldItemsCount > 0 || $result->UnavailableHoldsCount > 0)) {

			// Available Holds
			if ($result->HoldItemsCount > 0) {
				foreach ($result->HoldItems->HoldItem as $hold) {
					$curHold = new Hold();
					$curHold->userId = $patron->id;
					$curHold->type = 'ils';
					$curHold->source = $this->getIndexingProfile()->name;
					$curHold->available = true;
					$bibId = trim($hold->BID);
					$carlID = $this->fullCarlIDfromBID($bibId);
					$expireDate = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch = $this->getBranchInformation($hold->PickUpBranch);

					$curHold->sourceId = $carlID;
					$curHold->itemId = $hold->ItemNumber;
					$curHold->cancelId = trim($hold->Identifier);
					$curHold->position = $hold->QueuePosition;
					$curHold->recordId = $carlID;
					$curHold->shortId = $bibId;
					$curHold->title = $hold->Title;
					$curHold->author = $hold->Author;
					$curHold->pickupLocationId = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold->locationUpdateable = false;
					$curHold->pickupLocationName = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold->status = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold->expirationDate = strtotime($expireDate); // give a time stamp  // use this for available holds
					$curHold->reactivateDate = null;
					$curHold->frozen = isset($hold->Suspended) && ($hold->Suspended == true);
					$curHold->cancelable = false;
					$curHold->canFreeze = false;
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID); // This needs the $carlID
					if ($recordDriver->isValid()) {
						$curHold->updateFromRecordDriver($recordDriver);
					}
					$holds['available'][] = $curHold;

				}
			}

			// Unavailable Holds
			if ($result->UnavailableHoldsCount > 0) {
				foreach ($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
					$curHold = new Hold();
					$curHold->userId = $patron->id;
					$curHold->type = 'ils';
					$curHold->source = $this->getIndexingProfile()->name;
					$curHold->available = false;
					$bibId = $hold->BID;
					$carlID = $this->fullCarlIDfromBID($bibId);
					$expireDate = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch = $this->getBranchInformation($hold->PickUpBranch);

					$curHold->sourceId = $carlID;
					$curHold->itemId = $hold->ItemNumber;
					$curHold->cancelId = $hold->Identifier; // James Staub declares cancelId is synonymous with holdId 20200613
					// CarlX API v1.9.6.3 does not accurately calculate hold queue position. See https://ww2.tlcdelivers.com/helpdesk/Default.asp?TicketID=500458
					$curHold->position = $hold->QueuePosition;
					//$unavailableHoldViaSIP		= $this->getUnavailableHoldViaSIP($user, $hold->Identifier); // TO DO: should absolutely be refactored to merge API and SIP2 unavailable holds arrays outside of the API UnavailableHoldItem foreach loop - adds ~ 20 seconds to load James Staub's holds (30 items across 5 linked accounts)
					//$curHold['position']		= $unavailableHoldViaSIP['queuePosition'];
					$curHold->recordId = $carlID;
					$curHold->shortId = $bibId;
					$curHold->title = $hold->Title;
					$curHold->author = $hold->Author;
					$curHold->pickupLocationId = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold->pickupLocationName = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold->frozen = $hold->Suspended;
					$curHold->status = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold->automaticCancellationDate = strtotime($expireDate); // use this for unavailable holds
					$curHold->cancelable = true;

					if ($curHold->frozen) {
						$curHold->reactivateDate = strtotime($hold->SuspendedUntilDate);
						$curHold->status = 'Frozen';
					}
					// CarlX [9.6.4.3] will not allow update hold (suspend hold, change pickup location) on item level hold. UnavailableHoldItem ~ /^ITEM ID: / if the hold is an item level hold.
					if (str_starts_with($curHold->cancelId, 'ITEM ID: ')) {
						$curHold->canFreeze = false;
						$curHold->locationUpdateable = false;
					} elseif (str_starts_with($curHold->cancelId, 'BID: ')) {
						$curHold->canFreeze = true;
						$curHold->locationUpdateable = true;
					} else { // TO DO: Evaluate whether issue level holds are suspendable
						$curHold->canFreeze = false;
						$curHold->locationUpdateable = false;
					}

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID); // This needs the $carlID
					if ($recordDriver->isValid()) {
						$curHold->updateFromRecordDriver($recordDriver);
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
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date the hold should be automatically cancelled
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	public function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		return $this->placeHoldViaSIP($patron, $recordId, $pickupBranch, $cancelDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null) {
		// TODO: Implement placeItemHold() method. // CarlX [9.6.4.3] does not allow item level holds via SIP2
		return [
			'success' => false,
			'message' => 'Unable to place item holds for CARL.X',
		];
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @param string $cancelId Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false): array {
		return $this->placeHoldViaSIP($patron, $cancelId, null, null, 'cancel');

	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $this->BIDfromFullCarlID($recordId)); // "unavailable hold" is CarlX-speak for holds not yet on the hold shelf, i.e., "holds not yet ready for pickup"
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$pickupLocation = $unavailableHoldViaSIP['pickupLocation']; // NB branchcode not branchnumber
		if (preg_match('/\d{4}-\d{2}-\d{2}/', $dateToReactivate)) { // Aspen 21.10 set YYYY-MM-DD as reactivationDate format; CarlX 9.6.8 expects MM/DD/YYYY
			$dateToReactivate = preg_replace('/(\d{4})-(\d{2})-(\d{2})/', '$2/$3/$1', $dateToReactivate);
		}
		$freezeReactivationDate = $dateToReactivate . 'B';
		$result = $this->placeHoldViaSIP($patron, $recordId, $pickupLocation, null, 'update', $queuePosition, 'freeze', $freezeReactivationDate);
		return $result;
	}

	function thawHold(User $patron, $recordId, $itemToThawId): array {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $this->BIDfromFullCarlID($recordId));
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$pickupLocation = $unavailableHoldViaSIP['pickupLocation']; // NB branchcode not branchnumber
		$timeStamp = strtotime('+2 years'); // TO DO: read hold NNA or sync with default NNA (2 years?)
		$cancelDate = date('m/d/Y', $timeStamp);
		$result = $this->placeHoldViaSIP($patron, $recordId, $pickupLocation, $cancelDate, 'update', $queuePosition, 'thaw');
		return $result;
	}

	function changeHoldPickupLocation(User $patron, $recordId, $holdId, $newPickupLocation): array {
		$unavailableHoldViaSIP = $this->getUnavailableHoldViaSIP($patron, $holdId);
		$queuePosition = $unavailableHoldViaSIP['queuePosition'];
		$freeze = null;
		$freezeReactivationDate = null;
		if (!empty($unavailableHoldViaSIP['freezeReactivationDate']) && str_ends_with($unavailableHoldViaSIP['freezeReactivationDate'], 'B')) {
			$freeze = true;
			$freezeReactivationDate = $unavailableHoldViaSIP['freezeReactivationDate'];
		}
        return $this->placeHoldViaSIP($patron, $holdId, $newPickupLocation, null, 'update', $queuePosition, $freeze, $freezeReactivationDate);
	}

	public function getCheckouts(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];

		//Search for the patron in the database
		$result = $this->getPatronTransactions($patron);

		$itemsToLoad = [];
		if (!$result) {
			global $logger;
			$logger->log('Failed to retrieve user Check outs from CarlX API call.', Logger::LOG_WARNING);
		} else {
			//TLC provides both ChargeItems and OverdueItems as separate elements, we can combine for loading
			if (!empty($result->ChargeItems->ChargeItem)) {
				$itemsToLoad = $result->ChargeItems->ChargeItem;
			}
			if (!empty($result->OverdueItems->OverdueItem)) {
				$itemsToLoad = array_merge($itemsToLoad, $result->OverdueItems->OverdueItem);
			}

			foreach ($itemsToLoad as $chargeItem) {
				$curTitle = new Checkout();
				$curTitle->type = 'ils';
				$curTitle->source = $this->getIndexingProfile()->name;
				$curTitle->userId = $patron->id;
				$carlID = $this->fullCarlIDfromBID($chargeItem->BID);
				$curTitle->sourceId = $carlID;
				$dueDate = strstr($chargeItem->DueDate, 'T', true);
				$curTitle->recordId = $carlID;
				$curTitle->barcode = $chargeItem->ItemNumber;   // Barcode & ItemNumber are the same for CarlX
				$curTitle->itemId = $chargeItem->ItemNumber;
				$curTitle->title = $chargeItem->Title;
				$curTitle->author = $chargeItem->Author;
				$curTitle->dueDate = strtotime($dueDate);
				$curTitle->checkoutDate = strstr($chargeItem->TransactionDate, 'T', true);
				$curTitle->renewCount = isset($chargeItem->RenewalCount) ? $chargeItem->RenewalCount : 0;
				$curTitle->canRenew = true;
				$curTitle->renewIndicator = $chargeItem->ItemNumber;

				$curTitle->format = 'Unknown';
				if (!empty($carlID)) {
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($carlID); // This needs the $carlID
					if ($recordDriver->isValid()) {
						$curTitle->updateFromRecordDriver($recordDriver);
					}
				}
				$checkedOutTitles[] = $curTitle;
			}
		}

		return $checkedOutTitles;
	}

	function updatePin(User $patron, string $oldPin, string $newPin) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->Patron = new stdClass();
		$request->Patron->PatronPIN = $newPin;
		$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions, ['pin' => $newPin]);
		if ($result) {
			$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
			if (!$success) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Failed to update your PIN.',
						'isPublicFacing' => true,
					]),
				];
			} else {
				$patron->cat_password = $newPin;
				$patron->update();
				return [
					'success' => true,
					'message' => translate([
						'text' => 'Your PIN was updated successfully.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			global $logger;
			$logger->log('CarlX ILS gave no response when attempting to update Patron PIN.', Logger::LOG_ERROR);
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Failed to update your PIN.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	public function updateHomeLibrary(User $patron, string $homeLibraryCode) {
		$result = [
			'success' => false,
			'messages' => [],
		];

		$request = $this->getSearchbyPatronIdRequest($patron);
		if (!isset($request->Patron)) {
			$request->Patron = new stdClass();
		}

		$request->Patron->DefaultBranch = strtoupper($homeLibraryCode);

		$soapResult = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

		if ($soapResult) {
			$success = stripos($soapResult->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
			if (!$success) {
				$errorMessage = $soapResult->ResponseStatuses->ResponseStatus->LongMessage;
				$result['messages'][] = 'Failed to update your pickup location ' . ($errorMessage ? ' : ' . $errorMessage : '');
			} else {
				$result['success'] = true;
				$result['messages'][] = 'Your pickup location was updated successfully.';
			}

		} else {
			$result['messages'][] = 'Unable to update your pickup location.';
			global $logger;
			$logger->log('Unable to read XML from CarlX response when attempting to update pickup location.', Logger::LOG_ERROR);
		}
		if ($result['success'] == false && empty($result['messages'])) {
			$result['messages'][] = 'Unknown error updating your pickup location';
		}
		return $result;
	}

	/**
	 * @param User $patron
	 * @param boolean $canUpdateContactInfo
	 * @param boolean $fromMasquerade
	 * @return array
	 */
	public function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade): array {
		$result = [
			'success' => false,
			'messages' => [],
		];
		if ($canUpdateContactInfo) {
			$request = $this->getSearchbyPatronIdRequest($patron);
			if (!isset($request->Patron)) {
				$request->Patron = new stdClass();
			}
			// Patron Info to update.
			if (isset($_REQUEST['email'])) {
				$request->Patron->Email = $_REQUEST['email'];
				$patron->email = $_REQUEST['email'];
			}
			if (isset($_REQUEST['phone'])) {
				$request->Patron->Phone1 = $_REQUEST['phone'];
				$patron->phone = $_REQUEST['phone'];
			}
			if (isset($_REQUEST['workPhone'])) {
				$request->Patron->Phone2 = $_REQUEST['workPhone'];
				$patron->_workPhone = $_REQUEST['workPhone'];
			}
			if (!isset($request->Addresses)) {
				$request->Patron->Addresses = new stdClass();
			}
			if (!isset($request->Addresses->Address)) {
				$request->Patron->Addresses->Address = new stdClass();
			}
			$request->Patron->Addresses->Address->Type = 'Primary';
			if (isset($_REQUEST['address1'])) {
				$request->Patron->Addresses->Address->Street = $_REQUEST['address1'];
				$patron->_address1 = $_REQUEST['address1'];
			}
			if (isset($_REQUEST['city'])) {
				$request->Patron->Addresses->Address->City = $_REQUEST['city'];
				$patron->_city = $_REQUEST['city'];
			}
			if (isset($_REQUEST['state'])) {
				$request->Patron->Addresses->Address->State = $_REQUEST['state'];
				$patron->_state = $_REQUEST['state'];
			}
			if (isset($_REQUEST['zip'])) {
				$request->Patron->Addresses->Address->PostalCode = $_REQUEST['zip'];
				$patron->_zip = $_REQUEST['zip'];
            }
			if (isset($_REQUEST['emailReceiptFlag']) && ($_REQUEST['emailReceiptFlag'] == 'yes' || $_REQUEST['emailReceiptFlag'] == 'on')) {
				// if set check & on check must be combined because checkboxes/radios don't report 'offs'
				$request->Patron->EmailReceiptFlag = 1;
			} else {
				$request->Patron->EmailReceiptFlag = 0;
			}
			if (isset($_REQUEST['availableHoldNotice']) && ($_REQUEST['availableHoldNotice'] == 'yes' || $_REQUEST['availableHoldNotice'] == 'on')) {
				// if set check & on check must be combined because checkboxes/radios don't report 'offs'
				$request->Patron->SendHoldAvailableFlag = 1;
			} else {
				$request->Patron->SendHoldAvailableFlag = 0;
			}
			if (isset($_REQUEST['comingDueNotice']) && ($_REQUEST['comingDueNotice'] == 'yes' || $_REQUEST['comingDueNotice'] == 'on')) {
				// if set check & on check must be combined because checkboxes/radios don't report 'offs'
				$request->Patron->SendComingDueFlag = 1;
			} else {
				$request->Patron->SendComingDueFlag = 0;
			}
			if (isset($_REQUEST['phoneType'])) {
				$request->Patron->PhoneType = $_REQUEST['phoneType'];
				$patron->_phoneType = $_REQUEST['phoneType'];
			}

			if (isset($_REQUEST['notices'])) {
				$request->Patron->EmailNotices = $_REQUEST['notices'];
				$patron->_notices = $_REQUEST['notices'];
			}

			if (!empty($_REQUEST['pickupLocation'])) {
				$homeLocation = new Location();
				if ($homeLocation->get('code', $_REQUEST['pickupLocation'])) {
					$homeBranchCode = strtoupper($_REQUEST['pickupLocation']);
					$request->Patron->DefaultBranch = $homeBranchCode;
					$patron->homeLocationId = $homeLocation->locationId;
					$patron->_homeLocationCode = $homeLocation->code;
					$patron->_homeLocation = $homeLocation;
				}
			}

			$soapResult = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

			if ($soapResult) {
				$success = stripos($soapResult->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
				if (!$success) {
					$errorMessage = $soapResult->ResponseStatuses->ResponseStatus->LongMessage;
					$result['messages'][] = 'Failed to update your information' . ($errorMessage ? ' : ' . $errorMessage : '');
				} else {
					$result['success'] = true;
					$result['messages'][] = 'Your account was updated successfully.';
					$patron->update();
				}

			} else {
				$result['messages'][] = 'Unable to update your information.';
				global $logger;
				$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', Logger::LOG_ERROR);
			}

		} else {
			$result['messages'][] = 'You can not update your information.';
		}

		if ($result['success'] == false && empty($result['messages'])) {
			$result['messages'][] = 'Unknown error updating your account';
		}
		return $result;
	}

	public function getSelfRegistrationFields() {
		global $library;
		$fields = [];
		$fields[] = [
			'property' => 'firstName',
			'type' => 'text',
			'label' => 'First Name',
			'maxLength' => 40,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'middleName',
			'type' => 'text',
			'label' => 'Middle Name',
			'maxLength' => 40,
			'required' => false,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'lastName',
			'type' => 'text',
			'label' => 'Last Name',
			'maxLength' => 40,
			'required' => true,
			'autocomplete' => false,
		];
		if ($library && $library->promptForBirthDateInSelfReg) {
			$birthDateMin = date('Y-m-d', strtotime('-113 years'));
			$birthDateMax = date('Y-m-d', strtotime('-13 years'));
			$fields[] = [
				'property' => 'birthDate',
				'type' => 'date',
				'label' => 'Date of Birth (MM-DD-YYYY)',
				'min' => $birthDateMin,
				'max' => $birthDateMax,
				'maxLength' => 10,
				'required' => true,
				'autocomplete' => false,
			];
		}
		$fields[] = [
			'property' => 'address',
			'type' => 'text',
			'label' => 'Mailing Address',
			'maxLength' => 128,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'city',
			'type' => 'text',
			'label' => 'City',
			'maxLength' => 48,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'default' => 'TN',
			'property' => 'state',
			'type' => 'text',
			'label' => 'State',
			'maxLength' => 2,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'zip',
			'type' => 'text',
			'label' => 'Zip Code',
			'maxLength' => 32,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'phone',
			'type' => 'text',
			'label' => 'Primary Phone',
			'maxLength' => 15,
			'required' => true,
			'autocomplete' => false,
		];
		$fields[] = [
			'property' => 'email',
			'type' => 'email',
			'label' => 'Email',
			'maxLength' => 128,
			'required' => true,
			'autocomplete' => false,
		];
		return $fields;
	}

	function selfRegister(): array {
		global $library, $configArray, $active_ip, $interface;
		$success = false;

// TODO: move last_selfreg_patron_id out of table variables. 20201108
		$lastPatronID = new Variable();
		$lastPatronID->get('name', 'last_selfreg_patron_id');
		if (!empty($lastPatronID->value)) {
			$currentPatronIDNumber = rand(1, 13) + $lastPatronID->value;
// TODO: move selfRegIDPrefix to database. 20201108
			$tempPatronID = $configArray['Catalog']['selfRegIDPrefix'] . str_pad($currentPatronIDNumber, $configArray['Catalog']['selfRegIDNumberLength'], '0', STR_PAD_LEFT);

			$firstName = trim(strtoupper($_REQUEST['firstName']));
			$middleName = trim(strtoupper($_REQUEST['middleName']));
			$lastName = trim(strtoupper($_REQUEST['lastName']));
			if ($library && $library->promptForBirthDateInSelfReg) {
				$birthDate = trim($_REQUEST['birthDate']);
				$date = strtotime(str_replace('-', '/', $birthDate));
			}
            $address = trim(strtoupper($_REQUEST['address']));
			$city = trim(strtoupper($_REQUEST['city']));
			$state = trim(strtoupper($_REQUEST['state']));
			$zip = trim($_REQUEST['zip']);
			$email = trim(strtoupper($_REQUEST['email']));
			$phone = preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1-$2-$3', preg_replace('/\D/', '', trim($_REQUEST['phone'])));

			// DENY REGISTRATION IF DUPLICATE EMAIL IS FOUND IN CARL.X
			// searchPatron on Email appears to be case-insensitive and
			// appears to eliminate spurious whitespace
			$request = new stdClass();
			$request->AllSearchTermMatch = 'true';
			$request->SearchTerms = new stdClass();
			$request->SearchTerms->ApplicationType = 'exact match';
			$request->SearchTerms->Attribute = 'Email';
			$request->SearchTerms->Value = $email;
			$request->PagingParameters = new stdClass();
			$request->PagingParameters->StartPos = 0;
			$request->PagingParameters->NoOfRecords = 20;
			$request->Modifiers = new stdClass();
			$request->Modifiers->InstitutionCode = 'NASH';
			$result = $this->doSoapRequest('searchPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
			if ($result) {
				$noEmailMatch = stripos($result->ResponseStatuses->ResponseStatus[0]->ShortMessage, 'No matching records found');
				if ($noEmailMatch === false) {
					if ($result->PagingResult->NoOfRecords > 0) {
						$patronIdsMatching = array_column($result->Patrons, 'PatronID');
						$patronIdsMatching = implode(", ", $patronIdsMatching);
					}
					global $logger;
					$logger->log('Online Registration Email already exists in Carl. Email: ' . $email . ' IP: ' . $active_ip . ' PatronIDs: ' . $patronIdsMatching, Logger::LOG_NOTICE);

					// SEND EMAIL TO DUPLICATE EMAIL ADDRESS
					try {
						$body = $interface->fetch($this->getSelfRegTemplate('duplicate_email'));
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = 'Nashville Public Library: you have an account!';
						$mail->send($email, $subject, $body, 'no-reply@nashville.gov');
					} catch (Exception $e) {
						// SendGrid Failed
					}
					return [
						'success' => false,
						'message' => 'You tried to register for a Digital Access Card, but you might already have a card with Nashville Public Library. Please check your email for further instructions.',
						'barcode' => $tempPatronID,
					];
				}
			}

			// DENY REGISTRATION IF DUPLICATE EXACT FIRST NAME + LAST NAME + BIRTHDATE
			$request = new stdClass();
			$request->Modifiers = '';
			$request->AllSearchTermMatch = 'true';
			$request->SearchTerms = [];
			$request->SearchTerms[0]['ApplicationType'] = 'exact match';
			$request->SearchTerms[0]['Attribute'] = 'First Name';
			$request->SearchTerms[0]['Value'] = $firstName;
			$request->SearchTerms[1]['ApplicationType'] = 'exact match';
			$request->SearchTerms[1]['Attribute'] = 'Last Name';
			$request->SearchTerms[1]['Value'] = $lastName;
			$request->SearchTerms[2]['ApplicationType'] = 'exact match';
			$request->SearchTerms[2]['Attribute'] = 'Birthdate';
			$request->SearchTerms[2]['Value'] = date('Ymd', $date);
			$request->PagingParameters = new stdClass();
			$request->PagingParameters->StartPos = 0;
			$request->PagingParameters->NoOfRecords = 20;
			$request->Modifiers = new stdClass();
			$request->Modifiers->InstitutionCode = 'NASH';
			$result = $this->doSoapRequest('searchPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
			if ($result) {
				$noNameBirthdateMatch = stripos($result->ResponseStatuses->ResponseStatus[0]->ShortMessage, 'No matching records found');
				if ($noNameBirthdateMatch === false) {
					if ($result->PagingResult->NoOfRecords > 0) {
						$patronIdsMatching = array_column($result->Patrons, 'PatronID');
						$patronIdsMatching = implode(", ", $patronIdsMatching);
					}
					global $logger;
					$logger->log('Online Registration Name+Birthdate already exists in Carl. Name: ' . $firstName . ' ' . $lastName . ' IP: ' . $active_ip . ' PatronIDs: ' . $patronIdsMatching, Logger::LOG_NOTICE);

					// SEND EMAIL TO DUPLICATE NAME+BIRTHDATE REGISTRANT EMAIL ADDRESS
					try {
						$body = $interface->fetch($this->getSelfRegTemplate('duplicate_name+birthdate'));
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = 'Nashville Public Library: you might already have an account!';
						$mail->send($email, $subject, $body, 'no-reply@nashville.gov');
					} catch (Exception $e) {
						//SendGrid failed
					}
					return [
						'success' => false,
						'message' => 'You tried to register for a Digital Access Card, but you might already have a card with Nashville Public Library. Please check your email for further instructions.',
						'barcode' => $tempPatronID,
					];
				}
			}

			// CREATE PATRON REQUEST
			$request = new stdClass();
			$request->Modifiers = new stdClass();
			$request->Modifiers->ReportMode = false;
			$request->PatronFlags = new stdClass();
			//$request->PatronFlags->PatronFlag					= 'DUPCHECK_ALTID'; // Duplicate check for alt id
			$request->PatronFlags->PatronFlag[0] = 'DUPCHECK_NAME_DOB'; // Duplicate check for name/date of birth
			$request->PatronFlags->PatronFlag[1] = 'VALIDATE_ZIPCODE'; // Validate ZIP against Carl.X Admin legal ZIPs
			$request->Patron = new stdClass();
			$request->Patron->PatronID = $tempPatronID;
			$request->Patron->Email = $email;
			$request->Patron->FirstName = $firstName;
			$request->Patron->MiddleName = $middleName;
			$request->Patron->LastName = $lastName;
			$request->Patron->Addresses = new stdClass();
			$request->Patron->Addresses->Address = new stdClass();
			$request->Patron->Addresses->Address->Type = 'Primary';
			$request->Patron->Addresses->Address->Street = $address;
			$request->Patron->Addresses->Address->City = $city;
			$request->Patron->Addresses->Address->State = $state;
			$request->Patron->Addresses->Address->PostalCode = $zip;
			$request->Patron->PreferredAddress = 'Primary';
			//$request->Patron->PatronPIN						= $pin;
			$request->Patron->Phone1 = $phone;
			$request->Patron->RegistrationDate = date('c'); // Registration Date, format ISO 8601
			$request->Patron->LastActionDate = date('c'); // Registration Date, format ISO 8601
			$request->Patron->LastEditDate = date('c'); // Registration Date, format ISO 8601

			$request->Patron->EmailNotices = $configArray['Catalog']['selfRegEmailNotices'];
			$request->Patron->DefaultBranch = $configArray['Catalog']['selfRegDefaultBranch'];
			$request->Patron->PatronExpirationDate = $configArray['Catalog']['selfRegPatronExpirationDate'];
			$request->Patron->PatronStatusCode = $configArray['Catalog']['selfRegPatronStatusCode'];
			$request->Patron->PatronType = $configArray['Catalog']['selfRegPatronType'];
			$request->Patron->RegBranch = $configArray['Catalog']['selfRegRegBranch'];
			$request->Patron->RegisteredBy = $configArray['Catalog']['selfRegRegisteredBy'];

			// VALIDATE BIRTH DATE.
			// DENY REGISTRATION IF REGISTRANT IS NOT 13 - 113 YEARS OLD
			if ($library && $library->promptForBirthDateInSelfReg) {
				$birthDate = trim($_REQUEST['birthDate']);
				$date = strtotime(str_replace('-', '/', $birthDate));
				$birthDateMin = strtotime('-113 years');
				$birthDateMax = strtotime('-13 years');
				if ($date >= $birthDateMin && $date <= $birthDateMax) {
					$request->Patron->BirthDate = date('Y-m-d', $date);
				} else {
					global $logger;
					$logger->log('Online Registrant is too young : birth date : ' . date('Y-m-d', $date), Logger::LOG_WARNING);
					return [
						'success' => false,
						'message' => 'You must be 13 years old to register.',
					];
				}
			}
			$result = $this->doSoapRequest('createPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
			if ($result) {
				$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
				if (!$success) {
					$errorMessage = $result->ResponseStatuses->ResponseStatus->ShortMessage;
					if (!empty($result->ResponseStatuses->ResponseStatus->LongMessage)) {
						$errorMessage .= "... " . $result->ResponseStatuses->ResponseStatus->LongMessage;
					}
					if (str_contains($errorMessage, 'A patron with that id already exists')) {
						global $logger;
						$logger->log('While self-registering user for CarlX, temp id number was reported in use. Increasing internal counter', Logger::LOG_ERROR);
						// Increment the temp patron id number.
						$lastPatronID->value = $currentPatronIDNumber;
						if (!$lastPatronID->update()) {
							$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', Logger::LOG_ERROR);
						}
					}
					return [
						'success' => false,
						'message' => $errorMessage,
					];
				} else {
					$lastPatronID->value = $currentPatronIDNumber;
					if (!$lastPatronID->update()) {
						global $logger;
						$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', Logger::LOG_ERROR);
					}
					// Get Patron
					$request = new stdClass();
					$request->SearchType = 'Patron ID';
					$request->SearchID = $tempPatronID;
					$request->Modifiers = '';

					/** @noinspection PhpUnusedLocalVariableInspection */
					$result = $this->doSoapRequest('getPatronInformation', $request);

					// FOLLOWING SUCCESSFUL SELF REGISTRATION, INPUT PATRON IP ADDRESS INTO PATRON RECORD NOTE
					$request = new stdClass();
					$request->Modifiers = '';
					$request->Note = new stdClass();
					$request->Note->PatronID = $tempPatronID;
					$request->Note->NoteType = 2;
					$request->Note->NoteText = "Online registration from IP " . $active_ip;
					$result = $this->doSoapRequest('addPatronNote', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

					if ($result) {
						$success = stripos($result->ResponseStatuses->ResponseStatus[0]->ShortMessage, 'Success') !== false;
						if (!$success) {
							global $logger;
							$logger->log('Unable to write IP address in Patron Note.', Logger::LOG_ERROR);
							// Return Success Any way, because the account was created.
							return [
								'success' => true,
								'barcode' => $tempPatronID,
							];
						}
					}

					// FOLLOWING SUCCESSFUL SELF REGISTRATION, EMAIL PATRON THE LIBRARY CARD NUMBER
					try {
						$body = $firstName . " " . $lastName . "\n\n";
						$body .= translate([
							'text' => 'Thank you for registering for a Digital Access Card at the Nashville Public Library. Your library card number is:',
							'isPublicFacing' => true,
							'isAdminEnteredData' => true,
						]);
						$body .= "\n\n" . $tempPatronID . "\n\n";
						$body_template = $interface->fetch($this->getSelfRegTemplate('success'));
						$body .= $body_template;
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = 'Welcome to the Nashville Public Library';
						$mail->send($email, $subject, $body, 'no-reply@nashville.gov');
					} catch (Exception $e) {
						// SendGrid Failed
					}
					return [
						'success' => $success,
						'barcode' => $tempPatronID,
						'patronName' => $firstName . ' ' . $lastName,
					];
				}
			} else {
				global $logger;
				$logger->log('Unable to read XML from CarlX response when attempting to create Patron.', Logger::LOG_ERROR);
			}
		} else {
			global $logger;
			$logger->log('No value for "last_selfreg_patron_id" set in Variables table. Can not self-register patron in CarlX Driver.', Logger::LOG_ERROR);
		}
		return [
			'success' => $success,
		];

	}

	function getSelfRegTemplate($reason) {
		if ($reason == 'duplicate_email') {
			return 'Emails/self-registration-denied-duplicate_email.tpl';
		} elseif ($reason == 'duplicate_name+birthdate') {
			return 'Emails/self-registration-denied-duplicate_name+birthdate.tpl';
		} elseif ($reason == 'success') {
			return 'Emails/self-registration.tpl';
		} else {
			return null;
		}
	}

	public function getReadingHistory(User $patron, $page = 1, $recordsPerPage = -1, $sortOption = 'checkedOut') {
		$readHistoryEnabled = false;
		$request = $this->getSearchbyPatronIdRequest($patron);
		$result = $this->doSoapRequest('getPatronInformation', $request, $this->patronWsdl);
		if ($result && $result->Patron) {
			$readHistoryEnabled = $result->Patron->LoanHistoryOptInFlag;
		}

		if ($readHistoryEnabled) { // Create Reading History Request
			$historyActive = true;
			$readingHistoryTitles = [];
			$numTitles = 0;

			$request->HistoryType = 'L'; //  From Documentation: The type of charge history to return, (O)utreach or (L)oan History opt-in
			$result = $this->doSoapRequest('getPatronChargeHistory', $request);

			if ($result) {
				// Process Reading History Response
				if (!empty($result->ChargeHistoryItems->ChargeItem)) {
					foreach ($result->ChargeHistoryItems->ChargeItem as $readingHistoryEntry) {
						// Process Reading History Entries
						$checkOutDate = new DateTime($readingHistoryEntry->ChargeDateTime);
						$curTitle = [];
						$curTitle['itemId'] = $readingHistoryEntry->ItemNumber;
						$curTitle['id'] = $readingHistoryEntry->BID;
						$curTitle['shortId'] = $readingHistoryEntry->BID;
						$curTitle['recordId'] = $this->fullCarlIDfromBID($readingHistoryEntry->BID);
						$curTitle['title'] = rtrim($readingHistoryEntry->Title, ' /');
						$curTitle['checkout'] = $checkOutDate->format('m-d-Y'); // this format is expected by Aspen Discovery's java cron program.
						$curTitle['borrower_num'] = $patron->id;
						$curTitle['dueDate'] = null; // Not available in ChargeHistoryItems
						$curTitle['author'] = null; // Not available in ChargeHistoryItems

						$readingHistoryTitles[] = $curTitle;
					}

					$numTitles = count($readingHistoryTitles);

					//process pagination
					if ($recordsPerPage != -1) {
						$startRecord = ($page - 1) * $recordsPerPage;
						$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
					}

					set_time_limit(20 * count($readingHistoryTitles)); // Taken from Aspencat Driver

					// Fetch Additional Information for each Item
					foreach ($readingHistoryTitles as $key => $historyEntry) {
						//Get additional information from resources table
						$historyEntry['ratingData'] = null;
						$historyEntry['permanentId'] = null;
						$historyEntry['linkUrl'] = null;
						$historyEntry['coverUrl'] = null;
						$historyEntry['format'] = 'Unknown';
						if (!empty($historyEntry['recordId'])) {
							require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
							$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $historyEntry['recordId']);
							if ($recordDriver->isValid()) {
								$historyEntry['ratingData'] = $recordDriver->getRatingData();
								$historyEntry['permanentId'] = $recordDriver->getPermanentId();
								$historyEntry['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
								$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
								$historyEntry['format'] = $recordDriver->getFormats();
								$historyEntry['author'] = $recordDriver->getPrimaryAuthor();
								if (empty($curTitle['title'])) {
									$curTitle['title'] = $recordDriver->getTitle();
								}
							}

							$recordDriver = null;
						}
						$historyEntry['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($historyEntry['title']));

						$readingHistoryTitles[$key] = $historyEntry;
					}


				}

				// Return Reading History
				return [
					'historyActive' => $historyActive,
					'titles' => $readingHistoryTitles,
					'numTitles' => $numTitles,
				];

			} else {
				global $logger;
				$logger->log('CarlX ILS gave no response when attempting to get Reading History.', Logger::LOG_ERROR);
			}
		}
		return [
			'historyActive' => false,
			'titles' => [],
			'numTitles' => 0,
		];
	}

	public function performsReadingHistoryUpdatesOfILS() : bool {
		return true;
	}

	public function doReadingHistoryAction(User $patron, string $action, array $selectedTitles) : void {
		if ($action == 'optIn' || $action == 'optOut') {
			$request = $this->getSearchbyPatronIdRequest($patron);
			if (!isset ($request->Patron)) {
				$request->Patron = new stdClass();
			}
			$request->Patron->LoanHistoryOptInFlag = ($action == 'optIn');
			$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
			if ($result) {
				$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
				if (!$success) {
					$errorMessage = $result->ResponseStatuses->ResponseStatus->LongMessage;
					global $logger;
					$logger->log("Unable to modify reading history status $errorMessage", Logger::LOG_ERROR);
				}
			} else {
				global $logger;
				$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', Logger::LOG_ERROR);
			}
		}
	}

	public function getFines(User $patron, $includeMessages = false): array {
		$myFines = [];
		$request = $this->getSearchbyPatronIdRequest($patron);

		// Fines
		$request->TransactionType = 'Fine';
		$result = $this->doSoapRequest('getPatronTransactions', $request);
		//global $logger;
		//$logger->log("Result of getPatronTransactions (Fine)\r\n" . print_r($result, true), Logger::LOG_ERROR);
		if ($result && !empty($result->FineItems->FineItem)) {
			if (!is_array($result->FineItems->FineItem)) {
				$result->FineItems->FineItem = [$result->FineItems->FineItem];
			}
			foreach ($result->FineItems->FineItem as $fine) {
				// hard coded Nashville school branch IDs
				if ($fine->Branch == 0) {
					$fine->Branch = $fine->TransactionBranch;
				}

				if ($fine->FineAmountPaid > 0) {
					$fine->FineAmountOutstanding = $fine->FineAmount - $fine->FineAmountPaid;
				} else {
					$fine->FineAmountOutstanding = $fine->FineAmount;
				}

				if (str_starts_with($fine->Identifier, 'ITEM ID: ')) {
					$fine->Identifier = substr($fine->Identifier, 9);
				}
				$fine->Identifier = str_replace('#', '', $fine->Identifier);
				$fineType = $fine->TransactionCode;
				$fine->FeeNotes = $fine->TransactionCode . ' (' . CarlX::$fineTypeTranslations[$fine->TransactionCode] . ') ' . $fine->FeeNotes;

				$myFines[] = [
					'fineId' => $fine->Identifier,
					'type' => $fineType,
					'reason' => $fine->FeeNotes,
					'amount' => $fine->FineAmount,
					'amountVal' => $fine->FineAmount,
					'amountOutstanding' => $fine->FineAmountOutstanding,
					'amountOutstandingVal' => $fine->FineAmountOutstanding,
					'message' => $fine->Title,
					'date' => date('M j, Y', strtotime($fine->FineAssessedDate)),
				];
			}
		}

		// Lost Item Fees
		if ($result && $result->LostItemsCount > 0) {
			// TODO: Lost Items don't have the fine amount
			$request->TransactionType = 'Lost';
			$result = $this->doSoapRequest('getPatronTransactions', $request);
			//$logger->log("Result of getPatronTransactions (Lost)\r\n" . print_r($result, true), Logger::LOG_ERROR);

			if ($result && !empty($result->LostItems->LostItem)) {
				if (!is_array($result->LostItems->LostItem)) {
					$result->LostItems->LostItem = [$result->LostItems->LostItem];
				}
				foreach ($result->LostItems->LostItem as $fine) {
					// hard coded Nashville school branch IDs
					if ($fine->Branch == 0) {
						$fine->Branch = $fine->TransactionBranch;
					}
					$fine->System = $this->getFineSystem($fine->Branch);
					$fine->CanPayFine = $this->canPayFine($fine->System);

					if (!empty($fine->FeeAmountPaid) && $fine->FeeAmountPaid > 0) {
						$fine->FeeAmountOutstanding = $fine->FeeAmount - $fine->FeeAmountPaid;
					} else {
						$fine->FeeAmountOutstanding = $fine->FeeAmount;
					}

					if (str_starts_with($fine->Identifier, 'ITEM ID: ')) {
						$fine->Identifier = substr($fine->Identifier, 9);
					}

					$fineType = $fine->TransactionCode;
					$fine->FeeNotes = $fine->TransactionCode . ' (' . CarlX::$fineTypeTranslations[$fine->TransactionCode] . ') ' . $fine->FeeNotes;

					$myFines[] = [
						'fineId' => $fine->Identifier,
						'type' => $fineType,
						'reason' => $fine->FeeNotes,
						'amount' => $fine->FeeAmount,
						'amountVal' => $fine->FeeAmount,
						'amountOutstanding' => $fine->FeeAmountOutstanding,
						'amountOutstandingVal' => $fine->FeeAmountOutstanding,
						'message' => $fine->Title,
						'date' => date('M j, Y', strtotime($fine->TransactionDate)),
					];
				}
				// The following epicycle is required because CarlX PatronAPI GetPatronTransactions Lost does not report FeeAmountOutstanding. See TLC ticket https://ww2.tlcdelivers.com/helpdesk/Default.asp?TicketID=515720
				$myLostFines = $this->getLostViaSIP($patron->cat_username);
				$myFinesIds = array_column($myFines, 'fineId');
				foreach ($myLostFines as $myLostFine) {
					$keys = array_keys($myFinesIds, $myLostFine['fineId']);
					foreach ($keys as $key) {
						// CarlX can have Processing fees and Lost fees associated with the same item id; here we target only the Lost, because Processing fees correctly report previous partial payments through the PatronAPI
						if ($myFines[$key]['type'] == "L") {
							$myFines[$key]['amountOutstanding'] = $myLostFine['amountOutstanding'];
							$myFines[$key]['amountOutstandingVal'] = $myLostFine['amountOutstandingVal'];
							break;
						}
					}
				}
			}
		}
		$sorter = function ($a, $b) {
			$messageA = $a['message'];
			$messageB = $b['message'];
			return strcasecmp($messageA, $messageB);
		};
		uasort($myFines, $sorter);
		return $myFines;
	}

	public function canPayFine($system) {
		return true;
	}

	public function getFineSystem($branchId) {
		return '';
	}

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
	private function getPatronTransactions(User $user) {
		$request = $this->getSearchbyPatronIdRequest($user);
        return $this->doSoapRequest('getPatronTransactions', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
	}

	public function getPhoneTypeList() {
		$request = new stdClass();
		$request->Modifiers = '';

		$result = $this->doSoapRequest('getPhoneTypeList', $request);
		if ($result) {
			$phoneTypes = [];
			foreach ($result->PhoneTypes->PhoneType as $phoneType) {
				$phoneTypes[$phoneType->SortGroup][$phoneType->PhoneTypeId] = $phoneType->Description;
			}
			return $phoneTypes;
		}
		return false;
	}

	private function getBranchInformation($branchNumber = null, $branchCode = null) {
		/** @var Memcache $memCache */ global $memCache;

		if (!empty($branchNumber)) {
			$branchInfo = $memCache->get('carlx_branchNumbers');
			if (!empty($branchInfo) and isset($branchInfo[$branchNumber])) {
				return $branchInfo[$branchNumber];
			} else {
				$request = new stdClass();
				$request->BranchSearchType = 'Branch Number';
				$request->BranchSearchValue = $branchNumber;
				$request->Modifiers = '';
			}
		} elseif (!empty($branchCode)) {
			$branchInfo = $memCache->get('carlx_branchCodes');
			if (!empty($branchInfo) and isset($branchInfo[$branchCode])) {
				return $branchInfo[$branchCode];
			} else {
				$request = new stdClass();
				$request->BranchSearchType = 'Branch Code';
				$request->BranchSearchValue = $branchCode;
				$request->Modifiers = '';
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
					$branchInfo = [
						$branchNumber = $result->BranchInfo,
					];
				}
				$memCache->set('carlx_branchNumbers', $branchInfo, $configArray['Caching']['carlx_branchNumbers']);
			} elseif (!empty($branchCode)) {
				$branchInfo = $memCache->get('carlx_branchCodes');
				if ($branchInfo) {
					$branchInfo[$branchCode] = $result->BranchInfo;
				} else {
					$branchInfo = [
						$branchCode => $result->BranchInfo,
					];
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
	private function extractDateFromCarlXDateField($dateField) {
		return strstr($dateField, 'T', true);
	}

	/**
	 * @param $user
	 * @return stdClass
	 */
	protected function getSearchbyPatronIdRequest(User $user) {
		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID = $user->cat_username; // TODO: Question: barcode/pin check
		$request->Modifiers = '';
		return $request;
	}

	private function getUnavailableHold(User $patron, $holdID) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->TransactionType = 'UnavailableHold';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		if ($result && !empty($result->UnavailableHoldItems->UnavailableHoldItem)) {
			if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) {
				$result->UnavailableHoldItems->UnavailableHoldItem = [$result->UnavailableHoldItems->UnavailableHoldItem];
			}
			foreach ($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
				if ($hold->BID == $holdID) {
					return $hold;
				}
			}
		}
		return false;
	}

	private function getUnavailableHoldViaSIP(User $patron, $holdId) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->TransactionType = 'UnavailableHold';

		/** @noinspection PhpUnusedLocalVariableInspection */
		$result = $this->doSoapRequest('getPatronTransactions', $request);

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
			ExternalRequestLogEntry::logRequest('carlx.selfCheckStatus', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			// Make sure the response is 98 as expected
			if (str_starts_with($msg_result, "98")) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])) {
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				} else {
					$mySip->AO = 'NASH'; /* set AO to value returned */ // hardcoded Nashville
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				} else {
					$mySip->AN = '';
				}

				$mySip->patron = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				$in = $mySip->msgPatronInformation('unavail', 1, 110); // hardcoded Nashville - circulation policy allows 100 holds for many borrower types
				$sipResponse = $mySip->get_message($in);
				$result = $mySip->parsePatronInfoResponse($sipResponse);
				ExternalRequestLogEntry::logRequest('carlx.getUnavailableHolds', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $sipResponse, ['patronPwd' => $patron->cat_password]);
				if ($result && !empty($result['variable']['CD'])) {
					if (!is_array($result['variable']['CD'])) {
						$result['variable']['CD'] = (array)$result['variable']['CD'];
					}
					foreach ($result['variable']['CD'] as $unavailableHold) {
						$hold = [];
						$hold['Raw'] = explode("^", $unavailableHold);
						foreach ($hold['Raw'] as $item) {
							$field = substr($item, 0, 1);
							$value = substr($item, 1);
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
		return [
			'holdId' => $holdId,
			'title' => $title,
			'pickupLocation' => $pickupLocation,
			// NB branchcode, not branchnumber
			'queuePosition' => $queuePosition,
			'freezeReactivationDate' => $freezeReactivationDate,
			'success' => $success,
			'message' => $message,
		];
	}

	public function placeHoldViaSIP(User $patron, $holdId, $pickupBranch = null, $cancelDate = null, $type = null, $queuePosition = null, $freeze = null, $freezeReactivationDate = null) {
		if (str_starts_with($holdId, $this->accountProfile->recordSource . ':')) {
			$holdId = str_replace($this->accountProfile->recordSource . ':', '', $holdId);
		}
		//Place the hold via SIP 2
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		$success = false;
		$title = '';
		$message = 'Failed to connect to complete requested action.';
		$apiResult = [
			'title' => translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]),
		];

		if ($mySip->connect()) {
			//send self check status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			ExternalRequestLogEntry::logRequest('carlx.selfCheckStatus', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			// Make sure the response is 98 as expected
			if (str_starts_with($msg_result, "98")) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])) {
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				} else {
					$mySip->AO = 'NASH'; /* set AO to value returned */ // hardcoded for Nashville
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				} else {
					$mySip->AN = '';
				}

				$mySip->patron = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				if (empty($pickupBranch)) {
					//Get the code for the location
					$locationLookup = new Location();
					$locationLookup->locationId = $patron->homeLocationId;
					$locationLookup->find(1);
					if ($locationLookup->getNumResults() > 0) {
						$pickupBranch = strtoupper($locationLookup->code);
					}
				} else {
					$pickupBranch = strtoupper($pickupBranch);
				}
				$pickupBranchInfo = $this->getBranchInformation(null, $pickupBranch);
				$pickupBranchNumber = $pickupBranchInfo->BranchNumber;

				//place the hold
				$itemId = '';
				$recordId = '';
				if (str_starts_with($holdId, 'ITEM ID: ')) {
					$holdType = 3; // specific copy
					$itemId = substr($holdId, 9);
				} elseif (str_starts_with($holdId, 'BID: ')) {
					$holdType = 2; // any copy of title
					$recordId = substr($holdId, 5);
				} elseif (str_starts_with($holdId, 'CARL')) {
					$holdType = 2; // any copy of title
					$recordId = $this->BIDfromFullCarlID($holdId);
				} else { // assume a short BID
					$holdType = 2; // any copy of title
					$recordId = $holdId;
				}

				if ($type == 'cancel') {
					$mode = '-';
					// Get Title  (Title is not part of the cancel response)
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->fullCarlIDfromBID($recordId));
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
					}
				} elseif ($type == 'recall') { // NASHVILLE DOES NOT ALLOW RECALL // TO DO: Evaluate what recall code should be
					$mode = '-';
					// Get Title  (Title is not part of the cancel response)
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->fullCarlIDfromBID($recordId));
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
					}

				} elseif ($type == 'update') {
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
				ExternalRequestLogEntry::logRequest('carlx.placeHold', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, ['patronPwd' => $patron->cat_password]);

				if (str_starts_with($msg_result, "16")) {
					$result = $mySip->parseHoldResponse($msg_result);
					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];
					if (!empty($result['variable']['AJ'][0])) {
						$title = $result['variable']['AJ'][0];
					}
					if ($success) {
						$apiResult['title'] = translate([
							'text' => 'Hold placed successfully',
							'isPublicFacing' => true,
						]);
						$apiResult['action'] = translate([
							'text' => 'Go to Holds',
							'isPublicFacing' => true,
						]);
						$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
						$patron->forceReloadOfHolds();
					}
				}
			}
		}

		$apiResult['message'] = $message;
		return [
			'title' => $title,
			'bib' => $recordId,
			'success' => $success,
			'message' => translate([
				'text' => $message,
				'isPublicFacing' => true,
			]),
			'api' => $apiResult,
		];
	}


	public function renewCheckoutViaSIP(User $patron, $itemId) {
		//renew the item via SIP 2
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		$success = false;
		$message = 'Failed to connect to complete requested action.';
		$apiResult['title'] = translate([
			'text' => 'Unable to renew item',
			'isPublicFacing' => true,
		]);
		if ($mySip->connect()) {
			//send selfcheck status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			ExternalRequestLogEntry::logRequest('carlx.selfCheckStatus', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			// Make sure the response is 98 as expected
			if (str_starts_with($msg_result, "98")) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])) {
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				} else {
					$mySip->AO = 'NASH'; /* set AO to value returned */
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				} else {
					$mySip->AN = '';
				}

				$mySip->patron = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				$in = $mySip->msgRenew($itemId, '', '', '', 'N', 'N', 'Y');
				//print_r($in . '<br/>');
				$msg_result = $mySip->get_message($in);
				ExternalRequestLogEntry::logRequest('carlx.renewCheckout', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, ['patronPwd' => $patron->cat_password]);
				//print_r($msg_result);

				if (str_starts_with($msg_result, "30")) {
					$result = $mySip->parseRenewResponse($msg_result);

//					$title = $result['variable']['AJ'][0];

					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];

					if (!$success) {
						$title = $result['variable']['AJ'][0];
						$apiResult['message'] = $message;
						$message = empty($title) ? $message : "<p style=\"font-style:italic\">$title</p><p>$message.</p>";
					} else {
						$apiResult['title'] = translate([
							'text' => 'Title renewed successfully',
							'isPublicFacing' => true,
						]);
						$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
						$patron->forceReloadOfCheckouts();
					}


				}
			}
		} else {
			$message = "Could not connect to circulation server, please try again later.";
			$apiResult['message'] = $message;
		}

		return [
			'itemId' => $itemId,
			'success' => $success,
			'message' => $message,
			'api' => $apiResult,
		];
	}

	/**
	 * @param $BID
	 * @return string CARL ID
	 */
	private function fullCarlIDfromBID($BID) {
		return 'CARL' . str_pad($BID, 10, '0', STR_PAD_LEFT);
	}

	private function BIDfromFullCarlID($CarlID) {
		if (strpos($CarlID, ':') > 0) {
			[
				,
				$CarlID,
			] = explode(':', $CarlID);
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

	public function findNewUserByEmail($patronEmail): mixed {
		return false;
	}

	public function getAccountSummary(User $patron): AccountSummary {
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		//Load summary information for number of holds, checkouts, etc
		$patronSummaryRequest = new stdClass();
		$patronSummaryRequest->SearchType = 'Patron ID';
		$patronSummaryRequest->SearchID = $patron->cat_username;
		$patronSummaryRequest->Modifiers = '';

		$patronSummaryResponse = $this->doSoapRequest('getPatronTransactions', $patronSummaryRequest, $this->patronWsdl);

		if (!empty($patronSummaryResponse) && is_object($patronSummaryResponse)) {
			$summary->numCheckedOut += $patronSummaryResponse->ChargedItemsCount;
			$summary->numCheckedOut += $patronSummaryResponse->OverdueItemsCount;
			$summary->numOverdue = $patronSummaryResponse->OverdueItemsCount;
			$summary->numAvailableHolds = $patronSummaryResponse->HoldItemsCount;
			$summary->numUnavailableHolds = $patronSummaryResponse->UnavailableHoldsCount;

			$outstandingFines = $patronSummaryResponse->FineTotal + $patronSummaryResponse->LostItemFeeTotal;
			$summary->totalFines = floatval($outstandingFines);

			$request = $this->getSearchbyPatronIdRequest($patron);
			$patronInfoResponse = $this->doSoapRequest('getPatronInformation', $request);
			if ($patronInfoResponse && $patronInfoResponse->Patron) {
				$summary->expirationDate = strtotime($this->extractDateFromCarlXDateField($patronInfoResponse->Patron->ExpirationDate));
			}
		}

		return $summary;
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool {
		return false;
	}

	public function getHoldsReportData($location): array {
		$this->initDatabaseConnection();
		$sql = <<<EOT
			with holds_vs_items as (
				select
					t.bid
					, t.occur
					, p.name as PATRON_NAME
					, p.sponsor as HOME_ROOM
					, bb.btyname as GRD_LVL
					, p.patronid as P_BARCODE
					, l.locname as SHELF_LOCATION
					, b.title as TITLE
					, i.cn as CALL_NUMBER
					, i.item as ITEM_ID
					, dense_rank() over (partition by t.bid order by t.occur asc) as occur_dense_rank
					, dense_rank() over (partition by t.bid order by i.item asc) as nth_item_on_shelf
				from transbid_v t
				left join patron_v p on t.patronid = p.patronid
				left join bbibmap_v b on t.bid = b.bid
				left join bty_v bb on p.bty = bb.btynumber
				left join branch_v ob on t.holdingbranch = ob.branchnumber -- Origin Branch
				left join item_v i on ( t.bid = i.bid and t.holdingbranch = i.branch)
				left join location_v l on i.location = l.locnumber
				where ob.branchcode = '$location'
				and t.pickupbranch = ob.branchnumber -- pickup branch field changed from t.renew to t.pickupbranch in CarlX 9.6.8.0
				and t.transcode = 'R*'
				and i.status = 'S'
				order by 
					t.bid
					, t.occur
			), 
			fillable as (
				select 
					h.*
				from holds_vs_items h
				where h.occur_dense_rank = h.nth_item_on_shelf
				order by 
					h.SHELF_LOCATION
					, h.CALL_NUMBER
					, h.TITLE
					, h.occur_dense_rank
			)
			select
				PATRON_NAME
				, HOME_ROOM
				, GRD_LVL
				, P_BARCODE
				, SHELF_LOCATION
				, TITLE
				, CALL_NUMBER
				, ITEM_ID
			from fillable
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		$data = [];
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}

	public function getStudentBarcodeData($location, $homeroom) {
		$this->initDatabaseConnection();
		// query students by school and homeroom
		/** @noinspection SqlResolve */
		$sql = <<<EOT
			select
				patronbranch.branchcode
				, patronbranch.branchname
				, bty_v.btynumber AS bty
				, bty_v.btyname as grade
				, case 
						when bty = 13 
						then patron_v.name
						else patron_v.sponsor
					end as homeroom
				, case 
						when bty = 13 
						then patron_v.patronid
						else patron_v.street2
					end as homeroomid
				, patron_v.name AS patronname
				, patron_v.patronid
				, patron_v.lastname
				, patron_v.firstname
				, patron_v.middlename
				, patron_v.suffixname
			from
				branch_v patronbranch
				, bty_v
				, patron_v
			where
				patron_v.bty = bty_v.btynumber
				and patronbranch.branchnumber = patron_v.defaultbranch
				and (
					(
						patron_v.bty in ('21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','46','47')
						and patronbranch.branchcode = '$location'
						and patron_v.street2 = '$homeroom'
					) or (
						patron_v.bty = 13
						and patron_v.patronid = '$homeroom'
					)
				)
			order by
				patronbranch.branchcode
				, case
					when patron_v.bty = 13 then 0
					else 1
				end
				, patron_v.sponsor
				, patron_v.name
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		$data = [];
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}

	public function getStudentBarcodeDataHomerooms($location) {
		$this->initDatabaseConnection();
		// query school branch codes and homerooms
		/** @noinspection SqlResolve */
		/** @noinspection SqlConstantExpression */
		$sql = <<<EOT
			select 
			  branchcode
			  , homeroomid
			  , min(homeroomname) as homeroomname
			  , case
				when min(grade) < max(grade)
				  then replace(replace(trim(to_char(min(grade),'00')),'-01','PK'),'00','KI') || '-' || replace(replace(trim(to_char(max(grade),'00')),'-01','PK'),'00','KI')
				  else replace(replace(trim(to_char(min(grade),'00')),'-01','PK'),'00','KI') || '___'
			  end as grade
			from (
			  select distinct
				b.branchcode
				, s.street2 as homeroomid
				, nvl(regexp_replace(upper(h.name),'[^A-Z]','_'),'_NULL_') as homeroomname
				, s.bty-22 as grade
			  from
				branch_v b
				  left join patron_v s on b.branchnumber = s.defaultbranch
				  left join patron_v h on s.street2 = h.patronid
			  where
				b.branchcode = '$location'
				and s.street2 is not null
				and s.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
			  order by
				b.branchcode
				, homeroomname
			) a
			group by branchcode, homeroomid
			order by branchcode, homeroomname
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		$data = [];
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}

	public function getStudentReportData($location, $showOverdueOnly, $date) {
		$this->initDatabaseConnection();
		if ($showOverdueOnly == 'checkedOut') {
			$statuses = "(TRANSITEM_V.transcode = 'O' or transitem_v.transcode='L' or transitem_v.transcode='C')";
		} elseif ($showOverdueOnly == 'overdue') {
			$statuses = "(TRANSITEM_V.transcode = 'O' or transitem_v.transcode='L')";
		}
        if ($showOverdueOnly == 'checkedOut' || $showOverdueOnly == 'overdue') {
            /** @noinspection SqlResolve */
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
                      , to_char(transitem_v.dueornotneededafterdate,'MM/DD/YYYY') AS Due_Date
                      , item_v.price AS Owed
                      , to_char(transitem_v.dueornotneededafterdate,'MM/DD/YYYY') AS Due_Date_Dup
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
                      and $statuses
                      and patronbranch.branchgroup = '2'
                      and patronbranchgroup.branchgroup = patronbranch.branchgroup
                      and bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
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
        } elseif ($showOverdueOnly == 'fees') {
			/** @noinspection SqlResolve */
            $sql = <<<EOT
               -- school fees report CarlX sql
                with p as ( -- first gather patrons of the requested branch
                    select
                        b.branchcode
                        , b.branchname
                        , p.bty
                        , t.btyname
                        , p.sponsor
                        , p.name
                        , p.patronid
                    from patron_v p
                    left join branch_v b on p.defaultbranch = b.branchnumber
                    left join bty_v t on p.bty = t.btynumber
                    where p.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
                    and b.branchcode = '$location'
                ), r as (
                select
                     p.branchcode
                    , p.branchname
                    , p.bty
                    , p.btyname
                    , p.sponsor
                    , p.name
                    , p.patronid
                    , r.callnumber
                    , r.title
                    , to_char(r.dueornotneededafterdate,'MM/DD/YYYY') as due
                    , to_char(r.amountowed / 100, 'fm999D00') as owed
                    , r.item
                from p 
                left join report3fines_v r on p.patronid = r.patronid
                where r.patronid is not null
                and r.amountowed > 0
                order by 
                    p.branchcode
                    , p.bty
                    , p.sponsor
                    , p.name
                    , r.callnumber
                    , r.title
            ) 
            select
                     r.branchcode AS Home_Lib_Code
                    , r.branchname AS Home_Lib
                    , r.bty AS P_Type
                    , r.btyname AS Grd_Lvl
                    , r.sponsor AS Home_Room
                    , r.name AS Patron_Name
                    , r.patronid AS P_Barcode
                    , itembranch.branchgroup AS SYSTEM
                    , r.callnumber AS Call_Number
                    , r.title AS Title
                    , r.due AS Due_Date
                    , r.owed as Owed
                    , r.due AS Due_Date_Dup
                    , r.item AS Item
            from r
            left join item_v i on r.item = i.item
            left join branch_v itembranch on i.owningbranch = itembranch.branchnumber
            order by 
                r.branchcode
                , r.bty
                , r.sponsor
                , r.name
                , itembranch.branchgroup
                , r.callnumber
                , r.title
EOT;
        }
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}

	//Defaults are correct for this
//	function getPasswordPinValidationRules(){
//		return [
//			'minLength' => 4,
//			'maxLength' => 6,
//			'onlyDigitsAllowed' => true,
//		];
//	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user) {
		//This is similar to patron Login
		$request = $this->getSearchbyPatronIdRequest($user);
		$result = $this->doSoapRequest('getPatronInformation', $request, $this->patronWsdl);
		if ($result) {
			if (isset($result->Patron)) {
				$this->loadContactInformationFromSoapResult($user, $result);
			}
		}
	}

	public function loadContactInformationFromSoapResult(User $user, stdClass $soapResult) {
		$user->_fullname = isset($soapResult->Patron->FullName) ? $soapResult->Patron->FullName : '';
		$user->_emailReceiptFlag = $soapResult->Patron->EmailReceiptFlag;
		$user->_availableHoldNotice = $soapResult->Patron->SendHoldAvailableFlag;
		$user->_comingDueNotice = $soapResult->Patron->SendComingDueFlag;
		$user->_phoneType = $soapResult->Patron->PhoneType;

		$location = new Location();
		$location->code = strtolower($soapResult->Patron->DefaultBranch);
		if ($location->find(true)) {
			if ($user->myLocation1Id > 0) {
				/** @var /Location $location */
				//Get display name for preferred location 1
				$myLocation1 = new Location();
				$myLocation1->locationId = $user->myLocation1Id;
				if ($myLocation1->find(true)) {
					$user->_myLocation1 = $myLocation1->displayName;
				}
			}

			if ($user->myLocation2Id > 0) {
				//Get display name for preferred location 2
				$myLocation2 = new Location();
				$myLocation2->locationId = $user->myLocation2Id;
				if ($myLocation2->find(true)) {
					$user->_myLocation2 = $myLocation2->displayName;
				}
			}

			//Get display names that aren't stored
			$user->_homeLocationCode = $location->code;
			$user->_homeLocation = $location->displayName;
		}

		if (isset($soapResult->Patron->Addresses)) {
			//Find the primary address
			$primaryAddress = null;
			foreach ($soapResult->Patron->Addresses->Address as $address) {
				if ($address->Type == 'Primary') {
					$primaryAddress = $address;
					break;
				}
			}
			if ($primaryAddress != null) {
				$user->_address1 = $primaryAddress->Street;
				$user->_address2 = $primaryAddress->City . ', ' . $primaryAddress->State;
				$user->_city = $primaryAddress->City;
				$user->_state = $primaryAddress->State;
				$user->_zip = $primaryAddress->PostalCode;
			}
		}

		if (isset($soapResult->Patron->EmailNotices)) {
			$user->_notices = $soapResult->Patron->EmailNotices;
		}

		$user->_web_note = '';

		$user->_expires = $this->extractDateFromCarlXDateField($soapResult->Patron->ExpirationDate);
		$user->_expired = 0; // default setting
		$user->_expireClose = 0;

		$timeExpire = strtotime($user->_expires);
		$timeNow = time();
		$timeToExpire = $timeExpire - $timeNow;
		if ($timeToExpire <= 30 * 24 * 60 * 60) {
			if ($timeToExpire <= 0) {
				$user->_expired = 1;
			}
			$user->_expireClose = 1;
		}
	}

	public function showOutstandingFines() {
		return true;
	}

	static $fineTypeTranslations = [
		'F' => 'Fine',
		'F2' => 'Processing',
		'FS' => 'Manual',
		'L' => 'Lost',
	];

	static $fineTypeSIP2Translations = [
		'F' => '04',
		'F2' => '05',
		'FS' => '01',
		'L' => '07',
	];

	function getForgotPasswordType() {
		return 'emailAspenResetLink';
	}

	public function getPatronIDChanges($searchPatronID): ?array {
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = <<<EOT
			select
				newpatronid
			from patronidchange_v p
			where oldpatronid = :searchpatronid
			order by changetime desc 
			-- fetch first 1 rows only
EOT;

		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_bind_by_name($stid, ':searchpatronid', $searchPatronID);
		oci_execute($stid);
		$data = [];
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}

	public function showHoldPosition(): bool {
		return true;
	}

	public function suspendRequiresReactivationDate(): bool {
		return true;
	}

	public function showDateWhenSuspending(): bool {
		return true;
	}

	public function showTimesRenewed(): bool {
		return true;
	}

	public function showDateInFines(): bool {
		return false;
	}

	public function bypassReadingHistoryUpdate($patron) : bool {
		//Check to see if the last seen date is after the last time we updated reading history
		$request = $this->getSearchbyPatronIdRequest($patron);
		$result = $this->doSoapRequest('getPatronInformation', $request, $this->patronWsdl);

		$selfServeActivityDate = strtotime($result->Patron->SelfServeActivityDate);
		$lastActionDate = strtotime($patron->Patron->LastActionDate);
		$lastReadingHistoryUpdate = $patron->lastReadingHistoryUpdate;
		if ($selfServeActivityDate > $lastReadingHistoryUpdate || $lastActionDate > $lastReadingHistoryUpdate) {
			return false;
		}
		return true;
	}
}