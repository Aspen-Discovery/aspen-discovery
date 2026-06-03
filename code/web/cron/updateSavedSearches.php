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
$usersWithUpdatesToEmail = [];
if ($search->getNumResults() > 0) {
	$searchUpdateLogEntry->numSearches = $search->getNumResults();
	$searchUpdateLogEntry->update();
	$allSearches = $search->fetchAll('id');
	$numProcessed = 0;
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

			require_once ROOT_DIR . '/sys/SearchObject/minSO.php';

			SearchObjectFactory::initSearchObject($searchEntry->searchSource);
			$size = strlen($searchEntry->search_object);
			$minSO = unserialize($searchEntry->search_object);
			$searchObject = SearchObjectFactory::deminify($minSO);

			$searchObject->removeFilterByPrefix('time_since_added');
			$searchObject->addFilter('time_since_added:Week');
			$searchObject->setFieldsToReturn('id,title_display,author_display');
			$searchObject->setLimit(3);

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
						//Only send new result notifications once a week
						$searchEntry->hasNewResults = 0;
					}
				} else {
					$searchEntry->lastUpdated = date("Y-m-d");
				}
				if ($searchEntry->update() > 0) {
					$newTitles = $searchResult['response']['docs'];

					$searchUpdateLogEntry->numUpdated++;
					if ($searchEntry->hasNewResults && $searchEntry->sendNotification && $userForSearch->canReceiveNotifications('notifySavedSearch')) {
						global $logger;
						$logger->log("New results in search " . $searchEntry->title . " for user " . $userForSearch->id, Logger::LOG_ERROR);
						$appScheme = 'aspen-lida';
						require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
						$brandedSettings = new BrandedAppSetting();
						if ($brandedSettings->find(true)) {
							$appScheme = $brandedSettings->slugName;
						} else {
							require_once ROOT_DIR . '/sys/SystemVariables.php';
							$systemVariables = SystemVariables::getSystemVariables();
							if ($systemVariables && !empty($systemVariables->appScheme)) {
								$appScheme = $systemVariables->appScheme;
							}
						}
						//define body
						$body = [
							'title' => 'New Titles',
							'body' => 'New titles have been added to your saved search "' . $searchEntry->title . '" at the library. Check them out!',
							'categoryId' => 'savedSearch',
							'channelId' => 'savedSearch',
							'data' => ['url' => urlencode($appScheme . '://user/saved_search?search=' . $searchEntry->id . "&name=" . $searchEntry->title)],
						];
						//send message
						$userForSearch->sendPushNotification($body, "saved_search");
					}
					// If the user wishes to receive saved search emails, keep track of those here.
					if ($searchEntry->hasNewResults && $searchEntry->sendNotification && $userForSearch->notifySavedSearches) {
						$userLibrary = $userForSearch->getHomeLibrary();
						$baseUrl = $userLibrary->getBaseUrl();
						$key = $userForSearch->id . '|' . $baseUrl;

						$searchObject = SearchObjectFactory::deminify($minSO);
						$searchUrl = $searchObject->renderSearchUrl(true);
						if (!str_starts_with($searchUrl, 'http')) {
							$searchUrl = $baseUrl . $searchUrl;
						}
						if (!isset($usersWithUpdatesToEmail[$key])) {
							$usersWithUpdatesToEmail[$key] = [
								'user' => $userForSearch,
								'library' => $userLibrary,
								'baseUrl' => $baseUrl,
								'updatedSearches' => [
									[
										'title' => $searchEntry->title,
										'url' => $searchUrl,
										'newTitles' => $newTitles
									]
								],
							];
						} else {
							$usersWithUpdatesToEmail[$key]['updatedSearches'][] = [
								'title' => $searchEntry->title,
								'url' => $searchUrl,
								'newTitles' => $newTitles
							];
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

// Now that we know all the searches that have updates, let's send a single email to each distinct email address from that set.
require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
foreach ($usersWithUpdatesToEmail as $data) {
	if (empty($data['user']) || empty($data['updatedSearches'])) {
		continue;
	}
	/** @var User $activeUser */
	$activeUser = $data['user'];

	global $activeLanguage;
	require_once ROOT_DIR . '/sys/Translation/Language.php';
	$validLanguages = Language::getValidLanguages();
	if (array_key_exists($activeUser->interfaceLanguage, $validLanguages)) {
		$activeLanguage = $validLanguages[$activeUser->interfaceLanguage];
	}else{
		$activeLanguage = $validLanguages['en'];
	}
	$emailTemplate = EmailTemplate::getActiveTemplate('savedSearchAlert', $activeUser);

	$emailAddress = $data['user']->email;

	$updatedSearches = "";
	$updatedSearchesHtml = "<ul>";

	$updatedSearchesWithSampleTitles = "";
	$updatedSearchesWithSampleTitlesHtml = "<ul>";

	$nl = PHP_EOL;

	foreach ($data['updatedSearches'] as $updatedSearch) {
		$title = $updatedSearch['title'];
		$url = $updatedSearch['url'];
		if (empty($title) || empty($url)) {
			continue;
		}

		$updatedSearches .= "{$title} ({$url}){$nl}";
		$updatedSearchesWithSampleTitles .= "{$title} ({$url}){$nl}";
		$updatedSearchesHtml .= "<li><strong style='font-size:125%;'><a href='{$url}'>{$title}</a></strong></li>";
		$updatedSearchesWithSampleTitlesHtml .= "<li><strong style='font-size:125%;'><a href='{$url}'>{$title}</a></strong>";

		if (!empty($updatedSearch['newTitles']) && is_array($updatedSearch['newTitles'])) {
			$updatedSearchesWithSampleTitlesHtml .= "<ul>";
			foreach ($updatedSearch['newTitles'] as $newTitle) {
				$titleId = $newTitle['id'] ?? null;
				if (!$titleId) {
					continue;
				}
				$titleUrl = rtrim($data['baseUrl'] ?? '', '/') . "/GroupedWork/" . $titleId;
				$displayTitle = $newTitle['title_display'] ?? '';
				$displayAuthor = $newTitle['author_display'] ?? '';
				$updatedSearchesWithSampleTitles .= $displayTitle . " - " . $displayAuthor . " (" . $titleUrl . ")" . $nl;
				$updatedSearchesWithSampleTitlesHtml .= "<li><a href='{$titleUrl}'>{$displayTitle}</a> {$displayAuthor}</li>";
			}
			$updatedSearchesWithSampleTitlesHtml .= "</ul>";
		}
		$updatedSearchesWithSampleTitlesHtml .= "</li>";
		$updatedSearchesWithSampleTitles .= $nl;
	}
	$updatedSearchesHtml .= "</ul>";
	$updatedSearchesWithSampleTitlesHtml .= "</ul>";

	$parameters = [
		'user' => $activeUser,
		'library' => $data['library'],
		'searchHistory' => [
			'updatedSearches' => $updatedSearches,
			'updatedSearchesHtml' => $updatedSearchesHtml,
			'updatedSearchesWithSampleTitles' => $updatedSearchesWithSampleTitles,
			'updatedSearchesWithSampleTitlesHtml' => $updatedSearchesWithSampleTitlesHtml,
			'url' => $data['baseUrl'] . '/Search/History?require_login'
		]
	];

	$emailTemplate->sendEmail($emailAddress, $parameters);
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