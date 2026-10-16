<?php /** @noinspection PhpMissingFieldTypeInspection */

class PrioritizedShelfLocation extends DataObject {
	public $__table = 'prioritized_shelf_locations';
	public $__displayNameColumn = 'shelfLocation';
	public $id;
	public $groupedWorkSettingsId;
	public $shelfLocation;
	public $weight;

	function getNumericColumnNames(): array {
		return [
			'weight',
		];
	}

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
				'description' => 'The unique id of the hours within the database',
			],
			'groupedWorkSettingsId' => [
				'property' => 'groupedWorkSettingsId;',
				'type' => 'hidden',
				'label' => 'Grouped Work Display Settings',
				'description' => 'A link to the settings which the details belongs to',
			],
			'shelfLocation' => [
				'property' => 'shelfLocation',
				'type' => 'regularExpression',
				'label' => 'Shelf Location',
				'description' => 'The shelf location to prioritize',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}