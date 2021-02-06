<?php

/**
 * Complete integration via APIs including availability and account information.
 */
require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
require_once ROOT_DIR . '/sys/Utils/DateUtils.php';
class OverDriveDriver extends AbstractEContentDriver{
	public $version = 3;

	protected $requirePin;
	/** @var string */
	protected $ILSName;

	/** @var OverDriveSetting */
	private $scope = null;
	protected $settings = null;
	private $clientKey = null;
	private $clientSecret = null;

	protected $format_map = array(
		'ebook-epub-adobe' => 'Adobe EPUB eBook',
		'ebook-epub-open' => 'Open EPUB eBook',
		'ebook-pdf-adobe' => 'Adobe PDF eBook',
		'ebook-pdf-open' => 'Open PDF eBook',
		'ebook-kindle' => 'Kindle Book',
		'ebook-disney' => 'Disney Online Book',
		'ebook-overdrive' => 'OverDrive Read',
		'ebook-microsoft' => 'Microsoft eBook',
		'audiobook-wma' => 'OverDrive WMA Audiobook',
		'audiobook-mp3' => 'OverDrive MP3 Audiobook',
		'audiobook-streaming' => 'Streaming Audiobook',
		'music-wma' => 'OverDrive Music',
		'video-wmv' => 'OverDrive Video',
		'video-wmv-mobile' => 'OverDrive Video (mobile)',
		'periodicals-nook' => 'NOOK Periodicals',
		'audiobook-overdrive' => 'OverDrive Listen',
		'video-streaming' => 'OverDrive Video',
		'ebook-mediado' => 'MediaDo Reader',
		'magazine-overdrive' => 'OverDrive Magazine'
	);
	private $lastHttpCode;

	private function getSettings()
	{
		if ($this->settings == null) {
			try {
				//There can be multiple settings so we will get based on the library being used.
				//We may also want to do this based on the patron's home library?
				global $library;
				require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
				$this->scope = new OverDriveScope();
				$this->scope->id = $library->overDriveScopeId;
				if ($this->scope->find(true)){
					require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
					$this->settings = new OverDriveSetting();
					$this->settings->id = $this->scope->settingId;
					if (!$this->settings->find(true)) {
						$this->settings = false;
					}else{
						if (empty($this->scope->clientKey)){
							$this->clientKey = $this->settings->clientKey;
						}else{
							$this->clientKey = $this->scope->clientKey;
						}
						if (empty($this->scope->clientSecret)){
							$this->clientSecret = $this->settings->clientSecret;
						}else{
							$this->clientSecret = $this->scope->clientSecret;
						}
					}
				}else{
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

	private function _connectToAPI($forceNewConnection = false){
		global $memCache;
		$settings = $this->getSettings();
		if ($settings == false){
			return false;
		}
		$tokenData = $memCache->get('overdrive_token_' . $settings->id . '_' . $this->scope->id);
		if ($forceNewConnection || $tokenData == false){
			if (!empty($this->clientKey) && !empty($this->clientSecret)){
				$ch = curl_init("https://oauth.overdrive.com/token");
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
				curl_setopt($ch, CURLOPT_USERPWD, $this->clientKey . ":" . $this->clientSecret);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$return = curl_exec($ch);
				curl_close($ch);
				$tokenData = json_decode($return);
				if ($tokenData){
					$memCache->set('overdrive_token_' . $settings->id . '_' . $this->scope->id, $tokenData, $tokenData->expires_in - 10);
				}else{
					$this->incrementStat('numConnectionFailures');
				}
			}else{
				//OverDrive is not configured
				return false;
			}
		}
		return $tokenData;
	}

	private function _connectToPatronAPI($user, $patronBarcode, $patronPin, $forceNewConnection = false){
		global $memCache;
		global $timer;
		global $logger;
		$settings = $this->getSettings();
		if ($settings == false){
			return false;
		}
		$patronTokenData = $memCache->get("overdrive_patron_token_{$settings->id}_{$this->scope->id}_$patronBarcode");
		if ($forceNewConnection || $patronTokenData == false){
			$tokenData = $this->_connectToAPI($forceNewConnection);
			$timer->logTime("Connected to OverDrive API");
			if ($tokenData){
				$ch = curl_init("https://oauth-patron.overdrive.com/patrontoken");
				if (empty($settings->websiteId)){
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

				if (empty($settings->clientSecret)){
					$logger->log("Patron is not valid for OverDrive, ClientSecret is not set", Logger::LOG_ERROR);
					return false;
				}
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$encodedAuthValue = base64_encode($this->clientKey . ":" . $this->clientSecret);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
					"Authorization: Basic " . $encodedAuthValue,
					"User-Agent: Aspen Discovery"
				));
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
				curl_setopt($ch, CURLOPT_POST, 1);

				if ($patronPin == null){
					$postFields = "grant_type=password&username={$patronBarcode}&password=ignore&password_required=false&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}else{
					$postFields = "grant_type=password&username={$patronBarcode}&password={$patronPin}&password_required=true&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

				$return = curl_exec($ch);
				$timer->logTime("Logged $patronBarcode into OverDrive API");
				curl_close($ch);
				$patronTokenData = json_decode($return);
				$timer->logTime("Decoded return for login of $patronBarcode into OverDrive API");
				if ($patronTokenData){
					if (isset($patronTokenData->error)){
						if ($patronTokenData->error == 'unauthorized_client'){ // login failure
							// patrons with too high a fine amount will get this result.
							$logger->log("Patron is not valid for OverDrive, patronTokenData returned unauthorized_client", Logger::LOG_ERROR);
							return false;
						}else{
							if (IPAddress::showDebuggingInformation()){
								$logger->log("Patron $patronBarcode is not valid for OverDrive, { $patronTokenData->error}", Logger::LOG_ERROR);
							}
						}
					}else{
						if (property_exists($patronTokenData, 'expires_in')){
							$memCache->set("overdrive_patron_token_{$settings->id}_{$this->scope->id}_$patronBarcode", $patronTokenData, $patronTokenData->expires_in - 10);
						}else{
							$this->incrementStat('numConnectionFailures');
						}
					}
				}
			}else{
				$logger->log("Could not connect to OverDrive", Logger::LOG_ERROR);
				$this->incrementStat('numConnectionFailures');
				return false;
			}
		}
		return $patronTokenData;
	}

	public function _callUrl($url){
		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}", "User-Agent: Aspen Discovery"));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return null;
	}

	/**
	 * @param User $user
	 * @return string
	 */
	private function getILSName($user){
		if (!isset($this->ILSName)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library;
			/** @var Library $patronHomeLibrary */
			$patronHomeLibrary = Library::getPatronHomeLibrary($user);
			if (!empty($patronHomeLibrary->getOverdriveScope()->authenticationILSName)) {
				$this->ILSName = $patronHomeLibrary->getOverdriveScope()->authenticationILSName;
			}elseif (!empty($library->getOverdriveScope()->authenticationILSName)) {
				$this->ILSName = $library->getOverdriveScope()->authenticationILSName;
			}
		}
		return $this->ILSName;
	}

	/**
	 * @param $user User
	 * @return bool
	 */
	private function getRequirePin($user){
		if (!isset($this->requirePin)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library;
			$patronHomeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			if (!empty($patronHomeLibrary->getOverdriveScope()->requirePin)) {
				$this->requirePin = $patronHomeLibrary->getOverdriveScope()->requirePin;
			}elseif (isset($library->getOverdriveScope()->requirePin)) {
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
	public function _callPatronUrl($user, $url, $postParams = null, $method = null){
		global $configArray;

		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($user)){
			$userPin = $user->getPasswordOrPin();
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, false);
		}else{
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, false);
		}
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if (isset($tokenData->token_type) && isset($tokenData->access_token)){
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: Aspen Discovery",
					"Host: patron.api.overdrive.com" // production
					//"Host: integration-patron.api.overdrive.com" // testing
				);
			}else{
				//The user is not valid
				if (isset($configArray['Site']['debug']) && $configArray['Site']['debug'] == true){
					print_r($tokenData);
				}
				return false;
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			if ($postParams != null){
				curl_setopt($ch, CURLOPT_POST, 1);
				//Convert post fields to json
				$jsonData = array('fields' => array());
				foreach ($postParams as $key => $value){
					$jsonData['fields'][] = array(
						'name' => $key,
						'value' => $value
					);
				}
				$postData = json_encode($jsonData);
				//print_r($postData);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				$headers[] = 'Content-Type: application/vnd.overdrive.content.api+json';
			}else{
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			if (!empty($method)){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			}

			$return = curl_exec($ch);
			$returnVal = json_decode($return);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					curl_close($ch);
					return $returnVal;
				}
			}else{
				$results = curl_getinfo($ch);
				$this->lastHttpCode = $results['http_code'];
				global $logger;
				if ($return == false) {
					$logger->log("Failed to call overdrive url $url " . session_id() . " curl_exec returned false " . print_r($postParams, true), Logger::LOG_ERROR);
				}else{
					$logger->log("Failed to call overdrive url " . session_id() . print_r($return, true), Logger::LOG_ERROR);
				}

			}
			curl_close($ch);
		}
		return false;
	}

	/**
	 * @param User $user
	 * @param $url
	 * @return bool|mixed
	 */
	private function _callPatronDeleteUrl($user, $url){
		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($user)){
			$userPin = $user->getPasswordOrPin();
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, false);
		}else{
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, false);
		}
		//TODO: Remove || true when oauth works
		if ($tokenData || true){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if ($tokenData){
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: Aspen Discovery",
					"Host: patron.api.overdrive.com",
					//"Host: integration-patron.api.overdrive.com"
				);
			}else{
				$headers = array("User-Agent: Aspen Discovery", "Host: api.overdrive.com");
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$return = curl_exec($ch);
			$returnInfo = curl_getinfo($ch);
			if ($returnInfo['http_code'] == 204){
				$result = true;
			}else{
				//echo("Response code was " . $returnInfo['http_code']);
				$result = false;
			}
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}else{
				return $result;
			}
		}
		return false;
	}

	public function getLibraryAccountInformation(){
		$libraryId = $this->getSettings()->accountId;
		return $this->_callUrl("https://api.overdrive.com/v1/libraries/$libraryId");
	}

	public function getAdvantageAccountInformation(){
        $libraryId = $this->getSettings()->accountId;
		return $this->_callUrl("https://api.overdrive.com/v1/libraries/$libraryId/advantageAccounts");
	}

	public function getProductMetadata($overDriveId, $productsKey = null){
		if ($productsKey == null){
			$productsKey = $this->getSettings()->productsKey;
		}
		if (is_numeric($overDriveId)){
			//This is a crossRefId, we need to search for the product by crossRefId to get the actual id
			$searchUrl = "https://api.overdrive.com/v1/collections/$productsKey/products?crossRefId=$overDriveId";
			$searchResults = $this->_callUrl($searchUrl);
			if (!empty($searchResults->products) && count($searchResults->products) > 0){
				$overDriveId = $searchResults->products[0]->id;
			}
		}
		$overDriveId= strtoupper($overDriveId);
		$metadataUrl = "https://api.overdrive.com/v1/collections/$productsKey/products/$overDriveId/metadata";
		return $this->_callUrl($metadataUrl);
	}

	public function getProductAvailability($overDriveId, $productsKey = null){
		if ($productsKey == null){
			$productsKey = $this->getSettings()->productsKey;
		}
		$availabilityUrl = "https://api.overdrive.com/v2/collections/$productsKey/products/$overDriveId/availability";
		return $this->_callUrl($availabilityUrl);
	}

	private $checkouts = array();

	/**
	 * Loads information about items that the user has checked out in OverDrive
	 *
	 * @param User $patron
	 * @param bool $forSummary
	 * @return array
	 */
	public function getCheckouts($patron, $forSummary = false){
		if (isset($this->checkouts[$patron->id])){
			return $this->checkouts[$patron->id];
		}
		global $logger;
		if (!$this->isUserValidForOverDrive($patron)){
			return array();
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts';
		$response = $this->_callPatronUrl($patron, $url);
		if ($response == false){
			//The user is not authorized to use OverDrive
			$this->incrementStat('numApiErrors');
			return array();
		}

		$checkedOutTitles = [];
		$supplementalMaterialIds = [];
		if (isset($response->checkouts)){
			foreach ($response->checkouts as $curTitle){
				$checkout = array();
				if (isset($curTitle->links->bundledChildren)){
					foreach ($curTitle->links->bundledChildren as $bundledChild){
						if (preg_match('%.*/checkouts/(.*)%ix', $bundledChild->href, $matches)) {
							$supplementalMaterialIds[$matches[1]] = $curTitle->reserveId;
						}
					}
				}

				if (array_key_exists($curTitle->reserveId, $supplementalMaterialIds)){
					$parentCheckoutId = $supplementalMaterialIds[$curTitle->reserveId];
					$parentCheckout = $checkedOutTitles['OverDrive' . $parentCheckoutId];
					if (!isset($parentCheckout['supplementalMaterials'])) {
						$parentCheckout['supplementalMaterials'] = [];
					}
					$supplementalMaterial = [
						'checkoutSource' => 'OverDrive',
						'overDriveId' => $curTitle->reserveId,
						'isSupplemental' => true,
						'userId' => $patron->id,
					];
					$supplementalMaterial = $this->loadCheckoutFormatInformation($curTitle, $supplementalMaterial);
					if (isset($supplementalMaterial['selectedFormat']) && !empty($supplementalMaterial['selectedFormat']['format'])) {
						$parentCheckout['supplementalMaterials'][] = $supplementalMaterial;
					}
					$checkedOutTitles['OverDrive' . $parentCheckoutId] = $parentCheckout;
				}else{
					//Load data from api
					$checkout['checkoutSource'] = 'OverDrive';
					$checkout['overDriveId'] = $curTitle->reserveId;
					$checkout['expiresOn'] = $curTitle->expires;
					try {
						$expirationDate = new DateTime($curTitle->expires);
						$checkout['dueDate'] = $expirationDate->getTimestamp();
						//If the title expires in less than 3 days we should be able to renew it
						if ($expirationDate->getTimestamp() < time() + 3 * 24 * 60 * 60){
							$checkout['canRenew'] = true;
						}else{
							$checkout['canRenew'] = false;
						}
					} catch (Exception $e) {
						$logger->log("Could not parse date for overdrive expiration " . $curTitle->expires, Logger::LOG_NOTICE);
					}
					try {
						$checkOutDate = new DateTime($curTitle->checkoutDate);
						$checkout['checkoutDate'] = $checkOutDate->getTimestamp();
					} catch (Exception $e) {
						$logger->log("Could not parse date for overdrive checkout date " . $curTitle->checkoutDate, Logger::LOG_NOTICE);
					}
					$checkout['overdriveRead'] = false;
					if (isset($curTitle->isFormatLockedIn) && $curTitle->isFormatLockedIn == 1){
						$checkout['formatSelected'] = true;
					}else{
						$checkout['formatSelected'] = false;
					}
					$checkout['formats'] = array();
					if (!$forSummary){
						$checkout = $this->loadCheckoutFormatInformation($curTitle, $checkout);

						if (isset($curTitle->actions->earlyReturn)){
							$checkout['earlyReturn']  = true;
						}
						//Figure out which eContent record this is for.
						require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
						$overDriveRecord = new OverDriveRecordDriver($checkout['overDriveId']);
						if ($overDriveRecord->isValid()) {
							$checkout['recordId'] = $overDriveRecord->getUniqueID();
							$groupedWorkId = $overDriveRecord->getGroupedWorkId();
							if ($groupedWorkId != null) {
								$checkout['groupedWorkId'] = $overDriveRecord->getGroupedWorkId();
							}
							$formats = $overDriveRecord->getFormats();
							$checkout['groupedWorkId'] = $overDriveRecord->getPermanentId();
							$checkout['format'] = reset($formats);
							$checkout['coverUrl'] = $overDriveRecord->getBookcoverUrl('medium', true);
							$checkout['ratingData'] = $overDriveRecord->getRatingData();
							$checkout['recordUrl'] = $overDriveRecord->getLinkUrl(true);
							$checkout['title'] = $overDriveRecord->getTitle();
							$checkout['author'] = $overDriveRecord->getAuthor();
							$checkout['linkUrl'] = $overDriveRecord->getLinkUrl(false);
						}else{
							//The title doesn't exist in the collection - this happens with Magazines right now (early 2021).
							//Load the title information from metadata, but don't link it.
							$overDriveMetadata = $this->getProductMetadata($checkout['overDriveId']);
							if ($overDriveMetadata){
								$checkout['format'] = $overDriveMetadata->mediaType;
								$checkout['coverUrl'] = $overDriveMetadata->images->cover150Wide->href;
								$checkout['title'] = $overDriveMetadata->title;
								$checkout['author'] = $overDriveMetadata->publisher;
								//Magazines link to the searchable record by the parent magazine title id
								if (!empty($overDriveMetadata->parentMagazineTitleId)){
									require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
									$overDriveProduct = new OverDriveAPIProduct();
									$overDriveProduct->crossRefId = $overDriveMetadata->parentMagazineTitleId;
									if ($overDriveProduct->find(true)){
										//we have the product, now we need to find the grouped work id
										require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
										$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
										$groupedWorkPrimaryIdentifier->type = 'overdrive';
										$groupedWorkPrimaryIdentifier->identifier = $overDriveProduct->overdriveId;
										if ($groupedWorkPrimaryIdentifier->find(true)){
											require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
											$groupedWork = new GroupedWork();
											$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
											if ($groupedWork->find(true)){
												$checkout['groupedWorkId'] = $groupedWork->permanent_id;
												require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
												$groupedWorkDriver = new GroupedWorkDriver($groupedWork->permanent_id);
												$checkout['ratingData'] = $groupedWorkDriver->getRatingData();
												$checkout['recordUrl'] = $groupedWorkDriver->getLinkUrl(true);
												$checkout['linkUrl'] = $groupedWorkDriver->getLinkUrl(false);
											}
										}
									}
								}

							}
						}
					}
					$checkout['user'] = $patron->getNameAndLibraryLabel();
					$checkout['userId'] = $patron->id;

					$key = $checkout['checkoutSource'] . $checkout['overDriveId'];
					$checkedOutTitles[$key] = $checkout;
				}
			}
		}
		if (!$forSummary){
			$this->checkouts[$patron->id] = $checkedOutTitles;
		}
		return $checkedOutTitles;
	}

	private $holds = array();

	/**
	 * @param User $user
	 * @param bool $forSummary
	 * @return array
	 */
	public function getHolds($user, $forSummary = false){
		//Cache holds for the user just for this call.
		if (isset($this->holds[$user->id])){
			return $this->holds[$user->id];
		}
		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);
		if (!$this->isUserValidForOverDrive($user)){
			return $holds;
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds';
		$response = $this->_callPatronUrl($user, $url);
		if ($response == false){
			$this->incrementStat('numApiErrors');
			return $holds;
		}
		if (isset($response->holds)){
			foreach ($response->holds as $curTitle){
				$hold = array();
				$hold['overDriveId'] = $curTitle->reserveId;
				if (!empty($curTitle->emailAddress)){
					$hold['notifyEmail'] = $curTitle->emailAddress;
				}
				$datePlaced                = strtotime($curTitle->holdPlacedDate);
				if ($datePlaced) {
					$hold['create']            = $datePlaced;
				}
				$hold['holdQueueLength']   = $curTitle->numberOfHolds;
				$hold['holdQueuePosition'] = $curTitle->holdListPosition;
				$hold['position']          = $curTitle->holdListPosition;  // this is so that overdrive holds can be sorted by hold position with the IlS holds
				$hold['available']         = isset($curTitle->actions->checkout);
				if ($hold['available']){
					$hold['expire'] = strtotime($curTitle->holdExpires);
				}else{
					$hold['allowFreezeHolds'] = true;
					$hold['canFreeze'] = true;
					if (isset($curTitle->holdSuspension)){
						$hold['frozen'] = true;
						$hold['status'] = "Frozen";
						if ($curTitle->holdSuspension->numberOfDays > 0) {
							$numDaysSuspended = $curTitle->holdSuspension->numberOfDays;
							$hold['status'] .= ' until ' . DateUtils::addDays(date('m/d/Y'), $numDaysSuspended, "m/d/Y");
						}
					}
				}
				$hold['holdSource'] = 'OverDrive';

				//Figure out which eContent record this is for.
				require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
				if (!$forSummary){
					$overDriveRecord = new OverDriveRecordDriver($hold['overDriveId']);
					$hold['groupedWorkId'] = $overDriveRecord->getPermanentId();
					$hold['recordId'] = $overDriveRecord->getUniqueID();
					$hold['coverUrl'] = $overDriveRecord->getBookcoverUrl('medium', true);
					$hold['recordUrl'] = $overDriveRecord->getAbsoluteUrl();
					$hold['title'] = $overDriveRecord->getTitle();
					$hold['sortTitle'] = $overDriveRecord->getTitle();
					$hold['author'] = $overDriveRecord->getAuthor();
					$hold['linkUrl'] = $overDriveRecord->getLinkUrl(true);
					$hold['format'] = $overDriveRecord->getFormats();
					$hold['ratingData'] = $overDriveRecord->getRatingData();

					$hold['previewActions'] = $overDriveRecord->getPreviewActions();
				}
				$hold['user'] = $user->getNameAndLibraryLabel();
				$hold['userId'] = $user->id;

				$key = $hold['holdSource'] . $hold['overDriveId'] . $hold['user'];
				if ($hold['available']){
					$holds['available'][$key] = $hold;
				}else{
					$holds['unavailable'][$key] = $hold;
				}
			}
		}
		if (!$forSummary){
			$this->holds[$user->id] = $holds;
		}
		return $holds;
	}

	/**
	 * Returns a summary of information about the user's account in OverDrive.
	 *
	 * @param User $patron
	 *
	 * @return array
	 */
	public function getAccountSummary($patron){
		global $memCache;
		global $configArray;
		global $timer;

		if ($patron == false){
			return array(
				'numCheckedOut' => 0,
				'numOverdue' => 0,
				'numAvailableHolds' => 0,
				'numUnavailableHolds' => 0,
				'checkedOut' => array(),
				'holds' => array()
			);
		}

		$summary = $memCache->get('overdrive_summary_' . $patron->id);
		if ($summary == false || isset($_REQUEST['reload'])){

			//Get account information from api

			//TODO: Optimize so we don't need to load all checkouts and holds
			$summary = array();
			$checkedOutItems = $this->getCheckouts($patron, true);
			$summary['numCheckedOut'] = count($checkedOutItems);

			$holds = $this->getHolds($patron, true);
			$summary['numAvailableHolds'] = count($holds['available']);
			$summary['numUnavailableHolds'] = count($holds['unavailable']);

			$summary['checkedOut'] = $checkedOutItems;
			$summary['holds'] = $holds;

			$timer->logTime("Finished loading titles from overdrive summary");
			$memCache->set('overdrive_summary_' . $patron->id, $summary, $configArray['Caching']['account_summary']);
		}

		return $summary;
	}

	function placeHold($user, $overDriveId, $pickupBranch = null, $cancelDate = null)
	{
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId;
		$params = array(
			'reserveId' => $overDriveId,
			'emailAddress' => trim((empty($user->overdriveEmail) ? $user->email : $user->overdriveEmail))
		);
		$response = $this->_callPatronUrl($user, $url, $params);

		$holdResult = array();
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		if (isset($response->holdListPosition)){
			$this->trackUserUsageOfOverDrive($user);
			$this->trackRecordHold($overDriveId);
			$this->incrementStat('numHoldsPlaced');

			$holdResult['success'] = true;
			$holdResult['message'] = "<p class='alert alert-success'>" . translate(['text'=>'overdrive_hold_success', 'defaultText' => 'Your hold was placed successfully.  You are number %1% on the wait list.', 1=>$response->holdListPosition]) . "</p>";
			$holdResult['hasWhileYouWait'] = false;

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
						$holdResult['message'] .= '<h3>' . translate('While You Wait') . '</h3>';
						$holdResult['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
						$holdResult['hasWhileYouWait'] = true;
					}
				}
			}
		}else{
			$holdResult['message'] = translate('Sorry, but we could not place a hold for you on this title.');
			if (isset($response->message)) $holdResult['message'] .= "  {$response->message}";
			$this->incrementStat('numFailedHolds');
		}
		$user->clearCache();
		$memCache->delete('overdrive_summary_' . $user->id);

		return $holdResult;
	}

	function freezeHold(User $user, $overDriveId, $reactivationDate)
	{
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$params = array(
			'emailAddress' => trim($user->overdriveEmail)
		);
		if (empty($reactivationDate)){
			$params['suspensionType'] = 'indefinite';
		}else{
			//OverDrive always seems to place the suspension for 2 days less than it should be
			try {
				$numberOfDaysToSuspend = (new DateTime())->diff(new DateTime($reactivationDate))->days + 2;
				$params['suspensionType'] = 'limited';
				$params['numberOfDays'] = $numberOfDaysToSuspend;
			} catch (Exception $e) {
				return ['success'=>false,'message'=>'Unable to determine reactivation date'];
			}

		}
		$response = $this->_callPatronUrl($user, $url, $params);

		$holdResult = array();
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		if (isset($response->holdListPosition) && isset($response->holdSuspension)){
			$this->incrementStat('numHoldsFrozen');
			$holdResult['success'] = true;
			$holdResult['message'] = translate(['text'=>'overdrive_freeze_hold_success', 'defaultText' => 'Your hold was frozen successfully.']);
		}else{
			$holdResult['message'] = translate('Sorry, but we could not freeze the hold on this title.');
			if (isset($response->message)) $holdResult['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}
		$user->clearCache();
		$memCache->delete('overdrive_summary_' . $user->id);

		return $holdResult;
	}

	function thawHold(User $user, $overDriveId)
	{
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId . '/suspension';
		$response = $this->_callPatronDeleteUrl($user, $url);

		$holdResult = array();
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		if ($response == true){
			$holdResult['success'] = true;
			$holdResult['message'] = translate(['text'=>'overdrive_thaw_hold_success', 'defaultText' => 'Your hold was thawed successfully.']);
			$this->incrementStat('numHoldsThawed');
		}else{
			$holdResult['message'] = translate('Sorry, but we could not thaw the hold on this title.');
			if (isset($response->message)) $holdResult['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}
		$user->clearCache();
		$memCache->delete('overdrive_summary_' . $user->id);

		return $holdResult;
	}

	/**
	 * @param User $user
	 * @param string $overDriveId
	 * @return array
	 */
	function cancelHold($user, $overDriveId, $cancelId = null){
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($user, $url);

		$cancelHoldResult = array();
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = translate('Your hold was cancelled successfully.');
			$this->incrementStat('numHoldsCancelled');
		}else{
			$cancelHoldResult['message'] = translate('There was an error cancelling your hold.');
		    if (isset($response->message)) $cancelHoldResult['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}
		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $cancelHoldResult;
	}

	/**
	 * Checkout a title from OverDrive
	 *
	 * @param string $overDriveId
	 * @param User $user
	 *
	 * @return array results (success, message, noCopies)
	 */
	public function checkOutTitle($user, $overDriveId){
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts';
		$params = array(
			'reserveId' => $overDriveId,
		);
		$response = $this->_callPatronUrl($user, $url, $params);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		//print_r($response);
		if (isset($response->expires)) {
			$result['success'] = true;
			$result['message'] = translate(['text'=>'overdrive_checkout_success', 'defaultText'=>'Your title was checked out successfully. You may now download the title from your Account.']);
			$this->trackUserUsageOfOverDrive($user);
			$this->trackRecordCheckout($overDriveId);
			$this->incrementStat('numCheckouts');
			$user->lastReadingHistoryUpdate = 0;
			$user->update();
		}else{
			$this->incrementStat('numFailedCheckouts');
			$result['message'] = translate('Sorry, we could not checkout this title to you.');
			if (isset($response->errorCode) && $response->errorCode == 'PatronHasExceededCheckoutLimit'){
				$result['message'] .= "\r\n\r\n" . translate(['text'=>'overdrive_exceeded_checkouts', 'defaultText'=>'You have reached the maximum number of OverDrive titles you can checkout one time.']);
			}else{
				if (isset($response->message)) $result['message'] .= "  {$response->message}";
			}

			if ($response == false || (isset($response->errorCode) && ($response->errorCode == 'NoCopiesAvailable' || $response->errorCode == 'PatronHasExceededCheckoutLimit'))) {
				$result['noCopies'] = true;
				$result['message'] .= "\r\n\r\n" . translate('Would you like to place a hold instead?');
			}else if ($response->errorCode == 'TitleAlreadyCheckedOut') {
				$result['message'] = translate(['text' => 'overdrive_already_checked_out', 'defaultText' => "This title is already checked out to you, you can read it by visiting <a href='/MyAccount/CheckedOut'>Your Account</a>."]);
			}else{
				//Give more information about why it might gave failed, ie expired card or too much fines
				$result['message'] .= ' ' . translate(['text' => 'overdrive_checkout_failed', 'defaultText' => 'Sorry, we could not checkout this title to you.  Please verify that your card has not expired and that you do not have excessive fines.']);
			}

		}

		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $result;
	}

    /**
     * @param $overDriveId
     * @param User $user
     * @return array
     */
	public function returnCheckout($user, $overDriveId){
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts/' . $overDriveId;
		$response = $this->_callPatronDeleteUrl($user, $url);

		$cancelHoldResult = array();
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = translate('Your item was returned successfully.');
			$this->incrementStat('numEarlyReturns');
		}else{
			$cancelHoldResult['message'] =translate( 'There was an error returning this item.');
			if (isset($response->message)) $cancelHoldResult['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}

		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $cancelHoldResult;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $user){
		global $memCache;

		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/checkouts/' . $overDriveId . '/formats';
		$params = array(
			'reserveId' => $overDriveId,
			'formatType' => $formatId
		);
		$response = $this->_callPatronUrl($user, $url, $params);
		//print_r($response);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		if (isset($response->linkTemplates->downloadLink)){
			$result['success'] = true;
			$result['message'] = translate('This format was locked in');
			$downloadLink = $this->getDownloadLink($overDriveId, $formatId, $user);
			$result = $downloadLink;
		}else{
			$result['message'] = translate('Sorry, but we could not select a format for you.');
			if (isset($response->message)) $result['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}
		$memCache->delete('overdrive_summary_' . $user->id);

		return $result;
	}

	/** @var array Key = user id, value = boolean */
	private static $validUsersOverDrive = [];
	/**
	 * @param $user  User
	 * @return bool
	 */
	public function isUserValidForOverDrive($user){
		global $timer;
		$userBarcode = $user->getBarcode();
		if (!isset(OverDriveDriver::$validUsersOverDrive[$userBarcode])){
			if ($this->getRequirePin($user)){
				$userPin = $user->getPasswordOrPin();
				// determine which column is the pin by using the opposing field to the barcode. (between catalog password & username)
				$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, false);
			}else{
				$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, false);
			}
			$timer->logTime("Checked to see if the user $userBarcode is valid for OverDrive");
			$isValid = ($tokenData !== false) && ($tokenData !== null) && !array_key_exists('error', $tokenData);
			OverDriveDriver::$validUsersOverDrive[$userBarcode] = $isValid;
		}
		return OverDriveDriver::$validUsersOverDrive[$userBarcode];
	}

	public function getDownloadLink($overDriveId, $format, $user){
		global $configArray;

		$url = $this->getSettings()->patronApiUrl . "/v1/patrons/me/checkouts/{$overDriveId}/formats/{$format}/downloadlink";
		$url .= '?errorpageurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		if ($format == 'ebook-overdrive' || $format == 'ebook-mediado'){
			$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		}elseif ($format == 'audiobook-overdrive'){
			$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		}elseif ($format == 'video-streaming'){
			$url .= '&errorurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
			$url .= '&streamingauthurl=' . urlencode($configArray['Site']['url'] . '/Help/streamingvideoauth');
		}

		$response = $this->_callPatronUrl($user, $url);
		//print_r($response);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		if (isset($response->links->contentlink)){
			$result['success'] = true;
			$result['message'] = translate('Created Download Link');
			$result['downloadUrl'] = $response->links->contentlink->href;
			$this->incrementStat('numDownloads');
		}else{
			$result['message'] = translate('Sorry, but we could not get a download link for you.');
			if (isset($response->message)) $result['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}

		return $result;
	}

	public function hasNativeReadingHistory()
	{
		return false;
	}

	public function hasFastRenewAll()
	{
		return false;
	}

	/**
	 * Renew all titles currently checked out to the user.
	 * This is not currently implemented
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll($patron)
	{
		return false;
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @return mixed
	 */
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		global $memCache;

		//To renew, we actually just place another hold on the title.
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me/holds/' . $recordId;
		$params = array(
			'reserveId' => $recordId,
			'emailAddress' => trim((empty($patron->overdriveEmail) ? $patron->email : $patron->overdriveEmail))
		);
		$response = $this->_callPatronUrl($patron, $url, $params);

		$holdResult = array();
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		if (isset($response->holdListPosition)){
			$this->trackUserUsageOfOverDrive($patron);
			$this->trackRecordHold($recordId);

			$holdResult['success'] = true;
			$holdResult['message'] = "<p class='alert alert-success'>" . translate(['text'=>'overdrive_renew_success', 'defaultText' => 'Your title has been requested again, you are number %1% on the list.', 1=>$response->holdListPosition]) . "</p>";
			$this->incrementStat('numRenewals');
		}else{
			$holdResult['message'] = translate('Sorry, but we could not renew this title for you.');
			if (isset($response->message)) $holdResult['message'] .= "  {$response->message}";
			$this->incrementStat('numApiErrors');
		}
		$patron->clearCache();
		$memCache->delete('overdrive_summary_' . $patron->id);

		return $holdResult;
	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfOverDrive($user): void
	{
		require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
		$userUsage = new UserOverDriveUsage();
		$userUsage->instance = $_SERVER['SERVER_NAME'];
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userUsage->find(true)) {
			$userUsage->usageCount++;
			$userUsage->update();
		} else {
			$userUsage->usageCount = 1;
			$userUsage->insert();
		}
	}

	/**
	 * @param $overDriveId
	 */
	function trackRecordCheckout($overDriveId): void
	{
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
		$recordUsage = new OverDriveRecordUsage();
		$recordUsage->instance = $_SERVER['SERVER_NAME'];
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
	function trackRecordHold($overDriveId): void
	{
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
		$recordUsage = new OverDriveRecordUsage();
		$recordUsage->instance = $_SERVER['SERVER_NAME'];
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

	function getOptions(User $patron)
	{
		if (!$this->isUserValidForOverDrive($patron)){
			return array();
		}
		$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me';
		$response = $this->_callPatronUrl($patron, $url);
		if ($response == false){
			//The user is not authorized to use OverDrive
			return array();
		}else{
			$options = [
				'holdLimit' => $response->holdLimit,
				'checkoutLimit' => $response->checkoutLimit,
				'lendingPeriods' => []
			];

			foreach ($response->lendingPeriods as $lendingPeriod){
				$options['lendingPeriods'][$lendingPeriod->formatType] = [
					'formatType' => $lendingPeriod->formatType,
					'lendingPeriod' => $lendingPeriod->lendingPeriod
				];
			}

			foreach ($response->actions as $action){
				if (isset($action->editLendingPeriod)){
					$formatClassField = null;
					$lendingPeriodField = null;
					foreach($action->editLendingPeriod->fields as $field){
						if ($field->name == 'formatClass'){
							$formatClassField = $field;
						}elseif ($field->name == 'lendingPeriodDays'){
							$lendingPeriodField = $field;
						}
					}
					if ($formatClassField != null && $lendingPeriodField != null){
						$options['lendingPeriods'][$formatClassField->value]['options'] = $lendingPeriodField->options;
					}
				}
			}
		}
		return $options;
	}

	function updateOptions(User $patron) {
		if (!$this->isUserValidForOverDrive($patron)){
			return false;
		}

		$existingOptions = $this->getOptions($patron);
		foreach ($existingOptions['lendingPeriods'] as $lendingPeriod){
			if ($_REQUEST[$lendingPeriod['formatType']] != $lendingPeriod['lendingPeriod']){
				$url = $this->getSettings()->patronApiUrl . '/v1/patrons/me';

				$params = array(
					'formatClass' => strtolower($lendingPeriod['formatType']) ,
					'lendingPeriodDays' => $_REQUEST[$lendingPeriod['formatType']],
				);
				$this->_callPatronUrl($patron, $url, $params, 'PUT');

				if ($this->lastHttpCode != 204){
					return false;
				}
			}
		}
		$this->incrementStat('numOptionsUpdates');
		return true;
	}

	/**
	 * @param $curTitle
	 * @param array $bookshelfItem
	 * @return array
	 */
	private function loadCheckoutFormatInformation($curTitle, array $bookshelfItem): array
	{
		$bookshelfItem['allowDownload'] = true;
		if (isset($curTitle->formats)) {
			foreach ($curTitle->formats as $id => $format) {
				if ($format->formatType == 'ebook-overdrive' || $format->formatType == 'ebook-mediado') {
					$bookshelfItem['overdriveRead'] = true;
				} else if ($format->formatType == 'audiobook-overdrive') {
					$bookshelfItem['overdriveListen'] = true;
				} else if ($format->formatType == 'video-streaming') {
					$bookshelfItem['overdriveVideo'] = true;
				} else if ($format->formatType == 'magazine-overdrive') {
					$bookshelfItem['overdriveMagazine'] = true;
					$bookshelfItem['allowDownload'] = false;
				} else {
					$bookshelfItem['selectedFormat'] = array(
						'name' => $this->format_map[$format->formatType],
						'format' => $format->formatType,
					);
				}
				$curFormat = array();
				$curFormat['id'] = $id;
				$curFormat['format'] = $format;
				$curFormat['name'] = $this->format_map[$format->formatType];
				if (isset($format->links->self)) {
					$curFormat['downloadUrl'] = $format->links->self->href . '/downloadlink';
				}
				if ($format->formatType != 'magazine-overdrive' && $format->formatType != 'ebook-overdrive' && $format->formatType != 'ebook-mediado' && $format->formatType != 'audiobook-overdrive' && $format->formatType != 'video-streaming') {
					$bookshelfItem['formats'][] = $curFormat;
				} else {
					if (isset($curFormat['downloadUrl'])) {
						if ($format->formatType = 'ebook-overdrive' || $format->formatType == 'ebook-mediado' || $format->formatType == 'magazine-overdrive') {
							$bookshelfItem['overdriveReadUrl'] = $curFormat['downloadUrl'];
						} else if ($format->formatType == 'video-streaming') {
							$bookshelfItem['overdriveVideoUrl'] = $curFormat['downloadUrl'];
						} else {
							$bookshelfItem['overdriveListenUrl'] = $curFormat['downloadUrl'];
						}
					}
				}
			}
		}
		if (isset($curTitle->actions->format) && empty($bookshelfItem['formatSelected'])) {
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
					$curFormat = array();
					$curFormat['id'] = $format;
					$curFormat['name'] = $this->format_map[$format];
					$bookshelfItem['formats'][] = $curFormat;
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
	public function setSettings($activeSetting, $activeScope)
	{
		$this->settings = $activeSetting;
		$this->scope = $activeScope;
		if (empty($this->scope->clientKey)){
			$this->clientKey = $this->settings->clientKey;
		}else{
			$this->clientKey = $this->scope->clientKey;
		}
		if (empty($this->scope->clientSecret)){
			$this->clientSecret = $this->settings->clientSecret;
		}else{
			$this->clientSecret = $this->scope->clientSecret;
		}
	}

	function incrementStat(string $fieldName)
	{
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveStats.php';
		$axis360Stats = new OverDriveStats();
		$axis360Stats->instance = $_SERVER['SERVER_NAME'];
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
}
