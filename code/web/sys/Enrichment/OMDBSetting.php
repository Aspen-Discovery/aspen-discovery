<?php


class OMDBSetting extends DataObject
{
	public $__table = 'omdb_settings';    // table name
	public $id;
	public $apiKey;
	public $fetchCoversWithoutDates;

	public static function getObjectStructure() : array
	{
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiKey' => array('property' => 'apiKey', 'type' => 'storedPassword', 'label' => 'API Key', 'description' => 'The Key for the API', 'maxLength' => '10', 'hideInLists' => true),
		);
		return $structure;
	}
}