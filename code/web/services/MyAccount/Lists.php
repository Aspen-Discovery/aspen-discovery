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
		$sort = $_REQUEST['sort'];
		$order = 'ASC';
		if (($sort == 'dateCreated') || ($sort == 'created')) {
			$order = 'DESC';
		}
		$sortOrder = $sort . ' ' . $order;
		$userLists->orderBy($sort . ' ' . $order);
		$userLists->find();
		$lists = [];
		while ($userLists->fetch()){
			$lists[] = clone $userLists;
		}
		$interface->assign('lists', $lists);
		$interface->assign('sortedBy', $sort);

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);

		$recordsPerPage = 2;
		$interface->assign('curPage', $page);

		$link = $_SERVER['REQUEST_URI'];
		if (preg_match('/[&?]page=/', $link)) {
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		} else if (strpos($link, "?") > 0) {
			$link .= "&page=%d";
		} else {
			$link .= "?page=%d";
		}
		if ($recordsPerPage != '-1') {
			$options = array(
				'fileName' => $link,
				'perPage' => $recordsPerPage,
				'append' => false,
				'linkRenderingObject' => $this,
				'linkRenderingFunction' => 'renderListPaginationLink',
				'sort' => $sortOrder,
				'showCovers' => isset($_REQUEST['showCovers'])
			);
			$pager = new Pager($options);

			$interface->assign('pageLinks', $pager->getLinks());
		}

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