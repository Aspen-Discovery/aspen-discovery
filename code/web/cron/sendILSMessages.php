<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';
require_once ROOT_DIR . '/sys/CronLogEntry.php';
$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Send ILS Messages';
$cronLogEntry->insert();

$allNotifications = new UserILSMessage();
$allNotifications->status = "pending";
$notifications = $allNotifications->fetchAll('id');
$allNotifications->__destruct();
$allNotifications = null;

$numNotificationsSent = 0;
$cronLogEntry->notes = "Found " . count($notifications) . " notifications to process";
foreach ($notifications as $notification) {
	$tokens = [];
	$ilsMessage = new UserILSMessage();
	$ilsMessage->id = $notification;
	if ($ilsMessage->find(true)) {
		$user = new User();
		$user->id = $ilsMessage->userId;
		if($user->find(true)) {
			if($user->canReceiveNotifications('notifyAccount') && $user->canReceiveILSNotification($ilsMessage->type)) {
				if($ilsMessage->title && $ilsMessage->content) {
					//define the message
					$body = [
						'title' => $ilsMessage->title,
						'body' => $ilsMessage->content,
						'categoryId' => 'accountAlert',
						'channelId' => 'accountAlert',
					];
					$typeUpper = strtoupper($ilsMessage->type);
					if(str_contains($typeUpper, 'HOLD')) {
						$body['data'] = [
							'url' => urlencode(LocationSetting::getDeepLinkByName('user/holds', '')),
						];
					} elseif(str_contains($typeUpper, 'CHECKOUT') || str_contains($typeUpper,'OVERDUE') || str_contains($typeUpper,'BILLED')) {
						$body['data'] = [
							'url' => urlencode(LocationSetting::getDeepLinkByName('user/checkouts', '')),
						];
					}
					//send the message
					$sent = $user->sendPushNotification($body, 'ils_message');
					$numNotificationsSent += $sent;
				}
				// leaving this outside the if to preserve original behavior
				// if we should only update this if something actually sends
				// go ahead and move this inside the if statement above.
				$ilsMessage->status = "sent";
				$ilsMessage->dateSent = time();
				$ilsMessage->update();
			}
		}
	}
}

$cronLogEntry->notes .= "<br/>Sent $numNotificationsSent notifications";
$cronLogEntry->endTime = time();
$cronLogEntry->update();

global $aspen_db;
$aspen_db = null;

function console_log($message, $prefix = '') : void {
	$STDERR = fopen('php://stderr', 'w');
	fwrite($STDERR, $prefix . $message . "\n");
	fclose($STDERR);
}

die();