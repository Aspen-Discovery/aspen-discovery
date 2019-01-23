<?php
/**
 * @file
 * This file contains the implementation of a connection to Fedora. And the
 * interface for a repository configuration.
 */

require_once 'HttpConnection.php';
require_once 'RepositoryException.php';

/**
 * The general interface for a RepositoryConfig object.
 */
interface RepositoryConfigInterface {
  /**
   * Simple constructor defintion for the repository
   */
  function __construct($url, $username, $password);
}

/**
 * Specific RepositoryConfig implementation that extends the CurlConnection
 * class so that we can do specific processing on Curl requests for Fedora.
 * This also wraps the exceptions thrown by Curl, so that we keep our exception
 * encapsulation.
 *
 * It also doesn't take urls but instead uses relative paths that it adds the
 * fedora URL to. This makes is a bit easier to use. It also makes sure that
 * we always send usernames and passwords.
 */
class RepositoryConnection extends CurlConnection implements RepositoryConfigInterface {

  public $url;
  public $username;
  public $password;

  const FEDORA_URL = "http://localhost:8080/fedora";

  /**
   * This constructor for the RepositoryConnection.
   *
   * @param string $url
   *   The URL to Fedora.
   * @param string $username
   *   The username to connect with.
   * @param string $password
   *   The password to connect with.
   */
  function __construct($url = self::FEDORA_URL, $username = NULL, $password = NULL) {
    // Make sure the url doesn't have a trailing slash.
    $this->url = rtrim($url, "/");
    $this->username = $username;
    $this->password = $password;

    try {
      parent::__construct();
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * This private function makes a fedora URL from a noraml URL.
   */
  protected function buildUrl($url) {
    $url = ltrim($url, "/");
    return "{$this->url}/$url";
  }

  /**
   * These functions are used a lot when connecting to Fedora to create the
   * correct arguements for REST calls. This will encode and add an array
   * of arguements to a request URL.
   *
   * @param string $request
   *   The request that is being built.
   * @param character $seperator
   *   This is a helper to make sure that the first arguement gets a ? and the
   *   rest of them get a &.
   * @param array $params
   *   An array of parameters.
   * @param string $name
   *   The name of the parameter that we are adding.
   */
  public function addParamArray(&$request, &$seperator, $params, $name) {
    if (is_array($params)) {
      if (array_key_exists($name, $params)) {
        $this->addParam($request, $seperator, $name, $params[$name]);
      }
    }
  }

  /**
   * This function adds a specific parameter to a RESTful request. It makes
   * sure that PHP booleans are changes into true and false and that the
   * parameters are properly URL encoded.
   *
   * @param string $request
   *   The request that is being built.
   * @param character $seperator
   *   This is a helper to make sure that the first arguement gets a ? and the
   *   rest of them get a &.
   * @param string $name
   *   The name of the parameter that is being added
   * @param string $value
   *   the value of hte parameter.
   */
  public function addParam(&$request, &$seperator, $name, $value) {
    if ($value !== NULL) {
      if (is_bool($value)) {
        $parameter = $value ? 'true' : 'false';
      }
      else {
        $parameter = urlencode($value);
      }
      $request .= "{$seperator}{$name}={$parameter}";
      $seperator = '&';
    }
  }

  /**
   * Do a get request.
   *
   * @param string $url
   *   The URL relative to the fedora path to use.
   * @param string $headers_only
   *   Returns only the headers
   * @param string $file
   *   The filename to output the request to. If this is set then no headers
   *   will be returned.
   *
   * @return array
   *   The contents of the get request
   *
   * @see CurlConnection::getRequest()
   */
  public function getRequest($url, $headers_only = FALSE, $file = NULL) {
    try {
      return parent::getRequest($this->buildUrl($url), $headers_only, $file);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

  /**
   * @see CurlConnection::postRequest()
   */
  public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    try {
      return parent::postRequest($this->buildUrl($url), $type, $data, $content_type);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

   /**
   * @see CurlConnection::patchRequest()
   */
  public function patchRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    try {
      return parent::patchRequest($this->buildUrl($url), $type, $data, $content_type);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

  /**
   * @see CurlConnection::putRequest()
   */
  public function putRequest($url, $type = 'none', $file = NULL) {
    try {
      return parent::putRequest($this->buildUrl($url), $type, $file);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

  /**
   * @see CurlConnection::deleteRequest()
   */
  public function deleteRequest($url) {
    try {
      return parent::deleteRequest($this->buildUrl($url));
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

  /**
   * This function attempts to parse the exceptions that are recieved from
   * Fedora into something more reasonable. This it very hard since really
   * things like this are garbage in garbage out, but we do what we can.
   *
   * @param Exception $e
   *   The exception being parsed
   */
  protected function parseFedoraExceptions($e) {
    $code = $e->getCode();
    switch ($code) {
      case '400':
        // When setting an error 400 often Fedora puts useful error messages
        // in the message body, we might as well expose them.
        $response = $e->getResponse();
        $message = $response['content'];
        if (!$message || strpos($message, 'Exception') !== FALSE) {
          $message = $e->getMessage();
        }
        break;

      case '500':
        // When setting an error 500 Fedora is usually returning a java stack
        // trace. This isn't great, but we can give a better message by return
        // the message set in that exception .
        $response = $e->getResponse();
        $message = preg_split('/$\R?^/m', $response['content']);
        $message = $message[0];
        break;

      default:
        $message = $e->getMessage();
        break;
    }
    throw new RepositoryException($message, $code, $e);
  }
}
