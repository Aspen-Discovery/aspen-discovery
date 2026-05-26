<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestTitle.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php');
require_once(ROOT_DIR . '/sys/Administration/StickyFilter.php');
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MaterialsRequest_ManageTitleRequests extends ObjectEditor {
	public function launch(): void {
		global $interface;
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
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

		parent::launch();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageTitleRequests', 'Manage Materials Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function getObjectType(): string {
		return 'MaterialsRequestTitle';
	}

	function getToolName(): string {
		return 'ManageTitleRequests';
	}

	function getPageTitle(): string {
		return 'Manage Materials Requests By Title';
	}

	function getNumObjects(): int {
		if ($this->_numObjects === null) {
			$object = $this->getAllObjectQueryObj();

			$this->_numObjects = $object->count();
		}
		return $this->_numObjects;
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$list = [];

		$object = $this->getAllObjectQueryObj();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getAllObjectQueryObj(): MaterialsRequestTitle {
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		$userId = UserAccount::getActiveUserId();

		$object = new MaterialsRequestTitle();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);

		//Join materials requests so we can sort by and show the number of requests
		$materialsRequest = new MaterialsRequest();
		$materialsRequest->libraryId = $homeLibrary->libraryId;
		$object->joinAdd($materialsRequest, 'INNER', 'mr', 'id', 'materialsRequestTitleId');

		$materialsRequestStatus = new MaterialsRequestStatus();
		$object->joinAdd($materialsRequestStatus, 'INNER', 'mrs', 'mr.status', 'id');
		$object->selectAdd();
		$object->selectAdd("materials_request_title.*");
		$object->selectAdd('count(mr.id) as numRequests');
		$object->selectAdd('SUM(CASE when (mrs.isOpen = 1 and mrs.isActive = 1) THEN 1 ELSE 0 END) as numOpenRequests');
		$object->selectAdd("CASE when SUM(CASE WHEN mr.assignedTo = $userId THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END as assignedToMe");
		$object->groupBy('materials_request_title.id');

		return $object;
	}

	public function getCustomListPanel() : string {
		if ($this->getNumObjects() > 0) {
			global $interface;
			//Load status information
			$materialsRequestStatus = new MaterialsRequestStatus();
			$materialsRequestStatus->orderBy('holdNotNeeded DESC, holdFailed DESC, holdPlacedSuccessfully DESC, description ASC');
			$homeLibrary = Library::getPatronHomeLibrary();
			if (is_null($homeLibrary)) {
				//User does not have a home library, this is likely an admin account.  Use the active library
				global $library;
				$homeLibrary = $library;
			}

			$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
			$materialsRequestStatus->find();

			$availableStatuses = [];
			while ($materialsRequestStatus->fetch()) {
				$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			}
			$interface->assign('availableStatuses', $availableStatuses);
			return 'MaterialsRequest/manageTitleRequestPanel.tpl';
		}else{
			return '';
		}
	}

	function getObjectStructure($context = ''): array {
		return MaterialsRequestTitle::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getDefaultSort(): string {
		return 'title';
	}

	function getViewPermissions(): array {
		return ['Manage Library Materials Requests'];
	}

	function canAddNew(): bool {
		return false;
	}

	function canEdit(): bool {
		return false;
	}

	function canDelete(): bool {
		return false;
	}

	public function canCompare(): bool {
		return false;
	}

	function canBatchEdit(): bool {
		return false;
	}

	function canBatchDelete(): bool {
		return false;
	}

	protected function showHistoryLinks() : bool {
		return false;
	}
}
