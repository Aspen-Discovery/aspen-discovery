<?php

/**
 * Class WorldPaySetting - Store settings for FIS WorldPay
 */
class WorldPaySetting extends DataObject {
	public $__table = 'worldpay_settings';
	public $id;
	public $name;
	public $merchantCode;
	public $settleCode;
	public $paymentSite;
	public $useLineItems;

	private $_libraries;

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
			'merchantCode' => [
				'property' => 'merchantCode',
				'type' => 'text',
				'label' => 'Merchant Code',
				'description' => 'The Merchant Code provided by FIS',
				'maxLength' => 20,
			],
			'settleCode' => [
				'property' => 'settleCode',
				'type' => 'text',
				'label' => 'Settle Code',
				'description' => 'The Settle Code provided by FIS',
				'maxLength' => 20,
			],
			'paymentSite' => [
				'property' => 'paymentSite',
				'type' => 'text',
				'label' => 'Payment Site URL',
				'description' => 'The Payment Site URL provided by FIS',
				'maxLength' => 255,
			],
			'useLineItems' => [
				'property' => 'useLineItems',
				'type' => 'checkbox',
				'label' => 'Send Line Items',
				'description' => 'Whether or not to send Line Items to FIS',
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

	function getNumericColumnNames(): array {
		return ['customerId'];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->worldPaySettingId = $this->id;
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
		if ($name == "libraries") {
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
					if ($library->worldPaySettingId != $this->id) {
						$library->finePaymentType = 6;
						$library->worldPaySettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->worldPaySettingId == $this->id) {
						if ($library->finePaymentType == 6) {
							$library->finePaymentType = 0;
						}
						$library->worldPaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}