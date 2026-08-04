<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventInstance.php';

class UserAspenEventInstanceRegistration extends DataObject {
	public $__table = 'user_aspen_event_instance_registrations';
	public $id;
	public $userId;
	public $eventInstanceId;
	public $status;
	public $createdAt;
	public $notifiedAt;
	public $registeredByStaffId;
	public $attended;

	const VALID_STATUSES = ['waiting', 'invited', 'registered'];

	public function getUniquenessFields(): array {
		return [
			'userId',
			'eventInstanceId',
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'userId',
			'eventInstanceId',
			'registeredByStaffId',
			'attended'
		];
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		if (!$useWhere && $this->id) {
			require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
			UserAspenEventInstanceRegistrationAttendee::deleteForRegistration((int)$this->id);
		}
		return parent::delete($useWhere, $hardDelete);
	}

	public function isUserRegisteredForEvent(): bool {
		if($this->status) {
			return $this->status === 'registered';
		}

		if (!$this->find(true)) {
			return false;
		}
		return $this->status === 'registered';
	}

	public function registerUser(): bool {
		$status = 'registered';

		if ($this->status === 'registered') {
			return false;
		}

		if (!$this->validateStatus($status)) {
			return false;
		}

		if ($this->find(true)) {
			$this->status = $status;
			return $this->update();
		}

		$this->status = $status;
		$this->createdAt = date('Y-m-d H:i:s');
		return $this->insert();
	}

	public function addUserToWaitingList(): bool {
		if ($this->find(true)) {
			return false;
		}

		$status = 'waiting';

		if (!$this->validateStatus($status)) {
			return false;
		}

		$this->createdAt = date('Y-m-d H:i:s');
		$this->status = $status;
		return $this->insert();
	}

	public function getWaitingListInfo(): array {
		$default = ['onWaitingList' => false, 'position' => null, 'canRegister' => false];

		if (!$this->status && !$this->find(true)) {
			return $default;
		}

		if (!in_array($this->status, ['waiting', 'invited'], true)) {
			return $default;
		}

		return [
			'onWaitingList' => true,
			'position' => self::getWaitingListPosition($this->eventInstanceId, $this->createdAt),
			'canRegister' => $this->status === 'invited',
		];
	}

	/**
	 * Static because DataObject uses set properties as implicit WHERE clauses.
	 * Calling this on an instance with userId set would contaminate the count
	 * query, returning only the caller's own rows instead of all queue entries.
	 */
	public static function getWaitingListPosition(int $eventInstanceId, string $createdAt): ?int {
		$query = new UserAspenEventInstanceRegistration();
		$query->eventInstanceId = $eventInstanceId;
		$query->whereAdd('status IN ("waiting", "invited")');
		$query->whereAdd("createdAt < " . $query->escape($createdAt));

		return $query->count() + 1;
	}

	private function validateStatus(string $status): bool {
		return in_array($status, self::VALID_STATUSES, true);
	}
	
	public static function getRegistrationCount(int $eventInstanceId): int {
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		$registration->status = 'registered';
		return $registration->count();
	}

	public static function getWaitingListCount(int $eventInstanceId): int {
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		$registration->whereAdd('status IN ("waiting", "invited")');
		return $registration->count();
	}

	public static function getRegistrationsForEvent(int $eventInstanceId): array {
		$registrations = [];
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		$registration->find();
		while ($registration->fetch()) {
			$registrations[] = clone $registration;
		}
		return $registrations;
	}

	/**
	 * Returns IDs of invited registrations whose invite window has expired.
	 *
	 * Batches by EventType so each type's waitingListInviteExpiryHours is honoured
	 * in a single query per type. Skips soft-deleted event instances.
	 *
	 */
	public static function getExpiredInvitedRowIds(): array {
		require_once ROOT_DIR . '/sys/Events/EventType.php';

		$eventType = new EventType();
		$eventType->find();

		$expiredIds = [];
		while ($eventType->fetch()) {
			$expiryHours = (int)$eventType->waitingListInviteExpiryHours;
			if ($expiryHours <= 0) {
				$expiryHours = 24;
			}
			$cutoff = date('Y-m-d H:i:s', time() - ($expiryHours * 3600));

			$query = new UserAspenEventInstanceRegistration();
			$query->status = 'invited';
			$query->whereAdd('notifiedAt IS NOT NULL');
			$query->whereAdd('notifiedAt < ' . $query->escape($cutoff));
			$query->whereAdd('eventInstanceId IN (SELECT ei.id FROM event_instance ei JOIN event e ON ei.eventId = e.id WHERE ei.deleted = 0 AND e.eventTypeId = ' . (int)$eventType->id . ')');
			$query->find();
			while ($query->fetch()) {
				$expiredIds[] = (int)$query->id;
			}
		}

		return $expiredIds;
	}

	/**
	 * Checks whether a user has at least one event instance to register to.
	*/
	public static function isUserInvitedToRegister(int $userId): bool {
		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId;
		$registration->status = 'invited';
		return $registration->find(true);
	}

	/**
	 * @return array<int, string> [eventFieldId => value, ...]
	 */
	public function getCustomFieldValues(): array {
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationEventField.php';
		$values = [];
		$field = new UserAspenEventInstanceRegistrationEventField();
		$field->eventInstanceRegistrationId = (int)$this->id;
		$field->find();
		while ($field->fetch()) {
			$values[(int)$field->eventFieldId] = $field->value;
		}
		return $values;
	}

	/**
	 * @return array<int, int> [attendeeCategoryId => count, ...]
	 */
	public function getAttendeeCounts(): array {
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
		return UserAspenEventInstanceRegistrationAttendee::getCountsForRegistration((int)$this->id);
	}

	/**
	 * Deletes all registration rows tied to the given event instance.
	 * Returns the number of rows deleted (or false on failure).
	 */
	public static function deleteAllForEventInstance(int $eventInstanceId): bool|int {
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		return $registration->delete(true);
	}

	/**
	 * Returns row IDs for waiting registrations on a given instance, ordered by queue position.
	 */
	public static function getWaitingRowIdsForInstance(int $eventInstanceId): array {
		$query = new UserAspenEventInstanceRegistration();
		$query->eventInstanceId = $eventInstanceId;
		$query->status = 'waiting';
		$query->orderBy('createdAt ASC');
		$query->find();
		$ids = [];
		while ($query->fetch()) {
			$ids[] = (int)$query->id;
		}
		return $ids;
	}

	/**
	 * Returns users grouped by registration status for a single event instance.
	 * Shape: ['registered' => [userId, ...], 'invited' => [...], 'waiting' => [...]]
	 * Statuses with no users are omitted.
	 */
	public static function getUsersGroupedByStatusForInstance(int $eventInstanceId): array {
		return self::getUsersGroupedByStatusForInstances([$eventInstanceId]);
	}

	/**
	 * Returns users grouped by registration status across multiple event instances.
	 * A user on multiple instances with the same status appears once in that group.
	 */
	public static function getUsersGroupedByStatusForInstances(array $eventInstanceIds): array {
		if (empty($eventInstanceIds)) {
			return [];
		}

		$registration = new UserAspenEventInstanceRegistration();
		$registration->whereAddIn('eventInstanceId', $eventInstanceIds, false);
		$registration->find();

		$grouped = [];
		while ($registration->fetch()) {
			$grouped[$registration->status][(int)$registration->userId] = true;
		}

		return array_map('array_keys', $grouped);
	}

	/**
	 * Get the user object for the registered patron
	 * @return User|false
	 */
	public function getUser(): User|false {
		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			return $user;
		}
		return false;
	}

	/**
	 * Get the staff user who registered this patron (if applicable)
	 * @return User|false
	 */
	public function getStaffUser(): User|false {
		if (empty($this->registeredByStaffId)) {
			return false;
		}
		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->id = $this->registeredByStaffId;
		if ($user->find(true)) {
			return $user;
		}
		return false;
	}

	/**
	 * Get the event instance this registration is for
	 * @return EventInstance|false
	 */
	public function getEventInstance(): EventInstance|false {
		$eventInstance = new EventInstance();
		$eventInstance->id = $this->eventInstanceId;
		if ($eventInstance->find(true)) {
			return $eventInstance;
		}
		return false;
	}

	/**
	 * Check if this registration was made by staff
	 * @return bool
	 */
	public function wasRegisteredByStaff(): bool {
		return !empty($this->registeredByStaffId);
	}

	public function saveEventFieldValue(int $eventFieldId, string $value): void {
		if (empty($value) && $value !== '0') {
			return;
		}
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationEventField.php';
		$fieldEntry = new UserAspenEventInstanceRegistrationEventField();
		$fieldEntry->eventInstanceRegistrationId = $this->id;
		$fieldEntry->eventFieldId = $eventFieldId;
		if ($fieldEntry->find(true)) {
			return;
		}
		$fieldEntry->value = $value;
		$fieldEntry->insert();
	}
}
