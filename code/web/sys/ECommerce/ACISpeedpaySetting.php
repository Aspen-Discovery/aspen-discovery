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

	static function getObjectStructure(): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$billerAccount_options = array(
			'username' => 'Username',
			'cat_username' => 'Catalog Username/Barcode'
		);

		$structure = array(
			'id' => array(
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id'
			),
			'name' => array(
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'maxLength' => 50
			),
			'sandboxMode' => array(
				'property' => 'sandboxMode',
				'type' => 'checkbox',
				'label' => 'Use ACI Sandbox',
				'description' => 'Whether or not to use ACI Speedpay in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.'
			),
			'clientId' => array(
				'property' => 'clientId',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'Client identifier used for client authentication. This value is provided by ACI.',
				'hideInLists' => true
			),
			'clientSecret' => array(
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Client Secret',
				'description' => 'Client API token used for client authentication. This value is provided by ACI.',
				'hideInLists' => true
			),
			'apiAuthKey' => array(
				'property' => 'apiAuthKey',
				'type' => 'storedPassword',
				'label' => 'API Auth Key',
				'description' => 'The API key used to access the API. This value is provided by ACI.',
				'hideInLists' => true
			),
			'sdkClientId' => array(
				'property' => 'sdkClientId',
				'type' => 'text',
				'label' => 'SDK Client ID',
				'description' => 'Client identifier used for SDK client authentication. This value is provided by ACI.',
				'hideInLists' => true
			),
			'sdkClientSecret' => array(
				'property' => 'sdkClientSecret',
				'type' => 'storedPassword',
				'label' => 'SDK Client Secret',
				'description' => 'Client token used for SDK client authentication. This value is provided by ACI.',
				'hideInLists' => true
			),
			'sdkApiAuthKey' => array(
				'property' => 'sdkApiAuthKey',
				'type' => 'storedPassword',
				'label' => 'SDK API Auth Key',
				'description' => 'The API key used to access the SDK. This value is provided by ACI.',
				'hideInLists' => true
			),
			'billerId' => array(
				'property' => 'billerId',
				'type' => 'text',
				'label' => 'Biller Id',
				'description' => 'A unique identifier assigned by your ACI project manager.',
				'hideInLists' => true
			),
			'billerAccountId' => array(
				'property' => 'billerAccountId',
				'type' => 'enum',
				'label' => 'Biller Account Id Field',
				'values' => $billerAccount_options,
				'description' => 'The identifier field used to connect payments to users.',
				'hideInLists' => true
			),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			),
		);

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
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update() {
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

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function getServiceFee($amount, $token) {
		$authToken = $this->createAuthToken();
		if (!$authToken) {
			return false;
		}

		$serviceFeeRequest = new CurlWrapper();
		$serviceFeeRequest->addCustomHeaders([
			'Authorization: Bearer ' . $authToken,
			'Content-Type: application/x-www-form-urlencoded',
			"X-Auth-Key: $this->apiAuthKey",
		], false);

		$url = $this->getApiUrl() . '/fee/v3/fees/servicefee';
		$postParams = [
			'billerId' => $this->billerId,
			'paymentChannel' => 'Web',
			'isPayerEnrolled' => true,
			'fundingToken' => $token,
			'paymentOptionKind' => 'OneTimeNow',
			'paymentAmount' => [
				'value' => $amount,
				'currencyCode' => 'USD',
				'precision' => 2
			],
		];

		$serviceFeeResponse = $serviceFeeRequest->curlPostPage($url, $postParams);
		$serviceFeeResponse = json_decode($serviceFeeResponse, true);
		if ($serviceFeeRequest->getResponseCode() == 200) {
			return $serviceFeeResponse['feeAmount'];
		} else {
			return false;
		}
	}

	public function createAuthCode() {
		$baseUrl = $this->getApiUrl();
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$authCodeRequest = new CurlWrapper();
		$authCodeRequest->addCustomHeaders([
			"X-Auth-Key: $this->apiAuthKey",
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], false);
		$postParams = [
			'grant_type' => 'client_credentials',
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret
		];
		$url = $baseUrl . '/auth/v1/auth';
		$authCodeResults = $authCodeRequest->curlPostPage($url, $postParams);
		$authCodeResults = json_decode($authCodeResults, true);
		if (empty($authCodeResults['access_token'])) {
			return false;
		} else {
			return $authCodeResults['access_token'];
		}
	}

	public function createAuthToken() {
		$user = UserAccount::getLoggedInUser();
		$baseUrl = $this->getApiUrl();
		$apiAuthKey = $this->apiAuthKey;
		$billerId = $this->billerId;

		$billerAccountId = $this->billerAccountId;
		$billerAccountId = $user->$billerAccountId;

		if ($this->sandboxMode == 1) {
			$billerAccountId = '56050';
		}

		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$serviceAccountAuthorization = new CurlWrapper();
		$serviceAccountAuthorization->addCustomHeaders([
			"X-Auth-Key: $this->sdkApiAuthKey",
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json'
		], true);

		$postParams = [
			'grant_type' => 'client_credentials',
			'client_id' => $this->sdkClientId,
			'client_secret' => $this->sdkClientSecret,
			'scope' => 'token_exchange',
			'biller_id' => $this->billerId,
			'account_number' => $billerAccountId,
		];

		$url = $baseUrl . '/auth/v1/auth/token';
		$accessTokenResults = $serviceAccountAuthorization->curlPostPage($url, $postParams);
		$accessTokenResults = json_decode($accessTokenResults, true);
		if (empty($accessTokenResults['access_token'])) {
			return false;
		} else {
			return $accessTokenResults['access_token'];
		}
	}

	public function submitTransaction($patron, $payment, $fundingToken, $billerAccount, $accessToken) {
		$result = ['success' => false];

		$this->createAuthCode();


		$paymentAmount = $payment->totalPaid;
		$paymentAmount = (int)((string)($paymentAmount)) * 100;

		//$serviceFee = $this->getServiceFee($paymentAmount, $fundingToken);
		$transactionRequest = new CurlWrapper();
		$transactionRequest->addCustomHeaders([
			'Authorization: Bearer ' . $accessToken,
			'Accept: application/json',
			'Content-Type: application/json',
			"X-Auth-Key: $this->apiAuthKey",
		], false);

		$url = $this->getApiUrl() . '/transaction/v6/payments';
		$postParams = [
			'paymentDate' => date('YYYY-MM-DD'),
			'origination' => [
				'originator' => [
					'id' => 'Aspen Discovery',
					'kind' => 'AppServiceOriginator',
				],
				'paymentChannelKind' => 'Web',
				'paymentOption' => ['kind' => 'OneTimeNow'],
			],
			'fundingAccount' => ['token' => $fundingToken],
			'accountPayments' => [
				'billerAccount' => [
					'billerId' => $this->billerId,
					'billerAccountId' => $billerAccount,
				],
				'principalAmount' => [
					'value' => $paymentAmount,
					'currencyCode' => 'USD',
				],
			],
			'payer' => [
				'kind' => 'NonEnrolledIndividual',
				'address' => [
					'postalCode' => '30092',
					'countryCode' => 'US',
				],
				'emailAddress' => $patron->email,

			]
		];

		$requestTransaction = $transactionRequest->curlPostBodyData($url, $postParams);
		$transactionResponse = json_decode($requestTransaction, true);
		if ($transactionRequest->getResponseCode() == 200) {
			if (substr($transactionResponse['message'], 0, 1) === 'S') {
				$totalPaid = $transactionResponse['accountTransactionResults']['principalAmount']['value'] + $transactionResponse['accountTransactionResults']['serviceFeeAmount']['value'];
				$payment->transactionId = $transactionResponse['confirmationCode'];
				$payment->orderId = $transactionResponse['id'];
				$payment->completed = 1;
				$payment->totalPaid = number_format($totalPaid / 100, 2, '.', '');
				$payment->aciToken = $transactionResponse['fundingAccount']['token'];
				$payment->update();
				$result = [
					'success' => true,
					'message' => 'Payment completed.'
				];
			} else {
				$payment->error = 1;
				$payment->message = $transactionResponse['message'];
				$payment->update();
				$result['message'] = $transactionResponse['message'];
			}
		} else {
			$error = $transactionResponse['error']['status'] . ': ' . $transactionResponse['error']['kind'] . '. ' . $transactionResponse['error']['message'];
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
}