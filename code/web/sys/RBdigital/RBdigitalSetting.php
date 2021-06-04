<?php
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';
class RBdigitalSetting extends DataObject
{
	public $__table = 'rbdigital_settings';    // table name
	public $id;
	public $apiUrl;
	public $userInterfaceUrl;
	public $apiToken;
	public $libraryId;
	public $allowPatronLookupByEmail;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	private $_scopes;

	public static function getObjectStructure() : array
	{
		$rbdigitalScopeStructure = RBdigitalScope::getObjectStructure();
		unset($rbdigitalScopeStructure['settingId']);

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiUrl' => array('property' => 'apiUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL to the API'),
			'userInterfaceUrl' => array('property' => 'userInterfaceUrl', 'type' => 'url', 'label' => 'User Interface URL', 'description' => 'The URL where the Patron can access the catalog'),
			'apiToken' => array('property' => 'apiToken', 'type' => 'text', 'label' => 'API Token', 'description' => 'The API Token provided by RBdigital when registering'),
			'libraryId' => array('property' => 'libraryId', 'type' => 'integer', 'label' => 'Library Id', 'description' => 'The library id provided by RBdigital'),
			'allowPatronLookupByEmail' => array('property' => 'allowPatronLookupByEmail', 'type' => 'checkbox', 'label' => 'Allow Patron Lookup by Email', 'description' => 'Whether or not patrons can be looked up in RBdigital based on their email', 'default' => 1),
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
				'subObjectType' => 'RBdigitalScope',
				'structure' => $rbdigitalScopeStructure,
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
		return 'Library ' . $this->libraryId . ' (' . $this->userInterfaceUrl . ')';
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
				$allScope = new RBdigitalScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";
				$allScope->includeEAudiobook = true;
				$allScope->includeEBooks = true;
				$allScope->includeEMagazines = true;
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
				$scope = new RBdigitalScope();
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