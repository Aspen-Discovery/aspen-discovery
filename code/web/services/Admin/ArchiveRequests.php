<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Archive/ArchiveRequest.php';
class Admin_ArchiveRequests extends ObjectEditor {
	function getObjectType() : string{
		return 'ArchiveRequest';
	}
	function getToolName() : string{
		return 'ArchiveRequests';
	}
	function getPageTitle() : string{
		return 'Requests for Copies of Archive Materials';
	}
	function getAllObjects($page, $recordsPerPage) : array{
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
	function getDefaultSort() : string
	{
		return 'dateRequested desc';
	}

	function getObjectStructure() : array{
		return ArchiveRequest::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return UserAccount::userHasPermission('View Archive Material Requests');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#islandora_archive', 'Islandora Archives');
		$breadcrumbs[] = new Breadcrumb('/Admin/ArchiveRequests', 'Material Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'islandora_archive';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View Archive Material Requests');
	}
}