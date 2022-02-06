<?php

class Evergreen extends AbstractIlsDriver
{
	//Caching of sessionIds by patron for performance
	private static $accessTokensForUsers = array();

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	private $ahrFields = [
		"status",
		"transit",
		"capture_time",
		"current_copy",
		"email_notify",
		"expire_time",
		"fulfillment_lib",
		"fulfillment_staff",
		"fulfillment_time",
		"hold_type",
		"holdable_formats",
		"id",
		"phone_notify",
		"sms_notify",
		"sms_carrier",
		"pickup_lib",
		"prev_check_time",
		"request_lib",
		"request_time",
		"requestor",
		"selection_depth",
		"selection_ou",
		"target",
		"usr",
		"cancel_time",
		"notify_time",
		"notify_count",
		"notifications",
		"bib_rec",
		"eligible_copies",
		"frozen",
		"thaw_date",
		"shelf_time",
		"cancel_cause",
		"cancel_note",
		"cut_in_line",
		"mint_condition",
		"shelf_expire_time",
		"notes",
		"current_shelf_lib",
		"behind_desk",
		"acq_request",
		"hopeless_date",
	];
	private $auFields = [
		"addresses",
		"cards",
		"checkouts",
		"hold_requests",
		"permissions",
		"settings",
		"standing_penalties",
		"stat_cat_entries",
		"survey_responses",
		"waiver_entries",
		"ws_ou",
		"wsid",
		"active",
		"alert_message",
		"barred",
		"billing_address",
		"card",
		"claims_returned_count",
		"claims_never_checked_out_count",
		"create_date",
		"credit_forward_balance",
		"day_phone",
		"dob",
		"email",
		"evening_phone",
		"expire_date",
		"family_name",
		"first_given_name",
		"home_ou",
		"id",
		"ident_type",
		"ident_type2",
		"ident_value",
		"ident_value2",
		"last_xact_id",
		"mailing_address",
		"master_account",
		"net_access_level",
		"other_phone",
		"passwd",
		"photo_url",
		"prefix",
		"profile",
		"second_given_name",
		"standing",
		"suffix",
		"super_user",
		"usrgroup",
		"usrname",
		"alias",
		"juvenile",
		"last_update_time",
		"pref_prefix",
		"pref_first_given_name",
		"pref_second_given_name",
		"pref_family_name",
		"pref_suffix",
		"guardian",
		"name_keywords",
		"name_kw_tsvector",
		"groups",
		"deleted",
		"notes",
		"demographic",
		"billable_transactions",
		"money_summary",
		"open_billable_transactions_summary",
		"checkins",
		"performed_circulations",
		"fund_alloc_pcts",
		"reservations",
		"usr_activity",
		"usr_work_ou_map",
	];
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
		$result = [
			'success' => false,
			'message' => translate(['text'=>"The hold could not be cancelled.", 'isPublicFacing'=>true]),
			'api' => [
				'title' => translate(['text'=>'Hold not cancelled', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>'The hold could not be cancelled.', 'isPublicFacing'=>true])
			]
		];
		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.cancel';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode([(int)$cancelId]);
			$request .= '&param=';
			$request .= '&param=' . json_encode("Hold cancelled in Aspen Discovery");

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$result['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$result['message'] = $apiResponse->debug;
				}elseif ($apiResponse->payload[0] == 1 ){
					$result['message'] = translate(['text' => "The hold has been cancelled.", 'isPublicFacing' => true]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate(['text' => 'Hold cancelled', 'isPublicFacing' => true]);
					$result['api']['message'] = translate(['text' => 'Your hold has been cancelled,', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}else{
			$result['message'] = translate(['text'=>'Could not connect to the circulation system', 'isPublicFacing'=>true]);
		}
		return $result;
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

						$curHold->sourceId = $holdInfo['id'];
						$curHold->recordId = $holdInfo['target'];
						$curHold->cancelId = $holdInfo['id'];

						//TODO: Validate if these are accurate
						$curHold->locationUpdateable = true;
						$curHold->cancelable = true;


						if ($holdInfo['frozen'] == 't'){
							$curHold->frozen = true;
							$curHold->status = "Frozen";
							$curHold->canFreeze = true;
							if ($holdInfo['thaw_date'] != null) {
								$curHold->status .= ' until ' . date("m/d/Y", strtotime($holdInfo['thaw_date']));
							}
							$curHold->locationUpdateable = true;
						}elseif (!empty($holdInfo['transit'])){
							$curHold->status = 'In Transit';
						}elseif (!empty($holdInfo['capture_time'])){
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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null){
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			//Translate to numeric location id
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)){
				$pickupBranch = $location->historicCode;
			}
			if ($cancelDate == null){
				global $library;
				if ($library->defaultNotNeededAfterDays == 0){
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$cancelDate = date( DateTime::ISO8601, $sixMonthsFromNow);
				}else{
					//Default to a date 6 months (half a year) in the future.
					$nnaDate = time() + $library->defaultNotNeededAfterDays * 24 * 60 * 60;
					$cancelDate = date( DateTime::ISO8601, $nnaDate);
				}
			}
			$namedParams = [
				'patronid' => (int)$patron->username,
				"pickup_lib" => (int)$pickupBranch,
				"hold_type" => 'T',
//				"email_notify" => $patron->email,
//				"request_lib" =>  (int)$pickupBranch,
//				"request_time" => date( DateTime::ISO8601),
//				"expire_time" => $cancelDate,
//				"frozen" => 'f'
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.holds.test_and_create.batch';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode($namedParams);
			$request .= '&param=' . json_encode([(int)$recordId]);

			//First check to see if the hold can be placed
			$requestB = 'service=open-ils.circ&method=open-ils.circ.title_hold.is_possible';
			$requestB .= '&param=' . json_encode($authToken);
			$namedParamsB = [
				'patronid' => (int)$patron->username,
				"pickup_lib" => (int)$pickupBranch,
				"hold_type" => 'T',
				"titleid" => (int)$recordId
			];
			$requestB .= '&param=' . json_encode($namedParamsB);

			$apiResponseB = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $requestB);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponseB = json_decode($apiResponseB);
				if ($apiResponseB->payload[0]->success == 0){
					$hold_result['message'] = "Holds cannot be placed on this title";
					return $hold_result;
				}
			}

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$hold_result['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$hold_result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$hold_result['message'] = $apiResponse->debug;
				}elseif (isset($apiResponse->payload[0]->result) &&$apiResponse->payload[0]->result > 0 ){
					$hold_result['message'] = translate(['text' => "Your hold was placed successfully.", 'isPublicFacing' => true]);
					$hold_result['success'] = true;

					// Result for API or app use
					$hold_result['api']['title'] = translate(['text' => 'Hold placed successfully', 'isPublicFacing' => true]);
					$hold_result['api']['message'] = translate(['text' => 'Your hold was placed successfully.', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
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

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//return parent::patronLogin($username, $password, $validatedViaSSO);
		$session = $this->validatePatronAndGetAuthToken($username, $password);
		if ($session['userValid']){
			$sessionData = $this->fetchSession($session['authToken']);
			if ($sessionData != null){
				$userData = $this->mapEvergreenFields($sessionData, $this->auFields);

				$user = $this->loadPatronInformation($userData, $username, $password);

				$user->password = $password;

				return $user;
			}else{
				return null;
			}
		}else{
			return null;
		}
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

	private function mapEvergreenFields($rawResult, array $ahrFields)
	{
		$mappedResult = [];
		foreach ($ahrFields as $position => $label){
			if (isset($rawResult[$position])){
				$mappedResult[$label] = $rawResult[$position];
			}else{
				$mappedResult[$label] = null;
			}

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