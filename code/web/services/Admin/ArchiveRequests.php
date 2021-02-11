<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Archive/ArchiveRequest.php';
class Admin_ArchiveRequests extends ObjectEditor {
	function getObjectType(){
		return 'ArchiveRequest';
	}
	function getToolName(){
		return 'ArchiveRequests';
	}
	function getPageTitle(){
		return 'Requests for Copies of Archive Materials';
	}
	function getAllObjects($page, $recordsPerPage){
		$list = array();

		$object = new ArchiveRequest();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasPermission('View Archive Material Requests')){
			$homeLibrary = $user->getHomeLibrary();
			$archiveNamespace = $homeLibrary->archiveNamespace;
			$object->whereAdd("pid LIKE '{$archiveNamespace}:%'");
		}
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getDefaultSort()
	{
		return 'dateRequested desc';
	}

	function getObjectStructure(){
		return ArchiveRequest::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return UserAccount::userHasPermission('View Archive Material Requests');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#islandora_archive', 'Islandora Archives');
		$breadcrumbs[] = new Breadcrumb('/Admin/ArchiveRequests', 'Material Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'islandora_archive';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View Archive Material Requests');
	}
}