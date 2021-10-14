<?php


class CoceServerSetting extends DataObject
{
	public $__table = 'coce_settings';    // table name
	public $id;
	public $coceServerUrl;

	public static function getObjectStructure() : array
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'coceServerUrl' => array('property' => 'coceServerUrl', 'type' => 'url', 'label' => 'Coce Server URL', 'description' => 'The URL of a Coce server', 'maxLength' => '100'),
		);
	}
}