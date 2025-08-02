<?php

class Evergreen extends AbstractIlsDriver {
	//Caching of sessionIds by patron for performance
	private static $accessTokensForUsers = [];

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Evergreen Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct() {
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
	public function getCheckouts(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			//Get a list of circulations
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.circ&method=open-ils.circ.actor.user.checked_out';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . $patron->unique_ils_id;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			$index = 0;
			ExternalRequestLogEntry::logRequest('evergreen.getCheckouts', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0])) {
					//Process circulations
					foreach ($apiResponse->payload as $payload) {
						$mappedCheckout = $this->mapEvergreenFields($payload->circ->__p, $this->fetchIdl('circ'));
						$mappedRecord = $this->mapEvergreenFields($payload->record->__p, $this->fetchIdl('mvr'));
						$mappedCopy = $this->mapEvergreenFields($payload->copy->__p, $this->fetchIdl('acp'));
						$checkout = new Checkout();
						$checkout->type = 'ils';
						$checkout->source = $this->getIndexingProfile()->name;
						$checkout->sourceId = $mappedCheckout['target_copy'];
						$checkout->userId = $patron->id;
						$checkout->itemId = $mappedCopy['id'];
						$checkout->barcode = $mappedCopy['barcode'];
						$checkout->dueDate = strtotime($mappedCheckout['due_date']);
						$checkout->checkoutDate = strtotime($mappedCheckout['create_time']);
						if ($mappedCheckout['auto_renewal'] == 't') {
							$checkout->autoRenew = true;
						}
						$checkout->canRenew = $mappedCheckout['renewal_remaining'] > 0;
						$checkout->maxRenewals = $mappedCheckout['renewal_remaining'];
						$checkout->renewalId = $mappedCheckout['target_copy'];
						$checkout->renewIndicator = $mappedCheckout['target_copy'];
						$checkout->recordId = $mappedRecord['doc_id'];
						$checkout->title = $mappedRecord['title'];
						$checkout->author = $mappedRecord['author'];
						$checkout->callNumber = $this->getCallNumberForCopy($mappedCopy, $authToken);
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$recordDriver = new MarcRecordDriver((string)$checkout->recordId);
						if ($recordDriver->isValid()) {
							$checkout->updateFromRecordDriver($recordDriver);
						}
						$index++;
						$sortKey = "{$checkout->source}_{$checkout->sourceId}_$index";
						$checkedOutTitles[$sortKey] = $checkout;
					}
				}
			}
		}

		return $checkedOutTitles;
	}

	private function loadCheckoutData(User $patron, $checkoutId, $authToken): ?Checkout {
		$curCheckout = null;
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$request = 'service=open-ils.circ&method=open-ils.circ.retrieve';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . $checkoutId;
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

		ExternalRequestLogEntry::logRequest('evergreen.loadCheckoutData', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0])) {
				$mappedCheckout = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('circ'));
				$curCheckout = new Checkout();
				$curCheckout->type = 'ils';
				$curCheckout->source = $this->getIndexingProfile()->name;

				$curCheckout->sourceId = $mappedCheckout['target_copy'];
				$curCheckout->userId = $patron->id;

				$modsForCopy = null;
				if (!empty($mappedCheckout['target_copy'])) {
					$modsForCopy = $this->getModsForCopy($mappedCheckout['target_copy']);

					$curCheckout->recordId = $modsForCopy['doc_id'];
				}
				$curCheckout->itemId = $mappedCheckout['target_copy'];

				$curCheckout->dueDate = strtotime($mappedCheckout['due_date']);
				$curCheckout->checkoutDate = strtotime($mappedCheckout['create_time']);

				if ($mappedCheckout['auto_renewal'] == 't') {
					$curCheckout->autoRenew = true;
				}
				$curCheckout->canRenew = $mappedCheckout['renewal_remaining'] > 0;
				$curCheckout->maxRenewals = $mappedCheckout['renewal_remaining'];
				$curCheckout->renewalId = $mappedCheckout['target_copy'];
				$curCheckout->renewIndicator = $mappedCheckout['target_copy'];

				if (!empty($modsForCopy)) {
					$curCheckout->title = $modsForCopy['title'];
					$curCheckout->author = $modsForCopy['author'];
					$curCheckout->callNumber = reset($modsForCopy['call_numbers']);

					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver((string)$curCheckout->recordId);
					if ($recordDriver->isValid()) {
						$curCheckout->updateFromRecordDriver($recordDriver);
					}
				}
			}
		}
		return $curCheckout;
	}

	/**
	 * Load mods data based on an item id
	 *
	 * @param int $copyId
	 * @return []|null
	 */
	private function getModsForCopy(int $copyId): ?array {
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$request = 'service=open-ils.search&method=open-ils.search.biblio.mods_from_copy';
		$request .= '&param=' . $copyId;
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		ExternalRequestLogEntry::logRequest('evergreen.getModsForCopy', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0])) {
				$mods = $apiResponse->payload[0]->__p;
				return $this->mapEvergreenFields($mods, $this->fetchIdl('mvr'));
			}
		}
		return null;
	}

	/**
	 * Load call number label based on a mapped item object
	 *
	 * @param array $mappedCopy a mapped Evergreen copy object
	 * @param $authtoken
	 * @return string call number label associated with the copy
	 */
	private function getCallNumberForCopy(array $mappedCopy, $authtoken) {
		$label = '';
		$flesh = [
			"flesh" => 1,
			"flesh_fields" => [
				"acn" => [
					"prefix",
					"suffix"
				]
			]
		];
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$request = 'service=open-ils.pcrud&method=open-ils.pcrud.retrieve.acn';
		$request .= '&param=' . json_encode($authtoken);
		$request .= '&param=' . $mappedCopy["call_number"];
		$request .= '&param=' . json_encode($flesh);
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		ExternalRequestLogEntry::logRequest('evergreen.getCallNumberForCopy', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0])) {
				$obj = $apiResponse->payload[0];
				$callno = $this->mapEvergreenFields($obj->__p, $this->fetchIdl($obj->__c));
				$label = $callno["label"];
				$obj = $callno["prefix"];
				$prefix = $this->mapEvergreenFields($obj->__p, $this->fetchIdl($obj->__c));
				$obj = $callno["suffix"];
				$suffix = $this->mapEvergreenFields($obj->__p, $this->fetchIdl($obj->__c));
				if ($prefix["label"]) {
					$label = $prefix["label"] . " " . $label;
				}
				if ($suffix["label"]) {
					$label = $label . " " . $suffix["label"];
				}
			}
		}
		return $label;
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function renewAll(User $patron) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null) {
		$result = [
			'itemId' => $itemId,
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error renewing checkout',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Checkout could not be renewed',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unknown Error renewing checkout',
					'isPublicFacing' => true,
				]),
			],
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$request = 'service=open-ils.circ&method=open-ils.circ.renew';
			$request .= '&param=' . json_encode($authToken);
			$namedParams = [
				'patron_id' => (int)$patron->unique_ils_id,
				"copy_id" => $itemId,
				"opac_renewal" => 1,
			];
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.renewCheckout', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]->textcode) && $apiResponse->payload[0]->textcode == 'SUCCESS') {
					$result['message'] = translate([
						'text' => "Your title was renewed successfully.",
						'isPublicFacing' => true,
					]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Title renewed successfully',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your title was renewed successfully.',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfCheckouts();
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$result['message'] = $apiResponse->payload[0]->desc;
					$result['api']['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
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
	 * @param bool $isIll If the hold was from ILL
	 * @return  array
	 */
	function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => "The hold could not be cancelled.",
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Hold not cancelled',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'The hold could not be cancelled.',
					'isPublicFacing' => true,
				]),
			],
		];
		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.cancel';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode([(int)$cancelId]);
			$request .= '&param=';
			$request .= '&param=' . json_encode("Hold cancelled in Aspen Discovery");

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.cancelHold', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$result['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$result['message'] = $apiResponse->debug;
				} elseif ($apiResponse->payload[0] == 1) {
					$result['message'] = translate([
						'text' => "The hold has been cancelled.",
						'isPublicFacing' => true,
					]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold cancelled',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your hold has been cancelled,',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		} else {
			$result['message'] = translate([
				'text' => 'Could not connect to the circulation system',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation = null) {
		return $this->placeItemHold($patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation);
	}

	/**
	 * @inheritDoc
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null, $pickupSublocation = null) {
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

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			//Translate to numeric location id
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)) {
				$pickupBranch = $location->historicCode;
			}
			if ($cancelDate == null) {
				global $library;
				if ($library->defaultNotNeededAfterDays == 0) {
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$cancelDate = date(DateTimeInterface::ISO8601, $sixMonthsFromNow);
				} else {
					//Default to a date 6 months (half a year) in the future.
					if ($library->defaultNotNeededAfterDays > 0) {
						$nnaDate = time() + $library->defaultNotNeededAfterDays * 24 * 60 * 60;
						$cancelDate = date(DateTimeInterface::ISO8601, $nnaDate);
					}
				}
			}
			/** @noinspection SpellCheckingInspection */
			$namedParams = [
				'patronid' => (int)$patron->unique_ils_id,
				"pickup_lib" => (int)$pickupBranch,
				"hold_type" => 'P',
//				"request_lib" =>  (int)$pickupBranch,
//				"request_time" => date( DateTime::ISO8601),
//				"frozen" => 'f'
			];
			if (isset($_REQUEST['emailNotification']) && $_REQUEST['emailNotification'] == 'on') {
				$namedParams['email_notify'] = 't';
			}
			if (isset($_REQUEST['phoneNotification']) && $_REQUEST['phoneNotification'] == 'on') {
				if (isset($_REQUEST['phoneNumber']) && strlen($_REQUEST['phoneNumber']) > 0) {
					$namedParams['phone_notify'] = $_REQUEST['phoneNumber'];
				} elseif (isset($patron->phone) && strlen($patron->phone) > 0) {
					$namedParams['phone_notify'] = $patron->phone;
				}
			}
			if (isset($_REQUEST['smsNotification']) && $_REQUEST['smsNotification'] == 'on') {
				if (isset($_REQUEST['smsNumber']) && strlen($_REQUEST['smsNumber']) > 0) {
					if (isset($_REQUEST['smsCarrier']) && $_REQUEST['smsCarrier'] != -1) {
						$namedParams['sms_carrier'] = $_REQUEST['smsCarrier'];
						$namedParams['sms_notify'] = $_REQUEST['smsNumber'];
					}

				}
			}
			if ($cancelDate != null) {
				$namedParams['expire_time'] = $cancelDate;
			}

			$request = 'service=open-ils.circ&method=open-ils.circ.holds.test_and_create.batch.override';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode($namedParams);
			$request .= '&param=' . json_encode([(int)$itemId]);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.placeItemHold', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$hold_result['message'] = $apiResponse->payload[0]->desc;
					$hold_result['api']['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$hold_result['message'] = $apiResponse->payload[0]->result->desc;
					$hold_result['api']['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$hold_result['message'] = $apiResponse->debug;
					$hold_result['api']['message'] = $apiResponse->debug;
				} elseif (isset($apiResponse->payload[0]->result) && is_object($apiResponse->payload[0]->result)) {
					$apiHoldResult = $apiResponse->payload[0]->result;
					$hold_result['message'] = $apiHoldResult->last_event->desc;
					$hold_result['api']['message'] = $apiHoldResult->last_event->desc;
				} elseif (isset($apiResponse->payload[0]->result) && $apiResponse->payload[0]->result > 0) {
					$hold_result['message'] = translate([
						'text' => "Your hold was placed successfully.",
						'isPublicFacing' => true,
					]);
					$hold_result['success'] = true;

					// Result for API or app use
					$hold_result['api']['title'] = translate([
						'text' => 'Hold placed successfully',
						'isPublicFacing' => true,
					]);
					$hold_result['api']['message'] = translate([
						'text' => 'Your hold was placed successfully.',
						'isPublicFacing' => true,
					]);
					$hold_result['api']['action'] = translate([
						'text' => 'Go to Holds',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

		return $hold_result;
	}

	// Evergreen supports indefinite or thaw-on-date hold freezing
	public function suspendRequiresReactivationDate(): bool {
		return true;
	}

	public function showDateWhenSuspending(): bool {
		return true;
	}

	public function reactivateDateNotRequired(): bool {
		return true;
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => "The hold could not be frozen.",
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Hold not frozen',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'The hold could not be frozen.',
					'isPublicFacing' => true,
				]),
			],
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$namedParams = [
				'id' => $itemToFreezeId,
				'frozen' => 't',
			];

			if (isset($dateToReactivate) && !empty($dateToReactivate)) {
				$namedParams['thaw_date'] = $dateToReactivate;
			}

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.freezeHold', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$result['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$result['message'] = $apiResponse->debug;
				} elseif ($apiResponse->payload[0] > 0) {
					$result['message'] = translate([
						'text' => "Your hold was frozen successfully.",
						'isPublicFacing' => true,
					]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold frozen successfully',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your hold was frozen successfully.',
						'isPublicFacing' => true,
					]);

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
	function thawHold(User $patron, $recordId, $itemToThawId): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => "The hold could not be thawed.",
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Hold not thawed',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'The hold could not be thawed.',
					'isPublicFacing' => true,
				]),
			],
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$namedParams = [
				'id' => $itemToThawId,
				'frozen' => 'f',
				'thaw_date' => null,
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.thawHold', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$result['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$result['message'] = $apiResponse->debug;
				} elseif ($apiResponse->payload[0] > 0) {
					$result['message'] = translate([
						'text' => "Your hold was thawed successfully.",
						'isPublicFacing' => true,
					]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold thawed successfully',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your hold was thawed successfully.',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

		return $result;
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation, $newPickupSublocation = null): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => "The pickup location for the hold could not be changed.",
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Hold location not changed',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'The pickup location for the hold could not be changed.',
					'isPublicFacing' => true,
				]),
			],
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			//Translate to numeric location id
			$location = new Location();
			$location->code = $newPickupLocation;
			if ($location->find(true)) {
				$newPickupLocation = $location->historicCode;
			}

			$namedParams = [
				'id' => $itemToUpdateId,
				'pickup_lib' => (int)$newPickupLocation,
			];

			$request = 'service=open-ils.circ&method=open-ils.circ.hold.update';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=';
			$request .= '&param=' . json_encode($namedParams);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.changeHoldPickupLocation', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$result['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$result['message'] = $apiResponse->debug;
				} elseif ($apiResponse->payload[0] > 0) {
					$result['message'] = translate([
						'text' => "The pickup location for the hold was changed.",
						'isPublicFacing' => true,
					]);
					$result['success'] = true;

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold updated',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'The pickup location for the hold was changed.',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

		return $result;
	}

	public function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade): array {
		$authToken = $this->getAPIAuthToken($patron, true);
		$userMessages = [
			'success' => false,
			'messages' => [],
		];

		if (!$authToken || !$canUpdateContactInfo) {
			$userMessages['messages'][] = 'Your contact information cannot be updated.';
			return $userMessages;
		}

		if (!isset($_REQUEST['email'])) {
			return $userMessages;
		}

		$propertyName = 'email';
		$propertyValue = $_REQUEST['email'];
		$response = $this->updatePatronPropertyInEvergreen($propertyName, $propertyValue, $patron->ils_password, $authToken);

		if($response['success']) {
			$patron->email = $propertyValue;
			$patron->update();
		}

		$userMessages['messages'][] = $response['success'] ? 'Your email has been updated.' : 'Your email could not be updated. Please contact your library';
		$userMessages['success'] = $response['success'];

		return $userMessages;
	}

	private function updatePatronPropertyInEvergreen(string $propertyEvergreenName, string $propertyValue, string $patronIlsPassword, string $authToken): array {
		$evergreenUrl = $this->accountProfile->patronApiUrl . "/osrf-gateway-v1/$propertyEvergreenName";
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		/**The order of the request parameters is crucial
		 * This Evergreen API only handles unnamed parameters,
		 * And so it is only their position within the URL that
		 * determines which is which
		*/
		$request = 'service=open-ils.actor';
		$request .= "&method=open-ils.actor.user.$propertyEvergreenName.update";
		$requestParams = '&param=' . json_encode($authToken);
		$requestParams .= '&param=' . json_encode($propertyValue);
		$requestParams .= '&param=' . json_encode($patronIlsPassword);

		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request . $requestParams);
		ExternalRequestLogEntry::logRequest('evergreen.updatePatronPropertyInEvergreen', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);

		$responseData = json_decode($apiResponse, true);
		$response = ['success' => false, 'message' => ['code' => '', 'text' =>  'Evergreen sent an unexpected response']];
		if (!isset($responseData['payload'])) {
			return $response;
		}

		/** It seems the response sent back by the Evergreen API will have a
		 * status code of 200 even upon failure (eg. if the patron password
		 * is incorrect.) However, a successful update will always result
		 * in the "payload" property being set to 1. Check for this instead.
		*/
		$response['success'] = $responseData['payload'][0] == 1;

		if (!$response['success']) {
			global $logger;
			$logger->log('Error updating patron property ' . $propertyEvergreenName . ': ' . $responseData['payload'][0]['desc'], Logger::LOG_ERROR);
			$response['message']['code'] = $responseData['payload'][0]['textcode'];
			$response['message']['text'] = $responseData['payload'][0]['desc'];
			return $response;
		}

		$response['message']['text'] = "Patron property update successfull";
		return $response;
	}

	public function hasNativeReadingHistory(): bool {
		return true;
	}

	public function canLoadReadingHistoryInMasqueradeMode(): bool {
		$activePatron = UserAccount::getActiveUserObj();
		if (!empty($activePatron)) {
			return !empty($activePatron->ils_password);
		}
		return false;
	}


	public function performsReadingHistoryUpdatesOfILS(): bool {
		return false;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return array
	 * @throws Exception
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		$historyActive = false;
		$readingHistoryTitles = [];
		$numTitles = 0;
		$offset = 0;
		$hasMoreHistory = true;

		set_time_limit(0);
		while ($hasMoreHistory) {
			$authToken = $this->getAPIAuthToken($patron, false);
			if ($authToken != null) {
				//Get a list of checkouts

				$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
				$headers = [
					'Content-Type: application/x-www-form-urlencoded',
				];
				$this->apiCurlWrapper->addCustomHeaders($headers, false);

				$params = 'service=open-ils.actor';
				$params .= '&method=open-ils.actor.history.circ';
				$params .= '&param=' . json_encode($authToken);
				$params .= '&param={"offset":' . $offset . ',"limit":100}';
				$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

				ExternalRequestLogEntry::logRequest('evergreen.getReadingHistory', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $params, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
				if ($this->apiCurlWrapper->getResponseCode() == 200) {
					$circHistoryDecoded = json_decode($apiResponse);
					if (empty($circHistoryDecoded->payload)) {
						$hasMoreHistory = false;
					}
					foreach ($circHistoryDecoded->payload as $circEntry) {
						$circEntryMapped = $this->mapEvergreenFields($circEntry->__p, $this->fetchIdl('auch'));

						require_once ROOT_DIR . '/sys/User/Checkout.php';
						if (empty($circEntryMapped['source_circ'])) {
							$modsForCopy = $this->getModsForCopy($circEntryMapped['target_copy']);
							if ($modsForCopy != null) {
								if (is_integer($modsForCopy['doc_id'])) {
									$modsForCopy['doc_id'] = strval($modsForCopy['doc_id']);
								}
								$curTitle = [];
								$curTitle['id'] = $modsForCopy['doc_id'];
								$curTitle['shortId'] = $modsForCopy['doc_id'];
								$curTitle['recordId'] = $modsForCopy['doc_id'];
								$curTitle['title'] = $modsForCopy['title'];
								$curTitle['author'] = $modsForCopy['author'];
								require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
								$marcRecordDriver = new MarcRecordDriver($modsForCopy['doc_id']);
								if ($marcRecordDriver->isValid()) {
									$curTitle['format'] = $marcRecordDriver->getPrimaryFormat();
								} else {
									$curTitle['format'] = 'Unknown';
								}
								if (!empty($circEntryMapped['xact_start'])) {
									$curTitle['checkout'] = strtotime($circEntryMapped['xact_start']);;
								}
								if (!empty($circEntryMapped['checkin_time'])) {
									$curTitle['checkin'] = strtotime($circEntryMapped['checkin_time']);
								} else {
									$curTitle['checkin'] = null;
								}
							} else {
								continue;
							}
						} else {
							$checkout = $this->loadCheckoutData($patron, $circEntryMapped['source_circ'], $authToken);
							if ($checkout != null) {
								$curTitle = [];
								$curTitle['id'] = $checkout->recordId;
								$curTitle['shortId'] = $checkout->recordId;
								$curTitle['recordId'] = $checkout->recordId;
								$curTitle['title'] = $checkout->title;
								$curTitle['author'] = $checkout->author;
								$curTitle['format'] = $checkout->format;
								$curTitle['checkout'] = $checkout->checkoutDate;
								if (!empty($circEntryMapped['checkin_time'])) {
									$curTitle['checkin'] = strtotime($circEntryMapped['checkin_time']);
								} else {
									$curTitle['checkin'] = null;
								}
							}
						}
						$readingHistoryTitles[] = $curTitle;
						$numTitles++;
					}
				}
			}
			$offset += 100;
		}

		$systemVariables = SystemVariables::getSystemVariables();
		global $aspen_db;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		foreach ($readingHistoryTitles as $key => $historyEntry) {
			//Get additional information from resources table
			$historyEntry['ratingData'] = null;
			$historyEntry['permanentId'] = null;
			$historyEntry['linkUrl'] = null;
			$historyEntry['coverUrl'] = null;
			if (!empty($historyEntry['recordId'])) {
				if ($systemVariables->storeRecordDetailsInDatabase) {
					/** @noinspection SqlResolve */
					$getRecordDetailsQuery = 'SELECT permanent_id, indexed_format.format FROM grouped_work_records
								  LEFT JOIN grouped_work ON groupedWorkId = grouped_work.id
								  LEFT JOIN indexed_record_source ON sourceId = indexed_record_source.id
								  LEFT JOIN indexed_format on formatId = indexed_format.id
								  where source = ' . $aspen_db->quote($this->accountProfile->recordSource) . ' and recordIdentifier = ' . $aspen_db->quote($historyEntry['recordId']);
					$results = $aspen_db->query($getRecordDetailsQuery, PDO::FETCH_ASSOC);
					if ($results) {
						$result = $results->fetch();
						if ($result) {
							$groupedWorkDriver = new GroupedWorkDriver($result['permanent_id']);
							if ($groupedWorkDriver->isValid()) {
								$historyEntry['ratingData'] = $groupedWorkDriver->getRatingData();
								$historyEntry['permanentId'] = $groupedWorkDriver->getPermanentId();
								$historyEntry['linkUrl'] = $groupedWorkDriver->getLinkUrl();
								$historyEntry['coverUrl'] = $groupedWorkDriver->getBookcoverUrl('medium', true);
								$historyEntry['format'] = $result['format'];
								$historyEntry['title'] = $groupedWorkDriver->getTitle();
								$historyEntry['author'] = $groupedWorkDriver->getPrimaryAuthor();
							}
						}
					}
				} else {
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $historyEntry['recordId']);
					if ($recordDriver->isValid()) {
						$historyEntry['ratingData'] = $recordDriver->getRatingData();
						$historyEntry['permanentId'] = $recordDriver->getPermanentId();
						$historyEntry['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
						$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
						$historyEntry['format'] = $recordDriver->getFormats();
						$historyEntry['author'] = $recordDriver->getPrimaryAuthor();
					}
					$recordDriver->__destruct();
					$recordDriver = null;
				}
			}
			$readingHistoryTitles[$key] = $historyEntry;
		}

		$numTitles = count($readingHistoryTitles);

		return [
			'historyActive' => $historyActive,
			'titles' => $readingHistoryTitles,
			'numTitles' => $numTitles,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getHolds(User $patron): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = [];
		$unavailableHolds = [];
		$holds = [
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds,
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			//Get a list of holds
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$getHoldsParams = 'service=open-ils.circ';
			$getHoldsParams .= '&method=open-ils.circ.holds.retrieve';
			$getHoldsParams .= '&param=' . json_encode($authToken);
			$getHoldsParams .= '&param=' . $patron->unique_ils_id;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getHoldsParams);

			ExternalRequestLogEntry::logRequest('evergreen.getHolds', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $getHoldsParams, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				foreach ($apiResponse->payload[0] as $payload) {
					if ($payload->__c == 'ahr') { //class
						$holdInfo = $payload->__p; //ahr object

						$holdInfo = $this->mapEvergreenFields($holdInfo, $this->fetchIdl('ahr'));

						$curHold = new Hold();
						$curHold->userId = $patron->id;
						$curHold->type = 'ils';
						$curHold->source = $this->getIndexingProfile()->name;

						$curHold->sourceId = $holdInfo['id'];
						//If the hold_type is P the target will be the part, so we will need to look up the bib record based on the part
						if ($holdInfo['hold_type'] == 'P' || $holdInfo['hold_type'] == 'V') {
							require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
							$volumeInfo = new IlsVolumeInfo();
							$volumeInfo->volumeId = $holdInfo['target'];
							if ($volumeInfo->find(true)) {
								$curHold->volume = $volumeInfo->displayLabel;
								if (strpos($volumeInfo->recordId, ':') > 0) {
									[
										,
										$curHold->recordId,
									] = explode(':', $volumeInfo->recordId);
								} else {
									$curHold->recordId = $volumeInfo->recordId;
								}
							}
						} elseif ($holdInfo['hold_type'] == 'C') {
							//This is a copy level hold, need to look it up by the item number
							$modsInfo = $this->getModsForCopy($holdInfo['target']);
							$curHold->recordId = (string)$modsInfo['doc_id'];
							$curHold->title = (string)$modsInfo['title'];
							$curHold->author = (string)$modsInfo['author'];
						} elseif ($holdInfo['hold_type'] == 'M') {
							// Metarecord hold: retrieve master record to get actual bib ID.
							$mmrRequest = 'service=open-ils.pcrud&method=open-ils.pcrud.retrieve.mmr';
							$mmrRequest .= '&param=' . json_encode($authToken);
							$mmrRequest .= '&param=' . json_encode((int)$holdInfo['target']);
							$mmrResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $mmrRequest);
							ExternalRequestLogEntry::logRequest('evergreen.getMetarecord', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $mmrRequest, $this->apiCurlWrapper->getResponseCode(), $mmrResponse, []);
							if ($this->apiCurlWrapper->getResponseCode() == 200) {
								$mmrData = json_decode($mmrResponse);
								if (isset($mmrData->payload[0]->__p)) {
									$mappedMmr = $this->mapEvergreenFields($mmrData->payload[0]->__p, $this->fetchIdl('mmr'));
									$curHold->recordId = (string)$mappedMmr['master_record'];
								} else {
									$curHold->recordId = $holdInfo['target'];
								}
							} else {
								$curHold->recordId = $holdInfo['target'];
							}
						} else {
							//Hold Type is T (Title
							$curHold->recordId = $holdInfo['target'];

						}
						$curHold->cancelId = $holdInfo['id'];

						$curHold->locationUpdateable = true;
						$curHold->cancelable = true;

						//Get hold location
						$location = new Location();
						$location->historicCode = $holdInfo['pickup_lib'];
						if ($location->find(true)) {
							$curHold->pickupLocationId = $location->locationId;
							$curHold->pickupLocationName = $location->displayName;
						}

						$getHoldPosition = true;
						if ($holdInfo['frozen'] == 't') {
							$curHold->frozen = true;
							$curHold->status = "Frozen";
							$curHold->canFreeze = true;
							if ($holdInfo['thaw_date'] != null) {
								$curHold->reactivateDate = strtotime($holdInfo['thaw_date']);
							}
							$curHold->locationUpdateable = true;
						} elseif (!empty($holdInfo['shelf_time'])) {
							$curHold->expirationDate = strtotime($holdInfo['shelf_expire_time']);
							$curHold->status = "Ready to Pickup";
							$curHold->available = true;
							$getHoldPosition = false;
						} elseif (!empty($holdInfo['transit'])) {
							$curHold->status = 'In Transit';
							$getHoldPosition = false;
						} else {
							$curHold->status = "Pending";
							$curHold->canFreeze = $patron->getHomeLibrary()->allowFreezeHolds;
							$curHold->locationUpdateable = true;
						}

						if ($getHoldPosition) {
							//Get stats for the hold
							$getHoldStatsParams = 'service=open-ils.circ';
							$getHoldStatsParams .= '&method=open-ils.circ.hold.queue_stats.retrieve';
							$getHoldStatsParams .= '&param=' . json_encode($authToken);
							$getHoldStatsParams .= '&param=' . $holdInfo['id'];
							$getHoldStatsApiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getHoldStatsParams);
							ExternalRequestLogEntry::logRequest('evergreen.getHoldStats', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $getHoldStatsParams, $this->apiCurlWrapper->getResponseCode(), $getHoldStatsApiResponse, []);
							if ($this->apiCurlWrapper->getResponseCode() == 200) {
								$getHoldStatsApiResponse = json_decode($getHoldStatsApiResponse);
								if (isset($getHoldStatsApiResponse->payload) && isset($getHoldStatsApiResponse->payload[0])) {
									$holdStatsPayload = $getHoldStatsApiResponse->payload[0];
									$curHold->position = $holdStatsPayload->queue_position;
									$curHold->holdQueueLength = $holdStatsPayload->total_holds;
								}
							}
						}

						$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . $curHold->recordId);
						if ($recordDriver->isValid()) {
							$curHold->updateFromRecordDriver($recordDriver);
						} else {
							//Fetch title from SuperCat
							$titleInfo = $this->getBibFromSuperCat($curHold->recordId);
						}

						if (!$curHold->available) {
							$holds['unavailable'][$curHold->source . $curHold->cancelId . $curHold->userId] = $curHold;
						} else {
							$holds['available'][$curHold->source . $curHold->cancelId . $curHold->userId] = $curHold;
						}
					}
				}
			}
		}
		return $holds;
	}

	public function updateEditableUsername(User $patron, string $username): array {
		$authToken = $this->getAPIAuthToken($patron, true);
		$response = $this->updatePatronPropertyInEvergreen('username', $username, $patron->ils_password, $authToken);

		if ($response['success']) {
			$patron->ils_username = $username;
			$patron->update();
			return ['message' => 'Your username has been updated.', 'success' => true];
		}

		if($response['message']['code'] == 'USERNAME_EXISTS') {
			$userMessages['message'] = 'This username is already in use. Please choose another username.';
			return $userMessages;
		}

		$userMessages['message'] = 'Your username could not be updated. Please contact your library';
		return $userMessages;
	}

	public function getEditableUsername(User $user) {
		return $user->ils_username;
	}

	public function hasEditableUsername() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) {
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

		if (strpos($recordId, ':') !== false) {
			[
				,
				$recordId,
			] = explode(':', $recordId);
		}

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			//Translate to numeric location id
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)) {
				$pickupBranch = $location->historicCode;
			}
			if ($cancelDate == null) {
				global $library;
				if ($library->defaultNotNeededAfterDays == 0) {
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$cancelDate = date(DateTimeInterface::ISO8601, $sixMonthsFromNow);
				} else {
					//Default to a date 6 months (half a year) in the future.
					if ($library->defaultNotNeededAfterDays > 0) {
						$nnaDate = time() + $library->defaultNotNeededAfterDays * 24 * 60 * 60;
						$cancelDate = date(DateTimeInterface::ISO8601, $nnaDate);
					}
				}
			}
			/** @noinspection SpellCheckingInspection */
			$namedParams = [
				'patronid' => (int)$patron->unique_ils_id,
				"pickup_lib" => (int)$pickupBranch,
				"hold_type" => 'T',
				"request_lib" => (int)$pickupBranch,
//				"request_time" => date( DateTime::ISO8601),
//				"frozen" => 'f'
			];
			if (isset($_REQUEST['emailNotification']) && $_REQUEST['emailNotification'] == 'on') {
				$namedParams['email_notify'] = 't';
			}
			if (isset($_REQUEST['phoneNotification']) && $_REQUEST['phoneNotification'] == 'on') {
				if (isset($_REQUEST['phoneNumber']) && strlen($_REQUEST['phoneNumber']) > 0) {
					$namedParams['phone_notify'] = $_REQUEST['phoneNumber'];
				} elseif (isset($patron->phone) && strlen($patron->phone) > 0) {
					$namedParams['phone_notify'] = $patron->phone;
				}
			}
			if (isset($_REQUEST['smsNotification']) && $_REQUEST['smsNotification'] == 'on') {
				if (isset($_REQUEST['smsNumber']) && strlen($_REQUEST['smsNumber']) > 0) {
					if (isset($_REQUEST['smsCarrier']) && $_REQUEST['smsCarrier'] != -1) {
						$namedParams['sms_carrier'] = $_REQUEST['smsCarrier'];
						$namedParams['sms_notify'] = $_REQUEST['smsNumber'];
					}

				}
			}
			if ($cancelDate != null) {
				$namedParams['expire_time'] = $cancelDate;
			}

			$request = 'service=open-ils.circ&method=open-ils.circ.holds.test_and_create.batch.override';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . json_encode($namedParams);
			$request .= '&param=' . json_encode([(int)$recordId]);

			//First check to see if the hold can be placed
			$requestB = 'service=open-ils.circ&method=open-ils.circ.title_hold.is_possible';
			$requestB .= '&param=' . json_encode($authToken);
			/** @noinspection SpellCheckingInspection */
			$namedParamsB = [
				'patronid' => (int)$patron->unique_ils_id,
				"pickup_lib" => (int)$pickupBranch,
				"hold_type" => 'T',
				"titleid" => (int)$recordId,
				"oargs" => ["all" => 1],
			];
			$requestB .= '&param=' . json_encode($namedParamsB);

			$apiResponseB = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $requestB);

			ExternalRequestLogEntry::logRequest('evergreen.isHoldPossible', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponseB, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponseB = json_decode($apiResponseB);
				if ($apiResponseB->payload[0]->success == 0) {
					if (isset($apiResponseB->payload[0]->last_event) && ($apiResponseB->payload[0]->last_event->textcode == 'HIGH_LEVEL_HOLD_HAS_NO_COPIES')) {
						//Item/Part level holds are required
						$getPartsRequest = 'service=open-ils.search&method=open-ils.search.biblio.record_hold_parts';
						$namedPartsParams = [
							'record' => (int)$recordId,
						];
						$getPartsRequest .= '&param=' . json_encode($namedPartsParams);
						$getPartsResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getPartsRequest);

						ExternalRequestLogEntry::logRequest('evergreen.get_record_hold_parts', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $getPartsRequest, $this->apiCurlWrapper->getResponseCode(), $getPartsResponse, []);
						if ($this->apiCurlWrapper->getResponseCode() == 200) {
							$getPartsResponse = json_decode($getPartsResponse);
							$items = [];
							foreach ($getPartsResponse->payload[0] as $itemInfo) {
								$items[] = [
									'itemNumber' => $itemInfo->id,
									//'location' => trim(str_replace('&nbsp;', '', $itemInfo[2][$i])),
									'callNumber' => $itemInfo->label,
									//'status' => trim(str_replace('&nbsp;', '', $itemInfo[4][$i])),
								];
							}
							$hold_result['items'] = $items;
							if (count($items) > 0) {
								$message = 'Please select a part to place a hold on.';
							} else {
								$message = 'There are no holdable items for this title.';
							}
							$hold_result['success'] = false;
							$hold_result['message'] = $message;
							return $hold_result;
						}
					} else {
						$hold_result['message'] = translate([
							'text' => "This hold cannot be placed at this time. If you feel that this is in error, please contact your library for more information.",
							'isPublicFacing' => true
						]);
						return $hold_result;
					}
				}
			}

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			ExternalRequestLogEntry::logRequest('evergreen.placeHold', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$hold_result['message'] = $apiResponse->payload[0]->desc;
				} elseif (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->result->desc)) {
					$hold_result['message'] = $apiResponse->payload[0]->result->desc;
				} elseif (IPAddress::showDebuggingInformation() && isset($apiResponse->debug)) {
					$hold_result['message'] = $apiResponse->debug;
				} elseif (isset($apiResponse->payload[0]->result) && $apiResponse->payload[0]->result > 0) {
					$hold_result['message'] = translate([
						'text' => "Your hold was placed successfully.",
						'isPublicFacing' => true,
					]);
					$hold_result['success'] = true;

					// Result for API or app use
					$hold_result['api']['title'] = translate([
						'text' => 'Hold placed successfully',
						'isPublicFacing' => true,
					]);
					$hold_result['api']['message'] = translate([
						'text' => 'Your hold was placed successfully.',
						'isPublicFacing' => true,
					]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}
			}
		}

		return $hold_result;
	}

	public function getAPIAuthToken(User $patron, $allowStaffToken) {
		if ($allowStaffToken && UserAccount::isUserMasquerading() && empty($patron->getPasswordOrPin())) {
			$sessionInfo = $this->getStaffUserInfo();
		} else {
			$sessionInfo = $this->validatePatronAndGetAuthToken($patron->getBarcode(), $patron->getPasswordOrPin());
			if (!$sessionInfo['userValid'] && $allowStaffToken && UserAccount::isUserMasquerading()) {
				$sessionInfo = $this->getStaffUserInfo();
			}
		}
		if ($sessionInfo['userValid']) {
			return $sessionInfo['authToken'];
		}
		return null;
	}

	public function showDateInFines(): bool {
		return true;
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

		$fines = [];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			//Get a list of fines/fees
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.transactions.have_balance.fleshed';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . $patron->unique_ils_id;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			ExternalRequestLogEntry::logRequest('evergreen.getFines', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload)) {
					foreach ($apiResponse->payload[0] as $transactionObj) {
						$transactionRaw = $transactionObj->transaction->__p;
						$record = null;
						if (!empty($transactionObj->record)) {
							$record = $transactionObj->record->__p;
						}
						$circ = null;
						if (!empty($transactionObj->circ)) {
							$circ = $transactionObj->circ->__p;
						}
						/** @noinspection SpellCheckingInspection */
						$transactionObj = $this->mapEvergreenFields($transactionRaw, $this->fetchIdl('mbts'));
						if ($record != null) {
							$recordObject = $this->mapEvergreenFields($record, $this->fetchIdl('mvr'));
							$reason = $recordObject['title'] . ' - ' . $recordObject['author'];
//							if ($circ != null) {
//								$circObject = $this->mapEvergreenFields($circ, $this->fetchIdl('circ'));
//								if (!empty($circObject['stop_fines_time'])) {
//									$reason .= " (" .  date('M j, Y', strtotime($circObject['stop_fines_time']) . ")";
//								}
//
//							}
						} else {
							$reason = $transactionObj['last_billing_note'];
						}

						/** @noinspection SpellCheckingInspection */
						$curFine = [
							'fineId' => $transactionObj['id'],
							'date' => date('M j, Y', strtotime($transactionObj['xact_start'])),
							'type' => $transactionObj['xact_type'],
							'reason' => $transactionObj['last_billing_type'],
							'message' => $reason,
							'amountVal' => $transactionObj['total_owed'],
							'amountOutstandingVal' => $transactionObj['balance_owed'],
							'amount' => $currencyFormatter->formatCurrency($transactionObj['total_owed'], $currencyCode),
							'amountOutstanding' => $currencyFormatter->formatCurrency($transactionObj['balance_owed'], $currencyCode),
						];
						$fines[] = $curFine;
					}
				}
			}
		}

		return $fines;
	}

	public function showOutstandingFines() {
		return true;
	}


	public function completeFinePayment(User $patron, UserPayment $payment) {
		global $logger;
		$result = [
			'success' => false,
			'message' => 'Unknown error completing fine payment'
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken == null) {
			$result['message'] = translate([
				'text' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.',
				'isPublicFacing' => true
			]);
			$logger->log('Unable to authenticate with Evergreen while completing fine payment', Logger::LOG_ERROR);
			return $result;
		}

		// logged in, now fetch the patron's current last_xact_id
		// note that we cannot count on the user session object having
		// the most recent value
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$request = 'service=open-ils.actor&method=open-ils.actor.user.fleshed.retrieve';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . json_encode($patron->unique_ils_id);
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		ExternalRequestLogEntry::logRequest('evergreen.fetchUser', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if ($apiResponse->payload[0]->ilsevent == 0) {
				$evgUser = $apiResponse->payload[0]->__p;
				$evgUserObj = $this->mapEvergreenFields($evgUser, $this->fetchIdl('au'));
				$lastXactId = $evgUserObj['last_xact_id'];
			} else {
				$result['message'] = "Error {$apiResponse->payload[0]->textcode} loading user to update payment, please visit the library with your receipt.";
				$logger->log('Unable to fetch patron from Evergreen while completing fine payment', Logger::LOG_ERROR);
				return $result;
			}
		} else {
			// failed for an unknown reason
			return $result;
		}

		// parse payment into form that Evergreen expects
		$billingsPaid = explode(',', $payment->finesPaid);
		$evgPayments = [];
		foreach ($billingsPaid as $index => $billingPaid) {
			$evgPayment = explode('|', $billingPaid);
			$evgPayments[] = $evgPayment;
		}

		$evgPaymentParams = [
			'payment_type' => 'credit_card_payment',
			'userid' => $patron->unique_ils_id,
			'note' => 'via Aspen Discovery [' . $payment->paymentType . ']',
			'cc_args' => [
				'approval_code' => $payment->transactionId
			],
			'payments' => $evgPayments
		];

		$request = 'service=open-ils.circ&method=open-ils.circ.money.payment';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . json_encode($evgPaymentParams);
		$request .= '&param=' . json_encode($lastXactId);
		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		ExternalRequestLogEntry::logRequest('evergreen.payBillings', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);

		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if ($apiResponse->payload[0]->ilsevent == 0) {
				// we're good to go
				$result = [
					'success' => true,
					'message' => 'Your fines have been paid successfully, thank you.'
				];
			} else {
				$result['message'] = "Error {$apiResponse->payload[0]->textcode} updating your payment, please visit the library with your receipt.";
			}
		} else {
			$result['message'] = translate([
				'text' => 'Unable to post the payment to the library ILS. The library has been notified and will manually reconcile the payment.',
				'isPublicFacing' => true
			]);
			$logger->log('Unable to post the payment to the library ILS. The library has been notified and will manually reconcile the payment. Response code: ' . $this->apiCurlWrapper->getResponseCode(), Logger::LOG_ERROR);
		}

		$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
		return $result;
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		$username = trim($username);
		$password = trim($password);
		$session = $this->validatePatronAndGetAuthToken($username, $password);
		if ($session['userValid']) {
			$userData = $this->fetchSession($session['authToken']);
			if ($userData != null) {
				$user = $this->loadPatronInformation($userData, $username, $password);
				if ($user != null) {
					$user->password = $password;
				}

				return $user;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	private function getStaffUserInfo() {
		if (!array_key_exists($this->accountProfile->staffUsername, Evergreen::$accessTokensForUsers)) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';

			$session = [
				'userValid' => false,
				'authToken' => false,
			];

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
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
			ExternalRequestLogEntry::logRequest('evergreen.getStaffUserInfo', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), http_build_query($params), $this->apiCurlWrapper->getResponseCode(), $apiResponse, ['password' => $this->accountProfile->staffPassword]);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
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

	/**
	 * @param $patronBarcode
	 * @param $patronUsername
	 * @return bool|User
	 */
	public function findNewUser($patronBarcode, $patronUsername) {
		$staffSessionInfo = $this->getStaffUserInfo();
		if ($staffSessionInfo !== false) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.fleshed.retrieve_by_barcode';
			$request .= '&param=' . json_encode($staffSessionInfo['authToken']);
			$request .= '&param=' . json_encode($patronBarcode);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload) && isset($apiResponse->payload[0]->__p)) {
					if ($apiResponse->payload[0]->__c == 'au') { //class
						$mappedPatronData = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('au')); //payload

						/** @noinspection PhpUnnecessaryLocalVariableInspection */
						$user = $this->loadPatronInformation($mappedPatronData, $patronBarcode, null);
						return $user;
					}
				}
			}
		}

		//For Evergreen, this can only be called when initiating masquerade
		return false;
	}

	public function findNewUserByEmail($patronEmail): mixed {
		return false;
	}

	private function loadPatronInformation($userData, $username, $password): ?User {
		$user = new User();
		$user->username = $userData['id'];
		$user->unique_ils_id = $userData['id'];
		if ($user->find(true)) {
			$insert = false;
		} else {
			$insert = true;
		}

		$firstName = $userData['first_given_name'];
		$lastName = $userData['family_name'];

		//Handle preferred name
		if (!empty($userData['pref_first_given_name'])) {
			$firstName = $userData['pref_first_given_name'];
		}
		if (!empty($userData['pref_family_name'])) {
			$lastName = $userData['pref_family_name'];
		}
		$user->_fullname = $lastName . ',' . $firstName;
		$forceDisplayNameUpdate = false;
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		if ($user->lastname != $lastName) {
			$user->lastname = $lastName ?? '';
			$forceDisplayNameUpdate = true;
		}
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		//The user might have logged in with their username, make sure to set the card
		$staffUserInfo = $this->getStaffUserInfo();

		if (!is_object($userData['card'])) {
			if ($staffUserInfo['userValid']) {
				//Get details for the card
				//Lookup the patron type
				$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
				$headers = [
					'Content-Type: application/x-www-form-urlencoded',
				];
				$this->apiCurlWrapper->addCustomHeaders($headers, false);
				$request = 'service=open-ils.pcrud&method=open-ils.pcrud.retrieve.ac';
				$request .= '&param=' . json_encode($staffUserInfo['authToken']);
				$request .= '&param=' . json_encode($userData['card']);
				$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

				if ($this->apiCurlWrapper->getResponseCode() == 200) {
					$apiResponse = json_decode($apiResponse);
					if (isset($apiResponse->payload) && isset($apiResponse->payload[0]->__p)) {
						$cardInfo = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('ac'));
						$username = $cardInfo['barcode'];
					}
				}
			}
		} else {
			$cardInfo = $this->mapEvergreenFields($userData['card']->__p, $this->fetchIdl('ac'));
			$username = $cardInfo['barcode'];
		}
		$user->cat_username = $username;
		$user->ils_barcode = $username;
		if (!empty($password)) {
			$user->cat_password = $password;
			$user->ils_password = $password;
		}
		$user->email = $userData['email'];
		$user->ils_username = $userData['usrname'];
		if (!empty($userData['day_phone'])) {
			$user->phone = $userData['day_phone'];
		} elseif (!empty($userData['evening_phone'])) {
			$user->phone = $userData['evening_phone'];
		} elseif (!empty($userData['other_phone'])) {
			$user->phone = $userData['other_phone'];
		}

		$numericPtype = $userData['profile'];
		$user->patronType = $userData['profile'];
		if ($staffUserInfo['userValid']) {
			//Lookup the patron type
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.pcrud&method=open-ils.pcrud.retrieve.pgt';
			$request .= '&param=' . json_encode($staffUserInfo['authToken']);
			$request .= '&param=' . json_encode($numericPtype);
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload) && isset($apiResponse->payload[0]->__p)) {
					$pTypeInfo = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('pgt'));
					$user->patronType = $pTypeInfo['name'];
				}
			}
		}

		//TODO: Figure out how to parse the address we will need to look it up in web services
		//$fullAddress = $userData['mailing_address'];

		if (!empty($userData['expire_date'])) {
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

		if ($location->find(true)) {
			if ($user->homeLocationId != $location->locationId) {
				$user->homeLocationId = $location->locationId;
				$user->pickupLocationId = $user->homeLocationId;
			}
		} else {
			$user->homeLocationId = 0;
		}

		//Check patron pickup location the first time we see them.
		if ($insert) {
			$authToken = $this->getAPIAuthToken($user, true);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.patron.settings.retrieve';
			if ($authToken == null) {
				$request .= '&param=' . json_encode($staffUserInfo['authToken']);
				$request .= '&param=' . json_encode($user->unique_ils_id);
			} else {
				$request .= '&param=' . json_encode($authToken);
				$request .= '&param=' . json_encode($user->unique_ils_id);
			}
			$request .= '&param=' . json_encode([
					'opac.default_pickup_location',
				]);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				foreach ($apiResponse->payload[0] as $settingName => $settingValue) {
					if ($settingName == 'opac.default_pickup_location') {
						if (!empty($settingValue)) {
							$location = new Location();
							$location->historicCode = $settingValue;

							if ($location->find(true)) {
								$user->pickupLocationId = $location->locationId;
							}
						}
					}
				}
			}
		}

		if ($insert) {
			$user->created = date('Y-m-d');
			if (!$user->insert()) {
				return null;
			}
		} else {
			$user->update();
		}

		return $user;
	}

	private function validatePatronAndGetAuthToken(string $username, string $password) {
		if (array_key_exists($username, Evergreen::$accessTokensForUsers)) {
			return Evergreen::$accessTokensForUsers[$username];
		} else {
			$session = [
				'userValid' => false,
				'authToken' => false,
			];

			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$params = [
				'service' => 'open-ils.auth',
				'method' => 'open-ils.auth.login',
				'param' => json_encode([
					'password' => trim($password),
					'type' => 'persist',
					'org' => null,
					'identifier' => trim($username),
				]),
			];
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);
			ExternalRequestLogEntry::logRequest('evergreen.validatePatronAndGetAuthToken', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), http_build_query($params), $this->apiCurlWrapper->getResponseCode(), $apiResponse, ['password' => $password]);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if ($apiResponse->payload[0]->ilsevent == 0) {
					//Success!
					$session['userValid'] = true;
					$session['authToken'] = $apiResponse->payload[0]->payload->authtoken;
				}
			}

			Evergreen::$accessTokensForUsers[$username] = $session;
			return $session;
		}
	}

	private function fetchSession($authToken): ?array {
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$params = [
			'service' => 'open-ils.auth',
			'method' => 'open-ils.auth.session.retrieve',
			'param' => json_encode($authToken),
		];
		$getSessionResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $params);

		ExternalRequestLogEntry::logRequest('evergreen.fetchSession', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), http_build_query($params), $this->apiCurlWrapper->getResponseCode(), $getSessionResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$getSessionResponse = json_decode($getSessionResponse);
			if ($getSessionResponse->payload[0]->__c == 'au') { //class
				return $this->mapEvergreenFields($getSessionResponse->payload[0]->__p, $this->fetchIdl('au')); //payload
			}
		}
		return null;
	}

	private function mapEvergreenFields($rawResult, array $ahrFields): array {
		$mappedResult = [];
		foreach ($ahrFields as $position => $label) {
			if (isset($rawResult[$position])) {
				$mappedResult[$label] = $rawResult[$position];
			} else {
				$mappedResult[$label] = null;
			}

		}
		return $mappedResult;
	}

	private function getBibFromSuperCat($recordId): ?SimpleXMLElement {
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/opac/extras/supercat/retrieve/atom/record/' . $recordId;
		$superCatResult = $this->apiCurlWrapper->curlGetPage($evergreenUrl);
		ExternalRequestLogEntry::logRequest('evergreen.getBibFromSuperCat', 'GET', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), '', $this->apiCurlWrapper->getResponseCode(), $superCatResult, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			return simplexml_load_string($superCatResult);
		} else {
			return null;
		}
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
		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			//Get expiration information
			$expirationInformation = $this->getExpirationInformation($patron);
			$summary->expirationDate = $expirationInformation->expirationDate;

			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.fines.summary';
			$request .= '&param=' . json_encode($authToken);
			$request .= '&param=' . $patron->unique_ils_id;
			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload) && isset($apiResponse->payload[0]->__p)) {
					/** @noinspection SpellCheckingInspection */
					$moneySummary = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('mous'));
					$summary->totalFines = $moneySummary['balance_owed'];
				}
			}
		}

		return $summary;
	}

	public function getExpirationInformation(User $patron) : ExpirationInformation {
		$expirationInformation = new ExpirationInformation();

		// Use the same approach as loadContactInformation() to get patron-specific data
		// instead of session data, which returns staff account info when masquerading.
		$staffSessionInfo = $this->getStaffUserInfo();
		if ($staffSessionInfo !== false) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.fleshed.retrieve_by_barcode';
			$request .= '&param=' . json_encode($staffSessionInfo['authToken']);
			$request .= '&param=' . json_encode($patron->getBarcode());

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			ExternalRequestLogEntry::logRequest('evergreen.getExpirationInformation', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]->__p)) {
					if ($apiResponse->payload[0]->__c == 'au') {
						$mappedPatronData = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('au'));

						if (!empty($mappedPatronData['expire_date'])) {
							$expireTime = $mappedPatronData['expire_date'];
							$expireTime = strtotime($expireTime);
							$expirationInformation->expirationDate = $expireTime;
						}
					}
				}
			}
		}

		return $expirationInformation;
	}

	public function isPromptForHoldNotifications(): bool {
		return true;
	}

	public function getHoldNotificationTemplate(User $user): ?string {
		$this->loadHoldNotificationInfoFromEvergreen($user);
		return 'Record/evergreenHoldNotifications.tpl';
	}

	public function loadHoldNotificationInfo(User $user): ?array {
		return $this->loadHoldNotificationInfoFromEvergreen($user);
	}

	public function getHoldNotificationPreferencesTemplate(User $user): ?string {
		$this->loadHoldNotificationInfoFromEvergreen($user);
		return 'evergreenHoldNotificationPreferences.tpl';
	}

	public function processHoldNotificationPreferencesForm(User $user): array {
		$result = [
			'success' => false,
			'message' => 'Unknown error updating your Hold Notification Preferences',
		];

		$authToken = $this->getAPIAuthToken($user, false);

		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$holdNotificationMethods = '';
		if (isset($_REQUEST['emailNotification'])) {
			$holdNotificationMethods .= 'email';
		}
		if (isset($_REQUEST['phoneNotification'])) {
			if (strlen($holdNotificationMethods) > 0) {
				$holdNotificationMethods .= ':';
			}
			$holdNotificationMethods .= 'phone';
		}
		if (isset($_REQUEST['smsNotification'])) {
			if (strlen($holdNotificationMethods) > 0) {
				$holdNotificationMethods .= ':';
			}
			$holdNotificationMethods .= 'sms';
		}
		$defaultSmsCarrier = $_REQUEST['smsCarrier'] ?? '';
		$defaultSmsNumber = $_REQUEST['smsNumber'] ?? '';
		$defaultPhoneNumber = $_REQUEST['phoneNumber'] ?? '';

		$request = 'service=open-ils.actor&method=open-ils.actor.settings.apply.user_or_ws';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . json_encode([
				'opac.hold_notify' => $holdNotificationMethods,
				'opac.default_sms_carrier' => $defaultSmsCarrier,
				'opac.default_sms_notify' => $defaultSmsNumber,
				'opac.default_phone' => $defaultPhoneNumber,
			]);

		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		ExternalRequestLogEntry::logRequest('evergreen.updateHoldNotifications', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->debug)) {
				$result['message'] = $apiResponse->debug;
			} elseif ($apiResponse->status == 200) {
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'Your settings were updated successfully',
					'isPublicFacing' => true,
				]);
			}
		}

		return $result;
	}

	/**
	 * @param User $user
	 */
	private function loadHoldNotificationInfoFromEvergreen(User $user) {
		//Get a list of SMS carriers
		global $interface;
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		/** @noinspection SpellCheckingInspection */
		$request = 'service=open-ils.pcrud&method=open-ils.pcrud.search.csc.atomic';
		$request .= '&param=' . json_encode("ANONYMOUS");
		$request .= '&param=' . json_encode(['active' => 1]);

		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
		$smsCarriers = [];
		ExternalRequestLogEntry::logRequest('evergreen.getHoldNotificationTemplate', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $request, $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			foreach ($apiResponse->payload[0] as $smsInfo) {
				$smsObj = $this->mapEvergreenFields($smsInfo->__p, $this->fetchIdl('csc'));
				$smsCarriers[$smsObj['id']] = $smsObj['name'] . '(' . $smsObj['region'] . ')';
			}
		}
		asort($smsCarriers, SORT_STRING | SORT_FLAG_CASE);
		$interface->assign('smsCarriers', $smsCarriers);

		//Load notification preferences
		$notificationPreferences = [];
		if (!empty($user)) {
			$authToken = $this->getAPIAuthToken($user, true);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.settings.retrieve.atomic';
			$request .= '&param=' . json_encode([
					'opac.hold_notify',
					'opac.default_sms_carrier',
					'opac.default_sms_notify',
					'opac.default_phone',
				]);
			$request .= '&param=' . json_encode($authToken);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				foreach ($apiResponse->payload[0] as $payload) {
					$name = str_replace('.', '_', $payload->name);
					if ($payload->name == 'opac.hold_notify') {
						$value = explode(':', $payload->value);
					} else {
						$value = $payload->value;
					}

					$interface->assign($name, $value);
					$notificationPreferences[$name]['name'] = $name;
					$notificationPreferences[$name]['value'] = $value;
				}
			}

			$interface->assign('primaryEmail', $user->email);

			return [
				'preferences' => $notificationPreferences,
				'primaryEmail' => $user->email,
				'smsCarriers' => $smsCarriers,
			];
		}

		return [
			'preferences' => [],
			'primaryEmail' => '',
			'smsCarriers' => [],
		];
	}

	function fetchIdl($className): array {
		global $memCache;
		$idl = $memCache->get('evergreen_idl_' . $className);
		if ($idl == false || isset($_REQUEST['reload'])) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/reports/fm_IDL.xml?class=' . $className;
			$apiResponse = $this->apiCurlWrapper->curlGetPage($evergreenUrl);
			$idl = [];
			ExternalRequestLogEntry::logRequest('evergreen.fetchIdl', 'GET', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), '', $this->apiCurlWrapper->getResponseCode(), $apiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$idlRaw = simplexml_load_string($apiResponse);
				$fields = $idlRaw->class->fields;
				$index = 0;
				foreach ($fields->field as $field) {
					$attributes = $field->attributes();
					foreach ($attributes as $name => $value) {
						if ($name == 'name') {
							$idl[$index++] = (string)$attributes['name'];
							break;
						}
					}
				}
				global $configArray;
				$memCache->set('evergreen_idl_' . $className, $idl, $configArray['Caching']['evergreen_idl']);
			}
		}
		return $idl;
	}

	public function showHoldNotificationPreferences(): bool {
		return true;
	}

	public function showHoldPosition(): bool {
		return true;
	}

	public function showRenewalsRemaining(): bool {
		return true;
	}

	function getForgotPasswordType() {
		return 'emailResetLink';
	}

	function getEmailResetPinResultsTemplate() {
		return 'emailResetPinResults.tpl';
	}

	function getEmailResetPinTemplate() {
		if (isset($_REQUEST['resendEmail'])) {
			global $interface;
			$interface->assign('resendEmail', true);
		}
		return 'evergreenEmailResetPinLink.tpl';
	}

	private function _requestPasswordReset($identType, $identValue, $email) {
		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);

		$request = 'service=open-ils.actor&method=open-ils.actor.patron.password_reset.request';
		$request .= '&param=' . json_encode($identType);
		$request .= '&param=' . json_encode($identValue);
		$request .= '&param=' . json_encode($email);

		return $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);
	}

	function processEmailResetPinForm() : array {
		$result = [
			'success' => false,
			'patronFound' => false,
			'error' => translate([
				'text' => "Unknown error sending password reset.",
				'isPublicFacing' => true,
			]),
		];

		$patronIdentifier = strip_tags($_REQUEST['username']);
		$email = strip_tags($_REQUEST['email']);
		$apiResponse = $this->_requestPasswordReset('barcode', $patronIdentifier, $email);

		if ($this->apiCurlWrapper->getResponseCode() !== 200) {
			return $result;
		}

		$apiResponse = json_decode($apiResponse);

		// first check to see if we need to retry the patron lookup by username
		if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->textcode) && $apiResponse->payload[0]->textcode == 'ACTOR_USER_NOT_FOUND') {
			$apiResponse = $this->_requestPasswordReset('username', $patronIdentifier, $email);
			$apiResponse = json_decode($apiResponse);
		}

		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->textcode)) {
				$errorCode = $apiResponse->payload[0]->textcode;
				if ($errorCode == 'EMAIL_VERIFICATION_FAILED') {
					$result['error'] = translate([
						'text' => 'The email address you supplied does not match the address known to the library. Please try again',
						'isPublicFacing' => true,
					]);
				} elseif ($errorCode == 'ACTOR_USER_NOT_FOUND') {
					$result['error'] = translate([
						'text' => 'Unable to find your record. Please try again',
						'isPublicFacing' => true,
					]);
				}
				// if we get here, fall back to the default error response, as the
				// other errors that can happen likely signify
				// that somebody is trying to abuse the reset API
			} elseif ($apiResponse->payload[0] == 1) {
				$result['error'] = null;
				$result['patronFound'] = true;
				$result['success'] = true;
			}
		}

		return $result;
	}

	function getPasswordRecoveryTemplate() {
		global $interface;
		if (isset($_REQUEST['uniqueKey'])) {
			$error = null;
			$uniqueKey = $_REQUEST['uniqueKey'];
			$interface->assign('uniqueKey', $uniqueKey);

			// unlike Koha, the unique key gets verified only upon attempting
			// the password change
			$interface->assign('error', $error);

			$pinValidationRules = $this->getPasswordPinValidationRules();
			$interface->assign('pinValidationRules', $pinValidationRules);

			return 'evergreenPasswordRecovery.tpl';
		} else {
			//No key provided, go back to the starting point
			header('Location: /MyAccount/EmailResetPin');
			die();
		}
	}

	function processPasswordRecovery() {
		global $interface;
		if (isset($_REQUEST['uniqueKey'])) {
			$error = null;
			$uniqueKey = $_REQUEST['uniqueKey'];
			$pin1 = $_REQUEST['pin1'];

			// unlike Koha, we don't separately verify whether the
			// reset UUID is valid; that's handled by the method
			// that attempts the password change

			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$username = strip_tags($_REQUEST['username']);
			$email = strip_tags($_REQUEST['email']);
			$request = 'service=open-ils.actor&method=open-ils.actor.patron.password_reset.commit';
			$request .= '&param=' . json_encode($uniqueKey);
			$request .= '&param=' . json_encode($pin1);

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$error = translate([
					'text' => 'The link you clicked is either invalid, or expired.<br/>Be sure you used the link from the email, or contact library staff for assistance.<br/>Please contact the library if you need further assistance.',
				]);

				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->desc)) {
					$error = $apiResponse->payload[0]->desc;
				} elseif ($apiResponse->payload[0] == 1) {
					$result = [
						'success' => true,
						'message' => 'Your password was updated successfully.',
					];
					$interface->assign('result', $result);
					$error = null;
				}
				$interface->assign('error', $error);
				return 'evergreenPasswordRecoveryResult.tpl';
			} else {
				//No key provided, go back to the starting point
				header('Location: /MyAccount/EmailResetPin');
				die();
			}
		}
		return null;
	}

	function updatePin(User $patron, ?string $oldPin, string $newPin) {
		if ($patron->cat_password != $oldPin) {
			return [
				'success' => false,
				'message' => "The old password provided is incorrect.",
			];
		}
		$result = [
			'success' => false,
			'error' => translate([
				'text' => "Unknown error updating password.",
				'isPublicFacing' => true,
			]),
		];

		$authToken = $this->getAPIAuthToken($patron, false);

		$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$this->apiCurlWrapper->addCustomHeaders($headers, false);
		$request = 'service=open-ils.actor&method=open-ils.actor.user.password.update';
		$request .= '&param=' . json_encode($authToken);
		$request .= '&param=' . json_encode($newPin);
		$request .= '&param=' . json_encode($oldPin);

		$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

		if ($this->apiCurlWrapper->getResponseCode() == 200) {
			$apiResponse = json_decode($apiResponse);
			if (isset($apiResponse->payload[0]) && isset($apiResponse->payload[0]->textcode)) {
				$errorCode = $apiResponse->payload[0]->textcode;
				if ($errorCode == 'INCORRECT_PASSWORD') {
					$result['error'] = translate([
						'text' => 'The old password provided is incorrect',
						'isPublicFacing' => true,
					]);
				}
			} elseif ($apiResponse->payload[0] == 1) {
				$result['error'] = null;
				$result['success'] = true;
			}
		}

		return $result;
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
		$results = [
			'totalTitles' => 0,
			'totalLists' => 0,
		];

		$authToken = $this->getAPIAuthToken($patron, true);
		if ($authToken != null) {
			//Get a list of holds
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);

			$getListsParams = 'service=open-ils.actor';
			$getListsParams .= '&method=open-ils.actor.container.retrieve_by_class';
			$getListsParams .= '&param=' . json_encode($authToken);
			$getListsParams .= '&param=' . $patron->unique_ils_id;
			$getListsParams .= '&param=' . json_encode('biblio');
			$getListsParams .= '&param=' . json_encode('bookbag');
			$getListsApiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getListsParams);

			ExternalRequestLogEntry::logRequest('evergreen.getLists', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $getListsParams, $this->apiCurlWrapper->getResponseCode(), $getListsApiResponse, []);
			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($getListsApiResponse);
				if (isset($apiResponse->payload[0])) {
					foreach ($apiResponse->payload[0] as $bookbagInfo) {
						$bookBagInfoMapped = $this->mapEvergreenFields($bookbagInfo->__p, $this->fetchIdl('cbreb'));

						$title = $bookBagInfoMapped['name'];

						//Create the list (or find one that already exists)
						$newList = new UserList();
						$newList->user_id = $patron->id;
						$newList->title = $title;
						if (!$newList->find(true)) {
							$newList->description = $bookBagInfoMapped['description'];
							$newList->public = $bookBagInfoMapped['pub'] == 't';
							$newList->insert();
						} elseif ($newList->deleted == 1) {
							$newList->removeAllListEntries(true);
							$newList->deleted = 0;
							$newList->update();
						}

						//Load titles on the list
						$currentListTitles = $newList->getListTitles();

						$getListContentsParams = 'service=open-ils.actor';
						$getListContentsParams .= '&method=open-ils.actor.container.flesh';
						$getListContentsParams .= '&param=' . json_encode($authToken);
						$getListContentsParams .= '&param=' . json_encode('biblio');
						$getListContentsParams .= '&param=' . $bookBagInfoMapped['id'];
						$getListContentsApiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $getListContentsParams);
						ExternalRequestLogEntry::logRequest('evergreen.getListContents', 'POST', $evergreenUrl, $this->apiCurlWrapper->getHeaders(), $getListContentsParams, $this->apiCurlWrapper->getResponseCode(), $getListContentsApiResponse, []);
						if ($this->apiCurlWrapper->getResponseCode() == 200) {
							$listContentsResponse = json_decode($getListContentsApiResponse);
							if (isset($listContentsResponse->payload[0])) {
								$bookBagInfoMapped = $this->mapEvergreenFields($listContentsResponse->payload[0]->__p, $this->fetchIdl('cbreb'));
								foreach ($bookBagInfoMapped['items'] as $bookBagItem) {
									$bookBagItemMapped = $this->mapEvergreenFields($bookBagItem->__p, $this->fetchIdl('cbrebi'));
									$bibNumber = $bookBagItemMapped['target_biblio_record_entry'];
									$createTime = $bookBagItemMapped['create_time'];

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
												$listEntry->title = StringUtils::trimStringToLengthAtWordBoundary($groupedWork->full_title, 50, true);
												$listEntry->listId = $newList->id;
												$listEntry->notes = '';
												$listEntry->dateAdded = strtotime($createTime);
												$listEntry->insert();
												$currentListTitles[] = $listEntry;
											}
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
									$results['totalTitles']++;
								}
							}
						}

						$results['totalLists']++;
					}
				}
			}
		}

		return $results;
	}

	// Evergreen currently supports fetching users by ID or by barcode, but not by username
	// therefore we will disable masquerade with just  username.
	public function supportsLoginWithUsername(): bool {
		return false;
	}

	public function showPreferredNameInProfile(): bool {
		return true;
	}

	public function allowUpdatesOfPreferredName(User $patron): bool {
		return false;
	}

	public function loadContactInformation(User $user): void {
		$staffSessionInfo = $this->getStaffUserInfo();
		if ($staffSessionInfo !== false) {
			$evergreenUrl = $this->accountProfile->patronApiUrl . '/osrf-gateway-v1';
			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$this->apiCurlWrapper->addCustomHeaders($headers, false);
			$request = 'service=open-ils.actor&method=open-ils.actor.user.fleshed.retrieve_by_barcode';
			$request .= '&param=' . json_encode($staffSessionInfo['authToken']);
			$request .= '&param=' . json_encode($user->getBarcode());

			$apiResponse = $this->apiCurlWrapper->curlPostPage($evergreenUrl, $request);

			if ($this->apiCurlWrapper->getResponseCode() == 200) {
				$apiResponse = json_decode($apiResponse);
				if (isset($apiResponse->payload[0]->__p)) {
					if ($apiResponse->payload[0]->__c == 'au') { //class
						$mappedPatronData = $this->mapEvergreenFields($apiResponse->payload[0]->__p, $this->fetchIdl('au')); //payload

						$primaryAddress = reset($mappedPatronData['addresses']);
						if (!empty($primaryAddress)) {
							$primaryAddress = $this->mapEvergreenFields($primaryAddress->__p, $this->fetchIdl($primaryAddress->__c));
							$user->_address1 = $primaryAddress['street1'];
							$user->_address2 = $primaryAddress['street2'];
							$user->_city = $primaryAddress['city'];
							$user->_state = $primaryAddress['state'];
							$user->_zip = $primaryAddress['post_code'];
						}

						$user->_preferredName = '';
						if (!empty($mappedPatronData['pref_prefix'])) {
							$user->_preferredName .= $mappedPatronData['pref_prefix'] . ' ';
						} elseif (!empty($mappedPatronData['prefix'])) {
							$user->_preferredName .= $mappedPatronData['prefix'] . ' ';
						}
						if (!empty($mappedPatronData['pref_first_given_name'])) {
							$user->_preferredName .= $mappedPatronData['pref_first_given_name'];
						} elseif (!empty($mappedPatronData['first_given_name'])) {
							$user->_preferredName .= $mappedPatronData['first_given_name'];
						}
						if (!empty($mappedPatronData['pref_second_given_name'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['pref_second_given_name'];
						} elseif (!empty($mappedPatronData['second_given_name'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['second_given_name'];
						}
						if (!empty($mappedPatronData['pref_family_name'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['pref_family_name'];
						} elseif (!empty($mappedPatronData['family_name'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['family_name'];
						}
						if (!empty($mappedPatronData['pref_suffix'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['pref_suffix'];
						} elseif (!empty($mappedPatronData['suffix'])) {
							$user->_preferredName .= ' ' . $mappedPatronData['suffix'];
						}
						$user->_preferredName = trim($user->_preferredName);

						if (!empty($mappedPatronData['prefix'])) {
							$user->_fullname .= $mappedPatronData['prefix'] . ' ';
						}
						$user->_fullname .= $mappedPatronData['first_given_name'];
						if (!empty($mappedPatronData['second_given_name'])) {
							$user->_fullname .= ' ' . $mappedPatronData['second_given_name'];
						}
						if (!empty($mappedPatronData['family_name'])) {
							$user->_fullname .= ' ' . $mappedPatronData['family_name'];
						}
						if (!empty($mappedPatronData['suffix'])) {
							$user->_fullname .= ' ' . $mappedPatronData['suffix'];
						}
						$user->_fullname = trim($user->_fullname);

						if (!empty($mappedPatronData['expire_date'])) {
							$expireTime = $mappedPatronData['expire_date'];
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
					}
				}
			}
		}
	}
}
