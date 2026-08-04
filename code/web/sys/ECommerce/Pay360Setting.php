<?php

class Pay360Setting extends DataObject {
	public $__table = 'pay360_setting';
	public $id;
	public $name;
	public $privateKey;
	public $wsdlUrl;
	public $scpId;
	public $hmacKeyId;
	public $siteId;
	public $algorithm;
	public $subjectType;
	public $systemCode;
	public $pollingEnabled;

	private $_libraries;
	private $_locations;

	public function getEncryptedFieldNames(): array {
		return ['privateKey'];
	}

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Locations'));

		$structure = [
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
				'description' => 'A name for the settings',
				'maxLength' => 50,
			],
			'privateKey' => [
				'property' => 'privateKey',
				'hideInLists' => true,
				'type' => 'storedPassword',
				'label' => 'Pay360 Private Key',
				'description' => 'The Pay360 Private Key (provided by Capita)',
				'maxLength' => 255,
			],
			'wsdlUrl' => [
				'property' => 'wsdlUrl',
				'type' => 'text',
				'hideInLists' => true,
				'label' => 'Pay360 WSDL URL',
				'description' => 'The WSDL URL for use when sending requests to Pay360',
				'maxLength' => 255,
				'required' => true,
			],
			'scpId' => [
				'property' => 'scpId',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 SCP ID',
				'description' => 'The Pay360 SCP ID (provided by Capita)',
				'maxLength' => 50,
			],
			'hmacKeyId' => [
				'property' => 'hmacKeyId',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 HMAC Key ID',
				'description' => 'The Pay360 HMAC Key ID (provided by Capita)',
				'maxLength' => 50,
			],
			'siteId' => [
				'property' => 'siteId',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 Site Id',
				'description' => 'The Pay360 Site Id (provided by Capita)',
				'maxLength' => 50,
			],
			'algorithm' => [
				'property' => 'algorithm',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 algorithm',
				'description' => 'The Pay360 HMAC Algorithm (provided by Capita)',
				'maxLength' => 50,
			],
			'subjectType' => [
				'property' => 'subjectType',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 Subject Type',
				'description' => 'The Pay360 Subject Type',
				'maxLength' => 50,
			],
			'systemCode' => [
				'property' => 'systemCode',
				'hideInLists' => true,
				'type' => 'text',
				'label' => 'Pay360 System Code',
				'description' => 'The Pay360 System Code',
				'maxLength' => 50,
			],
			'pollingEnabled' => [
				'property' => 'pollingEnabled',
				'type' => 'checkbox',
				'label' => 'Enable Polling',
				'description' => 'Whenever a patron uses the Click to pay online button, start a polling process to check Pay360 for status updates. This will start 10 minutes after the patron first accessed the link, and run every 5 minutes for up to 30 minutes.',
				'hideInLists' => true,
				'default' => true,
			],
			// RESERVED FOR FUTURE USE
			// 'errorUrl' => [
			// 	'property' => 'errorUrl',
			// 	'hideInLists' => true,
			// 	'type' => 'text',
			// 	'label' => 'Pay360 Error URL',
			// 	'description' => '',
			// 	'maxLength' => 50,
			// ],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use these settings',
				'values' => $locationList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name): array|null {
		if ($name == "libraries" && !isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new Library();
			$obj->pay360SettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
			return $this->_libraries;
		}

		if ($name == "locations" && !isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new Location();
			$obj->pay360SettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
			return $this->_locations;
		}
		return parent::__get($name);
	}

	public function __set($name, $value): void {
		switch ($name) {
			case "libraries":
				$this->_libraries = $value;
				break;
			case "locations":
				$this->_locations = $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	public function update($context = ''): bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert($context = ''): int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries(): void {
		if (!isset ($this->_libraries) || !is_array($this->_libraries)) {
			return;
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		foreach ($libraryList as $libraryId => $displayName) {
			$library = new Library();
			$library->libraryId = $libraryId;
			$library->find(true);
			if (in_array($libraryId, $this->_libraries)) {
				if ($library->pay360SettingId != $this->id) {
					$library->finePaymentType = 17;
					$library->pay360SettingId = $this->id;
					$library->update();
				}
			} else {
				if ($library->pay360SettingId == $this->id) {
					if ($library->finePaymentType == 17) {
						$library->finePaymentType = 0;
					}
					$library->pay360SettingId = -1;
					$library->update();
				}
			}
		}
		unset($this->_libraries);
	}
	
	public function saveLocations(): void {
		if (!isset ($this->_locations) || !is_array($this->_locations)) {
			return;
		}
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Locations'));
		foreach ($locationList as $locationId => $displayName) {
			$location = new Location();
			$location->locationId = $locationId;
			$location->find(true);
			if (in_array($locationId, $this->_locations)) {
				if ($location->pay360SettingId != $this->id) {
					$location->pay360SettingId = $this->id;
					$location->update();
				}
			} else {
				if ($location->pay360SettingId == $this->id) {
					$location->pay360SettingId = -1;
					$location->update();
				}
			}
		}
		unset($this->_locations);
	}
}