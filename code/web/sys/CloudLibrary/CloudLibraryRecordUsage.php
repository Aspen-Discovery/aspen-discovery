<?php


class CloudLibraryRecordUsage extends DataObject
{
	public $__table = 'cloud_library_record_usage';
	public $id;
	public $instance;
	public $cloudLibraryId;
	public $year;
	public $month;
	public $timesHeld;
	public $timesCheckedOut;
}