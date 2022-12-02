<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';

class MaterialsRequest_ManageStatuses extends ObjectEditor {

	function getObjectType(): string {
		return 'MaterialsRequestStatus';
	}

	function getModule(): string {
		return 'MaterialsRequest';
	}

	function getToolName(): string {
		return 'ManageStatuses';
	}

	function getPageTitle(): string {
		return 'Materials Request Statuses';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new MaterialsRequestStatus();

		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}

		$object->libraryId = $homeLibrary->libraryId;
		$this->applyFilters($object);

		$object->orderBy('isDefault DESC');
		$object->orderBy('isPatronCancel DESC');
		$object->orderBy('isOpen DESC');
		$object->orderBy('description ASC');
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'isDefault desc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure(): array {
		return MaterialsRequestStatus::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'description';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function customListActions() {
		$objectActions = [];

		$objectActions[] = [
			'label' => 'Reset to Default',
			'action' => 'resetToDefault',
		];

		return $objectActions;
	}

	/** @noinspection PhpUnused */
	function resetToDefault() {
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		$materialRequestStatus = new MaterialsRequestStatus();
		$materialRequestStatus->libraryId = $homeLibrary->libraryId;
		$materialRequestStatus->delete(true);

		$materialRequestStatus = new MaterialsRequestStatus();
		$materialRequestStatus->libraryId = -1;
		$materialRequestStatus->find();
		while ($materialRequestStatus->fetch()) {
			$materialRequestStatus->id = null;
			$materialRequestStatus->libraryId = $homeLibrary->libraryId;
			$materialRequestStatus->insert();
		}
		header("Location: /MaterialsRequest/ManageStatuses");
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageStatuses', 'Manage Materials Requests Statuses');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Materials Requests');
	}
}