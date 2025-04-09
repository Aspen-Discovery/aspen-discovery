<?php


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

	public static function getObjectStructure($context = ''): array {
		return [
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
	static function logRequest(string $requestType, string $method, string $url, $headers, string $body, string $responseCode, ?string $response, array $dataToSanitize) {
		try {
			if (IPAddress::showDebuggingInformation() || (self::getForceDebuggingLogStatus() && str_starts_with($requestType,"myaccount_ajax"))) {
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
	 * Get the status of the toggle 'Force Debugging Log' for a set ecommerce application.
	 * 
	 * @return  bool     True if 'Force Debugging Log' is enabled for that ecommerce or False if not.
	 * @access  private
	 */
	private static function getForceDebuggingLogStatus(){
		
		global $library;
		$finePaymentType = $library->finePaymentType;
		$settings = null;
		$status = false;

		switch($finePaymentType){
			case 2:
				require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
				$settings = new PayPalSetting();
				$settings->id = $library->payPalSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 5:
				require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';
				$settings = new ProPaySetting();
				$settings->id = $library->proPaySettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 8:
				require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';
				$settings = new ACISpeedpaySetting();
				$settings->id = $library->aciSpeedpaySettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 9:
				require_once ROOT_DIR . '/sys/ECommerce/InvoiceCloudSetting.php';
				$settings = new InvoiceCloudSetting();
				$settings->id = $library->invoiceCloudSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				};
				break;
			case 11:
				require_once ROOT_DIR . '/sys/ECommerce/PayPalPayflowSetting.php';
				$settings = new PayPalPayflowSetting();
				$settings->id = $library->paypalPayflowSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 12:
				require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';
				$settings = new SquareSetting();
				$settings->id = $library->squareSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 13:
				require_once ROOT_DIR . '/sys/ECommerce/StripeSetting.php';
				$settings = new StripeSetting();
				$settings->id = $library->stripeSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
			case 14:
				require_once ROOT_DIR . '/sys/ECommerce/NCRPaymentsSetting';
				$settings = new NCRPaymentsSetting();
				$settings->id = $library->ncrSettingId;
				if($settings->find(true)){
					$status = $settings->forceDebugLog;
				}
				break;
		}
		return $status;
	}

	private static function sanitize($field, $dataToSanitize) {
		$sanitizedField = $field;
		foreach ($dataToSanitize as $dataFieldName => $value) {
			$sanitizedField = str_replace($value, "**$dataFieldName**", $sanitizedField);
		}
		return $sanitizedField;
	}
}