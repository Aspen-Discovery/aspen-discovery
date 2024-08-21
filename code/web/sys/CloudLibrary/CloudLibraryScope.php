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
	public $includeAdult;
	public $includeTeen;
	public $includeKids;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure($context = ''): array {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$cloudLibrarySettings = [];
		$cloudLibrarySetting = new CloudLibrarySetting();
		$cloudLibrarySetting->find();
		while ($cloudLibrarySetting->fetch()) {
			$cloudLibrarySettings[$cloudLibrarySetting->id] = (string)$cloudLibrarySetting;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));


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
			'includeAdult' => [
				'property' => 'includeAdult',
				'type' => 'checkbox',
				'label' => 'Include Adult Titles',
				'description' => 'Whether or not adult titles from the Cloud Library collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from cloudLibrary should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from cloudLibrary should be included in searches',
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
				'hideInLists' => true,
				'forcesReindex' => true,
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
				'hideInLists' => true,
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
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
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

	/**
	 * @return int|bool
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				if (in_array($libraryId, $this->_libraries)) {
					$libraryCloudLibraryScope = new LibraryCloudLibraryScope();
					$libraryCloudLibraryScope->libraryId = $libraryId;
					if ($libraryCloudLibraryScope->find(true)) {
						if ($libraryCloudLibraryScope->scopeId != $this->id) {
							$libraryCloudLibraryScope->scopeId = $this->id;
							$libraryCloudLibraryScope->update();
						}
					} else {
						$libraryCloudLibraryScope->scopeId = $this->id;
						$libraryCloudLibraryScope->update();
					}
				} else {
					$libraryCloudLibraryScope = new LibraryCloudLibraryScope();
					$libraryCloudLibraryScope->libraryId = $libraryId;
					$libraryCloudLibraryScope->scopeId = $this->id;
					if ($libraryCloudLibraryScope->find(true)) {
						$libraryCloudLibraryScope->delete();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName) {
				if (in_array($locationId, $this->_locations)) {
					$locationCloudLibraryScope = new LocationCloudLibraryScope();
					$locationCloudLibraryScope->locationId = $locationId;
					if ($locationCloudLibraryScope->find(true)) {
						if ($locationCloudLibraryScope->scopeId != $this->id) {
							$locationCloudLibraryScope->scopeId = $this->id;
							$locationCloudLibraryScope->update();
						}
					} else {
						$locationCloudLibraryScope->scopeId = $this->id;
						$locationCloudLibraryScope->update();
					}
				} else {
					$locationCloudLibraryScope = new LocationCloudLibraryScope();
					$locationCloudLibraryScope->locationId = $locationId;
					$locationCloudLibraryScope->scopeId = $this->id;
					if ($locationCloudLibraryScope->find(true)) {
						$locationCloudLibraryScope->delete();
					}
				}
			}
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