<?php /** @noinspection PhpMissingFieldTypeInspection */

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
	public $forceDebugLog;

	private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
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
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
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