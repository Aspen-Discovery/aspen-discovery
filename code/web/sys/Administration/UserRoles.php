<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserRoles extends DataObject
{

	public $__table = 'user_roles';// table name
    public $id;
	public $userId; // int(11)
	public $roleId; // int(11)


}