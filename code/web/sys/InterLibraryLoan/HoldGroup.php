<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroupLocation.php';

class HoldGroup extends DataObject {
	public $__table = 'hold_groups';
	public $id;
	public $name;

	protected $_locations;
	protected $_locationCodes;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$locationList = Location::getLocationList(false);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the Hold Group',
				'maxLength' => 50,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that make up this hold group',
				'values' => $locationList,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array {
		return ['name'];
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$holdGroupLocation = new HoldGroupLocation();
			$holdGroupLocation->holdGroupId = $this->id;
			$holdGroupLocation->delete(true);
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * @return null|int[]
	 */
	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new HoldGroupLocation();
			$obj->holdGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	/**
	 * @return null|string[]
	 */
	public function getLocationCodes() : ?array {
		if (!isset($this->_locationCodes) && $this->id) {
			$this->_locationCodes = [];
			$locationIds = $this->getLocations();
			foreach ($locationIds as $locationId) {
				$location = new Location();
				$location->locationId = $locationId;
				if ($location->find(true)) {
					$this->_locationCodes[] = $location->code;
				}
			}
		}
		return $this->_locationCodes;
	}

	public function __set($name, $value) {
		if ($name == "locations") {
			$this->setLocations($value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function setLocations(?array $locations) : void {
		$this->_locations = $locations;
	}

	public function saveLocations() : void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Hold Groups'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new HoldGroupLocation();
				$obj->holdGroupId = $this->id;
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
}
