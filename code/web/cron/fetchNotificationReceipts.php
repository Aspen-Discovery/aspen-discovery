<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';

$userNotification = new UserNotification();
$userNotification->completed = 0;
$userNotification->error = 0;

$notifications = array_filter($userNotification->fetchAll('receiptId'));

$userNotification = null;

$numProcessed = 0;

$expoNotification = new ExpoNotification();
foreach ($notifications as $notification) {
	$expoNotification->getExpoNotificationReceipt($notification);
	$numProcessed++;
}
$expoNotification = null;

global $aspen_db;
$aspen_db = null;

die();