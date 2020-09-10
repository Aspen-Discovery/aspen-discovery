<?php

class Axis360Setting extends DataObject
{
	public $__table = 'axis360_settings';    // table name
	public $id;
	public $apiUrl;
	public $userInterfaceUrl;
	public $vendorUsername;
	public $vendorPassword;
	public $libraryPrefix;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	public static function getObjectStructure()
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiUrl' => array('property' => 'apiUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL to the API'),
			'userInterfaceUrl' => array('property' => 'userInterfaceUrl', 'type' => 'url', 'label' => 'User Interface URL', 'description' => 'The URL where the Patron can access the catalog'),
			'vendorUsername' => array('property' => 'vendorUsername', 'type' => 'text', 'label' => 'Vendor Username', 'description' => 'The Vendor Username provided by Axis360 when registering'),
			'vendorPassword' => array('property' => 'vendorPassword', 'type' => 'storedPassword', 'label' => 'Vendor Password', 'description' => 'The Vendor Password provided by Axis360 when registering', 'hideInLists' => true),
			'libraryPrefix' => array('property' => 'libraryPrefix', 'type' => 'text', 'label' => 'Library Prefix', 'description' => 'The Library Prefix to use with the API'),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
		);
	}
}