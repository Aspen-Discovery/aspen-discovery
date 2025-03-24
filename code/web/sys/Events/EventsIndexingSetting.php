<?php
class EventsIndexingSetting extends DataObject {
	public $__table = 'events_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	public $numberOfDaysToIndex;

	private $_libraries;
//	public $lastUpdateOfAllEvents;
//	public $lastUpdateOfChangedEvents;
	public static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Events for All Locations'));

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'numberOfDaysToIndex' => [
				'property' => 'numberOfDaysToIndex',
				'type' => 'integer',
				'label' => 'Number of Days to Index',
				'description' => 'How many days in the future to index events',
				'default' => 365,
			],
			'lastUpdateOfAllEvents' => [
				'property' => 'lastUpdateOfAllEvents',
				'type' => 'timestamp',
				'label' => 'Last Update Of All Events',
				'readOnly' => 1,
			],
			'lastUpdateOfChangedEvents' => [
				'property' => 'lastUpdateOfChangedEvents',
				'type' => 'timestamp',
				'label' => 'Last Update Of Changed Events',
				'readOnly' => 1,
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
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
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

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
		}
		return $ret;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryEventsSetting();
			$library->settingSource = 'aspenEvents';
			$library->settingId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries() {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryEventSetting = new LibraryEventsSetting();

				$libraryEventSetting->settingSource = 'aspenEvents';
				$libraryEventSetting->settingId = $this->id;
				$libraryEventSetting->libraryId = $libraryId;
				$libraryEventSetting->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries() {
		//Delete links to the libraries
		$libraryEventSetting = new LibraryEventsSetting();
		$libraryEventSetting->settingSource = 'assabet';
		$libraryEventSetting->settingId = $this->id;
		return $libraryEventSetting->delete(true);
	}
}