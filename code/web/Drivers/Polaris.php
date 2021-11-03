<?php

//TODO: Update patron info
//TODO: Cancel all holds
//TODO: Freeze all holds
//TODO: Self Register

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

	public function getAccountSummary(User $patron) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		$basicDataResponse = $this->getBasicDataResponse($patron->getBarcode(), $patron->getPasswordOrPin(), UserAccount::isUserMasquerading());
		if ($basicDataResponse != null){
			//TODO: Account for electronic items
			$summary->numCheckedOut = $basicDataResponse->ItemsOutCount;
			$summary->numOverdue = $basicDataResponse->ItemsOverdueCount;
			$summary->numAvailableHolds =  $basicDataResponse->HoldRequestsHeldCount;
			$summary->numUnavailableHolds = $basicDataResponse->HoldRequestsCurrentCount + $basicDataResponse->HoldRequestsShippedCount;
			$summary->totalFines = $basicDataResponse->ChargeBalance;

			$polarisCirculateBlocksUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisCirculateBlocksUrl, 'GET', Polaris::$accessTokensForUsers[$patron->getBarcode()]['accessToken'], false, UserAccount::isUserMasquerading());
			if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
				$circulateBlocksResponse = json_decode($circulateBlocksResponse);
				$expireTime = $this->parsePolarisDate($circulateBlocksResponse->ExpirationDate);
				$summary->expirationDate = $expireTime;
			}
		}

		return $summary;
	}

	public function getILSMessages(User $user)
	{
		$messages = [];
		$library = $user->getHomeLibrary();
		if ($library == null){
			return $messages;
		}

		$polarisCirculateBlocksUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/circulationblocks";
		$circulateBlocksResponse = $this->getWebServiceResponse($polarisCirculateBlocksUrl, 'GET', $this->getAccessToken($user->getBarcode(), $user->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
			$circulateBlocksResponse = json_decode($circulateBlocksResponse);
			foreach ($circulateBlocksResponse->Blocks as $block){
				if (!empty($block->BlockDescription)) {
					$messages[] = [
						'message' => $block->BlockDescription,
						'messageStyle' => 'danger'
					];
				}
			}
			if (!$circulateBlocksResponse->CanPatronCirculate && empty($messages)){
				$messages[] = [
					'message' => "Your account has been frozen.  Please contact the library for more information.",
					'messageStyle' => 'danger',
				];
			}
		}

		if (empty($messages)) {
			$staffUserInfo = $this->getStaffUserInfo();
			$polarisRenewBlocksUrl = "/PAPIService/REST/protected/v1/1033/100/1/{$staffUserInfo['accessToken']}/circulation/patron/{$user->username}/renewblocks";
			$renewBlocksResponse = $this->getWebServiceResponse($polarisRenewBlocksUrl, 'GET', $staffUserInfo['accessSecret'], false, UserAccount::isUserMasquerading());
			if ($renewBlocksResponse && $this->lastResponseCode == 200) {
				$renewBlocksResponse = json_decode($renewBlocksResponse);
				foreach ($renewBlocksResponse->Blocks as $block) {
					if (!empty($block->BlockDescription)) {
						$messages[] = [
							'message' => $block->BlockDescription,
							'messageStyle' => 'danger'
						];
					}
				}
				if (!$renewBlocksResponse->CanPatronRenew && empty($messages)) {
					$messages[] = [
						'message' => "Your account has been frozen.  Please contact the library for more information.",
						'messageStyle' => 'danger',
					];
				}
			}
		}

		return $messages;
	}

	/**
	 * @param string $patronBarcode
	 * @param string $password
	 * @param bool $fromMasquerade - true if we are calling this while initiating masquerade mode
	 * @return stdClass|null
	 */
	private function getBasicDataResponse(string $patronBarcode, string $password, bool $fromMasquerade = false){
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/basicdata?addresses=1";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patronBarcode, $password, $fromMasquerade), false, $fromMasquerade);
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

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut")
	{
		//Get preferences for the barcode
		$readingHistoryEnabled = false;
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/preferences";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$readingHistoryEnabled = $jsonResponse->PatronPreferences->ReadingListEnabled;
		}

		if ($readingHistoryEnabled) {
			$readingHistoryTitles = array();
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/readinghistory?rowsperpage=5&page=0";
			$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				$readingHistoryList = $jsonResponse->PatronReadingHistoryGetRows;
				foreach ($readingHistoryList as $readingHistoryItem) {
					$checkOutDate = $this->parsePolarisDate($readingHistoryItem->CheckOutDate);
					$curTitle = array();
					$curTitle['id'] = $readingHistoryItem->BibID;
					$curTitle['shortId'] = $readingHistoryItem->BibID;
					$curTitle['recordId'] = $readingHistoryItem->BibID;
					$curTitle['title'] = $readingHistoryItem->Title;
					$curTitle['author'] = $readingHistoryItem->Author;
					$curTitle['format'] = $readingHistoryItem->FormatDescription;
					$curTitle['checkout'] = $checkOutDate;
					$curTitle['checkin'] = null; //Polaris doesn't indicate when things are checked in
					$curTitle['ratingData'] = null;
					$curTitle['permanentId'] = null;
					$curTitle['linkUrl'] = null;
					$curTitle['coverUrl'] = null;
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $curTitle['recordId']);
					if ($recordDriver->isValid()) {
						$curTitle['ratingData'] = $recordDriver->getRatingData();
						$curTitle['permanentId'] = $recordDriver->getPermanentId();
						$curTitle['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
						$curTitle['format'] = $recordDriver->getFormats();
						$curTitle['author'] = $recordDriver->getPrimaryAuthor();
					}
					$recordDriver = null;
					$readingHistoryTitles[] = $curTitle;
				}
			}
		}

		return array('historyActive' => $readingHistoryEnabled, 'titles' => $readingHistoryTitles, 'numTitles' => count($readingHistoryTitles));
	}

	public function getCheckouts(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = array();

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$itemsOutList = $jsonResponse->PatronItemsOutGetRows;
			foreach ($itemsOutList as $index => $itemOut){
				if ($itemOut->DisplayInPAC == 1 && !$itemOut->ElectronicItem) {
					$curCheckout = new Checkout();
					$curCheckout->type = 'ils';
					$curCheckout->source = $this->getIndexingProfile()->name;
					$curCheckout->sourceId = $itemOut->ItemID;
					$curCheckout->userId = $patron->id;

					$curCheckout->recordId = $itemOut->BibID;
					$curCheckout->itemId = $itemOut->ItemID;

					$curCheckout->dueDate = $this->parsePolarisDate($itemOut->DueDate);
					$curCheckout->checkoutDate = $this->parsePolarisDate($itemOut->CheckOutDate);

					$curCheckout->renewCount = $itemOut->RenewalCount;
					$curCheckout->canRenew = $itemOut->RenewalCount < $itemOut->RenewalLimit;
					$curCheckout->renewalId = $itemOut->ItemID;
					$curCheckout->renewIndicator = $itemOut->ItemID;

					$curCheckout->title = $itemOut->Title;
					$curCheckout->author = $itemOut->Author;
					$curCheckout->formats = [$itemOut->FormatDescription];
					$curCheckout->callNumber = $itemOut->CallNumber;

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver((string)$curCheckout->recordId);
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

	public function hasFastRenewAll()
	{
		return true;
	}

	public function renewAll(User $patron)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/0";
		$body = new stdClass();
		$body->Action = 'renew';
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->RenewData = new stdClass();
		$body->RenewData->IgnoreOverrideErrors = false;

		$accountSummary = $this->getAccountSummary($patron);

		$renewResult = array(
			'success' => false,
			'message' => array(),
			'Renewed' => 0,
			'NotRenewed' => $accountSummary->numCheckedOut,
			'Total' => $accountSummary->numCheckedOut
		);

		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0 || $jsonResponse->PAPIErrorCode == -3) {
				$itemRenewResult = $jsonResponse->ItemRenewResult;
				$renewResult['Renewed'] = count($itemRenewResult->DueDateRows);
				$renewResult['NotRenewed'] = count($itemRenewResult->BlockRows);
				if (count($itemRenewResult->BlockRows) > 0) {
					$checkouts = $patron->getCheckouts(true, $this->getIndexingProfile()->name);
					foreach ($itemRenewResult->BlockRows as $blockRow) {
						$itemId = $blockRow->ItemRecordID;
						$title = 'Unknown Title';
						foreach ($checkouts as $checkout){
							if ($checkout->itemId == $itemId){
								$title = $checkout->title;
							}
						}
						$renewResult['message'][] = $title . ':' . $blockRow->ErrorDesc;
					}
				}
				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
				$patron->forceReloadOfCheckouts();
				$renewResult['success'] = true;
			}else{
				$message = "All items could not be renewed.";
				$renewResult['message'][] = $message;
			}
		}else{
			$message = "The item could not be renewed";
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			$renewResult['message'][] = $message;
		}
		return $renewResult;
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/$itemId";
		$body = new stdClass();
		$body->Action = 'renew';
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->RenewData = new stdClass();
		$body->RenewData->IgnoreOverrideErrors = false;

		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$itemRenewResult = $jsonResponse->ItemRenewResult;
				if (isset($itemRenewResult->DueDateRows) && (count($itemRenewResult->DueDateRows) > 0)) {
					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfCheckouts();
					return array(
						'itemId' => $itemId,
						'success' => true,
						'message' => "Your item was successfully renewed"
					);
				}else{
					$message = '';
					foreach ($itemRenewResult->BlockRows as $blockRow){
						if (strlen($message) == 0){
							$message .= '<br/>';
						}
						$message .= $blockRow->ErrorDesc;
					}
					if (strlen($message) == 0){
						$message .= "This item could not be renewed";
					}
					return array(
						'itemId' => $itemId,
						'success' => true,
						'message' => $message
					);
				}
			}else{
				$message = "The item could not be renewed. {$jsonResponse->ErrorMessage}";
				return array(
					'itemId' => $itemIndex,
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$message = "The item could not be renewed";
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			return array(
				'itemId' => $itemIndex,
				'success' => false,
				'message' => $message
			);
		}
	}

	public function getHolds($patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$holdsList = $jsonResponse->PatronHoldRequestsGetRows;
			foreach ($holdsList as $index => $holdInfo){
				$curHold = new Hold();
				$curHold->userId = $patron->id;
				$curHold->type = 'ils';
				$curHold->source = $this->getIndexingProfile()->name;
				$curHold->sourceId = $holdInfo->HoldRequestID;
				$curHold->recordId = $holdInfo->BibID;
				$curHold->cancelId = $holdInfo->HoldRequestID;
				$curHold->frozen = false;
				$curHold->locationUpdateable = true;
				$curHold->cancelable = true;
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
						//TODO: This can be cancelled sometimes, depending on settings, but those settings are not returned.
						//Allow cancellation and then let Polaris decide if it works or not.
						break;
					case 6:
						//Held
						$curHold->status = $holdInfo->StatusDescription;
						$isAvailable = true;
						$curHold->locationUpdateable = false;
						$curHold->cancelable = false;
						break;
					case 7:
						//Not Supplied
						$curHold->status = $holdInfo->StatusDescription;
						$curHold->cancelable = false;
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
				if (!$isAvailable) {
					$curHold->holdQueueLength   = $holdInfo->QueueTotal;
					$curHold->position          = $holdInfo->QueuePosition;
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

				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver((string)$curHold->recordId);
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

		return $holds;
	}

	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		return $this->placeItemHold($patron, $recordId, null, $pickupBranch, $cancelDate);
	}

	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		if (strpos($recordId, ':') !== false){
			list(,$shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}

		global $offlineMode;
		if ($offlineMode) {
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold                = new OfflineHold();
			$offlineHold->bibId         = $shortId;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId      = $patron->id;
			$offlineHold->timeEntered   = time();
			$offlineHold->status        = 'Not Processed';
			if ($offlineHold->insert()) {
				return array(
					'title' => $title,
					'bib' => $shortId,
					'success' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			} else {
				return array(
					'title' => $title,
					'bib' => $shortId,
					'success' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		} else {
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest";
			$body = new stdClass();
			$body->PatronID = (int)$patron->username;
			$body->BibID = (int)$shortId;
			if (!empty($itemId)) {
				//Check to see if we also have a volume
				$relatedRecord = $record->getRelatedRecord();
				foreach ($relatedRecord->getItems() as $item){
					if ($item->itemId == $itemId){
						$marcRecord = $record->getMarcRecord();
						if (!empty($item->volume)) {
							//Volume holds just need the volume
							$body->VolumeNumber = $item->volume;
						}else{
							//If we place a hold on just an item, we need a barcode for the item rather than the record number
							/** @var File_MARC_Data_Field[] $marcItems */
							$marcItems = $marcRecord->getFields($this->getIndexingProfile()->itemTag);
							foreach ($marcItems as $marcItem) {
								$itemSubField = $marcItem->getSubfield($this->getIndexingProfile()->itemRecordNumber);
								if ($itemSubField->getData() == $itemId){
									$body->ItemBarcode = $marcItem->getSubfield($this->getIndexingProfile()->barcode)->getData();
									break;
								}
							}
						}
						break;
					}
				}
			}
			$body->PickupOrgID = (int)$pickupBranch;
			//Need to set the Workstation
			$body->WorkstationID = $this->getWorkstationID($patron);
			//Get the ID of the staff user
			$staffUserInfo = $this->getStaffUserInfo();
			$body->UserID = (int)$staffUserInfo['polarisId'];
			$body->RequestingOrgID = (int)$patron->getHomeLocationCode();
			$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', json_encode($body));
			$hold_result = $this->processHoldRequestResponse($response, $patron);

			$hold_result['title'] = $title;
			$hold_result['bid']   = $shortId;

			return $hold_result;
		}
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch)
	{
		if (strpos($recordId, ':') !== false){
			list(,$shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}

		//Get

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest";
		$body = new stdClass();
		$body->PatronID = (int)$patron->username;
		$body->BibID = (int)$recordId;
		$body->PickupOrgID = (int)$pickupBranch;
		$body->VolumeNumber = $volumeId;
		//Need to set the Workstation
		$body->WorkstationID = $this->getWorkstationID($patron);
		//Get the ID of the staff user
		$staffUserInfo = $this->getStaffUserInfo();
		$body->UserID = (int)$staffUserInfo['polarisId'];
		$body->RequestingOrgID = (int)$patron->getHomeLocationCode();
		$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', json_encode($body));
		$hold_result = $this->processHoldRequestResponse($response, $patron);

		$hold_result['title'] = $title;
		$hold_result['bid']   = $shortId;

		return $hold_result;
	}

	public function confirmHold(User $patron, $recordId, $confirmationId)
	{
		if (strpos($recordId, ':') !== false){
			list(,$shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}
		$result = [
			'success' => false,
			'message' => 'Unknown error confirming the hold'
		];
		require_once ROOT_DIR . '/sys/ILS/HoldRequestConfirmation.php';
		$holdRequestConfirmation = new HoldRequestConfirmation();
		$holdRequestConfirmation->id = $confirmationId;
		if ($holdRequestConfirmation->find(true)){
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest/" . $holdRequestConfirmation->requestId;
			$confirmationInfo = json_decode($holdRequestConfirmation->additionalParams);
			$body = new stdClass();
			$body->TxnGroupQualifier = $confirmationInfo->groupQualifier;
			$body->TxnQualifier = $confirmationInfo->qualifier;
			$body->RequestingOrgID = (int)$patron->getHomeLocationCode();
			$body->Answer = 1;
			$body->State = $confirmationInfo->state;
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', '', json_encode($body));

			$result = $this->processHoldRequestResponse($response, $patron);

			$result['title'] = $title;
			$result['bid']   = $shortId;

		}else{
			$result['message'] = 'Could not find information about the hold to be confirmed, it may have been confirmed already';
		}
		return $result;
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$cancelId/cancelled?wsid={$this->getWorkstationID($patron)}&userid={$staffInfo['polarisId']}";
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
				$patron->forceReloadOfHolds();
				return array(
					'success' => true,
					'message' => "The hold has been cancelled."
				);
			}else{
				$message = "The hold could not be cancelled. {$jsonResponse->ErrorMessage}";
				return array(
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$message = "The hold could not be cancelled.";
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			return array(
				'success' => false,
				'message' => $message
			);
		}
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

	private function loadPatronBasicData(string $patronBarcode, string $password, $patronId, bool $fromMasquerade = false)
	{
		$patronBasicData = $this->getBasicDataResponse($patronBarcode, $password, $fromMasquerade);
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
			$user->_fullname = $user->firstname . " " . $user->lastname;
			if ($forceDisplayNameUpdate) {
				$user->displayName = '';
			}
			$user->phone = $patronBasicData->PhoneNumber;
			if ($user->phone == null){
				$user->phone = $patronBasicData->PhoneNumber2;
				if ($user->phone == null){
					$user->phone = $patronBasicData->PhoneNumber3;
				}
			}
			$user->email = $patronBasicData->EmailAddress;

			$addresses = $patronBasicData->PatronAddresses;
			if (count($addresses) > 0){
				$address = reset($addresses);
				$user->_address1 = $address->StreetOne;
				$user->_address2 = $address->StreetTwo;
				$user->_city = $address->City;
				$user->_state = $address->State;
				$user->_zip = $address->PostalCode;
			}

			$polarisCirculateBlocksUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisCirculateBlocksUrl, 'GET', Polaris::$accessTokensForUsers[$patronBarcode]['accessToken'], false, $fromMasquerade);
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
				//New user check to see if they have reading history
				//Get preferences for the barcode
				$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/preferences";
				$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($user->getBarcode(), $user->getPasswordOrPin()), false, $fromMasquerade);
				if ($response && $this->lastResponseCode == 200){
					$jsonResponse = json_decode($response);
					$user->trackReadingHistory = $jsonResponse->PatronPreferences->ReadingListEnabled;
				}

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
	protected function loginViaWebService(&$username, $password, $fromMasquerade = false) : array
	{
		if (array_key_exists($username, Polaris::$accessTokensForUsers)){
			return Polaris::$accessTokensForUsers[$username];
		}else {
			$staffUserInfo = $this->getStaffUserInfo();

			$session = array(
				'userValid' => false,
				'accessToken' => false,
				'patronId' => false
			);

			//Validate that the patron exists. This can also be used to get the barcode for the user based on username
			$validatePatronResponseRaw = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/patron/' . $username, 'GET', $staffUserInfo['accessSecret'], false, true);
			$patronValidationDone = false;
			if ($validatePatronResponseRaw){
				$validationResponse = json_decode($validatePatronResponseRaw);
				if ($validationResponse->PAPIErrorCode == -3000){
					$patronValidationDone = true;
					$session = array(
						'userValid' => true,
						'accessToken' => '',
						'patronId' => ''
					);
				}else if (!empty($validationResponse->PatronBarcode) && $validationResponse->PatronBarcode != $username){
					$username = $validationResponse->PatronBarcode;
				}
			}

			$authenticationData = new stdClass();
			$authenticationData->Barcode = $username;
			$authenticationData->Password = $password;

			if (!$patronValidationDone) {
				$body = json_encode($authenticationData);
				$authenticationResponseRaw = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/authenticator/patron', 'POST', '', $body, $fromMasquerade);
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
			}
			Polaris::$accessTokensForUsers[$username] = $session;
			return $session;
		}
	}

	private function getAccessToken(string $barcode, string $password, bool $fromMasquerade = false)
	{
		//Get the session token for the user
		if (isset(Polaris::$accessTokensForUsers[$barcode])) {
			return Polaris::$accessTokensForUsers[$barcode]['accessToken'];
		} else {
			$sessionInfo = $this->loginViaWebService($barcode, $password, $fromMasquerade);
			return $sessionInfo['accessToken'];
		}
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToFreezeId/inactive";
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		if ($dateToReactivate == null){
			//User didn't pick a specific date, pick a day that is 2 years from now.
			$curTime = time();
			$curTime += 365 * 2 * 24 * 60 * 60;
			$formattedTime = date('Y-m-d\TH:i:s.00', $curTime);
			$body->ActivationDate = $formattedTime;
		}else {
			$body->ActivationDate = $dateToReactivate;
		}

		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				return array(
					'success' => true,
					'message' => "The hold has been frozen."
				);
			}else{
				$message = "The hold could not be frozen. {$jsonResponse->ErrorMessage}";
				return array(
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$message = "The hold could not be frozen.";
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			return array(
				'success' => false,
				'message' => $message
			);
		}
	}

	function thawHold(User $patron, $recordId, $itemToThawId)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToThawId/active";
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				return array(
					'success' => true,
					'message' => "The hold has been thawed."
				);
			}else{
				$message = "The hold could not be thawed. {$jsonResponse->ErrorMessage}";
				return array(
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$message = "The hold could not be thawed.";
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			return array(
				'success' => false,
				'message' => $message
			);
		}
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToUpdateId/pickupbranch?wsid={$this->getWorkstationID($patron)}&userid={$staffInfo['polarisId']}&pickupbranchid=$newPickupLocation";
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				return array(
					'success' => true,
					'message' => translate(['text'=>'The pickup location of your hold was changed successfully.', 'isPublicFacing'=>true])
				);
			}else{
				$message = translate(['text'=>'Sorry, the pickup location of your hold could not be changed.', 'isPublicFacing'=>true]) . " {$jsonResponse->ErrorMessage}";;
				return array(
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$message = translate(['text'=>'Sorry, the pickup location of your hold could not be changed.', 'isPublicFacing'=>true]);
			if (IPAddress::showDebuggingInformation()){
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			return array(
				'success' => false,
				'message' => $message
			);
		}
	}

	/**
	 * @param User $patron
	 * @param bool $canUpdateContactInfo
	 * @return array
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo)
	{
		$result = [
			'success' => false,
			'messages' => []
		];
		if ($canUpdateContactInfo) {
			$staffInfo = $this->getStaffUserInfo();
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}";
			$body = new stdClass();
			$body->LogonBranchID = $patron->getHomeLocationCode();
			$body->LogonUserID = (string)$staffInfo['polarisId'];
			$body->LogonWorkstationID = $this->getWorkstationID($patron);
			if (isset($_REQUEST['email'])) {
				$body->EmailAddress = $_REQUEST['email'];
				$patron->email = $_REQUEST['email'];
			}

			if (isset($_REQUEST['phone'])) {
				$body->PhoneVoice1 = $_REQUEST['phone'];
				$patron->phone = $_REQUEST['phone'];
			}

			$patronBasicData = $this->getBasicDataResponse($patron->getBarcode(), $patron->getPasswordOrPin());
			//Get the ID of the address to update
			$addresses = $patronBasicData->PatronAddresses;
			if (count($addresses) > 0){
				$address = reset($addresses);
				$body->AddressID = $address->AddressID;
				$body->FreeTextID = $address->FreeTextLabel;
			}

			if (isset($_REQUEST['address1'])) {
				$body->StreetOne = $_REQUEST['address1'];
				$patron->_address1 = $_REQUEST['address1'];
			}

			if (isset($_REQUEST['address2'])) {
				$body->StreetTwo = $_REQUEST['address2'];
				$patron->_address2 = $_REQUEST['address2'];
			}

			if (isset($_REQUEST['city'])) {
				$body->City = $_REQUEST['city'];
				$patron->_city = $_REQUEST['city'];
			}

			if (isset($_REQUEST['state'])) {
				$body->State = $_REQUEST['state'];
				$patron->_state = $_REQUEST['state'];
			}

			if (isset($_REQUEST['zip'])) {
				$body->PostalCode = $_REQUEST['zip'];
				$patron->_zip = $_REQUEST['zip'];
			}

			if (count($addresses) > 0){
				$address = reset($addresses);
				$body->AddressTypeID = $address->AddressTypeID;
			}

			// Update Home Location
			if (!empty($_REQUEST['pickupLocation'])) {
				$homeLibraryLocation = new Location();
				if ($homeLibraryLocation->get('code', $_REQUEST['pickupLocation'])) {
					$homeBranchCode = strtoupper($homeLibraryLocation->code);
					$body->RequestPickupBranchID = $homeBranchCode;
				}
			}
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->PAPIErrorCode == 0) {
					$result['success'] = true;
					$result['messages'][] = 'Your account was updated successfully.';
					$patron->update();
				}else{
					$result['messages'][] = "Error updating profile information (Error {$jsonResponse->PAPIErrorCode}).";
				}
			}else{
				$result['messages'][] = "Error updating profile information ({$this->lastResponseCode}).";
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}
		return $result;
	}

	function updatePin(User $patron, string $oldPin, string $newPin)
	{
		if ($patron->cat_password != $oldPin) {
			return ['success' => false, 'message' => "The old PIN provided is incorrect."];
		}
		$result = ['success' => false, 'message' => "Unknown error updating password."];
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}";
		$body = new stdClass();
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->Password = $newPin;
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$result['success'] = true;
				$result['message'] = 'Your password was updated successfully.';
				$patron->cat_password = $newPin;
				$patron->update();
			}else{
				$result['message'] = "Error updating your password. (Error {$jsonResponse->PAPIErrorCode}).";
			}
		}else{
			$result['messages'] = "Error updating your password. ({$this->lastResponseCode}).";
		}
		return $result;
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

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/account/outstanding";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		$fines = [];
		if ($response && $this->lastResponseCode == 200){
			$jsonResponse = json_decode($response);
			$finesRows = $jsonResponse->PatronAccountGetRows;
			foreach ($finesRows as $fineRow){
				$curFine = [
					'fineId' => $fineRow->TransactionID,
					'date' => $this->parsePolarisDate($fineRow->TransactionDate),
					'type' => $fineRow->TransactionTypeDescription,
					'reason' => $fineRow->FeeDescription,
					'message' => $fineRow->Title . " " . $fineRow->Author . ' ' . $fineRow->FreeTextNote,
					'amountVal' => $fineRow->TransactionAmount,
					'amountOutstandingVal' => $fineRow->OutstandingAmount,
					'amount' => $currencyFormatter->formatCurrency($fineRow->TransactionAmount, $currencyCode),
					'amountOutstanding' => $currencyFormatter->formatCurrency($fineRow->OutstandingAmount, $currencyCode),
				];
				$fines[] = $curFine;
			}
		}
		return $fines;
	}

	function showOutstandingFines()
	{
		return true;
	}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		$staffUserInfo = $this->getStaffUserInfo();
		$result = [
			'success' => false,
			'message' => ''
		];
		$finePayments = explode(',', $payment->finesPaid);
		$allPaymentsSucceed = true;
		foreach ($finePayments as $finePayment){
			list($fineId, $paymentAmount) = explode('|', $finePayment);
			$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/1/{$staffUserInfo['accessToken']}/patron/{$patron->getBarcode()}/account/{$fineId}/pay?wsid={$this->getWorkstationID($patron)}&userid={$staffUserInfo['polarisId']}";
			$body = new stdClass();
			$body->TxnAmount = $paymentAmount;
			$body->PaymentMethodID = 12;
			$body->FreeTextNote = 'Paid Online via Aspen Discovery';

			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $staffUserInfo['accessSecret'], json_encode($body), UserAccount::isUserMasquerading());

			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->PAPIErrorCode != 0) {
					$result['message'] .= $jsonResponse->ErrorMessage . '. ';
					$allPaymentsSucceed = false;
				}
			}else{
				$result['message'] .= 'Error paying fine in Polaris ' . $this->lastResponseCode . '. ';
				$allPaymentsSucceed = false;
			}
		}
		if ($allPaymentsSucceed){
			$result = [
				'success' => true,
				'message' => 'Your fines have been paid successfully, thank you.'
			];
		}

		global $logger;
		$logger->log("Marked fines as paid within Polaris for user {$patron->id}, {$result['message']}", Logger::LOG_ERROR);

		return $result;
	}

	public function getWebServiceResponse($query, $method = 'GET', $patronPassword = '', $body = false, $actAsStaff = false){
		// auth has to be in GMT, otherwise use config-level TZ
		$site_config_TZ = date_default_timezone_get();
		date_default_timezone_set('GMT');
		$date = date("D, d M Y H:i:s T");
		date_default_timezone_set($site_config_TZ);

		$url = $this->getWebServiceURL() . $query;

		if ($actAsStaff){
			$staffUserInfo = $this->getStaffUserInfo();
			$patronPassword = $staffUserInfo['accessSecret'];
		}
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
		if ($actAsStaff){
			$staffUserInfo = $this->getStaffUserInfo();
			$this->apiCurlWrapper->addCustomHeaders([
				'X-PAPI-AccessToken:' . $staffUserInfo['accessToken']
			], false);
		}

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
			//$timezoneOffset = $matches[2];
			//TODO: Adjust for timezone offset
			return $timestamp;
		} else {
			return 0;
		}
	}

	private function getStaffUserInfo()
	{
		if (!array_key_exists($this->accountProfile->staffUsername, Polaris::$accessTokensForUsers)) {
			$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/1/authenticator/staff";
			$authenticationData = new stdClass();
			$authenticationData->Domain = $this->accountProfile->domain;
			$authenticationData->Username = $this->accountProfile->staffUsername;
			$authenticationData->Password = $this->accountProfile->staffPassword;

			$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', json_encode($authenticationData));
			if ($response) {
				$jsonResponse = json_decode($response);
				Polaris::$accessTokensForUsers[$this->accountProfile->staffUsername] = [
					'accessToken' => $jsonResponse->AccessToken,
					'accessSecret' => $jsonResponse->AccessSecret,
					'polarisId' => $jsonResponse->PolarisUserID
				];
			} else {
				Polaris::$accessTokensForUsers[$this->accountProfile->staffUsername] = false;
			}
		}
		return Polaris::$accessTokensForUsers[$this->accountProfile->staffUsername];
	}

	public function findNewUser($patronBarcode){
		$staffUserInfo = $this->getStaffUserInfo();

		//Validate that the patron exists. This can also be used to get the barcode for the user based on username
		$validatePatronResponseRaw = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/patron/' . $patronBarcode, 'GET', $staffUserInfo['accessSecret'], false, true);
		$patronId = false;
		if ($validatePatronResponseRaw){
			$validationResponse = json_decode($validatePatronResponseRaw);
			if ($validationResponse->PAPIErrorCode != -3000){
				if (!empty($validationResponse->PatronBarcode) && $validationResponse->PatronBarcode != $patronBarcode){
					$patronBarcode = $validationResponse->PatronBarcode;
				}
				$patronId = $validationResponse->PatronID;
			}
		}

		//Load user data
		if ($patronId != false) {
			return $this->loadPatronBasicData($patronBarcode, '', $patronId, true);
		}else{
			return false;
		}
	}

	private function getWorkstationID(User $patron) : int
	{
		$homeLibrary = $patron->getHomeLibrary();
		if (empty($homeLibrary->workstationId)){
			return (int)$this->accountProfile->workstationId;
		}else{
			return (int)$homeLibrary->workstationId;
		}

	}

	/**
	 * @param $response
	 * @param User $patron
	 * @return array
	 */
	private function processHoldRequestResponse($response, User $patron): array
	{
		$hold_result = array();
		if ($response && $this->lastResponseCode == 200) {
			$jsonResult = json_decode($response);
			if ($jsonResult->PAPIErrorCode != 0) {
				$hold_result['success'] = false;
				$hold_result['message'] = 'Your hold could not be placed. ';
				if (IPAddress::showDebuggingInformation()) {
					$hold_result['message'] .= " ({$jsonResult->PAPIErrorCode})";
				}
			} else {
				if ($jsonResult->StatusType == 1) {
					$hold_result['success'] = false;
					$hold_result['message'] = translate(['text'=>'Your hold could not be placed. ' . $jsonResult->Message, 'isPublicFacing'=>true]);
				} else if ($jsonResult->StatusType == 2) {
					$hold_result['success'] = true;
					$hold_result['message'] = translate(['text' => "Your hold was placed successfully.", 'isPublicFacing'=>true]);
					if (isset($jsonResult->QueuePosition)) {
						$hold_result['message'] .= '&nbsp;' . translate(['text' => "You are number <b>%1%</b> in the queue.", '1' => $jsonResult->QueuePosition, 'isPublicFacing'=>true]);
					}
					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				} else if ($jsonResult->StatusType == 3) {
					$hold_result['success'] = false;
					$hold_result['confirmationNeeded'] = true;
					$hold_result['title'] = translate(['text'=>"Place Hold?", 'isPublicFacing'=>true]);
					require_once ROOT_DIR . '/sys/ILS/HoldRequestConfirmation.php';
					$holdRequestConfirmation = new HoldRequestConfirmation();
					$holdRequestConfirmation->userId = $patron->id;
					$holdRequestConfirmation->requestId = $jsonResult->RequestGUID;
					$holdRequestConfirmation->additionalParams = json_encode([
						'requestId' => $jsonResult->RequestGUID,
						'groupQualifier' => $jsonResult->TxnGroupQualifer,
						'qualifier' => $jsonResult->TxnQualifier,
						'state' => $jsonResult->StatusValue
					]);
					$holdRequestConfirmation->insert();
					$hold_result['confirmationId'] = $holdRequestConfirmation->id;

					$hold_result['message'] = translate(['text'=>$jsonResult->Message, 'isPublicFacing'=>true]);
				}
			}
		} else {
			$hold_result['success'] = false;
			$hold_result['message'] =  translate(['text'=>'Your hold could not be placed. ', 'isPublicFacing'=>true]);
			if (IPAddress::showDebuggingInformation()) {
				$hold_result['message'] .= " (HTTP Code: {$this->lastResponseCode})";
			}
		}
		return $hold_result;
	}

	public function treatVolumeHoldsAsItemHolds() {
		return true;
	}

	/**
	 * Import Lists from the ILS
	 *
	 * @param User $patron
	 * @return array - an array of results including the names of the lists that were imported as well as number of titles.
	 */
	function importListsFromIls($patron)
	{
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		$results = array(
			'totalTitles' => 0,
			'totalLists' => 0
		);

		//Get a list of all lists for the user
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/patronaccountgettitlelists";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			foreach ($jsonResponse->PatronAccountTitleListsRows as $listsRow) {
				$listId = $listsRow->RecordStoreID;
				$listName = $listsRow->RecordStoreName;
				$newList = new UserList();
				$newList->user_id = $patron->id;
				$newList->title = $listName;
				if (!$newList->find(true)) {
					$newList->insert();
				}elseif ($newList->deleted == 1){
					$newList->removeAllListEntries(true);
					$newList->deleted = 0;
					$newList->update();
				}
				$results['totalLists']++;
				//Get titles currently on the list
				$currentListTitles = $newList->getListTitles();

				//Get the titles for the list
				$getListTitlesUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/patrontitlelistgettitles?list=$listId";
				$getListTitlesResponse = $this->getWebServiceResponse($getListTitlesUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
				if ($getListTitlesResponse && $this->lastResponseCode == 200) {
					$getListTitlesJson = json_decode($getListTitlesResponse);
					foreach ($getListTitlesJson->PatronTitleListTitleRows as $titleListTitleRow) {
						$bibNumber = $titleListTitleRow->LocalControlNumber;
						$title = $titleListTitleRow->Name;
						$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
						$groupedWork = new GroupedWork();
						$primaryIdentifier->identifier = $bibNumber;
						$primaryIdentifier->type = $this->accountProfile->recordSource;

						if ($primaryIdentifier->find(true)) {
							$groupedWork->id = $primaryIdentifier->grouped_work_id;
							if ($groupedWork->find(true)) {
								//Check to see if this title is already on the list.
								$resourceOnList = false;
								foreach ($currentListTitles as $currentTitle) {
									if ($currentTitle->source == 'GroupedWork' && $currentTitle->sourceId == $groupedWork->permanent_id) {
										$resourceOnList = true;
										break;
									}
								}

								if (!$resourceOnList) {
									$listEntry = new UserListEntry();
									$listEntry->source = 'GroupedWork';
									$listEntry->sourceId = $groupedWork->permanent_id;
									$listEntry->listId = $newList->id;
									$listEntry->notes = '';
									$listEntry->dateAdded = time();
									$listEntry->title = StringUtils::trimStringToLengthAtWordBoundary($title, 50, true);
									$listEntry->insert();
									$currentListTitles[] = $listEntry;
								}
								$results['totalTitles']++;
							} else {
								if (!isset($results['errors'])) {
									$results['errors'] = array();
								}
								$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
							}
						} else {
							//The title is not in the resources, add an error to the results
							if (!isset($results['errors'])) {
								$results['errors'] = array();
							}
							$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
						}
					}
				}
			}
		}


		return $results;
	}

	function getPasswordPinValidationRules(){
		return [
			'minLength' => 4,
			'maxLength' => 14,
			'onlyDigitsAllowed' => false,
		];
	}
}