<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 12/27/2016
 *
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class UserStaffSettings extends DB_DataObject
{

	public $__table = 'user_staff_settings';

	public $id;
	public $userId;
	public $materialsRequestReplyToAddress;
	public $materialsRequestEmailSignature;

}