<?php


class UserEventsUsage extends DataObject
{
	public $__table = 'user_events_usage';
	public $id;
	public $userId;
	public $type;
	public $source;
	public $year;
	public $month;
	public $usageCount;
}