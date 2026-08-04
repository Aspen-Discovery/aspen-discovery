<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';

class HooplaSetting extends DataObject {
	public $__table = 'hoopla_settings';    // table name
	public $id;
	public $countryCode;
	public $apiUrl;
	public $apiUsername;
	public $apiPassword;
	public $accessToken;
	public $tokenExpirationTime;
	/** @noinspection PhpUnused */
	public $regroupAllRecords;
	/** @noinspection PhpUnused */
	public $runFullUpdate;
	/** @noinspection PhpUnused */
	public $lastUpdateOfChangedRecords;
	/** @noinspection PhpUnused */
	public $lastUpdateOfAllRecords;
	/** @noinspection PhpUnused */
	public $lastRecordProcessed;
	public $recordExtractionBatchSize;
	public $hooplaFlexBatchSize;
	public $indexingTime;
	// Legacy Hoopla v1 columns
	public $libraryId;
	public $runFullUpdateInstant;
	public $lastUpdateOfChangedRecordsInstant;
	public $lastUpdateOfAllRecordsInstant;
	public $runFullUpdateFlex;
	public $lastUpdateOfChangedRecordsFlex;
	public $lastUpdateOfAllRecordsFlex;
	public $hooplaInstantEnabled;
	public $hooplaFlexEnabled;

	private $_scopes;
	private $_librarySettings;
	private static ?bool $isHooplaVersion2 = null;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$hooplaScopeStructure = HooplaScope::getObjectStructure($context);
		unset($hooplaScopeStructure['settingId']);

		$isVersion2 = self::isHooplaVersion2();
		$libraryHooplaSettingsStructure = null;
		if ($isVersion2) {
			require_once ROOT_DIR . '/sys/Hoopla/LibraryHooplaSetting.php';
			$libraryHooplaSettingsStructure = LibraryHooplaSetting::getObjectStructure($context);
			unset($libraryHooplaSettingsStructure['settingId']);
			unset($libraryHooplaSettingsStructure['weight']);
		}

		// Base structure
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
		];

		if (!$isVersion2) {
			$structure['libraryId'] = [
				'property' => 'libraryId',
				'type' => 'integer',
				'label' => 'Library Id',
				'description' => 'The Library Id to use with the API',
			];
		}

		// Build the API Connection Properties based on Hoopla Version
		$structure['apiConnectionSection'] = [
			'property' => 'apiConnectionSection',
			'type' => 'section',
			'label' => 'API Connection Settings',
			'expandByDefault' => false,
			'properties' => self::getApiConnectionProperties($isVersion2),
		];

		// Build the Indexing Settings Properties based on Hoopla Version
		$structure['indexingSettingsSection'] = [
				'property' => 'indexingSettingsSection',
				'type' => 'section',
				'label' => 'General Indexing Settings',
				'expandByDefault' => true,
				'properties' => self::getIndexingProperties($isVersion2),
		];

		if ($isVersion2) {
			// Build the Library Settings Properties based on Hoopla Version
			// This is only for Hoopla Version 2
			$structure['librarySettingsSection'] = [
				'property' => 'librarySettingsSection',
				'type' => 'section',
				'label' => 'Hoopla Library Information',
				'expandByDefault' => true,
				'properties' => [
					'librarySettings' => [
						'property' => 'librarySettings',
						'type' => 'oneToMany',
						'label' => '',
						'description' => '',
						'note' => 'Define Hoopla information for each library that uses this collection.',
						'keyThis' => 'id',
						'keyOther' => 'settingId',
						'subObjectType' => 'LibraryHooplaSetting',
						'structure' => $libraryHooplaSettingsStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'additionalOneToManyActions' => [],
						'canAddNew' => true,
						'canDelete' => true,
					],
				],
			];
		}

		if (!$isVersion2) {
			$structure['hooplaInstantSection'] = [
				'property' => 'hooplaInstantSection',
				'type' => 'section',
				'label' => 'Hoopla Instant Settings',
				'expandByDefault' => true,
				'properties' => self::getLegacyInstantProperties(),
			];
			$structure['hooplaFlexSection'] = [
				'property' => 'hooplaFlexSection',
				'type' => 'section',
				'label' => 'Hoopla Flex Settings',
				'expandByDefault' => true,
				'properties' => self::getLegacyFlexProperties(),
			];
		}

		$structure += [
			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'HooplaScope',
				'structure' => $hooplaScopeStructure,
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
		return 'Hoopla ' . ' (' . $this->apiUsername . ')';
	}

	public function update(string $context = '') : int|bool {
		if ($this->indexingTime < 0 || $this->indexingTime > 23) {
			$this->indexingTime = 1;
		}

		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
			if (self::isHooplaVersion2()) {
				$this->saveLibrarySettings();
			}
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new HooplaScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
				$allScope->includeEAudiobook = true;
				$allScope->maxCostPerCheckoutEAudiobook = 5;
				$allScope->includeEBooks = true;
				$allScope->maxCostPerCheckoutEBooks = 5;
				$allScope->includeEComics = true;
				$allScope->maxCostPerCheckoutEComics = 5;
				$allScope->includeMovies = true;
				$allScope->maxCostPerCheckoutMovies = 5;
				$allScope->includeMusic = true;
				$allScope->maxCostPerCheckoutTelevision = 5;

				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
			if (self::isHooplaVersion2()) {
				$this->saveLibrarySettings();
			}
		}
		return $ret;
	}

	public function saveScopes() : void {
		if (isset ($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function saveLibrarySettings(): void {
		if (self::isHooplaVersion2() && isset($this->_librarySettings) && is_array($this->_librarySettings)) {
			$this->saveOneToManyOptions($this->_librarySettings, 'settingId');
			unset($this->_librarySettings);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new HooplaScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} elseif ($name == "librarySettings") {
			if (!self::isHooplaVersion2()) {
				return [];
			}
			if (!isset($this->_librarySettings) && $this->id) {
				require_once ROOT_DIR . '/sys/Hoopla/LibraryHooplaSetting.php';
				$this->_librarySettings = [];
				$librarySettings = new LibraryHooplaSetting();
				$librarySettings->settingId = $this->id;
				$librarySettings->find();
				while ($librarySettings->fetch()) {
					$this->_librarySettings[$librarySettings->id] = clone($librarySettings);
				}
			}
			return $this->_librarySettings;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} elseif ($name == "librarySettings") {
			if (self::isHooplaVersion2()) {
				$this->_librarySettings = $value;
			}
		} else {
			parent::__set($name, $value);
		}
	}

	private static function isHooplaVersion2(): bool {
		if (self::$isHooplaVersion2 == null) {
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			self::$isHooplaVersion2 = ($systemVariables !== false && !empty($systemVariables->hooplaVersion) && (int)$systemVariables->hooplaVersion == 2);
		}
		return self::$isHooplaVersion2;
	}

	private static function getApiConnectionProperties(bool $isVersion2): array {
		$apiConnectionProperties = [];
		if ($isVersion2) {
			$apiConnectionProperties['countryCode'] = [
				'property' => 'countryCode',
				'type' => 'enum',
				'label' => 'Country Code',
				'description' => 'The country code for the API',
				'values' => [
					'US' => 'US',
					'CA' => 'CA',
					'NZ' => 'NZ',
					'AU' => 'AU',
				],
			];
		}
		$apiConnectionProperties += [
			'apiUrl' => [
				'property' => 'apiUrl',
				'type' => 'url',
				'label' => 'url',
				'description' => 'The URL to the API',
			],
			'apiUsername' => [
				'property' => 'apiUsername',
				'type' => 'text',
				'label' => 'API Username',
				'description' => 'The API Username provided by your Aspen support vendor (or Hoopla when registering if not using third-party support or hosting)',
			],
			'apiPassword' => [
				'property' => 'apiPassword',
				'type' => 'storedPassword',
				'label' => 'API Password',
				'description' => 'The API Password provided by your Aspen support vendor (or Hoopla when registering if not using third-party support or hosting)',
				'hideInLists' => true,
			],
		];
		return $apiConnectionProperties;
	}
	private static function getIndexingProperties(bool $isVersion2): array {
		$indexingProperties = [];
		if ($isVersion2) {
			$indexingProperties += [
				'runFullUpdate' => [
					'property' => 'runFullUpdate',
					'type' => 'checkbox',
					'label' => 'Run Full Update',
					'description' => 'Trigger a full metadata reload on the next export run, including Instant and Flex titles',
					'default' => 0,
				],
			];
		}
		$indexingProperties += [
			'regroupAllRecords' => [
				'property' => 'regroupAllRecords',
				'type' => 'checkbox',
				'label' => 'Regroup all Records',
				'description' => 'Whether or not all existing records should be regrouped',
				'default' => 0,
			],
			'indexingTime' => [
				'property' => 'indexingTime',
				'type' => 'integer',
				'label' => 'Indexing Time',
				'description' => 'In 24 hour format, the hour of the day when the indexing should be run',
				'note' => '24 hour format, please enter a value between 0 and 23, default is 1',
				'default' => 1,
			],
			'recordExtractionBatchSize' => [
				'property' => 'recordExtractionBatchSize',
				'type' => 'enum',
				'label' => 'Record Extraction Batch Size',
				'description' => 'The number of records that should be extracted at once.',
				'note' => 'This normally does not need changes unless requested by Hoopla',
				'values' => [
					'100' => '100',
					'200' => '200',
					'300' => '300',
					'400' => '400',
					'500' => '500',
				],
				'default' => '500',
			],
			'hooplaFlexBatchSize' => [
				'property' => 'hooplaFlexBatchSize',
				'type' => 'integer',
				'label' => 'Flex Availability Batch Size',
				'description' => 'The number of records to process per api request for availability updates.',
				'note' => 'This normally does not need changes unless requested by Hoopla',
				'default' => '50',
				'max' => '50',
				'min' => '1',
			],

		];
		if ($isVersion2) {
			$indexingProperties += [
				'lastUpdateOfChangedRecords' => [
					'property' => 'lastUpdateOfChangedRecords',
					'type' => 'timestamp',
					'label' => 'Last Update of Changed Records',
					'description' => 'The timestamp when just changes were loaded',
					'default' => 0,
				],
				'lastUpdateOfAllRecordsGlobal' => [
					'property' => 'lastUpdateOfAllRecords',
					'type' => 'timestamp',
					'label' => 'Last Update of All Records',
					'description' => 'The timestamp when all records were loaded from the API',
					'default' => 0,
				],
				'lastRecordProcessed' => [
					'property' => 'lastRecordProcessed',
					'type' => 'integer',
					'label' => 'Last Record Processed',
					'description' => 'The index of the last record that was processed. Can be used for resuming API extracts if errors are generated.',
					'default' => 0,
				],
			];
		}
		return $indexingProperties;
	}


	private static function getLegacyInstantProperties(): array {
		return [
			'hooplaInstantEnabled' => [
				'property' => 'hooplaInstantEnabled',
				'type' => 'checkbox',
				'label' => 'Hoopla Instant Enabled',
				'description' => 'Whether or not Hoopla Instant titles should be indexed for this collection',
				'default' => 1,
			],
			'runFullUpdateInstant' => [
				'property' => 'runFullUpdateInstant',
				'type' => 'checkbox',
				'label' => 'Run Full Update for Instant',
				'description' => 'Whether or not a full update of all Instant records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedRecordsInstant' => [
				'property' => 'lastUpdateOfChangedRecordsInstant',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Instant Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllRecordsInstant' => [
				'property' => 'lastUpdateOfAllRecordsInstant',
				'type' => 'timestamp',
				'label' => 'Last Update of All Instant Records',
				'description' => 'The timestamp when all records were loaded',
				'default' => 0,
			],
		];
	}

	private static function getLegacyFlexProperties(): array {
		return [
			'hooplaFlexEnabled' => [
				'property' => 'hooplaFlexEnabled',
				'type' => 'checkbox',
				'label' => 'Hoopla Flex Enabled',
				'description' => 'Whether or not Hoopla Flex titles should be indexed for this collection',
				'default' => 0,
			],
			'runFullUpdateFlex' => [
				'property' => 'runFullUpdateFlex',
				'type' => 'checkbox',
				'label' => 'Run Full Update for Flex',
				'description' => 'Whether or not a full update of all Flex records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedRecordsFlex' => [
				'property' => 'lastUpdateOfChangedRecordsFlex',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Flex Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllRecordsFlex' => [
				'property' => 'lastUpdateOfAllRecordsFlex',
				'type' => 'timestamp',
				'label' => 'Last Update of All Flex Records',
				'description' => 'The timestamp when all records were loaded',
				'default' => 0,
			],
		];
	}
}
