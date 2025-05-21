<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';

class CloudLibrarySetting extends DataObject {
	public $__table = 'cloud_library_settings';    // table name
	public $id;
	public $name;
	public $apiUrl;
	public $userInterfaceUrl;
	public $libraryId;
	public $accountId;
	public $accountKey;
	public $runFullUpdate;
	public $useAlternateLibraryCard;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	private $_scopes;

	public static function getObjectStructure($context = ''): array {
		$cloudLibraryScopeStructure = CloudLibraryScope::getObjectStructure($context);
		unset($cloudLibraryScopeStructure['settingId']);
		return [
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
				'description' => 'A name for the setting to distinguish it from others when many are defined in an instance.',
				'maxLength' => 100
			],
			'apiUrl' => [
				'property' => 'apiUrl',
				'type' => 'url',
				'label' => 'API URL',
				'description' => 'The URL to the API',
			],
			'userInterfaceUrl' => [
				'property' => 'userInterfaceUrl',
				'type' => 'url',
				'label' => 'User Interface URL',
				'description' => 'The URL where the Patron can access the catalog',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'text',
				'label' => 'Library Id',
				'description' => 'The library id provided by cloudLibrary',
			],
			'accountId' => [
				'property' => 'accountId',
				'type' => 'text',
				'label' => 'Account Id',
				'description' => 'The Account Id provided by cloudLibrary when registering',
			],
			'accountKey' => [
				'property' => 'accountKey',
				'type' => 'storedPassword',
				'label' => 'API Token',
				'hideInLists' => true,
				'description' => 'The Account Key provided by cloudLibrary when registering',
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'useAlternateLibraryCard' => [
				'property' => 'useAlternateLibraryCard',
				'type' => 'checkbox',
				'label' => 'Use Alternate Library Card',
				'description' => 'Whether or not to use the alternate library card (for example, a state library card), for cloudLibrary checkouts',
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
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'CloudLibraryScope',
				'structure' => $cloudLibraryScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'additionalOneToManyActions' => [],
			],
		];
	}

	public function __toString() {
		return $this->libraryId . " - " . $this->userInterfaceUrl;
	}

	/**
	 * @return int|bool
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new CloudLibraryScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
		}
		return $ret;
	}

	public function saveScopes() {
		if (isset ($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new CloudLibraryScope();
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