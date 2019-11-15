<?php


class WebsiteIndexSetting extends DataObject
{
	public $__table = 'website_indexing_settings';    // table name
	public $id;
	public $name;
	public $searchCategory;
	public $siteUrl;
	public $indexFrequency;
	public $lastIndexed;

	public static function getObjectStructure()
	{
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the website to index'),
			'searchCategory' => array('property'=>'searchCategory', 'type'=>'text', 'label'=>'Search Category', 'description'=>'The category of the index.  All sites with the same category will be searched together'),
			'siteUrl' => array('property'=>'siteUrl', 'type'=>'url', 'label'=>'Site URL', 'description'=>'The URL to the Website'),
			'indexFrequency' => array('property'=>'indexFrequency', 'type'=>'enum', 'values' => ['hourly'=>'Hourly', 'daily'=>'Daily', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'yearly'=>'Yearly', 'once'=>'Once'], 'label'=>'Frequency to Fetch', 'description'=>'How often the records should be fetched'),
			'lastIndexed' => array('property'=>'lastIndexed', 'type'=>'integer', 'label'=>'Last Fetched (clear to force a new fetch)', 'description'=>'When the record was last fetched'),
		);
		return $structure;
	}
}