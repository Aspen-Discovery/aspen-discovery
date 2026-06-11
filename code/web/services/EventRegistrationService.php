<?php

require_once ROOT_DIR . '/sys/Utils/DateUtils.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';

/**
 * Service class for handling event registration logic
 * Shared between patron self-registration and staff registration
 */
class EventRegistrationService {

	/**
	 * Register a user for an event instance
	 * @param int $userId The user to register
	 * @param int $eventInstanceId The event instance to register for
	 * @param int|null $staffUserId The staff user performing the registration (null for self-registration)
	 * @return array Result with success status and message
	 */
	public static function registerUserForEvent(int $userId, int $eventInstanceId, ?int $staffUserId = null, array $attendeeCounts = []): array {
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			return self::publicErrorResult(translate(['text' => 'Event not found.', 'isPublicFacing' => true]));
		}

		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			return self::publicErrorResult(translate(['text' => 'User not found.', 'isPublicFacing' => true]));
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
		$validatedCounts = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($eventInstance, $attendeeCounts);
		if ($validatedCounts === false) {
			return self::publicErrorResult(translate(['text' => 'Invalid attendee counts. One or more categories exceed the allowed maximum.', 'isPublicFacing' => true]));
		}

		$requestedSeats = !empty($validatedCounts) ? array_sum($validatedCounts) : 1;

		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId;
		$registration->eventInstanceId = $eventInstanceId;

		$waitingListInfo = $registration->getWaitingListInfo();
		if (!self::hasAvailableSeats($eventInstance, $requestedSeats) && !$waitingListInfo['canRegister']) {
			return self::publicErrorResult(translate(['text' => 'This event is full. No seats available.', 'isPublicFacing' => true]));
		}

		$registration->registeredByStaffId = $staffUserId;

		if ($registration->registerUser()) {
			UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$registration->id, $validatedCounts);
			self::saveToUserEvents($eventInstance, $userId, $staffUserId);
			return [
				'success' => true,
				'title' => translate(['text' => 'Registration Successful', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'User has been registered for this event.', 'isPublicFacing' => true]),
			];
		}

		return self::publicErrorResult(translate(['text' => 'Failed to create registration.', 'isPublicFacing' => true]));
	}

	/**
	 * Unregister a user from an event instance
	 * @param int $userId The user to unregister
	 * @param int $eventInstanceId The event instance to unregister from
	 * @return array Result with success status and message
	 */
	public static function unregisterUserFromEvent(int $userId, int $eventInstanceId): array {
		$registration = self::getRegistrationFor($userId, $eventInstanceId);
		if (!$registration) {
			return self::publicErrorResult(translate(['text' => 'Registration not found.', 'isPublicFacing' => true]));
		}

		if ($registration->delete()) {
			return [
				'success' => true,
				'title' => translate(['text' => 'Registration Cancelled', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'Registration has been cancelled successfully.', 'isPublicFacing' => true]),
			];
		}

		return self::publicErrorResult(translate(['text' => 'Failed to cancel registration.', 'isPublicFacing' => true]));
	}

	public static function getAttendeeCategoryBreakdown(int $eventInstanceId): array {
		$categories = self::getEventTypeAttendeeCategories($eventInstanceId);
		if (empty($categories)) {
			return [];
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
		$countsByCategory = UserAspenEventInstanceRegistrationAttendee::getCategoryAttendeeCountsForInstance($eventInstanceId);

		$breakdown = [];
		foreach ($categories as $eventTypeAttendeeCategory) {
			$attendeeCategory = $eventTypeAttendeeCategory->getCategory();
			if ($attendeeCategory === null) {
				continue;
			}
			$breakdown[] = [
				'name' => $attendeeCategory->name,
				'count' => $countsByCategory[(int)$eventTypeAttendeeCategory->attendeeCategoryId] ?? 0,
			];
		}

		return $breakdown;
	}

	public static function getRegistrationCount(int $eventInstanceId): int {
		if (!empty(self::getEventTypeAttendeeCategories($eventInstanceId))) {
			require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
			$attendeeTotal = UserAspenEventInstanceRegistrationAttendee::getTotalAttendeesForInstance($eventInstanceId);
			if ($attendeeTotal > 0) {
				return $attendeeTotal;
			}
		}
		return UserAspenEventInstanceRegistration::getRegistrationCount($eventInstanceId);
	}

	private static function getEventTypeAttendeeCategories(int $eventInstanceId): array {
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		$eventInstance->find(true);
		$eventType = $eventInstance->getEventType();
		if ($eventType === null) {
			return [];
		}
		return $eventType->getEventTypeAttendeeCategories() ?: [];
	}

	public static function getAvailableSeats(EventInstance $instance): ?int {
		$capacity = $instance->getEffectiveNumberOfSeats();
		if ($capacity === null) {
			return null;
		}

		// If anyone is queued on the waiting list, block direct registration — new users must join the queue
		$waitingListCount = UserAspenEventInstanceRegistration::getWaitingListCount((int)$instance->id);
		if ($waitingListCount > 0) {
			return 0;
		}

		return max(0, $capacity - self::getRegistrationCount((int)$instance->id));
	}

	public static function hasAvailableSeats(EventInstance $instance, int $requestedSeats = 1): bool {
		$capacity = $instance->getEffectiveNumberOfSeats();
		if ($capacity === null) {
			return true;
		}
		$available = self::getAvailableSeats($instance);
		return $available >= $requestedSeats;
	}

	public static function getAvailableWaitingListSeats(EventInstance $instance): ?int {
		$capacity = $instance->getEffectiveWaitingListNumberOfSeats();
		if ($capacity === null) {
			return null;
		}
		return max(0, $capacity - UserAspenEventInstanceRegistration::getWaitingListCount((int)$instance->id));
	}

	public static function isWaitingListFull(EventInstance $instance): bool {
		return self::getAvailableWaitingListSeats($instance) === 0;
	}

	public static function getDisplayWaitingListSeats(EventInstance $instance): string {
		if ($instance->deleted) {
			return '—';
		}

		$totalSeats = $instance->getEffectiveWaitingListNumberOfSeats();
		if ($totalSeats === null) {
			return 'Available';
		}

		$available = self::getAvailableWaitingListSeats($instance);
		return "{$available} / {$totalSeats}";
	}

	public static function getRegistrationStatusMessage(bool $waitingListEnabled, bool $userOnWaitingList, bool $canRegister, int $waitingListPosition, bool $isEventFull, bool $isWaitingListFull): string {
		if (!$waitingListEnabled) {
			return "Registration available";
		}

		if ($userOnWaitingList) {
			if ($canRegister) {
				return "Registration available";
			}
			return "On waiting list - position " . $waitingListPosition;
		}

		if (!$isEventFull) {
			return "Registration available";
		}

		if (!$isWaitingListFull) {
			return "Waiting List available";
		}

		return "Registration unavailable";
	}

	public static function getRegistrationAction(bool $isRegistered, bool $isEventFull, bool $waitingListEnabled, bool $userOnWaitingList, bool $canRegister, bool $isWaitingListFull): string {
		if ($isRegistered) {
			return 'registered';
		}

		if (!$isEventFull) {
			return 'registrationAvailable';
		}

		if (!$waitingListEnabled) {
			return 'eventFull';
		}

		if ($userOnWaitingList && $canRegister) {
			return 'completeRegistration';
		}

		if ($userOnWaitingList) {
			return 'showPosition';
		}

		if (!$isWaitingListFull) {
			return 'joinWaitingList';
		}

		return 'eventFull';
	}

	/**
	 * Invite the next person on the waiting list for an event instance.
	 */
	public static function inviteNextOnWaitingList(EventInstance $instance): bool {
		require_once ROOT_DIR . '/sys/Account/User.php';
		global $logger;

		$candidateIds = UserAspenEventInstanceRegistration::getWaitingRowIdsForInstance((int)$instance->id);

		foreach ($candidateIds as $candidateId) {
			$candidate = new UserAspenEventInstanceRegistration();
			$candidate->id = $candidateId;
			if (!$candidate->find(true)) {
				continue;
			}

			$user = new User();
			$user->id = $candidate->userId;
			if (!$user->find(true)) {
				$candidate->delete();
				$logger->log("Waiting list candidate removed — user {$candidate->userId} not found (instance {$instance->id})", Logger::LOG_WARNING);
				continue;
			}

			if (!$user->canReceiveEventNotifications()) {
				// if the patron isn't reachable and their account has been linked, attempt to contact the viewer account holder instead
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$viewer = UserLink::getPrimaryAccount((int)$user->id);
				if ($viewer === null || !$viewer->canReceiveEventNotifications()) {
					$logger->log("Waiting list candidate skipped — user {$user->id} unreachable for event notifications (instance {$instance->id})", Logger::LOG_WARNING);
					continue;
				}
				$notifyUserId = (int)$viewer->id;
			} else {
				$notifyUserId = (int)$user->id;
			}

			$candidate->status = 'invited';
			$candidate->notifiedAt = date('Y-m-d H:i:s');
			$candidate->update();

			self::sendEventInstanceRegistrationInvitation($instance, $notifyUserId);
			return true;
		}

		return false;
	}

	public static function hasUnregisteredLinkedUsers(EventInstance $instance, ?User $viewer = null): bool {
		if ($viewer === null) {
			$viewer = UserAccount::getActiveUserObj();
			if (!$viewer) {
				return false;
			}
		}
		foreach ($viewer->getLinkedUsers() as $linkedUser) {
			$registration = new UserAspenEventInstanceRegistration();
			$registration->userId = (int)$linkedUser->id;
			$registration->eventInstanceId = (int)$instance->id;
			if ($registration->isUserRegisteredForEvent()) {
				continue;
			}
			$waitingListInfo = $registration->getWaitingListInfo();
			if (!$waitingListInfo['onWaitingList']) {
				return true;
			}
		}
		return false;
	}

	private static function sendEventInstanceRegistrationInvitation(EventInstance $instance, int $userId): void {
		$event = $instance->getParentEvent();

		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		self::sendEventEmail($userId, 'registerForEventFromWaitingList', [
			'eventDate' => DateUtils::formatHumanDate($instance->date),
			'eventTime' => $instance->time,
			'eventTitle' => $event->title,
			'library' => $homeLibrary,
		]);
	}

	public static function sendEventEmail(int $userId, string $templateName, array $parameters): bool {
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
		global $logger;

		$emailTemplate = EmailTemplate::getActiveTemplate($templateName);
		if (!$emailTemplate) {
			$logger->log("Unable to find email template: $templateName", Logger::LOG_ERROR);
			return false;
		}

		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			$logger->log("$templateName email skipped — user $userId not found", Logger::LOG_ERROR);
			return false;
		}

		if (!$user->canReceiveEventNotifications()) {
			return false;
		}

		$parameters['user'] = $user;

		$sent = $emailTemplate->sendEmail($user->email, $parameters);
		if (!$sent) {
			$logger->log("$templateName email failed to send for user $userId", Logger::LOG_ERROR);
			return false;
		}
		return true;
	}

	public static function saveToUserEvents(EventInstance $instance, int $userId, int|null $savedByStaffId = null): void {
		require_once ROOT_DIR . '/sys/Events/EventsIndexingSetting.php';
		$indexingSetting = new EventsIndexingSetting();
		if (!$indexingSetting->find(true)) {
			return;
		}

		$sourceId = 'aspenEvent_' . $indexingSetting->id . '_' . $instance->id;

		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
		$entry = new UserEventsEntry();
		$entry->sourceId = $sourceId;
		$entry->userId = $userId;
		if ($entry->find(true)) {
			return;
		}

		$event = $instance->getParentEvent();

		$entry->title = mb_substr($event->title, 0, 50);
		$entry->eventDate = strtotime($instance->date . ' ' . $instance->time);
		$entry->regRequired = !empty($event->registrationRequired) ? 1 : 0;
		$entry->savedByStaffId = $savedByStaffId;

		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		$location = new Location();
		$location->locationId = $event->locationId;
		$entry->location = $location->find(true) ? $location->displayName : '';

		$entry->dateAdded = time();
		$entry->insert();
	}

	public static function sendCancellationNotificationEmails(array $upcomingInstances, array $affectedUsersByStatus): void {
		if (empty($upcomingInstances) || empty($affectedUsersByStatus)) {
			return;
		}
		$formattedInstances = self::formatEmailTemplateEventInstances($upcomingInstances);
		$templateTypesByStatus = [
			'registered' => 'eventCancellationRegistered',
			'invited' => 'eventCancellationInvited',
			'waiting' => 'eventCancellationWaiting',
		];
		foreach ($affectedUsersByStatus as $status => $userIds) {
			if (!isset($templateTypesByStatus[$status])) {
				continue;
			}
			foreach ($userIds as $userId) {
				self::sendEventEmail($userId, $templateTypesByStatus[$status], [
					'instances' => $formattedInstances,
				]);
			}
		}
	}

	private static function formatEmailTemplateEventInstances(array $eventInstances): array {
		require_once ROOT_DIR . '/sys/Events/Event.php';

		$formatted = [];

		foreach ($eventInstances as $instance) {
			$event = new Event();
			$event->id = $instance->eventId;
			if (!$event->find(true)) {
				continue;
			}
			$humanEventDate = DateUtils::formatHumanDate($instance->date);
			$formatted[] = [
				'eventTitle' => $event->title,
				'eventDate' => $humanEventDate,
				'eventTime' => $instance->time,
			];
		}
		return $formatted;
	}

	/**
	 * Check if a user has permission to register other users for events
	 */
	public static function canStaffRegisterUsers(): bool {
		global $library;
		if (empty($library->allowStaffToRegisterUsersForEvents)) {
			return false;
		}
		return UserAccount::userHasPermission([
			'Register Users for Events for All Locations',
			'Register Users for Events for Home Library Locations',
			'Register Users for Events for Home Location',
		]);
	}

	/**
	 * Check if a user has permission to view and manage patron event attendance
	 */
	public static function canStaffManagePatronEventAttendance(?int $locationId = null): bool {
		if ($locationId !== null) {
			return self::canStaffManagePatronAttendanceForLocation($locationId);
		}
		return UserAccount::userHasPermission([
			'Manage Patron Event Attendance for All Locations',
			'Manage Patron Event Attendance for Home Library Locations',
			'Manage Patron Event Attendance for Home Location',
		]);
	}

	/**
	 * Check if staff can register users for a specific event location
	 */
	public static function canStaffRegisterUsersForLocation(int $locationId): bool {
		return self::hasPermissionForLocation(
			$locationId,
			'Register Users for Events for All Locations',
			'Register Users for Events for Home Library Locations',
			'Register Users for Events for Home Location',
		);
	}

	/**
	 * Check if staff can view and manage patron event attendance for a specific event location
	 */
	public static function canStaffManagePatronAttendanceForLocation(int $locationId): bool {
		return self::hasPermissionForLocation(
			$locationId,
			'Manage Patron Event Attendance for All Locations',
			'Manage Patron Event Attendance for Home Library Locations',
			'Manage Patron Event Attendance for Home Location',
		);
	}

	private static function publicErrorResult(string $message): array {
		return [
			'success' => false,
			'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
			'message' => $message,
		];
	}

	private static function adminErrorResult(string $message): array {
		return [
			'success' => false,
			'title' => translate(['text' => 'Error', 'isAdminFacing' => true]),
			'message' => $message,
		];
	}

	private static function getRegistrationFor(int $userId, int $eventInstanceId): UserAspenEventInstanceRegistration|false {
		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId;
		$registration->eventInstanceId = $eventInstanceId;
		return $registration->find(true) ? $registration : false;
	}

	private static function formatUserData(User $user): array {
		return [
			'id' => $user->id,
			'displayName' => $user->getDisplayName(),
			'barcode' => $user->ils_barcode,
			'email' => $user->email,
			'homeLocation' => $user->getHomeLocationName(),
		];
	}

	private static function hasPermissionForLocation(int $locationId, string $systemLevelPermission, string $locationLevelPermission, string $libraryLevelPermission): bool {
		if (!UserAccount::userHasPermission([$systemLevelPermission, $locationLevelPermission, $libraryLevelPermission])) {
			return false;
		}

		if (UserAccount::userHasPermission($systemLevelPermission)) {
			return true;
		}

		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			return false;
		}

		if (UserAccount::userHasPermission($libraryLevelPermission)) {
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$eventLibrary = Library::getLibraryForLocation($locationId);
			return $patronLibrary && $eventLibrary && $patronLibrary->libraryId == $eventLibrary->libraryId;
		}

		if (UserAccount::userHasPermission($locationLevelPermission)) {
			if ($user->homeLocationId == $locationId) {
				return true;
			}
			$additionalLocations = $user->getAdditionalAdministrationLocations();
			return array_key_exists($locationId, $additionalLocations);
		}

		return false;
	}

	/**
	 * Look up a user by barcode
	 */
	public static function lookupUserByBarcode(string $barcode): array {
		if (empty($barcode)) {
			return self::adminErrorResult(translate(['text' => 'Barcode is required.', 'isAdminFacing' => true]));
		}

		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->ils_barcode = $barcode;

		if ($user->find(true)) {
			return [
				'success' => true,
				'title' => translate(['text' => 'User Found', 'isAdminFacing' => true]),
				'message' => translate(['text' => 'User found successfully.', 'isAdminFacing' => true]),
				'user' => self::formatUserData($user),
			];
		}

		require_once ROOT_DIR . '/CatalogFactory.php';
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (method_exists($catalog, 'findNewUser')) {
			$newUser = $catalog->findNewUser($barcode, '');
			if ($newUser && !($newUser instanceof AspenError)) {
				$newUser->getDisplayName();
				$newUser->update();
				return [
					'success' => true,
					'title' => translate(['text' => 'User Found', 'isAdminFacing' => true]),
					'message' => translate(['text' => 'User loaded from ILS.', 'isAdminFacing' => true]),
					'user' => self::formatUserData($newUser),
				];
			}
		}

		return self::adminErrorResult(translate(['text' => 'User not found in Aspen or ILS.', 'isAdminFacing' => true]));
	}
}
