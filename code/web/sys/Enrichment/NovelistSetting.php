<?php


class NovelistSetting extends DataObject
{
	public $__table = 'novelist_settings';    // table name
	public $id;
	public $profile;
	public $pwd;

	public static function getObjectStructure() : array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'profile' => array('property' => 'profile', 'type' => 'text', 'label' => 'Profile ID', 'description' => 'The Profile Name for Novelist'),
			'pwd' => array('property' => 'pwd', 'type' => 'storedPassword', 'label' => 'Profile Password', 'description' => 'The password for the Profile', 'hideInLists' => true),
		];
	}
}