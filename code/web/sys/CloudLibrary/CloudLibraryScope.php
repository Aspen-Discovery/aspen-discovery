<?php

class CloudLibraryScope extends DataObject
{
	public $__table = 'cloud_library_scopes';
	public $id;
	public $name;
	public /** @noinspection PhpUnused */ $includeEAudiobook;
	public /** @noinspection PhpUnused */ $includeEBooks;
	public /** @noinspection PhpUnused */ $restrictToChildrensMaterial;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure()
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		return array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'includeEAudiobook' => array('property'=>'includeEAudiobook', 'type'=>'checkbox', 'label'=>'Include eAudio books', 'description'=>'Whether or not EAudiobook are included', 'default'=>1, 'forcesReindex' => true),
			'includeEBooks' => array('property'=>'includeEBooks', 'type'=>'checkbox', 'label'=>'Include eBooks', 'description'=>'Whether or not EBooks are included', 'default'=>1, 'forcesReindex' => true),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by RBdigital', 'default'=>0, 'forcesReindex' => true),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
				'forcesReindex' => true
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
				'forcesReindex' => true
			),
		);
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->cloudLibraryScopeId = $this->id;
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
				$obj->cloudLibraryScopeId = $this->id;
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
			$this->_libraries = $value;
		}elseif ($name == "locations") {
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
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->cloudLibraryScopeId != $this->id){
						$library->cloudLibraryScopeId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->cloudLibraryScopeId == $this->id){
						$library->cloudLibraryScopeId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
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
					if ($location->cloudLibraryScopeId != $this->id){
						$location->cloudLibraryScopeId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->cloudLibraryScopeId == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->cloudLibraryScopeId != -1){
							$location->cloudLibraryScopeId = -1;
						}else{
							$location->cloudLibraryScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries()
	{
		return $this->_libraries;
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations()
	{
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries(){
		$this->clearOneToManyOptions('Library', 'cloudLibraryScopeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations(){
		$this->clearOneToManyOptions('Location', 'cloudLibraryScopeId');
		unset($this->_locations);
	}
}
