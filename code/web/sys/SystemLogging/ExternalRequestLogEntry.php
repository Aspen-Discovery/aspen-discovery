<?php


class ExternalRequestLogEntry extends DataObject
{
	public  $__table = 'external_request_log';
	public $id;
	public $requestUrl;
	public $requestBody;
	public $requestHeaders;
	public $responseCode;
	public $response;
	public $requestTime;

	public static function getObjectStructure() : array {
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'requestUrl' => ['property'=>'requestUrl', 'type'=>'text', 'label'=>'Request URL', 'description'=>'The URL that was requested'],
			'requestHeaders' => ['property'=>'requestHeaders', 'type'=>'textarea', 'label'=>'Request Headers', 'description'=>'Headers sent as part of the request', 'hideInLists' => true],
			'requestBody' => ['property'=>'requestBody', 'type'=>'textarea', 'label'=>'Request Body', 'description'=>'Body sent as part of the request', 'hideInLists' => true],
			'responseCode' => ['property'=>'responseCode', 'type'=>'integer', 'label'=>'Response Code', 'description'=>'The response Code for the response'],
			'response' => ['property'=>'response', 'type'=>'textarea', 'label'=>'Response', 'description'=>'The response from the external server', 'hideInLists' => true],
			'requestTime' => ['property'=>'requestTime', 'type'=>'timestamp', 'label'=>'Request Time', 'description'=>'When the request was performed'],
		];
	}
}