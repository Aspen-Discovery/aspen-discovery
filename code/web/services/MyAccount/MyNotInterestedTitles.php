<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyNotInterestedTitles extends MyAccount {
	public function launch() {
		global $interface;
		global $timer;

		//Load user ratings
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

		$page = $_REQUEST['page'] ?? 1;
		$pageSize = $_REQUEST['pageSize'] ?? 20;

		$user = UserAccount::getActiveUserObj();
		$numNotInterested = $user->getNumNotInterested();

		//Load titles the user is not interested in
		$notInterested = [];

		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterestedObj = new NotInterested();
		$notInterestedObj->userId = UserAccount::getActiveUserId();
		$notInterestedObj->orderBy('dateMarked DESC');
		$notInterestedObj->limit(($page - 1) * $pageSize, $pageSize);
		$notInterestedObj->find();
		$notInterestedIds = [];
		while ($notInterestedObj->fetch()) {
			$notInterestedIds[$notInterestedObj->groupedRecordPermanentId] = clone($notInterestedObj);
		}
		$timer->logTime('Loaded ids of titles the user is not interested in');

		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$records = $searchObject->getRecords(array_keys($notInterestedIds));
		foreach ($notInterestedIds as $permanentId => $notInterestedObj) {
			if (array_key_exists($permanentId, $notInterestedIds)) {
				if (array_key_exists($permanentId, $records)) {
					$groupedWorkDriver = $records[$permanentId];
					if ($groupedWorkDriver->isValid) {
						$notInterested[] = [
							'id' => $notInterestedObj->id,
							'title' => $groupedWorkDriver->getTitle(),
							'author' => $groupedWorkDriver->getPrimaryAuthor(),
							'dateMarked' => $notInterestedObj->dateMarked,
							'link' => $groupedWorkDriver->getLinkUrl(),
						];
					}
				}
			}
		}
		$timer->logTime('Loaded grouped works for titles user is not interested in');

		// Process Paging
		$options = [
			'perPage' => $pageSize,
			'totalItems' => $numNotInterested,
			'append' => false,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$interface->assign('notInterested', $notInterested);
		$interface->assign('showNotInterested', false);

		$this->display('myNotInterestedTitles.tpl', "Titles You're Not Interested In", 'Search/home-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', "Titles You're Not Interested In");
		return $breadcrumbs;
	}
}