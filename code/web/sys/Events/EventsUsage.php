<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class EventsUsage extends DataObject
{
	public $__table = 'events_usage';
	public $id;
	public $type;
	public $source;
	public $identifier;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;
}