<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/API/GreenhouseAPI.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';
//TODO add this page to admin section
class AspenPWA_NotificationTestingTool extends Admin_Admin {
	function launch() : void {
		global $interface;
		$interface->assign('instructions', $this->getInstructions());
		require_once ROOT_DIR . '/sys/Notifications/FirebaseNotification.php';
		$firebaseNotification = new FirebaseNotification();
		$notificationContents = '';
		$receiptContents = '';
		if (!empty($_REQUEST['pushToken']) && $_REQUEST['sendNotification']) {
			$pushToken = $_REQUEST['pushToken'];
			$title = $_REQUEST['testTitle'] ?? 'Test Notification';
			$body = $_REQUEST['testBody'] ?? 'Testing push notifications using the Notification Testing Tool';
			$results = $firebaseNotification->sendTestPushNotification($title, $body, $pushToken);
			$notificationContents = $this->easy_printr('notificationResponse', $results);
		}

		if (!empty($_REQUEST['receiptId']) && $_REQUEST['getNotificationReceipt']) {
			$receiptId = $_REQUEST['receiptId'];
			$results = $firebaseNotification->getTestPushNotificationReceipt($receiptId);
			$receiptContents = $this->easy_printr('receiptResponse', $results);
		}

		$interface->assign('receiptResponse', $receiptContents);
		$interface->assign('notificationResponse', $notificationContents);

		$this->display('NotificationTestingTool.tpl', 'Aspen Progressive Web Application(PWA) Notification Testing Tool');
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
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_pwa', 'Aspen Progressive Web Application(PWA)');
		$breadcrumbs[] = new Breadcrumb('/AspenPWA/NotificationTestingTool', 'Notification Testing Tool');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_pwa';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Send Aspen Progressive Web Application(PWA) Notifications to All Libraries',
			'Send Aspen Progressive Web Application(PWA) Notifications to All Locations',
			'Send Aspen Progressive Web Application(PWA) Notifications to Home Library',
			'Send Aspen Progressive Web Application(PWA) Notifications to Home Location',
			'Send Aspen PWA Notifications to Home Library Locations',
		]);
	}
}