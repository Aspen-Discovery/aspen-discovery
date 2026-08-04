<?php /** @noinspection PhpMissingFieldTypeInspection */


class UserAgent extends DataObject {
	public $__table = 'user_agent';
	public $id;
	public $userAgent;
	/** @noinspection PhpUnused */
	public $isBot;
	public $blockAccess;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'isBot',
			'blockAccess',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'userAgent' => [
				'property' => 'userAgent',
				'type' => 'text',
				'label' => 'User Agent',
				'description' => 'The User Agent from requests',
			],
			'isBot' => [
				'property' => 'isBot',
				'type' => 'checkbox',
				'label' => 'Is Bot?',
				'description' => 'Is the User Agent representing a bot.',
				'default' => true,
			],
			'blockAccess' => [
				'property' => 'blockAccess',
				'type' => 'checkbox',
				'label' => 'Block Access from this User Agent',
				'description' => 'Traffic from this User Agent will not be allowed to use Aspen.',
				'default' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function objectHistoryEnabled(): bool {
		return false;
	}
}