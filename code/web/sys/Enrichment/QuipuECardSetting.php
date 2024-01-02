<?php


class QuipuECardSetting extends DataObject {
	public $__table = 'quipu_ecard_setting';
	public $id;
	public $server;
	public $clientId;
	public $hasECard;
	public $hasERenew;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'server' => [
				'property' => 'server',
				'type' => 'text',
				'label' => 'Server',
				'description' => 'The Name of the Server where eCard/eRenew is installed',
			],
			'clientId' => [
				'property' => 'clientId',
				'type' => 'integer',
				'label' => 'Client ID',
				'description' => 'The numeric client id for your instance',
				'hideInLists' => true,
			],
			'hasECard' => [
				'property' => 'hasECard',
				'type' => 'checkbox',
				'label' => 'Has eCard',
				'description' => 'Turn on if eCard has been purchased from Quipu by the library',
				'hideInLists' => true,
				'default' => true
			],
			'hasERenew' => [
				'property' => 'hasERenew',
				'type' => 'checkbox',
				'label' => 'Has eRenew',
				'description' => 'Turn on if eCard has been purchased from Quipu by the library',
				'hideInLists' => true,
				'default' => false
			],
		];
	}
}