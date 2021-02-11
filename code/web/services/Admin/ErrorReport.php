<?php

require_once ROOT_DIR . '/sys/AspenError.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_ErrorReport extends ObjectEditor
{

	function getObjectType(){
		return 'AspenError';
	}
	function getToolName(){
		return 'ErrorReport';
	}
	function getPageTitle(){
		return 'Errors';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new AspenError();
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
		return 'timestamp desc';
	}

	function getObjectStructure(){
		return AspenError::getObjectStructure();
	}

	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return true;
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/ErrorReport', 'Error Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_reports';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View System Reports');
	}
}