<?php


class ExternalRequestLogEntry extends DataObject
{
	public  $__table = 'external_request_log';
	public $id;
	public $requestType;
	public $requestMethod;
	public $requestUrl;
	public $requestBody;
	public $requestHeaders;
	public $responseCode;
	public $response;
	public $requestTime;

	public static function getObjectStructure() : array {
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'requestType' => ['property'=>'requestType', 'type'=>'text', 'label'=>'Request Type', 'description'=>'The type from Aspen to make it easier to search requests', 'readOnly' => true],
			'requestMethod' => ['property'=>'requestMethod', 'type'=>'text', 'label'=>'Request Method', 'description'=>'The method used to submit', 'readOnly' => true],
			'requestUrl' => ['property'=>'requestUrl', 'type'=>'text', 'label'=>'Request URL', 'description'=>'The URL that was requested', 'readOnly' => true],
			'requestHeaders' => ['property'=>'requestHeaders', 'type'=>'textarea', 'label'=>'Request Headers', 'description'=>'Headers sent as part of the request', 'hideInLists' => true, 'readOnly' => true],
			'requestBody' => ['property'=>'requestBody', 'type'=>'textarea', 'label'=>'Request Body', 'description'=>'Body sent as part of the request', 'hideInLists' => true, 'readOnly' => true],
			'responseCode' => ['property'=>'responseCode', 'type'=>'integer', 'label'=>'Response Code', 'description'=>'The response Code for the response', 'readOnly' => true],
			'response' => ['property'=>'response', 'type'=>'textarea', 'label'=>'Response', 'description'=>'The response from the external server', 'hideInLists' => true, 'readOnly' => true],
			'requestTime' => ['property'=>'requestTime', 'type'=>'timestamp', 'label'=>'Request Time', 'description'=>'When the request was performed', 'readOnly' => true],
		];
	}

	static function logRequest($requestType, $method, $url, $headers, $body, $responseCode, $response, $dataToSanitize){
		try {
			if (IPAddress::showDebuggingInformation()) {
				require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
				$externalRequest = new ExternalRequestLogEntry();
				$externalRequest->requestType = $requestType;
				$externalRequest->requestMethod = $method;

				$externalRequest->requestUrl = ExternalRequestLogEntry::sanitize($url, $dataToSanitize);
				$externalRequest->requestHeaders = ExternalRequestLogEntry::sanitize(implode($headers, "\n"), $dataToSanitize);
				$externalRequest->requestBody = ExternalRequestLogEntry::sanitize($body, $dataToSanitize);
				$externalRequest->responseCode = $responseCode;
				$externalRequest->response = ExternalRequestLogEntry::sanitize($response, $dataToSanitize);;
				$externalRequest->requestTime = time();
				$externalRequest->insert();
			}
		}catch (Exception $e){
			//This happens before the table is created, we can ignore it safely.
		}
	}

	private static function sanitize($field, $dataToSanitize){
		$sanitizedField = $field;
		foreach ($dataToSanitize as $field => $value){
			$sanitizedField = str_replace($value, "**$field**" , $sanitizedField);
		}
		return $sanitizedField;
	}
}