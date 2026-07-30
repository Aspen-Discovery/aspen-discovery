<?php /** @noinspection PhpMissingFieldTypeInspection */


require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationFormValues.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationTerms.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SymphonySelfRegistrationMunicipalityValues.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SymphonySelfRegistrationCountyCodeValues.php';

class SelfRegistrationForm extends DataObject {
	public $__table = 'self_registration_form';
	public $__displayNameColumn = 'ilsName';
	public $id;
	public $name;
	public $selfRegistrationBarcodePrefix;
	public $selfRegBarcodeSuffixLength;
	public $noDuplicateCheck;
	public $promptForSMSNoticesInSelfReg;
	public $selfRegistrationUserProfile;
	public $promptForParentInSelfReg;
	public $cityStateField;
	public $termsOfServiceSetting;

	private $_fields;
	private $_libraries;
	private $_municipalities;
	private $_counties;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$selfRegistrationTerms = [];
		$selfRegistrationTOS = new SelfRegistrationTerms();
		$selfRegistrationTOS->find();
		$selfRegistrationTerms[-1] = 'None';
		while ($selfRegistrationTOS->fetch()) {
			$selfRegistrationTerms[$selfRegistrationTOS->id] = (string)$selfRegistrationTOS->name;
		}

		$fieldValuesStructure = SelfRegistrationFormValues::getObjectStructure($context);
		$symphonySelfRegistrationMunicipalityValuesStructure = SymphonySelfRegistrationMunicipalityValues::getObjectStructure($context);
		$symphonySelfRegistrationCountyCodeValuesStructure = SymphonySelfRegistrationCountyCodeValues::getObjectStructure($context);
		unset($fieldValuesStructure['weight']);
		unset($fieldValuesStructure['selfRegistrationFormId']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the settings',
				'size' => '40',
				'maxLength' => 255,
			],
			'termsOfServiceSetting' => [
				'property' => 'termsOfServiceSetting',
				'type' => 'enum',
				'values' => $selfRegistrationTerms,
				'label' => 'Terms of Service Form',
			],
			'fields' => [
				'property' => 'fields',
				'type' => 'oneToMany',
				'label' => 'Fields',
				'description' => 'The fields for self registration',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'SelfRegistrationFormValues',
				'structure' => $fieldValuesStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'note' => 'Home Library must be included in the form'
			],
			'promptForSMSNoticesInSelfReg' => [
				'property' => 'promptForSMSNoticesInSelfReg',
				'type' => 'checkbox',
				'label' => 'Prompt For SMS Notices',
				'description' => 'Whether or not SMS Notification information should be requested.',
			],
			'promptForParentInSelfReg' => [
				'property' => 'promptForParentInSelfReg',
				'type' => 'checkbox',
				'label' => 'Prompt For Parent Information',
				'description' => 'Whether or not parent information should be requested if the person registering is a juvenile.',
			],
			'noDuplicateCheck' => [
				'property' => 'noDuplicateCheck',
				'type' => 'checkbox',
				'label' => 'Turn Off Duplicate Checking',
				'description' => 'Turn off checking for duplicate users in self registration.',
			],
			'cityStateField' => [
				'property' => 'cityStateField',
				'type' => 'enum',
				'values' => [
					0 => 'CITY / STATE field',
					1 => 'CITY and STATE fields',
					2 => 'CITY / STATE field - comma separated',
				],
				'label' => 'City / State Field',
				'description' => 'The field from which to load and update city and state.',
				'hideInLists' => true,
				'default' => 0,
				'permissions' => ['Library ILS Connection'],
			],
			'selfRegistrationUserProfile' => [
				'property' => 'selfRegistrationUserProfile',
				'type' => 'text',
				'label' => 'Self Registration Profile',
				'description' => 'The Profile to use during self registration.',
				'hideInLists' => true,
				'default' => 'SELFREG',
			],
			'selfRegistrationBarcodePrefix' => [
				'property' => 'selfRegistrationBarcodePrefix',
				'type' => 'text',
				'maxLength' => 10,
				'label' => 'Self Registration Barcode Prefix',
				'description' => 'The barcode prefix to use during self registration.',
				'default' => '',
			],
			'selfRegBarcodeSuffixLength' => [
				'property' => 'selfRegBarcodeSuffixLength',
				'type' => 'integer',
				'maxLength' => 2,
				'label' => 'Self Registration Barcode Suffix Length',
				'description' => 'Remaining length of the self registration barcode after the prefix.',
				'default' => '',
			],
			'municipalities' => [
				'property' => 'municipalities',
				'type' => 'oneToMany',
				'label' => 'Municipality Settings',
				'description' => 'Default settings for specific municipalities',
				'keyThis' => 'id',
				'keyOther' => 'selfRegistrationFormId',
				'subObjectType' => 'SymphonySelfRegistrationMunicipalityValues',
				'structure' => $symphonySelfRegistrationMunicipalityValuesStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'hideInLists' => true,
				'permissions' => ['Manage Self Registration Municipalities'],
				'note' => "Add 'Other' to define settings when there is no match.",
				'additionalOneToManyActions' => [
					0 => [
						'text' => 'Populate from ILS',
						'onclick' => "AspenDiscovery.Admin.populateFromILS('symphony', 'municipalities');",
					],
				],
				'newCanDoAdditionalActions' => true,
			],
			'countyCodes' => [
				'property' => 'countyCodes',
				'type' => 'oneToMany',
				'label' => 'County Codes',
				'description' => 'County codes and full county names for lookup during self registration.',
				'keyThis' => 'id',
				'keyOther' => 'selfRegistrationFormId',
				'subObjectType' => 'SymphonySelfRegistrationCountyCodeValues',
				'structure' => $symphonySelfRegistrationCountyCodeValuesStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'hideInLists' => true,
				'permissions' => ['Manage Self Registration Municipalities'],
				'note' => "If left blank the county/county code will not be used for municipality matching.",
				'additionalOneToManyActions' => [
					0 => [
						'text' => 'Populate from ILS',
						'onclick' => "AspenDiscovery.Admin.populateFromILS('symphony', 'countyCodes');",
					],
				],
				'newCanDoAdditionalActions' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this self registration form',
				'values' => $libraryList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFields();
			$this->saveLibraries();
			$this->saveMunicipalities();
			$this->saveCountyCodes();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFields();
			$this->saveLibraries();
			$this->saveMunicipalities();
			$this->saveCountyCodes();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == 'fields') {
			return $this->getFields();
		} if ($name == "libraries") {
			return $this->getLibraries();
		} if ($name == 'municipalities') {
			return $this->getMunicipalities();
		} if ($name == 'countyCodes') {
			return $this->getCountyCodes();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'fields') {
			$this->_fields = $value;
		} if ($name == "libraries") {
			$this->_libraries = $value;
		} if ($name == "municipalities") {
			$this->_municipalities = $value;
		} if ($name == "countyCodes") {
			$this->_counties = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * @return SymphonySelfRegistrationMunicipalityValues[]|null
	 */
	public function getMunicipalities(): ?array {
		if (!isset($this->_municipalities) && $this->id) {
			$this->_municipalities = [];
			$municipality = new SymphonySelfRegistrationMunicipalityValues();
			$municipality->selfRegistrationFormId = $this->id;
			$municipality->orderBy('municipality');
			$municipality->find();
			while ($municipality->fetch()) {
				$this->_municipalities[$municipality->id] = clone($municipality);
			}
		}
		return $this->_municipalities;
	}

	/**
	 * @return SymphonySelfRegistrationCountyCodeValues[]|null
	 */
	public function getCountyCodes(): ?array {
		if (!isset($this->_counties) && $this->id) {
			$this->_counties = [];
			$countyCode = new SymphonySelfRegistrationCountyCodeValues();
			$countyCode->selfRegistrationFormId = $this->id;
			$countyCode->orderBy('countyCode');
			$countyCode->find();
			while ($countyCode->fetch()) {
				$this->_counties[$countyCode->id] = clone($countyCode);
			}
		}
		return $this->_counties;
	}

	public function getMunicipalitySettingsByNameAndType($name, $type = null, $county = null) : ?int {
		$normalizedName = preg_replace('/\s+/', '', $name);

		$municipalities = new SymphonySelfRegistrationMunicipalityValues();
		$municipalities->selfRegistrationFormId = $this->id;

		if ($county !== null) {
			$normalizedCounty = preg_replace('/\s+/', '', $county);
			$countyCode = new SymphonySelfRegistrationCountyCodeValues();
			$countyCode->whereAdd("UPPER(REPLACE(countyName, ' ', '')) = " . $countyCode->escape(strtoupper($normalizedCounty)));
			if ($countyCode->find(true) && strlen($countyCode->countyCode) == 2) {
				$municipalities->whereAdd("UPPER(LEFT(ilsMunicipality, LENGTH(ilsMunicipality) - 1)) = " . "UPPER(LEFT(" . $municipalities->escape(strtoupper($countyCode->countyCode . $normalizedName)) . ", LENGTH(ilsMunicipality) - 1))");
			} else {
				$municipalities->whereAdd("UPPER(LEFT(municipality, 7)) = " . $municipalities->escape(strtoupper(substr($normalizedName, 0, 7)))); //ILS imported values only go up to 7 char
			}
		} else {
			$municipalities->whereAdd("UPPER(LEFT(municipality, 7)) = " . $municipalities->escape(strtoupper(substr($normalizedName, 0, 7)))); //ILS imported values only go up to 7 char
		}

		if ($type) {
			$municipalities->municipalityType = $type;
		}
		if ($municipalities->find(true)) {
			return $municipalities->id;
		}
		return null;
	}

	public function saveMunicipalities() : void {
		if (isset ($this->_municipalities) && is_array($this->_municipalities)) {
			$this->saveOneToManyOptions($this->_municipalities, 'selfRegistrationFormId');
			unset($this->_municipalities);
		}
	}

	public function saveCountyCodes() : void {
		if (isset ($this->_counties) && is_array($this->_counties)) {
			$this->saveOneToManyOptions($this->_counties, 'selfRegistrationFormId');
			unset($this->_counties);
		}
	}

	/** @return ?SelfRegistrationFormValues[] */
	public function getFields(): ?array {
		if (!isset($this->_fields) && $this->id) {
			$this->_fields = [];
			$field = new SelfRegistrationFormValues();
			$field->selfRegistrationFormId = $this->id;
			$field->orderBy('weight');
			$field->find();
			while ($field->fetch()) {
				$this->_fields[$field->id] = clone($field);
			}
		}
		return $this->_fields;
	}

	public function saveFields() : void {
		if (isset ($this->_fields) && is_array($this->_fields)) {
			$this->saveOneToManyOptions($this->_fields, 'selfRegistrationFormId');
			unset($this->fields);
		}
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new Library();
			$library->selfRegistrationFormId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//only update libraries in _libraries - unselected libraries will not have any fields other than selfRegistrationFormId updated
					if ($library->selfRegistrationFormId != $this->id) {
						$library->selfRegistrationFormId = $this->id;
						$library->update();
					}
				} else {
					if ($library->selfRegistrationFormId == $this->id) {
						$library->selfRegistrationFormId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function loadCopyableSubObjects() : void {
		$this->getFields();
		$index = -1;
		foreach ($this->_fields as $subObject) {
			$subObject->id = $index;
			$subObject->selfRegistrationFormId = $this->id;
			$index--;
		}
	}
}