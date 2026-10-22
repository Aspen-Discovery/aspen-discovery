<?php /** @noinspection PhpMissingFieldTypeInspection */

class LiDALoadingMessage extends DataObject {
	public $__table = 'lida_loading_messages';
	public $id;
	public $brandedAppSettingId;
	public $message;

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
			'message' => [
				'property' => 'message',
				'type' => 'text',
				'label' => 'Message',
				'description' => 'The message to be displayed',
				'required' => true,
				'maxLength' => 255
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}