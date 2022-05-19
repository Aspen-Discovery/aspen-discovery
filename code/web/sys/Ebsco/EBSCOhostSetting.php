<?php

/**
 * Class EBSCOhostSetting - Store settings for EBSCOhost
 */
class EBSCOhostSetting extends DataObject
{
	public $__table = 'ebscohost_settings';
	public $id;
	public $name;
	public $authType;
	public $profileId;
	public $profilePwd;
	public $ipProfileId;

	private $_libraries;
	private $_locations;

	static function getObjectStructure() : array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'A name for these settings', 'required' => true),
			'authType' => array('property'=>'authType', 'type'=>'enum', 'label'=>'Profile or IP Authentication', 'values' => array('profile' => 'Profile Authentication', 'ip' => 'IP Authentication'), 'description'=>'If using IP Authentication or Profile Authentication'),
			'profileId' => array('property' => 'profileId', 'type' => 'text', 'label' => 'Profile Id', 'description' => 'The profile used for authentication. Required if using profile authentication.', 'hideInLists' => true),
			'profilePwd' => array('property' => 'profilePwd', 'type' => 'text', 'label' => 'Profile Password', 'description' => 'The password used for profile authentication. Required if using profile authentication.', 'hideInLists' => true),
			'ipProfileId' => array('property' => 'ipProfileId', 'type' => 'text', 'label' => 'IP Profile Id', 'description' => 'The IP profile used for authenication. Required if using IP authentication.', 'hideInLists' => true),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this setting',
				'values' => $libraryList
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this setting',
				'values' => $locationList
			),
		);

		if (!UserAccount::userHasPermission('Library eCommerce Options')){
			unset($structure['libraries']);
		}

		return $structure;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->ebscohostSettingId = $this->id;
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
				$obj->ebscohostSettingId = $this->id;
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
					if ($library->ebscohostSettingId != $this->id){
						$library->ebscohostSettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->ebscohostSettingId == $this->id){
						$library->ebscohostSettingId = -1;
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
					if ($location->ebscohostSettingId != $this->id){
						$location->ebscohostSettingId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->ebscohostSettingId == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->ebscohostSettingId != -1){
							$location->ebscohostSettingId = -1;
						}else{
							$location->ebscohostSettingId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

}