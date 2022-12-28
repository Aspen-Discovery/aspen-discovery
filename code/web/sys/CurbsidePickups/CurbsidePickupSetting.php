<?php

class CurbsidePickupSetting extends DataObject {
	public $__table = 'curbside_pickup_settings';
	public $id;
	public $name;
	public $alwaysAllowPickups;
	public $allowCheckIn;
	public $timeAllowedBeforeCheckIn;
	public $useNote;
	public $noteLabel;
	public $noteInstruction;
	public $instructionSchedule;
	public $instructionNewPickup;
	public $contentSuccess;
	public $curbsidePickupInstructions;
	public $contentCheckedIn;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
				'description' => 'A name for the settings',
				'maxLength' => 50,
			],
			'alwaysAllowPickups' => [
				'property' => 'alwaysAllowPickups',
				'type' => 'checkbox',
				'label' => 'Allow patrons to always schedule curbside pickups',
				'description' => 'Whether or not patrons can schedule curbside pickups even if they do not have holds ready.',
				'default' => 1,
			],
			'allowCheckIn' => [
				'property' => 'allowCheckIn',
				'type' => 'checkbox',
				'label' => 'Allow patrons to use "Mark Arrived" as part of the plugin workflow',
				'description' => 'Whether or not patrons can check-in.',
				'default' => 1,
				'note' => 'If unchecked, you should instead specify instructions such as calling the front desk to check-in',
			],
			'timeAllowedBeforeCheckIn' => [
				'property' => 'timeAllowedBeforeCheckIn',
				'type' => 'integer',
				'label' => 'Time allowed (in minutes) before a pickup that patrons can see check-in instructions',
				'description' => '',
				'note' => 'If the pickup is marked as "Staged & Ready" in Koha, the instructions will display regardless of this time',
				'default' => 15,
			],
			'useNote' => [
				'property' => 'useNote',
				'type' => 'checkbox',
				'label' => 'Allow patrons to leave a note for their pickup',
				'description' => 'Whether or not patrons can leave a note',
				'default' => 1,
			],
			'noteLabel' => [
				'property' => 'noteLabel',
				'type' => 'text',
				'label' => 'Note Field Label',
				'description' => 'The label for the Note field',
				'maxLength' => 50,
				'hideInLists' => true,
				'default' => 'Note',
			],
			'noteInstruction' => [
				'property' => 'noteInstruction',
				'type' => 'text',
				'label' => 'Note Field Instructions',
				'description' => 'The instructions for the Note field, i.e. if you need them to include specific information.',
				'maxLength' => 255,
				'hideInLists' => true,
			],
			'instructionSchedule' => [
				'property' => 'instructionSchedule',
				'type' => 'html',
				'label' => 'Content for the main curbside pickup page',
				'description' => 'General information about the curbside pickup service for the patron',
				'hideInLists' => true,
			],
			'instructionNewPickup' => [
				'property' => 'instructionNewPickup',
				'type' => 'html',
				'label' => 'Content for scheduling a new curbside pickup',
				'description' => 'Instructions for the patron as they schedule a curbside pickup',
				'hideInLists' => true,
			],
			'contentSuccess' => [
				'property' => 'contentSuccess',
				'type' => 'html',
				'label' => 'Content for confirmation page',
				'description' => 'General information and instruction for the next steps in the pickup process',
				'hideInLists' => true,
			],
			'curbsidePickupInstructions' => [
				'property' => 'curbsidePickupInstructions',
				'type' => 'textarea',
				'label' => 'Patron instructions for curbside pickup',
				'description' => 'General instructions to patrons when checking-in for picking up curbside',
				'hideInLists' => true,
				'note' => 'Use Location Settings to specify instructions per branch',
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Administer Curbside Pickup')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->curbsidePickupSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->curbsidePickupSettingId != $this->id) {
						$library->curbsidePickupSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->curbsidePickupSettingId == $this->id) {
						$library->curbsidePickupSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	function getEditLink($context): string {
		return '/CurbsidePickup/Settings?objectAction=edit&id=' . $this->id;
	}
}