<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/LibraryLocation/CombinedResultSection.php';

class LocationCombinedResultSection extends CombinedResultSection {
	public $__table = 'location_combined_results_section';    // table name
	public $locationId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Locations'));

		$structure = parent::getObjectStructure($context);
		$structure['locationId'] = [
			'property' => 'locationId',
			'type' => 'enum',
			'values' => $locationList,
			'label' => 'Location',
			'description' => 'The id of a location',
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}