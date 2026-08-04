<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserAspenEventInstanceRegistrationAttendee extends DataObject {
	public $__table = 'user_aspen_event_instance_registration_attendee';
	public $id;
	public $registrationId;
	public $attendeeCategoryId;
	public $count;

	public function getUniquenessFields(): array {
		return ['registrationId', 'attendeeCategoryId'];
	}

	public function getNumericColumnNames(): array {
		return ['registrationId', 'attendeeCategoryId', 'count'];
	}

	public static function deleteForRegistration(int $registrationId): void {
		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $registrationId;
		$attendee->delete(true);
	}

	/**
	 * Sum all attendee counts across all active registrations for an event instance.
	 * Only includes registrations with status = 'registered'.
	 */
	public static function getTotalAttendeesForInstance(int $eventInstanceId): int {
		$query = new UserAspenEventInstanceRegistrationAttendee();
		$query->whereAdd('registrationId IN (SELECT id FROM user_aspen_event_instance_registrations WHERE eventInstanceId = ' . $query->escape($eventInstanceId) . " AND status = 'registered')");
		$query->find();
		$total = 0;
		while ($query->fetch()) {
			$total += (int)$query->count;
		}
		return $total;
	}

	/**
	 * Save attendee category counts for a registration.
	 * $attendeeCounts: [attendeeCategoryId => count, ...]
	 */
	public static function saveForRegistration(int $registrationId, array $attendeeCounts): void {
		foreach ($attendeeCounts as $categoryId => $count) {
			$count = (int)$count;
			if ($count <= 0) {
				continue;
			}
			$attendee = new UserAspenEventInstanceRegistrationAttendee();
			$attendee->registrationId = $registrationId;
			$attendee->attendeeCategoryId = (int)$categoryId;
			if ($attendee->find(true)) {
				$attendee->count = $count;
				$attendee->update();
				continue;
			}
			$attendee->count = $count;
			$attendee->insert();
		}
	}

	public static function getCountsForRegistration(int $registrationId): array {
		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $registrationId;
		$attendee->find();
		$counts = [];
		while ($attendee->fetch()) {
			$counts[(int)$attendee->attendeeCategoryId] = (int)$attendee->count;
		}
		return $counts;
	}

	/**
	 * Get per-category attendee counts for an event instance.
	 * Returns [['name' => ..., 'count' => ...], ...] or empty array if no categories.
	 */
	public static function getCategoryAttendeeCountsForInstance(int $eventInstanceId): array {
		$query = new UserAspenEventInstanceRegistrationAttendee();
		$escapedId = $query->escape($eventInstanceId);
		$query->whereAdd("registrationId IN (SELECT id FROM user_aspen_event_instance_registrations WHERE eventInstanceId = $escapedId AND status = 'registered')");
		$query->find();

		$countsByCategory = [];
		while ($query->fetch()) {
			$catId = (int)$query->attendeeCategoryId;
			$countsByCategory[$catId] = ($countsByCategory[$catId] ?? 0) + (int)$query->count;
		}

		return $countsByCategory;
	}

	/**
	 * Validate attendee counts against the event type's category limits.
	 * Returns validated array or false if any count exceeds its max.
	 * Returns empty array if the event type has no categories.
	 */
	public static function validateAttendeeCounts(EventInstance $eventInstance, array $attendeeCounts): array|false {
		if (empty($attendeeCounts)) {
			return [];
		}

		$eventType = $eventInstance->getEventType();
		if ($eventType === null) {
			return [];
		}

		$attendeeCategoriesForEventType = $eventType->getEventTypeAttendeeCategories();
		if (empty($attendeeCategoriesForEventType)) {
			return [];
		}

		$maxByCategory = [];
		foreach ($attendeeCategoriesForEventType as $eventTypeAttendeeCategory) {
			$maxByCategory[(int)$eventTypeAttendeeCategory->attendeeCategoryId] = (int)$eventTypeAttendeeCategory->maxAttendees;
		}

		$validated = [];
		foreach ($attendeeCounts as $categoryId => $count) {
			$categoryId = (int)$categoryId;
			$count = (int)$count;

			if (!isset($maxByCategory[$categoryId])) {
				continue;
			}

			if ($count > $maxByCategory[$categoryId]) {
				return false;
			}

			if ($count > 0) {
				$validated[$categoryId] = $count;
			}
		}

		return $validated;
	}
}
