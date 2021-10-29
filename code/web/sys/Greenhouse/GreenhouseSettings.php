<?php


class GreenhouseSettings extends DataObject
{
	public $__table = 'greenhouse_settings';
	public $id;
	public $greenhouseAlertSlackHook;

	public static function getObjectStructure() : array {
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'greenhouseAlertSlackHook' => ['property'=>'greenhouseAlertSlackHook', 'type'=>'url', 'label'=>'Alert Slack Hook', 'description'=>'A slack hook to send alerts to', 'maxLength'=>255, 'required' => false],
		];
	}
}