<?php /** @noinspection PhpMissingFieldTypeInspection */


require_once ROOT_DIR . '/sys/Gale/GaleProductCode.php';

class GaleSetting extends DataObject {
	public $__table = 'gale_settings';
	public $id;
	public $name;
	public $locationId;
	public $fullTextOnly;

	private $_libraries;
	private $_productCodes;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$productCodeStructure = GaleProductCode::getObjectStructure($context);

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
			'locationId' => [
				'property' => 'locationId',
				'type' => 'text',
				'label' => 'Location ID',
				'description' => 'The Location ID to use for the Gale API',
				'hideInLists' => false,
			],
			'fullTextOnly' => [
				'property' => 'fullTextOnly',
				'type' => 'checkbox',
				'label' => 'Search for full text only',
				'description' => 'Whether or not to ONLY search for full text resouces from Gale',
				'hideInLists' => false,
			],
			'productCodes' => [
				'property' => 'productCodes',
				'type' => 'oneToMany',
				'label' => 'Product Codes',
				'description' => 'Product codes available for this Gale configuration.',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'GaleProductCode',
				'structure' => $productCodeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
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
				$obj->galeSettingsId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == 'productCodes') {
			return $this->getProductCodes();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == 'productCodes') {
			$this->_productCodes = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveProductCodes();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveProductCodes();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$library = new Library();
			$library->galeSettingsId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$library->galeSettingsId = -1;
				$library->update();
			}
			$this->clearOneToManyOptions('GaleProductCode', 'settingId');
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
					if ($library->galeSettingsId != $this->id) {
						$library->galeSettingsId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->galeSettingsId == $this->id) {
						$library->galeSettingsId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	/**
	 * @return GaleProductCode[]
	 */
	public function getProductCodes(): array {
		if (!isset($this->_productCodes)) {
			$this->_productCodes = [];
			if ($this->id) {
				$obj = new GaleProductCode();
				$obj->settingId = $this->id;
				$obj->orderBy('displayName');
				$obj->find();
				while ($obj->fetch()) {
					$this->_productCodes[$obj->id] = clone($obj);
				}
			}
		}
		return $this->_productCodes;
	}

	public function saveProductCodes(): void {
		if (isset($this->_productCodes) && is_array($this->_productCodes)) {
			$this->saveOneToManyOptions($this->_productCodes, 'settingId');
			unset($this->_productCodes);
		}
	}
}
