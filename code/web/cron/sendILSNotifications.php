<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';

$allNotifications = new UserILSMessage();
$allNotifications->status = "pending";
$notifications = $allNotifications->fetchAll('id');
$allNotifications->__destruct();
$allNotifications = null;

foreach ($notifications as $notification) {
	$tokens = [];
	$ilsMessage = new UserILSMessage();
	$ilsMessage->id = $notification;
	if ($ilsMessage->find(true)) {
		$user = new User();
		$user->id = $ilsMessage->userId;
		if($user->find(true)) {
			if($user->canReceiveNotifications('notifyAccount') && $user->canReceiveILSNotification($ilsMessage->type)) {
				$tokens = $user->getNotificationPushToken();
				foreach($tokens as $token => $patron) {
					if($ilsMessage->title && $ilsMessage->content) {
						$body = [
							'to' => $user['token'],
							'title' => $ilsMessage->title,
							'body' => $ilsMessage->content,
							'categoryId' => 'accountAlert',
							'channelId' => 'accountAlert',
						];

						if(str_contains($ilsMessage->type, 'HOLD')) {
							$body['data'] = [
								'url' => urlencode(LocationSetting::getDeepLinkByName('user/holds', '')),
							];
						} elseif(str_contains($ilsMessage->type, 'CHECKOUT')) {
							$body['data'] = [
								'url' => urlencode(LocationSetting::getDeepLinkByName('user/checkouts', '')),
							];
						}

						$expoNotification = new ExpoNotification();
						$expoNotification->sendExpoPushNotification($body, $user['token'], $user['uid'], "ils_message");
						$expoNotification = null;
					}
				}
				$tokens = null;
				$ilsMessage->status = "sent";
				$ilsMessage->dateSent = time();
				$ilsMessage->update();
			}
		}
	}
}

global $aspen_db;
$aspen_db = null;

die();