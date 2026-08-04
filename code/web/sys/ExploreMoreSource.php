<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/ExploreMoreSourceLibrary.php';
require_once ROOT_DIR . '/sys/ExploreMoreSourceLocation.php';

class ExploreMoreSource extends DataObject {
	public $__table = 'explore_more_source';
	public $id;
	public $source;
	public $weight;
	public $showInExploreMore;
	private $libraries = [];
	private $locations = [];

	private function normalizeSelectedIds($values): array {
		if (!is_array($values)) {
			if ($values === null || $values === '') {
				$values = [];
			} else {
				$values = [$values];
			}
		}
		$normalizedValues = [];
		foreach ($values as $key => $value) {
			$selectedId = $value;
			if (($selectedId === null || $selectedId === '') && !empty($key)) {
				$selectedId = $key;
			}
			if ($selectedId === null || $selectedId === '') {
				continue;
			}
			$selectedId = (string)$selectedId;
			$normalizedValues[$selectedId] = $selectedId;
		}
		return $normalizedValues;
	}

	public function setLibraries($libraries): void {
		$this->libraries = $this->normalizeSelectedIds($libraries);
	}

	public function setLocations($locations): void {
		$this->locations = $this->normalizeSelectedIds($locations);
	}

	/** @var ExploreMoreSource[] */
	protected $_sources;

	public function getSources() : ?array {
		if (!isset($this->_sources) && $this->id) {
			$this->_sources = [];
			$sourceObj = new ExploreMoreSource();
			$sourceObj->orderBy('weight ASC');
			$sourceObj->find();
			while ($sourceObj->fetch()) {
				$this->_sources[$sourceObj->id] = clone($sourceObj);
			}
		}
		return $this->_sources;
	}

	public function getLibraries() : array {
		if (!is_array($this->libraries) || empty($this->libraries)) {
			$this->libraries = [];
			if ($this->id) {
				$obj = new ExploreMoreSourceLibrary();
				$obj->exploreMoreSourceId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$libraryId = (string)$obj->libraryId;
					$this->libraries[$libraryId] = $libraryId;
				}
			}
		}
		$this->libraries = $this->normalizeSelectedIds($this->libraries);
		return $this->libraries;
	}

	public function getLocations() : array {
		if (!is_array($this->locations) || empty($this->locations)) {
			$this->locations = [];
			if ($this->id) {
				$obj = new ExploreMoreSourceLocation();
				$obj->exploreMoreSourceId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$locationId = (string)$obj->locationId;
					$this->locations[$locationId] = $locationId;
				}
			}
		}
		$this->locations = $this->normalizeSelectedIds($this->locations);
		return $this->locations;
	}

	public function insert(string $context = '') : int|bool {
		$result = parent::insert($context);
		$this->saveLibraries();
		$this->saveLocations();
		return $result;
	}

	public function update(string $context = '') : int|bool {
		$result = parent::update($context);
		$this->saveLibraries();
		$this->saveLocations();
		return $result;
	}

	private function saveLibraries(): void {
		if ($this->id !== null) {
			$obj = new ExploreMoreSourceLibrary();
			$obj->exploreMoreSourceId = $this->id;
			$obj->delete(true);
			if (is_array($this->libraries)) {
				foreach ($this->libraries as $libraryId) {
					$lib = new ExploreMoreSourceLibrary();
					$lib->exploreMoreSourceId = $this->id;
					$lib->libraryId = (string)$libraryId;
					$lib->insert();
				}
			}
		}
	}

	private function saveLocations(): void {
		if ($this->id !== null) {
			$obj = new ExploreMoreSourceLocation();
			$obj->exploreMoreSourceId = $this->id;
			$obj->delete(true);
			if (is_array($this->locations)) {
				foreach ($this->locations as $locationId) {
					$loc = new ExploreMoreSourceLocation();
					$loc->exploreMoreSourceId = $this->id;
					$loc->locationId = $locationId;
					$loc->insert();
				}
			}
		}
	}

	public function __get($name) {
		if ($name === 'libraries') {
			return $this->getLibraries();
		} elseif ($name === 'locations') {
			return $this->getLocations();
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if ($name === 'libraries') {
			$this->setLibraries($value);
		} elseif ($name === 'locations') {
			$this->setLocations($value);
		} else {
			parent::__set($name, $value);
		}
	}

	static $_objectStructure = [];
	static function getObjectStructure($context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/ExploreMoreSourceEntry.php';
		$entryStructure = ExploreMoreSourceEntry::getObjectStructure($context);
		unset($entryStructure['exploreMoreSourceGroupId']);
		unset($entryStructure['weight']);
		// Ensure keys are strings for UI matching
		$libraryList = Library::getLibraryList(false);
		$libraryListStringKeys = [];
		foreach ($libraryList as $k => $v) {
			$libraryListStringKeys[(string)$k] = $v;
		}
		$locationList = Location::getLocationList(false);
		$locationListStringKeys = [];
		foreach ($locationList as $k => $v) {
			$locationListStringKeys[(string)$k] = $v;
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'ID',
				'description' => 'The unique id',
				'hideInLists' => true,
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'label' => 'Source',
				'values' => [
					'Catalog' => 'Catalog',
					'EBSCO EDS' => 'EBSCO EDS',
					'EBSCOhost' => 'EBSCOhost',
					'Summon' => 'Summon',
					'Gale' => 'Gale',
					'CloudSource' => 'CloudSource',
					'Events' => 'Events',
					'Web Indexer' => 'Web Indexer',
					'Lists' => 'Lists',
					'Open Archives' => 'Open Archives',
					'Series' => 'Series',
					'Genealogy' => 'Genealogy',
				],
				'required' => true,
				'readOnly' => true,
			],
			'showInExploreMore' => [
				'property' => 'showInExploreMore',
				'type' => 'checkbox',
				'label' => 'Show in Explore More',
				'description' => 'Whether this source displays content in Explore More',
				'default' => '1',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'label' => 'Libraries (selecting none activates for all)',
				'description' => 'Libraries where this source is visible',
				'listStyle' => 'checkboxSimple',
				'values' => $libraryListStringKeys,
				'uiOnly' => true,
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'label' => 'Locations (selecting none activates for all)',
				'description' => 'Locations where this source is visible',
				'listStyle' => 'checkboxSimple',
				'values' => $locationListStringKeys,
				'uiOnly' => true,
			],
		];
		self::$_objectStructure[$context] = $structure;
		return $structure;
	}
}
