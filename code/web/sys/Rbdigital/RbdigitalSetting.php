<?php

class RbdigitalSetting extends DataObject
{
    public $__table = 'rbdigital_settings';    // table name
    public $id;
    public $apiUrl;
    public $userInterfaceUrl;
    public $apiToken;
    public $libraryId;
    public $runFullUpdate;
    public $lastUpdateOfChangedRecords;
    public $lastUpdateOfAllRecords;

    public static function getObjectStructure()
    {
        $structure = array(
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'apiUrl' => array('property'=>'apiUrl', 'type'=>'url', 'label'=>'url', 'description'=>'The URL to the API'),
            'userInterfaceUrl' => array('property'=>'userInterfaceUrl', 'type'=>'url', 'label'=>'User Interface URL', 'description'=>'The URL where the Patron can access the catalog'),
            'apiToken' => array('property'=>'apiToken', 'type'=>'text', 'label'=>'API Token', 'description'=>'The API Token provided by Rbdigital when registering'),
            'libraryId' => array('property' => 'libraryId', 'type' => 'integer', 'label' => 'Library Id', 'description'=>'The library id provided by Rbdigital'),
            'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description'=>'Whether or not a full update of all records should be done on the next pass of indexing', 'default'=>0),
            'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'integer', 'label' => 'Last Update of Changed Records', 'description'=>'The timestamp when just changes were loaded', 'default'=>0),
            'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'integer', 'label' => 'Last Update of All Records', 'description'=>'The timestamp when just changes were loaded', 'default'=>0),
        );
        return $structure;
    }
}