<?php

use PHPUnit\Framework\TestCase;
define('PATH_TO_ROOT', __DIR__ . '/../../../../../');

/**
 * Tests for the waiting list model on UserAspenEventInstanceRegistration and
 * the deletion / cancellation flow across Event and EventInstance.
 *
 * Database-hitting tests use real inserts (matching codebase conventions).
 * Pure logic tests use plain assertions.
 */
class EventWaitingListTests extends TestCase {

	private Event $event;
	private EventInstance $eventInstance;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once PATH_TO_ROOT . 'code/web/sys/Utils/DateUtils.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Events/Event.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Events/EventInstance.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Events/UserAspenEventInstanceRegistration.php';
		require_once PATH_TO_ROOT . 'code/web/services/EventRegistrationService.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Events/EventType.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Account/User.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$eventType = new EventType();
		$eventType->title = 'PHPUnit Test Type';
		if (!$eventType->find(true)) {
			$eventType->eventInformationFieldSetId = 1;
			$eventType->eventRegistrationFieldSetId = 1;
			$eventType->insert();
		}

		$this->event = new Event();
		$this->event->title = 'PHPUnit Waiting List Event';
		$this->event->registrationRequired = 1;
		$this->event->numberOfSeats = 2;
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

		$aspen_db->exec("DELETE FROM user_aspen_event_instance_registrations");
		$aspen_db->exec("DELETE FROM event_instance");
		$aspen_db->exec("DELETE FROM event");
		$aspen_db->exec("DELETE FROM user WHERE source = 'phpunit'");

		parent::tearDown();
	}

	private function insertUser(int $id, string $email, int $notificationsOn): User {
		$user = new User();
		$user->source = 'phpunit';
		$user->username = "phpunit_user_$id";
		$user->firstname = 'Test';
		$user->lastname = "User_$id";
		$user->displayName = "Test User $id";
		$user->created = date('Y-m-d H:i:s');
		$user->homeLocationId = 1;
		$user->myLocation1Id = 1;
		$user->myLocation2Id = 1;
		$user->unique_ils_id = "phpunit_user_$id";
		$user->email = $email;
		$user->eventRegistrationNotificationsByEmail = $notificationsOn;
		$user->insert();
		return $user;
	}

	private function insertRegistration(int $userId, string $status, string $createdAt = null, string $notifiedAt = null): UserAspenEventInstanceRegistration {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = $userId;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->status = $status;
		$reg->createdAt = $createdAt ?? date('Y-m-d H:i:s');
		if ($notifiedAt !== null) {
			$reg->notifiedAt = $notifiedAt;
		}
		$reg->insert();
		return $reg;
	}

	// ── validateStatus ───────────────────────────

	public function testValidateStatusAcceptsValidStatuses(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$method = new ReflectionMethod(UserAspenEventInstanceRegistration::class, 'validateStatus');
		$method->setAccessible(true);

		$this->assertTrue($method->invoke($reg, 'waiting'));
		$this->assertTrue($method->invoke($reg, 'invited'));
		$this->assertTrue($method->invoke($reg, 'registered'));
	}

	public function testValidateStatusRejectsInvalidStatus(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$method = new ReflectionMethod(UserAspenEventInstanceRegistration::class, 'validateStatus');
		$method->setAccessible(true);

		$this->assertFalse($method->invoke($reg, 'expired'));
		$this->assertFalse($method->invoke($reg, ''));
		$this->assertFalse($method->invoke($reg, 'REGISTERED'));
	}

	// ── Uniqueness ───────────────────────────────

	public function testUniquenessFieldsAreUserIdAndEventInstanceId(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$fields = $reg->getUniquenessFields();

		$this->assertContains('userId', $fields);
		$this->assertContains('eventInstanceId', $fields);
		$this->assertCount(2, $fields);
	}

	// ── getWaitingListInfo ──────────────────────

	public function testGetWaitingListInfoReturnsPositionWhenWaiting(): void {
		$this->insertRegistration(5001, 'waiting', '2026-04-09 10:00:00');

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 5001;
		$reg->eventInstanceId = $this->eventInstance->id;

		$info = $reg->getWaitingListInfo();
		$this->assertTrue($info['onWaitingList']);
		$this->assertEquals(1, $info['position']);
		$this->assertFalse($info['canRegister']);
	}

	public function testGetWaitingListInfoReturnsCanRegisterWhenInvited(): void {
		$this->insertRegistration(5002, 'invited', '2026-04-09 10:00:00');

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 5002;
		$reg->eventInstanceId = $this->eventInstance->id;

		$info = $reg->getWaitingListInfo();
		$this->assertTrue($info['onWaitingList']);
		$this->assertTrue($info['canRegister']);
	}

	public function testGetWaitingListInfoReturnsDefaultWhenRegistered(): void {
		$this->insertRegistration(5003, 'registered');

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 5003;
		$reg->eventInstanceId = $this->eventInstance->id;

		$info = $reg->getWaitingListInfo();
		$this->assertFalse($info['onWaitingList']);
		$this->assertNull($info['position']);
		$this->assertFalse($info['canRegister']);
	}

	public function testGetWaitingListInfoReturnsDefaultWhenNoRow(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 99999;
		$reg->eventInstanceId = $this->eventInstance->id;

		$info = $reg->getWaitingListInfo();
		$this->assertFalse($info['onWaitingList']);
		$this->assertNull($info['position']);
		$this->assertFalse($info['canRegister']);
	}

	// ── isUserRegisteredForEvent ────────────────

	public function testIsUserRegisteredForEventTrueWhenRegistered(): void {
		$this->insertRegistration(5101, 'registered');

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 5101;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);

		$this->assertTrue($reg->isUserRegisteredForEvent());
	}

	public function testIsUserRegisteredForEventFalseWhenWaiting(): void {
		$this->insertRegistration(5102, 'waiting');

		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 5102;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->find(true);

		$this->assertFalse($reg->isUserRegisteredForEvent());
	}

	public function testIsUserRegisteredForEventFalseWhenNoRow(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 99998;
		$reg->eventInstanceId = $this->eventInstance->id;

		$this->assertFalse($reg->isUserRegisteredForEvent());
	}

	// ── Joining the waiting list ─────────────────

	public function testJoinWaitingListCreatesRowWithWaitingStatus(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 1001;
		$reg->eventInstanceId = $this->eventInstance->id;
		$result = $reg->addUserToWaitingList();

		$this->assertNotFalse($result);

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = 1001;
		$check->eventInstanceId = $this->eventInstance->id;
		$this->assertTrue($check->find(true));
		$this->assertEquals('waiting', $check->status);
	}

	public function testJoinWaitingListRejectsDuplicateForSameUserAndInstance(): void {
		$this->insertRegistration(1002, 'waiting');

		$reg2 = new UserAspenEventInstanceRegistration();
		$reg2->userId = 1002;
		$reg2->eventInstanceId = $this->eventInstance->id;
		$this->assertFalse($reg2->addUserToWaitingList());
	}

	// ── Position by timestamp ────────────────────

	public function testWaitingListPositionIsDerivedFromCreatedAtOrder(): void {
		$this->insertRegistration(2001, 'waiting', '2026-04-09 10:00:00');
		$this->insertRegistration(2002, 'waiting', '2026-04-09 10:05:00');
		$this->insertRegistration(2003, 'waiting', '2026-04-09 10:10:00');

		$this->assertEquals(1, UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:00:00'));
		$this->assertEquals(2, UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:05:00'));
		$this->assertEquals(3, UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:10:00'));
	}

	public function testWaitingListPositionStaticCountsOnlyWaitingAndInvited(): void {
		$this->insertRegistration(2101, 'registered', '2026-04-09 09:00:00');
		$this->insertRegistration(2102, 'waiting', '2026-04-09 10:00:00');

		$position = UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:00:00');
		$this->assertEquals(1, $position, 'Registered user ahead in time should not count toward queue position');
	}

	public function testPositionIsStableAfterMiddleUserLeaves(): void {
		$this->insertRegistration(3001, 'waiting', '2026-04-09 10:00:00');
		$middle = $this->insertRegistration(3002, 'waiting', '2026-04-09 10:05:00');
		$this->insertRegistration(3003, 'waiting', '2026-04-09 10:10:00');

		$middle->delete();

		$this->assertEquals(1, UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:00:00'));
		$this->assertEquals(2, UserAspenEventInstanceRegistration::getWaitingListPosition($this->eventInstance->id, '2026-04-09 10:10:00'));
	}

	// ── Converting to registered ─────────────────

	public function testConvertFromWaitingListToRegistered(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 4001;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->addUserToWaitingList();

		$promote = new UserAspenEventInstanceRegistration();
		$promote->userId = 4001;
		$promote->eventInstanceId = $this->eventInstance->id;
		$promote->registerUser();

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = 4001;
		$check->eventInstanceId = $this->eventInstance->id;
		$check->find(true);
		$this->assertEquals('registered', $check->status);
	}

	public function testConversionIsASingleRowUpdate(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 4002;
		$reg->eventInstanceId = $this->eventInstance->id;
		$reg->addUserToWaitingList();

		$originalId = $reg->id;

		$promote = new UserAspenEventInstanceRegistration();
		$promote->userId = 4002;
		$promote->eventInstanceId = $this->eventInstance->id;
		$promote->registerUser();

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = 4002;
		$check->eventInstanceId = $this->eventInstance->id;
		$check->find(true);
		$this->assertEquals($originalId, $check->id);
	}

	public function testRegisterUserCreatesRowWhenNoneExists(): void {
		$reg = new UserAspenEventInstanceRegistration();
		$reg->userId = 4004;
		$reg->eventInstanceId = $this->eventInstance->id;
		$this->assertNotFalse($reg->registerUser());

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = 4004;
		$check->eventInstanceId = $this->eventInstance->id;
		$check->find(true);
		$this->assertEquals('registered', $check->status);
	}

	// ── Capacity checks ──────────────────────────

	public function testWaitingListCountOnlyCountsWaitingAndInvited(): void {
		$this->insertRegistration(6001, 'registered');
		$this->insertRegistration(6002, 'waiting');
		$this->insertRegistration(6003, 'invited');

		$this->assertEquals(2, UserAspenEventInstanceRegistration::getWaitingListCount((int)$this->eventInstance->id));
	}

	public function testRegistrationCountOnlyCountsRegisteredRows(): void {
		$this->insertRegistration(6004, 'registered');
		$this->insertRegistration(6005, 'waiting');

		$this->assertEquals(1, UserAspenEventInstanceRegistration::getRegistrationCount((int)$this->eventInstance->id));
	}

	public function testWaitingListFullWhenCountReachesCapacity(): void {
		for ($i = 0; $i < 3; $i++) {
			$this->insertRegistration(7000 + $i, 'waiting', date('Y-m-d H:i:s', strtotime("+{$i} minutes")));
		}
		$this->assertTrue(EventRegistrationService::isWaitingListFull($this->eventInstance));
	}

	public function testWaitingListNotFullWhenBelowCapacity(): void {
		$this->insertRegistration(7010, 'waiting');
		$this->assertFalse(EventRegistrationService::isWaitingListFull($this->eventInstance));
	}

	// ── Available seats ──────────────────────────

	public function testAvailableSeatsIsZeroWhenWaitingListHasEntries(): void {
		$this->insertRegistration(9001, 'waiting');
		$this->assertEquals(0, EventRegistrationService::getAvailableSeats($this->eventInstance));
	}

	public function testAvailableSeatsReflectsCapacityMinusRegisteredWhenNoWaiters(): void {
		$this->insertRegistration(9002, 'registered');
		$this->assertEquals(1, EventRegistrationService::getAvailableSeats($this->eventInstance));
	}

	// ── Status messages ──────────────────────────

	public function testStatusMessageRegistrationAvailableWhenNoWaitingList(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(false, false, false, 0, false, false);
		$this->assertEquals('Registration available', $msg);
	}

	public function testStatusMessagePositionWhenOnWaitingList(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(true, true, false, 3, true, false);
		$this->assertEquals('On waiting list - position 3', $msg);
	}

	public function testStatusMessageCanRegisterFromWaitingList(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(true, true, true, 0, true, false);
		$this->assertEquals('Registration available', $msg);
	}

	public function testStatusMessageWaitingListAvailable(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(true, false, false, 0, true, false);
		$this->assertEquals('Waiting List available', $msg);
	}

	public function testStatusMessageRegistrationUnavailable(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(true, false, false, 0, true, true);
		$this->assertEquals('Registration unavailable', $msg);
	}

	public function testStatusMessageRegistrationAvailableWhenNotFull(): void {
		$msg = EventRegistrationService::getRegistrationStatusMessage(true, false, false, 0, false, false);
		$this->assertEquals('Registration available', $msg);
	}

	// ── Registration action ──────────────────────

	public function testRegistrationActionRegisteredShortCircuits(): void {
		$this->assertEquals('registered', EventRegistrationService::getRegistrationAction(true, true, true, true, true, true));
	}

	public function testRegistrationActionNotFullReturnsRegistrationAvailable(): void {
		$this->assertEquals('registrationAvailable', EventRegistrationService::getRegistrationAction(false, false, true, false, false, false));
	}

	public function testRegistrationActionFullNoWaitlist(): void {
		$this->assertEquals('eventFull', EventRegistrationService::getRegistrationAction(false, true, false, false, false, false));
	}

	public function testRegistrationActionCompleteRegistrationWhenInvited(): void {
		$this->assertEquals('completeRegistration', EventRegistrationService::getRegistrationAction(false, true, true, true, true, false));
	}

	public function testRegistrationActionShowPositionWhenQueued(): void {
		$this->assertEquals('showPosition', EventRegistrationService::getRegistrationAction(false, true, true, true, false, false));
	}

	public function testRegistrationActionJoinWaitingListWhenRoom(): void {
		$this->assertEquals('joinWaitingList', EventRegistrationService::getRegistrationAction(false, true, true, false, false, false));
	}

	public function testRegistrationActionEventFullWhenWaitlistAlsoFull(): void {
		$this->assertEquals('eventFull', EventRegistrationService::getRegistrationAction(false, true, true, false, false, true));
	}

	// ── isUpcoming ───────────────────────────────

	public function testIsUpcomingTrueForFutureInstance(): void {
		$instance = new EventInstance();
		$instance->date = date('Y-m-d', strtotime('+1 day'));
		$instance->time = '10:00';
		$this->assertTrue($instance->isUpcoming());
	}

	public function testIsUpcomingFalseForPastInstance(): void {
		$instance = new EventInstance();
		$instance->date = date('Y-m-d', strtotime('-1 day'));
		$instance->time = '10:00';
		$this->assertFalse($instance->isUpcoming());
	}

	public function testIsUpcomingFalseWhenDateMissing(): void {
		$instance = new EventInstance();
		$instance->time = '10:00';
		$this->assertFalse($instance->isUpcoming());
	}

	public function testIsUpcomingFalseWhenTimeMissing(): void {
		$instance = new EventInstance();
		$instance->date = date('Y-m-d', strtotime('+1 day'));
		$this->assertFalse($instance->isUpcoming());
	}

	// ── addUpcomingWhereClause ───────────────────

	public function testAddUpcomingWhereClauseFiltersPastInstances(): void {
		$past = new EventInstance();
		$past->eventId = $this->event->id;
		$past->date = date('Y-m-d', strtotime('-1 day'));
		$past->time = '10:00';
		$past->length = 60;
		$past->status = 1;
		$past->insert();

		$query = new EventInstance();
		$query->eventId = $this->event->id;
		EventInstance::addUpcomingWhereClause($query);
		$query->find();

		$ids = [];
		while ($query->fetch()) {
			$ids[] = (int)$query->id;
		}
		$this->assertContains((int)$this->eventInstance->id, $ids);
		$this->assertNotContains((int)$past->id, $ids);
	}

	// ── Leaving / deletion ───────────────────────

	public function testLeaveWaitingListRemovesRow(): void {
		$reg = $this->insertRegistration(13001, 'waiting');
		$reg->delete();

		$check = new UserAspenEventInstanceRegistration();
		$check->userId = 13001;
		$check->eventInstanceId = $this->eventInstance->id;
		$this->assertFalse($check->find(true));
	}

	public function testLeaveWaitingListDoesNotAffectOtherUsers(): void {
		$regA = $this->insertRegistration(13002, 'waiting', '2026-04-09 10:00:00');
		$this->insertRegistration(13003, 'waiting', '2026-04-09 10:05:00');

		$regA->delete();

		$checkB = new UserAspenEventInstanceRegistration();
		$checkB->userId = 13003;
		$checkB->eventInstanceId = $this->eventInstance->id;
		$this->assertTrue($checkB->find(true));
	}

	// ── EventInstance capacity logic ─────────────

	public function testEffectiveSeatsUsesInstanceOverrideWhenSet(): void {
		$this->eventInstance->numberOfSeats = 10;
		$this->eventInstance->update();

		$reloaded = new EventInstance();
		$reloaded->id = $this->eventInstance->id;
		$reloaded->find(true);

		$this->assertEquals(10, $reloaded->getEffectiveNumberOfSeats());
	}

	public function testEffectiveSeatsFallsBackToEventDefault(): void {
		$this->eventInstance->numberOfSeats = 0;
		$this->eventInstance->update();

		$reloaded = new EventInstance();
		$reloaded->id = $this->eventInstance->id;
		$reloaded->find(true);

		$this->assertEquals($this->event->numberOfSeats, $reloaded->getEffectiveNumberOfSeats());
	}

	// ── Display format ───────────────────────────

	public function testDisplayWaitingListSeatsFormat(): void {
		$display = $this->eventInstance->getDisplayWaitingListSeats();
		$this->assertMatchesRegularExpression('/^\d+ \/ \d+$/', $display);
	}

	public function testDisplayWaitingListSeatsDashWhenDeleted(): void {
		$this->eventInstance->deleted = 1;
		$this->assertEquals('—', $this->eventInstance->getDisplayWaitingListSeats());
	}

	// ── Static helpers ───────────────────────────

	public function testGetWaitingRowIdsForInstanceOrdersByCreatedAt(): void {
		$late = $this->insertRegistration(14001, 'waiting', '2026-04-09 10:10:00');
		$early = $this->insertRegistration(14002, 'waiting', '2026-04-09 10:00:00');
		$middle = $this->insertRegistration(14003, 'waiting', '2026-04-09 10:05:00');

		$ids = UserAspenEventInstanceRegistration::getWaitingRowIdsForInstance((int)$this->eventInstance->id);

		$this->assertEquals([(int)$early->id, (int)$middle->id, (int)$late->id], $ids);
	}

	public function testGetWaitingRowIdsForInstanceExcludesNonWaitingStatuses(): void {
		$waiting = $this->insertRegistration(14101, 'waiting');
		$this->insertRegistration(14102, 'registered');
		$this->insertRegistration(14103, 'invited');

		$ids = UserAspenEventInstanceRegistration::getWaitingRowIdsForInstance((int)$this->eventInstance->id);
		$this->assertEquals([(int)$waiting->id], $ids);
	}

	public function testDeleteAllForEventInstanceRemovesEveryStatus(): void {
		$this->insertRegistration(15001, 'waiting');
		$this->insertRegistration(15002, 'invited');
		$this->insertRegistration(15003, 'registered');

		UserAspenEventInstanceRegistration::deleteAllForEventInstance((int)$this->eventInstance->id);

		$remaining = new UserAspenEventInstanceRegistration();
		$remaining->eventInstanceId = $this->eventInstance->id;
		$this->assertEquals(0, $remaining->count());
	}

	public function testGetUsersGroupedByStatusForInstance(): void {
		$this->insertRegistration(16001, 'registered');
		$this->insertRegistration(16002, 'waiting');
		$this->insertRegistration(16003, 'waiting');
		$this->insertRegistration(16004, 'invited');

		$grouped = UserAspenEventInstanceRegistration::getUsersGroupedByStatusForInstance((int)$this->eventInstance->id);

		$this->assertEqualsCanonicalizing([16001], $grouped['registered']);
		$this->assertEqualsCanonicalizing([16002, 16003], $grouped['waiting']);
		$this->assertEqualsCanonicalizing([16004], $grouped['invited']);
	}

	public function testGetUsersGroupedByStatusForInstancesAcrossMultipleInstances(): void {
		$instance2 = new EventInstance();
		$instance2->eventId = $this->event->id;
		$instance2->date = date('Y-m-d', strtotime('+8 days'));
		$instance2->time = '14:00';
		$instance2->length = 60;
		$instance2->status = 1;
		$instance2->insert();

		$this->insertRegistration(16101, 'registered');
		$this->insertRegistration(16102, 'waiting');

		$reg3 = new UserAspenEventInstanceRegistration();
		$reg3->userId = 16101;
		$reg3->eventInstanceId = $instance2->id;
		$reg3->status = 'registered';
		$reg3->createdAt = date('Y-m-d H:i:s');
		$reg3->insert();

		$reg4 = new UserAspenEventInstanceRegistration();
		$reg4->userId = 16103;
		$reg4->eventInstanceId = $instance2->id;
		$reg4->status = 'waiting';
		$reg4->createdAt = date('Y-m-d H:i:s');
		$reg4->insert();

		$grouped = UserAspenEventInstanceRegistration::getUsersGroupedByStatusForInstances(
			[(int)$this->eventInstance->id, (int)$instance2->id]
		);

		$this->assertEqualsCanonicalizing([16101], $grouped['registered']);
		$this->assertEqualsCanonicalizing([16102, 16103], $grouped['waiting']);
	}

	public function testGetUsersGroupedByStatusForInstancesWithEmptyInputReturnsEmptyArray(): void {
		$this->assertSame([], UserAspenEventInstanceRegistration::getUsersGroupedByStatusForInstances([]));
	}

	// ── isUserInvitedToRegister ──────────────────

	public function testIsUserInvitedToRegisterTrueWhenInvitedRowExists(): void {
		$this->insertRegistration(17001, 'invited');
		$this->assertTrue(UserAspenEventInstanceRegistration::isUserInvitedToRegister(17001));
	}

	public function testIsUserInvitedToRegisterFalseWhenOnlyWaitingOrRegistered(): void {
		$this->insertRegistration(17002, 'waiting');
		$this->insertRegistration(17003, 'registered');
		$this->assertFalse(UserAspenEventInstanceRegistration::isUserInvitedToRegister(17002));
		$this->assertFalse(UserAspenEventInstanceRegistration::isUserInvitedToRegister(17003));
	}

	// ── User::canReceiveEventNotifications ───────

	public function testCanReceiveTrueWithEmailAndToggleOn(): void {
		$user = new User();
		$user->email = 'alice@example.test';
		$user->eventRegistrationNotificationsByEmail = 1;
		$this->assertTrue($user->canReceiveEventNotifications());
	}

	public function testCanReceiveFalseWithNoEmail(): void {
		$user = new User();
		$user->email = '';
		$user->eventRegistrationNotificationsByEmail = 1;
		$this->assertFalse($user->canReceiveEventNotifications());
	}

	public function testCanReceiveFalseWithToggleOff(): void {
		$user = new User();
		$user->email = 'bob@example.test';
		$user->eventRegistrationNotificationsByEmail = 0;
		$this->assertFalse($user->canReceiveEventNotifications());
	}

	// ── inviteNextOnWaitingList reachability ─────

	public function testInviteNextPromotesReachableUser(): void {
		$user = $this->insertUser(18001, 'reachable@example.test', 1);
		$this->insertRegistration($user->id, 'waiting');

		$result = EventRegistrationService::inviteNextOnWaitingList($this->eventInstance);
		$this->assertTrue($result);

		$row = new UserAspenEventInstanceRegistration();
		$row->userId = $user->id;
		$row->eventInstanceId = $this->eventInstance->id;
		$row->find(true);
		$this->assertEquals('invited', $row->status);
		$this->assertNotNull($row->notifiedAt);
	}

	public function testInviteNextSkipsUnreachableUserWithoutDeleting(): void {
		$unreachableUser = $this->insertUser(18101, 'no-toggle@example.test', 0);
		$reachableUser = $this->insertUser(18102, 'reachable@example.test', 1);

		$unreachable = $this->insertRegistration($unreachableUser->id, 'waiting', '2026-04-09 10:00:00');
		$reachable = $this->insertRegistration($reachableUser->id, 'waiting', '2026-04-09 10:05:00');

		$result = EventRegistrationService::inviteNextOnWaitingList($this->eventInstance);
		$this->assertTrue($result);

		$check = new UserAspenEventInstanceRegistration();
		$check->id = $unreachable->id;
		$this->assertTrue($check->find(true), 'Unreachable user keeps their queue position');
		$this->assertEquals('waiting', $check->status, 'Unreachable user remains waiting — not banned');

		$reachableCheck = new UserAspenEventInstanceRegistration();
		$reachableCheck->id = $reachable->id;
		$reachableCheck->find(true);
		$this->assertEquals('invited', $reachableCheck->status);
	}

	public function testInviteNextReturnsFalseWhenAllUnreachable(): void {
		$user = $this->insertUser(18201, 'no-email@example.test', 1);
		$user->email = '';
		$user->update();

		$this->insertRegistration($user->id, 'waiting');

		$this->assertFalse(EventRegistrationService::inviteNextOnWaitingList($this->eventInstance));

		$row = new UserAspenEventInstanceRegistration();
		$row->userId = $user->id;
		$row->eventInstanceId = $this->eventInstance->id;
		$this->assertTrue($row->find(true), 'Unreachable user keeps their spot even when the queue is exhausted');
		$this->assertEquals('waiting', $row->status);
	}

	public function testInviteNextReturnsFalseWhenQueueEmpty(): void {
		$this->assertFalse(EventRegistrationService::inviteNextOnWaitingList($this->eventInstance));
	}

	public function testInviteNextDeletesRowsForMissingUsers(): void {
		$ghost = $this->insertRegistration(19001, 'waiting');

		$this->assertFalse(EventRegistrationService::inviteNextOnWaitingList($this->eventInstance));

		$check = new UserAspenEventInstanceRegistration();
		$check->id = $ghost->id;
		$this->assertFalse($check->find(true));
	}

	// ── EventInstance::delete ────────────────────

	public function testEventInstanceDeleteThrowsWhenUseWhereTrue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->eventInstance->delete(true);
	}

	public function testEventInstanceDeleteSoftDeletesAndCascadesChildren(): void {
		$this->insertRegistration(20001, 'registered');
		$this->insertRegistration(20002, 'waiting');

		$this->eventInstance->delete();

		$reloaded = new EventInstance();
		$reloaded->id = $this->eventInstance->id;
		$reloaded->_includeDeleted = true;
		$reloaded->find(true);
		$this->assertEquals(1, (int)$reloaded->deleted);

		$children = new UserAspenEventInstanceRegistration();
		$children->eventInstanceId = $this->eventInstance->id;
		$this->assertEquals(0, $children->count(), 'All child rows should be gone');
	}

	public function testEventInstanceDeleteCleansChildrenEvenWhenNotificationsSuppressed(): void {
		$this->insertRegistration(20101, 'registered');

		$this->eventInstance->delete(false, false, true);

		$children = new UserAspenEventInstanceRegistration();
		$children->eventInstanceId = $this->eventInstance->id;
		$this->assertEquals(0, $children->count());
	}

	// ── Event::delete ────────────────────────────

	public function testEventDeleteThrowsWhenUseWhereTrue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->event->delete(true);
	}

	public function testEventDeleteCascadesToAllInstances(): void {
		$past = new EventInstance();
		$past->eventId = $this->event->id;
		$past->date = date('Y-m-d', strtotime('-1 day'));
		$past->time = '10:00';
		$past->length = 60;
		$past->status = 1;
		$past->insert();

		$this->insertRegistration(21001, 'registered');

		$this->event->delete();

		$reloaded = new Event();
		$reloaded->id = $this->event->id;
		$reloaded->_includeDeleted = true;
		$reloaded->find(true);
		$this->assertEquals(1, (int)$reloaded->deleted);

		$reloadedInstance = new EventInstance();
		$reloadedInstance->id = $this->eventInstance->id;
		$reloadedInstance->_includeDeleted = true;
		$reloadedInstance->find(true);
		$this->assertEquals(1, (int)$reloadedInstance->deleted, 'Upcoming instance should be soft-deleted via cascade');

		$pastReloaded = new EventInstance();
		$pastReloaded->id = $past->id;
		$pastReloaded->_includeDeleted = true;
		$pastReloaded->find(true);
		$this->assertEquals(1, (int)$pastReloaded->deleted, 'Past instance should also be soft-deleted — the event is gone');

		$children = new UserAspenEventInstanceRegistration();
		$children->eventInstanceId = $this->eventInstance->id;
		$this->assertEquals(0, $children->count(), 'Registration rows under cascaded instance should be cleaned up');
	}

	// ── Event::getUpcomingInstances / getAllInstanceIds

	public function testGetUpcomingInstancesExcludesPastAndDeleted(): void {
		$past = new EventInstance();
		$past->eventId = $this->event->id;
		$past->date = date('Y-m-d', strtotime('-1 day'));
		$past->time = '10:00';
		$past->length = 60;
		$past->status = 1;
		$past->insert();

		$deleted = new EventInstance();
		$deleted->eventId = $this->event->id;
		$deleted->date = date('Y-m-d', strtotime('+2 days'));
		$deleted->time = '10:00';
		$deleted->length = 60;
		$deleted->status = 1;
		$deleted->deleted = 1;
		$deleted->insert();

		$upcoming = $this->event->getUpcomingInstances();
		$ids = [];
		foreach ($upcoming as $instance) {
			$ids[] = (int)$instance->id;
		}

		$this->assertContains((int)$this->eventInstance->id, $ids);
		$this->assertNotContains((int)$past->id, $ids);
		$this->assertNotContains((int)$deleted->id, $ids);
	}

	public function testGetAllInstanceIdsIncludesEverything(): void {
		$past = new EventInstance();
		$past->eventId = $this->event->id;
		$past->date = date('Y-m-d', strtotime('-1 day'));
		$past->time = '10:00';
		$past->length = 60;
		$past->status = 1;
		$past->insert();

		$ids = $this->event->getAllInstanceIds();
		$this->assertContains((int)$this->eventInstance->id, $ids);
		$this->assertContains((int)$past->id, $ids);
	}

	// ── getExpiredInvitedRowIds ──────────────────

	public function testGetExpiredInvitedRowIdsReturnsOldInvites(): void {
		$expired = $this->insertRegistration(22001, 'invited', null, date('Y-m-d H:i:s', strtotime('-48 hours')));
		$fresh = $this->insertRegistration(22002, 'invited', null, date('Y-m-d H:i:s', strtotime('-1 hour')));

		$ids = UserAspenEventInstanceRegistration::getExpiredInvitedRowIds();

		$this->assertContains((int)$expired->id, $ids, 'Old invite should be flagged as expired');
		$this->assertNotContains((int)$fresh->id, $ids, 'Recent invite should not be flagged');
	}

	public function testGetExpiredInvitedRowIdsSkipsSoftDeletedInstances(): void {
		$expired = $this->insertRegistration(22101, 'invited', null, date('Y-m-d H:i:s', strtotime('-48 hours')));

		$this->eventInstance->deleted = 1;
		$this->eventInstance->update();

		$ids = UserAspenEventInstanceRegistration::getExpiredInvitedRowIds();
		$this->assertNotContains((int)$expired->id, $ids);
	}
}
