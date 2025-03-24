<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/EventFieldSet.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLibrary.php';
require_once ROOT_DIR . '/sys/Events/EventTypeLocation.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

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
	public $eventFieldSetId;

	public $_libraries;
	public $_locations;

	public static function getObjectStructure($context = ''): array {
		$eventSets = EventFieldSet::getEventFieldSetList();
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
			],
			'titleCustomizable' => [
				'property' => 'titleCustomizable',
				'type' => 'checkbox',
				'label' => 'Title Customizable?',
				'default' => true,
				'description' => 'Can users change the title for individual events of this type?',
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
			'cover' => [
				'property' => 'cover',
				'type' => 'image',
				'label' => 'Cover',
				'maxWidth' => 280,
				'maxHeight' => 280,
				'maxLength' => 150,
				'description' => 'The default cover image for this type of event',
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
			'eventFieldSetId' => [
				'property' => 'eventFieldSetId',
				'type' => 'enum',
				'label' => 'Event Field Set',
				'description' => 'The event field set that contains the right fields to use with this event type',
				'values' => $eventSets,
				'required' => true,
			],
			'archived' => [
				'property' => 'archived',
				'type' => 'checkbox',
				'label' => 'Archive?',
				'default' => false,
				'description' => 'An archived event type will no longer show up as an option for events but can be restored later',
			]
		];
		return $structure;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	function delete($useWhere = false) : int {
		$event = new Event();
		$event->eventTypeId = $this->id;
		$event->deleted = 0;
		if ($event->count() == 0) {
			return parent::delete($useWhere);
		}
		return 0;
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->setLibraries($value);
		} else if ($name == 'locations'){
			$this->setLocations($value);
		} else
		{
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'libraries') {
			return $this->getLibraries();
		} else if ($name == 'locations') {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function setLibraries($value) {
		$this->_libraries = $value;
	}

	public function setLocations($value) {
		$this->_locations = $value;
	}

	public function getLibraries() {
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

	public function getLocations() {
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

	public function saveLibraries() {
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

	public function saveLocations() {
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


	private function clearLibraries() {
		//Unset existing library associations
		$eventTypeLibrary = new EventTypeLibrary();
		$eventTypeLibrary->eventTypeId= $this->id;
		$eventTypeLibrary->find();
		while ($eventTypeLibrary->fetch()){
			$eventTypeLibrary->delete(true);;
		}
	}

	private function clearLocations() {
		//Unset existing library associations
		$eventTypeLocation = new EventTypeLocation();
		$eventTypeLocation->eventTypeId= $this->id;
		$eventTypeLocation->find();
		while ($eventTypeLocation->fetch()) {
			$eventTypeLocation->delete(true);
		}
	}

	public static function getEventTypeList($includeArchived = false, $location = false): array {
		$typeList = [];
		$object = new EventType();
		$object->orderBy('title');
		if (!$includeArchived) {
			$object->archived = 0;
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
		$type->find(true);
		return $type->archived;
	}

	public function getFieldSetFields() {
		$fieldSet = new EventFieldSet();
		if ($this->eventFieldSetId) {
			$fieldSet->id = $this->eventFieldSetId;
			if ($fieldSet->find(true)) {
				return $fieldSet->getFieldObjectStructure();
			}
		}
		return [];
	}

}
