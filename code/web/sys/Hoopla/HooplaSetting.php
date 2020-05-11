<?php

class HooplaSetting extends DataObject
{
	public $__table = 'hoopla_settings';    // table name
	public $id;
	public $apiUrl;
	public $libraryId;
	public $apiUsername;
	public $apiPassword;
	public /** @noinspection PhpUnused */ $apiToken;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	public /** @noinspection PhpUnused */ $excludeTitlesWithCopiesFromOtherVendors;

	public static function getObjectStructure()
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiUrl' => array('property' => 'apiUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL to the API'),
			'libraryId' => array('property' => 'libraryId', 'type' => 'integer', 'label' => 'Library Id', 'description' => 'The Library Id to use with the API'),
			'excludeTitlesWithCopiesFromOtherVendors' => array('property' => 'excludeTitlesWithCopiesFromOtherVendors', 'type' => 'checkbox', 'label' => 'Exclude Records With Copies from other eContent Vendors (OverDrive and RBdigital)', 'description' => 'Whether or not records in other collections should be included', 'default' => 0, 'forcesReindex' => true),
			'apiUsername' => array('property' => 'apiUsername', 'type' => 'text', 'label' => 'API Username', 'description' => 'The API Username provided by Hoopla when registering'),
			'apiPassword' => array('property' => 'apiPassword', 'type' => 'storedPassword', 'label' => 'API Password', 'description' => 'The API Password provided by Hoopla when registering', 'hideInLists' => true),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
		);
	}
}