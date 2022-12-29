<?php
require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';

class OverDriveSetting extends DataObject {
	public $__table = 'overdrive_settings';    // table name
	public $id;
	public $url;
	public $patronApiUrl;
	public $clientSecret;
	public $clientKey;
	public $accountId;
	public $websiteId;
	public $productsKey;
	public $runFullUpdate;
	public $useFulfillmentInterface;
	public $showLibbyPromo;
	public $allowLargeDeletes;
	public $numExtractionThreads;
	public $numRetriesOnError;
	public $productsToUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	public $enableRequestLogging;

	public $_scopes;

	public function getEncryptedFieldNames(): array {
		return ['clientSecret'];
	}

	public static function getObjectStructure($context = ''): array {
		$overdriveScopeStructure = OverDriveScope::getObjectStructure($context);
		unset($overdriveScopeStructure['settingId']);

		$objectStructure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
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
			'useFulfillmentInterface' => [
				'property' => 'useFulfillmentInterface',
				'type' => 'checkbox',
				'label' => 'Enable updated checkout fulfillment interface',
				'description' => 'Whether or not to use the updated fulfillment interface',
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
			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
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
		];
		if (!(UserAccount::getActiveUserObj()->source = 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin')) {
			unset($objectStructure['enableRequestLogging']);
		}
		return $objectStructure;
	}

	public function __toString() {
		return $this->url;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
		}
		return true;
	}

	public function insert($context = '') {
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
				$scope = new OverDriveScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}
}