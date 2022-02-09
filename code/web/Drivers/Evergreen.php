<?php
require_once ROOT_DIR . '/Drivers/SIP2Driver.php';

class Evergreen extends SIP2Driver
{
	//Caching of sessionIds by patron for performance
	private static $accessTokensForUsers = array();

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	private $ahrFields = ['Status', 'Transit', 'Capture_Date_Time', 'Currently_Targeted_Copy', 'Notify_by_Email', 'Hold_Expire_Date_Time', 'Fulfilling_Library', 'Fulfilling_Staff', 'Fulfillment_Date_Time', 'Hold_Type', 'Holdable_Formats', 'Hold_ID', 'Notifications_Phone_Number', 'Notifications_SMS_Number', 'Notifications_SMS_Carrier', 'Pickup_Library', 'Last_Targeting_Date_Time', 'Requesting_Library', 'Request_Date_Time', 'Requesting_User', 'Item_Selection_Depth', 'Selection_Locus', 'Target_Object_ID', 'Hold_User', 'Hold_Cancel_Date_Time', 'Notify_Time', 'Notify_Count', 'Notifications', 'Bib_Record_link', 'Eligible_Copies', 'Currently_Frozen', 'Activation_Date', 'Shelf_Time', 'Cancelation_cause', 'Cancelation_note', 'Top_of_Queue', 'Is_Mint_Condition', 'Shelf_Expire_Time', 'Notes', 'Current_Shelf_Lib', 'Behind_Desk', 'Acquisition_Request', 'Hopeless_Date'];
	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Evergreen Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->apiCurlWrapper = null;
	}

	/**
	 * @inheritDoc
	 */
	public function getCheckouts(User $patron)
	{
		// TODO: Implement getCheckouts() method.
	}

	/**
	 * @inheritDoc
	 */
	public function hasFastRenewAll()
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function renewAll(User $patron)
	{
		// TODO: Implement renewAll() method.
	}

	/**
	 * @inheritDoc
	 */
	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	/**
	 * @inheritDoc
	 */
	function cancelHold(User $patron, $recordId, $cancelId = null)
	{
		// TODO: Implement cancelHold() method.
	}

	/**
	 * @inheritDoc
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		// TODO: Implement placeItemHold() method.
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		// TODO: Implement freezeHold() method.
	}

	function thawHold(User $patron, $recordId, $itemToThawId)
	{
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		// TODO: Implement changeHoldPickupLocation() method.
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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null){
			//Get a list of holds
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$params = [
				'service' => 'open-ils.circ',
				'method' => 'open-ils.circ.holds.retrieve',
				'param' => json_encode($authToken),
			];
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				foreach ($apiResponse->payload[0] as $payload) {
					if ($payload->__c == 'ahr') { //class
						$holdInfo = $payload->__p; //ahr object

						$holdInfo = $this->mapEvergreenFields($holdInfo, $this->ahrFields);

						$curHold = new Hold();
						$curHold->userId = $patron->id;
						$curHold->type = 'ils';
						$curHold->source = $this->getIndexingProfile()->name;

						$curHold->sourceId = $holdInfo['Hold_ID'];
						$curHold->recordId = $holdInfo['Target_Object_ID'];
						$curHold->cancelId = $holdInfo['Hold_ID'];

						//TODO: Validate if these are accurate
						$curHold->locationUpdateable = true;
						$curHold->cancelable = true;


						if ($holdInfo['Currently_Frozen'] == 't'){
							$curHold->frozen = true;
							$curHold->status = "Frozen";
							$curHold->canFreeze = true;
							if ($holdInfo['ActivationDate'] != null) {
								$curHold->status .= ' until ' . date("m/d/Y", strtotime($holdInfo['ActivationDate']));
							}
							$curHold->locationUpdateable = true;
						}elseif (!empty($holdInfo['Transit'])){
							$curHold->status = 'In Transit';
						}elseif (!empty($holdInfo['Capture_Date_Time'])){
							$curHold->cancelable = false;
							$curHold->status = "Ready to Pickup";
						}else{
							$curHold->status = "Pending";
							$curHold->canFreeze = $patron->getHomeLibrary()->allowFreezeHolds;
							$curHold->locationUpdateable = true;
						}

						$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . $curHold->recordId);
						if ($recordDriver->isValid()){
							$curHold->updateFromRecordDriver($recordDriver);
						}else{
							//Fetch title from supercat
							$titleInfo = $this->getBibFromSuperCat($curHold->recordId);
						}

						if (!$curHold->available) {
							$holds['unavailable'][$curHold->source . $curHold->cancelId. $curHold->userId] = $curHold;
						} else {
							$holds['available'][$curHold->source . $curHold->cancelId. $curHold->userId] = $curHold;
						}
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

		$apiToken = $this->getAPIAuthToken($patron);
		if ($apiToken != null){
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$params = [
				'service' => 'open-ils.circ',
				'method' => 'open-ils.circ_holds.test_and_create.batch',
				'param' => [
					"patronid" => $patron->username,
					"pickup_lib" => $pickupBranch,
					"hold_type" => 'T',
//					"email_notify" => $patron->email,
//					"expire_time" to expireTime,
//					"frozen" to suspendHold
				]
			];

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
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
		$sessionInfo = $this->validatePatronAndGetAuthToken($patron->getBarcode(), $patron->getPasswordOrPin());
		if ($sessionInfo['userValid']){
			return $sessionInfo['authToken'];
		}
		return null;
	}

	public function getFines(User $patron, $includeMessages = false)
	{
		// TODO: Implement getFines() method.
	}

	private function validatePatronAndGetAuthToken(string $username, string $password)
	{
		if (array_key_exists($username, Evergreen::$accessTokensForUsers)){
			return Evergreen::$accessTokensForUsers[$username];
		}else {
			$session = array(
				'userValid' => false,
				'authToken' => false,
			);

			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$params = [
				'service' => 'open-ils.auth',
				'method' => 'open-ils.auth.login',
				'param' => json_encode([
					'password' => (string)$password,
					'type' => 'persist',
					'org' => null,
					'identifier' => (string)$username,
				]),
			];
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if ($apiResponse->payload[0]->ilsevent == 0){
					//Success!
					$session['userValid'] = true;
					$session['authToken'] = $apiResponse->payload[0]->payload->authtoken;
				}
			}

			Evergreen::$accessTokensForUsers[$username] = $session;
			return $session;
		}
	}
	private function fetchSession($authToken){
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers  = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$params = [
			'service' => 'open-ils.auth',
			'method' => 'open-ils.auth.session.retrieve',
			'param' => json_encode($authToken),
		];
		$getSessionResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

		if ($this->apiCurlWrapper->getResponseCode() == 200){
			$getSessionResponse = json_decode($getSessionResponse);
			if ($getSessionResponse->payload[0]->__c == 'au'){ //class
				return $getSessionResponse->payload[0]->__p; //payload
			}
		}
		return null;
	}

	private function loadPatronBasicData(string $username, string $password, $session, $authToken)
	{
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$params = [
			'service' => 'open-ils.actor',
			'method' => 'open-ils.actor.user.fleshed.retrieve',
		];
		$params = http_build_query($params) . '&param=' . json_encode($authToken);
		$headers  = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$getUserResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

		if ($this->apiCurlWrapper->getResponseCode() == 200){

		}else{
			return null;
		}

	}

	private function mapEvergreenFields($rawResult, array $ahrFields)
	{
		$mappedResult = [];
		foreach ($ahrFields as $position => $label){
			$mappedResult[$label] = $rawResult[$position];
		}
		return $mappedResult;
	}

	private function getBibFromSuperCat($recordId)
	{
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/opac/extras/supercat/retrieve/atom/record/' . $recordId;
		$superCatResult = $this->apiCurlWrapper->curlGetPage($evergreenUrl);
		if ($this->apiCurlWrapper->getResponseCode() == 200){
			return simplexml_load_string($evergreenUrl);
		}else{
			return null;
		}
	}
}