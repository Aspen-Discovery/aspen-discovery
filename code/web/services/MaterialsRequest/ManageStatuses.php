<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';

class MaterialsRequest_ManageStatuses extends ObjectEditor
{

	function getObjectType(){
		return 'MaterialsRequestStatus';
	}
	function getToolName(){
		return 'ManageStatuses';
	}
	function getPageTitle(){
		return 'Materials Request Statuses';
	}
	function getAllObjects(){
		$status = new MaterialsRequestStatus();
		if (UserAccount::userHasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$status->libraryId = $homeLibrary->libraryId;
		}
		$status->orderBy('isDefault DESC');
		$status->orderBy('isPatronCancel DESC');
		$status->orderBy('isOpen DESC');
		$status->orderBy('description ASC');
		$status->find();
		$objectList = array();
		while ($status->fetch()){
			$objectList[$status->id] = clone $status;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return MaterialsRequestStatus::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'description';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('library_material_requests');
	}
	function customListActions(){
		$objectActions = array();
		if (UserAccount::userHasRole('library_material_requests')){
			$objectActions[] = array(
				'label' => 'Reset to Default',
				'action' => 'resetToDefault',
			);
		}

		return $objectActions;
	}

	/** @noinspection PhpUnused */
	function resetToDefault(){
		if (UserAccount::userHasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$materialRequestStatus = new MaterialsRequestStatus();
			$materialRequestStatus->libraryId = $homeLibrary->libraryId;
			$materialRequestStatus->delete(true);

			$materialRequestStatus = new MaterialsRequestStatus();
			$materialRequestStatus->libraryId = -1;
			$materialRequestStatus->find();
			while ($materialRequestStatus->fetch()){
				$materialRequestStatus->id = null;
				$materialRequestStatus->libraryId = $homeLibrary->libraryId;
				$materialRequestStatus->insert();
			}
		}
		header("Location: /Admin/ManageStatuses");
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageStatuses', 'Manage Materials Requests Statuses');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'materials_request';
	}
}