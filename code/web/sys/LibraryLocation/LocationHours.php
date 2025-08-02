<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class LocationHours extends DataObject {
	public $__table = 'location_hours';   // table name
	public $id;                           // int(11)  not_null primary_key auto_increment
	public $locationId;                   // int(11)
	public $day;                          // int(11)
	public $open;                         // varchar(10)
	public $close;                        // varchar(10)
	public $closed;
	public $notes;

	public static $dayNames = [
		'Sunday',
		'Monday',
		'Tuesday',
		'Wednesday',
		'Thursday',
		'Friday',
		'Saturday',
	];

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'day',
			'closed',
		];
	}

	static function getObjectStructure($context = ''): array {
		$location = new Location();
		$location->selectAdd();
		$location->selectAdd('locationId');
		$location->selectAdd('displayName');
		$location->orderBy('displayName');
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->locationId] = $location->displayName;
		}
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'values' => $locationList,
				'label' => 'Location',
				'description' => 'The library location.',
			],
			'day' => [
				'property' => 'day',
				'type' => 'enum',
				'values' => LocationHours::$dayNames,
				'label' => 'Day of Week',
				'description' => 'The day of the week 0 to 6 (0 = Sunday to 6 = Saturday)',
			],
			'closed' => [
				'property' => 'closed',
				'type' => 'checkbox',
				'label' => 'Closed',
				'description' => 'Check to indicate that the library is closed on this day.',
			],
			'open' => [
				'property' => 'open',
				'type' => 'time',
				'label' => 'Opening Hour',
				'description' => 'The opening hour. Use 24 hour format HH:MM, eg: 08:30',
			],
			'close' => [
				'property' => 'close',
				'type' => 'time',
				'label' => 'Closing Hour',
				'description' => 'The closing hour. Use 24 hour format HH:MM, eg: 16:30',
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'text',
				'label' => 'Notes',
				'description' => 'Notes to show for the the hours',
				'maxLength' => 255,
			],
		];
	}
}