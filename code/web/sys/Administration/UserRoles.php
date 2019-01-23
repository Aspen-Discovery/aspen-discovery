<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 11/17/2016
 *
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class UserRoles extends DB_DataObject
{

	public $__table = 'user_roles';// table name
	public $userId; // int(11)
	public $roleId; // int(11)

	function keys() {
		return array('userId', 'roleId');
	}

}