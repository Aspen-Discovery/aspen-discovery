<?php

require_once ROOT_DIR . '/sys/User/Booking.php';

class BookingService {

	public static function storeBooking(User $patron, string $itemId, string $recordId, string $startDate, string $endDate, ?string $pickupBranch, array $apiResponse): void {
		$booking = new Booking();
		$booking->userId                = $patron->id;
		$booking->recordId              = $recordId;
		$booking->itemId                = $itemId;
		$booking->ils_booking_id        = $apiResponse['booking_id'];
		$booking->ils_start_date        = $startDate;
		$booking->ils_end_date          = $endDate;
		$booking->ils_pickup_library_id = $pickupBranch;
		$booking->ils_status            = $apiResponse['status'] ?? null;
		$booking->createdAt             = time();
		$booking->insert();
	}

	public static function filterBookableForPlacement(array $copies): array {
		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		$bookable = array_filter($copies, function ($item): bool {
			if (empty($item['bookable']) || empty($item['isLibraryItem']) || empty($item['locationCode'])) {
				return false;
			}
			$owningLibrary = Location::getLibraryForCode($item['locationCode']);
			return !empty($owningLibrary) && !empty($owningLibrary->enableBookingPlacement);
		});
		return array_values($bookable);
	}

	/**
	 * Diff live ILS bookings against Aspen's stored copies, update any that changed,
	 * delete rows for bookings that no longer exist in Koha, and return a mapped array
	 * ready for display. The $staffModified flag surfaces bookings that were altered
	 * by staff after the patron placed them.
	 */
	public static function syncAndMapBookings(User $patron, array $liveBookings): array {
		$storedById = self::loadStoredBookingsById($patron);
		$bookings = [];

		foreach ($liveBookings as $raw) {
			$bookingId = $raw['booking_id'];
			$entry = $storedById[$bookingId] ?? null;
			$changed = self::syncBookingRow($entry, $raw);

			$bookings[] = [
				'id'              => $bookingId,
				'recordId'        => $raw['biblio_id'],
				'itemId'          => $raw['item_id'],
				'startDate'       => $raw['start_date'],
				'endDate'         => $raw['end_date'],
				'status'          => $raw['status'] ?? null,
				'pickupLibraryId' => $raw['pickup_library_id'] ?? null,
				'staffModified'   => $changed,
			];
			unset($storedById[$bookingId]);
		}

		foreach ($storedById as $orphan) {
			$orphan->delete();
		}

		return $bookings;
	}

	/**
	 * Merge each live ILS booking with its locally stored metadata (creation
	 * time, patron-placed original values) and the record's display fields, and flag
	 * whether the booking has already elapsed. Consumed by both the web account view
	 * and the API.
	 */
	public static function enrichBookings(User $patron, array $liveBookings): array {
		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$storedById = self::loadStoredBookingsById($patron);
		$today = date('Y-m-d');
		$enriched = [];

		foreach ($liveBookings as $booking) {
			$stored = $storedById[$booking['id']] ?? null;
			$booking['userId']                  = $patron->id;
			$booking['createdAt']               = $stored->createdAt ?? null;
			$booking['originalStartDate']       = $stored->ils_start_date ?? null;
			$booking['originalEndDate']         = $stored->ils_end_date ?? null;
			$booking['originalPickupLibraryId'] = $stored->ils_pickup_library_id ?? null;

			$driver = new MarcRecordDriver($patron->source . ':' . $booking['recordId']);
			if ($driver->isValid()) {
				$booking['title']    = $driver->getTitle();
				$booking['author']   = $driver->getPrimaryAuthor();
				$booking['coverUrl'] = $driver->getBookcoverUrl('medium', true);
				$booking['linkUrl']  = $driver->getLinkUrl();
			}

			$booking['isPast'] = in_array($booking['status'], ['fulfilled', 'cancelled'], true)
				|| (!empty($booking['endDate']) && $booking['endDate'] < $today);

			$enriched[] = $booking;
		}

		return $enriched;
	}

	public static function deleteStoredBooking(User $patron, int $bookingId): void {
		$booking = new Booking();
		$booking->userId = $patron->id;
		$booking->ils_booking_id = $bookingId;
		if ($booking->find(true)) {
			$booking->delete();
		}
	}

	public static function updateStoredBooking(User $patron, int $bookingId, string $startDate, string $endDate, ?string $pickupBranch): void {
		$booking = new Booking();
		$booking->userId = $patron->id;
		$booking->ils_booking_id = $bookingId;
		if ($booking->find(true)) {
			$booking->ils_start_date        = $startDate;
			$booking->ils_end_date          = $endDate;
			$booking->ils_pickup_library_id = $pickupBranch;
			$booking->update();
		}
	}

	private static function loadStoredBookingsById(User $patron): array {
		$stored = new Booking();
		$stored->userId = $patron->id;
		$stored->find();
		$storedById = [];
		while ($stored->fetch()) {
			$storedById[$stored->ils_booking_id] = clone $stored;
		}
		return $storedById;
	}

	private static function syncBookingRow(?Booking $stored, array $raw): bool {
		if ($stored === null) {
			return false;
		}

		$changed =
			$stored->ils_status !== ($raw['status'] ?? null) ||
			$stored->ils_start_date !== $raw['start_date'] ||
			$stored->ils_end_date !== $raw['end_date'] ||
			$stored->ils_pickup_library_id !== ($raw['pickup_library_id'] ?? null);

		if ($changed) {
			$stored->ils_status            = $raw['status'] ?? null;
			$stored->ils_start_date        = $raw['start_date'];
			$stored->ils_end_date          = $raw['end_date'];
			$stored->ils_pickup_library_id = $raw['pickup_library_id'] ?? null;
			$stored->update();
		}

		return $changed;
	}
}
