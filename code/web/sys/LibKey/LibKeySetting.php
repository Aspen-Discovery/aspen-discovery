<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibKeySetting extends DataObject {
	public $__table = 'libkey_settings';
	public $id;
	public $name;
	public $libraryId;
	public $apiKey;

	private $_libraries;

	function getEncryptedFieldNames(): array {
		return ['apiKey'];
	}

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
				'maxLength' => 50,
				'description' => 'A name for these settings',
				'required' => true,
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'text',
				'label' => 'Library ID',
				'maxLength' => 50,
				'description' => 'Your LibKey library Id',
				'required' => true,
			],
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'storedPassword',
				'label' => 'Api Key',
				'description' => 'Your LibKey API Key.',
				'hideInLists' => true,
				'required' => false,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this setting',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->libKeySettingId = $this->id;
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
					if ($library->libKeySettingId != $this->id) {
						$library->finePaymentType = 2;
						$library->libKeySettingId = $this->id;
						$library->update();
					}
				} else {
					if ($library->libKeySettingId == $this->id) {
						if ($library->finePaymentType == 2) {
							$library->finePaymentType = 0;
						}
						$library->libKeySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}