<?php
/** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/SublocationPatronType.php';


class Sublocation extends DataObject {
	public $__table = 'sublocation';
	public $__displayNameColumn = 'name';
	public $id;
	public $ilsId;
	public $name;
	public $weight;
	public $locationId;
	public $isValidHoldPickupAreaILS;
	public $isValidHoldPickupAreaAspen;
	public $isValidEventLocation;

	private $_patronTypes;

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'isValidHoldPickupAreaAspen',
			'isValidHoldPickupAreaILS',
			'isValidEventLocation',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

			//Load locations for lookup values
		$allLocationsList = Location::getLocationList(false);
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries'));

		$uneditableForILS = false;
		global $library;
		$accountProfile = $library->getAccountProfile();
		if ($accountProfile !== false && $accountProfile->ils == 'polaris') {
			$uneditableForILS = true;
		}

		$patronTypeList = PType::getPatronTypeList();

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the sublocation within the database',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The display name for the sublocation',
			],
			'ilsId' => [
				'property' => 'ilsId',
				'type' => 'text',
				'label' => 'ILS Code',
				'description' => 'The ILS Code for the sublocation',
				'readOnly' => $uneditableForILS,
				'onchange' => "return AspenDiscovery.Admin.validateSublocationHoldPickupAreaAspen(this);",
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'values' => $locationList,
				'allValues' => $allLocationsList,
				'label' => 'Location',
				'description' => 'The location which the sublocation belongs to',
			],
			'isValidHoldPickupAreaILS' => [
				'property' => 'isValidHoldPickupAreaILS',
				'type' => 'checkbox',
				'label' => 'Valid Hold Pickup Area (ILS)',
				'description' => 'Whether or not this sublocation is a valid hold pickup area for the ILS',
				'readOnly' => $uneditableForILS,
				'onchange' => "return AspenDiscovery.Admin.validateSublocationHoldPickupAreaAspen(this);",
				'note' => 'Requires an ILS Id value to be provided'
			],
			'isValidHoldPickupAreaAspen' => [
				'property' => 'isValidHoldPickupAreaAspen',
				'type' => 'checkbox',
				'label' => 'Valid Hold Pickup Area (Aspen)',
				'description' => 'Whether or not this sublocation is a valid hold pickup area for Aspen',
				'note' => 'Requires an ILS Id and Valid Hold Pickup Area (ILS) to be checked',
			],
			'isValidEventLocation' => [
				'property' => 'isValidEventLocation',
				'type' => 'checkbox',
				'label' => 'Valid Event Location',
				'description' => 'Whether or not this sublocation is valid for events',
			],
			'patronTypes' => [
				'property' => 'patronTypes',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Eligible Patron Types',
				'description' => 'Define what patron types should be able to use this sublocation',
				'values' => $patronTypeList,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "patronTypes") {
			return $this->getPatronTypes();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "patronTypes") {
			$this->_patronTypes = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret !== FALSE) {
			$this->clearPatronTypes();

			$sublocationPType = new SublocationPatronType();
			$sublocationPType->sublocationId = $this->id;
			$sublocationPType->delete(true);
		}
		return $ret;
	}

	public function getPatronTypes() : ?array {
		if (!isset($this->_patronTypes) && $this->id) {
			$this->_patronTypes = [];
			$patronTypeLink = new SublocationPatronType();
			$patronTypeLink->sublocationId = $this->id;
			$patronTypeLink->find();
			while ($patronTypeLink->fetch()) {
				$this->_patronTypes[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
			}
		}
		return $this->_patronTypes;
	}

	public function savePatronTypes() : void {
		if (isset($this->_patronTypes) && is_array($this->_patronTypes)) {
			$this->clearPatronTypes();

			foreach ($this->_patronTypes as $patronTypeId) {
				$link = new SublocationPatronType();

				$link->sublocationId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_patronTypes);
		}
	}

	public function clearPatronTypes() : void {
		//Delete sublocations to the patron types
		$link = new SublocationPatronType();
		$link->sublocationId = $this->id;
		$link->delete(true);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/Admin/Sublocations?objectAction=edit&id=' . $this->id;
	}
}
