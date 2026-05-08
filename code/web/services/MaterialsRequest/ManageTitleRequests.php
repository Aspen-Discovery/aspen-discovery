<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestTitle.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php');
require_once(ROOT_DIR . '/sys/Administration/StickyFilter.php');
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MaterialsRequest_ManageTitleRequests extends Admin_Admin {

	function launch() : void {
		global $interface;
		global $aspen_db;
		$homeLibrary = Library::getPatronHomeLibrary();
		$user = UserAccount::getLoggedInUser();

		$interface->assign('showExistingTitleInformation', $homeLibrary->checkRequestsForExistingTitles);

		//Get a list of all material requests for the user
		$allRequests = [];
		if ($user) {

			$materialsRequestTitles = new MaterialsRequestTitle();

			if (isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize']) || $_REQUEST['pageSize'] == 'all')) {
				$materialsRequestTitlesPerPage =  $_REQUEST['pageSize'];
				PageDefaults::updatePageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageTitleRequests',null, $materialsRequestTitlesPerPage, null);
			} else {
				$pageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageTitleRequests',null);
				if ($pageDefaults !== null && !empty($pageDefaults->pageSize)) {
					$materialsRequestTitlesPerPage =  $pageDefaults->pageSize;
				}else{
					$materialsRequestTitlesPerPage = 30;
				}
			}
			if($materialsRequestTitlesPerPage == 'all') {
				$materialsRequestTitlesPerPage = $materialsRequestTitles->count();
				$interface->assign('showingAllRequests', true);
			} else {
				$interface->assign('showingAllRequests', false);
			}
			$interface->assign('materialsRequestsPerPage', $materialsRequestTitlesPerPage);
			$page = $_REQUEST['page'] ?? 1;
			if (isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], ['title', 'author', 'format', 'dateFirstRequested', 'dateLastRequested desc', 'numRequests desc'])) {
				$materialsRequestSort =  $_REQUEST['sort'];
				PageDefaults::updatePageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageTitleRequests',null, null, $materialsRequestSort);
			} else {
				$pageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageTitleRequests',null);
				if ($pageDefaults !== null && !empty($pageDefaults->pageSize)) {
					$materialsRequestSort =  $pageDefaults->pageSort;
				}else{
					$materialsRequestSort = 'dateLastRequested';
				}
			}
			$interface->assign('materialsRequestSort', $materialsRequestSort);
			if (!isset($_REQUEST['exportAll'])) {
				$materialsRequestTitles->limit(((int)$page - 1) * (int)$materialsRequestTitlesPerPage,(int)$materialsRequestTitlesPerPage);
			}
			$materialsRequestTitleCount = $materialsRequestTitles->count();

			if ($materialsRequestTitles->find()) {
				$stmt = $aspen_db->query("SELECT mrt.*, COUNT(mr.id) as numRequests 
							FROM materials_request_title mrt
							LEFT JOIN materials_request mr ON mrt.id = mr.materialsRequestTitleId
							GROUP BY mrt.id
							ORDER BY $materialsRequestSort
							");
				$allRequests = $stmt->fetchAll(PDO::FETCH_CLASS, 'MaterialsRequestTitle');
			}

			$options = [
				'totalItems' => $materialsRequestTitleCount,
				'perPage' => $materialsRequestTitlesPerPage,
			];

			$pager = new Pager($options);

			$interface->assign('pageLinks', $pager->getLinks());

			$columnsToDisplay = [
				'id' => 'Id',
				'title' => 'Title',
				'author' => 'Author',
				'format' => 'Format',
				'dateFirstRequested' => 'First Requested',
				'dateLastRequested' => 'Last Requested',
				'numRequests' => 'Number of Requests',
			];
			$interface->assign('columnsToDisplay', $columnsToDisplay);

			// Find Date Columns for JavaScript Table sorter
			$dateColumns = [];
			foreach (array_keys($columnsToDisplay) as $index => $column) {
				if (in_array($column, [
					'dateFirstRequested',
					'dateLastRequested',
				])) {
					$dateColumns[] = $index;
				}
			}
			$interface->assign('dateColumns', $dateColumns); //data gets added within template

		} else {
			$interface->assign('error', "You must be logged in to manage requests.");
		}
		// Get Statuses
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
		$materialsRequestStatus->find();
		$availableStatuses = [];
		while ($materialsRequestStatus->fetch()) {
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
		}
		$interface->assign('availableStatuses', $availableStatuses);
		// Get Assignees
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		$locations = new Location();
		$locations->libraryId = $homeLibrary->libraryId;
		$locations->find();
		$locationsForLibrary = [];
		while ($locations->fetch()) {
			$locationsForLibrary[] = $locations->locationId;
		}
		//Get a list of other users that are materials request users for this library
		$permission = new Permission();
		$permission->name = 'Manage Library Materials Requests';
		if ($permission->find(true)) {
			//Get roles for the user
			$rolePermissions = new RolePermissions();
			$rolePermissions->permissionId = $permission->id;
			$rolePermissions->find();
			$assignees = [];
			while ($rolePermissions->fetch()) {
				// Get Available Assignees
				$materialsRequestManagers = new User();
				if (count($locationsForLibrary) > 0) {
					if ($materialsRequestManagers->query("SELECT * from user WHERE id IN (SELECT userId FROM user_roles WHERE roleId = $rolePermissions->roleId) AND ((id IN (SELECT userId from user_administration_locations WHERE locationId IN (" . implode(', ', $locationsForLibrary) . "))) OR homeLocationId IN (" . implode(', ', $locationsForLibrary) . "))")) {
						while ($materialsRequestManagers->fetch()) {
							if (empty($materialsRequestManagers->displayName)) {
								$assignees[$materialsRequestManagers->id] = $materialsRequestManagers->firstname . ' ' . $materialsRequestManagers->lastname;
							} else {
								$assignees[$materialsRequestManagers->id] = $materialsRequestManagers->getDisplayName();
							}
						}
					}
				}
			}
			$interface->assign('assignees', $assignees);
		}
		$interface->assign('allRequests', $allRequests);


		$this->display('manageTitleRequests.tpl', 'Manage Materials Requests');

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageTitleRequests', 'Manage Materials Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manage Library Materials Requests');
	}
}
