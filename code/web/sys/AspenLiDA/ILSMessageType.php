<?php

class ILSMessageType extends DataObject {
	public $__table = 'ils_message_type';
	public $id;
	public $attributeId;
	public $module;
	public $code;
	public $isDigest;
	public $locationCode;
	public $isEnabled;
	public $ilsNotificationSettingId;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'attributeId',
			'isDigest',
			'isEnabled',
			'ilsNotificationSettingId'
		];
	}

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'module' => [
				'property' => 'module',
				'type' => 'text',
				'label' => 'Category',
				'description' => 'The Category of Message from the ILS',
				'readOnly' => true,
			],
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'Type',
				'description' => 'The Type of Message from the ILS',
				'readOnly' => true,
			],
			'locationCode' => [
				'property' => 'locationCode',
				'type' => 'text',
				'label' => 'Location',
				'description' => '',
				'readOnly' => true,
			],
			'isDigest' => [
				'property' => 'isDigest',
				'type' => 'checkbox',
				'label' => 'Is Digest',
				'description' => 'If the message type is sent as a digest',
			],
			'isEnabled' => [
				'property' => 'isEnabled',
				'type' => 'checkbox',
				'label' => 'Is Enabled in Aspen',
				'description' => 'Whether or not Aspen will send notifications for this message type',
			]

		];
	}

	public function getEditLink($context): string {
		return '/AspenLiDA/ILSMessageTypes?objectAction=edit&id=' . $this->id;
	}
}