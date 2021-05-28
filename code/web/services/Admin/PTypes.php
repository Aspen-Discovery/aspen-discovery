<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/PType.php';

class Admin_PTypes extends ObjectEditor
{

	function getObjectType() : string{
		return 'PType';
	}
	function getToolName() : string{
		return 'PTypes';
	}
	function getPageTitle() : string{
		return 'Patron Types';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$libraryList = array();

		$object = new PType();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$libraryList[$object->id] = clone $object;
		}

		return $libraryList;
	}
	function getDefaultSort() : string
	{
		return 'pType asc';
	}
	function getObjectStructure() : array {
		return PType::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'pType';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/PTypes', 'Patron Types');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'primary_configuration';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Patron Types');
	}
}