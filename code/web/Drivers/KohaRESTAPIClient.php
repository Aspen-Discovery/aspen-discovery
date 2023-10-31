<?php
require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';

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
	 * Makes an API call to Koha using POST method and get a response body and a response code on success
	 * Recover specific data of it as properties.
	 *
	 * @param string $endpoint e.g "/api/v1/auth/password/validation"
	 * @param array $requestParameters e.g ['identifier' => $username,'password' => $password,]
	 * @param string $caller e.g "koha.PatronLogin"
	 * @param array $dataToSanitize e.g ['password' => $password]
	 * @param array|null $extraHeaders e.g ['x-koha-embed: +strings,extended_attributes']
	 *
	 * @return  array     An array containing the response body and the response code obtained by the request.
	 * @access  public
	 */
	public function post(string $endpoint, array $requestParameters, string $caller, array $dataToSanitize = [], array $extraHeaders = null): array {
		// Preparing request
		$apiURL = $this->baseURL . $endpoint;
		$jsonEncodedParams = json_encode($requestParameters);
		$this->apiCurlWrapper->addCustomHeaders([
			$this->getAuthorizationHeader($caller)
		], true);
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}
		//Getting response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'POST', $jsonEncodedParams);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);
		ExternalRequestLogEntry::logRequest($caller, 'POST', $apiURL, $this->apiCurlWrapper->getHeaders(), $jsonEncodedParams, $responseCode, $response, $dataToSanitize);
		return [
			'body' => $jsonResponse,
			'code' => $responseCode,
			'url' => $apiURL,
			'headers' => $this->apiCurlWrapper->getHeaders(),
		];
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
	 * @return  string|bool           Authorization header if successful, otherwise returns false.
	 * @access  private
	 */
	private function getAuthorizationHeader($caller): mixed {
		if ($this->authenticationMethod == 'basic') {
			$basicToken = $this->getBasicAuthToken();
			$header = 'Authorization: Basic ' . $basicToken;
		} else {
			$oAuthToken = $this->getOAuthToken();
			if ($oAuthToken) {
				$this->oAuthToken = $oAuthToken;
				$header = 'Authorization: Bearer ' . $this->oAuthToken;
			} else {
				global $logger;
				//Special message case for patronLogin
				if (stripos($caller,"koha.patronLogin") !== false) {
					$logger->log("Unable to authenticate with the ILS from koha.patronLogin", Logger::LOG_ERROR);
				} else {
					$logger->log("Unable to retrieve OAuth2 token from " . $caller, Logger::LOG_ERROR);
				}
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
	 * @return  mixed           Authorization token if successful, otherwise returns false.
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
		$jsonResponse = json_decode($response);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('koharestapiclient.getOAuthToken', 'POST', $apiUrl, $this->apiCurlWrapper->getHeaders(), json_encode($params), $responseCode, $response, ['client_secret' => $this->accountProfile->oAuthClientSecret]);
		if (!empty($jsonResponse->access_token)) {
			$oAuthToken = $jsonResponse->access_token;
		} else {
			$oAuthToken = false;
		}
		return $oAuthToken;
	}

	/**
	 * Get basic authorization token
	 *
	 * Makes an API call and returns a new Basic Auth token.
	 *
	 * @return  string           Authorization basic token.
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
	 * @return  string|bool           Web service URL if successful, otherwise returns false.
	 *
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

	/**
	 * Json validator
	 *
	 * Checks if $response is a valid json
	 *
	 * @return  mixed    JSON decoded response if successful, otherwise returns the original response.
	 *
	 * @access  private
	 */
	private function jsonValidate($response): mixed {
		// decode the JSON data
		$jsonDecodedResponse = json_decode($response);
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = ''; // JSON is valid
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded.';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON.';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON.';
				break;
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_RECURSION:
				$error = 'One or more recursive references in the value to be encoded.';
				break;
			case JSON_ERROR_INF_OR_NAN:
				$error = 'One or more NAN or INF values in the value to be encoded.';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$error = 'A value of a type that cannot be encoded was given.';
				break;
			default:
				$error = 'Unknown JSON error occured.';
				break;
		}
		if ($error !== '') {
			global $logger;
			$logger->log('The JSON response has not been decoded correctly : ' . $error, Logger::LOG_WARNING);
			return $response;
		} else {
				return $jsonDecodedResponse;
		}
	}
}