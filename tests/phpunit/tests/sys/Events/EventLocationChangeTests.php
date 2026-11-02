<?php

use PHPUnit\Framework\TestCase;

if (!defined('EVENT_LOCATION_PATH_TO_ROOT')) {
	define('EVENT_LOCATION_PATH_TO_ROOT', __DIR__ . '/../../../../../');
}

class EventLocationChangeTests extends TestCase {

	private const ORIGINAL_LOCATION_ID = 1;
	private const NEW_LOCATION_ID = 2;

	private Event $event;
	private Sublocation $originalSublocation;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once EVENT_LOCATION_PATH_TO_ROOT . 'code/web/sys/Events/Event.php';
		require_once EVENT_LOCATION_PATH_TO_ROOT . 'code/web/sys/Events/EventInstance.php';
		require_once EVENT_LOCATION_PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Location.php';
		require_once EVENT_LOCATION_PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Sublocation.php';
		require_once EVENT_LOCATION_PATH_TO_ROOT . 'code/web/sys/Account/User.php';
	}

	protected function setUp(): void {
		global $aspen_db;
		parent::setUp();

		$eventTypeIdOfferedNowhere = (int)$aspen_db->query("SELECT COALESCE(MAX(eventTypeId), 0) + 1 FROM event_type_location")->fetchColumn();

		$this->originalSublocation = $this->insertSublocation(self::ORIGINAL_LOCATION_ID, 'PHPUnit Room A');
		$this->insertSublocation(self::NEW_LOCATION_ID, 'PHPUnit Room B');

		$this->event = new Event();
		$this->event->title = 'PHPUnit Location Change Event';
		$this->event->eventTypeId = $eventTypeIdOfferedNowhere;
		$this->event->locationId = self::ORIGINAL_LOCATION_ID;
		$this->event->sublocationId = $this->originalSublocation->id;
		$this->event->startDate = date('Y-m-d');
		$this->event->insert();
	}

	protected function tearDown(): void {
		global $aspen_db;

		$eventId = (int)$this->event->id;
		$aspen_db->exec("DELETE FROM event_instance WHERE eventId = $eventId");
		$aspen_db->exec("DELETE FROM event WHERE id = $eventId");
		$aspen_db->exec("DELETE FROM sublocation WHERE name LIKE 'PHPUnit %'");
		UserAccount::logout();
		$aspen_db->exec("DELETE FROM user WHERE source = 'phpunit'");

		parent::tearDown();
	}

	private function insertSublocation(int $locationId, string $name): Sublocation {
		$sublocation = new Sublocation();
		$sublocation->locationId = $locationId;
		$sublocation->name = $name;
		$sublocation->isValidEventLocation = 1;
		$sublocation->insert();
		return $sublocation;
	}

	private function insertInstance(?int $sublocationId): EventInstance {
		$instance = new EventInstance();
		$instance->eventId = $this->event->id;
		$instance->date = date('Y-m-d', strtotime('+7 days'));
		$instance->time = '10:00';
		$instance->length = 60;
		$instance->status = 1;
		$instance->sublocationId = $sublocationId;
		$instance->insert();
		return $instance;
	}

	private function loginTestUserAtLocation(int $locationId): void {
		$user = new User();
		$user->source = 'phpunit';
		$user->username = 'phpunit_event_location';
		$user->firstname = 'Test';
		$user->lastname = 'EventLocation';
		$user->displayName = 'Test EventLocation';
		$user->created = date('Y-m-d H:i:s');
		$user->homeLocationId = $locationId;
		$user->myLocation1Id = 0;
		$user->myLocation2Id = 0;
		$user->unique_ils_id = 'phpunit_event_location';
		$user->ils_barcode = 'PHPUNIT_EL_1';
		$user->email = 'eventlocation@example.test';
		$user->insert();

		UserAccount::loginWithAspen($user);
	}

	private function getStoredEventSublocationId(): ?int {
		global $aspen_db;

		$result = $aspen_db->query("SELECT sublocationId FROM event WHERE id = {$this->event->id}");
		$storedValue = $result->fetchColumn();
		return $storedValue === null || $storedValue === false ? null : (int)$storedValue;
	}

	private function getStoredInstanceSublocationId(int $instanceId): ?int {
		global $aspen_db;

		$result = $aspen_db->query("SELECT sublocationId FROM event_instance WHERE id = $instanceId");
		$storedValue = $result->fetchColumn();
		return $storedValue === null || $storedValue === false ? null : (int)$storedValue;
	}

	public function testChangingLocationClearsTheEventSublocation(): void {
		$this->event->setProperty('locationId', self::NEW_LOCATION_ID, null);
		$this->event->update();

		$this->assertNull($this->getStoredEventSublocationId());
	}

	public function testChangingLocationClearsInstanceSublocationOverrides(): void {
		$instance = $this->insertInstance($this->originalSublocation->id);

		$this->event->setProperty('locationId', self::NEW_LOCATION_ID, null);
		$this->event->update();

		$this->assertNull($this->getStoredInstanceSublocationId((int)$instance->id));
	}

	public function testKeepingLocationKeepsTheEventSublocation(): void {
		$this->event->setProperty('title', 'PHPUnit Location Change Event Renamed', null);
		$this->event->update();

		$this->assertEquals($this->originalSublocation->id, $this->getStoredEventSublocationId());
	}

	public function testValidateLocationRejectsALocationTheUserCannotAdminister(): void {
		$this->loginTestUserAtLocation(self::ORIGINAL_LOCATION_ID);
		$this->event->setProperty('locationId', self::NEW_LOCATION_ID, null);

		$validationResults = $this->event->validateLocation();

		$this->assertFalse($validationResults['validatedOk']);
		$this->assertEquals('You do not have permission to administer events at the selected location.', $validationResults['errors'][0]);
	}

	public function testValidateLocationRejectsAnEventTypeTheLocationDoesNotOffer(): void {
		$this->loginTestUserAtLocation(self::ORIGINAL_LOCATION_ID);
		$this->event->setProperty('locationId', self::NEW_LOCATION_ID, null);
		$this->event->update();
		$this->event->setProperty('locationId', self::ORIGINAL_LOCATION_ID, null);

		$validationResults = $this->event->validateLocation();

		$this->assertFalse($validationResults['validatedOk']);
		$this->assertEquals('The selected location does not offer this event type.', $validationResults['errors'][0]);
	}

	public function testValidateLocationAcceptsAnEditThatKeepsALocationNoLongerOfferingTheEventType(): void {
		$this->loginTestUserAtLocation(self::ORIGINAL_LOCATION_ID);
		$this->event->setProperty('title', 'PHPUnit Location Change Event Renamed', null);

		$validationResults = $this->event->validateLocation();

		$this->assertTrue($validationResults['validatedOk']);
		$this->assertEmpty($validationResults['errors']);
	}
}
