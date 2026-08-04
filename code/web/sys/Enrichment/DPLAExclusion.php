<?php /** @noinspection PhpMissingFieldTypeInspection */

class DPLAExclusion extends DataObject {
	public $__table = 'dpla_exclusion_settings';    // table name
	public $id;
	public $dplaLink;

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
				'description' => 'The unique id',
			],
			'dplaLink' => [
				'property' => 'dplaLink',
				'type' => 'text',
				'label' => 'DP.LA Link',
				'description' => 'The link to the DP.LA resource.',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}