<?php

require_once ROOT_DIR . '/Drivers/Sierra.php';

class SantaFe extends Sierra{
	public function _getLoginFormValues($patron){
		$loginData = array();
		$loginData['name'] = $patron->cat_username;
		$loginData['code'] = $patron->cat_password;

		return $loginData;
	}

	public function _curl_login(User $patron) {
		global $logger;
		$loginResult = false;

		$baseUrl = $this->getVendorOpacUrl() . "/patroninfo~S1/IIITICKET";
		$curlUrl   = 'https://catalog.ci.santa-fe.nm.us/iii/cas/login?scope=1&service=' . urlencode($baseUrl);
		$post_data = $this->_getLoginFormValues($patron);

		$logger->log('Loading page ' . $curlUrl, Logger::LOG_NOTICE);

		$loginResponse = $this->curlWrapper->curlPostPage($curlUrl, $post_data);

		//When a library uses IPSSO, the initial login does a redirect and requires additional parameters.
        if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResponse, $loginMatches)) {
			$lt = $loginMatches[1]; //Get the lt value
			//Login again
			$post_data['lt']       = $lt;
			$post_data['_eventId'] = 'submit';

			//Don't issue a post, just call the same page (with redirects as needed)
			//Get the ticket from the previous response

			usleep(50000);
			$post_string = http_build_query($post_data);
			curl_setopt($this->curlWrapper->curl_connection, CURLOPT_POSTFIELDS, $post_string);

			$loginResponse = curl_exec($this->curlWrapper->curl_connection);
		}

		if ($loginResponse) {
			$loginResult = true;

			// Check for Login Error Responses
			$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/is', $loginResponse, $matches);
			if ($numMatches > 0) {
				$logger->log('Millennium Curl Login Attempt received an Error response : ' . $matches['error'], Logger::LOG_DEBUG);
				$loginResult = false;
			} else {

				// Pause briefly after logging in as some follow-up millennium operations (done via curl) will fail if done too quickly
				usleep(150000);
			}
		}

		return $loginResult;
	}
}