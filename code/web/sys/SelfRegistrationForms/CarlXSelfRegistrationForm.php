<?php

class CarlXSelfRegistrationForm extends DataObject {
	public $__table = 'self_registration_form_carlx';
	public $id;
	public $name;
	public $selfRegEmailNotices;
	public $selfRegDefaultBranch;
	public $selfRegPatronExpirationDate;
	public $selfRegPatronStatusCode;
	public $selfRegPatronType;
	public $selfRegRegBranch;
	public $selfRegRegisteredBy;
	public $lastPatronBarcode;
	public $barcodePrefix;
	public $selfRegIDNumberLength;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
			'selfRegEmailNotices' => [
				'property' => 'selfRegEmailNotices',
				'type' => 'text',
				'label' => 'Email Notices',
				'description' => 'What the default setting for Email Notices is in the ILS',
				'default' => 'DO_NOT_SEND_EMAIL',
			],
			'selfRegDefaultBranch' => [
				'property' => 'selfRegDefaultBranch',
				'type' => 'text',
				'label' => 'Default Branch',
				'description' => 'The default branch for self registration.',
			],
			'selfRegPatronExpirationDate' => [
				'property' => 'selfRegPatronExpirationDate',
				'type' => 'date',
				'label' => 'Self Registration Expiration Date',
				'description' => 'Expiration date for self registered patrons.',
			],
			'selfRegPatronStatusCode' => [
				'property' => 'selfRegPatronStatusCode',
				'type' => 'text',
				'label' => 'Self Registration Status Code',
				'description' => 'Status code for registering patron.',
				'hideInLists' => true,
				'default' => 'GOOD',
			],
			'selfRegPatronType' => [
				'property' => 'selfRegPatronType',
				'type' => 'text',
				'label' => 'Patron Type',
				'description' => 'The patron type for self registering patrons',
				'hideInLists' => true,
				'default' => 'SELFREG',
			],
			'selfRegRegisteredBy' => [
				'property' => 'selfRegRegisteredBy',
				'type' => 'text',
				'label' => 'Self Registered By',
				'description' => 'Self registered by',
				'hideInLists' => true,
			],
			'lastPatronBarcode' => [
				'property' => 'lastPatronBarcode',
				'type' => 'integer',
				'label' => 'Last Patron Barcode',
				'description' => 'Barcode of last registered patron (will update after each new self-registered patron)',
				'hideInLists' => true,
			],
			'barcodePrefix' => [
				'property' => 'barcodePrefix',
				'type' => 'text',
				'label' => 'Barcode Prefix',
				'description' => 'Barcode Prefix',
				'hideInLists' => true,
			],
			'selfRegIDNumberLength' => [
				'property' => 'selfRegIDNumberLength',
				'type' => 'text',
				'label' => 'Self Reg Barcode Number Length',
				'description' => 'Self Reg Barcode Number Length',
				'hideInLists' => true,
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
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
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
}