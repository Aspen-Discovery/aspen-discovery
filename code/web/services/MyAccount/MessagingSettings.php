<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_MessagingSettings extends MyAccount
{
	function launch($msg = null)
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (isset($_REQUEST['submit'])){
			$result = $catalog->processMessagingSettingsForm($user);

			$interface->assign('result', $result);
		}
		$this->display($catalog->getMessagingSettingsTemplate($user), 'Notification Settings');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Messaging Settings');
		return $breadcrumbs;
	}
}