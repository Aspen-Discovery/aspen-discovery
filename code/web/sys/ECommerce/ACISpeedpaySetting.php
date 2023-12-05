<?php

/**
 * Class ACISpeedpaySetting - Store settings for ACI Speedpay
 */
class ACISpeedpaySetting extends DataObject {
	public $__table = 'aci_speedpay_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $clientId;
	public $clientSecret;
	public $apiAuthKey;
	public $sdkClientId;
	public $sdkClientSecret;
	public $sdkApiAuthKey;
	public $billerId;
	public $billerAccountId;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$billerAccount_options = [
			'username' => 'Username',
			'cat_username' => 'Catalog Username/Barcode',
		];

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'maxLength' => 50,
			],
			'sandboxMode' => [
				'property' => 'sandboxMode',
				'type' => 'checkbox',
				'label' => 'Use ACI Sandbox',
				'description' => 'Whether or not to use ACI Speedpay in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'clientId' => [
				'property' => 'clientId',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'Client identifier used for client authentication. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Client Secret',
				'description' => 'Client API token used for client authentication. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'apiAuthKey' => [
				'property' => 'apiAuthKey',
				'type' => 'storedPassword',
				'label' => 'API Auth Key',
				'description' => 'The API key used to access the API. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'sdkClientId' => [
				'property' => 'sdkClientId',
				'type' => 'text',
				'label' => 'SDK Client ID',
				'description' => 'Client identifier used for SDK client authentication. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'sdkClientSecret' => [
				'property' => 'sdkClientSecret',
				'type' => 'storedPassword',
				'label' => 'SDK Client Secret',
				'description' => 'Client token used for SDK client authentication. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'sdkApiAuthKey' => [
				'property' => 'sdkApiAuthKey',
				'type' => 'storedPassword',
				'label' => 'SDK API Auth Key',
				'description' => 'The API key used to access the SDK. This value is provided by ACI.',
				'hideInLists' => true,
			],
			'billerId' => [
				'property' => 'billerId',
				'type' => 'text',
				'label' => 'Biller Id',
				'description' => 'A unique identifier assigned by your ACI project manager.',
				'hideInLists' => true,
			],
			'billerAccountId' => [
				'property' => 'billerAccountId',
				'type' => 'enum',
				'label' => 'Biller Account Id Field',
				'values' => $billerAccount_options,
				'description' => 'The identifier field used to connect payments to users.',
				'hideInLists' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	function getNumericColumnNames(): array {
		return ['customerId'];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->aciSpeedpaySettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->aciSpeedpaySettingId != $this->id) {
						$library->finePaymentType = 8;
						$library->aciSpeedpaySettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->aciSpeedpaySettingId == $this->id) {
						if ($library->finePaymentType == 8) {
							$library->finePaymentType = 0;
						}
						$library->aciSpeedpaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function getServiceFee($amount, $token, $authCode) {
		$currencyCode = 'USD';
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		if (SystemVariables::getSystemVariables()->currencyCode) {
			$currencyCode = SystemVariables::getSystemVariables()->currencyCode;
		}

		$serviceFeeRequest = new CurlWrapper();
		$serviceFeeRequest->addCustomHeaders([
			'Authorization: Bearer ' . $authCode,
			'Content-Type: application/json',
			"X-Auth-Key: $this->apiAuthKey",
		], false);

		$url = $this->getApiUrl() . '/fee/v3/fees/payments/servicefee';
		$postParams = [
			'billerId' => $this->billerId,
			'paymentChannel' => 'Web',
			'isPayerEnrolled' => false,
			'fundingToken' => $token,
			'paymentOptionKind' => 'OneTimeNow',
			'paymentAmount' => [
				'value' => $amount,
				'currencyCode' => $currencyCode,
				'precision' => 2,
			],
		];

		$serviceFeeResponse = $serviceFeeRequest->curlPostBodyData($url, $postParams);
		$serviceFeeResponse = json_decode($serviceFeeResponse, true);
		if ($serviceFeeRequest->getResponseCode() == 200) {
			return $serviceFeeResponse['feeAmount'];
		} else {
			return false;
		}
	}

	/*
	 * @return array|bool
	 */
	public function createAuthCode(): mixed {
		$state = random_bytes(6);
		$state = bin2hex($state);
		$codeVerifier = $this->generateCodeVerifier();
		$codeChallenge = $this->generateCodeChallenge($codeVerifier);
		$baseUrl = $this->getApiUrl();
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$authCodeRequest = new CurlWrapper();
		$authCodeRequest->addCustomHeaders([
			"X-Auth-Key: $this->apiAuthKey",
			'Accept: application/json',
		], false);
		$postParams = [
			'response_type' => 'code',
			'client_id' => $this->clientId,
			'code_challenge' => $codeChallenge,
			'code_challenge_method' => 'S256',
			'state' => $state,

		];

		$params = $this->buildQueryString($postParams);
		$url = $this->appendQuery($baseUrl . '/auth/v1/auth/authorize', $params);
		$authorizationResults = $authCodeRequest->curlGetPage($url);
		$authCodeResults = json_decode($authorizationResults, true);
		if (empty($authCodeResults['code'])) {
			if(!empty($authCodeResults['error'])) {
				if (isset($authCodeResults['error']['message']['default'])) {
					$message = $authCodeResults['error']['message']['default'];
				} else {
					$message = $authCodeResults['error']['message'];
				}
				return [
					'success' => false,
					'message' => $message,
				];
			}
			return false;
		} else {
			$token = $this->obtainAccessToken($codeVerifier, $authCodeResults['code']);
			if($token) {
				return [
					'success' => true,
					'token' => $token,
				];
			}
			return [
				'success' => false,
				'message' => 'Unable to validate session with ACI Speedpay.',
			];
		}
	}

	private function obtainAccessToken($codeVerifier, $code) {
		$baseUrl = $this->getApiUrl();
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$authCodeRequest = new CurlWrapper();
		$authCodeRequest->addCustomHeaders([
			"X-Auth-Key: $this->apiAuthKey",
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], false);
		$postParams = [
			'grant_type' => 'authorization_code',
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'biller_id' => $this->billerId,
			'code' => $code,
			'code_verifier' => $codeVerifier
		];
		$url = $baseUrl . '/auth/v1/auth/token';
		$authCodeResults = $authCodeRequest->curlPostPage($url, $postParams);
		$authCodeResults = json_decode($authCodeResults, true);
		if (empty($authCodeResults['access_token'])) {
			return false;
		} else {
			return $authCodeResults['access_token'];
		}
	}

	/*
	 * @return array|bool
	 */
	public function submitTransaction($patron, $payment, $fundingToken, $billerAccount): mixed {
		$result = ['success' => false];

		$authCode = $this->createAuthCode();
		if(!$authCode['success']) {
			// return error if we couldn't obtain authCode
			return $authCode;
		}

		$paymentAmount = $payment->totalPaid;
		$paymentAmount = $paymentAmount * 100;
		$paymentAmount = (int)$paymentAmount;

		$serviceFee = $this->getServiceFee($paymentAmount, $fundingToken, $authCode['token']);

		$today = new DateTime('now');
		$today = $today->format('Y-m-d');

		$currencyCode = "USD";
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		if(SystemVariables::getSystemVariables()->currencyCode) {
			$currencyCode = SystemVariables::getSystemVariables()->currencyCode;
		}

		$paymentRequest = new CurlWrapper();
		$url = $this->getApiUrl() . '/transaction/v6/payments';

		$postData = array();
		$postData['id'] = $payment->orderId;
		$postData['paymentDate'] = $today;
		$postData['origination'] = array(
			'originator' => array(
				'kind' => 'User',
			),
			'paymentChannelKind' => 'Web',
			'paymentOption' => array(
				'kind' => 'OneTimeNow',
			),
		);
		$postData['payer'] = array(
			'kind' => 'NonEnrolledIndividual',
			'emailAddress' => $patron->email,
			'firstName' => $patron->firstname,
			'lastName' => $patron->lastname,
		);
		$postData['fundingAccount'] = array(
			'token' => $fundingToken,
		);
		$postData['accountPayments'] = array(
			array(
			'ordinal' => 1,
			'billerAccount' => array(
				'billerId' => $this->billerId,
				'billerAccountId' => $billerAccount,
			),
			'serviceFeeAmount' => $serviceFee,
			'principalAmount' => array(
				'value' => $paymentAmount,
				'currencyCode' => $currencyCode,
				'precision' => 2,
			),
		));

		$paymentRequest->addCustomHeaders([
			'Accept: application/json',
			'Authorization: Bearer ' . $authCode['token'],
			'Content-Type: application/json',
			'X-Auth-Key: ' . $this->apiAuthKey,
		], true);

		$paymentTransaction = $paymentRequest->curlPostBodyData($url, $postData);

		$paymentResponse = json_decode($paymentTransaction, true);
		if ($paymentRequest->getResponseCode() == 200) {
			if (str_starts_with($paymentResponse['message']['code'], 'S')) {
				$ccNumber = $paymentResponse['fundingAccountSummary']['name'];
				$confirmationCode = $paymentResponse['confirmationCode'];
				$totalPaid = $paymentResponse['accountTransactionResults'][0]['principalAmount']['value'] + $paymentResponse['accountTransactionResults'][0]['serviceFeeAmount']['value'];
				$payment->transactionId = $paymentResponse['confirmationCode'];
				$payment->orderId = $paymentResponse['id'];

				$user = new User();
				$user->id = $payment->userId;
				if ($user->find(true)) {
					$finePaymentCompleted = $user->completeFinePayment($payment);
					if ($finePaymentCompleted['success']) {
						$payment->totalPaid = number_format($totalPaid / 100, 2, '.', '');
						$payment->message .= "Payment completed, TransactionId = $payment->transactionId, Confirmation Code = $confirmationCode, CC Number = $ccNumber, Net Amount = $payment->totalPaid. ";
						$payment->update();
						return [
							'success' => true,
							'message' => translate([
								'text' => 'Your payment has been completed. ',
								'isPublicFacing' => true,
							]),
						];
					} else {
						$payment->error = true;
						$payment->message .= $finePaymentCompleted['message'];
						$payment->update();
						return [
							'success' => false,
							'message' => $finePaymentCompleted['message'],
						];
					}
				} else {
					$payment->error = true;
					$payment->message .= 'Could not find user to mark the fine paid in the ILS.';
					$payment->update();
				}

			} else {
				$payment->error = 1;
				$payment->message = $paymentResponse['message']['default'];
				$payment->update();
				$result['message'] = $paymentResponse['message']['default'];
			}
		} else {
			if(isset($paymentResponse['error']['message']['default'])) {
				$message = $paymentResponse['error']['message']['default'];
			} else {
				$message = $paymentResponse['error']['message'];
			}
			$error = $paymentResponse['error']['status'] . ': ' . $message;
			$payment->error = 1;
			$payment->message = $error;
			$payment->update();
			$result['message'] = $error;
		}

		return $result;
	}

	public function getApiUrl() {
		$baseUrl = 'https://api.acispeedpay.com';
		if ($this->sandboxMode == 1) {
			$baseUrl = 'https://sandbox-api.acispeedpay.com';
		}
		return $baseUrl;
	}

	protected function buildQueryString(array $params): string {
		return http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
	}

	protected function appendQuery($url, $query): string {
		$query = trim($query, '?&');

		if ($query) {
			$glue = strstr($url, '?') === false ? '?' : '&';
			return $url . $glue . $query;
		}

		return $url;
	}

	protected function generateCodeVerifier() {
		$verifier_bytes = random_bytes(64);
		return rtrim(strtr(base64_encode($verifier_bytes), '+/', '-_'), '=');
	}
	protected function generateCodeChallenge($verifier) {
		$challenge_bytes = hash('sha256', $verifier, true);
		return rtrim(strtr(base64_encode($challenge_bytes), '+/', '-_'), '=');
	}
}