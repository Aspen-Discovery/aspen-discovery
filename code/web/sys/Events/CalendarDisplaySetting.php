<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';
require_once ROOT_DIR . '/sys/Events/EventFieldCalendarOptions.php';
require_once ROOT_DIR . '/sys/Events/CalendarDisplaySettingLibrary.php';

class CalendarDisplaySetting extends DataObject {
	public $__table = 'calendar_display_settings';
	public $id;
	public $name;
	public $calendarTitle;
	public $cover;
	public $altText;
	public $footer;
	public $fullMonthName;

	private $_libraries;
	/** @var EventFieldCalendarOptions[] */
	private $_eventFields;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$eventFieldCalendarOptions = EventFieldCalendarOptions::getObjectStructure($context);
		unset($eventFieldCalendarOptions['weight']);
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
			],
			'calendarTitle' => [
				'property' => 'calendarTitle',
				'type' => 'text',
				'label' => 'Calendar Title',
				'description' => 'A title for the calendar displayed',
				'default' => 'Events Calendar',
			],
			'cover' => [
				'property' => 'cover',
				'type' => 'image',
				'label' => 'Header Image',
				'thumbWidth' => 750,
				'maxWidth' => 1170,
				'maxHeight' => 250,
				'description' => 'Calendar header image (1140 x 100px max)',
				'hideInLists' => true,
			],
			'altText' => [
				'property' => 'altText',
				'type' => 'text',
				'label' => 'Header image description',
				'description' => 'A header image description to use for alt-text',
			],
			'fullMonthName' => [
				'property' => 'fullMonthName',
				'type' => 'checkbox',
				'label' => 'Show Full Month Names',
				'description' => 'Show full names of months on calendar',
			],
			'eventFields' => [
				'property' => 'eventFields',
				'type' => 'oneToMany',
				'label' => 'Fields To Display on Calendars',
				'description' => 'The fields for event information to show in the calendar',
				'keyThis' => 'id',
				'keyOther' => 'calendarDisplaySettingId',
				'subObjectType' => 'EventFieldCalendarOptions',
				'structure' => $eventFieldCalendarOptions,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'footer' => [
				'property' => 'footer',
				'type' => 'html',
				'label' => 'Footer',
				'description' => 'The footer for the calendar',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveEventFields();
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveEventFields();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "eventFields") {
			return $this->getEventFields();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "eventFields") {
			$this->_eventFields = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && $hardDelete && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearEventFields();
		}
		return $ret;
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$libraryLink = new CalendarDisplaySettingLibrary();
			$libraryLink->calendarDisplaySettingId = $this->id;
			$libraryLink->find();
			while ($libraryLink->fetch()) {
				$this->_libraries[$libraryLink->libraryId] = clone $libraryLink;
			}
		}
		return $this->_libraries;
	}

	public function getEventFields() : ?array {
		if (!isset($this->_eventFields) && $this->id) {
			$this->_eventFields = [];
			$formField = new EventFieldCalendarOptions();
			$formField->calendarDisplaySettingId = $this->id;
			$formField->orderBy('weight');
			$formField->find();
			while ($formField->fetch()) {
				$this->_eventFields[$formField->id] = clone $formField;
			}
		}
		return $this->_eventFields;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$obj = new CalendarDisplaySettingLibrary();
				$obj->calendarDisplaySettingId = $this->id;
				$obj->libraryId = $libraryId;
				if (in_array($libraryId, $this->_libraries)) {
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

	public function saveEventFields() : void {
		if (isset($this->_eventFields) && is_array($this->_eventFields)) {
			$this->saveOneToManyOptions($this->_eventFields, 'calendarDisplaySettingId');
			unset($this->eventFields);
		}
	}

	private function clearLibraries() : void {
		//Delete links to the libraries
		$libraryLink = new CalendarDisplaySettingLibrary();
		$libraryLink->calendarDisplaySettingId = $this->id;
		$libraryLink->delete(true);
	}

	private function clearEventFields() : void {
		$this->clearOneToManyOptions('EventFieldCalendarOptions', 'calendarDisplaySettingId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->eventFields = [];
	}

}