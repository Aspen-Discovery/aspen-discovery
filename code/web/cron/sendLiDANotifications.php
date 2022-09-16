<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';

require_once ROOT_DIR . '/sys/Account/PType.php';
require_once ROOT_DIR . '/sys/Account/User.php';

require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotification.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationLocation.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationPType.php';

global $logger;

$allNotifications = new LiDANotification();
$allNotifications->sent = 0;
$notifications = $allNotifications->fetchAll('id');

foreach($notifications as $notification) {
	$tokens = [];
	$notificationToSend = new LiDANotification();
	$notificationToSend->id = $notification;
	if($notificationToSend->find(true)) {
		$now = time();
		if($now - $notificationToSend->sendOn > 0) {
			$expirationTime = $notificationToSend->sendOn + (7 * 24 * 60 * 60);
			if(!empty($notificationToSend->expiresOn)) {
				$expirationTime = $notificationToSend->expiresOn;
			}
			$tokens = $notificationToSend->getEligibleUsers();
			foreach($tokens as $token => $user) {
				$body = array(
					'to' => $user['token'],
					'title' => $notificationToSend->title,
					'body' => strip_tags($notificationToSend->message),
					'categoryId' => 'libraryAlert',
					'channelId' => 'libraryAlert',
					'expiration' => $expirationTime,
					'data' => [
						'sendOn' => $notificationToSend->sendOn,
					]
				);

				if($notificationToSend->ctaUrl) {
					$body['data'] = [
						'url' => urlencode($notificationToSend->ctaUrl),
						'label' => $notificationToSend->ctaLabel ?: 'View',
					];
				}

				$expoNotification = new ExpoNotification();
				$expoNotification->sendExpoPushNotification($body, $user['token'], $user['uid'], "custom_notification");
			}

			$notificationToSend->sent = 1;
			$notificationToSend->update();
		}
	}
}