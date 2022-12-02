<?php


class NewYorkTimesSetting extends DataObject {
	public $__table = 'nyt_api_settings';    // table name
	public $id;
	public $booksApiKey;

	public static function getObjectStructure(): array {
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
				'maxLength' => '32',
				'hideInLists' => true,
				'forcesListReindex' => true,
			],
		];
		return $structure;
	}
}