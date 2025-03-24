<?php

//TODO: Cancel all holds
//TODO: Freeze all holds
//TODO: Self Register

class Polaris extends AbstractIlsDriver {
	//Caching of sessionIds by patron for performance
	private static $accessTokensForUsers = [];
	private static $accessTokenForStaff = null;

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Polaris Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct() {
		$this->apiCurlWrapper = null;
	}

	public function getAccountSummary(User $patron): AccountSummary {
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		//Can't use the quick response since it includes eContent.
		$checkouts = $this->getCheckouts($patron);
		$summary->numCheckedOut = count($checkouts);
		$numOverdue = 0;
		foreach ($checkouts as $checkout) {
			if ($checkout->isOverdue()) {
				$numOverdue++;
			}
		}
		$summary->numOverdue = $numOverdue;

		$holds = $this->getHolds($patron);
		$summary->numAvailableHolds = count($holds['available']);
		$summary->numUnavailableHolds = count($holds['unavailable']);

		//Get additional information
		$basicDataResponse = $this->getBasicDataResponse($patron->getBarcode(), $patron->getPasswordOrPin(), UserAccount::isUserMasquerading());
		if ($basicDataResponse != null) {
			$summary->totalFines = $basicDataResponse->ChargeBalance;

			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisUrl, 'GET', Polaris::$accessTokensForUsers[$patron->getBarcode()]['accessToken'], false, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.getCirculateBlocks', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $circulateBlocksResponse, []);
			if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
				$circulateBlocksResponse = json_decode($circulateBlocksResponse);
				$expireTime = $this->parsePolarisDate($circulateBlocksResponse->ExpirationDate);
				$summary->expirationDate = $expireTime;
			}
		}

		return $summary;
	}

	public function getILSMessages(User $user) {
		$messages = [];
		$library = $user->getHomeLibrary();
		if ($library == null) {
			return $messages;
		}

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$user->getBarcode()}/circulationblocks";
		$circulateBlocksResponse = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($user->getBarcode(), $user->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.getCirculateBlocks', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $circulateBlocksResponse, []);
		if ($circulateBlocksResponse && $this->lastResponseCode == 200) {
			$circulateBlocksResponse = json_decode($circulateBlocksResponse);
			foreach ($circulateBlocksResponse->Blocks as $block) {
				if (!empty($block->BlockDescription)) {
					$messages[] = [
						'message' => $block->BlockDescription,
						'messageStyle' => 'danger',
					];
				}
			}
			if (!$circulateBlocksResponse->CanPatronCirculate && empty($messages)) {
				$messages[] = [
					'message' => "Your account has been frozen.  Please contact the library for more information.",
					'messageStyle' => 'danger',
				];
			}
		}

		if (empty($messages)) {
			$staffUserInfo = $this->getStaffUserInfo();
			$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/1/{$staffUserInfo['accessToken']}/circulation/patron/{$user->unique_ils_id}/renewblocks";
			$renewBlocksResponse = $this->getWebServiceResponse($polarisUrl, 'GET', $staffUserInfo['accessSecret'] , false, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.getRenewBlocks', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $renewBlocksResponse, []);
			if ($renewBlocksResponse && $this->lastResponseCode == 200) {
				$renewBlocksResponse = json_decode($renewBlocksResponse);
				foreach ($renewBlocksResponse->Blocks as $block) {
					if (!empty($block->BlockDescription)) {
						$messages[] = [
							'message' => $block->BlockDescription,
							'messageStyle' => 'danger',
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

		//TODO: Implement /public/1/patron/{PatronBarcode}/messages (Ticket 95499) - Also implement marking as read

		return $messages;
	}

	/**
	 * @param string $patronBarcode
	 * @param string $password
	 * @param bool $fromMasquerade - true if we are calling this while initiating masquerade mode
	 * @return stdClass|null
	 */
	private function getBasicDataResponse(string $patronBarcode, ?string $password, bool $fromMasquerade = false) {
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/basicdata?addresses=1";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patronBarcode, $password, $fromMasquerade), false, $fromMasquerade);
		ExternalRequestLogEntry::logRequest('polaris.getBasicDataResponse', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			return $jsonResponse->PatronBasicData;
		} else {
			return null;
		}
	}

	public function hasNativeReadingHistory(): bool {
		return true;
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		//Get preferences for the barcode
		$readingHistoryEnabled = false;
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/preferences";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.getPreferences', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			$readingHistoryEnabled = $jsonResponse->PatronPreferences->ReadingListEnabled;
		}

		$readingHistoryTitles = [];
		if ($readingHistoryEnabled) {
			ini_set('memory_limit', '2G');

			$readingHistoryTitles = [];
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/readinghistory?rowsperpage=5&page=0";
			$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.getReadingHistory', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				$readingHistoryList = $jsonResponse->PatronReadingHistoryGetRows;
				set_time_limit(20 * count($readingHistoryList));
				foreach ($readingHistoryList as $readingHistoryItem) {
					$checkOutDate = $this->parsePolarisDate($readingHistoryItem->CheckOutDate);
					$curTitle = [];
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
					$recordDriver->__destruct();
					$recordDriver = null;
					$readingHistoryTitles[] = $curTitle;
				}
			}
		}

		return [
			'historyActive' => $readingHistoryEnabled,
			'titles' => $readingHistoryTitles,
			'numTitles' => count($readingHistoryTitles),
		];
	}

	public function getCheckouts(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.getCheckouts', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			$itemsOutList = $jsonResponse->PatronItemsOutGetRows;
			foreach ($itemsOutList as $index => $itemOut) {
				if (!$itemOut->ElectronicItem) {
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
					if (isset($itemOut->CanItemBeRenewed)) {
						$curCheckout->canRenew = $itemOut->CanItemBeRenewed;
					} else {
						$curCheckout->canRenew = $itemOut->RenewalCount < $itemOut->RenewalLimit;
					}
					$curCheckout->maxRenewals = $itemOut->RenewalLimit;
					$curCheckout->renewalId = $itemOut->ItemID;
					$curCheckout->renewIndicator = $itemOut->ItemID;

					$curCheckout->title = $itemOut->Title;
					$curCheckout->author = $itemOut->Author;
					$curCheckout->formats = [$itemOut->FormatDescription];
					$curCheckout->callNumber = $itemOut->CallNumber;
					$curCheckout->barcode = $itemOut->Barcode;
					if (!empty($itemOut->Designation)) {
						$curCheckout->callNumber .= ' ' . $itemOut->Designation;
					}

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver((string)$curCheckout->recordId);
					if ($recordDriver->isValid()) {
						$curCheckout->updateFromRecordDriver($recordDriver);
					}

					$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$index";
					$checkedOutTitles[$sortKey] = $curCheckout;
				}
			}
		}
		return $checkedOutTitles;
	}

	public function hasFastRenewAll(): bool {
		return true;
	}

	public function renewAll(User $patron) {
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/0";
		$body = new stdClass();
		$body->Action = 'renew';
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->RenewData = new stdClass();
		$body->RenewData->IgnoreOverrideErrors = $_REQUEST['confirmedRenewal'] ?? false;

		$accountSummary = $this->getAccountSummary($patron);

		$renewResult = [
			'success' => false,
			'message' => [],
			'Renewed' => 0,
			'NotRenewed' => $accountSummary->numCheckedOut,
			'Total' => $accountSummary->numCheckedOut,
		];

		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.renewAll', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
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
						foreach ($checkouts as $checkout) {
							if ($checkout->itemId == $itemId) {
								$title = $checkout->title;
								break;
							}
						}
						$renewResult['message'][] = $title . ':' . $blockRow->ErrorDesc;
					}
				} else {
					$renewResult['success'] = true;
					$renewResult['message'] = translate([
						'text' => 'All titles renewed successfully',
						'isPublicFacing' => true,
					]);
					$renewResult['api']['message'] = translate([
						'text' => 'All titles renewed successfully',
						'isPublicFacing' => true,
					]);
				}
				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
				$patron->forceReloadOfCheckouts();
				$renewResult['success'] = true;
			} else if ($jsonResponse->PAPIErrorCode == -2) {
				$itemRenewResult = $jsonResponse->ItemRenewResult;
				$confirmRenewalFee = false;
				$message = '';
				$apiMessage = '';
				foreach ($itemRenewResult->BlockRows as $blockRow) {
					if (strlen($message) > 0) {
						$message .= "</br>";
						$apiMessage .= "\n";
					}
					$message .= $blockRow->ErrorDesc;
					$apiMessage .= $blockRow->ErrorDesc;

					// Item renewal block
					if ($blockRow->PAPIErrorType == 2) {
						// Confirm charge for renewing item
						if ($blockRow->ErrorAllowOverride) {
							$confirmRenewalFee = true;
						}
					}
				}
				$renewResult['success'] = false;
				$renewResult['message'] = $message;
				$renewResult['confirmRenewalFee'] = $confirmRenewalFee;

				// Result for API or app use
				$renewResult['api']['title'] = translate([
					'text' => 'Confirm Renewal',
					'isPublicFacing' => true,
				]);
				$renewResult['api']['message'] = translate([
					'text' => $message,
					'isPublicFacing' => true,
				]);
				$renewResult['api']['action'] = translate([
					'text' => 'Renew Items',
					'isPublicFacing' => true,
				]);
			} else {
				$message = "All items could not be renewed.";
				$renewResult['message'][] = $message;
			}
		} else {
			$message = "The item could not be renewed";
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			$renewResult['message'][] = $message;
		}
		return $renewResult;
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null) {
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/itemsout/$itemId";
		$body = new stdClass();
		$body->Action = 'renew';
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->RenewData = new stdClass();
		$body->RenewData->IgnoreOverrideErrors = $_REQUEST['confirmedRenewal'] ?? false;

		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.renewCheckout', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$itemRenewResult = $jsonResponse->ItemRenewResult;
				if (isset($itemRenewResult->DueDateRows) && (count($itemRenewResult->DueDateRows) > 0)) {
					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfCheckouts();

					$result['itemId'] = $itemId;
					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'Your item was successfully renewed',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Title renewed successfully',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your item was renewed',
						'isPublicFacing' => true,
					]);

					return $result;
				} else {
					$message = '';
					foreach ($itemRenewResult->BlockRows as $blockRow) {
						if (strlen($message) == 0) {
							$message .= '<br/>';
						}
						$message .= $blockRow->ErrorDesc;
					}
					if (strlen($message) == 0) {
						$message .= "This item could not be renewed";
					}

					$result['itemId'] = $itemId;
					$result['success'] = true;
					$result['message'] = $message;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Unable to renew title',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'This item could not be renewed',
						'isPublicFacing' => true,
					]);

					return $result;
				}
			} else if ($jsonResponse->PAPIErrorCode == -2) {
				$itemRenewResult = $jsonResponse->ItemRenewResult;
				$message = '';
				foreach ($itemRenewResult->BlockRows as $blockRow) {
					$message .= $blockRow->ErrorDesc;

					$userCanOverride = false;
					// Item renewal block
					if ($blockRow->PAPIErrorType == 2) {
						// Confirm charge for renewing item
						$userCanOverride = $blockRow->ErrorAllowOverride;
					}

					if (strlen($message) == 0) {
						$message .= 'This item could not be renewed.';
					}

					$result['itemId'] = $itemId;
					$result['success'] = false;
					$result['message'] = $message;
					$result['confirmRenewalFee'] = $userCanOverride;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Confirm Renewal',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => $message,
						'isPublicFacing' => true,
					]);
					$result['api']['action'] = translate([
						'text' => 'Renew Item',
						'isPublicFacing' => true,
					]);
					$result['api']['confirmRenewalFee'] = translate([
						'text' => $userCanOverride,
						'isPublicFacing' => true,
					]);

					return $result;

				}
			} else {
				$message = "The item could not be renewed. {$jsonResponse->ErrorMessage}";

				$result['itemId'] = $itemIndex;
				$result['success'] = false;
				$result['message'] = $message;

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to renew title',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = $jsonResponse->ErrorMessage;

				return $result;
			}
		} else {
			$message = "The item could not be renewed";
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}

			$result['itemId'] = $itemIndex;
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to renew title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'The item could not be renewed',
				'isPublicFacing' => true,
			]);

			return $result;
		}
	}

	public function getHolds($patron): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		global $library;
		$availableHolds = [];
		$unavailableHolds = [];
		$holds = [
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds,
		];
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.getHolds', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			$holdsList = $jsonResponse->PatronHoldRequestsGetRows;
			foreach ($holdsList as $index => $holdInfo) {
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
				switch ($holdInfo->StatusID) {
					case 1:
						//Frozen
						$curHold->status = $holdInfo->StatusDescription;
						$curHold->reactivateDate = $this->parsePolarisDate($holdInfo->ActivationDate);
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
						$curHold->locationUpdateable = true;
						$curHold->cancelable = true;
						$curHold->expirationDate = $this->parsePolarisDate($holdInfo->PickupByDate);
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
					$curHold->holdQueueLength = $holdInfo->QueueTotal;
					$curHold->position = $holdInfo->QueuePosition;
				}
				$curHold->canFreeze = $holdInfo->CanSuspend;
				$curHold->title = $holdInfo->Title;
				$curHold->author = $holdInfo->Author;
				$curHold->callNumber = $holdInfo->CallNumber;
				if (!empty($holdInfo->Designation)) {
					$curHold->callNumber .= ' ' . $holdInfo->Designation;
				}

				$curPickupBranch = new Location();
				$curPickupBranch->code = $holdInfo->PickupBranchID;
				if ($curPickupBranch->find(true)) {
					$curHold->pickupLocationId = $curPickupBranch->locationId;

					//Polaris does not return the hold area for the hold, instead it updates the pickup branch name
					// to include the name of the hold area. So if the selected location has hold areas, display the
					// information from Polaris.
					if (count($curPickupBranch->getPickupSublocations()) > 0){
						//Polaris does not return the hold area for the hold, instead it updates the pickup branch name
						// to include the name of the hold area.
						$curHold->pickupLocationName = $holdInfo->PickupBranchName;
					}else{
						//We can display the name of the branch which includes any translations
						$curHold->pickupLocationName = $curPickupBranch->displayName;
					}
				} else {
					$curHold->pickupLocationName = $holdInfo->PickupBranchName;
				}

				$curHold->expirationDate = $this->parsePolarisDate($holdInfo->PickupByDate);
				$curHold->position = $holdInfo->QueuePosition;
				$curHold->holdQueueLength = $holdInfo->QueueTotal;
				$curHold->volume = $holdInfo->VolumeNumber;

				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver((string)$curHold->recordId);
				if ($recordDriver->isValid()) {
					$curHold->updateFromRecordDriver($recordDriver);
				}

				$curHold->available = $isAvailable;
				if ($curHold->available) {
					if (!$library->allowChangingPickupLocationForAvailableHolds) {
						$curHold->locationUpdateable = false;
					}
					if (!$library->allowCancellingAvailableHolds) {
						$curHold->cancelable = false;
					}

					$holds['available'][] = $curHold;
				} else {
					$holds['unavailable'][] = $curHold;
				}
			}
		}

		//Get ILL holds
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/illrequests/all";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.illRequests', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			$illRequestList = $jsonResponse->PatronILLRequestsGetRows;
			$illCancelAvailable = true;
			if (!empty($this->accountProfile->apiVersion)) {
				$apiVersionFloat = floatval($this->accountProfile->apiVersion);
				$illCancelAvailable = $apiVersionFloat >= 7;
			}
			foreach ($illRequestList as $index => $illRequestInfo) {
				$curHold = new Hold();
				$curHold->userId = $patron->id;
				$curHold->type = 'ils';
				$curHold->source = $this->getIndexingProfile()->name;
				$curHold->sourceId = $illRequestInfo->ILLRequestID;
				$curHold->recordId = $illRequestInfo->BibRecordID;
				$curHold->cancelId = $illRequestInfo->ILLRequestID;
				$curHold->frozen = false;
				$curHold->locationUpdateable = false;

				$curHold->cancelable = $illCancelAvailable && ($illRequestInfo->ILLStatusID == 1 || $illRequestInfo->ILLStatusID == 2 || $illRequestInfo->ILLStatusID == 3 || $illRequestInfo->ILLStatusID == 5);
				$isAvailable = false;
				$curHold->status = $illRequestInfo->Status;
				switch ($illRequestInfo->ILLStatusID) {
					case 1: //Inactive
					case 2: //Active?
					case 3: //Active
					case 5: //Shipped
					case 11: //Received-Transferred
						$curHold->status = $illRequestInfo->Status;
						break;

					case 10: //Received-Held
						$curHold->status = $illRequestInfo->Status;
						$isAvailable = true;
						$curHold->expirationDate = $this->parsePolarisDate($illRequestInfo->PickupByDate);
						break;
					case 12: //Received-Satisfied
					case 13: //Received-Used
					case 14: //Received-Unused
					case 15: //Returned
					case 16: //Cancelled
						$curHold->status = $illRequestInfo->Status;
						continue 2;
				}
				$curHold->canFreeze = false;
				$curHold->title = $illRequestInfo->Title;
				$curHold->author = $illRequestInfo->Author;
				$curHold->callNumber = $illRequestInfo->CallNumber;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $illRequestInfo->PickupBranchID;
				if ($curPickupBranch->find(true)) {
					$curPickupBranch->fetch();
					$curHold->pickupLocationId = $curPickupBranch->locationId;
					$curHold->pickupLocationName = $curPickupBranch->displayName;
				} else {
					$curHold->pickupLocationName = $illRequestInfo->PickupBranch;
				}
				$curHold->expirationDate = $this->parsePolarisDate($illRequestInfo->PickupByDate);
				$curHold->volume = $illRequestInfo->VolumeAndIssue;
				$curHold->format = $illRequestInfo->Format;
				$curHold->isIll = true;

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

	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null, $pickupSublocation = null) {
		return $this->placeItemHold($patron, $recordId, null, $pickupBranch, $cancelDate, $pickupSublocation);
	}

	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null, $pickupSublocation = null) {
		if (strpos($recordId, ':') !== false) {
			[
				,
				$shortId,
			] = explode(':', $recordId);
		} else {
			$shortId = $recordId;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest";
		$body = new stdClass();
		$body->PatronID = (int)$patron->unique_ils_id;
		$body->BibID = (int)$shortId;
		if (!empty($itemId)) {
			//Check to see if we also have a volume
			$relatedRecord = $record->getRelatedRecord();
			foreach ($relatedRecord->getItems() as $item) {
				if ($item->itemId == $itemId) {
					if (!empty($item->volume)) {
						//Volume holds just need the volume
						$body->VolumeNumber = $item->volume;
					} else {
						$marcRecord = $record->getMarcRecord();
						//If we place a hold on just an item, we need a barcode for the item rather than the record number
						/** @var File_MARC_Data_Field[] $marcItems */
						$marcItems = $marcRecord->getFields($this->getIndexingProfile()->itemTag);
						foreach ($marcItems as $marcItem) {
							$itemSubField = $marcItem->getSubfield($this->getIndexingProfile()->itemRecordNumber);
							if ($itemSubField->getData() == $itemId) {
								$barcodeSubfield = $marcItem->getSubfield($this->getIndexingProfile()->barcode);
								if ($barcodeSubfield != null) {
									$body->ItemBarcode = $barcodeSubfield->getData();
									break;
								}
							}
						}
					}
					break;
				}
			}
		}
		$body->PickupOrgID = (int)$pickupBranch;

		if ($pickupSublocation) {
			$body->HoldPickupAreaID = (int)$pickupSublocation;
		}else{
			//Check to see if there is only 1 valid pickup area for the location. If so, pass that as the pickup area
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)) {
				$sublocations = $location->getPickupSublocations($patron);
				if (count($sublocations) == 1) {
					$firstSublocation = reset($sublocations);
					$body->HoldPickupAreaID = (int)$firstSublocation->ilsId;
				}else{
					$sublocationIsValid = false;
					if ($patron->rememberHoldPickupLocation) {
						foreach ($sublocations as $sublocation) {
							if ($sublocation->id == $patron->pickupSublocationId) {
								$sublocationIsValid = true;
								$body->HoldPickupAreaID = (int)$sublocation->ilsId;
								break;
							}
						}
					}
					if (!$sublocationIsValid) {
						$firstSublocation = reset($sublocations);
						$body->HoldPickupAreaID = (int)$firstSublocation->ilsId;
					}
				}
			}
		}

		//Need to set the Workstation
		$body->WorkstationID = $this->getWorkstationID($patron);
		//Get the ID of the staff user
		$staffUserInfo = $this->getStaffUserInfo();
		$body->UserID = (int)$staffUserInfo['polarisId'];
		$body->RequestingOrgID = (int)$patron->getHomeLocationCode();
		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', $encodedBody);
		ExternalRequestLogEntry::logRequest('polaris.placeHold', 'POST', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
		$hold_result = $this->processHoldRequestResponse($response, $patron);
		if ($hold_result['success']) {
			//Force the title to be re-indexed when a hold is placed to ensure the
			require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
			$recordIdentifierToReload = new RecordIdentifiersToReload();
			$recordIdentifierToReload->type = $this->getIndexingProfile()->name;
			$recordIdentifierToReload->identifier = $shortId;
			$recordIdentifierToReload->insert();
		}

		$hold_result['title'] = $title;
		$hold_result['bid'] = $shortId;

		return $hold_result;
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation = null) {
		if (strpos($recordId, ':') !== false) {
			[
				,
				$shortId,
			] = explode(':', $recordId);
		} else {
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
		$body->PatronID = (int)$patron->unique_ils_id;
		$body->BibID = (int)$recordId;
		$body->PickupOrgID = (int)$pickupBranch;
		$body->VolumeNumber = $volumeId;
		//Need to set the Workstation
		$body->WorkstationID = $this->getWorkstationID($patron);
		//Get the ID of the staff user
		$staffUserInfo = $this->getStaffUserInfo();
		$body->UserID = (int)$staffUserInfo['polarisId'];
		$body->RequestingOrgID = (int)$patron->getHomeLocationCode();

		if ($pickupSublocation) {
			$body->HoldPickupAreaID = (int)$pickupSublocation;
		}

		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', $encodedBody);
		ExternalRequestLogEntry::logRequest('polaris.placeVolumeHold', 'POST', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
		$hold_result = $this->processHoldRequestResponse($response, $patron);

		$hold_result['title'] = $title;
		$hold_result['bid'] = $shortId;

		return $hold_result;
	}

	public function confirmHold(User $patron, $recordId, $confirmationId) {
		if (strpos($recordId, ':') !== false) {
			[
				,
				$shortId,
			] = explode(':', $recordId);
		} else {
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
			'message' => 'Unknown error confirming the hold',
			'api' => [
				'title' => 'Unable to place hold',
				'message' => 'Unknown error confirming the hold',
			],
		];
		require_once ROOT_DIR . '/sys/ILS/HoldRequestConfirmation.php';
		$holdRequestConfirmation = new HoldRequestConfirmation();
		$holdRequestConfirmation->id = $confirmationId;
		if ($holdRequestConfirmation->find(true)) {
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/holdrequest/" . $holdRequestConfirmation->requestId;
			$confirmationInfo = json_decode($holdRequestConfirmation->additionalParams);
			$body = new stdClass();
			$body->TxnGroupQualifier = $confirmationInfo->groupQualifier;
			$body->TxnQualifier = $confirmationInfo->qualifier;
			$body->RequestingOrgID = (int)$patron->getHomeLocationCode();
			$body->Answer = 1;
			$body->State = $confirmationInfo->state;
			$encodedBody = json_encode($body);
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', '', $encodedBody);
			ExternalRequestLogEntry::logRequest('polaris.placeHold', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
			$result = $this->processHoldRequestResponse($response, $patron);

			$result['title'] = $title;
			$result['bid'] = $shortId;

		} else {
			$result['message'] = translate([
				'text' => 'Could not find information about the hold to be confirmed, it may have been confirmed already',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Could not find information about the hold to be confirmed, it may have been confirmed already',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false): array {
		if (!$isIll) {
			$staffInfo = $this->getStaffUserInfo();
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$cancelId/cancelled?wsid={$this->getWorkstationID($patron)}&userid={$staffInfo['polarisId']}";
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.cancelHold', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		} else {
			$staffInfo = $this->getStaffUserInfo();
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/illrequests/$cancelId/cancelled?wsid={$this->getWorkstationID($patron)}&userid={$staffInfo['polarisId']}";
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.cancelIllHold', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		}
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
				$patron->forceReloadOfHolds();
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'The hold has been cancelled.',
					'isPublicFacing' => true,
				]);;

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Hold cancelled',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Your hold has been cancelled.',
					'isPublicFacing' => true,
				]);

				return $result;
			} else {
				$message = "The hold could not be cancelled. {$jsonResponse->ErrorMessage}";
				$result['success'] = false;
				$result['message'] = $message;

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to cancel hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = $jsonResponse->ErrorMessage;

				return $result;
			}
		} else {
			$message = "The hold could not be cancelled.";
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold not cancelled',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'The hold could not be cancelled.',
				'isPublicFacing' => true,
			]);

			return $result;
		}
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$barcodesToTest = [];
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
		$barcodesToTest = array_unique($barcodesToTest);

		foreach ($barcodesToTest as $i => $barcode) {
			$sessionInfo = $this->loginViaWebService($username, $password);

			if ($sessionInfo['userValid']) {
				//Load user data
				return $this->loadPatronBasicData($username, $password, $sessionInfo['patronId']);
			}
		}
		return null;
	}

	private function loadPatronBasicData(string $patronBarcode, ?string $password, $patronId, bool $fromMasquerade = false, ?User $user = null) {
		$patronBasicData = $this->getBasicDataResponse($patronBarcode, $password, $fromMasquerade);
		if ($patronBasicData != null) {
			if ($user == null) {
				$userExistsInDB = false;
				$user = new User();
				$user->source = $this->accountProfile->name;
				$user->username = $patronId;
				$user->unique_ils_id = $patronId;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}
			} else {
				$userExistsInDB = isset($user->id);
			}
			$user->cat_username = $patronBarcode;
			$user->ils_barcode = $patronBarcode;
			if (!empty($password)) {
				$user->cat_password = $password;
				$user->ils_password = $password;
			}
			$user->patronType = $patronBasicData->PatronCodeID;

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
			if ($user->phone == null) {
				$user->phone = $patronBasicData->PhoneNumber2;
				if ($user->phone == null) {
					$user->phone = $patronBasicData->PhoneNumber3;
				}
			}
			if (is_null($patronBasicData->EmailAddress)) {
				$user->email = '';
			} else {
				$user->email = $patronBasicData->EmailAddress;
			}

			$addresses = $patronBasicData->PatronAddresses;
			if (count($addresses) > 0) {
				$address = reset($addresses);
				$user->_address1 = $address->StreetOne;
				$user->_address2 = $address->StreetTwo;
				$user->_city = $address->City;
				$user->_state = $address->State;
				$user->_zip = $address->PostalCode;
			}

			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patronBarcode}/circulationblocks";
			$circulateBlocksResponse = $this->getWebServiceResponse($polarisUrl, 'GET', Polaris::$accessTokensForUsers[$patronBarcode]['accessToken'], false, $fromMasquerade);
			ExternalRequestLogEntry::logRequest('polaris.getCirculationBlocks', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $circulateBlocksResponse, []);
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
						$homeLocationChanged = false;
						if ($user->homeLocationId != $location->locationId) {
							$homeLocationChanged = true;
						}
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

						if ($homeLocationChanged) {
							//reset the patrons preferred pickup location to their new home library
							$user->setPickupLocationId($user->homeLocationId);
							$user->setRememberHoldPickupLocation(0);
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
				ExternalRequestLogEntry::logRequest('polaris.loadPreferences', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
				if ($response && $this->lastResponseCode == 200) {
					$jsonResponse = json_decode($response);
					$user->trackReadingHistory = $jsonResponse->PatronPreferences->ReadingListEnabled;
				}

				$user->created = date('Y-m-d');
				if (!$user->insert()) {
					return null;
				}
			}
			return $user;
		} else {
			return null;
		}
	}

	/**
	 * @param $username
	 * @param $password
	 *
	 * @return array
	 */
	protected function loginViaWebService(string &$username, ?string $password, $fromMasquerade = false): array {
		if (array_key_exists($username, Polaris::$accessTokensForUsers)) {
			return Polaris::$accessTokensForUsers[$username];
		} else {
			$staffUserInfo = $this->getStaffUserInfo();

			$session = [
				'userValid' => false,
				'accessToken' => false,
				'patronId' => false,
			];

			//Validate that the patron exists. This can also be used to get the barcode for the user based on username
			$polarisUrl = '/PAPIService/REST/public/v1/1033/100/1/patron/' . $username;
			$validatePatronResponseRaw = $this->getWebServiceResponse($polarisUrl, 'GET', $staffUserInfo['accessSecret'], false, true);
			ExternalRequestLogEntry::logRequest('polaris.validatePatron', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $validatePatronResponseRaw, ['staffAccessSecret' => $staffUserInfo['accessSecret']]);
			$patronValidationDone = false;
			if ($validatePatronResponseRaw) {
				$validationResponse = json_decode($validatePatronResponseRaw);
				if ($validationResponse->PAPIErrorCode == -3000) {
					$patronValidationDone = true;
					$session = [
						'userValid' => true,
						'accessToken' => '',
						'patronId' => '',
					];
				} elseif (!empty($validationResponse->PatronBarcode) && $validationResponse->PatronBarcode != $username) {
					$username = $validationResponse->PatronBarcode;
				}
			}

			$authenticationData = new stdClass();
			$authenticationData->Barcode = $username;
			$authenticationData->Password = $password;

			if (!$patronValidationDone) {
				$body = json_encode($authenticationData);
				$polarisUrl = '/PAPIService/REST/public/v1/1033/100/1/authenticator/patron';
				$authenticationResponseRaw = $this->getWebServiceResponse($polarisUrl, 'POST', '', $body, $fromMasquerade);
				ExternalRequestLogEntry::logRequest('polaris.authenticatePatron', 'POST', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $authenticationResponseRaw, ['password' => $password]);
				if ($authenticationResponseRaw) {
					$authenticationResponse = json_decode($authenticationResponseRaw);
					if (empty($authenticationResponse->PAPIErrorCode) || $authenticationResponse->PAPIErrorCode == 0) {
						$accessToken = $authenticationResponse->AccessToken ?? null;
						$patronId = $authenticationResponse->PatronID ?? null;
						if ($accessToken === null) {
							global $logger;
							$logger->log('Polaris authentication error: AccessToken is null. Raw response: ' . PHP_EOL . print_r($authenticationResponse, true), Logger::LOG_ERROR);
						}
						if ($patronId === null) {
							global $logger;
							$logger->log('Polaris authentication error: PatronID is null. Raw response: ' . PHP_EOL . print_r($authenticationResponse, true), Logger::LOG_ERROR);
						}
						$session = [
							'userValid' => true,
							'accessToken' => $accessToken,
							'patronId' => $patronId,
						];
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

	private function getAccessToken(string $barcode, ?string $password, bool $fromMasquerade = false) {
		//Get the session token for the user
		if (isset(Polaris::$accessTokensForUsers[$barcode])) {
			return Polaris::$accessTokensForUsers[$barcode]['accessToken'];
		} else {
			$sessionInfo = $this->loginViaWebService($barcode, $password, $fromMasquerade);
			return $sessionInfo['accessToken'];
		}
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array {
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToFreezeId/inactive";
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		if ($dateToReactivate == null) {
			//User didn't pick a specific date, pick a day that is 2 years from now.
			$curTime = time();
			$curTime += 365 * 2 * 24 * 60 * 60;
			$formattedTime = date('Y-m-d\TH:i:s.00', $curTime);
			$body->ActivationDate = $formattedTime;
		} else {
			$body->ActivationDate = $dateToReactivate;
		}

		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), $encodedBody, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.freezeHold', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'The hold has been frozen.',
					'isPublicFacing' => true,
				]);

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Hold frozen successfully',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Your hold has been frozen.',
					'isPublicFacing' => true,
				]);

				return $result;
			} else {
				$message = "The hold could not be frozen. {$jsonResponse->ErrorMessage}";
				$result['success'] = false;
				$result['message'] = $message;

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Hold not frozen',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = $jsonResponse->ErrorMessage;

				return $result;
			}
		} else {
			$message = "The hold could not be frozen.";
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold not frozen',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, your hold could not be frozen.',
				'isPublicFacing' => true,
			]);

			return $result;
		}
	}

	function thawHold(User $patron, $recordId, $itemToThawId): array {
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToThawId/active";
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), json_encode($body), UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.thawHold', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'The hold has been thawed.',
					'isPublicFacing' => true,
				]);;

				$result['api']['title'] = translate([
					'text' => 'Hold thawed',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Your hold has been thawed.',
					'isPublicFacing' => true,
				]);

				return $result;
			} else {
				$message = "The hold could not be thawed. {$jsonResponse->ErrorMessage}";
				$result['success'] = false;
				$result['message'] = $message;

				$result['api']['title'] = translate([
					'text' => 'Unable to thaw hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = $jsonResponse->ErrorMessage;

				return $result;
			}
		} else {
			$message = "The hold could not be thawed.";
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}

			$result['success'] = false;
			$result['message'] = $message;

			$result['api']['title'] = translate([
				'text' => 'Unable to thaw hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your hold could not be thawed.',
				'isPublicFacing' => true,
			]);

			return $result;
		}
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation, $newPickupSublocation = null): array {
		//Polaris is currently unable to change a pickup area unless the pickup location does as well.
		// we will return a good message if this is the case.
		$existingHolds = $this->getHolds($patron);
		$allHolds = array_merge($existingHolds['available'], $existingHolds['unavailable']);

		//Get the aspen pickup location id rather than polar
		$location = new Location();
		$location->code = $newPickupLocation;
		if ($location->find(true)) {
			/** @var Hold $hold */
			foreach ($allHolds as $hold) {
				if ($hold->sourceId == $itemToUpdateId) {
					if ($hold->pickupLocationId == $location->locationId && !empty($newPickupSublocation)) {
						$message = translate([
							'text' => 'To change pickup area within the branch, please contact the library.',
							'isPublicFacing' => true,
						]);
						$result['success'] = false;
						$result['message'] = $message;

						// Result for API or app use
						$result['api']['title'] = translate([
							'text' => 'Unable to update pickup area',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = $message;
						return $result;
					}
					break;
				}
			}
		}

		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/holdrequests/$itemToUpdateId/pickupbranch?wsid={$this->getWorkstationID($patron)}&userid={$staffInfo['polarisId']}&pickupbranchid=$newPickupLocation";
		if (!empty($newPickupSublocation)) {
			$polarisUrl .= "&holdpickupareaid=$newPickupSublocation";
		}
		$body = new stdClass();
		$body->UserID = $staffInfo['polarisId'];
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.changePickupLocation', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$patron->forceReloadOfHolds();
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'The pickup location of your hold was changed successfully.',
					'isPublicFacing' => true,
				]);

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Pickup location updated',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'The pickup location of your hold was changed successfully.',
					'isPublicFacing' => true,
				]);

				return $result;
			} else {
				$message = translate([
						'text' => 'Sorry, the pickup location of your hold could not be changed.',
						'isPublicFacing' => true,
					]) . " {$jsonResponse->ErrorMessage}";
				$result['success'] = false;
				$result['message'] = $message;

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to update pickup location',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = $jsonResponse->ErrorMessage;

				return $result;
			}
		} else {
			$message = translate([
				'text' => 'Sorry, the pickup location of your hold could not be changed.',
				'isPublicFacing' => true,
			]);
			if (IPAddress::showDebuggingInformation()) {
				$message .= " (HTTP Code: {$this->lastResponseCode})";
			}
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to update pickup location',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, the pickup location of your hold could not be changed.',
				'isPublicFacing' => true,
			]);

			return $result;
		}
	}

	/**
	 * @param User $patron
	 * @param bool $canUpdateContactInfo
	 * @param boolean $fromMasquerade
	 * @return array
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade): array {
		global $library;
		$result = [
			'success' => false,
			'messages' => [],
		];
		if ($canUpdateContactInfo) {
			$staffInfo = $this->getStaffUserInfo();
			$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}";
			$body = new stdClass();
			$body->LogonBranchID = $patron->getHomeLocationCode();
			$body->LogonUserID = (string)$staffInfo['polarisId'];
			$body->LogonWorkstationID = $this->getWorkstationID($patron);

			//User the patron's home library rather than the active library just in case they are on the wrong interface
			$library = $patron->getHomeLibrary();
			$this->setupBodyForSelfRegAndPatronUpdateCall('patronUpdate', $body, $library);
			if (isset($_REQUEST['email'])) {
				$patron->email = $_REQUEST['email'];
			}

			if (isset($_REQUEST['phone'])) {
				$patron->phone = $_REQUEST['phone1'];
			}

			$patronBasicData = $this->getBasicDataResponse($patron->getBarcode(), $patron->getPasswordOrPin(), $fromMasquerade);
			//Get the ID of the address to update
			$addresses = $patronBasicData->PatronAddresses;
			$address = null;
			if (count($addresses) > 0) {
				$address = reset($addresses);
				$body->AddressID = $address->AddressID;
				//$body->FreeTextID = $address->FreeTextLabel;
			}

			if (isset($_REQUEST['address'])) {
				$patron->_address1 = $_REQUEST['address'];
			}

			if (isset($_REQUEST['address2'])) {
				$patron->_address2 = $_REQUEST['address2'];
			}

			if (isset($_REQUEST['city'])) {
				$patron->_city = $_REQUEST['city'];
			}

			if (isset($_REQUEST['state'])) {
				$patron->_state = $_REQUEST['state'];
			}

			if (isset($_REQUEST['zip'])) {
				$patron->_zip = $_REQUEST['zip'];
			}

			if (count($addresses) > 0) {
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

			if (!$library->allowNameUpdates) {
				unset($body->NameFirst);
				unset($body->NameLast);
				unset($body->NameMiddle);
				unset($body->LegalNameFirst);
				unset($body->LegalNameMiddle);
				unset($body->LegalNameLast);
				unset($body->UseLegalNameOnNotices);
			} else {
				if ($body->NameFirst == $patronBasicData->NameFirst) {
					unset($body->NameFirst);
				}
				if ($body->NameLast == $patronBasicData->NameLast) {
					unset($body->NameLast);
				}
				if ($body->NameMiddle == $patronBasicData->NameMiddle) {
					unset($body->NameMiddle);
				}
				if ($body->LegalNameFirst == $patronBasicData->LegalNameFirst) {
					unset($body->LegalNameFirst);
				}
				if ($body->LegalNameMiddle == $patronBasicData->LegalNameMiddle) {
					unset($body->LegalNameMiddle);
				}
				if ($body->LegalNameLast == $patronBasicData->LegalNameLast) {
					unset($body->LegalNameLast);
				}
			}

			if (!$library->allowPatronAddressUpdates) {
				unset($body->PostalCode);
				unset($body->City);
				unset($body->State);
				unset($body->StreetOne);
				unset($body->StreetTwo);
				unset($body->StreetThree);
			} else {
				if ($address != null) {
					if ($body->PostalCode == $address->PostalCode) {
						unset($body->PostalCode);
					}
					if ($body->City == $address->City) {
						unset($body->City);
					}
					if ($body->State == $address->State) {
						unset($body->State);
					}
					if ($body->StreetOne == $address->StreetOne) {
						unset($body->StreetOne);
					}
					if ($body->StreetTwo == $address->StreetTwo) {
						unset($body->StreetTwo);
					}
					if ($body->StreetThree == $address->StreetThree) {
						unset($body->StreetThree);
					}
					if ($body->ZipPlusFour == $address->ZipPlusFour) {
						unset($body->ZipPlusFour);
					}
					if ($body->County == $address->County) {
						unset($body->County);
					}
				}
			}
			if (!$library->allowPatronPhoneNumberUpdates) {
				unset($body->PhoneVoice1);
				unset($body->PhoneVoice2);
				unset($body->PhoneVoice3);
				unset($body->PhoneVoice1Carrier);
				unset($body->PhoneVoice2Carrier);
				unset($body->PhoneVoice3Carrier);
				unset($body->TxtPhoneNumber);
			} else {
				if ($body->PhoneVoice1 == $patronBasicData->PhoneVoice1) {
					unset($body->PhoneVoice1);
				}
				if ($body->PhoneVoice2 == $patronBasicData->PhoneVoice2) {
					unset($body->PhoneVoice2);
				}
				if ($body->PhoneVoice3 == $patronBasicData->PhoneVoice3) {
					unset($body->PhoneVoice3);
				}
				if ($body->PhoneVoice1Carrier == $patronBasicData->PhoneVoice1Carrier) {
					unset($body->PhoneVoice1Carrier);
				}
				if ($body->PhoneVoice2Carrier == $patronBasicData->PhoneVoice2Carrier) {
					unset($body->PhoneVoice2Carrier);
				}
				if ($body->PhoneVoice3Carrier == $patronBasicData->PhoneVoice3Carrier) {
					unset($body->PhoneVoice3Carrier);
				}
				if ($body->TxtPhoneNumber == $patronBasicData->TxtPhoneNumber) {
					unset($body->TxtPhoneNumber);
				}
			}
			if (!$library->showNoticeTypeInProfile) {
				unset($body->DeliveryOptionID);
				unset($body->EReceiptOptionID);
			} else {
				if ($body->DeliveryOptionID == $patronBasicData->DeliveryOptionID) {
					unset($body->DeliveryOptionID);
				}
				if ($body->EReceiptOptionID == $patronBasicData->EReceiptOptionID) {
					unset($body->EReceiptOptionID);
				}
			}
			$encodedBody = json_encode($body);
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), $encodedBody, $fromMasquerade || UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.updatePatronInfo', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->PAPIErrorCode == 0) {
					//Check to see if we need to update the username


					$result['success'] = true;
					$result['messages'][] = 'Your account was updated successfully.';
					$patron->update();
				} else {
					$result['messages'][] = "Error updating profile information (Error {$jsonResponse->PAPIErrorCode}). {$jsonResponse->ErrorMessage}";
				}
			} else {
				$result['messages'][] = "Error updating profile information ({$this->lastResponseCode}).";
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}
		return $result;
	}

	function updatePin(User $patron, ?string $oldPin, string $newPin) {
		if ($patron->ils_password != $oldPin && $patron->cat_password != $oldPin) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'The old PIN provided is incorrect.',
					'isPublicFacing' => true,
				]),
			];
		}
		$result = [
			'success' => false,
			'message' => "Unknown error updating password.",
		];
		$staffInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}";
		$body = new stdClass();
		$body->LogonBranchID = $patron->getHomeLocationCode();
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		$body->LogonWorkstationID = $this->getWorkstationID($patron);
		$body->Password = $newPin;
		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $staffInfo['accessSecret'], $encodedBody, true);
		ExternalRequestLogEntry::logRequest('polaris.updatePin', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, ['newPin' => $newPin]);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			if ($jsonResponse->PAPIErrorCode == 0) {
				$result['success'] = true;
				$result['message'] = 'Your password was updated successfully.';
				$patron->cat_password = $newPin;
				$patron->ils_password = $newPin;
				$patron->update();
			} else {
				$result['message'] = "Error updating your password. (Error {$jsonResponse->PAPIErrorCode}).";
			}
		} else {
			$result['message'] = "Error updating your password. ({$this->lastResponseCode}).";
		}
		return $result;
	}

	public function getFines(User $patron, $includeMessages = false): array {
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/account/outstanding";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		$fines = [];
		ExternalRequestLogEntry::logRequest('polaris.getFines', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResponse = json_decode($response);
			$finesRows = $jsonResponse->PatronAccountGetRows;
			foreach ($finesRows as $fineRow) {
				if ($fineRow->TransactionTypeDescription != "Credit") {
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
		}
		return $fines;
	}

	function showOutstandingFines() {
		return true;
	}

	public function completeFinePayment(User $patron, UserPayment $payment) {
		$staffUserInfo = $this->getStaffUserInfo();
		$result = [
			'success' => false,
			'message' => '',
		];
		$finePayments = explode(',', $payment->finesPaid);
		$allPaymentsSucceed = true;
		$orgId = $patron->getHomeLocationCode();
		foreach ($finePayments as $finePayment) {
			[
				$fineId,
				$paymentAmount,
			] = explode('|', $finePayment);
			$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/$orgId/{$staffUserInfo['accessToken']}/patron/{$patron->getBarcode()}/account/{$fineId}/pay?wsid={$this->getWorkstationID($patron)}&userid={$staffUserInfo['polarisId']}";
			$body = new stdClass();
			$body->TxnAmount = $paymentAmount;
			$body->PaymentMethodID = 12;
			$body->FreeTextNote = 'Paid Online via Aspen Discovery';

			$encodedBody = json_encode($body);
			$response = $this->getWebServiceResponse($polarisUrl, 'PUT', $staffUserInfo['accessSecret'], $encodedBody, UserAccount::isUserMasquerading());
			ExternalRequestLogEntry::logRequest('polaris.completeFinePayment', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);

			if ($response && $this->lastResponseCode == 200) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse->PAPIErrorCode != 0) {
					$result['message'] .= $jsonResponse->ErrorMessage . '. ';
					$allPaymentsSucceed = false;
				}
			} else {
				$result['message'] .= 'Error paying fine in Polaris ' . $this->lastResponseCode . '. ';
				$allPaymentsSucceed = false;
			}
		}
		if ($allPaymentsSucceed) {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your fines have been paid successfully, thank you.',
				'isPublicFacing' => true,
			]);
		}

		global $logger;
		$logger->log("Marked fines as paid within Polaris for user {$patron->id}, {$result['message']}", Logger::LOG_ERROR);

		return $result;
	}

	public function getWebServiceResponse($query, $method = 'GET', $patronPassword = '', $body = false, $actAsStaff = false) {
		// auth has to be in GMT, otherwise use config-level TZ
		$site_config_TZ = date_default_timezone_get();
		date_default_timezone_set('GMT');
		$date = date("D, d M Y H:i:s T");
		date_default_timezone_set($site_config_TZ);

		$url = $this->getWebServiceURL() . $query;

		if ($actAsStaff) {
			$staffUserInfo = $this->getStaffUserInfo();
			$patronPassword = $staffUserInfo['accessSecret'];
		}
		$signature_text = $method . $url . $date . $patronPassword;
		$signature = base64_encode(hash_hmac('sha1', $signature_text, $this->accountProfile->oAuthClientSecret, true));

		$auth_token = "PWS {$this->accountProfile->oAuthClientId}:$signature";
		$this->apiCurlWrapper->addCustomHeaders([
			"Content-type: application/json",
			"Accept: application/json",
			"PolarisDate: $date",
			"Authorization: $auth_token",
		], true);
		if ($actAsStaff) {
			$staffUserInfo = $this->getStaffUserInfo();
			$this->apiCurlWrapper->addCustomHeaders([
				'X-PAPI-AccessToken:' . $staffUserInfo['accessToken'],
			], false);
		}

		if ($method == 'GET') {
			$response = $this->apiCurlWrapper->curlGetPage($url);
		} else {
			$response = $this->apiCurlWrapper->curlSendPage($url, $method, $body);
		}
		$this->lastResponseCode = $this->apiCurlWrapper->getResponseCode();

		return $response;
	}

	private $lastResponseCode;

	private function parsePolarisDate($polarisDate) {
		if (preg_match('%/Date\((\d{13})([+-]\d{4})\)/%i', $polarisDate, $matches)) {
			$timestamp = $matches[1] / 1000;
			//$timezoneOffset = $matches[2];
			//TODO: Adjust for timezone offset
			return $timestamp;
		} else {
			return 0;
		}
	}

	private function getStaffUserInfo() : array|false {
		if (is_null(Polaris::$accessTokenForStaff)) {
			$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/1/authenticator/staff";
			$authenticationData = new stdClass();
			$authenticationData->Domain = $this->accountProfile->domain;
			$authenticationData->Username = $this->accountProfile->staffUsername;
			$authenticationData->Password = $this->accountProfile->staffPassword;

			$encodedBody = json_encode($authenticationData);
			$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', $encodedBody);
			ExternalRequestLogEntry::logRequest('polaris.getStaffUserInfo', 'POST', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, ['staffPassword' => $this->accountProfile->staffPassword]);
			if ($response) {
				$jsonResponse = json_decode($response);
				Polaris::$accessTokenForStaff = [
					'accessToken' => $jsonResponse->AccessToken,
					'accessSecret' => $jsonResponse->AccessSecret,
					'polarisId' => $jsonResponse->PolarisUserID,
				];
			} else {
				Polaris::$accessTokenForStaff = false;
			}
		}
		return Polaris::$accessTokenForStaff;
	}

	public function findNewUser($patronBarcode, $patronUsername) {
		$staffUserInfo = $this->getStaffUserInfo();

		//Validate that the patron exists. This can also be used to get the barcode for the user based on username
		if (!empty($patronBarcode)) {
			$polarisUrl = '/PAPIService/REST/public/v1/1033/100/1/patron/' . $patronBarcode;
			$validatePatronResponseRaw = $this->getWebServiceResponse($polarisUrl, 'GET', $staffUserInfo['accessSecret'], false, true);
			$patronId = false;
			ExternalRequestLogEntry::logRequest('polaris.findNewUser', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $validatePatronResponseRaw, ['accessSecret' => $staffUserInfo['accessSecret']]);
			if ($validatePatronResponseRaw) {
				$validationResponse = json_decode($validatePatronResponseRaw);
				if ($validationResponse->PAPIErrorCode != -3000) {
					//Since we're testing barcode the patron barcode should match.
					if (!empty($validationResponse->PatronBarcode) && $validationResponse->PatronBarcode != $patronBarcode) {
						//Since we're testing barcode the patron barcode should match.
						return false;
					}
					$patronId = $validationResponse->PatronID;
				}
			}
		} else {
			$polarisUrl = '/PAPIService/REST/public/v1/1033/100/1/patron/' . $patronUsername;
			$validatePatronResponseRaw = $this->getWebServiceResponse($polarisUrl, 'GET', $staffUserInfo['accessSecret'], false, true);
			$patronId = false;
			ExternalRequestLogEntry::logRequest('polaris.findNewUser', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $validatePatronResponseRaw, ['accessSecret' => $staffUserInfo['accessSecret']]);
			if ($validatePatronResponseRaw) {
				$validationResponse = json_decode($validatePatronResponseRaw);
				if ($validationResponse->PAPIErrorCode != -3000) {
					if (!empty($validationResponse->PatronBarcode)) {
						//Need to get the patron barcode
						$patronBarcode = $validationResponse->PatronBarcode;
					} else {
						return false;
					}
					$patronId = $validationResponse->PatronID;
				}
			}
		}

		//Load user data
		if ($patronId != false) {
			return $this->loadPatronBasicData($patronBarcode, '', $patronId, true);
		} else {
			return false;
		}
	}

	public function findNewUserByEmail($patronEmail): mixed {
		return false;
	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user) {
		$this->loadPatronBasicData($user->getBarcode(), $user->getPasswordOrPin(), $user->unique_ils_id, UserAccount::isUserMasquerading(), $user);
	}

	private function getWorkstationID(User $patron): int {
		$homeLibrary = $patron->getHomeLibrary();
		if (empty($homeLibrary->workstationId)) {
			return (int)$this->accountProfile->workstationId;
		} else {
			return (int)$homeLibrary->workstationId;
		}

	}

	/**
	 * @param $response
	 * @param User $patron
	 * @return array
	 */
	private function processHoldRequestResponse($response, User $patron): array {
		$hold_result = [];
		if ($response && $this->lastResponseCode == 200) {
			$jsonResult = json_decode($response);
			if ($jsonResult->PAPIErrorCode != 0) {
				$hold_result['success'] = false;
				$hold_result['message'] = translate([
					'text' => 'Your hold could not be placed.',
					'isPublicFacing' => true,
				]);
				if (IPAddress::showDebuggingInformation()) {
					$hold_result['message'] .= " ({$jsonResult->PAPIErrorCode})";
				}
				// Result for API or app use
				$hold_result['api']['title'] = translate([
					'text' => 'Unable to place hold',
					'isPublicFacing' => true,
				]);
				$hold_result['api']['message'] = translate([
					'text' => 'Your hold could not be placed.',
					'isPublicFacing' => true,
				]);
			} else {
				if ($jsonResult->StatusType == 1) {
					$hold_result['success'] = false;
					$hold_result['message'] = translate([
						'text' => 'Your hold could not be placed. ' . $jsonResult->Message,
						'isPublicFacing' => true,
					]);
					$hold_result['api']['message'] = translate([
						'text' => 'Your hold could not be placed. ' . $jsonResult->Message,
						'isPublicFacing' => true,
					]);
				} elseif ($jsonResult->StatusType == 2) {
					$hold_result['success'] = true;
					$hold_result['message'] = translate([
						'text' => "Your hold was placed successfully.",
						'isPublicFacing' => true,
					]);
					$hold_result['api']['message'] = translate([
						'text' => 'Your hold was placed successfully.',
						'isPublicFacing' => true,
					]);
					if (isset($jsonResult->QueuePosition)) {
						$hold_result['message'] .= '&nbsp;' . translate([
								'text' => "You are number <b>%1%</b> in the queue.",
								'1' => $jsonResult->QueuePosition,
								'isPublicFacing' => true,
							]);
						$hold_result['api']['message'] .= ' ' . translate([
								'text' => 'You are number <b>%1%</b> in the queue.',
								'1' => $jsonResult->QueuePosition,
								'isPublicFacing' => true,
							]);
					}
					// Result for API or app use
					$hold_result['api']['title'] = translate([
						'text' => 'Hold placed successfully',
						'isPublicFacing' => true,
					]);
					$hold_result['api']['action'] = translate([
						'text' => 'Go to Holds',
						'isPublicFacing' => true,
					]);;
					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				} elseif ($jsonResult->StatusType == 3) {
					$hold_result['success'] = false;
					$hold_result['confirmationNeeded'] = true;
					$hold_result['title'] = translate([
						'text' => "Place Hold?",
						'isPublicFacing' => true,
					]);
					require_once ROOT_DIR . '/sys/ILS/HoldRequestConfirmation.php';
					$holdRequestConfirmation = new HoldRequestConfirmation();
					$holdRequestConfirmation->userId = $patron->id;
					$holdRequestConfirmation->requestId = $jsonResult->RequestGUID;
					$holdRequestConfirmation->additionalParams = json_encode([
						'requestId' => $jsonResult->RequestGUID,
						'groupQualifier' => $jsonResult->TxnGroupQualifer,
						'qualifier' => $jsonResult->TxnQualifier,
						'state' => $jsonResult->StatusValue,
					]);
					$holdRequestConfirmation->insert();
					$hold_result['confirmationId'] = $holdRequestConfirmation->id;

					$hold_result['message'] = translate([
						'text' => $jsonResult->Message,
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$hold_result['api']['title'] = translate([
						'text' => 'Place Hold?',
						'isPublicFacing' => true,
					]);
					$hold_result['api']['message'] = $jsonResult->Message;
					$hold_result['api']['confirmationNeeded'] = true;
					$hold_result['api']['confirmationId'] = $holdRequestConfirmation->id;
				}
			}
		} else {
			$hold_result['success'] = false;
			$hold_result['message'] = translate([
				'text' => 'Your hold could not be placed. ',
				'isPublicFacing' => true,
			]);
			$hold_result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$hold_result['api']['message'] = translate([
				'text' => 'Your hold could not be placed.',
				'isPublicFacing' => true,
			]);
			if (IPAddress::showDebuggingInformation()) {
				$hold_result['message'] .= " (HTTP Code: {$this->lastResponseCode})";
				$hold_result['api']['message'] .= " (HTTP Code: {$this->lastResponseCode})";
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
	function importListsFromIls($patron) {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		$results = [
			'totalTitles' => 0,
			'totalLists' => 0,
		];

		//Get a list of all lists for the user
		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/patronaccountgettitlelists";
		$response = $this->getWebServiceResponse($polarisUrl, 'GET', $this->getAccessToken($patron->getBarcode(), $patron->getPasswordOrPin()), false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.getPatronLists', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
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
				} elseif ($newList->deleted == 1) {
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
				ExternalRequestLogEntry::logRequest('polaris.getPatronListTitles', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $response, []);
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
									$results['errors'] = [];
								}
								$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
							}
						} else {
							//The title is not in the resources, add an error to the results
							if (!isset($results['errors'])) {
								$results['errors'] = [];
							}
							$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
						}
					}
				}
			}
		}

		return $results;
	}

	//Use the values
//	function getPasswordPinValidationRules() : array {
//		return [
//			'minLength' => 4,
//			'maxLength' => 14,
//			'onlyDigitsAllowed' => false,
//			'requireStrongPassword' => false
//		];
//	}

	/** @noinspection PhpUndefinedFieldInspection */
	function getPatronUpdateForm($user) {
		$patronUpdateFields = $this->getSelfRegistrationFields('patronUpdate');
		//Display sections as headings
		foreach ($patronUpdateFields as &$section) {
			if ($section['type'] == 'section') {
				$section['renderAsHeading'] = true;
			}
		}
		if (!$user->getHomeLibrary()->showNoticeTypeInProfile) {
			unset($patronUpdateFields['preferencesSection']['properties']['notices']);
			unset($patronUpdateFields['preferencesSection']['properties']['eReceipts']);
		}

		$basicDataResponse = $this->getBasicDataResponse($user->getBarcode(), $user->getPasswordOrPin(), UserAccount::isUserMasquerading());
		if ($basicDataResponse != null) {
			$user->branchcode = $user->getHomeLocationCode();
			$user->firstName = $basicDataResponse->NameFirst;
			$user->lastName = $basicDataResponse->NameLast;
			$user->middleName = $basicDataResponse->NameMiddle;
			if (count($basicDataResponse->PatronAddresses) > 0) {
				$address = reset($basicDataResponse->PatronAddresses);
				$user->zipcode = $address->PostalCode;
				$user->city = $address->City;
				$user->state = $address->State;
				$user->address = $address->StreetOne;
				$user->address2 = $address->StreetTwo;
				$user->address3 = $address->StreetThree;
			}
			$user->birthDate = $this->parsePolarisDate($basicDataResponse->BirthDate);
			$user->phone1 = $basicDataResponse->PhoneNumber;
			$user->phone2 = $basicDataResponse->PhoneNumber2;
			$user->phone3 = $basicDataResponse->PhoneNumber3;
			$user->txtPhone = $basicDataResponse->TxtPhoneNumber;
			if ($user->txtPhone >= 1 && $user->txtPhone <= 3) {
				$property = 'Phone' . $user->txtPhone . 'CarrierID';
				$user->txtCarrier = $basicDataResponse->$property;
			}
			$user->email = $basicDataResponse->EmailAddress;
			$user->altEmail = $basicDataResponse->AltEmailAddress;
			$user->notices = $basicDataResponse->DeliveryOptionID;
			$user->txtPhone = $basicDataResponse->TxtPhoneNumber;
			$user->eReceipts = $basicDataResponse->EReceiptOptionID;
			$user->firstNameOnIdentification = $basicDataResponse->LegalNameFirst;
			$user->middleNameOnIdentification = $basicDataResponse->LegalNameMiddle;
			$user->lastNameOnIdentification = $basicDataResponse->LegalNameLast;
			$user->useNameOnIdForNotices = $basicDataResponse->UseLegalNameOnNotices;
		}

		global $interface;
		$patronUpdateFields[] = [
			'property' => 'updateScope',
			'type' => 'hidden',
			'label' => 'Update Scope',
			'description' => '',
			'default' => 'contact',
		];
		$patronUpdateFields[] = [
			'property' => 'patronId',
			'type' => 'hidden',
			'label' => 'Active Patron',
			'description' => '',
			'default' => $user->id,
		];
		//These need to be part of the object, not just defaults because we can't combine default settings with a provided object.
		/** @noinspection PhpUndefinedFieldInspection */
		$user->updateScope = 'contact';
		/** @noinspection PhpUndefinedFieldInspection */
		$user->patronId = $user->id;

		$interface->assign('submitUrl', '/MyAccount/ContactInformation');
		$interface->assign('structure', $patronUpdateFields);
		$interface->assign('object', $user);
		$interface->assign('saveButtonText', 'Update Contact Information');
		$interface->assign('formLabel', 'Update Contact Information Form');

		return $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}

	function getSelfRegistrationFields($type = 'selfReg') {
		global $library;
		$location = new Location();

		$pickupLocations = [];

		$fields = [];
		$validLibraries = [];
		if ($type == 'selfReg') {
			if ($library->selfRegistrationLocationRestrictions == 1) {
				//Library Locations
				$location->libraryId = $library->libraryId;
				$location->orderBy('isMainBranch DESC, displayName');
			} elseif ($library->selfRegistrationLocationRestrictions == 2) {
				//Valid pickup locations
				$location->whereAdd('validSelfRegistrationBranch <> 2');
				$location->orderBy('isMainBranch DESC, displayName');
			} elseif ($library->selfRegistrationLocationRestrictions == 3) {
				//Valid pickup locations
				$location->libraryId = $library->libraryId;
				$location->whereAdd('validSelfRegistrationBranch <> 2');
				$location->orderBy('isMainBranch DESC, displayName');
			}
			if ($location->find()) {
				while ($location->fetch()) {
					if (count($validLibraries) == 0 || array_key_exists($location->code, $validLibraries)) {
						$pickupLocations[$location->code] = $location->displayName;
					}
				}
				if (count($pickupLocations) > 1) {
					$pickupLocations = [
							'' => translate([
								'text' => 'Select a location',
								'isPublicFacing' => true,
							]),
						] + $pickupLocations;
				}
			}

			$fields['librarySection'] = [
				'property' => 'librarySection',
				'type' => 'section',
				'label' => 'Library',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'branchcode' => [
						'property' => 'branchcode',
						'type' => 'enum',
						'label' => 'Home Library',
						'description' => 'Please choose the Library location you would prefer to use',
						'values' => $pickupLocations,
						'required' => true,
						'default' => '',
					],
				],
			];
		} else {
			if ($library->allowHomeLibraryUpdates) {
				$patron = UserAccount::getActiveUserObj();
				$userPickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
				$pickupLocations = [];
				foreach ($userPickupLocations as $key => $location) {
					if ($location instanceof Location) {
						$pickupLocations[$location->code] = $location->displayName;
					} else {
						if ($key == '0default') {
							$pickupLocations[-1] = $location;
						}
					}
				}
				$fields['librarySection'] = [
					'property' => 'librarySection',
					'type' => 'section',
					'label' => 'Library',
					'hideInLists' => true,
					'expandByDefault' => true,
					'properties' => [
						'branchcode' => [
							'property' => 'branchcode',
							'type' => 'enum',
							'label' => 'Home Library',
							'description' => 'Please choose the Library location you would prefer to use',
							'values' => $pickupLocations,
							'required' => true,
							'default' => '',
						],
					],
				];
			}
		}

		$fields['personalInformationSection'] = [
			'property' => 'personalInformationSection',
			'type' => 'section',
			'label' => 'Personal Information',
			'hideInLists' => true,
			'expandByDefault' => true,
			'properties' => [
				'firstName' => [
					'property' => 'firstName',
					'type' => 'text',
					'label' => 'First Name',
					'description' => 'Your first name',
					'maxLength' => 25,
					'required' => true,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
				'middleName' => [
					'property' => 'middleName',
					'type' => 'text',
					'label' => 'Middle Name',
					'description' => 'Your middle name',
					'maxLength' => 25,
					'required' => false,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
				'lastName' => [
					'property' => 'lastName',
					'type' => 'text',
					'label' => 'Last Name',
					'description' => 'Your last name',
					'maxLength' => 60,
					'required' => true,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
			],
		];
		if ($type == 'selfReg' && $library && $library->promptForBirthDateInSelfReg) {
			$birthDateMin = date('Y-m-d', strtotime('-113 years'));
			$birthDateMax = date('Y-m-d', strtotime('-13 years'));
			$fields['personalInformationSection']['properties']['birthDate'] = [
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
		$fields['nameOnIdentificationSection'] = [
			'property' => 'nameOnIdentificationSection',
			'type' => 'section',
			'label' => 'Name on Identification',
			'hideInLists' => true,
			'expandByDefault' => true,
			'properties' => [
				'firstNameOnIdentification' => [
					'property' => 'firstNameOnIdentification',
					'type' => 'text',
					'label' => 'First Name',
					'description' => 'The first name on your ID',
					'maxLength' => 25,
					'required' => false,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
				'middleNameOnIdentification' => [
					'property' => 'middleNameOnIdentification',
					'type' => 'text',
					'label' => 'Middle Name',
					'description' => 'The middle name on your ID',
					'maxLength' => 25,
					'required' => false,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
				'lastNameOnIdentification' => [
					'property' => 'lastNameOnIdentification',
					'type' => 'text',
					'label' => 'Last Name',
					'description' => 'The last name on your ID',
					'maxLength' => 60,
					'required' => false,
					'readOnly' => ($type == 'patronUpdate'),
					'autocomplete' => false,
				],
				'useNameOnIdForNotices' => [
					'property' => 'useNameOnIdForNotices',
					'type' => 'checkbox',
					'label' => 'Use name on ID for print / phone notices',
					'description' => 'Whether or not the library should use the name on your ID when sending print and phone notices',
					'required' => false,
					'readOnly' => ($type == 'patronUpdate'),
				],
			],
		];
		$addressCanBeUpdated = true;
		if ($type == 'patronUpdate' && !$library->allowPatronAddressUpdates) {
			$addressCanBeUpdated = false;
		}
		if (empty($library->validSelfRegistrationStates)) {
			$borrowerStateField = [
				'property' => 'state',
				'type' => 'text',
				'label' => 'State',
				'description' => 'State',
				'maxLength' => 32,
				'required' => true,
				'autocomplete' => false,
			];
		} else {
			$validStates = explode('|', $library->validSelfRegistrationStates);
			$validStates = array_combine($validStates, $validStates);
			$borrowerStateField = [
				'property' => 'state',
				'type' => 'enum',
				'values' => $validStates,
				'label' => 'State',
				'description' => 'State',
				'maxLength' => 32,
				'required' => true,
				'readOnly' => !$addressCanBeUpdated,
			];
		}
		$fields['addressSection'] = [
			'property' => 'addressSection',
			'type' => 'section',
			'label' => 'Main Address',
			'hideInLists' => true,
			'expandByDefault' => true,
			'properties' => [
				'address' => [
					'property' => 'address',
					'type' => 'text',
					'label' => 'Address',
					'description' => 'Address',
					'maxLength' => 128,
					'required' => true,
					'readOnly' => !$addressCanBeUpdated,
					'autocomplete' => false,
				],
				'address2' => [
					'property' => 'address2',
					'type' => 'text',
					'label' => 'Address 2',
					'description' => 'Second line of the address',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => !$addressCanBeUpdated,
					'autocomplete' => false,
				],
				'address3' => [
					'property' => 'address3',
					'type' => 'text',
					'label' => 'Address 3',
					'description' => 'Third line of the address',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => !$addressCanBeUpdated,
					'autocomplete' => false,
				],
				'city' => [
					'property' => 'city',
					'type' => 'text',
					'label' => 'City',
					'description' => 'City',
					'maxLength' => 48,
					'required' => true,
					'readOnly' => !$addressCanBeUpdated,
					'autocomplete' => false,
				],
				'state' => $borrowerStateField,
				'zipcode' => [
					'property' => 'zipcode',
					'type' => 'text',
					'label' => 'Zip Code',
					'description' => 'Zip Code',
					'maxLength' => 32,
					'required' => true,
					'readOnly' => !$addressCanBeUpdated,
					'autocomplete' => false,
				],
			],
		];
		if (!empty($library->validSelfRegistrationZipCodes)) {
			$fields['mainAddressSection']['properties']['borrower_zipcode']['validationPattern'] = $library->validSelfRegistrationZipCodes;
			$fields['mainAddressSection']['properties']['borrower_zipcode']['validationMessage'] = translate([
				'text' => 'Please enter a valid zip code',
				'isPublicFacing' => true,
			]);
		}
		if ($library->requireNumericPhoneNumbersWhenUpdatingProfile) {
			$phoneFormat = '';
		} else {
			$phoneFormat = ' (xxx-xxx-xxxx)';
		}
		$phoneCanBeUpdated = true;
		if ($type == 'patronUpdate' && !$library->allowPatronPhoneNumberUpdates) {
			$phoneCanBeUpdated = false;
		}
		$fields['contactInformationSection'] = [
			'property' => 'contactInformationSection',
			'type' => 'section',
			'label' => 'Contact Information',
			'hideInLists' => true,
			'expandByDefault' => true,
			'properties' => [
				'email' => [
					'property' => 'email',
					'type' => 'email',
					'label' => 'Email address',
					'description' => 'Email',
					'maxLength' => 128,
					'required' => false,
					'autocomplete' => false,
				],
				'altEmail' => [
					'property' => 'altEmail',
					'type' => 'email',
					'label' => 'Alt. Email Address',
					'description' => 'Email',
					'maxLength' => 128,
					'required' => false,
					'autocomplete' => false,
				],
				'phone1' => [
					'property' => 'phone1',
					'type' => 'text',
					'label' => 'Phone 1' . $phoneFormat,
					'description' => 'Phone 1',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => !$phoneCanBeUpdated,
					'autocomplete' => false,
				],
				'phone2' => [
					'property' => 'phone2',
					'type' => 'text',
					'label' => 'Phone 2' . $phoneFormat,
					'description' => 'Phone 2',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => !$phoneCanBeUpdated,
					'autocomplete' => false,
				],
				'phone3' => [
					'property' => 'phone3',
					'type' => 'text',
					'label' => 'Phone 3' . $phoneFormat,
					'description' => 'Phone 3',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => !$phoneCanBeUpdated,
					'autocomplete' => false,
				],
			],
		];
		$carriers = $this->getCarrierList();

		$fields['preferencesSection'] = [
			'property' => 'preferencesSection',
			'type' => 'section',
			'label' => 'Preferences',
			'hideInLists' => true,
			'expandByDefault' => true,
			'properties' => [
				'notices' => [
					'property' => 'notices',
					'type' => 'enum',
					'values' => [
						2 => 'Email Address',
						1 => 'Mailing Address',
						3 => 'Phone 1',
						4 => 'Phone 2',
						5 => 'Phone 3',
						'8' => 'TXT Messaging',
						0 => 'None',
					],
					'label' => 'My preference for receiving library notices',
					'description' => 'My preference for receiving library notices',
					'required' => false,
					'default' => 2,
					'readOnly' => $type == 'patronUpdate' && !$library->showNoticeTypeInProfile,
				],
				'txtPhone' => [
					'property' => 'txtPhone',
					'type' => 'enum',
					'values' => [
						'(None)' => '(None)',
						1 => 'Phone 1',
						2 => 'Phone 2',
						3 => 'Phone 3',
					],
					'label' => 'Phone number for TXT messages',
					'description' => 'Phone number for TXT messages',
					'required' => false,
					'default' => '(None)',
				],
				'txtCarrier' => [
					'property' => 'txtCarrier',
					'type' => 'enum',
					'values' => $carriers,
					'label' => 'Carrier',
					'description' => 'The Carrier to use when sending TXT messages',
					'required' => false,
				],
				'eReceipts' => [
					'property' => 'eReceipts',
					'type' => 'enum',
					'values' => [
						'0' => '(None)',
						'2' => 'Email',
					],
					'label' => 'E-receipts',
					'description' => 'How you would like to receive E-receipts',
					'maxLength' => 128,
					'required' => false,
					'readOnly' => $type == 'patronUpdate' && !$library->showNoticeTypeInProfile,
				],
			],
		];
		if ($type == 'selfReg') {
			$passwordLabel = $library->loginFormPasswordLabel;
			$logonInfoSection = [
				'property' => 'logonInformationSection',
				'type' => 'section',
				'label' => 'Logon Information',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$logonInfoSection['properties']['patronUsername'] = [
				'property' => 'patronUsername',
				'type' => 'text',
				'label' => 'Username (optional as an alternate to barcode)',
				'description' => 'An optional username to use when logging in.',
				'required' => false,
				'maxLength' => 128,
				'autocomplete' => false,
			];
			$passwordNotes = $library->selfRegistrationPasswordNotes;
			$logonInfoSection['properties']['patronPassword'] = [
				'property' => 'patronPassword',
				'type' => 'password',
				'label' => $passwordLabel,
				'description' => $passwordNotes,
				'minLength' => 3,
				'maxLength' => 25,
				'showConfirm' => true,
				'required' => true,
				'showDescription' => true,
				'autocomplete' => false,
			];
			$fields['logonInformationSection'] = $logonInfoSection;
		}

		return $fields;
	}

	public function selfRegister(): array {
		global $library;
		$result = [
			'success' => false,
		];

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron";
		$body = new stdClass();
		$staffInfo = $this->getStaffUserInfo();
		$body->LogonBranchID = $library->ilsCode;
		$body->LogonUserID = (string)$staffInfo['polarisId'];
		if (empty($library->workstationId)) {
			$body->LogonWorkstationID = (int)$this->accountProfile->workstationId;
		} else {
			$body->LogonWorkstationID = (int)$library->workstationId;
		}
		$body->PatronBranchID = $_REQUEST['branchcode'];

		$this->setupBodyForSelfRegAndPatronUpdateCall('selfReg', $body, $library);

		$encodedBody = json_encode($body);
		$response = $this->getWebServiceResponse($polarisUrl, 'POST', '', $encodedBody);
		ExternalRequestLogEntry::logRequest('polaris.selfRegister', 'POST', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), $encodedBody, $this->lastResponseCode, $response, []);
		if ($response && $this->lastResponseCode == 200) {
			$jsonResult = json_decode($response);
			if ($jsonResult->PAPIErrorCode != 0) {
				$result['message'] = translate([
						'text' => "Could not create your account.",
						'isPublicFacing' => true,
					]) . " " . translate([
						'text' => $jsonResult->ErrorMessage,
						'isPublicFacing' => true,
					]);
				if (IPAddress::showDebuggingInformation()) {
					$result['message'] .= " ({$jsonResult->PAPIErrorCode})";
				}
			} else {
				$result['success'] = true;
				$result['username'] = $_REQUEST['patronUsername'];
				$result['barcode'] = $jsonResult->Barcode;
			}
		}

		return $result;
	}

	/**
	 * @param stdClass $body
	 * @param Library $library
	 */
	private function setupBodyForSelfRegAndPatronUpdateCall($type, stdClass $body, Library $library): void {
		if (isset($_REQUEST['branchcode'])) {
			$body->PatronBranchID = $_REQUEST['branchcode'];
		}

		if (isset($_REQUEST['zipcode'])) {
			$body->PostalCode = $_REQUEST['zipcode'];
		}
		$body->ZipPlusFour = '';
		if (isset($_REQUEST['city'])) {
			$body->City = $_REQUEST['city'];
		}
		if (isset($_REQUEST['state'])) {
			$body->State = $_REQUEST['state'];
		}
		$body->County = '';
		//$body->CountryID = '';

		if (isset($_REQUEST['address'])) {
			$body->StreetOne = $_REQUEST['address'];
		}
		if (isset($_REQUEST['address2'])) {
			$body->StreetTwo = $_REQUEST['address2'];
		}
		if (isset($_REQUEST['address3'])) {
			$body->StreetThree = $_REQUEST['address3'];
		}

		if (isset($_REQUEST['firstName'])) {
			$body->NameFirst = $_REQUEST['firstName'];
		}
		if (isset($_REQUEST['lastName'])) {
			$body->NameLast = $_REQUEST['lastName'];
		}
		if (isset($_REQUEST['middleName'])) {
			$body->NameMiddle = $_REQUEST['middleName'];
		}
		//$body->User1 = '';
		//$body->User2 = '';
		//$body->User3 = '';
		//$body->User4 = '';
		//$body->User5 = '';
		//$body->Gender = '';
		if (isset($_REQUEST['birthDate'])) {
			if ($type == 'selfReg' && $library && $library->promptForBirthDateInSelfReg) {
				$body->Birthdate = $_REQUEST['birthDate'];
			}
		}

		if (isset($_REQUEST['phone1'])) {
			$body->PhoneVoice1 = $_REQUEST['phone1'];
		}
		if (isset($_REQUEST['phone2'])) {
			$body->PhoneVoice2 = $_REQUEST['phone2'];
		}
		if (isset($_REQUEST['phone3'])) {
			$body->PhoneVoice3 = $_REQUEST['phone3'];
		}
		$body->PhoneVoice1Carrier = -2;
		$body->PhoneVoice2Carrier = -2;
		$body->PhoneVoice3Carrier = -2;
		if (isset($_REQUEST['txtPhone'])) {
			$txtCarrier = $_REQUEST['txtPhone'];
			if ($txtCarrier != '(None)') {
				$property = 'Phone' . $_REQUEST['txtPhone'] . 'CarrierID';
				$body->$property = $_REQUEST['txtCarrier'];
			}
		}

		if (isset($_REQUEST['email'])) {
			$body->EmailAddress = $_REQUEST['email'];
		}
		if (isset($_REQUEST['altEmail'])) {
			$body->AltEmailAddress = $_REQUEST['altEmail'];
		}
		//$body->LanguageID = 1;
		if (isset($_REQUEST['patronUsername'])) {
			$body->Username = $_REQUEST['patronUsername'];
		}
		if (isset($_REQUEST['patronPassword'])) {
			$body->Password = $_REQUEST['patronPassword'];
		}
		if (isset($_REQUEST['patronPasswordRepeat'])) {
			$body->Password2 = $_REQUEST['patronPasswordRepeat'];
		}
		if (isset($_REQUEST['notices'])) {
			$body->DeliveryOptionID = $_REQUEST['notices'];
		}
		if (isset($_REQUEST['txtPhone'])) {
			if ($_REQUEST['txtPhone'] != '(None)') {
				$body->EnableSMS = !empty($_REQUEST['txtPhone']) ? 1 : 0;
				$body->TxtPhoneNumber = $_REQUEST['txtPhone'];
			} else {
				$body->EnableSMS = 0;
				$body->TxtPhoneNumber = '';
			}
		}
		//$body->Barcode = '';
		if (isset($_REQUEST['eReceipts'])) {
			if ($_REQUEST['eReceipts'] != '(None)') {
				$body->EReceiptOptionID = $_REQUEST['eReceipts'];
			}
		}
		//$body->PatronCode = '';
		//$body->ExpirationDate = '';
		//$body->AddrCheckDate = '';
		//$body->GenderID = '';
		if ($type == 'selfReg' || $library->allowNameUpdates) {
			if (isset($_REQUEST['firstNameOnIdentification'])) {
				$body->LegalNameFirst = $_REQUEST['firstNameOnIdentification'];
			}
			if (isset($_REQUEST['middleNameOnIdentification'])) {
				$body->LegalNameMiddle = $_REQUEST['middleNameOnIdentification'];
			}
			if (isset($_REQUEST['lastNameOnIdentification'])) {
				$body->LegalNameLast = $_REQUEST['lastNameOnIdentification'];
			}
			if (isset($_REQUEST['useNameOnIdForNotices'])) {
				$body->UseLegalNameOnNotices = isset($_REQUEST['useNameOnIdForNotices']) ? true : false;
			}
		}
		if (isset($_REQUEST['branchcode'])) {
			$body->RequestPickupBranchID = $_REQUEST['branchcode'];
		}
	}

	function getForgotPasswordType() {
		return 'emailAspenResetLink';
	}

	public function showHoldPosition(): bool {
		return true;
	}

	public function supportsLoginWithUsername(): bool {
		return true;
	}

	/**
	 * Returns true if reset username is a separate page independent of the patron information page
	 *
	 * @return bool
	 */
	public function showResetUsernameLink(): bool {
		return true;
	}

	public function getUsernameValidationRules(): array {
		return [
			'minLength' => 4,
			'maxLength' => 50,
			'additionalRequirements' => translate([
				'text' => 'All usernames must begin with a letter. Usernames can contain letters, numbers, and special characters. Spaces are not allowed, and special characters cannot be contiguous.',
				'isPublicFacing' => true,
			]),
		];
	}

	public function updateEditableUsername(User $patron, string $newUsername): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error updating username',
				'isPublicFacing' => true,
			]),
		];

		$polarisUrl = "/PAPIService/REST/public/v1/1033/100/1/patron/{$patron->getBarcode()}/username/" . urlencode($newUsername);
		$updateUsernameResponse = $this->getWebServiceResponse($polarisUrl, 'PUT', Polaris::$accessTokensForUsers[$patron->getBarcode()]['accessToken'], false, UserAccount::isUserMasquerading());
		ExternalRequestLogEntry::logRequest('polaris.updateUsername', 'PUT', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $updateUsernameResponse, []);
		if ($updateUsernameResponse && $this->lastResponseCode == 200) {
			$updateUsernameResponse = json_decode($updateUsernameResponse);
			if ($updateUsernameResponse->PAPIErrorCode == 0) {
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'Your username was updated successfully',
						'isPublicFacing' => true,
					]),
				];
			} else {
				$result = [
					'success' => false,
					'message' => translate([
						'text' => $updateUsernameResponse->ErrorMessage,
						'isPublicFacing' => true,
					]),
				];
			}
		}

		return $result;
	}

	public function suspendRequiresReactivationDate(): bool {
		return true;
	}

	public function showDateWhenSuspending(): bool {
		return true;
	}

	public function reactivateDateNotRequired(): bool {
		return true;
	}

	public function showTimesRenewed(): bool {
		return true;
	}

	private function getCarrierList(): array {
		$staffUserInfo = $this->getStaffUserInfo();
		$polarisUrl = "/PAPIService/REST/protected/v1/1033/100/1/{$staffUserInfo['accessToken']}/sysadmin/mobilephonecarriers";
		$carriersResponse = $this->getWebServiceResponse($polarisUrl, 'GET', $staffUserInfo['accessSecret'], false, true);
		ExternalRequestLogEntry::logRequest('polaris.getCarrierList', 'GET', $this->getWebServiceURL() . $polarisUrl, $this->apiCurlWrapper->getHeaders(), false, $this->lastResponseCode, $carriersResponse, []);
		$carriers = [];
		if ($carriersResponse && $this->lastResponseCode == 200) {
			$carriersResponse = json_decode($carriersResponse);
			foreach ($carriersResponse->SAMobilePhoneCarriersGetRows as $carrierInfo) {
				$carriers[$carrierInfo->CarrierID] = $carrierInfo->CarrierName;
			}
		}

		asort($carriers);
		return $carriers;
	}
}
