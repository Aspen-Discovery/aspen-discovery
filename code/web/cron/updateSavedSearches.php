<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';

require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
require_once ROOT_DIR . '/sys/CronLogEntry.php';
$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Updating Saved Searches';
$cronLogEntry->insert();

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
global $configArray;

$defaultSolrScope = $solrScope;
if ($search->getNumResults() > 0) {
	$searchUpdateLogEntry->numSearches = $search->getNumResults();
	$searchUpdateLogEntry->update();
	$allSearches = $search->fetchAll('id');
	$numProcessed = 0;
	$usersWithUpdatesToEmail = [];
	foreach ($allSearches as $searchId) {
		$searchEntry = new SearchEntry();
		$searchEntry->id = $searchId;
		if ($searchEntry->find(true)) {
			//Get the home library of the user
			$userForSearch = new User();
			$userForSearch->id = $searchEntry->user_id;

			if ($userForSearch->find(true)) {
				$homeLibrary = $userForSearch->getHomeLibrary();
				if ($homeLibrary == null) {
					$solrScope = $defaultSolrScope;
				} else {
					$solrScope = $homeLibrary->subdomain;
				}
			} else {
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

			$searchResult = $searchObject->processSearch();
			if (!$searchResult instanceof AspenError && empty($searchResult['error'])) {
				$numResults = $searchObject->getResultTotal();
				$hasNewResults = $numResults > 0;
				$searchEntry->hasNewResults = $hasNewResults;
				if (!empty($searchEntry->lastUpdated)) {
					$lastUpdated = strtotime($searchEntry->lastUpdated);
					$oneWeekLater = strtotime("+7 day", $lastUpdated);
					$oneWeekLater = date("Y-m-d", $oneWeekLater);
					$today = date("Y-m-d");
					if ($oneWeekLater == $today) {
						$searchEntry->lastUpdated = $today;
					} else {
						$searchEntry->hasNewResults = 0;
					}
				} else {
					$searchEntry->lastUpdated = date("Y-m-d");
				}
				if ($searchEntry->update() > 0) {
					$searchUpdateLogEntry->numUpdated++;
					if ($searchEntry->hasNewResults && $userForSearch->canReceiveNotifications('notifySavedSearch')) {
						global $logger;
						$logger->log("New results in search " . $searchEntry->title . " for user " . $userForSearch->id, Logger::LOG_ERROR);
						$appScheme = 'aspen-lida';
						require_once ROOT_DIR . '/sys/SystemVariables.php';
						$systemVariables = SystemVariables::getSystemVariables();
						if ($systemVariables && !empty($systemVariables->appScheme)) {
							$appScheme = $systemVariables->appScheme;
						}
						$notificationToken = new UserNotificationToken();
						$notificationToken->userId = $userForSearch->id;
						$notificationToken->notifySavedSearch = 1;
						$notificationToken->find();
						while ($notificationToken->fetch()) {
							$logger->log("Found notification push token for user " . $userForSearch->id, Logger::LOG_ERROR);
							$body = [
								'to' => $notificationToken->pushToken,
								'title' => 'New Titles',
								'body' => 'New titles have been added to your saved search "' . $searchEntry->title . '" at the library. Check them out!',
								'categoryId' => 'savedSearch',
								'channelId' => 'savedSearch',
								'data' => ['url' => urlencode($appScheme . '://user/saved_search?search=' . $searchEntry->id . "&name=" . $searchEntry->title)],
							];
							$expoNotification = new ExpoNotification();
							$expoNotification->sendExpoPushNotification($body, $notificationToken->pushToken, $searchEntry->user_id, "saved_search");
							$expoNotification = null;
						}
						$notificationToken->__destruct();
						$notificationToken = null;
					}
					// If the user wishes to receive saved search emails, keep track of those here.
					if ($userForSearch->notifySavedSearches == TRUE) {
						$userLibrary = $userForSearch->getHomeLibrary();
						$baseUrl = $userLibrary->getBaseUrl();
						$key = $userForSearch->email . '|' . $baseUrl;
						if (array_key_exists($key, $usersWithUpdatesToEmail)) {
							$usersWithUpdatesToEmail[$key][] = $searchEntry->title;
						}
						else {
							$usersWithUpdatesToEmail[$key] = [$searchEntry->title];
						}
					}
				}
			} else {
				if ($searchEntry->hasNewResults) {
					$searchEntry->hasNewResults = false;
					$searchEntry->update();
				}
			}
			$userForSearch = null;
		}
		$numProcessed++;
		if ($numProcessed % 100 == 0) {
			$searchUpdateLogEntry->update();
		}
		$searchEntry->__destruct();
		$searchEntry = null;
	}
}
$searchUpdateLogEntry->update();

$searchUpdateLogEntry->addNote("Finished updating saved searches");
$searchUpdateLogEntry->endTime = time();
$searchUpdateLogEntry->update();

// Now that we know all of the searches that have updates, let's send a single email to each distinct email address from that set.
require_once ROOT_DIR . '/sys/Email/Mailer.php';
$mailer = new Mailer();
foreach ($usersWithUpdatesToEmail as $key => $searchTitles) {
	$keySeparated = explode('|', $key);
	$emailAddress = $keySeparated[0];
	$baseUrl = $keySeparated[1];
	$body = translate([
		'text' => 'There are new results appearing in your saved searches!',
		'isPublicFacing' => true,
	]) . "\r\n" . $baseUrl . '/Search/History?require_login';
	$htmlBody = '<p>' . translate([
		'text' => 'There are new results appearing in %1%your saved searches%2%!',
		1 => '<a href="' . $baseUrl . '/Search/History?require_login">',
		2 => '</a>',
		'isPublicFacing' => true,
	]) . '</p><ul>';
	foreach ($searchTitles as $searchTitle) {
		$body .= "\r\n" . $searchTitle;
		$htmlBody .= '<li>' . $searchTitle . '</li>';
	}
	$htmlBody .= '</ul>';
	$result = $mailer->send($emailAddress, translate([
		'text' => "New Results in Your Saved Searches",
		'isPublicFacing' => true,
	]), $body, null, $htmlBody);
}

$numUpdated = ($searchUpdateLogEntry->numUpdated > 0) ? $searchUpdateLogEntry->numUpdated : 0;
$cronLogEntry->notes .= "<br/>Updated a total of " . $numUpdated . " searches.";
$cronLogEntry->endTime = time();
$cronLogEntry->update();

$search->__destruct();
$search = null;

global $aspen_db;
$aspen_db = null;

die();