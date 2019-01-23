<?php

/**
 * @file
 * This file defines the classes used to make HTTP requests.
 */

/**
 * HTTP Exception. This is thrown when a status code other then 2XX is returned.
 *
 * @param string $message
 *   A message describing the exception.
 * @param int $code
 *   The error code. These are often the HTTP status codes, however less then
 *   100 is defined by the class extending HttpConnection, for eample cURL.
 * @param array $response
 *   The array containing: status, headers, and content of the HTTP request
 *   causing the error. This is only set if there was a HTTP response sent.
 */
class HttpConnectionException extends Exception {

  protected $response;

  /**
   * The constructor for the exception. Adds a response field.
   *
   * @param string $message
   *   The error message
   * @param int $code
   *   The error code
   * @param array $response
   *   The HTTP response
   * @param Exception $previous
   *   The previous exception in the chain
   */
  function __construct($message, $code, $response = NULL, $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->response = $response;
  }

  /**
   * Get the HTTP response that caused the exception.
   *
   * @return array
   *   Array containing the HTTP response. It has three keys: status, headers
   *   and content.
   */
  function getResponse() {
    return $this->response;
  }
}

/**
 * Abstract class defining functions for HTTP connections
 */
abstract class HttpConnection {

  /**
   * This determines if the HTTP connection should use cookies. (Default: TRUE)
   * @var type boolean
   */
  public $cookies = TRUE;
  /**
   * The username to connect with. If no username is desired then use NULL.
   * (Default: NULL)
   * @var type string
   */
  public $username = NULL;
  /**
   * The password to connect with. Used if a username is set.
   * @var type string
   */
  public $password = NULL;
  /**
   * TRUE to check the existence of a common name and also verify that it
   * matches the hostname provided. (Default: TRUE)
   * @var type boolean
   */
  public $verifyHost = TRUE;
  /**
   * FALSE to stop cURL from verifying the peer's certificate. (Default: TRUE)
   * @var type boolean
   */
  public $verifyPeer = TRUE;
  /**
   * The maximum number of seconds to allow cURL functions to execute. (Default:
   * cURL default)
   * @var type int
   */
  public $timeout = NULL;
  /**
   * The number of seconds to wait while trying to connect. Use 0 to wait
   * indefinitely. (Default: 5)
   * @var type
   */
  public $connectTimeout = 5;
  /**
   * The useragent to use. (Default: cURL default)
   * @var type string
   */
  public $userAgent = NULL;
  /**
   * If this is set to true, the connection will be recycled, so that cURL will
   * try to use the same connection for multiple requests. If this is set to
   * FALSE a new connection will be used each time.
   * @var type boolean
   */
  public $reuseConnection = TRUE;

  /**
   * Some servers require the version of ssl to be set.
   * We set it to NULL which will allow php to try and figure out what
   * version to use.  in some cases you may have to set this to 2 or 3
   * @var int
   */
  public $sslVersion = NULL;

  /**
   * Turn on to print debug infotmation to stderr.
   * @var type boolean
   */
  public $debug = FALSE;

  /**
   */
  public function __sleep() {
    return array(
      'url',
      'cookies',
      'username',
      'password',
      'verifyHost',
      'verifyPeer',
      'timeout',
      'connectTimeout',
      'userAgent',
      'reuseConnection',
      'sslVersion',
    );
  }

  /**
   * Post a request to the server. This is primarily used for
   * sending files.
   *
   * @todo Test this for posting general form data. (Other then files.)
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file.
   * @param string $data
   *   What this parameter contains is decided by the $type parameter.
   * @param string $content_type
   *   The content type header to set for the post request.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL);

  /**
   * Do a patch request, used for partial updates of a resource
   *
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file.
   * @param string $data
   *   What this parameter contains is decided by the $type parameter.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function patchRequest($url, $type = 'none', $data = NULL, $content_type = NULL);

  /**
   * Send a HTTP GET request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param boolean $headers_only
   *   This will cause curl to only return the HTTP headers.
   * @param string $file
   *   A file to output the content of request to. If this is set then headers
   *   are not returned and the 'content' and 'headers' keys of the return isn't
   *   set.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function getRequest($url, $headers_only = FALSE, $file = FALSE);

  /**
   * Send a HTTP PUT request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file, none.
   * @param string $file
   *   What this parameter contains is decided by the $type parameter.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function putRequest($url, $type = 'none', $file = NULL);

  /**
   * Send a HTTP DELETE request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function deleteRequest($url);
}

/**
 * This class defines a abstract HttpConnection using the PHP cURL library.
 */
class CurlConnection extends HttpConnection {
  const COOKIE_LOCATION = 'curl_cookie';
  protected $cookieFile = NULL;
  protected static $curlContext = NULL;

  /**
   * Constructor for the connection.
   *
   * @throws HttpConnectionException
   */
  public function __construct() {
    if (!function_exists("curl_init")) {
      throw new HttpConnectionException('cURL PHP Module must to enabled.', 0);
    }
    $this->createCookieFile();
  }

  /**
   * Save the cookies to the sessions and remember all of the parents members.
   */
  public function __sleep() {
    $this->saveCookiesToSession();
    return parent::__sleep();
  }

  /**
   * Restore the cookies file and initialize curl.
   */
  public function __wakeup() {
    $this->createCookieFile();
    $this->getCurlContext();
  }

  /**
   * Destructor for the connection.
   *
   * Save the cookies to the session unallocate curl, and free the cookies file.
   */
  public function __destruct() {
    $this->saveCookiesToSession();
    $this->unallocateCurlContext();
    unlink($this->cookieFile);
  }

  /**
   * Determines if the server operating system is Windows.
   *
   * @return bool
   *   TRUE if Windows, FALSE otherwise.
   */
  protected function isWindows() {
    // Determine if PHP is currently running on Windows.
    if (strpos(strtolower(php_uname('s')), 'windows') !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Create a file to store cookies.
   */
  protected function createCookieFile() {
    $this->cookieFile = tempnam(sys_get_temp_dir(), 'curlcookie');
    // If we didn't get a place to store cookies in a temporary
    // file, we cannot continue.
    if (! $this->cookieFile) {
      throw new HttpConnectionException('Could not open temporary file at '.sys_get_temp_dir(),0);
    }
    // See if we have any cookies in the session already
    // this makes sure SESSION ids persist.
    if (isset($_SESSION[self::COOKIE_LOCATION])) {
      file_put_contents($this->cookieFile, $_SESSION[self::COOKIE_LOCATION]);
    }
  }

  /**
   * Save the contents of the cookie file to the session.
   */
  protected function saveCookiesToSession() {
    // Before we go, save our fedora session cookie to the browsers session.
    if (isset($_SESSION)) {
      $_SESSION[self::COOKIE_LOCATION] = file_get_contents($this->cookieFile);
    }
  }

  /**
   * This function sets up the context for curl.
   */
  protected function getCurlContext() {
    if (!isset(self::$curlContext)) {
      self::$curlContext = curl_init();
    }
  }

  /**
   * Unallocate curl context
   */
  protected function unallocateCurlContext() {
    if (self::$curlContext) {
      curl_close(self::$curlContext);
      self::$curlContext = NULL;
    }
  }

  /**
   * This sets the curl options
   */
  protected function setupCurlContext($url) {
    $this->getCurlContext();
    curl_setopt(self::$curlContext, CURLOPT_URL, $url);
    curl_setopt(self::$curlContext, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
    curl_setopt(self::$curlContext, CURLOPT_SSL_VERIFYHOST, $this->verifyHost ? 2 : 1);
    if ($this->sslVersion !== NULL) {
      curl_setopt(self::$curlContext, CURLOPT_SSLVERSION, $this->sslVersion);
    }
    if ($this->timeout) {
      curl_setopt(self::$curlContext, CURLOPT_TIMEOUT, $this->timeout);
    }
    curl_setopt(self::$curlContext, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    if ($this->userAgent) {
      curl_setopt(self::$curlContext, CURLOPT_USERAGENT, $this->userAgent);
    }
    if ($this->cookies) {
      curl_setopt(self::$curlContext, CURLOPT_COOKIEFILE, $this->cookieFile);
      curl_setopt(self::$curlContext, CURLOPT_COOKIEJAR, $this->cookieFile);
    }
    curl_setopt(self::$curlContext, CURLOPT_FAILONERROR, FALSE);
    curl_setopt(self::$curlContext, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt(self::$curlContext, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt(self::$curlContext, CURLOPT_HEADER, TRUE);

    if ($this->debug) {
      curl_setopt(self::$curlContext, CURLOPT_VERBOSE, 1);
    }
    if ($this->username) {
      $user = $this->username;
      $pass = $this->password;
      curl_setopt(self::$curlContext, CURLOPT_USERPWD, "$user:$pass");
    }
  }

  /**
   * This function actually does the cURL request. It is a private function
   * meant to be called by the public get, post and put methods.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Array has keys: (status, headers, content).
   */
 protected function doCurlRequest($file = NULL) {
    $remaining_attempts = 3;
    while ($remaining_attempts > 0) {
      $curl_response = curl_exec(self::$curlContext);
      // Since we are using exceptions we trap curl error
      // codes and toss an exception, here is a good error
      // code reference.
      // http://curl.haxx.se/libcurl/c/libcurl-errors.html
      $error_code = curl_errno(self::$curlContext);
      $error_string = curl_error(self::$curlContext);
      if ($error_code != 0) {
        throw new HttpConnectionException($error_string, $error_code);
      }

      $info = curl_getinfo(self::$curlContext);

      $response = array();
      $response['status'] = $info['http_code'];
      $http_error_string = '';
      if ($file == NULL) {
        $response['headers'] = substr($curl_response, 0, $info['header_size'] - 1);
        $response['content'] = substr($curl_response, $info['header_size']);

        // We do some ugly stuff here to strip the error string out
        // of the HTTP headers, since curl doesn't provide any helper.
        $http_error_string = explode("\r\n\r\n", $response['headers']);
        $http_error_string = $http_error_string[count($http_error_string) - 1];
        $http_error_string = explode("\r\n", $http_error_string);
        $http_error_string = substr($http_error_string[0], 13);
        $http_error_string = trim($http_error_string);
      }
      $blocked = $info['http_code'] == 409;
      $remaining_attempts = $blocked ? --$remaining_attempts : 0;
    }
    // Throw an exception if this isn't a 2XX response.
    $success = preg_match("/^2/", $info['http_code']);
    if (!$success) {
      throw new HttpConnectionException($http_error_string, $info['http_code'], $response);
    }
    return $response;
  }


  /**
   * @see HttpConnection::patchRequest
   */
  public function patchRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    $this->setupCurlContext($url);
    curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'PATCH');

    switch (strtolower($type)) {
      case 'string':
        if ($content_type) {
          $headers = array("Content-Type: $content_type");
        }
        else {
          $headers = array("Content-Type: text/plain");
        }
        curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, $data);
        break;

      case 'file':
        if (version_compare(phpversion(), '5.5.0', '>=')) {
          if ($content_type) {
            $cfile = new CURLFile($data, $content_type, $data);
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => $cfile));
          }
          else {
            $cfile = new CURLFile($data);
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => $cfile));
          }
        }
        else {
          if ($content_type) {
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data;type=$content_type"));
          }
          else {
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data"));
          }
        }
        break;

      case 'none':
        curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array());
        break;

      default:
        throw new HttpConnectionException('$type must be: string, file. ' . "($type).", 0);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt(self::$curlContext, CURLOPT_POST, FALSE);
      curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, array());
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * @see HttpConnection::postRequest
   */
  public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    $this->setupCurlContext($url);
    curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt(self::$curlContext, CURLOPT_POST, TRUE);

    switch (strtolower($type)) {
      case 'string':
        if ($content_type) {
          $headers = array("Content-Type: $content_type");
        }
        else {
          $headers = array("Content-Type: text/plain");
        }
        curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, $data);
        break;

      case 'file':
        if (version_compare(phpversion(), '5.5.0', '>=')) {
          if ($content_type) {
            $cfile = new CURLFile($data, $content_type, $data);
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => $cfile));
          }
          else {
            $cfile = new CURLFile($data);
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => $cfile));
          }
        }
        else {
          if ($content_type) {
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data;type=$content_type"));
          }
          else {
            curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data"));
          }
        }
        break;

      case 'none':
        curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, array());
        break;

      default:
        throw new HttpConnectionException('$type must be: string, file. ' . "($type).", 0);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt(self::$curlContext, CURLOPT_POST, FALSE);
      curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, array());
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * @see HttpConnection::putRequest
   */
  function putRequest($url, $type = 'none', $file = NULL) {
    $this->setupCurlContext($url);
    curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'PUT');
    switch (strtolower($type)) {
      case 'string':
        // When using 'php://memory' in Windows, the following error
        // occurs when trying to ingest a page into the Book Solution Pack:
        // "Warning: curl_setopt(): cannot represent a stream of type
        // MEMORY as a STDIO FILE* in CurlConnection->putRequest()"
        // Reference: http://bit.ly/18Qym02
        $file_stream = (($this->isWindows()) ? 'php://temp' : 'php://memory');
        $fh = fopen($file_stream, 'rw');
        fwrite($fh, $file);
        rewind($fh);
        $size = strlen($file);
        curl_setopt(self::$curlContext, CURLOPT_PUT, TRUE);
        curl_setopt(self::$curlContext, CURLOPT_INFILE, $fh);
        curl_setopt(self::$curlContext, CURLOPT_INFILESIZE, $size);
        break;

      case 'file':
        $fh = fopen($file, 'r');
        $size = filesize($file);
        curl_setopt(self::$curlContext, CURLOPT_PUT, TRUE);
        curl_setopt(self::$curlContext, CURLOPT_INFILE, $fh);
        curl_setopt(self::$curlContext, CURLOPT_INFILESIZE, $size);
        break;

      case 'none':
        break;

      default:
        throw new HttpConnectionException('$type must be: string, file. ' . "($type).", 0);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      //curl_setopt(self::$curlContext, CURLOPT_PUT, FALSE);
      //curl_setopt(self::$curlContext, CURLOPT_INFILE, 'default');
      //curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, FALSE);
      // We can't unallocate put requests becuase CURLOPT_INFILE can't be undone
      // this is ugly, but it gets the job done for now.
      $this->unallocateCurlContext();
    }
    else {
      $this->unallocateCurlContext();
    }

    if (isset($fh)) {
      fclose($fh);
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * @see HttpConnection::getRequest
   */
  function getRequest($url, $headers_only = FALSE, $file = NULL) {
    // Need this as before we were opening a new file pointer for std for each
    // request. When the ulimit was reached this would make things blow up.
    static $stdout = NULL;

    if ($stdout === NULL) {
      $stdout = fopen('php://stdout', 'w');
    }
    $this->setupCurlContext($url);

    if ($headers_only) {
      curl_setopt(self::$curlContext, CURLOPT_NOBODY, TRUE);
      curl_setopt(self::$curlContext, CURLOPT_HEADER, TRUE);
    } else {
      curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt(self::$curlContext, CURLOPT_HTTPGET, TRUE);
    }

    if ($file) {
      $file_original_path = $file;
      // In Windows, using 'temporary://' with curl_setopt 'CURLOPT_FILE'
      // results in the following error: "Warning: curl_setopt():
      // DrupalTemporaryStreamWrapper::stream_cast is not implemented!"
      if ($this->isWindows()) {
        $file = str_replace('temporary://', sys_get_temp_dir() . '/', $file);
      }
      $file = fopen($file, 'w+');
      // Determine if the current operating system is Windows.
      // Also check whether the output buffer is being utilized.
      if (($this->isWindows()) && ($file_original_path == 'php://output')) {
        // In Windows, ensure the image can be displayed onscreen. Just using
        // 'CURLOPT_FILE' results in a broken image and the following error:
        // "Warning: curl_setopt(): cannot represent a stream of type
        // Output as a STDIO FILE* in CurlConnection->getRequest()"
        // Resource: http://www.php.net/manual/en/function.curl-setopt.php#58074
        curl_setopt(self::$curlContext, CURLOPT_RETURNTRANSFER, FALSE);
      }
      else {
        curl_setopt(self::$curlContext, CURLOPT_FILE, $file);
      }
      curl_setopt(self::$curlContext, CURLOPT_HEADER, FALSE);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest($file);
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt(self::$curlContext, CURLOPT_HTTPGET, FALSE);
      curl_setopt(self::$curlContext, CURLOPT_NOBODY, FALSE);
      curl_setopt(self::$curlContext, CURLOPT_HEADER, FALSE);
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($file) {
      fclose($file);
      curl_setopt(self::$curlContext, CURLOPT_FILE, $stdout);
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * @see HttpConnection::deleteRequest
   */
  function deleteRequest($url) {
    $this->setupCurlContext($url);

    curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'DELETE');

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, NULL);
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

}
