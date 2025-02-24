<?php
class EventsIndexingSetting extends DataObject {
	public $__table = 'events_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	public $numberOfDaysToIndex;
//	public $lastUpdateOfAllEvents;
//	public $lastUpdateOfChangedEvents;
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
			'numberOfDaysToIndex' => [
				'property' => 'numberOfDaysToIndex',
				'type' => 'integer',
				'label' => 'Number of Days to Index',
				'description' => 'How many days in the future to index events',
				'default' => 365,
			],
			'lastUpdateOfAllEvents' => [
				'property' => 'lastUpdateOfAllEvents',
				'type' => 'timestamp',
				'label' => 'Last Update Of All Events',
				'readOnly' => 1,
			],
			'lastUpdateOfChangedEvents' => [
				'property' => 'lastUpdateOfChangedEvents',
				'type' => 'timestamp',
				'label' => 'Last Update Of Changed Events',
				'readOnly' => 1,
			],
		];
	}
}