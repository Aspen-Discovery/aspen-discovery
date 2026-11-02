<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxScope.php';
require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxSettings.php';

class BorrowBoxSetting extends DataObject {
	public $__table = 'borrowbox_settings';
	public $id;
	public $name;
	public $apiUrl;
	public $apiUsername;
	public $apiPassword;
	public $runFullUpdate;
	/** @noinspection PhpUnused */
	public $allowLargeDeletes;
	/** @noinspection PhpUnused */
	public $numRetriesOnError;
	/** @noinspection PhpUnused */
	public $productsToUpdate;
	/** @noinspection PhpUnused */
	public $lastUpdateOfChangedRecords;
	/** @noinspection PhpUnused */
	public $lastUpdateOfAllRecords;
	/** @noinspection PhpUnused */
	public $enableRequestLogging;

	public $_scopes;
	public $_librarySettings;

	public function getEncryptedFieldNames(): array {
		return ['apiPassword'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$borrowBoxScopeStructure = BorrowBoxScope::getObjectStructure($context);
		unset($borrowBoxScopeStructure['settingId']);

		$libraryBorrowBoxSettingsStructure = LibraryBorrowBoxSettings::getObjectStructure($context);
		unset($libraryBorrowBoxSettingsStructure['settingId']);
		unset($libraryBorrowBoxSettingsStructure['weight']);

		$objectStructure = [
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
				'description' => 'The name to be shown to patrons to identify the BorrowBox collection.',
				'default' => 'BorrowBox',
				'maxLength' => 125,
				'canBatchUpdate' => false,
			],
			'apiUrl' => [
				'property' => 'apiUrl',
				'type' => 'url',
				'label' => 'API URL',
				'description' => 'The base URL for the BorrowBox API',
				'default' => 'https://api.borrowbox.net',
				'canBatchUpdate' => false,
			],
			'apiUsername' => [
				'property' => 'apiUsername',
				'type' => 'text',
				'label' => 'API Username',
				'description' => 'The username for BorrowBox API Basic authentication',
				'canBatchUpdate' => false,
			],
			'apiPassword' => [
				'property' => 'apiPassword',
				'type' => 'storedPassword',
				'label' => 'API Password',
				'description' => 'The password/secret for BorrowBox API Basic authentication',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'allowLargeDeletes' => [
				'property' => 'allowLargeDeletes',
				'type' => 'checkbox',
				'label' => 'Allow Large Deletes',
				'description' => 'Whether or not Aspen can delete more than 500 records or 5% of the collection',
				'default' => 1,
			],
			'numRetriesOnError' => [
				'property' => 'numRetriesOnError',
				'type' => 'integer',
				'label' => 'Num Retries',
				'description' => 'The number of retries to attempt when errors are returned from BorrowBox',
				'canBatchUpdate' => false,
				'default' => 1,
				'min' => 0,
				'max' => 5,
			],
			'productsToUpdate' => [
				'property' => 'productsToUpdate',
				'type' => 'textarea',
				'label' => 'Products To Reindex',
				'description' => 'A list of products to update on the next index',
				'canBatchUpdate' => false,
				'hideInLists' => true,
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
			'enableRequestLogging' => [
				'property' => 'enableRequestLogging',
				'type' => 'checkbox',
				'label' => 'Enable Request Logging',
				'description' => 'Whether or not request logging is done while extracting from BorrowBox.',
				'default' => 0,
			],
			'librarySettingsSection' => [
				'property' => 'librarySettingsSection',
				'type' => 'section',
				'label' => 'Library Settings',
				'expandByDefault' => true,
				'properties' => [
					'librarySettings' => [
						'property' => 'librarySettings',
						'type' => 'oneToMany',
						'label' => '',
						'description' => '',
						'note' => 'Define per-library settings including the BorrowBox Site ID for each library that uses this collection.',
						'keyThis' => 'id',
						'keyOther' => 'settingId',
						'subObjectType' => 'LibraryBorrowBoxSettings',
						'structure' => $libraryBorrowBoxSettingsStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'additionalOneToManyActions' => [],
						'canAddNew' => true,
						'canDelete' => true,
					],
				]
			],

			'scopesSection' => [
				'property' => 'librarySettingsSection',
				'type' => 'section',
				'label' => 'Scopes',
				'expandByDefault' => true,
				'properties' => [
					'scopes' => [
						'property' => 'scopes',
						'type' => 'oneToMany',
						'label' => '',
						'description' => '',
						'note' => 'Define the records to include for each library and location that uses this collection',
						'keyThis' => 'id',
						'keyOther' => 'settingId',
						'subObjectType' => 'BorrowBoxScope',
						'structure' => $borrowBoxScopeStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => true,
						'additionalOneToManyActions' => [],
						'canAddNew' => true,
						'canDelete' => true,
					],
				],
			],
		];
		$isAspenAdmin = UserAccount::isLoggedIn() && UserAccount::getActiveUserObj()->isAspenAdminUser();
		if (!$isAspenAdmin) {
			unset($objectStructure['enableRequestLogging']);
		}

		self::$_objectStructure[$context] = $objectStructure;
		return self::$_objectStructure[$context];
	}

	public function __toString() {
		return "$this->name ($this->apiUrl)";
	}

	public function update(string $context = '') : int|bool {
		$this->apiUrl = rtrim($this->apiUrl, '/');
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
			$this->saveLibrarySettings();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$this->apiUrl = rtrim($this->apiUrl, '/');
		$ret = parent::insert();
		if ($ret === FALSE) {
			return $ret;
		}
		if (empty($this->_scopes)) {
			$this->_scopes = [];
			$allScope = new BorrowBoxScope();
			$allScope->settingId = $this->id;
			$allScope->name = "All Records";
			$allScope->includeAdult = true;
			$allScope->includeKids = true;
			$allScope->includeTeen = true;
			$this->_scopes[] = $allScope;
		}
		$this->saveScopes();
		$this->saveLibrarySettings();
		return $ret;
	}

	public function saveScopes() : void {
		$scopesLoaded = isset($this->_scopes) && is_array($this->_scopes);
		if (!$scopesLoaded) {
			return;
		}
		$this->saveOneToManyOptions($this->_scopes, 'settingId');
		unset($this->_scopes);
	}

	public function saveLibrarySettings() : void {
		$librarySettingsLoaded = isset($this->_librarySettings) && is_array($this->_librarySettings);
		if (!$librarySettingsLoaded) {
			return;
		}
		$this->saveOneToManyOptions($this->_librarySettings, 'settingId');
		unset($this->_librarySettings);
	}

	public function __get($name) {
		if ($name == "scopes") {
			$scopesNeedLoading = !isset($this->_scopes) && $this->id;
			if ($scopesNeedLoading) {
				$this->_scopes = [];
				$scope = new BorrowBoxScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} elseif ($name == "librarySettings") {
			$librarySettingsNeedLoading = !isset($this->_librarySettings) && $this->id;
			if ($librarySettingsNeedLoading) {
				$this->_librarySettings = [];
				$librarySetting = new LibraryBorrowBoxSettings();
				$librarySetting->settingId = $this->id;
				$librarySetting->find();
				while ($librarySetting->fetch()) {
					$this->_librarySettings[$librarySetting->id] = clone($librarySetting);
				}
			}
			return $this->_librarySettings;
		}else{
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} elseif ($name == "librarySettings") {
			$this->_librarySettings = $value;
		} else {
			parent::__set($name, $value);
		}
	}
}
