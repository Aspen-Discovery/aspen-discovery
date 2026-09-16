<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Omeka/OmekaScope.php';

class OmekaSetting extends DataObject {
	public $__table = 'omeka_settings';
	public $id;
	public $name;
	public $baseUrl;
	public $siteSlug;
	public $apiKeyIdentity;
	public $apiKeyCredential;
	public $runFullUpdate;
	/** @noinspection PhpUnused */
	public $lastUpdateOfChangedRecords;
	/** @noinspection PhpUnused */
	public $lastUpdateOfAllRecords;

	private $_scopes;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$omekaScopeStructure = OmekaScope::getObjectStructure($context);
		unset($omekaScopeStructure['settingId']);

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
				'description' => 'The name for the setting',
				'maxLength' => 100,
				'required' => true,
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'url',
				'label' => 'Base URL',
				'description' => 'The URL of the Omeka S server, without the /api suffix',
				'maxLength' => 255,
				'required' => true,
			],
			'siteSlug' => [
				'property' => 'siteSlug',
				'type' => 'text',
				'label' => 'Site Slug',
				'description' => 'The slug of the Omeka S site used to build public links to items',
				'maxLength' => 100,
				'required' => true,
			],
			'apiKeyIdentity' => [
				'property' => 'apiKeyIdentity',
				'type' => 'text',
				'label' => 'API Key Identity',
				'description' => 'The key_identity value for the Omeka S API. Leave blank for anonymous access to public items.',
				'maxLength' => 255,
			],
			'apiKeyCredential' => [
				'property' => 'apiKeyCredential',
				'type' => 'storedPassword',
				'label' => 'API Key Credential',
				'description' => 'The key_credential value for the Omeka S API. Leave blank for anonymous access to public items.',
				'maxLength' => 255,
				'hideInLists' => true,
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],

			'lastUpdateOfChangedRecords' => [
				'property' => 'lastUpdateOfChangedRecords',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllRecords' => [
				'property' => 'lastUpdateOfAllRecords',
				'type' => 'timestamp',
				'label' => 'Last Update of All Records',
				'description' => 'The timestamp when all records were loaded',
				'default' => 0,
			],

			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'OmekaScope',
				'structure' => $omekaScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'additionalOneToManyActions' => [],
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __toString() {
		return "$this->name ($this->baseUrl)";
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new OmekaScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
				$allScope->includeAllItemSets = 1;

				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$deletingSingleSetting = !$useWhere && !empty($this->id);
		if ($deletingSingleSetting) {
			$scope = new OmekaScope();
			$scope->settingId = $this->id;
			$scope->find();
			while ($scope->fetch()) {
				$scope->delete();
			}
		}
		return parent::delete($useWhere, $hardDelete);
	}

	public function saveScopes() : void {
		if (isset($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new OmekaScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} else {
			parent::__set($name, $value);
		}
	}
}
