<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
require_once ROOT_DIR . '/sys/OverDrive/LibraryOverDriveSettings.php';

class OverDriveSetting extends DataObject {
	public $__table = 'overdrive_settings';    // table name
	public $id;
	public $name;
	public $readerName;
	public $url;
	public $patronApiUrl;
	public $clientSecret;
	public $clientKey;
	public $accountId;
	public $websiteId;
	public $productsKey;
	public $runFullUpdate;
	public $showLibbyPromo;
	/** @noinspection PhpUnused */
	public $allowLargeDeletes;
	/** @noinspection PhpUnused */
	public $numExtractionThreads;
	/** @noinspection PhpUnused */
	public $numRetriesOnError;
	/** @noinspection PhpUnused */
	public $productsToUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	/** @noinspection PhpUnused */
	public $enableRequestLogging;

	public $_scopes;
	public $_librarySettings;

	public function getEncryptedFieldNames(): array {
		return ['clientSecret'];
	}

	public static function getObjectStructure($context = ''): array {
		$overdriveScopeStructure = OverDriveScope::getObjectStructure($context);
		unset($overdriveScopeStructure['settingId']);

		$libraryOverDriveSettingsStructure = LibraryOverDriveSettings::getObjectStructure($context);
		unset($libraryOverDriveSettingsStructure['settingId']);
		unset($libraryOverDriveSettingsStructure['weight']);

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
				'description' => 'The name to be shown to patrons to identify the collection in Where Is It Popup and Copies View. I.e. Library Libby Collection.',
				'default' => 'Libby',
				'maxLength' => 125,
				'canBatchUpdate' => false,
			],
			'readerName' => [
				'property' => 'readerName',
				'type' => 'text',
				'label' => 'Reader Name',
				'description' => 'Name of the OverDrive app to display to patrons. Default is Libby. Sora, and Lexis Nexis are additional options in special cases.',
				'default' => 'Libby',
				'maxLength' => 25,
				'canBatchUpdate' => true,
			],
			'url' => [
				'property' => 'url',
				'type' => 'url',
				'label' => 'url',
				'description' => 'The publicly accessible URL',
				'canBatchUpdate' => false,
			],
			'patronApiUrl' => [
				'property' => 'patronApiUrl',
				'type' => 'url',
				'label' => 'Patron API URL',
				'description' => 'The URL where the Patron API is located',
				'canBatchUpdate' => false,
			],
			'clientKey' => [
				'property' => 'clientKey',
				'type' => 'text',
				'label' => 'Client Key',
				'description' => 'The client key provided by OverDrive when registering',
				'canBatchUpdate' => false,
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Client Secret',
				'description' => 'The client secret provided by OverDrive when registering',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'accountId' => [
				'property' => 'accountId',
				'type' => 'integer',
				'label' => 'Account Id',
				'description' => 'The account id for the main collection provided by OverDrive and used to load information about collections',
				'canBatchUpdate' => false,
			],
			'websiteId' => [
				'property' => 'websiteId',
				'type' => 'integer',
				'label' => 'Website Id',
				'description' => 'The website id provided by OverDrive and used to load circulation information',
				'canBatchUpdate' => false,
			],
			'productsKey' => [
				'property' => 'productsKey',
				'type' => 'text',
				'label' => 'Products Key',
				'description' => 'The products key provided by OverDrive used to load information about collections',
				'canBatchUpdate' => false,
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
			'showLibbyPromo' => [
				'property' => 'showLibbyPromo',
				'type' => 'checkbox',
				'label' => 'Show Libby promo in checkout fulfillment interface',
				'description' => 'Whether or not to show the Libby promo ad in the fulfillment interface',
				'default' => 1,
			],
			'numExtractionThreads' => [
				'property' => 'numExtractionThreads',
				'type' => 'integer',
				'label' => 'Num Extraction Threads',
				'description' => 'The number of threads to use when extracting from OverDrive',
				'canBatchUpdate' => false,
				'default' => 10,
				'min' => 1,
				'max' => 10,
			],
			'numRetriesOnError' => [
				'property' => 'numRetriesOnError',
				'type' => 'integer',
				'label' => 'Num Retries',
				'description' => 'The number of retries to attempt when errors are returned from OverDrive',
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
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'enableRequestLogging' => [
				'property' => 'enableRequestLogging',
				'type' => 'checkbox',
				'label' => 'Enable Request Logging',
				'description' => 'Whether or not request logging is done while extracting from Aspen.',
				'default' => 0,
			],
			'librarySettingsSection' => [
				'property' => 'librarySettingsSection',
				'type' => 'section',
				'label' => 'Authentication and Advantage Information',
				'expandByDefault' => true,
				'properties' => [
					'librarySettings' => [
						'property' => 'librarySettings',
						'type' => 'oneToMany',
						'label' => '',
						'description' => '',
						'note' => 'Define settings for how patron authentication is done as well as OverDrive Advantage information for each library that uses this collection.',
						'keyThis' => 'id',
						'keyOther' => 'settingId',
						'subObjectType' => 'LibraryOverDriveSettings',
						'structure' => $libraryOverDriveSettingsStructure,
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
						'subObjectType' => 'OverDriveScope',
						'structure' => $overdriveScopeStructure,
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
		if (!(UserAccount::getActiveUserObj()->isAspenAdminUser())) {
			unset($objectStructure['enableRequestLogging']);
		}
		return $objectStructure;
	}

	public function __toString() {
		return "$this->name ($this->url)";
	}

	public function update($context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
			$this->saveLibrarySettings();
		}
		return true;
	}

	public function insert($context = '') : int {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new OverDriveScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
				$allScope->includeAdult = true;
				$allScope->includeKids = true;
				$allScope->includeTeen = true;
				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
			$this->saveLibrarySettings();
		}
		return $ret;
	}

	public function saveScopes() : void {
		if (isset ($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function saveLibrarySettings() : void {
		if (isset ($this->_librarySettings) && is_array($this->_librarySettings)) {
			$this->saveOneToManyOptions($this->_librarySettings, 'settingId');
			unset($this->_librarySettings);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new OverDriveScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} elseif ($name == "librarySettings") {
			if (!isset($this->_librarySettings) && $this->id) {
				$this->_librarySettings = [];
				$librarySetting = new LibraryOverDriveSettings();
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
