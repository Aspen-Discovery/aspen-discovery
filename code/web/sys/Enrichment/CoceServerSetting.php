<?php /** @noinspection PhpMissingFieldTypeInspection */


class CoceServerSetting extends DataObject {
	public $__table = 'coce_settings';    // table name
	public $id;
	public $coceServerUrl;

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
			'coceServerUrl' => [
				'property' => 'coceServerUrl',
				'type' => 'url',
				'label' => 'Coce Server URL',
				'description' => 'The URL of a Coce server',
				'maxLength' => '100',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}