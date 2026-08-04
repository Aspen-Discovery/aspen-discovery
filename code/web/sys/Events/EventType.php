<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventFieldSet.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLibrary.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLocation.php';
require_once ROOT_DIR . '/sys/Events/Event.php';
require_once ROOT_DIR . '/sys/Events/EventTypeAttendeeCategory.php';

class EventType extends DataObject {
	public $__table = 'event_type';
	public $id;
	public $title;
	public $titleCustomizable;
	public $description;
	public $descriptionCustomizable;
	public $cover;
	public $coverCustomizable;
	public $eventLength; // in hours
	public $lengthCustomizable;
	public $archived;
	public $eventInformationFieldSetId;
	public $eventRegistrationFieldSetId;
	public $includeInReports;
	public $displayEventBranchOnThumbnail;
	public $displayEventBranchOnThumbnailCustomizable;
	public $waitingListInviteExpiryHours;

	public $_libraries;
	public $_locations;
	public $_eventTypeAttendeeCategories;

	static $_objectStructure = [];

	public function getNumericColumnNames(): array {
		return [
			'titleCustomizable',
			'descriptionCustomizable',
			'coverCustomizable',
			'eventLength',
			'lengthCustomizable',
			'archived',
			'includeInReports',
			'displayEventBranchOnThumbnailCustomizable',
			'waitingListInviteExpiryHours',
		];
	}

	static function getObjectStructure(string $context = ''): array {
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$eventInformationSets = EventFieldSet::getEventInformationFieldSetList(); 
		$eventRegistrationSets = EventFieldSet::getEventRegistrationFieldSetList(); 
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The default title for this type of event',
				'maxLength' => 50,
				'required' => true,
			],
			'titleCustomizable' => [
				'property' => 'titleCustomizable',
				'type' => 'checkbox',
				'label' => 'Title Customizable?',
				'default' => true,
				'description' => 'Can users change the title for individual events of this type?',
			],
			'editFormInstructions' => [
				'property' => 'editFormInstructions',
				'type' => 'translatableTextBlock',
				'label' => 'Instructions',
				'description' => 'Instructions for administrators creating events of this type.',
				'defaultTextFile' => '',
				'hideInLists' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'html',
				'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><br><div><span><sub><sup>',
				'label' => 'Description',
				'description' => 'The default description for this type of event',
			],
			'descriptionCustomizable' => [
				'property' => 'descriptionCustomizable',
				'type' => 'checkbox',
				'label' => 'Description Customizable?',
				'default' => true,
				'description' => 'Can users change the description for individual events of this type?',
			],
			'displayEventBranchOnThumbnail' => [
				'property' => 'displayEventBranchOnThumbnail',
				'type' => 'checkbox',
				'label' => 'Display Event Branch on Thumbnail',
				'default' => false,
				'description' => 'Whether or not to display the event branch on the thubmnail image. Can be overridden for specific events.',
			],
			'displayEventBranchOnThumbnailCustomizable' => [
				'property' => 'displayEventBranchOnThumbnailCustomizable',
				'type' => 'checkbox',
				'label' => 'Display Event Branch on Thumbnail Customizable?',
				'default' => true,
				'description' => 'Can users change the display the event branch on the thubmnail image setting for individual events of this type?',
			],
			'cover' => [
				'property' => 'cover',
				'type' => 'image',
				'label' => 'Cover',
				'maxWidth' => 280,
				'maxHeight' => 280,
				'maxLength' => 150,
				'description' => 'The default cover image for this type of event',
				'path' => "$coverPath/aspenEvents/",
				'hideInLists' => true,
			],
			'coverCustomizable' => [
				'property' => 'coverCustomizable',
				'type' => 'checkbox',
				'label' => 'Cover Customizable?',
				'default' => true,
				'description' => 'Can users change the cover for individual events of this type?',
			],
			'eventLength' => [
				'property' => 'eventLength',
				'type' => 'duration',
				'label' => 'Event Length',
				'description' => 'The default event length for this type of event',
			],
			'lengthCustomizable' => [
				'property' => 'lengthCustomizable',
				'type' => 'checkbox',
				'label' => 'Length Customizable?',
				'default' => true,
				'description' => 'Can users change the event length for individual events of this type?',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this type',
				'values' => $libraryList,
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this type',
				'values' => $locationList,
			],
			'attendeeCategories' => [
				'property' => 'attendeeCategories',
				'type' => 'oneToMany',
				'label' => 'Attendee Categories',
				'description' => 'Define attendee categories and their limits for this event type',
				'keyThis' => 'id',
				'keyOther' => 'eventTypeId',
				'subObjectType' => 'EventTypeAttendeeCategory',
				'structure' => EventTypeAttendeeCategory::getObjectStructure(),
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'eventFieldSets' => [
				'property' => 'eventFieldSets',
				'type' => 'section',
				'label' => 'Event Field Sets',
				'expandByDefault' => true,
				'properties' => [
					'eventInformationFieldSetId' => [
						'property' => 'eventInformationFieldSetId',
						'type' => 'enum',
						'label' => 'Additional Information',
						'description' => 'The event field set that contains the right information fields for staff to use when adding information to this type of event.',
						'values' => $eventInformationSets,
						'required' => true,
					],
					'eventRegistrationFieldSetId' => [
						'property' => 'eventRegistrationFieldSetId',
						'type' => 'enum',
						'label' => 'Registration form',
						'description' => 'The event field set that contains the right registration fields for patrons to fill in when they register to an event of this type.',
						'values' => $eventRegistrationSets,
						'required' => true,
					],
				]
			],
			'includeInReports' => [
				'property' => 'includeInReports',
				'type' => 'checkbox',
				'label' => 'Include in Reports?',
				'default' => true,
				'description' => 'If unchecked, events of this type will not be shown in events reports',
			],
			'waitingListInviteExpiryHours' => [
				'property' => 'waitingListInviteExpiryHours',
				'type' => 'integer',
				'label' => 'Waiting List Invite Expiry (Hours)',
				'default' => 24,
				'min' => 1,
				'max' => 168,
				'description' => 'How many hours a user has to register after being invited from the waiting list',
			],
			'archived' => [
				'property' => 'archived',
				'type' => 'checkbox',
				'label' => 'Archive?',
				'default' => false,
				'description' => 'An archived event type will no longer show up as an option for events but can be restored later',
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveAttendeeCategories();
			$this->saveTextBlockTranslations('editFormInstructions');
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveAttendeeCategories();
			$this->saveTextBlockTranslations('editFormInstructions');
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$event = new Event();
		$event->eventTypeId = $this->id;
		$event->deleted = "0";
		if ($event->count() == 0) {
			//Delete links to libraries and locations as well
			$this->clearLocations();
			$this->clearLibraries();
			return parent::delete($useWhere, $hardDelete);
		} else{
			//Cannot delete event types that have events created for them
		}
		return 0;
	}

	public function getDeletionBlockInformation(array $structure) : array {
		$deletionBlock = parent::getDeletionBlockInformation($structure);
		if ($deletionBlock['preventDeletion']) {
			return $deletionBlock;
		}else{
			$event = new Event();
			$event->eventTypeId = $this->id;
			$event->deleted = "0";
			if ($event->count() > 0) {
				$deletionBlock['preventDeletion'] = true;
				$deletionBlock['message'] = translate(['text' => 'This Event Type is in use by one or more events that have not been deleted. Please delete the events prior to deleting this event type.', 'isAdminFacing'=>true]);
			}
		}
		return $deletionBlock;
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->setLibraries($value);
		} else if ($name == 'locations'){
			$this->setLocations($value);
		} else if ($name == 'attendeeCategories') {
			$this->_eventTypeAttendeeCategories = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'libraries') {
			return $this->getLibraries();
		} else if ($name == 'locations') {
			return $this->getLocations();
		} else if ($name == 'attendeeCategories') {
			return $this->getEventTypeAttendeeCategories();
		} else {
			return parent::__get($name);
		}
	}

	public function setLibraries($value) : void {
		$this->_libraries = $value;
	}

	public function setLocations($value) : void {
		$this->_locations = $value;
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new EventTypeLibrary();
			$library->eventTypeId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = clone($library);
			}
		}
		return $this->_libraries;
	}

	public function getLocations() : ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new EventTypeLocation();
			$location->eventTypeId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$this->_locations[$location->locationId] = clone($location);
			}
		}
		return $this->_locations;
	}

	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $library) {
				$eventTypeLibrary = new EventTypeLibrary();
				$eventTypeLibrary->libraryId = $library;
				$eventTypeLibrary->eventTypeId = $this->id;
				$eventTypeLibrary->update();
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset($this->_locations) && is_array($this->_locations)) {
			$this->clearLocations();

			foreach ($this->_locations as $location) {
				$eventTypeLocation = new EventTypeLocation();
				$eventTypeLocation->locationId = $location;
				$eventTypeLocation->eventTypeId = $this->id;
				$eventTypeLocation->update();
			}
			unset($this->_locations);
		}
	}


	private function clearLibraries() : void {
		//Unset existing library associations
		$eventTypeLibrary = new EventTypeLibrary();
		$eventTypeLibrary->eventTypeId= $this->id;
		$eventTypeLibrary->find();
		while ($eventTypeLibrary->fetch()){
			$eventTypeLibrary->delete(true);
		}
	}

	private function clearLocations() : void {
		//Unset existing library associations
		$eventTypeLocation = new EventTypeLocation();
		$eventTypeLocation->eventTypeId= $this->id;
		$eventTypeLocation->find();
		while ($eventTypeLocation->fetch()) {
			$eventTypeLocation->delete(true);
		}
	}

	public function getEventTypeAttendeeCategories(): array {
		if (isset($this->_eventTypeAttendeeCategories)) {
			return $this->_eventTypeAttendeeCategories ?? [];
		}
		$this->_eventTypeAttendeeCategories = [];
		$eventTypeAttendeeCategory = new EventTypeAttendeeCategory();
		$eventTypeAttendeeCategory->eventTypeId = $this->id;
		$eventTypeAttendeeCategory->find();
		while ($eventTypeAttendeeCategory->fetch()) {
			$this->_eventTypeAttendeeCategories[$eventTypeAttendeeCategory->id] = clone($eventTypeAttendeeCategory);
		}
		return $this->_eventTypeAttendeeCategories;
	}

	public function saveAttendeeCategories(): void {
		if (isset($this->_eventTypeAttendeeCategories) && is_array($this->_eventTypeAttendeeCategories)) {
			$this->saveOneToManyOptions($this->_eventTypeAttendeeCategories, 'eventTypeId');
			unset($this->_eventTypeAttendeeCategories);
		}
	}

	public static function getEventTypeList($includeArchived = false, $location = false, $forReports = false): array {
		$typeList = [];
		$object = new EventType();
		$object->orderBy('title');
		if (!$includeArchived) {
			$object->archived = 0;
		}
		if ($forReports) {
			$object->includeInReports = 1;
		}
		if ($location) {
			$validTypeIdsForLocation = self::getEventTypeIdsForLocation($location);
			$object->whereAddIn('id', $validTypeIdsForLocation, true);
		}
		$object->find();
		while ($object->fetch()) {
			$label = $object->title;
			$typeList[$object->id] = $label;
		}
		return $typeList;
	}

	public static function getEventTypeIdsForLocation(string $locationId, $includeArchived = false): array {
		$typeIds = [];
		$typeLocation = new EventTypeLocation();
		$typeLocation->locationId = $locationId;
		$typeLocation->find();
		while ($typeLocation->fetch()) {
			if (!$includeArchived && !EventType::isArchived($typeLocation->eventTypeId)) {
				$typeIds[] = $typeLocation->eventTypeId;
			}
		}
		return $typeIds;
	}

	public static function getLocationIdsForEventType(string $eventTypeId, $includeArchived = false): array {
		$locationIds = [];
		$typeLocation = new EventTypeLocation();
		$typeLocation->eventTypeId = $eventTypeId;
		$typeLocation->find();
		while ($typeLocation->fetch()) {
			if (!$includeArchived && !EventType::isArchived($typeLocation->eventTypeId)) {
				$locationIds[] = $typeLocation->locationId;
			}
		}
		return $locationIds;
	}

	public static function getTypeName(string $typeId): string {
		$type = new EventType();
		$type->id = $typeId;
		$type->find(true);
		return $type->title;
	}

	public static function isArchived(string $typeId): bool {
		$type = new EventType();
		$type->id = $typeId;
		if ($type->find(true)) {
			return $type->archived;
		}else{
			//This has been deleted, treat it as archived
			return true;
		}

	}

	public function getFieldSetFieldsByUse($fieldSetUse) : array {
		$fieldSet = new EventFieldSet();

		if ($fieldSetUse ==  1 && $this->eventInformationFieldSetId) {
			$fieldSet->id = $this->eventInformationFieldSetId;
		}

		if ($fieldSetUse == 2 && $this->eventRegistrationFieldSetId) {
			$fieldSet->id = $this->eventRegistrationFieldSetId;
		}

		if ($fieldSet->find(true)) {
			return $fieldSet->getFieldObjectStructure();
		}

		return [];
	}

}
