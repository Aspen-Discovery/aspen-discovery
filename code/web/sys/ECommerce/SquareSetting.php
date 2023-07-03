<?php


class SquareSetting extends DataObject {
	public $__table = 'square_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $applicationId;
	public $accessToken;
	public $locationId;

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
				'label' => 'Use Square Sandbox (for testing payments only, does not collect money)',
				'description' => 'Whether or not to use Square in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'applicationId' => [
				'property' => 'applicationId',
				'type' => 'text',
				'label' => 'Application ID',
				'description' => 'The Application ID to use when paying fines with Square.',
				'hideInLists' => false,
				'default' => '',
				'size' => 80,
			],
			'accessToken' => [
				'property' => 'accessToken',
				'type' => 'storedPassword',
				'label' => 'Access Token',
				'description' => 'The Access Token to use when paying fines with Square.',
				'hideInLists' => true,
				'default' => '',
				'size' => 80,
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'text',
				'label' => 'Location ID',
				'description' => 'The Location ID to use when paying fines with Square.',
				'hideInLists' => false,
				'default' => '',
				'size' => 80,
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
				$obj->squareSettingId = $this->id;
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
					if ($library->squareSettingId != $this->id) {
						$library->finePaymentType = 12;
						$library->squareSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->squareSettingId == $this->id) {
						if ($library->finePaymentType == 12) {
							$library->finePaymentType = 0;
						}
						$library->squareSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}