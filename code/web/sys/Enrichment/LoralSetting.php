<?php /** @noinspection PhpMissingFieldTypeInspection */

class LoralSetting extends DataObject {
	public $__table = 'loral_settings';    // table name
	public $id;
	public $name;
	public $loralUrl;
	public $loralId;
	public $password;
	public $enabled;

	private $_libraries;

	static $_objectStructure = [];

	public function getEncryptedFieldNames() : array {
		return [
			'password',
		];
	}

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
				'description' => 'A Name for the Loral Subscription for internal use',
				'maxlength' => 255,
				'required' => true,
			],
			'loralUrl' => [
				'property' => 'loralUrl',
				'type' => 'url',
				'label' => 'Loral Url',
				'description' => 'The Base URL of the Loral Server',
				'maxLength' => 255,
			],
			'loralId' => [
				'property' => 'loralId',
				'type' => 'text',
				'label' => 'Loral Id',
				'description' => 'The ID of the Loral subscription',
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Loral Password',
				'description' => 'The password for accessing the API',
				'hideInLists' => true,
				'maxLength' => 50,
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Integration Enabled',
				'description' => 'Whether Loral integration is enabled',
				'default' => 1,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that can use these settings',
				'values' => $libraryList,
				'hideInLists' => false,
				'forcesReindex' => true,
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
				$obj->loralSettingId = $this->id;
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

	public function update(string $context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void{
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->loralSettingId != $this->id) {
						$library->loralSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->loralSettingId == $this->id) {
						$library->loralSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}