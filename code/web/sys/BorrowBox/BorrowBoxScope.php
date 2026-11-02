<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';

class BorrowBoxScope extends DataObject {
	public $__table = 'borrowbox_scopes';
	public $id;
	public $settingId;
	public $name;
	public $includeAdult;
	public $includeTeen;
	public $includeKids;
	protected $_libraries;
	protected $_locations;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$borrowBoxSettings = [];
		$borrowBoxSetting = new BorrowBoxSetting();
		$borrowBoxSetting->find();
		while ($borrowBoxSetting->fetch()) {
			$borrowBoxSettings[$borrowBoxSetting->id] = (string)$borrowBoxSetting;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxScope.php';
		$libraryBorrowBoxScopeStructure = LibraryBorrowBoxScope::getObjectStructure($context);
		unset($libraryBorrowBoxScopeStructure['scopeId']);
		unset($libraryBorrowBoxScopeStructure['weight']);

		require_once ROOT_DIR . '/sys/BorrowBox/LocationBorrowBoxScope.php';
		$locationBorrowBoxScopeStructure = LocationBorrowBoxScope::getObjectStructure($context);
		unset($locationBorrowBoxScopeStructure['scopeId']);
		unset($locationBorrowBoxScopeStructure['weight']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'enum',
				'values' => $borrowBoxSettings,
				'label' => 'Setting Id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the scope',
				'maxLength' => 50,
			],
			'includeAdult' => [
				'property' => 'includeAdult',
				'type' => 'checkbox',
				'label' => 'Include Adult Titles',
				'description' => 'Whether or not adult titles from the BorrowBox collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from the BorrowBox collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from the BorrowBox collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => "Libraries",
				'description' => "The libraries that use this scope",
				'values' => $libraryList,
				'hideInLists' => false,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => "Locations",
				'description' => "The locations that use this scope",
				'values' => $locationList,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/BorrowBox/Scopes?objectAction=edit&id=' . $this->id;
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

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				if ($library->find(true)) {
					$libraryBorrowBoxScopes = $library->getLibraryBorrowBoxScopes();
					if (in_array($libraryId, $this->_libraries)) {
						$foundScope = false;
						foreach ($libraryBorrowBoxScopes as $libraryBorrowBoxScope) {
							if ($libraryBorrowBoxScope->scopeId == $this->id) {
								$foundScope = true;
								break;
							}
						}
						//We want to apply the scope to this library
						if (!$foundScope) {
							$libraryBorrowBoxScope = new LibraryBorrowBoxScope();
							$libraryBorrowBoxScope->scopeId = $this->id;
							$libraryBorrowBoxScope->libraryId = $libraryId;
							$libraryBorrowBoxScope->insert();
						}
					} else {
						//It should not be applied to this scope. Only change if it was applied previously
						foreach ($libraryBorrowBoxScopes as $libraryBorrowBoxScope) {
							if ($libraryBorrowBoxScope->scopeId == $this->id) {
								$libraryBorrowBoxScope->delete();
							}
						}
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				if ($location->find(true)) {
					$locationBorrowBoxScopes = $location->getLocationBorrowBoxScopes();
					if (in_array($locationId, $this->_locations)) {
						$foundScope = false;
						foreach ($locationBorrowBoxScopes as $locationBorrowBoxScope) {
							if ($locationBorrowBoxScope->scopeId == $this->id) {
								$foundScope = true;
								break;
							}
						}
						//We want to apply the scope to this location
						if (!$foundScope) {
							$locationBorrowBoxScope = new LocationBorrowBoxScope();
							$locationBorrowBoxScope->scopeId = $this->id;
							$locationBorrowBoxScope->locationId = $locationId;
							$locationBorrowBoxScope->insert();
						}
					} else {
						//It should not be applied to this scope. Only change if it was applied previously
						foreach ($locationBorrowBoxScopes as $locationBorrowBoxScope) {
							if ($locationBorrowBoxScope->scopeId == $this->id) {
								$locationBorrowBoxScope->delete();
							}
						}
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return LibraryBorrowBoxScope[] */
	public function getLibraries() : array {
		if (!isset($this->_libraries)) {
			$this->_libraries = [];
			if ($this->id > 0) {
				require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxScope.php';
				$libraryBorrowBoxScope = new LibraryBorrowBoxScope();
				$libraryBorrowBoxScope->scopeId = $this->id;
				$this->_libraries = $libraryBorrowBoxScope->fetchAll('libraryId');
			}
		}
		return $this->_libraries;
	}

	/** @return LocationBorrowBoxScope[]
	 * @noinspection PhpUnused
	 */
	public function getLocations() : array {
		if (!isset($this->_locations)) {
			$this->_locations = [];
			if ($this->id > 0) {
				require_once ROOT_DIR . '/sys/BorrowBox/LocationBorrowBoxScope.php';
				$locationBorrowBoxScope = new LocationBorrowBoxScope();
				$locationBorrowBoxScope->scopeId = $this->id;
				$this->_locations = $locationBorrowBoxScope->fetchAll('locationId');
			}
		}
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) : void {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val) : void {
		$this->_locations = $val;
	}

	public function clearLibraries() : void {
		$this->clearOneToManyOptions('Library', 'borrowBoxScopeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() : void {
		$this->clearOneToManyOptions('Location', 'borrowBoxScopeId');
		unset($this->_locations);
	}
}
