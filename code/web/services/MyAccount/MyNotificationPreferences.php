<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MyAccount_MyNotificationPreferences extends MyAccount {
	function launch() : void {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			global $offlineMode;
			if (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}
			$obj = new UserNotificationToken();
			$obj->userId = $user->id;
			$obj->tokenType = "firebase";
			$obj->find();
			$tokens = [];
			while ($obj->fetch()) {
				$token = [
					"token" => $obj->pushToken,
					"deviceModel" => $obj->deviceModel,
					"notifyCustom" => $obj->notifyCustom,
					"notifyAccount" => $obj->notifyAccount,
					"notifySavedSearch" => $obj->notifySavedSearch
				];
				$tokens[] = $token;
			}
			$interface->assign("tokens", $tokens);
		}
		


		$this->display('myNotificationPreferences.tpl', 'Notifications');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Notification Settings');
		return $breadcrumbs;
	}
}