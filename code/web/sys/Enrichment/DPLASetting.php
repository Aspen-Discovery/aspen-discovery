<?php


class DPLASetting extends DataObject {
	public $__table = 'dpla_api_settings';    // table name
	public $id;
	public $apiKey;

	public static function getObjectStructure(): array {
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
		return $structure;
	}
}