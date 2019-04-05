<?php

class CurlWrapper {

	private $cookieJar;
	private $headers = [];
	public $curl_connection; // need access in order to check for curl errors.

    public function __construct() {
        global $interface;
        $gitBranch = $interface->getVariable('gitBranch');
        if (substr($gitBranch, -1) == "\n"){
            $gitBranch = substr($gitBranch, 0, -1);
        }
        $header = array();
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "User-Agent: Aspen Discovery " . $gitBranch;
        $this->headers = $header;
    }

    public function __destruct(){
		$this->close_curl();
	}

	public function setCookieJar($prefix = "CURLCOOKIE"){
		$cookieJar = tempnam("/tmp", $prefix);
		$this->cookieJar = $cookieJar;
	}

	/**
	 * @return mixed CookieJar name
	 */
	public function getCookieJar() {
		if (is_null($this->cookieJar)){
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
	public function curl_connect($curlUrl = null, $curl_options = null){
		//Make sure we only connect once
		if (!$this->curl_connection){
			$cookie = $this->getCookieJar();

			$this->curl_connection = curl_init($curlUrl);
			$default_curl_options = array(
				CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_HTTPHEADER => $this->headers,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_UNRESTRICTED_AUTH => true,
				CURLOPT_COOKIEJAR => $cookie,
				CURLOPT_COOKIESESSION => false,
				CURLOPT_FORBID_REUSE => false,
				CURLOPT_HEADER => false,
				CURLOPT_AUTOREFERER => true,
				//  CURLOPT_HEADER => true, // debugging only
				//  CURLOPT_VERBOSE => true, // debugging only
			);

			if ($curl_options) {
				$default_curl_options = array_merge($default_curl_options, $curl_options);
			}
			curl_setopt_array($this->curl_connection, $default_curl_options);
		}else{
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
		if ($this->curl_connection) curl_close($this->curl_connection);
		if ($this->cookieJar && file_exists($this->cookieJar)) unlink($this->cookieJar);
	}

	/**
	 * Uses the GET method to retrieve content from a page
	 *
	 * @param string    $url          The url to post to
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlGetPage($url){
		$this->curl_connect($url);
        curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $logger;
			$logger->log('curl get error : '.curl_error($this->curl_connection), PEAR_LOG_ERR);
		}
		return $return;
	}

	/**
	 * Uses the POST Method to retrieve content from a page
	 *
	 * @param string    $url          The url to post to
	 * @param string[]  $postParams   Additional Post Params to use
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlPostPage($url, $postParams){
		$post_string = http_build_query($postParams);

		$this->curl_connect($url);
		curl_setopt_array($this->curl_connection, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_string
		));
		$return = curl_exec($this->curl_connection);
		if (!$return) { // log curl error
			global $logger;
			$logger->log('curl post error : '.curl_error($this->curl_connection), PEAR_LOG_ERR);
		}
		return $return;
	}

	/**
	 * Uses the POST Method to retrieve content from a page
	 *
	 * @param string            $url          The url to post to
	 * @param string[]|string  $postParams   Additional Post Params to use
	 * @param boolean   $jsonEncode
	 *
	 * @return string   The response from the web page if any
	 */
	public function curlPostBodyData($url, $postParams, $jsonEncode = true){
		if ($jsonEncode){
			$post_string = json_encode($postParams);
		}else{
			$post_string  = $postParams;
		}

		$this->curl_connect($url);
		curl_setopt_array($this->curl_connection, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_string,
		));

		return curl_exec($this->curl_connection);
	}

	protected function setupDebugging(){
		$result1 = curl_setopt($this->curl_connection, CURLOPT_HEADER, true);
		$result2 = curl_setopt($this->curl_connection, CURLOPT_VERBOSE, true);
		return $result1 && $result2;
	}

	function addCustomHeaders($customHeaders, $overrideExisting) {
	    if ($overrideExisting) {
            $this->headers = $customHeaders;
        } else {
            $this->headers = array_merge($this->headers , $customHeaders);
        }
	}
}