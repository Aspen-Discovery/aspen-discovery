<?php


class GreenhouseSettings extends DataObject
{
	public $__table = 'greenhouse_settings';
	public $id;
	public $greenhouseAlertSlackHook;
	public $apiKey1;
	public $apiKey2;
	public $apiKey3;
	public $apiKey4;
	public $apiKey5;

	public static function getObjectStructure() : array {
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'greenhouseAlertSlackHook' => ['property'=>'greenhouseAlertSlackHook', 'type'=>'url', 'label'=>'Alert Slack Hook', 'description'=>'A slack hook to send alerts to', 'maxLength'=>255, 'required' => false],
			'apiKey1' => ['property' => 'apiKey1', 'type' => 'storedPassword', 'label' => 'API Key 1', 'description' => 'API key for authenticating LiDA access', 'canBatchUpdate'=> false, 'hideInLists'=> true],
			'apiKey2' => ['property' => 'apiKey2', 'type' => 'storedPassword', 'label' => 'API Key 2', 'description' => 'API key for authenticating LiDA access', 'canBatchUpdate'=> false, 'hideInLists'=> true],
			'apiKey3' => ['property' => 'apiKey3', 'type' => 'storedPassword', 'label' => 'API Key 3', 'description' => 'API key for authenticating LiDA access', 'canBatchUpdate'=> false, 'hideInLists'=> true],
			'apiKey4' => ['property' => 'apiKey4', 'type' => 'storedPassword', 'label' => 'API Key 4', 'description' => 'API key for authenticating LiDA access', 'canBatchUpdate'=> false, 'hideInLists'=> true],
			'apiKey5' => ['property' => 'apiKey5', 'type' => 'storedPassword', 'label' => 'API Key 5', 'description' => 'API key for authenticating LiDA access', 'canBatchUpdate'=> false, 'hideInLists'=> true],
		];
	}
}