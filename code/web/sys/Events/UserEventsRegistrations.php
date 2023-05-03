<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserEventsRegistrations extends DataObject {
	public $__table = 'user_events_registrations';
	public $id;
	public $userId;
	public $barcode;
	public $sourceId;
	public $waitlist;

	public function getUniquenessFields(): array {
		return [
			'userId',
			'sourceId',
		];
	}

}
