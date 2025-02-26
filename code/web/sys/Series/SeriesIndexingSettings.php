<?php

require_once ROOT_DIR . '/sys/CourseReserves/CourseReserveLibraryMapValue.php';

class SeriesIndexingSettings extends DataObject {
	public $__table = 'series_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	public $lastUpdateOfChangedSeries;
	public $lastUpdateOfAllSeries;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedSeries' => [
				'property' => 'lastUpdateOfChangedSeries',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Course Reserves',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllCourseReserves' => [
				'property' => 'lastUpdateOfAllSeries',
				'type' => 'timestamp',
				'label' => 'Last Update of All Series',
				'description' => 'The timestamp when all course reserves were loaded',
				'default' => 0,
			],
		];
	}
}