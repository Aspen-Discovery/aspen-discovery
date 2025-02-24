<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';

class OverDriveScope extends DataObject {
	public $__table = 'overdrive_scopes';
	public $id;
	public $settingId;
	public $name;
	public $includeAdult;
	public $includeTeen;
	public $includeKids;

	protected $_libraries;
	protected $_locations;



	public static function getObjectStructure($context = ''): array {
		$overdriveSettings = [];
		$overdriveSetting = new OverDriveSetting();
		$overdriveSetting->find();
		while ($overdriveSetting->fetch()) {
			$overdriveSettings[$overdriveSetting->id] = (string)$overdriveSetting;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		require_once ROOT_DIR . '/sys/OverDrive/LibraryOverDriveScope.php';
		$libraryOverDriveScopeStructure = LibraryOverDriveScope::getObjectStructure($context);
		unset($libraryOverDriveScopeStructure['scopeId']);
		unset($libraryOverDriveScopeStructure['weight']);

		require_once ROOT_DIR . '/sys/OverDrive/LocationOverDriveScope.php';
		$locationOverDriveScopeStructure = LocationOverDriveScope::getObjectStructure($context);
		unset($locationOverDriveScopeStructure['scopeId']);
		unset($locationOverDriveScopeStructure['weight']);

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
				'values' => $overdriveSettings,
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
				'description' => 'Whether or not adult titles from the Overdrive collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from the Overdrive collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from the Overdrive collection should be included in searches',
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
	}

	/** @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getEditLink($context): string {
		return '/OverDrive/Scopes?objectAction=edit&id=' . $this->id;
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

	public function update($context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert($context = '') : int {
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
					$libraryOverDriveScopes = $library->getLibraryOverdriveScopes();
					if (in_array($libraryId, $this->_libraries)) {
						$foundScope = false;
						foreach ($libraryOverDriveScopes as $libraryOverDriveScope) {
							if ($libraryOverDriveScope->scopeId == $this->id) {
								$foundScope = true;
								break;
							}
						}
						//We want to apply the scope to this library
						if (!$foundScope) {
							$libraryOverDriveScope = new LibraryOverDriveScope();
							$libraryOverDriveScope->scopeId = $this->id;
							$libraryOverDriveScope->libraryId = $libraryId;
							$libraryOverDriveScope->insert();
						}
					} else {
						//It should not be applied to this scope. Only change if it was applied previously
						foreach ($libraryOverDriveScopes as $libraryOverDriveScope) {
							if ($libraryOverDriveScope->scopeId == $this->id) {
								$libraryOverDriveScope->delete();
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
					$locationOverDriveScopes = $location->getLocationOverdriveScopes();
					if (in_array($locationId, $this->_locations)) {
						$foundScope = false;
						foreach ($locationOverDriveScopes as $locationOverDriveScope) {
							if ($locationOverDriveScope->scopeId == $this->id) {
								$foundScope = true;
								break;
							}
						}
						//We want to apply the scope to this location
						if (!$foundScope) {
							$locationOverDriveScope = new LocationOverDriveScope();
							$locationOverDriveScope->scopeId = $this->id;
							$locationOverDriveScope->locationId = $locationId;
							$locationOverDriveScope->insert();
						}
					} else {
						//It should not be applied to this scope. Only change if it was applied previously
						foreach ($locationOverDriveScopes as $locationOverDriveScope) {
							if ($locationOverDriveScope->scopeId == $this->id) {
								$locationOverDriveScope->delete();
							}
						}
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return LibraryOverDriveScope[] */
	public function getLibraries() : array {
		if (!isset($this->_libraries)) {
			$this->_libraries = [];
			if ($this->id > 0) {
				require_once ROOT_DIR . '/sys/OverDrive/LibraryOverDriveScope.php';
				$libraryOverDriveScope = new LibraryOverDriveScope();
				$libraryOverDriveScope->scopeId = $this->id;
				$this->_libraries = $libraryOverDriveScope->fetchAll('libraryId');
			}
		}
		return $this->_libraries;
	}

	/** @return LocationOverDriveScope[]
	 * @noinspection PhpUnused
	 */
	public function getLocations() : array {
		if (!isset($this->_locations)) {
			$this->_locations = [];
			if ($this->id > 0) {
				require_once ROOT_DIR . '/sys/OverDrive/LocationOverDriveScope.php';
				$locationOverDriveScope = new LocationOverDriveScope();
				$locationOverDriveScope->scopeId = $this->id;
				$this->_locations = $locationOverDriveScope->fetchAll('locationId');
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
		$this->_libraries = $val;
	}

	public function clearLibraries() : void {
		$this->clearOneToManyOptions('Library', 'overDriveScopeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() : void {
		$this->clearOneToManyOptions('Location', 'overDriveScopeId');
		unset($this->_locations);
	}
}
