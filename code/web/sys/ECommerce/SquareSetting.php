<?php /** @noinspection PhpMissingFieldTypeInspection */


class SquareSetting extends DataObject {
	public $__table = 'square_settings';
	public $id;
	public $name;
	public $sandboxMode;
	/** @noinspection PhpUnused */
	public $forceDebugLog;
	public $applicationId;
	public $accessToken;
	public $locationId;

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
			'sandboxMode' => [
				'property' => 'sandboxMode',
				'type' => 'checkbox',
				'label' => 'Use Square Sandbox (for testing payments only, does not collect money)',
				'description' => 'Whether or not to use Square in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'forceDebugLog' => [
				'property' => 'forceDebugLog',
				'type' => 'checkbox',
				'label' => 'Force Debugging Logs',
				'description' => 'Whether or not to allow users to get debugging information about payments either if the user IP is authorized or not',
				'hideInLists' => false,
				'default' => false,
			],
			'applicationId' => [
				'property' => 'applicationId',
				'type' => 'text',
				'label' => 'Application ID',
				'description' => 'The Application ID to use when paying fines with Square.',
				'hideInLists' => false,
				'default' => '',
				'size' => 80,
			],
			'accessToken' => [
				'property' => 'accessToken',
				'type' => 'storedPassword',
				'label' => 'Access Token',
				'description' => 'The Access Token to use when paying fines with Square.',
				'hideInLists' => true,
				'default' => '',
				'size' => 80,
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'text',
				'label' => 'Location ID',
				'description' => 'The Location ID to use when paying fines with Square.',
				'hideInLists' => false,
				'default' => '',
				'size' => 80,
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
				$obj->squareSettingId = $this->id;
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
					if ($library->squareSettingId != $this->id) {
						$library->finePaymentType = 12;
						$library->squareSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->squareSettingId == $this->id) {
						if ($library->finePaymentType == 12) {
							$library->finePaymentType = 0;
						}
						$library->squareSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function submitTransaction(UserPayment $payment, string $paymentToken, string $squareOrderId, array $amountMoney): array {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';

		$paymentRequest = new CurlWrapper();

		$paymentRequest->addCustomHeaders([
			'Content-Type: application/json',
			'Square-Version: 2023-06-08',
			"Authorization: Bearer $this->accessToken",
		], true);

		$baseUrl = 'https://connect.squareup.com';

		if ($this->sandboxMode == 1) {
			$baseUrl = 'https://connect.squareupsandbox.com';
		}

		$body = [
			'idempotency_key' => strval($payment->id),
			'amount_money' => $amountMoney,
			'source_id' => $paymentToken,
			'order_id' => $squareOrderId,
			'location_id' => strval($this->locationId),
		];

		$paymentUrl = $baseUrl . '/v2/payments';

		$paymentRequestResults = $paymentRequest->curlPostBodyData($paymentUrl, $body);

		ExternalRequestLogEntry::logRequest(
			'fine_payment.completeSquareOrder',
			'POST',
			$paymentUrl,
			$paymentRequest->getHeaders(),
			json_encode($body),
			$paymentRequest->getResponseCode(),
			$paymentRequestResults,
			[]
		);

		$decodedPaymentRequestResults = json_decode($paymentRequestResults);

		if (!isset($decodedPaymentRequestResults->payment)) {
			$error = $decodedPaymentRequestResults->errors[0] ?? null;

			$errorMessage =
				$error->detail ??
				$error->code ??
				'Unknown Square payment error';

			return [
				'success' => false,
				'message' => $errorMessage,
			];
		}

		return [
			'success' => true,
			'payment' => $decodedPaymentRequestResults->payment,
		];
	}

	public function createLineItems(UserPayment $payment): array {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		require_once ROOT_DIR . '/sys/Account/UserPaymentLine.php';

		$paymentRequest = new CurlWrapper();

		$paymentRequest->addCustomHeaders([
			'Content-Type: application/json',
			'Square-Version: 2023-06-08',
			"Authorization: Bearer $this->accessToken",
		], true);

		$baseUrl = 'https://connect.squareup.com';

		if ($this->sandboxMode == 1) {
			$baseUrl = 'https://connect.squareupsandbox.com';
		}

		$userPaymentLine = new UserPaymentLine();
		$userPaymentLine->paymentId = $payment->id;

		$lineItems = [];

		if ($userPaymentLine->find()) {
			while ($userPaymentLine->fetch()) {
				$lineItems[] = [
					'name' => $userPaymentLine->description,
					'quantity' => '1',
					'base_price_money' => [
						'amount' => (int)round($userPaymentLine->amountPaid * 100),
						'currency' => 'USD'
					]
				];
			}
		}

		if (empty($lineItems)) {
			return [
				'success' => false,
				'message' => 'No payment line items were found.',
			];
		}

		$orderUrl = $baseUrl . '/v2/orders';

		$orderBody = [
			'idempotency_key' => strval($payment->id) . '-order',
			'order' => [
				'location_id' => strval($this->locationId),
				'line_items' => $lineItems
			]
		];

		$orderResponse = $paymentRequest->curlPostBodyData($orderUrl, $orderBody);

		ExternalRequestLogEntry::logRequest(
			'fine_payment.createSquareOrder',
			'POST',
			$orderUrl,
			$paymentRequest->getHeaders(),
			json_encode($orderBody),
			$paymentRequest->getResponseCode(),
			$orderResponse,
			[]
		);

		$decodedOrder = json_decode($orderResponse);

		$squareOrderId = $decodedOrder->order->id ?? null;

		if ($squareOrderId === null) {
			$error = $decodedOrder->errors[0] ?? null;

			$errorMessage =
				$error->detail ??
				$error->code ??
				'Unknown Square order error';

			return [
				'success' => false,
				'message' => $errorMessage,
			];
		}

		return [
			'success' => true,
			'orderId' => $squareOrderId,
			'amountMoney' => [
				'amount' => $decodedOrder->order->total_money->amount,
				'currency' => $decodedOrder->order->total_money->currency
			],
		];
	}
}