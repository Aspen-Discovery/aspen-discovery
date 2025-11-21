<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/API/GreenhouseAPI.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class AspenLiDA_NotificationTestingTool extends Admin_Admin {
	function launch() : void {
		global $interface;
		$interface->assign('instructions', $this->getInstructions());

		require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
		$expo = new ExpoNotification();

		$notificationContents = '';
		$receiptContents = '';
		if (!empty($_REQUEST['pushToken']) && $_REQUEST['sendNotification']) {
			$pushToken = $_REQUEST['pushToken'];
			$title = $_REQUEST['testTitle'] ?? 'Test Notification';
			$body = $_REQUEST['testBody'] ?? 'Testing push notifications using the Notification Testing Tool';
			$results = $expo->sendExpoTestPushNotification($title, $body, $pushToken);
			$notificationContents = $this->easy_printr('notificationResponse', $results);
		}

		if (!empty($_REQUEST['receiptId']) && $_REQUEST['getNotificationReceipt']) {
			$receiptId = $_REQUEST['receiptId'];
			$results = $expo->getExpoTestPushNotificationReceipt($receiptId);
			$receiptContents = $this->easy_printr('receiptResponse', $results);
		}

		$interface->assign('receiptResponse', $receiptContents);
		$interface->assign('notificationResponse', $notificationContents);

		$this->display('aspenLiDANotificationTestingTool.tpl', 'Aspen LiDA Notification Testing Tool');
	}

	function easy_printr($section, &$var) : string {
		$contents = "<pre id='$section'>";
		$formattedContents = print_r($var, true);
		if ($formattedContents !== false) {
			$contents .= $formattedContents;
		}
		$contents .= '</pre>';
		return $contents;
	}

	function getInstructions(): string {
		return 'https://docs.expo.dev/push-notifications/sending-notifications/#errors';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/NotificationTestingTool', 'Notification Testing Tool');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Send Notifications to All Libraries',
			'Send Notifications to All Locations',
			'Send Notifications to Home Library',
			'Send Notifications to Home Location',
			'Send Notifications to Home Library Locations',
		]);
	}
}