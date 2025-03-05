<?php


class NewYorkTimesSetting extends DataObject {
	public $__table = 'nyt_api_settings';    // table name
	public $id;
	public $booksApiKey;
	public int $runFullUpdate;
	public int $enableExtensiveLogging;

	public static function getObjectStructure($context = ''): array {
		return [
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
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'When checked, forces a full update of all NYT lists regardless of modification date. This setting will be automatically unchecked after the update completes.',
				'default' => 0,
			],
			'enableExtensiveLogging' => [
				'property' => 'enableExtensiveLogging',
				'type' => 'checkbox',
				'label' => 'Enable Extensive Logging',
				'description' => 'When checked, the NYT Update Log will be populated with more informative log entries. This setting will be automatically unchecked after the update completes.',
				'default' => 0,
			],
		];
	}
}