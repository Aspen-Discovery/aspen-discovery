<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SSOMapping extends DataObject
{
	public $__table = 'sso_mapping';
	public $id;
	public $aspenField;
	public $responseField;
	public $ssoSettingId;

	static function getObjectStructure(): array
	{

		$aspen_fields = array(
			'user_id' => 'Username/Cardnumber',
			'email' => 'Email',
			'first_name' => 'First Name',
			'last_name' => 'Last Name'
		);

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'aspenField' => array('property' => 'aspenField', 'type' => 'enum', 'values' => $aspen_fields, 'label' => 'Field in Aspen', 'description' => 'The field to match', 'required' => true),
			'responseField' => array('property' => 'responseField', 'type' => 'text', 'label' => 'Field From Provider', 'description' => 'The field to match with Aspen that is returned by the SSO Provider', 'required' => true),
		);
	}
}