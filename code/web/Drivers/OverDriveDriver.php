<?php

/**
 * Complete integration via APIs including availability and account information.
 */
require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
require_once ROOT_DIR . '/sys/Utils/DateUtils.php';

class OverDriveDriver extends AbstractEContentDriver {
	public int $version = 3;

	/** @var bool[] */
	protected array $requirePin = [];
	/** @var string[] */
	protected array $ILSName = [];

	protected null|OverDriveSetting|false $settings = null;
	/** @var string[] */
	private array $patronApiHost = [];
	/** @var string[] */
	private array $overdriveApiHost = [];
	/** @var string[] */
	protected ?array $format_map = null;
	private ?string $lastHttpCode = null;

	private ?CurlWrapper $apiCurlWrapper = null;

	public function initCurlWrapper() : void {
		$this->apiCurlWrapper = new CurlWrapper();
		$this->apiCurlWrapper->timeout = 5;
	}

	/**
	 * @var OverDriveDriver[]
	 */
	private static array $singletonDrivers = [];

	public static function getOverDriveDriver(int $settingId) : OverDriveDriver {
		if (!isset(OverDriveDriver::$singletonDrivers[$settingId])) {
			OverDriveDriver::$singletonDrivers[$settingId] = new OverDriveDriver();
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$overDriveSetting = new OverDriveSetting();
			$overDriveSetting->id = $settingId;
			if ($overDriveSetting->find(true)) {
				OverDriveDriver::$singletonDrivers[$settingId]->setSettings($overDriveSetting);
			}
		}
		return OverDriveDriver::$singletonDrivers[$settingId];
	}

	public function getFormatMap() : array {
		if ($this->format_map == null) {
			$readerName = $this->getReaderName();
			$this->format_map = [
				'ebook-epub-adobe' => 'Adobe EPUB eBook',
				'ebook-epub-open' => 'Open EPUB eBook',
				'ebook-pdf-adobe' => 'Adobe PDF eBook',
				'ebook-pdf-open' => 'Open PDF eBook',
				'ebook-kindle' => 'Kindle Book',
				'ebook-disney' => 'Disney Online Book',
				'ebook-overdrive' => "$readerName eBook",
				'ebook-microsoft' => 'Microsoft eBook',
				'audiobook-wma' => "$readerName Audiobook",
				'audiobook-mp3' => "$readerName Audiobook",
				'audiobook-streaming' => 'Streaming Audiobook',
				'music-wma' => "$readerName Music",
				'video-wmv' => "$readerName Video",
				'video-wmv-mobile' => "$readerName Video",
				'periodicals-nook' => 'NOOK Periodicals',
				'audiobook-overdrive' => "$readerName Listen",
				'video-streaming' => "$readerName Video",
				'ebook-mediado' => 'MediaDo Reader',
				'magazine-overdrive' => "$readerName Magazine",
			];
		}
		return $this->format_map;
	}

	public function getReaderName() : string {
		if (!empty($this->settings)) {
			return $this->settings->readerName;
		}else{
			$readerNames = $this->getReaderNames();
			return implode(', ', $readerNames);
		}
	}

	/**
	 * @var null|string[]
	 */
	private ?array $_readerNames = null;
	public function getReaderNames() : array {
		if ($this->_readerNames == null) {
			$this->_readerNames = [];
			try {
				$settings = $this->getAvailableSettings();
				foreach ($settings as $setting) {
					$this->_readerNames[$setting->readerName] = $setting->readerName;
				}
				if (empty($this->_readerNames)) {
					$this->_readerNames['Libby'] = 'Libby';
				}
			}catch (Exception) {
				//This happens if new tables are not installed
			}
		}
		return $this->_readerNames;
	}

	/**
	 * @var null|OverDriveSetting[]
	 */
	private ?array $_availableSettings = null;
	/**
	 * @return OverDriveSetting[]
	 */
	public function getAvailableSettings() : array {
		if ($this->_availableSettings == null) {
			$this->_availableSettings = [];

			if (UserAccount::isLoggedIn()) {
				$activeLibrary = UserAccount::getLoggedInUser()->getHomeLibrary();
			}else{
				global $library;
				$activeLibrary = $library;
			}

			if ($activeLibrary != null) {
				$librarySettings = $activeLibrary->getLibraryOverdriveSettings();
				foreach ($librarySettings as $librarySetting) {
					$this->_availableSettings[$librarySetting->settingId] = $librarySetting->getOverDriveSettings();
				}
			}
		}

		return $this->_availableSettings;
	}

	public function getActiveSettings() : OverDriveSetting {
		if (!empty($this->settings)) {
			return $this->settings;
		}else {
			$settings = $this->getAvailableSettings();
			return reset($settings);
		}
	}

	public function getProductUrl(OverDriveSetting $settings, $crossRefId) : string {
		$baseUrl = $settings->url;
		if (!str_ends_with($baseUrl, '/')) {
			$baseUrl .= '/';
		}
		if (str_contains($baseUrl, 'lexisdl')) {
			$baseUrl .= 'title/' . $crossRefId;
		} else{
			$baseUrl .= 'media/' . $crossRefId;
		}
		return $baseUrl;
	}

	public function isCirculationEnabled(Library $activeLibrary, OverDriveSetting $settings) : bool {
		$librarySettings = $activeLibrary->getLibraryOverdriveSetting($settings->id);
		if ($librarySettings == null) {
			return false;
		}else{
			return (bool) $librarySettings->circulationEnabled;
		}
	}
	public function getTokenData(Library $activeLibrary, OverDriveSetting $settings) : false|stdClass|null {
		return $this->_connectToAPI($activeLibrary, $settings, true, "getTokenData");
	}

	public function getPatronTokenData(OverDriveSetting $settings, User $user, bool $forceNewConnection = false) : bool|stdClass {
		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($settings, $user)) {
			$userPin = $user->getPasswordOrPin();
			$tokenData = $this->_connectToPatronAPI($settings, $user, $userBarcode, $userPin, $forceNewConnection);
		} else {
			$tokenData = $this->_connectToPatronAPI($settings, $user, $userBarcode, null, $forceNewConnection);
		}

		return $tokenData;
	}

	private function _connectToAPI(Library $activeLibrary, OverDriveSetting $settings, bool $forceNewConnection, string $methodName) : false|stdClass|null {
		global $memCache;
		$tokenData = $memCache->get('overdrive_token_' . $settings->id . '_' . $activeLibrary->libraryId);
		if ($forceNewConnection || $tokenData === false) {
			$activeLibrarySettings = $activeLibrary->getLibraryOverdriveSetting($settings->id);
			$clientAuthString = $this->getClientAuthString($settings, $activeLibrarySettings);
			if (!empty($clientAuthString)) {

				$url = "https://oauth.overdrive.com/token";

				$this->initCurlWrapper();
				$this->apiCurlWrapper->setConnectTimeout(1);
				$this->apiCurlWrapper->addCustomHeaders([
					"Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
				], true);

				$params = [
					'grant_type' => 'client_credentials',
				];

				$curlOptions = [
					CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_USERPWD => $clientAuthString,
				];

				$response = $this->apiCurlWrapper->curlPostPage($url, $params, $curlOptions);
				ExternalRequestLogEntry::logRequest('overdrive.connectToAPI_' . $methodName, 'POST', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);

				$tokenData = json_decode($response);
				if ($tokenData) {
					if (!isset($tokenData->error)) {
						$memCache->set('overdrive_token_' . $settings->id . '_' . $activeLibrary->libraryId, $tokenData, $tokenData->expires_in - 10);
					}
				} else {
					$this->incrementStat('numConnectionFailures');
				}
			} else {
				//OverDrive is not configured
				return false;
			}
		}
		return $tokenData;
	}

	private function _connectToPatronAPI(OverDriveSetting $settings, User $user, string $patronBarcode, ?string $patronPin, bool $forceNewConnection = false) {
		global $memCache;
		global $timer;
		global $logger;

		$homeLibrary = $user->getHomeLibrary();
		if (empty($homeLibrary)) {
			return false;
		}
		$librarySettings = $homeLibrary->getLibraryOverdriveSetting($settings->id);
		if ($librarySettings == null) {
			//These settings aren't valid for the library
			return false;
		}
		$patronTokenData = $memCache->get("overdrive_patron_token_{$settings->id}_{$homeLibrary->libraryId}_$patronBarcode");
		if ($forceNewConnection || !$patronTokenData) {
			$tokenData = $this->_connectToAPI($user->getHomeLibrary(), $settings, $forceNewConnection, "connectToPatronAPI");
			$timer->logTime("Connected to OverDrive API");
			if ($tokenData) {
				$this->initCurlWrapper();
				$url = "https://oauth-patron.overdrive.com/patrontoken";
				//This seems to be no longer needed now that we are using apiCurlWrapper
				//$ch = curl_init("https://oauth-patron.overdrive.com/patrontoken");
				if (empty($settings->websiteId)) {
					if (IPAddress::showDebuggingInformation()) {
						$logger->log("Patron is not valid for OverDrive, website id is not set", Logger::LOG_ERROR);
					}
					return false;
				}
				$websiteId = $settings->websiteId;

				$ilsname = $this->getILSName($settings, $user);
				if (empty($ilsname)) {
					$logger->log("Patron is not valid for OverDrive, ILSName is not set", Logger::LOG_ERROR);
					return false;
				}

				$clientAuthString = $this->getClientAuthString($settings, $librarySettings);
				if (empty($clientAuthString)) {
					$logger->log("Patron is not valid for OverDrive, ClientSecret is not set", Logger::LOG_ERROR);
					return false;
				}
				$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
				$this->apiCurlWrapper->setOption(CURLOPT_SSL_VERIFYPEER, false);
				$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);
				$encodedAuthValue = base64_encode($clientAuthString);
				global $interface;
				$this->apiCurlWrapper->addCustomHeaders([
					"Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
					"Authorization: Basic " . $encodedAuthValue,
					"User-Agent: Aspen Discovery " . $interface->getVariable('aspenVersion'),
				], true);

				$patronBarcode = urlencode($patronBarcode);
				if ($patronPin == null && !$user->isLoggedInViaSSO) {
					$postFields = "grant_type=password&username=$patronBarcode&password=ignore&password_required=false&scope=websiteId:$websiteId%20ilsname:$ilsname";
				} elseif ($patronPin != null) {
					$postFields = "grant_type=password&username=$patronBarcode&password=$patronPin&password_required=true&scope=websiteId:$websiteId%20ilsname:$ilsname";
				} else {
					return false;
				}

				$content = $this->apiCurlWrapper->curlPostPage($url, $postFields);
				ExternalRequestLogEntry::logRequest('overdrive.connectToPatronAPI_' . "getPatronTokenData", 'POST', $url, $this->apiCurlWrapper->getHeaders(), $postFields, $this->apiCurlWrapper->getResponseCode(), $content, ['password'=>$patronPin]);
				$timer->logTime("Logged $patronBarcode into OverDrive API");
				$patronTokenData = json_decode($content);
				$timer->logTime("Decoded return for login of $patronBarcode into OverDrive API");
				if ($patronTokenData) {
					if (isset($patronTokenData->error)) {
						if ($patronTokenData->error == 'unauthorized_client') { // login failure
							// patrons with too high a fine amount will get this result.
							$logger->log("Patron is not valid for OverDrive, patronTokenData returned unauthorized_client", Logger::LOG_ERROR);
						} else {
							if (IPAddress::showDebuggingInformation()) {
								$logger->log("Patron $patronBarcode is not valid for OverDrive, { $patronTokenData->error}", Logger::LOG_ERROR);
							}
						}
						return false;
					} else {
						if (property_exists($patronTokenData, 'expires_in')) {
							$memCache->set("overdrive_patron_token_{$settings->id}_{$homeLibrary->libraryId}_$patronBarcode", $patronTokenData, $patronTokenData->expires_in - 10);
						} else {
							$this->incrementStat('numConnectionFailures');
							return false;
						}
					}
				}else{
					$logger->log("Did not get a valid response from OverDrive while connecting to the Patron API", Logger::LOG_ERROR);
					$this->incrementStat('numConnectionFailures');
					return false;
				}
			} else {
				$logger->log("Could not connect to OverDrive", Logger::LOG_ERROR);
				$this->incrementStat('numConnectionFailures');
				return false;
			}
		}
		return $patronTokenData;
	}

	public function _callUrl(Library $activeLibrary, OverDriveSetting $settings, string $url, string $methodName) {
		$tokenData = $this->_connectToAPI($activeLibrary, $settings, false, "callUrl");
		if ($tokenData) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
			$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);
			global $interface;
			$this->apiCurlWrapper->addCustomHeaders([
				"Authorization: $tokenData->token_type $tokenData->access_token",
				"User-Agent: Aspen Discovery " . $interface->getVariable('aspenVersion'),
			], true);

			$content = $this->apiCurlWrapper->curlGetPage($url);
			ExternalRequestLogEntry::logRequest('overdrive.callUrl_' . $methodName, 'GET', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $content, []);
			$response = json_decode($content);
			//print_r($returnVal);
			if ($response != null) {
				if (!isset($response->message) || $response->message != 'An unexpected error has occurred.') {
					return $response;
				}
			}
		}
		return null;
	}

	private function getILSName(OverDriveSetting $settings, User $user) : ?string {
		if (!isset($this->ILSName[$settings->id])) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			/** @var Library $patronHomeLibrary */
			$patronHomeLibrary = Library::getPatronHomeLibrary($user);
			if ($patronHomeLibrary == null) {
				$this->ILSName[$settings->id] = null;
			}else{
				$homeLibrarySettings = $patronHomeLibrary->getLibraryOverdriveSetting($settings->id);
				if ($homeLibrarySettings != null) {
					$this->ILSName[$settings->id] = $homeLibrarySettings->authenticationILSName;
				}else{
					$this->ILSName[$settings->id] = null;
				}
			}
		}
		return $this->ILSName[$settings->id];
	}

	private function getRequirePin(OverDriveSetting $settings, User $user) : bool {
		if (!isset($this->requirePin[$settings->id])) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			$patronHomeLibrary = Library::getPatronHomeLibrary($user);
			if ($patronHomeLibrary == null) {
				$this->requirePin[$settings->id] = false;
			}else{
				$homeLibrarySettings = $patronHomeLibrary->getLibraryOverdriveSetting($settings->id);
				if ($homeLibrarySettings != null) {
					$this->requirePin[$settings->id] = $homeLibrarySettings->requirePin;
				}else{
					$this->requirePin[$settings->id] = false;
				}
			}
		}
		return $this->requirePin[$settings->id];
	}

	public function _callPatronUrl(OverDriveSetting $settings, User $user, string $url, string $methodName, ?array $postParams = null, ?string $method = null) : bool|stdClass {
		global $configArray;

		$tokenData = $this->getPatronTokenData($settings, $user);
		if ($tokenData) {
			$patronApiHost = $this->getPatronApiHost($settings);

			$this->initCurlWrapper();
			if (isset($tokenData->token_type) && isset($tokenData->access_token)) {
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				global $interface;
				$this->apiCurlWrapper->addCustomHeaders([
					"Authorization: $authorizationData",
					"User-Agent: Aspen Discovery " . $interface->getVariable('aspenVersion'),
					"Host: $patronApiHost",
				], true);
			} else {
				//The user is not valid
				if (isset($configArray['Site']['debug']) && $configArray['Site']['debug']) {
					print_r($tokenData);
				}
				return false;
			}

			$curlOptions = [
				CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => 2,
			];

			if ($postParams != null) {
				//Convert POST fields to JSON
				$jsonData = ['fields' => []];
				foreach ($postParams as $key => $value) {
					$jsonData['fields'][] = [
						'name' => $key,
						'value' => $value,
					];
				}
				$postData = json_encode($jsonData);
				//print_r($postData);
				$headers[] = 'Content-Type: application/vnd.overdrive.content.api+json';

				$this->apiCurlWrapper->addCustomHeaders($headers, false);
				$response = $this->apiCurlWrapper->curlPostPage($url, $postData, $curlOptions);
				ExternalRequestLogEntry::logRequest('overdrive.callPatronUrl_' . $methodName, 'POST', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			} else {
				$response = $this->apiCurlWrapper->curlGetPage($url);
				ExternalRequestLogEntry::logRequest('overdrive.callPatronUrl_' . $methodName, 'GET', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			}

			if (!empty($method)) {
				if ($postParams != null) {
					$jsonData = ['fields' => []];
					foreach ($postParams as $key => $value) {
						$jsonData['fields'][] = [
							'name' => $key,
							'value' => $value,
						];
					}
					$postData = json_encode($jsonData);
					$response = $this->apiCurlWrapper->curlSendPage($url, $method, $postData);
				} else {
					$response = $this->apiCurlWrapper->curlSendPage($url, $method);
				}
				ExternalRequestLogEntry::logRequest('overdrive.callPatronUrl_' . $methodName, $method, $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);
			}

			$returnVal = json_decode($response);
			if ($returnVal != null) {
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.') {
					return $returnVal;
				}
			} else {
				$this->lastHttpCode = $this->apiCurlWrapper->getResponseCode();
				global $logger;
				if ($response === false) {
					$logger->log("Failed to call overdrive url $url " . session_id() . " curl_exec returned false " . print_r($postParams, true), Logger::LOG_ERROR);
				} else {
					$logger->log("Failed to call overdrive url " . session_id() . print_r($response, true), Logger::LOG_ERROR);
				}

			}
		}
		return false;
	}

	private function _callPatronDeleteUrl(OverDriveSetting $settings, User $user, string $url, string $methodName) {
		$tokenData = $this->getPatronTokenData($settings, $user);

		$this->initCurlWrapper();
		$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
		$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);

		global $interface;
		if ($tokenData) {
			$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
			$patronApiHost = $this->getPatronApiHost($settings);
			$this->apiCurlWrapper->addCustomHeaders([
				"Authorization: $authorizationData",
				"User-Agent: Aspen Discovery " . $interface->getVariable('aspenVersion'),
				"Host: $patronApiHost",
			], true);
		} else {
			$this->apiCurlWrapper->addCustomHeaders([
				"User-Agent: Aspen Discovery " . $interface->getVariable('aspenVersion'),
				"Host: {$this->getOverDriveApiHost($settings)}",
			], true);
		}

		$content = $this->apiCurlWrapper->curlSendPage($url, "DELETE", false);
		ExternalRequestLogEntry::logRequest('overdrive.callPatronDeleteUrl_' . $methodName, 'DEL', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $content, []);
		$responseCode = $this->apiCurlWrapper->getResponseCode();

		if ($responseCode == 204) {
			$result = true;
		} else {
			//echo("Response code was " . $responseCode);
			$result = false;
		}

		$response = json_decode($content);
		if ($response != null) {
			if (!isset($response->message) || $response->message != 'An unexpected error has occurred.') {
				return $response;
			}
		} else {
			return $result;
		}
		return false;
	}

	public function getLibraryAccountInformation(Library $activeLibrary, OverDriveSetting $settings) {
		$libraryId = $settings->accountId;
		return $this->_callUrl($activeLibrary, $settings, "https://{$this->getOverDriveApiHost($settings)}/v1/libraries/$libraryId", "getLibraryAccountInformation");
	}

	public function getAdvantageAccountInformation(Library $activeLibrary, OverDriveSetting $settings) {
		$libraryId = $settings->accountId;
		return $this->_callUrl($activeLibrary, $settings, "https://{$this->getOverDriveApiHost($settings)}/v1/libraries/$libraryId/advantageAccounts", "getAdvantageAccountInformation");
	}

	public function getProductMetadata(Library $activeLibrary, OverDriveSetting $settings, $overDriveId, $productsKey = null) {
		if ($productsKey == null) {
			$productsKey = $settings->productsKey;
		}
		if (is_numeric($overDriveId)) {
			//This is a crossRefId, we need to search for the product by crossRefId to get the actual id
			$searchUrl = "https://{$this->getOverDriveApiHost($settings)}/v1/collections/$productsKey/products?crossRefId=$overDriveId";
			$searchResults = $this->_callUrl($activeLibrary, $settings, $searchUrl, "getProductMetadata");
			if (!empty($searchResults->products) && count($searchResults->products) > 0) {
				$overDriveId = $searchResults->products[0]->id;
			}
		}
		$overDriveId = strtoupper($overDriveId);
		$metadataUrl = "https://{$this->getOverDriveApiHost($settings)}/v1/collections/$productsKey/products/$overDriveId/metadata";
		return $this->_callUrl($activeLibrary, $settings, $metadataUrl, "getProductMetadata");
	}

	public function getProductAvailability(Library $activeLibrary, OverDriveSetting $settings, $overDriveId, $productsKey = null) {
		if ($productsKey == null) {
			$productsKey =$settings->productsKey;
		}
		$availabilityUrl = "https://{$this->getOverDriveApiHost($settings)}/v2/collections/$productsKey/products/$overDriveId/availability";
		return $this->_callUrl($activeLibrary, $settings, $availabilityUrl, "getProductAvailability");
	}

	/**
	 * Loads information about items that the user has checked out in OverDrive.
	 * If multiple OverDrive collections are connected, all checkouts for all collections will be loaded.
	 */
	public function getCheckouts(User $patron, bool $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		global $logger;

		if (!empty($this->settings)) {
			$settingsToCheck = [$this->settings->id => $this->settings];
		}else{
			$settingsToCheck = $this->getAvailableSettings();
		}
		$checkedOutTitles = [];
		foreach ($settingsToCheck as $setting) {
			if (!$this->isUserValidForOverDrive($setting, $patron)) {
				continue;
			}
			$url = $setting->patronApiUrl . '/v1/patrons/me/checkouts';
			$response = $this->_callPatronUrl($setting, $patron, $url, "getCheckouts");
			if ($response === false) {
				//The user is not authorized to use OverDrive
				$this->incrementStat('numApiErrors');
				continue;
			}

			$supplementalMaterialIds = [];
			if (isset($response->checkouts)) {
				foreach ($response->checkouts as $curTitle) {
					$checkout = new Checkout();
					$checkout->type = 'overdrive';
					$checkout->source = 'overdrive';
					$checkout->userId = $patron->id;
					if (isset($curTitle->links->bundledChildren)) {
						foreach ($curTitle->links->bundledChildren as $bundledChild) {
							if (preg_match('%.*/checkouts/(.*)%ix', $bundledChild->href, $matches)) {
								$supplementalMaterialIds[$matches[1]] = $curTitle->reserveId;
							}
						}
					}

					if (array_key_exists($curTitle->reserveId, $supplementalMaterialIds)) {
						$parentCheckoutId = $supplementalMaterialIds[$curTitle->reserveId];
						/** @var Checkout $parentCheckout */
						$parentCheckout = $checkedOutTitles['overdrive' . $parentCheckoutId . $patron->id];
						if (!isset($parentCheckout->supplementalMaterials)) {
							$parentCheckout->supplementalMaterials = [];
						}
						$supplementalMaterial = new Checkout();
						$supplementalMaterial->source = 'overdrive';
						$supplementalMaterial->sourceId = $curTitle->reserveId . '_' . $setting->id;
						if (count($settingsToCheck) > 1) {
							$supplementalMaterial->collectionName = $setting->name;
						}
						$supplementalMaterial->recordId = $curTitle->reserveId;
						$supplementalMaterial->userId = $patron->id;
						$supplementalMaterial->isSupplemental = true;
						$supplementalMaterial = $this->loadCheckoutFormatInformation($curTitle, $supplementalMaterial);
						if (!empty($supplementalMaterial->selectedFormatValue)) {
							$parentCheckout->supplementalMaterials[] = $supplementalMaterial;
						}
					} else {
						//Load data from api
						$checkout->sourceId = $curTitle->reserveId . '_' . $setting->id;
						$checkout->recordId = $curTitle->reserveId;
						if (count($settingsToCheck) > 1) {
							$checkout->collectionName = $setting->name;
						}
						$checkout->dueDate = $curTitle->expires;
						$checkout->canRenew = false;
						try {
							$expirationDate = new DateTime($curTitle->expires);
							$checkout->dueDate = $expirationDate->getTimestamp();
							//If the title expires in less than 3 days, we should be able to renew it

							if ($expirationDate->getTimestamp() < time() + 3 * 24 * 60 * 60) {
								$checkout->canRenew = true;
							} else {
								$checkout->canRenew = false;
							}
						} catch (Exception $e) {
							$logger->log("Could not parse date for overdrive expiration " . $curTitle->expires, Logger::LOG_NOTICE);
						}
						try {
							$checkOutDate = new DateTime($curTitle->checkoutDate);
							$checkout->checkoutDate = $checkOutDate->getTimestamp();
						} catch (Exception $e) {
							$logger->log("Could not parse date for overdrive checkout date " . $curTitle->checkoutDate, Logger::LOG_NOTICE);
						}
						$checkout->overdriveRead = false;
						if (isset($curTitle->isFormatLockedIn) && $curTitle->isFormatLockedIn == 1) {
							$checkout->formatSelected = true;
						} else {
							$checkout->formatSelected = false;
						}
						$checkout->formats = [];
						if (!$forSummary) {
							$checkout = $this->loadCheckoutFormatInformation($curTitle, $checkout);

							if (isset($curTitle->actions->earlyReturn)) {
								$checkout->canReturnEarly = true;
							}
							//Figure out which eContent record this is for.
							require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
							$overDriveRecord = new OverDriveRecordDriver($checkout->recordId);
							if ($overDriveRecord->isValid()) {
								$checkout->updateFromRecordDriver($overDriveRecord);
								$checkout->format = $checkout->getRecordFormatCategory();
							} else {
								//The title doesn't exist in the collection - this happens with Magazines right now (early 2021).
								//Load the title information from metadata, but don't link it.
								$overDriveMetadata = $this->getProductMetadata($patron->getHomeLibrary(), $setting, $checkout->recordId);
								if ($overDriveMetadata) {
									$checkout->format = $overDriveMetadata->mediaType;
									$checkout->coverUrl = $overDriveMetadata->images->cover150Wide->href;
									$checkout->title = $overDriveMetadata->title;
									$checkout->author = $overDriveMetadata->publisher;
									//Magazines link to the searchable record by the parent magazine title id
									if (!empty($overDriveMetadata->parentMagazineTitleId)) {
										require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
										$overDriveProduct = new OverDriveAPIProduct();
										$overDriveProduct->crossRefId = $overDriveMetadata->parentMagazineTitleId;
										if ($overDriveProduct->find(true)) {
											//we have the product, now we need to find the grouped work id
											require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
											$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
											$groupedWorkPrimaryIdentifier->type = 'overdrive';
											$groupedWorkPrimaryIdentifier->identifier = $overDriveProduct->overdriveId;
											if ($groupedWorkPrimaryIdentifier->find(true)) {
												require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
												$groupedWork = new GroupedWork();
												$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
												if ($groupedWork->find(true)) {
													$checkout->groupedWorkId = $groupedWork->permanent_id;
												}
											}
										}
									}
								}
							}
						}

						$key = $checkout->source . $checkout->sourceId . $checkout->userId;
						$checkedOutTitles[$key] = $checkout;
					}
				}
			}
		}

		return $checkedOutTitles;
	}

	private array $holds = [];

	/**
	 * @param User $patron
	 * @param bool $forSummary
	 * @return array
	 */
	public function getHolds(User $patron, bool $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		//Cache holds for the user just for this call.
		if (isset($this->holds[$patron->id])) {
			return $this->holds[$patron->id];
		}
		$holds = [
			'available' => [],
			'unavailable' => [],
		];
		if (!empty($this->settings)) {
			$settingsToCheck = [$this->settings->id => $this->settings];
		}else{
			$settingsToCheck = $this->getAvailableSettings();
		}
		foreach ($settingsToCheck as $setting) {
			if (!$this->isUserValidForOverDrive($setting, $patron)) {
				continue;
			}
			$url = $setting->patronApiUrl . '/v1/patrons/me/holds';
			$response = $this->_callPatronUrl($setting, $patron, $url, "getHolds");
			if ($response === false) {
				$this->incrementStat('numApiErrors');
				continue;
			}
			if (isset($response->holds)) {
				foreach ($response->holds as $curTitle) {
					$hold = new Hold();
					$hold->type = 'overdrive';
					$hold->source = 'overdrive';
					$hold->sourceId = $curTitle->reserveId . '_' . $setting->id;
					$hold->recordId = $curTitle->reserveId;
					$datePlaced = strtotime($curTitle->holdPlacedDate);
					if ($datePlaced) {
						$hold->createDate = $datePlaced;
					}
					$hold->holdQueueLength = $curTitle->numberOfHolds;
					$hold->position = $curTitle->holdListPosition;  // this is so that overdrive holds can be sorted by hold position with the IlS holds
					$hold->cancelable = true;
					$hold->available = isset($curTitle->actions->checkout);
					if ($hold->available) {
						$hold->expirationDate = strtotime($curTitle->holdExpires);
					} else {
						$hold->canFreeze = true;
						if (isset($curTitle->holdSuspension)) {
							$hold->frozen = true;
							$hold->status = "Frozen";
							if ($curTitle->holdSuspension->numberOfDays > 0) {
								$numDaysSuspended = $curTitle->holdSuspension->numberOfDays;
								$reactivateDate = DateUtils::addDays(date('m/d/Y'), $numDaysSuspended, "M d,Y");
								$hold->reactivateDate = strtotime($reactivateDate);
							}
						}
					}
					if (count($settingsToCheck) > 1) {
						$hold->collectionName = $setting->name;
					}

					$hold->userId = $patron->id;

					require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
					$overDriveRecordDriver = new OverDriveRecordDriver($hold->recordId);
					if ($overDriveRecordDriver->isValid()) {
						$hold->updateFromRecordDriver($overDriveRecordDriver);
					}

					$key = $hold->type . $hold->sourceId . $hold->userId;
					if ($hold->available) {
						$holds['available'][$key] = $hold;
					} else {
						$holds['unavailable'][$key] = $hold;
					}
				}
			}
		}

		if (!$forSummary) {
			$this->holds[$patron->id] = $holds;
		}
		return $holds;
	}

	/**
	 * Returns a summary of information about the user's account in OverDrive.
	 */
	public function getAccountSummary(User $user): AccountSummary {
		[
			$existingId,
			$summary,
		] = $user->getCachedAccountSummary('overdrive');

		if ($summary === null || isset($_REQUEST['reload'])) {
			//Get account information from api
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

		return $summary;
	}

	/**
	 * Attempts to place a hold for the patron.
	 *
	 * If the library has multiple OverDrive collections available, the driver should have the active settings
	 * set using a call to setSettings before calling this method.
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) : array {
		require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
		$recordDriver = new OverDriveRecordDriver($recordId);
		if (!$recordDriver->isValid()) {
			return [
				'success' => false,
				'message' => translate(['text'=>'This title no longer exists within the catalog.', 'isPublicFacing'=>true]),
			];
		}

		$bestItem = $recordDriver->getBestCirculationOption($patron);

		if ($bestItem == null) {
			global $interface;
			$readerName = $interface->getVariable('readerName');
			return [
				'success' => false,
				'message' => translate(['text'=>'Unable to find a record to place a hold on. Your account may not be valid for use with %1% or this title may have been withdrawn.', 1=>$readerName, 'isPublicFacing'=>true]),
			];
		}

		$patronHomeLibrary = $patron->getHomeLibrary();
		if (substr_count($bestItem->itemId, ':') >= 2) {
			list(, $settingIdForItem,) = explode(':', $bestItem->itemId);
			$librarySettings = $patronHomeLibrary->getLibraryOverdriveSetting($settingIdForItem);

		}else{
			$allLibrarySettings = $patronHomeLibrary->getLibraryOverdriveSettings();
			if (!empty($allLibrarySettings)) {
				$librarySettings = reset($allLibrarySettings);
			}
		}

		if (empty($librarySettings)) {
			return [
				'success' => false,
				'message' => translate(['text'=>'Unable to find information about the collection to place a hold for this title.', 'isPublicFacing'=>true]),
			];
		}
		$settings = $librarySettings->getOverDriveSettings();

		$url = $settings->patronApiUrl . '/v1/patrons/me/holds/' . $recordId;
		$params = [
			'reserveId' => $recordId,
			'emailAddress' => trim((empty($patron->overdriveEmail) ? $patron->email : $patron->overdriveEmail)),
		];
		$response = $this->_callPatronUrl($settings, $patron, $url, "placeHold", $params);

		$holdResult = [];
		$holdResult['success'] = false;

		// Store result for API or app use
		$holdResult['api'] = [];

		if (isset($response->holdListPosition)) {
			$this->trackUserUsageOfOverDrive($patron);
			$this->trackRecordHold($recordId);
			$this->incrementStat('numHoldsPlaced');

			$holdResult['success'] = true;
			$holdResult['message'] = "<p class='alert alert-success'>" . translate([
					'text' => 'Your hold was placed successfully.  You are number %1% on the wait list.',
					1 => $response->holdListPosition,
					'isPublicFacing' => true,
				]) . "</p>";
			$holdResult['hasWhileYouWait'] = false;

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Hold Placed Successfully',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Your hold was placed successfully.  You are number %1% on the wait list.',
				1 => $response->holdListPosition,
				'isPublicFacing' => true,
			]);
			$holdResult['api']['action'] = translate([
				'text' => 'Go to Holds',
				'isPublicFacing' => true,
			]);

			//Get the grouped work for the record
			global $library;
			if ($library->showWhileYouWait) {
				$groupedWorkId = $recordDriver->getPermanentId();
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
				$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
				$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

				global $interface;
				if (count($whileYouWaitTitles) > 0) {
					$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
					$holdResult['message'] .= '<h3>' . translate([
							'text' => 'While You Wait',
							'isPublicFacing' => true,
						]) . '</h3>';
					$holdResult['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
					$holdResult['hasWhileYouWait'] = true;
				}
			}

			$patron->clearCache();
			$patron->clearCachedAccountSummaryForSource('overdrive');
			$patron->forceReloadOfHolds();
		} else {
			$holdResult['message'] = translate([
				'text' => 'Sorry, but we could not place a hold for you on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['message'] .= "  $response->message";
			}

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Unable to place hold',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Sorry, but we could not place a hold for you on this title.',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numFailedHolds');
		}

		return $holdResult;
	}

	function freezeHold(User $patron, $overDriveId, $reactivationDate): array {
		//Figure out which collection the title is on hold in.
		if (str_contains($overDriveId, '_')){
			list ($overDriveId, $settingId) = explode('_', $overDriveId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		$url = $settings->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$params = [
			'emailAddress' => trim($patron->overdriveEmail),
		];
		if (empty($reactivationDate)) {
			$params['suspensionType'] = 'indefinite';
		} else {
			try {
				$numberOfDaysToSuspend = (new DateTime())->diff(new DateTime($reactivationDate))->days + 1;
				$params['suspensionType'] = 'limited';
				$params['numberOfDays'] = $numberOfDaysToSuspend;
			} /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
				return [
					'success' => false,
					'message' => 'Unable to determine reactivation date',
				];
			}

		}
		$response = $this->_callPatronUrl($settings, $patron, $url, "freezeHold", $params);

		$holdResult = [];
		$holdResult['success'] = false;

		if (isset($response->holdListPosition) && isset($response->holdSuspension)) {
			$this->incrementStat('numHoldsFrozen');
			$holdResult['success'] = true;
			$holdResult['message'] = translate([
				'text' => 'Your hold was frozen successfully.',
				'isPublicFacing' => true,
			]);

			// Store result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Hold frozen',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Your hold was frozen successfully.',
				'isPublicFacing' => true,
			]);

			$patron->forceReloadOfHolds();
		} else {
			$holdResult['message'] = translate([
				'text' => 'Sorry, but we could not freeze the hold on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['message'] .= "  $response->message";
			}

			// Store result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Unable to freeze hold',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Sorry, but we could not freeze the hold on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['api']['message'] .= "  $response->message";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();

		return $holdResult;
	}

	function thawHold(User $patron, $overDriveId): array {
		//Figure out which collection the title is on hold in.
		if (str_contains($overDriveId, '_')){
			list ($overDriveId, $settingId) = explode('_', $overDriveId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		$url = $settings->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$response = $this->_callPatronDeleteUrl($settings, $patron, $url, "thawHold");

		$holdResult = [];
		$holdResult['success'] = false;

		// Store result for API or app use
		$holdResult['api'] = [];

		if ($response === true) {
			$holdResult['success'] = true;
			$holdResult['message'] = translate([
				'text' => 'Your hold was thawed successfully.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Hold thawed',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Your hold was thawed successfully.',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsThawed');
			$patron->forceReloadOfHolds();
		} else {
			$holdResult['message'] = translate([
				'text' => 'Sorry, but we could not thaw the hold on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['message'] .= "  $response->message";
			}

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Unable to thaw hold',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Sorry, but we could not thaw the hold on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['api']['message'] .= "  $response->message";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();

		return $holdResult;
	}

	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false): array {
		//Figure out which collection the title is on hold in.
		if (str_contains($recordId, '_')){
			list ($overDriveId, $settingId) = explode('_', $recordId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
			$overDriveId = $recordId;
		}

		$url = $settings->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($settings, $patron, $url, "cancelHold");

		$cancelHoldResult = [];
		$cancelHoldResult['success'] = false;

		// Store result for API or app use
		$cancelHoldResult['api'] = [];

		if ($response === true) {
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = translate([
				'text' => 'Your hold was cancelled successfully.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$cancelHoldResult['api']['title'] = translate([
				'text' => 'Hold cancelled',
				'isPublicFacing' => true,
			]);
			$cancelHoldResult['api']['message'] = translate([
				'text' => 'Your hold was cancelled successfully.',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numHoldsCancelled');
			$patron->clearCachedAccountSummaryForSource('overdrive');
			$patron->forceReloadOfHolds();
		} else {
			$cancelHoldResult['message'] = translate([
				'text' => 'There was an error cancelling your hold.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$cancelHoldResult['message'] .= "  $response->message";
			}

			// Result for API or app use
			$cancelHoldResult['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$cancelHoldResult['api']['message'] = translate([
				'text' => 'There was an error cancelling your hold.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$cancelHoldResult['api']['message'] .= "  $response->message";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();
		return $cancelHoldResult;
	}

	/**
	 * Checkout a title from OverDrive
	 *
	 * @param string $titleId
	 * @param User $patron
	 *
	 * @return array results (success, message, noCopies)
	 */
	public function checkOutTitle($patron, $titleId) : array {
		//Figure out which collection the title is on hold in.
		$settingIdForItem = null;
		if (str_contains($titleId, '_')){
			list ($overDriveId, $settingIdForItem) = explode('_', $titleId);
			$settings = $this->getAvailableSettings()[$settingIdForItem];
			$patronHomeLibrary = $patron->getHomeLibrary();
			$librarySettings = $patronHomeLibrary->getLibraryOverdriveSetting($settingIdForItem);
		}else{
			$overDriveId = $titleId;
		}

		require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
		$recordDriver = new OverDriveRecordDriver($overDriveId);
		if (!$recordDriver->isValid()) {
			return [
				'success' => false,
				'message' => translate(['text'=>'This title no longer exists within the catalog.', 'isPublicFacing'=>true]),
			];
		}

		if ($settingIdForItem == null) {
			$bestItem = $recordDriver->getBestCirculationOption($patron);
			if ($bestItem == null) {
				global $interface;
				$readerName = $interface->getVariable('readerName');
				return [
					'success' => false,
					'message' => translate(['text'=>'Unable to find a record to checkout. Your account may not be valid for use with %1% or this title may have been withdrawn.', 1=>$readerName, 'isPublicFacing'=>true]),
				];
			}

			$patronHomeLibrary = $patron->getHomeLibrary();
			if (substr_count($bestItem->itemId, ':') >= 2) {
				list(, $settingIdForItem,) = explode(':', $bestItem->itemId);
				$librarySettings = $patronHomeLibrary->getLibraryOverdriveSetting($settingIdForItem);

			}else{
				$allLibrarySettings = $patronHomeLibrary->getLibraryOverdriveSettings();
				if (!empty($allLibrarySettings)) {
					$librarySettings = reset($allLibrarySettings);
				}
			}
		}

		if (empty($librarySettings)) {
			return [
				'success' => false,
				'message' => translate(['text'=>'Unable to find information about the collection to check out this title.', 'isPublicFacing'=>true]),
			];
		}
		$settings = $librarySettings->getOverDriveSettings();

		$url = $settings->patronApiUrl . '/v1/patrons/me/checkouts';
		$params = [
			'reserveId' => $overDriveId,
		];
		$response = $this->_callPatronUrl($settings, $patron, $url, "checkOutTitle", $params);

		$result = [];
		$result['success'] = false;

		// Store result for API or app use
		$result['api'] = [];

		//print_r($response);
		if (isset($response->expires)) {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Your title was checked out successfully. You may now download the title from your Account.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Checked out title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your title was checked out successfully. You may now download the title from your Account.',
				'isPublicFacing' => true,
			]);
			$result['api']['action'] = translate([
				'text' => 'Go to Checkouts',
				'isPublicFacing' => true,
			]);

			$this->trackUserUsageOfOverDrive($patron);
			$this->trackRecordCheckout($titleId);
			$this->incrementStat('numCheckouts');
			$patron->lastReadingHistoryUpdate = 0;
			$patron->update();
			$patron->clearCachedAccountSummaryForSource('overdrive');
			$patron->forceReloadOfCheckouts();
		} else {
			$this->incrementStat('numFailedCheckouts');
			$result['message'] = translate([
				'text' => 'Sorry, we could not checkout this title to you.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to checkout title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, we could not checkout this title to you. ',
				'isPublicFacing' => true,
			]);
			if (isset($response->errorCode) && $response->errorCode == 'PatronHasExceededCheckoutLimit') {
				$result['message'] .= "\r\n\r\n" . translate([
						'text' => 'You have reached the maximum number of OverDrive titles you can checkout one time.',
						'isPublicFacing' => true,
					]);

				// Result for API or app use
				$result['api']['message'] .= translate([
					'text' => "You have reached the maximum number of OverDrive titles you can checkout one time. ",
					'isPublicFacing' => true,
				]);

			} else {
				if (isset($response->message)) {
					$result['message'] .= "  $response->message";
				}
				if (isset($response->message)) {
					$result['api']['message'] .= "  $response->message";
				}
			}

			if ($response === false) {
				//Give more information about why it might have failed. I.e., expired card or too many fines
				$result['message'] = translate([
					'text' => 'Sorry, we could not checkout this title to you. Could not connect to OverDrive.',
					'isPublicFacing' => true,
				]);

				// Result for API or app use
				$result['api']['message'] = translate([
					'text' => 'Sorry, we could not checkout this title to you. Could not connect to OverDrive.',
					'isPublicFacing' => true,
				]);
			} elseif ((isset($response->errorCode) && ($response->errorCode == 'NoCopiesAvailable' || $response->errorCode == 'PatronHasExceededCheckoutLimit' || $response->errorCode == 'NoCopiesAvailable_AvailableInCpcForFastLaneMembersOnly'))) {
				$result['noCopies'] = true;
				$result['message'] .= "\r\n\r\n" . translate([
						'text' => 'Would you like to place a hold instead?',
						'isPublicFacing' => true,
					]);

				// Result for API or app use
				$result['api']['action'] = translate([
					'text' => "Place a Hold",
					'isPublicFacing' => true,
				]);
			} elseif ($response->errorCode == 'TitleAlreadyCheckedOut') {
				$result['message'] = translate([
						'text' => "This title is already checked out to you.",
						'isPublicFacing' => true,
					]) . " <a href='/MyAccount/CheckedOut' class='btn btn-info'>" . translate([
						'text' => "View In Account",
						'isPublicFacing' => true,
					]) . "</a>";

				// Result for API or app use
				$result['api']['message'] = translate([
					'text' => "This title is already checked out to you.",
					'isPublicFacing' => true,
				]);
				$result['api']['action'] = translate([
					'text' => "Go to Checkouts",
					'isPublicFacing' => true,
				]);
			} else {
				//Give more information about why it might have failed. I.e., expired card or too many fines
				$result['message'] .= ' ' . translate([
						'text' => 'Sorry, we could not checkout this title to you.  Please verify that your card has not expired and that you do not have excessive fines.',
						'isPublicFacing' => true,
					]);

				// Result for API or app use
				$result['api']['message'] .= ' ' . translate([
						'text' => 'Sorry, we could not checkout this title to you.  Please verify that your card has not expired and that you do not have excessive fines.',
						'isPublicFacing' => true,
					]);
			}

		}

		$patron->clearCache();
		return $result;
	}

	/**
	 * Returns a checkout within OverDrive
	 *
	 *  If the library has multiple OverDrive collections available, the driver should have the active settings
	 *  set using a call to setSettings before calling this method.
	 */
	public function returnCheckout(User $patron, string $overDriveId) : array {
		//Figure out which collection the title is on hold in.
		if (str_contains($overDriveId, '_')){
			list ($overDriveId, $settingId) = explode('_', $overDriveId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		$url = $settings->patronApiUrl . '/v1/patrons/me/checkouts/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($settings, $patron, $url, "returnCheckout");

		$cancelHoldResult = [];
		$cancelHoldResult['success'] = false;

		// Store result for API or app use
		$cancelHoldResult['api'] = [];

		if ($response === true) {
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = translate([
				'text' => 'Your item was returned successfully.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$cancelHoldResult['api']['title'] = translate([
				'text' => 'Title returned',
				'isPublicFacing' => true,
			]);
			$cancelHoldResult['api']['message'] = translate([
				'text' => 'Your item was returned successfully.',
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numEarlyReturns');

			$patron->clearCachedAccountSummaryForSource('overdrive');
			$patron->forceReloadOfCheckouts();
		} else {
			$cancelHoldResult['message'] = translate([
				'text' => 'There was an error returning this item.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				if ($response->message == "An unmapped error has occurred. '7'") {
					$cancelHoldResult['message'] .= "  " . translate([
						'text' => "An unmapped error has occurred. '7'",
						'isPublicFacing' => true,
					]);
				} else {
					$cancelHoldResult['message'] .= "  $response->message";
				}
			}

			// Result for API or app use
			$cancelHoldResult['api']['title'] = translate([
				'text' => 'Unable to return title',
				'isPublicFacing' => true,
			]);
			$cancelHoldResult['api']['message'] = translate([
				'text' => 'There was an error returning this item.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				if ($response->message == "An unmapped error has occurred. '7'") {
					$cancelHoldResult['api']['message'] .= "  " . translate([
						'text' => "An unmapped error has occurred. '7'",
						'isPublicFacing' => true,
					]);
				} else {
					$cancelHoldResult['api']['message'] .= "  $response->message";
				}
			}

			$this->incrementStat('numApiErrors');
		}

		$patron->clearCache();
		return $cancelHoldResult;
	}

	/** @var array Key = user id, value = boolean */
	private static array $validUsersOverDrive = [];

	public function isUserValidForOverDrive(OverDriveSetting $setting, User $user) : bool {
		global $timer;
		$userBarcode = $user->getBarcode();
		if (empty($userBarcode)) {
			return false;
		}
		if (!isset(OverDriveDriver::$validUsersOverDrive[$setting->id .  ':' . $userBarcode])) {
			$tokenData = $this->getPatronTokenData($setting, $user);
			$timer->logTime("Checked to see if the user $userBarcode is valid for OverDrive");
			$isValid = !empty($tokenData) && !isset($tokenData->error);
			OverDriveDriver::$validUsersOverDrive[$setting->id .  ':' . $userBarcode] = $isValid;
		}
		return OverDriveDriver::$validUsersOverDrive[$setting->id .  ':' . $userBarcode];
	}

	public function getDownloadLink(string $overDriveId, User $user) : array {
		$result = [];
		$result['success'] = false;

		//Figure out which collection the title is on hold in.
		if (str_contains($overDriveId, '_')){
			//Don't strip the setting from the overDrive ID since we need it later
			list (, $settingId) = explode('_', $overDriveId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		$showLibbyPromoSetting = $settings->showLibbyPromo;
		if ($showLibbyPromoSetting == 1) {
			$showLibbyPromo = "";
		} else {
			$showLibbyPromo = "?appPromoOverride=none";
		}
		$downloadRedirectUrl = $this->getDownloadRedirectUrl($user, $overDriveId, $showLibbyPromo);
		if ($downloadRedirectUrl) {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Select a format',
				'isPublicFacing' => true,
			]);
			$result['modalBody'] = "<iframe src='$downloadRedirectUrl' class='fulfillmentFrame'></iframe>";
			$result['downloadUrl'] = $downloadRedirectUrl; // for API access
			$this->incrementStat('numDownloads');
		} else {
			$result['message'] = translate([
				'text' => 'Unable to create download url',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	function getDownloadRedirectUrl($user, $overDriveId, $showLibbyPromo)  : ?string {
		//Figure out which collection the title is on hold in.
		if (str_contains($overDriveId, '_')){
			list ($overDriveId, $settingId) = explode('_', $overDriveId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		$url = $settings->patronApiUrl . "/v1/patrons/me/checkouts/$overDriveId" . $showLibbyPromo;
		$response = $this->_callPatronUrl($settings, $user, $url, "getDownloadRedirectUrl");
		if ($response === false) {
			//The user is not authorized to use OverDrive
			$this->incrementStat('numApiErrors');
			return null;
		}

		if (!empty($response->links)) {
			$apiUrl = $response->links->downloadRedirect->href;
			if (empty($apiUrl)) {
				return null;
			}
			$tokenData = $this->getPatronTokenData($settings, $user, true);
			if (empty($tokenData)) {
				return null;
			}
			$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;

			$apiHost = $this->getPatronApiHost($settings);

			$ch = curl_init($apiUrl);
			curl_setopt($ch, CURLOPT_ENCODING, "");
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				"Authorization: " . $authorizationData,
				"Host: $apiHost",
				"Accept: application/json, text/xml, text/html",
			]);

			$content = curl_exec($ch);
			$response = curl_getinfo($ch);
			$header = curl_getinfo($ch, CURLINFO_HEADER_OUT);
			$headers = explode("\n", $header);
			curl_close($ch);

			if ($response['http_code'] == 301 || $response['http_code'] == 302) {
				ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
				ExternalRequestLogEntry::logRequest('overdrive.getDownloadRedirectUrl', 'GET', $apiUrl, $headers, false, $response['http_code'], $content, []);
				return $response['redirect_url'];
			}

			if (preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match('/window\.location="(.*)"/i', $content, $value)) {
				ExternalRequestLogEntry::logRequest('overdrive.getDownloadRedirectUrl', 'GET', $apiUrl, $headers, false, $response['http_code'], $content, []);
				return $response['redirect_url'];
			} else {
				ExternalRequestLogEntry::logRequest('overdrive.getDownloadRedirectUrl', 'GET', $apiUrl, $headers, false, $response['http_code'], $content, []);
				return $response['url'];
			}
		}else{
			return null;
		}
	}

	public function hasNativeReadingHistory(): bool {
		return false;
	}

	public function hasFastRenewAll(): bool {
		return false;
	}

	/**
	 * Renew all titles currently checked out to the user.
	 * This is not currently implemented for OverDrive
	 */
	public function renewAll(User $patron) : array|false {
		return false;
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * If the library has multiple OverDrive collections available, the driver should have the active settings
	 * set using a call to setSettings before calling this method.
	 */
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null) : array {
		if (str_contains($recordId, '_')){
			list ($recordId, $settingId) = explode('_', $recordId);
			$settings = $this->getAvailableSettings()[$settingId];
		}else{
			$settings = $this->getActiveSettings();
		}

		//To renew, we actually just place another hold on the title.
		$url = $settings->patronApiUrl . '/v1/patrons/me/holds/' . $recordId;
		$params = [
			'reserveId' => $recordId,
			'emailAddress' => trim((empty($patron->overdriveEmail) ? $patron->email : $patron->overdriveEmail)),
		];
		$response = $this->_callPatronUrl($settings, $patron, $url, "renewCheckout", $params);

		$holdResult = [];
		$holdResult['success'] = false;

		if (isset($response->holdListPosition)) {
			$this->trackUserUsageOfOverDrive($patron);
			$this->trackRecordHold($recordId);

			$holdResult['success'] = true;
			$holdResult['message'] = "<p class='alert alert-success'>" . translate([
					'text' => 'Your title has been requested again, you are number %1% on the list.',
					1 => $response->holdListPosition,
					'isPublicFacing' => true,
				]) . "</p>";

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Renewed title',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => 'Your title has been requested again, you are number %1% on the list.',
				1 => $response->holdListPosition,
				'isPublicFacing' => true,
			]);

			$this->incrementStat('numRenewals');

			$patron->forceReloadOfCheckouts();
		} else {
			$holdResult['message'] = translate([
				'text' => 'Sorry, but we could not renew this title for you.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['message'] .= "  $response->message";
			}

			// Result for API or app use
			$holdResult['api']['title'] = translate([
				'text' => 'Unable to renew title',
				'isPublicFacing' => true,
			]);
			$holdResult['api']['message'] = translate([
				'text' => "Sorry, but we could not renew this title for you.",
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['api']['message'] .= "  $response->message";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();

		return $holdResult;
	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfOverDrive($user): void {
		require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
		$userUsage = new UserOverDriveUsage();
		$userOverDriveTracking = $user->userCookiePreferenceLocalAnalytics;
		global $aspenUsage;
		$userUsage->instance = $aspenUsage->getInstance();
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userOverDriveTracking) {
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
	 * @param $overDriveId
	 */
	function trackRecordCheckout($overDriveId): void {
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
		$recordUsage = new OverDriveRecordUsage();
		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->getInstance();
		$recordUsage->overdriveId = $overDriveId;
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
	 * @param $overDriveId
	 */
	function trackRecordHold($overDriveId): void {
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
		$recordUsage = new OverDriveRecordUsage();
		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->getInstance();
		$recordUsage->overdriveId = $overDriveId;
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

	function getOptions(User $patron) : array {
		$settings = $this->getActiveSettings();

		if (!$this->isUserValidForOverDrive($settings, $patron)) {
			return [];
		}

		$url = $settings->patronApiUrl . '/v1/patrons/me';
		$response = $this->_callPatronUrl($settings, $patron, $url, "getOptions");
		if ($response === false) {
			//The user is not authorized to use OverDrive
			return [];
		} else {
			$options = [
				'holdLimit' => $response->holdLimit,
				'checkoutLimit' => $response->checkoutLimit,
				'lendingPeriods' => [],
			];

			foreach ($response->lendingPeriods as $lendingPeriod) {
				$options['lendingPeriods'][$lendingPeriod->formatType] = [
					'formatType' => $lendingPeriod->formatType,
					'lendingPeriod' => $lendingPeriod->lendingPeriod,
				];
			}

			foreach ($response->actions as $action) {
				if (isset($action->editLendingPeriod)) {
					$formatClassField = null;
					$lendingPeriodField = null;
					foreach ($action->editLendingPeriod->fields as $field) {
						if ($field->name == 'formatClass') {
							$formatClassField = $field;
						} elseif ($field->name == 'lendingPeriodDays') {
							$lendingPeriodField = $field;
						}
					}
					if ($formatClassField != null && $lendingPeriodField != null) {
						$formatClass = $formatClassField->value;
						if ($formatClass == 'Periodicals') {
							$formatClass = 'Magazines';
						}

						$options['lendingPeriods'][$formatClass]['options'] = $lendingPeriodField->options;

						if ($formatClass == 'Magazines') {
							unset($options['lendingPeriods'][$formatClass]);
						}
					}
				}
			}
		}
		return $options;
	}

	function updateOptions(User $patron) : bool {
		$settingsToUpdate = $patron->getHomeLibrary()->getOverdriveSettings();
		foreach ($settingsToUpdate as $overDriveSetting) {
			if ($this->isUserValidForOverDrive($overDriveSetting, $patron)) {
				$existingOptions = $this->getOptions($patron);
				foreach ($existingOptions['lendingPeriods'] as $lendingPeriod) {
					if ($_REQUEST[$lendingPeriod['formatType'] . '_' . $overDriveSetting->id] != $lendingPeriod['lendingPeriod']) {
						$url = $overDriveSetting->patronApiUrl . '/v1/patrons/me';

						$formatClass = $lendingPeriod['formatType'];
						if ($formatClass == 'Magazines') {
							$formatClass = 'magazine-overdrive';
						}
						$params = [
							'formatClass' => strtolower($formatClass),
							'lendingPeriodDays' => $_REQUEST[$lendingPeriod['formatType'] . '_' . $overDriveSetting->id],
						];
						/** @noinspection PhpUnusedLocalVariableInspection */
						$response = $this->_callPatronUrl($overDriveSetting, $patron, $url, "updateOptions", $params, 'PUT');

						if ($this->lastHttpCode != 204) {
							return false;
						}
					}
				}
			}
		}

		$this->incrementStat('numOptionsUpdates');
		return true;
	}

	/**
	 * @param $curTitle
	 * @param Checkout $bookshelfItem
	 * @return Checkout
	 */
	private function loadCheckoutFormatInformation($curTitle, Checkout $bookshelfItem): Checkout {
		$bookshelfItem->allowDownload = true;
		if (isset($curTitle->formats)) {
			foreach ($curTitle->formats as $id => $format) {
				if ($format->formatType == 'ebook-overdrive' || $format->formatType == 'ebook-mediado') {
					$bookshelfItem->overdriveRead = true;
				} elseif ($format->formatType == 'audiobook-overdrive') {
					$bookshelfItem->overdriveListen = true;
				} elseif ($format->formatType == 'video-streaming') {
					$bookshelfItem->overdriveVideo = true;
				} elseif ($format->formatType == 'magazine-overdrive') {
					$bookshelfItem->overdriveMagazine = true;
					$bookshelfItem->allowDownload = false;
				} else {
					$bookshelfItem->selectedFormatName = $this->getFormatMap()[$format->formatType];
					$bookshelfItem->selectedFormatValue = $format->formatType;
				}
				$curFormat = [];
				$curFormat['id'] = $id;
				$curFormat['format'] = $format;
				$curFormat['name'] = $this->getFormatMap()[$format->formatType];
				if (isset($format->links->self)) {
					$curFormat['downloadUrl'] = $format->links->self->href . '/downloadlink';
				}
				if ($format->formatType != 'magazine-overdrive' && $format->formatType != 'ebook-overdrive' && $format->formatType != 'ebook-mediado' && $format->formatType != 'audiobook-overdrive' && $format->formatType != 'video-streaming') {
					$bookshelfItem->formats[] = $curFormat;
				} else {
					if (isset($curFormat['downloadUrl'])) {
						if ($format->formatType == 'ebook-overdrive' || $format->formatType == 'ebook-mediado' || $format->formatType == 'magazine-overdrive') {
							$bookshelfItem->overdriveReadUrl = $curFormat['downloadUrl'];
						} elseif ($format->formatType == 'video-streaming') {
							$bookshelfItem->overdriveVideoUrl = $curFormat['downloadUrl'];
						} else {
							$bookshelfItem->overdriveListenUrl = $curFormat['downloadUrl'];
						}
					}
				}
			}
		} elseif (!$curTitle->isFormatLockedIn && isset($curTitle->actions->format)) {
			foreach ($curTitle->actions->format->fields as $curFieldIndex => $curField) {
				if (isset($curField->options)) {
					foreach ($curField->options as $format) {
						if ($format == 'ebook-overdrive' || $format == 'ebook-mediado') {
							$bookshelfItem->overdriveRead = true;
						} elseif ($format == 'audiobook-overdrive') {
							$bookshelfItem->overdriveListen = true;
						} elseif ($format == 'video-streaming') {
							$bookshelfItem->overdriveVideo = true;
						} elseif ($format == 'magazine-overdrive') {
							$bookshelfItem->overdriveMagazine = true;
							$bookshelfItem->allowDownload = false;
						} else {
							$bookshelfItem->selectedFormatName = $this->getFormatMap()[$format];
							$bookshelfItem->selectedFormatValue = $format;
						}
						$curFormat = [];
						$curFormat['id'] = $curFieldIndex;
						$curFormat['format'] = $format;
						$curFormat['name'] = $this->getFormatMap()[$format];
						if (isset($format->links->downloadRedirect)) {
							$curFormat['downloadUrl'] = $format->links->downloadRedirect->href . '/downloadlink';
						}
						if ($format != 'magazine-overdrive' && $format != 'ebook-overdrive' && $format != 'ebook-mediado' && $format != 'audiobook-overdrive' && $format != 'video-streaming') {
							$bookshelfItem->formats[] = $curFormat;
						} else {
							if (isset($curFormat['downloadUrl'])) {
								if ($format == 'ebook-overdrive' || $format == 'ebook-mediado' || $format == 'magazine-overdrive') {
									$bookshelfItem->overdriveReadUrl = $curFormat['downloadUrl'];
								} elseif ($format == 'video-streaming') {
									$bookshelfItem->overdriveVideoUrl = $curFormat['downloadUrl'];
								} else {
									$bookshelfItem->overdriveListenUrl = $curFormat['downloadUrl'];
								}
							}
						}
					}
				}
			}
		}
		if (isset($curTitle->actions->format) && empty($bookshelfItem->selectedFormatValue)) {
			//Get the options for the format which includes the valid formats
			$formatField = null;
			foreach ($curTitle->actions->format->fields as $curField) {
				if ($curField->name == 'formatType') {
					$formatField = $curField;
					break;
				}
			}
			if (isset($formatField->options)) {
				foreach ($formatField->options as $format) {
					$curFormat = [];
					$curFormat['id'] = $format;
					$curFormat['name'] = $this->getFormatMap()[$format];
					$bookshelfItem->formats[] = $curFormat;
				}
			}
		}
		return $bookshelfItem;
	}

	public function setSettings(OverDriveSetting $activeSetting) : void {
		$this->settings = $activeSetting;
	}

	function incrementStat(string $fieldName) : void {
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveStats.php';
		$axis360Stats = new OverDriveStats();
		global $aspenUsage;
		$axis360Stats->instance = $aspenUsage->getInstance();
		$axis360Stats->year = date('Y');
		$axis360Stats->month = date('n');
		if ($axis360Stats->find(true)) {
			$axis360Stats->$fieldName++;
			$axis360Stats->update();
		} else {
			$axis360Stats->$fieldName = 1;
			$axis360Stats->insert();
		}
	}

	private function getPatronApiHost(OverDriveSetting $settings) : string {
		if (!isset($this->patronApiHost[$settings->id])) {
			$patronApiUrl = $settings->patronApiUrl;
			/** @noinspection HttpUrlsUsage */
			$patronApiHost = str_replace('http://', '', $patronApiUrl);
			$this->patronApiHost[$settings->id] = str_replace('https://', '', $patronApiHost);
		}
		return $this->patronApiHost[$settings->id];
	}

	private function getOverDriveApiHost(OverDriveSetting $settings) : string {
		if (!isset($this->overdriveApiHost[$settings->id])) {
			$patronApiHost = $this->getPatronApiHost($settings);
			if (str_contains($patronApiHost, 'integration')) {
				$this->overdriveApiHost[$settings->id] = 'integration.api.overdrive.com';
			} else {
				$this->overdriveApiHost[$settings->id] = 'api.overdrive.com';
			}
		}
		return $this->overdriveApiHost[$settings->id];
	}

	private function getClientAuthString(OverDriveSetting $settings, ?LibraryOverDriveSettings $librarySettings) : ?string {
		if (empty($librarySettings->clientKey) || empty($librarySettings->clientSecret)) {
			if (empty($settings->clientSecret) || empty($settings->clientKey)) {
				return null;
			}else{
				return $settings->clientKey . ':' . $settings->clientSecret;
			}
		}else{
			return $librarySettings->clientKey . ':' . $librarySettings->clientSecret;
		}
	}
}
