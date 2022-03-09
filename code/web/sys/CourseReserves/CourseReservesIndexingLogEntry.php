<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class CourseReservesIndexingLogEntry extends BaseLogEntry
{
	public $__table = 'course_reserves_indexing_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numLists;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;
}