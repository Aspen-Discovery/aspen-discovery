<?php


class PayPalPayflowSetting extends DataObject {
	public $__table = 'paypal_payflow_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $partner;
	public $vendor;
	public $user;
	public $password;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
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
			'sandboxMode' => [
				'property' => 'sandboxMode',
				'type' => 'checkbox',
				'label' => 'Use PayPal Payflow Sandbox',
				'description' => 'Whether or not to use PayPal Payflow in Sandbox mode',
				'note' => 'This is for testing only! No funds will be received by the library when this box is checked.',
			],
			'partner' => [
				'property' => 'partner',
				'type' => 'text',
				'label' => 'Partner',
				'description' => 'The Client ID to use when paying fines.',
				'default' => '',
				'size' => 72,
			],
			'vendor' => [
				'property' => 'vendor',
				'type' => 'text',
				'label' => 'Vendor',
				'description' => 'The Client ID to use when paying fines.',
				'default' => '',
				'size' => 72,
			],
			'user' => [
				'property' => 'user',
				'type' => 'text',
				'label' => 'User',
				'description' => 'The Client ID to use when paying fines.',
				'hideInLists' => true,
				'default' => '',
				'size' => 72,
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Password',
				'description' => 'The Client Secret to use when paying fines.',
				'hideInLists' => true,
				'default' => '',
				'size' => 72,
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
		return ['sandboxMode'];
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->paypalPayflowSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert($context = '') {
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
					if ($library->paypalPropaySettingId != $this->id) {
						$library->finePaymentType = 11;
						$library->paypalPayflowSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->paypalPayflowSettingId == $this->id) {
						if ($library->finePaymentType == 11) {
							$library->finePaymentType = 0;
						}
						$library->paypalPayflowSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public static function parsePayflowString(string $str) {
		$out = [];
		while(strlen($str) > 0) {
			$loc = strpos($str, '=');
			if($loc === FALSE) {
				$str = "";
				continue;
			}

			$subStr = substr($str, 0, $loc);
			$str = substr($str, $loc + 1);

			if(preg_match('/^(\w+)\[(\d+)]$/', $subStr, $matches)) {
				$count = intval($matches[2]);
				$out[$matches[1]] = substr($str, 0, $count);
				$str = substr($str, $count + 1);
			} else {
				$count = strpos($str, '&');
				if ($count === FALSE) {
					$out[$subStr] = $str;
					$str = '';
				} else {
					$out[$subStr] = substr($str, 0, $count);
					$str = substr($str, $count + 1);
				}
			}
		}
		return $out;
	}
}