<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';

class CloudLibrary_Scopes extends ObjectEditor
{
	function getObjectType(){
		return 'CloudLibraryScope';
	}
	function getToolName(){
		return 'Scopes';
	}
	function getModule(){
		return 'CloudLibrary';
	}
	function getPageTitle(){
		return 'Cloud Library Scopes';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new CloudLibraryScope();
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
		return CloudLibraryScope::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return true;
	}
	function canDelete(){
		return true;
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'Cloud Library');
		$breadcrumbs[] = new Breadcrumb('/CloudLibrary/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cloud_library';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Cloud Library');
	}
}