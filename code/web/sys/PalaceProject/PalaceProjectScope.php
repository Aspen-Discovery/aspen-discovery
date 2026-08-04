<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';

class PalaceProjectScope extends DataObject {
	public $__table = 'palace_project_scopes';
	public $id;
	public $name;
	public $settingId;
	/** @noinspection PhpUnused */
	public $includeAdult;
	/** @noinspection PhpUnused */
	public $includeTeen;
	/** @noinspection PhpUnused */
	public $includeKids;

	private $_libraries;
	private $_locations;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$palaceProjectSettings = [];
		$palaceProjectSetting = new PalaceProjectSetting();
		$palaceProjectSetting->find();
		while ($palaceProjectSetting->fetch()) {
			$palaceProjectSettings[$palaceProjectSetting->id] = $palaceProjectSetting->name;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

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
				'values' => $palaceProjectSettings,
				'label' => 'Setting Id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the scope',
				'maxLength' => 50,
				'required' => true,
			],
			'includeAdult' => [
				'property' => 'includeAdult',
				'type' => 'checkbox',
				'label' => 'Include Adult Titles',
				'description' => 'Whether or not adult titles from the Palace Project collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from the Palace Project collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from the Palace Project collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
				'forcesReindex' => true,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/PalaceProject/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->palaceProjectScopeId = $this->id;
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
				$obj->palaceProjectScopeId = $this->id;
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
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->palaceProjectScopeId != $this->id) {
						$library->palaceProjectScopeId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->palaceProjectScopeId == $this->id) {
						$library->palaceProjectScopeId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->palaceProjectScopeId != $this->id) {
						$location->palaceProjectScopeId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->palaceProjectScopeId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->palaceProjectScopeId != -1) {
							$location->palaceProjectScopeId = -1;
						} else {
							$location->palaceProjectScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	private null|bool|PalaceProjectSetting $_palaceProjectSettings = false;
	public function getSettings() : ?PalaceProjectSetting {
		if ($this->_palaceProjectSettings === false) {
			$this->_palaceProjectSettings = null;
			if ($this->settingId > 0) {
				$palaceProjectSettings = new PalaceProjectSetting();
				$palaceProjectSettings->id = $this->settingId;
				if ($palaceProjectSettings->find(true)){
					$this->_palaceProjectSettings = $palaceProjectSettings;
				}
			}
		}
		return $this->_palaceProjectSettings;
	}
}