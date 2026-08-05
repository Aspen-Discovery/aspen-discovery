<?php /** @noinspection PhpMissingFieldTypeInspection */

class BDSSetting extends DataObject {
	public $__table = 'bds_settings';    // table name
	public $id;
	public $name;
	public $dbmCode;
	public $enabled;

	private $_libraries;

	static $_objectStructure = [];

	public function getEncryptedFieldNames() : array {
		return [
			'dbmCode',
		];
	}

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
				'description' => 'A Name for the BDS Subscription for internal use',
				'maxlength' => 255,
				'required' => true,
			],
			'dbmCode' => [
				'property' => 'dbmCode',
				'type' => 'storedPassword',
				'label' => 'BDS DBM Code',
				'description' => 'The customer-issued BDS DBM code used to authenticate cover image requests.',
				'hideInLists' => true,
				'maxLength' => 50,
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Integration Enabled',
				'description' => 'Whether BDS cover image integration is enabled',
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
				'forcesReindex' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name !== "libraries") {
			return parent::__get($name);
		}
		$needsLoad = !isset($this->_libraries) && $this->id;
		if (!$needsLoad) {
			return $this->_libraries ?? null;
		}
		$this->_libraries = [];
		$obj = new Library();
		$obj->bdsSettingId = $this->id;
		$obj->find();
		while ($obj->fetch()) {
			$this->_libraries[$obj->libraryId] = $obj->libraryId;
		}
		return $this->_libraries;
	}

	public function __set($name, $value) {
		if ($name !== "libraries") {
			parent::__set($name, $value);
			return;
		}
		$this->_libraries = $value;
	}

	public function update(string $context = '') : bool|int {
		$ret = parent::update();
		if ($ret === FALSE) {
			return $ret;
		}
		$this->saveLibraries();
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret === FALSE) {
			return $ret;
		}
		$this->saveLibraries();
		return $ret;
	}

	public function saveLibraries() : void {
		$hasPendingChanges = isset($this->_libraries) && is_array($this->_libraries);
		if (!$hasPendingChanges) {
			return;
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		foreach ($libraryList as $libraryId => $displayName) {
			$library = new Library();
			$library->libraryId = $libraryId;
			$library->find(true);
			$needsLink = in_array($libraryId, $this->_libraries) && $library->bdsSettingId != $this->id;
			$needsUnlink = !in_array($libraryId, $this->_libraries) && $library->bdsSettingId == $this->id;
			if ($needsLink) {
				$library->bdsSettingId = $this->id;
				$library->update();
			} elseif ($needsUnlink) {
				$library->bdsSettingId = -1;
				$library->update();
			}
		}
		unset($this->_libraries);
	}
}
