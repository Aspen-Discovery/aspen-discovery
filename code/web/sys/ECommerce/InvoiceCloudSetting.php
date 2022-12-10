<?php

/**
 * Class InvoiceCloudSetting - Store settings for InvoiceCloud
 */
class InvoiceCloudSetting extends DataObject {
	public $__table = 'invoice_cloud_settings';
	public $id;
	public $name;
	public $apiKey;
	public $invoiceTypeId;
	public $ccServiceFee;

	private $_libraries;

	function getNumericColumnNames(): array {
		return ['invoiceTypeId'];
	}

	static function getObjectStructure(): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'text',
				'label' => 'Application API Key',
				'description' => 'The API Key generated in the Invoice Cloud Biller Portal',
				'hideInLists' => true,
				'maxLength' => 500,
			],
			'invoiceTypeId' => [
				'property' => 'invoiceTypeId',
				'type' => 'text',
				'label' => 'Invoice Type ID',
				'description' => 'The invoice type ID',
				'hideInLists' => true,
				'maxLength' => 50,
			],
			'ccServiceFee' => [
				'property' => 'ccServiceFee',
				'type' => 'text',
				'label' => 'Credit Card Service Fee',
				'description' => 'The credit card service fee',
				'hideInLists' => true,
				'maxLength' => 50,
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
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->invoiceCloudSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
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
					if ($library->invoiceCloudSettingId != $this->id) {
						$library->finePaymentType = 9;
						$library->invoiceCloudSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->invoiceCloudSettingId == $this->id) {
						if ($library->finePaymentType == 9) {
							$library->finePaymentType = 0;
						}
						$library->invoiceCloudSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}