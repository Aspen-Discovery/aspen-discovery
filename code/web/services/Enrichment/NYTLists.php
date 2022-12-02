<?php

include_once ROOT_DIR . '/services/Admin/Admin.php';
include_once ROOT_DIR . '/sys/UserLists/UserList.php';

class Enrichment_NYTLists extends Admin_Admin {

	function launch() {
		global $interface;

		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		$nytSettings = new NewYorkTimesSetting();

		if (!$nytSettings->find(true)) {
			$interface->assign('error', 'The New York Times API is not configured properly, create settings at <a href="/Admin/NewYorkTimesSettings"></a>');
		} else {
			$api_key = $nytSettings->booksApiKey;

			// instantiate class with api key
			require_once ROOT_DIR . '/sys/NYTApi.php';
			$nyt_api = new NYTApi($api_key);

			//Get the raw response from the API with a list of all the names
			$availableListsRaw = $nyt_api->get_list('names');

			//Convert into an object that can be processed
			$availableLists = json_decode($availableListsRaw);
			$availableListsCompareFunction = function ($subjectArray0, $subjectArray1) {
				return strcasecmp($subjectArray0->display_name, $subjectArray1->display_name);
			};

			$prevYear = date("Y-m-d", strtotime("-1 year"));
			$availableLists = $availableLists->results;
			usort($availableLists, $availableListsCompareFunction);

			// only fetch list of lists that have been updated by NYT in the last year (used for create/update dropdown)
			$activeAvailableLists = [];
			foreach ($availableLists as $availableList) {
				if ($availableList->newest_published_date > $prevYear) {
					$activeAvailableLists[] = $availableList;
				}
			}
			$interface->assign('availableLists', $activeAvailableLists);

			$isListSelected = !empty($_REQUEST['selectedList']);
			$selectedList = null;
			if ($isListSelected) {
				$selectedList = $_REQUEST['selectedList'];
				$interface->assign('selectedListName', $selectedList);

				if (isset($_REQUEST['submit'])) {
					//Find and update the correct Aspen Discovery list, creating a new list as needed.
					require_once ROOT_DIR . '/services/API/ListAPI.php';
					$listApi = new ListAPI();
					try {
						$results = $listApi->createUserListFromNYT($selectedList, null);
						if ($results['success'] == false) {
							$interface->assign('error', $results['message']);
						} else {
							$interface->assign('successMessage', $results['message']);
						}
						sleep(7);
					} catch (Exception $e) {
						$interface->assign('error', $e->getMessage());
					}
				}
			}

			// Fetch lists after any updating has been done

			// Get user id
			$nyTimesUser = new User();
			$nyTimesUser->username = 'nyt_user';
			if ($nyTimesUser->find(1)) {
				$prevYear = date("Y-m-d", strtotime("-1 year"));
				// Get User Lists
				$nyTimesUserLists = new UserList();
				$nyTimesUserLists->user_id = $nyTimesUser->id;
				$nyTimesUserLists->whereAdd('title like "NYT - %"');
				$nyTimesUserLists->deleted = 0;
				$nyTimesUserLists->orderBy('title');
				$existingLists = $nyTimesUserLists->fetchAll();

				$activeNYTLists = [];
				foreach ($existingLists as $existingList) {
					$activeNYTList = new UserList();
					$activeNYTList->id = $existingList->id;
					$activeNYTList->find();
					while ($activeNYTList->fetch()) {
						$nytListModified = strtotime($activeNYTList->nytListModified);
						$lastModified = date('Y-m-d', $nytListModified);
						if ($lastModified >= $prevYear) {
							$activeNYTLists[] = $activeNYTList;
						} else {
							$activeNYTList->delete();
						}
					}
				}
				$interface->assign('existingLists', $activeNYTLists);
			}
		}

		$this->display('nytLists.tpl', 'Lists from New York Times');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/NYTLists', 'New York Times Lists');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('View New York Times Lists');
	}
}