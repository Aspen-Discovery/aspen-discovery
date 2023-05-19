<?php

class EventsBranchMapping extends DataObject {
	public $__table = 'event_library_map_values';    // table name
	public $id;
	public /** @noinspection PhpUnused */
		$aspenLocation;
	public /** @noinspection PhpUnused */
		$eventsLocation;
	public $locationId;
	public $libraryId;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'aspenLocation' => [
				'property' => 'aspenLocation',
				'type' => 'text',
				'label' => 'Aspen Location Name',
				'description' => 'The branch name as it appears in Aspen.',
				'maxLength' => '255',
			],
			'eventsLocation' => [
				'property' => 'eventsLocation',
				'type' => 'text',
				'label' => 'Events Integration Location Name',
				'description' => 'The branch name as it appears in your events pages',
				'maxLength' => '255',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'label',
				'label' => 'Location Id',
				'description' => 'The location id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'label',
				'label' => 'Library Id',
				'description' => 'The library id',
			],
		];
	}
}