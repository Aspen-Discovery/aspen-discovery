<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class API_RegistrationAPI extends Action {
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
					'getForgotPinType',
					'initiatePinReset',
				])) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('UserAPI', $method);
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
				APIUsage::incrementStat('UserAPI', $method);
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

	function getRegistrationCapabilities() {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		$catalogRegistrationCapabilities = $catalog->getRegistrationCapabilities();
		return [
			'success' => true,
			'capabilities' => $catalogRegistrationCapabilities
		];
	}

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

	function lookupAccountByPhoneNumber() {

	}

	function getBasicRegistrationForm() {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->getBasicRegistrationForm();
	}

	function processBasicRegistrationForm() {
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		return $catalog->processBasicRegistrationForm();
	}

	function getForgotPasswordType() {
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

	function initiatePasswordReset() {

	}
}