<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class RolePermissions extends DataObject
{

	public $__table = 'role_permissions';// table name
    public $id;
	public $roleId; // int(11)
	public $permissionId; // int(11)

	function keys() {
		return array('roleId', 'permissionId');
	}

}