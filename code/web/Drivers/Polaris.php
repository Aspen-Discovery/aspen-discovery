<?php


class Polaris extends AbstractIlsDriver
{
	//Caching of sessionIds by patron for performance (also stored within memcache)
	private static $accessKeysForUsers = array();

	/** @var CurlWrapper */
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Polaris Driver");
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->apiCurlWrapper = null;
	}

	public function hasNativeReadingHistory()
	{
		// TODO: Implement hasNativeReadingHistory() method.
	}

	public function getCheckouts(User $user)
	{
		// TODO: Implement getCheckouts() method.
	}

	public function hasFastRenewAll()
	{
		// TODO: Implement hasFastRenewAll() method.
	}

	public function renewAll($patron)
	{
		// TODO: Implement renewAll() method.
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	public function getHolds($user)
	{
		// TODO: Implement getHolds() method.
	}

	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		// TODO: Implement placeHold() method.
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		// TODO: Implement cancelHold() method.
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$barcodesToTest = array();
		$barcodesToTest[] = $username;
		$barcodesToTest[] = preg_replace('/[^a-zA-Z\d]/', '', trim($username));
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library) {
			if ($library->barcodePrefix) {
				if (strpos($username, $library->barcodePrefix) !== 0) {
					//Add the barcode prefix to the barcode
					$barcodesToTest[] = $library->barcodePrefix . $username;
				}
			}
		}

		foreach ($barcodesToTest as $i => $barcode) {
			list($userValid, $accessKey, $polarisId) = $this->loginViaWebService($username, $password);

			if ($userValid){
				//Load user data
			}
		}
	}

	/**
	 * @param $username
	 * @param $password
	 *
	 * @return [] - index 0 (login succeeds), index 1 (accessToken), index 2 (patron id)
	 */
	protected function loginViaWebService($username, $password)
	{
		global $memCache;
		global $library;
		$memCacheKey = "polaris_access_token_info_{$library->libraryId}_$username";
		$accessTokenInfo = $memCache->get($memCacheKey);
		if ($accessTokenInfo != false) {
			list(, $accessToken, $polarisID) = $accessTokenInfo;
			Polaris::$accessKeysForUsers[$polarisID] = $accessToken;
		} else {
			$session = array(false, false, false);
			$authenticationData = new stdClass();
			$authenticationData->Barcode = $username;
			$authenticationData->Password = $password;

			$body = json_encode($authenticationData);
			$authenticationResponseRaw = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/authenticator/patron', 'POST', '', $body);
			if ($authenticationResponseRaw) {
				$authenticationResponse = json_decode($authenticationResponseRaw);
				if ($authenticationResponse->PAPIErrorCode == 0){
					$accessToken = $authenticationResponse->AccessToken;
					$patronId = $authenticationResponse->PatronID;
					Polaris::$accessKeysForUsers[$patronId] = $accessToken;
					$session = array(true, $accessToken, $patronId);
					global $configArray;
					$memCache->set($memCacheKey, $session, $configArray['Caching']['sirsi_roa_session_token']);
				}else{
					global $logger;
					$logger->log($authenticationResponse->ErrorMessage, Logger::LOG_ERROR);
					$logger->log(print_r($authenticationResponse, true), Logger::LOG_ERROR);
				}
			} else {
				global $logger;
				$errorMessage = 'Polaris Authentication Error: ' . $this->lastResponseCode;
				$logger->log($errorMessage, Logger::LOG_ERROR);
				$logger->log(print_r($authenticationResponseRaw, true), Logger::LOG_ERROR);
			}
		}
		return $session;
	}

	private function getAccessToken($patron)
	{
		$polarisUserId = $patron->username;

		//Get the session token for the user
		if (isset(Polaris::$accessKeysForUsers[$polarisUserId])) {
			return Polaris::$accessKeysForUsers[$polarisUserId];
		} else {
			list(, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			return $sessionToken;
		}
	}

	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		// TODO: Implement placeItemHold() method.
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		// TODO: Implement freezeHold() method.
	}

	function thawHold($patron, $recordId, $itemToThawId)
	{
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		// TODO: Implement changeHoldPickupLocation() method.
	}

	function updatePatronInfo($patron, $canUpdateContactInfo)
	{
		// TODO: Implement updatePatronInfo() method.
	}

	public function getFines($patron, $includeMessages = false)
	{
		// TODO: Implement getFines() method.
	}

	public function getWebServiceResponse($query, $method = 'GET', $patronPassword = '', $body = false){
		// auth has to be in GMT, otherwise use config-level TZ
		$site_config_TZ = date_default_timezone_get();
		date_default_timezone_set('GMT');
		$date = date("D, d M Y H:i:s T");
		date_default_timezone_set($site_config_TZ);

		$url = $this->getWebServiceURL() . $query;

		$signature_text = $method . $url . $date . $patronPassword;
		$signature = base64_encode(
			hash_hmac('sha1', $signature_text, $this->accountProfile->oAuthClientSecret, true)
		);

		$auth_token = "PWS {$this->accountProfile->oAuthClientId}:$signature";
		$this->apiCurlWrapper->addCustomHeaders([
			"Content-type: application/json",
			"Accept: application/json",
			"PolarisDate: $date",
			"Authorization: $auth_token"
		], true);

		$response = $this->apiCurlWrapper->curlSendPage($url, $method, $body);
		$this->lastResponseCode = $this->apiCurlWrapper->getResponseCode();

		return $response;
	}

	private $lastResponseCode;

}