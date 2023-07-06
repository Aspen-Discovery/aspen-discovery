<?php


class CertifiedPaymentsByDeluxeSetting extends DataObject {
	public $__table = 'deluxe_certified_payments_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $applicationId;

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
				'label' => 'Enable Sandbox for Testing',
				'description' => 'Whether or not to use sandbox mode to test payments',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'applicationId' => [
				'property' => 'applicationId',
				'type' => 'text',
				'label' => 'Application Id',
				'hideInLists' => true,
				'default' => '',
				'size' => 500,
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
				$obj->deluxeCertifiedPaymentsSettingId = $this->id;
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
					if ($library->deluxeCertifiedPaymentsSettingId != $this->id) {
						$library->finePaymentType = 10;
						$library->deluxeCertifiedPaymentsSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->deluxeCertifiedPaymentsSettingId == $this->id) {
						if ($library->finePaymentType == 10) {
							$library->finePaymentType = 0;
						}
						$library->deluxeCertifiedPaymentsSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	/**
	 * @param $code
	 * @param string $message
	 * @return string
	 */
	public static function getFailedPaymentMessage($code, string $message): string {
		if($code == null) {
			$message .= 'Unknown Error';
		} elseif ($code == 1) {
			$message .= 'General platform system error from vendor';
		} elseif ($code == 3) {
			$message .= 'Duplicate transaction';
		} elseif ($code == 5) {
			$message .= 'Invalid transaction type';
		} elseif ($code == 100) {
			$message .= 'AVS initiated void';
		} elseif ($code == 101) {
			$message .= 'Card type not valid';
		} elseif ($code == 102) {
			$message .= 'Card expired';
		} elseif ($code == 103) {
			$message .= 'Card number not valid';
		} elseif ($code == 104) {
			$message .= 'Voice authorization requested (Call)';
		} elseif ($code == 105) {
			$message .= 'Processor reported error';
		} elseif ($code == 106) {
			$message .= 'Card declined';
		} elseif ($code == 108) {
			$message .= 'Card flagged by processor';
		} elseif ($code == 109) {
			$message .= 'Remittance ID is on administrative hold';
		} elseif ($code == 116) {
			$message .= 'Routing transit number invalid';
		} elseif ($code == 118) {
			$message .= 'Convenience fee transaction failure';
		} elseif ($code == 200) {
			$message .= 'Unknown Error';
		} else {
			$message .= 'Unknown Error';
		}
		return $message;
	}
}