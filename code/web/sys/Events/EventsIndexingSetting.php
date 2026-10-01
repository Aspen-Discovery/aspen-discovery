<?php /** @noinspection PhpMissingFieldTypeInspection */
class EventsIndexingSetting extends DataObject {
	public $__table = 'events_indexing_settings';    // table name
	public $id;
	public $name;
	public $runFullUpdate;
	/** @noinspection PhpUnused */
	public $numberOfDaysToIndex;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}