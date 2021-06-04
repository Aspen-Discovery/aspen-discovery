<?php

require_once ROOT_DIR . '/sys/Email/AmazonSesSetting.php';

/**
 * Class AmazonSesRequest
 *
 * Some code initially based on:
 * https://github.com/daniel-zahariev/php-aws-ses
 */
class AmazonSesRequest
{
	private $ses;
	private $verb;
	private $parameters = array();

	// CURL request handler that can be reused
	protected $curl_handler = null;

	// Holds the response from calling AWS's API
	protected $response;

	//
	public static $curlOptions = array();

	/**
	 * Constructor
	 *
	 * @param AmazonSesSetting $ses The AmazonSesSetting with settings for the request
	 * @param string $verb HTTP verb
	 * @return void
	 */
	public function __construct(AmazonSesSetting $ses, string $verb = 'GET') {
		$this->ses = $ses;
		$this->verb = $verb;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);
	}

	/**
	 * Set HTTP method
	 *
	 * @param string $verb
	 * @return AmazonSesRequest $this
	 */
	public function setVerb(string $verb) : AmazonSesRequest{
		$this->verb = $verb;

		return $this;
	}

	/**
	 * Set request parameter
	 *
	 * @param string  $key Key
	 * @param string  $value Value
	 * @param boolean $replace Whether to replace the key if it already exists (default true)
	 * @return AmazonSesRequest $this
	 */
	public function setParameter(string $key, string $value, bool $replace = true) : AmazonSesRequest {
		if(!$replace && isset($this->parameters[$key])) {
			$temp = (array)($this->parameters[$key]);
			$temp[] = $value;
			$this->parameters[$key] = $temp;
		} else {
			$this->parameters[$key] = $value;
		}

		return $this;
	}

	/**
	 * Clear the request parameters
	 * @return AmazonSesRequest $this
	 */
	public function clearParameters() : AmazonSesRequest {
		$this->parameters = array();
		return $this;
	}

	/**
	 * Instantiate and setup CURL handler for sending requests.
	 * Instance is cashed in `$this->curl_handler`
	 *
	 * @return resource $curl_handler
	 */
	protected function getCurlHandler() {
		if (!empty($this->AmazonSesRequest))
			return $this->curl_handler;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SimpleEmailService/php');

		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		foreach(self::$curlOptions as $option => $value) {
			curl_setopt($curl, $option, $value);
		}

		$this->curl_handler = $curl;

		return $this->curl_handler;
	}

	/**
	 * Get the response
	 *
	 * @return object | false
	 */
	public function getResponse() : ?object {

		$url = 'https://'.$this->ses->getHost().'/';
		ksort($this->parameters);
		$query = http_build_query($this->parameters);
		$headers = $this->getHeaders($query);

		$curl_handler = $this->getCurlHandler();
		curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, $this->verb);

		// Request types
		switch ($this->verb) {
			case 'GET':
			case 'DELETE':
				$url .= '?'.$query;
				break;

			case 'POST':
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $query);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				break;
		}
		curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_handler, CURLOPT_URL, $url);


		// Execute, grab errors
		if (curl_exec($curl_handler)) {
			$this->response->code = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
		} else {
			$this->response->error = array(
				'curl' => true,
				'code' => curl_errno($curl_handler),
				'message' => curl_error($curl_handler),
			);
		}

		// cleanup for reusing the current instance for multiple requests
		curl_setopt($curl_handler, CURLOPT_POSTFIELDS, '');
		$this->parameters = array();

		// Parse body into XML
		if ($this->response->error === false && !empty($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab SES errors
			if (!in_array($this->response->code, array(200, 201, 202, 204))
				&& isset($this->response->body->Error)) {
				$error = $this->response->body->Error;
				$output = array();
				$output['curl'] = false;
				$output['Error'] = array();
				$output['Error']['Type'] = (string)$error->Type;
				$output['Error']['Code'] = (string)$error->Code;
				$output['Error']['Message'] = (string)$error->Message;
				$output['RequestId'] = (string)$this->response->body->RequestId;

				$this->response->error = $output;
				unset($this->response->body);
			}
		}

		$response = $this->response;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);

		return $response;
	}

	/**
	 * Get request headers
	 * @param string $query
	 * @return array
	 */
	protected function getHeaders($query) {
		$headers = array();

		$date = (new DateTime('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
		$headers[] = 'X-Amz-Date: ' . $date;
		$headers[] = 'Host: ' . $this->ses->getHost();
		$headers[] = 'Authorization: ' . $this->__getAuthHeaderV4($date, $query);
		$headers[] = 'X-SES-CONFIGURATION-SET: ' . $this->ses->bulkMailConfigSet;

		return $headers;
	}

	/**
	 * Destroy any leftover handlers
	 */
	public function __destruct() {
		if (!empty($this->curl_handler))
			@curl_close($this->curl_handler);
	}

	/**
	 * CURL write callback
	 *
	 * @param resource $curl CURL resource
	 * @param string $data Data
	 * @return integer
	 */
	private function __responseWriteCallback($curl, string $data) : int {
		if (!isset($this->response->body)) {
			$this->response->body = $data;
		} else {
			$this->response->body .= $data;
		}

		return strlen($data);
	}

	/**
	 * @param string $key
	 * @param string $dateStamp
	 * @param string $regionName
	 * @param string $serviceName
	 * @param string $algo
	 * @return string
	 */
	private function __getSigningKey(string $key, string $dateStamp, string $regionName, string $serviceName, string $algo) : string {
		$kDate = hash_hmac($algo, $dateStamp, 'AWS4' . $key, true);
		$kRegion = hash_hmac($algo, $regionName, $kDate, true);
		$kService = hash_hmac($algo, $serviceName, $kRegion, true);

		return hash_hmac($algo,'aws4_request', $kService, true);
	}

	/**
	 * Implementation of AWS Signature Version 4
	 * @see https://docs.aws.amazon.com/general/latest/gr/sigv4_signing.html
	 * @param string $amz_datetime
	 * @param string $query
	 * @return string
	 */
	private function __getAuthHeaderV4(string $amz_datetime, string $query) : string {
		$amz_date = substr($amz_datetime, 0, 8);
		$algo = 'sha256';
		$aws_algo = 'AWS4-HMAC-' . strtoupper($algo);

		$host_parts = explode('.', $this->ses->getHost());
		$service = $host_parts[0];
		$region = $host_parts[1];

		$canonical_uri = '/';
		if($this->verb === 'POST') {
			$canonical_querystring = '';
			$payload_data = $query;
		} else {
			$canonical_querystring = $query;
			$payload_data = '';
		}

		// ************* TASK 1: CREATE A CANONICAL REQUEST *************
		$canonical_headers_list = [
			'host:' . $this->ses->getHost(),
			'x-amz-date:' . $amz_datetime
		];

		$canonical_headers = implode("\n", $canonical_headers_list) . "\n";
		$signed_headers = 'host;x-amz-date';
		$payload_hash = hash($algo, $payload_data);

		$canonical_request = implode("\n", array(
			$this->verb,
			$canonical_uri,
			$canonical_querystring,
			$canonical_headers,
			$signed_headers,
			$payload_hash
		));

		// ************* TASK 2: CREATE THE STRING TO SIGN*************
		$credential_scope = $amz_date. '/' . $region . '/' . $service . '/' . 'aws4_request';
		$string_to_sign = implode("\n", array(
			$aws_algo,
			$amz_datetime,
			$credential_scope,
			hash($algo, $canonical_request)
		));

		// ************* TASK 3: CALCULATE THE SIGNATURE *************
		// Create the signing key using the function defined above.
		$signing_key = $this->__getSigningKey($this->ses->accessKeySecret, $amz_date, $region, $service, $algo);

		// Sign the string_to_sign using the signing_key
		$signature = hash_hmac($algo, $string_to_sign, $signing_key);

		// ************* TASK 4: ADD SIGNING INFORMATION TO THE REQUEST *************
		return $aws_algo . ' ' . implode(', ', array(
				'Credential=' . $this->ses->accessKeyId . '/' . $credential_scope,
				'SignedHeaders=' . $signed_headers ,
				'Signature=' . $signature
			));
	}
}