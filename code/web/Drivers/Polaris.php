<?php


class Polaris extends AbstractIlsDriver
{
	//Caching of sessionIds by patron for performance (also stored within memcache)
	private static $accessTokensForUsers = array();

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Polaris Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->apiCurlWrapper = null;
	}

	public function getAccountSummary(User $user) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $user->id;
		$summary->source = 'ils';

		$basicDataResponse = $this->getBasicDataResponse($user->getBarcode(), $user->getPasswordOrPin());
		if ($basicDataResponse != null){
			//TODO: Account for electronic items
			$summary->numCheckedOut = $basicDataResponse->ItemsOutCount;
			$summary->numOverdue = $basicDataResponse->ItemsOverdueCount;
			$summary->numAvailableHolds = $basicDataResponse->HoldRequestsCurrentCount + $basicDataResponse->HoldRequestsShippedCount;
			$summary->numUnavailableHolds = $basicDataResponse->HoldRequestsHeldCount;
			$summary->totalFines = $basicDataResponse->ChargeBalance;

			$polarisCirculateBlocksUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisCirculateBlocksUrl, 'GET', Polaris::$accessTokensForUsers[$user->getBarcode()]['accessToken']);
			if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
				$circulateBlocksResponse = json_decode($circulateBlocksResponse);
				$expireTime = $this->parsePolarisDate($circulateBlocksResponse->ExpirationDate);
				$summary->expirationDate = $expireTime;
			}
		}

		return $summary;
	}

	/**
	 * @param string $patronBarcode
	 * @param string $password
	 *
	 * @return stdClass|null
	 */
	private function getBasicDataResponse(string $patronBarcode, string $password){
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/basicdata";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patronBarcode, $password));
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			return $jsonResponse->PatronBasicData;
		}else{
			return null;
		}
	}

	public function hasNativeReadingHistory()
	{
		return true;
	}

	public function getCheckouts(User $user)
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = array();

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/itemsout/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($user->getBarcode(), $user->getPasswordOrPin()));
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$itemsOutList = $jsonResponse->PatronItemsOutGetRows;
			foreach ($itemsOutList as $index => $itemOut){
				if ($itemOut->DisplayInPAC == 1 && !$itemOut->ElectronicItem) {
					$curCheckout = new Checkout();
					$curCheckout->type = 'ils';
					$curCheckout->source = $this->getIndexingProfile()->name;
					$curCheckout->sourceId = $itemOut->ItemID;
					$curCheckout->userId = $user->id;

					$curCheckout->recordId = $itemOut->BibID;
					$curCheckout->itemId = $itemOut->ItemID;

					$curCheckout->dueDate = $this->parsePolarisDate($itemOut->DueDate);
					$curCheckout->checkoutDate = $this->parsePolarisDate($itemOut->CheckOutDate);

					$curCheckout->renewCount = $itemOut->RenewalCount;
					$curCheckout->canRenew = $itemOut->RenewalCount < $itemOut->RenewalLimit;
					$curCheckout->renewalId = $itemOut->ItemID;

					$curCheckout->title = $itemOut->Title;
					$curCheckout->author = $itemOut->Author;
					$curCheckout->formats = [$itemOut->FormatDescription];
					$curCheckout->callNumber = $itemOut->CallNumber;

					$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$index";
					$checkedOutTitles[$sortKey] = $curCheckout;
				}
			}
		}
		return $checkedOutTitles;
	}

	public function hasFastRenewAll()
	{
		return true;
	}

	public function renewAll($patron)
	{
		// TODO: Implement renewAll() method.
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	public function getHolds($user)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/holdrequests/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($user->getBarcode(), $user->getPasswordOrPin()));
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$holdsList = $jsonResponse->PatronHoldRequestsGetRows;
			foreach ($holdsList as $index => $holdInfo){
				$curHold = new Hold();
				$curHold->userId = $user->id;
				$curHold->type = 'ils';
				$curHold->source = $this->getIndexingProfile()->name;
				$curHold->sourceId = $holdInfo->HoldRequestID;
				$curHold->recordId = $holdInfo->BibID;
				$curHold->cancelId = $holdInfo->HoldRequestID;
				$curHold->frozen = false;
				$curHold->locationUpdateable = true;
				$isAvailable = false;
				switch ($holdInfo->StatusID){
					case 1:
						//Frozen
						$curHold->status = $holdInfo->StatusDescription;
						$curHold->frozen = true;
						break;
					case 3:
						//Active
						$curHold->status = $holdInfo->StatusDescription;
						break;
					case 4:
						//Pending
						$curHold->status = $holdInfo->StatusDescription;
						break;
					case 5:
						//In Transit
						$curHold->status = $holdInfo->StatusDescription;
						break;
					case 6:
						//Held
						$curHold->status = $holdInfo->StatusDescription;
						$isAvailable = true;
						$curHold->locationUpdateable = false;
						break;
					case 7:
						//Not Supplied
						$curHold->status = $holdInfo->StatusDescription;
						break;
					case 8:
						//Unclaimed - Don't show this one
						$curHold->status = $holdInfo->StatusDescription;
						continue 2;
						break;
					case 9:
						//Expired - Don't show this one
						$curHold->status = $holdInfo->StatusDescription;
						continue 2;
						break;
					case 16:
						//Cancelled - Don't show this one
						$curHold->status = $holdInfo->StatusDescription;
						continue 2;
						break;
				}
				$curHold->canFreeze = $holdInfo->CanSuspend;
				$curHold->title = $holdInfo->Title;
				$curHold->author = $holdInfo->Author;
				$curHold->callNumber = $holdInfo->CallNumber;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $holdInfo->PickupBranchID;
				if ($curPickupBranch->find(true)) {
					$curPickupBranch->fetch();
					$curHold->pickupLocationId = $curPickupBranch->locationId;
					$curHold->pickupLocationName = $curPickupBranch->displayName;
				}else{
					$curHold->pickupLocationName = $holdInfo->PickupBranchName;
				}
				$curHold->expirationDate = $this->parsePolarisDate($holdInfo->PickupBranchName);
				$curHold->position = $holdInfo->QueuePosition;
				$curHold->holdQueueLength = $holdInfo->QueueTotal;
				$curHold->volume = $holdInfo->VolumeNumber;

				$curHold->available = $isAvailable;
				if ($curHold->available) {
					$holds['available'][] = $curHold;
				} else {
					$holds['unavailable'][] = $curHold;
				}
			}
		}

		return $holds;
	}

	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		return $this->placeItemHold($patron, $recordId, null, $pickupBranch, $cancelDate);
	}

	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest";
		$body = new stdClass();
		$body->PatronID = $patron->username;
		$body->BibID = $recordId;
		if (!empty($itemId)){
			$body->ItemBarcode = $itemId;
		}
		$body->PickupOrgID = $pickupBranch;
		//TODO: Volume holds
		//TODO: Need to set the Workstation
		$body->WorkstationID = 1;
		$response = $this->getWebServiceResponse($polarisUrl, 'POST', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()));
		if ($response && $this->lastResponseCode == 200){

		}
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		// TODO: Implement cancelHold() method.
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$barcodesToTest = array();
		$barcodesToTest[] = $username;
		$barcodesToTest[] = preg_replace('/[^a-zA-Z\d]/', '', trim($username));
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library) {
			if ($library->barcodePrefix) {
				if (strpos($username, $library->barcodePrefix) !== 0) {
					//Add the barcode prefix to the barcode
					$barcodesToTest[] = $library->barcodePrefix . $username;
				}
			}
		}

		foreach ($barcodesToTest as $i => $barcode) {
			$sessionInfo = $this->loginViaWebService($username, $password);

			if ($sessionInfo['userValid']){
				//Load user data
				return $this->loadPatronBasicData($username, $password, $sessionInfo['patronId']);
			}
		}
		return null;
	}

	private function loadPatronBasicData(string $patronBarcode, string $password, $patronId)
	{
		$patronBasicData = $this->getBasicDataResponse($patronBarcode, $password);
		if ($patronBasicData != null){
			$userExistsInDB = false;
			$user = new User();
			$user->source = $this->accountProfile->name;
			$user->username = $patronId;
			if ($user->find(true)) {
				$userExistsInDB = true;
			}
			$user->cat_username = $patronBarcode;
			$user->cat_password = $password;

			$forceDisplayNameUpdate = false;
			$firstName = isset($patronBasicData->NameFirst) ? $patronBasicData->NameFirst : '';
			if ($user->firstname != $firstName) {
				$user->firstname = $firstName;
				$forceDisplayNameUpdate = true;
			}
			$lastName = isset($patronBasicData->NameLast) ? $patronBasicData->NameLast : '';
			if ($user->lastname != $lastName) {
				$user->lastname = isset($lastName) ? $lastName : '';
				$forceDisplayNameUpdate = true;
			}
			if ($forceDisplayNameUpdate) {
				$user->displayName = '';
			}
			$user->phone = $patronBasicData->PhoneNumber;
			$user->email = $patronBasicData->EmailAddress;

			//TODO: Load address information

			$polarisCirculateBlocksUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisCirculateBlocksUrl, 'GET', Polaris::$accessTokensForUsers[$patronBarcode]['accessToken']);
			if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
				$circulateBlocksResponse = json_decode($circulateBlocksResponse);
				//Load home library
				$homeBranchCode = strtolower(trim($circulateBlocksResponse->AssignedBranchID));
				//Translate home branch to plain text
				$location = new Location();
				$location->code = $homeBranchCode;
				if (!$location->find(true)) {
					$location = null;
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

				$expireTime = $this->parsePolarisDate($circulateBlocksResponse->ExpirationDate);
				$user->_expires = date('n-j-Y', $expireTime);
				if (!empty($user->_expires)) {
					$timeNow = time();
					$timeToExpire = $expireTime - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60) {
						if ($timeToExpire <= 0) {
							$user->_expired = 1;
						}
						$user->_expireClose = 1;
					}
				}
			}

			if ($userExistsInDB) {
				$user->update();
			} else {
				$user->created = date('Y-m-d');
				$user->insert();
			}
			return $user;
		}else{
			return null;
		}
	}

	/**
	 * @param $username
	 * @param $password
	 *
	 * @return array
	 */
	protected function loginViaWebService($username, $password) : array
	{
		if (array_key_exists($username, Polaris::$accessTokensForUsers)){
			return Polaris::$accessTokensForUsers[$username];
		}else {
			$session = array(
				'userValid' => false,
				'accessToken' => false,
				'patronId' => false
			);
			$authenticationData = new stdClass();
			$authenticationData->Barcode = $username;
			$authenticationData->Password = $password;

			$body = json_encode($authenticationData);
			$authenticationResponseRaw = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/authenticator/patron', 'POST', '', $body);
			if ($authenticationResponseRaw) {
				$authenticationResponse = json_decode($authenticationResponseRaw);
				if ($authenticationResponse->PAPIErrorCode == 0) {
					$accessToken = $authenticationResponse->AccessToken;
					$patronId = $authenticationResponse->PatronID;
					$session = array(
						'userValid' => true,
						'accessToken' => $accessToken,
						'patronId' => $patronId
					);
				} else {
					global $logger;
					$logger->log($authenticationResponse->ErrorMessage, Logger::LOG_ERROR);
					$logger->log(print_r($authenticationResponse, true), Logger::LOG_ERROR);
				}
			} else {
				global $logger;
				$errorMessage = 'Polaris Authentication Error: ' . $this->lastResponseCode;
				$logger->log($errorMessage, Logger::LOG_ERROR);
				$logger->log(print_r($authenticationResponseRaw, true), Logger::LOG_ERROR);
			}
			Polaris::$accessTokensForUsers[$username] = $session;
			return $session;
		}
	}

	private function getAccessToken(string $barcode, string $password)
	{
		//Get the session token for the user
		if (isset(Polaris::$accessTokensForUsers[$barcode])) {
			return Polaris::$accessTokensForUsers[$barcode]['accessToken'];
		} else {
			$sessionInfo = $this->loginViaWebService($barcode, $password);
			return $sessionInfo['accessToken'];
		}
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		// TODO: Implement freezeHold() method.
	}

	function thawHold($patron, $recordId, $itemToThawId)
	{
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		// TODO: Implement changeHoldPickupLocation() method.
	}

	function updatePatronInfo($patron, $canUpdateContactInfo)
	{
		// TODO: Implement updatePatronInfo() method.
	}

	public function getFines($patron, $includeMessages = false)
	{
		// TODO: Implement getFines() method.
	}

	public function getWebServiceResponse($query, $method = 'GET', $patronPassword = '', $body = false){
		// auth has to be in GMT, otherwise use config-level TZ
		$site_config_TZ = date_default_timezone_get();
		date_default_timezone_set('GMT');
		$date = date("D, d M Y H:i:s T");
		date_default_timezone_set($site_config_TZ);

		$url = $this->getWebServiceURL() . $query;

		$signature_text = $method . $url . $date . $patronPassword;
		$signature = base64_encode(
			hash_hmac('sha1', $signature_text, $this->accountProfile->oAuthClientSecret, true)
		);

		$auth_token = "PWS {$this->accountProfile->oAuthClientId}:$signature";
		$this->apiCurlWrapper->addCustomHeaders([
			"Content-type: application/json",
			"Accept: application/json",
			"PolarisDate: $date",
			"Authorization: $auth_token"
		], true);

		if ($method == 'GET'){
			$response = $this->apiCurlWrapper->curlGetPage($url);
		}else{
			$response = $this->apiCurlWrapper->curlSendPage($url, $method, $body);
		}
		$this->lastResponseCode = $this->apiCurlWrapper->getResponseCode();

		return $response;
	}

	private $lastResponseCode;

	private function parsePolarisDate($polarisDate)
	{
		if (preg_match('%/Date\((\d{13})([+-]\d{4})\)/%i', $polarisDate, $matches)) {
			$timestamp = $matches[1] / 1000;
			$timezoneOffset = $matches[2];
			//TODO: Adjust for timezone offset
			return $timestamp;
		} else {
			return 0;
		}
	}

}