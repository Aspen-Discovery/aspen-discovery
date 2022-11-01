<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
class LibrarySolution extends AbstractIlsDriver {
    private $curlWrapper;

    public function __construct($accountProfile) {
        parent::__construct($accountProfile);
        $this->curlWrapper = new CurlWrapper();
        $this->curlWrapper->addCustomHeaders($this->getCustomHeaders(), true);
    }

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 * Interface defined in CatalogConnection.php
	 *
	 * This driver does not currently support SSO since we have to pass the password for login
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

		//Post enter barcode and pin to the login screen
		$loginSucceeded = $this->loginPatronToLSS($username, $password);
		if ($loginSucceeded){
			//Get the account summary
			$url = $this->getVendorOpacUrl() . '/account/summary?_=' . time() * 1000;
			$accountSummaryRaw = $this->curlWrapper->curlGetPage($url);
			$accountSummary = json_decode($accountSummaryRaw);
			if (!empty($accountSummary)) {

				$userExistsInDB = false;
				$user           = new User();
				$user->username = $accountSummary->patron->guid;
				$user->source   = $this->accountProfile->name;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}

				$user->password = $accountSummary->patron->pin;

				$forceDisplayNameUpdate = false;
				$firstName              = $accountSummary->patron->firstName;
				if ($user->firstname != $firstName) {
					$user->firstname        = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = $accountSummary->patron->lastName;
				if ($user->lastname != $lastName) {
					$user->lastname         = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate) {
					$user->displayName = '';
				}
				$user->_fullname     = $accountSummary->patron->fullName;
				$user->cat_username = $accountSummary->patron->patronId;
				$user->cat_password = $accountSummary->patron->pin;
				$user->phone        = $accountSummary->patron->phone;
				$user->email        = $accountSummary->patron->email;

				//Setup home location
				$location = null;
				if (isset($accountSummary->patron->issuingBranchId) || isset($accountSummary->patron->defaultRequestPickupBranch)) {
					$homeBranchCode = isset($accountSummary->patron->issuingBranchId) ? $accountSummary->patron->issuingBranchId : $accountSummary->patron->defaultRequestPickupBranch;
					$homeBranchCode = str_replace('+', '', $homeBranchCode);
					//Translate home branch to plain text
					$location       = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(true)) {
						unset($location);
					}
				} else {
					global $logger;
					$logger->log('Library Solution Driver: No Home Library Location or Hold location found in account look-up. User : ' . $user->id, Logger::LOG_ERROR);
					// The code below will attempt to find a location for the library anyway if the homeLocation is already set
				}

				if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
					if (empty($user->homeLocationId) && !isset($location)) {
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

				$user->_expires = $accountSummary->patron->cardExpirationDate;
				list ($yearExp, $monthExp, $dayExp) = explode("-", $user->_expires);
				$timeExpire    = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
				$timeNow       = time();
				$timeToExpire  = $timeExpire - $timeNow;
				$user->_expired = 0;
				if ($timeToExpire <= 30 * 24 * 60 * 60) {
					if ($timeToExpire <= 0) {
						$user->_expired = 1;
					}
					$user->_expireClose = 1;
				} else {
					$user->_expireClose = 0;
				}

				$user->_address1 = $accountSummary->patron->address1;
				$user->_city     = $accountSummary->patron->city;
				$user->_state    = $accountSummary->patron->state;
				$user->_zip      = $accountSummary->patron->zipcode;

				$user->_fines    = $accountSummary->patron->fees / 100;
				$user->_finesVal = floatval(preg_replace('/[^\\d.]/', '', $user->_fines));

				$user->_numCheckedOutIls     = $accountSummary->accountSummary->loanCount;
				$user->_numHoldsAvailableIls = $accountSummary->accountSummary->arrivedHolds;
				$user->_numHoldsRequestedIls = $accountSummary->accountSummary->pendingHolds;
				$user->_numHoldsIls          = $user->_numHoldsAvailableIls + $user->_numHoldsRequestedIls;

				if ($userExistsInDB) {
					$user->update();
				} else {
					$user->created = date('Y-m-d');
					$user->insert();
				}

				$timer->logTime("patron logged in successfully");
				return $user;
			}else{
				// bad or empty response; or json decoding error
				global $logger;
				$logger->log('Bad or Empty response for Library Solution Account Summary call during login', Logger::LOG_ERROR);
				$timer->logTime("patron login failed");
				return null;
			}
		}else{
			$info = curl_getinfo($this->curlWrapper->curl_connection);
			$timer->logTime("patron login failed");
			return null;
		}
	}

	public function hasNativeReadingHistory() : bool {
		return true;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return array
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1) {
		$readingHistory = array();
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)){
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/loans/history/0/20/OutDate?_=' . time() * 1000;
			$loanInfoRaw = $this->curlWrapper->curlGetPage($url);
			$loanInfo = json_decode($loanInfoRaw);

			foreach ($loanInfo->loanHistory as $loan){
				$dueDate = $loan->dueDate;
				$curTitle = array();
				$curTitle['itemId']       = $loan->itemId;
				$curTitle['id']           = $loan->bibliographicId;
				$curTitle['shortId']      = $loan->bibliographicId;
				$curTitle['recordId']     = $loan->bibliographicId;
				$curTitle['title']        = utf8_encode($loan->title);
				$curTitle['author']       = utf8_encode($loan->author);
				$curTitle['dueDate']      = $dueDate;        // item likely will not have a dueDate, (get null value)
				$curTitle['checkout']     = $loan->outDateString; // item always has a outDateString
				$curTitle['borrower_num'] = $patron->id;

				//Get additional information from MARC Record
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver( $this->accountProfile->recordSource . ":" . $curTitle['recordId']);
					if ($recordDriver->isValid()){
						$historyEntry['permanentId'] = $recordDriver->getPermanentId();
						$curTitle['coverUrl']        = $recordDriver->getBookcoverUrl('medium', true);
						$curTitle['groupedWorkId']   = $recordDriver->getGroupedWorkId();
						$curTitle['ratingData']      = $recordDriver->getRatingData();
						$curTitle['linkUrl']         = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
						$curTitle['format']          = $recordDriver->getFormats();
						$curTitle['author']          = $recordDriver->getPrimaryAuthor();
						if (!isset($curTitle['title']) || empty($curTitle['title'])){
							$curTitle['title']         = $recordDriver->getTitle();
						}
					}else{
						$historyEntry['permanentId'] = null;
						$curTitle['coverUrl']        = "";
						$curTitle['groupedWorkId']   = "";
						$curTitle['format']          = "Unknown";
					}
				}
				$curTitle['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($curTitle['title'])); // set after title might have been fetched from Marc

				$readingHistory[] = $curTitle;
			}
		}

		//LSS does not have a way to disable reading history so we will always set to true.
		if (!$patron->trackReadingHistory){
			$patron->trackReadingHistory = 1;
			$patron->update();
		}


		return array('historyActive'=>true, 'titles'=>$readingHistory, 'numTitles'=> count($readingHistory));
	}

	protected function getCustomHeaders() {
		return array(
			'Host: tlcweb01.mnps.org:8080',
			'User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
			'Accept: application/json, text/javascript, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Accept-Encoding: gzip, deflate',
			'Content-Type: application/json; charset=utf-8',
			'Ls2pac-config-type: pac',
			'Ls2pac-config-name: ysm',
			'X-Requested-With: XMLHttpRequest',
			'Connection: keep-alive',
			'Pragma: no-cache',
			'Cache-Control: no-cache',
		);
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
	public function getCheckouts(User $patron) : array{
		$transactions = array();
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)){
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/loans/0/20/Status?_=' . time() * 1000;
			$loanInfoRaw = $this->curlWrapper->curlGetPage($url);
			$loanInfo = json_decode($loanInfoRaw);

			foreach ($loanInfo->loans as $loan){
				$curTitle = array();
				$curTitle['checkoutSource'] = 'ILS';
				$curTitle['itemId'] = $loan->itemId;
				$curTitle['renewIndicator'] = $loan->itemId;
				$curTitle['id'] = $loan->bibliographicId;
				$curTitle['shortId'] = $loan->bibliographicId;
				$curTitle['recordId'] = $loan->bibliographicId;
				$curTitle['title'] = utf8_encode($loan->title);
				$curTitle['author'] = utf8_encode($loan->author);
				$dueDate = $loan->dueDate;
				if ($dueDate){
					$dueDate = strtotime($dueDate);
				}
				$curTitle['dueDate'] = $dueDate;
				/*$curTitle['renewCount']
				$curTitle['barcode']
				$curTitle['canRenew']
				$curTitle['itemindex']
				$curTitle['renewIndicator']
				*/

				//Get additional information from MARC Record
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver( $this->accountProfile->recordSource . ":" . $curTitle['recordId']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
						$curTitle['ratingData'] = $recordDriver->getRatingData();
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$formats = $recordDriver->getFormats();
						$curTitle['format'] = reset($formats);
						$curTitle['author'] = $recordDriver->getPrimaryAuthor();
						if (!isset($curTitle['title']) || empty($curTitle['title'])){
							$curTitle['title'] = $recordDriver->getTitle();
						}
					}else{
						$curTitle['coverUrl'] = "";
						$curTitle['groupedWorkId'] = "";
						$curTitle['format'] = "Unknown";
						$curTitle['author'] = "";
					}
					$curTitle['link'] = $recordDriver->getLinkUrl();
				}

				$transactions[] = $curTitle;
			}
		}

		return $transactions;
	}

	public function hasFastRenewAll() : bool{
		return false;
	}

	public function renewAll(User $patron){
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	public function isAuthenticated(){
		$url = $this->getVendorOpacUrl() . '/isAuthenticated?_=' . time() * 1000;
		$result = $this->curlWrapper->curlGetPage($url);
		return $result == 'true';
	}

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => "Sorry, we were unable to renew your checkout.");
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//$isAuthenticated = $this->isAuthenticated();
			$url = $this->getVendorOpacUrl() . '/loans/renew?_=' . time() * 1000;
			$postParams = '{"renewLoanInfos":"[{\"success\":false,\"itemId\":\"' . $itemId . '\",\"date\":' . (time() * 1000) . ',\"downloadable\":false}]"}';
			$renewCheckoutResponseRaw = $this->curlWrapper->curlPostBodyData($url, $postParams, false);
			$renewCheckoutResponse = json_decode($renewCheckoutResponseRaw);
			if ($renewCheckoutResponse == null){
				//We didn't get valid JSON back
				$result['message'] = "We could not renew your item.  Received an invalid response from the server.";
			}else{
				foreach ($renewCheckoutResponse->renewLoanInfos as $renewInfo){
					if ($renewInfo->success){
						$result['success'] = 'true';
						$result['message'] = "Your item was renewed successfully.  It is now due {$renewInfo->dateString}.";
					}else{
						$result['message'] = "Your item could not be renewed online.";
					}
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	/**
	 * @param $username
	 * @param $password
	 * @return boolean
	 */
	protected function loginPatronToLSS($username, $password) {
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$url = $this->getVendorOpacUrl() . '/login?rememberMe=false&_=' . time() * 1000;
		$postParams = array(
			'password' => $password,
			'pin' => $password,
			'rememberMe' => 'false',
			'username' => $username,
		);
		$loginResponse = $this->curlWrapper->curlPostBodyData($url, $postParams);
		if (strlen($loginResponse) > 0){
			$decodedResponse = json_decode($loginResponse);
			if ($decodedResponse){
				$loginSucceeded = $decodedResponse->success == 'true';
			}else{
				$loginSucceeded = false;
			}
		}else{
			global $logger;
			$logger->log("Unable to connect to LSS.  Received $loginResponse", Logger::LOG_WARNING);
			$loginSucceeded = false;
		}

		return $loginSucceeded;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron      The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getHolds($patron) : array {
		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);

		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/requests/0/20/Status?_=' . time() * 1000;
			$holdInfoRaw = $this->curlWrapper->curlGetPage($url);
			$holdInfo = json_decode($holdInfoRaw);

			$indexingProfile = $this->getIndexingProfile();
			foreach ($holdInfo->holds as $hold){
				$curHold= array();
				$bibId = $hold->bibliographicId;
				$curHold['id'] = $bibId;
				$curHold['holdSource'] = 'ILS';
				$curHold['itemId'] = $hold->itemId;
				$curHold['cancelId'] = $hold->holdNumber;
				$curHold['position'] = $hold->holdQueueLength;
				$curHold['recordId'] = $bibId;
				$curHold['shortId'] = $bibId;
				$curHold['title'] = $hold->title;
				$curHold['author'] = $hold->author;
				$curHold['locationId'] = $hold->holdPickupBranchId;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $hold->holdPickupBranchId;
				if ($curPickupBranch->find(true)) {
					$curHold['currentPickupId'] = $curPickupBranch->locationId;
					$curHold['currentPickupName'] = $curPickupBranch->displayName;
					$curHold['location'] = $curPickupBranch->displayName;
				}
				//$curHold['locationId'] = $matches[1];
				$curHold['locationUpdateable'] = false;
				$curHold['currentPickupName'] = $hold->holdPickupBranch;

				if ($indexingProfile){
					$curHold['status'] = $indexingProfile->translate('item_status', $hold->status);
				}else{
					$curHold['status'] = $hold->status;
				}

				//$expireDate = (string)$hold->expireDate;
				//$curHold['expire'] = strtotime($expireDate);
				$curHold['reactivate'] = $hold->suspendUntilDateString;

				//MDN - it looks like holdCancelable is not accurate, setting to true always
				//$curHold['cancelable'] = $hold->holdCancelable;
				$curHold['cancelable'] = true;
				$curHold['frozen'] = $hold->suspendUntilDate != null;
				if ($curHold['frozen']){
					$curHold['reactivateTime'] = $hold->suspendUntilDate;
				}
				//Although LSS interface shows this is possible, we haven't been able to make it work in the
				//LSS OPAC, setting to false always
				$curHold['canFreeze'] = false;

				$curHold['sortTitle'] = $hold->title;
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ":" . $bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle'] = $recordDriver->getSortableTitle();
					$curHold['format'] = $recordDriver->getFormat();
					$curHold['isbn'] = $recordDriver->getCleanISBN();
					$curHold['upc'] = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}
				$curHold['link'] = $recordDriver->getLinkUrl();
				$curHold['user'] = $patron->getNameAndLibraryLabel();

				//TODO: Determine the status of available holds
				if (!isset($hold->status) || $hold->status == 'PE' || $hold->status == 'T'){
					$holds['unavailable'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId']. $curHold['user']] = $curHold;
				}else{
					$holds['available'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId']. $curHold['user']] = $curHold;
				}
			}
		}
		return $holds;
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
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be placed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$url = $this->getVendorOpacUrl() . '/requests/true?_=' . time() * 1000;
			//LSS allows multiple holds to be places at once, but we will only do one at a time for now.
			$postParams[] = array(
				'bibliographicId' => $recordId,
				'downloadable' => false,
				'interfaceType' => 'PAC',
				'pickupBranchId' => $pickupBranch,
				'titleLevelHold' => 'true'
			);
			$placeHoldResponseRaw = $this->curlWrapper->curlPostBodyData($url, $postParams);
			$placeHoldResponse = json_decode($placeHoldResponseRaw);

			foreach ($placeHoldResponse->placeHoldInfos as $holdResponse){
				if ($holdResponse->success){
					$result['success'] = true;
					$result['message'] = translate(['text'=>"Your hold was placed successfully.",'isPublicFacing'=>true]);
				}else{
					$result['message'] = 'Sorry, your hold could not be placed.  ' . htmlentities(translate(['text'=>$holdResponse->message,'isPublicFacing'=>true]));
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
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
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null){
		return array('success' => false, 'message' => 'Unable to place Item level holds in Library.Solution at this time');
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param   User    $patron     The User to cancel the hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $cancelId   Information about the hold to be cancelled
	 * @param   boolean $isIll      If the hold is an ILL hold
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false) : array{
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be cancelled.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//for lss we need additional information about the hold
			$url = $this->getVendorOpacUrl() . '/requests/0/20/Status?_=' . time() * 1000;
			$holdInfoRaw = $this->curlWrapper->curlGetPage($url);
			$holdInfo = json_decode($holdInfoRaw);

			$selectedHold = null;
			foreach ($holdInfo->holds as $hold) {
				if ($hold->holdNumber == $cancelId){
					$selectedHold = $hold;
				}
			}

			$url = $this->getVendorOpacUrl() . '/requests/cancel?_=' . time() * 1000;
			$postParams = '{"cancelHoldInfos":"[{\"desireNumber\":\"' . $cancelId. '\",\"success\":false,\"holdQueueLength\":\"' . $selectedHold->holdQueueLength . '\",\"bibliographicId\":\"' . $recordId. '\",\"whichBranch\":' . $selectedHold->holdPickupBranchId . ',\"status\":\"' . $selectedHold->status . '\",\"downloadable\":false}]"}';

			$responseRaw = $this->curlWrapper->curlPostBodyData($url, $postParams, false);
			$response = json_decode($responseRaw);

			foreach ($response->cancelHoldInfos as $itemResponse){
				if ($itemResponse->success){
					$result['success'] = true;
					$result['message'] = 'Your hold was cancelled successfully.';
				}else{
					$result['message'] = 'Sorry, your hold could not be cancelled.';
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) : array {
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be frozen.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$url = $this->getVendorOpacUrl() . '/requests/suspend?_=' . time() * 1000;
			$formattedReactivationDate = $dateToReactivate;
			$postParams = '{"suspendHoldInfos":"[{\"desireNumber\":\"' . $itemToFreezeId . '\",\"success\":false,\"suspendDate\":\"' . $formattedReactivationDate . '\",\"queuePosition\":\"1\",\"bibliographicId\":\"' . $recordId . '\",\"pickupBranchId\":100,\"downloadable\":false}]"}';
			$responseRaw = $this->curlWrapper->curlPostBodyData($url, $postParams, false);
			$response = json_decode($responseRaw);

			foreach ($response->suspendHoldInfos as $itemResponse){
				if ($itemResponse->success){
					$result['success'] = true;
					$result['message'] = translate(['text'=>'Your hold was frozen successfully.', 'isPublicFacing'=> true]);
				}else{
					$result['message'] = translate(['text'=>'Sorry, your hold could not be frozen.', 'isPublicFacing'=> true]);
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId) : array {
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be thawed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$result['message'] = 'This functionality is currently unimplemented';
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation) : array {
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, the pickup location for your hold could not be changed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//Not possible in LSS
			$result['message'] = 'This functionality is currently unimplemented';
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	/**
	 * @param User $patron patron to get fines for
	 * @return array  Array of messages
	 */
	function getFines($patron, $includeMessages = false) : array {
		$fines = array();

		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//Load transactions from LSS
			//TODO: Verify that this will load more than 10000 fines
			$url = $this->getVendorOpacUrl() . '/fees/0/10000/OutDate?_=' . time() * 1000;
			$feeInfoRaw = $this->curlWrapper->curlGetPage($url);
			$feeInfo = json_decode($feeInfoRaw);

			foreach ($feeInfo->fees as $fee){
				$fines[] = array(
					'reason' => $fee->title,
					'message' => $fee->feeComment,
					'amount' => '$' . sprintf('%0.2f', $fee->fee / 100),
				);
			}
		}

		return $fines;
	}

	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade) : array
	{
		return [
			'success' => false,
			'messages' => [
				'Cannot update patron information in Library.Solution'
			]
		];
	}
}