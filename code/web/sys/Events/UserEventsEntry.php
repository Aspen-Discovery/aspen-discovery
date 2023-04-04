<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

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
	//public $pastEvent;

	public function getUniquenessFields(): array {
		return [
			'userId',
			'sourceId',
		];
	}

}
