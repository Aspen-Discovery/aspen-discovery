<?php /** @noinspection PhpMissingFieldTypeInspection */


class StripeSetting extends DataObject {
	public $__table = 'stripe_settings';
	public $id;
	public $name;
	/** @noinspection PhpUnused */
	public $forceDebugLog;
	public $stripePublicKey;
	public $stripeSecretKey;

	private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
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
			'forceDebugLog' => [
				'property' => 'forceDebugLog',
				'type' => 'checkbox',
				'label' => 'Force Debugging Logs',
				'description' => 'Whether or not to allow users to get debugging information about payments either if the user IP is authorized or not',
				'hideInLists' => false,
				'default' => false,
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
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

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void {
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

	private function createPaymentIntent($paymentAmount, $paymentMethodId, $payment, $transactionType) {
		$baseUrl = 'https://api.stripe.com';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$paymentIntentSetup = new CurlWrapper();
		$paymentIntentSetup->addCustomHeaders([
			'Authorization: Bearer ' . $this->stripeSecretKey,
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], false);

		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$currencyCode = 'USD';
		$currencySymbol = '$';
		if (!empty($systemVariables) && !empty($systemVariables->currencyCode)) {
			$currencyCode = $systemVariables->currencyCode;
			$currencySymbol = StringUtils::getCurrencySymbol();
		}
		$stripeCurrency = strtolower($currencyCode);

		$user = new User();
		$user->id = $payment->userId;
		$user->find(true);

		$amountDisplay = trim(($currencySymbol) . number_format($payment->totalPaid, 2) . ' ' . $currencyCode);
		$description = ucfirst($transactionType);
		if (!empty($payment->finesPaid)) {
			$finesArray = explode(',', $payment->finesPaid);
			$fineCount = count($finesArray);
			$description .= ' - ' . $fineCount . ' fine(s) - Total: ' . $amountDisplay;
		} else {
			$description .= ' - Total: ' . $amountDisplay;
		}

		$paymentLines = $payment->getPaymentLines();
		$metadata = [
			'Transaction Type' => ucfirst($transactionType),
		];
		if (!empty($payment->finesPaid)) {
			$finesArray = explode(',', $payment->finesPaid);
			$metadata['Fines Paid Count'] = (string)count($finesArray);
			$metadata['Fines Paid IDs'] = $payment->finesPaid;
		}
		if (!empty($user->unique_ils_id)) {
			$metadata['Patron ILS ID'] = $user->unique_ils_id;
		}
		if (!empty($user->ils_barcode)) {
			$metadata['Patron Barcode'] = $user->ils_barcode;
		}

		foreach ($paymentLines as $i => $paymentLine) {
			$metadata['Line ' . ($i + 1)] = $paymentLine->description . " - " . StringUtils::formatCurrency($paymentLine->amountPaid);
		}

		$postParams = [
			'amount' => $paymentAmount,
			'currency' => $stripeCurrency,
			'automatic_payment_methods' => [
				'enabled' => 'true',
				'allow_redirects' => 'never',
			],
			'payment_method' => $paymentMethodId,
			'description' => $description,
			'metadata' => $metadata,
		];

		$url = $baseUrl . '/v1/payment_intents';
		$paymentIntent = $paymentIntentSetup->curlPostPage($url, $postParams);
	
		ExternalRequestLogEntry::logRequest('fine_payment.createPaymentIntent', 'POST', $url, $paymentIntentSetup->getHeaders(), json_encode($postParams), $paymentIntentSetup->getResponseCode(), $paymentIntent, []);
		
		return json_decode($paymentIntent, true);
	}

	/*
	 * @return array
	 */
	public function submitTransaction($payment, $paymentMethodId, $transactionType): array {
		$result = ['success' => false];

		$paymentAmount = $payment->totalPaid;
		$paymentAmount = $paymentAmount * 100;
		$paymentAmount = (int)$paymentAmount;

		$paymentIntent = $this->createPaymentIntent($paymentAmount, $paymentMethodId, $payment, $transactionType);
		if (!empty($paymentIntent['error'])) {
			$payment->error = true;
			$payment->message .= $paymentIntent['error']['message'];
			$payment->update();
			return [
				'success' => false,
				'message' => $paymentIntent['error']['message'],
			];
		}else{
			$paymentIntentId = $paymentIntent['id'];

			$paymentRequest = new CurlWrapper();
			$url = 'https://api.stripe.com/v1/payment_intents/' . $paymentIntentId . '/confirm';

			$paymentRequest->addCustomHeaders([
				'Accept: application/json',
				'Authorization: Bearer ' . $this->stripeSecretKey,
				'Content-Type: application/x-www-form-urlencoded',
			], true);

			$paymentTransaction = $paymentRequest->curlPostBodyData($url, null);

			$forceDebugLog = $this->forceDebugLog;
			if (IPAddress::showDebuggingInformation() || $forceDebugLog){
				ExternalRequestLogEntry::logRequest('fine_payment.createPaymentIntent', 'POST', $url, $paymentRequest->getHeaders(),'', $paymentRequest->getResponseCode(), $paymentTransaction, []);
			}

			$paymentResponse = json_decode($paymentTransaction, true);
			if ($paymentRequest->getResponseCode() == 200) {
				{
					$totalPaid = $paymentResponse['amount_received'];
					$payment->transactionId = $paymentResponse['id'];
					$payment->orderId = $paymentResponse['id'];
					$payment->totalPaid = number_format($totalPaid / 100, 2, '.', '');

					// Extract receipt URL from the charge.
					// PaymentIntent has the latest_charge field that contains the receipt_url.
					if (!empty($paymentResponse['latest_charge'])) {
						$chargeId = $paymentResponse['latest_charge'];
						$baseUrl = 'https://api.stripe.com';
						$chargeUrl = $baseUrl . '/v1/charges/' . $chargeId;

						require_once ROOT_DIR . '/sys/CurlWrapper.php';
						$chargeRequest = new CurlWrapper();
						$chargeRequest->addCustomHeaders([
							'Authorization: Bearer ' . $this->stripeSecretKey,
							'Accept: application/json',
						], false);

						$chargeResponse = $chargeRequest->curlGetPage($chargeUrl);

						if ($chargeRequest->getResponseCode() == 200) {
							$chargeData = json_decode($chargeResponse, true);
							if (!empty($chargeData['receipt_url'])) {
								$payment->receiptUrl = $chargeData['receipt_url'];
							}
						}
					}

					if ($transactionType == 'donation'){
						$payment->message .= "Donation sent, TransactionId = $payment->transactionId, Net Amount = $payment->totalPaid. ";
						$payment->update();
						$result = [
							'success' => true,
							'message' => translate([
								'text' => 'Your donation has been sent. Thank you! ',
								'isPublicFacing' => true,
							]),
						];
						if (!empty($payment->receiptUrl)) {
							$result['receiptUrl'] = $payment->receiptUrl;
						}
						return $result;
					} else {
						$user = new User();
						$user->id = $payment->userId;
						if ($user->find(true)) {
							$finePaymentCompleted = $user->completeFinePayment($payment);
							if ($finePaymentCompleted['success']) {
								$payment->message .= "Payment completed, TransactionId = $payment->transactionId, Net Amount = $payment->totalPaid. ";
								$payment->update();
								$result = [
									'success' => true,
									'message' => translate([
										'text' => 'Your payment has been completed. ',
										'isPublicFacing' => true,
									]),
								];
								if (!empty($payment->receiptUrl)) {
									$result['receiptUrl'] = $payment->receiptUrl;
								}
								return $result;
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
				$message = $paymentResponse['error']['message']['default'] ?? $paymentResponse['error']['message'];
				$error = $paymentResponse['error']['status'] . ': ' . $message;
				$payment->error = 1;
				$payment->message = $error;
				$payment->update();
				$result['message'] = $error;
			}
		}

		return $result;
	}
}
