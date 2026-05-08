<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';

class Holiday extends DataObject {
	public $__table = 'holiday';
	public $__displayNameColumn = 'displayName';
	public $displayName;
	public $id;                    // int(11)  not_null primary_key auto_increment
	public $libraryId;             // int(11)
	public $date;                  // date
	public $name;                  // varchar(100)
	public $locationId; 			// int(11)
	public $closed;					// tinyint(1)
	public $open;					// varchar(10)
	public $close;					// varchar(10)
	public $locationIds = [];      // group locations

	public function getNumericColumnNames(): array {
		return [
			'libraryId',
			'locationId',
			'closed',
		];
	}


	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(false);
		$locationList = self::getHolidayLocationsList();

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the holiday within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library',
			],
			'date' => [
				'property' => 'date',
				'type' => 'date',
				'label' => 'Date',
				'description' => 'The date of a holiday.',
				'required' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Holiday Name',
				'description' => 'The name of a holiday',
			],
			'closed' => [
				'property' => 'closed',
				'type' => 'checkbox',
				'label' => 'Closed',
				'default' => true,
				'description' => 'Check to indicate that the library is closed on this day.',
			],
			'open' => [
				'property' => 'open',
				'type' => 'time',
				'label' => 'Opening Hour',
				'description' => 'The opening hour. Use 24 hour format HH:MM, eg: 08:30',
			],
			'close' => [
				'property' => 'close',
				'type' => 'time',
				'label' => 'Closing Hour',
				'description' => 'The closing hour. Use 24 hour format HH:MM, eg: 16:30',
			],
			'locationIds' => [
				'property' => 'locationIds',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxList',
				'values' => $locationList,
				'label' => 'Locations',
				'description' => 'Locations this holiday or special-hours entry applies to.',
				'useKeysForValues' => true,
				'default' => array_keys($locationList),
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public static function getHolidayLocationsList(?int $libraryId = null): array {
		if ($libraryId === null) {
			$libraryId = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
		}
		$location = new Location();
		$location->selectAdd();
		$location->selectAdd('locationId');
		$location->selectAdd('displayName');
		$location->showInHolidayHoursTable = 1;
		$location->libraryId = $libraryId;
		$location->orderBy('displayName');
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->locationId] = $location->displayName;
		}
		return $locationList;
	}

	public function fetch(): bool|DataObject|null {
		$result = parent::fetch();
		if (!empty($this->name)) {
			$this->displayName = $this->name . ' (' . $this->date . ')';
		} else {
			$this->displayName = $this->date;
		}
		return $result;
	}

	public function insert(string $context = ''): int|bool {
		$this->clearStoredTime();
		return parent::insert($context);
	}

	public function update(string $context = ''): int|bool {
		$this->clearStoredTime();
		return parent::update($context);
	}

	private function clearStoredTime(): void {
		// DataObject::update() skips null string fields, so we manually clear stored times.
		if ($this->open === null) {
			$this->setProperty('open', '', ['type' => 'time']);
		}
		if ($this->close === null) {
			$this->setProperty('close', '', ['type' => 'time']);
		}
	}

}
