<?php

require_once ROOT_DIR . '/Drivers/HorizonAPI.php';

abstract class HorizonAPI3_23 extends HorizonAPI {
	private function getBaseWebServiceUrl() {
		if (!empty($this->accountProfile->patronApiUrl)) {
			$webServiceURL = $this->accountProfile->patronApiUrl;
		} else {
			global $logger;
			$logger->log('No Web Service URL defined in Horizon API Driver', Logger::LOG_ALERT);
			echo("Web service URL must be defined in the account profile to work with the Horizon API");
			die();
		}

		$urlParts = parse_url($webServiceURL);
		$baseWebServiceUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . (!empty($urlParts['port']) ? ':' . $urlParts['port'] : '');

		return $baseWebServiceUrl;
	}

	/**
	 * @param User $patron
	 * @param string $oldPin
	 * @param string $newPin
	 * @return string[] a message to the user letting them know what happened
	 */
	function updatePin(User $patron, string $oldPin, string $newPin) {
		//Log the user in
		[
			$userValid,
			$sessionToken,
		] = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
		if (!$userValid) {
			return [
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
			];
		}

		$updatePinUrl = $this->getBaseWebServiceUrl() . '/hzws/user/patron/changeMyPin';
		$jsonParameters = [
			'currentPin' => $oldPin,
			'newPin' => $newPin,
		];
		$updatePinResponse = $this->getWebServiceResponseUpdated($updatePinUrl, $jsonParameters, $sessionToken);
		if (isset($updatePinResponse['messageList'])) {
			$errors = '';
			foreach ($updatePinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, Logger::LOG_ERROR);
			return [
				'success' => false,
				'message' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.',
			];
		} elseif (!empty($updatePinResponse['sessionToken'])) {
			// Success response isn't particularly clear, but returning the session Token seems to indicate the pin updated. plb 8-15-2016
			$patron->cat_password = $newPin;
			$patron->update();
			return [
				'success' => true,
				'message' => "Your pin number was updated successfully.",
			];
		} else {
			return [
				'success' => false,
				'message' => "Sorry, we could not update your pin number. Please try again later.",
			];
		}
	}

	/**
	 * @param User $user
	 * @param string $newPin
	 * @param null|string $resetToken
	 * @return array
	 */
	function resetPin($user, $newPin, $resetToken = null) {
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', Logger::LOG_ERROR);
			return [
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later',
			];
		}

		$changeMyPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/user/patron/changeMyPin';
		$jsonParameters = [
			'resetPinToken' => $resetToken,
			'newPin' => $newPin,
		];
		$changeMyPinResponse = $this->getWebServiceResponseUpdated($changeMyPinAPIUrl, $jsonParameters);
		if (isset($changeMyPinResponse['messageList'])) {
			$errors = '';
			foreach ($changeMyPinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, Logger::LOG_ERROR);
			return [
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.',
			];
		} elseif (!empty($changeMyPinResponse['sessionToken'])) {
			if ($user->username == $changeMyPinResponse['patronKey']) { // Check that the ILS user matches the Aspen Discovery user
				$user->cat_password = $newPin;
				$user->update();
			}
			return [
				'success' => true,
			];
//			return "Your pin number was updated successfully.";
		} else {
			return [
				'error' => "Sorry, we could not update your pin number. Please try again later.",
			];
		}
	}

	function getEmailResetPinResultsTemplate() {
		return 'emailResetPinResults.tpl';
	}

	// Newer Horizon API version
	function processEmailResetPinForm() {
		$barcode = $_REQUEST['barcode'];

		$patron = new User;
		$patron->get('cat_username', $barcode);
		if (!empty($patron->id)) {
			global $configArray;
			$userID = $patron->id;

			// If possible, check if Horizon has an email address for the patron
			if (!empty($patron->cat_password)) {
				[
					$userValid,
					$sessionToken,
				] = $this->loginViaWebService($barcode, $patron->cat_password);
				if ($userValid) {
					// Yay! We were able to login with the pin Aspen Discovery has!

					//Now check for an email address
					$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true');
					if ($lookupMyAccountInfoResponse) {
						if (isset($lookupMyAccountInfoResponse->AddressInfo)) {
							if (empty($lookupMyAccountInfoResponse->AddressInfo->email)) {
								// return an error message because horizon doesn't have an email.
								return [
									'success' => false,
									'error' => 'The circulation system does not have an email associated with this card number. Please contact your library to reset your pin.',
								];
							}
						}
					}
				}
			}

			// email the pin to the user
			$resetPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/user/patron/resetMyPin';
			$jsonPOST = [
				'login' => $barcode,
				'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>' . (empty($userID) ? '' : '&uid=' . $userID),
			];

			$resetPinResponse = $this->getWebServiceResponseUpdated($resetPinAPIUrl, $jsonPOST);
			// Reset Pin Response is empty JSON on success.

			if ($resetPinResponse === [] && !isset($resetPinResponse['messageList'])) {
				return [
					'success' => true,
				];
			} else {
				$result = [
					'success' => false,
					'error' => "Sorry, we could not email your pin to you.  Please visit the library to reset your pin.",
				];
				if (isset($resetPinResponse['messageList'])) {
					$errors = '';
					foreach ($resetPinResponse['messageList'] as $errorMessage) {
						$errors .= $errorMessage['message'] . ';';
					}
					global $logger;
					$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, Logger::LOG_ERROR);
				}
				return $result;
			}


		} else {
			return [
				'success' => false,
				'error' => 'Sorry, we did not find the card number you entered or you have not logged into the catalog previously.  Please contact your library to reset your pin.',
			];
		}
	}


	/**
	 *  Handles API calls to the newer Horizon APIs.
	 *
	 * @param $url
	 * @param array $post POST variables get encoded as JSON
	 * @return bool|mixed|SimpleXMLElement
	 */
	public function getWebServiceResponseUpdated($url, $post = [], $sessionToken = '') {
		global $configArray;
		$requestHeaders = [
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: Aspen Discovery',
			'x-sirs-clientId: ' . $configArray['Catalog']['clientId'],
		];

		if (!empty($sessionToken)) {
			$requestHeaders[] = "x-sirs-sessionToken: $sessionToken";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		if (!empty($post)) {
			$post = json_encode($post);  // Turn Post Fields into JSON Data
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//		curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enables request headers for curl_getinfo()
		$curlResponse = curl_exec($ch);

//		$info = curl_getinfo($ch);  // for debugging curl calls

		curl_close($ch);

		if ($curlResponse !== false && $curlResponse !== 'false') {
			$response = json_decode($curlResponse, true);
			if (json_last_error() == JSON_ERROR_NONE) {
				return $response;
			} else {
				global $logger;
				$logger->log('Error Parsing JSON response in WCPL Driver: ' . json_last_error_msg(), Logger::LOG_ERROR);
				return false;
			}


		} else {
			global $logger;
			$logger->log('Curl problem in getWebServiceResponseUpdated', Logger::LOG_WARNING);
			return false;
		}
	}

	function getForgotPasswordType() {
		return 'emailResetLink';
	}

	function getEmailResetPinTemplate() {
		return 'sirsiROAEmailResetPinLink.tpl';
	}
}