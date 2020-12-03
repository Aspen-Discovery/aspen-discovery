<?php


class WebsiteIndexSetting extends DataObject
{
	public $__table = 'website_indexing_settings';    // table name
	public $id;
	public $name;
	public $searchCategory;
	public $siteUrl;
	public /** @noinspection PhpUnused */$pageTitleExpression;
	public /** @noinspection PhpUnused */$descriptionExpression;
	public /** @noinspection PhpUnused */$pathsToExclude;
	public /** @noinspection PhpUnused */$indexFrequency;
	public /** @noinspection PhpUnused */$lastIndexed;

	public static function getObjectStructure()
	{
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'name' => ['property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the website to index', 'maxLength'=>75, 'required' => true],
			'searchCategory' => ['property'=>'searchCategory', 'type'=>'text', 'label'=>'Search Category', 'description'=>'The category of the index.  All sites with the same category will be searched together', 'maxLength'=>75, 'required' => true, 'default' => 'Library Website'],
			'siteUrl' => ['property'=>'siteUrl', 'type'=>'url', 'label'=>'Site URL', 'description'=>'The URL to the Website', 'maxLength'=>255, 'required' => true],
			'pageTitleExpression' => ['property'=>'pageTitleExpression', 'type'=>'regularExpression', 'label'=>'Page Title Expression', 'description'=>'A regular expression to use to load the title from.  Will use the value of the first group identified.', 'maxLength'=>255, 'required' => false, 'default' => ''],
			'descriptionExpression' => ['property'=>'descriptionExpression', 'type'=>'regularExpression', 'label'=>'Description Expression', 'description'=>'A regular expression to use to load the description from.', 'maxLength'=>255, 'required' => false, 'default' => ''],
			'pathsToExclude' => ['property' => 'pathsToExclude', 'type'=>'textarea', 'label'=>'Paths to exclude', 'description'=>'A list of paths to exclude from the index with each on it\'s own line.', 'hideInLists' => true],
			'indexFrequency' => ['property'=>'indexFrequency', 'type'=>'enum', 'values' => ['hourly'=>'Hourly', 'daily'=>'Daily', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'yearly'=>'Yearly', 'once'=>'Once'], 'label'=>'Frequency to Fetch', 'description'=>'How often the records should be fetched'],
			'lastIndexed' => ['property'=>'lastIndexed', 'type'=>'integer', 'label'=>'Last Fetched (clear to force a new fetch)', 'description'=>'When the record was last fetched'],
		];
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