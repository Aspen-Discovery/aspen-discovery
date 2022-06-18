<?php

require_once ROOT_DIR . '/sys/Axis360/Axis360Scope.php';
class Axis360Setting extends DataObject
{
	public $__table = 'axis360_settings';    // table name
	public $id;
	public $apiUrl;
	public $userInterfaceUrl;
	public $vendorUsername;
	public $vendorPassword;
	public $libraryPrefix;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	private $_scopes;

	public static function getObjectStructure() : array
	{
		$axis360ScopeStructure = Axis360Scope::getObjectStructure();
		unset($axis360ScopeStructure['settingId']);

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiUrl' => array('property' => 'apiUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL to the API'),
			'userInterfaceUrl' => array('property' => 'userInterfaceUrl', 'type' => 'url', 'label' => 'User Interface URL', 'description' => 'The URL where the Patron can access the catalog'),
			'vendorUsername' => array('property' => 'vendorUsername', 'type' => 'text', 'label' => 'Vendor Username', 'description' => 'The Vendor Username provided by Axis360 when registering'),
			'vendorPassword' => array('property' => 'vendorPassword', 'type' => 'storedPassword', 'label' => 'Vendor Password', 'description' => 'The Vendor Password provided by Axis360 when registering', 'hideInLists' => true),
			'libraryPrefix' => array('property' => 'libraryPrefix', 'type' => 'text', 'label' => 'Library Prefix', 'description' => 'The Library Prefix to use with the API'),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'Axis360Scope',
				'structure' => $axis360ScopeStructure,
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
		return 'Library ' . $this->libraryPrefix . ' (' . $this->apiUrl . ')';
	}

	/**
	 * @return int|bool
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
		}
		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)){
				$this->_scopes = [];
				$allScope = new Axis360Scope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
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
				$scope = new Axis360Scope();
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