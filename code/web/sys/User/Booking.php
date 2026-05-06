<?php

class Booking extends DataObject {
	public $__table = 'user_booking';
	public int $id;
	public int $userId;
	public string $recordId;
	public string $itemId;
	public int $ils_booking_id;
	public string $ils_start_date;
	public string $ils_end_date;
	public int $createdAt;
	public ?string $ils_pickup_library_id;
	public ?string $ils_status;
	public ?string $ils_notes;

	public function getNumericColumnNames(): array {
		return ['userId', 'ils_booking_id'];
	}
}
