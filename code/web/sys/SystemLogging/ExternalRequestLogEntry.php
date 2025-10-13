<?php /** @noinspection PhpMissingFieldTypeInspection */


class ExternalRequestLogEntry extends DataObject {
	public $__table = 'external_request_log';
	public $id;
	public $requestType;
	public $requestMethod;
	public $requestUrl;
	public $requestBody;
	public $requestHeaders;
	public $responseCode;
	public $response;
	public $requestTime;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'requestType' => [
				'property' => 'requestType',
				'type' => 'text',
				'label' => 'Request Type',
				'description' => 'The type from Aspen to make it easier to search requests',
				'readOnly' => true,
			],
			'requestMethod' => [
				'property' => 'requestMethod',
				'type' => 'text',
				'label' => 'Request Method',
				'description' => 'The method used to submit',
				'readOnly' => true,
			],
			'requestUrl' => [
				'property' => 'requestUrl',
				'type' => 'text',
				'label' => 'Request URL',
				'description' => 'The URL that was requested',
				'readOnly' => true,
			],
			'requestHeaders' => [
				'property' => 'requestHeaders',
				'type' => 'textarea',
				'label' => 'Request Headers',
				'description' => 'Headers sent as part of the request',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'requestBody' => [
				'property' => 'requestBody',
				'type' => 'textarea',
				'label' => 'Request Body',
				'description' => 'Body sent as part of the request',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'responseCode' => [
				'property' => 'responseCode',
				'type' => 'integer',
				'label' => 'Response Code',
				'description' => 'The response Code for the response',
				'readOnly' => true,
			],
			'response' => [
				'property' => 'response',
				'type' => 'textarea',
				'label' => 'Response',
				'description' => 'The response from the external server',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'requestTime' => [
				'property' => 'requestTime',
				'type' => 'timestamp',
				'label' => 'Request Time',
				'description' => 'When the request was performed',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @param string $requestType
	 * @param string $method
	 * @param string $url
	 * @param null|string|string[] $headers
	 * @param string $body
	 * @param string $responseCode
	 * @param string|null $response
	 * @param string[] $dataToSanitize
	 */
	static function logRequest(string $requestType, string $method, string $url, mixed $headers, string $body, string $responseCode, ?string $response, array $dataToSanitize) : void {
		try {
			if (IPAddress::showDebuggingInformation() || self::getForceDebuggingLogStatus($requestType)) {
				require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
				$externalRequest = new ExternalRequestLogEntry();
				$externalRequest->requestType = $requestType;
				$externalRequest->requestMethod = $method;

				require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
				$externalRequest->requestUrl = StringUtils::truncate(ExternalRequestLogEntry::sanitize($url, $dataToSanitize), 400);
				if (is_null($headers)) {
					$headers = '';
				} elseif (is_array($headers)) {
					$headers = implode("\n", $headers);
				}
				$externalRequest->requestHeaders = ExternalRequestLogEntry::sanitize($headers, $dataToSanitize);
				$externalRequest->requestBody = ExternalRequestLogEntry::sanitize($body, $dataToSanitize);
				$externalRequest->responseCode = $responseCode;
				if (is_null($response)) {
					$response = '';
				}
				$externalRequest->response = ExternalRequestLogEntry::sanitize($response, $dataToSanitize);
				$externalRequest->requestTime = time();
				$externalRequest->insert();
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error logging request " . $e->getMessage(), Logger::LOG_ERROR);
		}
	}

	/**
	 * Get the status of the toggle 'Force Debugging Log' for an object related with a request type.
	 * Example : getForceDebuggingLogStatus should return the status for a specific vendor as it is related with the request type "fine_payment".
	 *
	 *
	 * @return  bool     True if 'Force Debugging Log' is enabled for that object or False if not.
	 * @access  private
	 */
	private static function getForceDebuggingLogStatus(string $requestType) : bool {

		$status = false;

		if(str_starts_with($requestType,"fine_payment")){
			$status = self::getForcePaymentDebugLogging();
		}

		return $status;
	}

	/**
	 * Get the status of the toggle 'Force Debugging Log' for a set ecommerce application.
	 * 
	 * @return  bool     True if 'Force Debugging Log' is enabled for that ecommerce or False if not.
	 * @access  private
	 */
	private static function getForcePaymentDebugLogging() : bool {

		//Array of [finePaymentType, vendorId, vendorClass]
		$eCommerceOptions = [
			[2,'payPalSettingId','PayPalSetting'],
			[5,'proPaySettingId','ProPaySetting'],
			[7,'worldPaySettingId','WorldPaySetting'],
			[8,'aciSpeedpaySettingId','ACISpeedpaySetting'],
			[9,'invoiceCloudSettingId','InvoiceCloudSetting'],
			[11,'paypalPayflowSettingId','PayPalPayflowSetting'],
			[12,'squareSettingId','SquareSetting'],
			[13,'stripeSettingId','StripeSetting'],
			[14,'ncrSettingId','NCRPaymentsSetting']
		];

		global $library;
		$settings = null;
		$status = false;

		//Look for the current vendor of the library and get
		// the status of the 'forceDebugLog' attribute
		foreach($eCommerceOptions as $eCommerceOption){

			[$finePaymentType,$vendorId,$vendorClass] = $eCommerceOption;

			if ($finePaymentType == $library->finePaymentType){
				require_once ROOT_DIR . "/sys/ECommerce/$vendorClass.php";
				$settings = new $vendorClass();
				$settings->id = $library->$vendorId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog ?? false;
				}
				break;
			}
		}
		return $status;
	}

	private static function sanitize($field, $dataToSanitize) : string {
		$sanitizedField = $field;
		foreach ($dataToSanitize as $dataFieldName => $value) {
			$sanitizedField = str_replace($value, "**$dataFieldName**", $sanitizedField);
		}
		return $sanitizedField;
	}
}