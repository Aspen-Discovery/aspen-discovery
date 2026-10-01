<?php /** @noinspection PhpMissingFieldTypeInspection */

class ChiliFreshSetting extends DataObject
{
	public $__table = 'chilifresh_settings';    // table name
	public $id;
	public $enabled;
	public $genericArtCode;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array
	{
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
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Integration Enabled',
				'description' => 'Whether or not ChiliFresh cover art integration is enabled',
				'default' => 1,
			],
			'genericArtCode' => [
				'property' => 'genericArtCode',
				'type' => 'text',
				'label' => 'Generic Art Code',
				'description' => 'Optional code supplied by ChiliFresh to specify fallback image to use',
				'maxlength' => 255,
				'required' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}
