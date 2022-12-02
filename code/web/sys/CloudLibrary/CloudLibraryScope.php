<?php
require_once ROOT_DIR . '/sys/CloudLibrary/LibraryCloudLibraryScope.php';
require_once ROOT_DIR . '/sys/CloudLibrary/LocationCloudLibraryScope.php';

class CloudLibraryScope extends DataObject {
	public $__table = 'cloud_library_scopes';
	public $id;
	public $name;
	public $settingId;
	public /** @noinspection PhpUnused */
		$includeEAudiobook;
	public /** @noinspection PhpUnused */
		$includeEBooks;
	public /** @noinspection PhpUnused */
		$restrictToChildrensMaterial;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure(): array {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$cloudLibrarySettings = [];
		$cloudLibrarySetting = new CloudLibrarySetting();
		$cloudLibrarySetting->find();
		while ($cloudLibrarySetting->fetch()) {
			$cloudLibrarySettings[$cloudLibrarySetting->id] = (string)$cloudLibrarySetting;
		}

		$libraryCloudLibraryScopeStructure = LibraryCloudLibraryScope::getObjectStructure();
		unset($libraryCloudLibraryScopeStructure['scopeId']);

		$locationCloudLibraryScopeStructure = LocationCloudLibraryScope::getObjectStructure();
		unset($locationCloudLibraryScopeStructure['scopeId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'enum',
				'values' => $cloudLibrarySettings,
				'label' => 'Setting Id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the scope',
				'maxLength' => 50,
			],
			'includeEAudiobook' => [
				'property' => 'includeEAudiobook',
				'type' => 'checkbox',
				'label' => 'Include eAudio books',
				'description' => 'Whether or not EAudiobook are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'includeEBooks' => [
				'property' => 'includeEBooks',
				'type' => 'checkbox',
				'label' => 'Include eBooks',
				'description' => 'Whether or not EBooks are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'restrictToChildrensMaterial' => [
				'property' => 'restrictToChildrensMaterial',
				'type' => 'checkbox',
				'label' => 'Include Children\'s Materials Only',
				'description' => 'If checked only includes titles identified as children by cloudLibrary',
				'default' => 0,
				'forcesReindex' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'scopeId',
				'subObjectType' => 'LibraryCloudLibraryScope',
				'structure' => $libraryCloudLibraryScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Libraries',
						'url' => '/CloudLibrary/Scopes?id=$id&amp;objectAction=addToAllLibraries',
					],
					[
						'text' => 'Clear Libraries',
						'url' => '/CloudLibrary/Scopes?id=$id&amp;objectAction=clearLibraries',
						'class' => 'btn-warning',
					],
				],
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'sideLoadScopeId',
				'subObjectType' => 'LocationCloudLibraryScope',
				'structure' => $locationCloudLibraryScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Locations',
						'url' => '/CloudLibrary/Scopes?id=$id&amp;objectAction=addToAllLocations',
					],
					[
						'text' => 'Clear Locations',
						'url' => '/CloudLibrary/Scopes?id=$id&amp;objectAction=clearLocations',
						'class' => 'btn-warning',
					],
				],
				'forcesReindex' => true,
			],
		];
	}

	/** @noinspection PhpUnused */
	public function getEditLink($context): string {
		return '/CloudLibrary/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __toString() {
		return $this->getSetting() . " - " . $this->name;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new LibraryCloudLibraryScope();
				$obj->scopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->id] = clone($obj);
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new LocationCloudLibraryScope();
				$obj->scopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->id] = clone($obj);
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * @return int|bool
	 */
	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$this->saveOneToManyOptions($this->_libraries, 'scopeId');
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$this->saveOneToManyOptions($this->_locations, 'scopeId');
			unset($this->_locations);
		}
	}

	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries() {
		return $this->__get('libraries');
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations() {
		return $this->__get('locations');
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() {
		$this->clearOneToManyOptions('LibraryCloudLibraryScope', 'scopeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() {
		$this->clearOneToManyOptions('LocationCloudLibraryScope', 'scopeId');
		unset($this->_locations);
	}

	public function getSetting() {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$setting = new CloudLibrarySetting();
		$setting->id = $this->settingId;
		if ($setting->find(true)) {
			return $setting;
		} else {
			return null;
		}
	}
}
