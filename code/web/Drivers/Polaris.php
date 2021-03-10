<?php


class Polaris extends AbstractIlsDriver
{
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
//			$body = "<PatronAuthenticationData>
//				<Barcode>$username</Barcode>
//				<Password>$password</Password>
//			</PatronAuthenticationData>";
//			$authenticationResponse = $this->getWebServiceResponse('/PAPIService/REST/public/v1/1033/100/1/authenticator/patron', 'POST', '', $body);

			//staff authentication
			$body = "<AuthenticationData>
				<Domain>MAIN</Domain>
				<Username>$username</Username>
				<Password>$password</Password>
			</AuthenticationData>";
			$authenticationResponse = $this->getWebServiceResponse('/PAPIService/REST/protected/v1/1033/100/1/authenticator/staff', 'POST', '', $body);
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
			"Content-type: text/xml",
			"Accept: text/xml",
			"PolarisDate: $date",
			"Authorization: $auth_token"
		], true);

		/** @noinspection PhpUnusedLocalVariableInspection */
		$response = $this->apiCurlWrapper->curlSendPage($url, $method, $body);
		$responseCode = $this->apiCurlWrapper->getResponseCode();

		return $response;
	}

}