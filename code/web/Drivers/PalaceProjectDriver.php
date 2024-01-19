<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class PalaceProjectDriver extends AbstractEContentDriver {
	/** @var CurlWrapper */
	private $curlWrapper;

	public function initCurlWrapper() {
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 20;
	}

	public function hasNativeReadingHistory(): bool {
		return false;
	}

	private $checkouts = [];

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
		if (isset($this->checkouts[$patron->id])) {
			return $this->checkouts[$patron->id];
		}

		$this->loadCirculationInformation($patron);

		return $this->checkouts[$patron->id];
	}

	public function loadCirculationInformation(User $patron) {
		$checkouts = [];
		$holds = [
			'available' => [],
			'unavailable' => [],
		];

		$settings = $this->getSettings($patron);
		if ($settings == false) {
			$this->checkouts[$patron->id] = $checkouts;
			$this->holds[$patron->id] = $holds;
		}

		global $interface;
		if ($interface != null) {
			$gitBranch = $interface->getVariable('gitBranch');
			if (substr($gitBranch, -1) == "\n") {
				$gitBranch = substr($gitBranch, 0, -1);
			}
		} else {
			$gitBranch = 'Primary';
		}
		$checkoutsUrl = $settings->apiUrl . "/" . $settings->libraryId . "/loans";
		$headers = [
			'Authorization: Basic ' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
			'Accept: application/opds+json',
			'User-Agent: Aspen Discovery ' . $gitBranch
		];

		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, true);
		$response = $this->curlWrapper->curlGetPage($checkoutsUrl);
		ExternalRequestLogEntry::logRequest('palaceProject.getCheckouts', 'POST', $checkoutsUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		if ($response != false) {
			$jsonResponse = json_decode($response);
			if (!empty($jsonResponse)) {
				foreach ($jsonResponse->publications as $publication) {
					$checkout = new Checkout();
					$checkout->type = 'palace_project';
					$checkout->source = 'palace_project';
					$checkout->userId = $patron->id;
					$checkout->sourceId = $publication->metadata->identifier;
					$checkout->recordId = $publication->metadata->identifier;

					require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
					$overDriveRecord = new PalaceProjectRecordDriver($checkout->sourceId);
					if ($overDriveRecord->isValid()) {
						$checkout->updateFromRecordDriver($overDriveRecord);
						$checkout->format = $checkout->getRecordFormatCategory();
					}

					$key = $checkout->source . $checkout->sourceId . $checkout->userId;
					$checkouts[$key] = $checkout;
				}
			}else {
				global $logger;
				$logger->log('Error loading checkouts, bad response from Palace Project', Logger::LOG_ERROR);
				$this->incrementStat('numApiErrors');
			}
		} else {
			global $logger;
			$logger->log('Error loading checkouts, no response from Palace Project', Logger::LOG_ERROR);
			$this->incrementStat('numApiErrors');
		}

		$this->checkouts[$patron->id] = $checkouts;
		$this->holds[$patron->id] = $holds;
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll(): bool {
		return false;
	}

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll(User $patron) {
		return false;
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @return mixed
	 */
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null) {
		return $this->checkOutTitle($patron, $recordId, true);
	}

	/**
	 * Return a title currently checked out to the user
	 *
	 * @param $patron User
	 * @param $transactionId   string
	 * @return array
	 */
	public function returnCheckout($patron, $transactionId) {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$settings = $this->getSettings();
		$returnCheckoutUrl = $settings->apiUrl . "/Services/VendorAPI/EarlyCheckin/v2?transactionID=$transactionId";
		$headers = [
			'Authorization: Basic' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
		];
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, false);
		$response = $this->curlWrapper->curlGetPage($returnCheckoutUrl);
		ExternalRequestLogEntry::logRequest('palaceProject.returnCheckout', 'GET', $returnCheckoutUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		/** @var stdClass $xmlResults */
		$xmlResults = simplexml_load_string($response);
		$removeHoldResult = $xmlResults->EarlyCheckinRestResult;
		$status = $removeHoldResult->status;
		if ($status->code != '0000') {
			$result['message'] = translate([
				'text' => "Could not return Boundless title, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => "Could not return Boundless title, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numApiErrors');
		} else {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your Boundless title was returned successfully',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Title returned',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your Boundless title was returned successfully',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numEarlyReturns');
			$patron->clearCachedAccountSummaryForSource('palaceProject');
			$patron->forceReloadOfCheckouts();
		}
		return $result;
	}

	private $holds = [];

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 * @param bool $forSummary
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($patron, $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		if (isset($this->holds[$patron->id])) {
			return $this->holds[$patron->id];
		}
		$this->loadCirculationInformation($patron);

		return $this->holds[$patron->id];
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$settings = $this->getSettings($patron);
		$holdUrl = $settings->apiUrl . "/Services/VendorAPI/addToHold/v2/$recordId/{$patron->getBarcode()}";
		$headers = [
			'Authorization: Basic' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
		];
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, false);
		$response = $this->curlWrapper->curlSendPage($holdUrl, 'GET');
		ExternalRequestLogEntry::logRequest('palaceProject.placeHold', 'GET', $holdUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		/** @var stdClass $xmlResults */
		$xmlResults = simplexml_load_string($response);
		$addToHoldResult = $xmlResults->addtoholdResult;
		$status = $addToHoldResult->status;
		if ($status->code == '3111') {
			//The title is available, try to check it out.
			return $this->checkOutTitle($patron, $recordId, false);
		} elseif ($status->code != '0000') {
			$result['message'] = translate([
				'text' => "Could not place Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => "Could not place Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numApiErrors');
		} else {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your Boundless hold was placed successfully',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold Placed Successfully',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your Boundless hold was placed successfully',
				'isPublicFacing' => true,
			]);
			$result['api']['action'] = translate([
				'text' => 'Go to Holds',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsPlaced');
			$this->trackUserUsageOfPalaceProject($patron);
			$this->trackRecordHold($recordId);
			$patron->clearCachedAccountSummaryForSource('palace_project');
			$patron->forceReloadOfHolds();
		}
		return $result;
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$settings = $this->getSettings($patron);
		$cancelHoldUrl = $settings->apiUrl . "/Services/VendorAPI/removeHold/v2/$recordId/{$patron->getBarcode()}";
		$headers = [
			'Authorization: Basic' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
		];
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, false);
		$response = $this->curlWrapper->curlSendPage($cancelHoldUrl, 'GET');
		ExternalRequestLogEntry::logRequest('palaceProject.cancelHold', 'GET', $cancelHoldUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		/** @var stdClass $xmlResults */
		$xmlResults = simplexml_load_string($response);
		$removeHoldResult = $xmlResults->removeholdResult;
		$status = $removeHoldResult->status;
		if ($status->code != '0000') {
			$result['message'] = translate([
				'text' => "Could not cancel Boundless hold, " . (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Could not cancel Boundless hold, ' . (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numApiErrors');
		} else {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your Boundless hold was cancelled successfully',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold cancelled',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your Boundless hold was cancelled successfully',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsCancelled');
			$patron->clearCachedAccountSummaryForSource('palace_project');
			$patron->forceReloadOfHolds();
		}
		return $result;
	}

	public function getAccountSummary(User $user): AccountSummary {
		[
			$existingId,
			$summary,
		] = $user->getCachedAccountSummary('palace_project');

		if ($summary === null || isset($_REQUEST['reload'])) {
			require_once ROOT_DIR . '/sys/User/AccountSummary.php';
			$summary = new AccountSummary();
			$summary->userId = $user->id;
			$summary->source = 'overdrive';
			$summary->resetCounters();
			$checkedOutItems = $this->getCheckouts($user, true);
			$summary->numCheckedOut = count($checkedOutItems);

			$holds = $this->getHolds($user, true);
			$summary->numAvailableHolds = count($holds['available']);
			$summary->numUnavailableHolds = count($holds['unavailable']);

			$summary->lastLoaded = time();
			if ($existingId != null) {
				$summary->id = $existingId;
				$summary->update();
			} else {
				$summary->insert();
			}
		}

		$summary->lastLoaded = time();
		if ($existingId != null) {
			$summary->id = $existingId;
			$summary->update();
		} else {
			$summary->insert();
		}

		return $summary;
	}

	/**
	 * @param User $patron
	 * @param string $titleId
	 *
	 * @param bool $fromRenew
	 * @return array
	 */
	public function checkOutTitle($patron, $titleId, $fromRenew = false) {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($titleId);
		if ($recordDriver->isValid()) {
			$borrowLink = $recordDriver->getBorrowLink();

			global $interface;
			if ($interface != null) {
				$gitBranch = $interface->getVariable('gitBranch');
				if (substr($gitBranch, -1) == "\n") {
					$gitBranch = substr($gitBranch, 0, -1);
				}
			} else {
				$gitBranch = 'Primary';
			}
			$headers = [
				'Authorization: Basic ' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
				'Accept: application/opds+json',
				'User-Agent: Aspen Discovery ' . $gitBranch
			];
			$this->initCurlWrapper();
			$this->curlWrapper->addCustomHeaders($headers, true);
			$response = $this->curlWrapper->curlGetPage($borrowLink);
			ExternalRequestLogEntry::logRequest('palaceProject.checkoutTitle', 'POST', $borrowLink, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
			if ($response != false) {
				$jsonResponse = json_decode($response);
				if ($this->curlWrapper->getResponseCode() == '200') {
					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'Your Palace Project title was checked out successfully. You may now download the title from your Account.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Checked out title',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your Boundless title was checked out successfully. You may now download the title from your Account.',
						'isPublicFacing' => true,
					]);
					$result['api']['action'] = translate([
						'text' => 'Go to Checkouts',
						'isPublicFacing' => true,
					]);

					$this->incrementStat('numCheckouts');
					$this->trackUserUsageOfPalaceProject($patron);
					$this->trackRecordCheckout($titleId);
					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$patron->clearCachedAccountSummaryForSource('palace_project');
					$patron->forceReloadOfCheckouts();
				}else{
					$result['message'] = translate([
						'text' => 'Sorry, we could not checkout this Palace Project title to you.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Unable to checkout title',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Sorry, we could not checkout this Palace Project title to you.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				global $logger;
				$logger->log('Error loading checkouts, no response from Palace Project', Logger::LOG_ERROR);
				$this->incrementStat('numApiErrors');
			}

		} else {
			$result['message'] = translate([
				'text' => 'Invalid Record Id',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/**
	 * @param User|null $user
	 * @return false|PalaceProjectSetting
	 */
	private function getSettings(User $user = null) {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectScope.php';
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';
		$activeLibrary = null;
		if ($user != null) {
			$activeLibrary = $user->getHomeLibrary();
		}
		if ($activeLibrary == null) {
			global $library;
			$activeLibrary = $library;
		}
		$scope = new PalaceProjectScope();
		$scope->id = $activeLibrary->palaceProjectScopeId;
		if ($activeLibrary->palaceProjectScopeId > 0) {
			if ($scope->find(true)) {
				$settings = new PalaceProjectSetting();
				$settings->id = $scope->settingId;
				if ($settings->find(true)) {
					return $settings;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfPalaceProject($user): void {
		require_once ROOT_DIR . '/sys/PalaceProject/UserPalaceProjectUsage.php';
		$userUsage = new UserPalaceProjectUsage();
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');
		global $aspenUsage;
		$userUsage->instance = $aspenUsage->getInstance();

		if ($userUsage->find(true)) {
			$userUsage->usageCount++;
			$userUsage->update();
		} else {
			$userUsage->usageCount = 1;
			$userUsage->insert();
		}
	}

	/**
	 * @param string $recordId
	 */
	function trackRecordCheckout($recordId): void {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectRecordUsage.php';
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
		$recordUsage = new PalaceProjectRecordUsage();
		$product = new PalaceProjectTitle();
		$product->palaceProjectId = $recordId;
		if ($product->find(true)) {
			$recordUsage->palaceProjectId = $product->palaceProjectId;
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesCheckedOut++;
				$recordUsage->update();
			} else {
				$recordUsage->timesCheckedOut = 1;
				$recordUsage->timesHeld = 0;
				$recordUsage->insert();
			}
		}
	}

	/**
	 * @param string $recordId
	 */
	function trackRecordHold($recordId): void {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectRecordUsage.php';
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
		$recordUsage = new PalaceProjectRecordUsage();
		$product = new PalaceProjectTitle();
		$product->palaceProjectId = $recordId;
		if ($product->find(true)) {
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->palaceProjectId = $product->palaceProjectId;
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesHeld++;
				$recordUsage->update();
			} else {
				$recordUsage->timesCheckedOut = 0;
				$recordUsage->timesHeld = 1;
				$recordUsage->insert();
			}
		}
	}

	function freezeHold(User $patron, $recordId): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$settings = $this->getSettings($patron);
		$freezeHoldUrl = $settings->apiUrl . "/Services/VendorAPI/suspendHold/v2/$recordId/{$patron->getBarcode()}";
		$headers = [
			'Authorization: Basic' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
		];
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, false);
		$response = $this->curlWrapper->curlSendPage($freezeHoldUrl, 'GET');
		ExternalRequestLogEntry::logRequest('palaceProject.freezeHold', 'GET', $freezeHoldUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		/** @var stdClass $xmlResults */
		$xmlResults = simplexml_load_string($response);
		$freezeHoldResult = $xmlResults->HoldResult;
		$status = $freezeHoldResult->status;
		if ($status->code != '0000') {
			$result['message'] = translate([
				'text' => "Could not freeze Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to freeze hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => "Could not freeze Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numApiErrors');
		} else {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your hold was frozen successfully',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold frozen',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your hold was frozen successfully',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsFrozen');
			$patron->forceReloadOfHolds();
		}
		return $result;
	}

	function thawHold(User $patron, $recordId): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$settings = $this->getSettings($patron);
		$freezeHoldUrl = $settings->apiUrl . "/Services/VendorAPI/activateHold/v2/$recordId/{$patron->getBarcode()}";
		$headers = [
			'Authorization: Basic' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
		];
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, false);
		$response = $this->curlWrapper->curlSendPage($freezeHoldUrl, 'GET');
		ExternalRequestLogEntry::logRequest('palaceProject.thawHold', 'GET', $freezeHoldUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		/** @var stdClass $xmlResults */
		$xmlResults = simplexml_load_string($response);
		$thawHoldResult = $xmlResults->HoldResult;
		$status = $thawHoldResult->status;
		if ($status->code != '0000') {
			$result['message'] = translate([
				'text' => "Could not thaw Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to thaw hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => "Could not thaw Boundless hold, %1%",
				1 => (string)$status->statusMessage,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numApiErrors');
		} else {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your Boundless hold was thawed successfully',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold thawed',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your Boundless hold was thawed successfully',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsThawed');
			$patron->forceReloadOfHolds();
		}
		return $result;
	}

	private function incrementStat(string $fieldName) {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectStats.php';
		$palaceProjectStats = new PalaceProjectStats();
		global $aspenUsage;
		$palaceProjectStats->instance = $aspenUsage->getInstance();
		$palaceProjectStats->year = date('Y');
		$palaceProjectStats->month = date('n');
		if ($palaceProjectStats->find(true)) {
			$palaceProjectStats->$fieldName++;
			$palaceProjectStats->update();
		} else {
			$palaceProjectStats->$fieldName = 1;
			$palaceProjectStats->insert();
		}
	}
}