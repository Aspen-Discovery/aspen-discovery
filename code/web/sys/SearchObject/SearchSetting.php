<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/SearchObject/SearchTypes.php';
require_once ROOT_DIR . '/sys/SearchObject/SortOptions.php';

class SearchSetting extends DataObject {
	public $__table = 'search_settings';
	public $id;
	public $name;

	protected $_searchTypes;
	protected $_sortOptions;

	protected $_libraries;
	protected $_locations;


	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Search Settings'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Search Settings'));

		$searchTypeStructure = SearchTypes::getObjectStructure($context);
		$sortOptionsStructure = SortOptions::getObjectStructure($context);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database.',
				'uniqueProperty' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings.',
				'uniqueProperty' => true,
			],
			'searchTypes' => [
				'property' => 'searchTypes',
				'type' => 'oneToMany',
				'label' => 'Search Types',
				'description' => 'Search Type Settings',
				'keyThis' => 'id',
				'keyOther' => 'searchSettingId',
				'subObjectType' => 'SearchTypes',
				'structure' => $searchTypeStructure,
				'sortable' => false,
				'storeDb' => true,
				'hideInLists' => true,
			],
			'sortOptions' => [
				'property' => 'sortOptions',
				'type' => 'oneToMany',
				'label' => 'Sort Options',
				'description' => 'Sort Option Settings',
				'keyThis' => 'id',
				'keyOther' => 'searchSettingId',
				'subObjectType' => 'SortOptions',
				'structure' => $sortOptionsStructure,
				'sortable' => false,
				'storeDb' => true,
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this Grouped Work Display setting.',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this Grouped Work Display setting.',
				'values' => $locationList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function __get($name) {
		if ($name === 'searchTypes') {
			if (!isset($this->_searchTypes)) {
				$this->_searchTypes = [];
				if ($this->id) {
					// Existing setting: load from DB
					$searchType = new SearchTypes();
					$searchType->searchSettingId = $this->id;
					$searchType->orderBy('id');
					$searchType->find();
					while ($searchType->fetch()) {
						$this->_searchTypes[$searchType->id] = clone $searchType;
					}
				} else {
					// New setting: pre-fill with defaults
					$defaults = [
						'Keyword' => 'Keyword',
						'Title'   => 'Title',
						'StartOfTitle'  => 'Start of Title',
						'Series' => 'Series',
						'PrimaryAuthor'    => 'Author',
						'Author' => 'Authors and Contributors',
						'Subject' => 'Subject',
						'LocalCallNumber' => 'Call Number',
						'ISN' => 'ISBN/ISSN/UPC',
						'Publisher' => 'Publisher',
						'year' => 'Year of Publication',
						'toc' => 'Table of Contents',
						'id' => 'Record Number',
					];
					$defaultAdvancedOnly = ['ISN', 'Publisher', 'year', 'toc', 'id'];

					$tempId = -1;
					foreach ($defaults as $type => $label) {
						$searchType = new SearchTypes();
						$searchType->id = $tempId--;
						$searchType->type = $type;
						$searchType->label = $label;
						$searchType->defaultLabel = $label;
						$searchType->enabled = in_array($type, $defaultAdvancedOnly) ? 2 : 1;
						$this->_searchTypes[$searchType->id] = $searchType;
					}
				}
			}
			return $this->_searchTypes;
		} elseif ($name === 'sortOptions') {
			if (!isset($this->_sortOptions)) {
				$this->_sortOptions = [];
				if ($this->id) {
					// Existing setting: load from DB
					$sortOption = new SortOptions();
					$sortOption->searchSettingId = $this->id;
					$sortOption->orderBy('id');
					$sortOption->find();
					while ($sortOption->fetch()) {
						$this->_sortOptions[$sortOption->id] = clone $sortOption;
					}
				} else {
					// New setting: pre-fill with defaults
					$defaults = [
						'relevance' => 'Best Match',
						'year desc,title asc' => "Publication Year Desc",
						'year asc,title asc' => "Publication Year Asc",
						'author asc,title asc' => "Author",
						'title' => 'Title',
						'days_since_added asc' => "Date Purchased Desc",
						'callnumber_sort' => 'Call Number',
						'popularity desc' => 'Total Checkouts',
						'rating asc' => 'User Rating (Ascending)',
						'rating desc' => 'User Rating (Descending)',
						'total_holds desc' => "Number of Holds",
					];
					$tempId = -1;
					foreach ($defaults as $type => $label) {
						$sortOption = new SortOptions();
						$sortOption->id = $tempId--;
						$sortOption->type = $type;
						$sortOption->label = $label;
						$sortOption->defaultLabel = $label;
						$sortOption->enabled = 1;
						$this->_sortOptions[$sortOption->id] = $sortOption;
					}
				}
			}
			return $this->_sortOptions;
		} elseif ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->searchSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new Location();
				$obj->searchSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return parent::__get($name);
		}
	}
	public function __set($name, $value) {
		if ($name === 'searchTypes') {
			$this->_searchTypes = $value;
		} elseif ($name === 'sortOptions') {
			$this->_sortOptions = $value;
		} elseif ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSearchTypes();
			$this->saveSortOptions();
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSearchTypes();
			$this->saveSortOptions();
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveSearchTypes() : void {
		if (isset ($this->_searchTypes)) {
			$this->saveOneToManyOptions($this->_searchTypes, 'searchSettingId');
			unset($this->_searchTypes);
		}
	}

	public function saveSortOptions() : void {
		if (isset ($this->_sortOptions)) {
			$this->saveOneToManyOptions($this->_sortOptions, 'searchSettingId');
			unset($this->_sortOptions);
		}
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Search Settings'));
			$this->saveOneToManyOptions($this->_libraries, 'searchSettingId', $libraryList, 'Library');
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset ($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Search Settings'));
			$this->saveOneToManyOptions($this->_locations, 'searchSettingId', $locationList, 'Location');
			unset($this->_locations);
		}
	}

	/** @noinspection PhpUnused */
	public function clearSearchTypes() : void {
		$this->clearOneToManyOptions('SearchTypes', 'searchSettingId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearSortOptions() : void {
		$this->clearOneToManyOptions('SortOptions', 'searchSettingId');
		unset($this->_locations);
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() : void {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Search Settings'));
		$this->clearOneToManyOptions('Library', 'searchSettingId', $libraryList);
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() : void {
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Search Settings'));
		$this->clearOneToManyOptions('Location', 'searchSettingId', $locationList);
		unset($this->_locations);
	}


}