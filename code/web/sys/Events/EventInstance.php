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
	public $waitingList;
	public $waitingListNumberOfSeats;

	public $dateUpdated;
	public $deleted;

	private $_parentEvent = null;

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
			'waitingList' => [
				'property' => 'waitingList',
				'type' => 'checkbox',
				'label' => 'Waiting List Override',
				'description' => 'Override whether waiting list is enabled for this specific instance.',
			],
			'waitingListNumberOfSeats' => [
				'property' => 'waitingListNumberOfSeats',
				'type' => 'integer',
				'label' => 'Number of Seats on Waiting List Override',
				'description' => 'Override waiting list capacity for this specific instance.',
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
			'waitingListNumberOfSeats',
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

	public function delete(bool $useWhere = false, bool $hardDelete = false, bool $suppressIndividualNotifications = false) : bool|int {
		if ($useWhere) {
			throw new InvalidArgumentException('EventInstance::delete does not support $useWhere = true. Delete instances individually.');
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';

		$shouldNotify = !$suppressIndividualNotifications && $this->isUpcoming();

		$affectedUsersByStatus = $shouldNotify
			? UserAspenEventInstanceRegistration::getUsersGroupedByStatusForInstance((int)$this->id)
			: [];

		$this->deleted = 1;
		$this->dateUpdated = time();
		$softDeleteResult = parent::update();
		if ($softDeleteResult === false) {
			return false;
		}

		UserAspenEventInstanceRegistration::deleteAllForEventInstance((int)$this->id);

		if ($shouldNotify) {
			require_once ROOT_DIR . '/services/EventRegistrationService.php';
			EventRegistrationService::sendCancellationNotificationEmails([$this], $affectedUsersByStatus);
		}

		return $softDeleteResult;
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
		if ($this->_parentEvent !== null && $this->_parentEvent->id == $this->eventId) {
			return $this->_parentEvent;
		}
		$event = new Event();
		$event->id = $this->eventId;
		$event->find(true);
		$this->_parentEvent = $event;
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

	public function isWaitingListEnabled(): bool {
		if ($this->waitingList !== null) {
			return (bool)$this->waitingList;
		}
		$event = $this->getParentEvent();
		return (bool)$event->waitingList;
	}

	public function getEffectiveWaitingListNumberOfSeats(): ?int {
		if ($this->waitingListNumberOfSeats !== null && $this->waitingListNumberOfSeats > 0) {
			return $this->waitingListNumberOfSeats;
		}
		$event = $this->getParentEvent();
		if ($event->waitingListNumberOfSeats === null || $event->waitingListNumberOfSeats == 0) {
			return null;
		}
		return $event->waitingListNumberOfSeats;
	}

	public function getDisplayWaitingListSeats(): string {
		require_once ROOT_DIR . '/services/EventRegistrationService.php';
		return EventRegistrationService::getDisplayWaitingListSeats($this);
	}

	public function isUpcoming(): bool {
		if (empty($this->date) || empty($this->time)) {
			return false;
		}
		$eventTimestamp = strtotime($this->date . ' ' . $this->time);
		if ($eventTimestamp === false) {
			return false;
		}
		return $eventTimestamp > time();
	}

	public static function addUpcomingWhereClause(DataObject $query): void {
		$cutoffDate = $query->escape(date('Y-m-d'));
		$cutoffTime = $query->escape(date('H:i:s'));
		$query->whereAdd("(date > $cutoffDate OR (date = $cutoffDate AND time > $cutoffTime))");
	}

	public function getEventType() : EventType|null {
		if (!isset($this->eventId)) {
			return null;
		}
		$event = $this->getParentEvent();

		if (!isset($event->eventTypeId)) {
			return null;
		}
		$eventType = new EventType();
		$eventType->id = $event->eventTypeId;
		if (!$eventType->find(true)) {
			return null;
		}

		return $eventType;
	}
}