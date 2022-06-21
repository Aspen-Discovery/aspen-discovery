<?php


class SystemMessageDismissal extends DataObject
{
	public $__table = 'system_message_dismissal';
	public $id;
	public $systemMessageId;
	public $userId;
}