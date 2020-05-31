<?php


class WebsiteIndexSetting extends DataObject
{
	public $__table = 'website_indexing_settings';    // table name
	public $id;
	public $name;
	public $searchCategory;
	public $siteUrl;
	public $pathsToExclude;
	public $indexFrequency;
	public $lastIndexed;

	public static function getObjectStructure()
	{
		return array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the website to index', 'required' => true),
			'searchCategory' => array('property'=>'searchCategory', 'type'=>'text', 'label'=>'Search Category', 'description'=>'The category of the index.  All sites with the same category will be searched together', 'required' => true, 'default' => 'Library Website'),
			'siteUrl' => array('property'=>'siteUrl', 'type'=>'url', 'label'=>'Site URL', 'description'=>'The URL to the Website', 'required' => true),
			'pathsToExclude' => array('property' => 'pathsToExclude', 'type'=>'textarea', 'label'=>'Paths to exclude', 'description'=>'A list of paths to exclude from the index with each on it\'s own line.', 'hideInLists' => true),
			'indexFrequency' => array('property'=>'indexFrequency', 'type'=>'enum', 'values' => ['hourly'=>'Hourly', 'daily'=>'Daily', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'yearly'=>'Yearly', 'once'=>'Once'], 'label'=>'Frequency to Fetch', 'description'=>'How often the records should be fetched'),
			'lastIndexed' => array('property'=>'lastIndexed', 'type'=>'integer', 'label'=>'Last Fetched (clear to force a new fetch)', 'description'=>'When the record was last fetched'),
		);
	}

	public function update()
	{
		if (substr($this->siteUrl, -1) == '/'){
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		return parent::update();
	}

	public function insert()
	{
		if (substr($this->siteUrl, -1) == '/'){
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		return parent::insert();
	}
}