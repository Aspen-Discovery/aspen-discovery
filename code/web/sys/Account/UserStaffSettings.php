<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserStaffSettings extends DataObject
{

	public $__table = 'user_staff_settings';

	public $id;
	public $userId;
	public $materialsRequestReplyToAddress;
	public $materialsRequestEmailSignature;

}