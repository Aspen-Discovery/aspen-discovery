<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/PType.php';

class Admin_PTypes extends ObjectEditor
{

	function getObjectType(){
		return 'PType';
	}
	function getToolName(){
		return 'PTypes';
	}
	function getPageTitle(){
		return 'Patron Types';
	}
	function getAllObjects($page, $recordsPerPage){
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
	function getDefaultSort()
	{
		return 'pType asc';
	}
	function getObjectStructure(){
		return PType::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'pType';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/PTypes', 'Patron Types');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Patron Types');
	}
}