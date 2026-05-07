<?php

use PHPUnit\Framework\TestCase;

class BookingServiceTests extends TestCase {
	private static int $userId = 9901;
	private static int $nextIlsId = 1;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../code/web/services/BookingService.php';
	}

	public static function setUpBeforeClass(): void {
		global $aspen_db;
		$aspen_db->exec("CREATE TABLE IF NOT EXISTS user_booking (
			id int(11) NOT NULL AUTO_INCREMENT,
			userId int(11) NOT NULL,
			recordId varchar(50) NOT NULL,
			itemId varchar(50) NOT NULL,
			ils_booking_id int(11) NOT NULL,
			ils_start_date date NOT NULL,
			ils_end_date date NOT NULL,
			ils_pickup_library_id varchar(50) DEFAULT NULL,
			ils_status varchar(50) DEFAULT NULL,
			ils_notes text DEFAULT NULL,
			createdAt int(11) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY userId (userId, ils_booking_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	protected function setUp(): void {
		parent::setUp();
		global $aspen_db;
		$aspen_db->exec("DELETE FROM user_booking WHERE userId = " . self::$userId);
	}

	private function makePatron(): User {
		$patron = new User();
		$patron->id = self::$userId;
		return $patron;
	}

	private function nextIlsId(): int {
		return self::$nextIlsId++;
	}

	private function defaultApiResponse(int $ilsId): array {
		return ['booking_id' => $ilsId, 'status' => 'confirmed'];
	}

	// -------------------------------------------------------------------------

	public function testStoreBookingInsertsCorrectFields(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron,
			'ITEM-1',
			'BIB-1',
			'2026-06-01',
			'2026-06-07',
			'CPL',
			'handle with care',
			$this->defaultApiResponse($ilsId)
		);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$this->assertTrue((bool)$b->find(true));

		$this->assertEquals('BIB-1', $b->recordId);
		$this->assertEquals('ITEM-1', $b->itemId);
		$this->assertEquals('2026-06-01', $b->ils_start_date);
		$this->assertEquals('2026-06-07', $b->ils_end_date);
		$this->assertEquals('CPL', $b->ils_pickup_library_id);
		$this->assertEquals('confirmed', $b->ils_status);
		$this->assertEquals('handle with care', $b->ils_notes);
	}

	public function testStoreBookingWithNullPickupAndNotes(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-2', 'BIB-2', '2026-07-01', '2026-07-03',
			null, null,
			$this->defaultApiResponse($ilsId)
		);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$this->assertTrue((bool)$b->find(true));
		$this->assertNull($b->ils_pickup_library_id);
		$this->assertNull($b->ils_notes);
	}

	// -------------------------------------------------------------------------

	public function testSyncAndMapBookings_unchanged_staffModifiedFalse(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-3', 'BIB-3', '2026-06-01', '2026-06-07',
			'CPL', null,
			['booking_id' => $ilsId, 'status' => 'confirmed']
		);

		$live = [[
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-3',
			'item_id'           => 'ITEM-3',
			'start_date'        => '2026-06-01',
			'end_date'          => '2026-06-07',
			'status'            => 'confirmed',
			'pickup_library_id' => 'CPL',
		]];

		$result = BookingService::syncAndMapBookings($patron, $live);

		$this->assertCount(1, $result);
		$this->assertFalse($result[0]['staffModified']);
	}

	public function testSyncAndMapBookings_staffChangedDates_staffModifiedTrue(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-4', 'BIB-4', '2026-06-01', '2026-06-07',
			'CPL', null,
			['booking_id' => $ilsId, 'status' => 'confirmed']
		);

		$live = [[
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-4',
			'item_id'           => 'ITEM-4',
			'start_date'        => '2026-06-03',
			'end_date'          => '2026-06-10',
			'status'            => 'confirmed',
			'pickup_library_id' => 'CPL',
		]];

		$result = BookingService::syncAndMapBookings($patron, $live);

		$this->assertCount(1, $result);
		$this->assertTrue($result[0]['staffModified']);
	}

	public function testSyncAndMapBookings_staffChangedDates_updatesStoredRow(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-5', 'BIB-5', '2026-06-01', '2026-06-07',
			'CPL', null,
			['booking_id' => $ilsId, 'status' => 'confirmed']
		);

		$live = [[
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-5',
			'item_id'           => 'ITEM-5',
			'start_date'        => '2026-06-15',
			'end_date'          => '2026-06-20',
			'status'            => 'waiting',
			'pickup_library_id' => 'MPL',
		]];

		BookingService::syncAndMapBookings($patron, $live);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$b->find(true);

		$this->assertEquals('2026-06-15', $b->ils_start_date);
		$this->assertEquals('2026-06-20', $b->ils_end_date);
		$this->assertEquals('waiting', $b->ils_status);
		$this->assertEquals('MPL', $b->ils_pickup_library_id);
	}

	public function testSyncAndMapBookings_newBookingNotInStore_staffModifiedFalse(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		$live = [[
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-6',
			'item_id'           => 'ITEM-6',
			'start_date'        => '2026-07-01',
			'end_date'          => '2026-07-05',
			'status'            => 'confirmed',
			'pickup_library_id' => null,
		]];

		$result = BookingService::syncAndMapBookings($patron, $live);

		$this->assertCount(1, $result);
		$this->assertFalse($result[0]['staffModified']);
	}

	public function testSyncAndMapBookings_orphanDeleted(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-7', 'BIB-7', '2026-06-01', '2026-06-07',
			null, null,
			['booking_id' => $ilsId, 'status' => 'confirmed']
		);

		BookingService::syncAndMapBookings($patron, []);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$this->assertFalse((bool)$b->find(true), 'Orphaned stored booking should be deleted');
	}

	public function testSyncAndMapBookings_mapsFieldsCorrectly(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		$live = [[
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-8',
			'item_id'           => 'ITEM-8',
			'start_date'        => '2026-08-01',
			'end_date'          => '2026-08-10',
			'status'            => 'waiting',
			'pickup_library_id' => 'CPL',
		]];

		$result = BookingService::syncAndMapBookings($patron, $live);

		$this->assertEquals($ilsId, $result[0]['id']);
		$this->assertEquals('BIB-8', $result[0]['recordId']);
		$this->assertEquals('ITEM-8', $result[0]['itemId']);
		$this->assertEquals('2026-08-01', $result[0]['startDate']);
		$this->assertEquals('2026-08-10', $result[0]['endDate']);
		$this->assertEquals('waiting', $result[0]['status']);
		$this->assertEquals('CPL', $result[0]['pickupLibraryId']);
	}

	// -------------------------------------------------------------------------

	public function testDeleteStoredBooking_removesRow(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-9', 'BIB-9', '2026-06-01', '2026-06-07',
			null, null,
			$this->defaultApiResponse($ilsId)
		);

		BookingService::deleteStoredBooking($patron, $ilsId);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$this->assertFalse((bool)$b->find(true));
	}

	public function testDeleteStoredBooking_nonExistent_noError(): void {
		$patron = $this->makePatron();
		BookingService::deleteStoredBooking($patron, 99999);
		$this->assertTrue(true);
	}

	// -------------------------------------------------------------------------

	public function testUpdateStoredBooking_updatesDateAndPickup(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-10', 'BIB-10', '2026-06-01', '2026-06-07',
			'CPL', null,
			$this->defaultApiResponse($ilsId)
		);

		BookingService::updateStoredBooking($patron, $ilsId, '2026-07-01', '2026-07-14', 'MPL');

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$b->find(true);

		$this->assertEquals('2026-07-01', $b->ils_start_date);
		$this->assertEquals('2026-07-14', $b->ils_end_date);
		$this->assertEquals('MPL', $b->ils_pickup_library_id);
	}

	public function testUpdateStoredBooking_nullPickup_clearsField(): void {
		$patron = $this->makePatron();
		$ilsId = $this->nextIlsId();

		BookingService::storeBooking(
			$patron, 'ITEM-11', 'BIB-11', '2026-06-01', '2026-06-07',
			'CPL', null,
			$this->defaultApiResponse($ilsId)
		);

		BookingService::updateStoredBooking($patron, $ilsId, '2026-06-01', '2026-06-07', null);

		$b = new Booking();
		$b->userId = self::$userId;
		$b->ils_booking_id = $ilsId;
		$b->find(true);

		$this->assertNull($b->ils_pickup_library_id);
	}

	public function testUpdateStoredBooking_nonExistent_noError(): void {
		$patron = $this->makePatron();
		BookingService::updateStoredBooking($patron, 99999, '2026-06-01', '2026-06-07', null);
		$this->assertTrue(true);
	}
}
