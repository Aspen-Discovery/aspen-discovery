<?php

class CurbsidePickupSetting extends DataObject {
	public $__table = 'curbside_pickup_settings';
	public $id;
	public $name;
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
		$currentId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$libraryList = self::getLibraryListWithAssignments($currentId);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of this setting.',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for this setting.',
				'maxLength' => 50,
				'required' => true
			],
			'allowCheckIn' => [
				'property' => 'allowCheckIn',
				'type' => 'checkbox',
				'label' => 'Allow Patrons to Use "Mark Arrived" as Part of the Plugin Workflow',
				'description' => 'Whether or not patrons can check-in to indicate their arrival to the library.',
				'default' => 1,
				'note' => 'If unchecked, you should instead specify instructions (e.g., "Please call the front desk to check in.").',
				'onchange' => 'return AspenDiscovery.CurbsidePickup.updateCurbsidePickupSettingsFields();',
			],
			'curbsidePickupInstructions' => [
				'property' => 'curbsidePickupInstructions',
				'type' => 'textarea',
				'label' => 'Patron Instructions for Curbside Pickup',
				'description' => 'General instructions shown to patrons during check-in for curbside pickups at the selected libraries.',
				'maxLength' => 255,
				'note' => 'For custom instructions per location/branch, edit this mirrored field under the ILS/Account Integration section of the <a href="/Admin/Locations" target="_blank">Location settings</a>.',
			],
			'timeAllowedBeforeCheckIn' => [
				'property' => 'timeAllowedBeforeCheckIn',
				'type' => 'integer',
				'label' => 'Check-In Instruction Lead Time (Minutes)',
				'description' => 'The number of minutes before a scheduled pickup when patrons can view check-in instructions.',
				'note' => 'Set to -1 to display at all times. If the pickup is marked as "Staged & Ready," the instructions will display regardless of this set time.',
				'default' => -1,
			],
			'useNote' => [
				'property' => 'useNote',
				'type' => 'checkbox',
				'label' => 'Allow Patrons to Leave a Note for Their Pickup',
				'description' => 'Whether or not patrons can leave a note regarding their pickup.',
				'default' => 1,
			],
			'noteLabel' => [
				'property' => 'noteLabel',
				'type' => 'text',
				'label' => 'Note Field Label',
				'description' => 'The label for the Note field in the scheduling pickup modal.',
				'maxLength' => 75,
				'default' => 'Note',
			],
			'noteInstruction' => [
				'property' => 'noteInstruction',
				'type' => 'text',
				'label' => 'Note Field Instructions',
				'description' => 'The instructions for the Note field in the scheduling pickup modal.',
				'maxLength' => 255,
			],
			'instructionSchedule' => [
				'property' => 'instructionSchedule',
				'type' => 'html',
				'label' => 'Subheading Content on the Curbside Pickup Page',
				'description' => 'General information about the curbside pickup service for patrons.',
			],
			'instructionNewPickup' => [
				'property' => 'instructionNewPickup',
				'type' => 'html',
				'label' => 'Subheading Content on the Scheduling Curbside Pickup Modal',
				'description' => 'Instructions for patrons as they schedule curbside pickups.',
			],
			'contentSuccess' => [
				'property' => 'contentSuccess',
				'type' => 'html',
				'label' => 'Message for the Patron after Successfully Scheduling a Curbside Pickup ',
				'description' => 'Information about the next steps in the process after the patron has successfully scheduled a curbside pickup.',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings in Aspen.',
				'values' => $libraryList,
				'note' => 'This setting dictates which library catalogs allow patrons to schedule curbside pickups in Aspen. However, whether a library actually allows curbside pickups is configured within the respective ILS.
							If you select a library that is already assigned to another setting, it will be automatically removed from that setting and assigned to this one.'
			],
		];

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
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = ''): bool {
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

	/**
	 * Get the library list with assignment information appended to library names.
	 *
	 * @param int $currentId The ID of the current curbside pickup setting.
	 * @return array The library list with assignment information.
	 */
	private static function getLibraryListWithAssignments(int $currentId): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$libraryAssignments = [];

		foreach (array_keys($libraryList) as $libraryId) {
			$libraryCheck = new Library();
			$libraryCheck->libraryId = $libraryId;
			if ($libraryCheck->find(true) && $libraryCheck->curbsidePickupSettingId > 0 && $libraryCheck->curbsidePickupSettingId != $currentId) {
				$settingCheck = new CurbsidePickupSetting();
				$settingCheck->id = $libraryCheck->curbsidePickupSettingId;
				if ($settingCheck->find(true)) {
					$libraryAssignments[$libraryId] = $settingCheck->name;
				}
			}
		}

		foreach ($libraryAssignments as $libraryId => $settingName) {
			$libraryList[$libraryId] .= ' (Assigned to "' . $settingName . '" Setting)';
		}

		return $libraryList;
	}

	public function saveLibraries(): void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					// Apply the scope to this library.
					if ($library->curbsidePickupSettingId != $this->id) {
						$library->curbsidePickupSettingId = $this->id;
						$library->update();
					}
				} else {
					// Do not apply the scope to this library; only change if it was applied to the scope.
					if ($library->curbsidePickupSettingId == $this->id) {
						$library->curbsidePickupSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	function getEditLink(): string {
		return '/CurbsidePickup/Settings?objectAction=edit&id=' . $this->id;
	}
}