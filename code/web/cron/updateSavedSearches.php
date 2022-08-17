<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';

require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';

//Create a log entry
$searchUpdateLogEntry = new SearchUpdateLogEntry();
$searchUpdateLogEntry->startTime = time();
$searchUpdateLogEntry->insert();

set_time_limit(0);

//Get a list of all saved searches
$search = new SearchEntry();
$search->saved = 1;
$search->searchSource = 'local';
$search->find();

global $library;
global $solrScope;
$defaultSolrScope = $solrScope;
if ($search->getNumResults() > 0){
	$searchUpdateLogEntry->numSearches = $search->getNumResults();
	$searchUpdateLogEntry->update();
	$allSearches = $search->fetchAll('id');
	$numProcessed = 0;
	foreach ($allSearches as $searchId){
		$searchEntry = new SearchEntry();
		$searchEntry->id = $searchId;
		if ($searchEntry->find(true)) {
			//Get the home library of the user
			$userForSearch = new User();
			$userForSearch->id = $searchEntry->user_id;

			if ($userForSearch->find(true)){
				$homeLibrary = $userForSearch->getHomeLibrary();
				if ($homeLibrary == null){
					$solrScope = $defaultSolrScope;
				}else{
					$solrScope = $homeLibrary->subdomain;
				}
			}else{
				continue;
			}

			$searchObject = SearchObjectFactory::initSearchObject();
			$size = strlen($searchEntry->search_object);
			$minSO = unserialize($searchEntry->search_object);
			$searchObject = SearchObjectFactory::deminify($minSO);

			$searchObject->removeFilterByPrefix('time_since_added');
			$searchObject->addFilter('time_since_added:Week');
			$searchObject->setFieldsToReturn('id');
			$searchObject->setLimit(10);

			$searchResult = $searchObject->processSearch(false,false);
			if (!$searchResult instanceof AspenError && empty($searchResult['error'])) {
				$numResults = $searchObject->getResultTotal();
				$hasNewResults = $numResults > 0;
				$searchEntry->hasNewResults = $hasNewResults;
				if ($searchEntry->update() > 0){
					$searchUpdateLogEntry->numUpdated++;
					if ($hasNewResults){
						$notificationToken = new UserNotificationToken();
						$notificationToken->userId = $searchEntry->user_id;
						$notificationToken->find();
						while($notificationToken->fetch()) {
							$notification = array(
								'to' => $notificationToken->pushToken,
								'title' => 'New Titles',
								'body' => 'New titles have been added to your saved search ' . $searchEntry->title . ' at the library. Check them out!',
								'categoryId' => 'savedSearch',
								'channelId' => 'savedSearch'
							);
							$expoNotification = new ExpoNotification();
							$expoNotification->sendExpoPushNotification($notification, $notificationToken->pushToken, $searchEntry->user_id, "saved_search");
						}
					}
				}
			}else{
				if ($searchEntry->hasNewResults) {
					$searchEntry->hasNewResults = false;
					$searchEntry->update();
				}
			}
		}
		$numProcessed++;
		if ($numProcessed % 100 == 0){
			$searchUpdateLogEntry->update();
		}
	}
}
$searchUpdateLogEntry->update();

$searchUpdateLogEntry->addNote("Finished updating saved searches");
$searchUpdateLogEntry->endTime = time();
$searchUpdateLogEntry->update();