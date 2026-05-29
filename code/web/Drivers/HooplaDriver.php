<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class HooplaDriver extends AbstractEContentDriver {
	const memCacheKey = 'hoopla_api_access_token';
	/** @var HooplaSetting|null */
	private $hooplaSettings = null;
	public $hooplaAPIBaseURL = 'hoopla-api-dev.hoopladigital.com';
	private $accessToken;
	private $hooplaEnabled = false;
	private ?bool $isHooplaVersion2 = null;
	// Legacy Hoopla v1 settings
	private $hooplaInstantEnabled = false;
	private $hooplaFlexEnabled = false;

	public function __construct() {
		require_once ROOT_DIR . '/sys/Hoopla/HooplaSetting.php';
		try {
			$hooplaSettings = new HooplaSetting();
			if ($hooplaSettings->find(true)) {
				$this->hooplaEnabled = true;
				$this->hooplaAPIBaseURL = $hooplaSettings->apiUrl;
				if (!$this->isHooplaVersion2()) {
					$this->hooplaInstantEnabled = $hooplaSettings->hooplaInstantEnabled;
					$this->hooplaFlexEnabled = $hooplaSettings->hooplaFlexEnabled;
				}
				$this->hooplaSettings = $hooplaSettings;
				$this->getAccessToken();
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Could not load Hoopla settings", Logger::LOG_ALERT);
		}
	}

	/**
	 * Clean an assumed Hoopla RecordID to Hoopla ID number
	 * @param $hooplaRecordId
	 * @return string
	 */
	public static function recordIDtoHooplaID($hooplaRecordId) {
		if (strpos($hooplaRecordId, ':') !== false) {
			[
				,
				$hooplaRecordId,
			] = explode(':', $hooplaRecordId, 2);
		}
		return preg_replace('/^MWT/', '', $hooplaRecordId);
	}


	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	private function getAPIResponse($requestType, $url, $params = null, $customRequest = null, $additionalHeaders = null, $dataToSanitize = []) {
		global $logger;
		$logger->log('Hoopla API URL :' . $url, Logger::LOG_NOTICE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->accessToken,
			'Originating-App-Id: Aspen Discovery',
		];
		if (!empty($additionalHeaders) && is_array($additionalHeaders)) {
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (empty($customRequest)) {
			$customRequest = 'GET';
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($customRequest == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
		}

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			// For local debugging only
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		}
		if ($params !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		ExternalRequestLogEntry::logRequest($requestType, $customRequest, $url, $headers, '', curl_getinfo($ch, CURLINFO_HTTP_CODE), $json, $dataToSanitize);

		if (!$json && $httpCode == 401) {
			$logger->log('401 Response in getAPIResponse. Attempting to renew access token', Logger::LOG_WARNING);
			$this->renewAccessToken();
			return [
				'body' => false,
				'httpCode' => $httpCode,
			];
		}

		$logger->log("Hoopla API response\r\n$json", Logger::LOG_DEBUG);
		curl_close($ch);

		if ($json !== false && $json !== 'false') {
			return [
				'body' => json_decode($json),
				'httpCode' => $httpCode,
			];
		} else {
			$logger->log('Curl problem in getAPIResponse', Logger::LOG_WARNING);
			return [
				'body' => false,
				'httpCode' => $httpCode,
			];
		}
	}

	/**
	 * Simplified CURL call for returning a title. Success is determined by receiving a http status code of 204
	 * @param $url
	 * @return bool
	 */
	private function getAPIResponseReturnHooplaTitle($url) {
		$ch = curl_init();
		$headers = [
			'Authorization: Bearer ' . $this->accessToken,
			'Originating-App-Id: Aspen Discovery',
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			// For local debugging only
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		ExternalRequestLogEntry::logRequest('hoopla.returnCheckout', 'DELETE', $url, $headers, '', $http_code, $response, []);

		curl_close($ch);
		return $http_code == 204;
	}


	private static $hooplaLibraryIdsForUser;

	/**
	 * @param User $user
	 *
	 * @return false|int
	 */
	public function getHooplaLibraryID($user) {
		if ($this->hooplaEnabled) {
			if (isset(self::$hooplaLibraryIdsForUser[$user->id])) {
				return self::$hooplaLibraryIdsForUser[$user->id]['libraryId'];
			} else {
				$library = $user->getHomeLibrary();
				$hooplaID = $library->getHooplaLibraryID();
				self::$hooplaLibraryIdsForUser[$user->id]['libraryId'] = $hooplaID;
				return $hooplaID;
			}
		}
		return false;
	}

	/**
	 * @param User $user
	 *
	 * @return null|string
	 */
	private function getHooplaBasePatronURL($user) {
		$url = null;
		if ($this->hooplaEnabled) {
			$hooplaLibraryID = $this->getHooplaLibraryID($user);
			$barcode = $user->getBarcode();
			if (!empty($hooplaLibraryID) && !empty($barcode)) {
				$url = $this->hooplaAPIBaseURL . '/api/v1/libraries/' . $hooplaLibraryID . '/patrons/' . $barcode;
			}
		}
		return $url;
	}

	private $hooplaPatronStatuses = [];

	/**
	 * @param $user User
	 *
	 * @return AccountSummary
	 */
	public function getAccountSummary(User $user): AccountSummary {
		$summary = $user->getCachedAccountSummary('hoopla');

		if ($summary->dataIsStale || isset($_REQUEST['reload'])) {
			$patronURL = $this->getHooplaBasePatronURL($user);
			if (!empty($patronURL)) {
				// Get Patron Status (only has checkouts in status call)
				$getPatronStatusURL = $patronURL . '/status';
				$hooplaPatronStatusResponse = $this->getAPIResponse('hoopla.getAccountSummary', $getPatronStatusURL);
				if ($hooplaPatronStatusResponse['httpCode'] == 200 && !empty($hooplaPatronStatusResponse['body'])) {
					$this->hooplaPatronStatuses[$user->id] = $hooplaPatronStatusResponse['body'];

					$summary->numCheckedOut = $hooplaPatronStatusResponse['body']->currentlyBorrowed;
					$summary->numCheckoutsRemaining = $hooplaPatronStatusResponse['body']->borrowsRemaining;
				} else {
					global $logger;
					$hooplaErrorMessage = empty($hooplaPatronStatusResponse['body']->message) ? '' : ' Hoopla Message :' . $hooplaPatronStatusResponse['body']->message;
					$logger->log('Error retrieving patron status from Hoopla. User ID : ' . $user->id . $hooplaErrorMessage, Logger::LOG_NOTICE);
					$this->hooplaPatronStatuses[$user->id] = false; // Don't do status call again for this user
				}
				// Get Holds status for only the patrons can access to Hoopla Flex
				if ($user->isValidForEContentSource('hoopla_flex')) {
					$holdsUrl = $patronURL . '/holds/current';
					$holdsResponse = $this->getAPIResponse('hoopla.getHoldsCount', $holdsUrl);
					if ($holdsResponse['httpCode'] == 200 && !empty($holdsResponse['body'])) {
						$availableHolds = 0;
						$unavailableHolds = 0;

						foreach ($holdsResponse['body'] as $hold) {
							if ($hold->status === 'RESERVED') {
								$availableHolds++;
							} else if ($hold->status === 'WAITING') {
								$unavailableHolds++;
							}
						}

						$summary->numAvailableHolds = $availableHolds;
						$summary->numUnavailableHolds = $unavailableHolds;
					} else {
						global $logger;
						$errorMessage = empty($holdsResponse['body']->message) ? '' : ' Hoopla Message: ' . $holdsResponse['body']->message;
						$logger->log('Error retrieving holds from Hoopla. User ID: ' . $user->id . $errorMessage, Logger::LOG_NOTICE);
					}
				} else {
					$summary->numAvailableHolds = 0;
					$summary->numUnavailableHolds = 0;
				}
			}

			$summary->lastLoaded = time();
			$summary->update();
		}
		return $summary;
	}

	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron       The user to load transactions for
	 * @param array $options     Additional options
	 * @return Checkout[]
	 */
	public function getCheckouts(User $patron, array $options = []): array {
		$accountSummary = $patron->getCachedAccountSummary('hoopla');
		$cachedCheckouts = $patron->getCachedCheckoutsForSource('hoopla');
		if ($accountSummary->areCheckoutsStale() || isset($_REQUEST['reload']) || isset($_REQUEST['refreshCheckouts'])) {
			require_once ROOT_DIR . '/sys/User/Checkout.php';
			$checkouts = [];
			if ($this->hooplaEnabled) {
				$hooplaCheckedOutTitlesURL = $this->getHooplaBasePatronURL($patron);
				if (!empty($hooplaCheckedOutTitlesURL)) {
					$hooplaCheckedOutTitlesURL .= '/checkouts/current';
					$checkOutsResponse = $this->getAPIResponse('hoopla.getCheckouts', $hooplaCheckedOutTitlesURL);
					if ($checkOutsResponse['httpCode'] == 200 && !empty($checkOutsResponse['body'])) {
						$hooplaPatronStatus = null;
						foreach ($checkOutsResponse['body'] as $checkOut) {
							$hooplaRecordID = $checkOut->contentId;
							$currentTitle = new Checkout();
							$currentTitle->type = 'hoopla';
							$currentTitle->source = 'hoopla';
							$currentTitle->userId = $patron->id;
							$currentTitle->sourceId = $checkOut->contentId;
							$currentTitle->recordId = $checkOut->contentId;
							$currentTitle->title = $checkOut->title;
							if (isset($checkOut->author)) {
								$currentTitle->author = $checkOut->author;
							}
							$currentTitle->format = $checkOut->kind;
							$currentTitle->checkoutDate = $checkOut->borrowed;
							$currentTitle->dueDate = $checkOut->due;
							$currentTitle->accessOnlineUrl = $checkOut->url;

							require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
							$hooplaRecordDriver = new HooplaRecordDriver($hooplaRecordID);
							if ($hooplaRecordDriver->isValid()) {
								// Get Record For other details
								$currentTitle->groupedWorkId = $hooplaRecordDriver->getGroupedWorkId();
								$currentTitle->author = $hooplaRecordDriver->getPrimaryAuthor();
								$currentTitle->format = $hooplaRecordDriver->getPrimaryFormat();
							}
							$key = $currentTitle->source . $currentTitle->sourceId . $currentTitle->userId; // This matches the key naming scheme in the Overdrive Driver
							$checkouts[$key] = $currentTitle;
						}
					} else {
						global $logger;
						$logger->log('Error retrieving checkouts from Hoopla.', Logger::LOG_ERROR);
					}
				}
			}
			$cachedCheckouts = $this->updateCachedCheckoutsBasedOnActiveCheckouts($cachedCheckouts, $checkouts, $accountSummary);
		}
		return $cachedCheckouts;
	}

	/**
	 * @return string
	 */
	private function getAccessToken() {
		if (empty($this->accessToken)) {
			/** @var Memcache $memCache */ global $memCache;
			$accessToken = $memCache->get(self::memCacheKey);
			if (empty($accessToken)) {
				$tokenInDB = $this->hooplaSettings->accessToken;
				$tokenExpirationTimeInDB = $this->hooplaSettings->tokenExpirationTime;
				if (!empty($tokenInDB) && !empty($tokenExpirationTimeInDB) && $tokenExpirationTimeInDB > (time())) {
					$this->accessToken = $tokenInDB;
				} else {
					$this->renewAccessToken();
				}
			} else {
				$this->accessToken = $accessToken;
			}
		}
		return $this->accessToken;
	}

	private function renewAccessToken() {
		if ($this->hooplaEnabled) {
			$url = 'https://' . str_replace([
					'http://',
					'https://',
				], '', $this->hooplaAPIBaseURL) . '/v2/token';
			// Ensure https is used

			$username = $this->hooplaSettings->apiUsername;
			$password = $this->hooplaSettings->apiPassword;

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, []);

			global $instanceName;
			if (stripos($instanceName, 'localhost') !== false) {
				// For local debugging only
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			}
			$response = curl_exec($curl);
			ExternalRequestLogEntry::logRequest('hoopla.renewAccessToken', 'POST', $url, [], '', curl_getinfo($curl, CURLINFO_HTTP_CODE), $response, []);

			curl_close($curl);

			if ($response) {
				$json = json_decode($response);
				if (!empty($json->access_token)) {
					$this->accessToken = $json->access_token;
					$this->hooplaSettings->accessToken = $json->access_token;
					$this->hooplaSettings->tokenExpirationTime = time() + $json->expires_in; 
					$this->hooplaSettings->update();

					/** @var Memcache $memCache */ global $memCache;
					global $configArray;
					$memCache->set(self::memCacheKey, $this->accessToken, $configArray['Caching']['hoopla_api_access_token']);

					return true;

				} else {
					global $logger;
					$logger->log('Hoopla API retrieve access token call did not contain an access token', Logger::LOG_ERROR);
				}
			} else {
				global $logger;
				$logger->log('Curl Error in Hoopla API call to retrieve access token', Logger::LOG_ERROR);
			}
		} else {
			global $logger;
			$logger->log('Hoopla API user and/or password not set. Can not retrieve access token', Logger::LOG_ERROR);
		}
		return false;
	}

	/**
	 * Get the type (Instant or Flex) of a Hoopla title
	 * @param string $titleId
	 * @return string 'Instant' or 'Flex'
	 */
	private function getHooplaType($titleId, $libraryId): string {
		if ($this->isHooplaVersion2()) {
			require_once ROOT_DIR . '/sys/Hoopla/HooplaEntitlement.php';
			require_once ROOT_DIR . '/sys/Hoopla/HooplaEntitlementScope.php';

			$hooplaEntitlement = new HooplaEntitlement();
			$hooplaEntitlement->hooplaId = $titleId;
			$hooplaEntitlement->joinAdd(new HooplaEntitlementScope(), 'INNER', 'hes', 'id','entitlementId');
			$hooplaEntitlement->whereAdd('hes.scopeLibraryId = ' . (int)$libraryId);
			if ($hooplaEntitlement->find(true)) {
				return $hooplaEntitlement->hooplaType;
			}
		} else {
			require_once ROOT_DIR . '/sys/Hoopla/HooplaExtract.php';
			$hooplaItem = new HooplaExtract();
			$hooplaItem->hooplaId = $titleId;
			if ($hooplaItem->find(true)) {
				if (!empty($hooplaItem->hooplaType)) {
					return $hooplaItem->hooplaType;
				}
			}
		}
		return 'Instant';
	}

	public function getHoldQueueSize($titleId, $libraryId) {
		require_once ROOT_DIR . '/sys/Hoopla/HooplaFlexAvailability.php';
		$flexAvailability = new HooplaFlexAvailability();
		$flexAvailability->hooplaId = $titleId;
		if ($this->isHooplaVersion2()) {
			$flexAvailability->scopeLibraryId = $libraryId;
		}
		if ($flexAvailability->find(true)) {
			return $flexAvailability->holdsQueueSize;
		}
		return 0;
	}

	/**
	 * @param User $patron
	 * @param string $titleId
	 * @return array
	 */
	public function checkOutTitle(User $patron, string $titleId) : array {
		if ($this->hooplaEnabled) {
			$checkoutURL = $this->getHooplaBasePatronURL($patron);
			if (!empty($checkoutURL)) {

				$titleId = self::recordIDtoHooplaID($titleId);
				$scopeLibraryId = $patron->getHomeLibrary()->libraryId;
				$hooplaType = $this->getHooplaType($titleId, $scopeLibraryId);
				if ($hooplaType == 'Flex' && !$patron->isValidForEContentSource('hoopla_flex')) {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Unable to checkout title',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Hoopla Flex is not enabled for this library.',
							'isPublicFacing' => true,
						]),
						'api' => [
							'title' => "Unable to checkout title",
							'message' => "Hoopla Flex is not enabled for this library.",
						]
					];
				}
				$checkoutURL .= '/' . $titleId;
				$checkoutResponse = $this->getAPIResponse('hoopla.checkoutTitle', $checkoutURL, [], 'POST');
				if ($checkoutResponse) {
					if ($checkoutResponse['httpCode'] == 200) {
						$this->trackUserUsageOfHoopla($patron);
						$this->trackRecordCheckout($titleId);
						$accountSummary = $patron->getCachedAccountSummary('hoopla');
						$accountSummary->incrementNumberOfCheckouts();
						$accountSummary->markCheckoutsStale();

						$dueDate = date('l, F j', $checkoutResponse['body']->due);

						// Result for API or app use
						$apiResult = [
							'title' => translate([
								'text' => 'Checked out title',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'You can now enjoy this title through %1%. You can stream it to your browser, or download it for offline viewing using our mobile apps.',
								1 => $dueDate,
								'isPublicFacing' => true,
							]),
							'action' => translate([
								'text' => 'Go to Checkouts',
								'isPublicFacing' => true,
							])
						];

						//Prepare message for translation
						return [
							'success' => true,
							'message' => translate([
								'text' => 'You can now enjoy this title through %1%. You can stream it to your browser, or download it for offline viewing using our mobile apps.',
								1 => $dueDate,
								'isPublicFacing' => true,
							]),
							'title' => translate([
								'text' => $checkoutResponse['body']->title,
								'isPublicFacing' => true,
							]),
							'HooplaURL' => $checkoutResponse['body']->url,
							'due' => $checkoutResponse['body']->due,
							'api' => $apiResult,
						];
					} else if ($checkoutResponse['httpCode'] == 404) {
						// prompt user to register at hoopla
						$registrationUrl = 'https://www.hoopladigital.com/register';
						return [
							'success' => false,
							'needToRegister' => true,
							'title' => translate([
								'text' => 'Registration Required',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'We are unable to find a Hoopla digital account for your library card. Please register to continue.',
								'isPublicFacing' => true,
							]),
							'buttons' => '<a class="btn btn-primary" href="' . $registrationUrl . '" target="_blank">' . translate(['text' => 'Register at Hoopla', 'isPublicFacing' => true]) . '</a>',
							'api' => [
								'title' => translate([
									'text' => 'Unable to checkout title',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'We are unable to find a Hoopla digital account for your library card. Please register at %1%',
									1 => $registrationUrl,
									'isPublicFacing' => true,
								])
							]
						];
					} else if ($checkoutResponse['httpCode'] == 422 && $hooplaType == 'Flex') {
						// prompt user to place a hold for Flex titles
						return [
							'success' => false,
							'noCopies' => true,
							'title' => translate([
								'text' => 'Title Not Available',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'No copies available right now, would you like to place a hold instead?',
								'isPublicFacing' => true,
							]),
							'buttons' => '<button class="btn btn-primary" onclick="AspenDiscovery.Hoopla.doHold(\'' . $patron->id . '\', \'' . $titleId . '\')">' . translate(['text' => 'Place Hold', 'isPublicFacing' => true]) . '</button> ',
							'api' => [
								'title' => translate([
									'text' => 'Unable to checkout title',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Title currently unavailable, please try again after 5 minutes',
									'isPublicFacing' => true,
								]),
							]
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Checkout Unavailable',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => $checkoutResponse['body']->detail ?? $checkoutResponse['body']->message ?? 'Title currently unavailable',
								'isPublicFacing' => true,
							]),
							'api' => [
								'title' => translate([
									'text' => 'Checkout Unavailable',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => $checkoutResponse['body']->detail ?? $checkoutResponse['body']->message ?? 'Title currently unavailable',
									'isPublicFacing' => true,
								]),
							]
						];
					}
				} else {
					// Result for API or app use
					$apiResult = [];
					$apiResult['title'] = translate([
						'text' => 'Unable to checkout title',
						'isPublicFacing' => true,
					]);
					$apiResult['message'] = translate([
						'text' => 'An error occurred checking out the Hoopla title.',
						'isPublicFacing' => true,
					]);

					return [
						'success' => false,
						'message' => 'An error occurred checking out the Hoopla title.',
						'api' => $apiResult,
					];
				}
			} elseif (!$this->getHooplaLibraryID($patron)) {
				// Result for API or app use
				$apiResult = [];
				$apiResult['title'] = translate([
					'text' => 'Unable to checkout title',
					'isPublicFacing' => true,
				]);
				$apiResult['message'] = translate([
					'text' => 'Your library does not have Hoopla integration enabled.',
					'isPublicFacing' => true,
				]);

				return [
					'success' => false,
					'message' => 'Your library does not have Hoopla integration enabled.',
					'api' => $apiResult,
				];
			} else {
				// Result for API or app use
				$apiResult = [];
				$apiResult['title'] = translate([
					'text' => 'Unable to checkout title',
					'isPublicFacing' => true,
				]);
				$apiResult['message'] = translate([
					'text' => 'There was an error retrieving your library card number.',
					'isPublicFacing' => true,
				]);

				return [
					'success' => false,
					'message' => 'There was an error retrieving your library card number.',
					'api' => $apiResult,
				];
			}
		} else {
			// Result for API or app use
			$apiResult = [];
			$apiResult['title'] = translate([
				'text' => 'Unable to checkout title',
				'isPublicFacing' => true,
			]);
			$apiResult['message'] = translate([
				'text' => 'Hoopla integration is not enabled.',
				'isPublicFacing' => true,
			]);

			return [
				'success' => false,
				'message' => 'Hoopla integration is not enabled.',
				'api' => $apiResult,
			];
		}

		//Default return
		return [
			'success' => false,
			'message' => translate(['text' => 'Unknown error checking out title', 'isPublicFacing' => true]),
			'api' => [
				'title' => translate(['text' => 'Unable to checkout title', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'Unknown error checking out title', 'isPublicFacing' => true])
			]
		];
	}

	/**
	 * @param string $hooplaId
	 * @param User $patron
	 *
	 * @return array
	 */
	public function returnCheckout(User $patron, string $hooplaId) : array {
		$apiResult = [];
		if ($this->hooplaEnabled) {
			$returnCheckoutURL = $this->getHooplaBasePatronURL($patron);
			if (!empty($returnCheckoutURL)) {
				$itemId = self::recordIDtoHooplaID($hooplaId);
				$returnCheckoutURL .= "/$itemId";
				$result = $this->getAPIResponseReturnHooplaTitle($returnCheckoutURL);
				if ($result) {
					$accountSummary = $patron->getCachedAccountSummary('hoopla');
					$accountSummary->decrementNumberOfCheckouts();
					$accountSummary->markCheckoutsStale();

					// Result for API or app use
					$apiResult['title'] = translate([
						'text' => 'Title returned',
						'isPublicFacing' => true,
					]);
					$apiResult['message'] = translate([
						'text' => 'The title was successfully returned.',
						'isPublicFacing' => true,
					]);

					return [
						'success' => true,
						'message' => 'The title was successfully returned.',
						'api' => $apiResult,
					];
				} else {
					// Result for API or app use
					$apiResult['title'] = translate([
						'text' => 'Unable to return title',
						'isPublicFacing' => true,
					]);
					$apiResult['message'] = translate([
						'text' => 'There was an error returning this title, please click the refresh button to update your checkouts and try again.',
						'isPublicFacing' => true,
					]);

					return [
						'success' => false,
						'message' => 'There was an error returning this title, please click the refresh button to update your checkouts and try again.',
						'api' => $apiResult,
					];
				}

			} elseif (!$this->getHooplaLibraryID($patron)) {
				// Result for API or app use
				$apiResult['title'] = translate([
					'text' => 'Unable to return title',
					'isPublicFacing' => true,
				]);
				$apiResult['message'] = translate([
					'text' => 'Your library does not have Hoopla integration enabled.',
					'isPublicFacing' => true,
				]);

				return [
					'success' => false,
					'message' => 'Your library does not have Hoopla integration enabled.',
					'api' => $apiResult,
				];
			} else {
				// Result for API or app use
				$apiResult['title'] = translate([
					'text' => 'Unable to return title',
					'isPublicFacing' => true,
				]);
				$apiResult['message'] = translate([
					'text' => 'There was an error retrieving your library card number.',
					'isPublicFacing' => true,
				]);

				return [
					'success' => false,
					'message' => 'There was an error retrieving your library card number.',
					'api' => $apiResult,
				];
			}
		} else {
			// Result for API or app use
			$apiResult['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$apiResult['message'] = translate([
				'text' => 'Hoopla integration is not enabled.',
				'isPublicFacing' => true,
			]);

			return [
				'success' => false,
				'message' => 'Hoopla integration is not enabled.',
				'api' => $apiResult,
			];
		}
	}

	public function hasNativeReadingHistory(): bool {
		return false;
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
	public function renewAll(User $patron) : array {
		return [
			'success' => 'false',
			'message' => 'Renew All not implemented for HooplaDriver, renew one at a time'
		];
	}

	public function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array {
		return [
			'success' => 'false',
			'message' => 'Titles from Hoopla cannot be renewed'
		];
	}

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
	public function getHolds(User $patron, ?bool $forSummary = false): array {
		$accountSummary = $patron->getCachedAccountSummary('hoopla');
		$cachedHolds = $patron->getCachedHoldsForSource('hoopla');
		if ($accountSummary->areHoldsStale() || isset($_REQUEST['reload']) || isset($_REQUEST['refreshHolds'])) {
			require_once ROOT_DIR . '/sys/User/Hold.php';
			$holds = [
				'available' => [],
				'unavailable' => [],
			];
			$patronHomeLibrary = $patron->getHomeLibrary();
			$primaryHooplaSetting = $patronHomeLibrary->getPrimaryHooplaSetting();
			if ($this->isHooplaVersion2() && $primaryHooplaSetting != null) {
				$flexEnabled = $primaryHooplaSetting->hooplaFlexEnabled;
			} else {
				$flexEnabled = $this ->hooplaFlexEnabled;
			}
			if ($flexEnabled) {
				$holdUrl = $this->getHooplaBasePatronURL($patron);
				if (!empty($holdUrl)) {
					$holdUrl .= '/holds/current';
					$holdResponse = $this->getAPIResponse('hoopla.getHolds', $holdUrl);
					if ($holdResponse['httpCode'] == 200 && !empty($holdResponse['body'])) {
						foreach ($holdResponse['body'] as $holdInfo) {
							$this->loadHoldInfo($holdInfo, $holds, $patron, $forSummary);
						}
					}
				}
			}
			$cachedHolds = $this->updateCachedHoldsBasedOnActiveHolds($cachedHolds, $holds, $accountSummary);
		}

		return $cachedHolds;
	}

	private function loadHoldInfo($rawHold, array &$holds, User $user, $forSummary): Hold
	{
		$hold = new Hold();
		$hold->type = 'hoopla';
		$hold->source = 'hoopla';

		$hold->recordId = $rawHold->contentId;
		$hold->sourceId = 'hoopla:' . $rawHold->contentId;
		$hold->cancelId = $rawHold->contentId;
		$hold->title = $rawHold->title;
		$hold->position = $rawHold->holdsQueuePosition;
		$hold->createDate = $rawHold->inserted;
		if (isset($rawHold->reserveUntil)) {
			$hold->expirationDate = $rawHold->reserveUntil;
		}
		$hold->cancelable = true;
		$hold->kind = $rawHold->kind;
		$hold->userId = $user->id;
		$hold->available = $rawHold->status === 'RESERVED';

		if (!empty($rawHold->kind)) {
			$hold->format = 'Hoopla ' . ucfirst(strtolower($rawHold->kind));
		}

		require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
		$hooplaRecord = new HooplaRecordDriver($hold->recordId);
		if ($hooplaRecord->isValid()) {
			$hold->updateFromRecordDriver($hooplaRecord);
		}

		$hold->userId = $user->id;
		$key = $hold->source . $hold->recordId . $hold->userId;

		if ($hold->available) {
			$holds['available'][$key] = $hold;
		} else {
			$holds['unavailable'][$key] = $hold;
		}

		return $hold;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param null $pickupBranch For compatibility
	 * @param null $cancelDate For compatibility
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) : array {
		$result = [
			'success' => false,
			'message' => translate(['text' => 'Unknown error', 'isPublicFacing' => true])
		];

		if ($this->hooplaEnabled) {
			$holdURL = $this->getHooplaBasePatronURL($patron);
			if (!empty($holdURL)) {
				$titleId = self::recordIDtoHooplaID($recordId);
				$scopeLibraryId = $patron->getHomeLibrary()->libraryId;
				$titleType = $this->getHooplaType($titleId, $scopeLibraryId);

				// Check if Flex is enabled
				if ($titleType == 'Flex' && !$patron->isValidForEContentSource('hoopla_flex')) {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Unable to place hold',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Hoopla Flex is not enabled for this library.',
							'isPublicFacing' => true,
						]),
						'api' => [
							'title' => "Unable to place hold",
							'message' => "Hoopla Flex is not enabled for this library.",
						],
					];
				}

				$holdURL .= '/holds/' . $titleId;

				$holdResponse = $this->getAPIResponse('hoopla.placeHold', $holdURL, null, 'POST');

				if ($holdResponse && isset($holdResponse['body'])) {
					if ($holdResponse['httpCode'] == 200 && !empty($holdResponse['body'])) {
						$this->trackUserUsageOfHoopla($patron);
						$this->trackRecordHold($titleId);
						$accountSummary = $patron->getCachedAccountSummary('hoopla');
						$accountSummary->incrementNumberOfUnavailableHolds();
						$accountSummary->markHoldsStale();

						return [
							'success' => true,
							'title' => translate(['text' => 'Hold Placed Successfully', 'isPublicFacing' => true]),
							'message' => translate([
								'text' => 'Your hold for %1% was placed successfully. You are number %2% in the queue.',
								1 => $holdResponse['body']->title,
								2 => $holdResponse['body']->holdsQueuePosition,
								'isPublicFacing' => true
							]),
							'buttons' => '<a class="btn btn-primary" href="/MyAccount/Holds" role="button">' . translate(['text' => 'View My Holds', 'isPublicFacing' => true]) . '</a>',
							'api' => [
								'title' => translate(['text' => 'Hold Placed Successfully', 'isPublicFacing' => true]),
								'message' => translate(['text' => 'Your hold was placed successfully. You are number %1% in the queue.', 1 => $holdResponse['body']->holdsQueuePosition, 'isPublicFacing' => true]),
								'action' => translate(['text' => 'View My Holds', 'isPublicFacing' => true])
							]
						];
					} else if ($holdResponse['httpCode'] == 400) {
						// Prompt user to check out instead 
						return [
							'success' => false,
							'available' => true,
							'title' => translate([
								'text' => 'Title Available',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'This title is currently available. Would you like to check it out instead?',
								'isPublicFacing' => true,
							]),
							'buttons' => '<button class="btn btn-primary" onclick="AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $titleId . '\', \'' . $patron->id . '\', \'Flex\')">' .
								translate(['text' => 'Check Out', 'isPublicFacing' => true]) .
								'</button>',
							'api' => [
								'title' => translate([
									'text' => 'Unable to place hold',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Title currently available, please try again after 5 minutes',
									'isPublicFacing' => true,
								]),
							]
						];
					} else if ($holdResponse['httpCode'] == 403) {
						// Prompt user to register at hoopla
						$registrationUrl = 'https://www.hoopladigital.com/register';
						return [
							'success' => false,
							'needToRegister' => true,
							'title' => translate([
								'text' => 'Registration Required',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'We are unable to find a Hoopla digital account for your library card. Please register to continue.',
								'isPublicFacing' => true,
							]),
							'buttons' => '<a class="btn btn-primary" href="' . $registrationUrl . '" target="_blank">' .
								translate(['text' => 'Register at Hoopla', 'isPublicFacing' => true]) .
								'</a>',
							'api' => [
								'title' => translate([
									'text' => 'Unable to place hold',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'We are unable to find a Hoopla digital account for your library card. Please register at %1%',
									1 => $registrationUrl,
									'isPublicFacing' => true,
								])
							]
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Unable to place hold',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'There was an error placing your hold. Please try again later.',
								'isPublicFacing' => true,
							]),
							'api' => [
								'title' => translate([
									'text' => 'Unable to place hold',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'There was an error placing your hold. Please try again later.',
									'isPublicFacing' => true,
								]),
							]
						];
					}
				}
			}
		}

		return $result;

	}

	function trackRecordHold($recordId): void {
		require_once ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php';
		require_once ROOT_DIR . '/sys/Hoopla/HooplaExtract.php';
		$recordUsage = new HooplaRecordUsage();
		$product = new HooplaExtract();
		$product->hooplaId = $recordId;
		if ($product->find(true)) {
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->hooplaId = $product->hooplaId;
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

	function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array
	{
		$result = [
			'success' => false,
			'message' => 'Unknown error canceling hold'
		];

		$holds = $this->getHolds($patron);
		$holdToCancel = $this->getHoldByCancelId($holds, $recordId, $cancelId);

		$patronUrl = $this->getHooplaBasePatronURL($patron);
		if (!empty($patronUrl)) {
			$cancelUrl = $patronUrl . '/holds/' . $recordId;
			$cancelResponse = $this->getAPIResponse('hoopla.cancelHold', $cancelUrl, null, 'DELETE');

			if ($cancelResponse['httpCode'] == 403) {
				// Hold already cancelled or doesn't exist
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'Hold already cancelled or doesn\'t exist',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Unable to cancel hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Hold already cancelled or doesn\'t exist',
					'isPublicFacing' => true,
				]);
			} else if ($cancelResponse['httpCode'] == 200) {
				// Empty response means success (HTTP 200 with no content)
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'Your Hoopla hold was cancelled successfully',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Hold Cancelled Successfully',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Your Hoopla hold was cancelled successfully',
					'isPublicFacing' => true,
				]);
				$this->updateCachesForCancelledHold($patron, $holdToCancel, 'hoopla');
			} else {
				$result['message'] = translate([
					'text' => 'Could not cancel Hoopla hold.',
					'isPublicFacing' => true,
				]);
				$result['api']['title'] = translate([
					'text' => 'Unable to cancel hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Could not cancel Hoopla hold.',
					'isPublicFacing' => true,
				]);
			}
		}

		return $result;
	}

	/**
	 * @param User $user
	 */
	public function trackUserUsageOfHoopla(User $user): void {
		require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
		$userUsage = new UserHooplaUsage();

		$userHooplaTracking = $user->userCookiePreferenceLocalAnalytics || !$user->getHomeLibrary()->cookieStorageConsent;;
		global $aspenUsage;
		$userUsage->instance = $aspenUsage->getInstance();
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userHooplaTracking) {
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
	 * @param int $hooplaId
	 */
	public function trackRecordCheckout($hooplaId): void {
		require_once ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php';
		$recordUsage = new HooplaRecordUsage();
		require_once ROOT_DIR . '/sys/Hoopla/HooplaExtract.php';
		$product = new HooplaExtract();
		$product->hooplaId = $hooplaId;
		if ($product->find(true)) {
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->hooplaId = $product->id;
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
	private function isHooplaVersion2(): bool {
		if ($this->isHooplaVersion2 == null) {
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			$this->isHooplaVersion2 = ($systemVariables !== false && !empty($systemVariables->hooplaVersion) && (int)$systemVariables->hooplaVersion == 2);
		}
		return $this->isHooplaVersion2;
	}
}
