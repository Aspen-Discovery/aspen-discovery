<?php

/**
 * Complete integration via APIs including availability and account information.
 */
require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
require_once ROOT_DIR . '/sys/Utils/DateUtils.php';

class OverDriveDriver extends AbstractEContentDriver {
	public $version = 3;

	protected $requirePin;
	/** @var string */
	protected $ILSName;

	/** @var OverDriveScope */
	private $scope = null;
	/** @var OverDriveSetting */
	protected $settings = null;
	private $patronApiHost = null;
	private $overdriveApiHost = null;
	private $clientKey = null;
	private $clientSecret = null;
	protected $format_map = null;
	private $lastHttpCode;

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	public function initCurlWrapper() {
		$this->apiCurlWrapper = new CurlWrapper();
		$this->apiCurlWrapper->timeout = 5;
	}


	/** @var OverDriveDriver */
	private static $singletonDriver = null;

	/**
	 * @return OverDriveDriver
	 */
	public static function getOverDriveDriver() {
		if (OverDriveDriver::$singletonDriver == null) {
			OverDriveDriver::$singletonDriver = new OverDriveDriver();
		}
		return OverDriveDriver::$singletonDriver;
	}

	public function getFormatMap() {
		if ($this->format_map == null) {
			$readerName = $this->getReaderName();
			$this->format_map = [
				'ebook-epub-adobe' => 'Adobe EPUB eBook',
				'ebook-epub-open' => 'Open EPUB eBook',
				'ebook-pdf-adobe' => 'Adobe PDF eBook',
				'ebook-pdf-open' => 'Open PDF eBook',
				'ebook-kindle' => 'Kindle Book',
				'ebook-disney' => 'Disney Online Book',
				'ebook-overdrive' => "$readerName Read",
				'ebook-microsoft' => 'Microsoft eBook',
				'audiobook-wma' => "$readerName WMA Audiobook",
				'audiobook-mp3' => "$readerName MP3 Audiobook",
				'audiobook-streaming' => 'Streaming Audiobook',
				'music-wma' => "$readerName Music",
				'video-wmv' => "$readerName Video",
				'video-wmv-mobile' => "$readerName Video (mobile)",
				'periodicals-nook' => 'NOOK Periodicals',
				'audiobook-overdrive' => "$readerName Listen",
				'video-streaming' => "$readerName Video",
				'ebook-mediado' => 'MediaDo Reader',
				'magazine-overdrive' => "$readerName Magazine",
			];
		}
		return $this->format_map;
	}

	public function getReaderName() {
		global $library;
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
		$this->scope = new OverDriveScope();
		$this->scope->id = $library->overDriveScopeId;
		if ($this->scope->find(true)) {
			return $this->scope->readerName;
		}
		return 'Libby';
	}

	public function getSettings() {
		if ($this->settings == null) {
			try {
				//There can be multiple settings so we will get based on the library being used.
				//We may also want to do this based on the patron's home library?
				global $library;
				require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
				$this->scope = new OverDriveScope();
				$this->scope->id = $library->overDriveScopeId;
				if ($this->scope->find(true)) {
					require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
					$this->settings = new OverDriveSetting();
					$this->settings->id = $this->scope->settingId;
					if (!$this->settings->find(true)) {
						$this->settings = false;
					} else {
						if (empty($this->scope->clientKey)) {
							$this->clientKey = $this->settings->clientKey;
						} else {
							$this->clientKey = $this->scope->clientKey;
						}
						if (empty($this->scope->clientSecret)) {
							$this->clientSecret = $this->settings->clientSecret;
						} else {
							$this->clientSecret = $this->scope->clientSecret;
						}
					}
				} else {
					$this->settings = false;
					$this->scope = false;
				}
			} catch (Exception $e) {
				$this->settings = false;
				$this->scope = false;
			}

		}
		return $this->settings;
	}

	public function getProductUrl($crossRefId) {
		$settings = $this->getSettings();
		if ($settings != null) {
			$baseUrl = $settings->url;
			if (substr($baseUrl, -1) != '/') {
				$baseUrl .= '/';
			}
			$baseUrl .= 'media/' . $crossRefId;
		}else{
			$baseUrl = '';
		}
		return $baseUrl;
	}

	public function isCirculationEnabled() {
		$this->getSettings();
		if ($this->scope == null) {
			return false;
		} else {
			return $this->scope->circulationEnabled;
		}
	}

	public function getTokenData() {
		return $this->_connectToAPI(true, "getTokenData");
	}

	/**
	 * @param User $user
	 * @param bool $forceNewConnection
	 * @return bool|mixed
	 */
	public function getPatronTokenData($user, $forceNewConnection = false) {
		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($user)) {
			$userPin = $user->getPasswordOrPin();
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, "getPatronTokenData", $forceNewConnection);
		} else {
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, "getPatronTokenData", $forceNewConnection);
		}

		return $tokenData;
	}

	private function _connectToAPI($forceNewConnection, $methodName) {
		global $memCache;
		$settings = $this->getSettings();
		if ($settings == false) {
			return false;
		}
		$tokenData = $memCache->get('overdrive_token_' . $settings->id . '_' . $this->scope->id);
		if ($forceNewConnection || $tokenData == false) {
			if (!empty($this->clientKey) && !empty($this->clientSecret)) {

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
					CURLOPT_USERPWD => $this->clientKey . ':' . $this->clientSecret,
				];

				$response = $this->apiCurlWrapper->curlPostPage($url, $params, $curlOptions);
				ExternalRequestLogEntry::logRequest('overdrive.connectToAPI_' . $methodName, 'POST', $url, $this->apiCurlWrapper->getHeaders(), false, $this->apiCurlWrapper->getResponseCode(), $response, []);

				$tokenData = json_decode($response);
				if ($tokenData) {
					if (!isset($tokenData->error)) {
						$memCache->set('overdrive_token_' . $settings->id . '_' . $this->scope->id, $tokenData, $tokenData->expires_in - 10);
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

	private function _connectToPatronAPI(User $user, $patronBarcode, $patronPin, $methodName, $forceNewConnection = false) {
		global $memCache;
		global $timer;
		global $logger;
		$settings = $this->getSettings();
		if ($settings == false) {
			return false;
		}

		$patronTokenData = $memCache->get("overdrive_patron_token_{$settings->id}_{$this->scope->id}_$patronBarcode");
		if ($forceNewConnection || $patronTokenData == false) {
			$tokenData = $this->_connectToAPI($forceNewConnection, "connectToPatronAPI");
			$timer->logTime("Connected to OverDrive API");
			if ($tokenData) {
				$this->initCurlWrapper();
				$url = "https://oauth-patron.overdrive.com/patrontoken";
				$ch = curl_init("https://oauth-patron.overdrive.com/patrontoken");
				if (empty($settings->websiteId)) {
					if (IPAddress::showDebuggingInformation()) {
						$logger->log("Patron is not valid for OverDrive, website id is not set", Logger::LOG_ERROR);
					}
					return false;
				}
				$websiteId = $settings->websiteId;

				$ilsname = $this->getILSName($user);
				if (!$ilsname) {
					$logger->log("Patron is not valid for OverDrive, ILSName is not set", Logger::LOG_ERROR);
					return false;
				}

				if (empty($settings->clientSecret)) {
					$logger->log("Patron is not valid for OverDrive, ClientSecret is not set", Logger::LOG_ERROR);
					return false;
				}
				$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
				$this->apiCurlWrapper->setOption(CURLOPT_SSL_VERIFYPEER, false);
				$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);
				$encodedAuthValue = base64_encode($this->clientKey . ":" . $this->clientSecret);
				global $interface;
				$this->apiCurlWrapper->addCustomHeaders([
					"Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
					"Authorization: Basic " . $encodedAuthValue,
					"User-Agent: Aspen Discovery " . $interface->getVariable('gitBranch'),
				], true);

				$patronBarcode = urlencode($patronBarcode);
				if ($patronPin == null) {
					$postFields = "grant_type=password&username={$patronBarcode}&password=ignore&password_required=false&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				} else {
					$postFields = "grant_type=password&username={$patronBarcode}&password={$patronPin}&password_required=true&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}

				$content = $this->apiCurlWrapper->curlPostPage($url, $postFields);
				ExternalRequestLogEntry::logRequest('overdrive.connectToPatronAPI_' . $methodName, 'POST', $url, $this->apiCurlWrapper->getHeaders(), $postFields, $this->apiCurlWrapper->getResponseCode(), $content, ['password'=>$patronPin]);
				$timer->logTime("Logged $patronBarcode into OverDrive API");
				$patronTokenData = json_decode($content);
				$timer->logTime("Decoded return for login of $patronBarcode into OverDrive API");
				if ($patronTokenData) {
					if (isset($patronTokenData->error)) {
						if ($patronTokenData->error == 'unauthorized_client') { // login failure
							// patrons with too high a fine amount will get this result.
							$logger->log("Patron is not valid for OverDrive, patronTokenData returned unauthorized_client", Logger::LOG_ERROR);
							return false;
						} else {
							if (IPAddress::showDebuggingInformation()) {
								$logger->log("Patron $patronBarcode is not valid for OverDrive, { $patronTokenData->error}", Logger::LOG_ERROR);
							}
						}
					} else {
						if (property_exists($patronTokenData, 'expires_in')) {
							$memCache->set("overdrive_patron_token_{$settings->id}_{$this->scope->id}_$patronBarcode", $patronTokenData, $patronTokenData->expires_in - 10);
						} else {
							$this->incrementStat('numConnectionFailures');
						}
					}
				}
			} else {
				$logger->log("Could not connect to OverDrive", Logger::LOG_ERROR);
				$this->incrementStat('numConnectionFailures');
				return false;
			}
		}
		return $patronTokenData;
	}

	public function _callUrl($url, $methodName) {
		$tokenData = $this->_connectToAPI(false, "callUrl");
		if ($tokenData) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
			$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);
			global $interface;
			$this->apiCurlWrapper->addCustomHeaders([
				"Authorization: {$tokenData->token_type} {$tokenData->access_token}",
				"User-Agent: Aspen Discovery " . $interface->getVariable('gitBranch'),
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

	/**
	 * @param User $user
	 * @return string
	 */
	private function getILSName($user) {
		if (!isset($this->ILSName)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library;
			/** @var Library $patronHomeLibrary */
			$patronHomeLibrary = Library::getPatronHomeLibrary($user);
			if (!empty($patronHomeLibrary->getOverdriveScope()->authenticationILSName)) {
				$this->ILSName = $patronHomeLibrary->getOverdriveScope()->authenticationILSName;
			} elseif (!empty($library->getOverdriveScope()->authenticationILSName)) {
				$this->ILSName = $library->getOverdriveScope()->authenticationILSName;
			}
		}
		return $this->ILSName;
	}

	/**
	 * @param $user User
	 * @return bool
	 */
	private function getRequirePin($user) {
		if (!isset($this->requirePin)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library;
			$patronHomeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			if (!empty($patronHomeLibrary->getOverdriveScope()->requirePin)) {
				$this->requirePin = $patronHomeLibrary->getOverdriveScope()->requirePin;
			} elseif (isset($library->getOverdriveScope()->requirePin)) {
				$this->requirePin = $library->getOverdriveScope()->requirePin;
			} else {
				$this->requirePin = false;
			}
		}
		return $this->requirePin;
	}

	/**
	 * @param User $user
	 * @param $url
	 * @param array $postParams
	 * @param string $method
	 * @return bool|mixed
	 */
	public function _callPatronUrl($user, $url, $methodName, $postParams = null, $method = null) {
		global $configArray;

		$tokenData = $this->getPatronTokenData($user);
		if ($tokenData) {
			$patronApiHost = $this->getPatronApiHost();

			$this->initCurlWrapper();
			if (isset($tokenData->token_type) && isset($tokenData->access_token)) {
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				global $interface;
				$this->apiCurlWrapper->addCustomHeaders([
					"Authorization: $authorizationData",
					"User-Agent: Aspen Discovery " . $interface->getVariable('gitBranch'),
					"Host: $patronApiHost",
				], true);
			} else {
				//The user is not valid
				if (isset($configArray['Site']['debug']) && $configArray['Site']['debug'] == true) {
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
				//Convert post fields to json
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
				if ($response == false) {
					$logger->log("Failed to call overdrive url $url " . session_id() . " curl_exec returned false " . print_r($postParams, true), Logger::LOG_ERROR);
				} else {
					$logger->log("Failed to call overdrive url " . session_id() . print_r($response, true), Logger::LOG_ERROR);
				}

			}
		}
		return false;
	}

	/**
	 * @param User $user
	 * @param $url
	 * @return bool|mixed
	 */
	private function _callPatronDeleteUrl($user, $url, $methodName) {
		$tokenData = $this->getPatronTokenData($user);

		//TODO: Remove || true when oauth works
		if ($tokenData || true) {
			$this->initCurlWrapper();
			$this->apiCurlWrapper->setOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$this->apiCurlWrapper->setOption(CURLOPT_RETURNTRANSFER, true);
			$this->apiCurlWrapper->setOption(CURLOPT_FOLLOWLOCATION, 1);

			global $interface;
			if ($tokenData) {
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$patronApiHost = $this->getPatronApiHost();
				$this->apiCurlWrapper->addCustomHeaders([
					"Authorization: $authorizationData",
					"User-Agent: Aspen Discovery " . $interface->getVariable('gitBranch'),
					"Host: $patronApiHost",
				], true);
			} else {
				$this->apiCurlWrapper->addCustomHeaders([
					"User-Agent: Aspen Discovery " . $interface->getVariable('gitBranch'),
					"Host: {$this->getOverDriveApiHost()}",
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
		}
		return false;
	}

	public function getLibraryAccountInformation() {
		$libraryId = $this->getSettings()->accountId;
		return $this->_callUrl("https://{$this->getOverDriveApiHost()}/v1/libraries/$libraryId", "getLibraryAccountInformation");
	}

	public function getAdvantageAccountInformation() {
		$libraryId = $this->getSettings()->accountId;
		return $this->_callUrl("https://{$this->getOverDriveApiHost()}/v1/libraries/$libraryId/advantageAccounts", "getAdvantageAccountInformation");
	}

	public function getProductMetadata($overDriveId, $productsKey = null) {
		if ($productsKey == null) {
			$productsKey = $this->getSettings()->productsKey;
		}
		if (is_numeric($overDriveId)) {
			//This is a crossRefId, we need to search for the product by crossRefId to get the actual id
			$searchUrl = "https://{$this->getOverDriveApiHost()}/v1/collections/$productsKey/products?crossRefId=$overDriveId";
			$searchResults = $this->_callUrl($searchUrl, "getProductMetadata");
			if (!empty($searchResults->products) && count($searchResults->products) > 0) {
				$overDriveId = $searchResults->products[0]->id;
			}
		}
		$overDriveId = strtoupper($overDriveId);
		$metadataUrl = "https://{$this->getOverDriveApiHost()}/v1/collections/$productsKey/products/$overDriveId/metadata";
		return $this->_callUrl($metadataUrl, "getProductMetadata");
	}

	public function getProductAvailability($overDriveId, $productsKey = null) {
		if ($productsKey == null) {
			$productsKey = $this->getSettings()->productsKey;
		}
		$availabilityUrl = "https://{$this->getOverDriveApiHost()}/v2/collections/$productsKey/products/$overDriveId/availability";
		return $this->_callUrl($availabilityUrl, "getProductAvailability");
	}

	/**
	 * Loads information about items that the user has checked out in OverDrive
	 *
	 * @param User $patron
	 * @param bool $forSummary
	 * @return Checkout[]
	 */
	public function getCheckouts(User $patron, bool $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		global $logger;
		if (!$this->isUserValidForOverDrive($patron)) {
			return [];
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts';
		$response = $this->_callPatronUrl($patron, $url, "getCheckouts");
		if ($response == false) {
			//The user is not authorized to use OverDrive
			$this->incrementStat('numApiErrors');
			return [];
		}

		global $interface;
		$fulfillmentMethod = (string)$this->getSettings()->useFulfillmentInterface;
		$interface->assign('fulfillmentMethod', $fulfillmentMethod);

		$checkedOutTitles = [];
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
					$supplementalMaterial->sourceId = $curTitle->reserveId;
					$supplementalMaterial->recordId = $curTitle->reserveId;
					$supplementalMaterial->userId = $patron->id;
					$supplementalMaterial->isSupplemental = true;
					$supplementalMaterial = $this->loadCheckoutFormatInformation($curTitle, $supplementalMaterial);
					if (isset($supplementalMaterial->selectedFormatValue) && !empty($supplementalMaterial->selectedFormatValue)) {
						$parentCheckout->supplementalMaterials[] = $supplementalMaterial;
					}
				} else {
					//Load data from api
					$checkout->sourceId = $curTitle->reserveId;
					$checkout->recordId = $curTitle->reserveId;
					$checkout->dueDate = $curTitle->expires;
					$checkout->canRenew = false;
					try {
						$expirationDate = new DateTime($curTitle->expires);
						$checkout->dueDate = $expirationDate->getTimestamp();
						//If the title expires in less than 3 days we should be able to renew it
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
						$overDriveRecord = new OverDriveRecordDriver($checkout->sourceId);
						if ($overDriveRecord->isValid()) {
							$checkout->updateFromRecordDriver($overDriveRecord);
							$checkout->format = $checkout->getRecordFormatCategory();
						} else {
							//The title doesn't exist in the collection - this happens with Magazines right now (early 2021).
							//Load the title information from metadata, but don't link it.
							$overDriveMetadata = $this->getProductMetadata($checkout->sourceId);
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
		return $checkedOutTitles;
	}

	private $holds = [];

	/**
	 * @param User $patron
	 * @param bool $forSummary
	 * @return array
	 */
	public function getHolds($patron, $forSummary = false): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		//Cache holds for the user just for this call.
		if (isset($this->holds[$patron->id])) {
			return $this->holds[$patron->id];
		}
		$holds = [
			'available' => [],
			'unavailable' => [],
		];
		if (!$this->isUserValidForOverDrive($patron)) {
			return $holds;
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds';
		$response = $this->_callPatronUrl($patron, $url, "getHolds");
		if ($response == false) {
			$this->incrementStat('numApiErrors');
			return $holds;
		}
		if (isset($response->holds)) {
			foreach ($response->holds as $curTitle) {
				$hold = new Hold();
				$hold->type = 'overdrive';
				$hold->source = 'overdrive';
				$hold->sourceId = $curTitle->reserveId;
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

	function placeHold($user, $overDriveId, $pickupBranch = null, $cancelDate = null) {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId;
		$params = [
			'reserveId' => $overDriveId,
			'emailAddress' => trim((empty($user->overdriveEmail) ? $user->email : $user->overdriveEmail)),
		];
		$response = $this->_callPatronUrl($user, $url, "placeHold", $params);

		$holdResult = [];
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		// Store result for API or app use
		$holdResult['api'] = [];

		if (isset($response->holdListPosition)) {
			$this->trackUserUsageOfOverDrive($user);
			$this->trackRecordHold($overDriveId);
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
				require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
				$recordDriver = new OverDriveRecordDriver($overDriveId);
				if ($recordDriver->isValid()) {
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
			}

			$user->clearCache();
			$user->clearCachedAccountSummaryForSource('overdrive');
			$user->forceReloadOfHolds();
		} else {
			$holdResult['message'] = translate([
				'text' => 'Sorry, but we could not place a hold for you on this title.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$holdResult['message'] .= "  {$response->message}";
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
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$params = [
			'emailAddress' => trim($patron->overdriveEmail),
		];
		if (empty($reactivationDate)) {
			$params['suspensionType'] = 'indefinite';
		} else {
			//OverDrive always seems to place the suspension for 2 days less than it should be
			try {
				$numberOfDaysToSuspend = (new DateTime())->diff(new DateTime($reactivationDate))->days + 2;
				$params['suspensionType'] = 'limited';
				$params['numberOfDays'] = $numberOfDaysToSuspend;
			} catch (Exception $e) {
				return [
					'success' => false,
					'message' => 'Unable to determine reactivation date',
				];
			}

		}
		$response = $this->_callPatronUrl($patron, $url, "freezeHold", $params);

		$holdResult = [];
		$holdResult['success'] = false;
		$holdResult['message'] = '';

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
				$holdResult['message'] .= "  {$response->message}";
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
				$holdResult['api']['message'] .= "  {$response->message}";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();

		return $holdResult;
	}

	function thawHold(User $patron, $overDriveId): array {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$response = $this->_callPatronDeleteUrl($patron, $url, "thawHold");

		$holdResult = [];
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		// Store result for API or app use
		$holdResult['api'] = [];

		if ($response == true) {
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
				$holdResult['message'] .= "  {$response->message}";
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
				$holdResult['api']['message'] .= "  {$response->message}";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();

		return $holdResult;
	}

	/**
	 * @param User $patron
	 * @param string $overDriveId
	 * @return array
	 */
	function cancelHold($patron, $overDriveId, $cancelId = null, $isIll = false): array {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($patron, $url, "cancelHold");

		$cancelHoldResult = [];
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';

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
				$cancelHoldResult['message'] .= "  {$response->message}";
			}

			// Result for API or app use
			$cancelHoldResult['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$cancelHoldResult['api']['message'] = translate([
				'text' => 'There was an error cancelling your hold.',
				'isPublicFacing' => true,
			]);;
			if (isset($response->message)) {
				$cancelHoldResult['api']['message'] .= "  {$response->message}";
			}

			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();
		return $cancelHoldResult;
	}

	/**
	 * Checkout a title from OverDrive
	 *
	 * @param string $overDriveId
	 * @param User $patron
	 *
	 * @return array results (success, message, noCopies)
	 */
	public function checkOutTitle($patron, $overDriveId) {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts';
		$params = [
			'reserveId' => $overDriveId,
		];
		$response = $this->_callPatronUrl($patron, $url, "checkOutTitle", $params);

		$result = [];
		$result['success'] = false;
		$result['message'] = '';

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
			$this->trackRecordCheckout($overDriveId);
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
					$result['message'] .= "  {$response->message}";
				}
				if (isset($response->message)) {
					$result['api']['message'] .= "  {$response->message}";
				}
			}

			if ($response == false) {
				//Give more information about why it might have failed, ie expired card or too many fines
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
				//Give more information about why it might have failed, ie expired card or too many fines
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
	 * @param $overDriveId
	 * @param User $patron
	 * @return array
	 */
	public function returnCheckout($patron, $overDriveId) {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($patron, $url, "returnCheckout");

		$cancelHoldResult = [];
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';

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
					$cancelHoldResult['message'] .= "  {$response->message}";
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
					$cancelHoldResult['api']['message'] .= "  {$response->message}";
				}
			}

			$this->incrementStat('numApiErrors');
		}

		$patron->clearCache();
		return $cancelHoldResult;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $patron) {
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts/' . $overDriveId . '/formats';
		$params = [
			'reserveId' => $overDriveId,
			'formatType' => $formatId,
		];
		$response = $this->_callPatronUrl($patron, $url, "selectOverDriveDownloadFormat", $params);
		//print_r($response);

		$result = [];
		$result['success'] = false;
		$result['message'] = '';

		if (isset($response->linkTemplates->downloadLink)) {
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'This format was locked in',
				'isPublicFacing' => true,
			]);
			$downloadLink = $this->getDownloadLink($overDriveId, $formatId, $patron);
			$result = $downloadLink;
			$patron->forceReloadOfCheckouts();
		} else {
			$result['message'] = translate([
				'text' => 'Sorry, but we could not select a format for you.',
				'isPublicFacing' => true,
			]);
			if (isset($response->message)) {
				$result['message'] .= "  {$response->message}";
			}
			$this->incrementStat('numApiErrors');
		}

		return $result;
	}

	/** @var array Key = user id, value = boolean */
	private static $validUsersOverDrive = [];

	/**
	 * @param $user  User
	 * @return bool
	 */
	public function isUserValidForOverDrive($user) {
		global $timer;
		$userBarcode = $user->getBarcode();
		if (!isset(OverDriveDriver::$validUsersOverDrive[$userBarcode])) {
			$tokenData = $this->getPatronTokenData($user, false);
			$timer->logTime("Checked to see if the user $userBarcode is valid for OverDrive");
			$isValid = ($tokenData !== false) && ($tokenData !== null) && !isset($tokenData->error);
			OverDriveDriver::$validUsersOverDrive[$userBarcode] = $isValid;
		}
		return OverDriveDriver::$validUsersOverDrive[$userBarcode];
	}

	public function getDownloadLink($overDriveId, $format, $user, $isSupplement = false) {
		global $configArray;
		$result = [];
		$result['success'] = false;
		$result['message'] = '';

		// check the value of useFulfillmentInterface
		$fulfillmentMethod = $this->getSettings()->useFulfillmentInterface;
		if ($fulfillmentMethod == 1 && $isSupplement == 0) {
			$showLibbyPromoSetting = $this->getSettings()->showLibbyPromo;
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
				$result['fulfillment'] = "redirect";
				$result['modalBody'] = "<iframe src='{$downloadRedirectUrl}' class='fulfillmentFrame'></iframe>";
				$result['downloadUrl'] = $downloadRedirectUrl; // for API access
				$this->incrementStat('numDownloads');
			} else {
				$result['message'] = translate([
					'text' => 'Unable to create download url',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$url = $this->getSettings()->patronApiUrl . "/v1/patrons/me/checkouts/{$overDriveId}/formats/{$format}/downloadlink";
			$url .= '?errorpageurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
			if ($format == 'ebook-overdrive' || $format == 'ebook-mediado') {
				$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
			} elseif ($format == 'audiobook-overdrive') {
				$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
			} elseif ($format == 'video-streaming') {
				$url .= '&errorurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
				$url .= '&streamingauthurl=' . urlencode($configArray['Site']['url'] . '/Help/streamingvideoauth');
			}

			$response = $this->_callPatronUrl($user, $url, "getDownloadLink");
			//print_r($response);

			$result = [];
			$result['success'] = false;
			$result['message'] = '';

			if (isset($response->links->contentlink)) {
				$result['success'] = true;
				$result['message'] = translate([
					'text' => 'Created Download Link',
					'isPublicFacing' => true,
				]);
				$result['downloadUrl'] = $response->links->contentlink->href;
				$result['fulfillment'] = "download";
				$this->incrementStat('numDownloads');
			} else {
				$result['message'] = translate([
					'text' => 'Sorry, but we could not get a download link for you.',
					'isPublicFacing' => true,
				]);
				if (isset($response->message)) {
					$result['message'] .= "  {$response->message}";
				}
				$this->incrementStat('numApiErrors');
			}
		}

		return $result;
	}

	function getDownloadRedirectUrl($user, $overDriveId, $showLibbyPromo) {

		$url = $this->getSettings()->patronApiUrl . "/v1/patrons/me/checkouts/$overDriveId" . $showLibbyPromo;
		$response = $this->_callPatronUrl($user, $url, "getDownloadRedirectUrl");
		if ($response == false) {
			//The user is not authorized to use OverDrive
			$this->incrementStat('numApiErrors');
			return [];
		}

		$apiUrl = $response->links->downloadRedirect->href;
		if (empty($apiUrl)) {
			return '';
		}
		$tokenData = $this->getPatronTokenData($user, true);
		$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;

		$apiHost = $this->getPatronApiHost();

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

		if (preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/window\.location\=\"(.*)\"/i", $content, $value)) {
			ExternalRequestLogEntry::logRequest('overdrive.getDownloadRedirectUrl', 'GET', $apiUrl, $headers, false, $response['http_code'], $content, []);
			return $response['redirect_url'];
		} else {
			ExternalRequestLogEntry::logRequest('overdrive.getDownloadRedirectUrl', 'GET', $apiUrl, $headers, false, $response['http_code'], $content, []);
			return $response['url'];
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
	 * This is not currently implemented
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
		//To renew, we actually just place another hold on the title.
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $recordId;
		$params = [
			'reserveId' => $recordId,
			'emailAddress' => trim((empty($patron->overdriveEmail) ? $patron->email : $patron->overdriveEmail)),
		];
		$response = $this->_callPatronUrl($patron, $url, "renewCheckout", $params);

		$holdResult = [];
		$holdResult['success'] = false;
		$holdResult['message'] = '';

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
				$holdResult['message'] .= "  {$response->message}";
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
				$holdResult['api']['message'] .= "  {$response->message}";
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
		$userObj = UserAccount::getActiveUserObj();
		$userOverDriveTracking = $userObj->userCookiePreferenceOverdrive;
		global $aspenUsage;
		global $library;
		$userUsage->instance = $aspenUsage->getInstance();
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');
		if ($userOverDriveTracking && $library->cookieStorageConsent) {
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

	function getOptions(User $patron) {
		if (!$this->isUserValidForOverDrive($patron)) {
			return [];
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me';
		$response = $this->_callPatronUrl($patron, $url, "getOptions");
		if ($response == false) {
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

						if($formatClass == 'Magazines') {
							unset($options['lendingPeriods'][$formatClass]);
						}
					}
				}
			}
		}
		return $options;
	}

	function updateOptions(User $patron) {
		if (!$this->isUserValidForOverDrive($patron)) {
			return false;
		}

		$existingOptions = $this->getOptions($patron);
		foreach ($existingOptions['lendingPeriods'] as $lendingPeriod) {
			if ($_REQUEST[$lendingPeriod['formatType']] != $lendingPeriod['lendingPeriod']) {
				$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me';

				$formatClass = $lendingPeriod['formatType'];
				if ($formatClass == 'Magazines') {
					$formatClass = 'magazine-overdrive';
				}
				$params = [
					'formatClass' => strtolower($formatClass),
					'lendingPeriodDays' => $_REQUEST[$lendingPeriod['formatType']],
				];
				$response = $this->_callPatronUrl($patron, $url, "updateOptions", $params, 'PUT');

				if ($this->lastHttpCode != 204) {
					return false;
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
		} elseif ($curTitle->isFormatLockedIn == false && isset($curTitle->actions->format)) {
			foreach ($curTitle->actions->format->fields as $curFieldIndex => $curField) {
				if (isset($curField->options)) {
					foreach ($curField->options as $index => $format) {
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
			foreach ($curTitle->actions->format->fields as $curFieldIndex => $curField) {
				if ($curField->name == 'formatType') {
					$formatField = $curField;
					break;
				}
			}
			if (isset($formatField->options)) {
				foreach ($formatField->options as $index => $format) {
					$curFormat = [];
					$curFormat['id'] = $format;
					$curFormat['name'] = $this->getFormatMap()[$format];
					$bookshelfItem->formats[] = $curFormat;
				}
				//}else{
				//No formats found for the title, do we need to do anything special?
			}
		}
		return $bookshelfItem;
	}

	/**
	 * @param OverDriveSetting $activeSetting
	 * @param OverDriveScope $activeScope
	 */
	public function setSettings($activeSetting, $activeScope) {
		$this->settings = $activeSetting;
		$this->scope = $activeScope;
		if (empty($this->scope->clientKey)) {
			$this->clientKey = $this->settings->clientKey;
		} else {
			$this->clientKey = $this->scope->clientKey;
		}
		if (empty($this->scope->clientSecret)) {
			$this->clientSecret = $this->settings->clientSecret;
		} else {
			$this->clientSecret = $this->scope->clientSecret;
		}
	}

	function incrementStat(string $fieldName) {
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

	private function getPatronApiHost() {
		if ($this->patronApiHost == null) {
			$patronApiUrl = $this->getSettings()->patronApiUrl;
			$patronApiHost = str_replace('http://', '', $patronApiUrl);
			$this->patronApiHost = str_replace('https://', '', $patronApiHost);
		}
		return $this->patronApiHost;
	}

	private function getOverDriveApiHost() {
		if ($this->overdriveApiHost == null) {
			$patronApiHost = $this->getPatronApiHost();
			if (strpos($patronApiHost, 'integration') !== false) {
				$this->overdriveApiHost = 'integration.api.overdrive.com';
			} else {
				$this->overdriveApiHost = 'api.overdrive.com';
			}
		}
		return $this->overdriveApiHost;
	}
}