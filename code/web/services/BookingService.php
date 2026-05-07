<?php

require_once ROOT_DIR . '/sys/User/Booking.php';

class BookingService {

	public static function storeBooking(User $patron, string $itemId, string $recordId, string $startDate, string $endDate, ?string $pickupBranch, ?string $notes, array $apiResponse): void {
		$booking = new Booking();
		$booking->userId                = $patron->id;
		$booking->recordId              = $recordId;
		$booking->itemId                = $itemId;
		$booking->ils_booking_id        = $apiResponse['booking_id'];
		$booking->ils_start_date        = $startDate;
		$booking->ils_end_date          = $endDate;
		$booking->ils_pickup_library_id = $pickupBranch;
		$booking->ils_status            = $apiResponse['status'] ?? null;
		$booking->ils_notes             = $notes;
		$booking->createdAt             = time();
		$booking->insert();
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

	public static function deleteStoredBooking(User $patron, int $bookingId): void {
		$booking = new Booking();
		$booking->userId = $patron->id;
		$booking->ils_booking_id = $bookingId;
		if ($booking->find(true)) {
			$booking->delete();
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
