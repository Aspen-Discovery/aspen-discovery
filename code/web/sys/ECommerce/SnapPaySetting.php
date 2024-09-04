<?php

class SnapPaySetting extends DataObject {
	public $__table = 'snappay_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $accountId;
	public $merchantId;
	public $apiAuthenticationCode;
	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
				'label' => 'Use SnapPay Sandbox (for testing payments only, does not collect money)',
				'description' => 'Whether or not to use SnapPay in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'accountId' => [
				'property' => 'accountId',
				'type' => 'text',
				'label' => 'Account ID',
				'description' => 'The Account ID to use when paying fines with SnapPay.',
				'hideInLists' => false,
				'default' => '',
				'size' => 10,
			],
			'merchantId' => [
				'property' => 'merchantId',
				'type' => 'text',
				'label' => 'Merchant ID',
				'description' => 'The Merchant ID to use when paying fines with SnapPay.',
				'hideInLists' => false,
				'default' => '',
				'size' => 20,
			],
			'apiAuthenticationCode' => [
				'property' => 'apiAuthenticationCode',
				'type' => 'storedPassword',
				'label' => 'API Authentication Code',
				'description' => 'The API Authentication Code to use when paying fines with SnapPay.',
				'hideInLists' => true,
				'default' => '',
				'size' => 255,
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

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->snapPaySettingId = $this->id;
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
		if ($name == 'libraries') {
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

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
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
					if ($library->snapPaySettingId != $this->id) {
						$library->finePaymentType = 15;
						$library->snapPaySettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->snapPaySettingId == $this->id) {
						if ($library->finePaymentType == 15) {
							$library->finePaymentType = 0;
						}
						$library->snapPaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	private function createPaymentIntent($patron, $paymentAmount, $paymentMethodId) {
		$baseUrl = 'https://stage.snappayglobal.com/Interop/HostedPaymentPage';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$paymentIntentSetup = new CurlWrapper();
		$paymentIntentSetup->addCustomHeaders([
			'Authorization: Bearer ' . $this->stripeSecretKey,
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], false);

		$postParams = [
			'amount' => $paymentAmount,
			'currency' => 'usd',
			'automatic_payment_methods' => [
				'enabled' => 'true',
				'allow_redirects' => 'never',
			],
			'payment_method' => $paymentMethodId,
		];

		$url = $baseUrl . '/v1/payment_intents';
		$paymentIntent = $paymentIntentSetup->curlPostPage($url, $postParams);
		return json_decode($paymentIntent, true);
	}

	/*
	 * @return array|bool
	 */
	public function submitTransaction($patron, $payment, $paymentMethodId, $transactionType): mixed {
		$result = ['success' => false];

		$paymentAmount = $payment->totalPaid;
		$paymentAmount = $paymentAmount * 100;
		$paymentAmount = (int)$paymentAmount;

		$paymentIntent = $this->createPaymentIntent($patron, $paymentAmount, $paymentMethodId);
		$paymentIntentId = $paymentIntent['id'];

		$paymentRequest = new CurlWrapper();
		$url = 'https://api.stripe.com/v1/payment_intents/' . $paymentIntentId . '/confirm';

		$paymentRequest->addCustomHeaders([
			'Accept: application/json',
			'Authorization: Bearer ' . $this->stripeSecretKey,
			'Content-Type: application/x-www-form-urlencoded',
		], true);

		$paymentTransaction = $paymentRequest->curlPostBodyData($url, null);

		$paymentResponse = json_decode($paymentTransaction, true);
		if ($paymentRequest->getResponseCode() == 200) {
			{
				$totalPaid = $paymentResponse['amount_received'];
				$payment->transactionId = $paymentResponse['id'];
				$payment->orderId = $paymentResponse['id'];
				$payment->totalPaid = number_format($totalPaid / 100, 2, '.', '');

				if ($transactionType == 'donation') {
					$payment->message .= "Donation sent, TransactionId = $payment->transactionId, Net Amount = $payment->totalPaid. ";
					$payment->update();
					return [
						'success' => true,
						'message' => translate([
							'text' => 'Your donation has been sent. Thank you! ',
							'isPublicFacing' => true,
						]),
					];
				} else {
					$user = new User();
					$user->id = $payment->userId;
					if ($user->find(true)) {
						$finePaymentCompleted = $user->completeFinePayment($payment);
						if ($finePaymentCompleted['success']) {
							$payment->message .= "Payment completed, TransactionId = $payment->transactionId, Net Amount = $payment->totalPaid. ";
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
				}
			}
		} else {
			if (isset($paymentResponse['error']['message']['default'])) {
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
}