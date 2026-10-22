<?php /** @noinspection PhpMissingFieldTypeInspection */

class GroupedWorkEContentSort extends DataObject {
	public $__table = 'grouped_work_econtent_sort';
	public $id;
	public $eContentSortingGroupId;
	public $eContentSource;
	public $weight;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkEContentSortingGroup.php';
		$validEContentSources = GroupedWorkEContentSortingGroup::getValidEContentSources();
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
			'eContentSortingGroupId' => [
				'property' => 'eContentSortingGroupId',
				'type' => 'integer',
				'label' => 'eContent Sorting Group ID',
				'description' => 'The sorting group this belongs to',
			],
			'eContentSource' => [
				'property' => 'eContentSource',
				'type' => 'enum',
				'values' => $validEContentSources,
				'label' => 'Source',
				'description' => 'The source to be sorted',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}