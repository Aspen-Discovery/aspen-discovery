<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';

$userNotification = new UserNotification();
$userNotification->completed = 0;
$userNotification->error = 0;

$notifications = $userNotification->fetchAll('receiptId');

$numProcessed = 0;

foreach($notifications as $notification) {
	$expoNotification = new ExpoNotification();
	$expoNotification->getExpoNotificationReceipt($notification);
	$numProcessed++;
}