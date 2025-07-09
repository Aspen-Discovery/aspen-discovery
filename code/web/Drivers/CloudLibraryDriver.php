<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class CloudLibraryDriver extends AbstractEContentDriver {
	/** @var CurlWrapper */
	private $curlWrapper;

	public function initCurlWrapper() {
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 20;
		$this->curlWrapper->connectTimeout = 4;
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
		if (isset($this->checkouts[$patron->id])) {
			return $this->checkouts[$patron->id];
		}

		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';

		$checkouts = [];
		$settings = $this->getSettings($patron);
		if ($settings == false) {
			return $checkouts;
		}

		$circulation = $this->getPatronCirculation($patron);

		if (isset($circulation->Checkouts->Item)) {
			foreach ($circulation->Checkouts->Item as $checkoutFromCloudLibrary) {
				$checkout = new Checkout();
				$checkout->type = 'cloud_library';
				$checkout->source = 'cloud_library';

				$checkout->sourceId = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout->recordId = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout->dueDate = strtotime((string)$checkoutFromCloudLibrary->EventEndDateInUTC);

				try {
					$timeDiff = $checkout->dueDate - time();
					//Checkouts cannot be renewed 3 days before the title is due
					if ($timeDiff < (3 * 24 * 60 * 60)) {
						$checkout->canRenew = true;
					} else {
						$checkout->canRenew = false;
					}
				} catch (Exception $e) {
					$checkout->canRenew = false;
				}

				$recordDriver = new CloudLibraryRecordDriver((string)$checkoutFromCloudLibrary->ItemId);
				if ($recordDriver->isValid()) {
					$checkout->updateFromRecordDriver($recordDriver);
					$checkout->accessOnlineUrl = $recordDriver->getAccessOnlineLinkUrl($patron);
				} else {
					$checkout->title = translate([
						'text' => 'Unknown cloudLibrary Title',
						'isPublicFacing' => true,
					]);
					$checkout->format = translate([
						'text' => 'Unknown - cloudLibrary',
						'isPublicFacing' => true,
					]);
				}

				$checkout->userId = $patron->id;

				$checkouts[$checkout->source . $checkout->sourceId . $checkout->userId] = $checkout;
			}
		}

		return $checkouts;
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
	 * Renew a title by checking it in first and then checking it out again.
	 *
	 * @param User $patron   The patron requesting the renewal.
	 * @param string $recordId The record id.
	 * @param mixed  $itemId   Optional additional item id.
	 * @param mixed  $itemIndex Optional index.
	 * @return array           Array containing success status and messages.
	 */
	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null): array
	{
		// Check if the item has any active holds or reserves.
		if ($this->itemHasHoldsOrReserves($patron, $recordId)) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'This title has active holds or reserves and cannot be renewed at this time.',
					'isPublicFacing' => true,
				]),
				'api' => [
					'title' => translate([
						'text' => 'Renewal not permitted',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'This title has active holds or reserves and cannot be renewed.',
						'isPublicFacing' => true,
					]),
				],
			];
		}

		// Perform check-in first (as recommended by CloudLibrary support).
		$checkInResult = $this->returnCheckout($patron, $recordId);
		if (!$checkInResult['success']) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Failed to check in the title for renewal: ' . $checkInResult['message'],
					'isPublicFacing' => true,
				])
			];
		}

		// Because there is no API request that exists for atomic, renewal operations, use a small
		// delay to ensure the CloudLibrary system has time to check in the title before checking it back out.
		usleep(500000); // 500ms delay

		$result = $this->checkOutTitle($patron, $recordId, true);

		if ($result['success']) {
			$checkouts = $this->getCheckouts($patron);
			foreach ($checkouts as $checkout) {
				if ($checkout->recordId == $recordId) {
					$result['dueDate'] = date('M j, Y', $checkout->dueDate);
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Return a title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @return array
	 */
	public function returnCheckout($patron, $recordId) {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		$settings = $this->getSettings($patron);
		$patronId = $this->getPatronId($patron);
		$apiPath = "/cirrus/library/{$settings->libraryId}/checkin";
		$requestBody = "<CheckinRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CheckinRequest>";
		$response = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('cloudLibrary.returnCheckout', 'POST', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), $requestBody, $this->curlWrapper->getResponseCode(), $response, []);
		if ($responseCode == '200') {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => "Your title was returned successfully.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Title returned',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your title was returned successfully.',
				'isPublicFacing' => true,
			]);

			$patron->clearCachedAccountSummaryForSource('cloud_library');
			$patron->forceReloadOfCheckouts();
		} elseif ($responseCode == '400') {
			$result['message'] = translate([
				'text' => "Bad Request returning checkout.",
				'isPublicFacing' => true,
			]);
			global $configArray;
			if (IPAddress::showDebuggingInformation()) {
				$result['message'] .= "\r\n" . $requestBody;
			}

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Bad Request returning checkout.',
				'isPublicFacing' => true,
			]);
		} elseif ($responseCode == '403') {
			$result['message'] = translate([
				'text' => "Unable to authenticate.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Unable to authenticate.',
				'isPublicFacing' => true,
			]);
		} elseif ($responseCode == '404') {
			$result['message'] = translate([
				'text' => "Checkout was not found.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Checkout was not found.',
				'isPublicFacing' => true,
			]);
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
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($patron): array {
		if (isset($this->holds[$patron->id])) {
			return $this->holds[$patron->id];
		}
		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
		require_once ROOT_DIR . '/sys/User/Hold.php';

		$holds = [
			'available' => [],
			'unavailable' => [],
		];

		$settings = $this->getSettings($patron);
		if ($settings == false) {
			return $holds;
		}
		$circulation = $this->getPatronCirculation($patron);

		if (isset($circulation->Holds->Item)) {
			$index = 0;
			foreach ($circulation->Holds->Item as $holdFromCloudLibrary) {
				$hold = $this->loadCloudLibraryHoldInfo($patron, $holdFromCloudLibrary);

				$key = $hold->type . $hold->sourceId . $hold->userId;
				$hold->position = (string)$holdFromCloudLibrary->Position;
				$holds['unavailable'][$key] = $hold;
				$index++;
			}
		}

		if (isset($circulation->Reserves->Item)) {
			$index = 0;
			foreach ($circulation->Reserves->Item as $holdFromCloudLibrary) {
				$hold = $this->loadCloudLibraryHoldInfo($patron, $holdFromCloudLibrary);
				$hold->available = true;

				$key = $hold->type . $hold->sourceId . $hold->userId;
				$holds['available'][$key] = $hold;
				$index++;
			}
		}

		return $holds;
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
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		$settings = $this->getSettings($patron);
		$patronId = $this->getPatronId($patron);
		$password = $patron->getPasswordOrPin();
		$patronEligibleForHolds = $patron->eligibleForHolds();
		if ($patronEligibleForHolds['fineLimitReached']) {
			$result['message'] = translate([
				'text' => 'Sorry, your account has too many outstanding fines to use cloudLibrary.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Fine limit reached',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, your account has too many outstanding fines to use cloudLibrary.',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		$apiPath = "/cirrus/library/{$settings->libraryId}/placehold?password=$password";
		$requestBody = "<PlaceHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</PlaceHoldRequest>";
		$response = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		ExternalRequestLogEntry::logRequest('cloudLibrary.placeHold', 'POST', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), $requestBody, $this->curlWrapper->getResponseCode(), $response, ['password' => $password]);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '201') {
			$this->trackUserUsageOfCloudLibrary($patron);
			$this->trackRecordHold($recordId);

			$result['success'] = true;
			$result['message'] = "<p class='alert alert-success'>" . translate([
					'text' => "Your hold was placed successfully.",
					'isPublicFacing' => true,
				]) . "</p>";
			$result['hasWhileYouWait'] = false;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold Placed Successfully',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your hold was placed successfully.',
				'isPublicFacing' => true,
			]);
			$result['api']['action'] = translate([
				'text' => 'Go to Holds',
				'isPublicFacing' => true,
			]);

			//Get the grouped work for the record
			global $library;
			if ($library->showWhileYouWait) {
				require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
				$recordDriver = new CloudLibraryRecordDriver($recordId);
				if ($recordDriver->isValid()) {
					$groupedWorkId = $recordDriver->getPermanentId();
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
					$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

					global $interface;
					if (count($whileYouWaitTitles) > 0) {
						$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
						$result['message'] .= '<h3>' . translate([
								'text' => 'While You Wait',
								'isPublicFacing' => true,
							]) . '</h3>';
						$result['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
						$result['hasWhileYouWait'] = true;
					}
				}
			}

			$patron->clearCachedAccountSummaryForSource('cloud_library');
			$patron->forceReloadOfHolds();
		} elseif ($responseCode == '405') {
			$result['message'] = translate([
				'text' => "Bad Request placing hold.",
				'isPublicFacing' => true,
			]);
			global $configArray;
			if (IPAddress::showDebuggingInformation()) {
				$result['message'] .= "\r\n" . $requestBody;
			}

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Bad Request placing hold.',
				'isPublicFacing' => true,
			]);

		} elseif ($responseCode == '403') {
			$result['message'] = translate([
				'text' => "Unable to authenticate.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Unable to authenticate.',
				'isPublicFacing' => true,
			]);

		} elseif ($responseCode == '404') {
			$result['message'] = translate([
				'text' => "Item was not found.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Item was not found.',
				'isPublicFacing' => true,
			]);

		} elseif ($responseCode == '404') {
			$result['message'] = translate([
				'text' => 'Could not place hold.  Already on hold or the item can be checked out',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Could not place hold.  Already on hold or the item can be checked out',
				'isPublicFacing' => true,
			]);
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
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		$settings = $this->getSettings($patron);
		$patronId = $this->getPatronId($patron);
		$apiPath = "/cirrus/library/{$settings->libraryId}/cancelhold";
		$requestBody = "<CancelHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CancelHoldRequest>";
		$response = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		ExternalRequestLogEntry::logRequest('cloudLibrary.cancelHold', 'POST', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), $requestBody, $this->curlWrapper->getResponseCode(), $response, []);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '200') {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => "Your hold was cancelled successfully.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold cancelled',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your hold was cancelled successfully.',
				'isPublicFacing' => true,
			]);

			$patron->clearCachedAccountSummaryForSource('cloud_library');
			$patron->forceReloadOfHolds();
		} elseif ($responseCode == '400') {
			$result['message'] = translate([
				'text' => "Bad Request cancelling hold.",
				'isPublicFacing' => true,
			]);
			if (IPAddress::showDebuggingInformation()) {
				$result['message'] .= "\r\n" . $requestBody;
			}

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Bad Request cancelling hold.',
				'isPublicFacing' => true,
			]);

		} elseif ($responseCode == '403') {
			$result['message'] = translate([
				'text' => "Unable to authenticate.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Unable to authenticate.',
				'isPublicFacing' => true,
			]);

		} elseif ($responseCode == '404') {
			$result['message'] = translate([
				'text' => "Item was not found.",
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Item was not found.',
				'isPublicFacing' => true,
			]);

		}
		return $result;
	}

	public function getAccountSummary(User $user): AccountSummary {
		[
			$existingId,
			$summary,
		] = $user->getCachedAccountSummary('cloud_library');

		if ($summary === null || isset($_REQUEST['reload'])) {
			require_once ROOT_DIR . '/sys/User/AccountSummary.php';
			$summary = new AccountSummary();
			$summary->userId = $user->id;
			$summary->source = 'cloud_library';
			$summary->resetCounters();

			//Get account information from api
			$circulation = $this->getPatronCirculation($user);

			$summary->numCheckedOut = empty($circulation->Checkouts->Item) ? 0 : count($circulation->Checkouts->Item);

			$summary->numAvailableHolds = empty($circulation->Reserves->Item) ? 0 : count($circulation->Reserves->Item);
			$summary->numUnavailableHolds = empty($circulation->Holds->Item) ? 0 : count($circulation->Holds->Item);

			$summary->lastLoaded = time();
			if ($existingId != null) {
				$summary->id = $existingId;
				$summary->update();
			} else {
				$summary->insert();
			}
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
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		// Result for API or app use
		$result['api']['title'] = translate([
			'text' => 'Unknown Error',
			'isPublicFacing' => true,
		]);
		$result['api']['message'] = translate([
			'text' => 'Unable to checkout title at this time. Please try again later.',
			'isPublicFacing' => true,
		]);

		$settings = $this->getSettings($patron);
		$patronId = $this->getPatronId($patron);
		$password = $this->getCloudLibraryPasswordOrPin($patron);
		if (!$patron->eligibleForHolds()) {
			$result['message'] = translate([
				'text' => 'Sorry, your account has too many outstanding fines to use cloudLibrary.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Fine limit reached',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, your account has too many outstanding fines to use cloudLibrary.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		$apiPath = "/cirrus/library/{$settings->libraryId}/checkout?password=$password";
		$requestBody = "<CheckoutRequest>
			<ItemId>{$titleId}</ItemId>
			<PatronId>{$patronId}</PatronId>
		</CheckoutRequest>";
		$checkoutResponse = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		ExternalRequestLogEntry::logRequest('cloudLibrary.checkoutTitle', 'POST', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), $requestBody, $this->curlWrapper->getResponseCode(), $checkoutResponse, ['password' => $password]);
		if ($checkoutResponse != null) {
			$checkoutXml = simplexml_load_string($checkoutResponse);
			if (isset($checkoutXml->Error)) {
				$result['message'] = $checkoutXml->Error->Message;
				$result['api']['message'] = $checkoutXml->Error->Message;
			} elseif ($checkoutXml->Message == 'Authentication failed') {
				$result['message'] = translate(['text' => 'Sorry, we could not checkout this cloudLibrary title to you.', 'isPublicFacing' => true,]);
				$result['api']['title'] = translate([
					'text' => 'Unable to checkout title',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate(['text' => 'Sorry, we could not checkout this cloudLibrary title to you.', 'isPublicFacing' => true,]);
			} elseif (str_contains($checkoutXml->Message, 'Patron cannot loan more than')) {
				$result['message'] = translate(['text' => 'Borrow limit reached. You have reached the maximum number of books you can borrow. Return some before borrowing more.', 'isPublicFacing' => true,]);
				$result['api']['title'] = translate([
					'text' => 'Borrow limit reached',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate(['text' => 'Borrow limit reached. You have reached the maximum number of books you can borrow. Return some before borrowing more.', 'isPublicFacing' => true,]);
			} else {
				$this->trackUserUsageOfCloudLibrary($patron);
				$this->trackRecordCheckout($titleId);
				$patron->lastReadingHistoryUpdate = 0;
				$patron->update();

				$result['success'] = true;
				if ($fromRenew) {
					$result['message'] = translate([
						'text' => 'Your title was renewed successfully.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Renewed title',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your title was renewed successfully.',
						'isPublicFacing' => true,
					]);
				} else {
					$result['message'] = translate([
						'text' => 'Your title was checked out successfully. You can read or listen to the title from your account.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Checked out title',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your title was checked out successfully. You can read or listen to the title from your account.',
						'isPublicFacing' => true,
					]);
					$result['api']['action'] = translate([
						'text' => 'Go to Checkouts',
						'isPublicFacing' => true,
					]);
				}

				$patron->clearCachedAccountSummaryForSource('cloud_library');
				$patron->forceReloadOfCheckouts();
			}
		}
		return $result;
	}

	private function getPatronCirculation(User $user) {
		$settings = $this->getSettings($user);
		if ($settings != false) {
			$patronId = $this->getPatronId($user);
			$password = $this->getCloudLibraryPasswordOrPin($user);
			$apiPath = "/cirrus/library/{$settings->libraryId}/circulation/patron/$patronId?password=$password";
			$circulationInfo = $this->callCloudLibraryUrl($settings, $apiPath);
			ExternalRequestLogEntry::logRequest('cloudLibrary.getPatronCirculation', 'GET', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), '', $this->curlWrapper->getResponseCode(), $circulationInfo, ['password' => $password]);
			return simplexml_load_string($circulationInfo);
		} else {
			return null;
		}
	}

	/**
	 * @param User|null $user
	 * @return CloudLibrarySetting|false
	 */
	public function getSettings(User $user = null) {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$activeLibrary = null;
		if ($user != null) {
			$activeLibrary = $user->getHomeLibrary();
		}
		if ($activeLibrary == null) {
			global $library;
			$activeLibrary = $library;
		}
		$scopeId = $activeLibrary->getCloudLibraryScope();
		if($scopeId > 0) {
			$scope = new CloudLibraryScope();
			$scope->id = $scopeId;
			if ($scope->find(true)) {
				return $scope->getSetting();
			}
		}
		return false;
	}

	private function getPatronId($patron)
	{
		$settings = $this->getSettings($patron);
		if ($settings->useAlternateLibraryCard) {
			return str_replace(' ', '%20', $patron->getAlternateLibraryCardBarcode());
		}
		return str_replace(' ', '%20', $patron->getBarcode());
	}

	private function getCloudLibraryPasswordOrPin($patron)
	{
		$settings = $this->getSettings($patron);
		if ($settings->useAlternateLibraryCard) {
			return $patron->getAlternateLibraryCardPasswordOrPin();
		}
		return $patron->getPasswordOrPin();
	}

	private function callCloudLibraryUrl(CloudLibrarySetting $settings, string $apiPath, $method = 'GET', $requestBody = null) {
		$nowUtcDate = gmdate('D, d M Y H:i:s T');
		$dataToSign = $nowUtcDate . "\n" . $method . "\n" . $apiPath;
		$signature = base64_encode(hash_hmac("sha256", $dataToSign, $settings->accountKey, true));

		$headers = [
			"3mcl-Datetime: $nowUtcDate",
			"3mcl-Authorization: 3MCLAUTH {$settings->accountId}:$signature",
			'3mcl-APIVersion: 3.0',
			'Content-Type: application/xml',
			'Accept: application/xml',
		];

		//Can't reuse the curl wrapper so make sure it is initialized on each call
		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, true);
		return $this->curlWrapper->curlSendPage($settings->apiUrl . $apiPath, $method, $requestBody);
	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfCloudLibrary($user): void {
		require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
		global $library;
		$userCloudLibraryTracking = $user->userCookiePreferenceLocalAnalytics;
		$userUsage = new UserCloudLibraryUsage();
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userCloudLibraryTracking) {
			if ($userUsage->find(true)) {
				$userUsage->usageCount++;
				$userUsage->update();
			} else {
				$userUsage->usageCount = 1;
				$userUsage->insert();
			}
		}
	}

	/**
	 * @param string $recordId
	 */
	function trackRecordCheckout($recordId): void {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
		$recordUsage = new CloudLibraryRecordUsage();
		$product = new CloudLibraryProduct();
		$product->cloudLibraryId = $recordId;
		if ($product->find(true)) {
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->cloudLibraryId = $product->id;
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
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
		$recordUsage = new CloudLibraryRecordUsage();
		$product = new CloudLibraryProduct();
		$product->cloudLibraryId = $recordId;
		if ($product->find(true)) {
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->cloudLibraryId = $product->id;
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

	public function checkAuthentication(User $user): bool
	{
		$settings = $this->getSettings($user);
		if ($settings == false) {
			return false;
		}
		$patronId = $this->getPatronId($user);
		$password = $this->getCloudLibraryPasswordOrPin($user);
		$apiPath = "/cirrus/library/{$settings->libraryId}/patron/$patronId?password=$password";
		$authenticationResponse = $this->callCloudLibraryUrl($settings, $apiPath);
		ExternalRequestLogEntry::logRequest('cloudLibrary.checkAuthentication', 'GET', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), '', $this->curlWrapper->getResponseCode(), $authenticationResponse, ['password' => $password]);
		/** @var SimpleXMLElement $authentication */
		$authentication = simplexml_load_string($authenticationResponse);
		if ($authentication->result == 'SUCCESS') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $user
	 * @param $holdFromCloudLibrary
	 * @return Hold
	 */
	private function loadCloudLibraryHoldInfo(User $user, $holdFromCloudLibrary): Hold {
		$hold = new Hold();
		$hold->type = 'cloud_library';
		$hold->source = 'cloud_library';

		$hold->sourceId = (string)$holdFromCloudLibrary->ItemId;
		$hold->recordId = (string)$holdFromCloudLibrary->ItemId;
		$hold->createDate = strtotime($holdFromCloudLibrary->EventStartDateInUTC);
		$hold->userId = $user->id;
		if (isset($holdFromCloudLibrary->Position)) {
			$hold->position = (int)$holdFromCloudLibrary->Position;
		}
		$hold->cancelable = true;

		$recordDriver = new CloudLibraryRecordDriver((string)$holdFromCloudLibrary->ItemId);
		if ($recordDriver->isValid()) {
			$hold->updateFromRecordDriver($recordDriver);
		} else {
			$hold->title = translate([
				'text' => 'Unknown cloudLibrary Title',
				'isPublicFacing' => true,
			]);
			$hold->format = translate([
				'Unknown - cloudLibrary',
				'isPublicFacing' => true,
			]);
		}
		return $hold;
	}

	/**
	 * @param string $itemId
	 * @param User $patron
	 *
	 * @return null|string
	 */
	public function getItemStatus($itemId, $patron) {
		$settings = $this->getSettings($patron);
		$patronId = $this->getPatronId($patron);
		$apiPath = "/cirrus/library/{$settings->libraryId}/item/status/$patronId/$itemId";
		$itemStatusInfo = $this->callCloudLibraryUrl($settings, $apiPath);
		ExternalRequestLogEntry::logRequest('cloudLibrary.getItemStatus', 'GET', $settings->apiUrl . $apiPath, $this->curlWrapper->getHeaders(), '', $this->curlWrapper->getResponseCode(), $itemStatusInfo, []);
		if ($this->curlWrapper->getResponseCode() == 200) {
			$itemStatus = simplexml_load_string($itemStatusInfo);
			$this->curlWrapper = new CurlWrapper();
			return (string)$itemStatus->DocumentStatus->status;
		} elseif ($this->curlWrapper->getResponseCode() == 403) {
			$errorMessage = simplexml_load_string($itemStatusInfo);
			return (string)$errorMessage->Message;
		} else {
			return false;
		}
	}

	public function redirectToCloudLibrary(User $patron, CloudLibraryRecordDriver $recordDriver) {
		$settings = $this->getSettings($patron);
		$userInterfaceUrl = $settings->userInterfaceUrl;

		//Setup the default redirection paths
		$redirectDetailUrl = $userInterfaceUrl . '/detail/' . $recordDriver->getId();

		//Generate redirect URL to Cloud Library with password
		$redirectUrl = $this->generateRedirectUrl($patron, $recordDriver, true);

		if (!$redirectUrl) {
			//Some libraries do not require PIN to login cloud library
			//Generate redirect URL to cloud library without password
			$redirectUrl = $this->generateRedirectUrl($patron, $recordDriver, false);
		}

		if (!$redirectUrl) {
			//Fall back to record detail page
			$redirectUrl = $redirectDetailUrl;
		}

		header('Location:' . $redirectUrl);
		die();

	}

	private function generateRedirectUrl(User $patron, CloudLibraryRecordDriver $recordDriver, $includePassword = false) {
		$settings = $this->getSettings($patron);
		$userInterfaceUrl = $settings->userInterfaceUrl;
		if (substr($userInterfaceUrl, -1) == '/') {
			$userInterfaceUrl = substr($userInterfaceUrl, 0, -1);
		}

		//Login the user to CloudLibrary
		$loginUrl = "{$userInterfaceUrl}/login";
		if ($includePassword) {
			$postParams = [
				'username' => $this->getPatronId($patron),
				'password' => $this->getCloudLibraryPasswordOrPin($patron),
				'eula' => 'eula',
				'login_form' => 'true',
				'library_id' => $settings->accountId,
			];
		} else {
			$postParams = [
				'username' => $this->getPatronId($patron),
				'eula' => 'eula',
				'login_form' => 'true',
				'library_id' => $settings->accountId,
			];
		}
		$curlWrapper = new CurlWrapper();
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$curlWrapper->addCustomHeaders($headers, false);
		$response = $curlWrapper->curlPostPage($loginUrl, $postParams, [CURLOPT_HEADER => true]);
		ExternalRequestLogEntry::logRequest('cloudLibrary.redirectToCloudLibrary', 'POST', $loginUrl, $curlWrapper->getHeaders(), json_encode($postParams), $curlWrapper->getResponseCode(), $response, ['password' => $patron->getPasswordOrPin()]);
		if ($response) {
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
			$cookies = [];
			foreach ($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			foreach ($cookies as $name => $value) {
				if (strpos($name, 'sessionid_') === 0) {
					if ($recordDriver->getPrimaryFormat() == 'MP3') {
						$redirectUrl = "$userInterfaceUrl/audiobooks/{$recordDriver->getId()}?auth_cookie={$value}";
					} else {
						$redirectUrl = "$userInterfaceUrl/ebooks/{$recordDriver->getId()}?auth_cookie={$value}";
					}
					$redirectUrlCurlWrapper = new CurlWrapper();
					$redirectUrlCurlWrapper->curlGetPage($redirectUrl);
					$redirectUrlCURLResponseCode = $redirectUrlCurlWrapper->getResponseCode();
					if ($redirectUrlCURLResponseCode == 200) {
						return $redirectUrl;
					}
				}
			}
		}
		return null;
	}

	public function getCloudLibraryUrl(User $patron, CloudLibraryRecordDriver $recordDriver) {
		$redirectUrl = null;
		$settings = $this->getSettings($patron);
		$userInterfaceUrl = $settings->userInterfaceUrl;
		if (substr($userInterfaceUrl, -1) == '/') {
			$userInterfaceUrl = substr($userInterfaceUrl, 0, -1);
		}

		//Setup the default redirection paths
		/*if ($recordDriver->getPrimaryFormat() == 'MP3') {
			$redirectUrl = $userInterfaceUrl . '/AudioPlayer/' . $recordDriver->getId();
		} else {
			$redirectUrl = $userInterfaceUrl . '/EPubRead/' . $recordDriver->getId();
		}*/

		$redirectUrl = $userInterfaceUrl . '/detail/' . $recordDriver->getId();

		//Login the user to CloudLibrary
		$loginUrl = "{$userInterfaceUrl}/login";
		$postParams = [
			'username' => $patron->getBarcode(),
			'password' => $patron->getPasswordOrPin(),
		];
		$curlWrapper = new CurlWrapper();
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$curlWrapper->addCustomHeaders($headers, false);
		$response = $curlWrapper->curlPostPage($loginUrl, $postParams, [CURLOPT_HEADER => true]);
		ExternalRequestLogEntry::logRequest('cloudLibrary.redirectToCloudLibrary', 'POST', $loginUrl, $curlWrapper->getHeaders(), '', $curlWrapper->getResponseCode(), $response, ['password' => $patron->getPasswordOrPin()]);
		if ($response) {
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
			$cookies = [];
			foreach ($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			foreach ($cookies as $name => $value) {
				if (strpos($name, 'sessionid_') === 0) {
					/*if ($recordDriver->getPrimaryFormat() == 'MP3') {
						//TODO: Need a new URL from CloudLibrary for audio books
						$redirectUrl = "$userInterfaceUrl/audiobooks/{$recordDriver->getId()}?auth_cookie={$value}";
					} else {
						$redirectUrl = "$userInterfaceUrl/ebooks/{$recordDriver->getId()}?auth_cookie={$value}";
					}*/

					$redirectUrl = "$userInterfaceUrl/ebooks/{$recordDriver->getId()}?auth_cookie={$value}";
					break;
				}
			}
		}

		return $redirectUrl;
	}

	/**
	 * Check if an item has any active holds or reserves using the cloudLibrary API.
	 *
	 * @param User $patron   The patron requesting the renewal.
	 * @param string $itemId   The cloudLibrary item id.
	 * @return bool            True if holds or reserves exist, false otherwise.
	 */
	private function itemHasHoldsOrReserves(User $patron, string $itemId): bool {
		$settings = $this->getSettings($patron);
		$apiPath = "/cirrus/library/{$settings->libraryId}/circulation/item/{$itemId}";
		$response = $this->callCloudLibraryUrl($settings, $apiPath, 'GET');
		if ($this->curlWrapper->getResponseCode() == 200) {
			$xml = simplexml_load_string($response);
			// Check if any holds exist.
			if (isset($xml->Holds) && count($xml->Holds->Patron) > 0) {
				return true;
			}
			// Check if any reserves exist.
			if (isset($xml->Reserves) && count($xml->Reserves->Patron) > 0) {
				return true;
			}
		}
		return false;
	}

}