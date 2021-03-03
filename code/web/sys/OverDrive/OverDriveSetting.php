<?php
require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
class OverDriveSetting extends DataObject
{
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
	public $allowLargeDeletes;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	public $_scopes;

	public static function getObjectStructure()
	{
		$overdriveScopeStructure = OverDriveScope::getObjectStructure();
		unset($overdriveScopeStructure['settingId']);

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'url' => array('property' => 'url', 'type' => 'url', 'label' => 'url', 'description' => 'The publicly accessible URL', 'canBatchUpdate'=>false),
			'patronApiUrl' => array('property' => 'patronApiUrl', 'type' => 'url', 'label' => 'Patron API URL', 'description' => 'The URL where the Patron API is located', 'canBatchUpdate'=>false),
			'clientKey' => array('property' => 'clientKey', 'type' => 'text', 'label' => 'Client Key', 'description' => 'The client key provided by OverDrive when registering', 'canBatchUpdate'=>false),
			'clientSecret' => array('property' => 'clientSecret', 'type' => 'text', 'label' => 'Client Secret', 'description' => 'The client secret provided by OverDrive when registering', 'canBatchUpdate'=>false),
			'accountId' => array('property' => 'accountId', 'type' => 'integer', 'label' => 'Account Id', 'description' => 'The account id for the main collection provided by OverDrive and used to load information about collections', 'canBatchUpdate'=>false),
			'websiteId' => array('property' => 'websiteId', 'type' => 'integer', 'label' => 'Website Id', 'description' => 'The website id provided by OverDrive and used to load circulation information', 'canBatchUpdate'=>false),
			'productsKey' => array('property' => 'productsKey', 'type' => 'text', 'label' => 'Products Key', 'description' => 'The products key provided by OverDrive used to load information about collections', 'canBatchUpdate'=>false),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'allowLargeDeletes' => array('property' => 'allowLargeDeletes', 'type' => 'checkbox', 'label' => 'Allow Large Deletes', 'description' => 'Whether or not Aspen can delete more than 500 records or 5% of the collection', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'helpLink' => '',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'OverDriveScope',
				'structure' => $overdriveScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'additionalOneToManyActions' => []
			]
		);
	}

	public function __toString()
	{
		return $this->url;
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)){
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

	public function saveScopes(){
		if (isset ($this->_scopes) && is_array($this->_scopes)){
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function __get($name){
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id){
				$this->_scopes = [];
				$scope = new OverDriveScope();
				$scope->settingId = $this->id;
				$scope->find();
				while($scope->fetch()){
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "scopes") {
			$this->_scopes = $value;
		}else {
			$this->_data[$name] = $value;
		}
	}
}