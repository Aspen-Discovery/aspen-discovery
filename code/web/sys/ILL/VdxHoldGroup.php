<?php

require_once ROOT_DIR . '/sys/ILL/VdxHoldGroupLocation.php';

class VdxHoldGroup extends DataObject
{
	public $__table = 'vdx_hold_groups';
	public $id;
	public $name;

	protected $_locations;
	protected $_locationCodes;

	public static function getObjectStructure(): array
	{
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer ILL Hold Groups'));

		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Hold Group', 'maxLength' => 50],

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that make up this hold group',
				'values' => $locationList,
				'hideInLists' => false
			),
		];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array
	{
		return ['name'];
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$holdGroupLocation = new VdxHoldGroupLocation();
			$holdGroupLocation->vdxHoldGroupId = $this->id;
			$holdGroupLocation->delete(true);
		}
		return $ret;
	}

	public function __get($name)
	{
		if ($name == "locations") {
			return $this->getLocations();
		} else {
			return $this->_data[$name];
		}
	}

	/**
	 * @return int[]
	 */
	public function getLocations(): ?array
	{
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new VdxHoldGroupLocation();
			$obj->vdxHoldGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function getLocationCodes() {
		if (!isset($this->_locationCodes) && $this->id) {
			$this->_locationCodes = [];
			$locationIds = $this->getLocations();
			foreach ($locationIds as $locationId){
				$location = new Location();
				$location->locationId = $locationId;
				if ($location->find(true)){
					$this->_locationCodes[] = $location->code;
				}
			}
		}
		return $this->_locationCodes;
	}

	public function __set($name, $value)
	{
		if ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}


	public function saveLocations()
	{
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer VDX Hold Groups'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new VdxHoldGroupLocation();
				$obj->vdxHoldGroupId = $this->id;
				$obj->locationId = $locationId;
				if (in_array($locationId, $this->_locations)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function okToExport(array $selectedFilters): bool
	{
		return parent::okToExport($selectedFilters);
	}
}