<?php

class UserMessage extends DataObject
{
	public $__table = 'user_messages';
	public $id;
	public $userId;
	public $messageType;
	public $messageLevel;
	public $message;
	public $isDismissed;
	public $action1Title;
	public $action1;
	public $action2Title;
	public $action2;
}