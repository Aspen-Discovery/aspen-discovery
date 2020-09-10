<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/UserLists/UserList.php';

class Lists extends MyAccount
{

	function launch()
	{
		global $interface;
		$userLists = new UserList();
		$userLists->user_id = UserAccount::getActiveUserId();
		$userLists->orderBy('title');
		$userLists->find();
		$lists = [];
		while ($userLists->fetch()){
			$lists[] = clone $userLists;
		}
		$interface->assign('lists', $lists);
		$this->display('../MyAccount/lists.tpl', translate('My Lists'));
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Lists');
		return $breadcrumbs;
	}
}