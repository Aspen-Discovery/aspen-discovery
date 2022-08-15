<?php

class AppSetting extends DataObject
{
	public $__table = 'aspen_lida_general_settings';
	public $id;
	public $name;
	public $enableAccess;
	public $releaseChannel;

	private $_locations;

	static function getObjectStructure() : array {
		$releaseChannels = [0 => 'Beta (Testing)', 1 => 'Production (Public)'];
		$locationList = Location::getLocationList(UserAccount::userHasPermission('Administer Aspen LiDA Settings'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name for these settings', 'maxLength' => 50, 'required' => true),
			'enableAccess' => array('property' => 'enableAccess', 'type' => 'checkbox', 'label' => 'Display location(s) in Aspen LiDA', 'description' => 'Whether or not the selected locations are available in Aspen LiDA.', 'default' => true),
			'releaseChannel' => array('property' => 'releaseChannel', 'type' => 'enum', 'values' => $releaseChannels, 'label' => 'Release Channel', 'description' => 'Is the location available in the production or beta/testing app'),
			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use these settings',
				'values' => $locationList,
			),

		);

		return $structure;
	}

	public function __get($name){
		if ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new Location();
				$obj->lidaGeneralSettingId = $this->id;
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
		if ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList(UserAccount::userHasPermission('Administer Aspen LiDA Settings'));
			foreach ($locationList as $locationId => $displayName){
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)){
					//We want to apply the scope to this library
					if ($location->lidaGeneralSettingId != $this->id){
						$location->lidaGeneralSettingId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->lidaGeneralSettingId == $this->id){
						$location->lidaGeneralSettingId = -1;
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	function getEditLink() : string{
		return '/AspenLiDA/AppSettings?objectAction=edit&id=' . $this->id;
	}
}