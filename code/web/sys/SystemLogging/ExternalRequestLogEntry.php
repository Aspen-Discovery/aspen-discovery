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
			if (IPAddress::showDebuggingInformation()) {
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

	private static function sanitize($field, $dataToSanitize) {
		$sanitizedField = $field;
		foreach ($dataToSanitize as $dataFieldName => $value) {
			$sanitizedField = str_replace($value, "**$dataFieldName**", $sanitizedField);
		}
		return $sanitizedField;
	}
}