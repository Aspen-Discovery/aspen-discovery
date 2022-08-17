<?php

require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';

class UserNotification extends DataObject
{
	public $__table = 'user_notifications';

	public $id;
	public $userId;
	public $notificationType;
	public $notificationDate;
	public $pushToken;
	public $receiptId;
	public $completed;
	public $error;
	public $message;

}