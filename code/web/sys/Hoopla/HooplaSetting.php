<?php
require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';

class HooplaSetting extends DataObject
{
	public $__table = 'hoopla_settings';    // table name
	public $id;
	public $apiUrl;
	public $libraryId;
	public $apiUsername;
	public $apiPassword;
	public /** @noinspection PhpUnused */ $apiToken;
	public $regroupAllRecords;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	public /** @noinspection PhpUnused */ $excludeTitlesWithCopiesFromOtherVendors;

	private $_scopes;

	public static function getObjectStructure() : array
	{
		$hooplaScopeStructure = HooplaScope::getObjectStructure();
		unset($hooplaScopeStructure['settingId']);

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiUrl' => array('property' => 'apiUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL to the API'),
			'libraryId' => array('property' => 'libraryId', 'type' => 'integer', 'label' => 'Library Id', 'description' => 'The Library Id to use with the API'),
			'excludeTitlesWithCopiesFromOtherVendors' => array('property' => 'excludeTitlesWithCopiesFromOtherVendors', 'type' => 'checkbox', 'label' => 'Exclude Records With Copies from other eContent Vendors (OverDrive, cloudLibrary, Axis 360, etc.)', 'description' => 'Whether or not records in other collections should be included', 'default' => 0, 'forcesReindex' => true),
			'apiUsername' => array('property' => 'apiUsername', 'type' => 'text', 'label' => 'API Username', 'description' => 'The API Username provided by Hoopla when registering'),
			'apiPassword' => array('property' => 'apiPassword', 'type' => 'storedPassword', 'label' => 'API Password', 'description' => 'The API Password provided by Hoopla when registering', 'hideInLists' => true),
			'regroupAllRecords' => array('property' => 'regroupAllRecords', 'type' => 'checkbox', 'label' => 'Regroup all Records', 'description' => 'Whether or not all existing records should be regrouped', 'default' => 0),
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
				'subObjectType' => 'HooplaScope',
				'structure' => $hooplaScopeStructure,
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
		return 'Library ' . $this->libraryId . ' (' . $this->apiUsername . ')';
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
				$scope = new HooplaScope();
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