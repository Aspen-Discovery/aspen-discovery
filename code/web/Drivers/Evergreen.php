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
	private $circFields = [
		'checkin_lib',
		'checkin_staff',
		'checkin_time',
		'circ_lib',
		'circ_staff',
		'desk_renewal',
		'due_date',
		'duration',
		'duration_rule',
		'fine_interval',
		'id',
		'max_fine',
		'max_fine_rule',
		'opac_renewal',
		'phone_renewal',
		'recurring_fine',
		'recurring_fine_rule',
		'renewal_remaining',
		'grace_period',
		'stop_fines',
		'stop_fines_time',
		'target_copy',
		'usr',
		'xact_finish',
		'xact_start',
		'create_time',
		'workstation',
		'checkin_workstation',
		'checkin_scan_time',
		'parent_circ',
		'billings',
		'payments',
		'billable_transaction',
		'circ_type',
		'billing_total',
		'payment_total',
		'unrecovered',
		'copy_location',
		'aaactsc_entries',
		'aaasc_entries',
		'auto_renewal',
		'auto_renewal_remaining',
	];
	private $mvrFields = [
		'title',
		'author',
		'doc_id',
		'doc_type',
		'pubdate',
		'isbn',
		'publisher',
		'tcn',
		'subject',
		'types_of_resource',
		'call_numbers',
		'edition',
		'online_loc',
		'synopsis',
		'physical_description',
		'toc',
		'copy_count',
		'series',
	];
	private $mousFields = [
		'balance_owed',
		'total_owed',
		'total_paid',
		'usr',
	];
	private $mbtsFields = [
		'balance_owed',
		'id',
		'last_billing_note',
		'last_billing_ts',
		'last_billing_type',
		'last_payment_note',
		'last_payment_ts',
		'last_payment_type',
		'total_owed',
		'total_paid',
		'usr',
		'xact_finish',
		'xact_start',
		'xact_type',
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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			//Get a list of holds
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.checked_out';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . $patron->username;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			$index = 0;
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0])) {
					//Process out titles
					foreach ($apiResponse->payload[0]->out as $checkoutId) {
						$checkout = $this->loadCheckoutData($patron, $checkoutId, $authToken);
						if ($checkout != null){
							$index++;
							$sortKey = "{$checkout->source}_{$checkout->sourceId}_$index";
							$checkedOutTitles[$sortKey] = $checkout;
						}
					}
					//Process overdue titles
					foreach ($apiResponse->payload[0]->overdue as $checkoutId) {
						$checkout = $this->loadCheckoutData($patron, $checkoutId, $authToken);
						if ($checkout != null){
							$index++;
							$sortKey = "{$checkout->source}_{$checkout->sourceId}_$index";
							$checkedOutTitles[$sortKey] = $checkout;
						}
					}
				}
			}
		}

		return $checkedOutTitles;
	}

	private function loadCheckoutData(User $patron, $checkoutId, $authToken) : ?Checkout {
		$curCheckout = null;
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$request = 'service=open-ils.circ&method=open-ils.circ.retrieve';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . $checkoutId;
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0])) {
				$mappedCheckout = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->circFields);
				$curCheckout = new Checkout();
				$curCheckout->type = 'ils';
				$curCheckout->source = $this->getIndexingProfile()->name;

				$curCheckout->sourceId = $mappedCheckout['target_copy'];
				$curCheckout->userId = $patron->id;

				$modsForCopy = $this->getModsForCopy($mappedCheckout['target_copy']);

				$curCheckout->recordId = $modsForCopy['doc_id'];
				$curCheckout->itemId = $mappedCheckout['target_copy'];

				$curCheckout->dueDate = strtotime($mappedCheckout['due_date']);
				$curCheckout->checkoutDate = strtotime($mappedCheckout['create_time']);

				//$curCheckout->renewCount = $itemOut->RenewalCount;
				$curCheckout->canRenew = $mappedCheckout['renewal_remaining'] > 0;
				$curCheckout->maxRenewals = $mappedCheckout['renewal_remaining'];
				$curCheckout->renewalId = $mappedCheckout['target_copy'];
				$curCheckout->renewIndicator = $mappedCheckout['target_copy'];

				$curCheckout->title = $modsForCopy['title'];
				$curCheckout->author = $modsForCopy['author'];
				$curCheckout->callNumber = reset($modsForCopy['call_numbers']);

				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver((string)$curCheckout->recordId);
				if ($recordDriver->isValid()){
					$curCheckout->updateFromRecordDriver($recordDriver);
				}
			}
		}
		return $curCheckout;
	}

	/**
	 * Load mods data based on an item id
	 *
	 * @param int $copyId
	 * @return string[]|null
	 */
	private function getModsForCopy($copyId) {
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$request = 'service=open-ils.search&method=open-ils.search.biblio.mods_from_copy';
		$request .= '&param=' . $copyId;
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0])) {
				$mods = $apiResponse->payload[0]->__p;
				$mods = $this->mapEvergreenFields($mods, $this->mvrFields);
				return $mods;
			}
		}
		return null;
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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$request = 'service=open-ils.circ&method=open-ils.circ.renew';
			$request .= '&param=' . json_encode($authToken);
			$namedParams = [
				'patron_id' => (int)$patron->username,
				"copy_id" => $itemId,
//				'id' => $itemId,
//				'circ' => [
//					'copy_id' => $itemId
//				]
				//"opac_renewal" => 1,
			];
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]->textcode) &&$apiResponse->payload[0]->textcode == 'SUCCESS' ){
					$result['message'] = translate(['text' => "Your title was renewed successfully.", 'isPublicFacing' => true]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate(['text' => 'Title renewed successfully', 'isPublicFacing' => true]);
					$result['api']['message'] = translate(['text' => 'Your title was renewed successfully.', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$result['message'] = $apiResponse->payload[0]->desc;
					$result['api']['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$result['message'] = $apiResponse->debug;
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

		if (strpos($recordId, ':') !== false){
			list(,$recordId) = explode(':', $recordId);
		}

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
				"hold_type" => 'P',
				"email_notify" => $patron->email,
//				"request_lib" =>  (int)$pickupBranch,
//				"request_time" => date( DateTime::ISO8601),
				"expire_time" => $cancelDate,
//				"frozen" => 'f'
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.holds.test_and_create.batch';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode($namedParams);
			$request .= '&param=' . json_encode([(int)$itemId]);

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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$namedParams = [
				'id' => $itemToFreezeId,
				'frozen' => 't'
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$result['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$result['message'] = $apiResponse->debug;
				}elseif ($apiResponse->payload[0] > 0 ){
					$result['message'] = translate(['text' => "Your hold was frozen successfully.", 'isPublicFacing' => true]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate(['text' => 'Hold frozen successfully', 'isPublicFacing' => true]);
					$result['api']['message'] = translate(['text' => 'Your hold was frozen successfully.', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$namedParams = [
				'id' => $itemToThawId,
				'frozen' => 'f'
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$result['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$result['message'] = $apiResponse->debug;
				}elseif ($apiResponse->payload[0] > 0 ){
					$result['message'] = translate(['text' => "Your hold was thawed successfully.", 'isPublicFacing' => true]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate(['text' => 'Hold thawed successfully', 'isPublicFacing' => true]);
					$result['api']['message'] = translate(['text' => 'Your hold was thawed successfully.', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

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

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			//Translate to numeric location id
			$location = new Location();
			$location->code = $newPickupLocation;
			if ($location->find(true)){
				$newPickupLocation = $location->historicCode;
			}

			$namedParams = [
				'id' => $itemToUpdateId,
				'pickup_lib' => (int)$newPickupLocation
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)){
					$result['message'] = $apiResponse->payload[0]->desc;
				}elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)){
					$result['message'] = $apiResponse->payload[0]->result->desc;
				}elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)){
					$result['message'] = $apiResponse->debug;
				}elseif ($apiResponse->payload[0] > 0 ){
					$result['message'] = translate(['text' => "The pickup location for the hold was changed.", 'isPublicFacing' => true]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate(['text' => 'Hold updated', 'isPublicFacing' => true]);
					$result['api']['message'] = translate(['text' => 'The pickup location for the hold was changed.', 'isPublicFacing' => true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

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
						//If the hold_type is P the target will be the part so we will need to lookup the bib record based on the part
						if ($holdInfo['hold_type'] == 'P'){
							require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
							$volumeInfo = new IlsVolumeInfo();
							$volumeInfo->volumeId = $holdInfo['target'];
							if ($volumeInfo->find(true)){
								$curHold->volume = $volumeInfo->displayLabel;
								if (strpos($volumeInfo->recordId, ':') > 0) {
									list (, $curHold->recordId) = explode(':', $volumeInfo->recordId);
								}else{
									$curHold->recordId = $volumeInfo->recordId;
								}
							}
						}else{
							$curHold->recordId = $holdInfo['target'];
						}
						$curHold->cancelId = $holdInfo['id'];

						//TODO: Validate if these are accurate
						$curHold->locationUpdateable = true;
						$curHold->cancelable = true;

						//Get hold location
						$location = new Location();
						$location->historicCode = $holdInfo['pickup_lib'];
						if ($location->find(true)){
							$curHold->pickupLocationId = $location->locationId;
							$curHold->pickupLocationName = $location->displayName;
						}

						if ($holdInfo['frozen'] == 't'){
							$curHold->frozen = true;
							$curHold->status = "Frozen";
							$curHold->canFreeze = true;
							if ($holdInfo['thaw_date'] != null) {
								$curHold->status .= ' until ' . date("m/d/Y", strtotime($holdInfo['thaw_date']));
							}
							$curHold->locationUpdateable = true;
						}elseif (!empty($holdInfo['shelf_time'])){
							$curHold->cancelable = false;
							$curHold->expirationDate = strtotime($holdInfo['shelf_expire_time']);
							$curHold->status = "Ready to Pickup";
						}elseif (!empty($holdInfo['capture_time'])){
							$curHold->cancelable = false;
							$curHold->status = "Ready to Pickup";
						}elseif (!empty($holdInfo['transit'])){
							$curHold->status = 'In Transit';
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

		if (strpos($recordId, ':') !== false){
			list(,$recordId) = explode(':', $recordId);
		}

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
				"email_notify" => 't',
//				"request_lib" =>  (int)$pickupBranch,
//				"request_time" => date( DateTime::ISO8601),
				"expire_time" => $cancelDate,
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
					if (isset($apiResponseB->payload[0]->last_event) && ($apiResponseB->payload[0]->last_event->textcode == 'HIGH_LEVEL_HOLD_HAS_NO_COPIES')){
						//Item/Part level holds are required
						$getPartsRequest = 'service=open-ils.search&method=open-ils.search.biblio.record_hold_parts';
						$namedPartsParams = [
							'record' => (int)$recordId
						];
						$getPartsRequest .= '&param=' . json_encode($namedPartsParams);
						$getPartsResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getPartsRequest);
						if ($this->apiCurlWrapper->getResponseCode() == 200) {
							$getPartsResponse = json_decode($getPartsResponse);
							$items = array();
							foreach ($getPartsResponse->payload[0] as $itemInfo){
								$items[] = array(
									'itemNumber' => $itemInfo->id,
									//'location' => trim(str_replace('&nbsp;', '', $itemInfo[2][$i])),
									'callNumber' => $itemInfo->label,
									//'status' => trim(str_replace('&nbsp;', '', $itemInfo[4][$i])),
								);
							}
							$hold_result['items'] = $items;
							if (count($items) > 0){
								$message = 'Please select a part to place a hold on.';
							}else{
								$message = 'There are no holdable items for this title.';
							}
							$hold_result['success'] = false;
							$hold_result['message'] = $message;
							return $hold_result;
						}
					}else {
						$hold_result['message'] = "Holds cannot be placed on this title";
						return $hold_result;
					}
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
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)){
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );

		$fines = [];

		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null) {
			//Get a list of holds
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.transactions.have_charge.fleshed';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . $patron->username;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload)){
					foreach ($apiResponse->payload[0] as $transactionList){
						foreach ($transactionList as $transactionObj){
							$transaction = $transactionObj->__p;
							$transactionObj = $this->mapEvergreenFields($transaction, $this->mbtsFields);
							$curFine = [
								'fineId' => $transactionObj['id'],
								'date' => strtotime($transactionObj['xact_start']),
								'type' => $transactionObj['xact_type'],
								'reason' => $transactionObj['last_billing_type'],
								'message' => $transactionObj['last_billing_note'],
								'amountVal' => $transactionObj['total_owed'],
								'amountOutstandingVal' => $transactionObj['total_owed'] - $transactionObj['total_paid'],
								'amount' => $currencyFormatter->formatCurrency($transactionObj['total_owed'], $currencyCode),
								'amountOutstanding' => $currencyFormatter->formatCurrency($transactionObj['total_owed'] - $transactionObj['total_paid'], $currencyCode),
							];
							$fines[] = $curFine;
						}
					}
				}
			}
		}

		return $fines;
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//return parent::patronLogin($username, $password, $validatedViaSSO);
		$username = trim($username);
		$password = trim($password);
		$session = $this->validatePatronAndGetAuthToken($username, $password);
		if ($session['userValid']){
			$userData = $this->fetchSession($session['authToken']);
			if ($userData != null){
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

	private function getStaffUserInfo()
	{
		if (!array_key_exists($this->accountProfile->staffUsername, Evergreen::$accessTokensForUsers)) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';

			$session = array(
				'userValid' => false,
				'authToken' => false,
			);

			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$params = [
				'service' => 'open-ils.auth',
				'method' => 'open-ils.auth.login',
				'param' => json_encode([
					'password' => (string)$this->accountProfile->staffPassword,
					'type' => 'persist',
					'org' => null,
					'identifier' => (string)$this->accountProfile->staffUsername,
				]),
			];

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

			if ($this->apiCurlWrapper->getResponseCode() == 200){
				$apiResponse = json_decode($apiResponse);
				$session['userValid'] = true;
				$session['authToken'] = $apiResponse->payload[0]->payload->authtoken;

				Evergreen::$accessTokensForUsers[$this->accountProfile->staffUsername] = $session;
			} else {
				Evergreen::$accessTokensForUsers[$this->accountProfile->staffUsername] = false;
			}
		}
		return Evergreen::$accessTokensForUsers[$this->accountProfile->staffUsername];
	}

	public function findNewUser($patronBarcode)
	{
		$sessionInfo = $this->getStaffUserInfo();
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers  = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$params = [
			'service' => 'open-ils.auth',
			'method' => 'open-ils.auth.session.retrieve',
			'param' => json_encode($sessionInfo['authToken']),
		];

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
				return $this->mapEvergreenFields($getSessionResponse->payload[0]->__p, $this->auFields); //payload
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
			$bibParsed = simplexml_load_string($superCatResult);
			return $bibParsed;
		}else{
			return null;
		}
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
		$authToken = $this->getAPIAuthToken($patron);
		if ($authToken != null){
			$sessionData = $this->fetchSession($authToken);
			if ($sessionData != null){
				$expireTime = $sessionData['expire_date'];
				$expireTime = strtotime($expireTime);
				$summary->expirationDate = $expireTime;
				//TODO : Load total charge balance
				//$summary->totalFines = $basicDataResponse->ChargeBalance;

				$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
				$headers = array(
					'Content-Type: application/x-www-form-urlencoded',
				);
				$this->apiCurlWrapper->addCustomHeaders($headers, false);
				$request = 'service=open-ils.actor&method=open-ils.actor.user.fines.summary';
				$request .= '&param=' . json_encode($authToken);
				$request .= '&param=' . $patron->username;
				$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

				if ($this->apiCurlWrapper->getResponseCode() == 200) {
					$apiResponse = json_decode($apiResponse);
					if (isset($apiResponse->payload) && isset($apiResponse->payload[0]->__p)){
						$moneySummary = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->mousFields);
						$summary->totalFines = $moneySummary['balance_owed'];
					}
				}
			}
		}

		return $summary;
	}
}