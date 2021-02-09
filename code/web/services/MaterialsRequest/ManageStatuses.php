<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';

class MaterialsRequest_ManageStatuses extends ObjectEditor
{

	function getObjectType(){
		return 'MaterialsRequestStatus';
	}
	function getModule()
	{
		return 'MaterialsRequest';
	}

	function getToolName(){
		return 'ManageStatuses';
	}
	function getPageTitle(){
		return 'Materials Request Statuses';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new MaterialsRequestStatus();

		$homeLibrary = Library::getPatronHomeLibrary();
		$object->libraryId = $homeLibrary->libraryId;
		$this->applyFilters($object);

		$object->orderBy('isDefault DESC');
		$object->orderBy('isPatronCancel DESC');
		$object->orderBy('isOpen DESC');
		$object->orderBy('description ASC');
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'isDefault desc';
	}
	function canSort()
	{
		return false;
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
	function customListActions(){
		$objectActions = array();

		$objectActions[] = array(
			'label' => 'Reset to Default',
			'action' => 'resetToDefault',
		);

		return $objectActions;
	}

	/** @noinspection PhpUnused */
	function resetToDefault(){
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
		header("Location: /MaterialsRequest/ManageStatuses");
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

	function canView()
	{
		return UserAccount::userHasPermission('Administer Materials Requests');
	}
}