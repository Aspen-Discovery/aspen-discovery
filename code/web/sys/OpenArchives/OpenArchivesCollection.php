<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/OpenArchives/LibraryOpenArchivesCollection.php';
require_once ROOT_DIR . '/sys/OpenArchives/LocationOpenArchivesCollection.php';

class OpenArchivesCollection extends DataObject {
	public $__table = 'open_archives_collection';
	public $id;
	public $name;
	public $baseUrl;
	public $setName;
	public $indexAllSets;
	public $subjects;
	public $defaultCover;
	public /** @noinspection PhpUnused */
		$subjectFilters;
	public $imageRegex;
	public $metadataFormat;
	public $dateFormatting;
	public /** @noinspection PhpUnused */
		$fetchFrequency;
	public /** @noinspection PhpUnused */
		$loadOneMonthAtATime;
	public $lastFetched;
	public $deleted;

	public $_libraries;
	public $_locations;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Open Archives'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Open Archives'));

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name to identify the open archives collection in the system',
				'size' => '100',
			],
			'baseURL' => [
				'property' => 'baseUrl',
				'type' => 'url',
				'label' => 'Base URL',
				'description' => 'The url of the open archives site',
				'size' => '255',
			],
			'defaultCover' => [
				'property' => 'defaultCover',
				'type' => 'image',
				'label' => 'Background Image for Default Covers (280x280)',
				'description' => 'A background image for default covers (.jpg or .png only)',
				'required' => false,
				'maxWidth' => 280,
				'maxHeight' => 280,
				'hideInLists' => true,
			],
			'setName' => [
				'property' => 'setName',
				'type' => 'text',
				'label' => 'Set(s) To Index (separate multiple values with commas)',
				'description' => 'The name of the set(s) to harvest',
				'size' => '100',
			],
			'indexAllSets' => [
				'property' => 'indexAllSets',
				'type' => 'checkbox',
				'label' => 'Index all Sets',
				'description' => 'Index all Sets. This should be set or the list of sets to index should be provided.',
				'default' => false,
			],
			'metadataFormat' => [
				'property' => 'metadataFormat',
				'type' => 'enum',
				'values' => [
					'oai_dc' => 'Dublin Core (works for most archives)',
					'mods' => 'MODS',
				],
				'label' => 'Metadata Format',
				'description' => 'The format of the metadata provided by the OAI collection',
			],
			'dateFormatting' => [
				'property' => 'dateFormatting',
				'type' => 'enum',
				'values' => [
					0 => 'Do no date formatting',
					1 => 'Convert to Date Format',
				],
				'label' => 'Date Formatting',
				'description' => 'Either take date exactly how metadata is formatted in the collection or attempt to change to a date format (this is how it worked before)',
			],
			'subjects' => [
				'property' => 'subjects',
				'type' => 'textarea',
				'label' => 'Available Subjects',
				'description' => 'Subjects that exist within the collection',
				'readOnly' => true,
				'hideInLists' => true,
			],
			'subjectFilters' => [
				'property' => 'subjectFilters',
				'type' => 'textarea',
				'label' => 'Subject Filters (each filter on it\'s own line, regular expressions ok)',
				'description' => 'Subjects to filter by',
				'hideInLists' => true,
			],
			'imageRegex' => [
				'property' => 'imageRegex',
				'type' => 'multilineRegularExpression',
				'label' => 'Image Regular Expression',
				'description' => 'A regular expression to extract the thumbnail.',
				'note' => 'Used to extract thumbnails, can be blank, the first capturing group is used for the value, can put multiple expressions on their own line',
			],
			'fetchFrequency' => [
				'property' => 'fetchFrequency',
				'type' => 'enum',
				'values' => [
					'daily' => 'Daily',
					'weekly' => 'Weekly',
					'monthly' => 'Monthly',
					'yearly' => 'Yearly',
					'once' => 'Once',
				],
				'label' => 'Frequency to Fetch',
				'description' => 'How often the records should be fetched',
			],
			'loadOneMonthAtATime' => [
				'property' => 'loadOneMonthAtATime',
				'type' => 'checkbox',
				'label' => 'Fetch by Month',
				'description' => 'Whether or not records should be fetched by month which increases performance on most servers',
			],
			'lastFetched' => [
				'property' => 'lastFetched',
				'type' => 'timestamp',
				'label' => 'Last Fetched (clear to force a new fetch)',
				'description' => 'When the record was last fetched',
			],

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

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		//Allow last fetched to be overridden
		if (!empty($this->_changedFields) && !in_array('lastFetched', $this->_changedFields)) {
			$this->lastFetched = 0;
		}
		$this->clearDefaultCovers();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}

		return $ret;
	}

	public function insert($context = '') {
		$this->clearDefaultCovers();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false) : int {
		$this->clearLibraries();
		$this->clearLocations();
		$this->clearDefaultCovers();
		$this->deleted = true;
		return $this->update();
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryOpenArchivesCollection();
			$library->collectionId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries() {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
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

	private function clearLibraries() {
		//Delete links to the libraries
		$libraryOpenArchivesCollection = new LibraryOpenArchivesCollection();
		$libraryOpenArchivesCollection->collectionId = $this->id;
		return $libraryOpenArchivesCollection->delete(true);
	}

	public function getLocations() {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new LocationOpenArchivesCollection();
			$location->collectionId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$this->_locations[$location->locationId] = $location->locationId;
			}
		}
		return $this->_locations;
	}

	public function saveLocations() {
		if (isset($this->_locations) && is_array($this->_locations)) {
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

	private function clearLocations() {
		//Delete links to the libraries
		$locationOpenArchivesCollection = new LocationOpenArchivesCollection();
		$locationOpenArchivesCollection->collectionId = $this->id;
		return $locationOpenArchivesCollection->delete(true);
	}

	private function clearDefaultCovers() {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$covers = new BookCoverInfo();
		$covers->reloadAllDefaultCovers();
	}
}