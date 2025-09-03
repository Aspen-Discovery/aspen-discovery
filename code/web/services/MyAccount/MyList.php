<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MyAccount_MyList extends MyAccount {
	function __construct() {
		$this->requireLogin = false;
		parent::__construct();
	}

	/** @noinspection PhpUnused */
	function reloadCover() : array {
		$listId = $_REQUEST['id'];
		$listEntry = new UserListEntry();
		$listEntry->listId = $listId;

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->setRecordType('list');
		$bookCoverInfo->setRecordId($listEntry->listId);
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->setImageSource('');
			$bookCoverInfo->setThumbnailLoaded(0);
			$bookCoverInfo->setMediumLoaded(0);
			$bookCoverInfo->setLargeLoaded(0);
			$bookCoverInfo->update();
		}

		return [
			'success' => true,
			'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.',
		];
	}

	function launch() : void {
		global $interface;

		// Fetch the List object
		$listId = $_REQUEST['id'];
		$_SESSION['returnToModule'] = 'MyAccount';
		$_SESSION['returnToAction'] = 'MyList';
		$_SESSION['returnToId'] = $listId;
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$list = new UserList();
		$list->id = $listId;

		// Setup print interface variables
		$printListAuthor = isset($_REQUEST['listAuthor']) ? filter_var($_REQUEST['listAuthor'], FILTER_VALIDATE_BOOLEAN) : false;
		$printListDescription = isset($_REQUEST['listDescription']) ? filter_var($_REQUEST['listDescription'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryCovers = isset($_REQUEST['covers']) ? filter_var($_REQUEST['covers'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntrySeries = isset($_REQUEST['series']) ? filter_var($_REQUEST['series'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryFormats = isset($_REQUEST['formats']) ? filter_var($_REQUEST['formats'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryDescription = isset($_REQUEST['description']) ? filter_var($_REQUEST['description'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryNotes = isset($_REQUEST['notes']) ? filter_var($_REQUEST['notes'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryHoldings = isset($_REQUEST['holdings']) ? filter_var($_REQUEST['holdings'], FILTER_VALIDATE_BOOLEAN) : false;
		$printEntryRating = isset($_REQUEST['rating']) ? filter_var($_REQUEST['rating'], FILTER_VALIDATE_BOOLEAN) : false;
		$printInterface = isset($_REQUEST['print']) ? filter_var($_REQUEST['print'], FILTER_VALIDATE_BOOLEAN) : false;
		$interface->assign('printInterface', $printInterface);
		$interface->assign('printListAuthor', $printListAuthor);
		$interface->assign('printListDescription', $printListDescription);
		$interface->assign('printEntryCovers', $printEntryCovers);
		$interface->assign('printEntrySeries', $printEntrySeries);
		$interface->assign('printEntryFormats', $printEntryFormats);
		$interface->assign('printEntryDescription', $printEntryDescription);
		$interface->assign('printEntryNotes', $printEntryNotes);
		$interface->assign('printEntryHoldings', $printEntryHoldings);
		$interface->assign('printEntryRating', $printEntryRating);

		//If the list does not exist, create a new My Favorites List
		if (!$list->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		// Ensure user has privileges to view the list
		if (!$list->public && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$loginAction = new MyAccount_Login();
			$loginAction->launch();
			exit();
		}
		if (!$list->public && $list->user_id != UserAccount::getActiveUserId()) {
			//Allow the user to view if they are admin
			if (!UserAccount::isLoggedIn() || !UserAccount::userHasPermission('Edit All Lists')) {
				$this->display('invalidList.tpl', 'Invalid List');
				return;
			}
		}

		//List Notes are created as part of the "bulk add to list" function
		if (isset($_SESSION['listNotes'])) {
			$interface->assign('notes', $_SESSION['listNotes']);
			unset($_SESSION['listNotes']);
		}

		//Perform an action on the list, but verify that the user has permission to do so.
		$userCanEdit = false;
		$userObj = UserAccount::getActiveUserObj();
		if ($userObj !== false) {
			$userCanEdit = $userObj->canEditList($list);
			if ($userCanEdit && UserAccount::userHasPermission('Upload List Covers')){
				global $configArray;
				$customCoverPath =  $configArray['Site']['coverPath'] . '/original/lists/' . $list->id . '.png';
				$hasUploadedCover = file_exists($customCoverPath);
				$interface->assign('hasUploadedCover', $hasUploadedCover);
			}
		}

		if ($userCanEdit && (isset($_REQUEST['myListActionHead']) || isset($_REQUEST['myListActionItem']) || isset($_GET['delete']))) {
			if (isset($_REQUEST['myListActionHead']) && strlen($_REQUEST['myListActionHead']) > 0) {
				$actionToPerform = $_REQUEST['myListActionHead'];
				if ($actionToPerform == 'saveList') {
					$list->title = strip_tags($_REQUEST['newTitle']);
					$list->description = strip_tags($_REQUEST['newDescription']);
					$list->public = isset($_REQUEST['public']) && ($_REQUEST['public'] == 'true' || $_REQUEST['public'] == 'on');
					if (!$list->public) {
						$list->searchable = false;
						$list->displayListAuthor = false;
					} else {
						$list->searchable = isset($_REQUEST['searchable']) && ($_REQUEST['searchable'] == 'true' || $_REQUEST['searchable'] == 'on');
						$list->displayListAuthor = isset($_REQUEST['displayListAuthor']) && ($_REQUEST['displayListAuthor'] == 'true' || $_REQUEST['displayListAuthor'] == 'on');
					}
					$this->reloadCover();
					$list->update();
					$list->fixWeights();
				} elseif ($actionToPerform == 'deleteList') {
					$list->delete();

					header("Location: /MyAccount/Lists");
					die();
				} elseif ($actionToPerform == 'deleteListHard') {
					$list->delete(true, true);

					header("Location: /MyAccount/Lists");
					die();
				} elseif ($actionToPerform == 'bulkAddTitles') {
					$notes = $this->bulkAddTitles($list);
					$this->reloadCover();
					$_SESSION['listNotes'] = $notes;
				}
			} elseif (isset($_REQUEST['delete'])) {
				$recordToDelete = $_REQUEST['delete'];
				$list->removeListEntry($recordToDelete);
				$this->reloadCover();
				$list->update();
				$list->fixWeights();
			}

			//Redirect back to avoid having the parameters stay in the URL.
			header("Location: /MyAccount/MyList/$list->id");
			die();
		}

		//Check to see if we have page defaults
		$defaultPageSize = 20;
		$defaultSort = $list->defaultSort;
		if ($userObj !== false) {
			$pageDefaults = PageDefaults::getPageDefaultsForUser($userObj->id, 'MyAccount', 'MyList', $list->id);
			if ($pageDefaults != null) {
				$defaultPageSize = $pageDefaults->pageSize ?? $defaultPageSize;
				$defaultSort = $pageDefaults->pageSort ?? $defaultSort;
			}
		}

		// Send the list to the template so title/description can be displayed:
		$interface->assign('userList', $list);
		$interface->assign('listSelected', $list->id);

		global $library;
		$interface->assign('enableListDescriptions', $library->enableListDescriptions);

		if (!empty($library->allowableListNames)) {
			$validListNames = explode('|', $library->allowableListNames);
			foreach ($validListNames as $index => $listName) {
				$validListNames[$index] = translate([
					'text' => $listName,
					'isPublicFacing' => true,
					'isAdminEnteredData' => true,
				]);
			}
		} else {
			$validListNames = [];
		}
		$interface->assign('validListNames', $validListNames);

		// Retrieve and format dates to send to template
		$dateCreated = $list->created;
		$dateUpdated = $list->dateUpdated;
		$dateCreated = date("F j, Y, g:i a", $dateCreated);
		$dateUpdated = date("F j, Y, g:i a", $dateUpdated);
		$interface->assign('dateCreated', $dateCreated);
		$interface->assign('dateUpdated', $dateUpdated);

		// Create a handler for displaying favorites and use it to assign
		// appropriate template variables:
		$interface->assign('allowEdit', $userCanEdit);

		//Determine the sort options
		$activeSort = $defaultSort;
		if (isset($_REQUEST['sort']) && array_key_exists($_REQUEST['sort'], UserList::getSortOptions())) {
			$activeSort = $_REQUEST['sort'];
			//Update the default sort for the user as well
			if ($userObj !== false) {
				PageDefaults::updatePageDefaultsForUser($userObj->id, 'MyAccount', 'MyList', $list->id, null, $activeSort);
			}
		}
		if (empty($activeSort)) {
			$activeSort = 'dateAdded';
		}

		//Determine the page size
		if (isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize']))) {
			$defaultPageSize = $_REQUEST['pageSize'];
			if ($userObj !== false) {
				PageDefaults::updatePageDefaultsForUser($userObj->id, 'MyAccount', 'MyList', $list->id, $defaultPageSize, null);
			}
		}

		//Set the default sort (for people other than the list editor to match what the editor does)
		if ($userCanEdit && $activeSort != $list->defaultSort) {
			$list->defaultSort = $activeSort;
			$list->fixWeights();
			$list->update();
		}

		$this->buildListForDisplay($list, $userCanEdit, $activeSort, $defaultPageSize);

		if (UserAccount::isLoggedIn()) {
			$sidebar = 'Search/home-sidebar.tpl';
		} else {
			$sidebar = '';
		}
		$this->display('../MyAccount/list.tpl', $list->title ?? translate([
			'text' => 'My List',
			'isPublicFacing' => true,
		]), $sidebar, false);
	}

	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 * @param UserList $list
	 * @param bool $allowEdit
	 * @param string $sortName
	 * @param int $pageSize
	 */
	private function buildListForDisplay(UserList $list, bool $allowEdit, string $sortName, int $pageSize) : void {
		global $interface;

		$printInterface = isset($_REQUEST['print']) ? filter_var($_REQUEST['print'], FILTER_VALIDATE_BOOLEAN) : false;
		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null) {
			$queryParams = [];
		} else {
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				[
					$name,
					$value,
				] = explode("=", $param);
				if ($name != 'sort') {
					$queryParams[$name] = $value;
				}
			}
		}
		$sortOptions = [
			'title' => [
				'desc' => 'Title',
				'selected' => $sortName == 'title',
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => 'title'])),
			],
			'author' => [
				'desc' => 'Author',
				'selected' => $sortName == 'author',
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => 'author'])),
			],
			'dateAdded' => [
				'desc' => 'Date Added',
				'selected' => $sortName == 'dateAdded',
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => 'dateAdded'])),
			],
			'recentlyAdded' => [
				'desc' => 'Recently Added',
				'selected' => $sortName == 'recentlyAdded',
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => 'recentlyAdded'])),
			],
			'custom' => [
				'desc' => 'User Defined',
				'selected' => $sortName == 'custom',
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => 'custom'])),
			],
		];

		$interface->assign('sortList', $sortOptions);
		$interface->assign('userSort', ($sortName == 'custom')); // switch for when users can sort their list

		$totalRecords = $list->numValidListItems();
		$page = $_REQUEST['page'] ?? 1;
		$startRecord = ($page - 1) * $pageSize;
		if ($startRecord < 0) {
			$startRecord = 0;
		}
		$endRecord = $page * $pageSize;
		if ($endRecord > $totalRecords) {
			$endRecord = $totalRecords;
		}
		if ($printInterface) {
			// When printing, show all results on one page
			$startRecord = 0;
			$endRecord = $totalRecords;
			$pageSize = $totalRecords;
		}
		$pageInfo = [
			'resultTotal' => $totalRecords,
			'startRecord' => $startRecord,
			'endRecord' => $endRecord,
			'perPage' => $pageSize,
		];
		$resourceList = $list->getListRecords($startRecord, $pageSize, $allowEdit, 'html', null, $sortName);
		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd', $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

		$link = $_SERVER['REQUEST_URI'];
		if (preg_match('/[&?]page=/', $link)) {
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		} elseif (strpos($link, "?") > 0) {
			$link .= "&page=%d";
		} else {
			$link .= "?page=%d";
		}
		$options = [
			'totalItems' => $pageInfo['resultTotal'],
			'perPage' => $pageInfo['perPage'],
			'fileName' => $link,
			'append' => false,
		];
		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

	}

	function bulkAddTitles(UserList $list) : array {
		$totalRecords = $list->numValidListItems();
		$numAdded = 0;
		$notes = [];
		$titlesToAdd = $_REQUEST['titlesToAdd'];
		$titleSearches[] = preg_split("/\\r\\n|\\r|\\n/", $titlesToAdd);

		foreach ($titleSearches[0] as $titleSearch) {
			$titleSearch = trim($titleSearch);
			if (!empty($titleSearch)) {
				$_REQUEST['lookfor'] = $titleSearch;
				$_REQUEST['searchIndex'] = 'Keyword';
				$searchObject = SearchObjectFactory::initSearchObject();
				$searchObject->setLimit(1);
				$searchObject->init();
				$searchObject->clearFacets();
				$results = $searchObject->processSearch(false, false);
				if ($results['response'] && $results['response']['numFound'] >= 1) {
					$firstDoc = $results['response']['docs'][0];
					//Get the id of the document
					$id = $firstDoc['id'];

					//Check to see if the title has already been added to the list
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					$userListEntry->source = 'GroupedWork';
					$userListEntry->sourceId = $id;
					if ($userListEntry->find(true)) {
						//Title already exists, skip it
						$existingEntry = true;
						continue;
					}
					$userListEntry->weight = $totalRecords++;
					$numAdded++;

					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWork = new GroupedWork();
					$groupedWork->permanent_id = $userListEntry->sourceId;
					if ($groupedWork->find(true)) {
						$userListEntry->title = mb_substr($groupedWork->full_title, 0, 50);
					}

					$userListEntry->notes = '';
					$userListEntry->dateAdded = time();
					$userListEntry->insert();
				} else {
					$notes[] = "Could not find a title matching " . $titleSearch;
				}
			}
		}

		//Update solr
		$list->update();

		if ($numAdded > 0) {
			$notes[] = "Added $numAdded titles to the list";
		} elseif ($numAdded === 0) {
			$notes[] = 'No titles were added to the list';
		}

		return $notes;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		if (UserAccount::isLoggedIn()) {
			$breadcrumbs[] = new Breadcrumb('/MyAccount/Lists', 'Lists');
		}
		$breadcrumbs[] = new Breadcrumb('', 'List');
		return $breadcrumbs;
	}
}