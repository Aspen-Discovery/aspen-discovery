<?php
/** @noinspection SqlResolve */

use PgSql\Connection;

class Sierra extends AbstractIlsDriver {
	protected string $urlIdRegExp = "~.*/([0-9]*)$~";
	private ?stdClass $sierraToken = null;
	private ?int $lastResponseCode;
	/** @noinspection PhpPropertyOnlyWrittenInspection */
	private ?int $lastError = null;
	private ?string $lastErrorMessage = null;

	private null|false|Connection $_sierraDNAConnection = null;

	public ?CurlWrapper $curlWrapper = null;

	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
		$this->curlWrapper = new CurlWrapper();
	}

	public function _connectToApi() {
		if ($this->sierraToken == null) {
			$apiVersion = $this->accountProfile->apiVersion;
			$tokenUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v$apiVersion/token/";
			$ch = curl_init($tokenUrl);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$authInfo = base64_encode($this->accountProfile->oAuthClientId . ":" . $this->accountProfile->oAuthClientSecret);
			$headers = [
				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
				'User-Agent: Aspen Discovery',
				'Authorization: Basic ' . $authInfo,
			];
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			curl_close($ch);
			ExternalRequestLogEntry::logRequest('sierra.connectToApi', 'POST', $tokenUrl, $headers, "grant_type=client_credentials", $responseCode, $return, []);

			$this->sierraToken = json_decode($return);
		}
		return $this->sierraToken;
	}

	public function __destruct() {
		$this->closeSierraDNAConnection();
	}

	public function _callUrl($requestType, $url) {
		$tokenData = $this->_connectToAPI();
		if ($tokenData) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = [
				"Authorization: " . $tokenData->token_type . " $tokenData->access_token",
				"User-Agent: Aspen Discovery",
				//"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Host: " . $host,
			];
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;

			ExternalRequestLogEntry::logRequest($requestType, 'GET', $url, $headers, '', $responseCode, $return, []);
			curl_close($ch);

			$returnVal = json_decode($return);
			if ($returnVal != null) {
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.') {
					return $returnVal;
				}
			}
		}
		return null;
	}

	public function _postPage($requestType, $url, $postParams) {

		$tokenData = $this->_connectToAPI();
		if ($tokenData) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = [
				"Authorization: " . $tokenData->token_type . " $tokenData->access_token",
				"User-Agent: Aspen Discovery",
				"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Accept-Language: *",
				"Host: " . $host,
				'Content-Type: application/json',
				'Accept: application/json',
			];
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postParams != null) {
				if (is_string($postParams)) {
					$post_string = $postParams;
				} else {
					$post_string = http_build_query($postParams);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
			} else {
				$post_string = '';
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
				$headers[] = 'Content-Length: 0';
			}
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;
			$this->lastError = curl_errno($ch);
			$this->lastErrorMessage = curl_error($ch);
//			if ($responseCode == 400){
//				global $logger;
//				$logger->log("Got 400 error POSTING to '" . $url . "'", Logger::LOG_ERROR);
//				$logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
//			}

			ExternalRequestLogEntry::logRequest($requestType, 'POST', $url, $headers, $post_string, $responseCode, $return, []);
			curl_close($ch);
			$returnVal = json_decode($return);
			if ($returnVal != null) {
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.') {
					return $returnVal;
				}
			}
		}
		return null;
	}

	public function _sendPage($requestType, $httpMethod, $url, $postParams = null) {

		$tokenData = $this->_connectToAPI();
		if ($tokenData) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = [
				"Authorization: " . $tokenData->token_type . " $tokenData->access_token",
				"User-Agent: Aspen Discovery",
				//"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Host: " . $host,
			];
			if ($httpMethod == 'PUT') {
				$headers[] = 'Content-Type: application/json';
				if ($postParams === null || $postParams === false) {
					$headers[] = 'Content-Length: 0';
				} else {
					if (is_array($postParams)) {
						$postParams = json_encode($postParams);
					}
				}
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			if ($httpMethod == 'GET') {
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			} elseif ($httpMethod == 'POST') {
				curl_setopt($ch, CURLOPT_POST, true);
			} else {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
			}
			if ($postParams != null) {
				if (is_array($postParams)) {
					$postFields = http_build_query($postParams);
				} else {
					$postFields = $postParams;
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			} else {
				$postFields = '';
			}
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;
			$this->lastError = curl_errno($ch);
			$this->lastErrorMessage = curl_error($ch);

			ExternalRequestLogEntry::logRequest($requestType, $httpMethod, $url, $headers, $postFields, $responseCode, $return, []);
			curl_close($ch);
			$returnVal = json_decode($return);
			if ($returnVal != null) {
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.') {
					return $returnVal;
				}
			}
		}
		return null;
	}

	/**
	 * Returns one of three values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * @return string
	 */
	function getForgotPasswordType() : string {
		if ($this->accountProfile == null) {
			return 'none';
		} else {
			if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
				return 'emailAspenResetLink';
			} else {
				return 'none';
			}
		}
	}

	public function getHolds(User $patron): array {
		global $library;
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = [];
		$unavailableHolds = [];
		$return = [
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds,
		];

		$patronId = $patron->unique_ils_id;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;

		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/holds";
		if ($this->accountProfile->apiVersion > 4) {
			$sierraUrl .= "?fields=default,priorityQueueLength&limit=1000";
		} else {
			$sierraUrl .= "?fields=default,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate,placed&limit=1000";
		}
		$holds = $this->_callUrl('sierra.getHolds', $sierraUrl);

		if ($holds->total == 0) {
			return $return;
		}

		// these will be consistent for every hold
		$pickupLocations = $patron->getValidPickupBranches($this->accountProfile->recordSource);
		if (count($pickupLocations) > 1) {
			$canUpdatePL = true;
		} else {
			$canUpdatePL = false;
		}
		foreach ($holds->entries as $sierraHold) {
			$curHold = new Hold();
			$curHold->userId = $patron->id;
			$curHold->type = 'ils';
			$curHold->source = $this->accountProfile->getIndexingProfile()->name;

			$curHold->frozen = $sierraHold->frozen;
			$curHold->createDate = strtotime($sierraHold->placed); // date hold created
			// innreach holds don't include notNeededAfterDate
			$curHold->automaticCancellationDate = isset($sierraHold->notNeededAfterDate) ? strtotime($sierraHold->notNeededAfterDate) : null; // not needed after date
			$curHold->expirationDate = isset($sierraHold->pickupByDate) ? strtotime($sierraHold->pickupByDate) : false; // pick up by date // this isn't available in api v4

			if (isset($sierraHold->priority)) {
				if ($this->accountProfile->apiVersion == 4) {
					$holdPriority = (integer)$sierraHold->priority + 1;
				} else {
					$holdPriority = $sierraHold->priority;
				}
				$curHold->position = $holdPriority;
			}
			if (isset($sierraHold->priorityQueueLength)) {
				$curHold->holdQueueLength = $sierraHold->priorityQueueLength;
			}

			// cancel id
			preg_match($this->urlIdRegExp, $sierraHold->id, $m);
			$curHold->cancelId = $m[1];

			// status, cancelable, freezable
			$recordStatus = $sierraHold->status->code;
			// check item record status
			if (preg_match($this->urlIdRegExp, $sierraHold->record, $m)) {
				$recordId = $m[1];
			} else {
				$recordId = substr($sierraHold->record, strrpos($sierraHold->record, '/') + 1);
			}
			$isInnReach = false;
			if ($sierraHold->recordType == 'i') {
				$recordItemStatus = $sierraHold->status->code;
				// If this is an inn-reach exclude from check -- this comes later
				if (!str_contains($recordId, "@")) {
					// if the item status is "on hold shelf" (!) but the hold record status is "on hold" (0) use "on hold" status
					// the "on hold shelf" status is for another patron.
					if ($recordItemStatus != "!" && $recordStatus != '0') {
						// check for in transit status see
						if ($recordItemStatus == 't') {
							if (isset($sierraHold->priority) && (int)$sierraHold->priority == 1) {
								$recordStatus = 't';
							}
						}
					}
				} else {
					// inn-reach status
					$isInnReach = true;
					$curHold->source = $library->interLibraryLoanName;
					$recordStatus = $recordItemStatus;
				}
			}

			$available = false;
			// type hint so '0' != false
			switch ((string)$recordStatus) {
				case '0':
				case '-':
					if ($sierraHold->frozen) {
						$status = "Frozen";
					} else {
						$status = 'On hold';
					}
					$freezeable = true;
					$cancelable = true;

					if ($canUpdatePL) {
						$updatePickup = true;
					} else {
						$updatePickup = false;
					}
					if ($isInnReach) {
						if (!empty($sierraHold->pickupByDate)) {
							$status = 'Ready For Pickup';
							$available = true;
							$updatePickup = false;
							$freezeable = false;
						}
					}
					break;
				case 'b':
				case 'j':
				case 'i':
				case '!':
					$status = 'Ready';
					$cancelable = true;
					$freezeable = false;
					$updatePickup = false;
					$available = true;
					break;
				case 't':
					$status = 'In transit';
					$cancelable = true;
					$freezeable = false;
					if ($canUpdatePL) {
						$updatePickup = true;
					} else {
						$updatePickup = false;
					}
					break;
				case "&": // inn-reach status
					$status = "Requested";
					$cancelable = true;
					$freezeable = false;
					$updatePickup = false;
					break;
				case "#": // inn-reach status
					$sierraHold->status->code = 'i';
					$status = 'Ready';
					$freezeable = false;
					$cancelable = false;
					$updatePickup = false;
					$available = true;
					break;
				default:
					$status = $recordItemStatusMessage ?? 'On hold';
					$cancelable = false;
					$freezeable = false;
					$updatePickup = false;
			}
			$curHold->status = $status;
//			if (isset($curHold->holdQueueLength)) {
//				// if the patron is the only person on wait list hold can't be frozen
//				if (isset($curHold->position) && ($curHold->position == 1 && (int)$curHold->holdQueueLength == 1)) {
//					$freezeable = false;
//					// if there is no priority set but queueLength = 1
//				} elseif (!isset($curHold->position) && $curHold->holdQueueLength == 1) {
//					$freezeable = false;
//				}
//			}
			$curHold->canFreeze = ($freezeable && $patron->getHomeLibrary()->allowFreezeHolds) || $curHold->frozen;
			$curHold->cancelable = $cancelable;
			$curHold->locationUpdateable = $updatePickup;
			$curHold->available = $available;
			// unset for next round.
			unset($status, $freezeable, $cancelable, $updatePickup);

			// pick up location
			if (!empty($sierraHold->pickupLocation)) {
				$pickupBranch = new Location();
				$pickupBranch->code = $sierraHold->pickupLocation->code;
				if ($pickupBranch->find(true)) {
					$curHold->pickupLocationId = $pickupBranch->locationId;
					$curHold->pickupLocationName = $pickupBranch->displayName;
				} else {
					$curHold->pickupLocationId = false;
					$curHold->pickupLocationName = $sierraHold->pickupLocation->name;
				}
			} else {
				//This shouldn't happen, but we have had examples where it did
				global $logger;
				$logger->log("Patron with barcode {$patron->getBarcode()} has a hold with out a pickup location ", Logger::LOG_ERROR);
				$curHold->pickupLocationId = false;
				$curHold->pickupLocationName = '';
			}

			// determine if this is an innreach hold
			// or if it's a regular ILS hold
			if (str_contains($recordId, "@")) {
				$titleAuthor = $this->getTitleAndAuthorForInnReachHold($curHold->cancelId);
				if ($titleAuthor !== false) {
					$curHold->title = $titleAuthor['title'];
					$curHold->author = $titleAuthor['author'];
					$curHold->format = [];
				} else {
					$curHold->title = 'Unknown';
					$curHold->author = 'Unknown';
				}
				$curHold->sourceId = '';
				$curHold->recordId = '';
				$curHold->source = $library->interLibraryLoanName;
			} else {
				///////////////
				// ILS HOLD
				//////////////
				// record type and record id
				$recordType = $sierraHold->recordType;
				// for item level holds we need to grab the bib id.
				$id = $recordId; //$m[1];
				if ($recordType == 'i') {
					$itemId = ".i$id" . $this->getCheckDigit($id);
					$id = $this->getBibIdForItem($itemId, $id);
				} else {
					$recordXD = $this->getCheckDigit($id);
					$id = ".b$id$recordXD";
				}

				if ($id) {

					$curHold->recordId = $id;
					$curHold->sourceId = $curHold->recordId;

					// get more info from record
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($curHold->recordId);
					if ($recordDriver->isValid()) {
						$curHold->updateFromRecordDriver($recordDriver);

						if($recordType == 'i') {
							// Get volume for Item holds
							$relatedRecord = $recordDriver->getRelatedRecord();
							if ($relatedRecord != null) {
								$groupingItem = $relatedRecord->getItemById($itemId);
								if ($groupingItem != null) {
									$curHold->volume = $groupingItem->volume;
									$curHold->callNumber = $groupingItem->callNumber;
								}
							}
						}
					}
				} else {
					$curHold->sourceId = '';
					$curHold->recordId = '';
				}
			}
			if ($available) {
				$return['available'][] = $curHold;
			} else {
				$return['unavailable'][] = $curHold;
			}
		}

		return $return;
	}

	/**
	 * Retrieves valid pickup locations for this patron for this record.
	 * @param string $recordId
	 * @param User $patron
	 * @return array An array containing valid pickup locations
	 */
	public function getValidPickupLocationsForRecordFromILS($recordId, $patron): array {
		if ($recordId == null) {
			return [
				'success' => false,
				'message' => 'Missing record; unable to retrieve valid pickup locations',
				'useDefaultLocationFiltering' => true,
			];
		}
		$patronId = $patron->unique_ils_id;
		$recordId = substr(str_replace('.b', '', $recordId), 0, -1);
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/holds/requests/form?";
		$params = ['recordNumber' => $recordId];
		$sierraUrl .= http_build_query($params);

		$pickupLocationsResponse = $this->_callUrl('sierra.getPickupLocationsForRecord', $sierraUrl);

		if (!empty($pickupLocationsResponse) && !empty($pickupLocationsResponse->holdshelf)) {
			$locationCodes = [];
			if (!empty($pickupLocationsResponse->holdshelf->selected)) {
				$locationCodes[] = $pickupLocationsResponse->holdshelf->selected->code;
			}
			foreach ($pickupLocationsResponse->holdshelf->locations as $location) {
				$locationCodes[] = trim($location->code);
			}
			return [
				'success' => true,
				'message' => 'Pickup locations found',
				'locationCodes' => $locationCodes,
			];
		} else {
			$message = 'Unable to retrieve valid pickup locations from Sierra. ';
			$message .= $pickupLocationsResponse->name ?? '';
			$message .= !empty($pickupLocationsResponse->description) ? ': ' . $pickupLocationsResponse->description : '';
			return [
				'success' => false,
				'message' => $message,
				'useDefaultLocationFiltering' => true,
			];
		}
	}
	/**
	 * Checks whether this ILS restricts pickup locations for specific records.
	 *
	 * @return bool
	 */
	public function restrictValidPickupLocationsForRecordByILS(): bool {
		return true;
	}

	public function hasNativeReadingHistory(): bool {
		return true;
	}

	public function performsReadingHistoryUpdatesOfILS() : bool {
		return true;
	}

	public function optInToReadingHistoryILS(User $user): array {
		$result = ['success' => false];

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $user->unique_ils_id . "/checkouts/history/activationStatus";
		$requestBody = json_encode(['readingHistoryActivation' => true]);
		$this->_postPage('sierra.optInToReadingHistory', $sierraUrl, $requestBody);

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204) {
			$result['success'] = true;
			$result['message'] = 'Reading history has been enabled in the ILS.';
		} else {
			$result['message'] = 'Failed to enable reading history in the ILS.';
		}

		return $result;
	}

	public function optOutOfReadingHistoryILS(User $user): array {
		$result = ['success' => false];

		//First delete checkout hisotry
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $user->unique_ils_id . "/checkouts/history";
		$this->_sendPage('sierra.deleteReadingHistory', 'DELETE', $sierraUrl, []);

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $user->unique_ils_id . "/checkouts/history/activationStatus";
		$requestBody = json_encode(['readingHistoryActivation' => false]);
		$this->_postPage('sierra.optOutOfReadingHistory', $sierraUrl, $requestBody);

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204) {
			$result['success'] = true;
			$result['message'] = translate(['text' => 'Reading history has been disabled in the ILS.', 'isPublicFacing'=>true]);
		} else {
			$result['message'] = translate(['text' => 'Failed to disable reading history in the ILS.', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	public function doReadingHistoryAction(User $patron, string $action, array $selectedTitles): ?array {
		return match ($action) {
			'optIn' => $this->optInToReadingHistoryILS($patron),
			'optOut' => $this->optOutOfReadingHistoryILS($patron),
			default => parent::doReadingHistoryAction($patron, $action, $selectedTitles),
		};
	}

	public function isOptedIntoReadingHistoryInILS(User $patron) : bool {
		$patronId = $patron->unique_ils_id;
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/checkouts/history/activationStatus";
		$readingHistoryEnabledResponse = $this->_callUrl('sierra.getReadingHistoryStatus', $sierraUrl);
		$readingHistoryEnabled = false;
		if (!empty($readingHistoryEnabledResponse)) {
			$readingHistoryEnabled = $readingHistoryEnabledResponse->readingHistoryActivation;
		}
		return $readingHistoryEnabled;
	}

	public function getReadingHistory(User $patron): array {
		$patronId = $patron->unique_ils_id;
		$readingHistoryEnabled = $this->isOptedIntoReadingHistoryInILS($patron);
		// To preserve reading history for existing accounts in Aspen,
		// and if the user's reading history is already enabled in Aspen,
		// then enable it in the Sierra ILS.
		if ($patron->trackReadingHistory && !$readingHistoryEnabled) {
			$optInResult = $this->optInToReadingHistoryILS($patron);
			if ($optInResult['success']) {
				$readingHistoryEnabled = true;
			}
		}

		$readingHistoryTitles = [];
		if ($readingHistoryEnabled) {
			ini_set('memory_limit', '2G');
			set_time_limit(0);

			$numProcessed = 0;
			$totalToProcess = 1000;
			while ($numProcessed < $totalToProcess) {
				$getReadingHistoryUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/checkouts/history?limit=100&offset=$numProcessed&sortField=outDate&sortOrder=desc&fields=id,patron,item,bib,outDate,returnDate";

				$readingHistoryResponse = $this->_callUrl('sierra.getReadingHistory', $getReadingHistoryUrl);
				if ($readingHistoryResponse && $readingHistoryResponse->total > 0) {
					$totalToProcess = $readingHistoryResponse->total;
					foreach ($readingHistoryResponse->entries as $historyEntry) {
						$curTitle = [];
						preg_match($this->urlIdRegExp, $historyEntry->bib, $matches);
						$bibId = ".b$matches[1]" . $this->getCheckDigit($matches[1]);
						$curTitle['id'] = $bibId;
						$curTitle['shortId'] = "$matches[1]";
						$curTitle['sourceId'] = $bibId;
						$itemRecordId = $this->getIdFromSierraLink($historyEntry->item ?? null);
						$itemInfo = $this->_callUrl('sierra.getItemInfo', $historyEntry->item);
						$curTitle['barcode'] = $itemInfo->barcode ?: null;
						$checkoutTimestamp = strtotime($historyEntry->outDate);
						if ($checkoutTimestamp === false) {
							$checkoutTimestamp = null;
						}
						$curTitle['checkout'] = $checkoutTimestamp;
						if (!empty($historyEntry->returnDate)) {
							$checkinDate = strtotime($historyEntry->returnDate);
						}else{
							$checkinDate = null;
						}

						if ($checkinDate === null) {
							// Sierra history entries often omit returnDate; try to recover from DNA using the patron/item identifiers.
							$checkinDate = $this->getCheckinDateFromSierraDNA((int)$patronId, $itemRecordId, $checkoutTimestamp);
						}
						$curTitle['checkin'] = $checkinDate ?? -1;
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $curTitle['sourceId']);
						if ($recordDriver->isValid()) {
							$curTitle['permanentId'] = $recordDriver->getPermanentId();
							$curTitle['title'] = $recordDriver->getTitle();
							$curTitle['format'] = $recordDriver->getFormats();
							$curTitle['author'] = $recordDriver->getPrimaryAuthor();
						} else {
							//get title and author by looking up the bib
							$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/{$curTitle['shortId']}");
							if ($getBibResponse) {
								if (isset($getBibResponse->deleted) && $getBibResponse->deleted) {
									$curTitle['title'] = 'Deleted from catalog';
									$curTitle['author'] = 'Unknown';
									$curTitle['format'] = 'Unknown';
								} else {
									if (isset($getBibResponse->title)) {
										$curTitle['title'] = $getBibResponse->title;
									} else {
										$curTitle['title'] = 'Unknown';
									}
									if (isset($getBibResponse->author)) {
										$curTitle['author'] = $getBibResponse->author;
									} else {
										$curTitle['author'] = 'Unknown';
									}
									$curTitle['format'] = $getBibResponse->materialType->value ?? 'Unknown';
								}
							} else {
								$curTitle['title'] = 'Unknown';
								$curTitle['author'] = 'Unknown';
								$curTitle['format'] = 'Unknown';
							}
							$getBibResponse = null;
						}
						$recordDriver->__destruct();
						$recordDriver = null;

						$readingHistoryTitles[] = $curTitle;
					}
					$numProcessed += count($readingHistoryResponse->entries);
				} else {
					break;
				}
			}
		}

		return [
			'historyActive' => $readingHistoryEnabled,
			'titles' => $readingHistoryTitles,
			'numTitles' => count($readingHistoryTitles),
		];
	}

	/**
	 * Look up a check-in date from Sierra DNA using circ_trans joined via record numbers.
	 * Joins circ_trans to record_metadata to match patron and item by record_num (not internal ids),
	 * filters to check in transactions (op_code = 'i'), and optionally narrows to a +/- 1-day window
	 * around the checkout timestamp before returning the most recent match.
	 *
	 * @param int $patronId Patron record number (not internal id).
	 * @param int|null $itemId Item record number (not internal id).
	 * @param int|null $checkoutTimestamp Checkout timestamp for narrowing the search window.
	 * @return int|null Unix timestamp of check-in or null if not found.
	 */
	private function getCheckinDateFromSierraDNA(int $patronId, ?int $itemId, ?int $checkoutTimestamp): ?int {
		$sierraDnaConnection = $this->connectToSierraDNA();
		if (!$sierraDnaConnection) {
			global $logger;
			$logger->log("Reading history DNA lookup failed: unable to connect to Sierra DNA.", Logger::LOG_ERROR);
			return null;
		}

		// Only use circ_trans record_num match to avoid cross-patron results.
		// language=PostgreSQL
		$getReturnDateByItemNumStmt = "SELECT EXTRACT(EPOCH FROM ct.transaction_gmt) AS checkin_epoch
			FROM sierra_view.circ_trans ct
			JOIN sierra_view.record_metadata irm ON ct.item_record_id = irm.id AND irm.record_type_code = 'i'
			JOIN sierra_view.record_metadata prm ON ct.patron_record_id = prm.id AND prm.record_type_code = 'p'
			WHERE irm.record_num = $1 AND prm.record_num = $2 AND ct.op_code = 'i'";
		$paramsByNum = [$itemId, $patronId];
		if ($checkoutTimestamp !== null) {
			$checkoutIsoDate = gmdate('c', $checkoutTimestamp);
			/** @noinspection SpellCheckingInspection */
			$getReturnDateByItemNumStmt .= " AND ct.transaction_gmt BETWEEN $3::timestamptz - interval '1 day' AND $3::timestamptz + interval '1 day'";
			$paramsByNum[] = $checkoutIsoDate;
		}
		$getReturnDateByItemNumStmt .= " ORDER BY ct.transaction_gmt DESC LIMIT 1";
		$getReturnDateByItemNumRS = pg_query_params($sierraDnaConnection, $getReturnDateByItemNumStmt, $paramsByNum);
		if ($getReturnDateByItemNumRS !== false && pg_num_rows($getReturnDateByItemNumRS) > 0) {
			$itemData = pg_fetch_assoc($getReturnDateByItemNumRS);
			$timestamp = (int)$itemData['checkin_epoch'];
			global $logger;
			$logger->log("Reading history DNA checkin date for itemId $itemId: " . date(DATE_ATOM, $timestamp), Logger::LOG_ERROR);
			return $timestamp;
		}

		global $logger;
		$logger->log("Reading history DNA lookup could not find checkin date (itemId=$itemId)", Logger::LOG_ERROR);

		return null;
	}

	private function getIdFromSierraLink(?string $link): ?int {
		if ($link === null || $link === '') {
			return null;
		}
		if (is_numeric($link)) {
			return (int)$link;
		}
		$matches = [];
		if (preg_match($this->urlIdRegExp, $link, $matches)) {
			return (int)$matches[1];
		}
		return null;
	}

	public function getCheckouts(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];
		global $library;

		$patronId = $patron->unique_ils_id;

		$numProcessed = 0;
		$total = -1;

		while ($numProcessed < $total || $total == -1) {
			$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/checkouts?fields=default,barcode,callNumber&limit=100&offset=$numProcessed";
			$checkouts = $this->_callUrl('sierra.getCheckouts', $sierraUrl);
			if ($total == -1) {
				$total = $checkouts->total;
			}

			foreach ($checkouts->entries as $i => $entry) {
				preg_match($this->urlIdRegExp, $entry->id, $m);
				$checkoutId = $m[1];

				$curCheckout = new Checkout();
				$curCheckout->type = 'ils';
				$curCheckout->source = $this->getIndexingProfile()->name;
				$curCheckout->userId = $patron->id;
				$curCheckout->dueDate = strtotime($entry->dueDate);
				$curCheckout->checkoutDate = strtotime($entry->outDate);
				$curCheckout->renewCount = $entry->numberOfRenewals;
				$curCheckout->canRenew = true;
				$curCheckout->renewalId = $checkoutId;
				$curCheckout->renewIndicator = $checkoutId;
				if (isset($entry->barcode)) {
					$curCheckout->barcode = $entry->barcode;
				}
				if (str_contains($entry->item, "@")) {
					$curCheckout->source = $library->interLibraryLoanName;
					$curCheckout->sourceId = '';
					$curCheckout->recordId = '';
					$titleAuthor = $this->getTitleAndAuthorForInnReachCheckout($checkoutId);
					if ($titleAuthor) {
						$curCheckout->title = $titleAuthor['title'];
						$curCheckout->author = $titleAuthor['author'];
					} else {
						$curCheckout->title = 'Unknown';
						$curCheckout->author = 'Unknown';
					}
					$curCheckout->formats = ['Unknown'];
				} else {
					preg_match($this->urlIdRegExp, $entry->item, $m);
					$itemIdShort = $m[1];
					$itemId = ".i" . $itemIdShort . $this->getCheckDigit($itemIdShort);
					$bibId = $this->getBibIdForItem($itemId, $itemIdShort);

					if (!empty($entry->callNumber)) {
						$curCheckout->callNumber = $entry->callNumber;
					}

					$curCheckout->itemId = $itemId;
					if ($bibId) {
						$curCheckout->sourceId = $bibId;
						$curCheckout->recordId = $bibId;
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$recordDriver = new MarcRecordDriver($curCheckout->recordId);
						if ($recordDriver->isValid()) {
							$curCheckout->updateFromRecordDriver($recordDriver);
							$relatedRecord = $recordDriver->getRelatedRecord();
							if ($relatedRecord != null) {
								//Check to see if we have volume info for the item
								foreach ($relatedRecord->getItems() as $item) {
									if ($item->itemId == $itemId) {
										if (!empty($item->volume)) {
											$curCheckout->volume = $item->volume;
										}
										if ($item->callNumber != $curCheckout->callNumber) {
											$curCheckout->callNumber = $item->callNumber;
										}
										break;
									}
								}
							}
						} else {
							$bibIdShort = substr(str_replace('.b', 'b', $bibId), 0, -1);
							$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/$bibIdShort");
							if ($getBibResponse) {
								$curCheckout->title = $getBibResponse->title ?? 'Unknown';
								$curCheckout->author = $getBibResponse->author ?? 'Unknown';
								$curCheckout->formats = [$getBibResponse->materialType->value ?? 'Unknown'];
							} else {
								$curCheckout->title = 'Unknown';
								$curCheckout->author = 'Unknown';
								$curCheckout->formats = ['Unknown'];
							}
						}
					} else {
						$curCheckout->sourceId = '';
						$curCheckout->recordId = '';
					}
				}
				$index = $i + $numProcessed;
				$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$index";
				$checkedOutTitles[$sortKey] = $curCheckout;
			}
			$numProcessed += count($checkouts->entries);
		}

		return $checkedOutTitles;
	}

	function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/checkouts/$itemId/renewal";
		$renewResponse = $this->_postPage('sierra.renewCheckout', $sierraUrl, '');

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ":" . $recordId);
			if ($recordDriver->isValid()) {
				$title = $recordDriver->getTitle();
			} else {
				$title = false;
			}

			$return = ['success' => true];
			if ($title) {
				$return['message'] = translate([
					'text' => '%1% has been renewed.',
					1 => $title,
					'isPublicFacing' => true,
				]);
				$return['api']['message'] = translate([
					'text' => '%1% has been renewed.',
					1 => $title,
					'isPublicFacing' => true,
				]);
			} else {
				$return['api']['message'] = translate([
					'text' => 'Your item has been renewed',
					'isPublicFacing' => true,
				]);
			}
			$return['api']['title'] = translate([
				'text' => 'Checkout renewed successfully',
				'isPublicFacing' => true,
			]);
		} else {
			$message = translate([
				'text' => "Unable to renew your checkout",
				'isPublicFacing' => true,
			]);
			if (!empty($renewResponse) && !empty($renewResponse->description)) {
				$message .= '<br/>' . translate([
						'text' => $renewResponse->description,
						'isPublicFacing' => true,
					]);
			}
			return [
				'success' => false,
				'message' => $message,
				'api' => [
					'title' => translate([
						'text' => 'Error renewing',
						'isPublicFacing' => true,
					]),
					'message' => $message,
				]
			];
		}

		return $return;
	}

	private function getTitleFromItemLink(string $itemLink) {
		$bibId = $this->getBibIdFromItemLink($itemLink);
		$title = '';
		if ($bibId) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver((string)$bibId);
			if ($recordDriver->isValid()) {
				$title = $recordDriver->getTitle();
			} else {
				$bibIdShort = substr(str_replace('.b', '', $bibId), 0, -1);
				$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/$bibIdShort");
				if ($getBibResponse) {
					$title = $getBibResponse->title;
				}
			}
		}
		return $title;
	}
	private function getTitleByItemId(string $itemId, string $itemShortId){
		$bibId = $this->getBibIdForItem($itemId, $itemShortId);
		$title = '';
		if ($bibId) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver((string)$bibId);
			if ($recordDriver->isValid()) {
				$title = $recordDriver->getTitle();
			} else {
				$bibIdShort = substr(str_replace('.b', 'b', $bibId), 0, -1);
				$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/$bibIdShort");
				if ($getBibResponse) {
					$title = $getBibResponse->title;
				}
			}
		}
		return $title;
	}

	/**
	 * @param string $itemId
	 * @param string|null $shortId
	 * @return string|false
	 */
	private function getBibIdForItem(string $itemId, ?string $shortId) : string|false {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItem.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
		$groupedWorkItem = new GroupedWorkItem();
		$groupedWorkItem->itemId = $itemId;
		$id = false;
		if ($groupedWorkItem->find(true)) {
			$groupedWorkRecord = new GroupedWorkRecord();
			$groupedWorkRecord->id = $groupedWorkItem->groupedWorkRecordId;
			if ($groupedWorkRecord->find(true)) {
				$id = $groupedWorkRecord->recordIdentifier;
			}
		}
		if (!$id && !empty($shortId)) {
			//Lookup the bib id from the Sierra APIs
			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/items/$shortId";
			$id = $this->getBibIdFromItemLink($sierraUrl);
		}
		return $id;
	}

	private function getBibIdFromItemLink(string $itemLink) : string|false {
		$itemInfo = $this->_callUrl('sierra.getItemInfo', $itemLink);
		if (!empty($itemInfo)) {
			if (empty($itemInfo->bibIds)) {
				$id = false;
			}else if (is_array($itemInfo->bibIds)) {
				$id = reset($itemInfo->bibIds);
				$id = '.b' . $id . $this->getCheckDigit($id);
			}else if (is_string($itemInfo->bibIds)) {
				$id = $itemInfo->bibIds;
				$id = '.b' . $id . $this->getCheckDigit($id);
			}else{
				$id = false;
			}
		} else {
			$id = false;
		}
		return $id;
	}

	function freezeHold(User $patron, string $recordId, string $itemToFreezeId, ?string $dateToReactivate): array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/$itemToFreezeId";
		$params = [
			'freeze' => true,
		];
		$freezeResponse = $this->_sendPage('sierra.freezeHold', 'PUT', $sierraUrl, $params);
		if (!$freezeResponse) {
			return [
				'success' => true,
				'message' => translate([
					'text' => "Hold frozen successfully.",
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Hold frozen',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Hold frozen successfully',
						'isPublicFacing' => true,
					]),
				],
			];
		} else {
			$return = [
				'success' => false,
				'message' => translate([
					'text' => 'Unable to freeze your hold.',
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Unable to freeze hold',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Unable to freeze your hold.',
						'isPublicFacing' => true,
					]),
				],
			];
			$return['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $freezeResponse->description)),
					'isPublicFacing' => true,
				]);
			$return['api']['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $freezeResponse->description)),
					'isPublicFacing' => true,
				]);
			return $return;
		}
	}

	function thawHold(User $patron, string $recordId, string $itemToThawId): array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/$itemToThawId";
		$params = [
			'freeze' => false,
		];
		$thawResponse = $this->_sendPage('sierra.thawHold', 'PUT', $sierraUrl, json_encode($params));
		if (!$thawResponse) {
			return [
				'success' => true,
				'message' => translate([
					'text' => 'Hold thawed successfully.',
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Hold thawed',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Hold thawed successfully.',
						'isPublicFacing' => true,
					]),
				],
			];
		} else {
			$return = [
				'success' => true,
				'message' => translate([
					'text' => "Unable to thaw your hold.",
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Error thawing hold',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Unable to thaw your hold.',
						'isPublicFacing' => true,
					]),
				],
			];
			$return['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $thawResponse->description)),
					'isPublicFacing' => true,
				]);
			$return['api']['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $thawResponse->description)),
					'isPublicFacing' => true,
				]);
			return $return;
		}
	}

	function changeHoldPickupLocation(User $patron, string $holdId, string $newPickupLocation, ?string $newPickupSublocation = null): array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/$holdId";
		$params = [
			'pickupLocation' => $newPickupLocation,
		];
		$changePickupResponse = $this->_sendPage('sierra.changePickupLocation', 'PUT', $sierraUrl, json_encode($params));
		if (!$changePickupResponse) {
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

		} else {
			$message = translate([
					'text' => 'Sorry, the pickup location of your hold could not be changed.',
					'isPublicFacing' => true,
				]) . " $changePickupResponse->ErrorMessage";
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to update pickup location',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = trim(str_replace('WebPAC Error : ', '', $changePickupResponse->ErrorMessage));

		}
		return $result;
	}

	public function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/$cancelId";
		$cancelHoldResponse = $this->_sendPage('sierra.cancelHold', 'DELETE', $sierraUrl, '');
		if (!$cancelHoldResponse) {
			return [
				'success' => true,
				'message' => translate([
					'text' => 'Hold cancelled successfully.',
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Hold cancelled',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Hold cancelled successfully.',
						'isPublicFacing' => true,
					]),
				],
			];
		} else {
			$return = [
				'success' => true,
				'message' => translate([
					'text' => "Unable to cancel your hold.",
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Error cancelling hold',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Unable to cancel your hold. ',
						'isPublicFacing' => true,
					]),
				],
			];
			$return['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $cancelHoldResponse->description)),
					'isPublicFacing' => true,
				]);
			$return['api']['message'] .= ' ' . translate([
					'text' => trim(str_replace('WebPAC Error : ', '', $cancelHoldResponse->description)),
					'isPublicFacing' => true,
				]);
			return $return;
		}
	}

	public function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) : array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$hold_result = [
			'success' => false,
			'message' => translate([
				'text' => 'There was an error placing your hold.',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Unable to place hold',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'There was an error placing your hold.',
					'isPublicFacing' => true,
				]),
			],
		];

		if (strpos($recordId, ':')) {
			[
				,
				$recordId,
			] = explode(':', $recordId);
		}

		$recordType = substr($recordId, 1, 1);
		$recordNumber = substr($recordId, 2, -1);

		$params = [
			'recordType' => $recordType,
			'recordNumber' => (int)$recordNumber,
			'pickupLocation' => $pickupBranch,
		];

		if ($cancelDate != null) {
			$params['neededBy'] = $cancelDate;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$hold_result['bib'] = $recordId;
		if (!$record) {
			$hold_result['title'] = 'Unknown';
		} else {
			$title = $record->getTitle();
			$hold_result['title'] = $title;
		}

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/$patron->unique_ils_id/holds/requests";
		$placeHoldResponse = $this->_postPage('sierra.placeHold', $sierraUrl, json_encode($params));
		if ($placeHoldResponse == null && ($this->lastResponseCode == 200 || $this->lastResponseCode = 204)) {
			$hold_result['success'] = true;
			$hold_result['message'] = translate([
				'text' => "Your hold was placed successfully. It may take up to a minute for the hold to appear on your account.",
				'isPublicFacing' => true,
			]);

			$hold_result['api']['title'] = translate([
				'text' => 'Hold placed successfully',
				'isPublicFacing' => true,
			]);
			$hold_result['api']['message'] = translate([
				'text' => 'Your hold was placed successfully. It may take up to a minute for the hold to appear on your account.',
				'isPublicFacing' => true,
			]);
			//Do not show go to holds for Sierra since it may take 30 seconds or so for the hold to be available in the patron account
		} else {
			//Get the hold form
			$message = $placeHoldResponse->description ?? $placeHoldResponse->name;
			$hold_result['success'] = false;
			$hold_result['message'] = translate([
				'text' => $message,
				'isPublicFacing' => true,
			]);

			$hold_result['api']['title'] = translate([
				'text' => $message,
				'isPublicFacing' => true,
			]);
			$hold_result['api']['message'] = translate([
				'text' => $message,
				'isPublicFacing' => true,
			]);
			if (isset($placeHoldResponse->code) && isset($placeHoldResponse->details->itemsAsVolumes)) {
				$items = [];
				foreach ($placeHoldResponse->details->itemsAsVolumes as $itemFromSierra) {
					$status = $itemFromSierra->status->display;
					if ($itemFromSierra->status->code == '-' && !empty( $itemFromSierra->status->duedate)) {
						$status = 'CHECKED OUT';
					}
					$items[] = [
						'itemNumber' => '.i' . $itemFromSierra->id . $this->getCheckDigit($itemFromSierra->id),
						'location' => $itemFromSierra->location->name,
						'callNumber' => $itemFromSierra->callNumber,
						'status' => $status,
					];
				}
				$sorter = function ($a, $b){
					if ($a['location'] == $b['location']) {
						if ($a['callNumber'] == $b['callNumber']) {
							return 0;
						}
						return strnatcasecmp($b['callNumber'], $a['callNumber']);
					}
					return strnatcasecmp($a['location'], $b['location']);
				};
				uasort($items, $sorter);
				$hold_result['items'] = $items;
			}
		}

		return $hold_result;
	}

	public function placeItemHold(User $patron, string $recordId, string $itemId, string $pickupBranch, ?string $cancelDate = null, ?string $pickupSublocation = null) : array {
		return $this->placeHold($patron, $itemId, $pickupBranch, $cancelDate);
	}

	/**
	 * TODO: This should be updated to not use screen scraping
	 */
	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation = null) : array {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch);
	}

	public function hasFastRenewAll() : bool {
		return false;
	}

	public function renewAll(User $patron) : array {
		return [
			'success' => 'false',
			'message' => 'Renew All not implemented for Sierra, renew one at a time'
		];
	}

	public function patronLogin($username, $password, $validatedViaSSO) : User|AspenError|null {
		global $library;
		$username = trim($username);
		$password = trim($password);
		if ($this->accountProfile == null) {
			return null;
		}else {
			$loginMethod = $this->accountProfile->loginConfiguration;
		}
		if ($loginMethod == 'barcode_pin' || $loginMethod == 'name_barcode') {

			//No validate the barcode and pin
			$params = [
				'barcode' => $username,
				'pin' => $password,
				'caseSensitivity' => false,
			];

			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/validate";
			$this->_postPage('sierra.validatePatron', $sierraUrl, json_encode($params));
			if ($this->lastResponseCode != 204) {
				return null;
			}


		} else { // $loginMethod == 'name_barcode'
			//TODO: Do validation using name_barcode login
			return null;
		}

		//We've passed validation, get information for the patron
		$patronInfo = $this->getPatronInfoByBarcode($username);

		if (!$patronInfo) {
			return null;
		}

		$userExistsInDB = false;
		$user = new User();
		$user->source = $this->accountProfile->name;
		$user->username = $patronInfo->id;
		$user->unique_ils_id = $patronInfo->id;
		if ($user->find(true)) {
			$userExistsInDB = true;
		}
		$barcodeField = null;
		$usernameField = null;
		if (!empty($patronInfo->varFields)) {
			foreach ($patronInfo->varFields as $varField) {
				if ($varField->fieldTag == 'b') {
					$barcodeField = $varField;
				}else if ($varField->fieldTag == $library->usernameField) {
					$usernameField = $varField;
				}
			}
		}
		if ($barcodeField != null) {
			$user->cat_username = $barcodeField->content;
			$user->ils_barcode = $barcodeField->content;
		} else {
			$user->cat_username = $username;
			$user->ils_barcode = $username;
		}
		$user->cat_password = $password;
		$user->ils_password = $password;
		if ($usernameField != null) {
			$user->ils_username = $usernameField->content;
		}

		$forceDisplayNameUpdate = false;
		$primaryName = reset($patronInfo->names);
		if (str_contains($primaryName, ',')) {
			[
				$lastName,
				$firstName,
			] = explode(',', $primaryName, 2);
		} else {
			$lastName = $primaryName;
			$firstName = '';
		}
		$firstName = trim($firstName);
		$lastName = trim($lastName);
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		if ($user->lastname != $lastName) {
			$user->lastname = $lastName;
			$forceDisplayNameUpdate = true;
		}
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		$this->loadContactInformationFromApiResult($user, $patronInfo);

		if ($userExistsInDB) {
			$user->update();
		} else {
			if ($this->isOptedIntoReadingHistoryInILS($user)) {
				$user->trackReadingHistory = 1;
				$user->forceReadingHistoryLoad = 1;
			}
			$user->created = date('Y-m-d');
			if (!$user->insert()) {
				return null;
			}
		}
		return $user;
	}

	private array $_patronInfoByBarcode = [];
	public function getPatronInfoByBarcode($barcode) {
		if (array_key_exists($barcode, $this->_patronInfoByBarcode)){
			return $this->_patronInfoByBarcode[$barcode];
		}
		$params = [
			'varFieldTag' => 'b',
			'varFieldContent' => $barcode,
			'fields' => 'id,names,deleted,suppressed,addresses,phones,emails,expirationDate,homeLibraryCode,moneyOwed,patronType,patronCodes,blockInfo,message,pMessage,langPref,fixedFields,varFields,updatedDate,createdDate,birthDate',
		];

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/find?";
		$sierraUrl .= http_build_query($params);

		$response = $this->_callUrl('sierra.findPatronByBarcode', $sierraUrl);
		if (!$response) {
			$this->_patronInfoByBarcode[$barcode] = false;
		} else {
			if (!empty($response->deleted) || !empty($response->suppressed) || (!empty($response->httpStatus) && $response->httpStatus == 404)) {
				$this->_patronInfoByBarcode[$barcode] = false;
			} else {
				$this->_patronInfoByBarcode[$barcode] = $response;
			}
		}
		return $this->_patronInfoByBarcode[$barcode];
	}

	public function getPatronInfoByUsername($username) {
		global $library;
		if (empty($library->usernameField)) {
			return false;
		}
		$params = [
			'varFieldTag' => $library->usernameField,
			'varFieldContent' => $username,
			'fields' => 'id,names,deleted,suppressed,addresses,phones,emails,expirationDate,homeLibraryCode,moneyOwed,patronType,patronCodes,blockInfo,message,pMessage,langPref,fixedFields,varFields,updatedDate,createdDate',
		];

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/find?";
		$sierraUrl .= http_build_query($params);

		$response = $this->_callUrl('sierra.findPatronByBarcode', $sierraUrl);
		if (!$response) {
			return false;
		} else if (isset($response->httpStatus) && ($response->httpStatus != 200)) {
			return false;
		} else {
			if ($response->deleted || $response->suppressed) {
				return false;
			} else {
				return $response;
			}
		}
	}

	public function getPatronsByIdList($ids) {
		$params = [
			'id' => implode(",", $ids),
			'fields' => 'id,names,deleted,suppressed,addresses,phones,emails,expirationDate,homeLibraryCode,moneyOwed,patronType,patronCodes,blockInfo,message,pMessage,langPref,fixedFields,varFields,updatedDate,createdDate,birthDate',
		];

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/?";
		$sierraUrl .= http_build_query($params);

		$response = $this->_callUrl('sierra.findPatronsByIdList', $sierraUrl);
		if (!$response) {
			return false;
		} else {
			if (!empty($response->httpStatus) && $response->httpStatus == 404) {
				return false;
			} else {
				return $response;
			}
		}
	}

	public function deletePatronById($id) : bool {
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $id;

		/** @noinspection PhpUnusedLocalVariableInspection */
		$response = $this->_sendPage('sierra.deletePatron', 'DELETE', $sierraUrl);
		if ($this->lastResponseCode == 204) {
			return true;
		} else {
			return false;
		}
	}


	public function findNewUser($patronBarcode, $patronUsername) : bool|User {
		global $library;
		if (!empty($patronBarcode)) {
			$patronInfo = $this->getPatronInfoByBarcode($patronBarcode);
		}else{
			$patronInfo = $this->getPatronInfoByUsername($patronUsername);
		}

		if (!$patronInfo) {
			return false;
		}

		$userExistsInDB = false;
		$user = new User();
		$user->source = $this->accountProfile->name;
		$user->username = $patronInfo->id;
		$user->unique_ils_id = $patronInfo->id;
		if ($user->find(true)) {
			$userExistsInDB = true;
		}
		$barcodeField = null;
		$usernameField = null;
		if (!empty($patronInfo->varFields)) {
			foreach ($patronInfo->varFields as $varField) {
				if ($varField->fieldTag == 'b') {
					$barcodeField = $varField;
				}else if ($varField->fieldTag == $library->usernameField) {
					$usernameField = $varField;
				}
			}
		}
		if ($barcodeField != null) {
			$user->cat_username = $barcodeField->content;
			$user->ils_barcode = $barcodeField->content;
		} else {
			if (!empty($patronBarcode)) {
				$user->cat_username = $patronBarcode;
				$user->ils_barcode = $patronBarcode;
			}
		}
		if ($usernameField != null) {
			$user->ils_username = $usernameField->content;
		} else {
			if (!empty($patronUsername)) {
				$user->ils_username = $patronUsername;
			}
		}

		$forceDisplayNameUpdate = false;
		$primaryName = '';
		if ($patronInfo->names != null) {
			$primaryName = reset($patronInfo->names);
		}
		if (str_contains($primaryName, ',')) {
			[
				$lastName,
				$firstName,
			] = explode(',', $primaryName, 2);
		} else {
			$lastName = $primaryName;
			$firstName = '';
		}
		$firstName = trim($firstName);
		$lastName = trim($lastName);
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		if ($user->lastname != $lastName) {
			$user->lastname =$lastName;
			$forceDisplayNameUpdate = true;
		}
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		$this->loadContactInformationFromApiResult($user, $patronInfo);

		if ($userExistsInDB) {
			$user->update();
		} else {
			if ($this->isOptedIntoReadingHistoryInILS($user)) {
				$user->trackReadingHistory = 1;
				$user->forceReadingHistoryLoad = 1;
			}
			$user->created = date('Y-m-d');
			if (!$user->insert()) {
				return false;
			}
		}

		return $user;
	}

	public function findNewUserByEmail($patronEmail): bool|User  {
		return false;
	}

	public function getAccountSummary(User $patron): AccountSummary {
		$summary = $patron->getCachedAccountSummary('ils');

		if ($summary->dataIsStale || isset($_REQUEST['reload'])) {
			$patronInfo = $this->getPatronInfoByBarcode($patron->getBarcode());
			if ($patronInfo) {
				//To save time, we don't want to load full details on the checkouts. Instead, we can call the APIs just to get counts
				$numCheckoutsProcessed = 0;
				$numOverdue = 0;
				$totalCheckouts = -1;

				while ($numCheckoutsProcessed < $totalCheckouts || $totalCheckouts == -1) {
					$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id . "/checkouts?fields=default&limit=100&offset=$numCheckoutsProcessed";
					$checkouts = $this->_callUrl('sierra.getCheckouts', $sierraUrl);
					if ($totalCheckouts == -1) {
						$totalCheckouts = $checkouts->total;
					}
					foreach ($checkouts->entries as $entry) {
						$checkoutDueDate = strtotime($entry->dueDate);
						$dueDate = strtotime('midnight', $checkoutDueDate);
						$today = strtotime('midnight');
						$daysUntilDue = ceil(($dueDate - $today) / (24 * 60 * 60));
						$overdue = $daysUntilDue < 0;
						if ($overdue) {
							$numOverdue++;
						}
					}
					$numCheckoutsProcessed += count($checkouts->entries);
				}
				$summary->numCheckedOut = $totalCheckouts;
				$summary->numOverdue = $numOverdue;

				$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id . "/holds?fields=default&limit=1000";
				$holds = $this->_callUrl('sierra.getHolds', $sierraUrl);
				$numAvailableHolds = 0;
				$numUnavailableHolds = 0;
				if ($holds->total > 0) {
					foreach ($holds->entries as $sierraHold) {
						$available = false;
						$isInnReach = false;
						$recordStatus = $sierraHold->status->code;
						// check item record status
						if (preg_match($this->urlIdRegExp, $sierraHold->record, $m)) {
							$recordId = $m[1];
						} else {
							$recordId = substr($sierraHold->record, strrpos($sierraHold->record, '/') + 1);
						}
						if ($sierraHold->recordType == 'i') {
							$recordItemStatus = $sierraHold->status->code;
							// If this is an inn-reach exclude from check -- this comes later
							if (!str_contains($recordId, "@")) {
								// if the item status is "on hold shelf" (!) but the hold record status is "on hold" (0) use "on hold" status
								// the "on hold shelf" status is for another patron.
								if ($recordItemStatus != "!" && $recordStatus != '0') {
									// check for in transit status see
									if ($recordItemStatus == 't') {
										if (isset($sierraHold->priority) && (int)$sierraHold->priority == 1) {
											$recordStatus = 't';
										}
									}
								}
							} else {
								// inn-reach status
								$isInnReach = true;
								$recordStatus = $recordItemStatus;
							}
						}
						switch ((string)$recordStatus) {
							case '0':
							case '-':
								if ($isInnReach) {
									if (!empty($sierraHold->pickupByDate)) {
										$available = true;
									}
								}
								break;
							case 'b':
							case 'j':
							case 'i':
							case '!':
							case "#": // inn-reach status
								$available = true;
								break;
							default:
								//$available = false
						}
						if ($available) {
							$numAvailableHolds++;
						} else {
							$numUnavailableHolds++;
						}
					}
				}
				$summary->numAvailableHolds = $numAvailableHolds;
				$summary->numUnavailableHolds = $numUnavailableHolds;

				$summary->totalFines = $patronInfo->moneyOwed;

				//Get expiration information
				$expirationInformation = $this->getExpirationInformation($patron);
				$summary->expirationDate = $expirationInformation->expirationDate;
			}
		}

		return $summary;
	}

	public function getExpirationInformation(User $patron) : ExpirationInformation {
		$expirationInformation = new ExpirationInformation();

		$patronInfo = $this->getPatronInfoByBarcode($patron->getBarcode());
		if ($patronInfo) {
			if (!empty($patronInfo->expirationDate)) {
				[
					$yearExp,
					$monthExp,
					$dayExp,
				] = explode("-", $patronInfo->expirationDate);
				$expirationInformation->expirationDate = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
			}else{
				//No expiration date set, leave it blank
				$expirationInformation->expirationDate = 0;
			}
		}

		return $expirationInformation;
	}

	public function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade): array {
		$result = [
			'success' => false,
			'messages' => [],
		];

		if ($canUpdateContactInfo) {
			$params = [];

			$userHomeLibrary = $patron->getHomeLibrary();
			if (isset($_REQUEST['email'])) {
				$patron->email = $_REQUEST['email'];
				$params['emails'] = [$_REQUEST['email']];
			}
			if ($userHomeLibrary->allowPatronPhoneNumberUpdates) {
				$params['phones'] = [];
				if (isset($_REQUEST['phone'])) {
					$patron->phone = $_REQUEST['phone'];
					$tmpPhone = new stdClass();
					$tmpPhone->type = $userHomeLibrary->phoneField;
					$tmpPhone->number = $_REQUEST['phone'];
					$params['phones'][] = $tmpPhone;
				}
			}
			if ($userHomeLibrary->allowPatronWorkPhoneNumberUpdates) {
				if (!array_key_exists('phones', $params)) {
					$params['phones'] = [];
				}
				if (isset($_REQUEST['workPhone'])) {
					$patron->_workPhone = $_REQUEST['workPhone'];
					$tmpPhone = new stdClass();
					$tmpPhone->type = $userHomeLibrary->workPhoneField;
					$tmpPhone->number = $_REQUEST['workPhone'];
					$params['phones'][] = $tmpPhone;
				}
			}
			if ($userHomeLibrary->allowPatronAddressUpdates) {
				$params['addresses'] = [];
				$address = new stdClass();
				$address->lines = [];
				$address->type = 'a';
				$address->lines[] = $_REQUEST['address1'];
				//Add blank lines as needed
				for ($i = 2; $i < $userHomeLibrary->sierraAddressLineForCityState; $i++) {
					$address->lines[] = '';
				}
				if ($userHomeLibrary->sierraZipOnSameLineAsCityState) {
					$cityStateZip = $_REQUEST['city'] . ', ' . $_REQUEST['state'] . ' ' . $_REQUEST['zip'];
					$address->lines[] = $cityStateZip;
				}else{
					$cityState = $_REQUEST['city'] . ', ' . $_REQUEST['state'];
					$address->lines[] = $cityState;
					$address->lines[] = $_REQUEST['zip'];
				}

				$params['addresses'][] = $address;
			}

			if (!empty($_REQUEST['notices'])) {
				$params['fixedFields'] = [];
				$noticeField = new stdClass();
				$fieldValue = new stdClass();
				$fieldValue->label = 'Notice Preference';
				$fieldValue->value = $_REQUEST['notices'];
				$noticeField->{'268'} = $fieldValue;
				$params['fixedFields']['268'] = $fieldValue;
			}

			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id;
			/** @noinspection PhpUnusedLocalVariableInspection */
			$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));

			if ($this->lastResponseCode == 204) {
				$result['success'] = true;
				$result['messages'][] = 'Your account was updated successfully.';
				$patron->update();
			} else {
				$result['messages'][] = 'Unable to update patron. ' . $this->lastErrorMessage;
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}

		return $result;
	}

	public function getSelfRegistrationTerms() : ?SelfRegistrationTerms {
		global $library;

		if (!empty($library->selfRegistrationFormId)) {
			require_once ROOT_DIR . '/sys/SelfRegistrationForms/SierraSelfRegistrationForm.php';
			$selfRegistrationForm = new SierraSelfRegistrationForm();
			$selfRegistrationForm->id = $library->selfRegistrationFormId;
			if ($selfRegistrationForm->find(true)) {
				$tosId = $selfRegistrationForm->termsOfServiceSetting;
				require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationTerms.php';
				$tos = new SelfRegistrationTerms();
				$tos->id = $tosId;
				if ($tosId != -1){
					if ($tos->find(true)) {
						return $tos;
					}
				}
			}
			return null;
		}
		return null;
	}

	public function getSelfRegistrationFields() : array {
		global $library;

		$pickupLocations = [];
		$location = new Location();
		//0 = no restrictions (ignore location setting)
		if ($library->selfRegistrationLocationRestrictions == 1) {
			//All Library Locations (ignore location setting)
			$location->libraryId = $library->libraryId;
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
				$pickupLocations[$location->code] = $location->displayName;
			}
			if (count($pickupLocations) > 1) {
				array_unshift($pickupLocations, translate([
					'text' => 'Please select a location',
					'isPublicFacing' => true,
				]));
			}
		}

		global $library;
		$hasCustomSelfRegistrationFrom = false;

		$customFields = [];
		if (!empty($library->selfRegistrationFormId)) {
			require_once ROOT_DIR . '/sys/SelfRegistrationForms/SierraSelfRegistrationForm.php';
			$selfRegistrationForm = new SierraSelfRegistrationForm();
			$selfRegistrationForm->id = $library->selfRegistrationFormId;
			if ($selfRegistrationForm->find(true)) {
				$customFields = $selfRegistrationForm->getFields();
				if ($customFields != null && count($customFields) > 0) {
					$hasCustomSelfRegistrationFrom = true;
				}
			}
		}

		$pickupLocationField = [
			'property' => 'pickupLocation',
			'type' => 'enum',
			'label' => 'Home Library',
			'description' => 'Please choose the Library location you would prefer to use',
			'values' => $pickupLocations,
			'required' => true,
		];

		$fields = [];
		if ($hasCustomSelfRegistrationFrom) {
			$fields['librarySection'] = [
				'property' => 'librarySection',
				'type' => 'section',
				'label' => 'Library',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['identitySection'] = [
				'property' => 'identitySection',
				'type' => 'section',
				'label' => 'Identity',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['mainAddressSection'] = [
				'property' => 'mainAddressSection',
				'type' => 'section',
				'label' => 'Main Address',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['contactInformationSection'] = [
				'property' => 'contactInformationSection',
				'type' => 'section',
				'label' => 'Contact Information',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			//Use self registration fields
			/** @var SelfRegistrationFormValues $customField */
			foreach ($customFields as $customField) {
				if ($customField->ilsName == 'library') {
					if (count($pickupLocations) == 1) {
						$fields['librarySection'] = [
							'property' => 'librarySection',
							'type' => 'section',
							'label' => 'Library',
							'hideInLists' => true,
							'expandByDefault' => true,
							'properties' => [
								$customField->ilsName => $pickupLocationField,
							],
							'hiddenByDefault' => true,
						];
					} else {
						$fields['librarySection'] = [
							'property' => 'librarySection',
							'type' => 'section',
							'label' => 'Library',
							'hideInLists' => true,
							'expandByDefault' => true,
							'properties' => [
								$customField->ilsName => $pickupLocationField,
							],
						];
					}
				} elseif ($customField->ilsName == 'zip' && !empty($library->validSelfRegistrationZipCodes)) {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note,
						'validationPattern' => $library->validSelfRegistrationZipCodes,
						'validationMessage' => translate([
							'text' => 'Please enter a valid zip code',
							'isPublicFacing' => true,
						]),
					];
				} elseif ($customField->ilsName == 'city' && $selfRegistrationForm->cityDropdown) {
					$cityOptions = [];
					$validCities = new SierraSelfRegistrationMunicipalityValues();
					$validCities->municipalityType = 'city';
					$validCities->find();
					while ($validCities->fetch()) {
						$cityOptions[$validCities->municipality] = $validCities->municipality;
					}
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => 'enum',
						'values' => $cityOptions,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note,
					];
				} elseif ($customField->ilsName == 'state') {
					if (!empty($library->validSelfRegistrationStates)){
						$validStates = explode('|', $library->validSelfRegistrationStates);
						$validStates = array_combine($validStates, $validStates);
						$fields[$customField->section]['properties'][] = [
							'property' => $customField->ilsName,
							'type' => 'enum',
							'values' => $validStates,
							'label' => $customField->displayName,
							'required' => $customField->required,
							'note' => $customField->note,
						];
					} else {
						$fields[$customField->section]['properties'][] = [
							'property' => $customField->ilsName,
							'type' => $customField->fieldType,
							'label' => $customField->displayName,
							'required' => $customField->required,
							'note' => $customField->note,
							'maxLength' => 2,
						];
					}
				} elseif ($customField->ilsName == 'pin') {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => 'pin',
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note
					];
					$fields[$customField->section]['properties']['pinConfirmation'] = [
						'property' => 'pinConfirmation',
						'type' => 'pinConfirmation',
						'label' => 'Confirm PIN',
						'required' => true
					];
				} else if ($customField->ilsName == 'noticePreference') {
					if (!empty($selfRegistrationForm->selfRegNoticePrefOptions)) {
						$noticePrefValues = json_decode($selfRegistrationForm->selfRegNoticePrefOptions);
					} else {
						$noticePrefValues = $this->getValidNotificationOptions();
						if (!empty($selfRegistrationForm)) {
							$selfRegistrationForm->selfRegNoticePrefOptions = json_encode($noticePrefValues);
							$selfRegistrationForm->update();
						}
					}
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => 'enum',
						'values' => $noticePrefValues,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note
					];
				} else {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note
					];
				}
			}
			foreach ($fields as $section) {
				if ($section['type'] == 'section') {
					if (empty($section['properties'])) {
						unset ($fields[$section['property']]);
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * @throws DateMalformedIntervalStringException
	 */
	public function selfRegister(): array {
		global $library;
		$selfRegResult = [
			'success' => false,
			'message' => 'Unknown Error while registering your account'
		];

		$selfRegistrationForm = null;
		$formFields = null;
		$municipalities = null;
		if ($library->selfRegistrationFormId > 0){
			$selfRegistrationForm = new SierraSelfRegistrationForm();
			$selfRegistrationForm->id = $library->selfRegistrationFormId;
			if ($selfRegistrationForm->find(true)) {
				$formFields = $selfRegistrationForm->getFields();
				$municipalities = $selfRegistrationForm->getMunicipalities();
			}else {
				$selfRegistrationForm = null;
			}
		}

		$params = [];

		if ($formFields != null) {
			foreach ($formFields as $fieldObj){
				$field = $fieldObj->ilsName;
				if ($field == 'firstName') {
					if (!empty($_REQUEST['middleName'])) {
						$fullName = $_REQUEST['lastName'] . ', ' . $_REQUEST['firstName'] . ' ' . $_REQUEST['middleName'];
					} else {
						$fullName = $_REQUEST['lastName'] . ', ' . $_REQUEST['firstName'];
					}
					$params['names'] = [$fullName];
				}
				elseif ($field == 'birthDate') {
					$params['birthDate'] = $_REQUEST['birthDate'];
				}
				elseif ($field == 'guardian') {
					if (!empty($_REQUEST['guardian'])) {
						$params['varFields'][] = [
							'fieldTag' => $selfRegistrationForm->selfRegGuardianField,
							'content' => $_REQUEST['guardian']
						];
					}
				}
				elseif ($field == 'email') {
					$params['emails'] = [$_REQUEST['email']];
					if ($selfRegistrationForm->selfRegEmailBarcode) {
						$params['barcodes'] = [$_REQUEST['email']];
					}
				}
				elseif ($field == 'phone') {
					$tmpPhone = new stdClass();
					$tmpPhone->type = $selfRegistrationForm->selfRegTelephoneField;
					$tmpPhone->number = $_REQUEST['phone'];
					$params['phones'][] = $tmpPhone;
				}
				elseif ($field == 'street') {
					$params['addresses'] = [];
					$address = new stdClass();
					$address->lines = [];
					$address->type = 'a';
					$address->lines[] = $_REQUEST['street'];
					//Add blank lines as needed
					for ($i = 2; $i < $library->sierraAddressLineForCityState; $i++) {
						$address->lines[] = '';
					}
					if ($selfRegistrationForm->noCommaInAddress){
						$cityState = $_REQUEST['city'] . ' ' . $_REQUEST['state'];
					} else {
						$cityState = $_REQUEST['city'] . ', ' . $_REQUEST['state'];
					}
					if ($library->sierraZipOnSameLineAsCityState) {
						$cityStateZip = $cityState . ' ' . $_REQUEST['zip'];
						$address->lines[] = $cityStateZip;
					}else{
						$address->lines[] = $cityState;
						$address->lines[] = $_REQUEST['zip'];
					}

					$params['addresses'][] = $address;
				}
				elseif ($field == 'barcode') {
					$params['barcodes'] = [$_REQUEST['barcode']];
				}
				elseif ($field == 'pin') {
					$params['pin'] = $_REQUEST['pin'];
				}
			}

			$barcodePrefix = '';
			// set barcode suffix length to 7 if not set
			$barcodeSuffixLength = 7;
			if (!$selfRegistrationForm->selfRegUsePatronIdBarcode) {
				if (!empty($selfRegistrationForm->selfRegBarcodePrefix)) {
					$barcodePrefix = $selfRegistrationForm->selfRegBarcodePrefix;
				}
				if (!empty($selfRegistrationForm->selfRegBarcodeSuffixLength)) {
					$barcodeSuffixLength = $selfRegistrationForm->selfRegBarcodeSuffixLength;
				}
				$barcode = $this->generateBarcode($barcodePrefix, $barcodeSuffixLength);

				if ($barcode) {
					$params['barcodes'] = [$barcode];
				} else {
					return [
						'success' => false,
						'message' => 'Could not generate a valid library card number. Please try again later.'
					];
				}
			} else {
				$params['barcodes'] = [''];
			}

			if (!empty($selfRegistrationForm->selfRegExpirationDays)) {
				$expirationDays = $selfRegistrationForm->selfRegExpirationDays;
			} else {
				$expirationDays = 30;
			}
			$expirationDate = new DateTime();
			$expirationDate->add(new DateInterval('P' . $expirationDays . 'D'));
			$params['expirationDate'] = $expirationDate->format('Y-m-d');

			$params['homeLibraryCode'] = $_REQUEST['pickupLocation'];
			$params['patronType'] = (int)$selfRegistrationForm->selfRegPatronType;
			$params['patronCodes'] = [
				'pcode1' => $selfRegistrationForm->selfRegPcode1,
				'pcode2' => $selfRegistrationForm->selfRegPcode2,
				'pcode3' => (int)$selfRegistrationForm->selfRegPcode3,
				'pcode4' => (int)$selfRegistrationForm->selfRegPcode4
			];
			$params['pMessage'] = $selfRegistrationForm->selfRegPatronMessage;
			if (!empty($_REQUEST['noticePreference'])) {
				$params['fixedFields'] = [
					'268' => [
						'label' => 'Notice Preference',
						'value' => $_REQUEST['noticePreference']
					],
				];
			} else {
				$params['fixedFields'] = [
					'268' => [
						'label' => 'Notice Preference',
						'value' => $selfRegistrationForm->selfRegNoticePref
					],
				];
			}
			if ($selfRegistrationForm->selfRegUseAgency) {
				$params['fixedFields']['158'] = [
					'label' => 'Patron Agency',
					'value' => (string)$selfRegistrationForm->selfRegAgency
				];
			}
			if ($selfRegistrationForm->addSelfRegNote) {
				$params['varFields'][] = [
					'fieldTag' => 'x',
					'content' => translate([
						'text' => 'Patron self-registered on %1%.',
						1 => date('m/d/Y'),
						'isPublicFacing' => 'false'
					]),
				];
			}

			// Override with any municipality-specific settings
			if (!empty($municipalities)) {
				// Use Google Geocoding API to get patron's municipality
				if (!empty($params['addresses'])) {
					$address = implode(", ", $params['addresses'][0]->lines);
					$address = str_replace("\r\n", ",", $address);
					$address = str_replace(" ", "+", $address);
					$address = str_replace("#", "", $address);

					require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
					$googleSettings = new GoogleApiSetting();
					if ($googleSettings->find(true)) {
						if (!empty($googleSettings->googleMapsKey)) {
							$apiKey = $googleSettings->googleMapsKey;
							$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' . $apiKey;

							// fetch google geocode data
							$curl = new CurlWrapper();
							$response = $curl->curlGetPage($url);
							$data = json_decode($response);
							$curl->close_curl();

							if ($data->status == 'OK') {
								$components = $data->results[0]->address_components;

								$city = '';
								$county = '';
								$state = '';
								foreach ($components as $component) {
									if ($component->types[0] == 'locality') {
										$city = $component->short_name;
									}
									else if ($component->types[0] == 'administrative_area_level_2') {
										$county = $component->short_name;
									}
									else if ($component->types[0] == 'administrative_area_level_1') {
										$state = $component->short_name;
									}
								}
								$matchId = null;
								if ($city != '') {
									$matchId = $selfRegistrationForm->getMunicipalitySettingsByNameAndType($city, 'city');
								}
								if (!$matchId && $county != '') {
									$matchId = $selfRegistrationForm->getMunicipalitySettingsByNameAndType($county, 'county');
								}
								if (!$matchId && $state != '') {
									$matchId = $selfRegistrationForm->getMunicipalitySettingsByNameAndType($state, 'state');
								}
								if (!$matchId) {
									$matchId = $selfRegistrationForm->getMunicipalitySettingsByNameAndType('other');
								}
								if ($matchId) {
									// Abort if self-registration is not allowed
									if (!$municipalities[$matchId]->selfRegAllowed) {
										return [
											'success' => false,
											'message' => translate([
												'text' => "Your address is not within the library’s service area. Please contact the library for more information.",
												'isPublicFacing' => true
											])
										];
									}
									// Override PType and PCode Settings according to match settings
									if (!empty($municipalities[$matchId]->expirationLength)) {
										$expirationDays = $municipalities[$matchId]->expirationLength;
										$expirationDate = new DateTime();
										if (!empty($municipalities[$matchId]->expirationPeriod)) {
											$expirationPeriod = $municipalities[$matchId]->expirationPeriod;
										} else {
											$expirationPeriod = "D";
										}
										$expirationDate->add(new DateInterval('P' . $expirationDays . $expirationPeriod));
										$params['expirationDate'] = $expirationDate->format('Y-m-d');
									}
									if (!empty($municipalities[$matchId]->sierraPType) && $municipalities[$matchId]->sierraPType != -1) {
										$params['patronType'] = (int)$municipalities[$matchId]->sierraPType;
									}
									if (!empty($municipalities[$matchId]->sierraPTypeApproved) && $municipalities[$matchId]->sierraPTypeApproved != -1) {
										$sierraPTypeApproved = (int)$municipalities[$matchId]->sierraPTypeApproved;
									}
									if (!empty($municipalities[$matchId]->sierraPCode1)) {
										$params['patronCodes']['pcode1'] = $municipalities[$matchId]->sierraPCode1;
									}
									if (!empty($municipalities[$matchId]->sierraPCode2)) {
										$params['patronCodes']['pcode2'] = $municipalities[$matchId]->sierraPCode2;
									}
									if (!empty($municipalities[$matchId]->sierraPCode3 && $municipalities[$matchId]->sierraPCode3 != -1)) {
										$params['patronCodes']['pcode3'] = (int)$municipalities[$matchId]->sierraPCode3;
									}
									if (!empty($municipalities[$matchId]->sierraPCode1) && $municipalities[$matchId]->sierraPCode4 != -1) {
										$params['patronCodes']['pcode4'] = (int)$municipalities[$matchId]->sierraPCode4;
									}
								}
							}
						}
					}
				}
			}
		}
		if (!$selfRegistrationForm->selfRegNoDuplicateCheck) {
			if ($this->checkForDuplicateUsers($_REQUEST['lastName'], $_REQUEST['firstName'], $params['birthDate'])) {
				return [
					'success' => false,
					'message' => translate([
						'text' => "It looks like you already have an account with the library. Please sign in with your library card. If you believe you're receiving this message in error, please contact the library.",
						'isPublicFacing' => true
					])
				];
			}
		}

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/";
		$createPatronResult = $this->_postPage('sierra.createPatron', $sierraUrl, json_encode($params));

		if ($this->lastResponseCode == 200) {
			$patronId = str_replace($sierraUrl, '', $createPatronResult->link);
			$newUser = null;
			$barcode = null;
			if ($selfRegistrationForm->selfRegUsePatronIdBarcode) {
				$updateBarcodeResult = $this->updateBarcode($patronId, $patronId);
				if ($updateBarcodeResult) {
					$selfRegResult = [
						'success' => true,
						'barcode' => $patronId
					];
					$barcode = $patronId;
					$newUser = $this->findNewUser($barcode, null);
				} else {
					$selfRegResult = [
						'success' => false,
						'message' => translate([
							'text' => 'Unable to assign barcode.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				$selfRegResult = [
					'success' => true,
					'barcode' => $params['barcodes'][0]
				];
				$barcode = $params['barcodes'][0];
				$newUser = $this->findNewUser($barcode, null);
			}
			if ($newUser != null) {
				$selfRegResult['newUser'] = $newUser;
				$selfRegResult['sendWelcomeMessage'] = true;
			}
			if ($library->logSelfRegistrations) {
				// Add to registration table
				require_once ROOT_DIR . '/sys/SelfRegistrationForms/SierraRegistration.php';
				$registration = new SierraRegistration();
				$registration->barcode = $barcode;
				$registration->patronId = $patronId;
				if (!empty($params['patronType'])) {
					$registration->sierraPType = $params['patronType'];
				}
				if (!empty($sierraPTypeApproved)) {
					$registration->sierraPTypeApproved = $sierraPTypeApproved;
				}
				if (!empty($params['patronCodes']['pcode1'])) {
					$registration->sierraPCode1 = $params['patronCodes']['pcode1'];
				}
				if (!empty($params['patronCodes']['pcode2'])) {
					$registration->sierraPCode2 = $params['patronCodes']['pcode2'];
				}
				if (!empty($params['patronCodes']['pcode3'])) {
					$registration->sierraPCode3 = $params['patronCodes']['pcode3'];
				}
				if (!empty($params['patronCodes']['pcode4'])) {
					$registration->sierraPCode4 = $params['patronCodes']['pcode4'];
				}
				global $locationSingleton;
				$activeLocation = $locationSingleton->getActiveLocation();
				if (!empty($activeLocation)) {
					$registration->locationId = $activeLocation->id;
				}
				if (!empty($library->libraryId)) {
					$registration->libraryId = $library->libraryId;
				}
				$registration->insert();
			}
		}

		return $selfRegResult;
	}

	private function generateBarcode($barcodePrefix, $barcodeSuffixLength) : ?string {
		$foundValidBarcode = false;
		$attempts = 0;
		$maxAttempts = 10;

		$barcode = null;
		while (!$foundValidBarcode && $attempts < $maxAttempts) {
			$barcode = $barcodePrefix;
			for ($i = 0; $i < $barcodeSuffixLength; $i++) {
				$barcode .= rand(0, 9);
			}
			$foundValidBarcode = $this->getPatronInfoByBarcode($barcode) === false;
			$attempts++;
		}
		return $foundValidBarcode ? $barcode : null;
	}

	private function checkForDuplicateUsers($lastName, $firstName, $birthDate): bool {
		$sierraDnaConnection = $this->connectToSierraDNA();

		$getDuplicatePatronsStmt = "SELECT prf.last_name, prf.first_name, pr.birth_date_gmt FROM sierra_view.patron_record_fullname AS prf LEFT JOIN sierra_view.patron_record AS pr ON prf.patron_record_id = pr.id WHERE UPPER(prf.last_name) = $1 AND UPPER(prf.first_name) = $2 AND pr.birth_date_gmt = $3";

		$getPatronsRS = pg_query_params($sierraDnaConnection, $getDuplicatePatronsStmt, [strtoupper(trim($lastName)), strtoupper(trim($firstName)), $birthDate]);
		if ($getPatronsRS === false || pg_num_rows($getPatronsRS) === 0) {
			// No duplicate patrons
			return false;
		} else {
			// Found one or more duplicates
			return true;
		}
	}

	private function getValidNotificationOptions($patron = null) : array {
		$sierraDnaConnection = $this->connectToSierraDNA();
		if ($patron != null) {
			$patronId = $patron->unique_ils_id;
			$getNotificationOptionsStmt = "SELECT nm.code, nm.name, (pv.notification_medium_code IS NOT NULL) AS selected 
			FROM sierra_view.notification_medium_property_myuser AS nm
			LEFT JOIN sierra_view.patron_view AS pv ON pv.notification_medium_code = nm.code AND pv.record_num = $1 ORDER BY nm.display_order;";
			$getNotificationOptionsRS = pg_query_params($sierraDnaConnection, $getNotificationOptionsStmt, [$patronId]);
		} else {
			$getNotificationOptionsStmt = "SELECT code, name FROM sierra_view.notification_medium_property_myuser ORDER BY display_order;";
			$getNotificationOptionsRS = pg_query($sierraDnaConnection, $getNotificationOptionsStmt);
		}
		if ($getNotificationOptionsRS === false) {
			return [];
		} else {
			$options = [];
			while ($curRow = pg_fetch_array($getNotificationOptionsRS, NULL, PGSQL_ASSOC)) {
				if ($patron != null) {
					$options[$curRow['code']]['name'] = $curRow['name'];
					$options[$curRow['code']]['selected'] = $curRow['selected'] == 't';
				} else {
					$options[$curRow['code']] = $curRow['name'];
				}
			}
			return $options;
		}
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool {
		return true;
	}

	/**
	 * @param User $patron
	 * @return ?string
	 */
	public function getMessagingSettingsTemplate(User $patron): ?string {
		global $interface;
		$library = $patron->getHomeLibrary();
		$notificationOptions = $this->getValidNotificationOptions($patron);
		$interface->assign('notificationOptions', $notificationOptions);
		if ($library->allowProfileUpdates) {
			$interface->assign('canSave', true);
		} else {
			$interface->assign('canSave', false);
		}

		return 'sierraMessagingSettings.tpl';
	}

	public function processMessagingSettingsForm(User $patron): array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'message' => 'Unknown error processing messaging settings.',
		];
		$noticeCode = $_REQUEST['noticePreference'];
		$updateAccountInfoResponse = $this->updateNoticePreference($noticeCode, $patron->unique_ils_id);
		if (!$updateAccountInfoResponse) {
			if (strlen($result['message']) == 0) {
				$result['message'] = 'Error processing messaging settings.';
			}
		} else {
			$result['success'] = true;
			$result['message'] = 'Your account was updated successfully.';
		}
		return $result;
	}

	private function updateBarcode($barcode, $patronId): bool {
		$params = [
			'barcodes' => [$barcode]
		];
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));
		if ($this->lastResponseCode == 204) {
			return true;
		} else {
			return false;
		}
	}

	private function updateNoticePreference($preferenceCode, $patronId): bool {
		$params = [
			'fixedFields' => [
				'268' => [
					'label' => 'Notice Preference',
					'value' => $preferenceCode
				],
			]
		];
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));
		if ($this->lastResponseCode == 204) {
			return true;
		} else {
			return false;
		}
	}

	public function updatePatronRegistration($patronObject, $patronId): bool {
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($patronObject));
		if ($this->lastResponseCode == 204) {
			return true;
		} else {
			return false;
		}
	}

	public function getPatronMetadataOptions($field): array {
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$params = [
			'fields' => $field,
		];
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/metadata?";
		$sierraUrl .= http_build_query($params);
		$metadataResponse = $this->_callUrl('sierra.getPatronMetadata', $sierraUrl);
		if ($metadataResponse && is_array($metadataResponse)) {
			$metadataOptions = [];
			foreach ($metadataResponse as $metadata) {
				foreach ($metadata->values as $option) {
					$code = $option->code;
					$metadataOptions[$metadata->field][$code] = $code . " - " . $option->desc;
				}
			}
			return $metadataOptions;
		} else {
			return [];
		}
	}

	public function getFines($patron = null, $includeMessages = false): array {
		$fines = [];

		$params = [
			'fields' => 'default,assessedDate,itemCharge,processingFee,billingFee,chargeType,paidAmount,datePaid,description,returnDate,location,description,invoiceNumber',
		];

		$patronId = $patron->unique_ils_id;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/fines?";
		$sierraUrl .= http_build_query($params);

		$finesResponse = $this->_callUrl('sierra.getFines', $sierraUrl);
		if ($finesResponse && $finesResponse->total > 0) {
			foreach ($finesResponse->entries as $fineEntry) {
				$fineUrl = $fineEntry->id;
				$fineId = substr($fineUrl, strrpos($fineUrl, '/') + 1);
				$fineAmount = $fineEntry->itemCharge + $fineEntry->processingFee + $fineEntry->billingFee;
				$message = '';
				if (isset($fineEntry->description)) {
					$message = $fineEntry->description;
				} else {
					if (isset($fineEntry->item)) {
						preg_match($this->urlIdRegExp, $fineEntry->item, $m);
						$itemIdShort = $m[1];
						$itemId = ".i" . $itemIdShort . $this->getCheckDigit($itemIdShort);
						if ($itemIdShort !=null ) {
							$message = $this->getTitleByItemId($itemId, $itemIdShort);
						} else {
							$message = translate([
								'text' => 'Title not available - contact library. ',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]);
						}
					}
				}
				$fines[] = [
					'fineId' => $fineId,
					'reason' => $fineEntry->chargeType->display,
					'type' => $fineEntry->chargeType->display,
					'amount' => $fineAmount,
					'amountVal' => $fineAmount,
					'message' => $message,
					'amountOutstanding' => $fineAmount - $fineEntry->paidAmount,
					'amountOutstandingVal' => $fineAmount - $fineEntry->paidAmount,
					'date' => date('M j, Y', strtotime($fineEntry->assessedDate)),
					'invoiceNumber' => $fineEntry->invoiceNumber,
				];
			}
		}
		return $fines;
	}

	function showOutstandingFines() : bool {
		return true;
	}

	public function completeFinePayment(User $patron, UserPayment $payment) : array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'message' => '',
		];

		$userFines = $this->getFines($patron);

		//Before adding payments, we need to

		$paymentParams = [
			'payments' => [],
		];

		// Get payment location based on system configuration
		$systemVariables = SystemVariables::getSystemVariables();
		global $locationSingleton, $library;

		if ($systemVariables && $systemVariables->libraryToUseForPayments == 1) {
			// Get active location using the singleton pattern.
			$activeLocation = $locationSingleton->getActiveLocation();
			global $logger;
			if ($activeLocation) {
				$paymentLocation = $activeLocation;
				$logger->log("Using active branch location $activeLocation->code for Sierra payments", Logger::LOG_NOTICE);
			} else if ($library) {
				// Fall back to library's main location or first alphabetical location.
				$mainLocation = $library->getMainLocation();
				if ($mainLocation) {
					$paymentLocation = $mainLocation;
					$logger->log("Using library's main location $mainLocation->code for Sierra payments.", Logger::LOG_NOTICE);
				} else {
					// Get first location alphabetically.
					$libraryLocations = new Location();
					$libraryLocations->libraryId = $library->libraryId;
					$libraryLocations->orderBy('code');
					if ($libraryLocations->find(true)) {
						$paymentLocation = clone $libraryLocations; // Shallow copy to prevent accidental modifications of the original object.
						$logger->log("Using library's first alphabetical location $paymentLocation->code for Sierra payments.", Logger::LOG_NOTICE);
					} else {
						$logger->log("No locations found for library $library->displayName, falling back to patron's home library.", Logger::LOG_WARNING);
						$paymentLocation = $patron->getHomeLocation();
					}
				}
			} else {
				$logger->log("No active library found, falling back to patron's home library.", Logger::LOG_WARNING);
				$paymentLocation = $patron->getHomeLocation();
			}
		} else {
			// Default to patron home location.
			global $logger;
			$paymentLocation = $patron->getHomeLocation();
			$logger->log("Using patron home location $paymentLocation->code for Sierra payments as per system configuration.", Logger::LOG_NOTICE);
		}

		// Set stat group if configured.
		if ($paymentLocation && $paymentLocation->statGroup != -1) {
			$paymentParams['statgroup'] = (int)$paymentLocation->statGroup;
		}

		$finePayments = explode(',', $payment->finesPaid);
		foreach ($finePayments as $finePayment) {
			[
				$fineId,
				$paymentAmount,
			] = explode('|', $finePayment);

			//Find the fine in the list of user payments so we can tell if it's fully paid or partially paid
			$fineInvoiceNumber = '';
			foreach ($userFines as $userFine) {
				if ($userFine['fineId'] == $fineId) {
					$fineInvoiceNumber = $userFine['invoiceNumber'];
					break;
				}
			}

			$paymentType = 1; //Fully or partially paid, do not waive the remainder

			$tmpPayment = new stdClass();
			$tmpPayment->amount = (int)(round((float)$paymentAmount * 100));
			$tmpPayment->paymentType = $paymentType;
			$tmpPayment->invoiceNumber = (string)$fineInvoiceNumber;
			$tmpPayment->initials = 'aspen';

			$paymentParams['payments'][] = $tmpPayment;
		}

		$patronId = $patron->unique_ils_id;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/fines/payment";

		$makePaymentResponse = $this->_sendPage('sierra.addPayment', 'PUT', $sierraUrl, json_encode($paymentParams));

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204) {
			$result['success'] = true;
		} else {
			$result['success'] = false;
			if (isset($makePaymentResponse->description)) {
				$result['message'] = $makePaymentResponse->description;
			} else {
				$result['message'] = 'Could not record fine payment.';
			}
		}
		return $result;
	}

	public function isPatronAccountLocked(User $patron, $fine) : bool {
		// Try paying $0 towards the fine - if patron record is locked API will return 500: Patron Record is Busy
		$payment = new stdClass();
		$payment->amount = 0;
		$payment->paymentType = 1;
		$payment->invoiceNumber = (string)$fine['invoiceNumber'];
		$payment->initials = 'aspen';
		$paymentParams['payments'][] = $payment;

		$patronId = $patron->unique_ils_id;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patronId . "/fines/payment";

		/** @noinspection PhpUnusedLocalVariableInspection */
		$makePaymentResponse = $this->_sendPage('sierra.addPayment', 'PUT', $sierraUrl, json_encode($paymentParams));

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204) {
			return false;
		} else {
			return true;
		}
	}

	function importListsFromIls($patron) : array {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$user = UserAccount::getLoggedInUser();
		$results = [
			'totalTitles' => 0,
			'totalLists' => 0,
		];

		//Get the page which contains a table with all lists in them.
		$listsPage = $this->_fetchPatronInfoPage($patron, 'mylists');
		//Get the actual table
		if (preg_match('/<table[^>]*?class="patFunc"[^>]*?>(.*?)<\/table>/si', $listsPage, $listsPageMatches)) {
			$allListTable = $listsPageMatches[1];
			//Now that we have the table, get the actual list names and ids
			if (preg_match_all('/<tr[^>]*?class="patFuncEntry"[^>]*?>.*?<input type="checkbox" id ="(\\d+)".*?<a.*?>(.*?)<\/a>.*?<td[^>]*class="patFuncDetails">(.*?)<\/td>.*?<\/tr>/si', $allListTable, $listDetails, PREG_SET_ORDER)) {
				for ($listIndex = 0; $listIndex < count($listDetails); $listIndex++) {
					$listId = $listDetails[$listIndex][1];
					$title = $listDetails[$listIndex][2];
					$description = str_replace('&nbsp;', '', $listDetails[$listIndex][3]);

					//Create the list (or find one that already exists)
					$newList = new UserList();
					$newList->user_id = $user->id;
					$newList->title = $title;
					if (!$newList->find(true)) {
						$newList->description = strip_tags($description);
						$newList->insert();
					} elseif ($newList->deleted == 1) {
						$newList->removeAllListEntries();
						$newList->deleted = 0;
						$newList->update();
					}

					$currentListTitles = $newList->getListTitles();
					$this->getListTitlesFromWebPAC($patron, $listId, $currentListTitles, $newList, $results, $title);

					$results['totalLists'] += 1;
				}
			} elseif (preg_match_all('~<a.*?listNum=(\d+)">(.*?)</a>~si', $allListTable, $listDetails, PREG_SET_ORDER)) {
				for ($listIndex = 0; $listIndex < count($listDetails); $listIndex++) {
					$listId = $listDetails[$listIndex][1];
					$title = $listDetails[$listIndex][2];
					$newList = new UserList();
					$newList->user_id = $user->id;
					$newList->title = $title;
					if (!$newList->find(true)) {
						$newList->insert();
					} elseif ($newList->deleted == 1) {
						$newList->removeAllListEntries();
						$newList->deleted = 0;
						$newList->update();
					}

					$currentListTitles = $newList->getListTitles();
					$this->getListTitlesFromWebPAC($patron, $listId, $currentListTitles, $newList, $results, $title);

					$results['totalLists'] += 1;
				}
			}
		}

		return $results;
	}

	/**
	 * @param $patron
	 * @param $listId
	 * @param array|null $currentListTitles
	 * @param UserList $newList
	 * @param array $results
	 * @param $title
	 */
	private function getListTitlesFromWebPAC($patron, $listId, ?array $currentListTitles, UserList $newList, array &$results, $title): void {
		//Get a list of all titles within the list to be imported
		//Increase the timeout for the page to load large lists
		$this->curlWrapper->setTimeout(240);
		$listDetailsPage = $this->_fetchPatronInfoPage($patron, 'mylists?listNum=' . $listId);
		//Get the table for the details
		$listsDetailsMatches = [];
		$matchResult = preg_match('/<table[^>]*?class="patFunc"[^>]*?>.*/si', $listDetailsPage, $listsDetailsMatches);
		if ($matchResult) {
			$listTitlesTable = $listsDetailsMatches[0];
			//Trim to the end of the table
			$endTablePosition = strpos($listTitlesTable, '</table>');
			$listTitlesTable = substr($listTitlesTable, 0, $endTablePosition);
			//Get the bib numbers for the title
			preg_match_all('/<input type="checkbox" name=".*?(b\d{1,7})".*?<span[^>]*class="patFuncTitle(?:Main)?">(.*?)<\/span>/si', $listTitlesTable, $bibNumberMatches, PREG_SET_ORDER);
			for ($bibCtr = 0; $bibCtr < count($bibNumberMatches); $bibCtr++) {
				$bibNumber = $bibNumberMatches[$bibCtr][1];
				$bibTitle = strip_tags($bibNumberMatches[$bibCtr][2]);

				//Get the grouped work for the resource
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$primaryIdentifier->identifier = '.' . $bibNumber . $this->getCheckDigit($bibNumber);
				$primaryIdentifier->type = 'ils';
				if ($primaryIdentifier->find(true)) {
					$groupedWork = new GroupedWork();
					$groupedWork->id = $primaryIdentifier->grouped_work_id;
					if ($groupedWork->find(true)) {
						//Check to see if this title is already on the list.
						$resourceOnList = false;
						foreach ($currentListTitles as $currentTitle) {
							if (($currentTitle->source == 'GroupedWork') && ($currentTitle->sourceId == $groupedWork->permanent_id)) {
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
							$listEntry->title = StringUtils::trimStringToLengthAtWordBoundary($groupedWork->full_title, 50, true);
							$listEntry->insert();
						}
					}
				} else {
					//The title is not in the resources, add an error to the results
					if (!isset($results['errors'])) {
						$results['errors'] = [];
					}
					$results['errors'][] = "\"$bibTitle\" on list $title could not be found in the catalog and was not imported.";
				}

				$results['totalTitles']++;
			}
		} else {
			$results['errors'][] = "Titles table not found for list $title.";
		}
	}

	public function loadContactInformation(User $user) : void {
		$patronInfo = $this->getPatronInfoByBarcode($user->getBarcode());

		if (!$patronInfo) {
			return;
		}
		$this->loadContactInformationFromApiResult($user, $patronInfo);
	}

	private function loadContactInformationFromApiResult(User $user, stdClass $patronInfo) : void {
		global $library;
		$userHomeLibrary = $user->getHomeLibrary() ?? $library;
		$user->_fullname = reset($patronInfo->names);
		if (!empty($patronInfo->addresses)) {
			$primaryAddress = reset($patronInfo->addresses);
			$user->_address1 = $primaryAddress->lines[0];
			$this->parseCityStateZipFromAddressLines($primaryAddress, $userHomeLibrary, $user);
		}
		if (!empty($patronInfo->phones)) {
			foreach ($patronInfo->phones as $phoneInfo) {
				if ($phoneInfo->type == $userHomeLibrary->phoneField) {
					$user->phone = $phoneInfo->number;
				}elseif ($phoneInfo->type == $userHomeLibrary->workPhoneField) {
					$user->_workPhone = $phoneInfo->number;
				}
			}
		}
		if (!empty($patronInfo->emails)) {
			$user->email = reset($patronInfo->emails);
		}

		$homeLocationCode = $patronInfo->homeLibraryCode;
		$location = new Location();
		$location->code = $homeLocationCode;
		if (!$location->find(true)) {
			unset($location);
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

		if (!empty($patronInfo->expirationDate)) {
			$user->_expires = $patronInfo->expirationDate;
			[
				$yearExp,
				$monthExp,
				$dayExp,
			] = explode("-", $user->_expires);
			$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
			$timeNow = time();
			$timeToExpire = $timeExpire - $timeNow;
			if ($timeToExpire <= 30 * 24 * 60 * 60) {
				if ($timeToExpire <= 0) {
					$user->_expired = 1;
				}
				$user->_expireClose = 1;
			}
		}

		$finesVal = $patronInfo->moneyOwed;
		$user->_fines = sprintf('$%01.2f', $finesVal);
		$user->_finesVal = $finesVal;
		$user->patronType = $patronInfo->patronType;
		$user->_notices = $patronInfo->fixedFields->{'268'}->value;
		$user->_noticePreferenceLabel = match ($user->_notices) {
			'a' => 'Mail',
			'p' => 'Telephone',
			'z' => 'Email',
			default => 'none',
		};
	}

//	function getPasswordPinValidationRules() : array {
//		return [
//			'minLength' => 4,
//			'maxLength' => 60,
//			'onlyDigitsAllowed' => false,
////		'requireStrongPassword' => false
//		];
//	}

	function updatePin(User $patron, ?string $oldPin, string $newPin): array {
		if ($patron->getPasswordOrPin() != $oldPin) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'The old PIN provided is incorrect.',
					'isPublicFacing' => true,
				]),
			];
		}
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'message' => "Unknown error updating password.",
		];
		$params = [
			'pin' => $newPin,
		];
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id;
		$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));
		if ($this->lastResponseCode == 204) {
			$result['success'] = true;
			$result['message'] = 'Your password was updated successfully.';
			$patron->cat_password = $newPin;
			$patron->update();
		} else {
			$message = translate([
				'text' => 'Unable to update PIN. ',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
			if (!empty($this->lastErrorMessage)) {
				$message .= translate([
					'text' => $this->lastErrorMessage,
					'isPublicFacing' => true,
					'inAttribute' => true,
				]);
			}
			if (!empty($updatePatronResponse) && !empty($updatePatronResponse->description)) {
				$message .= '<br/>' . translate([
						'text' => $updatePatronResponse->description,
						'isPublicFacing' => true,
						'inAttribute' => true,
					]);
			}
			$result['message'] = $message;
		}
		return $result;
	}

	public function connectToSierraDNA(): false|Connection {
		if ($this->_sierraDNAConnection == null) {
			$accountProfile = $this->accountProfile;
			$this->_sierraDNAConnection = pg_connect("host=$accountProfile->databaseHost port=$accountProfile->databasePort dbname=$accountProfile->databaseName user=$accountProfile->databaseUser password=$accountProfile->databasePassword");
		}
		return $this->_sierraDNAConnection;
	}

	public function closeSierraDNAConnection(): void {
		if ($this->_sierraDNAConnection != null) {
			pg_close($this->_sierraDNAConnection);
			$this->_sierraDNAConnection = null;
		}
	}

	/**
	 * @param string $checkoutId
	 * @return array|false
	 */
	public function getTitleAndAuthorForInnReachCheckout(string $checkoutId): array|false {
		/** @noinspection SqlResolve */
		$checkoutInfoSql = "SELECT 
			  bib_record_property.best_title as title,
			  bib_record_property.best_author as author,
			  bib_record_property.best_title_norm as sort_title
			FROM 
			  sierra_view.checkout, 
			  sierra_view.bib_record_item_record_link, 
			  sierra_view.bib_record_property
			WHERE 
			  sierra_view.checkout.id = $1
			  AND checkout.item_record_id = bib_record_item_record_link.item_record_id
			  AND bib_record_item_record_link.bib_record_id = bib_record_property.bib_record_id";
		$innReachConnection = $this->connectToSierraDNA();
		if (!$innReachConnection) {
			return false;
		}
		$res = pg_query_params($innReachConnection, $checkoutInfoSql, [$checkoutId]);
		return pg_fetch_array($res, 0);
	}

	/**
	 * @param string $holdId
	 * @return array|false
	 */
	public function getTitleAndAuthorForInnReachHold(string $holdId): array|false {
		/** @noinspection SqlResolve */
		$holdInfoSql = "SELECT 
			  bib_record_property.best_title as title,
			  bib_record_property.best_author as author,
			  bib_record_property.best_title_norm as sort_title
			FROM 
			  sierra_view.hold, 
			  sierra_view.bib_record_item_record_link, 
			  sierra_view.bib_record_property
			WHERE 
			  sierra_view.hold.id = $1
					AND sierra_view.hold.is_ir=true
					AND sierra_view.hold.record_id = bib_record_item_record_link.item_record_id
					AND bib_record_item_record_link.bib_record_id = bib_record_property.bib_record_id";
		$innReachConnection = $this->connectToSierraDNA();
		if (!$innReachConnection) {
			return false;
		}
		$res = pg_query_params($innReachConnection, $holdInfoSql, [$holdId]);
		return pg_fetch_array($res, 0);
	}

	public function showHoldPosition(): bool {
		return true;
	}

	public function showTimesRenewed(): bool {
		return true;
	}

	public function showHoldPlacedDate(): bool {
		return true;
	}

	function updateHomeLibrary(User $patron, string $homeLibraryCode) : array {
		$result = [
			'success' => false,
			'messages' => [],
		];

		if ($patron->getHomeLibrary()->allowHomeLibraryUpdates) {
			$params = [];

			if (isset($_REQUEST['homeLocation'])) {
				$location = new Location();
				$location->code = $_REQUEST['homeLocation'];
				if (!$location->find(true)) {
					$result['messages'][] = 'Could not find that home location.';
					return $result;
				}
				$patron->homeLocationId = $location->locationId;
				$params['homeLibraryCode'] = $_REQUEST['homeLocation'];
			}

			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id;
			/** @noinspection PhpUnusedLocalVariableInspection */
			$updatePatronResponse = $this->_sendPage('sierra.updatePatronHomeLocation', 'PUT', $sierraUrl, json_encode($params));

			if ($this->lastResponseCode == 204) {
				$result['success'] = true;
				$result['messages'][] = translate([
					'text' => 'Your home library was updated successfully.',
					'isPublicFacing' => true,
				]);
				$patron->update();
			} else {
				$result['messages'][] = 'Unable to update patron. ' . $this->lastErrorMessage;
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}

		return $result;
	}

	public function supportsLoginWithUsername() : bool {
		return true;
	}

	/**
	 * Returns true if reset username is a separate page independent of the patron information page
	 *
	 * @return bool
	 */
	public function showResetUsernameLink(): bool {
		global $library;
		if ($library->allowUsernameUpdates){
			return true;
		}
		return false;
	}

	public function getUsernameValidationRules(): array {
		return [
			'minLength' => 4,
			'maxLength' => 75,
			'additionalRequirements' => translate([
				'text' => 'The username may only contain letters and numbers.',
				'isPublicFacing' => true,
			]),
		];
	}

	public function updateEditableUsername(User $patron, string $username): array {
		global $library;
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'message' => 'Unknown error updating username',
		];

		$params = [];
		$params['varFields'] = [];
		$usernameField = new stdClass();
		$usernameField->fieldTag = $library->usernameField;
		$usernameField->content = $username;
		$params['varFields'][] = $usernameField;

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/" . $patron->unique_ils_id;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$updatePatronResponse = $this->_sendPage('sierra.updateEditableUsername', 'PUT', $sierraUrl, json_encode($params));

		if ($this->lastResponseCode == 204) {
			$result['success'] = true;
			$result['message'] = 'Your account was updated successfully.';
			$patron->ils_username = $username;
			$patron->update();
		} else {
			$result['message'] = 'Unable to update patron. ' . $this->lastErrorMessage;
		}

		return $result;
	}

	public function hasAPICheckout() : bool {
		return true;
	}

	public function checkoutByAPI(User $patron, $barcode, Location $currentLocation): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'There was an error checking out this title.',
				'isPublicFacing' => true,
			]),
			'title' => translate([
				'text' => 'Unable to checkout title',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Unable to checkout title',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'There was an error checking out this title.',
					'isPublicFacing' => true,
				]),
			],
			'itemData' => []
		];

		//Find the correct stat group to use
		$doCheckout = false;
		require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
		$scoSettings = new AspenLiDASelfCheckSetting();
		$checkoutLocationSetting = $scoSettings->getCheckoutLocationSetting($currentLocation->code);
		if ($checkoutLocationSetting == 0) {
			//Use the active location, no change needed
			$doCheckout = true;
		}elseif ($checkoutLocationSetting == 1) {
			//Use home location for the user
			$currentLocation = $patron->getHomeLocation();
			$doCheckout = true;
		}else {
			//Use the current location for the item
			//To get the current location, we need to determine if the item is already on hold.
			//If it is, make sure it is on hold for the active user and use the pickup_location
			//If it is not on hold, use the current location for the item
			$sierraDnaConnection = $this->connectToSierraDNA();

			$getItemIdByBarcodeStmt = "SELECT * from sierra_view.item_view where barcode = $1";

			//Lookup the item by barcode
			$getItemRS = pg_query_params($sierraDnaConnection, $getItemIdByBarcodeStmt, [$barcode]);
			if ($getItemRS === false || pg_num_rows($getItemRS) === 0) {
				$result['message'] = translate([
					'text' => 'Unable to checkout this item. Cannot find item for barcode %1%.',
					1 => $barcode,
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Unable to checkout this item. Cannot find item for barcode %1%.',
					1 => $barcode,
					'isPublicFacing' => true,
				]);
			}else{
				if (pg_num_rows($getItemRS) > 1) {
					$result['message'] = translate([
						'text' => 'Unable to complete checkout because more than one item was found for barcode %1%.',
						1 => $barcode,
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Unable to complete checkout because more than one item was found for barcode %1%.',
						1 => $barcode,
						'isPublicFacing' => true,
					]);
				}else{
					$itemInfo = pg_fetch_array($getItemRS, 0);
					$itemRecordIdentifier = $itemInfo['id'];
					$itemHomeLocation = $itemInfo['location_code'];

					//Check to see if the record has a hold
					$getItemHoldStmt = "SELECT * from sierra_view.hold where record_id = $1 and status IN ('b', 'j', 'i')";
					$getHoldRS = pg_query_params($sierraDnaConnection, $getItemHoldStmt, [$itemRecordIdentifier]);
					if ($getHoldRS !== false && pg_num_rows($getHoldRS) == 1) {
						$holdInfo = pg_fetch_array($getHoldRS, 0);

						//Get the patron_record_id
						$getPatronIdStmt = "SELECT id from sierra_view.record_metadata where record_type_code = 'p' and record_num = $1";
						$getPatronIdRS = pg_query_params($sierraDnaConnection, $getPatronIdStmt, [$patron->unique_ils_id]);
						if ($getPatronIdRS !== false && pg_num_rows($getPatronIdRS) == 1) {
							$patronInfo = pg_fetch_array($getPatronIdRS, 0);
							$patronRecordId = $patronInfo['id'];
							if ($holdInfo['patron_record_id'] == $patronRecordId) {
								$checkoutLocationCode = $holdInfo['pickup_location_code'];
								$doCheckout = true;
							}else{
								$result['message'] = translate([
									'text' => 'Unable to complete checkout, this title is on hold for another patron.',
									'isPublicFacing' => true,
								]);
								$result['api']['message'] = translate([
									'text' => 'Unable to complete checkout, this title is on hold for another patron.',
									'isPublicFacing' => true,
								]);
							}
						}else{
							$result['message'] = translate([
								'text' => 'Unable to complete checkout, could not find the patron id.',
								'isPublicFacing' => true,
							]);
							$result['api']['message'] = translate([
								'text' => 'Unable to complete checkout, could not find the patron id.',
								'isPublicFacing' => true,
							]);
						}
					}else{
						//Use the home location for the item
						$checkoutLocationCode = $itemHomeLocation;
						$doCheckout = true;
					}

					if ($doCheckout && !empty($checkoutLocationCode)) {
						//Find the appropriate branch in the branches table. This requires a bit of finesse since the
						//Sierra code in Aspen may not match the code in the home location exactly
						$tmpCurrentLocation = $this->getAspenLocationForSierraLocationCode($checkoutLocationCode);
						if ($tmpCurrentLocation != null) {
							$currentLocation = $tmpCurrentLocation;
						}
					}
				}
			}

			$this->closeSierraDNAConnection();
		}

		if ($doCheckout) {
			$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/checkout";

			//On some systems, PHP is not properly encoding the integer statgroup, so we will manually encode it.
			//This is important because Sierra can't handle the statgroup if it is encoded as string
			$jsonEncodedParams = '{';
			$jsonEncodedParams .= '"patronBarcode": "' . $patron->ils_barcode . '",';
			$jsonEncodedParams .= '"itemBarcode": "' . $barcode . '",';
			if (!empty($patron->ils_password)) {
				$jsonEncodedParams .= '"patronPin": "' . $patron->ils_password . '",';
			}
			if (!empty($currentLocation->circulationUsername)) {
				$jsonEncodedParams .= '"username": "' . $currentLocation->circulationUsername . '",';
			}
			if (!empty($currentLocation->statGroup) && $currentLocation->statGroup != -1) {
				$jsonEncodedParams .= '"statgroup": ' . $currentLocation->statGroup . ',';
			}
			if (str_ends_with($jsonEncodedParams, ',')) {
				$jsonEncodedParams = substr($jsonEncodedParams, 0, strlen($jsonEncodedParams) - 1);
			}
			$jsonEncodedParams .= '}';

			$checkoutResult = $this->_postPage('sierra.checkout', $sierraUrl, $jsonEncodedParams);
			if ($this->lastResponseCode == 200) {
				$checkoutLink = $checkoutResult->id;

				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'You have successfully checked out this title.',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Checkout successful',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'You have successfully checked out this title.',
					'isPublicFacing' => true,
				]);

				//Get information about the checkout
				$checkout = $this->getCheckoutDataFromLink($checkoutLink);
				if ($checkout != null) {
					$itemUrl = $checkout->item;
					$title = $this->getTitleFromItemLink($itemUrl);

					$result['itemData'] = [
						'title' => $title,
						'due' => $checkout->dueDate ?? null,
						'barcode' => $barcode,
					];
				}
			} elseif ($this->lastResponseCode == 405) {
				$result['message'] = translate([
					'text' => 'Checkouts cannot be performed by the provided API user.',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Checkouts cannot be performed by the provided API user.',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Checkouts cannot be performed by the provided API user.',
					'isPublicFacing' => true,
				]);
			} elseif ($this->lastResponseCode == 500) {
				$result['message'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
			} elseif (!empty($checkoutResult)) {
				$result['title'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
				$result['message'] = translate([
					'text' => $checkoutResult->description,
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => $checkoutResult->description,
					'isPublicFacing' => true,
				]);
			}
		}

		return $result;
	}

	public function hasAPICheckIn() : bool {
		return true;
	}

	public function checkInByAPI(User $patron, $barcode, Location $currentLocation): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'There was an error checking in this title.',
				'isPublicFacing' => true,
			]),
			'title' => translate([
				'text' => 'Unable to check in title',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Unable to check in title',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'There was an error checking in this title.',
					'isPublicFacing' => true,
				]),
			],
			'itemData' => []
		];

		//Find the correct stat group to use
		$doCheckIn = false;
		require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
		$scoSettings = new AspenLiDASelfCheckSetting();
		$checkInLocationSetting = $scoSettings->getCheckoutLocationSetting($currentLocation->code);
		if ($checkInLocationSetting == 0) {
			//Use the active location, no change needed
			$doCheckIn = true;
		}elseif ($checkInLocationSetting == 1) {
			//Use home location for the user
			$currentLocation = $patron->getHomeLocation();
			$doCheckIn = true;
		}else {
			//Use the current location for the item
			//To get the current location, we need to determine if the item is already on hold.
			//If it is, make sure it is on hold for the active user and use the pickup_location
			//If it is not on hold, use the current location for the item
			$sierraDnaConnection = $this->connectToSierraDNA();

			$getItemIdByBarcodeStmt = "SELECT * from sierra_view.item_view where barcode = $1";

			//Lookup the item by barcode
			$getItemRS = pg_query_params($sierraDnaConnection, $getItemIdByBarcodeStmt, [$barcode]);
			if ($getItemRS === false || pg_num_rows($getItemRS) === 0) {
				$result['message'] = translate([
					'text' => 'Unable to check in this item. Cannot find item for barcode %1%.',
					1 => $barcode,
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Unable to check in this item. Cannot find item for barcode %1%.',
					1 => $barcode,
					'isPublicFacing' => true,
				]);
			}else{
				if (pg_num_rows($getItemRS) > 1) {
					$result['message'] = translate([
						'text' => 'Unable to complete check in because more than one item was found for barcode %1%.',
						1 => $barcode,
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Unable to complete check in because more than one item was found for barcode %1%.',
						1 => $barcode,
						'isPublicFacing' => true,
					]);
				}else{
					//For check in, we don't need to check holds since it is already in possession of the patron
					$itemInfo = pg_fetch_array($getItemRS, 0);
					$itemHomeLocation = $itemInfo['location_code'];

					$tmpCurrentLocation = $this->getAspenLocationForSierraLocationCode($itemHomeLocation);
					if ($tmpCurrentLocation != null) {
						$currentLocation = $tmpCurrentLocation;
					}
					$doCheckIn = true;
				}
			}

			$this->closeSierraDNAConnection();
		}

		if ($doCheckIn) {
			$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/items/checkouts/$barcode";
			if (!empty($currentLocation->statGroup) && $currentLocation->statGroup != -1) {
				$sierraUrl .= '?statgroup=' . $currentLocation->statGroup;
			}
			if (!empty($currentLocation->circulationUsername)) {
				if (!str_contains($sierraUrl, '?')) {
					$sierraUrl .= '?';
				}else{
					$sierraUrl .= '&';
				}
				$sierraUrl .= 'username=' . $currentLocation->circulationUsername;
			}

			$checkoutResult = $this->_sendPage( 'sierra.checkin', 'DELETE', $sierraUrl);
			if ($this->lastResponseCode >= 200 && $this->lastResponseCode < 300) {
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'You have successfully checked in this title.',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Check in successful',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'You have successfully checked in this title.',
					'isPublicFacing' => true,
				]);
			} elseif (!empty($checkoutResult)) {
				$result['message'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => $checkoutResult->name,
					'isPublicFacing' => true,
				]);
			}
		}

		return $result;
	}

	public function getCheckoutDataFromLink($checkoutLink) {
		$checkout = $this->_callUrl('sierra.getCheckouts', $checkoutLink);

		if ($this->lastResponseCode == 200) {
			return $checkout;
		}else{
			return null;
		}
	}

	private function getAspenLocationForSierraLocationCode(string $locationCode) : ?Location {
		$tmpLocationCode = $locationCode;
		while (!empty($tmpLocationCode)) {
			$location = new Location();
			$location->whereAdd("code LIKE " . $location->escape($tmpLocationCode . '%'));
			if ($location->find(true)) {
				return $location;
			}
			$tmpLocationCode = substr($tmpLocationCode, 0, strlen($tmpLocationCode) -1);
		}
		return null;
	}

	public function loadLibraries() : array {
		$result = [
			'success' => false,
			'message' => 'Could not load libraries from Sierra'
		];

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/branches";
		$branches = $this->_callUrl('sierra.getBranches', $sierraUrl);
		if ($branches != null){
			$numUpdated = 0;
			$numErrors = 0;
			$firstTheme = new Theme();
			$firstTheme->orderBy('id');
			$firstTheme->find(true);

			$firstGroupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$firstGroupedWorkDisplaySettings->orderBy('id');
			$firstGroupedWorkDisplaySettings->find(true);

			if ($branches->total > 0) {
				foreach ($branches->entries as $entry) {
					$library = new Library();
					$library->ilsCode = $entry->id;
					if ($library->browseCategoryGroupId == null) {
						$browseCategoryGroup = new BrowseCategoryGroup();
						if (!$browseCategoryGroup->find(true)) {
							$numErrors++;
							continue;
						}else{
							$library->browseCategoryGroupId = $browseCategoryGroup->id;
						}
					}
					if (!$library->find(true)){
						$library->subdomain = $entry->id;
						$library->displayName = $entry->name;
						$libraryTheme = new LibraryTheme();
						$libraryTheme->themeId = $firstTheme->id;
						$library->__set('themes', [$libraryTheme]);

						$recordsToInclude = [];
						$recordToInclude = new LibraryRecordToInclude();
						$recordToInclude->indexingProfileId = $this->getIndexingProfile()->id;
						$recordToInclude->markRecordsAsOwned = 1;
						$recordToInclude->location = $entry->id . ".*";
						$recordsToInclude[] = $recordToInclude;

						$recordToInclude = new LibraryRecordToInclude();
						$recordToInclude->indexingProfileId = $this->getIndexingProfile()->id;
						$recordToInclude->markRecordsAsOwned = 0;
						$recordToInclude->location = '.*';
						$recordsToInclude[] = $recordToInclude;

						$library->groupedWorkDisplaySettingId = $firstGroupedWorkDisplaySettings->id;

						$library->__set('recordsToInclude', $recordsToInclude);

						if ($library->insert()){
							$numUpdated++;
						}else{
							$numErrors++;
						}
					}
				}
			}
			if ($numErrors > 0){
				$result = [
					'success' => true,
					'message' => "Loaded $numUpdated libraries from Sierra, $numErrors had errors loading."
				];
			}else{
				$result = [
					'success' => true,
					'message' => "Loaded $numUpdated libraries from Sierra"
				];
			}
		}

		return $result;
	}

	public function loadLocations() : array {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading locations from Sierra'
		];
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/branches";
		$branches = $this->_callUrl('sierra.getBranches', $sierraUrl);
		if ($branches != null){
			$numUpdated = 0;
			$numErrors = 0;
			if ($branches->total > 0) {
				foreach ($branches->entries as $entry) {
					$library = new Library();
					$library->ilsCode = $entry->id;
					if (!$library->find(true)){
						//Could not get the library
						$numErrors++;
					}else{
						//Got the library
						foreach ($entry->locations as $sierraLocation) {
							$locationCode = $sierraLocation->code;
							$location = new Location();
							$location->code = $locationCode;
							if (!$location->find(true)) {
								//This location has not been added to the database yet.
								$location = new Location();
								$location->code = $locationCode;
								$location->displayName = $sierraLocation->name;
								$location->createSearchInterface = 1;
								$location->showInSelectInterface = 1;
								$location->enableAppAccess = 1;
								$location->theme = -1;
								$location->useLibraryThemes = 1;
								$location->languageAndDisplayInHeader = 1;
								$location->displayExploreMoreBarInSummon = 1;
								$location->displayExploreMoreBarInGale = 1;
								$location->displayExploreMoreBarInEbscoEds = 1;
								$location->displayExploreMoreBarInEbscoHost = 1;
								$location->displayExploreMoreBarInCatalogSearch = 1;
								$location->showInLocationsAndHoursList = 1;
								$location->validHoldPickupBranch = 1;
								$location->validSelfRegistrationBranch = 1;
								$location->showHoldButton = 1;
								$location->publicListsToInclude = 4;
								$location->automaticTimeoutLength = 90;
								$location->automaticTimeoutLengthLoggedOut = 450;
								$location->showEmailThis = 1;
								$location->showShareOnX = 1;
								$location->showShareOnFacebook = 1;
								$location->showShareOnPinterest = 1;
								$location->showShareOnLink = 1;
								$location->showFavorites = 1;
								$location->includeLibraryRecordsToInclude = 1;

								$recordToInclude = new LocationRecordToInclude();
								$recordToInclude->indexingProfileId = $this->getIndexingProfile()->id;
								$recordToInclude->markRecordsAsOwned = 1;
								$recordToInclude->location = $locationCode . ".*";
								$recordsToInclude = [$recordToInclude];

								//Figure out the library for the location
								$location->libraryId = $library->libraryId;

								$location->__set('recordsToInclude', $recordsToInclude);

								if ($location->insert()){
									$numUpdated++;
								}else{
									$numErrors++;
								}
							}
						}

					}

				}
			}
			if ($numErrors > 0){
				$result = [
					'success' => true,
					'message' => "Loaded $numUpdated libraries from Sierra, $numErrors had errors loading."
				];
			}else{
				$result = [
					'success' => true,
					'message' => "Loaded $numUpdated libraries from Sierra"
				];
			}
		}

		return $result;
	}

	public function supportAccountNotifications() : bool {
		return true;
	}

	/**
	 * Update account notifications for the user. At this point, the system has verified that the user can receive push notifications
	 * and that they are opted in to getting account notifications.
	 *
	 * @param User $user - the user to update notifications for
	 * @param ILSNotificationSetting $ilsNotificationSetting - the settings to base notifications on
	 * @param ?CronLogEntry $cronLogEntry - an optional log entry to record information to
	 * @return array
	 */
	public function updateAccountNotifications(User $user, ILSNotificationSetting $ilsNotificationSetting, ?CronLogEntry $cronLogEntry): array {
		require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';

		$sierraDnaConnection = $this->connectToSierraDNA();
		if (!$sierraDnaConnection) {
			if (!is_null($cronLogEntry)) {$cronLogEntry->notes .= 'Could not connect to the Sierra Database<br/>';}
			return [
				'success' => false,
				'message' => 'Could not connect to the Sierra database'
			];
		}
		$datetimeNow = new DateTime();
		$formattedTimeNow = $datetimeNow->format('Y-m-d H:i:s P');
		$datetime24HoursAgo = new DateTime();
		date_sub($datetime24HoursAgo, new DateInterval('PT24H'));
		$formattedTime24HoursAgo = $datetime24HoursAgo->format('Y-m-d H:i:s P');
		$datetime48HoursAgo = new DateTime();
		date_sub($datetime48HoursAgo, new DateInterval('PT48H'));
		$formattedTime48HoursAgo = $datetime48HoursAgo->format('Y-m-d H:i:s P');
		$dateTime24HoursFromNow = new DateTime();
		$dateTime24HoursFromNow->add(new DateInterval('P1D'));
		$formattedTime24HoursFromNow = $dateTime24HoursFromNow->format('Y-m-d H:i:s P');
		$dateTime3DaysFromNow = new DateTime();
		$dateTime3DaysFromNow->add(new DateInterval('P3D'));
		$datetime7DaysAgo = new DateTime();
		date_sub($datetime7DaysAgo, new DateInterval('P7D'));
		$datetime14DaysAgo = new DateTime();
		date_sub($datetime14DaysAgo, new DateInterval('P14D'));
		$datetime21DaysAgo = new DateTime();
		date_sub($datetime21DaysAgo, new DateInterval('P21D'));
		$loadHoldReadyForPickup = $user->canReceiveILSNotification('hold_ready');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Holds Ready For Pickup? $loadHoldReadyForPickup<br/>";
		$loadHoldExpiresSoon = $user->canReceiveILSNotification('hold_expire');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Holds Expire Soon? $loadHoldExpiresSoon<br/>";
		$numMessagesAdded = 0;
		if ($loadHoldReadyForPickup || $loadHoldExpiresSoon) {
			//Look for holds for the patron that have been put on the hold shelf in the last 48 hours
			// or that will expire in the next 24 hours (but are not currently expired)
			$getHoldsNeedingNoticesStmt = "select sierra_view.hold.*, record_num as patron_record_num from sierra_view.hold inner join sierra_view.record_metadata on patron_record_id = sierra_view.record_metadata.id where (hold.on_holdshelf_gmt >= $1 OR (expire_holdshelf_gmt >= $2 AND expire_holdshelf_gmt <= $3)) and record_num = $4";
			$getHoldsNeedingNoticesRS = pg_query_params($sierraDnaConnection, $getHoldsNeedingNoticesStmt, [$formattedTime48HoursAgo, $formattedTimeNow, $formattedTime24HoursFromNow, $user->unique_ils_id]);
			if ($getHoldsNeedingNoticesRS === false) {
				return [
					'success' => false,
					'message' => 'Error querying Sierra DNA for holds needing notices'
				];
			}else{
				while ($curRow = pg_fetch_array($getHoldsNeedingNoticesRS, NULL, PGSQL_ASSOC)) {
					$existingMessage = new UserILSMessage();
					$existingMessage->userId = $user->id;
					//For Sierra, we will use the message id as the hold or checkout
					$existingMessage->messageId = $curRow['id'];
					$onHoldshelfTime = strtotime($curRow['on_holdshelf_gmt']);
					$expireHoldshelfTime = strtotime($curRow['expire_holdshelf_gmt']);
					$cronLogEntry->notes .= "&nbsp;&nbsp;&nbsp;&nbsp;- Processing hold with onHoldshelfTime of $onHoldshelfTime and expireHoldshelfTime of $expireHoldshelfTime.<br/>";
					if ($onHoldshelfTime > $datetime48HoursAgo->getTimestamp()) {
						//We will show that a hold is on the holdshelf if it was moved to the hold shelf in the last 24 hours.
						if ($loadHoldReadyForPickup) {
							$numMessagesAdded += $this->createIlsMessage($user, 'hold_ready', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}
					if ($expireHoldshelfTime >= $datetimeNow->getTimestamp() && $expireHoldshelfTime <= $dateTime24HoursFromNow->getTimestamp()) {
						//We will show that a hold expires soon if it will expire in the next 24 hours.
						if ($loadHoldExpiresSoon) {
							$numMessagesAdded += $this->createIlsMessage($user, 'hold_expire', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}
				}
			}
		}
		$loadCheckoutDueSoon = $user->canReceiveILSNotification('checkout_due_soon');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Checkouts Due Soon? $loadHoldReadyForPickup<br/>";
		$loadOverdue1 = $user->canReceiveILSNotification('overdue_1');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Overdue 1? $loadOverdue1<br/>";
		$loadOverdue7 = $user->canReceiveILSNotification('overdue_7');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Overdue 7? $loadOverdue7<br/>";
		$loadOverdue14 = $user->canReceiveILSNotification('overdue_14');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Overdue 14? $loadOverdue14<br/>";
		$loadBilled = $user->canReceiveILSNotification('billed');
		$cronLogEntry->notes .= "&nbsp;&nbsp;- Checking Billed? $loadBilled<br/>";
		if ($loadCheckoutDueSoon || $loadOverdue1 || $loadOverdue7 || $loadOverdue14 || $loadBilled) {
			//Load checkouts for the patron
			$getCheckoutsNeedingNoticesStmt = "select sierra_view.checkout.*, record_num as patron_record_num from sierra_view.checkout inner join sierra_view.record_metadata on patron_record_id = sierra_view.record_metadata.id where due_gmt < $1 AND record_num = $2";
			$getCheckoutsNeedingNoticesRS = pg_query_params($sierraDnaConnection, $getCheckoutsNeedingNoticesStmt, [$formattedTimeNow, $user->unique_ils_id]);
			if ($getCheckoutsNeedingNoticesRS === false) {
				return [
					'success' => false,
					'message' => 'Error querying Sierra DNA for checkouts needing notices'
				];
			}else{
				while ($curRow = pg_fetch_array($getCheckoutsNeedingNoticesRS, NULL, PGSQL_ASSOC)) {
					$existingMessage = new UserILSMessage();
					$existingMessage->userId = $user->id;
					//For Sierra, we will use the message id as the hold or checkout
					$existingMessage->messageId = $curRow['id'];
					$dueDateTime = strtotime($curRow['due_gmt']);
					$cronLogEntry->notes .= "&nbsp;&nbsp;&nbsp;&nbsp;- Processing checkout with dueDateTime of $dueDateTime.<br/>";
					if ($dueDateTime <= $datetime21DaysAgo->getTimestamp()) {
						if ($loadBilled) {
							$numMessagesAdded += $this->createIlsMessage($user, 'billed', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}elseif ($dueDateTime <= $datetime14DaysAgo->getTimestamp()) {
						if ($loadOverdue14) {
							$numMessagesAdded += $this->createIlsMessage($user, 'overdue_14', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}elseif ($dueDateTime <= $datetime7DaysAgo->getTimestamp()) {
						if ($loadOverdue7) {
							$numMessagesAdded += $this->createIlsMessage($user, 'overdue_7', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}elseif ($dueDateTime <= $datetime24HoursAgo->getTimestamp()) {
						if ($loadOverdue1) {
							$numMessagesAdded += $this->createIlsMessage($user, 'overdue_1', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}elseif ($dueDateTime <= $dateTime3DaysFromNow->getTimestamp()) {
						if ($loadCheckoutDueSoon) {
							$numMessagesAdded += $this->createIlsMessage($user, 'checkout_due_soon', $ilsNotificationSetting, $existingMessage, $cronLogEntry);
						}
					}
				}
			}
		}

		return [
			'success' => true,
			'message' => 'Added ' . $numMessagesAdded . ' to message queue',
			'numMessagesAdded' => $numMessagesAdded
		];
	}

	public function getMessageTypes(): array {
		$messageTypes = [];
		$messageTypes[] = [
			'name' => 'Hold Ready for Pickup',
			'module' => '',
			'code' => 'hold_ready',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Hold Ready to Expire',
			'module' => '',
			'code' => 'hold_expire',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Checkout Due Soon',
			'module' => '',
			'code' => 'checkout_due_soon',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Checkout 1 day overdue',
			'module' => '',
			'code' => 'overdue_1',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Checkout 7 days overdue',
			'module' => '',
			'code' => 'overdue_7',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Checkout 14 days overdue',
			'module' => '',
			'code' => 'overdue_14',
			'branch' => ''
		];
		$messageTypes[] = [
			'name' => 'Bill for unreturned item',
			'module' => '',
			'code' => 'billed',
			'branch' => ''
		];

		return $messageTypes;
	}

	/**
	 * Creates the ILS message within Aspen unless one already exists.
	 */
	private function createIlsMessage(User $user, string $messageCode, ILSNotificationSetting $ilsNotificationSetting, UserILSMessage $existingMessage, ?CronLogEntry $cronLogEntry) : int {
		$existingMessage->type = $messageCode;
		if (!$existingMessage->find(true)) {
			$ilsMessageType = $ilsNotificationSetting->getMessageTypeByCode($messageCode);
			if ($ilsMessageType != null) {
				$existingMessage->status = 'pending';
				$existingMessage->title = $ilsMessageType->getTextBlockTranslation('messageTitle', $user->interfaceLanguage);
				$existingMessage->content = $ilsMessageType->getTextBlockTranslation('messageBody', $user->interfaceLanguage);
				$existingMessage->dateQueued = time();
				if ($existingMessage->insert()) {
					$cronLogEntry->notes .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- ILS Message was created.<br/>";
					return 1;
				}else{
					$cronLogEntry->notes .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Inserting the message failed.<br/>";
				}
			}
		}else{
			$cronLogEntry->notes .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- ILS Message has already been created.<br/>";
		}
		return 0;
	}

	public function getEmailResetPinTemplate() : string {
		return 'requestPinReset.tpl';
	}

	public function getEmailResetPinResultsTemplate() : ?string {
		return 'requestPinResetResults.tpl';
	}

	public function processEmailResetPinForm() : array {
		$barcode = strip_tags($_REQUEST['barcode']);

		//Go to the pinreset page
		$pinResetUrl = $this->getVendorOpacUrl() . '/pinreset';
		$cookieJar = tempnam(sys_get_temp_dir(), "CURLCOOKIE");
		$curl_connection = curl_init();
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);

		curl_setopt($curl_connection, CURLOPT_URL, $pinResetUrl);
		/*$pinResetPageHtml = */
		curl_exec($curl_connection);

		//Now submit the request
		$post_data['code'] = $barcode;
		$post_data['pat_submit'] = 'xxx';
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$pinResetResultPageHtml = curl_exec($curl_connection);

		//Parse the response
		$result = [
			'success' => false,
			'error' => true,
			'message' => 'Unknown error resetting pin',
		];

		if (preg_match('/<div class="errormessage">(.*?)<\/div>/is', $pinResetResultPageHtml, $matches)) {
			$result['error'] = false;
			$result['message'] = trim($matches[1]);
		} elseif (preg_match('/<div class="pageContent">.*?<strong>(.*?)<\/strong>/si', $pinResetResultPageHtml, $matches)) {
			$result['error'] = false;
			$result['success'] = true;
			$result['message'] = trim($matches[1]);
		}
		return $result;
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param string $baseId the base id without checksum
	 * @return string the check digit
	 */
	function getCheckDigit(string $baseId) : string {
		return Sierra::getCheckDigitStatic($baseId);
	}

	static function getCheckDigitStatic($baseId) : string {
		$baseId = preg_replace('/\.?[bij]/', '', $baseId);
		$sumOfDigits = 0;
		for ($i = 0; $i < strlen($baseId); $i++) {
			$curDigit = substr($baseId, $i, 1);
			$sumOfDigits += ((strlen($baseId) + 1) - $i) * $curDigit;
		}
		$modValue = $sumOfDigits % 11;
		if ($modValue == 10) {
			return "x";
		} else {
			return $modValue;
		}
	}

	public function _curl_login(User $patron) : bool {
		global $logger;
		$loginResult = false;

		$headers = [
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Accept-Language: en-US,en;q=0.5",
			"Accept-Encoding: gzip, deflate, br, zstd",
			"Content-Type: application/x-www-form-urlencoded",
			"Origin:{$this->getVendorOpacUrl()}"
		];
		$this->curlWrapper->addCustomHeaders($headers, true);
		$curlUrl = $this->getVendorOpacUrl() . "/patroninfo/%2Fpatroninfo%2F";
		$post_data = $this->_getLoginFormValues($patron);

		$logger->log('Posting Login Credentials to ' . $curlUrl, Logger::LOG_NOTICE);

		$loginResponse = $this->curlWrapper->curlPostPage($curlUrl, $post_data);
		if (str_contains($loginResponse, 'Access Denied')) {
			return false;
		}
		$curlInfo = curl_getinfo($this->curlWrapper->curl_connection);
		$redirectUrl = $curlInfo['url'];

		//When a library uses IPSSO, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResponse, $loginMatches)) {
			$lt = $loginMatches[1]; //Get the lt value
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';

			//Don't issue a post, just call the same page (with redirects as needed)
			$loginResponse = $this->curlWrapper->curlPostPage($redirectUrl, $post_data);
		}

		if ($loginResponse) {
			$loginResult = true;

			// Check for Login Error Responses
			$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/is', $loginResponse, $matches);
			if ($numMatches > 0) {
				$logger->log('Millennium Curl Login Attempt received an Error response : ' . $matches['error'], Logger::LOG_DEBUG);
				$loginResult = false;
			} else {

				// Pause briefly after logging in as some follow-up millennium operations (done via curl) will fail if done too quickly
				usleep(150000);
			}
		}

		return $loginResult;
	}

	public function _getLoginFormValues(User $patron) : array {
		$loginData = [];

		if ($this->accountProfile->iiiLoginConfiguration == 'name_barcode_pin') {
			$loginData['name'] = $patron->lastname;
			$loginData['code'] = $patron->ils_barcode;
			$loginData['pin'] = $patron->ils_password;
		} else if ($this->accountProfile->iiiLoginConfiguration == 'barcode_pin') {
			$loginData['code'] = $patron->ils_barcode;
			$loginData['pin'] = $patron->ils_password;
		} else {
			$loginData['name'] = $patron->ils_barcode;
			$loginData['code'] = $patron->ils_password;
		}

		return $loginData;
	}

	/**
	 * Return a page from classic with comments stripped
	 *
	 * @param $patron             User The unique identifier for the patron
	 * @param $page               string The page to be loaded
	 * @return string             The page from classic
	 */
	public function _fetchPatronInfoPage(USer $patron, string $page) : string {
		//First we have to log in to classic
		if ($this->_curl_login($patron)) {
			$scope = $this->getDefaultScope();

			//Now we can get the page
			$curlUrl = $this->getVendorOpacUrl() . "/patroninfo~S$scope/" . $patron->unique_ils_id . "/$page";
			$curlResponse = $this->curlWrapper->curlGetPage($curlUrl);

			//Strip HTML comments
			return preg_replace("/<!--([^(-->)]*)-->/", " ", $curlResponse);
		}
		return false;
	}

	public function getMillenniumScope() {
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)) {
			if ($searchLocation->useScope && $searchLocation->restrictSearchByLocation) {
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)) {
			return $branchScope;
		} elseif (isset($searchLibrary) && $searchLibrary->useScope && $searchLibrary->restrictSearchByLibrary) {
			return $searchLibrary->scope;
		} else {
			return $this->getDefaultScope();
		}
	}

	public function getDefaultScope() {
		global $configArray;
		return $configArray['OPAC']['defaultScope'] ?? '93';
	}

	public function hasIssueSummaries() : bool {
		return true;
	}

	public function getMillenniumRecordInfo($id) : MillenniumCache {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCache.php';
		$scope = $this->getMillenniumScope();
		//Load the pages for holdings, order information, and items
		$millenniumCache = new MillenniumCache();
		$millenniumCache->recordId = $id;
		$millenniumCache->scope = $scope;
		global $timer;
		$host = $this->getVendorOpacUrl();

		//If we get an identifier type, strip that
		if (strpos($id, ':') > 0) {
			$id = substr($id, strpos($id, ':') + 1);
		}
		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		$req = $host . "/search~S$scope/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/holdings~" . $id_;
		$millenniumCache->holdingsInfo = file_get_contents($req);
		//$logger->log("Loaded holdings from url $req", Logger::LOG_DEBUG);
		$timer->logTime('got holdings from millennium');

		$req = $host . "/search~S$scope/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/frameset~" . $id_;
		$millenniumCache->framesetInfo = file_get_contents($req);
		$timer->logTime('got frameset info from millennium');

		$millenniumCache->cacheDate = time();

		return $millenniumCache;

	}

	/**
	 * Checks millennium to determine if there are issue summaries available.
	 * If there are issue summaries available, it will return them in an array.
	 * With holdings below them.
	 *
	 * If there are no issue summaries, null will be returned from the summary.
	 */
	public function getIssueSummaries(string $id) : ?array {
		$millenniumInfo = $this->getMillenniumRecordInfo($id);
		//Issue summaries are loaded from the main record page.

		if (preg_match('/class\s*=\s*"bibHoldings"/', $millenniumInfo->framesetInfo)) {
			//There are issue summaries available
			//Extract the table with the holdings
			$issueSummaries = [];
			$matches = [];
			if (preg_match('/<table\s.*?class="bibHoldings">(.*?)<\/table>/s', $millenniumInfo->framesetInfo, $matches)) {
				$issueSummaryTable = trim($matches[1]);
				//Each holdingSummary begins with a holdingsDivider statement
				$summaryMatches = explode('<tr><td colspan="2"><hr  class="holdingsDivider" /></td></tr>', $issueSummaryTable);
				if (count($summaryMatches) > 1) {
					//Process each match independently
					foreach ($summaryMatches as $summaryData) {
						$summaryData = trim($summaryData);
						if (strlen($summaryData) > 0) {
							//Get each line within the summary
							$issueSummary = [];
							$issueSummary['type'] = 'issueSummary';
							$issueSummary['location'] = '';
							$summaryLines = [];
							preg_match_all('/<tr\\s*>(.*?)<\/tr>/s', $summaryData, $summaryLines, PREG_SET_ORDER);
							for ($matchi = 0; $matchi < count($summaryLines); $matchi++) {
								$summaryLine = trim(str_replace('&nbsp;', ' ', $summaryLines[$matchi][1]));
								$summaryCols = [];
								if (preg_match('/<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>/s', $summaryLine, $summaryCols)) {
									$label = trim($summaryCols[1]);
									$value = trim(strip_tags($summaryCols[2]));
									//Check to see if this has a link to a check-in grid.
									if (preg_match('/.*?<a href="(.*?)">.*/s', $label, $linkData)) {
										//Parse the check-in id
										$checkInLink = $linkData[1];
										if (preg_match('/\/search~S\\d+\\?\/.*?\/.*?\/.*?\/(.*?)&.*/', $checkInLink, $checkInGridInfo)) {
											$issueSummary['checkInGridId'] = $checkInGridInfo[1];
										}
									}
									//Convert to camel case
									$label = (preg_replace('/\W/', '', strip_tags($label)));
									$label = strtolower(substr($label, 0, 1)) . substr($label, 1);
									if ($label == 'location') {
										//Try to trim the courier code if any
										if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $value, $locationParts)) {
											$value = $locationParts[1];
										}
									} elseif ($label == 'holdings') {
										//Change the label to avoid conflicts with actual holdings
										$label = 'holdingStatement';
									} elseif ($label == 'itemLoc') {
										//Change the label for consistency
										$label = 'location';
									}
									$issueSummary[$label] = $value;
								}
							}
							$issueSummaries[$issueSummary['location'] . count($issueSummaries)] = $issueSummary;
						}
					}
				}
			}

			return $issueSummaries;
		} else {
			return null;
		}
	}

	function getCheckInGrid($id, $checkInGridId) : array {
		//Issue summaries are loaded from the main record page.
		global $configArray;

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		// Load Record Page
		if (str_ends_with($configArray['Catalog']['url'], '/')) {
			$host = substr($configArray['Catalog']['url'], 0, -1);
		} else {
			$host = $configArray['Catalog']['url'];
		}

		$branchScope = $this->getMillenniumScope();
		$req = $host . "/search~S$branchScope/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/$checkInGridId&FF=1,0,";
		$result = file_get_contents($req);

		//Extract the actual table
		$checkInData = [];
		if (preg_match('/<table\s+class="checkinCardTable">(.*?)<\/table>/s', $result, $matches)) {
			$checkInTable = trim($matches[1]);

			//Extract each item from the grid.
			preg_match_all('/.*?<td valign="top" class="(.*?)">(.*?)<\/td>/s', $checkInTable, $checkInCellMatch, PREG_SET_ORDER);
			for ($matchi = 0; $matchi < count($checkInCellMatch); $matchi++) {
				$checkInCell = [];
				$checkInCell['class'] = $checkInCellMatch[$matchi][1];
				$cellData = trim($checkInCellMatch[$matchi][2]);
				//Load issue date, status, date received, issue number, copies received
				/** @noinspection RegExpUnnecessaryNonCapturingGroup */
				if (preg_match('/(.*?)<br\\s*\/?>.*?<span class="(?:.*?)">(.*?)<\/span>.*?on (\\d{1,2}-\\d{1,2}-\\d{1,2})<br\\s*\/?>(.*?)(?:<!-- copies --> \\((\\d+) copy\\))?<br\\s*\/?>/s', $cellData, $matches)) {
					$checkInCell['issueDate'] = trim($matches[1]);
					$checkInCell['status'] = trim($matches[2]);
					$checkInCell['statusDate'] = trim($matches[3]);
					$checkInCell['issueNumber'] = trim($matches[4]);
					if (isset($matches[5])) {
						$checkInCell['copies'] = trim($matches[5]);
					}
				}
				$checkInData[] = $checkInCell;
			}
		}
		return $checkInData;
	}

	/**
	 * @param mixed $primaryAddress
	 * @param Library|null $userHomeLibrary
	 * @param User $user
	 */
	public function parseCityStateZipFromAddressLines(mixed $primaryAddress, ?Library $userHomeLibrary, User $user) : void {
		//Get the correct address line for the city/state/zip
		if (array_key_exists($userHomeLibrary->sierraAddressLineForCityState - 1, $primaryAddress->lines)) {
			$line2 = $primaryAddress->lines[$userHomeLibrary->sierraAddressLineForCityState - 1];
			if ($userHomeLibrary->sierraZipOnSameLineAsCityState) {
				$this->getCityStateZipFromLine($line2, $user);
			} else {
				$this->getCityStateFromLine($line2, $user);
				if (array_key_exists($userHomeLibrary->sierraAddressLineForCityState, $primaryAddress->lines)) {
					$user->_zip = $primaryAddress->lines[$userHomeLibrary->sierraAddressLineForCityState];
				}
			}
		}

		if (!empty($user->_zip)){
			$user->_zip = trim($user->_zip);
		}

		//Check to see if we got a good zip
		if (empty($user->_zip) || !preg_match('/^(\d{5}(-\d{4})?|[A-Z]\d[A-Z] ?\d[A-Z]\d)$/', $user->_zip)) {
			//Scan the lines from 2-5 to see if we can get a good match for the zip code
			for ($i = 1; $i < count($primaryAddress->lines); $i++) {
				$addressLine = $primaryAddress->lines[$i];
				if (preg_match('/^(\d{5}(-\d{4})?|[A-Z]\d[A-Z] ?\d[A-Z]\d)$/', $addressLine)) {
					//This looks like a zip/postal code
					$user->_zip = $addressLine;
					//The city/state is probably the previous line
					if ($i > 2) {
						$previousLine = $primaryAddress->lines[$i - 1];
						$this->getCityStateFromLine($previousLine, $user);
					}else{
						//city state is probably not included
					}
					return;
				}elseif (preg_match('/^(.*?)(\d{5}(-\d{4})?|[A-Z]\d[A-Z] ?\d[A-Z]\d)$/', $addressLine)) {
					$this->getCityStateZipFromLine($addressLine, $user);
					return;
				}
			}
		}
	}

	/**
	 * @param string $addressLine
	 * @param User $user
	 */
	public function getCityStateFromLine(string $addressLine, User $user): void {
		if (strpos($addressLine, ',')) {
			$user->_city = substr($addressLine, 0, strrpos($addressLine, ','));
			$user->_state = trim(substr($addressLine, strrpos($addressLine, ',') + 1));
		} else {
			$parts = preg_split('/\s+/', $addressLine);
			if (count($parts) >= 2) {
				$user->_state = array_pop($parts);
				$user->_city = implode(' ', $parts);
			} else {
				$user->_city = $addressLine;
			}
		}
	}

	/**
	 * @param string $line2
	 * @param User $user
	 */
	public function getCityStateZipFromLine(string $line2, User $user): void {
		if (strpos($line2, ',')) {
			$user->_city = substr($line2, 0, strrpos($line2, ','));
			$stateZip = trim(substr($line2, strrpos($line2, ',') + 1));
			if (strpos($stateZip, ' ')) {
				$user->_state = substr($stateZip, 0, strrpos($stateZip, ' '));
				$user->_zip = substr($stateZip, strrpos($stateZip, ' '));
			} else {
				$user->_state = trim($stateZip);
			}
		} else {
			$parts = preg_split('/\s+/', $line2);
			if (count($parts) >= 3) {
				$lastpart = array_pop($parts);
				if (is_numeric($lastpart)) {
					$user->_zip = $lastpart;
					$user->_state = array_pop($parts);
				} else {
					$user->_state = $lastpart;
				}
				$user->_city = implode(' ', $parts);
			} else {
				$user->_city = $line2;
			}
		}
	}
}