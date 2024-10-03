<?php
require_once ROOT_DIR . '/services/API/AbstractAPI.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class API_RegistrationAPI extends AbstractAPI {
	/**
	 * Processes method to determine return type and calls the correct method.
	 * Should not be called directly.
	 *
	 * @see Action::launch()
	 * @access private
	 */
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$output = '';

		//Set Headers
		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getRegistrationCapabilities',
					'lookupAccountByEmail',
					'lookupAccountByPhoneNumber',
					'getBasicRegistrationForm',
					'processBasicRegistrationForm',
					'getForgotPasswordType',
					'initiatePasswordResetByEmail',
					'initiatePasswordResetByBarcode',
					'getSelfRegistrationForm',
					'getSelfRegistrationTerms',
					'processSelfRegistration'
				])) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('RegistrationAPI', $method);
					$output = json_encode(['result' => $this->$method()]);
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('UserAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			if ($method != 'getUserForApiCall' && method_exists($this, $method)) {
				$result = [
					'result' => $this->$method(),
				];
				$output = json_encode($result);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('RegistrationAPI', $method);
			} else {
				$output = json_encode(['error' => 'invalid_method']);
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}

	/** @noinspection PhpUnused */
	function getRegistrationCapabilities() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		$catalogRegistrationCapabilities = $catalog->getRegistrationCapabilities();
		return [
			'success' => true,
			'capabilities' => $catalogRegistrationCapabilities
		];
	}

	/** @noinspection PhpUnused */
	function lookupAccountByEmail() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['email'])) {
			$email = $_REQUEST['email'];
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return [
					'success' => false,
					'message' => translate(['text' => 'The email supplied was not valid.', 'isPublicFacing' => true])
				];
			} else {
				return $catalog->lookupAccountByEmail($email);
			}
		} else {
			return [
				'success' => false,
				'message' => translate(['text' => 'email was not supplied', 'isPublicFacing' => true])
			];
		}
	}

	/** @noinspection PhpUnused */
	function lookupAccountByPhoneNumber() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['phone'])) {
			$phone = $_REQUEST['phone'];
			$phone = preg_replace('/[^0-9]/', '', $phone);
			if (strlen($phone) >= 7 && strlen($phone) <= 11) {
				$result = $catalog->lookupAccountByPhoneNumber($phone);
			}else{
				$result = [
					'success' => false,
					'message' => translate(['text' => 'The phone number supplied was not valid.', 'isPublicFacing' => true])
				];
			}
			if ($result['success'] == false) {
				$phone = $_REQUEST['phone'];
				if (substr($phone, 0, 1) == '+') {
					$phone = preg_replace('/[^0-9]/', '', $phone);
					if (strlen($phone) >= 10) {
						$phone = substr($phone, strlen($phone) - 10, 10);
						$result = $catalog->lookupAccountByPhoneNumber($phone);
					}
				}
			}
			return $result;
		}
		return [
			'success' => false,
			'message' => translate(['text' => 'This method is not currently available', 'isPublicFacing' => true])
		];
	}

	/** @noinspection PhpUnused */
	function getBasicRegistrationForm() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->getBasicRegistrationForm();
	}

	/** @noinspection PhpUnused */
	function processBasicRegistrationForm() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		$addressValidated = false;
		if (isset($_REQUEST['addressValidated'])) {
			if ($_REQUEST['addressValidated'] == 'on') {
				$addressValidated = true;
			}else{
				$addressValidated = boolval($_REQUEST['addressValidated']);
			}
		}
		return $catalog->processBasicRegistrationForm($addressValidated);
	}

	/** @noinspection PhpUnused */
	function getSelfRegistrationForm() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->getSelfRegistrationFields();
	}

	/** @noinspection PhpUnused */
	function getSelfRegistrationTerms() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->getSelfRegistrationTerms();
	}

	/** @noinspection PhpUnused */
	function processSelfRegistration() : array {
		global $library;
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);

		require_once ROOT_DIR . '/sys/Administration/USPS.php';
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$uspsInfo = USPS::getUSPSInfo();
		$streetAddress = '';
		$city = '';
		$state = '';
		$zip = '';
		$dob = '';
		foreach ($_REQUEST as $selfRegValue => $val){
			if (!(preg_match('/(.*?)address2(.*)|(.*?)borrower_B(.*)|(.*?)borrower_alt(.*)/', $selfRegValue))){
				if (preg_match('/(.*?)address|street(.*)/', $selfRegValue)){
					$streetAddress = $val;
				}
				elseif (preg_match('/(.*?)city(.*)/', $selfRegValue)){
					$city = $val;
				}
				elseif (preg_match('/(.*?)state(.*)/', $selfRegValue)){
					//USPS does not accept anything other than 2 character state codes but will use the ZIP to fill in the blank
					if (strlen($val) == 2){
						$state = $val;
					}
				}
				elseif (preg_match('/(.*?)zip(.*)/', $selfRegValue)){
					$zip = $val;
				}
				elseif (preg_match('/(.*?)dob|dateofbirth|birth[dD]ate(.*)/', $selfRegValue)){
					$dob = $val;
				}
			}
		}

		if($uspsInfo) {
			if (SystemUtils::validateAddress($streetAddress, $city, $state, $zip)){
				if (!empty($dob)) {
					if (SystemUtils::validateAge($library->minSelfRegAge, $dob)) {
						return $catalog->selfRegister();
					} else {
						$ageMessage = translate([
							'text' => 'Age not valid.',
							'isPublicFacing' => true
						]);

						return [
							'success' => false,
							'title' => '',
							'message' => $ageMessage
						];
					}
				} else {
					return $catalog->selfRegister();
				}
			} else {
				$addressMessage = translate([
					'text' => 'The address you entered does not appear to be valid. Please check your address and try again.',
					'isPublicFacing' => true
				]);

				return [
					'success' => false,
					'title' => '',
					'message' => $addressMessage
				];
			}
		} else {
			if (!empty($dob)) {
				if (SystemUtils::validateAge($library->minSelfRegAge, $dob)){
					return $catalog->selfRegister();
				} else {
					$ageMessage = translate([
						'text' => 'Age should be at least' . $library->minSelfRegAge . ' years. Please enter a valid Date of Birth.',
						'isPublicFacing' => true
					]);
					return [
						'success' => false,
						'title' => '',
						'message' => $ageMessage
					];
				}
			} else {
				return $catalog->selfRegister();
			}
		}
	}

	/** @noinspection PhpUnused */
	function getForgotPasswordType() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		$forgotPasswordType = $catalog->getForgotPasswordType();
		$labels = [
			'emailResetLink' => 'Reset my PIN/Password',
			'emailAspenResetLink' => 'Start PIN/Password Reset',
			'none' => '',
			'emailPin' => 'Recover PIN/Password'

		];
		global $configArray;
		$resetPasswordLink = '';


		switch ($forgotPasswordType) {
			case 'emailAspenResetLink':
				$resetPasswordLink = $configArray['Site']['url'] . '/MyAccount/InitiateResetPin';
				break;
			case 'emailResetLink':
				$resetPasswordLink = $configArray['Site']['url'] . '/MyAccount/EmailResetPin';
				break;
			case 'emailPin':
				$resetPasswordLink = $configArray['Site']['url'] . '/MyAccount/EmailPin';
				break;
		}
		return [
			'success' => true,
			'forgotPasswordType' => $forgotPasswordType,
			'label' => translate(['text' => $labels[$forgotPasswordType], 'isPublicFacing' => true, 'inAttribute' => true]),
			'resetPasswordLink' => $resetPasswordLink,
		];
	}

	/** @noinspection PhpUnused */
	function initiatePasswordResetByEmail() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->initiatePasswordResetByEmail();
	}

	/** @noinspection PhpUnused */
	function initiatePasswordResetByBarcode() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->initiatePasswordResetByBarcode();
	}
}