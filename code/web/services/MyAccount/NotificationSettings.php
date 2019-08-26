<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

/** @noinspection PhpUnused */
class MyAccount_NotificationSettings extends MyAccount
{
	function launch($msg = null)
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['submit'])){
			$result = $catalog->processNotificationSettingsForm($user);

			$interface->assign('result', $result);
		}
		$this->display($catalog->getNotificationSettingsTemplate($user), 'Notification Settings');
	}
}