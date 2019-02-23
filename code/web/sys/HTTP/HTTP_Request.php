<?php

class HTTP_Request
{
    private $method = 'GET';
    private $url;
    private $rawQuery;
    private $responseBody;
    private $responseInfo;
    private $body;

    public function setMethod($method = 'GET') {
        if ($method != 'GET' && $method != 'POST') {
            global $logger;
            $logger->log('Method must be GET or POST', PEAR_LOG_CRIT);
        } else {
            $this->method = $method;
        }
    }

    public function setURL($url) {
        $this->url = $url;
    }

    public function addRawQueryString($queryString) {
        $this->rawQuery = $queryString;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function sendRequest($saveBody = null)
    {
        if (!isset($this->url)) {
            return new PEAR_Error('URL was not set');
        }
        $curl_opts = array(
            // set request url
            CURLOPT_URL => $this->url,
            // return data
            CURLOPT_RETURNTRANSFER => 1,
            // do not include header in result
            CURLOPT_HEADER => 0,
            // set user agent
            CURLOPT_USERAGENT => 'Pika app cURL Request'
        );
        if ($this->method == 'GET') {
            $curl_opts[CURLOPT_HTTPGET] = true;
            if (isset($this->rawQuery)) {
                $curl_opts[CURLOPT_URL] = $this->url . '?' . $this->rawQuery;
            }
        }else {
            $curl_opts[CURLOPT_POST] = true;
            if ($this->body) {
                $curl_opts[CURLOPT_POSTFIELDS] = $this->body;
            }
        }
        // Get cURL resource
        $curl = curl_init();
        // Set curl options
        curl_setopt_array($curl, $curl_opts);
        // Send the request & save response to $response
        $response = curl_exec($curl);
        if ($response == true) {
            $this->responseInfo = curl_getinfo($curl);
            $this->responseBody = $response;
        }
        // Close request to clear up some resources
        curl_close($curl);

        return $response;
    }

    public function getURL(){
        return $this->url;
    }

    public function getResponseBody(){
        return $this->responseBody;
    }

    public function disconnect(){
        //Nothing needs to be done
    }
    public function getResponseCode(){
        return $this->responseInfo['http_code'];
    }
}