<?php

require_once ROOT_DIR . '/sys/AspenError.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_ErrorReport extends ObjectEditor
{

	function getObjectType() : string{
		return 'AspenError';
	}
	function getToolName() : string{
		return 'ErrorReport';
	}
	function getPageTitle() : string{
		return 'Errors';
	}
	function getAllObjects($page, $recordsPerPage) : array{
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

	function getDefaultSort() : string
	{
		return 'timestamp desc';
	}

	function getObjectStructure() : array {
		return AspenError::getObjectStructure();
	}

	function getIdKeyColumn() : string{
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return true;
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/ErrorReport', 'Error Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_reports';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View System Reports');
	}
}