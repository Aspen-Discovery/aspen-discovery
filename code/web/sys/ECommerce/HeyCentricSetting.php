<?php


class HeyCentricSetting extends DataObject {
	public $__table = 'heycentric_setting';
	public $id;
	public $name;
	public $showPayLater;
    public $baseUrl;
    public $client;
	public $privateKey;
    public $area;
    public $till;
    public $entity;
    public $rurl;

	private $_libraries;

	public function getEncryptedFieldNames(): array {
		return ['privateKey'];
	}

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
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'text',
				'hideInLists' => true,
				'label' => 'HeyCentric base URL',
				'description' => 'The base URL that links to the HeyCentric platform where patrons can make payments',
				'maxLength' => 50,
			],
			'privateKey' => [
				'property' => 'privateKey',
				'hideInLists' => true,
				'type' => 'storedPassword',
				'label' => 'HeyCentric Private Key',
				'description' => 'The HeyCentric Private Key for your site',
				'maxLength' => 50,
			],
			'client' => [
				'property' => 'client',
				'type' => 'text',
				'label' => 'Client',
				'description' => '',
				'maxLength' => 10,
			],
			'entity' => [
				'property' => 'entity',
				'type' => 'text',
				'label' => 'Entity',
				'description' => '',
				'maxLength' => 10,
			],
			'till' => [
				'property' => 'till',
				'type' => 'text',
				'label' => 'Till',
				'description' => '',
				'maxLength' => 10,
			],
			'area' => [
				'property' => 'area',
				'type' => 'text',
				'label' => 'Area',
				'description' => '',
				'maxLength' => 50,
			],
			'rurl' => [
				'property' => 'rurl',
				'type' => 'hidden',
				'hideInLists' => true,
				'label' => 'Return URL',
				'description' => 'the URL to return the patron to once the payment has been processed',
				'maxLength' => 50,
				'default' => ROOT_DIR . '/MyAccount/AJAX?method=completeHeyCentricOrder'
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
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->heyCentricSettingId = $this->id;
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
					if ($library->heyCentricSettingId != $this->id) {
						$library->finePaymentType = 16;
						$library->heyCentricSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->heyCentricSettingId == $this->id) {
						if ($library->finePaymentType == 16) {
							$library->finePaymentType = 0;
						}
						$library->heyCentricSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}