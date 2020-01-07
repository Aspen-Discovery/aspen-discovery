<?php


class GoogleApiSetting extends DataObject
{
	public $__table = 'google_api_settings';    // table name
	public $id;
	public $googleBooksKey;

	public static function getObjectStructure()
	{
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'googleBooksKey' => array('property' => 'googleBooksKey', 'type' => 'text', 'label' => 'Google Books Key', 'description' => 'The Google books API key to use'),
		);
		return $structure;
	}
}