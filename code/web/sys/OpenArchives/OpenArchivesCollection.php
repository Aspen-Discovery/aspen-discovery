<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/OpenArchives/LibraryOpenArchivesCollection.php';
require_once ROOT_DIR . '/sys/OpenArchives/LocationOpenArchivesCollection.php';

class OpenArchivesCollection extends DataObject
{
	public $__table = 'open_archives_collection';
	public $id;
	public $name;
	public $baseUrl;
	public $setName;
	public $subjects;
	public /** @noinspection PhpUnused */ $subjectFilters;
	public $imageRegex;
	public /** @noinspection PhpUnused */ $fetchFrequency;
	public /** @noinspection PhpUnused */ $loadOneMonthAtATime;
	public $lastFetched;

	public $_libraries;
	public $_locations;

	static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Open Archives'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Open Archives'));

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name to identify the open archives collection in the system', 'size' => '100'),
			'baseURL' => array('property' => 'baseUrl', 'type' => 'url', 'label' => 'Base URL', 'description' => 'The url of the open archives site', 'size' => '255'),
			'setName' => array('property' => 'setName', 'type' => 'text', 'label' => 'Set Name (separate multiple values with commas)', 'description' => 'The name of the set to harvest', 'size' => '100'),
			'subjects' => array('property' => 'subjects', 'type' => 'textarea', 'label' => 'Available Subjects', 'description' => 'Subjects that exist within the collection', 'readOnly' => true, 'hideInLists' => true),
			'subjectFilters' => array('property' => 'subjectFilters', 'type' => 'textarea', 'label' => 'Subject Filters (each filter on it\'s own line, regular expressions ok)', 'description' => 'Subjects to filter by', 'hideInLists' => true),
			'imageRegex' => array('property' => 'imageRegex', 'type' => 'regularExpression', 'label' => 'Image Regular Expression (to extract thumbnails, can be blank, use first capturing group for value)', 'description' => 'A regular expression to extract the thumbnail.', 'size' => '100'),
			'fetchFrequency' => array('property' => 'fetchFrequency', 'type' => 'enum', 'values' => ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'once' => 'Once'], 'label' => 'Frequency to Fetch', 'description' => 'How often the records should be fetched'),
			'loadOneMonthAtATime' => array('property' => 'loadOneMonthAtATime', 'type' => 'checkbox', 'label' => 'Fetch by Month', 'description' => 'Whether or not records should be fetched by month which increases performance on most servers'),
			'lastFetched' => array('property' => 'lastFetched', 'type' => 'timestamp', 'label' => 'Last Fetched (clear to force a new fetch)', 'description' => 'When the record was last fetched'),

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
		$this->lastFetched = 0;
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}

		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$this->clearLibraries();
		$this->clearLocations();
		return $this->update();
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$library = new LibraryOpenArchivesCollection();
			$library->collectionId = $this->id;
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
				$libraryOpenArchivesCollection = new LibraryOpenArchivesCollection();

				$libraryOpenArchivesCollection->collectionId = $this->id;
				$libraryOpenArchivesCollection->libraryId = $libraryId;
				$libraryOpenArchivesCollection->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryOpenArchivesCollection = new LibraryOpenArchivesCollection();
		$libraryOpenArchivesCollection->collectionId = $this->id;
		return $libraryOpenArchivesCollection->delete(true);
	}

	public function getLocations() {
		if (!isset($this->_locations) && $this->id){
			$this->_locations = array();
			$location = new LocationOpenArchivesCollection();
			$location->collectionId = $this->id;
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
				$locationOpenArchivesCollection = new LocationOpenArchivesCollection();

				$locationOpenArchivesCollection->collectionId = $this->id;
				$locationOpenArchivesCollection->locationId = $libraryId;
				$locationOpenArchivesCollection->insert();
			}
			unset($this->_locations);
		}
	}

	private function clearLocations()
	{
		//Delete links to the libraries
		$locationOpenArchivesCollection = new LocationOpenArchivesCollection();
		$locationOpenArchivesCollection->collectionId = $this->id;
		return $locationOpenArchivesCollection->delete(true);
	}
}