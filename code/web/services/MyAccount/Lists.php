<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/UserLists/UserList.php';
require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';

class Lists extends MyAccount {

	function launch() : void {
		global $interface;
		global $library;

		$user = UserAccount::getActiveUserObj();
		$userLists = new UserList();
		$userLists->user_id = UserAccount::getActiveUserId();
		$userLists->deleted = "0";
		$sort = $_REQUEST['sort'] ?? 'title';
		if (($sort == 'dateCreated') || ($sort == 'created') || ($sort == 'dateUpdated')) {
			$order = ' DESC';
		} else {
			$order = ' ASC';
		}

		$interface->assign('listOwnerId', $user->id);

		$page = $_REQUEST['page'] ?? 1;
		$interface->assign('page', $page);

		$listsPerPage = 20;
		$interface->assign('startingNumber', ($page - 1) * $listsPerPage);
		$interface->assign('curPage', $page);
		$userLists->orderBy($sort . $order);
		$userLists->limit(($page - 1) * $listsPerPage, $listsPerPage);
		$listCount = $userLists->count();
		$userLists->find();
		$lists = [];
		while ($userLists->fetch()) {
			$lists[] = clone $userLists;
		}
		$interface->assign('lists', $lists);
		$interface->assign('sortedBy', $sort);

		$interface->assign('enableListDescriptions', $library->enableListDescriptions);

		$options = [
			'totalItems' => $listCount,
			'perPage' => $listsPerPage,
			'showCovers' => isset($_REQUEST['showCovers']),
			'displayListAuthor' => isset($_REQUEST['displayListAuthor']),
		];
		$pager = new Pager($options);

		$interface->assign('pageLinks', $pager->getLinks());

		$userCanTransfer = $user->isStaff() && UserAccount::userHasPermission('Transfer Lists');
		$interface->assign('userCanTransfer', $userCanTransfer);

		$lists = new UserList();
		$lists->user_id = UserAccount::getActiveUserId();
		$lists->listGroupId = -1;
		$numUnassignedLists = $lists->count();
		$interface->assign('numUnassignedLists', $numUnassignedLists);

		$activeListGroup = [];
		$listGroup = new UserListGroup();
		$listGroups = $listGroup->getListGroups(UserAccount::getActiveUserObj());
		$groupId = null;
		if (isset($_REQUEST['groupId'])) {
			$groupId = $_REQUEST['groupId'];
			$user->lastListGroupViewed = $groupId;
			$user->update();
			if ($groupId == -1) {
				$activeListGroup = UserAccount::getActiveUserObj()->getUnassignedListsForListGroups();
				$activeListGroupDetails = new UserListGroup();
				$activeListGroupDetails->title = 'Unassigned Lists';
				$activeListGroupDetails->id = -1;
			} else {
				$listGroup = new UserListGroup();
				$listGroup->id = $groupId;
				$listGroup->userId = UserAccount::getActiveUserId();
				if ($listGroup->find(true)) {
					$activeListGroupDetails = $listGroup;
					$userList = new UserList();
					$userList->user_id = UserAccount::getActiveUserId();
					$userList->listGroupId = $listGroup->id;
					$userList->find();
					while ($userList->fetch()) {
						$activeListGroup[] = clone $userList;
					}
				} else {
					$activeListGroup = UserListGroup::getLastViewedGroupForUser(UserAccount::getActiveUserObj());
					$activeListGroupDetails = UserListGroup::getLastViewedGroupDetailsForUser(UserAccount::getActiveUserObj());
				}
			}
		} else {
			if ($user->lastListGroupViewed == -1) {
				$activeListGroup = UserAccount::getActiveUserObj()->getUnassignedListsForListGroups();
				$activeListGroupDetails = new UserListGroup();
				$activeListGroupDetails->title = 'Unassigned Lists';
				$activeListGroupDetails->id = -1;
			} else {
				$activeListGroup = UserListGroup::getLastViewedGroupForUser(UserAccount::getActiveUserObj());
				if (empty($activeListGroup) && count($listGroups) > 0) {
					$activeListGroup = $listGroup->getListsForGroup(UserAccount::getActiveUserObj());
					$activeListGroupDetails = $listGroups[0];
					// Update the user's last viewed group to this one so we don't keep hitting this case
					$user->lastListGroupViewed = $activeListGroupDetails->id;
					$user->update();
				} else {
					$activeListGroupDetails = UserListGroup::getLastViewedGroupDetailsForUser(UserAccount::getActiveUserObj());
				}
			}
		}
		$interface->assign('groupId', $groupId);
		$interface->assign('activeListGroup', $activeListGroup);
		$interface->assign('activeListGroupDetails', $activeListGroupDetails);
		$interface->assign('listGroups', $listGroups);

		$this->display('../MyAccount/lists.tpl', 'My Lists');

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Lists');
		return $breadcrumbs;
	}
}