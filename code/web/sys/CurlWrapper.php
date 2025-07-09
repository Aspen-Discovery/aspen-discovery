<?php

class CurlWrapper {

	private $cookieJar;
	private $headers = [];
	private $options = [];
	public $curl_connection; // need access in order to check for curl errors.
	public $connectTimeout = 2;
	public $timeout = 10;
	public $responseHeaders = [];
	public $cookies = [];

	public function __construct() {
		global $interface;
		if ($interface != null) {
			$aspenVersion = $interface->getVariable('aspenVersion');
			if (substr($aspenVersion, -1) == "\n") {
				$aspenVersion = substr($aspenVersion, 0, -1);
			}
		} else {
			$aspenVersion = 'Primary';
		}

		$header = [];
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "User-Agent: Aspen Discovery " . $aspenVersion;
		$this->headers = $header;

		$default_options = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_UNRESTRICTED_AUTH => true,
			CURLOPT_COOKIESESSION => false,
			CURLOPT_FORBID_REUSE => false,
			CURLOPT_HEADER => false,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_ENCODING => '',
		];
		$this->options = $default_options;
	}

	public function __destruct() {
		$this->close_curl();
	}

	public function setCookieJar($prefix = "CURLCOOKIE") {
		$cookieJar = @tempnam(sys_get_temp_dir(), $prefix);
		$this->cookieJar = $cookieJar;
	}

	/**
	 * @return mixed CookieJar name
	 */
	public function getCookieJar() {
		if (is_null($this->cookieJar)) {
			$this->setCookieJar();
		}
		return $this->cookieJar;
	}

	/**
	 * Initialize and configure curl connection
	 *
	 * @param string|null $curlUrl optional url passed to curl_init
	 * @param null|array $curl_options is an array of curl options to include or overwrite.
	 *                    Keys is the curl option constant, Values is the value to set the option to.
	 * @return resource
	 */
	public function curl_connect($curlUrl = null, $curl_options = null) {
		//Make sure we only connect once
		if (!$this->curl_connection) {
			$cookie = $this->getCookieJar();

			$this->curl_connection = curl_init($curlUrl);
			$this->setOption(CURLOPT_COOKIEJAR, $cookie);
			$this->setOption(CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
			$this->setOption(CURLOPT_TIMEOUT, $this->timeout);
			$this->setOption(CURLOPT_HTTPHEADER, $this->headers);
			$this->setOption(CURLOPT_HEADERFUNCTION, [
				$this,
				'curlResponseHeaderCallback',
			]);

			global $configArray;
			if (IPAddress::showDebuggingInformation() && $configArray['System']['debugCurl']) {
				$this->setOption(CURLOPT_VERBOSE, true);
			}

			if ($curl_options) {
				curl_setopt_array($this->curl_connection, $curl_options);
			}
			curl_setopt_array($this->curl_connection, $this->options);
		} else {
			//Reset to HTTP GET and set the active URL
			curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
			curl_setopt($this->curl_connection, CURLOPT_URL, $curlUrl);
		}

		return $this->curl_connection;
	}

	/**
	 *  Cleans up after curl operations.
	 *  Is ran automatically as the class is being shutdown.
	 */
	public function close_curl() {
		if (!empty($this->curl_connection)) {
			curl_close($this->curl_connection);
			$this->curl_connection = null;
		}
		if ($this->cookieJar && file_exists($this->cookieJar)) {
			unlink($this->cookieJar);
		}
	}

	/**
	 * Uses the GET method to retrieve content from a page
	 *
	 * @param string $url The url to post to
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlGetPage($url) {
		$this->curl_connect($url);
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		$this->responseHeaders = [];
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $logger;
			$logger->log("curl get error for $url: " . curl_error($this->curl_connection), Logger::LOG_ERROR);
		}
		return $return;
	}

	/**
	 * Resets options set on the curl_connection so that older requests do not affect newer requests.
	 */
	public function resetCurlConnectionOptions():void {
		curl_setopt($this->curl_connection, CURLOPT_CUSTOMREQUEST, null);
		return;
	}

	/**
	 * Uses the POST Method to retrieve content from a page
	 *
	 * @param string $url The url to post to
	 * @param string|string[] $postParams Additional Post Params to use
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlPostPage($url, $postParams, $curlOptions = null) {
		if (is_string($postParams)) {
			$post_string = $postParams;
		} else {
			$post_string = http_build_query($postParams);
		}
		$this->curl_connect($url);
		curl_setopt_array($this->curl_connection, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_string,
		]);
		if ($curlOptions != null) {
			foreach ($curlOptions as $key => $value) {
				curl_setopt($this->curl_connection, $key, $value);
			}
		}
		$this->responseHeaders = [];
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $logger;
			$logger->log("curl post error for $url: " . curl_error($this->curl_connection), Logger::LOG_ERROR);
			$logger->log("  error code " . curl_errno($this->curl_connection), Logger::LOG_ERROR);
			$logger->log("  response code " . $this->getResponseCode(), Logger::LOG_ERROR);
		}
		return $return;
	}

	/**
	 * Uses the POST Method to retrieve content from a page
	 *
	 * @param string $url The url to post to
	 * @param string[]|string|stdClass $postParams Additional Post Params to use
	 * @param boolean $jsonEncode
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlPostBodyData($url, $postParams, $jsonEncode = true) {
		if ($jsonEncode) {
			$post_string = json_encode($postParams);
		} else {
			$post_string = $postParams;
		}

		$this->curl_connect($url);
		curl_setopt_array($this->curl_connection, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_string,
		]);
		$this->responseHeaders = [];
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $logger;
			$logger->log("curl post error for $url: " . curl_error($this->curl_connection), Logger::LOG_ERROR);
		}
		return $return;
	}

	public function curlSendPage(string $url, string $httpMethod, $body = null) {
		$this->curl_connect($url);
		if ($httpMethod == 'GET') {
			curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		} elseif ($httpMethod == 'POST') {
			curl_setopt($this->curl_connection, CURLOPT_POST, true);
		} elseif ($httpMethod == 'PUT') {
			//curl_setopt($this->curl_connection, CURLOPT_PUT, true);
			curl_setopt($this->curl_connection, CURLOPT_CUSTOMREQUEST, "PUT");
			if ($body === null || $body === false) {
				$this->addCustomHeaders(['Content-Length: 0'], false);
			}
		} else {
			curl_setopt($this->curl_connection, CURLOPT_CUSTOMREQUEST, $httpMethod);
		}
		if ($body != null) {
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $body);
		}
		$this->responseHeaders = [];
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $configArray;
			if (!$configArray['Site']['isProduction']) {
				$curl_err = curl_error($this->curl_connection);
				$curl_info = curl_getinfo($this->curl_connection);
			}
			global $logger;
			$logger->log("curl send error for url $url : " . curl_error($this->curl_connection), Logger::LOG_ERROR);
		}
		return $return;
	}

	function getResponseCode() {
		$curl_info = curl_getinfo($this->curl_connection);
		return $curl_info['http_code'];
	}

	function getInfo() {
		return curl_getinfo($this->curl_connection);
	}

	function getHeaders() {
		return $this->headers;
	}

	function getHeaderSize() {
		return curl_getinfo($this->curl_connection, CURLINFO_HEADER_SIZE);
	}

	public function setupDebugging() {
		$result1 = curl_setopt($this->curl_connection, CURLOPT_HEADER, true);
		$result2 = curl_setopt($this->curl_connection, CURLOPT_VERBOSE, true);
		return $result1 && $result2;
	}

	/**
	 * @param string[] $customHeaders
	 * @param bool $overrideExisting
	 */
	function addCustomHeaders(array $customHeaders, bool $overrideExisting) {
		if ($overrideExisting) {
			$this->headers = $customHeaders;
		} else {
			$this->headers = array_merge($this->headers, $customHeaders);
		}
		if (!empty($this->curl_connection)) {
			curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, $this->headers);
		}
	}

	function setOption($curlOption, $value) {
		$this->options[$curlOption] = $value;
		if (!empty($this->curl_connection)) {
			curl_setopt($this->curl_connection, $curlOption, $value);
		}
	}

	function setTimeout($timeout) {
		$this->timeout = $timeout;
		if (!empty($this->curl_connection)) {
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, $this->timeout);
		}
	}

	function setConnectTimeout($timeout) {
		$this->connectTimeout = $timeout;
		if (!empty($this->curl_connection)) {
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		}
	}

	function curlResponseHeaderCallback($ch, $headerLine) {
		$this->responseHeaders[] = $headerLine;
		if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1) {
			$this->cookies[] = $cookie[1];
		}
		return strlen($headerLine); // Needed by curl
	}
}
