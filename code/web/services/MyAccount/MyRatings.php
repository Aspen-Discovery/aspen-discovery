<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyRatings extends MyAccount {
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
		$numRated = $user->getNumRatings();
		$rating = new UserWorkReview();
		$rating->userId = UserAccount::getActiveUserId();
		$rating->orderBy('dateRated DESC');
		$rating->limit(($page - 1) * $pageSize, $pageSize);
		$rating->find();
		$ratings = [];
		$ratedIds = [];
		while ($rating->fetch()) {
			if (!array_key_exists($rating->groupedRecordPermanentId, $ratedIds)) {
				$ratedIds[$rating->groupedRecordPermanentId] = clone $rating;
			}
			//$ratings[$rating->groupedRecordPermanentId] = [];
		}
		$timer->logTime("Loaded ids of titles the user has rated");

		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$records = $searchObject->getRecords(array_keys($ratedIds));
		foreach ($ratedIds as $permanentId => $rating) {
			if (array_key_exists($permanentId, $records)) {
				$groupedWorkDriver = $records[$permanentId];
				if ($groupedWorkDriver->isValid) {
					$ratings[$rating->groupedRecordPermanentId] = [
						'id' => $rating->id,
						'groupedWorkId' => $rating->groupedRecordPermanentId,
						'title' => $groupedWorkDriver->getTitle(),
						'author' => $groupedWorkDriver->getPrimaryAuthor(),
						'rating' => $rating->rating,
						'review' => $rating->review,
						'link' => $groupedWorkDriver->getLinkUrl(),
						'dateRated' => $rating->dateRated,
						'ratingData' => $groupedWorkDriver->getRatingData(),
					];
				}
			}
		}

		// Process Paging
		$options = [
			'perPage' => $pageSize,
			'totalItems' => $numRated,
			'append' => false,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());
		$interface->assign('ratings', $ratings);
		$interface->assign('showNotInterested', false);
		$this->display('myRatings.tpl', 'My Ratings', 'Search/home-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Titles You Rated');
		return $breadcrumbs;
	}
}