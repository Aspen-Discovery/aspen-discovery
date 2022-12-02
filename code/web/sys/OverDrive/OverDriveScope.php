<?php

require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';

class OverDriveScope extends DataObject {
	public $__table = 'overdrive_scopes';
	public $id;
	public $settingId;
	public $name;
	public $includeAdult;
	public $includeTeen;
	public $includeKids;
	public $clientSecret;
	public $clientKey;
	public $authenticationILSName;
	public $requirePin;
	public /** @noinspection PhpUnused */
		$overdriveAdvantageName;
	public /** @noinspection PhpUnused */
		$overdriveAdvantageProductsKey;
	public $circulationEnabled;

	private $_libraries;
	private $_locations;

	public function getEncryptedFieldNames(): array {
		return ['clientSecret'];
	}

	public static function getObjectStructure(): array {
		$overdriveSettings = [];
		$overdriveSetting = new OverDriveSetting();
		$overdriveSetting->find();
		while ($overdriveSetting->fetch()) {
			$overdriveSettings[$overdriveSetting->id] = (string)$overdriveSetting;
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
			'circulationEnabled' => [
				'property' => 'circulationEnabled',
				'type' => 'checkbox',
				'label' => 'Circulation Enabled',
				'description' => 'Whether or not circulation is enabled within Aspen',
				'hideInLists' => true,
				'default' => true,
				'forcesReindex' => false,
			],
			'clientKey' => [
				'property' => 'clientKey',
				'type' => 'text',
				'label' => 'Circulation Client Key (if different from settings)',
				'description' => 'The client key provided by OverDrive when registering',
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Circulation Client Secret (if different from settings)',
				'description' => 'The client secret provided by OverDrive when registering',
				'hideInLists' => true,
			],
			'authenticationILSName' => [
				'property' => 'authenticationILSName',
				'type' => 'text',
				'label' => 'The ILS Name Overdrive uses for user Authentication',
				'description' => 'The name of the ILS that OverDrive uses to authenticate users logging into the Overdrive website.',
				'size' => '20',
				'hideInLists' => true,
			],
			'requirePin' => [
				'property' => 'requirePin',
				'type' => 'checkbox',
				'label' => 'Is a Pin Required to log into Overdrive website?',
				'description' => 'Turn on to allow repeat search in Overdrive functionality.',
				'hideInLists' => true,
				'default' => 0,
			],
			'overdriveAdvantageName' => [
				'property' => 'overdriveAdvantageName',
				'type' => 'text',
				'label' => 'Overdrive Advantage Name',
				'description' => 'The name of the OverDrive Advantage account if any.',
				'size' => '80',
				'hideInLists' => true,
				'forcesReindex' => true,
			],
			'overdriveAdvantageProductsKey' => [
				'property' => 'overdriveAdvantageProductsKey',
				'type' => 'text',
				'label' => 'Overdrive Advantage Products Key',
				'description' => 'The products key for use when building urls to the API from the advantageAccounts call.',
				'size' => '80',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'includeAdult' => [
				'property' => 'includeAdult',
				'type' => 'checkbox',
				'label' => 'Include Adult Titles',
				'description' => 'Whether or not adult titles from the Overdrive collection should be included in searches',
				'hideInLists' => true,
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from the Overdrive collection should be included in searches',
				'hideInLists' => true,
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from the Overdrive collection should be included in searches',
				'hideInLists' => true,
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
	}

	/** @noinspection PhpUnused */
	public function getEditLink($context): string {
		return '/OverDrive/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->overDriveScopeId = $this->id;
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
				$obj->overDriveScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
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

	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
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
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->overDriveScopeId != $this->id) {
						$library->overDriveScopeId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->overDriveScopeId == $this->id) {
						$library->overDriveScopeId = -1;
						$library->update();
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
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->overDriveScopeId != $this->id) {
						$location->overDriveScopeId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->overDriveScopeId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->overDriveScopeId != -1) {
							$location->overDriveScopeId = -1;
						} else {
							$location->overDriveScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[] */
	public function getLibraries() {
		return $this->_libraries;
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations() {
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val) {
		$this->_libraries = $val;
	}

	public function clearLibraries() {
		$this->clearOneToManyOptions('Library', 'overDriveScopeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() {
		$this->clearOneToManyOptions('Location', 'overDriveScopeId');
		unset($this->_locations);
	}
}