<?php


class CompriseSetting extends DataObject
{
	public $__table = 'comprise_settings';
	public $id;
	public $customerName;
	public $customerId;
	public $username;
	public $password;

	static function getObjectStructure() : array {
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'customerName' => array('property' => 'customerName', 'type' => 'text', 'label' => 'Customer Name', 'description' => 'The Customer Name assigned by Comprise'),
			'customerId' => array('property' => 'customerId', 'type' => 'integer', 'label' => 'Customer Id', 'description' => 'The Customer Id to use with the API'),
			'username' => array('property' => 'username', 'type' => 'text', 'label' => 'User Name', 'description' => 'The User Name assigned by Comprise'),
			'password' => array('property' => 'password', 'type' => 'storedPassword', 'label' => 'Password', 'description' => 'The Password assigned by Comprise'),
		);
	}

	function getNumericColumnNames() : array
	{
		return ['customerId'];
	}

	function getEncryptedFieldNames() : array {
		return ['password'];
	}
}