<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/Event.php';

class EventInstance extends DataObject {
	public $__table = 'event_instance';
	public $id;
	public $eventId;
	public $date;
	public $time;
	public $length;
	public $sublocationId;
	public $status;
	public $note;
	public $numberOfSeats;

	public $dateUpdated;
	public $deleted;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$sublocationList = Location::getEventSublocations(null);
		$sublocationList = [""] + $sublocationList;
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'eventId' => [
				'property' => 'eventId',
				'type' => 'text',
				'label' => 'Event Name',
				'description' => 'A name for the field',
				'hiddenByDefault' => true,
				'hideInLists' => true,
			],
			'date' => [
				'property' => 'date',
				'type' => 'date',
				'label' => 'Event Date',
				'description' => 'The event date',
			],
			'time' => [
				'property' => 'time',
				'type' => 'time',
				'label' => 'Event Time',
				'description' => 'The event Time',
			],
			'length' => [
				'property' => 'length',
				'type' => 'integer',
				'label' => 'Length (Minutes)',
				'description' => 'The event length in minutes',
			],
			'sublocationId' => [
				'property' => 'sublocationId',
				'type' => 'enum',
				'label' => 'Sublocation',
				'description' => 'Sublocation of the event',
				'values' => $sublocationList,
			],
			'note' => [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Note',
				'description' => 'A note for this specific instance',
			],
			'numberOfSeats' => [
				'property' => 'numberOfSeats',
				'type' => 'integer',
				'label' => 'Number of Seats Override',
				'description' => 'Override capacity for this specific instance. Leave blank to use event default.',
				'min' => 0,
				'max' => 1000,
			],
			'status' => [
				'property' => 'status',
				'type' => 'checkbox',
				'label' => 'Active',
				'default' => 1,
				'description' => 'Whether the event is active or cancelled',
			],
			'dateUpdated' => [
				'property' => 'dateUpdated',
				'label' => 'Date last updated',
				'type' => 'hidden',
				'hideInLists' => true,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getNumericColumnNames(): array {
		return [
			'length',
			'dateUpdated',
			'numberOfSeats',
		];
	}

	public function update(string $context = '') : int|bool {
		$this->dateUpdated = time();
		if (isset($this->_changedFields) && count($this->_changedFields) > 0) {
			$this->_changedFields[] = 'dateUpdated';
		}
		return parent::update();
	}

	public function insert(string $context = '') : int|bool {
		$this->dateUpdated = time();
		return parent::insert();
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		if (!$useWhere) {
			$this->deleted = 1;
			$this->dateUpdated = time();
			return parent::update();
		} else {
			return parent::delete($useWhere, $hardDelete);
		}
	}

	public function fetch(): bool|DataObject|null {
		$return = parent::fetch();
		if ($return) {
			if (empty($this->sublocationId)) {
				$event = $this->getParentEvent();
				$this->sublocationId = $event->sublocationId;
			}
		}
		return $return;
	}

	function getParentEvent() : Event {
		$event = new Event();
		$event->id = $this->eventId;
		$event->find(true);
		return $event;
	}

	/** @noinspection PhpUnused */
	function getLocation() : string {
		$event = $this->getParentEvent();
		$location = new Location();
		$location->locationId = $event->locationId;
		$location->find(true);
		return $location->displayName;
	}

	/** @noinspection PhpUnused */
	function getSublocation() : string {
		$event = $this->getParentEvent();
		$sublocations = Location::getEventSublocations($event->locationId);
		if ($event->sublocationId) {
			$sublocation = $sublocations[$event->sublocationId];
		}
		return $sublocation ?? '';
	}

	function getSeries($onlyFuture = false) : array {
		$series = [];
		$eventInstances = new EventInstance();
		$eventInstances->eventId = $this->eventId;
		$eventInstances->deleted = 0;
		if ($onlyFuture) {
			$escapedDate = $eventInstances->escape($this->date);
			$escapedTime = $eventInstances->escape($this->time);
			$eventInstances->whereAdd("date > " . $escapedDate . " OR date = " . $escapedDate . " AND time > " . $escapedTime);
		} else {
			$eventInstances->whereAdd("id != " . $this->id);
		}
		$eventInstances->orderBy('date');
		$eventInstances->find();
		while ($eventInstances->fetch()) {
			$series[$eventInstances->id] = clone($eventInstances);
		}
		return $series;
	}

	function getUpcomingInstanceCount() {
		$event = $this->getParentEvent();
		return $event->getInstanceCount();
	}

	public function getEffectiveNumberOfSeats(): ?int {
		if ($this->numberOfSeats !== null && $this->numberOfSeats > 0) {
			return $this->numberOfSeats;
		}
		$event = $this->getParentEvent();
		if ($event->numberOfSeats === null || $event->numberOfSeats == 0) {
			return null;
		}
		return $event->numberOfSeats;
	}

	public function getRegistrationCount(): int {
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $this->id;
		return $registration->count();
	}

	public function getAvailableSeats(): ?int {
		$capacity = $this->getEffectiveNumberOfSeats();
		if ($capacity === null) {
			return null;
		}
		return max(0, $capacity - $this->getRegistrationCount());
	}

	public function hasAvailableSeats(int $requestedSeats = 1): bool {
		$capacity = $this->getEffectiveNumberOfSeats();
		if ($capacity === null) {
			return true;
		}
		$available = $this->getAvailableSeats();
		return $available >= $requestedSeats;
	}

}