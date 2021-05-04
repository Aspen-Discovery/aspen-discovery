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
		$userLists->deleted = "0";
		$sort = $_REQUEST['sortBy'];
		$orderBy = 'ASC';
		if (($sort == 'dateCreated') || ($sort == 'created')) {
			$orderBy = 'DESC';
		}
		$userLists->orderBy($sort . ' ' . $orderBy);
		$userLists->find();
		$lists = [];
		while ($userLists->fetch()){
			$lists[] = clone $userLists;
		}
		$interface->assign('lists', $lists);
		$interface->assign('sortedBy', $sort);
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