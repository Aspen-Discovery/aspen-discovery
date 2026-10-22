<?php /** @noinspection PhpMissingFieldTypeInspection */

class LocationMoreDetails extends DataObject {
	public $__table = 'location_more_details';
	public $id;
	public $locationId;
	public $source;
	public $collapseByDefault;
	public $weight;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		//Load Libraries for lookup values
		require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
		$validSources = RecordInterface::getValidMoreDetailsSources();
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'label' => 'Source',
				'values' => $validSources,
				'description' => 'The source of the data to display',
			],
			'collapseByDefault' => [
				'property' => 'collapseByDefault',
				'type' => 'checkbox',
				'label' => 'Collapse By Default',
				'description' => 'Whether or not the section should be collapsed by default',
				'default' => true,
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

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '';
	}
}