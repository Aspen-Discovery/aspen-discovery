<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';

class Hoopla_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'HooplaScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'Hoopla';
	}
	function getPageTitle(){
		return 'Hoopla Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new HooplaScope();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
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
		return 'name asc';
	}
	function getObjectStructure(){
		return HooplaScope::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAdditionalObjectActions($existingObject){
		return [];
	}

	function getInstructions(){
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'hoopla';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Hoopla');
	}
}