<?php

require_once ROOT_DIR . '/Drivers/Sierra.php';

class Arlington extends Sierra{
	public function _getLoginFormValues($patron){
		$loginData = array();
		$loginData['pin'] = $patron->cat_password;
		$loginData['code'] = $patron->cat_username;
		$loginData['submit'] = 'submit';
		return $loginData;
	}

	public function getSelfRegistrationFields() {
		header('Location: http://library.arlingtonva.us/services/accounts-and-borrowing/get-a-free-library-card/');
		die;
	}

	public function hasUsernameField(){
		return true;
	}

    /**
     * @param User $user
     * @param string $oldPin
     * @param string $newPin
     * @return string[] The message to the user updating them on status
     */
	function updatePin($user, $oldPin, $newPin){
		$scope = $this->getDefaultScope();

		//First we have to login to classic
		$this->_curl_login($user);

		//Now we can get the page
		$curlUrl = $this->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $user->username ."/newpin";

		$post = array(
			'pin'        => $oldPin,
			'pin1'       => $newPin,
			'pin2'       => $newPin,
			'pat_submit' => 'xxx'
		);
		$curlResponse = $this->curlWrapper->curlPostPage($curlUrl, $post);

		if ($curlResponse) {
			if (stripos($curlResponse, 'Your PIN has been modified.')) {
				$user->cat_password = $newPin;
				$user->update();
				return ['success' => true, 'message' => "Your pin number was updated successfully."];
			} else if (preg_match('/class="errormessage">(.+?)<\/div>/is', $curlResponse, $matches)){
				return ['success' => false, 'message' => trim($matches[1])];

			} else {
				return ['success' => false, 'message' => "Sorry, your PIN has not been modified : unknown error. Please try again later."];
			}

		} else {
			return ['success' => false, 'message' => "Sorry, we could not update your pin number. Please try again later."];
		}

	}

}