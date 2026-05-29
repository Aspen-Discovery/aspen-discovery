<?php
require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';

class KohaApiUserAgent {
	private AccountProfile $accountProfile;
	private $basicAuthToken;
	private $oAuthToken;
	private CurlWrapper $apiCurlWrapper;
	private $baseURL;
	private $defaultHeaders;
	private $authenticationMethod;

	const memCacheKey = 'koha_api_access_token';


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
	* Performs an API call using the GET method.
	*
	* Sends a GET request to the specified endpoint of Koha,
	* and returns an array with :
	* - content
	* - status code
	* - url
	* - headers
	*
	* on success.
	* @param string			$endpoint			The API endpoint to call (e.g. "/api/v1/password/validation").
	* @param string			$caller				Identifier of the calling service (e.g. "koha.patronlogin").
	* @param array			$dataToSanitize		Associative array of data to sanitize (e.g. ['password' => $password]).
	* @param ?array			$extraHeaders		Optional headers to include in the request (e.g. ['Example: Example']).
	*
	* @return array{content: mixed, code: int, url: string, headers: array[string]}|false Returns an associative array with the response body and status code, or false on authorization failure.
	*
	* @access public
	*/
	public function get(string $endpoint, string $caller, array $dataToSanitize = [], ?array $extraHeaders = null): array|bool {

		// Prepare the request
		$apiURL = $this->baseURL . $endpoint;

		if ($this->getAuthorizationHeader($caller)) {
			$this->apiCurlWrapper->addCustomHeaders([
				$this->getAuthorizationHeader($caller)
			], true);
		} else {
			return false;
		}
		// Add default headers
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);

		// Add custom headers
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}

		// Get the response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'GET');
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);

		// Catch and save information about the API request
		ExternalRequestLogEntry::logRequest($caller, 'GET', $apiURL, $this->apiCurlWrapper->getHeaders(), '', $responseCode, $response, $dataToSanitize);

		return [
			'content' => $jsonResponse,
			'code' => $responseCode,
			'url' => $apiURL,
			'headers' => $this->apiCurlWrapper->getHeaders(),
		];
	}

	/**
	* Performs an API call using the POST method.
	*
	* Sends a POST request to the specified endpoint of Koha,
	* and returns an array with :
	* - content
	* - status code
	* - url
	* - headers
	*
	* on success.
	* @param string			$endpoint				The API endpoint to call (e.g. "/api/v1/password/validation").
	* @param array|object	$requestParameters		The requested parameters by the API endpoint.
	* @param string			$caller					Identifier of the calling service (e.g. "koha.patronlogin").
	* @param array			$dataToSanitize			Associative array of data to sanitize (e.g. ['password' => $password]).
	* @param ?array			$extraHeaders			Optional headers to include in the request (e.g. ['Example: Example']).
	*
	* @return array{content: mixed, code: int, url: string, headers: array[string]}|false Returns an associative array with the response body and status code, or false on authorization failure.
	*
	* @access public
	*/
	public function post(string $endpoint, array|object $requestParameters, string $caller, array $dataToSanitize = [], ?array $extraHeaders = null): array|bool {

		// Prepare the request
		$apiURL = $this->baseURL . $endpoint;
		$jsonEncodedParams = json_encode($requestParameters);
		
		if ($this->getAuthorizationHeader($caller)) {
			$this->apiCurlWrapper->addCustomHeaders([
				$this->getAuthorizationHeader($caller)
			], true);
		} else {
			return false;
		}

		// Add default headers
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);

		// Add custom headers
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}

		//Get the response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'POST', $jsonEncodedParams);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);

		// Catch and save information about the API request
		ExternalRequestLogEntry::logRequest($caller, 'POST', $apiURL, $this->apiCurlWrapper->getHeaders(), $jsonEncodedParams, $responseCode, $response, $dataToSanitize);
		return [
			'content' => $jsonResponse,
			'code' => $responseCode,
			'url' => $apiURL,
			'headers' => $this->apiCurlWrapper->getHeaders(),
		];
	}

	/**
	* Performs an API call using the PUT method.
	*
	* Sends a PUT request to the specified endpoint of Koha,
	* and returns an array with :
	* - content
	* - status code
	* - url
	* - headers
	*
	* on success.
	* @param string			$endpoint				The API endpoint to call (e.g. "/api/v1/password/validation").
	* @param array|object	$requestParameters		The requested parameters by the API endpoint.
	* @param string			$caller					Identifier of the calling service (e.g. "koha.patronlogin").
	* @param array			$dataToSanitize			Associative array of data to sanitize (e.g. ['password' => $password]).
	* @param ?array			$extraHeaders			Optional headers to include in the request (e.g. ['Example: Example']).
	*
	* @return array{content: mixed, code: int, url: string, headers: array[string]}|false Returns an associative array with the response body and status code, or false on authorization failure.
	*
	* @access public
	*/
	public function put(string $endpoint, array $requestParameters, string $caller, array $dataToSanitize = [], ?array $extraHeaders = null): array|bool {

		// Prepare the request
		$apiURL = $this->baseURL . $endpoint;
		$jsonEncodedParams = json_encode($requestParameters);

		if ($this->getAuthorizationHeader($caller)) {
			$this->apiCurlWrapper->addCustomHeaders([
				$this->getAuthorizationHeader($caller)
			], true);
		} else {
			return false;
		}

		// Add default headers
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);

		// Add custom headers
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}

		// Get the response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'PUT', $jsonEncodedParams);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);

		// Catch and save information about the API request
		ExternalRequestLogEntry::logRequest($caller, 'PUT', $apiURL, $this->apiCurlWrapper->getHeaders(), $jsonEncodedParams, $responseCode, $response, $dataToSanitize);
		return [
			'content' => $jsonResponse,
			'code' => $responseCode,
			'url' => $apiURL,
			'headers' => $this->apiCurlWrapper->getHeaders(),
		];
	}

	/**
	* Performs an API call using the PATCH method.
	*
	* Sends a PATCH request to the specified endpoint of Koha,
	* and returns an array with :
	* - content
	* - status code
	* - url
	* - headers
	*
	* on success.
	* @param string			$endpoint				The API endpoint to call (e.g. "/api/v1/password/validation").
	* @param array|object	$requestParameters		The requested parameters by the API endpoint.
	* @param string			$caller					Identifier of the calling service (e.g. "koha.patronlogin").
	* @param array			$dataToSanitize			Associative array of data to sanitize (e.g. ['password' => $password]).
	* @param ?array			$extraHeaders			Optional headers to include in the request (e.g. ['Example: Example']).
	*
	* @return array{content: mixed, code: int, url: string, headers: array[string]}|false Returns an associative array with the response body and status code, or false on authorization failure.
	*
	* @access public
	*/
	public function patch(string $endpoint, array $requestParameters, string $caller, array $dataToSanitize = [], ?array $extraHeaders = null): array|bool {
		
		// Prepare the request
		$apiURL = $this->baseURL . $endpoint;
		$jsonEncodedParams = json_encode($requestParameters);

		if ($this->getAuthorizationHeader($caller)) {
			$this->apiCurlWrapper->addCustomHeaders([
				$this->getAuthorizationHeader($caller)
			], true);
		} else {
			return false;
		}

		// Add default headers
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);

		// Add custom headers
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}

		// Get the response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'PATCH', $jsonEncodedParams);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);

		// Catch and save information about the API request
		ExternalRequestLogEntry::logRequest($caller, 'PATCH', $apiURL, $this->apiCurlWrapper->getHeaders(), $jsonEncodedParams, $responseCode, $response, $dataToSanitize);
		return [
			'content' => $jsonResponse,
			'code' => $responseCode,
			'url' => $apiURL,
			'headers' => $this->apiCurlWrapper->getHeaders(),
		];
	}

	/**
	* Performs an API call using the DELETE method.
	*
	* Sends a DELETE request to the specified endpoint of Koha,
	* and returns an array with :
	* - content
	* - status code
	* - url
	* - headers
	*
	* on success.
	* @param string			$endpoint				The API endpoint to call (e.g. "/api/v1/password/validation").
	* @param string			$caller					Identifier of the calling service (e.g. "koha.patronlogin").
	* @param array			$dataToSanitize			Associative array of data to sanitize (e.g. ['password' => $password]).
	* @param ?array			$extraHeaders			Optional headers to include in the request (e.g. ['Example: Example']).
	*
	* @return array{content: mixed, code: int, url: string, headers: array[string]}|false Returns an associative array with the response body and status code, or false on authorization failure.
	*
	* @access public
	*/
	public function delete(string $endpoint, string $caller, array $dataToSanitize = [], ?array $extraHeaders = null): array|bool {

		// Prepare the request
		$apiURL = $this->baseURL . $endpoint;

		if ($this->getAuthorizationHeader($caller)) {
			$this->apiCurlWrapper->addCustomHeaders([
				$this->getAuthorizationHeader($caller)
			], true);
		} else {
			return false;
		}

		// Add default headers
		$this->apiCurlWrapper->addCustomHeaders($this->defaultHeaders, false);

		// Add custom headers
		if (isset($extraHeaders)) {
			$this->apiCurlWrapper->addCustomHeaders($extraHeaders, false);
		}

		//Get the response body
		$response = $this->apiCurlWrapper->curlSendPage($apiURL, 'DELETE', '');
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		$jsonResponse = $this->jsonValidate($response);

		// Catch and save information about the API request
		ExternalRequestLogEntry::logRequest($caller, 'DELETE', $apiURL, $this->apiCurlWrapper->getHeaders(), '', $responseCode, $response, $dataToSanitize);
		return [
			'content' => $jsonResponse,
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
	 * Get the current authorization header.
	 *
	 * Checks the authentication method that will be use to make the call to the API
	 * and returns a string with the appropiate header.
	 *
	 * @return  string|bool	An authorization header, otherwise returns false.
	 * @access  private
	 */
	private function getAuthorizationHeader($caller): string|bool {
		if ($this->authenticationMethod == 'basic') {
			$basicToken = $this->getBasicAuthToken();
			$header = 'Authorization: Basic ' . $basicToken;
		} else {
			$oAuthToken = $this->getOAuthToken();
			if ($oAuthToken) {
				$header = 'Authorization: Bearer ' . $oAuthToken;
			} else {
				global $logger;
				//Special message case for patronLogin
				if (stripos($caller, "koha.patronLogin") !== false) {
					$logger->log("Unable to authenticate with the ILS from koha.patronLogin", Logger::LOG_ERROR);
				} else {
					$logger->log("Unable to retrieve OAuth2 token from " . $caller, Logger::LOG_ERROR);
				}
				$result['messages'][] = translate([
					'text' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.',
					'isPublicFacing' => true,
				]);
				$result['api']['messages'] = translate([
					'text' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.',
					'isPublicFacing' => true,
				]);
				return $oAuthToken;
			}
		}
		return $header;
	}

	/**
	 * Get open authorization token
	 *
	 * Check if there is a cached valid token and return it.
	 * Otherwise, get a new one from the API.
	 *
	 * @return  mixed	A valid token, otherwise false.
	 * @access  private
	 */
	private function getOAuthToken(): mixed {

		if (empty($this->oAuthToken)){

			/** @var Memcache $memCache */ global $memCache;

			// Get the value from DB
			$oAuthToken = $memCache->get(self::memCacheKey);

			if(!empty($oAuthToken)){
				$this->oAuthToken = $oAuthToken;
				return $this->oAuthToken;
			}

			// Get a new value from the API
			$tokenData = $this->getNewOAuthToken();

			// If the API call fails then return false
			if (!$tokenData){
				return false;
			}

			$accessToken = $tokenData['access_token'];
			$expiration = $tokenData['expiration'];

			// Store the new value
			if($memCache->set(self::memCacheKey,$accessToken,$expiration)){
				$this->oAuthToken = $accessToken;
			} else {
				return false;
			}

		}
		return $this->oAuthToken;
	}

	/**
	 * Get a **new** open authorization token.
	 *
	 * Makes an API call and returns an associate array that contains:
	 *
	 * - 'access_token' => The value of a new Open Authorization token
	 * - 'expiration' => The token lifetime segment
	 *
	 * If the request to get the new token fails, then return false.
	 *
	 *
	 * @return  mixed	An associative array with the token and the expiration date, otherwise false.
	 *
	 * @access  private
	 */
	private function getNewOAuthToken(): mixed {

		$content = [];

		// Prepare request
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

		// Get response body
		$response = $this->apiCurlWrapper->curlPostPage($apiUrl, $params);
		$jsonResponse = json_decode($response);
		$responseCode = $this->apiCurlWrapper->getResponseCode();
		ExternalRequestLogEntry::logRequest('koharestapiclient.getOAuthToken', 'POST', $apiUrl, $this->apiCurlWrapper->getHeaders(), json_encode($params), $responseCode, $response, ['client_secret' => $this->accountProfile->oAuthClientSecret]);

		if (!empty($jsonResponse->access_token)) {
			$content['access_token'] = $jsonResponse->access_token;
			$content['expiration'] = $jsonResponse->expires_in;
			return $content;
		} else {
			return false;
		}
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
		$jsonDecodedResponse = json_decode($response,true);
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

	public function getLastResponseCode(): int {
		return $this->apiCurlWrapper->getResponseCode();
	}
}