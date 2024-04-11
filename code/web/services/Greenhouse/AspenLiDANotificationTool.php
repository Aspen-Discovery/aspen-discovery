<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/API/GreenhouseAPI.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class Greenhouse_AspenLiDANotificationTool extends Admin_Admin {
	function launch() {
		global $interface;
		$interface->assign('instructions', $this->getInstructions());

		require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
		$expo = new ExpoNotification();

		$notificationContents = '';
		$receiptContents = '';
		if (!empty($_REQUEST['pushToken']) && $_REQUEST['sendNotification']) {
			$pushToken = $_REQUEST['pushToken'];
			$title = $_REQUEST['testTitle'] ?? 'Test Notification';
			$body = $_REQUEST['testBody'] ?? 'Testing push notifications using the Notification Tool';
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

		$this->display('aspenLiDANotificationTool.tpl', 'Aspen LiDA Notification Tool');
	}

	function easy_printr($section, &$var) {
		$contents = "<pre id='{$section}'>";
		$formattedContents = print_r($var, true);
		if ($formattedContents !== false) {
			$contents .= $formattedContents;
		}
		$contents .= '</pre>';
		return $contents;
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Greenhouse/greenhouse-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://docs.expo.dev/push-notifications/sending-notifications/#errors';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/AspenLiDASiteListingCache', 'Aspen LiDA Notification Tool');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}
}