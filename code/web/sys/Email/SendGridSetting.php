<?php /** @noinspection PhpMissingFieldTypeInspection */


class SendGridSetting extends DataObject {
	public $__table = 'sendgrid_settings';
	public $id;
	public $fromAddress;
	public $replyToAddress;
	public $apiKey;
	public $baseUrl;

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
				'description' => 'The unique id',
			],
			'fromAddress' => [
				'property' => 'fromAddress',
				'type' => 'email',
				'label' => 'From Address',
				'description' => 'The address emails are sent from',
				'default' => 'no-reply@turningleaftechnologies.com',
			],
			'replyToAddress' => [
				'property' => 'replyToAddress',
				'type' => 'email',
				'label' => 'ReplyTo Address',
				'description' => 'The address that will be shown for responses',
				'default' => '',
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'label' => 'SendGrid URL',
				'type' => 'text',
				'description' => 'The URL used for sending - is region-specific',
				'default' => '',
			],
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'storedPassword',
				'label' => 'SendGrid API Key',
				'description' => 'The API Key used for sending',
				'default' => '',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}