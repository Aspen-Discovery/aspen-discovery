<?php

require_once ROOT_DIR . '/SummonQuery.php';

abstract class Summon {
     
        const IDENTIFIER_ID = 1;
        const IDENTIFIER_BOOKMARK = 2;
		$results = [];

        /**
     * The URL of the Summon API server
     * @var string
     */
    protected $host = 'http://api.summon.serialssolutions.com';

     /**
     * The API version to use
     *
     * @var string
     */
    protected $version = '2.0.0';

      /**
     * The secret Key used for authentication
     * @var string
     */
    protected $apiKey ='5mxGHdWKx2KsF9sfb87OdpP6Au6x+USkHFx1x9xzF3g=';

        /**
     * The Client ID used for authentication
     * @var string
     */
    protected $apiId ='parliament';

    /**
     * The session for the current transaction
     * @var string
     */
    protected $sessionId = false;

      /**
     * Is the end user authenticated or not?
     * @var bool
     */
    protected $authedUser;

      /**
     * Acceptable response type from Summon
     * Currently summon supports json and xml
     * @var string
     */
    protected $responseType = "json";



     /**
     * Constructor
     *
     * Sets up the Summon API Client
     *
     * @param string $apiId   Summon API ID
     * @param string $apiKey  Summon API Key
     * @param array  $options Associative array of additional options; legal keys:
     *    <ul>
     *      <li>authedUser - is the end-user authenticated?</li>
     *      <li>debug - boolean to control debug mode</li>
     *      <li>host - base URL of Summon API</li>
     *      <li>sessionId - Summon session ID to apply</li>
     *      <li>version - API version to use</li>
     *      <li>responseType - Acceptable response (json or xml)</li>
     *    </ul>
     */
    public function __construct($apiId, $apiKey, $options = array())
    {
        // Process incoming parameters:
        $this->apiId = $apiId;
        $this->apiKey = $apiKey;
        $legalOptions = array(
            'authedUser', 'debug', 'host', 'sessionId', 'version', 'responseType'
        );
        foreach ($legalOptions as $option) {
            if (isset($options[$option])) {
                $this->$option = $options[$option];
            }
        }
    }

     /**
     * Print a message if debug is enabled.
     *
     * @param string $msg Message to print
     *
     * @return void
     */
    protected function debugPrint($msg)
    {
        if ($this->debug) {
            echo "<pre>{$msg}</pre>\n";
        }
    }

      /**
     * Retrieves a document specified by the ID or bookmark.
     *
     * @param string $id     The document to retrieve from the Summon API
     * @param bool   $raw    Return raw (true) or processed (false) response?
     * @param int     idType Constant representing type of $id (either standard
     * identifier -- IDENTIFIER_ID -- or bookmark -- IDENTIFIER_BOOKMARK).
     *
     * @return string    The requested resource
     */
    public function getRecord($id, $raw = false, $idType = self::IDENTIFIER_ID)
    {
        $this->debugPrint("Get Record: $id");

        // Query String Parameters
        $options = $idType === self::IDENTIFIER_BOOKMARK
            ? array('s.bookMark' => $id)
            : array('s.q' => sprintf('ID:"%s"', $id));
        $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';
        return $this->call($options, 'search', 'GET', $raw);
    }

    /**
     * Execute a search.
     *
     * @param Summon_Query $query     Query object
     * @param bool                          $returnErr On fatal error, should we fail
     * outright (false) or treat it as an empty result set with an error key set
     * (true)?
     * @param bool                          $raw       Return raw (true) or processed
     * (false) response?
     *
     * @return array             An array of query results
     */
    public function query($query, $returnErr = false, $raw = false)
    {
        // Query String Parameters
        $options = $query->getOptionsArray();
        $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';

        // Special case -- if user filtered down to newspapers AND excluded them,
        // we can't possibly have any results:
        if (isset($options['s.fvf']) && is_array($options['s.fvf'])
            && in_array('ContentType,Newspaper Article,true', $options['s.fvf'])
            && in_array('ContentType,Newspaper Article', $options['s.fvf'])
        ) {
            return array(
                'recordCount' => 0,
                'documents' => array()
            );
        }

        $this->debugPrint('Query: ' . print_r($options, true));

        try {
            $result = $this->call($options, 'search', 'GET', $raw);
        } catch (SerialsSolutions_Summon_Exception $e) {
            if ($returnErr) {
                return array(
                    'recordCount' => 0,
                    'documents' => array(),
                    'errors' => $e->getMessage()
                );
            } else {
                $this->handleFatalError($e);
            }
        }

        return $result;
    }

    /**
     * Submit REST Request
     *
     * @param array  $params  An array of parameters for the request
     * @param string $service The API Service to call
     * @param string $method  The HTTP Method to use
     * @param bool   $raw     Return raw (true) or processed (false) response?
     *
     * @throws SerialsSolutions_Summon_Exception
     * @return object         The Summon API response (or a PEAR_Error object).
     */
    protected function call($params = array(), $service = 'search', $method = 'GET',
        $raw = false
    ) {
        $baseUrl = $this->host . '/' . $this->version . '/' . $service;

        // Build Query String
        $query = array();
        foreach ($params as $function => $value) {
            if (is_array($value)) {
                foreach ($value as $additional) {
                    $additional = urlencode($additional);
                    $query[] = "$function=$additional";
                }
            } elseif (!is_null($value)) {
                $value = urlencode($value);
                $query[] = "$function=$value";
            }
        }
        asort($query);
        $queryString = implode('&', $query);

        // Build Authorization Headers
        $headers = array(
            'Accept' => 'application/'.$this->responseType,
            'x-summon-date' => gmdate('D, d M Y H:i:s T'),
            'Host' => 'api.summon.serialssolutions.com'
        );
        $data = implode("\n", $headers) . "\n/$this->version/$service\n" .
            urldecode($queryString) . "\n";
        $hmacHash = $this->hmacsha1($this->apiKey, $data);
        $headers['Authorization'] = "Summon $this->apiId;$hmacHash";
        if ($this->sessionId) {
            $headers['x-summon-session-id'] = $this->sessionId;
        }

        // Send request
        $result = $this->httpRequest($baseUrl, $method, $queryString, $headers);
        if (!$raw) {
            // Process response
            $result = $this->process($result); 
        }
        return $result;
    }

      /**
     * Perform normalization and analysis of Summon return value.
     *
     * @param array $input The raw response from Summon
     *
     * @throws SerialsSolutions_Summon_Exception
     * @return array       The processed response from Summon
     */
    protected function process($input)
    {
        if ($this->responseType !== "json") {
            return $input;
        }

        // Unpack JSON Data
        $result = json_decode($input, true);

        // Catch decoding errors -- turn a bad JSON input into an empty result set
        // containing an appropriate error code.
        if (!$result) {
            $result = array(
                'recordCount' => 0,
                'documents' => array(),
                'errors' => array(
                    array(
                        'code' => 'PHP-Internal',
                        'message' => 'Cannot decode JSON response: ' . $input
                    )
                )
            );
        }

          // Detect errors
          if (isset($result['errors']) && is_array($result['errors'])) {
            foreach ($result['errors'] as $current) {
                $errors[] = "{$current['code']}: {$current['message']}";
            }
            $msg = 'Unable to process query<br />Summon returned: ' .
                implode('<br />', $errors);
            throw new SerialsSolutions_Summon_Exception($msg);
        }

        return $result;
    }

     /**
     * Generate an HMAC hash
     *
     * @param string $key  Hash key
     * @param string $data Data to hash
     *
     * @return string      Generated hash
     */
    protected function hmacsha1($key, $data)
    {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize) {
            $key=pack('H*', $hashfunc($key));
        }
        $key=str_pad($key, $blocksize, chr(0x00));
        $ipad=str_repeat(chr(0x36), $blocksize);
        $opad=str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
            'H*', $hashfunc(
                ($key^$opad).pack(
                    'H*', $hashfunc(
                        ($key^$ipad).$data
                    )
                )
            )
        );
        return base64_encode($hmac);
    }

     /**
     * Handle a fatal error.
     *
     * @param Exception $e Exception to process.
     *
     * @return void
     */
    abstract public function handleFatalError($e);

      /**
     * Perform an HTTP request.
     *
     * @param string $baseUrl     Base URL for request
     * @param string $method      HTTP method for request
     * @param string $queryString Query string to append to URL
     * @param array  $headers     HTTP headers to send
     *
     * @throws SerialsSolutions_Summon_Exception
     * @return string             HTTP response body
     */
    abstract protected function httpRequest($baseUrl, $method, $queryString,
        $headers
    );





	
}