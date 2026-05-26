<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class PalaceProjectDriver extends AbstractEContentDriver {
	/** @var ?CurlWrapper */
	private ?CurlWrapper $curlWrapper;

	public function initCurlWrapper() : void {
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 20;
	}

	public function hasNativeReadingHistory(): bool {
		return false;
	}

	private array $checkouts = [];

	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron       The user to load transactions for
	 * @param array $options     Additional options
	 * @return Checkout[]        Array of the patron's transactions on success
	 * @access public
	 */
	public function getCheckouts(User $patron, array $options = []): array {
		$this->loadCirculationInformation($patron);

		return $this->checkouts[$patron->id];
	}

	public function loadCirculationInformation(User $patron) : void {
		$accountSummary = $patron->getCachedAccountSummary('palace_project');
		$cachedHolds = $patron->getCachedHoldsForSource('palace_project');
		$cachedCheckouts = $patron->getCachedCheckoutsForSource('palace_project');

		if ($accountSummary->areHoldsStale() || $accountSummary->areCheckoutsStale() || isset($_REQUEST['reload']) || isset($_REQUEST['refreshHolds']) || isset($_REQUEST['refreshCheckouts'])) {
			require_once ROOT_DIR . '/sys/User/Checkout.php';
			require_once ROOT_DIR . '/sys/User/Hold.php';
			$checkouts = [];
			$holds = [
				'available' => [],
				'unavailable' => [],
			];

			$settings = $this->getSettings($patron);
			if ($settings !== false) {
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
				if ($response !== false) {
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
									} else if ($link->properties->availability->state == 'ready') {
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
								$checkout->canRenew = false;
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
								} else {
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
									} else if ($link->rel == 'http://opds-spec.org/acquisition') {
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
							} else {
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
								} else {
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
									} elseif ($link->rel == 'http://librarysimplified.org/terms/rel/revoke') {
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
					} else {
						global $logger;
						$logger->log('Error loading circulation information, bad response from Palace Project', Logger::LOG_ERROR);
						$this->incrementStat('numApiErrors');
					}
				} else {
					global $logger;
					$logger->log('Error loading circulation information, no response from Palace Project', Logger::LOG_ERROR);
					$this->incrementStat('numApiErrors');
				}
			}

			$this->checkouts[$patron->id] = $checkouts;
			$this->holds[$patron->id] = $holds;

			$this->updateCachedHoldsBasedOnActiveHolds($cachedHolds, $holds, $accountSummary);
			$this->updateCachedCheckoutsBasedOnActiveCheckouts($cachedCheckouts, $checkouts, $accountSummary);
		}else{
			$this->checkouts[$patron->id] = $cachedCheckouts;
			$this->holds[$patron->id] = $cachedHolds;
		}
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll(): bool {
		return false;
	}

	/**
	 * Renew all titles currently checked out to the user
	 */
	public function renewAll(User $patron) : array {
		return [
			'success' => 'false',
			'message' => 'Renew All not implemented for PalaceProjectDriver, renew one at a time'
		];
	}

	function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array {
		return $this->checkOutTitle($patron, $recordId, true);
	}

	/**
	 * Return a title currently checked out to the user
	 *
	 * @param $patron User
	 * @param $recordId   string
	 * @return array
	 */
	public function returnCheckout(User $patron, string $recordId) : array {
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
				if ($response !== false) {
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
						$accountSummary = $patron->getCachedAccountSummary('palace_project');
						$accountSummary->incrementNumberOfCheckouts();
						$accountSummary->markCheckoutsStale();
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

	private array $holds = [];

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
	public function getHolds(User $patron, bool $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$this->loadCirculationInformation($patron);

		return $this->holds[$patron->id];
	}

	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) : array {
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
			if ($response !== false) {
				$jsonResponse = json_decode($response);
				if ($this->curlWrapper->getResponseCode() == '200' || $this->curlWrapper->getResponseCode() == '201') {
					//Check to see if the title was held or borrowed
					$wasCheckedOut = false;
					foreach ($jsonResponse->links as $link) {
						if (str_starts_with($link->rel, 'http://opds-spec.org/acquisition')) {
							$state = $link->properties->availability->state;
							$wasCheckedOut = $state == 'ready';
						}
					}

					$result['success'] = true;
					if ($wasCheckedOut) {
						$result['title'] = translate([
							'text' => "Title Checked Out",
							'isPublicFacing' => true,
						]);
						$result['message'] = translate([
							'text' => 'Your Palace Project title was available already. You may now download the title from your Account.',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => 'Your Palace Project title was available already. Use the Palace Project app to read/listen to the title.',
							'isPublicFacing' => true,
						]);
						$result['api']['title'] = translate([
							'text' => 'Checked out title',
							'isPublicFacing' => true,
						]);
						$result['api']['action'] = translate([
							'text' => 'Go to Checkouts',
							'isPublicFacing' => true,
						]);
						$result['checkedOut'] = true;
						$this->incrementStat('numCheckouts');
						$this->trackRecordCheckout($recordId);
					}else{
						$result['title'] = translate([
							'text' => "Hold Placed",
							'isPublicFacing' => true,
						]);
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
						$result['api']['action'] = translate([
							'text' => 'Go to Holds',
							'isPublicFacing' => true,
						]);
						$result['checkedOut'] = false;
						$this->incrementStat('numHoldsPlaced');
						$this->trackRecordHold($recordId);
					}

					$this->trackUserUsageOfPalaceProject($patron);
					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$accountSummary = $patron->getCachedAccountSummary('palace_project');
					if ($wasCheckedOut) {
						$accountSummary->incrementNumberOfCheckouts();
						$accountSummary->markCheckoutsStale();
					}else{
						$accountSummary->incrementNumberOfUnavailableHolds();
						$accountSummary->markHoldsStale();
					}
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

	function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error',
				'isPublicFacing' => true,
			]),
		];

		$holds = $patron->getHolds(false,'palace_project');
		$hold = $this->getHoldByCancelId($holds, $recordId, $cancelId);

		if ($hold != null) {
			$cancelHoldUrl = $hold->cancellationUrl;

			$headers = $this->getPalaceProjectHeaders($patron);

			$this->initCurlWrapper();
			$this->curlWrapper->addCustomHeaders($headers, true);
			$response = $this->curlWrapper->curlGetPage($cancelHoldUrl);
			ExternalRequestLogEntry::logRequest('palaceProject.cancelHold', 'POST', $cancelHoldUrl, $this->curlWrapper->getHeaders(), false, $this->curlWrapper->getResponseCode(), $response, []);
			$cancelWorked = false;
			if ($response !== false) {
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
					$this->updateCachesForCancelledHold($patron, $hold, 'palace_project');
					$cancelWorked = true;
				}
			}
			if (!$cancelWorked) {
				$result['message'] = translate([
					'text' => 'Could not cancel Palace Project hold.',
					'isPublicFacing' => true,
				]);

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to cancel hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Could not cancel Palace Project hold.',
					'isPublicFacing' => true,
				]);

				$this->incrementStat('numApiErrors');
			}
		}

		return $result;
	}

	public function getAccountSummary(User $user): AccountSummary {
		$summary = $user->getCachedAccountSummary('palace_project');

		if ($summary->dataIsStale || isset($_REQUEST['reload'])) {
			require_once ROOT_DIR . '/sys/User/AccountSummary.php';
			$checkedOutItems = $this->getCheckouts($user);
			$summary->numCheckedOut = count($checkedOutItems);

			$holds = $this->getHolds($user, true);
			$summary->numAvailableHolds = count($holds['available']);
			$summary->numUnavailableHolds = count($holds['unavailable']);

			$summary->lastLoaded = time();
			$summary->update();
		}

		$summary->lastLoaded = time();
		$summary->update();

		return $summary;
	}

	/**
	 * @param User $patron
	 * @param string $titleId
	 *
	 * @param bool $fromRenew
	 * @return array
	 */
	public function checkOutTitle(User $patron, string $titleId, ?bool $fromRenew = false) : array {
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
			if ($response !== false) {
				$jsonResponse = json_decode($response);
				if ($this->curlWrapper->getResponseCode() == '200' || $this->curlWrapper->getResponseCode() == '201') {
					//Verify the title was borrowed. A hold may have been placed if the availability was inaccurate
					$wasCheckedOut = false;
					foreach ($jsonResponse->links as $link) {
						if (str_starts_with($link->rel, 'http://opds-spec.org/acquisition')) {
							$state = $link->properties->availability->state;
							$wasCheckedOut = $state == 'ready';
						}
					}
					$result['success'] = true;
					if ($wasCheckedOut) {
						$result['title'] = translate([
							'text' => "Title Checked Out Successfully",
							'isPublicFacing' => true,
						]);
						$result['message'] = translate([
							'text' => 'Your Palace Project title was checked out successfully. You may now download the title from your Account.',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => 'Your Palace Project title was checked out successfully. Use the Palace Project app to read/listen to the title.',
							'isPublicFacing' => true,
						]);
						$result['api']['title'] = translate([
							'text' => 'Checked out title',
							'isPublicFacing' => true,
						]);
						$result['api']['action'] = translate([
							'text' => 'Go to Checkouts',
							'isPublicFacing' => true,
						]);
						$result['checkedOut'] = true;
						$this->incrementStat('numCheckouts');
						$this->trackRecordCheckout($titleId);
					}else{
						$result['title'] = translate([
							'text' => "Hold Placed",
							'isPublicFacing' => true,
						]);
						$result['message'] = translate([
							'text' => 'Your Palace Project title is not currently available. A hold was placed for you.',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => 'Your Palace Project title is not currently available. A hold was placed for you.',
							'isPublicFacing' => true,
						]);
						$result['api']['title'] = translate([
							'text' => 'Hold Placed',
							'isPublicFacing' => true,
						]);
						$result['api']['action'] = translate([
							'text' => 'Go to Holds',
							'isPublicFacing' => true,
						]);
						$result['checkedOut'] = false;
						$this->incrementStat('numHolds');
						$this->trackRecordHold($titleId);
					}

					$this->trackUserUsageOfPalaceProject($patron);

					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$accountSummary = $patron->getCachedAccountSummary('palace_project');
					if ($wasCheckedOut) {
						$accountSummary->incrementNumberOfCheckouts();
						$accountSummary->markCheckoutsStale();
					}else{
						$accountSummary->incrementNumberOfUnavailableHolds();
						$accountSummary->markHoldsStale();
					}
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

	private null|PalaceProjectSetting|false $_activeSettings = null;
	public function getActiveSettings() : PalaceProjectSetting|false {
		if ($this->_activeSettings == null) {
			if (UserAccount::isLoggedIn()) {
				$this->_activeSettings = $this->getSettings(UserAccount::getActiveUserObj());
			} else {
				$this->_activeSettings = $this->getSettings();
			}
		}
		return $this->_activeSettings;
	}

	private array|null $_activeCollections = null;
	function getActiveCollectionIds() : array {
		if ($this->_activeCollections === null) {
			$settings = $this->getActiveSettings();
			if ($settings !== false) {
				$collectionsForSettings = $settings->getCollections();
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
	private function getSettings(User $user = null) : false|PalaceProjectSetting {
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
	 * @param User $user
	 */
	public function trackUserUsageOfPalaceProject(User $user): void {
		require_once ROOT_DIR . '/sys/PalaceProject/UserPalaceProjectUsage.php';
		$userUsage = new UserPalaceProjectUsage();

		$userPalaceProjectTracking = $user->userCookiePreferenceLocalAnalytics || !$user->getHomeLibrary()->cookieStorageConsent;
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');
		global $aspenUsage;
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
	 * @param string|int $recordId
	 */
	function trackRecordCheckout(string|int $recordId): void {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
		$product = new PalaceProjectTitle();
		if (is_numeric($recordId)) {
			$product->id = $recordId;
		} else {
			$product->palaceProjectId = $recordId;
		}
		if ($product->find(true)) {
			require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectRecordUsage.php';
			$recordUsage = new PalaceProjectRecordUsage();
			$recordUsage->palaceProjectId = $product->id;
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
	 * @param string|int $recordId
	 */
	function trackRecordHold(string|int $recordId): void {
		require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
		$product = new PalaceProjectTitle();
		if (is_numeric($recordId)) {
			$product->id = $recordId;
		} else {
			$product->palaceProjectId = $recordId;
		}
		if ($product->find(true)) {
			require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectRecordUsage.php';
			$recordUsage = new PalaceProjectRecordUsage();
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->palaceProjectId = $product->id;
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

	private function incrementStat(string $fieldName) : void {
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

	private function getPalaceProjectHeaders(User $patron) : array {
		global $interface;
		if ($interface != null) {
			$aspenVersion = $interface->getVariable('aspenVersion');
			if (str_ends_with($aspenVersion, "\n")) {
				$aspenVersion = substr($aspenVersion, 0, -1);
			}
		} else {
			$aspenVersion = 'Primary';
		}
		$settings = $this->getSettings();
		if ($settings->requirePin) {
			$authorization = base64_encode("$patron->ils_barcode:$patron->ils_password");
		}else{
			$authorization = base64_encode("$patron->ils_barcode");
		}
		return [
			'Authorization: Basic ' . $authorization,
			'Accept: application/opds+json',
			'User-Agent: Aspen Discovery ' . $aspenVersion,
		];
	}

	public function getUsageInstructions() : false|string {
		$settings = $this->getSettings();
		if ($settings === false) {
			return false;
		}else{
			global $activeLanguage;
			return $settings->getTextBlockTranslation('instructionsForUsage', $activeLanguage->code);
		}
	}
}
