<?php


class EDSSettings extends DataObject {
	public $__table = 'ebsco_eds_settings';
	public $id;
	public $name;
	public $edsApiProfile;
	public $edsApiUsername;
	public $edsApiPassword;
	public $edsSearchProfile;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for these settings',
				'required' => true,
			],
			'edsApiProfile' => [
				'property' => 'edsApiProfile',
				'type' => 'text',
				'label' => 'EDS API Profile',
				'description' => 'The profile to use when connecting to the EBSCO API',
				'hideInLists' => true,
			],
			'edsSearchProfile' => [
				'property' => 'edsSearchProfile',
				'type' => 'text',
				'label' => 'EDS Search Profile',
				'description' => 'The profile to use when linking to EBSCO EDS',
				'hideInLists' => true,
			],
			'edsApiUsername' => [
				'property' => 'edsApiUsername',
				'type' => 'text',
				'label' => 'EDS API Username',
				'description' => 'The username to use when connecting to the EBSCO API',
				'hideInLists' => true,
			],
			'edsApiPassword' => [
				'property' => 'edsApiPassword',
				'type' => 'storedPassword',
				'label' => 'EDS API Password',
				'description' => 'The password to use when connecting to the EBSCO API',
				'hideInLists' => true,
			],
		];
	}
}