<?php /** @noinspection PhpMissingFieldTypeInspection */

class SortOptions extends DataObject
{
	public $__table = 'sort_options';
	public $id;
	public $searchSettingId;
	public $type;
	public $label;
	public $defaultLabel;
	public $enabled;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array
	{
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database.',
				'uniqueProperty' => true,
			],
			'searchSettingId' => [
				'property' => 'searchSettingId',
				'type' => 'label',
				'label' => 'Search Setting Id',
				'description' => 'The unique id within the database for the search setting.',
			],
			'type' => [
				'property' => 'type',
				'type' => 'text',
				'label' => 'Sort Option',
				'description' => 'The sort option type.',
				'readOnly' => true,
			],
			'defaultLabel' => [
				'property' => 'defaultLabel',
				'type' => 'text',
				'label' => 'Default Label',
				'readOnly' => true,
			],
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Label',
				'description' => 'The label for the sort option.',
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'enum',
				'values' => [
					0 => 'Disabled',
					1 => 'Enabled'
				],
				'label' => 'Enabled?',
				'description' => 'Setting for if sort option is enabled.',
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}