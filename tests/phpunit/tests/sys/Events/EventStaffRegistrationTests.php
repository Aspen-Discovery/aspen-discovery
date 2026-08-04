<?php

use PHPUnit\Framework\TestCase;

if (!defined('STAFF_REG_PATH_TO_ROOT')) {
	define('STAFF_REG_PATH_TO_ROOT', __DIR__ . '/../../../../../');
}

class EventStaffRegistrationTests extends TestCase {

	private Event $event;
	private EventInstance $eventInstance;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Utils/DateUtils.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/Event.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/EventInstance.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/EventType.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/UserAspenEventInstanceRegistration.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/services/EventRegistrationService.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/EventsIndexingSetting.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Events/UserEventsEntry.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/Account/User.php';
		require_once STAFF_REG_PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Location.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$eventType = new EventType();
		$eventType->title = 'PHPUnit Staff Reg Type';
		if (!$eventType->find(true)) {
			$eventType->eventInformationFieldSetId = 1;
			$eventType->eventRegistrationFieldSetId = 1;
			$eventType->insert();
		}

		$this->event = new Event();
		$this->event->title = 'PHPUnit Staff Reg Event';
		$this->event->registrationRequired = 1;
		$this->event->numberOfSeats = 3;
		$this->event->waitingList = 1;
		$this->event->waitingListNumberOfSeats = 3;
		$this->event->locationId = 1;
		$this->event->eventTypeId = $eventType->id;
		$this->event->startDate = date('Y-m-d');
		$this->event->insert();

		$this->eventInstance = new EventInstance();
		$this->eventInstance->eventId = $this->event->id;
		$this->eventInstance->date = date('Y-m-d', strtotime('+7 days'));
		$this->eventInstance->time = '10:00';
		$this->eventInstance->length = 60;
		$this->eventInstance->status = 1;
		$this->eventInstance->waitingList = 1;
		$this->eventInstance->waitingListNumberOfSeats = 3;
		$this->eventInstance->insert();
	}

	protected function tearDown(): void {
		global $aspen_db;

		$aspen_db->exec("DELETE FROM user_events_entry WHERE sourceId LIKE 'aspenEvent_%'");
		$aspen_db->exec("DELETE FROM user_aspen_event_instance_registrations");
		$aspen_db->exec("DELETE FROM event_instance");
		$aspen_db->exec("DELETE FROM event");
		$aspen_db->exec("DELETE FROM user WHERE source = 'phpunit'");
		$aspen_db->exec("DELETE FROM events_indexing_settings WHERE name = 'PHPUnit Setting'");

		parent::tearDown();
	}

	// ── Helpers ──────────────────────────────────

	private function insertUser(int $id): User {
		$user = new User();
		$user->source = 'phpunit';
		$user->username = "phpunit_staffreg_$id";
		$user->firstname = 'Test';
		$user->lastname = "StaffReg_$id";
		$user->displayName = "Test StaffReg $id";
		$user->created = date('Y-m-d H:i:s');
		$user->homeLocationId = 1;
		$user->myLocation1Id = 1;
		$user->myLocation2Id = 1;
		$user->unique_ils_id = "phpunit_staffreg_$id";
		$user->ils_barcode = "PHPUNIT_SR_$id";
		$user->email = "staffreg$id@example.test";
		$user->insert();
		return $user;
	}

	private function insertRegistration(int $userId, string $status = 'registered'): UserAspenEventInstanceRegistration {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $userId;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = $status;
		$reg->createdAt = date('Y-m-d H:i:s');
		$reg->insert();
		return $reg;
	}

	private function ensureEventsIndexingSetting(): EventsIndexingSetting {
		$setting = new EventsIndexingSetting();
		$setting->name = 'PHPUnit Setting';
		if (!$setting->find(true)) {
			$setting->insert();
		}
		return $setting;
	}

	// ── registerUserForEvent ────────────────────

	public function testRegisterUserCreatesRegistrationWithRegisteredStatus(): void {
		$user = $this->insertUser(40001);

		$result = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);

		$this->assertTrue($result['success']);

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $user->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);
		$this->assertEquals('registered', $reg->status);
	}

	public function testRegisterUserSetsStaffUserId(): void {
		$patron = $this->insertUser(40010);
		$staff = $this->insertUser(40011);

		$result = EventRegistrationService::registerUserForEvent((int)$patron->id, (int)$this->eventInstance->id, (int)$staff->id);

		$this->assertTrue($result['success']);

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $patron->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);
		$this->assertEquals((int)$staff->id, (int)$reg->registeredByStaffId);
	}

	public function testRegisterUserRejectsNonexistentEventInstance(): void {
		$user = $this->insertUser(40020);
		$result = EventRegistrationService::registerUserForEvent((int)$user->id, 99999);
		$this->assertFalse($result['success']);
	}

	public function testRegisterUserRejectsNonexistentUser(): void {
		$result = EventRegistrationService::registerUserForEvent(99999, (int)$this->eventInstance->id);
		$this->assertFalse($result['success']);
	}

	public function testRegisterUserRejectsDuplicate(): void {
		$user = $this->insertUser(40030);
		$r1 = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertTrue($r1['success']);

		$r2 = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertFalse($r2['success']);
	}

	public function testRegisterUserRejectsWhenFull(): void {
		// Fill 3 seats
		for ($i = 0; $i < 3; $i++) {
			$u = $this->insertUser(40040 + $i);
			$this->insertRegistration((int)$u->id);
		}

		$user = $this->insertUser(40050);
		$result = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertFalse($result['success']);
	}

	public function testRegisterUserAllowsInvitedUserWhenFull(): void {
		// Fill all seats
		for ($i = 0; $i < 3; $i++) {
			$u = $this->insertUser(40060 + $i);
			$this->insertRegistration((int)$u->id);
		}

		// Put user on waitlist as invited
		$invitedUser = $this->insertUser(40070);
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $invitedUser->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = 'invited';
		$reg->createdAt = date('Y-m-d H:i:s');
		$reg->notifiedAt = date('Y-m-d H:i:s');
		$reg->insert();

		// Unregister one to make room — invited user should be allowed through
		$firstReg = new UserAspenEventInstanceRegistration();
		$firstReg->userId = 40060;
		$firstReg->eventInstanceId = $this->eventInstance->id;
		$firstReg->find(true);
		$firstReg->delete();

		$result = EventRegistrationService::registerUserForEvent((int)$invitedUser->id, (int)$this->eventInstance->id);
		$this->assertTrue($result['success']);

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = $invitedUser->id;
		$check->eventInstanceId = $this->eventInstance->id;
		$check->find(true);
		$this->assertEquals('registered', $check->status);
	}

	// ── unregisterUserFromEvent ─────────────────

	public function testUnregisterUserDeletesRow(): void {
		$user = $this->insertUser(40100);
		$this->insertRegistration((int)$user->id);

		$result = EventRegistrationService::unregisterUserFromEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertTrue($result['success']);

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = $user->id;
		$check->eventInstanceId = $this->eventInstance->id;
		$this->assertFalse($check->find(true));
	}

	public function testUnregisterUserFailsWhenNotFound(): void {
		$result = EventRegistrationService::unregisterUserFromEvent(99999, (int)$this->eventInstance->id);
		$this->assertFalse($result['success']);
	}

	// ── getRegistrationsForEvent ────────────────

	public function testGetRegistrationsReturnsAllStatuses(): void {
		$u1 = $this->insertUser(40200);
		$u2 = $this->insertUser(40201);
		$u3 = $this->insertUser(40202);

		$this->insertRegistration((int)$u1->id, 'registered');
		$this->insertRegistration((int)$u2->id, 'waiting');
		$this->insertRegistration((int)$u3->id, 'invited');

		$regs = UserAspenEventInstanceRegistration::getRegistrationsForEvent((int)$this->eventInstance->id);
		$this->assertCount(3, $regs);
	}

	public function testGetRegistrationsReturnsEmptyForNoRegistrations(): void {
		$regs = UserAspenEventInstanceRegistration::getRegistrationsForEvent((int)$this->eventInstance->id);
		$this->assertEmpty($regs);
	}

	// ── lookupUserByBarcode ─────────────────────

	public function testLookupUserByBarcodeFindsExistingUser(): void {
		$user = $this->insertUser(40300);

		$result = EventRegistrationService::lookupUserByBarcode('PHPUNIT_SR_40300');
		$this->assertTrue($result['success']);
		$this->assertEquals((int)$user->id, (int)$result['user']['id']);
		$this->assertEquals('PHPUNIT_SR_40300', $result['user']['barcode']);
	}

	public function testLookupUserByBarcodeFailsForEmptyBarcode(): void {
		$result = EventRegistrationService::lookupUserByBarcode('');
		$this->assertFalse($result['success']);
	}

	public function testLookupUserByBarcodeFailsForUnknownBarcode(): void {
		$result = EventRegistrationService::lookupUserByBarcode('NONEXISTENT_BARCODE_12345');
		$this->assertFalse($result['success']);
	}

	// ── UserAspenEventInstanceRegistration::getUser ──

	public function testGetUserReturnsUserObject(): void {
		$user = $this->insertUser(40400);
		$reg = $this->insertRegistration((int)$user->id);

		$fetchedUser = $reg->getUser();
		$this->assertInstanceOf(User::class, $fetchedUser);
		$this->assertEquals((int)$user->id, (int)$fetchedUser->id);
	}

	public function testGetUserReturnsFalseForMissingUser(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 99999;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = 'registered';
		$reg->createdAt = date('Y-m-d H:i:s');
		$reg->insert();

		$this->assertFalse($reg->getUser());
	}

	// ── UserAspenEventInstanceRegistration::getStaffUser ──

	public function testGetStaffUserReturnsStaffWhenSet(): void {
		$patron = $this->insertUser(40500);
		$staff = $this->insertUser(40501);

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $patron->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = 'registered';
		$reg->registeredByStaffId = $staff->id;
		$reg->createdAt = date('Y-m-d H:i:s');
		$reg->insert();

		$fetchedStaff = $reg->getStaffUser();
		$this->assertInstanceOf(User::class, $fetchedStaff);
		$this->assertEquals((int)$staff->id, (int)$fetchedStaff->id);
	}

	public function testGetStaffUserReturnsFalseWhenNoStaff(): void {
		$user = $this->insertUser(40510);
		$reg = $this->insertRegistration((int)$user->id);

		$this->assertFalse($reg->getStaffUser());
	}

	// ── UserAspenEventInstanceRegistration::getEventInstance ──

	public function testGetEventInstanceReturnsInstance(): void {
		$user = $this->insertUser(40600);
		$reg = $this->insertRegistration((int)$user->id);

		$instance = $reg->getEventInstance();
		$this->assertInstanceOf(EventInstance::class, $instance);
		$this->assertEquals((int)$this->eventInstance->id, (int)$instance->id);
	}

	public function testGetEventInstanceReturnsFalseForBadId(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->eventInstanceId = 99999;
		$this->assertFalse($reg->getEventInstance());
	}

	// ── UserAspenEventInstanceRegistration::wasRegisteredByStaff ──

	public function testWasRegisteredByStaffTrueWhenSet(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->registeredByStaffId = 123;
		$this->assertTrue($reg->wasRegisteredByStaff());
	}

	public function testWasRegisteredByStaffFalseWhenNull(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$this->assertFalse($reg->wasRegisteredByStaff());
	}

	public function testWasRegisteredByStaffFalseWhenZero(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->registeredByStaffId = 0;
		$this->assertFalse($reg->wasRegisteredByStaff());
	}

	// ── EventInstance::saveToUserEvents ──────────

	public function testSaveToUserEventsCreatesEntry(): void {
		$setting = $this->ensureEventsIndexingSetting();
		$user = $this->insertUser(40700);

		EventRegistrationService::saveToUserEvents($this->eventInstance, (int)$user->id);

		$entry = new UserEventsEntry();
		$entry->sourceId = 'aspenEvent_' . $setting->id . '_' . $this->eventInstance->id;
		$entry->userId = $user->id;
		$this->assertTrue($entry->find(true));
		$this->assertEquals(mb_substr($this->event->title, 0, 50), $entry->title);
		$this->assertEquals(1, (int)$entry->regRequired);
	}

	public function testSaveToUserEventsIsIdempotent(): void {
		$this->ensureEventsIndexingSetting();
		$user = $this->insertUser(40710);

		EventRegistrationService::saveToUserEvents($this->eventInstance, (int)$user->id);
		EventRegistrationService::saveToUserEvents($this->eventInstance, (int)$user->id);

		$entry = new UserEventsEntry();
		$entry->userId = $user->id;
		$this->assertEquals(1, $entry->count());
	}

	public function testSaveToUserEventsNoOpWithoutSetting(): void {
		// No EventsIndexingSetting row — should silently return
		$user = $this->insertUser(40720);
		EventRegistrationService::saveToUserEvents($this->eventInstance, (int)$user->id);

		$entry = new UserEventsEntry();
		$entry->userId = $user->id;
		$this->assertEquals(0, $entry->count());
	}

	// ── EventInstance::getParentEvent cache invalidation ──

	public function testGetParentEventInvalidatesCacheOnEventIdChange(): void {
		$event2 = new Event();
		$event2->title = 'PHPUnit Second Event';
		$event2->registrationRequired = 1;
		$event2->numberOfSeats = 5;
		$event2->locationId = 1;
		$event2->eventTypeId = $this->event->eventTypeId;
		$event2->startDate = date('Y-m-d');
		$event2->insert();

		$instance = new EventInstance();
		$instance->eventId = $this->event->id;
		$instance->date = date('Y-m-d', strtotime('+8 days'));
		$instance->time = '11:00';
		$instance->length = 60;
		$instance->status = 1;
		$instance->insert();

		// First call caches parent
		$parent1 = $instance->getParentEvent();
		$this->assertEquals((int)$this->event->id, (int)$parent1->id);

		// Simulate fetch loop: eventId changes
		$instance->eventId = $event2->id;
		$parent2 = $instance->getParentEvent();
		$this->assertEquals((int)$event2->id, (int)$parent2->id, 'Cache should invalidate when eventId changes');

		// Cleanup
		global $aspen_db;
		$aspen_db->exec("DELETE FROM event_instance WHERE id = " . (int)$instance->id);
		$aspen_db->exec("DELETE FROM event WHERE id = " . (int)$event2->id);
	}

	// ── Integration: register + saveToUserEvents ──

	public function testRegisterUserForEventSavesToUserEvents(): void {
		$this->ensureEventsIndexingSetting();
		$user = $this->insertUser(40800);

		$result = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertTrue($result['success']);

		$entry = new UserEventsEntry();
		$entry->userId = $user->id;
		$this->assertEquals(1, $entry->count(), 'Registration should create a user events entry');
	}

	public function testRegisterUserForEventDoesNotSaveOnFailure(): void {
		$this->ensureEventsIndexingSetting();

		// Fill event
		for ($i = 0; $i < 3; $i++) {
			$u = $this->insertUser(40810 + $i);
			$this->insertRegistration((int)$u->id);
		}

		$user = $this->insertUser(40820);
		$result = EventRegistrationService::registerUserForEvent((int)$user->id, (int)$this->eventInstance->id);
		$this->assertFalse($result['success']);

		$entry = new UserEventsEntry();
		$entry->userId = $user->id;
		$this->assertEquals(0, $entry->count(), 'Failed registration should not create a user events entry');
	}
}
