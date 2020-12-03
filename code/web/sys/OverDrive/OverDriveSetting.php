<?php

class OverDriveSetting extends DataObject
{
	public $__table = 'overdrive_settings';    // table name
	public $id;
	public $url;
	public $patronApiUrl;
	public $clientSecret;
	public $clientKey;
	public $accountId;
	public $websiteId;
	public $productsKey;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	public static function getObjectStructure()
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'url' => array('property' => 'url', 'type' => 'url', 'label' => 'url', 'description' => 'The publicly accessible URL'),
			'patronApiUrl' => array('property' => 'patronApiUrl', 'type' => 'url', 'label' => 'Patron API URL', 'description' => 'The URL where the Patron API is located'),
			'clientSecret' => array('property' => 'clientSecret', 'type' => 'text', 'label' => 'Client Secret', 'description' => 'The client secret provided by OverDrive when registering'),
			'clientKey' => array('property' => 'clientKey', 'type' => 'text', 'label' => 'Client Key', 'description' => 'The client key provided by OverDrive when registering'),
			'accountId' => array('property' => 'accountId', 'type' => 'integer', 'label' => 'Account Id', 'description' => 'The account id for the main collection provided by OverDrive and used to load information about collections'),
			'websiteId' => array('property' => 'websiteId', 'type' => 'integer', 'label' => 'Website Id', 'description' => 'The website id provided by OverDrive and used to load circulation information'),
			'productsKey' => array('property' => 'productsKey', 'type' => 'text', 'label' => 'Products Key', 'description' => 'The products key provided by OverDrive used to load information about collections'),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
		);
	}
}