<?php
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class KohaRESTAPIClient {
	private AccountProfile $accountProfile;
	private $oAuthToken;
	private $basicAuthToken;
	private CurlWrapper $apiCurlWrapper;
	private $baseURL;
	private $defaultHeaders;
	private $authenticationMethod;

	public function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
		$this->baseURL = $this->getWebServiceURL();
		$this->defaultHeaders = [
			'User-Agent: Aspen Discovery',
			'Accept: */*',
			'Cache-Control: no-cache',
			'Content-Type: application/json;charset=UTF-8',
			'Host: ' . preg_replace('~http[s]?://~', '', $this->baseURL),
		];
		$this->apiCurlWrapper = new CurlWrapper();
		$this->apiCurlWrapper->setTimeout(30);
		$this->setAuthenticationMethod();
	}

	/**
	 * Makes an API call via POST method
	 *
	 * Makes an API call to Koha using POST method and get a response body as JSON on success
	 * or returns false if not.
	 * Recover specific data of it as properties.
	 *
	 * @param string $endpoint e.g "/api/v1/auth/password/validation"
	 * @param array $requestParameters e.g ['identifier' => $username,'password' => $password,]
	 * @param string $caller e.g "koha.PatronLogin"
	 * @param array $dataToSanitize e.g ['password' => $password]
	 * @param array|null $extraHeaders e.g ['x-koha-embed: +strings,extended_attributes']
	 *
	 * @return  mixed           Authorization token if successful.If an error occurs, return false.
	 * @access  public
	 */
	public function post(string $endpoint, array $requestParameters, string $caller, array $dataToSanitize = [], array $extraHeaders = null): mixed {
		// Preparing request
		$apiURL = $this->baseURL . $endpoint;
		$jsonEncodedParams = json_encode($requestParameters);
		$this->apiCurlWrapper->addCustomHeaders([
			$this->getAuthorizationHeader()
		], true);
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}
		//Getting response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'POST', $jsonEncodedParams);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		if ($response) {
			ExternalRequestLogEntry::logRequest($caller, 'POST', $apiURL, $this->apiCurlWrapper->getHeaders(), $jsonEncodedParams, $responseCode, $response, $dataToSanitize);
			return json_decode($response);
		} else {
			return false;
		}
	}

	/**
	 * Set authentication method
	 *
	 * Set the authentication method used by the account profile either if it is
	 * OAuth2 or Basic
	 *
	 * @return  void
	 * @access  private
	 */
	private function setAuthenticationMethod(): void {
		if (isset($this->accountProfile->oAuthClientId) && isset($this->accountProfile->oAuthClientSecret)) {
			$this->authenticationMethod = 'oauth';
		} else {
			$this->authenticationMethod = 'basic';
		}
	}

	/**
	 * Get authorization header
	 *
	 * Checks the authentication method used and returns a string with the corresponding header
	 *
	 * @return  string|bool           Authorization header if successful
	 *                                If an error occurs, return a AspenError
	 * @access  private
	 */
	private function getAuthorizationHeader(): mixed {
		if ($this->authenticationMethod == 'basic') {
			$header = 'Authorization: Basic ' . $this->basicAuthToken;
		} else {
			$oAuthToken = $this->getOAuthToken();
			if ($oAuthToken) {
				$this->oAuthToken = $oAuthToken;
				$header = 'Authorization: Bearer ' . $this->oAuthToken;
			} else {
				global $logger;
				$logger->log("Unable to retrieve OAuth2 token", Logger::LOG_ERROR);
				return $oAuthToken;
			}
		}
		return $header;
	}

	/**
	 * Get open authorization token
	 *
	 * Makes an API call and returns a new OAuth token if successful or
	 * false if not
	 *
	 * @return  mixed           Authorization token if successful. If an error occurs, return false.
	 * @access  private
	 */
	private function getOAuthToken(): mixed {
		// Preparing request
		$apiUrl = $this->baseURL . "/api/v1/oauth/token";
		$params = [
			'grant_type' => 'client_credentials',
			'client_id' => $this->accountProfile->oAuthClientId,
			'client_secret' => $this->accountProfile->oAuthClientSecret,
		];
		$this->apiCurlWrapper->addCustomHeaders([
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded',
		], false);
		//Getting response body
		$response = $this->apiCurlWrapper->curlPostPage($apiUrl, $params);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$json_response = json_decode($response);
		ExternalRequestLogEntry::logRequest('koha.getOAuthToken', 'POST', $apiUrl, $this->apiCurlWrapper->getHeaders(), json_encode($params), $responseCode, $response, ['client_secret' => $this->accountProfile->oAuthClientSecret]);
		if (!empty($json_response->access_token)) {
			$oAuthToken = $json_response->access_token;
		} else {
			$oAuthToken = false;
		}
		return $oAuthToken;
	}

	/**
	 * Get basic authorization token
	 *
	 * Makes an API call and returns a new Basic Auth token if successful or
	 * false if not
	 *
	 * @return  string           Authorization token if successful
	 * @access  private
	 */
	private function getBasicAuthToken(): string {
		$client = UserAccount::getActiveUserObj();
		$client_id = $client->getBarcode();
		$client_secret = $client->getPasswordOrPin();
		$this->basicAuthToken = base64_encode($client_id . ":" . $client_secret);
		return $this->basicAuthToken;
	}


	/**
	 * Get web service URL
	 *
	 * Get currently Koha instance URL on success
	 *
	 * @return  string|bool           Web service URL if successful
	 *                                If an error occurs, return false
	 * @access  private
	 */
	private function getWebServiceURL(): bool|string {
		if (!empty($this->accountProfile->patronApiUrl)) {
			$webServiceURL = trim($this->accountProfile->patronApiUrl);
			$this->baseURL = rtrim($webServiceURL, '/'); // remove any trailing slash because other functions will add it.
			return $this->baseURL;
		} else {
			global $logger;
			$logger->log('No Web Service URL defined in account profile', Logger::LOG_ALERT);
		}
		return $this->baseURL;
	}
}