<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Omeka/OmekaSetting.php';

class OmekaScope extends DataObject {
	public $__table = 'omeka_scopes';
	public $id;
	public $name;
	public $settingId;
	public $includeAllItemSets;
	/** @noinspection PhpUnused */
	public $itemSetIds;

	private $_libraries;
	private $_locations;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$omekaSettings = [];
		$omekaSetting = new OmekaSetting();
		$omekaSetting->find();
		while ($omekaSetting->fetch()) {
			$omekaSettings[$omekaSetting->id] = $omekaSetting->name;
		}

		require_once ROOT_DIR . '/sys/Omeka/LibraryOmekaScope.php';
		require_once ROOT_DIR . '/sys/Omeka/LocationOmekaScope.php';
		$libraryOmekaScopeStructure = LibraryOmekaScope::getObjectStructure($context);
		unset($libraryOmekaScopeStructure['omekaScopeId']);
		$locationOmekaScopeStructure = LocationOmekaScope::getObjectStructure($context);
		unset($locationOmekaScopeStructure['omekaScopeId']);

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
				'values' => $omekaSettings,
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
			'includeAllItemSets' => [
				'property' => 'includeAllItemSets',
				'type' => 'checkbox',
				'label' => 'Include All Item Sets',
				'description' => 'Include every item from the server. This should be checked or a list of item sets to include should be provided.',
				'default' => 0,
				'forcesReindex' => true,
			],
			'itemSetIds' => [
				'property' => 'itemSetIds',
				'type' => 'text',
				'label' => 'Item Set IDs',
				'description' => 'A comma separated list of Omeka item set ids to include when Include All Item Sets is not checked.',
				'maxLength' => 500,
				'forcesReindex' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'omekaScopeId',
				'subObjectType' => 'LibraryOmekaScope',
				'structure' => $libraryOmekaScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'forcesReindex' => true,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'omekaScopeId',
				'subObjectType' => 'LocationOmekaScope',
				'structure' => $locationOmekaScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/Omeka/Scopes?objectAction=edit&id=' . $this->id;
	}

	static function getScopeListWithSettingNames(): array {
		$scopeList = [];
		$scope = new OmekaScope();
		$scope->joinAdd(new OmekaSetting(), 'INNER', 'setting', 'settingId', 'id');
		$scope->selectAdd();
		$scope->selectAdd('omeka_scopes.*');
		$scope->selectAdd('setting.name AS setting_name');
		$scope->orderBy('setting.name, omeka_scopes.name');
		$scope->find();
		$scopeData = $scope->fetchAssoc();
		while ($scopeData) {
			$scopeList[$scopeData['id']] = $scopeData['setting_name'] . ' - ' . $scopeData['name'];
			$scopeData = $scope->fetchAssoc();
		}
		return $scopeList;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraryScopes();
		} elseif ($name == "locations") {
			return $this->getLocationScopes();
		} else {
			return parent::__get($name);
		}
	}

	private function getLibraryScopes() {
		if (isset($this->_libraries) || !$this->id) {
			return $this->_libraries;
		}
		require_once ROOT_DIR . '/sys/Omeka/LibraryOmekaScope.php';
		$this->_libraries = [];
		$obj = new LibraryOmekaScope();
		$obj->omekaScopeId = $this->id;
		$obj->find();
		while ($obj->fetch()) {
			$this->_libraries[$obj->id] = clone($obj);
		}
		return $this->_libraries;
	}

	private function getLocationScopes() {
		if (isset($this->_locations) || !$this->id) {
			return $this->_locations;
		}
		require_once ROOT_DIR . '/sys/Omeka/LocationOmekaScope.php';
		$this->_locations = [];
		$obj = new LocationOmekaScope();
		$obj->omekaScopeId = $this->id;
		$obj->find();
		while ($obj->fetch()) {
			$this->_locations[$obj->id] = clone($obj);
		}
		return $this->_locations;
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
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret !== FALSE) {
			require_once ROOT_DIR . '/sys/Omeka/LibraryOmekaScope.php';
			require_once ROOT_DIR . '/sys/Omeka/LocationOmekaScope.php';
			$libraryScope = new LibraryOmekaScope();
			$libraryScope->omekaScopeId = $this->id;
			$libraryScope->delete(true);
			$locationScope = new LocationOmekaScope();
			$locationScope->omekaScopeId = $this->id;
			$locationScope->delete(true);
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->saveOneToManyOptions($this->_libraries, 'omekaScopeId');
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset($this->_locations) && is_array($this->_locations)) {
			$this->saveOneToManyOptions($this->_locations, 'omekaScopeId');
			unset($this->_locations);
		}
	}

	private null|bool|OmekaSetting $_omekaSettings = false;
	public function getSettings() : ?OmekaSetting {
		if ($this->_omekaSettings === false) {
			$this->_omekaSettings = null;
			if ($this->settingId > 0) {
				$omekaSettings = new OmekaSetting();
				$omekaSettings->id = $this->settingId;
				if ($omekaSettings->find(true)) {
					$this->_omekaSettings = $omekaSettings;
				}
			}
		}
		return $this->_omekaSettings;
	}
}
