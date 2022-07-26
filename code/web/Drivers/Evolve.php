<?php

class Evolve extends AbstractIlsDriver
{
	//Caching of sessionIds by patron for performance
	private static $accessTokensForUsers = [];

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created evolve Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->apiCurlWrapper = null;
	}

	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 * @return Checkout[]        Array of the patron's transactions on success
	 * @access public
	 */
	public function getCheckouts(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = array();

		$sessionInfo = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
		if ($sessionInfo['userValid']){
			$evolveUrl = $this->accountProfile->patronApiUrl . '/Holding/Token=' . $sessionInfo['accessToken'] . '|OnLoan=YES';
			$response = $this->apiCurlWrapper->curlGetPage($evolveUrl);
			ExternalRequestLogEntry::logRequest('evolve.getCheckouts', 'GET', $this->getWebServiceURL() . $evolveUrl, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200){
				$jsonData = json_decode($response);
				foreach ($jsonData as $index => $itemOut){
					$curCheckout = new Checkout();
					$curCheckout->type = 'ils';
					$curCheckout->source = $this->getIndexingProfile()->name;

					$curCheckout->recordId = $itemOut->ID;
					$curCheckout->itemId = (int)$itemOut->HoldingID;

					$curCheckout->dueDate = strtotime($itemOut->DueDate);
					//$curCheckout->checkoutDate = strtotime($itemOut->CheckOutDate);

					$curCheckout->renewCount = (int)$itemOut->CircRenewed;
					$curCheckout->canRenew = true;

					$curCheckout->renewalId = (int)$itemOut->HoldingID;
					$curCheckout->title = $itemOut->Title;
					$curCheckout->author = $itemOut->Author;
					$curCheckout->formats = [$itemOut->Form];
					$curCheckout->callNumber = $itemOut->CallNumber;

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($curCheckout->recordId);
					if ($recordDriver->isValid()){
						$curCheckout->updateFromRecordDriver($recordDriver);
					}

					$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$index";
					$checkedOutTitles[$sortKey] = $curCheckout;
				}
			}
		}


		return $checkedOutTitles;
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll()
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function renewAll(User $patron)
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$result = [
			'itemId' => $itemId,
			'success' => false,
			'message' => translate(['text' => 'Unknown Error renewing checkout', 'isPublicFacing' => true]),
			'api' => [
				'title' => translate(['text'=>'Checkout could not be renewed', 'isPublicFacing'=>true]),
				'message' => translate(['text' => 'Unknown Error renewing checkout', 'isPublicFacing' => true]),
			]
		];
		return $result;
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @param string $cancelId Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false)
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The hold could not be cancelled.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold not cancelled', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The hold could not be cancelled.', 'isPublicFacing'=>true])
			]
		];
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch) {
		return $this->placeItemHold($patron, $recordId, $volumeId, $pickupBranch);
	}


	/**
	 * @inheritDoc
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		$hold_result = [
			'success' => false,
			'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true]),
			'api' => [
				'title' => translate(['text' => 'Unable to place hold', 'isPublicFacing'=> true]),
				'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true])
			],
		];

		return $hold_result;
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The hold could not be frozen.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold not frozen', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The hold could not be frozen.', 'isPublicFacing'=>true])
			]
		];

		return $result;
	}

	/**
	 * @param User $patron
	 * @param string|int $recordId
	 * @param string|int $itemToThawId
	 *
	 * @return array
	 */
	function thawHold(User $patron, $recordId, $itemToThawId)
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The hold could not be thawed.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold not thawed', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The hold could not be thawed.', 'isPublicFacing'=>true])
			]
		];

		return $result;
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The pickup location for the hold could not be changed.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold location not changed', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The pickup location for the hold could not be changed.', 'isPublicFacing'=>true])
			]
		];

		return $result;
	}

	function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade)
	{
		// TODO: Implement updatePatronInfo() method.
	}

	public function hasNativeReadingHistory()
	{
		// TODO: Implement hasNativeReadingHistory() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getHolds(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$sessionInfo = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
		if ($sessionInfo['userValid']){
			$evolveUrl = $this->accountProfile->patronApiUrl . '/Holding/Token=' . $sessionInfo['accessToken'] . '|OnReserve=YES';
			$response = $this->apiCurlWrapper->curlGetPage($evolveUrl);
			ExternalRequestLogEntry::logRequest('evolve.getHolds', 'GET', $this->getWebServiceURL() . $evolveUrl, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200){
				$holdsList = json_decode($response);
				foreach ($holdsList as $holdInfo){
					$curHold = new Hold();
					$curHold->userId = $patron->id;
					$curHold->type = 'ils';
					$curHold->source = $this->getIndexingProfile()->name;
					$curHold->sourceId = $holdInfo->ID;
					$curHold->recordId = $holdInfo->ID;
					$curHold->cancelId = $holdInfo->ID;
					$curHold->frozen = false;
					$curHold->locationUpdateable = true;
					$curHold->cancelable = true;
					$curHold->status = $holdInfo->ReserveStatus;
					if ($holdInfo->ReserveStatus == 'Hold Shelf'){
						$isAvailable = true;
					}else{
						$isAvailable = false;
					}
					//TODO: Hold positions within hold queue
					if (!$isAvailable) {
						if (isset($holdInfo->Priority)) {
							$curHold->position = $holdInfo->Priority;
						}
					}
					$curHold->canFreeze = false;
					$curHold->title = $holdInfo->Title;
					$curHold->author = $holdInfo->Author;
					$curHold->callNumber = $holdInfo->CallNumber;
					$curHold->format = $holdInfo->Form;
					//TODO: Pickup location id
					$curHold->pickupLocationName = $holdInfo->Location;

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($curHold->recordId);
					if ($recordDriver->isValid()){
						$curHold->updateFromRecordDriver($recordDriver);
					}

					$curHold->available = $isAvailable;
					if ($curHold->available) {
						$holds['available'][] = $curHold;
					} else {
						$holds['unavailable'][] = $curHold;
					}
				}
			}
		}
		return $holds;
	}

	/**
	 * @inheritDoc
	 */
	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		$hold_result = [
			'success' => false,
			'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true]),
			'api' => [
				'title' => translate(['text' => 'Unable to place hold', 'isPublicFacing'=> true]),
				'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true])
			],
		];

		$sessionInfo = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
		if ($sessionInfo['userValid']) {
			$this->apiCurlWrapper->addCustomHeaders([
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
			], true);

			$params = new stdClass();
			$params->Token = $sessionInfo['accessToken'];
			$params->CatalogItem  = str_replace('CA010', '', $recordId);
			$params->Action = "Create";
			$postParams = json_encode($params);
			$postParams = 'Token=' .  $sessionInfo['accessToken'] . '|CatalogItem=' . $recordId . '|Action=Create';

			//$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			$response = $this->apiCurlWrapper->curlPostBodyData($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			ExternalRequestLogEntry::logRequest('evolve.placeHold', 'POST', $this->accountProfile->patronApiUrl . '/AccountReserve', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
				}
			}
		}

		return $hold_result;
	}

	/**
	 * @inheritDoc
	 */
	public function getAPIAuthToken(User $patron)
	{
		//Remove any spaces from the barcode

		return null;
	}

	public function getFines(User $patron, $includeMessages = false)
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)){
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );

		$fines = [];
		$sessionInfo = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
		if ($sessionInfo['userValid']) {
			$evolveUrl = $this->accountProfile->patronApiUrl . '/AccountFinancial/Token=' . $sessionInfo['accessToken'];
			$response = $this->apiCurlWrapper->curlGetPage($evolveUrl);
			ExternalRequestLogEntry::logRequest('evolve.getFines', 'GET', $this->getWebServiceURL() . $evolveUrl, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$finesRows = json_decode($response);
				foreach ($finesRows as $index => $fineRow){
					$curFine = [
						'fineId' => $index,
						'date' => strtotime($fineRow->ItemDate),
						'type' => $fineRow->ItemType,
						'reason' => $fineRow->ItemType,
						'message' => $fineRow->ItemComment,
						'amountVal' => floatval(str_replace('$', '', $fineRow->ItemAmount)),
						'amountOutstandingVal' => floatval(str_replace('$', '', $fineRow->UnpaidAmount)),
						'amount' => $fineRow->ItemAmount,
						'amountOutstanding' => $fineRow->UnpaidAmount,
					];
					$fines[] = $curFine;
				}
			}
		}

		return $fines;
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
			$sessionInfo = $this->loginViaWebService($barcode, $password);
			if ($sessionInfo instanceof AspenError){
				return $sessionInfo;
			}

			if ($sessionInfo['userValid']){
				//Load user data
				return $this->loadPatronBasicData($username, $password, $sessionInfo);
			}
		}
		return null;
	}

	private function loadPatronBasicData(string $patronBarcode, string $password, array $sessionInfo)
	{
		$accountDetails = $this->getAccountDetails($sessionInfo['accessToken']);
		if ($accountDetails != null){
			$userExistsInDB = false;
			$user = new User();
			$user->source = $this->accountProfile->name;
			if (empty($sessionInfo['patronId'])) {
				$user->username = $patronBarcode;
			}else{
				$user->username = $sessionInfo['patronId'];
			}
			if ($user->find(true)) {
				$userExistsInDB = true;
			}
			$user->cat_username = $patronBarcode;
			if (!empty($password)) {
				$user->cat_password = $password;
			}

			$forceDisplayNameUpdate = false;
			$name = $accountDetails->Name;
			list($firstName, $lastName) = explode(' ', $name);
			if ($user->firstname != $firstName) {
				$user->firstname = $firstName;
				$forceDisplayNameUpdate = true;
			}
			if ($user->lastname != $lastName) {
				$user->lastname = isset($lastName) ? $lastName : '';
				$forceDisplayNameUpdate = true;
			}
			$user->_fullname = $name;
			if ($forceDisplayNameUpdate) {
				$user->displayName = '';
			}
			$user->phone = $accountDetails->Phone;
			$user->email = $accountDetails->Email;

			//TODO: Figure out home library
			//While we figure this out, we need to set the home library to the main library
			global $library;
			$locationsForLibrary = $library->getLocations();
			$user->homeLocationId = reset($locationsForLibrary)->locationId;

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
	 * @param string $accessToken
	 * @return stdClass|null
	 */
	private function getAccountDetails(string $accessToken){
		$evolveUrl = $this->accountProfile->patronApiUrl . '/AccountDetails/Token=' . $accessToken;
		$response = $this->apiCurlWrapper->curlGetPage($evolveUrl);
		ExternalRequestLogEntry::logRequest('evolve.getAccountDetails', 'GET', $this->getWebServiceURL() . $evolveUrl, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
		if ($response && $this->apiCurlWrapper->getResponseCode() == 200){
			$jsonResponse = json_decode($response);
			return $jsonResponse[0];
		}else{
			return null;
		}
	}

	/**
	 * @param $username
	 * @param $password
	 *
	 * @return array|AspenError
	 */
	protected function loginViaWebService(&$username, $password, $fromMasquerade = false)
	{
		if (array_key_exists($username, Evolve::$accessTokensForUsers)) {
			return Evolve::$accessTokensForUsers[$username];
		} else {
			$session = array(
				'userValid' => false,
				'accessToken' => false,
				'patronId' => false
			);

			//Get the token
			$apiToken = $this->accountProfile->oAuthClientSecret;
			$this->apiCurlWrapper->addCustomHeaders([
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
			], true);

			$params = new stdClass();
			$params->APPTYPE = "CATALOG";
			$params->Token = $apiToken;
			$params->Login = $username;
			$params->Pwd = $password;
			$postParams = json_encode($params);

			$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/Authenticate', $postParams);
			ExternalRequestLogEntry::logRequest('evolve.patronLogin', 'POST', $this->accountProfile->patronApiUrl . '/Authenticate', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
				}
				if ($jsonData->Status == 'Success'){
					$session = array(
						'userValid' => true,
						'accessToken' => $jsonData->LoginToken,
						'patronId' => $jsonData->AccountID
					);
				}else{
					return new AspenError($jsonData->Status . ' ' . $jsonData->Message);
				}
			}

			Evolve::$accessTokensForUsers[$username] = $session;
			return $session;
		}
	}

	private function getStaffUserInfo()
	{

		return null;
	}

	public function findNewUser($patronBarcode)
	{

		//For Evergreen, this can only be called when initiating masquerade
		return false;
	}

	private function loadPatronInformation($userData, $username, $password) {
		$user = new User();
		$user->username = $userData['id'];
		if ($user->find(true)) {
			$insert = false;
		} else {
			$insert = true;
		}

		$firstName = $userData['first_given_name'];
		$lastName = $userData['family_name'];
		$user->_fullname = $lastName . ',' . $firstName;
		$forceDisplayNameUpdate = false;
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		if ($user->lastname != $lastName) {
			$user->lastname = isset($lastName) ? $lastName : '';
			$forceDisplayNameUpdate = true;
		}
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		$user->cat_username = $username;
		$user->cat_password = $password;
		$user->email = $userData['email'];
		if (!empty($userData['day_phone'])){
			$user->phone = $userData['day_phone'];
		}elseif (!empty($userData['evening_phone'])){
			$user->phone = $userData['evening_phone'];
		}elseif (!empty($userData['other_phone'])){
			$user->phone = $userData['other_phone'];
		}

		$user->patronType = $userData['usrgroup'];

		//TODO: Figure out how to parse the address we will need to look it up in web services
		$fullAddress = $userData['mailing_address'];

		if (!empty($userData['expire_date'])){
			$expireTime = $userData['expire_date'];
			$expireTime = strtotime($expireTime);
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

		//Get home location
		$location = new Location();
		$location->historicCode = $userData['home_ou'];

		if ($location->find(true)){
			if ($user->homeLocationId != $location->locationId){
				$user->homeLocationId = $location->locationId;
				$user->pickupLocationId = $user->homeLocationId;
			}
		}else{
			$user->homeLocationId = 0;
		}

		if ($insert) {
			$user->created = date('Y-m-d');
			$user->insert();
		} else {
			$user->update();
		}

		return $user;
	}

	private function validatePatronAndGetAuthToken(string $username, string $password)
	{
		return false;
	}

	public function getAccountSummary(User $patron) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		//Can't use the quick response since it includes eContent.
		$checkouts = $this->getCheckouts($patron);
		$summary->numCheckedOut = count($checkouts);
		$numOverdue = 0;
		foreach ($checkouts as $checkout){
			if ($checkout->isOverdue()){
				$numOverdue++;
			}
		}
		$summary->numOverdue = $numOverdue;

		$holds = $this->getHolds($patron);
		$summary->numAvailableHolds = count($holds['available']);
		$summary->numUnavailableHolds = count($holds['unavailable']);

		//Get additional information
		$fines = $this->getFines($patron);
		$totalfines = 0;
		foreach ($fines as $fine) {
			$totalfines += $fine['amountOutstandingVal'];
		}
		$summary->totalFines = $totalfines;

		return $summary;
	}
}