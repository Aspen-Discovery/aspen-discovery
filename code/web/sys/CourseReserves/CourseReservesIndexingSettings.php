<?php


class CourseReservesIndexingSettings extends DataObject
{
	public $__table = 'course_reserves_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	public $lastUpdateOfChangedCourseReserves;
	public $lastUpdateOfAllCourseReserves;

	public static function getObjectStructure() : array
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedCourseReserves' => array('property' => 'lastUpdateOfChangedCourseReserves', 'type' => 'timestamp', 'label' => 'Last Update of Changed Course Reserves', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllCourseReserves' => array('property' => 'lastUpdateOfAllCourseReserves', 'type' => 'timestamp', 'label' => 'Last Update of All Course Reserves', 'description' => 'The timestamp when all course reserves were loaded', 'default' => 0),
		);
	}
}