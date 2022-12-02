<?php


class SendGridSetting extends DataObject {
	public $__table = 'sendgrid_settings';
	public $id;
	public $fromAddress;
	public $replyToAddress;
	public $apiKey;

	public static function getObjectStructure(): array {
		return [
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
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'storedPassword',
				'label' => 'SendGrid API Key',
				'description' => 'The API Key used for sending',
				'default' => '',
				'hideInLists' => true,
			],
		];
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}