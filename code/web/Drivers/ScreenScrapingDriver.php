<?php
/**
 * An abstract base class so screen scraping functionality can be stored in a single location
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/23/2015
 * Time: 2:32 PM
 */

abstract class ScreenScrapingDriver implements DriverInterface {
	/** @var  AccountProfile $accountProfile */
	public $accountProfile;
	private $cookieJar;
	public $curl_connection; // need access in order to check for curl errors.


	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		$this->accountProfile = $accountProfile;
	}

	public function __destruct(){
		$this->_close_curl();
	}

	public function setCookieJar(){
		$cookieJar = tempnam("/tmp", "CURLCOOKIE");
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
	 * @param null $curl_url optional url passed to curl_init
	 * @param null|Array $curl_options is an array of curl options to include or overwrite.
	 *                    Keys is the curl option constant, Values is the value to set the option to.
	 * @return resource
	 */
	public function _curl_connect($curlUrl = null, $curl_options = null){
		//Make sure we only connect once
		if (!$this->curl_connection){
			$header = $this->getCustomHeaders();
			if ($header == null) {
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
				$header[] = "User-Agent: Pika " . $gitBranch;
			}

			$cookie = $this->getCookieJar();

			$this->curl_connection = curl_init($curlUrl);
			$default_curl_options = array(
				CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_HTTPHEADER => $header,
				//CURLOPT_USERAGENT => 'User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
				//CURLOPT_USERAGENT => "User-Agent:Pika " . $gitBranch,
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
	public function _close_curl() {
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
	public function _curlGetPage($url){
		$this->_curl_connect($url);
		$return = curl_exec($this->curl_connection);
//		$info = curl_getinfo($this->curl_connection);
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
	public function _curlPostPage($url, $postParams){
		$post_string = http_build_query($postParams);

		$this->_curl_connect($url);
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
	public function _curlPostBodyData($url, $postParams, $jsonEncode = true){
		if ($jsonEncode){
			$post_string = json_encode($postParams);
		}else{
			$post_string  = $postParams;
		}

		$this->_curl_connect($url);
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

	public function getVendorOpacUrl(){
		global $configArray;

		if ($this->accountProfile && $this->accountProfile->vendorOpacUrl ){
			$host = $this->accountProfile->vendorOpacUrl;
		}else{
			$host = $configArray['Catalog']['url'];
		}

		if (substr($host, -1) == '/') {
			$host = substr($host, 0, -1);
		}
		return $host;
	}

	protected function getCustomHeaders() {
		return null;
	}
}