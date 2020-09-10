<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class ReadingHistoryEntry extends DataObject
{
	public $__table = 'user_reading_history_work';   // table name
	public $id;
	public $userId;
	public $groupedWorkPermanentId;
	public $source;
	public $sourceId;
	public $title;
	public $author;
	public $format;
	public $checkOutDate;
	public $checkInDate;
	public $deleted;

}
