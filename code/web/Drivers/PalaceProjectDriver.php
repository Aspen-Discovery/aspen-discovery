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
		if (isset($this->checkouts[$patron->id])) {
			return $this->checkouts[$patron->id];
		}

		$this->loadCirculationInformation($patron);

		return $this->checkouts[$patron->id];
	}

	public function loadCirculationInformation(User $patron) {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$checkouts = [];
		$holds = [
			'available' => [],
			'unavailable' => [],
		];

		$settings = $this->getSettings($patron);
		if ($settings == false) {
			$this->checkouts[$patron->id] = $checkouts;
			$this->holds[$patron->id] = $holds;
			return;
		}

		$headers = $this->getPalaceProjectHeaders($patron);
		$homeLibrary = $patron->getHomeLibrary();
		if (!empty($homeLibrary)) {
			$homePalaceProjectLibraryId = $homeLibrary->palaceProjectLibraryId;
			if (!empty($homePalaceProjectLibraryId)) {
				$checkoutsUrl = $settings->apiUrl . "/" . $homePalaceProjectLibraryId . "/loans?refresh=false";
			} else {
				$checkoutsUrl = $settings->apiUrl . "/" . $settings->libraryId . "/loans?refresh=false";
			}
		} else {
			$checkoutsUrl = $settings->apiUrl . "/" . $settings->libraryId . "/loans?refresh=false";
		}

		$this->initCurlWrapper();
		$this->curlWrapper->addCustomHeaders($headers, true);
		$response = $this->curlWrapper->curlGetPage($checkoutsUrl);
		ExternalRequestLogEntry::logRequest('palaceProject.getCirculation', 'POST', $checkoutsUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
		if ($response != false) {
			$jsonResponse = json_decode($response);
			if (!empty($jsonResponse) && !empty($jsonResponse->publications)) {
				foreach ($jsonResponse->publications as $publication) {
					//Figure out if this is a hold or a checkout
					$links = $publication->links;
					$circulationType = 'checkout';
					$holdAvailable = false;
					foreach ($links as $link) {
						if ($link->rel == 'http://opds-spec.org/acquisition/borrow') {
							if ($link->properties->availability->state == 'reserved') {
								$circulationType = 'hold';
								$holdAvailable = false;
							}else if ($link->properties->availability->state == 'ready') {
								$circulationType = 'hold';
								$holdAvailable = true;
							}
						}
					}

					require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
					require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
					if ($circulationType == 'checkout') {
						$checkout = new Checkout();
						$checkout->type = 'palace_project';
						$checkout->source = 'palace_project';
						$checkout->userId = $patron->id;
						$checkout->sourceId = $publication->metadata->identifier;
						$checkout->recordId = $publication->metadata->identifier;
						$palaceProjectTitle = new PalaceProjectTitle();
						$palaceProjectTitle->palaceProjectId = $publication->metadata->identifier;
						if ($palaceProjectTitle->find(true)) {
							$checkout->sourceId = $palaceProjectTitle->id;
							$checkout->recordId = $palaceProjectTitle->id;
							$palaceProjectRecord = new PalaceProjectRecordDriver($checkout->sourceId);
							if ($palaceProjectRecord->isValid()) {
								$checkout->updateFromRecordDriver($palaceProjectRecord);
								$checkout->format = $palaceProjectRecord->getPrimaryFormat();
							}
						}else{
							//We can't find this title locally, it is either from another eContent vendor or no longer exists,
							// don't show it
							continue;
//							$checkout->title = $publication->metadata->title;
//							if (!empty($publication->metadata->author)) {
//								$checkout->author = $publication->metadata->author->name;
//							}
						}

						foreach ($links as $link) {
							if ($link->rel == 'http://librarysimplified.org/terms/rel/revoke') {
								$checkout->canReturnEarly = true;
								$checkout->earlyReturnUrl = $link->href;
							}else if ($link->rel == 'http://opds-spec.org/acquisition') {
								if (!empty($link->properties) && !empty($link->properties->availability)) {
									if (!empty($link->properties->availability->since)) {
										$checkout->checkoutDate = strtotime($link->properties->availability->since);
									}
									if (!empty($link->properties->availability->until)) {
										$checkout->dueDate = strtotime($link->properties->availability->until);
									}
								}
							}
						}

						$key = $checkout->source . $checkout->sourceId . $checkout->userId;
						$checkouts[$key] = $checkout;
					}else{
						$hold = new Hold();
						$hold->type = 'palace_project';
						$hold->source = 'palace_project';
						$hold->userId = $patron->id;
						$hold->sourceId = $publication->metadata->identifier;
						$hold->recordId = $publication->metadata->identifier;
						$hold->cancelable = true;

						$palaceProjectTitle = new PalaceProjectTitle();
						$palaceProjectTitle->palaceProjectId = $publication->metadata->identifier;
						if ($palaceProjectTitle->find(true)) {
							$hold->sourceId = $palaceProjectTitle->id;
							$hold->recordId = $palaceProjectTitle->id;
							$palaceProjectRecord = new PalaceProjectRecordDriver($hold->sourceId);
							if ($palaceProjectRecord->isValid()) {
								$hold->updateFromRecordDriver($palaceProjectRecord);
								$hold->format = $palaceProjectRecord->getPrimaryFormat();
							}
						}else{
							//We can't find this title locally, it is either from another eContent vendor or no longer exists,
							// don't show it
							continue;
						}

						$hold->userId = $patron->id;
						$key = $hold->source . $hold->sourceId . $hold->userId;

						$hold->available = $holdAvailable;

						foreach ($links as $link) {
							if ($link->rel == 'http://opds-spec.org/acquisition/borrow') {
								if (!empty($link->properties->availability->since)) {
									$hold->createDate = strtotime($link->properties->availability->since);
								}
								if (!empty($link->properties->availability->until)) {
									$hold->expirationDate = strtotime($link->properties->availability->until);
								}
							}elseif ($link->rel == 'http://librarysimplified.org/terms/rel/revoke') {
								$hold->cancellationUrl = $link->href;
							}
						}

						if ($holdAvailable) {
							$holds['available'][$key] = $hold;
						} else {
							$holds['unavailable'][$key] = $hold;
						}
					}

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
	 * @param $recordId   string
	 * @return array
	 */
	public function returnCheckout($patron, $recordId) {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error returning title',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$checkouts = $patron->getCheckouts(false,'palace_project');
		$foundCheckout = false;
		foreach ($checkouts as $checkout) {
			if ($checkout->recordId == $recordId) {
				$foundCheckout = true;
				$returnUrl = $checkout->earlyReturnUrl;
				$headers = $this->getPalaceProjectHeaders($patron);

				$this->initCurlWrapper();
				$this->curlWrapper->addCustomHeaders($headers, true);
				$response = $this->curlWrapper->curlGetPage($returnUrl);
				ExternalRequestLogEntry::logRequest('palaceProject.returnCheckout', 'POST', $returnUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
				if ($response != false) {
					//This returns XML, but we don't really need it for anything, the response code is enough.
					//$jsonResponse = json_decode($response);
					if ($this->curlWrapper->getResponseCode() == 200) {
						$result['success'] = true;
						$result['title'] = translate([
							'text' => 'Title returned successfully',
							'isPublicFacing' => true,
						]);
						$result['message'] = translate([
							'text' => 'Your Palace Project title was returned successfully',
							'isPublicFacing' => true,
						]);

						// Result for API or app use
						$result['api']['title'] = translate([
							'text' => 'Title returned',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => 'Your Palace Project title was returned successfully',
							'isPublicFacing' => true,
						]);
						$this->incrementStat('numEarlyReturns');
						$patron->clearCachedAccountSummaryForSource('palace_project');
						$patron->forceReloadOfCheckouts();
					} else {
						$result['message'] = translate([
							'text' => "Could not return Palace Project title",
							'isPublicFacing' => true,
						]);

						// Result for API or app use
						$result['api']['title'] = translate([
							'text' => 'Unable to return title',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => "Could not return Palace Project title",
							'isPublicFacing' => true,
						]);

						$this->incrementStat('numApiErrors');
					}
				}
				break;
			}
		}
		if (!$foundCheckout) {
			//Title was already returned
			$result['success'] = true;
			$result['title'] = translate([
				'text' => 'Title returned successfully',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Your Palace Project title was previously returned',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Title returned',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your Palace Project title was returned successfully',
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

		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($recordId);
		if ($recordDriver->isValid()) {
			$borrowLink = $recordDriver->getBorrowLink();
			$settings = $this->getActiveSettings();
			$homeLibrary = $patron->getHomeLibrary();
			if (!empty($homeLibrary)) {
				$homePalaceProjectLibraryId = $homeLibrary->palaceProjectLibraryId;
				if (!empty($homePalaceProjectLibraryId)) {
					$borrowLink = preg_replace(
						'~/[^/]+/works/~',
					'/' . $homePalaceProjectLibraryId . '/works/',
						$borrowLink
					);
				}
			}
			$headers = $this->getPalaceProjectHeaders($patron);
			$this->initCurlWrapper();
			$this->curlWrapper->addCustomHeaders($headers, true);
			$response = $this->curlWrapper->curlGetPage($borrowLink);
			ExternalRequestLogEntry::logRequest('palaceProject.placeHold', 'POST', $borrowLink, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
			if ($response != false) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse == false) {
					$xmlResponse = simplexml_load_string($response);
				}
				if ($this->curlWrapper->getResponseCode() == '200' || $this->curlWrapper->getResponseCode() == '201') {
					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'Your Palace Project hold was placed successfully.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold Placed Successfully',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your Palace Project hold was placed successfully.',
						'isPublicFacing' => true,
					]);

					$this->incrementStat('numHoldsPlaced');
					$this->trackUserUsageOfPalaceProject($patron);
					$this->trackRecordCheckout($recordId);
					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$patron->clearCachedAccountSummaryForSource('palace_project');
					$patron->forceReloadOfHolds();
				}else{
					$result['message'] = translate([
						'text' => 'Sorry, we could not place this hold.',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Unable to place hold',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Sorry, we could not place this hold.',
						'isPublicFacing' => true,
					]);
					if (!empty($jsonResponse->detail)) {
						$result['message'] .= '<br/>' . translate([
								'text' => $jsonResponse->detail,
								'isPublicFacing' => true,
							]);
						$result['api']['message'] .= "\n" . translate([
								'text' => $jsonResponse->detail,
								'isPublicFacing' => true,
							]);
					}
				}
			} else {
				global $logger;
				$logger->log('Error placing hold, no response from Palace Project', Logger::LOG_ERROR);
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

		$holds = $patron->getHolds(false,'palace_project');
		$foundHold = false;
		foreach ($holds as $section) {
			/** @var Hold $hold */
			foreach ($section as $hold) {
				if ($hold->recordId == $recordId) {
					$foundHold = true;
					break;
				}
			}
			if ($foundHold) {
				break;
			}
		}

		if ($foundHold) {
			$cancelHoldUrl = $hold->cancellationUrl;

			$headers = $this->getPalaceProjectHeaders($patron);

			$this->initCurlWrapper();
			$this->curlWrapper->addCustomHeaders($headers, true);
			$response = $this->curlWrapper->curlGetPage($cancelHoldUrl);
			ExternalRequestLogEntry::logRequest('palaceProject.cancelHold', 'POST', $cancelHoldUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
			$cancelWorked = false;
			if ($response != false) {
				if ($this->curlWrapper->getResponseCode() == 200) {
					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'Your Palace Project hold was cancelled successfully',
						'isPublicFacing' => true,
					]);

					// Result for API or app use
					$result['api']['title'] = translate([
						'text' => 'Hold cancelled',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your Palace Project hold was cancelled successfully',
						'isPublicFacing' => true,
					]);

					$this->incrementStat('numHoldsCancelled');
					$patron->clearCachedAccountSummaryForSource('palace_project');
					$patron->forceReloadOfHolds();
					$cancelWorked = true;
				}
			}
			if (!$cancelWorked) {
				$result['message'] = translate([
					'text' => "Could not cancel Palace Project hold, " . (string)$status->statusMessage,
					'isPublicFacing' => true,
				]);

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to cancel hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Could not cancel Palace Project hold, ' . (string)$status->statusMessage,
					'isPublicFacing' => true,
				]);

				$this->incrementStat('numApiErrors');
			}
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
			$settings = $this->getActiveSettings();
			$homeLibrary = $patron->getHomeLibrary();
			if (!empty($homeLibrary)) {
				$homePalaceProjectLibraryId = $homeLibrary->palaceProjectLibraryId;
				if (!empty($homePalaceProjectLibraryId)) {
					$borrowLink = preg_replace(
						'~/[^/]+/works/~',
					'/' . $homePalaceProjectLibraryId . '/works/',
						$borrowLink
					);
				}
			}

			$headers = $this->getPalaceProjectHeaders($patron);
			$this->initCurlWrapper();
			$this->curlWrapper->addCustomHeaders($headers, true);
			$response = $this->curlWrapper->curlGetPage($borrowLink);
			ExternalRequestLogEntry::logRequest('palaceProject.checkoutTitle', 'POST', $borrowLink, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
			if ($response != false) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse == false) {
					$xmlResponse = simplexml_load_string($response);
				}
				if ($this->curlWrapper->getResponseCode() == '200' || $this->curlWrapper->getResponseCode() == '201') {
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
						'text' => 'Your Palace Project title was checked out successfully. Use the Palace Project app to read/listen to the title.',
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
					if (!empty($jsonResponse->detail)) {
						$result['message'] .= '<br/>' . translate([
								'text' => $jsonResponse->detail,
								'isPublicFacing' => true,
							]);
						$result['api']['message'] .= "\n" . translate([
								'text' => $jsonResponse->detail,
								'isPublicFacing' => true,
							]);
					}
				}
			} else {
				global $logger;
				$logger->log('Error checking out title, no response from Palace Project', Logger::LOG_ERROR);
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

	private $_activeSettings = false;
	public function getActiveSettings() : PalaceProjectSetting|false {
		if ($this->_activeSettings !== null && !is_object($this->_activeSettings)) {
			if (UserAccount::isLoggedIn()) {
				$this->_activeSettings = $this->getSettings(UserAccount::getActiveUserObj());
			} else {
				$this->_activeSettings = $this->getSettings(null);
			}
		}
		return $this->_activeSettings;
	}

	private $_activeCollections = null;
	function getActiveCollectionIds() : array {
		if ($this->_activeCollections === null) {
			$settings = $this->getActiveSettings();
			if ($settings != false) {
				$collectionsForSettings = $settings->collections;
				$this->_activeCollections = array_keys($collectionsForSettings);
			}else{
				$this->_activeCollections = [];
			}
		}
		return $this->_activeCollections;
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

		$userPalaceProjectTracking = $user->userCookiePreferenceLocalAnalytics;
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');
		global $aspenUsage;
		global $library;
		$userUsage->instance = $aspenUsage->getInstance();

		if ($userPalaceProjectTracking) {
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

	private function getPalaceProjectHeaders(User $patron) {
		global $interface;
		if ($interface != null) {
			$aspenVersion = $interface->getVariable('aspenVersion');
			if (substr($aspenVersion, -1) == "\n") {
				$aspenVersion = substr($aspenVersion, 0, -1);
			}
		} else {
			$aspenVersion = 'Primary';
		}
		return [
			'Authorization: Basic ' . base64_encode("$patron->ils_barcode:$patron->ils_password"),
			'Accept: application/opds+json',
			'User-Agent: Aspen Discovery ' . $aspenVersion,
		];
	}

	public function getUsageInstructions() {
		$settings = $this->getSettings();
		if ($settings == false) {
			return false;
		}else{
			global $activeLanguage;
			return $settings->getTextBlockTranslation('instructionsForUsage', $activeLanguage->code, true);
		}
	}
}