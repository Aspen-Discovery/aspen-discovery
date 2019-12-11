<?php

require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
class SideLoadScope extends DataObject
{
	public $__table = 'sideload_scopes';
	public $id;
	public $name;
	public $sideLoadId;
	public $restrictToChildrensMaterial;
	private $__libraries;
	private $__locations;

	public static function getObjectStructure()
	{
		$validSideLoads = [];
		$sideLoad = new SideLoad();
		$sideLoad->orderBy('name');
		$sideLoad->find();
		while ($sideLoad->fetch()){
			$validSideLoads[$sideLoad->id] = $sideLoad->name;
		}

		$librarySideLoadScopeStructure = LibrarySideLoadScope::getObjectStructure();
		unset($librarySideLoadScopeStructure['sideLoadScopeId']);

		$locationSideLoadScopeStructure = LocationSideLoadScope::getObjectStructure();
		unset($locationSideLoadScopeStructure['sideLoadScopeId']);

		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'sideLoadId' => array('property' => 'sideLoadId', 'type' => 'enum', 'values'=>$validSideLoads, 'label' => 'Side Load', 'description' =>'The Side Load to apply the scope to'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by RBdigital', 'default'=>0),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'helpLink' => '',
				'keyThis' => 'id',
				'keyOther' => 'sideLoadScopeId',
				'subObjectType' => 'LibrarySideLoadScope',
				'structure' => $librarySideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => array(
					array(
						'text' => 'Apply To All Libraries',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=addToAllLibraries',
					),
					array(
						'text' => 'Clear Libraries',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=clearLibraries',
						'class' => 'btn-warning',
					),
				)
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'helpLink' => '',
				'keyThis' => 'id',
				'keyOther' => 'sideLoadScopeId',
				'subObjectType' => 'LocationSideLoadScope',
				'structure' => $locationSideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => array(
					array(
						'text' => 'Apply To All Locations',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=addToAllLocations',
					),
					array(
						'text' => 'Clear Locations',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=clearLocations',
						'class' => 'btn-warning',
					),
				)
			),
		);
		return $structure;
	}

	function getEditLink(){
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->__libraries) && $this->id){
				$this->__libraries = [];
				$obj = new LibrarySideLoadScope();
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->__libraries[$obj->id] = clone($obj);
				}
			}
			return $this->__libraries;
		} elseif ($name == "locations") {
			if (!isset($this->__locations) && $this->id){
				$this->__locations = [];
				$obj = new LocationSideLoadScope();
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->__locations[$obj->id] = clone($obj);
				}
			}
			return $this->__locations;
		} else {
			return $this->__data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->__libraries = $value;
		}elseif ($name == "locations") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->__locations = $value;
		}else {
			$this->__data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->__libraries) && is_array($this->__libraries)){
			$this->saveOneToManyOptions($this->__libraries, 'sideLoadScopeId');
			unset($this->__libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->__locations) && is_array($this->__locations)){
			$this->saveOneToManyOptions($this->__locations, 'sideLoadScopeId');
			unset($this->__locations);
		}
	}

	/** @return LibrarySideLoadScope[] */
	public function getLibraries()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->libraries;
	}

	/** @return LocationSideLoadScope[] */
	public function getLocations()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->locations;
	}

	public function setLibraries($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->libraries = $val;
	}

	public function setLocations($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->locations = $val;
	}

	public function clearLibraries(){
		$this->clearOneToManyOptions('LibrarySideLoadScope', 'sideLoadScopeId');
		unset($this->__libraries);
	}

	public function clearLocations(){
		$this->clearOneToManyOptions('LocationSideLoadScope', 'sideLoadScopeId');
		unset($this->__locations);
	}
}
