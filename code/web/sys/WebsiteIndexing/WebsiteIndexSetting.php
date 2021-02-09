<?php
require_once ROOT_DIR . '/sys/WebsiteIndexing/LibraryWebsiteIndexing.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/LocationWebsiteIndexing.php';

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
	public $maxPagesToIndex;
	public $deleted;

	public $_libraries;
	public $_locations;

	public function getNumericColumnNames()
	{
		return ['lastIndexed', 'deleted'];
	}

	public static function getObjectStructure()
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Website Indexing Settings'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Website Indexing Settings'));

		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'name' => ['property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the website to index', 'maxLength'=>75, 'required' => true],
			'searchCategory' => ['property'=>'searchCategory', 'type'=>'text', 'label'=>'Search Category', 'description'=>'The category of the index.  All sites with the same category will be searched together', 'maxLength'=>75, 'required' => true, 'default' => 'Library Website'],
			'siteUrl' => ['property'=>'siteUrl', 'type'=>'url', 'label'=>'Site URL', 'description'=>'The URL to the Website', 'maxLength'=>255, 'required' => true],
			'pageTitleExpression' => ['property'=>'pageTitleExpression', 'type'=>'regularExpression', 'label'=>'Page Title Expression', 'description'=>'A regular expression to use to load the title from.  Will use the value of the first group identified.', 'maxLength'=>255, 'required' => false, 'default' => '', 'hideInLists' => true],
			'descriptionExpression' => ['property'=>'descriptionExpression', 'type'=>'regularExpression', 'label'=>'Description Expression', 'description'=>'A regular expression to use to load the description from.', 'maxLength'=>255, 'required' => false, 'default' => '', 'hideInLists' => true],
			'pathsToExclude' => ['property' => 'pathsToExclude', 'type'=>'textarea', 'label'=>'Paths to exclude', 'description'=>'A list of paths to exclude from the index with each on it\'s own line.', 'hideInLists' => true],
			'maxPagesToIndex' => ['property' => 'maxPagesToIndex', 'type'=>'integer', 'label'=>'Maximum Pages To Index', 'description'=>'A maximum number of pages to index.', 'default' => 2500],
			'indexFrequency' => ['property'=>'indexFrequency', 'type'=>'enum', 'values' => ['hourly'=>'Hourly', 'daily'=>'Daily', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'yearly'=>'Yearly', 'once'=>'Once'], 'label'=>'Frequency to Fetch', 'description'=>'How often the records should be fetched'],
			'lastIndexed' => ['property'=>'lastIndexed', 'type'=>'timestamp', 'label'=>'Last Fetched (clear to force a new fetch)', 'description'=>'When the record was last fetched'],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that can view this website',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that can view this website',
				'values' => $locationList,
			],
		];
	}

	public function __get($name)
	{
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			$this->_locations = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		if (substr($this->siteUrl, -1) == '/'){
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}

		return $ret;
	}

	public function insert()
	{
		if (substr($this->siteUrl, -1) == '/'){
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$this->deleted = 1;
		$this->clearLibraries();
		$this->clearLocations();
		return $this->update();
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$library = new LibraryWebsiteIndexing();
			$library->settingId = $this->id;
			$library->find();
			while($library->fetch()){
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryWebsiteIndexing = new LibraryWebsiteIndexing();

				$libraryWebsiteIndexing->settingId = $this->id;
				$libraryWebsiteIndexing->libraryId = $libraryId;
				$libraryWebsiteIndexing->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryWebsiteIndexing = new LibraryWebsiteIndexing();
		$libraryWebsiteIndexing->settingId = $this->id;
		return $libraryWebsiteIndexing->delete(true);
	}

	public function getLocations() {
		if (!isset($this->_locations) && $this->id){
			$this->_locations = array();
			$location = new LocationWebsiteIndexing();
			$location->settingId = $this->id;
			$location->find();
			while($location->fetch()){
				$this->_locations[$location->locationId] = $location->locationId;
			}
		}
		return $this->_locations;
	}

	public function saveLocations(){
		if (isset($this->_locations) && is_array($this->_locations)){
			$this->clearLocations();

			foreach ($this->_locations as $libraryId) {
				$locationWebsiteIndexing = new LocationWebsiteIndexing();

				$locationWebsiteIndexing->settingId = $this->id;
				$locationWebsiteIndexing->locationId = $libraryId;
				$locationWebsiteIndexing->insert();
			}
			unset($this->_locations);
		}
	}

	private function clearLocations()
	{
		//Delete links to the libraries
		$locationWebsiteIndexing = new LocationWebsiteIndexing();
		$locationWebsiteIndexing->settingId = $this->id;
		return $locationWebsiteIndexing->delete(true);
	}
}