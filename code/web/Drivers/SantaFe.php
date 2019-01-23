<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/Drivers/Sierra.php';

/**
 * Pika Connector for Arlington's Innovative catalog (Sierra)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 */
class SantaFe extends Sierra{
	public function _getLoginFormValues($patron){
		$loginData = array();
		$loginData['name'] = $patron->cat_username;
		$loginData['code'] = $patron->cat_password;

		return $loginData;
	}

	public function _curl_login($patron) {
		global $logger;
		$loginResult = false;

		$baseUrl = $this->getVendorOpacUrl() . "/patroninfo~S1/IIITICKET";
		$curlUrl   = 'https://catalog.ci.santa-fe.nm.us/iii/cas/login?scope=1&service=' . urlencode($baseUrl);
		$post_data = $this->_getLoginFormValues($patron);

		$logger->log('Loading page ' . $curlUrl, PEAR_LOG_INFO);

		$loginResponse = $this->_curlPostPage($curlUrl, $post_data);

		//When a library uses IPSSO, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResponse, $loginMatches)) {
			$lt = $loginMatches[1]; //Get the lt value
			//Login again
			$post_data['lt']       = $lt;
			$post_data['_eventId'] = 'submit';

			//Don't issue a post, just call the same page (with redirects as needed)
			//Get the ticket from the previous response

			//curl_setopt($this->curl_connection, CURLOPT_URL, $baseUrl);
			usleep(50000);
			$post_string = http_build_query($post_data);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);

			$loginResponse = curl_exec($this->curl_connection);
		}

		if ($loginResponse) {
			$loginResult = true;

			// Check for Login Error Responses
			$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/is', $loginResponse, $matches);
			if ($numMatches > 0) {
				$logger->log('Millennium Curl Login Attempt received an Error response : ' . $matches['error'], PEAR_LOG_DEBUG);
				$loginResult = false;
			} else {

				// Pause briefly after logging in as some follow-up millennium operations (done via curl) will fail if done too quickly
				usleep(150000);
			}
		}

		return $loginResult;
	}
}