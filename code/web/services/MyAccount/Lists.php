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
		if (isset($_REQUEST['sort'])) {
			$sort = $_REQUEST['sort'];
		} else {
			$sort = 'title';
		}
		if (($sort == 'dateCreated') || ($sort == 'created') || ($sort == 'dateUpdated')) {
			$order = ' DESC';
		} else {
			$order = ' ASC';
		}

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);

		$listsPerPage = 20;
		$interface->assign('curPage', $page);
		$userLists->orderBy($sort . $order);
		$userLists->limit(($page - 1) * $listsPerPage, $listsPerPage);
		$listCount = $userLists->count();
		$userLists->find();
		$lists = [];
		while ($userLists->fetch()){
			$lists[] = clone $userLists;
		}
		$interface->assign('lists', $lists);
		$interface->assign('sortedBy', $sort);

		$options = array(
			'totalItems' => $listCount,
			'fileName' => '/MyAccount/MyLists?page=%d',
			'perPage' => $listsPerPage,
			'showCovers' => isset($_REQUEST['showCovers'])
		);
		$pager = new Pager($options);

			$interface->assign('pageLinks', $pager->getLinks());

		$this->display('../MyAccount/lists.tpl', 'My Lists');

	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Lists');
		return $breadcrumbs;
	}
}