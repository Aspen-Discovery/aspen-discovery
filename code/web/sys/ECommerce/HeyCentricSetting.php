<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/ECommerce/HeyCentricUrlParameterSetting.php'; 
require_once ROOT_DIR . '/sys/ECommerce/HeyCentricUrlParameter.php'; 

class HeyCentricSetting extends DataObject {
	public $__table = 'heycentric_setting';
	public $id;
	public $name;
	public $baseUrl;
	public $privateKey;

	private $_libraries;
	private $_locations;
	private $_urlParameterSettings;

	public function getEncryptedFieldNames(): array {
		return ['privateKey'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Locations'));
		$urlParameterSettingFields = HeyCentricUrlParameterSetting::getObjectStructure();

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
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'text',
				'hideInLists' => true,
				'label' => 'HeyCentric base URL',
				'description' => 'The base URL that links to the HeyCentric platform where patrons can make payments',
				'maxLength' => 50,
				'required' => true,
			],
			'privateKey' => [
				'property' => 'privateKey',
				'hideInLists' => true,
				'type' => 'storedPassword',
				'label' => 'HeyCentric Private Key',
				'description' => 'The HeyCentric Private Key for your site',
				'maxLength' => 50,
			],
			'urlParameterSettings' => [
				'property' => 'urlParameterSettings',
				'type' => 'section',
				'hideInLists' => true,
				'label' => 'HeyCentric URL Parameter Settings',
				'description' => 'The parameters to include when forming the HeyCentric payment URL and/or its hash',
				'maxLength' => 50,
				'required' => true,
				'properties' => $urlParameterSettingFields,
			],
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name): array|null {
		if ($name == "libraries" && !isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new Library();
			$obj->heyCentricSettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
			return $this->_libraries;
		}

		if ($name == "locations" && !isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new Location();
			$obj->heyCentricSettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
			return $this->_locations;
		}
		
		if (strpos($name, "value") || strpos($name, "includeInUrl") || strpos($name, "includeInHash") || strpos($name, "kohaAdditionalField") || $name == "urlParameterSettingList") {
			if (!isset($this->_urlParameterSettings)) {
				$this->_urlParameterSettings = [];
				$urlParameterList = HeyCentricUrlParameter::getHeyCentricUrlParamFields();
				foreach($urlParameterList as $urlParameter) {
					$urlParameterSetting = new HeyCentricUrlParameterSetting();
					if ($this->id) {
						$urlParameterSetting->heyCentricSettingId = $this->id;
						$urlParameterSetting->heyCentricUrlParameterId = $urlParameter['id'];
						$urlParameterSetting->find(true);
					}
					$this->_urlParameterSettings[$urlParameter['property'] . "_value"] = $this->id ? $urlParameterSetting->value : null;
					$this->_urlParameterSettings[$urlParameter['property'] . "_heyCentricSettingId"] = $this->id ? $urlParameterSetting->heyCentricSettingId : null;
					$this->_urlParameterSettings[$urlParameter['property'] . "_heyCentricUrlParameterId"] = $this->id ? $urlParameterSetting->heyCentricUrlParameterId : null;
					$this->_urlParameterSettings[$urlParameter['property'] . "_includeInUrl"] = $this->id ? $urlParameterSetting->includeInUrl : null;
					$this->_urlParameterSettings[$urlParameter['property'] . "_includeInHash"] = $this->id ? $urlParameterSetting->includeInHash : null;
					$this->_urlParameterSettings[$urlParameter['property'] . "_kohaAdditionalField"] = $this->id ? $urlParameterSetting->kohaAdditionalField : null;
				}
			}

			return $this->_urlParameterSettings;
		}
		return parent::__get($name);
	}

	public function __set($name, $value): void {
		if (strpos($name, '_') && !empty($this->_urlParameterSettings)) {
			$this->_urlParameterSettings[$name] = $value;
		}

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

	public function update(string $context = ''): int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveUrlParameterSettings();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveUrlParameterSettings();
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
				if ($library->heyCentricSettingId != $this->id) {
					$library->finePaymentType = 16;
					$library->heyCentricSettingId = $this->id;
					$library->update();
				}
			} else {
				if ($library->heyCentricSettingId == $this->id) {
					if ($library->finePaymentType == 16) {
						$library->finePaymentType = 0;
					}
					$library->heyCentricSettingId = -1;
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
				if ($location->heyCentricSettingId != $this->id) {
					$location->heyCentricSettingId = $this->id;
					$location->update();
				}
			} else {
				if ($location->heyCentricSettingId == $this->id) {
					if ($location->finePaymentType == 16) {
					}
					$location->heyCentricSettingId = -1;
					$location->update();
				}
			}
		}
		unset($this->_locations);
	}

	public function saveUrlParameterSettings(): void {
		if (!isset($this->_urlParameterSettings) || !is_array($this->_urlParameterSettings)) {
			return;
		}

		$urlParams = HeyCentricUrlParameter::getHeyCentricUrlParamFields();
		foreach ($urlParams as $objectStructure) {
			$urlParameterSetting = new HeyCentricUrlParameterSetting();
			$urlParameterSetting->heyCentricSettingId = $this->id;
			$urlParameterSetting->heyCentricUrlParameterId = $objectStructure['id'];
			if(!$urlParameterSetting->find(true)) {
				$urlParameterSetting->insert();
			}
			$urlParameterSetting->value = $this->_urlParameterSettings[$objectStructure["property"] . "_value"];
			$urlParameterSetting->includeInUrl = $this->_urlParameterSettings[$objectStructure['property'] . "_includeInUrl"];
			$urlParameterSetting->includeInHash = $this->_urlParameterSettings[$objectStructure['property'] . "_includeInHash"];
			$urlParameterSetting->kohaAdditionalField = $this->_urlParameterSettings[$objectStructure['property'] . "_kohaAdditionalField"];
			$urlParameterSetting->update();
		}
		unset($this->_urlParameterSettings);
	}
}