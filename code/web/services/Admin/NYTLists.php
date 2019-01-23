<?php

/**
 * A class that allows generation of Lists from the New York Times API
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/29/2016
 * Time: 12:07 PM
 */
include_once ROOT_DIR . '/services/Admin/Admin.php';
include_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
class NYTLists extends Admin_Admin {

	function launch() {
		global $interface;
		global $configArray;

		//Display a list of available lists within the New York Times API
		if (!isset($configArray['NYT_API']) || empty($configArray['NYT_API']['books_API_key'])){
			$interface->assign('error', 'The New York Times API is not configured properly, create a books_API_key in the NYT_API section');
		}else{
			$api_key = $configArray['NYT_API']['books_API_key'];

			// instantiate class with api key
			require_once ROOT_DIR . '/sys/NYTApi.php';
			$nyt_api = new NYTApi($api_key);

			//Get the raw response from the API with a list of all the names
			$availableListsRaw = $nyt_api->get_list('names');
			//Convert into an object that can be processed
			$availableLists = json_decode($availableListsRaw);

			$interface->assign('availableLists', $availableLists);

			$isListSelected = !empty($_REQUEST['selectedList']);
			$selectedList = null;
			if ($isListSelected) {
				$selectedList = $_REQUEST['selectedList'];
				$interface->assign('selectedListName', $selectedList);

				if (isset($_REQUEST['submit'])){
					//Find and update the correct Pika list, creating a new list as needed.
					require_once ROOT_DIR . '/services/API/ListAPI.php';
					$listApi = new ListAPI();
					$results = $listApi->createUserListFromNYT($selectedList);
					if ($results['success'] == false){
						$interface->assign('error', $results['message']);
					}else{
						$interface->assign('successMessage', $results['message']);
					}
				}
			}

			// Fetch lists after any updating has been done

			// Get user id
			$nyTimesUser = new User();
			$nyTimesUser->cat_username = $configArray['NYT_API']['pika_username'];
			$nyTimesUser->cat_password = $configArray['NYT_API']['pika_password'];
			if ($nyTimesUser->find(1)) {
				// Get User Lists
				$nyTimesUserLists          = new UserList();
				$nyTimesUserLists->user_id = $nyTimesUser->id;
				$nyTimesUserLists->whereAdd('title like "NYT - %"');
				$nyTimesUserLists->orderBy('title');
				$pikaLists = $nyTimesUserLists->fetchAll();

				$interface->assign('pikaLists', $pikaLists);
			}
		}

		$this->display('nytLists.tpl', 'Lists from New York Times');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'contentEditor');
	}
}