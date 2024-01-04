<?php


class StripeSetting extends DataObject {
	public $__table = 'stripe_settings';
	public $id;
	public $name;
	public $stripePublicKey;
	public $stripeSecretKey;

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
			'stripePublicKey' => [
				'property' => 'stripePublicKey',
				'type' => 'text',
				'label' => 'Public Key',
				'description' => 'The Public Key to use when paying fines with Stripe.',
				'hideInLists' => false,
				'default' => '',
				'size' => 100,
			],
			'stripeSecretKey' => [
				'property' => 'stripeSecretKey',
				'type' => 'storedPassword',
				'label' => 'Secret Key',
				'description' => 'The Secret Key to use when paying fines with Stripe.',
				'hideInLists' => true,
				'default' => '',
				'size' => 100,
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
				$obj->stripeSettingId = $this->id;
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
					if ($library->stripeSettingId != $this->id) {
						$library->finePaymentType = 13;
						$library->stripeSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->stripeSettingId == $this->id) {
						if ($library->finePaymentType == 13) {
							$library->finePaymentType = 0;
						}
						$library->stripeSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	private function createPaymentIntent($patron, $paymentAmount, $paymentMethodId) {
		$baseUrl = 'https://api.stripe.com';
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
	public function submitTransaction($patron, $payment, $paymentMethodId): mixed {
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
}