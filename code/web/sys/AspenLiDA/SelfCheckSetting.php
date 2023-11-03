<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckBarcode.php';

class AspenLiDASelfCheckSetting extends DataObject {
	public $__table = 'aspen_lida_self_check_settings';
	public $id;
	public $name;
	public $isEnabled;

	private $_locations;
	private $_barcodeStyles;

	static function getObjectStructure($context = ''): array {
		$locationsList = [];
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()) {
			$locationsList[$location->locationId] = $location->displayName;
		}

		$allBarcodeStyles = AspenLiDASelfCheckBarcode::getObjectStructure($context);

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
				'description' => 'The name for these settings',
				'maxLength' => 50,
				'required' => true,
			],
			'isEnabled' => [
				'property' => 'isEnabled',
				'type' => 'checkbox',
				'label' => 'Enable Self-Check',
				'description' => 'Whether or not patrons can self-check using Aspen LiDA',
				'required' => false,
			],
			'barcodeStyles' => [
				'property' => 'barcodeStyles',
				'type' => 'oneToMany',
				'label' => 'Valid Barcode Styles',
				'description' => 'Define valid barcode styles for the location',
				'keyThis' => 'selfCheckSettingsId',
				'subObjectType' => 'AspenLiDASelfCheckBarcode',
				'structure' => $allBarcodeStyles,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'note' => 'Only allow the necessary styles. Too many styles have a negative impact on device battery consumption.'
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use these settings',
				'values' => $locationsList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Administer Aspen LiDA Self-Check Settings')) {
			unset($structure['locations']);
		}

		return $structure;
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationsList = [];
			$location = new Location();
			$location->orderBy('displayName');
			if (!UserAccount::userHasPermission('Administer All Locations')) {
				$homeLibrary = Library::getPatronHomeLibrary();
				$location->libraryId = $homeLibrary->libraryId;
			}
			$location->find();
			while ($location->fetch()) {
				$locationsList[$location->locationId] = $location->displayName;
			}
			foreach ($locationsList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->lidaSelfCheckSettingId != $this->id) {
						$location->lidaSelfCheckSettingId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->lidaSelfCheckSettingId == $this->id) {
						$location->lidaSelfCheckSettingId = -1;
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}
	public function __get($name) {
		if ($name == 'locations') {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new Location();
				$obj->lidaSelfCheckSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} elseif ($name == 'barcodeStyles') {
			return $this->getBarcodeStyles();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'locations') {
			$this->_locations = $value;
		} elseif ($name == 'barcodeStyles') {
			$this->_barcodeStyles = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
			$this->saveBarcodeStyles();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
			$this->saveBarcodeStyles();
		}
		return $ret;
	}

	public function getBarcodeStyles() {
		if (!isset($this->_barcodeStyles) && $this->id) {
			$this->_barcodeStyles = [];

			$barcodeStyle = new AspenLiDASelfCheckBarcode();
			$barcodeStyle->selfCheckSettingsId = $this->id;
			if ($barcodeStyle->find()) {
				while ($barcodeStyle->fetch()) {
					$this->_barcodeStyles[$barcodeStyle->id] = clone $barcodeStyle;
				}
			}

		}
		return $this->_barcodeStyles;
	}

	public function saveBarcodeStyles() {
		if (isset ($this->_barcodeStyles) && is_array($this->_barcodeStyles)) {
			$this->saveOneToManyOptions($this->_barcodeStyles, 'selfCheckSettingsId');
			unset($this->_barcodeStyles);
		}
	}

	function getEditLink($context): string {
		return '/AspenLiDA/SelfCheckSettings?objectAction=edit&id=' . $this->id;
	}
}