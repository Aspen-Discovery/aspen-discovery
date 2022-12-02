<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_HoldNotificationPreferences extends MyAccount {
	function launch($msg = null) {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['submit'])) {
			$result = $catalog->processHoldNotificationPreferencesForm($user);

			$interface->assign('result', $result);
		}
		$this->display($catalog->getHoldNotificationPreferencesTemplate($user), 'Hold Notification Settings');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Hold Notification Settings');
		return $breadcrumbs;
	}
}