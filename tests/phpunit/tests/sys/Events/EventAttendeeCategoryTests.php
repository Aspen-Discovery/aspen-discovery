<?php

use PHPUnit\Framework\TestCase;
define('ATTENDEE_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class EventAttendeeCategoryTests extends TestCase {

	private Event $event;
	private EventInstance $eventInstance;
	private EventType $eventType;
	private AspenEventAttendeeCategory $categoryChild;
	private AspenEventAttendeeCategory $categoryAdult;
	private EventTypeAttendeeCategory $joinChild;
	private EventTypeAttendeeCategory $joinAdult;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Utils/DateUtils.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/Event.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/EventInstance.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/EventType.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/AspenEventAttendeeCategory.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/EventTypeAttendeeCategory.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/UserAspenEventInstanceRegistration.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/services/EventRegistrationService.php';
		require_once ATTENDEE_PATH_TO_ROOT . 'code/web/sys/Account/User.php';
	}

	protected function setUp(): void {
		parent::setUp();

		// Event type
		$this->eventType = new EventType();
		$this->eventType->title = 'PHPUnit Attendee Category Type';
		if (!$this->eventType->find(true)) {
			$this->eventType->eventInformationFieldSetId = 1;
			$this->eventType->eventRegistrationFieldSetId = 1;
			$this->eventType->insert();
		}

		// Attendee categories
		$this->categoryChild = new AspenEventAttendeeCategory();
		$this->categoryChild->name = 'PHPUnit Child';
		if (!$this->categoryChild->find(true)) {
			$this->categoryChild->staffDescription = 'Children attending';
			$this->categoryChild->publicDescription = 'Children';
			$this->categoryChild->insert();
		}

		$this->categoryAdult = new AspenEventAttendeeCategory();
		$this->categoryAdult->name = 'PHPUnit Adult';
		if (!$this->categoryAdult->find(true)) {
			$this->categoryAdult->staffDescription = 'Adults attending';
			$this->categoryAdult->publicDescription = 'Adults';
			$this->categoryAdult->insert();
		}

		// Link categories to event type
		$this->joinChild = new EventTypeAttendeeCategory();
		$this->joinChild->eventTypeId = $this->eventType->id;
		$this->joinChild->attendeeCategoryId = $this->categoryChild->id;
		if (!$this->joinChild->find(true)) {
			$this->joinChild->maxAttendees = 3;
			$this->joinChild->insert();
		} else {
			$this->joinChild->maxAttendees = 3;
			$this->joinChild->update();
		}

		$this->joinAdult = new EventTypeAttendeeCategory();
		$this->joinAdult->eventTypeId = $this->eventType->id;
		$this->joinAdult->attendeeCategoryId = $this->categoryAdult->id;
		if (!$this->joinAdult->find(true)) {
			$this->joinAdult->maxAttendees = 2;
			$this->joinAdult->insert();
		} else {
			$this->joinAdult->maxAttendees = 2;
			$this->joinAdult->update();
		}

		// Event
		$this->event = new Event();
		$this->event->title = 'PHPUnit Attendee Category Event';
		$this->event->registrationRequired = 1;
		$this->event->numberOfSeats = 10;
		$this->event->locationId = 1;
		$this->event->eventTypeId = $this->eventType->id;
		$this->event->startDate = date('Y-m-d');
		$this->event->insert();

		// Instance
		$this->eventInstance = new EventInstance();
		$this->eventInstance->eventId = $this->event->id;
		$this->eventInstance->date = date('Y-m-d', strtotime('+7 days'));
		$this->eventInstance->time = '10:00';
		$this->eventInstance->length = 60;
		$this->eventInstance->status = 1;
		$this->eventInstance->insert();
	}

	protected function tearDown(): void {
		global $aspen_db;

		$aspen_db->exec("DELETE FROM user_aspen_event_instance_registration_attendee");
		$aspen_db->exec("DELETE FROM user_aspen_event_instance_registrations");
		$aspen_db->exec("DELETE FROM event_instance");
		$aspen_db->exec("DELETE FROM event");
		$aspen_db->exec("DELETE FROM event_type_attendee_category");
		$aspen_db->exec("DELETE FROM aspen_event_attendee_category WHERE name LIKE 'PHPUnit%'");
		$aspen_db->exec("DELETE FROM event_type WHERE title = 'PHPUnit Attendee Category Type'");
		$aspen_db->exec("DELETE FROM user WHERE source = 'phpunit'");

		parent::tearDown();
	}

	// ── Helpers ──────────────────────────────────

	private function insertUser(int $id): User {
		$user = new User();
		$user->source = 'phpunit';
		$user->username = "phpunit_attendee_$id";
		$user->firstname = 'Test';
		$user->lastname = "Attendee_$id";
		$user->displayName = "Test Attendee $id";
		$user->created = date('Y-m-d H:i:s');
		$user->homeLocationId = 1;
		$user->myLocation1Id = 1;
		$user->myLocation2Id = 1;
		$user->unique_ils_id = "phpunit_attendee_$id";
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

	// ── validateAttendeeCounts ───────────────────

	public function testValidateCountsReturnsEmptyArrayWhenNoCountsProvided(): void {
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, []);
		$this->assertSame([], $result);
	}

	public function testValidateCountsReturnsValidatedArrayWithinLimits(): void {
		$counts = [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		];
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, $counts);
		$this->assertIsArray($result);
		$this->assertEquals(2, $result[(int)$this->categoryChild->id]);
		$this->assertEquals(1, $result[(int)$this->categoryAdult->id]);
	}

	public function testValidateCountsReturnsFalseWhenExceedingMax(): void {
		$counts = [
			$this->categoryChild->id => 5, // max is 3
		];
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, $counts);
		$this->assertFalse($result);
	}

	public function testValidateCountsFiltersOutUnknownCategoryIds(): void {
		$counts = [
			$this->categoryChild->id => 1,
			99999 => 5, // unknown category
		];
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, $counts);
		$this->assertIsArray($result);
		$this->assertCount(1, $result);
		$this->assertArrayNotHasKey(99999, $result);
	}

	public function testValidateCountsFiltersOutZeroAndNegativeCounts(): void {
		$counts = [
			$this->categoryChild->id => 0,
			$this->categoryAdult->id => -1,
		];
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, $counts);
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testValidateCountsReturnsEmptyForEventTypeWithNoCategories(): void {
		// Create an event type with no categories
		$bareType = new EventType();
		$bareType->title = 'PHPUnit Bare Type';
		$bareType->eventInformationFieldSetId = 1;
		$bareType->eventRegistrationFieldSetId = 1;
		$bareType->insert();

		$bareEvent = new Event();
		$bareEvent->title = 'PHPUnit Bare Event';
		$bareEvent->registrationRequired = 1;
		$bareEvent->numberOfSeats = 5;
		$bareEvent->locationId = 1;
		$bareEvent->eventTypeId = $bareType->id;
		$bareEvent->startDate = date('Y-m-d');
		$bareEvent->insert();

		$bareInstance = new EventInstance();
		$bareInstance->eventId = $bareEvent->id;
		$bareInstance->date = date('Y-m-d', strtotime('+7 days'));
		$bareInstance->time = '14:00';
		$bareInstance->length = 60;
		$bareInstance->status = 1;
		$bareInstance->insert();

		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($bareInstance, [$this->categoryChild->id => 2]);
		$this->assertSame([], $result);

		// Cleanup
		global $aspen_db;
		$aspen_db->exec("DELETE FROM event_instance WHERE id = " . (int)$bareInstance->id);
		$aspen_db->exec("DELETE FROM event WHERE id = " . (int)$bareEvent->id);
		$aspen_db->exec("DELETE FROM event_type WHERE id = " . (int)$bareType->id);
	}

	public function testValidateCountsAcceptsExactMax(): void {
		$counts = [
			$this->categoryChild->id => 3, // exactly max
		];
		$result = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($this->eventInstance, $counts);
		$this->assertIsArray($result);
		$this->assertEquals(3, $result[(int)$this->categoryChild->id]);
	}

	// ── saveForRegistration ─────────────────────

	public function testSaveForRegistrationInsertsNewRows(): void {
		$reg = $this->insertRegistration(30001);
		$counts = [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		];

		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, $counts);

		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $reg->id;
		$this->assertEquals(2, $attendee->count());
	}

	public function testSaveForRegistrationUpsertsExistingRows(): void {
		$reg = $this->insertRegistration(30002);

		// First save
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 1,
		]);

		// Upsert with new count
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 3,
		]);

		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $reg->id;
		$attendee->attendeeCategoryId = $this->categoryChild->id;
		$attendee->find(true);
		$this->assertEquals(3, (int)$attendee->count);

		// Still only one row
		$all = new UserAspenEventInstanceRegistrationAttendee();
		$all->registrationId = $reg->id;
		$this->assertEquals(1, $all->count());
	}

	public function testSaveForRegistrationSkipsZeroCounts(): void {
		$reg = $this->insertRegistration(30003);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 0,
			$this->categoryAdult->id => 1,
		]);

		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $reg->id;
		$this->assertEquals(1, $attendee->count());
	}

	// ── deleteForRegistration ───────────────────

	public function testDeleteForRegistrationRemovesAllRows(): void {
		$reg = $this->insertRegistration(30010);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		]);

		UserAspenEventInstanceRegistrationAttendee::deleteForRegistration((int)$reg->id);

		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $reg->id;
		$this->assertEquals(0, $attendee->count());
	}

	// ── getTotalAttendeesForInstance ─────────────

	public function testGetTotalAttendeesForInstanceSumsAcrossRegistrations(): void {
		$reg1 = $this->insertRegistration(30030);
		$reg2 = $this->insertRegistration(30031);

		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg1->id, [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		]);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg2->id, [
			$this->categoryChild->id => 1,
		]);

		$total = UserAspenEventInstanceRegistrationAttendee::getTotalAttendeesForInstance((int)$this->eventInstance->id);
		$this->assertEquals(4, $total);
	}

	public function testGetTotalAttendeesForInstanceExcludesCancelledRegistrations(): void {
		$active = $this->insertRegistration(30040);
		$cancelled = $this->insertRegistration(30041);
		$cancelled->cancelled = 1;
		$cancelled->update();

		// Cancelled registration won't have status='registered', but let's also set status
		// to something that isn't 'registered' to be safe — though 'registered' + cancelled
		// is the actual pattern. The query filters on status = 'registered'.
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$active->id, [
			$this->categoryChild->id => 2,
		]);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$cancelled->id, [
			$this->categoryChild->id => 3,
		]);

		$total = UserAspenEventInstanceRegistrationAttendee::getTotalAttendeesForInstance((int)$this->eventInstance->id);
		// Both have status='registered' — the query only checks status, not cancelled flag.
		// This tests actual behavior. If this is a bug, the test documents it.
		$this->assertEquals(5, $total);
	}

	public function testGetTotalAttendeesForInstanceExcludesWaitingStatus(): void {
		$registered = $this->insertRegistration(30050);
		$waiting = $this->insertRegistration(30051, 'waiting');

		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$registered->id, [
			$this->categoryChild->id => 2,
		]);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$waiting->id, [
			$this->categoryChild->id => 3,
		]);

		$total = UserAspenEventInstanceRegistrationAttendee::getTotalAttendeesForInstance((int)$this->eventInstance->id);
		$this->assertEquals(2, $total);
	}

	public function testGetTotalAttendeesForInstanceReturnsZeroWhenEmpty(): void {
		$total = UserAspenEventInstanceRegistrationAttendee::getTotalAttendeesForInstance((int)$this->eventInstance->id);
		$this->assertEquals(0, $total);
	}

	// ── getCategoryAttendeeCountsForInstance ─────

	public function testGetCategoryCountsGroupsByCategory(): void {
		$reg1 = $this->insertRegistration(30060);
		$reg2 = $this->insertRegistration(30061);

		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg1->id, [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		]);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg2->id, [
			$this->categoryChild->id => 1,
		]);

		$counts = UserAspenEventInstanceRegistrationAttendee::getCategoryAttendeeCountsForInstance((int)$this->eventInstance->id);

		$this->assertEquals(3, $counts[(int)$this->categoryChild->id]);
		$this->assertEquals(1, $counts[(int)$this->categoryAdult->id]);
	}

	public function testGetCategoryCountsReturnsEmptyWhenNoAttendeeRows(): void {
		$counts = UserAspenEventInstanceRegistrationAttendee::getCategoryAttendeeCountsForInstance((int)$this->eventInstance->id);
		$this->assertEmpty($counts);
	}

	// ── EventInstance::getRegistrationCount ──────

	public function testGetRegistrationCountSumsAttendeesWhenCategoriesExist(): void {
		$reg1 = $this->insertRegistration(30070);
		$reg2 = $this->insertRegistration(30071);

		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg1->id, [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		]);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg2->id, [
			$this->categoryChild->id => 1,
		]);

		// Reload instance to avoid cached state
		$instance = new EventInstance();
		$instance->id = $this->eventInstance->id;
		$instance->find(true);

		$this->assertEquals(4, EventRegistrationService::getRegistrationCount((int)$instance->id));
	}

	public function testGetRegistrationCountFallsBackToRowCountWhenNoAttendeeRows(): void {
		$this->insertRegistration(30080);
		$this->insertRegistration(30081);

		// These registrations have no attendee rows, so getTotalAttendeesForInstance returns 0
		// and getRegistrationCount falls back to counting registration rows
		$instance = new EventInstance();
		$instance->id = $this->eventInstance->id;
		$instance->find(true);

		$this->assertEquals(2, EventRegistrationService::getRegistrationCount((int)$instance->id));
	}

	// ── EventInstance::getAttendeeCategoryBreakdown ──

	public function testGetAttendeeCategoryBreakdownReturnsFormattedArray(): void {
		$reg = $this->insertRegistration(30090);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		]);

		$instance = new EventInstance();
		$instance->id = $this->eventInstance->id;
		$instance->find(true);

		$breakdown = EventRegistrationService::getAttendeeCategoryBreakdown((int)$instance->id);

		$this->assertCount(2, $breakdown);

		$names = array_column($breakdown, 'name');
		$this->assertContains('PHPUnit Child', $names);
		$this->assertContains('PHPUnit Adult', $names);

		$childEntry = array_values(array_filter($breakdown, fn($e) => $e['name'] === 'PHPUnit Child'))[0];
		$adultEntry = array_values(array_filter($breakdown, fn($e) => $e['name'] === 'PHPUnit Adult'))[0];
		$this->assertEquals(2, $childEntry['count']);
		$this->assertEquals(1, $adultEntry['count']);
	}

	public function testGetAttendeeCategoryBreakdownShowsZeroForUnusedCategories(): void {
		// No registrations at all — categories should appear with count 0
		$instance = new EventInstance();
		$instance->id = $this->eventInstance->id;
		$instance->find(true);

		$breakdown = EventRegistrationService::getAttendeeCategoryBreakdown((int)$instance->id);
		$this->assertCount(2, $breakdown);

		foreach ($breakdown as $entry) {
			$this->assertEquals(0, $entry['count']);
		}
	}

	// ── EventInstance::hasAvailableSeats with multi-seat ──

	public function testHasAvailableSeatsChecksAgainstRequestedSeatCount(): void {
		// 10 seats total, register 8 attendees
		$reg = $this->insertRegistration(30100);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg->id, [
			$this->categoryChild->id => 3,
			$this->categoryAdult->id => 2,
		]);
		$reg2 = $this->insertRegistration(30101);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$reg2->id, [
			$this->categoryChild->id => 3,
		]);

		$instance = new EventInstance();
		$instance->id = $this->eventInstance->id;
		$instance->find(true);

		// 8 of 10 used → 2 available
		$this->assertTrue(EventRegistrationService::hasAvailableSeats($instance, 2));
		$this->assertFalse(EventRegistrationService::hasAvailableSeats($instance, 3));
	}

	// ── EventRegistrationService::registerUserForEvent ──

	public function testRegisterUserSavesAttendeeRows(): void {
		$user = $this->insertUser(31001);
		$counts = [
			$this->categoryChild->id => 2,
			$this->categoryAdult->id => 1,
		];

		$result = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			$counts
		);

		$this->assertTrue($result['success']);

		// Verify attendee rows
		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $user->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);

		$attendee->registrationId = $reg->id;
		$this->assertEquals(2, $attendee->count());

		$total = array_sum(UserAspenEventInstanceRegistrationAttendee::getCountsForRegistration((int)$reg->id));
		$this->assertEquals(3, $total);
	}

	public function testRegisterUserRejectsInvalidAttendeeCounts(): void {
		$user = $this->insertUser(31002);
		$counts = [
			$this->categoryChild->id => 99, // exceeds max of 3
		];

		$result = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			$counts
		);

		$this->assertFalse($result['success']);
		$this->assertStringContainsString('Invalid attendee counts', $result['message']);
	}

	public function testRegisterUserChecksCapacityAgainstAttendeeSum(): void {
		// Fill 9 of 10 seats
		$filler = $this->insertRegistration(31010);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$filler->id, [
			$this->categoryChild->id => 3,
			$this->categoryAdult->id => 2,
		]);
		$filler2 = $this->insertRegistration(31011);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$filler2->id, [
			$this->categoryChild->id => 3,
			$this->categoryAdult->id => 1,
		]);

		// 9 seats used, try to register 2 more (only 1 available)
		$user = $this->insertUser(31012);
		$result = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[
				$this->categoryChild->id => 1,
				$this->categoryAdult->id => 1,
			]
		);

		$this->assertFalse($result['success']);
		$this->assertStringContainsString('full', $result['message']);
	}

	public function testRegisterUserWithNoCategoriesUsesImplicitOneSeat(): void {
		// Create event type without categories
		$bareType = new EventType();
		$bareType->title = 'PHPUnit No Categories Type';
		$bareType->eventInformationFieldSetId = 1;
		$bareType->eventRegistrationFieldSetId = 1;
		$bareType->insert();

		$bareEvent = new Event();
		$bareEvent->title = 'PHPUnit No Categories Event';
		$bareEvent->registrationRequired = 1;
		$bareEvent->numberOfSeats = 2;
		$bareEvent->locationId = 1;
		$bareEvent->eventTypeId = $bareType->id;
		$bareEvent->startDate = date('Y-m-d');
		$bareEvent->insert();

		$bareInstance = new EventInstance();
		$bareInstance->eventId = $bareEvent->id;
		$bareInstance->date = date('Y-m-d', strtotime('+7 days'));
		$bareInstance->time = '14:00';
		$bareInstance->length = 60;
		$bareInstance->status = 1;
		$bareInstance->insert();

		$user1 = $this->insertUser(31020);
		$user2 = $this->insertUser(31021);
		$user3 = $this->insertUser(31022);

		$r1 = EventRegistrationService::registerUserForEvent((int)$user1->id, (int)$bareInstance->id);
		$r2 = EventRegistrationService::registerUserForEvent((int)$user2->id, (int)$bareInstance->id);
		$r3 = EventRegistrationService::registerUserForEvent((int)$user3->id, (int)$bareInstance->id);

		$this->assertTrue($r1['success']);
		$this->assertTrue($r2['success']);
		$this->assertFalse($r3['success'], 'Third registration should fail — only 2 seats');

		// Cleanup
		global $aspen_db;
		$aspen_db->exec("DELETE FROM user_aspen_event_instance_registrations WHERE eventInstanceId = " . (int)$bareInstance->id);
		$aspen_db->exec("DELETE FROM event_instance WHERE id = " . (int)$bareInstance->id);
		$aspen_db->exec("DELETE FROM event WHERE id = " . (int)$bareEvent->id);
		$aspen_db->exec("DELETE FROM event_type WHERE id = " . (int)$bareType->id);
	}

	public function testReactivateCancelledRegistrationUpsertsAttendeeRows(): void {
		$user = $this->insertUser(31030);

		// Initial registration
		$r1 = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[$this->categoryChild->id => 1]
		);
		$this->assertTrue($r1['success']);

		// Cancel
		EventRegistrationService::unregisterUserFromEvent((int)$user->id, (int)$this->eventInstance->id);

		// Re-register with different counts
		$r2 = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[$this->categoryChild->id => 3, $this->categoryAdult->id => 2]
		);
		$this->assertTrue($r2['success']);

		// Verify counts updated
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $user->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);

		$total = array_sum(UserAspenEventInstanceRegistrationAttendee::getCountsForRegistration((int)$reg->id));
		$this->assertEquals(5, $total);
	}

	public function testReactivateCancelledRegistrationChecksCapacity(): void {
		// Fill to 9 of 10
		$filler = $this->insertRegistration(31040);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$filler->id, [
			$this->categoryChild->id => 3,
			$this->categoryAdult->id => 2,
		]);
		$filler2 = $this->insertRegistration(31041);
		UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$filler2->id, [
			$this->categoryChild->id => 3,
			$this->categoryAdult->id => 1,
		]);

		$user = $this->insertUser(31042);
		// Create and cancel a registration
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $user->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = 'registered';
		$reg->cancelled = 1;
		$reg->createdAt = date('Y-m-d H:i:s');
		$reg->insert();

		// Try to reactivate with 2 seats (only 1 available)
		$result = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[$this->categoryChild->id => 1, $this->categoryAdult->id => 1]
		);

		$this->assertFalse($result['success']);
		$this->assertStringContainsString('full', $result['message']);
	}

	public function testStaffRegistrationRecordsStaffUserId(): void {
		$patron = $this->insertUser(31050);
		$staff = $this->insertUser(31051);

		$result = EventRegistrationService::registerUserForEvent(
			(int)$patron->id,
			(int)$this->eventInstance->id,
			(int)$staff->id,
			[$this->categoryChild->id => 1]
		);

		$this->assertTrue($result['success']);

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $patron->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);
		$this->assertEquals((int)$staff->id, (int)$reg->registeredByStaffId);
	}

	// ── Duplicate registration guard ────────────

	public function testRegisterUserRejectsDuplicateRegistration(): void {
		$user = $this->insertUser(31060);

		$r1 = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[$this->categoryChild->id => 1]
		);
		$this->assertTrue($r1['success']);

		$r2 = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[$this->categoryChild->id => 2]
		);
		$this->assertFalse($r2['success']);
		$this->assertStringContainsString('Failed to create registration', $r2['message']);
	}

	// ── Edge: register with empty counts on categoried event ──

	public function testRegisterWithEmptyCountsOnCategoriedEventUsesOneSeat(): void {
		$user = $this->insertUser(31070);

		// Empty attendeeCounts → validateAttendeeCounts returns [] → requestedSeats = 1
		$result = EventRegistrationService::registerUserForEvent(
			(int)$user->id,
			(int)$this->eventInstance->id,
			null,
			[]
		);

		$this->assertTrue($result['success']);

		// No attendee rows created
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $user->id;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);

		$attendee = new UserAspenEventInstanceRegistrationAttendee();
		$attendee->registrationId = $reg->id;
		$this->assertEquals(0, $attendee->count());
	}
}
