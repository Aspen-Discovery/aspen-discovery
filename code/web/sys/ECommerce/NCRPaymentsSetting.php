<?php /** @noinspection PhpMissingFieldTypeInspection */

class NCRPaymentsSetting extends DataObject {
	public $__table = 'ncr_payments_settings';
	public $id;
	public $name;
	public $clientKey;
	public $webKey;
	public $paymentTypeId;
	public $lastTransactionNumber;
	/** @noinspection PhpUnused */
	public $forceDebugLog;
	private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
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
			'clientKey' => [
				'property' => 'clientKey',
				'type' => 'text',
				'label' => 'Client Key',
				'hideInLists' => true,
				'default' => '',
				'maxLength' => 500,
			],
			'webKey' => [
				'property' => 'webKey',
				'type' => 'text',
				'label' => 'Web Key',
				'hideInLists' => true,
				'default' => '',
				'maxLength' => 500,
			],
			'paymentTypeId' => [
				'property' => 'paymentTypeId',
				'type' => 'text',
				'label' => 'Payment Type ID',
				'description' => 'Same as API number',
				'hideInLists' => false,
				'default' => '0',
				'maxLength' => 1,
			],
			'forceDebugLog' => [
				'property' => 'forceDebugLog',
				'type' => 'checkbox',
				'label' => 'Force Debugging Logs',
				'description' => 'Whether or not to allow users to get debugging information about payments either if the user IP is authorized or not',
				'hideInLists' => false,
				'default' => false,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => false,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->ncrSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->ncrSettingId != $this->id) {
						$library->finePaymentType = 14;
						$library->ncrSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->ncrSettingId == $this->id) {
						if ($library->finePaymentType == 14) {
							$library->finePaymentType = 0;
						}
						$library->ncrSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}