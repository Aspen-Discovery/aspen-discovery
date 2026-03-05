<?php

require_once ROOT_DIR . '/Action.php';

class History extends Action {
	var $catalog;
	private static $searchSourceLabels = [
		'local' => 'Catalog',
		'genealogy' => 'Genealogy',
	];

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		//Load system messages
		if (UserAccount::isLoggedIn()) {
			$accountMessages = [];
			try {
				$customAccountMessages = new SystemMessage();
				$now = time();
				$customAccountMessages->showOn = 1;
				$customAccountMessages->whereAdd("startDate = 0 OR startDate <= $now");
				$customAccountMessages->whereAdd("endDate = 0 OR endDate > $now");
				$customAccountMessages->find();
				while ($customAccountMessages->fetch()) {
					if ($customAccountMessages->isValidForDisplay()) {
						$accountMessages[] = clone $customAccountMessages;
					}
				}
			} catch (Exception $e) {
				//This happens before the table is created, ignore it.
			}
			global $interface;
			$interface->assign('accountMessages', $accountMessages);
		}
	}

	function launch() {
		global $interface;

		// In some contexts, we want to require a login before showing search
		// history:
		if (isset($_REQUEST['require_login']) && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$launchAction = new MyAccount_Login();
			$launchAction->launch();
			exit();
		}

		global $library;
		if (!$library->enableSavedSearches) {
			//User shouldn't get here
			$module = 'Error';
			$action = 'Handle404';
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		if (isset($_REQUEST['purge']) && $_REQUEST['purge'] == 'true') {
			$s = new SearchEntry();
			$s::purgeUserRecentSearches(session_id(), UserAccount::getActiveUserId());
			// We don't want to remember the last search after a purge:
			unset($_SESSION['lastSearchURL']);
		}

		$interface->assign('numSavedSearches', 0);
		$interface->assign('numRecentSearches', 0);
		$tab = $_REQUEST['tab'] ?? 'saved';
		if ($user = UserAccount::getActiveUserObj()) {
			$searchEntry = new SearchEntry();
			$savedSearches = $searchEntry::getUserSavedSearches($user->id);
			$recentSearches = $searchEntry::getUserRecentSearches(session_id(), $user->id);
			$interface->assign('numSavedSearches', count($savedSearches));
			$interface->assign('numRecentSearches', count($recentSearches));
			if (count($savedSearches) == 0) {
				$tab = 'recent'; // If there are no saved searches show the recent searches tab by default
			}
		}
		$interface->assign('tab', $tab);
		// initial on page load values for pagination and sorting
		$page = $_REQUEST['page'] ?? 1;
		$interface->assign('page', $page);
		$limit = $_REQUEST['limit'] ?? 20;
		$interface->assign('limit', $limit);
		$sort = $_REQUEST['sort'] ?? 'id';
		$interface->assign('sort', $sort);


		if (UserAccount::isLoggedIn()) {
			$this->loadAccountSidebarVariables();

			$this->display('history.tpl', 'Search History');
		} else {
			$this->display('history.tpl', 'Search History', '');
		}
	}

	public static function getSearchForSaveForm($searchId) {
		global $interface;

		// Retrieve search history
		$s = new SearchEntry();
		$searchHistory = $s->getSearches(session_id(), UserAccount::isLoggedIn() ? UserAccount::getActiveUserId() : null);

		$thisSearch = [];
		if (count($searchHistory) > 0) {
			// Loop through the history to find the one we want
			foreach ($searchHistory as $search) {
				if ($search->id == $searchId) {
					$searchObject = SearchObjectFactory::initSearchObject();
					$size = strlen($search->search_object);
					$minSO = unserialize($search->search_object);
					$searchObject = SearchObjectFactory::deminify($minSO);

					$searchObject->activateAllFacets();

					$searchSourceLabel = $searchObject->getSearchSource();
					if (array_key_exists($searchSourceLabel, self::$searchSourceLabels)) {
						$searchSourceLabel = self::$searchSourceLabels[$searchSourceLabel];
					}

					$thisSearch = [
						'id' => $search->id,
						'title' => $search->title,
						'url' => $searchObject->renderSearchUrl(),
						'description' => $searchObject->displayQuery(),
						'filters' => $searchObject->getFilterList(),
						'hits' => number_format($searchObject->getResultTotal()),
						'source' => $searchSourceLabel,
					];

					if (empty($thisSearch['description'])) {
						$thisSearch['description'] = "Anything (Empty search)";
					}

					//This breaks the save search form, better to just leave it empty
//					if (empty($thisSearch['filters'])){
//						$thisSearch['filters'] = "No filters set";
//					}
				}
			}
		}

		$interface->assign('thisSearch', $thisSearch);
		return $thisSearch;
	}

	public static function getSavedSearchObject($searchId) {
		// Retrieve search history
		$s = new SearchEntry();
		$s->id = $searchId;
		if ($s->find(true)) {
			SearchObjectFactory::initSearchObject();
			$minSO = unserialize($s->search_object);

			$searchObject = SearchObjectFactory::deminify($minSO);

			$searchSourceLabel = $searchObject->getSearchSource();
			if (array_key_exists($searchSourceLabel, self::$searchSourceLabels)) {
				$searchSourceLabel = self::$searchSourceLabels[$searchSourceLabel];
			}

			$thisSearch = [
				'id' => $s->id,
				'url' => $s->searchUrl,
				'search_object' => $s->search_object,
				'source' => $searchSourceLabel,
				'hasNewResults' => $s->hasNewResults,
			];
		}
		return $thisSearch;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn()) {
			$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Search History');
		return $breadcrumbs;
	}
}