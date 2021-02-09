<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Scope.php';

class Axis360_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'Axis360Scope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'Axis360';
	}
	function getPageTitle(){
		return 'Axis 360 Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new Axis360Scope();
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
		return Axis360Scope::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		if (!empty($this->activeObject) && $this->activeObject instanceof Axis360Scope){
			$breadcrumbs[] = new Breadcrumb('/Axis360/Settings?objectAction=edit&id=' . $this->activeObject->settingId , 'Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/Axis360/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'axis360';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Axis 360');
	}
}