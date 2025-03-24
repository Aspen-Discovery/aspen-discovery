<?php
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationFormValues.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationTerms.php';

class SierraSelfRegistrationForm extends DataObject {
	public $__table = 'self_registration_form_sierra';
	public $id;
	public $name;
	public $selfRegistrationTemplate;
	public $termsOfServiceSetting;
	public $selfRegBarcodePrefix;
	public $selfRegBarcodeSuffixLength;
	public $selfRegExpirationDays;
	public $selfRegPatronType;
	public $selfRegTelephoneField;
	public $selfRegPcode1;
	public $selfRegPcode2;
	public $selfRegPcode3;
	public $selfRegPcode4;
	public $selfRegPatronMessage;
	public $selfRegNoticePref;
	public $selfRegAgency;
	public $selfRegGuardianField;
	public $selfRegEmailBarcode;


	private $_fields;
	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$selfRegistrationTerms = [];
		$selfRegistrationTOS = new SelfRegistrationTerms();
		$selfRegistrationTOS->find();
		$selfRegistrationTerms[-1] = 'None';
		while ($selfRegistrationTOS->fetch()) {
			$selfRegistrationTerms[$selfRegistrationTOS->id] = (string)$selfRegistrationTOS->name;
		}

		$fieldValuesStructure = SelfRegistrationFormValues::getObjectStructure($context);
		unset($fieldValuesStructure['weight']);
		unset($fieldValuesStructure['selfRegistrationFormId']);

		return [
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
			'selfRegistrationTemplate' => [
				'property' => 'selfRegistrationTemplate',
				'type' => 'text',
				'label' => 'Self Registration Template',
				'description' => 'The ILS template to use during self registration',
				'hideInLists' => true,
				'default' => 'default',
			],
			'selfRegBarcodePrefix' => [
				'property' => 'selfRegBarcodePrefix',
				'type' => 'text',
				'label' => 'Barcode Prefix',
				'description' => 'The prefix for the barcode',
				'maxLength' => 10,
			],
			'selfRegBarcodeSuffixLength' => [
				'property' => 'selfRegBarcodeSuffixLength',
				'type' => 'integer',
				'label' => 'Barcode Suffix Length',
				'description' => 'The length of the suffix for the barcode',
				'maxLength' => 2,
			],
			'selfRegPatronType' => [
				'property' => 'selfRegPatronType',
				'type' => 'integer',
				'label' => 'Patron Type',
				'description' => 'Patron type to use for self registered patrons',
				'maxLength' => 3,
			],
			'selfRegExpirationDays' => [
				'property' => 'selfRegExpirationDays',
				'type' => 'integer',
				'label' => 'Expiration Days',
				'description' => 'The number of days after which the patron will be expired',
				'maxLength' => 3,
				'default' => 30,
			],
			'selfRegTelephoneField' => [
				'property' => 'selfRegTelephoneField',
				'type' => 'enum',
				'label' => 'Primary Telephone Field',
				'description' => 'Define primary telephone field for self registered patrons',
				'hideInLists' => true,
				'values' => [
					't' => 'TELEPHONE',
					'p' => 'TELEPHONE2'
				],
				'default' => 't'
			],
			'selfRegPcode1' => [
				'property' => 'selfRegPcode1',
				'type' => 'text',
				'label' => 'Patron Code 1',
				'description' => 'pcode1 for self registered patrons',
				'maxLength' => 25,
			],
			'selfRegPcode2' => [
				'property' => 'selfRegPcode2',
				'type' => 'text',
				'label' => 'Patron Code 2',
				'description' => 'pcode2 for self registered patrons',
				'maxLength' => 25,
			],
			'selfRegPcode3' => [
				'property' => 'selfRegPcode3',
				'type' => 'integer',
				'label' => 'Patron Code 3',
				'description' => 'pcode3 for self registered patrons',
				'maxLength' => 3,
			],
			'selfRegPcode4' => [
				'property' => 'selfRegPcode4',
				'type' => 'integer',
				'label' => 'Patron Code 4',
				'description' => 'pcode4 for self registered patrons',
				'maxLength' => 3,
			],
			'selfRegPatronMessage' => [
				'property' => 'selfRegPatronMessage',
				'type' => 'text',
				'label' => 'Patron Message',
				'description' => 'Patron message to display to self registered patrons',
				'maxLength' => 35,
			],
			'selfRegNoticePref' => [
				'property' => 'selfRegNoticePref',
				'type' => 'enum',
				'label' => 'Notice Preference',
				'description' => 'Default notification preference for new patrons',
				'values' => [
					'-' => 'None',
					'z' => 'Email',
					'p' => 'Phone',
					't' => 'Text'
				],
				'default' => '-'
			],
			'selfRegAgency' => [
				'property' => 'selfRegAgency',
				'type' => 'integer',
				'label' => 'Patron Agency',
				'description' => 'Patron agency for self registered patrons',
				'hideInLists' => true,
				'maxLength' => 3,
			],
			'selfRegGuardianField' => [
				'property' => 'selfRegGuardianField',
				'type' => 'text',
				'label' => 'Guardian Field',
				'description' => 'Define guardian field for self registered patrons',
				'hideInLists' => true,
				'maxLength' => 10,
			],
			'selfRegEmailBarcode' => [
				'property' => 'selfRegEmailBarcode',
				'type' => 'checkbox',
				'label' => 'Use Email for Barcode',
				'description' => "Use user's email for their barcode",
				'hideInLists' => true,
				'default' => 0,
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
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFields();
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFields();
			$this->saveLibraries();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == 'fields') {
			return $this->getFields();
		} if ($name == 'libraries') {
			return $this->getLibraries();
		}else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'fields') {
			$this->_fields = $value;
		} if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/** @return SelfRegistrationFormValues[] */
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

	public function clearFields() {
		$this->clearOneToManyOptions('SelfRegistrationFormValues', 'selfRegistrationFormId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->fields = [];
	}

	public function saveFields() {
		if (isset ($this->_fields) && is_array($this->_fields)) {
			$this->saveOneToManyOptions($this->_fields, 'selfRegistrationFormId');
			unset($this->fields);
		}
	}

	public function getLibraries() {
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

	public function saveLibraries() {
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

	public function loadCopyableSubObjects() {
		$this->getFields();
		$index = -1;
		foreach ($this->_fields as $subObject) {
			$subObject->id = $index;
			$subObject->selfRegistrationFormId = $this->id;
			$index--;
		}
	}
}