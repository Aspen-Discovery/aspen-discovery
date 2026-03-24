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

		global $library;
		$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		$interface->assign('formatDisplayStyle', $groupedWorkDisplaySettings->formatDisplayStyle);
		$interface->assign('hideManifestationsInMobileView', $groupedWorkDisplaySettings->hideManifestationsInMobileView);
		$interface->assign('facetCountsToShow', $groupedWorkDisplaySettings->facetCountsToShow);
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
		if (empty($listId) || !is_numeric($listId) || !$list->find(true)) {
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
		//Only show list groups if the active user has the
		$showListGroup = false;
		$userObj = UserAccount::getActiveUserObj();
		if ($userObj !== false) {
			$userCanEdit = $userObj->canEditList($list);
			if ($userCanEdit && UserAccount::userHasPermission('Upload List Covers')){
				global $configArray;
				$customCoverPath =  $configArray['Site']['coverPath'] . '/original/lists/' . $list->id . '.png';
				$hasUploadedCover = file_exists($customCoverPath);
				$interface->assign('hasUploadedCover', $hasUploadedCover);
			}
			if ($userObj->id == $list->user_id) {
				$showListGroup = true;
			}
		}
		$interface->assign('showListGroup', $showListGroup);

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
					if ($showListGroup) {
						$list->listGroupId = isset($_REQUEST['listGroupSelect']) ? intval($_REQUEST['listGroupSelect']) : -1;
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

		//Get the types of records on the list and create a
		$selectedResourceTypes = $_REQUEST['resourceTypes'] ?? [];
		$activeListSources = $list->getListSources();
		$listSources = [];
		$activeUrl = $_SERVER['REQUEST_URI'];
		foreach ($activeListSources as $listSource) {
			$isApplied = empty($selectedResourceTypes) || in_array($listSource, $selectedResourceTypes);
			if ($isApplied) {
				$url = $activeUrl;
				if (empty($selectedResourceTypes)) {
					//Removing is adding everything except this
					$removalUrl = $activeUrl;
					foreach ($activeListSources as $listSource2) {
						if ($listSource2 != $listSource) {
							$removalUrl .= (str_contains($removalUrl, '?') ? '&' : '?') . "resourceTypes[]=$listSource2";
						}
					}
				}else{
					$tmpRemovalUrl = str_replace([
						"&resourceTypes[]=$listSource",
						"?resourceTypes[]=$listSource"
					], '', $activeUrl);
					if (str_contains($tmpRemovalUrl, '&') && !str_contains($tmpRemovalUrl, '?')) {
						$tmpRemovalUrl = preg_replace("/resourceTypes\[]=$listSource&?/", '', $activeUrl);
					}
					$removalUrl = $tmpRemovalUrl;
				}
			}else{
				$url = $activeUrl . (str_contains($activeUrl, '?') ? '&' : '?') . "resourceTypes[]=$listSource";
				$removalUrl = $activeUrl;
			}
			$sourceDisplayName = $listSource;
			if ($listSource == 'GroupedWork') {
				$sourceDisplayName = 'Library Materials';
			}elseif ($listSource == 'OpenArchives') {
				$sourceDisplayName = 'History & Archives';
			}
			$listSources[] = [
				'value' => $listSource,
				'display' => $sourceDisplayName,
				'isApplied' => $isApplied,
				'url' => $url,
				'removalUrl' => $removalUrl,
				'countIsApproximate' => false,
				'count' => $list->getNumListEntriesBySource($listSource),
			];
		}
		$interface->assign('listSources', $listSources);
		if (empty($selectedResourceTypes)) {
			$selectedResourceTypes = $activeListSources;
		}
		$interface->assign('selectedResourceTypes', implode('|', $selectedResourceTypes));

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
				PageDefaults::updatePageDefaultsForUser($userObj->id, 'MyAccount', 'MyList', $list->id, null, $activeSort, null);
			}
		}
		if (empty($activeSort)) {
			$activeSort = 'dateAdded';
		}

		//Determine the page size
		if (isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize']))) {
			$defaultPageSize = $_REQUEST['pageSize'];
			if ($userObj !== false) {
				PageDefaults::updatePageDefaultsForUser($userObj->id, 'MyAccount', 'MyList', $list->id, $defaultPageSize, null, null);
			}
		}

		//Set the default sort (for people other than the list editor to match what the editor does)
		if ($userCanEdit && $activeSort != $list->defaultSort) {
			$list->defaultSort = $activeSort;
			$list->fixWeights();
			$list->update();
		}

		$inListGroup = false;
		if ($list->listGroupId > 0) {
			$inListGroup = true;
		}

		require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
		$listGroupInfo = null;
		if ($inListGroup) {
			$listGroup = new UserListGroup();
			$listGroup->id = $list->listGroupId;
			if ($listGroup->find(true)) {
				$listGroupInfo = clone $listGroup;
			}else{
				//The list group was deleted
				$inListGroup = false;
			}
		}
		$interface->assign('inListGroup', $inListGroup);
		$interface->assign('listGroupInfo', $listGroupInfo);

		$userListGroups = [];
		if ($userCanEdit && $showListGroup) {
			$listGroup = new UserListGroup();
			$userListGroups = $listGroup->getListGroups(UserAccount::getActiveUserObj());
		}
		$interface->assign('userListGroups', $userListGroups);

		$listHasFiltersApplied = 0;
		if (count($listSources) != count($selectedResourceTypes)) {
			$listHasFiltersApplied = 1;
		}
		$filterParams = [];
		if (array_key_exists('filter', $_REQUEST)) {
			$filterParams = $_REQUEST['filter'];
		}elseif (array_key_exists('activeFilters', $_REQUEST)) {
			$filterParams = explode('|',$_REQUEST['activeFilters']);
		}
		if (!empty($filterParams)) {
			$listHasFiltersApplied = 1;
		}
		$interface->assign('listHasFiltersApplied', $listHasFiltersApplied);
		$activeFilters = empty($filterParams) ? '' : implode('|', $filterParams);
		$interface->assign('activeFilters', $activeFilters);

		$this->buildListForDisplay($list, $userCanEdit, $activeSort, $defaultPageSize, $selectedResourceTypes, $filterParams);

		$numValidListItems = $list->numValidListItems();
		$interface->assign('numValidListItems', $numValidListItems);
		if ($numValidListItems > 0) {
			$sidebar = 'MyAccount/list-sidebar.tpl';
		}else{
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
	 * @param array $selectedResourceTypes
	 * @param array $filterParams
	 */
	private function buildListForDisplay(UserList $list, bool $allowEdit, string $sortName, int $pageSize, array $selectedResourceTypes, array $filterParams) : void {
		global $interface;

		$printInterface = isset($_REQUEST['print']) && filter_var($_REQUEST['print'], FILTER_VALIDATE_BOOLEAN);
		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null) {
			$queryParams = [];
		} else {
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				$parts = explode("=", $param, 2);
				if (count($parts) === 2) {
					[
						$name,
						$value,
					] = $parts;
					if ($name != 'sort' && $name != 'searchSource') {
						$queryParams[urldecode($name)] = urldecode($value);
					}
				}
			}
		}
		$availableSortOptions = UserList::getSortOptions();
		$sortOptions = [];
		foreach ($availableSortOptions as $sortKey => $sortLabel) {
			$sortOptions[$sortKey] = [
				'desc' => $sortLabel,
				'selected' => $sortName == $sortKey,
				'sortUrl' => "/MyAccount/MyList/$list->id?" . http_build_query(array_merge($queryParams, ['sort' => $sortKey])),
			];
		}

		$interface->assign('sortList', $sortOptions);
		$interface->assign('userSort', ($sortName == 'custom')); // switch for when users can sort their list

		// Calculate total records considering active filters.
		$page = $_REQUEST['page'] ?? 1;
		$startRecord = ($page - 1) * $pageSize;
		if ($startRecord < 0) {
			$startRecord = 0;
		}
		if ($printInterface) {
			// When printing, show all results on one page
			$startRecord = 0;
			$pageSize = $list->numValidListItems();
		}

		if (count($selectedResourceTypes) == 1 && in_array('GroupedWork', $selectedResourceTypes)) {
			//Because all records may not be indexed within Solr, and some records may have been weeded, we want to show all entries if no filters are applied
			//But, we still want to do the solr search to load the facets.
			$resourceList = $list->getListRecordsUsingSolr($startRecord, $pageSize, $allowEdit, 'html', null, $sortName, $filterParams);
			$numFilteredResults = $resourceList['numFilteredEntries'];
			$totalResults = $resourceList['numFilteredEntries'];
			$formattedRecords = $resourceList['formattedRecords'];

			if (empty($filterParams)) {
				$resourceList = $list->getListRecords($startRecord, $pageSize, $allowEdit, 'html', null, $sortName, false, 0, $selectedResourceTypes);
				$numFilteredResults = $list->numValidListItems($selectedResourceTypes);
				$totalResults = $numFilteredResults;
				$formattedRecords = $resourceList;
			}
		}else{
			$resourceList = $list->getListRecords($startRecord, $pageSize, $allowEdit, 'html', null, $sortName, false, 0,$selectedResourceTypes);
			$numFilteredResults = $list->numValidListItems($selectedResourceTypes);
			$totalResults = $numFilteredResults;
			$formattedRecords = $resourceList;
		}

		$endRecord = $page * $pageSize;
		if ($endRecord > $numFilteredResults) {
			$endRecord = $numFilteredResults;
		}
		$pageInfo = [
			'startRecord' => $startRecord,
			'endRecord' => $endRecord,
			'perPage' => $pageSize,
			'resultTotal' => $numFilteredResults
		];
		$interface->assign('resourceList', $formattedRecords);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord'] + 1);
		$interface->assign('recordEnd', $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

		$link = $_SERVER['REQUEST_URI'];
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
		global $interface;

		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		if (UserAccount::isLoggedIn()) {
			$breadcrumbs[] = new Breadcrumb('/MyAccount/Lists', 'Lists');
		}
		$breadcrumbs[] = new Breadcrumb('', 'List');

		$recordCount = $interface->getVariable('recordCount');
		if (empty($recordCount)) {
			$resultCountText = translate([
				'text' => "No Results Found",
				"isPublicFacing" => true,
			]);
		}else{
			$recordStart = number_format($interface->getVariable('recordStart'));
			$recordEnd = number_format($interface->getVariable('recordEnd'));
			$recordCount = number_format($interface->getVariable('recordCount'));
			$resultCountText = translate([
				'text' => "Showing %1% - %2% of %3%",
				1 => $recordStart,
				2 => $recordEnd,
				3 => $recordCount,
				"isPublicFacing" => true,
			]);
		}

		$numValidListItems = $interface->getVariable('numValidListItems');
		$resultCountText .= ' ('. translate([
			'text' => "%1% total entries",
			1 => $numValidListItems,
			"isPublicFacing" => true,
		]) .')';

		$breadcrumbs[] = new Breadcrumb(null, $resultCountText, false);
		return $breadcrumbs;
	}
}