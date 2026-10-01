<?php /** @noinspection PhpMissingFieldTypeInspection */


class CompriseSetting extends DataObject {
	public $__table = 'comprise_settings';
	public $id;
	public $customerName;
	public $customerId;
	public $username;
	public $password;

	public $customerNameForDonation;
	public $customerIdForDonation;

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
			'customerName' => [
				'property' => 'customerName',
				'type' => 'text',
				'label' => 'Customer Name',
				'description' => 'The Customer Name assigned by Comprise',
			],
			'customerId' => [
				'property' => 'customerId',
				'type' => 'integer',
				'label' => 'Customer Id',
				'description' => 'The Customer Id to use with the API',
			],
			'username' => [
				'property' => 'username',
				'type' => 'text',
				'label' => 'User Name',
				'description' => 'The User Name assigned by Comprise',
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Password',
				'description' => 'The Password assigned by Comprise',
				'hideInLists' => true,
			],
			'donationSettings' => [
				'property' => 'donationSettings',
				'type' => 'section',
				'label' => 'Donation Settings (Optional)',
				'description' => 'Configure Customer Name and Id for donation transactions. These fields are optional.',
				'hideInLists' => true,
				'properties' => [
					'customerNameForDonation' => [
						'property' => 'customerNameForDonation',
						'type' => 'text',
						'label' => 'Customer Name for Donation (Optional)',
						'description' => 'The Customer Name for the donation form (Optional)',
						'note' => 'This is optional. Only use it if you want to separate the donation transactions from the fine transactions in Comprise.',
					],
					'customerIdForDonation' => [
						'property' => 'customerIdForDonation',
						'type' => 'integer',
						'label' => 'Customer Id for Donation (Optional)',
						'description' => 'The Customer Id for the donation form (Optional)',
						'note' => 'This is optional. Only use it if you want to separate the donation transactions from the fine transactions in Comprise.',
					],
				]
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

	function getEncryptedFieldNames(): array {
		return ['password'];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->compriseSettingId = $this->id;
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
					if ($library->compriseSettingId != $this->id) {
						$library->finePaymentType = 4;
						$library->compriseSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->compriseSettingId == $this->id) {
						if ($library->finePaymentType == 4) {
							$library->finePaymentType = 0;
						}
						$library->compriseSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}