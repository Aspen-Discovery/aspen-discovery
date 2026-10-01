<?php /** @noinspection PhpMissingFieldTypeInspection */

class DPLASetting extends DataObject {
	public $__table = 'dpla_api_settings';    // table name
	public $id;
	public $apiKey;

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
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'storedPassword',
				'label' => 'API Key',
				'description' => 'The Key for the API',
				'maxLength' => '32',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}