<?php

require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';
class RBdigitalScope extends DataObject
{
	public $__table = 'rbdigital_scopes';
	public $id;
	public $settingId;
	public $name;
	public $includeEAudiobook;
	public $includeEBooks;
	public $includeEMagazines;
	public $restrictToChildrensMaterial;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure()
	{
		$rbDigitalSettings =[];
		$rbDigitalSetting = new RBdigitalSetting();
		$rbDigitalSetting->find();
		while ($rbDigitalSetting->fetch()){
			$rbDigitalSettings[$rbDigitalSetting->id] = (string)$rbDigitalSetting;
		}

		$libraryList = Library::getLibraryList();
		$locationList = Location::getLocationList();

		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'settingId' => ['property' => 'settingId', 'type' => 'enum', 'values' => $rbDigitalSettings, 'label' => 'Setting Id'],
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'includeEAudiobook' => array('property'=>'includeEAudiobook', 'type'=>'checkbox', 'label'=>'Include eAudio books', 'description'=>'Whether or not EAudiobook are included', 'default'=>1),
			'includeEBooks' => array('property'=>'includeEBooks', 'type'=>'checkbox', 'label'=>'Include eBooks', 'description'=>'Whether or not EBooks are included', 'default'=>1),
			'includeEMagazines' => array('property'=>'includeEMagazines', 'type'=>'checkbox', 'label'=>'Include eMagazines', 'description'=>'Whether or not EMagazines are included', 'default'=>1),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by RBdigital', 'default'=>0),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
				'hideInLists' => true,
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
				'hideInLists' => true,
			),
		);
		return $structure;
	}

	/** @noinspection PhpUnused */
	public function getEditLink(){
		return '/RBdigital/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->rbdigitalScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new Location();
				$obj->rbdigitalScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_locations = $value;
		}else {
			$this->_data[$name] = $value;
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
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList();
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->rbdigitalScopeId != $this->id){
						$library->rbdigitalScopeId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->rbdigitalScopeId == $this->id){
						$library->rbdigitalScopeId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList();
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName){
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)){
					//We want to apply the scope to this library
					if ($location->rbdigitalScopeId != $this->id){
						$location->rbdigitalScopeId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->rbdigitalScopeId == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->rbdigitalScopeId != -1){
							$location->rbdigitalScopeId = -1;
						}else{
							$location->rbdigitalScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[] */
	public function getLibraries()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->_libraries;
	}

	/** @return Location[] */
	public function getLocations()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->_locations;
	}

	public function setLibraries($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->_libraries = $val;
	}

	public function setLocations($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->_libraries = $val;
	}

	public function clearLibraries(){
		$this->clearOneToManyOptions('Library', 'rbdigitalScopeId');
		unset($this->_libraries);
	}

	public function clearLocations(){
		$this->clearOneToManyOptions('Location', 'rbdigitalScopeId');
		unset($this->_locations);
	}
}
