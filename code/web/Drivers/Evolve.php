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
	public function getCheckouts(User $patron) : array
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
					$curCheckout->sourceId = $itemOut->ID;
					$curCheckout->userId = $patron->id;

					$curCheckout->recordId = $itemOut->ID;
					$curCheckout->itemId = (int)$itemOut->HoldingID;

					$curCheckout->dueDate = strtotime($itemOut->DueDate);
					//$curCheckout->checkoutDate = strtotime($itemOut->CheckOutDate);

					$curCheckout->renewCount = (int)$itemOut->CircRenewed;
					$curCheckout->canRenew = true;

					$curCheckout->renewalId = $itemOut->ID;
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
	public function hasFastRenewAll() : bool
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
			$params->CatalogItem  = $recordId;
			$params->Action = "Renew Item";
			$postParams = json_encode($params);
//			$postParams = 'Token=' .  $sessionInfo['accessToken'] . '|CatalogItem=' . $recordId . '|Action=Renew Item';

			//$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			$response = $this->apiCurlWrapper->curlPostBodyData($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams, false);
			ExternalRequestLogEntry::logRequest('evolve.renewCheckout', 'POST', $this->accountProfile->patronApiUrl . '/AccountReserve', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
					if ($jsonData->Status == 'Success'){
						$result['success'] = true;
						$result['message'] = translate(['text' => 'Your item was successfully renewed', 'isPublicFacing' => true]);

						// Result for API or app use
						$result['api']['title'] = translate(['text'=>'Title renewed successfully', 'isPublicFacing'=>true]);
						$result['api']['message'] = translate(['text' => 'Your item was renewed', 'isPublicFacing' => true]);
					}else{
						$message = "The item could not be renewed. {$jsonData->Message}";

						$result['itemId'] = $itemIndex;
						$result['success'] = false;
						$result['message'] = $message;

						// Result for API or app use
						$result['api']['title'] = translate(['text'=>'Unable to renew title', 'isPublicFacing'=>true]);
						$result['api']['message'] = $jsonData->Message;
					}
				}
			}
		}

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
	function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false) : array
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The hold could not be cancelled.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold not cancelled', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The hold could not be cancelled.', 'isPublicFacing'=>true])
			]
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
			$params->CatalogItem  = $recordId;
			$params->Action = "Cancel";
			$postParams = json_encode($params);
			//$postParams = 'Token=' .  $sessionInfo['accessToken'] . '|CatalogItem=' . $recordId . '|Action=Cancel';

			//$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			$response = $this->apiCurlWrapper->curlPostBodyData($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams, false);
			ExternalRequestLogEntry::logRequest('evolve.cancelHold', 'POST', $this->accountProfile->patronApiUrl . '/AccountReserve', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
					if ($jsonData->Status == 'Success'){
						$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
						$patron->forceReloadOfHolds();
						$result['success'] = true;
						$result['message'] = translate(['text' => 'The hold has been cancelled.', 'isPublicFacing' => true]);;

						// Result for API or app use
						$result['api']['title'] = translate(['text' => 'Hold cancelled', 'isPublicFacing' => true]);
						$result['api']['message'] = translate(['text' => 'Your hold has been cancelled.', 'isPublicFacing' => true]);
					}else{
						$message = "The hold could not be cancelled. {$jsonData->Message}";
						$result['success'] = false;
						$result['message'] = $message;

						// Result for API or app use
						$result['api']['title'] = translate(['text' => 'Unable to cancel hold', 'isPublicFacing' => true]);
						$result['api']['message'] = $jsonData->Message;
					}
				}
			}
		}

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

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate) : array
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
	function thawHold(User $patron, $recordId, $itemToThawId) : array
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

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation) : array
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The pickup location for the hold could not be changed.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold location not changed', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The pickup location for the hold could not be changed.', 'isPublicFacing'=>true])
			]
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
			$params->CatalogItem  = $itemToUpdateId;
			$params->Location  = $newPickupLocation;
			$params->Action = "Change Location";
			$postParams = json_encode($params);
			//$postParams = 'Token=' .  $sessionInfo['accessToken'] . '|CatalogItem=' . $itemToUpdateId . '|Location=' . $newPickupLocation . '|Action=Change Location';

			//$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			$response = $this->apiCurlWrapper->curlPostBodyData($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams, false);
			ExternalRequestLogEntry::logRequest('evolve.changePickupLocation', 'POST', $this->accountProfile->patronApiUrl . '/AccountReserve', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
					if ($jsonData->Status == 'Success'){
						$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
						$patron->forceReloadOfHolds();
						$result['success'] = true;
						$result['message'] = translate(['text'=>'The pickup location of your hold was changed successfully.', 'isPublicFacing'=>true]);

						// Result for API or app use
						$result['api']['title'] = translate(['text'=>'Pickup location updated', 'isPublicFacing'=>true]);
						$result['api']['message'] = translate(['text'=>'The pickup location of your hold was changed successfully.', 'isPublicFacing'=>true]);
					}else{
						$message = translate(['text'=>'Sorry, the pickup location of your hold could not be changed.', 'isPublicFacing'=>true]) . " {$jsonData->Message}";
						$result['success'] = false;
						$result['message'] = $message;

						// Result for API or app use
						$result['api']['title'] = translate(['text'=>'Unable to update pickup location', 'isPublicFacing'=>true]);
						$result['api']['message'] = $jsonData->Message;
					}
				}
			}
		}

		return $result;
	}

	function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade)
	{
		return [
			'success' => false,
			'messages' => ['Cannot update patron information with this ILS.']
		];
	}

	public function hasNativeReadingHistory() : bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getHolds(User $patron) : array
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

					$pickupLocation = new Location();
					$pickupLocation->code = $holdInfo->Location;
					if ($pickupLocation->find(true)){
						$curHold->pickupLocationId = $pickupLocation->locationId;
						$curHold->pickupLocationName = $pickupLocation->displayName;
					}else{
						$curHold->pickupLocationName = $holdInfo->Location;
					}

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
			$params->CatalogItem  = $recordId;
			$params->Action = "Create";
			$postParams = json_encode($params);
			//$postParams = 'Token=' .  $sessionInfo['accessToken'] . '|CatalogItem=' . $recordId . '|Location=' . $pickupBranch .  '|Action=Create';

			//$response = $this->apiCurlWrapper->curlPostPage($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams);
			$response = $this->apiCurlWrapper->curlPostBodyData($this->accountProfile->patronApiUrl . '/AccountReserve', $postParams, false);
			ExternalRequestLogEntry::logRequest('evolve.placeHold', 'POST', $this->accountProfile->patronApiUrl . '/AccountReserve', $this->apiCurlWrapper->getHeaders(), $postParams, $this->apiCurlWrapper->getResponseCode(), $response, []);
			if ($response && $this->apiCurlWrapper->getResponseCode() == 200) {
				$jsonData = json_decode($response);
				if (is_array($jsonData)){
					$jsonData = $jsonData[0];
					if ($jsonData->Status == 'Success'){
						$hold_result['success'] = true;
						$hold_result['message'] = empty($jsonData->Message) ? translate(['text' => "Your hold was placed successfully.", 'isPublicFacing'=>true]) : $jsonData->Message;
						// Result for API or app use
						$hold_result['api']['title'] = translate(['text'=>'Hold placed successfully', 'isPublicFacing'=>true]);
						$hold_result['api']['message'] = empty($jsonData->Message) ? "" : $jsonData->Message;
						$hold_result['api']['action'] = translate(['text' => 'Go to Holds', 'isPublicFacing'=>true]);;
					}else{
						$hold_result['message'] = $jsonData->Message;
						$hold_result['api']['message'] = $jsonData->Message;
					}
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

	public function getFines(User $patron, $includeMessages = false) : array
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

			//Load home location for the user
			global $library;
			$homeLocation = new Location();
			$homeLocation->code = $accountDetails->Location;
			if ($homeLocation->find(true)){
				$user->homeLocationId = $homeLocation->locationId;
			}else{
				$locationsForLibrary = $library->getLocations();
				$user->homeLocationId = reset($locationsForLibrary)->locationId;
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