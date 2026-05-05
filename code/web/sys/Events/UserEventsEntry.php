<?php /** @noinspection PhpMissingFieldTypeInspection */


class UserEventsEntry extends DataObject {
	public $__table = 'user_events_entry';
	public $id;
	public $userId;
	public $sourceId;
	public $title;
	public $eventDate;
	public $dateAdded;
	public $regRequired;
	public $location;
	public $displayEventBranchOnThumbnail;

	public function getUniquenessFields(): array {
		return [
			'userId',
			'sourceId',
		];
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
		$eventInstanceId = AspenEventRecordDriver::extractEventInstanceId($this->sourceId);

		if ($eventInstanceId !== null) {
			require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
			$registration = new UserAspenEventInstanceRegistration();
			$registration->userId = $this->userId;
			$registration->eventInstanceId = $eventInstanceId;
			$registration->delete(true);
		}
		
		return parent::delete();
	}
}
