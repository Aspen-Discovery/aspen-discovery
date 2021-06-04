<?php


class EDSSettings extends DataObject
{
	public $__table = 'ebsco_eds_settings';
	public $id;
	public $name;
	public $edsApiProfile;
	public $edsApiUsername;
	public $edsApiPassword;
	public $edsSearchProfile;

	public static function getObjectStructure() : array {
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'A name for these settings', 'required' => true),
			'edsApiProfile' => array('property' => 'edsApiProfile', 'type' => 'text', 'label' => 'EDS API Profile', 'description' => 'The profile to use when connecting to the EBSCO API', 'hideInLists' => true),
			'edsSearchProfile' => array('property' => 'edsSearchProfile', 'type' => 'text', 'label' => 'EDS Search Profile', 'description' => 'The profile to use when linking to EBSCO EDS', 'hideInLists' => true),
			'edsApiUsername' => array('property' => 'edsApiUsername', 'type' => 'text', 'label' => 'EDS API Username', 'description' => 'The username to use when connecting to the EBSCO API', 'hideInLists' => true),
			'edsApiPassword' => array('property' => 'edsApiPassword', 'type' => 'text', 'label' => 'EDS API Password', 'description' => 'The password to use when connecting to the EBSCO API', 'hideInLists' => true),
		];
	}
}