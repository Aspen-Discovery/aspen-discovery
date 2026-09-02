<?php /** @noinspection PhpMissingFieldTypeInspection */


class NewYorkTimesSetting extends DataObject {
	public $__table = 'nyt_api_settings';    // table name
	public $id;
	public $booksApiKey;

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
			'booksApiKey' => [
				'property' => 'booksApiKey',
				'type' => 'storedPassword',
				'label' => 'Books API Key',
				'description' => 'The Key for the Books API',
				'maxLength' => '48',
				'hideInLists' => true,
				'forcesListReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}