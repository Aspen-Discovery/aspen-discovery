<?php


class QuipuECardSetting extends DataObject
{
	public $__table = 'quipu_ecard_setting';
	public $id;
	public $server;
	public $clientId;

	public static function getObjectStructure() : array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'server' => array('property' => 'server', 'type' => 'text', 'label' => 'Server', 'description' => 'The Name of the Server where eCard is installed'),
			'clientId' => array('property' => 'clientId', 'type' => 'integer', 'label' => 'Client ID', 'description' => 'The numeric client id for your instance', 'hideInLists' => true),
		];
	}
}