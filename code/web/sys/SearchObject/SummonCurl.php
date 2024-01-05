<?php

require_once ROOT_DIR '/Summon.php';


class Summon_CURL extends Summon
{
    /**
     * Handle a fatal error.
     *
     * @param Exception $e Exception to process.
     *
     * @return void
     */
    public function handleFatalError($e)
    {
        throw $e;
    }

    /**
     * Perform a GET HTTP request.
     *
     * @param string $baseUrl     Base URL for request
     * @param string $method      HTTP method for request
     * @param string $queryString Query string to append to URL
     * @param array  $headers     HTTP headers to send
     *
     * @throws Exception
     * @return string             HTTP response body
     */
    protected function httpRequest($baseUrl, $method, $queryString, $headers)
    {
        $this->debugPrint(
            "{$method}: {$baseUrl}?{$queryString}"
        );

        // Modify headers as summon needs it in "key: value" format
        $modified_headers = array();
        foreach ($headers as $key=>$value) {
            $modified_headers[] = $key.": ".$value;
        }

        $curl = curl_init();
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "{$baseUrl}?{$queryString}",
            CURLOPT_HTTPHEADER => $modified_headers
        );
        curl_setopt_array($curl, $curlOptions);
        $result = curl_exec($curl);
        if ($result === false) {
            throw new Exception("Error in HTTP Request.");
        }
        curl_close($curl);

        return $result;
    }
}
