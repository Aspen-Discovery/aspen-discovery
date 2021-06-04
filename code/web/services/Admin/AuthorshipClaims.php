<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Archive/ClaimAuthorshipRequest.php';
class Admin_AuthorshipClaims extends ObjectEditor {
	function getObjectType() : string{
		return 'ClaimAuthorshipRequest';
	}
	function getToolName() : string{
		return 'AuthorshipClaims';
	}
	function getPageTitle() : string{
		return 'Claims of Authorship for Archive Materials';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$list = array();

		$object = new ClaimAuthorshipRequest();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasPermission('View Archive Authorship Claims')){
			$homeLibrary = $user->getHomeLibrary();
			$archiveNamespace = $homeLibrary->archiveNamespace;
			$object->whereAdd("pid LIKE '{$archiveNamespace}:%'");
		}
		$object->orderBy($this->getSort());
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

	function getObjectStructure() : array {
		return ClaimAuthorshipRequest::getObjectStructure();
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
		return UserAccount::userHasPermission('View Archive Authorship Claims');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#islandora_archive', 'Islandora Archives');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorshipClaims', 'Authorship Claims');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'islandora_archive';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View Archive Authorship Claims', 'View Library Archive Authorship Claims']);
	}
}