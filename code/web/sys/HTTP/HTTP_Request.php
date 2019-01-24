<?php

class HTTP_Request
{
    private $method = 'GET';
    private $url;
    private $responseBody;
    private $responseInfo;

    public function setMethod($method = 'GET') {
        if ($method == 'GET' || $method == 'POST') {
            global $logger;
            $logger->log('Method must be GET or POST', PEAR_LOG_CRIT);
        } else {
            $this->method = $method;
        }
    }

    public function setURL($url) {
        $this->url = $url;
    }

    public function sendRequest()
    {
        if (!isset($this->url)) {
            return new PEAR_Error('URL was not set');
        }
        $info = array();
        if ($this->method == 'GET') {
            $response = http_get($this->url, array(), $info);
            if ($response) {
                $this->responseBody = http_get_request_body();
                $this->responseInfo = $info;
            }
        }else {
            $response = http_post_data($this->url, $this->post_data, array(), $info);
            if ($response) {
                $this->responseBody = http_get_request_body();
                $this->responseInfo = $info;
            }
        }
        return $response;
    }

    public function getResponseBody(){
        return $this->responseBody;
    }

    public function disconnect(){
        //Nothing needs to be done
    }
}