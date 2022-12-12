<?php


class NovelistSetting extends DataObject {
	public $__table = 'novelist_settings';    // table name
	public $id;
	public $profile;
	public $pwd;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'profile' => [
				'property' => 'profile',
				'type' => 'text',
				'label' => 'Profile ID',
				'description' => 'The Profile Name for Novelist',
			],
			'pwd' => [
				'property' => 'pwd',
				'type' => 'storedPassword',
				'label' => 'Profile Password',
				'description' => 'The password for the Profile',
				'hideInLists' => true,
			],
		];
	}
}