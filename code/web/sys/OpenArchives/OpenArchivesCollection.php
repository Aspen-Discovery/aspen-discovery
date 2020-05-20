<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class OpenArchivesCollection extends DataObject
{
    public $__table = 'open_archives_collection';
    public $id;
    public $name;
    public $baseUrl;
    public $setName;
    public $subjects;
    public $subjectFilters;
    public $fetchFrequency;
	public $loadOneMonthAtATime;
	public $lastFetched;

    static function getObjectStructure(){
        return [
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'A name to identify the open archives collection in the system', 'size'=>'100'),
            'baseURL' => array('property'=>'baseUrl', 'type'=>'url', 'label'=>'Base URL', 'description'=>'The url of the open archives site', 'size'=>'255'),
            'setName' => array('property'=>'setName', 'type'=>'text', 'label'=>'Set Name (separate multiple values with commas)', 'description'=>'The name of the set to harvest', 'size'=>'100'),
            'subjects' => array('property'=>'subjects', 'type'=>'textarea', 'label'=>'Available Subjects', 'description'=>'Subjects that exist within the collection', 'readOnly' => true, 'hideInLists' => true),
            'subjectFilters' => array('property'=>'subjectFilters', 'type'=>'textarea', 'label'=>'Subject Filters (each filter on it\'s own line, regular expressions ok)', 'description'=>'Subjects to filter by', 'hideInLists' => true),
            'fetchFrequency' => array('property'=>'fetchFrequency', 'type'=>'enum', 'values' => ['daily'=>'Daily', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'yearly'=>'Yearly', 'once'=>'Once'], 'label'=>'Frequency to Fetch', 'description'=>'How often the records should be fetched'),
	        'loadOneMonthAtATime' => array('property'=>'loadOneMonthAtATime', 'type'=>'checkbox', 'label'=>'Fetch by Month', 'description'=>'Whether or not records should be fetched by month which increases performance on most servers'),
            'lastFetched' => array('property'=>'lastFetched', 'type'=>'timestamp', 'label'=>'Last Fetched (clear to force a new fetch)', 'description'=>'When the record was last fetched'),
        ];
    }

    public function update()
    {
        $this->lastFetched = 0;
        return parent::update();
    }
}