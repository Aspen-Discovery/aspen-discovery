<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';

class RBdigital_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'RBdigitalScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'RBdigital';
	}
	function getPageTitle(){
		return 'RBdigital Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new RBdigitalScope();
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
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
		return RBdigitalScope::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#rbdigital', 'RBdigital');
		if (!empty($this->activeObject) && $this->activeObject instanceof RBdigitalScope){
			$breadcrumbs[] = new Breadcrumb('/RBdigital/Settings?objectAction=edit&id=' . $this->activeObject->settingId , 'Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/RBdigital/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'rbdigital';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer RBdigital');
	}
}