<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyRatings extends MyAccount{
	public function launch(){
		global $interface;
		global $timer;

		//Load user ratings
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$rating = new UserWorkReview();
		$rating->userId = UserAccount::getActiveUserId();
		$rating->orderBy('dateRated DESC');
		$rating->find();
		$ratings = [];
		$ratedIds = [];
		while($rating->fetch()){
			if (!array_key_exists($rating->groupedRecordPermanentId, $ratedIds)) {
				$ratedIds[$rating->groupedRecordPermanentId] = clone $rating;
			}
			//$ratings[$rating->groupedRecordPermanentId] = [];
		}
		$timer->logTime("Loaded ids of titles the user has rated");

		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$records = $searchObject->getRecords(array_keys($ratedIds));
		foreach ($ratedIds as $permanentId => $rating){
			if (array_key_exists($permanentId, $records)){
				$groupedWorkDriver = $records[$permanentId];
				if ($groupedWorkDriver->isValid){
					$ratings[$rating->groupedRecordPermanentId] = array(
						'id' =>$rating->id,
						'groupedWorkId' => $rating->groupedRecordPermanentId,
						'title' => $groupedWorkDriver->getTitle(),
						'author' => $groupedWorkDriver->getPrimaryAuthor(),
						'rating' => $rating->rating,
						'review' => $rating->review,
						'link' => $groupedWorkDriver->getLinkUrl(),
						'dateRated' => $rating->dateRated,
						'ratingData' => $groupedWorkDriver->getRatingData(),
					);
				}
			}
		}

		//Load titles the user is not interested in
		$notInterested = [];

		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterestedObj = new NotInterested();
		$notInterestedObj->userId = UserAccount::getActiveUserId();
		$notInterestedObj->orderBy('dateMarked DESC');
		$notInterestedObj->find();
		$notInterestedIds = array();
		while ($notInterestedObj->fetch()) {
			$notInterestedIds[$notInterestedObj->groupedRecordPermanentId] = clone($notInterestedObj);
		}
		$timer->logTime("Loaded ids of titles the user is not interested in");

		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$records = $searchObject->getRecords(array_keys($notInterestedIds));
		foreach ($notInterestedIds as $permanentId => $notInterestedObj){
			if (array_key_exists($permanentId, $notInterestedIds)) {
				if (array_key_exists($permanentId, $records)) {
					$groupedWorkDriver = $records[$permanentId];
					if ($groupedWorkDriver->isValid) {
						$notInterested[] = array(
							'id' => $notInterestedObj->id,
							'title' => $groupedWorkDriver->getTitle(),
							'author' => $groupedWorkDriver->getPrimaryAuthor(),
							'dateMarked' => $notInterestedObj->dateMarked,
							'link' => $groupedWorkDriver->getLinkUrl()
						);
					}
				}
			}
		}
		$timer->logTime("Loaded grouped works for titles user is not interested in");

		$interface->assign('ratings', $ratings);
		$interface->assign('notInterested', $notInterested);
		$interface->assign('showNotInterested', false);

		$this->display('myRatings.tpl', 'My Ratings', 'Search/home-sidebar.tpl');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Ratings');
		return $breadcrumbs;
	}
}