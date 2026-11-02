<?php

/**
 * BorrowBox API Driver for Aspen Discovery.
 *
 * Handles all API communication with BorrowBox for patron operations
 * including checkouts, holds/reserves, returns, and renewals.
 *
 * BorrowBox uses a single JWT Bearer token (client_credentials grant) for all
 * API calls. Patrons are identified by barcode in the URL path — there is no
 * separate patron OAuth flow.
 *
 * @category Aspen Discovery
 * @package  Drivers
 */

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class BorrowBoxDriver extends AbstractEContentDriver {

	/** @var null|BorrowBoxSetting|false Current BorrowBox setting, null = not loaded, false = not found */
	protected null|BorrowBoxSetting|false $settings = null;

	/** @var CurlWrapper|null Reusable cURL wrapper for API requests */
	private ?CurlWrapper $apiCurlWrapper = null;

	/** @var string|null Cached JWT access token */
	private ?string $accessToken = null;

	/** @var int|null Epoch timestamp when the access token expires */
	private ?int $tokenExpiry = null;

	/**
	 * Set the active BorrowBox setting.
	 */
	public function setSettings(BorrowBoxSetting $setting): void {
		$this->settings = $setting;
		$this->accessToken = null;
		$this->tokenExpiry = null;
	}

	private function restoreSettings(null|BorrowBoxSetting|false $originalSettings): void {
		$this->settings = $originalSettings;
		$this->accessToken = null;
		$this->tokenExpiry = null;
	}

	/**
	 * Get the active BorrowBox setting, or load the first available one.
	 *
	 * @return BorrowBoxSetting|false
	 */
	public function getSettings(): BorrowBoxSetting|false {
		if ($this->settings !== null) {
			return $this->settings !== false ? $this->settings : false;
		}

		$availableSettings = $this->getAvailableSettings();
		if (empty($availableSettings)) {
			$this->settings = false;
			return false;
		}

		$this->settings = reset($availableSettings);
		return $this->settings;
	}

	/** @var BorrowBoxSetting[]|null */
	private ?array $_availableSettings = null;

	/**
	 * Get all BorrowBox settings available for the current library context.
	 *
	 * @return BorrowBoxSetting[]
	 */
	public function getAvailableSettings(): array {
		if ($this->_availableSettings !== null) {
			return $this->_availableSettings;
		}

		$this->_availableSettings = [];
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
		require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxSettings.php';

		$activeLibrary = $this->getActiveLibrary();

		if ($activeLibrary === null) {
			return $this->_availableSettings;
		}

		$libraryBorrowBoxSettings = new LibraryBorrowBoxSettings();
		$libraryBorrowBoxSettings->libraryId = $activeLibrary->libraryId;
		if (!$libraryBorrowBoxSettings->find()) {
			return $this->_availableSettings;
		}

		while ($libraryBorrowBoxSettings->fetch()) {
			$borrowBoxSetting = new BorrowBoxSetting();
			$borrowBoxSetting->id = $libraryBorrowBoxSettings->settingId;
			if (!$borrowBoxSetting->find(true)) {
				continue;
			}
			$this->_availableSettings[$borrowBoxSetting->id] = clone $borrowBoxSetting;
		}

		return $this->_availableSettings;
	}

	private function getActiveLibrary(): ?Library {
		global $library;
		$activeLocationId = UserAccount::isLoggedIn() ? UserAccount::getUserHomeLocationId() : 0;
		if ($activeLocationId <= 0) {
			return $library;
		}
		$location = new Location();
		$location->locationId = $activeLocationId;
		if (!$location->find(true)) {
			return $library;
		}
		$homeLibrary = new Library();
		$homeLibrary->libraryId = $location->libraryId;
		if (!$homeLibrary->find(true)) {
			return $library;
		}
		return $homeLibrary;
	}

	private function setSettingsForProduct(string $productId): void {
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = $productId;
		if (!$product->find(true)) {
			return;
		}
		$availableSettings = $this->getAvailableSettings();
		$availability = new BorrowBoxAPIProductAvailability();
		$availability->productId = $product->id;
		$availability->find();
		while ($availability->fetch()) {
			if (isset($availableSettings[$availability->settingId])) {
				$this->setSettings($availableSettings[$availability->settingId]);
				return;
			}
		}
	}

	/**
	 * Initialise or re-initialise the cURL wrapper.
	 */
	public function initCurlWrapper(): void {
		$this->apiCurlWrapper = new CurlWrapper();
		$this->apiCurlWrapper->timeout = 20;
		$this->apiCurlWrapper->connectTimeout = 5;
	}

	/**
	 * Authenticate with the BorrowBox API and obtain / cache a JWT access token.
	 *
	 * Uses `POST /v1/token` with Basic auth (Base64 of username:password) and
	 * `grant_type=client_credentials` as form data.
	 *
	 * @param bool $forceNew Force re-authentication even if a cached token exists.
	 * @return bool True if we have a valid token, false on failure.
	 */
	private function _connectToAPI(bool $forceNew = false): bool {
		$setting = $this->getSettings();
		if ($setting === false) {
			return false;
		}

		$currentTokenIsValid = !$forceNew && $this->accessToken !== null && $this->tokenExpiry !== null && time() < $this->tokenExpiry;
		if ($currentTokenIsValid) {
			return true;
		}

		global $memCache;
		$cacheKey = 'borrowbox_token_' . $setting->id;
		$cachedToken = $forceNew ? false : $memCache->get($cacheKey);
		$cachedTokenFound = $cachedToken !== false && is_array($cachedToken);
		if ($cachedTokenFound) {
			$this->accessToken = $cachedToken['access_token'];
			$this->tokenExpiry = $cachedToken['expiry'];
		}
		$cachedTokenIsValid = $cachedTokenFound && time() < $this->tokenExpiry;
		if ($cachedTokenIsValid) {
			return true;
		}

		$apiUrl = rtrim($setting->apiUrl, '/');
		$url = $apiUrl . '/v1/token';

		$this->initCurlWrapper();
		$authString = base64_encode($setting->apiUsername . ':' . $setting->apiPassword);
		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Basic ' . $authString,
			'Content-Type: application/x-www-form-urlencoded',
		], true);

		$postFields = 'grant_type=client_credentials';
		$response = $this->apiCurlWrapper->curlPostPage($url, $postFields);
		ExternalRequestLogEntry::logRequest('borrowbox.connectToAPI', 'POST', $url, $this->apiCurlWrapper->getHeaders(), $postFields, $this->apiCurlWrapper->getResponseCode(), $response, ['Authorization' => 'Basic ***']);

		$responseCode = $this->apiCurlWrapper->getResponseCode();
		if ($responseCode != '200') {
			global $logger;
			$logger->log("BorrowBox authentication failed with HTTP $responseCode", Logger::LOG_ERROR);
			$this->incrementStat('numConnectionFailures');
			return false;
		}

		$tokenData = json_decode($response, true);
		$accessTokenMissing = empty($tokenData) || empty($tokenData['access_token']);
		if ($accessTokenMissing) {
			global $logger;
			$logger->log('BorrowBox authentication returned invalid token data', Logger::LOG_ERROR);
			$this->incrementStat('numConnectionFailures');
			return false;
		}

		$expiresIn = $tokenData['expires_in'] ?? 3600;
		$this->accessToken = $tokenData['access_token'];
		$this->tokenExpiry = time() + $expiresIn - 30;

		$memCache->set($cacheKey, [
			'access_token' => $this->accessToken,
			'expiry' => $this->tokenExpiry,
		], $expiresIn - 30);

		return true;
	}

	/**
	 * Perform a GET request to the BorrowBox API with Bearer auth.
	 * Automatically re-authenticates on 401.
	 *
	 * @param string $url       Full URL to call
	 * @param string $methodName Method name for logging
	 * @return object|null Decoded JSON response, or null on failure.
	 */
	private function _callUrl(string $url, string $methodName): ?object {
		if (!$this->_connectToAPI()) {
			return null;
		}

		$this->initCurlWrapper();
		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Bearer ' . $this->accessToken,
			'Accept: application/json',
		], true);

		$response = $this->apiCurlWrapper->curlGetPage($url);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName, 'GET', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);

		if ($responseCode == '401' && $this->_connectToAPI(true)) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $this->accessToken,
				'Accept: application/json',
			], true);

			$response = $this->apiCurlWrapper->curlGetPage($url);
			$responseCode = $this->apiCurlWrapper->getResponseCode();
			ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName . '_retry', 'GET', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);
		}

		if ($responseCode != '200') {
			return null;
		}

		return json_decode($response);
	}

	/**
	 * Perform a POST request to the BorrowBox API.
	 *
	 * BorrowBox POST endpoints for loans use query parameters, not a request body.
	 * The $params string should be appended to the URL before calling.
	 *
	 * @param string $url       Full URL (with query params already appended)
	 * @param string $methodName Method name for logging
	 * @return object|array|null Decoded JSON response, or null on failure.
	 */
	private function _callPostUrl(string $url, string $methodName): object|array|null {
		if (!$this->_connectToAPI()) {
			return null;
		}

		$this->initCurlWrapper();
		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Bearer ' . $this->accessToken,
			'Content-Type: application/json',
			'Accept: application/json',
		], true);

		$response = $this->apiCurlWrapper->curlPostPage($url, '');
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName, 'POST', $url, $this->apiCurlWrapper->getHeaders(), '', $responseCode, $response, []);

		if ($responseCode == '401' && $this->_connectToAPI(true)) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $this->accessToken,
				'Content-Type: application/json',
				'Accept: application/json',
			], true);

			$response = $this->apiCurlWrapper->curlPostPage($url, '');
			$responseCode = $this->apiCurlWrapper->getResponseCode();
			ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName . '_retry', 'POST', $url, $this->apiCurlWrapper->getHeaders(), '', $responseCode, $response, []);
		}

		$result = new stdClass();
		$result->responseCode = $responseCode;
		$result->body = null;

		if (!empty($response)) {
			$result->body = json_decode($response);
		}

		return $result;
	}

	/**
	 * Perform a PUT request to the BorrowBox API (used for renewals).
	 *
	 * @param string $url       Full URL
	 * @param string $methodName Method name for logging
	 * @return object|null Result object with responseCode and body.
	 */
	private function _callPutUrl(string $url, string $methodName): ?object {
		if (!$this->_connectToAPI()) {
			return null;
		}

		$this->initCurlWrapper();
		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Bearer ' . $this->accessToken,
			'Content-Type: application/json',
			'Accept: application/json',
		], true);

		$response = $this->apiCurlWrapper->curlSendPage($url, 'PUT');
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName, 'PUT', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);

		if ($responseCode == '401' && $this->_connectToAPI(true)) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $this->accessToken,
				'Content-Type: application/json',
				'Accept: application/json',
			], true);

			$response = $this->apiCurlWrapper->curlSendPage($url, 'PUT');
			$responseCode = $this->apiCurlWrapper->getResponseCode();
			ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName . '_retry', 'PUT', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);
		}

		$result = new stdClass();
		$result->responseCode = $responseCode;
		$result->body = null;

		if (!empty($response)) {
			$result->body = json_decode($response);
		}

		return $result;
	}

	/**
	 * Perform a DELETE request to the BorrowBox API (used for returns and cancellations).
	 *
	 * @param string $url       Full URL
	 * @param string $methodName Method name for logging
	 * @return object|null Result object with responseCode and body (body may be null for 204).
	 */
	private function _callDeleteUrl(string $url, string $methodName): ?object {
		if (!$this->_connectToAPI()) {
			return null;
		}

		$this->initCurlWrapper();
		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Bearer ' . $this->accessToken,
			'Accept: application/json',
		], true);

		$response = $this->apiCurlWrapper->curlSendPage($url, 'DELETE');
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName, 'DELETE', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);

		if ($responseCode == '401' && $this->_connectToAPI(true)) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $this->accessToken,
				'Accept: application/json',
			], true);

			$response = $this->apiCurlWrapper->curlSendPage($url, 'DELETE');
			$responseCode = $this->apiCurlWrapper->getResponseCode();
			ExternalRequestLogEntry::logRequest('borrowbox.' . $methodName . '_retry', 'DELETE', $url, $this->apiCurlWrapper->getHeaders(), false, $responseCode, $response, []);
		}

		$result = new stdClass();
		$result->responseCode = $responseCode;
		$result->body = null;

		if (!empty($response)) {
			$result->body = json_decode($response);
		}

		return $result;
	}

	/**
	 * Get the BorrowBox siteId for a patron's home library.
	 *
	 * @param User $patron
	 * @return string|null
	 */
	private function getSiteId(User $patron): ?string {
		require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxSettings.php';
		$homeLibrary = $patron->getHomeLibrary();
		if ($homeLibrary === null) {
			return null;
		}

		$setting = $this->getSettings();
		if ($setting === false) {
			return null;
		}

		$librarySettings = new LibraryBorrowBoxSettings();
		$librarySettings->libraryId = $homeLibrary->libraryId;
		$librarySettings->settingId = $setting->id;
		if ($librarySettings->find(true)) {
			return $librarySettings->siteId;
		}

		return null;
	}

	/**
	 * Get the URL-encoded patron barcode for BorrowBox API paths.
	 *
	 * @param User $patron
	 * @return string
	 */
	private function getPatronId(User $patron): string {
		return urlencode($patron->getBarcode());
	}

	/**
	 * Build the base loans URL for a patron.
	 *
	 * @param User $patron
	 * @return string|null The loans URL, or null if siteId cannot be determined.
	 */
	private function getPatronLoansUrl(User $patron): ?string {
		$setting = $this->getSettings();
		if ($setting === false) {
			return null;
		}
		$siteId = $this->getSiteId($patron);
		if ($siteId === null) {
			return null;
		}
		$patronId = $this->getPatronId($patron);
		$apiUrl = rtrim($setting->apiUrl, '/');
		return "$apiUrl/v1/sites/$siteId/patrons/$patronId/loans";
	}

	/**
	 * Get a summary of the patron's BorrowBox account (checkout + hold counts).
	 *
	 * @param User $user
	 * @return AccountSummary
	 */
	public function getAccountSummary(User $user): AccountSummary {
		$summary = $user->getCachedAccountSummary('borrowbox');
		$summaryIsCurrent = !$summary->dataIsStale && !isset($_REQUEST['reload']);
		if ($summaryIsCurrent) {
			return $summary;
		}

		$checkedOutItems = $this->getCheckouts($user);
		$summary->numCheckedOut = count($checkedOutItems);

		$holds = $this->getHolds($user);
		$summary->numAvailableHolds = count($holds['available']);
		$summary->numUnavailableHolds = count($holds['unavailable']);

		$summary->lastLoaded = time();
		$summary->update();

		return $summary;
	}

	/**
	 * Get all active checkouts for a patron from BorrowBox.
	 *
	 * Calls GET /v1/sites/{siteId}/patrons/{patronId}/loans and filters for
	 * loanStatus=ACTIVE.
	 *
	 * @param User $patron
	 * @return Checkout[]
	 */
	public function getCheckouts(User $patron, array $options = []): array {
		$accountSummary = $patron->getCachedAccountSummary('borrowbox');
		$cachedCheckouts = $patron->getCachedCheckoutsForSource('borrowbox');
		$checkoutsAreCurrent = !$accountSummary->dataIsStale && !$accountSummary->areCheckoutsStale() && !isset($_REQUEST['reload']) && !isset($_REQUEST['refreshCheckouts']);
		if ($checkoutsAreCurrent) {
			return $cachedCheckouts;
		}

		require_once ROOT_DIR . '/sys/User/Checkout.php';

		$checkouts = [];

		if (!empty($this->settings) && $this->settings !== false) {
			$settingsToCheck = [$this->settings->id => $this->settings];
		} else {
			$settingsToCheck = $this->getAvailableSettings();
		}

		foreach ($settingsToCheck as $setting) {
			$originalSettings = $this->settings;
			$this->setSettings($setting);

			$loansUrl = $this->getPatronLoansUrl($patron);
			if ($loansUrl === null) {
				$this->restoreSettings($originalSettings);
				continue;
			}

			$response = $this->_callUrl($loansUrl, 'getCheckouts');
			$loansMissing = $response === null || !isset($response->items);
			if ($loansMissing) {
				$this->incrementStat('numApiErrors');
				$this->restoreSettings($originalSettings);
				continue;
			}

			$this->trackUserUsageOfBorrowBox($patron);

			foreach ($response->items as $loan) {
				if (!isset($loan->loanStatus) || $loan->loanStatus !== 'ACTIVE') {
					continue;
				}

				$checkout = new Checkout();
				$checkout->type = 'borrowbox';
				$checkout->source = 'borrowbox';
				$checkout->userId = $patron->id;
				$checkout->sourceId = $loan->loanId . '_' . $setting->id;
				$checkout->recordId = $loan->productId;
				$checkout->dueDate = $loan->endDate;
				$checkout->checkoutDate = $loan->startDate;
				$checkout->canReturnEarly = true;
				$checkout->canRenew = true;

				if (!empty($loan->accessLink)) {
					$checkout->accessOnlineUrl = $loan->accessLink;
				}

				if (count($settingsToCheck) > 1) {
					$checkout->collectionName = $setting->name;
				}

				require_once ROOT_DIR . '/RecordDrivers/BorrowBoxRecordDriver.php';
				$recordDriver = new BorrowBoxRecordDriver($loan->productId);
				if ($recordDriver->isValid()) {
					$checkout->updateFromRecordDriver($recordDriver);
				}

				$key = $checkout->source . $checkout->sourceId . $checkout->userId;
				$checkouts[$key] = $checkout;
			}

			$this->restoreSettings($originalSettings);
		}

		return $this->updateCachedCheckoutsBasedOnActiveCheckouts($cachedCheckouts, $checkouts, $accountSummary);
	}

	/**
	 * Get all current holds/reserves for a patron from BorrowBox.
	 *
	 * Calls GET /v1/sites/{siteId}/patrons/{patronId}/loans and filters for
	 * loanStatus=RESERVED.
	 *
	 * @param User $patron
	 * @return array{available: Hold[], unavailable: Hold[]}
	 */
	public function getHolds(User $patron): array {
		$accountSummary = $patron->getCachedAccountSummary('borrowbox');
		$cachedHolds = $patron->getCachedHoldsForSource('borrowbox');
		$holdsAreCurrent = !$accountSummary->dataIsStale && !$accountSummary->areHoldsStale() && !isset($_REQUEST['reload']) && !isset($_REQUEST['refreshHolds']);
		if ($holdsAreCurrent) {
			return $cachedHolds;
		}

		require_once ROOT_DIR . '/sys/User/Hold.php';

		$holds = [
			'available' => [],
			'unavailable' => [],
		];

		if (!empty($this->settings) && $this->settings !== false) {
			$settingsToCheck = [$this->settings->id => $this->settings];
		} else {
			$settingsToCheck = $this->getAvailableSettings();
		}

		foreach ($settingsToCheck as $setting) {
			$originalSettings = $this->settings;
			$this->setSettings($setting);

			$loansUrl = $this->getPatronLoansUrl($patron);
			if ($loansUrl === null) {
				$this->restoreSettings($originalSettings);
				continue;
			}

			$response = $this->_callUrl($loansUrl, 'getHolds');
			if ($response === null || !isset($response->items)) {
				$this->incrementStat('numApiErrors');
				$this->restoreSettings($originalSettings);
				continue;
			}

			foreach ($response->items as $loan) {
				if (!isset($loan->loanStatus) || $loan->loanStatus !== 'RESERVED') {
					continue;
				}

				$hold = new Hold();
				$hold->type = 'borrowbox';
				$hold->source = 'borrowbox';
				$hold->sourceId = $loan->loanId . '_' . $setting->id;
				$hold->recordId = $loan->productId;
				$hold->userId = $patron->id;
				$hold->createDate = $loan->startDate;
				$hold->expirationDate = $loan->endDate;
				$hold->cancelable = true;
				$hold->available = false;
				$hold->canFreeze = false;

				if (count($settingsToCheck) > 1) {
					$hold->collectionName = $setting->name;
				}

				require_once ROOT_DIR . '/RecordDrivers/BorrowBoxRecordDriver.php';
				$recordDriver = new BorrowBoxRecordDriver($loan->productId);
				if ($recordDriver->isValid()) {
					$hold->updateFromRecordDriver($recordDriver);
				}

				$key = $hold->type . $hold->sourceId . $hold->userId;
				$holds['unavailable'][$key] = $hold;
			}

			$this->restoreSettings($originalSettings);
		}

		return $this->updateCachedHoldsBasedOnActiveHolds($cachedHolds, $holds, $accountSummary);
	}

	/**
	 * BorrowBox does not provide native reading history.
	 */
	public function hasNativeReadingHistory(): bool {
		return false;
	}

	/**
	 * Track a patron's usage of BorrowBox.
	 *
	 * @param User $patron
	 */
	public function trackUserUsageOfBorrowBox(User $patron): void {
		$userBorrowBoxTracking = $patron->userCookiePreferenceLocalAnalytics || !$patron->getHomeLibrary()->cookieStorageConsent;
		if (!$userBorrowBoxTracking) {
			return;
		}

		require_once ROOT_DIR . '/sys/BorrowBox/UserBorrowBoxUsage.php';
		$userUsage = new UserBorrowBoxUsage();
		global $aspenUsage;
		$userUsage->instance = $aspenUsage->getInstance();
		$userUsage->userId = $patron->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');
		$userUsage->day = date('d');

		if ($userUsage->find(true)) {
			$userUsage->usageCount++;
			$userUsage->update();
		} else {
			$userUsage->usageCount = 1;
			$userUsage->insert();
		}
	}

	/**
	 * Track a checkout event for a BorrowBox record.
	 *
	 * @param string $productId
	 */
	public function trackRecordCheckout(string $productId): void {
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxRecordUsage.php';
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
		$recordUsage = new BorrowBoxRecordUsage();
		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = $productId;
		if (!$product->find(true)) {
			return;
		}

		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->getInstance();
		$recordUsage->borrowboxId = $product->id;
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

	/**
	 * Track a hold event for a BorrowBox record.
	 *
	 * @param string $productId
	 */
	public function trackRecordHold(string $productId): void {
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxRecordUsage.php';
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
		$recordUsage = new BorrowBoxRecordUsage();
		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = $productId;
		if (!$product->find(true)) {
			return;
		}

		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->getInstance();
		$recordUsage->borrowboxId = $product->id;
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

	/**
	 * Increment a statistics counter for BorrowBox operations.
	 *
	 * @param string $fieldName The stats field to increment.
	 */
	public function incrementStat(string $fieldName): void {
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxStats.php';
		$borrowboxStats = new BorrowBoxStats();
		global $aspenUsage;
		$borrowboxStats->instance = $aspenUsage->getInstance();
		$borrowboxStats->year = date('Y');
		$borrowboxStats->month = date('n');
		$borrowboxStats->day = date('d');
		if ($borrowboxStats->find(true)) {
			$borrowboxStats->$fieldName++;
			$borrowboxStats->update();
		} else {
			$borrowboxStats->$fieldName = 1;
			$borrowboxStats->insert();
		}
	}

	/**
	 * Extract a user-facing error message from a BorrowBox API error response body.
	 *
	 * Per the BorrowBox API specification only patron_message may be shown to
	 * patrons; error_description and error codes are technical and must not be
	 * displayed.
	 *
	 * @param object|null $body Decoded JSON response body
	 * @return string
	 */
	private function extractErrorMessage(?object $body): string {
		if ($body === null) {
			return '';
		}
		if (!empty($body->patron_message)) {
			return (string)$body->patron_message;
		}
		return '';
	}
}
