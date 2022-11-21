<?php

require_once ROOT_DIR . '/Action.php';

class History extends Action {
	var $catalog;
	private  static $searchSourceLabels = array(
		'local' => 'Catalog',
		'genealogy' => 'Genealogy'
	);

	function launch()
	{
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
		if (!$library->enableSavedSearches){
			//User shouldn't get here
			$module = 'Error';
			$action = 'Handle404';
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		// Retrieve search history
		$s = new SearchEntry();
		$searchHistory = $s->getSearches(session_id(), UserAccount::isLoggedIn() ? UserAccount::getActiveUserId() : null);

		if (count($searchHistory) > 0) {
			// Build an array of history entries
			$links = array();
			$saved = array();

			// Loop through the history
			foreach($searchHistory as $search) {
				$size = strlen($search->search_object);
				$minSO = unserialize($search->search_object);
				$searchObject = SearchObjectFactory::deminify($minSO);

				// Make sure all facets are active so we get appropriate
				// descriptions in the filter box.
				$searchObject->activateAllFacets();

				$searchSourceLabel = $searchObject->getSearchSource();
				if (array_key_exists($searchSourceLabel, self::$searchSourceLabels)) {
					$searchSourceLabel = self::$searchSourceLabels[$searchSourceLabel];
				}

				$newItem = array(
					'id'          => $search->id,
					'time'        => date("g:ia, jS M y", $searchObject->getStartTime()),
					'title'       => $search->title,
					'url'         => $searchObject->renderSearchUrl(),
					'searchId'    => $searchObject->getSearchId(),
					'description' => $searchObject->displayQuery(),
					'filters'     => $searchObject->getFilterList(),
					'hits'        => number_format($searchObject->getResultTotal()),
					'source'      => $searchSourceLabel,
					'speed'       => round($searchObject->getQuerySpeed(), 2)."s",
					// Size is purely for debugging. Not currently displayed in the template.
					// It's the size of the serialized, minified search in the database.
					'size'        => round($size/1024, 3)."kb",
					'hasNewResults' => $search->hasNewResults == 1,

				);

				if ($search->hasNewResults){
					$searchObject->addFilter('time_since_added:Week');
					$newItem['newTitlesUrl'] = $searchObject->renderSearchUrl();
				}

				// Saved searches
				if ($search->saved == 1) {
					$saved[] = $newItem;

					// All the others
				} else {
					// If this was a purge request we don't need this
					if (isset($_REQUEST['purge']) && $_REQUEST['purge'] == 'true') {
						$search->delete();

						// We don't want to remember the last search after a purge:
						unset($_SESSION['lastSearchURL']);
						// Otherwise add to the list
					} else {
						$links[] = $newItem;
					}
				}
			}

			// One final check, after a purge make sure we still have a history
			if (count($links) > 0 || count($saved) > 0) {
				$interface->assign('links', array_reverse($links));
				$interface->assign('saved', array_reverse($saved));
				$interface->assign('noHistory', false);
				// Nothing left in history
			} else {
				$interface->assign('noHistory', true);
			}
			// No history
		} else {
			$interface->assign('noHistory', true);
		}

		if (UserAccount::isLoggedIn()){
			$this->loadAccountSidebarVariables();

			$this->display('history.tpl', 'Search History');
		}else{
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
			foreach($searchHistory as $search) {
				if($search->id == $searchId) {
					$searchObject = SearchObjectFactory::initSearchObject();
					$size = strlen($search->search_object);
					$minSO = unserialize($search->search_object);
					$searchObject = SearchObjectFactory::deminify($minSO);

					$searchObject->activateAllFacets();

					$searchSourceLabel = $searchObject->getSearchSource();
					if (array_key_exists($searchSourceLabel, self::$searchSourceLabels)) {
						$searchSourceLabel = self::$searchSourceLabels[$searchSourceLabel];
					}

					$thisSearch = array(
						'id'          => $search->id,
						'title'       => $search->title,
						'url'         => $searchObject->renderSearchUrl(),
						'description' => $searchObject->displayQuery(),
						'filters'     => $searchObject->getFilterList(),
						'hits'        => number_format($searchObject->getResultTotal()),
						'source'      => $searchSourceLabel,
					);

					if (empty($thisSearch['description'])){
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

			$thisSearch = array(
				'id' => $s->id,
				'url' => $s->searchUrl,
				'search_object' => $s->search_object,
				'source' => $searchSourceLabel,
				'hasNewResults' => $s->hasNewResults,
			);
		}
		return $thisSearch;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn()){
			$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Search History');
		return $breadcrumbs;
	}
}